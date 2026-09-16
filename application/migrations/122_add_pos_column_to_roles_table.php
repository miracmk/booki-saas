<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - POS, wave 1 (2026-08-28).
 *
 * Permission column for the new POS admin section - same bitmask pattern as
 * migration 073/114/117/119.
 * ---------------------------------------------------------------------------- */

class Migration_Add_pos_column_to_roles_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('pos', 'roles')) {
            $this->dbforge->add_column('roles', [
                'pos' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'null' => true,
                ],
            ]);

            $this->db->update('roles', ['pos' => '15'], ['slug' => 'admin']);
            $this->db->update('roles', ['pos' => '15'], ['slug' => 'secretary']);
            $this->db->update('roles', ['pos' => '3'], ['slug' => 'provider']);
            $this->db->update('roles', ['pos' => '0'], ['slug' => 'customer']);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('pos', 'roles')) {
            $this->dbforge->drop_column('roles', 'pos');
        }
    }
}
