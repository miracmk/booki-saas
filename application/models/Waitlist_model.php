<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Waitlist Model
 *
 * CRUD + matching queries for waitlist_entries. The actual "who to notify
 * when a slot opens" orchestration lives in Waitlist_service - this model
 * only knows how to read/write rows.
 * ---------------------------------------------------------------------------- */

class Waitlist_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer',
        'id_users_customer' => 'integer',
        'id_services' => 'integer',
        'id_users_provider' => 'integer',
        'converted_appointment_id' => 'integer',
    ];

    /**
     * Save (insert or update) a waitlist entry.
     *
     * @param array $entry Associative array with the entry data.
     * @return int Returns the entry ID.
     * @throws InvalidArgumentException
     */
    public function save(array $entry): int
    {
        $this->validate($entry);

        if (empty($entry['id'])) {
            $entry_id = $this->insert($entry);
        } else {
            $entry_id = $this->update($entry);
        }

        return $entry_id;
    }

    /**
     * Validate the waitlist entry data.
     *
     * @param array $entry Associative array with the entry data.
     * @throws InvalidArgumentException
     */
    public function validate(array $entry): void
    {
        if (!empty($entry['id'])) {
            $count = $this->db->get_where('waitlist_entries', ['id' => $entry['id']])->num_rows();

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided waitlist entry ID does not exist in the database: ' . $entry['id'],
                );
            }

            return;
        }

        if (empty($entry['id_users_customer']) || empty($entry['id_services'])) {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($entry, true));
        }
    }

    /**
     * Insert a new waitlist entry into the database.
     *
     * @param array $entry Associative array with the entry data.
     * @return int Returns the entry ID.
     * @throws RuntimeException
     */
    protected function insert(array $entry): int
    {
        $entry['created_at'] = date('Y-m-d H:i:s');

        if (!$this->db->insert('waitlist_entries', $entry)) {
            throw new RuntimeException('Could not insert waitlist entry.');
        }

        return $this->db->insert_id();
    }

    /**
     * Update an existing waitlist entry.
     *
     * @param array $entry Associative array with the entry data.
     * @return int Returns the entry ID.
     * @throws RuntimeException
     */
    protected function update(array $entry): int
    {
        $entry['updated_at'] = date('Y-m-d H:i:s');

        if (!$this->db->update('waitlist_entries', $entry, ['id' => $entry['id']])) {
            throw new RuntimeException('Could not update waitlist entry.');
        }

        return $entry['id'];
    }

    /**
     * Remove a waitlist entry from the database.
     *
     * @param int $entry_id Entry ID.
     */
    public function delete(int $entry_id): void
    {
        $this->db->delete('waitlist_entries', ['id' => $entry_id]);
    }

    /**
     * Get a specific waitlist entry from the database.
     *
     * @param int $entry_id The ID of the record to be returned.
     * @return array Returns an array with the entry data.
     * @throws InvalidArgumentException
     */
    public function find(int $entry_id): array
    {
        $entry = $this->db->get_where('waitlist_entries', ['id' => $entry_id])->row_array();

        if (!$entry) {
            throw new InvalidArgumentException('The provided waitlist entry ID was not found in the database: ' . $entry_id);
        }

        $this->cast($entry);

        return $entry;
    }

    /**
     * Get all waitlist entries for a customer.
     *
     * @param int $customer_id Customer ID.
     * @return array Returns an array of entries.
     */
    public function get_for_customer(int $customer_id): array
    {
        $entries = $this->db
            ->where('id_users_customer', $customer_id)
            ->order_by('created_at', 'DESC')
            ->get('waitlist_entries')
            ->result_array();

        foreach ($entries as &$entry) {
            $this->cast($entry);
        }

        return $entries;
    }

    /**
     * Get all waiting entries (for the admin waitlist page).
     *
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @return array Returns an array of entries with customer/service names joined in.
     */
    public function get_waiting(?int $limit = null, ?int $offset = null): array
    {
        $entries = $this->db
            ->select('we.*, s.name as service_name, u.first_name as customer_first_name, u.last_name as customer_last_name')
            ->from('waitlist_entries we')
            ->join('services s', 's.id = we.id_services', 'left')
            ->join('users u', 'u.id = we.id_users_customer', 'left')
            ->where('we.status', 'waiting')
            ->order_by('we.created_at', 'ASC')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->result_array();

        foreach ($entries as &$entry) {
            $this->cast($entry);
        }

        return $entries;
    }

    /**
     * Find waitlist entries matching a freed slot: same service, and either no provider
     * preference or the same provider, currently 'waiting'. Date/time window filtering (if the
     * customer specified one) is applied so a slot that doesn't fall within their requested
     * window doesn't trigger a notification for it.
     *
     * @param int $service_id Service ID.
     * @param int $provider_id Provider ID of the freed slot.
     * @param string $slot_datetime The freed slot's start datetime ('Y-m-d H:i:s').
     * @return array Returns matching entries, oldest request first (first-come-first-served).
     */
    public function get_matching_entries(int $service_id, int $provider_id, string $slot_datetime): array
    {
        $slot_date = substr($slot_datetime, 0, 10);
        $slot_time = substr($slot_datetime, 11, 5);

        $this->db
            ->where('id_services', $service_id)
            ->where('status', 'waiting')
            ->group_start()
            ->where('id_users_provider IS NULL')
            ->or_where('id_users_provider', $provider_id)
            ->group_end()
            ->group_start()
            ->where('requested_date IS NULL')
            ->or_where('requested_date', $slot_date)
            ->group_end()
            ->group_start()
            ->where('requested_time_window_start IS NULL')
            ->or_where('requested_time_window_start <=', $slot_time)
            ->group_end()
            ->group_start()
            ->where('requested_time_window_end IS NULL')
            ->or_where('requested_time_window_end >=', $slot_time)
            ->group_end()
            ->order_by('created_at', 'ASC');

        $entries = $this->db->get('waitlist_entries')->result_array();

        foreach ($entries as &$entry) {
            $this->cast($entry);
        }

        return $entries;
    }

    /**
     * Mark an entry as notified, with a claim window deadline.
     *
     * @param int $entry_id Entry ID.
     * @param string $channel Channel used: sms, whatsapp, both.
     * @param int $claim_window_minutes How many minutes the customer has to book before the
     *   entry lazily reverts to 'waiting' (see expire_stale()).
     */
    public function mark_notified(int $entry_id, string $channel, int $claim_window_minutes = 30): void
    {
        $this->db->update(
            'waitlist_entries',
            [
                'status' => 'notified',
                'notify_channel' => $channel,
                'notified_at' => date('Y-m-d H:i:s'),
                'notify_expires_at' => date('Y-m-d H:i:s', strtotime('+' . $claim_window_minutes . ' minutes')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            ['id' => $entry_id],
        );
    }

    /**
     * Mark an entry as converted (the customer booked the freed slot).
     *
     * @param int $entry_id Entry ID.
     * @param int $appointment_id The appointment that was booked.
     */
    public function mark_converted(int $entry_id, int $appointment_id): void
    {
        $this->db->update(
            'waitlist_entries',
            [
                'status' => 'converted',
                'converted_appointment_id' => $appointment_id,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            ['id' => $entry_id],
        );
    }

    /**
     * Lazily revert 'notified' entries whose claim window has lapsed back to 'waiting', so the
     * next matching entry can be notified instead. Called opportunistically (waitlist page load,
     * new join attempt) rather than via a cron job - this codebase has no background job/queue
     * infrastructure yet (see docs/ROADMAP.md gap #18, Dalga 2).
     *
     * @return int Number of entries reverted.
     */
    public function expire_stale(): int
    {
        $this->db->where('status', 'notified');
        $this->db->where('notify_expires_at <', date('Y-m-d H:i:s'));
        $this->db->update('waitlist_entries', ['status' => 'waiting', 'updated_at' => date('Y-m-d H:i:s')]);

        return $this->db->affected_rows();
    }
}
