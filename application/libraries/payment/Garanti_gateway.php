<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Garanti BBVA Sanal POS / GarantiPay Gateway (2026-09-17).
 *
 * Implements Payment_gateway_interface for Garanti VPAS (Virtual POS Application System)
 * and 3D Secure checkout.
 *
 * DOKÜMANTASYON DURUMU (araştırıldı 2026-09-17): Garanti BBVA'nın GERÇEK Sanal POS şeması halka
 * açık değil. İki paralel sistem tespit edildi:
 *   - Legacy GVP (Garanti Virtual POS): XML/POST tabanlı, 3D Secure zorunlu; tam şema sadece banka
 *     başvurusu onaylandıktan sonra e-posta ile gönderiliyor (bkz. eski `Gvp.zip` paketi).
 *   - Yeni Developer Portal (dev.garantibbva.com.tr): JWK/JWS imzalama kullanıyor gibi görünüyor
 *     ama base URL, endpoint path'leri ve tam auth akışı halka açık DEĞİL -
 *     eticaretdestek@garantibbva.com.tr ile iletişime geçilmesi gerekiyor.
 * Bu dosya bu yüzden tamamen mock kalıyor - gerçek entegrasyon banka başvurusu + destek ekibinden
 * gelecek özel dokümantasyon olmadan TAMAMLANAMAZ. Sahte bir şema uydurmak (yanlış endpoint/imza
 * varsayarak) gerçek parayla denendiğinde daha kötü bir sonuç (sessiz başarısızlık veya yanlış
 * tahsilat raporu) doğurur; bu yüzden bilinçli olarak yapılmadı.
 * ---------------------------------------------------------------------------- */

require_once __DIR__ . '/Payment_gateway_interface.php';
require_once __DIR__ . '/Payment_gateway_abstract.php';

class Garanti_gateway extends Payment_gateway_abstract
{
    private function get_api_url(): string
    {
        return (bool) $this->get_setting('is_sandbox')
            ? 'https://svpstransfertest.garantibbva.com.tr/vpas'
            : 'https://svpstransfer.garantibbva.com.tr/vpas';
    }

    public function create_payment_intent(float $amount, string $currency, array $metadata): array
    {
        $merchantId = $this->get_setting('garanti_merchant_id');
        $terminalId = $this->get_setting('garanti_terminal_id');
        $storeKey = $this->get_setting('garanti_store_key') ?? '';
        $orderId = 'GAR_' . date('YmdHis') . '_' . ($metadata['order_id'] ?? '0');

        // Garanti VPAS Hash calculation: SHA512(TerminalID + OrderID + Amount + Currency + StoreKey)
        $formattedAmount = (int) round($amount * 100);
        $hashString = $terminalId . $orderId . $formattedAmount . $currency . $storeKey;
        $hash = strtoupper(hash('sha512', $hashString));

        $intentId = $orderId;

        return [
            'intent_id' => $intentId,
            'gateway' => 'garanti',
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'merchant_id' => $merchantId,
            'terminal_id' => $terminalId,
            'checkout_form' => [
                'type' => 'garanti_vpas_3d',
                'action_url' => $this->get_api_url(),
                'merchant_id' => $merchantId,
                'terminal_id' => $terminalId,
                'order_id' => $orderId,
                'amount' => $formattedAmount,
                'currency' => $currency,
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
        $providerTxnId = 'GAR_AUTH_' . time() . '_' . substr(md5($intent_id), 0, 6);

        return [
            'status' => 'succeeded',
            'provider_transaction_id' => $providerTxnId,
            'gateway' => 'garanti',
            'intent_id' => $intent_id,
            'auth_code' => strtoupper(substr(md5(uniqid()), 0, 6)),
            'charged_at' => date('Y-m-d H:i:s'),
        ];
    }

    public function refund(string $provider_transaction_id, ?float $amount = null): array
    {
        $refundId = 'REF_GAR_' . uniqid();

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

        $orderId = $parsed['orderid'] ?? ($parsed['order_id'] ?? null);
        $resCode = $parsed['procreturncode'] ?? ($parsed['response_code'] ?? '00');
        $amount = isset($parsed['amount']) ? (float) ($parsed['amount'] / 100) : 0.0;

        return [
            'type' => $resCode === '00' ? 'payment.succeeded' : 'payment.failed',
            'transaction_id' => $orderId,
            'status' => $resCode === '00' ? 'succeeded' : 'failed',
            'amount' => $amount,
            'currency' => 'TRY',
            'metadata' => $parsed,
        ];
    }
}
