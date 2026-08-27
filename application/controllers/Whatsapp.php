<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - WhatsApp Business Cloud API integration controller (2026-08-27).
 *
 * Admin-facing settings page plus the public webhook endpoint that Meta
 * calls for incoming messages and status updates.
 *
 * @package Controllers
 */

class Whatsapp extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('messaging_settings_model');
        $this->load->model('whatsapp_messages_model');
        $this->load->model('users_model');
        $this->load->library('whatsapp_client');
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
            'messages' => $messages,
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

            // Initialize WhatsApp client with current settings
            $settings = $this->messaging_settings_model->get_settings();
            $this->whatsapp_client = new Whatsapp_client(
                $settings['whatsapp_phone_number_id'],
                $settings['whatsapp_access_token'],
            );

            $result = $this->whatsapp_client->send_text($wa_id, $message);

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
            $mode = request('hub_mode');
            $token = request('hub_verify_token');
            $challenge = request('hub_challenge');

            if (!$mode || !$token || !$challenge) {
                abort(403, 'Forbidden');
            }

            $settings = $this->messaging_settings_model->get_settings();

            $expected_token = $settings['whatsapp_webhook_verify_token'];

            if (empty($expected_token) || !hash_equals($expected_token, $token)) {
                abort(403, 'Forbidden');
            }

            echo $challenge;
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
            $payload = json_decode(file_get_contents('php://input'), true) ?: [];

            if (empty($payload)) {
                response();

                return;
            }

            // Get settings to initialize client for verifying webhooks
            $settings = $this->messaging_settings_model->get_settings();
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

            // Try to match the sender to a known user by WhatsApp ID
            // (Requires a future migration to add users.whatsapp_wa_id column for this to work.
            // For now, matching is not automatic; staff can log messages without a user_id.)
            $matched_user = null;

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

            response();
        } catch (Throwable $e) {
            log_message('error', 'Whatsapp::webhook_receive - ' . $e->getMessage());

            response();
        }
    }
}
