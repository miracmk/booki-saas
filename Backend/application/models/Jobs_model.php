<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Jobs Model (Faz 32+33, 2026-08-28).
 *
 * Thin CRUD layer and database-specific queries for the jobs table.
 * Used by Queue library and the observability/monitoring layer.
 * ---------------------------------------------------------------------------- */

class Jobs_model extends App_Model
{
    /**
     * Protected casts for automatic type conversion on retrieval.
     */
    protected array $casts = [
        'id' => 'integer',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'reference_id' => 'integer',
    ];

    /**
     * Insert a new job into the queue.
     *
     * @param array $job Job record: ['queue', 'channel', 'handler', 'payload', 'status',
     *                  'attempts', 'max_attempts', 'available_at', 'created_at', ...]
     * @return int The ID of the newly inserted job.
     */
    public function insert(array $job): int
    {
        $this->db->insert('jobs', $job);

        return $this->db->insert_id();
    }

    /**
     * Retrieve a single job by ID.
     *
     * @param int $id The job ID.
     * @return array|null The job record, or null if not found.
     */
    public function find(int $id): ?array
    {
        return $this->db->get_where('jobs', ['id' => $id])->row_array() ?: null;
    }

    /**
     * Atomically reserve up to $limit pending jobs for a worker.
     *
     * Updates pending jobs (where available_at <= now()) to reserved status with the
     * current timestamp and worker ID. Returns the full records of the reserved jobs.
     *
     * The UPDATE statement is atomic and safe under concurrent workers - MySQL's
     * UPDATE...LIMIT is an atomic operation that doesn't require SELECT...FOR UPDATE.
     * The follow-up SELECT fetches the rows just marked, ensuring we return the exact
     * records the UPDATE reserved (not any stale/duplicate data).
     *
     * @param int $limit Maximum jobs to reserve.
     * @param string $worker_id Unique worker identifier (e.g., hostname:pid).
     * @param string $queue Queue name to process.
     * @return array Array of reserved job records.
     */
    public function reserve_batch(int $limit, string $worker_id, string $queue): array
    {
        $now = gmdate('Y-m-d H:i:s');

        // A per-call unique reservation token, NOT just $worker_id: reserved_at has only
        // second-level precision, so two calls from the same worker within the same second
        // would otherwise produce identical (reserved_by, reserved_at) values - the
        // follow-up SELECT below would then match rows from a PRIOR call's UPDATE too,
        // returning stale/already-claimed jobs instead of only the rows THIS call reserved.
        // Verified by a real Docker test: a second reserve_batch() call by the same
        // worker_id within the same second returned the previous call's rows before this fix.
        $reservation_token = $worker_id . ':' . bin2hex(random_bytes(8));
        $reserved_at = $now;

        // Atomic UPDATE: mark pending jobs as reserved in a single statement.
        // ORDER BY available_at ASC ensures FIFO processing (oldest first).
        $this->db->query(
            'UPDATE ' . $this->db->dbprefix('jobs') . ' ' .
            'SET status = ?, reserved_at = ?, reserved_by = ? ' .
            'WHERE status = ? AND available_at <= ? AND queue = ? ' .
            'ORDER BY available_at ASC ' .
            'LIMIT ?',
            ['reserved', $reserved_at, $reservation_token, 'pending', $now, $queue, $limit],
        );

        // Fetch the jobs just reserved: those matching THIS call's unique reservation token.
        // Unlike a plain worker_id, this token cannot collide with any other call, past or
        // concurrent, so the SELECT can only return rows this exact UPDATE just changed.
        $result = $this->db->get_where(
            'jobs',
            [
                'status' => 'reserved',
                'reserved_by' => $reservation_token,
                'queue' => $queue,
            ],
            $limit,
        )->result_array();

        return $result ?: [];
    }

    /**
     * Update a job's status and optional extra columns.
     *
     * @param int $id The job ID.
     * @param string $status New status: 'pending', 'reserved', 'succeeded', 'failed'.
     * @param array $extra Additional columns to update (e.g., ['attempts' => 5, 'last_error' => '...']).
     * @return void
     */
    public function mark_status(int $id, string $status, array $extra = []): void
    {
        $update = array_merge(['status' => $status], $extra);
        $this->db->update('jobs', $update, ['id' => $id]);
    }

