<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - WhatsApp Business Cloud API integration controller (2026-08-27).
 *
 * Admin-facing settings page plus the public webhook endpoint that Meta
 * calls for incoming messages and status updates.
 *
 * @package Controllers
 */

class Whatsapp extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('messaging_settings_model');
        $this->load->model('whatsapp_messages_model');
        $this->load->model('users_model');
        $this->load->library('whatsapp_client');
        $this->load->library('whatsapp_bridge');
    }

    /**
     * Render WhatsApp settings and recent messages.
     */
    public function index(): void
    {
        method('get');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        $settings = $this->messaging_settings_model->get_settings();

        $messages = $this->whatsapp_messages_model->get_recent(50);

        $mode = $settings['whatsapp_mode'] ?? 'official';

        html_vars([
            'page_title' => 'WhatsApp Entegrasyonu',
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'whatsapp_configured' => !empty($settings['whatsapp_phone_number_id']) && !empty($settings['whatsapp_access_token']),
            'whatsapp_business_phone_display' => $settings['whatsapp_business_phone_display'],
            'whatsapp_notifications_enabled' => filter_var(
                $settings['whatsapp_notifications_enabled'],
                FILTER_VALIDATE_BOOLEAN,
            ),
            'webhook_url' => site_url('whatsapp/webhook'),
            'webhook_verify_token_required' => !empty($settings['whatsapp_webhook_verify_token']),
            'whatsapp_mode' => $mode,
            'unofficial_status' => $settings['whatsapp_unofficial_status'] ?? 'disconnected',
            'unofficial_name' => $settings['whatsapp_unofficial_name'],
            'unofficial_consent_at' => $settings['whatsapp_unofficial_consent_at'],
            'bridge_url' => $settings['whatsapp_bridge_url'],
            'bridge_secret_set' => !empty($settings['whatsapp_bridge_secret']),
            'messages' => $messages,
        ]);

        script_vars([
            'mode' => $mode,
            'bridge_secret_set' => !empty($settings['whatsapp_bridge_secret']),
            'routes' => [
                'save_mode' => site_url('whatsapp/save_mode'),
                'save_bridge' => site_url('whatsapp/save_bridge'),
                'check_connection' => site_url('whatsapp/check_connection'),
                'send_test' => site_url('whatsapp/send_test'),
                'qr_start' => site_url('whatsapp/qr_start'),
                'qr_status' => site_url('whatsapp/qr_status'),
                'qr_logout' => site_url('whatsapp/qr_logout'),
            ],
        ]);

        $this->load->view('pages/whatsapp');
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

            check('wa_id', 'string');
            check('message', 'string');
            check('user_id', 'numeric|null');

            $wa_id = (string) request('wa_id');
            $message = trim((string) request('message'));
            $user_id = request('user_id') !== null ? (int) request('user_id') : null;

            if ($message === '') {
                throw new InvalidArgumentException('Mesaj boş olamaz.');
            }

            // Route the manual reply through the active sender mode (official Meta
            // API or unofficial bridge).
            $settings = $this->messaging_settings_model->get_settings();
            $result = $this->send_whatsapp(
                $wa_id,
                $message,
                $settings['whatsapp_mode'] ?? 'official',
            );

            if (!$result['success']) {
                throw new RuntimeException('WhatsApp mesajı gönderilemedi: ' . $result['error']);
            }

            // Log the outbound message
            $this->whatsapp_messages_model->save([
                'id_users' => $user_id,
                'wa_id' => $wa_id,
                'direction' => 'out',
                'message' => $message,
                'status' => 'sent',
            ]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Public webhook endpoint - Meta POSTs incoming messages and status updates here.
     * Handles both GET (verification) and POST (message/status handling).
     */
    public function webhook(): void
    {
        $request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($request_method === 'GET') {
            $this->webhook_verify();

            return;
        }

        if ($request_method === 'POST') {
            $this->webhook_receive();

            return;
        }

        abort(405, 'Method Not Allowed');
    }

    /**
     * Handle webhook verification request from Meta (GET request).
     * Meta sends: hub.mode=subscribe, hub.verify_token=..., hub.challenge=...
     * We respond with the challenge if the token matches.
     */
    private function webhook_verify(): void
    {
        try {
            $mode = request('hub_mode') ?? (request('hub.mode') ?? ($_GET['hub_mode'] ?? ($_GET['hub.mode'] ?? null)));
            $token = request('hub_verify_token') ?? (request('hub.verify_token') ?? ($_GET['hub_verify_token'] ?? ($_GET['hub.verify_token'] ?? null)));
            $challenge = request('hub_challenge') ?? (request('hub.challenge') ?? ($_GET['hub_challenge'] ?? ($_GET['hub.challenge'] ?? null)));

            if (empty($mode) || empty($challenge)) {
                parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
                $mode = $mode ?: ($qs['hub_mode'] ?? ($qs['hub.mode'] ?? null));
                $token = $token ?: ($qs['hub_verify_token'] ?? ($qs['hub.verify_token'] ?? null));
                $challenge = $challenge ?: ($qs['hub_challenge'] ?? ($qs['hub.challenge'] ?? null));
            }

            if (!$mode || !$token || !$challenge) {
                abort(403, 'Forbidden');
            }

            // Platform master token for central SaaS integration
            $master_token = master_setting('meta_webhook_verify_token')
                ?: (getenv('META_WEBHOOK_VERIFY_TOKEN') ?: 'bookiapp_meta_webhook_secret_2026');

            if ($mode === 'subscribe' && hash_equals($master_token, (string) $token)) {
                $this->output
                    ->set_status_header(200)
                    ->set_content_type('text/plain', 'UTF-8')
                    ->set_output((string) $challenge);
                return;
            }

            // Check tenant-specific settings if table exists in current DB
            if ($this->db->table_exists('messaging_settings')) {
                $settings = $this->messaging_settings_model->get_settings();
                $expected_token = $settings['whatsapp_webhook_verify_token'] ?? null;
                if (!empty($expected_token) && hash_equals($expected_token, (string) $token)) {
                    $this->output
                        ->set_status_header(200)
                        ->set_content_type('text/plain', 'UTF-8')
                        ->set_output((string) $challenge);
                    return;
                }
            }

            abort(403, 'Forbidden');
        } catch (Throwable $e) {
            log_message('error', 'Whatsapp::webhook_verify - ' . $e->getMessage());

            abort(403, 'Forbidden');
        }
    }

    /**
     * Handle incoming webhook POST from Meta (message or status update).
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

            // Get settings to initialize client for verifying webhooks
            $settings = $this->messaging_settings_model->get_settings();

            // Verify Meta HMAC signature if app secret is configured
            $app_secret = $settings['meta_app_secret'] ?? ($settings['whatsapp_app_secret'] ?? (getenv('META_APP_SECRET') ?: ''));
            $signature_header = $this->input->get_request_header('X-Hub-Signature-256')
                ?? ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? null);

            if (!empty($app_secret)) {
                if (empty($signature_header)) {
                    log_message('error', 'Whatsapp::webhook_receive - Missing X-Hub-Signature-256');
                    abort(403, 'Missing Meta signature');
                }
                $expected = 'sha256=' . hash_hmac('sha256', $raw_input, $app_secret);
                if (!hash_equals($expected, (string) $signature_header)) {
                    log_message('error', 'Whatsapp::webhook_receive - Invalid X-Hub-Signature-256');
                    abort(403, 'Invalid Meta signature');
                }
            }

            $this->whatsapp_client = new Whatsapp_client(
                $settings['whatsapp_phone_number_id'],
                $settings['whatsapp_access_token'],
                $settings['whatsapp_webhook_verify_token'],
            );

            // Parse the incoming message
            $message = $this->whatsapp_client->parse_incoming($payload);

            if (empty($message)) {
                response();

                return;
            }

            $wa_id = $message['from'];
            $body = $message['body'];

            // Try to match the sender to a known user by WhatsApp ID.
            // BooKi (Dalga 3 / Faz 3.5) - users.whatsapp_wa_id (added by
            // migration 133) is what makes this match possible; before that the
            // column did not exist and every incoming message landed with a null
            // user. Details in match_user_by_wa_id().
            $matched_user = $this->match_user_by_wa_id($wa_id);

            // Log the message
            $this->whatsapp_messages_model->save([
                'id_users' => $matched_user['id'] ?? null,
                'wa_id' => $wa_id,
                'direction' => 'in',
                'message' => $body ?: '[non-text message]',
            ]);

            // Update last_contact_channel for customers (like Telegram does)
            if ($matched_user && $matched_user['role_slug'] === DB_SLUG_CUSTOMER) {
                $this->db->update('users', ['last_contact_channel' => 'whatsapp'], ['id' => $matched_user['id']]);
            }

            // Post-Service Follow-Up Inbound Listener (RED/DUR opt-out, reaction check, NPS rating)
            $follow_up_handled = false;
            try {
                $this->load->library('follow_up_engine');
                $follow_up_handled = $this->follow_up_engine->handle_inbound_reply($wa_id, $body, $matched_user);
            } catch (Throwable $e) {
                log_message('error', 'Whatsapp::webhook_receive - Follow_up_engine error: ' . $e->getMessage());
            }

            // Auto-reply via AI Assistant if enabled and not already handled by follow-up flow
            $ai_enabled = !$follow_up_handled && (!empty($settings['ai_reply_whatsapp_enabled']) 
                || (setting('ai_reply_whatsapp_enabled') !== '0'));
            if ($ai_enabled) {
                $this->load->library('ai_channel_responder');
                $ai_reply = $this->ai_channel_responder->respond('whatsapp', $wa_id, $body, $matched_user);
                if (!empty($ai_reply)) {
                    $mode = $settings['whatsapp_mode'] ?? 'official';
                    $this->send_whatsapp($wa_id, $ai_reply, $mode);
                    $this->whatsapp_messages_model->save([
                        'id_users' => $matched_user['id'] ?? null,
                        'wa_id' => $wa_id,
                        'direction' => 'out',
                        'message' => $ai_reply,
                        'status' => 'sent',
                    ]);
                }
            }

            response();
        } catch (Throwable $e) {
            log_message('error', 'Whatsapp::webhook_receive - ' . $e->getMessage());

            response();
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.5) - switch the active sender mode between the
     * official Meta Cloud API and the unofficial bridge. Switching to 'unofficial'
     * requires the informed-consent timer to be logged first (see the wizard).
     */
    public function save_mode(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('mode', 'string');

            $mode = (string) request('mode');
            if (!in_array($mode, ['official', 'unofficial'], true)) {
                throw new InvalidArgumentException('Geçersiz mod.');
            }

            if ($mode === 'unofficial' && !plan_allows('whatsapp_unofficial')) {
                throw new RuntimeException('Bu özellik mevcut paketinizde yok. Yükseltmek için bizimle iletişime geçin.');
            }

            $consent = filter_var(request('consent', false), FILTER_VALIDATE_BOOLEAN);
            if ($mode === 'unofficial' && !$consent) {
                throw new InvalidArgumentException('Resmi olmayan mod için bilgilendirilmiş onay gereklidir.');
            }

            $settings = $this->messaging_settings_model->get_settings();

            $data = ['whatsapp_mode' => $mode];

            // One-time consent timestamp: only stamped the first time unofficial
            // mode is enabled, never re-written on later switches.
            if ($mode === 'unofficial' && empty($settings['whatsapp_unofficial_consent_at'])) {
                $data['whatsapp_unofficial_consent_at'] = date('Y-m-d H:i:s');
            }

            $this->messaging_settings_model->save_settings($data);

            json_response(['success' => true, 'consent_at' => $data['whatsapp_unofficial_consent_at'] ?? null]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.5) - persist the unofficial bridge's REST base
     * URL and its shared secret. The secret is PII-encrypted by the model exactly like
     * the Meta access token. Empty values are ignored (never wipe a working config);
     * the UI treats leaving them blank as "keep current".
     */
    public function save_bridge(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            if (!plan_allows('whatsapp_unofficial')) {
                throw new RuntimeException('Bu özellik mevcut paketinizde yok. Yükseltmek için bizimle iletişime geçin.');
            }

            check('bridge_url', 'string|null');
            check('bridge_secret', 'string|null');

            $data = [];

            $url = trim((string) request('bridge_url', ''));

            if ($url !== '') {
                if (!preg_match('~^https?://~i', $url)) {
                    throw new InvalidArgumentException('Köprü adresi http(s):// ile başlamalı.');
                }

                $data['whatsapp_bridge_url'] = rtrim($url, '/');
            }

            $secret = trim((string) request('bridge_secret', ''));

            if ($secret !== '') {
                $data['whatsapp_bridge_secret'] = $secret;
            }

            if (empty($data)) {
                throw new InvalidArgumentException('Değişiklik yapılmadı.');
            }

            $this->messaging_settings_model->save_settings($data);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.5) - busy check for the wizard: whether the
     * active mode's credentials actually reach a connected device/account.
     */
    public function check_connection(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            $settings = $this->messaging_settings_model->get_settings();
            $mode = $settings['whatsapp_mode'] ?? 'official';

            if ($mode === 'unofficial') {
                $bridge = new Whatsapp_bridge($settings['whatsapp_bridge_url'], $settings['whatsapp_bridge_secret']);
                if (!$bridge->is_configured()) {
                    json_response(['success' => false, 'reason' => 'bridge_not_configured', 'message' => 'Köprü adresi tanımlı değil.']);

                    return;
                }

                $health = $bridge->health();
                if ($health === null) {
                    json_response(['success' => false, 'reason' => 'bridge_unreachable', 'message' => 'Köprüye ulaşılamıyor.']);

                    return;
                }

                $session = $bridge->session_status($this->tenant_identifier());

                json_response(['success' => true, 'bridge' => $health, 'session' => $session ?? []]);

                return;
            }

            $client = new Whatsapp_client(
                $settings['whatsapp_phone_number_id'],
                $settings['whatsapp_access_token'],
            );

            if (!$client->is_configured()) {
                json_response(['success' => false, 'reason' => 'not_configured', 'message' => 'Meta bilgileri eksik.']);

                return;
            }

            $account = $client->get_account_info();

            if ($account === null) {
                json_response(['success' => false, 'reason' => 'meta_unreachable', 'message' => 'Meta hesabına ulaşılamadı; bilgiler doğru mu?']);

                return;
            }

            json_response(['success' => true, 'account' => $account]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.5) - send a test message through the active mode
     * and log it in whatsapp_messages, proving the end-to-end path works.
     */
    public function send_test(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('to_phone', 'string');

            $to_phone = preg_replace('/\D+/', '', (string) request('to_phone'));

            if ($to_phone === '') {
                throw new InvalidArgumentException('Telefon numarası boş olamaz.');
            }

            $settings = $this->messaging_settings_model->get_settings();
            $mode = $settings['whatsapp_mode'] ?? 'official';

            $result = $this->send_whatsapp($to_phone, 'BooKi - test mesajı.', $mode);

            if (!$result['success']) {
                throw new RuntimeException('Gönderilemedi: ' . $result['error']);
            }

            $this->whatsapp_messages_model->save([
                'id_users' => null,
                'wa_id' => $to_phone,
                'direction' => 'out',
                'message' => 'BooKi - test mesajı.',
                'status' => 'sent',
            ]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.5) - ask the bridge to begin a coupling session
     * for this tenant; the resulting QR code is fetched via qr_status() polling.
     */
    public function qr_start(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            if (!plan_allows('whatsapp_unofficial')) {
                throw new RuntimeException('Bu özellik mevcut paketinizde yok. Yükseltmek için bizimle iletişime geçin.');
            }

            $settings = $this->messaging_settings_model->get_settings();

            if (($settings['whatsapp_mode'] ?? 'official') !== 'unofficial') {
                $this->messaging_settings_model->save_settings([
                    'whatsapp_mode' => 'unofficial',
                    'whatsapp_unofficial_consent_at' => date('Y-m-d H:i:s'),
                ]);
                $this->load->model('settings_model');
                $this->settings_model->set_setting('whatsapp_mode', 'unofficial');
                $settings['whatsapp_mode'] = 'unofficial';
            }

            $bridge_url = !empty($settings['whatsapp_bridge_url'])
                ? $settings['whatsapp_bridge_url']
                : (getenv('WA_BRIDGE_URL') ?: 'http://wa-bridge:3000');
            $bridge_secret = !empty($settings['whatsapp_bridge_secret'])
                ? $settings['whatsapp_bridge_secret']
                : (getenv('WA_BRIDGE_SECRET') ?: null);

            $bridge = new Whatsapp_bridge($bridge_url, $bridge_secret);
            if (!$bridge->is_configured()) {
                throw new InvalidArgumentException('Köprü adresi tanımlı değil.');
            }

            $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';
            $tenant_subdomain = $this->tenant_identifier();
            $webhook_url = 'https://' . $tenant_subdomain . '-' . $app_domain . '/index.php/whatsapp/bridge_inbound';

            $result = $bridge->session_start($tenant_subdomain, [
                'webhookUrl' => $webhook_url,
            ]);

            if ($result === null) {
                throw new RuntimeException('Köprüye ulaşılamadı.');
            }

            $status = (string) ($result['status'] ?? 'connecting');
            $this->messaging_settings_model->save_settings(['whatsapp_unofficial_status' => $status]);

            json_response([
                'success' => true,
                'status' => $status,
                'qr' => $result['qr'] ?? null,
                'response' => $result,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.5) - poll the bridge for the current session
     * state (and QR data while connecting). Cache the status in messaging_settings
     * so the panel can show a badge without a round-trip.
     */
    public function qr_status(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            $settings = $this->messaging_settings_model->get_settings();

            $bridge_url = !empty($settings['whatsapp_bridge_url'])
                ? $settings['whatsapp_bridge_url']
                : (getenv('WA_BRIDGE_URL') ?: 'http://wa-bridge:3000');
            $bridge_secret = !empty($settings['whatsapp_bridge_secret'])
                ? $settings['whatsapp_bridge_secret']
                : (getenv('WA_BRIDGE_SECRET') ?: null);

            $bridge = new Whatsapp_bridge($bridge_url, $bridge_secret);

            if (!$bridge->is_configured()) {
                json_response(['success' => false, 'reason' => 'bridge_not_configured']);

                return;
            }

            $session = $bridge->session_status($this->tenant_identifier());

            if ($session === null) {
                json_response(['success' => false, 'reason' => 'bridge_unreachable']);

                return;
            }

            $state = (string) ($session['status'] ?? 'disconnected');
            $mapped = strtolower($state);

            if (!in_array($mapped, ['connecting'], true)) {
                $this->messaging_settings_model->save_settings([
                    'whatsapp_unofficial_status' => in_array($mapped, ['connected'], true) ? 'connected' : ($mapped === 'error' ? 'error' : 'disconnected'),
                ]);
            }

            json_response([
                'success' => true,
                'status' => $mapped,
                'qr' => $session['qr'] ?? null,
                'meta' => $session,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.5) - end the bridge session for this tenant
     * (logout the paired device) and reset the cached status.
     */
    public function qr_logout(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            $settings = $this->messaging_settings_model->get_settings();

            $bridge = new Whatsapp_bridge($settings['whatsapp_bridge_url'], $settings['whatsapp_bridge_secret']);
            if ($bridge->is_configured()) {
                $bridge->session_logout($this->tenant_identifier());
            }

            $this->messaging_settings_model->save_settings(['whatsapp_unofficial_status' => 'disconnected']);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.5) - public endpoint the ki-wa-bridge calls
     * when the paired device receives an inbound message. Authenticated with the
     * shared bridge secret (X-Bridge-Secret header), never with the admin session.
     */
    public function bridge_inbound(): void
    {
        try {
            $payload = json_decode(file_get_contents('php://input'), true) ?: [];

            if (empty($payload)) {
                response();

                return;
            }

            $settings = $this->messaging_settings_model->get_settings();
            $secret = $settings['whatsapp_bridge_secret'] ?? null;

            $bridge = new Whatsapp_bridge($settings['whatsapp_bridge_url'], $secret);

            $received = $_SERVER['HTTP_X_BRIDGE_SECRET'] ?? null;

            if (!$bridge->verify_secret_header(is_string($received) ? $received : '')) {
                log_message('error', 'Whatsapp::bridge_inbound - secret mismatch for tenant ' . $this->tenant_identifier());

                response();

                return;
            }

            // The request reaches us on a tenant-resolved Host (multi-tenant) or the
            // standalone deployment; only accept payloads that claim our own tenant.
            if (is_multi_tenant_mode() && !empty($payload['tenant']) && $payload['tenant'] !== $this->tenant_identifier()) {
                log_message('error', 'Whatsapp::bridge_inbound - tenant mismatch: ' . $payload['tenant']);

                response();

                return;
            }

            $from = (string) ($payload['from'] ?? '');
            $push_name = (string) ($payload['push_name'] ?? '');
            $body = (string) ($payload['body'] ?? '[non-text message]');

            if ($from === '') {
                log_message('error', 'Whatsapp::bridge_inbound - payload without from');

                response();

                return;
            }

            $matched_user = $this->match_user_by_wa_id($from, $push_name);

            // Store the inbound message (mirrors webhook_receive() behavior).
            $this->whatsapp_messages_model->save([
                'id_users' => $matched_user['id'] ?? null,
                'wa_id' => $from,
                'direction' => 'in',
                'message' => $body ?: '[non-text message]',
            ]);

            if ($matched_user && $matched_user['role_slug'] === DB_SLUG_CUSTOMER) {
                $this->db->update('users', ['last_contact_channel' => 'whatsapp'], ['id' => $matched_user['id']]);
            }

            // Post-Service Follow-Up Inbound Listener (RED/DUR opt-out, reaction check, NPS rating)
            $follow_up_handled = false;
            try {
                $this->load->library('follow_up_engine');
                $follow_up_handled = $this->follow_up_engine->handle_inbound_reply($from, $body, $matched_user);
            } catch (Throwable $e) {
                log_message('error', 'Whatsapp::bridge_inbound - Follow_up_engine error: ' . $e->getMessage());
            }

            // Auto-reply via AI Assistant if enabled and not already handled by follow-up flow
            $ai_enabled = !$follow_up_handled && (!empty($settings['ai_reply_whatsapp_enabled']) 
                || (setting('ai_reply_whatsapp_enabled') !== '0'));
            if ($ai_enabled) {
                $this->load->library('ai_channel_responder');
                $ai_reply = $this->ai_channel_responder->respond('whatsapp', $from, $body, $matched_user);
                if (!empty($ai_reply)) {
                    $mode = $settings['whatsapp_mode'] ?? 'unofficial';
                    $this->send_whatsapp($from, $ai_reply, $mode);
                    $this->whatsapp_messages_model->save([
                        'id_users' => $matched_user['id'] ?? null,
                        'wa_id' => $from,
                        'direction' => 'out',
                        'message' => $ai_reply,
                        'status' => 'sent',
                    ]);
                }
            }

            response();
        } catch (Throwable $e) {
            log_message('error', 'Whatsapp::bridge_inbound - ' . $e->getMessage());

            response();
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.5) - resolve an inbound sender to a known
     * customer by their whatsapp_wa_id, phone candidates, or push_name.
     *
     * @return array|null
     */
    private function match_user_by_wa_id(string $wa_id, ?string $push_name = null): ?array
    {
        if (!$this->db->field_exists('whatsapp_wa_id', 'users')) {
            return null;
        }

        // 1. Direct whatsapp_wa_id match
        $user = $this->db
            ->select('users.*, roles.slug AS role_slug')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'left')
            ->where('users.whatsapp_wa_id', $wa_id)
            ->limit(1)
            ->get()
            ->row_array();

        if ($user) {
            return $this->decrypt_user_pii($user);
        }

        $normalized = preg_replace('/\D+/', '', $wa_id);

        if ($normalized !== '' && $normalized !== $wa_id) {
            $user = $this->db
                ->select('users.*, roles.slug AS role_slug')
                ->from('users')
                ->join('roles', 'roles.id = users.id_roles', 'left')
                ->where('users.whatsapp_wa_id', $normalized)
                ->limit(1)
                ->get()
                ->row_array();

            if ($user) {
                return $this->decrypt_user_pii($user);
            }
        }

        // 2. If $wa_id is a telephone number (10-12 digits, e.g. 905xxxxxxxxx or 5xxxxxxxxx)
        if ($normalized !== '' && strlen($normalized) >= 10 && strlen($normalized) <= 12) {
            $last10 = substr($normalized, -10);
            $candidates = array_values(array_unique(array_filter([
                $normalized,
                $last10,
                '0' . $last10,
                '90' . $last10,
                '+90' . $last10,
                sprintf('0 (%s) %s %s %s', substr($last10, 0, 3), substr($last10, 3, 3), substr($last10, 6, 2), substr($last10, 8, 2)),
                sprintf('0%s %s %s %s', substr($last10, 0, 3), substr($last10, 3, 3), substr($last10, 6, 2), substr($last10, 8, 2)),
            ])));

            // A. PII Hash matching (for encrypted databases)
            if (function_exists('sf_pii_hash') && $this->db->field_exists('phone_number_hash', 'users')) {
                $hashes = array_values(array_unique(array_filter(array_map('sf_pii_hash', $candidates))));
                if (!empty($hashes)) {
                    $user = $this->db
                        ->select('users.*, roles.slug AS role_slug')
                        ->from('users')
                        ->join('roles', 'roles.id = users.id_roles', 'left')
                        ->where_in('users.phone_number_hash', $hashes)
                        ->limit(1)
                        ->get()
                        ->row_array();

                    if ($user) {
                        $this->db->update('users', ['whatsapp_wa_id' => $wa_id], ['id' => $user['id']]);
                        return $this->decrypt_user_pii($user);
                    }
                }
            }

            // B. Plaintext match fallback
            $user = $this->db
                ->select('users.*, roles.slug AS role_slug')
                ->from('users')
                ->join('roles', 'roles.id = users.id_roles', 'left')
                ->group_start()
                ->where_in('users.phone_number', $candidates)
                ->or_like('users.phone_number', $last10)
                ->group_end()
                ->limit(1)
                ->get()
                ->row_array();

            if ($user) {
                $this->db->update('users', ['whatsapp_wa_id' => $wa_id], ['id' => $user['id']]);
                return $this->decrypt_user_pii($user);
            }
        }

        // 3. Fallback: match by WhatsApp profile display name (push_name) if available
        $clean_push = trim((string) $push_name);
        if ($clean_push !== '' && mb_strlen($clean_push) >= 3) {
            $matching_users = $this->db
                ->select('users.*, roles.slug AS role_slug')
                ->from('users')
                ->join('roles', 'roles.id = users.id_roles', 'left')
                ->group_start()
                ->where('CONCAT_WS(" ", users.first_name, users.last_name)', $clean_push)
                ->or_where('users.first_name', $clean_push)
                ->group_end()
                ->limit(2)
                ->get()
                ->result_array();

            // Link only if uniquely matched to 1 customer
            if (count($matching_users) === 1) {
                $user = $matching_users[0];
                $this->db->update('users', ['whatsapp_wa_id' => $wa_id], ['id' => $user['id']]);
                return $this->decrypt_user_pii($user);
            }
        }

        return null;
    }

    /**
     * Decrypt customer PII fields if encrypted.
     */
    private function decrypt_user_pii(array $user): array
    {
        if (function_exists('sf_pii_is_encrypted') && function_exists('sf_pii_decrypt')) {
            foreach (['phone_number', 'email', 'address', 'notes', 'city', 'zip_code'] as $field) {
                if (!empty($user[$field]) && sf_pii_is_encrypted($user[$field])) {
                    $user[$field] = sf_pii_decrypt($user[$field]);
                }
            }
        }

        return $user;
    }

    /**
     * BooKi (Dalga 3 / Faz 3.5) - send a message through either WhatsApp
     * transport. Returns ['success' => bool, 'message_id' => ?string, 'error' => ?string].
     */
    private function send_whatsapp(string $to, string $text, string $mode): array
    {
        if ($mode === 'unofficial') {
            $settings = $this->messaging_settings_model->get_settings();
            $bridge = new Whatsapp_bridge($settings['whatsapp_bridge_url'], $settings['whatsapp_bridge_secret']);

            return $bridge->send($this->tenant_identifier(), $to, $text);
        }

        $settings = $this->messaging_settings_model->get_settings();

        $client = new Whatsapp_client(
            $settings['whatsapp_phone_number_id'],
            $settings['whatsapp_access_token'],
        );

        return $client->send_text($to, $text);
    }

    /**
     * BooKi (Dalga 3 / Faz 3.5) - stable tenant identifier for bridge calls.
     */
    private function tenant_identifier(): string
    {
        $context = tenant_context();
        $subdomain = $context['subdomain'] ?? 'default';

        if (str_starts_with($subdomain, 'demo-') || $subdomain === 'salonflora' || str_contains($subdomain, '_sb') || str_contains($subdomain, '-sb') || $subdomain === 'demo') {
            return 'salonflora';
        }

        return $subdomain;
    }
}
