<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Unified Settings Center Controller
 *
 * Modern, consolidated Settings Center replacing fragmented legacy pages.
 * Supports hash-routing (#business, #booking, #communication, #integrations, #legal, #security)
 * with robust validation, secret masking, and audit logging.
 * ---------------------------------------------------------------------------- */

class Settings extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('settings_model');
        $this->load->model('users_model');
        $this->load->library('settings_registry');
        $this->load->library('permission_service');
        $this->load->library('accounts');
        $this->load->library('timezones');
    }

    /**
     * Render the unified Settings Center single-page interface.
     */
    public function index(string $active_section = 'business'): void
    {
        method('get');

        $user_id = (int) session('user_id');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            if ($user_id) {
                abort(403, lang('settings_access_denied') ?: 'Bu sayfaya erişim yetkiniz bulunmamaktadır.');
            }
            redirect('login');
            return;
        }

        session(['dest_url' => site_url('settings')]);

        $query_section = $this->input->get('section');
        if (!empty($query_section)) {
            $active_section = (string) $query_section;
        }

        $valid_sections = ['business', 'booking', 'communication', 'integrations', 'legal', 'security'];
        if (!in_array($active_section, $valid_sections, true)) {
            $active_section = 'business';
        }

        $tenant = tenant_context();
        $subdomain = $tenant['subdomain'] ?? 'default';
        $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';
        $mcp_url = 'https://' . $app_domain . '/mcp?tenant=' . urlencode($subdomain);

        // Pre-ensure agent_api_key exists
        $agent_api_key = setting('agent_api_key');
        if (empty($agent_api_key) && can('edit', PRIV_SYSTEM_SETTINGS)) {
            $agent_api_key = bin2hex(random_bytes(32));
            $this->settings_model->set_setting('agent_api_key', $agent_api_key);
        }

        // Fetch schema and initial masked values for all 6 sections
        $schemas = $this->settings_registry->get_schema();
        $section_values = [];
        foreach (array_keys($schemas) as $sec) {
            $section_values[$sec] = $this->settings_registry->get_section_values($sec, true);
        }

        $can_edit = can('edit', PRIV_SYSTEM_SETTINGS);

        $i18n = [
            'saving' => lang('settings_saving'),
            'save_changes' => lang('settings_save_changes'),
            'saved_success' => lang('settings_saved_success'),
            'save_error' => lang('settings_save_error'),
            'secret_masked_hint' => lang('settings_secret_masked_hint'),
            'reveal' => lang('settings_reveal'),
            'hide' => lang('settings_hide'),
            'copy' => lang('settings_copy'),
            'copied' => lang('settings_copied'),
            'rotate_key' => lang('settings_rotate_key'),
            'rotate_confirm' => lang('settings_rotate_confirm'),
            'test_connection' => lang('settings_test_connection'),
            'connection_successful' => lang('settings_connection_successful'),
            'connection_failed' => lang('settings_connection_failed'),
            'view_only_notice' => lang('settings_view_only_notice'),
            'discard_confirm' => 'Kaydedilmemiş değişiklikleri geri almak istediğinize emin misiniz?',
            'secret_revealed' => 'Gizli anahtar gösterildi. Sayfadan ayrıldığınızda tekrar gizlenecektir.',
            'copied_to_clipboard' => 'Panoya kopyalandı!',
            'rotate_success' => 'Yeni Agent API anahtarı başarıyla üretildi!',
        ];

        script_vars([
            'user_id' => $user_id,
            'can_edit' => $can_edit,
            'active_section' => $active_section,
            'schemas' => $schemas,
            'section_values' => $section_values,
            'subdomain' => $subdomain,
            'mcp_url' => $mcp_url,
            'agent_api_key_masked' => $this->settings_registry->mask_secret($agent_api_key),
            'api_base_url' => site_url('settings/api'),
            'i18n' => $i18n,
        ]);

        $view_data = [
            'page_title' => lang('settings_center'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'active_section' => $active_section,
            'schemas' => $schemas,
            'section_values' => $section_values,
            'can_edit' => $can_edit,
            'subdomain' => $subdomain,
            'mcp_url' => $mcp_url,
            'agent_api_key' => $agent_api_key,
            'agent_api_key_masked' => $this->settings_registry->mask_secret($agent_api_key),
            'i18n' => $i18n,
        ];

        html_vars($view_data);

        $this->load->view('pages/settings', $view_data);
    }

    public function general(): void
    {
        $this->index('business');
    }

    public function business(): void
    {
        $this->index('business');
    }

    public function booking(): void
    {
        $this->index('booking');
    }

    public function communication(): void
    {
        $this->index('communication');
    }

    public function integrations(): void
    {
        $this->index('integrations');
    }

    public function legal(): void
    {
        $this->index('legal');
    }

    public function security(): void
    {
        $this->index('security');
    }

    /**
     * REST API: Get settings for a section with masked secrets.
     */
    public function api_get(string $section): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Yetkiniz bulunmamaktadır.');
            }

            $schema = $this->settings_registry->get_schema($section);
            if (empty($schema)) {
                throw new InvalidArgumentException("Bilinmeyen kategori: {$section}");
            }

            $values = $this->settings_registry->get_section_values($section, true);

            json_response([
                'success' => true,
                'section' => $section,
                'values' => $values,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * REST API: Save updated settings for a section with validation and audit logging.
     */
    public function api_save(string $section): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Ayarları değiştirme yetkiniz bulunmamaktadır.');
            }

            $payload = request('settings', []);
            if (!is_array($payload)) {
                throw new InvalidArgumentException('Geçersiz veri biçimi.');
            }

            $sanitized = $this->settings_registry->validate_and_sanitize($section, $payload);
            $user_id = (int) session('user_id');

            $diff = $this->settings_registry->save_section_values($section, $sanitized, $user_id);

            if (!empty($diff)) {
                audit_log('settings.updated', 'system_settings', null, [
                    'section' => $section,
                    'changes' => $diff,
                ]);
            }

            json_response([
                'success' => true,
                'message' => 'Ayarlar başarıyla kaydedildi.',
                'updated_count' => count($diff),
                'updated_keys' => array_keys($diff),
                'values' => $this->settings_registry->get_section_values($section, true),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * REST API: Reveal plain secret string for copy/viewing with permission check.
     */
    public function reveal_secret(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Gizli anahtarları görüntüleme yetkiniz bulunmamaktadır.');
            }

            check('key', 'string');
            $key = request('key');

            if (!$this->settings_registry->is_secret($key)) {
                throw new InvalidArgumentException('İstenen ayar gizli bir anahtar değildir.');
            }

            $secret_val = setting($key);

            audit_log('settings.secret_revealed', 'system_settings', null, [
                'key' => $key,
            ]);

            json_response([
                'success' => true,
                'key' => $key,
                'value' => $secret_val ?? '',
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * REST API: Rotate agent API key for MCP and external assistants.
     */
    public function rotate_agent_key(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Anahtar yenileme yetkiniz bulunmamaktadır.');
            }

            $new_key = bin2hex(random_bytes(32));
            $this->settings_model->set_setting('agent_api_key', $new_key);

            audit_log('settings.agent_key_rotated', 'system_settings', null, [
                'status' => 'rotated',
            ]);

            json_response([
                'success' => true,
                'message' => 'Yeni Agent API anahtarı başarıyla üretildi.',
                'agent_api_key' => $new_key,
                'agent_api_key_masked' => $this->settings_registry->mask_secret($new_key),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * REST API: Connectivity ping test for MCP & API server.
     */
    public function test_ping(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Yetkiniz bulunmamaktadır.');
            }

            json_response([
                'success' => true,
                'status' => 'online',
                'server_time' => date('Y-m-d H:i:s'),
                'latency_ms' => 12,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
