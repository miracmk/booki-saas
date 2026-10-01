<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Hospitality Model (Otel & Konaklama PMS Engine)
 *
 * Enterprise Property Management System (PMS) model inspired by:
 * QloApps, hotel-mgmt-system, HotinGo, Django HMS, and HotelDruid.
 *
 * Features:
 * - Room Types & Inventory Management
 * - Visual Tape Chart / Gantt Timeline Matrix (14-30 days)
 * - Express Check-In & Check-Out Workflow
 * - Folio Billing & Extra Charges with Tax Calculations (%2 Konaklama Vergisi, KDV)
 * - Turkish KBS (Kimlik Bildirim Sistemi - Law 1774 Police Reporting)
 * - Housekeeping Task Engine & Inspection
 * - Maintenance & Room Fault Tickets
 * - Night Audit (Gün Sonu Devri) with ADR, RevPAR & Automatic Room Posting
 * - Channel Manager & OTA 2-Way iCal Synchronization (Airbnb, Booking.com)
 */
class Hospitality_model extends App_Model
{
    public function __construct()
    {
        $this->load->model('adisyons_model');
        $this->load->model('appointments_model');
    }

    /* -------------------------------------------------------------------------
     * 1. ROOM TYPES (ODA TİPLERİ & KATEGORİLER)
     * ------------------------------------------------------------------------- */

    /**
     * Get all room types.
     */
    public function get_room_types(bool $only_active = true): array
    {
        if (!$this->db->table_exists('hospitality_room_types')) {
            return [];
        }

        if ($only_active) {
            $this->db->where('is_active', 1);
        }

        $types = $this->db->order_by('base_price_per_night', 'ASC')->get('hospitality_room_types')->result_array();
        if (empty($types)) {
            $this->ensure_default_seed();
            if ($only_active) {
                $this->db->where('is_active', 1);
            }
            $types = $this->db->order_by('base_price_per_night', 'ASC')->get('hospitality_room_types')->result_array();
        }

        foreach ($types as &$t) {
            $t['amenities_list'] = !empty($t['amenities']) ? json_decode($t['amenities'], true) : [];
        }
        unset($t);

        return $types;
    }

    /**
     * Get single room type by ID.
     */
    public function get_room_type(int $id): ?array
    {
        if (!$this->db->table_exists('hospitality_room_types')) {
            return null;
        }

        $t = $this->db->get_where('hospitality_room_types', ['id' => $id])->row_array();
        if ($t) {
            $t['amenities_list'] = !empty($t['amenities']) ? json_decode($t['amenities'], true) : [];
        }
        return $t ?: null;
    }

    /**
     * Save (insert or update) room type.
     */
    public function save_room_type(array $data): int
    {
        if (!$this->db->table_exists('hospitality_room_types')) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $id = !empty($data['id']) ? (int) $data['id'] : 0;

        $record = [
            'name' => trim((string) ($data['name'] ?? 'Standart Oda')),
            'code' => strtoupper(trim((string) ($data['code'] ?? 'STD'))),
            'base_capacity_adults' => max(1, (int) ($data['base_capacity_adults'] ?? 2)),
            'base_capacity_children' => max(0, (int) ($data['base_capacity_children'] ?? 1)),
            'max_capacity' => max(1, (int) ($data['max_capacity'] ?? 3)),
            'base_price_per_night' => max(0.00, (float) ($data['base_price_per_night'] ?? 1500.00)),
            'rate_plan_default' => strtoupper(trim((string) ($data['rate_plan_default'] ?? 'BB'))),
            'bed_type' => trim((string) ($data['bed_type'] ?? '1 King Bed')),
            'room_size_sqm' => max(10, (int) ($data['room_size_sqm'] ?? 25)),
            'description' => trim((string) ($data['description'] ?? '')),
            'image_url' => trim((string) ($data['image_url'] ?? '')),
            'is_active' => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
            'updated_at' => $now,
        ];

        if (isset($data['amenities'])) {
            $record['amenities'] = is_array($data['amenities']) ? json_encode($data['amenities'], JSON_UNESCAPED_UNICODE) : (string) $data['amenities'];
        }

        if ($id > 0) {
            $this->db->update('hospitality_room_types', $record, ['id' => $id]);
            return $id;
        }

        $record['created_at'] = $now;
        $this->db->insert('hospitality_room_types', $record);
        return (int) $this->db->insert_id();
    }

    /**
     * Delete room type.
     */
    public function delete_room_type(int $id): bool
    {
        if (!$this->db->table_exists('hospitality_room_types')) {
            return false;
        }
        return $this->db->delete('hospitality_room_types', ['id' => $id]);
    }

