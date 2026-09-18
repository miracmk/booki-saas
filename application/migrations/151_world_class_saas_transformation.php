<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration 151: World-Class SaaS Platform Transformation.
 *
 * Adds:
 * 1. Adisyon (Tab/Bill/Order) & Adisyon Items System
 * 2. Restaurant Suite (Tables, Floor Plan, Reservations, Experiences)
 * 3. Operational Check-In / Check-Out Logs & Real-Time Occupancy
 * 4. Finance, Expenses, Cash Registers (Kasa) & Staff Commissions/Payouts
 * 5. Consumable Recipes & Stock Movements for Automated Inventory Deduction
 * 6. Package & Membership Extensions (Freeze, Transfer, Bonus Sessions, Plan Builder)
 * 7. Service Add-ons & Required Multi-Resources
 * 8. Audit Logging & Notification Events
 * 9. Industry & Modularity Settings
 */
class Migration_World_class_saas_transformation extends EA_Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------------
        // 1. ADISYON SYSTEM (Billing & Order Tabs)
        // ---------------------------------------------------------------------
        if (!$this->db->table_exists('adisyons')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'adisyon_number' => ['type' => 'VARCHAR', 'constraint' => 64],
                'id_users_customer' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_appointments' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_restaurant_tables' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_users_staff' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'open'], // open, closed, billed, cancelled
                'payment_status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'unpaid'], // unpaid, partially_paid, paid, refunded
                'invoice_status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'uninvoiced'], // uninvoiced, invoiced, voided
                'id_invoices' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'subtotal' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'discount_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'discount_percent' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => '0.00'],
                'tax_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'total_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'paid_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'tip_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'opened_at' => ['type' => 'DATETIME'],
                'closed_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('adisyon_number');
            $this->dbforge->add_key('id_users_customer');
            $this->dbforge->add_key('id_appointments');
            $this->dbforge->add_key('id_restaurant_tables');
            $this->dbforge->add_key('status');
            $this->dbforge->add_key('payment_status');
            $this->dbforge->create_table('adisyons');
        }

        if (!$this->db->table_exists('adisyon_items')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_adisyons' => ['type' => 'INT', 'unsigned' => true],
                'item_type' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'service'], // service, product, addon, custom
                'id_services' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_products' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_service_addons' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'name' => ['type' => 'VARCHAR', 'constraint' => 255],
                'unit_price' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'quantity' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '1.00'],
                'discount_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'tax_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => '20.00'],
                'tax_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'total_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'id_users_staff' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_customer_packages' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_customer_memberships' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'notes' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_adisyons');
            $this->dbforge->create_table('adisyon_items');
        }

        // Adisyon Payments (records payment methods split: cash, card, transfer, package, membership)
        if (!$this->db->table_exists('adisyon_payments')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_adisyons' => ['type' => 'INT', 'unsigned' => true],
                'payment_method' => ['type' => 'VARCHAR', 'constraint' => 32], // cash, card, bank_transfer, online, package, membership, credit
                'amount' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
                'id_customer_packages' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_customer_memberships' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_payment_transactions' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'notes' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'received_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_adisyons');
            $this->dbforge->create_table('adisyon_payments');
        }

        // ---------------------------------------------------------------------
        // 2. RESTAURANT ARCHITECTURE (Tables, Floor Plan, Reservations, Experiences)
        // ---------------------------------------------------------------------
        if (!$this->db->table_exists('restaurant_tables')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'table_number' => ['type' => 'VARCHAR', 'constraint' => 32],
                'name' => ['type' => 'VARCHAR', 'constraint' => 128],
                'section' => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Ana Salon'], // Ana Salon, Teras, Bahçe, VIP, Bar
                'capacity' => ['type' => 'INT', 'default' => 4],
                'shape' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'rectangle'], // round, square, rectangle
                'pos_x' => ['type' => 'INT', 'default' => 50],
                'pos_y' => ['type' => 'INT', 'default' => 50],
                'width' => ['type' => 'INT', 'default' => 100],
                'height' => ['type' => 'INT', 'default' => 80],
                'status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'available'], // available, reserved, seated, dining, bill_requested, paid, cleaning, inactive
                'current_id_adisyons' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'current_id_reservations' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_users_server' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'seated_at' => ['type' => 'DATETIME', 'null' => true],
                'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('table_number');
            $this->dbforge->add_key('section');
            $this->dbforge->add_key('status');
            $this->dbforge->create_table('restaurant_tables');
        }

        if (!$this->db->table_exists('restaurant_reservations')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_users_customer' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_restaurant_tables' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'party_size' => ['type' => 'INT', 'default' => 2],
                'reservation_datetime' => ['type' => 'DATETIME'],
                'duration_minutes' => ['type' => 'INT', 'default' => 120],
                'status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'confirmed'], // pending, confirmed, seated, completed, cancelled, no_show
                'id_restaurant_experiences' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'special_occasion' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true], // Birthday, Anniversary, Business, Romantic, Other
                'allergies' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'preferences' => ['type' => 'TEXT', 'null' => true],
                'is_vip' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'deposit_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'deposit_status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'not_required'], // not_required, pending, paid, refunded
                'id_payment_transactions' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users_customer');
            $this->dbforge->add_key('id_restaurant_tables');
            $this->dbforge->add_key('reservation_datetime');
            $this->dbforge->add_key('status');
            $this->dbforge->create_table('restaurant_reservations');
        }

        if (!$this->db->table_exists('restaurant_experiences')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'title' => ['type' => 'VARCHAR', 'constraint' => 255],
                'description' => ['type' => 'TEXT', 'null' => true],
                'price_per_person' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'duration_minutes' => ['type' => 'INT', 'default' => 120],
                'min_party' => ['type' => 'INT', 'default' => 1],
                'max_party' => ['type' => 'INT', 'default' => 20],
                'deposit_required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'deposit_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('restaurant_experiences');
        }

        // ---------------------------------------------------------------------
        // 3. OPERATIONAL CHECK-IN / CHECK-OUT LOGS
        // ---------------------------------------------------------------------
        if (!$this->db->table_exists('checkin_logs')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_users_customer' => ['type' => 'INT', 'unsigned' => true],
                'id_appointments' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_customer_memberships' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_customer_packages' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_restaurant_tables' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'checkin_method' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'manual'], // qr, phone, manual, kiosk
                'entry_timestamp' => ['type' => 'DATETIME'],
                'exit_timestamp' => ['type' => 'DATETIME', 'null' => true],
                'duration_minutes' => ['type' => 'INT', 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'inside'], // inside, departed, auto_checkout
                'checked_in_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'notes' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users_customer');
            $this->dbforge->add_key('status');
            $this->dbforge->add_key('entry_timestamp');
            $this->dbforge->create_table('checkin_logs');
        }

        // ---------------------------------------------------------------------
        // 4. FINANCE, EXPENSES, CASH REGISTERS (KASA) & STAFF COMMISSIONS
        // ---------------------------------------------------------------------
        if (!$this->db->table_exists('expenses')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'supplier_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'category' => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'other'], // rent, utilities, supplies, inventory, marketing, salaries, software, maintenance, taxes, other
                'title' => ['type' => 'VARCHAR', 'constraint' => 255],
                'amount' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
                'tax_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'expense_date' => ['type' => 'DATE'],
                'payment_method' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'cash'], // cash, bank_transfer, card
                'is_recurring' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'recurrence_period' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true], // monthly, quarterly, yearly
                'attachment_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'paid'], // paid, pending, approved
                'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('category');
            $this->dbforge->add_key('expense_date');
            $this->dbforge->add_key('status');
            $this->dbforge->create_table('expenses');
        }

        if (!$this->db->table_exists('cash_registers')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'register_name' => ['type' => 'VARCHAR', 'constraint' => 128, 'default' => 'Ana Kasa'],
                'opening_balance' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'current_balance' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'open'], // open, closed
                'opened_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'closed_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'opened_at' => ['type' => 'DATETIME'],
                'closed_at' => ['type' => 'DATETIME', 'null' => true],
                'total_cash_in' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'total_cash_out' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'expected_cash' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'actual_cash' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'difference' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'closing_notes' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('status');
            $this->dbforge->create_table('cash_registers');
        }

        if (!$this->db->table_exists('bank_accounts')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'bank_name' => ['type' => 'VARCHAR', 'constraint' => 128],
                'account_name' => ['type' => 'VARCHAR', 'constraint' => 128],
                'iban' => ['type' => 'VARCHAR', 'constraint' => 64],
                'currency' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'TRY'],
                'balance' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
                'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('bank_accounts');
        }

        if (!$this->db->table_exists('staff_commissions')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_users_staff' => ['type' => 'INT', 'unsigned' => true],
                'id_appointments' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_adisyons' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_services' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_products' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'sale_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'commission_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => '0.00'],
                'commission_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'tip_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'pending'], // pending, approved, paid
                'payout_date' => ['type' => 'DATE', 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users_staff');
            $this->dbforge->add_key('status');
            $this->dbforge->create_table('staff_commissions');
        }

        if (!$this->db->table_exists('staff_payouts')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_users_staff' => ['type' => 'INT', 'unsigned' => true],
                'period_start' => ['type' => 'DATE'],
                'period_end' => ['type' => 'DATE'],
                'base_salary' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'total_commission' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'total_tips' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'advances_deduction' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'net_payout' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'payment_method' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'bank_transfer'],
                'payment_date' => ['type' => 'DATE'],
                'status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'completed'],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users_staff');
            $this->dbforge->create_table('staff_payouts');
        }

        // ---------------------------------------------------------------------
        // 5. INVENTORY & CONSUMABLE RECIPES & STOCK MOVEMENTS
        // ---------------------------------------------------------------------
        if (!$this->db->table_exists('service_consumables')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_services' => ['type' => 'INT', 'unsigned' => true],
                'id_products' => ['type' => 'INT', 'unsigned' => true],
                'quantity_used' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '1.00'],
                'unit' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'adet'], // adet, ml, gr, paket, çift
                'notes' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_services');
            $this->dbforge->add_key('id_products');
            $this->dbforge->create_table('service_consumables');
        }

        if (!$this->db->table_exists('stock_movements')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_products' => ['type' => 'INT', 'unsigned' => true],
                'movement_type' => ['type' => 'VARCHAR', 'constraint' => 32], // purchase, service_consumption, waste, adjustment, sale, return
                'quantity' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
                'unit_cost' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'id_appointments' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_adisyons' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'id_users' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'notes' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_products');
            $this->dbforge->add_key('movement_type');
            $this->dbforge->add_key('created_at');
            $this->dbforge->create_table('stock_movements');
        }

        if (!$this->db->table_exists('purchase_orders')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'po_number' => ['type' => 'VARCHAR', 'constraint' => 64],
                'supplier_name' => ['type' => 'VARCHAR', 'constraint' => 255],
                'total_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'ordered'], // draft, ordered, received, cancelled
                'expected_delivery_date' => ['type' => 'DATE', 'null' => true],
                'received_date' => ['type' => 'DATE', 'null' => true],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('purchase_orders');
        }

        if (!$this->db->table_exists('purchase_order_items')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_purchase_orders' => ['type' => 'INT', 'unsigned' => true],
                'id_products' => ['type' => 'INT', 'unsigned' => true],
                'quantity' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
                'unit_cost' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
                'total_cost' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_purchase_orders');
            $this->dbforge->create_table('purchase_order_items');
        }

        // ---------------------------------------------------------------------
        // 6. PACKAGE & MEMBERSHIP EXTENSIONS
        // ---------------------------------------------------------------------
        if ($this->db->table_exists('customer_packages')) {
            $fields = [];
            if (!$this->db->field_exists('is_frozen', 'customer_packages')) {
                $fields['is_frozen'] = ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0];
            }
            if (!$this->db->field_exists('frozen_at', 'customer_packages')) {
                $fields['frozen_at'] = ['type' => 'DATETIME', 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('frozen_until', 'customer_packages')) {
                $fields['frozen_until'] = ['type' => 'DATETIME', 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('bonus_sessions', 'customer_packages')) {
                $fields['bonus_sessions'] = ['type' => 'INT', 'default' => 0];
            }
            if (!$this->db->field_exists('notes', 'customer_packages')) {
                $fields['notes'] = ['type' => 'TEXT', 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('source_package_id', 'customer_packages')) {
                $fields['source_package_id'] = ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null];
            }
            if (!empty($fields)) {
                $this->dbforge->add_column('customer_packages', $fields);
            }
        }

        if (!$this->db->table_exists('package_usage_logs')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_customer_packages' => ['type' => 'INT', 'unsigned' => true],
                'id_appointments' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'sessions_deducted' => ['type' => 'INT', 'default' => 1],
                'remaining_after' => ['type' => 'INT'],
                'action' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'deducted'], // deducted, restored, adjusted, transferred
                'performed_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'notes' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_customer_packages');
            $this->dbforge->create_table('package_usage_logs');
        }

        if ($this->db->table_exists('membership_plans')) {
            $fields = [];
            if (!$this->db->field_exists('billing_cycle', 'membership_plans')) {
                $fields['billing_cycle'] = ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'monthly']; // monthly, quarterly, semiannual, yearly, custom
            }
            if (!$this->db->field_exists('setup_fee', 'membership_plans')) {
                $fields['setup_fee'] = ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'];
            }
            if (!$this->db->field_exists('is_unlimited', 'membership_plans')) {
                $fields['is_unlimited'] = ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0];
            }
            if (!$this->db->field_exists('included_services_json', 'membership_plans')) {
                $fields['included_services_json'] = ['type' => 'TEXT', 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('guest_allowance_per_month', 'membership_plans')) {
                $fields['guest_allowance_per_month'] = ['type' => 'INT', 'default' => 0];
            }
            if (!$this->db->field_exists('freeze_days_allowed', 'membership_plans')) {
                $fields['freeze_days_allowed'] = ['type' => 'INT', 'default' => 30];
            }
            if (!$this->db->field_exists('category', 'membership_plans')) {
                $fields['category'] = ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Genel'];
            }
            if (!empty($fields)) {
                $this->dbforge->add_column('membership_plans', $fields);
            }
        }

        if ($this->db->table_exists('customer_memberships')) {
            $fields = [];
            if (!$this->db->field_exists('qr_code_token', 'customer_memberships')) {
                $fields['qr_code_token'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('guest_passes_used', 'customer_memberships')) {
                $fields['guest_passes_used'] = ['type' => 'INT', 'default' => 0];
            }
            if (!$this->db->field_exists('is_frozen', 'customer_memberships')) {
                $fields['is_frozen'] = ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0];
            }
            if (!$this->db->field_exists('frozen_at', 'customer_memberships')) {
                $fields['frozen_at'] = ['type' => 'DATETIME', 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('frozen_until', 'customer_memberships')) {
                $fields['frozen_until'] = ['type' => 'DATETIME', 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('notes', 'customer_memberships')) {
                $fields['notes'] = ['type' => 'TEXT', 'null' => true, 'default' => null];
            }
            if (!empty($fields)) {
                $this->dbforge->add_column('customer_memberships', $fields);
            }
        }

        // ---------------------------------------------------------------------
        // 7. SERVICE ADD-ONS & REQUIRED RESOURCES
        // ---------------------------------------------------------------------
        if (!$this->db->table_exists('service_addons')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_services' => ['type' => 'INT', 'unsigned' => true],
                'name' => ['type' => 'VARCHAR', 'constraint' => 255],
                'duration_minutes' => ['type' => 'INT', 'default' => 15],
                'price' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                'description' => ['type' => 'TEXT', 'null' => true],
                'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_services');
            $this->dbforge->create_table('service_addons');
        }

        if (!$this->db->table_exists('service_required_resources')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_services' => ['type' => 'INT', 'unsigned' => true],
                'resource_type' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'station'], // station, machine, chair, device
                'id_stations' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'quantity' => ['type' => 'INT', 'default' => 1],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_services');
            $this->dbforge->create_table('service_required_resources');
        }

        // ---------------------------------------------------------------------
        // 8. AUDIT LOGGING & NOTIFICATION LOGS
        // ---------------------------------------------------------------------
        if (!$this->db->table_exists('audit_logs')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_users' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'action_type' => ['type' => 'VARCHAR', 'constraint' => 64], // create, update, delete, price_change, status_change, checkin, checkout, payment, consume_package
                'entity_type' => ['type' => 'VARCHAR', 'constraint' => 64], // appointment, customer, adisyon, payment, invoice, package, membership, stock
                'entity_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'before_payload' => ['type' => 'JSON', 'null' => true],
                'after_payload' => ['type' => 'JSON', 'null' => true],
                'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
                'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('entity_type');
            $this->dbforge->add_key('entity_id');
            $this->dbforge->add_key('action_type');
            $this->dbforge->add_key('created_at');
            $this->dbforge->create_table('audit_logs');
        }

        // ---------------------------------------------------------------------
        // 9. INDUSTRY & MODULARITY SETTINGS SEEDING
        // ---------------------------------------------------------------------
        $settings_to_seed = [
            'business_type' => 'wellness', // wellness, beauty, restaurant, clinic, studio, general
            'features_enabled_json' => json_encode([
                'appointments' => true,
                'calendar' => true,
                'packages' => true,
                'memberships' => true,
                'checkin' => true,
                'adisyon' => true,
                'pos' => true,
                'finance' => true,
                'expenses' => true,
                'inventory' => true,
                'staff_commissions' => true,
                'marketing' => true,
                'reviews' => true,
                'loyalty' => true,
                'client_portal' => true,
                'restaurant_floor_plan' => false,
                'restaurant_reservations' => false,
                'restaurant_experiences' => false,
            ]),
            'currency_symbol' => '₺',
            'currency_code' => 'TRY',
            'e_invoice_provider' => 'parasut', // parasut, uyumsoft, logo, mock
            'e_invoice_auto_issue' => '0',
            'cash_register_auto_open' => '1',
            'max_capacity' => '50',
            'no_show_threshold_for_deposit' => '2',
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
        $tables = [
            'audit_logs',
            'service_required_resources',
            'service_addons',
            'package_usage_logs',
            'purchase_order_items',
            'purchase_orders',
            'stock_movements',
            'service_consumables',
            'staff_payouts',
            'staff_commissions',
            'bank_accounts',
            'cash_registers',
            'expenses',
            'checkin_logs',
            'restaurant_experiences',
            'restaurant_reservations',
            'restaurant_tables',
            'adisyon_payments',
            'adisyon_items',
            'adisyons',
        ];

        foreach ($tables as $tbl) {
            if ($this->db->table_exists($tbl)) {
                $this->dbforge->drop_table($tbl);
            }
        }
    }
}
