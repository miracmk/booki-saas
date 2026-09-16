<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Payment transactions model (2026-08-27).
 *
 * Handles payment_transactions table operations. Provides CRUD methods for
 * audit trail of payment attempts/completions.
 * ---------------------------------------------------------------------------- */

class Payment_transactions_model extends EA_Model
{
    /**
     * Save a new payment transaction.
     *
     * @param array $transaction Transaction data (gateway, amount, status, etc.).
     *
     * @return int Transaction ID.
     *
     * @throws RuntimeException
     */
    public function save(array $transaction): int
    {
        $transaction['created_at'] = date('Y-m-d H:i:s');

        if (!$this->db->insert('payment_transactions', $transaction)) {
            throw new RuntimeException('Could not insert payment transaction.');
        }

        return $this->db->insert_id();
    }

    /**
     * Find a transaction by ID.
     *
     * @param int $id Transaction ID.
     *
     * @return array|null Transaction row, or null if not found.
     */
    public function find(int $id): ?array
    {
        return $this->db->get_where('payment_transactions', ['id' => $id])->row_array() ?: null;
    }

    /**
     * Find a transaction by intent ID (gateway-specific payment intent identifier).
     *
     * @param string $intent_id Intent ID from create_payment_intent().
     *
     * @return array|null Transaction row, or null if not found.
     */
    public function find_by_intent_id(string $intent_id): ?array
    {
        return $this->db->get_where('payment_transactions', ['intent_id' => $intent_id])->row_array() ?: null;
    }

    /**
     * Find a transaction by provider transaction ID (gateway-issued transaction ID).
     *
     * @param string $provider_transaction_id Provider transaction ID.
     *
     * @return array|null Transaction row, or null if not found.
     */
    public function find_by_provider_transaction_id(string $provider_transaction_id): ?array
    {
        return $this->db->get_where('payment_transactions', ['provider_transaction_id' => $provider_transaction_id])->row_array() ?: null;
    }

    /**
     * Faz 30 (KVKK export) - a customer's payment transaction history. id_users is nullable
     * (e.g. walk-in POS sales with no linked customer), so this never matches those rows.
     *
     * @param int $customer_id
     * @return array
     */
    public function get_for_customer(int $customer_id): array
    {
        return $this->db
            ->where('id_users', $customer_id)
            ->order_by('created_at', 'DESC')
            ->get('payment_transactions')
            ->result_array();
    }

    /**
     * Update the status of an existing transaction.
     *
     * @param int $id Transaction ID.
     * @param string $status New status (pending, succeeded, failed, refunded, partially_refunded).
     * @param string|null $raw_response Raw API response (optional).
     *
     * @throws RuntimeException
     */
    public function update_status(int $id, string $status, ?string $raw_response = null): void
    {
        $update_data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($raw_response !== null) {
            $update_data['raw_response'] = $raw_response;
        }

        if (!$this->db->update('payment_transactions', $update_data, ['id' => $id])) {
            throw new RuntimeException('Could not update payment transaction status.');
        }
    }

    /**
     * Get all transactions for an appointment (may have multiple: deposit, full_payment, refund).
     *
     * @param int $appointment_id Appointment ID.
     *
     * @return array Array of transaction rows.
     */
    public function get_by_appointment(int $appointment_id): array
    {
        return $this->db
            ->get_where('payment_transactions', ['id_appointments' => $appointment_id])
            ->result_array() ?: [];
    }

    /**
     * Get all transactions for a customer.
     *
     * @param int $user_id Customer user ID.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     *
     * @return array Array of transaction rows.
     */
    public function get_by_customer(int $user_id, ?int $limit = null, ?int $offset = null): array
    {
        return $this->db
            ->get_where('payment_transactions', ['id_users' => $user_id], $limit, $offset)
            ->result_array() ?: [];
    }
}
