<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Waitlist, wave 1 (2026-08-28).
 *
 * Permission column for the new Waitlist admin section (see the generic
 * bitmask-based permission_helper.php, consumed by
 * Roles_model::get_permissions_by_slug()). Providers get view-only (1) so
 * they can see who's waiting for their own services; customers get none (0),
 * they join the waitlist through the booking flow, not this admin page.
 * ---------------------------------------------------------------------------- */

class Migration_Add_waitlist_column_to_roles_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('waitlist', 'roles')) {
            $this->dbforge->add_column('roles', [
                'waitlist' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'null' => true,
                ],
            ]);

            $this->db->update('roles', ['waitlist' => '15'], ['slug' => 'admin']);
            $this->db->update('roles', ['waitlist' => '15'], ['slug' => 'secretary']);
            $this->db->update('roles', ['waitlist' => '1'], ['slug' => 'provider']);
            $this->db->update('roles', ['waitlist' => '0'], ['slug' => 'customer']);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('waitlist', 'roles')) {
            $this->dbforge->drop_column('roles', 'waitlist');
        }
    }
}
