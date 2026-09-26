<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - payment gateway settings (2026-08-27).
 *
 * Single-row configuration table per tenant DB, storing active payment gateway
 * selection (iyzico, PayTR, Stripe, or none) and corresponding API credentials.
 * Sensitive fields (API keys/secrets) are encrypted via sf_pii_encrypt() before storage.
 * ---------------------------------------------------------------------------- */

class Migration_Create_payment_settings_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('payment_settings')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'active_gateway' => [
                    'type' => 'ENUM',
                    'constraint' => ['none', 'iyzico', 'paytr', 'stripe'],
                    'default' => 'none',
                    'null' => false,
                ],
                'require_deposit' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false],
                'deposit_type' => [
                    'type' => 'ENUM',
                    'constraint' => ['fixed', 'percentage'],
                    'null' => true,
                ],
                'deposit_value' => ['type' => 'DECIMAL', 'constraint' => [10, 2], 'null' => true],
                'iyzico_api_key' => ['type' => 'TEXT', 'null' => true],
                'iyzico_secret_key' => ['type' => 'TEXT', 'null' => true],
                'paytr_merchant_id' => ['type' => 'TEXT', 'null' => true],
                'paytr_merchant_key' => ['type' => 'TEXT', 'null' => true],
                'paytr_merchant_salt' => ['type' => 'TEXT', 'null' => true],
                'stripe_publishable_key' => ['type' => 'TEXT', 'null' => true],
                'stripe_secret_key' => ['type' => 'TEXT', 'null' => true],
                'webhook_secret' => ['type' => 'TEXT', 'null' => true],
                'is_sandbox' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'null' => false],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('payment_settings', true, ['engine' => 'InnoDB']);

            // Insert default row (active_gateway='none' - payments disabled by default)
            $this->db->insert('payment_settings', [
                'active_gateway' => 'none',
                'require_deposit' => 0,
                'deposit_type' => null,
                'deposit_value' => null,
                'is_sandbox' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('payment_settings')) {
            $this->dbforge->drop_table('payment_settings');
        }
    }
}
