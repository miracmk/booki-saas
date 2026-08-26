<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - per-appointment pricing overrides.
 *
 * Reports/commissions now derive the "effective price" of a session from its
 * REAL check-in/check-out duration (rounded down to the nearest half hour)
 * times the service's hourly rate (service.price / (service.duration/60)),
 * instead of always using the flat service.price. These two columns let
 * staff opt out of that at booking time:
 *
 * - custom_duration_minutes: book a non-standard duration for a service
 *   (e.g. 100 dk) instead of picking one of the service's preset duration
 *   variants; the effective price still scales from the service's hourly
 *   rate (100/60 * hourly_rate).
 * - price_override: a fully fixed price for this one reservation, bypassing
 *   the hourly-rate calculation entirely (both for the customer charge and,
 *   when the provider's commission is percentage-based, the commission
 *   base).
 * ---------------------------------------------------------------------------- */

class Migration_Add_pricing_override_fields_to_appointments_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('custom_duration_minutes', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'custom_duration_minutes' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'payment_recorded_by',
                ],
            ]);
        }

        if (!$this->db->field_exists('price_override', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'price_override' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                    'after' => 'custom_duration_minutes',
                ],
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        foreach (['price_override', 'custom_duration_minutes'] as $column) {
            if ($this->db->field_exists($column, 'appointments')) {
                $this->dbforge->drop_column('appointments', $column);
            }
        }
    }
}
