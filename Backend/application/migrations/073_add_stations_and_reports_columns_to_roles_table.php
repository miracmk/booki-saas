<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - permission columns for the new Stations and
 * Reports admin sections (see the generic bitmask-based permission_helper.php,
 * consumed by Roles_model::get_permissions_by_slug).
 * ---------------------------------------------------------------------------- */

class Migration_Add_stations_and_reports_columns_to_roles_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('stations', 'roles')) {
            $this->dbforge->add_column('roles', [
                'stations' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'null' => true,
                ],
            ]);

            $this->db->update('roles', ['stations' => '15'], ['slug' => 'admin']);
            $this->db->update('roles', ['stations' => '15'], ['slug' => 'secretary']);
            $this->db->update('roles', ['stations' => '0'], ['slug' => 'provider']);
            $this->db->update('roles', ['stations' => '0'], ['slug' => 'customer']);
        }

        if (!$this->db->field_exists('reports', 'roles')) {
            $this->dbforge->add_column('roles', [
                'reports' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'null' => true,
                ],
            ]);

            $this->db->update('roles', ['reports' => '15'], ['slug' => 'admin']);
            $this->db->update('roles', ['reports' => '1'], ['slug' => 'secretary']);
            $this->db->update('roles', ['reports' => '0'], ['slug' => 'provider']);
            $this->db->update('roles', ['reports' => '0'], ['slug' => 'customer']);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('reports', 'roles')) {
            $this->dbforge->drop_column('roles', 'reports');
        }

        if ($this->db->field_exists('stations', 'roles')) {
            $this->dbforge->drop_column('roles', 'stations');
        }
    }
}
