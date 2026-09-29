<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi SaaS - Post-Service Follow-Up Engine.
 *
 * Core architectural engine driving automated post-service follow-ups,
 * delayed queue dispatching, anti-spam de-duplication, quiet hours enforcement,
 * and inbound response/NPS/reaction tracking across 9 industry families.
 *
 * @package Libraries
 */
class Follow_up_engine
{
    /**
     * @var App_Controller|CI_Controller
     */
    protected App_Controller|CI_Controller $CI;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('follow_up_rules_model');
        $this->CI->load->model('follow_up_dispatches_model');
        $this->CI->load->model('appointments_model');
        $this->CI->load->model('customers_model');
        $this->CI->load->model('providers_model');
        $this->CI->load->model('services_model');
        $this->CI->load->model('settings_model');
        $this->CI->load->model('messaging_settings_model');
        $this->CI->load->model('whatsapp_messages_model');

        $this->CI->load->library('notifications');
        $this->CI->load->library('queue');
        $this->CI->load->library('whatsapp_client');
        $this->CI->load->library('whatsapp_bridge');
        $this->CI->load->helper('industry');
    }

    /**
     * Entry point when a booking transitions to COMPLETED or CHECKED_OUT.
     * Dispatches BookingCompletedEvent and schedules delayed queue jobs.
     *
     * Now includes service-level follow-up awareness:
     * 1. Checks if the service has follow_up_required = true
     * 2. Uses service-level follow_up_category to match rules
     * 3. Respects follow_up_priority (critical/standard/optional)
     * 4. Applies service-level delay and message overrides
     *
     * @param int|string $booking_id The appointment/booking ID.
     * @param array $options Optional override parameters.
     * @return array Created dispatch IDs.
     */
    public function on_booking_completed(int|string $booking_id, array $options = []): array
    {
        $appointment = $this->CI->db
            ->get_where('appointments', ['id' => $booking_id])
            ->row_array();

        if (!$appointment) {
            log_message('error', "Follow_up_engine::on_booking_completed - Booking #{$booking_id} not found.");
            return [];
        }

        $customer_id = $appointment['id_users_customer'] ?? null;
        if (!$customer_id) {
            return [];
        }

        $customer = $this->CI->db
            ->get_where('users', ['id' => $customer_id])
            ->row_array();

        if (!$customer) {
            return [];
        }

        // Determine tenant identifier
        $tenant_id = $this->resolve_tenant_id();

        // Determine industry family and blueprint type
        $blueprint_type = current_industry_code();
        $industry_family = $this->resolve_industry_family($blueprint_type);

        // Load the service to check service-level follow-up configuration
        $service = null;
        if (!empty($appointment['id_services'])) {
            $service = $this->CI->db
                ->get_where('services', ['id' => $appointment['id_services']])
                ->row_array();
        }

        // Service-level follow-up fields
        $svc_follow_up_required  = (bool) ($service['follow_up_required'] ?? false);
        $svc_follow_up_category  = $service['follow_up_category'] ?? null;
        $svc_follow_up_priority  = $service['follow_up_priority'] ?? 'optional';
        $svc_delay_override      = $service['follow_up_delay_override'] ?? null;
        $svc_message_override    = $service['follow_up_message_override'] ?? null;

        // Fetch active rules for this tenant and blueprint
        $rules = $this->CI->follow_up_rules_model->get_active_rules($tenant_id, $blueprint_type);

        if (empty($rules)) {
            // Also check for general industry family rules
            $rules = $this->CI->follow_up_rules_model->get_active_rules($tenant_id, null);
            $rules = array_filter($rules, static function ($r) use ($industry_family) {
                return ($r['industry_family'] ?? '') === $industry_family;
            });
        }

        if (empty($rules)) {
            return [];
        }

        // Filter and prioritize rules based on service-level configuration
        $rules = $this->filter_rules_by_service_config($rules, $svc_follow_up_required, $svc_follow_up_category, $svc_follow_up_priority);

        if (empty($rules)) {
            return [];
        }

        $created_dispatches = [];
        $timezone = $this->get_company_timezone();
        $is_opted_out = !empty($customer['marketing_opt_out']);

        foreach ($rules as $rule) {
            $rule_type = $rule['rule_type'] ?? '';

            // Determine effective priority for this dispatch
            $effective_priority = $this->resolve_effective_priority($rule, $svc_follow_up_priority);

            // Critical priority rules (medical reaction checks) bypass opt-out
            if ($is_opted_out && $effective_priority !== 'critical') {
                continue;
            }

            // Apply service-level delay override if present
            $effective_delay = $svc_delay_override ?: ($rule['trigger_delay_interval'] ?? '0 minutes');

            // Calculate trigger time from interval
            $delay_seconds = $this->parse_interval_seconds($effective_delay);
            $scheduled_dt = new DateTime('now', new DateTimeZone($timezone));

            if ($delay_seconds > 0) {
                $scheduled_dt->modify('+' . $delay_seconds . ' seconds');
            }

            // Apply Quiet Hours Rule (21:00 - 09:00): postpone to next morning 09:30 if needed
            $scheduled_dt = $this->apply_quiet_hours($scheduled_dt, $timezone);

            // Compute actual delay seconds from now for queue
            $now_dt = new DateTime('now', new DateTimeZone($timezone));
            $queue_delay = max(0, $scheduled_dt->getTimestamp() - $now_dt->getTimestamp());

            // Build dynamic payload variables with service-level overrides
            $payload = $this->build_payload_context($appointment, $customer, $rule, array_merge($options, [
                '_service_follow_up_category' => $svc_follow_up_category,
                '_service_follow_up_priority' => $svc_follow_up_priority,
                '_service_message_override'   => $svc_message_override,
            ]));

            $dispatch_id = 'fup_' . bin2hex(random_bytes(16));

            $dispatch_data = [
                'id' => $dispatch_id,
                'tenant_id' => $tenant_id,
                'booking_id' => (string) $booking_id,
                'customer_id' => (string) $customer_id,
                'rule_id' => $rule['id'] ?? null,
                'channel' => 'WHATSAPP',
                'status' => 'SCHEDULED',
                'scheduled_for' => $scheduled_dt->format('Y-m-d H:i:s'),
                'payload' => $payload,
            ];

            $this->CI->follow_up_dispatches_model->schedule($dispatch_data);
            $created_dispatches[] = $dispatch_id;

            // Push to BullMQ/Redis/Database Queue via BooKi Queue library
            if ($this->CI->queue->enabled()) {
                $this->CI->queue->push(
                    'whatsapp',
                    'follow_up.process_dispatch',
                    ['dispatch_id' => $dispatch_id],
                    ['delay_seconds' => $queue_delay]
                );
            }
        }

        return $created_dispatches;
    }

    /**
     * Filter sector-level rules based on service-level follow-up configuration.
     *
     * Logic:
     * - If service has follow_up_required = true AND a specific category:
     *   → Only dispatch rules matching that category (e.g. medical_reaction → reaction_check)
     * - If service has follow_up_required = false AND priority = 'optional':
     *   → Only dispatch marketing/NPS rules if tenant has them enabled
     * - If service has no follow-up config (legacy):
     *   → Fall back to existing sector-level behavior (all active rules)
     *
     * @param array $rules All active rules for this tenant/blueprint
     * @param bool $svc_required Whether service requires follow-up
     * @param string|null $svc_category Service follow-up category
     * @param string $svc_priority Service follow-up priority level
     * @return array Filtered rules
     */
    private function filter_rules_by_service_config(array $rules, bool $svc_required, ?string $svc_category, string $svc_priority): array
    {
        // Legacy services without follow-up fields: use all sector rules (backward compat)
        if (!$svc_required && $svc_category === null) {
            return $rules;
        }

        // Map service follow-up categories to matching rule_types
        $category_to_rule_types = [
            'medical_reaction'    => ['reaction_check'],
            'medical_protocol'    => ['diet_form', 'routine_check', 'reaction_check'],
            'aftercare_safety'    => ['aftercare', 'reaction_check'],
            'asset_delivery'      => ['asset_delivery'],
            'compliance_check'    => ['aftercare', 'reaction_check'],
            'veterinary_postop'   => ['reaction_check', 'aftercare'],
            'retention_marketing' => ['retention_rebook'],
            'review_nps'          => ['review_request'],
        ];

        $matched_rule_types = $category_to_rule_types[$svc_category] ?? [];

        if ($svc_required) {
            // Service requires follow-up: dispatch category-matched rules
            // Always include retention_rebook and review_request as bonus if active
            $required_types = array_merge($matched_rule_types, ['retention_rebook', 'review_request']);
            return array_filter($rules, static function ($rule) use ($required_types) {
                return in_array($rule['rule_type'] ?? '', $required_types, true);
            });
        }

        // Service does NOT require follow-up: only dispatch optional marketing/NPS
        if ($svc_priority === 'optional') {
            return array_filter($rules, static function ($rule) {
                return in_array($rule['rule_type'] ?? '', ['retention_rebook', 'review_request'], true);
            });
        }

        return $rules;
    }

    /**
     * Resolve the effective follow-up priority considering both the rule type
     * and service-level priority configuration.
     *
     * - Medical reaction checks are ALWAYS critical regardless of service config
     * - Service priority elevates/lowers the effective priority
     *
     * @param array $rule The follow-up rule
     * @param string $svc_priority Service-level priority (critical/standard/optional)
     * @return string Effective priority
     */
    private function resolve_effective_priority(array $rule, string $svc_priority): string
    {
        $rule_type = $rule['rule_type'] ?? '';

        // Medical reaction checks are always critical - they involve patient safety
        if ($rule_type === 'reaction_check') {
            return 'critical';
        }

        // Service-level priority takes precedence for other rule types
        if ($svc_priority === 'critical') {
            return 'critical';
        }

        return $svc_priority ?: 'optional';
    }

    /**
     * Trigger a status-driven follow-up (e.g. READY_FOR_PICKUP in ceramic workshop or car wash).
     *
     * @param int|string $booking_id
     * @param string $trigger_status e.g. 'READY_FOR_PICKUP'
     * @param array $custom_payload
     * @return string|null Dispatch ID if scheduled.
     */
    public function on_status_trigger(int|string $booking_id, string $trigger_status, array $custom_payload = []): ?string
    {
        $appointment = $this->CI->db
            ->get_where('appointments', ['id' => $booking_id])
            ->row_array();

        if (!$appointment) {
            return null;
        }

        $customer_id = $appointment['id_users_customer'] ?? null;
        if (!$customer_id) {
            return null;
        }

        $customer = $this->CI->db->get_where('users', ['id' => $customer_id])->row_array();
        if (!$customer) {
            return null;
        }

        $tenant_id = $this->resolve_tenant_id();
        $blueprint_type = current_industry_code();

        // Find matching status-driven rule
        $rules = $this->CI->follow_up_rules_model->get_active_rules($tenant_id, $blueprint_type);
        $matched_rule = null;

        foreach ($rules as $rule) {
            if (($rule['trigger_delay_interval'] ?? '') === '0 minutes' && ($rule['rule_type'] ?? '') === 'asset_delivery') {
                $matched_rule = $rule;
                break;
            }
        }

        if (!$matched_rule) {
            return null;
        }

        $timezone = $this->get_company_timezone();
        $now_dt = new DateTime('now', new DateTimeZone($timezone));
        $scheduled_dt = $this->apply_quiet_hours($now_dt, $timezone);

        $payload = $this->build_payload_context($appointment, $customer, $matched_rule, $custom_payload);
        $dispatch_id = 'fup_' . bin2hex(random_bytes(16));

        $dispatch_data = [
            'id' => $dispatch_id,
            'tenant_id' => $tenant_id,
            'booking_id' => (string) $booking_id,
            'customer_id' => (string) $customer_id,
            'rule_id' => $matched_rule['id'],
            'channel' => 'WHATSAPP',
            'status' => 'SCHEDULED',
            'scheduled_for' => $scheduled_dt->format('Y-m-d H:i:s'),
            'payload' => $payload,
        ];

        $this->CI->follow_up_dispatches_model->schedule($dispatch_data);

        // Immediate or quiet-hours delayed dispatch
        $queue_delay = max(0, $scheduled_dt->getTimestamp() - time());
        if ($this->CI->queue->enabled()) {
            $this->CI->queue->push(
                'whatsapp',
                'follow_up.process_dispatch',
                ['dispatch_id' => $dispatch_id],
                ['delay_seconds' => $queue_delay]
            );
        } else {
            $this->process_dispatch($dispatch_id);
        }

        return $dispatch_id;
    }

    /**
     * Queue Worker handler: executed when delayed job fires.
     *
     * @param App_Controller|CI_Controller $ci
     * @param array $payload
     */
    public function handle_queued_dispatch(App_Controller|CI_Controller $ci, array $payload): void
    {
        $dispatch_id = $payload['dispatch_id'] ?? null;
        if ($dispatch_id) {
            $this->process_dispatch($dispatch_id);
        }
    }

    /**
     * Worker execution method with 3 critical business rules:
     * 1. Anti-Spam / Rebook check (De-duplication)
     * 2. Opt-Out check (RED / DUR)
     * 3. Quiet Hours check at execution time
     *
     * @param string $dispatch_id
     * @return bool
     */
    public function process_dispatch(string $dispatch_id): bool
    {
        $dispatch = $this->CI->follow_up_dispatches_model->find($dispatch_id);
        if (!$dispatch) {
            log_message('error', "Follow_up_engine::process_dispatch - Dispatch {$dispatch_id} not found.");
            return false;
        }

        if ($dispatch['status'] !== 'SCHEDULED') {
            // Already processed or cancelled
            return true;
        }

        $rule = null;
        if (!empty($dispatch['rule_id'])) {
            $rule = $this->CI->follow_up_rules_model->find($dispatch['rule_id']);
        }

        $rule_type = $rule['rule_type'] ?? 'retention_rebook';

        // --------------------------------------------------------------------
        // RULE 1: Anti-Spam / Rebook Control (De-duplication)
        // Before sending retention rebook, check if customer already has a future booking
        // --------------------------------------------------------------------
        if ($rule_type === 'retention_rebook') {
            $customer_id = $dispatch['customer_id'];
            $now = date('Y-m-d H:i:s');

            $future_booking = $this->CI->db
                ->where('id_users_customer', $customer_id)
                ->where('start_datetime >', $now)
                ->where_not_in('status', ['Cancelled', 'Draft', 'İptal', 'iptal', 'cancelled'])
                ->limit(1)
                ->get('appointments')
                ->row_array();

            if ($future_booking) {
                // Customer already rebooked! Cancel dispatch to avoid spamming
                $this->CI->follow_up_dispatches_model->mark_as_cancelled(
                    $dispatch_id,
                    'CANCELLED_REBOOKED',
                    [
                        'reason' => 'Customer already has active future booking',
                        'future_booking_id' => $future_booking['id'],
                        'start_datetime' => $future_booking['start_datetime'],
                    ]
                );
                return true;
            }
        }

        // --------------------------------------------------------------------
        // RULE 2: Opt-Out Check (KVKK / İleti Güvenliği)
        // If customer opted out (RED/DUR), suppress all EXCEPT medical reaction checks
        // --------------------------------------------------------------------
        $customer = $this->CI->db
            ->get_where('users', ['id' => $dispatch['customer_id']])
            ->row_array();

        if ($customer && !empty($customer['marketing_opt_out'])) {
            if ($rule_type !== 'reaction_check') {
                $this->CI->follow_up_dispatches_model->mark_as_cancelled(
                    $dispatch_id,
                    'CANCELLED_OPT_OUT',
                    ['reason' => 'Customer opted out via RED/DUR']
                );
                return true;
            }
        }

        // --------------------------------------------------------------------
        // RULE 3: Quiet Hours Check (21:00 - 09:00)
        // If worker executes inside quiet hours, reschedule to next day 09:30
        // --------------------------------------------------------------------
        $timezone = $this->get_company_timezone();
        $now_dt = new DateTime('now', new DateTimeZone($timezone));
        $hour = (int) $now_dt->format('G');

        if ($hour >= 21 || $hour < 9) {
            $adjusted_dt = $this->apply_quiet_hours($now_dt, $timezone);
            $new_time_str = $adjusted_dt->format('Y-m-d H:i:s');

            $this->CI->follow_up_dispatches_model->reschedule(
                $dispatch_id,
                $new_time_str,
                'Quiet hours postponement to 09:30'
            );

            // Re-enqueue job
            $delay = max(0, $adjusted_dt->getTimestamp() - time());
            if ($this->CI->queue->enabled()) {
                $this->CI->queue->push(
                    'whatsapp',
                    'follow_up.process_dispatch',
                    ['dispatch_id' => $dispatch_id],
                    ['delay_seconds' => $delay]
                );
            }
            return true;
        }

        // --------------------------------------------------------------------
        // Dispatch via Meta Cloud WhatsApp API / SMS / In-App
        // --------------------------------------------------------------------
        try {
            $payload = $dispatch['payload_decoded'] ?? [];
            $channel = strtoupper($dispatch['channel'] ?? 'WHATSAPP');
            $message_text = $this->render_message_text($rule, $payload, $customer);

            if ($channel === 'WHATSAPP') {
                $this->send_whatsapp_message($customer, $message_text, $rule, $payload);
            } elseif ($channel === 'SMS') {
                $this->send_sms_message($customer, $message_text);
            }

            $this->CI->follow_up_dispatches_model->mark_as_sent($dispatch_id, $payload);
            return true;
        } catch (Throwable $e) {
            log_message('error', "Follow_up_engine::process_dispatch failed: " . $e->getMessage());
            $this->CI->follow_up_dispatches_model->mark_as_failed($dispatch_id, $e->getMessage());
            return false;
        }
    }

    /**
     * Webhook listener for incoming customer replies via WhatsApp.
     * Handles:
     * 1. Opt-out ("RED", "DUR", "STOP", "IPTAL")
     * 2. Opt-in resume ("BASTIR", "START")
     * 3. Medical reaction responses (1: "İyiyim", 2: "Doktoruma Danışmak İstiyorum")
     * 4. NPS review ratings (1 to 5)
     *
     * @param string $from Sender WhatsApp ID / Phone number.
     * @param string $body Text message content.
     * @param array|null $matched_user Resolved user row from users table.
     * @return bool True if handled by follow-up engine.
     */
    public function handle_inbound_reply(string $from, string $body, ?array $matched_user): bool
    {
        $normalized = mb_strtoupper(trim($body), 'UTF-8');

        // 1. KVKK / İleti Güvenliği: Opt-Out Check ("RED", "DUR", "STOP", "IPTAL", "UNSUBSCRIBE")
        if (in_array($normalized, ['RED', 'DUR', 'STOP', 'IPTAL', 'İPTAL', 'UNSUBSCRIBE', 'ÇIKIŞ', 'CIKIS'], true)) {
            if ($matched_user) {
                $this->CI->db->update('users', ['marketing_opt_out' => 1], ['id' => $matched_user['id']]);
                $this->CI->follow_up_dispatches_model->cancel_pending_for_customer($matched_user['id'], true);

                $reply = "Takip ve tanıtım mesajı listesinden çıktınız. Tıbbi ve acil durum bildirimleri haricinde tarafınıza otomatik mesaj gönderilmeyecektir. Yeniden bildirim almak için BASTIR yazabilirsiniz.";
                $this->send_inbound_acknowledgment($from, $reply, $matched_user);
                return true;
            }
        }

        // 2. Opt-In Resume ("BASTIR", "START", "BAŞLAT")
        if (in_array($normalized, ['BASTIR', 'START', 'BAŞLAT', 'BASLAT', 'ABONE'], true)) {
            if ($matched_user) {
                $this->CI->db->update('users', ['marketing_opt_out' => 0], ['id' => $matched_user['id']]);
                $reply = "Takip ve bilgilendirme bildirimleri tekrar aktif edilmiştir. Teşekkür ederiz!";
                $this->send_inbound_acknowledgment($from, $reply, $matched_user);
                return true;
            }
        }

        if (!$matched_user) {
            return false;
        }

        $customer_id = $matched_user['id'];

        // 3. Medical Reaction Check Reply Handling
        $latest_reaction_dispatch = $this->CI->follow_up_dispatches_model->get_latest_for_customer(
            $customer_id,
            'reaction_check',
            259200 // last 3 days
        );

        if ($latest_reaction_dispatch) {
            if ($normalized === '1' || str_contains(mb_strtolower($body, 'UTF-8'), 'iyi') || str_contains(mb_strtolower($body, 'UTF-8'), 'sorun yok')) {
                // Patient is recovering well
                $this->CI->follow_up_dispatches_model->record_response($latest_reaction_dispatch['id'], [
                    'reaction' => 'good',
                    'choice' => 1,
                    'raw' => $body,
                ]);

                $reply = "Geri bildiriminiz için teşekkür ederiz. İyileşme sürecinizi yakından takip ediyoruz. Sağlıklı günler dileriz!";
                $this->send_inbound_acknowledgment($from, $reply, $matched_user);
                return true;
            }

            if ($normalized === '2' || str_contains(mb_strtolower($body, 'UTF-8'), 'doktor') || str_contains(mb_strtolower($body, 'UTF-8'), 'ağrı') || str_contains(mb_strtolower($body, 'UTF-8'), 'kanama') || str_contains(mb_strtolower($body, 'UTF-8'), 'ateş')) {
                // EMERGENCY / CONSULTATION NEEDED
                $this->CI->follow_up_dispatches_model->record_response($latest_reaction_dispatch['id'], [
                    'reaction' => 'consult_needed',
                    'choice' => 2,
                    'urgent' => true,
                    'raw' => $body,
                ]);

                // Create high-priority notification for clinic staff / doctor
                $this->raise_doctor_urgent_alert($matched_user, $latest_reaction_dispatch, $body);

                $reply = "ACİL BİLDİRİMİNİZ ALINDI. Durum hekiminize iletildi, klinik ekibimiz en kısa sürede sizi arayacaktır. Acil durumlarda lütfen kliniğimizi doğrudan arayınız.";
                $this->send_inbound_acknowledgment($from, $reply, $matched_user);
                return true;
            }
        }

        // 4. NPS & Review Score Handling (1-5)
        $latest_nps_dispatch = $this->CI->follow_up_dispatches_model->get_latest_for_customer(
            $customer_id,
            'review_request',
            172800 // last 2 days
        );

        if ($latest_nps_dispatch) {
            // Check for numeric rating 1 to 5
            if (preg_match('/^([1-5])\b/', $normalized, $matches)) {
                $score = (int) $matches[1];
                $this->CI->follow_up_dispatches_model->record_response($latest_nps_dispatch['id'], [
                    'nps_score' => $score,
                    'raw' => $body,
                ]);

                if ($score === 5) {
                    // 5-Star: Send Google Maps review invitation
                    $payload = $latest_nps_dispatch['payload_decoded'] ?? [];
                    $maps_url = $payload['google_maps_review_url'] ?? setting('google_maps_review_url');
                    if (empty($maps_url)) {
                        $maps_url = site_url('review/' . ($latest_nps_dispatch['booking_id'] ?? ''));
                    }

                    $reply = "Harika bir deneyim yaşamanıza çok sevindik! 🌟 Deneyiminizi Google Haritalar'da da paylaşarak bize destek olmak ister misiniz?\n" . $maps_url;
                    $this->send_inbound_acknowledgment($from, $reply, $matched_user);
                } elseif ($score <= 3) {
                    // Low rating: Apologize and alert management
                    $reply = "Beklentilerinizi karşılayamadığımız için üzgünüz. Geri bildiriminizi işletme yöneticimize ilettik, en kısa sürede sizinle iletişime geçeceğiz.";
                    $this->send_inbound_acknowledgment($from, $reply, $matched_user);
                    $this->log_manager_complaint_alert($matched_user, $latest_nps_dispatch, $score, $body);
                } else {
                    $reply = "Değerlendirmeniz için çok teşekkür ederiz! Bir sonraki ziyaretinizde görüşmek üzere.";
                    $this->send_inbound_acknowledgment($from, $reply, $matched_user);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Quiet Hours Rule (KVKK / İleti Güvenliği):
     * Messages cannot be sent between 21:00 (9 PM) and 09:00 (9 AM).
     * If target time falls into this window, it is automatically postponed to next morning at 09:30:00.
     *
     * @param DateTime $target Target scheduled datetime.
     * @param string $timezone Local timezone of tenant.
     * @return DateTime Adjusted datetime.
     */
    public function apply_quiet_hours(DateTime $target, string $timezone = 'Europe/Istanbul'): DateTime
    {
        $dt = clone $target;
        $dt->setTimezone(new DateTimeZone($timezone));

        $hour = (int) $dt->format('G');

        if ($hour >= 21) {
            // Gece 21:00 - 23:59: ertesi gün sabah 09:30'a ötele
            $dt->modify('+1 day');
            $dt->setTime(9, 30, 0);
        } elseif ($hour < 9) {
            // Gece 00:00 - 08:59: aynı gün sabah 09:30'a ötele
            $dt->setTime(9, 30, 0);
        }

        return $dt;
    }

    /**
     * Parse human interval string (e.g. '24 hours', '21 days', '45 minutes') to seconds.
     */
    public function parse_interval_seconds(string $interval_str): int
    {
        $interval_str = trim(strtolower($interval_str));

        if ($interval_str === '0 minutes' || $interval_str === '0' || $interval_str === 'now') {
            return 0;
        }

        if (preg_match('/^(\d+)\s*(minute|minutes|dk|dakika)s?$/i', $interval_str, $m)) {
            return (int) $m[1] * 60;
        }
        if (preg_match('/^(\d+)\s*(hour|hours|saat)s?$/i', $interval_str, $m)) {
            return (int) $m[1] * 3600;
        }
        if (preg_match('/^(\d+)\s*(day|days|gun|gün)s?$/i', $interval_str, $m)) {
            return (int) $m[1] * 86400;
        }
        if (preg_match('/^(\d+)\s*(week|weeks|hafta)s?$/i', $interval_str, $m)) {
            return (int) $m[1] * 7 * 86400;
        }
        if (preg_match('/^(\d+)\s*(month|months|ay)s?$/i', $interval_str, $m)) {
            return (int) $m[1] * 30 * 86400;
        }

        // Fallback using DateInterval
        try {
            $future = new DateTime('now');
            $future->modify('+' . $interval_str);
            return max(0, $future->getTimestamp() - time());
        } catch (Throwable) {
            return 3600; // 1 hour safe default
        }
    }

    /**
     * Resolve industry family for blueprint code.
     */
    public function resolve_industry_family(string $blueprint_type): string
    {
        $map = [
            'dentist' => 'health_clinical',
            'doctor_clinic' => 'health_clinical',
            'psychology_dietitian_clinic' => 'health_clinical',

            'beauty_salon' => 'beauty_wellness',
            'nail_studio' => 'beauty_wellness',
            'massage_spa' => 'beauty_wellness',
            'barber' => 'beauty_wellness',

            'auto_service_detailing' => 'automotive',
            'car_wash' => 'automotive',

            'experience_escape_room' => 'education_experience',
            'workshop' => 'education_experience',
            'education' => 'education_experience',

            'pt_training' => 'sports_fitness',
            'pilates_studio' => 'sports_fitness',
            'sports_court' => 'sports_fitness',
            'gym' => 'sports_fitness',

            'restaurant' => 'hospitality_food',
            'hotel' => 'hospitality_food',

            'law_firm' => 'professional',
            'consulting_agency' => 'professional',
        ];

        return $map[$blueprint_type] ?? 'beauty_wellness';
    }

    /**
     * Build merged payload context for dynamic template rendering.
     */
    private function build_payload_context(array $appointment, array $customer, array $rule, array $extra = []): array
    {
        $service = !empty($appointment['id_services'])
            ? $this->CI->db->get_where('services', ['id' => $appointment['id_services']])->row_array()
            : null;

        $provider = !empty($appointment['id_users_provider'])
            ? $this->CI->db->get_where('users', ['id' => $appointment['id_users_provider']])->row_array()
            : null;

        $company_name = setting('company_name') ?: 'BooKi';
        $google_maps_review_url = setting('google_maps_review_url') ?: ('https://maps.google.com/?q=' . urlencode($company_name));
        $booking_url = site_url('booking');
        $portal_url = site_url('portal');

        $customer_name = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        $provider_name = $provider ? trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? '')) : 'Uzman';
        $service_name = $service['name'] ?? 'Hizmet';

        $schema = [];
        if (!empty($rule['dynamic_payload_schema'])) {
            $schema = is_string($rule['dynamic_payload_schema'])
                ? (json_decode($rule['dynamic_payload_schema'], true) ?: [])
                : $rule['dynamic_payload_schema'];
        }

        $base = [
            'booking_id' => $appointment['id'],
            'customer_name' => $customer_name,
            'customer_phone' => $customer['phone_number'] ?? '',
            'provider_name' => $provider_name,
            'service_name' => $service_name,
            'company_name' => $company_name,
            'booking_url' => $booking_url,
            'portal_url' => $portal_url,
            'google_maps_review_url' => $google_maps_review_url,
            'report_url' => site_url('reports/download/' . $appointment['id']),
            'warranty_url' => site_url('portal/warranty/' . $appointment['id']),
            'photo_url' => site_url('portal/media/' . $appointment['id']),
            'pickup_code' => strtoupper(substr(md5($appointment['id'] . 'pickup'), 0, 6)),
            'artwork_name' => $extra['artwork_name'] ?? 'Seramik Eseriniz',
            'vehicle_plate' => $extra['vehicle_plate'] ?? ($appointment['notes'] ?? 'Aracınız'),
            'remaining_credits' => $extra['remaining_credits'] ?? '1',
            'total_credits' => $extra['total_credits'] ?? '10',
            'escape_time_minutes' => $extra['escape_time_minutes'] ?? '47',
            'resource_name' => $extra['resource_name'] ?? 'Halı Saha / Kort',
            'payment_url' => site_url('portal/packages'),
            'nps_url' => site_url('review/' . $appointment['id']),
        ];

        return array_merge($schema, $base, $extra);
    }

    /**
     * Render message body text with dynamic placeholder replacements.
     */
    private function render_message_text(?array $rule, array $payload, array $customer): string
    {
        // Check for service-level message override first
        $service_message_override = $payload['_service_message_override'] ?? null;
        if (!empty($service_message_override)) {
            $template = $service_message_override;
        } else {
            $schema = !empty($rule['dynamic_payload_schema'])
                ? (is_string($rule['dynamic_payload_schema']) ? json_decode($rule['dynamic_payload_schema'], true) : $rule['dynamic_payload_schema'])
                : [];

            $template = $schema['mesaj'] ?? ($schema['soru'] ?? null);
        }

        if (!$template) {
            // Built-in templates per rule type
            $rule_type = $rule['rule_type'] ?? 'review_request';

            switch ($rule_type) {
                case 'reaction_check':
                    $template = "Merhaba {{customer_name}}, geçmiş olsun! {{service_name}} işlemi sonrasında zonklama, beklenmeyen kanama veya yüksek ateş var mı?\n(1: İyiyim, 2: Doktoruma Danışmak İstiyorum)";
                    break;
                case 'review_request':
                    $template = "Merhaba {{customer_name}}, bugünkü bakımınızdan memnun kaldınız mı? 1-5 puan arası değerlendirebilirsiniz: 🌟\n{{company_name}}";
                    break;
                case 'aftercare':
                    $template = "Merhaba {{customer_name}},\n{{talimat}}\nSağlıklı günler dileriz! - {{company_name}}";
                    break;
                case 'retention_rebook':
                    $template = "Merhaba {{customer_name}},\n{{service_name}} bakım vaktiniz geldi. {{provider_name}} için boş randevu slotlarını görmek için tıklayın:\n{{booking_url}}";
                    break;
                case 'diet_form':
                    $template = "Merhaba {{customer_name}}, seans sonrası haftalık beslenme günlüğünüzü doldurmayı unutmayın:\n{{form_link}}";
                    break;
                case 'routine_check':
                    $template = "Merhaba {{customer_name}}, rutin 6 aylık sağlık kontrolü zamanınız geldi. Uygun saatleri seçmek için:\n{{booking_url}}";
                    break;
                case 'asset_delivery':
                    $template = "Merhaba {{customer_name}}, dijital belgeniz hazırlandı:\n{{rapor_link}}";
                    break;
                default:
                    $template = "Merhaba {{customer_name}}, {{company_name}} olarak size sağlıklı günler dileriz!";
                    break;
            }
        }

        // Replace placeholders
        foreach ($payload as $key => $val) {
            if (is_scalar($val)) {
                $template = str_replace('{{' . $key . '}}', (string) $val, $template);
            }
        }

        return $template;
    }

    /**
     * Dispatch WhatsApp message to recipient.
     */
    private function send_whatsapp_message(array $user, string $text, ?array $rule, array $payload): void
    {
        $phone = $user['phone_number'] ?? '';
        if (empty($phone)) {
            return;
        }

        $settings = $this->CI->messaging_settings_model->get_settings();
        $mode = $settings['whatsapp_mode'] ?? 'official';

        if ($mode === 'official' && !empty($settings['whatsapp_phone_number_id']) && !empty($settings['whatsapp_access_token'])) {
            $client = new Whatsapp_client(
                $settings['whatsapp_phone_number_id'],
                $settings['whatsapp_access_token'],
                $settings['whatsapp_webhook_verify_token'] ?? ''
            );
            $client->send_text($phone, $text);
        } else {
            // Unofficial bridge or Notifications library fallback
            $this->CI->notifications->send_whatsapp($user, $text);
        }

        // Log outgoing message in whatsapp_messages table
        $this->CI->whatsapp_messages_model->save([
            'id_users' => $user['id'] ?? null,
            'wa_id' => preg_replace('/\D+/', '', $phone),
            'direction' => 'out',
            'message' => $text,
            'status' => 'sent',
        ]);
    }

    /**
     * Dispatch SMS message fallback.
     */
    private function send_sms_message(array $user, string $text): void
    {
        $this->CI->notifications->send_sms($user, $text);
    }

    /**
     * Send instant acknowledgment reply back to incoming customer WhatsApp message.
     */
    private function send_inbound_acknowledgment(string $from, string $reply, ?array $matched_user): void
    {
        $settings = $this->CI->messaging_settings_model->get_settings();
        $mode = $settings['whatsapp_mode'] ?? 'official';

        if ($mode === 'official' && !empty($settings['whatsapp_phone_number_id']) && !empty($settings['whatsapp_access_token'])) {
            $client = new Whatsapp_client(
                $settings['whatsapp_phone_number_id'],
                $settings['whatsapp_access_token'],
                $settings['whatsapp_webhook_verify_token'] ?? ''
            );
            $client->send_text($from, $reply);
        } elseif (!empty($matched_user)) {
            $this->CI->notifications->send_whatsapp($matched_user, $reply);
        }

        $this->CI->whatsapp_messages_model->save([
            'id_users' => $matched_user['id'] ?? null,
            'wa_id' => $from,
            'direction' => 'out',
            'message' => $reply,
            'status' => 'sent',
        ]);
    }

    /**
     * Raise high-priority urgent alert for clinic doctor and staff when patient reports complications.
     */
    private function raise_doctor_urgent_alert(array $customer, array $dispatch, string $message): void
    {
        $name = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        $alert_text = "[ACİL REAKSİYON] Hasta: {$name} operasyon sonrası acil hekim danışması talep etti. Yanıt: {$message}";

        log_message('error', $alert_text);

        // Save as priority note or message
        $this->CI->whatsapp_messages_model->save([
            'id_users' => $customer['id'],
            'wa_id' => $customer['phone_number'] ?? '',
            'direction' => 'in',
            'message' => $alert_text,
            'status' => 'urgent',
        ]);
    }

    /**
     * Log complaint alert for branch manager when NPS <= 3.
     */
    private function log_manager_complaint_alert(array $customer, array $dispatch, int $score, string $message): void
    {
        $name = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        $alert_text = "[DÜŞÜK NPS GERİ BİLDİRİM] Müşteri: {$name}, Puan: {$score}/5. Geri bildirim: {$message}";

        log_message('warning', $alert_text);
    }

    /**
     * Resolve active tenant identifier.
     */
    private function resolve_tenant_id(): string
    {
        if (function_exists('tenant_context')) {
            $tc = tenant_context();
            if (is_array($tc) && !empty($tc['id'])) {
                return (string) $tc['id'];
            }
        }
        return 'default';
    }

    /**
     * Resolve tenant company timezone.
     */
    private function get_company_timezone(): string
    {
        $tz = setting('company_timezone');
        return !empty($tz) ? $tz : 'Europe/Istanbul';
    }
}
