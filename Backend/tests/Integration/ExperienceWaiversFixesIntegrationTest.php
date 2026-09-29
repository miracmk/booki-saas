<?php declare(strict_types=1);

namespace Tests\Integration;

use Tests\TenantTestCase;
use RuntimeException;
use InvalidArgumentException;

/**
 * Integration Test for Sector 7 (experience - Kaçış Evi, VR, Etkinlik & Deneyimler, Feragatname & Biletleme) Backend Fixes.
 * Covers Digital_waivers_model, Event_tickets_model, Verticals controller web endpoints,
 * and Verticals_api_v1 API role-based authorization & lifecycle.
 */
class ExperienceWaiversFixesIntegrationTest extends TenantTestCase
{
    private static int $staffUserId;
    private static int $customerUserId;
    private static int $otherCustomerUserId;
    private static int $appointmentId;
    private static int $serviceId;

    protected function setUp(): void
    {
        parent::setUp();
        self::ci()->load->model('digital_waivers_model');
        self::ci()->load->model('event_tickets_model');
        self::ci()->load->model('customers_model');
        self::ci()->load->model('appointments_model');
        self::ci()->load->model('services_model');

        $db = self::db();

        // 1. Staff / Game Master User
        $staff = $db->get_where('users', ['email' => 'escape_staff_test@booki.local'])->row_array();
        if (!$staff) {
            $db->insert('users', [
                'first_name' => 'Can',
                'last_name' => 'GameMaster',
                'email' => 'escape_staff_test@booki.local',
                'phone_number' => '05551112233',
                'id_roles' => 2,
                'role_slug' => 'provider',
                'is_active' => 1,
            ]);
            self::$staffUserId = (int) $db->insert_id();
        } else {
            self::$staffUserId = (int) $staff['id'];
        }

        // 2. Customer User
        $customer = $db->get_where('users', ['email' => 'escape_player_test@booki.local'])->row_array();
        if (!$customer) {
            $db->insert('users', [
                'first_name' => 'Elif',
                'last_name' => 'KacisOyuncusu',
                'email' => 'escape_player_test@booki.local',
                'phone_number' => '05559998877',
                'id_roles' => 3,
                'role_slug' => 'customer',
                'is_active' => 1,
            ]);
            self::$customerUserId = (int) $db->insert_id();
        } else {
            self::$customerUserId = (int) $customer['id'];
        }

        // 3. Other Customer
        $otherCustomer = $db->get_where('users', ['email' => 'other_player_test@booki.local'])->row_array();
        if (!$otherCustomer) {
            $db->insert('users', [
                'first_name' => 'Murat',
                'last_name' => 'DigerOyuncu',
                'email' => 'other_player_test@booki.local',
                'phone_number' => '05557776655',
                'id_roles' => 3,
                'role_slug' => 'customer',
                'is_active' => 1,
            ]);
            self::$otherCustomerUserId = (int) $db->insert_id();
        } else {
            self::$otherCustomerUserId = (int) $otherCustomer['id'];
        }

        // 4. Service
        $service = $db->get_where('services', ['name' => 'Kaçış Odası: Zindan Deneyimi'])->row_array();
        if (!$service) {
            $db->insert('services', [
                'name' => 'Kaçış Odası: Zindan Deneyimi',
                'duration' => 60,
                'price' => 500.00,
                'currency' => 'TL',
                'description' => '60 dakikalık gerilim ve bulmaca deneyimi',
            ]);
            self::$serviceId = (int) $db->insert_id();
        } else {
            self::$serviceId = (int) $service['id'];
        }

        // 5. Appointment
        $appt = $db->get_where('appointments', ['notes' => 'TEST_EXPERIENCE_APPT'])->row_array();
        if (!$appt) {
            $start = date('Y-m-d 15:00:00', strtotime('+1 day'));
            $end = date('Y-m-d 16:00:00', strtotime('+1 day'));
            $db->insert('appointments', [
                'start_datetime' => $start,
                'end_datetime' => $end,
                'is_unavailability' => 0,
                'id_services' => self::$serviceId,
                'id_users_provider' => self::$staffUserId,
                'id_users_customer' => self::$customerUserId,
                'notes' => 'TEST_EXPERIENCE_APPT',
                'status' => 'confirmed',
            ]);
            self::$appointmentId = (int) $db->insert_id();
        } else {
            self::$appointmentId = (int) $appt['id'];
        }
    }

