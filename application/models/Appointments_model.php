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
 * Appointments model.
 *
 * @package Models
 */
class Appointments_model extends EA_Model
{
    /**
     * @var array
     */
    protected array $casts = [
        'id' => 'integer',
        'is_unavailability' => 'boolean',
        'id_users_provider' => 'integer',
        'id_users_customer' => 'integer',
        'id_services' => 'integer',
    ];

    /**
     * @var array
     */
    protected array $api_resource = [
        'id' => 'id',
        'book' => 'book_datetime',
        'start' => 'start_datetime',
        'end' => 'end_datetime',
        'location' => 'location',
        'meetingLink' => 'meeting_link',
        'color' => 'color',
        'status' => 'status',
        'notes' => 'notes',
        'hash' => 'hash',
        'serviceId' => 'id_services',
        'providerId' => 'id_users_provider',
        'customerId' => 'id_users_customer',
        'googleCalendarId' => 'id_google_calendar',
        'caldavCalendarId' => 'id_caldav_calendar',
    ];

    /**
     * Save (insert or update) an appointment.
     *
     * @param array $appointment Associative array with the appointment data.
     *
     * @return int Returns the appointment ID.
     *
     * @throws InvalidArgumentException
     */
    public function save(array $appointment): int
    {
        $this->validate($appointment);

        if (empty($appointment['id'])) {
            $appointment_id = $this->insert($appointment);
            $appointment['id'] = $appointment_id;

            $this->enqueue_crm('appointment.created', $appointment);

            return $appointment_id;
        } else {
            $appointment_id = $this->update($appointment);

            $this->enqueue_crm('appointment.updated', $appointment);

            return $appointment_id;
        }
    }

    /**
     * BooKi (2026-09-16) - Zoho CRM integration: write a PII-free pointer to the tenant's
     * crm_outbox so the `console crm_sync` worker can mirror this appointment into CRM. Deliberately
     * fail-safe (Crm_sync::enqueue() never throws) and never called for unavailability blocks.
     */
    private function enqueue_crm(string $action, array $appointment): void
    {
        if (!empty($appointment['is_unavailability'])) {
            return;
        }

        $this->load->library('crm_sync');

        $this->crm_sync->enqueue(
            $action,
            !empty($appointment['id_users_customer']) ? (int) $appointment['id_users_customer'] : null,
            (int) ($appointment['id'] ?? 0),
        );
    }

    /**
     * Validate the appointment data.
     *
     * @param array $appointment Associative array with the appointment data.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $appointment): void
    {
        // If an appointment ID is provided then check whether the record really exists in the database.
        if (!empty($appointment['id'])) {
            $count = $this->db->get_where('appointments', ['id' => $appointment['id']])->num_rows();

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided appointment ID does not exist in the database: ' . $appointment['id'],
                );
            }
        }

        // Make sure all required fields are provided.

        $require_notes = filter_var(setting('require_notes'), FILTER_VALIDATE_BOOLEAN);

        if (
            empty($appointment['start_datetime']) ||
            empty($appointment['end_datetime']) ||
            empty($appointment['id_services']) ||
            empty($appointment['id_users_provider']) ||
            empty($appointment['id_users_customer']) ||
            (empty($appointment['notes']) && $require_notes)
        ) {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($appointment, true));
        }

        // Make sure that the provided appointment date time values are valid.
        if (!validate_datetime($appointment['start_datetime'])) {
            throw new InvalidArgumentException('The appointment start date time is invalid.');
        }

        if (!validate_datetime($appointment['end_datetime'])) {
            throw new InvalidArgumentException('The appointment end date time is invalid.');
        }

        // Make the appointment lasts longer than the minimum duration (in minutes).
        $diff = (strtotime($appointment['end_datetime']) - strtotime($appointment['start_datetime'])) / 60;

        if ($diff < EVENT_MINIMUM_DURATION) {
            throw new InvalidArgumentException(
                'The appointment duration cannot be less than ' . EVENT_MINIMUM_DURATION . ' minutes.',
            );
        }

        // Make sure the provider ID really exists in the database.
        $count = $this->db
            ->select()
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('users.id', $appointment['id_users_provider'])
            ->where('roles.slug', DB_SLUG_PROVIDER)
            ->get()
            ->num_rows();

        if (!$count) {
            throw new InvalidArgumentException(
                'The appointment provider ID was not found in the database: ' . $appointment['id_users_provider'],
            );
        }

        if (!filter_var($appointment['is_unavailability'], FILTER_VALIDATE_BOOLEAN)) {
            // Make sure the customer ID really exists in the database.
            $count = $this->db
                ->select()
                ->from('users')
                ->join('roles', 'roles.id = users.id_roles', 'inner')
                ->where('users.id', $appointment['id_users_customer'])
                ->where('roles.slug', DB_SLUG_CUSTOMER)
                ->get()
                ->num_rows();

            if (!$count) {
                throw new InvalidArgumentException(
                    'The appointment customer ID was not found in the database: ' . $appointment['id_users_customer'],
                );
            }

            // Make sure the service ID really exists in the database.
            $count = $this->db->get_where('services', ['id' => $appointment['id_services']])->num_rows();

            if (!$count) {
                throw new InvalidArgumentException('Appointment service id is invalid.');
            }
        }
    }

    /**
     * Get all appointments that match the provided criteria.
     *
     * @param array|string|null $where Where conditions.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of appointments.
     */
    public function get(
        array|string|null $where = null,
        ?int $limit = null,
        ?int $offset = null,
        ?string $order_by = null,
    ): array {
        if ($where !== null) {
            $this->db->where($where);
        }

        if ($order_by) {
            $this->db->order_by($this->quote_order_by($order_by));
        }

        $appointments = $this->db
            ->get_where('appointments', ['is_unavailability' => false], $limit, $offset)
            ->result_array();

        foreach ($appointments as &$appointment) {
            $this->cast($appointment);
        }

        return $appointments;
    }

