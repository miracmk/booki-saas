<?php declare(strict_types=1);

namespace Tests\Integration;

use Tests\TenantTestCase;
use RuntimeException;
use InvalidArgumentException;

/**
 * Integration Test for Sector 9 (professional - Hukuk Bürosu, Danışmanlık, Gayrimenkul)
 */
class ProfessionalServicesIntegrationTest extends TenantTestCase
{
    private static int $clientId;
    private static int $consultantId;

    protected function setUp(): void
    {
        parent::setUp();
        $db = self::db();

        // 1. Client / Customer
        $client = $db->get_where('users', ['email' => 'muvekkil_ahmet@booki.local'])->row_array();
        if (!$client) {
            $db->insert('users', [
                'first_name' => 'Ahmet',
                'last_name' => 'Müvekkil',
                'email' => 'muvekkil_ahmet@booki.local',
                'phone_number' => '05551112233',
                'id_roles' => 3,
                'role_slug' => 'customer',
                'is_active' => 1,
            ]);
            self::$clientId = (int) $db->insert_id();
        } else {
            self::$clientId = (int) $client['id'];
        }

        // 2. Consultant / Lawyer / Agent
        $consultant = $db->get_where('users', ['email' => 'avukat_canan@booki.local'])->row_array();
        if (!$consultant) {
            $db->insert('users', [
                'first_name' => 'Canan',
                'last_name' => 'Avukat',
                'email' => 'avukat_canan@booki.local',
                'phone_number' => '05554445566',
                'id_roles' => 2,
                'role_slug' => 'provider',
                'is_active' => 1,
            ]);
            self::$consultantId = (int) $db->insert_id();
        } else {
            self::$consultantId = (int) $consultant['id'];
        }

        // Ensure migration 176 tables exist
        self::ci()->load->library('migration');
        self::ci()->migration->version(176);
    }

    private function createVerticalsController(): \Verticals
    {
        require_once APPPATH . 'controllers/Verticals.php';
        $ci = self::ci();
        $controller = (new \ReflectionClass(\Verticals::class))->newInstanceWithoutConstructor();
        $controller->props = &$ci->props;
        $controller->load = $ci->load;
        $controller->db = $ci->db;
        $controller->input = $ci->input;
        $controller->output = $ci->output;
        $controller->accounts = $ci->accounts ?? null;
        $controller->roles_model = $ci->roles_model ?? null;
        return $controller;
    }

    public function testSaveLegalCaseSuccess(): void
    {
        $_SESSION['user_id'] = self::$consultantId;
        $_SESSION['role_slug'] = 'provider';

        $controller = $this->createVerticalsController();

        $_POST = [
            'id_users_client' => self::$clientId,
            'case_number' => '2026/888 Esas',
            'court_name' => 'İstanbul 12. Asliye Hukuk Mahkemesi',
            'opposing_party' => 'XYZ İnşaat Ltd. Şti.',
            'case_type' => 'civil',
            'case_subject' => 'Haksız fiil kaynaklı maddi ve manevi tazminat davası',
            'hearing_datetime' => '2026-11-15 10:30:00',
        ];

        ob_start();
        $controller->save_legal_case();
        $output = ob_get_clean();

        $response = json_decode($controller->output->get_output(), true);
        $this->assertTrue($response['success'], 'save_legal_case should succeed');
        $this->assertNotEmpty($response['case_id']);

        $case = self::db()->get_where('legal_cases', ['id' => $response['case_id']])->row_array();
        $this->assertNotNull($case);
        $this->assertSame('2026/888 Esas', $case['case_number']);
        $this->assertSame('open', $case['status']);
        $this->assertSame('2026-11-15 10:30:00', $case['hearing_datetime']);
    }

    public function testSaveLegalCaseValidation(): void
    {
        $_SESSION['user_id'] = self::$consultantId;
        $_SESSION['role_slug'] = 'provider';

        $controller = $this->createVerticalsController();

        $_POST = [
            'id_users_client' => 0, // Invalid client
            'case_number' => '',
            'court_name' => '',
        ];

        ob_start();
        $controller->save_legal_case();
        $output = ob_get_clean();

        $response = json_decode($controller->output->get_output(), true);
        $this->assertFalse($response['success']);
    }

    public function testSaveLegalCaseCustomerRoleGated(): void
    {
        $_SESSION['user_id'] = self::$clientId;
        $_SESSION['role_slug'] = 'customer';

        $controller = $this->createVerticalsController();

        $_POST = [
            'id_users_client' => self::$clientId,
            'case_number' => '2026/999 Esas',
            'court_name' => 'Bakırköy 3. İcra Mahkemesi',
            'case_subject' => 'İcra takibine itirazın kaldırılması',
        ];

        $caught = false;
        ob_start();
        try {
            $controller->save_legal_case();
        } catch (\Throwable $e) {
            $caught = true;
            $this->assertStringContainsString('Forbidden', $e->getMessage());
        } finally {
            ob_get_clean();
        }

        if (!$caught) {
            $response = json_decode($controller->output->get_output(), true);
            $this->assertFalse($response['success']);
        }
    }

