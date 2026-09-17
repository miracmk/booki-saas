<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Payment settings model (2026-08-27).
 *
 * Handles the payment_settings table operations. Manages encryption of sensitive
 * API keys using sf_pii_encrypt/sf_pii_decrypt (same as customer PII).
 * ---------------------------------------------------------------------------- */

class Payment_settings_model extends EA_Model
{
    /**
     * Encrypted fields (API keys, secrets, webhook secret).
     *
     * @var array
     */
    private array $encrypted_fields = [
        'iyzico_api_key',
        'iyzico_secret_key',
        'paytr_merchant_id',
        'paytr_merchant_key',
        'paytr_merchant_salt',
        'stripe_publishable_key',
        'stripe_secret_key',
        'webhook_secret',
        'odeal_api_key',
        'odeal_secret_key',
        'garanti_prov_password',
        'garanti_store_key',
        'enpara_store_key',
    ];

    /**
     * Get the current payment settings (always returns a single row, decrypted).
     *
     * @return array Settings row with sensitive fields decrypted.
     *
     * @throws RuntimeException If no settings row exists (should never happen - migration creates default).
     */
    public function get_settings(): array
    {
        $settings = $this->db->get('payment_settings')->row_array();

        if (!$settings) {
            throw new RuntimeException('No payment settings found in database.');
        }

        // Decrypt all sensitive fields
        foreach ($this->encrypted_fields as $field) {
            if (!empty($settings[$field])) {
                $settings[$field] = sf_pii_decrypt($settings[$field]);
            }
        }

        return $settings;
    }

    /**
     * Save (update) payment settings.
     *
     * Encrypts sensitive fields before storage. Preserves existing values for any
     * fields left empty (does not overwrite with null).
     *
     * @param array $data Settings data to update (partial update supported).
     *
     * @throws RuntimeException On validation or database errors.
     */
    public function save_settings(array $data): void
    {
        // Get current settings to preserve unmodified sensitive fields
        $current = $this->get_settings();

        // Encrypt sensitive fields (only if provided and non-empty)
        $to_save = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $this->encrypted_fields, true)) {
                // Sensitive field: encrypt if provided and non-empty, otherwise preserve current
                if ($value === null || $value === '') {
                    $to_save[$key] = $current[$key] ?? null;
                } else {
                    $to_save[$key] = sf_pii_encrypt($value);
                }
            } else {
                // Non-sensitive field: use provided value
                $to_save[$key] = $value;
            }
        }

        // Preserve any fields not included in the update
        foreach ($current as $key => $value) {
            if (!isset($to_save[$key])) {
                $to_save[$key] = $value;
            }
        }

        $to_save['updated_at'] = date('Y-m-d H:i:s');

        // Update the single payment_settings row (id=1)
        if (!$this->db->update('payment_settings', $to_save, ['id' => 1])) {
            throw new RuntimeException('Could not update payment settings.');
        }
    }
}
