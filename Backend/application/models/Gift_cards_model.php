<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Gift Cards & Deposit (Kapora) Management Model
 * ---------------------------------------------------------------------------- */

class Gift_cards_model extends App_Model
{
    /**
     * Generate a unique uppercase gift card code (e.g. GC-98B2-X5A1).
     */
    public function generate_code(): string
    {
        do {
            $code = 'GC-' . strtoupper(bin2hex(random_bytes(2))) . '-' . strtoupper(bin2hex(random_bytes(2)));
            $exists = $this->db->get_where('gift_cards', ['code' => $code])->num_rows() > 0;
        } while ($exists);

        return $code;
    }

    /**
     * Issue a new gift card.
     */
    public function issue_card(array $data): array
    {
        if (empty($data['code'])) {
            $data['code'] = $this->generate_code();
        }

        $amount = (float) ($data['initial_amount'] ?? 0.00);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Hediye kartı tutarı sıfırdan büyük olmalıdır.');
        }

        $now = date('Y-m-d H:i:s');
        $card = [
            'code' => strtoupper(trim($data['code'])),
            'initial_amount' => $amount,
            'current_balance' => $amount,
            'id_users_customer' => !empty($data['id_users_customer']) ? (int) $data['id_users_customer'] : null,
            'recipient_name' => $data['recipient_name'] ?? null,
            'recipient_email' => $data['recipient_email'] ?? null,
            'recipient_phone' => $data['recipient_phone'] ?? null,
            'status' => 'active',
            'expires_at' => !empty($data['expires_at']) ? $data['expires_at'] : null,
            'notes' => $data['notes'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->db->insert('gift_cards', $card);
        $card['id'] = $this->db->insert_id();

        return $card;
    }

    /**
     * Get gift card by code with validation.
     */
    public function get_by_code(string $code): ?array
    {
        $card = $this->db->get_where('gift_cards', ['code' => strtoupper(trim($code))])->row_array();
        if (!$card) {
            return null;
        }

        // Check expiration
        if (!empty($card['expires_at']) && $card['expires_at'] < date('Y-m-d') && $card['status'] === 'active') {
            $this->db->update('gift_cards', ['status' => 'expired', 'updated_at' => date('Y-m-d H:i:s')], ['id' => $card['id']]);
            $card['status'] = 'expired';
        }

        return $card;
    }

    /**
     * Redeem part or all of a gift card balance against an appointment or adisyon.
     */
    public function redeem(string $code, float $amount, ?int $appointment_id = null, ?int $adisyon_id = null): array
    {
        $card = $this->get_by_code($code);
        if (!$card) {
            return ['success' => false, 'message' => 'Geçersiz hediye kartı kodu.'];
        }

        if ($card['status'] !== 'active') {
            return ['success' => false, 'message' => 'Hediye kartı aktif değil (Durum: ' . $card['status'] . ').'];
        }

        if ((float) $card['current_balance'] < $amount) {
            return [
                'success' => false,
                'message' => 'Yetersiz bakiye. Mevcut bakiye: ' . number_format($card['current_balance'], 2) . ' TL',
                'available_balance' => (float) $card['current_balance'],
            ];
        }

        $new_balance = (float) $card['current_balance'] - $amount;
        $new_status = ($new_balance <= 0.001) ? 'redeemed' : 'active';
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();

        // 1. Update card balance
        $this->db->update('gift_cards', [
            'current_balance' => $new_balance,
            'status' => $new_status,
            'updated_at' => $now,
        ], ['id' => $card['id']]);

        // 2. Record redemption
        $this->db->insert('gift_card_redemptions', [
            'id_gift_cards' => $card['id'],
            'id_appointments' => $appointment_id,
            'id_adisyons' => $adisyon_id,
            'redeemed_amount' => $amount,
            'redeemed_at' => $now,
        ]);

        $this->db->trans_complete();

        return [
            'success' => true,
            'message' => number_format($amount, 2) . ' TL başarıyla kullanıldı.',
            'redeemed_amount' => $amount,
            'remaining_balance' => $new_balance,
            'card_status' => $new_status,
        ];
    }

    /**
     * Record deposit (kapora) payment for an appointment.
     */
    public function record_appointment_deposit(int $appointment_id, float $amount, ?string $transaction_id = null): bool
    {
        $now = date('Y-m-d H:i:s');
        return $this->db->update('appointments', [
            'deposit_amount' => $amount,
            'deposit_status' => 'paid',
            'deposit_paid_at' => $now,
            'deposit_transaction_id' => $transaction_id,
            'update_datetime' => $now,
        ], ['id' => $appointment_id]);
    }

    /**
     * Update appointment deposit status (e.g. pending, paid, refunded, forfeited).
     */
    public function update_deposit_status(int $appointment_id, string $status): bool
    {
        $valid = ['none', 'pending', 'paid', 'refunded', 'forfeited'];
        if (!in_array($status, $valid, true)) {
            throw new InvalidArgumentException('Geçersiz kapora durumu: ' . $status);
        }

        return $this->db->update('appointments', [
            'deposit_status' => $status,
            'update_datetime' => date('Y-m-d H:i:s'),
        ], ['id' => $appointment_id]);
    }
}
