<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - multi-tenant SaaS support (2026-08-26).
 *
 * The SAME codebase serves two deployment shapes:
 *
 * 1. Single-tenant / standalone (e.g. Salon Flora's own production deployment) - the 'default' DB
 *    connection group IS the tenant's own database, exactly as before multi-tenancy was added.
 * 2. Multi-tenant cloud SaaS - the 'default' connection is a lightweight MASTER database holding only
 *    the `tenants` catalog; App_Controller::resolve_tenant() (called from the constructor every
 *    controller shares) resolves the request's Host header to a tenant row and swaps $this->db to
 *    that tenant's own database for the rest of the request. Console.php's connect_tenant()/
 *    connect_master() do the CLI equivalent for multi-tenant-aware commands (migrate/sync/cleanup/
 *    tenant_create), looping over tenants explicitly instead of resolving one from a Host header.
 *
 * tenant_context() is the single source of truth for "which tenant is this request/CLI-iteration in"
 * - set by resolve_tenant()/connect_tenant() (or left null in single-tenant mode), read by
 * salonflora_crypto_helper.php (per-tenant PII keys) and anywhere else that needs to know.
 * ---------------------------------------------------------------------------- */

if (!function_exists('tenant_context')) {
    /**
     * Get, set, or clear the current request's/CLI-iteration's tenant context.
     *
     * A sentinel default (rather than null) distinguishes "not passed - just reading" from
     * "explicitly clear it" (tenant_context_clear() passes null through).
     *
     * @param array|null|string $tenant Pass an array to set it, null to explicitly clear it (see
     *   tenant_context_clear()), or omit entirely to just read the current value.
     *
     * @return array|null Null in single-tenant/standalone mode, or after being cleared.
     */
    function tenant_context(array|null|string $tenant = '__unset__'): ?array
    {
        static $current = null;

        if ($tenant !== '__unset__') {
            $current = $tenant;
        }

        return $current;
    }
}

if (!function_exists('tenant_context_clear')) {
    /**
     * Reset the tenant context back to null. Used by Console.php's connect_master() after
     * connect_tenant() set it, so a subsequent master-DB operation never runs under a stale tenant's
     * PII keys.
     */
    function tenant_context_clear(): void
    {
        tenant_context(null);
    }
}

if (!function_exists('is_multi_tenant_mode')) {
    /**
     * Whether the currently-connected 'default' DB is a multi-tenant master DB (has a `tenants`
     * table) rather than a single tenant's own DB. Cheap to call repeatedly - CI_DB caches
     * table_exists() results per request via its own table list cache.
     */
    function is_multi_tenant_mode(): bool
    {
        if (tenant_context() !== null) {
            return true;
        }

        $CI = &get_instance();

        // BooKi (2026-09-16) - Check both prefixed (ea_tenants) and non-prefixed (tenants) table names
        // to handle different environments (multi-tenant SaaS vs single-tenant).
        return $CI->db->table_exists($CI->db->dbprefix('tenants')) || $CI->db->table_exists('tenants');
    }
}

if (!function_exists('master_setting')) {
    /**
     * BooKi (2026-08-26) - read (or write, if $value is passed) a platform-wide key/value
     * setting from the master DB's `master_settings` table (e.g. the shared "Ki Business" Google
     * OAuth Client ID/Secret - see Google_sync::get_client_id()/get_client_secret()).
     *
     * Deliberately reconnects to the 'default' connection GROUP BY NAME (not $CI->db, which during a
     * tenant request has already been swapped to that tenant's own DB - see
     * App_Controller::resolve_tenant()) via a throwaway, non-active connection object
     * ($this->load->database('default', true) - the TRUE "return, don't replace $this->db" form), so
     * calling this never disturbs whichever DB the current request is actually working against.
     * Single-tenant/standalone deployments have no `master_settings` table at all - always returns
     * null on read and no-ops on write so callers don't need their own is_multi_tenant_mode() guard.
     */
    function master_setting(string $name, ?string $value = '__unset__'): ?string
    {
        if (!is_multi_tenant_mode()) {
            return null;
        }

        $CI = &get_instance();
        $master_db = $CI->load->database('default', true);

        if (!$master_db || !$master_db->table_exists('master_settings')) {
            return null;
        }

        if ($value !== '__unset__') {
            if ($master_db->get_where('master_settings', ['name' => $name])->num_rows() > 0) {
                $master_db->update('master_settings', ['value' => $value], ['name' => $name]);
            } else {
                $master_db->insert('master_settings', ['name' => $name, 'value' => $value]);
            }

            return $value;
        }

        $row = $master_db->get_where('master_settings', ['name' => $name])->row_array();

        return $row['value'] ?? null;
    }
}

if (!function_exists('build_google_oauth_state')) {
    /**
     * BooKi (2026-09-19) - Build a signed OAuth state parameter containing tenant host,
     * return route, and CSRF token for multi-tenant central OAuth relay.
     *
     * @param string $csrf_token Random CSRF token to verify upon return.
     * @param string $target_route Controller route to dispatch to (e.g. 'google/oauth_callback').
     *
     * @return string Signed state string formatted as base64url(json).hmac
     */
    function build_google_oauth_state(string $csrf_token, string $target_route = 'google/oauth_callback'): string
    {
        if (function_exists('get_instance')) {
            try {
                if (!is_multi_tenant_mode()) {
                    return $csrf_token;
                }
            } catch (Throwable $e) {
                // If CI DB not bootstrapped (e.g. unit tests), continue building multi-tenant state
            }
        }

        $host = preg_replace('/:\d+$/', '', strtolower((string) ($_SERVER['HTTP_HOST'] ?? '')));

        $payload = [
            'host' => $host,
            'target' => $target_route,
            'csrf' => $csrf_token,
            'ts' => time(),
        ];

        $json = json_encode($payload);
        $b64 = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $key = get_google_oauth_relay_key();
        $sig = hash_hmac('sha256', $b64, $key);

        return $b64 . '.' . $sig;
    }
}

if (!function_exists('get_google_oauth_relay_key')) {
    /**
     * Stable secret key used for signing and verifying OAuth relay state.
     */
    function get_google_oauth_relay_key(): string
    {
        $key = getenv('BOOKI_APP_KEY') ?: getenv('EA_APP_KEY') ?: getenv('TENANT_MASTER_KEY');
        if (!empty($key)) {
            return $key;
        }

        if (function_exists('config_item')) {
            $ci_key = config_item('encryption_key');
            if (!empty($ci_key)) {
                return $ci_key;
            }
        }

        throw new RuntimeException('Encryption key (BOOKI_APP_KEY / encryption_key) is not configured for OAuth relay state.');
    }
}

if (!function_exists('verify_google_oauth_state')) {
    /**
     * BooKi (2026-09-19) - Verify and unpack a signed OAuth state parameter.
     * Returns null if signature is invalid or state has expired (> 15 minutes).
     *
     * @param string $state_param
     *
     * @return array|null Decoded payload array or null if invalid.
     */
    function verify_google_oauth_state(string $state_param): ?array
    {
        if (!str_contains($state_param, '.')) {
            return null;
        }

        $parts = explode('.', $state_param, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$b64, $sig] = $parts;
        $key = get_google_oauth_relay_key();
        $expected_sig = hash_hmac('sha256', $b64, $key);

        if (!hash_equals($expected_sig, $sig)) {
            return null;
        }

        $json = base64_decode(strtr($b64, '-_', '+/'));
        $payload = json_decode($json, true);

        if (!is_array($payload) || empty($payload['ts']) || empty($payload['host'])) {
            return null;
        }

        // 15-minute expiration
        if (abs(time() - (int) $payload['ts']) > 900) {
            return null;
        }

        return $payload;
    }
}

if (!function_exists('get_default_sector_commission_rates')) {
    /**
     * Return default commission rates (%) for all 12 BooKi industry sectors.
     */
    function get_default_sector_commission_rates(): array
    {
        return [
            'barber' => 5.00,
            'beauty_salon' => 7.50,
            'nail_studio' => 6.00,
            'massage_spa' => 8.00,
            'dentist' => 5.00,
            'doctor_clinic' => 5.00,
            'pilates_studio' => 7.00,
            'gym' => 6.00,
            'pt_training' => 8.00,
            'car_wash' => 5.00,
            'restaurant' => 3.50,
            'hotel' => 10.00,
            'default' => 5.00,
        ];
    }
}

if (!function_exists('get_all_sector_commission_rates')) {
    /**
     * Return active commission rates (%) merged with master_settings customizations.
     */
    function get_all_sector_commission_rates(): array
    {
        $defaults = get_default_sector_commission_rates();
        $stored = master_setting('marketplace_sector_commission_rates');
        if (!empty($stored)) {
            $decoded = json_decode($stored, true);
            if (is_array($decoded)) {
                return array_merge($defaults, $decoded);
            }
        }
        return $defaults;
    }
}

if (!function_exists('get_sector_commission_rate')) {
    /**
     * Resolve the commission rate (%) for a specific sector code or business category.
     */
    function get_sector_commission_rate(?string $sector = null): float
    {
        $rates = get_all_sector_commission_rates();
        if ($sector !== null && isset($rates[$sector])) {
            return (float) $rates[$sector];
        }

        if (!empty($sector)) {
            $norm = mb_strtolower(trim($sector), 'UTF-8');
            if (strpos($norm, 'kuafor') !== false || strpos($norm, 'kuaför') !== false || strpos($norm, 'berber') !== false) {
                return (float) ($rates['barber'] ?? 5.0);
            }
            if (strpos($norm, 'guzellik') !== false || strpos($norm, 'güzellik') !== false) {
                return (float) ($rates['beauty_salon'] ?? 7.5);
            }
            if (strpos($norm, 'tirnak') !== false || strpos($norm, 'tırnak') !== false || strpos($norm, 'nail') !== false) {
                return (float) ($rates['nail_studio'] ?? 6.0);
            }
            if (strpos($norm, 'spa') !== false || strpos($norm, 'masaj') !== false) {
                return (float) ($rates['massage_spa'] ?? 8.0);
            }
            if (strpos($norm, 'dis') !== false || strpos($norm, 'diş') !== false || strpos($norm, 'dental') !== false) {
                return (float) ($rates['dentist'] ?? 5.0);
            }
            if (strpos($norm, 'klinik') !== false || strpos($norm, 'doktor') !== false) {
                return (float) ($rates['doctor_clinic'] ?? 5.0);
            }
            if (strpos($norm, 'pilates') !== false || strpos($norm, 'yoga') !== false) {
                return (float) ($rates['pilates_studio'] ?? 7.0);
            }
            if (strpos($norm, 'gym') !== false || strpos($norm, 'fitness') !== false || strpos($norm, 'spor') !== false) {
                return (float) ($rates['gym'] ?? 6.0);
            }
            if (strpos($norm, 'pt') !== false || strpos($norm, 'antrenor') !== false || strpos($norm, 'trainer') !== false) {
                return (float) ($rates['pt_training'] ?? 8.0);
            }
            if (strpos($norm, 'oto') !== false || strpos($norm, 'yikama') !== false || strpos($norm, 'yıkama') !== false) {
                return (float) ($rates['car_wash'] ?? 5.0);
            }
            if (strpos($norm, 'restoran') !== false || strpos($norm, 'cafe') !== false || strpos($norm, 'kafe') !== false) {
                return (float) ($rates['restaurant'] ?? 3.5);
            }
            if (strpos($norm, 'otel') !== false || strpos($norm, 'hotel') !== false) {
                return (float) ($rates['hotel'] ?? 10.0);
            }
        }

        $general = master_setting('marketplace_commission_rate');
        if (!empty($general)) {
            return (float) $general;
        }

        return (float) ($rates['default'] ?? 5.0);
    }
}

if (!function_exists('randevuburada_url')) {
    /**
     * Get absolute or relative URL for the RandevuBurada marketplace portal.
     * Respects RANDEVUBURADA_DOMAIN environment variable, defaults to randevuburada.kibusiness.co.
     */
    function randevuburada_url(string $path = ''): string
    {
        $domain = getenv('RANDEVUBURADA_DOMAIN') ?: 'randevuburada.kibusiness.co';
        $path = ltrim($path, '/');
        return 'https://' . $domain . ($path !== '' ? '/' . $path : '');
    }
}

if (!function_exists('booki_site_url')) {
    /**
     * Get absolute URL for the BooKi main marketing and SaaS website.
     * Respects BOOKI_DOMAIN / MARKETPLACE_DOMAIN environment variables, defaults to booki.kibusiness.co.
     */
    function booki_site_url(string $path = ''): string
    {
        $domain = getenv('BOOKI_DOMAIN') ?: (getenv('MARKETPLACE_DOMAIN') ?: 'booki.kibusiness.co');
        $path = ltrim($path, '/');
        return 'https://' . $domain . ($path !== '' ? '/' . $path : '');
    }
}

if (!function_exists('tr_slug')) {
    /**
     * Simple Turkish-safe slug generator.
     */
    function tr_slug(string $text): string
    {
        $tr_map = [
            'ş' => 's', 'Ş' => 's', 'ç' => 'c', 'Ç' => 'c',
            'ğ' => 'g', 'Ğ' => 'g', 'ü' => 'u', 'Ü' => 'u',
            'ö' => 'o', 'Ö' => 'o', 'ı' => 'i', 'İ' => 'i',
            'â' => 'a', 'Â' => 'a', 'î' => 'i', 'Î' => 'i',
            'û' => 'u', 'Û' => 'u',
        ];
        $slug = strtr($text, $tr_map);
        $slug = mb_strtolower($slug, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }
}

if (!function_exists('generate_lead_slug')) {
    /**
     * Generate an SEO-friendly slug from business name, district, and city.
     * Turkish characters are transliterated and uniqueness is ensured via DB check.
     */
    function generate_lead_slug(string $name, string $district = '', string $city = ''): string
    {
        $parts = array_filter([$name, $district, $city], fn($p) => trim($p) !== '');
        $raw = implode(' ', $parts);

        $slug = tr_slug($raw);

        if ($slug === '') {
            $slug = 'isletme-' . bin2hex(random_bytes(4));
        }

        $CI =& get_instance();
        if (isset($CI->db) && $CI->db->table_exists('leads')) {
            $base_slug = $slug;
            $counter = 1;
            while ($CI->db->where('slug', $slug)->count_all_results('leads') > 0) {
                $counter++;
                $slug = $base_slug . '-' . $counter;
            }
        }

        return $slug;
    }
}

