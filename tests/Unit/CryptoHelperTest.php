<?php declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for the salonflora_crypto_helper encryption functions.
 *
 * These tests verify the PII encryption/decryption and hashing functions work correctly,
 * including UTF-8 support, null handling, and error conditions.
 */
class CryptoHelperTest extends TestCase
{
    /**
     * Set up the test environment by loading the crypto helper with test keys.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set up test encryption and hash keys (32 bytes each, base64-encoded)
        $test_enc_key = base64_encode(random_bytes(32));
        $test_hash_key = base64_encode(random_bytes(32));

        putenv('SF_PII_ENC_KEY=' . $test_enc_key);
        putenv('SF_PII_HASH_KEY=' . $test_hash_key);

        // tenant_context() (used by the crypto helper's key-resolution fallback) lives in
        // tenant_helper.php, a separate file - not autoloaded in this minimal unit-test bootstrap.
        require_once __DIR__ . '/../../application/helpers/tenant_helper.php';
        require_once __DIR__ . '/../../application/helpers/salonflora_crypto_helper.php';
    }

    /**
     * Test that encryption and decryption round-trip correctly, preserving the original plaintext.
     */
    public function testRoundTripEncryptionDecryption(): void
    {
        $plaintext = 'some türkçe metin';
        $encrypted = sf_pii_encrypt($plaintext);

        $this->assertNotNull($encrypted);
        $this->assertTrue(sf_pii_is_encrypted($encrypted));

        $decrypted = sf_pii_decrypt($encrypted);

        $this->assertNotNull($decrypted);
        $this->assertSame($plaintext, $decrypted);
    }

    /**
     * Test that the round-trip preserves Turkish UTF-8 characters correctly.
     */
    public function testRoundTripPreservesUtf8Characters(): void
    {
        $plaintext = 'Müzisyen Anısına';
        $encrypted = sf_pii_encrypt($plaintext);

        $decrypted = sf_pii_decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }

    /**
     * Test that sf_pii_is_encrypted() returns false for plaintext strings.
     */
    public function testIsEncryptedReturnsFalseForPlaintext(): void
    {
        $plaintext = 'This is not encrypted';

        $this->assertFalse(sf_pii_is_encrypted($plaintext));
    }

    /**
     * Test that sf_pii_is_encrypted() returns true for encrypted strings.
     */
    public function testIsEncryptedReturnsTrueForEncrypted(): void
    {
        $plaintext = 'This is encrypted';
        $encrypted = sf_pii_encrypt($plaintext);

        $this->assertTrue(sf_pii_is_encrypted($encrypted));
    }

    /**
     * Test that encrypt(null) returns null (null in, null out).
     */
    public function testEncryptNullReturnsNull(): void
    {
        $result = sf_pii_encrypt(null);

        $this->assertNull($result);
    }

    /**
     * Test that encrypt('') returns null (empty string is treated like null).
     */
    public function testEncryptEmptyStringReturnsNull(): void
    {
        $result = sf_pii_encrypt('');

        $this->assertNull($result);
    }

    /**
     * Test that decrypt(null) returns null.
     */
    public function testDecryptNullReturnsNull(): void
    {
        $result = sf_pii_decrypt(null);

        $this->assertNull($result);
    }

    /**
     * Test that decrypt('') returns null.
     */
    public function testDecryptEmptyStringReturnsNull(): void
    {
        $result = sf_pii_decrypt('');

        $this->assertNull($result);
    }

    /**
     * Test that sf_pii_decrypt_or_fail() throws on tampered ciphertext.
     */
    public function testDecryptOrFailThrowsOnTamperedCiphertext(): void
    {
        $plaintext = 'sensitive data';
        $encrypted = sf_pii_encrypt($plaintext);

        $this->assertNotNull($encrypted);

        // Tamper with the encrypted value by changing one character
        $tampered = substr_replace($encrypted, 'X', 10, 1);

        $this->expectException(\RuntimeException::class);
        sf_pii_decrypt_or_fail($tampered);
    }

    /**
     * Test that sf_pii_decrypt_or_fail() returns null for null input.
     */
    public function testDecryptOrFailReturnsNullForNull(): void
    {
        $result = sf_pii_decrypt_or_fail(null);

        $this->assertNull($result);
    }

    /**
     * Test that sf_pii_hash() returns the same hash for the same plaintext.
     */
    public function testHashConsistency(): void
    {
        $plaintext = '+1 555-1234';
        $hash1 = sf_pii_hash($plaintext);
        $hash2 = sf_pii_hash($plaintext);

        $this->assertNotNull($hash1);
        $this->assertSame($hash1, $hash2);
    }

    /**
     * Test that sf_pii_hash() normalizes input (case-insensitive, trimmed).
     */
    public function testHashNormalizesInput(): void
    {
        $plaintext = 'example@email.com';
        $normalized = '  EXAMPLE@EMAIL.COM  ';

        $hash1 = sf_pii_hash($plaintext);
        $hash2 = sf_pii_hash($normalized);

        $this->assertSame($hash1, $hash2);
    }

    /**
     * Test that sf_pii_hash() returns null for null input.
     */
    public function testHashNullReturnsNull(): void
    {
        $result = sf_pii_hash(null);

        $this->assertNull($result);
    }

    /**
     * Test that sf_pii_hash() returns null for empty string input.
     */
    public function testHashEmptyStringReturnsNull(): void
    {
        $result = sf_pii_hash('');

        $this->assertNull($result);
    }

    /**
     * Test that encryption produces different ciphertexts for the same plaintext (nonce is random).
     */
    public function testEncryptionNonceIsRandom(): void
    {
        $plaintext = 'same plaintext';
        $encrypted1 = sf_pii_encrypt($plaintext);
        $encrypted2 = sf_pii_encrypt($plaintext);

        $this->assertNotNull($encrypted1);
        $this->assertNotNull($encrypted2);
        $this->assertNotSame($encrypted1, $encrypted2);

        // But they both decrypt to the same plaintext
        $this->assertSame($plaintext, sf_pii_decrypt($encrypted1));
        $this->assertSame($plaintext, sf_pii_decrypt($encrypted2));
    }
}
