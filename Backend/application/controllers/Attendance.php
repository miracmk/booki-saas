<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - PDKS, Shifts & Attendance Controller
 * ---------------------------------------------------------------------------- */

class Attendance extends App_Controller
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

        $tab = $this->input->get('tab') ?: 'daily';
        $date = $this->input->get('date') ?: date('Y-m-d');
        $month = (int) ($this->input->get('month') ?: date('m'));
        $year = (int) ($this->input->get('year') ?: date('Y'));

        $shifts = $this->hr_model->get_shifts();
        $assignments = $this->hr_model->get_shift_assignments();
        $daily = $this->hr_model->get_daily_attendance($date);
        $monthly_summary = $this->hr_model->get_monthly_attendance_summary($month, $year);
        $employees = $this->hr_model->get_employees(['is_active' => 1]);

        $view = [
            'active_tab' => $tab,
            'selected_date' => $date,
            'selected_month' => $month,
            'selected_year' => $year,
            'shifts' => $shifts,
            'assignments' => $assignments,
            'daily_attendance' => $daily,
            'monthly_summary' => $monthly_summary,
            'employees' => $employees,
        ];

        html_vars($view);
        $this->load->view('pages/attendance');
    }

    public function punch(): void
    {
        $user_id = (int) ($this->input->post('id_users') ?: session('user_id'));
        $punch_type = strtoupper($this->input->post('punch_type') ?: 'IN');
        $method = $this->input->post('method') ?: 'web';
        $lat = !empty($this->input->post('latitude')) ? (float) $this->input->post('latitude') : null;
        $lng = !empty($this->input->post('longitude')) ? (float) $this->input->post('longitude') : null;

        if (!$user_id) {
            json_response(['success' => false, 'message' => 'Personel ID bulunamadı.'], 400);
            return;
        }

        $res = $this->hr_model->log_attendance($user_id, $punch_type, $method, $lat, $lng);
        json_response(['success' => true, 'data' => $res, 'message' => "Giriş/Çıkış işlemi kaydedildi ({$punch_type})."]);
    }

    public function kiosk_punch(): void
    {
        $pin = trim($this->input->post('pin_code') ?? '');
        $qr = trim($this->input->post('qr_token') ?? '');
        $punch_type = strtoupper($this->input->post('punch_type') ?: 'IN');

        if (empty($pin) && empty($qr)) {
            json_response(['success' => false, 'message' => 'PIN veya QR kodu girilmelidir.'], 400);
            return;
        }

        $this->db->where('is_active', 1);
        if (!empty($pin)) {
            $this->db->where('pin_code', $pin);
        } else {
            $this->db->where('qr_token', $qr);
        }
        $user = $this->db->get('users')->row_array();

        if (!$user) {
            json_response(['success' => false, 'message' => 'Geçersiz PIN veya QR kod!'], 404);
            return;
        }

        $res = $this->hr_model->log_attendance((int) $user['id'], $punch_type, !empty($pin) ? 'kiosk_pin' : 'kiosk_qr');
        json_response([
            'success' => true,
            'employee_name' => $user['first_name'] . ' ' . $user['last_name'],
            'punch_type' => $punch_type,
            'time' => date('H:i:s'),
            'message' => "Hoş geldiniz, {$user['first_name']}! Kayıt alındı.",
        ]);
    }

    public function save_shift(): void
    {
        $data = $this->input->post();
        if (empty($data['name']) || empty($data['start_time']) || empty($data['end_time'])) {
            json_response(['success' => false, 'message' => 'Vardiya adı ve saatleri zorunludur.'], 400);
            return;
        }

        $id = $this->hr_model->save_shift($data);
        json_response(['success' => true, 'id' => $id, 'message' => 'Vardiya başarıyla kaydedildi.']);
    }

    public function delete_shift(int $id): void
    {
        $this->hr_model->delete_shift($id);
        json_response(['success' => true, 'message' => 'Vardiya silindi.']);
    }

    public function save_assignment(): void
    {
        $data = $this->input->post();
        if (empty($data['id_users']) || empty($data['id_shifts']) || empty($data['start_date'])) {
            json_response(['success' => false, 'message' => 'Personel, vardiya ve başlangıç tarihi zorunludur.'], 400);
            return;
        }

        $id = $this->hr_model->save_shift_assignment($data);
        json_response(['success' => true, 'id' => $id, 'message' => 'Vardiya ataması yapıldı.']);
    }

    public function delete_assignment(int $id): void
    {
        $this->hr_model->delete_shift_assignment($id);
        json_response(['success' => true, 'message' => 'Vardiya ataması silindi.']);
    }

    public function kiosk(): void
    {
        // Standalone touch tablet kiosk screen for branches
        $view = [
            'shifts' => $this->hr_model->get_shifts(true),
        ];
        html_vars($view);
        $this->load->view('pages/kiosk_attendance');
    }
}
