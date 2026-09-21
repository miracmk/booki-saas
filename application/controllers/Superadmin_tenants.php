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
 * against the master DB (see EA_Controller::resolve_tenant()'s superadmin host exception). Tenant
 * provisioning here mirrors Console::tenant_create() exactly (same DB-swap dance, same
 * Instance::migrate()/seed() call) - duplicated rather than shared because Console's version is
 * CLI-only (private connect_tenant()/connect_master() helpers, echo-based output) and this is a web
 * JSON endpoint; keep the two in sync if the provisioning steps ever change.
 */
class Superadmin_tenants extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!session('superadmin_id')) {
            redirect('superadmin_auth');
            exit();
        }

        $this->load->library('instance');
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
     * EA_Controller::resolve_tenant() sets it for a normal (non-superadmin) request. Must be called
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
            $id = (int) request('id');
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

            $from = $zc->sip_login();
            if ($from === '') {
                throw new InvalidArgumentException(
                    'Zadarma SIP Login tanımlı değil. Platform Ayarları > Zadarma SIP bölümünden ' .
                    'SIP Login (örn: 325384-100) değerini kaydedin.'
                );
            }

            $raw_target = (string) ($lead['phone'] ?: $lead['whatsapp'] ?: '');
            $to = $zc->normalize_phone($raw_target);
            if ($to === '' || strlen(preg_replace('/[^0-9]/', '', $to)) < 10) {
                throw new InvalidArgumentException(
                    'Aranacak numara geçersiz: "' . $raw_target . '". Numarayı uluslararası formatta girin (örn: 905XXXXXXXXX).'
                );
            }

            $call_mode = master_setting('zadarma_call_mode') ?: 'callback';

            // Gerçek Zadarma API çağrısı (HMAC-SHA1 imzalı).
            $result = $zc->request_callback($from, $to, $from);

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
                'message' => "Önce {$from} numaralı dahili telefonunuz çalacak; açtığınızda {$to} aranacak.",
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
                'google_client_id', 'google_client_secret', 'google_project_id',
                // Platform SMTP & IMAP
                'platform_smtp_host', 'platform_smtp_port', 'platform_smtp_crypto',
                'platform_smtp_user', 'platform_smtp_pass', 'platform_smtp_from_name', 'platform_smtp_from_address',
                'platform_imap_host', 'platform_imap_port', 'platform_imap_crypto', 'platform_imap_user', 'platform_imap_pass',
                // WhatsApp & Messaging
                'whatsapp_quick_templates', 'wa_bridge_url', 'wa_bridge_secret',
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
            check('id', 'numeric');

            $id = (int) request('id');
            $actor = session('superadmin_username') ?: 'Admin';
            $fields = ['name', 'sector', 'district', 'address', 'contact_person', 'phone', 'whatsapp', 'email', 'website', 'instagram', 'reservation_type', 'stage', 'priority', 'package', 'billing_period', 'notes', 'tags', 'potential_mrr', 'next_action', 'next_action_date', 'demo_start_date', 'demo_end_date'];

            $data = [];
            foreach ($fields as $f) {
                if (request($f) !== null) {
                    $data[$f] = trim((string) request($f));
                }
            }

            $success = $this->leads_model->update_lead($id, $data, $actor);
            if (!$success) {
                throw new InvalidArgumentException("Lead #{$id} güncellenemedi.");
            }

            $this->master_audit_model->log('update_lead', 'lead', (string) $id, "Lead bilgileri güncellendi");
            json_response(['success' => true]);
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
