<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Adds call and Instagram to the tenant default notification channel choices.
 */
class Migration_Expand_default_notification_channels extends EA_Migration
{
    public function up(): void
    {
        if ($this->db->table_exists('messaging_settings') && $this->db->field_exists('default_notification_channel', 'messaging_settings')) {
            $table = $this->db->protect_identifiers($this->db->dbprefix('messaging_settings'));
            $this->db->query("ALTER TABLE {$table} MODIFY `default_notification_channel` ENUM('email','sms','call','whatsapp','telegram','instagram') NOT NULL DEFAULT 'telegram'");
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('messaging_settings') && $this->db->field_exists('default_notification_channel', 'messaging_settings')) {
            $table = $this->db->protect_identifiers($this->db->dbprefix('messaging_settings'));
            $this->db->query("ALTER TABLE {$table} MODIFY `default_notification_channel` ENUM('email','sms','whatsapp','telegram') NOT NULL DEFAULT 'telegram'");
        }
    }
}
