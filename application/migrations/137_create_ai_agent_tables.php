<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - AI Asistan (Dalga 4, 2026-09-12).
 *
 * Admin-panel-internal AI agent (tool-calling, provider-agnostic via
 * OpenRouter - see Ai_agent_client.php) that can answer small data questions
 * (customer lookup, appointment history) and PROPOSE customer-record updates.
 * It never writes directly to `customers`/`ea_users` - every proposed change
 * lands in `ai_agent_pending_changes` and only takes effect once an admin
 * approves it (see Ai_agent.php::approve()). This is the audit trail for
 * that approval workflow.
 *
 * Conversation history itself is kept in the PHP session (not DB) for now -
 * out of scope for this first pass, see docs/SESSION_NOTES.md.
 * -------------------------------------------------------------------------- */

class Migration_Create_ai_agent_tables extends CI_Migration
{
    public function up(): void
    {
        // ── ai_agent_pending_changes ────────────────────────────────────
        if (!$this->db->table_exists('ai_agent_pending_changes')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE],
                'target_table' => ['type' => 'VARCHAR', 'constraint' => 64],
                'target_id' => ['type' => 'INT', 'unsigned' => TRUE],
                'changes' => ['type' => 'TEXT'], // JSON: {field: new_value, ...}
                'reason' => ['type' => 'TEXT', 'null' => TRUE], // model's own justification, shown to the approver
                'model_name' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => TRUE],
                'status' => ['type' => "ENUM('pending','approved','rejected')", 'default' => 'pending'],
                'created_at' => ['type' => 'DATETIME'],
                'resolved_at' => ['type' => 'DATETIME', 'null' => TRUE],
                'resolved_by' => ['type' => 'INT', 'unsigned' => TRUE, 'null' => TRUE],
            ]);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key('status');
            $this->dbforge->add_key(['target_table', 'target_id']);
            $this->dbforge->create_table('ai_agent_pending_changes', TRUE);
        }

        // ── ea_roles: ai_agent bitmask (1=view 2=add 4=edit 8=del 15=all) ─
        if ($this->db->field_exists('reviews', 'roles') && !$this->db->field_exists('ai_agent', 'roles')) {
            $this->dbforge->add_column('roles', [
                'ai_agent' => ['type' => 'INT', 'unsigned' => TRUE, 'default' => 0, 'after' => 'reviews'],
            ]);
        } elseif (!$this->db->field_exists('ai_agent', 'roles')) {
            $this->dbforge->add_column('roles', [
                'ai_agent' => ['type' => 'INT', 'unsigned' => TRUE, 'default' => 0, 'after' => 'is_admin'],
            ]);
        }

        // Admin role gets full access; other roles stay 0 (opt-in per tenant).
        $this->db->update('roles', ['ai_agent' => '15'], ['slug' => 'admin']);
    }

    public function down(): void
    {
        if ($this->db->table_exists('ai_agent_pending_changes')) {
            $this->dbforge->drop_table('ai_agent_pending_changes');
        }

        if ($this->db->field_exists('ai_agent', 'roles')) {
            $this->dbforge->drop_column('roles', 'ai_agent');
        }
    }
}
