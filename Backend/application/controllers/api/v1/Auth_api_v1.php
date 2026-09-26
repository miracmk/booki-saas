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
 * Mobile Authentication & Multi-Tenant Discovery API v1 Controller.
 *
 * Provides token-based authentication (JWT) for the mobile app, supporting:
 * - Direct login with tenant subdomain
 * - Multi-tenant user login with automatic tenant discovery or tenant selection list
 * - Customer registration
 * - Current session verification (/me)
 * - Tenant lookup
 *
 * @package Controllers\Api\V1
 */
class Auth_api_v1 extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api');
        $this->load->library('accounts');
        $this->load->model('users_model');
        $this->load->model('roles_model');
    }

    /**
     * Mobile login endpoint.
     *
     * POST /api/v1/auth/login
     * Params:
     * - identifier: string (username or email)
     * - password: string
     * - tenant_subdomain: optional string
     */
    public function login(): void
    {
        try {
            method('post');

            $identifier = trim((string) (request('identifier') ?? request('username') ?? request('email')));
            $password = (string) request('password');
            $requested_tenant = trim((string) (request('tenant_subdomain') ?? request('tenant') ?? ''));

            if (empty($identifier) || empty($password)) {
                json_response([
                    'success' => false,
                    'message' => 'Lütfen kullanıcı adı / e-posta ve şifrenizi girin.',
                ], 400);
                return;
            }

            // Case 1: Tenant is already resolved in current context or explicitly specified
            if (tenant_context() !== null || !empty($requested_tenant)) {
                $tenant = tenant_context();

                if (empty($tenant) && !empty($requested_tenant)) {
                    $tenant = $this->db->get_where('tenants', [
                        'subdomain' => strtolower($requested_tenant),
                        'status' => 'active',
                    ])->row_array();

                    if (empty($tenant)) {
                        json_response([
                            'success' => false,
                            'message' => 'Belirtilen işletme bulunamadı veya aktif değil.',
                        ], 404);
                        return;
                    }

                    // Connect to the specific tenant's DB
                    $this->connect_to_tenant($tenant);
                }

                $user_data = $this->authenticate_in_current_tenant($identifier, $password);

                if (empty($user_data)) {
                    json_response([
                        'success' => false,
                        'message' => 'Geçersiz kullanıcı bilgileri veya şifre.',
                    ], 401);
                    return;
                }

                $token = $this->generate_auth_response($user_data, $tenant);
                return;
            }

            // Case 2: On master DB without tenant - multi-tenant discovery across active tenants
            if (is_multi_tenant_mode()) {
                $tenants = $this->db->get_where('tenants', ['status' => 'active'])->result_array();
                $matched_tenants = [];

                foreach ($tenants as $t) {
                    $match = $this->check_credentials_in_tenant($t, $identifier, $password);
                    if ($match !== null) {
                        $matched_tenants[] = [
                            'tenant' => [
                                'id' => (int) $t['id'],
                                'subdomain' => $t['subdomain'],
                                'company_name' => $t['company_name'] ?? $t['subdomain'],
                                'custom_domain' => $t['custom_domain'] ?? null,
                            ],
                            'user' => $match,
                        ];
                    }
                }

                if (empty($matched_tenants)) {
                    json_response([
                        'success' => false,
                        'message' => 'Kullanıcı adı/e-posta veya şifre hatalı.',
                    ], 401);
                    return;
                }

                // If user belongs to exactly one active tenant, authenticate immediately!
                if (count($matched_tenants) === 1) {
                    $single = $matched_tenants[0];
                    $tenant_obj = $this->db->get_where('tenants', ['id' => $single['tenant']['id']])->row_array();
                    $this->connect_to_tenant($tenant_obj);
                    $this->generate_auth_response($single['user'], $tenant_obj);
                    return;
                }

                // If user belongs to multiple tenants, return tenant selection list
                $selection_list = [];
                foreach ($matched_tenants as $m) {
                    $selection_list[] = [
                        'subdomain' => $m['tenant']['subdomain'],
                        'company_name' => $m['tenant']['company_name'],
                        'custom_domain' => $m['tenant']['custom_domain'],
                        'role' => $m['user']['role_slug'],
                        'user_name' => ($m['user']['first_name'] ?? '') . ' ' . ($m['user']['last_name'] ?? ''),
                    ];
                }

                json_response([
                    'success' => true,
                    'multiple_tenants' => true,
                    'message' => 'Hesabınız birden fazla işletmede bulundu. Lütfen giriş yapmak istediğiniz işletmeyi seçin.',
                    'tenants' => $selection_list,
                ]);
                return;
            }

            // Case 3: Standalone deployment (single-tenant)
            $user_data = $this->authenticate_in_current_tenant($identifier, $password);
            if (empty($user_data)) {
                json_response([
                    'success' => false,
                    'message' => 'Geçersiz kullanıcı bilgileri veya şifre.',
                ], 401);
                return;
            }

            $this->generate_auth_response($user_data, null);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Customer registration from mobile app.
     *
     * POST /api/v1/auth/register
     */
    public function register(): void
    {
        try {
            method('post');

            $tenant_subdomain = trim((string) request('tenant_subdomain'));
            $first_name = trim((string) request('first_name'));
            $last_name = trim((string) request('last_name'));
            $email = trim((string) request('email'));
            $phone_number = trim((string) request('phone_number'));
            $password = (string) request('password');

            if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
                json_response([
                    'success' => false,
                    'message' => 'Lütfen ad, soyad, e-posta ve şifre alanlarını doldurun.',
                ], 400);
                return;
            }

            $tenant = null;
            if (is_multi_tenant_mode()) {
                if (empty($tenant_subdomain)) {
                    json_response([
                        'success' => false,
                        'message' => 'Kayıt olmak istediğiniz işletme (tenant_subdomain) belirtilmelidir.',
                    ], 400);
                    return;
                }

                $tenant = $this->db->get_where('tenants', [
                    'subdomain' => strtolower($tenant_subdomain),
                    'status' => 'active',
                ])->row_array();

                if (empty($tenant)) {
                    json_response([
                        'success' => false,
                        'message' => 'Geçersiz veya aktif olmayan işletme.',
                    ], 404);
                    return;
                }

                $this->connect_to_tenant($tenant);
            }

            $this->load->model('customers_model');

            // Check if customer already exists
            $existing_user = $this->find_user_by_email_or_username($email);
            if (!empty($existing_user)) {
                json_response([
                    'success' => false,
                    'message' => 'Bu e-posta adresi ile kayıtlı bir hesap zaten var. Lütfen giriş yapın.',
                ], 409);
                return;
            }

            // Create customer user
            $customer_data = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone_number' => $phone_number,
            ];

            $customer_id = $this->customers_model->save($customer_data);

            // Set password in user_settings
            $salt = generate_salt();
            $hashed_password = hash_password($salt, $password);

            $this->db->insert('user_settings', [
                'id_users' => $customer_id,
                'username' => strtolower(explode('@', $email)[0] . '_' . random_int(100, 999)),
                'password' => $hashed_password,
                'salt' => $salt,
            ]);

            $user_data = [
                'user_id' => $customer_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone_number' => $phone_number,
                'role_slug' => DB_SLUG_CUSTOMER,
            ];

            $this->generate_auth_response($user_data, $tenant);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get current authenticated user profile.
     *
     * GET /api/v1/auth/me
     */
    public function me(): void
    {
        try {
            method('get');

            $this->api->auth();

            $user_id = (int) session('user_id');
            $user = $this->users_model->find($user_id);

            if (empty($user)) {
                json_response([
                    'success' => false,
                    'message' => 'Kullanıcı bulunamadı.',
                ], 404);
                return;
            }

            $role = $this->roles_model->find($user['id_roles']);
            $tenant = tenant_context();

            json_response([
                'success' => true,
                'user' => [
                    'id' => (int) $user['id'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email'],
                    'phone_number' => $user['phone_number'] ?? '',
                    'role' => $role['slug'] ?? session('role_slug'),
                    'timezone' => $user['timezone'] ?? 'Europe/Istanbul',
                    'language' => $user['language'] ?? 'turkish',
                ],
                'tenant' => $tenant ? [
                    'id' => (int) ($tenant['id'] ?? 0),
                    'subdomain' => $tenant['subdomain'] ?? '',
                    'company_name' => $tenant['company_name'] ?? $tenant['subdomain'] ?? '',
                    'custom_domain' => $tenant['custom_domain'] ?? null,
                ] : null,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Lookup active tenants by keyword or list public demo tenants.
     *
     * GET /api/v1/auth/tenants
     */
    public function tenants(): void
    {
        try {
            method('get');

            if (!is_multi_tenant_mode()) {
                json_response([
                    'success' => true,
                    'tenants' => [[
                        'subdomain' => 'default',
                        'company_name' => setting('company_name') ?: 'BooKi Rezervasyon',
                    ]],
                ]);
                return;
            }

            $query = trim((string) request('q'));

            $this->db->select('id, subdomain, company_name, custom_domain')
                ->where('status', 'active');

            if (!empty($query)) {
                $this->db->group_start()
                    ->like('subdomain', $query)
                    ->or_like('company_name', $query)
                    ->group_end();
            }

            $tenants = $this->db->limit(20)->get('tenants')->result_array();

            json_response([
                'success' => true,
                'tenants' => array_map(fn($t) => [
                    'id' => (int) $t['id'],
                    'subdomain' => $t['subdomain'],
                    'company_name' => $t['company_name'] ?? $t['subdomain'],
                    'custom_domain' => $t['custom_domain'],
                ], $tenants),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Connect database to specified tenant.
     */
    private function connect_to_tenant(array $tenant): void
    {
        $tenant_db_config = [
            'hostname' => $tenant['db_host'],
            'username' => $tenant['db_username'],
            'password' => tenant_master_decrypt($tenant['db_password']),
            'database' => $tenant['db_name'],
            'dbdriver' => 'mysqli',
            'dbprefix' => 'ea_',
            'pconnect' => false,
            'db_debug' => false,
            'cache_on' => false,
            'cachedir' => '',
            'char_set' => 'utf8mb4',
            'dbcollat' => 'utf8mb4_unicode_ci',
            'swap_pre' => '',
        ];

        $this->db = $this->load->database($tenant_db_config, true);
        tenant_context($tenant);
    }

    /**
     * Authenticate user credentials in the current database context.
     */
    private function authenticate_in_current_tenant(string $identifier, string $password): ?array
    {
        // 1. Try username check via accounts library
        $user_data = $this->accounts->check_login($identifier, $password);
        if (!empty($user_data)) {
            $user = $this->users_model->find($user_data['user_id']);
            return array_merge($user_data, [
                'first_name' => $user['first_name'] ?? '',
                'last_name' => $user['last_name'] ?? '',
                'email' => $user['email'] ?? $user_data['user_email'] ?? '',
            ]);
        }

        // 2. Try email match
        $user = $this->find_user_by_email_or_username($identifier);
        if (!empty($user)) {
            $user_settings = $this->db->get_where('user_settings', ['id_users' => $user['id']])->row_array();
            if (!empty($user_settings) && !empty($user_settings['password'])) {
                $salt = $user_settings['salt'] ?? '';
                if (verify_password($salt, $password, $user_settings['password'])) {
                    $role = $this->roles_model->find($user['id_roles']);
                    return [
                        'user_id' => (int) $user['id'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'email' => $user['email'],
                        'username' => $user_settings['username'] ?? '',
                        'role_slug' => $role['slug'] ?? DB_SLUG_CUSTOMER,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Check credentials for a tenant in isolated connection.
     */
    private function check_credentials_in_tenant(array $tenant, string $identifier, string $password): ?array
    {
        $tenant_db_config = [
            'hostname' => $tenant['db_host'],
            'username' => $tenant['db_username'],
            'password' => tenant_master_decrypt($tenant['db_password']),
            'database' => $tenant['db_name'],
            'dbdriver' => 'mysqli',
            'dbprefix' => 'ea_',
            'pconnect' => false,
            'db_debug' => false,
            'cache_on' => false,
            'cachedir' => '',
            'char_set' => 'utf8mb4',
            'dbcollat' => 'utf8mb4_unicode_ci',
            'swap_pre' => '',
        ];

        try {
            $tenant_db = $this->load->database($tenant_db_config, true);

            // Find matching user by username or email
            $user_row = $tenant_db->select('users.id, users.first_name, users.last_name, users.email, roles.slug as role_slug, user_settings.password, user_settings.salt, user_settings.username')
                ->from('users')
                ->join('roles', 'roles.id = users.id_roles')
                ->join('user_settings', 'user_settings.id_users = users.id')
                ->where('user_settings.username', $identifier)
                ->get()
                ->row_array();

            if (empty($user_row)) {
                // If not found by username, scan users for email match
                // (Note: in multi-tenant mode emails might be encrypted or hashed)
                $candidates = $tenant_db->select('users.id, users.first_name, users.last_name, users.email, roles.slug as role_slug, user_settings.password, user_settings.salt, user_settings.username')
                    ->from('users')
                    ->join('roles', 'roles.id = users.id_roles')
                    ->join('user_settings', 'user_settings.id_users = users.id')
                    ->get()
                    ->result_array();

                foreach ($candidates as $candidate) {
                    $decrypted_email = sf_pii_decrypt($candidate['email']) ?: $candidate['email'];
                    if (strcasecmp($decrypted_email, $identifier) === 0) {
                        $user_row = $candidate;
                        $user_row['email'] = $decrypted_email;
                        break;
                    }
                }
            }

            $tenant_db->close();

            if (!empty($user_row) && !empty($user_row['password'])) {
                if (verify_password($user_row['salt'] ?? '', $password, $user_row['password'])) {
                    return [
                        'user_id' => (int) $user_row['id'],
                        'first_name' => $user_row['first_name'],
                        'last_name' => $user_row['last_name'],
                        'email' => $user_row['email'],
                        'username' => $user_row['username'],
                        'role_slug' => $user_row['role_slug'],
                    ];
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'Auth_api_v1 error querying tenant ' . $tenant['subdomain'] . ': ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Find user by email (checking encrypted and plaintext) or username.
     */
    private function find_user_by_email_or_username(string $identifier): ?array
    {
        $users = $this->users_model->get();
        foreach ($users as $u) {
            $decrypted_email = sf_pii_decrypt($u['email']) ?: $u['email'];
            if (strcasecmp($decrypted_email, $identifier) === 0) {
                $u['email'] = $decrypted_email;
                return $u;
            }
        }

        $user_settings = $this->db->get_where('user_settings', ['username' => $identifier])->row_array();
        if (!empty($user_settings)) {
            return $this->users_model->find($user_settings['id_users']);
        }

        return null;
    }

    /**
     * Build and return final JSON auth response with JWT token.
     */
    private function generate_auth_response(array $user_data, ?array $tenant): void
    {
        $payload = [
            'user_id' => (int) $user_data['user_id'],
            'role_slug' => $user_data['role_slug'],
            'email' => $user_data['email'] ?? '',
            'tenant_id' => $tenant ? (int) ($tenant['id'] ?? 0) : null,
            'tenant_subdomain' => $tenant['subdomain'] ?? null,
        ];

        $token = Api::generate_user_token($payload);

        json_response([
            'success' => true,
            'multiple_tenants' => false,
            'token' => $token,
            'user' => [
                'id' => (int) $user_data['user_id'],
                'first_name' => $user_data['first_name'] ?? '',
                'last_name' => $user_data['last_name'] ?? '',
                'email' => $user_data['email'] ?? '',
                'role' => $user_data['role_slug'],
            ],
            'tenant' => $tenant ? [
                'id' => (int) ($tenant['id'] ?? 0),
                'subdomain' => $tenant['subdomain'],
                'company_name' => $tenant['company_name'] ?? $tenant['subdomain'],
                'custom_domain' => $tenant['custom_domain'] ?? null,
            ] : null,
        ]);
    }
}

