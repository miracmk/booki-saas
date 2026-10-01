<?php

namespace Tests\Integration;

defined('BASEPATH') or exit('No direct script access allowed');

use Tests\TenantTestCase;

/**
 * World-Class Hospitality PMS Integration Test Suite.
 *
 * Validates the full suite of PMS features inspired by QloApps, hotel-mgmt-system,
 * HotinGo, Django HMS, and HotelDruid:
 * - Room Types & Dynamic Rate Plans
 * - 14-Day Visual Tape Chart / Gantt Matrix
 * - Express Check-In & Check-Out Lifecycle
 * - Folio Itemization & %2 Konaklama Vergisi / KDV Taxes
 * - Turkish KBS Law 1774 Identity Declarations & XML Export
 * - Housekeeping Task Dispatching & Status Auto-Advancement
 * - Maintenance Tickets & Room Blocking
 * - Night Audit (Gün Sonu Devri) ADR & RevPAR Calculations
 * - 2-Way OTA iCal Feeds (Airbnb, Booking.com)
 * - RESTful API v1 Hospitality Endpoints
 */
class WorldClassHospitalityPmsIntegrationTest extends TenantTestCase
{
    private static int $staffUserId;
    private static int $guestUserId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ci()->load->model('hospitality_model');
        $this->ci()->load->model('adisyons_model');

