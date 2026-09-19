<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration 153: Migrate existing tenants to industry blueprints.
 *
 * Automatically inspects the current database context (and tenant domain/subdomain/name),
 * determines the matching industry blueprint, ensures all 12 industry blueprints are populated
 * in industry_blueprints table, and applies the industry blueprint settings, terminology,
 * service categories, services, and stations.
 */
class Migration_Migrate_tenants_to_industry_blueprints extends EA_Migration
{
    public function up(): void
    {
        $CI = &get_instance();
        $CI->load->library('blueprint_service');

        // 1. Ensure all 12 blueprints are populated into industry_blueprints table
        if ($this->db->table_exists('industry_blueprints')) {
            $all_blueprints = $CI->blueprint_service->get_all_blueprints();
            foreach ($all_blueprints as $bp_meta) {
                $bp = $CI->blueprint_service->get_blueprint($bp_meta['code']);
                if ($bp) {
                    $existing = $this->db->get_where('industry_blueprints', ['code' => $bp_meta['code']])->row_array();
                    $bp_data = [
                        'code' => $bp_meta['code'],
                        'name' => $bp['industry']['name'],
                        'icon' => $bp['industry']['icon'] ?? '🏢',
                        'description' => $bp['industry']['description'] ?? '',
                        'service_type' => $bp['industry']['service_type'] ?? 'duration',
                        'enabled_modules' => json_encode($bp['enabled_modules'] ?? []),
                        'terminology' => json_encode($bp['terminology'] ?? []),
                        'default_settings' => json_encode($bp['default_settings'] ?? []),
                        'sort_order' => 0,
                        'is_active' => 1,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    if ($existing) {
                        $this->db->update('industry_blueprints', $bp_data, ['id' => $existing['id']]);
                    } else {
                        $bp_data['created_at'] = date('Y-m-d H:i:s');
                        $this->db->insert('industry_blueprints', $bp_data);
                    }
                }
            }
        }

        $ctx = function_exists('tenant_context') ? tenant_context() : null;
        $subdomain = is_array($ctx) ? ($ctx['subdomain'] ?? '') : '';
        $company_name = '';
        $setting_company = $this->db->get_where('settings', ['name' => 'company_name'])->row_array();
        if ($setting_company) {
            $company_name = strtolower($setting_company['value'] ?? '');
        }

        $db_name = strtolower($this->db->database);
        $current_industry = $this->db->get_where('settings', ['name' => 'industry_code'])->row_array();
        $industry_code = $current_industry['value'] ?? '';

        if (empty($industry_code) || $industry_code === 'general') {
            $text_to_check = strtolower($subdomain . ' ' . $db_name . ' ' . $company_name);

            if (preg_match('/(barber|kuafor|kuaför|berber)/ui', $text_to_check)) {
                $industry_code = 'barber';
            } elseif (preg_match('/(masaj|massage|spa)/ui', $text_to_check)) {
                $industry_code = 'massage_spa';
            } elseif (preg_match('/(restoran|restaurant|cafe|kafe|bistro|yemek)/ui', $text_to_check)) {
                $industry_code = 'restaurant';
            } elseif (preg_match('/(otel|hotel|resort|pansiyon)/ui', $text_to_check)) {
                $industry_code = 'hotel';
            } elseif (preg_match('/(dis|dent|dental|diş)/ui', $text_to_check)) {
                $industry_code = 'dentist';
            } elseif (preg_match('/(klinik|clinic|doctor|doktor|tip|tıp)/ui', $text_to_check)) {
                $industry_code = 'doctor_clinic';
            } elseif (preg_match('/(pilates|studyo|stüdyo|studio|yoga)/ui', $text_to_check)) {
                $industry_code = 'pilates_studio';
            } elseif (preg_match('/(pt|trainer|personal|antrenor)/ui', $text_to_check)) {
                $industry_code = 'pt_training';
            } elseif (preg_match('/(gym|fitness|spor)/ui', $text_to_check)) {
                $industry_code = 'gym';
            } elseif (preg_match('/(oto|car|wash|yikama|yıkama)/ui', $text_to_check)) {
                $industry_code = 'car_wash';
            } elseif (preg_match('/(tirnak|tırnak|nail)/ui', $text_to_check)) {
                $industry_code = 'nail_studio';
            } else {
                // salonflora, demo-guzellik, qatest etc.
                $industry_code = 'beauty_salon';
            }
        }

        // Apply blueprint (without destroying existing live customer/booking records)
        $seed_demo = false;
        if (strpos($subdomain, 'demo-') === 0 || strpos($db_name, 'demo') !== false) {
            $appt_count = $this->db->count_all('appointments');
            if ($appt_count === 0) {
                $seed_demo = true;
            }
        }

        $CI->blueprint_service->apply_blueprint($industry_code, $seed_demo);
    }

    public function down(): void
    {
        // No-op to preserve operational data
    }
}
