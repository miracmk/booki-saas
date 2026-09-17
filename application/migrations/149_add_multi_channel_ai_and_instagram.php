<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration 149: Add multi-channel AI auto-reply toggles to messaging_settings,
 * create instagram_messages table for Instagram logging, and add instagram_user_id to users.
 */
class Migration_Add_multi_channel_ai_and_instagram extends EA_Migration
{
    public function up(): void
    {
        // 1. Add AI reply columns and Instagram webhook verify token to messaging_settings
        if ($this->db->table_exists('messaging_settings')) {
            $fields_to_add = [];

            if (!$this->db->field_exists('ai_reply_whatsapp_enabled', 'messaging_settings')) {
                $fields_to_add['ai_reply_whatsapp_enabled'] = [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 0,
                ];
            }

            if (!$this->db->field_exists('ai_reply_telegram_enabled', 'messaging_settings')) {
                $fields_to_add['ai_reply_telegram_enabled'] = [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 0,
                ];
            }

            if (!$this->db->field_exists('ai_reply_instagram_enabled', 'messaging_settings')) {
                $fields_to_add['ai_reply_instagram_enabled'] = [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 0,
                ];
            }

            if (!$this->db->field_exists('instagram_webhook_verify_token', 'messaging_settings')) {
                $fields_to_add['instagram_webhook_verify_token'] = [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'default' => null,
                ];
            }

            if (!empty($fields_to_add)) {
                $this->dbforge->add_column('messaging_settings', $fields_to_add);
            }
        }

        // 2. Create instagram_messages table
        if (!$this->db->table_exists('instagram_messages')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_users' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'instagram_user_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => false,
                ],
                'direction' => [
                    'type' => 'ENUM',
                    'constraint' => ['in', 'out'],
                    'default' => 'in',
                    'null' => false,
                ],
                'message' => [
                    'type' => 'TEXT',
                    'null' => false,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users');
            $this->dbforge->add_key('instagram_user_id');
            $this->dbforge->add_key('created_at');

            $this->dbforge->create_table('instagram_messages', true);
        }

        // 3. Add instagram_user_id to users for customer matching
        if ($this->db->table_exists('users') && !$this->db->field_exists('instagram_user_id', 'users')) {
            $this->dbforge->add_column('users', [
                'instagram_user_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('users') && $this->db->field_exists('instagram_user_id', 'users')) {
            $this->dbforge->drop_column('users', 'instagram_user_id');
        }

        if ($this->db->table_exists('instagram_messages')) {
            $this->dbforge->drop_table('instagram_messages');
        }

        if ($this->db->table_exists('messaging_settings')) {
            $columns = [
                'ai_reply_whatsapp_enabled',
                'ai_reply_telegram_enabled',
                'ai_reply_instagram_enabled',
                'instagram_webhook_verify_token',
            ];
            foreach ($columns as $column) {
                if ($this->db->field_exists($column, 'messaging_settings')) {
                    $this->dbforge->drop_column('messaging_settings', $column);
                }
            }
        }
    }
}
