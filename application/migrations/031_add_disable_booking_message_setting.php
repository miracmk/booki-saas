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

class Migration_Add_disable_booking_message_setting extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->get_where('settings', ['name' => 'disable_booking_message'])->num_rows()) {
            $this->db->insert('settings', [
                'name' => 'disable_booking_message',
                'value' =>
                    '<p style="text-align: center;">Thanks for stopping by!</p><p style="text-align: center;">We are not accepting new appointments at the moment, please check back again later.</p>',
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->get_where('settings', ['name' => 'disable_booking_message'])->num_rows()) {
            $this->db->delete('settings', ['name' => 'disable_booking_message']);
        }
    }
}
