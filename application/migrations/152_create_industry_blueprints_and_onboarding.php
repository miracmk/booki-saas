<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration 152: Create Industry Blueprints and Onboarding Settings.
 *
 * Adds:
 * 1. industry_blueprints table for managing sector-based presets, module activations, and terminologies
 * 2. industry_code and onboarding_completed settings in settings table
 */
class Migration_Create_industry_blueprints_and_onboarding extends EA_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists('industry_blueprints')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'code' => ['type' => 'VARCHAR', 'constraint' => 64],
                'name' => ['type' => 'VARCHAR', 'constraint' => 128],
                'icon' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
                'description' => ['type' => 'TEXT', 'null' => true],
                'service_type' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'duration'], // duration, station, procedure, hybrid
                'enabled_modules' => ['type' => 'JSON'],
                'terminology' => ['type' => 'JSON'],
                'default_settings' => ['type' => 'JSON'],
                'sort_order' => ['type' => 'INT', 'default' => 0],
                'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('code', false, true); // unique
            $this->dbforge->add_key('is_active');
            $this->dbforge->add_key('sort_order');
            $this->dbforge->create_table('industry_blueprints');
        }

        // Ensure industry settings exist
        $settings_to_seed = [
            'industry_code' => 'general',
            'industry_custom_terminology' => json_encode([
                'customer_label' => 'Müşteri',
                'provider_label' => 'Personel',
                'appointment_label' => 'Randevu',
                'station_label' => 'İstasyon / Oda',
                'service_label' => 'Hizmet',
            ]),
            'onboarding_completed' => '0',
            'onboarding_step' => '1',
        ];

        foreach ($settings_to_seed as $key => $val) {
            $existing = $this->db->get_where('settings', ['name' => $key])->row_array();
            if (!$existing) {
                $this->db->insert('settings', [
                    'name' => $key,
                    'value' => $val,
                ]);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('industry_blueprints')) {
            $this->dbforge->drop_table('industry_blueprints');
        }

        $this->db->where_in('name', ['industry_code', 'industry_custom_terminology', 'onboarding_completed', 'onboarding_step'])->delete('settings');
    }
}

