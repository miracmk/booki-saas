<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - index for id_stations lookups (station-mate
 * queries run on every availability calculation).
 * ---------------------------------------------------------------------------- */

class Migration_Add_index_to_id_stations extends EA_Migration
{
    public function up(): void
    {
        $indexes = $this->db->query('SHOW INDEX FROM ' . $this->db->dbprefix('users') . " WHERE Key_name = 'idx_id_stations'")->result_array();

        if (empty($indexes)) {
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('users') . ' ADD INDEX idx_id_stations (id_stations)');
        }
    }

    public function down(): void
    {
        $indexes = $this->db->query('SHOW INDEX FROM ' . $this->db->dbprefix('users') . " WHERE Key_name = 'idx_id_stations'")->result_array();

        if (!empty($indexes)) {
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('users') . ' DROP INDEX idx_id_stations');
        }
    }
}
