<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Memberships, wave 1 (2026-08-28).
 *
 * customer_membership_sessions: structural mirror of customer_package_sessions
 * (migration 106) - one row per appointment that consumed a session from a
 * membership, UNIQUE on id_appointments to prevent double-consumption.
 * ---------------------------------------------------------------------------- */

class Migration_Create_customer_membership_sessions_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('customer_membership_sessions')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_customer_memberships' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                    'comment' => 'FK to customer_memberships.id',
                ],
                'id_appointments' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                    'comment' => 'FK to appointments.id, UNIQUE to prevent double-usage',
                ],
                'consumed_at' => ['type' => 'DATETIME', 'null' => false],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_customer_memberships');
            $this->dbforge->add_key(['id_appointments'], false, true); // UNIQUE index

            $this->dbforge->create_table('customer_membership_sessions');
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $this->dbforge->drop_table('customer_membership_sessions');
    }
}
