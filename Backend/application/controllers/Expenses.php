<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Expenses Controller (Operational & Supplier Cost Management)
 * ---------------------------------------------------------------------------- */

class Expenses extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('expenses_model');
    }

    public function index(): void
    {
        session(['dest_url' => site_url('expenses')]);
        $user_id = session('user_id');
        if (!$user_id) {
            redirect('login');
            return;
        }

        if (session('role_slug') === DB_SLUG_CUSTOMER) {
            redirect('customer_portal');
            return;
        }

        $category = $this->input->get('category') ?: null;
        $start_date = $this->input->get('start_date') ?: null;
        $end_date = $this->input->get('end_date') ?: null;

        $expenses = $this->expenses_model->get_all($category, $start_date, $end_date, 100);
        $summary = $this->expenses_model->get_summary_by_category();

        $this->load->library('accounts');
        $this->load->model('roles_model');
        $role_slug = session('role_slug');

        html_vars([
            'page_title' => 'Gider Yönetimi & Harcamalar',
            'active_menu' => 'expenses',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        $view_data = [
            'active_menu' => 'expenses',
            'expenses' => $expenses,
            'summary' => $summary,
            'category_filter' => $category,
        ];

        $this->load->view('pages/expenses', $view_data);
    }

    public function save(): void
    {
        $this->ensure_authenticated();
        $data = [
            'id' => $this->input->post('id') ?: null,
            'supplier_name' => $this->input->post('supplier_name') ?: null,
            'category' => $this->input->post('category') ?: 'other',
            'title' => $this->input->post('title'),
            'amount' => (float) $this->input->post('amount'),
            'tax_amount' => (float) ($this->input->post('tax_amount') ?: 0),
            'expense_date' => $this->input->post('expense_date') ?: date('Y-m-d'),
            'payment_method' => $this->input->post('payment_method') ?: 'cash',
            'is_recurring' => $this->input->post('is_recurring') ? 1 : 0,
            'recurrence_period' => $this->input->post('recurrence_period') ?: null,
            'notes' => $this->input->post('notes') ?: null,
            'status' => $this->input->post('status') ?: 'paid',
            'created_by' => $this->session->userdata('user_id'),
        ];

        try {
            $id = $this->expenses_model->save($data);
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

    public function delete(int $id): void
    {
        $this->ensure_authenticated();
        if ($this->input->method() !== 'post') {
            $this->output
                ->set_status_header(405)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Method Not Allowed']));
            return;
        }
        $this->expenses_model->delete($id);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['status' => 'success']));
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
