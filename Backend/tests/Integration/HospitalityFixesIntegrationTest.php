<?php declare(strict_types=1);

namespace Tests\Integration;

use Tests\TenantTestCase;
use RuntimeException;
use InvalidArgumentException;

/**
 * Integration Test for Sector 6 (hospitality - Otel, Butik Otel, Bungalov, Pansiyon - Oda Yönetimi, Housekeeping & Folyo / Oda Hesabı) Backend Fixes.
 * Covers room status updates, room charges & folios, guest preferences, page view mapping, and API authentication/authorization.
 */
class HospitalityFixesIntegrationTest extends TenantTestCase
{
    private static int $staffUserId;
    private static int $guest1UserId;
    private static int $guest2UserId;
    private static int $room1Id;
    private static int $room2Id;
    private static int $serviceId;

    protected function setUp(): void
    {
        parent::setUp();
        self::ci()->load->model('adisyons_model');
        self::ci()->load->model('customers_model');
        self::ci()->load->model('appointments_model');
        self::ci()->load->model('services_model');
        self::ci()->load->model('roles_model');
        self::ci()->load->model('restaurant_model');

        $db = self::db();

        // 1. Staff User (Receptionist / Hotel Manager)
        $staff = $db->get_where('users', ['email' => 'hotel_manager_test@booki.local'])->row_array();
        if (!$staff) {
            $db->insert('users', [
                'first_name' => 'Ahmet',
                'last_name' => 'Mudur',
                'email' => 'hotel_manager_test@booki.local',
                'phone_number' => '05553334411',
                'id_roles' => 1,
                'role_slug' => 'admin',
                'is_active' => 1,
            ]);
            self::$staffUserId = (int) $db->insert_id();
        } else {
            self::$staffUserId = (int) $staff['id'];
        }

        // 2. Guest Users
        self::$guest1UserId = $this->ensureUser('guest_hotel_1@booki.local', 'Bora', 'Misafir', '05554445511', 3, 'customer');
        self::$guest2UserId = $this->ensureUser('guest_hotel_2@booki.local', 'Cansu', 'Yolcu', '05554445522', 3, 'customer');

        // 3. Service (Konaklama / Oda Rezervasyonu)
        $service = $db->get_where('services', ['name' => 'Standart Oda Konaklama'])->row_array();
        if (!$service) {
            $db->insert('services', [
                'name' => 'Standart Oda Konaklama',
                'duration' => 1440,
                'price' => 3500.00,
                'currency' => 'TRY',
            ]);
            self::$serviceId = (int) $db->insert_id();
        } else {
            self::$serviceId = (int) $service['id'];
        }

        // 4. Ensure migration 173 fields exist on stations and adisyons
        if (!$db->field_exists('status', 'stations')) {
            self::ci()->load->dbforge();
            self::ci()->dbforge->add_column('stations', [
                'status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'clean', 'null' => true],
            ]);
        }
        if (!$db->field_exists('capacity', 'stations')) {
            self::ci()->load->dbforge();
            self::ci()->dbforge->add_column('stations', [
                'capacity' => ['type' => 'INT', 'constraint' => 11, 'default' => 2, 'null' => true],
            ]);
        }
        if (!$db->field_exists('id_stations', 'adisyons')) {
            self::ci()->load->dbforge();
            self::ci()->dbforge->add_column('adisyons', [
                'id_stations' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'default' => null],
            ]);
        }

        // 5. Rooms (Stations)
        $room1 = $db->get_where('stations', ['name' => 'Oda 101 Test Suit'])->row_array();
        if (!$room1) {
            $db->insert('stations', [
                'name' => 'Oda 101 Test Suit',
                'notes' => 'Deniz manzaralı balkonlu suit',
                'status' => 'clean',
                'capacity' => 2,
                'is_active' => 1,
            ]);
            self::$room1Id = (int) $db->insert_id();
        } else {
            self::$room1Id = (int) $room1['id'];
            $db->update('stations', ['status' => 'clean', 'capacity' => 2], ['id' => self::$room1Id]);
        }

