<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Industry Blueprint, Dynamic Terminology & Modular Configuration Helper
 *
 * Provides real-time helpers for resolving the active tenant/business industry,
 * dynamically translating UI terms (customer, provider, service, station, appointment),
 * evaluating feature/module visibility flags, and tailoring dashboard configurations.
 * ---------------------------------------------------------------------------- */

if (!function_exists('current_industry_code')) {
    /**
     * Get the active industry code for the current tenant.
     *
     * @return string
     */
    function current_industry_code(): string
    {
        $code = setting('industry_code');
        return !empty($code) ? (string) $code : 'beauty_salon';
    }
}

if (!function_exists('current_vertical_group')) {
    /**
     * Get the high-level vertical category for the active tenant.
     * Returns: 'beauty', 'restaurant', 'sports', 'health', 'automotive', 'experience', 'hospitality', 'education', or 'professional'.
     */
    function current_vertical_group(?string $code = null): string
    {
        $code = $code ?: current_industry_code();

        // 1. Check sector pricing matrix if available
        if (function_exists('find_business_barem')) {
            $barem = find_business_barem($code);
            if ($barem && !empty($barem['vertical_group'])) {
                return $barem['vertical_group'];
            }
        }

        $map = [
            'beauty_salon' => 'beauty',
            'barber' => 'beauty',
            'nail_studio' => 'beauty',
            'massage_spa' => 'beauty',

            'restaurant' => 'restaurant',

            'gym' => 'sports',
            'pt_training' => 'sports',
            'pilates_studio' => 'sports',
            'sports_court' => 'sports',

            'doctor_clinic' => 'health',
            'dentist' => 'health',
            'psychology_dietitian_clinic' => 'health',

            'car_wash' => 'automotive',
            'auto_service_detailing' => 'automotive',

            'hotel' => 'hospitality',
            'experience_escape_room' => 'experience',

            'education' => 'education',
            'professional' => 'professional',
            'law_firm' => 'legal',
            'consulting_agency' => 'consulting',
        ];

        return $map[$code] ?? 'beauty';
    }
}

if (!function_exists('all_main_sectors')) {
    /**
     * Get list of all 9 main industry categories.
     */
    function all_main_sectors(): array
    {
        return function_exists('get_main_sectors') ? get_main_sectors() : [];
    }
}

if (!function_exists('all_sub_business_types')) {
    /**
     * Get list of all 168 sub-business types.
     */
    function all_sub_business_types(?string $main_sector = null): array
    {
        return function_exists('get_sub_business_types') ? get_sub_business_types($main_sector) : [];
    }
}

if (!function_exists('is_vertical')) {
    function is_vertical(string $vertical): bool
    {
        return current_vertical_group() === $vertical;
    }
}

if (!function_exists('current_industry_blueprint')) {
    /**
     * Get the blueprint definition for the active or given industry.
     *
     * @param string|null $code
     * @return array|null
     */
    function current_industry_blueprint(?string $code = null): ?array
    {
        static $cached_blueprints = [];

        $code = $code ?: current_industry_code();

        if (isset($cached_blueprints[$code])) {
            return $cached_blueprints[$code];
        }

        $base_path = defined('APPPATH') ? APPPATH : (dirname(__DIR__) . '/');
        $filepath = $base_path . 'seeders/blueprints/' . $code . '.json';
        if (!file_exists($filepath)) {
            // Fallback to beauty_salon if specific industry blueprint does not exist
            $filepath = $base_path . 'seeders/blueprints/beauty_salon.json';
            if (!file_exists($filepath)) {
                return null;
            }
        }

        $json = file_get_contents($filepath);
        $data = json_decode($json, true);

        $cached_blueprints[$code] = is_array($data) ? $data : null;
        return $cached_blueprints[$code];
    }
}

if (!function_exists('current_industry_info')) {
    /**
     * Get high-level metadata (name, icon, description, service_type) for the active industry.
     *
     * @return array
     */
    function current_industry_info(): array
    {
        $bp = current_industry_blueprint();
        $code = current_industry_code();

        $defaults = [
            'code' => $code,
            'name' => 'Güzellik & Kişisel Bakım',
            'icon' => '✨',
            'service_type' => 'duration',
            'description' => 'Rezervasyon ve işletme yönetim sistemi.',
        ];

        if ($bp && isset($bp['industry'])) {
            return [
                'code' => $bp['industry']['code'] ?? $code,
                'name' => $bp['industry']['name'] ?? $defaults['name'],
                'icon' => $bp['industry']['icon'] ?? $defaults['icon'],
                'service_type' => $bp['industry']['service_type'] ?? $defaults['service_type'],
                'description' => $bp['industry']['description'] ?? $defaults['description'],
            ];
        }

        return $defaults;
    }
}

