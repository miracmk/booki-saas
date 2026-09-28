<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi SaaS - Client Beauty Profile & Formula History Model
 *
 * Implements client hair color formulas, patch test logs, before/after photo records,
 * and POS gratuity/tip split tracking (OpenSalon / Salon Booking System standard).
 */
class Beauty_profiles_model extends App_Model
{
    public function __construct()
    {
        //
    }

    /**
     * Get or create beauty profile for a customer.
     */
    public function get_profile(int $customer_id): ?array
    {
        return $this->db->get_where('customer_beauty_profiles', ['id_users_customer' => $customer_id])->row_array();
    }

    /**
     * Save beauty profile (color formulas, hair/skin type, patch test).
     */
    public function save_profile($customer_id_or_data, array $data = []): int
    {
        if (is_array($customer_id_or_data)) {
            $data = $customer_id_or_data;
            $customer_id = (int) ($data['id_users_customer'] ?? 0);
        } else {
            $customer_id = (int) $customer_id_or_data;
        }

        $now = date('Y-m-d H:i:s');
        $existing = $this->get_profile($customer_id);

        $record = [
            'color_formula' => $data['color_formula'] ?? $data['color_formula_history'] ?? null,
            'hair_type' => $data['hair_type'] ?? null,
            'skin_type' => $data['skin_type'] ?? null,
            'patch_test_date' => !empty($data['patch_test_date']) ? $data['patch_test_date'] : null,
            'patch_test_result' => $data['patch_test_result'] ?? $data['patch_test_results'] ?? 'negative',
            'nail_notes' => $data['nail_notes'] ?? null,
            'before_photo_url' => $data['before_photo_url'] ?? null,
            'after_photo_url' => $data['after_photo_url'] ?? null,
            'private_notes' => $data['private_notes'] ?? $data['preferred_stylist_notes'] ?? null,
            'updated_at' => $now,
        ];

        if ($existing) {
            $this->db->where('id_users_customer', $customer_id)->update('customer_beauty_profiles', $record);
            return (int) $existing['id'];
        }

        $record['id_users_customer'] = $customer_id;
        $record['created_at'] = $now;
        $this->db->insert('customer_beauty_profiles', $record);
        return (int) $this->db->insert_id();
    }

    /**
     * Save appointment / checkout tip.
     */
    public function save_tip(array $data): int
    {
        $record = [
            'id_appointments' => !empty($data['id_appointments']) ? (int) $data['id_appointments'] : null,
            'id_adisyons' => !empty($data['id_adisyons']) ? (int) $data['id_adisyons'] : null,
            'tip_amount' => (float) ($data['tip_amount'] ?? 0.00),
            'payment_method' => $data['payment_method'] ?? 'credit_card',
            'distributed_to_user_id' => !empty($data['distributed_to_user_id']) ? (int) $data['distributed_to_user_id'] : null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert('appointment_tips', $record);
        return (int) $this->db->insert_id();
    }

    /**
     * Get tips report by staff.
     */
    public function get_tips_by_staff(?string $start_date = null, ?string $end_date = null): array
    {
        $this->db->select('t.distributed_to_user_id, u.first_name, u.last_name, 
            COUNT(t.id) as tip_count, SUM(t.tip_amount) as total_tips')
            ->from('appointment_tips t')
            ->join('users u', 'u.id = t.distributed_to_user_id', 'left')
            ->group_by('t.distributed_to_user_id');

        if ($start_date) {
            $this->db->where('t.created_at >=', $start_date . ' 00:00:00');
        }
        if ($end_date) {
            $this->db->where('t.created_at <=', $end_date . ' 23:59:59');
        }

        return $this->db->get()->result_array();
    }
}
