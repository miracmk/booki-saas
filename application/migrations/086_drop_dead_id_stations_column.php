<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - cleanup. `users.id_stations` (added by migration 076, a single-station
 * FK) was superseded by the multi-station `stations_providers` join table in migration 078 - its data
 * was migrated over at the time and nothing has read this column since (verified by search). The
 * `idx_id_stations` index (also migration 076) is dead along with it. Dropping both removes stale
 * schema that could otherwise confuse a future reader into thinking it's still load-bearing.
 * ---------------------------------------------------------------------------- */

class Migration_Drop_dead_id_stations_column extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $indexes = $this->db
            ->query('SHOW INDEX FROM ' . $this->db->dbprefix('users') . " WHERE Key_name = 'idx_id_stations'")
            ->result_array();

        if (!empty($indexes)) {
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('users') . ' DROP INDEX idx_id_stations');
        }

        if ($this->db->field_exists('id_stations', 'users')) {
            $this->dbforge->drop_column('users', 'id_stations');
        }
    }

    /**
     * Downgrade method.
     *
     * Recreates the column and index (matching migration 076's up()) but cannot restore the values -
     * they haven't been read or written since migration 078, so there's nothing meaningful to migrate
     * back in.
     */
    public function down(): void
    {
        if (!$this->db->field_exists('id_stations', 'users')) {
            $this->dbforge->add_column('users', [
                'id_stations' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
            ]);
        }

        $indexes = $this->db
            ->query('SHOW INDEX FROM ' . $this->db->dbprefix('users') . " WHERE Key_name = 'idx_id_stations'")
            ->result_array();

        if (empty($indexes)) {
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('users') . ' ADD INDEX idx_id_stations (id_stations)');
        }
    }
}
