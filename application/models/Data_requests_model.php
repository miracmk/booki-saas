<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Data Requests Model (Faz 30, KVKK/GDPR)
 *
 * Thin CRUD layer for data export/erasure request tracking.
 * ---------------------------------------------------------------------------- */

class Data_requests_model extends EA_Model
{
    /**
     * Protected casts for automatic type conversion on retrieval.
     */
    protected array $casts = [
        'id' => 'integer',
        'id_users' => 'integer',
        'requested_by' => 'integer',
        'file_size' => 'integer',
    ];

    /**
     * Insert a new data request (export or erasure) for a customer.
     *
     * @param int $customer_id Customer ID.
     * @param string $request_type 'export' or 'erasure'.
     * @param int|null $requested_by User ID of the admin/staff member, if applicable.
     * @param string|null $ip_address Originating IP address (for audit trail).
     * @return int The ID of the newly inserted request.
     */
    public function insert_request(int $customer_id, string $request_type, ?int $requested_by = null, ?string $ip_address = null): int
    {
        $this->db->insert('data_requests', [
            'id_users' => $customer_id,
            'request_type' => $request_type,
            'status' => 'pending',
            'requested_by' => $requested_by,
            'ip_address' => $ip_address,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->insert_id();
    }

    /**
     * Retrieve a single data request by ID.
     *
     * @param int $id The request ID.
     * @return array|null The request record, or null if not found.
     */
    public function find(int $id): ?array
    {
        $row = $this->db->get_where('data_requests', ['id' => $id])->row_array();

        if ($row) {
            $this->cast($row);
        }

        return $row ?: null;
    }

    /**
     * Retrieve a single data request by its token hash.
     *
     * @param string $token_hash The token hash string.
     * @return array|null The request record, or null if not found.
     */
    public function find_by_token_hash(string $token_hash): ?array
    {
        $row = $this->db->get_where('data_requests', ['token_hash' => $token_hash])->row_array();

        if ($row) {
            $this->cast($row);
        }

        return $row ?: null;
    }

    /**
     * Mark a data request as being processed.
     *
     * @param int $id Request ID.
     * @return void
     */
    public function mark_processing(int $id): void
    {
        $this->db->update(
            'data_requests',
            ['status' => 'processing', 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $id],
        );
    }

    /**
     * Mark a data request as ready (packaged export).
     *
     * @param int $id Request ID.
     * @param string $token_hash The secure token hash for access.
     * @param string $expires The expiry datetime.
     * @param string $file_path Relative path from storage root.
     * @param int $file_size Size in bytes.
     * @param string $format Format string (e.g. 'json', 'html').
     * @return void
     */
    public function mark_ready(int $id, string $token_hash, string $expires, string $file_path, int $file_size, string $format): void
    {
        $this->db->update(
            'data_requests',
            [
                'status' => 'ready',
                'token_hash' => $token_hash,
                'expires' => $expires,
                'file_path' => $file_path,
                'file_size' => $file_size,
                'format' => $format,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            ['id' => $id],
        );
    }

    /**
     * Mark a data request as failed.
     *
     * @param int $id Request ID.
     * @param string $error_message Error description.
     * @return void
     */
    public function mark_failed(int $id, string $error_message): void
    {
        $this->db->update(
            'data_requests',
            ['status' => 'failed', 'error_message' => $error_message, 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $id],
        );
    }

    /**
     * Mark a data request as completed (e.g. after download or erasure).
     *
     * @param int $id Request ID.
     * @param string|null $notes Optional notes about the completion.
     * @return void
     */
    public function mark_completed(int $id, ?string $notes = null): void
    {
        $update = ['status' => 'completed', 'updated_at' => date('Y-m-d H:i:s')];

        if ($notes !== null) {
            $update['notes'] = $notes;
        }

        $this->db->update('data_requests', $update, ['id' => $id]);
    }

    /**
     * Mark a data request as expired (invalidated).
     *
     * @param int $id Request ID.
     * @return void
     */
    public function mark_expired(int $id): void
    {
        $this->db->update(
            'data_requests',
            [
                'status' => 'expired',
                'token_hash' => null,
                'file_path' => null,
                'file_size' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            ['id' => $id],
        );
    }

    /**
     * Get all requests for a customer, optionally filtered by request type.
     *
     * @param int $customer_id Customer ID.
     * @param string|null $request_type Optional filter: 'export' or 'erasure'.
     * @return array Array of request records.
     */
    public function get_for_customer(int $customer_id, ?string $request_type = null): array
    {
        $this->db->where('id_users', $customer_id);

        if ($request_type !== null) {
            $this->db->where('request_type', $request_type);
        }

        $rows = $this->db
            ->order_by('created_at', 'DESC')
            ->get('data_requests')
            ->result_array();

        foreach ($rows as &$row) {
            $this->cast($row);
        }

        return $rows;
    }

    /**
     * Search data requests by type and/or status (admin panel).
     *
     * @param string|null $request_type Optional filter: 'export' or 'erasure'.
     * @param string|null $status Optional filter: 'pending', 'processing', 'ready', 'failed', 'expired', 'completed'.
     * @param int $limit Maximum rows to return.
     * @param int $offset Offset for pagination.
     * @return array Array of request records.
     */
    public function search_admin(?string $request_type = null, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        if ($request_type !== null) {
            $this->db->where('request_type', $request_type);
        }

        if ($status !== null) {
            $this->db->where('status', $status);
        }

        $rows = $this->db
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->offset($offset)
            ->get('data_requests')
            ->result_array();

        foreach ($rows as &$row) {
            $this->cast($row);
        }

        return $rows;
    }

    /**
     * Get a count of data requests by status, optionally filtered by request type.
     *
     * @param string|null $request_type Optional filter: 'export' or 'erasure'.
     * @return array Associative array: ['pending' => N, 'processing' => N, 'ready' => N, 'failed' => N, 'expired' => N, 'completed' => N]
     */
    public function count_by_status(?string $request_type = null): array
    {
        $query = 'SELECT status, COUNT(*) as count FROM ' . $this->db->dbprefix('data_requests');

        if ($request_type !== null) {
            $query .= ' WHERE request_type = ? GROUP BY status';
            $result = $this->db->query($query, [$request_type])->result_array();
        } else {
            $query .= ' GROUP BY status';
            $result = $this->db->query($query)->result_array();
        }

        $counts = ['pending' => 0, 'processing' => 0, 'ready' => 0, 'failed' => 0, 'expired' => 0, 'completed' => 0];

        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Get ready/pending export requests that have expired (expires < now).
     *
     * @param string $before_datetime Cutoff datetime ('Y-m-d H:i:s').
     * @return array Array of expired request records.
     */
    public function get_expirable(string $before_datetime): array
    {
        $rows = $this->db
            ->where('status', 'ready')
            ->where('expires <', $before_datetime)
            ->order_by('expires', 'ASC')
            ->get('data_requests')
            ->result_array();

        foreach ($rows as &$row) {
            $this->cast($row);
        }

        return $rows;
    }

    /**
     * Get pending export requests (oldest first, for batch processing).
     *
     * @param int $limit Maximum rows to return.
     * @return array Array of pending export records.
     */
    public function get_pending_exports(int $limit = 25): array
    {
        $rows = $this->db
            ->where('status', 'pending')
            ->where('request_type', 'export')
            ->order_by('created_at', 'ASC')
            ->limit($limit)
            ->get('data_requests')
            ->result_array();

        foreach ($rows as &$row) {
            $this->cast($row);
        }

        return $rows;
    }

    /**
     * Check if a customer has an open (not-yet-completed) export request.
     *
     * @param int $customer_id Customer ID.
     * @return bool True if an open export exists.
     */
    public function has_open_export(int $customer_id): bool
    {
        $count = $this->db
            ->where('id_users', $customer_id)
            ->where('request_type', 'export')
            ->where_in('status', ['pending', 'processing', 'ready'])
            ->get('data_requests')
            ->num_rows();

        return $count > 0;
    }

    /**
     * Check if a customer has a pending erasure request.
     *
     * @param int $customer_id Customer ID.
     * @return bool True if a pending erasure exists.
     */
    public function has_open_erasure(int $customer_id): bool
    {
        $count = $this->db
            ->where('id_users', $customer_id)
            ->where('request_type', 'erasure')
            ->where('status', 'pending')
            ->get('data_requests')
            ->num_rows();

        return $count > 0;
    }
}
