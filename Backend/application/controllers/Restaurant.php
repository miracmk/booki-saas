<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Restaurant Operations Controller
 * Floor Plan, Kroki Editor, Smart Seating, Patronage (Müdavimlik), QR Menu, KDS & POS
 * ---------------------------------------------------------------------------- */

class Restaurant extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('restaurant_model');
        $this->load->model('customers_model');
        $this->load->model('adisyons_model');
        $this->load->model('loyalty_points_model');
        $this->load->model('customer_memberships_model');
        $this->load->model('memberships_model');
        $this->load->model('roles_model');
        $this->load->library('accounts');
    }

    /* -------------------------------------------------------------------------
     * 1. INTERACTIVE FLOOR PLAN & KROKI OPERATIONS CENTER
     * ------------------------------------------------------------------------- */

    /**
     * Interactive Floor Plan & Kroki Designer (Yönetim Paneli).
     */
    public function index(): void
    {
        session(['dest_url' => site_url('restaurant')]);
        $user_id = session('user_id');
        if (!$user_id) {
            redirect('login');
            return;
        }

        if (session('role_slug') === DB_SLUG_CUSTOMER) {
            redirect('customer_portal');
            return;
        }

        $tables = $this->restaurant_model->get_tables_with_status();
        $layout_elements = $this->restaurant_model->get_layout_elements();
        $today_reservations = $this->restaurant_model->get_reservations(date('Y-m-d'));
        $waitlist = $this->db->table_exists('waitlist') ? $this->db->get('waitlist', 20)->result_array() : [];
        $experiences = $this->restaurant_model->get_experiences();
        $staff_members = $this->db->get_where('users', ['id_roles' => 2])->result_array();
        $patrons = $this->restaurant_model->get_all_patron_profiles();

        html_vars([
            'page_title' => 'Restoran Masa Planı & Kroki Yönetimi',
            'active_menu' => 'restaurant_floor_plan',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $view_data = [
            'active_menu' => 'restaurant_floor_plan',
            'tables' => $tables,
            'layout_elements' => $layout_elements,
            'reservations' => $today_reservations,
            'waitlist' => $waitlist,
            'experiences' => $experiences,
            'staff_members' => $staff_members,
            'patrons' => $patrons,
        ];

        $this->load->view('pages/restaurant_floor_plan', $view_data);
    }

    /**
     * Masa Müdavimleri & 360° Misafir Zekası Paneli.
     */
    public function guest_patronage(): void
    {
        $this->ensure_authenticated();
        $user_id = session('user_id');

        $tier = $this->input->get('tier');
        $search = $this->input->get('search');
        $patrons = $this->restaurant_model->get_all_patron_profiles($tier, $search);
        $tables = $this->restaurant_model->get_tables_with_status();
        $staff_members = $this->db->get_where('users', ['id_roles' => 2])->result_array();

        html_vars([
            'page_title' => 'Masa Müdavimleri & 360° Misafir Zekası',
            'active_menu' => 'restaurant_patronage',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $view_data = [
            'patrons' => $patrons,
            'tables' => $tables,
            'staff_members' => $staff_members,
            'current_tier' => $tier,
            'current_search' => $search,
        ];

        $this->load->view('pages/restaurant_guest_patronage', $view_data);
    }

    /**
     * Müşteriye Açık İnteraktif Kroki / Rezervasyon Sayfası.
     */
    public function kroki_booking(): void
    {
        $tables = $this->restaurant_model->get_tables_with_status();
        $layout_elements = $this->restaurant_model->get_layout_elements();
        $experiences = $this->restaurant_model->get_experiences();
        $company_name = setting('company_name') ?: 'BooKi Gourmet';

        // Check customer session if logged in
        $customer = null;
        $dining_profile = null;
        if (session('user_id') && session('role_slug') === DB_SLUG_CUSTOMER) {
            $customer = $this->customers_model->find((int) session('user_id'));
            if ($customer) {
                $dining_profile = $this->restaurant_model->get_customer_dining_profile((int) $customer['id']);
            }
        }

        $view_data = [
            'tables' => $tables,
            'layout_elements' => $layout_elements,
            'experiences' => $experiences,
            'company_name' => $company_name,
            'customer' => $customer,
            'dining_profile' => $dining_profile,
        ];

        $this->load->view('pages/restaurant_kroki_booking', $view_data);
    }

    /**
     * Restaurant Reservations Management.
     */
    public function reservations(): void
    {
        session(['dest_url' => site_url('restaurant/reservations')]);
        $user_id = session('user_id');
        if (!$user_id) {
            redirect('login');
            return;
        }

        $date = $this->input->get('date') ?: date('Y-m-d');
        $status = $this->input->get('status');

        $reservations = $this->restaurant_model->get_reservations($date, $status);
        $tables = $this->restaurant_model->get_tables_with_status();
        $experiences = $this->restaurant_model->get_experiences();

        html_vars([
            'page_title' => 'Masa Rezervasyonları',
            'active_menu' => 'restaurant_reservations',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $view_data = [
            'active_menu' => 'restaurant_reservations',
            'selected_date' => $date,
            'status' => $status,
            'reservations' => $reservations,
            'tables' => $tables,
            'experiences' => $experiences,
            'customers' => $this->customers_model->get(),
            'staff_members' => $this->db->get_where('users', ['id_roles' => 2])->result_array(),
        ];

        $this->load->view('pages/restaurant_reservations', $view_data);
    }

    /**
     * Save or update restaurant reservation.
     */
    public function save_reservation(): void
    {
        $payload = $this->get_request_payload();

        $customer_id = !empty($payload['id_users_customer']) ? (int) $payload['id_users_customer'] : null;
        $phone = $payload['guest_phone'] ?? ($payload['phone_number'] ?? null);
        $firstName = $payload['guest_first_name'] ?? ($payload['first_name'] ?? 'Misafir');
        $lastName = $payload['guest_last_name'] ?? ($payload['last_name'] ?? '');
        $email = $payload['guest_email'] ?? ($payload['email'] ?? null);

        if (!$customer_id && !empty($phone)) {
            $lookup = $this->customers_model->search($phone, 1);
            if (!empty($lookup)) {
                $customer_id = (int) $lookup[0]['id'];
            } else {
                $clean_digits = preg_replace('/[^0-9]/', '', $phone);
                $existing = $this->db->get_where('users', ['phone_number' => $clean_digits])->row_array();
                if ($existing) {
                    $customer_id = (int) $existing['id'];
                } else {
                    $customer_data = [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'phone_number' => $phone,
                        'email' => $email,
                    ];
                    $customer_id = $this->customers_model->save($customer_data);
                }
            }
        }

        $data = [
            'id' => !empty($payload['id']) ? (int) $payload['id'] : null,
            'id_restaurant_tables' => !empty($payload['id_restaurant_tables']) ? (int) $payload['id_restaurant_tables'] : null,
            'id_users_customer' => $customer_id,
            'reservation_datetime' => $payload['reservation_datetime'] ?? date('Y-m-d H:i:s'),
            'party_size' => (int) ($payload['party_size'] ?? 2),
            'status' => $payload['status'] ?? 'confirmed',
            'special_requests' => $payload['special_requests'] ?? ($payload['notes'] ?? null),
            'notes' => $payload['notes'] ?? null,
            'is_customer_selected_table' => !empty($payload['is_customer_selected_table']) ? 1 : 0,
            'is_patron_priority' => !empty($payload['is_patron_priority']) ? 1 : 0,
        ];

        try {
            $id = $this->restaurant_model->save_reservation($data);

            // If table assigned, mark table reserved
            if (!empty($data['id_restaurant_tables']) && $data['status'] === 'confirmed') {
                $this->db->update('restaurant_tables', [
                    'status' => 'reserved',
                    'current_id_reservations' => $id,
                ], ['id' => $data['id_restaurant_tables']]);
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'id' => $id,
                    'customer_id' => $customer_id,
                    'message' => 'Rezervasyon başarıyla kaydedildi.',
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Update reservation status (confirmed, seated, cancelled, no_show).
     */
    public function set_reservation_status(): void
    {
        $this->ensure_authenticated();
        $id = (int) $this->input->post('id_reservations');
        $status = $this->input->post('status');

        try {
            $this->restaurant_model->update_reservation_status($id, $status);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success']));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Update table coordinates via AJAX drag-and-drop.
     */
    public function save_layout(): void
    {
        $this->ensure_authenticated();
        $table_id = (int) $this->input->post('id');
        $pos_x = (int) $this->input->post('pos_x');
        $pos_y = (int) $this->input->post('pos_y');
        $width = $this->input->post('width') ? (int) $this->input->post('width') : null;
        $height = $this->input->post('height') ? (int) $this->input->post('height') : null;

        try {
            $this->restaurant_model->update_table_layout($table_id, $pos_x, $pos_y, $width, $height);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success']));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Update table kroki properties (shape, rotation, VIP flag, min spend, server).
     */
    public function save_table_kroki(): void
    {
        $this->ensure_authenticated();
        $payload = $this->get_request_payload();

        $table_id = (int) ($payload['id'] ?? 0);
        if (!$table_id) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => 'Masa ID gereklidir.']));
            return;
        }

        try {
            $this->restaurant_model->update_table_kroki($table_id, $payload);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success']));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Save or update architectural layout element (wall, window, door, bar etc.).
     */
    public function save_layout_element(): void
    {
        $this->ensure_authenticated();
        $payload = $this->get_request_payload();

        try {
            $id = $this->restaurant_model->save_layout_element($payload);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'id' => $id]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Delete architectural layout element.
     */
    public function delete_layout_element(): void
    {
        $this->ensure_authenticated();
        $id = (int) $this->input->post('id');
        $this->restaurant_model->delete_layout_element($id);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['status' => 'success']));
    }

    /**
     * Combine two tables (Table Pushing).
     */
    public function combine_tables(): void
    {
        $this->ensure_authenticated();
        $master_id = (int) $this->input->post('master_id');
        $slave_id = (int) $this->input->post('slave_id');

        try {
            $this->restaurant_model->combine_tables($master_id, $slave_id);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'message' => 'Masalar başarıyla birleştirildi.']));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Split combined tables.
     */
    public function split_tables(): void
    {
        $this->ensure_authenticated();
        $table_id = (int) $this->input->post('table_id');

        try {
            $this->restaurant_model->split_tables($table_id);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'message' => 'Masa birleştirmesi ayrıldı.']));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Change table status.
     */
    public function set_table_status(): void
    {
        $this->ensure_authenticated();
        $table_id = (int) $this->input->post('id_tables');
        $status = $this->input->post('status');
        $server_id = $this->input->post('id_users_server') ? (int) $this->input->post('id_users_server') : null;

        try {
            $this->restaurant_model->update_table_status($table_id, $status, $server_id);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success']));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Save table.
     */
    public function save_table(): void
    {
        $this->ensure_authenticated();
        $data = [
            'id' => $this->input->post('id') ? (int) $this->input->post('id') : null,
            'table_number' => $this->input->post('table_number'),
            'name' => $this->input->post('name'),
            'capacity' => (int) ($this->input->post('capacity') ?: 4),
            'section' => $this->input->post('section') ?: 'Ana Salon',
            'shape' => $this->input->post('shape') ?: 'rectangle',
            'rotation' => (int) ($this->input->post('rotation') ?: 0),
            'is_vip_only' => $this->input->post('is_vip_only') ? 1 : 0,
            'min_spend' => (float) ($this->input->post('min_spend') ?: 0.00),
            'pos_x' => (int) ($this->input->post('pos_x') ?: 100),
            'pos_y' => (int) ($this->input->post('pos_y') ?: 100),
            'width' => (int) ($this->input->post('width') ?: 110),
            'height' => (int) ($this->input->post('height') ?: 85),
            'id_users_server' => $this->input->post('id_users_server') ? (int) $this->input->post('id_users_server') : null,
        ];

        try {
            $table_id = $this->restaurant_model->save_table($data);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'id' => $table_id]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Delete table.
     */
    public function delete_table(): void
    {
        $this->ensure_authenticated();
        $table_id = (int) $this->input->post('id');
        $this->restaurant_model->delete_table($table_id);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['status' => 'success']));
    }

    /* -------------------------------------------------------------------------
     * 2. QR DIGITAL MENU & SELF-ORDERING (Masa QR Menüsü)
     * ------------------------------------------------------------------------- */

    /**
     * Customer QR Digital Menu & Instant Self-Order.
     */
    public function menu(?string $table_token = null): void
    {
        $table = null;
        if (!empty($table_token)) {
            $table = $this->restaurant_model->get_table_by_token($table_token);
        }

        if ($table && empty($table['qr_token'])) {
            $table['qr_token'] = $this->restaurant_model->ensure_table_qr_token((int) $table['id']);
        }

        $categories = $this->restaurant_model->get_menu_categories();
        $items = $this->restaurant_model->get_menu_items();

        // Customer loyalty/membership info if logged in
        $customer = null;
        $active_membership = null;
        if (session('user_id') && session('role_slug') === DB_SLUG_CUSTOMER) {
            $customer = $this->customers_model->find((int) session('user_id'));
            if ($customer) {
                $customer['loyalty_balance'] = $this->loyalty_points_model->get_balance((int) $customer['id']);
                $memberships = $this->customer_memberships_model->get_for_customer((int) $customer['id']);
                if (!empty($memberships)) {
                    $active_membership = $memberships[0];
                }
            }
        }

        // Existing table experience if already dining
        $table_experience = null;
        if ($table && !empty($table['current_id_adisyons'])) {
            $table_experience = $this->restaurant_model->get_table_live_experience((int) $table['id']);
        }

        $company_name = setting('company_name') ?: 'BooKi Gourmet';
        $qr_settings = $this->restaurant_model->get_qr_settings();

        $view_data = [
            'table' => $table,
            'table_token' => $table_token,
            'categories' => $categories,
            'items' => $items,
            'customer' => $customer,
            'membership' => $active_membership,
            'table_experience' => $table_experience,
            'company_name' => $company_name,
            'qr_settings' => $qr_settings,
        ];

        $this->load->view('pages/restaurant_qr_menu', $view_data);
    }

    /* -------------------------------------------------------------------------
     * 3. CUSTOMER SCREEN / LIVE TABLE PORTAL (Canlı Sipariş & Deneyim Portalı)
     * ------------------------------------------------------------------------- */
    public function customer_screen(?string $table_token = null): void
    {
        $table = null;
        if (!empty($table_token)) {
            $table = $this->restaurant_model->get_table_by_token($table_token);
        }

        if (!$table) {
            $all_tables = $this->restaurant_model->get_tables_with_status();
            $table = !empty($all_tables) ? $all_tables[0] : null;
        }

        $experience = null;
        if ($table) {
            $experience = $this->restaurant_model->get_table_live_experience((int) $table['id']);
        }

        $company_name = setting('company_name') ?: 'BooKi Gourmet';

        $view_data = [
            'table' => $table,
            'table_token' => $table_token,
            'experience' => $experience,
            'company_name' => $company_name,
        ];

        $this->load->view('pages/restaurant_customer_screen', $view_data);
    }

    /* -------------------------------------------------------------------------
     * 4. KITCHEN DISPLAY SYSTEM (KDS - Canlı Mutfak & Bar Ekranı)
     * ------------------------------------------------------------------------- */
    public function kitchen_screen(): void
    {
        $this->ensure_authenticated();
        $user_id = session('user_id');

        $station = $this->input->get('station') ?: 'all';
        $orders = $this->restaurant_model->get_active_kitchen_orders($station);

        html_vars([
            'page_title' => 'KDS — Mutfak & Bar Ekranı',
            'active_menu' => 'restaurant_kds',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $view_data = [
            'station' => $station,
            'orders' => $orders,
        ];

        $this->output->set_content_type('text/html; charset=UTF-8');
        $this->load->view('pages/restaurant_kitchen_screen', $view_data);
    }

    /* -------------------------------------------------------------------------
     * 5. REGISTER SCREEN (Restoran Kasa / POS Ekranı)
     * ------------------------------------------------------------------------- */
    public function register_screen(): void
    {
        $this->ensure_authenticated();
        $user_id = session('user_id');

        $tables = $this->restaurant_model->get_tables_with_status();
        $categories = $this->restaurant_model->get_menu_categories();
        $menu_items = $this->restaurant_model->get_menu_items();
        $customers = $this->customers_model->get();

        html_vars([
            'page_title' => 'Restoran Kasa & Satış Terminali (POS)',
            'active_menu' => 'restaurant_register',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $view_data = [
            'tables' => $tables,
            'categories' => $categories,
            'menu_items' => $menu_items,
            'customers' => $customers,
        ];

        $this->output->set_content_type('text/html; charset=UTF-8');
        $this->load->view('pages/restaurant_register_screen', $view_data);
    }

    /* -------------------------------------------------------------------------
     * 6. WAITRESS SCREEN (Garson El Terminali Ekranı)
     * ------------------------------------------------------------------------- */
    public function waitress_screen(): void
    {
        $this->ensure_authenticated();
        $user_id = session('user_id');

        $tables = $this->restaurant_model->get_tables_with_status();
        $categories = $this->restaurant_model->get_menu_categories();
        $menu_items = $this->restaurant_model->get_menu_items();
        $active_calls = $this->restaurant_model->get_active_table_calls();

        html_vars([
            'page_title' => 'Garson El Terminali',
            'active_menu' => 'restaurant_waitress',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $view_data = [
            'tables' => $tables,
            'categories' => $categories,
            'menu_items' => $menu_items,
            'active_calls' => $active_calls,
        ];

        $this->output->set_content_type('text/html; charset=UTF-8');
        $this->load->view('pages/restaurant_waitress_screen', $view_data);
    }

    /* -------------------------------------------------------------------------
     * 7. PRINTABLE QR TABLE STANDS / TENTS (Yazdırılabilir Masa QR Kodları)
     * ------------------------------------------------------------------------- */
    public function print_qr_stands(): void
    {
        $this->ensure_authenticated();
        $tables = $this->restaurant_model->get_tables_with_status();

        foreach ($tables as &$t) {
            if (empty($t['qr_token'])) {
                $t['qr_token'] = $this->restaurant_model->ensure_table_qr_token((int) $t['id']);
            }
        }
        unset($t);

        $view_data = [
            'tables' => $tables,
            'company_name' => setting('company_name') ?: 'BooKi Restaurant',
        ];

        $this->load->view('pages/restaurant_print_qr_stands', $view_data);
    }

    /* -------------------------------------------------------------------------
     * 7.1. QR MENU & THEME CUSTOMIZER STUDIO (Yönetim Paneli)
     * ------------------------------------------------------------------------- */
    public function qr_menu_manager(): void
    {
        $this->ensure_authenticated();
        $user_id = session('user_id');

        $qr_settings = $this->restaurant_model->get_qr_settings();
        $categories = $this->restaurant_model->get_menu_categories();
        $menu_items = $this->restaurant_model->get_menu_items();
        $stations = $this->restaurant_model->get_kitchen_bar_stations();
        $tables = $this->restaurant_model->get_tables_with_status();

        html_vars([
            'page_title' => 'QR Menü Tasarım & Tema Stüdyosu',
            'active_menu' => 'restaurant_qr_menu',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $view_data = [
            'active_menu' => 'restaurant_qr_menu',
            'qr_settings' => $qr_settings,
            'categories' => $categories,
            'menu_items' => $menu_items,
            'stations' => $stations,
            'tables' => $tables,
            'company_name' => setting('company_name') ?: 'BooKi Restoran',
        ];

        $this->load->view('pages/restaurant_qr_menu_manager', $view_data);
    }

    /**
     * AJAX: Save QR Menu customizer settings.
     */
    public function api_save_qr_settings(): void
    {
        $this->ensure_authenticated();
        $payload = $this->get_request_payload();

        $success = $this->restaurant_model->save_qr_settings($payload);
        if ($success) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'message' => 'QR menü tasarım ve çalışma ayarları başarıyla güncellendi.',
                    'settings' => $this->restaurant_model->get_qr_settings(),
                ]));
        } else {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Ayarlar kaydedilemedi.']));
        }
    }

    /**
     * AJAX: Quick inline update of menu item (QR visibility, badge, station, price).
     */
    public function api_update_menu_item_quick(): void
    {
        $this->ensure_authenticated();
        $payload = $this->get_request_payload();
        $item_id = (int) ($payload['id'] ?? 0);

        if (!$item_id) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Ürün ID bulunamadı.']));
            return;
        }

        $success = $this->restaurant_model->update_menu_item_quick($item_id, $payload);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => $success ? 'success' : 'error',
                'message' => $success ? 'Ürün bilgisi güncellendi.' : 'Güncelleme başarısız.',
            ]));
    }

    /* -------------------------------------------------------------------------
     * 7.2. STAFF STATIONS & MULTI-STATION BRIGADE DE CUISINE
     * ------------------------------------------------------------------------- */
    public function staff_stations(): void
    {
        $this->ensure_authenticated();
        $user_id = session('user_id');

        $shift_date = $this->input->get('shift_date') ?: date('Y-m-d');
        $stations = $this->restaurant_model->get_kitchen_bar_stations();
        $staff_users = $this->restaurant_model->get_staff_users();
        $daily_assignments = $this->restaurant_model->get_daily_staff_stations($shift_date);

        html_vars([
            'page_title' => 'Mutfak, Bar & Salon Personel İstasyon Yönetimi',
            'active_menu' => 'restaurant_staff_stations',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $view_data = [
            'active_menu' => 'restaurant_staff_stations',
            'stations' => $stations,
            'staff_users' => $staff_users,
            'daily_assignments' => $daily_assignments,
            'shift_date' => $shift_date,
        ];

        $this->load->view('pages/restaurant_staff_stations', $view_data);
    }

    /**
     * AJAX: Save dynamic multi-station assignments for a staff member.
     */
    public function api_save_staff_stations(): void
    {
        $this->ensure_authenticated();
        $payload = $this->get_request_payload();
        $user_id = (int) ($payload['user_id'] ?? 0);
        $station_codes = is_array($payload['stations'] ?? null) ? $payload['stations'] : [];
        $role_title = !empty($payload['role_title']) ? trim((string) $payload['role_title']) : null;
        $shift_date = !empty($payload['shift_date']) ? trim((string) $payload['shift_date']) : date('Y-m-d');

        if (!$user_id) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Personel seçilmedi.']));
            return;
        }

        $success = $this->restaurant_model->save_staff_station_assignments($user_id, $station_codes, $role_title, $shift_date);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => $success ? 'success' : 'error',
                'message' => $success ? 'İstasyon görevlendirmeleri başarıyla kaydedildi.' : 'Kayıt başarısız oldu.',
            ]));
    }

    /**
     * AJAX: Update table duration mode and session minutes.
     */
    public function api_set_table_duration(): void
    {
        $this->ensure_authenticated();
        $payload = $this->get_request_payload();
        $table_id = (int) ($payload['table_id'] ?? 0);
        $mode = $payload['duration_mode'] ?? 'open_ended';
        $duration_mins = (int) ($payload['session_duration_minutes'] ?? 90);

        if (!$table_id) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Masa ID geçersiz.']));
            return;
        }

        $success = $this->restaurant_model->update_table_duration_mode($table_id, $mode, $duration_mins);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => $success ? 'success' : 'error',
                'message' => $success ? 'Masa süre ve oturum modu güncellendi.' : 'Güncelleme başarısız.',
            ]));
    }

    /* -------------------------------------------------------------------------
     * AJAX APIS: SELF-ORDER, CALLS, KDS, SMART SEATING & REGISTER
     * ------------------------------------------------------------------------- */

    /**
     * API: Get menu JSON.
     */
    public function api_menu(): void
    {
        $category_id = $this->input->get('category_id') ? (int) $this->input->get('category_id') : null;
        $dietary = $this->input->get('dietary') ?: null;

        $items = $this->restaurant_model->get_menu_items($category_id, $dietary);
        $categories = $this->restaurant_model->get_menu_categories();

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'categories' => $categories,
                'items' => $items,
            ]));
    }

    /**
     * API: Submit self-order from table or staff handheld.
     */
    /**
     * Resolve table either by numeric table_id or by QR token.
     */
    private function resolve_table($identifier): ?array
    {
        if (empty($identifier)) {
            return null;
        }
        if (is_numeric($identifier) && (int) $identifier > 0) {
            $table = $this->db->get_where('restaurant_tables', ['id' => (int) $identifier])->row_array();
            if ($table) {
                return $table;
            }
        }
        return $this->restaurant_model->get_table_by_token((string) $identifier);
    }

    /**
     * API: Submit self-order or staff order for table.
     */
    public function api_self_order(): void
    {
        $raw = file_get_contents('php://input');
        $payload = !empty($raw) ? json_decode($raw, true) : $this->input->post();

        $table_ident = $payload['table_id'] ?? $payload['table_token'] ?? null;
        $cart_items = $payload['items'] ?? [];
        $phone = $payload['customer_phone'] ?? null;
        $name = $payload['customer_name'] ?? null;

        if (!$table_ident) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => 'Masa belirtilmedi.']));
            return;
        }

        $table = $this->resolve_table($table_ident);
        if (!$table) {
            $this->output->set_status_header(404)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => 'Masa bulunamadı.']));
            return;
        }

        if (is_string($cart_items)) {
            $cart_items = json_decode($cart_items, true) ?: [];
        }

        try {
            $result = $this->restaurant_model->submit_table_order(
                (int) $table['id'],
                $cart_items,
                $phone,
                $name,
                session('user_id') ?: null
            );

            $redeem_points = (int) ($payload['redeem_points'] ?? 0);
            if ($redeem_points > 0 && !empty($result['adisyon_id']) && !empty($result['customer_id'])) {
                try {
                    $redeem_res = $this->restaurant_model->redeem_loyalty_points(
                        (int) $result['adisyon_id'],
                        (int) $result['customer_id'],
                        $redeem_points
                    );
                    $result['redeemed_points'] = $redeem_res['points_redeemed'];
                    $result['loyalty_discount'] = $redeem_res['discount_applied'];
                    $result['total_amount'] = $redeem_res['new_total'];
                } catch (Throwable $e) {
                    $result['loyalty_redeem_error'] = $e->getMessage();
                }
            }

            $result['success'] = true;

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($result));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'success' => false, 'message' => $e->getMessage()]));
        }
    }

    /**
     * API: Trigger waiter or bill request from table.
     */
    public function api_call_waiter(): void
    {
        $raw = file_get_contents('php://input');
        $payload = !empty($raw) ? json_decode($raw, true) : $this->input->post();

        $table_ident = $payload['table_id'] ?? $payload['table_token'] ?? null;
        $call_type = $payload['call_type'] ?? 'waiter';
        $note = $payload['note'] ?? null;
        $payment_pref = $payload['payment_pref'] ?? null;

        $table = $this->resolve_table($table_ident);
        if (!$table) {
            $this->output->set_status_header(404)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => 'Masa bulunamadı.']));
            return;
        }

        try {
            $call_id = $this->restaurant_model->call_waiter((int) $table['id'], $call_type, $note, $payment_pref);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'call_id' => $call_id,
                    'message' => $call_type === 'bill' ? 'Hesap talebiniz garsona iletildi.' : 'Garson çağrınız personele iletildi.',
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * API: Resolve waiter call.
     */
    public function api_resolve_call(): void
    {
        $this->ensure_authenticated();
        $raw = file_get_contents('php://input');
        $payload = !empty($raw) ? json_decode($raw, true) : $this->input->post();

        $table_id = (int) ($payload['table_id'] ?? 0);
        if (!$table_id) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => 'Masa ID zorunludur.']));
            return;
        }

        $this->restaurant_model->resolve_table_calls($table_id);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['status' => 'success']));
    }

    /**
     * API: Live table experience data.
     */
    public function api_table_experience(): void
    {
        $table_ident = $this->input->get('table_id') ?: $this->input->get('table_token');
        $table = $this->resolve_table($table_ident);

        if (!$table) {
            $this->output->set_status_header(404)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => 'Masa bulunamadı.']));
            return;
        }

        try {
            $experience = $this->restaurant_model->get_table_live_experience((int) $table['id']);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'data' => $experience,
                ]));
        } catch (Throwable $e) {
            $this->output->set_status_header(500)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * API: Validate & calculate coupon discount (TastyIgniter Coupon Engine).
     */
    public function api_validate_coupon(): void
    {
        $payload = $this->get_request_payload();
        $code = trim((string) ($payload['code'] ?? ''));
        $total = (float) ($payload['order_total'] ?? 0.00);

        if (empty($code)) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode([
                'status' => 'error',
                'message' => 'Kupon kodu girilmelidir.',
            ]));
            return;
        }

        $result = $this->restaurant_model->validate_coupon($code, $total);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => $result['valid'] ? 'success' : 'error',
                'data' => $result,
            ]));
    }

    /**
     * API: Kitchen & Waiter Intercom / Chat (NutrixPOS).
     */
    public function api_kitchen_chats(): void
    {
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $payload = $this->get_request_payload();
            $message = trim((string) ($payload['message'] ?? ''));
            if (empty($message)) {
                $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Mesaj içeriği boş olamaz.',
                ]));
                return;
            }

            $user_display_name = $this->session->userdata('user_display_name') ?: ($payload['sender_name'] ?? 'Mutfak');
            $user_id = $this->session->userdata('user_id');

            $chat_id = $this->restaurant_model->send_kitchen_chat([
                'sender_id' => $user_id,
                'sender_name' => $user_display_name,
                'sender_role' => $payload['sender_role'] ?? 'kitchen',
                'target_role' => $payload['target_role'] ?? 'all',
                'message' => $message,
                'urgency' => $payload['urgency'] ?? 'normal',
                'table_id' => !empty($payload['table_id']) ? (int) $payload['table_id'] : null,
            ]);

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'chat_id' => $chat_id,
                ]));
            return;
        }

        // GET recent chats
        $chats = $this->restaurant_model->get_recent_kitchen_chats(50);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'data' => $chats,
            ]));
    }

    /**
     * API: Menu Item Modifiers / Options (TastyIgniter).
     */
    public function api_menu_options(): void
    {
        $item_id = (int) $this->input->get('item_id');
        $groups = $this->restaurant_model->get_menu_option_groups($item_id);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'data' => $groups,
            ]));
    }

    /**
     * API: Out-of-Stock Override (TastyIgniter & NutrixPOS).
     */
    public function api_set_out_of_stock(): void
    {
        $this->ensure_authenticated();
        $payload = $this->get_request_payload();
        $item_id = (int) ($payload['item_id'] ?? 0);
        $type = $payload['type'] ?? 'indefinitely';
        $until = $payload['until'] ?? null;

        if (!$item_id) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode([
                'status' => 'error',
                'message' => 'Ürün ID zorunludur.',
            ]));
            return;
        }

        $this->restaurant_model->set_out_of_stock($item_id, $type, $until);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'message' => 'Ürün tükendi olarak işaretlendi.',
            ]));
    }

    public function api_clear_out_of_stock(): void
    {
        $this->ensure_authenticated();
        $payload = $this->get_request_payload();
        $item_id = (int) ($payload['item_id'] ?? 0);

        if (!$item_id) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode([
                'status' => 'error',
                'message' => 'Ürün ID zorunludur.',
            ]));
            return;
        }

        $this->restaurant_model->clear_out_of_stock($item_id);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'message' => 'Ürün tekrar satışa açıldı.',
            ]));
    }

    /**
     * API: Food Cost & Margin Analytics (NutrixPOS).
     */
    public function api_food_cost(): void
    {
        $this->ensure_authenticated();
        $item_id = (int) $this->input->get('item_id');
        if (!$item_id) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode([
                'status' => 'error',
                'message' => 'Ürün ID zorunludur.',
            ]));
            return;
        }

        $cost_data = $this->restaurant_model->get_item_food_cost($item_id);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'data' => $cost_data,
            ]));
    }

    /**
     * API: Record Waste / Disposal (NutrixPOS).
     */
    public function api_record_disposal(): void
    {
        $this->ensure_authenticated();
        $payload = $this->get_request_payload();

        if (empty($payload['item_name']) || empty($payload['reason'])) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode([
                'status' => 'error',
                'message' => 'Ürün adı ve zayi nedeni zorunludur.',
            ]));
            return;
        }

        $user_id = $this->session->userdata('user_id');
        $id = $this->restaurant_model->record_disposal([
            'disposal_type' => $payload['disposal_type'] ?? 'dish',
            'item_id' => (int) ($payload['item_id'] ?? 0),
            'item_name' => $payload['item_name'],
            'quantity' => (float) ($payload['quantity'] ?? 1.0),
            'cost_loss' => (float) ($payload['cost_loss'] ?? 0.0),
            'reason' => $payload['reason'],
            'reported_by' => $user_id,
        ]);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'disposal_id' => $id,
                'message' => 'Fire / zayi kaydı başarıyla oluşturuldu.',
            ]));
    }

    /**
     * API: Combo Table Management (TastyIgniter).
     */
    public function api_combo_table(): void
    {
        $this->ensure_authenticated();
        $payload = $this->get_request_payload();
        $action = $payload['action'] ?? 'create';

        try {
            if ($action === 'create') {
                $table_ids = (array) ($payload['table_ids'] ?? []);
                $name = $payload['name'] ?? null;
                $combo_id = $this->restaurant_model->create_combo_table($table_ids, $name);
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => 'success',
                        'combo_table_id' => $combo_id,
                        'message' => 'Masalar başarıyla birleştirildi!',
                    ]));
                return;
            }

            if ($action === 'split') {
                $combo_id = (int) ($payload['combo_id'] ?? 0);
                $success = $this->restaurant_model->split_combo_table($combo_id);
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => $success ? 'success' : 'error',
                        'message' => $success ? 'Birleştirilmiş masa ayrıldı.' : 'Masa bulunamadı.',
                    ]));
                return;
            }
        } catch (Throwable $e) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]));
        }
    }

    /**
     * API: Mealtimes & Active Mealtime (TastyIgniter).
     */
    public function api_mealtimes(): void
    {
        $mealtimes = $this->restaurant_model->get_mealtimes();
        $active = $this->restaurant_model->get_active_mealtime();

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'data' => [
                    'mealtimes' => $mealtimes,
                    'active_mealtime' => $active,
                ],
            ]));
    }

    /**
     * API: Redeem customer loyalty points.
     */
    public function api_redeem_loyalty(): void
    {
        $this->ensure_authenticated();
        $payload = $this->get_request_payload();

        $adisyon_id = (int) ($payload['adisyon_id'] ?? 0);
        $points = (int) ($payload['points'] ?? 0);

        if (!$adisyon_id || $points <= 0) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode([
                'status' => 'error',
                'message' => 'Adisyon ID ve puan tutarı zorunludur.'
            ]));
            return;
        }

        $this->load->model('adisyons_model');
        $adisyon = $this->adisyons_model->find($adisyon_id);
        if (!$adisyon || empty($adisyon['id_users_customer'])) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode([
                'status' => 'error',
                'message' => 'Adisyona bağlı bir müşteri bulunamadı. Lütfen önce masaya müşteri bağlayın.'
            ]));
            return;
        }

        try {
            $res = $this->restaurant_model->redeem_loyalty_points($adisyon_id, (int) $adisyon['id_users_customer'], $points);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'points_redeemed' => $res['points_redeemed'],
                    'discount_applied' => $res['discount_applied'],
                    'new_total' => $res['new_total'],
                ]));
        } catch (Throwable $e) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
        }
    }

    /**
     * API: Smart Table Auto-Assignment.
     */
    public function api_auto_assign(): void
    {
        $party_size = (int) ($this->input->get('party_size') ?: $this->input->post('party_size') ?: 2);
        $section = $this->input->get('section') ?: $this->input->post('section');
        $customer_id = (int) ($this->input->get('customer_id') ?: $this->input->post('customer_id') ?: 0);
        $datetime = $this->input->get('datetime') ?: $this->input->post('datetime');

        $assigned = $this->restaurant_model->auto_assign_table($party_size, $section, $customer_id ?: null, $datetime);

        if (!$assigned) {
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'not_available', 'message' => 'Seçilen kişi sayısına uygun müsait masa bulunamadı.']));
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'table' => $assigned,
                'is_patron_favorite' => $assigned['is_patron_favorite'] ?? false,
            ]));
    }

    /**
     * API: KDS active tickets polling.
     */
    public function api_kds_orders(): void
    {
        $station = $this->input->get('station') ?: 'all';
        $orders = $this->restaurant_model->get_active_kitchen_orders($station);

        $now = time();
        foreach ($orders as &$o) {
            $ord_time = strtotime($o['ordered_at']);
            $elapsed_min = max(0, round(($now - $ord_time) / 60));
            $o['elapsed_minutes'] = $elapsed_min;
            $o['urgency'] = $elapsed_min >= 20 ? 'critical' : ($elapsed_min >= 10 ? 'warning' : 'normal');
        }
        unset($o);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'orders' => $orders,
                'count' => count($orders),
                'server_time' => date('H:i:s'),
            ]));
    }

    /**
     * API: Update KDS ticket status (bump).
     */
    public function api_kds_update(): void
    {
        $this->ensure_authenticated();
        $raw = file_get_contents('php://input');
        $payload = !empty($raw) ? json_decode($raw, true) : $this->input->post();

        $order_id = (int) ($payload['order_id'] ?? $payload['id'] ?? 0);
        $status = $payload['status'] ?? '';

        try {
            $this->restaurant_model->update_kitchen_order_status($order_id, $status);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'order_id' => $order_id, 'new_status' => $status]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * API: Waitress screen live tables and notifications polling.
     */
    public function api_waiter_tables(): void
    {
        $this->ensure_authenticated();
        $tables = $this->restaurant_model->get_tables_with_status();
        $calls = $this->restaurant_model->get_active_table_calls();

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'tables' => $tables,
                'active_calls' => $calls,
                'urgent_calls_count' => count($calls),
            ]));
    }

    /**
     * API: Transfer table.
     */
    public function api_transfer_table(): void
    {
        $this->ensure_authenticated();
        $raw = file_get_contents('php://input');
        $payload = !empty($raw) ? json_decode($raw, true) : $this->input->post();

        $source_id = (int) ($payload['source_table_id'] ?? 0);
        $target_id = (int) ($payload['target_table_id'] ?? 0);

        try {
            $result = $this->restaurant_model->transfer_table($source_id, $target_id, session('user_id') ?: null);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($result));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * API: Customer lookup by phone (for POS & Hostess).
     */
    public function api_customer_lookup(): void
    {
        $this->ensure_authenticated();
        $phone = $this->input->get('phone');
        if (empty($phone)) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => 'Telefon gereklidir.']));
            return;
        }

        $search_phone = preg_replace('/[^0-9]/', '', $phone);
        $customers = $this->customers_model->get();
        $customer = null;

        foreach ($customers as $c) {
            $c_phone = preg_replace('/[^0-9]/', '', $c['phone_number'] ?? '');
            if (!empty($c_phone) && !empty($search_phone) && (str_ends_with($c_phone, $search_phone) || str_ends_with($search_phone, $c_phone))) {
                $customer = $c;
                break;
            }
        }

        if (!$customer) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 'not_found']));
            return;
        }

        $customer_id = (int) $customer['id'];
        $points = $this->loyalty_points_model->get_balance($customer_id);

        $membership = $this->db
            ->select('cm.*, mp.name as plan_name, mp.discount_percent, mp.perks_description')
            ->from('customer_memberships cm')
            ->join('membership_plans mp', 'mp.id = cm.id_membership_plans', 'left')
            ->where('cm.id_users_customer', $customer_id)
            ->where('cm.status', 'active')
            ->get()
            ->row_array();

        // Patronage 360 profile
        $patron_profile = $this->restaurant_model->get_customer_dining_profile($customer_id);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'customer' => [
                    'id' => $customer_id,
                    'name' => trim($customer['first_name'] . ' ' . $customer['last_name']),
                    'phone' => $customer['phone_number'],
                    'loyalty_points' => $points,
                    'membership' => $membership,
                    'patron_profile' => $patron_profile,
                ],
            ]));
    }

    /**
     * API: Save patron profile notes & preferences.
     */
    public function api_save_patron_profile(): void
    {
        $this->ensure_authenticated();
        $payload = $this->get_request_payload();

        $customer_id = (int) ($payload['id_users_customer'] ?? 0);
        if (!$customer_id) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => 'Müşteri ID gereklidir.']));
            return;
        }

        try {
            $id = $this->restaurant_model->save_customer_dining_profile($payload);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'id' => $id]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * API: Lookup patron profile by phone number or search string.
     */
    public function api_lookup_patron(): void
    {
        $term = trim((string) ($this->input->get('q') ?: $this->input->post('q') ?: ''));
        if (empty($term)) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => 'Arama kriteri gereklidir.']));
            return;
        }

        try {
            $customers = $this->customers_model->search($term, 10);

            // Fallback: If empty, try searching direct phone or mobile or normalized digits
            if (empty($customers)) {
                $role_id = $this->customers_model->get_customer_role_id();
                $clean_digits = preg_replace('/[^0-9]/', '', $term);

                $this->db->select()
                    ->from('users')
                    ->where('id_roles', $role_id)
                    ->group_start()
                    ->like('first_name', $term)
                    ->or_like('last_name', $term)
                    ->or_like('mobile_number', $term);

                if (!empty($clean_digits)) {
                    $this->db->or_like('phone_number', $clean_digits)
                             ->or_like('mobile_number', $clean_digits);
                }
                $raw_custs = $this->db->group_end()->limit(10)->get()->result_array();
                $raw_ids = array_column($raw_custs, 'id');
                $customers = [];
                foreach ($raw_ids as $cid) {
                    $c_dec = $this->customers_model->find((int) $cid);
                    if ($c_dec) {
                        $customers[] = $c_dec;
                    }
                }
            }

            $results = [];
            foreach ($customers as $c) {
                $profile = $this->restaurant_model->get_customer_dining_profile((int) $c['id']);
                $results[] = [
                    'id' => (int) $c['id'],
                    'first_name' => $c['first_name'],
                    'last_name' => $c['last_name'],
                    'full_name' => $c['first_name'] . ' ' . $c['last_name'],
                    'phone_number' => $c['phone_number'],
                    'email' => $c['email'],
                    'patron_profile' => $profile,
                ];
            }

            $this->output->set_content_type('application/json')->set_output(json_encode([
                'status' => 'success',
                'results' => $results,
                'match' => !empty($results) ? $results[0] : null,
            ]));
        } catch (Throwable $e) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'location' => $e->getFile() . ':' . $e->getLine()
            ]));
        }
    }

    /**
     * API: Close table & adisyon (Finalize and free table for cleaning).
     */
    public function api_close_table(): void
    {
        $this->ensure_authenticated();
        $payload = $this->get_request_payload();

        $table_id = (int) ($payload['table_id'] ?? 0);
        $table = $this->db->get_where('restaurant_tables', ['id' => $table_id])->row_array();

        if (!$table) {
            $this->output->set_status_header(404)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => 'Masa bulunamadı.']));
            return;
        }

        if (empty($table['current_id_adisyons'])) {
            $this->restaurant_model->update_table_status($table_id, 'available');
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'status' => 'success',
                'success' => true,
                'table_status' => 'available',
                'message' => 'Masa boşa çıkarıldı ve kullanıma hazır.',
            ]));
            return;
        }

        $adisyon_id = (int) $table['current_id_adisyons'];

        try {
            $this->adisyons_model->close($adisyon_id);

            $adisyon = $this->adisyons_model->find($adisyon_id);
            if (empty($adisyon['id_users_customer']) && !empty($payload['customer_id'])) {
                $this->db->update('adisyons', ['id_users_customer' => (int) $payload['customer_id']], ['id' => $adisyon_id]);
                $adisyon['id_users_customer'] = (int) $payload['customer_id'];
            }

            $points_to_earn = 0;
            if (!empty($adisyon['id_users_customer'])) {
                $customer_id = (int) $adisyon['id_users_customer'];
                $total_amount = (float) $adisyon['total_amount'];

                // Sync to patronage profile
                $this->restaurant_model->record_guest_visit($customer_id, $total_amount, $table_id);

                // Award loyalty points (5% rate, 10 pts per 1 TL)
                $points_to_earn = (int) round($total_amount * 0.05 * 10);
                if ($points_to_earn > 0) {
                    $this->loyalty_points_model->earn($customer_id, 0, $points_to_earn);
                    $this->db->order_by('id', 'DESC')->limit(1)->update('loyalty_points', ['id_adisyons' => $adisyon_id], [
                        'id_users_customer' => $customer_id,
                        'points' => $points_to_earn,
                    ]);
                    $this->db->update('adisyons', ['loyalty_points_earned' => $points_to_earn], ['id' => $adisyon_id]);
                }
            }

            // Mark table cleaning
            $this->restaurant_model->update_table_status($table_id, 'cleaning');

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'success' => true,
                    'earned_points' => $points_to_earn,
                    'table_status' => 'cleaning',
                    'message' => 'Masa başarıyla kapatıldı ve temizlik moduna alındı.',
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'success' => false, 'message' => $e->getMessage()]));
        }
    }

    protected function ensure_authenticated(): void
    {
        if (!session('user_id')) {
            if ($this->input->is_ajax_request() || strpos($this->uri->uri_string(), 'api') !== false) {
                $this->output
                    ->set_status_header(401)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
                exit;
            }
            redirect('login');
            exit;
        }

        if (session('role_slug') === DB_SLUG_CUSTOMER) {
            if ($this->input->is_ajax_request() || strpos($this->uri->uri_string(), 'api') !== false) {
                $this->output
                    ->set_status_header(403)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => 'error', 'message' => 'Forbidden']));
                exit;
            }
            redirect('customer_portal');
            exit;
        }
    }

    protected function get_request_payload(): array
    {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                return $json;
            }
        }
        $post = $this->input->post();
        return is_array($post) ? $post : [];
    }
}
