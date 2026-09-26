<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - CRM customer card fields (2026-08-25).
 *
 * - social_links: encrypted JSON ({"whatsapp":"...","telegram":"...","instagram":"..."}) - contact
 *   links, PII like phone/email so it goes through the same sf_pii_encrypt()/sf_pii_decrypt() pipeline
 *   (see Customers_model::ENCRYPTED_ONLY_FIELDS). No hash/search index - nobody searches customers by
 *   their Instagram handle.
 * - last_contact_channel: plaintext, short enum-ish string (whatsapp/telegram/instagram/phone/email/
 *   in_person) set manually by staff for now. NOT encrypted - it's a channel label, not PII, and
 *   staff need to filter/sort by it later. Automated tracking (who actually messaged when) awaits the
 *   messaging integrations (Composio/native Telegram/Meta) - this column exists so that phase has
 *   somewhere to write to without another migration.
 * ---------------------------------------------------------------------------- */

class Migration_Add_crm_fields_to_users_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('social_links', 'users')) {
            $this->dbforge->add_column('users', [
                'social_links' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'notes',
                ],
            ]);
        }

        if (!$this->db->field_exists('last_contact_channel', 'users')) {
            $this->dbforge->add_column('users', [
                'last_contact_channel' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'null' => true,
                    'after' => 'social_links',
                ],
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('last_contact_channel', 'users')) {
            $this->dbforge->drop_column('users', 'last_contact_channel');
        }

        if ($this->db->field_exists('social_links', 'users')) {
            $this->dbforge->drop_column('users', 'social_links');
        }
    }
}