if (!function_exists('industry_term')) {
    /**
     * Return the industry-specific localized terminology label.
     *
     * Supported keys:
     * - customer_label     (e.g., Müşteri, Hasta, Misafir, Üye, Danışan, Araç Sahibi)
     * - provider_label     (e.g., Personel, Hekim, Garson / Servis, Antrenör, Berber)
     * - service_label      (e.g., Hizmet, Tedavi / İşlem, Menü & Rezervasyon, Ders / Seans)
     * - station_label      (e.g., İstasyon / Oda, Diş Üniti, Masa / Bölüm, Stüdyo, Peron, Koltuk)
     * - appointment_label  (e.g., Randevu, Muayene / Tedavi, Masa Rezervasyonu, Ders / Seans)
     *
     * @param string $key
     * @param string|null $fallback
     * @return string
     */
    function industry_term(string $key, ?string $fallback = null): string
    {
        static $custom_terms = null;

        if ($custom_terms === null) {
            $raw = setting('industry_custom_terminology');
            if (!empty($raw) && is_string($raw)) {
                $custom_terms = json_decode($raw, true);
            } elseif (is_array($raw)) {
                $custom_terms = $raw;
            } else {
                $custom_terms = [];
            }
        }

        // 1. Check custom terminology saved in tenant settings
        if (!empty($custom_terms[$key])) {
            return (string) $custom_terms[$key];
        }

        // 2. Check blueprint terminology
        $bp = current_industry_blueprint();
        if ($bp && !empty($bp['terminology'][$key])) {
            return (string) $bp['terminology'][$key];
        }

        // 3. Sensible universal defaults
        $universal_defaults = [
            'customer_label' => 'Müşteri',
            'provider_label' => 'Personel / Uzman',
            'service_label' => 'Hizmet',
            'station_label' => 'İstasyon / Oda',
            'appointment_label' => 'Randevu',
        ];

        return $fallback ?? ($universal_defaults[$key] ?? 'Kayıt');
    }
}

if (!function_exists('is_module_enabled')) {
    /**
     * Check whether a modular feature is enabled for the active tenant / industry.
     *
     * Evaluates settings ('features_enabled_json') and falls back to blueprint 'enabled_modules'.
     *
     * @param string $module
     * @return bool
     */
    function is_module_enabled(string $module): bool
    {
        static $features_cache = null;

        if ($features_cache === null) {
            $raw = setting('features_enabled_json');
            if (!empty($raw) && is_string($raw)) {
                $features_cache = json_decode($raw, true) ?: [];
            } elseif (is_array($raw)) {
                $features_cache = $raw;
            } else {
                $features_cache = [];
            }
        }

        // If explicitly set in features_enabled_json
        if (isset($features_cache[$module])) {
            return (bool) $features_cache[$module];
        }

        // Otherwise check active blueprint's enabled_modules
        $bp = current_industry_blueprint();
        if ($bp && isset($bp['enabled_modules']) && is_array($bp['enabled_modules'])) {
            return in_array($module, $bp['enabled_modules'], true);
        }

        // Core modules default to enabled
        $core_modules = ['appointments', 'calendar', 'customers', 'services', 'reports', 'settings'];
        return in_array($module, $core_modules, true);
    }
}

