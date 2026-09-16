<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Automation Engine (Dalga 3 / Faz 3.2, 2026-09-08).
 *
 * Per-tenant WHEN/IF/THEN rule engine. Each row is one rule:
 *   event       — the hub event this rule listens to
 *   conditions  — JSON array of {field, op, value} tests; empty/null = always fire
 *   actions     — JSON array of action descriptors to run when conditions pass
 *   enabled     — 0 = dormant (default, see seeded templates below)
 *
 * ACTION TYPES (JSON):
 *   { "type": "message", "recipient": "customer|provider|admin|secretary",
 *     "channels": "sms,whatsapp", "subject": "...", "text": "..." }
 *   { "type": "note", "text": "..." }
 *   { "type": "log" }
 *
 * CONDITIONS (JSON):
 *   [ { "field": "service.duration_minutes", "op": ">=", "value": 60 },
 *     { "field": "appointment.start_datetime", "op": "weekdays_only" } ]
 *
 * Supported fields (read from ctx arrays):
 *   appointment.id, appointment.start_datetime, appointment.end_datetime
 *   service.id, service.name, service.duration_minutes
 *   provider.id, provider.first_name, provider.last_name
 *   customer.id, customer.first_name, customer.last_name
 *
 * Supported ops: =, !=, <, >, <=, >=, in (value = csv), weekdays_only
 *
 * PLACEHOLDERS in action text are rendered by Communication_hub::render_message()
 * (same {service_name}, {customer_name}, etc. syntax).
 *
 * Default seed (6 disabled templates, see seed_defaults()):
 *   1. Hoş geldin – appointment_created → customer → sms+whatsapp
 *   2. Seans hatırlatması – appointment_created → customer → sms
 *   3. Seans sonrası – appointment_completed → customer → sms+email
 *   4. Değerlendirme isteği – appointment_completed → customer → sms
 *   5. VIP sadakat – appointment_completed → customer (condition: appointment_count>=5) → sms
 *   6. Kayıp randevu telafisi – appointment_cancelled → provider → sms
 */

class Migration_Create_automation_rules_table extends EA_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists('automation_rules')) {
            $fields = [
                'id' => [
                    'type' => 'INT', 'constraint' => 11, 'unsigned' => true,
                    'auto_increment' => true, 'null' => false,
                ],
                'name' => [
                    'type' => 'VARCHAR', 'constraint' => 128, 'null' => false,
                ],
                'event' => [
                    'type' => 'VARCHAR', 'constraint' => 32, 'null' => false,
                ],
                'conditions' => [
                    'type' => 'TEXT', 'null' => true,
                ],
                'actions' => [
                    'type' => 'TEXT', 'null' => false,
                ],
                'enabled' => [
                    'type' => 'TINYINT', 'constraint' => 1, 'null' => false,
                    'default' => 0,
                ],
                'created_at' => [
                    'type' => 'DATETIME', 'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                ],
                'updated_at' => [
                    'type' => 'DATETIME', 'null' => true,
                ],
            ];

            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('event');
            $this->dbforge->add_key('enabled');
            $this->dbforge->create_table('automation_rules');
        }

