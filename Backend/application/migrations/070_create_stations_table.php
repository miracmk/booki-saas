<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - Stations (istasyon) support.
 * ---------------------------------------------------------------------------- */

class Migration_Create_stations_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('stations')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'auto_increment' => true,
                ],
                'create_datetime' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'update_datetime' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => '256',
                    'null' => false,
                ],
                'notes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'is_active' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 1,
                ],
            ]);

            $this->dbforge->add_key('id', true);

            $this->dbforge->create_table('stations', true, ['engine' => 'InnoDB']);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('stations')) {
            $this->dbforge->drop_table('stations');
        }
    }
}
