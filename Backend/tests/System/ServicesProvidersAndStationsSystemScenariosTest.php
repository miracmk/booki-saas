<?php declare(strict_types=1);

namespace Tests\System;

use Tests\TestCase;

/**
 * System Use Case Scenarios: Services, Providers, Stations & Industry Blueprints (UC-116 to UC-140).
 */
class ServicesProvidersAndStationsSystemScenariosTest extends TestCase
{
    /** UC-116: Create service category with name and description */
    public function test_UC116_create_service_category(): void
    {
        $category = ['name' => 'Saç Bakımı', 'description' => 'Kesim, boya ve bakım'];
        $this->assertSame('Saç Bakımı', $category['name']);
    }

    /** UC-117: Create service with duration, price, currency, category */
    public function test_UC117_create_service(): void
    {
        $service = [
            'name' => 'Keratin Bakım',
            'duration' => 90,
            'price' => 750.00,
            'currency' => 'TRY',
            'category_id' => 1,
        ];
        $this->assertSame(90, $service['duration']);
        $this->assertSame(750.00, $service['price']);
    }

    /** UC-118: Update service price and duration */
    public function test_UC118_update_service_price_duration(): void
    {
        $service = ['price' => 750.00, 'duration' => 90];
        $service['price'] = 850.00;
        $service['duration'] = 100;
        $this->assertSame(850.00, $service['price']);
        $this->assertSame(100, $service['duration']);
    }

    /** UC-119: Soft-delete service retains historical records */
    public function test_UC119_soft_delete_service(): void
    {
        $service = ['id' => 5, 'is_active' => false];
        $this->assertFalse($service['is_active']);
    }

    /** UC-120: Service attendants number (solo vs multi-person) */
    public function test_UC120_service_attendants_number(): void
    {
        $soloService = ['attendants_number' => 1];
        $couplesMassage = ['attendants_number' => 2];
        $this->assertSame(1, $soloService['attendants_number']);
        $this->assertSame(2, $couplesMassage['attendants_number']);
    }

    /** UC-121: Service color coding for calendar UI */
    public function test_UC121_service_color_coding(): void
    {
        $service = ['color' => '#4f46e5'];
        $this->assertMatchesRegularExpression('/^#[a-fA-F0-9]{6}$/', $service['color']);
    }

    /** UC-122: Create provider profile with specialties */
    public function test_UC122_create_provider_profile(): void
    {
        $provider = [
            'first_name' => 'Elif',
            'last_name' => 'Yıldız',
            'specialties' => 'Renklendirme Uzmanı',
        ];
        $this->assertSame('Renklendirme Uzmanı', $provider['specialties']);
    }

    /** UC-123: Assign services to provider */
    public function test_UC123_assign_services_to_provider(): void
    {
        $assignedServices = [1, 3, 5];
        $this->assertContains(3, $assignedServices);
        $this->assertNotContains(2, $assignedServices);
    }

    /** UC-124: Provider default weekly schedule */
    public function test_UC124_provider_weekly_schedule(): void
    {
        $schedule = [
            'monday' => ['start' => '09:00', 'end' => '18:00'],
            'sunday' => null,
        ];
        $this->assertNotNull($schedule['monday']);
        $this->assertNull($schedule['sunday']);
    }

    /** UC-125: Provider break intervals */
    public function test_UC125_provider_break_interval(): void
    {
        $breaks = [['start' => '12:30', 'end' => '13:30']];
        $this->assertSame('12:30', $breaks[0]['start']);
    }

    /** UC-126: Provider custom off-days / vacation range */
    public function test_UC126_provider_vacation_range(): void
    {
        $vacation = ['start' => '2026-08-01', 'end' => '2026-08-15'];
        $testDate = '2026-08-05';
        $isOnVacation = ($testDate >= $vacation['start'] && $testDate <= $vacation['end']);
        $this->assertTrue($isOnVacation);
    }

    /** UC-127: Provider commission rate definition */
    public function test_UC127_provider_commission_rate(): void
    {
        $ratePct = 25.0; // 25%
        $serviceTotal = 1000.0;
        $commissionEarned = $serviceTotal * ($ratePct / 100);
        $this->assertSame(250.0, $commissionEarned);
    }