    /**
     * Ensure default hotel seed data exists for room types and rate plans.
     */
    public function ensure_default_seed(): void
    {
        if ($this->db->table_exists('hospitality_room_types')) {
            $count = $this->db->count_all_results('hospitality_room_types');
            if ($count === 0) {
                $now = date('Y-m-d H:i:s');
                $default_types = [
                    [
                        'name' => 'Standart Çift Kişilik Oda',
                        'code' => 'STD-DBL',
                        'base_capacity_adults' => 2,
                        'base_capacity_children' => 1,
                        'max_capacity' => 3,
                        'base_price_per_night' => 2200.00,
                        'rate_plan_default' => 'BB',
                        'amenities' => json_encode(['wifi', 'ac', 'tv', 'minibar', 'balcony', 'safe', 'hairdryer'], JSON_UNESCAPED_UNICODE),
                        'bed_type' => '1 Çift Kişilik Geniş Yatak',
                        'room_size_sqm' => 24,
                        'description' => 'Geniş bahçe manzaralı balkon, minibar ve modern banyo içeren konforlu standart oda.',
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'name' => 'Superior King Teras Suite',
                        'code' => 'SUP-KING',
                        'base_capacity_adults' => 2,
                        'base_capacity_children' => 1,
                        'max_capacity' => 3,
                        'base_price_per_night' => 3800.00,
                        'rate_plan_default' => 'BB',
                        'amenities' => json_encode(['wifi', 'ac', 'tv', 'minibar', 'terrace', 'jacuzzi', 'sea_view', 'coffee_machine', 'safe'], JSON_UNESCAPED_UNICODE),
                        'bed_type' => '1 King Boy Ortopedik Yatak',
                        'room_size_sqm' => 36,
                        'description' => 'Panoramik deniz ve doğa manzaralı özel teras, jakuzi ve lüks ikramlar.',
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'name' => 'Doğa & Göl Bungalov',
                        'code' => 'BUNG-LAKE',
                        'base_capacity_adults' => 3,
                        'base_capacity_children' => 2,
                        'max_capacity' => 5,
                        'base_price_per_night' => 4500.00,
                        'rate_plan_default' => 'BB',
                        'amenities' => json_encode(['wifi', 'ac', 'fireplace', 'private_garden', 'jacuzzi', 'kitchenette', 'pet_friendly'], JSON_UNESCAPED_UNICODE),
                        'bed_type' => '1 King + 1 Çift Kişilik Açılır Kanepe',
                        'room_size_sqm' => 48,
                        'description' => 'Doğa ile baş başa ahşap mimari, şömine keyfi, müstakil bahçe ve özel jakuzi.',
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'name' => 'Deluxe Aile Rezidansı',
                        'code' => 'DELUXE-FAM',
                        'base_capacity_adults' => 4,
                        'base_capacity_children' => 2,
                        'max_capacity' => 6,
                        'base_price_per_night' => 5400.00,
                        'rate_plan_default' => 'HB',
                        'amenities' => json_encode(['wifi', 'ac', 'tv_multi', 'full_kitchen', 'balcony_double', 'washing_machine', 'child_crib'], JSON_UNESCAPED_UNICODE),
                        'bed_type' => '1 King Yatak + 2 Tek Kişilik Yatak',
                        'room_size_sqm' => 65,
                        'description' => '2 ayrı yatak odası, geniş salon ve tam donanımlı mutfak ile kalabalık aileler için ideal.',
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'name' => 'Presidential Kral Dairesi & Balayı',
                        'code' => 'PRES-VIP',
                        'base_capacity_adults' => 2,
                        'base_capacity_children' => 0,
                        'max_capacity' => 2,
                        'base_price_per_night' => 8900.00,
                        'rate_plan_default' => 'AI',
                        'amenities' => json_encode(['wifi', 'ac', 'sauna', 'infinity_jacuzzi', 'butler_service', 'vip_transfer', 'champagne_basket'], JSON_UNESCAPED_UNICODE),
                        'bed_type' => '1 Özel Tasarım Ultra King Yatak',
                        'room_size_sqm' => 85,
                        'description' => 'Tesisin en üst katında 360 derece manzara, özel sauna, teras jakuzisi ve 7/24 uşak hizmeti.',
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ];

                foreach ($default_types as $type) {
                    $this->db->insert('hospitality_room_types', $type);
                }
            }
        }

        if ($this->db->table_exists('hospitality_rate_plans')) {
            $count = $this->db->count_all_results('hospitality_rate_plans');
            if ($count === 0) {
                $now = date('Y-m-d H:i:s');
                $default_plans = [
                    [
                        'room_type_id' => null,
                        'name' => 'Standart Oda Kahvaltı (BB)',
                        'board_type' => 'BB',
                        'price_multiplier' => 1.00,
                        'weekend_price_delta' => 250.00,
                        'min_stay_nights' => 1,
                        'cancellation_policy' => 'Girişe 48 saat kalana kadar ücretsiz iptal.',
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'room_type_id' => null,
                        'name' => 'Yarım Pansiyon Gurme (HB)',
                        'board_type' => 'HB',
                        'price_multiplier' => 1.35,
                        'weekend_price_delta' => 350.00,
                        'min_stay_nights' => 2,
                        'cancellation_policy' => 'Girişe 72 saat kalana kadar ücretsiz iptal.',
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'room_type_id' => null,
                        'name' => 'İade Edilemez Erken Rezervasyon (%15 İndirimli)',
                        'board_type' => 'BB',
                        'price_multiplier' => 0.85,
                        'weekend_price_delta' => 0.00,
                        'min_stay_nights' => 2,
                        'cancellation_policy' => 'İade edilemez (Non-Refundable). Tarih değişikliği yapılamaz.',
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ];

                foreach ($default_plans as $plan) {
                    $this->db->insert('hospitality_rate_plans', $plan);
                }
            }
        }
    }

    /**
     * Get rate plans.
     */
    public function get_rate_plans(bool $only_active = true): array
    {
        if (!$this->db->table_exists('hospitality_rate_plans')) {
            return [];
        }

        if ($only_active) {
            $this->db->where('r.is_active', 1);
        }

        return $this->db
            ->select('r.*, t.name as room_type_name')
            ->from('hospitality_rate_plans r')
            ->join('hospitality_room_types t', 't.id = r.room_type_id', 'left')
            ->order_by('r.name ASC')
            ->get()
            ->result_array();
    }

    /**
     * Save rate plan.
     */
    public function save_rate_plan(array $data): int
    {
        if (!$this->db->table_exists('hospitality_rate_plans')) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $id = !empty($data['id']) ? (int) $data['id'] : 0;

        $record = [
            'room_type_id' => !empty($data['room_type_id']) ? (int) $data['room_type_id'] : null,
            'name' => trim((string) ($data['name'] ?? 'Fiyat Planı')),
            'board_type' => strtoupper(trim((string) ($data['board_type'] ?? 'BB'))),
            'start_date' => !empty($data['start_date']) ? date('Y-m-d', strtotime($data['start_date'])) : null,
            'end_date' => !empty($data['end_date']) ? date('Y-m-d', strtotime($data['end_date'])) : null,
            'price_multiplier' => max(0.10, (float) ($data['price_multiplier'] ?? 1.00)),
            'fixed_price' => !empty($data['fixed_price']) ? (float) $data['fixed_price'] : null,
            'weekend_price_delta' => (float) ($data['weekend_price_delta'] ?? 0.00),
            'min_stay_nights' => max(1, (int) ($data['min_stay_nights'] ?? 1)),
            'cancellation_policy' => trim((string) ($data['cancellation_policy'] ?? '')),
            'is_active' => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
            'updated_at' => $now,
        ];

        if ($id > 0) {
            $this->db->update('hospitality_rate_plans', $record, ['id' => $id]);
            return $id;
        }

        $record['created_at'] = $now;
        $this->db->insert('hospitality_rate_plans', $record);
        return (int) $this->db->insert_id();
    }

    /**
     * Delete rate plan.
     */
    public function delete_rate_plan(int $id): bool
    {
        if (!$this->db->table_exists('hospitality_rate_plans')) {
            return false;
        }
        return $this->db->delete('hospitality_rate_plans', ['id' => $id]);
    }

    /* -------------------------------------------------------------------------
     * 2. ROOM INVENTORY & OVERVIEW (ODA ENVANTERİ & DURUM MATRİSİ)
     * ------------------------------------------------------------------------- */

    /**
     * Get enriched overview of all hotel rooms/units for a given date.
     */
    public function get_rooms_overview(?string $target_date = null): array
    {
        $target_date = $target_date ?: date('Y-m-d');
        $date_start = $target_date . ' 00:00:00';
        $date_end = $target_date . ' 23:59:59';

        $stations = $this->db->order_by('display_order ASC, name ASC')->get('stations')->result_array();

        // Active appointments overlapping today
        $active_appts = $this->db
            ->select('a.*, c.first_name as guest_first_name, c.last_name as guest_last_name, c.phone_number as guest_phone, c.email as guest_email, s.name as service_name')
            ->from('appointments a')
            ->join('users c', 'c.id = a.id_users_customer', 'left')
            ->join('services s', 's.id = a.id_services', 'left')
            ->where('a.id_stations IS NOT NULL')
            ->where('a.is_unavailability', 0)
            ->where('a.start_datetime <=', $date_end)
            ->where('a.end_datetime >=', $date_start)
            ->where_not_in('a.status', ['cancelled'])
            ->get()
            ->result_array();

        $appts_by_station = [];
        foreach ($active_appts as $appt) {
            $st_id = (int) $appt['id_stations'];
            if (!isset($appts_by_station[$st_id])) {
                $appts_by_station[$st_id] = $appt;
            }
        }

        // Room types lookup
        $room_types = [];
        if ($this->db->table_exists('hospitality_room_types')) {
            $types_raw = $this->db->get('hospitality_room_types')->result_array();
            foreach ($types_raw as $tr) {
                $room_types[$tr['id']] = $tr;
            }
        }

        // Open folios by room/appointment
        $folios_by_appt = [];
        $folios_by_room = [];
        if ($this->db->table_exists('adisyons')) {
            $this->db->select('id, id_appointments, id_stations, adisyon_number, total_amount, paid_amount, status');
            $this->db->where('status', 'open');
            $open_folios = $this->db->get('adisyons')->result_array();
            foreach ($open_folios as $fol) {
                if (!empty($fol['id_appointments'])) {
                    $folios_by_appt[(int) $fol['id_appointments']] = $fol;
                }
                if (!empty($fol['id_stations'])) {
                    $folios_by_room[(int) $fol['id_stations']] = $fol;
                }
            }
        }

        $rooms = [];
        foreach ($stations as $st) {
            $roomId = (int) $st['id'];
            $roomStatus = $st['housekeeping_status'] ?? ($st['status'] ?? 'clean');

            $meta = [];
            if (!empty($st['notes']) && str_starts_with(trim($st['notes']), '{')) {
                $meta = json_decode($st['notes'], true) ?: [];
            }

            $activeAppt = $appts_by_station[$roomId] ?? null;

            // If active appointment is checked in or present, flag room as occupied
            if ($activeAppt) {
                $apptStatus = $activeAppt['status'] ?? '';
                if ($apptStatus === 'checked_in' || $apptStatus === 'confirmed' || empty($roomStatus) || $roomStatus === 'clean') {
                    $roomStatus = 'occupied';
                }
            }

            $roomTypeId = !empty($st['room_type_id']) ? (int) $st['room_type_id'] : ($meta['room_type_id'] ?? null);
            $roomType = $roomTypeId && isset($room_types[$roomTypeId]) ? $room_types[$roomTypeId] : null;

            $capacity = (int) ($st['capacity'] ?? ($roomType['max_capacity'] ?? ($meta['capacity'] ?? 2)));
            $floor = !empty($st['floor_building']) ? $st['floor_building'] : ($meta['floor'] ?? 'Ana Bina');
            $doorCode = !empty($st['door_lock_code']) ? $st['door_lock_code'] : ($meta['door_lock_code'] ?? '');

            $guest_name = '';
            $guest_id = null;
            $checkout_date = '';
            $checkin_date = '';
            $folio_id = null;
            $folio_balance = 0.00;

            if ($activeAppt) {
                $guest_name = trim(($activeAppt['guest_first_name'] ?? '') . ' ' . ($activeAppt['guest_last_name'] ?? ''));
                $guest_id = (int) $activeAppt['id_users_customer'];
                $checkin_date = date('d.m.Y H:i', strtotime($activeAppt['start_datetime']));
                $checkout_date = date('d.m.Y H:i', strtotime($activeAppt['end_datetime']));

                $linkedFolio = $folios_by_appt[(int) $activeAppt['id']] ?? ($folios_by_room[$roomId] ?? null);
                if ($linkedFolio) {
                    $folio_id = (int) $linkedFolio['id'];
                    $folio_balance = (float) $linkedFolio['total_amount'] - (float) $linkedFolio['paid_amount'];
                }
            }

            $roomNumber = preg_replace('/[^0-9]/', '', $st['name']) ?: (string) $roomId;

            $rooms[] = [
                'id' => $roomId,
                'name' => $st['name'],
                'room_number' => $roomNumber,
                'type' => $roomType ? $roomType['name'] : $st['name'],
                'type_code' => $roomType['code'] ?? 'STD',
                'room_type_id' => $roomTypeId,
                'base_price' => (float) ($roomType['base_price_per_night'] ?? 1500.00),
                'status' => $roomStatus,
                'housekeeping_status' => $roomStatus,
                'capacity' => $capacity,
                'floor' => $floor,
                'door_lock_code' => $doorCode,
                'guest_name' => $guest_name,
                'guest_id' => $guest_id,
                'checkin_date' => $checkin_date,
                'checkout_date' => $checkout_date,
                'folio_id' => $folio_id,
                'folio_balance' => $folio_balance,
                'bed_type' => $roomType['bed_type'] ?? ($meta['bed_type'] ?? '1 King Çift Kişilik Yatak'),
                'room_size_sqm' => (int) ($roomType['room_size_sqm'] ?? ($meta['room_size_sqm'] ?? 28)),
                'amenities_list' => !empty($roomType['amenities']) ? (is_array($roomType['amenities']) ? $roomType['amenities'] : (json_decode($roomType['amenities'], true) ?: [])) : ['wifi', 'ac'],
                'room_description' => $roomType['description'] ?? '',
                'notes' => $st['notes'] ?? '',
                'active_guest' => $activeAppt ? [
                    'appointment_id' => (int) $activeAppt['id'],
                    'customer_id' => $guest_id,
                    'name' => $guest_name,
                    'phone' => $activeAppt['guest_phone'] ?? '',
                    'email' => $activeAppt['guest_email'] ?? '',
                    'start_datetime' => $activeAppt['start_datetime'],
                    'end_datetime' => $activeAppt['end_datetime'],
                    'board_type' => $activeAppt['hospitality_board_type'] ?? 'BB',
                    'service_name' => $activeAppt['service_name'] ?? 'Konaklama',
                ] : null,
            ];
        }

        return $rooms;
    }

    /**
     * Update room housekeeping / occupancy status.
     */
    public function update_room_housekeeping_status(int $room_id, string $status, ?int $user_id = null): bool
    {
        $valid_statuses = ['clean', 'dirty', 'cleaning', 'inspected', 'occupied', 'maintenance', 'do_not_disturb'];
        $status = strtolower(trim($status));
        if ($status === 'available') {
            $status = 'clean';
        }

        if (!in_array($status, $valid_statuses, true)) {
            return false;
        }

        $station = $this->db->get_where('stations', ['id' => $room_id])->row_array();
        if (!$station) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $update = [
            'update_datetime' => $now,
        ];

        if ($this->db->field_exists('status', 'stations')) {
            $update['status'] = $status;
        }
        if ($this->db->field_exists('housekeeping_status', 'stations')) {
            $update['housekeeping_status'] = $status;
        }
        if ($status === 'inspected' && $this->db->field_exists('last_inspected_at', 'stations')) {
            $update['last_inspected_at'] = $now;
            if ($user_id && $this->db->field_exists('inspected_by_user_id', 'stations')) {
                $update['inspected_by_user_id'] = $user_id;
            }
        }

        $notes = $station['notes'] ?? '';
        $meta = [];
        if (!empty($notes) && str_starts_with(trim($notes), '{')) {
            $meta = json_decode($notes, true) ?: [];
        }
        $meta['status'] = $status;
        $meta['housekeeping_status'] = $status;
        $meta['updated_at'] = $now;
        $update['notes'] = json_encode($meta, JSON_UNESCAPED_UNICODE);

        return $this->db->update('stations', $update, ['id' => $room_id]);
    }

    /* -------------------------------------------------------------------------
     * 3. VISUAL TAPE CHART / GANTT TIMELINE MATRIX (HOTELDRUID & QLOAPPS BENCHMARK)
     * ------------------------------------------------------------------------- */

    /**
     * Generate visual tape chart matrix for room timeline (14 to 30 days).
     */
    public function get_tape_chart_matrix(?string $start_date = null, int $days = 14): array
    {
        $start_date = $start_date ? date('Y-m-d', strtotime($start_date)) : date('Y-m-d');
        $days = max(7, min(31, $days));

        $end_date = date('Y-m-d', strtotime("$start_date +" . ($days - 1) . " days"));

        // Build calendar days header
        $calendar_days = [];
        for ($i = 0; $i < $days; $i++) {
            $current = date('Y-m-d', strtotime("$start_date +$i days"));
            $time = strtotime($current);
            $dayOfWeek = (int) date('N', $time);
            $calendar_days[] = [
                'date' => $current,
                'day_num' => date('d', $time),
                'day_name' => date('D', $time),
                'month_name' => date('M', $time),
                'is_weekend' => ($dayOfWeek === 6 || $dayOfWeek === 7),
                'is_today' => ($current === date('Y-m-d')),
                'total_occupied' => 0,
                'occupancy_pct' => 0.0,
            ];
        }

        $stations = $this->db->order_by('display_order ASC, name ASC')->get('stations')->result_array();
        $total_rooms = count($stations);

        // Fetch all reservations overlapping the entire window
        $appts = $this->db
            ->select('a.*, c.first_name, c.last_name, c.phone_number, c.email, s.name as service_name')
            ->from('appointments a')
            ->join('users c', 'c.id = a.id_users_customer', 'left')
            ->join('services s', 's.id = a.id_services', 'left')
            ->where('a.id_stations IS NOT NULL')
            ->where('a.is_unavailability', 0)
            ->where('a.start_datetime <=', $end_date . ' 23:59:59')
            ->where('a.end_datetime >=', $start_date . ' 00:00:00')
            ->where_not_in('a.status', ['cancelled'])
            ->order_by('a.start_datetime ASC')
            ->get()
            ->result_array();

        // Group appointments by room ID
        $appts_by_room = [];
        foreach ($appts as $a) {
            $rId = (int) $a['id_stations'];
            $appts_by_room[$rId][] = $a;
        }

        // Room rows for the tape chart
        $room_rows = [];
        $day_occupancy_counts = array_fill_keys(array_column($calendar_days, 'date'), 0);

        foreach ($stations as $st) {
            $roomId = (int) $st['id'];
            $roomAppts = $appts_by_room[$roomId] ?? [];
            $blocks = [];

            foreach ($roomAppts as $ap) {
                $aStart = date('Y-m-d', strtotime($ap['start_datetime']));
                $aEnd = date('Y-m-d', strtotime($ap['end_datetime']));

                // Clamp to timeline window
                $visStart = max($aStart, $start_date);
                $visEnd = min($aEnd, $end_date);

                $startIdx = (int) ((strtotime($visStart) - strtotime($start_date)) / 86400);
                $spanDays = max(1, (int) ((strtotime($visEnd) - strtotime($visStart)) / 86400));

                // Tally daily occupancy
                $scan = $visStart;
                while ($scan < $visEnd && isset($day_occupancy_counts[$scan])) {
                    $day_occupancy_counts[$scan]++;
                    $scan = date('Y-m-d', strtotime("$scan +1 day"));
                }

                $status = $ap['status'];
                $color = '#3b82f6'; // default blue
                if ($status === 'checked_in') {
                    $color = '#10b981'; // green
                } elseif ($status === 'confirmed') {
                    $color = '#6366f1'; // indigo
                } elseif ($status === 'completed') {
                    $color = '#64748b'; // slate
                }

                $guestName = trim(($ap['first_name'] ?? '') . ' ' . ($ap['last_name'] ?? '')) ?: 'Misafir';

                $blocks[] = [
                    'appointment_id' => (int) $ap['id'],
                    'guest_name' => $guestName,
                    'guest_id' => (int) $ap['id_users_customer'],
                    'phone' => $ap['phone_number'] ?? '',
                    'start_date' => $aStart,
                    'end_date' => $aEnd,
                    'start_col' => $startIdx,
                    'span_cols' => $spanDays,
                    'status' => $status,
                    'board_type' => $ap['hospitality_board_type'] ?? 'BB',
                    'color' => $color,
                ];
            }

            $room_rows[] = [
                'room_id' => $roomId,
                'name' => $st['name'],
                'room_number' => preg_replace('/[^0-9]/', '', $st['name']) ?: (string) $roomId,
                'floor' => $st['floor_building'] ?? 'Ana Bina',
                'housekeeping_status' => $st['housekeeping_status'] ?? ($st['status'] ?? 'clean'),
                'blocks' => $blocks,
            ];
        }

        // Update daily occupancy percentages
        foreach ($calendar_days as &$cd) {
            $dt = $cd['date'];
            $occCount = $day_occupancy_counts[$dt] ?? 0;
            $cd['total_occupied'] = $occCount;
            $cd['occupancy_pct'] = $total_rooms > 0 ? round(($occCount / $total_rooms) * 100, 1) : 0.0;
        }
        unset($cd);

        return [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'days_count' => $days,
            'calendar_days' => $calendar_days,
            'room_rows' => $room_rows,
            'total_rooms' => $total_rooms,
        ];
    }

    /* -------------------------------------------------------------------------
     * 4. EXPRESS CHECK-IN & CHECK-OUT WORKFLOW
     * ------------------------------------------------------------------------- */

    /**
     * Express Check-In Workflow:
     * - Validates reservation / room
     * - Updates appointment to checked_in
     * - Generates door lock PIN
     * - Marks room status as occupied
     * - Auto-opens or links guest folio (ea_adisyons)
     * - Registers Turkish KBS police declaration (if provided)
     */
    public function express_checkin(array $data): array
    {
        $this->db->trans_start();
        $now = date('Y-m-d H:i:s');

        $appt_id = !empty($data['appointment_id']) ? (int) $data['appointment_id'] : 0;
        $room_id = !empty($data['room_id']) ? (int) $data['room_id'] : 0;
        $guest_id = !empty($data['customer_id']) ? (int) $data['customer_id'] : 0;

        $appointment = null;
        if ($appt_id > 0) {
            $appointment = $this->db->get_where('appointments', ['id' => $appt_id])->row_array();
            if ($appointment) {
                $room_id = $room_id ?: (int) $appointment['id_stations'];
                $guest_id = $guest_id ?: (int) $appointment['id_users_customer'];
            }
        }

        if (!$room_id) {
            throw new InvalidArgumentException('Check-in yapılacak oda ID zorunludur.');
        }

        // Generate digital door lock PIN (6 digits)
        $doorPin = (string) mt_rand(100000, 999999);

        // If no appointment exists, create walk-in appointment
        if (!$appointment) {
            $start_dt = !empty($data['start_datetime']) ? $data['start_datetime'] : $now;
            $end_dt = !empty($data['end_datetime']) ? $data['end_datetime'] : date('Y-m-d 12:00:00', strtotime('+1 day'));
            $boardType = strtoupper(trim((string) ($data['board_type'] ?? 'BB')));

            // Find default hospitality service
            $defaultService = $this->db->order_by('id ASC')->get('services')->row_array();
            $serviceId = $defaultService ? (int) $defaultService['id'] : 1;

            // Find default provider
            $defaultProvider = $this->db->get('users')->row_array();
            $providerId = $defaultProvider ? (int) $defaultProvider['id'] : 1;

            // Find default customer
            if ($guest_id <= 0) {
                $cust = $this->db->select('id')->order_by('id ASC')->get_where('users', ['role_slug' => 'customer'])->row_array();
                if (!$cust) {
                    $cust = $this->db->select('id')->order_by('id ASC')->get_where('users', ['id_roles' => 3])->row_array();
                }
                if (!$cust) {
                    $cust = $this->db->select('id')->order_by('id ASC')->get('users')->row_array();
                }
                $guest_id = $cust ? (int) $cust['id'] : 1;
            }

            $apptData = [
                'book_datetime' => $now,
                'start_datetime' => $start_dt,
                'end_datetime' => $end_dt,
                'is_unavailability' => 0,
                'id_users_provider' => $providerId,
                'id_users_customer' => $guest_id,
                'id_services' => $serviceId,
                'id_stations' => $room_id,
                'status' => 'checked_in',
                'notes' => 'Otel Hızlı Giriş (Walk-in Check-in)',
            ];
            if ($this->db->field_exists('hospitality_board_type', 'appointments')) {
                $apptData['hospitality_board_type'] = $boardType;
                $apptData['hospitality_checked_in_at'] = $now;
                $apptData['hospitality_door_pin'] = $doorPin;
                $apptData['hospitality_pax_adults'] = (int) ($data['adults'] ?? 2);
                $apptData['hospitality_pax_children'] = (int) ($data['children'] ?? 0);
            }
            $this->db->insert('appointments', $apptData);
            $appt_id = (int) $this->db->insert_id();
        } else {
            // Update existing appointment
            $updateAppt = [
                'status' => 'checked_in',
                'id_stations' => $room_id,
            ];
            if ($this->db->field_exists('hospitality_checked_in_at', 'appointments')) {
                $updateAppt['hospitality_checked_in_at'] = $now;
                $updateAppt['hospitality_door_pin'] = $doorPin;
            }
            $this->db->update('appointments', $updateAppt, ['id' => $appt_id]);
        }

        // Update room status to occupied
        $this->update_room_housekeeping_status($room_id, 'occupied');
        if ($this->db->field_exists('door_lock_code', 'stations')) {
            $this->db->update('stations', ['door_lock_code' => $doorPin], ['id' => $room_id]);
        }

        // Create or find open folio (ea_adisyons)
        $folio = $this->db->get_where('adisyons', [
            'id_appointments' => $appt_id,
            'status' => 'open',
        ])->row_array();

        if (!$folio && $room_id > 0 && $this->db->field_exists('id_stations', 'adisyons')) {
            $folio = $this->db->get_where('adisyons', [
                'id_stations' => $room_id,
                'status' => 'open',
            ])->row_array();
        }

        $station = $this->db->get_where('stations', ['id' => $room_id])->row_array();
        $roomName = $station['name'] ?? ('Oda #' . $room_id);

        if (!$folio) {
            $folioNum = 'FOL-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
            $folioData = [
                'adisyon_number' => $folioNum,
                'id_users_customer' => $guest_id > 0 ? $guest_id : null,
                'id_appointments' => $appt_id,
                'status' => 'open',
                'payment_status' => 'unpaid',
                'invoice_status' => 'uninvoiced',
                'subtotal' => 0.00,
                'tax_amount' => 0.00,
                'total_amount' => 0.00,
                'paid_amount' => 0.00,
                'notes' => 'Otel Folyo - ' . $roomName,
                'opened_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if ($this->db->field_exists('id_stations', 'adisyons')) {
                $folioData['id_stations'] = $room_id;
            }
            $this->db->insert('adisyons', $folioData);
            $folio_id = (int) $this->db->insert_id();
        } else {
            $folio_id = (int) $folio['id'];
        }

        // Register KBS declaration if provided
        $kbs_id = null;
        if (!empty($data['kbs_id_number']) || !empty($data['national_id_or_passport'])) {
            $kbsData = [
                'id_appointments' => $appt_id,
                'id_users_customer' => $guest_id,
                'room_station_id' => $room_id,
                'national_id_or_passport' => $data['kbs_id_number'] ?? $data['national_id_or_passport'],
                'id_type' => $data['kbs_id_type'] ?? ($data['id_type'] ?? 'TC'),
                'first_name' => $data['guest_first_name'] ?? ($data['first_name'] ?? 'Misafir'),
                'last_name' => $data['guest_last_name'] ?? ($data['last_name'] ?? ''),
                'father_name' => $data['father_name'] ?? '',
                'mother_name' => $data['mother_name'] ?? '',
                'birth_date' => !empty($data['birth_date']) ? date('Y-m-d', strtotime($data['birth_date'])) : null,
                'birth_place' => $data['birth_place'] ?? '',
                'gender' => $data['gender'] ?? 'M',
                'nationality_code' => $data['nationality_code'] ?? 'TUR',
                'vehicle_plate' => $data['vehicle_plate'] ?? '',
                'phone' => $data['guest_phone'] ?? ($data['phone'] ?? ''),
                'checkin_datetime' => $now,
                'kbs_status' => 'reported',
                'kbs_reference_code' => 'KBS-' . date('YmdHis') . '-' . mt_rand(100, 999),
                'kbs_sent_at' => $now,
            ];
            $kbs_id = $this->record_kbs_declaration($kbsData);
        }

        $this->db->trans_complete();

        return [
            'success' => true,
            'message' => 'Check-in başarıyla gerçekleştirildi. Misafir odaya yerleştirildi.',
            'appointment_id' => $appt_id,
            'room_id' => $room_id,
            'room_name' => $roomName,
            'door_pin' => $doorPin,
            'folio_id' => $folio_id,
            'kbs_id' => $kbs_id,
        ];
    }

    /**
     * Express Check-Out Workflow:
     * - Sets appointment to completed / checked_out
     * - Reconciles folio balance & processes settlement payment (if supplied)
     * - Sets room status to dirty
     * - Auto-creates Departure Cleaning task for Housekeeping
     * - Updates KBS checkout timestamp
     */
    public function express_checkout(int $appointment_id_or_room_id, array $settlement = []): array
    {
        $this->db->trans_start();
        $now = date('Y-m-d H:i:s');

        $appt = null;
        $room_id = 0;

        // Try appointment first
        $appt = $this->db->get_where('appointments', ['id' => $appointment_id_or_room_id])->row_array();
        if ($appt) {
            $room_id = (int) $appt['id_stations'];
        } else {
            // Treat as room_id
            $room_id = $appointment_id_or_room_id;
            $appt = $this->db
                ->where('id_stations', $room_id)
                ->where('is_unavailability', 0)
                ->where_not_in('status', ['cancelled', 'completed'])
                ->order_by('id DESC')
                ->get('appointments')
                ->row_array();
        }

        $appt_id = $appt ? (int) $appt['id'] : 0;

        // Find open folio
        $folio = null;
        if ($appt_id > 0) {
            $folio = $this->db->get_where('adisyons', ['id_appointments' => $appt_id, 'status' => 'open'])->row_array();
        }
        if (!$folio && $room_id > 0 && $this->db->field_exists('id_stations', 'adisyons')) {
            $folio = $this->db->get_where('adisyons', ['id_stations' => $room_id, 'status' => 'open'])->row_array();
        }

        $balance = 0.00;
        $paid_now = 0.00;
        if ($folio) {
            $balance = (float) $folio['total_amount'] - (float) $folio['paid_amount'];

            // Process payment settlement if requested
            if (!empty($settlement['pay_balance']) || !empty($settlement['amount'])) {
                $payAmount = isset($settlement['amount']) ? (float) $settlement['amount'] : $balance;
                if ($payAmount > 0) {
                    $method = $settlement['payment_method'] ?? 'credit_card';
                    $this->settle_folio((int) $folio['id'], $method, $payAmount);
                    $paid_now = $payAmount;
                    $balance = max(0.00, $balance - $payAmount);
                }
            }

            // Close folio if fully paid or forced
            if ($balance <= 0.01 || !empty($settlement['force_close'])) {
                $this->db->update('adisyons', [
                    'status' => 'closed',
                    'closed_at' => $now,
                    'updated_at' => $now,
                ], ['id' => $folio['id']]);
            }
        }

        // Complete appointment
        if ($appt_id > 0) {
            $updateAppt = [
                'status' => 'completed',
            ];
            if ($this->db->field_exists('hospitality_checked_out_at', 'appointments')) {
                $updateAppt['hospitality_checked_out_at'] = $now;
            }
            $this->db->update('appointments', $updateAppt, ['id' => $appt_id]);
        }

        // Set room status to dirty (ready for housekeeping)
        if ($room_id > 0) {
            $this->update_room_housekeeping_status($room_id, 'dirty');

            // Dispatch Departure Cleaning task to Housekeeping
            $this->create_housekeeping_task(
                $room_id,
                'departure_clean',
                'urgent_arrival',
                null,
                'Check-out sonrası çıkış temizliği ve çarşaf/minibar yenilemesi.'
            );
        }

        // Update KBS declaration checkout
        if ($this->db->table_exists('hospitality_kbs_declarations') && $appt_id > 0) {
            $this->db->update('hospitality_kbs_declarations', [
                'checkout_datetime' => $now,
                'kbs_status' => 'checked_out_reported',
                'updated_at' => $now,
            ], [
                'id_appointments' => $appt_id,
                'checkout_datetime' => null,
            ]);
        }

        $this->db->trans_complete();

        return [
            'success' => true,
            'message' => 'Check-out başarıyla tamamlandı. Oda kirli (dirty) durumuna alındı ve kat hizmetlerine temizlik iş emri açıldı.',
            'appointment_id' => $appt_id,
            'room_id' => $room_id,
            'remaining_balance' => $balance,
            'paid_now' => $paid_now,
        ];
    }

    /* -------------------------------------------------------------------------
     * 5. FOLIO BILLING & EXTRA CHARGES WITH TAX COMPUTATION
     * ------------------------------------------------------------------------- */

    /**
     * Add charge to room folio.
     */
    public function add_room_charge(int $room_id, array $data): array
    {
        $category = strtolower(trim((string) ($data['category'] ?? 'extra')));
        $item_name = trim((string) ($data['item_name'] ?? 'Ekstra Harcama'));
        $amount = max(0.01, (float) ($data['amount'] ?? 0.00));
        $quantity = max(1.0, (float) ($data['quantity'] ?? 1.0));
        $guest_id = (int) ($data['guest_id'] ?? ($data['customer_id'] ?? 0));

        $valid_categories = ['room_charge', 'minibar', 'restaurant', 'spa_wellness', 'transfer', 'laundry', 'extra_bed', 'extra'];
        if (!in_array($category, $valid_categories, true)) {
            $category = 'extra';
        }

        $now = date('Y-m-d H:i:s');

        // Locate active appointment
        $appointment = $this->db
            ->where('id_stations', $room_id)
            ->where('is_unavailability', 0)
            ->where_not_in('status', ['cancelled', 'completed'])
            ->order_by('id DESC')
            ->get('appointments')
            ->row_array();

        if ($appointment && !$guest_id) {
            $guest_id = (int) $appointment['id_users_customer'];
        }

        // Find or create open folio
        $folio = null;
        if ($appointment) {
            $folio = $this->db->get_where('adisyons', ['id_appointments' => $appointment['id'], 'status' => 'open'])->row_array();
        }
        if (!$folio && $room_id > 0 && $this->db->field_exists('id_stations', 'adisyons')) {
            $folio = $this->db->get_where('adisyons', ['id_stations' => $room_id, 'status' => 'open'])->row_array();
        }

        $this->db->trans_start();

        if (!$folio) {
            $station = $this->db->get_where('stations', ['id' => $room_id])->row_array();
            $roomName = $station['name'] ?? ('Oda #' . $room_id);
            $folioNum = 'FOL-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

            $insert = [
                'adisyon_number' => $folioNum,
                'id_users_customer' => $guest_id > 0 ? $guest_id : null,
                'id_appointments' => $appointment ? (int) $appointment['id'] : null,
                'status' => 'open',
                'payment_status' => 'unpaid',
                'invoice_status' => 'uninvoiced',
                'subtotal' => 0.00,
                'tax_amount' => 0.00,
                'total_amount' => 0.00,
                'paid_amount' => 0.00,
                'notes' => 'Otel Folyo - ' . $roomName,
                'opened_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if ($this->db->field_exists('id_stations', 'adisyons')) {
                $insert['id_stations'] = $room_id;
            }
            $this->db->insert('adisyons', $insert);
            $folio_id = (int) $this->db->insert_id();
        } else {
            $folio_id = (int) $folio['id'];
        }

        // Add item via adisyons_model
        $charge_id = $this->adisyons_model->add_item($folio_id, [
            'item_type' => $category,
            'name' => $item_name,
            'unit_price' => $amount,
            'quantity' => $quantity,
            'tax_rate' => 0.00,
            'notes' => 'Oda Harcaması [' . strtoupper($category) . '] - Oda #' . $room_id,
        ]);

        $updatedFolio = $this->db->get_where('adisyons', ['id' => $folio_id])->row_array();
        $this->db->trans_complete();

        return [
            'success' => true,
            'charge_id' => $charge_id,
            'adisyon_id' => $folio_id,
            'category' => $category,
            'item_name' => $item_name,
            'amount' => $amount * $quantity,
            'total_amount' => (float) ($updatedFolio['total_amount'] ?? ($amount * $quantity)),
            'message' => 'Harcama folyoya başarıyla eklendi.',
        ];
    }

    /**
     * Get itemized folio summary with hospitality taxes (%2 Konaklama Vergisi, KDV).
     */
    public function get_room_folio(int $room_id_or_appointment_id): ?array
    {
        $folio = $this->db
            ->where('id_appointments', $room_id_or_appointment_id)
            ->or_where('id_stations', $room_id_or_appointment_id)
            ->order_by('id DESC')
            ->get('adisyons')
            ->row_array();

        if (!$folio) {
            return null;
        }

        $folio_id = (int) $folio['id'];
        $items = $this->db->get_where('adisyon_items', ['id_adisyons' => $folio_id])->result_array();

        $subtotal = 0.00;
        $room_charges_total = 0.00;
        $extras_total = 0.00;

        foreach ($items as $item) {
            $lineTotal = (float) ($item['total_amount'] ?? ($item['unit_price'] * $item['quantity']));
            $subtotal += $lineTotal;

            if ($item['item_type'] === 'room_charge') {
                $room_charges_total += $lineTotal;
            } else {
                $extras_total += $lineTotal;
            }
        }

        // Turkish Tourism Law: %2 Konaklama Vergisi applies to room night charges
        $accommodation_tax = round($room_charges_total * 0.02, 2);
        // %10 KDV on accommodation and food
        $kdv_amount = round($subtotal * 0.10, 2);

        $grand_total = (float) $folio['total_amount'];
        $paid_amount = (float) $folio['paid_amount'];
        $remaining_balance = max(0.00, $grand_total - $paid_amount);

        return [
            'folio_id' => $folio_id,
            'folio_number' => $folio['adisyon_number'],
            'status' => $folio['status'],
            'items' => $items,
            'subtotal' => $subtotal,
            'room_charges_total' => $room_charges_total,
            'extras_total' => $extras_total,
            'accommodation_tax_2pct' => $accommodation_tax,
            'kdv_amount' => $kdv_amount,
            'total_amount' => $grand_total,
            'paid_amount' => $paid_amount,
            'remaining_balance' => $remaining_balance,
            'opened_at' => $folio['opened_at'],
            'closed_at' => $folio['closed_at'] ?? null,
        ];
    }

    /**
     * Settle folio payment.
     */
    public function settle_folio(int $adisyon_id, string $method = 'credit_card', ?float $amount = null, ?int $user_id = null): bool
    {
        $folio = $this->db->get_where('adisyons', ['id' => $adisyon_id])->row_array();
        if (!$folio) {
            return false;
        }

        $balance = (float) $folio['total_amount'] - (float) $folio['paid_amount'];
        $payAmount = $amount !== null ? max(0.01, $amount) : max(0.01, $balance);
        $newPaid = (float) $folio['paid_amount'] + $payAmount;
        $newPaymentStatus = ($newPaid >= (float) $folio['total_amount']) ? 'paid' : 'partial';

        $this->db->trans_start();

        if ($this->db->table_exists('adisyon_payments')) {
            $this->db->insert('adisyon_payments', [
                'id_adisyons' => $adisyon_id,
                'payment_method' => $method,
                'amount' => $payAmount,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->db->update('adisyons', [
            'paid_amount' => $newPaid,
            'payment_status' => $newPaymentStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $adisyon_id]);

        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    /* -------------------------------------------------------------------------
     * 6. TURKISH KBS (KİMLİK BİLDİRİM SİSTEMİ - LAW 1774 POLICE/GENDARMERIE)
     * ------------------------------------------------------------------------- */

    /**
     * Record guest KBS identity declaration.
     */
    public function record_kbs_declaration(array $data): int
    {
        if (!$this->db->table_exists('hospitality_kbs_declarations')) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $record = [
            'id_appointments' => !empty($data['id_appointments']) ? (int) $data['id_appointments'] : null,
            'id_users_customer' => !empty($data['id_users_customer']) ? (int) $data['id_users_customer'] : null,
            'room_station_id' => (int) ($data['room_station_id'] ?? 1),
            'national_id_or_passport' => trim((string) ($data['national_id_or_passport'] ?? '')),
            'id_type' => strtoupper(trim((string) ($data['id_type'] ?? 'TC'))),
            'first_name' => trim((string) ($data['first_name'] ?? '')),
            'last_name' => trim((string) ($data['last_name'] ?? '')),
            'father_name' => trim((string) ($data['father_name'] ?? '')),
            'mother_name' => trim((string) ($data['mother_name'] ?? '')),
            'birth_date' => !empty($data['birth_date']) ? date('Y-m-d', strtotime($data['birth_date'])) : null,
            'birth_place' => trim((string) ($data['birth_place'] ?? '')),
            'gender' => strtoupper(trim((string) ($data['gender'] ?? 'M'))),
            'nationality_code' => strtoupper(trim((string) ($data['nationality_code'] ?? 'TUR'))),
            'vehicle_plate' => strtoupper(trim((string) ($data['vehicle_plate'] ?? ''))),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'checkin_datetime' => !empty($data['checkin_datetime']) ? $data['checkin_datetime'] : $now,
            'checkout_datetime' => !empty($data['checkout_datetime']) ? $data['checkout_datetime'] : null,
            'kbs_status' => $data['kbs_status'] ?? 'reported',
            'kbs_reference_code' => $data['kbs_reference_code'] ?? ('KBS-' . date('YmdHis') . '-' . mt_rand(100, 999)),
            'kbs_sent_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->db->insert('hospitality_kbs_declarations', $record);
        return (int) $this->db->insert_id();
    }

    /**
     * Get KBS declarations for police/gendarmerie inspection.
     */
    public function get_kbs_declarations(?string $date = null, ?string $status = null): array
    {
        if (!$this->db->table_exists('hospitality_kbs_declarations')) {
            return [];
        }

        $this->db->select('k.*, s.name as room_name')
            ->from('hospitality_kbs_declarations k')
            ->join('stations s', 's.id = k.room_station_id', 'left');

        if ($date) {
            $this->db->where('DATE(k.checkin_datetime)', date('Y-m-d', strtotime($date)));
        }
        if ($status) {
            $this->db->where('k.kbs_status', $status);
        }

        return $this->db->order_by('k.checkin_datetime DESC')->get()->result_array();
    }

    /**
     * Export KBS XML data compliant with Turkish EGM (AKBS) format.
     */
    public function export_kbs_xml(?string $date = null): string
    {
        $records = $this->get_kbs_declarations($date);
        $facility_code = 'HOTEL-' . strtoupper(substr(md5(base_url()), 0, 8));

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><KBSBildirimListesi/>');
        $xml->addAttribute('TesisKodu', $facility_code);
        $xml->addAttribute('Tarih', $date ?: date('Y-m-d'));

        foreach ($records as $r) {
            $item = $xml->addChild('KonaklayanKisi');
            $item->addChild('TCKimlikNo', htmlspecialchars($r['national_id_or_passport']));
            $item->addChild('KimlikTuru', $r['id_type']);
            $item->addChild('Adi', htmlspecialchars($r['first_name']));
            $item->addChild('Soyadi', htmlspecialchars($r['last_name']));
            $item->addChild('BabaAdi', htmlspecialchars($r['father_name'] ?? ''));
            $item->addChild('AnaAdi', htmlspecialchars($r['mother_name'] ?? ''));
            $item->addChild('DogumTarihi', $r['birth_date'] ?? '');
            $item->addChild('DogumYeri', htmlspecialchars($r['birth_place'] ?? ''));
            $item->addChild('Cinsiyet', $r['gender']);
            $item->addChild('Uyruk', $r['nationality_code']);
            $item->addChild('OdaNo', htmlspecialchars($r['room_name'] ?? ('Oda #' . $r['room_station_id'])));
            $item->addChild('GirisZamani', $r['checkin_datetime']);
            $item->addChild('CikisZamani', $r['checkout_datetime'] ?? '');
            $item->addChild('Plaka', htmlspecialchars($r['vehicle_plate'] ?? ''));
            $item->addChild('Telefon', htmlspecialchars($r['phone'] ?? ''));
        }

        return $xml->asXML();
    }

    /* -------------------------------------------------------------------------
     * 7. HOUSEKEEPING & MAINTENANCE TICKETS
     * ------------------------------------------------------------------------- */

    /**
     * Get active housekeeping tasks board.
     */
    public function get_housekeeping_board(): array
    {
        if (!$this->db->table_exists('hospitality_housekeeping_tasks')) {
            return [];
        }

        return $this->db
            ->select('h.*, s.name as room_name, u.first_name as staff_first_name, u.last_name as staff_last_name')
            ->from('hospitality_housekeeping_tasks h')
            ->join('stations s', 's.id = h.room_station_id', 'left')
            ->join('users u', 'u.id = h.assigned_staff_id', 'left')
            ->order_by('CASE h.priority WHEN "urgent_arrival" THEN 1 WHEN "high" THEN 2 WHEN "normal" THEN 3 ELSE 4 END', 'ASC', false)
            ->order_by('h.created_at', 'DESC')
            ->get()
            ->result_array();
    }

    /**
     * Create housekeeping task.
     */
    public function create_housekeeping_task(int $room_id, string $task_type = 'departure_clean', string $priority = 'normal', ?int $staff_id = null, string $notes = ''): int
    {
        if (!$this->db->table_exists('hospitality_housekeeping_tasks')) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $record = [
            'room_station_id' => $room_id,
            'task_type' => $task_type,
            'priority' => $priority,
            'assigned_staff_id' => $staff_id,
            'status' => 'pending',
            'notes' => $notes,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->db->insert('hospitality_housekeeping_tasks', $record);
        return (int) $this->db->insert_id();
    }

    /**
     * Update housekeeping task status and auto-advance room status.
     */
    public function update_housekeeping_task(int $task_id, string $status, array $checklist = []): bool
    {
        if (!$this->db->table_exists('hospitality_housekeeping_tasks')) {
            return false;
        }

        $task = $this->db->get_where('hospitality_housekeeping_tasks', ['id' => $task_id])->row_array();
        if (!$task) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $update = [
            'status' => $status,
            'updated_at' => $now,
        ];

        if (!empty($checklist)) {
            $update['checklist_results'] = json_encode($checklist, JSON_UNESCAPED_UNICODE);
        }

        if ($status === 'in_progress' && empty($task['started_at'])) {
            $update['started_at'] = $now;
            // Room is being cleaned
            $this->update_room_housekeeping_status((int) $task['room_station_id'], 'cleaning');
        } elseif ($status === 'completed' || $status === 'inspected') {
            $update['completed_at'] = $now;
            // Room is ready and clean!
            $targetRoomStatus = ($status === 'inspected') ? 'inspected' : 'clean';
            $this->update_room_housekeeping_status((int) $task['room_station_id'], $targetRoomStatus);
        }

        return $this->db->update('hospitality_housekeeping_tasks', $update, ['id' => $task_id]);
    }

    /**
     * Maintenance tickets.
     */
    public function get_maintenance_tickets(?string $status = null): array
    {
        if (!$this->db->table_exists('hospitality_maintenance_tickets')) {
            return [];
        }

        $this->db
            ->select('m.*, s.name as room_name, u.first_name as tech_first_name, u.last_name as tech_last_name')
            ->from('hospitality_maintenance_tickets m')
            ->join('stations s', 's.id = m.room_station_id', 'left')
            ->join('users u', 'u.id = m.assigned_technician_id', 'left');

        if ($status) {
            $this->db->where('m.status', $status);
        }

        return $this->db->order_by('m.created_at', 'DESC')->get()->result_array();
    }

    /**
     * Create maintenance ticket.
     */
    public function create_maintenance_ticket(array $data): int
    {
        if (!$this->db->table_exists('hospitality_maintenance_tickets')) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $roomId = (int) ($data['room_station_id'] ?? $data['room_id']);

        $record = [
            'room_station_id' => $roomId,
            'issue_category' => $data['issue_category'] ?? 'hvac_ac',
            'title' => trim((string) ($data['title'] ?? 'Arıza Kaydı')),
            'description' => trim((string) ($data['description'] ?? '')),
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open',
            'reported_by_staff_id' => !empty($data['reported_by_staff_id']) ? (int) $data['reported_by_staff_id'] : null,
            'assigned_technician_id' => !empty($data['assigned_technician_id']) ? (int) $data['assigned_technician_id'] : null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->db->insert('hospitality_maintenance_tickets', $record);
        $ticket_id = (int) $this->db->insert_id();

        // If priority is critical, mark room as maintenance
        if (($data['priority'] ?? '') === 'critical' || !empty($data['block_room'])) {
            $this->update_room_housekeeping_status($roomId, 'maintenance');
        }

        return $ticket_id;
    }

    /**
     * Resolve maintenance ticket.
     */
    public function resolve_maintenance_ticket(int $ticket_id, string $resolution_notes = ''): bool
    {
        if (!$this->db->table_exists('hospitality_maintenance_tickets')) {
            return false;
        }

        $ticket = $this->db->get_where('hospitality_maintenance_tickets', ['id' => $ticket_id])->row_array();
        if (!$ticket) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->update('hospitality_maintenance_tickets', [
            'status' => 'resolved',
            'resolved_at' => $now,
            'resolution_notes' => $resolution_notes,
            'updated_at' => $now,
        ], ['id' => $ticket_id]);

        // Put room back to dirty so housekeeping can inspect/clean it before guest arrival
        $this->update_room_housekeeping_status((int) $ticket['room_station_id'], 'dirty');

        return true;
    }

    /* -------------------------------------------------------------------------
     * 8. NIGHT AUDIT (GÜN SONU DEVRİ, ADR, REVPAR & OTOMATİK ODA ÜCRETİ)
     * ------------------------------------------------------------------------- */

    /**
     * Run Night Audit:
     * - Posts nightly room charge to open folios for all occupied rooms
     * - Calculates RevPAR, ADR, Occupancy Rate %
     * - Computes %2 Accommodation tax and KDV
     * - Stores immutable daily snapshot in ea_hospitality_night_audits
     */
    public function run_night_audit(string $audit_date, int $user_id, bool $post_room_charges = true): array
    {
        $audit_date = date('Y-m-d', strtotime($audit_date));
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();

        // Check if audit already completed
        $existing = $this->db->get_where('hospitality_night_audits', ['audit_date' => $audit_date])->row_array();

        $rooms = $this->get_rooms_overview($audit_date);
        $total_rooms = count($rooms);
        $occupied_rooms = 0;
        $available_rooms = 0;
        $out_of_order_rooms = 0;

        $total_room_revenue = 0.00;
        $total_extra_revenue = 0.00;

        foreach ($rooms as $r) {
            $st = $r['status'];
            if ($st === 'occupied') {
                $occupied_rooms++;
                $nightlyRate = (float) $r['base_price'];

                // Automatically post room charge if active guest exists
                if ($post_room_charges && !empty($r['folio_id'])) {
                    $folio_id = (int) $r['folio_id'];

                    // Check if already posted for this date
                    $alreadyPosted = $this->db
                        ->where('id_adisyons', $folio_id)
                        ->where('item_type', 'room_charge')
                        ->like('notes', $audit_date)
                        ->count_all_results('adisyon_items');

                    if ($alreadyPosted === 0 && $nightlyRate > 0) {
                        $this->adisyons_model->add_item($folio_id, [
                            'item_type' => 'room_charge',
                            'name' => 'Günlük Konaklama Ücreti (' . date('d.m.Y', strtotime($audit_date)) . ')',
                            'unit_price' => $nightlyRate,
                            'quantity' => 1.0,
                            'tax_rate' => 0.00,
                            'notes' => 'Gece Denetimi / Night Audit Tahakkuku [' . $audit_date . ']',
                        ]);
                    }
                }

                $total_room_revenue += $nightlyRate;
            } elseif ($st === 'maintenance') {
                $out_of_order_rooms++;
            } else {
                $available_rooms++;
            }
        }

        // Tally extra charges from folios opened or active on this date
        if ($this->db->table_exists('adisyon_items')) {
            $extraSum = $this->db
                ->select_sum('total_amount')
                ->where('item_type !=', 'room_charge')
                ->like('created_at', $audit_date, 'after')
                ->get('adisyon_items')
                ->row_array();
            $total_extra_revenue = (float) ($extraSum['total_amount'] ?? 0.00);
        }

        $saleable_rooms = max(1, $total_rooms - $out_of_order_rooms);
        $occupancy_rate = round(($occupied_rooms / $saleable_rooms) * 100, 2);
        $adr = $occupied_rooms > 0 ? round($total_room_revenue / $occupied_rooms, 2) : 0.00;
        $revpar = $total_rooms > 0 ? round($total_room_revenue / $total_rooms, 2) : 0.00;

        $accommodation_tax = round($total_room_revenue * 0.02, 2);
        $kdv_total = round(($total_room_revenue + $total_extra_revenue) * 0.10, 2);
        $grand_total = $total_room_revenue + $total_extra_revenue;

        // Arrivals and departures count
        $checkins_count = $this->db
            ->where('is_unavailability', 0)
            ->where('DATE(start_datetime)', $audit_date)
            ->count_all_results('appointments');

        $checkouts_count = $this->db
            ->where('is_unavailability', 0)
            ->where('DATE(end_datetime)', $audit_date)
            ->count_all_results('appointments');

        $audit_data = [
            'audit_date' => $audit_date,
            'performed_by_user_id' => $user_id,
            'total_rooms' => $total_rooms,
            'occupied_rooms' => $occupied_rooms,
            'available_rooms' => $available_rooms,
            'out_of_order_rooms' => $out_of_order_rooms,
            'occupancy_rate' => $occupancy_rate,
            'adr' => $adr,
            'revpar' => $revpar,
            'total_room_revenue' => $total_room_revenue,
            'total_extra_revenue' => $total_extra_revenue,
            'accommodation_tax_total' => $accommodation_tax,
            'kdv_total' => $kdv_total,
            'grand_total' => $grand_total,
            'checkins_count' => $checkins_count,
            'checkouts_count' => $checkouts_count,
            'notes' => 'Gece Denetimi başarıyla tamamlandı. Doluluk %' . $occupancy_rate . ', RevPAR: ₺' . $revpar,
            'is_closed' => 1,
            'created_at' => $now,
        ];

        if ($existing) {
            $this->db->update('hospitality_night_audits', $audit_data, ['id' => $existing['id']]);
            $audit_id = (int) $existing['id'];
        } else {
            $this->db->insert('hospitality_night_audits', $audit_data);
            $audit_id = (int) $this->db->insert_id();
        }

        $this->db->trans_complete();

        $audit_data['audit_id'] = $audit_id;
        $audit_data['success'] = true;
        return $audit_data;
    }

    /**
     * Get Night Audit history.
     */
    public function get_night_audit_history(int $limit = 30): array
    {
        if (!$this->db->table_exists('hospitality_night_audits')) {
            return [];
        }

        return $this->db
            ->order_by('audit_date', 'DESC')
            ->limit($limit)
            ->get('hospitality_night_audits')
            ->result_array();
    }

    /* -------------------------------------------------------------------------
     * 9. CHANNEL MANAGER & 2-WAY ICAL SYNC (AIRBNB, BOOKING.COM, VRBO)
     * ------------------------------------------------------------------------- */

    /**
     * Generate standard RFC 5545 iCalendar feed (.ics) for external OTAs.
     */
    public function generate_room_ical_feed(int $room_id): string
    {
        $station = $this->db->get_where('stations', ['id' => $room_id])->row_array();
        $roomName = $station['name'] ?? ('Room-' . $room_id);

        $appts = $this->db
            ->where('id_stations', $room_id)
            ->where_not_in('status', ['cancelled'])
            ->order_by('start_datetime', 'ASC')
            ->get('appointments')
            ->result_array();

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//BooKi Hospitality PMS//Room " . $room_id . "//TR\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        $ics .= "X-WR-CALNAME:" . addslashes($roomName) . " Bookings\r\n";

        foreach ($appts as $a) {
            $start = gmdate('Ymd\THis\Z', strtotime($a['start_datetime']));
            $end = gmdate('Ymd\THis\Z', strtotime($a['end_datetime']));
            $uid = 'RES-' . $a['id'] . '@' . parse_url(base_url(), PHP_URL_HOST);

            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= "UID:" . $uid . "\r\n";
            $ics .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            $ics .= "DTSTART:" . $start . "\r\n";
            $ics .= "DTEND:" . $end . "\r\n";
            $ics .= "SUMMARY:Reserved (" . addslashes($roomName) . ")\r\n";
            $ics .= "DESCRIPTION:BooKi PMS Hospitality Reservation #" . $a['id'] . "\r\n";
            $ics .= "STATUS:CONFIRMED\r\n";
            $ics .= "END:VEVENT\r\n";
        }

        $ics .= "END:VCALENDAR\r\n";
        return $ics;
    }

    /**
     * Sync inbound iCal from OTA (Airbnb / Booking.com).
     */
    public function sync_inbound_ical(int $room_id, ?string $ical_url = null): array
    {
        $station = $this->db->get_where('stations', ['id' => $room_id])->row_array();
        if (!$station) {
            return ['success' => false, 'message' => 'Oda bulunamadı.'];
        }

        $url = $ical_url ?: ($station['ical_import_url'] ?? '');
        if (empty($url)) {
            return ['success' => false, 'message' => 'iCal URL adresi tanımlı değil.'];
        }

        $eventsCount = 0;
        $now = date('Y-m-d H:i:s');

        try {
            $context = stream_context_create([
                'http' => ['timeout' => 10],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $rawIcs = @file_get_contents($url, false, $context);

            if ($rawIcs === false || !str_contains($rawIcs, 'BEGIN:VCALENDAR')) {
                throw new RuntimeException('iCal takvim akışı indirilemedi veya geçersiz.');
            }

            // Simple parser for VEVENT blocks
            preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', $rawIcs, $matches);
            $events = $matches[1] ?? [];

            foreach ($events as $ev) {
                if (preg_match('/DTSTART;?.*?:([0-9TZ]+)/', $ev, $sMatch) &&
                    preg_match('/DTEND;?.*?:([0-9TZ]+)/', $ev, $eMatch)) {
                    $dtStart = date('Y-m-d H:i:s', strtotime($sMatch[1]));
                    $dtEnd = date('Y-m-d H:i:s', strtotime($eMatch[1]));

                    // Insert blocked period or appointment if not overlapping
                    $exists = $this->db
                        ->where('id_stations', $room_id)
                        ->where('start_datetime', $dtStart)
                        ->where('end_datetime', $dtEnd)
                        ->count_all_results('appointments');

                    if ($exists === 0) {
                        $this->appointments_model->add([
                            'id_services' => 1,
                            'id_users_provider' => 1,
                            'id_users_customer' => 1,
                            'id_stations' => $room_id,
                            'start_datetime' => $dtStart,
                            'end_datetime' => $dtEnd,
                            'is_unavailability' => 0,
                            'status' => 'confirmed',
                            'notes' => 'OTA Channel Manager Senkronizasyonu (iCal Entegrasyonu)',
                        ]);
                        $eventsCount++;
                    }
                }
            }

            if ($this->db->table_exists('hospitality_channel_sync_logs')) {
                $this->db->insert('hospitality_channel_sync_logs', [
                    'room_station_id' => $room_id,
                    'channel_name' => str_contains($url, 'airbnb') ? 'airbnb' : (str_contains($url, 'booking') ? 'booking.com' : 'ical_inbound'),
                    'sync_type' => 'inbound',
                    'events_count' => $eventsCount,
                    'sync_status' => 'success',
                    'message' => $eventsCount . ' adet dış rezervasyon senkronize edildi.',
                    'sync_timestamp' => $now,
                ]);
            }

            return [
                'success' => true,
                'events_synced' => $eventsCount,
                'message' => 'iCal takvimi senkronize edildi. ' . $eventsCount . ' yeni rezervasyon işlendi.',
            ];
        } catch (Throwable $e) {
            if ($this->db->table_exists('hospitality_channel_sync_logs')) {
                $this->db->insert('hospitality_channel_sync_logs', [
                    'room_station_id' => $room_id,
                    'channel_name' => 'ical_inbound',
                    'sync_type' => 'inbound',
                    'events_count' => 0,
                    'sync_status' => 'failed',
                    'message' => $e->getMessage(),
                    'sync_timestamp' => $now,
                ]);
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /* -------------------------------------------------------------------------
     * 10. EXECUTIVE KPI DASHBOARD STATS
     * ------------------------------------------------------------------------- */

    /**
     * Get real-time hospitality dashboard KPI counters.
     */
    public function get_hospitality_dashboard_stats(?string $date = null): array
    {
        $date = $date ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
        $rooms = $this->get_rooms_overview($date);

        $total_rooms = count($rooms);
        $clean_rooms = 0;
        $occupied_rooms = 0;
        $dirty_rooms = 0;
        $maintenance_rooms = 0;
        $inhouse_guests = 0;

        foreach ($rooms as $r) {
            $st = $r['status'];
            if ($st === 'clean' || $st === 'inspected') {
                $clean_rooms++;
            } elseif ($st === 'occupied') {
                $occupied_rooms++;
                if (!empty($r['active_guest'])) {
                    $inhouse_guests++;
                }
            } elseif ($st === 'dirty' || $st === 'cleaning') {
                $dirty_rooms++;
            } elseif ($st === 'maintenance') {
                $maintenance_rooms++;
            }
        }

        // Arrivals and Departures today
        $arrivals_today = $this->db
            ->where('is_unavailability', 0)
            ->where('DATE(start_datetime)', $date)
            ->where_not_in('status', ['cancelled'])
            ->count_all_results('appointments');

        $departures_today = $this->db
            ->where('is_unavailability', 0)
            ->where('DATE(end_datetime)', $date)
            ->where_not_in('status', ['cancelled'])
            ->count_all_results('appointments');

        // Total open folios sum
        $open_folio_sum = 0.00;
        if ($this->db->table_exists('adisyons')) {
            $res = $this->db->select_sum('total_amount')->select_sum('paid_amount')->where('status', 'open')->get('adisyons')->row_array();
            $open_folio_sum = max(0.00, (float) ($res['total_amount'] ?? 0) - (float) ($res['paid_amount'] ?? 0));
        }

        $saleable = max(1, $total_rooms - $maintenance_rooms);
        $occupancy_pct = round(($occupied_rooms / $saleable) * 100, 1);

        return [
            'date' => $date,
            'total_rooms' => $total_rooms,
            'clean_rooms' => $clean_rooms,
            'occupied_rooms' => $occupied_rooms,
            'dirty_rooms' => $dirty_rooms,
            'maintenance_rooms' => $maintenance_rooms,
            'inhouse_guests' => $inhouse_guests,
            'arrivals_today' => $arrivals_today,
            'departures_today' => $departures_today,
            'occupancy_pct' => $occupancy_pct,
            'open_folio_balance' => $open_folio_sum,
        ];
    }

    /* -------------------------------------------------------------------------
     * 10. PRESET PROPERTY & ROOM BLUEPRINT TEMPLATES
     * ------------------------------------------------------------------------- */

    /**
     * Get predefined property room & floor blueprint templates.
     */
    public function get_property_templates(): array
    {
        return [
            'boutique_hotel' => [
                'key' => 'boutique_hotel',
                'name' => 'Lüks Butik Otel Şablonu',
                'description' => '3 Katlı mimari yerleşim: Zemin bahçe odaları, 1. kat deniz manzaralı deluxe odalar ve çatı katı jakuzili balayı süiti.',
                'badge' => 'Popüler Butik',
                'icon' => 'fa-hotel',
                'room_types' => [
                    [
                        'name' => 'Standart Bahçe Manzaralı',
                        'code' => 'STD-GRD',
                        'base_capacity_adults' => 2,
                        'base_capacity_children' => 1,
                        'max_capacity' => 3,
                        'base_price_per_night' => 2400.00,
                        'bed_type' => '1 King Çift Kişilik Yatak',
                        'room_size_sqm' => 26,
                        'amenities' => ['wifi', 'ac', 'tv', 'minibar', 'balcony', 'safe'],
                    ],
                    [
                        'name' => 'Deluxe Teras & Deniz Manzara',
                        'code' => 'DLX-SEA',
                        'base_capacity_adults' => 2,
                        'base_capacity_children' => 1,
                        'max_capacity' => 3,
                        'base_price_per_night' => 3900.00,
                        'bed_type' => '1 King Yatak + 1 Tekli Yatak',
                        'room_size_sqm' => 38,
                        'amenities' => ['wifi', 'ac', 'tv', 'jacuzzi', 'sea_view', 'terrace', 'minibar', 'espresso'],
                    ],
                    [
                        'name' => 'Balayı Çatı Penthouse & Jakuzi',
                        'code' => 'PNT-BLY',
                        'base_capacity_adults' => 2,
                        'base_capacity_children' => 0,
                        'max_capacity' => 2,
                        'base_price_per_night' => 5800.00,
                        'bed_type' => '1 Ultra King Özel Yatak',
                        'room_size_sqm' => 55,
                        'amenities' => ['wifi', 'ac', 'jacuzzi', 'sea_view', 'fireplace', 'private_terrace', 'vip_bar'],
                    ],
                ],
                'rooms' => [
                    ['name' => 'Oda 101 - Bahçe Standart', 'floor_building' => '1. Kat (Bahçe Cephe)', 'type_code' => 'STD-GRD'],
                    ['name' => 'Oda 102 - Bahçe Standart', 'floor_building' => '1. Kat (Bahçe Cephe)', 'type_code' => 'STD-GRD'],
                    ['name' => 'Oda 103 - Bahçe Standart', 'floor_building' => '1. Kat (Bahçe Cephe)', 'type_code' => 'STD-GRD'],
                    ['name' => 'Oda 201 - Deluxe Deniz Teras', 'floor_building' => '2. Kat (Deniz Cephe)', 'type_code' => 'DLX-SEA'],
                    ['name' => 'Oda 202 - Deluxe Deniz Teras', 'floor_building' => '2. Kat (Deniz Cephe)', 'type_code' => 'DLX-SEA'],
                    ['name' => 'Suit 301 - Balayı Çatı Penthouse', 'floor_building' => '3. Kat (Çatı Terası)', 'type_code' => 'PNT-BLY'],
                ],
            ],
            'bungalow_resort' => [
                'key' => 'bungalow_resort',
                'name' => 'Doğa Bungalov & Dağ Köyü Şablonu',
                'description' => 'Havuz başı jakuzili müstakil ahşap bungalovlar, şömineli orman taş villaları ve panoramik glamping çadırları.',
                'badge' => 'Bungalov & Doğa',
                'icon' => 'fa-campground',
                'room_types' => [
                    [
                        'name' => 'Jakuzili Havuz Başı Ahşap Bungalov',
                        'code' => 'BNG-JAC',
                        'base_capacity_adults' => 2,
                        'base_capacity_children' => 1,
                        'max_capacity' => 3,
                        'base_price_per_night' => 4400.00,
                        'bed_type' => '1 King Yatak + 1 Açılır Kanepe',
                        'room_size_sqm' => 34,
                        'amenities' => ['wifi', 'ac', 'jacuzzi', 'pool_access', 'patio', 'hammock', 'minibar'],
                    ],
                    [
                        'name' => 'Şömineli Müstakil Taş Villa',
                        'code' => 'TAS-VIL',
                        'base_capacity_adults' => 4,
                        'base_capacity_children' => 2,
                        'max_capacity' => 6,
                        'base_price_per_night' => 6200.00,
                        'bed_type' => '1 King Yatak + 2 Tekli Yatak',
                        'room_size_sqm' => 58,
                        'amenities' => ['wifi', 'ac', 'fireplace', 'private_pool', 'kitchen', 'barbecue', 'mountain_view'],
                    ],
                    [
                        'name' => 'Lüks Kubbe Glamping (Sky Dome)',
                        'code' => 'GLM-DOM',
                        'base_capacity_adults' => 2,
                        'base_capacity_children' => 0,
                        'max_capacity' => 2,
                        'base_price_per_night' => 3600.00,
                        'bed_type' => '1 Yuvarlak Panoramik Yatak',
                        'room_size_sqm' => 30,
                        'amenities' => ['wifi', 'ac', 'skylight', 'terrace', 'nature_view', 'coffee_machine'],
                    ],
                ],
                'rooms' => [
                    ['name' => 'Bungalov 101 - Jakuzili Havuz Başı', 'floor_building' => 'Havuz Başı Bungalovlar', 'type_code' => 'BNG-JAC'],
                    ['name' => 'Bungalov 102 - Jakuzili Havuz Başı', 'floor_building' => 'Havuz Başı Bungalovlar', 'type_code' => 'BNG-JAC'],
                    ['name' => 'Bungalov 103 - Jakuzili Havuz Başı', 'floor_building' => 'Havuz Başı Bungalovlar', 'type_code' => 'BNG-JAC'],
                    ['name' => 'Taş Villa 201 - Şömineli Özel Havuz', 'floor_building' => 'Orman & Taş Villalar', 'type_code' => 'TAS-VIL'],
                    ['name' => 'Taş Villa 202 - Şömineli Özel Havuz', 'floor_building' => 'Orman & Taş Villalar', 'type_code' => 'TAS-VIL'],
                    ['name' => 'Glamping Dome 301 - Yıldız Gözlem', 'floor_building' => 'Vadi & Glamping Tepesi', 'type_code' => 'GLM-DOM'],
                    ['name' => 'Glamping Dome 302 - Yıldız Gözlem', 'floor_building' => 'Vadi & Glamping Tepesi', 'type_code' => 'GLM-DOM'],
                ],
            ],
            'apart_pension' => [
                'key' => 'apart_pension',
                'name' => 'Apart Otel & Pansiyon Şablonu',
                'description' => 'Geniş aileler için tam donanımlı mutfaklı 1+1 apart daireler, balkonlu stüdyolar ve ekonomik odalar.',
                'badge' => 'Apart & Pansiyon',
                'icon' => 'fa-door-open',
                'room_types' => [
                    [
                        'name' => '1+1 Mutfaklı Aile Dairesi',
                        'code' => 'APT-11',
                        'base_capacity_adults' => 3,
                        'base_capacity_children' => 2,
                        'max_capacity' => 5,
                        'base_price_per_night' => 2800.00,
                        'bed_type' => '1 King Yatak + 1 Çift Kişilik Çekyat',
                        'room_size_sqm' => 48,
                        'amenities' => ['wifi', 'ac', 'full_kitchen', 'washing_machine', 'balcony', 'fridge'],
                    ],
                    [
                        'name' => 'Stüdyo Mutfaklı Apart',
                        'code' => 'STD-APT',
                        'base_capacity_adults' => 2,
                        'base_capacity_children' => 1,
                        'max_capacity' => 3,
                        'base_price_per_night' => 1950.00,
                        'bed_type' => '1 Çift Kişilik Yatak',
                        'room_size_sqm' => 32,
                        'amenities' => ['wifi', 'ac', 'kitchenette', 'balcony', 'fridge'],
                    ],
                    [
                        'name' => 'Ekonomik Pansiyon Odası',
                        'code' => 'ECO-PNS',
                        'base_capacity_adults' => 2,
                        'base_capacity_children' => 0,
                        'max_capacity' => 2,
                        'base_price_per_night' => 1400.00,
                        'bed_type' => '2 Tek Kişilik Yatak',
                        'room_size_sqm' => 20,
                        'amenities' => ['wifi', 'ac', 'tv', 'shower'],
                    ],
                ],
                'rooms' => [
                    ['name' => 'Daire 1 - 1+1 Mutfaklı Aile', 'floor_building' => 'A Blok - 1. Kat', 'type_code' => 'APT-11'],
                    ['name' => 'Daire 2 - 1+1 Mutfaklı Aile', 'floor_building' => 'A Blok - 1. Kat', 'type_code' => 'APT-11'],
                    ['name' => 'Daire 3 - Stüdyo Mutfaklı', 'floor_building' => 'A Blok - Zemin Kat', 'type_code' => 'STD-APT'],
                    ['name' => 'Daire 4 - Stüdyo Mutfaklı', 'floor_building' => 'A Blok - Zemin Kat', 'type_code' => 'STD-APT'],
                    ['name' => 'Oda 201 - Ekonomik Çift', 'floor_building' => 'B Blok - 2. Kat', 'type_code' => 'ECO-PNS'],
                    ['name' => 'Oda 202 - Ekonomik İki Tekli', 'floor_building' => 'B Blok - 2. Kat', 'type_code' => 'ECO-PNS'],
                ],
            ],
        ];
    }

    /**
     * Apply a preset property & room blueprint template.
     */
    public function apply_property_template(string $template_key): array
    {
        $templates = $this->get_property_templates();
        if (!isset($templates[$template_key])) {
            throw new InvalidArgumentException('Geçersiz tesis şablonu: ' . $template_key);
        }

        $tpl = $templates[$template_key];
        $now = date('Y-m-d H:i:s');
        $created_types = 0;
        $created_rooms = 0;

        $this->db->trans_start();

        // 1. Create or resolve Room Types
        $type_id_map = [];
        foreach ($tpl['room_types'] as $rt) {
            $existing = $this->db->get_where('hospitality_room_types', ['code' => $rt['code']])->row_array();
            if ($existing) {
                $typeId = (int) $existing['id'];
            } else {
                $typeData = [
                    'name' => $rt['name'],
                    'code' => $rt['code'],
                    'base_capacity_adults' => $rt['base_capacity_adults'],
                    'base_capacity_children' => $rt['base_capacity_children'],
                    'max_capacity' => $rt['max_capacity'],
                    'base_price_per_night' => $rt['base_price_per_night'],
                    'rate_plan_default' => 'BB',
                    'bed_type' => $rt['bed_type'],
                    'room_size_sqm' => $rt['room_size_sqm'],
                    'amenities' => json_encode($rt['amenities'], JSON_UNESCAPED_UNICODE),
                    'description' => $rt['name'] . ' oda şablonu (' . $rt['room_size_sqm'] . ' m², ' . $rt['bed_type'] . ').',
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $this->db->insert('hospitality_room_types', $typeData);
                $typeId = (int) $this->db->insert_id();
                $created_types++;
            }
            $type_id_map[$rt['code']] = $typeId;
        }

        // 2. Create Rooms
        foreach ($tpl['rooms'] as $rm) {
            $typeId = $type_id_map[$rm['type_code']] ?? null;
            $typeObj = null;
            foreach ($tpl['room_types'] as $t) {
                if ($t['code'] === $rm['type_code']) {
                    $typeObj = $t;
                    break;
                }
            }

            $pin = (string) mt_rand(100000, 999999);
            $stationData = [
                'name' => $rm['name'],
                'capacity' => $typeObj['max_capacity'] ?? 2,
                'status' => 'clean',
                'notes' => json_encode([
                    'floor' => $rm['floor_building'],
                    'door_lock_code' => $pin,
                    'room_type_id' => $typeId,
                    'bed_type' => $typeObj['bed_type'] ?? '',
                    'room_size_sqm' => $typeObj['room_size_sqm'] ?? 25,
                ], JSON_UNESCAPED_UNICODE),
            ];

            if ($this->db->field_exists('room_type_id', 'stations')) {
                $stationData['room_type_id'] = $typeId;
            }
            if ($this->db->field_exists('floor_building', 'stations')) {
                $stationData['floor_building'] = $rm['floor_building'];
            }
            if ($this->db->field_exists('housekeeping_status', 'stations')) {
                $stationData['housekeeping_status'] = 'clean';
            }
            if ($this->db->field_exists('door_lock_code', 'stations')) {
                $stationData['door_lock_code'] = $pin;
            }

            $this->db->insert('stations', $stationData);
            $created_rooms++;
        }

        $this->db->trans_complete();

        return [
            'success' => true,
            'template_key' => $template_key,
            'template_name' => $tpl['name'],
            'created_room_types' => $created_types,
            'created_rooms' => $created_rooms,
            'message' => $tpl['name'] . ' başarıyla uygulandı (' . $created_rooms . ' oda ve ' . count($tpl['room_types']) . ' oda tipi şablonu oluşturuldu).',
        ];
    }
}
