<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Customer Packages Model
 *
 * Handles database operations for multi-session package management.
 * ---------------------------------------------------------------------------- */

class Packages_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer',
        'id_users_customer' => 'integer',
        'id_services' => 'integer',
        'total_sessions' => 'integer',
        'used_sessions' => 'integer',
        'unit_price' => 'float',
        'sold_by' => 'integer',
    ];

    /**
     * Save (insert or update) a package.
     *
     * @param array $package Associative array with the package data.
     * @return int Returns the package ID.
     * @throws InvalidArgumentException
     */
    public function save(array $package): int
    {
        $this->validate($package);

        if (empty($package['id'])) {
            $package_id = $this->insert($package);
        } else {
            $package_id = $this->update($package);
        }

        return $package_id;
    }

    /**
     * Validate the package data.
     *
     * @param array $package Associative array with the package data.
     * @throws InvalidArgumentException
     */
    public function validate(array $package): void
    {
        if (!empty($package['id'])) {
            $count = $this->db->get_where('customer_packages', ['id' => $package['id']])->num_rows();

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided package ID does not exist in the database: ' . $package['id'],
                );
            }
        }

        if (empty($package['id_users_customer']) || empty($package['id_services']) || empty($package['total_sessions'])) {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($package, true));
        }

        if ((int) $package['total_sessions'] <= 0) {
            throw new InvalidArgumentException('Total sessions must be greater than 0.');
        }

        if (isset($package['used_sessions']) && (int) $package['used_sessions'] > (int) $package['total_sessions']) {
            throw new InvalidArgumentException('Used sessions cannot exceed total sessions.');
        }
    }

    /**
     * Insert a new package into the database.
     *
     * @param array $package Associative array with the package data.
     * @return int Returns the package ID.
     * @throws RuntimeException
     */
    protected function insert(array $package): int
    {
        $package['created_at'] = date('Y-m-d H:i:s');
        if (!isset($package['purchased_at'])) {
            $package['purchased_at'] = date('Y-m-d H:i:s');
        }

        if (!$this->db->insert('customer_packages', $package)) {
            throw new RuntimeException('Could not insert package.');
        }

        return $this->db->insert_id();
    }

    /**
     * Update an existing package.
     *
     * @param array $package Associative array with the package data.
     * @return int Returns the package ID.
     * @throws RuntimeException
     */
    protected function update(array $package): int
    {
        $package['updated_at'] = date('Y-m-d H:i:s');

        if (!$this->db->update('customer_packages', $package, ['id' => $package['id']])) {
            throw new RuntimeException('Could not update package.');
        }

        return $package['id'];
    }

    /**
     * Remove an existing package from the database.
     *
     * @param int $package_id Package ID.
     */
    public function delete(int $package_id): void
    {
        // Delete related package sessions first
        $this->db->delete('customer_package_sessions', ['id_customer_packages' => $package_id]);

        // Then delete the package
        $this->db->delete('customer_packages', ['id' => $package_id]);
    }

    /**
     * Get a specific package from the database.
     *
     * @param int $package_id The ID of the record to be returned.
     * @return array Returns an array with the package data.
     * @throws InvalidArgumentException
     */
    public function find(int $package_id): array
    {
        $package = $this->db->get_where('customer_packages', ['id' => $package_id])->row_array();

        if (!$package) {
            throw new InvalidArgumentException('The provided package ID was not found in the database: ' . $package_id);
        }

        $this->cast($package);

        return $package;
    }

    /**
     * Get all packages that match the provided criteria.
     *
     * @param array|string|null $where Where conditions
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     * @return array Returns an array of packages.
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

        if ($order_by !== null) {
            $this->db->order_by($this->quote_order_by($order_by));
        }

        $packages = $this->db->get('customer_packages', $limit, $offset)->result_array();

        foreach ($packages as &$package) {
            $this->cast($package);
        }

        return $packages;
    }

    /**
     * Search packages by keyword.
     *
     * @param string $keyword Search keyword.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @return array Returns an array of packages.
     */
    public function search(string $keyword, ?int $limit = null, ?int $offset = null): array
    {
        $packages = $this->db
            ->select('cp.*, s.name as service_name')
            ->from('customer_packages cp')
            ->join('services s', 's.id = cp.id_services', 'left')
            ->like('s.name', $keyword)
            ->limit($limit)
            ->offset($offset)
            ->order_by('cp.created_at DESC')
            ->get()
            ->result_array();

        foreach ($packages as &$package) {
            $this->cast($package);
        }

        return $packages;
    }

    /**
     * Get all packages for a specific customer.
     *
     * @param int $customer_id Customer ID.
     * @param int|null $service_id Optional service filter.
     * @return array Returns an array of packages.
     */
    public function get_for_customer(int $customer_id, ?int $service_id = null): array
    {
        $where = ['id_users_customer' => $customer_id];

        if ($service_id !== null) {
            $where['id_services'] = $service_id;
        }

        return $this->get($where, null, null, 'created_at DESC');
    }

    /**
     * Get the first active package for a customer and service.
     * Returns the package with the earliest expiry date (FIFO).
     *
     * @param int $customer_id Customer ID.
     * @param int $service_id Service ID.
     * @return array|null Returns package data or null if none found.
     */
    public function get_active_for_customer_service(int $customer_id, int $service_id): ?array
    {
        $now = date('Y-m-d H:i:s');

        // Order by expires_at, treating NULL as the highest value (no expiry = last to use)
        $package = $this->db
            ->select('*')
            ->select($this->db->raw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END as is_no_expiry'))
            ->from('customer_packages')
            ->where('id_users_customer', $customer_id)
            ->where('id_services', $service_id)
            ->where('status', 'active')
            ->where('used_sessions <', $this->db->raw('total_sessions'))
            ->group_start()
            ->where('expires_at IS NULL')
            ->or_where('expires_at >', $now)
            ->group_end()
            ->order_by('is_no_expiry ASC, expires_at ASC')
            ->limit(1)
            ->get()
            ->row_array();

        if (!$package) {
            return null;
        }

        // Remove the temporary helper column
        unset($package['is_no_expiry']);

        $this->cast($package);

        return $package;
    }

    /**
     * Consume one session from a package for an appointment.
     *
     * @param int $package_id Package ID.
     * @param int $appointment_id Appointment ID.
     * @throws RuntimeException
     */
    public function consume_session(int $package_id, int $appointment_id): void
    {
        $this->db->trans_start();

        try {
            // Check if this appointment has already consumed a session from this package
            $existing = $this->db
                ->get_where('customer_package_sessions', ['id_appointments' => $appointment_id])
                ->num_rows();

            if ($existing > 0) {
                // Already consumed, silently return
                $this->db->trans_complete();
                return;
            }

            // Record the session consumption
            $this->db->insert('customer_package_sessions', [
                'id_customer_packages' => $package_id,
                'id_appointments' => $appointment_id,
                'consumed_at' => date('Y-m-d H:i:s'),
            ]);

            // Increment used_sessions
            $this->db->set('used_sessions', 'used_sessions + 1', false);
            $this->db->where('id', $package_id);
            $this->db->update('customer_packages');

            // Get the updated package to check if it's now exhausted
            $package = $this->find($package_id);

            if ((int) $package['used_sessions'] >= (int) $package['total_sessions']) {
                $this->db->update('customer_packages', ['status' => 'exhausted'], ['id' => $package_id]);
            }

            $this->db->trans_complete();
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw new RuntimeException('Could not consume package session: ' . $e->getMessage());
        }
    }

    /**
     * Restore a session that was consumed by an appointment (undo checkout).
     *
     * @param int $appointment_id Appointment ID.
     */
    public function restore_session(int $appointment_id): void
    {
        $this->db->trans_start();

        try {
            // Find the package session record
            $session = $this->db
                ->get_where('customer_package_sessions', ['id_appointments' => $appointment_id])
                ->row_array();

            if (!$session) {
                // No session was consumed for this appointment
                $this->db->trans_complete();
                return;
            }

            $package_id = $session['id_customer_packages'];

            // Delete the session consumption record
            $this->db->delete('customer_package_sessions', ['id_appointments' => $appointment_id]);

            // Decrement used_sessions
            $this->db->set('used_sessions', 'used_sessions - 1', false);
            $this->db->where('id', $package_id);
            $this->db->update('customer_packages');

            // Reset status to 'active' if it was exhausted
            $package = $this->find($package_id);

            if ($package['status'] === 'exhausted') {
                $this->db->update('customer_packages', ['status' => 'active'], ['id' => $package_id]);
            }

            $this->db->trans_complete();
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw new RuntimeException('Could not restore package session: ' . $e->getMessage());
        }
    }
}
