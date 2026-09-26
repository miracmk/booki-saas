<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Stores multiple tenant-wide default notification channels.
 */
class Migration_Add_notification_channel_selection extends App_Migration
{
    public function up(): void
    {
        if ($this->db->table_exists('messaging_settings') && !$this->db->field_exists('default_notification_channels', 'messaging_settings')) {
            $this->dbforge->add_column('messaging_settings', [
                'default_notification_channels' => [
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => true,
                    'after' => 'default_notification_channel',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('messaging_settings') && $this->db->field_exists('default_notification_channels', 'messaging_settings')) {
            $this->dbforge->drop_column('messaging_settings', 'default_notification_channels');
        }
    }
}