if (!function_exists('industry_dashboard_config')) {
    /**
     * Get industry-specific dashboard configuration:
     * - KPI card titles and icons
     * - Quick action buttons tailored to the sector
     * - Attention panel emphasis
     *
     * @param string|null $code
     * @return array
     */
    function industry_dashboard_config(?string $code = null): array
    {
        $code = $code ?: current_industry_code();
        $url = static fn(string $path): string => function_exists('site_url') ? site_url($path) : '/' . ltrim($path, '/');

        switch ($code) {
            case 'dentist':
            case 'doctor_clinic':
                return [
                    'badge' => 'Klinik & Sağlık Yönetim Paneli',
                    'kpi_1_title' => 'Bugünkü Hastalar',
                    'kpi_1_sub' => 'planlanan muayene',
                    'kpi_2_title' => 'Bugünkü Tedavi Cirosu',
                    'kpi_3_title' => 'Aktif Tedaviler',
                    'kpi_3_sub' => 'ünitte devam eden',
                    'kpi_4_title' => 'Ünit & Hekim Doluluğu',
                    'quick_actions' => [
                        ['label' => '+ Yeni Hasta Randevusu', 'url' => $url('calendar'), 'class' => 'btn-primary'],
                        ['label' => '+ Hasta Kaydı', 'url' => $url('customers'), 'class' => 'btn-outline-secondary'],
                        ['label' => '🩺 Ünit & Oda Durumu', 'url' => $url('stations'), 'class' => 'btn-outline-secondary'],
                        ['label' => '₺ Tahsilat & Fatura', 'url' => $url('invoices'), 'class' => 'btn-outline-secondary'],
                    ],
                ];

            case 'restaurant':
                return [
                    'badge' => 'Restoran & Masa Yönetim Paneli',
                    'kpi_1_title' => 'Bugünkü Rezervasyonlar',
                    'kpi_1_sub' => 'beklenen misafir masası',
                    'kpi_2_title' => 'Bugünkü Restoran Hasılatı',
                    'kpi_3_title' => 'Açık Masalar & Adisyon',
                    'kpi_3_sub' => 'canlı masada servis',
                    'kpi_4_title' => 'Masa Doluluk Oranı',
                    'quick_actions' => [
                        ['label' => '🍽️ Canlı Masa Planı', 'url' => $url('restaurant'), 'class' => 'btn-danger'],
                        ['label' => '+ Yeni Masa Rezervasyonu', 'url' => $url('restaurant/reservations'), 'class' => 'btn-primary'],
                        ['label' => '+ Yeni Adisyon', 'url' => $url('adisyons'), 'class' => 'btn-warning text-dark'],
                        ['label' => '⚡ Hızlı POS Satış', 'url' => $url('pos'), 'class' => 'btn-outline-secondary'],
                    ],
                ];

            case 'gym':
            case 'pilates_studio':
            case 'pt_training':
                return [
                    'badge' => 'Spor & Stüdyo Yönetim Paneli',
                    'kpi_1_title' => 'Bugünkü Seans & Dersler',
                    'kpi_1_sub' => 'planlanan antrenman',
                    'kpi_2_title' => 'Üyelik & Paket Cirosu',
                    'kpi_3_title' => 'Salondaki Üyeler',
                    'kpi_3_sub' => 'turnike / check-in aktif',
                    'kpi_4_title' => 'Stüdyo Kapasite Doluluğu',
                    'quick_actions' => [
                        ['label' => '+ Yeni Ders / Seans', 'url' => $url('calendar'), 'class' => 'btn-primary'],
                        ['label' => '🎫 Üyelik / Paket Satışı', 'url' => $url('packages'), 'class' => 'btn-success'],
                        ['label' => '📥 Turnike / Üye Girişi', 'url' => $url('checkin'), 'class' => 'btn-info text-white'],
                        ['label' => '+ Yeni Üye Kaydı', 'url' => $url('customers'), 'class' => 'btn-outline-secondary'],
                    ],
                ];

            case 'car_wash':
                return [
                    'badge' => 'Oto Yıkama & Detailing Paneli',
                    'kpi_1_title' => 'Bugünkü Araç Kabul',
                    'kpi_1_sub' => 'araç randevusu',
                    'kpi_2_title' => 'Bugünkü Yıkama Cirosu',
                    'kpi_3_title' => 'Perondaki Araçlar',
                    'kpi_3_sub' => 'işlemde olan',
                    'kpi_4_title' => 'Peron Doluluk Oranı',
                    'quick_actions' => [
                        ['label' => '+ Yeni Araç Kabul', 'url' => $url('calendar'), 'class' => 'btn-primary'],
                        ['label' => '🚗 Peron & İstasyonlar', 'url' => $url('stations'), 'class' => 'btn-outline-secondary'],
                        ['label' => '⚡ Hızlı Tahsilat (POS)', 'url' => $url('pos'), 'class' => 'btn-outline-secondary'],
                        ['label' => '+ Araç Sahibi Ekle', 'url' => $url('customers'), 'class' => 'btn-outline-secondary'],
                    ],
                ];

            case 'barber':
                return [
                    'badge' => 'Berber & Kuaför Yönetim Paneli',
                    'kpi_1_title' => 'Bugünkü Randevular',
                    'kpi_1_sub' => 'planlanan traş & bakım',
                    'kpi_2_title' => 'Bugünkü Kasa Cirosu',
                    'kpi_3_title' => 'Koltuktaki Müşteriler',
                    'kpi_3_sub' => 'canlı seans',
                    'kpi_4_title' => 'Koltuk Doluluk Oranı',
                    'quick_actions' => [
                        ['label' => '+ Yeni Randevu', 'url' => $url('calendar'), 'class' => 'btn-primary'],
                        ['label' => '+ Müşteri Ekle', 'url' => $url('customers'), 'class' => 'btn-outline-secondary'],
                        ['label' => '⚡ Hızlı Satış (POS)', 'url' => $url('pos'), 'class' => 'btn-warning text-dark'],
                        ['label' => '⏳ Bekleme Sırası', 'url' => $url('waitlist'), 'class' => 'btn-outline-secondary'],
                    ],
                ];

            case 'hotel':
                return [
                    'badge' => 'Otel & Konaklama Yönetim Paneli',
                    'kpi_1_title' => 'Bugünkü Giriş / Çıkışlar',
                    'kpi_1_sub' => 'oda rezervasyonu',
                    'kpi_2_title' => 'Bugünkü Gelir',
                    'kpi_3_title' => 'Konaklayan Misafirler',
                    'kpi_3_sub' => 'aktif odada',
                    'kpi_4_title' => 'Oda Doluluk Oranı',
                    'quick_actions' => [
                        ['label' => '+ Yeni Rezervasyon', 'url' => $url('calendar'), 'class' => 'btn-primary'],
                        ['label' => '🏨 Odalar & Bloklar', 'url' => $url('stations'), 'class' => 'btn-outline-secondary'],
                        ['label' => '+ Misafir Kaydı', 'url' => $url('customers'), 'class' => 'btn-outline-secondary'],
                        ['label' => '🧾 Folyo / e-Fatura', 'url' => $url('invoices'), 'class' => 'btn-outline-secondary'],
                    ],
                ];

            case 'beauty_salon':
            case 'nail_studio':
            case 'massage_spa':
            default:
                return [
                    'badge' => 'Güzellik & Bakım Yönetim Paneli',
                    'kpi_1_title' => 'Bugünkü Randevular',
                    'kpi_1_sub' => 'bugün için planlanan',
                    'kpi_2_title' => 'Bugünkü Gelir',
                    'kpi_3_title' => 'Aktif Seanslar',
                    'kpi_3_sub' => 'canlı',
                    'kpi_4_title' => 'Doluluk Oranı',
                    'quick_actions' => [
                        ['label' => '+ Yeni Randevu', 'url' => $url('calendar'), 'class' => 'btn-primary'],
                        ['label' => '+ Müşteri Ekle', 'url' => $url('customers'), 'class' => 'btn-outline-secondary'],
                        ['label' => '⚡ Hızlı Ödeme (POS)', 'url' => $url('pos'), 'class' => 'btn-outline-secondary'],
                        ['label' => '+ Bekleme Talebi', 'url' => $url('waitlist'), 'class' => 'btn-outline-secondary'],
                    ],
                ];
        }
    }
}

