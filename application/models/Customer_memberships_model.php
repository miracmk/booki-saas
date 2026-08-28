<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Customer Memberships Model
 *
 * Handles the customer-facing subscription instances (customer_memberships)
 * and their session consumption (customer_membership_sessions) - structural
 * mirror of Packages_model's consume_session()/restore_session() pattern.
 *
 * RENEWAL DESIGN NOTE (Dalga 1, 2026-08-28): Payment_gateway_interface only
 * supports the checkout-flow shape (create_payment_intent() + a user-present
 * charge() against that intent) - there is no stored-card / off-session
 * charge capability in this codebase, so a truly unattended automatic
 * recurring charge cannot be implemented honestly yet. Renewal therefore
 * follows the exact same precedent Packages already established: staff
 * record the renewal directly (renew(), analogous to how a package purchase
 * is just an admin-created row via Packages_model::save(), no payment
 * gateway integration required). renew_if_due() only performs the LAZY
 * status transition (active -> past_due/expired when the period lapses
 * un-renewed) - it never silently advances the period or fabricates a
 * successful charge. Wiring renew() to a real payment capture is a natural
 * follow-up once/if a stored-payment-method capability exists.
 * ---------------------------------------------------------------------------- */

class Customer_memberships_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer',
        'id_users_customer' => 'integer',
        'id_membership_plans' => 'integer',
        'sessions_used_this_period' => 'integer',
        'auto_renew' => 'boolean',
        'sold_by' => 'integer',
    ];

    /**
     * Save (insert or update) a customer membership.
     *
     * @param array $membership Associative array with the membership data.
     * @return int Returns the membership ID.
     * @throws InvalidArgumentException
     */
    public function save(array $membership): int
    {
        $this->validate($membership);

        if (empty($membership['id'])) {
            $membership_id = $this->insert($membership);
        } else {
            $membership_id = $this->update($membership);
        }

        return $membership_id;
    }

    /**
     * Validate the membership data.
     *
     * @param array $membership Associative array with the membership data.
     * @throws InvalidArgumentException
     */
    public function validate(array $membership): void
    {
        if (!empty($membership['id'])) {
            $count = $this->db->get_where('customer_memberships', ['id' => $membership['id']])->num_rows();

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided membership ID does not exist in the database: ' . $membership['id'],
                );
            }

            return;
        }

        if (empty($membership['id_users_customer']) || empty($membership['id_membership_plans'])) {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($membership, true));
        }
    }

    /**
     * Insert a new membership into the database.
     *
     * @param array $membership Associative array with the membership data.
     * @return int Returns the membership ID.
     * @throws RuntimeException
     */
    protected function insert(array $membership): int
    {
        $membership['created_at'] = date('Y-m-d H:i:s');

        if (!$this->db->insert('customer_memberships', $membership)) {
            throw new RuntimeException('Could not insert customer membership.');
        }

        return $this->db->insert_id();
    }

    /**
     * Update an existing membership.
     *
     * @param array $membership Associative array with the membership data.
     * @return int Returns the membership ID.
     * @throws RuntimeException
     */
    protected function update(array $membership): int
    {
        $membership['updated_at'] = date('Y-m-d H:i:s');

        if (!$this->db->update('customer_memberships', $membership, ['id' => $membership['id']])) {
            throw new RuntimeException('Could not update customer membership.');
        }

        return $membership['id'];
    }

    /**
     * Get a specific membership from the database.
     *
     * @param int $membership_id The ID of the record to be returned.
     * @return array Returns an array with the membership data.
     * @throws InvalidArgumentException
     */
    public function find(int $membership_id): array
    {
        $membership = $this->db->get_where('customer_memberships', ['id' => $membership_id])->row_array();

        if (!$membership) {
            throw new InvalidArgumentException('The provided membership ID was not found in the database: ' . $membership_id);
        }

        $this->cast($membership);

        return $membership;
    }

    /**
     * Get all memberships for a customer.
     *
     * @param int $customer_id Customer ID.
     * @return array Returns an array of memberships.
     */
    public function get_for_customer(int $customer_id): array
    {
        $memberships = $this->db
            ->where('id_users_customer', $customer_id)
            ->order_by('created_at', 'DESC')
            ->get('customer_memberships')
            ->result_array();

        foreach ($memberships as &$membership) {
            $this->cast($membership);
        }

        return $memberships;
    }

    /**
     * Get all memberships (admin management page), joined with plan/customer names.
     *
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @return array Returns an array of memberships.
     */
    public function get_all(?int $limit = null, ?int $offset = null): array
    {
        $memberships = $this->db
            ->select('cm.*, mp.name as plan_name, mp.id_services, u.first_name as customer_first_name, u.last_name as customer_last_name')
            ->from('customer_memberships cm')
            ->join('membership_plans mp', 'mp.id = cm.id_membership_plans', 'left')
            ->join('users u', 'u.id = cm.id_users_customer', 'left')
            ->order_by('cm.created_at', 'DESC')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->result_array();

        foreach ($memberships as &$membership) {
            $this->cast($membership);
        }

        return $memberships;
    }

    /**
     * Get the active membership for a customer/service (mirrors
     * Packages_model::get_active_for_customer_service()). Lazily transitions
     * the membership's status first (see renew_if_due()) so a lapsed,
     * un-renewed membership never grants a session it hasn't been paid for.
     *
     * @param int $customer_id Customer ID.
     * @param int $service_id Service ID.
     * @return array|null Returns membership data or null if none active.
     */
    public function get_active_for_customer_service(int $customer_id, int $service_id): ?array
    {
        $membership = $this->db
            ->select('cm.*')
            ->from('customer_memberships cm')
            ->join('membership_plans mp', 'mp.id = cm.id_membership_plans')
            ->where('cm.id_users_customer', $customer_id)
            ->where('mp.id_services', $service_id)
            ->where('cm.status', 'active')
            ->order_by('cm.current_period_end', 'ASC')
            ->limit(1)
            ->get()
            ->row_array();

        if (!$membership) {
            return null;
        }

        $this->cast($membership);

        $membership = $this->renew_if_due($membership);

        if ($membership['status'] !== 'active') {
            return null;
        }

        $plan = $this->db->get_where('membership_plans', ['id' => $membership['id_membership_plans']])->row_array();

        if (
            $plan['sessions_per_period'] !== null &&
            (int) $membership['sessions_used_this_period'] >= (int) $plan['sessions_per_period']
        ) {
            return null;
        }

        return $membership;
    }

    /**
     * Lazily transition a membership whose current period has lapsed. Called on every read
     * (get_active_for_customer_service(), the admin membership list) rather than via a cron job -
     * see the class-level docblock for why this cannot yet trigger a real automatic charge.
     *
     * @param array $membership Membership row (already cast).
     * @return array The membership row, with 'status' updated if a transition occurred.
     */
    public function renew_if_due(array $membership): array
    {
        if ($membership['status'] !== 'active') {
            return $membership;
        }

        if (strtotime($membership['current_period_end']) >= time()) {
            return $membership;
        }

        $new_status = filter_var($membership['auto_renew'], FILTER_VALIDATE_BOOLEAN) ? 'past_due' : 'expired';

        $this->db->update(
            'customer_memberships',
            ['status' => $new_status, 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $membership['id']],
        );

        $membership['status'] = $new_status;

        return $membership;
    }

    /**
     * Explicitly renew a membership (staff-recorded, e.g. the customer paid in person or via an
     * existing offline method) - advances the period by the plan's billing_period, resets session
     * usage, reactivates a past_due/expired membership. Analogous to how a Packages purchase is
     * simply an admin-created row (see Packages_model, no payment gateway integration there
     * either).
     *
     * @param int $membership_id Membership ID.
     * @param int|null $renewed_by User ID of the staff member recording the renewal.
     * @return int Returns the membership ID.
     * @throws RuntimeException
     */
    public function renew(int $membership_id, ?int $renewed_by = null): int
    {
        $this->db->trans_start();

        try {
            $membership = $this->find($membership_id);
            $plan = $this->db->get_where('membership_plans', ['id' => $membership['id_membership_plans']])->row_array();

            if (!$plan) {
                throw new RuntimeException('Membership plan not found for membership ' . $membership_id);
            }

            // A lapsed membership renews from now(); a still-active one (early renewal) extends
            // from its current period end, so the customer never loses paid-for time.
            $period_start = strtotime($membership['current_period_end']) >= time()
                ? $membership['current_period_end']
                : date('Y-m-d H:i:s');

            $period_end = $this->add_billing_period($period_start, $plan['billing_period']);

            $this->db->update(
                'customer_memberships',
                [
                    'status' => 'active',
                    'current_period_start' => $period_start,
                    'current_period_end' => $period_end,
                    'sessions_used_this_period' => 0,
                    'last_renewed_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                ['id' => $membership_id],
            );

            $this->db->trans_complete();
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw new RuntimeException('Could not renew membership: ' . $e->getMessage());
        }

        return $membership_id;
    }

    /**
     * Consume one session from a membership for an appointment (structural mirror of
     * Packages_model::consume_session()).
     *
     * @param int $membership_id Membership ID.
     * @param int $appointment_id Appointment ID.
     * @throws RuntimeException
     */
    public function consume_session(int $membership_id, int $appointment_id): void
    {
        $this->db->trans_start();

        try {
            $existing = $this->db
                ->get_where('customer_membership_sessions', ['id_appointments' => $appointment_id])
                ->num_rows();

            if ($existing > 0) {
                $this->db->trans_complete();
                return;
            }

            $this->db->insert('customer_membership_sessions', [
                'id_customer_memberships' => $membership_id,
                'id_appointments' => $appointment_id,
                'consumed_at' => date('Y-m-d H:i:s'),
            ]);

            $this->db->set('sessions_used_this_period', 'sessions_used_this_period + 1', false);
            $this->db->where('id', $membership_id);
            $this->db->update('customer_memberships');

            $this->db->trans_complete();
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw new RuntimeException('Could not consume membership session: ' . $e->getMessage());
        }
    }

    /**
     * Restore a session that was consumed by an appointment (undo checkout) - structural mirror of
     * Packages_model::restore_session().
     *
     * @param int $appointment_id Appointment ID.
     */
    public function restore_session(int $appointment_id): void
    {
        $this->db->trans_start();

        try {
            $session = $this->db
                ->get_where('customer_membership_sessions', ['id_appointments' => $appointment_id])
                ->row_array();

            if (!$session) {
                $this->db->trans_complete();
                return;
            }

            $membership_id = $session['id_customer_memberships'];

            $this->db->delete('customer_membership_sessions', ['id_appointments' => $appointment_id]);

            $this->db->set('sessions_used_this_period', 'GREATEST(sessions_used_this_period - 1, 0)', false);
            $this->db->where('id', $membership_id);
            $this->db->update('customer_memberships');

            $this->db->trans_complete();
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw new RuntimeException('Could not restore membership session: ' . $e->getMessage());
        }
    }

    /**
     * Add one billing period to a datetime string.
     *
     * @param string $from Datetime string ('Y-m-d H:i:s').
     * @param string $billing_period monthly, quarterly, or yearly.
     * @return string The resulting datetime string.
     */
    private function add_billing_period(string $from, string $billing_period): string
    {
        $months = match ($billing_period) {
            'quarterly' => 3,
            'yearly' => 12,
            default => 1,
        };

        return date('Y-m-d H:i:s', strtotime('+' . $months . ' months', strtotime($from)));
    }
}
