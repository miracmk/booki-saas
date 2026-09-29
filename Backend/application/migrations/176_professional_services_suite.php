<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration 176: Professional Services Suite (Legal, Consulting & Real Estate)
 */
class Migration_professional_services_suite extends CI_Migration
{
    public function up(): void
    {
        // 1. Legal Cases Table
        if (!$this->db->table_exists('legal_cases')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `ea_legal_cases` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `case_number` VARCHAR(100) NOT NULL,
                    `court_name` VARCHAR(255) NOT NULL,
                    `id_users_client` INT UNSIGNED NOT NULL,
                    `opposing_party` VARCHAR(255) NOT NULL,
                    `case_type` VARCHAR(100) NOT NULL DEFAULT 'civil',
                    `case_subject` TEXT NOT NULL,
                    `hearing_datetime` DATETIME NULL,
                    `status` VARCHAR(32) NOT NULL DEFAULT 'open',
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_legal_case_client` (`id_users_client`),
                    KEY `idx_legal_case_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 2. Consulting Time Logs (Billable Hours)
        if (!$this->db->table_exists('consulting_time_logs')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `ea_consulting_time_logs` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_users_client` INT UNSIGNED NOT NULL,
                    `id_users_consultant` INT UNSIGNED NOT NULL,
                    `project_name` VARCHAR(255) NOT NULL,
                    `duration_minutes` INT UNSIGNED NOT NULL,
                    `hourly_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `total_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `work_description` TEXT NOT NULL,
                    `is_billable` TINYINT(1) NOT NULL DEFAULT 1,
                    `status` VARCHAR(32) NOT NULL DEFAULT 'logged',
                    `created_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_consulting_client` (`id_users_client`),
                    KEY `idx_consulting_consultant` (`id_users_consultant`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 3. Real Estate Listings Table
        if (!$this->db->table_exists('real_estate_listings')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `ea_real_estate_listings` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `listing_code` VARCHAR(50) NOT NULL,
                    `title` VARCHAR(255) NOT NULL,
                    `listing_type` VARCHAR(32) NOT NULL DEFAULT 'sale',
                    `property_type` VARCHAR(64) NOT NULL DEFAULT 'apartment',
                    `price` DECIMAL(14,2) NOT NULL,
                    `city` VARCHAR(100) NOT NULL,
                    `district` VARCHAR(100) NOT NULL,
                    `square_meters` INT UNSIGNED NULL,
                    `id_users_agent` INT UNSIGNED NULL,
                    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_property_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } else {
            $this->load->dbforge();
            if (!$this->db->field_exists('listing_code', 'real_estate_listings')) {
                $this->dbforge->add_column('real_estate_listings', [
                    'listing_code' => [
                        'type' => 'VARCHAR',
                        'constraint' => 50,
                        'null' => true,
                        'after' => 'id',
                    ],
                ]);
            }
            if (!$this->db->field_exists('property_type', 'real_estate_listings')) {
                $this->dbforge->add_column('real_estate_listings', [
                    'property_type' => [
                        'type' => 'VARCHAR',
                        'constraint' => 64,
                        'null' => true,
                        'default' => 'apartment',
                        'after' => 'listing_type',
                    ],
                ]);
            }
            if (!$this->db->field_exists('square_meters', 'real_estate_listings')) {
                $this->dbforge->add_column('real_estate_listings', [
                    'square_meters' => [
                        'type' => 'INT',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => true,
                        'after' => 'district',
                    ],
                ]);
            }
            if (!$this->db->field_exists('id_users_agent', 'real_estate_listings')) {
                $this->dbforge->add_column('real_estate_listings', [
                    'id_users_agent' => [
                        'type' => 'INT',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => true,
                        'after' => 'status',
                    ],
                ]);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('real_estate_listings')) {
            $this->db->query("DROP TABLE IF EXISTS `ea_real_estate_listings`;");
        }
        if ($this->db->table_exists('consulting_time_logs')) {
            $this->db->query("DROP TABLE IF EXISTS `ea_consulting_time_logs`;");
        }
        if ($this->db->table_exists('legal_cases')) {
            $this->db->query("DROP TABLE IF EXISTS `ea_legal_cases`;");
        }
    }
}
