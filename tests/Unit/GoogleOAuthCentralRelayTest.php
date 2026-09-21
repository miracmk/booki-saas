<?php declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for multi-tenant central Google OAuth state relay (2026-09-19).
 *
 * Verifies that the signed state generated on tenant origins can be verified,
 * prevents tampering, and enforces expiration and payload integrity.
 */
class GoogleOAuthCentralRelayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Load tenant helper if not already loaded
        if (!function_exists('build_google_oauth_state')) {
            require_once __DIR__ . '/../../application/helpers/tenant_helper.php';
        }
    }

    public function testBuildAndVerifyOAuthState(): void
    {
        $_SERVER['HTTP_HOST'] = 'salonflora-bookiapp.kibusiness.co';
        $csrf = bin2hex(random_bytes(32));

        $state = build_google_oauth_state($csrf, 'google/oauth_callback');

        $this->assertNotEmpty($state);
        $this->assertStringContainsString('.', $state);

        $payload = verify_google_oauth_state($state);

        $this->assertNotNull($payload, 'Valid state must unpack successfully');
        $this->assertSame('salonflora-bookiapp.kibusiness.co', $payload['host']);
        $this->assertSame('google/oauth_callback', $payload['target']);
        $this->assertSame($csrf, $payload['csrf']);
        $this->assertArrayHasKey('ts', $payload);
    }

    public function testTamperedStateFailsVerification(): void
    {
        $_SERVER['HTTP_HOST'] = 'salonflora-bookiapp.kibusiness.co';
        $csrf = bin2hex(random_bytes(32));

        $state = build_google_oauth_state($csrf, 'google/oauth_callback');
        [$b64, $sig] = explode('.', $state, 2);

        // Tamper payload
        $tampered_b64 = rtrim(strtr(base64_encode(json_encode(['host' => 'evil.com', 'csrf' => $csrf, 'ts' => time()])), '+/', '-_'), '=');
        $tampered_state = $tampered_b64 . '.' . $sig;

        $this->assertNull(
            verify_google_oauth_state($tampered_state),
            'State with mismatched HMAC signature must be rejected.'
        );
    }

    public function testExpiredStateFailsVerification(): void
    {
        $payload = [
            'host' => 'salonflora-bookiapp.kibusiness.co',
            'target' => 'google/oauth_callback',
            'csrf' => 'test-csrf-token',
            'ts' => time() - 1000, // Expired (> 900s)
        ];

        $json = json_encode($payload);
        $b64 = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $key = (function_exists('config_item') ? config_item('encryption_key') : null)
            ?: (getenv('EA_APP_KEY') ?: 'booki-oauth-relay-secret-key');
        $sig = hash_hmac('sha256', $b64, $key);
        $expired_state = $b64 . '.' . $sig;

        $this->assertNull(
            verify_google_oauth_state($expired_state),
            'State older than 15 minutes must be rejected.'
        );
    }

    public function testInvalidFormatStateReturnsNull(): void
    {
        $this->assertNull(verify_google_oauth_state(''));
        $this->assertNull(verify_google_oauth_state('plainstringwithnodots'));
        $this->assertNull(verify_google_oauth_state('invalid.too.many.dots'));
    }
}
