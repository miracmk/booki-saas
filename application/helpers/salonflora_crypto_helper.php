<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - column-level encryption for sensitive customer/provider PII (phone
 * number, email, address, "Semt/Mahalle", zip code, notes), added for KVKK/data-protection hardening
 * (2026-08-24). First/last name are deliberately NOT covered here - staff rely on partial name search
 * every day, and that's not something encryption (which only supports exact-match lookups without a
 * much larger blind-index project) can preserve.
 *
 * Two independent keys, both 32 raw bytes, base64-encoded in the environment (docker-compose.yml /
 * .env, same pattern as every other Salon Flora secret):
 *
 * - SF_PII_ENC_KEY: AES-256-GCM encryption key. Ciphertext is authenticated (GCM tag) - a tampered or
 *   truncated value fails to decrypt rather than returning corrupted plaintext.
 * - SF_PII_HASH_KEY: HMAC-SHA256 key for a separate, exact-match search index (sf_pii_hash()). A
 *   different key is used than for encryption, deliberately - reusing one key for two different
 *   cryptographic purposes is a well-known way to weaken both.
 *
 * Ciphertext format (sf_pii_encrypt() output): base64(12-byte GCM nonce . 16-byte GCM tag . ciphertext).
 * Nonce is random per call (never reused for a given key), which is required for GCM's security
 * guarantees - two encryptions of the same plaintext will not produce the same ciphertext.
 *
 * Hash format (sf_pii_hash() output): lowercase hex HMAC-SHA256 of the trimmed, lowercased plaintext -
 * normalized so an exact-match search doesn't depend on case/whitespace the way the old `= value`
 * lookup wouldn't have either. This is NOT encryption - two equal plaintexts always hash to the same
 * value, which is exactly what makes an indexed exact-match search possible, but it also means the
 * hash column must never be treated as confidential on its own (it doesn't reveal the plaintext, but a
 * known-plaintext dictionary attack against a low-entropy field, like a 10-digit phone number, is
 * feasible for anyone who also has DB access - the encrypted column remains the actual protection).
 * ---------------------------------------------------------------------------- */

if (!function_exists('sf_pii_enc_key')) {
    /**
     * @return string 32 raw bytes.
     */
    function sf_pii_enc_key(): string
    {
        // Ki Reservation (2026-08-26) - multi-tenant mode: each tenant has its own key, resolved and
        // decrypted once per request by Tenant_resolver (see tenant_helper.php's docblock). Never
        // statically cached here - a single PHP worker process can serve different tenants across
        // requests. Single-tenant/standalone deployments (tenant_context() stays null) fall through
        // to the env-var key below, unchanged from before multi-tenancy existed.
        $tenant = tenant_context();

        if ($tenant !== null) {
            if (empty($tenant['pii_enc_key'])) {
                throw new RuntimeException('Tenant is missing a PII encryption key.');
            }

            return $tenant['pii_enc_key'];
        }

        static $key = null;

        if ($key === null) {
            $encoded = getenv('SF_PII_ENC_KEY');

            if (empty($encoded)) {
                throw new RuntimeException('SF_PII_ENC_KEY is not set - PII encryption cannot proceed.');
            }

            $key = base64_decode($encoded, true);

            if ($key === false || strlen($key) !== 32) {
                throw new RuntimeException('SF_PII_ENC_KEY must be a base64-encoded 32-byte key.');
            }
        }

        return $key;
    }
}

