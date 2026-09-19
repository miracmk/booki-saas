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
