<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration 150: Expand Enterprise Domains (Reviews, Waitlist, POS Gateways, Invoices ERP, Marketing Suite & Attribution).
 */
class Migration_Expand_enterprise_domains extends EA_Migration
{
    public function up(): void
    {
        // 1. Reviews: provider rating, station/room rating and comments
        if ($this->db->table_exists('reviews')) {
            $fields = [];
            if (!$this->db->field_exists('id_users_provider', 'reviews')) {
                $fields['id_users_provider'] = ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('id_stations', 'reviews')) {
                $fields['id_stations'] = ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('provider_rating', 'reviews')) {
                $fields['provider_rating'] = ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('station_rating', 'reviews')) {
                $fields['station_rating'] = ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('station_comment', 'reviews')) {
                $fields['station_comment'] = ['type' => 'TEXT', 'null' => true, 'default' => null];
            }
            if (!empty($fields)) {
                $this->dbforge->add_column('reviews', $fields);
            }
        }

        // 2. POS: Payment Gateways (ÖdeAl, Garanti Sanal POS, Enpara Sanal POS, Stripe, Iyzico)
        if ($this->db->table_exists('payment_settings')) {
            // Modify active_gateway from enum to varchar(32) to accommodate new gateways cleanly
            $this->db->query("ALTER TABLE " . $this->db->dbprefix('payment_settings') . " MODIFY COLUMN active_gateway VARCHAR(32) NOT NULL DEFAULT 'none'");

            $fields = [];
            if (!$this->db->field_exists('odeal_api_key', 'payment_settings')) {
                $fields['odeal_api_key'] = ['type' => 'TEXT', 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('odeal_secret_key', 'payment_settings')) {
                $fields['odeal_secret_key'] = ['type' => 'TEXT', 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('odeal_terminal_id', 'payment_settings')) {
                $fields['odeal_terminal_id'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('garanti_merchant_id', 'payment_settings')) {
                $fields['garanti_merchant_id'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('garanti_terminal_id', 'payment_settings')) {
                $fields['garanti_terminal_id'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('garanti_prov_user_id', 'payment_settings')) {
                $fields['garanti_prov_user_id'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('garanti_prov_password', 'payment_settings')) {
                $fields['garanti_prov_password'] = ['type' => 'TEXT', 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('garanti_store_key', 'payment_settings')) {
                $fields['garanti_store_key'] = ['type' => 'TEXT', 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('enpara_merchant_id', 'payment_settings')) {
                $fields['enpara_merchant_id'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('enpara_terminal_id', 'payment_settings')) {
                $fields['enpara_terminal_id'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('enpara_store_key', 'payment_settings')) {
                $fields['enpara_store_key'] = ['type' => 'TEXT', 'null' => true, 'default' => null];
            }
            if (!empty($fields)) {
                $this->dbforge->add_column('payment_settings', $fields);
            }
        }

        // 3. Invoices: ERP Integration Status & Metadata
        if ($this->db->table_exists('invoices')) {
            $fields = [];
            if (!$this->db->field_exists('erp_status', 'invoices')) {
                $fields['erp_status'] = ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'not_synced'];
            }
            if (!$this->db->field_exists('erp_provider', 'invoices')) {
                $fields['erp_provider'] = ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('erp_invoice_id', 'invoices')) {
                $fields['erp_invoice_id'] = ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('erp_synced_at', 'invoices')) {
                $fields['erp_synced_at'] = ['type' => 'DATETIME', 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('erp_error', 'invoices')) {
                $fields['erp_error'] = ['type' => 'TEXT', 'null' => true, 'default' => null];
            }
            if (!empty($fields)) {
                $this->dbforge->add_column('invoices', $fields);
            }
        }

        // 4. Marketing Campaigns: status expansion (pause/resume), campaign_type, budget, target_url
        if ($this->db->table_exists('marketing_campaigns')) {
            $this->db->query("ALTER TABLE " . $this->db->dbprefix('marketing_campaigns') . " MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'draft'");

            $fields = [];
            if (!$this->db->field_exists('campaign_type', 'marketing_campaigns')) {
                $fields['campaign_type'] = ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'broadcast'];
            }
            if (!$this->db->field_exists('budget', 'marketing_campaigns')) {
                $fields['budget'] = ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('target_url', 'marketing_campaigns')) {
                $fields['target_url'] = ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null];
            }
            if (!empty($fields)) {
                $this->dbforge->add_column('marketing_campaigns', $fields);
            }
        }

        // 5. Landing Pages table
        if (!$this->db->table_exists('landing_pages')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'slug' => ['type' => 'VARCHAR', 'constraint' => 128],
                'title' => ['type' => 'VARCHAR', 'constraint' => 255],
                'headline' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'content' => ['type' => 'TEXT', 'null' => true],
                'id_services' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'cta_text' => ['type' => 'VARCHAR', 'constraint' => 128, 'default' => 'Randevu Al'],
                'views_count' => ['type' => 'INT', 'default' => 0],
                'conversions_count' => ['type' => 'INT', 'default' => 0],
                'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('slug');
            $this->dbforge->create_table('landing_pages');
        }

        // 6. Traffic Attributions table (Session attribution, click timestamps, heatmaps & customer extraction)
        if (!$this->db->table_exists('traffic_attributions')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'session_id' => ['type' => 'VARCHAR', 'constraint' => 64],
                'id_users_customer' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'appointment_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'landing_page_slug' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'utm_source' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'utm_medium' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'utm_campaign' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'utm_term' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'utm_content' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'gclid' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'fbclid' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'referrer' => ['type' => 'TEXT', 'null' => true],
                'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
                'click_timestamp' => ['type' => 'DATETIME'],
                'heatmap_summary' => ['type' => 'JSON', 'null' => true],
                'extracted_customer_data' => ['type' => 'JSON', 'null' => true],
                'converted' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'converted_at' => ['type' => 'DATETIME', 'null' => true],
                'revenue' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('session_id');
            $this->dbforge->add_key('utm_source');
            $this->dbforge->add_key('id_users_customer');
            $this->dbforge->create_table('traffic_attributions');
        }

        // 7. Seed Google & Meta & ERP marketing integration keys in settings if not set
        $settings_to_seed = [
            'google_ads_id' => '',
            'google_analytics_id' => '',
            'google_search_console_token' => '',
            'google_trends_keywords' => '',
            'google_business_profile_id' => '',
            'meta_pixel_id' => '',
            'meta_capi_token' => '',
            'meta_ad_account_id' => '',
            'meta_page_id' => '',
            'meta_status_sync_enabled' => '0',
            'gtm_container_id' => '',
            'active_erp_provider' => 'parasut',
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
        if ($this->db->table_exists('traffic_attributions')) {
            $this->dbforge->drop_table('traffic_attributions');
        }
        if ($this->db->table_exists('landing_pages')) {
            $this->dbforge->drop_table('landing_pages');
        }
    }
}
