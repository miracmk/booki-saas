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
 * SaaS admin panel (reservationadmin.kibusiness.co) - tenant CRUD + plan/license tracking. Runs
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

        html_vars([
            'page_title' => 'BooKi - Kiracılar',
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

            $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'reservationapp.kibusiness.co';

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

            $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'reservationapp.kibusiness.co';
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