    private function createVerticalsController(): \Verticals
    {
        require_once APPPATH . 'controllers/Verticals.php';
        $ci = self::ci();
        $controller = (new \ReflectionClass(\Verticals::class))->newInstanceWithoutConstructor();
        $controller->props = &$ci->props;
        $controller->load = $ci->load;
        $controller->load->model('digital_waivers_model');
        $controller->load->model('event_tickets_model');
        $controller->load->model('customers_model');
        $controller->load->model('roles_model');
        $controller->digital_waivers_model = $ci->digital_waivers_model;
        $controller->event_tickets_model = $ci->event_tickets_model;
        $controller->customers_model = $ci->customers_model;
        $controller->roles_model = $ci->roles_model;
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
        $api->load->model('digital_waivers_model');
        $api->load->model('event_tickets_model');
        $api->digital_waivers_model = $ci->digital_waivers_model;
        $api->event_tickets_model = $ci->event_tickets_model;
        $api->output = $ci->output;
        $api->input = $ci->input;
        $api->db = $ci->db;
        return $api;
    }

    /**
     * Test 1: Verify Digital_waivers_model CRUD operations and input validation.
     */
    public function testDigitalWaiversModelCrudAndValidation(): void
    {
        $model = self::ci()->digital_waivers_model;
        $db = self::db();

        // 1. Validation failure: missing title
        $this->expectException(InvalidArgumentException::class);
        $model->save_waiver([
            'title' => '',
            'content_html' => '<p>Feragatname içeriği</p>',
        ]);
    }

    public function testDigitalWaiversModelSaveAndUpdate(): void
    {
        $model = self::ci()->digital_waivers_model;
        $db = self::db();

        // 1. Create new waiver
        $waiverId = $model->save_waiver([
            'title' => 'VR Kaçış Oyunu Güvenlik & Feragatname Formu',
            'content_html' => '<p>Epilepsi, kalp rahatsızlığı vb. durumları kabul ediyorum.</p>',
            'is_mandatory' => 1,
            'applicable_service_ids' => [self::$serviceId],
        ]);

        $this->assertGreaterThan(0, $waiverId);

        $row = $db->get_where('digital_waivers', ['id' => $waiverId])->row_array();
        $this->assertNotNull($row);
        $this->assertSame('VR Kaçış Oyunu Güvenlik & Feragatname Formu', $row['title']);
        $this->assertEquals(1, (int) $row['is_mandatory']);
        $this->assertStringContainsString((string) self::$serviceId, $row['applicable_service_ids']);

        // 2. Update existing waiver
        $updatedId = $model->save_waiver([
            'id' => $waiverId,
            'title' => 'Güncellenmiş VR Kaçış Oyunu Güvenlik Formu',
            'content_html' => '<p>Güncellenmiş kurallar ve onay maddeleri.</p>',
            'is_mandatory' => 1,
        ]);

        $this->assertEquals($waiverId, $updatedId);

        $updatedRow = $db->get_where('digital_waivers', ['id' => $waiverId])->row_array();
        $this->assertSame('Güncellenmiş VR Kaçış Oyunu Güvenlik Formu', $updatedRow['title']);

        // Clean up
        $db->where('id', $waiverId)->delete('digital_waivers');
    }

