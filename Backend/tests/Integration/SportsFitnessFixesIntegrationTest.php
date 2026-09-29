<?php declare(strict_types=1);

namespace Tests\Integration;

use Tests\TenantTestCase;
use RuntimeException;
use InvalidArgumentException;

/**
 * Integration Test for Sector 4 (sports_fitness - Spor Salonu, Fitness, PT, Halı Saha, Kortlar & Turnike) Backend Fixes.
 * Covers Sports_matches_model, Verticals controller web endpoints,
 * Verticals_api_v1 API authentication and atomic matchmaking concurrency protection.
 */
class SportsFitnessFixesIntegrationTest extends TenantTestCase
{
    private static int $providerId;
    private static int $player1Id;
    private static int $player2Id;
    private static int $player3Id;
    private static int $player4Id;
    private static int $memberCustomerId;
    private static int $appointmentCustomerId;
    private static int $stationId;

    protected function setUp(): void
    {
        parent::setUp();
        self::ci()->load->model('sports_matches_model');
        self::ci()->load->model('customers_model');
        self::ci()->load->model('appointments_model');
        self::ci()->load->model('checkin_model');

        $db = self::db();

        // 1. Provider User (Coach / Trainer / Staff)
        $provider = $db->get_where('users', ['email' => 'coach_pt_test@booki.local'])->row_array();
        if (!$provider) {
            $db->insert('users', [
                'first_name' => 'Burak',
                'last_name' => 'Antrenor',
                'email' => 'coach_pt_test@booki.local',
                'phone_number' => '05551113344',
                'id_roles' => 2,
            ]);
            self::$providerId = (int) $db->insert_id();
        } else {
            self::$providerId = (int) $provider['id'];
        }

        // 2. Customer Players
        self::$player1Id = $this->ensureCustomer('padel_p1_test@booki.local', 'Ali', 'Padelci', '05552223301');
        self::$player2Id = $this->ensureCustomer('padel_p2_test@booki.local', 'Berk', 'Tenisci', '05552223302');
        self::$player3Id = $this->ensureCustomer('padel_p3_test@booki.local', 'Cem', 'Oyuncu', '05552223303');
        self::$player4Id = $this->ensureCustomer('padel_p4_test@booki.local', 'Deniz', 'Futbolcu', '05552223304');
        self::$memberCustomerId = $this->ensureCustomer('gym_member_test@booki.local', 'Mert', 'FitnessUye', '05552223305');
        self::$appointmentCustomerId = $this->ensureCustomer('court_res_test@booki.local', 'Efe', 'KortRezervasyon', '05552223306');

        // 3. Station (Court)
        $station = $db->get_where('stations', ['name' => 'Kort 1 Padel Test'])->row_array();
        if (!$station) {
            $db->insert('stations', [
                'name' => 'Kort 1 Padel Test',
                'is_active' => 1,
            ]);
            self::$stationId = (int) $db->insert_id();
        } else {
            self::$stationId = (int) $station['id'];
        }
    }