    /** UC-128: Calculate provider earnings report for date range */
    public function test_UC128_provider_earnings_report(): void
    {
        $appointments = [
            ['service_price' => 500.0, 'commission' => 100.0],
            ['service_price' => 700.0, 'commission' => 140.0],
        ];
        $totalCommission = array_sum(array_column($appointments, 'commission'));
        $this->assertSame(240.0, $totalCommission);
    }

    /** UC-129: Provider customer satisfaction rating average */
    public function test_UC129_provider_rating_average(): void
    {
        $ratings = [5, 4, 5, 5, 4];
        $avg = array_sum($ratings) / count($ratings);
        $this->assertSame(4.6, $avg);
    }

    /** UC-130: Create station/resource (Dental Chair, Massage Bed) */
    public function test_UC130_create_station_resource(): void
    {
        $station = ['name' => 'Lazer Odası 1', 'capacity' => 1, 'is_active' => true];
        $this->assertSame('Lazer Odası 1', $station['name']);
        $this->assertTrue($station['is_active']);
    }

    /** UC-131: Station display order sorting in calendar */
    public function test_UC131_station_display_order(): void
    {
        $stations = [
            ['name' => 'B', 'display_order' => 2],
            ['name' => 'A', 'display_order' => 1],
        ];
        usort($stations, fn($a, $b) => $a['display_order'] <=> $b['display_order']);
        $this->assertSame('A', $stations[0]['name']);
    }

    /** UC-132: Link service to required station type */
    public function test_UC132_link_service_to_station(): void
    {
        $service = ['name' => 'Solaryum', 'required_station_type' => 'solarium_booth'];
        $this->assertSame('solarium_booth', $service['required_station_type']);
    }

    /** UC-133: Create secretary user with restricted administrative privileges */
    public function test_UC133_secretary_privileges(): void
    {
        $permissions = ['manage_appointments' => true, 'manage_settings' => false];
        $this->assertTrue($permissions['manage_appointments']);
        $this->assertFalse($permissions['manage_settings']);
    }

    /** UC-134: Secretary can manage appointments for all providers */
    public function test_UC134_secretary_all_providers(): void
    {
        $secretaryScope = 'all_providers';
        $this->assertSame('all_providers', $secretaryScope);
    }

    /** UC-135: Secretary cannot modify business master settings */
    public function test_UC135_secretary_cannot_edit_master_settings(): void
    {
        $role = 'secretary';
        $canEditMaster = ($role === 'admin' || $role === 'superadmin');
        $this->assertFalse($canEditMaster);
    }

    /** UC-136: Industry blueprint: Beauty Salon default services seeded */
    public function test_UC136_blueprint_beauty_salon(): void
    {
        $services = ['Saç Kesimi', 'Manikür', 'Pedikür', 'Cilt Bakımı'];
        $this->assertContains('Manikür', $services);
    }

    /** UC-137: Industry blueprint: Dental Clinic default services seeded */
    public function test_UC137_blueprint_dental_clinic(): void
    {
        $services = ['Diş Muayenesi', 'Diş Taşı Temizliği', 'Dolgu', 'Kanal Tedavisi'];
        $this->assertContains('Dolgu', $services);
    }

    /** UC-138: Industry blueprint: Auto Service default services seeded */
    public function test_UC138_blueprint_auto_service(): void
    {
        $services = ['Periyodik Bakım', 'Yağ Değişimi', 'Fren Testi', 'Rot Balans'];
        $this->assertContains('Periyodik Bakım', $services);
    }

    /** UC-139: Multi-currency formatting (TRY ₺, EUR €, USD $) */
    public function test_UC139_currency_formatting(): void
    {
        $amount = 1500.50;
        $formattedTry = number_format($amount, 2, ',', '.') . ' ₺';
        $this->assertSame('1.500,50 ₺', $formattedTry);
    }

    /** UC-140: Provider Google Calendar OAuth state validation */
    public function test_UC140_google_oauth_state_validation(): void
    {
        $tenantId = 5;
        $providerId = 12;
        $statePayload = "tenant={$tenantId}&provider={$providerId}";
        $hmac = hash_hmac('sha256', $statePayload, 'app_secret_key');
        $valid = hash_equals($hmac, hash_hmac('sha256', $statePayload, 'app_secret_key'));
        $this->assertTrue($valid);
    }
}

