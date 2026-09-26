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

class Migration_Add_totp_columns_to_user_settings_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('totp_secret', 'user_settings')) {
            $fields = [
                'totp_secret' => [
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => true,
                    'after' => 'password_reset_expires',
                ],
            ];

            $this->dbforge->add_column('user_settings', $fields);
        }

        if (!$this->db->field_exists('totp_enabled', 'user_settings')) {
            $fields = [
                'totp_enabled' => [
                    'type' => 'TINYINT',
                    'constraint' => '1',
                    'null' => false,
                    'default' => 0,
                    'after' => 'totp_secret',
                ],
            ];

            $this->dbforge->add_column('user_settings', $fields);
        }

        if (!$this->db->field_exists('totp_confirmed_at', 'user_settings')) {
            $fields = [
                'totp_confirmed_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'totp_enabled',
                ],
            ];

            $this->dbforge->add_column('user_settings', $fields);
        }

        if (!$this->db->field_exists('totp_backup_codes', 'user_settings')) {
            $fields = [
                'totp_backup_codes' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'totp_confirmed_at',
                ],
            ];

            $this->dbforge->add_column('user_settings', $fields);
        }

        if (!$this->db->field_exists('totp_last_step', 'user_settings')) {
            $fields = [
                'totp_last_step' => [
                    'type' => 'BIGINT',
                    'null' => true,
                    'after' => 'totp_backup_codes',
                ],
            ];

            $this->dbforge->add_column('user_settings', $fields);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('totp_secret', 'user_settings')) {
            $this->dbforge->drop_column('user_settings', 'totp_secret');
        }

        if ($this->db->field_exists('totp_enabled', 'user_settings')) {
            $this->dbforge->drop_column('user_settings', 'totp_enabled');
        }

        if ($this->db->field_exists('totp_confirmed_at', 'user_settings')) {
            $this->dbforge->drop_column('user_settings', 'totp_confirmed_at');
        }

        if ($this->db->field_exists('totp_backup_codes', 'user_settings')) {
            $this->dbforge->drop_column('user_settings', 'totp_backup_codes');
        }

        if ($this->db->field_exists('totp_last_step', 'user_settings')) {
            $this->dbforge->drop_column('user_settings', 'totp_last_step');
        }
    }
}
