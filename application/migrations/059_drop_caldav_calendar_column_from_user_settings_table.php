<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

class Migration_Drop_caldav_calendar_column_from_user_settings_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if ($this->db->field_exists('caldav_calendar', 'user_settings')) {
            $this->dbforge->drop_column('user_settings', 'caldav_calendar');
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if (!$this->db->field_exists('caldav_calendar', 'user_settings')) {
            $fields = [
                'caldav_calendar' => [
                    'type' => 'VARCHAR',
                    'constraint' => '256',
                    'null' => true,
                    'after' => 'caldav_password',
                ],
            ];

            $this->dbforge->add_column('user_settings', $fields);
        }
    }
}
