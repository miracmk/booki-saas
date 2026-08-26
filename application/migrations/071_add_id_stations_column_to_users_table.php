<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - assigns a fixed station to each provider.
 * ---------------------------------------------------------------------------- */

class Migration_Add_id_stations_column_to_users_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('id_stations', 'users')) {
            $fields = [
                'id_stations' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'null' => true,
                    'after' => 'id_roles',
                ],
            ];

            $this->dbforge->add_column('users', $fields);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('id_stations', 'users')) {
            $this->dbforge->drop_column('users', 'id_stations');
        }
    }
}
