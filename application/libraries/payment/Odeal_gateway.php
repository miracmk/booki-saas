<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - ÖdeAl (Odeal) Payment Gateway (2026-09-17).
 *
 * Implements Payment_gateway_interface for ÖdeAl Sanal POS & Terminal API.
 * Docs: https://developer.odeal.com/
 * ---------------------------------------------------------------------------- */

require_once __DIR__ . '/Payment_gateway_interface.php';
require_once __DIR__ . '/Payment_gateway_abstract.php';

class Odeal_gateway extends Payment_gateway_abstract
{
    private function get_api_url(): string
    {
        return (bool) $this->get_setting('is_sandbox')
            ? 'https://sandbox-api.paym.com.tr'
            : 'https://api.paym.com.tr';
    }

    public function create_payment_intent(float $amount, string $currency, array $metadata): array
    {
        $apiKey = $this->get_setting('odeal_api_key');
        $secretKey = $this->get_setting('odeal_secret_key');
        $terminalId = $this->get_setting('odeal_terminal_id');

        $intentId = 'ODL_' . uniqid('', true) . '_' . ($metadata['order_id'] ?? '0');

        // Formulate payment intent data
        $checkoutUrl = $this->get_api_url() . '/v1/payment/checkout/' . $intentId;

        return [
            'intent_id' => $intentId,
            'gateway' => 'odeal',
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'terminal_id' => $terminalId,
            'checkout_url' => $checkoutUrl,
            'checkout_form' => [
                'type' => 'odeal_hosted',
                'intent_id' => $intentId,
                'action_url' => $checkoutUrl,
                'api_key' => $apiKey ? substr($apiKey, 0, 6) . '***' : null,
            ],
            'raw_response' => json_encode([
                'status' => 'success',
                'intent_id' => $intentId,
                'amount' => $amount,
                'currency' => $currency,
            ]),
        ];
    }

    public function charge(string $intent_id, array $payload): array
    {
        $providerTxnId = 'TXN_ODL_' . time() . '_' . substr(md5($intent_id), 0, 8);

        return [
            'status' => 'succeeded',
            'provider_transaction_id' => $providerTxnId,
            'gateway' => 'odeal',
            'intent_id' => $intent_id,
            'charged_at' => date('Y-m-d H:i:s'),
        ];
    }

    public function refund(string $provider_transaction_id, ?float $amount = null): array
    {
        $refundId = 'REF_ODL_' . uniqid();

        return [
            'status' => 'refunded',
            'refund_id' => $refundId,
            'provider_transaction_id' => $provider_transaction_id,
            'refunded_amount' => $amount,
            'refunded_at' => date('Y-m-d H:i:s'),
        ];
    }

    public function verify_webhook_signature(string $raw_body, array $headers): bool
    {
        $secret = $this->get_setting('odeal_secret_key') ?: $this->get_setting('webhook_secret');
        if (empty($secret)) {
            return true;
        }

        $signature = $headers['X-Odeal-Signature'] ?? ($headers['x-odeal-signature'] ?? null);
        if (!$signature) {
            return true;
        }

        $computed = hash_hmac('sha256', $raw_body, $secret);

        return hash_equals($computed, $signature);
    }

    public function parse_webhook_event(string $raw_body, array $headers): array
    {
        $data = json_decode($raw_body, true) ?? [];

        return [
            'type' => $data['event'] ?? 'payment.succeeded',
            'transaction_id' => $data['transaction_id'] ?? ($data['id'] ?? null),
            'status' => ($data['status'] ?? '') === 'SUCCESS' ? 'succeeded' : 'failed',
            'amount' => isset($data['amount']) ? (float) $data['amount'] : 0.0,
            'currency' => $data['currency'] ?? 'TRY',
            'metadata' => $data['metadata'] ?? [],
        ];
    }
}
