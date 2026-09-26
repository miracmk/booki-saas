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

class Migration_Add_calendar_view_setting extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('calendar_view', 'user_settings')) {
            $fields = [
                'calendar_view' => [
                    'type' => 'VARCHAR',
                    'constraint' => '32',
                    'default' => 'default',
                ],
            ];

            $this->dbforge->add_column('user_settings', $fields);

            $this->db->update('user_settings', ['calendar_view' => 'default']);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('calendar_view', 'user_settings')) {
            $this->dbforge->drop_column('user_settings', 'calendar_view');
        }
    }
}