    /**
     * Salon Flora customization - get the booked (non-unavailability) appointments of a set of providers on a
     * given date. Used by the station-capacity-aware availability calculation, to find out whether a "station
     * mate" provider (a different provider assigned to the same physical station) already has an appointment
     * during the requested date, so the slot can be considered unavailable for the current provider too.
     *
     * Multi-branch support: optional branch filter for multi-branch deployments (null = no filter).
     *
     * @param array $provider_ids Provider (user) IDs to check.
     * @param string $date Date (Y-m-d).
     * @param int|null $exclude_appointment_id Exclude an appointment from the result (e.g. when editing it).
     * @param int|null $branch_id Optional branch filter (null = no filter, include all branches).
     *
     * @return array Returns an array of appointments.
     */
    public function get_for_provider_ids(array $provider_ids, string $date, ?int $exclude_appointment_id = null, ?int $branch_id = null): array
    {
        if (empty($provider_ids)) {
            return [];
        }

        $this->db
            ->where_in('id_users_provider', array_map('intval', $provider_ids))
            ->where('is_unavailability', false)
            ->where('DATE(start_datetime) <=', $date)
            ->where('DATE(end_datetime) >=', $date);

        if ($exclude_appointment_id) {
            $this->db->where('id !=', (int) $exclude_appointment_id);
        }

        // Multi-branch support: apply branch filter only if provided and branch count is > 1
        if ($branch_id !== null) {
            $this->load->model('branches_model');
            if ($this->branches_model->count_active() > 1) {
                $this->db->where('id_branches', $branch_id);
            }
        }

        $appointments = $this->db->get('appointments')->result_array();

        foreach ($appointments as &$appointment) {
            $this->cast($appointment);
        }

        return $appointments;
    }

    /**
     * Insert a new appointment into the database.
     *
     * @param array $appointment Associative array with the appointment data.
     *
     * @return int Returns the appointment ID.
     *
     * @throws RuntimeException
     */
    protected function insert(array $appointment): int
    {
        $appointment['book_datetime'] = date('Y-m-d H:i:s');
        $appointment['create_datetime'] = date('Y-m-d H:i:s');
        $appointment['update_datetime'] = date('Y-m-d H:i:s');
        $appointment['hash'] = random_string('alnum', 12);

        if (!$this->db->insert('appointments', $appointment)) {
            throw new RuntimeException('Could not insert appointment.');
        }

        return $this->db->insert_id();
    }

