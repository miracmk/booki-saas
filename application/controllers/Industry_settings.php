<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Industry Settings & Modular Blueprint Configuration Controller
 *
 * Allows business owners and administrators to select an industry blueprint,
 * customize domain terminology (Customer/Patient, Staff/Doctor, Service/Treatment,
 * Station/Unit/Table), and toggle modular features directly from Settings at any time.
 * ---------------------------------------------------------------------------- */

class Industry_settings extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('settings_model');
        $this->load->library('accounts');
        $this->load->library('blueprint_service');
    }

    /**
     * Display the Industry & Modular Configuration settings page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('industry_settings')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');
            return;
        }

        $all_blueprints = $this->blueprint_service->get_all_blueprints();
        $active_industry = current_industry_code();

        // Load custom terminology from settings or active blueprint
        $raw_terminology = setting('industry_custom_terminology');
        $current_terminology = !empty($raw_terminology) && is_string($raw_terminology)
            ? json_decode($raw_terminology, true)
            : ($raw_terminology ?: []);

        $active_bp = $this->blueprint_service->get_blueprint($active_industry);
        if ($active_bp && !empty($active_bp['terminology'])) {
            $current_terminology = array_merge($active_bp['terminology'], $current_terminology ?: []);
        }

        // Load enabled features/modules
        $raw_features = setting('features_enabled_json');
        $current_features = !empty($raw_features) && is_string($raw_features)
            ? json_decode($raw_features, true)
            : ($raw_features ?: []);

        if (empty($current_features) && $active_bp && !empty($active_bp['enabled_modules'])) {
            foreach ($active_bp['enabled_modules'] as $mod) {
                $current_features[$mod] = true;
            }
        }

        // Comprehensive modules list
        $available_modules = [
            'calendar' => ['name' => 'Takvim & Çizelge', 'icon' => 'fas fa-calendar-alt', 'desc' => 'Dinamik ajanda ve randevu takvimi', 'core' => true],
            'appointments' => ['name' => 'Randevu / Seans Yönetimi', 'icon' => 'fas fa-clock', 'desc' => 'Müşteri randevuları, durumları ve bildirimleri', 'core' => true],
            'stations' => ['name' => 'İstasyonlar / Odalar / Masalar', 'icon' => 'fas fa-door-open', 'desc' => 'Fiziksel alan, ünit, peron veya oda kapasite yönetimi', 'core' => false],
            'restaurant_floor_plan' => ['name' => 'Restoran & Canlı Masa Planı', 'icon' => 'fas fa-utensils', 'desc' => 'Kroki üzerinde canlı masa durumu ve adisyon entegrasyonu', 'core' => false],
            'restaurant_reservations' => ['name' => 'Masa Rezervasyonları', 'icon' => 'fas fa-chair', 'desc' => 'Öğle/akşam servisi masa ayırtma ve kişi sayısı takibi', 'core' => false],
            'adisyon' => ['name' => 'Adisyon & Sipariş Takibi', 'icon' => 'fas fa-receipt', 'desc' => 'Canlı adisyon açma, ürün/hizmet ekleme ve parçalı ödeme', 'core' => false],
            'pos' => ['name' => 'Hızlı Satış (POS)', 'icon' => 'fas fa-cash-register', 'desc' => 'Barkodlu veya dokunmatik hızlı kasa satışı', 'core' => false],
            'packages' => ['name' => 'Paket Seanslar', 'icon' => 'fas fa-box', 'desc' => 'Çoklu seans paketleri (örn. 10 Seans Lazer / Reformer)', 'core' => false],
            'memberships' => ['name' => 'Üyelikler & Abonelikler', 'icon' => 'fas fa-id-card', 'desc' => 'Aylık/yıllık üyelik kartları, periyodik yenilemeler', 'core' => false],
            'checkin' => ['name' => 'Kiosk & Müşteri Girişi (Check-in)', 'icon' => 'fas fa-sign-in-alt', 'desc' => 'Self-servis tablet kiosk veya turnike müşteri girişi', 'core' => false],
            'invoices' => ['name' => 'Faturalar & e-Fatura', 'icon' => 'fas fa-file-invoice-dollar', 'desc' => 'Resmi fatura kesimi, e-Arşiv/e-Fatura entegrasyonu', 'core' => false],
            'expenses' => ['name' => 'Gider & Masraf Yönetimi', 'icon' => 'fas fa-money-bill-wave', 'desc' => 'Kira, fatura, malzeme ve işletme giderleri', 'core' => false],
            'finance' => ['name' => 'Finans & Kasa Raporları', 'icon' => 'fas fa-chart-line', 'desc' => 'Nakit akışı, gün sonu Z raporu ve kasa hareketleri', 'core' => false],
            'inventory' => ['name' => 'Ürünler & Stok Takibi', 'icon' => 'fas fa-boxes', 'desc' => 'Kritik stok uyarıları, sarf ve perakende ürün yönetimi', 'core' => false],
            'staff_commissions' => ['name' => 'Personel Prim & Hakediş', 'icon' => 'fas fa-percentage', 'desc' => 'Hizmet ve ürün satışlarından personele prim hesabı', 'core' => false],
            'marketing' => ['name' => 'Pazarlama & Kampanyalar', 'icon' => 'fas fa-paper-plane', 'desc' => 'Toplu SMS/WhatsApp, geri kazanım ve doğum günü bildirimleri', 'core' => false],
            'reviews' => ['name' => 'Müşteri Değerlendirmeleri', 'icon' => 'fas fa-star', 'desc' => 'Otomatik memnuniyet anketleri ve itibar puanı', 'core' => false],
        ];

        $view_data = [
            'all_blueprints' => $all_blueprints,
            'active_industry' => $active_industry,
            'current_terminology' => $current_terminology,
            'current_features' => $current_features,
            'available_modules' => $available_modules,
        ];

        html_vars([
            'page_title' => 'Sektör & Modül Yapılandırması',
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
        ]);

        $this->load->view('pages/industry_settings', $view_data);
    }

    /**
     * Fetch blueprint defaults via AJAX for live interactive preview in the UI.
     */
    public function get_blueprint_defaults(string $code = ''): void
    {
        try {
            method('get');
            $code = $code ?: (string) $this->input->get('code');
            $bp = $this->blueprint_service->get_blueprint($code);

            if (!$bp) {
                throw new InvalidArgumentException("Sektör blueprint'i bulunamadı: {$code}");
            }

            json_response([
                'success' => true,
                'code' => $code,
                'name' => $bp['industry']['name'] ?? '',
                'icon' => $bp['industry']['icon'] ?? '🏢',
                'description' => $bp['industry']['description'] ?? '',
                'service_type' => $bp['industry']['service_type'] ?? 'duration',
                'terminology' => $bp['terminology'] ?? [],
                'enabled_modules' => $bp['enabled_modules'] ?? [],
                'default_settings' => $bp['default_settings'] ?? [],
                'services_count' => count($bp['services'] ?? []),
                'categories_count' => count($bp['service_categories'] ?? []),
            ]);
        } catch (Throwable $e) {
            json_response(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Save industry selection, update terminology, features toggles, and sync.
     */
    public function save(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('Bu işlem için yönetici yetkisi gerekmektedir.');
            }

            $industry_code = trim((string) $this->input->post('industry_code'));
            if (empty($industry_code)) {
                throw new InvalidArgumentException('Lütfen geçerli bir sektör seçiniz.');
            }

            $bp = $this->blueprint_service->get_blueprint($industry_code);
            if (!$bp) {
                throw new InvalidArgumentException("Geçersiz sektör kodu: {$industry_code}");
            }

            // 1. Process Terminology
            $terminology_input = $this->input->post('terminology');
            if (is_string($terminology_input)) {
                $terminology_input = json_decode($terminology_input, true) ?: [];
            }
            if (!is_array($terminology_input)) {
                $terminology_input = [];
            }

            $merged_terminology = array_merge(
                $bp['terminology'] ?? [],
                array_filter($terminology_input, static fn($val) => !empty(trim((string) $val)))
            );

            // 2. Process Enabled Modules
            $modules_input = $this->input->post('modules');
            if (is_string($modules_input)) {
                $modules_input = json_decode($modules_input, true) ?: [];
            }
            if (!is_array($modules_input)) {
                $modules_input = [];
            }

            // All supported modules in the platform
            $all_supported_modules = [
                'appointments', 'calendar', 'stations', 'packages', 'memberships',
                'adisyon', 'pos', 'finance', 'expenses', 'inventory',
                'staff_commissions', 'marketing', 'reviews', 'loyalty',
                'client_portal', 'checkin', 'invoices',
                'restaurant_floor_plan', 'restaurant_reservations', 'restaurant_experiences'
            ];

            $features_map = [];
            foreach ($all_supported_modules as $mod) {
                // If custom module list sent, use it; otherwise use blueprint defaults
                if (!empty($modules_input)) {
                    $features_map[$mod] = in_array($mod, $modules_input, true) || !empty($modules_input[$mod]);
                } else {
                    $features_map[$mod] = in_array($mod, $bp['enabled_modules'] ?? [], true);
                }
            }

            // 3. Save to settings table
            $settings_to_update = [
                'industry_code' => $industry_code,
                'business_type' => $bp['default_settings']['business_type'] ?? 'wellness',
                'features_enabled_json' => json_encode($features_map),
                'industry_custom_terminology' => json_encode($merged_terminology),
                'slot_interval' => (string) ($bp['default_settings']['slot_interval'] ?? setting('slot_interval', '15')),
                'future_booking_limit' => (string) ($bp['default_settings']['future_booking_limit'] ?? setting('future_booking_limit', '30')),
                'onboarding_completed' => '1',
            ];

            if (!empty($bp['default_settings']['currency_symbol'])) {
                $settings_to_update['currency_symbol'] = $bp['default_settings']['currency_symbol'];
            }

            foreach ($settings_to_update as $name => $val) {
                $exists = $this->db->get_where('settings', ['name' => $name])->row_array();
                if ($exists) {
                    $this->db->update('settings', ['value' => $val], ['name' => $name]);
                } else {
                    $this->db->insert('settings', ['name' => $name, 'value' => $val]);
                }
            }

            // 4. Optionally import sample categories & services if requested
            $import_templates = $this->input->post('import_templates') === '1' || $this->input->post('import_templates') === 'true';
            $template_results = null;
            if ($import_templates) {
                $template_results = $this->blueprint_service->apply_blueprint($industry_code, false);
            }

            // 5. Sync to master database tenants table if in multi-tenant environment
            $tenant = tenant_context();
            if ($tenant && !empty($tenant['id'])) {
                $master = $this->load->database('default', true);
                if ($master && ($master->table_exists($master->dbprefix('tenants')) || $master->table_exists('tenants'))) {
                    $master->where('id', (int) $tenant['id'])->update('tenants', [
                        'category' => $bp['industry']['name'] ?? $industry_code,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            json_response([
                'success' => true,
                'message' => "Sektörünüz başarıyla '{$bp['industry']['name']}' olarak güncellendi! Alanlar ve modüller o sektöre uyarlandı.",
                'industry' => $bp['industry']['name'],
                'industry_code' => $industry_code,
                'template_results' => $template_results,
            ]);
        } catch (Throwable $e) {
            json_response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