    /**
     * Test 2: Verify Digital_waivers_model digital signing and booking addons.
     */
    public function testDigitalWaiversModelSigningAndAddons(): void
    {
        $model = self::ci()->digital_waivers_model;
        $db = self::db();

        $waiverId = $model->save_waiver([
            'title' => 'Lazer Tag Sorumluluk Feragatnamesi',
            'content_html' => '<p>Fiziksel aktivitelerden doğabilecek yaralanma sorumluluğu kabul edilir.</p>',
            'is_mandatory' => 1,
        ]);

        // 1. Digital signing
        $sigId = $model->sign_waiver([
            'id_waivers' => $waiverId,
            'id_appointments' => self::$appointmentId,
            'id_users_customer' => self::$customerUserId,
            'signer_full_name' => 'Elif KacisOyuncusu',
            'signer_email' => 'elif@test.com',
            'signer_phone' => '05559998877',
            'signature_data' => 'DATA:IMAGE/PNG;BASE64,ABCDEF123456',
            'ip_address' => '192.168.1.100',
        ]);

        $this->assertGreaterThan(0, $sigId);

        $sigRow = $db->get_where('waiver_signatures', ['id' => $sigId])->row_array();
        $this->assertNotNull($sigRow);
        $this->assertSame('Elif KacisOyuncusu', $sigRow['signer_full_name']);
        $this->assertSame('192.168.1.100', $sigRow['ip_address']);

        // 2. Check is_waiver_signed
        $isSigned = $model->is_waiver_signed($waiverId, self::$appointmentId);
        $this->assertTrue($isSigned);

        $notSigned = $model->is_waiver_signed($waiverId, 999999);
        $this->assertFalse($notSigned);

        // 3. Addon attachment
        $addonId = $model->add_booking_addon(self::$appointmentId, [
            'name' => 'Ekstra VR Başlığı & Sensör Paketi',
            'quantity' => 2,
            'unit_price' => 150.00,
        ]);
        $this->assertGreaterThan(0, $addonId);

        $addons = $model->get_booking_addons(self::$appointmentId);
        $this->assertNotEmpty($addons);
        $this->assertSame('Ekstra VR Başlığı & Sensör Paketi', $addons[0]['name']);
        $this->assertEquals(300.00, (float) $addons[0]['total_price']);

        // Clean up
        $db->where('id', $sigId)->delete('waiver_signatures');
        $db->where('id', $addonId)->delete('appointment_addons');
        $db->where('id', $waiverId)->delete('digital_waivers');
    }

    /**
     * Test 3: Verify Event_tickets_model issuance, unique collision-resistant code generation, and validation.
     */
    public function testEventTicketsModelIssuanceAndValidation(): void
    {
        $model = self::ci()->event_tickets_model;
        $db = self::db();

        // 1. Issue Ticket
        $ticket = $model->issue_ticket(
            self::$appointmentId,
            self::$customerUserId,
            'VIP-Ön Sıra',
            'vip',
            450.00
        );

        $this->assertNotEmpty($ticket['id']);
        $this->assertNotEmpty($ticket['ticket_code']);
        $this->assertStringStartsWith('TKT-', $ticket['ticket_code']);
        $this->assertSame('valid', $ticket['status']);
        $this->assertNull($ticket['used_at']);
        $this->assertEquals(450.00, (float) $ticket['price']);

        $ticketCode = $ticket['ticket_code'];

        // 2. Validate ticket - 1st scan (Valid & Burn)
        $valResult = $model->validate_ticket($ticketCode);
        $this->assertTrue($valResult['valid']);
        $this->assertSame('Bilet geçerli! Giriş onaylandı.', $valResult['message']);
        $this->assertSame('used', $valResult['ticket']['status']);
        $this->assertNotNull($valResult['ticket']['used_at']);

        // Check DB row updated
        $freshRow = $db->get_where('event_tickets', ['id' => $ticket['id']])->row_array();
        $this->assertSame('used', $freshRow['status']);
        $this->assertNotNull($freshRow['used_at']);

        // 3. Validate ticket - 2nd scan (Already used rejection)
        $secondResult = $model->validate_ticket($ticketCode);
        $this->assertFalse($secondResult['valid']);
        $this->assertSame('Bu bilet daha önce kullanılmış.', $secondResult['message']);

        // 4. Validate non-existent ticket
        $invalidResult = $model->validate_ticket('TKT-NON-EXISTENT-CODE');
        $this->assertFalse($invalidResult['valid']);
        $this->assertSame('Geçersiz bilet kodu.', $invalidResult['message']);

        // Clean up
        $db->where('id', $ticket['id'])->delete('event_tickets');
    }