        self::$staffUserId = $this->ensureUser('hotel_staff_test@booki.local', 'Kemal', 'Mudur', '05551112233', 1, 'admin');
        self::$guestUserId = $this->ensureUser('hotel_guest_test@booki.local', 'Zeynep', 'Kaya', '05552223344', 3, 'customer');
    }

    private function ensureUser(string $email, string $firstName, string $lastName, string $phone, int $roleId, string $roleSlug): int
    {
        $db = self::db();
        $user = $db->get_where('users', ['email' => $email])->row_array();
        if (!$user) {
            $db->insert('users', [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone_number' => $phone,
                'id_roles' => $roleId,
                'role_slug' => $roleSlug,
                'is_active' => 1,
            ]);
            return (int) $db->insert_id();
        }
        return (int) $user['id'];
    }

    private function createVerticalsController(): \Verticals
    {
        require_once APPPATH . 'controllers/Verticals.php';
        $ci = self::ci();
        $controller = (new \ReflectionClass(\Verticals::class))->newInstanceWithoutConstructor();
        $controller->props = &$ci->props;
        $controller->load = $ci->load;
        $controller->load->model('hospitality_model');
        $controller->load->model('adisyons_model');
        $controller->load->model('customers_model');
        $controller->load->model('roles_model');
        $controller->hospitality_model = $ci->hospitality_model;
        $controller->adisyons_model = $ci->adisyons_model;
        $controller->customers_model = $ci->customers_model;
        $controller->roles_model = $ci->roles_model;
        $controller->accounts = $ci->accounts;
        $controller->output = $ci->output;
        $controller->input = $ci->input;
        $controller->db = $ci->db;
        return $controller;
    }

    /**
     * 1. Test Room Types CRUD and Auto-Seeding.
     */
    public function testRoomTypesCrudAndAutoSeeding(): void
    {
        $types = $this->ci()->hospitality_model->get_room_types(false);
        $this->assertNotEmpty($types, 'Default room types should be auto-seeded if empty');

        // Create new boutique villa room type
        $newTypeId = $this->ci()->hospitality_model->save_room_type([
            'name' => 'Lüks Orman Taş Villa',
            'code' => 'VILLA-STONE',
            'base_capacity_adults' => 4,
            'base_capacity_children' => 2,
            'max_capacity' => 6,
            'base_price_per_night' => 6500.00,
            'rate_plan_default' => 'HB',
            'bed_type' => '2 King + 2 Tekli Yatak',
            'room_size_sqm' => 95,
            'description' => 'Müstakil orman içi taş villa, özel havuzlu ve şömineli.',
            'amenities' => ['wifi', 'ac', 'private_pool', 'fireplace', 'kitchen'],
        ]);

        $this->assertGreaterThan(0, $newTypeId);

        $fetched = $this->ci()->hospitality_model->get_room_type($newTypeId);
        $this->assertNotNull($fetched);
        $this->assertEquals('Lüks Orman Taş Villa', $fetched['name']);
        $this->assertEquals(6500.00, (float) $fetched['base_price_per_night']);
        $this->assertContains('private_pool', $fetched['amenities_list']);

        // Clean up
        $this->ci()->hospitality_model->delete_room_type($newTypeId);
        $this->assertNull($this->ci()->hospitality_model->get_room_type($newTypeId));
    }

    /**
     * 2. Test Dynamic Rate Plans.
     */
    public function testRatePlansAndSeasonalPricing(): void
    {
        $plans = $this->ci()->hospitality_model->get_rate_plans(false);
        $this->assertNotEmpty($plans, 'Default rate plans should be available');

        $planId = $this->ci()->hospitality_model->save_rate_plan([
            'name' => 'Bayram Özel %30 Artırımlı',
            'board_type' => 'AI',
            'price_multiplier' => 1.30,
            'weekend_price_delta' => 500.00,
            'min_stay_nights' => 3,
            'cancellation_policy' => 'İade yapılamaz.',
        ]);

        $this->assertGreaterThan(0, $planId);

        $allPlans = $this->ci()->hospitality_model->get_rate_plans(false);
        $found = false;
        foreach ($allPlans as $p) {
            if ((int) $p['id'] === $planId) {
                $found = true;
                $this->assertEquals(1.30, (float) $p['price_multiplier']);
                $this->assertEquals(3, (int) $p['min_stay_nights']);
                break;
            }
        }
        $this->assertTrue($found);

        // Delete plan
        $this->ci()->hospitality_model->delete_rate_plan($planId);
    }

    /**
     * 3. Test 14-Day Visual Tape Chart Matrix (HotelDruid & QloApps benchmark).
     */
    public function testTapeChartMatrixGeneration(): void
    {
        $startDate = date('Y-m-d');
        $matrix = $this->ci()->hospitality_model->get_tape_chart_matrix($startDate, 14);

        $this->assertIsArray($matrix);
        $this->assertEquals(14, $matrix['days_count']);
        $this->assertCount(14, $matrix['calendar_days']);
        $this->assertNotEmpty($matrix['room_rows']);

        // First day should be today
        $this->assertEquals($startDate, $matrix['calendar_days'][0]['date']);
        $this->assertTrue($matrix['calendar_days'][0]['is_today']);
    }

    /**
     * 4. Test Express Check-In Workflow with Door PIN, Folio, and KBS.
     */
    public function testExpressCheckinWorkflow(): void
    {
        // Pick or create test room
        $station = $this->ci()->db->order_by('id ASC')->get('stations')->row_array();
        if (!$station) {
            $this->ci()->db->insert('stations', ['name' => 'Test Oda 901', 'capacity' => 2, 'status' => 'clean']);
            $roomId = (int) $this->ci()->db->insert_id();
        } else {
            $roomId = (int) $station['id'];
        }

        $checkinData = [
            'room_id' => $roomId,
            'customer_id' => self::$guestUserId,
            'guest_first_name' => 'Ahmet',
            'guest_last_name' => 'Korkmaz',
            'board_type' => 'BB',
            'kbs_id_number' => '12345678901',
            'kbs_id_type' => 'TC',
            'guest_phone' => '05321112233',
            'father_name' => 'Mehmet',
            'birth_date' => '1988-05-15',
            'vehicle_plate' => '34 HOTEL 99',
        ];

        $res = $this->ci()->hospitality_model->express_checkin($checkinData);

        $this->assertTrue($res['success']);
        $this->assertNotEmpty($res['door_pin'], '6-digit smart door PIN must be generated');
        $this->assertGreaterThan(0, $res['appointment_id']);
        $this->assertGreaterThan(0, $res['folio_id']);
        $this->assertGreaterThan(0, $res['kbs_id']);

        // Verify room status changed to occupied
        $updatedRoom = $this->ci()->db->get_where('stations', ['id' => $roomId])->row_array();
        $this->assertEquals('occupied', $updatedRoom['status']);

        // Verify open folio exists
        $folio = $this->ci()->db->get_where('adisyons', ['id' => $res['folio_id']])->row_array();
        $this->assertEquals('open', $folio['status']);
        $this->assertStringStartsWith('FOL-', $folio['adisyon_number']);

        // Verify KBS declaration was recorded
        $kbs = $this->ci()->db->get_where('hospitality_kbs_declarations', ['id' => $res['kbs_id']])->row_array();
        $this->assertEquals('12345678901', $kbs['national_id_or_passport']);
        $this->assertEquals('Ahmet', $kbs['first_name']);
        $this->assertEquals('34 HOTEL 99', $kbs['vehicle_plate']);

        // 5. Test Folio Charge addition
        $chargeRes = $this->ci()->hospitality_model->add_room_charge($roomId, [
            'category' => 'minibar',
            'item_name' => 'Efes Pilsen & Karışık Kuruyemiş',
            'amount' => 180.00,
            'quantity' => 1,
        ]);

        $this->assertTrue($chargeRes['success']);
        $this->assertEquals(180.00, $chargeRes['total_amount']);

        // 6. Test Folio Itemized Summary and Taxes
        $folioSummary = $this->ci()->hospitality_model->get_room_folio($roomId);
        $this->assertNotNull($folioSummary);
        $this->assertEquals(180.00, (float) $folioSummary['subtotal']);
        $this->assertEquals(18.00, (float) $folioSummary['kdv_amount']); // 10% KDV

        // 7. Test Express Check-Out Workflow
        $checkoutRes = $this->ci()->hospitality_model->express_checkout($roomId, [
            'pay_balance' => 1,
            'payment_method' => 'credit_card',
        ]);

        $this->assertTrue($checkoutRes['success']);

        // Verify room status is set to dirty
        $roomAfterCheckout = $this->ci()->db->get_where('stations', ['id' => $roomId])->row_array();
        $this->assertEquals('dirty', $roomAfterCheckout['status']);

        // Verify Housekeeping departure clean task was automatically generated
        $hkTask = $this->ci()->db
            ->where('room_station_id', $roomId)
            ->where('task_type', 'departure_clean')
            ->order_by('id DESC')
            ->get('hospitality_housekeeping_tasks')
            ->row_array();

        $this->assertNotNull($hkTask, 'Housekeeping task must be auto-dispatched on checkout');
        $this->assertEquals('pending', $hkTask['status']);

        // 8. Test Housekeeping Task Completion & Room Auto-Advancement
        $this->ci()->hospitality_model->update_housekeeping_task((int) $hkTask['id'], 'completed');
        $roomAfterCleaning = $this->ci()->db->get_where('stations', ['id' => $roomId])->row_array();
        $this->assertEquals('clean', $roomAfterCleaning['status'], 'Room must auto-advance to clean after HK completion');
    }

    /**
     * 5. Test Maintenance Ticket & Room Blocking.
     */
    public function testMaintenanceTicketAndRoomBlocking(): void
    {
        $station = $this->ci()->db->order_by('id ASC')->get('stations')->row_array();
        $roomId = (int) $station['id'];

        $ticketId = $this->ci()->hospitality_model->create_maintenance_ticket([
            'room_id' => $roomId,
            'issue_category' => 'hvac_ac',
            'title' => 'Klima gazı kaçırıyor, soğutmuyor',
            'priority' => 'critical',
            'block_room' => 1,
        ]);

        $this->assertGreaterThan(0, $ticketId);

        // Room should be in maintenance status
        $room = $this->ci()->db->get_where('stations', ['id' => $roomId])->row_array();
        $this->assertEquals('maintenance', $room['status']);

        // Resolve ticket
        $resolved = $this->ci()->hospitality_model->resolve_maintenance_ticket($ticketId, 'Klima gazı dolduruldu.');
        $this->assertTrue($resolved);

        // Room should transition to dirty for cleaning inspection
        $roomResolved = $this->ci()->db->get_where('stations', ['id' => $roomId])->row_array();
        $this->assertEquals('dirty', $roomResolved['status']);
    }

    /**
     * 6. Test Night Audit Engine, ADR, RevPAR, and %2 Konaklama Vergisi.
     */
    public function testNightAuditEngineAndKpiMetrics(): void
    {
        $auditDate = date('Y-m-d');
        $userId = 1;

        $auditResult = $this->ci()->hospitality_model->run_night_audit($auditDate, $userId, true);

        $this->assertTrue($auditResult['success']);
        $this->assertArrayHasKey('adr', $auditResult);
        $this->assertArrayHasKey('revpar', $auditResult);
        $this->assertArrayHasKey('occupancy_rate', $auditResult);
        $this->assertArrayHasKey('accommodation_tax_total', $auditResult);

        // Verify record in ea_hospitality_night_audits
        $dbAudit = $this->ci()->db->get_where('hospitality_night_audits', ['audit_date' => $auditDate])->row_array();
        $this->assertNotNull($dbAudit);
        $this->assertEquals(1, (int) $dbAudit['is_closed']);

        $history = $this->ci()->hospitality_model->get_night_audit_history(5);
        $this->assertNotEmpty($history);
    }

    /**
     * 7. Test Turkish KBS Police Reporting XML and JSON Export.
     */
    public function testKbsPoliceReportingXmlAndJsonExport(): void
    {
        $station = $this->ci()->db->order_by('id ASC')->get('stations')->row_array();

        $this->ci()->hospitality_model->record_kbs_declaration([
            'room_station_id' => (int) $station['id'],
            'national_id_or_passport' => '98765432109',
            'id_type' => 'TC',
            'first_name' => 'Kemal',
            'last_name' => 'Öztürk',
            'father_name' => 'Ali',
            'mother_name' => 'Fatma',
            'birth_date' => '1990-01-01',
            'birth_place' => 'Antalya',
            'gender' => 'M',
            'nationality_code' => 'TUR',
            'vehicle_plate' => '07 ANT 10',
            'checkin_datetime' => date('Y-m-d H:i:s'),
        ]);

        $records = $this->ci()->hospitality_model->get_kbs_declarations(date('Y-m-d'));
        $this->assertNotEmpty($records);

        $xml = $this->ci()->hospitality_model->export_kbs_xml(date('Y-m-d'));
        $this->assertStringContainsString('<?xml version="1.0"', $xml);
        $this->assertStringContainsString('<KBSBildirimListesi', $xml);
        $this->assertStringContainsString('98765432109', $xml);
        $this->assertStringContainsString('07 ANT 10', $xml);
    }

    /**
     * 8. Test 2-Way OTA iCal Feed Generation.
     */
    public function testTwoWayIcalFeedGeneration(): void
    {
        $station = $this->ci()->db->order_by('id ASC')->get('stations')->row_array();
        $roomId = (int) $station['id'];

        $ics = $this->ci()->hospitality_model->generate_room_ical_feed($roomId);

        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('VERSION:2.0', $ics);
        $this->assertStringContainsString('PRODID:-//BooKi Hospitality PMS', $ics);
        $this->assertStringContainsString('END:VCALENDAR', $ics);
    }

    /**
     * 9. Test Verticals Controller Hospitality Actions.
     */
    public function testVerticalsControllerHospitalityActions(): void
    {
        session(['user_id' => 1, 'role_slug' => 'admin']);

        $controller = $this->createVerticalsController();

        // Test Tape Chart endpoint
        $_GET['days'] = 14;
        $controller->hospitality_tape_chart();
        $chartOutput = self::ci()->output->get_output();

        $chartJson = json_decode($chartOutput, true);
        $this->assertTrue($chartJson['success'] ?? false);
        $this->assertEquals(14, $chartJson['data']['days_count'] ?? 0);
    }

    /**
     * 10. Test Preset Property & Room Blueprint Templates (Boutique, Bungalow, Apart).
     */
    public function testPresetPropertyTemplatesAndFloorBlueprints(): void
    {
        $templates = $this->ci()->hospitality_model->get_property_templates();
        $this->assertArrayHasKey('boutique_hotel', $templates);
        $this->assertArrayHasKey('bungalow_resort', $templates);
        $this->assertArrayHasKey('apart_pension', $templates);

        // Apply Bungalow Resort Template
        $result = $this->ci()->hospitality_model->apply_property_template('bungalow_resort');
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['created_rooms']);

        // Verify rooms overview includes blueprint properties (sqm, bed_type, amenities)
        $rooms = $this->ci()->hospitality_model->get_rooms_overview();
        $this->assertNotEmpty($rooms);
        $hasBungalow = false;
        foreach ($rooms as $r) {
            $this->assertArrayHasKey('room_size_sqm', $r);
            $this->assertArrayHasKey('bed_type', $r);
            $this->assertArrayHasKey('amenities_list', $r);
            if (str_contains($r['name'], 'Bungalov') || str_contains($r['floor'], 'Bungalov')) {
                $hasBungalow = true;
            }
        }
        $this->assertTrue($hasBungalow, 'Bungalow resort template rooms must be in overview');
    }
}
