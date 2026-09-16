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

/**
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Add_appointment_status_options_setting extends CI_Migration
{
    /**
     * Upgrade method.
     *
     * @throws Exception
     */
    public function up(): void
    {
        if (!$this->db->get_where('settings', ['name' => 'appointment_status_options'])->num_rows()) {
            $this->db->insert('settings', [
                'name' => 'appointment_status_options',
                'value' => '["Booked", "Confirmed", "Rescheduled", "Cancelled", "Draft"]',
            ]);
        }
    }

    /**
     * Downgrade method.
     *
     * @throws Exception
     */
    public function down(): void
    {
        if ($this->db->get_where('settings', ['name' => 'appointment_status_options'])->num_rows()) {
            $this->db->delete('settings', ['name' => 'status_options']);
        }
    }
}
