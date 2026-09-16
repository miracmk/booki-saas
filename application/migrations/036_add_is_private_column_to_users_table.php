<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Open Source Web Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) 2013 - 2020, Ki Software
 * @license     http://opensource.org/licenses/Proprietary - Ki Software License
 * @link        http://kisoftware.com
 * @since       v1.4.0
 * ---------------------------------------------------------------------------- */

class Migration_Add_is_private_column_to_users_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('is_private', 'users')) {
            $fields = [
                'is_private' => [
                    'type' => 'TINYINT',
                    'constraint' => '4',
                    'default' => '0',
                    'after' => 'language',
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
        if ($this->db->field_exists('is_private', 'users')) {
            $this->dbforge->drop_column('users', 'is_private');
        }
    }
}
