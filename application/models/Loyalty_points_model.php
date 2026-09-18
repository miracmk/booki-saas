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
 * Loyalty Points model.
 *
 * Handles loyalty points earning and redemption for customers.
 *
 * @package Models
 */
class Loyalty_points_model extends EA_Model
{
    /**
     * @var array
     */
    protected array $casts = [
        'id' => 'integer',
        'id_users_customer' => 'integer',
        'points' => 'integer',
        'id_appointments' => 'integer',
        'created_by' => 'integer',
    ];

    /**
     * Add points to a customer account (typically from a completed appointment).
     *
     * @param int $customer_id Customer user ID.
     * @param int $appointment_id Associated appointment ID.
     * @param int $points Positive number of points to add.
     *
     * @throws InvalidArgumentException If customer_id is invalid or points < 0.
     */
    public function earn(int $customer_id, int $appointment_id, int $points): void
    {
        if ($customer_id <= 0) {
            throw new InvalidArgumentException('Invalid customer_id: ' . $customer_id);
        }

        if ($points < 0) {
            throw new InvalidArgumentException('Points must be non-negative for earn(): ' . $points);
        }

        if ($points === 0) {
            return; // No-op for 0 points
        }

        try {
            $this->db->trans_begin();

            // Record the earning transaction
            $this->db->insert($this->db->dbprefix('loyalty_points'), [
                'id_users_customer' => $customer_id,
                'id_appointments' => $appointment_id,
                'points' => $points,
                'reason' => 'earned_appointment',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Update the customer's balance
            $this->db->query(
                'UPDATE ' . $this->db->dbprefix('users') .
                ' SET loyalty_points_balance = loyalty_points_balance + ' . intval($points) .
                ' WHERE id = ' . intval($customer_id),
            );

            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();
                throw new RuntimeException('Failed to record loyalty points earning.');
            }

            $this->db->trans_commit();
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw $e;
        }
    }

    /**
     * Redeem points from a customer account.
     *
     * @param int $customer_id Customer user ID.
     * @param int $points Positive number of points to redeem.
     * @param string|null $reason Optional reason for redemption (default: 'redeemed').
     *
     * @throws InvalidArgumentException If points exceeds customer balance or if points < 0.
     */
    public function redeem(int $customer_id, int $points, ?string $reason = null): void
    {
        if ($customer_id <= 0) {
            throw new InvalidArgumentException('Invalid customer_id: ' . $customer_id);
        }

        if ($points < 0) {
            throw new InvalidArgumentException('Points must be non-negative for redeem(): ' . $points);
        }

        if ($points === 0) {
            return; // No-op for 0 points
        }

        if ($reason === null) {
            $reason = 'redeemed';
        }

        try {
            $this->db->trans_begin();

            // Record the redemption as a negative transaction
            $this->db->insert($this->db->dbprefix('loyalty_points'), [
                'id_users_customer' => $customer_id,
                'id_appointments' => null,
                'points' => -$points,
                'reason' => $reason,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Atomically decrement the customer's balance ensuring it doesn't go below zero
            $this->db->query(
                'UPDATE ' . $this->db->dbprefix('users') .
                ' SET loyalty_points_balance = loyalty_points_balance - ? ' .
                ' WHERE id = ? AND loyalty_points_balance >= ?',
                [(int)$points, (int)$customer_id, (int)$points]
            );

            if ($this->db->affected_rows() !== 1 || $this->db->trans_status() === false) {
                $this->db->trans_rollback();
                throw new InvalidArgumentException('Insufficient loyalty points or concurrent modification.');
            }

            $this->db->trans_commit();
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw $e;
        }
    }

    /**
     * Get the current points balance for a customer.
     *
     * @param int $customer_id Customer user ID.
     *
     * @return int Current points balance (0 if customer not found).
     */
    public function get_balance(int $customer_id): int
    {
        $result = $this->db->select('loyalty_points_balance')
            ->from($this->db->dbprefix('users'))
            ->where('id', $customer_id)
            ->get()
            ->row();

        if (!$result) {
            return 0;
        }

        return intval($result->loyalty_points_balance) ?? 0;
    }

    /**
     * Get the points history for a customer.
     *
     * @param int $customer_id Customer user ID.
     *
     * @return array Array of loyalty points records, newest first.
     */
    public function get_history(int $customer_id): array
    {
        return $this->db->select('*')
            ->from($this->db->dbprefix('loyalty_points'))
            ->where('id_users_customer', $customer_id)
            ->order_by('created_at', 'DESC')
            ->get()
            ->result_array();
    }
}
