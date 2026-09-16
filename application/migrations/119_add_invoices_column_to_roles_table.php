<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Internal Invoicing, wave 1 (2026-08-28).
 *
 * Permission column for the new Invoices admin section - same bitmask
 * pattern as migration 073/114/117.
 * ---------------------------------------------------------------------------- */

class Migration_Add_invoices_column_to_roles_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('invoices', 'roles')) {
            $this->dbforge->add_column('roles', [
                'invoices' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'null' => true,
                ],
            ]);

            $this->db->update('roles', ['invoices' => '15'], ['slug' => 'admin']);
            $this->db->update('roles', ['invoices' => '15'], ['slug' => 'secretary']);
            $this->db->update('roles', ['invoices' => '0'], ['slug' => 'provider']);
            $this->db->update('roles', ['invoices' => '0'], ['slug' => 'customer']);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('invoices', 'roles')) {
            $this->dbforge->drop_column('roles', 'invoices');
        }
    }
}
