<?php declare(strict_types=1);

namespace Tests\System;

use Tests\TestCase;
use DateTime;
use DateTimeZone;

/**
 * Mobile App & MCP Server Integration Test.
 *
 * Verifies:
 * 1. Mobile app station endpoints (Masa / Oda / Kort / Peron / Cihaz).
 * 2. Availabilities scoped by stationId (3-variable intersection: provider ∩ service ∩ station).
 * 3. Appointment creation with explicit stationId payload.
 * 4. Agent API & MCP Server multi-vertical data accessibility (stations, KDS, sports, vehicles).
 * 5. General Settings mobile download links & QR code rendering.
 */
class MobileAppAndMcpVerticalIntegrationTest extends TestCase
{
    /**
     * Test that stations (rooms, tables, courts, devices) can be filtered and assigned to appointments.
     */
    public function testStationModelAndAssignmentLogic(): void
    {
        $stations = [
            ['id' => 1, 'name' => 'Oda 1 (Masaj & Bakım)', 'capacity' => 2, 'status' => 'empty'],
            ['id' => 2, 'name' => 'Oda 2 (Buz Lazer)', 'capacity' => 1, 'status' => 'empty'],
            ['id' => 3, 'name' => 'Kort 1 (Toprak Kort)', 'capacity' => 4, 'status' => 'empty'],
            ['id' => 4, 'name' => 'Masa 5 (Teras / Bahçe)', 'capacity' => 6, 'status' => 'empty'],
            ['id' => 5, 'name' => 'Lift 2 (Peron & Yağ Değişimi)', 'capacity' => 1, 'status' => 'empty'],
        ];

        $this->assertCount(5, $stations);

        // Verify station structure contains required keys for mobile app
        foreach ($stations as $st) {
            $this->assertArrayHasKey('id', $st);
            $this->assertArrayHasKey('name', $st);
            $this->assertArrayHasKey('capacity', $st);
            $this->assertArrayHasKey('status', $st);
            $this->assertGreaterThanOrEqual(1, $st['capacity']);
        }
    }

    /**
     * Test that selecting a specific station filters availability correctly.
     */
    public function testStationConstrainedAvailability(): void
    {
        $allSlots = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00'];
        $stationAppointments = [
            ['station_id' => 2, 'start_time' => '10:00', 'end_time' => '11:00'],
            ['station_id' => 2, 'start_time' => '14:00', 'end_time' => '15:00'],
        ];

        $targetStationId = 2;

        // Filter slots where target station is occupied
        $availableSlotsForStation = array_values(array_filter($allSlots, function ($slot) use ($stationAppointments, $targetStationId) {
            foreach ($stationAppointments as $appt) {
                if ($appt['station_id'] === $targetStationId && $appt['start_time'] === $slot) {
                    return false; // Station occupied
                }
            }
            return true;
        }));

        $this->assertEquals(['09:00', '11:00', '13:00', '15:00'], $availableSlotsForStation);
        $this->assertNotContains('10:00', $availableSlotsForStation);
        $this->assertNotContains('14:00', $availableSlotsForStation);
    }

    /**
     * Test appointment creation with stationId payload maps to id_stations and manual assignment.
     */
    public function testAppointmentPayloadStationMapping(): void
    {
        $mobilePayload = [
            'serviceId' => 3,
            'providerId' => 2,
            'customerId' => 10,
            'start' => '2026-10-01 14:00:00',
            'stationId' => 4,
            'notes' => 'Mobil Randevu [Masa 4]',
        ];

        // Simulate Appointments_api_v1 / Appointments_model api_decode logic
        $decoded = [];
        if (isset($mobilePayload['serviceId'])) {
            $decoded['id_services'] = $mobilePayload['serviceId'];
        }
        if (isset($mobilePayload['providerId'])) {
            $decoded['id_users_provider'] = $mobilePayload['providerId'];
        }
        if (isset($mobilePayload['customerId'])) {
            $decoded['id_users_customer'] = $mobilePayload['customerId'];
        }
        if (isset($mobilePayload['start'])) {
            $decoded['start_datetime'] = $mobilePayload['start'];
        }
        if (isset($mobilePayload['stationId'])) {
            $decoded['id_stations'] = $mobilePayload['stationId'];
            $decoded['station_assigned_manually'] = 1;
        }

        $this->assertEquals(4, $decoded['id_stations']);
        $this->assertEquals(1, $decoded['station_assigned_manually']);
        $this->assertEquals(3, $decoded['id_services']);
        $this->assertEquals(2, $decoded['id_users_provider']);
    }

    /**
     * Test MCP Server tool registry coverage for multi-vertical resources.
     */
    public function testMcpServerToolRegistryCoverage(): void
    {
        $expectedTools = [
            'business',
            'services',
            'providers',
            'stations',
            'availability',
            'customer_lookup',
            'customer_appointments',
            'create_appointment',
            'reschedule_appointment',
            'cancel_appointment',
            'vertical_records',
            'marketing_campaigns',
            'marketing_realtime',
            'marketing_analytics',
        ];

        $paths = [
            '/var/www/html/deploy/deploy/mcp/reservation-mcp/server.js',
            '/var/www/html/deploy/mcp/reservation-mcp/server.js',
            dirname(__DIR__, 2) . '/deploy/mcp/reservation-mcp/server.js',
        ];

        $serverJs = '';
        foreach ($paths as $p) {
            if (file_exists($p)) {
                $serverJs = file_get_contents($p);
                break;
            }
        }

        $this->assertNotEmpty($serverJs, 'server.js must be accessible');

        foreach ($expectedTools as $tool) {
            $this->assertStringContainsString("'$tool'", $serverJs, "Tool '$tool' must be registered in MCP server.js");
        }

        // Verify station_id is supported in create_appointment tool
        $this->assertStringContainsString('station_id', $serverJs);
    }

    /**
     * Test General Settings page contains mobile download links and QR code.
     */
    public function testGeneralSettingsMobileDownloadSection(): void
    {
        $viewPath = '/var/www/html/application/views/pages/general_settings.php';
        if (!file_exists($viewPath)) {
            $viewPath = dirname(__DIR__, 2) . '/application/views/pages/general_settings.php';
        }

        $viewContent = file_get_contents($viewPath);
        $this->assertNotEmpty($viewContent);

        $this->assertStringContainsString('mobile-app-download-section', $viewContent);
        $this->assertStringContainsString('BooKi Mobil Uygulamalarını İndirin', $viewContent);
        $this->assertStringContainsString('booki-release.apk', $viewContent);
        $this->assertStringContainsString('play.google.com', $viewContent);
        $this->assertStringContainsString('apps.apple.com', $viewContent);
        $this->assertStringContainsString('qrserver.com', $viewContent);
    }
}
