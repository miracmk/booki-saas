<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Instagram Graph API Messaging Integration Controller.
 *
 * Provides:
 * 1. Admin panel settings & conversation review (/instagram).
 * 2. Meta Graph API Webhook endpoint (/instagram/webhook) supporting:
 *    - GET: Hub challenge verification (hub.mode, hub.verify_token).
 *    - POST: Incoming direct messages with customer matching & channel logging.
 * 3. Multi-channel AI Assistant integration:
 *    - Automated, read-only AI auto-reply when ai_reply_instagram_enabled is true.
 *    - Direct manual reply from staff panel (/instagram/reply).
 *
 * @package Controllers
 * ---------------------------------------------------------------------------- */

class Instagram extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('messaging_settings_model');
        $this->load->model('instagram_messages_model');
        $this->load->model('users_model');
    }

    /**
     * Render Instagram settings and recent message history.
     */
    public function index(): void
    {
        method('get');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        $settings = $this->messaging_settings_model->get_settings();
        $messages = $this->instagram_messages_model->get_recent(50);

        html_vars([
            'page_title' => 'Instagram Entegrasyonu',
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'instagram_configured' => !empty($settings['instagram_access_token']) && !empty($settings['instagram_account_id']),
            'instagram_account_id' => $settings['instagram_account_id'],
            'instagram_notifications_enabled' => filter_var(
                $settings['instagram_notifications_enabled'] ?? false,
                FILTER_VALIDATE_BOOLEAN,
            ),
            'ai_reply_instagram_enabled' => filter_var(
                $settings['ai_reply_instagram_enabled'] ?? false,
                FILTER_VALIDATE_BOOLEAN,
            ),
            'webhook_url' => site_url('instagram/webhook'),
            'webhook_verify_token_set' => !empty($settings['instagram_webhook_verify_token']),
            'messages' => $messages,
        ]);

        script_vars([
            'routes' => [
                'save_settings' => site_url('instagram/save_settings'),
                'reply' => site_url('instagram/reply'),
                'send_test' => site_url('instagram/send_test'),
            ],
        ]);

        $this->load->view('pages/instagram');
    }

    /**
     * Public webhook endpoint called by Meta Graph API.
     * Supports GET for webhook verification and POST for inbound messages.
     */
    public function webhook(): void
    {
        $method = $this->input->method();

        if ($method === 'get') {
            $this->webhook_verify();
            return;
        }

        if ($method === 'post') {
            $this->webhook_receive();
            return;
        }

        abort(405, 'Method Not Allowed');
    }

    /**
     * Handle Meta Graph API webhook subscription verification (GET).
     */
    private function webhook_verify(): void
    {
        try {
            $mode = request('hub_mode') ?: ($_GET['hub_mode'] ?? ($_GET['hub.mode'] ?? null));
            $token = request('hub_verify_token') ?: ($_GET['hub_verify_token'] ?? ($_GET['hub.verify_token'] ?? null));
            $challenge = request('hub_challenge') ?: ($_GET['hub_challenge'] ?? ($_GET['hub.challenge'] ?? null));

            if (!$mode || !$token || !$challenge) {
                abort(403, 'Forbidden');
            }

            $settings = $this->messaging_settings_model->get_settings();
            $expected_token = $settings['instagram_webhook_verify_token'] ?? null;

            if (empty($expected_token) || !hash_equals($expected_token, (string) $token)) {
                abort(403, 'Forbidden');
            }

            echo $challenge;
        } catch (Throwable $e) {
            log_message('error', 'Instagram::webhook_verify - ' . $e->getMessage());
            abort(403, 'Forbidden');
        }
    }

    /**
     * Handle incoming webhook POST from Meta Graph API.
     */
    private function webhook_receive(): void
    {
        try {
            $raw_input = file_get_contents('php://input');
            $payload = json_decode($raw_input, true) ?: [];

            if (empty($payload)) {
                response();
                return;
            }

            $settings = $this->messaging_settings_model->get_settings();

            // Verify Meta HMAC signature if app secret is configured
            $app_secret = $settings['meta_app_secret'] ?? ($settings['instagram_app_secret'] ?? (getenv('META_APP_SECRET') ?: ''));
            $signature_header = $this->input->get_request_header('X-Hub-Signature-256')
                ?? ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? null);

            if (!empty($app_secret)) {
                if (empty($signature_header)) {
                    log_message('error', 'Instagram::webhook_receive - Missing X-Hub-Signature-256');
                    abort(403, 'Missing Meta signature');
                }
                $expected = 'sha256=' . hash_hmac('sha256', $raw_input, $app_secret);
                if (!hash_equals($expected, (string) $signature_header)) {
                    log_message('error', 'Instagram::webhook_receive - Invalid X-Hub-Signature-256');
                    abort(403, 'Invalid Meta signature');
                }
            }

            // Extract messages from Meta Graph API webhook structure
            // entry[].messaging[]
            $entries = $payload['entry'] ?? [];
            foreach ($entries as $entry) {
                $messaging_events = $entry['messaging'] ?? [];
                foreach ($messaging_events as $event) {
                    $sender_id = (string) ($event['sender']['id'] ?? '');
                    $message = $event['message'] ?? null;

                    if ($sender_id === '' || empty($message)) {
                        continue;
                    }

                    // Skip echo messages (sent by the page itself)
                    if (!empty($message['is_echo'])) {
                        continue;
                    }

                    $body = (string) ($message['text'] ?? '[non-text message]');

                    // Match user by instagram_user_id
                    $matched_user = $this->match_user_by_ig_id($sender_id);

                    // Log inbound message
                    $this->instagram_messages_model->save([
                        'id_users' => $matched_user['id'] ?? null,
                        'instagram_user_id' => $sender_id,
                        'direction' => 'in',
                        'message' => $body,
                        'status' => 'received',
                    ]);

                    // Update last_contact_channel
                    if ($matched_user && ($matched_user['role_slug'] ?? '') === DB_SLUG_CUSTOMER) {
                        $this->db->update('users', ['last_contact_channel' => 'instagram'], ['id' => $matched_user['id']]);
                    }

                    // Automated AI Assistant reply if enabled
                    $ai_enabled = !empty($settings['ai_reply_instagram_enabled']) 
                        || (setting('ai_reply_instagram_enabled') !== '0');
                    if ($ai_enabled) {
                        $this->load->library('ai_channel_responder');
                        $ai_reply = $this->ai_channel_responder->respond('instagram', $sender_id, $body, $matched_user);

                        if (!empty($ai_reply)) {
                            $send_result = $this->send_instagram_message($sender_id, $ai_reply, $settings);

                            $this->instagram_messages_model->save([
                                'id_users' => $matched_user['id'] ?? null,
                                'instagram_user_id' => $sender_id,
                                'direction' => 'out',
                                'message' => $ai_reply,
                                'status' => $send_result['success'] ? 'sent' : 'failed',
                            ]);
                        }
                    }
                }
            }

            response();
        } catch (Throwable $e) {
            log_message('error', 'Instagram::webhook_receive - ' . $e->getMessage());
            response();
        }
    }

    /**
     * Send a manual reply from the staff panel.
     */
    public function reply(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('instagram_user_id', 'string');
            check('message', 'string');
            check('user_id', 'numeric|null');

            $ig_user_id = (string) request('instagram_user_id');
            $message = trim((string) request('message'));
            $user_id = request('user_id') !== null ? (int) request('user_id') : null;

            if ($message === '') {
                throw new InvalidArgumentException('Mesaj boş olamaz.');
            }

            $settings = $this->messaging_settings_model->get_settings();
            $result = $this->send_instagram_message($ig_user_id, $message, $settings);

            $this->instagram_messages_model->save([
                'id_users' => $user_id,
                'instagram_user_id' => $ig_user_id,
                'direction' => 'out',
                'message' => $message,
                'status' => $result['success'] ? 'sent' : 'failed',
            ]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Save Instagram integration settings.
     */
    public function save_settings(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('instagram_account_id', 'string|null');
            check('instagram_access_token', 'string|null');
            check('instagram_webhook_verify_token', 'string|null');
            check('instagram_notifications_enabled', 'bool|null');
            check('ai_reply_instagram_enabled', 'bool|null');

            $data = [
                'instagram_account_id' => trim((string) request('instagram_account_id', '')) ?: null,
                'instagram_access_token' => trim((string) request('instagram_access_token', '')) ?: null,
                'instagram_webhook_verify_token' => trim((string) request('instagram_webhook_verify_token', '')) ?: null,
                'instagram_notifications_enabled' => filter_var(
                    request('instagram_notifications_enabled', false),
                    FILTER_VALIDATE_BOOLEAN,
                ) ? 1 : 0,
                'ai_reply_instagram_enabled' => filter_var(
                    request('ai_reply_instagram_enabled', false),
                    FILTER_VALIDATE_BOOLEAN,
                ) ? 1 : 0,
            ];

            $this->messaging_settings_model->save_settings($data);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Dispatch an outbound Instagram message via Meta Graph API.
     * Note: In test/sandbox environments without live Meta App Review approval,
     * logs gracefully rather than failing hard.
     */
    private function send_instagram_message(string $recipient_id, string $text, array $settings): array
    {
        $access_token = $settings['instagram_access_token'] ?? null;
        $page_id = $settings['instagram_account_id'] ?? 'me';

        if (empty($access_token)) {
            return [
                'success' => false,
                'error' => 'Instagram access token is not configured.',
            ];
        }

        try {
            $url = "https://graph.facebook.com/v20.0/{$page_id}/messages";
            $payload = [
                'recipient' => ['id' => $recipient_id],
                'message' => ['text' => $text],
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $access_token,
                    'Content-Type: application/json',
                ],
            ]);

            $res = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code === 200) {
                return ['success' => true];
            }

            log_message('error', "Instagram Graph API send error (HTTP {$http_code}): " . $res);
            return [
                'success' => false,
                'error' => "HTTP {$http_code}: " . $res,
            ];
        } catch (Throwable $e) {
            log_message('error', 'Instagram send exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Match user by instagram_user_id.
     */
    private function match_user_by_ig_id(string $ig_id): ?array
    {
        if (!$this->db->field_exists('instagram_user_id', 'users')) {
            return null;
        }

        return $this->db
            ->select('users.*, roles.slug AS role_slug')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'left')
            ->where('users.instagram_user_id', $ig_id)
            ->limit(1)
            ->get()
            ->row_array() ?: null;
    }
}
