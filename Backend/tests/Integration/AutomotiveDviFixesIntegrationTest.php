<?php declare(strict_types=1);

namespace Tests\Integration;

use Tests\TenantTestCase;
use InvalidArgumentException;

/**
 * Integration Test for Sector 5 (automotive - Oto Servis, Ekspertiz, Detailing, DVI & İş Emri) Frontend & Backend Fixes.
 */
class AutomotiveDviFixesIntegrationTest extends TenantTestCase
{
    private static int $customerId;
    private static int $vehicleId;
    private static int $technicianId;

    protected function setUp(): void
    {
        parent::setUp();
        self::ci()->load->model('vehicles_model');
        self::ci()->load->model('work_orders_model');
        self::ci()->load->model('customers_model');

        $db = self::db();

        // 1. Ensure Customer
        $customer = $db->get_where('users', ['email' => 'oto_musteri_test@booki.local'])->row_array();
        if (!$customer) {
            $db->insert('users', [
                'first_name' => 'Ahmet',
                'last_name' => 'OtoMusteri',
                'email' => 'oto_musteri_test@booki.local',
                'phone_number' => '05553334455',
                'id_roles' => 3, // Customer
            ]);
            self::$customerId = (int) $db->insert_id();
        } else {
            self::$customerId = (int) $customer['id'];
        }

        // 2. Ensure Technician / Provider
        $tech = $db->get_where('users', ['email' => 'oto_usta_test@booki.local'])->row_array();
        if (!$tech) {
            $db->insert('users', [
                'first_name' => 'Usta',
                'last_name' => 'Mehmet',
                'email' => 'oto_usta_test@booki.local',
                'phone_number' => '05554445566',
                'id_roles' => 2, // Provider
            ]);
            self::$technicianId = (int) $db->insert_id();
        } else {
            self::$technicianId = (int) $tech['id'];
        }

        // 3. Ensure Customer Vehicle
        $veh = $db->get_where('customer_vehicles', ['plate_number' => '34OTO2026'])->row_array();
        if (!$veh) {
            $db->insert('customer_vehicles', [
                'id_users_customer' => self::$customerId,
                'plate_number' => '34OTO2026',
                'vin' => 'WBA1234567890TEST',
                'brand' => 'Audi',
                'model' => 'A4 2.0 TDI',
                'year' => 2022,
                'color' => 'Gri',
                'current_km' => 78500,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            self::$vehicleId = (int) $db->insert_id();
        } else {
            self::$vehicleId = (int) $veh['id'];
        }
    }

    /**
     * Test 1: Verify save_inspection handles checklist items, overall score, and creates public share token.
     */
    public function testSaveInspectionCreatesValidDviRecord(): void
    {
        $inspection = self::ci()->work_orders_model->save_inspection([
            'id_vehicles' => self::$vehicleId,
            'inspector_id' => self::$technicianId,
            'inspection_type' => 'pre_sale_expertiz',
            'overall_score' => 88,
            'items' => [
                'motor_yagi' => ['status' => 'green', 'note' => 'Yağ seviyesi tam ve temiz'],
                'fren_balatalari' => ['status' => 'yellow', 'note' => 'Ön balatalar %30 kaldı'],
                'lastikler' => ['status' => 'green', 'note' => 'Diş derinliği 6mm'],
                'aku_elektrik' => ['status' => 'green', 'note' => 'Akü 12.6V iyi'],
                'suspansiyon_alt_takim' => ['status' => 'yellow', 'note' => 'Sağ rotil hafif boşluklu'],
                'kaporta_boya' => ['status' => 'green', 'note' => 'Orijinal hatasız boyasız'],
            ],
        ]);

        $this->assertNotEmpty($inspection['id']);
        $this->assertSame(self::$vehicleId, (int) $inspection['id_vehicles']);
        $this->assertSame('pre_sale_expertiz', $inspection['inspection_type']);
        $this->assertSame(88, (int) $inspection['overall_score']);
        $this->assertNotEmpty($inspection['customer_shared_token']);

        // Check retrieval by token
        $retrieved = self::ci()->work_orders_model->get_inspection_by_token($inspection['customer_shared_token']);
        $this->assertNotNull($retrieved);
        $this->assertSame('34OTO2026', $retrieved['plate_number']);
        $this->assertSame('Audi', $retrieved['brand']);
        $this->assertIsArray($retrieved['items']);
        $this->assertSame('green', $retrieved['items']['motor_yagi']['status']);
    }

    /**
     * Test 2: Verify create_work_order correctly stores new work order with technician and complaint.
     */
    public function testCreateWorkOrderStoresValidPipelineEntry(): void
    {
        $wo = self::ci()->work_orders_model->create_work_order([
            'id_vehicles' => self::$vehicleId,
            'id_users_technician' => self::$technicianId,
            'customer_complaint' => 'Periyodik 80.000 bakım ve ön fren balata kontrolü',
            'labor_items' => [['description' => 'Periyodik 80.000 bakım ve ön fren balata kontrolü']],
            'estimated_cost' => 6500.00,
            'delivery_datetime' => date('Y-m-d H:i:s', strtotime('+2 days')),
            'status' => 'created',
        ]);

        $this->assertNotEmpty($wo['id']);
        $this->assertStringStartsWith('WO-', $wo['work_order_number']);
        $this->assertSame(self::$vehicleId, (int) $wo['id_vehicles']);
        $this->assertSame(self::$technicianId, (int) $wo['id_users_technician']);
        $this->assertSame(6500.00, (float) $wo['estimated_cost']);
        $this->assertSame('created', $wo['status']);
    }

    /**
     * Test 3: Verify update_status supports inspected, parts_waiting, in_progress, ready, delivered.
     */
    public function testWorkOrderStatusPipelineTransitions(): void
    {
        $wo = self::ci()->work_orders_model->create_work_order([
            'id_vehicles' => self::$vehicleId,
            'status' => 'created',
        ]);
        $woId = (int) $wo['id'];

        // inspected
        $this->assertTrue(self::ci()->work_orders_model->update_status($woId, 'inspected'));
        $dbWo = self::db()->get_where('work_orders', ['id' => $woId])->row_array();
        $this->assertSame('inspected', $dbWo['status']);

        // in_progress
        $this->assertTrue(self::ci()->work_orders_model->update_status($woId, 'in_progress'));
        $dbWo = self::db()->get_where('work_orders', ['id' => $woId])->row_array();
        $this->assertSame('in_progress', $dbWo['status']);

        // parts_waiting
        $this->assertTrue(self::ci()->work_orders_model->update_status($woId, 'parts_waiting'));
        $dbWo = self::db()->get_where('work_orders', ['id' => $woId])->row_array();
        $this->assertSame('parts_waiting', $dbWo['status']);

        // ready
        $this->assertTrue(self::ci()->work_orders_model->update_status($woId, 'ready'));
        $dbWo = self::db()->get_where('work_orders', ['id' => $woId])->row_array();
        $this->assertSame('ready', $dbWo['status']);

        // delivered
        $this->assertTrue(self::ci()->work_orders_model->update_status($woId, 'delivered'));
        $dbWo = self::db()->get_where('work_orders', ['id' => $woId])->row_array();
        $this->assertSame('delivered', $dbWo['status']);
        $this->assertNotNull($dbWo['delivery_datetime']);
    }

    /**
     * Test 4: Verify vertical_vehicles_dvi.php template contains all required DOM elements and hooks.
     */
    public function testViewTemplateContainsRequiredDomElements(): void
    {
        $viewPath = APPPATH . 'views/pages/vertical_vehicles_dvi.php';
        $this->assertFileExists($viewPath);
        $content = file_get_contents($viewPath);

        // 1. DVI Modal and elements
        $this->assertStringContainsString('id="modal-new-dvi"', $content);
        $this->assertStringContainsString('id="dvi-modal-plate"', $content);
        $this->assertStringContainsString('id="dvi-inspection-type"', $content);
        $this->assertStringContainsString('id="dvi-overall-score"', $content);
        $this->assertStringContainsString('id="dvi-vehicle-id"', $content);
        $this->assertStringContainsString('id="form-save-dvi"', $content);
        $this->assertStringContainsString('btn-new-dvi', $content);
        $this->assertStringContainsString('id="btn-submit-dvi"', $content);
        $this->assertStringContainsString('id="dvi-share-result"', $content);

        // Checklist items
        $this->assertStringContainsString('item_motor_yagi_status', $content);
        $this->assertStringContainsString('item_fren_balatalari_status', $content);
        $this->assertStringContainsString('item_lastikler_status', $content);
        $this->assertStringContainsString('item_aku_elektrik_status', $content);
        $this->assertStringContainsString('item_suspansiyon_alt_takim_status', $content);
        $this->assertStringContainsString('item_kaporta_boya_status', $content);

        // 2. Work Order Modal and elements
        $this->assertStringContainsString('id="modal-add-work-order"', $content);
        $this->assertStringContainsString('id="form-add-wo"', $content);
        $this->assertStringContainsString('id="wo-vehicle-id"', $content);
        $this->assertStringContainsString('id="wo-technician-id"', $content);
        $this->assertStringContainsString('id="wo-customer-complaint"', $content);
        $this->assertStringContainsString('id="wo-estimated-cost"', $content);
        $this->assertStringContainsString('id="wo-delivery-datetime"', $content);
        $this->assertStringContainsString('Yeni İş Emri Başlat', $content);

        // 3. Status update buttons
        $this->assertStringContainsString('btn-wo-status', $content);
        $this->assertStringContainsString('data-status="inspected"', $content);
        $this->assertStringContainsString('data-status="in_progress"', $content);
        $this->assertStringContainsString('data-status="parts_waiting"', $content);
        $this->assertStringContainsString('data-status="ready"', $content);
        $this->assertStringContainsString('data-status="delivered"', $content);

        // 4. escapeHtml sanitization
        $this->assertStringContainsString('function escapeHtml(str)', $content);
    }
}
