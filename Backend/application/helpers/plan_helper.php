<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Sektörel Fiyatlandırma, Paket Baremleri & Özellik Yetkilendirme Helper'ı
 *
 * 9 Ana Sektör, 168 Alt İşletme Tipi ve 5 Paket Kademesi:
 * - Ücretsiz (Free): 0 ₺ - Tüm özellikler açık, AI Asistan YOK. Sektörel başlangıç baremleri.
 * - Başlangıç (Basic): Aylık 1.250 ₺ / Yıllık peşin 1.000 ₺/ay - Tüm özellikler + AI Asistan AÇIK.
 * - Orta (Pro): Aylık 2.450 ₺ / Yıllık peşin 1.950 ₺/ay - Tüm özellikler + AI Asistan AÇIK.
 * - Premium: Aylık 4.750 ₺ / Yıllık peşin 3.800 ₺/ay - Tüm özellikler + AI Asistan AÇIK.
 * - Özel (Custom / Elite): Özel Teklif - Tüm özellikler + AI Asistan AÇIK + Sınırsız Barem.
 * ---------------------------------------------------------------------------- */

if (!function_exists('plan_tier_order')) {
    /**
     * Canonical tier progression order.
     */
    function plan_tier_order(): array
    {
        return ['Free', 'Basic', 'Pro', 'Premium', 'Custom'];
    }
}

if (!function_exists('normalize_plan_name')) {
    /**
     * Normalize plan name / slug to canonical format ('Free', 'Basic', 'Pro', 'Premium', 'Custom').
     */
    function normalize_plan_name(?string $plan): string
    {
        if (empty($plan)) {
            return 'Free';
        }

        $p = mb_strtolower(trim($plan), 'UTF-8');

        return match ($p) {
            'free', 'ücretsiz', 'ucretsiz' => 'Free',
            'basic', 'başlangıç', 'baslangic', 'starter' => 'Basic',
            'pro', 'orta', 'medium', 'professional' => 'Pro',
            'premium' => 'Premium',
            'custom', 'özel', 'ozel', 'elite', 'enterprise' => 'Custom',
            default => 'Free',
        };
    }
}

if (!function_exists('plan_pricing_definitions')) {
    /**
     * Get plan pricing information for all 5 tiers.
     */
    function plan_pricing_definitions(): array
    {
        static $pricing = null;

        if ($pricing === null) {
            $config_path = (defined('APPPATH') ? APPPATH : dirname(__DIR__) . '/') . 'config/sector_pricing_matrix.php';
            if (file_exists($config_path)) {
                require $config_path;
                if (isset($config['plan_pricing'])) {
                    $pricing = $config['plan_pricing'];
                }
            }

            if ($pricing === null) {
                $pricing = [
                    'Free' => [
                        'name' => 'Ücretsiz',
                        'slug' => 'free',
                        'monthly_price' => 0,
                        'annual_monthly_price' => 0,
                        'annual_total_price' => 0,
                        'currency' => '₺',
                        'billing_period' => 'ömür boyu',
                        'has_ai' => false,
                        'badge' => 'BAŞLANGIÇ',
                        'description' => 'Bireysel çalışanlar ve tek kişilik işletmeler için başlangıç seviyesi.',
                    ],
                    'Basic' => [
                        'name' => 'Başlangıç',
                        'slug' => 'basic',
                        'monthly_price' => 1250,
                        'annual_monthly_price' => 1000,
                        'annual_total_price' => 12000,
                        'currency' => '₺',
                        'billing_period' => 'ay',
                        'has_ai' => true,
                        'badge' => 'TEMEL İŞLETME',
                        'description' => 'Büyüyen işletmeler ve operasyonunu hızlandırmak isteyenler için.',
                    ],
                    'Pro' => [
                        'name' => 'Orta',
                        'slug' => 'pro',
                        'monthly_price' => 2450,
                        'annual_monthly_price' => 1950,
                        'annual_total_price' => 23400,
                        'currency' => '₺',
                        'billing_period' => 'ay',
                        'has_ai' => true,
                        'badge' => 'BÜYÜYEN İŞLETME',
                        'description' => 'Yoğun randevu trafiği olan, ekip çalışması ve büyüme odaklı işletmeler.',
                    ],
                    'Premium' => [
                        'name' => 'Premium',
                        'slug' => 'premium',
                        'monthly_price' => 4750,
                        'annual_monthly_price' => 3800,
                        'annual_total_price' => 45600,
                        'currency' => '₺',
                        'billing_period' => 'ay',
                        'has_ai' => true,
                        'badge' => 'EN POPÜLER',
                        'description' => 'Yüksek hacimli işletmeler, çoklu şube ve geniş personel kadroları için.',
                    ],
                    'Custom' => [
                        'name' => 'Özel',
                        'slug' => 'custom',
                        'monthly_price' => null,
                        'annual_monthly_price' => null,
                        'annual_total_price' => null,
                        'currency' => '₺',
                        'billing_period' => 'özel teklif',
                        'has_ai' => true,
                        'badge' => 'KURUMSAL & ÖZEL',
                        'description' => 'Sınırsız kaynak, özel entegrasyonlar, özel SLA ve kurumsal destek.',
                    ],
                ];
            }
        }

        return $pricing;
    }
}