if (!function_exists('sf_pii_hash_key')) {
    /**
     * @return string 32 raw bytes.
     */
    function sf_pii_hash_key(): string
    {
        // Ki Reservation (2026-08-26) - see the identical comment in sf_pii_enc_key().
        $tenant = tenant_context();

        if ($tenant !== null) {
            if (empty($tenant['pii_hash_key'])) {
                throw new RuntimeException('Tenant is missing a PII hash key.');
            }

            return $tenant['pii_hash_key'];
        }

        static $key = null;

        if ($key === null) {
            $encoded = getenv('SF_PII_HASH_KEY');

            if (empty($encoded)) {
                throw new RuntimeException('SF_PII_HASH_KEY is not set - PII search hashing cannot proceed.');
            }

            $key = base64_decode($encoded, true);

            if ($key === false || strlen($key) !== 32) {
                throw new RuntimeException('SF_PII_HASH_KEY must be a base64-encoded 32-byte key.');
            }
        }

        return $key;
    }
}

if (!function_exists('sf_pii_encrypt')) {
    /**
     * @param string|null $plaintext
     *
     * @return string|null Null in, null out (so an empty/absent field stays empty rather than becoming
     *   a "ciphertext of an empty string").
     */
    function sf_pii_encrypt(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return null;
        }

        $nonce = random_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', sf_pii_enc_key(), OPENSSL_RAW_DATA, $nonce, $tag);

        if ($ciphertext === false) {
            throw new RuntimeException('PII encryption failed.');
        }

        // Salon Flora - "SFENC1:" version tag makes ciphertext trivially distinguishable from
        // still-plaintext data (the backfill migration uses this to skip rows it already encrypted,
        // making it safely re-runnable) and from a future format if the scheme ever changes.
        return 'SFENC1:' . base64_encode($nonce . $tag . $ciphertext);
    }
}

if (!function_exists('sf_pii_is_encrypted')) {
    /**
     * @param string|null $value
     *
     * @return bool True if $value looks like it was produced by sf_pii_encrypt() (has the "SFENC1:"
     *   version tag) rather than being plaintext.
     */
    function sf_pii_is_encrypted(?string $value): bool
    {
        return $value !== null && str_starts_with($value, 'SFENC1:');
    }
}

if (!function_exists('sf_pii_decrypt')) {
    /**
     * @param string|null $encoded
     *
     * @return string|null Null in, null out. Also returns null (rather than throwing) for a value that
     *   fails to decrypt/authenticate, so a single corrupted row degrades to "field unavailable" instead
     *   of taking down the whole request - callers that need to know the difference should check
     *   sf_pii_decrypt_or_fail() instead.
     */
    function sf_pii_decrypt(?string $encoded): ?string
    {
        try {
            return sf_pii_decrypt_or_fail($encoded);
        } catch (Throwable $e) {
            log_message('error', 'sf_pii_decrypt: ' . $e->getMessage());

            return null;
        }
    }
}

if (!function_exists('sf_pii_decrypt_or_fail')) {
    /**
     * @param string|null $encoded
     *
     * @return string|null Null in, null out.
     *
     * @throws RuntimeException On a malformed value or a failed GCM authentication check (tampered or
     *   corrupted ciphertext, or the wrong key).
     */
    function sf_pii_decrypt_or_fail(?string $encoded): ?string
    {
        if ($encoded === null || $encoded === '') {
            return null;
        }

        if (!str_starts_with($encoded, 'SFENC1:')) {
            throw new RuntimeException('Value is not SFENC1-tagged ciphertext (looks like plaintext).');
        }

        $raw = base64_decode(substr($encoded, 7), true);

        if ($raw === false || strlen($raw) < 12 + 16) {
            throw new RuntimeException('Malformed PII ciphertext.');
        }

        $nonce = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);

        $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', sf_pii_enc_key(), OPENSSL_RAW_DATA, $nonce, $tag);

        if ($plaintext === false) {
            throw new RuntimeException('PII decryption failed (tampered ciphertext or wrong key).');
        }

        return $plaintext;
    }
}

if (!function_exists('sf_pii_hash')) {
    /**
     * @param string|null $plaintext
     *
     * @return string|null Null in, null out.
     */
    function sf_pii_hash(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return null;
        }

        $normalized = mb_strtolower(trim($plaintext));

        return hash_hmac('sha256', $normalized, sf_pii_hash_key());
    }
}
