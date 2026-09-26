<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

class Migration_Create_data_requests_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('data_requests')) {
            $fields = [
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                    'null' => false,
                ],
                'id_users' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'request_type' => [
                    'type' => 'ENUM',
                    'constraint' => ['export', 'erasure'],
                    'null' => false,
                ],
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['pending', 'processing', 'ready', 'failed', 'expired', 'completed'],
                    'null' => false,
                    'default' => 'pending',
                ],
                'requested_by' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'token_hash' => [
                    'type' => 'CHAR',
                    'constraint' => '64',
                    'null' => true,
                ],
                'expires' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'file_path' => [
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => true,
                ],
                'file_size' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'format' => [
                    'type' => 'VARCHAR',
                    'constraint' => '20',
                    'null' => true,
                ],
                'error_message' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'notes' => [
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => true,
                ],
                'ip_address' => [
                    'type' => 'VARCHAR',
                    'constraint' => '45',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ];

            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users');
            $this->dbforge->add_key('status');
            $this->dbforge->add_key(['request_type', 'status']);
            $this->dbforge->add_key('expires');

            $this->dbforge->create_table('data_requests');

            // BooKi - CI3's add_key() (system/database/DB_forge.php) only has 2 real
            // parameters ($key, $primary) - it silently ignores any 3rd/4th argument, so there is
            // no add_key(..., true) shortcut for a UNIQUE index in this codebase (confirmed by
            // actually inspecting a real generated schema: several pre-existing tables that pass
            // extra args to add_key() expecting uniqueness get a plain, non-unique KEY instead).
            // A real UNIQUE constraint needs the raw-SQL ALTER pattern already used elsewhere in
            // this codebase (e.g. Console::master_install()'s tenants.subdomain index).
            //
            // For data_requests.token_hash: MySQL allows multiple NULLs in a UNIQUE index, so
            // multiple erasure rows (token_hash always NULL) and multiple not-yet-ready export
            // rows (token_hash also NULL until the export is packaged) coexist fine - this is safe.
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('data_requests') . ' ADD UNIQUE INDEX idx_data_requests_token_hash (token_hash)',
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('data_requests')) {
            $this->dbforge->drop_table('data_requests');
        }
    }
}
