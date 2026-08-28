<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Recurrence Groups Model
 *
 * Thin CRUD for recurrence_groups (series metadata: frequency, occurrence
 * counts, status). The actual per-occurrence booking logic lives in the
 * Recurrence_service library, which is the only writer of appointments rows
 * for a series - this model never touches the appointments table.
 * ---------------------------------------------------------------------------- */

class Recurrence_groups_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer',
        'id_created_by' => 'integer',
        'id_services' => 'integer',
        'id_users_provider' => 'integer',
        'id_users_customer' => 'integer',
        'interval_count' => 'integer',
        'occurrences_total' => 'integer',
        'occurrences_created' => 'integer',
    ];

    /**
     * Save (insert or update) a recurrence group.
     *
     * @param array $group Associative array with the group data.
     * @return int Returns the group ID.
     * @throws InvalidArgumentException
     */
    public function save(array $group): int
    {
        $this->validate($group);

        if (empty($group['id'])) {
            $group_id = $this->insert($group);
        } else {
            $group_id = $this->update($group);
        }

        return $group_id;
    }

    /**
     * Validate the recurrence group data.
     *
     * @param array $group Associative array with the group data.
     * @throws InvalidArgumentException
     */
    public function validate(array $group): void
    {
        if (!empty($group['id'])) {
            $count = $this->db->get_where('recurrence_groups', ['id' => $group['id']])->num_rows();

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided recurrence group ID does not exist in the database: ' . $group['id'],
                );
            }

            return;
        }

        if (
            empty($group['id_services']) ||
            empty($group['id_users_provider']) ||
            empty($group['id_users_customer']) ||
            empty($group['occurrences_total']) ||
            empty($group['start_date'])
        ) {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($group, true));
        }

        if ((int) $group['occurrences_total'] <= 0) {
            throw new InvalidArgumentException('Occurrences total must be greater than 0.');
        }
    }

    /**
     * Insert a new recurrence group into the database.
     *
     * @param array $group Associative array with the group data.
     * @return int Returns the group ID.
     * @throws RuntimeException
     */
    protected function insert(array $group): int
    {
        $group['created_at'] = date('Y-m-d H:i:s');

        if (!$this->db->insert('recurrence_groups', $group)) {
            throw new RuntimeException('Could not insert recurrence group.');
        }

        return $this->db->insert_id();
    }

    /**
     * Update an existing recurrence group.
     *
     * @param array $group Associative array with the group data.
     * @return int Returns the group ID.
     * @throws RuntimeException
     */
    protected function update(array $group): int
    {
        $group['updated_at'] = date('Y-m-d H:i:s');

        if (!$this->db->update('recurrence_groups', $group, ['id' => $group['id']])) {
            throw new RuntimeException('Could not update recurrence group.');
        }

        return $group['id'];
    }

    /**
     * Get a specific recurrence group from the database.
     *
     * @param int $group_id The ID of the record to be returned.
     * @return array Returns an array with the group data.
     * @throws InvalidArgumentException
     */
    public function find(int $group_id): array
    {
        $group = $this->db->get_where('recurrence_groups', ['id' => $group_id])->row_array();

        if (!$group) {
            throw new InvalidArgumentException('The provided recurrence group ID was not found in the database: ' . $group_id);
        }

        $this->cast($group);

        return $group;
    }

    /**
     * Increment the occurrences_created counter by one.
     *
     * @param int $group_id Recurrence group ID.
     */
    public function increment_occurrences_created(int $group_id): void
    {
        $this->db->set('occurrences_created', 'occurrences_created + 1', false);
        $this->db->set('updated_at', date('Y-m-d H:i:s'));
        $this->db->where('id', $group_id);
        $this->db->update('recurrence_groups');
    }

    /**
     * Mark a recurrence group's status (e.g. after the series is fully created).
     *
     * @param int $group_id Recurrence group ID.
     * @param string $status New status: active, completed, cancelled.
     */
    public function mark_status(int $group_id, string $status): void
    {
        $this->db->update(
            'recurrence_groups',
            ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $group_id],
        );
    }

    /**
     * Get all appointments that belong to a recurrence group.
     *
     * @param int $group_id Recurrence group ID.
     * @return array Returns an array of appointment rows, ordered by sequence.
     */
    public function get_appointments(int $group_id): array
    {
        return $this->db
            ->where('id_recurrence_group', $group_id)
            ->order_by('recurrence_sequence', 'ASC')
            ->get('appointments')
            ->result_array();
    }
}
