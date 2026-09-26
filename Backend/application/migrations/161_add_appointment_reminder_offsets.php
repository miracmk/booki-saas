<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration 161: Add configurable multi-offset appointment reminder timing.
 *
 * Replaces the single reminder_hours_ahead setting with up to 4 per-appointment
 * reminder offsets stored as a JSON integer array.
 *
 * Adds:
 * - appointments.reminders_notified (TEXT) - JSON array of offsets already sent
 * - messaging_settings.reminder_offsets (TEXT) - JSON array of configured offsets
 *
 * Backfills:
 * - messaging_settings.reminder_offsets = [reminder_hours_ahead] (legacy single offset)
 * - appointments.reminders_notified = [reminder_hours_ahead] for rows already fully
 *   notified (is_reminder_sent = 1) so they are not re-sent after the upgrade.
 */
class Migration_Add_appointment_reminder_offsets extends App_Migration
{
    public function up(): void
    {
        // 1. Appointments notified-offsets tracking
        if ($this->db->table_exists('appointments') && !$this->db->field_exists('reminders_notified', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'reminders_notified' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }

        // 2. Messaging settings offsets storage
        if ($this->db->table_exists('messaging_settings') && !$this->db->field_exists('reminder_offsets', 'messaging_settings')) {
            $this->dbforge->add_column('messaging_settings', [
                'reminder_offsets' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'default' => null,
                ],
            ]);

            // Backfill the legacy single-offset configuration as a JSON array.
            $this->db->query(
                'UPDATE ' . $this->db->dbprefix('messaging_settings')
                . ' SET reminder_offsets = CONCAT(\'[\', GREATEST(COALESCE(reminder_hours_ahead, 24), 1), \']\')'
                . ' WHERE reminder_offsets IS NULL'
            );
        }

        // 3. Backfill already-notified appointments so legacy-complete rows are not re-sent.
        if (
            $this->db->table_exists('appointments')
            && $this->db->field_exists('reminders_notified', 'appointments')
            && $this->db->field_exists('is_reminder_sent', 'appointments')
            && $this->db->table_exists('messaging_settings')
        ) {
            $this->db->query(
                'UPDATE ' . $this->db->dbprefix('appointments') . ' a'
                . ' SET a.reminders_notified = CONCAT(\'[\', COALESCE((SELECT MAX(m.reminder_hours_ahead) FROM '
                . $this->db->dbprefix('messaging_settings') . ' m), 24), \']\')'
                . ' WHERE a.is_reminder_sent = 1 AND a.reminders_notified IS NULL'
            );
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('appointments') && $this->db->field_exists('reminders_notified', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'reminders_notified');
        }

        if ($this->db->table_exists('messaging_settings') && $this->db->field_exists('reminder_offsets', 'messaging_settings')) {
            $this->dbforge->drop_column('messaging_settings', 'reminder_offsets');
        }
    }
}