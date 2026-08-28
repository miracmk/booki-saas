<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Recurring appointments, wave 1 (2026-08-28).
 *
 * Adds a nullable link from an appointment to the recurrence series it
 * belongs to (recurrence_groups, migration 111) plus its position within
 * that series. Both columns are NULL for every appointment created before
 * this migration and for every non-recurring appointment created after it -
 * purely additive, no existing read/write path changes behavior.
 * ---------------------------------------------------------------------------- */

class Migration_Add_recurrence_to_appointments extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('id_recurrence_group', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'id_recurrence_group' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'FK to recurrence_groups.id, NULL = not part of a series',
                    'after' => 'conflict_override_at',
                ],
            ]);
        }

        if (!$this->db->field_exists('recurrence_sequence', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'recurrence_sequence' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'Position of this occurrence within its recurrence group (1-based), NULL if not recurring',
                    'after' => 'id_recurrence_group',
                ],
            ]);
        }

        $indexes = $this->db
            ->query(
                'SHOW INDEX FROM ' . $this->db->dbprefix('appointments') . " WHERE Key_name = 'idx_id_recurrence_group'",
            )
            ->result_array();

        if (empty($indexes)) {
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('appointments') . ' ADD INDEX idx_id_recurrence_group (id_recurrence_group)',
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $indexes = $this->db
            ->query(
                'SHOW INDEX FROM ' . $this->db->dbprefix('appointments') . " WHERE Key_name = 'idx_id_recurrence_group'",
            )
            ->result_array();

        if (!empty($indexes)) {
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('appointments') . ' DROP INDEX idx_id_recurrence_group',
            );
        }

        foreach (['recurrence_sequence', 'id_recurrence_group'] as $column) {
            if ($this->db->field_exists($column, 'appointments')) {
                $this->dbforge->drop_column('appointments', $column);
            }
        }
    }
}
