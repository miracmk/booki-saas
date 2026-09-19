<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Migration 154: Appointment Consumables & Session Cost Accounting
 *
 * Implements session-based consumable tracking (sarf malzemesi reçetesi & fiili harcama),
 * flexible per-appointment material adjustments, and unit economics (consumable cost
 * and gross profit per session).
 * ---------------------------------------------------------------------------- */

class Migration_Create_appointment_consumables_and_session_costs extends EA_Migration
{
    public function up(): void
    {
        // 1. Ensure products table has unit, is_consumable, and cost_price compatibility
        if ($this->db->table_exists('products')) {
            if (!$this->db->field_exists('unit', 'products')) {
                $this->dbforge->add_column('products', [
                    'unit' => [
                        'type' => 'VARCHAR',
                        'constraint' => 32,
                        'default' => 'adet',
                        'after' => 'name',
                    ],
                ]);
            }

            if (!$this->db->field_exists('is_consumable', 'products')) {
                $this->dbforge->add_column('products', [
                    'is_consumable' => [
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => 0,
                        'after' => 'unit',
                    ],
                ]);
            }

            if (!$this->db->field_exists('cost_price', 'products') && !$this->db->field_exists('cost', 'products')) {
                $this->dbforge->add_column('products', [
                    'cost_price' => [
                        'type' => 'DECIMAL',
                        'constraint' => '10,2',
                        'default' => '0.00',
                        'after' => 'sale_price',
                    ],
                ]);
            }
        }

        // 2. Add consumable cost & profit tracking to appointments table
        if ($this->db->table_exists('appointments')) {
            $appointment_columns = [];

            if (!$this->db->field_exists('consumables_cost', 'appointments')) {
                $appointment_columns['consumables_cost'] = [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => '0.00',
                ];
            }

            if (!$this->db->field_exists('gross_profit', 'appointments')) {
                $appointment_columns['gross_profit'] = [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => '0.00',
                ];
            }

            if (!$this->db->field_exists('consumables_deducted', 'appointments')) {
                $appointment_columns['consumables_deducted'] = [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ];
            }

            if (!empty($appointment_columns)) {
                $this->dbforge->add_column('appointments', $appointment_columns);
            }
        }

        // 3. Create appointment_consumables table for tracking materials spent in each session
        if (!$this->db->table_exists('appointment_consumables')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_appointments' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'id_products' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'quantity_used' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => '1.00',
                ],
                'unit' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'adet',
                ],
                'unit_cost' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => '0.00',
                ],
                'total_cost' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => '0.00',
                ],
                'is_extra' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'comment' => '0 = from service recipe, 1 = extra consumed in session',
                ],
                'notes' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_appointments');
            $this->dbforge->add_key('id_products');
            $this->dbforge->create_table('appointment_consumables');
        }

        // 4. Ensure service_consumables table has necessary columns
        if ($this->db->table_exists('service_consumables')) {
            if (!$this->db->field_exists('unit', 'service_consumables')) {
                $this->dbforge->add_column('service_consumables', [
                    'unit' => [
                        'type' => 'VARCHAR',
                        'constraint' => 32,
                        'default' => 'adet',
                        'after' => 'quantity_used',
                    ],
                ]);
            }
        }

        // 5. Ensure stock_movements has required columns & movement_type is wide enough
        if ($this->db->table_exists('stock_movements')) {
            // 5a. Widen movement_type from VARCHAR(16) → VARCHAR(32) for values like 'service_consumption'
            // This is safe to run even if it's already VARCHAR(32) — MySQL treats it as a no-op
            $this->db->query("ALTER TABLE `{$this->db->dbprefix}stock_movements` MODIFY COLUMN `movement_type` VARCHAR(32) NOT NULL");

            // 5b. Ensure id_appointments column exists (migration 107 may have created it differently)
            if (!$this->db->field_exists('id_appointments', 'stock_movements')) {
                $this->dbforge->add_column('stock_movements', [
                    'id_appointments' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'null' => true,
                    ],
                ]);
            }

            $sm_cols = [];
            if (!$this->db->field_exists('quantity', 'stock_movements') && $this->db->field_exists('quantity_delta', 'stock_movements')) {
                $sm_cols['quantity'] = [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                ];
            }
            if (!$this->db->field_exists('unit_cost', 'stock_movements')) {
                $sm_cols['unit_cost'] = [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => '0.00',
                ];
            }
            if (!$this->db->field_exists('notes', 'stock_movements')) {
                $sm_cols['notes'] = [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ];
            }
            if (!empty($sm_cols)) {
                $this->dbforge->add_column('stock_movements', $sm_cols);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('appointment_consumables')) {
            $this->dbforge->drop_table('appointment_consumables');
        }

        if ($this->db->table_exists('appointments')) {
            if ($this->db->field_exists('consumables_cost', 'appointments')) {
                $this->dbforge->drop_column('appointments', 'consumables_cost');
            }
            if ($this->db->field_exists('gross_profit', 'appointments')) {
                $this->dbforge->drop_column('appointments', 'gross_profit');
            }
            if ($this->db->field_exists('consumables_deducted', 'appointments')) {
                $this->dbforge->drop_column('appointments', 'consumables_deducted');
            }
        }

        if ($this->db->table_exists('products')) {
            if ($this->db->field_exists('unit', 'products')) {
                $this->dbforge->drop_column('products', 'unit');
            }
            if ($this->db->field_exists('is_consumable', 'products')) {
                $this->dbforge->drop_column('products', 'is_consumable');
            }
        }
    }
}
