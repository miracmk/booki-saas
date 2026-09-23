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
 * Portal controller - the bare app-domain (reservationapp.kibusiness.co) entry point.
 *
 * Multi-tenant SaaS only. This controller is the ONE thing EA_Controller::resolve_tenant() lets
 * through on the bare app domain without a resolved tenant - $this->db here is still the MASTER DB
 * (holds only the `tenants` catalog, no `users`/`settings` tables), so it does not attempt real
 * password authentication itself. It only identifies which tenant a submitted username/email belongs
 * to (fanning out across active tenants' own databases, the same "loop every tenant" pattern
 * Console.php's migrate/sync/cleanup already use) and redirects the browser to that tenant's own
 * domain to actually log in there, against its own DB, exactly as it would if reached directly.
 */
class Portal extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!is_multi_tenant_mode()) {
            abort(404, 'Not Found');
        }
    }

    /**
     * Render the "which company are you with?" form.
     */
    public function index(): void
    {
        method('get');

        html_vars([
            'page_title' => lang('login'),
            'csrf_token' => $this->security->get_csrf_hash(),
        ]);

        $this->load->view('pages/portal');
    }

    /**
     * Given an enterprise identifier (business username / tenant subdomain, custom domain, or staff username/email),
     * resolve which active tenant it belongs to and return that tenant's login URL.
     * Checks the master tenants catalog first for instant response, and falls back to scanning tenant user tables.
     */
    public function find_tenant(): void
    {
        try {
            method('post');

            $this->apply_lookup_rate_limit();

            check('identifier', 'string');

            $raw_identifier = trim((string) request('identifier'));

            if ($raw_identifier === '' || strlen($raw_identifier) > 255) {
                throw new InvalidArgumentException(lang('invalid_credentials_provided'));
            }

            $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';
            $app_domain_pattern = preg_quote($app_domain, '/');

            // Normalize identifier: strip protocol, paths, ports, leading @
            $clean_identifier = preg_replace('#^https?://#i', '', $raw_identifier);
            $clean_identifier = preg_replace('#/.*$#', '', $clean_identifier);
            $clean_identifier = preg_replace('#:\d+$#', '', $clean_identifier);

            if (preg_match('/^([a-z0-9-]+)-' . $app_domain_pattern . '$/i', $clean_identifier, $m)
                || preg_match('/^([a-z0-9-]+)\.' . $app_domain_pattern . '$/i', $clean_identifier, $m)) {
                $subdomain_candidate = strtolower($m[1]);
            } else {
                $subdomain_candidate = strtolower(ltrim($clean_identifier, '@'));
            }

            // Step 1: Direct lookup in master tenants catalog by subdomain or custom_domain
            $this->db->group_start()
                ->where('subdomain', $subdomain_candidate)
                ->or_where('custom_domain', $clean_identifier)
                ->or_where('custom_domain', $raw_identifier);

            if ($this->db->field_exists('company_name', 'tenants')) {
                $this->db->or_where('company_name', $raw_identifier);
            }

            $tenant = $this->db->group_end()
                ->where('status', 'active')
                ->get('tenants')
                ->row_array();

            if (!empty($tenant)) {
                $host = $tenant['custom_domain'] ?: ($tenant['subdomain'] . '-' . $app_domain);
                json_response([
                    'success' => true,
                    'tenant_name' => $tenant['subdomain'],
                    'login_url' => 'https://' . $host . '/login',
                ]);
                return;
            }

            // Step 2: Fallback across active tenant DBs for username / email matching
            $tenants = $this->db
                ->get_where('tenants', ['status' => 'active'])
                ->result_array();

            foreach ($tenants as $t) {
                $tenant_db_config = [
                    'hostname' => $t['db_host'],
                    'username' => $t['db_username'],
                    'password' => tenant_master_decrypt($t['db_password']),
                    'database' => $t['db_name'],
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

                    $match = $tenant_db
                        ->select('user_settings.username')
                        ->from('user_settings')
                        ->join('users', 'users.id = user_settings.id_users')
                        ->where('user_settings.username', $raw_identifier)
                        ->or_where('users.email', $raw_identifier)
                        ->get()
                        ->row_array();

                    $tenant_db->close();
                } catch (Throwable $e) {
                    log_message('error', 'Portal::find_tenant() could not query tenant "' .
                        $t['subdomain'] . '": ' . $e->getMessage());
                    continue;
                }

                if (!empty($match)) {
                    $host = $t['custom_domain'] ?: ($t['subdomain'] . '-' . $app_domain);
                    json_response([
                        'success' => true,
                        'tenant_name' => $t['subdomain'],
                        'login_url' => 'https://' . $host . '/login?u=' . rawurlencode($match['username']),
                    ]);
                    return;
                }
            }

            json_response([
                'success' => false,
                'message' => 'Belirtilen işletme kullanıcı adı ("' . htmlspecialchars($raw_identifier, ENT_QUOTES, 'UTF-8') . '") bulunamadı. Lütfen işletme adınızı kontrol edin.',
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Apply rate limiting to the tenant-lookup endpoint (mirrors Login::apply_login_rate_limit() -
     * this fans out to every tenant DB per call, so it is at least as expensive to abuse).
     */
    private function apply_lookup_rate_limit(): void
    {
        try {
            $this->load->driver('cache', ['adapter' => 'file']);

            if (!isset($this->cache) || !is_object($this->cache)) {
                return;
            }

            $ip = $this->input->ip_address();
            $cache_key = 'portal_lookup_attempts_' . str_replace([':', '.'], '_', $ip);

            $attempts = $this->cache->get($cache_key);

            if ($attempts === false) {
                $this->cache->save($cache_key, 1, 300); // 5 minutes
                return;
            }

            $this->cache->save($cache_key, $attempts + 1, 300);

            if ($attempts >= 10) {
                throw new RuntimeException('Too many attempts. Please try again in a few minutes.');
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            log_message('error', 'Cache error in portal lookup rate limiting: ' . $e->getMessage());
        }
    }
}
