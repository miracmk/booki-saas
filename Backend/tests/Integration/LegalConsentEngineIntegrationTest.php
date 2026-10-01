<?php declare(strict_types=1);

namespace Tests\Integration;

use Tests\TenantTestCase;

/**
 * Integration Test for Comprehensive Legal Consent, Waiver & Dynamic Contract System.
 * Tests catalog retrieval, dynamic placeholder compilation, service auto-linking,
 * and immutable signature snapshotting.
 */
class LegalConsentEngineIntegrationTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::ci()->load->library('legal_catalog');
        self::ci()->load->model('services_model');
        self::ci()->load->model('appointments_model');
    }

    public function testCatalogRetrieval(): void
    {
        $catalog = self::ci()->legal_catalog->get_catalog();
        $this->assertIsArray($catalog);
        $this->assertArrayHasKey('CILT_BAKIMI_MEDIKAL_ESTETIK', $catalog);
        $this->assertArrayHasKey('LAZER_EPILASYON_ONAM', $catalog);
        $this->assertArrayHasKey('BOTOKS_DOLGU_ESTETIK_ONAM', $catalog);
        $this->assertArrayHasKey('KALICI_MAKYAJ_MICROBLADING_ONAM', $catalog);
        $this->assertArrayHasKey('DIS_HEKIMLIGI_TEDAVI_ONAM', $catalog);
        $this->assertArrayHasKey('KVKK_AYDINLATMA_VE_ACIK_RIZA', $catalog);

        // Verify structure
        $cilt = $catalog['CILT_BAKIMI_MEDIKAL_ESTETIK'];
        $this->assertSame('CILT_BAKIMI_MEDIKAL_ESTETIK', $cilt['code']);
        $this->assertStringContainsString('{{CUSTOMER_FULL_NAME}}', $cilt['content_html']);
        $this->assertStringContainsString('{{SERVICE_NAME}}', $cilt['content_html']);
    }

    public function testDynamicPlaceholderCompilation(): void
    {
        $rawHtml = '<p>Sayın {{CUSTOMER_FULL_NAME}} (TC: {{CUSTOMER_MASKED_TCKN}} / Tel: {{CUSTOMER_PHONE}}),</p>' .
                   '<p>{{SERVICE_NAME}} ({{SERVICE_CATEGORY}}) hizmeti için randevunuz {{APPOINTMENT_DATE_TIME}} ' .
                   'tarihinde {{PROVIDER_NAME}} ile onaylanmıştır. Ücret: {{SERVICE_PRICE}} TL. Merkez: {{TENANT_NAME}}.</p>';

        $context = [
            'customer_first_name' => 'Elif',
            'customer_last_name' => 'Öztürk',
            'customer_phone' => '+905321112233',
            'customer_masked_tckn' => '123*****890',
            'service_name' => 'Lazer Epilasyon Tüm Vücut',
            'service_category' => 'Lazer Epilasyon & Cilt',
            'service_price' => 3500.00,
            'provider_name' => 'Uzm. Dr. Zeynep Aksoy',
            'appointment_date_time' => '12.10.2026 14:00',
            'tenant_name' => 'Dr. Figen Polikliniği'
        ];

        $compiled = self::ci()->legal_catalog->compile($rawHtml, $context);

        $this->assertStringNotContainsString('{{CUSTOMER_FULL_NAME}}', $compiled);
        $this->assertStringNotContainsString('{{CUSTOMER_PHONE}}', $compiled);
        $this->assertStringNotContainsString('{{SERVICE_NAME}}', $compiled);
        $this->assertStringContainsString('Elif Öztürk', $compiled);
        $this->assertStringContainsString('+905321112233', $compiled);
        $this->assertStringContainsString('123*****890', $compiled);
        $this->assertStringContainsString('Lazer Epilasyon Tüm Vücut', $compiled);
        $this->assertStringContainsString('Uzm. Dr. Zeynep Aksoy', $compiled);
        $this->assertStringContainsString('3,500.00', $compiled);
        $this->assertStringContainsString('Dr. Figen Polikliniği', $compiled);
    }

    public function testSeedingAndDummyRemoval(): void
    {
        $db = self::db();
        // Ensure no dummy records exist
        $dummy = $db->like('title', 'Zipline')->or_like('title', 'Macera')->get('digital_waivers')->result_array();
        $this->assertEmpty($dummy, 'Legacy Macera Parkı dummy waiver must not exist in database.');

        // Ensure active clinical and KVKK templates exist
        $activeWaivers = $db->get('digital_waivers')->result_array();
        $this->assertNotEmpty($activeWaivers);

        $titles = array_column($activeWaivers, 'title');
        $hasKvkk = false;
        $hasCilt = false;
        foreach ($titles as $t) {
            if (stripos($t, 'KVKK') !== false) {
                $hasKvkk = true;
            }
            if (stripos($t, 'Cilt') !== false) {
                $hasCilt = true;
            }
        }
        $this->assertTrue($hasKvkk, 'KVKK waiver must be present in database.');
        $this->assertTrue($hasCilt, 'Clinical Cilt Bakımı waiver must be present in database.');
    }

    public function testServiceSuggestionAlgorithm(): void
    {
        $suggestedCilt = self::ci()->legal_catalog->get_suggested_templates_for_service(
            'Hydrafacial Medikal Cilt Temizliği',
            'Cilt Bakımı'
        );
        $this->assertArrayHasKey('CILT_BAKIMI_MEDIKAL_ESTETIK', $suggestedCilt);

        $suggestedLazer = self::ci()->legal_catalog->get_suggested_templates_for_service(
            'Buz Lazer Epilasyon',
            'Lazer & Epilasyon'
        );
        $this->assertArrayHasKey('LAZER_EPILASYON_ONAM', $suggestedLazer);

        $suggestedBotox = self::ci()->legal_catalog->get_suggested_templates_for_service(
            'Alın ve Göz Çevresi Botoks',
            'Medikal Estetik'
        );
        $this->assertArrayHasKey('BOTOKS_DOLGU_ESTETIK_ONAM', $suggestedBotox);
    }

    public function testWaiverSignaturePersistenceWithCompiledHtml(): void
    {
        $db = self::db();

        $ciltWaiver = $db->like('title', 'Cilt')->get('digital_waivers')->row_array();
        $this->assertNotNull($ciltWaiver);

        $testHtml = '<div>Hasta Onayı: Ahmet Yılmaz, 10.10.2026</div>';

        // Insert signature
        $sigData = [
            'id_waivers' => $ciltWaiver['id'],
            'id_appointments' => 99999,
            'id_users_customer' => 88888,
            'signer_full_name' => 'Ahmet Yılmaz',
            'signer_email' => 'ahmet@example.local',
            'signer_phone' => '+905001112233',
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
            'signature_type' => 'biometric',
            'ip_address' => '127.0.0.1',
            'signed_at' => date('Y-m-d H:i:s'),
            'compiled_content_html' => $testHtml,
        ];

        $db->insert('waiver_signatures', $sigData);
        $sigId = (int) $db->insert_id();
        $this->assertGreaterThan(0, $sigId);

        // Fetch back and verify
        $fetched = $db->get_where('waiver_signatures', ['id' => $sigId])->row_array();
        $this->assertSame('Ahmet Yılmaz', $fetched['signer_full_name']);
        $this->assertSame('biometric', $fetched['signature_type']);
        $this->assertSame($testHtml, $fetched['compiled_content_html']);

        // Clean up test signature
        $db->delete('waiver_signatures', ['id' => $sigId]);
    }
}
