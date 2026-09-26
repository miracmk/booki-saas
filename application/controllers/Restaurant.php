<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Restaurant Operations Controller (Floor Plan, Tables & Reservations)
 * ---------------------------------------------------------------------------- */

class Restaurant extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('restaurant_model');
        $this->load->model('customers_model');
        $this->load->model('adisyons_model');
    }

    /**
     * Interactive Floor Plan & Live Table Operations Center.
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
        $today_reservations = $this->restaurant_model->get_reservations(date('Y-m-d'));
        $waitlist = $this->db->table_exists('waitlist') ? $this->db->get('waitlist', 20)->result_array() : [];
        $experiences = $this->restaurant_model->get_experiences();

        $this->load->library('accounts');
        $this->load->model('roles_model');
        $role_slug = session('role_slug');

        html_vars([
            'page_title' => 'Restoran Operasyonları & Masa Planı',
            'active_menu' => 'restaurant_floor_plan',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        $view_data = [
            'active_menu' => 'restaurant_floor_plan',
            'tables' => $tables,
            'reservations' => $today_reservations,
            'waitlist' => $waitlist,
            'experiences' => $experiences,
            'staff_members' => $this->db->get_where('users', ['id_roles' => 2])->result_array(), // providers/staff
        ];

        $this->load->view('pages/restaurant_floor_plan', $view_data);
    }

    /**
     * Reservations list view.
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
        $status = $this->input->get('status') ?: null;

        $reservations = $this->restaurant_model->get_reservations($date, $status);
        $tables = $this->restaurant_model->get_tables_with_status();
        $experiences = $this->restaurant_model->get_experiences();

        $this->load->library('accounts');
        $this->load->model('roles_model');
        $role_slug = session('role_slug');

        html_vars([
            'page_title' => 'Restoran Rezervasyonları',
            'active_menu' => 'restaurant_reservations',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        $view_data = [
            'active_menu' => 'restaurant_reservations',
            'date' => $date,
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
     * Update table coordinates & size via AJAX drag-and-drop.
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
     * Change table status (available, seated, dining, bill_requested, cleaning, etc.).
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
     * Seat reservation or walk-in at a table and open adisyon.
     */
    public function seat(): void
    {
        $this->ensure_authenticated();
        $table_id = (int) $this->input->post('id_tables');
        $reservation_id = $this->input->post('id_reservations') ? (int) $this->input->post('id_reservations') : null;
        $customer_id = $this->input->post('id_users_customer') ? (int) $this->input->post('id_users_customer') : null;
        $server_id = $this->input->post('id_users_server') ? (int) $this->input->post('id_users_server') : null;

        try {
            if ($reservation_id) {
                $result = $this->restaurant_model->seat_reservation($reservation_id, $table_id);
            } else {
                $adisyon = $this->adisyons_model->get_or_create_for_table($table_id, $customer_id, $server_id);
                $result = [
                    'table_id' => $table_id,
                    'adisyon_id' => $adisyon['id'],
                ];
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'data' => $result]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Save restaurant table (create/update).
     */
    public function save_table(): void
    {
        $this->ensure_authenticated();
        $data = [
            'id' => $this->input->post('id') ?: null,
            'table_number' => $this->input->post('table_number'),
            'name' => $this->input->post('name') ?: 'Masa ' . $this->input->post('table_number'),
            'section' => $this->input->post('section') ?: 'Ana Salon',
            'capacity' => (int) ($this->input->post('capacity') ?: 4),
            'shape' => $this->input->post('shape') ?: 'rectangle',
        ];

        try {
            $id = $this->restaurant_model->save_table($data);
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
     * Save restaurant reservation.
     */
    public function save_reservation(): void
    {
        $this->ensure_authenticated();
        $data = [
            'id' => $this->input->post('id') ?: null,
            'id_users_customer' => $this->input->post('id_users_customer') ?: null,
            'id_restaurant_tables' => $this->input->post('id_restaurant_tables') ?: null,
            'party_size' => (int) ($this->input->post('party_size') ?: 2),
            'reservation_datetime' => $this->input->post('reservation_datetime'),
            'duration_minutes' => (int) ($this->input->post('duration_minutes') ?: 120),
            'id_restaurant_experiences' => $this->input->post('id_restaurant_experiences') ?: null,
            'special_occasion' => $this->input->post('special_occasion') ?: null,
            'allergies' => $this->input->post('allergies') ?: null,
            'preferences' => $this->input->post('preferences') ?: null,
            'is_vip' => $this->input->post('is_vip') ? 1 : 0,
            'deposit_amount' => (float) ($this->input->post('deposit_amount') ?: 0),
            'notes' => $this->input->post('notes') ?: null,
        ];

        try {
            $id = $this->restaurant_model->save_reservation($data);
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

    protected function ensure_authenticated(): void
    {
        if (!session('user_id')) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
            exit;
        }

        if (session('role_slug') === DB_SLUG_CUSTOMER) {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Forbidden']));
            exit;
        }
    }
}
