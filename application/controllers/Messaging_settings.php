<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Messaging settings controller (2026-08-27).
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
            'sms_gateway' => $settings['sms_gateway'],
            'netgsm_username' => $settings['netgsm_username'] ? '***' : '',
            'netgsm_header' => $settings['netgsm_header'],
            'sms_notifications_enabled' => (bool) $settings['sms_notifications_enabled'],
            'whatsapp_phone_number_id' => $settings['whatsapp_phone_number_id'] ? '***' : '',
            'whatsapp_access_token' => $settings['whatsapp_access_token'] ? '***' : '',
            'whatsapp_waba_id' => $settings['whatsapp_waba_id'] ? '***' : '',
            'whatsapp_webhook_verify_token' => $settings['whatsapp_webhook_verify_token'] ? '***' : '',
            'whatsapp_business_phone_display' => $settings['whatsapp_business_phone_display'],
            'whatsapp_notifications_enabled' => (bool) $settings['whatsapp_notifications_enabled'],
            'smtp_host' => $settings['smtp_host'],
            'smtp_port' => $settings['smtp_port'],
            'smtp_crypto' => $settings['smtp_crypto'],
            'smtp_user' => $settings['smtp_user'] ? '***' : '',
            'smtp_pass' => $settings['smtp_pass'] ? '***' : '',
            'smtp_from_name' => $settings['smtp_from_name'],
            'smtp_from_address' => $settings['smtp_from_address'],
        ];

        html_vars([
            'page_title' => 'SMS ve WhatsApp Ayarları',
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'settings' => $display_settings,
            'webhook_url' => site_url('whatsapp/webhook'),
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
            check('smtp_host', 'string|null');
            check('smtp_port', 'int|null');
            check('smtp_crypto', 'string|null');
            check('smtp_user', 'string|null');
            check('smtp_pass', 'string|null');
            check('smtp_from_name', 'string|null');
            check('smtp_from_address', 'string|null');

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
                'smtp_host' => trim((string) request('smtp_host', '')) ?: null,
                'smtp_port' => !empty(request('smtp_port')) ? (int) request('smtp_port') : null,
                'smtp_crypto' => trim((string) request('smtp_crypto', '')) ?: null,
                'smtp_user' => trim((string) request('smtp_user', '')) ?: null,
                'smtp_pass' => trim((string) request('smtp_pass', '')) ?: null,
                'smtp_from_name' => trim((string) request('smtp_from_name', '')) ?: null,
                'smtp_from_address' => trim((string) request('smtp_from_address', '')) ?: null,
            ];

            $this->messaging_settings_model->save_settings($data);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
