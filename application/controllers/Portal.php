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
     * Given a username/email, find which active tenant it belongs to and return that tenant's login
     * URL. Fans out to each active tenant's own database in turn (small tenant counts expected for
     * the foreseeable future - same tradeoff Console.php's tenant loops already accept) rather than
     * keeping a separate, syncable email-&gt;tenant index.
     */
    public function find_tenant(): void
    {
        try {
            method('post');

            $this->apply_lookup_rate_limit();

            check('identifier', 'string');

            $identifier = trim((string) request('identifier'));

            if ($identifier === '' || strlen($identifier) > 255) {
                throw new InvalidArgumentException(lang('invalid_credentials_provided'));
            }

            $tenants = $this->db
                ->get_where('tenants', ['status' => 'active'])
                ->result_array();

            foreach ($tenants as $tenant) {
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

                    // Plain '=' (not LOWER()) - utf8mb4_unicode_ci is already case-insensitive, and
                    // CI3's Query Builder mishandles the dbprefix substitution for table references
                    // wrapped inside a raw SQL function expression used as a where() key.
                    $match = $tenant_db
                        ->select('user_settings.username')
                        ->from('user_settings')
                        ->join('users', 'users.id = user_settings.id_users')
                        ->where('user_settings.username', $identifier)
                        ->or_where('users.email', $identifier)
                        ->get()
                        ->row_array();

                    $tenant_db->close();
                } catch (Throwable $e) {
                    // An unreachable/broken tenant DB should not block resolving the others.
                    log_message('error', 'Portal::find_tenant() could not query tenant "' .
                        $tenant['subdomain'] . '": ' . $e->getMessage());
                    continue;
                }

                if (!empty($match)) {
                    $host = $tenant['custom_domain'] ?: ($tenant['subdomain'] . '-reservationapp.kibusiness.co');

                    // Constant-time-ish: always do the same amount of work whether found early or late
                    // is not critical here (unlike password checks) - which tenant owns a given
                    // username is not itself secret in the way a password is.
                    json_response([
                        'success' => true,
                        'login_url' => 'https://' . $host . '/login?u=' . rawurlencode($match['username']),
                    ]);
                    return;
                }
            }

            json_response([
                'success' => false,
                'message' => lang('invalid_credentials_provided'),
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
