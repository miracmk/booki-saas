<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Email (SMTP) settings (Dalga 3 / Faz 3.6, 2026-09-10).
 *
 * Tenant SMTP override + platform fallback SMTP with promo footer.
 *
 * Adds to `messaging_settings`:
 *   - smtp_host              SMTP server hostname (nullable, tenant-specific)
 *   - smtp_port              SMTP server port (nullable, defaults to 587)
 *   - smtp_crypto            TLS/SSL encryption type (nullable, defaults to 'tls')
 *   - smtp_user              SMTP username (encrypted PII)
 *   - smtp_pass              SMTP password (encrypted PII)
 *   - smtp_from_name         SMTP From name override (nullable, tenant-specific)
 *   - smtp_from_address      SMTP From address override (nullable, tenant-specific)
 *
 * If tenant provides smtp_host, that SMTP config is used.
 * Otherwise, platform fallback SMTP (.env MAIL_SMTP_*) is used, and a
 * promo footer ("Sent via BooKi") is appended to the email body.
 * -------------------------------------------------------------------------- */

class Migration_Add_email_settings_to_messaging extends CI_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists('messaging_settings')) {
            return;
        }

        $columns = [
            'smtp_host' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'smtp_port' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'smtp_crypto' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true,
            ],
            'smtp_user' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'smtp_pass' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'smtp_from_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'smtp_from_address' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ];

        foreach ($columns as $name => $definition) {
            if (!$this->db->field_exists($name, 'messaging_settings')) {
                $this->dbforge->add_column('messaging_settings', [$name => $definition]);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('messaging_settings')) {
            foreach (['smtp_host', 'smtp_port', 'smtp_crypto', 'smtp_user', 'smtp_pass',
                         'smtp_from_name', 'smtp_from_address'] as $name) {
                if ($this->db->field_exists($name, 'messaging_settings')) {
                    $this->dbforge->drop_column('messaging_settings', $name);
                }
            }
        }
    }
}
