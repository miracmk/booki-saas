<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Marketing (Dalga 3 / Faz 3.3, 2026-09-09).
 *
 * Three new tables:
 *   marketing_segments  – named customer lists with type + rules JSON
 *   marketing_campaigns  – campaign definitions with channel + message
 *   campaign_recipients  – per-recipient send tracking
 *
 * Also adds a `marketing` column to ea_roles (bitmask: 1=view 2=add 4=edit 8=del 15=all).
 * -------------------------------------------------------------------------- */

class Migration_Create_marketing_tables extends CI_Migration
{
    public function up(): void
    {
        // ── marketing_segments ──────────────────────────────────────────
        if (!$this->db->table_exists('marketing_segments')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE],
                'name' => ['type' => 'VARCHAR', 'constraint' => 128],
                'type' => ['type' => "ENUM('vip','inactive','birthday','all','custom')", 'default' => 'all'],
                'rules' => ['type' => 'TEXT', 'null' => TRUE],
                'description' => ['type' => 'TEXT', 'null' => TRUE],
                'member_count' => ['type' => 'INT', 'default' => 0],
                'enabled' => ['type' => 'TINYINT', 'default' => 1],
                'last_calculated' => ['type' => 'DATETIME', 'null' => TRUE],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table('marketing_segments');
        }

        // ── marketing_campaigns ─────────────────────────────────────────
        if (!$this->db->table_exists('marketing_campaigns')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE],
                'name' => ['type' => 'VARCHAR', 'constraint' => 128],
                'segment_id' => ['type' => 'INT', 'unsigned' => TRUE],
                'channel' => ['type' => "ENUM('email','sms','whatsapp','telegram')", 'default' => 'email'],
                'subject' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE],
                'message' => ['type' => 'TEXT'],
                'status' => ['type' => "ENUM('draft','queued','sending','sent','failed')", 'default' => 'draft'],
                'sent_count' => ['type' => 'INT', 'default' => 0],
                'failed_count' => ['type' => 'INT', 'default' => 0],
                'total_recipients' => ['type' => 'INT', 'default' => 0],
                'scheduled_at' => ['type' => 'DATETIME', 'null' => TRUE],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key('segment_id');
            $this->dbforge->add_key('status');
            $this->dbforge->create_table('marketing_campaigns');
        }

        // ── campaign_recipients ─────────────────────────────────────────
        if (!$this->db->table_exists('campaign_recipients')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE],
                'campaign_id' => ['type' => 'INT', 'unsigned' => TRUE],
                'customer_id' => ['type' => 'INT', 'unsigned' => TRUE],
                'channel' => ['type' => "ENUM('email','sms','whatsapp','telegram')"],
                'recipient' => ['type' => 'VARCHAR', 'constraint' => 255],
                'status' => ['type' => "ENUM('pending','sent','failed','bounced')", 'default' => 'pending'],
                'error_message' => ['type' => 'TEXT', 'null' => TRUE],
                'sent_at' => ['type' => 'DATETIME', 'null' => TRUE],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key('campaign_id');
            $this->dbforge->add_key('customer_id');
            $this->dbforge->create_table('campaign_recipients');
        }

        // ── ea_roles: marketing bitmask ─────────────────────────────────
        if ($this->db->field_exists('marketing', 'roles') === FALSE) {
            $this->dbforge->add_column('roles', [
                'marketing' => ['type' => 'INT', 'unsigned' => TRUE, 'default' => 0, 'after' => 'is_admin'],
            ]);
        }

        // Seed permissions (bitmask: 1=view 2=add 4=edit 8=del 15=all) - admin gets full access.
        $this->db->update('roles', ['marketing' => '15'], ['slug' => 'admin']);
    }

    public function down(): void
    {
        $this->dbforge->drop_table('campaign_recipients');
        $this->dbforge->drop_table('marketing_campaigns');
        $this->dbforge->drop_table('marketing_segments');

        if ($this->db->field_exists('marketing', 'roles')) {
            $this->dbforge->drop_column('roles', 'marketing');
        }
    }
}
