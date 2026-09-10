<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - WhatsApp dual-mode (Dalga 3 / Faz 3.5, 2026-09-10).
 *
 * Supports the two connection methods the roadmap promises per tenant:
 *
 *   1. official    - Meta WhatsApp Business Cloud API (existing Whatsapp_client,
 *                    messaging_settings fields, webhook endpoint).
 *   2. unofficial  - QR-code device pairing through a separate Node sidecar
 *                    ("ki-wa-bridge", Baileys/whatsapp-web.js based). The PHP app
 *                    talks to the bridge over a short REST contract only; the
 *                    pairing/QR/websocket state lives in the bridge, never here.
 *
 * Adds to `messaging_settings`:
 *   - whatsapp_mode                      which sender the app uses ('official'|'unofficial')
 *   - whatsapp_unofficial_status         cached bridge session state for the panel
 *   - whatsapp_unofficial_name           display name of the paired device/number
 *   - whatsapp_unofficial_consent_at     logged timestamp of the informed-consent opt-in
 *   - whatsapp_bridge_url                REST base URL of the tenant's bridge instance
 *   - whatsapp_bridge_secret            shared secret (PII-encrypted) used both ways
 *                                       (app -> bridge on /v1/send, bridge -> app on
 *                                       whatsapp/bridge_inbound) for auth.
 *
 * Adds to `users`:
 *   - whatsapp_wa_id                     the user's WhatsApp ID (dialing-prefix number),
 *                                        used to match inbound messages to a customer.
 * -------------------------------------------------------------------------- */

class Migration_Add_whatsapp_dual_mode extends CI_Migration
{
    public function up(): void
    {
        $this->add_messaging_settings_columns();
        $this->add_users_column();
    }

    private function add_messaging_settings_columns(): void
    {
        if (!$this->db->table_exists('messaging_settings')) {
            return;
        }

        $columns = [
            'whatsapp_mode' => [
                'type' => 'VARCHAR',
                'constraint' => 16,
                'default' => 'official',
                'null' => false,
            ],
            'whatsapp_unofficial_status' => [
                'type' => 'VARCHAR',
                'constraint' => 16,
                'default' => 'disconnected',
                'null' => false,
            ],
            'whatsapp_unofficial_name' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'whatsapp_unofficial_consent_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'whatsapp_bridge_url' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'whatsapp_bridge_secret' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ];

        foreach ($columns as $name => $definition) {
            if (!$this->db->field_exists($name, 'messaging_settings')) {
                $this->dbforge->add_column('messaging_settings', [$name => $definition]);
            }
        }
    }

    private function add_users_column(): void
    {
        if (!$this->db->table_exists('users')) {
            return;
        }

        if (!$this->db->field_exists('whatsapp_wa_id', 'users')) {
            $this->dbforge->add_column('users', [
                'whatsapp_wa_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => true,
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('messaging_settings')) {
            foreach (['whatsapp_bridge_secret', 'whatsapp_bridge_url', 'whatsapp_unofficial_consent_at',
                         'whatsapp_unofficial_name', 'whatsapp_unofficial_status', 'whatsapp_mode'] as $name) {
                if ($this->db->field_exists($name, 'messaging_settings')) {
                    $this->dbforge->drop_column('messaging_settings', $name);
                }
            }
        }

        if ($this->db->table_exists('users') && $this->db->field_exists('whatsapp_wa_id', 'users')) {
            $this->dbforge->drop_column('users', 'whatsapp_wa_id');
        }
    }
}