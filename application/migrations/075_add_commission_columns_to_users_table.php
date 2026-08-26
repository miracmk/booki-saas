<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - per-provider commission settings, used by the
 * daily revenue report to compute the payout owed to each therapist.
 * ---------------------------------------------------------------------------- */

class Migration_Add_commission_columns_to_users_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('commission_type', 'users')) {
            $this->dbforge->add_column('users', [
                'commission_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => '32',
                    'null' => true,
                    'after' => 'id_stations',
                ],
            ]);
        }

        if (!$this->db->field_exists('commission_value', 'users')) {
            $this->dbforge->add_column('users', [
                'commission_value' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                    'default' => 0,
                    'after' => 'commission_type',
                ],
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('commission_value', 'users')) {
            $this->dbforge->drop_column('users', 'commission_value');
        }

        if ($this->db->field_exists('commission_type', 'users')) {
            $this->dbforge->drop_column('users', 'commission_type');
        }
    }
}
