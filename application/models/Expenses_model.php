<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Expenses Model (Operational & Recurring Cost Tracking)
 * ---------------------------------------------------------------------------- */

class Expenses_model extends App_Model
{
    protected array $casts = [
        'id' => 'integer',
        'amount' => 'float',
        'tax_amount' => 'float',
        'is_recurring' => 'boolean',
        'created_by' => 'integer',
    ];

    public function get_all(?string $category = null, ?string $start_date = null, ?string $end_date = null, int $limit = 100): array
    {
        $this->db
            ->select('e.*, u.first_name as creator_first_name, u.last_name as creator_last_name')
            ->from('expenses e')
            ->join('users u', 'u.id = e.created_by', 'left');

        if ($category) {
            $this->db->where('e.category', $category);
        }
        if ($start_date) {
            $this->db->where('e.expense_date >=', $start_date);
        }
        if ($end_date) {
            $this->db->where('e.expense_date <=', $end_date);
        }

        return $this->db
            ->order_by('e.expense_date DESC, e.id DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    public function save(array $data): int
    {
        if (empty($data['title']) || empty($data['amount']) || empty($data['expense_date'])) {
            throw new InvalidArgumentException('Gider başlığı, tutar ve tarih zorunludur.');
        }

        if (empty($data['id'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('expenses', $data);
            $id = $this->db->insert_id();

            // If cash payment, update cash register
            if (($data['payment_method'] ?? 'cash') === 'cash' && ($data['status'] ?? 'paid') === 'paid') {
                $open_reg = $this->db->get_where('cash_registers', ['status' => 'open'])->row_array();
                if ($open_reg) {
                    $amount = (float) $data['amount'];
                    $this->db->set('current_balance', 'current_balance - ' . $amount, false);
                    $this->db->set('total_cash_out', 'total_cash_out + ' . $amount, false);
                    $this->db->where('id', $open_reg['id']);
                    $this->db->update('cash_registers');
                }
            }

            return $id;
        } else {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('expenses', $data, ['id' => $data['id']]);
            return (int) $data['id'];
        }
    }

    public function delete(int $id): void
    {
        $this->db->delete('expenses', ['id' => $id]);
    }

    public function get_summary_by_category(?string $month = null): array
    {
        $target_month = $month ?: date('Y-m');
        return $this->db
            ->select('category, COUNT(*) as count, SUM(amount) as total_amount')
            ->from('expenses')
            ->where("DATE_FORMAT(expense_date, '%Y-%m') =", $target_month)
            ->where('status', 'paid')
            ->group_by('category')
            ->order_by('total_amount DESC')
            ->get()
            ->result_array();
    }
}
