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

class Migration_Add_status_column_to_appointments_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('status', 'appointments')) {
            $fields = [
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => '512',
                    'default' => '',
                    'after' => 'color',
                ],
            ];

            $this->dbforge->add_column('appointments', $fields);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('status', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'status');
        }
    }
}
