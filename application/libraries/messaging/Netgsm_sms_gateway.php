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

        try {
            $client = new Client();

            // Netgsm send endpoint (simple GET/POST with basic auth or params)
            $response = $client->get('https://api.netgsm.com.tr/sms/send/get', [
                'query' => [
                    'username' => $this->username,
                    'password' => $this->password,
                    'to' => $to_phone,
                    'message' => $message,
                    'header' => $this->header ?: 'No-Header',
                    'lang' => '1', // 1 = Turkish (UTF-8), 0 = English
                ],
                'timeout' => 10,
            ]);

            $body = (string) $response->getBody();
            $parts = explode(':', $body);

            // Netgsm responds with "result_code:message_id" or error code
            // 00 = success, other codes are errors
            if (isset($parts[0]) && $parts[0] === '00' && isset($parts[1])) {
                return [
                    'success' => true,
                    'provider_message_id' => trim($parts[1]),
                    'error' => null,
                ];
            }

            $error_code = trim($parts[0] ?? 'unknown');

            return [
                'success' => false,
                'provider_message_id' => null,
                'error' => 'netgsm_error_' . $error_code,
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
