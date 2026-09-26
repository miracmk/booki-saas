<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - audit log for ISO 27001 / HIPAA-style access accountability
 * (2026-08-24). Records who did what, when, and from where, for the actions
 * that matter most for compliance review: authentication, and any action
 * that reads/writes/erases customer or provider PII or financial data. Not
 * every read is logged (that would make the table enormous and the log
 * unreadable) - see audit_helper.php for exactly which actions are wired up.
 * ---------------------------------------------------------------------------- */

class Migration_Create_audit_log_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('audit_log')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'action' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => false,
                ],
                'id_users' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'actor_role' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => true,
                ],
                'actor_label' => [
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => true,
                ],
                'entity_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => true,
                ],
                'entity_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'ip_address' => [
                    'type' => 'VARCHAR',
                    'constraint' => 45,
                    'null' => true,
                ],
                'details' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('audit_log', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('audit_log') . ' ADD INDEX idx_audit_created_at (created_at)',
            );
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('audit_log') . ' ADD INDEX idx_audit_action (action)',
            );
            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('audit_log') .
                    ' ADD INDEX idx_audit_entity (entity_type, entity_id)',
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('audit_log')) {
            $this->dbforge->drop_table('audit_log');
        }
    }
}
