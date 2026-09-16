<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Membership Plans Model
 *
 * CRUD for membership_plans (the subscription definitions staff sell -
 * price, billing period, sessions granted per period). See
 * Customer_memberships_model for the customer-facing subscription instances.
 * ---------------------------------------------------------------------------- */

class Memberships_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer',
        'id_services' => 'integer',
        'price' => 'float',
        'sessions_per_period' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Save (insert or update) a membership plan.
     *
     * @param array $plan Associative array with the plan data.
     * @return int Returns the plan ID.
     * @throws InvalidArgumentException
     */
    public function save(array $plan): int
    {
        $this->validate($plan);

        if (empty($plan['id'])) {
            $plan_id = $this->insert($plan);
        } else {
            $plan_id = $this->update($plan);
        }

        return $plan_id;
    }

    /**
     * Validate the plan data.
     *
     * @param array $plan Associative array with the plan data.
     * @throws InvalidArgumentException
     */
    public function validate(array $plan): void
    {
        if (!empty($plan['id'])) {
            $count = $this->db->get_where('membership_plans', ['id' => $plan['id']])->num_rows();

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided membership plan ID does not exist in the database: ' . $plan['id'],
                );
            }

            return;
        }

        if (empty($plan['name']) || empty($plan['id_services']) || !isset($plan['price'])) {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($plan, true));
        }

        if ((float) $plan['price'] < 0) {
            throw new InvalidArgumentException('Price cannot be negative.');
        }
    }

    /**
     * Insert a new plan into the database.
     *
     * @param array $plan Associative array with the plan data.
     * @return int Returns the plan ID.
     * @throws RuntimeException
     */
    protected function insert(array $plan): int
    {
        $plan['created_at'] = date('Y-m-d H:i:s');

        if (!$this->db->insert('membership_plans', $plan)) {
            throw new RuntimeException('Could not insert membership plan.');
        }

        return $this->db->insert_id();
    }

    /**
     * Update an existing plan.
     *
     * @param array $plan Associative array with the plan data.
     * @return int Returns the plan ID.
     * @throws RuntimeException
     */
    protected function update(array $plan): int
    {
        $plan['updated_at'] = date('Y-m-d H:i:s');

        if (!$this->db->update('membership_plans', $plan, ['id' => $plan['id']])) {
            throw new RuntimeException('Could not update membership plan.');
        }

        return $plan['id'];
    }

    /**
     * Remove a plan from the database.
     *
     * @param int $plan_id Plan ID.
     */
    public function delete(int $plan_id): void
    {
        $this->db->delete('membership_plans', ['id' => $plan_id]);
    }

    /**
     * Get a specific plan from the database.
     *
     * @param int $plan_id The ID of the record to be returned.
     * @return array Returns an array with the plan data.
     * @throws InvalidArgumentException
     */
    public function find(int $plan_id): array
    {
        $plan = $this->db->get_where('membership_plans', ['id' => $plan_id])->row_array();

        if (!$plan) {
            throw new InvalidArgumentException('The provided membership plan ID was not found in the database: ' . $plan_id);
        }

        $this->cast($plan);

        return $plan;
    }

    /**
     * Get all active plans.
     *
     * @return array Returns an array of plans.
     */
    public function get_active(): array
    {
        $plans = $this->db->get_where('membership_plans', ['is_active' => 1])->result_array();

        foreach ($plans as &$plan) {
            $this->cast($plan);
        }

        return $plans;
    }

    /**
     * Get all plans (admin management page).
     *
     * @return array Returns an array of plans.
     */
    public function get(): array
    {
        $plans = $this->db->order_by('name', 'ASC')->get('membership_plans')->result_array();

        foreach ($plans as &$plan) {
            $this->cast($plan);
        }

        return $plans;
    }
}
