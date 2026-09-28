<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi Unified Catalog Controller.
 *
 * Provides a vertical-aware UI abstraction layer over Services, Categories,
 * Products, Packages, and Memberships without forcing different domain models
 * into an artificial single table.
 */
class Catalog extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('vertical_service');
        $this->load->library('permission_service');
        $this->load->model('services_model');
        $this->load->model('service_categories_model');
        $this->load->model('products_model');
        $this->load->model('packages_model');
        $this->load->model('memberships_model');
        $this->load->model('roles_model');
        $this->load->library('accounts');
    }

    public function index(): void
    {
        session(['dest_url' => site_url('catalog')]);

        $user_id = (int) session('user_id');
        if (!$user_id) {
            redirect('login');
            return;
        }

        if (cannot('view', 'services') && cannot('view', 'products')) {
            abort(403, 'Forbidden: Katalog erişim yetkiniz bulunmuyor.');
        }

        $business_type = $this->vertical_service->current_business_type();
        $family = $this->vertical_service->resolve_family($business_type);
        $terms = $this->vertical_service->get_terminology($business_type);

        // Fetch counts & preview data
        $services = $this->db->get('services')->result_array() ?: [];
        $categories = $this->db->get('service_categories')->result_array() ?: [];
        $products = $this->db->table_exists('products') ? ($this->db->get('products')->result_array() ?: []) : [];
        $packages = $this->db->table_exists('customer_packages') || $this->db->table_exists('packages')
            ? ($this->db->get($this->db->table_exists('customer_packages') ? 'customer_packages' : 'packages')->result_array() ?: [])
            : [];
        $memberships = $this->db->table_exists('membership_plans')
            ? ($this->db->get('membership_plans')->result_array() ?: [])
            : [];

        // Build vertical-specific catalog sections
        $sections = $this->build_vertical_sections($family, $terms, [
            'services' => $services,
            'categories' => $categories,
            'products' => $products,
            'packages' => $packages,
            'memberships' => $memberships,
        ]);

        html_vars([
            'page_title' => $terms['catalog'] ?? 'Katalog',
            'active_menu' => 'services',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $this->load->view('pages/catalog', [
            'family' => $family,
            'business_type' => $business_type,
            'terms' => $terms,
            'sections' => $sections,
            'total_services' => count($services),
            'total_categories' => count($categories),
            'total_products' => count($products),
            'total_packages' => count($packages),
            'total_memberships' => count($memberships),
        ]);
    }

    /**
     * Build catalog sections according to vertical requirements.
     */
    protected function build_vertical_sections(string $family, array $terms, array $data): array
    {
        $sections = [];

        if ($family === 'restaurant_food') {
            // Restaurant: Menü, Menü Grupları, Ürünler, Ekstralar, Paketler
            $sections[] = [
                'key' => 'menu',
                'title' => 'Menü & Lezzetler',
                'description' => 'A la carte menüdeki ana yemekler, başlangıçlar, tatlılar ve içecekler.',
                'icon' => 'fas fa-utensils',
                'count' => count($data['services']),
                'url' => site_url('services'),
                'btn_text' => 'Menüyü Yönet',
            ];
            $sections[] = [
                'key' => 'categories',
                'title' => 'Menü Grupları & Mutfaklar',
                'description' => 'Menü kategorileri (Başlangıçlar, Sıcaklar, İçecekler, Şefin Spesiyalleri).',
                'icon' => 'fas fa-layer-group',
                'count' => count($data['categories']),
                'url' => site_url('service_categories'),
                'btn_text' => 'Grupları Yönet',
            ];
            $sections[] = [
                'key' => 'products',
                'title' => 'İçecek & Perakende Ürünler',
                'description' => 'Şişeli içecekler, gurme perakende ürünler ve ambalajlı lezzetler.',
                'icon' => 'fas fa-wine-bottle',
                'count' => count($data['products']),
                'url' => site_url('products'),
                'btn_text' => 'Ürünleri Yönet',
            ];
            $sections[] = [
                'key' => 'experiences',
                'title' => 'Fiks Menüler & Deneyim Paketleri',
                'description' => 'Tadım menüleri, şef masası ve özel gün rezervasyon paketleri.',
                'icon' => 'fas fa-box-open',
                'count' => count($data['packages']),
                'url' => site_url('packages'),
                'btn_text' => 'Paketleri Yönet',
            ];
        } elseif ($family === 'health_clinical') {
            // Clinic: Muayene / İşlemler, Ürünler, Tedavi Paketleri
            $sections[] = [
                'key' => 'services',
                'title' => $terms['service'] ?? 'Muayene & Tıbbi İşlemler',
                'description' => 'Muayene, tahlil, görüntüleme ve medikal işlem protokolleri.',
                'icon' => 'fas fa-stethoscope',
                'count' => count($data['services']),
                'url' => site_url('services'),
                'btn_text' => 'İşlemleri Yönet',
            ];
            $sections[] = [
                'key' => 'products',
                'title' => 'Medikal Destek & İlaç Ürünleri',
                'description' => 'Kliniğinizde satılan veya reçete edilen destekleyici ürünler.',
                'icon' => 'fas fa-pills',
                'count' => count($data['products']),
                'url' => site_url('products'),
                'btn_text' => 'Ürünleri Yönet',
            ];
            $sections[] = [
                'key' => 'packages',
                'title' => 'Tedavi & Takip Paketleri',
                'description' => 'Çoklu seans tedavi paketleri ve dönemsel sağlık kontrolleri.',
                'icon' => 'fas fa-box-open',
                'count' => count($data['packages']),
                'url' => site_url('packages'),
                'btn_text' => 'Paketleri Yönet',
            ];
        } elseif ($family === 'sports_fitness') {
            // Gym: Dersler, Seanslar, Üyelikler, Paketler, Ürünler
            $sections[] = [
                'key' => 'services',
                'title' => 'Dersler & Branşlar',
                'description' => 'Grup dersleri, reformer pilates, spinning ve stüdyo programları.',
                'icon' => 'fas fa-dumbbell',
                'count' => count($data['services']),
                'url' => site_url('services'),
                'btn_text' => 'Dersleri Yönet',
            ];
            $sections[] = [
                'key' => 'memberships',
                'title' => 'Üyelikler & Abonelik Planları',
                'description' => 'Aylık, 3 aylık ve yıllık sınırsız veya kotalı salon üyelikleri.',
                'icon' => 'fas fa-id-card',
                'count' => count($data['memberships']),
                'url' => site_url('memberships'),
                'btn_text' => 'Üyelikleri Yönet',
            ];
            $sections[] = [
                'key' => 'packages',
                'title' => 'Ders & PT Seans Paketleri',
                'description' => '10lu / 20li seans paketleri ve birebir antrenman kuponları.',
                'icon' => 'fas fa-box-open',
                'count' => count($data['packages']),
                'url' => site_url('packages'),
                'btn_text' => 'Paketleri Yönet',
            ];
            $sections[] = [
                'key' => 'products',
                'title' => 'Sporcu Besinleri & Ekipmanlar',
                'description' => 'Protein tozları, vitaminler, sporcu kıyafetleri ve aksesuarlar.',
                'icon' => 'fas fa-running',
                'count' => count($data['products']),
                'url' => site_url('products'),
                'btn_text' => 'Ürünleri Yönet',
            ];
        } else {
            // Beauty & Default: Hizmetler, Ürünler, Paketler, Üyelikler
            $sections[] = [
                'key' => 'services',
                'title' => $terms['service'] ?? 'Hizmetler & Bakım Seansları',
                'description' => 'Cilt bakımı, lazer epilasyon, kalıcı makyaj ve uzman seansları.',
                'icon' => 'fas fa-magic',
                'count' => count($data['services']),
                'url' => site_url('services'),
                'btn_text' => 'Hizmetleri Yönet',
            ];
            $sections[] = [
                'key' => 'products',
                'title' => 'Kozmetik & Bakım Ürünleri',
                'description' => 'Ev devam ürünleri, serumlar, kremler ve bakım kitleri.',
                'icon' => 'fas fa-spa',
                'count' => count($data['products']),
                'url' => site_url('products'),
                'btn_text' => 'Ürünleri Yönet',
            ];
            $sections[] = [
                'key' => 'packages',
                'title' => 'Seans Paketleri',
                'description' => 'Danışanlara özel 6lı / 8li indirimli seans paketleri.',
                'icon' => 'fas fa-box-open',
                'count' => count($data['packages']),
                'url' => site_url('packages'),
                'btn_text' => 'Paketleri Yönet',
            ];
            $sections[] = [
                'key' => 'memberships',
                'title' => 'Kulüp & VIP Üyelikler',
                'description' => 'Düzenli danışanlar için aylık abonelik ve avantaj planları.',
                'icon' => 'fas fa-id-card',
                'count' => count($data['memberships']),
                'url' => site_url('memberships'),
                'btn_text' => 'Üyelikleri Yönet',
            ];
        }

        return $sections;
    }
}
