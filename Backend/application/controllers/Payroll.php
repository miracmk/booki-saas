<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Payroll, Advances & Expense Claims Controller
 * ---------------------------------------------------------------------------- */

class Payroll extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('hr_model');
        $this->load->model('users_model');
    }

    public function index(): void
    {
        $user_id = session('user_id');
        if (!$user_id) {
            redirect('login');
            return;
        }

        $tab = $this->input->get('tab') ?: 'payrolls';
        $selected_payroll_id = (int) $this->input->get('payroll_id');

        $payrolls = $this->hr_model->get_payrolls();
        if (!$selected_payroll_id && !empty($payrolls)) {
            $selected_payroll_id = (int) $payrolls[0]['id'];
        }

        $slips = $selected_payroll_id ? $this->hr_model->get_payroll_slips($selected_payroll_id) : [];
        $components = $this->hr_model->get_salary_components();
        $advances = $this->hr_model->get_advances();
        $expenses = $this->hr_model->get_expense_claims();
        $employees = $this->hr_model->get_employees(['is_active' => 1]);

        $view = [
            'active_tab' => $tab,
            'payrolls' => $payrolls,
            'selected_payroll_id' => $selected_payroll_id,
            'slips' => $slips,
            'components' => $components,
            'advances' => $advances,
            'expenses' => $expenses,
            'employees' => $employees,
        ];

        html_vars($view);
        $this->load->view('pages/payroll');
    }

    public function generate(): void
    {
        $month = (int) ($this->input->post('month') ?: date('m'));
        $year = (int) ($this->input->post('year') ?: date('Y'));
        $branch_id = !empty($this->input->post('branch_id')) ? (int) $this->input->post('branch_id') : null;

        $res = $this->hr_model->generate_payroll($month, $year, $branch_id);
        json_response([
            'success' => true,
            'data' => $res,
            'message' => "{$year}/{$month} dönemi bordrosu başarıyla hesaplandı ({$res['employee_count']} çalışan).",
        ]);
    }

    public function finalize(int $payroll_id): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->update('hr_payrolls', [
            'status' => 'finalized',
            'finalized_at' => $now,
            'updated_at' => $now,
        ], ['id' => $payroll_id]);

        json_response(['success' => true, 'message' => 'Bordro kesinleştirildi ve onaylandı.']);
    }

    public function save_structure(): void
    {
        $user_id = (int) $this->input->post('id_users');
        $base = (float) $this->input->post('base_salary');
        $currency = $this->input->post('currency') ?: 'TRY';

        if (!$user_id) {
            json_response(['success' => false, 'message' => 'Personel seçilmelidir.'], 400);
            return;
        }

        $this->hr_model->save_salary_structure($user_id, [
            'base_salary' => $base,
            'currency' => $currency,
        ]);

        json_response(['success' => true, 'message' => 'Maaş yapısı kaydedildi.']);
    }

    public function save_advance(): void
    {
        $data = $this->input->post();
        if (empty($data['id_users']) || empty($data['requested_amount'])) {
            json_response(['success' => false, 'message' => 'Personel ve talep tutarı zorunludur.'], 400);
            return;
        }

        $id = $this->hr_model->save_advance($data);
        json_response(['success' => true, 'id' => $id, 'message' => 'Avans talebi kaydedildi.']);
    }

    public function update_advance_status(): void
    {
        $id = (int) $this->input->post('id');
        $status = $this->input->post('status');
        $reason = $this->input->post('reason');
        $approver_id = (int) session('user_id');

        $this->hr_model->update_advance_status($id, $status, $approver_id, $reason);
        json_response(['success' => true, 'message' => "Avans durumu güncellendi ({$status})."]);
    }

    public function save_expense(): void
    {
        $data = $this->input->post();
        if (empty($data['id_users']) || empty($data['amount'])) {
            json_response(['success' => false, 'message' => 'Personel ve masraf tutarı zorunludur.'], 400);
            return;
        }

        $receipt_path = null;
        if (!empty($_FILES['receipt']['name'])) {
            $upload_path = FCPATH . 'storage/hr_expenses/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }
            $orig_name = basename($_FILES['receipt']['name']);
            $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $orig_name);
            $target = $upload_path . $file_name;
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $target)) {
                $receipt_path = 'storage/hr_expenses/' . $file_name;
            }
        }

        $data['receipt_file_path'] = $receipt_path;
        $id = $this->hr_model->save_expense_claim($data);
        json_response(['success' => true, 'id' => $id, 'message' => 'Masraf fişi kaydedildi.']);
    }

    public function update_expense_status(): void
    {
        $id = (int) $this->input->post('id');
        $status = $this->input->post('status');
        $approver_id = (int) session('user_id');

        $this->hr_model->update_expense_status($id, $status, $approver_id);
        json_response(['success' => true, 'message' => "Masraf durumu güncellendi ({$status})."]);
    }
}
