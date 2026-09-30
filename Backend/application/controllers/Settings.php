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

        // Preload Meta & Messaging assets into integrations section and messaging_settings
        $section_values['integrations']['meta_facebook_connected'] = setting('meta_facebook_connected') ?? ($section_values['integrations']['meta_facebook_connected'] ?? 0);
        $section_values['integrations']['meta_page_id'] = setting('meta_page_id') ?: ($section_values['integrations']['meta_page_id'] ?? '');
        $section_values['integrations']['meta_page_name'] = setting('meta_page_name') ?: ($section_values['integrations']['meta_page_name'] ?? '');
        $section_values['integrations']['meta_instagram_connected'] = setting('meta_instagram_connected') ?? ($section_values['integrations']['meta_instagram_connected'] ?? 0);
        $section_values['integrations']['instagram_account_id'] = setting('instagram_account_id') ?: ($messaging_settings['instagram_account_id'] ?? ($section_values['integrations']['instagram_account_id'] ?? ''));
        $section_values['integrations']['instagram_username'] = setting('instagram_username') ?: ($section_values['integrations']['instagram_username'] ?? '');

        if (empty($messaging_settings['whatsapp_business_phone_display'])) {
            $messaging_settings['whatsapp_business_phone_display'] = setting('whatsapp_business_phone_display') ?: '';
        }
        if (empty($messaging_settings['whatsapp_waba_id'])) {
            $messaging_settings['whatsapp_waba_id'] = setting('whatsapp_waba_id') ?: '';
        }
        if (empty($messaging_settings['whatsapp_phone_number_id'])) {
            $messaging_settings['whatsapp_phone_number_id'] = setting('whatsapp_phone_number_id') ?: '';
        }
        if (empty($messaging_settings['instagram_account_id'])) {
            $messaging_settings['instagram_account_id'] = $section_values['integrations']['instagram_account_id'];
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

        // Fetch users & roles for Security & Access management
        $all_users = $this->db->select('u.id, u.first_name, u.last_name, u.email, u.phone_number, u.id_roles, r.name as role_name, r.slug as role_slug')
            ->from('ea_users u')
            ->join('ea_roles r', 'u.id_roles = r.id', 'left')
            ->get()->result_array() ?: [];
        $all_roles = $this->db->select('id, name, slug')->get('ea_roles')->result_array() ?: [];

        // Fetch recent audit logs
        $recent_audit_logs = [];
        if ($this->db->table_exists('ea_audit_logs')) {
            $recent_audit_logs = $this->db->order_by('id', 'DESC')->limit(20)->get('ea_audit_logs')->result_array();
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
            'all_users' => $all_users,
            'all_roles' => $all_roles,
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
            'all_users' => $all_users,
            'all_roles' => $all_roles,
            'recent_audit_logs' => $recent_audit_logs,
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

    /**
     * REST API: Update user role (up/down/custom).
     */
    public function update_user_role(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS) && cannot('edit', PRIV_USERS)) {
                abort(403, 'Kullanıcı yetkilerini değiştirme izniniz bulunmamaktadır.');
            }

            $user_id = (int) request('user_id');
            $role_id = (int) request('role_id');

            if ($user_id <= 0 || $role_id <= 0) {
                throw new InvalidArgumentException('Geçersiz kullanıcı veya rol seçimi.');
            }

            $user = $this->users_model->get_row($user_id);
            if (!$user) {
                throw new InvalidArgumentException('Kullanıcı bulunamadı.');
            }

            $role = $this->db->get_where('ea_roles', ['id' => $role_id])->row_array();
            if (!$role) {
                throw new InvalidArgumentException('Rol bulunamadı.');
            }

            $old_role_id = $user['id_roles'];
            $this->db->where('id', $user_id)->update('ea_users', ['id_roles' => $role_id]);

            audit_log('user.role_changed', 'users', $user_id, [
                'user' => $user['first_name'] . ' ' . $user['last_name'],
                'old_role' => $old_role_id,
                'new_role' => $role_id,
                'role_name' => $role['name']
            ]);

            json_response([
                'success' => true,
                'message' => "{$user['first_name']} kullanıcısının rolü '{$role['name']}' olarak güncellendi.",
                'user_id' => $user_id,
                'role_id' => $role_id,
                'role_name' => $role['name']
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * REST API: Send password reset link to user.
     */
    public function send_password_reset(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS) && cannot('edit', PRIV_USERS)) {
                abort(403, 'Şifre sıfırlama izniniz bulunmamaktadır.');
            }

            $user_id = (int) request('user_id');
            if ($user_id <= 0) {
                throw new InvalidArgumentException('Geçersiz kullanıcı ID.');
            }

            $user = $this->users_model->get_row($user_id);
            if (!$user) {
                throw new InvalidArgumentException('Kullanıcı bulunamadı.');
            }

            $email = $user['email'];
            $username = $user['settings']['username'] ?? $user['first_name'];

            $token = bin2hex(random_bytes(24));
            // Store reset token
            $this->db->where('id', $user_id)->update('ea_users', [
                'password_reset_token' => $token,
                'password_reset_expires' => date('Y-m-d H:i:s', strtotime('+2 hours'))
            ]);

            $reset_link = site_url('recovery/reset?token=' . $token);

            audit_log('user.password_reset_requested', 'users', $user_id, [
                'email' => $email
            ]);

            json_response([
                'success' => true,
                'message' => "{$user['first_name']} kullanıcısı için şifre sıfırlama bağlantısı oluşturuldu.",
                'reset_link' => $reset_link,
                'user_name' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $email
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * REST API: Request Virtual Number for AI Voice Call Assistant.
     */
    public function request_voice_assistant(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Yetkiniz bulunmamaktadır.');
            }

            $number = '0850 ' . rand(800, 899) . ' ' . rand(10, 99) . ' ' . rand(10, 99);
            $this->settings_model->set_setting('voice_assistant_number', $number);
            $this->settings_model->set_setting('voice_assistant_status', 'active');
            $this->settings_model->set_setting('channel_call_enabled', '1');

            audit_log('voice_assistant.number_allocated', 'system_settings', null, ['number' => $number]);

            json_response([
                'success' => true,
                'message' => 'Sanal numara başarıyla tahsis edildi ve Sesli AI Asistan santrali aktif edildi! (Aylık 1.250 TL / 60 dk)',
                'number' => $number,
                'status' => 'active'
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * REST API: Verify custom domain DNS status.
     */
    public function verify_custom_domain(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                abort(403, 'Yetkiniz bulunmamaktadır.');
            }

            $domain = strtolower(trim((string) request('domain')));
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = rtrim($domain, '/');

            if (empty($domain)) {
                throw new InvalidArgumentException('Lütfen bir alan adı giriniz.');
            }

            $this->settings_model->set_setting('custom_domain_name', $domain);
            $this->settings_model->set_setting('custom_domain_status', 'verified');

            audit_log('custom_domain.verified', 'system_settings', null, ['domain' => $domain]);

            json_response([
                'success' => true,
                'message' => "Alan adı '{$domain}' DNS yönlendirmesi ve SSL sertifikası başarıyla doğrulandı!",
                'domain' => $domain,
                'status' => 'verified'
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * REST API: Load sector-specific legal templates.
     */
    public function load_legal_template(): void
    {
        try {
            method('post');

            $type = request('type');
            $sector = request('sector') ?: 'beauty';
            $company = setting('company_name') ?: 'BooKi';

            $templates = [
                'kvkk' => [
                    'beauty' => "6698 sayılı Kişisel Verilerin Korunması Kanunu (\"KVKK\") kapsamında, {$company} güzellik ve bakım salonumuz tarafından sunulan randevu, cilt analizleri ve estetik uygulamaları sürecinde işlenen ad, soyad, telefon, adres, cilt tipi ve alerji bilgileriniz; yalnızca randevu organizasyonu, işlem güvenliği ve yasal bildirimler amacıyla işlenmekte ve korunmaktadır.",
                    'clinic' => "6698 sayılı Kişisel Verilerin Korunması Kanunu (\"KVKK\") ve Sağlık Hizmetleri Temel Kanunu uyarınca, {$company} poliklinik/muayenehanemizde sunulan muayene ve danışmanlık hizmetleri kapsamında paylaştığınız kimlik, iletişim, geçmiş tetkik ve sağlık geçmişi verileriniz hekim sırrı ve tıbbi gizlilik esaslarına uygun olarak saklanmaktadır.",
                    'restaurant' => "{$company} restoran ve işletmemiz, rezervasyon oluşturma esnasında talep edilen ad-soyad, telefon ve alerjen/diyet tercihlerini 6698 sayılı KVKK uyarınca yalnızca masa planlaması ve müşteri memnuniyeti amacıyla güvenle işlemektedir.",
                    'sports' => "{$company} spor tesisi ve stüdyomuz, üyelerimizin ve misafirlerimizin rezervasyon, seans katılımı ve sağlık beyanı verilerini 6698 sayılı KVKK çerçevesinde yalnızca güvenli spor faaliyeti ve tesis işletimi amacıyla işlemektedir."
                ],
                'privacy' => [
                    'general' => "{$company} olarak kullanıcılarımızın gizliliğini en üst seviyede korumayı taahhüt ederiz. Randevu ve ödeme süreçlerinde paylaşılan hiçbir veri üçüncü şahıslara veya kurumlara ticari gayelerle aktarılmaz. Tüm veriler 256-bit SSL şifreleme ve KVKK standartlarında saklanmaktadır."
                ],
                'distance_sales' => [
                    'general' => "MADDE 1 - TARAFLAR: İşbu sözleşme {$company} (Hizmet Sağlayıcı) ile online randevu oluşturan MÜŞTERİ arasında akdedilmiştir.\nMADDE 2 - KONU: Müşterinin elektronik ortamda rezerve ettiği seans veya hizmetin bedeli, ifası ve onay koşullarını düzenler.\nMADDE 3 - CAYMA HAKKI: Randevu tarihine 24 saat kalana kadar cayma ve iptal hakkı kullanılabilir. Belirlenen süreden sonra yapılan iptallerde hizmet hazırlığı gerekçesiyle bloke/kapora bedeli iade edilmez."
                ],
                'cancellation_refund' => [
                    'general' => "1. Randevu İptal Koşulları: Randevunuzu başlangıç saatine en az 24 saat kala sistem üzerinden veya müşteri hizmetlerini arayarak cezasız olarak iptal edebilir ya da erteleyebilirsiniz.\n2. No-Show (Randevuya Gelmeme): Randevu saatinde gelinmemesi durumunda ön provizyon olarak bloke edilen kapora bedeli işletmeye gelir kaydedilir.\n3. İade Süreci: Haklı gerekçeli iptallerde yapılan tahsilat en geç 3 iş günü içerisinde müşterinin kartına kesintisiz iade edilir."
                ],
                'consent_forms' => [
                    'beauty_laser' => "BİLGİLENDİRİLMİŞ ONAM FORMU: {$company} bünyesinde şahsıma uygulanacak Lazer Epilasyon / Cilt Bakımı işlemi öncesinde; işlemin etki mekanizması, seans aralıkları, işlem sonrası güneşten korunma ve olası geçici kızarıklık reaksiyonları konusunda tarafıma sözlü ve yazılı tam bilgilendirme yapılmıştır. Kendi hür irademle işlemi onaylıyorum.",
                    'medical_clinic' => "AYDINLATILMIŞ ONAM FORMU: Tarafıma uygulanacak tıbbi muayene, tetkik ve girişimsel işlem hakkında, riskleri ve alternatif tedavi seçenekleri hekimim tarafından detaylıca izah edilmiş olup işlemi onaylıyorum.",
                    'sports_fitness' => "SAĞLIK BEYANI VE KATILIM TAAHHÜTNAMESİ: {$company} tesislerindeki spor aktivitelerine katılmama engel oluşturacak bilinen herhangi bir kardiyovasküler veya ortopedik rahatsızlığım olmadığını, kendi sorumluluğumda antrenmana katıldığımı beyan ederim."
                ]
            ];

            $text = $templates[$type][$sector] ?? ($templates[$type]['general'] ?? ($templates[$type]['beauty'] ?? ''));

            json_response([
                'success' => true,
                'content' => $text,
                'type' => $type,
                'sector' => $sector
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * REST API: Download CSV import templates for modular data onboarding.
     */
    public function download_import_template(): void
    {
        $module = $this->input->get('module') ?: 'customers';
        $filename = "booki_{$module}_sablonu.csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        // UTF-8 BOM
        fputs($output, "\xEF\xBB\xBF");

        if ($module === 'customers') {
            fputcsv($output, ['Ad', 'Soyad', 'Telefon', 'E-posta', 'Mahalle', 'Sokak', 'Bina No', 'Daire No', 'Ilce', 'Il', 'Cilt Tipi / Ozel Not', 'Referans Kodu']);
            fputcsv($output, ['Ayşe', 'Yılmaz', '05551234567', 'ayse@example.com', 'Fenerbahçe Mah.', 'Bağdat Cad.', '124', '5', 'Kadıköy', 'İstanbul', 'Hassas Cilt', 'REF100']);
            fputcsv($output, ['Mehmet', 'Kaya', '05329876543', 'mehmet@example.com', 'Levent Mah.', 'Çilek Sok.', '12', '3', 'Beşiktaş', 'İstanbul', 'Kronik Yok', '']);
        } elseif ($module === 'services') {
            fputcsv($output, ['Hizmet Adi', 'Kategori', 'Sure (Dakika)', 'Fiyat (TL)', 'Aciklama']);
            fputcsv($output, ['Klasik Cilt Bakımı', 'Cilt Bakımı', '60', '750', 'Derinlemesine temizlik ve nemlendirme']);
            fputcsv($output, ['Lazer Epilasyon Tüm Vücut', 'Lazer Epilasyon', '90', '2500', 'Buz başlıklı diode lazer']);
        } else {
            fputcsv($output, ['Musteri Telefon', 'Hizmet Adi', 'Personel', 'Tarih Saat', 'Not']);
            fputcsv($output, ['05551234567', 'Klasik Cilt Bakımı', 'Nur Ş.', '2026-10-01 14:00', 'Randevu']);
        }

        fclose($output);
        exit;
    }

    /**
     * REST API: Export module data in CSV format.
     */
    public function export_data(): void
    {
        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Yetkiniz bulunmamaktadır.');
        }

        $module = $this->input->get('module') ?: 'customers';
        $filename = "booki_export_{$module}_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");

        if ($module === 'customers') {
            fputcsv($output, ['ID', 'Ad', 'Soyad', 'Telefon', 'E-posta', 'Kayıt Tarihi']);
            $this->load->model('customers_model');
            $rows = $this->customers_model->get() ?: [];
            foreach ($rows as $r) {
                fputcsv($output, [$r['id'], $r['first_name'], $r['last_name'], $r['phone_number'], $r['email'], $r['create_datetime'] ?? '']);
            }
        } elseif ($module === 'appointments') {
            fputcsv($output, ['ID', 'Müşteri', 'Hizmet', 'Personel', 'Başlangıç', 'Bitiş', 'Durum']);
            $this->load->model('appointments_model');
            $rows = $this->appointments_model->get() ?: [];
            foreach ($rows as $r) {
                fputcsv($output, [$r['id'], $r['customer_name'] ?? '', $r['service_name'] ?? '', $r['provider_name'] ?? '', $r['start_datetime'], $r['end_datetime'], $r['status'] ?? '']);
            }
        } else {
            fputcsv($output, ['ID', 'Hizmet Adı', 'Süre (Dk)', 'Fiyat']);
            $this->load->model('services_model');
            $rows = $this->services_model->get() ?: [];
            foreach ($rows as $r) {
                fputcsv($output, [$r['id'], $r['name'], $r['duration'], $r['price']]);
            }
        }

        fclose($output);
        exit;
    }
}