if (!function_exists('plan_feature_matrix')) {
    /**
     * Feature entitlement matrix.
     * All features are open across all tiers, with AI Agent available on Basic, Pro, Premium, Custom.
     */
    function plan_feature_matrix(): array
    {
        $all_standard_features = [
            PRIV_APPOINTMENTS,
            PRIV_CUSTOMERS,
            PRIV_SERVICES,
            PRIV_BLOCKED_PERIODS,
            PRIV_STATIONS,
            PRIV_USER_SETTINGS,
            PRIV_SYSTEM_SETTINGS,
            PRIV_REPORTS,
            PRIV_WAITLIST,
            PRIV_WEBHOOKS,
            PRIV_PRODUCTS,
            PRIV_MARKETING,
            PRIV_INVOICES,
            PRIV_POS,
            PRIV_REVIEWS,
            PRIV_MEMBERSHIPS,
            PRIV_PACKAGES,
            PRIV_BRANCHES,
            'google_calendar',
            'pwa',
            'whatsapp_unofficial',
            'accounting_export',
            'white_label',
            'custom_domain',
            'api_priority',
            'google_contacts_sync',
        ];

        return [
            'Free' => $all_standard_features,
            'Basic' => array_merge($all_standard_features, [PRIV_AI_AGENT, 'ai_assistant']),
            'Pro' => array_merge($all_standard_features, [PRIV_AI_AGENT, 'ai_assistant']),
            'Premium' => array_merge($all_standard_features, [PRIV_AI_AGENT, 'ai_assistant']),
            'Custom' => array_merge($all_standard_features, [PRIV_AI_AGENT, 'ai_assistant']),
            // Legacy aliases for backward compatibility
            'Elite' => array_merge($all_standard_features, [PRIV_AI_AGENT, 'ai_assistant']),
        ];
    }
}

if (!function_exists('plan_allows')) {
    /**
     * Check if a feature is allowed for the active tenant's plan.
     * Rule: All features are open across all plans, with AI Assistant as the ONLY exception (unavailable on Free).
     *
     * @param string $feature Feature slug or constant (e.g. PRIV_AI_AGENT, 'white_label', etc.)
     * @return bool
     */
    function plan_allows(string $feature): bool
    {
        if (!is_multi_tenant_mode()) {
            return true;
        }

        $tenant = tenant_context();
        $raw_plan = $tenant['plan'] ?? null;
        $canonical_plan = normalize_plan_name($raw_plan);

        // AI Assistant is the ONLY exception: disabled on Free plan, enabled on all paid plans!
        $is_ai_feature = in_array(strtolower($feature), [
            'ai_agent',
            'ai_assistant',
            'ai',
            defined('PRIV_AI_AGENT') ? PRIV_AI_AGENT : 'ai_agent',
        ], true);

        if ($is_ai_feature) {
            return $canonical_plan !== 'Free';
        }

        // All other features are completely open across all plans!
        return true;
    }
}

