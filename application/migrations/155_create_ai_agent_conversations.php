<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Migration 155: AI agent long-term memory + conversation archive.
 *
 * The ACTIVE conversation lives in the PHP session. When a conversation is
 * closed (5-minute idle timeout, manual reset, or the session thread cap),
 * it becomes its OWN row here (per user): raw messages are archived into
 * `ai_agent_messages` under that row (audit trail, tool pairs kept intact)
 * and a per-conversation summary is stored on the row. Long-term memory is
 * per-customer: each turn injects the summaries of that user's most recent
 * closed conversations (see Ai_agent_conversations_model::get_recent_summaries()),
 * never a single global blob. This fixes the recurring "context loss"
 * problem of the AI Asistan (previously session-only history, see migration
 * 137) without unbounded token growth.
 * -------------------------------------------------------------------------- */

class Migration_Create_ai_agent_conversations extends EA_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists('ai_agent_conversations')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE],
                'id_users' => ['type' => 'INT', 'unsigned' => TRUE],
                'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE],
                'summary' => ['type' => 'TEXT', 'null' => TRUE],
                'summarized_until' => ['type' => 'INT', 'unsigned' => TRUE, 'default' => 0],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME', 'null' => TRUE],
            ]);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key('id_users');
            $this->dbforge->create_table('ai_agent_conversations', TRUE);
        }

        if (!$this->db->table_exists('ai_agent_messages')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE],
                'id_conversation' => ['type' => 'INT', 'unsigned' => TRUE],
                'role' => ['type' => "ENUM('user','assistant','tool')"],
                'content' => ['type' => 'MEDIUMTEXT'],
                'tool_calls' => ['type' => 'TEXT', 'null' => TRUE], // JSON, assistant messages only
                'tool_call_id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => TRUE],
                'tool_name' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => TRUE],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key('id_conversation');
            $this->dbforge->create_table('ai_agent_messages', TRUE);
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('ai_agent_messages')) {
            $this->dbforge->drop_table('ai_agent_messages');
        }

        if ($this->db->table_exists('ai_agent_conversations')) {
            $this->dbforge->drop_table('ai_agent_conversations');
        }
    }
}
