<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Memberships, wave 1 (2026-08-28).
 *
 * membership_plans: subscription plan definitions (e.g. "Aylık 4 Seans Cilt
 * Bakımı Üyeliği" - price + billing period + how many sessions of a service
 * the plan grants per period). One plan = one service, mirroring the
 * existing single-service Packages model rather than a many-to-many plan.
 *
 * customer_memberships: one row per customer's active/past subscription.
 * Renewal is LAZY (checked via Customer_memberships_model::renew_if_due() on
 * every read, e.g. at checkout or on the membership list page) rather than
 * cron-driven - this codebase has no background job/queue infrastructure yet
 * (see docs/ROADMAP.md gap #18, Dalga 2). A membership past its
 * current_period_end with auto_renew=1 attempts a renewal charge on next
 * access; on failure it becomes 'past_due' (a grace state, not cancelled).
 *
 * Deliberately separate tables from customer_packages/customer_package_sessions
 * - Memberships is recurring entitlement (auto-renewing period), Packages is
 * a fixed one-time purchase; merging them would break Packages' existing
 * exhaustion semantics.
 * ---------------------------------------------------------------------------- */

class Migration_Create_membership_tables extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('membership_plans')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => false],
                'id_services' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'comment' => 'FK to services.id'],
                'billing_period' => [
                    'type' => 'ENUM',
                    'constraint' => ['monthly', 'quarterly', 'yearly'],
                    'default' => 'monthly',
                    'null' => false,
                ],
                'price' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
                'sessions_per_period' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'NULL = unlimited sessions per period',
                ],
                'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'null' => false],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_services');
            $this->dbforge->add_key('is_active');

            $this->dbforge->create_table('membership_plans');
        }

        if (!$this->db->table_exists('customer_memberships')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_users_customer' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'comment' => 'FK to users.id'],
                'id_membership_plans' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'comment' => 'FK to membership_plans.id'],
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['active', 'past_due', 'cancelled', 'expired'],
                    'default' => 'active',
                    'null' => false,
                ],
                'current_period_start' => ['type' => 'DATETIME', 'null' => false],
                'current_period_end' => ['type' => 'DATETIME', 'null' => false],
                'sessions_used_this_period' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0, 'null' => false],
                'auto_renew' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'null' => false],
                'last_renewed_at' => ['type' => 'DATETIME', 'null' => true],
                'cancelled_at' => ['type' => 'DATETIME', 'null' => true],
                'sold_by' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'FK to users.id (admin/secretary who created it)',
                ],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users_customer');
            $this->dbforge->add_key('status');
            $this->dbforge->add_key('current_period_end');

            $this->dbforge->create_table('customer_memberships');
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $this->dbforge->drop_table('customer_memberships');
        $this->dbforge->drop_table('membership_plans');
    }
}
