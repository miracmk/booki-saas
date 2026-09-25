<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Messaging settings controller (2026-08-27).
 *
 * Admin-facing page for configuring SMS (Netgsm) and WhatsApp Business Cloud
 * API credentials. Handles both display (GET) and persistence (POST).
 *
 * @package Controllers
 */

class Messaging_settings extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('messaging_settings_model');
    }

    /**
     * Render the messaging settings page (SMS + WhatsApp configuration).
     */
    public function index(): void
    {
        method('get');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        $settings = $this->messaging_settings_model->get_settings();

        // Prepare display-safe version (mask sensitive values)
        $display_settings = [
            'reminder_notifications_enabled' => (bool) ($settings['reminder_notifications_enabled'] ?? true),
            'reminder_hours_ahead' => (int) ($settings['reminder_hours_ahead'] ?? 24),
            'reminder_offsets' => $settings['reminder_offsets'] ?? [24],
            'sms_gateway' => $settings['sms_gateway'],
            'netgsm_username' => $settings['netgsm_username'] ? '***' : '',
            'netgsm_password' => !empty($settings['netgsm_password']) ? '***' : '',
            'netgsm_header' => $settings['netgsm_header'],
            'sms_notifications_enabled' => (bool) $settings['sms_notifications_enabled'],
            'whatsapp_phone_number_id' => $settings['whatsapp_phone_number_id'] ? '***' : '',
            'whatsapp_access_token' => $settings['whatsapp_access_token'] ? '***' : '',
            'whatsapp_waba_id' => $settings['whatsapp_waba_id'] ? '***' : '',
            'whatsapp_webhook_verify_token' => $settings['whatsapp_webhook_verify_token'] ? '***' : '',
            'whatsapp_business_phone_display' => $settings['whatsapp_business_phone_display'],
            'whatsapp_notifications_enabled' => (bool) $settings['whatsapp_notifications_enabled'],
            'email_notifications_enabled' => (bool) ($settings['email_notifications_enabled'] ?? true),
            'call_notifications_enabled' => (bool) ($settings['call_notifications_enabled'] ?? false),
            'call_provider' => $settings['call_provider'] ?? '',
            'call_api_key' => !empty($settings['call_api_key']) ? '***' : '',
            'call_from_number' => $settings['call_from_number'] ?? '',
            'telegram_notifications_enabled' => (bool) ($settings['telegram_notifications_enabled'] ?? false),
            'telegram_bot_token' => !empty($settings['telegram_bot_token']) ? '***' : '',
            'instagram_notifications_enabled' => (bool) ($settings['instagram_notifications_enabled'] ?? false),
            'instagram_access_token' => !empty($settings['instagram_access_token']) ? '***' : '',
            'instagram_account_id' => $settings['instagram_account_id'] ?? '',
            'instagram_webhook_verify_token' => $settings['instagram_webhook_verify_token'] ? '***' : '',
            'ai_reply_whatsapp_enabled' => (bool) ($settings['ai_reply_whatsapp_enabled'] ?? false),
            'ai_reply_telegram_enabled' => (bool) ($settings['ai_reply_telegram_enabled'] ?? false),
            'ai_reply_instagram_enabled' => (bool) ($settings['ai_reply_instagram_enabled'] ?? false),
            'smtp_host' => $settings['smtp_host'],
            'smtp_port' => $settings['smtp_port'],
            'smtp_crypto' => $settings['smtp_crypto'],
            'smtp_user' => $settings['smtp_user'] ? '***' : '',
            'smtp_pass' => $settings['smtp_pass'] ? '***' : '',
            'smtp_from_name' => $settings['smtp_from_name'],
            'smtp_from_address' => $settings['smtp_from_address'],
            'default_notification_channel' => $settings['default_notification_channel'] ?? 'telegram',
            'default_notification_channels' => array_values(array_filter(array_map('trim', explode(',', (string) ($settings['default_notification_channels'] ?? $settings['default_notification_channel'] ?? 'telegram'))))),
            'whatsapp_mode' => $settings['whatsapp_mode'] ?? 'official',
            'whatsapp_unofficial_status' => $settings['whatsapp_unofficial_status'] ?? 'disconnected',
            'whatsapp_unofficial_name' => $settings['whatsapp_unofficial_name'] ?? null,
            'whatsapp_bridge_configured' => !empty($settings['whatsapp_bridge_url']) && !empty($settings['whatsapp_bridge_secret']),
        ];

        html_vars([
            'page_title' => 'SMS ve WhatsApp Ayarları',
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'settings' => $display_settings,
            'webhook_url' => site_url('whatsapp/webhook'),
            'whatsapp_url' => site_url('whatsapp'),
            'telegram_url' => site_url('telegram'),
            'instagram_url' => site_url('instagram'),
        ]);

        $this->load->view('pages/messaging_settings');
    }

    /**
     * Save messaging settings (SMS + WhatsApp credentials).
     */
    public function save_settings(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('sms_gateway', 'string|null');
            check('netgsm_username', 'string|null');
            check('netgsm_password', 'string|null');
            check('netgsm_header', 'string|null');
            check('sms_notifications_enabled', 'bool|null');
            check('whatsapp_phone_number_id', 'string|null');
            check('whatsapp_access_token', 'string|null');
            check('whatsapp_waba_id', 'string|null');
            check('whatsapp_webhook_verify_token', 'string|null');
            check('whatsapp_business_phone_display', 'string|null');
            check('whatsapp_notifications_enabled', 'bool|null');
            check('email_notifications_enabled', 'bool|null');
            check('call_notifications_enabled', 'bool|null');
            check('call_provider', 'string|null');
            check('call_api_key', 'string|null');
            check('call_from_number', 'string|null');
            check('telegram_notifications_enabled', 'bool|null');
            check('telegram_bot_token', 'string|null');
            check('instagram_notifications_enabled', 'bool|null');
            check('instagram_access_token', 'string|null');
            check('instagram_account_id', 'string|null');
            check('instagram_webhook_verify_token', 'string|null');
            check('ai_reply_whatsapp_enabled', 'bool|null');
            check('ai_reply_telegram_enabled', 'bool|null');
            check('ai_reply_instagram_enabled', 'bool|null');
            check('smtp_host', 'string|null');
            check('smtp_port', 'integer|null');
            check('smtp_crypto', 'string|null');
            check('smtp_user', 'string|null');
            check('smtp_pass', 'string|null');
            check('smtp_from_name', 'string|null');
            check('smtp_from_address', 'string|null');
            check('default_notification_channel', 'string|null');
            check('default_notification_channels', 'array|null');
            check('reminder_notifications_enabled', 'bool|null');
            check('reminder_hours_ahead', 'integer|null');
            check('reminder_offsets', 'array|null');

            $data = [
                'sms_gateway' => trim((string) request('sms_gateway', 'none')) ?: 'none',
                'netgsm_username' => trim((string) request('netgsm_username', '')) ?: null,
                'netgsm_password' => trim((string) request('netgsm_password', '')) ?: null,
                'netgsm_header' => trim((string) request('netgsm_header', '')) ?: null,
                'sms_notifications_enabled' => filter_var(
                    request('sms_notifications_enabled', false),
                    FILTER_VALIDATE_BOOLEAN,
                ) ? 1 : 0,
                'whatsapp_phone_number_id' => trim((string) request('whatsapp_phone_number_id', '')) ?: null,
                'whatsapp_access_token' => trim((string) request('whatsapp_access_token', '')) ?: null,
                'whatsapp_waba_id' => trim((string) request('whatsapp_waba_id', '')) ?: null,
                'whatsapp_webhook_verify_token' => trim((string) request('whatsapp_webhook_verify_token', '')) ?: null,
                'whatsapp_business_phone_display' => trim((string) request('whatsapp_business_phone_display', '')) ?: null,
                'whatsapp_notifications_enabled' => filter_var(
                    request('whatsapp_notifications_enabled', false),
                    FILTER_VALIDATE_BOOLEAN,
                ) ? 1 : 0,
                'email_notifications_enabled' => filter_var(
                    request('email_notifications_enabled', true),
                    FILTER_VALIDATE_BOOLEAN,
                ) ? 1 : 0,
                'call_notifications_enabled' => filter_var(
                    request('call_notifications_enabled', false),
                    FILTER_VALIDATE_BOOLEAN,
                ) ? 1 : 0,
                'call_provider' => trim((string) request('call_provider', '')) ?: null,
                'call_api_key' => trim((string) request('call_api_key', '')) ?: null,
                'call_from_number' => trim((string) request('call_from_number', '')) ?: null,
                'telegram_notifications_enabled' => filter_var(
                    request('telegram_notifications_enabled', false),
                    FILTER_VALIDATE_BOOLEAN,
                ) ? 1 : 0,
                'telegram_bot_token' => trim((string) request('telegram_bot_token', '')) ?: null,
                'instagram_notifications_enabled' => filter_var(
                    request('instagram_notifications_enabled', false),
                    FILTER_VALIDATE_BOOLEAN,
                ) ? 1 : 0,
                'instagram_access_token' => trim((string) request('instagram_access_token', '')) ?: null,
                'instagram_account_id' => trim((string) request('instagram_account_id', '')) ?: null,
                'instagram_webhook_verify_token' => trim((string) request('instagram_webhook_verify_token', '')) ?: null,
                'ai_reply_whatsapp_enabled' => filter_var(
                    request('ai_reply_whatsapp_enabled', false),
                    FILTER_VALIDATE_BOOLEAN,
                ) ? 1 : 0,
                'ai_reply_telegram_enabled' => filter_var(
                    request('ai_reply_telegram_enabled', false),
                    FILTER_VALIDATE_BOOLEAN,
                ) ? 1 : 0,
                'ai_reply_instagram_enabled' => filter_var(
                    request('ai_reply_instagram_enabled', false),
                    FILTER_VALIDATE_BOOLEAN,
                ) ? 1 : 0,
                'smtp_host' => trim((string) request('smtp_host', '')) ?: null,
                'smtp_port' => !empty(request('smtp_port')) ? (int) request('smtp_port') : null,
                'smtp_crypto' => trim((string) request('smtp_crypto', '')) ?: null,
                'smtp_user' => trim((string) request('smtp_user', '')) ?: null,
                'smtp_pass' => trim((string) request('smtp_pass', '')) ?: null,
                'smtp_from_name' => trim((string) request('smtp_from_name', '')) ?: null,
                'smtp_from_address' => trim((string) request('smtp_from_address', '')) ?: null,
                'default_notification_channel' => null,
                'default_notification_channels' => implode(',', array_values(array_intersect(
                    ['email', 'sms', 'call', 'whatsapp', 'telegram', 'instagram'],
                    array_map('strval', (array) request('default_notification_channels', [])),
                ))) ?: 'email',
                'reminder_notifications_enabled' => filter_var(
                    request('reminder_notifications_enabled', true),
                    FILTER_VALIDATE_BOOLEAN,
                ) ? 1 : 0,
                'reminder_hours_ahead' => !empty(request('reminder_hours_ahead')) ? (int) request('reminder_hours_ahead') : 24,
                'reminder_offsets' => (array) request('reminder_offsets', []),
            ];

            $this->messaging_settings_model->save_settings($data);

            // Sync Telegram settings to ea_settings so legacy/core consumers stay aligned
            $settings_sync = [
                'telegram_notifications_enabled' => $data['telegram_notifications_enabled'] ? '1' : '0',
                'ai_reply_telegram_enabled' => $data['ai_reply_telegram_enabled'] ? '1' : '0',
            ];
            if (!empty($data['telegram_bot_token'])) {
                $settings_sync['telegram_bot_token'] = $data['telegram_bot_token'];
            }
            setting($settings_sync);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Trigger appointment reminders manually from the UI for immediate test / execution.
     */
    public function run_reminders(): void
    {
        try {
            $this->load->library('appointment_reminders');

            $sent_count = $this->appointment_reminders->run(true);

            $messaging_settings = $this->messaging_settings_model->get_settings();
            $hours_ahead = (int) ($messaging_settings['reminder_hours_ahead'] ?? 24);
            if ($hours_ahead <= 0) {
                $hours_ahead = 24;
            }

            json_response([
                'success' => true,
                'sent_count' => $sent_count,
                'hours_ahead' => $hours_ahead,
                'message' => "{$sent_count} adet randevu hatırlatması gönderildi.",
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
