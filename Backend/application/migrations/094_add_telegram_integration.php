<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - native Telegram integration (2026-08-25).
 *
 * - users.telegram_chat_id / telegram_username: set once a user (admin/secretary/provider/customer)
 *   links their Telegram account by pressing a personal "Start" deep link on the bot. Not treated as
 *   PII-encrypted (unlike phone/email) - a bare numeric chat ID isn't independently identifying and
 *   staff need to look records up by it in the webhook handler, which a LIKE/exact match on ciphertext
 *   can't do.
 * - users.telegram_link_token: one-time token embedded in a user's personal deep link
 *   (t.me/<bot>?start=<token>), consumed (nulled out) the moment the link succeeds.
 * - telegram_messages: a minimal inbound/outbound log so staff have somewhere to see what a customer
 *   wrote on Telegram and reply, without building a full chat UI.
 * ---------------------------------------------------------------------------- */

class Migration_Add_telegram_integration extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('telegram_chat_id', 'users')) {
            $this->dbforge->add_column('users', [
                'telegram_chat_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                    'after' => 'last_contact_channel',
                ],
            ]);
        }

        if (!$this->db->field_exists('telegram_username', 'users')) {
            $this->dbforge->add_column('users', [
                'telegram_username' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                    'after' => 'telegram_chat_id',
                ],
            ]);
        }

        if (!$this->db->field_exists('telegram_link_token', 'users')) {
            $this->dbforge->add_column('users', [
                'telegram_link_token' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                    'after' => 'telegram_username',
                ],
            ]);
        }

        $existing_indexes = array_column(
            $this->db->query('SHOW INDEX FROM ' . $this->db->dbprefix('users'))->result_array(),
            'Key_name',
        );

        if (!in_array('idx_telegram_chat_id', $existing_indexes, true)) {
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('users') . ' ADD INDEX idx_telegram_chat_id (telegram_chat_id)',
            );
        }

        if (!in_array('idx_telegram_link_token', $existing_indexes, true)) {
            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('users') .
                    ' ADD INDEX idx_telegram_link_token (telegram_link_token)',
            );
        }

        if (!$this->db->table_exists('telegram_messages')) {
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
                'chat_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => false,
                ],
                'direction' => [
                    'type' => 'ENUM',
                    'constraint' => ['in', 'out'],
                    'null' => false,
                ],
                'message' => [
                    'type' => 'TEXT',
                    'null' => false,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('telegram_messages', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('telegram_messages') .
                    ' ADD INDEX idx_telegram_messages_chat_id (chat_id)',
            );
            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('telegram_messages') .
                    ' ADD INDEX idx_telegram_messages_created_at (created_at)',
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('telegram_messages')) {
            $this->dbforge->drop_table('telegram_messages');
        }

        $existing_indexes = array_column(
            $this->db->query('SHOW INDEX FROM ' . $this->db->dbprefix('users'))->result_array(),
            'Key_name',
        );

        if (in_array('idx_telegram_link_token', $existing_indexes, true)) {
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('users') . ' DROP INDEX idx_telegram_link_token',
            );
        }

        if (in_array('idx_telegram_chat_id', $existing_indexes, true)) {
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('users') . ' DROP INDEX idx_telegram_chat_id');
        }

        if ($this->db->field_exists('telegram_link_token', 'users')) {
            $this->dbforge->drop_column('users', 'telegram_link_token');
        }

        if ($this->db->field_exists('telegram_username', 'users')) {
            $this->dbforge->drop_column('users', 'telegram_username');
        }

        if ($this->db->field_exists('telegram_chat_id', 'users')) {
            $this->dbforge->drop_column('users', 'telegram_chat_id');
        }
    }
}
