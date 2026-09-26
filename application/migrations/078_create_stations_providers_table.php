<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - a provider may now be assigned to more than one
 * station (e.g. can work in either "Masaj Odası 1" or "Masaj Odası 2"),
 * replacing the previous single users.id_stations foreign key. Also adds
 * appointments.id_stations, which records which physical station a given
 * appointment actually occupies - needed now that the provider's station can
 * no longer be inferred as a single fixed value.
 * ---------------------------------------------------------------------------- */

class Migration_Create_stations_providers_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('stations_providers')) {
            $this->dbforge->add_field([
                'id_stations' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'null' => false,
                ],
                'id_users' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'null' => false,
                ],
            ]);

            $this->dbforge->add_key('id_stations', true);
            $this->dbforge->add_key('id_users', true);

            $this->dbforge->create_table('stations_providers', true, ['engine' => 'InnoDB']);
        }

        // Migrate the existing single-station assignments into the new join table.
        if ($this->db->field_exists('id_stations', 'users')) {
            $providers = $this->db
                ->select('id, id_stations')
                ->from('users')
                ->where('id_stations IS NOT NULL', null, false)
                ->get()
                ->result_array();

            foreach ($providers as $provider) {
                $exists = $this->db
                    ->get_where('stations_providers', [
                        'id_stations' => $provider['id_stations'],
                        'id_users' => $provider['id'],
                    ])
                    ->num_rows();

                if (!$exists) {
                    $this->db->insert('stations_providers', [
                        'id_stations' => $provider['id_stations'],
                        'id_users' => $provider['id'],
                    ]);
                }
            }
        }

        if (!$this->db->field_exists('id_stations', 'appointments')) {
            $fields = [
                'id_stations' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'null' => true,
                    'after' => 'id_services',
                ],
            ];

            $this->dbforge->add_column('appointments', $fields);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('id_stations', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'id_stations');
        }

        if ($this->db->table_exists('stations_providers')) {
            $this->dbforge->drop_table('stations_providers');
        }
    }
}