if (!function_exists('require_plan_feature')) {
    /**
     * Enforce that a feature is permitted by the tenant plan or abort with HTTP 402.
     */
    function require_plan_feature(string $feature): void
    {
        if (!plan_allows($feature)) {
            abort(402, 'Bu özellik (Yapay Zeka Asistanı) Ücretsiz paketinizde bulunmamaktadır. Kullanmak için Başlangıç veya üstü bir pakete geçiş yapın.');
        }
    }
}

/* ----------------------------------------------------------------------------
 * Sektörel Barem ve Baraj (Quota / Threshold) Fonksiyonları
 * ---------------------------------------------------------------------------- */

if (!function_exists('get_sector_pricing_matrix')) {
    /**
     * Load the comprehensive matrix of 168 sub-business types and their plan thresholds.
     */
    function get_sector_pricing_matrix(): array
    {
        static $matrix = null;

        if ($matrix === null) {
            $config_path = (defined('APPPATH') ? APPPATH : dirname(__DIR__) . '/') . 'config/sector_pricing_matrix.php';
            if (file_exists($config_path)) {
                require $config_path;
                if (isset($config['sector_pricing_matrix'])) {
                    $matrix = $config['sector_pricing_matrix'];
                }
            }
            if ($matrix === null) {
                $matrix = [];
            }
        }

        return $matrix;
    }
}

if (!function_exists('get_main_sectors')) {
    /**
     * Get list of all 9 main industry categories.
     */
    function get_main_sectors(): array
    {
        static $sectors = null;

        if ($sectors === null) {
            $config_path = (defined('APPPATH') ? APPPATH : dirname(__DIR__) . '/') . 'config/sector_pricing_matrix.php';
            if (file_exists($config_path)) {
                require $config_path;
                if (isset($config['main_sectors'])) {
                    $sectors = $config['main_sectors'];
                }
            }
            if ($sectors === null) {
                $sectors = [
                    'Güzellik / Kişisel Bakım' => ['name' => 'Güzellik / Kişisel Bakım', 'vertical_group' => 'beauty', 'icon' => '💅'],
                    'Restoran / Yeme-İçme' => ['name' => 'Restoran / Yeme-İçme', 'vertical_group' => 'restaurant', 'icon' => '🍽️'],
                    'Spor / Fitness' => ['name' => 'Spor / Fitness', 'vertical_group' => 'sports', 'icon' => '🏋️'],
                    'Sağlık / Uzmanlık' => ['name' => 'Sağlık / Uzmanlık', 'vertical_group' => 'health', 'icon' => '🩺'],
                    'Otomotiv' => ['name' => 'Otomotiv', 'vertical_group' => 'automotive', 'icon' => '🚗'],
                    'Deneyim / Eğlence' => ['name' => 'Deneyim / Eğlence', 'vertical_group' => 'experience', 'icon' => '🎯'],
                    'Konaklama' => ['name' => 'Konaklama', 'vertical_group' => 'hospitality', 'icon' => '🏨'],
                    'Eğitim / Kurs' => ['name' => 'Eğitim / Kurs', 'vertical_group' => 'education', 'icon' => '📚'],
                    'Profesyonel Hizmet' => ['name' => 'Profesyonel Hizmet', 'vertical_group' => 'professional', 'icon' => '💼'],
                ];
            }
        }

        return $sectors;
    }
}

