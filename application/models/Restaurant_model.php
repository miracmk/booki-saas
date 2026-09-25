<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Restaurant Operations Model (Floor Plans, Tables, Reservations & Experiences)
 * ---------------------------------------------------------------------------- */

class Restaurant_model extends EA_Model
{
    /**
     * Get all tables grouped by section with live status and active adisyon details.
     */
    public function get_tables_with_status(): array
    {
        $tables = $this->db
            ->select('rt.*, 
                      u.first_name as server_first_name, u.last_name as server_last_name,
                      ad.adisyon_number, ad.total_amount as adisyon_total, ad.paid_amount as adisyon_paid,
                      c.first_name as guest_first_name, c.last_name as guest_last_name, c.phone_number as guest_phone')
            ->from('restaurant_tables rt')
            ->join('users u', 'u.id = rt.id_users_server', 'left')
            ->join('adisyons ad', 'ad.id = rt.current_id_adisyons', 'left')
            ->join('users c', 'c.id = ad.id_users_customer', 'left')
            ->where('rt.is_active', 1)
            ->order_by('rt.section ASC, rt.table_number ASC')
            ->get()
            ->result_array();

        // Calculate occupancy time in minutes if seated
        $now = time();
        foreach ($tables as &$t) {
            if ($t['status'] === 'seated' || $t['status'] === 'dining') {
                $seated_time = !empty($t['seated_at']) ? strtotime($t['seated_at']) : $now;
                $t['minutes_seated'] = max(0, round(($now - $seated_time) / 60));
            } else {
                $t['minutes_seated'] = 0;
            }
        }

        return $tables;
    }

    /**
     * Update table position & dimensions on the visual floor plan.
     */
    public function update_table_layout(int $table_id, int $pos_x, int $pos_y, ?int $width = null, ?int $height = null): void
    {
        $data = [
            'pos_x' => $pos_x,
            'pos_y' => $pos_y,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($width !== null) {
            $data['width'] = $width;
        }
        if ($height !== null) {
            $data['height'] = $height;
        }

        $this->db->update('restaurant_tables', $data, ['id' => $table_id]);
    }

    /**
     * Update table status (e.g. available, reserved, seated, dining, bill_requested, paid, cleaning).
     */
    public function update_table_status(int $table_id, string $status, ?int $server_id = null): void
    {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($status === 'seated') {
            $data['seated_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'available' || $status === 'cleaning') {
            $data['current_id_adisyons'] = null;
            $data['current_id_reservations'] = null;
            $data['seated_at'] = null;
        }

        if ($server_id !== null) {
            $data['id_users_server'] = $server_id;
        }

        $this->db->update('restaurant_tables', $data, ['id' => $table_id]);
    }

    /**
     * Create or update a table.
     */
    public function save_table(array $data): int
    {
        if (empty($data['table_number'])) {
            throw new InvalidArgumentException('Masa numarası gereklidir.');
        }

        if (empty($data['id'])) {
            if (function_exists('require_tenant_quota')) {
                require_tenant_quota('resource');
            }
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('restaurant_tables', $data);
            return $this->db->insert_id();
        } else {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('restaurant_tables', $data, ['id' => $data['id']]);
            return (int) $data['id'];
        }
    }

    /**
     * Delete table.
     */
    public function delete_table(int $table_id): void
    {
        $this->db->update('restaurant_tables', ['is_active' => 0], ['id' => $table_id]);
    }

    /**
     * Get restaurant reservations with filtering.
     */
    public function get_reservations(?string $date = null, ?string $status = null): array
    {
        $this->db
            ->select('rr.*, 
                      c.first_name as guest_first_name, c.last_name as guest_last_name, c.phone_number as guest_phone, c.email as guest_email,
                      rt.table_number, rt.name as table_name, rt.section as table_section,
                      re.title as experience_title')
            ->from('restaurant_reservations rr')
            ->join('users c', 'c.id = rr.id_users_customer', 'left')
            ->join('restaurant_tables rt', 'rt.id = rr.id_restaurant_tables', 'left')
            ->join('restaurant_experiences re', 're.id = rr.id_restaurant_experiences', 'left');

        if ($date) {
            $this->db->where('DATE(rr.reservation_datetime)', $date);
        }

        if ($status) {
            $this->db->where('rr.status', $status);
        }

        return $this->db
            ->order_by('rr.reservation_datetime ASC')
            ->get()
            ->result_array();
    }

    /**
     * Create or update restaurant reservation.
     */
    public function save_reservation(array $data): int
    {
        if (empty($data['reservation_datetime']) || empty($data['party_size'])) {
            throw new InvalidArgumentException('Tarih ve kişi sayısı zorunludur.');
        }

        if (empty($data['id'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('restaurant_reservations', $data);
            return $this->db->insert_id();
        } else {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('restaurant_reservations', $data, ['id' => $data['id']]);
            return (int) $data['id'];
        }
    }

    /**
     * Seat a reservation at a table and open adisyon.
     */
    public function seat_reservation(int $reservation_id, ?int $table_id = null): array
    {
        $res = $this->db->get_where('restaurant_reservations', ['id' => $reservation_id])->row_array();
        if (!$res) {
            throw new InvalidArgumentException('Rezervasyon bulunamadı.');
        }

        $target_table_id = $table_id ?: $res['id_restaurant_tables'];
        if (!$target_table_id) {
            throw new InvalidArgumentException('Lütfen bir masa seçin.');
        }

        $this->db->trans_start();
        try {
            $this->load->model('adisyons_model');
            $adisyon = $this->adisyons_model->get_or_create_for_table($target_table_id, (int) $res['id_users_customer']);

            $this->db->update('restaurant_reservations', [
                'id_restaurant_tables' => $target_table_id,
                'status' => 'seated',
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $reservation_id]);

            $this->db->update('restaurant_tables', [
                'current_id_reservations' => $reservation_id,
                'current_id_adisyons' => $adisyon['id'],
                'status' => 'seated',
                'seated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $target_table_id]);

            $this->db->trans_complete();
            return [
                'reservation_id' => $reservation_id,
                'table_id' => $target_table_id,
                'adisyon_id' => $adisyon['id'],
            ];
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw new RuntimeException('Masa açılamadı: ' . $e->getMessage());
        }
    }

    /**
     * Experiences CRUD.
     */
    public function get_experiences(): array
    {
        return $this->db
            ->get_where('restaurant_experiences', ['is_active' => 1])
            ->result_array();
    }

    public function save_experience(array $data): int
    {
        if (empty($data['id'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('restaurant_experiences', $data);
            return $this->db->insert_id();
        } else {
            $this->db->update('restaurant_experiences', $data, ['id' => $data['id']]);
            return (int) $data['id'];
        }
    }

    /* -------------------------------------------------------------------------
     * GUEST INTELLIGENCE (SevenRooms / OpenTable Style Deep Guest CRM)
     * ------------------------------------------------------------------------- */

    /**
     * Get or initialize guest intelligence profile for a customer.
     */
    public function get_guest_preferences(int $customer_id): array
    {
        $prefs = $this->db->get_where('restaurant_guest_preferences', ['id_users_customer' => $customer_id])->row_array();
        if ($prefs) {
            return $prefs;
        }

        // Return empty defaults if not yet created
        return [
            'id_users_customer' => $customer_id,
            'vip_level' => 'regular',
            'dietary_restrictions' => null,
            'seating_preference' => null,
            'favorite_drink' => null,
            'special_notes' => null,
            'visit_count' => 0,
            'no_show_count' => 0,
            'average_spend' => 0.00,
        ];
    }

    /**
     * Upsert guest preferences (dietary, VIP, seating, favorite drinks).
     */
    public function save_guest_preferences(int $customer_id, array $data): array
    {
        $existing = $this->db->get_where('restaurant_guest_preferences', ['id_users_customer' => $customer_id])->row_array();
        $now = date('Y-m-d H:i:s');

        $dietary = $data['dietary_restrictions'] ?? [];
        if (!empty($data['allergies'])) {
            $allergies = is_array($data['allergies']) ? $data['allergies'] : [$data['allergies']];
            $dietary = array_merge(is_array($dietary) ? $dietary : [$dietary], $allergies);
        }
        if (is_array($dietary)) {
            $dietary = json_encode(array_values(array_unique($dietary)), JSON_UNESCAPED_UNICODE);
        }

        $record = [
            'vip_level' => $data['vip_level'] ?? 'regular',
            'dietary_restrictions' => $dietary,
            'seating_preference' => $data['seating_preference'] ?? null,
            'favorite_drink' => $data['favorite_drink'] ?? null,
            'special_notes' => $data['special_notes'] ?? null,
            'updated_at' => $now,
        ];

        if ($existing) {
            $this->db->update('restaurant_guest_preferences', $record, ['id_users_customer' => $customer_id]);
        } else {
            $record['id_users_customer'] = $customer_id;
            $record['visit_count'] = 0;
            $record['no_show_count'] = 0;
            $record['average_spend'] = 0.00;
            $record['created_at'] = $now;
            $this->db->insert('restaurant_guest_preferences', $record);
        }

        return $this->get_guest_preferences($customer_id);
    }

    /**
     * Increment visit or no-show count and update average spend.
     */
    public function record_guest_visit(int $customer_id, float $spend = 0.00, bool $is_no_show = false): void
    {
        $current = $this->get_guest_preferences($customer_id);
        $now = date('Y-m-d H:i:s');

        if ($is_no_show) {
            $update = [
                'no_show_count' => ((int) $current['no_show_count']) + 1,
                'updated_at' => $now,
            ];
        } else {
            $visits = ((int) $current['visit_count']) + 1;
            $old_total = ((float) $current['average_spend']) * ((int) $current['visit_count']);
            $new_avg = ($old_total + $spend) / max(1, $visits);

            $update = [
                'visit_count' => $visits,
                'average_spend' => round($new_avg, 2),
                'updated_at' => $now,
            ];

            // Auto-promote frequent/high-spend diners to VIP
            if ($visits >= 5 || $new_avg >= 1500) {
                if ($current['vip_level'] === 'regular') {
                    $update['vip_level'] = 'vip';
                }
            }
        }

        $this->save_guest_preferences($customer_id, array_merge($current, $update));
    }

    /* -------------------------------------------------------------------------
     * KITCHEN DISPLAY SYSTEM (KDS) & MUTFAK YÖNETİMİ (Simpra / Restopos Style)
     * ------------------------------------------------------------------------- */

    /**
     * Dispatch an item order to kitchen or bar display.
     */
    public function create_kitchen_order(array $order_data): int
    {
        $now = date('Y-m-d H:i:s');
        $record = [
            'id_adisyons' => !empty($order_data['id_adisyons']) ? (int) $order_data['id_adisyons'] : null,
            'id_restaurant_tables' => !empty($order_data['id_restaurant_tables']) ? (int) $order_data['id_restaurant_tables'] : null,
            'station' => $order_data['station'] ?? 'kitchen',
            'item_name' => trim($order_data['item_name'] ?? 'Sipariş Öğesi'),
            'quantity' => max(1, (int) ($order_data['quantity'] ?? 1)),
            'notes' => $order_data['notes'] ?? null,
            'status' => 'new',
            'ordered_at' => $now,
        ];

        $this->db->insert('kitchen_orders', $record);
        return $this->db->insert_id();
    }

    /**
     * Retrieve live kitchen tickets for kitchen display screens.
     */
    public function get_active_kitchen_orders(?string $station = null): array
    {
        $this->db
            ->select('ko.*, rt.table_number, rt.section')
            ->from('kitchen_orders ko')
            ->join('restaurant_tables rt', 'rt.id = ko.id_restaurant_tables', 'left')
            ->where_in('ko.status', ['new', 'preparing', 'ready'])
            ->order_by('ko.ordered_at ASC');

        if ($station !== null && $station !== 'all') {
            $this->db->where('ko.station', $station);
        }

        return $this->db->get()->result_array();
    }

    /**
     * Update kitchen order status: new -> preparing -> ready -> served.
     */
    public function update_kitchen_order_status(int $order_id, string $status): bool
    {
        $valid = ['new', 'preparing', 'ready', 'served', 'cancelled'];
        if (!in_array($status, $valid, true)) {
            throw new InvalidArgumentException('Geçersiz mutfak sipariş durumu: ' . $status);
        }

        $now = date('Y-m-d H:i:s');
        $data = [
            'status' => $status,
        ];

        if ($status === 'preparing' || $status === 'ready') {
            $data['prepared_at'] = $now;
        } elseif ($status === 'served') {
            $data['served_at'] = $now;
        }

        return $this->db->update('kitchen_orders', $data, ['id' => $order_id]);
    }
}

