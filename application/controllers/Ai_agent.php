<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - AI Asistan admin controller (Dalga 4, 2026-09-12).
 *
 * Conversation lifecycle is SESSION-BASED and PER-CUSTOMER (id_users): the
 * active conversation lives in the PHP session (fast, private to the logged-
 * in user's session). After self::IDLE_TIMEOUT_SECONDS of inactivity ONLY
 * this user's open conversation is "closed": it is archived as its OWN row in
 * ai_agent_conversations (with a per-conversation summary) and its raw
 * messages go to ai_agent_messages (migration 155). Each turn injects the
 * summaries of this user's most recent closed conversations as long-term
 * memory - never a single global blob. Tool-calling logic is in
 * Ai_agent_client. This file is only responsible for auth/permission gating,
 * the pending-change approval workflow (the one place customer data actually
 * gets mutated), and rendering the page.
 *
 * @package Controllers
 */
class Ai_agent extends App_Controller
{
    private const SESSION_KEY = 'ai_agent_history';

    /** Session key holding the unix timestamp of the last chat() request. */
    private const SESSION_LAST_ACTIVITY = 'ai_agent_last_activity';

    /** 5 dk hareketsizlik → aktif konuşma kapanır, özetlenip DB'ye yazılır. */
    private const IDLE_TIMEOUT_SECONDS = 300;

    /** Aktif (session) konuşmanın mesaj tavanı; aşılırsa konuşma kapatılıp yenisi başlar. */
    private const MAX_SESSION_MESSAGES = 40;

    public function __construct()
    {
        parent::__construct();

        require_plan_feature(PRIV_AI_AGENT);

        $this->load->library('ai_agent_client');
    }

    /**
     * Render the AI Asistan page (chat + pending approval queue).
     */
    public function index(): void
    {
        method('get');

        $user_id = session('user_id');
        if (!$user_id) {
            redirect('login');
            return;
        }

        if (cannot('view', PRIV_AI_AGENT)) {
            abort(403, 'Forbidden');
        }

        // The active conversation lives in the PHP session (see class docblock):
        // render it as-is. Closed/archived conversations live in the DB and are
        // injected into the model as rolling summaries, not rendered here.
        $visible = [];
        foreach ($this->session->userdata(self::SESSION_KEY) ?: [] as $msg) {
            if (($msg['role'] ?? '') === 'tool' || empty($msg['content'])) {
                continue;
            }
            $visible[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }

        html_vars([
            'page_title' => 'AI Asistan',
            'active_menu' => PRIV_AI_AGENT,
            'history' => $visible,
            'pending' => $this->get_pending_changes(),
        ]);

        script_vars([
            'routes' => [
                'chat' => site_url('ai_agent/chat'),
                'pending' => site_url('ai_agent/pending'),
                'approve' => site_url('ai_agent/approve'),
                'reject' => site_url('ai_agent/reject'),
                'reset' => site_url('ai_agent/reset'),
            ],
        ]);

        $this->load->view('pages/ai_agent');
    }

    /**
     * Send one user message, run the tool-calling loop, return the assistant's reply.
     *
     * Lifecycle: the ACTIVE conversation lives in the PHP session. On every
     * request the idle timer is checked - if the user was inactive for more
     * than self::IDLE_TIMEOUT_SECONDS, ONLY this user's open conversation is
     * closed (archived into its own ai_agent_conversations row + summarized)
     * and this message starts a fresh one. Long-term memory is per-customer:
     * the summaries of this user's most recent closed conversations (capped,
     * NOT the whole history in one blob) are injected into every turn.
     */
    public function chat(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_AI_AGENT)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('message', 'string');
            $user_message = trim((string) request('message'));

            if ($user_message === '') {
                throw new InvalidArgumentException('Mesaj boş olamaz.');
            }

            $user_id = (int) session('user_id');

            // ── 5 dk hareketsizlik kontrolü: SADECE bu kullanıcının açık
            //    konuşmasını kapat (diğer kullanıcılar etkilenmez).
            $last_activity = (int) ($this->session->userdata(self::SESSION_LAST_ACTIVITY) ?: 0);
            if ($last_activity > 0 && (time() - $last_activity) > self::IDLE_TIMEOUT_SECONDS) {
                $this->close_conversation($user_id);
            }

            // Long-term memory: per-customer, recent closed-conversation summaries.
            $this->load->model('ai_agent_conversations_model');
            $summaries = $this->ai_agent_conversations_model->get_recent_summaries($user_id);
            $summary = trim(implode("\n\n---\n\n", array_reverse($summaries)));

            // Active conversation from the PHP session (never client-supplied;
            // the session is the only source of truth for the open thread).
            $history = $this->session->userdata(self::SESSION_KEY) ?: [];
            $history[] = ['role' => 'user', 'content' => $user_message];

            $result = $this->ai_agent_client->chat($history, $summary !== '' ? $summary : null);

            if (!empty($result['turn_messages'])) {
                foreach ($result['turn_messages'] as $tm) {
                    $history[] = $tm;
                }
            } else {
                $history[] = ['role' => 'assistant', 'content' => $result['reply']];
            }

            if (count($history) > self::MAX_SESSION_MESSAGES) {
                // Keep the session thread bounded: archive+summarize the FULL
                // conversation now and let the next message start fresh.
                $this->session->set_userdata(self::SESSION_KEY, $history);
                $this->close_conversation($user_id);
            } else {
                $this->session->set_userdata(self::SESSION_KEY, $history);
                $this->session->set_userdata(self::SESSION_LAST_ACTIVITY, time());
            }

            json_response([
                'success' => true,
                'reply' => $result['reply'],
                'tool_calls' => $result['tool_calls'],
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Close THIS user's active (session) conversation: archive its raw messages
     * under its OWN new ai_agent_conversations row and store a per-conversation
     * summary on it, then clear the session scratchpad so the next message
     * starts a fresh conversation. Memory assembly (which summaries to inject)
     * happens per-turn in chat() via get_recent_summaries().
     */
    private function close_conversation(int $user_id): void
    {
        $history = $this->session->userdata(self::SESSION_KEY) ?: [];
        $this->session->unset_userdata(self::SESSION_KEY);
        $this->session->unset_userdata(self::SESSION_LAST_ACTIVITY);

        if (empty($history)) {
            return;
        }

        $this->load->model('ai_agent_conversations_model');

        // 1. A dedicated row per closed conversation (per-customer archive).
        $conversation_id = $this->ai_agent_conversations_model->create($user_id);

        // 2. Archive the raw closed conversation (audit trail; tool pairs stay intact).
        $this->ai_agent_conversations_model->append_messages($conversation_id, $history);

        // 3. Store THIS conversation's own summary on its row (LLM-compressed
        //    with a deterministic fallback, see summarize_history()).
        $summary = $this->ai_agent_client->summarize_history($history);
        $this->ai_agent_conversations_model->update_summary($conversation_id, $summary);
    }

    /**
     * "Sohbeti sıfırla" — closes THIS user's active session conversation
     * (archived into its own DB row + per-conversation summary, so nothing is
     * lost) and lets the next message start a fresh thread. Pending changes
     * are untouched.
     */
    public function reset(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_AI_AGENT)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            $this->close_conversation((int) session('user_id'));

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * List pending (not yet resolved) proposed changes.
     */
    public function pending(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_AI_AGENT)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            json_response(['success' => true, 'pending' => $this->get_pending_changes()]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Approve a pending change: apply it via Customers_model::save() (which
     * re-validates like every other write path) then mark it resolved.
     */
    public function approve(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_AI_AGENT)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('id', 'integer');
            $id = (int) request('id');

            $change = $this->db->get_where('ai_agent_pending_changes', ['id' => $id, 'status' => 'pending'])->row_array();

            if (!$change) {
                throw new InvalidArgumentException('Bekleyen değişiklik bulunamadı.');
            }

            if ($change['target_table'] === 'users') {
                $this->load->model('customers_model');

                $customer = $this->customers_model->find((int) $change['target_id']);
                $changes = json_decode((string) $change['changes'], true) ?: [];

                $this->customers_model->save(array_merge($customer, $changes));
            } elseif ($change['target_table'] === 'appointments') {
                $this->load->model('appointments_model');
                $this->load->model('customers_model');
                $this->load->model('services_model');
                $this->load->model('providers_model');

                $payload = json_decode((string) $change['changes'], true) ?: [];
                $action = $payload['action'] ?? 'create';

                if ($action === 'create') {
                    // 1. Resolve or create customer
                    $customer_id = null;
                    $phone = trim((string) ($payload['customer_phone'] ?? ''));
                    $email = trim((string) ($payload['customer_email'] ?? ''));
                    $full_name = trim((string) ($payload['customer_name'] ?? ''));

                    if (!empty($phone)) {
                        $existing = $this->customers_model->search($phone);
                        if (!empty($existing)) {
                            $customer_id = (int) $existing[0]['id'];
                        }
                    }

                    if (!$customer_id && !empty($payload['customer_id'])) {
                        $customer_id = (int) $payload['customer_id'];
                    }

                    if (!$customer_id) {
                        $parts = explode(' ', $full_name, 2);
                        $first_name = !empty($parts[0]) ? $parts[0] : 'Misafir';
                        $last_name = !empty($parts[1]) ? $parts[1] : 'Müşteri';

                        $customer_id = $this->customers_model->save([
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                            'phone_number' => $phone ?: '05000000000',
                            'email' => $email ?: (bin2hex(random_bytes(4)) . '@kibusiness.co'),
                        ]);
                    }

                    // 2. Resolve Service
                    $service_id = (int) ($payload['service_id'] ?? 0);
                    if (!$service_id && !empty($payload['service_name'])) {
                        $srv = $this->db->like('name', $payload['service_name'])->get('services')->row_array();
                        if ($srv) {
                            $service_id = (int) $srv['id'];
                        }
                    }
                    if (!$service_id) {
                        $services = $this->services_model->get_available_services();
                        if (!empty($services)) {
                            $service_id = (int) $services[0]['id'];
                        }
                    }

                    $service = $this->services_model->find($service_id);
                    $duration = !empty($service['duration']) ? (int) $service['duration'] : 60;

                    // 3. Resolve Provider
                    $provider_id = (int) ($payload['provider_id'] ?? 0);
                    if (!$provider_id) {
                        $sp = $this->db->get_where('services_providers', ['id_services' => $service_id])->row_array();
                        if ($sp) {
                            $provider_id = (int) $sp['id_users'];
                        } else {
                            $providers = $this->providers_model->get_available_providers();
                            if (!empty($providers)) {
                                $provider_id = (int) $providers[0]['id'];
                            }
                        }
                    }

                    // 4. Calculate start and end datetime
                    $start_datetime = !empty($payload['start_datetime']) ? date('Y-m-d H:i:s', strtotime($payload['start_datetime'])) : date('Y-m-d H:i:s');
                    $end_datetime = !empty($payload['end_datetime'])
                        ? date('Y-m-d H:i:s', strtotime($payload['end_datetime']))
                        : date('Y-m-d H:i:s', strtotime($start_datetime) + ($duration * 60));

                    $appointment = [
                        'start_datetime' => $start_datetime,
                        'end_datetime' => $end_datetime,
                        'id_services' => $service_id,
                        'id_users_provider' => $provider_id,
                        'id_users_customer' => $customer_id,
                        'notes' => $payload['notes'] ?? 'AI Asistan randevu talebi (Yönetici Onaylı)',
                        'is_unavailability' => false,
                    ];

                    $created_id = $this->appointments_model->save($appointment);
                    $payload['result_appointment_id'] = $created_id;
                    $this->db->update('ai_agent_pending_changes', [
                        'target_id' => $created_id,
                        'changes' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    ], ['id' => $id]);

                    $notif_customer_name = !empty($payload['customer_name']) ? $payload['customer_name'] : ($customer ? trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) : 'Değerli Misafirimiz');
                    $notif_service_name = !empty($payload['service_name']) ? $payload['service_name'] : ($service ? $service['name'] : 'Hizmet');
                    $notif_start_datetime = $start_datetime;
                    $notif_end_datetime = $end_datetime;
                    $provider = $provider_id ? $this->providers_model->find((int) $provider_id) : null;
                    $notif_provider_name = $provider ? trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? '')) : 'Uzman Personelimiz';

                } elseif ($action === 'cancel') {
                    $appointment_id = (int) ($change['target_id'] ?: ($payload['appointment_id'] ?? 0));
                    if ($appointment_id) {
                        $appt = $this->appointments_model->find($appointment_id);
                        if ($appt) {
                            $customer = $this->customers_model->find((int) $appt['id_users_customer']);
                            $service = $this->services_model->find((int) $appt['id_services']);
                            $provider = $this->providers_model->find((int) $appt['id_users_provider']);

                            $notif_customer_name = !empty($payload['customer_name']) ? $payload['customer_name'] : ($customer ? trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) : 'Değerli Misafirimiz');
                            $notif_service_name = !empty($payload['service_name']) ? $payload['service_name'] : ($service ? $service['name'] : 'Hizmet');
                            $notif_start_datetime = !empty($payload['start_datetime']) ? $payload['start_datetime'] : $appt['start_datetime'];
                            $notif_end_datetime = !empty($payload['end_datetime']) ? $payload['end_datetime'] : $appt['end_datetime'];
                            $notif_provider_name = $provider ? trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? '')) : 'Uzman Personelimiz';
                        }
                        $this->appointments_model->delete($appointment_id);
                    }
                } elseif ($action === 'reschedule') {
                    $appointment_id = (int) ($change['target_id'] ?: ($payload['appointment_id'] ?? 0));
                    if ($appointment_id) {
                        $appt = $this->appointments_model->find($appointment_id);
                        if ($appt) {
                            $new_start = !empty($payload['new_start_datetime']) ? date('Y-m-d H:i:s', strtotime($payload['new_start_datetime'])) : $appt['start_datetime'];
                            $service = $this->services_model->find((int) $appt['id_services']);
                            $duration = !empty($service['duration']) ? (int) $service['duration'] : 60;
                            $new_end = date('Y-m-d H:i:s', strtotime($new_start) + ($duration * 60));

                            $customer = $this->customers_model->find((int) $appt['id_users_customer']);
                            $provider = $this->providers_model->find(!empty($payload['new_provider_id']) ? (int) $payload['new_provider_id'] : (int) $appt['id_users_provider']);

                            $appt['start_datetime'] = $new_start;
                            $appt['end_datetime'] = $new_end;
                            if (!empty($payload['new_provider_id'])) {
                                $appt['id_users_provider'] = (int) $payload['new_provider_id'];
                            }
                            if (!empty($payload['reason'])) {
                                $appt['notes'] = trim(($appt['notes'] ?? '') . "\n[AI Değişiklik]: " . $payload['reason']);
                            }

                            $this->appointments_model->save($appt);

                            $notif_customer_name = !empty($payload['customer_name']) ? $payload['customer_name'] : ($customer ? trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) : 'Değerli Misafirimiz');
                            $notif_service_name = !empty($payload['service_name']) ? $payload['service_name'] : ($service ? $service['name'] : 'Hizmet');
                            $notif_start_datetime = $new_start;
                            $notif_end_datetime = $new_end;
                            $notif_provider_name = $provider ? trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? '')) : 'Uzman Personelimiz';
                        }
                    }
                }
                // Dispatch automated notification to customer channel
                try {
                    $this->load->library('channel_templates');
                    $channel = $payload['channel'] ?? null;
                    $sender_id = $payload['sender_id'] ?? null;

                    if ($channel && $sender_id) {
                        $template_key = match ($action) {
                            'create' => 'appointment_approved',
                            'reschedule' => 'appointment_rescheduled',
                            'cancel' => 'appointment_cancelled',
                            default => null,
                        };

                        if ($template_key) {
                            $msg_text = $this->channel_templates->render($template_key, [
                                'customer_name' => $notif_customer_name ?? 'Değerli Misafirimiz',
                                'service_name' => $notif_service_name ?? 'Hizmet',
                                'start_datetime' => $notif_start_datetime ?? null,
                                'end_datetime' => $notif_end_datetime ?? null,
                                'provider_name' => $notif_provider_name ?? 'Uzman Personelimiz',
                            ]);

                            if ($channel === 'telegram') {
                                $this->load->library('telegram_client');
                                $this->telegram_client->send_message($sender_id, $msg_text);
                            } elseif ($channel === 'whatsapp') {
                                $this->load->model('messaging_settings_model');
                                $m_settings = $this->messaging_settings_model->get_settings();
                                $mode = $m_settings['whatsapp_mode'] ?? 'official';
                                if ($mode === 'unofficial') {
                                    $this->load->library('whatsapp_bridge');
                                    $bridge = new Whatsapp_bridge($m_settings['whatsapp_bridge_url'], $m_settings['whatsapp_bridge_secret']);
                                    $tenant_sub = function_exists('tenant_context') ? (tenant_context()['subdomain'] ?? 'default') : 'default';
                                    $bridge->send($tenant_sub, $sender_id, $msg_text);
                                } else {
                                    $this->load->library('whatsapp_client');
                                    $this->whatsapp_client->send_message($sender_id, $msg_text);
                                }
                            } elseif ($channel === 'instagram') {
                                $this->load->model('messaging_settings_model');
                                $m_settings = $this->messaging_settings_model->get_settings();
                                $access_token = $m_settings['instagram_access_token'] ?? null;
                                $page_id = $m_settings['instagram_account_id'] ?? 'me';
                                if (!empty($access_token)) {
                                    $url = "https://graph.facebook.com/v20.0/{$page_id}/messages";
                                    $ch = curl_init();
                                    curl_setopt_array($ch, [
                                        CURLOPT_URL => $url,
                                        CURLOPT_POST => true,
                                        CURLOPT_POSTFIELDS => json_encode([
                                            'recipient' => ['id' => $sender_id],
                                            'message' => ['text' => $msg_text],
                                        ]),
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_TIMEOUT => 15,
                                        CURLOPT_HTTPHEADER => [
                                            'Authorization: Bearer ' . $access_token,
                                            'Content-Type: application/json',
                                        ],
                                    ]);
                                    curl_exec($ch);
                                    curl_close($ch);
                                }
                            }
                        }
                    }
                } catch (\Throwable $ne) {
                    log_message('error', 'Ai_agent approval notification failed: ' . $ne->getMessage());
                }
            } else {
                throw new RuntimeException('Bilinmeyen hedef tablo: ' . $change['target_table']);
            }

            $this->db->update('ai_agent_pending_changes', [
                'status' => 'approved',
                'resolved_at' => date('Y-m-d H:i:s'),
                'resolved_by' => session('user_id'),
            ], ['id' => $id]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Reject a pending change without applying it.
     */
    public function reject(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_AI_AGENT)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('id', 'integer');
            $id = (int) request('id');

            $this->db->update('ai_agent_pending_changes', [
                'status' => 'rejected',
                'resolved_at' => date('Y-m-d H:i:s'),
                'resolved_by' => session('user_id'),
            ], ['id' => $id, 'status' => 'pending']);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    private function get_pending_changes(): array
    {
        return $this->db
            ->where('status', 'pending')
            ->order_by('created_at', 'desc')
            ->get('ai_agent_pending_changes')
            ->result_array();
    }
}
