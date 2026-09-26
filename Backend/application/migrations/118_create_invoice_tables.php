<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Internal Invoicing, wave 1 (2026-08-28).
 *
 * invoices + invoice_items: a billing document that aggregates charges from
 * appointments, packages, products, and memberships into one customer-facing
 * invoice. Invoices_model only READS those source tables when building an
 * invoice (build_from_appointment()/build_from_order()) - it never writes to
 * them, and critically never writes to payment_transactions either in this
 * phase (invoices can exist in draft/issued status with zero payment
 * collected). item_type/id_reference is a polymorphic reference (no DB-level
 * FK, since the referenced table varies) - validated in the model layer, not
 * the schema.
 * ---------------------------------------------------------------------------- */

class Migration_Create_invoice_tables extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('invoices')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'invoice_number' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
                'id_users_customer' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'comment' => 'FK to users.id'],
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['draft', 'issued', 'paid', 'partially_paid', 'void'],
                    'default' => 'draft',
                    'null' => false,
                ],
                'subtotal' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0, 'null' => false],
                'tax_total' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0, 'null' => false],
                'total' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0, 'null' => false],
                'currency' => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'TRY', 'null' => false],
                'issued_at' => ['type' => 'DATETIME', 'null' => true],
                'due_at' => ['type' => 'DATETIME', 'null' => true],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => 'FK to users.id'],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key(['invoice_number'], false, true); // UNIQUE
            $this->dbforge->add_key('id_users_customer');
            $this->dbforge->add_key('status');

            $this->dbforge->create_table('invoices');
        }

        if (!$this->db->table_exists('invoice_items')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_invoices' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'comment' => 'FK to invoices.id'],
                'item_type' => [
                    'type' => 'ENUM',
                    'constraint' => ['appointment', 'package', 'product', 'membership', 'pos_order'],
                    'null' => false,
                ],
                'id_reference' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'Polymorphic FK, meaning depends on item_type - no DB-level constraint',
                ],
                'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'quantity' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 1, 'null' => false],
                'unit_price' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
                'line_total' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_invoices');
            $this->dbforge->add_key(['item_type', 'id_reference']);

            $this->dbforge->create_table('invoice_items');
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $this->dbforge->drop_table('invoice_items');
        $this->dbforge->drop_table('invoices');
    }
}
