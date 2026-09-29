<?php declare(strict_types=1);

namespace Tests\Integration;

use Tests\TenantTestCase;
use RuntimeException;
use InvalidArgumentException;

/**
 * Integration Test for Sector 3 (health_clinical) Backend Fixes.
 * Covers Clinical_records_model, Verticals controller web endpoints,
 * and Verticals_api_v1 API authorization & confidential record scoping.
 */
class HealthClinicalFixesIntegrationTest extends TenantTestCase
{
    private static int $providerId;
    private static int $customerId;
    private static int $otherCustomerId;
    private static int $appointmentId;

    protected function setUp(): void
    {
        parent::setUp();
        self::ci()->load->model('clinical_records_model');
        self::ci()->load->model('appointments_model');
        self::ci()->load->model('customers_model');

        $db = self::db();

        // 1. Provider User (Doctor / Specialist)
        $provider = $db->get_where('users', ['email' => 'dr_ahmet_test@booki.local'])->row_array();
        if (!$provider) {
            $db->insert('users', [
                'first_name' => 'Ahmet',
                'last_name' => 'Uzman',
                'email' => 'dr_ahmet_test@booki.local',
                'phone_number' => '05551112233',
                'id_roles' => 2,
            ]);
            self::$providerId = (int) $db->insert_id();
        } else {
            self::$providerId = (int) $provider['id'];
        }

        // 2. Customer User (Patient)
        $customer = $db->get_where('users', ['email' => 'canan_patient_test@booki.local'])->row_array();
        if (!$customer) {
            $db->insert('users', [
                'first_name' => 'Canan',
                'last_name' => 'Hasta',
                'email' => 'canan_patient_test@booki.local',
                'phone_number' => '05554445566',
                'id_roles' => 3,
            ]);
            self::$customerId = (int) $db->insert_id();
        } else {
            self::$customerId = (int) $customer['id'];
        }

        // 3. Second Customer User (Other Patient)
        $otherCustomer = $db->get_where('users', ['email' => 'mehmet_other_test@booki.local'])->row_array();
        if (!$otherCustomer) {
            $db->insert('users', [
                'first_name' => 'Mehmet',
                'last_name' => 'Diger',
                'email' => 'mehmet_other_test@booki.local',
                'phone_number' => '05557778899',
                'id_roles' => 3,
            ]);
            self::$otherCustomerId = (int) $db->insert_id();
        } else {
            self::$otherCustomerId = (int) $otherCustomer['id'];
        }

        // 4. Sample Appointment
        $appt = $db->get_where('appointments', [
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'notes' => 'Health clinical test appointment',
        ])->row_array();

        if (!$appt) {
            $db->insert('appointments', [
                'start_datetime' => date('Y-m-d H:i:s', strtotime('+2 days 10:00:00')),
                'end_datetime' => date('Y-m-d H:i:s', strtotime('+2 days 10:45:00')),
                'is_unavailability' => 0,
                'id_users_customer' => self::$customerId,
                'id_users_provider' => self::$providerId,
                'id_services' => 1,
                'hash' => md5(uniqid('appt', true)),
                'notes' => 'Health clinical test appointment',
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
        $controller->load->model('clinical_records_model');
        $controller->load->model('appointments_model');
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
        $api->load->model('clinical_records_model');
        $api->load->model('appointments_model');
        return $api;
    }

    /**
     * Test 1: Verify add_record robustly parses is_confidential variations.
     */
    public function testAddRecordRobustIsConfidentialParsing(): void
    {
        $model = self::ci()->clinical_records_model;
        $db = self::db();

        $truthyValues = [1, '1', true, 'true', 'on'];
        $createdIds = [];

        foreach ($truthyValues as $val) {
            $id = $model->add_record([
                'id_users_customer' => self::$customerId,
                'id_users_provider' => self::$providerId,
                'subjective' => 'Truthy test',
                'is_confidential' => $val,
            ]);
            $createdIds[] = $id;

            $row = $db->get_where('clinical_records', ['id' => $id])->row_array();
            $this->assertEquals(1, (int) $row['is_confidential'], "Value " . var_export($val, true) . " must be parsed as 1");
        }

        $falsyValues = [0, '0', false, 'false', 'off'];
        foreach ($falsyValues as $val) {
            $id = $model->add_record([
                'id_users_customer' => self::$customerId,
                'id_users_provider' => self::$providerId,
                'subjective' => 'Falsy test',
                'is_confidential' => $val,
            ]);
            $createdIds[] = $id;

            $row = $db->get_where('clinical_records', ['id' => $id])->row_array();
            $this->assertEquals(0, (int) $row['is_confidential'], "Value " . var_export($val, true) . " must be parsed as 0");
        }

        // Test default when omitted -> must default to 1 (confidential)
        $idDefault = $model->add_record([
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'subjective' => 'Default confidential test',
        ]);
        $createdIds[] = $idDefault;

        $rowDefault = $db->get_where('clinical_records', ['id' => $idDefault])->row_array();
        $this->assertEquals(1, (int) $rowDefault['is_confidential'], "Omitted is_confidential must default to 1");

        // Cleanup
        $db->where_in('id', $createdIds)->delete('clinical_records');
    }

    /**
     * Test 2: Verify add_record cleanly links and casts id_appointments.
     */
    public function testAddRecordCleanAppointmentLinking(): void
    {
        $model = self::ci()->clinical_records_model;
        $db = self::db();

        // Linked with integer appointment ID
        $id1 = $model->add_record([
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'id_appointments' => self::$appointmentId,
            'subjective' => 'Integer appt link',
        ]);
        $row1 = $db->get_where('clinical_records', ['id' => $id1])->row_array();
        $this->assertSame(self::$appointmentId, (int) $row1['id_appointments']);

        // Linked with numeric string appointment ID
        $id2 = $model->add_record([
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'id_appointments' => (string) self::$appointmentId,
            'subjective' => 'String appt link',
        ]);
        $row2 = $db->get_where('clinical_records', ['id' => $id2])->row_array();
        $this->assertSame(self::$appointmentId, (int) $row2['id_appointments']);

        // Null / empty appointment ID
        $id3 = $model->add_record([
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'id_appointments' => null,
            'subjective' => 'No appt link',
        ]);
        $row3 = $db->get_where('clinical_records', ['id' => $id3])->row_array();
        $this->assertNull($row3['id_appointments']);

        // Empty string appointment ID
        $id4 = $model->add_record([
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'id_appointments' => '',
            'subjective' => 'Empty string appt link',
        ]);
        $row4 = $db->get_where('clinical_records', ['id' => $id4])->row_array();
        $this->assertNull($row4['id_appointments']);

        // Cleanup
        $db->where_in('id', [$id1, $id2, $id3, $id4])->delete('clinical_records');
    }

    /**
     * Test 3: Verify get_record_by_id returns complete record with patient, provider, and appointment info.
     */
    public function testGetRecordByIdWithCompleteDetails(): void
    {
        $model = self::ci()->clinical_records_model;
        $db = self::db();

        $recordId = $model->add_record([
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'id_appointments' => self::$appointmentId,
            'record_type' => 'soap_note',
            'subjective' => 'Hastanın baş ağrısı şikayeti var.',
            'objective' => 'Tansiyon: 120/80 mmHg, Nabız: 72.',
            'assessment' => 'Gerilim tipi baş ağrısı.',
            'plan' => 'Bol su tüketimi, dinlenme ve magnezyum takviyesi.',
            'attachments' => [['name' => 'kan_tahlili.pdf', 'url' => 'https://storage/kan.pdf']],
            'is_confidential' => 1,
        ]);

        $fullRecord = $model->get_record_by_id($recordId);

        $this->assertNotNull($fullRecord);
        $this->assertEquals($recordId, (int) $fullRecord['id']);
        $this->assertSame('Canan', $fullRecord['patient_first_name']);
        $this->assertSame('Hasta', $fullRecord['patient_last_name']);
        $this->assertSame('05554445566', $fullRecord['patient_phone']);
        $this->assertSame('Ahmet', $fullRecord['provider_first_name']);
        $this->assertSame('Uzman', $fullRecord['provider_last_name']);
        $this->assertNotEmpty($fullRecord['appointment_start_datetime']);
        $this->assertNotEmpty($fullRecord['appointment_end_datetime']);
        $this->assertSame('Hastanın baş ağrısı şikayeti var.', $fullRecord['subjective']);
        $this->assertSame('Tansiyon: 120/80 mmHg, Nabız: 72.', $fullRecord['objective']);
        $this->assertSame('Gerilim tipi baş ağrısı.', $fullRecord['assessment']);
        $this->assertSame('Bol su tüketimi, dinlenme ve magnezyum takviyesi.', $fullRecord['plan']);
        $this->assertIsArray($fullRecord['attachments']);
        $this->assertCount(1, $fullRecord['attachments']);
        $this->assertSame('kan_tahlili.pdf', $fullRecord['attachments'][0]['name']);

        // Non-existent ID returns null
        $this->assertNull($model->get_record_by_id(999999999));

        // Cleanup
        $db->where('id', $recordId)->delete('clinical_records');
    }

    /**
     * Test 4: Verify get_patient_appointments returns recent appointments and excludes unavailabilities.
     */
    public function testGetPatientAppointments(): void
    {
        $model = self::ci()->clinical_records_model;
        $db = self::db();

        // Add an unavailable record for same customer (should be excluded)
        $db->insert('appointments', [
            'start_datetime' => date('Y-m-d H:i:s', strtotime('+3 days')),
            'end_datetime' => date('Y-m-d H:i:s', strtotime('+3 days +1 hour')),
            'is_unavailability' => 1,
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'id_services' => 1,
            'notes' => 'Doctor unavailable block',
        ]);
        $unavailId = (int) $db->insert_id();

        $appts = $model->get_patient_appointments(self::$customerId);

        $this->assertIsArray($appts);
        $this->assertNotEmpty($appts);

        // Verify all returned appointments have is_unavailability == 0
        foreach ($appts as $a) {
            $this->assertEquals(0, (int) $a['is_unavailability']);
            $this->assertArrayHasKey('start_datetime', $a);
            $this->assertArrayHasKey('end_datetime', $a);
        }

        // Verify the unavailable block was NOT returned
        $unavailFound = false;
        foreach ($appts as $a) {
            if ((int) $a['id'] === $unavailId) {
                $unavailFound = true;
                break;
            }
        }
        $this->assertFalse($unavailFound, 'Unavailable appointment blocks must not be returned');

        // Cleanup
        $db->where('id', $unavailId)->delete('appointments');
    }

    /**
     * Test 5: Verify get_patient_history respects include_confidential parameter.
     */
    public function testGetPatientHistoryConfidentialityFilter(): void
    {
        $model = self::ci()->clinical_records_model;
        $db = self::db();

        // 1. Confidential record
        $recConfId = $model->add_record([
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'subjective' => 'Hassas psikiyatri notu',
            'is_confidential' => 1,
        ]);

        // 2. Non-confidential record
        $recPublicId = $model->add_record([
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'subjective' => 'Genel diyet listesi',
            'is_confidential' => 0,
        ]);

        // Doctor / Provider view (include_confidential = true) -> both records returned
        $allRecords = $model->get_patient_history(self::$customerId, true);
        $allIds = array_column($allRecords, 'id');
        $this->assertContains((string) $recConfId, array_map('strval', $allIds));
        $this->assertContains((string) $recPublicId, array_map('strval', $allIds));

        // Customer view (include_confidential = false) -> ONLY non-confidential returned
        $publicOnlyRecords = $model->get_patient_history(self::$customerId, false);
        $publicOnlyIds = array_column($publicOnlyRecords, 'id');
        $this->assertNotContains((string) $recConfId, array_map('strval', $publicOnlyIds));
        $this->assertContains((string) $recPublicId, array_map('strval', $publicOnlyIds));

        // Cleanup
        $db->where_in('id', [$recConfId, $recPublicId])->delete('clinical_records');
    }

    /**
     * Test 6: Verify get_or_create_telehealth_link generates and stores video meeting link.
     */
    public function testTelehealthLinkGenerationAndPersistence(): void
    {
        $model = self::ci()->clinical_records_model;
        $db = self::db();

        // Reset meeting link
        $db->where('id', self::$appointmentId)->update('appointments', ['meeting_link' => null]);

        $link1 = $model->get_or_create_telehealth_link(self::$appointmentId);

        $this->assertNotEmpty($link1);
        $this->assertStringStartsWith('https://meet.jit.si/booki-telehealth-', $link1);

        // Check appointment row in DB
        $apptRow = $db->get_where('appointments', ['id' => self::$appointmentId])->row_array();
        $this->assertSame($link1, $apptRow['meeting_link']);

        // Call again -> must return identical link without recreating
        $link2 = $model->get_or_create_telehealth_link(self::$appointmentId);
        $this->assertSame($link1, $link2);
    }

    /**
     * Test 7: Verify save_patient_insurance and get_patient_insurance.
     */
    public function testPatientInsuranceSaveAndFetch(): void
    {
        $model = self::ci()->clinical_records_model;
        $db = self::db();

        // 1. Initial save
        $insId = $model->save_patient_insurance(self::$customerId, [
            'provider_name' => 'Acıbadem Sigorta',
            'policy_number' => 'ACB-2026-9901',
            'coverage_ratio' => 85,
            'valid_until' => '2027-12-31',
            'notes' => 'Tamamlayıcı sağlık sigortası',
        ]);

        $this->assertGreaterThan(0, $insId);

        $fetched = $model->get_patient_insurance(self::$customerId);
        $this->assertNotNull($fetched);
        $this->assertSame('Acıbadem Sigorta', $fetched['provider_name']);
        $this->assertSame('ACB-2026-9901', $fetched['policy_number']);
        $this->assertEquals(85, (int) $fetched['coverage_ratio']);
        $this->assertSame('2027-12-31', $fetched['valid_until']);

        // 2. Update existing insurance
        $updatedId = $model->save_patient_insurance(self::$customerId, [
            'provider_name' => 'Allianz Sigorta',
            'policy_number' => 'ALZ-5544',
            'coverage_ratio' => 100,
        ]);
        $this->assertSame($insId, $updatedId);

        $fetchedAfter = $model->get_patient_insurance(self::$customerId);
        $this->assertSame('Allianz Sigorta', $fetchedAfter['provider_name']);
        $this->assertSame('ALZ-5544', $fetchedAfter['policy_number']);
        $this->assertEquals(100, (int) $fetchedAfter['coverage_ratio']);

        // Cleanup
        $db->where('id_users_customer', self::$customerId)->delete('patient_insurances');
    }

    /**
     * Test 8: Verify Verticals controller web endpoints authorization & functionality.
     */
    public function testVerticalsWebControllerEndpoints(): void
    {
        $controller = $this->createVerticalsController();

        // 8.1 add_clinical_record: Unauthenticated -> 401
        session(['user_id' => null, 'role_slug' => null]);
        $unauthThrown = false;
        try {
            $controller->add_clinical_record();
        } catch (\Throwable $e) {
            $unauthThrown = true;
            $this->assertEquals(401, $e->getCode());
        }
        $this->assertTrue($unauthThrown, 'Unauthenticated user must be rejected with 401');

        // 8.2 add_clinical_record: Customer role -> 403
        session(['user_id' => self::$customerId, 'role_slug' => 'customer']);
        $forbiddenThrown = false;
        try {
            $controller->add_clinical_record();
        } catch (\Throwable $e) {
            $forbiddenThrown = true;
            $this->assertEquals(403, $e->getCode());
        }
        $this->assertTrue($forbiddenThrown, 'Customer role must be rejected with 403 Forbidden');

        // 8.3 add_clinical_record: Missing required patient / provider -> returns success=false
        session(['user_id' => self::$providerId, 'role_slug' => 'provider']);
        $_POST = [];
        $controller->add_clinical_record();
        $resp = json_decode(self::ci()->output->get_output(), true);
        $this->assertFalse($resp['success']);
        $this->assertSame('Danışan/hasta ve hekim/uzman seçimi zorunludur.', $resp['message']);

        // 8.4 add_clinical_record: Success as provider
        $_POST = [
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'id_appointments' => self::$appointmentId,
            'subjective' => 'Web controller charting note',
            'objective' => 'Bulgular normal',
            'assessment' => 'Rutin kontrol',
            'plan' => '6 ay sonra kontrol',
            'is_confidential' => '1',
        ];
        $controller->add_clinical_record();
        $successResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($successResp['success']);
        $this->assertSame('Klinik dosya başarıyla kaydedildi.', $successResp['message']);
        $this->assertGreaterThan(0, $successResp['record_id']);
        $newRecordId = (int) $successResp['record_id'];

        // 8.5 get_patient_history web endpoint
        $controller->get_patient_history(self::$customerId);
        $historyResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($historyResp['success']);
        $this->assertEquals(self::$customerId, $historyResp['customer_id']);
        $this->assertIsArray($historyResp['clinical_records']);

        // 8.6 save_patient_insurance web endpoint: Customer role -> 403
        session(['user_id' => self::$customerId, 'role_slug' => 'customer']);
        $custInsThrown = false;
        try {
            $controller->save_patient_insurance();
        } catch (\Throwable $e) {
            $custInsThrown = true;
            $this->assertEquals(403, $e->getCode());
        }
        $this->assertTrue($custInsThrown, 'Customer cannot save insurance via web controller');

        // 8.7 save_patient_insurance web endpoint: Success as provider
        session(['user_id' => self::$providerId, 'role_slug' => 'provider']);
        $_POST = [
            'customer_id' => self::$customerId,
            'provider_name' => 'Web SGK',
            'policy_number' => 'SGK-9900',
            'coverage_ratio' => 90,
        ];
        $controller->save_patient_insurance();
        $insResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($insResp['success']);
        $this->assertGreaterThan(0, $insResp['insurance_id']);

        // 8.8 get_telehealth_link web endpoint: Success as provider
        $controller->get_telehealth_link(self::$appointmentId);
        $thResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertTrue($thResp['success']);
        $this->assertNotEmpty($thResp['telehealth_url']);

        // Cleanup
        self::db()->where('id', $newRecordId)->delete('clinical_records');
        self::db()->where('id_users_customer', self::$customerId)->delete('patient_insurances');
    }

    /**
     * Test 9: Verify Verticals_api_v1 authorization gating & confidential data scoping.
     */
    public function testVerticalsApiAuthorizationGating(): void
    {
        $apiController = $this->createVerticalsApiController();
        $model = self::ci()->clinical_records_model;
        $db = self::db();

        // 1. Create a confidential record and a public record for customer
        $confId = $model->add_record([
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'subjective' => 'API confidential clinical note',
            'is_confidential' => 1,
        ]);
        $pubId = $model->add_record([
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'subjective' => 'API public dietary note',
            'is_confidential' => 0,
        ]);

        // 9.1 add_clinical_record API: Customer role -> 403 Forbidden
        session(['user_id' => self::$customerId, 'role_slug' => 'customer']);
        $_POST = [
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'subjective' => 'Customer trying to write clinical record',
        ];
        $apiController->add_clinical_record();
        $apiAddResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('error', $apiAddResp);
        $this->assertSame('Bu işlem için yetkiniz bulunmamaktadır.', $apiAddResp['error']);

        // 9.2 save_patient_insurance API: Customer role -> 403 Forbidden
        session(['user_id' => self::$customerId, 'role_slug' => 'customer']);
        $_POST = [
            'provider_name' => 'Hacked Insurance',
        ];
        $apiController->save_patient_insurance(self::$customerId);
        $apiSaveInsResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('error', $apiSaveInsResp);
        $this->assertSame('Bu işlem için yetkiniz bulunmamaktadır.', $apiSaveInsResp['error']);

        // 9.3 get_patient_clinical_history API: Customer requesting ANOTHER customer's history -> 403 Forbidden
        session(['user_id' => self::$customerId, 'role_slug' => 'customer']);
        $apiController->get_patient_clinical_history(self::$otherCustomerId);
        $otherHistoryResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('error', $otherHistoryResp);
        $this->assertSame('Diğer danışanların klinik kayıtlarına erişim yetkiniz yoktur.', $otherHistoryResp['error']);

        // 9.4 get_patient_clinical_history API: Customer requesting OWN history -> confidential records EXCLUDED
        session(['user_id' => self::$customerId, 'role_slug' => 'customer']);
        $apiController->get_patient_clinical_history(self::$customerId);
        $ownHistoryResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertEquals(self::$customerId, $ownHistoryResp['customer_id']);
        $ownRecordIds = array_column($ownHistoryResp['clinical_records'], 'id');
        $this->assertNotContains((string) $confId, array_map('strval', $ownRecordIds), 'Customer must NOT receive confidential records');
        $this->assertContains((string) $pubId, array_map('strval', $ownRecordIds), 'Customer must receive public records');

        // 9.5 get_patient_clinical_history API: Provider requesting patient history -> ALL records INCLUDED
        session(['user_id' => self::$providerId, 'role_slug' => 'provider']);
        $apiController->get_patient_clinical_history(self::$customerId);
        $providerHistoryResp = json_decode(self::ci()->output->get_output(), true);
        $providerRecordIds = array_column($providerHistoryResp['clinical_records'], 'id');
        $this->assertContains((string) $confId, array_map('strval', $providerRecordIds), 'Provider must receive confidential records');
        $this->assertContains((string) $pubId, array_map('strval', $providerRecordIds), 'Provider must receive public records');

        // 9.6 get_telehealth_link API: Customer requesting ANOTHER customer's appointment -> 403 Forbidden
        session(['user_id' => self::$otherCustomerId, 'role_slug' => 'customer']);
        $apiController->get_telehealth_link(self::$appointmentId);
        $otherThResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertArrayHasKey('error', $otherThResp);
        $this->assertSame('Bu randevuya ait telehealth bağlantısına erişim yetkiniz yoktur.', $otherThResp['error']);

        // 9.7 get_telehealth_link API: Customer requesting OWN appointment -> 200 OK
        session(['user_id' => self::$customerId, 'role_slug' => 'customer']);
        $apiController->get_telehealth_link(self::$appointmentId);
        $ownThResp = json_decode(self::ci()->output->get_output(), true);
        $this->assertNotEmpty($ownThResp['telehealth_url']);

        // Cleanup
        $db->where_in('id', [$confId, $pubId])->delete('clinical_records');
    }
}
