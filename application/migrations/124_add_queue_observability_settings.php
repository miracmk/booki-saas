<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Queue & Observability Settings (Faz 32+33, 2026-08-28).
 *
 * Seeds the initial configuration for the unified job queue:
 * - queue_enabled: whether jobs are processed (default: disabled during faz 33)
 * - queue_max_attempts: default number of retry attempts for failed jobs
 * - health_token: secret token used to authorize the health check endpoint
 * - log_format: output format for structured logging (e.g., 'json')
 *
 * Each of these settings is idempotent - if already present, the existing value
 * is left untouched, so re-running the migration is safe (it won't overwrite
 * any later customizations).
 * ---------------------------------------------------------------------------- */

class Migration_Add_queue_observability_settings extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $this->set_setting('queue_enabled', '0');
        $this->set_setting('queue_max_attempts', '3');
        $this->set_setting('health_token', bin2hex(random_bytes(32)));
        $this->set_setting('log_format', 'json');
    }

    /**
     * Downgrade method.
     *
     * Removes the queue and observability settings added by this migration.
     */
    public function down(): void
    {
        $this->delete_setting('queue_enabled');
        $this->delete_setting('queue_max_attempts');
        $this->delete_setting('health_token');
        $this->delete_setting('log_format');
    }

    /**
     * Helper: set a setting only if it doesn't already exist.
     * If it exists, leave it untouched (idempotent).
     */
    private function set_setting(string $name, string $value): void
    {
        $existing = $this->db->get_where('settings', ['name' => $name])->row_array();

        if (!$existing) {
            $this->db->insert('settings', ['name' => $name, 'value' => $value]);
        }
    }

    /**
     * Helper: delete a setting if it exists.
     */
    private function delete_setting(string $name): void
    {
        $this->db->delete('settings', ['name' => $name]);
    }
}
