<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Nutrixpos_tastyigniter_advanced_suite extends CI_Migration
{
    public function up(): void
    {
        // 1. Menu Option Groups (Modifiers: Size, Sauce, Extras, Cooking Temp)
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `restaurant_menu_option_groups` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(191) NOT NULL,
                `display_type` ENUM('select','radio','checkbox') NOT NULL DEFAULT 'radio',
                `is_required` TINYINT(1) NOT NULL DEFAULT 0,
                `min_selected` INT NOT NULL DEFAULT 0,
                `max_selected` INT NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 2. Menu Option Values (+Price, Default)
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `restaurant_menu_option_values` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `group_id` INT UNSIGNED NOT NULL,
                `name` VARCHAR(191) NOT NULL,
                `price_modifier` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `is_default` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_group_id` (`group_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 3. Menu Item Options Link
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `restaurant_menu_item_options` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `menu_item_id` INT UNSIGNED NOT NULL,
                `option_group_id` INT UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_item_group` (`menu_item_id`, `option_group_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 4. Mealtimes (Breakfast, Lunch, Dinner, Happy Hour)
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `restaurant_mealtimes` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `start_time` TIME NOT NULL,
                `end_time` TIME NOT NULL,
                `validity` ENUM('daily','period','recurring') NOT NULL DEFAULT 'daily',
                `recurring_days` VARCHAR(50) NULL,
                `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 5. Menu Mealtimes Link
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `restaurant_menu_mealtimes` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `menu_item_id` INT UNSIGNED NOT NULL,
                `mealtime_id` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_item_mealtime` (`menu_item_id`, `mealtime_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 6. Coupons & Discount Codes
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `restaurant_coupons` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(50) NOT NULL UNIQUE,
                `name` VARCHAR(191) NOT NULL,
                `discount_type` ENUM('fixed','percent') NOT NULL DEFAULT 'fixed',
                `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `min_order_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `max_redemptions` INT NOT NULL DEFAULT 0,
                `redemptions_count` INT NOT NULL DEFAULT 0,
                `valid_from` DATETIME NULL,
                `valid_until` DATETIME NULL,
                `auto_apply` TINYINT(1) NOT NULL DEFAULT 0,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 7. Coupon Usage Log
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `restaurant_coupon_usage` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `coupon_id` INT UNSIGNED NOT NULL,
                `id_users_customer` INT UNSIGNED NULL,
                `id_adisyons` INT UNSIGNED NULL,
                `discount_amount` DECIMAL(10,2) NOT NULL,
                `used_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_coupon_id` (`coupon_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 8. Ingredients & Allergens
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `restaurant_ingredients` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `icon` VARCHAR(50) NOT NULL DEFAULT 'leaf',
                `is_allergen` TINYINT(1) NOT NULL DEFAULT 0,
                `allergen_code` VARCHAR(50) NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 9. Menu Ingredients Link
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `restaurant_menu_ingredients` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `menu_item_id` INT UNSIGNED NOT NULL,
                `ingredient_id` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_item_ingredient` (`menu_item_id`, `ingredient_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 10. Menu Specials (Deals / Time-based Promotional Price)
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `restaurant_menu_specials` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `menu_item_id` INT UNSIGNED NOT NULL,
                `special_price` DECIMAL(10,2) NOT NULL,
                `discount_percent` DECIMAL(5,2) NULL,
                `validity` ENUM('forever','period','recurring') NOT NULL DEFAULT 'forever',
                `valid_from` DATETIME NULL,
                `valid_until` DATETIME NULL,
                `recurring_days` VARCHAR(50) NULL,
                `recurring_from` TIME NULL,
                `recurring_to` TIME NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_special_menu` (`menu_item_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 11. Kitchen & Waiter Live Intercom / Chat
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `restaurant_kitchen_chats` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `sender_id` INT UNSIGNED NULL,
                `sender_name` VARCHAR(100) NOT NULL,
                `sender_role` VARCHAR(50) NOT NULL,
                `target_role` VARCHAR(50) NOT NULL DEFAULT 'all',
                `message` TEXT NOT NULL,
                `urgency` ENUM('normal','urgent','ready','stock_out') NOT NULL DEFAULT 'normal',
                `table_id` INT UNSIGNED NULL,
                `is_read` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_chat_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 12. Raw Materials / Inventory
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `restaurant_materials` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(191) NOT NULL,
                `unit` VARCHAR(20) NOT NULL DEFAULT 'kg',
                `current_stock` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
                `stock_alert_threshold` DECIMAL(12,3) NOT NULL DEFAULT 5.000,
                `fixed_unit_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 13. Material Stock In / Entries
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `restaurant_material_entries` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `material_id` INT UNSIGNED NOT NULL,
                `supplier_name` VARCHAR(191) NULL,
                `sku` VARCHAR(100) NULL,
                `purchase_quantity` DECIMAL(12,3) NOT NULL,
                `remaining_quantity` DECIMAL(12,3) NOT NULL,
                `unit_purchase_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `expiration_date` DATE NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_entry_material` (`material_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 14. Recipes (Bill of Materials - Menu Item Ingredient Quantity)
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `restaurant_recipes` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `menu_item_id` INT UNSIGNED NOT NULL,
                `material_id` INT UNSIGNED NOT NULL,
                `quantity` DECIMAL(12,3) NOT NULL,
                `unit` VARCHAR(20) NOT NULL DEFAULT 'kg',
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_recipe_item` (`menu_item_id`, `material_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 15. Waste / Disposal Tracking
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `restaurant_disposals` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `disposal_type` ENUM('material','dish') NOT NULL DEFAULT 'dish',
                `item_id` INT UNSIGNED NOT NULL,
                `item_name` VARCHAR(191) NOT NULL,
                `quantity` DECIMAL(12,3) NOT NULL,
                `cost_loss` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `reason` VARCHAR(255) NOT NULL,
                `reported_by` INT UNSIGNED NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 16. Purchase Orders
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `restaurant_purchase_orders` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `display_id` VARCHAR(50) NOT NULL,
                `supplier_name` VARCHAR(191) NOT NULL,
                `status` ENUM('open','partial','received','cancelled') NOT NULL DEFAULT 'open',
                `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `notes` TEXT NULL,
                `created_at` DATETIME NOT NULL,
                `received_at` DATETIME NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 17. Goods Received Notes (GRN items)
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `restaurant_grn_items` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `purchase_order_id` INT UNSIGNED NOT NULL,
                `material_id` INT UNSIGNED NOT NULL,
                `quantity_ordered` DECIMAL(12,3) NOT NULL,
                `quantity_received` DECIMAL(12,3) NOT NULL,
                `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `expiration_date` DATE NULL,
                PRIMARY KEY (`id`),
                KEY `idx_grn_po` (`purchase_order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 18. Alter restaurant_tables for Combo Tables (only if table exists in this tenant)
        if ($this->db->table_exists('restaurant_tables')) {
            $tbl_cols = [];
            if (!$this->db->field_exists('is_combo', 'restaurant_tables')) {
                $tbl_cols['is_combo'] = [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'null' => false,
                ];
            }
            if (!$this->db->field_exists('combo_table_ids', 'restaurant_tables')) {
                $tbl_cols['combo_table_ids'] = [
                    'type' => 'TEXT',
                    'null' => true,
                ];
            }
            if (!empty($tbl_cols)) {
                $this->dbforge->add_column('restaurant_tables', $tbl_cols);
            }
        }

        // 19. Alter restaurant_menu_items for Stock and Out-Of-Stock Override (only if table exists in this tenant)
        if ($this->db->table_exists('restaurant_menu_items')) {
            $menu_cols = [];
            if (!$this->db->field_exists('is_tracked', 'restaurant_menu_items')) {
                $menu_cols['is_tracked'] = [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'null' => false,
                ];
            }
            if (!$this->db->field_exists('stock_quantity', 'restaurant_menu_items')) {
                $menu_cols['stock_quantity'] = [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => 0.00,
                    'null' => false,
                ];
            }
            if (!$this->db->field_exists('out_of_stock_type', 'restaurant_menu_items')) {
                $menu_cols['out_of_stock_type'] = [
                    'type' => "ENUM('none','indefinitely','custom')",
                    'default' => 'none',
                    'null' => false,
                ];
            }
            if (!$this->db->field_exists('out_of_stock_until', 'restaurant_menu_items')) {
                $menu_cols['out_of_stock_until'] = [
                    'type' => 'DATETIME',
                    'null' => true,
                ];
            }
            if (!empty($menu_cols)) {
                $this->dbforge->add_column('restaurant_menu_items', $menu_cols);
            }
        }
    }

    public function down(): void
    {
        $this->db->query("DROP TABLE IF EXISTS `restaurant_grn_items`");
        $this->db->query("DROP TABLE IF EXISTS `restaurant_purchase_orders`");
        $this->db->query("DROP TABLE IF EXISTS `restaurant_disposals`");
        $this->db->query("DROP TABLE IF EXISTS `restaurant_recipes`");
        $this->db->query("DROP TABLE IF EXISTS `restaurant_material_entries`");
        $this->db->query("DROP TABLE IF EXISTS `restaurant_materials`");
        $this->db->query("DROP TABLE IF EXISTS `restaurant_kitchen_chats`");
        $this->db->query("DROP TABLE IF EXISTS `restaurant_menu_specials`");
        $this->db->query("DROP TABLE IF EXISTS `restaurant_menu_ingredients`");
        $this->db->query("DROP TABLE IF EXISTS `restaurant_ingredients`");
        $this->db->query("DROP TABLE IF EXISTS `restaurant_coupon_usage`");
        $this->db->query("DROP TABLE IF EXISTS `restaurant_coupons`");
        $this->db->query("DROP TABLE IF EXISTS `restaurant_menu_mealtimes`");
        $this->db->query("DROP TABLE IF EXISTS `restaurant_mealtimes`");
        $this->db->query("DROP TABLE IF EXISTS `restaurant_menu_item_options`");
        $this->db->query("DROP TABLE IF EXISTS `restaurant_menu_option_values`");
        $this->db->query("DROP TABLE IF EXISTS `restaurant_menu_option_groups`");
    }
}
