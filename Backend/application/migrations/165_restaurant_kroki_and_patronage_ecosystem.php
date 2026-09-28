<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Interactive Floor Plan (Kroki), Patronage (Müdavimlik) & Smart Seating
 * ---------------------------------------------------------------------------- */

class Migration_Restaurant_kroki_and_patronage_ecosystem extends App_Migration
{
    public function up(): void
    {
        $this->load->dbforge();

        // 1. restaurant_layout_elements (Walls, Doors, Windows, Bar, Stage, Pillars etc.)
        if (!$this->db->table_exists('restaurant_layout_elements')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'section' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'default' => 'Ana Salon',
                ],
                'element_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'wall', // wall, window, door, bar_counter, stage, pillar, kitchen_pass, plant, restroom
                ],
                'label' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                ],
                'pos_x' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'pos_y' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'width' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 120,
                ],
                'height' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 30,
                ],
                'rotation' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'style_json' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'is_active' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('section');
            $this->dbforge->create_table('restaurant_layout_elements', true);
        }

        // 2. Add layout and patronage columns on restaurant_tables
        $tbl_cols = [
            'shape' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'rectangle', // rectangle, square, round, booth, bistro
            ],
            'rotation' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'is_vip_only' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'min_spend' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => '0.00',
            ],
            'assigned_server_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'combined_with_table_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'last_cleaned_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ];

        foreach ($tbl_cols as $col_name => $col_def) {
            if (!$this->db->field_exists($col_name, 'restaurant_tables')) {
                $this->dbforge->add_column('restaurant_tables', [$col_name => $col_def]);
            }
        }

        // 3. customer_dining_profiles (Masa Müdavimliği & 360° Misafir Zekası)
        if (!$this->db->table_exists('customer_dining_profiles')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_users_customer' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'favorite_table_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'favorite_section' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                ],
                'total_visits' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'total_spend' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => '0.00',
                ],
                'avg_spend' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => '0.00',
                ],
                'avg_party_size' => [
                    'type' => 'DECIMAL',
                    'constraint' => '3,1',
                    'default' => '2.0',
                ],
                'favorite_dishes_json' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'favorite_drinks_json' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'dietary_habits' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'allergens' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'service_notes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'favorite_server_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'patronage_tier' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'first_timer', // first_timer, regular, vip_regular, elite
                ],
                'welcome_treat_pref' => [
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => true,
                ],
                'last_visit_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users_customer');
            $this->dbforge->create_table('customer_dining_profiles', true);
        }

        // 4. Columns on restaurant_reservations
        $res_cols = [
            'is_customer_selected_table' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'is_patron_priority' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'special_requests' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ];

        foreach ($res_cols as $col_name => $col_def) {
            if (!$this->db->field_exists($col_name, 'restaurant_reservations')) {
                $this->dbforge->add_column('restaurant_reservations', [$col_name => $col_def]);
            }
        }
    }

    public function down(): void
    {
        $this->load->dbforge();
        if ($this->db->table_exists('customer_dining_profiles')) {
            $this->dbforge->drop_table('customer_dining_profiles');
        }
        if ($this->db->table_exists('restaurant_layout_elements')) {
            $this->dbforge->drop_table('restaurant_layout_elements');
        }
    }
}
