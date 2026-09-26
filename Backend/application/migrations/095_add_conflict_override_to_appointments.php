<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - room/therapist conflict override (2026-08-25).
 *
 * Admins/secretaries may now force-save an appointment despite a busy therapist and/or a busy/
 * unavailable station (see Calendar.php::save_appointment()). These columns record that it happened,
 * by whom, and which resource(s) were conflicted - both to visually flag the appointment on the
 * calendar (conflict_override not null) and for after-the-fact accountability (a double-booked room
 * needs to be traceable to who authorized it).
 * ---------------------------------------------------------------------------- */

class Migration_Add_conflict_override_to_appointments extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('conflict_override', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'conflict_override' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                    'after' => 'is_unavailability',
                ],
            ]);
        }

        if (!$this->db->field_exists('conflict_override_by', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'conflict_override_by' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'conflict_override',
                ],
            ]);
        }

        if (!$this->db->field_exists('conflict_override_at', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'conflict_override_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'conflict_override_by',
                ],
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        foreach (['conflict_override_at', 'conflict_override_by', 'conflict_override'] as $column) {
            if ($this->db->field_exists($column, 'appointments')) {
                $this->dbforge->drop_column('appointments', $column);
            }
        }
    }
}
