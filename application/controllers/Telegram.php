<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * Salon Flora customization - native Telegram integration controller (2026-08-25). Admin-facing setup
 * page (bot token, webhook, staff linking, inbound messages/reply) plus the public webhook endpoint
 * Telegram itself calls. See Telegram_client.php for the raw Bot API calls.
 *
 * @package Controllers
 */
class Telegram extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('settings_model');
        $this->load->model('admins_model');
        $this->load->model('providers_model');
        $this->load->model('secretaries_model');
        $this->load->model('customers_model');

        $this->load->library('telegram_client');
    }

    /**
     * Render the Telegram integration settings page.
     */
    public function index(): void
    {
        method('get');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        $staff = array_merge(
            array_map(fn($u) => $u + ['role' => 'admin'], $this->admins_model->get()),
            array_map(fn($u) => $u + ['role' => 'secretary'], $this->secretaries_model->get()),
            array_map(fn($u) => $u + ['role' => 'provider'], $this->providers_model->get()),
        );

        $staff = array_map(
            fn($u) => [
                'id' => $u['id'],
                'role' => $u['role'],
                'name' => trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')),
                'telegram_chat_id' => $u['telegram_chat_id'] ?? null,
                'telegram_username' => $u['telegram_username'] ?? null,
            ],
            $staff,
        );

        $messages = $this->db
            ->select('telegram_messages.*, users.first_name, users.last_name')
            ->from('telegram_messages')
            ->join('users', 'users.id = telegram_messages.id_users', 'left')
            ->order_by('telegram_messages.created_at', 'desc')
            ->limit(50)
            ->get()
            ->result_array();

        html_vars([
            'page_title' => 'Telegram Entegrasyonu',
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'telegram_bot_token_set' => !empty(setting('telegram_bot_token')),
            'telegram_bot_username' => setting('telegram_bot_username'),
            'telegram_notifications_enabled' => filter_var(
                setting('telegram_notifications_enabled'),
                FILTER_VALIDATE_BOOLEAN,
            ),
            'telegram_webhook_configured' => !empty(setting('telegram_webhook_secret')),
            'staff' => $staff,
            'messages' => $messages,
        ]);

        $this->load->view('pages/telegram');
    }

    /**
     * Save the bot token + notification toggle, and verify the token by fetching the bot's own
     * username (needed to build staff deep links).
     */
    public function save_settings(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('bot_token', 'string|null');
            check('notifications_enabled', 'bool|null');

            $bot_token = trim((string) request('bot_token', ''));
            $notifications_enabled = filter_var(request('notifications_enabled', false), FILTER_VALIDATE_BOOLEAN);

            if ($bot_token !== '') {
                setting(['telegram_bot_token' => $bot_token]);

                $me = $this->telegram_client->get_me();

                if (!$me) {
                    // Salon Flora customization - InvalidArgumentException (not RuntimeException) is
                    // deliberate: json_exception()'s sensitive-message sanitizer replaces any
                    // RuntimeException whose text matches /token/i with a generic message, which would
                    // hide this exact, actionable error from the person who just mistyped a token.
                    throw new InvalidArgumentException(
                        'Bu değer doğrulanamadı - Telegram kabul etmedi. Lütfen @BotFather\'dan aldığınız değeri kontrol edin.',
                    );
                }

                setting(['telegram_bot_username' => $me['username']]);
            }

            setting(['telegram_notifications_enabled' => $notifications_enabled ? '1' : '0']);

            json_response(['success' => true, 'bot_username' => setting('telegram_bot_username')]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Point Telegram's webhook at this installation. Generates and stores a random secret token the
     * first time, reused on subsequent calls (re-running this just re-registers the same URL/secret).
     */
    public function setup_webhook(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            if (empty(setting('telegram_bot_token'))) {
                // Salon Flora customization - InvalidArgumentException, not RuntimeException - see the
                // comment on the same pattern in save_settings() above.
                throw new InvalidArgumentException('Önce Bot Ayarları\'nı kaydedin.');
            }

            $secret = setting('telegram_webhook_secret');

            if (empty($secret)) {
                $secret = bin2hex(random_bytes(32));
                setting(['telegram_webhook_secret' => $secret]);
            }

            $webhook_url = site_url('telegram/webhook');

            $success = $this->telegram_client->set_webhook($webhook_url, $secret);

            if (!$success) {
                throw new RuntimeException('Telegram webhook kaydı başarısız oldu.');
            }

            json_response(['success' => true, 'webhook_url' => $webhook_url]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Generate (or refresh) a one-time "Start" deep link for a staff member to link their own
     * Telegram chat ID by pressing it once.
     */
    public function generate_link(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('user_id', 'numeric');

            $user_id = (int) request('user_id');

            $bot_username = setting('telegram_bot_username');

            if (empty($bot_username)) {
                // Salon Flora customization - InvalidArgumentException, not RuntimeException - see the
                // comment on the same pattern in save_settings() above.
                throw new InvalidArgumentException('Önce Bot Ayarları\'nı kaydedip doğrulayın.');
            }

            $token = bin2hex(random_bytes(16));

            $this->db->update('users', ['telegram_link_token' => $token], ['id' => $user_id]);

            json_response([
                'success' => true,
                'link' => 'https://t.me/' . $bot_username . '?start=' . $token,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Send a reply to a chat from the "Gelen Mesajlar" list.
     */
    public function reply(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('chat_id', 'string');
            check('message', 'string');
            check('user_id', 'numeric|null');

            $chat_id = (string) request('chat_id');
            $message = trim((string) request('message'));
            $user_id = request('user_id') !== null ? (int) request('user_id') : null;

            if ($message === '') {
                throw new InvalidArgumentException('Mesaj boş olamaz.');
            }

            $sent = $this->telegram_client->send_message($chat_id, $message);

            if (!$sent) {
                throw new RuntimeException('Mesaj gönderilemedi - bot yapılandırmasını kontrol edin.');
            }

            $this->db->insert('telegram_messages', [
                'id_users' => $user_id,
                'chat_id' => $chat_id,
                'direction' => 'out',
                'message' => $message,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Public webhook endpoint - Telegram POSTs every bot update here. Verified via the
     * X-Telegram-Bot-Api-Secret-Token header (set once via setup_webhook()), NOT session auth - Telegram
     * has no session with this app.
     */
    public function webhook(): void
    {
        try {
            method('post');

            $configured_secret = setting('telegram_webhook_secret');
            $received_secret = $this->input->get_request_header('X-Telegram-Bot-Api-Secret-Token');

            if (empty($configured_secret) || !hash_equals($configured_secret, (string) $received_secret)) {
                abort(403, 'Forbidden');
            }

            $update = json_decode(file_get_contents('php://input'), true) ?: [];

            $message = $update['message'] ?? null;

            if (!$message || empty($message['chat']['id'])) {
                response();

                return;
            }

            $chat_id = (string) $message['chat']['id'];
            $text = (string) ($message['text'] ?? '');
            $username = $message['from']['username'] ?? null;

            if (str_starts_with($text, '/start ')) {
                $token = trim(substr($text, 7));

                $user = $this->db->get_where('users', ['telegram_link_token' => $token])->row_array();

                if ($user) {
                    $this->db->update(
                        'users',
                        [
                            'telegram_chat_id' => $chat_id,
                            'telegram_username' => $username,
                            'telegram_link_token' => null,
                        ],
                        ['id' => $user['id']],
                    );

                    $this->telegram_client->send_message(
                        $chat_id,
                        '✅ Telegram hesabınız BooKi ile bağlandı. Randevu bildirimlerini buradan da alacaksınız.',
                    );
                } else {
                    $this->telegram_client->send_message(
                        $chat_id,
                        'Bu bağlantı geçersiz veya süresi dolmuş. Lütfen yönetiminizden yeni bir bağlantı isteyin.',
                    );
                }

                response();

                return;
            }

            // Not a /start link - log the message, matching it to a known user by chat_id if possible
            // (so staff can see who it's from) - unmatched messages are still logged (id_users null) so
            // nothing is silently dropped, staff can link/identify them manually later.
            $matched_user = $this->db
                ->select('users.id, roles.slug AS role_slug')
                ->from('users')
                ->join('roles', 'roles.id = users.id_roles')
                ->where('users.telegram_chat_id', $chat_id)
                ->get()
                ->row_array();

            $this->db->insert('telegram_messages', [
                'id_users' => $matched_user['id'] ?? null,
                'chat_id' => $chat_id,
                'direction' => 'in',
                'message' => $text !== '' ? $text : '[metin olmayan mesaj]',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // BooKi (2026-08-26) - an inbound message is a real signal that Telegram is the
            // customer's most recent contact channel, so the CRM card no longer relies solely on staff
            // marking it manually (see Customers.php/customers.js). Only customers carry this field
            // meaningfully - a provider/admin/secretary messaging the bot is unrelated to CRM tracking.
            if ($matched_user && $matched_user['role_slug'] === DB_SLUG_CUSTOMER) {
                $this->db->update('users', ['last_contact_channel' => 'telegram'], ['id' => $matched_user['id']]);
            }

            // Auto-reply via AI Assistant if enabled
            $this->load->model('messaging_settings_model');
            $msg_settings = $this->messaging_settings_model->get_settings();
            if (!empty($msg_settings['ai_reply_telegram_enabled'])) {
                $this->load->library('ai_channel_responder');
                $ai_reply = $this->ai_channel_responder->respond('telegram', $chat_id, $text, $matched_user);
                if (!empty($ai_reply)) {
                    $this->telegram_client->send_message($chat_id, $ai_reply);
                    $this->db->insert('telegram_messages', [
                        'id_users' => $matched_user['id'] ?? null,
                        'chat_id' => $chat_id,
                        'direction' => 'out',
                        'message' => $ai_reply,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            response();
        } catch (Throwable $e) {
            log_message('error', 'Telegram::webhook - ' . $e->getMessage());

            response();
        }
    }
}
