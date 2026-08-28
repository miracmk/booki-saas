<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * "Müşteri Paneli" (2026-08-26) - a logged-in customer's own view of their appointment history and
 * profile. Distinct from the existing hash-link "reschedule my appointment" flow in Booking.php
 * (unauthenticated, still works, unchanged) - this is the new, login-based alternative. Only the
 * 'customer' role reaches here; every other role is redirected to the calendar/backend they already
 * use (see Login::validate()'s role-aware redirect).
 */
class Customer_portal extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('customers_model');
        $this->load->model('services_model');
        $this->load->model('providers_model');
        $this->load->model('packages_model'); // Multi-session packages

        // Faz 30 (KVKK) - download_export() is reached via a one-time emailed link, which may well
        // arrive after the customer's browser session has expired (exports take time to build). The
        // token itself IS the credential here (see download_export()'s own hash check), exactly like
        // Recovery::reset() needing no login - so this one action is exempt from the login gate.
        if (is_callback('Customer_portal', 'download_export')) {
            return;
        }

        if (!session('user_id') || session('role_slug') !== DB_SLUG_CUSTOMER) {
            redirect('login');
            exit();
        }
    }

    public function index(): void
    {
        method('get');

        $customer_id = (int) session('user_id');
        $customer = $this->customers_model->find($customer_id);

        $appointments = $this->appointments_model->get(['id_users_customer' => $customer_id], null, null, 'start_datetime');

        $now = date('Y-m-d H:i:s');
        $upcoming = array_values(array_filter($appointments, fn($a) => $a['start_datetime'] >= $now));
        $past = array_values(array_filter($appointments, fn($a) => $a['start_datetime'] < $now));
        usort($past, fn($a, $b) => strcmp($b['start_datetime'], $a['start_datetime']));

        $decorate = function (array $appointment) {
            $service = $this->services_model->find($appointment['id_services']);
            $provider = $this->providers_model->find($appointment['id_users_provider']);

            $appointment['service_name'] = $service['name'] ?? '';
            $appointment['provider_name'] = trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? ''));

            return $appointment;
        };

        // Get customer's active packages
        $customer_packages = $this->packages_model->get_for_customer($customer_id);

        html_vars([
            'page_title' => 'Randevularım',
            'csrf_token' => $this->security->get_csrf_hash(),
            'customer' => $customer,
            'upcoming_appointments' => array_map($decorate, $upcoming),
            'past_appointments' => array_map($decorate, $past),
            'customer_packages' => $customer_packages,
        ]);

        $this->load->view('pages/customer_portal');
    }

    public function update_profile(): void
    {
        try {
            method('post');

            check('first_name', 'string');
            check('last_name', 'string|null');
            check('email', 'string|null');
            check('phone_number', 'string|null');
            check('address', 'string|null');
            check('city', 'string|null');

            $customer_id = (int) session('user_id');

            $this->customers_model->save([
                'id' => $customer_id,
                'first_name' => request('first_name'),
                'last_name' => request('last_name'),
                'email' => request('email'),
                'phone_number' => request('phone_number'),
                'address' => request('address'),
                'city' => request('city'),
            ]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Self-service password change - the admin who assigns a customer's initial login
     * (Customers::set_login()) has no way to hand over anything but that one password, so this is
     * the only way a customer can ever set their own. "Forgot password" is deliberately NOT built -
     * this deployment's SMTP isn't configured yet (see README's known limitations), so a reset email
     * couldn't be sent anyway.
     */
    public function change_password(): void
    {
        try {
            method('post');

            check('current_password', 'string');
            check('new_password', 'string');

            $customer_id = (int) session('user_id');
            $current_password = (string) request('current_password');
            $new_password = (string) request('new_password');

            if (strlen($new_password) < 8) {
                throw new InvalidArgumentException('Yeni şifre en az 8 karakter olmalı.');
            }

            $settings = $this->db->get_where('user_settings', ['id_users' => $customer_id])->row_array();

            if (!$settings || !verify_password($settings['salt'], $current_password, $settings['password'])) {
                throw new InvalidArgumentException('Mevcut şifre yanlış.');
            }

            $this->customers_model->set_login_credentials($customer_id, $settings['username'], $new_password);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Faz 30 (KVKK) - list the logged-in customer's own data requests (export history + any
     * pending erasure request), for the portal's KVKK card.
     */
    public function data_requests(): void
    {
        try {
            method('get');

            $this->load->model('data_requests_model');

            $customer_id = (int) session('user_id');

            json_response([
                'success' => true,
                'requests' => $this->data_requests_model->get_for_customer($customer_id),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Faz 30 (KVKK) - "right to access": the customer requests a copy of their own data. Reuses
     * the same queue gate pattern as Notifications::do_send_*() - if the queue is enabled, this
     * returns immediately and Data_export::handle_queued_export() builds it in the background;
     * otherwise it builds synchronously here (small deployments, or queue temporarily disabled).
     */
    public function request_export(): void
    {
        try {
            method('post');

            $this->load->model('data_requests_model');

            $customer_id = (int) session('user_id');

            if ($this->data_requests_model->has_open_export($customer_id)) {
                throw new InvalidArgumentException(
                    'Zaten bekleyen veya hazır bir dışa aktarma talebiniz var. Lütfen önce onu tamamlayın.',
                );
            }

            $request_id = $this->data_requests_model->insert_request(
                $customer_id,
                'export',
                null,
                $this->input->ip_address(),
            );

            $this->load->library('queue');

            $queued = $this->queue->enabled() &&
                $this->queue->push('email', 'data_requests.export', ['request_id' => $request_id]) !== null;

            if (!$queued) {
                $this->load->library('data_export');
                $this->data_export->handle_queued_export($this, ['request_id' => $request_id]);
            }

            json_response(['success' => true, 'request_id' => $request_id]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Faz 30 (KVKK) - "right to erasure": the customer requests their account be anonymized. This
     * only RECORDS the request as pending - actual anonymization needs staff review (accounting/
     * invoice retention under VUK can legitimately block an immediate erasure), performed from the
     * admin Data_requests panel, which calls Customers_model::anonymize() and then mark_completed().
     */
    public function request_erasure(): void
    {
        try {
            method('post');

            $this->load->model('data_requests_model');

            $customer_id = (int) session('user_id');

            if ($this->data_requests_model->has_open_erasure($customer_id)) {
                throw new InvalidArgumentException('Zaten bekleyen bir silme talebiniz var.');
            }

            $request_id = $this->data_requests_model->insert_request(
                $customer_id,
                'erasure',
                null,
                $this->input->ip_address(),
            );

            json_response(['success' => true, 'request_id' => $request_id]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Faz 30 (KVKK) - resend the download link for an already-ready export. A fresh token is
     * generated and re-hashed into the SAME data_requests row (the original raw token was never
     * stored, so it cannot be recovered - see Data_requests_model::mark_ready()'s docblock); the
     * already-built file on disk is reused as-is.
     */
    public function request_download_link(): void
    {
        try {
            method('post');

            check('request_id', 'numeric');

            $this->load->model('data_requests_model');
            $this->load->library('email_messages');

            $customer_id = (int) session('user_id');
            $request_id = (int) request('request_id');

            $request = $this->data_requests_model->find($request_id);

            if (!$request || $request['id_users'] !== $customer_id) {
                throw new InvalidArgumentException('Talep bulunamadı.');
            }

            if ($request['status'] !== 'ready') {
                throw new InvalidArgumentException('Bu talep henüz hazır değil veya süresi dolmuş.');
            }

            $customer = $this->customers_model->find($customer_id);

            if (empty($customer['email'])) {
                throw new InvalidArgumentException('Hesabınızda kayıtlı bir e-posta adresi yok.');
            }

            $token = bin2hex(random_bytes(32));

            $this->data_requests_model->mark_ready(
                $request_id,
                hash('sha256', $token),
                date('Y-m-d H:i:s', strtotime('+72 hours')),
                $request['file_path'],
                $request['file_size'],
                $request['format'],
            );

            $settings = [
                'company_name' => setting('company_name'),
                'company_link' => setting('company_link'),
                'company_email' => setting('company_email'),
            ];

            $download_link = site_url('customer_portal/download_export/' . $request_id . '/' . $token);

            $this->email_messages->send_data_export_ready($download_link, '72 saat', $customer['email'], $settings);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Faz 30 (KVKK) - serve a ready export file. Reachable without a login session (see the
     * constructor's is_callback() exemption) - the raw token in the URL is the only credential,
     * exactly like Recovery::reset()'s password-reset link. The realpath()-prefix check mirrors
     * Customers_model::invalidate_data_exports()'s path-traversal guard.
     *
     * @param int $request_id
     * @param string $token
     */
    public function download_export(int $request_id, string $token): void
    {
        $this->load->model('data_requests_model');

        $request = $this->data_requests_model->find($request_id);

        if (
            !$request ||
            $request['status'] !== 'ready' ||
            empty($request['token_hash']) ||
            !hash_equals($request['token_hash'], hash('sha256', $token))
        ) {
            abort(404, 'Bağlantı geçersiz veya süresi dolmuş.');
        }

        if (!empty($request['expires']) && $request['expires'] < date('Y-m-d H:i:s')) {
            abort(404, 'Bağlantının süresi dolmuş.');
        }

        $base = realpath(storage_path('exports'));
        $abs = $base !== false ? realpath(storage_path($request['file_path'])) : false;

        if ($abs === false || strpos($abs, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($abs)) {
            abort(404, 'Dosya bulunamadı.');
        }

        $content_type = $request['format'] === 'zip' ? 'application/zip' : 'application/json';
        $download_name = $request['format'] === 'zip' ? 'veri-disa-aktarma.zip' : 'veri-disa-aktarma.json';

        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . $download_name . '"');
        header('Content-Length: ' . filesize($abs));

        readfile($abs);
        exit();
    }
}
