<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi Navigation Service.
 *
 * Provides central, schema-driven, vertical-aware, and permission-aware navigation.
 * Eliminates scattered if/else branches from header and sidebar views.
 *
 * Item Schema:
 * - id: unique string identifier
 * - label: localized / terminology-aware title
 * - icon: FontAwesome icon class
 * - route: controller/action path (without site_url)
 * - group: one of the 12 standard groups
 * - order: sort order within group
 * - module: required feature flag / module slug
 * - permission: [action, resource] pair for access control
 * - children: nested sub-items (optional)
 * - badge: notification badge or tag (optional)
 * - mobile_visibility: boolean
 */
class Navigation_service
{
    protected CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('vertical_service');
        $this->CI->load->library('permission_service');
    }

    /**
     * Build navigation tree tailored for the currently logged in user.
     *
     * @return array Grouped navigation items: [group_key => ['title' => ..., 'icon' => ..., 'items' => [...]]]
     */
    public function forCurrentUser(): array
    {
        $user_id = (int) session('user_id');
        $role_slug = (string) (session('role_slug') ?: 'customer');
        $business_type = $this->CI->vertical_service->current_business_type();
        $family = $this->CI->vertical_service->resolve_family($business_type);
        $terms = $this->CI->vertical_service->get_terminology($business_type);

        $catalog_label = $terms['catalog'] ?? 'Katalog';
        $customer_label = $terms['customer'] ?? 'Müşteriler';
        $provider_label = $terms['provider'] ?? 'Personel / Ekip';
        $appointment_label = $terms['appointment'] ?? 'Randevular';
        $station_label = $terms['station'] ?? 'İstasyonlar & Odalar';

        $all_items = $this->get_raw_navigation_items($family, $business_type, $terms);

        // Filter items based on module enablement and user permissions
        $filtered_groups = [
            'dashboard' => ['title' => 'Dashboard', 'icon' => 'fas fa-gauge-high', 'items' => []],
            'operations' => ['title' => 'Operasyon', 'icon' => 'fas fa-calendar-check', 'items' => []],
            'crm' => ['title' => "{$customer_label} & CRM", 'icon' => 'fas fa-user-friends', 'items' => []],
            'catalog' => ['title' => $catalog_label, 'icon' => 'fas fa-layer-group', 'items' => []],
            'resources' => ['title' => 'Kaynaklar', 'icon' => 'fas fa-door-open', 'items' => []],
            'team' => ['title' => 'Ekip & İK', 'icon' => 'fas fa-users-cog', 'items' => []],
            'finance' => ['title' => 'Satış & Finans', 'icon' => 'fas fa-wallet', 'items' => []],
            'marketing' => ['title' => 'Pazarlama & Mesajlaşma', 'icon' => 'fas fa-comments-dollar', 'items' => []],
            'vertical' => ['title' => 'Vertical Modülleri', 'icon' => 'fas fa-shapes', 'items' => []],
            'reports' => ['title' => 'Raporlar', 'icon' => 'fas fa-chart-pie', 'items' => []],
            'ai' => ['title' => 'AI Asistan', 'icon' => 'fas fa-robot', 'items' => []],
            'randevuburada' => ['title' => 'RandevuBurada', 'icon' => 'fas fa-store text-warning', 'items' => []],
            'settings' => ['title' => 'Ayarlar', 'icon' => 'fas fa-cogs', 'items' => []],
        ];

        foreach ($all_items as $item) {
            if (!$this->is_item_visible($item, $user_id)) {
                continue;
            }

            // Filter children if present
            if (!empty($item['children'])) {
                $filtered_children = [];
                foreach ($item['children'] as $child) {
                    if ($this->is_item_visible($child, $user_id)) {
                        $filtered_children[] = $child;
                    }
                }
                $item['children'] = $filtered_children;

                // If parent has children but none are visible, skip parent
                if (empty($item['children']) && empty($item['route'])) {
                    continue;
                }
            }

            $group_key = $item['group'] ?? 'operations';
            if (isset($filtered_groups[$group_key])) {
                $filtered_groups[$group_key]['items'][] = $item;
            }
        }

        // Remove empty groups
        return array_filter($filtered_groups, static fn ($g) => !empty($g['items']));
    }

    /**
     * Check if a specific navigation item is visible for the user.
     */
    protected function is_item_visible(array $item, int $user_id): bool
    {
        // 1. Check Module Enablement
        if (!empty($item['module'])) {
            if (!module_enabled($item['module'])) {
                return false;
            }
        }

        // 2. Check Permission
        if (!empty($item['permission'])) {
            [$action, $resource] = $item['permission'];
            if (!$this->CI->permission_service->can($action, $resource, $user_id)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Define the full schema of navigation items across all 12 groups.
     */
    protected function get_raw_navigation_items(string $family, string $business_type, array $terms): array
    {
        $items = [];

        // 1. DASHBOARD
        $items[] = [
            'id' => 'nav_dashboard',
            'label' => 'Dashboard',
            'icon' => 'fas fa-gauge-high',
            'route' => 'dashboard',
            'group' => 'dashboard',
            'order' => 1,
            'module' => null,
            'permission' => ['view', 'dashboard'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        // 2. OPERASYON
        $items[] = [
            'id' => 'nav_calendar',
            'label' => 'Takvim & Ajanda',
            'icon' => 'fas fa-calendar-alt',
            'route' => 'calendar',
            'group' => 'operations',
            'order' => 1,
            'module' => 'calendar',
            'permission' => ['view', 'appointments'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        $items[] = [
            'id' => 'nav_waitlist',
            'label' => 'Bekleme Listesi',
            'icon' => 'fas fa-hourglass-half',
            'route' => 'waitlist',
            'group' => 'operations',
            'order' => 2,
            'module' => 'waitlist',
            'permission' => ['view', 'waitlist'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        $items[] = [
            'id' => 'nav_checkin',
            'label' => 'Giriş / Kiosk (Check-in)',
            'icon' => 'fas fa-sign-in-alt',
            'route' => 'checkin',
            'group' => 'operations',
            'order' => 3,
            'module' => 'checkin',
            'permission' => ['view', 'checkin'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => false,
        ];

        // Vertical-specific operation items (Restaurant, Clinic, Sports, etc.)
        if ($family === 'restaurant_food') {
            $items[] = [
                'id' => 'nav_rest_floor_plan',
                'label' => 'Canlı Masa Planı (2D Kroki)',
                'icon' => 'fas fa-border-all',
                'route' => 'restaurant',
                'group' => 'operations',
                'order' => 4,
                'module' => 'restaurant_floor_plan',
                'permission' => ['view', 'restaurant_floor_plan'],
                'children' => [],
                'badge' => 'Canlı',
                'mobile_visibility' => true,
            ];
            $items[] = [
                'id' => 'nav_rest_reservations',
                'label' => 'Masa Rezervasyonları',
                'icon' => 'fas fa-calendar-check',
                'route' => 'restaurant/reservations',
                'group' => 'operations',
                'order' => 5,
                'module' => 'restaurant_reservations',
                'permission' => ['view', 'restaurant_reservations'],
                'children' => [],
                'badge' => null,
                'mobile_visibility' => true,
            ];
            $items[] = [
                'id' => 'nav_rest_waitress',
                'label' => 'Garson POS Ekranı',
                'icon' => 'fas fa-mobile-alt',
                'route' => 'restaurant/waitress_screen',
                'group' => 'operations',
                'order' => 6,
                'module' => 'adisyon',
                'permission' => ['add', 'adisyons'],
                'children' => [],
                'badge' => null,
                'mobile_visibility' => true,
            ];
            $items[] = [
                'id' => 'nav_rest_register',
                'label' => 'Kasa & Adisyon Terminali',
                'icon' => 'fas fa-cash-register',
                'route' => 'restaurant/register_screen',
                'group' => 'operations',
                'order' => 7,
                'module' => 'pos',
                'permission' => ['view', 'pos'],
                'children' => [],
                'badge' => null,
                'mobile_visibility' => true,
            ];
        }

        // 3. CRM & MÜŞTERİLER
        $items[] = [
            'id' => 'nav_customers',
            'label' => "Tüm {$terms['customer']}lar",
            'icon' => 'fas fa-users',
            'route' => 'customers',
            'group' => 'crm',
            'order' => 1,
            'module' => 'customers',
            'permission' => ['view', 'customers'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        // 4. KATALOG (Unifying Services, Products, Packages, Memberships)
        // Services / Procedures / Menus
        $items[] = [
            'id' => 'nav_cat_services',
            'label' => $terms['service'],
            'icon' => 'fas fa-concierge-bell',
            'route' => 'services',
            'group' => 'catalog',
            'order' => 1,
            'module' => 'services',
            'permission' => ['view', 'services'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        // Products / Inventory
        $items[] = [
            'id' => 'nav_cat_products',
            'label' => $terms['product'],
            'icon' => 'fas fa-boxes',
            'route' => 'products',
            'group' => 'catalog',
            'order' => 2,
            'module' => 'inventory',
            'permission' => ['view', 'products'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        // Packages
        $items[] = [
            'id' => 'nav_cat_packages',
            'label' => $terms['package'],
            'icon' => 'fas fa-box-open',
            'route' => 'packages',
            'group' => 'catalog',
            'order' => 3,
            'module' => 'packages',
            'permission' => ['view', 'packages'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        // Memberships
        $items[] = [
            'id' => 'nav_cat_memberships',
            'label' => $terms['membership'],
            'icon' => 'fas fa-id-card',
            'route' => 'memberships',
            'group' => 'catalog',
            'order' => 4,
            'module' => 'memberships',
            'permission' => ['view', 'memberships'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        // 5. KAYNAKLAR
        $items[] = [
            'id' => 'nav_stations',
            'label' => $terms['station'],
            'icon' => 'fas fa-door-open',
            'route' => 'stations',
            'group' => 'resources',
            'order' => 1,
            'module' => 'stations',
            'permission' => ['view', 'stations'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        $items[] = [
            'id' => 'nav_blocked_periods',
            'label' => 'Blokaj & Tatil Günleri',
            'icon' => 'fas fa-ban',
            'route' => 'blocked_periods',
            'group' => 'resources',
            'order' => 2,
            'module' => 'calendar',
            'permission' => ['view', 'blocked_periods'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => false,
        ];

        // 6. EKİP & İNSAN KAYNAKLARI (HRMS)
        $items[] = [
            'id' => 'nav_hr',
            'label' => 'Personel & Özlük',
            'icon' => 'fas fa-users-cog',
            'route' => 'hr',
            'group' => 'team',
            'order' => 1,
            'module' => null,
            'permission' => ['view', 'users'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        $items[] = [
            'id' => 'nav_providers',
            'label' => $terms['provider'],
            'icon' => 'fas fa-user-tie',
            'route' => 'providers',
            'group' => 'team',
            'order' => 2,
            'module' => 'services',
            'permission' => ['view', 'users'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        $items[] = [
            'id' => 'nav_attendance',
            'label' => 'PDKS & Vardiya',
            'icon' => 'fas fa-clock',
            'route' => 'attendance',
            'group' => 'team',
            'order' => 3,
            'module' => null,
            'permission' => ['view', 'users'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        $items[] = [
            'id' => 'nav_leaves',
            'label' => 'İzin Yönetimi',
            'icon' => 'fas fa-calendar-check',
            'route' => 'leaves',
            'group' => 'team',
            'order' => 4,
            'module' => null,
            'permission' => ['view', 'users'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        $items[] = [
            'id' => 'nav_payroll',
            'label' => 'Bordro & Maaş',
            'icon' => 'fas fa-money-check-alt',
            'route' => 'payroll',
            'group' => 'team',
            'order' => 5,
            'module' => null,
            'permission' => ['view', 'financial_reports'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => false,
        ];

        $items[] = [
            'id' => 'nav_ess',
            'label' => 'Personel Portalı (ESS)',
            'icon' => 'fas fa-id-card-clip',
            'route' => 'ess',
            'group' => 'team',
            'order' => 6,
            'module' => null,
            'permission' => ['view', 'user_settings'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        // 7. SATIŞ & FİNANS
        $items[] = [
            'id' => 'nav_adisyons',
            'label' => 'Adisyonlar',
            'icon' => 'fas fa-receipt',
            'route' => 'adisyons',
            'group' => 'finance',
            'order' => 1,
            'module' => 'adisyon',
            'permission' => ['view', 'adisyons'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        $items[] = [
            'id' => 'nav_pos',
            'label' => 'Hızlı Satış (POS)',
            'icon' => 'fas fa-cash-register',
            'route' => 'pos',
            'group' => 'finance',
            'order' => 2,
            'module' => 'pos',
            'permission' => ['view', 'pos'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        $items[] = [
            'id' => 'nav_finance',
            'label' => 'Kasa & Gelir/Gider',
            'icon' => 'fas fa-chart-line',
            'route' => 'finance',
            'group' => 'finance',
            'order' => 3,
            'module' => 'finance',
            'permission' => ['view', 'finance'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => false,
        ];

        $items[] = [
            'id' => 'nav_invoices',
            'label' => 'Faturalar & e-Fatura',
            'icon' => 'fas fa-file-invoice-dollar',
            'route' => 'invoices',
            'group' => 'finance',
            'order' => 4,
            'module' => 'invoices',
            'permission' => ['view', 'invoices'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => false,
        ];

        $items[] = [
            'id' => 'nav_expenses',
            'label' => 'Gider Yönetimi',
            'icon' => 'fas fa-money-bill-wave',
            'route' => 'expenses',
            'group' => 'finance',
            'order' => 5,
            'module' => 'expenses',
            'permission' => ['view', 'expenses'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => false,
        ];

        // 8. PAZARLAMA & MESAJLAŞMA
        $items[] = [
            'id' => 'nav_chat_portal',
            'label' => 'Canlı Chat & AI Portalı',
            'icon' => 'fas fa-comments text-success',
            'route' => 'chat_portal',
            'group' => 'marketing',
            'order' => 0,
            'module' => null,
            'permission' => null,
            'children' => [],
            'badge' => 'Canlı',
            'mobile_visibility' => true,
        ];

        $items[] = [
            'id' => 'nav_marketing',
            'label' => 'Kampanyalar & SMS',
            'icon' => 'fas fa-paper-plane',
            'route' => 'marketing',
            'group' => 'marketing',
            'order' => 1,
            'module' => 'marketing',
            'permission' => ['view', 'marketing'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => false,
        ];

        $items[] = [
            'id' => 'nav_reviews',
            'label' => 'Müşteri Değerlendirmeleri',
            'icon' => 'fas fa-star',
            'route' => 'reviews',
            'group' => 'marketing',
            'order' => 2,
            'module' => 'reviews',
            'permission' => ['view', 'reviews'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => false,
        ];

        // 8.1. RANDEVUBURADA PAZARYERİ ENTEGRASYONU
        $items[] = [
            'id' => 'nav_rb_profile',
            'label' => 'Vitrin & Profil',
            'icon' => 'fas fa-id-card',
            'route' => 'randevuburada/profile',
            'group' => 'randevuburada',
            'order' => 1,
            'module' => null,
            'permission' => null,
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        $items[] = [
            'id' => 'nav_rb_services',
            'label' => 'Hizmetler & Fiyatlar',
            'icon' => 'fas fa-tags',
            'route' => 'randevuburada/services',
            'group' => 'randevuburada',
            'order' => 2,
            'module' => null,
            'permission' => null,
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        $items[] = [
            'id' => 'nav_rb_reviews',
            'label' => 'Yorum Yönetimi',
            'icon' => 'fas fa-star-half-alt',
            'route' => 'randevuburada/reviews',
            'group' => 'randevuburada',
            'order' => 3,
            'module' => null,
            'permission' => null,
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        $items[] = [
            'id' => 'nav_rb_reservations',
            'label' => 'Pazaryeri Rezervasyonları',
            'icon' => 'fas fa-calendar-check',
            'route' => 'randevuburada/reservations',
            'group' => 'randevuburada',
            'order' => 4,
            'module' => null,
            'permission' => null,
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        // 9. VERTICAL MODÜLLERİ (Coherent vertical suites)
        if ($family === 'restaurant_food') {
            $items[] = [
                'id' => 'nav_qr_menu',
                'label' => 'QR Menü Stüdyosu',
                'icon' => 'fas fa-qrcode',
                'route' => 'restaurant/qr_menu_manager',
                'group' => 'vertical',
                'order' => 1,
                'module' => 'restaurant_floor_plan',
                'permission' => ['view', 'restaurant_floor_plan'],
                'children' => [],
                'badge' => null,
                'mobile_visibility' => true,
            ];
            $items[] = [
                'id' => 'nav_kitchen_screen',
                'label' => 'Mutfak & Bar (KDS)',
                'icon' => 'fas fa-tv',
                'route' => 'restaurant/kitchen_screen',
                'group' => 'vertical',
                'order' => 2,
                'module' => 'verticals_kds',
                'permission' => ['view', 'verticals_kds'],
                'children' => [],
                'badge' => null,
                'mobile_visibility' => true,
            ];
            $items[] = [
                'id' => 'nav_print_qr_stands',
                'label' => 'Masa QR Stand Baskı Merkezi',
                'icon' => 'fas fa-print',
                'route' => 'restaurant/print_qr_stands',
                'group' => 'vertical',
                'order' => 3,
                'module' => 'restaurant_floor_plan',
                'permission' => ['view', 'restaurant_floor_plan'],
                'children' => [],
                'badge' => null,
                'mobile_visibility' => false,
            ];
        } elseif ($family === 'health_clinical') {
            $items[] = [
                'id' => 'nav_clinical_ehr',
                'label' => 'EHR / SOAP Klinik Dosyası',
                'icon' => 'fas fa-notes-medical',
                'route' => 'verticals/clinic',
                'group' => 'vertical',
                'order' => 1,
                'module' => 'verticals_clinic',
                'permission' => ['view', 'verticals_clinic'],
                'children' => [],
                'badge' => null,
                'mobile_visibility' => true,
            ];
        } elseif ($family === 'sports_fitness') {
            $items[] = [
                'id' => 'nav_sports_matches',
                'label' => 'Kortlar, Maçlar & Turnike',
                'icon' => 'fas fa-volleyball-ball',
                'route' => 'verticals/sports',
                'group' => 'vertical',
                'order' => 1,
                'module' => 'verticals_sports',
                'permission' => ['view', 'verticals_sports'],
                'children' => [],
                'badge' => null,
                'mobile_visibility' => true,
            ];
        } elseif ($family === 'automotive') {
            $items[] = [
                'id' => 'nav_auto_dvi',
                'label' => 'Araç Sicili & DVI Ekspertiz',
                'icon' => 'fas fa-tools',
                'route' => 'verticals/automotive',
                'group' => 'vertical',
                'order' => 1,
                'module' => 'verticals_automotive',
                'permission' => ['view', 'verticals_automotive'],
                'children' => [],
                'badge' => null,
                'mobile_visibility' => true,
            ];
        } elseif ($family === 'experience') {
            $items[] = [
                'id' => 'nav_exp_tickets',
                'label' => 'Feragatname & Biletler',
                'icon' => 'fas fa-ticket-alt',
                'route' => 'verticals/experience',
                'group' => 'vertical',
                'order' => 1,
                'module' => 'verticals_experience',
                'permission' => ['view', 'verticals_experience'],
                'children' => [],
                'badge' => null,
                'mobile_visibility' => true,
            ];
        } else {
            // Beauty & Spa
            $items[] = [
                'id' => 'nav_beauty_gift_cards',
                'label' => 'Hediye Kartı & Kapora',
                'icon' => 'fas fa-gift',
                'route' => 'verticals/gift_cards',
                'group' => 'vertical',
                'order' => 1,
                'module' => 'verticals_gift_cards',
                'permission' => ['view', 'verticals_gift_cards'],
                'children' => [],
                'badge' => null,
                'mobile_visibility' => true,
            ];
        }

        // 10. RAPORLAR
        $items[] = [
            'id' => 'nav_reports',
            'label' => 'İşletme Raporları',
            'icon' => 'fas fa-chart-pie',
            'route' => 'reports',
            'group' => 'reports',
            'order' => 1,
            'module' => 'reports',
            'permission' => ['view', 'reports'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => false,
        ];

        // 11. AI ASİSTAN
        $items[] = [
            'id' => 'nav_ai_agent',
            'label' => 'AI Asistan',
            'icon' => 'fas fa-robot',
            'route' => 'ai_agent',
            'group' => 'ai',
            'order' => 1,
            'module' => 'ai_agent',
            'permission' => ['view', 'ai_agent'],
            'children' => [],
            'badge' => 'Copilot',
            'mobile_visibility' => true,
        ];

        // 12. AYARLAR
        $items[] = [
            'id' => 'nav_settings_center',
            'label' => lang('settings_center') ?: 'Ayar Merkezi',
            'icon' => 'fas fa-sliders-h',
            'route' => 'settings',
            'group' => 'settings',
            'order' => 1,
            'module' => null,
            'permission' => ['view', 'system_settings'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        $items[] = [
            'id' => 'nav_account',
            'label' => 'Profil & Hesap Ayarları',
            'icon' => 'fas fa-user-cog',
            'route' => 'account',
            'group' => 'settings',
            'order' => 2,
            'module' => null,
            'permission' => null,
            'children' => [],
            'badge' => null,
            'mobile_visibility' => true,
        ];

        $items[] = [
            'id' => 'nav_set_industry',
            'label' => lang('industry_and_modules') ?: 'Sektör & Modüller',
            'icon' => 'fas fa-shapes',
            'route' => 'industry_settings',
            'group' => 'settings',
            'order' => 3,
            'module' => null,
            'permission' => ['view', 'system_settings'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => false,
        ];

        $items[] = [
            'id' => 'nav_set_branches',
            'label' => lang('branches') ?: 'Şubeler',
            'icon' => 'fas fa-code-branch',
            'route' => 'branches',
            'group' => 'settings',
            'order' => 4,
            'module' => null,
            'permission' => ['view', 'branches'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => false,
        ];

        $items[] = [
            'id' => 'nav_set_audit',
            'label' => lang('audit_log') ?: 'Denetim Günlüğü',
            'icon' => 'fas fa-clipboard-list',
            'route' => 'audit_log',
            'group' => 'settings',
            'order' => 5,
            'module' => null,
            'permission' => ['view', 'system_settings'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => false,
        ];

        $items[] = [
            'id' => 'nav_data_requests',
            'label' => 'KVKK & Veri Talepleri',
            'icon' => 'fas fa-shield-alt',
            'route' => 'data_requests',
            'group' => 'settings',
            'order' => 6,
            'module' => null,
            'permission' => ['view', 'system_settings'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => false,
        ];

        $items[] = [
            'id' => 'nav_onboarding',
            'label' => 'Sektör Sihirbazı',
            'icon' => 'fas fa-magic',
            'route' => 'onboarding',
            'group' => 'settings',
            'order' => 7,
            'module' => null,
            'permission' => ['view', 'system_settings'],
            'children' => [],
            'badge' => null,
            'mobile_visibility' => false,
        ];

        return $items;
    }
}
