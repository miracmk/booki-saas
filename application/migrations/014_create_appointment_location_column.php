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

class Migration_Create_appointment_location_column extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('location', 'appointments')) {
            $fields = [
                'location' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'end_datetime',
                ],
            ];

            $this->dbforge->add_column('appointments', $fields);
        }

        if (!$this->db->field_exists('location', 'services')) {
            $fields = [
                'location' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'description',
                ],
            ];

            $this->dbforge->add_column('services', $fields);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('location', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'location');
        }

        if ($this->db->field_exists('location', 'services')) {
            $this->dbforge->drop_column('services', 'location');
        }
    }
}
