<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Restaurant Operations Model
 * Interactive Floor Plan (Kroki), Patronage (Müdavimlik), QR Menu, KDS & POS
 * ---------------------------------------------------------------------------- */

class Restaurant_model extends App_Model
{
    /* -------------------------------------------------------------------------
     * 1. TABLES & FLOOR PLAN
     * ------------------------------------------------------------------------- */

    /**
     * Get all tables grouped by section with live status, active adisyon and patronage details.
     */
    public function get_tables_with_status(): array
    {
        $tables = $this->db
            ->select('rt.*, 
                      u.first_name as server_first_name, u.last_name as server_last_name,
                      ad.adisyon_number, ad.total_amount as adisyon_total, ad.paid_amount as adisyon_paid,
                      c.first_name as guest_first_name, c.last_name as guest_last_name, c.phone_number as guest_phone,
                      cdp.patronage_tier, cdp.total_visits as guest_total_visits, cdp.service_notes as guest_service_notes')
            ->from('restaurant_tables rt')
            ->join('users u', 'u.id = rt.id_users_server', 'left')
            ->join('adisyons ad', 'ad.id = rt.current_id_adisyons', 'left')
            ->join('users c', 'c.id = ad.id_users_customer', 'left')
            ->join('customer_dining_profiles cdp', 'cdp.id_users_customer = c.id', 'left')
            ->where('rt.is_active', 1)
            ->order_by('rt.section ASC, rt.table_number ASC')
            ->get()
            ->result_array();

        // Calculate occupancy time in minutes, SambaPOS-3 aging stages and turn-time forecast
        $now = time();
        foreach ($tables as &$t) {
            $t['duration_mode'] = !empty($t['duration_mode']) ? $t['duration_mode'] : 'open_ended';
            $t['session_duration_minutes'] = (int) (!empty($t['session_duration_minutes']) ? $t['session_duration_minutes'] : 90);

            if ($t['status'] === 'seated' || $t['status'] === 'dining' || !empty($t['current_id_adisyons'])) {
                $seated_time = !empty($t['seated_at']) ? strtotime($t['seated_at']) : $now;
                $elapsed_mins = max(0, (int) round(($now - $seated_time) / 60));
                $t['minutes_seated'] = $elapsed_mins;

                // SambaPOS-3 Table Aging Stage & Visual State Machine
                if (($t['waiter_call_status'] ?? '') === 'bill') {
                    $t['aging_stage'] = 'bill_requested';
                    $t['aging_color'] = '#A855F7'; // Purple (Hesap İstendi - Acil)
                    $t['aging_label'] = 'Hesap İstendi';
                } elseif ($elapsed_mins < 25) {
                    $t['aging_stage'] = 'new_orders';
                    $t['aging_color'] = '#10B981'; // Green (Yeni Oturdu)
                    $t['aging_label'] = 'Yeni Açıldı';
                } elseif ($elapsed_mins < 60) {
                    $t['aging_stage'] = 'dining_active';
                    $t['aging_color'] = '#3B82F6'; // Blue (Yemekte)
                    $t['aging_label'] = 'Yemek Servisinde';
                } elseif ($elapsed_mins < 90) {
                    $t['aging_stage'] = 'mature_table';
                    $t['aging_color'] = '#EAB308'; // Yellow (Tatlı / Kahve)
                    $t['aging_label'] = 'Tatlı & Kahve';
                } elseif ($elapsed_mins < 120) {
                    $t['aging_stage'] = 'long_stay';
                    $t['aging_color'] = '#F97316'; // Orange (Uzun Oturum)
                    $t['aging_label'] = 'Uzun Oturum';
                } else {
                    $t['aging_stage'] = 'vip_extended';
                    $t['aging_color'] = '#8B5CF6'; // Violet (Müdavim / Gece Oturumu)
                    $t['aging_label'] = 'Müdavim Masası';
                }

                // SambaPOS-3 Turn-Time Forecasting (Kişi Sayısına Göre Ortalama Boşalma)
                $cap = (int) ($t['capacity'] ?? 4);
                $avg_turnaround = ($cap <= 2) ? 55 : (($cap <= 4) ? 80 : 110);
                $est_remaining = max(5, $avg_turnaround - $elapsed_mins);
                $t['predicted_turnaround_mins'] = $avg_turnaround;
                $t['predicted_remaining_mins'] = $est_remaining;
                $t['predicted_free_at'] = date('H:i', $now + ($est_remaining * 60));

                // Session Duration Countdown (Hamam / Private Spa / VIP Loca / Süreli Seans)
                if ($t['duration_mode'] === 'fixed_duration') {
                    $session_left = max(0, $t['session_duration_minutes'] - $elapsed_mins);
                    $t['session_remaining_mins'] = $session_left;
                    $t['session_is_expired'] = ($session_left === 0);
                } else {
                    $t['session_remaining_mins'] = null;
                    $t['session_is_expired'] = false;
                }
            } else {
                $t['minutes_seated'] = 0;
                $t['aging_stage'] = ($t['status'] === 'cleaning') ? 'dirty_busboy' : 'available';
                $t['aging_color'] = ($t['status'] === 'cleaning') ? '#64748B' : '#22C55E';
                $t['aging_label'] = ($t['status'] === 'cleaning') ? 'Deberasaj Bekliyor' : 'Müsait';
                $t['predicted_turnaround_mins'] = 0;
                $t['predicted_remaining_mins'] = 0;
                $t['predicted_free_at'] = null;
                $t['session_remaining_mins'] = null;
                $t['session_is_expired'] = false;
            }

            // Defaults for shape & rotation
            if (empty($t['shape'])) {
                $t['shape'] = 'rectangle';
            }
            $t['rotation'] = (int) ($t['rotation'] ?? 0);
            $t['is_vip_only'] = (int) ($t['is_vip_only'] ?? 0);
            $t['min_spend'] = (float) ($t['min_spend'] ?? 0.00);
        }
        unset($t);

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
     * Update full kroki attributes (position, size, shape, rotation, VIP flag, min spend).
     */
    public function update_table_kroki(int $table_id, array $data): bool
    {
        $allowed = ['pos_x', 'pos_y', 'width', 'height', 'shape', 'rotation', 'is_vip_only', 'min_spend', 'assigned_server_id', 'section', 'capacity'];
        $update = array_intersect_key($data, array_flip($allowed));
        $update['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('restaurant_tables', $update, ['id' => $table_id]);
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
        } elseif ($status === 'available') {
            $data['current_id_adisyons'] = null;
            $data['current_id_reservations'] = null;
            $data['seated_at'] = null;
            $data['waiter_call_status'] = 'none';
            $data['waiter_call_time'] = null;
            $data['waiter_call_note'] = null;
        } elseif ($status === 'cleaning') {
            $data['current_id_adisyons'] = null;
            $data['current_id_reservations'] = null;
            $data['last_cleaned_at'] = date('Y-m-d H:i:s');
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
            if (empty($data['qr_token'])) {
                $data['qr_token'] = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $data['section'] ?? 'M'), 0, 4) . '-M' . $data['table_number']);
            }
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
     * Look up table by QR token.
     */
    public function get_table_by_token(string $token): ?array
    {
        return $this->db
            ->get_where('restaurant_tables', ['qr_token' => $token, 'is_active' => 1])
            ->row_array();
    }

    /**
     * Ensure a table has a permanent unique QR token.
     */
    public function ensure_table_qr_token(int $table_id): string
    {
        $table = $this->db->get_where('restaurant_tables', ['id' => $table_id])->row_array();
        if (!$table) {
            throw new InvalidArgumentException('Masa bulunamadı.');
        }

        if (!empty($table['qr_token'])) {
            return $table['qr_token'];
        }

        $sec_clean = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $table['section'] ?? 'M'), 0, 4));
        $token = $sec_clean . '-M' . $table['table_number'];

        $existing = $this->db->get_where('restaurant_tables', ['qr_token' => $token, 'id !=' => $table_id])->num_rows();
        if ($existing > 0) {
            $token .= '-' . substr(bin2hex(random_bytes(2)), 0, 3);
        }

        $this->db->update('restaurant_tables', ['qr_token' => $token], ['id' => $table_id]);
        return $token;
    }

    /**
     * Combine two tables for large parties (Table Pushing).
     */
    public function combine_tables(int $master_table_id, int $slave_table_id): bool
    {
        $this->db->update('restaurant_tables', [
            'combined_with_table_id' => $master_table_id,
            'status' => 'seated',
        ], ['id' => $slave_table_id]);

        return true;
    }

    /**
     * Split combined tables back to individual tables.
     */
    public function split_tables(int $table_id): bool
    {
        $this->db->update('restaurant_tables', ['combined_with_table_id' => null], ['id' => $table_id]);
        $this->db->update('restaurant_tables', ['combined_with_table_id' => null], ['combined_with_table_id' => $table_id]);
        return true;
    }

    /**
     * Transfer table (move active adisyon, reservations & tickets from Table A to Table B).
     */
    public function transfer_table(int $source_table_id, int $target_table_id, ?int $server_id = null): array
    {
        $src = $this->db->get_where('restaurant_tables', ['id' => $source_table_id])->row_array();
        $dst = $this->db->get_where('restaurant_tables', ['id' => $target_table_id])->row_array();

        if (!$src || !$dst) {
            throw new InvalidArgumentException('Geçersiz masa seçimi.');
        }

        if (empty($src['current_id_adisyons'])) {
            throw new InvalidArgumentException('Kaynak masada taşınacak açık bir adisyon bulunmuyor.');
        }

        if (!empty($dst['current_id_adisyons'])) {
            throw new InvalidArgumentException('Hedef masada zaten açık bir adisyon mevcut. Lütfen boş bir masa seçin.');
        }

        $this->db->trans_start();
        try {
            $adisyon_id = (int) $src['current_id_adisyons'];

            // 1. Move adisyon
            $this->db->update('adisyons', ['id_restaurant_tables' => $target_table_id], ['id' => $adisyon_id]);

            // 2. Move kitchen tickets
            $this->db->update('kitchen_orders', ['id_restaurant_tables' => $target_table_id], ['id_adisyons' => $adisyon_id]);

            // 3. Move table calls
            $this->db->update('restaurant_table_calls', ['id_restaurant_tables' => $target_table_id], [
                'id_restaurant_tables' => $source_table_id,
                'status' => 'pending',
            ]);

            // 4. Update destination table
            $this->db->update('restaurant_tables', [
                'current_id_adisyons' => $adisyon_id,
                'status' => 'dining',
                'seated_at' => $src['seated_at'] ?: date('Y-m-d H:i:s'),
                'waiter_call_status' => $src['waiter_call_status'],
                'waiter_call_note' => $src['waiter_call_note'],
                'waiter_call_time' => $src['waiter_call_time'],
                'id_users_server' => $server_id ?: $dst['id_users_server'] ?: $src['id_users_server'],
            ], ['id' => $target_table_id]);

            // 5. Free source table to cleaning
            $this->db->update('restaurant_tables', [
                'current_id_adisyons' => null,
                'status' => 'cleaning',
                'seated_at' => null,
                'waiter_call_status' => 'none',
                'waiter_call_note' => null,
                'waiter_call_time' => null,
            ], ['id' => $source_table_id]);

            $this->db->trans_complete();

            return [
                'status' => 'success',
                'source_table_id' => $source_table_id,
                'target_table_id' => $target_table_id,
                'adisyon_id' => $adisyon_id,
            ];
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw new RuntimeException('Masa transferi başarısız: ' . $e->getMessage());
        }
    }

    /* -------------------------------------------------------------------------
     * 2. KROKI ARCHITECTURAL LAYOUT ELEMENTS (Duvar, Bar, Sahne, Pencere vb.)
     * ------------------------------------------------------------------------- */

    /**
     * Get architectural elements for the visual floor plan.
     */
    public function get_layout_elements(?string $section = null): array
    {
        $this->db->where('is_active', 1);
        if (!empty($section) && $section !== 'all') {
            $this->db->where('section', $section);
        }
        return $this->db->order_by('id ASC')->get('restaurant_layout_elements')->result_array();
    }

    /**
     * Create or update architectural layout element.
     */
    public function save_layout_element(array $data): int
    {
        $allowed = ['id', 'section', 'element_type', 'label', 'pos_x', 'pos_y', 'width', 'height', 'rotation', 'style_json', 'is_active', 'created_at', 'updated_at'];
        $insert_data = [];
        $extra_styles = [];

        foreach ($data as $key => $val) {
            if (in_array($key, $allowed)) {
                $insert_data[$key] = $val;
            } else {
                $extra_styles[$key] = $val;
            }
        }

        if (!empty($extra_styles)) {
            $existing_styles = !empty($insert_data['style_json']) ? json_decode($insert_data['style_json'], true) : [];
            if (!is_array($existing_styles)) $existing_styles = [];
            $insert_data['style_json'] = json_encode(array_merge($existing_styles, $extra_styles));
        }

        if (empty($insert_data['id'])) {
            $insert_data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('restaurant_layout_elements', $insert_data);
            return $this->db->insert_id();
        } else {
            $insert_data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('restaurant_layout_elements', $insert_data, ['id' => $insert_data['id']]);
            return (int) $insert_data['id'];
        }
    }

    /**
     * Delete an architectural layout element.
     */
    public function delete_layout_element(int $id): bool
    {
        return $this->db->update('restaurant_layout_elements', ['is_active' => 0], ['id' => $id]);
    }

    /* -------------------------------------------------------------------------
     * 3. MASA MÜDAVİMLİĞİ & 360° MİSAFİR ZEKASI (PATRONAGE ENGINE)
     * ------------------------------------------------------------------------- */

    /**
     * Get a customer's detailed dining profile (Müdavimlik Kartı).
     */
    public function get_customer_dining_profile(int $customer_id): ?array
    {
        $profile = $this->db
            ->select('cdp.*, 
                      rt.table_number as fav_table_number, rt.name as fav_table_name, rt.section as fav_table_section,
                      u.first_name, u.last_name, u.phone_number, u.email,
                      srv.first_name as server_first_name, srv.last_name as server_last_name')
            ->from('customer_dining_profiles cdp')
            ->join('users u', 'u.id = cdp.id_users_customer', 'left')
            ->join('restaurant_tables rt', 'rt.id = cdp.favorite_table_id', 'left')
            ->join('users srv', 'srv.id = cdp.favorite_server_id', 'left')
            ->where('cdp.id_users_customer', $customer_id)
            ->get()
            ->row_array();

        if ($profile) {
            $profile['favorite_dishes'] = !empty($profile['favorite_dishes_json']) ? json_decode($profile['favorite_dishes_json'], true) : [];
            $profile['favorite_drinks'] = !empty($profile['favorite_drinks_json']) ? json_decode($profile['favorite_drinks_json'], true) : [];
        }

        return $profile;
    }

    /**
     * Save or update a dining profile.
     */
    public function save_customer_dining_profile(array $data): int
    {
        if (empty($data['id_users_customer'])) {
            throw new InvalidArgumentException('Müşteri ID gereklidir.');
        }

        $existing = $this->db->get_where('customer_dining_profiles', ['id_users_customer' => $data['id_users_customer']])->row_array();

        if (is_array($data['favorite_dishes_json'] ?? null)) {
            $data['favorite_dishes_json'] = json_encode($data['favorite_dishes_json']);
        }
        if (is_array($data['favorite_drinks_json'] ?? null)) {
            $data['favorite_drinks_json'] = json_encode($data['favorite_drinks_json']);
        }

        if ($existing) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('customer_dining_profiles', $data, ['id' => $existing['id']]);
            return (int) $existing['id'];
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('customer_dining_profiles', $data);
            return $this->db->insert_id();
        }
    }

    /**
     * Retrieve all regular patrons with optional tier filter.
     */
    public function get_all_patron_profiles(?string $tier = null, ?string $search = null): array
    {
        $this->db
            ->select('cdp.*, u.first_name, u.last_name, u.phone_number, u.email,
                      rt.table_number as fav_table_number, rt.name as fav_table_name, rt.section as fav_table_section')
            ->from('customer_dining_profiles cdp')
            ->join('users u', 'u.id = cdp.id_users_customer', 'left')
            ->join('restaurant_tables rt', 'rt.id = cdp.favorite_table_id', 'left');

        if (!empty($tier) && $tier !== 'all') {
            $this->db->where('cdp.patronage_tier', $tier);
        }

        if (!empty($search)) {
            $this->db->group_start()
                ->like('u.first_name', $search)
                ->or_like('u.last_name', $search)
                ->or_like('u.phone_number', $search)
                ->group_end();
        }

        return $this->db
            ->order_by('cdp.total_visits DESC, cdp.total_spend DESC')
            ->get()
            ->result_array();
    }

    /**
     * Automatically update customer patronage metrics upon visit completion.
     */
    public function sync_guest_visit_to_profile(int $customer_id, float $spend_amount, int $table_id): void
    {
        $existing = $this->db->get_where('customer_dining_profiles', ['id_users_customer' => $customer_id])->row_array();
        $table = $this->db->get_where('restaurant_tables', ['id' => $table_id])->row_array();

        if ($existing) {
            $new_visits = ((int) $existing['total_visits']) + 1;
            $new_total_spend = ((float) $existing['total_spend']) + $spend_amount;
            $new_avg_spend = round($new_total_spend / max(1, $new_visits), 2);

            // Tier promotion logic
            $tier = $existing['patronage_tier'];
            if ($new_visits >= 15 || $new_total_spend >= 30000) {
                $tier = 'elite';
            } elseif ($new_visits >= 6 || $new_total_spend >= 10000) {
                $tier = 'vip_regular';
            } elseif ($new_visits >= 2) {
                $tier = 'regular';
            }

            $update = [
                'total_visits' => $new_visits,
                'total_spend' => $new_total_spend,
                'avg_spend' => $new_avg_spend,
                'last_visit_at' => date('Y-m-d H:i:s'),
                'patronage_tier' => $tier,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            // If favorite table isn't set, default to current table
            if (empty($existing['favorite_table_id']) && $table) {
                $update['favorite_table_id'] = $table_id;
                $update['favorite_section'] = $table['section'];
            }

            $this->db->update('customer_dining_profiles', $update, ['id' => $existing['id']]);
        } else {
            $this->db->insert('customer_dining_profiles', [
                'id_users_customer' => $customer_id,
                'favorite_table_id' => $table_id,
                'favorite_section' => $table['section'] ?? 'Ana Salon',
                'total_visits' => 1,
                'total_spend' => $spend_amount,
                'avg_spend' => $spend_amount,
                'patronage_tier' => 'first_timer',
                'last_visit_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Check if a table is reserved or protected by VIP / Regular Guard rules.
     */
    public function check_table_guard(int $table_id, ?string $datetime = null, ?int $customer_id = null): array
    {
        $table = $this->db->get_where('restaurant_tables', ['id' => $table_id])->row_array();
        if (!$table) {
            return ['allowed' => false, 'reason' => 'Masa bulunamadı.'];
        }

        // 1. If table is marked VIP only
        if (!empty($table['is_vip_only'])) {
            if (!$customer_id) {
                return ['allowed' => false, 'reason' => 'Bu masa özel VIP / Müdavim masası olarak ayrılmıştır.'];
            }

            $profile = $this->get_customer_dining_profile($customer_id);
            $tier = $profile['patronage_tier'] ?? 'first_timer';

            if (!in_array($tier, ['regular', 'vip_regular', 'elite'])) {
                return ['allowed' => false, 'reason' => 'Bu masa yalnızca müdavim ve VIP misafirlerimiz için ayrılmıştır.'];
            }
        }

        return ['allowed' => true, 'reason' => 'Müsait'];
    }

    /* -------------------------------------------------------------------------
     * 4. SMART TABLE ALLOCATION (AKILLI OTOMATİK MASA ATAMA MOTORU)
     * ------------------------------------------------------------------------- */

    /**
     * Automatically assigns the best table using party size, patronage preference, and section balance.
     */
    public function auto_assign_table(int $party_size, ?string $preferred_section = null, ?int $customer_id = null, ?string $datetime = null): ?array
    {
        $all_tables = $this->get_tables_with_status();
        $date = !empty($datetime) ? date('Y-m-d', strtotime($datetime)) : date('Y-m-d');

        // Check if customer is a regular with a favorite table
        $patron_profile = null;
        if ($customer_id) {
            $patron_profile = $this->get_customer_dining_profile($customer_id);
        }

        // 1. If regular has a favorite table, check if it's available
        if ($patron_profile && !empty($patron_profile['favorite_table_id'])) {
            $fav_table_id = (int) $patron_profile['favorite_table_id'];
            $fav_table = array_filter($all_tables, fn($t) => (int)$t['id'] === $fav_table_id);
            $fav_table = !empty($fav_table) ? reset($fav_table) : null;

            if ($fav_table && (int)$fav_table['capacity'] >= $party_size) {
                // Check if table has conflicting reservation on that date
                $conflict = $this->db
                    ->where('id_restaurant_tables', $fav_table_id)
                    ->where('DATE(reservation_datetime)', $date)
                    ->where('status !=', 'cancelled')
                    ->get('restaurant_reservations')
                    ->num_rows();

                if ($conflict === 0 && in_array($fav_table['status'], ['available', 'cleaning'])) {
                    $fav_table['is_patron_favorite'] = true;
                    return $fav_table;
                }
            }
        }

        // 2. Candidate tables: capacity >= party_size
        $candidates = array_filter($all_tables, function($t) use ($party_size, $preferred_section, $customer_id, $patron_profile) {
            if ((int)$t['capacity'] < $party_size) {
                return false;
            }
            if ($t['status'] === 'seated' || $t['status'] === 'dining' || $t['status'] === 'bill_requested') {
                return false;
            }
            // VIP table guard
            if (!empty($t['is_vip_only'])) {
                $tier = $patron_profile['patronage_tier'] ?? 'first_timer';
                if (!in_array($tier, ['regular', 'vip_regular', 'elite'])) {
                    return false;
                }
            }
            return true;
        });

        if (empty($candidates)) {
            // Fallback: check all available tables regardless of VIP flag
            $candidates = array_filter($all_tables, fn($t) => (int)$t['capacity'] >= $party_size && in_array($t['status'], ['available', 'cleaning']));
        }

        if (empty($candidates)) {
            return null;
        }

        // 3. Sort candidates:
        // Priority 1: Preferred section match
        // Priority 2: Tightest capacity fit (e.g. 2 guests get 2-top or 4-top, not 8-top)
        // Priority 3: Table number order
        $pref_sec = $preferred_section ?: ($patron_profile['favorite_section'] ?? null);

        usort($candidates, function($a, $b) use ($party_size, $pref_sec) {
            $a_sec_match = ($pref_sec && $a['section'] === $pref_sec) ? 1 : 0;
            $b_sec_match = ($pref_sec && $b['section'] === $pref_sec) ? 1 : 0;

            if ($a_sec_match !== $b_sec_match) {
                return $b_sec_match - $a_sec_match; // matched section first
            }

            $a_cap_diff = abs((int)$a['capacity'] - $party_size);
            $b_cap_diff = abs((int)$b['capacity'] - $party_size);

            if ($a_cap_diff !== $b_cap_diff) {
                return $a_cap_diff - $b_cap_diff; // tightest fit first
            }

            return (int)$a['table_number'] - (int)$b['table_number'];
        });

        $best = reset($candidates);
        $best['is_patron_favorite'] = false;
        return $best;
    }

    /* -------------------------------------------------------------------------
     * 5. RESERVATIONS & EXPERIENCES
     * ------------------------------------------------------------------------- */

    /**
     * Get restaurant reservations with filtering.
     */
    public function get_reservations(?string $date = null, ?string $status = null): array
    {
        $this->db
            ->select('rr.*, 
                      c.first_name as guest_first_name, c.last_name as guest_last_name, c.phone_number as guest_phone, c.email as guest_email,
                      rt.table_number, rt.name as table_name, rt.section as table_section,
                      re.title as experience_title,
                      cdp.patronage_tier, cdp.total_visits, cdp.service_notes as guest_notes')
            ->from('restaurant_reservations rr')
            ->join('users c', 'c.id = rr.id_users_customer', 'left')
            ->join('restaurant_tables rt', 'rt.id = rr.id_restaurant_tables', 'left')
            ->join('restaurant_experiences re', 're.id = rr.id_restaurant_experiences', 'left')
            ->join('customer_dining_profiles cdp', 'cdp.id_users_customer = c.id', 'left');

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
     * Update reservation status (confirmed, seated, cancelled, no_show).
     */
    public function update_reservation_status(int $id, string $status): void
    {
        $this->db->update('restaurant_reservations', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    /**
     * Get dining experiences.
     */
    public function get_experiences(): array
    {
        return $this->db
            ->get_where('restaurant_experiences', ['is_active' => 1])
            ->result_array();
    }

    /**
     * Create or update dining experience.
     */
    public function save_experience(array $data): int
    {
        if (empty($data['title'])) {
            throw new InvalidArgumentException('Deneyim başlığı zorunludur.');
        }

        if (empty($data['id'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('restaurant_experiences', $data);
            return $this->db->insert_id();
        } else {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('restaurant_experiences', $data, ['id' => $data['id']]);
            return (int) $data['id'];
        }
    }

    /* -------------------------------------------------------------------------
     * 6. KITCHEN DISPLAY SYSTEM (KDS) & ORDERS
     * ------------------------------------------------------------------------- */

    /**
     * Retrieve active orders for KDS screen.
     */
    public function get_active_kitchen_orders(string $station = 'all'): array
    {
        $this->db
            ->select('ko.*, rt.table_number, rt.name as table_name, rt.section, ad.adisyon_number')
            ->from('kitchen_orders ko')
            ->join('restaurant_tables rt', 'rt.id = ko.id_restaurant_tables', 'left')
            ->join('adisyons ad', 'ad.id = ko.id_adisyons', 'left')
            ->where_in('ko.status', ['new', 'preparing', 'ready']);

        if ($station !== 'all') {
            $this->db->where('ko.station', $station);
        }

        return $this->db
            ->order_by('ko.ordered_at ASC')
            ->get()
            ->result_array();
    }

    /**
     * Create new kitchen order ticket.
     */
    public function create_kitchen_order(array $data): int
    {
        $data['ordered_at'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? 'new';
        $this->db->insert('kitchen_orders', $data);
        return $this->db->insert_id();
    }

    /**
     * Update kitchen order status.
     */
    public function update_kitchen_order_status(int $order_id, string $status): bool
    {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($status === 'preparing') {
            $data['started_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'ready') {
            $data['ready_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'served') {
            $data['served_at'] = date('Y-m-d H:i:s');
        }

        return $this->db->update('kitchen_orders', $data, ['id' => $order_id]);
    }

    /* -------------------------------------------------------------------------
     * 7. QR DIGITAL MENU & CATEGORIES
     * ------------------------------------------------------------------------- */

    /**
     * Retrieve all active menu categories.
     */
    public function get_menu_categories(): array
    {
        return $this->db
            ->order_by('display_order ASC, name ASC')
            ->get_where('restaurant_menu_categories', ['is_active' => 1])
            ->result_array();
    }

    /**
     * Create or update menu category.
     */
    public function save_menu_category(array $data): int
    {
        if (empty($data['id'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('restaurant_menu_categories', $data);
            return $this->db->insert_id();
        } else {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('restaurant_menu_categories', $data, ['id' => $data['id']]);
            return (int) $data['id'];
        }
    }

    /**
     * Retrieve menu items with optional category and dietary filters.
     */
    public function get_menu_items(?int $category_id = null, ?string $dietary = null, bool $only_available = true): array
    {
        $this->db
            ->select('rmi.*, rmc.name as category_name, rmc.slug as category_slug, rmc.icon as category_icon')
            ->from('restaurant_menu_items rmi')
            ->join('restaurant_menu_categories rmc', 'rmc.id = rmi.id_categories', 'left');

        if ($only_available) {
            $this->db->where('rmi.is_available', 1);
        }

        if ($category_id !== null && $category_id > 0) {
            $this->db->where('rmi.id_categories', $category_id);
        }

        if (!empty($dietary) && $dietary !== 'all') {
            $this->db->like('rmi.dietary_badges', $dietary);
        }

        $items = $this->db
            ->order_by('rmi.display_order ASC, rmi.name ASC')
            ->get()
            ->result_array();

        foreach ($items as &$item) {
            $item['options'] = !empty($item['options_json']) ? json_decode($item['options_json'], true) : [];
            $item['dietary_array'] = !empty($item['dietary_badges']) ? array_map('trim', explode(',', $item['dietary_badges'])) : [];
            $item['allergens_array'] = !empty($item['allergens']) ? array_map('trim', explode(',', $item['allergens'])) : [];
        }
        unset($item);

        return $items;
    }

    /**
     * Get single menu item.
     */
    public function get_menu_item(int $id): ?array
    {
        $item = $this->db
            ->select('rmi.*, rmc.name as category_name, rmc.slug as category_slug')
            ->from('restaurant_menu_items rmi')
            ->join('restaurant_menu_categories rmc', 'rmc.id = rmi.id_categories', 'left')
            ->where('rmi.id', $id)
            ->get()
            ->row_array();

        if ($item) {
            $item['options'] = !empty($item['options_json']) ? json_decode($item['options_json'], true) : [];
            $item['dietary_array'] = !empty($item['dietary_badges']) ? array_map('trim', explode(',', $item['dietary_badges'])) : [];
            $item['allergens_array'] = !empty($item['allergens']) ? array_map('trim', explode(',', $item['allergens'])) : [];
        }

        return $item;
    }

    /**
     * Create or update menu item.
     */
    public function save_menu_item(array $data): int
    {
        if (is_array($data['options_json'] ?? null)) {
            $data['options_json'] = json_encode($data['options_json']);
        }

        if (empty($data['id'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('restaurant_menu_items', $data);
            return $this->db->insert_id();
        } else {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('restaurant_menu_items', $data, ['id' => $data['id']]);
            return (int) $data['id'];
        }
    }

    /* -------------------------------------------------------------------------
     * 8. TABLE CALLS (GARSON & HESAP ÇAĞRILARI)
     * ------------------------------------------------------------------------- */

    /**
     * Record a call from a table (garson, hesap, su, servis vb.).
     */
    public function call_waiter(int $table_id, string $call_type = 'waiter', ?string $note = null, ?string $payment_pref = null): int
    {
        $now = date('Y-m-d H:i:s');
        $call_data = [
            'id_restaurant_tables' => $table_id,
            'call_type' => $call_type,
            'note' => $note,
            'payment_method_preference' => $payment_pref,
            'status' => 'pending',
            'created_at' => $now,
        ];

        $this->db->insert('restaurant_table_calls', $call_data);
        $call_id = $this->db->insert_id();

        $status_map = [
            'waiter' => 'waiter_called',
            'bill' => 'bill_requested',
            'water' => 'waiter_called',
            'napkin' => 'waiter_called',
        ];
        $tbl_call_status = $status_map[$call_type] ?? 'waiter_called';

        $this->db->update('restaurant_tables', [
            'waiter_call_status' => $tbl_call_status,
            'waiter_call_time' => $now,
            'waiter_call_note' => $note ?: ($call_type === 'bill' ? 'Hesap İstendi (' . ($payment_pref ?: 'Nakit/Kart') . ')' : 'Garson Çağrıldı'),
        ], ['id' => $table_id]);

        return $call_id;
    }

    /**
     * Resolve pending calls for a table.
     */
    public function resolve_table_calls(int $table_id): bool
    {
        $now = date('Y-m-d H:i:s');
        $this->db->update('restaurant_table_calls', [
            'status' => 'completed',
            'resolved_at' => $now,
        ], [
            'id_restaurant_tables' => $table_id,
            'status' => 'pending',
        ]);

        $this->db->update('restaurant_tables', [
            'waiter_call_status' => 'none',
            'waiter_call_time' => null,
            'waiter_call_note' => null,
        ], ['id' => $table_id]);

        return true;
    }

    /**
     * Get active waiter calls for waiter & POS screens.
     */
    public function get_active_table_calls(): array
    {
        return $this->db
            ->select('rtc.*, rt.table_number, rt.name as table_name, rt.section')
            ->from('restaurant_table_calls rtc')
            ->join('restaurant_tables rt', 'rt.id = rtc.id_restaurant_tables', 'left')
            ->where('rtc.status', 'pending')
            ->order_by('rtc.created_at ASC')
            ->get()
            ->result_array();
    }

    /* -------------------------------------------------------------------------
     * 9. SELF-ORDERING ENGINE WITH LOYALTY & MEMBERSHIP INTEGRATION
     * ------------------------------------------------------------------------- */

    /**
     * Submit an order (self-order from table QR or waiter handheld).
     */
    public function submit_table_order(
        int $table_id,
        array $cart_items,
        ?string $customer_phone = null,
        ?string $customer_name = null,
        ?int $server_id = null
    ): array {
        if (empty($cart_items)) {
            throw new InvalidArgumentException('Sipariş sepeti boş olamaz.');
        }

        $table = $this->db->get_where('restaurant_tables', ['id' => $table_id])->row_array();
        if (!$table) {
            throw new InvalidArgumentException('Geçersiz masa.');
        }

        $this->load->model('adisyons_model');
        $this->load->model('customers_model');
        $this->load->model('customer_memberships_model');
        $this->load->model('loyalty_points_model');

        $this->db->trans_start();
        try {
            $customer_id = null;
            $active_membership = null;
            $membership_discount_rate = 0.00;

            // 1. Identify or register customer via phone if provided
            if (!empty($customer_phone)) {
                $search_phone = preg_replace('/[^0-9]/', '', $customer_phone);
                $customers = $this->customers_model->get();
                $existing_customer = null;

                foreach ($customers as $c) {
                    $c_phone = preg_replace('/[^0-9]/', '', $c['phone_number'] ?? '');
                    if (!empty($c_phone) && !empty($search_phone) && (str_ends_with($c_phone, $search_phone) || str_ends_with($search_phone, $c_phone))) {
                        $existing_customer = $c;
                        break;
                    }
                }

                if ($existing_customer) {
                    $customer_id = (int) $existing_customer['id'];
                } else {
                    $parts = explode(' ', trim($customer_name ?: 'Değerli Misafir'));
                    $first_name = array_shift($parts);
                    $last_name = !empty($parts) ? implode(' ', $parts) : 'Misafir';

                    $customer_id = $this->customers_model->save([
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'phone_number' => $customer_phone,
                    ]);
                }

                // Check active memberships for discount perk
                if ($customer_id) {
                    $memberships = $this->db
                        ->select('cm.*, mp.name as plan_name, mp.discount_percent, mp.perks_description')
                        ->from('customer_memberships cm')
                        ->join('membership_plans mp', 'mp.id = cm.id_membership_plans', 'left')
                        ->where('cm.id_users_customer', $customer_id)
                        ->where('cm.status', 'active')
                        ->where('cm.current_period_end >=', date('Y-m-d H:i:s'))
                        ->get()
                        ->result_array();

                    if (!empty($memberships)) {
                        $active_membership = $memberships[0];
                        $membership_discount_rate = (float) ($active_membership['discount_percent'] ?? 0.00);
                    }
                }
            }

            // 2. Get or create active adisyon for this table
            $adisyon = $this->adisyons_model->get_or_create_for_table($table_id, $customer_id, $server_id);
            $adisyon_id = (int) $adisyon['id'];

            // 3. Process each cart item, add to adisyon and dispatch to kitchen
            $now = date('Y-m-d H:i:s');
            $dispatched_orders = [];

            foreach ($cart_items as $item) {
                $name = trim($item['name'] ?? 'Ürün');
                $price = (float) ($item['price'] ?? 0.00);
                $qty = max(1, (int) ($item['quantity'] ?? 1));
                $station = $item['station'] ?? 'kitchen';
                $notes = $item['notes'] ?? null;
                $options_summary = '';

                if (!empty($item['selected_options']) && is_array($item['selected_options'])) {
                    $opt_parts = [];
                    foreach ($item['selected_options'] as $opt_name => $opt_val) {
                        $opt_parts[] = $opt_name . ': ' . (is_array($opt_val) ? implode(', ', $opt_val) : $opt_val);
                    }
                    $options_summary = implode(' | ', $opt_parts);
                }

                $full_note = trim(($options_summary ? '[' . $options_summary . '] ' : '') . ($notes ?: ''));

                // Add item to adisyon
                $item_record = [
                    'item_type' => 'product',
                    'name' => $name,
                    'unit_price' => $price,
                    'quantity' => $qty,
                    'discount_amount' => 0.00,
                    'tax_rate' => 10.00,
                    'notes' => $full_note ?: null,
                ];

                $this->adisyons_model->add_item($adisyon_id, $item_record);

                // Dispatch order to Kitchen Display System (KDS)
                $kds_id = $this->create_kitchen_order([
                    'id_adisyons' => $adisyon_id,
                    'id_restaurant_tables' => $table_id,
                    'station' => $station,
                    'item_name' => $name,
                    'quantity' => $qty,
                    'notes' => $full_note ?: null,
                ]);

                $dispatched_orders[] = [
                    'kitchen_order_id' => $kds_id,
                    'item_name' => $name,
                    'quantity' => $qty,
                    'station' => $station,
                ];
            }

            // 4. Apply membership discount if eligible
            $updated_adisyon = $this->adisyons_model->find($adisyon_id);
            $membership_discount_amount = 0.00;

            if ($membership_discount_rate > 0) {
                $subtotal = (float) $updated_adisyon['subtotal'];
                $membership_discount_amount = round($subtotal * ($membership_discount_rate / 100), 2);

                $this->db->update('adisyons', [
                    'membership_discount' => $membership_discount_amount,
                    'id_customer_memberships' => $active_membership ? (int) $active_membership['id'] : null,
                    'discount_amount' => ((float) $updated_adisyon['discount_amount']) + $membership_discount_amount,
                ], ['id' => $adisyon_id]);

                $this->adisyons_model->recompute_totals($adisyon_id);
                $updated_adisyon = $this->adisyons_model->find($adisyon_id);
            }

            // 5. Calculate potential loyalty points earned (5% of order subtotal, 10 pts per 1 TL)
            $points_to_earn = (int) round(((float) $updated_adisyon['total_amount']) * 0.05 * 10);
            $this->db->update('adisyons', [
                'loyalty_points_earned' => $points_to_earn,
            ], ['id' => $adisyon_id]);

            // 6. Update table status to dining
            $this->db->update('restaurant_tables', [
                'status' => 'dining',
                'seated_at' => $table['seated_at'] ?: $now,
            ], ['id' => $table_id]);

            $this->db->trans_complete();

            return [
                'status' => 'success',
                'success' => true,
                'table_id' => $table_id,
                'adisyon_id' => $adisyon_id,
                'adisyon_number' => $updated_adisyon['adisyon_number'],
                'customer_id' => $customer_id,
                'active_membership' => $active_membership,
                'membership_discount' => $membership_discount_amount,
                'potential_loyalty_points' => $points_to_earn,
                'total_amount' => $updated_adisyon['total_amount'],
                'dispatched_items_count' => count($dispatched_orders),
                'dispatched_orders' => $dispatched_orders,
            ];
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw new RuntimeException('Sipariş kaydedilemedi: ' . $e->getMessage());
        }
    }

    /**
     * Get live dining experience status for customer screen / portal.
     */
    public function get_table_live_experience(int $table_id): array
    {
        $table = $this->db
            ->select('rt.*, u.first_name as server_first_name, u.last_name as server_last_name')
            ->from('restaurant_tables rt')
            ->join('users u', 'u.id = rt.id_users_server', 'left')
            ->where('rt.id', $table_id)
            ->get()
            ->row_array();

        if (!$table) {
            throw new InvalidArgumentException('Masa bulunamadı.');
        }

        $adisyon = null;
        $customer = null;
        $active_membership = null;
        $patron_profile = null;
        $kitchen_orders = [];

        if (!empty($table['current_id_adisyons'])) {
            $this->load->model('adisyons_model');
            $adisyon = $this->adisyons_model->find((int) $table['current_id_adisyons']);

            // Get live kitchen items status
            $kitchen_orders = $this->db
                ->get_where('kitchen_orders', ['id_adisyons' => $table['current_id_adisyons']])
                ->result_array();

            if (!empty($adisyon['id_users_customer'])) {
                $this->load->model('customers_model');
                try {
                    $customer = $this->customers_model->find((int) $adisyon['id_users_customer']);
                } catch (Throwable $e) {
                    $customer = null;
                }

                if ($customer) {
                    $this->load->model('loyalty_points_model');
                    $customer['loyalty_balance'] = $this->loyalty_points_model->get_balance((int) $customer['id']);

                    // Active membership
                    $active_membership = $this->db
                        ->select('cm.*, mp.name as plan_name, mp.discount_percent, mp.perks_description')
                        ->from('customer_memberships cm')
                        ->join('membership_plans mp', 'mp.id = cm.id_membership_plans', 'left')
                        ->where('cm.id_users_customer', (int) $customer['id'])
                        ->where('cm.status', 'active')
                        ->get()
                        ->row_array();

                    // Patronage profile
                    $patron_profile = $this->get_customer_dining_profile((int) $customer['id']);
                }
            }
        }

        // Active waiter calls
        $pending_call = $this->db
            ->get_where('restaurant_table_calls', [
                'id_restaurant_tables' => $table_id,
                'status' => 'pending',
            ])
            ->row_array();

        // Calculate dining duration
        $minutes_dining = 0;
        if (!empty($table['seated_at'])) {
            $minutes_dining = max(0, round((time() - strtotime($table['seated_at'])) / 60));
        }

        // Compute overall stepper status for customer live experience
        $stepper_status = 'received';
        $kitchen_summary = 'Siparişiniz alındı';
        if (!empty($kitchen_orders)) {
            $has_preparing = false;
            $has_ready = false;
            $all_served = true;
            $all_ready = true;
            foreach ($kitchen_orders as $ko) {
                if ($ko['status'] === 'preparing') {
                    $has_preparing = true;
                    $all_ready = false;
                    $all_served = false;
                } elseif ($ko['status'] === 'ready') {
                    $has_ready = true;
                    $all_served = false;
                } elseif ($ko['status'] === 'served') {
                    // served
                } else {
                    $all_ready = false;
                    $all_served = false;
                }
            }

            if ($all_served) {
                $stepper_status = 'served';
                $kitchen_summary = 'Tüm siparişleriniz masanıza servis edildi';
            } elseif ($has_ready || $all_ready) {
                $stepper_status = 'ready';
                $kitchen_summary = 'Siparişleriniz hazırlandı, masanıza getiriliyor';
            } elseif ($has_preparing) {
                $stepper_status = 'preparing';
                $kitchen_summary = 'Şeflerimiz siparişinizi özenle hazırlıyor';
            } else {
                $stepper_status = 'received';
                $kitchen_summary = 'Siparişiniz mutfağa iletildi';
            }
        }

        return [
            'table' => $table,
            'minutes_dining' => $minutes_dining,
            'adisyon' => $adisyon,
            'kitchen_orders' => $kitchen_orders,
            'customer' => $customer,
            'membership' => $active_membership,
            'patron_profile' => $patron_profile,
            'pending_call' => $pending_call,
            'stepper_status' => $stepper_status,
            'kitchen_summary' => $kitchen_summary,
            'loyalty_balance' => $customer['loyalty_balance'] ?? 0,
        ];
    }

    /**
     * Redeem customer loyalty points to pay down adisyon balance.
     * Rate: 10 points = 1.00 TL discount.
     */
    public function redeem_loyalty_points(int $adisyon_id, int $customer_id, int $points_to_redeem): array
    {
        if ($points_to_redeem <= 0) {
            throw new InvalidArgumentException('Kullanılacak puan 0\'dan büyük olmalıdır.');
        }

        $this->load->model('adisyons_model');
        $this->load->model('loyalty_points_model');

        $adisyon = $this->adisyons_model->find($adisyon_id);
        if (!$adisyon) {
            throw new InvalidArgumentException('Adisyon bulunamadı.');
        }

        $current_balance = $this->loyalty_points_model->get_balance($customer_id);

        if ($points_to_redeem > $current_balance) {
            throw new InvalidArgumentException("Yetersiz puan. Mevcut bakiye: {$current_balance} puan.");
        }

        // 10 puan = 1 TL
        $discount_value = round($points_to_redeem / 10, 2);
        $total_payable = (float) $adisyon['total_amount'];

        if ($discount_value > $total_payable) {
            $discount_value = $total_payable;
            $points_to_redeem = (int) ($discount_value * 10);
        }

        $this->db->trans_start();
        try {
            // Deduct points
            $this->loyalty_points_model->redeem($customer_id, $points_to_redeem, 'redeemed_order');

            // Link to adisyon
            $this->db->order_by('id', 'DESC')->limit(1)->update('loyalty_points', ['id_adisyons' => $adisyon_id], [
                'id_users_customer' => $customer_id,
            ]);

            // Apply loyalty discount on adisyon
            $new_loyalty_discount = ((float) ($adisyon['loyalty_discount'] ?? 0)) + $discount_value;
            $new_points_used = ((int) ($adisyon['loyalty_points_used'] ?? 0)) + $points_to_redeem;

            $this->db->update('adisyons', [
                'loyalty_discount' => $new_loyalty_discount,
                'loyalty_points_used' => $new_points_used,
                'discount_amount' => ((float) $adisyon['discount_amount']) + $discount_value,
            ], ['id' => $adisyon_id]);

            $this->adisyons_model->recompute_totals($adisyon_id);

            $this->db->trans_complete();

            $updated = $this->adisyons_model->find($adisyon_id);
            return [
                'status' => 'success',
                'points_redeemed' => $points_to_redeem,
                'discount_applied' => $discount_value,
                'remaining_points' => $current_balance - $points_to_redeem,
                'new_total' => $updated['total_amount'],
            ];
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw new RuntimeException('Puan kullanımı başarısız: ' . $e->getMessage());
        }
    }

    /**
     * Record a guest visit and sync to patron profile.
     */
    public function record_guest_visit(int $customer_id, float $total_amount, int $table_id = 0): void
    {
        $this->sync_guest_visit_to_profile($customer_id, $total_amount, $table_id);
    }

    /* -------------------------------------------------------------------------
     * 7. QR MENU SETTINGS & THEME CUSTOMIZER
     * ------------------------------------------------------------------------- */

    /**
     * Get restaurant QR menu customizer settings.
     */
    public function get_qr_settings(): array
    {
        if (!$this->db->table_exists('restaurant_qr_settings')) {
            return $this->get_default_qr_settings();
        }

        $row = $this->db->order_by('id', 'ASC')->limit(1)->get('restaurant_qr_settings')->row_array();
        if (!$row) {
            return $this->get_default_qr_settings();
        }

        return $row;
    }

    /**
     * Fallback default QR settings.
     */
    public function get_default_qr_settings(): array
    {
        return [
            'id' => 1,
            'theme_preset' => 'dark_gold',
            'primary_color' => '#D97706',
            'accent_color' => '#F59E0B',
            'background_mode' => 'dark',
            'font_family' => 'Poppins',
            'hero_title' => 'Hoş Geldiniz',
            'hero_subtitle' => 'Lezzetli anlar ve seçkin lezzetler sizi bekliyor.',
            'hero_banner_url' => '',
            'allow_self_order' => 1,
            'order_approval_mode' => 'direct',
            'enable_waiter_call' => 1,
            'enable_bill_request' => 1,
            'enable_tipping' => 1,
            'tip_options' => '5,10,15,20',
            'venue_duration_mode' => 'open_ended',
            'default_seat_duration_minutes' => 90,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Save QR menu settings.
     */
    public function save_qr_settings(array $data): bool
    {
        $clean = [
            'theme_preset' => $data['theme_preset'] ?? 'dark_gold',
            'primary_color' => $data['primary_color'] ?? '#D97706',
            'accent_color' => $data['accent_color'] ?? '#F59E0B',
            'background_mode' => $data['background_mode'] ?? 'dark',
            'font_family' => $data['font_family'] ?? 'Poppins',
            'hero_title' => !empty($data['hero_title']) ? trim((string) $data['hero_title']) : 'Hoş Geldiniz',
            'hero_subtitle' => !empty($data['hero_subtitle']) ? trim((string) $data['hero_subtitle']) : null,
            'hero_banner_url' => !empty($data['hero_banner_url']) ? trim((string) $data['hero_banner_url']) : null,
            'allow_self_order' => isset($data['allow_self_order']) ? (int) $data['allow_self_order'] : 1,
            'order_approval_mode' => in_array($data['order_approval_mode'] ?? '', ['direct', 'waiter_approval']) ? $data['order_approval_mode'] : 'direct',
            'enable_waiter_call' => isset($data['enable_waiter_call']) ? (int) $data['enable_waiter_call'] : 1,
            'enable_bill_request' => isset($data['enable_bill_request']) ? (int) $data['enable_bill_request'] : 1,
            'enable_tipping' => isset($data['enable_tipping']) ? (int) $data['enable_tipping'] : 1,
            'tip_options' => !empty($data['tip_options']) ? trim((string) $data['tip_options']) : '5,10,15,20',
            'venue_duration_mode' => in_array($data['venue_duration_mode'] ?? '', ['open_ended', 'fixed_duration', 'daily_pass', 'multi_pass']) ? $data['venue_duration_mode'] : 'open_ended',
            'default_seat_duration_minutes' => isset($data['default_seat_duration_minutes']) ? max(15, (int) $data['default_seat_duration_minutes']) : 90,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $existing = $this->db->order_by('id', 'ASC')->limit(1)->get('restaurant_qr_settings')->row_array();
        if ($existing) {
            return $this->db->update('restaurant_qr_settings', $clean, ['id' => $existing['id']]);
        }

        return $this->db->insert('restaurant_qr_settings', $clean);
    }

    /* -------------------------------------------------------------------------
     * 8. STAFF STATIONS & MULTI-STATION BRIGADE DE CUISINE
     * ------------------------------------------------------------------------- */

    /**
     * Standard list of Kitchen, Bar & Service Stations based on Turizm Ansiklopedisi & Otelcim.
     */
    public function get_kitchen_bar_stations(): array
    {
        return [
            // Mutfak İstasyonları (Brigade de Cuisine)
            'hot_sauce' => [
                'code' => 'hot_sauce',
                'name' => 'Sıcak & Sos Bölümü (Chef Saucier)',
                'group' => 'Mutfak',
                'color' => '#EF4444',
                'icon' => 'fa-fire',
                'description' => 'Sıcak yemekler, soslar, tava ve tencere yemekleri hazırlığı.',
            ],
            'grill_meat' => [
                'code' => 'grill_meat',
                'name' => 'Izgara & Et/Balık (Chef Rôtisseur / Grillardin / Poissonier)',
                'group' => 'Mutfak',
                'color' => '#DC2626',
                'icon' => 'fa-drumstick-bite',
                'description' => 'Kırmızı et, köfte, ızgara balık, çevirmeler ve alevli ocak operasyonu.',
            ],
            'cold_pantry' => [
                'code' => 'cold_pantry',
                'name' => 'Soğuk & Meze (Chef Garde-Manger / Hors d\'oeuvrier)',
                'group' => 'Mutfak',
                'color' => '#10B981',
                'icon' => 'fa-leaf',
                'description' => 'Soğuk mezeler, salatalar, şarküteri tabakları ve soğuk ordövrler.',
            ],
            'bakery_dough' => [
                'code' => 'bakery_dough',
                'name' => 'Hamur & Fırın (Boulanger / Pide & Pizza)',
                'group' => 'Mutfak',
                'color' => '#F59E0B',
                'icon' => 'fa-pizza-slice',
                'description' => 'Pide, pizza, lahmacun, fırın mamülleri ve taze ekmek üretimi.',
            ],
            'pastry_dessert' => [
                'code' => 'pastry_dessert',
                'name' => 'Pastane & Tatlı (Chef Pâtissier / Glacier)',
                'group' => 'Mutfak',
                'color' => '#EC4899',
                'icon' => 'fa-birthday-cake',
                'description' => 'Sıcak ve soğuk tatlılar, sufle, pasta, künefe ve dondurmalar.',
            ],
            'soup_garnish' => [
                'code' => 'soup_garnish',
                'name' => 'Garnitür & Çorba (Chef Entremetier / Chef Potager)',
                'group' => 'Mutfak',
                'color' => '#8B5CF6',
                'icon' => 'fa-bowl-rice',
                'description' => 'Günün çorbaları, sebze garnitürleri, makarnalar ve pilavlar.',
            ],
            'breakfast' => [
                'code' => 'breakfast',
                'name' => 'Kahvaltı & Brunch (Cuisinier de Breakfast)',
                'group' => 'Mutfak',
                'color' => '#F97316',
                'icon' => 'fa-egg',
                'description' => 'Serpme kahvaltılıklar, sahanda yumurta, omlet, krep ve brunch menüsü.',
            ],
            'aboyer' => [
                'code' => 'aboyer',
                'name' => 'Sunucu / Mutfak Spikeri (Aboyer / Expeditor)',
                'group' => 'Mutfak',
                'color' => '#6366F1',
                'icon' => 'fa-bullhorn',
                'description' => 'Adisyonları aşçılara iletir, hazır tabakların kalite kontrolünü yapıp garsona teslim eder.',
            ],
            'stewarding' => [
                'code' => 'stewarding',
                'name' => 'Bulaşıkhane & Hijyen (Steward / Plongeur)',
                'group' => 'Mutfak',
                'color' => '#64748B',
                'icon' => 'fa-soap',
                'description' => 'Kazan, tencere, tabak ve bardak hijyeni, çöp ve malzeme sirkülasyonu.',
            ],

            // Bar İstasyonları (Bar Brigade)
            'bar_captain' => [
                'code' => 'bar_captain',
                'name' => 'Bar Kaptanı (Bar Captain)',
                'group' => 'Bar',
                'color' => '#06B6D4',
                'icon' => 'fa-user-tie',
                'description' => 'Barların genel denetimi, barmenlerin yönetimi, içecek reçeteleri ve stok kontrolü.',
            ],
            'bartender' => [
                'code' => 'bartender',
                'name' => 'Barmen / Barmaid (Miksoloji & Hazırlık)',
                'group' => 'Bar',
                'color' => '#3B82F6',
                'icon' => 'fa-cocktail',
                'description' => 'Kokteylleri ve özel içecekleri hazırlayan miksoloji uzmanı.',
            ],
            'barboy' => [
                'code' => 'barboy',
                'name' => 'Bar Komisi / Barboy (Destek & Temizlik)',
                'group' => 'Bar',
                'color' => '#0EA5E9',
                'icon' => 'fa-glass-cheers',
                'description' => 'Bar temizliği, bardak parlatma, buz ve meyve taşıma, hammadde tamamlama.',
            ],
            'sommelier' => [
                'code' => 'sommelier',
                'name' => 'Sommelier (Şarap & Mahzen Uzmanı)',
                'group' => 'Bar',
                'color' => '#9333EA',
                'icon' => 'fa-wine-bottle',
                'description' => 'Şarap menüsü, kav yönetimi ve yemek-şarap uyumu uzmanı.',
            ],

            // Salon (FOH - Front of House)
            'head_waiter' => [
                'code' => 'head_waiter',
                'name' => 'Şef Garson / Kaptan (Head Waiter / Maitre d\')',
                'group' => 'Salon',
                'color' => '#14B8A6',
                'icon' => 'fa-clipboard-check',
                'description' => 'Salon operasyonunu denetler, misafirleri karşılar ve garsonları yönetir.',
            ],
            'waiter' => [
                'code' => 'waiter',
                'name' => 'Garson (Chef de Rang - Masa Yetkilisi)',
                'group' => 'Salon',
                'color' => '#10B981',
                'icon' => 'fa-concierge-bell',
                'description' => 'Masalara atanır, sipariş alır, adisyon açar ve servis sunar.',
            ],
            'comi' => [
                'code' => 'comi',
                'name' => 'Komi / Deberasör (Servis Desteği & Temizlik)',
                'group' => 'Salon',
                'color' => '#6B7280',
                'icon' => 'fa-hands-helping',
                'description' => 'Sipariş taşıma, masa deberasajı (boş toplama) ve masa hazırlığı.',
            ],
        ];
    }

    /**
     * Get staff users (admins & providers) eligible for restaurant station assignments.
     */
    public function get_staff_users(): array
    {
        return $this->db
            ->select('id, first_name, last_name, email, id_roles, phone_number')
            ->from('users')
            ->where_in('id_roles', [1, 2])
            ->order_by('first_name ASC, last_name ASC')
            ->get()
            ->result_array();
    }

    /**
     * Get active station assignments for today or given date.
     * Returns: [user_id => ['stations' => ['hot_sauce', 'grill_meat'], 'role_title' => '...']]
     */
    public function get_daily_staff_stations(?string $shift_date = null): array
    {
        if (!$this->db->table_exists('restaurant_staff_stations')) {
            return [];
        }

        $date = !empty($shift_date) ? $shift_date : date('Y-m-d');

        $rows = $this->db
            ->from('restaurant_staff_stations')
            ->where('is_active', 1)
            ->group_start()
                ->where('shift_date', $date)
                ->or_where('shift_date IS NULL')
            ->group_end()
            ->order_by('id ASC')
            ->get()
            ->result_array();

        $result = [];
        foreach ($rows as $r) {
            $uid = (int) $r['id_users'];
            if (!isset($result[$uid])) {
                $result[$uid] = [
                    'stations' => [],
                    'role_title' => $r['role_title'] ?? '',
                ];
            }
            if (!in_array($r['station_code'], $result[$uid]['stations'], true)) {
                $result[$uid]['stations'][] = $r['station_code'];
            }
            if (!empty($r['role_title'])) {
                $result[$uid]['role_title'] = $r['role_title'];
            }
        }

        return $result;
    }

    /**
     * Save dynamic multi-station assignment for a staff member.
     */
    public function save_staff_station_assignments(int $user_id, array $station_codes, ?string $role_title = null, ?string $shift_date = null): bool
    {
        if (!$this->db->table_exists('restaurant_staff_stations')) {
            return false;
        }

        $date = !empty($shift_date) ? $shift_date : date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();

        // Remove existing assignments for this user on this shift date
        $this->db
            ->where('id_users', $user_id)
            ->group_start()
                ->where('shift_date', $date)
                ->or_where('shift_date IS NULL')
            ->group_end()
            ->delete('restaurant_staff_stations');

        // Insert new multi-station assignments
        foreach ($station_codes as $code) {
            $code = trim((string) $code);
            if (empty($code)) {
                continue;
            }
            $this->db->insert('restaurant_staff_stations', [
                'id_users' => $user_id,
                'station_code' => $code,
                'role_title' => $role_title,
                'shift_date' => $date,
                'is_active' => 1,
                'created_at' => $now,
            ]);
        }

        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    /**
     * Quick inline update of menu item (QR visibility, badge, station, price).
     */
    public function update_menu_item_quick(int $item_id, array $data): bool
    {
        if (!$this->db->table_exists('restaurant_menu_items')) {
            return false;
        }

        $update = [];
        if (isset($data['is_qr_visible'])) {
            $update['is_qr_visible'] = (int) $data['is_qr_visible'];
        }
        if (isset($data['badge_text'])) {
            $update['badge_text'] = !empty($data['badge_text']) ? trim((string) $data['badge_text']) : null;
        }
        if (!empty($data['station'])) {
            $update['station'] = trim((string) $data['station']);
        }
        if (isset($data['price'])) {
            $update['price'] = (float) $data['price'];
        }
        if (isset($data['prep_time_minutes'])) {
            $update['prep_time_minutes'] = max(1, (int) $data['prep_time_minutes']);
        }
        if (isset($data['is_available'])) {
            $update['is_available'] = (int) $data['is_available'];
        }

        if (empty($update)) {
            return true;
        }

        $update['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->update('restaurant_menu_items', $update, ['id' => $item_id]);
    }

    /**
     * Update table duration mode (open-ended vs fixed session vs daily pass).
     */
    public function update_table_duration_mode(int $table_id, string $mode, int $duration_mins = 90): bool
    {
        if (!$this->db->table_exists('restaurant_tables')) {
            return false;
        }

        $valid_mode = in_array($mode, ['open_ended', 'fixed_duration', 'daily_pass', 'multi_pass'], true) ? $mode : 'open_ended';

        return $this->db->update('restaurant_tables', [
            'duration_mode' => $valid_mode,
            'session_duration_minutes' => max(15, $duration_mins),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $table_id]);
    }

    // =========================================================================
    // NUTRIXPOS & TASTYIGNITER ADVANCED SUITE METHODS
    // =========================================================================

    /**
     * Get menu option groups with values (TastyIgniter modifiers: sizes, sauces, etc.).
     */
    public function get_menu_option_groups(int $menu_item_id = 0): array
    {
        if (!$this->db->table_exists('restaurant_menu_option_groups')) {
            return [];
        }

        if ($menu_item_id > 0) {
            $group_ids = $this->db->select('option_group_id')
                ->where('menu_item_id', $menu_item_id)
                ->get('restaurant_menu_item_options')
                ->result_array();
            $ids = array_column($group_ids, 'option_group_id');
            if (empty($ids)) {
                return [];
            }
            $this->db->where_in('id', $ids);
        }

        $groups = $this->db->order_by('id', 'asc')->get('restaurant_menu_option_groups')->result_array();
        foreach ($groups as &$group) {
            $group['values'] = $this->db->where('group_id', $group['id'])
                ->order_by('id', 'asc')
                ->get('restaurant_menu_option_values')
                ->result_array();
        }
        unset($group);

        return $groups;
    }

    /**
     * Get mealtimes and currently active mealtime (TastyIgniter).
     */
    public function get_mealtimes(): array
    {
        if (!$this->db->table_exists('restaurant_mealtimes')) {
            return [];
        }
        return $this->db->where('is_enabled', 1)->order_by('start_time', 'asc')->get('restaurant_mealtimes')->result_array();
    }

    public function get_active_mealtime(): ?array
    {
        $now_time = date('H:i:s');
        $now_day = date('D'); // Mon, Tue, etc.

        $mealtimes = $this->get_mealtimes();
        foreach ($mealtimes as $mt) {
            if ($mt['validity'] === 'recurring') {
                $days = explode(',', (string) $mt['recurring_days']);
                if (!in_array($now_day, $days, true)) {
                    continue;
                }
            }
            if ($now_time >= $mt['start_time'] && $now_time <= $mt['end_time']) {
                return $mt;
            }
        }
        return null;
    }

    /**
     * Validate and calculate coupon discount (TastyIgniter Coupon Engine).
     */
    public function validate_coupon(string $code, float $order_total = 0.00, ?int $customer_id = null): array
    {
        if (!$this->db->table_exists('restaurant_coupons')) {
            return ['valid' => false, 'message' => 'Kupon servisi aktif değil.'];
        }

        $coupon = $this->db->get_where('restaurant_coupons', [
            'code' => trim($code),
            'is_active' => 1,
        ])->row_array();

        if (!$coupon) {
            return ['valid' => false, 'message' => 'Geçersiz veya süresi dolmuş kupon kodu.'];
        }

        $now = date('Y-m-d H:i:s');
        if (!empty($coupon['valid_from']) && $now < $coupon['valid_from']) {
            return ['valid' => false, 'message' => 'Kupon henüz kullanımda değil.'];
        }
        if (!empty($coupon['valid_until']) && $now > $coupon['valid_until']) {
            return ['valid' => false, 'message' => 'Kuponun geçerlilik süresi dolmuştur.'];
        }
        if ($coupon['max_redemptions'] > 0 && $coupon['redemptions_count'] >= $coupon['max_redemptions']) {
            return ['valid' => false, 'message' => 'Kupon kullanım limitine ulaşılmıştır.'];
        }
        if ($order_total > 0 && $order_total < (float) $coupon['min_order_total']) {
            return [
                'valid' => false,
                'message' => 'Bu kupon için minimum sepet tutarı ' . number_format((float) $coupon['min_order_total'], 2) . ' ₺ olmalıdır.',
            ];
        }

        $discount = 0.00;
        if ($coupon['discount_type'] === 'percent') {
            $discount = round($order_total * ((float) $coupon['discount_value'] / 100), 2);
        } else {
            $discount = min($order_total, (float) $coupon['discount_value']);
        }

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount_amount' => $discount,
            'message' => 'Kupon başarıyla uygulandı!',
        ];
    }

    /**
     * Ingredients & Allergens (TastyIgniter).
     */
    public function get_ingredients(): array
    {
        if (!$this->db->table_exists('restaurant_ingredients')) {
            return [];
        }
        return $this->db->order_by('name', 'asc')->get('restaurant_ingredients')->result_array();
    }

    public function get_menu_ingredients(int $menu_item_id): array
    {
        if (!$this->db->table_exists('restaurant_menu_ingredients')) {
            return [];
        }
        return $this->db->select('i.*')
            ->from('restaurant_ingredients i')
            ->join('restaurant_menu_ingredients mi', 'mi.ingredient_id = i.id')
            ->where('mi.menu_item_id', $menu_item_id)
            ->get()
            ->result_array();
    }

    /**
     * Combo Tables: Combine two or more tables into a single block (TastyIgniter).
     */
    public function create_combo_table(array $table_ids, ?string $combo_name = null): int
    {
        if (count($table_ids) < 2) {
            throw new InvalidArgumentException('En az 2 masa seçilmelidir.');
        }

        $tables = $this->db->where_in('id', $table_ids)->get('restaurant_tables')->result_array();
        if (count($tables) < 2) {
            throw new RuntimeException('Belirtilen masalar bulunamadı.');
        }

        $total_capacity = array_sum(array_column($tables, 'capacity'));
        $table_numbers = implode('/', array_column($tables, 'table_number'));
        $name = $combo_name ?: 'Combo ' . $table_numbers;

        $first = $tables[0];
        $combo_data = [
            'table_number' => $name,
            'section' => $first['section'],
            'capacity' => $total_capacity,
            'status' => 'available',
            'is_combo' => 1,
            'combo_table_ids' => json_encode($table_ids),
            'pos_x' => $first['pos_x'],
            'pos_y' => $first['pos_y'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert('restaurant_tables', $combo_data);
        $combo_id = $this->db->insert_id();

        // Mark individual tables as combined/occupied
        $this->db->where_in('id', $table_ids)->update('restaurant_tables', [
            'status' => 'seated',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $combo_id;
    }

    public function split_combo_table(int $combo_table_id): bool
    {
        $combo = $this->db->get_where('restaurant_tables', ['id' => $combo_table_id, 'is_combo' => 1])->row_array();
        if (!$combo) {
            return false;
        }

        $child_ids = json_decode((string) $combo['combo_table_ids'], true);
        if (!empty($child_ids)) {
            $this->db->where_in('id', $child_ids)->update('restaurant_tables', [
                'status' => 'available',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->db->delete('restaurant_tables', ['id' => $combo_table_id]);
    }

    /**
     * Stock & Out-of-Stock Override (TastyIgniter & NutrixPOS).
     */
    public function set_out_of_stock(int $item_id, string $type = 'indefinitely', ?string $until = null): bool
    {
        return $this->db->update('restaurant_menu_items', [
            'is_available' => 0,
            'out_of_stock_type' => in_array($type, ['indefinitely', 'custom'], true) ? $type : 'indefinitely',
            'out_of_stock_until' => $until,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $item_id]);
    }

    public function clear_out_of_stock(int $item_id): bool
    {
        return $this->db->update('restaurant_menu_items', [
            'is_available' => 1,
            'out_of_stock_type' => 'none',
            'out_of_stock_until' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $item_id]);
    }

    /**
     * Kitchen & Waiter Live Intercom / Chat (NutrixPOS).
     */
    public function send_kitchen_chat(array $data): int
    {
        $insert = [
            'sender_id' => $data['sender_id'] ?? null,
            'sender_name' => $data['sender_name'] ?? 'Mutfak',
            'sender_role' => $data['sender_role'] ?? 'kitchen',
            'target_role' => $data['target_role'] ?? 'all',
            'message' => $data['message'],
            'urgency' => in_array($data['urgency'] ?? '', ['normal', 'urgent', 'ready', 'stock_out'], true) ? $data['urgency'] : 'normal',
            'table_id' => $data['table_id'] ?? null,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert('restaurant_kitchen_chats', $insert);
        return $this->db->insert_id();
    }

    public function get_recent_kitchen_chats(int $limit = 50): array
    {
        if (!$this->db->table_exists('restaurant_kitchen_chats')) {
            return [];
        }
        return $this->db->order_by('created_at', 'desc')->limit($limit)->get('restaurant_kitchen_chats')->result_array();
    }

    /**
     * Materials & BOM Recipes (NutrixPOS).
     */
    public function get_materials(): array
    {
        if (!$this->db->table_exists('restaurant_materials')) {
            return [];
        }
        return $this->db->order_by('name', 'asc')->get('restaurant_materials')->result_array();
    }

    public function get_recipe(int $menu_item_id): array
    {
        if (!$this->db->table_exists('restaurant_recipes')) {
            return [];
        }
        return $this->db->select('r.*, m.name as material_name, m.unit as material_unit, m.fixed_unit_cost, m.current_stock')
            ->from('restaurant_recipes r')
            ->join('restaurant_materials m', 'm.id = r.material_id')
            ->where('r.menu_item_id', $menu_item_id)
            ->get()
            ->result_array();
    }

    public function consume_recipe_stock(int $menu_item_id, int $quantity = 1): array
    {
        $recipe = $this->get_recipe($menu_item_id);
        $consumed = [];

        foreach ($recipe as $item) {
            $amount_to_deduct = (float) $item['quantity'] * $quantity;
            $this->db->set('current_stock', 'current_stock - ' . $amount_to_deduct, false);
            $this->db->where('id', $item['material_id']);
            $this->db->update('restaurant_materials');

            $consumed[] = [
                'material_id' => $item['material_id'],
                'material_name' => $item['material_name'],
                'deducted' => $amount_to_deduct,
                'unit' => $item['unit'],
            ];
        }

        return $consumed;
    }

    /**
     * Food Cost & Gross Profit Analysis per Dish (NutrixPOS).
     */
    public function get_item_food_cost(int $menu_item_id): array
    {
        $menu_item = $this->db->get_where('restaurant_menu_items', ['id' => $menu_item_id])->row_array();
        if (!$menu_item) {
            return ['error' => 'Ürün bulunamadı'];
        }

        $recipe = $this->get_recipe($menu_item_id);
        $total_cost = 0.00;
        foreach ($recipe as $line) {
            $total_cost += (float) $line['quantity'] * (float) $line['fixed_unit_cost'];
        }

        $sale_price = (float) $menu_item['price'];
        $gross_profit = max(0, $sale_price - $total_cost);
        $cost_percentage = $sale_price > 0 ? round(($total_cost / $sale_price) * 100, 1) : 0;

        return [
            'item_id' => $menu_item_id,
            'name' => $menu_item['name'],
            'price' => $sale_price,
            'food_cost' => round($total_cost, 2),
            'gross_profit' => round($gross_profit, 2),
            'cost_percentage' => $cost_percentage,
            'recipe' => $recipe,
        ];
    }

    /**
     * Waste / Disposal Tracking (NutrixPOS).
     */
    public function record_disposal(array $data): int
    {
        $insert = [
            'disposal_type' => $data['disposal_type'] ?? 'dish',
            'item_id' => $data['item_id'],
            'item_name' => $data['item_name'],
            'quantity' => (float) ($data['quantity'] ?? 1.0),
            'cost_loss' => (float) ($data['cost_loss'] ?? 0.0),
            'reason' => $data['reason'] ?? 'Fire / İmha',
            'reported_by' => $data['reported_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert('restaurant_disposals', $insert);
        return $this->db->insert_id();
    }

    public function get_disposals(int $limit = 50): array
    {
        if (!$this->db->table_exists('restaurant_disposals')) {
            return [];
        }
        return $this->db->order_by('created_at', 'desc')->limit($limit)->get('restaurant_disposals')->result_array();
    }

    /**
     * Get or initialize guest intelligence profile for a customer.
     */
    public function get_guest_preferences(int $customer_id): array
    {
        if (!$this->db->table_exists('restaurant_guest_preferences')) {
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

        $prefs = $this->db->get_where('restaurant_guest_preferences', ['id_users_customer' => $customer_id])->row_array();
        if ($prefs) {
            return $prefs;
        }

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
        if (!$this->db->table_exists('restaurant_guest_preferences')) {
            return ['id_users_customer' => $customer_id];
        }

        $existing = $this->db->get_where('restaurant_guest_preferences', ['id_users_customer' => $customer_id])->row_array();
        $now = date('Y-m-d H:i:s');

        $dietary = $data['dietary_restrictions'] ?? ($data['dietary'] ?? []);
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
            'seating_preference' => $data['seating_preference'] ?? ($data['preferred_seating'] ?? null),
            'favorite_drink' => $data['favorite_drink'] ?? null,
            'special_notes' => $data['special_notes'] ?? ($data['notes'] ?? null),
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
}


