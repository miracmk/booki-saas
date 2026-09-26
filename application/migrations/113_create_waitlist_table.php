<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Waitlist, wave 1 (2026-08-28).
 *
 * waitlist_entries: a customer's request to be notified when a slot opens up
 * for a service (optionally with a specific provider and/or date/time window
 * requested). Notified entries get a claim window (notify_expires_at) - if
 * the customer doesn't book before it lapses, Waitlist_model::expire_stale()
 * (called lazily, no cron) flips them back to 'waiting' so the next matching
 * entry can be notified.
 * ---------------------------------------------------------------------------- */

class Migration_Create_waitlist_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('waitlist_entries')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_users_customer' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'comment' => 'FK to users.id'],
                'id_services' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'comment' => 'FK to services.id'],
                'id_users_provider' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'FK to users.id, NULL = any provider offering the service',
                ],
                'requested_date' => ['type' => 'DATE', 'null' => true, 'comment' => 'NULL = any date'],
                'requested_time_window_start' => ['type' => 'TIME', 'null' => true],
                'requested_time_window_end' => ['type' => 'TIME', 'null' => true],
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['waiting', 'notified', 'converted', 'expired', 'cancelled'],
                    'default' => 'waiting',
                    'null' => false,
                ],
                'notify_channel' => [
                    'type' => 'ENUM',
                    'constraint' => ['sms', 'whatsapp', 'both'],
                    'default' => 'both',
                    'null' => false,
                ],
                'notified_at' => ['type' => 'DATETIME', 'null' => true],
                'notify_expires_at' => ['type' => 'DATETIME', 'null' => true, 'comment' => 'Claim window deadline for the current notification'],
                'converted_appointment_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key(['id_services', 'id_users_provider', 'status']);
            $this->dbforge->add_key('id_users_customer');

            $this->dbforge->create_table('waitlist_entries');
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $this->dbforge->drop_table('waitlist_entries');
    }
}
