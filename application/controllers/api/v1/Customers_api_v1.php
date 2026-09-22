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
 * Customers API v1 controller.
 *
 * @package Controllers
 */
class Customers_api_v1 extends EA_Controller
{
    /**
     * Customers_api_v1 constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api');
        $this->load->library('webhooks_client');

        $this->api->auth();

        $this->api->model('customers_model');
    }

    /**
     * Get a customer collection.
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

            $customers = empty($keyword)
                ? $this->customers_model->get(null, $limit, $offset, $order_by)
                : $this->customers_model->search($keyword, $limit, $offset, $order_by);

            foreach ($customers as &$customer) {
                $this->customers_model->api_encode($customer);

                if (!empty($fields)) {
                    $this->customers_model->only($customer, $fields);
                }

                if (!empty($with)) {
                    $this->customers_model->load($customer, $with);
                }
            }

            json_response($customers);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get a single customer.
     *
     * @param int|null $id Customer ID.
     */
    public function show(?int $id = null): void
    {
        try {
            // Validate ID is a positive integer
            if (empty($id) || $id <= 0) {
                response('', 400);
                return;
            }

            $occurrences = $this->customers_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $fields = $this->api->request_fields();

            $customer = $this->customers_model->find($id);

            $this->customers_model->api_encode($customer);

            if (!empty($fields)) {
                $this->customers_model->only($customer, $fields);
            }

            json_response($customer);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Store a new customer.
     */
    public function store(): void
    {
        try {
            $customer = request();

            $this->customers_model->api_decode($customer);

            if (array_key_exists('id', $customer)) {
                unset($customer['id']);
            }

            $customer_id = $this->customers_model->save($customer);

            $created_customer = $this->customers_model->find($customer_id);

            $this->webhooks_client->trigger(WEBHOOK_CUSTOMER_SAVE, $created_customer);

            $this->customers_model->api_encode($created_customer);

            json_response($created_customer, 201);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update a customer.
     *
     * @param int $id Customer ID.
     */
    public function update(int $id): void
    {
        try {
            $occurrences = $this->customers_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $original_customer = $occurrences[0];

            $customer = request();

            $this->customers_model->api_decode($customer, $original_customer);

            $customer_id = $this->customers_model->save($customer);

            $updated_customer = $this->customers_model->find($customer_id);

            $this->webhooks_client->trigger(WEBHOOK_CUSTOMER_SAVE, $updated_customer);

            $this->customers_model->api_encode($updated_customer);

            json_response($updated_customer);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete a customer.
     *
     * @param int $id Customer ID.
     */
    public function destroy(int $id): void
    {
        try {
            // Validate ID is a positive integer
            if ($id <= 0) {
                response('', 400);
                return;
            }

            $occurrences = $this->customers_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $deleted_customer = $occurrences[0];

            $this->customers_model->delete($id);

            $this->webhooks_client->trigger(WEBHOOK_CUSTOMER_DELETE, $deleted_customer);

            response('', 204);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * GET /api/v1/customers/:id/mini-crm
     * Returns Mini-CRM profile for the customer (visits, spend, no-show score, past appointments).
     */
    public function mini_crm(int $id): void
    {
        try {
            $customer = $this->customers_model->find($id);
            if (!$customer) {
                response('', 404);
                return;
            }

            $this->load->model('appointments_model');
            $this->load->model('services_model');

            // Fetch customer appointments
            $appts = $this->db
                ->from('appointments')
                ->where('id_users_customer', $id)
                ->order_by('start_datetime', 'DESC')
                ->get()
                ->result_array();

            $total_appointments = count($appts);
            $completed_visits = 0;
            $no_shows = 0;
            $cancelled = 0;
            $total_spent = 0.0;
            $past_appointments = [];

            foreach ($appts as $appt) {
                $status = strtolower($appt['status'] ?? '');
                $srv = $this->services_model->find($appt['id_services']);
                $srv_price = (float)($srv['price'] ?? 0.0);

                if (in_array($status, ['completed', 'tamamlandı', 'closed', 'arrived'], true)) {
                    $completed_visits++;
                    $total_spent += $srv_price;
                } elseif (in_array($status, ['no_show', 'no-show', 'gelmedi'], true)) {
                    $no_shows++;
                } elseif (in_array($status, ['cancelled', 'iptal'], true)) {
                    $cancelled++;
                }

                if (count($past_appointments) < 5) {
                    $past_appointments[] = [
                        'id' => (int)$appt['id'],
                        'service_name' => $srv['name'] ?? 'Hizmet',
                        'start_datetime' => $appt['start_datetime'],
                        'status' => $appt['status'],
                        'price' => $srv_price,
                    ];
                }
            }

            // Calculate No-Show Score: 100 base, -25 per no-show (min 0)
            $no_show_score = max(0, 100 - ($no_shows * 25));

            $phone_clean = preg_replace('/[^\d+]/', '', $customer['phone_number'] ?? '');
            if (!str_starts_with($phone_clean, '+') && strlen($phone_clean) === 10 && str_starts_with($phone_clean, '5')) {
                $phone_clean = '+90' . $phone_clean;
            }

            $crm_data = [
                'customer_id' => $id,
                'name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
                'first_name' => $customer['first_name'] ?? '',
                'last_name' => $customer['last_name'] ?? '',
                'phone' => $customer['phone_number'] ?? '',
                'clean_phone' => $phone_clean,
                'email' => $customer['email'] ?? '',
                'notes' => $customer['notes'] ?? '',
                'allergy_notes' => $customer['custom_field_allergy'] ?? ($customer['notes'] ?? 'Bilinen bir alerji veya kısıt bulunmamaktadır.'),
                'total_visits' => $completed_visits,
                'total_appointments' => $total_appointments,
                'no_shows' => $no_shows,
                'cancelled' => $cancelled,
                'no_show_score' => $no_show_score,
                'total_spent' => round($total_spent, 2),
                'currency' => setting('currency') ?: '₺',
                'whatsapp_url' => !empty($phone_clean) ? 'https://wa.me/' . ltrim($phone_clean, '+') : null,
                'call_url' => !empty($phone_clean) ? 'tel:' . $phone_clean : null,
                'past_appointments' => $past_appointments,
            ];

            json_response($crm_data);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}

