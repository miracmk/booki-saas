<?php declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for Industry Blueprint, Terminology resolution, and Dynamic Module configurations.
 */
class IndustryBlueprintSettingsTest extends TestCase
{
    protected string $blueprintsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->blueprintsPath = dirname(__DIR__, 2) . '/application/seeders/blueprints/';
    }

    /**
     * Verify all 12 standard industry blueprints exist, have valid JSON, and required schema.
     */
    public function testStandardBlueprintsExistAndValid(): void
    {
        $expectedCodes = [
            'barber', 'beauty_salon', 'car_wash', 'dentist', 'doctor_clinic',
            'gym', 'hotel', 'massage_spa', 'nail_studio', 'pilates_studio',
            'pt_training', 'restaurant'
        ];

        foreach ($expectedCodes as $code) {
            $filepath = $this->blueprintsPath . $code . '.json';
            $this->assertFileExists($filepath, "Blueprint file must exist for: {$code}");

            $content = file_get_contents($filepath);
            $this->assertNotEmpty($content, "Blueprint file must not be empty: {$code}");

            $data = json_decode($content, true);
            $this->assertIsArray($data, "Blueprint JSON must parse to array: {$code}");

            // Verify top-level structure
            $this->assertArrayHasKey('industry', $data, "Missing 'industry' key in: {$code}");
            $this->assertEquals($code, $data['industry']['code'] ?? null, "Industry code mismatch in: {$code}");
            $this->assertNotEmpty($data['industry']['name'] ?? null, "Missing name in: {$code}");
            $this->assertNotEmpty($data['industry']['icon'] ?? null, "Missing icon in: {$code}");

            $this->assertArrayHasKey('terminology', $data, "Missing 'terminology' in: {$code}");
            $this->assertArrayHasKey('enabled_modules', $data, "Missing 'enabled_modules' in: {$code}");
            $this->assertArrayHasKey('default_settings', $data, "Missing 'default_settings' in: {$code}");
        }
    }

    /**
     * Test sector-specific terminology mapping.
     */
    public function testSectorSpecificTerminology(): void
    {
        // 1. Dentist / Diş Hekimi
        $dentist = json_decode(file_get_contents($this->blueprintsPath . 'dentist.json'), true);
        $this->assertEquals('Hasta', $dentist['terminology']['customer_label']);
        $this->assertStringContainsString('Hekim', $dentist['terminology']['provider_label']);
        $this->assertStringContainsString('Ünit', $dentist['terminology']['station_label']);

        // 2. Restaurant / Restoran
        $restaurant = json_decode(file_get_contents($this->blueprintsPath . 'restaurant.json'), true);
        $this->assertEquals('Misafir', $restaurant['terminology']['customer_label']);
        $this->assertStringContainsString('Masa', $restaurant['terminology']['station_label']);
        $this->assertStringContainsString('Rezervasyon', $restaurant['terminology']['appointment_label']);

        // 3. Gym / Spor Salonu
        $gym = json_decode(file_get_contents($this->blueprintsPath . 'gym.json'), true);
        $this->assertStringContainsString('Üye', $gym['terminology']['customer_label']);
        $this->assertStringContainsString('Antrenör', $gym['terminology']['provider_label']);

        // 4. Car Wash / Oto Yıkama
        $carWash = json_decode(file_get_contents($this->blueprintsPath . 'car_wash.json'), true);
        $this->assertStringContainsString('Araç', $carWash['terminology']['customer_label']);
        $this->assertStringContainsString('Peron', $carWash['terminology']['station_label']);
    }

    /**
     * Test module segmentation across sectors.
     */
    public function testModuleSegmentationAcrossSectors(): void
    {
        $restaurant = json_decode(file_get_contents($this->blueprintsPath . 'restaurant.json'), true);
        $dentist = json_decode(file_get_contents($this->blueprintsPath . 'dentist.json'), true);
        $barber = json_decode(file_get_contents($this->blueprintsPath . 'barber.json'), true);
        $gym = json_decode(file_get_contents($this->blueprintsPath . 'gym.json'), true);

        // Restaurant must have restaurant_floor_plan & restaurant_reservations
        $this->assertContains('restaurant_floor_plan', $restaurant['enabled_modules']);
        $this->assertContains('restaurant_reservations', $restaurant['enabled_modules']);

        // Dentist, Barber, Gym must NOT have restaurant_floor_plan
        $this->assertNotContains('restaurant_floor_plan', $dentist['enabled_modules']);
        $this->assertNotContains('restaurant_floor_plan', $barber['enabled_modules']);
        $this->assertNotContains('restaurant_floor_plan', $gym['enabled_modules']);

        // Gym must have memberships and checkin
        $this->assertContains('memberships', $gym['enabled_modules']);
        $this->assertContains('checkin', $gym['enabled_modules']);

        // Barber should not have memberships by default
        $this->assertNotContains('memberships', $barber['enabled_modules']);
    }

    /**
     * Test industry helper functions if loaded.
     */
    public function testIndustryHelperFunctions(): void
    {
        $helperPath = dirname(__DIR__, 2) . '/application/helpers/industry_helper.php';
        $this->assertFileExists($helperPath);

        if (!function_exists('industry_dashboard_config')) {
            require_once $helperPath;
        }

        $restaurantConfig = industry_dashboard_config('restaurant');
        $this->assertArrayHasKey('badge', $restaurantConfig);
        $this->assertStringContainsString('Restoran', $restaurantConfig['badge']);
        $this->assertStringContainsString('Rezervasyon', $restaurantConfig['kpi_1_title']);
        $this->assertNotEmpty($restaurantConfig['quick_actions']);

        $dentistConfig = industry_dashboard_config('dentist');
        $this->assertStringContainsString('Hasta', $dentistConfig['kpi_1_title']);
        $this->assertStringContainsString('Ünit', $dentistConfig['kpi_4_title']);

        $gymConfig = industry_dashboard_config('gym');
        $this->assertStringContainsString('Ders', $gymConfig['kpi_1_title']);
        $this->assertStringContainsString('Stüdyo', $gymConfig['kpi_4_title']);
    }

    /**
     * Test blueprint loading and terminology resolution for different sectors.
     */
    public function testBlueprintLoadingAndTerminologyResolution(): void
    {
        $dentistBp = current_industry_blueprint('dentist');
        $this->assertNotNull($dentistBp);
        $this->assertEquals('dentist', $dentistBp['industry']['code']);
        $this->assertEquals('Hasta', $dentistBp['terminology']['customer_label']);
        $this->assertEquals('Tedavi / İşlem', $dentistBp['terminology']['service_label']);

        $restaurantBp = current_industry_blueprint('restaurant');
        $this->assertNotNull($restaurantBp);
        $this->assertEquals('restaurant', $restaurantBp['industry']['code']);
        $this->assertEquals('Misafir', $restaurantBp['terminology']['customer_label']);
        $this->assertEquals('Masa Rezervasyonu', $restaurantBp['terminology']['appointment_label']);

        $carWashBp = current_industry_blueprint('car_wash');
        $this->assertNotNull($carWashBp);
        $this->assertEquals('car_wash', $carWashBp['industry']['code']);
        $this->assertEquals('Yıkama Peronu / Detailing Alanı', $carWashBp['terminology']['station_label']);
    }
}
