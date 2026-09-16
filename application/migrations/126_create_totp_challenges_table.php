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

class Migration_Create_totp_challenges_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('totp_challenges')) {
            $fields = [
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'auto_increment' => true,
                    'null' => false,
                ],
                'id_users' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'token_hash' => [
                    'type' => 'CHAR',
                    'constraint' => '64',
                    'null' => false,
                ],
                'expires' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'attempts' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                    'default' => 0,
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
            ];

            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users');
            $this->dbforge->add_key('expires');

            $this->dbforge->create_table('totp_challenges');

            // BooKi - CI3's add_key() (system/database/DB_forge.php) only has 2 real
            // parameters ($key, $primary) - it silently ignores any 3rd/4th argument, so there is
            // no add_key(..., true) shortcut for a UNIQUE index in this codebase (confirmed by
            // actually inspecting a real generated schema: several pre-existing tables that pass
            // extra args to add_key() expecting uniqueness get a plain, non-unique KEY instead).
            // A real UNIQUE constraint needs the raw-SQL ALTER pattern already used elsewhere in
            // this codebase (e.g. Console::master_install()'s tenants.subdomain index).
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('totp_challenges') . ' ADD UNIQUE INDEX idx_totp_challenges_token_hash (token_hash)',
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('totp_challenges')) {
            $this->dbforge->drop_table('totp_challenges');
        }
    }
}
