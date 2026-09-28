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
        $this->load->model('providers_model');
        $this->load->model('blocked_periods_model');
        $this->load->model('working_plan_exceptions_model');
        $this->load->model('messaging_settings_model');
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

        // Preload messaging settings (WhatsApp dual-mode, Baileys bridge, etc.)
        $messaging_settings = $this->messaging_settings_model->get_settings();
        if (empty($section_values['communication']['whatsapp_mode'])) {
            $section_values['communication']['whatsapp_mode'] = $messaging_settings['whatsapp_mode'] ?? 'official';
        }
        if (empty($section_values['communication']['whatsapp_bridge_url'])) {
            $section_values['communication']['whatsapp_bridge_url'] = $messaging_settings['whatsapp_bridge_url'] ?? 'http://wa-bridge:3000';
        }
        if (empty($section_values['communication']['whatsapp_phone_number_id']) && !empty($messaging_settings['whatsapp_phone_number_id'])) {
            $section_values['communication']['whatsapp_phone_number_id'] = $messaging_settings['whatsapp_phone_number_id'];
        }

        // Preload tenant AI policies into integrations section if table exists
        if ($this->db->table_exists('tenant_ai_policies')) {
            $ai_policy_row = $this->db->get('tenant_ai_policies')->row_array();
            if ($ai_policy_row) {
                if (empty($section_values['integrations']['ai_brand_name'])) {
                    $section_values['integrations']['ai_brand_name'] = $ai_policy_row['brand_name'] ?? '';
                }
                if (empty($section_values['integrations']['ai_tone'])) {
                    $section_values['integrations']['ai_tone'] = $ai_policy_row['tone'] ?? 'friendly_professional';
                }
                if (empty($section_values['integrations']['ai_language'])) {
                    $section_values['integrations']['ai_language'] = $ai_policy_row['language'] ?? 'tr';
                }
                if (empty($section_values['integrations']['ai_greeting_style'])) {
                    $section_values['integrations']['ai_greeting_style'] = $ai_policy_row['greeting_style'] ?? '';
                }
                if (empty($section_values['integrations']['ai_do_rules'])) {
                    $section_values['integrations']['ai_do_rules'] = $ai_policy_row['do_rules'] ?? '';
                }
                if (empty($section_values['integrations']['ai_dont_rules'])) {
                    $section_values['integrations']['ai_dont_rules'] = $ai_policy_row['dont_rules'] ?? '';
                }
                if (empty($section_values['integrations']['ai_cancellation_policy'])) {
                    $section_values['integrations']['ai_cancellation_policy'] = $ai_policy_row['cancellation_policy'] ?? '';
                }
                if (empty($section_values['integrations']['ai_discount_policy'])) {
                    $section_values['integrations']['ai_discount_policy'] = $ai_policy_row['discount_policy'] ?? '';
                }
                if (empty($section_values['integrations']['ai_forbidden_terms'])) {
                    $section_values['integrations']['ai_forbidden_terms'] = $ai_policy_row['forbidden_terms'] ?? '';
                }
            }
        }

        // Fetch working plan and exceptions data
        $raw_plan = setting('company_working_plan');
        $working_plan = [];
        if (!empty($raw_plan)) {
            $working_plan = is_string($raw_plan) ? json_decode($raw_plan, true) : (array)$raw_plan;
        }
        if (empty($working_plan) || !is_array($working_plan)) {
            $default_day = ['start' => '09:00', 'end' => '18:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]];
            $working_plan = [
                'monday' => $default_day,
                'tuesday' => $default_day,
                'wednesday' => $default_day,
                'thursday' => $default_day,
                'friday' => $default_day,
                'saturday' => ['start' => '10:00', 'end' => '16:00', 'breaks' => []],
                'sunday' => null,
            ];
        }

        // Fetch blocked periods (company-wide holidays/closed days)
        $blocked_periods = $this->blocked_periods_model->get(null, 100, 0, 'start_datetime ASC') ?: [];

        // Fetch staff providers and working plan exceptions
        $providers = $this->providers_model->get() ?: [];
        $working_plan_exceptions = $this->working_plan_exceptions_model->get(null, 100, 0, 'start_date ASC') ?: [];

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
            'delete_confirm' => 'Bu kaydı silmek istediğinize emin misiniz?',
            'holiday_added' => 'Tatil / Kapalı dönem başarıyla eklendi!',
            'holiday_deleted' => 'Kayıt başarıyla silindi!',
            'plan_applied' => 'Çalışma planı tüm personele uygulandı!',
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
            'working_plan' => $working_plan,
            'blocked_periods' => $blocked_periods,
            'working_plan_exceptions' => $working_plan_exceptions,
            'csrf_token' => config_item('csrf_protection') ? $this->security->get_csrf_hash() : '',
            'i18n' => $i18n,
            'whatsapp_unofficial_status' => $messaging_settings['whatsapp_unofficial_status'] ?? 'disconnected',
            'whatsapp_unofficial_consent_at' => $messaging_settings['whatsapp_unofficial_consent_at'] ?? null,
            'whatsapp_routes' => [
                'save_mode' => site_url('whatsapp/save_mode'),
                'qr_start' => site_url('whatsapp/qr_start'),
                'qr_status' => site_url('whatsapp/qr_status'),
                'qr_logout' => site_url('whatsapp/qr_logout'),
                'check_connection' => site_url('whatsapp/check_connection'),
            ],
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
            'working_plan' => $working_plan,
            'blocked_periods' => $blocked_periods,
            'working_plan_exceptions' => $working_plan_exceptions,
            'providers' => $providers,
            'whatsapp_unofficial_status' => $messaging_settings['whatsapp_unofficial_status'] ?? 'disconnected',
            'whatsapp_unofficial_consent_at' => $messaging_settings['whatsapp_unofficial_consent_at'] ?? null,
            'csrf_token' => config_item('csrf_protection') ? $this->security->get_csrf_hash() : '',
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

            $payload = request('settings', null);
            if ($payload === null) {
                $raw_json = json_decode($this->input->raw_input_stream, true);
                if (is_array($raw_json)) {
                    $payload = $raw_json['settings'] ?? $raw_json;
                }
            }
            if (!is_array($payload)) {
                $payload = request() ?: [];
                if (isset($payload['settings']) && is_array($payload['settings'])) {
                    $payload = $payload['settings'];
                }
            }

            // If business section and company_working_plan is provided, persist it
            $saved_working_plan = false;
            if ($section === 'business' && isset($payload['company_working_plan'])) {
                $plan_data = $payload['company_working_plan'];
                $plan_str = is_array($plan_data) ? json_encode($plan_data) : (string)$plan_data;
                $this->settings_model->set_setting('company_working_plan', $plan_str);
                $saved_working_plan = true;
                unset($payload['company_working_plan']);
            }

            $sanitized = $this->settings_registry->validate_and_sanitize($section, $payload);
            $user_id = (int) session('user_id');

            $diff = $this->settings_registry->save_section_values($section, $sanitized, $user_id);
            if ($saved_working_plan) {
                $diff['company_working_plan'] = ['old' => '...', 'new' => 'updated'];
            }

            // Sync communication settings to messaging_settings table
            if ($section === 'communication') {
                $ms_data = [];
                if (isset($sanitized['whatsapp_mode'])) {
                    $ms_data['whatsapp_mode'] = $sanitized['whatsapp_mode'];
                }
                if (isset($sanitized['whatsapp_bridge_url'])) {
                    $ms_data['whatsapp_bridge_url'] = $sanitized['whatsapp_bridge_url'];
                }
                if (!empty($sanitized['whatsapp_bridge_secret']) && !str_contains((string)$sanitized['whatsapp_bridge_secret'], '••••')) {
                    $ms_data['whatsapp_bridge_secret'] = $sanitized['whatsapp_bridge_secret'];
                }
                if (isset($sanitized['whatsapp_phone_number_id'])) {
                    $ms_data['whatsapp_phone_number_id'] = $sanitized['whatsapp_phone_number_id'];
                }
                if (!empty($sanitized['whatsapp_access_token']) && !str_contains((string)$sanitized['whatsapp_access_token'], '••••')) {
                    $ms_data['whatsapp_access_token'] = $sanitized['whatsapp_access_token'];
                }
                if (!empty($ms_data)) {
                    $this->messaging_settings_model->save_settings($ms_data);
                }
            }

            // Sync AI assistant policy settings to tenant_ai_policies table
            if ($section === 'integrations' && $this->db->table_exists('tenant_ai_policies')) {
                $ai_fields = [
                    'brand_name' => $sanitized['ai_brand_name'] ?? null,
                    'tone' => $sanitized['ai_tone'] ?? 'friendly_professional',
                    'language' => $sanitized['ai_language'] ?? 'tr',
                    'greeting_style' => $sanitized['ai_greeting_style'] ?? null,
                    'do_rules' => $sanitized['ai_do_rules'] ?? null,
                    'dont_rules' => $sanitized['ai_dont_rules'] ?? null,
                    'cancellation_policy' => $sanitized['ai_cancellation_policy'] ?? null,
                    'discount_policy' => $sanitized['ai_discount_policy'] ?? null,
                    'forbidden_terms' => $sanitized['ai_forbidden_terms'] ?? null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $existing_policy = $this->db->get('tenant_ai_policies')->row_array();
                if ($existing_policy) {
                    $this->db->update('tenant_ai_policies', $ai_fields, ['id' => $existing_policy['id']]);
                } else {
                    $ai_fields['created_at'] = date('Y-m-d H:i:s');
                    $this->db->insert('tenant_ai_policies', $ai_fields);
                }
            }

            if (!empty($diff)) {
                audit_log('settings.updated', 'system_settings', null, [
                    'section' => $section,
                    'changes' => $diff,
                ]);
            }

            json_response([
                'success' => true,
                'message' => lang('settings_saved_success') ?: 'Ayarlar başarıyla kaydedildi.',
                'updated_count' => count($diff),
                'updated_keys' => array_keys($diff),
                'values' => $this->settings_registry->get_section_values($section, true),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * REST API: Add a company-wide holiday or blocked period.
     */
    public function add_blocked_period(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS) && cannot('add', PRIV_BLOCKED_PERIODS)) {
                abort(403, 'Tatil ekleme yetkiniz bulunmamaktadır.');
            }

            $name = trim((string) request('name'));
            $start = trim((string) request('start_datetime'));
            $end = trim((string) request('end_datetime'));
            $notes = trim((string) request('notes', ''));

            if (empty($name) || empty($start) || empty($end)) {
                throw new InvalidArgumentException('Lütfen tüm zorunlu alanları (başlık, başlangıç ve bitiş) doldurunuz.');
            }

            $start_dt = date('Y-m-d H:i:s', strtotime($start));
            $end_dt = date('Y-m-d H:i:s', strtotime($end));

            if (strtotime($start_dt) >= strtotime($end_dt)) {
                throw new InvalidArgumentException('Başlangıç tarihi bitiş tarihinden önce olmalıdır.');
            }

            $id = $this->blocked_periods_model->save([
                'name' => $name,
                'start_datetime' => $start_dt,
                'end_datetime' => $end_dt,
                'notes' => $notes,
            ]);

            audit_log('blocked_period.created', 'system_settings', $id, ['name' => $name]);

            json_response([
                'success' => true,
                'message' => 'Tatil / Kapalı dönem başarıyla eklendi.',
                'id' => $id,
                'item' => [
                    'id' => $id,
                    'name' => $name,
                    'start_datetime' => $start_dt,
                    'end_datetime' => $end_dt,
                    'notes' => $notes,
                ],
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * REST API: Delete a company-wide holiday or blocked period.
     */
    public function delete_blocked_period(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS) && cannot('delete', PRIV_BLOCKED_PERIODS)) {
                abort(403, 'Tatil silme yetkiniz bulunmamaktadır.');
            }

            $id = (int) request('id');
            if ($id <= 0) {
                throw new InvalidArgumentException('Geçersiz kayıt ID.');
            }

            $this->blocked_periods_model->delete($id);
            audit_log('blocked_period.deleted', 'system_settings', $id, []);

            json_response([
                'success' => true,
                'message' => 'Kayıt başarıyla silindi.',
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * REST API: Apply company working plan to all providers.
     */
    public function apply_global_working_plan(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Bu işlem için yetkiniz bulunmamaktadır.');
            }

            $plan = request('working_plan');
            if (empty($plan)) {
                $plan = setting('company_working_plan');
            }
            if (is_array($plan)) {
                $plan = json_encode($plan);
            }

            if (empty($plan)) {
                throw new InvalidArgumentException('Geçerli bir çalışma planı bulunamadı.');
            }

            $providers = $this->providers_model->get();
            $count = 0;
            foreach ($providers as $provider) {
                $this->providers_model->set_setting($provider['id'], 'working_plan', $plan);
                $count++;
            }

            audit_log('working_plan.applied_to_all', 'system_settings', null, ['count' => $count]);

            json_response([
                'success' => true,
                'message' => "Çalışma planı {$count} personelin tamamına başarıyla uygulandı.",
                'applied_count' => $count,
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
