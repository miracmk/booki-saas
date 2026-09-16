<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi customization - "Accounting/ERP Connector" (2026-08-27):
 * Stores OAuth credentials and configuration for integrating with accounting systems
 * (initially Paraşüt, extensible via the Accounting_connector_interface).
 *
 * Tokens are encrypted at rest using sf_pii_encrypt() from the salonflora_crypto_helper.
 * ---------------------------------------------------------------------------- */

class Migration_Create_accounting_connections_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('accounting_connections')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'provider' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'parasut',
                    'null' => false,
                ],
                'access_token' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'comment' => 'OAuth2 access token, encrypted at rest',
                ],
                'refresh_token' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'comment' => 'OAuth2 refresh token, encrypted at rest',
                ],
                'company_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                    'comment' => 'Remote company/account ID at the provider',
                ],
                'expires_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'comment' => 'Token expiration timestamp for refresh logic',
                ],
                'auto_invoice_enabled' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'null' => false,
                    'comment' => '1 = automatically create invoices for completed appointments',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('accounting_connections', true, ['engine' => 'InnoDB']);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('accounting_connections')) {
            $this->dbforge->drop_table('accounting_connections');
        }
    }
}
