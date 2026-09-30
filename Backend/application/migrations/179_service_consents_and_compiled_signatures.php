<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Service_consents_and_compiled_signatures extends CI_Migration
{
    public function up(): void
    {
        // 1. Add compiled_content_html & signature_type to waiver_signatures if not present
        if ($this->db->table_exists('waiver_signatures')) {
            $fields = [];
            if (!$this->db->field_exists('compiled_content_html', 'waiver_signatures')) {
                $fields['compiled_content_html'] = [
                    'type' => 'LONGTEXT',
                    'null' => true,
                    'default' => null,
                ];
            }
            if (!$this->db->field_exists('signature_type', 'waiver_signatures')) {
                $fields['signature_type'] = [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'canvas_biometric',
                ];
            }
            if (!empty($fields)) {
                $this->dbforge->add_column('waiver_signatures', $fields);
            }
        }

        // 2. Clean out old Macera Parkı dummy from digital_waivers
        if ($this->db->table_exists('digital_waivers')) {
            $this->db->like('title', 'Macera Parkı')->delete('digital_waivers');

            // 3. Load legal_catalog and seed sector templates
            $CI =& get_instance();
            $CI->load->library('legal_catalog');
            $catalog = $CI->legal_catalog->get_catalog();
            $now = date('Y-m-d H:i:s');

            foreach ($catalog as $tpl) {
                $existing = $this->db->get_where('digital_waivers', ['title' => $tpl['title']])->row_array();
                if (!$existing) {
                    $this->db->insert('digital_waivers', [
                        'title' => $tpl['title'],
                        'content_html' => $tpl['content_html'],
                        'is_mandatory' => $tpl['is_mandatory'],
                        'applicable_service_ids' => '',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            // 4. Automatically link relevant templates to existing services
            $services = $this->db->get('services')->result_array();
            $all_waivers = $this->db->get('digital_waivers')->result_array();

            foreach ($services as $service) {
                $s_id = (string) $service['id'];
                $s_name = mb_strtolower($service['name'], 'UTF-8');

                foreach ($all_waivers as $w) {
                    $w_title = mb_strtolower($w['title'], 'UTF-8');
                    $ids = array_filter(array_map('trim', explode(',', (string) ($w['applicable_service_ids'] ?? ''))));

                    $should_link = false;
                    if ((str_contains($s_name, 'cilt') || str_contains($s_name, 'bakım') || str_contains($s_name, 'peeling')) && str_contains($w_title, 'cilt bakımı')) {
                        $should_link = true;
                    } elseif ((str_contains($s_name, 'lazer') || str_contains($s_name, 'epilasyon')) && str_contains($w_title, 'lazer epilasyon')) {
                        $should_link = true;
                    } elseif ((str_contains($s_name, 'botoks') || str_contains($s_name, 'dolgu')) && str_contains($w_title, 'botulinum')) {
                        $should_link = true;
                    } elseif ((str_contains($s_name, 'makyaj') || str_contains($s_name, 'microblading') || str_contains($s_name, 'kaş')) && str_contains($w_title, 'kalıcı makyaj')) {
                        $should_link = true;
                    } elseif (str_contains($w_title, 'kvkk')) {
                        $should_link = true;
                    }

                    if ($should_link && !in_array($s_id, $ids, true)) {
                        $ids[] = $s_id;
                        $this->db->update('digital_waivers', [
                            'applicable_service_ids' => implode(',', $ids),
                            'updated_at' => $now,
                        ], ['id' => $w['id']]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // Non-destructive rollback
    }
}
