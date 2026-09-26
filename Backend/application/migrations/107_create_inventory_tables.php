<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Inventory/stock management (products, stock movements, appointment products).
 *
 * Supports product management, stock tracking, and linking products to appointments.
 * products: product catalog with SKU, pricing, stock levels.
 * stock_movements: audit trail for all stock changes (sales, restocks, adjustments).
 * appointment_products: line items for products used/sold in an appointment.
 * ---------------------------------------------------------------------------- */

class Migration_Create_inventory_tables extends App_Migration
{
    public function up()
    {
        // products table
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 191,
            ],
            'sku' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'comment' => 'Stock keeping unit, unique',
            ],
            'sale_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'comment' => 'Selling price per unit',
            ],
            'cost_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'comment' => 'Cost price for margin calculation',
            ],
            'stock_quantity' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => 'Current stock level',
            ],
            'low_stock_threshold' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 5,
                'comment' => 'Reorder point alert level',
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->dbforge->add_key('id', true);
        if (isset($this->db->dbdriver) && $this->db->dbdriver !== 'sqlite') {
            $this->dbforge->add_key('sku');
        }
        $this->dbforge->add_key('is_active');

        $this->dbforge->create_table('products');

        // stock_movements table
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'id_products' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'FK to products.id',
            ],
            'movement_type' => [
                'type' => 'VARCHAR',
                'constraint' => 16,
                'comment' => 'sale, restock, adjustment, waste',
            ],
            'quantity_delta' => [
                'type' => 'INT',
                'comment' => 'Can be positive (restock/adjustment) or negative (sale/waste)',
            ],
            'id_appointments' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'FK to appointments.id (if applicable)',
            ],
            'id_users_customer' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'FK to users.id (customer)',
            ],
            'unit_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'comment' => 'Price at time of transaction',
            ],
            'recorded_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'FK to users.id (who recorded)',
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('id_products');
        $this->dbforge->add_key('id_appointments');
        $this->dbforge->add_key('movement_type');
        $this->dbforge->add_key('created_at');

        $this->dbforge->create_table('stock_movements');

        // appointment_products table
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'id_appointments' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'FK to appointments.id',
            ],
            'id_products' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'FK to products.id',
            ],
            'quantity' => [
                'type' => 'INT',
                'comment' => 'Quantity of product used/sold',
            ],
            'unit_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'comment' => 'Price per unit at time of appointment',
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('id_appointments');
        $this->dbforge->add_key('id_products');

        $this->dbforge->create_table('appointment_products');
    }

    public function down()
    {
        $this->dbforge->drop_table('appointment_products');
        $this->dbforge->drop_table('stock_movements');
        $this->dbforge->drop_table('products');
    }
}
