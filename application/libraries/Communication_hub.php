<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Communication Hub (Dalga 3 / Faz 3.1, 2026-09-08).
 *
 * Event-driven, tenant-configurable messaging engine. Publishers across the app
 * raise events (appointment_created / appointment_completed / appointment_cancelled);
 * the hub looks up every enabled rule for that event in the tenant's own
 * `communication_rules` table, resolves the target recipients, renders the rule's
 * subject/message template ({{placeholder}} syntax) and hands each recipient+channel
 * pair to the existing Notification channel senders (send_sms / send_whatsapp /
 * send_telegram / send_generic_email).
 *
 * Contracts:
 *  - Every channel send is best-effort and independently gated by that channel's
 *    own configuration/recipient fields. A rule may reference a channel the tenant
 *    hasn't configured - it simply no-ops until configured.
 *  - Channel sends go through the queue (user_id + generated text only - no raw PII
 *    like phone numbers or email addresses in job payloads). Each queued handler
 *    re-fetches the recipient at send time.
 *  - publish() swallows and logs every Throwable: the hub must NEVER break a
 *    booking, checkout, or cancellation request it is hooked into.
 *
 * Rules are managed via Console::communication_rules() / communication_rule_set()
 * / communication_rule_template().
 *
 * @package Libraries
 */
class Communication_hub
{
    public const EVENTS = ['appointment_created', 'appointment_completed', 'appointment_cancelled'];

    public const RECIPIENTS = ['customer', 'provider', 'admin', 'secretary'];

