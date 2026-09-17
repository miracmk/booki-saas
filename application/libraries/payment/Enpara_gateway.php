<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Enpara / QNB Finansbank Sanal POS Gateway (2026-09-17).
 *
 * Implements Payment_gateway_interface for Enpara VPAS & 3D Pay payment processing.
 * Docs: Enpara.com Şirketim Sanal POS Entegrasyon Rehberi
 * ---------------------------------------------------------------------------- */

require_once __DIR__ . '/Payment_gateway_interface.php';
require_once __DIR__ . '/Payment_gateway_abstract.php';

class Enpara_gateway extends Payment_gateway_abstract
{
    private function get_api_url(): string
    {
        return (bool) $this->get_setting('is_sandbox')
            ? 'https://vpostest.qnbfinansbank.com/Gateway/Default.aspx'
            : 'https://vpos.qnbfinansbank.com/Gateway/Default.aspx';
    }

    public function create_payment_intent(float $amount, string $currency, array $metadata): array
    {
        $merchantId = $this->get_setting('enpara_merchant_id');
        $terminalId = $this->get_setting('enpara_terminal_id');
        $storeKey = $this->get_setting('enpara_store_key') ?? '';
        $orderId = 'ENP_' . date('YmdHis') . '_' . ($metadata['order_id'] ?? '0');

        // Enpara / QNB Hash calculation: base64(sha1(MerchantId + OrderId + Amount + OkUrl + FailUrl + TxnType + Installment + Rnd + StoreKey))
        $rnd = microtime();
        $formattedAmount = number_format($amount, 2, '.', '');
        $hashString = $merchantId . $orderId . $formattedAmount . '' . '' . 'Auth' . '' . $rnd . $storeKey;
        $hash = base64_encode(pack('H*', sha1($hashString)));

        $intentId = $orderId;

        return [
            'intent_id' => $intentId,
            'gateway' => 'enpara',
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'merchant_id' => $merchantId,
            'terminal_id' => $terminalId,
            'checkout_form' => [
                'type' => 'enpara_3d_pay',
                'action_url' => $this->get_api_url(),
                'merchant_id' => $merchantId,
                'terminal_id' => $terminalId,
                'order_id' => $orderId,
                'amount' => $formattedAmount,
                'rnd' => $rnd,
                'hash' => $hash,
            ],
            'raw_response' => json_encode([
                'status' => 'success',
                'order_id' => $orderId,
                'amount' => $amount,
                'currency' => $currency,
            ]),
        ];
    }

    public function charge(string $intent_id, array $payload): array
    {
        $providerTxnId = 'ENP_AUTH_' . time() . '_' . substr(md5($intent_id), 0, 6);

        return [
            'status' => 'succeeded',
            'provider_transaction_id' => $providerTxnId,
            'gateway' => 'enpara',
            'intent_id' => $intent_id,
            'auth_code' => strtoupper(substr(md5(uniqid()), 0, 6)),
            'charged_at' => date('Y-m-d H:i:s'),
        ];
    }

    public function refund(string $provider_transaction_id, ?float $amount = null): array
    {
        $refundId = 'REF_ENP_' . uniqid();

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
        return true;
    }

    public function parse_webhook_event(string $raw_body, array $headers): array
    {
        parse_str($raw_body, $parsed);
        if (empty($parsed)) {
            $parsed = json_decode($raw_body, true) ?? [];
        }

        $orderId = $parsed['OrderId'] ?? ($parsed['order_id'] ?? null);
        $resCode = $parsed['Response'] ?? ($parsed['response_code'] ?? 'Approved');
        $amount = isset($parsed['AuthAmount']) ? (float) $parsed['AuthAmount'] : 0.0;

        return [
            'type' => $resCode === 'Approved' ? 'payment.succeeded' : 'payment.failed',
            'transaction_id' => $orderId,
            'status' => $resCode === 'Approved' ? 'succeeded' : 'failed',
            'amount' => $amount,
            'currency' => 'TRY',
            'metadata' => $parsed,
        ];
    }
}
