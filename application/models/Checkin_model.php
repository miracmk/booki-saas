<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Check-in / Check-out Model (Operational Entry/Exit, Kiosk & Occupancy)
 * ---------------------------------------------------------------------------- */

class Checkin_model extends EA_Model
{
    /**
     * Perform check-in by customer ID, phone, QR code or appointment ID.
     */
    public function check_in(array $params): array
    {
        $customer_id = !empty($params['id_users_customer']) ? (int) $params['id_users_customer'] : null;
        $phone = !empty($params['phone']) ? trim($params['phone']) : null;
        $qr_token = !empty($params['qr_token']) ? trim($params['qr_token']) : null;
        $appointment_id = !empty($params['id_appointments']) ? (int) $params['id_appointments'] : null;
        $method = $params['checkin_method'] ?? 'manual';
        $now = date('Y-m-d H:i:s');

        // Locate customer if not directly provided
        if (!$customer_id) {
            if ($qr_token) {
                // Check if QR token matches customer membership or customer
                $membership = $this->db->get_where('customer_memberships', ['qr_code_token' => $qr_token])->row_array();
                if ($membership) {
                    $customer_id = (int) $membership['id_users_customer'];
                    $params['id_customer_memberships'] = (int) $membership['id'];
                } else {
                    $customer = $this->db->get_where('users', ['id' => (int) $qr_token])->row_array();
                    if ($customer) {
                        $customer_id = (int) $customer['id'];
                    }
                }
            } elseif ($phone) {
                $clean_phone = preg_replace('/[^\d]/', '', $phone);
                $customer = $this->db
                    ->where("REPLACE(REPLACE(REPLACE(phone_number, ' ', ''), '-', ''), '(', '') LIKE '%" . substr($clean_phone, -10) . "%'")
                    ->get('users')
                    ->row_array();
                if ($customer) {
                    $customer_id = (int) $customer['id'];
                }
            } elseif ($appointment_id) {
                $appt = $this->db->get_where('appointments', ['id' => $appointment_id])->row_array();
                if ($appt) {
                    $customer_id = (int) $appt['id_users_customer'];
                }
            }
        }

        if (!$customer_id) {
            throw new InvalidArgumentException('Müşteri bulunamadı. Lütfen geçerli bir telefon, QR kod veya müşteri seçin.');
        }

        // Fetch customer profile & check active memberships and packages
        $customer = $this->db->get_where('users', ['id' => $customer_id])->row_array();
        if (!$customer) {
            throw new InvalidArgumentException('Müşteri kaydı bulunamadı.');
        }

        // Check if already checked in
        $active_checkin = $this->db
            ->get_where('checkin_logs', [
                'id_users_customer' => $customer_id,
                'status' => 'inside',
            ])
            ->row_array();

        if ($active_checkin) {
            return [
                'status' => 'already_inside',
                'message' => $customer['first_name'] . ' ' . $customer['last_name'] . ' zaten içeride (Giriş: ' . date('H:i', strtotime($active_checkin['entry_timestamp'])) . ')',
                'checkin_log' => $active_checkin,
                'customer' => $customer,
            ];
        }

        // Find active membership
        $membership = $this->db
            ->select('cm.*, mp.name as plan_name, mp.sessions_per_period, mp.is_unlimited')
            ->from('customer_memberships cm')
            ->join('membership_plans mp', 'mp.id = cm.id_membership_plans', 'left')
            ->where('cm.id_users_customer', $customer_id)
            ->where('cm.status', 'active')
            ->get()
            ->row_array();

        // Find active packages
        $packages = $this->db
            ->select('cp.*, s.name as service_name')
            ->from('customer_packages cp')
            ->join('services s', 's.id = cp.id_services', 'left')
            ->where('cp.id_users_customer', $customer_id)
            ->where('cp.status', 'active')
            ->where('cp.used_sessions <', $this->db->raw('total_sessions'))
            ->get()
            ->result_array();

        $this->db->trans_start();
        $this->db->insert('checkin_logs', [
            'id_users_customer' => $customer_id,
            'id_appointments' => $appointment_id,
            'id_customer_memberships' => $membership ? (int) $membership['id'] : null,
            'id_customer_packages' => !empty($params['id_customer_packages']) ? (int) $params['id_customer_packages'] : null,
            'id_restaurant_tables' => !empty($params['id_restaurant_tables']) ? (int) $params['id_restaurant_tables'] : null,
            'checkin_method' => $method,
            'entry_timestamp' => $now,
            'status' => 'inside',
            'checked_in_by' => !empty($params['checked_in_by']) ? (int) $params['checked_in_by'] : null,
            'notes' => $params['notes'] ?? null,
            'created_at' => $now,
        ]);
        $checkin_id = $this->db->insert_id();

        // If membership has session counter, increment this period
        if ($membership) {
            $this->db->set('sessions_used_this_period', 'sessions_used_this_period + 1', false);
            $this->db->where('id', $membership['id']);
            $this->db->update('customer_memberships');
        }

        // If appointment linked, mark check-in on appointment if field exists
        if ($appointment_id && $this->db->field_exists('is_checked_in', 'appointments')) {
            $this->db->update('appointments', ['is_checked_in' => 1], ['id' => $appointment_id]);
        }

        $this->db->trans_complete();

        return [
            'status' => 'success',
            'checkin_id' => $checkin_id,
            'customer' => $customer,
            'membership' => $membership,
            'packages' => $packages,
            'entry_time' => date('H:i', strtotime($now)),
        ];
    }

