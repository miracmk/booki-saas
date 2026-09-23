<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Google Places API (New) Lead Crawler Tables Migration
 * 
 * Extends `leads` with Places New metadata (place_id, types, business_status,
 * discovery_state, matched_queries, enrichment fields) and creates `crawl_jobs`
 * and `places_api_usage` tracking tables.
 * -------------------------------------------------------------------------- */

class Migration_Add_google_places_crawler_tables extends CI_Migration
{
    public function up(): void
    {
        $db = $this->db;
        $dbforge = $this->dbforge;

        // 1. Extend `leads` table with Google Places New fields
        if ($db->table_exists('leads')) {
            $fields_to_add = [];

            if (!$db->field_exists('place_id', 'leads')) {
                $fields_to_add['place_id'] = [
                    'type' => 'VARCHAR',
                    'constraint' => 128,
                    'null' => true,
                    'after' => 'id',
                ];
            }
            if (!$db->field_exists('primary_type', 'leads')) {
                $fields_to_add['primary_type'] = [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                    'after' => 'sector',
                ];
            }
            if (!$db->field_exists('types_json', 'leads')) {
                $fields_to_add['types_json'] = [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'primary_type',
                ];
            }
            if (!$db->field_exists('business_status', 'leads')) {
                $fields_to_add['business_status'] = [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'OPERATIONAL',
                    'null' => false,
                    'after' => 'verification',
                ];
            }
            if (!$db->field_exists('discovery_state', 'leads')) {
                $fields_to_add['discovery_state'] = [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'DISCOVERED',
                    'null' => false,
                    'after' => 'business_status',
                ];
            }
            if (!$db->field_exists('google_maps_uri', 'leads')) {
                $fields_to_add['google_maps_uri'] = [
                    'type' => 'VARCHAR',
                    'constraint' => 512,
                    'null' => true,
                    'after' => 'website',
                ];
            }
            if (!$db->field_exists('matched_categories', 'leads')) {
                $fields_to_add['matched_categories'] = [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'tags',
                ];
            }
            if (!$db->field_exists('matched_queries', 'leads')) {
                $fields_to_add['matched_queries'] = [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'matched_categories',
                ];
            }
            if (!$db->field_exists('matched_regions', 'leads')) {
                $fields_to_add['matched_regions'] = [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'matched_queries',
                ];
            }
            if (!$db->field_exists('first_seen_at', 'leads')) {
                $fields_to_add['first_seen_at'] = [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'matched_regions',
                ];
            }
            if (!$db->field_exists('last_seen_at', 'leads')) {
                $fields_to_add['last_seen_at'] = [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'first_seen_at',
                ];
            }
            if (!$db->field_exists('last_crawled_at', 'leads')) {
                $fields_to_add['last_crawled_at'] = [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'last_seen_at',
                ];
            }
            if (!$db->field_exists('discovery_count', 'leads')) {
                $fields_to_add['discovery_count'] = [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 1,
                    'null' => false,
                    'after' => 'last_crawled_at',
                ];
            }
            if (!$db->field_exists('rating', 'leads')) {
                $fields_to_add['rating'] = [
                    'type' => 'DECIMAL',
                    'constraint' => '3,1',
                    'null' => true,
                    'after' => 'discovery_count',
                ];
            }
            if (!$db->field_exists('user_rating_count', 'leads')) {
                $fields_to_add['user_rating_count'] = [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'after' => 'rating',
                ];
            }
            if (!$db->field_exists('price_level', 'leads')) {
                $fields_to_add['price_level'] = [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => true,
                    'after' => 'user_rating_count',
                ];
            }
            if (!$db->field_exists('opening_hours_json', 'leads')) {
                $fields_to_add['opening_hours_json'] = [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'price_level',
                ];
            }
            if (!$db->field_exists('photos_json', 'leads')) {
                $fields_to_add['photos_json'] = [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'opening_hours_json',
                ];
            }
            if (!$db->field_exists('reviews_json', 'leads')) {
                $fields_to_add['reviews_json'] = [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'photos_json',
                ];
            }
            if (!$db->field_exists('enriched_at', 'leads')) {
                $fields_to_add['enriched_at'] = [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'reviews_json',
                ];
            }

            if (!empty($fields_to_add)) {
                $dbforge->add_column('leads', $fields_to_add);
            }

            // Add index and unique on place_id if not exists
            $db->query('ALTER TABLE ' . $db->dbprefix('leads') . 
                ' ADD UNIQUE INDEX idx_leads_place_id (place_id),' .
                ' ADD INDEX idx_leads_business_status (business_status),' .
                ' ADD INDEX idx_leads_discovery_state (discovery_state)');
        }

        // 2. Create `crawl_jobs` table
        if (!$db->table_exists('crawl_jobs')) {
            $dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'status' => ['type' => 'ENUM', 'constraint' => ['QUEUED', 'RUNNING', 'COMPLETED', 'FAILED', 'CANCELLED'], 'default' => 'QUEUED', 'null' => false],
                'mode' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'standard', 'null' => false],
                'region_mode' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'districts', 'null' => false],
                'region_data_json' => ['type' => 'TEXT', 'null' => true],
                'category_slugs_json' => ['type' => 'TEXT', 'null' => true],
                'search_queries_json' => ['type' => 'TEXT', 'null' => true],
                'total_queries' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => false],
                'completed_queries' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => false],
                'pages_requested' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => false],
                'results_found' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => false],
                'new_leads' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => false],
                'updated_leads' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => false],
                'filtered_closed' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => false],
                'errors_json' => ['type' => 'TEXT', 'null' => true],
                'created_by' => ['type' => 'VARCHAR', 'constraint' => 128, 'default' => 'Super Admin', 'null' => false],
                'started_at' => ['type' => 'DATETIME', 'null' => true],
                'completed_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $dbforge->add_key('id', true);
            $dbforge->create_table('crawl_jobs', true, ['engine' => 'InnoDB']);
            $db->query('ALTER TABLE ' . $db->dbprefix('crawl_jobs') . 
                ' ADD INDEX idx_crawl_status (status),' .
                ' ADD INDEX idx_crawl_created (created_at)');
        }

        // 3. Create `places_api_usage` table
        if (!$db->table_exists('places_api_usage')) {
            $dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'timestamp' => ['type' => 'DATETIME', 'null' => false],
                'endpoint' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
                'operation' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'crawl_job_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'place_id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'http_status' => ['type' => 'INT', 'constraint' => 4, 'null' => false],
                'response_time_ms' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'sku_tier' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'Pro', 'null' => false],
                'metadata_json' => ['type' => 'TEXT', 'null' => true],
            ]);
            $dbforge->add_key('id', true);
            $dbforge->create_table('places_api_usage', true, ['engine' => 'InnoDB']);
            $db->query('ALTER TABLE ' . $db->dbprefix('places_api_usage') . 
                ' ADD INDEX idx_usage_time (timestamp),' .
                ' ADD INDEX idx_usage_op (operation),' .
                ' ADD INDEX idx_usage_job (crawl_job_id)');
        }
    }

    public function down(): void
    {
    }
}

