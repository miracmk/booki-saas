<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration: 180_randevuburada_marketplace_escrow_and_ads
 *
 * Implements BooKi & RandevuBurada Ecosystem:
 * 1. `bk_marketplace_customers`: Standalone customer accounts for RandevuBurada
 * 2. `bk_escrow_settlements`: Financial Escrow & Payout engine (%5 RB + %5 POS + %1 EFT = %11 fee, %89 net payout)
 * 3. `bk_marketplace_ads`: Sponsored listings & ad ranking boost engine
 * 4. `bk_fraud_fingerprints`: Anti-fraud and trial abuse prevention gateway
 * Note: Following architectural guidelines, modern tables use clean `bk_` prefix.
 */
class Migration_Randevuburada_marketplace_escrow_and_ads extends CI_Migration
{
    public function up(): void
    {
        // 1. bk_marketplace_customers
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `bk_marketplace_customers` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `full_name` VARCHAR(150) NOT NULL,
                `phone` VARCHAR(25) NOT NULL,
                `email` VARCHAR(150) NULL,
                `password_hash` VARCHAR(255) NULL,
                `avatar_url` VARCHAR(255) NULL,
                `verification_code` VARCHAR(10) NULL,
                `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
                `status` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE KEY `uk_bk_customer_phone` (`phone`),
                INDEX `idx_bk_customer_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. bk_escrow_settlements (%5 RandevuBurada + %5 Tosla POS + %1 EFT/FAST Transfer = %11 Total, %89 Net Payout)
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `bk_escrow_settlements` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `id_tenants` INT UNSIGNED NOT NULL,
                `id_appointments` INT UNSIGNED NULL,
                `customer_id` INT UNSIGNED NULL,
                `customer_name` VARCHAR(150) NOT NULL,
                `customer_phone` VARCHAR(25) NOT NULL,
                `service_name` VARCHAR(255) NOT NULL,
                `gross_amount` DECIMAL(10,2) NOT NULL COMMENT 'Müşteriden çekilen toplam tutar',
                `marketplace_rate` DECIMAL(5,2) NOT NULL DEFAULT 5.00 COMMENT 'RandevuBurada komisyon oranı (%)',
                `marketplace_commission` DECIMAL(10,2) NOT NULL COMMENT '%5 RandevuBurada komisyonu',
                `pos_rate` DECIMAL(5,2) NOT NULL DEFAULT 5.00 COMMENT 'Tosla Sanal POS komisyon oranı (%)',
                `pos_fee` DECIMAL(10,2) NOT NULL COMMENT '%5 Sanal POS maliyeti',
                `transfer_rate` DECIMAL(5,2) NOT NULL DEFAULT 1.00 COMMENT 'Banka EFT/FAST transfer oranı (%)',
                `transfer_fee` DECIMAL(10,2) NOT NULL COMMENT '%1 EFT/FAST transfer bedeli',
                `net_payout_amount` DECIMAL(10,2) NOT NULL COMMENT 'İşletmeye ödenecek net tutar (%89)',
                `tosla_transaction_id` VARCHAR(100) NULL,
                `tosla_order_id` VARCHAR(50) NOT NULL,
                `provision_status` ENUM('authorized', 'captured', 'refunded', 'voided') NOT NULL DEFAULT 'authorized',
                `payout_status` ENUM('pending_showup', 'in_escrow_t3', 'ready_for_payout', 'transferred', 'cancelled') NOT NULL DEFAULT 'pending_showup',
                `payout_due_date` DATETIME NULL COMMENT 'T+3 Vade Bitiş Tarihi',
                `payout_transferred_at` DATETIME NULL,
                `payout_iban` VARCHAR(34) NULL,
                `payout_reference` VARCHAR(100) NULL,
                `notes` TEXT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                INDEX `idx_bk_settlement_tenant` (`id_tenants`, `payout_status`),
                INDEX `idx_bk_settlement_due` (`payout_due_date`),
                INDEX `idx_bk_settlement_order` (`tosla_order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 3. bk_marketplace_ads (Sponsored Ads & Featured Ranking)
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `bk_marketplace_ads` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `id_tenants` INT UNSIGNED NOT NULL,
                `ad_package_key` VARCHAR(50) NOT NULL,
                `ad_type` ENUM('category_top', 'city_top', 'homepage_featured') NOT NULL DEFAULT 'category_top',
                `category` VARCHAR(100) NULL,
                `city` VARCHAR(50) NULL,
                `district` VARCHAR(50) NULL,
                `sponsored_rank_boost` INT NOT NULL DEFAULT 50 COMMENT 'Sıralama boost puanı (+50)',
                `price_paid` DECIMAL(10,2) NOT NULL,
                `starts_at` DATETIME NOT NULL,
                `ends_at` DATETIME NOT NULL,
                `status` ENUM('active', 'expired', 'paused') NOT NULL DEFAULT 'active',
                `tosla_order_id` VARCHAR(50) NOT NULL,
                `impressions_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `clicks_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                INDEX `idx_bk_active_ads` (`status`, `ends_at`, `category`, `city`),
                INDEX `idx_bk_tenant_ads` (`id_tenants`, `status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 4. bk_fraud_fingerprints (Anti-Fraud and Trial Abuse Prevention)
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `bk_fraud_fingerprints` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `ip_address` VARCHAR(45) NOT NULL,
                `device_fingerprint_hash` VARCHAR(128) NOT NULL,
                `google_id_hash` VARCHAR(128) NULL,
                `phone` VARCHAR(25) NULL,
                `trial_used` TINYINT(1) NOT NULL DEFAULT 0,
                `first_tenant_id` INT UNSIGNED NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                INDEX `idx_bk_ip` (`ip_address`),
                INDEX `idx_bk_device` (`device_fingerprint_hash`),
                INDEX `idx_bk_phone` (`phone`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        // 5. bk_tenants View (Modern prefix alias for master tenants)
        if ($this->db->table_exists('ea_tenants')) {
            $this->db->query("CREATE OR REPLACE VIEW `bk_tenants` AS SELECT * FROM `ea_tenants`;");
        }
    }

    public function down(): void
    {
        $this->db->query("DROP TABLE IF EXISTS `bk_marketplace_ads`;");
        $this->db->query("DROP TABLE IF EXISTS `bk_escrow_settlements`;");
        $this->db->query("DROP TABLE IF EXISTS `bk_marketplace_customers`;");
        $this->db->query("DROP TABLE IF EXISTS `bk_fraud_fingerprints`;");
    }
}
