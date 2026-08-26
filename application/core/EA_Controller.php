<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * Ki Reservation controller.
 *
 * @property EA_Benchmark $benchmark
 * @property EA_Cache $cache
 * @property EA_Calendar $calendar
 * @property EA_Config $config
 * @property EA_DB_forge $dbforge
 * @property EA_DB_query_builder $db
 * @property EA_DB_utility $dbutil
 * @property EA_Email $email
 * @property EA_Encrypt $encrypt
 * @property EA_Encryption $encryption
 * @property EA_Exceptions $exceptions
 * @property EA_Hooks $hooks
 * @property EA_Input $input
 * @property EA_Lang $lang
 * @property EA_Loader $load
 * @property EA_Log $log
 * @property EA_Migration $migration
 * @property EA_Output $output
 * @property EA_Profiler $profiler
 * @property EA_Router $router
 * @property EA_Security $security
 * @property EA_Session $session
 * @property EA_Upload $upload
 * @property EA_URI $uri
 *
 * @property Admins_model $admins_model
 * @property Appointments_model $appointments_model
 * @property Service_categories_model $service_categories_model
 * @property Consents_model $consents_model
 * @property Customers_model $customers_model
 * @property Providers_model $providers_model
 * @property Roles_model $roles_model
 * @property Secretaries_model $secretaries_model
 * @property Services_model $services_model
 * @property Settings_model $settings_model
 * @property Unavailabilities_model $unavailabilities_model
 * @property Users_model $users_model
 * @property Webhooks_model $webhooks_model
 * @property Blocked_periods_model $blocked_periods_model
 *
 * @property Accounts $accounts
 * @property Api $api
 * @property Cleanup $cleanup
 * @property Availability $availability
 * @property Email_messages $email_messages
 * @property Google_Sync $google_sync
 * @property Caldav_Sync $caldav_sync
 * @property Ics_file $ics_file
 * @property Instance $instance
 * @property Ldap_client $ldap_client
 * @property Notifications $notifications
 * @property Permissions $permissions
 * @property Synchronization $synchronization
 * @property Timezones $timezones
 * @property Webhooks_client $webhooks_client
 */
class EA_Controller extends CI_Controller
{
    /**
     * EA_Controller constructor.
     */
    public function __construct()
    {
        parent::__construct(); // Autoloads 'database' - $this->db now points at the 'default' connection group (see database.php).

        $this->resolve_tenant(); // Ki Reservation (2026-08-26) - see the method's docblock.

        $this->load->library('accounts');

        $this->check_storage_writable();
        $this->ensure_user_exists();
        $this->configure_timezone();
        $this->configure_language();
        $this->load_common_html_vars();
        $this->load_common_script_vars();
        $this->enforce_onboarding();

        rate_limit($this->input->ip_address());
    }

    /**
     * Ki Reservation (2026-08-26) - a freshly created SaaS tenant has no company profile yet
     * (tenant_create() only seeds the generic EasyAppointments defaults). Redirects the tenant's own
     * admin to Onboarding until they submit it (tracked by the 'onboarding_completed' setting).
     * Standalone deployments (tenant_context() null - e.g. Salon Flora's own production) are never
     * affected, and only the admin role is gated - providers/secretaries can use the app right away.
     */
    private function enforce_onboarding(): void
    {
        // NOTE: NOT is_multi_tenant_mode() here - by this point resolve_tenant() has already swapped
        // $this->db to the TENANT's own database (which has no `tenants` table), so that check would
        // always read as false. tenant_context() being set is what actually means "this request was
        // resolved to some tenant" - resolve_tenant() sets it right after the swap.
        if (is_cli() || !tenant_context()) {
            return;
        }

        if (!session('user_id') || session('role_slug') !== 'admin') {
            return;
        }

        if (!$this->db->table_exists('settings') || setting('onboarding_completed') === '1') {
            return;
        }

        if (strtolower((string) $this->router->class) === 'onboarding') {
            return;
        }

        // redirect() only sends the Location header, it does not stop execution (unlike abort()) -
        // this runs from the constructor, well before the router invokes the actual action method, so
        // without an explicit exit that action would still run and render its own output afterwards.
        redirect('onboarding');
        exit();
    }

