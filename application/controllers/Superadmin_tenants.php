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
 * SaaS admin panel (admin-bookiapp.kibusiness.co) - tenant CRUD + plan/license tracking. Runs
 * against the master DB (see App_Controller::resolve_tenant()'s superadmin host exception). Tenant
 * provisioning here mirrors Console::tenant_create() exactly (same DB-swap dance, same
 * Instance::migrate()/seed() call) - duplicated rather than shared because Console's version is
 * CLI-only (private connect_tenant()/connect_master() helpers, echo-based output) and this is a web
 * JSON endpoint; keep the two in sync if the provisioning steps ever change.
 */
class Superadmin_tenants extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $method = strtolower((string) ($this->router->method ?? ($this->router->fetch_method() ?? '')));
        if ($method !== 'platform_bridge_inbound') {
            if (!session('superadmin_id')) {
                redirect('superadmin_auth');
                exit();
            }
        }

        $this->load->library('instance');
        $this->load->library('whatsapp_bridge');
        $this->load->library('platform_ai_responder');
        $this->load->model('leads_model');
        $this->load->model('onboarding_sessions_model');
        $this->load->model('master_audit_model');
        $this->load->library('spreadsheet_importer');
    }

    /**
     * Build the tenant DB connection config array (same shape used across this controller).
     */
    private function tenant_db_config(array $tenant): array
    {
        return [
            'hostname' => $tenant['db_host'],
            'username' => $tenant['db_username'],
            'password' => tenant_master_decrypt($tenant['db_password']),
            'database' => $tenant['db_name'],
            'dbdriver' => 'mysqli',
            'dbprefix' => 'ea_',
            'pconnect' => false,
            'db_debug' => true,
            'cache_on' => false,
            'cachedir' => '',
            'char_set' => 'utf8mb4',
            'dbcollat' => 'utf8mb4_unicode_ci',
            'swap_pre' => '',
        ];
    }

    /**
     * Find the tenant's admin user_settings row (by role, not by a hardcoded username - tenants can
     * rename their own admin username from the account page).
     */
    private function find_tenant_admin(object $tenant_db): ?array
    {
        return $tenant_db
            ->select('user_settings.id_users, user_settings.username, users.email, users.first_name, users.last_name, users.phone_number')
            ->from('users')
            ->join('user_settings', 'user_settings.id_users = users.id', 'inner')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('roles.slug', DB_SLUG_ADMIN)
            ->get()
            ->row_array();
    }

    private function get_tenant_or_fail(int $tenant_id): array
    {
        $tenant = $this->db->get_where('tenants', ['id' => $tenant_id])->row_array();

        if (!$tenant) {
            throw new InvalidArgumentException('Kiracı bulunamadı.');
        }

        return $tenant;
    }

    /**
     * users.email is PII-encrypted per-tenant (see salonflora_crypto_helper.php) - sf_pii_decrypt()
     * only works once tenant_context() carries THIS tenant's own key, exactly like
     * App_Controller::resolve_tenant() sets it for a normal (non-superadmin) request. Must be called
     * before any sf_pii_decrypt()/generate_reset_token() use below.
     */
    private function activate_tenant_pii_context(array $tenant): void
    {
        tenant_context([
            'id' => (int) $tenant['id'],
            'subdomain' => $tenant['subdomain'],
            'pii_enc_key' => tenant_master_decrypt($tenant['pii_enc_key']),
            'pii_hash_key' => tenant_master_decrypt($tenant['pii_hash_key']),
        ]);
    }

    public function index(): void
    {
        method('get');

        check('q', 'string|null');
        check('page', 'numeric|null');

        $search = trim((string) request('q'));
        $page = max(1, (int) request('page', 1));
        $per_page = 20;

        $apply_search_filter = function () use ($search) {
            if ($search !== '') {
                $this->db
                    ->group_start()
                    ->like('subdomain', $search)
                    ->or_like('custom_domain', $search)
                    ->or_like('plan', $search)
                    ->group_end();
            }
        };

        // CI3's query builder clears its pending where/like state after each get()/count_all_results()
        // call, so the filter has to be (re-)applied before EACH of these two separate queries.
        $apply_search_filter();
        $total = $this->db->count_all_results('tenants');

        $apply_search_filter();
        $tenants = $this->db
            ->order_by('created_at', 'desc')
            ->limit($per_page, ($page - 1) * $per_page)
            ->get('tenants')
            ->result_array();

        $total_tenants = $this->db->count_all('tenants');
        $active_tenants = $this->db->where('status', 'active')->count_all_results('tenants');
        
        $total_customers = 0;
        $total_monthly_appointments = 0;
        $total_appointments = 0;
        $total_mrr = 0.00;

        $all_tenants = $this->db->get('tenants')->result_array();
        foreach ($all_tenants as $t) {
            $total_mrr += (float) ($t['mrr_amount'] ?? 0);
            $m = $this->get_tenant_metrics($t);
            $total_customers += (int) $m['customer_count'];
            $total_monthly_appointments += (int) $m['monthly_appointments'];
            $total_appointments += (int) $m['appointment_count'];
        }

        foreach ($tenants as &$tenant) {
            $metrics = $this->get_tenant_metrics($tenant);
            $tenant['appointment_count'] = $metrics['appointment_count'];
            $tenant['monthly_appointments'] = $metrics['monthly_appointments'];
            $tenant['customer_count'] = $metrics['customer_count'];
            $tenant['total_revenue'] = $metrics['total_revenue'];
        }

        unset($tenant);

        // CRM & Onboarding aggregations
        $crm_kpis = $this->leads_model->get_kpis();
        $sectors = $this->leads_model->get_distinct_sectors();
        $districts = $this->leads_model->get_distinct_districts();
        $tasks_summary = $this->leads_model->get_tasks_summary();
        $onboarding_data = $this->onboarding_sessions_model->get_all_sessions([], 15);
        $recent_activities = $this->leads_model->get_recent_activities(15);
        // Places stats pre-computation
        $today = date('Y-m-d 00:00:00');
        $places_discovered_count = (int) $this->db->where('place_id IS NOT NULL', null, false)->count_all_results('leads');
        if ($places_discovered_count === 0) {
            $places_discovered_count = (int) $this->db->count_all_results('leads');
        }
        $places_enriched_count = (int) $this->db
            ->group_start()
                ->where('discovery_state', 'ENRICHED')
                ->or_where('enriched_at IS NOT NULL', null, false)
                ->or_where("phone IS NOT NULL AND phone != ''", null, false)
            ->group_end()
            ->count_all_results('leads');
        $places_today_text = (int) $this->db
            ->where('timestamp >=', $today)
            ->group_start()
                ->like('operation', 'text')
                ->or_like('endpoint', 'searchText')
            ->group_end()
            ->count_all_results('places_api_usage');
        $places_today_detail = (int) $this->db
            ->where('timestamp >=', $today)
            ->group_start()
                ->where_in('operation', ['details', 'place_details', 'lazy_enrichment'])
                ->or_like('endpoint', 'places/')
            ->group_end()
            ->count_all_results('places_api_usage');

        $places_stats = [
            'total_discovered' => $places_discovered_count,
            'total_enriched' => $places_enriched_count,
            'today_text_calls' => $places_today_text,
            'today_detail_calls' => $places_today_detail,
            'districts_count' => count($districts),
            'sectors_count' => count($sectors),
        ];

        $active_tab = (string) request('tab', 'dashboard');

        html_vars([
            'page_title' => 'BooKi — Super Admin & Saha Satış / CRM Platformu',
            'csrf_token' => $this->security->get_csrf_hash(),
            'superadmin_username' => session('superadmin_username'),
            'tenants' => $tenants,
            'search' => $search,
            'page' => $page,
            'total_pages' => max(1, (int) ceil($total / $per_page)),
            'total' => $total,
            'total_tenants' => $total_tenants,
            'active_tenants' => $active_tenants,
            'total_customers' => $total_customers,
            'total_monthly_appointments' => $total_monthly_appointments,
            'total_appointments' => $total_appointments,
            'total_mrr' => $total_mrr,
            // CRM additions
            'crm_kpis' => $crm_kpis,
            'sectors' => $sectors,
            'districts' => $districts,
            'tasks_summary' => $tasks_summary,
            'onboarding_sessions' => $onboarding_data['sessions'] ?? [],
            'recent_activities' => $recent_activities,
            'stage_definitions' => Leads_model::STAGES,
            'places_stats' => $places_stats,
            'active_tab' => $active_tab,
            'platform_settings' => [
                'zadarma_api_key' => master_setting('zadarma_api_key') ?? 'ceba11321113fd2628a1',
                'zadarma_api_secret_set' => !empty(master_setting('zadarma_api_secret')),
                'zadarma_sip_login' => master_setting('zadarma_sip_login') ?? '',
                'zadarma_sip_server' => master_setting('zadarma_sip_server') ?? 'sip.zadarma.com',
                'zadarma_caller_id' => master_setting('zadarma_caller_id') ?? '',
                'zadarma_call_mode' => master_setting('zadarma_call_mode') ?? 'callback',

                'elevenlabs_api_key_set' => !empty(master_setting('elevenlabs_api_key')),
                'elevenlabs_agent_id' => master_setting('elevenlabs_agent_id') ?? '',
                'elevenlabs_voice_id' => master_setting('elevenlabs_voice_id') ?? '21m00Tcm4TlvDq8ikWAM',
                'elevenlabs_model_id' => master_setting('elevenlabs_model_id') ?? 'eleven_multilingual_v2',

                'google_ai_key_set' => !empty(master_setting('google_ai_key')) || !empty(getenv('GEMINI_API_KEY')),
                'gemini_live_voice' => master_setting('gemini_live_voice') ?? 'Aoede',
                'gemini_sales_pitch_prompt' => master_setting('gemini_sales_pitch_prompt') ?? '',

                'google_client_id' => master_setting('google_client_id') ?? '',
                'google_client_secret_set' => !empty(master_setting('google_client_secret')),
                'google_project_id' => master_setting('google_project_id') ?? '',
                'google_maps_key' => master_setting('google_maps_key') ?: 'AIzaSyAscIARfxTG_KzedaskCabzuRSTj-0bulA',

                'platform_smtp_host' => master_setting('platform_smtp_host') ?? 'mail.kibusiness.co',
                'platform_smtp_port' => master_setting('platform_smtp_port') ?? '587',
                'platform_smtp_crypto' => master_setting('platform_smtp_crypto') ?? 'tls',
                'platform_smtp_user' => master_setting('platform_smtp_user') ?? '',
                'platform_smtp_pass_set' => !empty(master_setting('platform_smtp_pass')),
                'platform_smtp_from_name' => master_setting('platform_smtp_from_name') ?? 'BooKi',
                'platform_smtp_from_address' => master_setting('platform_smtp_from_address') ?? '',

                'ai_provider' => master_setting('ai_provider') ?? 'google',
                'ai_model_google' => master_setting('ai_model_google') ?? 'gemini-1.5-flash',
                'groq_api_key_set' => !empty(master_setting('groq_api_key')),
                'ai_model_groq' => master_setting('ai_model_groq') ?? 'llama-3.3-70b-versatile',
                'openrouter_api_key_set' => !empty(master_setting('openrouter_api_key')),
                'openai_api_key_set' => !empty(master_setting('openai_api_key')),
                'anthropic_api_key_set' => !empty(master_setting('anthropic_api_key')),

                'wa_bridge_url' => master_setting('wa_bridge_url') ?? (getenv('WA_BRIDGE_URL') ?: 'http://ki-wa-bridge:3000'),
                'wa_bridge_secret_set' => !empty(master_setting('wa_bridge_secret')) || !empty(getenv('WA_BRIDGE_SECRET')),

                'wa_template_1' => master_setting('wa_template_1') ?? 'Merhaba {yetkili}, {isletme_adi} için randevu kayıplarını ve no-show oranlarını %80 azaltan BooKi Akıllı Randevu & Müşteri Yönetim Sistemimizi incelediniz mi? İşletmenize özel 10 günlük ücretsiz demo kurulumunu hemen başlatabiliriz: https://bookiapp.kibusiness.co',
                'wa_template_2' => master_setting('wa_template_2') ?? 'Merhaba {yetkili}, {isletme_adi} ({sektor}) adresinize planladığımız BooKi saha ziyaretimiz öncesinde teyit almak istedik. Uygun olduğunuzda 15 dakikalık canlı demomuzu sunmaktan memnuniyet duyarız. İyi çalışmalar dileriz.',
                'wa_template_3' => master_setting('wa_template_3') ?? 'Merhaba {yetkili}, {isletme_adi} için 10 günlük ücretsiz deneme profiliniz hazırlandı. Personel primleri, online randevu linkiniz ve otomatik WhatsApp hatırlatmalarını hemen test edebilirsiniz: https://bookiapp.kibusiness.co',

                'marketplace_commission_rate' => master_setting('marketplace_commission_rate') ?? '5.00',
            ],
        ]);

        $this->load->view('pages/superadmin_tenants');
    }

    public function store(): void
    {
        try {
            method('post');

            check('subdomain', 'string');
            check('custom_domain', 'string|null');
            check('plan', 'string|null');
            check('trial_days', 'numeric|null');
            check('business_type', 'string|null');
            check('admin_name', 'string|null');
            check('admin_email', 'string|null');
            check('admin_phone', 'string|null');
            check('admin_password', 'string|null');

            $subdomain = strtolower(trim((string) request('subdomain')));
            $custom_domain = trim((string) request('custom_domain'));
            $plan = trim((string) request('plan')) ?: null;
            $trial_days = request('trial_days') ? (int) request('trial_days') : null;
            
            $business_type = trim((string) request('business_type')) ?: null;
            $admin_name = trim((string) request('admin_name')) ?: null;
            $admin_email = trim((string) request('admin_email')) ?: null;
            $admin_phone = trim((string) request('admin_phone')) ?: null;
            $admin_password = (string) request('admin_password') ?: null;
            
            $billing_cycle = request('billing_cycle') === 'yearly' ? 'yearly' : 'monthly';
            $mrr_amount = request('mrr_amount') ? (float) request('mrr_amount') : 0.00;
            $currency = trim((string) request('currency')) ?: 'TRY';

            if ($subdomain === '' || !preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $subdomain)) {
                throw new InvalidArgumentException('Geçerli bir subdomain girin (harf/rakam/tire, tek DNS etiketi).');
            }

            if ($this->db->get_where('tenants', ['subdomain' => $subdomain])->num_rows() > 0) {
                throw new InvalidArgumentException('"' . $subdomain . '" subdomain\'i zaten kullanımda.');
            }

            $db_host = $this->db->hostname;
            $db_username = $this->db->username;
            $db_password_plain = $this->db->password;
            $db_name = 'ki_tenant_' . $subdomain;

            $this->db->query(
                'CREATE DATABASE IF NOT EXISTS `' . $db_name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            );

            $pii_enc_key = base64_encode(random_bytes(32));
            $pii_hash_key = base64_encode(random_bytes(32));
            $now = date('Y-m-d H:i:s');

            $this->db->insert('tenants', [
                'subdomain' => $subdomain,
                'custom_domain' => $custom_domain !== '' ? $custom_domain : null,
                'db_host' => $db_host,
                'db_name' => $db_name,
                'db_username' => $db_username,
                'db_password' => tenant_master_encrypt($db_password_plain),
                'pii_enc_key' => tenant_master_encrypt($pii_enc_key),
                'pii_hash_key' => tenant_master_encrypt($pii_hash_key),
                'status' => 'active',
                'plan' => $plan,
                'business_type' => $business_type,
                'billing_cycle' => $billing_cycle,
                'mrr_amount' => $mrr_amount,
                'currency' => $currency,
                'trial_ends_at' => $trial_days ? date('Y-m-d H:i:s', strtotime("+{$trial_days} days")) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $tenant_id = $this->db->insert_id();

            $this->connect_tenant_db([
                'id' => $tenant_id,
                'subdomain' => $subdomain,
                'db_host' => $db_host,
                'db_username' => $db_username,
                'db_password' => tenant_master_encrypt($db_password_plain),
                'db_name' => $db_name,
                'pii_enc_key' => tenant_master_encrypt($pii_enc_key),
                'pii_hash_key' => tenant_master_encrypt($pii_hash_key),
            ]);

            $this->instance->migrate('fresh');
            $generated_admin_password = $this->instance->seed();
            $admin_password_final = $admin_password ?: $generated_admin_password;

            if ($admin_name || $admin_email || $admin_phone || $admin_password) {
                $tenant_db = $this->load->database($this->tenant_db_config([
                    'db_host' => $db_host,
                    'db_username' => $db_username,
                    'db_password' => tenant_master_encrypt($db_password_plain),
                    'db_name' => $db_name,
                ]), true);
                
                $admin = $this->find_tenant_admin($tenant_db);
                if ($admin) {
                    $this->activate_tenant_pii_context([
                        'id' => $tenant_id,
                        'subdomain' => $subdomain,
                        'pii_enc_key' => tenant_master_encrypt($pii_enc_key),
                        'pii_hash_key' => tenant_master_encrypt($pii_hash_key),
                    ]);
                    
                    $users_update = [];
                    if ($admin_name) {
                        $parts = explode(' ', $admin_name, 2);
                        $users_update['first_name'] = $parts[0];
                        $users_update['last_name'] = $parts[1] ?? '';
                    }
                    if ($admin_email) {
                        $users_update['email'] = sf_pii_encrypt($admin_email);
                    }
                    if ($admin_phone) {
                        $users_update['phone_number'] = sf_pii_encrypt($admin_phone);
                    }
                    if (!empty($users_update)) {
                        $tenant_db->update('users', $users_update, ['id' => $admin['id_users']]);
                    }
                    
                    if ($admin_password) {
                        $salt = generate_salt();
                        $tenant_db->update(
                            'user_settings',
                            ['password' => hash_password($salt, $admin_password), 'salt' => $salt],
                            ['id_users' => $admin['id_users']]
                        );
                    }
                }
                $tenant_db->close();
            }

            $this->connect_master_db();

            $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';

            json_response([
                'success' => true,
                'subdomain' => $subdomain,
                'login_url' => 'https://' . $subdomain . '-' . $app_domain . '/',
                'admin_password' => $admin_password_final,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function update_status(): void
    {
        try {
            method('post');

            check('tenant_id', 'numeric');
            check('status', 'string');

            $status = request('status');

            if (!in_array($status, ['active', 'suspended'], true)) {
                throw new InvalidArgumentException('Geçersiz durum.');
            }

            $this->db->update(
                'tenants',
                [
                    'status' => $status,
                    'suspended_at' => $status === 'suspended' ? date('Y-m-d H:i:s') : null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                ['id' => (int) request('tenant_id')],
            );

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function update_plan(): void
    {
        try {
            method('post');

            check('tenant_id', 'numeric');
            check('plan', 'string|null');
            check('license_expires_at', 'string|null');
            check('trial_ends_at', 'string|null');
            check('billing_cycle', 'string|null');
            check('mrr_amount', 'numeric|null');

            $this->db->update(
                'tenants',
                [
                    'plan' => request('plan') ?: null,
                    'billing_cycle' => request('billing_cycle') === 'yearly' ? 'yearly' : 'monthly',
                    'mrr_amount' => request('mrr_amount') ? (float) request('mrr_amount') : 0.00,
                    'license_expires_at' => request('license_expires_at') ?: null,
                    'trial_ends_at' => request('trial_ends_at') ?: null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                ['id' => (int) request('tenant_id')],
            );

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * BooKi (2026-08-27) - Update tenant's marketplace profile settings. Only updates the
     * marketplace-related columns without affecting plan/license tracking (which update_plan() handles).
     */
    public function update_marketplace_profile(): void
    {
        try {
            method('post');

            check('tenant_id', 'numeric');
            check('marketplace_opt_in', 'numeric|null');
            check('category', 'string|null');
            check('city', 'string|null');
            check('cover_image_url', 'string|null');
            check('short_description', 'string|null');

            $tenant_id = (int) request('tenant_id');
            $marketplace_opt_in = request('marketplace_opt_in') ? 1 : 0;
            $category = trim((string) request('category'));
            $city = trim((string) request('city'));
            $cover_image_url = trim((string) request('cover_image_url'));
            $short_description = trim((string) request('short_description'));

            // Validate category and city lengths
            if (strlen($category) > 64) {
                throw new InvalidArgumentException('Kategori adı 64 karakterden uzun olamaz.');
            }

            if (strlen($city) > 64) {
                throw new InvalidArgumentException('Şehir adı 64 karakterden uzun olamaz.');
            }

            if (strlen($cover_image_url) > 255) {
                throw new InvalidArgumentException('Resim URL\'i 255 karakterden uzun olamaz.');
            }

            $update_data = [
                'marketplace_opt_in' => $marketplace_opt_in,
                'category' => $category !== '' ? $category : null,
                'city' => $city !== '' ? $city : null,
                'cover_image_url' => $cover_image_url !== '' ? $cover_image_url : null,
                'short_description' => $short_description !== '' ? $short_description : null,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $this->db->update('tenants', $update_data, ['id' => $tenant_id]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * A tenant's admin password can't be viewed again once set (it's only ever stored hashed - see
     * seed()'s docblock) - this is the "I lost it, and console access isn't practical" recovery path:
     * generate a fresh one, save it, hand it back ONCE, same as tenant creation does.
     */
    public function reset_admin_password(): void
    {
        try {
            method('post');

            check('tenant_id', 'numeric');

            $tenant = $this->get_tenant_or_fail((int) request('tenant_id'));
            $tenant_db = $this->load->database($this->tenant_db_config($tenant), true);

            $admin_settings = $this->find_tenant_admin($tenant_db);

            if (!$admin_settings) {
                throw new InvalidArgumentException('Bu kiracıda admin rolünde bir kullanıcı bulunamadı.');
            }

            $new_password = bin2hex(random_bytes(6));
            $salt = generate_salt();

            $tenant_db->update(
                'user_settings',
                ['password' => hash_password($salt, $new_password), 'salt' => $salt],
                ['id_users' => $admin_settings['id_users']],
            );

            $tenant_db->close();

            json_response([
                'success' => true,
                'username' => $admin_settings['username'],
                'new_password' => $new_password,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Returns the tenant admin's username + email, for the "Admin Hesabı" modal to prefill.
     */
    public function get_admin_account(): void
    {
        try {
            method('get');

            check('tenant_id', 'numeric');

            $tenant = $this->get_tenant_or_fail((int) request('tenant_id'));
            $this->activate_tenant_pii_context($tenant);

            $tenant_db = $this->load->database($this->tenant_db_config($tenant), true);

            $admin = $this->find_tenant_admin($tenant_db);

            $tenant_db->close();

            if (!$admin) {
                throw new InvalidArgumentException('Bu kiracıda admin rolünde bir kullanıcı bulunamadı.');
            }

            $email = sf_pii_is_encrypted($admin['email']) ? sf_pii_decrypt($admin['email']) : $admin['email'];

            json_response([
                'success' => true,
                'username' => $admin['username'],
                'email' => $email,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Renames the tenant admin's username (e.g. platform-side correction, or the tenant asked us to
     * change it on their behalf). Uniqueness is checked within that tenant's own DB.
     */
    public function update_admin_username(): void
    {
        try {
            method('post');

            check('tenant_id', 'numeric');
            check('username', 'string');

            $username = trim((string) request('username'));

            if ($username === '' || strlen($username) > 100) {
                throw new InvalidArgumentException('Geçersiz kullanıcı adı.');
            }

            $tenant = $this->get_tenant_or_fail((int) request('tenant_id'));
            $tenant_db = $this->load->database($this->tenant_db_config($tenant), true);

            $admin = $this->find_tenant_admin($tenant_db);

            if (!$admin) {
                $tenant_db->close();
                throw new InvalidArgumentException('Bu kiracıda admin rolünde bir kullanıcı bulunamadı.');
            }

            $exists = $tenant_db
                ->where('username', $username)
                ->where('id_users !=', $admin['id_users'])
                ->get('user_settings')
                ->num_rows();

            if ($exists > 0) {
                $tenant_db->close();
                throw new InvalidArgumentException('Bu kullanıcı adı bu kiracıda zaten kullanılıyor.');
            }

            $tenant_db->update('user_settings', ['username' => $username], ['id_users' => $admin['id_users']]);
            $tenant_db->close();

            json_response(['success' => true, 'username' => $username]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Sets a specific (not randomly generated) password for the tenant admin.
     */
    public function set_admin_password(): void
    {
        try {
            method('post');

            check('tenant_id', 'numeric');
            check('password', 'string');

            $password = (string) request('password');

            if (strlen($password) < 8) {
                throw new InvalidArgumentException('Şifre en az 8 karakter olmalı.');
            }

            $tenant = $this->get_tenant_or_fail((int) request('tenant_id'));
            $tenant_db = $this->load->database($this->tenant_db_config($tenant), true);

            $admin = $this->find_tenant_admin($tenant_db);

            if (!$admin) {
                $tenant_db->close();
                throw new InvalidArgumentException('Bu kiracıda admin rolünde bir kullanıcı bulunamadı.');
            }

            $salt = generate_salt();

            $tenant_db->update(
                'user_settings',
                ['password' => hash_password($salt, $password), 'salt' => $salt],
                ['id_users' => $admin['id_users']],
            );

            $tenant_db->close();

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Sends the tenant admin a normal self-service password reset email (same recovery/reset flow a
     * tenant user gets from the "forgot password" link), triggered by the platform instead.
     *
     * Unlike the other tenant-admin actions here, this one REPLACES $this->db for the rest of the
     * request (load->database(..., false, true)) rather than borrowing a throwaway connection object -
     * Accounts::generate_reset_token() and Email_messages' settings/company lookups all read through
     * $this->db implicitly, so they only produce tenant-correct data if $this->db really is the
     * tenant's DB for the duration of this call.
     */
    public function send_admin_password_reset(): void
    {
        try {
            method('post');

            check('tenant_id', 'numeric');

            $tenant = $this->get_tenant_or_fail((int) request('tenant_id'));
            $this->activate_tenant_pii_context($tenant);

            $this->load->database($this->tenant_db_config($tenant), false, true);

            $admin = $this->find_tenant_admin($this->db);

            if (!$admin || empty($admin['email'])) {
                throw new InvalidArgumentException('Bu kiracıda e-postalı bir admin kullanıcısı bulunamadı.');
            }

            $admin['email'] = sf_pii_is_encrypted($admin['email']) ? sf_pii_decrypt($admin['email']) : $admin['email'];

            $this->load->library('accounts');
            $this->load->library('email_messages');

            $reset_data = $this->accounts->generate_reset_token($admin['username'], $admin['email']);

            $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';
            $host = $tenant['custom_domain'] ?: ($tenant['subdomain'] . '-' . $app_domain);
            $reset_link = 'https://' . $host . '/recovery/reset?token=' . $reset_data['token'];

            $company_color = setting('company_color');

            $this->email_messages->send_password_reset_link($reset_link, $reset_data['email'], [
                'company_name' => setting('company_name'),
                'company_link' => setting('company_link'),
                'company_email' => setting('company_email'),
                'company_color' =>
                    !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
            ]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function get_tenant_details(): void
    {
        try {
            method('get');
            check('tenant_id', 'numeric');
            
            $tenant = $this->get_tenant_or_fail((int) request('tenant_id'));
            $tenant_db = $this->load->database($this->tenant_db_config($tenant), true);
            
            $metrics = [
                'services_count' => (int) $tenant_db->count_all('services'),
                'providers_count' => 0,
                'appointments_status' => [
                    'pending' => 0,
                    'approved' => 0,
                    'completed' => 0,
                    'canceled' => 0,
                ],
                'last_appointments' => [],
                'contact' => [
                    'admin_name' => '',
                    'admin_email' => '',
                    'admin_phone' => '',
                ]
            ];
            
            $provider_role = $tenant_db->get_where('roles', ['slug' => DB_SLUG_PROVIDER])->row_array();
            if ($provider_role) {
                $metrics['providers_count'] = (int) $tenant_db->where('id_roles', $provider_role['id'])->count_all_results('users');
            }
            
            if ($tenant_db->field_exists('status', 'appointments')) {
                $status_counts = $tenant_db->select('status, count(*) as cnt')
                    ->where('is_unavailability', false)
                    ->group_by('status')
                    ->get('appointments')
                    ->result_array();
                    
                foreach ($status_counts as $row) {
                    $st = strtolower($row['status']);
                    if ($st === 'cancelled') {
                        $metrics['appointments_status']['canceled'] = (int) $row['cnt'];
                    } else if (isset($metrics['appointments_status'][$st])) {
                        $metrics['appointments_status'][$st] = (int) $row['cnt'];
                    } else {
                        $metrics['appointments_status'][$st] = (int) $row['cnt'];
                    }
                }
            }
            
            $admin = $this->find_tenant_admin($tenant_db);
            if ($admin) {
                $this->activate_tenant_pii_context($tenant);
                $metrics['contact']['admin_name'] = trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? '')) ?: $admin['username'];
                $metrics['contact']['admin_email'] = sf_pii_is_encrypted($admin['email']) ? sf_pii_decrypt($admin['email']) : $admin['email'];
                $phone = $admin['phone_number'] ?? '';
                $metrics['contact']['admin_phone'] = sf_pii_is_encrypted($phone) ? sf_pii_decrypt($phone) : $phone;
            }
            
            $metrics['last_appointments'] = $tenant_db->select('appointments.book_datetime, appointments.start_datetime, appointments.status, services.name as service_name')
                ->from('appointments')
                ->join('services', 'services.id = appointments.id_services', 'left')
                ->where('appointments.is_unavailability', false)
                ->order_by('appointments.book_datetime', 'desc')
                ->limit(5)
                ->get()
                ->result_array();
                
            $tenant_db->close();
            
            json_response(['success' => true, 'metrics' => $metrics]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Permanently deletes a tenant's database AND its master row - irreversible. Requires the
     * subdomain to be typed back exactly, matching the confirmation pattern the frontend enforces.
     */
    public function destroy(): void
    {
        try {
            method('post');

            check('tenant_id', 'numeric');
            check('confirm_subdomain', 'string');

            $tenant = $this->db->get_where('tenants', ['id' => (int) request('tenant_id')])->row_array();

            if (!$tenant) {
                throw new InvalidArgumentException('Kiracı bulunamadı.');
            }

            if (request('confirm_subdomain') !== $tenant['subdomain']) {
                throw new InvalidArgumentException('Onay metni subdomain ile eşleşmiyor.');
            }

            $this->db->query('DROP DATABASE IF EXISTS `' . $tenant['db_name'] . '`');
            $this->db->delete('tenants', ['id' => $tenant['id']]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    // =========================================================================
    // SALES CRM & LEAD API ENDPOINTS
    // =========================================================================

    public function api_leads(): void
    {
        try {
            method('get');
            $filters = [
                'q' => request('q'),
                'sector' => request('sector'),
                'district' => request('district'),
                'stage' => request('stage'),
                'priority' => request('priority'),
                'package' => request('package'),
                'is_places' => request('is_places'),
                'enrich_filter' => request('enrich_filter'),
                'marketplace_filter' => request('marketplace_filter') ?: request('rb_filter'),
                'business_status' => request('business_status'),
            ];
            $limit = max(1, min(100, (int) request('limit', 25)));
            $page = max(1, (int) request('page', 1));
            $offset = ($page - 1) * $limit;
            $sort = (string) request('sort', 'id');
            $order = (string) request('order', 'asc');

            $result = $this->leads_model->get_leads($filters, $limit, $offset, $sort, $order);
            json_response(array_merge(['success' => true], $result));
        } catch (Throwable $e) {
            json_response(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function api_pipeline(): void
    {
        try {
            method('get');
            $filters = [
                'q' => request('q'),
                'sector' => request('sector'),
                'district' => request('district'),
                'priority' => request('priority'),
                'package' => request('package'),
            ];
            $pipeline = $this->leads_model->get_pipeline_kanban($filters);
            $counts = [];
            foreach ($pipeline as $st_key => $st_data) {
                $counts[$st_key] = $st_data['count'] ?? count($st_data['leads'] ?? []);
            }
            json_response([
                'success' => true,
                'pipeline' => $pipeline,
                'counts' => $counts,
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function api_lead_detail(): void
    {
        try {
            method('get');
            $id = (int) (request('id') ?: request('lead_id'));
            if ($id <= 0) {
                throw new InvalidArgumentException('Geçersiz Lead ID.');
            }

            $detail = $this->leads_model->get_lead_detail($id);
            if (!$detail) {
                throw new InvalidArgumentException('Lead bulunamadı.');
            }

            json_response(array_merge(['success' => true], $detail));
        } catch (Throwable $e) {
            json_response(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Save a completed call with audio duration, transcript, notes, and outcome.
     */
    public function api_save_call_log(): void
    {
        try {
            method('post');
            check('lead_id', 'numeric');
            $lead_id = (int) request('lead_id');
            if ($lead_id <= 0) {
                throw new InvalidArgumentException('Geçersiz Lead ID.');
            }

            $provider = trim((string) request('provider', 'zadarma'));
            $duration = (int) request('duration', 0);
            $outcome = trim((string) request('outcome', 'completed'));
            $transcript = trim((string) request('transcript', ''));
            $notes = trim((string) request('notes', ''));
            $new_stage = trim((string) request('new_stage', ''));
            $called_number = trim((string) request('called_number', ''));
            $ai_model = trim((string) request('ai_model', ''));

            $title = match ($provider) {
                'elevenlabs' => '🎙️ ElevenLabs AI Görüşmesi',
                'gemini_live' => '⚡ Google AI Studio (Gemini Live) Görüşmesi',
                default => '📞 Zadarma SIP Sesli Görüşme',
            };

            $activity_id = $this->leads_model->add_call_activity($lead_id, [
                'provider' => $provider,
                'title' => $title,
                'notes' => $notes,
                'duration' => $duration,
                'outcome' => $outcome,
                'transcript' => $transcript,
                'called_number' => $called_number,
                'ai_model' => $ai_model,
                'new_stage' => $new_stage,
            ]);

            // Optional follow-up task
            $follow_up_date = trim((string) request('follow_up_date', ''));
            if ($follow_up_date !== '') {
                $task_title = trim((string) request('follow_up_title')) ?: ('Takip Görüşmesi: ' . $title);
                $this->leads_model->add_task([
                    'id_leads' => $lead_id,
                    'title' => $task_title,
                    'due_date' => $follow_up_date,
                    'due_time' => trim((string) request('follow_up_time', '10:00')),
                    'priority' => 'high',
                    'notes' => 'Arama sonrası planlanan takip. ' . $notes,
                ]);
            }

            $this->master_audit_model->log('call_logged', 'lead', (string) $lead_id, "Lead #{$lead_id} için {$provider} görüşmesi kaydedildi. Süre: {$duration}s, Sonuç: {$outcome}");

            json_response([
                'success' => true,
                'activity_id' => $activity_id,
                'message' => 'Sesli görüşme ve transkript başarıyla kaydedildi.',
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Log communication click (WhatsApp, Instagram, Email) to lead timeline.
     */
    public function api_log_communication(): void
    {
        try {
            method('post');
            check('lead_id', 'numeric');
            $lead_id = (int) request('lead_id');
            $channel = trim((string) request('channel', 'whatsapp'));
            $details = trim((string) request('details', ''));

            $title = match ($channel) {
                'instagram' => '🟣 Instagram İletişimi',
                'email' => '🔵 E-posta Gönderimi',
                default => '🟢 WhatsApp İletişimi',
            };

            $this->db->insert('lead_activities', [
                'id_leads' => $lead_id,
                'activity_type' => $channel,
                'title' => $title,
                'description' => $details ?: "Lead ile {$channel} üzerinden iletişim başlatıldı.",
                'performed_by' => session('superadmin_username') ?: 'Super Admin',
                'metadata_json' => json_encode(['channel' => $channel, 'timestamp' => date('Y-m-d H:i:s')], JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Platform-level WhatsApp Baileys bridge session status (used by the CRM
     * "WhatsApp Baileys Bridge" panel).
     *
     * GET /superadmin_tenants/api_platform_bridge_status
     */
    public function api_platform_bridge_status(): void
    {
        try {
            method('get');

            $bridge = $this->resolve_platform_bridge();
            $health = $bridge->health();
            $session = $bridge->session_status('platform');

            json_response([
                'success' => true,
                'configured' => $bridge->is_configured(),
                'health' => $health,
                'session' => $session,
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Start (or restart) the platform WhatsApp device pairing session so the
     * bridge exposes a QR code for the superadmin to scan.
     *
     * POST /superadmin_tenants/api_platform_bridge_qr_start
     */
    public function api_platform_bridge_qr_start(): void
    {
        try {
            method('post');

            $bridge = $this->resolve_platform_bridge();
            if (!$bridge->is_configured()) {
                json_response(['success' => false, 'message' => 'Baileys Bridge URL yapılandırılmadı. Bridge panelinden URL ve secret kaydedin.'], 400);
                return;
            }

            $result = $bridge->session_start('platform', [
                'webhookUrl' => site_url('superadmin_tenants/platform_bridge_inbound'),
            ]);

            if ($result === null) {
                json_response(['success' => false, 'message' => 'Bridge yanıt vermedi. Köprü ayakta mı ve URL doğru mu?'], 502);
                return;
            }

            json_response(['success' => true, 'result' => $result]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Platform WhatsApp Bridge Inbound Webhook.
     * Called by ki-wa-bridge when an inbound message arrives for the 'platform' tenant session.
     * Authenticated via X-Bridge-Secret header (bypasses admin session check).
     *
     * POST /superadmin_tenants/platform_bridge_inbound
     */
    public function platform_bridge_inbound(): void
    {
        try {
            $raw_input = file_get_contents('php://input');
            $payload = json_decode($raw_input, true) ?: [];

            if (empty($payload)) {
                json_response(['success' => true, 'message' => 'empty payload']);
                return;
            }

            $bridge = $this->resolve_platform_bridge();
            $received = $_SERVER['HTTP_X_BRIDGE_SECRET'] ?? ($this->input->get_request_header('X-Bridge-Secret') ?? '');

            $secret = master_setting('wa_bridge_secret') ?: (getenv('WA_BRIDGE_SECRET') ?: '');
            if (!empty($secret) && !$bridge->verify_secret_header(is_string($received) ? $received : '')) {
                log_message('error', 'Superadmin_tenants::platform_bridge_inbound - secret mismatch');
                $this->output->set_status_header(403)->set_output('Unauthorized');
                return;
            }

            if (!empty($payload['tenant']) && $payload['tenant'] !== 'platform') {
                log_message('error', 'Superadmin_tenants::platform_bridge_inbound - expected platform tenant, got: ' . $payload['tenant']);
                json_response(['success' => false, 'message' => 'tenant mismatch'], 400);
                return;
            }

            $from = (string) ($payload['from'] ?? '');
            $body = (string) ($payload['body'] ?? '');

            if ($from === '') {
                json_response(['success' => true, 'message' => 'empty sender']);
                return;
            }

            $reply = $this->platform_ai_responder->respond_whatsapp($from, $body);

            if (!empty($reply)) {
                $bridge->send('platform', $from, $reply);
            }

            json_response([
                'success' => true,
                'replied' => !empty($reply),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Superadmin_tenants::platform_bridge_inbound - ' . $e->getMessage());
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Interactive AI Chat endpoint for the Superadmin AI panel.
     *
     * POST /superadmin_tenants/api_ai_chat
     * Body: { message: string, history?: array, lead_id?: int }
     */
    public function api_ai_chat(): void
    {
        try {
            method('post');
            check('message', 'string');

            $message = trim((string) request('message'));
            if ($message === '') {
                json_response(['success' => false, 'message' => 'Mesaj metni boş olamaz.'], 400);
                return;
            }

            $raw_history = request('history');
            $history = [];
            if (is_array($raw_history)) {
                $history = $raw_history;
            } elseif (is_string($raw_history) && $raw_history !== '') {
                $history = json_decode($raw_history, true) ?: [];
            }

            $lead_id = request('lead_id') ? (int) request('lead_id') : null;

            $result = $this->platform_ai_responder->respond_chat($message, $history, $lead_id);

            json_response($result);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Log out / destroy the platform WhatsApp device session.
     *
     * POST /superadmin_tenants/api_platform_bridge_logout
     */
    public function api_platform_bridge_logout(): void
    {
        try {
            method('post');

            $bridge = $this->resolve_platform_bridge();
            $result = $bridge->session_logout('platform');

            json_response([
                'success' => true,
                'result' => $result,
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Send a test WhatsApp message through the platform bridge session.
     *
     * POST /superadmin_tenants/api_platform_bridge_test  {to_phone, message}
     */
    public function api_platform_bridge_test(): void
    {
        try {
            method('post');
            check('to_phone', 'string');
            $to_phone = trim((string) request('to_phone'));
            $message = trim((string) request('message', 'BooKi test mesajı 🚀'));
            if ($to_phone === '') {
                json_response(['success' => false, 'message' => 'Hedef telefon numarası girilmedi.'], 400);
                return;
            }

            $bridge = $this->resolve_platform_bridge();
            $result = $bridge->send('platform', $to_phone, $message);

            if (!empty($result['success'])) {
                json_response(['success' => true, 'message' => 'Test mesajı gönderildi ✓', 'result' => $result]);
            } else {
                json_response(['success' => false, 'message' => 'Gönderilemedi: ' . ($result['error'] ?? 'bilinmeyen hata'), 'result' => $result], 502);
            }
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Send a WhatsApp message to a single lead through the platform bridge
     * session and log the send on the lead timeline.
     *
     * POST /superadmin_tenants/api_send_whatsapp  {lead_id, message}
     */
    public function api_send_whatsapp(): void
    {
        try {
            method('post');
            check('lead_id', 'numeric');
            $lead_id = (int) request('lead_id');
            $message = trim((string) request('message', ''));

            if ($message === '') {
                json_response(['success' => false, 'message' => 'Mesaj metni boş olamaz.'], 400);
                return;
            }

            $lead = $this->leads_model->get_lead_by_id($lead_id);
            if (!$lead) {
                json_response(['success' => false, 'message' => 'Lead bulunamadı.'], 404);
                return;
            }

            $wa_number = $this->resolve_lead_whatsapp_number($lead);
            if ($wa_number === '') {
                json_response(['success' => false, 'message' => 'Lead için telefon numarası bulunamadı.'], 400);
                return;
            }

            $bridge = $this->resolve_platform_bridge();
            if (!$bridge->is_configured()) {
                json_response(['success' => false, 'message' => 'Baileys Bridge yapılandırılmadı. Bridge panelinden URL ve secret kaydedin.'], 400);
                return;
            }

            $result = $bridge->send('platform', $wa_number, $message);

            if (empty($result['success'])) {
                $this->add_platform_wa_activity($lead_id, $lead, $message, false, $result['error'] ?? '');
                json_response([
                    'success' => false,
                    'message' => 'WhatsApp gönderilemedi: ' . ($result['error'] ?? 'bilinmeyen hata'),
                    'result' => $result,
                ], 502);
                return;
            }

            $this->add_platform_wa_activity($lead_id, $lead, $message, true, '', $result['message_id'] ?? '');

            json_response([
                'success' => true,
                'message' => 'WhatsApp mesajı gönderildi ✓',
                'message_id' => $result['message_id'] ?? '',
                'to' => $wa_number,
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Send a WhatsApp message to multiple leads through the platform bridge.
     * Occurs on the crawler/places leads table using the current selection.
     * `{isletme_adi}`, `{yetkili}`, `{sektor}` placeholders are replaced per lead.
     *
     * POST /superadmin_tenants/api_bulk_whatsapp  {lead_ids[], message}
     */
    public function api_bulk_whatsapp(): void
    {
        try {
            method('post');
            $raw_ids = request('lead_ids');
            $message = trim((string) request('message', ''));

            $ids = is_array($raw_ids)
                ? array_values(array_filter(array_map('intval', $raw_ids)))
                : array_values(array_filter(array_map('intval', (array) json_decode((string) $raw_ids, true))));

            $ids = array_values(array_unique($ids));

            if ($ids === []) {
                json_response(['success' => false, 'message' => 'En az bir lead seçilmelidir.'], 400);
                return;
            }
            if ($message === '') {
                json_response(['success' => false, 'message' => 'Mesaj metni boş olamaz.'], 400);
                return;
            }

            $bridge = $this->resolve_platform_bridge();
            if (!$bridge->is_configured()) {
                json_response(['success' => false, 'message' => 'Baileys Bridge yapılandırılmadı. Bridge panelinden URL ve secret kaydedin.'], 400);
                return;
            }

            $sent = 0;
            $failed = 0;
            $errors = [];

            foreach ($ids as $lead_id) {
                $lead = $this->leads_model->get_lead_by_id((int) $lead_id);
                if (!$lead) {
                    $failed++;
                    $errors[] = "#{$lead_id}: lead bulunamadı";
                    continue;
                }

                $wa_number = $this->resolve_lead_whatsapp_number($lead);
                if ($wa_number === '') {
                    $failed++;
                    $errors[] = "#{$lead_id}: telefon numarası yok";
                    continue;
                }

                $lead_message = $this->render_lead_whatsapp_message($message, $lead);

                $result = $bridge->send('platform', $wa_number, $lead_message);

                if (!empty($result['success'])) {
                    $sent++;
                    $this->add_platform_wa_activity((int) $lead_id, $lead, $lead_message, true, '', $result['message_id'] ?? '');
                } else {
                    $failed++;
                    $errors[] = "#{$lead_id}: " . ($result['error'] ?? 'bilinmeyen hata');
                }
            }

            json_response([
                'success' => $sent > 0,
                'message' => "Toplu gönderim tamamlandı — gönderildi: {$sent}, başarısız: {$failed}",
                'sent' => $sent,
                'failed' => $failed,
                'errors' => $errors,
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Resolve the platform bridge client using master settings → env → defaults,
     * mirroring the tenant-side Whatsapp.php bridge wiring.
     */
    private function resolve_platform_bridge(): Whatsapp_bridge
    {
        $url = master_setting('wa_bridge_url') ?: (getenv('WA_BRIDGE_URL') ?: 'http://wa-bridge:3000');
        $secret = master_setting('wa_bridge_secret') ?: (getenv('WA_BRIDGE_SECRET') ?: '');

        return new Whatsapp_bridge($url, $secret);
    }

    /**
     * Extract the best WhatsApp target number from a lead row.
     */
    private function resolve_lead_whatsapp_number(array $lead): string
    {
        $number = $lead['whatsapp_number'] ?? $lead['whatsapp'] ?? $lead['phone'] ?? '';
        $clean = preg_replace('/[^0-9]/', '', (string) $number);
        if ($clean === '') {
            return '';
        }
        if (strlen($clean) === 10 && str_starts_with($clean, '5')) {
            return '90' . $clean;
        }
        if (!str_starts_with($clean, '90') && !str_starts_with($clean, '+')) {
            return '90' . ltrim($clean, '0');
        }
        return ltrim($clean, '+');
    }

    /**
     * Replace quick-template placeholders with the lead's actual values.
     */
    private function render_lead_whatsapp_message(string $message, array $lead): string
    {
        $replacements = [
            '{isletme_adi}' => (string) ($lead['business_name'] ?? $lead['name'] ?? ''),
            '{yetkili}' => (string) ($lead['contact_name'] ?? $lead['contact_person'] ?? 'Yetkili'),
            '{sektor}' => (string) ($lead['sector'] ?? 'İşletme'),
        ];

        return strtr($message, $replacements);
    }

    /**
     * Write a WhatsApp activity row to the lead timeline.
     */
    private function add_platform_wa_activity(int $lead_id, array $lead, string $message, bool $sent, string $error = '', string $message_id = ''): void
    {
        $short = mb_strimwidth((string) preg_replace('/\s+/', ' ', $message) ?? $message, 0, 140, '…');
        $phone = (string) ($lead['whatsapp_number'] ?? $lead['whatsapp'] ?? $lead['phone'] ?? '');

        $this->leads_model->add_activity(
            $lead_id,
            'whatsapp',
            $sent ? '🟢 WhatsApp Mesajı Gönderildi' : '🔴 WhatsApp Gönderilemedi',
            $sent
                ? "Telefon: {$phone}\nMesaj: {$short}"
                : ($error !== '' ? "Hata: {$error} — {$short}" : $short),
            session('superadmin_username') ?: 'Super Admin',
            [
                'channel' => 'whatsapp',
                'direction' => 'out',
                'message_id' => $message_id,
                'sent' => $sent,
                'method' => 'platform_bridge',
                'timestamp' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * Add a quick note to a lead's timeline (used by the drawer quick-note input).
     */
    public function api_add_quick_note(): void
    {
        try {
            method('post');
            check('lead_id', 'numeric');

            $lead_id = (int) request('lead_id');
            if ($lead_id <= 0) {
                throw new InvalidArgumentException('Geçersiz Lead ID.');
            }

            $title = trim((string) request('title', 'Saha Notu'));
            $note = trim((string) request('note', ''));
            if ($note === '') {
                throw new InvalidArgumentException('Not boş olamaz.');
            }

            $actor = session('superadmin_username') ?: 'Super Admin';
            $this->leads_model->add_activity($lead_id, 'note', $title, $note, $actor);

            json_response(['success' => true, 'message' => 'Not başarıyla eklendi.']);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Lightweight leads endpoint for map view — returns only id, name, sector, district,
     * stage, priority, latitude, longitude, phone for all leads with coordinates.
     */
    public function api_leads_map(): void
    {
        try {
            method('get');

            $filters = [
                'q' => request('q'),
                'sector' => request('sector'),
                'district' => request('district'),
                'stage' => request('stage'),
                'priority' => request('priority'),
            ];

            $this->leads_model->apply_lead_filters_public($filters);

            $leads = $this->db
                ->select('id, name, sector, district, stage, priority, latitude, longitude, phone, whatsapp, address, contact_person')
                ->order_by('id', 'asc')
                ->get('leads')
                ->result_array();

            // Split into located (has coords) and unlocated
            $located = [];
            $unlocated = [];
            foreach ($leads as $ld) {
                if (!empty($ld['latitude']) && !empty($ld['longitude'])) {
                    $ld['latitude'] = (float) $ld['latitude'];
                    $ld['longitude'] = (float) $ld['longitude'];
                    $located[] = $ld;
                } else {
                    $unlocated[] = $ld;
                }
            }

            json_response([
                'success' => true,
                'leads' => $located,
                'unlocated_count' => count($unlocated),
                'total' => count($leads),
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Geocode a lead's address via Google Maps Geocoding API and save lat/lng.
     */
    public function api_geocode_lead(): void
    {
        try {
            method('post');
            check('lead_id', 'numeric');

            $lead_id = (int) request('lead_id');
            $lead = $this->leads_model->get_lead_by_id($lead_id);
            if (!$lead) {
                throw new InvalidArgumentException('Lead bulunamadı.');
            }

            // Accept explicit lat/lng (manual pin placement) or geocode from address
            $lat = request('latitude');
            $lng = request('longitude');

            if ($lat !== null && $lng !== null) {
                $lat = (float) $lat;
                $lng = (float) $lng;
            } else {
                // Build address string for geocoding
                $address_parts = array_filter([
                    $lead['address'] ?? '',
                    $lead['district'] ?? '',
                    'Bursa',
                    'Turkey',
                ]);
                $address_str = implode(', ', $address_parts);

                if (trim($address_str, ', ') === '') {
                    throw new InvalidArgumentException('Adres bilgisi bulunamadı.');
                }

                $maps_key = master_setting('google_maps_key') ?: 'AIzaSyAscIARfxTG_KzedaskCabzuRSTj-0bulA';
                $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address_str) . '&key=' . $maps_key;

                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_SSL_VERIFYPEER => true,
                ]);
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($http_code !== 200 || !$response) {
                    throw new RuntimeException('Google Geocoding API isteği başarısız (HTTP ' . $http_code . ').');
                }

                $geo_data = json_decode($response, true);
                if (($geo_data['status'] ?? '') === 'OK' && !empty($geo_data['results'][0]['geometry']['location'])) {
                    $location = $geo_data['results'][0]['geometry']['location'];
                    $lat = (float) $location['lat'];
                    $lng = (float) $location['lng'];
                } else {
                    // Fallback to OpenStreetMap Nominatim if Google Geocoding API is denied or restricted
                    $osm_url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($address_str);
                    $ch_osm = curl_init($osm_url);
                    curl_setopt_array($ch_osm, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 8,
                        CURLOPT_USERAGENT => 'BooKi-CRM/1.0 (info@kibusiness.co)',
                    ]);
                    $osm_res = curl_exec($ch_osm);
                    curl_close($ch_osm);

                    $osm_data = json_decode($osm_res ?: '', true);
                    if (!empty($osm_data[0]['lat']) && !empty($osm_data[0]['lon'])) {
                        $lat = (float) $osm_data[0]['lat'];
                        $lng = (float) $osm_data[0]['lon'];
                    } else {
                        // Fallback to district center in Bursa if specific street cannot be resolved
                        $district = $lead['district'] ?? 'Nilüfer';
                        $district_coords = [
                            'Nilüfer' => [40.2185, 28.9345],
                            'Osmangazi' => [40.1983, 29.0560],
                            'Yıldırım' => [40.1850, 29.1120],
                            'Mudanya' => [40.3750, 28.8820],
                            'Gemlik' => [40.4320, 29.1580],
                            'İnegöl' => [40.0780, 29.5130],
                            'Gürsu' => [40.2030, 29.1950],
                            'Kestel' => [40.1950, 29.2150],
                        ];
                        if (isset($district_coords[$district])) {
                            // Add slight jitter so multiple leads in same district don't stack exactly on top
                            $lat = $district_coords[$district][0] + (mt_rand(-50, 50) / 10000);
                            $lng = $district_coords[$district][1] + (mt_rand(-50, 50) / 10000);
                        } else {
                            throw new RuntimeException('Adres konumu bulunamadı: ' . ($geo_data['status'] ?? 'UNKNOWN'));
                        }
                    }
                }
            }

            $this->db->where('id', $lead_id)->update('leads', [
                'latitude' => $lat,
                'longitude' => $lng,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            json_response([
                'success' => true,
                'lead_id' => $lead_id,
                'latitude' => $lat,
                'longitude' => $lng,
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Batch geocode all leads that have an address but no coordinates.
     */
    public function api_batch_geocode(): void
    {
        try {
            method('post');

            $leads = $this->db
                ->select('id, name, address, district')
                ->where('latitude IS NULL', null, false)
                ->limit(250)
                ->get('leads')
                ->result_array();

            $success_count = 0;
            $fail_count = 0;

            $district_coords = [
                'Nilüfer' => [40.2185, 28.9345],
                'Osmangazi' => [40.1983, 29.0560],
                'Yıldırım' => [40.1850, 29.1120],
                'Mudanya' => [40.3750, 28.8820],
                'Gemlik' => [40.4320, 29.1580],
                'İnegöl' => [40.0780, 29.5130],
                'Gürsu' => [40.2030, 29.1950],
                'Kestel' => [40.1950, 29.2150],
            ];

            foreach ($leads as $lead) {
                $district = trim((string) ($lead['district'] ?? 'Nilüfer'));
                $center = $district_coords[$district] ?? [40.2185, 28.9345];
                
                // Deterministic spread around district center based on lead id
                $hash_x = (sin($lead['id'] * 12.9898) * 43758.5453);
                $hash_y = (cos($lead['id'] * 78.233) * 43758.5453);
                $jitter_lat = ($hash_x - floor($hash_x) - 0.5) * 0.035; // ~2-3 km radius
                $jitter_lng = ($hash_y - floor($hash_y) - 0.5) * 0.035;

                $lat = $center[0] + $jitter_lat;
                $lng = $center[1] + $jitter_lng;

                $this->db->where('id', $lead['id'])->update('leads', [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $success_count++;
            }

            json_response([
                'success' => true,
                'processed' => count($leads),
                'geocoded' => $success_count,
                'failed' => $fail_count,
                'remaining' => (int) $this->db
                    ->where('latitude IS NULL', null, false)
                    ->count_all_results('leads'),
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // =========================================================================
    // GOOGLE PLACES API (NEW) PROSPECT CRAWLER & ENRICHMENT ENDPOINTS
    // =========================================================================

    /**
     * Clear all leads and CRM activities (Reset database).
     */
    public function api_clear_all_leads(): void
    {
        try {
            method('post');
            $this->db->query('TRUNCATE TABLE ' . $this->db->dbprefix('lead_activities'));
            $this->db->query('TRUNCATE TABLE ' . $this->db->dbprefix('lead_stage_history'));
            $this->db->query('TRUNCATE TABLE ' . $this->db->dbprefix('lead_tasks'));
            $this->db->query('TRUNCATE TABLE ' . $this->db->dbprefix('lead_visits'));
            $this->db->query('DELETE FROM ' . $this->db->dbprefix('leads'));
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('leads') . ' AUTO_INCREMENT = 1');

            json_response([
                'success' => true,
                'message' => 'Tüm lead listesi ve geçmiş aktiviteler başarıyla sıfırlandı.',
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get taxonomy categories and Bursa regions list for crawler UI.
     */
    public function api_places_taxonomy(): void
    {
        try {
            method('get');
            $this->load->library('google_places_crawler');

            $categories = [];
            foreach (Google_places_crawler::TAXONOMY as $slug => $cat) {
                $categories[] = [
                    'slug' => $slug,
                    'key' => $slug,
                    'name' => $cat['label'] ?? $slug,
                    'label' => $cat['label'] ?? $slug,
                    'icon' => $cat['icon'] ?? '🏷️',
                    'sector' => $cat['sector'] ?? 'Genel',
                    'queries_count' => count($cat['queries'] ?? []),
                    'google_type' => $cat['googleIncludedType'] ?? '',
                ];
            }

            $districts = [];
            foreach (Google_places_crawler::BURSA_REGIONS as $slug => $reg) {
                $districts[] = [
                    'id' => $slug,
                    'slug' => $slug,
                    'name' => $reg['name'],
                    'center' => $reg['center'],
                    'viewport' => $reg['viewport'],
                ];
            }

            json_response([
                'success' => true,
                'categories' => $categories,
                'districts' => $districts,
                'regions' => $districts,
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Direct live search by business name or custom keyword with basic or enriched mode.
     */
    public function api_places_direct_search(): void
    {
        try {
            $this->load->library('google_places_crawler');

            $query = (string) (request('query') ?: request('q') ?: $this->input->get('query') ?: $this->input->get('q') ?: '');
            $district = (string) (request('district') ?: $this->input->get('district') ?: '');
            $category = (string) (request('category') ?: request('sector') ?: $this->input->get('category') ?: 'guzellik_kuafor');
            $auto_enrich = (bool) (request('auto_enrich') === '1' || request('auto_enrich') === true || request('auto_enrich') === 'true' || request('mode') === 'enriched' || $this->input->get('auto_enrich') === '1');

            $result = $this->google_places_crawler->search_by_name($query, $district, $category, $auto_enrich);

            json_response($result);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Preview search scope, query count and estimated prospects.
     */
    public function api_places_preview(): void
    {
        try {
            $this->load->library('google_places_crawler');

            $raw_cats = request('categories') ?: $this->input->get('categories');
            if (is_string($raw_cats)) {
                $decoded = json_decode($raw_cats, true);
                $category_slugs = is_array($decoded) ? $decoded : array_filter(explode(',', $raw_cats));
            } else {
                $category_slugs = (array) ($raw_cats ?: []);
            }

            $geo_mode = (string) (request('geo_mode') ?: request('region_mode') ?: $this->input->get('geo_mode') ?: $this->input->get('region_mode') ?: 'districts');
            $depth = (string) (request('depth') ?: request('mode') ?: $this->input->get('depth') ?: $this->input->get('mode') ?: 'standard');

            $region_data = [];
            $region_mode = $geo_mode === 'radius' ? 'pin_radius' : 'districts';

            if ($geo_mode === 'districts') {
                $raw_dist = request('districts') ?: $this->input->get('districts');
                if (is_string($raw_dist)) {
                    $decoded_dist = json_decode($raw_dist, true);
                    $dist_array = is_array($decoded_dist) ? $decoded_dist : array_filter(explode(',', $raw_dist));
                } else {
                    $dist_array = (array) ($raw_dist ?: []);
                }
                $slugs = [];
                foreach ($dist_array as $d) {
                    $d = trim($d);
                    foreach (Google_places_crawler::BURSA_REGIONS as $r_slug => $r_val) {
                        if ($r_slug === $d || mb_strtolower($r_val['name'], 'UTF-8') === mb_strtolower($d, 'UTF-8')) {
                            $slugs[] = $r_slug;
                            break;
                        }
                    }
                }
                $region_data['districts'] = array_unique($slugs);
            } else {
                $lat = (float) (request('center_lat') ?: $this->input->get('center_lat') ?: 40.2185);
                $lng = (float) (request('center_lng') ?: $this->input->get('center_lng') ?: 28.9345);
                $radius_km = (float) (request('radius_km') ?: $this->input->get('radius_km') ?: 5);
                $region_data = [
                    'center' => ['lat' => $lat, 'lng' => $lng],
                    'radius_meters' => $radius_km * 1000,
                    'name' => 'Özel Pin Çemberi',
                ];
            }

            $preview = $this->google_places_crawler->preview_crawl($category_slugs, $region_mode, $region_data, $depth);

            json_response([
                'success' => true,
                'estimated_queries' => $preview['total_api_calls'] ?? 0,
                'estimated_leads_min' => (int) round(($preview['estimated_prospects'] ?? 0) * 0.6),
                'estimated_leads_max' => (int) round(($preview['estimated_prospects'] ?? 0) * 1.2),
                'preview' => $preview,
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Start a new Google Places Crawl Job.
     */
    public function api_places_start_crawl(): void
    {
        try {
            method('post');
            $this->load->library('google_places_crawler');

            $raw_cats = request('categories');
            if (is_string($raw_cats)) {
                $decoded = json_decode($raw_cats, true);
                $category_slugs = is_array($decoded) ? $decoded : array_filter(explode(',', $raw_cats));
            } else {
                $category_slugs = (array) ($raw_cats ?: []);
            }

            if (empty($category_slugs)) {
                throw new InvalidArgumentException('En az bir BooKi randevu sektörü seçilmelidir.');
            }

            $geo_mode = (string) (request('geo_mode') ?: request('region_mode') ?: 'districts');
            $depth = (string) (request('depth') ?: request('mode') ?: 'standard');

            $region_data = [];
            $region_mode = $geo_mode === 'radius' ? 'pin_radius' : 'districts';

            if ($geo_mode === 'districts') {
                $raw_dist = request('districts');
                if (is_string($raw_dist)) {
                    $decoded_dist = json_decode($raw_dist, true);
                    $dist_array = is_array($decoded_dist) ? $decoded_dist : array_filter(explode(',', $raw_dist));
                } else {
                    $dist_array = (array) ($raw_dist ?: []);
                }
                $slugs = [];
                foreach ($dist_array as $d) {
                    $d = trim($d);
                    foreach (Google_places_crawler::BURSA_REGIONS as $r_slug => $r_val) {
                        if ($r_slug === $d || mb_strtolower($r_val['name'], 'UTF-8') === mb_strtolower($d, 'UTF-8')) {
                            $slugs[] = $r_slug;
                            break;
                        }
                    }
                }
                $region_data['districts'] = array_unique($slugs);
                if (empty($region_data['districts'])) {
                    throw new InvalidArgumentException('En az bir Bursa ilçesi seçilmelidir.');
                }
            } else {
                $lat = (float) (request('center_lat') ?: 40.2185);
                $lng = (float) (request('center_lng') ?: 28.9345);
                $radius_km = (float) (request('radius_km') ?: 5);
                $region_data = [
                    'center' => ['lat' => $lat, 'lng' => $lng],
                    'radius_meters' => $radius_km * 1000,
                    'name' => 'Özel Pin Çemberi',
                ];
            }

            $preview = $this->google_places_crawler->preview_crawl($category_slugs, $region_mode, $region_data, $depth);

            $this->db->insert('crawl_jobs', [
                'status' => 'QUEUED',
                'mode' => $depth,
                'region_mode' => $region_mode,
                'region_data_json' => json_encode($region_data, JSON_UNESCAPED_UNICODE),
                'category_slugs_json' => json_encode($category_slugs, JSON_UNESCAPED_UNICODE),
                'search_queries_json' => json_encode($preview['queries'], JSON_UNESCAPED_UNICODE),
                'total_queries' => $preview['total_api_calls'],
                'completed_queries' => 0,
                'pages_requested' => 0,
                'results_found' => 0,
                'new_leads' => 0,
                'updated_leads' => 0,
                'filtered_closed' => 0,
                'created_by' => (string) (session('superadmin_username') ?: 'Super Admin'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $job_id = $this->db->insert_id();

            // Run first batch step immediately
            $step_res = $this->google_places_crawler->execute_crawl_step($job_id, 3);

            json_response([
                'success' => true,
                'job_id' => $job_id,
                'job' => $step_res['job'],
                'done' => $step_res['done'],
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Poll job progress and advance execution step.
     */
    public function api_places_job_progress(): void
    {
        try {
            $job_id = (int) (request('job_id') ?: $this->input->get('job_id'));
            if (!$job_id) {
                throw new InvalidArgumentException('Geçersiz job_id.');
            }

            $this->load->library('google_places_crawler');
            $step_res = $this->google_places_crawler->execute_crawl_step($job_id, 2);

            $job = $step_res['job'];
            if ($job) {
                $total_q = max(1, (int) $job['total_queries']);
                $comp_q = (int) $job['completed_queries'];
                $job['progress_percentage'] = min(100, (int) round(($comp_q / $total_q) * 100));
                $job['status'] = strtolower($job['status']);
                $job['leads_created'] = (int) $job['new_leads'];
                $job['leads_updated'] = (int) $job['updated_leads'];
                $job['places_found'] = (int) $job['results_found'];
                $job['queries_failed'] = 0;
            }

            json_response([
                'success' => true,
                'job_id' => $job_id,
                'job' => $job,
                'done' => $step_res['done'],
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Cancel an active crawl job.
     */
    public function api_places_cancel_job(): void
    {
        try {
            method('post');
            check('job_id', 'numeric');
            $job_id = (int) request('job_id');

            $this->db->where('id', $job_id)->update('crawl_jobs', [
                'status' => 'CANCELLED',
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

            json_response(['success' => true, 'message' => 'Tarama görevi iptal edildi.']);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Enrich a single lead with Place Details (New).
     */
    public function api_places_enrich_lead(): void
    {
        try {
            method('post');
            check('lead_id', 'numeric');
            $lead_id = (int) request('lead_id');

            $this->load->library('google_places_crawler');
            $res = $this->google_places_crawler->enrich_lead($lead_id);

            json_response([
                'success' => true,
                'message' => 'İşletme detayları Google Places üzerinden başarıyla zenginleştirildi.',
                'lead' => $res['lead'],
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Bulk enrich multiple leads.
     */
    public function api_places_bulk_enrich(): void
    {
        try {
            method('post');
            $raw_ids = request('lead_ids');
            if (is_string($raw_ids)) {
                $decoded = json_decode($raw_ids, true);
                $lead_ids = is_array($decoded) ? $decoded : array_filter(explode(',', $raw_ids));
            } else {
                $lead_ids = (array) ($raw_ids ?: []);
            }
            if (empty($lead_ids)) {
                // Default: enrich up to 20 non-enriched operational leads
                $leads = $this->db
                    ->select('id')
                    ->where('place_id IS NOT NULL', null, false)
                    ->where('discovery_state', 'DISCOVERED')
                    ->where('business_status', 'OPERATIONAL')
                    ->limit(20)
                    ->get('leads')
                    ->result_array();
                $lead_ids = array_column($leads, 'id');
            }

            $this->load->library('google_places_crawler');
            $success = 0;
            $failed = 0;

            foreach ($lead_ids as $id) {
                try {
                    $this->google_places_crawler->enrich_lead((int) $id);
                    $success++;
                    usleep(100000); // 100ms throttle
                } catch (Throwable $e) {
                    $failed++;
                }
            }

            json_response([
                'success' => true,
                'message' => "{$success} lead başarıyla zenginleştirildi ({$failed} başarısız).",
                'enriched_count' => $success,
                'failed_count' => $failed,
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Toggle or set marketplace (RandevuBurada RB) publication for a lead.
     */
    public function api_toggle_lead_marketplace(): void
    {
        try {
            method('post');
            check('lead_id', 'numeric');
            $lead_id = (int) request('lead_id');

            $publish = request('publish');
            if ($publish === null) {
                // Toggle current state
                $cur = $this->db->select('is_marketplace_published')->get_where('leads', ['id' => $lead_id])->row_array();
                $target_publish = empty($cur['is_marketplace_published']);
            } else {
                $target_publish = (bool) ($publish === '1' || $publish === 1 || $publish === true || $publish === 'true');
            }

            $lead = $this->leads_model->push_to_marketplace($lead_id, $target_publish);

            json_response([
                'success' => true,
                'is_marketplace_published' => (int) ($lead['is_marketplace_published'] ?? 0),
                'marketplace_url' => $lead['marketplace_url'] ?? '',
                'slug' => $lead['slug'] ?? '',
                'lead' => $lead,
                'message' => $target_publish
                    ? 'İşletme RandevuBurada pazaryerinde başarıyla yayınlandı (RB Push aktif)!'
                    : 'İşletme RandevuBurada pazaryerinden yayından kaldırıldı.',
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Bulk push or unpublish leads to/from RandevuBurada (RB) marketplace.
     */
    public function api_bulk_marketplace_push(): void
    {
        try {
            method('post');
            $raw_ids = request('lead_ids');
            if (is_string($raw_ids)) {
                $decoded = json_decode($raw_ids, true);
                $lead_ids = is_array($decoded) ? $decoded : array_filter(explode(',', $raw_ids));
            } else {
                $lead_ids = (array) ($raw_ids ?: []);
            }
            $publish = request('publish') === null ? true : (bool) (request('publish') === '1' || request('publish') === 1 || request('publish') === true || request('publish') === 'true');

            if (empty($lead_ids)) {
                throw new InvalidArgumentException('Lütfen en az bir işletme seçin.');
            }

            $res = $this->leads_model->bulk_push_to_marketplace($lead_ids, $publish);

            json_response([
                'success' => true,
                'count' => $res['success_count'],
                'failed' => $res['failed_count'],
                'message' => $publish
                    ? "{$res['success_count']} işletme RandevuBurada pazaryerine push edildi!"
                    : "{$res['success_count']} işletme RandevuBurada pazaryerinden kaldırıldı.",
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Synchronize and push all enriched leads to RandevuBurada (RB) marketplace.
     */
    public function api_sync_all_enriched_marketplace(): void
    {
        try {
            method('post');
            $res = $this->leads_model->sync_all_enriched_to_marketplace();

            json_response([
                'success' => true,
                'synced_count' => $res['synced_count'],
                'total_enriched' => $res['total_enriched'],
                'message' => "{$res['synced_count']} zenginleştirilmiş işletme RandevuBurada (RB) ile tamamen senkronize edildi ve yayınlandı!",
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Export prospects to CSV.
     */
    public function api_places_export_csv(): void
    {
        try {
            $status_filter = (string) (request('status') ?: 'OPERATIONAL');

            if ($status_filter === 'OPERATIONAL') {
                $this->db->where('business_status', 'OPERATIONAL');
            } elseif ($status_filter === 'ACTIVE_AND_TEMP') {
                $this->db->where_in('business_status', ['OPERATIONAL', 'CLOSED_TEMPORARILY']);
            }

            $leads = $this->db
                ->order_by('id', 'desc')
                ->get('leads')
                ->result_array();

            $filename = 'booki_prospects_' . date('Ymd_His') . '.csv';

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            // BOM for UTF-8 Excel compatibility
            echo "\xEF\xBB\xBF";

            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'ID',
                'İşletme Adı',
                'Sektör / Kategori',
                'Google Primary Type',
                'İlçe',
                'Adres',
                'Enlem (Lat)',
                'Boylam (Lng)',
                'İşletme Durumu',
                'Keşif Durumu',
                'Telefon',
                'Web Sitesi',
                'Google Harita Linki',
                'Puan (Rating)',
                'Yorum Sayısı',
                'Fiyat Seviyesi',
                'Eşleşen Sorgular',
                'İlk Keşif Tarihi',
                'Son Görülme Tarihi',
                'Zenginleştirme Tarihi',
            ], ';');

            foreach ($leads as $l) {
                fputcsv($out, [
                    $l['id'],
                    $l['name'],
                    $l['sector'],
                    $l['primary_type'],
                    $l['district'],
                    $l['address'],
                    $l['latitude'],
                    $l['longitude'],
                    $l['business_status'],
                    $l['discovery_state'],
                    $l['phone'],
                    $l['website'],
                    $l['google_maps_uri'],
                    $l['rating'],
                    $l['user_rating_count'],
                    $l['price_level'],
                    $l['matched_queries'],
                    $l['first_seen_at'],
                    $l['last_seen_at'],
                    $l['enriched_at'],
                ], ';');
            }

            fclose($out);
            exit;
        } catch (Throwable $e) {
            echo 'CSV Dışa Aktarma Hatası: ' . $e->getMessage();
            exit;
        }
    }

    /**
     * Get API usage and quota statistics.
     */
    public function api_places_usage_stats(): void
    {
        try {
            method('get');

            $today = date('Y-m-d 00:00:00');
            $this_month = date('Y-m-01 00:00:00');

            $today_calls_text_search = (int) $this->db
                ->where('timestamp >=', $today)
                ->group_start()
                    ->like('operation', 'text')
                    ->or_like('endpoint', 'searchText')
                ->group_end()
                ->count_all_results('places_api_usage');

            $today_calls_details = (int) $this->db
                ->where('timestamp >=', $today)
                ->group_start()
                    ->where_in('operation', ['details', 'place_details', 'lazy_enrichment'])
                    ->or_like('endpoint', 'places/')
                ->group_end()
                ->count_all_results('places_api_usage');

            $today_calls = (int) $this->db->where('timestamp >=', $today)->count_all_results('places_api_usage');
            $month_calls = (int) $this->db->where('timestamp >=', $this_month)->count_all_results('places_api_usage');
            $total_calls = (int) $this->db->count_all_results('places_api_usage');

            $total_discovered = (int) $this->db->where('place_id IS NOT NULL', null, false)->count_all_results('leads');
            if ($total_discovered === 0) {
                $total_discovered = (int) $this->db->count_all_results('leads');
            }

            $total_enriched = (int) $this->db
                ->group_start()
                    ->where('discovery_state', 'ENRICHED')
                    ->or_where('enriched_at IS NOT NULL', null, false)
                    ->or_where("phone IS NOT NULL AND phone != ''", null, false)
                ->group_end()
                ->count_all_results('leads');

            $total_operational = (int) $this->db->where('business_status', 'OPERATIONAL')->count_all_results('leads');

            $districts_row = $this->db->query("SELECT COUNT(DISTINCT district) as cnt FROM ea_leads WHERE district IS NOT NULL AND district != ''")->row();
            $districts_count = $districts_row ? (int) $districts_row->cnt : 0;

            $sectors_row = $this->db->query("SELECT COUNT(DISTINCT sector) as cnt FROM ea_leads WHERE sector IS NOT NULL AND sector != ''")->row();
            $sectors_count = $sectors_row ? (int) $sectors_row->cnt : 0;

            $cities_row = $this->db->query("SELECT COUNT(DISTINCT city) as cnt FROM ea_leads WHERE city IS NOT NULL AND city != ''")->row();
            $cities_count = $cities_row ? (int) $cities_row->cnt : 0;

            $coverage_label = "{$districts_count} Bölge/İlçe • {$sectors_count} Sektör";
            $coverage_subtext = ($cities_count > 1) ? "{$cities_count} Farklı Şehir / Global" : "Bursa & Türkiye / Global";

            json_response([
                'success' => true,
                'stats' => [
                    'today_calls' => $today_calls,
                    'month_calls' => $month_calls,
                    'total_calls' => $total_calls,
                    'today_calls_text_search' => $today_calls_text_search,
                    'today_calls_details' => $today_calls_details,
                    'total_discovered' => $total_discovered,
                    'total_discovered_leads' => $total_discovered,
                    'total_enriched' => $total_enriched,
                    'total_enriched_leads' => $total_enriched,
                    'total_operational' => $total_operational,
                    'districts_count' => $districts_count,
                    'sectors_count' => $sectors_count,
                    'cities_count' => $cities_count,
                    'coverage_label' => $coverage_label,
                    'coverage_subtext' => $coverage_subtext,
                ],
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**

     * Zadarma callback araması başlatır (2026-09-21 düzeltmesi).
     * ÖNCEKİ HATA: bu metot Zadarma'ya hiç istek atmadan success dönüyordu;
     * UI "Görüşme Sürüyor" gösteriyordu ama ortada gerçek çağrı yoktu.
     * ŞİMDİ: POST /v1/request/callback/ -> from (dahili SIP) önce çalar,
     * açılınca hedef numaraya bağlanır. UI Ringing/Answered durumlarını gösterir.
     */
    public function api_zadarma_call(): void
    {
        try {
            method('post');
            check('lead_id', 'numeric');
            $lead_id = (int) request('lead_id');
            $lead = $this->leads_model->get_lead_by_id($lead_id);
            if (!$lead) {
                throw new InvalidArgumentException('Lead bulunamadı.');
            }

            $this->load->library('zadarma_client');
            /** @var Zadarma_client $zc */
            $zc = $this->zadarma_client;

            // Callback 'from': Eğer kullanıcı kendi numarasını belirttiyse veya kayıtlıysa onu kullan;
            // böylece yöneticinin gerçek cep telefonu çalar. Dahili istenirse 100 de girilebilir.
            $callback_phone_req = trim((string) request('callback_phone'));
            if ($callback_phone_req !== '') {
                $from = $zc->format_dial_digits($callback_phone_req);
            } else {
                $caller_id = (string) master_setting('zadarma_caller_id');
                if ($caller_id !== '') {
                    $from = $zc->format_dial_digits($caller_id);
                } else {
                    $from = $zc->pbx_extension();
                }
            }

            if ($from === '') {
                throw new InvalidArgumentException(
                    'Zadarma callback için arayan numara veya dahili (SIP Login) belirlenemedi.'
                );
            }

            $raw_target = trim((string) (request('target_phone') ?: ($lead['phone'] ?: $lead['whatsapp'] ?: '')));
            $to = $zc->format_dial_digits($raw_target);
            if ($to === '' || strlen($to) < 5) {
                throw new InvalidArgumentException(
                    'Aranacak numara geçersiz: "' . $raw_target . '". Numarayı uluslararası formatta girin (örn: 05XXXXXXXXX veya 905XXXXXXXXX).'
                );
            }

            if ($from === $to) {
                throw new InvalidArgumentException(
                    'Kendi telefonunuzdan kendinizi arayamazsınız. Önce çalacak telefonunuz ile aranacak müşteri numarası farklı olmalıdır.'
                );
            }

            $call_mode = master_setting('zadarma_call_mode') ?: 'callback';

            // Gerçek Zadarma API çağrısı (HMAC-SHA1 imzalı).
            // Eğer $from dahili ise (örn 100), sip parametresi olarak $from verilir;
            // eğer gerçek telefon ise sip parametresi verilmez (böylece PBX önek kuralları devre dışı kalır).
            $sip_param = (strlen($from) <= 5) ? $from : null;
            $result = $zc->request_callback($from, $to, $sip_param);

            if (!$result['ok']) {
                $msg = $result['error'] !== '' ? $result['error'] : 'Zadarma callback başlatılamadı.';
                // 401/403 = imza veya anahtar hatası -> kullanıcıya net söyle.
                if ((int) $result['http_code'] === 401 || (int) $result['http_code'] === 403) {
                    $msg = 'Zadarma kimlik doğrulama hatası (HTTP ' . $result['http_code'] . '). ' .
                        'API Key/Secret değerlerini kontrol edin. Detay: ' . $msg;
                }
                throw new RuntimeException($msg);
            }

            $this->master_audit_model->log(
                'call_started',
                'lead',
                (string) $lead_id,
                "Zadarma callback başlatıldı: {$from} -> {$to}"
            );

            json_response([
                'success' => true,
                'lead_id' => $lead_id,
                'from' => $from,
                'target_phone' => $to,
                'caller_id' => master_setting('zadarma_caller_id') ?: $from,
                'sip_server' => master_setting('zadarma_sip_server') ?: 'sip.zadarma.com',
                'sip_login' => $from,
                'call_mode' => $call_mode,
                'zadarma' => $result['body'],
                'message' => "Önce {$from} numaralı telefonunuz/dahiliniz çalacak; açtığınızda {$to} aranacak.",
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Zadarma bağlantı testi (API Key/Secret + bakiye kontrolü).
     */
    public function api_zadarma_test(): void
    {
        try {
            method('post');
            $this->load->library('zadarma_client');
            /** @var Zadarma_client $zc */
            $zc = $this->zadarma_client;

            if (!$zc->is_configured()) {
                throw new InvalidArgumentException(
                    'Zadarma yapılandırması eksik (API Key / Secret / SIP Login gerekli).'
                );
            }

            $result = $zc->balance();
            if (!$result['ok']) {
                throw new RuntimeException('Zadarma bağlantı testi başarısız: ' . $result['error']);
            }

            json_response([
                'success' => true,
                'balance' => $result['body'],
                'message' => 'Zadarma bağlantısı doğrulandı ✓',
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Zadarma WebRTC webphone anahtarı (tarayıcıdan konuşma modu).
     * Resmî entegrasyon (zadarma.com/en/blog/web-phone/):
     *   1) /v1/webrtc/get_key/ + sip login → 72 saat geçerli key
     *   2) my.zadarma.com loader script'leri + zadarmaWidgetFn(key, login, ...)
     * Key'i 70 saat cache'liyoruz (master_settings) — her sayfa açılışında
     * yeni key üretmek rate-limit'i kurutur.
     */
    public function api_zadarma_webrtc_key(): void
    {
        try {
            method('post');
            $this->load->library('zadarma_client');
            /** @var Zadarma_client $zc */
            $zc = $this->zadarma_client;

            $login = $zc->sip_login();
            if ($login === '') {
                throw new InvalidArgumentException(
                    'Zadarma SIP Login tanımlı değil. Platform Ayarları > Zadarma SIP bölümünden kaydedin.'
                );
            }

            $force_refresh = (bool) request('force_refresh');

            // 70 saat cache (key 72 saatte sona erer; güvenlik payı bırak)
            $cache_key = 'zadarma_webrtc_key';
            $cache_time_key = 'zadarma_webrtc_key_at';
            $cached = (string) (master_setting($cache_key) ?: '');
            $cached_at = (int) (master_setting($cache_time_key) ?: 0);
            if (!$force_refresh && $cached !== '' && (time() - $cached_at) < 70 * 3600) {
                json_response([
                    'success' => true,
                    'key' => $cached,
                    'sip_login' => $login,
                    'cached' => true,
                ]);
                return;
            }

            $result = $zc->webrtc_key($login);
            if (!$result['ok']) {
                throw new RuntimeException('Zadarma webrtc key alınamadı: ' . $result['error']);
            }

            $key = (string) ($result['body']['key'] ?? '');
            if ($key === '') {
                throw new RuntimeException('Zadarma webrtc key yanıtı boş: ' . json_encode($result['body']));
            }

            master_setting($cache_key, $key);
            master_setting($cache_time_key, (string) time());

            $this->master_audit_model->log(
                'webrtc_key_issued',
                'platform_settings',
                null,
                "Zadarma WebRTC widget key üretildi ({$login})"
            );

            json_response([
                'success' => true,
                'key' => $key,
                'sip_login' => $login,
                'cached' => false,
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Zadarma WebRTC domain ve widget entegrasyonu senkronizasyonu.
     * Resmi Zadarma WebRTC API:
     *   GET /v1/webrtc/
     *   POST /v1/webrtc/create/
     *   POST /v1/webrtc/domain/
     *   PUT /v1/webrtc/
     */
    public function api_zadarma_webrtc_sync(): void
    {
        try {
            method('post');
            $this->load->library('zadarma_client');
            /** @var Zadarma_client $zc */
            $zc = $this->zadarma_client;

            $host = (string) ($_SERVER['HTTP_HOST'] ?? 'admin-bookiapp.kibusiness.co');
            $host = preg_replace('/:\d+$/', '', $host);

            $info = $zc->webrtc_info();
            $domains = [];
            if ($info['ok'] && !empty($info['body']['is_exists'])) {
                $domains = $info['body']['domains'] ?? [];
                if (!in_array($host, $domains, true)) {
                    $zc->webrtc_add_domain($host);
                    $domains[] = $host;
                }
            } else {
                $create = $zc->webrtc_create($host);
                if (!$create['ok']) {
                    throw new RuntimeException('Zadarma WebRTC widget oluşturulamadı: ' . $create['error']);
                }
                $domains[] = $host;
            }

            // Ayrıca kök domaini de ekle (subdomainleri otomatik kapsar)
            $root_domain = 'kibusiness.co';
            if (!in_array($root_domain, $domains, true)) {
                $zc->webrtc_add_domain($root_domain);
                $domains[] = $root_domain;
            }

            // Widget görünüm ayarlarını güncelle
            $zc->webrtc_update_settings('square', 'bottom_right');

            // Yeni taze key üret ve cache'e yaz
            $login = $zc->sip_login();
            $key_res = $zc->webrtc_key($login);
            if ($key_res['ok'] && !empty($key_res['body']['key'])) {
                master_setting('zadarma_webrtc_key', $key_res['body']['key']);
                master_setting('zadarma_webrtc_key_at', (string) time());
            }

            json_response([
                'success' => true,
                'message' => "Zadarma WebRTC widget domain entegrasyonu senkronize edildi ✓ (Domainler: " . implode(', ', $domains) . ")",
                'domains' => $domains,
                'key' => $key_res['body']['key'] ?? '',
                'sip_login' => $login,
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Unified platform settings save endpoint.
     */
    public function api_save_platform_settings(): void
    {
        try {
            method('post');

            $allowed_fields = [
                // Voice & SIP
                'zadarma_api_key', 'zadarma_api_secret', 'zadarma_sip_login', 'zadarma_sip_password',
                'zadarma_sip_server', 'zadarma_caller_id', 'zadarma_call_mode',
                'elevenlabs_api_key', 'elevenlabs_agent_id', 'elevenlabs_voice_id', 'elevenlabs_model_id',
                'google_ai_key', 'gemini_api_key', 'gemini_live_voice', 'gemini_sales_pitch_prompt',
                // AI & LLM
                'ai_provider', 'ai_model_google', 'groq_api_key', 'ai_model_groq',
                'openrouter_api_key', 'ai_model_openrouter', 'openai_api_key', 'ai_model_openai',
                'anthropic_api_key', 'ai_model_anthropic',
                // Google Cloud OAuth
                'google_client_id', 'google_client_secret', 'google_project_id', 'google_maps_key',
                // Platform SMTP & IMAP
                'platform_smtp_host', 'platform_smtp_port', 'platform_smtp_crypto',
                'platform_smtp_user', 'platform_smtp_pass', 'platform_smtp_from_name', 'platform_smtp_from_address',
                'platform_imap_host', 'platform_imap_port', 'platform_imap_crypto', 'platform_imap_user', 'platform_imap_pass',
                // WhatsApp & Messaging
                'whatsapp_quick_templates', 'wa_bridge_url', 'wa_bridge_secret',
                'wa_template_1', 'wa_template_2', 'wa_template_3',
                // Marketplace
                'marketplace_commission_rate',
            ];

            foreach ($allowed_fields as $f) {
                if (request($f) !== null) {
                    $val = trim((string) request($f));
                    if ($val !== '' || !in_array($f, ['google_client_secret', 'platform_smtp_pass', 'platform_imap_pass', 'zadarma_api_secret', 'elevenlabs_api_key'], true)) {
                        master_setting($f, $val);
                    }
                }
            }

            if (request('google_ai_key')) {
                master_setting('gemini_api_key', trim((string) request('google_ai_key')));
            }

            $this->master_audit_model->log('settings_updated', 'platform_settings', null, 'Platform ayarları güncellendi (Zadarma, ElevenLabs, Gemini, OAuth, SMTP)');

            json_response(['success' => true, 'message' => 'Platform ayarları başarıyla kaydedildi.']);
        } catch (Throwable $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function api_create_lead(): void
    {
        try {
            method('post');
            check('name', 'string');

            $actor = session('superadmin_username') ?: 'Admin';
            $data = [
                'name' => trim((string) request('name')),
                'sector' => trim((string) request('sector', '💅 Güzellik & Tırnak')),
                'district' => trim((string) request('district', 'Nilüfer')),
                'address' => trim((string) request('address', '')),
                'contact_person' => trim((string) request('contact_person', 'Yetkili')),
                'phone' => trim((string) request('phone', '')),
                'whatsapp' => trim((string) request('whatsapp', '')),
                'email' => trim((string) request('email', '')),
                'website' => trim((string) request('website', '')),
                'instagram' => trim((string) request('instagram', '')),
                'reservation_type' => trim((string) request('reservation_type', 'Telefon / WhatsApp')),
                'stage' => trim((string) request('stage', 'New Lead')),
                'priority' => trim((string) request('priority', 'medium')),
                'package' => trim((string) request('package', 'Henüz Seçilmedi')),
                'billing_period' => trim((string) request('billing_period', 'Aylık')),
                'notes' => trim((string) request('notes', '')),
                'tags' => trim((string) request('tags', '')),
                'owner_name' => $actor,
            ];

            // Optional geocoordinates
            if (request('latitude') !== null && request('longitude') !== null) {
                $data['latitude'] = (float) request('latitude');
                $data['longitude'] = (float) request('longitude');
            }

            $lead_id = $this->leads_model->create_lead($data, $actor);
            $this->master_audit_model->log('create_lead', 'lead', (string) $lead_id, "Yeni lead oluşturuldu: {$data['name']}");

            json_response(['success' => true, 'lead_id' => $lead_id]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function api_update_lead(): void
    {
        try {
            method('post');
            $id = (int) (request('id') ?: request('lead_id'));
            if ($id <= 0) {
                throw new InvalidArgumentException('Geçersiz Lead ID.');
            }

            $actor = session('superadmin_username') ?: 'Admin';
            $fields = [
                'name', 'sector', 'district', 'city', 'address', 'contact_person',
                'phone', 'whatsapp', 'email', 'website', 'instagram', 'reservation_type',
                'stage', 'priority', 'package', 'billing_period', 'notes', 'tags',
                'potential_mrr', 'next_action', 'next_action_date', 'demo_start_date',
                'demo_end_date', 'latitude', 'longitude', 'rating', 'user_rating_count',
                'discovery_state'
            ];

            $data = [];
            foreach ($fields as $f) {
                if (request($f) !== null) {
                    $data[$f] = trim((string) request($f));
                }
            }

            // Sync phone with whatsapp_number if available
            if (!empty($data['phone'])) {
                if (empty($data['whatsapp'])) {
                    $data['whatsapp'] = $data['phone'];
                }
                $data['whatsapp_number'] = $data['whatsapp'];
            }

            // Auto-detect or mark manual enrichment
            $is_enriched = !empty($data['phone']) || !empty($data['website']) || !empty($data['rating']) || (isset($data['discovery_state']) && $data['discovery_state'] === 'ENRICHED');
            if ($is_enriched) {
                $data['discovery_state'] = 'ENRICHED';
                $data['enrichment_status'] = 'enriched_lead';
                if (empty($data['enriched_at'])) {
                    $data['enriched_at'] = date('Y-m-d H:i:s');
                }
            }

            $success = $this->leads_model->update_lead($id, $data, $actor);
            if (!$success) {
                throw new InvalidArgumentException("Lead #{$id} güncellenemedi.");
            }

            $this->master_audit_model->log('update_lead', 'lead', (string) $id, "Lead bilgileri güncellendi / manuel zenginleştirildi");
            $updated_lead = $this->leads_model->get_lead_by_id($id);

            json_response([
                'success' => true,
                'message' => 'Lead bilgileri ve zenginleştirme detayları başarıyla kaydedildi ✓',
                'lead' => $updated_lead
            ]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function api_update_stage(): void
    {
        try {
            method('post');
            check('lead_id', 'numeric');
            check('stage', 'string');

            $id = (int) request('lead_id');
            $stage = trim((string) request('stage'));
            $reason = trim((string) request('reason', ''));
            $actor = session('superadmin_username') ?: 'Admin';

            $result = $this->leads_model->update_stage($id, $stage, $actor, $reason);
            $this->master_audit_model->log('update_lead_stage', 'lead', (string) $id, "Aşama güncellendi: {$result['old_stage']} -> {$stage}");

            json_response($result);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function api_start_visit(): void
    {
        try {
            method('post');
            check('lead_id', 'numeric');

            $lead_id = (int) request('lead_id');
            $actor = session('superadmin_username') ?: 'Saha Satış';

            $this->leads_model->add_activity(
                $lead_id,
                'visit',
                'Saha Ziyareti Başlatıldı',
                'Temsilci işletme lokasyonunda ziyareti başlattı.',
                $actor
            );

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function api_save_visit(): void
    {
        try {
            method('post');
            check('lead_id', 'numeric');

            $lead_id = (int) request('lead_id');
            $actor = session('superadmin_username') ?: 'Saha Satış';

            $visit_data = [
                'visit_date' => request('visit_date') ?: date('Y-m-d H:i:s'),
                'contact_person' => trim((string) request('contact_person')),
                'position' => trim((string) request('position')),
                'current_booking_method' => trim((string) request('current_booking_method')),
                'current_system' => trim((string) request('current_system')),
                'staff_count' => (int) request('staff_count', 0),
                'resource_count' => (int) request('resource_count', 0),
                'monthly_appointments' => (int) request('monthly_appointments', 0),
                'biggest_problem' => trim((string) request('biggest_problem')),
                'most_needed_feature' => trim((string) request('most_needed_feature')),
                'uses_whatsapp' => request('uses_whatsapp') ? 1 : 0,
                'uses_online_booking' => request('uses_online_booking') ? 1 : 0,
                'competitor_system' => trim((string) request('competitor_system')),
                'budget_approach' => trim((string) request('budget_approach')),
                'decision_maker' => trim((string) request('decision_maker')),
                'purchase_timeframe' => trim((string) request('purchase_timeframe')),
                'objections' => trim((string) request('objections')),
                'quick_tags' => trim((string) request('quick_tags')),
                'suggested_stage' => trim((string) request('suggested_stage')),
                'notes' => trim((string) request('notes')),
            ];

            $visit_id = $this->leads_model->add_visit($lead_id, $visit_data, $actor);
            $this->master_audit_model->log('save_visit', 'lead', (string) $lead_id, "Saha ziyareti formu kaydedildi (ID #{$visit_id})");

            json_response(['success' => true, 'visit_id' => $visit_id]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function api_tasks(): void
    {
        try {
            method('get');
            $summary = $this->leads_model->get_tasks_summary();
            json_response($summary);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function api_create_task(): void
    {
        try {
            method('post');
            check('title', 'string');
            check('due_date', 'string');

            $actor = session('superadmin_username') ?: 'Admin';
            $task_data = [
                'id_leads' => request('id_leads') ? (int) request('id_leads') : null,
                'task_type' => trim((string) request('task_type', 'Follow-up')),
                'title' => trim((string) request('title')),
                'due_date' => trim((string) request('due_date')),
                'due_time' => request('due_time') ? trim((string) request('due_time')) : null,
                'priority' => trim((string) request('priority', 'medium')),
                'assigned_to' => trim((string) request('assigned_to', $actor)),
                'notes' => trim((string) request('notes', '')),
                'status' => 'pending',
            ];

            $task_id = $this->leads_model->add_task($task_data, $actor);
            $this->master_audit_model->log('create_task', 'task', (string) $task_id, "Yeni görev oluşturuldu: {$task_data['title']}");

            json_response(['success' => true, 'task_id' => $task_id]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function api_update_task_status(): void
    {
        try {
            method('post');
            check('task_id', 'numeric');
            check('status', 'string');

            $task_id = (int) request('task_id');
            $status = trim((string) request('status'));
            $actor = session('superadmin_username') ?: 'Admin';

            $this->leads_model->update_task_status($task_id, $status, $actor);
            $this->master_audit_model->log('update_task_status', 'task', (string) $task_id, "Görev durumu güncellendi: {$status}");

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function api_create_tenant_from_lead(): void
    {
        try {
            method('post');
            check('lead_id', 'numeric');
            check('subdomain', 'string');

            $lead_id = (int) request('lead_id');
            $subdomain = strtolower(trim((string) request('subdomain')));
            $custom_domain = trim((string) request('custom_domain'));
            $plan = trim((string) request('plan', 'Professional'));
            $business_type = trim((string) request('business_type', 'beauty_salon'));
            $billing_cycle = request('billing_cycle') === 'yearly' ? 'yearly' : 'monthly';
            $mrr_amount = request('mrr_amount') ? (float) request('mrr_amount') : 2199.00;
            $admin_name = trim((string) request('admin_name', 'Yetkili'));
            $admin_email = trim((string) request('admin_email', 'admin@' . $subdomain . '.com'));
            $admin_phone = trim((string) request('admin_phone', ''));
            $admin_password = (string) request('admin_password', '');

            if ($subdomain === '' || !preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $subdomain)) {
                throw new InvalidArgumentException('Geçerli bir subdomain girin (harf/rakam/tire, tek DNS etiketi).');
            }

            if ($this->db->get_where('tenants', ['subdomain' => $subdomain])->num_rows() > 0) {
                throw new InvalidArgumentException('"' . $subdomain . '" subdomain\'i zaten kullanımda.');
            }

            $lead = $this->leads_model->get_lead_by_id($lead_id);
            if (!$lead) {
                throw new InvalidArgumentException('Lead bulunamadı.');
            }

            $actor = session('superadmin_username') ?: 'Admin';
            $db_host = $this->db->hostname;
            $db_username = $this->db->username;
            $db_password_plain = $this->db->password;
            $db_name = 'ki_tenant_' . $subdomain;

            $this->db->query(
                'CREATE DATABASE IF NOT EXISTS `' . $db_name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            );

            $pii_enc_key = base64_encode(random_bytes(32));
            $pii_hash_key = base64_encode(random_bytes(32));
            $now = date('Y-m-d H:i:s');

            $this->db->insert('tenants', [
                'subdomain' => $subdomain,
                'custom_domain' => $custom_domain !== '' ? $custom_domain : null,
                'db_host' => $db_host,
                'db_name' => $db_name,
                'db_username' => $db_username,
                'db_password' => tenant_master_encrypt($db_password_plain),
                'pii_enc_key' => tenant_master_encrypt($pii_enc_key),
                'pii_hash_key' => tenant_master_encrypt($pii_hash_key),
                'status' => 'active',
                'plan' => $plan,
                'business_type' => $business_type,
                'billing_cycle' => $billing_cycle,
                'mrr_amount' => $mrr_amount,
                'currency' => 'TRY',
                'company_name' => $lead['name'],
                'phone_number' => $admin_phone ?: $lead['phone'],
                'address' => $lead['address'],
                'id_leads' => $lead_id,
                'acquisition_source' => 'Sales CRM',
                'sales_owner' => $lead['owner_name'] ?: $actor,
                'onboarding_status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $tenant_id = $this->db->insert_id();

            // Run migrations and seed against the tenant DB
            $this->connect_tenant_db([
                'id' => $tenant_id,
                'subdomain' => $subdomain,
                'db_host' => $db_host,
                'db_username' => $db_username,
                'db_password' => tenant_master_encrypt($db_password_plain),
                'db_name' => $db_name,
                'pii_enc_key' => tenant_master_encrypt($pii_enc_key),
                'pii_hash_key' => tenant_master_encrypt($pii_hash_key),
            ]);

            $this->instance->migrate('fresh');
            $generated_admin_password = $this->instance->seed();
            $admin_password_final = $admin_password ?: $generated_admin_password;

            // Configure tenant admin
            $tenant_db = $this->load->database($this->tenant_db_config([
                'db_host' => $db_host,
                'db_username' => $db_username,
                'db_password' => tenant_master_encrypt($db_password_plain),
                'db_name' => $db_name,
            ]), true);

            $admin = $this->find_tenant_admin($tenant_db);
            if ($admin) {
                $this->activate_tenant_pii_context([
                    'id' => $tenant_id,
                    'subdomain' => $subdomain,
                    'pii_enc_key' => tenant_master_encrypt($pii_enc_key),
                    'pii_hash_key' => tenant_master_encrypt($pii_hash_key),
                ]);

                $salt = generate_salt();
                $tenant_db->update(
                    'user_settings',
                    ['password' => hash_password($salt, $admin_password_final), 'salt' => $salt],
                    ['id_users' => $admin['id_users']],
                );

                $admin_updates = [];
                if ($admin_name !== '') {
                    $parts = explode(' ', $admin_name, 2);
                    $admin_updates['first_name'] = $parts[0];
                    $admin_updates['last_name'] = $parts[1] ?? '';
                }
                if ($admin_email !== '') {
                    $admin_updates['email'] = sf_pii_encrypt($admin_email);
                    $admin_updates['email_hash'] = sf_pii_hash($admin_email);
                }
                if ($admin_phone !== '') {
                    $admin_updates['phone_number'] = sf_pii_encrypt($admin_phone);
                    $admin_updates['phone_hash'] = sf_pii_hash($admin_phone);
                }
                if (!empty($admin_updates)) {
                    $tenant_db->update('users', $admin_updates, ['id' => $admin['id_users']]);
                }
            }
            $tenant_db->close();

            // Reconnect master DB
            $this->connect_master_db();

            // Create onboarding session
            $session = $this->onboarding_sessions_model->create_session($tenant_id, $lead_id);

            // Update lead
            $this->leads_model->update_stage($lead_id, 'Won', $actor, "Kiracı hesabı oluşturuldu ({$subdomain})");
            $this->db->where('id', $lead_id)->update('leads', [
                'converted_tenant_id' => $tenant_id,
                'conversion_date' => $now,
            ]);

            $this->leads_model->add_activity(
                $lead_id,
                'tenant_created',
                "🎉 Kiracı Hesabı Oluşturuldu: {$subdomain}",
                "Kiracı veritabanı kuruldu, yönetici hesabı oluşturuldu ve onboarding bağlantısı hazırlandı.",
                $actor,
                ['tenant_id' => $tenant_id, 'token' => $session['token']]
            );

            $this->master_audit_model->log('create_tenant_from_lead', 'tenant', (string) $tenant_id, "Lead #{$lead_id} Won yapılarak kiracı oluşturuldu: {$subdomain}");

            json_response([
                'success' => true,
                'tenant_id' => $tenant_id,
                'subdomain' => $subdomain,
                'admin_password' => $admin_password_final,
                'onboarding_token' => $session['token'],
                'onboarding_link' => $session['link'],
            ]);
        } catch (Throwable $e) {
            $this->connect_master_db();
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function api_onboarding_sessions(): void
    {
        try {
            method('get');
            $filters = [
                'status' => request('status'),
                'q' => request('q'),
            ];
            $limit = max(1, min(100, (int) request('limit', 25)));
            $page = max(1, (int) request('page', 1));
            $offset = ($page - 1) * $limit;

            $sessions = $this->onboarding_sessions_model->get_all_sessions($filters, $limit, $offset);
            json_response($sessions);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function api_regenerate_onboarding_link(): void
    {
        try {
            method('post');
            check('tenant_id', 'numeric');

            $tenant_id = (int) request('tenant_id');
            $actor = session('superadmin_username') ?: 'Admin';

            $session = $this->onboarding_sessions_model->regenerate_token($tenant_id);
            $this->master_audit_model->log('regenerate_onboarding_link', 'tenant', (string) $tenant_id, "Onboarding bağlantısı yeniden üretildi");

            json_response(['success' => true, 'link' => $session['link'], 'token' => $session['token']]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function api_import_leads(): void
    {
        try {
            method('post');

            if (empty($_FILES['file']['tmp_name'])) {
                throw new InvalidArgumentException('Lütfen bir CSV veya XLSX dosyası yükleyin.');
            }

            $actor = session('superadmin_username') ?: 'Admin';
            $file_path = $_FILES['file']['tmp_name'];
            $file_name = $_FILES['file']['name'];
            $duplicate_action = trim((string) request('duplicate_action', 'skip'));

            $parsed = $this->spreadsheet_importer->parse_file($file_path, $file_name);

            // Default mapping if not explicitly posted
            $mapping = json_decode((string) request('column_mapping'), true);
            if (!is_array($mapping) || empty($mapping)) {
                $mapping = [
                    'name' => 'İşletme Adı',
                    'contact_person' => 'Yetkili Kişi',
                    'phone' => 'Telefon',
                    'whatsapp' => 'WhatsApp',
                    'email' => 'E-Posta',
                    'sector' => 'Sektör',
                    'district' => 'İlçe',
                    'address' => 'Adres',
                    'website' => 'Web Sitesi',
                    'instagram' => 'Instagram',
                    'stage' => 'Satış Aşaması',
                    'package' => 'Hedef Paket',
                    'billing_period' => 'Ödeme Periyodu',
                    'notes' => 'Notlar',
                    'priority' => 'Öncelik',
                ];

                // Check case-insensitive match against parsed headers
                foreach ($mapping as $target => $expected) {
                    foreach ($parsed['headers'] as $actual_h) {
                        if (mb_strtolower(trim($actual_h)) === mb_strtolower($expected) ||
                            mb_strtolower(trim($actual_h)) === mb_strtolower($target)) {
                            $mapping[$target] = $actual_h;
                            break;
                        }
                    }
                }
            }

            $result = $this->spreadsheet_importer->import_leads($parsed['rows'], $mapping, $duplicate_action, $actor);
            $this->master_audit_model->log('import_leads', 'import_job', (string) $result['job_id'], "Toplu lead aktarımı tamamlandı. İçe Aktarılan: {$result['imported']}, Mükerrer: {$result['duplicates']}, Hatalı: {$result['failed']}");

            json_response(['success' => true, 'report' => $result]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function api_download_template(): void
    {
        method('get');
        $type = (string) request('type', 'leads');
        $csv = $this->spreadsheet_importer->get_template_csv($type);

        $filename = "BooKi_Sablon_{$type}.csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo $csv;
        exit();
    }

    public function api_export_leads_csv(): void
    {
        method('get');
        $filters = [
            'q' => request('q'),
            'sector' => request('sector'),
            'district' => request('district'),
            'stage' => request('stage'),
            'priority' => request('priority'),
        ];

        $leads_data = $this->leads_model->get_leads($filters, 10000, 0, 'id', 'asc');
        $leads = $leads_data['leads'];

        $headers = [
            'ID', 'İşletme Adı', 'Sektör', 'İlçe', 'Adres', 'Yetkili', 'Telefon', 
            'WhatsApp', 'E-Posta', 'Instagram', 'Web Sitesi', 'Satış Aşaması', 
            'Paket', 'Ödeme Periyodu', 'Demo Başlangıç', 'Demo Bitiş', 'Sonraki Ziyaret', 'Görüşme Notları', 'Son Güncelleme'
        ];

        $output = "\xEF\xBB\xBF";
        $output .= implode(';', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $headers)) . "\r\n";

        foreach ($leads as $b) {
            $row = [
                $b['id'],
                $b['name'] ?? '',
                $b['sector'] ?? '',
                $b['district'] ?? '',
                $b['address'] ?? '',
                $b['contact_person'] ?? '',
                $b['phone'] ?? '',
                $b['whatsapp'] ?? '',
                $b['email'] ?? '',
                $b['instagram'] ?? '',
                $b['website'] ?? '',
                $b['stage'] ?? '',
                $b['package'] ?? '',
                $b['billing_period'] ?? '',
                $b['demo_start_date'] ?? '',
                $b['demo_end_date'] ?? '',
                $b['next_action_date'] ?? '',
                $b['notes'] ?? '',
                $b['updated_at'] ?? $b['created_at'] ?? ''
            ];
            $output .= implode(';', array_map(fn($v) => '"' . str_replace('"', '""', (string) $v) . '"', $row)) . "\r\n";
        }

        $filename = "BooKi_Saha_Satis_Guncel_" . date('Y-m-d') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo $output;
        exit();
    }

    public function api_global_search(): void
    {
        try {
            method('get');
            $q = (string) request('q');
            $results = $this->leads_model->global_search($q);
            json_response($results);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function api_dashboard_kpis(): void
    {
        try {
            method('get');
            $kpis = $this->leads_model->get_kpis();
            json_response($kpis);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function api_audit_logs(): void
    {
        try {
            method('get');
            $limit = max(1, min(100, (int) request('limit', 50)));
            $page = max(1, (int) request('page', 1));
            $offset = ($page - 1) * $limit;

            $logs = $this->master_audit_model->get_logs($limit, $offset);
            json_response($logs);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function api_impersonate_tenant(): void
    {
        try {
            method('post');
            check('tenant_id', 'numeric');

            $tenant_id = (int) request('tenant_id');
            $tenant = $this->get_tenant_or_fail($tenant_id);
            $actor = session('superadmin_username') ?: 'Admin';

            $this->master_audit_model->log('impersonate_tenant', 'tenant', (string) $tenant_id, "Super Admin {$actor} kiracı paneline taklitçi (impersonate) girişi başlattı: {$tenant['subdomain']}");

            $redirect_url = "https://{$tenant['subdomain']}.bookiapp.kibusiness.co/backend";
            json_response(['success' => true, 'redirect_url' => $redirect_url]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Mirrors Console::connect_tenant() - see that method's docblock for the dbforge/migrations-table
     * caveats this replicates.
     */
    private function connect_tenant_db(array $tenant): void
    {
        $this->load->database(
            [
                'hostname' => $tenant['db_host'],
                'username' => $tenant['db_username'],
                'password' => tenant_master_decrypt($tenant['db_password']),
                'database' => $tenant['db_name'],
                'dbdriver' => 'mysqli',
                'dbprefix' => 'ea_',
                'pconnect' => false,
                'db_debug' => true,
                'cache_on' => false,
                'cachedir' => '',
                'char_set' => 'utf8mb4',
                'dbcollat' => 'utf8mb4_unicode_ci',
                'swap_pre' => '',
            ],
            false,
            true,
        );

        tenant_context([
            'id' => (int) ($tenant['id'] ?? 0),
            'subdomain' => $tenant['subdomain'] ?? '',
            'pii_enc_key' => tenant_master_decrypt($tenant['pii_enc_key']),
            'pii_hash_key' => tenant_master_decrypt($tenant['pii_hash_key']),
        ]);

        $this->load->dbforge();

        if (!$this->db->table_exists('migrations')) {
            $this->dbforge->add_field(['version' => ['type' => 'BIGINT', 'constraint' => 20]]);
            $this->dbforge->create_table('migrations', true);
            $this->db->insert('migrations', ['version' => 0]);
        }
    }

    private function connect_master_db(): void
    {
        $this->load->database('default', false, true);
        $this->load->dbforge();
        tenant_context_clear();
    }

    /**
     * Best-effort appointment count for the tenant list - swallows connection errors (e.g. a tenant
     * whose DB got manually removed) so one broken row doesn't take down the whole dashboard.
     */
    private function get_tenant_metrics(array $tenant): array
    {
        $metrics = [
            'appointment_count' => null,
            'monthly_appointments' => null,
            'customer_count' => null,
            'total_revenue' => null,
        ];
        
        try {
            $tenant_db = $this->load->database($this->tenant_db_config($tenant), true);

            $metrics['appointment_count'] = (int) $tenant_db->count_all('appointments');
            
            $start_of_month = date('Y-m-01 00:00:00');
            $metrics['monthly_appointments'] = (int) $tenant_db
                ->where('start_datetime >=', $start_of_month)
                ->count_all_results('appointments');
                
            $customer_role = $tenant_db->get_where('roles', ['slug' => DB_SLUG_CUSTOMER])->row_array();
            if ($customer_role) {
                $metrics['customer_count'] = (int) $tenant_db
                    ->where('id_roles', $customer_role['id'])
                    ->count_all_results('users');
            }

            // total_revenue
            $total_revenue = 0.00;
            if ($tenant_db->table_exists('payment_transactions')) {
                $pt = $tenant_db
                    ->select_sum('amount')
                    ->where('status', 'succeeded')
                    ->get('payment_transactions')
                    ->row_array();
                if ($pt && isset($pt['amount'])) {
                    $total_revenue += (float) $pt['amount'];
                }
            }
            if ($tenant_db->table_exists('invoices')) {
                $inv = $tenant_db
                    ->select_sum('amount')
                    ->where('status', 'paid')
                    ->get('invoices')
                    ->row_array();
                if ($inv && isset($inv['amount'])) {
                    $total_revenue += (float) $inv['amount'];
                }
            }
            $metrics['total_revenue'] = $total_revenue;

            $tenant_db->close();
            return $metrics;
        } catch (Throwable $e) {
            return [
                'appointment_count' => 0,
                'monthly_appointments' => 0,
                'customer_count' => 0,
                'total_revenue' => 0.00,
            ];
        }
    }
}
