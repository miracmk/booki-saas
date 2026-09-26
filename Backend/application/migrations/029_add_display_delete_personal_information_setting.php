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

class Migration_Add_display_delete_personal_information_setting extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->get_where('settings', ['name' => 'display_delete_personal_information'])->num_rows()) {
            $this->db->insert('settings', [
                'name' => 'display_delete_personal_information',
                'value' => '0',
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->get_where('settings', ['name' => 'display_delete_personal_information'])->num_rows()) {
            $this->db->delete('settings', ['name' => 'display_delete_personal_information']);
        }
    }
}
