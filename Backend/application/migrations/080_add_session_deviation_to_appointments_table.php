<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - records the deviation between an appointment's
 * expected session duration (from actual_start_datetime) and what actually
 * happened, plus whether the assigned station was chosen manually or by the
 * automatic free-station algorithm.
 * ---------------------------------------------------------------------------- */

class Migration_Add_session_deviation_to_appointments_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('session_deviation_type', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'session_deviation_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 16,
                    'null' => true,
                    'after' => 'actual_end_datetime',
                ],
            ]);
        }

        if (!$this->db->field_exists('session_deviation_minutes', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'session_deviation_minutes' => [
                    'type' => 'INT',
                    'null' => true,
                    'after' => 'session_deviation_type',
                ],
            ]);
        }

        if (!$this->db->field_exists('session_deviation_reason', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'session_deviation_reason' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'session_deviation_minutes',
                ],
            ]);
        }

        if (!$this->db->field_exists('station_assigned_manually', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'station_assigned_manually' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 0,
                    'after' => 'id_stations',
                ],
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('station_assigned_manually', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'station_assigned_manually');
        }

        if ($this->db->field_exists('session_deviation_reason', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'session_deviation_reason');
        }

        if ($this->db->field_exists('session_deviation_minutes', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'session_deviation_minutes');
        }

        if ($this->db->field_exists('session_deviation_type', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'session_deviation_type');
        }
    }
}
