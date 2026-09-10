<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Analytics indexes on appointments table (Faz 3.6, 2026-09-10).
 *
 * Adds composite indexes to optimize daily revenue reports and analytics queries
 * on the appointments table. Idempotent: checks with SHOW INDEX before adding.
 * ---------------------------------------------------------------------------- */

class Migration_Add_analytics_indexes_to_appointments extends CI_Migration
{
    public function up(): void
    {
        $table = $this->db->dbprefix('appointments');

        // idx_appt_provider_end: Filter by provider, order by end time (common revenue queries)
        $exists = $this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = ?", ['idx_appt_provider_end'])->num_rows();
        if (!$exists) {
            $this->db->query("ALTER TABLE {$table} ADD KEY idx_appt_provider_end (id_users_provider, actual_end_datetime)");
        }

        // idx_appt_service_end: Filter by service, order by end time
        $exists = $this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = ?", ['idx_appt_service_end'])->num_rows();
        if (!$exists) {
            $this->db->query("ALTER TABLE {$table} ADD KEY idx_appt_service_end (id_services, actual_end_datetime)");
        }

        // idx_appt_customer_start: Filter by customer, order by start time (customer history)
        $exists = $this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = ?", ['idx_appt_customer_start'])->num_rows();
        if (!$exists) {
            $this->db->query("ALTER TABLE {$table} ADD KEY idx_appt_customer_start (id_users_customer, start_datetime)");
        }

        // idx_appt_status_start: Filter by status, order by start time (completion queries)
        $exists = $this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = ?", ['idx_appt_status_start'])->num_rows();
        if (!$exists) {
            $this->db->query("ALTER TABLE {$table} ADD KEY idx_appt_status_start (status, start_datetime)");
        }
    }

    public function down(): void
    {
        $table = $this->db->dbprefix('appointments');

        // Drop indexes if they exist
        foreach (['idx_appt_provider_end', 'idx_appt_service_end', 'idx_appt_customer_start', 'idx_appt_status_start'] as $index_name) {
            $exists = $this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index_name])->num_rows();
            if ($exists) {
                $this->db->query("ALTER TABLE {$table} DROP INDEX {$index_name}");
            }
        }
    }
}