    public function testSaveConsultingTimeLogSuccess(): void
    {
        $_SESSION['user_id'] = self::$consultantId;
        $_SESSION['role_slug'] = 'provider';

        $controller = $this->createVerticalsController();

        $_POST = [
            'id_users_client' => self::$clientId,
            'id_users_consultant' => self::$consultantId,
            'project_name' => 'Dijital Süreç ve ERP Entegrasyonu',
            'duration_minutes' => 90,
            'hourly_rate' => 2000.00,
            'work_description' => 'İş akış analizi ve mimari planlama toplantısı yapıldı.',
            'is_billable' => 1,
        ];

        ob_start();
        $controller->save_consulting_time_log();
        $output = ob_get_clean();

        $response = json_decode($controller->output->get_output(), true);
        $this->assertTrue($response['success'], 'save_consulting_time_log should succeed');
        $this->assertNotEmpty($response['log_id']);
        // 90 mins @ 2000/hr = 1.5 * 2000 = 3000.00
        $this->assertEquals(3000.00, (float) $response['total_fee']);

        $log = self::db()->get_where('consulting_time_logs', ['id' => $response['log_id']])->row_array();
        $this->assertNotNull($log);
        $this->assertEquals(90, (int) $log['duration_minutes']);
        $this->assertEquals(3000.00, (float) $log['total_fee']);
        $this->assertEquals(1, (int) $log['is_billable']);
    }

    public function testSaveRealEstateListingSuccess(): void
    {
        $_SESSION['user_id'] = self::$consultantId;
        $_SESSION['role_slug'] = 'provider';

        $controller = $this->createVerticalsController();

        $_POST = [
            'title' => 'Suadiye Bağdat Caddesi Sıfır 4+1 Dubleks',
            'listing_type' => 'sale',
            'property_type' => 'apartment',
            'price' => 14500000.00,
            'city' => 'İstanbul',
            'district' => 'Kadıköy',
            'square_meters' => 210,
            'id_users_agent' => self::$consultantId,
        ];

        ob_start();
        $controller->save_real_estate_listing();
        $output = ob_get_clean();

        $response = json_decode($controller->output->get_output(), true);
        $this->assertTrue($response['success'] ?? false, 'save_real_estate_listing failed: ' . ($response['message'] ?? 'unknown'));
        $this->assertNotEmpty($response['listing_id']);
        $this->assertStringStartsWith('LST-', $response['listing_code']);

        $listing = self::db()->get_where('real_estate_listings', ['id' => $response['listing_id']])->row_array();
        $this->assertNotNull($listing);
        $this->assertSame('Kadıköy', $listing['district']);
        $this->assertSame('active', $listing['status']);
        $this->assertEquals(14500000.00, (float) $listing['price']);
    }

    private function createVerticalsApiController(): \Verticals_api_v1
    {
        require_once APPPATH . 'controllers/api/v1/Verticals_api_v1.php';
        $ci = self::ci();
        $controller = (new \ReflectionClass(\Verticals_api_v1::class))->newInstanceWithoutConstructor();
        $controller->props = &$ci->props;
        $controller->load = $ci->load;
        $controller->db = $ci->db;
        $controller->input = $ci->input;
        $controller->output = $ci->output;
        return $controller;
    }

    public function testApiSaveLegalCaseAuthAndRoleGating(): void
    {
        // 1. Unauthenticated -> 401
        unset($_SESSION['user_id'], $_SESSION['role_slug']);
        $api = $this->createVerticalsApiController();

        $api->save_legal_case();
        $res = json_decode($api->output->get_output(), true);
        $this->assertArrayHasKey('error', $res);
        $this->assertSame('Kimlik doğrulama gereklidir.', $res['error']);

        // 2. Customer -> 403
        $_SESSION['user_id'] = self::$clientId;
        $_SESSION['role_slug'] = 'customer';

        $api->save_legal_case();
        $res = json_decode($api->output->get_output(), true);
        $this->assertArrayHasKey('error', $res);
        $this->assertSame('Müvekkiller doğrudan dava kaydı açamaz.', $res['error']);
    }

    public function testApiSaveConsultingTimeLogSuccess(): void
    {
        $_SESSION['user_id'] = self::$consultantId;
        $_SESSION['role_slug'] = 'provider';

        $api = $this->createVerticalsApiController();

        $_POST = [
            'id_users_client' => self::$clientId,
            'id_users_consultant' => self::$consultantId,
            'project_name' => 'API Danışmanlık Projesi',
            'duration_minutes' => 120,
            'hourly_rate' => 1000.00,
            'work_description' => 'API üzerinden efor girişi yapıldı.',
            'is_billable' => 1,
        ];

        $api->save_consulting_time_log();

        $res = json_decode($api->output->get_output(), true);
        $this->assertTrue($res['success'] ?? false, 'save_consulting_time_log failed: ' . ($res['error'] ?? ''));
        $this->assertEquals(2000.00, (float) $res['total_fee']);
    }

    public function testApiSaveRealEstateListingSuccess(): void
    {
        $_SESSION['user_id'] = self::$consultantId;
        $_SESSION['role_slug'] = 'provider';

        $api = $this->createVerticalsApiController();

        $_POST = [
            'title' => 'API Gayrimenkul Portföyü',
            'listing_type' => 'rent',
            'property_type' => 'office',
            'price' => 45000.00,
            'city' => 'İstanbul',
            'district' => 'Şişli',
            'square_meters' => 180,
            'id_users_agent' => self::$consultantId,
        ];

        $api->save_real_estate_listing();

        $res = json_decode($api->output->get_output(), true);
        $this->assertTrue($res['success'] ?? false, 'save_real_estate_listing failed: ' . ($res['error'] ?? ''));
        $this->assertNotEmpty($res['listing_code']);
    }
}

