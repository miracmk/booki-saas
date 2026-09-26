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
 * BooKi controller.
 *
 * @property App_Benchmark $benchmark
 * @property App_Cache $cache
 * @property App_Calendar $calendar
 * @property App_Config $config
 * @property App_DB_forge $dbforge
 * @property App_DB_query_builder $db
 * @property App_DB_utility $dbutil
 * @property App_Email $email
 * @property App_Encrypt $encrypt
 * @property App_Encryption $encryption
 * @property App_Exceptions $exceptions
 * @property App_Hooks $hooks
 * @property App_Input $input
 * @property App_Lang $lang
 * @property App_Loader $load
 * @property App_Log $log
 * @property App_Migration $migration
 * @property App_Output $output
 * @property App_Profiler $profiler
 * @property App_Router $router
 * @property App_Security $security
 * @property App_Session $session
 * @property App_Upload $upload
 * @property App_URI $uri
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
class App_Controller extends CI_Controller
{
    /**
     * App_Controller constructor.
     */
    public function __construct()
    {
        parent::__construct(); // Autoloads 'database' - $this->db now points at the 'default' connection group (see database.php).

        $this->resolve_tenant(); // BooKi (2026-08-26) - see the method's docblock.

        $this->load->library('accounts');

        $this->check_storage_writable();
        $this->ensure_user_exists();
        $this->configure_timezone();
        $this->configure_language();
        $this->load_common_html_vars();
        $this->load_common_script_vars();
        $this->enforce_onboarding();
        $this->enforce_robots_policy();

        rate_limit($this->input->ip_address());
    }

