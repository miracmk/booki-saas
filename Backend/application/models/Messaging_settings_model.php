<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Messaging settings model (2026-08-27).
 *
 * Manages SMS/WhatsApp configuration. Single row per deployment.
 * Handles PII encryption/decryption of credentials.
 *
 * @package Models
 */

class Messaging_settings_model extends App_Model
{
    /**
     * Get the messaging settings row (decrypted).
     *
     * @return array Settings row with PII fields decrypted. Returns an empty row if none exists.
     */
    public function get_settings(): array
    {
        $row = $this->db->get('messaging_settings')->row_array();

        if (!$row) {
            $row = [
                'id' => null,
                'sms_gateway' => 'none',
                'netgsm_username' => null,
                'netgsm_password' => null,
                'netgsm_header' => null,
                'sms_notifications_enabled' => 0,
                'whatsapp_phone_number_id' => null,
                'whatsapp_access_token' => null,
                'whatsapp_waba_id' => null,
                'whatsapp_webhook_verify_token' => null,
                'whatsapp_business_phone_display' => null,
                'whatsapp_notifications_enabled' => 0,
                'email_notifications_enabled' => 1,
                'call_notifications_enabled' => 0,
                'call_provider' => null,
                'call_api_key' => null,
                'call_from_number' => null,
                'telegram_notifications_enabled' => 0,
                'telegram_bot_token' => null,
                'instagram_notifications_enabled' => 0,
                'instagram_access_token' => null,
                'instagram_account_id' => null,
                'whatsapp_mode' => 'official',
                'whatsapp_unofficial_status' => 'disconnected',
                'whatsapp_unofficial_name' => null,
                'whatsapp_unofficial_consent_at' => null,
                'whatsapp_bridge_url' => null,
                'whatsapp_bridge_secret' => null,
                'smtp_host' => null,
                'smtp_port' => null,
                'smtp_crypto' => null,
                'smtp_user' => null,
                'smtp_pass' => null,
                'smtp_from_name' => null,
                'smtp_from_address' => null,
                'default_notification_channel' => 'telegram',
                'default_notification_channels' => 'telegram',
                'ai_reply_whatsapp_enabled' => 0,
                'ai_reply_telegram_enabled' => 0,
                'ai_reply_instagram_enabled' => 0,
                'instagram_webhook_verify_token' => null,
                'reminder_notifications_enabled' => 1,
                'reminder_hours_ahead' => 24,
                'reminder_offsets' => [24],
            ];
        } else {
            // Decrypt sensitive fields
            $row['netgsm_username'] = sf_pii_decrypt($row['netgsm_username']);
            $row['netgsm_password'] = sf_pii_decrypt($row['netgsm_password']);
            $row['whatsapp_phone_number_id'] = sf_pii_decrypt($row['whatsapp_phone_number_id']);
            $row['whatsapp_access_token'] = sf_pii_decrypt($row['whatsapp_access_token']);
            $row['whatsapp_waba_id'] = sf_pii_decrypt($row['whatsapp_waba_id']);
            $row['whatsapp_webhook_verify_token'] = sf_pii_decrypt($row['whatsapp_webhook_verify_token']);
            $row['whatsapp_bridge_secret'] = sf_pii_decrypt($row['whatsapp_bridge_secret']);
            $row['call_api_key'] = sf_pii_decrypt($row['call_api_key']);
            $row['telegram_bot_token'] = sf_pii_decrypt($row['telegram_bot_token']);
            $row['instagram_access_token'] = sf_pii_decrypt($row['instagram_access_token']);
            $row['instagram_webhook_verify_token'] = sf_pii_is_encrypted($row['instagram_webhook_verify_token'] ?? null)
                ? sf_pii_decrypt($row['instagram_webhook_verify_token'])
                : ($row['instagram_webhook_verify_token'] ?? null);
            $row['smtp_user'] = sf_pii_decrypt($row['smtp_user']);
            $row['smtp_pass'] = sf_pii_decrypt($row['smtp_pass']);

            $row['reminder_notifications_enabled'] = isset($row['reminder_notifications_enabled']) ? (int) $row['reminder_notifications_enabled'] : 1;
            $row['reminder_hours_ahead'] = isset($row['reminder_hours_ahead']) ? (int) $row['reminder_hours_ahead'] : 24;
            $row['reminder_offsets'] = $this->normalize_reminder_offsets($row['reminder_offsets'] ?? null);
        }

        // The admin can always override these per tenant, but ship our own
        // "ki-wa-bridge" sidecar (bridge/, deploy compose service `wa-bridge`) as
        // the default unofficial-mode bridge so the QR wizard works out of the
        // box without requiring manual setup first.
        if (empty($row['whatsapp_bridge_url'])) {
            $row['whatsapp_bridge_url'] = getenv('WA_BRIDGE_URL') ?: 'http://wa-bridge:3000';
        }
        if (empty($row['whatsapp_bridge_secret'])) {
            $row['whatsapp_bridge_secret'] = getenv('WA_BRIDGE_SECRET') ?: null;
        }

        return $row;
    }

