<?php defined('BASEPATH') or exit('No direct script access allowed');

use GuzzleHttp\Client;

/* ----------------------------------------------------------------------------
 * BooKi - WhatsApp unofficial-mode bridge client (Dalga 3 / Faz 3.5,
 * 2026-09-10).
 *
 * Thin REST client for the separate Node sidecar container ("ki-wa-bridge",
 * Baileys/whatsapp-web.js based) that performs QR-device pairing and websocket
 * maintenance for the unofficial WhatsApp sender. The PHP app NEVER talks to
 * the Meta/Gateway internals of that connection - it only calls the bridge's
 * small HTTP contract:
 *
 *   GET  /health                         -> {status, version, ...}
 *   POST /v1/session/{tenant}/start      -> {status:'connecting'}
 *   GET  /v1/session/{tenant}/status     -> {status, qr?, name?, ...}
 *   POST /v1/session/{tenant}/logout
 *   POST /v1/send                        -> {success, message_id?, error?}
 *
 * Inbound messages arrive back at the app on whatsapp/bridge_inbound with an
 * `X-Bridge-Secret` header that verify_secret_header() checks.
 *
 * See docs/whatsapp-bridge-contract.md for the full contract the sidecar must
 * implement.
 *
 * @package Libraries
 */
class Whatsapp_bridge
{
    private string $base_url;

    private ?string $secret;

    public function __construct(?string $base_url = null, ?string $secret = null)
    {
        $this->base_url = rtrim((string) $base_url, '/');
        $this->secret = $secret;
    }

    public function is_configured(): bool
    {
        return $this->base_url !== '';
    }

    /**
     * Low-level request to the bridge. Returns null on transport error or if the
     * bridge answers with a non-JSON / non-array body.
     */
    private function request(string $method, string $path, array $payload = [], int $timeout = 10): ?array
    {
        if (!$this->is_configured()) {
            return null;
        }

        try {
            $client = new Client(['timeout' => $timeout]);

            $options = ['headers' => []];
            if ($this->secret !== null && $this->secret !== '') {
                $options['headers']['X-Bridge-Secret'] = $this->secret;
            }

            if ($method === 'GET' && !empty($payload)) {
                $options['query'] = $payload;
            } elseif ($method !== 'GET' && !empty($payload)) {
                $options['headers']['Content-Type'] = 'application/json';
                $options['json'] = $payload;
            }

            $response = $client->request($method, $this->base_url . $path, $options);
            $decoded  = json_decode((string) $response->getBody(), true);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable $e) {
            log_message('error', 'Whatsapp_bridge - ' . $method . ' ' . $path . ' failed: ' . $e->getMessage());

            return null;
        }
    }

    public function health(): ?array
    {
        return $this->request('GET', '/health');
    }

    public function session_start(string $tenant, array $config = []): ?array
    {
        return $this->request('POST', '/v1/session/' . rawurlencode($tenant) . '/start', $config);
    }

    public function session_status(string $tenant): ?array
    {
        return $this->request('GET', '/v1/session/' . rawurlencode($tenant) . '/status');
    }

    public function session_logout(string $tenant): ?array
    {
        return $this->request('POST', '/v1/session/' . rawurlencode($tenant) . '/logout');
    }

    /**
     * Send a WhatsApp message through the bridge (unofficial mode).
     *
     * @return array ['success' => bool, 'message_id' => ?string, 'error' => ?string]
     */
    public function send(string $tenant, string $to, string $text): array
    {
        if (!$this->is_configured()) {
            return ['success' => false, 'message_id' => null, 'error' => 'not_configured'];
        }

        // Normalize Turkish and international numbers to E.164 (without +)
        $clean_to = preg_replace('/[^\d]/', '', $to);
        if (str_starts_with($clean_to, '0') && strlen($clean_to) === 11) {
            $clean_to = '9' . $clean_to;
        } elseif (strlen($clean_to) === 10 && str_starts_with($clean_to, '5')) {
            $clean_to = '90' . $clean_to;
        }

        $result = $this->request('POST', '/v1/send', [
            'tenant' => $tenant,
            'to' => $clean_to,
            'text' => $text,
        ], 7);

        if ($result !== null && !empty($result['success']) && !empty($result['message_id'])) {
            return ['success' => true, 'message_id' => $result['message_id'], 'error' => null];
        }

        return ['success' => false, 'message_id' => null, 'error' => is_array($result)
            ? (string) ($result['error'] ?? 'unknown_error')
            : 'bridge_unreachable',
        ];
    }

    /**
     * Constant-time comparison of the secret sent by the bridge (inbound
     * whatsapp/bridge_inbound requests) against the stored one. Message auth, not
     * a webhook we blindly trust.
     */
    public function verify_secret_header(string $received): bool
    {
        if ($this->secret === null || $this->secret === '') {
            return false;
        }

        return is_string($received) && hash_equals($this->secret, $received);
    }
}