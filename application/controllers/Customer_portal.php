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

        html_vars([
            'page_title' => 'Randevularım',
            'csrf_token' => $this->security->get_csrf_hash(),
            'customer' => $customer,
            'upcoming_appointments' => array_map($decorate, $upcoming),
            'past_appointments' => array_map($decorate, $past),
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
}
