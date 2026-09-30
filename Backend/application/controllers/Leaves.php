<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Leave Management Controller (4857 SK)
 * ---------------------------------------------------------------------------- */

class Leaves extends App_Controller
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

        $tab = $this->input->get('tab') ?: 'applications';
        $status = $this->input->get('status') ?: '';
        $year = (int) ($this->input->get('year') ?: date('Y'));

        $applications = $this->hr_model->get_leave_applications([
            'status' => $status,
            'year' => $year,
        ]);
        $leave_types = $this->hr_model->get_leave_types(false);
        $holidays = $this->hr_model->get_holidays($year);
        $employees = $this->hr_model->get_employees(['is_active' => 1]);

        $view = [
            'active_tab' => $tab,
            'selected_year' => $year,
            'selected_status' => $status,
            'applications' => $applications,
            'leave_types' => $leave_types,
            'holidays' => $holidays,
            'employees' => $employees,
        ];

        html_vars($view);
        $this->load->view('pages/leaves');
    }

    public function apply(): void
    {
        $data = $this->input->post();
        if (empty($data['id_users']) || empty($data['id_leave_types']) || empty($data['start_date']) || empty($data['end_date'])) {
            json_response(['success' => false, 'message' => 'Personel, izin türü ve tarihler zorunludur.'], 400);
            return;
        }

        $id = $this->hr_model->save_leave_application($data);
        json_response(['success' => true, 'id' => $id, 'message' => 'İzin talebi başarıyla oluşturuldu.']);
    }

    public function update_status(): void
    {
        $id = (int) $this->input->post('id');
        $status = $this->input->post('status'); // approved, rejected, cancelled
        $reason = $this->input->post('rejection_reason');
        $approver_id = (int) session('user_id');

        if (!$id || !in_array($status, ['approved', 'rejected', 'cancelled'])) {
            json_response(['success' => false, 'message' => 'Geçersiz talep veya durum.'], 400);
            return;
        }

        $res = $this->hr_model->update_leave_status($id, $status, $approver_id, $reason);
        json_response(['success' => $res, 'message' => "İzin talebi güncellendi ({$status})."]);
    }

    public function save_type(): void
    {
        $data = $this->input->post();
        if (empty($data['name'])) {
            json_response(['success' => false, 'message' => 'İzin türü adı zorunludur.'], 400);
            return;
        }

        $id = $this->hr_model->save_leave_type($data);
        json_response(['success' => true, 'id' => $id, 'message' => 'İzin türü kaydedildi.']);
    }

    public function save_holiday(): void
    {
        $data = $this->input->post();
        if (empty($data['name']) || empty($data['start_date']) || empty($data['end_date'])) {
            json_response(['success' => false, 'message' => 'Tatil adı ve tarihleri zorunludur.'], 400);
            return;
        }

        $id = $this->hr_model->save_holiday($data);
        json_response(['success' => true, 'id' => $id, 'message' => 'Tatil takvimi güncellendi.']);
    }

    public function delete_holiday(int $id): void
    {
        $this->hr_model->delete_holiday($id);
        json_response(['success' => true, 'message' => 'Tatil silindi.']);
    }
}
