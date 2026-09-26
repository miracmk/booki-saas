<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - payment transactions ledger (2026-08-27).
 *
 * Audit trail of all payment attempts/completions. Links to appointments (may be null
 * for unlinked transactions) and customers. Tracks status changes and raw gateway responses.
 * ---------------------------------------------------------------------------- */

class Migration_Create_payment_transactions_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('payment_transactions')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_appointments' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'id_users' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'gateway' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
                'provider_transaction_id' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
                'intent_id' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
                'amount' => ['type' => 'DECIMAL', 'constraint' => [10, 2], 'null' => false],
                'currency' => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'TRY', 'null' => false],
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['pending', 'succeeded', 'failed', 'refunded', 'partially_refunded'],
                    'default' => 'pending',
                    'null' => false,
                ],
                'type' => [
                    'type' => 'ENUM',
                    'constraint' => ['deposit', 'full_payment', 'refund'],
                    'default' => 'deposit',
                    'null' => false,
                ],
                'raw_response' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_appointments');
            $this->dbforge->add_key('id_users');
            $this->dbforge->add_key('provider_transaction_id');
            $this->dbforge->add_key('intent_id');
            $this->dbforge->create_table('payment_transactions', true, ['engine' => 'InnoDB']);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('payment_transactions')) {
            $this->dbforge->drop_table('payment_transactions');
        }
    }
}
