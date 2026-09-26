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

class Migration_Add_language_to_users extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('language', 'users')) {
            $fields = [
                'language' => [
                    'type' => 'VARCHAR',
                    'constraint' => '256',
                    'default' => 'english',
                    'after' => 'timezone',
                ],
            ];

            $this->dbforge->add_column('users', $fields);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $this->dbforge->drop_column('users', 'language');
    }
}