    /**
     * Ki Reservation (2026-08-26) - multi-tenant SaaS support.
     *
     * The SAME codebase serves single-tenant/standalone deployments (e.g. Salon Flora's own
     * production - the 'default' DB IS the tenant's own database, nothing to do here) and the
     * multi-tenant cloud SaaS (the 'default' DB is a lightweight MASTER database holding only a
     * `tenants` catalog). Distinguishing the two is a single, cheap `table_exists('tenants')` check
     * (see is_multi_tenant_mode(), tenant_helper.php) - no separate deployment-mode config flag to
     * keep in sync.
     *
     * In multi-tenant mode: resolves the request's Host header to a tenant row in the master DB,
     * then swaps $this->db to that tenant's own database for the rest of the request via
     * Loader::database($config_array, FALSE, TRUE) - every model/library in the app already just
     * uses $this->db, so nothing else needs to know multi-tenancy exists. Sets tenant_context() so
     * salonflora_crypto_helper.php can use the tenant's own PII keys instead of the env-var ones.
     *
     * Skipped for CLI entirely - Console.php's multi-tenant-aware commands (migrate/sync/cleanup)
     * connect to each tenant's DB explicitly themselves, in a loop, which this per-request/single-
     * tenant-swap model doesn't fit.
     */
    private function resolve_tenant(): void
    {
        if (is_cli()) {
            return;
        }

        if (!is_multi_tenant_mode()) {
            return; // Single-tenant/standalone deployment - 'default' is already the tenant DB.
        }

        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $host = preg_replace('/:\d+$/', '', $host); // strip a port, if present
        $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'reservationapp.kibusiness.co';
        $superadmin_domain = getenv('SUPERADMIN_DOMAIN') ?: 'reservationadmin.kibusiness.co';

        // Ki Reservation (2026-08-26) - SaaS admin panel (reservationadmin.kibusiness.co): a completely
        // separate host from any tenant, never resolves to one - stays on the master DB for its whole
        // "Superadmin*" controller family (see SuperadminAuth.php's docblock). Any other controller
        // reached on this host 404s, same principle as Portal.php's bare-app-domain exception below.
        if ($host === $superadmin_domain) {
            if (str_starts_with(strtolower((string) $this->router->class), 'superadmin')) {
                return;
            }

            abort(404, 'Not Found');
        }

        $tenant = $this->db->get_where('tenants', ['custom_domain' => $host])->row_array();

        if (!$tenant) {
            // Kiracı subdomain'i iki kalıptan biriyle çözülür: "acme-reservationapp.kibusiness.co"
            // (kök domain, firma isimlerinde kullanılan güncel kalıp) veya
            // "acme.reservationapp.kibusiness.co" (eski/geriye dönük uyumluluk kalıbı).
            $app_domain_pattern = preg_quote($app_domain, '/');

            if (preg_match('/^([a-z0-9-]+)-' . $app_domain_pattern . '$/', $host, $matches)
                || preg_match('/^([a-z0-9-]+)\.' . $app_domain_pattern . '$/', $host, $matches)) {
                $tenant = $this->db->get_where('tenants', ['subdomain' => $matches[1]])->row_array();
            }
        }

        if (!$tenant || $tenant['status'] !== 'active') {
            // Bare app-domain exception: the "which company are you with?" portal is the ONE thing
            // allowed to run against the master DB with no tenant resolved - see Portal.php's docblock.
            if ($host === $app_domain && strtolower((string) $this->router->class) === 'portal') {
                return;
            }

            abort(404, 'Not Found');
        }

        // Ki Reservation (2026-08-26) - license/trial expiry: checked on every request (no cron
        // needed) rather than a separate "expired" status value, so the superadmin panel's date
        // fields are the single source of truth - flipping `status` to suspended is still a distinct,
        // manual action (see Faz 5b's onboarding gate for the same "check inline, no background job"
        // reasoning).
        $now = date('Y-m-d H:i:s');

        if (
            (!empty($tenant['license_expires_at']) && $tenant['license_expires_at'] < $now) ||
            (!empty($tenant['trial_ends_at']) && $tenant['trial_ends_at'] < $now)
        ) {
            abort(402, 'Bu hesabın aboneliği/deneme süresi sona erdi. Devam etmek için lütfen bizimle iletişime geçin.');
        }

        $tenant_db_config = [
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

        $this->load->database($tenant_db_config, false, true);

        tenant_context([
            'id' => (int) $tenant['id'],
            'subdomain' => $tenant['subdomain'],
            'pii_enc_key' => tenant_master_decrypt($tenant['pii_enc_key']),
            'pii_hash_key' => tenant_master_decrypt($tenant['pii_hash_key']),
        ]);
    }

    private function ensure_user_exists()
    {
        $user_id = session('user_id');

        if (!$user_id || !$this->db->table_exists('users')) {
            return;
        }

        if (!$this->accounts->does_account_exist($user_id)) {
            session_destroy();

            abort(403, 'Forbidden');
        }
    }

    /**
     * Configure the language.
     */
    private function configure_language()
    {
        $session_language = session('language');
        $query_language = request('language');
        $available_languages = config('available_languages');

        // Priority: session > query param > default (english)
        $language = null;

        if ($session_language && in_array($session_language, $available_languages)) {
            $language = $session_language;
        } elseif ($query_language && in_array($query_language, $available_languages)) {
            $language = $query_language;
        }

        if ($language) {
            $language_codes = config('language_codes');

            config([
                'language' => $language,
                'language_code' => array_search($language, $language_codes) ?: 'en',
            ]);
        }

        $this->lang->load('translations');
    }

    /**
     * Load common script vars for all requests.
     */
    private function load_common_html_vars()
    {
        // Ki Reservation (2026-08-26) - same reasoning as configure_timezone()'s existing guard: a
        // connected DB with no `settings` table happens for master-DB-only CLI commands
        // (console master_install/tenant_create, before a tenant DB is even selected/created) as well
        // as a not-yet-migrated fresh install.
        $has_settings = $this->db->table_exists('settings');

        html_vars([
            'base_url' => config('base_url'),
            'index_page' => config('index_page'),
            'available_languages' => config('available_languages'),
            'language' => $this->lang->language,
            'csrf_token' => $this->security->get_csrf_hash(),
            // Salon Flora customization - whitelabeling: company_name/company_logo were already
            // editable in General Settings and consumed by the public booking page, but the backend
            // header (backend_header.php) hardcoded "KI RESERVATION" + the platform's own logo
            // regardless of what a tenant configured. Loading them here, for every backend request,
            // makes the header consume the same setting the admin panel lets staff edit.
            'company_name' => $has_settings ? setting('company_name') : null,
            'company_logo' => $has_settings ? setting('company_logo') : null,
        ]);
    }

    /**
     * Load common script vars for all requests.
     */
    private function load_common_script_vars()
    {
        script_vars([
            'base_url' => config('base_url'),
            'index_page' => config('index_page'),
            'available_languages' => config('available_languages'),
            'csrf_token' => $this->security->get_csrf_hash(),
            'language' => config('language'),
            'language_code' => config('language_code'),
        ]);
    }

    /**
     * Set the default timezone of the app, based on the selected setting.
     */
    private function configure_timezone(): void
    {
        if (!$this->db->table_exists('settings')) {
            return;
        }

        $default_timezone = setting('default_timezone');

        date_default_timezone_set($default_timezone);
    }

    /**
     * Check if the storage folder is writable.
     */
    private function check_storage_writable(): void
    {
        $storage_path = APPPATH . '../storage';

        if (!is_dir($storage_path)) {
            show_error(
                'The storage folder does not exist: ' .
                    $storage_path .
                    '. ' .
                    'Please create this directory and ensure it is writable by the web server.',
                500,
                'Storage Configuration Error',
            );
        }

        if (!is_writable($storage_path)) {
            show_error(
                'The storage folder is not writable: ' .
                    $storage_path .
                    '. ' .
                    'Please ensure the web server has write permissions to this directory and its subdirectories (cache, logs, sessions, uploads).',
                500,
                'Storage Configuration Error',
            );
        }
    }
}
