<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration 157: Add appointment reminder tracking columns and settings.
 *
 * Adds:
 * - appointments.is_reminder_sent (TINYINT)
 * - appointments.reminder_sent_at (DATETIME)
 * - messaging_settings.reminder_notifications_enabled (TINYINT)
 * - messaging_settings.reminder_hours_ahead (INT)
 */
class Migration_Add_appointment_reminder_tracking extends App_Migration
{
    public function up(): void
    {
        // 1. Appointments tracking
        if ($this->db->table_exists('appointments')) {
            $fields_to_add = [];

            if (!$this->db->field_exists('is_reminder_sent', 'appointments')) {
                $fields_to_add['is_reminder_sent'] = [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ];
            }

            if (!$this->db->field_exists('reminder_sent_at', 'appointments')) {
                $fields_to_add['reminder_sent_at'] = [
                    'type' => 'DATETIME',
                    'null' => true,
                    'default' => null,
                ];
            }

            if (!empty($fields_to_add)) {
                $this->dbforge->add_column('appointments', $fields_to_add);
            }

            // Index for efficient background worker lookup
            try {
                $existing_indexes = $this->db->query("SHOW INDEX FROM " . $this->db->dbprefix('appointments'))->result_array();
                $has_idx = false;
                foreach ($existing_indexes as $idx) {
                    if (($idx['Key_name'] ?? '') === 'idx_appointments_reminder') {
                        $has_idx = true;
                        break;
                    }
                }
                if (!$has_idx) {
                    $this->db->query("ALTER TABLE " . $this->db->dbprefix('appointments') . " ADD INDEX idx_appointments_reminder (is_reminder_sent, start_datetime)");
                }
            } catch (Throwable $e) {
                log_message('error', 'Migration 157 index creation error: ' . $e->getMessage());
            }
        }

        // 2. Messaging settings options
        if ($this->db->table_exists('messaging_settings')) {
            $msg_fields = [];

            if (!$this->db->field_exists('reminder_notifications_enabled', 'messaging_settings')) {
                $msg_fields['reminder_notifications_enabled'] = [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ];
            }

            if (!$this->db->field_exists('reminder_hours_ahead', 'messaging_settings')) {
                $msg_fields['reminder_hours_ahead'] = [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 24,
                ];
            }

            if (!empty($msg_fields)) {
                $this->dbforge->add_column('messaging_settings', $msg_fields);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('appointments')) {
            if ($this->db->field_exists('is_reminder_sent', 'appointments')) {
                $this->dbforge->drop_column('appointments', 'is_reminder_sent');
            }
            if ($this->db->field_exists('reminder_sent_at', 'appointments')) {
                $this->dbforge->drop_column('appointments', 'reminder_sent_at');
            }
        }

        if ($this->db->table_exists('messaging_settings')) {
            if ($this->db->field_exists('reminder_notifications_enabled', 'messaging_settings')) {
                $this->dbforge->drop_column('messaging_settings', 'reminder_notifications_enabled');
            }
            if ($this->db->field_exists('reminder_hours_ahead', 'messaging_settings')) {
                $this->dbforge->drop_column('messaging_settings', 'reminder_hours_ahead');
            }
        }
    }
}

