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
 * Calendar controller.
 *
 * Handles calendar related operations.
 *
 * @package Controllers
 */
class Calendar extends EA_Controller
{
    public array $allowed_customer_fields = [
        'id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'address',
        'city',
        'state',
        'zip_code',
        'timezone',
        'language',
        'notes',
        'custom_field_1',
        'custom_field_2',
        'custom_field_3',
        'custom_field_4',
        'custom_field_5',
    ];

    public array $optional_customer_fields = [
        //
    ];

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
        'id_stations', // Salon Flora customization
        'station_assigned_manually', // Salon Flora customization - set server-side above, never trust the client for this
        'custom_duration_minutes', // Salon Flora customization - booking-time duration override (admin/secretary only)
        'price_override', // Salon Flora customization - booking-time fixed price override (admin/secretary only)
    ];

    public array $optional_appointment_fields = [
        //
    ];

    /**
     * Calendar constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('unavailabilities_model');
        $this->load->model('blocked_periods_model');
        $this->load->model('customers_model');
        $this->load->model('services_model');
        $this->load->model('providers_model');
        $this->load->model('roles_model');
        $this->load->model('stations_model'); // Salon Flora customization
        $this->load->model('packages_model'); // Multi-session packages
        $this->load->model('products_model'); // Inventory management

        $this->load->library('accounts');
        $this->load->library('google_sync');
        $this->load->library('notifications');
        $this->load->library('synchronization');
        $this->load->library('timezones');
        $this->load->library('webhooks_client');
        $this->load->library('permissions');
        $this->load->library('jitsi_client');
        $this->load->library('availability');
    }

    /**
     * Render the calendar page and display the selected appointment.
     *
     * This method will call the "index" callback to handle the page rendering.
     *
     * @param string $appointment_hash Appointment hash.
     */
    public function reschedule(string $appointment_hash): void
    {
        $this->index($appointment_hash);
    }

    /**
     * Display the main backend page.
     *
     * This method displays the main backend page. All login permission can view this page which displays a calendar
     * with the events of the selected provider or service. If a user has more privileges he will see more menus at the
     * top of the page.
     *
     * @param string $appointment_hash Appointment hash.
     */
    public function index(string $appointment_hash = ''): void
    {
        method('get');

        session([
            'dest_url' => site_url('calendar/index' . (!empty($appointment_hash) ? '/' . $appointment_hash : '')),
        ]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_APPOINTMENTS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        $user = $this->users_model->find($user_id);

        $secretary_providers = [];

        if ($role_slug === DB_SLUG_SECRETARY) {
            $secretary = $this->secretaries_model->find(session('user_id'));

            $secretary_providers = $secretary['providers'];
        }

        $edit_appointment = null;

        if (!empty($appointment_hash)) {
            $occurrences = $this->appointments_model->get(['hash' => $appointment_hash]);

            if ($appointment_hash !== '' && !empty($occurrences)) {
                $edit_appointment = $occurrences[0];

                $this->appointments_model->load($edit_appointment, ['customer']);

                // Salon Flora customization - providers must only ever see a customer's first name.
                $edit_appointment['customer'] = $this->appointments_model->filter_customer_for_role(
                    $edit_appointment['customer'],
                    $role_slug,
                );
            }
        }

        $privileges = $this->roles_model->get_permissions_by_slug($role_slug);

        $available_providers = $this->providers_model->get_available_providers();

        if ($role_slug === DB_SLUG_PROVIDER) {
            $available_providers = array_values(
                array_filter($available_providers, function ($available_provider) use ($user_id) {
                    return (int) $available_provider['id'] === (int) $user_id;
                }),
            );
        }

        if ($role_slug === DB_SLUG_SECRETARY) {
            $available_providers = array_values(
                array_filter($available_providers, function ($available_provider) use ($secretary_providers) {
                    return in_array($available_provider['id'], $secretary_providers);
                }),
            );
        }

        // Salon Flora customization - let the frontend filter the station dropdown down to only the stations
        // each provider is actually assigned to (used when picking a station at booking time, before an
        // appointment ID exists to call update_station with).
        foreach ($available_providers as &$available_provider) {
            $available_provider['station_ids'] = $this->providers_model->get_station_ids((int) $available_provider['id']);
        }

        unset($available_provider);

        $available_services = $this->services_model->get_available_services();

        // Filter services to only include those that can be served by at least one available provider
        $provider_service_ids = [];
        foreach ($available_providers as $provider) {
            foreach ($provider['services'] as $service_id) {
                $provider_service_ids[$service_id] = true;
            }
        }

        $available_services = array_values(
            array_filter($available_services, function ($service) use ($provider_service_ids) {
                return isset($provider_service_ids[$service['id']]);
            }),
        );

        $calendar_view = request('view', $user['settings']['calendar_view']);

        $appointment_status_options = setting('appointment_status_options');

        $customers = $this->customers_model->get(null, 50, null, 'update_datetime DESC');

        if (setting('limit_customer_access') && $role_slug === DB_SLUG_PROVIDER) {
            // Only include the customers that the provider is supposed to see (they had past booking together)
            $CI = $this;

            $customers = array_values(
                array_filter($customers, function ($customer) use ($user_id, $CI) {
                    if (!$CI->permissions->has_customer_access($user_id, $customer['id'])) {
                        return false;
                    }

                    return true;
                }),
            );
        }

        // Salon Flora customization - providers must only ever see a customer's first name, never surname,
        // phone, email or address, in the customer picker either.
        if ($role_slug === DB_SLUG_PROVIDER) {
            $customers = array_map(
                fn(array $customer) => $this->appointments_model->filter_customer_for_role($customer, $role_slug),
                $customers,
            );
        }

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'date_format' => setting('date_format'),
            'time_format' => setting('time_format'),
            'first_weekday' => setting('first_weekday'),
            'company_working_plan' => setting('company_working_plan'),
            'timezones' => $this->timezones->to_array(),
            'privileges' => $privileges,
            'calendar_view' => $calendar_view,
            'available_providers' => filter_sensitive_users_data($available_providers),
            'available_services' => $available_services,
            'secretary_providers' => $secretary_providers,
            'edit_appointment' => $edit_appointment,
            'google_sync_feature' => filter_var(
                setting('google_sync_feature') ?: config('google_sync_feature'),
                FILTER_VALIDATE_BOOLEAN,
            ),
            'customers' => $customers,
            'default_language' => setting('default_language'),
            'default_timezone' => setting('default_timezone'),
            'stations' => $this->stations_model->to_options(), // Salon Flora customization
            'session_thresholds' => [
                // Salon Flora customization
                'tolerance_minutes' => (int) setting('session_deviation_tolerance_minutes', SESSION_DEVIATION_TOLERANCE_MINUTES_DEFAULT),
                'duration_baseline' => setting('session_duration_baseline', 'check_in'),
                'warning_minutes' => SESSION_WARNING_THRESHOLD_MINUTES,
                'late_start_grace_minutes' => SESSION_LATE_START_GRACE_MINUTES,
                'end_prompt_snooze_minutes' => SESSION_END_PROMPT_SNOOZE_MINUTES,
            ],
            'early_exit_reason_codes' => EARLY_EXIT_REASON_CODES, // Salon Flora customization
            // Salon Flora customization - payment method options for the collection dialog.
            'payment_methods' => [
                ['value' => 'iban', 'label' => 'IBAN'],
                ['value' => 'physical_pos', 'label' => 'Fiziki POS'],
                ['value' => 'virtual_pos', 'label' => 'Sanal POS'],
                ['value' => 'cash', 'label' => 'Nakit'],
            ],
            'can_manage_payment' => $role_slug !== DB_SLUG_PROVIDER,
        ]);

        html_vars([
            'page_title' => lang('calendar'),
            'active_menu' => PRIV_APPOINTMENTS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'timezone' => session('timezone'),
            'timezones' => $this->timezones->to_array(),
            'grouped_timezones' => $this->timezones->to_grouped_array(),
            'privileges' => $privileges,
            'calendar_view' => $calendar_view,
            'available_providers' => filter_sensitive_users_data($available_providers),
            'available_services' => $available_services,
            'secretary_providers' => $secretary_providers,
            'appointment_status_options' => json_decode($appointment_status_options, true) ?? [],
            'require_first_name' => setting('require_first_name'),
            'require_last_name' => setting('require_last_name'),
            'require_email' => setting('require_email'),
            'require_phone_number' => setting('require_phone_number'),
            'require_address' => setting('require_address'),
            'require_city' => setting('require_city'),
            'require_zip_code' => setting('require_zip_code'),
            'require_notes' => setting('require_notes'),
        ]);

        $this->load->view('pages/calendar');
    }

    /**
     * Save appointment changes that are made from the backend calendar page.
     */
    public function save_appointment(): void
    {
        try {
            method('post');

            check('customer_data', 'array|null');
            check('appointment_data', 'array');
            // Salon Flora customization - notifications are opt-in per recipient group (customer /
            // provider / admin), not a single all-or-nothing flag.
            check('notify_customer', 'bool|null');
            check('notify_provider', 'bool|null');
            check('notify_admin', 'bool|null');
            check('force_save', 'bool|null');

            $customer_data = request('customer_data');

            $appointment_data = request('appointment_data');

            $notify_customer = filter_var(request('notify_customer', true), FILTER_VALIDATE_BOOLEAN);
            $notify_provider = filter_var(request('notify_provider', true), FILTER_VALIDATE_BOOLEAN);
            $notify_admin = filter_var(request('notify_admin', true), FILTER_VALIDATE_BOOLEAN);

            $force_save = filter_var(request('force_save', false), FILTER_VALIDATE_BOOLEAN);

            $this->check_event_permissions((int) $appointment_data['id_users_provider']);

            // Save customer changes to the database.
            if ($customer_data) {
                $customer = $customer_data;

                // Salon Flora bugfix - was inverted (checked 'add' for an existing id, 'edit' for a new one).
                $required_permissions = !empty($customer['id'])
                    ? can('edit', PRIV_CUSTOMERS)
                    : can('add', PRIV_CUSTOMERS);

                if (!$required_permissions) {
                    throw new RuntimeException('You do not have the required permissions for this task.');
                }

                // Salon Flora customization: providers only ever see a customer's first name in the UI, so their
                // form never carries real values for the other fields (they arrive blank). Restore the existing
                // record's values for everything except first_name before saving, so a provider can never
                // silently wipe out a customer's surname/phone/email/address just by submitting the form.
                if (session('role_slug') === DB_SLUG_PROVIDER && !empty($customer['id'])) {
                    $existing_customer = $this->customers_model->find($customer['id']);

                    $customer = array_merge($existing_customer, ['first_name' => $customer['first_name'] ?? $existing_customer['first_name']]);
                }

                $this->customers_model->only($customer, $this->allowed_customer_fields);

                $this->customers_model->optional($customer, $this->optional_customer_fields);

                // Reuse existing customers by email (same behavior as public booking).
                if (empty($customer['id']) && $this->customers_model->exists($customer)) {
                    $customer['id'] = $this->customers_model->find_record_id($customer);
                }

                $customer['id'] = $this->customers_model->save($customer);
            }

            // Save appointment changes to the database.
            $manage_mode = !empty($appointment_data['id']);

            if ($appointment_data) {
                $appointment = $appointment_data;

                // Salon Flora bugfix - was inverted (checked 'add' for an existing id, 'edit' for a new one).
                $required_permissions = !empty($appointment['id'])
                    ? can('edit', PRIV_APPOINTMENTS)
                    : can('add', PRIV_APPOINTMENTS);

                if (!$required_permissions) {
                    throw new RuntimeException('You do not have the required permissions for this task.');
                }

                // If the appointment does not contain the customer record id, then it means that is going to be inserted.

                if (!isset($appointment['id_users_customer'])) {
                    // Salon Flora bugfix - $customer_data can be null (no customer changes submitted), which
                    // made the fallback throw a "trying to access array offset on null" warning.
                    $appointment['id_users_customer'] = $customer['id'] ?? ($customer_data['id'] ?? null);
                }

                // Check if the provider has a conflicting appointment at the selected time.
                $exclude_appointment_id = !empty($appointment['id']) ? (int) $appointment['id'] : null;

                $provider_conflict = $this->appointments_model->has_provider_conflict(
                    (int) $appointment['id_users_provider'],
                    $appointment['start_datetime'],
                    $appointment['end_datetime'],
                    $exclude_appointment_id,
                );

                // Salon Flora customization: stations are tied to SERVICES (which stations can host this
                // service), not to providers - a provider can work in any station their service is available in,
                // unless they have an explicit station_restriction_enabled override (see
                // Stations_model::get_candidate_station_ids()). Three cases, in priority order:
                //
                // 1. Staff explicitly picked a station in THIS request (id_stations present and non-empty in the
                //    raw payload) - covers both a brand new appointment (picked at booking time, before an
                //    appointment ID even exists) and an existing one saved through a path other than the live
                //    station dropdown.
                // 2. No explicit choice in this request, but the appointment already has a manually-assigned
                //    station (set earlier via the dropdown's live update_station call) - keep it, PROVIDED it's
                //    still a valid and free candidate (the service or time may have changed since it was picked -
                //    re-validating here prevents a stale manual assignment from silently surviving a save).
                // 3. Neither of the above - fall back to automatic assignment (first free candidate).
                //
                // A busy/unavailable station used to be a hard, non-overridable error - now (2026-08-25) an
                // admin/secretary may force through it exactly like a provider conflict, resolved into an
                // UNASSIGNED station (id_stations = null) rather than double-booking the room in the data model
                // - see the $force_save handling below. Only "this provider isn't set up for any station this
                // service needs" stays a hard config error (there is nothing sensible to override into).
                $requested_station_id = array_key_exists('id_stations', $appointment_data) && $appointment_data['id_stations'] !== null && $appointment_data['id_stations'] !== ''
                    ? (int) $appointment_data['id_stations']
                    : null;

                $existing_appointment = $manage_mode ? $this->appointments_model->find((int) $appointment['id']) : null;

                $candidate_station_ids = $this->stations_model->get_candidate_station_ids(
                    (int) $appointment['id_services'],
                    (int) $appointment['id_users_provider'],
                );

                // Salon Flora customization - hold a lock on every candidate station from here until the
                // appointment is actually saved further below (released after the save() call), covering all
                // three assignment paths (manual pick, keep-existing-manual, auto-assign) - see
                // Stations_model::acquire_station_locks() for why this can never leak past this request.
                $station_locks_held = [];

                if (!empty($candidate_station_ids)) {
                    if (!$this->stations_model->acquire_station_locks($candidate_station_ids)) {
                        throw new RuntimeException(
                            'İstasyon uygunluğu kontrol edilirken bir sorun oluştu. Lütfen tekrar deneyin.',
                        );
                    }

                    $station_locks_held = $candidate_station_ids;
                }

                if (empty($candidate_station_ids)) {
                    throw new RuntimeException('Bu hizmet sağlayıcı, bu hizmetin bağlı olduğu hiçbir istasyonda çalışacak şekilde tanımlanmamış.');
                }

                $keep_manual_station = false;

                if (
                    !$requested_station_id &&
                    $existing_appointment &&
                    !empty($existing_appointment['station_assigned_manually']) &&
                    in_array((int) $existing_appointment['id_stations'], $candidate_station_ids, true) &&
                    $this->stations_model->is_station_free(
                        (int) $existing_appointment['id_stations'],
                        $appointment['start_datetime'],
                        $appointment['end_datetime'],
                        $exclude_appointment_id,
                    )
                ) {
                    $keep_manual_station = true;
                }

                $station_conflict = false;
                $resolved_station_id = null;
                $resolved_station_manual = false;

                if ($requested_station_id) {
                    if (!in_array($requested_station_id, $candidate_station_ids, true)) {
                        throw new InvalidArgumentException('Seçilen istasyon bu hizmet için uygun değil.');
                    }

                    if (
                        $this->stations_model->is_station_free(
                            $requested_station_id,
                            $appointment['start_datetime'],
                            $appointment['end_datetime'],
                            $exclude_appointment_id,
                        )
                    ) {
                        $resolved_station_id = $requested_station_id;
                        $resolved_station_manual = true;
                    } else {
                        $station_conflict = true;
                    }
                } elseif ($keep_manual_station) {
                    $resolved_station_id = $existing_appointment['id_stations'];
                    $resolved_station_manual = true;
                } else {
                    $free_station_id = $this->stations_model->find_free_station(
                        $candidate_station_ids,
                        $appointment['start_datetime'],
                        $appointment['end_datetime'],
                        $exclude_appointment_id,
                    );

                    if ($free_station_id !== null) {
                        $resolved_station_id = $free_station_id;
                        $resolved_station_manual = false;
                    } else {
                        $station_conflict = true;
                    }
                }

                if (($provider_conflict || $station_conflict) && !$force_save) {
                    if ($provider_conflict && $station_conflict) {
                        $message = lang('provider_and_station_have_conflicting_appointment');
                    } elseif ($provider_conflict) {
                        $message = lang('provider_has_conflicting_appointment');
                    } else {
                        $message = lang('station_has_conflicting_appointment');
                    }

                    json_response([
                        'success' => false,
                        'conflict' => true,
                        'conflicts' => array_values(
                            array_filter([$provider_conflict ? 'provider' : null, $station_conflict ? 'station' : null]),
                        ),
                        'message' => $message,
                    ]);
                    return;
                }

                // Salon Flora customization - conflict_override* are never client-writable; computed here,
                // reapplied further below (after only()/optional() would otherwise strip them).
                $conflict_override = null;
                $conflict_override_by = null;
                $conflict_override_at = null;

                if ($provider_conflict || $station_conflict) {
                    // A provider may never force through a conflict on their own appointment (self-override);
                    // only admin/secretary can. The frontend already only shows the "yine de kaydet" option in
                    // the shared conflict dialog, but this is the actual enforcement point - never trust the
                    // client to have honored that.
                    if (session('role_slug') === DB_SLUG_PROVIDER) {
                        throw new RuntimeException(lang('conflict_override_forbidden_for_provider'));
                    }

                    if ($station_conflict) {
                        $resolved_station_id = null;
                        $resolved_station_manual = false;
                    }

                    $conflict_override = implode(
                        ',',
                        array_filter([$provider_conflict ? 'provider' : null, $station_conflict ? 'station' : null]),
                    );
                    $conflict_override_by = session('user_id');
                    $conflict_override_at = date('Y-m-d H:i:s');
                }
                // Re-saving a previously-conflicted appointment without a conflict this time (e.g. staff moved
                // it to a free slot) clears the flag rather than leaving a stale marker - the null defaults
                // above already handle that for both new and existing appointments.

                $appointment['id_stations'] = $resolved_station_id;
                $appointment['station_assigned_manually'] = $resolved_station_manual ? 1 : 0;

                if ($manage_mode && !empty($appointment['id'])) {
                    $this->synchronization->remove_appointment_on_provider_change(
                        $appointment['id'],
                        (int) $appointment['id_users_provider'],
                    );
                }

                // Jitsi integration: if enabled and meeting_link is empty, generate a Jitsi meeting link
                if (setting('jitsi_enabled') === '1' && empty($appointment['meeting_link'])) {
                    $appointment['meeting_link'] = $this->jitsi_client->generate_link();
                }

                // Salon Flora customization - only admins/secretaries may set booking-time pricing overrides; a
                // provider can create/edit their own appointments but must never set a custom duration/price for
                // a session (that would let them silently affect their own commission's calculation base).
                if (session('role_slug') === DB_SLUG_PROVIDER) {
                    unset($appointment['custom_duration_minutes'], $appointment['price_override']);
                }

                if (array_key_exists('custom_duration_minutes', $appointment)) {
                    $appointment['custom_duration_minutes'] = $appointment['custom_duration_minutes'] !== null && $appointment['custom_duration_minutes'] !== ''
                        ? (int) $appointment['custom_duration_minutes']
                        : null;

                    if ($appointment['custom_duration_minutes'] !== null && $appointment['custom_duration_minutes'] < 1) {
                        throw new InvalidArgumentException('Özel süre en az 1 dakika olmalı.');
                    }
                }

                if (array_key_exists('price_override', $appointment)) {
                    $appointment['price_override'] = $appointment['price_override'] !== null && $appointment['price_override'] !== ''
                        ? (float) $appointment['price_override']
                        : null;

                    if ($appointment['price_override'] !== null && $appointment['price_override'] < 0) {
                        throw new InvalidArgumentException('Sabit fiyat negatif olamaz.');
                    }
                }

                $this->appointments_model->only($appointment, $this->allowed_appointment_fields);

                $this->appointments_model->optional($appointment, $this->optional_appointment_fields);

                // Salon Flora customization - conflict_override* are server-decided (see above), never
                // client-writable, so they're reapplied here AFTER only()/optional() strip whatever the
                // client actually sent under those keys.
                $appointment['conflict_override'] = $conflict_override;
                $appointment['conflict_override_by'] = $conflict_override_by;
                $appointment['conflict_override_at'] = $conflict_override_at;

                $appointment['id'] = $this->appointments_model->save($appointment);

                if (!empty($station_locks_held)) {
                    $this->stations_model->release_station_locks($station_locks_held);
                }
            }

            if (empty($appointment['id'])) {
                throw new RuntimeException('The appointment ID is not available.');
            }

            $appointment = $this->appointments_model->find($appointment['id']);
            $provider = $this->providers_model->find($appointment['id_users_provider']);
            $customer = $this->customers_model->find($appointment['id_users_customer']);
            $service = $this->services_model->find($appointment['id_services']);

            $company_color = setting('company_color');

            $settings = [
                'company_name' => setting('company_name'),
                'company_link' => setting('company_link'),
                'company_email' => setting('company_email'),
                'company_color' =>
                    !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
                'date_format' => setting('date_format'),
                'time_format' => setting('time_format'),
            ];

            $this->synchronization->sync_appointment_saved($appointment, $service, $provider, $customer, $settings);

            if ($notify_customer || $notify_provider || $notify_admin) {
                $this->notifications->notify_appointment_saved(
                    $appointment,
                    $service,
                    $provider,
                    $customer,
                    $settings,
                    $manage_mode,
                    $notify_customer,
                    $notify_provider,
                    $notify_admin,
                );
            }

            // Ki Reservation (Dalga 3 / Faz 3.1) - Communication Hub: appointment_created event,
            // only for genuinely NEW appointments (staff-side creation on the calendar). Edits to
            // existing appointments are not a "created" event. Best-effort by contract.
            if (!$manage_mode) {
                $this->load->library('communication_hub');
                $this->communication_hub->publish(
                    'appointment_created',
                    compact('appointment', 'service', 'provider', 'customer', 'settings'),
                );

                // Ki Reservation (Dalga 3 / Faz 3.2) - Automation Engine: same event.
                $this->load->library('automation_engine');
                $this->automation_engine->evaluate(
                    'appointment_created',
                    compact('appointment', 'service', 'provider', 'customer', 'settings'),
                );
            }

            $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_SAVE, $appointment);

            // Salon Flora customization - real-time Google Sheets sync (see Google_sheets_writer).
            $this->load->library('google_sheets_writer');
            $this->google_sheets_writer->sync_record('appointments', (int) $appointment['id'], 'upsert');

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Salon Flora customization - mark the real check-in (seans başlangıcı) time of an appointment as "now",
     * using the server clock (never trust the client's clock for this).
     */
    public function check_in(): void
    {
        try {
            method('post');

            check('appointment_id', 'numeric');

            $appointment_id = (int) request('appointment_id');

            $appointment = $this->appointments_model->find($appointment_id);

            $this->check_event_permissions((int) $appointment['id_users_provider']);

            if (!can('edit', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            $this->appointments_model->set_actual_datetime($appointment_id, 'actual_start_datetime', date('Y-m-d H:i:s'));

            json_response([
                'success' => true,
                'appointment' => $this->appointments_model->find($appointment_id),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Salon Flora customization - manually (re)send an appointment notification, without saving any change,
     * from the calendar popover's "Bildirim Gönder" button. Staff pick exactly who gets notified (customer /
     * provider / admin) via the same three-tick dialog used on save.
     */
    public function send_notification(): void
    {
        try {
            method('post');

            check('appointment_id', 'numeric');
            check('notify_customer', 'bool|null');
            check('notify_provider', 'bool|null');
            check('notify_admin', 'bool|null');

            $appointment_id = (int) request('appointment_id');

            $notify_customer = filter_var(request('notify_customer', true), FILTER_VALIDATE_BOOLEAN);
            $notify_provider = filter_var(request('notify_provider', true), FILTER_VALIDATE_BOOLEAN);
            $notify_admin = filter_var(request('notify_admin', true), FILTER_VALIDATE_BOOLEAN);

            $appointment = $this->appointments_model->find($appointment_id);

            $this->check_event_permissions((int) $appointment['id_users_provider']);

            if (!can('edit', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            $provider = $this->providers_model->find($appointment['id_users_provider']);
            $customer = $this->customers_model->find($appointment['id_users_customer']);
            $service = $this->services_model->find($appointment['id_services']);

            $company_color = setting('company_color');

            $settings = [
                'company_name' => setting('company_name'),
                'company_link' => setting('company_link'),
                'company_email' => setting('company_email'),
                'company_color' =>
                    !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
                'date_format' => setting('date_format'),
                'time_format' => setting('time_format'),
            ];

            $this->notifications->notify_appointment_saved(
                $appointment,
                $service,
                $provider,
                $customer,
                $settings,
                true,
                $notify_customer,
                $notify_provider,
                $notify_admin,
            );

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Salon Flora customization - mark the real check-out (seans bitişi) time of an appointment as "now".
     *
     * The expected session duration is always measured from the real check-in time (actual_start_datetime), not
     * from the originally booked start_datetime - a session that started late is not expected to end at the
     * originally booked end_datetime. If the real duration deviates from the service's planned duration by more
     * than the configured thresholds and no deviation_reason was supplied, the check-out is rejected (nothing is
     * written) and the client is expected to collect a reason from staff and resubmit.
     */
    public function check_out(): void
    {
        try {
            method('post');

            check('appointment_id', 'numeric');
            check('deviation_reason', 'string|null');
            // Salon Flora customization (2026-08-25) - only meaningful when the deviation type is 'early';
            // see the classification block below.
            check('early_exit_justification', 'string|null');
            check('early_exit_reason_code', 'string|null');

            $appointment_id = (int) request('appointment_id');

            $deviation_reason = request('deviation_reason');
            $early_exit_justification = request('early_exit_justification');
            $early_exit_reason_code = request('early_exit_reason_code');

            $appointment = $this->appointments_model->find($appointment_id);

            $this->check_event_permissions((int) $appointment['id_users_provider']);

            if (!can('edit', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            if (empty($appointment['actual_start_datetime'])) {
                throw new RuntimeException('Önce seansı başlatmalısınız.');
            }

            $now = date('Y-m-d H:i:s');

            if ($now < $appointment['actual_start_datetime']) {
                throw new RuntimeException('Seans bitişi başlangıçtan önce olamaz.');
            }

            $service = $this->services_model->find((int) $appointment['id_services']);

            // Salon Flora customization - honor a per-appointment custom_duration_minutes override
            // (set via the modal) the same way compute_effective_billing() does, so a session
            // deliberately booked longer/shorter than the service default doesn't get flagged as a
            // deviation just for running its own actual planned length.
            $expected_minutes = ($appointment['custom_duration_minutes'] ?? null) !== null
                ? (int) $appointment['custom_duration_minutes']
                : (int) $service['duration'];

            // Salon Flora customization (2026-08-25) - duration measured from either the real check-in
            // time or the originally booked start time, per the 'session_duration_baseline' setting (see
            // compute_effective_billing(), which this must stay in sync with).
            $baseline = setting('session_duration_baseline', 'check_in') === 'booked_start'
                ? $appointment['start_datetime']
                : $appointment['actual_start_datetime'];

            $actual_minutes = (int) round((strtotime($now) - strtotime($baseline)) / 60);

            $delta_minutes = $actual_minutes - $expected_minutes;

            $tolerance = (int) setting('session_deviation_tolerance_minutes', SESSION_DEVIATION_TOLERANCE_MINUTES_DEFAULT);

            if ($delta_minutes < -$tolerance) {
                $deviation_type = 'early';
            } elseif ($delta_minutes > $tolerance) {
                $deviation_type = 'late';
            } else {
                $deviation_type = null;
            }

            // "late" (ran over) keeps the old free-text-reason flow. "early" (left more than tolerance
            // early) now requires an explicit haklı/haksız classification from a fixed reason list instead
            // - see EARLY_EXIT_REASON_CODES.
            if ($deviation_type === 'late' && empty($deviation_reason)) {
                json_response([
                    'success' => false,
                    'requires_reason' => true,
                    'deviation' => [
                        'type' => $deviation_type,
                        'expected_minutes' => $expected_minutes,
                        'actual_minutes' => $actual_minutes,
                        'delta_minutes' => $delta_minutes,
                    ],
                ]);
                return;
            }

            $early_exit_approved_by = null;

            if ($deviation_type === 'early') {
                if (
                    empty($early_exit_justification) ||
                    !in_array($early_exit_justification, ['justified', 'unjustified'], true) ||
                    empty($early_exit_reason_code) ||
                    !array_key_exists($early_exit_reason_code, EARLY_EXIT_REASON_CODES)
                ) {
                    json_response([
                        'success' => false,
                        'requires_reason' => true,
                        'deviation' => [
                            'type' => $deviation_type,
                            'expected_minutes' => $expected_minutes,
                            'actual_minutes' => $actual_minutes,
                            'delta_minutes' => $delta_minutes,
                            'reason_codes' => EARLY_EXIT_REASON_CODES,
                        ],
                    ]);
                    return;
                }

                // Salon Flora customization - a therapist classifying their OWN early exit as "haklı" is
                // recorded but does not affect billing until an admin/secretary approves it (either by
                // being the one who performed this check-out, or later via review_early_exit()) - see
                // compute_effective_billing(). This is what "yönetici onaylayacak" actually enforces.
                if (in_array(session('role_slug'), [DB_SLUG_ADMIN, DB_SLUG_SECRETARY], true)) {
                    $early_exit_approved_by = session('user_id');
                }
            }

            $this->appointments_model->set_actual_datetime($appointment_id, 'actual_end_datetime', $now);

            $this->appointments_model->set_session_deviation(
                $appointment_id,
                $deviation_type,
                $deviation_type !== null ? $delta_minutes : null,
                $deviation_type === 'late' ? $deviation_reason : null,
            );

            $this->appointments_model->set_early_exit_justification(
                $appointment_id,
                $deviation_type === 'early' ? $early_exit_justification : null,
                $deviation_type === 'early' ? $early_exit_reason_code : null,
                $deviation_type === 'early' ? $early_exit_approved_by : null,
            );

            // Ki Reservation (Dalga 1) - membership session tracking: consume a session from an
            // active membership if one exists for this customer/service, checked BEFORE packages
            // (a membership is a recurring, already-paid-for entitlement; a package is a one-time
            // purchase - if a customer has both, the membership is used first so its per-period
            // session count reflects actual usage). Never fails the checkout - memberships are
            // optional, same non-blocking contract as packages below.
            $membership_consumed = false;

            try {
                $this->load->model('customer_memberships_model');

                $active_membership = $this->customer_memberships_model->get_active_for_customer_service(
                    (int) $appointment['id_users_customer'],
                    (int) $appointment['id_services'],
                );

                if ($active_membership) {
                    $this->customer_memberships_model->consume_session((int) $active_membership['id'], $appointment_id);
                    $membership_consumed = true;
                }
            } catch (Throwable $membership_error) {
                log_message('warning', 'Membership consumption failed for appointment ' . $appointment_id . ': ' . $membership_error->getMessage());
            }

            // Multi-session package tracking: consume a session if an active package exists
            // for this customer/service combination. Do not fail if no package is found or
            // if the consumption fails - packages are optional. Skipped if a membership session
            // was already consumed above, so one visit is never double-charged against both.
            if (!$membership_consumed) {
                try {
                    $active_package = $this->packages_model->get_active_for_customer_service(
                        (int) $appointment['id_users_customer'],
                        (int) $appointment['id_services'],
                    );

                    if ($active_package) {
                        $this->packages_model->consume_session((int) $active_package['id'], $appointment_id);
                    }
                } catch (Throwable $package_error) {
                    // Log but do not fail the checkout
                    log_message('warning', 'Package consumption failed for appointment ' . $appointment_id . ': ' . $package_error->getMessage());
                }
            }

            // Ki Reservation (Dalga 3 / Faz 3.1) - Communication Hub: appointment_completed event.
            // Best-effort - a notification hiccup must never fail the checkout itself. The legacy
            // Notifications path had NO "seans tamamlandı" send at all; this is the first one.
            try {
                $completed_provider = $this->providers_model->find($appointment['id_users_provider']);
                $completed_customer = $this->customers_model->find($appointment['id_users_customer']);

                $hub_settings = [
                    'company_name' => setting('company_name'),
                    'company_link' => setting('company_link'),
                    'company_email' => setting('company_email'),
                ];

                $this->load->library('communication_hub');
                $this->communication_hub->publish('appointment_completed', [
                    'appointment' => $appointment,
                    'service' => $service,
                    'provider' => $completed_provider,
                    'customer' => $completed_customer,
                    'settings' => $hub_settings,
                ]);

                // Ki Reservation (Dalga 3 / Faz 3.2) - Automation Engine: same event.
                $this->load->library('automation_engine');
                $this->automation_engine->evaluate('appointment_completed', [
                    'appointment' => $appointment,
                    'service' => $service,
                    'provider' => $completed_provider,
                    'customer' => $completed_customer,
                    'settings' => $hub_settings,
                ]);
            } catch (Throwable $hub_error) {
                log_message(
                    'warning',
                    'Communication Hub appointment_completed failed for #' . $appointment_id . ': ' . $hub_error->getMessage(),
                );
            }

            json_response([
                'success' => true,
                'appointment' => $this->appointments_model->find($appointment_id),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Salon Flora customization (2026-08-25) - admin/secretary approves (or overturns) a therapist's own
     * "haklı erken çıkış" claim (early_exit_justification was set at check-out, but early_exit_approved_by
     * is still null - see check_out()), or retroactively (re)classifies an older completed session (see
     * D6 - historical corrections). Only admins/secretaries may call this; a provider can never
     * self-approve their own claim through this endpoint either.
     */
    public function review_early_exit(): void
    {
        try {
            method('post');

            if (!in_array(session('role_slug'), [DB_SLUG_ADMIN, DB_SLUG_SECRETARY], true)) {
                abort(403, 'Forbidden');
            }

            check('appointment_id', 'numeric');
            check('justification', 'string');
            check('reason_code', 'string');

            $appointment_id = (int) request('appointment_id');
            $justification = (string) request('justification');
            $reason_code = (string) request('reason_code');

            if (!in_array($justification, ['justified', 'unjustified'], true)) {
                throw new InvalidArgumentException('Geçersiz sınıflandırma.');
            }

            if (!array_key_exists($reason_code, EARLY_EXIT_REASON_CODES)) {
                throw new InvalidArgumentException('Geçersiz sebep kodu.');
            }

            $appointment = $this->appointments_model->find($appointment_id);

            if (empty($appointment['actual_start_datetime']) || empty($appointment['actual_end_datetime'])) {
                throw new InvalidArgumentException('Bu randevunun tamamlanmış bir seansı yok.');
            }

            $this->appointments_model->set_early_exit_justification(
                $appointment_id,
                $justification,
                $reason_code,
                session('user_id'),
            );

            json_response([
                'success' => true,
                'appointment' => $this->appointments_model->find($appointment_id),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Salon Flora customization - reset both check-in and check-out timestamps (e.g. after a mistaken click).
     */
    public function clear_session_times(): void
    {
        try {
            method('post');

            check('appointment_id', 'numeric');

            $appointment_id = (int) request('appointment_id');

            $appointment = $this->appointments_model->find($appointment_id);

            $this->check_event_permissions((int) $appointment['id_users_provider']);

            if (!can('edit', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            // Restore package session if one was consumed (before clearing times)
            try {
                $this->packages_model->restore_session($appointment_id);
            } catch (Throwable $package_error) {
                // Log but do not fail the session clear
                log_message('warning', 'Package session restore failed for appointment ' . $appointment_id . ': ' . $package_error->getMessage());
            }

            // Ki Reservation (Dalga 1) - restore a membership session if one was consumed (before
            // clearing times) - mirrors the package restore above.
            try {
                $this->load->model('customer_memberships_model');
                $this->customer_memberships_model->restore_session($appointment_id);
            } catch (Throwable $membership_error) {
                log_message('warning', 'Membership session restore failed for appointment ' . $appointment_id . ': ' . $membership_error->getMessage());
            }

            $this->appointments_model->set_actual_datetime($appointment_id, 'actual_start_datetime', null);
            $this->appointments_model->set_actual_datetime($appointment_id, 'actual_end_datetime', null);
            $this->appointments_model->set_session_deviation($appointment_id, null, null, null);
            $this->appointments_model->set_early_exit_justification($appointment_id, null, null, null);

            json_response([
                'success' => true,
                'appointment' => $this->appointments_model->find($appointment_id),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Salon Flora customization - manually assign (or clear) the physical station an appointment uses. Separate
     * from save_appointment() on purpose: changing the station alone should not re-trigger customer
     * notifications, Google/CalDAV sync, or the conflict-resolution dialog that a full appointment save does.
     */
    /**
     * Salon Flora customization (2026-08-25) - quick "add a note" action from the calendar popover, for
     * exceptional/external circumstances staff want logged without opening the full edit modal (which
     * would also trigger the notify-users dialog, conflict checks, etc.). APPENDS to any existing notes
     * (with a timestamp + who added it) rather than overwriting - unlike the modal's free-text notes
     * field, this is meant to build up a running log, not replace it.
     */
    public function add_note(): void
    {
        try {
            method('post');

            check('appointment_id', 'numeric');
            check('note', 'string');

            $appointment_id = (int) request('appointment_id');
            $note = trim((string) request('note'));

            if ($note === '') {
                throw new InvalidArgumentException('Not boş olamaz.');
            }

            $appointment = $this->appointments_model->find($appointment_id);

            $this->check_event_permissions((int) $appointment['id_users_provider']);

            if (!can('edit', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            $author = $this->accounts->get_user_display_name((int) session('user_id'));

            $entry = '[' . date('d.m.Y H:i') . ' - ' . $author . '] ' . mb_substr($note, 0, 500);

            $updated_notes = trim(($appointment['notes'] ?? '') . "\n" . $entry);

            $this->db->update('appointments', ['notes' => $updated_notes], ['id' => $appointment_id]);

            json_response([
                'success' => true,
                'appointment' => $this->appointments_model->find($appointment_id),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function update_station(): void
    {
        try {
            method('post');

            check('appointment_id', 'numeric');
            check('station_id', 'numeric|null');

            $appointment_id = (int) request('appointment_id');
            $station_id = request('station_id') !== null ? (int) request('station_id') : null;

            $appointment = $this->appointments_model->find($appointment_id);

            $this->check_event_permissions((int) $appointment['id_users_provider']);

            if (!can('edit', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            if ($station_id !== null) {
                $candidate_station_ids = $this->stations_model->get_candidate_station_ids(
                    (int) $appointment['id_services'],
                    (int) $appointment['id_users_provider'],
                );

                if (!in_array($station_id, $candidate_station_ids, true)) {
                    throw new RuntimeException('Seçilen istasyon bu hizmet için uygun değil.');
                }

                $is_free = $this->stations_model->is_station_free(
                    $station_id,
                    $appointment['start_datetime'],
                    $appointment['end_datetime'],
                    $appointment_id,
                );

                if (!$is_free) {
                    throw new RuntimeException('Bu saatte seçilen istasyon başka bir randevu tarafından kullanılıyor.');
                }
            }

            $this->appointments_model->set_station($appointment_id, $station_id, $station_id !== null);

            json_response([
                'success' => true,
                'appointment' => $this->appointments_model->find($appointment_id),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Salon Flora customization - which providers can serve a given service and, given a start/end time, whether
     * each one is already booked then. Powers the sequential booking form's "Hizmet Sağlayıcı" step (time and
     * service are picked first, so the provider list is scoped down before it's ever shown).
     */
    public function get_available_providers(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            check('service_id', 'numeric');
            check('start_datetime', 'string');
            check('end_datetime', 'string');
            check('appointment_id', 'numeric|null');

            $service_id = (int) request('service_id');
            $start_datetime = request('start_datetime');
            $end_datetime = request('end_datetime');
            $exclude_appointment_id = request('appointment_id') !== null ? (int) request('appointment_id') : null;

            $user_id = (int) session('user_id');
            $role_slug = session('role_slug');

            $secretary_providers = [];

            if ($role_slug === DB_SLUG_SECRETARY) {
                $secretary = $this->secretaries_model->find($user_id);
                $secretary_providers = $secretary['providers'];
            }

            $providers = array_values(
                array_filter($this->providers_model->get_available_providers(), function ($provider) use (
                    $service_id,
                    $role_slug,
                    $user_id,
                    $secretary_providers,
                ) {
                    if (!in_array($service_id, array_map('intval', $provider['services']), true)) {
                        return false;
                    }

                    if ($role_slug === DB_SLUG_PROVIDER && (int) $provider['id'] !== $user_id) {
                        return false;
                    }

                    if ($role_slug === DB_SLUG_SECRETARY && !in_array((int) $provider['id'], $secretary_providers, true)) {
                        return false;
                    }

                    return true;
                }),
            );

            $result = array_map(
                fn(array $provider) => [
                    'value' => (int) $provider['id'],
                    'label' => trim($provider['first_name'] . ' ' . $provider['last_name']),
                    'is_free' => !$this->appointments_model->has_provider_conflict(
                        (int) $provider['id'],
                        $start_datetime,
                        $end_datetime,
                        $exclude_appointment_id,
                    ),
                ],
                $providers,
            );

            json_response(['success' => true, 'providers' => $result]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Salon Flora customization - which stations can host a given service and, given a start/end time, whether
     * each one is already occupied then. Powers the sequential booking form's "İstasyon" step (the last one - it
     * only makes sense once the service and provider are both known).
     */
    public function get_available_stations(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            check('service_id', 'numeric');
            check('provider_id', 'numeric');
            check('start_datetime', 'string');
            check('end_datetime', 'string');
            check('appointment_id', 'numeric|null');

            $service_id = (int) request('service_id');
            $provider_id = (int) request('provider_id');
            $start_datetime = request('start_datetime');
            $end_datetime = request('end_datetime');
            $exclude_appointment_id = request('appointment_id') !== null ? (int) request('appointment_id') : null;

            $candidate_station_ids = $this->stations_model->get_candidate_station_ids($service_id, $provider_id);

            $stations = array_filter(
                $this->stations_model->to_options(),
                fn(array $station) => in_array($station['value'], $candidate_station_ids, true),
            );

            $result = array_map(
                fn(array $station) => $station + [
                    'is_free' => $this->stations_model->is_station_free(
                        $station['value'],
                        $start_datetime,
                        $end_datetime,
                        $exclude_appointment_id,
                    ),
                ],
                array_values($stations),
            );

            json_response(['success' => true, 'stations' => $result]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Salon Flora customization - record payment/collection details for a completed appointment. Providers can
     * check a session out, but only admins/secretaries may ever record or change payment data - this is enforced
     * here (not just hidden in the UI), since it protects real financial records.
     */
    public function update_payment(): void
    {
        try {
            method('post');

            $role_slug = session('role_slug');

            if ($role_slug === DB_SLUG_PROVIDER) {
                abort(403, 'Forbidden');
            }

            check('appointment_id', 'numeric');
            check('payment_status', 'string');
            check('payment_method', 'string|null');
            check('payment_amount', 'numeric|null');
            check('payment_balance_amount', 'numeric|null');
            check('is_invoiced', 'bool');

            $appointment_id = (int) request('appointment_id');
            $payment_status = request('payment_status');
            $payment_method = request('payment_method');
            $payment_amount = request('payment_amount') !== null ? (float) request('payment_amount') : null;
            $payment_balance_amount = request('payment_balance_amount') !== null ? (float) request('payment_balance_amount') : null;
            $is_invoiced = filter_var(request('is_invoiced'), FILTER_VALIDATE_BOOLEAN);

            if (!in_array($payment_status, [PAYMENT_STATUS_COLLECTED, PAYMENT_STATUS_NOT_COLLECTED], true)) {
                throw new InvalidArgumentException('Invalid payment status provided.');
            }

            if ($payment_status === PAYMENT_STATUS_COLLECTED && !in_array($payment_method, PAYMENT_METHODS, true)) {
                throw new RuntimeException('Lütfen bir ödeme yöntemi seçin.');
            }

            $appointment = $this->appointments_model->find($appointment_id);

            $this->check_event_permissions((int) $appointment['id_users_provider']);

            if (!can('edit', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            $this->appointments_model->set_payment(
                $appointment_id,
                $payment_status,
                $payment_status === PAYMENT_STATUS_COLLECTED ? $payment_method : null,
                $payment_amount,
                $payment_balance_amount,
                $is_invoiced,
                (int) session('user_id'),
            );

            audit_log('appointment.payment_update', 'appointment', $appointment_id, [
                'payment_status' => $payment_status,
                'payment_method' => $payment_status === PAYMENT_STATUS_COLLECTED ? $payment_method : null,
            ]);

            json_response([
                'success' => true,
                'appointment' => $this->appointments_model->find($appointment_id),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Salon Flora customization - manually set (correct/backfill) an appointment's real check-in/check-out
     * timestamps. Only admins/secretaries may do this (same restriction as payment) - a provider can check a
     * session in/out but never backdate or correct those timestamps after the fact. This matters because reports
     * and hourly-rate commissions/prices are computed from these exact timestamps (see Reports.php) - an
     * uncorrected mistaken check-in/out silently miscalculates both.
     */
    public function update_session_times(): void
    {
        try {
            method('post');

            $role_slug = session('role_slug');

            if ($role_slug === DB_SLUG_PROVIDER) {
                abort(403, 'Forbidden');
            }

            check('appointment_id', 'numeric');
            check('actual_start_datetime', 'string|null');
            check('actual_end_datetime', 'string|null');

            $appointment_id = (int) request('appointment_id');
            $actual_start_datetime = request('actual_start_datetime');
            $actual_end_datetime = request('actual_end_datetime');

            $appointment = $this->appointments_model->find($appointment_id);

            $this->check_event_permissions((int) $appointment['id_users_provider']);

            if (!can('edit', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            $this->appointments_model->set_session_times($appointment_id, $actual_start_datetime, $actual_end_datetime);

            json_response([
                'success' => true,
                'appointment' => $this->appointments_model->find($appointment_id),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Salon Flora customization - list appointments that are currently "in session" (checked in but not yet
     * checked out), for the active-sessions widget. Independent of the calendar's own provider filter, since a
     * provider whose sessions are overdue should still surface here even if the calendar view is currently
     * scoped to a different provider.
     */
    public function get_active_sessions(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_APPOINTMENTS)) {
                abort(403, 'Forbidden');
            }

            $user_id = (int) session('user_id');
            $role_slug = session('role_slug');

            $provider_id = null;

            if ($role_slug === DB_SLUG_PROVIDER) {
                $provider_id = $user_id;
            }

            $appointments = $this->appointments_model->get_active_sessions($provider_id, $role_slug);

            if ($role_slug === DB_SLUG_SECRETARY) {
                $appointments = array_values(
                    array_filter(
                        $appointments,
                        fn(array $appointment) => $this->secretaries_model->is_provider_supported(
                            $user_id,
                            (int) $appointment['id_users_provider'],
                        ),
                    ),
                );
            }

            $response = [
                'success' => true,
                'appointments' => $appointments,
                'server_time' => date('Y-m-d H:i:s'),
            ];

            // Salon Flora customization - the persistent "eksik tahsilat" list. Providers never see this at all
            // (they never handle payment data), regardless of role filters above.
            if ($role_slug !== DB_SLUG_PROVIDER) {
                $unpaid_appointments = $this->appointments_model->get_unpaid_sessions(null, $role_slug);

                if ($role_slug === DB_SLUG_SECRETARY) {
                    $unpaid_appointments = array_values(
                        array_filter(
                            $unpaid_appointments,
                            fn(array $appointment) => $this->secretaries_model->is_provider_supported(
                                $user_id,
                                (int) $appointment['id_users_provider'],
                            ),
                        ),
                    );
                }

                $response['unpaid_appointments'] = $unpaid_appointments;
            }

            json_response($response);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    private function check_event_permissions(int $provider_id): void
    {
        $user_id = (int) session('user_id');
        $role_slug = session('role_slug');

        if (
            $role_slug === DB_SLUG_SECRETARY &&
            !$this->secretaries_model->is_provider_supported($user_id, $provider_id)
        ) {
            abort(403);
        }

        if ($role_slug === DB_SLUG_PROVIDER && $user_id !== $provider_id) {
            abort(403);
        }
    }

    /**
     * Delete appointment from the database.
     *
     * This method deletes an existing appointment from the database. Once this action is finished it cannot be undone.
     * Notification emails are send to both provider and customer and the delete action is executed to the Google
     * Calendar account of the provider, if the "google_sync" setting is enabled.
     */
    public function delete_appointment(): void
    {
        try {
            method('post');

            if (cannot('delete', 'appointments')) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('appointment_id', 'numeric');
            check('cancellation_reason', 'string|null');
            check('notify_users', 'bool|null');

            $appointment_id = request('appointment_id');
            $cancellation_reason = (string) request('cancellation_reason');
            $notify_users = filter_var(request('notify_users', true), FILTER_VALIDATE_BOOLEAN);

            if (empty($appointment_id)) {
                throw new InvalidArgumentException('No appointment id provided.');
            }

            // Store appointment data for later use in this method.
            $appointment = $this->appointments_model->find($appointment_id);

            $this->check_event_permissions((int) $appointment['id_users_provider']);

            $provider = $this->providers_model->find($appointment['id_users_provider']);
            $customer = $this->customers_model->find($appointment['id_users_customer']);
            $service = $this->services_model->find($appointment['id_services']);

            $company_color = setting('company_color');

            $settings = [
                'company_name' => setting('company_name'),
                'company_email' => setting('company_email'),
                'company_link' => setting('company_link'),
                'company_color' =>
                    !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
                'date_format' => setting('date_format'),
                'time_format' => setting('time_format'),
            ];

            // Ki Reservation (Dalga 3 / Faz 3.1) - Communication Hub: appointment_cancelled event.
            // Published BEFORE the DB delete so templates still see the full appointment row
            // (mirrors the webhook trigger below). Gated on the admin's explicit `notify_users`
            // choice - unchecking it means "send nothing", which must stay true for the hub too.
            if ($notify_users) {
                try {
                    $this->load->library('communication_hub');
                    $this->communication_hub->publish('appointment_cancelled', [
                        'appointment' => $appointment,
                        'service' => $service,
                        'provider' => $provider,
                        'customer' => $customer,
                        'settings' => $settings,
                        'cancellation_reason' => $cancellation_reason,
                    ]);
                } catch (Throwable $hub_error) {
                    log_message(
                        'warning',
                        'Communication Hub appointment_cancelled failed for #' . $appointment_id . ': ' . $hub_error->getMessage(),
                    );
                }

                // Ki Reservation (Dalga 3 / Faz 3.2) - Automation Engine: same event.
                try {
                    $this->load->library('automation_engine');
                    $this->automation_engine->evaluate('appointment_cancelled', [
                        'appointment' => $appointment,
                        'service' => $service,
                        'provider' => $provider,
                        'customer' => $customer,
                        'settings' => $settings,
                        'cancellation_reason' => $cancellation_reason,
                    ]);
                } catch (Throwable $auto_error) {
                    log_message(
                        'warning',
                        'Automation Engine appointment_cancelled failed for #' . $appointment_id . ': ' . $auto_error->getMessage(),
                    );
                }
            }

            // Delete appointment record from the database.
            $this->appointments_model->delete($appointment_id);

            if ($notify_users) {
                $this->notifications->notify_appointment_deleted(
                    $appointment,
                    $service,
                    $provider,
                    $customer,
                    $settings,
                    $cancellation_reason,
                );
            }

            $this->synchronization->sync_appointment_deleted($appointment, $provider);

            $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_DELETE, $appointment);

            // Ki Reservation (Dalga 1) - a cancelled appointment may free up a slot someone is
            // waiting for. Best-effort, never blocks the cancellation itself.
            try {
                $this->load->library('waitlist_service');
                $this->waitlist_service->check_and_notify_on_opening($appointment);
            } catch (Throwable $waitlist_error) {
                log_message(
                    'warning',
                    'Waitlist notify failed for appointment ' . $appointment_id . ': ' . $waitlist_error->getMessage(),
                );
            }

            // Salon Flora customization - real-time Google Sheets sync (see Google_sheets_writer).
            $this->load->library('google_sheets_writer');
            $this->google_sheets_writer->sync_record('appointments', (int) $appointment_id, 'delete');

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Insert of update unavailability to database.
     */
    public function save_unavailability(): void
    {
        try {
            method('post');

            check('unavailability', 'array');

            // Check privileges
            $unavailability = request('unavailability');

            $required_permissions = !isset($unavailability['id'])
                ? can('add', PRIV_APPOINTMENTS)
                : can('edit', PRIV_APPOINTMENTS);

            if (!$required_permissions) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            $provider_id = (int) $unavailability['id_users_provider'];

            $this->check_event_permissions($provider_id);

            $provider = $this->providers_model->find($provider_id);

            $unavailability_id = $this->unavailabilities_model->save($unavailability);

            $unavailability = $this->unavailabilities_model->find($unavailability_id);

            $this->synchronization->sync_unavailability_saved($unavailability, $provider);

            $this->webhooks_client->trigger(WEBHOOK_UNAVAILABILITY_SAVE, $unavailability);

            json_response([
                'success' => true,
                'warnings' => $warnings ?? [],
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete an unavailability from database.
     */
    public function delete_unavailability(): void
    {
        method('post');
        try {
            if (cannot('delete', PRIV_APPOINTMENTS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('unavailability_id', 'numeric');

            $unavailability_id = request('unavailability_id');

            $unavailability = $this->unavailabilities_model->find($unavailability_id);

            $this->check_event_permissions((int) $unavailability['id_users_provider']);

            $provider = $this->providers_model->find($unavailability['id_users_provider']);

            $this->unavailabilities_model->delete($unavailability_id);

            $this->synchronization->sync_unavailability_deleted($unavailability, $provider);

            $this->webhooks_client->trigger(WEBHOOK_UNAVAILABILITY_DELETE, $unavailability);

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Insert or update working plan exceptions to database.
     */
    public function save_working_plan_exception(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USERS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('working_plan_exception', 'array');
            check('provider_id', 'numeric');

            $working_plan_exception = request('working_plan_exception');

            $provider_id = request('provider_id');

            $exception_id = $this->providers_model->save_working_plan_exception($provider_id, $working_plan_exception);

            json_response([
                'success' => true,
                'id' => $exception_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete a working plan exception from database.
     */
    public function delete_working_plan_exception(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USERS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('exception_id', 'numeric');
            check('provider_id', 'numeric');

            $exception_id = request('exception_id');

            $provider_id = request('provider_id');

            $this->load->model('working_plan_exceptions_model');
            $this->working_plan_exceptions_model->delete($exception_id);

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get Calendar Events
     *
     * This method will return all the calendar events within a specified period.
     */
    public function get_calendar_appointments_for_table_view(): void
    {
        try {
            method('post');

            $required_permissions = can('view', PRIV_APPOINTMENTS);

            if (!$required_permissions) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('start_date', 'date');
            check('end_date', 'date');

            $start_date = request('start_date') . ' 00:00:00';

            $end_date = request('end_date') . ' 23:59:59';

            $response = [
                'appointments' => $this->appointments_model->get([
                    'start_datetime >=' => $start_date,
                    'end_datetime <=' => $end_date,
                ]),
                'unavailabilities' => $this->unavailabilities_model->get([
                    'start_datetime >=' => $start_date,
                    'end_datetime <=' => $end_date,
                ]),
            ];

            foreach ($response['appointments'] as &$appointment) {
                $appointment['provider'] = filter_sensitive_user_data(
                    $this->providers_model->find($appointment['id_users_provider']),
                );
                $appointment['service'] = $this->services_model->find($appointment['id_services']);
                $appointment['customer'] = $this->appointments_model->filter_customer_for_role(
                    $this->customers_model->find($appointment['id_users_customer']),
                    session('role_slug'),
                );
            }

            unset($appointment);

            $user_id = session('user_id');

            $role_slug = session('role_slug');

            // If the current user is a provider he must only see his own appointments.
            if ($role_slug === DB_SLUG_PROVIDER) {
                foreach ($response['appointments'] as $index => $appointment) {
                    if ((int) $appointment['id_users_provider'] !== (int) $user_id) {
                        unset($response['appointments'][$index]);
                    }
                }

                $response['appointments'] = array_values($response['appointments']);

                foreach ($response['unavailabilities'] as $index => $unavailability) {
                    if ((int) $unavailability['id_users_provider'] !== (int) $user_id) {
                        unset($response['unavailabilities'][$index]);
                    }
                }

                $response['unavailabilities'] = array_values($response['unavailabilities']);
            }

            // If the current user is a secretary he must only see the appointments of his providers.
            if ($role_slug === DB_SLUG_SECRETARY) {
                $providers = $this->secretaries_model->find($user_id)['providers'];

                foreach ($response['appointments'] as $index => $appointment) {
                    if (!in_array((int) $appointment['id_users_provider'], $providers)) {
                        unset($response['appointments'][$index]);
                    }
                }

                $response['appointments'] = array_values($response['appointments']);

                foreach ($response['unavailabilities'] as $index => $unavailability) {
                    if (!in_array((int) $unavailability['id_users_provider'], $providers)) {
                        unset($response['unavailabilities'][$index]);
                    }
                }

                $response['unavailabilities'] = array_values($response['unavailabilities']);
            }

            foreach ($response['unavailabilities'] as &$unavailability) {
                $unavailability['provider'] = $this->providers_model->find($unavailability['id_users_provider']);
            }

            unset($unavailability);

            // Add blocked periods to the response.
            $start_date = request('start_date');
            $end_date = request('end_date');
            $response['blocked_periods'] = $this->blocked_periods_model->get_for_period($start_date, $end_date);

            // Salon Flora customization - see the identical comment in get_calendar_appointments().
            $response['server_time'] = date('Y-m-d H:i:s');

            json_response($response);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get the registered appointments for the given date period and record.
     *
     * This method returns the database appointments and unavailability periods for the user selected date period and
     * record type (provider or service).
     */
    public function get_calendar_appointments(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_APPOINTMENTS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('record_id', 'string|numeric|null');
            check('filter_type', 'string|null');
            check('start_date', 'date');
            check('end_date', 'date');

            $record_id = request('record_id');

            $is_all = request('record_id') === FILTER_TYPE_ALL;

            $filter_type = request('filter_type');

            if (!$filter_type && !$is_all) {
                json_response([
                    'appointments' => [],
                    'unavailabilities' => [],
                ]);

                return;
            }

            // Validate filter_type to prevent SQL injection via column name
            $allowed_filter_types = [FILTER_TYPE_PROVIDER, FILTER_TYPE_SERVICE, FILTER_TYPE_ALL];
            if ($filter_type && !in_array($filter_type, $allowed_filter_types, true)) {
                throw new InvalidArgumentException('Invalid filter type provided.');
            }

            // Determine which column to filter by
            if ($filter_type == FILTER_TYPE_PROVIDER) {
                $where_id = 'id_users_provider';
            } elseif ($filter_type === FILTER_TYPE_SERVICE) {
                $where_id = 'id_services';
            } else {
                $where_id = 'id_users_provider'; // Default for FILTER_TYPE_ALL
            }

            // Validate record_id is numeric when not "all"
            if (!$is_all && !is_numeric($record_id)) {
                throw new InvalidArgumentException('Invalid record ID provided.');
            }

            // Get appointments using query builder for safety
            $start_date = request('start_date');
            $end_date = date('Y-m-d', strtotime(request('end_date') . ' +1 day'));

            // Build query using CodeIgniter's query builder for SQL injection protection
            $this->db->select('*');
            $this->db->from('appointments');

            if (!$is_all) {
                $this->db->where($where_id, $record_id);
            }

            $this->db->group_start();
            $this->db->group_start();
            $this->db->where('start_datetime >', $start_date);
            $this->db->where('start_datetime <', $end_date);
            $this->db->group_end();
            $this->db->or_group_start();
            $this->db->where('end_datetime >', $start_date);
            $this->db->where('end_datetime <', $end_date);
            $this->db->group_end();
            $this->db->or_group_start();
            $this->db->where('start_datetime <=', $start_date);
            $this->db->where('end_datetime >=', $end_date);
            $this->db->group_end();
            $this->db->group_end();

            $this->db->where('is_unavailability', 0);

            $response['appointments'] = $this->db->get()->result_array();

            foreach ($response['appointments'] as &$appointment) {
                $appointment['provider'] = filter_sensitive_user_data(
                    $this->providers_model->find($appointment['id_users_provider']),
                );
                $appointment['service'] = $this->services_model->find($appointment['id_services']);
                $appointment['customer'] = $this->appointments_model->filter_customer_for_role(
                    $this->customers_model->find($appointment['id_users_customer']),
                    session('role_slug'),
                );
            }

            unset($appointment);

            // Get unavailability periods (only for provider).
            $response['unavailabilities'] = [];

            if ($filter_type == FILTER_TYPE_PROVIDER || $is_all) {
                // Build query using CodeIgniter's query builder for SQL injection protection
                $this->db->select('*');
                $this->db->from('appointments');

                if (!$is_all) {
                    $this->db->where($where_id, $record_id);
                }

                $this->db->group_start();
                $this->db->group_start();
                $this->db->where('start_datetime >', $start_date);
                $this->db->where('start_datetime <', $end_date);
                $this->db->group_end();
                $this->db->or_group_start();
                $this->db->where('end_datetime >', $start_date);
                $this->db->where('end_datetime <', $end_date);
                $this->db->group_end();
                $this->db->or_group_start();
                $this->db->where('start_datetime <=', $start_date);
                $this->db->where('end_datetime >=', $end_date);
                $this->db->group_end();
                $this->db->group_end();

                $this->db->where('is_unavailability', 1);

                $response['unavailabilities'] = $this->db->get()->result_array();
            }

            $user_id = session('user_id');

            $role_slug = session('role_slug');

            // If the current user is a provider he must only see his own appointments.
            if ($role_slug === DB_SLUG_PROVIDER) {
                foreach ($response['appointments'] as $index => $appointment) {
                    if ((int) $appointment['id_users_provider'] !== (int) $user_id) {
                        unset($response['appointments'][$index]);
                    }
                }

                $response['appointments'] = array_values($response['appointments']);

                foreach ($response['unavailabilities'] as $index => $unavailability) {
                    if ((int) $unavailability['id_users_provider'] !== (int) $user_id) {
                        unset($response['unavailabilities'][$index]);
                    }
                }

                unset($unavailability);

                $response['unavailabilities'] = array_values($response['unavailabilities']);
            }

            // If the current user is a secretary he must only see the appointments of his providers.
            if ($role_slug === DB_SLUG_SECRETARY) {
                $providers = $this->secretaries_model->find($user_id)['providers'];

                foreach ($response['appointments'] as $index => $appointment) {
                    if (!in_array((int) $appointment['id_users_provider'], $providers)) {
                        unset($response['appointments'][$index]);
                    }
                }

                $response['appointments'] = array_values($response['appointments']);

                foreach ($response['unavailabilities'] as $index => $unavailability) {
                    if (!in_array((int) $unavailability['id_users_provider'], $providers)) {
                        unset($response['unavailabilities'][$index]);
                    }
                }

                $response['unavailabilities'] = array_values($response['unavailabilities']);
            }

            foreach ($response['unavailabilities'] as &$unavailability) {
                $unavailability['provider'] = $this->providers_model->find($unavailability['id_users_provider']);
            }

            unset($unavailability);

            // Add blocked periods to the response.
            $start_date = request('start_date');
            $end_date = request('end_date');
            $response['blocked_periods'] = $this->blocked_periods_model->get_for_period($start_date, $end_date);

            // Salon Flora customization: lets the client compute session countdowns/badges against the server's
            // clock instead of the (possibly wrong) local machine clock.
            $response['server_time'] = date('Y-m-d H:i:s');

            json_response($response);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * "İlk Müsaitlik" toolbar widget (2026-09-10) - reuses Availability::find_first_available_slots()
     * (already builds provider + station matches for the booking wizard) with a synthetic 40-minute
     * probe service, so this doesn't duplicate the working-plan/station-matching logic. Scoped to
     * TODAY only (max_days=1) - "no slot found" for today means "Müsaitlik yok", per the 40-minute
     * threshold the reception desk works with.
     */
    public function get_next_availability(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_APPOINTMENTS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('provider_id', 'numeric|null');

            $provider_id = request('provider_id') ? (int) request('provider_id') : null;

            if ($provider_id) {
                $provider = $this->providers_model->find($provider_id);
                $providers = $provider ? [$provider] : [];
            } else {
                $providers = $this->providers_model->get_available_providers();
            }

            if (empty($providers)) {
                json_response(['available' => false]);
                return;
            }

            $services = $this->services_model->get(null, 1);

            if (empty($services)) {
                json_response(['available' => false]);
                return;
            }

            // Probe duration, not a real bookable service - see the 40-minute threshold in the docblock.
            $probe_service = $services[0];
            $probe_service['duration'] = 40;

            $slots = $this->availability->find_first_available_slots($probe_service, $providers, count($providers), 1);

            // A provider with stations configured but no free one at their first open hour isn't
            // actually usable at that hour - drop it so a later provider (or none) wins instead of
            // reporting a slot the desk can't actually seat anyone in.
            $providers_by_id = [];
            foreach ($providers as $p) {
                $providers_by_id[(int) $p['id']] = $p;
            }

            $slots = array_values(array_filter($slots, function ($slot) use ($providers_by_id) {
                $p = $providers_by_id[$slot['provider_id']] ?? null;

                return $slot['station_id'] !== null || empty($p['stations'] ?? []);
            }));

            if (empty($slots)) {
                json_response(['available' => false]);
                return;
            }

            usort($slots, fn($a, $b) => strcmp($a['date'] . ' ' . $a['hour'], $b['date'] . ' ' . $b['hour']));

            $slot = $slots[0];
            $slot_start = new DateTime($slot['date'] . ' ' . $slot['hour']);
            $now = new DateTime();

            // Next appointment for that provider after this slot - defines how big the window is.
            $next_start_row = $this->db
                ->select('start_datetime')
                ->from('appointments')
                ->where('id_users_provider', $slot['provider_id'])
                ->where('is_unavailability', 0)
                ->where('start_datetime >', $slot_start->format('Y-m-d H:i:s'))
                ->order_by('start_datetime', 'asc')
                ->limit(1)
                ->get()
                ->row_array();

            $window_end = $next_start_row ? new DateTime($next_start_row['start_datetime']) : null;

            // Working-day end, from the provider's own plan (falls back to the company default) - a
            // simplification vs. find_first_available_slots() itself: working_plan_exceptions for
            // today are not re-applied here, only the base weekly plan, so this closing-time text can
            // be off on an exception day even though the actual slot search above already honored it.
            $provider = $providers_by_id[$slot['provider_id']] ?? null;
            $working_plan_json = $provider['settings']['working_plan'] ?? setting('company_working_plan');
            $working_plan = json_decode((string) $working_plan_json, true) ?: [];
            $weekday = strtolower($slot_start->format('l'));
            $day_end_time = $working_plan[$weekday]['end'] ?? null;

            if ($day_end_time) {
                $day_end = new DateTime($slot['date'] . ' ' . $day_end_time);

                if (!$window_end || $day_end < $window_end) {
                    $window_end = $day_end;
                }
            }

            $window_minutes = $window_end ? max(0, (int) round(($window_end->getTimestamp() - $slot_start->getTimestamp()) / 60)) : null;

            json_response([
                'available' => true,
                'provider_id' => $slot['provider_id'],
                'provider_name' => $slot['provider_name'],
                'station_name' => $slot['station_name'],
                'time' => $slot_start->format('H:i'),
                'is_now' => $slot_start->getTimestamp() <= $now->getTimestamp() + 300,
                'window_minutes' => $window_minutes,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
