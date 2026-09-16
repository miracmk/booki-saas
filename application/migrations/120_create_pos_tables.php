<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - POS, wave 1 (2026-08-28).
 *
 * orders + order_items: a point-of-sale style sale (walk-in or tied to a
 * customer), mixing products/services/packages/memberships in one basket.
 * Payment capture happens via Orders_model::checkout(), which is a NEW
 * consumer of the existing Payment_gateway_factory/Payment_transactions_model
 * - it adds zero new methods to either and never changes an existing method
 * signature (see migration 121 for the one additive change to
 * payment_transactions itself: a widened `type` enum + two new nullable FK
 * columns).
 * ---------------------------------------------------------------------------- */

class Migration_Create_pos_tables extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('orders')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_users_customer' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'FK to users.id, NULL = walk-in sale with no customer record',
                ],
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['open', 'paid', 'cancelled', 'refunded'],
                    'default' => 'open',
                    'null' => false,
                ],
                'subtotal' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0, 'null' => false],
                'tax_total' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0, 'null' => false],
                'total' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0, 'null' => false],
                'currency' => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'TRY', 'null' => false],
                'sold_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => 'FK to users.id'],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users_customer');
            $this->dbforge->add_key('status');

            $this->dbforge->create_table('orders');
        }

        if (!$this->db->table_exists('order_items')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_orders' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'comment' => 'FK to orders.id'],
                'item_type' => [
                    'type' => 'ENUM',
                    'constraint' => ['product', 'service', 'package', 'membership'],
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
            $this->dbforge->add_key('id_orders');
            $this->dbforge->add_key(['item_type', 'id_reference']);

            $this->dbforge->create_table('order_items');
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $this->dbforge->drop_table('order_items');
        $this->dbforge->drop_table('orders');
    }
}