    /**
     * Test 4: Verify Verticals web controller auth enforcement (ensure_authenticated).
     */
    public function testVerticalsWebControllerAuthEnforcement(): void
    {
        $controller = $this->createVerticalsController();

        // 4.1 Unauthenticated access throws 401 RuntimeException
        session(['user_id' => null, 'role_slug' => null]);
        $endpoints = ['save_digital_waiver', 'sign_digital_waiver', 'issue_event_ticket', 'validate_event_ticket'];
        foreach ($endpoints as $ep) {
            $unauthThrown = false;
            try {
                $controller->{$ep}();
            } catch (\Throwable $e) {
                $unauthThrown = true;
                $this->assertEquals(401, $e->getCode(), "Endpoint $ep must throw 401 when unauthenticated");
            }
            $this->assertTrue($unauthThrown, "Endpoint $ep should have thrown 401");
        }

        // 4.2 Customer role throws 403 Forbidden on management endpoints
        session(['user_id' => self::$customerUserId, 'role_slug' => 'customer']);
        $restrictedEndpoints = ['save_digital_waiver', 'issue_event_ticket'];
        foreach ($restrictedEndpoints as $ep) {
            $forbiddenThrown = false;
            try {
                $controller->{$ep}();
            } catch (\Throwable $e) {
                $forbiddenThrown = true;
                $this->assertEquals(403, $e->getCode(), "Endpoint $ep must throw 403 for customer");
            }
            $this->assertTrue($forbiddenThrown, "Endpoint $ep should have thrown 403");
        }
    }

    /**
     * Test 5: Verify Verticals web controller waiver creation and signing endpoints.
     */
    public function testVerticalsWebControllerSaveAndSignDigitalWaiver(): void
    {
        $controller = $this->createVerticalsController();
        $db = self::db();

        // Login as Staff / Provider
        session(['user_id' => self::$staffUserId, 'role_slug' => 'provider']);

        // 5.1 Validation error on missing title / content
        $_POST = [];
        $controller->save_digital_waiver();
        $valFailResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertFalse($valFailResp['success']);
        $this->assertStringContainsString('zorunludur', $valFailResp['message']);

        // 5.2 Successful waiver save
        $_POST = [
            'title' => 'Web Formu: VR Korku Evi Feragatnamesi',
            'content_html' => '<p>Korku evi içi fiziksel ve görsel efektleri kabul ediyorum.</p>',
            'is_mandatory' => 1,
        ];
        $controller->save_digital_waiver();
        $saveResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($saveResp['success']);
        $this->assertGreaterThan(0, $saveResp['waiver_id']);
        $savedWaiverId = (int) $saveResp['waiver_id'];

        // 5.3 Validation error on sign_digital_waiver (missing signer_full_name)
        $_POST = [
            'id_waivers' => $savedWaiverId,
            'signer_full_name' => '',
        ];
        $controller->sign_digital_waiver();
        $signFailResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertFalse($signFailResp['success']);

        // 5.4 Successful digital signing
        $_POST = [
            'id_waivers' => $savedWaiverId,
            'id_appointments' => self::$appointmentId,
            'signer_full_name' => 'Caner Oyuncu',
            'signer_phone' => '05553332211',
            'signer_email' => 'caner@test.local',
            'signature_data' => 'BASE64_SIG_WEB_CONTROLLER',
        ];
        $controller->sign_digital_waiver();
        $signResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($signResp['success']);
        $this->assertGreaterThan(0, $signResp['signature_id']);
        $sigId = (int) $signResp['signature_id'];

        // Check signature persisted with IP
        $sigRow = $db->get_where('waiver_signatures', ['id' => $sigId])->row_array();
        $this->assertNotNull($sigRow);
        $this->assertSame('Caner Oyuncu', $sigRow['signer_full_name']);
        $this->assertNotEmpty($sigRow['ip_address']);

        // Clean up
        $db->where('id', $sigId)->delete('waiver_signatures');
        $db->where('id', $savedWaiverId)->delete('digital_waivers');
    }

