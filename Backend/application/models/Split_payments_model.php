<?php defined('BASEPATH') or exit('No direct script access allowed');

class Split_payments_model extends CI_Model
{

    public function get_payments(string $entity_type, int $entity_id): array
    {
        $this->db->where('entity_type', $entity_type);
        $this->db->where('entity_id', $entity_id);
        $this->db->order_by('created_at', 'ASC');
        $query = $this->db->get('system_split_payments');
        return $query->result_array();
    }

    public function add_payment(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        if (!isset($data['received_by']) && $this->session->userdata('user_id')) {
            $data['received_by'] = $this->session->userdata('user_id');
        }

        $this->db->insert('system_split_payments', $data);
        return $this->db->insert_id();
    }

    public function remove_payment(int $payment_id): bool
    {
        $this->db->where('id', $payment_id);
        return $this->db->delete('system_split_payments');
    }

    public function get_totals(string $entity_type, int $entity_id): array
    {
        $this->db->select('payment_type, amount');
        $this->db->where('entity_type', $entity_type);
        $this->db->where('entity_id', $entity_id);
        $query = $this->db->get('system_split_payments');
        $payments = $query->result_array();

        $total_paid = 0.0;
        $total_discount = 0.0;
        $total_complimentary = 0.0;
        $grand_total = 0.0;

        foreach ($payments as $payment) {
            $amount = (float) $payment['amount'];
            if (in_array($payment['payment_type'], ['cash', 'card', 'transfer', 'gift_card', 'membership'])) {
                $total_paid += $amount;
            } elseif (in_array($payment['payment_type'], ['discount', 'coupon'])) {
                $total_discount += $amount;
            } elseif ($payment['payment_type'] === 'complimentary') {
                $total_complimentary += $amount;
            }
            $grand_total += $amount;
        }

        return [
            'total_paid' => $total_paid,
            'total_discount' => $total_discount,
            'total_complimentary' => $total_complimentary,
            'grand_total' => $grand_total
        ];
    }

    public function clear_all(string $entity_type, int $entity_id): void
    {
        $this->db->where('entity_type', $entity_type);
        $this->db->where('entity_id', $entity_id);
        $this->db->delete('system_split_payments');
    }
}
