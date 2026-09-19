<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Netgsm SMS gateway implementation (2026-08-27).
 *
 * Netgsm REST API integration. Credentials are expected to be passed to the
 * constructor (typically from messaging_settings table, already decrypted).
 * If credentials are empty, send() and get_balance() silently return error/null
 * rather than throwing - allowing notifications to degrade gracefully.
 *
 * @package Libraries\Messaging
 */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Netgsm_sms_gateway implements Sms_gateway_interface
{
    /**
     * @var string|null Netgsm username
     */
    private ?string $username;

    /**
     * @var string|null Netgsm password
     */
    private ?string $password;

    /**
     * @var string|null Netgsm sender header (usually a brand name or phone number)
     */
    private ?string $header;

    public function __construct(?string $username = null, ?string $password = null, ?string $header = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->header = $header;
    }

    /**
     * Check if the gateway is properly configured.
     *
     * @return bool True if all required credentials are present and non-empty.
     */
    private function is_configured(): bool
    {
        return !empty($this->username) && !empty($this->password);
    }

    /**
     * Send an SMS via Netgsm API.
     *
     * @param string $to_phone Recipient phone number
     * @param string $message Message text
     *
     * @return array Result array (see Sms_gateway_interface)
     */
    public function send(string $to_phone, string $message): array
    {
        if (!$this->is_configured()) {
            return ['success' => false, 'provider_message_id' => null, 'error' => 'not_configured'];
        }

        // Clean phone number: remove all non-digits
        $clean_phone = preg_replace('/[^\d]/', '', $to_phone);
        // Netgsm expects 10 digits (5xxxxxxxxx) or 12 digits (905xxxxxxxxx)
        if (str_starts_with($clean_phone, '0') && strlen($clean_phone) === 11) {
            $clean_phone = substr($clean_phone, 1);
        }

        try {
            $client = new Client();

            // Netgsm POST endpoint with standard parameters: usercode, password, gsmno, message, msgheader
            $response = $client->post('https://api.netgsm.com.tr/sms/send/post', [
                'form_params' => [
                    'usercode' => $this->username,
                    'password' => $this->password,
                    'gsmno' => $clean_phone,
                    'message' => $message,
                    'msgheader' => $this->header ?: 'No-Header',
                    'dil' => 'TR',
                ],
                'timeout' => 10,
            ]);

            $body = trim((string) $response->getBody());

            // Netgsm responds with "00 <bulkid>" or "00:<bulkid>" on success
            if (str_starts_with($body, '00')) {
                $id_part = trim(ltrim(substr($body, 2), ': '));
                return [
                    'success' => true,
                    'provider_message_id' => $id_part ?: 'ok',
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'provider_message_id' => null,
                'error' => 'netgsm_error_' . $body,
            ];
        } catch (GuzzleException|Throwable $e) {
            log_message('error', 'Netgsm_sms_gateway::send - ' . $e->getMessage());

            return [
                'success' => false,
                'provider_message_id' => null,
                'error' => 'network_error',
            ];
        }
    }

    /**
     * Get account balance from Netgsm (not implemented yet - returns null).
     *
     * @return float|null Always null for now (Netgsm balance endpoint not yet integrated).
     */
    public function get_balance(): ?float
    {
        // TODO: Implement balance query via Netgsm API if needed
        return null;
    }
}
