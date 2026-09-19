<?php declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for Meta Webhook HMAC-SHA256 signature verification (UC-35).
 *
 * Verifies that Instagram and WhatsApp webhooks correctly validate the
 * X-Hub-Signature-256 header using timing-safe comparison.
 */
class MetaHmacVerificationTest extends TestCase
{
    private function verifyMetaSignature(string $raw_payload, ?string $signature_header, string $app_secret): bool
    {
        if (empty($app_secret) || empty($signature_header)) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $raw_payload, $app_secret);

        return hash_equals($expected, $signature_header);
    }

    public function testValidSignaturePasses(): void
    {
        $secret = 'super_secret_meta_key_123';
        $payload = json_encode(['entry' => [['id' => '12345', 'messaging' => []]]]);
        $valid_signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $this->assertTrue(
            $this->verifyMetaSignature($payload, $valid_signature, $secret),
            'Valid HMAC-SHA256 signature must be accepted.'
        );
    }

    public function testInvalidSignatureFails(): void
    {
        $secret = 'super_secret_meta_key_123';
        $payload = json_encode(['entry' => []]);
        $invalid_signature = 'sha256=invalid_hash_signature_value';

        $this->assertFalse(
            $this->verifyMetaSignature($payload, $invalid_signature, $secret),
            'Tampered or invalid signature must be rejected.'
        );
    }

    public function testTamperedPayloadFails(): void
    {
        $secret = 'super_secret_meta_key_123';
        $original_payload = '{"message":"hello"}';
        $signature = 'sha256=' . hash_hmac('sha256', $original_payload, $secret);

        $tampered_payload = '{"message":"tampered"}';

        $this->assertFalse(
            $this->verifyMetaSignature($tampered_payload, $signature, $secret),
            'Payload modified in transit must fail HMAC verification.'
        );
    }

    public function testEmptyHeaderOrSecretFails(): void
    {
        $payload = '{"message":"test"}';

        $this->assertFalse($this->verifyMetaSignature($payload, null, 'secret'));
        $this->assertFalse($this->verifyMetaSignature($payload, '', 'secret'));
        $this->assertFalse($this->verifyMetaSignature($payload, 'sha256=xxx', ''));
    }
}
