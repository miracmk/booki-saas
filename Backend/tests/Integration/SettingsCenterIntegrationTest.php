<?php declare(strict_types=1);

namespace Tests\Integration;

use Tests\TenantTestCase;

/**
 * Integration test suite for BooKi Ayarlar Merkezi (Settings Center)
 * and Permission/Routing fixes (Hata A & Hata B).
 */
class SettingsCenterIntegrationTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::require_multi_tenant();
    }

    /**
     * 1. Test Settings Registry Schema, Sections, and Secret Masking.
     */
    public function testSettingsRegistrySchemaAndSecretMasking(): void
    {
        $ci = self::ci();
        $ci->load->library('settings_registry');
        $reg = $ci->settings_registry;

        // Check 6 main sections
        $schemas = $reg->get_schema();
        $expected_sections = ['business', 'booking', 'communication', 'integrations', 'legal', 'security'];
        foreach ($expected_sections as $sec) {
            $this->assertArrayHasKey($sec, $schemas, "Section '{$sec}' must exist in Settings Registry.");
            $this->assertNotEmpty($schemas[$sec]['settings'], "Section '{$sec}' must define settings.");
        }

        // Check secret masking
        $this->assertTrue($reg->is_secret('agent_api_key'), "agent_api_key must be marked as secret.");
        $this->assertTrue($reg->is_secret('email_smtp_password'), "email_smtp_password must be marked as secret.");
        $this->assertTrue($reg->is_secret('telegram_bot_token'), "telegram_bot_token must be marked as secret.");
        $this->assertFalse($reg->is_secret('company_name'), "company_name must not be secret.");

        $masked = $reg->mask_secret('sk_live_1234567890abcdef');
        $this->assertStringContainsString('••••', $masked);
        $this->assertStringNotContainsString('1234567890', $masked);
        $this->assertTrue($reg->is_masked_placeholder($masked));
    }

    /**
     * 2. Test first_accessible_route() resolution for various roles.
     */
    public function testFirstAccessibleRouteResolution(): void
    {
        $ci = self::ci();
        $ci->load->library('permission_service');
        $ci->load->library('vertical_service');
        $ci->load->library('demo_service');

        // Connect to restaurant demo tenant
        $tenant = self::db()->get_where('tenants', ['subdomain' => 'demo-restoran'])->row_array();
        $this->assertNotEmpty($tenant, "demo-restoran tenant must exist.");
        self::connect_tenant($tenant);

        // Test owner: should land on dashboard
        $ci->demo_service->switch_role('owner');
        $owner_id = (int) session('user_id');
        $this->assertNotEmpty($owner_id, "Owner user session must be set.");
        
        $owner_route = $ci->permission_service->first_accessible_route($owner_id);
        $this->assertEquals('dashboard', $owner_route, "Owner must route to dashboard.");

        // Switch to waiter role and test first accessible route
        $ci->demo_service->switch_role('waiter');
        $waiter_id = (int) session('user_id');
        $waiter_route = $ci->permission_service->first_accessible_route($waiter_id);
        
        $this->assertNotEquals('dashboard', $waiter_route, "Waiter without dashboard permission must NOT route to dashboard.");
        $this->assertContains($waiter_route, ['restaurant', 'restaurant/waitress_screen', 'adisyons', 'account'], "Waiter must route to functional restaurant screen.");

        // Switch to kitchen role
        $ci->demo_service->switch_role('kitchen');
        $kitchen_id = (int) session('user_id');
        $kitchen_route = $ci->permission_service->first_accessible_route($kitchen_id);
        
        $this->assertNotEquals('dashboard', $kitchen_route, "Kitchen role must NOT route to dashboard.");
        $this->assertContains($kitchen_route, ['restaurant/kitchen_screen', 'account'], "Kitchen must route to kitchen screen.");

        // Restore owner
        $ci->demo_service->switch_role('owner');
    }

    /**
     * 3. Test Settings Validation and Persistence with Audit Diff.
     */
    public function testSettingsValidationAndPersistence(): void
    {
        $ci = self::ci();
        $ci->load->library('settings_registry');
        $reg = $ci->settings_registry;

        // Connect to a tenant db
        $tenant = self::db()->get_where('tenants', ['subdomain' => 'demo-guzellik'])->row_array();
        self::connect_tenant($tenant);

        // Invalid email validation test
        $this->expectException(\InvalidArgumentException::class);
        $reg->validate_and_sanitize('business', ['company_email' => 'invalid-email-format']);
    }

    /**
     * 4. Test Valid Settings Save and Secret Masking in Audit Diff.
     */
    public function testValidSettingsSaveAndAuditDiff(): void
    {
        $ci = self::ci();
        $ci->load->library('settings_registry');
        $reg = $ci->settings_registry;

        $tenant = self::db()->get_where('tenants', ['subdomain' => 'demo-guzellik'])->row_array();
        self::connect_tenant($tenant);

        $payload = [
            'company_name' => 'BooKi Beauty Studio ' . time(),
            'company_email' => 'contact@bookistudio.com',
            'company_currency' => 'TL',
            'company_color' => '#35A768',
        ];

        $sanitized = $reg->validate_and_sanitize('business', $payload);
        $diff = $reg->save_section_values('business', $sanitized, 1);

        $this->assertArrayHasKey('company_name', $diff);
        $this->assertEquals($payload['company_name'], $diff['company_name']['new']);

        // Test saving secret masks value in diff
        $secret_payload = [
            'agent_api_key' => 'secret_key_' . uniqid(),
        ];
        $sanitized_sec = $reg->validate_and_sanitize('integrations', $secret_payload);
        $diff_sec = $reg->save_section_values('integrations', $sanitized_sec, 1);

        $this->assertArrayHasKey('agent_api_key', $diff_sec);
        $this->assertEquals('[MASKED]', $diff_sec['agent_api_key']['new'], "Secret value must be masked in audit diff.");
    }

    /**
     * 5. Test Company Color Luminance and Contrast Guard.
     */
    public function testCompanyColorContrastGuard(): void
    {
        if (!function_exists('getLuminance')) {
            require_once APPPATH . 'views/components/company_color_style.php';
        }

        $this->assertTrue(function_exists('getLuminance'), "getLuminance helper function must exist.");

        // White has luminance ~ 1.0 (too light, must be guarded)
        $white_lum = getLuminance('#ffffff');
        $this->assertGreaterThan(0.80, $white_lum, "White (#ffffff) must have luminance > 0.80.");

        // Dark brand green has low luminance
        $green_lum = getLuminance('#35A768');
        $this->assertLessThan(0.80, $green_lum, "Brand green (#35A768) must have luminance < 0.80.");

        // Pure black has 0 luminance
        $black_lum = getLuminance('#000000');
        $this->assertEquals(0.0, $black_lum);
    }

    /**
     * 6. Test Settings View renders without error.
     */
    public function testSettingsControllerIndexRendersSuccessfully(): void
    {
        $ci = self::ci();
        $ci->load->library('demo_service');
        $ci->load->library('settings_registry');
        $ci->load->model('settings_model');

        $tenant = self::db()->get_where('tenants', ['subdomain' => 'demo-restoran'])->row_array();
        self::connect_tenant($tenant);

        $ci->demo_service->switch_role('owner');

        $schemas = $ci->settings_registry->get_schema();
        $values = $ci->settings_registry->get_section_values('business');

        $html = $ci->load->view('pages/settings', [
            'schemas' => $schemas,
            'sections' => $schemas,
            'active_section' => 'business',
            'section_values' => ['business' => $values],
            'values' => $values,
            'can_edit' => true,
            'can_save' => true,
            'can_manage_secrets' => true,
            'subdomain' => 'demo-restoran',
            'mcp_url' => 'https://demo-restoran.bookiapp.kibusiness.co/mcp',
            'agent_api_key' => 'test-key',
            'agent_api_key_masked' => 'test••••',
            'settings_registry' => $ci->settings_registry,
        ], true);

        $this->assertNotEmpty($html);
        $this->assertTrue(str_contains($html, 'Ayarlar Merkezi') || str_contains($html, 'Settings Center'), 'Page must contain Settings Center title.');
        $this->assertStringContainsString('Model Context Protocol', $html);
    }

    /**
     * 7. Test Working Plan and Blocked Periods (Holidays) Persistence Lifecycle.
     */
    public function testWorkingPlanAndBlockedPeriodsPersistence(): void
    {
        $ci = self::ci();
        $ci->load->model('settings_model');
        $ci->load->model('blocked_periods_model');
        $ci->load->model('providers_model');

        $tenant = self::db()->get_where('tenants', ['subdomain' => 'demo-restoran'])->row_array();
        self::connect_tenant($tenant);

        // 1. Save company_working_plan
        $custom_plan = [
            'monday' => ['start' => '08:30', 'end' => '23:00', 'breaks' => [['start' => '15:00', 'end' => '16:00']]],
            'tuesday' => ['start' => '08:30', 'end' => '23:00', 'breaks' => []],
            'wednesday' => ['start' => '08:30', 'end' => '23:00', 'breaks' => []],
            'thursday' => ['start' => '08:30', 'end' => '23:00', 'breaks' => []],
            'friday' => ['start' => '08:30', 'end' => '01:00', 'breaks' => []],
            'saturday' => ['start' => '09:00', 'end' => '01:00', 'breaks' => []],
            'sunday' => null, // Closed on Sundays
        ];
        $ci->settings_model->set_setting('company_working_plan', json_encode($custom_plan));

        $retrieved = json_decode(setting('company_working_plan'), true);
        $this->assertEquals('08:30', $retrieved['monday']['start']);
        $this->assertNull($retrieved['sunday']);

        // 2. Add blocked period / holiday
        $bp_id = $ci->blocked_periods_model->save([
            'name' => 'Yılbaşı Özel Tatili ' . time(),
            'start_datetime' => '2027-01-01 00:00:00',
            'end_datetime' => '2027-01-01 23:59:59',
            'notes' => 'Yeni yıl resmi tatili',
        ]);
        $this->assertGreaterThan(0, $bp_id);

        $saved_bp = $ci->blocked_periods_model->find($bp_id);
        $this->assertEquals('Yeni yıl resmi tatili', $saved_bp['notes']);

        // 3. Delete blocked period
        $ci->blocked_periods_model->delete($bp_id);
        $deleted = $ci->db->get_where('blocked_periods', ['id' => $bp_id])->row_array();
        $this->assertEmpty($deleted);

        // 4. Apply plan to all providers
        $providers = $ci->providers_model->get();
        if (!empty($providers)) {
            foreach ($providers as $pr) {
                $ci->providers_model->set_setting($pr['id'], 'working_plan', json_encode($custom_plan));
                $pr_plan = json_decode($ci->providers_model->get_setting((int)$pr['id'], 'working_plan'), true);
                $this->assertEquals('08:30', $pr_plan['monday']['start']);
            }
        }
    }

    /**
     * Test WhatsApp dual-mode (Baileys bridge) and company AI assistant settings schema and persistence.
     */
    public function testWhatsAppDualModeAndAiAssistantSettings(): void
    {
        $ci = &get_instance();
        $ci->load->library('settings_registry');
        $ci->load->model('messaging_settings_model');

        // 1. Verify schema definitions
        $comm_schema = $ci->settings_registry->get_schema(\Settings_registry::SECTION_COMMUNICATION);
        $this->assertArrayHasKey('whatsapp_mode', $comm_schema['settings']);
        $this->assertArrayHasKey('whatsapp_bridge_url', $comm_schema['settings']);
        $this->assertArrayHasKey('whatsapp_bridge_secret', $comm_schema['settings']);
        $this->assertTrue($comm_schema['settings']['whatsapp_bridge_secret']['is_secret']);

        $integ_schema = $ci->settings_registry->get_schema(\Settings_registry::SECTION_INTEGRATIONS);
        $this->assertArrayHasKey('ai_assistant', $integ_schema['tabs']);
        $this->assertArrayHasKey('ai_assistant_enabled', $integ_schema['settings']);
        $this->assertArrayHasKey('ai_brand_name', $integ_schema['settings']);
        $this->assertArrayHasKey('ai_tone', $integ_schema['settings']);
        $this->assertArrayHasKey('ai_do_rules', $integ_schema['settings']);
        $this->assertArrayHasKey('ai_dont_rules', $integ_schema['settings']);
        $this->assertArrayHasKey('ai_cancellation_policy', $integ_schema['settings']);

        // 2. Test saving WhatsApp dual-mode values
        $comm_payload = [
            'whatsapp_mode' => 'unofficial',
            'whatsapp_bridge_url' => 'http://wa-bridge:3000',
            'whatsapp_bridge_secret' => 'super-secret-test-bridge-token',
        ];
        $sanitized_comm = $ci->settings_registry->validate_and_sanitize(\Settings_registry::SECTION_COMMUNICATION, $comm_payload);
        $ci->settings_registry->save_section_values(\Settings_registry::SECTION_COMMUNICATION, $sanitized_comm, 1);

        $this->assertEquals('unofficial', setting('whatsapp_mode'));
        $this->assertEquals('http://wa-bridge:3000', setting('whatsapp_bridge_url'));

        // 3. Test saving AI Assistant settings and sync to tenant_ai_policies
        $ai_payload = [
            'ai_assistant_enabled' => true,
            'ai_brand_name' => 'Elit Kuaför & Güzellik',
            'ai_tone' => 'warm_empathetic',
            'ai_greeting_style' => 'Merhaba! Size nasıl yardımcı olabiliriz?',
            'ai_do_rules' => "Müşteriye her zaman randevu saatinden 15 dakika önce gelmesini hatırlat.\nHer randevuda sıcak içecek ikramı teklif et.",
            'ai_dont_rules' => "Asla fiyat indirimi sözü verme.\nYetkisiz işlem yapma.",
            'ai_cancellation_policy' => 'Randevudan en geç 2 saat önce haber verilmelidir.',
            'ai_forbidden_terms' => 'ucuz, dandik, indirim yok',
        ];
        $sanitized_ai = $ci->settings_registry->validate_and_sanitize(\Settings_registry::SECTION_INTEGRATIONS, $ai_payload);
        $ci->settings_registry->save_section_values(\Settings_registry::SECTION_INTEGRATIONS, $sanitized_ai, 1);

        $this->assertEquals('Elit Kuaför & Güzellik', setting('ai_brand_name'));
        $this->assertEquals('warm_empathetic', setting('ai_tone'));

        // 4. Verify AI Assistant prompt generation incorporates the business's policy
        $ci->load->library('ai_channel_responder');
        $reflector = new \ReflectionClass($ci->ai_channel_responder);
        if ($reflector->hasMethod('build_system_prompt')) {
            $method = $reflector->getMethod('build_system_prompt');
            $method->setAccessible(true);
            $prompt = $method->invoke($ci->ai_channel_responder, 'whatsapp', '+905551234567', null);
            $this->assertStringContainsString('Elit Kuaför & Güzellik', $prompt);
            $this->assertStringContainsString('İŞLETME ÖZEL ASİSTAN POLİTİKASI', $prompt);
            $this->assertStringContainsString('sıcak içecek ikramı', $prompt);
            $this->assertStringContainsString('en geç 2 saat önce', $prompt);
        }
    }
}

