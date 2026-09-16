<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Paket/Plan feature gating (Dalga 4, 2026-09-12).
 *
 * Four tiers: Free, Basic, Premium, Elite (Elite = every feature, no exceptions).
 * The tenant's tier is still just the existing free-text `tenants.plan` column
 * (schema unchanged) - normalized to one of these 4 strings by the superadmin
 * tenant edit form's dropdown (see superadmin_tenants.php) instead of arbitrary
 * text like the pre-existing "Premium LifeTime" value. A tenant whose plan
 * doesn't match any known tier (old free-text values, or null) is treated as
 * Free - the safest default (a lapsed/unrecognized plan never silently grants
 * premium access).
 *
 * Feature keys are mostly the existing PRIV_* constant VALUES (so gating a
 * controller is one `require_plan_feature(PRIV_MARKETING)` line, reusing a
 * name that's already meaningful in this codebase) plus a handful of extra
 * keys for things with no PRIV_* of their own (WhatsApp unofficial mode,
 * Google Calendar sync, accounting export, AI Asistan, future Google Contacts
 * sync).
 *
 * This is a PRODUCT decision, not just an engineering one - the exact
 * Free/Basic/Premium boundaries below were drafted by Claude per the user's
 * explicit "ayarlarsın" (you decide) instruction and should be treated as a
 * first draft to adjust from the superadmin panel's config, not a fixed spec.
 * ---------------------------------------------------------------------------- */

if (!function_exists('plan_feature_matrix')) {
    /**
     * @return array<string, string[]> tier name => feature keys included in that tier (cumulative -
     *   each tier's array is only what it ADDS beyond the previous one; plan_allows() flattens this).
     */
    function plan_feature_matrix(): array
    {
        return [
            'Free' => [
                PRIV_APPOINTMENTS,
                PRIV_CUSTOMERS,
                PRIV_SERVICES,
                PRIV_BLOCKED_PERIODS,
                PRIV_STATIONS,
                PRIV_USER_SETTINGS,
                PRIV_SYSTEM_SETTINGS,
            ],
            'Basic' => [
                PRIV_REPORTS,
                PRIV_WAITLIST,
                PRIV_WEBHOOKS,
                PRIV_PRODUCTS,
            ],
            'Premium' => [
                PRIV_MARKETING,
                PRIV_INVOICES,
                PRIV_POS,
                PRIV_REVIEWS,
                PRIV_MEMBERSHIPS,
                PRIV_PACKAGES,
                PRIV_BRANCHES,
                'whatsapp_unofficial',
                'google_calendar',
                'accounting_export',
            ],
            'Elite' => [
                PRIV_AI_AGENT,
                'google_contacts_sync',
            ],
        ];
    }

    /**
     * Ordered tier names, lowest to highest - each tier includes everything the ones before it have.
     */
    function plan_tier_order(): array
    {
        return ['Free', 'Basic', 'Premium', 'Elite'];
    }
}

if (!function_exists('plan_allows')) {
    /**
     * Whether the CURRENT tenant's plan (tenant_context()['plan']) includes the given feature key.
     * Standalone/self-hosted deployments (no tenant_context() at all, is_multi_tenant_mode() false)
     * always return true - plan tiers are a SaaS-only concept, a self-hosted install already paid for
     * (or built) the whole thing.
     *
     * @param string $feature A PRIV_* constant value, or one of the extra keys in plan_feature_matrix().
     */
    function plan_allows(string $feature): bool
    {
        if (!is_multi_tenant_mode()) {
            return true;
        }

        $tenant = tenant_context();
        $plan = $tenant['plan'] ?? null;

        $tier_order = plan_tier_order();
        $tier_index = in_array($plan, $tier_order, true) ? array_search($plan, $tier_order, true) : 0; // unknown/null -> Free

        $matrix = plan_feature_matrix();

        for ($i = 0; $i <= $tier_index; $i++) {
            if (in_array($feature, $matrix[$tier_order[$i]], true)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('require_plan_feature')) {
    /**
     * Controller-side gate: call at the top of a controller's constructor (or a specific action) for
     * a feature that belongs to a paid tier. Mirrors abort(403, ...) used by cannot()-based permission
     * checks elsewhere, so it behaves the same way in the browser (Bootstrap 403 page) and API clients
     * (JSON via json_exception() if the caller is inside a try/catch that expects one).
     */
    function require_plan_feature(string $feature): void
    {
        if (!plan_allows($feature)) {
            abort(402, 'Bu özellik mevcut paketinizde yok. Yükseltmek için bizimle iletişime geçin.');
        }
    }
}
