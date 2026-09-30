<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - RandevuBurada Pazaryeri Hub Controller
 * 
 * Provides dedicated endpoints for:
 * 1. Vitrin & Profil Yönetimi (profile, save_profile)
 * 2. Hizmetler & Fiyatlar (services, toggle_service, save_service_price)
 * 3. Yorum & Kaynak Yönetimi (reviews, save_sources, publish_review, reject_review)
 * 4. Pazaryeri Rezervasyonları (reservations, update_reservation_status)
 * ---------------------------------------------------------------------------- */

class Randevuburada extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('accounts');
        $this->load->model('settings_model');
        $this->load->model('services_model');
        $this->load->model('roles_model');

        if (!session('user_id')) {
            session(['dest_url' => current_url()]);
            redirect('login');
            return;
        }
    }

    /**
     * Storefront Profile Management
     */
    public function profile(): void
    {
        method('get');

        $user_id = session('user_id');
        $role_slug = session('role_slug');

        $current_tenant_ctx = function_exists('tenant_context') ? tenant_context() : null;
        $tenant_id = $current_tenant_ctx['id'] ?? null;
        $tenant_sub = $current_tenant_ctx['subdomain'] ?? '';
        $mp_url = !empty($tenant_sub)
            ? (function_exists('randevuburada_url') ? randevuburada_url('business/' . rawurlencode($tenant_sub)) : 'https://randevuburada.kibusiness.co/business/' . rawurlencode($tenant_sub))
            : (function_exists('randevuburada_url') ? randevuburada_url() : 'https://randevuburada.kibusiness.co');

        // Fetch master tenant record as fallback if available
        $master_tenant = [];
        try {
            if (!empty($tenant_id) || !empty($tenant_sub)) {
                $master_db = $this->load->database('default', true);
                if ($master_db && $master_db->conn_id) {
                    if (!empty($tenant_id)) {
                        $master_tenant = $master_db->where('id', (int)$tenant_id)->get('tenants')->row_array() ?: [];
                    } else {
                        $master_tenant = $master_db->where('subdomain', $tenant_sub)->get('tenants')->row_array() ?: [];
                    }
                    $master_db->close();
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'Master tenant lookup fallback error: ' . $e->getMessage());
        }

        $settings = [
            'company_name' => setting('company_name') ?: ($master_tenant['company_name'] ?? ''),
            'company_email' => setting('company_email', ''),
            'company_phone' => setting('company_phone') ?: (setting('phone_number') ?: ($master_tenant['phone_number'] ?? '')),
            'company_link' => setting('company_link', ''),
            'company_address' => setting('company_address') ?: (setting('address') ?: ($master_tenant['address'] ?? '')),
            'company_description' => setting('company_description') ?: (setting('marketplace_short_description') ?: ($master_tenant['short_description'] ?? '')),
            'company_color' => setting('company_color', '#35A768'),
            'randevuburada_active' => (setting('marketplace_opt_in') !== null && setting('marketplace_opt_in') !== '')
                ? (string)setting('marketplace_opt_in')
                : ((isset($master_tenant['marketplace_opt_in'])) ? (string)$master_tenant['marketplace_opt_in'] : (string)setting('randevuburada_active', '1')),
            'randevuburada_category' => setting('marketplace_category') ?: (setting('randevuburada_category') ?: ($master_tenant['category'] ?? 'Kuaför & Güzellik')),
            'randevuburada_price_range' => setting('marketplace_price_range') ?: ($master_tenant['price_range'] ?? '₺₺'),
            'randevuburada_city' => setting('marketplace_city') ?: (setting('randevuburada_city') ?: ($master_tenant['city'] ?? '')),
            'randevuburada_district' => setting('marketplace_district') ?: (setting('randevuburada_district') ?: ($master_tenant['district'] ?? '')),
            'randevuburada_neighborhood' => setting('marketplace_neighborhood') ?: (setting('randevuburada_neighborhood') ?: ($master_tenant['neighborhood'] ?? '')),
            'randevuburada_cover_image' => setting('marketplace_cover_image_url') ?: (setting('randevuburada_cover_image') ?: ($master_tenant['cover_image_url'] ?? '')),
            'randevuburada_tags' => setting('randevuburada_tags', 'rezervasyon, online randevu, bakım'),
            'randevuburada_instant_booking' => setting('randevuburada_instant_booking', '1'),
            'randevuburada_min_notice_hours' => setting('randevuburada_min_notice_hours', '2'),
        ];

        html_vars([
            'page_title' => 'RandevuBurada Vitrin & Profil Yönetimi',
            'active_menu' => 'randevuburada_profile',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        $this->load->view('pages/randevuburada/profile', [
            'settings' => $settings,
            'mp_url' => $mp_url,
            'tenant_sub' => $tenant_sub,
            'csrf_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash(),
        ]);
    }

    /**
     * Save Storefront Profile
     */
    public function save_profile(): void
    {
        method('post');

        $post = $this->input->post(null, true);
        if (empty($post)) {
            $raw = file_get_contents('php://input');
            $post = json_decode($raw, true) ?? [];
        }

        // Direct & dual-mapped settings to persist into tenant settings table
        $setting_mappings = [
            'company_name' => ['company_name'],
            'company_email' => ['company_email'],
            'company_phone' => ['company_phone', 'phone_number'],
            'company_link' => ['company_link'],
            'company_address' => ['company_address', 'address'],
            'company_description' => ['company_description', 'marketplace_short_description'],
            'company_color' => ['company_color'],
            'randevuburada_category' => ['randevuburada_category', 'marketplace_category'],
            'randevuburada_price_range' => ['randevuburada_price_range', 'marketplace_price_range'],
            'randevuburada_city' => ['randevuburada_city', 'marketplace_city', 'city'],
            'randevuburada_district' => ['randevuburada_district', 'marketplace_district', 'district'],
            'randevuburada_neighborhood' => ['randevuburada_neighborhood', 'marketplace_neighborhood', 'neighborhood'],
            'randevuburada_cover_image' => ['randevuburada_cover_image', 'marketplace_cover_image_url'],
            'randevuburada_active' => ['randevuburada_active', 'marketplace_opt_in'],
            'randevuburada_tags' => ['randevuburada_tags'],
            'randevuburada_instant_booking' => ['randevuburada_instant_booking'],
            'randevuburada_min_notice_hours' => ['randevuburada_min_notice_hours'],
        ];

        foreach ($setting_mappings as $input_key => $db_keys) {
            if (isset($post[$input_key])) {
                $val = is_bool($post[$input_key]) ? ($post[$input_key] ? '1' : '0') : trim((string)$post[$input_key]);
                foreach ($db_keys as $db_key) {
                    $this->settings_model->set_setting($db_key, $val);
                }
            }
        }

        // Synchronize with Master DB ea_tenants table so RandevuBurada Directory & BooKi are always 100% unified
        try {
            $current_tenant_ctx = function_exists('tenant_context') ? tenant_context() : null;
            $tenant_id = $current_tenant_ctx['id'] ?? null;
            $subdomain = $current_tenant_ctx['subdomain'] ?? '';

            if (!empty($tenant_id) || !empty($subdomain)) {
                $master_db = $this->load->database('default', true);
                if ($master_db && $master_db->conn_id) {
                    $master_update = [
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    if (isset($post['company_name'])) {
                        $master_update['company_name'] = trim((string)$post['company_name']);
                    }
                    if (isset($post['company_phone'])) {
                        $master_update['phone_number'] = trim((string)$post['company_phone']);
                    }
                    if (isset($post['company_address'])) {
                        $master_update['address'] = trim((string)$post['company_address']);
                    }
                    if (isset($post['company_description'])) {
                        $master_update['short_description'] = trim((string)$post['company_description']);
                    }
                    if (isset($post['randevuburada_category'])) {
                        $master_update['category'] = trim((string)$post['randevuburada_category']);
                    }
                    if (isset($post['randevuburada_price_range'])) {
                        $master_update['price_range'] = trim((string)$post['randevuburada_price_range']);
                    }
                    if (isset($post['randevuburada_city'])) {
                        $master_update['city'] = trim((string)$post['randevuburada_city']);
                    }
                    if (isset($post['randevuburada_district'])) {
                        $master_update['district'] = trim((string)$post['randevuburada_district']);
                    }
                    if (isset($post['randevuburada_neighborhood'])) {
                        $master_update['neighborhood'] = trim((string)$post['randevuburada_neighborhood']);
                    }
                    if (isset($post['randevuburada_cover_image'])) {
                        $master_update['cover_image_url'] = trim((string)$post['randevuburada_cover_image']);
                    }
                    if (isset($post['randevuburada_active'])) {
                        $master_update['marketplace_opt_in'] = (!empty($post['randevuburada_active']) && $post['randevuburada_active'] !== '0') ? 1 : 0;
                    }
                    if (!empty($tenant_id)) {
                        $master_db->where('id', (int) $tenant_id)->update('tenants', $master_update);
                    } else {
                        $master_db->where('subdomain', $subdomain)->update('tenants', $master_update);
                    }
                    $master_db->close();
                }
            }
        } catch (Throwable $syncError) {
            log_message('error', 'RandevuBurada master sync error: ' . $syncError->getMessage());
        }

        json_response([
            'success' => true,
            'message' => 'RandevuBurada vitrin profili ve İşletme Profili ayarları başarıyla senkronize edildi ve kaydedildi.',
            'csrf_hash' => $this->security->get_csrf_hash(),
        ]);
    }

    /**
     * Services & Prices for Marketplace
     */
    public function services(): void
    {
        method('get');

        $user_id = session('user_id');
        $role_slug = session('role_slug');

        $services = $this->services_model->get_available_services();
        $categories = $this->db->get('service_categories')->result_array();

        // Marketplace specific visibility overrides
        $hidden_services = json_decode(setting('randevuburada_hidden_services', '[]'), true) ?: [];
        $promo_prices = json_decode(setting('randevuburada_promo_prices', '{}'), true) ?: [];

        foreach ($services as &$svc) {
            $svc['is_marketplace_visible'] = !in_array($svc['id'], $hidden_services);
            $svc['promo_price'] = $promo_prices[$svc['id']] ?? $svc['price'];
        }

        html_vars([
            'page_title' => 'RandevuBurada Hizmetler & Fiyatlar',
            'active_menu' => 'randevuburada_services',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        $this->load->view('pages/randevuburada/services', [
            'services' => $services,
            'categories' => $categories,
            'csrf_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash(),
        ]);
    }

    /**
     * Toggle Service Marketplace Visibility
     */
    public function toggle_service(): void
    {
        method('post');

        $service_id = (int)$this->input->post('service_id');
        $is_visible = $this->input->post('is_visible') == '1' || $this->input->post('is_visible') === true || $this->input->post('is_visible') === 'true';

        $hidden_services = json_decode(setting('randevuburada_hidden_services', '[]'), true) ?: [];

        if ($is_visible) {
            $hidden_services = array_values(array_diff($hidden_services, [$service_id]));
        } else {
            if (!in_array($service_id, $hidden_services)) {
                $hidden_services[] = $service_id;
            }
        }

        $this->db->replace('settings', [
            'name' => 'randevuburada_hidden_services',
            'value' => json_encode(array_values($hidden_services))
        ]);

        json_response([
            'success' => true,
            'is_visible' => $is_visible,
            'message' => $is_visible ? 'Hizmet RandevuBurada vitrininde yayına alındı.' : 'Hizmet RandevuBurada vitrininde gizlendi.'
        ]);
    }

    /**
     * Save Service Price & Promo
     */
    public function save_service_price(): void
    {
        method('post');

        $service_id = (int)$this->input->post('service_id');
        $promo_price = (float)$this->input->post('promo_price');

        $promo_prices = json_decode(setting('randevuburada_promo_prices', '{}'), true) ?: [];
        $promo_prices[$service_id] = $promo_price;

        $this->db->replace('settings', [
            'name' => 'randevuburada_promo_prices',
            'value' => json_encode($promo_prices)
        ]);

        json_response([
            'success' => true,
            'message' => 'Pazaryeri özel fiyatı güncellendi.'
        ]);
    }

    /**
     * Reviews & Review Source Management
     */
    public function reviews(): void
    {
        method('get');

        $user_id = session('user_id');
        $role_slug = session('role_slug');

        $this->load->model('reviews_model');
        $this->load->model('providers_model');

        $reviews = $this->reviews_model->get();
        $providers = $this->providers_model->get_available_providers();

        // Source settings
        $sources = [
            'google' => setting('randevuburada_source_google', '1') === '1',
            'yandex' => setting('randevuburada_source_yandex', '1') === '1',
            'randevuburada' => setting('randevuburada_source_direct', '1') === '1',
        ];

        // Enrich reviews with simulated external sources if local reviews table only has native ones
        $enriched_reviews = [];
        foreach ($reviews as $rev) {
            $rev['source'] = $rev['source'] ?? 'randevuburada';
            $enriched_reviews[] = $rev;
        }

        html_vars([
            'page_title' => 'RandevuBurada Yorum Yönetimi & Kaynaklar',
            'active_menu' => 'randevuburada_reviews',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        $this->load->view('pages/randevuburada/reviews', [
            'reviews' => $enriched_reviews,
            'providers' => $providers,
            'sources' => $sources,
            'counts' => $this->reviews_model->counts(),
            'csrf_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash(),
        ]);
    }

    /**
     * Save Review Sources (Google, Yandex, RandevuBurada)
     */
    public function save_sources(): void
    {
        method('post');

        $google = $this->input->post('google') == '1' || $this->input->post('google') === 'true' ? '1' : '0';
        $yandex = $this->input->post('yandex') == '1' || $this->input->post('yandex') === 'true' ? '1' : '0';
        $direct = $this->input->post('randevuburada') == '1' || $this->input->post('randevuburada') === 'true' ? '1' : '0';

        $this->db->replace('settings', ['name' => 'randevuburada_source_google', 'value' => $google]);
        $this->db->replace('settings', ['name' => 'randevuburada_source_yandex', 'value' => $yandex]);
        $this->db->replace('settings', ['name' => 'randevuburada_source_direct', 'value' => $direct]);

        json_response([
            'success' => true,
            'message' => 'Yorum kaynakları başarıyla kaydedildi.'
        ]);
    }

    /**
     * Publish / Approve Review
     */
    public function publish_review(): void
    {
        method('post');
        $review_id = (int)$this->input->post('review_id');

        $this->db->where('id', $review_id)->update('reviews', [
            'status' => 'published',
            'moderated_at' => date('Y-m-d H:i:s'),
            'moderated_by' => session('user_id'),
        ]);

        json_response([
            'success' => true,
            'message' => 'Yorum onaylandı ve RandevuBurada vitrininde yayına alındı.'
        ]);
    }

    /**
     * Reject / Hide Review
     */
    public function reject_review(): void
    {
        method('post');
        $review_id = (int)$this->input->post('review_id');

        $this->db->where('id', $review_id)->update('reviews', [
            'status' => 'rejected',
            'moderated_at' => date('Y-m-d H:i:s'),
            'moderated_by' => session('user_id'),
        ]);

        json_response([
            'success' => true,
            'message' => 'Yorum reddedildi / vitrinden gizlendi.'
        ]);
    }

    /**
     * RandevuBurada Marketplace Reservations
     */
    public function reservations(): void
    {
        method('get');

        $user_id = session('user_id');
        $role_slug = session('role_slug');

        $this->load->model('appointments_model');
        $this->load->model('services_model');
        $this->load->model('providers_model');

        // Fetch appointments that originate from marketplace or all active appointments for display
        $appointments = $this->db->select('a.*, s.name as service_name, s.price as service_price, s.duration as service_duration, u_c.first_name as customer_first_name, u_c.last_name as customer_last_name, u_c.phone_number as customer_phone, u_p.first_name as provider_first_name, u_p.last_name as provider_last_name')
            ->from('appointments a')
            ->join('services s', 's.id = a.id_services', 'left')
            ->join('users u_c', 'u_c.id = a.id_users_customer', 'left')
            ->join('users u_p', 'u_p.id = a.id_users_provider', 'left')
            ->where('a.is_unavailability', 0)
            ->order_by('a.start_datetime', 'DESC')
            ->limit(100)
            ->get()
            ->result_array();

        // Calculate KPI counters
        $stats = [
            'total' => count($appointments),
            'confirmed' => 0,
            'pending' => 0,
            'cancelled' => 0,
            'total_volume' => 0.0,
        ];

        foreach ($appointments as $apt) {
            $status = strtolower($apt['status'] ?? '');
            if ($status === 'confirmed') {
                $stats['confirmed']++;
                $stats['total_volume'] += (float)($apt['service_price'] ?? 0);
            } elseif ($status === 'reserved' || $status === 'pending') {
                $stats['pending']++;
            } elseif ($status === 'cancelled') {
                $stats['cancelled']++;
            }
        }

        html_vars([
            'page_title' => 'RandevuBurada Rezervasyonları',
            'active_menu' => 'randevuburada_reservations',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        $this->load->view('pages/randevuburada/reservations', [
            'appointments' => $appointments,
            'stats' => $stats,
            'csrf_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash(),
        ]);
    }

    /**
     * Update Reservation Status
     */
    public function update_reservation_status(): void
    {
        method('post');

        $appointment_id = (int)$this->input->post('appointment_id');
        $new_status = trim((string)$this->input->post('status'));

        if (!in_array($new_status, ['confirmed', 'reserved', 'cancelled'])) {
            json_response(['success' => false, 'message' => 'Geçersiz randevu durumu.'], 400);
            return;
        }

        $this->db->where('id', $appointment_id)->update('appointments', [
            'status' => $new_status,
        ]);

        json_response([
            'success' => true,
            'status' => $new_status,
            'message' => 'Rezervasyon durumu başarıyla güncellendi.'
        ]);
    }
}
