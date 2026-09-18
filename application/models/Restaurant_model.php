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
}
