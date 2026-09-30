<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Finance Controller (Central Financial Operations, Cash, Bank & Expenses)
 * ---------------------------------------------------------------------------- */

class Finance extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('adisyons_model');
        $this->load->model('invoices_model');
        $this->load->model('expenses_model');
        $this->load->model('cash_registers_model');
        $this->load->model('staff_commissions_model');
    }

    /**
     * Central Finance Hub.
     */
    public function index(): void
    {
        session(['dest_url' => site_url('finance')]);
        $user_id = session('user_id');
        if (!$user_id) {
            redirect('login');
            return;
        }

        if (session('role_slug') === DB_SLUG_CUSTOMER) {
            redirect('customer_portal');
            return;
        }

        $today = date('Y-m-d');
        $this_month = date('Y-m');

        // Revenue metrics
        $today_collections = (float) ($this->db->select_sum('amount')->where('DATE(created_at)', $today)->get('adisyon_payments')->row()->amount ?? 0);
        $month_collections = (float) ($this->db->select_sum('amount')->where("DATE_FORMAT(created_at, '%Y-%m') =", $this_month)->get('adisyon_payments')->row()->amount ?? 0);

        $unpaid_adisyons = (float) ($this->db->select_sum('total_amount')->where('payment_status', 'unpaid')->get('adisyons')->row()->total_amount ?? 0);
        $unpaid_invoices = (float) ($this->db->select_sum('total')->where('status', 'issued')->get('invoices')->row()->total ?? 0);

        $month_expenses = (float) ($this->db->select_sum('amount')->where("DATE_FORMAT(expense_date, '%Y-%m') =", $this_month)->where('status', 'paid')->get('expenses')->row()->amount ?? 0);

        $net_cashflow = round($month_collections - $month_expenses, 2);

        // Active cash register
        $active_register = $this->cash_registers_model->get_active_register();
        $bank_accounts = $this->db->get_where('bank_accounts', ['is_active' => 1])->result_array();

        // Recent payments
        $recent_payments = $this->db
            ->select('ap.*, ad.adisyon_number, c.first_name as customer_first_name, c.last_name as customer_last_name')
            ->from('adisyon_payments ap')
            ->join('adisyons ad', 'ad.id = ap.id_adisyons', 'left')
            ->join('users c', 'c.id = ad.id_users_customer', 'left')
            ->order_by('ap.created_at DESC')
            ->limit(20)
            ->get()
            ->result_array();

        // Recent expenses
        $expenses = $this->expenses_model->get_all(null, null, null, 20);
        $expense_categories = $this->expenses_model->get_summary_by_category($this_month);

        // Staff commissions summary
        $staff_commissions = $this->staff_commissions_model->get_commissions_summary();

        $this->load->library('accounts');
        $this->load->model('roles_model');
        $role_slug = session('role_slug');

        html_vars([
            'page_title' => 'Finans Merkezi & Kasa Yönetimi',
            'active_menu' => 'finance',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        $view_data = [
            'active_menu' => 'finance',
            'today_collections' => $today_collections,
            'month_collections' => $month_collections,
            'unpaid_receivables' => round($unpaid_adisyons + $unpaid_invoices, 2),
            'month_expenses' => $month_expenses,
            'net_cashflow' => $net_cashflow,
            'active_register' => $active_register,
            'bank_accounts' => $bank_accounts,
            'recent_payments' => $recent_payments,
            'expenses' => $expenses,
            'expense_categories' => $expense_categories,
            'staff_commissions' => $staff_commissions,
        ];

        $this->load->view('pages/finance', $view_data);
    }

    /**
     * Close Cash Register / EOD Closing.
     */
    public function close_register(): void
    {
        $this->ensure_authenticated();
        $register_id = (int) $this->input->post('id_cash_registers');
        $actual_cash = (float) $this->input->post('actual_cash');
        $notes = $this->input->post('closing_notes') ?: null;

        try {
            $result = $this->cash_registers_model->close_register(
                $register_id,
                $actual_cash,
                $this->session->userdata('user_id'),
                $notes
            );

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
     * Open New Cash Register.
     */
    public function open_register(): void
    {
        $this->ensure_authenticated();
        $name = $this->input->post('register_name') ?: 'Ana Kasa';
        $opening = (float) $this->input->post('opening_balance');

        try {
            $id = $this->cash_registers_model->open_register($name, $opening, $this->session->userdata('user_id'));
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
     * Generate Staff Payroll Payout.
     */
    public function generate_payout(): void
    {
        $this->ensure_authenticated();
        $staff_id = (int) $this->input->post('id_users_staff');
        $period_start = $this->input->post('period_start');
        $period_end = $this->input->post('period_end');
        $base_salary = (float) ($this->input->post('base_salary') ?: 0);
        $advances = (float) ($this->input->post('advances') ?: 0);

        try {
            $payout_id = $this->staff_commissions_model->generate_payout(
                $staff_id,
                $period_start,
                $period_end,
                $base_salary,
                $advances
            );

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'success', 'payout_id' => $payout_id]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Save / Connect bank account or POS terminal.
     */
    public function save_bank_account(): void
    {
        $this->ensure_authenticated();
        $id = (int) $this->input->post('id');
        $data = [
            'bank_name' => trim((string) $this->input->post('bank_name')),
            'account_name' => trim((string) $this->input->post('account_name')),
            'account_type' => trim((string) $this->input->post('account_type')) ?: 'bank',
            'iban' => trim((string) $this->input->post('iban')),
            'pos_terminal_id' => trim((string) $this->input->post('pos_terminal_id')),
            'pos_provider' => trim((string) $this->input->post('pos_provider')),
            'currency' => trim((string) $this->input->post('currency')) ?: 'TRY',
            'balance' => (float) ($this->input->post('balance') ?: 0),
            'is_default_iban' => $this->input->post('is_default_iban') ? 1 : 0,
            'is_default_pos' => $this->input->post('is_default_pos') ? 1 : 0,
            'is_default_payout' => $this->input->post('is_default_payout') ? 1 : 0,
            'is_active' => 1,
        ];

        if ($data['is_default_iban']) {
            $this->db->update('bank_accounts', ['is_default_iban' => 0]);
        }
        if ($data['is_default_pos']) {
            $this->db->update('bank_accounts', ['is_default_pos' => 0]);
        }
        if ($data['is_default_payout']) {
            $this->db->update('bank_accounts', ['is_default_payout' => 0]);
        }

        if ($id > 0) {
            $this->db->where('id', $id)->update('bank_accounts', $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('bank_accounts', $data);
        }

        json_response(['success' => true, 'message' => 'Hesap / POS bilgisi başarıyla kaydedildi.']);
    }

    /**
     * Delete / Deactivate bank account.
     */
    public function delete_bank_account(int $id): void
    {
        $this->ensure_authenticated();
        $this->db->where('id', $id)->update('bank_accounts', ['is_active' => 0]);
        json_response(['success' => true, 'message' => 'Hesap başarıyla silindi.']);
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
