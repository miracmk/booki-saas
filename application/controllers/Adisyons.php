<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Adisyons Controller (Service Tickets, Orders & Checkout Operations)
 * ---------------------------------------------------------------------------- */

class Adisyons extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('adisyons_model');
        $this->load->model('customers_model');
        $this->load->model('services_model');
        $this->load->model('products_model');
        $this->load->model('packages_model');
        $this->load->model('customer_memberships_model');
    }

    /**
     * Adisyons listing page.
     */
    public function index(): void
    {
        session(['dest_url' => site_url('adisyons')]);
        $user_id = session('user_id');
        if (!$user_id) {
            redirect('login');
            return;
        }

        $status = $this->input->get('status') ?: 'all';
        $payment_status = $this->input->get('payment_status') ?: 'all';

        $this->db
            ->select('a.*, 
                      c.first_name as customer_first_name, c.last_name as customer_last_name, c.phone_number as customer_phone,
                      u.first_name as staff_first_name, u.last_name as staff_last_name,
                      rt.table_number, rt.name as table_name')
            ->from('adisyons a')
            ->join('users c', 'c.id = a.id_users_customer', 'left')
            ->join('users u', 'u.id = a.id_users_staff', 'left')
            ->join('restaurant_tables rt', 'rt.id = a.id_restaurant_tables', 'left');

        if ($status !== 'all') {
            $this->db->where('a.status', $status);
        }
        if ($payment_status !== 'all') {
            $this->db->where('a.payment_status', $payment_status);
        }

        $adisyons = $this->db
            ->order_by('a.id DESC')
            ->limit(100)
            ->get()
            ->result_array();

        // Calculate summary metrics
        $open_count = $this->db->where('status', 'open')->count_all_results('adisyons');
        $unpaid_total = (float) ($this->db->select_sum('total_amount')->where('payment_status', 'unpaid')->get('adisyons')->row()->total_amount ?? 0);
        $today_revenue = (float) ($this->db->select_sum('amount')->where('DATE(created_at)', date('Y-m-d'))->get('adisyon_payments')->row()->amount ?? 0);

        $this->load->library('accounts');
        $this->load->model('roles_model');
        $role_slug = session('role_slug');

        html_vars([
            'page_title' => 'Adisyonlar (Hesap & Sipariş Fişleri)',
            'active_menu' => 'adisyons',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        $view_data = [
            'active_menu' => 'adisyons',
            'adisyons' => $adisyons,
            'status_filter' => $status,
            'payment_filter' => $payment_status,
            'open_count' => $open_count,
            'unpaid_total' => $unpaid_total,
            'today_revenue' => $today_revenue,
            'available_services' => $this->services_model->get_available_services(),
            'available_products' => $this->products_model->get(['is_active' => 1]),
            'customers' => $this->customers_model->get(),
            'staff_members' => $this->db->get_where('users', ['id_roles' => 2])->result_array(),
        ];

        $this->load->view('pages/adisyons', $view_data);
    }

    /**
     * Get single adisyon details JSON (with items and payments).
     */
    public function get_details(int $adisyon_id): void
    {
        $this->ensure_authenticated();
        try {
            $adisyon = $this->adisyons_model->find($adisyon_id);

            // Fetch customer packages & memberships for quick checkout deduction
            $packages = [];
            $memberships = [];
            if (!empty($adisyon['id_users_customer'])) {
                $packages = $this->packages_model->get_for_customer((int) $adisyon['id_users_customer']);
                $memberships = $this->customer_memberships_model->get_for_customer((int) $adisyon['id_users_customer']);
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'success',
                    'adisyon' => $adisyon,
                    'customer_packages' => $packages,
                    'customer_memberships' => $memberships,
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Create fresh manual adisyon.
     */
    public function create(): void
    {
        $this->ensure_authenticated();
        $customer_id = $this->input->post('id_users_customer') ? (int) $this->input->post('id_users_customer') : null;
        $staff_id = $this->input->post('id_users_staff') ? (int) $this->input->post('id_users_staff') : null;
        $table_id = $this->input->post('id_restaurant_tables') ? (int) $this->input->post('id_restaurant_tables') : null;

        try {
            if ($table_id) {
                $adisyon = $this->adisyons_model->get_or_create_for_table($table_id, $customer_id, $staff_id);
            } else {
                $now = date('Y-m-d H:i:s');
                $prefix = 'AD-' . date('Ym') . '-';
                $last = $this->db->select('adisyon_number')->like('adisyon_number', $prefix, 'after')->order_by('id', 'DESC')->limit(1)->get('adisyons')->row_array();
                $num = $last ? (int) substr($last['adisyon_number'], strlen($prefix)) + 1 : 1;
                $adisyon_number = $prefix . str_pad((string) $num, 4, '0', STR_PAD_LEFT);

                $this->db->insert('adisyons', [
                    'adisyon_number' => $adisyon_number,
                    'id_users_customer' => $customer_id,
                    'id_users_staff' => $staff_id,
                    'status' => 'open',
                    'payment_status' => 'unpaid',
                    'invoice_status' => 'uninvoiced',
                    'subtotal' => 0.00,
                    'discount_amount' => 0.00,
                    'discount_percent' => 0.00,
                    'tax_amount' => 0.00,
                    'total_amount' => 0.00,
                    'paid_amount' => 0.00,
                    'tip_amount' => 0.00,
                    'opened_at' => $now,
                    'created_at' => $now,
                ]);
                $adisyon_id = $this->db->insert_id();
                $adisyon = $this->adisyons_model->find($adisyon_id);
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'adisyon' => $adisyon]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Add line item to adisyon.
     */
    public function add_item(): void
    {
        $this->ensure_authenticated();
        $adisyon_id = (int) $this->input->post('id_adisyons');
        $item = [
            'item_type' => $this->input->post('item_type') ?: 'product',
            'id_services' => $this->input->post('id_services') ?: null,
            'id_products' => $this->input->post('id_products') ?: null,
            'name' => $this->input->post('name'),
            'unit_price' => (float) $this->input->post('unit_price'),
            'quantity' => (float) ($this->input->post('quantity') ?: 1),
            'discount_amount' => (float) ($this->input->post('discount_amount') ?: 0),
            'tax_rate' => (float) ($this->input->post('tax_rate') ?: 20),
            'id_users_staff' => $this->input->post('id_users_staff') ?: null,
            'notes' => $this->input->post('notes') ?: null,
        ];

        try {
            $item_id = $this->adisyons_model->add_item($adisyon_id, $item);
            $adisyon = $this->adisyons_model->find($adisyon_id);

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'item_id' => $item_id, 'adisyon' => $adisyon]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Delete line item.
     */
    public function remove_item(int $item_id): void
    {
        $this->ensure_authenticated();
        try {
            $item = $this->db->get_where('adisyon_items', ['id' => $item_id])->row_array();
            $adisyon_id = $item ? (int) $item['id_adisyons'] : 0;

            $this->adisyons_model->remove_item($item_id);
            $adisyon = $adisyon_id ? $this->adisyons_model->find($adisyon_id) : null;

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'adisyon' => $adisyon]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Record payment (Cash, Card, Transfer, Package, Membership).
     */
    public function pay(): void
    {
        $this->ensure_authenticated();
        $adisyon_id = (int) $this->input->post('id_adisyons');
        $payment_data = [
            'amount' => (float) $this->input->post('amount'),
            'payment_method' => $this->input->post('payment_method') ?: 'cash',
            'id_customer_packages' => $this->input->post('id_customer_packages') ?: null,
            'id_customer_memberships' => $this->input->post('id_customer_memberships') ?: null,
            'notes' => $this->input->post('notes') ?: null,
            'received_by' => $this->session->userdata('user_id'),
        ];

        try {
            $payment_id = $this->adisyons_model->record_payment($adisyon_id, $payment_data);
            $adisyon = $this->adisyons_model->find($adisyon_id);

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'payment_id' => $payment_id, 'adisyon' => $adisyon]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Close adisyon & deduct inventory/consumables.
     */
    public function close(int $adisyon_id): void
    {
        $this->ensure_authenticated();
        try {
            $this->adisyons_model->close($adisyon_id);
            $adisyon = $this->adisyons_model->find($adisyon_id);

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'adisyon' => $adisyon]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Convert adisyon to Invoice.
     */
    public function create_invoice(int $adisyon_id): void
    {
        $this->ensure_authenticated();
        try {
            $invoice_id = $this->adisyons_model->convert_to_invoice($adisyon_id);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'invoice_id' => $invoice_id]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Thermal / POS slip print view.
     */
    public function print_slip(int $adisyon_id): void
    {
        $this->ensure_authenticated();
        $adisyon = $this->adisyons_model->find($adisyon_id);
        $this->load->view('pages/adisyon_print_slip', [
            'adisyon' => $adisyon,
            'company_name' => setting('company_name') ?: 'BooKi',
        ]);
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
    }
}