    public const CHANNELS = ['email', 'sms', 'whatsapp', 'telegram'];

    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * Communication_hub constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('admins_model');
        $this->CI->load->model('providers_model');
        $this->CI->load->model('secretaries_model');
    }

    /**
     * Publish a communication event. Safe to call from any request path - failures are
     * logged, never thrown back at the caller.
     *
     * @param string $event One of self::EVENTS.
     * @param array $ctx Context for template rendering and recipient resolution:
     *   appointment, service, provider, customer (arrays), settings (company details),
     *   plus optional cancellation_reason for the cancelled event.
     */
    public function publish(string $event, array $ctx): void
    {
        if (!in_array($event, self::EVENTS, true)) {
            log_message('warning', 'Communication_hub::publish() - Unknown event: ' . $event);
            return;
        }

        try {
            if (!$this->CI->db->table_exists('communication_rules')) {
                return;
            }

            $rules = $this->CI->db
                ->where('event', $event)
                ->where('enabled', 1)
                ->order_by('id', 'asc')
                ->get('communication_rules')
                ->result_array();

            foreach ($rules as $rule) {
                $this->dispatch_rule($rule, $ctx);
            }
        } catch (Throwable $e) {
            log_message('error', 'Communication_hub::publish(' . $event . ') failed: ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());
        }
    }

    /**
     * Public entry used by the Automation Engine (Faz 3.2): resolve a recipient group and send each
     * resolved recipient a pre-rendered message/subject over the given channels. Same channel gates
     * and queue behavior as rule dispatch. Best-effort - always returns, never throws.
     *
     * @param string $recipient_key One of self::RECIPIENTS.
     * @param array $ctx Event context (see publish()).
     * @param string $subject Pre-rendered subject (email only).
     * @param string $text Pre-rendered message body.
     * @param string[] $channels Channel names.
     */
    public function deliver(string $recipient_key, array $ctx, string $subject, string $text, array $channels): void
    {
        try {
            if (!in_array($recipient_key, self::RECIPIENTS, true)) {
                return;
            }

            $recipients = $this->resolve_recipients($recipient_key, $ctx);

            foreach ($recipients as $recipient) {
                foreach (array_unique($channels) as $channel) {
                    if (!in_array($channel, self::CHANNELS, true)) {
                        continue;
                    }

                    $this->send_channel_payload($channel, $recipient, $subject, $text);
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'Communication_hub::deliver(' . $recipient_key . ') failed: ' . $e->getMessage());
        }
    }

    /**
     * Dispatch a single rule: resolve recipients, then send each recipient the rendered
     * template over every channel the rule lists.
     *
     * @param array $rule A `communication_rules` row.
     * @param array $ctx Event context (see publish()).
     */
    protected function dispatch_rule(array $rule, array $ctx): void
    {
        $recipients = $this->resolve_recipients((string) $rule['recipient'], $ctx);

        if ($recipients === []) {
            return;
        }

        $channels = array_values(array_filter(array_map('trim', explode(',', (string) $rule['channel']))));

        foreach ($recipients as $recipient) {
            foreach ($channels as $channel) {
                if (!in_array($channel, self::CHANNELS, true)) {
                    continue;
                }

                $this->send_channel(
                    $channel,
                    $recipient,
                    $this->render_subject($rule, $ctx),
                    $this->render_message($rule, $ctx),
                );
            }
        }
    }

    /**
     * Resolve the recipient rows for a rule's recipient group.
     *
     * @param string $recipient One of self::RECIPIENTS.
     * @param array $ctx Event context.
     *
     * @return array[] Each entry additionally carries '_recipient_type' so the queued
     *   handlers can re-fetch the correct role table at send time.
     */
    public function resolve_recipients(string $recipient, array $ctx): array
    {
        switch ($recipient) {
            case 'customer':
                if (!empty($ctx['customer']) && is_array($ctx['customer'])) {
                    $row = $ctx['customer'];
                    $row['_recipient_type'] = 'customer';

                    return [$row];
                }

                return [];

            case 'provider':
                if (!empty($ctx['provider']) && is_array($ctx['provider'])) {
                    $row = $ctx['provider'];
                    $row['_recipient_type'] = 'provider';

                    return [$row];
                }

                return [];

            case 'admin':
                $admins = [];

                foreach ($this->CI->admins_model->get() as $admin) {
                    if (($admin['settings']['notifications'] ?? '0') === '1') {
                        $admin['_recipient_type'] = 'admin';
                        $admins[] = $admin;
                    }
                }

                return $admins;

            case 'secretary':
                $provider_id = (int) ($ctx['appointment']['id_users_provider'] ?? 0);
                $secretaries = [];

                foreach ($this->CI->secretaries_model->get() as $secretary) {
                    if (($secretary['settings']['notifications'] ?? '0') !== '1') {
                        continue;
                    }

                    if (!empty($secretary['providers']) && !in_array($provider_id, $secretary['providers'])) {
                        continue;
                    }

                    $secretary['_recipient_type'] = 'secretary';
                    $secretaries[] = $secretary;
                }

                return $secretaries;

            default:
                return [];
        }
    }

    /**
     * Send one recipient/channel pair.
     *
     * @param string $channel One of self::CHANNELS.
     * @param array $recipient Recipient row (with '_recipient_type').
     * @param string $subject Pre-rendered subject (email only).
     * @param string $text Pre-rendered message body.
     */
    protected function send_channel(string $channel, array $recipient, string $subject, string $text): void
    {
        try {
            $this->CI->load->library('notifications');

            switch ($channel) {
                case 'sms':
                    $this->CI->notifications->send_sms($recipient, $text);
                    break;

                case 'whatsapp':
                    $this->CI->notifications->send_whatsapp($recipient, $text);
                    break;

                case 'telegram':
                    $this->CI->notifications->send_telegram($recipient, $text);
                    break;

                case 'email':
                    $this->CI->notifications->send_generic_email($recipient, $subject, $text);
                    break;
            }
        } catch (Throwable $e) {
            log_message(
                'error',
                'Communication_hub::send_channel(' . $channel . ') failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Render the message body (per-rule template or the built-in default; then
     * {{placeholder}} substitution).
     */
    public function render_message(array $rule, array $ctx): string
    {
        $template = trim((string) ($rule['message'] ?? ''));

        if ($template === '') {
            $template = $this->default_message((string) $rule['event']);
        }

        return strtr($template, $this->build_placeholders($ctx));
    }

    /**
     * Render the email subject (per-rule template or the built-in default).
     */
    public function render_subject(array $rule, array $ctx): string
    {
        $template = trim((string) ($rule['subject'] ?? ''));

        if ($template === '') {
            $template = $this->default_subject((string) $rule['event']);
        }

        return strtr($template, $this->build_placeholders($ctx));
    }

    /**
     * Flat {{placeholder}} map shared by every template render.
     */
    protected function build_placeholders(array $ctx): array
    {
        $appointment = $ctx['appointment'] ?? [];
        $service = $ctx['service'] ?? [];
        $provider = $ctx['provider'] ?? [];
        $customer = $ctx['customer'] ?? [];
        $settings = $ctx['settings'] ?? [];

        $start = (string) ($appointment['start_datetime'] ?? '');

        return [
            'service_name' => (string) ($service['name'] ?? ''),
            'provider_name' => trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? '')),
            'customer_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
            'start_datetime' => $start !== '' ? date('d.m.Y H:i', strtotime($start)) : '',
            'appointment_id' => (string) ($appointment['id'] ?? ''),
            'company_name' => (string) ($settings['company_name'] ?? ''),
            'reason' => (string) ($ctx['cancellation_reason'] ?? ''),
            'appointment_count' => (string) ($ctx['appointment_count'] ?? ''),
            'review_link' => (string) ($ctx['review_link'] ?? ''),
        ];
    }

    /**
     * Built-in per-event default message (used when a rule has no custom template).
     */
    protected function default_message(string $event): string
    {
        return match ($event) {
            'appointment_created' => 'Merhaba, {service_name} için {start_datetime} tarihli randevunuz oluşturuldu. {company_name}',
            'appointment_completed' => 'Merhaba, {service_name} seansınız {provider_name} ile {start_datetime} tarihinde tamamlandı. {company_name}',
            'appointment_cancelled' => 'Merhaba, {service_name} için {start_datetime} tarihli randevunuz iptal edildi. {company_name}',
            default => '',
        };
    }

    /**
     * Built-in per-event default subject (used when a rule has no custom subject).
     */
    protected function default_subject(string $event): string
    {
        return match ($event) {
            'appointment_created' => 'Randevu Onaylandı',
            'appointment_completed' => 'Seans Tamamlandı',
            'appointment_cancelled' => 'Randevu İptal Edildi',
            default => 'Randevu Bilgilendirmesi',
        };
    }
}