if (!function_exists('get_sub_business_types')) {
    /**
     * Get sub-business types, optionally filtered by main sector name.
     */
    function get_sub_business_types(?string $main_sector = null): array
    {
        $matrix = get_sector_pricing_matrix();
        $results = [];

        foreach ($matrix as $key => $item) {
            if ($main_sector !== null && $item['main_sector'] !== $main_sector) {
                continue;
            }
            $results[$item['name']] = $item;
        }

        return $results;
    }
}

if (!function_exists('find_business_barem')) {
    /**
     * Find the barem definition for a business type name or slug.
     */
    function find_business_barem(?string $business_type): ?array
    {
        if (empty($business_type)) {
            return null;
        }

        $matrix = get_sector_pricing_matrix();

        // 1. Exact match by name
        if (isset($matrix[$business_type])) {
            return $matrix[$business_type];
        }

        // 2. Match by slug or normalized string
        $search = mb_strtolower(trim($business_type), 'UTF-8');
        foreach ($matrix as $name => $data) {
            if (mb_strtolower($name, 'UTF-8') === $search || ($data['slug'] ?? '') === $search) {
                return $data;
            }
        }

        // 3. Partial / contains match
        foreach ($matrix as $name => $data) {
            $norm = mb_strtolower($name, 'UTF-8');
            if (str_contains($norm, $search) || str_contains($search, $norm)) {
                return $data;
            }
        }

        return null;
    }
}

if (!function_exists('current_tenant_barem')) {
    /**
     * Get the active tenant's barem/quotas for their specific business type and plan.
     *
     * @param string|null $plan Optional plan override. If null, current tenant plan is used.
     * @return array
     */
    function current_tenant_barem(?string $plan = null): array
    {
        $tenant = tenant_context();
        $raw_plan = $plan ?: ($tenant['plan'] ?? 'Free');
        $canonical_plan = normalize_plan_name($raw_plan);

        // Resolve active business type from tenant settings
        $business_type = function_exists('setting') ? (setting('business_type') ?: setting('industry_code')) : null;
        if (empty($business_type) && !empty($tenant['category'])) {
            $business_type = $tenant['category'];
        }

        $barem = find_business_barem($business_type);

        // Fallback default barem if business type is unconfigured (defaults to Kuaför / general service)
        if (!$barem) {
            $barem = find_business_barem('Kuaför') ?: [
                'name' => 'Standart İşletme',
                'slug' => 'standart-isletme',
                'main_sector' => 'Genel',
                'vertical_group' => 'general',
                'metric_types' => ['resource' => 'İstasyon', 'staff' => 'Personel', 'booking' => 'Randevu'],
                'tiers' => [
                    'Free' => ['resource_limit' => 2, 'staff_limit' => 1, 'appointment_limit' => 40, 'resource_label' => '1-2 İstasyon', 'staff_label' => '1 Personel', 'appointment_label' => '40 Randevu', 'raw' => '1-2 İstasyon / 1 Personel / 40 Randevu'],
                    'Basic' => ['resource_limit' => 5, 'staff_limit' => 5, 'appointment_limit' => 250, 'resource_label' => '3-5 İstasyon', 'staff_label' => '3-5 Personel', 'appointment_label' => '250 Randevu', 'raw' => '3-5 İstasyon / 3-5 Personel / 250 Randevu'],
                    'Pro' => ['resource_limit' => 10, 'staff_limit' => 10, 'appointment_limit' => 750, 'resource_label' => '6-10 İstasyon', 'staff_label' => '6-10 Personel', 'appointment_label' => '750 Randevu', 'raw' => '6-10 İstasyon / 6-10 Personel / 750 Randevu'],
                    'Premium' => ['resource_limit' => 20, 'staff_limit' => 20, 'appointment_limit' => 2000, 'resource_label' => '11-20 İstasyon', 'staff_label' => '11-20 Personel', 'appointment_label' => '2.000 Randevu', 'raw' => '11-20 İstasyon / 11-20 Personel / 2.000 Randevu'],
                    'Custom' => ['resource_limit' => null, 'staff_limit' => null, 'appointment_limit' => null, 'resource_label' => '21+ İstasyon', 'staff_label' => '21+ Personel', 'appointment_label' => 'Sınırsız Randevu', 'raw' => '21+ İstasyon / 21+ Personel / Sınırsız Randevu'],
                ],
            ];
        }

        $tier_info = $barem['tiers'][$canonical_plan] ?? ($barem['tiers']['Free'] ?? []);

        return [
            'plan' => $canonical_plan,
            'business_type' => $barem['name'],
            'main_sector' => $barem['main_sector'],
            'vertical_group' => $barem['vertical_group'],
            'metric_types' => $barem['metric_types'],
            'resource_limit' => $tier_info['resource_limit'] ?? null,
            'staff_limit' => $tier_info['staff_limit'] ?? null,
            'appointment_limit' => $tier_info['appointment_limit'] ?? null,
            'resource_label' => $tier_info['resource_label'] ?? '',
            'staff_label' => $tier_info['staff_label'] ?? '',
            'appointment_label' => $tier_info['appointment_label'] ?? '',
            'raw_quota' => $tier_info['raw'] ?? '',
        ];
    }
}

