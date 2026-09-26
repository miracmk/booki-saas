<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Communication Hub (Dalga 3 / Faz 3.1, 2026-09-08).
 *
 * Per-tenant routing table that drives the Communication Hub engine
 * (application/libraries/Communication_hub.php). Each row binds:
 *   - an EVENT (appointment_created / appointment_completed / appointment_cancelled)
 *   - a RECIPIENT group (customer / provider / admin / secretary)
 *   - one or more CHANNELS, e.g. "email,sms,whatsapp" (comma-separated)
 *   - an optional per-rule subject + message template ({{placeholder}} syntax)
 *
 * The hub only ever SENDS through the queue's existing per-handler gates
 * (Events: send_sms()/send_whatsapp()/send_telegram()/send_generic_email()),
 * each of which no-ops gracefully when the underlying channel is disabled or
 * unconfigured. So a rule may list a channel the business hasn't set up yet -
 * it just won't fire until it is configured.
 *
 * Default seeding is deliberately conservative:
 *   - appointment_completed rules are ENABLED (email only): the pre-hub code had
 *     NO notification whatsoever at check-out, so this is the one event the hub
 *     turns on out of the box without duplicating anything.
 *   - appointment_created / appointment_cancelled rules are DISABLED because the
 *     legacy hardcoded Notifications path already emails those events. They are
 *     seeded with complementary channels (sms,whatsapp) so a business that opts
 *     in gets the NEW channels on top, not a duplicate email.
 *
 * Admins can flip rules via the console commands, see Console::communication_rules()
 * / communication_rule_set() / communication_rule_template().
 * ---------------------------------------------------------------------------- */

class Migration_Create_communication_rules_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('communication_rules')) {
            $fields = [
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                    'null' => false,
                ],
                'event' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'null' => false,
                ],
                'recipient' => [
                    'type' => 'VARCHAR',
                    'constraint' => 16,
                    'null' => false,
                ],
                'channel' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => false,
                ],
                'subject' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'message' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'enabled' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 1,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ];

            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('event');
            $this->dbforge->add_key('recipient');
            $this->dbforge->add_key('enabled');
            $this->dbforge->create_table('communication_rules');

            $this->seed_defaults();
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('communication_rules')) {
            $this->dbforge->drop_table('communication_rules');
        }
    }

    /**
     * Seed the per-tenant default rules (see header comment for the enable/disable logic).
     */
    private function seed_defaults(): void
    {
        $now = date('Y-m-d H:i:s');

        $defaults = [
            // appointment_completed had NO notification path before the hub - enabled (email).
            ['event' => 'appointment_completed', 'recipient' => 'customer', 'channel' => 'email', 'subject' => 'Seansın Tamamlandı', 'message' => 'Merhaba {customer_name}, {service_name} seansınız {provider_name} ile {start_datetime} tarihinde tamamlanmıştır. {company_name}', 'enabled' => 1],
            ['event' => 'appointment_completed', 'recipient' => 'provider', 'channel' => 'email', 'subject' => 'Seans Tamamlandı', 'message' => '{customer_name} - {service_name} seansı {start_datetime} tarihinde tamamlandı. {company_name}', 'enabled' => 1],
            ['event' => 'appointment_completed', 'recipient' => 'admin', 'channel' => 'email', 'subject' => 'Seans Tamamlandı', 'message' => '{customer_name} - {service_name} seansı {provider_name} ile {start_datetime} tarihinde tamamlandı. {company_name}', 'enabled' => 1],

            // appointment_created / appointment_cancelled emails already exist in the legacy
            // Notifications path - seed DISABLED with the new complementary channels only.
            ['event' => 'appointment_created', 'recipient' => 'customer', 'channel' => 'sms,whatsapp', 'subject' => 'Randevunuz Onaylandı', 'message' => 'Merhaba {customer_name}, {service_name} için {start_datetime} tarihli randevunuz oluşturuldu. {company_name}', 'enabled' => 0],
            ['event' => 'appointment_created', 'recipient' => 'provider', 'channel' => 'sms,whatsapp', 'subject' => 'Yeni Randevu', 'message' => 'Yeni randevu: {customer_name} - {service_name}, {start_datetime}. {company_name}', 'enabled' => 0],
            ['event' => 'appointment_created', 'recipient' => 'admin', 'channel' => 'sms,whatsapp', 'subject' => 'Yeni Randevu', 'message' => 'Yeni randevu: {customer_name} - {service_name} ({provider_name}), {start_datetime}. {company_name}', 'enabled' => 0],
            ['event' => 'appointment_cancelled', 'recipient' => 'customer', 'channel' => 'sms,whatsapp', 'subject' => 'Randevunuz İptal Edildi', 'message' => 'Merhaba {customer_name}, {service_name} için {start_datetime} tarihli randevunuz iptal edildi. {company_name}', 'enabled' => 0],
            ['event' => 'appointment_cancelled', 'recipient' => 'provider', 'channel' => 'sms,whatsapp', 'subject' => 'Randevu İptal Edildi', 'message' => 'Randevu iptal edildi: {customer_name} - {service_name}, {start_datetime}. {company_name}', 'enabled' => 0],
        ];

        foreach ($defaults as $rule) {
            $this->db->insert('communication_rules', [
                'event' => $rule['event'],
                'recipient' => $rule['recipient'],
                'channel' => $rule['channel'],
                'subject' => $rule['subject'],
                'message' => $rule['message'],
                'enabled' => $rule['enabled'],
                'created_at' => $now,
            ]);
        }
    }
}