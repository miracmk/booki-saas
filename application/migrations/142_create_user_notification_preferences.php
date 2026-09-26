<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Per-customer notification channel overrides.
 */
class Migration_Create_user_notification_preferences extends App_Migration
{
    public function up(): void
    {
        $this->add_channel_settings();

        if ($this->db->table_exists('user_notification_preferences')) {
            return;
        }

        $this->dbforge->add_field([
            'id_users' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'mode' => [
                'type' => 'ENUM',
                'constraint' => ['default', 'custom'],
                'default' => 'default',
                'null' => false,
            ],
            'email_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],
            'sms_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],
            'call_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],
            'whatsapp_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],
            'telegram_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],
            'instagram_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->dbforge->add_key('id_users', true);
        $this->dbforge->create_table('user_notification_preferences');
    }

    private function add_channel_settings(): void
    {
        if (!$this->db->table_exists('messaging_settings')) {
            return;
        }

        if ($this->db->field_exists('default_notification_channel', 'messaging_settings')) {
            $table = $this->db->protect_identifiers($this->db->dbprefix('messaging_settings'));
            $this->db->query("ALTER TABLE {$table} MODIFY `default_notification_channel` ENUM('email','sms','call','whatsapp','telegram','instagram') NOT NULL DEFAULT 'telegram'");
        }

        $columns = [
            'email_notifications_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'null' => false,
            ],
            'call_notifications_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],
            'call_provider' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'call_api_key' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'call_from_number' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => true,
            ],
            'telegram_notifications_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],
            'telegram_bot_token' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'instagram_notifications_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ],
            'instagram_access_token' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'instagram_account_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
        ];

        foreach ($columns as $name => $definition) {
            if (!$this->db->field_exists($name, 'messaging_settings')) {
                $this->dbforge->add_column('messaging_settings', [$name => $definition]);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('messaging_settings')) {
            foreach ([
                'email_notifications_enabled', 'call_notifications_enabled', 'call_provider', 'call_api_key',
                'call_from_number', 'telegram_notifications_enabled', 'telegram_bot_token',
                'instagram_notifications_enabled', 'instagram_access_token', 'instagram_account_id',
            ] as $name) {
                if ($this->db->field_exists($name, 'messaging_settings')) {
                    $this->dbforge->drop_column('messaging_settings', $name);
                }
            }
        }

        if ($this->db->table_exists('user_notification_preferences')) {
            $this->dbforge->drop_table('user_notification_preferences');
        }
    }
}
