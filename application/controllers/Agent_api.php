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
 * Agent API v1 controller.
 *
 * Server-to-server endpoints consumed by voice/text customer-representative
 * agents (e.g. an ElevenLabs conversational agent via our MCP server). Each
 * request is authorized with a per-tenant bearer token stored in the
 * `agent_api_key` setting - provisioned/rotated by an admin through the API:
 *
 *   PUT https://<tenant>/index.php/api/v1/settings/agent_api_key
 *   {"value": "<random-64-hex>"}
 *
 * Tenants with an empty `agent_api_key` respond 503, so enabling an agent is
 * an explicit admin act. All write operations reuse the same validation and
 * side-effect pipelines as the public booking flow (availability re-check,
 * station locks, notifications, webhooks, automation).
 *
 * @package Controllers
 */
class Agent_api extends EA_Controller
{
    protected array $allowed_customer_lookup_fields = [
        'id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'mobile_number',
        'city',
    ];

    /**
     * Agent_api constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');
        $this->load->model('stations_model');
        $this->load->model('settings_model');

        $this->load->library('availability');
        $this->load->library('synchronization');
        $this->load->library('notifications');
        $this->load->library('webhooks_client');
        $this->load->library('appointment_booking_service');
    }

    /**
     * Business/company info that helps an agent answer "what are you, where
     * are you, when do you operate".
     */
    public function business(): void
    {
        try {
            $this->auth();
            method('get');

            $timezone = setting('default_timezone', 'UTC');

            $working_plan = json_decode(setting('company_working_plan', '{}'), true) ?: [];

            $hours = [];

            foreach ($working_plan as $day => $plan) {
                $hours[$day] = [
                    'start' => $plan['start'] ?? null,
                    'end' => $plan['end'] ?? null,
                    'breaks' => $plan['breaks'] ?? [],
                ];
            }

            json_response([
                'success' => true,
                'business' => [
                    'name' => setting('company_name'),
                    'link' => setting('company_link'),
                    'email' => setting('company_email'),
                    'phone_number' => setting('company_phone_number'),
                    'address' => setting('company_address'),
                    'timezone' => $timezone,
                    'date_format' => setting('date_format'),
                    'time_format' => setting('time_format'),
                    'working_hours' => $hours,
                    'booking_disabled' => (bool) setting('disable_booking'),
                    'future_booking_limit' => setting('future_booking_limit'),
                ],
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Publicly bookable services.
     */
    public function services(): void
    {
        try {
            $this->auth();
            method('get');

            $services = [];

            foreach ($this->services_model->get_available_services(true) as $service) {
                $services[] = [
                    'id' => (int) $service['id'],
                    'name' => $service['name'],
                    'description' => $service['description'] ?? null,
                    'duration' => (int) ($service['duration'] ?? 0),
                    'price' => $service['price'] ?? 0,
                    'category' => $service['service_category_name'] ?? null,
                ];
            }

            json_response(['success' => true, 'services' => $services]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Publicly bookable providers with the service ids they offer.
     */
    public function providers(): void
    {
        try {
            $this->auth();
            method('get');

            $providers = [];

            foreach ($this->providers_model->get_available_providers(true) as $provider) {
                $providers[] = [
                    'id' => (int) $provider['id'],
                    'first_name' => $provider['first_name'],
                    'last_name' => $provider['last_name'],
                    'email' => $provider['email'],
                    'phone_number' => $provider['phone_number'],
                    'timezone' => $provider['timezone'],
                    'services' => array_map('intval', $provider['services'] ?? []),
                    'stations' => array_map('intval', $provider['stations'] ?? []),
                ];
            }

            json_response(['success' => true, 'providers' => $providers]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Available hours for a service. Requires service_id and date; provider_id
     * optional (without it, returns a per-provider breakdown).
     */
    public function availability(): void
    {
        try {
            $this->auth();
            method('get');

            $service_id = (int) request('service_id');
            $date = request('date');
            $provider_id = request('provider_id') !== null ? (int) request('provider_id') : null;

            if (empty($service_id) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
                throw new InvalidArgumentException('service_id and date (Y-m-d) are required.');
            }

            $service = $this->services_model->find($service_id);

            if (empty($service) || (bool) ($service['is_private'] ?? true)) {
                throw new RuntimeException('Requested service is not publicly bookable.', 404);
            }

            if ($provider_id !== null) {
                $provider = $this->providers_model->find($provider_id);

                if (empty($provider)) {
                    throw new RuntimeException('Requested provider was not found.', 404);
                }

                $hours = $this->availability->get_available_hours($date, $service, $provider);

                json_response([
                    'success' => true,
                    'service_id' => $service_id,
                    'date' => $date,
                    'providers' => [
                        [
                            'provider_id' => (int) $provider['id'],
                            'hours' => $hours,
                        ],
                    ],
                ]);

                return;
            }

            $rows = [];

            foreach ($this->providers_model->get_available_providers(true) as $provider) {
                if (!in_array($service_id, array_map('intval', $provider['services'] ?? []), true)) {
                    continue;
                }

                $rows[] = [
                    'provider_id' => (int) $provider['id'],
                    'provider_name' => trim($provider['first_name'] . ' ' . $provider['last_name']),
                    'hours' => $this->availability->get_available_hours($date, $service, $provider),
                ];
            }

            json_response([
                'success' => true,
                'service_id' => $service_id,
                'date' => $date,
                'providers' => $rows,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Search customers by phone number, email or name.
     */
    public function customer_lookup(): void
    {
        try {
            $this->auth();
            method('post');

            check('query', 'string');

            $query = trim(request('query'));

            if (mb_strlen($query) < 2) {
                throw new InvalidArgumentException('Query must be at least 2 characters.');
            }

            $customers = [];

            foreach ($this->customers_model->search($query, 10) as $customer) {
                $this->customers_model->only($customer, $this->allowed_customer_lookup_fields);

                $customers[] = $customer;
            }

            json_response(['success' => true, 'customers' => $customers]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Appointments of a customer (default: upcoming, past ones excluded).
     */
    public function customer_appointments(int $customer_id): void
    {
        try {
            $this->auth();
            method('get');

            $status = request('status', 'upcoming');

            $where = ['id_users_customer' => $customer_id];

            if ($status === 'upcoming') {
                $where['start_datetime >='] = date('Y-m-d H:i:s');
                $where['status !='] = 'Cancelled';
            } elseif ($status === 'all') {
                // no date filter
            } else {
                $where['start_datetime <'] = date('Y-m-d H:i:s');
                $where['status !='] = 'Cancelled';
            }

            $appointments = [];

            foreach ($this->appointments_model->get($where) as $appointment) {
                $service = $this->services_model->find((int) $appointment['id_services']);
                $provider = $this->providers_model->find((int) $appointment['id_users_provider']);

                $appointments[] = [
                    'id' => (int) $appointment['id'],
                    'hash' => $appointment['hash'],
                    'service' => $service['name'] ?? null,
                    'service_id' => (int) $appointment['id_services'],
                    'provider' => isset($provider['first_name'])
                        ? trim($provider['first_name'] . ' ' . ($provider['last_name'] ?? ''))
                        : null,
                    'provider_id' => (int) $appointment['id_users_provider'],
                    'start_datetime' => $appointment['start_datetime'],
                    'end_datetime' => $appointment['end_datetime'],
                    'status' => $appointment['status'],
                    'location' => $appointment['location'],
                    'notes' => $appointment['notes'],
                    'manage_link' => base_url('booking/reschedule/' . $appointment['hash']),
                ];
            }

            json_response(['success' => true, 'appointments' => $appointments]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Create an appointment using the full booking pipeline.
     */
    public function create_appointment(): void
    {
        try {
            $this->auth();
            method('post');

            check('service_id', 'integer');
            check('provider_id', 'integer');
            check('start_datetime', 'string');
            check('customer', 'array');

            $appointment = [
                'id_services' => (int) request('service_id'),
                'id_users_provider' => (int) request('provider_id'),
                'start_datetime' => request('start_datetime'),
                'notes' => request('notes', ''),
                'location' => request('location', ''),
            ];

            $customer = request('customer');

            if (empty($customer['first_name']) || empty($customer['last_name'])) {
                throw new InvalidArgumentException('customer.first_name and customer.last_name are required.');
            }

            if (!empty($customer['email']) && !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid customer email address.');
            }

            $result = $this->appointment_booking_service->create($appointment, $customer);

            if (empty($result['success'])) {
                json_response(
                    [
                        'success' => false,
                        'error' => $result['error'] ?? 'internal_error',
                        'message' => $result['message'] ?? 'Booking failed.',
                    ],
                    409,
                );

                return;
            }

            $appointment_id = (int) $result['appointment_id'];
            $appointment = $this->appointments_model->find($appointment_id);
            $service = $this->services_model->find((int) $appointment['id_services']);
            $provider = $this->providers_model->find((int) $appointment['id_users_provider']);
            $customer = $this->customers_model->find((int) $appointment['id_users_customer']);

            $settings = $this->notification_settings();

            if (!empty($appointment) && !empty($service) && !empty($provider) && !empty($customer)) {
                $this->best_effort(function () use ($appointment, $service, $provider, $customer, $settings) {
                    $this->synchronization->sync_appointment_saved($appointment, $service, $provider, $customer, $settings);
                });

                $this->best_effort(function () use ($appointment, $service, $provider, $customer, $settings) {
                    $this->notifications->notify_appointment_saved(
                        $appointment,
                        $service,
                        $provider,
                        $customer,
                        $settings,
                        false,
                    );
                });

                $this->load->library('communication_hub');
                $this->best_effort(function () use ($appointment, $service, $provider, $customer, $settings) {
                    $this->communication_hub->publish(
                        'appointment_created',
                        compact('appointment', 'service', 'provider', 'customer', 'settings'),
                    );
                });

                $this->load->library('automation_engine');
                $this->best_effort(function () use ($appointment, $service, $provider, $customer, $settings) {
                    $this->automation_engine->evaluate(
                        'appointment_created',
                        compact('appointment', 'service', 'provider', 'customer', 'settings'),
                    );
                });

                $this->best_effort(function () use ($appointment) {
                    $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_SAVE, $appointment);
                });
            }

            $response = [
                'success' => true,
                'appointment_id' => $appointment_id,
                'appointment_hash' => $appointment['hash'] ?? null,
                'manage_link' => base_url('booking/reschedule/' . ($appointment['hash'] ?? '')),
            ];

            if (!empty($result['payment_required'])) {
                $response['payment_required'] = true;
                $response['payment_intent'] = $result['payment_intent'];
            }

            json_response($response);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Cancel (delete) an existing appointment.
     */
    public function cancel_appointment(int $appointment_id): void
    {
        try {
            $this->auth();
            method('post');

            check('cancellation_reason', 'string');

            $cancellation_reason = strip_tags(substr(trim(request('cancellation_reason')), 0, 1000));

            if ($cancellation_reason === '') {
                throw new InvalidArgumentException('cancellation_reason is required.');
            }

            $appointment = $this->load_appointment_or_404($appointment_id);

            $service = $this->services_model->find((int) $appointment['id_services']);
            $provider = $this->providers_model->find((int) $appointment['id_users_provider']);
            $customer = $this->customers_model->find((int) $appointment['id_users_customer']);

            $settings = $this->notification_settings();

            $this->appointments_model->delete($appointment['id']);

            if (!empty($service) && !empty($provider) && !empty($customer)) {
                $this->best_effort(function () use ($appointment, $provider) {
                    $this->synchronization->sync_appointment_deleted($appointment, $provider);
                });

                $this->best_effort(function () use ($appointment, $service, $provider, $customer, $settings, $cancellation_reason) {
                    $this->notifications->notify_appointment_deleted(
                        $appointment,
                        $service,
                        $provider,
                        $customer,
                        $settings,
                        $cancellation_reason,
                    );
                });

                $this->best_effort(function () use ($appointment) {
                    $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_DELETE, $appointment);
                });
            }

            json_response(['success' => true, 'appointment_id' => (int) $appointment['id']]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Reschedule an existing appointment to a new start time.
     */
    public function reschedule_appointment(int $appointment_id): void
    {
        try {
            $this->auth();
            method('post');

            check('start_datetime', 'string');

            $new_start = request('start_datetime');

            if (!validate_datetime($new_start)) {
                throw new InvalidArgumentException('A valid start_datetime (Y-m-d H:i:s) is required.');
            }

            $appointment = $this->load_appointment_or_404($appointment_id);

            $service = $this->services_model->find((int) $appointment['id_services']);

            if (empty($service)) {
                throw new RuntimeException('Appointment service was not found.', 404);
            }

            $provider = $this->providers_model->find((int) $appointment['id_users_provider']);

            if (empty($provider)) {
                throw new RuntimeException('Appointment provider was not found.', 404);
            }

            $provider_timezone = isset($provider['timezone']) ? new DateTimeZone($provider['timezone']) : null;

            $start = new DateTime($new_start, $provider_timezone);
            $date = $start->format('Y-m-d');
            $hour = $start->format('H:i');

            $available_hours = $this->availability->get_available_hours(
                $date,
                $service,
                $provider,
                (int) $appointment['id'],
            );

            if (!in_array($hour, $available_hours, true)) {
                json_response(
                    [
                        'success' => false,
                        'error' => 'unavailable_time',
                        'message' => 'The requested time is not available.',
                    ],
                    409,
                );

                return;
            }

            $appointment['start_datetime'] = $start->format('Y-m-d H:i:s');
            $appointment['end_datetime'] = $this->appointments_model->calculate_end_datetime($appointment);

            $station_locks_held = [];
            $provider_station_ids = $this->providers_model->get_station_ids((int) $provider['id']);

            if (!empty($provider_station_ids)) {
                if (!$this->stations_model->acquire_station_locks($provider_station_ids)) {
                    throw new RuntimeException('Station availability could not be verified. Please try again.');
                }

                $station_locks_held = $provider_station_ids;

                $free_station_id = $this->stations_model->find_free_station(
                    $provider_station_ids,
                    $appointment['start_datetime'],
                    $appointment['end_datetime'],
                    (int) $appointment['id'],
                );

                if ($free_station_id === null) {
                    $this->stations_model->release_station_locks($station_locks_held);

                    json_response(
                        [
                            'success' => false,
                            'error' => 'no_available_station',
                            'message' => 'No station is available for the requested time.',
                        ],
                        409,
                    );

                    return;
                }

                $appointment['id_stations'] = $free_station_id;
            }

            $this->appointments_model->save($appointment);

            if (!empty($station_locks_held)) {
                $this->stations_model->release_station_locks($station_locks_held);
            }

            $appointment = $this->appointments_model->find((int) $appointment['id']);

            $customer = $this->customers_model->find((int) $appointment['id_users_customer']);

            $settings = $this->notification_settings();

            if (!empty($service) && !empty($provider) && !empty($customer)) {
                $this->best_effort(function () use ($appointment, $service, $provider, $customer, $settings) {
                    $this->synchronization->sync_appointment_saved($appointment, $service, $provider, $customer, $settings);
                });

                $this->best_effort(function () use ($appointment, $service, $provider, $customer, $settings) {
                    $this->notifications->notify_appointment_saved(
                        $appointment,
                        $service,
                        $provider,
                        $customer,
                        $settings,
                        true,
                    );
                });

                $this->best_effort(function () use ($appointment) {
                    $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_SAVE, $appointment);
                });
            }

            json_response([
                'success' => true,
                'appointment_id' => (int) $appointment['id'],
                'start_datetime' => $appointment['start_datetime'],
                'end_datetime' => $appointment['end_datetime'],
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Load an appointment by its numeric id or respond 404.
     */
    protected function load_appointment_or_404(int $appointment_id): array
    {
        $occurrences = $this->appointments_model->get(['id' => $appointment_id]);

        if (empty($occurrences)) {
            throw new RuntimeException('Appointment not found.', 404);
        }

        return $occurrences[0];
    }

    /**
     * Shared company notification settings block.
     */
    protected function notification_settings(): array
    {
        $company_color = setting('company_color');

        return [
            'company_name' => setting('company_name'),
            'company_email' => setting('company_email'),
            'company_link' => setting('company_link'),
            'company_color' =>
                !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
            'date_format' => setting('date_format'),
            'time_format' => setting('time_format'),
        ];
    }

    /**
     * Run a side effect, logging but never propagating failures.
     */
    protected function best_effort(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            log_message('error', 'Agent_api - best-effort side effect failed: ' . $e->getMessage());
        }
    }

    /**
     * Authorize the request against the per-tenant agent key setting.
     *
     * Terminates with a JSON response on failure - `abort()` would emit the HTML
     * error page, useless to a server-to-server agent client.
     */
    protected function auth(): void
    {
        $api_key = setting('agent_api_key', '');

        if ($api_key === '') {
            json_response(['success' => false, 'message' => 'Agent API is not configured for this tenant.'], 503);
            $this->output->_display();

            exit;
        }

        $provided_token = $this->get_bearer_token();

        if (empty($provided_token) || !hash_equals((string) $api_key, $provided_token)) {
            json_response(['success' => false, 'message' => 'Unauthorized.'], 401);
            $this->output->_display();

            exit;
        }
    }

    /**
     * Returns the bearer token value.
     *
     * @return string|null
     */
    protected function get_bearer_token(): ?string
    {
        $headers = null;

        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $request_headers = array_combine(
                array_map('ucwords', array_keys(apache_request_headers())),
                array_values(apache_request_headers()),
            );

            if (isset($request_headers['Authorization'])) {
                $headers = trim($request_headers['Authorization']);
            }
        }

        if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }

        return null;
    }
}