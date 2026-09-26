<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - adds a "Tamamlandı" (Completed) status option so
 * the daily revenue report has a status to rely on in addition to the actual
 * check-out timestamp.
 * ---------------------------------------------------------------------------- */

class Migration_Seed_completed_appointment_status extends App_Migration
{
    private const STATUS_LABEL = 'Tamamlandı';

    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $setting = $this->db->get_where('settings', ['name' => 'appointment_status_options'])->row_array();

        if (!$setting) {
            return;
        }

        $options = json_decode((string) $setting['value'], true) ?: [];

        if (!in_array(self::STATUS_LABEL, $options, true)) {
            $options[] = self::STATUS_LABEL;

            $this->db->update(
                'settings',
                ['value' => json_encode($options, JSON_UNESCAPED_UNICODE)],
                ['name' => 'appointment_status_options'],
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $setting = $this->db->get_where('settings', ['name' => 'appointment_status_options'])->row_array();

        if (!$setting) {
            return;
        }

        $options = json_decode((string) $setting['value'], true) ?: [];

        $options = array_values(array_filter($options, static fn($option) => $option !== self::STATUS_LABEL));

        $this->db->update(
            'settings',
            ['value' => json_encode($options, JSON_UNESCAPED_UNICODE)],
            ['name' => 'appointment_status_options'],
        );
    }
}