if (!function_exists('check_tenant_quota')) {
    /**
     * Check if the tenant is allowed to add more resources, staff, or appointments.
     *
     * @param string $metric_type 'resource' (or 'station', 'table'), 'staff' (or 'provider'), 'appointment' (or 'booking')
     * @param int $attempted_count Proposed total or increment
     * @return array ['allowed' => bool, 'current' => int, 'limit' => ?int, 'message' => string]
     */
    function check_tenant_quota(string $metric_type, int $attempted_count = 1): array
    {
        if (!is_multi_tenant_mode()) {
            return ['allowed' => true, 'current' => 0, 'limit' => null, 'message' => ''];
        }

        $barem = current_tenant_barem();
        $metric = mb_strtolower($metric_type, 'UTF-8');

        $CI = &get_instance();

        // 1. Staff / Provider limit
        if (in_array($metric, ['staff', 'provider', 'user', 'egitmen', 'uzman', 'personel'], true)) {
            $limit = $barem['staff_limit'];
            if ($limit === null) {
                return ['allowed' => true, 'current' => 0, 'limit' => null, 'message' => ''];
            }

            $current = 0;
            if (isset($CI->db) && $CI->db->table_exists('users')) {
                // Count active providers/staff
                $role_id = 1; // default provider role
                if ($CI->db->table_exists('roles')) {
                    $r = $CI->db->get_where('roles', ['slug' => 'provider'])->row_array();
                    if ($r) $role_id = (int) $r['id'];
                }
                $current = (int) $CI->db->where('id_roles', $role_id)->count_all_results('users');
            }

            $allowed = ($current + $attempted_count) <= $limit;
            $staff_name = $barem['metric_types']['staff'] ?? 'Personel';
            $msg = $allowed ? '' : "{$barem['business_type']} işletmesi için {$barem['plan']} paketinde en fazla {$limit} {$staff_name} tanımlanabilir (Mevcut: {$current}). Lütfen paketinizi yükseltin.";

            return [
                'allowed' => $allowed,
                'current' => $current,
                'limit' => $limit,
                'message' => $msg,
            ];
        }

        // 2. Resource / Station / Table limit
        if (in_array($metric, ['resource', 'station', 'table', 'koltuk', 'kabin', 'masa', 'peron', 'oda', 'istasyon'], true)) {
            $limit = $barem['resource_limit'];
            if ($limit === null) {
                return ['allowed' => true, 'current' => 0, 'limit' => null, 'message' => ''];
            }

            $current = 0;
            if (isset($CI->db)) {
                if ($barem['vertical_group'] === 'restaurant' && $CI->db->table_exists('restaurant_tables')) {
                    $current = (int) $CI->db->count_all_results('restaurant_tables');
                } elseif ($CI->db->table_exists('stations')) {
                    $current = (int) $CI->db->count_all_results('stations');
                }
            }

            $allowed = ($current + $attempted_count) <= $limit;
            $res_name = $barem['metric_types']['resource'] ?? 'İstasyon';
            $msg = $allowed ? '' : "{$barem['business_type']} işletmesi için {$barem['plan']} paketinde en fazla {$limit} {$res_name} tanımlanabilir (Mevcut: {$current}). Lütfen paketinizi yükseltin.";

            return [
                'allowed' => $allowed,
                'current' => $current,
                'limit' => $limit,
                'message' => $msg,
            ];
        }

        // 3. Monthly Appointment / Booking limit
        if (in_array($metric, ['appointment', 'booking', 'reservation', 'seans', 'randevu', 'gorusme', 'geceleme', 'arac'], true)) {
            $limit = $barem['appointment_limit'];
            if ($limit === null) {
                return ['allowed' => true, 'current' => 0, 'limit' => null, 'message' => ''];
            }

            $start_of_month = date('Y-m-01 00:00:00');
            $end_of_month = date('Y-m-t 23:59:59');
            $current = 0;

            if (isset($CI->db) && $CI->db->table_exists('appointments')) {
                $current = (int) $CI->db
                    ->where('start_datetime >=', $start_of_month)
                    ->where('start_datetime <=', $end_of_month)
                    ->count_all_results('appointments');
            }

            $allowed = ($current + $attempted_count) <= $limit;
            $book_name = $barem['metric_types']['booking'] ?? 'Randevu';
            $msg = $allowed ? '' : "{$barem['business_type']} işletmesi için {$barem['plan']} paketinde aylık en fazla {$limit} {$book_name} alınabilir (Bu ayki: {$current}). Lütfen paketinizi yükseltin.";

            return [
                'allowed' => $allowed,
                'current' => $current,
                'limit' => $limit,
                'message' => $msg,
            ];
        }

        return ['allowed' => true, 'current' => 0, 'limit' => null, 'message' => ''];
    }
}

