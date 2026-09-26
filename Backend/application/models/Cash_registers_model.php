<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Cash Registers Model (Kasa Yönetimi & Gün Sonu Kapanışı)
 * ---------------------------------------------------------------------------- */

class Cash_registers_model extends App_Model
{
    /**
     * Get currently active open cash register or initialize one.
     */
    public function get_active_register(): array
    {
        $register = $this->db
            ->select('cr.*, u.first_name as opener_first_name, u.last_name as opener_last_name')
            ->from('cash_registers cr')
            ->join('users u', 'u.id = cr.opened_by', 'left')
            ->where('cr.status', 'open')
            ->order_by('cr.id DESC')
            ->limit(1)
            ->get()
            ->row_array();

        if ($register) {
            return $register;
        }

        // Auto open initial register if none exists
        $now = date('Y-m-d H:i:s');
        $this->db->insert('cash_registers', [
            'register_name' => 'Ana Kasa',
            'opening_balance' => 0.00,
            'current_balance' => 0.00,
            'status' => 'open',
            'opened_at' => $now,
            'total_cash_in' => 0.00,
            'total_cash_out' => 0.00,
            'created_at' => $now,
        ]);
        $id = $this->db->insert_id();

        return $this->db->get_where('cash_registers', ['id' => $id])->row_array();
    }

    /**
     * Open a new cash session with initial float.
     */
    public function open_register(string $name, float $opening_balance, ?int $opened_by = null): int
    {
        // Close any lingering open registers
        $this->db->update('cash_registers', [
            'status' => 'closed',
            'closed_at' => date('Y-m-d H:i:s'),
        ], ['status' => 'open']);

        $now = date('Y-m-d H:i:s');
        $this->db->insert('cash_registers', [
            'register_name' => $name ?: 'Ana Kasa',
            'opening_balance' => $opening_balance,
            'current_balance' => $opening_balance,
            'status' => 'open',
            'opened_by' => $opened_by,
            'opened_at' => $now,
            'total_cash_in' => 0.00,
            'total_cash_out' => 0.00,
            'created_at' => $now,
        ]);

        return $this->db->insert_id();
    }

    /**
     * Close register / Perform Gün Sonu (EOD reconciliation).
     */
    public function close_register(int $register_id, float $actual_cash, ?int $closed_by = null, ?string $notes = null): array
    {
        $reg = $this->db->get_where('cash_registers', ['id' => $register_id])->row_array();
        if (!$reg) {
            throw new InvalidArgumentException('Kasa bulunamadı.');
        }

        $now = date('Y-m-d H:i:s');
        $opening = (float) $reg['opening_balance'];
        $cash_in = (float) $reg['total_cash_in'];
        $cash_out = (float) $reg['total_cash_out'];
        $expected_cash = round($opening + $cash_in - $cash_out, 2);
        $difference = round($actual_cash - $expected_cash, 2);

        $this->db->update('cash_registers', [
            'status' => 'closed',
            'closed_by' => $closed_by,
            'closed_at' => $now,
            'expected_cash' => $expected_cash,
            'actual_cash' => $actual_cash,
            'difference' => $difference,
            'closing_notes' => $notes,
        ], ['id' => $register_id]);

        return [
            'register_id' => $register_id,
            'opening_balance' => $opening,
            'total_cash_in' => $cash_in,
            'total_cash_out' => $cash_out,
            'expected_cash' => $expected_cash,
            'actual_cash' => $actual_cash,
            'difference' => $difference,
            'closed_at' => $now,
        ];
    }

    /**
     * Get closing history.
     */
    public function get_history(int $limit = 30): array
    {
        return $this->db
            ->select('cr.*, 
                      uo.first_name as opener_first_name, uo.last_name as opener_last_name,
                      uc.first_name as closer_first_name, uc.last_name as closer_last_name')
            ->from('cash_registers cr')
            ->join('users uo', 'uo.id = cr.opened_by', 'left')
            ->join('users uc', 'uc.id = cr.closed_by', 'left')
            ->order_by('cr.id DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }
}
