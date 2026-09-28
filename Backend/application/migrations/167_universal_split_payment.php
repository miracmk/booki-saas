<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Universal_split_payment extends CI_Migration
{
    public function up(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `system_split_payments` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `entity_type` ENUM('adisyon','appointment','order') NOT NULL,
              `entity_id` INT UNSIGNED NOT NULL,
              `payment_type` ENUM('cash','card','transfer','discount','complimentary','gift_card','membership','coupon') NOT NULL DEFAULT 'cash',
              `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
              `discount_percent` DECIMAL(5,2) DEFAULT NULL,
              `coupon_code` VARCHAR(50) DEFAULT NULL,
              `notes` VARCHAR(255) DEFAULT NULL,
              `received_by` INT UNSIGNED DEFAULT NULL,
              `created_at` DATETIME NOT NULL,
              `id_payment_transactions` INT UNSIGNED NULL,
              PRIMARY KEY (`id`),
              KEY `idx_entity` (`entity_type`, `entity_id`),
              KEY `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down(): void
    {
        $this->db->query("DROP TABLE IF EXISTS `system_split_payments`");
    }
}
