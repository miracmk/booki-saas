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

/**
 * Migration: Add data retention days setting.
 *
 * Adds a setting for automatic personal data erasure after X days.
 * When set to 0, automatic erasure is disabled.
 */
class Migration_Add_data_retention_days_setting extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $this->db->insert('settings', [
            'name' => 'data_retention_days',
            'value' => '0',
        ]);
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $this->db->delete('settings', ['name' => 'data_retention_days']);
    }
}