    /**
     * Test 6: Verify Verticals web controller ticket issuance and ticket validation / redemption.
     */
    public function testVerticalsWebControllerIssueAndValidateTicket(): void
    {
        $controller = $this->createVerticalsController();
        $db = self::db();

        // Login as Staff / Provider
        session(['user_id' => self::$staffUserId, 'role_slug' => 'provider']);

        // 6.1 Validation error: missing customer
        $_POST = ['ticket_type' => 'vip'];
        $controller->issue_event_ticket();
        $issueFailResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertFalse($issueFailResp['success']);
        $this->assertStringContainsString('zorunludur', $issueFailResp['message']);

        // 6.2 Successful ticket issuance
        $_POST = [
            'id_appointments' => self::$appointmentId,
            'id_users_customer' => self::$customerUserId,
            'ticket_type' => 'vip',
            'price' => 350.00,
        ];
        $controller->issue_event_ticket();
        $issueResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($issueResp['success']);
        $this->assertGreaterThan(0, $issueResp['ticket_id']);
        $this->assertNotEmpty($issueResp['ticket_code']);
        $this->assertStringStartsWith('TKT-', $issueResp['ticket_code']);

        $ticketCode = $issueResp['ticket_code'];
        $ticketId = (int) $issueResp['ticket_id'];

        // 6.3 Validate ticket: 1st scan (Valid entry)
        $_POST = ['ticket_code' => $ticketCode];
        $controller->validate_event_ticket();
        $val1Resp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($val1Resp['valid']);
        $this->assertSame('Bilet geçerli! Giriş onaylandı.', $val1Resp['message']);
        $this->assertSame('used', $val1Resp['ticket']['status']);

        // 6.4 Validate ticket: 2nd scan (Already used rejection)
        $_POST = ['ticket_code' => $ticketCode];
        $controller->validate_event_ticket();
        $val2Resp = json_decode(self::ci()->output->get_output(), true);
        $this->assertFalse($val2Resp['valid']);
        $this->assertSame('Bu bilet daha önce kullanılmış.', $val2Resp['message']);

        // 6.5 Validate ticket: Invalid code
        $_POST = ['ticket_code' => 'TKT-INVALID-CODE-XYZ'];
        $controller->validate_event_ticket();
        $val3Resp = json_decode(self::ci()->output->get_output(), true);
        $this->assertFalse($val3Resp['valid']);
        $this->assertSame('Geçersiz bilet kodu.', $val3Resp['message']);

        // Clean up
        $db->where('id', $ticketId)->delete('event_tickets');
    }

