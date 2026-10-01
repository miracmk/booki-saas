<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration: 181_escrow_invoice_gatekeeper_and_grace_period
 *
 * Implements Unified BK-RD-BKA Specification Enhancements:
 * 1. Deduction Models (Model A: 5%+5%+1%=89%, Model B: 20%+1%=79%, Model C: 0%+5%+1%=94%)
 * 2. Escrow e-Fatura / e-SMM Invoice Gatekeeper (`invoice_file_url`, `invoice_no`, `pending_invoice`, `invoice_verified`)
 * 3. Unified Current Accounts table `bk_current_accounts`
 * 4. Tenant Subscription Lifecycle: `saas_status` (active, grace_period, locked), `membership_type` (booki_saas, rb_only, unclaimed), `grace_period_ends_at`
 */
class Migration_Escrow_invoice_gatekeeper_and_grace_period extends CI_Migration
{
    public function up(): void
    {
        // 1. Enhance bk_escrow_settlements with deduction models and invoice gatekeeper fields
        $bkEscrowExists = $this->db->query("SHOW TABLES LIKE 'bk_escrow_settlements'")->num_rows() > 0;
        if ($bkEscrowExists) {
            $cols = $this->db->query("SHOW COLUMNS FROM `bk_escrow_settlements`")->result_array();
            $colNames = array_column($cols, 'Field');

            if (!in_array('deduction_model', $colNames, true)) {
                $this->db->query("ALTER TABLE `bk_escrow_settlements` ADD COLUMN `deduction_model` ENUM('model_a', 'model_b', 'model_c') NOT NULL DEFAULT 'model_a' COMMENT 'model_a=RB Organic (%11), model_b=RB-Only Manual (%21), model_c=BooKi SaaS (%6)';");
            }
            if (!in_array('invoice_file_url', $colNames, true)) {
                $this->db->query("ALTER TABLE `bk_escrow_settlements` ADD COLUMN `invoice_file_url` VARCHAR(255) NULL COMMENT 'Yuklenen e-Fatura / e-SMM PDF URL';");
            }
            if (!in_array('invoice_no', $colNames, true)) {
                $this->db->query("ALTER TABLE `bk_escrow_settlements` ADD COLUMN `invoice_no` VARCHAR(64) NULL COMMENT 'Fatura / Makbuz No';");
            }
            if (!in_array('invoice_tax_id', $colNames, true)) {
                $this->db->query("ALTER TABLE `bk_escrow_settlements` ADD COLUMN `invoice_tax_id` VARCHAR(32) NULL COMMENT 'VKN veya TCKN';");
            }
            if (!in_array('invoice_uploaded_at', $colNames, true)) {
                $this->db->query("ALTER TABLE `bk_escrow_settlements` ADD COLUMN `invoice_uploaded_at` DATETIME NULL;");
            }
            if (!in_array('invoice_verified_at', $colNames, true)) {
                $this->db->query("ALTER TABLE `bk_escrow_settlements` ADD COLUMN `invoice_verified_at` DATETIME NULL;");
            }

            $this->db->query("
                ALTER TABLE `bk_escrow_settlements` 
                MODIFY COLUMN `payout_status` 
                ENUM('pending_showup', 'in_escrow_t3', 'pending_invoice', 'invoice_verified', 'ready_for_payout', 'transferred', 'cancelled') 
                NOT NULL DEFAULT 'pending_showup';
            ");
        }

        // 2. Create bk_current_accounts for unified merchant balance and escrow payouts
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `bk_current_accounts` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `id_tenants` INT UNSIGNED NOT NULL,
                `account_type` ENUM('escrow_payout', 'saas_subscription', 'ad_payment', 'adjustment') NOT NULL DEFAULT 'escrow_payout',
                `reference_id` VARCHAR(100) NULL COMMENT 'Settlement ID or Order ID',
                `direction` ENUM('credit', 'debit') NOT NULL COMMENT 'credit=inbound to merchant, debit=fee or transfer out',
                `amount` DECIMAL(10,2) NOT NULL,
                `balance_after` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `status` ENUM('pending_invoice', 'invoice_verified', 'ready_for_transfer', 'transferred', 'rejected') NOT NULL DEFAULT 'pending_invoice',
                `description` VARCHAR(255) NULL,
                `invoice_file_url` VARCHAR(255) NULL,
                `invoice_no` VARCHAR(64) NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                INDEX `idx_bk_ca_tenant` (`id_tenants`, `status`),
                INDEX `idx_bk_ca_ref` (`reference_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 3. Add SaaS lifecycle & Grace Period fields to master ea_tenants table
        if ($this->db->table_exists('ea_tenants')) {
            if (!$this->db->field_exists('saas_status', 'ea_tenants')) {
                $this->db->query("ALTER TABLE `ea_tenants` ADD COLUMN `saas_status` ENUM('active', 'grace_period', 'locked') NOT NULL DEFAULT 'active' COMMENT 'active=full access, grace_period=5-day grace, locked=downgraded to RB-Only';");
            }

            if (!$this->db->field_exists('membership_type', 'ea_tenants')) {
                $this->db->query("ALTER TABLE `ea_tenants` ADD COLUMN `membership_type` ENUM('booki_saas', 'rb_only', 'unclaimed') NOT NULL DEFAULT 'booki_saas' COMMENT 'Persona: booki_saas, rb_only, unclaimed';");
            }

            if (!$this->db->field_exists('grace_period_ends_at', 'ea_tenants')) {
                $this->db->query("ALTER TABLE `ea_tenants` ADD COLUMN `grace_period_ends_at` DATETIME NULL COMMENT '5 gunluk grace period son kullanma tarihi';");
            }

            if (!$this->db->field_exists('failed_billing_attempts', 'ea_tenants')) {
                $this->db->query("ALTER TABLE `ea_tenants` ADD COLUMN `failed_billing_attempts` INT UNSIGNED NOT NULL DEFAULT 0;");
            }

            // Refresh the bk_tenants view to include newly added columns
            $this->db->query("CREATE OR REPLACE VIEW `bk_tenants` AS SELECT * FROM `ea_tenants`;");
        }
    }

    public function down(): void
    {
        $this->db->query("DROP TABLE IF EXISTS `bk_current_accounts`;");
    }
}
