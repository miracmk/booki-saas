<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration 162: Multi-Vertical Enterprise Suite for BooKi SaaS.
 *
 * Implements features across all 6 core verticals:
 * 1. Güzellik / Wellness / Spa: Deposit (Kapora) system & Gift Cards / Hediyelik Kartlar
 * 2. Restoran / Kafe / Bistro: Guest Intelligence (Tercih/Alerjen/VIP) & Kitchen Display System (KDS)
 * 3. Spor / Kort / Fitness: Court Matchmaking & Turnstile Access Integration
 * 4. Sağlık / Klinik: EHR (Elektronik Sağlık Kaydı), SOAP Clinical Charting & Sigorta/Insurance
 * 5. Otomotiv / Servis / Ekspertiz: Customer Vehicles, DVI (Digital Vehicle Inspection) & Work Orders
 * 6. Deneyim / Eğlence / Escape Room: Digital Waivers, Booking Addons & QR Event Tickets
 */
class Migration_Multi_vertical_enterprise_suite extends EA_Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------------
        // 1. GÜZELLİK & SPA - KAPORA (DEPOSIT) & HEDİYE KARTI SİSTEMİ
        // ---------------------------------------------------------------------
        if ($this->db->table_exists('appointments')) {
            if (!$this->db->field_exists('deposit_amount', 'appointments')) {
                $this->dbforge->add_column('appointments', [
                    'deposit_amount' => [
                        'type' => 'DECIMAL',
                        'constraint' => '10,2',
                        'default' => 0.00,
                        'after' => 'payment_balance_amount',
                    ],
                ]);
            }
            if (!$this->db->field_exists('deposit_status', 'appointments')) {
                $this->dbforge->add_column('appointments', [
                    'deposit_status' => [
                        'type' => 'VARCHAR',
                        'constraint' => 30,
                        'default' => 'none', // none, pending, paid, refunded, forfeited
                        'after' => 'deposit_amount',
                    ],
                ]);
            }
            if (!$this->db->field_exists('deposit_paid_at', 'appointments')) {
                $this->dbforge->add_column('appointments', [
                    'deposit_paid_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'default' => null,
                        'after' => 'deposit_status',
                    ],
                ]);
            }
            if (!$this->db->field_exists('deposit_transaction_id', 'appointments')) {
                $this->dbforge->add_column('appointments', [
                    'deposit_transaction_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => 100,
                        'null' => true,
                        'default' => null,
                        'after' => 'deposit_paid_at',
                    ],
                ]);
            }
        }

        // Table: ea_gift_cards
        if (!$this->db->table_exists('gift_cards')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'code' => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
                'initial_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
                'current_balance' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
                'id_users_customer' => ['type' => 'INT', 'null' => true, 'default' => null],
                'recipient_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'default' => null],
                'recipient_email' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'default' => null],
                'recipient_phone' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => null],
                'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'], // active, redeemed, expired, disabled
                'expires_at' => ['type' => 'DATE', 'null' => true, 'default' => null],
                'notes' => ['type' => 'TEXT', 'null' => true, 'default' => null],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users_customer');
            $this->dbforge->create_table('gift_cards', true);
        }

        // Table: ea_gift_card_redemptions
        if (!$this->db->table_exists('gift_card_redemptions')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_gift_cards' => ['type' => 'INT', 'unsigned' => true],
                'id_appointments' => ['type' => 'INT', 'null' => true, 'default' => null],
                'id_adisyons' => ['type' => 'INT', 'null' => true, 'default' => null],
                'redeemed_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
                'redeemed_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_gift_cards');
            $this->dbforge->create_table('gift_card_redemptions', true);
        }

        // ---------------------------------------------------------------------
        // 2. RESTORAN & KAFE - GUEST INTELLIGENCE & KITCHEN DISPLAY SYSTEM (KDS)
        // ---------------------------------------------------------------------
        if (!$this->db->table_exists('restaurant_guest_preferences')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_users_customer' => ['type' => 'INT', 'unique' => true],
                'vip_level' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'regular'], // regular, vip, vvip
                'dietary_restrictions' => ['type' => 'TEXT', 'null' => true, 'default' => null], // gluten-free, vegan, nut allergy
                'seating_preference' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null], // window, quiet, terrace
                'favorite_drink' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
                'special_notes' => ['type' => 'TEXT', 'null' => true, 'default' => null],
                'visit_count' => ['type' => 'INT', 'default' => 0],
                'no_show_count' => ['type' => 'INT', 'default' => 0],
                'average_spend' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('restaurant_guest_preferences', true);
        }

        if (!$this->db->table_exists('kitchen_orders')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_adisyons' => ['type' => 'INT', 'null' => true, 'default' => null],
                'id_restaurant_tables' => ['type' => 'INT', 'null' => true, 'default' => null],
                'station' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'kitchen'], // kitchen, bar, dessert, grill
                'item_name' => ['type' => 'VARCHAR', 'constraint' => 255],
                'quantity' => ['type' => 'INT', 'default' => 1],
                'notes' => ['type' => 'TEXT', 'null' => true, 'default' => null],
                'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'new'], // new, preparing, ready, served, cancelled
                'ordered_at' => ['type' => 'DATETIME'],
                'prepared_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
                'served_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_adisyons');
            $this->dbforge->add_key('status');
            $this->dbforge->create_table('kitchen_orders', true);
        }

        // ---------------------------------------------------------------------
        // 3. SPOR / KORT / HALI SAHA - OYUNCU EŞLEŞTİRME (MATCHMAKING) & LİGLER
        // ---------------------------------------------------------------------
        if (!$this->db->table_exists('sports_court_matches')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'title' => ['type' => 'VARCHAR', 'constraint' => 255],
                'id_stations' => ['type' => 'INT', 'null' => true, 'default' => null],
                'id_services' => ['type' => 'INT', 'null' => true, 'default' => null],
                'start_datetime' => ['type' => 'DATETIME'],
                'end_datetime' => ['type' => 'DATETIME'],
                'match_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'open'], // open, private, league, tournament
                'sport_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'padel'], // padel, tennis, football, basketball
                'level_required' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'all'], // beginner, intermediate, advanced, 1.0-5.0
                'max_players' => ['type' => 'INT', 'default' => 4],
                'current_players' => ['type' => 'INT', 'default' => 1],
                'price_per_player' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
                'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'open'], // open, full, in_progress, completed, cancelled
                'created_by_user_id' => ['type' => 'INT'],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('status');
            $this->dbforge->add_key('sport_type');
            $this->dbforge->create_table('sports_court_matches', true);
        }

        if (!$this->db->table_exists('sports_match_participants')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_matches' => ['type' => 'INT', 'unsigned' => true],
                'id_users_customer' => ['type' => 'INT'],
                'payment_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'], // pending, paid, refunded
                'team' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => null], // Team A, Team B
                'skill_level' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => null],
                'joined_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_matches');
            $this->dbforge->add_key('id_users_customer');
            $this->dbforge->create_table('sports_match_participants', true);
        }

        // ---------------------------------------------------------------------
        // 4. SAĞLIK / KLİNİK - EHR / SOAP KLİNİK CHARTING & ANAMNEZ & SİGORTA
        // ---------------------------------------------------------------------
        if (!$this->db->table_exists('clinical_records')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_users_customer' => ['type' => 'INT'],
                'id_appointments' => ['type' => 'INT', 'null' => true, 'default' => null],
                'id_users_provider' => ['type' => 'INT'],
                'record_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'soap_note'], // soap_note, anamnesis, prescription, lab_result, diet_plan, vet_record
                'subjective' => ['type' => 'TEXT', 'null' => true, 'default' => null], // Danışan şikayeti / Anamnez
                'objective' => ['type' => 'TEXT', 'null' => true, 'default' => null], // Muayene bulguları / Tetkikler
                'assessment' => ['type' => 'TEXT', 'null' => true, 'default' => null], // Teşhis / Klinik değerlendirme
                'plan' => ['type' => 'TEXT', 'null' => true, 'default' => null], // Tedavi planı / İlaç / Egzersiz
                'attachments_json' => ['type' => 'TEXT', 'null' => true, 'default' => null],
                'is_confidential' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users_customer');
            $this->dbforge->add_key('id_appointments');
            $this->dbforge->create_table('clinical_records', true);
        }

        if (!$this->db->table_exists('patient_insurances')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_users_customer' => ['type' => 'INT'],
                'provider_name' => ['type' => 'VARCHAR', 'constraint' => 150], // SGK, Allianz, Acıbadem vb.
                'policy_number' => ['type' => 'VARCHAR', 'constraint' => 100],
                'valid_until' => ['type' => 'DATE', 'null' => true, 'default' => null],
                'coverage_ratio' => ['type' => 'INT', 'default' => 100],
                'notes' => ['type' => 'TEXT', 'null' => true, 'default' => null],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users_customer');
            $this->dbforge->create_table('patient_insurances', true);
        }

        // ---------------------------------------------------------------------
        // 5. OTOMOTİV / SERVİS / EKSPERTİZ - ARAÇLAR, DVI & İŞ EMİRLERİ (WORK ORDERS)
        // ---------------------------------------------------------------------
        if (!$this->db->table_exists('customer_vehicles')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_users_customer' => ['type' => 'INT'],
                'plate_number' => ['type' => 'VARCHAR', 'constraint' => 30],
                'vin' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => null],
                'brand' => ['type' => 'VARCHAR', 'constraint' => 100],
                'model' => ['type' => 'VARCHAR', 'constraint' => 100],
                'year' => ['type' => 'INT', 'null' => true, 'default' => null],
                'color' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => null],
                'current_km' => ['type' => 'INT', 'default' => 0],
                'fuel_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'default' => null],
                'notes' => ['type' => 'TEXT', 'null' => true, 'default' => null],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('plate_number');
            $this->dbforge->add_key('id_users_customer');
            $this->dbforge->create_table('customer_vehicles', true);
        }

        if (!$this->db->table_exists('vehicle_inspections')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_vehicles' => ['type' => 'INT', 'unsigned' => true],
                'id_appointments' => ['type' => 'INT', 'null' => true, 'default' => null],
                'inspector_id' => ['type' => 'INT', 'null' => true, 'default' => null],
                'inspection_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'general_service'], // pre_wash, detailing, general_service, full_expertise
                'overall_score' => ['type' => 'INT', 'null' => true, 'default' => null],
                'items_json' => ['type' => 'MEDIUMTEXT', 'null' => true, 'default' => null],
                'customer_shared_token' => ['type' => 'VARCHAR', 'constraint' => 64, 'unique' => true],
                'customer_approved_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_vehicles');
            $this->dbforge->create_table('vehicle_inspections', true);
        }

        if (!$this->db->table_exists('work_orders')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'work_order_number' => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
                'id_vehicles' => ['type' => 'INT', 'unsigned' => true],
                'id_appointments' => ['type' => 'INT', 'null' => true, 'default' => null],
                'id_users_technician' => ['type' => 'INT', 'null' => true, 'default' => null],
                'status' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'created'], // created, inspected, estimate_pending, approved, in_progress, parts_waiting, quality_check, ready, delivered
                'estimated_cost' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
                'final_cost' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
                'labor_items_json' => ['type' => 'TEXT', 'null' => true, 'default' => null],
                'parts_items_json' => ['type' => 'TEXT', 'null' => true, 'default' => null],
                'delivery_datetime' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_vehicles');
            $this->dbforge->add_key('status');
            $this->dbforge->create_table('work_orders', true);
        }

        // ---------------------------------------------------------------------
        // 6. DENEYİM / EĞLENCE / ESCAPE ROOM - DİJİTAL FERAGATNAME & BİLETLEME
        // ---------------------------------------------------------------------
        if (!$this->db->table_exists('digital_waivers')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'title' => ['type' => 'VARCHAR', 'constraint' => 255],
                'content_html' => ['type' => 'TEXT'],
                'is_mandatory' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'applicable_service_ids' => ['type' => 'TEXT', 'null' => true, 'default' => null],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('digital_waivers', true);
        }

        if (!$this->db->table_exists('waiver_signatures')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_waivers' => ['type' => 'INT', 'unsigned' => true],
                'id_appointments' => ['type' => 'INT', 'null' => true, 'default' => null],
                'id_users_customer' => ['type' => 'INT', 'null' => true, 'default' => null],
                'signer_full_name' => ['type' => 'VARCHAR', 'constraint' => 150],
                'signer_email' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'default' => null],
                'signer_phone' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => null],
                'signature_data' => ['type' => 'LONGTEXT'],
                'ip_address' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => null],
                'signed_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_waivers');
            $this->dbforge->add_key('id_appointments');
            $this->dbforge->create_table('waiver_signatures', true);
        }

        if (!$this->db->table_exists('appointment_addons')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'id_appointments' => ['type' => 'INT'],
                'id_service_addons' => ['type' => 'INT', 'null' => true, 'default' => null],
                'name' => ['type' => 'VARCHAR', 'constraint' => 255],
                'quantity' => ['type' => 'INT', 'default' => 1],
                'unit_price' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
                'total_price' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_appointments');
            $this->dbforge->create_table('appointment_addons', true);
        }

        if (!$this->db->table_exists('event_tickets')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'ticket_code' => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
                'id_appointments' => ['type' => 'INT'],
                'id_users_customer' => ['type' => 'INT'],
                'seat_or_slot_label' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => null],
                'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'valid'], // valid, used, cancelled
                'used_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_appointments');
            $this->dbforge->create_table('event_tickets', true);
        }
    }

    public function down(): void
    {
        $this->dbforge->drop_table('event_tickets', true);
        $this->dbforge->drop_table('appointment_addons', true);
        $this->dbforge->drop_table('waiver_signatures', true);
        $this->dbforge->drop_table('digital_waivers', true);
        $this->dbforge->drop_table('work_orders', true);
        $this->dbforge->drop_table('vehicle_inspections', true);
        $this->dbforge->drop_table('customer_vehicles', true);
        $this->dbforge->drop_table('patient_insurances', true);
        $this->dbforge->drop_table('clinical_records', true);
        $this->dbforge->drop_table('sports_match_participants', true);
        $this->dbforge->drop_table('sports_court_matches', true);
        $this->dbforge->drop_table('kitchen_orders', true);
        $this->dbforge->drop_table('restaurant_guest_preferences', true);
        $this->dbforge->drop_table('gift_card_redemptions', true);
        $this->dbforge->drop_table('gift_cards', true);

        if ($this->db->table_exists('appointments')) {
            $cols = ['deposit_amount', 'deposit_status', 'deposit_paid_at', 'deposit_transaction_id'];
            foreach ($cols as $col) {
                if ($this->db->field_exists($col, 'appointments')) {
                    $this->dbforge->drop_column('appointments', $col);
                }
            }
        }
    }
}
