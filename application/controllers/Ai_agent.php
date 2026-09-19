<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - AI Asistan admin controller (Dalga 4, 2026-09-12).
 *
 * Thin controller: conversation state lives in the PHP session, all the
 * actual chat/tool-calling logic is in Ai_agent_client. This file is only
 * responsible for auth/permission gating, the pending-change approval
 * workflow (the one place customer data actually gets mutated), and
 * rendering the page.
 *
 * @package Controllers
 */
class Ai_agent extends EA_Controller
{
    private const SESSION_KEY = 'ai_agent_history';

    private const MAX_HISTORY_MESSAGES = 20;

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

        html_vars([
            'page_title' => 'AI Asistan',
            'active_menu' => PRIV_AI_AGENT,
            'history' => $this->session->userdata(self::SESSION_KEY) ?: [],
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
     * Conversation history is kept server-side in the PHP session (not DB - see
     * migration 137 doc comment) so a page refresh doesn't lose context, but it's
     * not shared across devices/staff and is capped to avoid unbounded growth.
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

            $history = $this->session->userdata(self::SESSION_KEY) ?: [];
            $history[] = ['role' => 'user', 'content' => $user_message];

            $result = $this->ai_agent_client->chat($history);

            if (!empty($result['turn_messages'])) {
                foreach ($result['turn_messages'] as $tm) {
                    $history[] = $tm;
                }
            } else {
                $history[] = ['role' => 'assistant', 'content' => $result['reply']];
            }

            // Cap history length (keep it recent, not endless).
            if (count($history) > 30) {
                $history = array_slice($history, -30);
            }

            $this->session->set_userdata(self::SESSION_KEY, $history);

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
     * Clear the conversation (start fresh) without touching pending changes.
     */
    public function reset(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_AI_AGENT)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            $this->session->unset_userdata(self::SESSION_KEY);

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

                } elseif ($action === 'cancel') {
                    $appointment_id = (int) ($change['target_id'] ?: ($payload['appointment_id'] ?? 0));
                    if ($appointment_id) {
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

                            $appt['start_datetime'] = $new_start;
                            $appt['end_datetime'] = $new_end;
                            if (!empty($payload['new_provider_id'])) {
                                $appt['id_users_provider'] = (int) $payload['new_provider_id'];
                            }
                            if (!empty($payload['reason'])) {
                                $appt['notes'] = trim(($appt['notes'] ?? '') . "\n[AI Değişiklik]: " . $payload['reason']);
                            }

                            $this->appointments_model->save($appt);
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
                            $p_id = $provider_id ?? ($appt['id_users_provider'] ?? 0);
                            $provider = $p_id ? $this->providers_model->find((int) $p_id) : null;
                            $p_name = $provider ? trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? '')) : 'Uzman Personelimiz';

                            $msg_text = $this->channel_templates->render($template_key, [
                                'customer_name' => $payload['customer_name'] ?? ($customer['first_name'] ?? 'Değerli Misafirimiz'),
                                'service_name' => $payload['service_name'] ?? ($service['name'] ?? 'Hizmet'),
                                'start_datetime' => $payload['start_datetime'] ?? ($payload['new_start_datetime'] ?? ($appt['start_datetime'] ?? null)),
                                'end_datetime' => $payload['end_datetime'] ?? ($new_end ?? ($appt['end_datetime'] ?? null)),
                                'provider_name' => $p_name,
                            ]);

                            if ($channel === 'telegram') {
                                $this->load->library('telegram_client');
                                $this->telegram_client->send_message($sender_id, $msg_text);
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
