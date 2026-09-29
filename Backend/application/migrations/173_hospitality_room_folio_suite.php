<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration 173: Hospitality Room & Folio Suite.
 *
 * Adds room status and capacity to stations table, and id_stations to adisyons table
 * for Sector 6 (Hospitality - Otel, Butik Otel, Bungalov, Pansiyon).
 */
class Migration_Hospitality_room_folio_suite extends App_Migration
{
    public function up(): void
    {
        if ($this->db->table_exists('stations')) {
            if (!$this->db->field_exists('status', 'stations')) {
                $this->dbforge->add_column('stations', [
                    'status' => [
                        'type' => 'VARCHAR',
                        'constraint' => 32,
                        'default' => 'clean',
                        'null' => true,
                    ],
                ]);
            }

            if (!$this->db->field_exists('capacity', 'stations')) {
                $this->dbforge->add_column('stations', [
                    'capacity' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'default' => 2,
                        'null' => true,
                    ],
                ]);
            }
        }

        if ($this->db->table_exists('adisyons')) {
            if (!$this->db->field_exists('id_stations', 'adisyons')) {
                $this->dbforge->add_column('adisyons', [
                    'id_stations' => [
                        'type' => 'INT',
                        'unsigned' => true,
                        'null' => true,
                        'default' => null,
                    ],
                ]);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('stations')) {
            if ($this->db->field_exists('status', 'stations')) {
                $this->dbforge->drop_column('stations', 'status');
            }
            if ($this->db->field_exists('capacity', 'stations')) {
                $this->dbforge->drop_column('stations', 'capacity');
            }
        }

        if ($this->db->table_exists('adisyons')) {
            if ($this->db->field_exists('id_stations', 'adisyons')) {
                $this->dbforge->drop_column('adisyons', 'id_stations');
            }
        }
    }
}