if (!function_exists('resolve_schema_org_type')) {
    /**
     * Resolve the precise Schema.org JSON-LD type across all 6 core sectors:
     * 1. Beauty/Spa: BeautySalon, DaySpa, HairSalon
     * 2. Restaurant: FoodEstablishment, Restaurant
     * 3. Sports: SportsActivityLocation, ExerciseGym
     * 4. Health: MedicalClinic, Physician
     * 5. Automotive: AutoRepair, AutoWash
     * 6. Experience/Lodging: LodgingBusiness, EventVenue
     *
     * @param string|array|null $context Context string (industry/blueprint code, primary_type, category, sector)
     *                                   or associative array of business metadata.
     * @return string Precise Schema.org type (subtype of LocalBusiness)
     */
    function resolve_schema_org_type($context = null): string
    {
        $raw_candidates = [];

        if (is_array($context)) {
            if (!empty($context['schema_type'])) {
                return (string) $context['schema_type'];
            }
            foreach (['industry_code', 'blueprint', 'business_type', 'primary_type', 'google_type', 'category', 'sector', 'name', 'company_name'] as $k) {
                if (!empty($context[$k]) && is_string($context[$k])) {
                    $raw_candidates[] = $context[$k];
                }
            }
        } elseif (is_string($context) && trim($context) !== '') {
            $raw_candidates[] = $context;
        } else {
            // Null or empty: resolve from current tenant settings / blueprint
            $raw_candidates[] = current_industry_code();
            $raw_candidates[] = setting('business_type') ?: '';
            $raw_candidates[] = setting('company_name') ?: '';
        }

        $haystack = mb_strtolower(implode(' ', array_filter($raw_candidates)), 'UTF-8');

        // Sector 1: Beauty & Spa (HairSalon / DaySpa / BeautySalon)
        if (!preg_match('/\b(car_wash|auto_wash|auto_repair)\b|oto kuaf|oto y\x{0131}ka|oto yika|ara\x{00e7} y/iu', $haystack) &&
            preg_match('/\b(barber|hair_salon|hair_care|hairdresser|barber_shop)\b|kuaför|kuafor|berber|saç|sac|erkek kuaf|bayan kuaf/iu', $haystack)) {
            return 'HairSalon';
        }
        if (preg_match('/\b(massage_spa|spa_massage|day_spa|massage|spa)\b|masaj|spa|hamam|sauna|terapi|wellness/iu', $haystack)) {
            return 'DaySpa';
        }
        if (preg_match('/\b(beauty_salon|nail_studio|nail_salon|beauty|estetik)\b|güzellik|guzellik|tırnak|tirnak|nail|cilt bak|lazer|epilasyon/iu', $haystack)) {
            return 'BeautySalon';
        }

        // Sector 2: Restaurant & Food (Restaurant / FoodEstablishment)
        if (preg_match('/\b(restaurant|restaurant_cafe|steakhouse|diner)\b|restoran|restaurant|lokanta|meyhane|steakhouse|kebap|ocakbaşı|balikci|balıkçı/iu', $haystack)) {
            return 'Restaurant';
        }
        if (preg_match('/\b(cafe|bistro|coffee_shop|bakery|food_establishment|meal_takeaway)\b|kafe|cafe|bistro|kahve|pastane|fırın|firin|tatlıcı|tatlici/iu', $haystack)) {
            return 'FoodEstablishment';
        }

        // Sector 3: Sports & Fitness (ExerciseGym / SportsActivityLocation)
        if (preg_match('/\b(gym|pt_training|gym_fitness|fitness_center|crossfit)\b|fitness|spor salonu|vücut geliştirme|vucut gelistirme|antrenman|\bpt\b|personal train/iu', $haystack)) {
            return 'ExerciseGym';
        }
        if (preg_match('/\b(sports_court|pilates_studio|pilates_yoga|sports_complex|stadium|sports_activity_location)\b|halı saha|hali saha|spor tesisi|kort|tenis|pilates|yoga|havuz|stüdyo|studyo|dövüş|boks/iu', $haystack)) {
            return 'SportsActivityLocation';
        }

        // Sector 4: Health & Medical (MedicalClinic / Physician)
        if (preg_match('/\b(doctor_clinic|dentist|dental_clinic|medical_clinic|hospital|clinic)\b|klinik|poliklinik|tıp merkezi|tip merkezi|diş klini|dis klini|ağız ve diş|agiz ve dis/iu', $haystack)) {
            return 'MedicalClinic';
        }
        if (preg_match('/\b(physician|doctor|psychology_dietitian_clinic|dietitian|psychologist|physiotherapy|physiotherapist)\b|doktor|hekim|muayenehane|uzman doktor|psikolog|diyetisyen|fizyoterapist|danışmanlık|danismanlik/iu', $haystack)) {
            return 'Physician';
        }

        // Sector 5: Automotive & Mobility (AutoWash / AutoRepair)
        if (preg_match('/\b(car_wash|auto_wash)\b|oto yıkama|oto yikama|oto kuaför|oto kuafor|araç yıkama|arac yikama|car wash/iu', $haystack)) {
            return 'AutoWash';
        }
        if (preg_match('/\b(auto_service_detailing|auto_repair|car_repair|auto_service)\b|oto servis|oto tamir|mekanik|periyodik bakım|bakim|ekspertiz|detailing|kaporta|rot balans|lastik/iu', $haystack)) {
            return 'AutoRepair';
        }

        // Sector 6: Lodging (LodgingBusiness)
        if (preg_match('/\b(hotel|lodging|lodging_hotel|resort|bed_and_breakfast|hostel|bungalov|villa|glamping)\b|otel|hotel|butik otel|pansiyon|tatil köyü|resort|bungalov|konaklama/iu', $haystack)) {
            return 'LodgingBusiness';
        }

        // Sector 7: Experience & Entertainment (EventVenue / EntertainmentBusiness)
        if (preg_match('/\b(experience_escape_room|event_venue|escape_room|amusement_center|arcade|bowling|bilardo)\b|kaçış oyunu|kacis oyunu|escape room|etkinlik alanı|etkinlik alani|düğün salonu|dugun salonu|davet alanı|kına konağı|balo salonu|eğlence/iu', $haystack)) {
            return 'EventVenue';
        }

        // Sector 8: Education & Courses (EducationalOrganization)
        if (preg_match('/\b(education|course|school|language_course|music_course|tutoring)\b|kurs|eğitim|egitim|dershane|dil kursu|sürücü kursu|etüt|akademi|özel ders/iu', $haystack)) {
            return 'EducationalOrganization';
        }

        // Sector 9: Professional Services (LegalService / AccountingService / ProfessionalService)
        if (preg_match('/\b(lawyer|attorney|legal|law_firm)\b|avukat|hukuk|baro|dava/iu', $haystack)) {
            return 'LegalService';
        }
        if (preg_match('/\b(accounting|accountant|financial_advisor)\b|muhasebe|mali müşavir|mali musavir|vergi/iu', $haystack)) {
            return 'AccountingService';
        }
        if (preg_match('/\b(consulting|agency|architecture|marketing)\b|danışmanlık|danismanlik|ajans|mimarlık|mimarlik|sigorta/iu', $haystack)) {
            return 'ProfessionalService';
        }

        // Default fallback
        return 'LocalBusiness';
    }
}

