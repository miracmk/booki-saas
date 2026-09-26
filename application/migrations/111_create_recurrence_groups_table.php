<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Recurring appointments, wave 1 (2026-08-28).
 *
 * recurrence_groups: one row per "create a series of N appointments" request.
 * Individual appointments link back to their group via
 * appointments.id_recurrence_group (migration 112, INT FK to this table's id
 * - the group row is inserted first via Recurrence_service::create_series(),
 * then each occurrence is booked one at a time through the normal
 * Appointment_booking_service, referencing the already-known group id).
 * occurrences_created may be less than occurrences_total if some dates
 * conflicted and were skipped - this is the audit trail for "8/10 randevu
 * oluşturuldu, 2 tanesi çakışma nedeniyle atlandı".
 * ---------------------------------------------------------------------------- */

class Migration_Create_recurrence_groups_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('recurrence_groups')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => 'FK to users.id'],
                'id_services' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'comment' => 'FK to services.id'],
                'id_users_provider' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'comment' => 'FK to users.id'],
                'id_users_customer' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'comment' => 'FK to users.id'],
                'frequency' => [
                    'type' => 'ENUM',
                    'constraint' => ['weekly', 'biweekly', 'monthly'],
                    'default' => 'weekly',
                    'null' => false,
                ],
                'interval_count' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 1, 'null' => false],
                'occurrences_total' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'occurrences_created' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0, 'null' => false],
                'start_date' => ['type' => 'DATE', 'null' => false],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 16,
                    'default' => 'active',
                    'null' => false,
                    'comment' => 'active, completed, cancelled',
                ],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users_customer');
            $this->dbforge->add_key('status');

            $this->dbforge->create_table('recurrence_groups');
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $this->dbforge->drop_table('recurrence_groups');
    }
}
