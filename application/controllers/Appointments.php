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
 * Appointments controller.
 *
 * Handles the appointments related operations.
 *
 * Notice: This file used to have the booking page related code which since v1.5 has now moved to the Booking.php
 * controller for improved consistency.
 *
 * @package Controllers
 */
class Appointments extends EA_Controller
{
    public array $allowed_appointment_fields = [
        'id',
        'start_datetime',
        'end_datetime',
        'location',
        'meeting_link',
        'notes',
        'color',
        'status',
        'is_unavailability',
        'id_users_provider',
        'id_users_customer',
        'id_services',
    ];

    public array $optional_appointment_fields = [
        //
    ];

    /**
     * Appointments constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('roles_model');

        $this->load->library('accounts');
        $this->load->library('timezones');
        $this->load->library('webhooks_client');
        $this->load->library('availability');
        $this->load->model('services_model');
        $this->load->model('providers_model');
    }

    /**
     * Ki Reservation (2026-08-26) - "İlk Müsaitlik": given a service, find the first 3 (by default)
     * upcoming date/hour slots across every provider assigned to it, each already matched to the
     * specific free provider + station (room) - see Availability::find_first_available_slots().
     */
    public function first_availability(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            check('service_id', 'numeric');
            check('limit', 'numeric|null');

            $service = $this->services_model->find((int) request('service_id'));

            $limit = request('limit') ? (int) request('limit') : 3;

            $available_providers = $this->providers_model->get_available_providers(true);

            $providers = array_values(
                array_filter(
                    $available_providers,
                    static fn(array $provider) => in_array($service['id'], $provider['services'], true),
                ),
            );

            $slots = $this->availability->find_first_available_slots($service, $providers, $limit);

            json_response($slots);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Support backwards compatibility for appointment links that still point to this URL.
     *
     * @param string $appointment_hash
     *
     * @deprecated Since 1.5
     */
    public function index(string $appointment_hash = ''): void
    {
        method('get');

        // Validate appointment hash format to prevent injection
        if (!empty($appointment_hash) && !preg_match('/^[a-fA-F0-9]{32}$/', $appointment_hash)) {
            abort(400, 'Invalid appointment hash format.');
        }

        redirect('booking/' . $appointment_hash);
    }

    /**
     * Filter appointments by the provided keyword.
     */
    public function search(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            check('keyword', 'string|null');
            check('order_by', 'string|null');
            check('limit', 'numeric|null');
            check('offset', 'numeric|null');

            $keyword = request('keyword', '');

            $order_by = request('order_by', 'update_datetime DESC');

            $limit = request('limit', 1000);

            $offset = (int) request('offset', '0');

            $appointments = $this->appointments_model->search($keyword, $limit, $offset, $order_by);

            $user_id = session('user_id');
            $role_slug = session('role_slug');

            // If the current user is a provider he must only see his own appointments.
            if ($role_slug === DB_SLUG_PROVIDER) {
                foreach ($appointments as $index => $appointment) {
                    if ((int) $appointment['id_users_provider'] !== (int) $user_id) {
                        unset($appointments[$index]);
                    }
                }

                $appointments = array_values($appointments);
            }

            // If the current user is a secretary he must only see the appointments of his providers.
            if ($role_slug === DB_SLUG_SECRETARY) {
                $provider_ids = $this->secretaries_model->find($user_id)['providers'];

                foreach ($appointments as $index => $appointment) {
                    if (!in_array((int) $appointment['id_users_provider'], $provider_ids)) {
                        unset($appointments[$index]);
                    }
                }

                $appointments = array_values($appointments);
            }

            json_response($appointments);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Store a new appointment.
     */
    public function store(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            check('appointment', 'json');

            $appointment = json_decode(request('appointment'), true);

            // Validate decoded appointment is an array
            if (!is_array($appointment)) {
                throw new InvalidArgumentException('Invalid appointment data provided.');
            }

            $user_id = (int) session('user_id');
            $role_slug = session('role_slug');

            if ($role_slug === DB_SLUG_PROVIDER) {
                $appointment['id_users_provider'] = $user_id;
            }

            $this->appointments_model->only($appointment, $this->allowed_appointment_fields);

            $this->appointments_model->optional($appointment, $this->optional_appointment_fields);

            $appointment_id = $this->appointments_model->save($appointment);

            $appointment = $this->appointments_model->find($appointment_id);

            $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_SAVE, $appointment);

            json_response([
                'success' => true,
                'id' => $appointment_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Find an appointment.
     */
    public function find(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            check('appointment_id', 'numeric');

            $appointment_id = request('appointment_id');

            // Validate appointment_id is a positive integer
            if (empty($appointment_id) || !filter_var($appointment_id, FILTER_VALIDATE_INT) || $appointment_id <= 0) {
                throw new InvalidArgumentException('Invalid appointment ID provided.');
            }

            $this->check_appointment_access((int) $appointment_id);

            $appointment = $this->appointments_model->find($appointment_id);

            json_response($appointment);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update a appointment.
     */
    public function update(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            check('appointment', 'json');

            $appointment = json_decode(request('appointment'), true);

            // Validate decoded appointment is an array
            if (!is_array($appointment)) {
                throw new InvalidArgumentException('Invalid appointment data provided.');
            }

            $user_id = (int) session('user_id');
            $role_slug = session('role_slug');

            if (!empty($appointment['id'])) {
                $this->check_appointment_access((int) $appointment['id']);
            }

            if ($role_slug === DB_SLUG_PROVIDER) {
                $appointment['id_users_provider'] = $user_id;
            }

            $this->appointments_model->only($appointment, $this->allowed_appointment_fields);

            $this->appointments_model->optional($appointment, $this->optional_appointment_fields);

            $appointment_id = $this->appointments_model->save($appointment);

            json_response([
                'success' => true,
                'id' => $appointment_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Remove a appointment.
     */
    public function destroy(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            check('appointment_id', 'numeric');

            $appointment_id = request('appointment_id');

            // Validate appointment_id is a positive integer
            if (empty($appointment_id) || !filter_var($appointment_id, FILTER_VALIDATE_INT) || $appointment_id <= 0) {
                throw new InvalidArgumentException('Invalid appointment ID provided.');
            }

            $this->check_appointment_access((int) $appointment_id);

            $appointment = $this->appointments_model->find($appointment_id);

            $this->appointments_model->delete($appointment_id);

            $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_DELETE, $appointment);

            // Ki Reservation (Dalga 1) - a cancelled appointment may free up a slot someone is
            // waiting for. Best-effort, never blocks the deletion itself.
            try {
                $this->load->library('waitlist_service');
                $this->waitlist_service->check_and_notify_on_opening($appointment);
            } catch (Throwable $waitlist_error) {
                log_message(
                    'warning',
                    'Waitlist notify failed for appointment ' . $appointment_id . ': ' . $waitlist_error->getMessage(),
                );
            }

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Check whether the current user has access to the appointment's provider.
     */
    private function check_appointment_access(int $appointment_id): void
    {
        $user_id = (int) session('user_id');
        $role_slug = session('role_slug');
        $appointment = $this->appointments_model->find($appointment_id);
        $provider_id = (int) $appointment['id_users_provider'];

        if (
            $role_slug === DB_SLUG_SECRETARY &&
            !$this->secretaries_model->is_provider_supported($user_id, $provider_id)
        ) {
            abort(403, 'Forbidden');
        }

        if ($role_slug === DB_SLUG_PROVIDER && $user_id !== $provider_id) {
            abort(403, 'Forbidden');
        }
    }
}
