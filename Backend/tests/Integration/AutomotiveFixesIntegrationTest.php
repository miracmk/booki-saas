<?php declare(strict_types=1);

namespace Tests\Integration;

use Tests\TenantTestCase;
use RuntimeException;
use InvalidArgumentException;

/**
 * Integration Test for Sector 5 (automotive - Oto Servis, Ekspertiz, Detailing, DVI & İş Emri) Backend Fixes.
 * Covers Vehicles_model, Work_orders_model, Verticals controller web endpoints,
 * and Verticals_api_v1 API authorization & lifecycle.
 */
class AutomotiveFixesIntegrationTest extends TenantTestCase
{
    private static int $technicianId;
    private static int $customerId;
    private static int $otherCustomerId;
    private static int $appointmentId;

    protected function setUp(): void
    {
        parent::setUp();
        self::ci()->load->model('vehicles_model');
        self::ci()->load->model('work_orders_model');
        self::ci()->load->model('customers_model');

        $db = self::db();

        // 1. Staff / Technician User
        $technician = $db->get_where('users', ['email' => 'usta_mehmet_test@booki.local'])->row_array();
        if (!$technician) {
            $db->insert('users', [
                'first_name' => 'Mehmet',
                'last_name' => 'Usta',
                'email' => 'usta_mehmet_test@booki.local',
                'phone_number' => '05551110022',
                'id_roles' => 2,
                'role_slug' => 'provider',
            ]);
            self::$technicianId = (int) $db->insert_id();
        } else {
            self::$technicianId = (int) $technician['id'];
        }

        // 2. Customer User (Vehicle Owner)
        $customer = $db->get_where('users', ['email' => 'ahmet_arac_sahibi@booki.local'])->row_array();
        if (!$customer) {
            $db->insert('users', [
                'first_name' => 'Ahmet',
                'last_name' => 'Sahip',
                'email' => 'ahmet_arac_sahibi@booki.local',
                'phone_number' => '05553334455',
                'id_roles' => 3,
                'role_slug' => 'customer',
            ]);
            self::$customerId = (int) $db->insert_id();
        } else {
            self::$customerId = (int) $customer['id'];
        }

        // 3. Second Customer User (Other Owner)
        $otherCustomer = $db->get_where('users', ['email' => 'selin_diger_arac@booki.local'])->row_array();
        if (!$otherCustomer) {
            $db->insert('users', [
                'first_name' => 'Selin',
                'last_name' => 'Diger',
                'email' => 'selin_diger_arac@booki.local',
                'phone_number' => '05556667788',
                'id_roles' => 3,
                'role_slug' => 'customer',
            ]);
            self::$otherCustomerId = (int) $db->insert_id();
        } else {
            self::$otherCustomerId = (int) $otherCustomer['id'];
        }

        // 4. Sample Service Appointment
        $appt = $db->get_where('appointments', [
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$technicianId,
            'notes' => 'Automotive test appointment',
        ])->row_array();

        if (!$appt) {
            $db->insert('appointments', [
                'start_datetime' => date('Y-m-d H:i:s', strtotime('+1 day 09:00:00')),
                'end_datetime' => date('Y-m-d H:i:s', strtotime('+1 day 10:30:00')),
                'is_unavailability' => 0,
                'id_users_customer' => self::$customerId,
                'id_users_provider' => self::$technicianId,
                'id_services' => 1,
                'hash' => md5(uniqid('auto_appt', true)),
                'notes' => 'Automotive test appointment',
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
        $controller->load->model('vehicles_model');
        $controller->load->model('work_orders_model');
        $controller->load->model('customers_model');
        return $controller;
    }

    private function createVerticalsApiController(): \Verticals_api_v1
    {
        require_once APPPATH . 'controllers/api/v1/Verticals_api_v1.php';
        $ci = self::ci();
        $api = (new \ReflectionClass(\Verticals_api_v1::class))->newInstanceWithoutConstructor();
        $api->props = &$ci->props;
        $api->load = $ci->load;
        $api->load->model('vehicles_model');
        $api->load->model('work_orders_model');
        return $api;
    }

    /**
     * Test 1: Verify Vehicles_model normalizes plate numbers and persists vehicle correctly.
     */
    public function testVehiclesModelPlateNormalizationAndPersistence(): void
    {
        $model = self::ci()->vehicles_model;
        $db = self::db();

        $plateRaw = ' 34  abC-1234 ';
        $vehicleId = $model->add_vehicle([
            'id_users_customer' => self::$customerId,
            'plate_number' => $plateRaw,
            'vin' => 'wba1234567890abcd',
            'brand' => 'BMW',
            'model' => '320i Sedan',
            'year' => 2022,
            'color' => 'Alpine White',
            'current_km' => 45200,
            'fuel_type' => 'gasoline',
            'notes' => 'Periyodik 45bin bakımı yapılacak.',
        ]);

        $this->assertGreaterThan(0, $vehicleId);

        $row = $db->get_where('customer_vehicles', ['id' => $vehicleId])->row_array();
        $this->assertNotNull($row);
        $this->assertSame('34ABC1234', $row['plate_number'], 'Plate must be normalized to uppercase alphanumeric');
        $this->assertSame('WBA1234567890ABCD', $row['vin'], 'VIN must be uppercase');
        $this->assertSame('BMW', $row['brand']);
        $this->assertSame('320i Sedan', $row['model']);
        $this->assertEquals(2022, (int) $row['year']);
        $this->assertEquals(45200, (int) $row['current_km']);
        $this->assertSame('Alpine White', $row['color']);

        // Clean up
        $db->where('id', $vehicleId)->delete('customer_vehicles');
    }

    /**
     * Test 2: Verify Vehicles_model validation on missing required fields.
     */
    public function testVehiclesModelValidationThrowsOnMissingFields(): void
    {
        $model = self::ci()->vehicles_model;

        // Missing customer
        $this->expectException(InvalidArgumentException::class);
        $model->add_vehicle([
            'plate_number' => '34XYZ99',
            'brand' => 'Audi',
            'model' => 'A4',
        ]);
    }

    /**
     * Test 3: Verify Vehicles_model lookups by plate, customer, get_all, and updates.
     */
    public function testVehiclesModelRetrievalAndUpdates(): void
    {
        $model = self::ci()->vehicles_model;
        $db = self::db();

        $vId = $model->add_vehicle([
            'id_users_customer' => self::$customerId,
            'plate_number' => '06ANK001',
            'brand' => 'Mercedes-Benz',
            'model' => 'C200',
            'year' => 2021,
            'current_km' => 30000,
        ]);

        // 1. Lookup by plate (with spacing/casing variations)
        $byPlate = $model->get_by_plate('06 ank 001');
        $this->assertNotNull($byPlate);
        $this->assertEquals($vId, (int) $byPlate['id']);

        // 2. Lookup by customer
        $byCust = $model->get_by_customer(self::$customerId);
        $this->assertNotEmpty($byCust);
        $found = false;
        foreach ($byCust as $cVehicle) {
            if ((int) $cVehicle['id'] === $vId) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Vehicle must be returned in customer vehicle list');

        // 3. Lookup get_all
        $all = $model->get_all(50);
        $this->assertNotEmpty($all);

        // 4. Update mileage and color
        $upRes = $model->update_vehicle($vId, [
            'current_km' => 35000,
            'color' => 'Siyah',
        ]);
        $this->assertTrue($upRes);

        $fresh = $db->get_where('customer_vehicles', ['id' => $vId])->row_array();
        $this->assertEquals(35000, (int) $fresh['current_km']);
        $this->assertSame('Siyah', $fresh['color']);

        // Clean up
        $db->where('id', $vId)->delete('customer_vehicles');
    }

    /**
     * Test 4: Verify Work_orders_model creation with custom or auto-generated number, items and costs.
     */
    public function testWorkOrderCreationAndAutoNumbering(): void
    {
        $vModel = self::ci()->vehicles_model;
        $woModel = self::ci()->work_orders_model;
        $db = self::db();

        $vId = $vModel->add_vehicle([
            'id_users_customer' => self::$customerId,
            'plate_number' => '35IZM35',
            'brand' => 'Volkswagen',
            'model' => 'Golf 8',
        ]);

        $laborItems = [
            ['title' => 'Yağ ve Filtre Değişimi', 'hours' => 1.5, 'rate' => 600.00, 'total' => 900.00],
            ['title' => 'Ön Fren Balata Değişimi', 'hours' => 1.0, 'rate' => 600.00, 'total' => 600.00],
        ];

        $partsItems = [
            ['part_number' => 'OIL-5W30-4L', 'name' => 'Castrol Edge 5W30', 'qty' => 1, 'unit_price' => 1200.00, 'total' => 1200.00],
            ['part_number' => 'BRK-VW-01', 'name' => 'Brembo Ön Balata Seti', 'qty' => 1, 'unit_price' => 1800.00, 'total' => 1800.00],
        ];

        $wo = $woModel->create_work_order([
            'id_vehicles' => $vId,
            'id_appointments' => self::$appointmentId,
            'id_users_technician' => self::$technicianId,
            'status' => 'created',
            'estimated_cost' => 4500.00,
            'final_cost' => 4500.00,
            'labor_items' => $laborItems,
            'parts_items' => $partsItems,
        ]);

        $this->assertNotEmpty($wo['id']);
        $this->assertStringStartsWith('WO-' . date('Y') . '-', $wo['work_order_number']);
        $this->assertSame('created', $wo['status']);

        // Check via get_by_id
        $fetched = $woModel->get_by_id((int) $wo['id']);
        $this->assertNotNull($fetched);
        $this->assertSame('35IZM35', $fetched['plate_number']);
        $this->assertSame('Mehmet', $fetched['technician_first_name']);
        $this->assertCount(2, $fetched['labor_items']);
        $this->assertCount(2, $fetched['parts_items']);
        $this->assertEquals(4500.00, (float) $fetched['estimated_cost']);

        // Clean up
        $db->where('id', $wo['id'])->delete('work_orders');
        $db->where('id', $vId)->delete('customer_vehicles');
    }

    /**
     * Test 5: Verify Work Order complete lifecycle:
     * created -> in_progress -> ready -> delivered (delivery_datetime set).
     */
    public function testWorkOrderLifecycleStatusPipeline(): void
    {
        $vModel = self::ci()->vehicles_model;
        $woModel = self::ci()->work_orders_model;
        $db = self::db();

        $vId = $vModel->add_vehicle([
            'id_users_customer' => self::$customerId,
            'plate_number' => '34LIFECYCLE',
            'brand' => 'Renault',
            'model' => 'Megane',
        ]);

        $wo = $woModel->create_work_order([
            'id_vehicles' => $vId,
            'status' => 'created',
        ]);
        $woId = (int) $wo['id'];

        // 1. Stage: created -> in_progress
        $ok1 = $woModel->update_status($woId, 'in_progress');
        $this->assertTrue($ok1);
        $row1 = $db->get_where('work_orders', ['id' => $woId])->row_array();
        $this->assertSame('in_progress', $row1['status']);
        $this->assertNull($row1['delivery_datetime']);

        // 2. Stage: in_progress -> ready
        $ok2 = $woModel->update_status($woId, 'ready');
        $this->assertTrue($ok2);
        $row2 = $db->get_where('work_orders', ['id' => $woId])->row_array();
        $this->assertSame('ready', $row2['status']);
        $this->assertNull($row2['delivery_datetime']);

        // 3. Stage: ready -> delivered
        $ok3 = $woModel->update_status($woId, 'delivered');
        $this->assertTrue($ok3);
        $row3 = $db->get_where('work_orders', ['id' => $woId])->row_array();
        $this->assertSame('delivered', $row3['status']);
        $this->assertNotNull($row3['delivery_datetime'], 'Delivered status must record delivery timestamp');

        // 4. Invalid status throws InvalidArgumentException
        $invalidThrown = false;
        try {
            $woModel->update_status($woId, 'flying_car_mode');
        } catch (InvalidArgumentException $e) {
            $invalidThrown = true;
        }
        $this->assertTrue($invalidThrown, 'Invalid stage name must be rejected');

        // 5. Non-existent work order throws InvalidArgumentException
        $nonExistentThrown = false;
        try {
            $woModel->update_status(9999999, 'in_progress');
        } catch (InvalidArgumentException $e) {
            $nonExistentThrown = true;
        }
        $this->assertTrue($nonExistentThrown, 'Non-existent work order must throw InvalidArgumentException');

        // Clean up
        $db->where('id', $woId)->delete('work_orders');
        $db->where('id', $vId)->delete('customer_vehicles');
    }

    /**
     * Test 6: Verify DVI Inspection saving, 32-character token generation, report fetching and approval.
     */
    public function testDviInspectionLifecycleWithTokenAndApproval(): void
    {
        $vModel = self::ci()->vehicles_model;
        $woModel = self::ci()->work_orders_model;
        $db = self::db();

        $vId = $vModel->add_vehicle([
            'id_users_customer' => self::$customerId,
            'plate_number' => '34DVI99',
            'brand' => 'Volvo',
            'model' => 'XC60',
            'year' => 2023,
        ]);

        $checklist = [
            ['item' => 'Motor Yağı Seviyesi', 'status' => 'pass', 'notes' => 'Tam ve temiz'],
            ['item' => 'Fren Balataları', 'status' => 'warning', 'notes' => '%30 kaldı, sonraki bakımda değişim'],
            ['item' => 'Lastik Diş Derinliği', 'status' => 'pass', 'notes' => '5.2 mm'],
            ['item' => 'Akü Sağlığı', 'status' => 'pass', 'notes' => '%92 iyi durumda'],
        ];

        $inspection = $woModel->save_inspection([
            'id_vehicles' => $vId,
            'id_appointments' => self::$appointmentId,
            'inspector_id' => self::$technicianId,
            'inspection_type' => 'full_expertise',
            'overall_score' => 88,
            'items' => $checklist,
        ]);

        $this->assertGreaterThan(0, $inspection['id']);
        $this->assertNotEmpty($inspection['customer_shared_token']);
        $this->assertEquals(32, strlen($inspection['customer_shared_token']), 'Customer share token must be a 32-char hex string');
        $token = $inspection['customer_shared_token'];

        // 1. Fetch by token (public customer view)
        $report = $woModel->get_inspection_by_token($token);
        $this->assertNotNull($report);
        $this->assertSame('34DVI99', $report['plate_number']);
        $this->assertSame('Volvo', $report['brand']);
        $this->assertSame('Mehmet', $report['inspector_first_name']);
        $this->assertEquals(88, (int) $report['overall_score']);
        $this->assertIsArray($report['items']);
        $this->assertCount(4, $report['items']);
        $this->assertNull($report['customer_approved_at']);

        // 2. Customer digital approval
        $apprRes = $woModel->approve_inspection_by_token($token);
        $this->assertTrue($apprRes);

        $approvedReport = $woModel->get_inspection_by_token($token);
        $this->assertNotNull($approvedReport['customer_approved_at']);

        // 3. Approve invalid token throws InvalidArgumentException
        $invalidTokenThrown = false;
        try {
            $woModel->approve_inspection_by_token('non_existent_token_12345');
        } catch (InvalidArgumentException $e) {
            $invalidTokenThrown = true;
        }
        $this->assertTrue($invalidTokenThrown);

        // Clean up
        $db->where('id', $inspection['id'])->delete('vehicle_inspections');
        $db->where('id', $vId)->delete('customer_vehicles');
    }

    /**
     * Test 7: Verify Verticals controller web endpoints authorization & functionality:
     * add_vehicle, create_work_order, update_work_order_status, save_vehicle_inspection, automotive view technicians.
     */
    public function testVerticalsWebControllerEndpoints(): void
    {
        $controller = $this->createVerticalsController();
        $db = self::db();

        // 7.1 Unauthenticated access throws 401 RuntimeException in testing
        session(['user_id' => null, 'role_slug' => null]);
        $endpoints = ['add_vehicle', 'create_work_order', 'update_work_order_status', 'save_vehicle_inspection'];
        foreach ($endpoints as $ep) {
            $unauthThrown = false;
            try {
                $controller->{$ep}();
            } catch (\Throwable $e) {
                $unauthThrown = true;
                $this->assertEquals(401, $e->getCode(), "Endpoint $ep must throw 401 when unauthenticated");
            }
            $this->assertTrue($unauthThrown);
        }

        // 7.2 Customer role throws 403 Forbidden in testing
        session(['user_id' => self::$customerId, 'role_slug' => 'customer']);
        foreach ($endpoints as $ep) {
            $forbiddenThrown = false;
            try {
                $controller->{$ep}();
            } catch (\Throwable $e) {
                $forbiddenThrown = true;
                $this->assertEquals(403, $e->getCode(), "Endpoint $ep must throw 403 for customer");
            }
            $this->assertTrue($forbiddenThrown);
        }

        // 7.3 Login as Staff / Technician
        session(['user_id' => self::$technicianId, 'role_slug' => 'provider']);

        // 7.4 add_vehicle: Validation error on empty fields
        $_POST = [];
        $controller->add_vehicle();
        $valResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertFalse($valResp['success']);

        // 7.5 add_vehicle: Success
        $_POST = [
            'id_users_customer' => self::$customerId,
            'plate_number' => '34WEB01',
            'brand' => 'Toyota',
            'model' => 'Corolla Hybrid',
            'year' => 2023,
            'current_km' => 15000,
        ];
        $controller->add_vehicle();
        $addResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($addResp['success']);
        $this->assertGreaterThan(0, $addResp['vehicle_id']);
        $webVehicleId = (int) $addResp['vehicle_id'];

        // 7.6 create_work_order: Validation error on missing vehicle
        $_POST = [];
        $controller->create_work_order();
        $woValResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertFalse($woValResp['success']);

        // 7.7 create_work_order: Success
        $_POST = [
            'id_vehicles' => $webVehicleId,
            'id_users_technician' => self::$technicianId,
            'customer_complaint' => 'Frenlerden ses geliyor.',
            'estimated_cost' => 1500.00,
        ];
        $controller->create_work_order();
        $woCreateResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($woCreateResp['success']);
        $this->assertNotEmpty($woCreateResp['work_order']['work_order_number']);
        $webWoId = (int) $woCreateResp['work_order']['id'];

        // 7.8 update_work_order_status: Validation error on missing status
        $_POST = ['work_order_id' => $webWoId];
        $controller->update_work_order_status();
        $upValResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertFalse($upValResp['success']);

        // 7.9 update_work_order_status: Success
        $_POST = [
            'work_order_id' => $webWoId,
            'status' => 'in_progress',
        ];
        $controller->update_work_order_status();
        $upResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($upResp['success']);
        $this->assertSame('in_progress', $upResp['status']);

        // 7.10 save_vehicle_inspection: Success
        $_POST = [
            'id_vehicles' => $webVehicleId,
            'inspection_type' => 'general_service',
            'overall_score' => 95,
            'items' => [
                ['name' => 'Fren Diski', 'status' => 'pass'],
            ],
        ];
        $controller->save_vehicle_inspection();
        $inspResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($inspResp['success']);
        $this->assertNotEmpty($inspResp['token']);
        $webInspId = (int) $inspResp['inspection']['id'];

        // Clean up
        $db->where('id', $webInspId)->delete('vehicle_inspections');
        $db->where('id', $webWoId)->delete('work_orders');
        $db->where('id', $webVehicleId)->delete('customer_vehicles');
    }

    /**
     * Test 8: Verify Verticals_api_v1 authorization, scoping and validation for automotive endpoints.
     */
    public function testVerticalsApiAuthGatingAndValidation(): void
    {
        $api = $this->createVerticalsApiController();
        $db = self::db();

        // 8.1 Unauthenticated access -> 401
        session(['user_id' => null, 'role_slug' => null]);
        $_POST = [];
        $api->add_vehicle();
        $resp401 = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('error', $resp401);
        $this->assertSame('Kimlik doğrulama gereklidir.', $resp401['error']);

        $api->create_work_order();
        $resp401Wo = json_decode(self::ci()->output->get_output(), true);
        $this->assertSame('Kimlik doğrulama gereklidir.', $resp401Wo['error']);

        $api->update_work_order_status(1);
        $resp401Up = json_decode(self::ci()->output->get_output(), true);
        $this->assertSame('Kimlik doğrulama gereklidir.', $resp401Up['error']);

        $api->save_vehicle_inspection();
        $resp401Insp = json_decode(self::ci()->output->get_output(), true);
        $this->assertSame('Kimlik doğrulama gereklidir.', $resp401Insp['error']);

        // 8.2 Customer role restricted from staff actions (create_work_order, update_work_order_status, save_vehicle_inspection)
        session(['user_id' => self::$customerId, 'role_slug' => 'customer']);

        $api->create_work_order();
        $custWoResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertSame('İş emri oluşturma yetkiniz bulunmamaktadır.', $custWoResp['error']);

        $api->update_work_order_status(1);
        $custUpResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertSame('İş emri durumunu güncelleme yetkiniz bulunmamaktadır.', $custUpResp['error']);

        $api->save_vehicle_inspection();
        $custInspResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertSame('Ekspertiz kaydetme yetkiniz bulunmamaktadır.', $custInspResp['error']);

        // 8.3 Customer adding vehicle: forbidden if trying to register for ANOTHER customer
        $_POST = [
            'id_users_customer' => self::$otherCustomerId,
            'plate_number' => '34HACK01',
            'brand' => 'Audi',
            'model' => 'RS6',
        ];
        $api->add_vehicle();
        $custOtherAddResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertSame('Diğer müşteriler adına araç kaydedemezsiniz.', $custOtherAddResp['error']);

        // 8.4 Customer adding vehicle: permitted for self
        $_POST = [
            'plate_number' => '34CUST01',
            'brand' => 'Honda',
            'model' => 'Civic',
            'year' => 2020,
        ];
        $api->add_vehicle();
        $custSelfAddResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($custSelfAddResp['success']);
        $apiVehicleId = (int) $custSelfAddResp['vehicle_id'];

        // 8.5 Customer retrieving customer vehicles: forbidden for other customer, allowed for self
        $api->get_customer_vehicles(self::$otherCustomerId);
        $otherVehResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertSame('Diğer müşterilerin araçlarına erişim yetkiniz yoktur.', $otherVehResp['error']);

        $api->get_customer_vehicles(self::$customerId);
        $ownVehResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('vehicles', $ownVehResp);

        // 8.6 Staff / Technician operations
        session(['user_id' => self::$technicianId, 'role_slug' => 'provider']);

        // Staff creates work order
        $_POST = [
            'id_vehicles' => $apiVehicleId,
            'estimated_cost' => 2200.00,
            'status' => 'created',
        ];
        $api->create_work_order();
        $staffWoResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($staffWoResp['success']);
        $apiWoId = (int) $staffWoResp['work_order']['id'];

        // Staff updates status
        $_POST = ['status' => 'ready'];
        $api->update_work_order_status($apiWoId);
        $staffUpResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($staffUpResp['success']);
        $this->assertSame('ready', $staffUpResp['status']);

        // Staff saves DVI inspection
        $_POST = [
            'id_vehicles' => $apiVehicleId,
            'inspection_type' => 'detailing',
            'overall_score' => 90,
            'items' => [['check' => 'Boya Kalınlığı', 'status' => 'pass']],
        ];
        $api->save_vehicle_inspection();
        $staffInspResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($staffInspResp['success']);
        $this->assertNotEmpty($staffInspResp['inspection']['customer_shared_token']);
        $shareToken = $staffInspResp['inspection']['customer_shared_token'];
        $apiInspId = (int) $staffInspResp['inspection']['id'];

        // 8.7 Public DVI report access & approval (No login required)
        session(['user_id' => null, 'role_slug' => null]);

        $api->public_inspection_report($shareToken);
        $publicReportResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertEquals(90, (int) $publicReportResp['overall_score']);
        $this->assertSame('34CUST01', $publicReportResp['plate_number']);

        $api->approve_inspection($shareToken);
        $publicApproveResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($publicApproveResp['success']);

        // Clean up
        $db->where('id', $apiInspId)->delete('vehicle_inspections');
        $db->where('id', $apiWoId)->delete('work_orders');
        $db->where('id', $apiVehicleId)->delete('customer_vehicles');
    }
}
