<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration 177: World-Class Hospitality & Hotel PMS Suite.
 *
 * Implements benchmark-grade hotel PMS features inspired by:
 * QloApps, hotel-mgmt-system, HotinGo, Django HMS, and HotelDruid.
 *
 * 1. Room Types & Categories (ea_hospitality_room_types)
 * 2. Room Inventory & Attributes (ea_stations extensions)
 * 3. Dynamic Rate Plans & Seasonal Pricing (ea_hospitality_rate_plans)
 * 4. KBS (Kimlik Bildirim Sistemi - Turkish Law 1774 Police Reporting)
 * 5. Housekeeping Task Engine & Inspection (ea_hospitality_housekeeping_tasks)
 * 6. Maintenance & Room Fault Tickets (ea_hospitality_maintenance_tickets)
 * 7. Night Audit & Gün Sonu Devri (ea_hospitality_night_audits)
 * 8. Channel Manager & iCal Sync Logs (ea_hospitality_channel_sync_logs)
 * 9. Appointment Hotel Extensions (board type, pax, checkin/out timestamps, door PIN)
 */
class Migration_World_class_hospitality_pms_suite extends App_Migration
{
    public function up(): void
    {
        // 1. Room Types Table
        if (!$this->db->table_exists('hospitality_room_types')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hospitality_room_types') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(120) NOT NULL,
                    `code` VARCHAR(32) NOT NULL,
                    `base_capacity_adults` INT NOT NULL DEFAULT 2,
                    `base_capacity_children` INT NOT NULL DEFAULT 1,
                    `max_capacity` INT NOT NULL DEFAULT 3,
                    `base_price_per_night` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `rate_plan_default` VARCHAR(16) NOT NULL DEFAULT 'BB',
                    `amenities` TEXT NULL,
                    `bed_type` VARCHAR(64) NOT NULL DEFAULT '1 King Bed',
                    `room_size_sqm` INT NOT NULL DEFAULT 25,
                    `description` TEXT NULL,
                    `image_url` VARCHAR(255) NULL,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_room_type_code` (`code`),
                    KEY `idx_room_type_active` (`is_active`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 2. Extend stations table for Hotel PMS attributes
        if ($this->db->table_exists('stations')) {
            if (!$this->db->field_exists('room_type_id', 'stations')) {
                $this->dbforge->add_column('stations', [
                    'room_type_id' => [
                        'type' => 'INT',
                        'unsigned' => true,
                        'null' => true,
                        'default' => null,
                        'after' => 'name',
                    ],
                ]);
            }
            if (!$this->db->field_exists('floor_building', 'stations')) {
                $this->dbforge->add_column('stations', [
                    'floor_building' => [
                        'type' => 'VARCHAR',
                        'constraint' => 64,
                        'default' => 'Ana Bina',
                        'null' => true,
                        'after' => 'room_type_id',
                    ],
                ]);
            }
            if (!$this->db->field_exists('housekeeping_status', 'stations')) {
                $this->dbforge->add_column('stations', [
                    'housekeeping_status' => [
                        'type' => 'VARCHAR',
                        'constraint' => 32,
                        'default' => 'clean', // clean, dirty, cleaning, inspected, occupied, maintenance, do_not_disturb
                        'null' => true,
                        'after' => 'status',
                    ],
                ]);
            }
            if (!$this->db->field_exists('door_lock_code', 'stations')) {
                $this->dbforge->add_column('stations', [
                    'door_lock_code' => [
                        'type' => 'VARCHAR',
                        'constraint' => 32,
                        'null' => true,
                        'default' => null,
                    ],
                ]);
            }
            if (!$this->db->field_exists('ical_export_token', 'stations')) {
                $this->dbforge->add_column('stations', [
                    'ical_export_token' => [
                        'type' => 'VARCHAR',
                        'constraint' => 64,
                        'null' => true,
                        'default' => null,
                    ],
                ]);
            }
            if (!$this->db->field_exists('ical_import_url', 'stations')) {
                $this->dbforge->add_column('stations', [
                    'ical_import_url' => [
                        'type' => 'VARCHAR',
                        'constraint' => 512,
                        'null' => true,
                        'default' => null,
                    ],
                ]);
            }
            if (!$this->db->field_exists('last_inspected_at', 'stations')) {
                $this->dbforge->add_column('stations', [
                    'last_inspected_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'default' => null,
                    ],
                ]);
            }
            if (!$this->db->field_exists('inspected_by_user_id', 'stations')) {
                $this->dbforge->add_column('stations', [
                    'inspected_by_user_id' => [
                        'type' => 'INT',
                        'unsigned' => true,
                        'null' => true,
                        'default' => null,
                    ],
                ]);
            }
        }

        // 3. Dynamic Rate Plans
        if (!$this->db->table_exists('hospitality_rate_plans')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hospitality_rate_plans') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `room_type_id` INT UNSIGNED NULL,
                    `name` VARCHAR(120) NOT NULL,
                    `board_type` VARCHAR(16) NOT NULL DEFAULT 'BB',
                    `start_date` DATE NULL,
                    `end_date` DATE NULL,
                    `price_multiplier` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
                    `fixed_price` DECIMAL(10,2) NULL,
                    `weekend_price_delta` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `min_stay_nights` INT NOT NULL DEFAULT 1,
                    `cancellation_policy` VARCHAR(255) NULL,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_rate_room_type` (`room_type_id`),
                    KEY `idx_rate_dates` (`start_date`, `end_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 4. Turkish KBS (Kimlik Bildirim Sistemi - Emniyet/Jandarma)
        if (!$this->db->table_exists('hospitality_kbs_declarations')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hospitality_kbs_declarations') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_appointments` INT UNSIGNED NULL,
                    `id_users_customer` INT UNSIGNED NULL,
                    `room_station_id` INT UNSIGNED NOT NULL,
                    `national_id_or_passport` VARCHAR(64) NOT NULL,
                    `id_type` VARCHAR(20) NOT NULL DEFAULT 'TC',
                    `first_name` VARCHAR(100) NOT NULL,
                    `last_name` VARCHAR(100) NOT NULL,
                    `father_name` VARCHAR(100) NULL,
                    `mother_name` VARCHAR(100) NULL,
                    `birth_date` DATE NULL,
                    `birth_place` VARCHAR(100) NULL,
                    `gender` VARCHAR(10) NOT NULL DEFAULT 'M',
                    `nationality_code` VARCHAR(3) NOT NULL DEFAULT 'TUR',
                    `vehicle_plate` VARCHAR(32) NULL,
                    `phone` VARCHAR(32) NULL,
                    `checkin_datetime` DATETIME NOT NULL,
                    `checkout_datetime` DATETIME NULL,
                    `kbs_status` VARCHAR(32) NOT NULL DEFAULT 'pending',
                    `kbs_reference_code` VARCHAR(100) NULL,
                    `kbs_sent_at` DATETIME NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_kbs_room` (`room_station_id`),
                    KEY `idx_kbs_customer` (`id_users_customer`),
                    KEY `idx_kbs_id_num` (`national_id_or_passport`),
                    KEY `idx_kbs_status` (`kbs_status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 5. Housekeeping Task Engine
        if (!$this->db->table_exists('hospitality_housekeeping_tasks')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hospitality_housekeeping_tasks') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `room_station_id` INT UNSIGNED NOT NULL,
                    `task_type` VARCHAR(32) NOT NULL DEFAULT 'departure_clean',
                    `priority` VARCHAR(20) NOT NULL DEFAULT 'normal',
                    `assigned_staff_id` INT UNSIGNED NULL,
                    `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
                    `started_at` DATETIME NULL,
                    `completed_at` DATETIME NULL,
                    `notes` TEXT NULL,
                    `checklist_results` TEXT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hk_room` (`room_station_id`),
                    KEY `idx_hk_status` (`status`),
                    KEY `idx_hk_assigned` (`assigned_staff_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 6. Maintenance & Room Fault Tickets
        if (!$this->db->table_exists('hospitality_maintenance_tickets')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hospitality_maintenance_tickets') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `room_station_id` INT UNSIGNED NOT NULL,
                    `issue_category` VARCHAR(32) NOT NULL DEFAULT 'hvac_ac',
                    `title` VARCHAR(150) NOT NULL,
                    `description` TEXT NULL,
                    `priority` VARCHAR(20) NOT NULL DEFAULT 'medium',
                    `status` VARCHAR(32) NOT NULL DEFAULT 'open',
                    `reported_by_staff_id` INT UNSIGNED NULL,
                    `assigned_technician_id` INT UNSIGNED NULL,
                    `resolved_at` DATETIME NULL,
                    `resolution_notes` TEXT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_maint_room` (`room_station_id`),
                    KEY `idx_maint_status` (`status`),
                    KEY `idx_maint_priority` (`priority`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 7. Night Audit Table
        if (!$this->db->table_exists('hospitality_night_audits')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hospitality_night_audits') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `audit_date` DATE NOT NULL,
                    `performed_by_user_id` INT UNSIGNED NOT NULL,
                    `total_rooms` INT NOT NULL DEFAULT 0,
                    `occupied_rooms` INT NOT NULL DEFAULT 0,
                    `available_rooms` INT NOT NULL DEFAULT 0,
                    `out_of_order_rooms` INT NOT NULL DEFAULT 0,
                    `occupancy_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                    `adr` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `revpar` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `total_room_revenue` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                    `total_extra_revenue` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                    `accommodation_tax_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `kdv_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `grand_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                    `checkins_count` INT NOT NULL DEFAULT 0,
                    `checkouts_count` INT NOT NULL DEFAULT 0,
                    `notes` TEXT NULL,
                    `is_closed` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_audit_date` (`audit_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 8. Channel Sync Logs
        if (!$this->db->table_exists('hospitality_channel_sync_logs')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hospitality_channel_sync_logs') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `room_station_id` INT UNSIGNED NOT NULL,
                    `channel_name` VARCHAR(64) NOT NULL,
                    `sync_type` VARCHAR(20) NOT NULL,
                    `events_count` INT NOT NULL DEFAULT 0,
                    `sync_status` VARCHAR(20) NOT NULL DEFAULT 'success',
                    `message` TEXT NULL,
                    `sync_timestamp` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_sync_room` (`room_station_id`),
                    KEY `idx_sync_time` (`sync_timestamp`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 9. Appointments Hotel Extensions
        if ($this->db->table_exists('appointments')) {
            if (!$this->db->field_exists('hospitality_board_type', 'appointments')) {
                $this->dbforge->add_column('appointments', [
                    'hospitality_board_type' => [
                        'type' => 'VARCHAR',
                        'constraint' => 16,
                        'default' => 'BB',
                        'null' => true,
                    ],
                ]);
            }
            if (!$this->db->field_exists('hospitality_checked_in_at', 'appointments')) {
                $this->dbforge->add_column('appointments', [
                    'hospitality_checked_in_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'default' => null,
                    ],
                ]);
            }
            if (!$this->db->field_exists('hospitality_checked_out_at', 'appointments')) {
                $this->dbforge->add_column('appointments', [
                    'hospitality_checked_out_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'default' => null,
                    ],
                ]);
            }
            if (!$this->db->field_exists('hospitality_pax_adults', 'appointments')) {
                $this->dbforge->add_column('appointments', [
                    'hospitality_pax_adults' => [
                        'type' => 'INT',
                        'default' => 2,
                        'null' => true,
                    ],
                ]);
            }
            if (!$this->db->field_exists('hospitality_pax_children', 'appointments')) {
                $this->dbforge->add_column('appointments', [
                    'hospitality_pax_children' => [
                        'type' => 'INT',
                        'default' => 0,
                        'null' => true,
                    ],
                ]);
            }
            if (!$this->db->field_exists('hospitality_door_pin', 'appointments')) {
                $this->dbforge->add_column('appointments', [
                    'hospitality_door_pin' => [
                        'type' => 'VARCHAR',
                        'constraint' => 16,
                        'null' => true,
                        'default' => null,
                    ],
                ]);
            }
        }

        // Seed default room types and rate plans if table is empty
        $this->seed_default_hospitality_data();
    }

    private function seed_default_hospitality_data(): void
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

    public function down(): void
    {
        $this->dbforge->drop_table('hospitality_channel_sync_logs', true);
        $this->dbforge->drop_table('hospitality_night_audits', true);
        $this->dbforge->drop_table('hospitality_maintenance_tickets', true);
        $this->dbforge->drop_table('hospitality_housekeeping_tasks', true);
        $this->dbforge->drop_table('hospitality_kbs_declarations', true);
        $this->dbforge->drop_table('hospitality_rate_plans', true);
        $this->dbforge->drop_table('hospitality_room_types', true);
    }
}
