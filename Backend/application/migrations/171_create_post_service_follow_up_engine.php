<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration 171: BooKi Post-Service Follow-Up Engine.
 *
 * Implements:
 * 1. follow_up_rules: Sector-based automated follow-up rules across 9 industry families
 * 2. follow_up_dispatches: Delayed & scheduled queue tasks with quiet-hours, anti-spam,
 *    and response/NPS tracking
 * 3. users.marketing_opt_out: KVKK / İleti Güvenliği Opt-Out flag (WhatsApp RED/DUR)
 * 4. Default seed rules for healthcare, beauty, automotive, experience, sports & food
 */
class Migration_Create_post_service_follow_up_engine extends App_Migration
{
    public function up(): void
    {
        // 1. Add marketing_opt_out column to users table
        if ($this->db->table_exists('users')) {
            if (!$this->db->field_exists('marketing_opt_out', 'users')) {
                $this->dbforge->add_column('users', [
                    'marketing_opt_out' => [
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => 0,
                        'null' => false,
                        'after' => 'last_contact_channel',
                    ],
                ]);
            }
        }

        // 2. Create follow_up_rules table
        if (!$this->db->table_exists('follow_up_rules')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => false,
                ],
                'tenant_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'default' => 'default',
                    'null' => false,
                ],
                'industry_family' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ],
                'blueprint_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ],
                'rule_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ],
                'trigger_delay_interval' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ],
                'whatsapp_template_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => false,
                ],
                'dynamic_payload_schema' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'is_active' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'null' => false,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key(['tenant_id', 'blueprint_type', 'is_active']);
            $this->dbforge->add_key(['industry_family', 'is_active']);
            $this->dbforge->create_table('follow_up_rules');
        }

        // 3. Create follow_up_dispatches table
        if (!$this->db->table_exists('follow_up_dispatches')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => false,
                ],
                'tenant_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'default' => 'default',
                    'null' => false,
                ],
                'booking_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => false,
                ],
                'customer_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => false,
                ],
                'rule_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                ],
                'channel' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'WHATSAPP',
                    'null' => false,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 35,
                    'default' => 'SCHEDULED',
                    'null' => false,
                ],
                'scheduled_for' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'sent_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'payload' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'response_received' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'error_message' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key(['status', 'scheduled_for']);
            $this->dbforge->add_key(['tenant_id', 'created_at']);
            $this->dbforge->add_key('booking_id');
            $this->dbforge->add_key('customer_id');
            $this->dbforge->create_table('follow_up_dispatches');
        }

        // 4. Seed default vertical rules
        $this->seed_default_vertical_rules();
    }

    public function down(): void
    {
        if ($this->db->table_exists('follow_up_dispatches')) {
            $this->dbforge->drop_table('follow_up_dispatches');
        }

        if ($this->db->table_exists('follow_up_rules')) {
            $this->dbforge->drop_table('follow_up_rules');
        }

        if ($this->db->table_exists('users') && $this->db->field_exists('marketing_opt_out', 'users')) {
            $this->dbforge->drop_column('users', 'marketing_opt_out');
        }
    }

    /**
     * Seeds the master follow-up rules for all industry blueprint presets.
     */
    private function seed_default_vertical_rules(): void
    {
        if (!$this->db->table_exists('follow_up_rules')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $tenant_id = 'default';

        $rules = [
            // A. health_clinical
            [
                'id' => 'rule-dentist-reaction-check',
                'tenant_id' => $tenant_id,
                'industry_family' => 'health_clinical',
                'blueprint_type' => 'dentist',
                'rule_type' => 'reaction_check',
                'trigger_delay_interval' => '24 hours',
                'whatsapp_template_name' => 'dentist_postop_reaction',
                'dynamic_payload_schema' => json_encode([
                    'hasta_adi' => '{{customer_name}}',
                    'hekim_adi' => '{{provider_name}}',
                    'islem' => '{{service_name}}',
                    'soru' => 'Geçmiş olsun! Uyuşukluk sonrası zonklama, beklenmeyen kanama veya yüksek ateş var mı? (1: İyiyim, 2: Doktoruma Danışmak İstiyorum)',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 'rule-dentist-routine-recall',
                'tenant_id' => $tenant_id,
                'industry_family' => 'health_clinical',
                'blueprint_type' => 'dentist',
                'rule_type' => 'routine_check',
                'trigger_delay_interval' => '180 days',
                'whatsapp_template_name' => 'dentist_routine_recall',
                'dynamic_payload_schema' => json_encode([
                    'hasta_adi' => '{{customer_name}}',
                    'hekim_adi' => '{{provider_name}}',
                    'slot_link' => '{{booking_url}}',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 'rule-doctor-reaction-check',
                'tenant_id' => $tenant_id,
                'industry_family' => 'health_clinical',
                'blueprint_type' => 'doctor_clinic',
                'rule_type' => 'reaction_check',
                'trigger_delay_interval' => '24 hours',
                'whatsapp_template_name' => 'doctor_postop_reaction',
                'dynamic_payload_schema' => json_encode([
                    'hasta_adi' => '{{customer_name}}',
                    'hekim_adi' => '{{provider_name}}',
                    'soru' => 'Geçmiş olsun! Ameliyat/operasyon sonrası ağrı veya komplikasyon var mı? (1: İyiyim, 2: Doktoruma Danışmak İstiyorum)',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 'rule-dietitian-weekly-log',
                'tenant_id' => $tenant_id,
                'industry_family' => 'health_clinical',
                'blueprint_type' => 'psychology_dietitian_clinic',
                'rule_type' => 'diet_form',
                'trigger_delay_interval' => '7 days',
                'whatsapp_template_name' => 'dietitian_weekly_log',
                'dynamic_payload_schema' => json_encode([
                    'danisan_adi' => '{{customer_name}}',
                    'uzman_adi' => '{{provider_name}}',
                    'form_link' => '{{portal_url}}/forms/nutrition-log',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // B. beauty_wellness
            [
                'id' => 'rule-beauty-nps-review',
                'tenant_id' => $tenant_id,
                'industry_family' => 'beauty_wellness',
                'blueprint_type' => 'beauty_salon',
                'rule_type' => 'review_request',
                'trigger_delay_interval' => '2 hours',
                'whatsapp_template_name' => 'beauty_nps_review',
                'dynamic_payload_schema' => json_encode([
                    'musteri_adi' => '{{customer_name}}',
                    'uzman_adi' => '{{provider_name}}',
                    'soru' => 'Bugünkü bakımınızdan memnun kaldınız mı? 1-5 puan arası değerlendirin.',
                    'maps_link' => '{{google_maps_review_url}}',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 'rule-beauty-aftercare-guide',
                'tenant_id' => $tenant_id,
                'industry_family' => 'beauty_wellness',
                'blueprint_type' => 'beauty_salon',
                'rule_type' => 'aftercare',
                'trigger_delay_interval' => '24 hours',
                'whatsapp_template_name' => 'beauty_aftercare_guide',
                'dynamic_payload_schema' => json_encode([
                    'musteri_adi' => '{{customer_name}}',
                    'islem' => '{{service_name}}',
                    'talimat' => '24 saat boyunca sıcak su, sauna ve doğrudan güneş ışığından kaçınınız.',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 'rule-nail-smart-renewal',
                'tenant_id' => $tenant_id,
                'industry_family' => 'beauty_wellness',
                'blueprint_type' => 'nail_studio',
                'rule_type' => 'retention_rebook',
                'trigger_delay_interval' => '21 days',
                'whatsapp_template_name' => 'nail_smart_renewal',
                'dynamic_payload_schema' => json_encode([
                    'musteri_adi' => '{{customer_name}}',
                    'uzman_adi' => '{{provider_name}}',
                    'randevu_link' => '{{booking_url}}',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 'rule-barber-haircut-cycle',
                'tenant_id' => $tenant_id,
                'industry_family' => 'beauty_wellness',
                'blueprint_type' => 'barber',
                'rule_type' => 'retention_rebook',
                'trigger_delay_interval' => '28 days',
                'whatsapp_template_name' => 'barber_smart_renewal',
                'dynamic_payload_schema' => json_encode([
                    'musteri_adi' => '{{customer_name}}',
                    'berber_adi' => '{{provider_name}}',
                    'randevu_link' => '{{booking_url}}',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 'rule-beauty-laser-rebook',
                'tenant_id' => $tenant_id,
                'industry_family' => 'beauty_wellness',
                'blueprint_type' => 'beauty_salon',
                'rule_type' => 'retention_rebook',
                'trigger_delay_interval' => '40 days',
                'whatsapp_template_name' => 'beauty_laser_renewal',
                'dynamic_payload_schema' => json_encode([
                    'musteri_adi' => '{{customer_name}}',
                    'islem' => 'Lazer Epilasyon',
                    'randevu_link' => '{{booking_url}}',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // C. automotive
            [
                'id' => 'rule-auto-inspection-report',
                'tenant_id' => $tenant_id,
                'industry_family' => 'automotive',
                'blueprint_type' => 'auto_service_detailing',
                'rule_type' => 'asset_delivery',
                'trigger_delay_interval' => '15 minutes',
                'whatsapp_template_name' => 'auto_inspection_report',
                'dynamic_payload_schema' => json_encode([
                    'musteri_adi' => '{{customer_name}}',
                    'arac_plaka' => '{{vehicle_plate}}',
                    'rapor_link' => '{{report_url}}',
                    'garanti_belgesi' => '{{warranty_url}}',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 'rule-auto-detailing-aftercare',
                'tenant_id' => $tenant_id,
                'industry_family' => 'automotive',
                'blueprint_type' => 'auto_service_detailing',
                'rule_type' => 'aftercare',
                'trigger_delay_interval' => '48 hours',
                'whatsapp_template_name' => 'auto_detailing_aftercare',
                'dynamic_payload_schema' => json_encode([
                    'musteri_adi' => '{{customer_name}}',
                    'talimat' => 'İlk 7 gün basınçlı su ve fırçalı yıkama yaptırmayınız.',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 'rule-auto-periodic-rebook',
                'tenant_id' => $tenant_id,
                'industry_family' => 'automotive',
                'blueprint_type' => 'auto_service_detailing',
                'rule_type' => 'retention_rebook',
                'trigger_delay_interval' => '180 days',
                'whatsapp_template_name' => 'auto_periodic_maintenance',
                'dynamic_payload_schema' => json_encode([
                    'musteri_adi' => '{{customer_name}}',
                    'randevu_link' => '{{booking_url}}',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // D. education_experience
            [
                'id' => 'rule-escape-room-souvenir',
                'tenant_id' => $tenant_id,
                'industry_family' => 'education_experience',
                'blueprint_type' => 'experience_escape_room',
                'rule_type' => 'asset_delivery',
                'trigger_delay_interval' => '1 hour',
                'whatsapp_template_name' => 'escape_room_souvenir',
                'dynamic_payload_schema' => json_encode([
                    'ekip_adi' => '{{customer_name}}',
                    'oda_adi' => '{{service_name}}',
                    'sure' => '{{escape_time_minutes}}',
                    'fotograf_link' => '{{photo_url}}',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 'rule-workshop-ready-pickup',
                'tenant_id' => $tenant_id,
                'industry_family' => 'education_experience',
                'blueprint_type' => 'workshop',
                'rule_type' => 'asset_delivery',
                'trigger_delay_interval' => '0 minutes',
                'whatsapp_template_name' => 'workshop_pottery_ready',
                'dynamic_payload_schema' => json_encode([
                    'katilimci_adi' => '{{customer_name}}',
                    'eser_adi' => '{{artwork_name}}',
                    'teslim_kodu' => '{{pickup_code}}',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // E. sports_fitness
            [
                'id' => 'rule-pt-session-credit',
                'tenant_id' => $tenant_id,
                'industry_family' => 'sports_fitness',
                'blueprint_type' => 'pt_training',
                'rule_type' => 'asset_delivery',
                'trigger_delay_interval' => '0 minutes',
                'whatsapp_template_name' => 'pt_session_completed',
                'dynamic_payload_schema' => json_encode([
                    'ogrenci_adi' => '{{customer_name}}',
                    'antrenor_adi' => '{{provider_name}}',
                    'kalan_ders' => '{{remaining_credits}}',
                    'toplam_ders' => '{{total_credits}}',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 'rule-pt-renew-discount',
                'tenant_id' => $tenant_id,
                'industry_family' => 'sports_fitness',
                'blueprint_type' => 'pt_training',
                'rule_type' => 'retention_rebook',
                'trigger_delay_interval' => '12 hours',
                'whatsapp_template_name' => 'pt_renew_package_discount',
                'dynamic_payload_schema' => json_encode([
                    'ogrenci_adi' => '{{customer_name}}',
                    'kalan_ders' => '1',
                    'indirim' => '%10',
                    'odeme_link' => '{{payment_url}}',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 'rule-court-slot-retention',
                'tenant_id' => $tenant_id,
                'industry_family' => 'sports_fitness',
                'blueprint_type' => 'sports_court',
                'rule_type' => 'retention_rebook',
                'trigger_delay_interval' => '2 hours',
                'whatsapp_template_name' => 'court_weekly_slot_retention',
                'dynamic_payload_schema' => json_encode([
                    'kaptan_adi' => '{{customer_name}}',
                    'saha_adi' => '{{resource_name}}',
                    'rezervasyon_link' => '{{booking_url}}',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // F. hospitality_food & professional
            [
                'id' => 'rule-restaurant-nps-rating',
                'tenant_id' => $tenant_id,
                'industry_family' => 'hospitality_food',
                'blueprint_type' => 'restaurant',
                'rule_type' => 'review_request',
                'trigger_delay_interval' => '45 minutes',
                'whatsapp_template_name' => 'restaurant_nps_rating',
                'dynamic_payload_schema' => json_encode([
                    'misafir_adi' => '{{customer_name}}',
                    'isletme_adi' => '{{company_name}}',
                    'nps_link' => '{{nps_url}}',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 'rule-hotel-invoice-lost',
                'tenant_id' => $tenant_id,
                'industry_family' => 'hospitality_food',
                'blueprint_type' => 'hotel',
                'rule_type' => 'asset_delivery',
                'trigger_delay_interval' => '2 hours',
                'whatsapp_template_name' => 'hotel_checkout_invoice_lost',
                'dynamic_payload_schema' => json_encode([
                    'misafir_adi' => '{{customer_name}}',
                    'fatura_link' => '{{invoice_url}}',
                    'kayip_esya_link' => '{{portal_url}}/lost-and-found',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 'rule-law-meeting-summary',
                'tenant_id' => $tenant_id,
                'industry_family' => 'professional',
                'blueprint_type' => 'law_firm',
                'rule_type' => 'asset_delivery',
                'trigger_delay_interval' => '2 hours',
                'whatsapp_template_name' => 'law_meeting_summary_docs',
                'dynamic_payload_schema' => json_encode([
                    'muvekkil_adi' => '{{customer_name}}',
                    'avukat_adi' => '{{provider_name}}',
                    'ozet_link' => '{{portal_url}}/documents',
                ]),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($rules as $rule) {
            $exists = $this->db->get_where('follow_up_rules', ['id' => $rule['id']])->row_array();
            if (!$exists) {
                $this->db->insert('follow_up_rules', $rule);
            }
        }
    }
}
