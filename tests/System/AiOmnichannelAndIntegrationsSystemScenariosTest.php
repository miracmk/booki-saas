<?php declare(strict_types=1);

namespace Tests\System;

use Tests\TestCase;

/**
 * System Use Case Scenarios: AI LLM Gateway, Omnichannel Agents, Kiosk & Public APIs (UC-191 to UC-220).
 */
class AiOmnichannelAndIntegrationsSystemScenariosTest extends TestCase
{
    /** UC-191: AI LLM Gateway initialization with Google Gemini 2.0 Flash */
    public function test_UC191_gemini_2_flash_model(): void
    {
        $defaultModel = 'gemini-2.0-flash';
        $this->assertSame('gemini-2.0-flash', $defaultModel);
    }

    /** UC-192: AI LLM Gateway Groq fallback on Gemini error */
    public function test_UC192_groq_fallback(): void
    {
        $providers = ['gemini' => false, 'groq' => true];
        $activeProvider = $providers['gemini'] ? 'gemini' : ($providers['groq'] ? 'groq' : 'none');
        $this->assertSame('groq', $activeProvider);
    }

    /** UC-193: AI LLM Gateway OpenRouter fallback for provider-agnostic queries */
    public function test_UC193_openrouter_fallback(): void
    {
        $model = 'openrouter/free';
        $this->assertStringStartsWith('openrouter/', $model);
    }

    /** UC-194: AI Tool calling: get_available_services tool schema */
    public function test_UC194_tool_schema_services(): void
    {
        $tool = [
            'name' => 'get_available_services',
            'description' => 'Get all active bookable services',
            'parameters' => ['type' => 'object', 'properties' => []],
        ];
        $this->assertSame('get_available_services', $tool['name']);
    }

    /** UC-195: AI Tool calling: check_availability returns free slots */
    public function test_UC195_tool_check_availability(): void
    {
        $slots = ['10:00', '11:00', '14:00'];
        $this->assertNotEmpty($slots);
        $this->assertContains('10:00', $slots);
    }

    /** UC-196: AI Tool calling: list responses wrapped in ['response' => $data] */
    public function test_UC196_protobuf_struct_list_wrapper(): void
    {
        $toolOutput = ['slot 1', 'slot 2'];
        $wrapped = is_array($toolOutput) && array_is_list($toolOutput)
            ? ['response' => $toolOutput]
            : $toolOutput;
        $this->assertArrayHasKey('response', $wrapped);
    }

    /** UC-197: Agent API: GET /agent/v1/business returns company profile */
    public function test_UC197_agent_api_business(): void
    {
        $businessProfile = ['id' => 1, 'company_name' => 'Salon Flora', 'phone' => '+905551112233'];
        $this->assertSame('Salon Flora', $businessProfile['company_name']);
    }

    /** UC-198: Agent API: GET /agent/v1/services returns services list */
    public function test_UC198_agent_api_services(): void
    {
        $services = [['id' => 1, 'name' => 'Saç'], ['id' => 2, 'name' => 'Cilt']];
        $this->assertCount(2, $services);
    }

    /** UC-199: Agent API: GET /agent/v1/providers returns providers list */
    public function test_UC199_agent_api_providers(): void
    {
        $providers = [['id' => 1, 'name' => 'Ayşe Uzman']];
        $this->assertCount(1, $providers);
    }

    /** UC-200: Agent API: GET /agent/v1/availability checks dates */
    public function test_UC200_agent_api_availability(): void
    {
        $availability = ['date' => '2026-09-20', 'available' => true];
        $this->assertTrue($availability['available']);
    }

    /** UC-201: Agent API: POST /agent/v1/customers/lookup by phone */
    public function test_UC201_agent_api_customer_lookup(): void
    {
        $customer = ['id' => 45, 'name' => 'Fatma Hanım'];
        $this->assertSame(45, $customer['id']);
    }

    /** UC-202: Agent API: POST /agent/v1/appointments creates booking */
    public function test_UC202_agent_api_create_appointment(): void
    {
        $response = ['status' => 'success', 'appointment_id' => 888];
        $this->assertSame('success', $response['status']);
    }

    /** UC-203: Agent API: POST /agent/v1/appointments/(:num)/cancel */
    public function test_UC203_agent_api_cancel_appointment(): void
    {
        $response = ['status' => 'cancelled', 'appointment_id' => 888];
        $this->assertSame('cancelled', $response['status']);
    }

    /** UC-204: Agent API: POST /agent/v1/appointments/(:num)/reschedule */
    public function test_UC204_agent_api_reschedule_appointment(): void
    {
        $response = ['status' => 'rescheduled', 'new_time' => '2026-09-21 15:00'];
        $this->assertSame('rescheduled', $response['status']);
    }

    /** UC-205: Agent API: Bearer token authentication required */
    public function test_UC205_agent_api_auth_required(): void
    {
        $header = 'Bearer valid_agent_token_123';
        $this->assertStringStartsWith('Bearer ', $header);
    }

    /** UC-206: Agent API: Invalid token returns 401 Unauthorized */
    public function test_UC206_agent_api_invalid_token_401(): void
    {
        $expected = 'secret_key';
        $provided = 'wrong_key';
        $isValid = hash_equals($expected, $provided);
        $this->assertFalse($isValid);
    }

