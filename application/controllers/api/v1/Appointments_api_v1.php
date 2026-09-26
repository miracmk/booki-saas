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
 * Appointments API v1 controller.
 *
 * @package Controllers
 */
class Appointments_api_v1 extends App_Controller
{
    /**
     * Appointments_api_v1 constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('customers_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('settings_model');

        $this->load->library('api');
        $this->load->library('webhooks_client');
        $this->load->library('synchronization');
        $this->load->library('notifications');

        $this->api->auth();

        $this->api->model('appointments_model');
    }

    /**
     * Get an appointment collection.
     */
    public function index(): void
    {
        try {
            $keyword = $this->api->request_keyword();

            $limit = $this->api->request_limit();

            $offset = $this->api->request_offset();

            $order_by = $this->api->request_order_by();

            $fields = $this->api->request_fields();

            $with = $this->api->request_with();

            $where = null;

            // Date query param.

            $date = request('date');

            if (!empty($date)) {
                $where['DATE(start_datetime)'] = (new DateTime($date))->format('Y-m-d');
            }

            // From query param.

            $from = request('from');

            if (!empty($from)) {
                $where['DATE(start_datetime) >='] = (new DateTime($from))->format('Y-m-d');
            }

            // Till query param.

            $till = request('till');

            if (!empty($till)) {
                $where['DATE(end_datetime) <='] = (new DateTime($till))->format('Y-m-d');
            }

            // Service ID query param.

            $service_id = request('serviceId');

            if (!empty($service_id)) {
                $where['id_services'] = $service_id;
            }

            // Provider ID query param.

            $provider_id = request('providerId');

            if (!empty($provider_id)) {
                $where['id_users_provider'] = $provider_id;
            }

            // Customer ID query param.

            $customer_id = request('customerId');

            if (!empty($customer_id)) {
                $where['id_users_customer'] = $customer_id;
            }

            // Role-based scoping for authenticated mobile users
            $session_role = session('role_slug');
            $session_user_id = session('user_id');
            if ($session_role === DB_SLUG_CUSTOMER) {
                $where['id_users_customer'] = $session_user_id;
            } elseif ($session_role === DB_SLUG_PROVIDER && empty($provider_id)) {
                $where['id_users_provider'] = $session_user_id;
            }

            $appointments = empty($keyword)
                ? $this->appointments_model->get($where, $limit, $offset, $order_by)
                : $this->appointments_model->search($keyword, $limit, $offset, $order_by);

            foreach ($appointments as &$appointment) {
                $this->appointments_model->api_encode($appointment);

                $this->aggregates($appointment);

                if (!empty($fields)) {
                    $this->appointments_model->only($appointment, $fields);
                }

                if (!empty($with)) {
                    $this->appointments_model->load($appointment, $with);
                }
            }

            json_response($appointments);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Load the relations of the current appointment if the "aggregates" query parameter is present.
     *
     * This is a compatibility addition to the appointment resource which was the only one to support it.
     *
     * Use the "attach" query parameter instead as this one will be removed.
     *
     * @param array $appointment Appointment data.
     *
     * @deprecated Since 1.5
     */
    private function aggregates(array &$appointment): void
    {
        $aggregates = request('aggregates') !== null;

        if ($aggregates) {
            $appointment['service'] = $this->services_model->find(
                $appointment['id_services'] ?? ($appointment['serviceId'] ?? null),
            );
            $appointment['provider'] = $this->providers_model->find(
                $appointment['id_users_provider'] ?? ($appointment['providerId'] ?? null),
            );
            $appointment['customer'] = $this->customers_model->find(
                $appointment['id_users_customer'] ?? ($appointment['customerId'] ?? null),
            );
            $this->services_model->api_encode($appointment['service']);
            $this->providers_model->api_encode($appointment['provider']);
            $this->customers_model->api_encode($appointment['customer']);
        }
    }

    /**
     * Get a single appointment.
     *
     * @param int|null $id Appointment ID.
     */
    public function show(?int $id = null): void
    {
        try {
            $occurrences = $this->appointments_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $fields = $this->api->request_fields();

            $with = $this->api->request_with();

            $appointment = $this->appointments_model->find($id);

            $this->appointments_model->api_encode($appointment);

            if (!empty($fields)) {
                $this->appointments_model->only($appointment, $fields);
            }

            if (!empty($with)) {
                $this->appointments_model->load($appointment, $with);
            }

            json_response($appointment);
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
            $appointment = request();

            $this->appointments_model->api_decode($appointment);

            if (array_key_exists('id', $appointment)) {
                unset($appointment['id']);
            }

            // 1. Service Resolution:
            $service_id = (int) ($appointment['id_services'] ?? 0);
            $service = null;
            if ($service_id > 0) {
                $service = $this->db->get_where('services', ['id' => $service_id])->row_array();
            }
            if (empty($service)) {
                $fallback_service = $this->db->select('*')
                    ->from('services')
                    ->order_by('id', 'ASC')
                    ->limit(1)
                    ->get()
                    ->row_array();
                if (!empty($fallback_service['id'])) {
                    $appointment['id_services'] = (int) $fallback_service['id'];
                    $service = $fallback_service;
                }
            }

            // 2. Provider Resolution:
            $provider_id = (int) ($appointment['id_users_provider'] ?? 0);
            $is_valid_provider = false;
            if ($provider_id > 0) {
                $is_valid_provider = (bool) $this->db
                    ->from('users')
                    ->join('roles', 'roles.id = users.id_roles', 'inner')
                    ->where('users.id', $provider_id)
                    ->where('roles.slug', DB_SLUG_PROVIDER)
                    ->count_all_results();
            }

            if (!$is_valid_provider) {
                $assigned_provider = !empty($appointment['id_services'])
                    ? $this->db->select('id_users')
                        ->from('services_providers')
                        ->where('id_services', $appointment['id_services'])
                        ->limit(1)
                        ->get()
                        ->row_array()
                    : null;

                if (!empty($assigned_provider['id_users'])) {
                    $appointment['id_users_provider'] = (int) $assigned_provider['id_users'];
                } else {
                    $fallback_provider = $this->db->select('users.id')
                        ->from('users')
                        ->join('roles', 'roles.id = users.id_roles', 'inner')
                        ->where('roles.slug', DB_SLUG_PROVIDER)
                        ->order_by('users.id', 'ASC')
                        ->limit(1)
                        ->get()
                        ->row_array();

                    if (!empty($fallback_provider['id'])) {
                        $appointment['id_users_provider'] = (int) $fallback_provider['id'];
                    }
                }
            }

            // 3. Customer Resolution:
            $customer_payload = $appointment['customer'] ?? request('customer');
            unset($appointment['customer']); // Do not pass to ea_appointments table

            if (!empty($customer_payload) && is_array($customer_payload)) {
                $first_name = trim($customer_payload['first_name'] ?? '');
                $last_name = trim($customer_payload['last_name'] ?? '');
                $phone = trim($customer_payload['phone_number'] ?? '');
                $email = trim($customer_payload['email'] ?? '');

                $matched_customer_id = null;
                if (!empty($phone)) {
                    $existing = $this->db->select('users.id')
                        ->from('users')
                        ->join('roles', 'roles.id = users.id_roles', 'inner')
                        ->where('roles.slug', DB_SLUG_CUSTOMER)
                        ->where('users.phone_number', $phone)
                        ->limit(1)
                        ->get()
                        ->row_array();
                    if (!empty($existing['id'])) {
                        $matched_customer_id = (int) $existing['id'];
                    }
                }

                if (!$matched_customer_id) {
                    $customer_role_row = $this->db->get_where('roles', ['slug' => DB_SLUG_CUSTOMER])->row_array();
                    $customer_role = (int) ($customer_role_row['id'] ?? 3);
                    $new_id = $this->customers_model->add([
                        'first_name' => $first_name ?: 'Müşteri',
                        'last_name' => $last_name ?: 'Mobil',
                        'phone_number' => $phone ?: '+905000000000',
                        'email' => !empty($email) ? $email : ('customer_' . time() . '_' . random_int(100, 999) . '@bookiapp.co'),
                        'id_roles' => $customer_role,
                    ]);
                    $matched_customer_id = $new_id;
                }
                $appointment['id_users_customer'] = $matched_customer_id;
            }

            $customer_id = (int) ($appointment['id_users_customer'] ?? 0);
            $is_valid_customer = false;
            if ($customer_id > 0) {
                $is_valid_customer = (bool) $this->db
                    ->from('users')
                    ->join('roles', 'roles.id = users.id_roles', 'inner')
                    ->where('users.id', $customer_id)
                    ->where('roles.slug', DB_SLUG_CUSTOMER)
                    ->count_all_results();
            }

            if (!$is_valid_customer) {
                $customer_role_row = $this->db->get_where('roles', ['slug' => DB_SLUG_CUSTOMER])->row_array();
                $customer_role = (int) ($customer_role_row['id'] ?? 3);

                $notes = (string) ($appointment['notes'] ?? '');
                $customer_name = null;
                $customer_phone = null;
                if (preg_match('/Müşteri:\s*([^|]+)/i', $notes, $m)) {
                    $customer_name = trim($m[1]);
                }
                if (preg_match('/Tel:\s*([^|]+)/i', $notes, $m)) {
                    $customer_phone = trim($m[1]);
                }

                if (!empty($customer_name) && !empty($customer_phone)) {
                    $parts = explode(' ', $customer_name, 2);
                    $first_name = $parts[0] ?? 'Müşteri';
                    $last_name = $parts[1] ?? 'Walk-in';

                    $new_id = $this->customers_model->add([
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'phone_number' => $customer_phone,
                        'email' => 'walkin_' . time() . '_' . random_int(100, 999) . '@bookiapp.co',
                        'id_roles' => $customer_role,
                    ]);
                    $appointment['id_users_customer'] = $new_id;
                } else {
                    $first_cust = $this->db
                        ->select('users.id')
                        ->from('users')
                        ->join('roles', 'roles.id = users.id_roles', 'inner')
                        ->where('roles.slug', DB_SLUG_CUSTOMER)
                        ->order_by('users.id', 'ASC')
                        ->limit(1)
                        ->get()
                        ->row_array();

                    if (!empty($first_cust['id'])) {
                        $appointment['id_users_customer'] = (int) $first_cust['id'];
                    } else {
                        $new_id = $this->customers_model->add([
                            'first_name' => 'Kapı Müşterisi',
                            'last_name' => '(Walk-in)',
                            'phone_number' => '+905000000000',
                            'email' => 'walkin@bookiapp.co',
                            'id_roles' => $customer_role,
                        ]);
                        $appointment['id_users_customer'] = $new_id;
                    }
                }
            }

            // 4. DateTime & End Duration calculation:
            if (empty($appointment['start_datetime'])) {
                $appointment['start_datetime'] = date('Y-m-d H:i:s');
            }

            if (empty($appointment['end_datetime'])) {
                $duration = !empty($service['duration']) ? (int) $service['duration'] : 30;
                $appointment['end_datetime'] = (new DateTime($appointment['start_datetime']))
                    ->add(new DateInterval('PT' . $duration . 'M'))
                    ->format('Y-m-d H:i:s');
            }

            $appointment_id = $this->appointments_model->save($appointment);

            $created_appointment = $this->appointments_model->find($appointment_id);

            $this->notify_and_sync_appointment($created_appointment);

            $this->appointments_model->api_encode($created_appointment);

            json_response($created_appointment, 201);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Send the required notifications and trigger syncing after saving an appointment.
     *
     * @param array $appointment Appointment data.
     * @param string $action Performed action ("store" or "update").
     */
    private function notify_and_sync_appointment(array $appointment, string $action = 'store'): void
    {
        $manage_mode = $action === 'update';

        $service = $this->services_model->find($appointment['id_services']);

        $provider = $this->providers_model->find($appointment['id_users_provider']);

        $customer = $this->customers_model->find($appointment['id_users_customer']);

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

        $this->synchronization->sync_appointment_saved($appointment, $service, $provider, $customer, $settings);

        $this->notifications->notify_appointment_saved(
            $appointment,
            $service,
            $provider,
            $customer,
            $settings,
            $manage_mode,
        );

        $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_SAVE, $appointment);
    }

    /**
     * Update an appointment.
     *
     * @param int $id Appointment ID.
     */
    public function update(int $id): void
    {
        try {
            $occurrences = $this->appointments_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $original_appointment = $occurrences[0];

            $appointment = request();

            $this->appointments_model->api_decode($appointment, $original_appointment);

            $appointment_id = $this->appointments_model->save($appointment);

            $updated_appointment = $this->appointments_model->find($appointment_id);

            $this->notify_and_sync_appointment($updated_appointment, 'update');

            $this->appointments_model->api_encode($updated_appointment);

            json_response($updated_appointment);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete an appointment.
     *
     * @param int $id Appointment ID.
     */
    public function destroy(int $id): void
    {
        try {
            $occurrences = $this->appointments_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $deleted_appointment = $occurrences[0];

            $service = $this->services_model->find($deleted_appointment['id_services']);

            $provider = $this->providers_model->find($deleted_appointment['id_users_provider']);

            $customer = $this->customers_model->find($deleted_appointment['id_users_customer']);

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

            $this->appointments_model->delete($id);

            $this->synchronization->sync_appointment_deleted($deleted_appointment, $provider);

            $this->notifications->notify_appointment_deleted(
                $deleted_appointment,
                $service,
                $provider,
                $customer,
                $settings,
            );

            $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_DELETE, $deleted_appointment);

            response('', 204);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
