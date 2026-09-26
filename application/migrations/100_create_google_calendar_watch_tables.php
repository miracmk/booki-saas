<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Google Calendar push-notification sync (Faz 2/3/4, 2026-08-26).
 *
 * Adds incremental sync (Google `syncToken`) and webhook push notifications
 * (`events.watch()`) on top of the existing full-window `console sync` cron.
 * `google_calendar_watch_channels` tracks one active watch channel per provider
 * (channels expire and must be renewed); `google_calendar_sync_log` records every
 * sync attempt (cron full-scan, webhook-triggered incremental, or channel renewal)
 * for the admin dashboard.
 * ---------------------------------------------------------------------------- */

class Migration_Create_google_calendar_watch_tables extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('google_calendar_watch_channels')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_users_provider' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'calendar_id' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'channel_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'resource_id' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                // Shared secret Google echoes back in X-Goog-Channel-Token on every push notification;
                // used to verify the notification actually concerns this channel before trusting it.
                'channel_token' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
                'expiration' => ['type' => 'DATETIME', 'null' => false],
                // Opaque cursor for incremental `events.list(syncToken=...)`. Google invalidates it
                // (410 GONE) if too much time passes without polling - callers must fall back to a
                // full re-sync and store the fresh token from that response.
                'sync_token' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
                'updated_at' => ['type' => 'DATETIME', 'null' => false],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('google_calendar_watch_channels', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('google_calendar_watch_channels') .
                    ' ADD UNIQUE INDEX idx_gcwc_provider_calendar (id_users_provider, calendar_id),' .
                    ' ADD INDEX idx_gcwc_channel (channel_id)',
            );
        }

        if (!$this->db->table_exists('google_calendar_sync_log')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_users_provider' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'trigger' => [
                    'type' => 'ENUM',
                    'constraint' => ['cron_full_scan', 'webhook_incremental', 'channel_renewal'],
                    'null' => false,
                ],
                'status' => ['type' => 'ENUM', 'constraint' => ['success', 'failed'], 'null' => false],
                'event_count' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
                'error_message' => ['type' => 'TEXT', 'null' => true],
                'synced_at' => ['type' => 'DATETIME', 'null' => false],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('google_calendar_sync_log', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('google_calendar_sync_log') .
                    ' ADD INDEX idx_gcsl_provider (id_users_provider)',
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        foreach (['google_calendar_sync_log', 'google_calendar_watch_channels'] as $table) {
            if ($this->db->table_exists($table)) {
                $this->dbforge->drop_table($table);
            }
        }
    }
}
