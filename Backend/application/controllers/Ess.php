<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Employee Self-Service (ESS) Portal Controller
 * ---------------------------------------------------------------------------- */

class Ess extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('hr_model');
        $this->load->model('users_model');
    }

    public function index(): void
    {
        $user_id = (int) session('user_id');
        if (!$user_id) {
            redirect('login');
            return;
        }

        $tab = $this->input->get('tab') ?: 'overview';
        $today = date('Y-m-d');
        $year = (int) date('Y');
        $month = (int) date('m');

        $employee = $this->hr_model->get_employee($user_id);
        $leave_allocations = $this->hr_model->get_leave_allocations($user_id, $year);
        $leave_applications = $this->hr_model->get_leave_applications(['user_id' => $user_id]);
        $leave_types = $this->hr_model->get_leave_types(true);
        $shifts = $this->hr_model->get_shift_assignments($user_id, $today);
        $daily_attendance = $this->hr_model->get_daily_attendance($today, $user_id);
        $payroll_slips = $this->hr_model->get_user_payroll_slips($user_id);
        $advances = $this->hr_model->get_advances(['user_id' => $user_id]);
        $expenses = $this->hr_model->get_expense_claims(['user_id' => $user_id]);
        $assets = $this->hr_model->get_asset_assignments(['user_id' => $user_id, 'active_only' => true]);

        // Commissions earned this month from BooKi
        $commissions = [];
        if ($this->db->table_exists('staff_commissions')) {
            $commissions = $this->db
                ->where('id_users_staff', $user_id)
                ->where("MONTH(created_at) =", $month)
                ->where("YEAR(created_at) =", $year)
                ->order_by('created_at', 'DESC')
                ->get('staff_commissions')
                ->result_array();
        }

        $view = [
            'active_tab' => $tab,
            'employee' => $employee,
            'leave_allocations' => $leave_allocations,
            'leave_applications' => $leave_applications,
            'leave_types' => $leave_types,
            'shifts' => $shifts,
            'today_attendance' => !empty($daily_attendance) ? $daily_attendance[0] : null,
            'payroll_slips' => $payroll_slips,
            'advances' => $advances,
            'expenses' => $expenses,
            'assets' => $assets,
            'commissions' => $commissions,
        ];

        html_vars($view);
        $this->load->view('pages/ess_portal');
    }

    public function punch(): void
    {
        $user_id = (int) session('user_id');
        $punch_type = strtoupper($this->input->post('punch_type') ?: 'IN');
        $lat = !empty($this->input->post('latitude')) ? (float) $this->input->post('latitude') : null;
        $lng = !empty($this->input->post('longitude')) ? (float) $this->input->post('longitude') : null;

        $res = $this->hr_model->log_attendance($user_id, $punch_type, 'web_ess', $lat, $lng);
        json_response([
            'success' => true,
            'message' => "Giriş/Çıkış kaydedildi ({$punch_type}). Saat: " . date('H:i:s'),
            'data' => $res,
        ]);
    }

    public function apply_leave(): void
    {
        $user_id = (int) session('user_id');
        $data = $this->input->post();
        $data['id_users'] = $user_id;

        if (empty($data['id_leave_types']) || empty($data['start_date']) || empty($data['end_date'])) {
            json_response(['success' => false, 'message' => 'İzin türü ve tarihler zorunludur.'], 400);
            return;
        }

        $id = $this->hr_model->save_leave_application($data);
        json_response(['success' => true, 'id' => $id, 'message' => 'İzin başvurunuz yöneticinize iletildi.']);
    }

    public function request_advance(): void
    {
        $user_id = (int) session('user_id');
        $amount = (float) $this->input->post('requested_amount');
        $reason = $this->input->post('reason');

        if ($amount <= 0) {
            json_response(['success' => false, 'message' => 'Geçerli bir avans tutarı giriniz.'], 400);
            return;
        }

        $id = $this->hr_model->save_advance([
            'id_users' => $user_id,
            'requested_amount' => $amount,
            'reason' => $reason,
        ]);

        json_response(['success' => true, 'id' => $id, 'message' => 'Avans talebiniz kaydedildi.']);
    }

    public function submit_expense(): void
    {
        $user_id = (int) session('user_id');
        $amount = (float) $this->input->post('amount');
        $category = $this->input->post('category') ?: 'general';
        $notes = $this->input->post('notes');

        if ($amount <= 0) {
            json_response(['success' => false, 'message' => 'Masraf tutarı zorunludur.'], 400);
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

        $id = $this->hr_model->save_expense_claim([
            'id_users' => $user_id,
            'category' => $category,
            'amount' => $amount,
            'notes' => $notes,
            'receipt_file_path' => $receipt_path,
        ]);

        json_response(['success' => true, 'id' => $id, 'message' => 'Masraf fişiniz kaydedildi.']);
    }
}
