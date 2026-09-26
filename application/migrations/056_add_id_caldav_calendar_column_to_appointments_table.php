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

class Migration_Add_id_caldav_calendar_column_to_appointments_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('id_caldav_calendar', 'appointments')) {
            $fields = [
                'id_caldav_calendar' => [
                    'type' => 'TEXT',
                    'null' => null,
                    'after' => 'id_google_calendar',
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
        if ($this->db->field_exists('id_caldav_calendar', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'id_caldav_calendar');
        }
    }
}
