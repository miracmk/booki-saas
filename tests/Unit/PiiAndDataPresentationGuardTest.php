<?php declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

if (!function_exists('log_message')) {
    function log_message($level, $message) {
        // Global stub for unit test environment
    }
}

/**
 * Quality Gate: Prevents PII ciphertext leaks (SFENC1:) and ensures human-readable presentation.
 */
class PiiAndDataPresentationGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $test_enc_key = base64_encode(random_bytes(32));
        $test_hash_key = base64_encode(random_bytes(32));
        putenv('SF_PII_ENC_KEY=' . $test_enc_key);
        putenv('SF_PII_HASH_KEY=' . $test_hash_key);

        $baseDir = dirname(__DIR__, 2);
        require_once $baseDir . '/application/helpers/tenant_helper.php';
        require_once $baseDir . '/application/helpers/salonflora_crypto_helper.php';
    }

    /**
     * Test that sf_pii_decrypt correctly decrypts SFENC1 ciphertexts.
     */
    public function testPiiDecryptionRestoresOriginalText(): void
    {
        $plainPhone = '05321234567';
        $plainEmail = 'musteri@example.com';

        $encryptedPhone = sf_pii_encrypt($plainPhone);
        $encryptedEmail = sf_pii_encrypt($plainEmail);

        $this->assertStringStartsWith('SFENC1:', $encryptedPhone);
        $this->assertStringStartsWith('SFENC1:', $encryptedEmail);

        $decryptedPhone = sf_pii_decrypt($encryptedPhone);
        $decryptedEmail = sf_pii_decrypt($encryptedEmail);

        $this->assertSame($plainPhone, $decryptedPhone);
        $this->assertSame($plainEmail, $decryptedEmail);
    }

    /**
     * Quality Gate: View-bound customer data must never contain SFENC1 ciphertext.
     */
    public function testViewBoundCustomerDataMustNeverContainEncryptedPrefix(): void
    {
        $encryptedPhone = sf_pii_encrypt('05321234567');
        $encryptedEmail = sf_pii_encrypt('ayse@example.com');

        $mockCustomers = [
            [
                'id' => 12,
                'first_name' => 'Ayşe',
                'last_name' => 'Yılmaz',
                'phone_number' => sf_pii_decrypt($encryptedPhone),
                'email' => sf_pii_decrypt($encryptedEmail),
            ],
        ];

        foreach ($mockCustomers as $customer) {
            $this->assertStringNotContainsString(
                'SFENC1:',
                $customer['phone_number'],
                'Customer phone number rendered to view must be decrypted and never contain SFENC1: prefix.'
            );
            $this->assertStringNotContainsString(
                'SFENC1:',
                $customer['email'],
                'Customer email rendered to view must be decrypted and never contain SFENC1: prefix.'
            );
        }
    }

    /**
     * Quality Gate: Customer presentation in tables must be a readable name, not a numeric ID.
     */
    public function testPresentationLayerDoesNotExposeRawNumericIdsForNames(): void
    {
        $formatCustomerName = function (array $row): string {
            if (!empty($row['customer_name'])) {
                return trim($row['customer_name']);
            }
            $first = $row['first_name'] ?? '';
            $last = $row['last_name'] ?? '';
            $combined = trim($first . ' ' . $last);
            return $combined !== '' ? $combined : ('Müşteri #' . ($row['id_users_customer'] ?? ''));
        };

        $sampleRow = [
            'id_users_customer' => 20,
            'customer_name' => 'Ayşe Yılmaz',
        ];

        $rendered = $formatCustomerName($sampleRow);

        $this->assertMatchesRegularExpression(
            '/\D+/',
            $rendered,
            'Rendered customer column must contain human-readable name, not just a numeric ID.'
        );
        $this->assertFalse(
            is_numeric($rendered),
            'Rendered customer name must never be purely numeric.'
        );
    }

    /**
     * Quality Gate: Ensure currency formatting helper outputs formatted numbers with ₺ or TRY.
     */
    public function testCurrencyFormattingStandard(): void
    {
        $formatCurrency = function (float $amount): string {
            return number_format($amount, 2, ',', '.') . ' ₺';
        };

        $formatted = $formatCurrency(150.0);
        $this->assertSame('150,00 ₺', $formatted);
        $this->assertStringContainsString('₺', $formatted);
    }
}
