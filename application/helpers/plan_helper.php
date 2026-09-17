<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('plan_feature_matrix')) {
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
                'google_calendar',
                'pwa',
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
                'accounting_export',
            ],
            'Elite' => [
                PRIV_AI_AGENT,
                'white_label',
                'custom_domain',
                'api_priority',
                'google_contacts_sync',
            ],
        ];
    }

    function plan_tier_order(): array
    {
        return ['Free', 'Basic', 'Premium', 'Elite'];
    }
}

if (!function_exists('plan_allows')) {
    function plan_allows(string $feature): bool
    {
        if (!is_multi_tenant_mode()) {
            return true;
        }

        $tenant = tenant_context();
        $plan = $tenant['plan'] ?? null;

        $tier_order = plan_tier_order();
        $tier_index = in_array($plan, $tier_order, true) ? array_search($plan, $tier_order, true) : 0;

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
    function require_plan_feature(string $feature): void
    {
        if (!plan_allows($feature)) {
            abort(402, 'Bu özellik mevcut paketinizde yok. Yükseltmek için bizimle iletişime geçin.');
        }
    }
}
