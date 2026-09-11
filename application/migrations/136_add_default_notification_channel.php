<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Bildirim Motoru varsayılan kanalı (2026-09-11).
 *
 * Adds to `messaging_settings`:
 *   - default_notification_channel   ENUM('email','sms','whatsapp','telegram'),
 *     defaults to 'telegram' so existing tenants (Salon Flora) keep their
 *     current behavior - notify_appointment_saved()/notify_appointment_deleted()
 *     already always sent email + telegram to the customer unconditionally;
 *     this setting is what previously hardcoded "and telegram" choice, now
 *     tenant-configurable to sms/whatsapp/email instead.
 * -------------------------------------------------------------------------- */

class Migration_Add_default_notification_channel extends CI_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists('messaging_settings')) {
            return;
        }

        if (!$this->db->field_exists('default_notification_channel', 'messaging_settings')) {
            $this->dbforge->add_column('messaging_settings', [
                'default_notification_channel' => [
                    'type' => "ENUM('email','sms','whatsapp','telegram')",
                    'default' => 'telegram',
                    'null' => false,
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('messaging_settings') && $this->db->field_exists('default_notification_channel', 'messaging_settings')) {
            $this->dbforge->drop_column('messaging_settings', 'default_notification_channel');
        }
    }
}