    private function ensureCustomer(string $email, string $firstName, string $lastName, string $phone): int
    {
        $db = self::db();
        $user = $db->get_where('users', ['email' => $email])->row_array();
        if (!$user) {
            $db->insert('users', [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone_number' => $phone,
                'id_roles' => 3,
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
        $controller->load->model('sports_matches_model');
        $controller->load->model('customers_model');
        $controller->load->model('roles_model');
        $controller->sports_matches_model = $ci->sports_matches_model;
        $controller->customers_model = $ci->customers_model;
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
        $api->load->model('sports_matches_model');
        $api->sports_matches_model = $ci->sports_matches_model;
        $api->output = $ci->output;
        $api->input = $ci->input;
        $api->db = $ci->db;
        return $api;
    }

    /**
     * Test 1: Verify create_match respects max_players (custom & defaults & clamp) and created_by_user_id.
     */
    public function testMatchCreationDefaultsAndCustomMaxPlayers(): void
    {
        $model = self::ci()->sports_matches_model;
        $db = self::db();

        $start = date('Y-m-d 18:00:00', strtotime('+2 days'));
        $end = date('Y-m-d 19:30:00', strtotime('+2 days'));

        // 1.1 Custom max_players = 6 & created_by_user_id specified
        $matchId = $model->create_match([
            'title' => 'Padel 3v3 Dostluk Maçı',
            'id_stations' => self::$stationId,
            'start_datetime' => $start,
            'end_datetime' => $end,
            'sport_type' => 'padel',
            'max_players' => 6,
            'created_by_user_id' => self::$player1Id,
            'price_per_player' => 150.00,
            'creator_skill_level' => '3.5',
        ]);

        $this->assertGreaterThan(0, $matchId);

        $row = $db->get_where('sports_court_matches', ['id' => $matchId])->row_array();
        $this->assertNotNull($row);
        $this->assertEquals(6, (int) $row['max_players']);
        $this->assertEquals(1, (int) $row['current_players']);
        $this->assertEquals('open', $row['status']);
        $this->assertEquals(self::$player1Id, (int) $row['created_by_user_id']);

        // Check creator is in sports_match_participants
        $participants = $db->get_where('sports_match_participants', ['id_matches' => $matchId])->result_array();
        $this->assertCount(1, $participants);
        $this->assertEquals(self::$player1Id, (int) $participants[0]['id_users_customer']);
        $this->assertEquals('paid', $participants[0]['payment_status']);
        $this->assertEquals('3.5', $participants[0]['skill_level']);

        // 1.2 Default max_players when omitted -> defaults to 4
        $defaultMatchId = $model->create_match([
            'title' => 'Default Padel Çiftler',
            'start_datetime' => $start,
            'end_datetime' => $end,
            'created_by_user_id' => self::$player1Id,
        ]);
        $defaultRow = $db->get_where('sports_court_matches', ['id' => $defaultMatchId])->row_array();
        $this->assertEquals(4, (int) $defaultRow['max_players']);

        // 1.3 Boundary Clamping: min 2, max 50
        $minClampedId = $model->create_match([
            'title' => 'Min Clamped Match',
            'start_datetime' => $start,
            'end_datetime' => $end,
            'max_players' => 1,
            'created_by_user_id' => self::$player1Id,
        ]);
        $minRow = $db->get_where('sports_court_matches', ['id' => $minClampedId])->row_array();
        $this->assertEquals(2, (int) $minRow['max_players']);

        $maxClampedId = $model->create_match([
            'title' => 'Max Clamped Match',
            'start_datetime' => $start,
            'end_datetime' => $end,
            'max_players' => 999,
            'created_by_user_id' => self::$player1Id,
        ]);
        $maxRow = $db->get_where('sports_court_matches', ['id' => $maxClampedId])->row_array();
        $this->assertEquals(50, (int) $maxRow['max_players']);

        // 1.4 Missing required fields throws InvalidArgumentException
        $this->expectException(InvalidArgumentException::class);
        $model->create_match(['title' => '']);
    }

    /**
     * Test 2: Verify atomic join under capacity, incremental players, and transition to 'full'.
     */
    public function testAtomicJoinUnderCapacityAndFullStatusTransition(): void
    {
        $model = self::ci()->sports_matches_model;
        $db = self::db();

        $start = date('Y-m-d 20:00:00', strtotime('+3 days'));
        $end = date('Y-m-d 21:00:00', strtotime('+3 days'));

        // Match capacity: 3 players (Creator + 2 slots)
        $matchId = $model->create_match([
            'title' => '3 Kişilik Mini Turnuva',
            'id_stations' => self::$stationId,
            'start_datetime' => $start,
            'end_datetime' => $end,
            'max_players' => 3,
            'created_by_user_id' => self::$player1Id,
            'price_per_player' => 200.00,
        ]);

        $m0 = $db->get_where('sports_court_matches', ['id' => $matchId])->row_array();
        $this->assertEquals(1, (int) $m0['current_players']);
        $this->assertEquals('open', $m0['status']);

        // Player 2 joins (Slot 2/3)
        $res2 = $model->join_match($matchId, self::$player2Id, 'Team B', '4.0');
        $this->assertTrue($res2['success']);
        $this->assertEquals(2, $res2['current_players']);
        $this->assertEquals('open', $res2['status']);

        $m1 = $db->get_where('sports_court_matches', ['id' => $matchId])->row_array();
        $this->assertEquals(2, (int) $m1['current_players']);
        $this->assertEquals('open', $m1['status']);

        // Player 3 joins (Slot 3/3 -> transitions to full)
        $res3 = $model->join_match($matchId, self::$player3Id, 'Team A', '3.0');
        $this->assertTrue($res3['success']);
        $this->assertEquals(3, $res3['current_players']);
        $this->assertEquals('full', $res3['status']);

        $m2 = $db->get_where('sports_court_matches', ['id' => $matchId])->row_array();
        $this->assertEquals(3, (int) $m2['current_players']);
        $this->assertEquals('full', $m2['status']);

        // Verify participants count in DB
        $count = $db->get_where('sports_match_participants', ['id_matches' => $matchId])->num_rows();
        $this->assertEquals(3, $count);
    }

    /**
     * Test 3: Verify over-capacity rejection with atomic row-lock and duplicate join protection.
     */
    public function testOverCapacityRejectionAndDuplicateJoin(): void
    {
        $model = self::ci()->sports_matches_model;
        $db = self::db();

        $start = date('Y-m-d 19:00:00', strtotime('+4 days'));
        $end = date('Y-m-d 20:00:00', strtotime('+4 days'));

        // Match capacity: 2 players (Creator + 1 slot)
        $matchId = $model->create_match([
            'title' => 'Teke Tek Maç',
            'id_stations' => self::$stationId,
            'start_datetime' => $start,
            'end_datetime' => $end,
            'max_players' => 2,
            'created_by_user_id' => self::$player1Id,
        ]);

        // Player 2 fills the match
        $join2 = $model->join_match($matchId, self::$player2Id);
        $this->assertTrue($join2['success']);
        $this->assertEquals('full', $join2['status']);

        // 3.1 Over-capacity rejection: Player 3 tries to join the full match
        $rejectRes = $model->join_match($matchId, self::$player3Id);
        $this->assertFalse($rejectRes['success']);
        $this->assertSame('Maç kontenjanı dolmuştur veya maç açık değil.', $rejectRes['message']);

        // Verify match row was NOT modified
        $m = $db->get_where('sports_court_matches', ['id' => $matchId])->row_array();
        $this->assertEquals(2, (int) $m['current_players']);
        $this->assertEquals('full', $m['status']);

        // Verify Player 3 was NOT inserted
        $p3 = $db->get_where('sports_match_participants', [
            'id_matches' => $matchId,
            'id_users_customer' => self::$player3Id,
        ])->row_array();
        $this->assertNull($p3);

        // 3.2 Duplicate join rejection: Player 2 attempts to join again
        $dupRes = $model->join_match($matchId, self::$player2Id);
        $this->assertFalse($dupRes['success']);
        $this->assertSame('Bu maça zaten katıldınız.', $dupRes['message']);
    }

    /**
     * Test 4: Verify turnstile access verification for Active Members (Check 1).
     */
    public function testTurnstileAccessForActiveMembers(): void
    {
        $model = self::ci()->sports_matches_model;
        $db = self::db();

        // Ensure active membership
        $db->where('id_users_customer', self::$memberCustomerId)->delete('customer_memberships');
        $db->insert('customer_memberships', [
            'id_users_customer' => self::$memberCustomerId,
            'id_membership_plans' => 1,
            'status' => 'active',
            'current_period_start' => date('Y-m-d 00:00:00', strtotime('-10 days')),
            'current_period_end' => date('Y-m-d 23:59:59', strtotime('+20 days')),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $res = $model->verify_turnstile_access('CUST:' . self::$memberCustomerId, 'GATE-GYM-01');

        $this->assertTrue($res['access_granted']);
        $this->assertEquals(1, $res['relay_trigger']);
        $this->assertEquals(1500, $res['pulse_duration_ms']);
        $this->assertEquals('GATE-GYM-01', $res['gate_id']);
        $this->assertStringContainsString('Aktif üyelik doğrulandı', $res['reason']);

        // Verify check-in log written
        $log = $db
            ->where('id_users_customer', self::$memberCustomerId)
            ->order_by('entry_timestamp DESC')
            ->get('checkin_logs', 1)
            ->row_array();
        $this->assertNotNull($log);
        $this->assertEquals('completed', $log['status']);
        $this->assertStringContainsString('GATE-GYM-01', $log['notes']);
    }

    /**
     * Test 5: Verify turnstile access verification for Appointment / Reservation holders (Check 2).
     */
    public function testTurnstileAccessForAppointmentHolders(): void
    {
        $model = self::ci()->sports_matches_model;
        $db = self::db();

        // Customer has NO active membership
        $db->where('id_users_customer', self::$appointmentCustomerId)->delete('customer_memberships');

        // Create court appointment starting in 15 minutes (within -30 min to +30 min window)
        $db->insert('appointments', [
            'id_users_customer' => self::$appointmentCustomerId,
            'id_users_provider' => self::$providerId,
            'id_services' => 1,
            'start_datetime' => date('Y-m-d H:i:s', strtotime('+15 minutes')),
            'end_datetime' => date('Y-m-d H:i:s', strtotime('+75 minutes')),
            'status' => 'Confirmed',
            'is_unavailability' => 0,
            'hash' => md5(uniqid('court_res', true)),
        ]);
        $apptId = (int) $db->insert_id();

        $res = $model->verify_turnstile_access('CUST:' . self::$appointmentCustomerId, 'GATE-COURT-02');

        $this->assertTrue($res['access_granted']);
        $this->assertEquals(1, $res['relay_trigger']);
        $this->assertStringContainsString('Rezervasyon/kort randevusu doğrulandı', $res['reason']);

        // Cleanup
        $db->where('id', $apptId)->delete('appointments');
    }

    /**
     * Test 6: Verify turnstile access verification for Open Match participants (Check 3).
     */
    public function testTurnstileAccessForMatchParticipants(): void
    {
        $model = self::ci()->sports_matches_model;
        $db = self::db();

        // Player 2 has NO membership and NO appointment
        $db->where('id_users_customer', self::$player2Id)->delete('customer_memberships');
        $db->where('id_users_customer', self::$player2Id)->delete('appointments');

        // 6.1 Match is starting in 20 minutes (within [-30 min, +45 min] window)
        $matchId = $model->create_match([
            'title' => 'Turnike Test Padel Maçı',
            'id_stations' => self::$stationId,
            'start_datetime' => date('Y-m-d H:i:s', strtotime('+20 minutes')),
            'end_datetime' => date('Y-m-d H:i:s', strtotime('+80 minutes')),
            'max_players' => 4,
            'created_by_user_id' => self::$player1Id,
        ]);

        $model->join_match($matchId, self::$player2Id);

        $res = $model->verify_turnstile_access('CUST:' . self::$player2Id, 'GATE-PADEL-01');

        $this->assertTrue($res['access_granted']);
        $this->assertEquals(1, $res['relay_trigger']);
        $this->assertStringContainsString('Açık maç katılımı doğrulandı (Turnike Test Padel Maçı)', $res['reason']);

        // 6.2 Match is far in future (+5 hours) -> Player 4 has match starting in 5 hours -> DENIED
        $db->where('id_users_customer', self::$player4Id)->delete('customer_memberships');
        $db->where('id_users_customer', self::$player4Id)->delete('appointments');

        $futureMatchId = $model->create_match([
            'title' => 'Gece Maçı',
            'start_datetime' => date('Y-m-d H:i:s', strtotime('+5 hours')),
            'end_datetime' => date('Y-m-d H:i:s', strtotime('+6 hours 30 minutes')),
            'max_players' => 4,
            'created_by_user_id' => self::$player4Id,
        ]);

        $futureRes = $model->verify_turnstile_access('CUST:' . self::$player4Id, 'GATE-PADEL-01');
        $this->assertFalse($futureRes['access_granted']);
        $this->assertEquals(0, $futureRes['relay_trigger']);
        $this->assertStringContainsString('Geçerli üyelik, aktif randevu veya maç katılımı bulunamadı', $futureRes['reason']);
    }

    /**
     * Test 7: Verify Verticals controller web endpoints (ensure_authenticated, create, join, verify).
     */
    public function testVerticalsWebControllerEndpoints(): void
    {
        $controller = $this->createVerticalsController();
        $db = self::db();

        // 7.1 Unauthenticated access to create_sports_match throws 401 RuntimeException in testing
        session(['user_id' => null, 'role_slug' => null]);
        $unauthThrown = false;
        try {
            $controller->create_sports_match();
        } catch (\Throwable $e) {
            $unauthThrown = true;
            $this->assertEquals(401, $e->getCode());
        }
        $this->assertTrue($unauthThrown, 'Unauthenticated user must be rejected with 401');

        // 7.2 Customer role access throws 403 Forbidden in testing
        session(['user_id' => self::$player1Id, 'role_slug' => 'customer']);
        $forbiddenThrown = false;
        try {
            $controller->create_sports_match();
        } catch (\Throwable $e) {
            $forbiddenThrown = true;
            $this->assertEquals(403, $e->getCode());
        }
        $this->assertTrue($forbiddenThrown, 'Customer role must be rejected with 403 in ensure_authenticated');

        // 7.3 Success as admin / provider: create_sports_match
        session(['user_id' => self::$providerId, 'role_slug' => 'provider']);
        $_POST = [
            'title' => 'Web Controller Test Maçı',
            'start_datetime' => date('Y-m-d 17:00:00', strtotime('+1 day')),
            'end_datetime' => date('Y-m-d 18:00:00', strtotime('+1 day')),
            'sport_type' => 'padel',
            'max_players' => 4,
            'created_by_user_id' => self::$player1Id,
        ];
        $controller->create_sports_match();
        $createResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($createResp['success']);
        $this->assertGreaterThan(0, $createResp['match_id']);
        $createdMatchId = (int) $createResp['match_id'];

        // 7.4 Success as admin / provider: join_sports_match
        $_POST = [
            'match_id' => $createdMatchId,
            'id_users_customer' => self::$player3Id,
            'team' => 'Team B',
            'skill_level' => '3.5',
        ];
        $controller->join_sports_match();
        $joinResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($joinResp['success']);
        $this->assertEquals(2, $joinResp['current_players']);

        // 7.5 Success as admin / provider: verify_turnstile
        $_POST = [
            'token' => 'CUST:' . self::$player3Id,
            'gate_id' => 'GATE-MAIN',
        ];
        $controller->verify_turnstile();
        $turnResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('access_granted', $turnResp);
        $this->assertArrayHasKey('relay_trigger', $turnResp);
    }

    /**
     * Test 8: Verify Verticals_api_v1 authorization, customer scoping & input validation.
     */
    public function testVerticalsApiV1EndpointsAuthAndValidation(): void
    {
        $api = $this->createVerticalsApiController();
        $db = self::db();

        // 8.1 create_sports_match: Missing auth -> 401
        session(['user_id' => null, 'role_slug' => null]);
        $_POST = [];
        $api->create_sports_match();
        $resp401 = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('error', $resp401);
        $this->assertSame('Kimlik doğrulama gereklidir.', $resp401['error']);

        // 8.2 create_sports_match: Authenticated as customer, missing required title -> 400
        session(['user_id' => self::$player1Id, 'role_slug' => 'customer']);
        $_POST = ['title' => ''];
        $api->create_sports_match();
        $resp400 = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('error', $resp400);
        $this->assertStringContainsString('zorunludur', $resp400['error']);

        // 8.3 create_sports_match: End datetime before start datetime -> 400
        $_POST = [
            'title' => 'Tarih Hatası Testi',
            'start_datetime' => date('Y-m-d 19:00:00', strtotime('+2 days')),
            'end_datetime' => date('Y-m-d 18:00:00', strtotime('+2 days')),
        ];
        $api->create_sports_match();
        $respDateErr = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('error', $respDateErr);
        $this->assertSame('Bitiş zamanı başlangıç zamanından sonra olmalıdır.', $respDateErr['error']);

        // 8.4 create_sports_match: Valid payload -> 201 Created
        $_POST = [
            'title' => 'API Padel Challenge',
            'start_datetime' => date('Y-m-d 19:00:00', strtotime('+2 days')),
            'end_datetime' => date('Y-m-d 20:30:00', strtotime('+2 days')),
            'sport_type' => 'padel',
            'max_players' => 4,
            'price_per_player' => 175.00,
        ];
        $api->create_sports_match();
        $apiCreateResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($apiCreateResp['success']);
        $this->assertGreaterThan(0, $apiCreateResp['match_id']);
        $apiMatchId = (int) $apiCreateResp['match_id'];

        // 8.5 join_sports_match: Customer attempting to join on behalf of ANOTHER customer -> 403 Forbidden
        session(['user_id' => self::$player2Id, 'role_slug' => 'customer']);
        $_POST = [
            'customer_id' => self::$player4Id,
        ];
        $api->join_sports_match($apiMatchId);
        $respForbidden = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('error', $respForbidden);
        $this->assertSame('Diğer kullanıcılar adına maça katılamazsınız.', $respForbidden['error']);

        // 8.6 join_sports_match: Customer joins for themselves -> 200 OK
        $_POST = [
            'customer_id' => self::$player2Id,
            'team' => 'Team B',
            'skill_level' => '3.0',
        ];
        $api->join_sports_match($apiMatchId);
        $joinOkResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($joinOkResp['success']);
        $this->assertEquals(2, $joinOkResp['current_players']);
    }
}
