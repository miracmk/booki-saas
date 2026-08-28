<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Unified Background Jobs & Webhook Delivery Tracking (Faz 32+33, 2026-08-28).
 *
 * This table implements a unified job queue for all asynchronous work: email sends,
 * SMS/WhatsApp notifications, webhook deliveries, long-running exports, and more.
 * It is also the system of record for webhook delivery status (retries, failures, etc).
 *
 * CRITICAL SECURITY NOTES:
 * - The `handler` column is NEVER eval'd or call_user_func()'d from this database value.
 *   It is resolved exclusively via a hardcoded whitelist in Job_dispatcher::HANDLERS,
 *   eliminating RCE risk entirely. Never pass this column to any dynamic callable resolution.
 * - The `payload` column MUST contain only database IDs and other non-PII values.
 *   Raw phone numbers, email addresses, encrypted passwords, or any other PII must NEVER
 *   be stored in this column. Each worker re-resolves recipient information at send time
 *   using the recipient model's existing decryption path, ensuring secrets are never logged
 *   or queued unencrypted.
 *
 * Job lifecycle:
 * - pending: waiting to be processed (available_at <= now)
 * - reserved: currently being processed by a worker (reserved_by/reserved_at set)
 * - succeeded: processed successfully
 * - failed: all retry attempts exhausted (attempts >= max_attempts)
 *
 * Exponential backoff on failure: available_at is set to now() + (60 * 2^attempts) on retry.
 * ---------------------------------------------------------------------------- */

class Migration_Create_jobs_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('jobs')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'queue' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => false,
                    'default' => 'default',
                ],
                'channel' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => false,
                ],
                'handler' => [
                    'type' => 'VARCHAR',
                    'constraint' => 191,
                    'null' => false,
                ],
                'payload' => [
                    'type' => 'LONGTEXT',
                    'null' => false,
                ],
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['pending', 'reserved', 'succeeded', 'failed'],
                    'null' => false,
                    'default' => 'pending',
                ],
                'attempts' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                    'default' => 0,
                ],
                'max_attempts' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                    'default' => 3,
                ],
                'available_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'reserved_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'reserved_by' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                ],
                'completed_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'last_error' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'reference_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => true,
                ],
                'reference_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'correlation_id' => [
                    'type' => 'CHAR',
                    'constraint' => 32,
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('jobs', true, ['engine' => 'InnoDB']);

            // Add indexes for efficient querying
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('jobs') .
                ' ADD INDEX idx_jobs_status_available (status, available_at),' .
                ' ADD INDEX idx_jobs_channel_status (channel, status),' .
                ' ADD INDEX idx_jobs_reference (reference_type, reference_id),' .
                ' ADD INDEX idx_jobs_correlation (correlation_id)',
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('jobs')) {
            $this->dbforge->drop_table('jobs');
        }
    }
}
