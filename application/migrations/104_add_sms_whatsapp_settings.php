<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - SMS and WhatsApp messaging settings table (2026-08-27).
 *
 * Stores Netgsm SMS and Meta WhatsApp Business Cloud API credentials per tenant.
 * Single row per deployment - settings applied globally to all tenant notifications.
 * All gateway credentials are PII-encrypted (sf_pii_encrypt/sf_pii_decrypt).
 * ---------------------------------------------------------------------------- */

class Migration_Add_sms_whatsapp_settings extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('messaging_settings')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'sms_gateway' => [
                    'type' => 'ENUM',
                    'constraint' => ['none', 'netgsm'],
                    'default' => 'none',
                    'null' => false,
                ],
                'netgsm_username' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'netgsm_password' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'netgsm_header' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
                'sms_notifications_enabled' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'unsigned' => true,
                    'default' => 0,
                    'null' => false,
                ],
                'whatsapp_phone_number_id' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'whatsapp_access_token' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'whatsapp_waba_id' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'whatsapp_webhook_verify_token' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'whatsapp_business_phone_display' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => true,
                ],
                'whatsapp_notifications_enabled' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'unsigned' => true,
                    'default' => 0,
                    'null' => false,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                    'default' => date('Y-m-d H:i:s'),
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('messaging_settings', true, ['engine' => 'InnoDB']);

            // Insert default (unconfigured) settings row
            $this->db->insert('messaging_settings', [
                'sms_gateway' => 'none',
                'sms_notifications_enabled' => 0,
                'whatsapp_notifications_enabled' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('messaging_settings')) {
            $this->dbforge->drop_table('messaging_settings');
        }
    }
}
