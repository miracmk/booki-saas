<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Stripe payment gateway (2026-08-27, gerçek API çağrıları eklendi 2026-09-17).
 *
 * Implementation of Payment_gateway_interface for Stripe Payments API.
 * Docs: https://docs.stripe.com/payments/payment-intents
 *       https://docs.stripe.com/webhooks (imza doğrulama formülü)
 *
 * 2026-09-17: bu dosya önceden tamamen mock'tu (curl çağrısı yoktu, sahte ID üretiyordu) ve
 * verify_webhook_signature() imza var mı diye bakıp HER ZAMAN `true` dönüyordu (webhook
 * doğrulaması fiilen devre dışıydı). Artık gerçek PaymentIntents API + gerçek
 * `Stripe-Signature: t=...,v1=...` HMAC doğrulaması yapılıyor.
 * ---------------------------------------------------------------------------- */

require_once __DIR__ . '/Payment_gateway_interface.php';
require_once __DIR__ . '/Payment_gateway_abstract.php';

class Stripe_gateway extends Payment_gateway_abstract
{
    private function get_api_url(): string
    {
        return 'https://api.stripe.com/v1';
    }

    /**
     * Make a form-encoded request to the Stripe API (Stripe's REST API takes
     * application/x-www-form-urlencoded, not JSON - including for nested params via PHP-style
     * bracket keys, e.g. `metadata[appointment_id]`).
     *
     * @throws RuntimeException On HTTP/transport errors or an API-level error response.
     */
    private function api_request(string $method, string $endpoint, array $params = []): array
    {
        $secret_key = $this->get_setting('stripe_secret_key');

        if (empty($secret_key)) {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        $url = $this->get_api_url() . $endpoint;
        $body = http_build_query($params);

        $ch = curl_init();
        $opts = [
            CURLOPT_URL => $method === 'GET' && $body !== '' ? $url . '?' . $body : $url,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $secret_key,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ];

        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($ch, $opts);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $this->log_error("HTTP request failed: {$curl_error}");

            throw new RuntimeException('Stripe API request failed: ' . $curl_error);
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            $this->log_error("Invalid JSON response from Stripe: {$response}");

            throw new RuntimeException('Stripe API returned invalid JSON.');
        }

        if ($http_code >= 400) {
            $error_message = $data['error']['message'] ?? 'Unknown error';
            $this->log_error("Stripe API error ({$http_code}): {$error_message}");

            throw new RuntimeException("Stripe API error: {$error_message}");
        }

        return $data;
    }

    public function create_payment_intent(float $amount, string $currency, array $metadata): array
    {
        try {
            $params = [
                'amount' => (int) round($amount * 100), // Stripe amounts are in the smallest currency unit (kuruş).
                'currency' => strtolower($currency),
                'automatic_payment_methods' => ['enabled' => 'true'],
            ];

            foreach ($metadata as $key => $value) {
                $params['metadata'][$key] = (string) $value;
            }

            $response = $this->api_request('POST', '/payment_intents', $params);

            return [
                'intent_id' => $response['id'],
                'gateway' => 'stripe',
                'amount' => $amount,
                'currency' => strtolower($currency),
                'client_secret' => $response['client_secret'] ?? null,
                'publishable_key' => $this->get_setting('stripe_publishable_key'),
                'status' => $response['status'] ?? 'requires_payment_method',
                'checkout_form' => [
                    'type' => 'stripe_elements',
                    'intent_id' => $response['id'],
                    'client_secret' => $response['client_secret'] ?? null,
                    'publishable_key' => $this->get_setting('stripe_publishable_key'),
                ],
                'raw_response' => json_encode($response),
            ];
        } catch (Throwable $e) {
            $this->log_error('create_payment_intent failed: ' . $e->getMessage());

            throw $e;
        }
    }

