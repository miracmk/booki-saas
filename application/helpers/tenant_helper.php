<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - multi-tenant SaaS support (2026-08-26).
 *
 * The SAME codebase serves two deployment shapes:
 *
 * 1. Single-tenant / standalone (e.g. Salon Flora's own production deployment) - the 'default' DB
 *    connection group IS the tenant's own database, exactly as before multi-tenancy was added.
 * 2. Multi-tenant cloud SaaS - the 'default' connection is a lightweight MASTER database holding only
 *    the `tenants` catalog; EA_Controller::resolve_tenant() (called from the constructor every
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
        $CI = &get_instance();

        return $CI->db->table_exists('tenants');
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
     * EA_Controller::resolve_tenant()) via a throwaway, non-active connection object
     * ($this->load->database('default', true) - the TRUE "return, don't replace $this->db" form), so
     * calling this never disturbs whichever DB the current request is actually working against.
     * Single-tenant/standalone deployments have no `master_settings` table at all - always returns
     * null there rather than erroring.
     *
     * @param string $name
     * @param string|null $value Pass to set; omit to just read.
     *
     * @return string|null
     */
    function master_setting(string $name, ?string $value = '__unset__'): ?string
    {
        $CI = &get_instance();

        $master_db = $CI->load->database('default', true);

        if (!$master_db->table_exists('master_settings')) {
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
