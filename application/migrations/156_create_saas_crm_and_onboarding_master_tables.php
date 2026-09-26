<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Migration 156: SaaS Sales CRM, Leads & Onboarding Master Tables.
 * -------------------------------------------------------------------------- */

class Migration_Create_saas_crm_and_onboarding_master_tables extends App_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists('leads')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'sector' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
                'district' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'address' => ['type' => 'TEXT', 'null' => true],
                'contact_person' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'phone' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'whatsapp' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'email' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'website' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'instagram' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'reservation_type' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'verification' => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Doğrulanmış', 'null' => true],
                'stage' => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Visit Planned', 'null' => false],
                'priority' => ['type' => 'ENUM', 'constraint' => ['low', 'medium', 'high', 'urgent'], 'default' => 'medium', 'null' => false],
                'lead_source' => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Field Research', 'null' => false],
                'owner_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'owner_name' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'package' => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Henüz Seçilmedi', 'null' => true],
                'billing_period' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'Aylık', 'null' => true],
                'potential_mrr' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00, 'null' => false],
                'demo_start_date' => ['type' => 'DATE', 'null' => true],
                'demo_end_date' => ['type' => 'DATE', 'null' => true],
                'trial_status' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
                'next_action' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'next_action_date' => ['type' => 'DATE', 'null' => true],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'tags' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'converted_tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'conversion_date' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('leads', true, ['engine' => 'InnoDB']);
        }

        if (!$this->db->table_exists('lead_activities')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_leads' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'activity_type' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'description' => ['type' => 'TEXT', 'null' => true],
                'performed_by' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
                'metadata_json' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('lead_activities', true, ['engine' => 'InnoDB']);
        }

        if (!$this->db->table_exists('lead_stage_history')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_leads' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'old_stage' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'new_stage' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'changed_by' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
                'reason_notes' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('lead_stage_history', true, ['engine' => 'InnoDB']);
        }

        if (!$this->db->table_exists('lead_tasks')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_leads' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'task_type' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'due_date' => ['type' => 'DATE', 'null' => false],
                'due_time' => ['type' => 'TIME', 'null' => true],
                'priority' => ['type' => 'ENUM', 'constraint' => ['low', 'medium', 'high', 'urgent'], 'default' => 'medium', 'null' => false],
                'assigned_to' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'in_progress', 'completed', 'cancelled'], 'default' => 'pending', 'null' => false],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'completed_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('lead_tasks', true, ['engine' => 'InnoDB']);
        }

        if (!$this->db->table_exists('lead_visits')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_leads' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'visit_date' => ['type' => 'DATETIME', 'null' => false],
                'contact_person' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'position' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'current_booking_method' => ['type' => 'TEXT', 'null' => true],
                'current_system' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'staff_count' => ['type' => 'INT', 'default' => 0, 'null' => false],
                'resource_count' => ['type' => 'INT', 'default' => 0, 'null' => false],
                'monthly_appointments' => ['type' => 'INT', 'default' => 0, 'null' => false],
                'biggest_problem' => ['type' => 'TEXT', 'null' => true],
                'most_needed_feature' => ['type' => 'TEXT', 'null' => true],
                'uses_whatsapp' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false],
                'uses_online_booking' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false],
                'competitor_system' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'budget_approach' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'decision_maker' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'purchase_timeframe' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'objections' => ['type' => 'TEXT', 'null' => true],
                'quick_tags' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'suggested_stage' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'created_by' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('lead_visits', true, ['engine' => 'InnoDB']);
        }

        if (!$this->db->table_exists('onboarding_sessions')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_tenants' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'id_leads' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'token' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'status' => ['type' => 'ENUM', 'constraint' => ['not_opened', 'opened', 'started', 'in_progress', 'completed'], 'default' => 'not_opened', 'null' => false],
                'current_step' => ['type' => 'INT', 'default' => 1, 'null' => false],
                'total_steps' => ['type' => 'INT', 'default' => 10, 'null' => false],
                'progress_percent' => ['type' => 'INT', 'default' => 0, 'null' => false],
                'session_data_json' => ['type' => 'MEDIUMTEXT', 'null' => true],
                'expires_at' => ['type' => 'DATETIME', 'null' => false],
                'first_opened_at' => ['type' => 'DATETIME', 'null' => true],
                'last_activity_at' => ['type' => 'DATETIME', 'null' => true],
                'completed_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('onboarding_sessions', true, ['engine' => 'InnoDB']);
        }

        if (!$this->db->table_exists('master_audit_logs')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'actor_username' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
                'action' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'entity_type' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'entity_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'description' => ['type' => 'TEXT', 'null' => true],
                'ip_address' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'metadata_json' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('master_audit_logs', true, ['engine' => 'InnoDB']);
        }
    }

    public function down(): void
    {
        // Tables are preserved on downgrade in master DB
    }
}

