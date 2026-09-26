<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - cleanup of the two station join tables:
 *
 * - `stations_services` (migration 082) had no uniqueness constraint on (id_stations, id_services),
 *   so a repeated insert (e.g. a client-side double-submit) could silently create a duplicate row.
 *   The application code already tolerates duplicates (array_unique() on the read side), but nothing
 *   should rely on that - add the UNIQUE key the table always should have had. A defensive dedup runs
 *   first in case any duplicates exist by the time this runs.
 *
 * - `stations_providers` (migration 078) has its PRIMARY KEY as (id_stations, id_users) - correct for
 *   Stations_model::get_provider_ids($station_id), but Providers_model::get_station_ids($provider_id)
 *   queries by id_users alone, which can't use a composite index whose leading column is id_stations.
 *   Add a secondary index starting with id_users for that direction.
 * ---------------------------------------------------------------------------- */

class Migration_Station_join_table_indexes extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if ($this->db->table_exists('stations_services')) {
            $duplicate_groups = $this->db
                ->query(
                    'SELECT id_stations, id_services, MIN(id) AS keep_id FROM ' .
                        $this->db->dbprefix('stations_services') .
                        ' GROUP BY id_stations, id_services HAVING COUNT(*) > 1',
                )
                ->result_array();

            foreach ($duplicate_groups as $group) {
                $this->db
                    ->where('id_stations', $group['id_stations'])
                    ->where('id_services', $group['id_services'])
                    ->where('id !=', $group['keep_id'])
                    ->delete('stations_services');
            }

            $index_exists = $this->db
                ->query(
                    'SHOW INDEX FROM ' .
                        $this->db->dbprefix('stations_services') .
                        " WHERE Key_name = 'idx_stations_services_unique'",
                )
                ->result_array();

            if (empty($index_exists)) {
                $this->db->query(
                    'ALTER TABLE ' .
                        $this->db->dbprefix('stations_services') .
                        ' ADD UNIQUE INDEX idx_stations_services_unique (id_stations, id_services)',
                );
            }
        }

        if ($this->db->table_exists('stations_providers')) {
            $index_exists = $this->db
                ->query(
                    'SHOW INDEX FROM ' .
                        $this->db->dbprefix('stations_providers') .
                        " WHERE Key_name = 'idx_stations_providers_id_users'",
                )
                ->result_array();

            if (empty($index_exists)) {
                $this->db->query(
                    'ALTER TABLE ' .
                        $this->db->dbprefix('stations_providers') .
                        ' ADD INDEX idx_stations_providers_id_users (id_users)',
                );
            }
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('stations_providers')) {
            $index_exists = $this->db
                ->query(
                    'SHOW INDEX FROM ' .
                        $this->db->dbprefix('stations_providers') .
                        " WHERE Key_name = 'idx_stations_providers_id_users'",
                )
                ->result_array();

            if (!empty($index_exists)) {
                $this->db->query(
                    'ALTER TABLE ' .
                        $this->db->dbprefix('stations_providers') .
                        ' DROP INDEX idx_stations_providers_id_users',
                );
            }
        }

        if ($this->db->table_exists('stations_services')) {
            $index_exists = $this->db
                ->query(
                    'SHOW INDEX FROM ' .
                        $this->db->dbprefix('stations_services') .
                        " WHERE Key_name = 'idx_stations_services_unique'",
                )
                ->result_array();

            if (!empty($index_exists)) {
                $this->db->query(
                    'ALTER TABLE ' .
                        $this->db->dbprefix('stations_services') .
                        ' DROP INDEX idx_stations_services_unique',
                );
            }
        }
    }
}