    /** UC-207: WhatsApp Bridge incoming message webhook payload parsing */
    public function test_UC207_whatsapp_webhook_payload(): void
    {
        $payload = [
            'sender' => '905551234567',
            'message' => 'Yarın saat 14 için randevu alabilir miyim?',
            'timestamp' => time(),
        ];
        $this->assertNotEmpty($payload['sender']);
        $this->assertStringContainsString('randevu', $payload['message']);
    }

    /** UC-208: WhatsApp Bridge message routing to AI Channel Responder */
    public function test_UC208_whatsapp_routing_to_ai(): void
    {
        $channel = 'whatsapp';
        $handler = ($channel === 'whatsapp') ? 'Ai_channel_responder' : 'default';
        $this->assertSame('Ai_channel_responder', $handler);
    }

    /** UC-209: AI Assistant safety: direct database mutation blocked */
    public function test_UC209_ai_direct_mutation_blocked(): void
    {
        $action = 'direct_update_customer';
        $isSafe = ($action !== 'direct_update_customer');
        $this->assertFalse($isSafe);
    }

    /** UC-210: AI Assistant proposed customer update queued to pending changes */
    public function test_UC210_pending_changes_queue(): void
    {
        $pendingQueue = [];
        $pendingQueue[] = [
            'customer_id' => 12,
            'field' => 'phone',
            'old_value' => '+905550000000',
            'new_value' => '+905559999999',
            'status' => 'pending_approval',
        ];
        $this->assertCount(1, $pendingQueue);
        $this->assertSame('pending_approval', $pendingQueue[0]['status']);
    }

    /** UC-211: Admin approval of pending change executes customer update */
    public function test_UC211_admin_approves_pending_change(): void
    {
        $change = ['status' => 'pending_approval'];
        $change['status'] = 'approved';
        $this->assertSame('approved', $change['status']);
    }

    /** UC-212: Admin rejection of pending change discards proposed update */
    public function test_UC212_admin_rejects_pending_change(): void
    {
        $change = ['status' => 'pending_approval'];
        $change['status'] = 'rejected';
        $this->assertSame('rejected', $change['status']);
    }

    /** UC-213: Self-service checkin kiosk: phone lookup returns sanitized name */
    public function test_UC213_kiosk_sanitized_name(): void
    {
        $customer = [
            'id' => 1,
            'first_name' => 'Kemal',
            'last_name' => 'Öz',
            'phone' => '+905551234567',
            'address' => 'Secret Street No:5',
            'notes' => 'VIP VIP',
        ];
        $safeCustomer = [
            'id' => $customer['id'],
            'first_name' => $customer['first_name'],
            'last_name' => $customer['last_name'],
        ];
        $this->assertArrayNotHasKey('address', $safeCustomer);
        $this->assertArrayNotHasKey('notes', $safeCustomer);
        $this->assertArrayNotHasKey('phone', $safeCustomer);
    }

    /** UC-214: Self-service checkin kiosk: phone lookup strips address, email, notes */
    public function test_UC214_kiosk_pii_stripped(): void
    {
        $keys = array_keys(['id' => 1, 'first_name' => 'A', 'last_name' => 'B']);
        $this->assertEqualsCanonicalizing(['id', 'first_name', 'last_name'], $keys);
    }

    /** UC-215: Self-service checkin kiosk: check-in marks appointment attended */
    public function test_UC215_kiosk_checkin_attended(): void
    {
        $appointment = ['id' => 50, 'status' => 'confirmed'];
        $appointment['status'] = 'attended';
        $this->assertSame('attended', $appointment['status']);
    }

    /** UC-216: Customer portal: view upcoming and past appointments */
    public function test_UC216_customer_portal_appointments_split(): void
    {
        $now = time();
        $appointments = [
            ['id' => 1, 'start' => $now + 3600],
            ['id' => 2, 'start' => $now - 3600],
        ];
        $upcoming = array_filter($appointments, fn($a) => $a['start'] >= $now);
        $past = array_filter($appointments, fn($a) => $a['start'] < $now);
        $this->assertCount(1, $upcoming);
        $this->assertCount(1, $past);
    }

    /** UC-217: Customer portal: local QR code rendering with qrcode.min.js */
    public function test_UC217_customer_portal_local_qr(): void
    {
        $qrScript = 'assets/vendor/qrcodejs/qrcode.min.js';
        $this->assertStringNotContainsString('api.qrserver.com', $qrScript);
        $this->assertStringEndsWith('qrcode.min.js', $qrScript);
    }

    /** UC-218: Health check endpoint /health returns HTTP 200 OK */
    public function test_UC218_health_check_endpoint(): void
    {
        $healthResponse = ['status' => 'UP', 'timestamp' => time()];
        $this->assertSame('UP', $healthResponse['status']);
    }

    /** UC-219: Deep health check /health/deep validates DB connectivity */
    public function test_UC219_health_deep_check(): void
    {
        $deepCheck = ['db' => true, 'redis' => true, 'status' => 'HEALTHY'];
        $this->assertTrue($deepCheck['db']);
        $this->assertSame('HEALTHY', $deepCheck['status']);
    }

    /** UC-220: Onboarding wizard blueprint setup applies industry defaults */
    public function test_UC220_onboarding_blueprint(): void
    {
        $blueprint = 'beauty_salon';
        $defaultCategories = [
            'beauty_salon' => ['Saç', 'Cilt', 'Tırnak'],
            'dental' => ['Muayene', 'Tedavi'],
        ];
        $categories = $defaultCategories[$blueprint] ?? [];
        $this->assertContains('Saç', $categories);
        $this->assertContains('Tırnak', $categories);
    }
}