    /**
     * Normalize a reminder-offset value into a safe, sorted integer array.
     *
     * Accepts a JSON string (as stored in messaging_settings.reminder_offsets),
     * an integer, or an array. Enforces the domain rules:
     * - only integers between 1 and 336 (hours)
     * - deduplicated, sorted ascending
     * - at most 4 offsets
     * - an explicit empty value means "no reminders at all" ([] preserved)
     * - null / '' mean "not configured" and fall back to the legacy [24]
     *
     * @param mixed $raw
     * @return array
     */
    public function normalize_reminder_offsets($raw): array
    {
        // Not configured -> legacy single offset behaviour.
        if ($raw === null || $raw === '') {
            return [24];
        }

        $offsets = $raw;
        if (is_string($offsets)) {
            $decoded = json_decode($offsets, true);
            $offsets = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($offsets)) {
            $offsets = [$offsets];
        }

        $offsets = array_map('intval', $offsets);
        $offsets = array_filter($offsets, static fn ($value) => $value >= 1 && $value <= 336);
        $offsets = array_values(array_unique($offsets));
        sort($offsets);

        return array_slice($offsets, 0, 4);
    }

    /**
     * Save messaging settings (encrypts sensitive fields).
     *
     * @param array $data New settings values. Keys can include sms_gateway, netgsm_username, etc.
     *   Empty/null values in $data preserve existing values (partial updates).
     *
     * @return void
     */
    public function save_settings(array $data): void
    {
        // Get existing row
        $existing = $this->get_settings();

        // Build update array: if a key is in $data and is not null/empty, use new value; else keep existing
        $to_update = [];

        $plaintext_fields = ['sms_gateway', 'netgsm_header', 'whatsapp_business_phone_display',
            'sms_notifications_enabled', 'whatsapp_notifications_enabled', 'email_notifications_enabled',
            'call_notifications_enabled', 'call_provider', 'call_from_number',
            'telegram_notifications_enabled', 'instagram_notifications_enabled', 'instagram_account_id',
            'whatsapp_mode',
            'whatsapp_unofficial_status', 'whatsapp_unofficial_name',
            'whatsapp_unofficial_consent_at', 'whatsapp_bridge_url', 'smtp_host', 'smtp_port',
            'smtp_crypto', 'smtp_from_name', 'smtp_from_address', 'default_notification_channel',
            'default_notification_channels',
            'ai_reply_whatsapp_enabled', 'ai_reply_telegram_enabled', 'ai_reply_instagram_enabled',
            'reminder_notifications_enabled', 'reminder_hours_ahead'];
        $encrypted_fields = ['netgsm_username', 'netgsm_password', 'whatsapp_phone_number_id',
            'whatsapp_access_token', 'whatsapp_waba_id', 'whatsapp_webhook_verify_token',
            'whatsapp_bridge_secret', 'call_api_key', 'telegram_bot_token', 'instagram_access_token',
            'instagram_webhook_verify_token',
            'smtp_user', 'smtp_pass'];

        foreach ($plaintext_fields as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                $to_update[$field] = null;
            } elseif (isset($data[$field]) && $data[$field] !== '') {
                $to_update[$field] = $data[$field];
            } elseif (isset($existing[$field])) {
                $to_update[$field] = $existing[$field];
            }
        }

        foreach ($encrypted_fields as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                $to_update[$field] = null;
            } elseif (isset($data[$field]) && $data[$field] !== '') {
                $to_update[$field] = sf_pii_encrypt($data[$field]);
            } elseif (isset($existing[$field]) && !sf_pii_is_encrypted($existing[$field])) {
                // If existing is plaintext (shouldn't happen, but safe fallback), encrypt it now
                $to_update[$field] = sf_pii_encrypt($existing[$field]);
            } elseif (isset($existing[$field])) {
                // Keep existing encrypted value
                $to_update[$field] = $existing[$field];
            }
        }

        $to_update['updated_at'] = date('Y-m-d H:i:s');

        // Multi-offset reminders: store the JSON array and keep reminder_hours_ahead
        // in sync (derived from the latest offset) for legacy consumers.
        if (isset($data['reminder_offsets']) && $this->db->field_exists('reminder_offsets', 'messaging_settings')) {
            $offsets = $this->normalize_reminder_offsets($data['reminder_offsets']);
            $to_update['reminder_offsets'] = json_encode($offsets);
            $to_update['reminder_hours_ahead'] = empty($offsets) ? 24 : max($offsets);
        }

        // Update or insert (using id=1 as the singleton row)
        $existing_id = $existing['id'];
        if ($existing_id) {
            $this->db->update('messaging_settings', $to_update, ['id' => $existing_id]);
        } else {
            $this->db->insert('messaging_settings', $to_update);
        }
    }
}
