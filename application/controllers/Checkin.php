<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Check-in / Check-out Controller (Operational Access & Kiosk Terminal)
 * ---------------------------------------------------------------------------- */

class Checkin extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('checkin_model');
        $this->load->model('customers_model');
        $this->load->model('customer_memberships_model');
        $this->load->model('packages_model');
    }

    /**
     * Operational Check-in / Check-out management page.
     */
    public function index(): void
    {
        session(['dest_url' => site_url('checkin')]);
        $user_id = session('user_id');
        if (!$user_id) {
            redirect('login');
            return;
        }

        $occupancy = $this->checkin_model->get_live_occupancy();
        $history = $this->checkin_model->get_history(null, date('Y-m-d'), 50);

        $this->load->library('accounts');
        $this->load->model('roles_model');
        $role_slug = session('role_slug');

        html_vars([
            'page_title' => 'Giriş / Çıkış & Tesis Doluluk Yönetimi',
            'active_menu' => 'checkin',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        $view_data = [
            'active_menu' => 'checkin',
            'occupancy' => $occupancy,
            'history' => $history,
            'customers' => $this->customers_model->get(null, 100, null, 'first_name ASC'),
        ];

        $this->load->view('pages/checkin', $view_data);
    }

    /**
     * Fullscreen Touch Kiosk Mode for reception tablets / self-service terminals.
     */
    public function kiosk(): void
    {
        $this->load->view('pages/kiosk', [
            'company_name' => setting('company_name') ?: 'BooKi',
            'company_logo' => setting('company_logo') ?: base_url('assets/img/logo.png'),
            'touchless_url' => site_url('checkin/mobile'),
        ]);
    }

    /**
     * Mobile Touchless Check-in / Check-out page (opened by customer via Kiosk QR).
     */
    public function mobile(): void
    {
        $this->load->view('pages/mobile_checkin', [
            'company_name' => setting('company_name') ?: 'BooKi',
            'company_logo' => setting('company_logo') ?: base_url('assets/img/logo.png'),
        ]);
    }

    /**
     * Unified Kiosk Action (Giriş / Çıkış / QR / Auto AJAX).
     */
    public function do_kiosk_action(): void
    {
        $identifier = $this->input->post('identifier') ?: ($this->input->post('phone') ?: $this->input->post('qr_token'));
        $action = $this->input->post('action') ?: 'auto';
        $method = $this->input->post('checkin_method') ?: 'kiosk';

        try {
            $res = $this->checkin_model->kiosk_process((string) $identifier, (string) $action, (string) $method);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($res));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Ensure staff member (admin, provider, secretary) is authenticated.
     */
    protected function ensure_staff_authenticated(): void
    {
        $user_id = $this->session->userdata('user_id');
        $role_slug = $this->session->userdata('role_slug');

        if (!$user_id || $role_slug === DB_SLUG_CUSTOMER) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
            exit;
        }
    }

    /**
     * Process Check-in (AJAX).
     */
    public function do_checkin(): void
    {
        $this->ensure_staff_authenticated();

        $params = [
            'id_users_customer' => $this->input->post('id_users_customer') ?: null,
            'phone' => $this->input->post('phone') ?: null,
            'qr_token' => $this->input->post('qr_token') ?: null,
            'id_appointments' => $this->input->post('id_appointments') ?: null,
            'id_customer_packages' => $this->input->post('id_customer_packages') ?: null,
            'checkin_method' => $this->input->post('checkin_method') ?: 'manual',
            'checked_in_by' => $this->session->userdata('user_id'),
            'notes' => $this->input->post('notes') ?: null,
        ];

        try {
            $res = $this->checkin_model->check_in($params);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($res));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Process Check-out (AJAX).
     */
    public function do_checkout(): void
    {
        $this->ensure_staff_authenticated();

        $checkin_id = (int) $this->input->post('checkin_id');

        try {
            $res = $this->checkin_model->check_out($checkin_id);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($res));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    /**
     * Live Occupancy JSON polling endpoint (Staff only).
     */
    public function live_status(): void
    {
        $this->ensure_staff_authenticated();

        $occupancy = $this->checkin_model->get_live_occupancy();
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($occupancy));
    }
}