if (!function_exists('require_tenant_quota')) {
    /**
     * Enforce tenant barem/quota. Throws InvalidArgumentException or aborts if quota is exceeded.
     *
     * @param string $metric_type 'resource', 'staff', or 'appointment'
     * @param int $attempted_count
     */
    function require_tenant_quota(string $metric_type, int $attempted_count = 1): void
    {
        $res = check_tenant_quota($metric_type, $attempted_count);
        if (!$res['allowed']) {
            if (function_exists('abort')) {
                abort(402, $res['message']);
            } else {
                throw new InvalidArgumentException($res['message']);
            }
        }
    }
}

/* ----------------------------------------------------------------------------
 * White Label Helper Functions
 * ---------------------------------------------------------------------------- */

if (!function_exists('is_white_label_active')) {
    /**
     * Check if white-label features are active for the current tenant.
     * True if single-tenant mode, or if tenant's plan allows white_label and white_label_enabled is 1.
     */
    function is_white_label_active(): bool
    {
        if (!is_multi_tenant_mode()) {
            return true;
        }

        return plan_allows('white_label') && (int) setting('white_label_enabled') === 1;
    }
}

if (!function_exists('white_label_logo')) {
    /**
     * Returns the white-label custom company logo if white-label is active
     * (plan allows it, setting is enabled, and a custom logo is uploaded).
     * Otherwise returns BooKi's platform logo.
     */
    function white_label_logo(): string
    {
        if (is_white_label_active()) {
            $custom_logo = setting('company_logo');
            if (!empty($custom_logo)) {
                return $custom_logo;
            }
        }

        return base_url('assets/img/logo.png');
    }
}