if (!function_exists('generate_schema_org_json_ld')) {
    /**
     * Generate complete Schema.org JSON-LD structured data with full GEO, opening hours,
     * ratings, reviews, catalog offers, and ReserveAction.
     *
     * @param array $params Metadata parameters
     * @return array
     */
    function generate_schema_org_json_ld(array $params): array
    {
        $schema_type = resolve_schema_org_type($params['type_context'] ?? $params);
        $default_base = function_exists('base_url') ? base_url() : '/';
        $url = $params['url'] ?? $default_base;
        $company_default = function_exists('setting') ? setting('company_name') : 'İşletme';
        $name = $params['name'] ?? ($company_default ?: 'İşletme');
        $default_logo = function_exists('base_url') ? base_url('assets/img/logo.png') : '/assets/img/logo.png';
        $image = !empty($params['image']) ? $params['image'] : (!empty($params['cover_image_url']) ? $params['cover_image_url'] : $default_logo);
        $description = $params['description'] ?? ($name . ' randevu ve rezervasyon noktası.');

        $json = [
            '@context' => 'https://schema.org',
            '@type' => $schema_type,
            'name' => $name,
            'url' => $url,
            'description' => $description,
            'image' => $image,
            'priceRange' => $params['price_range'] ?? '₺₺',
        ];

        if (!empty($params['telephone'])) {
            $json['telephone'] = $params['telephone'];
        } elseif (!empty($params['phone'])) {
            $json['telephone'] = $params['phone'];
        } elseif (!empty($params['phone_number'])) {
            $json['telephone'] = $params['phone_number'];
        }

        // Address
        if (!empty($params['address'])) {
            $addr = $params['address'];
            if (is_array($addr)) {
                $json['address'] = [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $addr['streetAddress'] ?? ($addr['address'] ?? null),
                    'addressLocality' => $addr['addressLocality'] ?? ($addr['district'] ?? null),
                    'addressRegion' => $addr['addressRegion'] ?? ($addr['city'] ?? null),
                    'postalCode' => $addr['postalCode'] ?? ($addr['zip_code'] ?? null),
                    'addressCountry' => $addr['addressCountry'] ?? 'TR',
                ];
            } else {
                $json['address'] = [
                    '@type' => 'PostalAddress',
                    'streetAddress' => (string) $addr,
                    'addressLocality' => $params['district'] ?? null,
                    'addressRegion' => $params['city'] ?? null,
                    'addressCountry' => 'TR',
                ];
            }
        } elseif (!empty($params['city']) || !empty($params['district'])) {
            $json['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => null,
                'addressLocality' => $params['district'] ?? null,
                'addressRegion' => $params['city'] ?? null,
                'addressCountry' => 'TR',
            ];
        }

        // Coordinate precision (DECIMAL 10,8 / 11,8 -> formatted up to 7 decimal digits, sub-meter)
        if (!empty($params['latitude']) && !empty($params['longitude'])) {
            $json['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => round((float) $params['latitude'], 7),
                'longitude' => round((float) $params['longitude'], 7),
            ];
        }

        // AggregateRating
        $rating = !empty($params['rating']) ? (float) $params['rating'] : (!empty($params['avg_rating']) ? (float) $params['avg_rating'] : 0);
        $review_count = !empty($params['review_count']) ? (int) $params['review_count'] : (!empty($params['user_rating_count']) ? (int) $params['user_rating_count'] : 0);

        if ($rating > 0) {
            $json['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round($rating, 1),
                'reviewCount' => max(1, $review_count),
                'bestRating' => '5',
                'worstRating' => '1',
            ];
        }

        // Reviews
        if (!empty($params['reviews']) && is_array($params['reviews'])) {
            $json['review'] = [];
            foreach (array_slice($params['reviews'], 0, 10) as $rev) {
                $json['review'][] = [
                    '@type' => 'Review',
                    'author' => ['@type' => 'Person', 'name' => $rev['customer_name'] ?? ($rev['author'] ?? 'Müşteri')],
                    'datePublished' => !empty($rev['created_at']) ? date('Y-m-d', strtotime($rev['created_at'])) : date('Y-m-d'),
                    'reviewRating' => [
                        '@type' => 'Rating',
                        'ratingValue' => (int) ($rev['rating'] ?? 5),
                    ],
                    'reviewBody' => $rev['comment'] ?? ($rev['text'] ?? ''),
                ];
            }
        }

        // Opening Hours Specifications (DST safe)
        if (!empty($params['opening_hours_specification'])) {
            $json['openingHoursSpecification'] = $params['opening_hours_specification'];
        } elseif (!empty($params['opening_hours'])) {
            $json['openingHours'] = $params['opening_hours'];
        }

        // OfferCatalog
        if (!empty($params['services']) && is_array($params['services'])) {
            $json['hasOfferCatalog'] = [
                '@type' => 'OfferCatalog',
                'name' => 'Hizmetler & Randevu Seçenekleri',
                'itemListElement' => array_map(function ($s) {
                    return [
                        '@type' => 'Offer',
                        'itemOffered' => [
                            '@type' => 'Service',
                            'name' => $s['name'],
                            'description' => $s['description'] ?? null,
                        ],
                        'price' => (float) ($s['price'] ?? 0),
                        'priceCurrency' => $s['currency'] ?? 'TRY',
                    ];
                }, array_slice($params['services'], 0, 25)),
            ];
        }

        // ReserveAction
        $booking_url = $params['booking_url'] ?? $url;
        $json['potentialAction'] = [
            '@type' => 'ReserveAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $booking_url,
                'inLanguage' => 'tr',
                'actionPlatform' => [
                    'https://schema.org/DesktopWebPlatform',
                    'https://schema.org/MobileWebPlatform',
                ],
            ],
            'result' => [
                '@type' => 'Reservation',
                'name' => 'Online Randevu',
            ],
        ];

        // Google Maps URL
        if (!empty($params['google_maps_uri'])) {
            $json['hasMap'] = $params['google_maps_uri'];
        } elseif (!empty($params['latitude']) && !empty($params['longitude'])) {
            $json['hasMap'] = 'https://maps.google.com/?q=' . urlencode($params['latitude'] . ',' . $params['longitude']);
        }

        return $json;
    }
}