    public function charge(string $intent_id, array $payload): array
    {
        // Stripe PaymentIntents (created with automatic_payment_methods) are confirmed
        // client-side by Stripe.js/Elements - there is no separate server-side "charge" call.
        // The actual result is reported back via the payment_intent.succeeded webhook
        // (see parse_webhook_event()). We just retrieve the current state for callers that
        // need a synchronous read.
        try {
            $response = $this->api_request('GET', '/payment_intents/' . $intent_id);

            return [
                'status' => ($response['status'] ?? '') === 'succeeded' ? 'succeeded' : 'pending',
                'provider_transaction_id' => $response['latest_charge'] ?? $response['id'],
                'gateway' => 'stripe',
                'intent_id' => $intent_id,
                'charged_at' => date('Y-m-d H:i:s'),
                'raw_response' => json_encode($response),
            ];
        } catch (Throwable $e) {
            $this->log_error('charge (retrieve) failed: ' . $e->getMessage());

            throw $e;
        }
    }

    public function refund(string $provider_transaction_id, ?float $amount = null): array
    {
        try {
            $params = [];

            // A payment_intent ID (pi_...) or a charge ID (ch_.../py_...) are both accepted by
            // Stripe's refund endpoint via different keys.
            if (str_starts_with($provider_transaction_id, 'pi_')) {
                $params['payment_intent'] = $provider_transaction_id;
            } else {
                $params['charge'] = $provider_transaction_id;
            }

            if ($amount !== null) {
                $params['amount'] = (int) round($amount * 100);
            }

            $response = $this->api_request('POST', '/refunds', $params);

            return [
                'status' => ($response['status'] ?? '') === 'succeeded' ? 'refunded' : ($response['status'] ?? 'pending'),
                'refund_id' => $response['id'],
                'provider_transaction_id' => $provider_transaction_id,
                'refunded_amount' => $amount,
                'refunded_at' => date('Y-m-d H:i:s'),
                'raw_response' => json_encode($response),
            ];
        } catch (Throwable $e) {
            $this->log_error('refund failed: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Verify a Stripe webhook's `Stripe-Signature` header.
     *
     * Format: `t=<unix_timestamp>,v1=<hex_hmac_sha256>` (may contain multiple v1= values during
     * secret rotation - any matching one is accepted). The signed payload is
     * "{timestamp}.{raw_body}", hashed with the webhook signing secret.
     * https://docs.stripe.com/webhooks#verify-manually
     */
    public function verify_webhook_signature(string $raw_body, array $headers): bool
    {
        $secret = $this->get_setting('webhook_secret');

        if (empty($secret)) {
            $this->log_error('Stripe webhook_secret not configured - refusing to accept webhook.');

            return false;
        }

        $sig_header = $headers['Stripe-Signature'] ?? ($headers['stripe-signature'] ?? null);

        if (!$sig_header) {
            $this->log_error('No Stripe-Signature header in webhook request.');

            return false;
        }

        $parts = [];
        foreach (explode(',', $sig_header) as $pair) {
            [$k, $v] = array_pad(explode('=', $pair, 2), 2, null);
            $parts[$k][] = $v;
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];

        if (!$timestamp || empty($signatures)) {
            $this->log_error('Malformed Stripe-Signature header.');

            return false;
        }

        // Reject payloads older than 5 minutes to mitigate replay attacks (Stripe's own default
        // tolerance).
        if (abs(time() - (int) $timestamp) > 300) {
            $this->log_error('Stripe webhook timestamp outside tolerance - possible replay.');

            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $raw_body, $secret);

        foreach ($signatures as $sig) {
            if ($sig !== null && hash_equals($expected, $sig)) {
                return true;
            }
        }

        return false;
    }

    public function parse_webhook_event(string $raw_body, array $headers): array
    {
        $event = json_decode($raw_body, true) ?? [];
        $obj = $event['data']['object'] ?? [];

        return [
            'type' => $event['type'] ?? 'payment_intent.succeeded',
            'transaction_id' => $obj['latest_charge'] ?? ($obj['id'] ?? null),
            'status' => ($obj['status'] ?? '') === 'succeeded' ? 'succeeded' : 'failed',
            'amount' => isset($obj['amount']) ? (float) ($obj['amount'] / 100) : 0.0,
            'currency' => strtoupper($obj['currency'] ?? 'TRY'),
            'metadata' => $obj['metadata'] ?? [],
        ];
    }
}
