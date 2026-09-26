<?php declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for Sector Pricing Matrix, 9 Main Sectors, 168 Sub-business Types,
 * 5-tier Pricing, Baremler (Quotas), and Feature Rules (All features open except AI on Free).
 */
class SectorPricingMatrixTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $constants = dirname(__DIR__, 2) . '/application/config/constants.php';
        if (file_exists($constants)) {
            require_once $constants;
        }

        $tenantHelper = dirname(__DIR__, 2) . '/application/helpers/tenant_helper.php';
        if (file_exists($tenantHelper)) {
            require_once $tenantHelper;
        }

        $planHelper = dirname(__DIR__, 2) . '/application/helpers/plan_helper.php';
        if (file_exists($planHelper)) {
            require_once $planHelper;
        }

        $industryHelper = dirname(__DIR__, 2) . '/application/helpers/industry_helper.php';
        if (file_exists($industryHelper)) {
            require_once $industryHelper;
        }
    }

    /**
     * Test that all 9 main sectors exist and have valid structure.
     */
    public function testAllMainSectorsLoaded(): void
    {
        $sectors = get_main_sectors();
        $this->assertCount(9, $sectors, 'Must contain exactly 9 main sectors');

        $expectedSectors = [
            'Güzellik / Kişisel Bakım',
            'Restoran / Yeme-İçme',
            'Spor / Fitness',
            'Sağlık / Uzmanlık',
            'Otomotiv',
            'Deneyim / Eğlence',
            'Konaklama',
            'Eğitim / Kurs',
            'Profesyonel Hizmet',
        ];

        foreach ($expectedSectors as $name) {
            $this->assertArrayHasKey($name, $sectors);
            $this->assertNotEmpty($sectors[$name]['name']);
            $this->assertNotEmpty($sectors[$name]['vertical_group']);
            $this->assertNotEmpty($sectors[$name]['icon']);
        }
    }

    /**
     * Test that exactly 168 sub-business types are loaded.
     */
    public function testAll168SubBusinessTypesLoaded(): void
    {
        $matrix = get_sector_pricing_matrix();
        $this->assertCount(168, $matrix, 'Must contain exactly 168 sub-business types');

        $requiredTiers = ['Free', 'Basic', 'Pro', 'Premium', 'Custom'];

        foreach ($matrix as $name => $data) {
            $this->assertNotEmpty($data['name']);
            $this->assertNotEmpty($data['slug']);
            $this->assertNotEmpty($data['main_sector']);
            $this->assertNotEmpty($data['vertical_group']);
            $this->assertArrayHasKey('metric_types', $data);
            $this->assertArrayHasKey('resource', $data['metric_types']);
            $this->assertArrayHasKey('staff', $data['metric_types']);
            $this->assertArrayHasKey('booking', $data['metric_types']);
            $this->assertArrayHasKey('tiers', $data);

            foreach ($requiredTiers as $t) {
                $this->assertArrayHasKey($t, $data['tiers']);
                $tier = $data['tiers'][$t];
                $this->assertArrayHasKey('raw', $tier);
                $this->assertArrayHasKey('resource_limit', $tier);
                $this->assertArrayHasKey('staff_limit', $tier);
                $this->assertArrayHasKey('appointment_limit', $tier);
            }
        }
    }

    /**
     * Test 5-tier pricing definitions and amounts from the spreadsheet.
     */
    public function testPlanPricingDefinitions(): void
    {
        $pricing = plan_pricing_definitions();
        $this->assertCount(5, $pricing);

        // 1. Ücretsiz (Free)
        $this->assertEquals('Ücretsiz', $pricing['Free']['name']);
        $this->assertEquals(0, $pricing['Free']['monthly_price']);
        $this->assertEquals(0, $pricing['Free']['annual_monthly_price']);
        $this->assertFalse($pricing['Free']['has_ai']);

        // 2. Başlangıç (Basic)
        $this->assertEquals('Başlangıç', $pricing['Basic']['name']);
        $this->assertEquals(1250, $pricing['Basic']['monthly_price']);
        $this->assertEquals(1000, $pricing['Basic']['annual_monthly_price']);
        $this->assertEquals(12000, $pricing['Basic']['annual_total_price']);
        $this->assertTrue($pricing['Basic']['has_ai']);

        // 3. Orta (Pro)
        $this->assertEquals('Orta', $pricing['Pro']['name']);
        $this->assertEquals(2450, $pricing['Pro']['monthly_price']);
        $this->assertEquals(1950, $pricing['Pro']['annual_monthly_price']);
        $this->assertEquals(23400, $pricing['Pro']['annual_total_price']);
        $this->assertTrue($pricing['Pro']['has_ai']);

        // 4. Premium
        $this->assertEquals('Premium', $pricing['Premium']['name']);
        $this->assertEquals(4750, $pricing['Premium']['monthly_price']);
        $this->assertEquals(3800, $pricing['Premium']['annual_monthly_price']);
        $this->assertEquals(45600, $pricing['Premium']['annual_total_price']);
        $this->assertTrue($pricing['Premium']['has_ai']);

        // 5. Özel (Custom)
        $this->assertEquals('Özel', $pricing['Custom']['name']);
        $this->assertNull($pricing['Custom']['monthly_price']);
        $this->assertTrue($pricing['Custom']['has_ai']);
    }

    /**
     * Test plan normalization for various Turkish and English plan slugs.
     */
    public function testNormalizePlanName(): void
    {
        $this->assertEquals('Free', normalize_plan_name('free'));
        $this->assertEquals('Free', normalize_plan_name('ücretsiz'));
        $this->assertEquals('Free', normalize_plan_name('ucretsiz'));
        $this->assertEquals('Free', normalize_plan_name(null));

        $this->assertEquals('Basic', normalize_plan_name('basic'));
        $this->assertEquals('Basic', normalize_plan_name('başlangıç'));
        $this->assertEquals('Basic', normalize_plan_name('starter'));

        $this->assertEquals('Pro', normalize_plan_name('pro'));
        $this->assertEquals('Pro', normalize_plan_name('orta'));
        $this->assertEquals('Pro', normalize_plan_name('medium'));
        $this->assertEquals('Pro', normalize_plan_name('professional'));

        $this->assertEquals('Premium', normalize_plan_name('premium'));

        $this->assertEquals('Custom', normalize_plan_name('custom'));
        $this->assertEquals('Custom', normalize_plan_name('özel'));
        $this->assertEquals('Custom', normalize_plan_name('elite'));
        $this->assertEquals('Custom', normalize_plan_name('enterprise'));
    }

    /**
     * Test the golden rule: "tüm özellikler açık tek istisna Ücretsizlerde AI Asistant yok".
     */
    public function testPlanAllowsFeatureRules(): void
    {
        // Mock multi-tenant mode with Free plan
        \tenant_context(['id' => 999, 'subdomain' => 'test-free', 'plan' => 'Free']);

        // Free plan: AI Assistant MUST be denied
        $this->assertFalse(\plan_allows('ai_agent'));
        $this->assertFalse(\plan_allows('ai_assistant'));
        $this->assertFalse(\plan_allows(PRIV_AI_AGENT));

        // Free plan: ALL other features MUST be allowed
        $this->assertTrue(\plan_allows('white_label'));
        $this->assertTrue(\plan_allows('custom_domain'));
        $this->assertTrue(\plan_allows(PRIV_REPORTS));
        $this->assertTrue(\plan_allows(PRIV_MARKETING));
        $this->assertTrue(\plan_allows(PRIV_INVOICES));
        $this->assertTrue(\plan_allows(PRIV_POS));
        $this->assertTrue(\plan_allows(PRIV_BRANCHES));
        $this->assertTrue(\plan_allows(PRIV_MEMBERSHIPS));
        $this->assertTrue(\plan_allows('whatsapp_unofficial'));
        $this->assertTrue(\plan_allows('google_calendar'));

        // Paid plans: AI Assistant MUST be allowed
        \tenant_context(['id' => 999, 'subdomain' => 'test-basic', 'plan' => 'Basic']);
        $this->assertTrue(\plan_allows('ai_agent'));
        $this->assertTrue(\plan_allows(PRIV_AI_AGENT));

        \tenant_context(['id' => 999, 'subdomain' => 'test-pro', 'plan' => 'Pro']);
        $this->assertTrue(\plan_allows('ai_agent'));

        \tenant_context(['id' => 999, 'subdomain' => 'test-premium', 'plan' => 'Premium']);
        $this->assertTrue(\plan_allows('ai_agent'));

        \tenant_context(['id' => 999, 'subdomain' => 'test-custom', 'plan' => 'Custom']);
        $this->assertTrue(\plan_allows('ai_agent'));

        \tenant_context_clear();
    }

    /**
     * Test specific sectoral baremler (quotas) for representative sub-business types.
     */
    public function testSectoralBaremValues(): void
    {
        // 1. Kuaför (Beauty)
        $kuafor = find_business_barem('Kuaför');
        $this->assertNotNull($kuafor);
        $this->assertEquals('Güzellik / Kişisel Bakım', $kuafor['main_sector']);
        $this->assertEquals('İstasyon', $kuafor['metric_types']['resource']);
        $this->assertEquals('Personel', $kuafor['metric_types']['staff']);
        $this->assertEquals('Randevu', $kuafor['metric_types']['booking']);
        $this->assertEquals('1-2 Koltuk', $kuafor['tiers']['Free']['resource_label']);
        $this->assertEquals(2, $kuafor['tiers']['Free']['resource_limit']);
        $this->assertEquals(1, $kuafor['tiers']['Free']['staff_limit']);
        $this->assertEquals(40, $kuafor['tiers']['Free']['appointment_limit']);
        $this->assertEquals(5, $kuafor['tiers']['Basic']['resource_limit']);
        $this->assertEquals(5, $kuafor['tiers']['Basic']['staff_limit']);
        $this->assertEquals(250, $kuafor['tiers']['Basic']['appointment_limit']);
        $this->assertEquals(10, $kuafor['tiers']['Pro']['resource_limit']);
        $this->assertEquals(10, $kuafor['tiers']['Pro']['staff_limit']);
        $this->assertEquals(750, $kuafor['tiers']['Pro']['appointment_limit']);
        $this->assertEquals(20, $kuafor['tiers']['Premium']['resource_limit']);
        $this->assertEquals(20, $kuafor['tiers']['Premium']['staff_limit']);
        $this->assertEquals(2000, $kuafor['tiers']['Premium']['appointment_limit']);
        $this->assertNull($kuafor['tiers']['Custom']['resource_limit']);
        $this->assertNull($kuafor['tiers']['Custom']['staff_limit']);
        $this->assertNull($kuafor['tiers']['Custom']['appointment_limit']);

        // 2. Restoran (Restaurant)
        $restoran = find_business_barem('Restoran');
        $this->assertNotNull($restoran);
        $this->assertEquals('Restoran / Yeme-İçme', $restoran['main_sector']);
        $this->assertEquals('Masa', $restoran['metric_types']['resource']);
        $this->assertEquals('Kullanıcı', $restoran['metric_types']['staff']);
        $this->assertEquals('Rezervasyon', $restoran['metric_types']['booking']);
        $this->assertEquals(4, $restoran['tiers']['Free']['resource_limit']);
        $this->assertEquals(1, $restoran['tiers']['Free']['staff_limit']);
        $this->assertEquals(50, $restoran['tiers']['Free']['appointment_limit']);
        $this->assertEquals(12, $restoran['tiers']['Basic']['resource_limit']);
        $this->assertEquals(5, $restoran['tiers']['Basic']['staff_limit']);
        $this->assertEquals(300, $restoran['tiers']['Basic']['appointment_limit']);
        $this->assertEquals(30, $restoran['tiers']['Pro']['resource_limit']);
        $this->assertEquals(12, $restoran['tiers']['Pro']['staff_limit']);
        $this->assertEquals(1000, $restoran['tiers']['Pro']['appointment_limit']);
        $this->assertEquals(65, $restoran['tiers']['Premium']['resource_limit']);
        $this->assertEquals(25, $restoran['tiers']['Premium']['staff_limit']);
        $this->assertEquals(3000, $restoran['tiers']['Premium']['appointment_limit']);

        // 3. Fitness / Gym (Sports)
        $gym = find_business_barem('Fitness / Gym');
        $this->assertNotNull($gym);
        $this->assertEquals('Spor / Fitness', $gym['main_sector']);
        $this->assertEquals('Kaynak', $gym['metric_types']['resource']);
        $this->assertEquals('Eğitmen', $gym['metric_types']['staff']);
        $this->assertEquals('Seans', $gym['metric_types']['booking']);
        $this->assertEquals(1, $gym['tiers']['Free']['resource_limit']);
        $this->assertEquals(1, $gym['tiers']['Free']['staff_limit']);
        $this->assertEquals(40, $gym['tiers']['Free']['appointment_limit']);
        $this->assertEquals(3, $gym['tiers']['Basic']['resource_limit']);
        $this->assertEquals(3, $gym['tiers']['Basic']['staff_limit']);
        $this->assertEquals(200, $gym['tiers']['Basic']['appointment_limit']);

        // 4. Diş Kliniği (Health)
        $dentist = find_business_barem('Diş Kliniği');
        $this->assertNotNull($dentist);
        $this->assertEquals('Sağlık / Uzmanlık', $dentist['main_sector']);
        $this->assertEquals('Ünite', $dentist['metric_types']['resource']);
        $this->assertEquals('Uzman', $dentist['metric_types']['staff']);
        $this->assertEquals('Görüşme', $dentist['metric_types']['booking']);
        $this->assertEquals(1, $dentist['tiers']['Free']['resource_limit']);
        $this->assertEquals(1, $dentist['tiers']['Free']['staff_limit']);
        $this->assertEquals(40, $dentist['tiers']['Free']['appointment_limit']);

        // 5. Oto Yıkama (Automotive)
        $carWash = find_business_barem('Oto Yıkama');
        $this->assertNotNull($carWash);
        $this->assertEquals('Otomotiv', $carWash['main_sector']);
        $this->assertEquals('Peron', $carWash['metric_types']['resource']);
        $this->assertEquals('Personel', $carWash['metric_types']['staff']);
        $this->assertEquals('Araç', $carWash['metric_types']['booking']);
        $this->assertEquals(1, $carWash['tiers']['Free']['resource_limit']);
        $this->assertEquals(1, $carWash['tiers']['Free']['staff_limit']);
        $this->assertEquals(40, $carWash['tiers']['Free']['appointment_limit']);

        // 6. Butik Otel (Hospitality)
        $hotel = find_business_barem('Butik Otel');
        $this->assertNotNull($hotel);
        $this->assertEquals('Konaklama', $hotel['main_sector']);
        $this->assertEquals('Oda', $hotel['metric_types']['resource']);
        $this->assertEquals('Personel', $hotel['metric_types']['staff']);
        $this->assertEquals('Geceleme', $hotel['metric_types']['booking']);
        $this->assertEquals(3, $hotel['tiers']['Free']['resource_limit']);
        $this->assertEquals(1, $hotel['tiers']['Free']['staff_limit']);
        $this->assertEquals(30, $hotel['tiers']['Free']['appointment_limit']);
    }

    /**
     * Test vertical group resolution for new sectors in industry_helper.
     */
    public function testVerticalGroupResolution(): void
    {
        $this->assertEquals('beauty', current_vertical_group('Kuaför'));
        $this->assertEquals('restaurant', current_vertical_group('Restoran'));
        $this->assertEquals('sports', current_vertical_group('Fitness / Gym'));
        $this->assertEquals('health', current_vertical_group('Diş Kliniği'));
        $this->assertEquals('automotive', current_vertical_group('Oto Yıkama'));
        $this->assertEquals('experience', current_vertical_group('Escape Room'));
        $this->assertEquals('hospitality', current_vertical_group('Butik Otel'));
        $this->assertEquals('education', current_vertical_group('Dil Kursu'));
        $this->assertEquals('professional', current_vertical_group('Avukatlık'));
    }
}