    /**
     * BooKi (2026-08-26) - a freshly created SaaS tenant has no company profile yet
     * (tenant_create() only seeds the generic platform defaults). Redirects the tenant's own
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
     * BooKi (2026-08-26) - multi-tenant SaaS support.
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
        $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';
        $superadmin_domain = getenv('SUPERADMIN_DOMAIN') ?: 'admin-bookiapp.kibusiness.co';
        $marketplace_domain = getenv('MARKETPLACE_DOMAIN') ?: 'booki.kibusiness.co';
        $randevuburada_domain = getenv('RANDEVUBURADA_DOMAIN') ?: 'randevuburada.kibusiness.co';

        // BooKi (2026-08-26) - SaaS admin panel (admin-bookiapp.kibusiness.co): a completely
        // separate host from any tenant, never resolves to one - stays on the master DB for its whole
        // "Superadmin*" controller family (see SuperadminAuth.php's docblock). Any other controller
        // reached on this host 404s, same principle as Portal.php's bare-app-domain exception below.
        if ($host === $superadmin_domain) {
            if (str_starts_with(strtolower((string) $this->router->class), 'superadmin') || strtolower((string) $this->router->class) === 'customer_onboarding') {
                return;
            }

            // Zadarma webhook istisnası - Zadarma sunucusu webhook'u master DB bağlamında
            // çağırır (lead_activities master'da); admin host'ta da ulaşılabilir olmalı.
            if (strtolower((string) $this->router->class) === 'zadarma') {
                return;
            }

            abort(404, 'Not Found');
        }

        // BooKi / RandevuBurada - Marketplace discovery portal, marketing site, and Customer Onboarding: reads
        // from the master DB's `tenants`, `reviews`, and `onboarding_sessions` tables. Stays on master DB for any host.
        if (
            strtolower((string) $this->router->class) === 'marketplace'
            || strtolower((string) $this->router->class) === 'landing'
            || strtolower((string) $this->router->class) === 'customer_onboarding'
            || strtolower((string) $this->router->class) === 'zadarma'
            || strtolower((string) $this->router->class) === 'meta'
            || strtolower((string) $this->router->class) === 'places_photo'
        ) {
            return;
        }

        if ($host === $marketplace_domain || $host === $randevuburada_domain) {
            abort(404, 'Not Found');
        }

        // BooKi (2026-08-28) - observability: health check endpoints work on any host
        // without tenant resolution, so monitoring can function even when tenant resolution itself
        // is broken (e.g., during DNS misconfiguration or a deployment in-flight).
        if (strtolower((string) $this->router->class) === 'health') {
            return;
        }

        // BooKi Mobile - Check X-Tenant-Subdomain or X-Tenant header for direct tenant resolution
        // BooKi Mobile & Webhooks - Check X-Tenant headers or query/post tenant parameter for direct tenant resolution
        $tenant_header = $_SERVER['HTTP_X_TENANT_SUBDOMAIN'] ?? $_SERVER['HTTP_X_TENANT'] ?? null;
        if (!empty($tenant_header)) {
            $tenant = $this->db->get_where('tenants', ['subdomain' => strtolower(trim((string) $tenant_header))])->row_array();
        }

        if (empty($tenant) && !empty($_GET['tenant'])) {
            $tenant = $this->db->get_where('tenants', ['subdomain' => strtolower(trim((string) $_GET['tenant']))])->row_array();
        }

        if (empty($tenant) && !empty($_POST['tenant'])) {
            $tenant = $this->db->get_where('tenants', ['subdomain' => strtolower(trim((string) $_POST['tenant']))])->row_array();
        }

        if (empty($tenant)) {
            $tenant = $this->db->get_where('tenants', ['custom_domain' => $host])->row_array();
        }

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
            // Also allow auth_api_v1 for multi-tenant mobile authentication discovery before tenant selection.
            if (($host === $app_domain || empty($tenant)) && in_array(strtolower((string) $this->router->class), ['portal', 'auth_api_v1'], true)) {
                return;
            }

            // BooKi - Central Webhooks & OAuth Relay: Meta, WhatsApp, Instagram, Payment Webhooks
            if ($host === $app_domain && in_array(strtolower((string) $this->router->class), ['meta', 'whatsapp', 'instagram', 'payment_webhooks'], true)) {
                return;
            }

            // BooKi (2026-09-19) - Google OAuth central relay: the central app domain receives OAuth
            // callbacks from Google (https://bookiapp.kibusiness.co/google/oauth_callback) and relays
            // them to the originating tenant based on the cryptographic signature in the state parameter.
            if ($host === $app_domain && strtolower((string) $this->router->class) === 'google'
                && strtolower((string) $this->router->method) === 'oauth_callback') {
                return;
            }

            abort(404, 'Not Found');
        }

        // BooKi (2026-08-26) - license/trial expiry: checked on every request (no cron
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

        $tenant_hostname = $tenant['db_host'];
        if (($tenant_hostname === 'db' || strpos($tenant_hostname, '127.0.0.1') !== false) && !empty(Config::DB_HOST)) {
            $tenant_hostname = Config::DB_HOST;
        }

        $tenant_db_config = [
            'hostname' => $tenant_hostname,
            'username' => $tenant['db_username'],
            'password' => tenant_master_decrypt($tenant['db_password']),
            'database' => $tenant['db_name'],
            'dbdriver' => 'mysqli',
            'dbprefix' => 'ea_',
            'pconnect' => false,
            'db_debug' => defined('ENVIRONMENT') && ENVIRONMENT === 'development',
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
            // BooKi (2026-08-26) - carried through so load_common_html_vars() can show an
            // "expiring soon" banner well before the hard 402 cutoff above actually kicks in.
            'trial_ends_at' => $tenant['trial_ends_at'] ?? null,
            'license_expires_at' => $tenant['license_expires_at'] ?? null,
            // BooKi (2026-09-12) - Dalga 4 paket/plan sistemi (Free/Basic/Premium/Elite) -
            // see plan_helper.php::plan_allows(). Free-text on the master `tenants.plan` column
            // (unchanged schema) but now normalized to one of these 4 by the superadmin UI dropdown.
            'plan' => $tenant['plan'] ?? null,
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

        // BooKi (2026-09-10) - the tenant's own "Varsayılan Dil" (General Settings) used to
        // only pre-fill new user/customer records' OWN language field - it never actually changed
        // what language THIS request rendered in, which reads as "doesn't work" to whoever set it.
        // It's now also the fallback active language for anyone who hasn't personally chosen one
        // (no session/query override) - between that per-user choice and Config::LANGUAGE's
        // platform-wide default. Guarded like load_common_html_vars()'s settings lookups - the
        // `settings` table doesn't exist on master-DB-only requests (Portal/Superadmin/Marketplace).
        $tenant_default_language = null;

        if ($this->db->table_exists('settings')) {
            $tenant_default_language = setting('default_language');
        }

        // Priority: session (user's own choice) > query param > tenant's own default > platform default
        $language = null;

        if ($session_language && in_array($session_language, $available_languages)) {
            $language = $session_language;
        } elseif ($query_language && in_array($query_language, $available_languages)) {
            $language = $query_language;
        } elseif ($tenant_default_language && in_array($tenant_default_language, $available_languages)) {
            $language = $tenant_default_language;
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
        // BooKi (2026-08-26) - same reasoning as configure_timezone()'s existing guard: a
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
            // BooKi - whitelabeling: custom company_logo is only exposed when white-label
            // package and setting are active; otherwise falls back to BooKi platform logo.
            'company_name' => $has_settings ? setting('company_name') : null,
            'company_logo' => $has_settings ? white_label_logo() : base_url('assets/img/logo.png'),
            'industry_code' => $has_settings ? current_industry_code() : 'beauty_salon',
            'industry_info' => $has_settings ? current_industry_info() : null,
            'expiry_warning' => $this->build_expiry_warning(),
        ]);
    }

    /**
     * BooKi (2026-08-26) - "N gün kaldı" banner data for backend_header.php, shown only to
     * the admin role (the only one who'd act on it) and only once the deadline is within reach - the
     * hard 402 cutoff in resolve_tenant() already covers "already expired", this is the advance
     * warning that comes before it.
     */
    private function build_expiry_warning(): ?array
    {
        $tenant = tenant_context();

        if (!$tenant || session('role_slug') !== 'admin') {
            return null;
        }

        $warn_days = 7;
        $now = new DateTime();
        $soonest = null;
        $label = null;

        foreach (['license_expires_at' => 'Lisansınızın', 'trial_ends_at' => 'Deneme sürenizin'] as $field => $field_label) {
            if (empty($tenant[$field])) {
                continue;
            }

            $expires_at = new DateTime($tenant[$field]);

            if ($expires_at < $now) {
                continue; // already expired - resolve_tenant() already blocks the request with a 402.
            }

            if ($soonest === null || $expires_at < $soonest) {
                $soonest = $expires_at;
                $label = $field_label;
            }
        }

        if ($soonest === null || $now->diff($soonest)->days > $warn_days) {
            return null;
        }

        return [
            'label' => $label,
            'days_left' => $now->diff($soonest)->days,
            'date' => $soonest->format('d.m.Y'),
        ];
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
     * Guarantees DST safety and persistent UTC+3 resolution (Europe/Istanbul).
     */
    private function configure_timezone(): void
    {
        $default_timezone = null;
        if ($this->db->table_exists('settings')) {
            $default_timezone = setting('default_timezone');
        }

        if (empty($default_timezone) || $default_timezone === 'UTC') {
            $default_timezone = 'Europe/Istanbul';
        }

        try {
            new DateTimeZone($default_timezone);
            date_default_timezone_set($default_timezone);
        } catch (Throwable $e) {
            date_default_timezone_set('Europe/Istanbul');
        }
    }

    /**
     * Enforce strict SEO & crawler indexing policy via HTTP headers:
     * Public booking and marketing landing pages remain indexable;
     * All private authenticated, administrative, customer portal, and API pages emit X-Robots-Tag: noindex, nofollow.
     */
    private function enforce_robots_policy(): void
    {
        $public_controllers = ['booking', 'landing', 'booking_confirmation', 'booking_cancellation', 'review', 'about', 'privacy', 'legal'];
        $current_controller = strtolower($this->router->class ?? '');

        if (!in_array($current_controller, $public_controllers, true) || session('user_id')) {
            if (!headers_sent()) {
                header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
            }
        }
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

if (!class_exists('App_Controller', false)) {
    class_alias(App_Controller::class, 'App_Controller');
}
