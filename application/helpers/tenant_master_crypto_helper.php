<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - multi-tenant SaaS support (2026-08-26).
 *
 * Protects each tenant's OWN secrets (their database password, their PII_ENC_KEY/PII_HASH_KEY) at
 * rest in the master `tenants` table, using a SEPARATE key (TENANT_MASTER_KEY, env var, only ever
 * needed by the master/control-plane connection) from the per-tenant PII keys those secrets
 * themselves protect - the same "don't reuse a key across trust boundaries" reasoning as
 * salonflora_crypto_helper.php's separate encryption/hash keys. Deliberately a standalone AES-256-GCM
 * implementation (not a call into salonflora_crypto_helper.php) so the two never share a version tag
 * or get confused for one another.
 * ---------------------------------------------------------------------------- */

if (!function_exists('tenant_master_key')) {
    /**
     * @return string 32 raw bytes.
     */
    function tenant_master_key(): string
    {
        static $key = null;

        if ($key === null) {
            $encoded = getenv('TENANT_MASTER_KEY');

            if (empty($encoded)) {
                throw new RuntimeException('TENANT_MASTER_KEY is not set - tenant secrets cannot be protected.');
            }

            $key = base64_decode($encoded, true);

            if ($key === false || strlen($key) !== 32) {
                throw new RuntimeException('TENANT_MASTER_KEY must be a base64-encoded 32-byte key.');
            }
        }

        return $key;
    }
}

if (!function_exists('tenant_master_encrypt')) {
    function tenant_master_encrypt(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return null;
        }

        $nonce = random_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', tenant_master_key(), OPENSSL_RAW_DATA, $nonce, $tag);

        if ($ciphertext === false) {
            throw new RuntimeException('Tenant secret encryption failed.');
        }

        return 'TMENC1:' . base64_encode($nonce . $tag . $ciphertext);
    }
}

if (!function_exists('tenant_master_decrypt')) {
    /**
     * @throws RuntimeException On a malformed value or a failed GCM authentication check.
     */
    function tenant_master_decrypt(?string $encoded): ?string
    {
        if ($encoded === null || $encoded === '') {
            return null;
        }

        if (!str_starts_with($encoded, 'TMENC1:')) {
            throw new RuntimeException('Value is not a recognized tenant-secret ciphertext.');
        }

        $raw = base64_decode(substr($encoded, strlen('TMENC1:')), true);

        if ($raw === false || strlen($raw) < 28) {
            throw new RuntimeException('Malformed tenant-secret ciphertext.');
        }

        $nonce = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);

        $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', tenant_master_key(), OPENSSL_RAW_DATA, $nonce, $tag);

        if ($plaintext === false) {
            throw new RuntimeException('Tenant secret decryption/authentication failed.');
        }

        return $plaintext;
    }
}
