<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Restaurant Ecosystem Migration (QR Menu, Self-Order & KDS)
 * ---------------------------------------------------------------------------- */

class Migration_Restaurant_ecosystem_qr_selforder_kds extends App_Migration
{
    public function up(): void
    {
        $this->load->dbforge();

        // 1. restaurant_menu_categories
        if (!$this->db->table_exists('restaurant_menu_categories')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                ],
                'slug' => [
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => true,
                ],
                'description' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'icon' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'default' => 'fa-utensils',
                ],
                'display_order' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
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
            $this->dbforge->add_key('slug');
            $this->dbforge->create_table('restaurant_menu_categories', true);
        }

        // 2. restaurant_menu_items
        if (!$this->db->table_exists('restaurant_menu_items')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_categories' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 191,
                ],
                'description' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'price' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => '0.00',
                ],
                'image_url' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'calories' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'prep_time_minutes' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 15,
                ],
                'allergens' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'dietary_badges' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'station' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'default' => 'kitchen',
                ],
                'options_json' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'is_available' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ],
                'display_order' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
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
            $this->dbforge->add_key('id_categories');
            $this->dbforge->create_table('restaurant_menu_items', true);
        }

        // 3. restaurant_table_calls
        if (!$this->db->table_exists('restaurant_table_calls')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_restaurant_tables' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'call_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'waiter',
                ],
                'note' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'payment_method_preference' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => true,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'pending',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'resolved_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_restaurant_tables');
            $this->dbforge->create_table('restaurant_table_calls', true);
        }

        // 4. Columns on restaurant_tables
        $tbl_cols = [
            'qr_token' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'waiter_call_status' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'none',
            ],
            'waiter_call_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'waiter_call_note' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ];
        foreach ($tbl_cols as $col_name => $col_def) {
            if (!$this->db->field_exists($col_name, 'restaurant_tables')) {
                $this->dbforge->add_column('restaurant_tables', [$col_name => $col_def]);
            }
        }

        // 5. Columns on adisyons
        $adisyon_cols = [
            'loyalty_points_earned' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'loyalty_points_used' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'loyalty_discount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => '0.00',
            ],
            'membership_discount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => '0.00',
            ],
            'id_customer_memberships' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
        ];
        foreach ($adisyon_cols as $col_name => $col_def) {
            if (!$this->db->field_exists($col_name, 'adisyons')) {
                $this->dbforge->add_column('adisyons', [$col_name => $col_def]);
            }
        }

        // 6. Columns on loyalty_points
        if (!$this->db->field_exists('id_adisyons', 'loyalty_points')) {
            $this->dbforge->add_column('loyalty_points', [
                'id_adisyons' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
            ]);
        }

        // 7. Columns on membership_plans
        $plan_cols = [
            'discount_percent' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => '0.00',
            ],
            'perks_description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ];
        foreach ($plan_cols as $col_name => $col_def) {
            if (!$this->db->field_exists($col_name, 'membership_plans')) {
                $this->dbforge->add_column('membership_plans', [$col_name => $col_def]);
            }
        }
    }

    public function down(): void
    {
        $this->load->dbforge();
        if ($this->db->table_exists('restaurant_table_calls')) {
            $this->dbforge->drop_table('restaurant_table_calls');
        }
        if ($this->db->table_exists('restaurant_menu_items')) {
            $this->dbforge->drop_table('restaurant_menu_items');
        }
        if ($this->db->table_exists('restaurant_menu_categories')) {
            $this->dbforge->drop_table('restaurant_menu_categories');
        }
    }
}
