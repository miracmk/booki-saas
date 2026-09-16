<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Branches model
 *
 * Handles CRUD operations for branches (physical locations within a single tenant).
 * Each tenant has at least one default branch; branches are optional for
 * single-location deployments and fully utilized for multi-location SaaS.
 * ---------------------------------------------------------------------------- */

class Branches_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Save (insert or update) a branch.
     *
     * @param array $branch Associative array with the branch data.
     *
     * @return int Returns the branch ID.
     *
     * @throws InvalidArgumentException
     */
    public function save(array $branch): int
    {
        $this->validate($branch);

        if (empty($branch['id'])) {
            $branch_id = $this->insert($branch);
        } else {
            $branch_id = $this->update($branch);
        }

        return $branch_id;
    }

    /**
     * Validate the branch data.
     *
     * @param array $branch Associative array with the branch data.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $branch): void
    {
        if (!empty($branch['id'])) {
            $count = $this->db->get_where('branches', ['id' => $branch['id']])->num_rows();

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided branch ID does not exist in the database: ' . $branch['id'],
                );
            }
        }

        if (empty($branch['name'])) {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($branch, true));
        }
    }

    /**
     * Insert a new branch into the database.
     *
     * @param array $branch Associative array with the branch data.
     *
     * @return int Returns the branch ID.
     *
     * @throws RuntimeException
     */
    protected function insert(array $branch): int
    {
        $branch['created_at'] = date('Y-m-d H:i:s');

        if (!$this->db->insert('branches', $branch)) {
            throw new RuntimeException('Could not insert branch.');
        }

        return $this->db->insert_id();
    }

    /**
     * Update an existing branch.
     *
     * @param array $branch Associative array with the branch data.
     *
     * @return int Returns the branch ID.
     *
     * @throws RuntimeException
     */
    protected function update(array $branch): int
    {
        if (!$this->db->update('branches', $branch, ['id' => $branch['id']])) {
            throw new RuntimeException('Could not update branch.');
        }

        return $branch['id'];
    }

    /**
     * Remove an existing branch from the database.
     * Note: This is soft-delete capable; consider updating is_active instead of hard delete
     * to preserve historical data and relational integrity.
     *
     * @param int $branch_id Branch ID.
     */
    public function delete(int $branch_id): void
    {
        $this->db->delete('branches', ['id' => $branch_id]);
    }

    /**
     * Get a specific branch from the database.
     *
     * @param int $branch_id The ID of the record to be returned.
     *
     * @return array Returns an array with the branch data.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $branch_id): array
    {
        $branch = $this->db->get_where('branches', ['id' => $branch_id])->row_array();

        if (!$branch) {
            throw new InvalidArgumentException('The provided branch ID was not found in the database: ' . $branch_id);
        }

        $this->cast($branch);

        return $branch;
    }

    /**
     * Get all branches that match the provided criteria.
     *
     * @param array|string|null $where Where conditions
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of branches.
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

        $branches = $this->db->get('branches', $limit, $offset)->result_array();

        foreach ($branches as &$branch) {
            $this->cast($branch);
        }

        return $branches;
    }

    /**
     * Get the default branch (is_default = 1).
     * Single-tenant deployments typically have exactly one default branch.
     *
     * @return array Branch record with is_default=1, or empty array if none exists.
     */
    public function get_default(): array
    {
        $branch = $this->db->get_where('branches', ['is_default' => 1])->row_array();

        if (!$branch) {
            return [];
        }

        $this->cast($branch);

        return $branch;
    }

    /**
     * Count the number of active branches (is_active = 1).
     * Used to determine whether to show branch-selection UI:
     * - count_active() <= 1: disable branch filtering (single-branch behavior)
     * - count_active() > 1: enable branch filtering (multi-branch behavior)
     *
     * @return int Number of active branches.
     */
    public function count_active(): int
    {
        return (int) $this->db->where('is_active', 1)->count_all_results('branches');
    }

    /**
     * Search branches by the provided keyword.
     *
     * @param string $keyword Search keyword.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of branches.
     */
    public function search(string $keyword, ?int $limit = null, ?int $offset = null, ?string $order_by = null): array
    {
        $branches = $this->db
            ->select()
            ->from('branches')
            ->group_start()
            ->like('name', $keyword)
            ->or_like('address', $keyword)
            ->or_like('phone', $keyword)
            ->group_end()
            ->limit($limit)
            ->offset($offset)
            ->order_by($this->quote_order_by($order_by ?? 'name ASC'))
            ->get()
            ->result_array();

        foreach ($branches as &$branch) {
            $this->cast($branch);
        }

        return $branches;
    }

    /**
     * Get branches as options for dropdowns.
     *
     * @return array Returns an array of options with 'value' and 'label' keys.
     */
    public function get_options(): array
    {
        $branches = $this->get(['is_active' => 1], null, null, 'name ASC');

        $options = [];

        foreach ($branches as $branch) {
            $options[] = [
                'value' => $branch['id'],
                'label' => $branch['name'],
            ];
        }

        return $options;
    }
}
