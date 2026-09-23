<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Marketplace pSEO & Lazy Enrichment Fields Migration
 *
 * Extends `leads` table with programmatic SEO, state management, lazy
 * enrichment, and profile claiming (unclaimed/claimed) fields.
 * -------------------------------------------------------------------------- */

class Migration_Add_marketplace_pseo_fields_to_leads extends CI_Migration
{
    public function up(): void
    {
        $db = $this->db;
        $dbforge = $this->dbforge;

        if (!$db->table_exists('leads')) {
            return;
        }

        $fields_to_add = [];

        if (!$db->field_exists('enrichment_status', 'leads')) {
            $fields_to_add['enrichment_status'] = [
                'type' => "ENUM('raw_lead', 'enriched_lead')",
                'default' => 'raw_lead',
                'null' => false,
            ];
        }

        if (!$db->field_exists('membership_status', 'leads')) {
            $fields_to_add['membership_status'] = [
                'type' => "ENUM('unclaimed', 'claimed_member')",
                'default' => 'unclaimed',
                'null' => false,
            ];
        }

        if (!$db->field_exists('google_place_id', 'leads')) {
            $fields_to_add['google_place_id'] = [
                'type' => 'VARCHAR',
                'constraint' => 256,
                'null' => true,
            ];
        }

        if (!$db->field_exists('slug', 'leads')) {
            $fields_to_add['slug'] = [
                'type' => 'VARCHAR',
                'constraint' => 256,
                'null' => true,
            ];
        }

        if (!$db->field_exists('city', 'leads')) {
            $fields_to_add['city'] = [
                'type' => 'VARCHAR',
                'constraint' => 128,
                'null' => true,
            ];
        }

        if (!$db->field_exists('neighborhood', 'leads')) {
            $fields_to_add['neighborhood'] = [
                'type' => 'VARCHAR',
                'constraint' => 128,
                'null' => true,
            ];
        }

        if (!$db->field_exists('photo_references', 'leads')) {
            $fields_to_add['photo_references'] = [
                'type' => 'TEXT',
                'null' => true,
            ];
        }

        if (!$db->field_exists('opening_hours', 'leads')) {
            $fields_to_add['opening_hours'] = [
                'type' => 'TEXT',
                'null' => true,
            ];
        }

        if (!$db->field_exists('instagram_url', 'leads')) {
            $fields_to_add['instagram_url'] = [
                'type' => 'VARCHAR',
                'constraint' => 512,
                'null' => true,
            ];
        }

        if (!$db->field_exists('whatsapp_number', 'leads')) {
            $fields_to_add['whatsapp_number'] = [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => true,
            ];
        }

        if (!$db->field_exists('website_url', 'leads')) {
            $fields_to_add['website_url'] = [
                'type' => 'VARCHAR',
                'constraint' => 512,
                'null' => true,
            ];
        }

        if (!$db->field_exists('claim_token', 'leads')) {
            $fields_to_add['claim_token'] = [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ];
        }

        if (!$db->field_exists('claimed_tenant_id', 'leads')) {
            $fields_to_add['claimed_tenant_id'] = [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ];
        }

        if (!empty($fields_to_add)) {
            $dbforge->add_column('leads', $fields_to_add);
        }

        // Add indexes safely
        $indexes = [
            'idx_leads_enrichment_status' => 'ALTER TABLE `leads` ADD INDEX `idx_leads_enrichment_status` (`enrichment_status`)',
            'idx_leads_membership_status' => 'ALTER TABLE `leads` ADD INDEX `idx_leads_membership_status` (`membership_status`)',
            'idx_leads_pseo_status' => 'ALTER TABLE `leads` ADD INDEX `idx_leads_pseo_status` (`enrichment_status`, `membership_status`)',
            'idx_leads_slug' => 'ALTER TABLE `leads` ADD UNIQUE INDEX `idx_leads_slug` (`slug`)',
            'idx_leads_claim_token' => 'ALTER TABLE `leads` ADD UNIQUE INDEX `idx_leads_claim_token` (`claim_token`)',
            'idx_leads_google_place_id' => 'ALTER TABLE `leads` ADD INDEX `idx_leads_google_place_id` (`google_place_id`)',
            'idx_leads_city' => 'ALTER TABLE `leads` ADD INDEX `idx_leads_city` (`city`)',
            'idx_leads_neighborhood' => 'ALTER TABLE `leads` ADD INDEX `idx_leads_neighborhood` (`neighborhood`)',
        ];

        foreach ($indexes as $index_name => $sql) {
            try {
                $check = $db->query("SHOW INDEX FROM `leads` WHERE Key_name = ?", [$index_name])->num_rows();
                if ($check === 0) {
                    $db->query($sql);
                }
            } catch (Throwable $e) {
                log_message('error', "Migration 160: Index {$index_name} could not be created: " . $e->getMessage());
            }
        }

        // Data Backfill:
        // 1. Sync place_id into google_place_id if place_id exists
        try {
            if ($db->field_exists('place_id', 'leads')) {
                $db->query("UPDATE `leads` SET `google_place_id` = `place_id` WHERE `google_place_id` IS NULL AND `place_id` IS NOT NULL");
            }
        } catch (Throwable $e) {}

        // 2. Mark previously enriched leads
        try {
            if ($db->field_exists('enriched_at', 'leads')) {
                $db->query("UPDATE `leads` SET `enrichment_status` = 'enriched_lead' WHERE `enriched_at` IS NOT NULL");
            }
        } catch (Throwable $e) {}

        // 3. Generate claim_token for existing leads
        try {
            $db->query("UPDATE `leads` SET `claim_token` = MD5(CONCAT(id, RAND(), NOW())) WHERE `claim_token` IS NULL OR `claim_token` = ''");
        } catch (Throwable $e) {}

        // 4. Backfill slug for leads
        try {
            $rows = $db->select('id, name, district, city')->where('slug IS NULL OR slug = ""', null, false)->limit(1000)->get('leads')->result_array();
            foreach ($rows as $row) {
                $slug = $this->generate_slug($row['name'] ?? '', $row['district'] ?? '', $row['city'] ?? '');
                $base = $slug;
                $counter = 1;
                while ($db->where('slug', $slug)->where('id !=', $row['id'])->count_all_results('leads') > 0) {
                    $counter++;
                    $slug = $base . '-' . $counter;
                }
                $db->where('id', $row['id'])->update('leads', ['slug' => $slug]);
            }
        } catch (Throwable $e) {
            log_message('error', 'Migration 160: Slug backfill warning: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        $db = $this->db;
        $dbforge = $this->dbforge;

        if (!$db->table_exists('leads')) {
            return;
        }

        $fields = [
            'enrichment_status',
            'membership_status',
            'google_place_id',
            'slug',
            'city',
            'neighborhood',
            'photo_references',
            'opening_hours',
            'instagram_url',
            'whatsapp_number',
            'website_url',
            'claim_token',
            'claimed_tenant_id',
        ];

        foreach ($fields as $field) {
            if ($db->field_exists($field, 'leads')) {
                $dbforge->drop_column('leads', $field);
            }
        }
    }

    private function generate_slug(string $name, string $district = '', string $city = ''): string
    {
        $parts = array_filter([$name, $district, $city], fn($p) => trim($p) !== '');
        $raw = implode(' ', $parts);

        $tr_map = [
            'ş' => 's', 'Ş' => 's', 'ç' => 'c', 'Ç' => 'c',
            'ğ' => 'g', 'Ğ' => 'g', 'ü' => 'u', 'Ü' => 'u',
            'ö' => 'o', 'Ö' => 'o', 'ı' => 'i', 'İ' => 'i',
            'â' => 'a', 'Â' => 'a', 'î' => 'i', 'Î' => 'i',
            'û' => 'u', 'Û' => 'u',
        ];
        $slug = strtr($raw, $tr_map);
        $slug = mb_strtolower($slug, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = 'isletme-' . bin2hex(random_bytes(4));
        }

        return $slug;
    }
}
