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

class Migration_Add_altcha_settings extends CI_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $settings = [
            [
                'name' => 'altcha_enabled',
                'value' => '0',
            ],
            [
                'name' => 'altcha_hmac_key',
                'value' => '',
            ],
            [
                'name' => 'altcha_max_number',
                'value' => '100000',
            ],
            [
                'name' => 'altcha_expires',
                'value' => '300',
            ],
        ];

        foreach ($settings as $setting) {
            $existing = $this->db->get_where('settings', ['name' => $setting['name']])->row_array();

            if (empty($existing)) {
                $this->db->insert('settings', $setting);
            }
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $setting_names = ['altcha_enabled', 'altcha_hmac_key', 'altcha_max_number', 'altcha_expires'];

        foreach ($setting_names as $name) {
            $this->db->delete('settings', ['name' => $name]);
        }
    }
}
