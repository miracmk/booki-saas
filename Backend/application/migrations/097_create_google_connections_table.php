<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - "Google Entegrasyonları" (2026-08-25): a general-purpose Google OAuth
 * connection store, separate from the existing Calendar-specific sync (Google.php/Google_sync.php,
 * which keeps storing its own token in providers' user_settings under 'google_token' - untouched, still
 * the only thing driving Calendar sync).
 *
 * This table lets EITHER the company (owner_type='company', owner_id NULL) OR an individual provider
 * (owner_type='provider', owner_id = that user's id) connect their own Google account for one or more
 * of: Contacts, Drive, Sheets, Docs, Tasks (see Google_integrations_client::SERVICES). One OAuth consent
 * can grant several services at once - granted_scopes/enabled_services record exactly what was approved.
 *
 * Tokens are PII-grade secrets, encrypted at rest with the same sf_pii_encrypt() pipeline used for
 * customer/provider contact fields (see salonflora_crypto_helper.php) - never stored in plaintext.
 * ---------------------------------------------------------------------------- */

class Migration_Create_google_connections_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('google_connections')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'owner_type' => [
                    'type' => 'ENUM',
                    'constraint' => ['company', 'provider'],
                    'null' => false,
                ],
                // Salon Flora customization - 0 (not NULL) represents the company-level connection: MySQL
                // allows multiple NULLs through a UNIQUE index, which would let several "company" rows
                // slip in; 0 makes the (owner_type, owner_id) unique index actually enforce "one
                // connection per owner". Never a real user id (ids start at 1).
                'owner_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                    'default' => 0,
                ],
                'google_account_email' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'access_token' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'refresh_token' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'granted_scopes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'enabled_services' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'token_expires_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('google_connections', true, ['engine' => 'InnoDB']);

            // One connection per owner - a second "connect" for the same owner_type/owner_id updates the
            // existing row instead of creating a duplicate (see Google_integrations_client::save_connection()).
            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('google_connections') .
                    ' ADD UNIQUE INDEX idx_google_connections_owner (owner_type, owner_id)',
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('google_connections')) {
            $this->dbforge->drop_table('google_connections');
        }
    }
}