        if (!$this->db->table_exists('automation_log')) {
            $log_fields = [
                'id' => [
                    'type' => 'INT', 'constraint' => 11, 'unsigned' => true,
                    'auto_increment' => true, 'null' => false,
                ],
                'rule_id' => [
                    'type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true,
                ],
                'event' => [
                    'type' => 'VARCHAR', 'constraint' => 32, 'null' => false,
                ],
                'appointment_id' => [
                    'type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true,
                ],
                'actions_run' => [
                    'type' => 'INT', 'constraint' => 3, 'null' => false,
                    'default' => 0,
                ],
                'created_at' => [
                    'type' => 'DATETIME', 'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                ],
            ];

            $this->dbforge->add_field($log_fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('rule_id');
            $this->dbforge->add_key('event');
            $this->dbforge->create_table('automation_log');
        }

        $this->seed_defaults();
    }

    public function down(): void
    {
        foreach (['automation_rules', 'automation_log'] as $table) {
            if ($this->db->table_exists($table)) {
                $this->dbforge->drop_table($table);
            }
        }
    }

    private function seed_defaults(): void
    {
        $now = date('Y-m-d H:i:s');

        // Only seed if no rules exist yet (idempotent for multi-migrate).
        if ((int) $this->db->count_all('automation_rules') > 0) {
            return;
        }

        $templates = [
            [
                'name' => 'Hoş geldin mesajı (sms+whatsapp)',
                'event' => 'appointment_created',
                'conditions' => null,
                'actions' => json_encode([
                    ['type' => 'message', 'recipient' => 'customer',
                     'channels' => 'sms,whatsapp', 'subject' => '',
                     'text' => 'Merhaba {customer_name}, {service_name} randevunuz {start_datetime} tarihinde {provider_name} ile oluşturuldu. {company_name}'],
                ], JSON_THROW_ON_ERROR),
                'enabled' => 0,
            ],
            [
                'name' => 'Seans hatırlatması (24 saat once sms)',
                'event' => 'appointment_created',
                'conditions' => json_encode([
                    ['field' => 'service.duration_minutes', 'op' => '>=', 'value' => 30],
                ], JSON_THROW_ON_ERROR),
                'actions' => json_encode([
                    ['type' => 'message', 'recipient' => 'customer',
                     'channels' => 'sms', 'subject' => '',
                     'text' => '{customer_name}, yarınki {service_name} seansınızı hatırlatırız — {start_datetime}. {company_name}'],
                ], JSON_THROW_ON_ERROR),
                'enabled' => 0,
            ],
            [
                'name' => 'Seans sonrası bildirim',
                'event' => 'appointment_completed',
                'conditions' => null,
                'actions' => json_encode([
                    ['type' => 'message', 'recipient' => 'customer',
                     'channels' => 'sms,whatsapp', 'subject' => '',
                     'text' => 'Merhaba {customer_name}, {service_name} seansınız {provider_name} ile tamamlanmıştır. {company_name}'],
                ], JSON_THROW_ON_ERROR),
                'enabled' => 0,
            ],
            [
                'name' => 'Değerlendirme isteği (2 saat sonra sms)',
                'event' => 'appointment_completed',
                'conditions' => null,
                'actions' => json_encode([
                    ['type' => 'message', 'recipient' => 'customer',
                     'channels' => 'sms', 'subject' => '',
                     'text' => 'Merhaba {customer_name}, {service_name} deneyiminizi değerlendirir misiniz? {company_name}'],
                ], JSON_THROW_ON_ERROR),
                'enabled' => 0,
            ],
            [
                'name' => 'VIP sadakat bildirimi (5+ randevu sms)',
                'event' => 'appointment_completed',
                'conditions' => json_encode([
                    ['field' => 'customer.appointment_count', 'op' => '>=', 'value' => 5],
                ], JSON_THROW_ON_ERROR),
                'actions' => json_encode([
                    ['type' => 'message', 'recipient' => 'customer',
                     'channels' => 'sms', 'subject' => '',
                     'text' => 'Sadık müşterimiz {customer_name}, {appointment_count}. seansınız tamamlandı! Size özel %10 indirim sizi bekliyor. {company_name}'],
                    ['type' => 'note', 'text' => '[Otomasyon] VIP sadakat bildirimi gönderildi.'],
                ], JSON_THROW_ON_ERROR),
                'enabled' => 0,
            ],
            [
                'name' => 'İptal sonrası provider bildirimi',
                'event' => 'appointment_cancelled',
                'conditions' => null,
                'actions' => json_encode([
                    ['type' => 'message', 'recipient' => 'provider',
                     'channels' => 'sms,whatsapp', 'subject' => '',
                     'text' => '{customer_name}, {service_name} randevusunu iptal etti ({reason}). {company_name}'],
                ], JSON_THROW_ON_ERROR),
                'enabled' => 0,
            ],
        ];

        foreach ($templates as $t) {
            $this->db->insert('automation_rules', [
                'name' => $t['name'],
                'event' => $t['event'],
                'conditions' => $t['conditions'],
                'actions' => $t['actions'],
                'enabled' => $t['enabled'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}