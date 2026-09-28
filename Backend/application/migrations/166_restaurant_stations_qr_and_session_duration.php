<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Restaurant Stations, QR Customizer & Session Duration Architecture
 *
 * Implements:
 * 1. `restaurant_staff_stations`: Multi-station dynamic assignments for Kitchen & Bar.
 * 2. `restaurant_qr_settings`: Comprehensive QR Menu customizer & theme preferences.
 * 3. `restaurant_tables`: Adds duration_mode, session_duration_minutes, session_expires_at.
 * 4. `services`: Adds access_type, pass_validity_days, total_passes for Hamam/Spa/Passes.
 * 5. `restaurant_menu_items`: Adds is_qr_visible and badge_text.
 * -------------------------------------------------------------------------- */

class Migration_Restaurant_stations_qr_and_session_duration extends CI_Migration
{
    public function up(): void
    {
        $db = $this->db;
        $dbforge = $this->dbforge;

        // 1. Create `restaurant_staff_stations` table if not exists
        if (!$db->table_exists('restaurant_staff_stations')) {
            $dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_users' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'station_code' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => false,
                ],
                'role_title' => [
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => true,
                ],
                'shift_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'is_active' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'null' => false,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $dbforge->add_key('id', true);
            $dbforge->add_key('id_users');
            $dbforge->add_key('station_code');
            $dbforge->add_key('shift_date');
            $dbforge->create_table('restaurant_staff_stations', true, ['engine' => 'InnoDB']);
        }

        // 2. Create `restaurant_qr_settings` table if not exists
        if (!$db->table_exists('restaurant_qr_settings')) {
            $dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'theme_preset' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'default' => 'dark_gold',
                    'null' => false,
                ],
                'primary_color' => [
                    'type' => 'VARCHAR',
                    'constraint' => 16,
                    'default' => '#D97706',
                    'null' => false,
                ],
                'accent_color' => [
                    'type' => 'VARCHAR',
                    'constraint' => 16,
                    'default' => '#F59E0B',
                    'null' => false,
                ],
                'background_mode' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'dark',
                    'null' => false,
                ],
                'font_family' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'default' => 'Poppins',
                    'null' => false,
                ],
                'hero_title' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'default' => 'Hoş Geldiniz',
                    'null' => false,
                ],
                'hero_subtitle' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'hero_banner_url' => [
                    'type' => 'VARCHAR',
                    'constraint' => 500,
                    'null' => true,
                ],
                'allow_self_order' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'null' => false,
                ],
                'order_approval_mode' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'direct',
                    'null' => false,
                ],
                'enable_waiter_call' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'null' => false,
                ],
                'enable_bill_request' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'null' => false,
                ],
                'enable_tipping' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'null' => false,
                ],
                'tip_options' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'default' => '5,10,15,20',
                    'null' => false,
                ],
                'venue_duration_mode' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'open_ended',
                    'null' => false,
                ],
                'default_seat_duration_minutes' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 90,
                    'null' => false,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $dbforge->add_key('id', true);
            $dbforge->create_table('restaurant_qr_settings', true, ['engine' => 'InnoDB']);

            // Insert initial default setting record
            $db->insert('restaurant_qr_settings', [
                'theme_preset' => 'dark_gold',
                'primary_color' => '#D97706',
                'accent_color' => '#F59E0B',
                'background_mode' => 'dark',
                'font_family' => 'Poppins',
                'hero_title' => 'Hoş Geldiniz',
                'hero_subtitle' => 'Lezzetli anlar ve seçkin lezzetler sizi bekliyor.',
                'allow_self_order' => 1,
                'order_approval_mode' => 'direct',
                'enable_waiter_call' => 1,
                'enable_bill_request' => 1,
                'enable_tipping' => 1,
                'tip_options' => '5,10,15,20',
                'venue_duration_mode' => 'open_ended',
                'default_seat_duration_minutes' => 90,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // 3. Add columns to `restaurant_tables`
        if ($db->table_exists('restaurant_tables')) {
            $table_fields = [];
            if (!$db->field_exists('duration_mode', 'restaurant_tables')) {
                $table_fields['duration_mode'] = [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'open_ended',
                    'null' => false,
                ];
            }
            if (!$db->field_exists('session_duration_minutes', 'restaurant_tables')) {
                $table_fields['session_duration_minutes'] = [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 90,
                    'null' => false,
                ];
            }
            if (!$db->field_exists('session_expires_at', 'restaurant_tables')) {
                $table_fields['session_expires_at'] = [
                    'type' => 'DATETIME',
                    'null' => true,
                ];
            }
            if (!empty($table_fields)) {
                $dbforge->add_column('restaurant_tables', $table_fields);
            }
        }

        // 4. Add columns to `services` (For Hamam/Spa/Pass and Duration Upgrade)
        if ($db->table_exists('services')) {
            $service_fields = [];
            if (!$db->field_exists('access_type', 'services')) {
                $service_fields['access_type'] = [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'duration', // duration, open_ended, daily_pass, multi_pass
                    'null' => false,
                ];
            }
            if (!$db->field_exists('pass_validity_days', 'services')) {
                $service_fields['pass_validity_days'] = [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 1,
                    'null' => false,
                ];
            }
            if (!$db->field_exists('total_passes', 'services')) {
                $service_fields['total_passes'] = [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 1,
                    'null' => false,
                ];
            }
            if (!empty($service_fields)) {
                $dbforge->add_column('services', $service_fields);
            }
        }

        // 5. Add columns to `restaurant_menu_items`
        if ($db->table_exists('restaurant_menu_items')) {
            $menu_fields = [];
            if (!$db->field_exists('is_qr_visible', 'restaurant_menu_items')) {
                $menu_fields['is_qr_visible'] = [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'null' => false,
                ];
            }
            if (!$db->field_exists('badge_text', 'restaurant_menu_items')) {
                $menu_fields['badge_text'] = [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                ];
            }
            if (!empty($menu_fields)) {
                $dbforge->add_column('restaurant_menu_items', $menu_fields);
            }
        }
    }

    public function down(): void
    {
        $db = $this->db;
        $dbforge = $this->dbforge;

        if ($db->table_exists('restaurant_staff_stations')) {
            $dbforge->drop_table('restaurant_staff_stations', true);
        }
        if ($db->table_exists('restaurant_qr_settings')) {
            $dbforge->drop_table('restaurant_qr_settings', true);
        }
    }
}
