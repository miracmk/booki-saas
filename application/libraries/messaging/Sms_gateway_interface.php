<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - SMS gateway interface (2026-08-27).
 *
 * Contract for SMS providers (Netgsm, etc). Implementations must never throw
 * exceptions - failures are returned in the result array with 'success' => false
 * and an 'error' string, allowing callers to degrade gracefully when a gateway
 * is unconfigured or unreachable.
 *
 * @package Libraries\Messaging
 */

interface Sms_gateway_interface
{
    /**
     * Send an SMS message to a phone number.
     *
     * @param string $to_phone Recipient phone number (format provider-dependent, e.g. +90XXXXXXXXXX)
     * @param string $message Plain text message body
     *
     * @return array Result array with keys:
     *   - success (bool): true if the message was sent/accepted by the provider
     *   - provider_message_id (string|null): unique identifier from the provider for this message
     *   - error (string|null): error description if success is false
     *
     *   Example success: ['success' => true, 'provider_message_id' => '12345', 'error' => null]
     *   Example failure: ['success' => false, 'provider_message_id' => null, 'error' => 'not_configured']
     */
    public function send(string $to_phone, string $message): array;

    /**
     * Get the current account balance (if available).
     *
     * @return float|null Account balance in the provider's units (typically message credits or currency),
     *   or null if the balance cannot be determined (gateway unconfigured or provider doesn't support this).
     */
    public function get_balance(): ?float;
}
