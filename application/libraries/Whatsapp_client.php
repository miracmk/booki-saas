<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Meta WhatsApp Business Cloud API client (2026-08-27).
 *
 * Mirrors the Telegram_client pattern - talks to Meta's Cloud API directly
 * with credentials stored in messaging_settings (already decrypted at load time).
 * No exceptions thrown; API failures return false or empty array.
 *
 * @package Libraries
 */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Whatsapp_client
{
    /**
     * @var string|null Phone number ID from Meta
     */
    private ?string $phone_number_id;

    /**
     * @var string|null Access token for the WABA (WhatsApp Business Account)
     */
    private ?string $access_token;

    /**
     * @var string|null Webhook verify token (for validating incoming webhook requests)
     */
    private ?string $webhook_verify_token;

    public function __construct(?string $phone_number_id = null, ?string $access_token = null, ?string $webhook_verify_token = null)
    {
        $this->phone_number_id = $phone_number_id;
        $this->access_token = $access_token;
        $this->webhook_verify_token = $webhook_verify_token;
    }

    /**
     * Check if the WhatsApp gateway is properly configured.
     *
     * @return bool True if phone_number_id and access_token are both present and non-empty.
     */
    public function is_configured(): bool
    {
        return !empty($this->phone_number_id) && !empty($this->access_token);
    }

    /**
     * Make an API call to Meta's WhatsApp Cloud API.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint path (e.g. '/messages')
     * @param array $data Request body/query for POST/GET
     *
     * @return array|null Decoded JSON response, or null on error.
     */
    private function call(string $method, string $endpoint, array $data = [], bool $as_query = false): ?array
    {
        if (!$this->is_configured()) {
            return null;
        }

        try {
            $client = new Client();

            $url = 'https://graph.facebook.com/v19.0/' . $this->phone_number_id . $endpoint;

            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->access_token,
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 10,
            ];

            if (!empty($data)) {
                if ($as_query) {
                    $options['query'] = $data;
                } else {
                    $options['json'] = $data;
                }
            }

            $response = $client->request($method, $url, $options);

            $decoded = json_decode((string) $response->getBody(), true);

            return is_array($decoded) ? $decoded : null;
        } catch (GuzzleException|Throwable $e) {
            log_message('error', 'Whatsapp_client - ' . $method . ' ' . $endpoint . ' failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Fetch the connected WABA phone number's account details from Meta.
     *
     * Used by the onboarding wizard's "test connection" step to prove the
     * saved credentials actually work and display business information.
     *
     * @return array|null Decoded account info, or null if not configured / unreachable.
     *   Contains display_phone_number, verified_name, quality_rating, id, ...
     */
    public function get_account_info(): ?array
    {
        if (!$this->is_configured()) {
            return null;
        }

        return $this->call('GET', '/', [
            'fields' => 'id,display_phone_number,verified_name,code_verification_status,quality_rating',
        ], true);
    }

    /**
     * Send a text message to a WhatsApp contact.
     *
     * @param string $to Recipient WhatsApp ID (phone number format, e.g. "90xxxxxxxxxx" or "1xxxxxxxxxx")
     * @param string $body Message text
     *
     * @return array Result array with keys:
     *   - success (bool)
     *   - message_id (string|null): Meta's message ID if successful
     *   - error (string|null): error description if not successful
     */
    public function send_text(string $to, string $body): array
    {
        if (!$this->is_configured()) {
            return ['success' => false, 'message_id' => null, 'error' => 'not_configured'];
        }

        if (empty($to) || empty($body)) {
            return ['success' => false, 'message_id' => null, 'error' => 'invalid_params'];
        }

        $result = $this->call('POST', '/messages', [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $body,
            ],
        ]);

        if ($result && isset($result['messages'][0]['id'])) {
            return [
                'success' => true,
                'message_id' => $result['messages'][0]['id'],
                'error' => null,
            ];
        }

        $error = $result['error']['message'] ?? 'unknown_error';

        return [
            'success' => false,
            'message_id' => null,
            'error' => $error,
        ];
    }

    /**
     * Send a WhatsApp template message (typically used for notifications).
     *
     * @param string $to Recipient WhatsApp ID
     * @param string $template_name Template name as defined in Meta Business Manager
     * @param array $params Template parameters (body variables, indexed 0, 1, 2, ...)
     *
     * @return array Result array (see send_text())
     */
    public function send_template(string $to, string $template_name, array $params = []): array
    {
        if (!$this->is_configured()) {
            return ['success' => false, 'message_id' => null, 'error' => 'not_configured'];
        }

        if (empty($to) || empty($template_name)) {
            return ['success' => false, 'message_id' => null, 'error' => 'invalid_params'];
        }

        $template_body = [];
        if (!empty($params)) {
            $template_body['parameters'] = array_map(
                fn($param) => ['type' => 'text', 'text' => $param],
                $params,
            );
        }

        $result = $this->call('POST', '/messages', [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $template_name,
                ...$template_body,
            ],
        ]);

        if ($result && isset($result['messages'][0]['id'])) {
            return [
                'success' => true,
                'message_id' => $result['messages'][0]['id'],
                'error' => null,
            ];
        }

        $error = $result['error']['message'] ?? 'unknown_error';

        return [
            'success' => false,
            'message_id' => null,
            'error' => $error,
        ];
    }

    /**
     * Verify the webhook token sent by Meta in a verification request.
     *
     * @param string $token Token from Meta's hub.verify_token query parameter
     *
     * @return bool True if the token matches the configured webhook_verify_token.
     */
    public function verify_webhook_token(string $token): bool
    {
        if (empty($this->webhook_verify_token)) {
            return false;
        }

        return hash_equals($this->webhook_verify_token, $token);
    }

    /**
     * Parse an incoming webhook payload from Meta.
     *
     * @param array $payload Decoded webhook payload (Meta sends this as the request body)
     *
     * @return array Normalized message array with keys: from, body, type (or empty array if unparseable).
     *
     *   Example: ['from' => '90xxxxxxxxxx', 'body' => 'Hello', 'type' => 'text']
     */
    public function parse_incoming(array $payload): array
    {
        // Meta webhook structure: entry[].changes[].value.messages[]
        $entry = $payload['entry'][0] ?? null;
        if (!$entry) {
            return [];
        }

        $change = $entry['changes'][0] ?? null;
        if (!$change) {
            return [];
        }

        $value = $change['value'] ?? null;
        if (!$value || !isset($value['messages'])) {
            return [];
        }

        $message = $value['messages'][0] ?? null;
        if (!$message) {
            return [];
        }

        $from = $message['from'] ?? null;
        $type = $message['type'] ?? null;

        if (!$from || !$type) {
            return [];
        }

        $body = '';
        if ($type === 'text') {
            $body = $message['text']['body'] ?? '';
        } elseif ($type === 'interactive') {
            // Interactive messages (buttons, lists) - extract button reply
            $button_reply = $message['interactive']['button_reply'] ?? null;
            if ($button_reply) {
                $body = $button_reply['title'] ?? '';
            }
        } else {
            $body = '[' . $type . ' message - not text]';
        }

        return [
            'from' => $from,
            'body' => $body,
            'type' => $type,
        ];
    }
}
