<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Marketplace Sync & Publish Fields Migration (RandevuBurada RB Push)
 *
 * Adds `is_marketplace_published` toggle and `marketplace_synced_at` timestamp
 * to the `leads` table. Ensures all previously enriched leads are synchronized
 * with valid slugs, photo references, and published state.
 * -------------------------------------------------------------------------- */

class Migration_Add_marketplace_sync_and_publish_to_leads extends CI_Migration
{
    public function up(): void
    {
        $db = $this->db;
        $dbforge = $this->dbforge;

        if (!$db->table_exists('leads')) {
            return;
        }

        $leads_tbl = '`' . $db->dbprefix('leads') . '`';
        $fields_to_add = [];

        if (!$db->field_exists('is_marketplace_published', 'leads')) {
            $fields_to_add['is_marketplace_published'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ];
        }

        if (!$db->field_exists('marketplace_synced_at', 'leads')) {
            $fields_to_add['marketplace_synced_at'] = [
                'type' => 'DATETIME',
                'null' => true,
            ];
        }

        if (!empty($fields_to_add)) {
            $dbforge->add_column('leads', $fields_to_add);
        }

        // Indexes
        $indexes = [
            'idx_leads_is_marketplace_published' => "ALTER TABLE {$leads_tbl} ADD INDEX `idx_leads_is_marketplace_published` (`is_marketplace_published`)",
            'idx_leads_rb_storefront' => "ALTER TABLE {$leads_tbl} ADD INDEX `idx_leads_rb_storefront` (`is_marketplace_published`, `business_status`, `rating`)",
        ];

        foreach ($indexes as $index_name => $sql) {
            try {
                $check = $db->query("SHOW INDEX FROM {$leads_tbl} WHERE Key_name = ?", [$index_name])->num_rows();
                if ($check === 0) {
                    $db->query($sql);
                }
            } catch (Throwable $e) {
                log_message('error', "Migration 163: Index {$index_name} warning: " . $e->getMessage());
            }
        }

        // Data Backfill & Sync:
        // 1. Sync place_id into google_place_id where missing
        try {
            if ($db->field_exists('place_id', 'leads') && $db->field_exists('google_place_id', 'leads')) {
                $db->query("UPDATE {$leads_tbl} SET `google_place_id` = `place_id` WHERE (`google_place_id` IS NULL OR `google_place_id` = '') AND `place_id` IS NOT NULL AND `place_id` != ''");
            }
        } catch (Throwable $e) {}

        // 2. Normalize enrichment_status for all enriched leads
        try {
            $db->query("UPDATE {$leads_tbl} SET `enrichment_status` = 'enriched_lead' WHERE `enriched_at` IS NOT NULL OR `discovery_state` = 'ENRICHED'");
        } catch (Throwable $e) {}

        // 3. Mark previously enriched operational leads as published on marketplace (RB) with synced timestamp
        try {
            $db->query("UPDATE {$leads_tbl} SET `is_marketplace_published` = 1, `marketplace_synced_at` = NOW() WHERE `business_status` = 'OPERATIONAL' AND (`enrichment_status` = 'enriched_lead' OR `enriched_at` IS NOT NULL)");
        } catch (Throwable $e) {}

        // 4. Backfill photo_references from photos_json where photo_references is empty
        try {
            $photo_rows = $db->select('id, photos_json')
                ->where('photos_json IS NOT NULL', null, false)
                ->where('photos_json !=', '')
                ->group_start()
                    ->where('photo_references IS NULL', null, false)
                    ->or_where('photo_references', '')
                ->group_end()
                ->get('leads')
                ->result_array();

            foreach ($photo_rows as $pr) {
                $photos = json_decode($pr['photos_json'], true);
                if (is_array($photos)) {
                    $refs = [];
                    foreach ($photos as $p) {
                        if (!empty($p['name'])) {
                            $refs[] = $p['name'];
                        } elseif (is_string($p)) {
                            $refs[] = $p;
                        }
                    }
                    if (!empty($refs)) {
                        $db->where('id', $pr['id'])->update('leads', [
                            'photo_references' => json_encode(array_slice($refs, 0, 5), JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'Migration 163: photo_references backfill warning: ' . $e->getMessage());
        }

        // 5. Ensure unique slugs for all leads
        try {
            $slug_rows = $db->select('id, name, district, city')
                ->group_start()
                    ->where('slug IS NULL', null, false)
                    ->or_where('slug', '')
                ->group_end()
                ->get('leads')
                ->result_array();

            foreach ($slug_rows as $row) {
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
            log_message('error', 'Migration 163: slug backfill warning: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        $db = $this->db;
        $dbforge = $this->dbforge;

        if (!$db->table_exists('leads')) {
            return;
        }

        if ($db->field_exists('is_marketplace_published', 'leads')) {
            $dbforge->drop_column('leads', 'is_marketplace_published');
        }
        if ($db->field_exists('marketplace_synced_at', 'leads')) {
            $dbforge->drop_column('leads', 'marketplace_synced_at');
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
