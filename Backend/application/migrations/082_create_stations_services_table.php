<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - stations are now tied to the SERVICES they can
 * host (not to providers). A station with no row here is considered open to
 * every service ("fail-open") - this keeps today's 3 stations working exactly
 * as before until an admin deliberately restricts one. The old
 * ea_stations_providers assignment is kept as-is and becomes an OPTIONAL
 * override, gated by the new users.station_restriction_enabled flag (default
 * off): with the flag off, a provider can work in any station their service
 * is available in, regardless of ea_stations_providers rows.
 *
 * No data is deleted or modified by this migration - only new structures are
 * added, so today's behavior (every provider assigned to all 3 stations) is
 * unaffected either way.
 * ---------------------------------------------------------------------------- */

class Migration_Create_stations_services_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('stations_services')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_stations' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'id_services' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_stations');
            $this->dbforge->add_key('id_services');
            $this->dbforge->create_table('stations_services');
        }

        if (!$this->db->field_exists('station_restriction_enabled', 'users')) {
            $this->dbforge->add_column('users', [
                'station_restriction_enabled' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 0,
                    'after' => 'commission_value',
                ],
            ]);
        }

        $index_exists = $this->db
            ->query(
                'SHOW INDEX FROM ' . $this->db->dbprefix('appointments') . " WHERE Key_name = 'idx_appointments_station_time'",
            )
            ->result_array();

        if (empty($index_exists)) {
            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('appointments') .
                    ' ADD INDEX idx_appointments_station_time (id_stations, start_datetime, end_datetime)',
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $index_exists = $this->db
            ->query(
                'SHOW INDEX FROM ' . $this->db->dbprefix('appointments') . " WHERE Key_name = 'idx_appointments_station_time'",
            )
            ->result_array();

        if (!empty($index_exists)) {
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('appointments') . ' DROP INDEX idx_appointments_station_time',
            );
        }

        if ($this->db->field_exists('station_restriction_enabled', 'users')) {
            $this->dbforge->drop_column('users', 'station_restriction_enabled');
        }

        if ($this->db->table_exists('stations_services')) {
            $this->dbforge->drop_table('stations_services');
        }
    }
}