        $room2 = $db->get_where('stations', ['name' => 'Bungalov 201 Test'])->row_array();
        if (!$room2) {
            $db->insert('stations', [
                'name' => 'Bungalov 201 Test',
                'notes' => 'Doğa manzaralı jakuzili ahşap bungalov',
                'status' => 'clean',
                'capacity' => 4,
                'is_active' => 1,
            ]);
            self::$room2Id = (int) $db->insert_id();
        } else {
            self::$room2Id = (int) $room2['id'];
            $db->update('stations', ['status' => 'clean', 'capacity' => 4], ['id' => self::$room2Id]);
        }
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
        $controller->load->model('adisyons_model');
        $controller->load->model('customers_model');
        $controller->load->model('roles_model');
        $controller->load->model('restaurant_model');
        $controller->load->library('accounts');
        $controller->adisyons_model = $ci->adisyons_model;
        $controller->customers_model = $ci->customers_model;
        $controller->roles_model = $ci->roles_model;
        $controller->accounts = $ci->accounts;
        $controller->output = $ci->output;
        $controller->input = $ci->input;
        $controller->db = $ci->db;
        return $controller;
    }

    private function createVerticalsApiController(): \Verticals_api_v1
    {
        require_once APPPATH . 'controllers/api/v1/Verticals_api_v1.php';
        $ci = self::ci();
        $api = (new \ReflectionClass(\Verticals_api_v1::class))->newInstanceWithoutConstructor();
        $api->props = &$ci->props;
        $api->load = $ci->load;
        $api->load->model('adisyons_model');
        $api->load->model('restaurant_model');
        $api->adisyons_model = $ci->adisyons_model;
        $api->restaurant_model = $ci->restaurant_model;
        $api->output = $ci->output;
        $api->input = $ci->input;
        $api->db = $ci->db;
        return $api;
    }

    /**
     * Test 1: update_room_status transitions rooms through valid statuses and persists in database.
     */
    public function testUpdateRoomStatusSuccessAndPersistence(): void
    {
        $controller = $this->createVerticalsController();
        $db = self::db();

        session(['user_id' => self::$staffUserId, 'role_slug' => 'admin']);

        $statuses = ['dirty', 'cleaning', 'clean', 'maintenance', 'occupied'];
        foreach ($statuses as $targetStatus) {
            $_POST = [
                'room_id' => self::$room1Id,
                'status' => $targetStatus,
            ];

            $controller->update_room_status();
            $res = json_decode(self::ci()->output->get_output(), true);

            $this->assertTrue($res['success'], "Failed setting status to {$targetStatus}");
            $this->assertEquals(self::$room1Id, (int) $res['room_id']);
            $this->assertEquals($targetStatus, $res['status']);

            // Verify in stations table
            $station = $db->get_where('stations', ['id' => self::$room1Id])->row_array();
            $this->assertNotNull($station);
            $this->assertEquals($targetStatus, $station['status']);
        }
    }

    /**
     * Test 2: update_room_status rejects invalid status or non-existent room ID.
     */
    public function testUpdateRoomStatusValidation(): void
    {
        $controller = $this->createVerticalsController();

        session(['user_id' => self::$staffUserId, 'role_slug' => 'admin']);

        // 2.1 Invalid status
        $_POST = [
            'room_id' => self::$room1Id,
            'status' => 'broken_glass',
        ];
        $controller->update_room_status();
        $res = json_decode(self::ci()->output->get_output(), true);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('Geçersiz oda ID veya durum', $res['message']);

        // 2.2 Non-existent room ID
        $_POST = [
            'room_id' => 9999999,
            'status' => 'clean',
        ];
        $controller->update_room_status();
        $resNotFound = json_decode(self::ci()->output->get_output(), true);
        $this->assertFalse($resNotFound['success']);
        $this->assertStringContainsString('Oda bulunamadı', $resNotFound['message']);
    }

    /**
     * Test 3: update_room_status enforces authentication and rejects customer role.
     */
    public function testUpdateRoomStatusAuthGuard(): void
    {
        $controller = $this->createVerticalsController();

        // 3.1 Unauthenticated -> 401
        session(['user_id' => null, 'role_slug' => null]);
        $unauthThrown = false;
        try {
            $controller->update_room_status();
        } catch (\Throwable $e) {
            $unauthThrown = true;
            $this->assertEquals(401, $e->getCode());
        }
        $this->assertTrue($unauthThrown, 'Unauthenticated user must be rejected with 401');

        // 3.2 Customer role -> 403
        session(['user_id' => self::$guest1UserId, 'role_slug' => 'customer']);
        $forbiddenThrown = false;
        try {
            $controller->update_room_status();
        } catch (\Throwable $e) {
            $forbiddenThrown = true;
            $this->assertEquals(403, $e->getCode());
        }
        $this->assertTrue($forbiddenThrown, 'Customer role must be rejected with 403');
    }

    /**
     * Test 4: add_room_charge creates a new folio or attaches charge to existing open folio and updates total_amount.
     */
    public function testAddRoomChargeFindsOrCreatesOpenFolioAndRecalculatesTotal(): void
    {
        $controller = $this->createVerticalsController();
        $db = self::db();

        session(['user_id' => self::$staffUserId, 'role_slug' => 'admin']);

        // Clean up any existing open adisyons for room1 & guest1
        $db->where('id_users_customer', self::$guest1UserId)->delete('adisyons');

        // 4.1 First charge: Minibar (350.00 TL)
        $_POST = [
            'room_id' => self::$room1Id,
            'guest_id' => self::$guest1UserId,
            'item_name' => 'Minibar: 2x Su, 1x Cips, 1x Kola',
            'amount' => 350.00,
            'category' => 'minibar',
        ];
        $controller->add_room_charge();
        $res1 = json_decode(self::ci()->output->get_output(), true);

        $this->assertTrue($res1['success'] ?? false, 'Failed with: ' . json_encode($res1));
        $this->assertGreaterThan(0, (int) $res1['charge_id']);
        $this->assertEquals(350.00, (float) $res1['total_amount']);
        $this->assertGreaterThan(0, (int) $res1['adisyon_id']);
        $firstAdisyonId = (int) $res1['adisyon_id'];

        // Verify adisyon record in DB
        $adisyonRow = $db->get_where('adisyons', ['id' => $firstAdisyonId])->row_array();
        $this->assertNotNull($adisyonRow);
        $this->assertEquals('open', $adisyonRow['status']);
        $this->assertEquals(350.00, (float) $adisyonRow['total_amount']);

        // 4.2 Second charge to SAME room/guest: SPA & Massage (1200.00 TL) -> Should append to existing folio
        $_POST = [
            'room_id' => self::$room1Id,
            'guest_id' => self::$guest1UserId,
            'item_name' => 'Aromaterapi Masajı 50 dk',
            'amount' => 1200.00,
            'category' => 'spa',
        ];
        $controller->add_room_charge();
        $res2 = json_decode(self::ci()->output->get_output(), true);

        $this->assertTrue($res2['success']);
        $this->assertEquals($firstAdisyonId, (int) $res2['adisyon_id'], 'Must reuse existing open folio');
        $this->assertEquals(1550.00, (float) $res2['total_amount'], 'Total must equal 350 + 1200 = 1550');

        // 4.3 Third charge: Transfer (800.00 TL)
        $_POST = [
            'room_id' => self::$room1Id,
            'guest_id' => self::$guest1UserId,
            'item_name' => 'Havalimanı VIP Transfer',
            'amount' => 800.00,
            'category' => 'transfer',
        ];
        $controller->add_room_charge();
        $res3 = json_decode(self::ci()->output->get_output(), true);

        $this->assertTrue($res3['success']);
        $this->assertEquals(2350.00, (float) $res3['total_amount'], 'Total must equal 1550 + 800 = 2350');

        // Verify line items count in adisyon_items
        $items = $db->get_where('adisyon_items', ['id_adisyons' => $firstAdisyonId])->result_array();
        $this->assertCount(3, $items);
    }

    /**
     * Test 5: add_room_charge input validation (missing/invalid amount, empty item name).
     */
    public function testAddRoomChargeValidation(): void
    {
        $controller = $this->createVerticalsController();

        session(['user_id' => self::$staffUserId, 'role_slug' => 'admin']);

        // 5.1 Amount <= 0
        $_POST = [
            'room_id' => self::$room1Id,
            'guest_id' => self::$guest1UserId,
            'item_name' => 'Bedava Kahve',
            'amount' => 0.00,
            'category' => 'restaurant',
        ];
        $controller->add_room_charge();
        $resZero = json_decode(self::ci()->output->get_output(), true);
        $this->assertFalse($resZero['success']);

        // 5.2 Missing item name
        $_POST = [
            'room_id' => self::$room1Id,
            'guest_id' => self::$guest1UserId,
            'item_name' => '',
            'amount' => 150.00,
            'category' => 'extra',
        ];
        $controller->add_room_charge();
        $resEmptyName = json_decode(self::ci()->output->get_output(), true);
        $this->assertFalse($resEmptyName['success']);
    }

    /**
     * Test 6: save_guest_preferences stores preferences into user notes/metadata and restaurant_guest_preferences.
     */
    public function testSaveGuestPreferencesSuccessAndPersistence(): void
    {
        $controller = $this->createVerticalsController();
        $db = self::db();

        session(['user_id' => self::$staffUserId, 'role_slug' => 'admin']);

        $_POST = [
            'customer_id' => self::$guest1UserId,
            'preferences' => [
                'pillow_type' => 'Kuştüyü Yastık',
                'dietary' => 'Laktozsuz & Glutensiz',
                'floor_preference' => 'Üst Kat / Sessiz Cephe',
                'notes' => 'Late check-out talep ediyor (14:00)',
            ],
        ];

        $controller->save_guest_preferences();
        $res = json_decode(self::ci()->output->get_output(), true);

        $this->assertTrue($res['success']);
        $this->assertEquals('Misafir tercihleri kaydedildi.', $res['message']);
        $this->assertEquals('Kuştüyü Yastık', $res['preferences']['pillow_type']);
        $this->assertEquals('Laktozsuz & Glutensiz', $res['preferences']['dietary']);
        $this->assertEquals('Üst Kat / Sessiz Cephe', $res['preferences']['floor_preference']);

        // Check user notes in database
        $user = $db->get_where('users', ['id' => self::$guest1UserId])->row_array();
        $this->assertNotEmpty($user['notes']);
        $meta = json_decode($user['notes'], true);
        $this->assertIsArray($meta);
        $this->assertEquals('Kuştüyü Yastık', $meta['pillow_type']);
        $this->assertEquals('Laktozsuz & Glutensiz', $meta['dietary']);
        $this->assertEquals('Üst Kat / Sessiz Cephe', $meta['floor_preference']);
        $this->assertEquals('Late check-out talep ediyor (14:00)', $meta['notes']);

        // Check restaurant_guest_preferences table sync
        if ($db->table_exists('restaurant_guest_preferences')) {
            $prefRow = $db->get_where('restaurant_guest_preferences', ['id_users_customer' => self::$guest1UserId])->row_array();
            $this->assertNotNull($prefRow);
            $this->assertEquals('Üst Kat / Sessiz Cephe', $prefRow['seating_preference']);
            $this->assertEquals('Laktozsuz & Glutensiz', $prefRow['dietary_restrictions']);
        }
    }

    /**
     * Test 7: hospitality page view loads active guest check-ins, maps occupied rooms, and exposes folios.
     */
    public function testHospitalityPageViewActiveGuestAndRoomMapping(): void
    {
        $controller = $this->createVerticalsController();
        $db = self::db();

        session(['user_id' => self::$staffUserId, 'role_slug' => 'admin']);

        // Create an active check-in appointment for room 1 starting today and ending tomorrow
        $todayStart = date('Y-m-d 10:00:00');
        $tomorrowEnd = date('Y-m-d 12:00:00', strtotime('+1 day'));

        $db->insert('appointments', [
            'start_datetime' => $todayStart,
            'end_datetime' => $tomorrowEnd,
            'is_unavailability' => 0,
            'status' => 'confirmed',
            'id_users_customer' => self::$guest1UserId,
            'id_users_provider' => self::$staffUserId,
            'id_services' => self::$serviceId,
            'id_stations' => self::$room1Id,
        ]);
        $apptId = (int) $db->insert_id();

        // Create an open adisyon for this appointment
        $db->insert('adisyons', [
            'adisyon_number' => 'FOL-ACTIVE-TEST',
            'id_users_customer' => self::$guest1UserId,
            'id_appointments' => $apptId,
            'status' => 'open',
            'payment_status' => 'unpaid',
            'invoice_status' => 'uninvoiced',
            'subtotal' => 3500.00,
            'tax_amount' => 700.00,
            'total_amount' => 3500.00,
            'paid_amount' => 0.00,
            'opened_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $adisyonId = (int) $db->insert_id();

        // Call hospitality page action
        $controller->hospitality();
        $output = self::ci()->output->get_output();

        // Verify HTML page was rendered
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Kat Hizmetleri', $output);
        $this->assertStringContainsString('Oda 101 Test Suit', $output);
        $this->assertStringContainsString('Bora Misafir', $output);

        // Cleanup
        $db->where('id', $apptId)->delete('appointments');
        $db->where('id', $adisyonId)->delete('adisyons');
    }

    /**
     * Test 8: Verticals_api_v1 endpoints (update_room_status, add_room_charge, save_guest_preferences).
     */
    public function testVerticalsApiV1HospitalityEndpoints(): void
    {
        $api = $this->createVerticalsApiController();
        $db = self::db();

        // 8.1 API update_room_status: Unauthenticated -> 401
        session(['user_id' => null, 'role_slug' => null]);
        $_POST = ['room_id' => self::$room2Id, 'status' => 'dirty'];
        $api->update_room_status();
        $api401 = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('error', $api401);
        $this->assertEquals('Kimlik doğrulama gereklidir.', $api401['error']);

        // 8.2 API update_room_status: Customer role -> 403
        session(['user_id' => self::$guest1UserId, 'role_slug' => 'customer']);
        $api->update_room_status();
        $api403 = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('error', $api403);
        $this->assertStringContainsString('yetkiniz bulunmamaktadır', $api403['error']);

        // 8.3 API update_room_status: Authenticated staff -> Success
        session(['user_id' => self::$staffUserId, 'role_slug' => 'admin']);
        $_POST = ['room_id' => self::$room2Id, 'status' => 'maintenance'];
        $api->update_room_status();
        $apiSuccess = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($apiSuccess['success']);
        $this->assertEquals('maintenance', $apiSuccess['status']);
        $st = $db->get_where('stations', ['id' => self::$room2Id])->row_array();
        $this->assertEquals('maintenance', $st['status']);

        // 8.4 API add_room_charge: Staff adds charge
        $_POST = [
            'room_id' => self::$room2Id,
            'guest_id' => self::$guest2UserId,
            'item_name' => 'Özel Restoran Akşam Yemeği',
            'amount' => 1450.00,
            'category' => 'restaurant',
        ];
        $api->add_room_charge();
        $apiCharge = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($apiCharge['success']);
        $this->assertEquals(1450.00, (float) $apiCharge['total_amount']);
        $this->assertEquals('restaurant', $apiCharge['category']);

        // 8.5 API save_guest_preferences: Customer cannot edit other customer's preferences -> 403
        session(['user_id' => self::$guest1UserId, 'role_slug' => 'customer']);
        $_POST = [
            'pillow_type' => 'Kaz Tüyü',
            'dietary' => 'Vegan',
        ];
        $api->save_guest_preferences(self::$guest2UserId);
        $apiCrossEdit = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('error', $apiCrossEdit);
        $this->assertStringContainsString('yetkiniz yoktur', $apiCrossEdit['error']);

        // 8.6 API save_guest_preferences: Customer CAN edit their own preferences -> 200
        $_POST = [
            'pillow_type' => 'Lateks Ortopedik',
            'floor_preference' => 'Giriş Kat',
            'dietary' => 'Glutensiz',
            'notes' => 'Sessiz oda tercih edilir',
        ];
        $api->save_guest_preferences(self::$guest1UserId);
        $apiOwnEdit = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($apiOwnEdit['success']);
        $this->assertEquals('Lateks Ortopedik', $apiOwnEdit['preferences']['pillow_type']);
        $this->assertEquals('Giriş Kat', $apiOwnEdit['preferences']['floor_preference']);
    }
}
