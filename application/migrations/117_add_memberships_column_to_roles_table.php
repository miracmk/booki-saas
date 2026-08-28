<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Memberships, wave 1 (2026-08-28).
 *
 * Permission column for the new Memberships admin section - same bitmask
 * pattern as migration 073/114. Providers get view-only (1) so they can see
 * a customer's active membership before a session; customers get none (0),
 * memberships are sold/managed by staff for now.
 * ---------------------------------------------------------------------------- */

class Migration_Add_memberships_column_to_roles_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('memberships', 'roles')) {
            $this->dbforge->add_column('roles', [
                'memberships' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'null' => true,
                ],
            ]);

            $this->db->update('roles', ['memberships' => '15'], ['slug' => 'admin']);
            $this->db->update('roles', ['memberships' => '15'], ['slug' => 'secretary']);
            $this->db->update('roles', ['memberships' => '1'], ['slug' => 'provider']);
            $this->db->update('roles', ['memberships' => '0'], ['slug' => 'customer']);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('memberships', 'roles')) {
            $this->dbforge->drop_column('roles', 'memberships');
        }
    }
}