    /**
     * Release stale reservations (jobs reserved longer than $stale_minutes ago).
     *
     * Atomically returns reserved jobs whose reserved_at is older than the threshold
     * back to pending status, clearing the reservation.
     *
     * @param int $stale_minutes Minutes a reservation must be stale to release.
     * @return int Number of jobs released.
     */
    public function release_stale(int $stale_minutes): int
    {
        $cutoff = new DateTime('now', new DateTimeZone('UTC'));
        $cutoff->modify('-' . $stale_minutes . ' minutes');

        $this->db->update(
            'jobs',
            [
                'status' => 'pending',
                'reserved_by' => null,
                'reserved_at' => null,
            ],
            [
                'status' => 'reserved',
                'reserved_at <' => $cutoff->format('Y-m-d H:i:s'),
            ],
        );

        return $this->db->affected_rows();
    }

    /**
     * Get a summary count of jobs by status.
     *
     * Returns the number of pending, reserved, succeeded, and failed jobs in the queue.
     *
     * @return array Associative array: ['pending' => N, 'reserved' => N, 'succeeded' => N, 'failed' => N]
     */
    public function count_by_status(): array
    {
        $result = $this->db->query(
            'SELECT status, COUNT(*) as count FROM ' . $this->db->dbprefix('jobs') . ' GROUP BY status',
        )->result_array();

        $counts = ['pending' => 0, 'reserved' => 0, 'succeeded' => 0, 'failed' => 0];

        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Retrieve recent failed jobs for the jobs monitoring page.
     *
     * Returns the most recent failed jobs (those with status='failed').
     * Useful for administrative monitoring and failure analysis.
     *
     * @param int $since_minutes Only return jobs failed in the last N minutes (default: 60).
     * @param int $limit Maximum number of jobs to return (default: 50).
     * @return array Array of failed job records.
     */
    public function recent_failures(int $since_minutes = 60, int $limit = 50): array
    {
        $cutoff = new DateTime('now', new DateTimeZone('UTC'));
        $cutoff->modify('-' . $since_minutes . ' minutes');

        return $this->db
            ->where('status', 'failed')
            ->where('completed_at >=', $cutoff->format('Y-m-d H:i:s'))
            ->order_by('completed_at', 'desc')
            ->limit($limit)
            ->get('jobs')
            ->result_array() ?: [];
    }

    /**
     * Get the age (in seconds) of the oldest pending job.
     *
     * Returns the number of seconds between the oldest pending job's available_at
     * and now(). Useful for monitoring queue staleness - if this value is large,
     * the queue is backlogged.
     *
     * @return int|null Age in seconds, or null if no pending jobs exist.
     */
    public function oldest_pending_age_seconds(): ?int
    {
        $result = $this->db->query(
            'SELECT TIMESTAMPDIFF(SECOND, MIN(available_at), NOW()) as age ' .
            'FROM ' . $this->db->dbprefix('jobs') . ' ' .
            'WHERE status = ?',
            ['pending'],
        )->row_array();

        if (!$result || $result['age'] === null) {
            return null;
        }

        return (int) $result['age'];
    }

    /**
     * Delete completed jobs older than the specified retention period.
     *
     * Removes succeeded and failed job records that were completed before
     * the retention cutoff date. Used by the automated cleanup task to maintain
     * database size and avoid accumulating old job records indefinitely.
     *
     * @param int $retention_days Number of days to retain completed jobs (default: 30).
     * @return int Number of rows deleted.
     */
    public function delete_old(int $retention_days = 30): int
    {
        $cutoff = new DateTime('now', new DateTimeZone('UTC'));
        $cutoff->modify('-' . $retention_days . ' days');

        $this->db->where_in('status', ['succeeded', 'failed']);
        $this->db->where('completed_at <', $cutoff->format('Y-m-d H:i:s'));
        $this->db->delete('jobs');

        return $this->db->affected_rows();
    }

    /**
     * Retry a failed job by resetting its state to pending.
     *
     * Resets the job's status to 'pending', clears the attempt counter,
     * and sets available_at to now so it can be immediately picked up
     * by the next worker.
     *
     * @param int $id The job ID.
     * @return void
     */
    public function retry(int $id): void
    {
        $this->db->update(
            'jobs',
            [
                'status' => 'pending',
                'attempts' => 0,
                'available_at' => date('Y-m-d H:i:s'),
            ],
            ['id' => $id],
        );
    }
}
