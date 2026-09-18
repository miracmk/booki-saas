<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Staff Commissions & Payroll Engine
 * ---------------------------------------------------------------------------- */

class Staff_commissions_model extends EA_Model
{
    /**
     * Calculate and record commissions for items in an adisyon or completed appointment.
     */
    public function calculate_for_adisyon(int $adisyon_id): void
    {
        $adisyon = $this->db->get_where('adisyons', ['id' => $adisyon_id])->row_array();
        if (!$adisyon) {
            return;
        }

        $items = $this->db->get_where('adisyon_items', ['id_adisyons' => $adisyon_id])->result_array();

        foreach ($items as $item) {
            $staff_id = !empty($item['id_users_staff']) ? (int) $item['id_users_staff'] : (int) $adisyon['id_users_staff'];
            if (!$staff_id) {
                continue;
            }

            // Check if already computed
            $existing = $this->db
                ->get_where('staff_commissions', [
                    'id_adisyons' => $adisyon_id,
                    'id_services' => $item['id_services'],
                    'id_products' => $item['id_products'],
                    'id_users_staff' => $staff_id,
                ])
                ->num_rows();

            if ($existing > 0) {
                continue;
            }

            // Determine commission rate
            $commission_rate = 0.00;
            if ($item['item_type'] === 'service' && !empty($item['id_services'])) {
                // Check provider-service specific commission if table exists
                if ($this->db->table_exists('provider_service_commissions')) {
                    $spec = $this->db->get_where('provider_service_commissions', [
                        'id_users_provider' => $staff_id,
                        'id_services' => $item['id_services'],
                    ])->row_array();
                    if ($spec) {
                        $commission_rate = (float) $spec['commission_rate'];
                    }
                }
                // Fallback to provider general commission rate
                if ($commission_rate <= 0) {
                    $provider = $this->db->get_where('user_settings', ['id_users' => $staff_id])->row_array();
                    $commission_rate = (float) ($provider['commission_rate'] ?? 15.00);
                }
            } elseif ($item['item_type'] === 'product') {
                $commission_rate = 10.00; // default 10% on retail products
            }

            $sale_amount = (float) $item['total_amount'];
            $commission_amount = round($sale_amount * ($commission_rate / 100), 2);

            $this->db->insert('staff_commissions', [
                'id_users_staff' => $staff_id,
                'id_appointments' => $adisyon['id_appointments'],
                'id_adisyons' => $adisyon_id,
                'id_services' => $item['id_services'],
                'id_products' => $item['id_products'],
                'sale_amount' => $sale_amount,
                'commission_rate' => $commission_rate,
                'commission_amount' => $commission_amount,
                'tip_amount' => 0.00,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Get commission overview for all staff or specific staff in date range.
     */
    public function get_commissions_summary(?int $staff_id = null, ?string $start_date = null, ?string $end_date = null): array
    {
        $this->db
            ->select('sc.id_users_staff, u.first_name, u.last_name, u.email,
                      COUNT(sc.id) as sales_count,
                      SUM(sc.sale_amount) as total_sales,
                      SUM(sc.commission_amount) as total_commission,
                      SUM(sc.tip_amount) as total_tips')
            ->from('staff_commissions sc')
            ->join('users u', 'u.id = sc.id_users_staff', 'left')
            ->group_by('sc.id_users_staff');

        if ($staff_id) {
            $this->db->where('sc.id_users_staff', $staff_id);
        }
        if ($start_date) {
            $this->db->where('DATE(sc.created_at) >=', $start_date);
        }
        if ($end_date) {
            $this->db->where('DATE(sc.created_at) <=', $end_date);
        }

        return $this->db->get()->result_array();
    }

    /**
     * Generate payroll payout.
     */
    public function generate_payout(int $staff_id, string $period_start, string $period_end, float $base_salary = 0.00, float $advances = 0.00): int
    {
        $sums = $this->db
            ->select('SUM(commission_amount) as comm_sum, SUM(tip_amount) as tip_sum')
            ->from('staff_commissions')
            ->where('id_users_staff', $staff_id)
            ->where('status', 'pending')
            ->where('DATE(created_at) >=', $period_start)
            ->where('DATE(created_at) <=', $period_end)
            ->get()
            ->row_array();

        $total_commission = (float) ($sums['comm_sum'] ?? 0);
        $total_tips = (float) ($sums['tip_sum'] ?? 0);
        $net_payout = max(0, round($base_salary + $total_commission + $total_tips - $advances, 2));

        $this->db->trans_start();
        $this->db->insert('staff_payouts', [
            'id_users_staff' => $staff_id,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'base_salary' => $base_salary,
            'total_commission' => $total_commission,
            'total_tips' => $total_tips,
            'advances_deduction' => $advances,
            'net_payout' => $net_payout,
            'payment_method' => 'bank_transfer',
            'payment_date' => date('Y-m-d'),
            'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $payout_id = $this->db->insert_id();

        // Mark commissions as paid
        $this->db
            ->where('id_users_staff', $staff_id)
            ->where('status', 'pending')
            ->where('DATE(created_at) >=', $period_start)
            ->where('DATE(created_at) <=', $period_end)
            ->update('staff_commissions', [
                'status' => 'paid',
                'payout_date' => date('Y-m-d'),
            ]);

        $this->db->trans_complete();
        return $payout_id;
    }
}