    /**
     * Test 7: Verify Verticals_api_v1 API role-based authorization and input validation.
     */
    public function testVerticalsApiControllerRoleBasedAuthAndEndpoints(): void
    {
        $api = $this->createVerticalsApiController();
        $db = self::db();

        // 7.1 Unauthenticated requests return 401
        session(['user_id' => null, 'role_slug' => null]);
        $_POST = [];
        $_GET = [];

        $api->save_digital_waiver();
        $unauthResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('error', $unauthResp);
        $this->assertStringContainsString('Kimlik doğrulama gereklidir', $unauthResp['error']);

        // 7.2 Customer role checks (Customer is forbidden from saving waiver template & issuing/validating tickets)
        session(['user_id' => self::$customerUserId, 'role_slug' => 'customer']);

        $api->save_digital_waiver();
        $forbidResp1 = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('error', $forbidResp1);
        $this->assertStringContainsString('yetkiniz bulunmamaktadır', $forbidResp1['error']);

        $api->issue_event_ticket();
        $forbidResp2 = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('error', $forbidResp2);
        $this->assertStringContainsString('yetkiniz bulunmamaktadır', $forbidResp2['error']);

        $api->validate_event_ticket();
        $forbidResp3 = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('error', $forbidResp3);
        $this->assertStringContainsString('yetkiniz bulunmamaktadır', $forbidResp3['error']);

        // 7.3 Customer CAN digitally sign a waiver
        $model = self::ci()->digital_waivers_model;
        $waiverId = $model->save_waiver([
            'title' => 'API Test Feragatnamesi',
            'content_html' => '<p>API üzerinden imzalanacak metin.</p>',
            'is_mandatory' => 1,
        ]);

        $_POST = [
            'id_waivers' => $waiverId,
            'signer_full_name' => 'Elif KacisOyuncusu',
            'signer_email' => 'elif@api-test.com',
            'signature_data' => 'BASE64_SIG_FROM_CUSTOMER',
        ];
        $api->sign_digital_waiver();
        $custSignResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($custSignResp['success']);
        $this->assertGreaterThan(0, $custSignResp['signature_id']);
        $apiSigId = (int) $custSignResp['signature_id'];

        // Check user id was assigned from session
        $apiSigRow = $db->get_where('waiver_signatures', ['id' => $apiSigId])->row_array();
        $this->assertEquals(self::$customerUserId, (int) $apiSigRow['id_users_customer']);

        // 7.4 Staff/Provider creates waiver via API
        session(['user_id' => self::$staffUserId, 'role_slug' => 'provider']);

        $_POST = [
            'title' => 'API Kaçış Odası Şablonu',
            'content_html' => '<p>Kurallar ve onaylar.</p>',
            'is_mandatory' => 1,
        ];
        $api->save_digital_waiver();
        $apiWaiverResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($apiWaiverResp['success']);
        $apiWaiverId = (int) $apiWaiverResp['waiver_id'];

        // 7.5 Staff/Provider issues event ticket via API
        $_POST = [
            'id_appointments' => self::$appointmentId,
            'id_users_customer' => self::$customerUserId,
            'ticket_type' => 'vip',
            'price' => 250.00,
        ];
        $api->issue_event_ticket();
        $apiTicketResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($apiTicketResp['success']);
        $this->assertNotEmpty($apiTicketResp['ticket_code']);
        $apiTicketCode = $apiTicketResp['ticket_code'];
        $apiTicketId = (int) $apiTicketResp['ticket_id'];

        // 7.6 Staff/Provider validates event ticket via API
        $_POST = ['ticket_code' => $apiTicketCode];
        $api->validate_event_ticket();
        $apiValResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($apiValResp['valid']);
        $this->assertSame('Bilet geçerli! Giriş onaylandı.', $apiValResp['message']);

        // Clean up
        $db->where('id', $apiSigId)->delete('waiver_signatures');
        $db->where('id', $waiverId)->delete('digital_waivers');
        $db->where('id', $apiWaiverId)->delete('digital_waivers');
        $db->where('id', $apiTicketId)->delete('event_tickets');
    }

    /**
     * Test 8: Verify Verticals::experience loads active customers and appointments.
     */
    public function testVerticalsExperienceViewLoadsActiveCustomersAndAppointments(): void
    {
        $controller = $this->createVerticalsController();

        // Login as Staff / Provider
        session(['user_id' => self::$staffUserId, 'role_slug' => 'provider']);

        // Call experience method and verify view parameters
        $controller->experience();
        $output = self::ci()->output->get_output();

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Deneyimler, Dijital Feragatname & Biletleme', $output);
        $this->assertStringContainsString('Yeni Feragatname Şablonu', $output);
        $this->assertStringContainsString('Kapı QR Bilet Doğrulama', $output);
        $this->assertStringContainsString('Bilet Kes / Üret', $output);
    }
}