    /**
     * Perform check-out.
     */
    public function check_out(int $checkin_id): array
    {
        $checkin = $this->db->get_where('checkin_logs', ['id' => $checkin_id])->row_array();
        if (!$checkin) {
            throw new InvalidArgumentException('Giriş kaydı bulunamadı.');
        }

        $now = date('Y-m-d H:i:s');
        $duration = max(1, round((strtotime($now) - strtotime($checkin['entry_timestamp'])) / 60));

        $this->db->update('checkin_logs', [
            'exit_timestamp' => $now,
            'duration_minutes' => $duration,
            'status' => 'departed',
        ], ['id' => $checkin_id]);

        return [
            'status' => 'success',
            'checkin_id' => $checkin_id,
            'duration_minutes' => $duration,
            'exit_time' => date('H:i', strtotime($now)),
        ];
    }

    /**
     * Get live occupancy status (currently inside count, capacity, recent check-ins).
     */
    public function get_live_occupancy(): array
    {
        $inside = $this->db
            ->select('cl.*, 
                      c.first_name as customer_first_name, c.last_name as customer_last_name, c.phone_number as customer_phone,
                      mp.name as membership_plan_name,
                      rt.table_number, rt.name as table_name')
            ->from('checkin_logs cl')
            ->join('users c', 'c.id = cl.id_users_customer', 'left')
            ->join('customer_memberships cm', 'cm.id = cl.id_customer_memberships', 'left')
            ->join('membership_plans mp', 'mp.id = cm.id_membership_plans', 'left')
            ->join('restaurant_tables rt', 'rt.id = cl.id_restaurant_tables', 'left')
            ->where('cl.status', 'inside')
            ->order_by('cl.entry_timestamp DESC')
            ->get()
            ->result_array();

        $capacity = (int) ($this->db->get_where('settings', ['name' => 'max_capacity'])->row()->value ?? 50);
        $current_count = count($inside);
        $occupancy_rate = $capacity > 0 ? min(100, round(($current_count / $capacity) * 100)) : 0;

        return [
            'current_count' => $current_count,
            'max_capacity' => $capacity,
            'occupancy_rate' => $occupancy_rate,
            'active_guests' => $inside,
        ];
    }

    /**
     * Get visit history with filtering.
     */
    public function get_history(?int $customer_id = null, ?string $date = null, int $limit = 50): array
    {
        $this->db
            ->select('cl.*, 
                      c.first_name as customer_first_name, c.last_name as customer_last_name, c.phone_number as customer_phone,
                      mp.name as membership_plan_name,
                      s.name as service_name')
            ->from('checkin_logs cl')
            ->join('users c', 'c.id = cl.id_users_customer', 'left')
            ->join('customer_memberships cm', 'cm.id = cl.id_customer_memberships', 'left')
            ->join('membership_plans mp', 'mp.id = cm.id_membership_plans', 'left')
            ->join('appointments a', 'a.id = cl.id_appointments', 'left')
            ->join('services s', 's.id = a.id_services', 'left');

        if ($customer_id) {
            $this->db->where('cl.id_users_customer', $customer_id);
        }

        if ($date) {
            $this->db->where('DATE(cl.entry_timestamp)', $date);
        }

        return $this->db
            ->order_by('cl.entry_timestamp DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }
}
