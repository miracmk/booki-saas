<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - real check-in / check-out timestamps, distinct
 * from the planned start_datetime / end_datetime of the booking.
 * ---------------------------------------------------------------------------- */

class Migration_Add_actual_datetimes_to_appointments_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('actual_start_datetime', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'actual_start_datetime' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'end_datetime',
                ],
            ]);
        }

        if (!$this->db->field_exists('actual_end_datetime', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'actual_end_datetime' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'actual_start_datetime',
                ],
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('actual_end_datetime', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'actual_end_datetime');
        }

        if ($this->db->field_exists('actual_start_datetime', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'actual_start_datetime');
        }
    }
}
