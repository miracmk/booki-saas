<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler
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
class Appointments extends App_Controller
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
        'id_stations',
        'custom_duration_minutes',
        'price_override',
        'payment_status',
        'payment_method',
        'payment_amount',
        'id_customer_packages',
        'package_consumed',
        'consumables_cost',
        'gross_profit',
        'consumables_deducted',
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
        $this->load->model('packages_model');
    }

    /**
     * Get active package for a customer and service.
     */
    public function get_customer_package(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            check('customer_id', 'numeric');
            check('service_id', 'numeric');

            $customer_id = (int) request('customer_id');
            $service_id = (int) request('service_id');

            if ($customer_id <= 0 || $service_id <= 0) {
                json_response(['has_package' => false, 'package' => null]);
                return;
            }

            $package = $this->packages_model->get_active_for_customer_service($customer_id, $service_id);

            if ($package) {
                $service = $this->services_model->find($service_id);
                $remaining = max(0, (int) $package['total_sessions'] - (int) $package['used_sessions']);
                json_response([
                    'has_package' => ($remaining > 0),
                    'package' => [
                        'id' => (int) $package['id'],
                        'package_name' => !empty($package['name']) ? $package['name'] : (($service['name'] ?? 'Hizmet') . ' Paketi'),
                        'total_sessions' => (int) $package['total_sessions'],
                        'used_sessions' => (int) $package['used_sessions'],
                        'remaining_sessions' => $remaining,
                        'unit_price' => (float) ($package['unit_price'] ?? 0),
                        'expires_at' => !empty($package['expires_at']) ? date('d.m.Y', strtotime($package['expires_at'])) : null,
                    ],
                ]);
            } else {
                json_response(['has_package' => false, 'package' => null]);
            }
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * BooKi (2026-08-26) - "İlk Müsaitlik": given a service, find the first 3 (by default)
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
                    static fn(array $provider) => in_array((int) $service['id'], array_map('intval', $provider['services'] ?? []), true) &&
                        !empty($provider['settings']['working_plan'] ?? null),
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

            if (empty($appointment['is_unavailability']) && !empty($appointment['id_users_provider'])) {
                if ($this->appointments_model->has_provider_conflict((int) $appointment['id_users_provider'], $appointment['start_datetime'], $appointment['end_datetime'])) {
                    throw new RuntimeException('Bu saatte sağlayıcının başka bir randevusu bulunmaktadır.', 409);
                }
            }

            $appointment_id = $this->appointments_model->save($appointment);

            if (!empty($appointment['id_customer_packages'])) {
                $pkg_id = (int) $appointment['id_customer_packages'];
                $this->packages_model->consume_session($pkg_id, $appointment_id);
                $this->db->update('appointments', [
                    'id_customer_packages' => $pkg_id,
                    'package_consumed' => 1,
                    'payment_method' => 'package',
                    'payment_status' => 'completed',
                    'payment_amount' => 0.00,
                ], ['id' => $appointment_id]);
            }

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

            if (empty($appointment['is_unavailability']) && !empty($appointment['id_users_provider'])) {
                $exclude_id = !empty($appointment['id']) ? (int) $appointment['id'] : null;
                $start_dt = $appointment['start_datetime'] ?? null;
                $end_dt = $appointment['end_datetime'] ?? null;

                if ($start_dt && $end_dt && $this->appointments_model->has_provider_conflict((int) $appointment['id_users_provider'], $start_dt, $end_dt, $exclude_id)) {
                    throw new RuntimeException('Bu saatte sağlayıcının başka bir randevusu bulunmaktadır.', 409);
                }
            }

            $appointment_id = $this->appointments_model->save($appointment);

            if (!empty($appointment['id_customer_packages'])) {
                $pkg_id = (int) $appointment['id_customer_packages'];
                $existing = $this->db->get_where('customer_package_sessions', ['id_appointments' => $appointment_id])->num_rows();
                if ($existing === 0) {
                    $this->packages_model->consume_session($pkg_id, $appointment_id);
                    $this->db->update('appointments', [
                        'id_customer_packages' => $pkg_id,
                        'package_consumed' => 1,
                        'payment_method' => 'package',
                        'payment_status' => 'completed',
                        'payment_amount' => 0.00,
                    ], ['id' => $appointment_id]);
                }
            }

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

            // BooKi (Dalga 3 / Faz 3.1) - Communication Hub: appointment_cancelled event.
            // Best-effort and BEFORE the DB delete so templates still see the full appointment row.
            try {
                $this->load->model('customers_model');

                $cancelled_provider = $this->providers_model->find($appointment['id_users_provider']);
                $cancelled_customer = $this->customers_model->find($appointment['id_users_customer']);
                $cancelled_service = $this->services_model->find($appointment['id_services']);

                $hub_settings = [
                    'company_name' => setting('company_name'),
                    'company_link' => setting('company_link'),
                    'company_email' => setting('company_email'),
                ];

                $this->load->library('communication_hub');
                $this->communication_hub->publish('appointment_cancelled', [
                    'appointment' => $appointment,
                    'service' => $cancelled_service,
                    'provider' => $cancelled_provider,
                    'customer' => $cancelled_customer,
                    'settings' => $hub_settings,
                    'cancellation_reason' => 'Randevu iptal edildi',
                ]);

                // BooKi (Dalga 3 / Faz 3.2) - Automation Engine: same event.
                $this->load->library('automation_engine');
                $this->automation_engine->evaluate('appointment_cancelled', [
                    'appointment' => $appointment,
                    'service' => $cancelled_service,
                    'provider' => $cancelled_provider,
                    'customer' => $cancelled_customer,
                    'settings' => $hub_settings,
                    'cancellation_reason' => 'Randevu iptal edildi',
                ]);
            } catch (Throwable $hub_error) {
                log_message(
                    'warning',
                    'Communication Hub appointment_cancelled failed for #' . $appointment_id . ': ' . $hub_error->getMessage(),
                );
            }

            $this->appointments_model->delete($appointment_id);

            $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_DELETE, $appointment);

            // BooKi (Dalga 1) - a cancelled appointment may free up a slot someone is
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

    /**
     * Get consumables for an appointment session.
     */
    public function get_consumables(int $appointment_id): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            $this->load->model('inventory_consumables_model');
            $items = $this->inventory_consumables_model->get_appointment_consumables($appointment_id, true);
            $costs = $this->inventory_consumables_model->recalculate_appointment_costs($appointment_id);

            $appt = $this->appointments_model->find($appointment_id);

            json_response([
                'success' => true,
                'items' => $items,
                'consumables_cost' => $costs['consumables_cost'],
                'gross_profit' => $costs['gross_profit'],
                'consumables_deducted' => (int) ($appt['consumables_deducted'] ?? 0),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Save an appointment consumable (extra or adjusted item).
     */
    public function save_consumable(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            $data = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $this->load->model('inventory_consumables_model');

            $id = $this->inventory_consumables_model->save_appointment_consumable($data);
            $costs = $this->inventory_consumables_model->recalculate_appointment_costs((int) $data['id_appointments']);

            json_response([
                'success' => true,
                'id' => $id,
                'consumables_cost' => $costs['consumables_cost'],
                'gross_profit' => $costs['gross_profit'],
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete an appointment consumable entry.
     */
    public function delete_consumable(int $id): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            $this->load->model('inventory_consumables_model');
            $this->inventory_consumables_model->delete_appointment_consumable($id);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Explicitly deduct consumables stock for an appointment session.
     */
    public function deduct_consumables(int $appointment_id): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            $this->load->model('inventory_consumables_model');
            $this->inventory_consumables_model->deduct_for_appointment($appointment_id);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Revert deducted consumables stock for an appointment session.
     */
    public function revert_consumables(int $appointment_id): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            $this->load->model('inventory_consumables_model');
            $this->inventory_consumables_model->revert_for_appointment($appointment_id);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get digital waivers / consents status for an appointment.
     */
    public function get_consents(int $appointment_id): void
    {
        try {
            method('get');
            if (cannot('view', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            $this->load->model('digital_waivers_model');
            $this->load->library('legal_catalog');

            $appt = $this->appointments_model->find($appointment_id);
            if (!$appt) {
                throw new InvalidArgumentException('Randevu bulunamadı.');
            }

            $service_id = (int) $appt['id_services'];
            $service = $this->services_model->find($service_id);
            $customer = $this->customers_model->find((int) $appt['id_users_customer']);
            $provider = !empty($appt['id_users_provider']) ? $this->providers_model->find((int) $appt['id_users_provider']) : null;

            // Existing signatures
            $signatures = $this->digital_waivers_model->get_appointment_signatures($appointment_id);
            $signed_waiver_ids = array_map(function ($s) {
                return (int) $s['id_waivers'];
            }, $signatures);

            // Matched templates for this service
            $waivers = $this->db->get('digital_waivers')->result_array();
            $items = [];

            $service_category_name = '';
            if (!empty($service['id_service_categories'])) {
                $cat = $this->service_categories_model->find((int)$service['id_service_categories']);
                $service_category_name = $cat['name'] ?? '';
            }

            $start_dt = $appt['start_datetime'] ?? null;
            $appt_date = $start_dt ? date('d.m.Y', strtotime($start_dt)) : date('d.m.Y');
            $appt_time = $start_dt ? date('H:i', strtotime($start_dt)) : date('H:i');

            $context = [
                'customer_full_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
                'customer_phone' => $customer['phone_number'] ?? '',
                'customer_email' => $customer['email'] ?? '',
                'service_name' => $service['name'] ?? 'Hizmet',
                'service_category' => $service_category_name,
                'service_price' => (float)($service['price'] ?? 0),
                'provider_name' => $provider ? trim($provider['first_name'] . ' ' . $provider['last_name']) : 'Merkez Uzmanı',
                'appointment_date' => $appt_date,
                'appointment_time' => $appt_time,
                'appointment_datetime' => $appt_date . ' ' . $appt_time,
                'tenant_name' => setting('company_name') ?: 'BooKi İşletmesi',
                'tenant_legal_name' => setting('company_name') ?: 'BooKi İşletmesi',
            ];

            foreach ($waivers as $w) {
                $service_ids_str = (string) ($w['applicable_service_ids'] ?? '');
                $service_ids = array_filter(array_map('trim', explode(',', $service_ids_str)));

                $is_applicable = in_array((string)$service_id, $service_ids, true);
                if (!$is_applicable && empty($service_ids_str) && str_contains(mb_strtolower($w['title'], 'UTF-8'), 'kvkk')) {
                    $is_applicable = true;
                }

                if ($is_applicable || in_array((int)$w['id'], $signed_waiver_ids, true)) {
                    $sig = null;
                    foreach ($signatures as $s) {
                        if ((int)$s['id_waivers'] === (int)$w['id']) {
                            $sig = $s;
                            break;
                        }
                    }

                    $compiled_html = $this->legal_catalog->compile($w['content_html'], $context);

                    $items[] = [
                        'waiver_id' => (int) $w['id'],
                        'title' => $w['title'],
                        'is_mandatory' => (bool) $w['is_mandatory'],
                        'is_signed' => $sig !== null,
                        'signature' => $sig,
                        'compiled_html' => $sig ? ($sig['compiled_content_html'] ?: $compiled_html) : $compiled_html,
                    ];
                }
            }

            json_response([
                'success' => true,
                'consents' => $items,
                'signed_count' => count($signatures),
                'total_count' => count($items),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Sign a consent form for an appointment directly from staff admin panel.
     */
    public function sign_consent(): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            $data = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $appointment_id = (int) ($data['appointment_id'] ?? 0);
            $waiver_id = (int) ($data['waiver_id'] ?? 0);
            $signature_data = $data['signature_data'] ?? 'data:text/plain;base64,' . base64_encode('STAFF_VERIFIED_CONSENT');
            $compiled_html = $data['compiled_content_html'] ?? null;

            if (!$appointment_id || !$waiver_id) {
                throw new InvalidArgumentException('Geçersiz randevu veya onam formu ID.');
            }

            $appt = $this->appointments_model->find($appointment_id);
            if (!$appt) {
                throw new InvalidArgumentException('Randevu bulunamadı.');
            }

            $customer = $this->customers_model->find((int) $appt['id_users_customer']);
            $signer_name = $customer ? trim($customer['first_name'] . ' ' . $customer['last_name']) : 'Danışan';

            $this->load->model('digital_waivers_model');
            $sig_id = $this->digital_waivers_model->sign_waiver([
                'id_waivers' => $waiver_id,
                'id_appointments' => $appointment_id,
                'id_users_customer' => (int) $appt['id_users_customer'],
                'signer_full_name' => $signer_name,
                'signer_email' => $customer['email'] ?? null,
                'signer_phone' => $customer['phone_number'] ?? null,
                'signature_data' => $signature_data,
                'signature_type' => str_starts_with($signature_data, 'data:image') ? 'canvas_biometric' : 'staff_assisted',
                'compiled_content_html' => $compiled_html,
                'ip_address' => $this->input->ip_address(),
            ]);

            json_response(['success' => true, 'signature_id' => $sig_id]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}

