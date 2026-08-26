<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - multi-tenant SaaS support (2026-08-26).
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
