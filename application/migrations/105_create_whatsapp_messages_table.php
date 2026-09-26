<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - WhatsApp messages log table (2026-08-27).
 *
 * Inbound/outbound WhatsApp message log for staff visibility and manual reply.
 * Mirrors the design of telegram_messages for consistency.
 * ---------------------------------------------------------------------------- */

class Migration_Create_whatsapp_messages_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('whatsapp_messages')) {
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
                'wa_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
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
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['sent', 'delivered', 'read', 'failed'],
                    'null' => true,
                ],
                'template_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('whatsapp_messages', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('whatsapp_messages') .
                    ' ADD INDEX idx_whatsapp_messages_wa_id (wa_id)',
            );
            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('whatsapp_messages') .
                    ' ADD INDEX idx_whatsapp_messages_id_users (id_users)',
            );
            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('whatsapp_messages') .
                    ' ADD INDEX idx_whatsapp_messages_created_at (created_at)',
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('whatsapp_messages')) {
            $this->dbforge->drop_table('whatsapp_messages');
        }
    }
}
