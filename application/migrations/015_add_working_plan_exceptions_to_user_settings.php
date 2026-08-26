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

class Migration_Add_working_plan_exceptions_to_user_settings extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('working_plan_exceptions', 'user_settings')) {
            $fields = [
                'working_plan_exceptions' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'working_plan',
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
        if ($this->db->field_exists('working_plan_exceptions', 'user_settings')) {
            $this->dbforge->drop_column('user_settings', 'working_plan_exceptions');
        }
    }
}