    /**
     * Update an existing appointment.
     *
     * @param array $appointment Associative array with the appointment data.
     *
     * @return int Returns the appointment ID.
     *
     * @throws RuntimeException
     */
    protected function update(array $appointment): int
    {
        // Get old status to detect transition to 'closed'
        $old = $this->db->get_where('appointments', ['id' => $appointment['id']])->row_array();
        
        $appointment['update_datetime'] = date('Y-m-d H:i:s');

        if (!$this->db->update('appointments', $appointment, ['id' => $appointment['id']])) {
            throw new RuntimeException('Could not update appointment record.');
        }
        
        // Faz 43/44 Marketplace Commission
        if ($old && ($old['status'] !== 'closed') && (isset($appointment['status']) && $appointment['status'] === 'closed')) {
            $notes = $appointment['notes'] ?? $old['notes'] ?? '';
            if (strpos($notes, '[Pazar Yeri]') !== false) {
                $service = $this->db->get_where('services', ['id' => $old['id_services']])->row_array();
                if ($service) {
                    $amount = (float) $service['price'];
                    
                    $master_db = $this->load->database('default', true);
                    $tenant = tenant_context();
                    
                    if ($tenant && $amount > 0 && $master_db->table_exists('master_settings')) {
                        $rate_row = $master_db->get_where('master_settings', ['name' => 'marketplace_commission_rate'])->row_array();
                        $rate = $rate_row ? (float) $rate_row['value'] : 5.0;
                        $commission = $amount * ($rate / 100);
                        
                        // Update wallet
                        $master_db->query('INSERT INTO ' . $master_db->dbprefix('tenant_wallets') . ' (id_tenants, balance, total_earned, total_commission, updated_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE balance = balance + ?, total_earned = total_earned + ?, total_commission = total_commission + ?, updated_at = ?', [
                            $tenant['id'], 
                            $amount - $commission, $amount, $commission, date('Y-m-d H:i:s'),
                            $amount - $commission, $amount, $commission, date('Y-m-d H:i:s')
                        ]);
                        
                        // Log ledger
                        $master_db->insert('wallet_ledger', [
                            'id_tenants' => $tenant['id'],
                            'type' => 'booking_earning',
                            'amount' => $amount,
                            'currency' => $service['currency'] ?? 'TRY',
                            'reference_id' => 'APT-' . $appointment['id'],
                            'description' => 'Randevu Geliri (Hizmet: ' . $service['name'] . ')',
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                        
                        $master_db->insert('wallet_ledger', [
                            'id_tenants' => $tenant['id'],
                            'type' => 'commission_deduction',
                            'amount' => -$commission,
                            'currency' => $service['currency'] ?? 'TRY',
                            'reference_id' => 'APT-' . $appointment['id'],
                            'description' => 'Marketplace Komisyonu (%' . $rate . ')',
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
        }

        return $appointment['id'];
    }

    /**
     * Get a specific appointment from the database.
     *
     * @param int $appointment_id The ID of the record to be returned.
     *
     * @return array Returns an array with the appointment data.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $appointment_id): array
    {
        $appointment = $this->db->get_where('appointments', ['id' => $appointment_id])->row_array();

        if (!$appointment) {
            throw new InvalidArgumentException(
                'The provided appointment ID was not found in the database: ' . $appointment_id,
            );
        }

        $this->cast($appointment);

        return $appointment;
    }

    /**
     * Faz 30 (KVKK export) - all appointments (including unavailability-excluded, real bookings only)
     * belonging to a customer, newest first.
     *
     * @param int $customer_id
     * @return array
     */
    public function get_for_customer(int $customer_id): array
    {
        $appointments = $this->db
            ->where('id_users_customer', $customer_id)
            ->order_by('start_datetime', 'DESC')
            ->get('appointments')
            ->result_array();

        foreach ($appointments as &$appointment) {
            $this->cast($appointment);
        }

        return $appointments;
    }

    /**
     * Get a specific field value from the database.
     *
     * @param int $appointment_id Appointment ID.
     * @param string $field Name of the value to be returned.
     *
     * @return mixed Returns the selected appointment value from the database.
     *
     * @throws InvalidArgumentException
     */
    public function value(int $appointment_id, string $field): mixed
    {
        if (empty($field)) {
            throw new InvalidArgumentException('The field argument is cannot be empty.');
        }

        if (empty($appointment_id)) {
            throw new InvalidArgumentException('The appointment ID argument cannot be empty.');
        }

        // Check whether the appointment exists.
        $query = $this->db->get_where('appointments', ['id' => $appointment_id]);

        if (!$query->num_rows()) {
            throw new InvalidArgumentException(
                'The provided appointment ID was not found in the database: ' . $appointment_id,
            );
        }

        // Check if the required field is part of the appointment data.
        $appointment = $query->row_array();

        $this->cast($appointment);

        if (!array_key_exists($field, $appointment)) {
            throw new InvalidArgumentException('The requested field was not found in the appointment data: ' . $field);
        }

        return $appointment[$field];
    }

    /**
     * Remove all the Google Calendar event IDs from appointment records.
     *
     * @param int $provider_id Matching provider ID.
     */
    public function clear_google_sync_ids(int $provider_id): void
    {
        $this->db->update('appointments', ['id_google_calendar' => null], ['id_users_provider' => $provider_id]);
    }

    /**
     * Remove all the Google Calendar event IDs from appointment records.
     *
     * @param int $provider_id Matching provider ID.
     */
    public function clear_caldav_sync_ids(int $provider_id): void
    {
        $this->db->update('appointments', ['id_caldav_calendar' => null], ['id_users_provider' => $provider_id]);
    }

    /**
     * Deletes recurring CalDAV events for the provided date period.
     *
     * @param string $start_date_time
     * @param string $end_date_time
     *
     * @return void
     */
    public function delete_caldav_recurring_events(string $start_date_time, string $end_date_time): void
    {
        $this->db
            ->where('start_datetime >=', $start_date_time)
            ->where('end_datetime <=', $end_date_time)
            ->where('is_unavailability', true)
            ->like('id_caldav_calendar', 'RECURRENCE')
            ->delete('appointments');
    }

    /**
     * Remove an existing appointment from the database.
     *
     * @param int $appointment_id Appointment ID.
     *
     * @throws RuntimeException
     */
    public function delete(int $appointment_id): void
    {
        $this->db->delete('appointments', ['id' => $appointment_id]);

        $this->load->library('crm_sync');

        $this->crm_sync->enqueue('appointment.cancelled', null, $appointment_id);
    }

    /**
     * Get the attendants number for the requested period.
     *
     * @param DateTime $start Period start.
     * @param DateTime $end Period end.
     * @param int $service_id Service ID.
     * @param int $provider_id Provider ID.
     * @param int|null $exclude_appointment_id Exclude an appointment from the result set.
     *
     * @return int Returns the number of appointments that match the provided criteria.
     */
    public function get_attendants_number_for_period(
        DateTime $start,
        DateTime $end,
        int $service_id,
        int $provider_id,
        ?int $exclude_appointment_id = null,
    ): int {
        if ($exclude_appointment_id) {
            $this->db->where('id !=', $exclude_appointment_id);
        }

        $result = $this->db
            ->select('count(*) AS attendants_number')
            ->from('appointments')
            ->group_start()
            ->group_start()
            ->where('start_datetime <=', $start->format('Y-m-d H:i:s'))
            ->where('end_datetime >', $start->format('Y-m-d H:i:s'))
            ->group_end()
            ->or_group_start()
            ->where('start_datetime <', $end->format('Y-m-d H:i:s'))
            ->where('end_datetime >=', $end->format('Y-m-d H:i:s'))
            ->group_end()
            ->group_end()
            ->where('id_services', $service_id)
            ->where('id_users_provider', $provider_id)
            ->get()
            ->row_array();

        return $result['attendants_number'];
    }

    /**
     *
     * Returns the number of the other service attendants number for the provided time slot.
     *
     * @param DateTime $start Period start.
     * @param DateTime $end Period end.
     * @param int $service_id Service ID.
     * @param int $provider_id Provider ID.
     * @param int|null $exclude_appointment_id Exclude an appointment from the result set.
     *
     * @return int Returns the number of appointments that match the provided criteria.
     */
    public function get_other_service_attendants_number(
        DateTime $start,
        DateTime $end,
        int $service_id,
        int $provider_id,
        ?int $exclude_appointment_id = null,
    ): int {
        if ($exclude_appointment_id) {
            $this->db->where('id !=', $exclude_appointment_id);
        }

        $result = $this->db
            ->select('count(*) AS attendants_number')
            ->from('appointments')
            ->group_start()
            ->group_start()
            ->where('start_datetime <=', $start->format('Y-m-d H:i:s'))
            ->where('end_datetime >', $start->format('Y-m-d H:i:s'))
            ->group_end()
            ->or_group_start()
            ->where('start_datetime <', $end->format('Y-m-d H:i:s'))
            ->where('end_datetime >=', $end->format('Y-m-d H:i:s'))
            ->group_end()
            ->group_end()
            ->where('id_services !=', $service_id)
            ->where('id_users_provider', $provider_id)
            ->get()
            ->row_array();

        return $result['attendants_number'];
    }

    /**
     * Get the query builder interface, configured for use with the appointments table.
     *
     * @return CI_DB_query_builder
     */
    public function query(): CI_DB_query_builder
    {
        return $this->db->from('appointments');
    }

    /**
     * Search appointments by the provided keyword.
     *
     * @param string $keyword Search keyword.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of appointments.
     */
    public function search(string $keyword, ?int $limit = null, ?int $offset = null, ?string $order_by = null): array
    {
        $appointments = $this->db
            ->select('appointments.*')
            ->from('appointments')
            ->join('services', 'services.id = appointments.id_services', 'left')
            ->join('users AS providers', 'providers.id = appointments.id_users_provider', 'inner')
            ->join('users AS customers', 'customers.id = appointments.id_users_customer', 'left')
            ->where('is_unavailability', false)
            ->group_start()
            ->like('appointments.start_datetime', $keyword)
            ->or_like('appointments.end_datetime', $keyword)
            ->or_like('appointments.location', $keyword)
            ->or_like('appointments.hash', $keyword)
            ->or_like('appointments.notes', $keyword)
            ->or_like('services.name', $keyword)
            ->or_like('services.description', $keyword)
            ->or_like('providers.first_name', $keyword)
            ->or_like('providers.last_name', $keyword)
            ->or_like('providers.email', $keyword)
            ->or_like('providers.phone_number', $keyword)
            ->or_like('customers.first_name', $keyword)
            ->or_like('customers.last_name', $keyword)
            ->or_like('customers.email', $keyword)
            ->or_like('customers.phone_number', $keyword)
            ->group_end()
            ->limit($limit)
            ->offset($offset)
            ->order_by($this->quote_order_by($order_by))
            ->get()
            ->result_array();

        foreach ($appointments as &$appointment) {
            $this->cast($appointment);
        }

        return $appointments;
    }

    /**
     * Get appointments as options for dropdowns.
     *
     * @param array|string|null $where Where conditions.
     *
     * @return array Returns an array of options with 'value' and 'label' keys.
     */
    public function to_options(array|string|null $where = null): array
    {
        if ($where !== null) {
            $this->db->where($where);
        }

        $appointments = $this->db
            ->select('appointments.id, appointments.start_datetime, services.name AS service_name')
            ->from('appointments')
            ->join('services', 'services.id = appointments.id_services', 'left')
            ->where('is_unavailability', false)
            ->order_by('start_datetime', 'DESC')
            ->get()
            ->result_array();

        $options = [];

        foreach ($appointments as $appointment) {
            $options[] = [
                'value' => (int) $appointment['id'],
                'label' => $appointment['start_datetime'] . ' - ' . ($appointment['service_name'] ?? 'N/A'),
            ];
        }

        return $options;
    }

    /**
     * Salon Flora customization (2026-08-24, KVKK hardening) - decrypt the PII fields of a raw `users`
     * row fetched directly (bypassing Customers_model/Providers_model, which would otherwise handle
     * this themselves - see their decrypt_pii()). Safe to call on an empty array (get_where().row_array()
     * returns [] when the id doesn't match anything).
     *
     * @param array $row
     *
     * @return array
     */
    private function decrypt_user_pii(array $row): array
    {
        foreach (['email', 'phone_number', 'address', 'state', 'zip_code', 'notes'] as $field) {
            if (array_key_exists($field, $row) && sf_pii_is_encrypted($row[$field])) {
                $row[$field] = sf_pii_decrypt($row[$field]);
            }
        }

        return $row;
    }

    /**
     * Load related resources to an appointment.
     *
     * @param array $appointment Associative array with the appointment data.
     * @param array $resources Resource names to be attached ("service", "provider", "customer" supported).
     *
     * @throws InvalidArgumentException
     */
    public function load(array &$appointment, array $resources): void
    {
        if (empty($appointment) || empty($resources)) {
            return;
        }

        foreach ($resources as $resource) {
            switch ($resource) {
                case 'service':
                    $appointment['service'] = $this->db
                        ->get_where('services', [
                            'id' => $appointment['id_services'] ?? ($appointment['serviceId'] ?? null),
                        ])
                        ->row_array();
                    break;

                case 'provider':
                    // Salon Flora customization - raw fetch bypasses Providers_model, decrypt PII here.
                    $appointment['provider'] = $this->decrypt_user_pii(
                        $this->db
                            ->get_where('users', [
                                'id' => $appointment['id_users_provider'] ?? ($appointment['providerId'] ?? null),
                            ])
                            ->row_array(),
                    );
                    break;

                case 'customer':
                    // Salon Flora customization - raw fetch bypasses Customers_model, decrypt PII here.
                    $appointment['customer'] = $this->decrypt_user_pii(
                        $this->db
                            ->get_where('users', [
                                'id' => $appointment['id_users_customer'] ?? ($appointment['customerId'] ?? null),
                            ])
                            ->row_array(),
                    );
                    break;

                default:
                    throw new InvalidArgumentException(
                        'The requested appointment relation is not supported: ' . $resource,
                    );
            }
        }
    }

    /**
     * Convert the database appointment record to the equivalent API resource.
     *
     * @param array $appointment Appointment data.
     */
    public function api_encode(array &$appointment): void
    {
        $encoded_resource = [
            'id' => array_key_exists('id', $appointment) ? (int) $appointment['id'] : null,
            'book' => $appointment['book_datetime'],
            'start' => $appointment['start_datetime'],
            'end' => $appointment['end_datetime'],
            'hash' => $appointment['hash'],
            'color' => $appointment['color'],
            'status' => $appointment['status'],
            'location' => $appointment['location'],
            'notes' => $appointment['notes'],
            'customerId' => $appointment['id_users_customer'] !== null ? (int) $appointment['id_users_customer'] : null,
            'providerId' => $appointment['id_users_provider'] !== null ? (int) $appointment['id_users_provider'] : null,
            'serviceId' => $appointment['id_services'] !== null ? (int) $appointment['id_services'] : null,
            'meetingLink' => $appointment['meeting_link'],
            'googleCalendarId' =>
                $appointment['id_google_calendar'] !== null ? $appointment['id_google_calendar'] : null,
            'caldavCalendarId' =>
                $appointment['id_caldav_calendar'] !== null ? $appointment['id_caldav_calendar'] : null,
        ];

        $appointment = $encoded_resource;
    }

    /**
     * Convert the API resource to the equivalent database appointment record.
     *
     * @param array $appointment API resource.
     * @param array|null $base Base appointment data to be overwritten with the provided values (useful for updates).
     */
    public function api_decode(array &$appointment, ?array $base = null): void
    {
        $decoded_resource = $base ?: [];

        if (array_key_exists('id', $appointment)) {
            $decoded_resource['id'] = $appointment['id'];
        }

        if (array_key_exists('book', $appointment)) {
            $decoded_resource['book_datetime'] = $appointment['book'];
        }

        if (array_key_exists('start', $appointment)) {
            $decoded_resource['start_datetime'] = $appointment['start'];
        }

        if (array_key_exists('end', $appointment)) {
            $decoded_resource['end_datetime'] = $appointment['end'];
        }

        if (array_key_exists('hash', $appointment)) {
            $decoded_resource['hash'] = $appointment['hash'];
        }

        if (array_key_exists('color', $appointment)) {
            $decoded_resource['color'] = $appointment['color'];
        }

        if (array_key_exists('location', $appointment)) {
            $decoded_resource['location'] = $appointment['location'];
        }

        if (array_key_exists('status', $appointment)) {
            $decoded_resource['status'] = $appointment['status'];
        }

        if (array_key_exists('notes', $appointment)) {
            $decoded_resource['notes'] = $appointment['notes'];
        }

        if (array_key_exists('customerId', $appointment)) {
            $decoded_resource['id_users_customer'] = $appointment['customerId'];
        }

        if (array_key_exists('providerId', $appointment)) {
            $decoded_resource['id_users_provider'] = $appointment['providerId'];
        }

        if (array_key_exists('serviceId', $appointment)) {
            $decoded_resource['id_services'] = $appointment['serviceId'];
        }

        if (array_key_exists('googleCalendarId', $appointment)) {
            $decoded_resource['id_google_calendar'] = $appointment['googleCalendarId'];
        }

        if (array_key_exists('caldavCalendarId', $appointment)) {
            $decoded_resource['id_caldav_calendar'] = $appointment['caldavCalendarId'];
        }

        if (array_key_exists('meetingLink', $appointment)) {
            $decoded_resource['meeting_link'] = $appointment['meetingLink'];
        }

        $decoded_resource['is_unavailability'] = false;

        $appointment = $decoded_resource;
    }

    /**
     * Calculate the end date time of an appointment based on the selected service.
     *
     * @param array $appointment Appointment data.
     *
     * @return string Returns the end date time value.
     *
     * @throws Exception
     */
    public function calculate_end_datetime(array $appointment): string
    {
        $duration = $this->db->get_where('services', ['id' => $appointment['id_services']])?->row()?->duration;

        $end_date_time_object = new DateTime($appointment['start_datetime']);

        $end_date_time_object->add(new DateInterval('PT' . $duration . 'M'));

        return $end_date_time_object->format('Y-m-d H:i:s');
    }

    /**
     * Check if the provider has a conflicting appointment at the given time period.
     *
     * @param int $provider_id Provider ID.
     * @param string $start_datetime Start date time of the appointment.
     * @param string $end_datetime End date time of the appointment.
     * @param int|null $exclude_appointment_id Exclude an appointment from the conflict check (useful for updates).
     *
     * @return bool Returns true if there is a conflict, false otherwise.
     */
    public function has_provider_conflict(
        int $provider_id,
        string $start_datetime,
        string $end_datetime,
        ?int $exclude_appointment_id = null,
        ?int $branch_id = null,
    ): bool {
        $this->db->select('id')->from('appointments')->where('id_users_provider', $provider_id);

        if ($exclude_appointment_id) {
            $this->db->where('id !=', $exclude_appointment_id);
        }

        // Multi-branch support: apply branch filter only if provided and branch count is > 1
        if ($branch_id !== null) {
            $this->load->model('branches_model');
            if ($this->branches_model->count_active() > 1) {
                $this->db->where('id_branches', $branch_id);
            }
        }

        // Check for overlapping appointments:
        // An overlap occurs when:  (existing_start < new_end) AND (existing_end > new_start)

        return $this->db
            ->group_start()
            ->where('start_datetime <', $end_datetime)
            ->where('end_datetime >', $start_datetime)
            ->group_end()
            ->get()
            ->num_rows() > 0;
    }

    /**
     * Salon Flora customization - set the real check-in/check-out timestamp of an appointment. A dedicated
     * partial update, bypassing validate(), since check-in/check-out only ever touches one column and the
     * full-record validation in validate() requires fields (start_datetime, id_services, etc.) that are
     * irrelevant here.
     *
     * @param int $appointment_id Appointment ID.
     * @param string $field Either "actual_start_datetime" or "actual_end_datetime".
     * @param string $value Datetime value (Y-m-d H:i:s).
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function set_actual_datetime(int $appointment_id, string $field, ?string $value): void
    {
        if (!in_array($field, ['actual_start_datetime', 'actual_end_datetime'], true)) {
            throw new InvalidArgumentException('Invalid field provided: ' . $field);
        }

        $count = $this->db->get_where('appointments', ['id' => $appointment_id])->num_rows();

        if (!$count) {
            throw new InvalidArgumentException('The provided appointment ID does not exist in the database: ' . $appointment_id);
        }

        if (!$this->db->update('appointments', [$field => $value], ['id' => $appointment_id])) {
            throw new RuntimeException('Could not update appointment record.');
        }
    }

    /**
     * Salon Flora customization (2026-08-25) - the EFFECTIVE billed duration/price for a session, shared by
     * Reports.php (the daily revenue report / provider commissions) and the frontend preview
     * (App.Utils.SessionStatus.effectivePricing() mirrors this exactly - keep the two in sync if this changes).
     *
     * Billed duration:
     * - No real check-in/check-out yet: falls back to a booking-time custom_duration_minutes override, or the
     *   service's own planned duration.
     * - Real duration within +/- 'session_deviation_tolerance_minutes' (setting, default
     *   SESSION_DEVIATION_TOLERANCE_MINUTES_DEFAULT) of the planned duration: the FULL planned duration is
     *   billed ("Normal" - small deviations either way don't change what's billed).
     * - Ran over by more than the tolerance: the real (longer) duration is billed.
     * - Left more than the tolerance early: the full planned duration is billed ONLY if the session was
     *   classified 'justified' (appointments.early_exit_justification) AND that classification has been
     *   approved (early_exit_approved_by is set - see Calendar.php::check_out()); otherwise the real (shorter)
     *   duration is billed. An unapproved therapist self-classification never affects billing.
     *
     * Replaces the old rule that blindly rounded the real duration DOWN to the nearest 30 minutes regardless of
     * cause - which, for example, billed a 54-minute session against a 60-minute service as just 30 minutes. That
     * wasn't an intentional "small overrun is free" discount, it was a real underpayment of provider commissions.
     *
     * The hourly rate is derived from whichever duration variant of the service was actually booked - a
     * service's duration variants are proportional (e.g. Klasik Masaj 60dk=2500/90dk=3750/120dk=5000), so any
     * variant's own price/duration ratio gives the same per-hour rate.
     *
     * A price_override on the appointment always wins over the hourly-rate calculation for the billed price (but
     * never affects an 'hourly' commission_type payout, which is always rate × effective duration).
     *
     * @param array $service Must include 'price' and 'duration' (minutes).
     * @param array $appointment May include start_datetime, actual_start_datetime, actual_end_datetime,
     *   custom_duration_minutes, price_override, early_exit_justification, early_exit_approved_by.
     *
     * @return array{minutes: int, price: float, hourly_rate: float}
     */
    public function compute_effective_billing(array $service, array $appointment): array
    {
        $service_duration = (int) $service['duration'];

        $expected_minutes = ($appointment['custom_duration_minutes'] ?? null) !== null
            ? (int) $appointment['custom_duration_minutes']
            : $service_duration;

        $raw_minutes = null;

        if (!empty($appointment['actual_start_datetime']) && !empty($appointment['actual_end_datetime'])) {
            $baseline = setting('session_duration_baseline', 'check_in') === 'booked_start' && !empty($appointment['start_datetime'])
                ? $appointment['start_datetime']
                : $appointment['actual_start_datetime'];

            $raw_minutes = (int) round(
                (strtotime($appointment['actual_end_datetime']) - strtotime($baseline)) / 60,
            );
        }

        if ($raw_minutes === null) {
            $minutes = $expected_minutes;
        } else {
            $delta_minutes = $raw_minutes - $expected_minutes;
            $tolerance = (int) setting('session_deviation_tolerance_minutes', SESSION_DEVIATION_TOLERANCE_MINUTES_DEFAULT);

            if (abs($delta_minutes) <= $tolerance) {
                $minutes = $expected_minutes;
            } elseif ($delta_minutes > $tolerance) {
                $minutes = $raw_minutes;
            } else {
                $justified = ($appointment['early_exit_justification'] ?? null) === 'justified'
                    && !empty($appointment['early_exit_approved_by']);

                $minutes = $justified ? $expected_minutes : $raw_minutes;
            }
        }

        $hourly_rate = $service_duration > 0 ? ((float) $service['price'] / $service_duration) * 60 : (float) $service['price'];

        $price_override = $appointment['price_override'] ?? null;

        $price = $price_override !== null ? (float) $price_override : round($hourly_rate * ($minutes / 60), 2);

        return ['minutes' => $minutes, 'price' => $price, 'hourly_rate' => $hourly_rate];
    }

    /**
     * Salon Flora customization - manually set (correct/backfill) BOTH real check-in/check-out timestamps at
     * once. Used by staff to fix a mistaken or missed check-in/check-out so reports/commissions compute from the
     * right duration. Unlike check_in()/check_out(), this trusts the caller-supplied values rather than the
     * server clock - the controller is responsible for restricting this to admins/secretaries.
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function set_session_times(int $appointment_id, ?string $actual_start_datetime, ?string $actual_end_datetime): void
    {
        $count = $this->db->get_where('appointments', ['id' => $appointment_id])->num_rows();

        if (!$count) {
            throw new InvalidArgumentException('The provided appointment ID does not exist in the database: ' . $appointment_id);
        }

        if (
            $actual_start_datetime !== null &&
            $actual_end_datetime !== null &&
            $actual_end_datetime < $actual_start_datetime
        ) {
            throw new RuntimeException('Seans bitişi başlangıçtan önce olamaz.');
        }

        if (
            !$this->db->update(
                'appointments',
                [
                    'actual_start_datetime' => $actual_start_datetime,
                    'actual_end_datetime' => $actual_end_datetime,
                ],
                ['id' => $appointment_id],
            )
        ) {
            throw new RuntimeException('Could not update appointment record.');
        }
    }

    /**
     * Salon Flora customization - record the deviation between the expected session duration (from the real
     * check-in time) and what actually happened, along with the reason the staff member gave for it. Pass null
     * for all three fields to clear a previously recorded deviation (e.g. when the session times are reset).
     */
    public function set_session_deviation(
        int $appointment_id,
        ?string $type,
        ?int $minutes,
        ?string $reason,
    ): void {
        $count = $this->db->get_where('appointments', ['id' => $appointment_id])->num_rows();

        if (!$count) {
            throw new InvalidArgumentException('The provided appointment ID does not exist in the database: ' . $appointment_id);
        }

        if (
            !$this->db->update(
                'appointments',
                [
                    'session_deviation_type' => $type,
                    'session_deviation_minutes' => $minutes,
                    'session_deviation_reason' => $reason !== null ? mb_substr($reason, 0, 255) : null,
                ],
                ['id' => $appointment_id],
            )
        ) {
            throw new RuntimeException('Could not update appointment record.');
        }
    }

    /**
     * Salon Flora customization (2026-08-25) - record an early-exit classification (see
     * Calendar.php::check_out() and compute_effective_billing()). Pass all null to clear a previously
     * recorded classification (e.g. when the session times are reset).
     *
     * @param int $appointment_id
     * @param string|null $justification 'justified' | 'unjustified' | null.
     * @param string|null $reason_code One of EARLY_EXIT_REASON_CODES' keys, or null.
     * @param int|null $approved_by User id whose role authorizes this classification for billing purposes
     *   (admin/secretary immediately; null means a therapist's own claim is still pending review - see
     *   review_early_exit() in Calendar.php).
     */
    public function set_early_exit_justification(
        int $appointment_id,
        ?string $justification,
        ?string $reason_code,
        ?int $approved_by,
    ): void {
        $count = $this->db->get_where('appointments', ['id' => $appointment_id])->num_rows();

        if (!$count) {
            throw new InvalidArgumentException('The provided appointment ID does not exist in the database: ' . $appointment_id);
        }

        if (
            !$this->db->update(
                'appointments',
                [
                    'early_exit_justification' => $justification,
                    'early_exit_reason_code' => $reason_code,
                    'early_exit_approved_by' => $approved_by,
                    'early_exit_approved_at' => $approved_by !== null ? date('Y-m-d H:i:s') : null,
                ],
                ['id' => $appointment_id],
            )
        ) {
            throw new RuntimeException('Could not update appointment record.');
        }
    }

    /**
     * Salon Flora customization - assign (or clear) the physical station an appointment uses, independently of
     * the automatic free-station algorithm run during save_appointment(). Used when staff manually override the
     * station from the calendar.
     */
    public function set_station(int $appointment_id, ?int $station_id, bool $manually_assigned): void
    {
        $count = $this->db->get_where('appointments', ['id' => $appointment_id])->num_rows();

        if (!$count) {
            throw new InvalidArgumentException('The provided appointment ID does not exist in the database: ' . $appointment_id);
        }

        if (
            !$this->db->update(
                'appointments',
                [
                    'id_stations' => $station_id,
                    'station_assigned_manually' => $manually_assigned ? 1 : 0,
                ],
                ['id' => $appointment_id],
            )
        ) {
            throw new RuntimeException('Could not update appointment record.');
        }
    }

    /**
     * Salon Flora customization - record (or clear) payment/collection details for a completed appointment.
     * Only admins/secretaries are allowed to call this (enforced in the controller, not here) - providers can
     * check a session out but never touch payment data.
     */
    public function set_payment(
        int $appointment_id,
        string $payment_status,
        ?string $payment_method,
        ?float $payment_amount,
        ?float $payment_balance_amount,
        bool $is_invoiced,
        int $recorded_by_user_id,
    ): void {
        $count = $this->db->get_where('appointments', ['id' => $appointment_id])->num_rows();

        if (!$count) {
            throw new InvalidArgumentException('The provided appointment ID does not exist in the database: ' . $appointment_id);
        }

        if (
            !$this->db->update(
                'appointments',
                [
                    'payment_status' => $payment_status,
                    'payment_method' => $payment_method,
                    'payment_amount' => $payment_amount,
                    'payment_balance_amount' => $payment_balance_amount,
                    'is_invoiced' => $is_invoiced ? 1 : 0,
                    'payment_recorded_by' => $recorded_by_user_id,
                ],
                ['id' => $appointment_id],
            )
        ) {
            throw new RuntimeException('Could not update appointment record.');
        }
    }

    /**
     * Salon Flora customization - check whether another provider assigned to the same station already has an
     * overlapping appointment. This is the server-side counterpart of the station-capacity-aware availability
     * calculation in Availability::get_available_periods() and guards against race conditions (e.g. two
     * simultaneous bookings for station mates).
     *
     * @param array $station_mate_provider_ids Provider (user) IDs assigned to the same station, excluding the
     * provider that is currently being booked.
     * @param string $start_datetime Appointment start datetime.
     * @param string $end_datetime Appointment end datetime.
     * @param int|null $exclude_appointment_id Exclude an appointment from the check (e.g. when editing it).
     *
     * @return bool Returns whether a conflicting appointment exists.
     */
    public function has_station_conflict(
        int $station_id,
        string $start_datetime,
        string $end_datetime,
        ?int $exclude_appointment_id = null,
        ?int $branch_id = null,
    ): bool {
        // Salon Flora customization: a station is now tied to SERVICES, not to a fixed set of providers - it's a
        // shared physical resource (room/table), so ANY appointment using it (regardless of which provider) blocks
        // it for everyone else. Appointments with no station recorded (id_stations IS NULL) no longer count as a
        // conflict for every station - they simply don't occupy any physical room.
        $this->db
            ->select('id')
            ->from('appointments')
            ->where('is_unavailability', false)
            ->where('id_stations', $station_id)
            // Salon Flora customization - a cancelled/draft appointment never actually occupied the
            // physical station, so it must not keep blocking it for everyone else.
            ->where_not_in('status', ['Cancelled', 'Draft']);

        if ($exclude_appointment_id) {
            $this->db->where('id !=', $exclude_appointment_id);
        }

        // Multi-branch support: apply branch filter only if provided and branch count is > 1
        if ($branch_id !== null) {
            $this->load->model('branches_model');
            if ($this->branches_model->count_active() > 1) {
                $this->db->where('id_branches', $branch_id);
            }
        }

        return $this->db
            ->group_start()
            ->where('start_datetime <', $end_datetime)
            ->where('end_datetime >', $start_datetime)
            ->group_end()
            ->get()
            ->num_rows() > 0;
    }

    /**
     * Salon Flora customization - a provider must only ever see a customer's first name, never their surname,
     * phone, email, address or any other contact/personal detail - those are reserved for admins/secretaries.
     * Used everywhere a customer record is attached to an appointment before it reaches the frontend (the
     * calendar feed, the active-sessions widget, the single-appointment lookup for the edit modal).
     *
     * @param array $customer Full customer record.
     * @param string|null $role_slug Current user's role slug.
     *
     * @return array The customer record, unmodified for admins/secretaries, reduced to {id, first_name} for
     *   providers.
     */
    public function filter_customer_for_role(array $customer, ?string $role_slug): array
    {
        if ($role_slug !== DB_SLUG_PROVIDER) {
            return $customer;
        }

        return [
            'id' => $customer['id'] ?? null,
            'first_name' => $customer['first_name'] ?? null,
        ];
    }

    /**
     * Salon Flora customization - appointments that are currently "in session": checked in (actual_start_datetime
     * set) but not yet checked out (actual_end_datetime still null). Powers the active-sessions widget.
     *
     * @param int|null $provider_id Restrict to a single provider (used for the "provider" role), or null for all.
     * @param string|null $role_slug Current user's role slug - controls how much of the customer record is
     *   attached (see filter_customer_for_role()).
     */
    public function get_active_sessions(?int $provider_id = null, ?string $role_slug = null): array
    {
        $this->db
            ->select('*')
            ->from('appointments')
            ->where('is_unavailability', false)
            ->where('actual_start_datetime IS NOT NULL', null, false)
            ->where('actual_end_datetime IS NULL', null, false)
            ->order_by('actual_start_datetime', 'asc');

        if ($provider_id !== null) {
            $this->db->where('id_users_provider', $provider_id);
        }

        $appointments = $this->db->get()->result_array();

        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');
        $this->load->model('stations_model');

        foreach ($appointments as &$appointment) {
            $appointment['provider'] = filter_sensitive_user_data(
                $this->providers_model->find($appointment['id_users_provider']),
            );
            $appointment['service'] = $this->services_model->find($appointment['id_services']);
            $appointment['customer'] = $this->filter_customer_for_role(
                $this->customers_model->find($appointment['id_users_customer']),
                $role_slug,
            );
            $appointment['station'] = !empty($appointment['id_stations'])
                ? $this->stations_model->find((int) $appointment['id_stations'])
                : null;
        }

        unset($appointment);

        return $appointments;
    }

    /**
     * Salon Flora customization - completed sessions (today) whose payment hasn't actually been collected yet.
     * This is the kalıcı ("persistent") counterpart of the active-sessions list: a session that finishes with
     * payment missing must stay visible SOMEWHERE even after it drops out of "in session", or staff have no way
     * to notice it later in the day. Marking a session "Tahsilat yapılmadı / eksik" only closes the mandatory
     * dialog (see Calendar::update_payment()) - it's an acknowledgement, not a resolution, so those sessions stay
     * in this list exactly like untouched 'pending' ones. Only 'collected' removes a session from it.
     *
     * @param int|null $provider_id Restrict to a single provider (used for the "provider" role - though providers
     *   never see this list at all, see Calendar::get_active_sessions()).
     * @param string|null $role_slug Current user's role slug - controls how much of the customer record is
     *   attached (see filter_customer_for_role()).
     */
    public function get_unpaid_sessions(?int $provider_id = null, ?string $role_slug = null): array
    {
        $this->db
            ->select('*')
            ->from('appointments')
            ->where('is_unavailability', false)
            ->where('actual_end_datetime IS NOT NULL', null, false)
            ->where('payment_status !=', PAYMENT_STATUS_COLLECTED)
            // Salon Flora bugfix - filter by the date the session actually FINISHED (actual_end_datetime),
            // not when it was booked to start. A session booked at 23:30 that runs past midnight used to
            // fall out of "today" entirely under the old start_datetime filter.
            ->where('DATE(actual_end_datetime) =', date('Y-m-d'))
            ->order_by('actual_end_datetime', 'asc');

        if ($provider_id !== null) {
            $this->db->where('id_users_provider', $provider_id);
        }

        $appointments = $this->db->get()->result_array();

        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');

        foreach ($appointments as &$appointment) {
            $appointment['provider'] = filter_sensitive_user_data(
                $this->providers_model->find($appointment['id_users_provider']),
            );
            $appointment['service'] = $this->services_model->find($appointment['id_services']);
            $appointment['customer'] = $this->filter_customer_for_role(
                $this->customers_model->find($appointment['id_users_customer']),
                $role_slug,
            );
        }

        unset($appointment);

        return $appointments;
    }

    /**
     * Trigger auto-invoice creation if enabled for a completed appointment.
     *
     * This method checks if automatic invoicing is enabled in accounting settings,
     * retrieves the appointment and customer details, and attempts to create an invoice
     * via the configured connector (e.g., Paraşüt). Any errors are logged but do NOT
     * block the appointment workflow - invoicing failures are non-fatal.
     *
     * Note: This is a skeleton method with no call points yet. Future integration will
     * invoke this at appointment completion or status transitions. Currently safe to call
     * from any business logic without affecting existing flows.
     *
     * @param int $appointment_id Appointment ID to create an invoice for.
     *
     * @return void Errors are logged, never thrown.
     */
    public function trigger_auto_invoice_if_enabled(int $appointment_id): void
    {
        try {
            $this->load->model('accounting_settings_model');
            $this->load->library('accounting/parasut_connector');

            // Check if auto-invoicing is enabled
            $connection = $this->accounting_settings_model->get_connection();

            if (!$connection || !$connection['auto_invoice_enabled']) {
                return; // Auto-invoicing is disabled
            }

            // Verify connection is valid
            if (!$this->parasut_connector->is_connected()) {
                log_message('warning', 'Accounting connection is not active, skipping auto-invoice for appointment ' . $appointment_id);
                return;
            }

            // Fetch appointment and customer details
            $appointment = $this->find($appointment_id);
            $this->load->model('customers_model');
            $customer = $this->customers_model->find($appointment['id_users_customer']);

            if (!$appointment || !$customer) {
                log_message('error', 'Could not find appointment or customer for auto-invoice, appointment_id=' . $appointment_id);
                return;
            }

            // Attempt invoice creation
            $external_invoice_id = $this->parasut_connector->create_invoice($appointment, $customer);

            log_message(
                'info',
                'Auto-invoice created successfully for appointment ' . $appointment_id .
                ', external_id=' . $external_invoice_id,
            );
        } catch (Throwable $e) {
            // Log the error but do not rethrow - invoicing failure is not a business-critical failure
            log_message('error', 'Auto-invoice creation failed for appointment ' . $appointment_id . ': ' . $e->getMessage());
        }
    }
}

