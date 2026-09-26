<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - ÖdeAl (Odeal) Payment Gateway (2026-09-17, gerçek domain+auth 2026-09-17).
 *
 * Implements Payment_gateway_interface for ÖdeAl Sanal POS & Terminal API.
 * Docs: https://docs.odeal.com/sanalpos/tr/
 *
 * 2026-09-17 düzeltmesi: önceki kod TAMAMEN uydurma bir domain kullanıyordu
 * ("paym.com.tr" - ÖdeAl ile hiç ilgisi yok, DNS çözümlenmez). Gerçek domain'ler ve OAuth2
 * client_credentials token akışı (araştırmayla doğrulandı) aşağıda uygulandı.
 *
 * TODO: `/init-3d`, `/init-non-3d`, `/init-link` endpoint'lerinin TAM request/response şeması
 * dokümantasyonun kimlik-doğrulama gerektiren alt sayfalarında - genel yapı (3D Secure zorunlu,
 * "Pay by Link" seçeneği var) doğrulandı ama alan adları henüz TEYIT EDİLMEDİ. Gerçek bir ÖdeAl
 * sözleşmesi/sandbox hesabı alındığında bu iskelet üzerinden tamamlanmalı - şu an create_payment_
 * intent()/refund() hâlâ placeholder döner (gerçek isteği FIRLATMIYOR), sadece get_access_token()
 * gerçek.
 * ---------------------------------------------------------------------------- */

require_once __DIR__ . '/Payment_gateway_interface.php';
require_once __DIR__ . '/Payment_gateway_abstract.php';

class Odeal_gateway extends Payment_gateway_abstract
{
    private function get_auth_url(): string
    {
        return (bool) $this->get_setting('is_sandbox')
            ? 'https://auth-sandbox.odeal.com/api/v1'
            : 'https://auth.odeal.com/api/v1';
    }

    private function get_api_url(): string
    {
        return (bool) $this->get_setting('is_sandbox')
            ? 'https://api-stg.odeal.com/vpos'
            : 'https://api.odeal.com/vpos';
    }

    /**
     * OAuth2 client_credentials token exchange. ÖdeAl invalidates the previous token whenever a
     * new one is issued (no refresh-token flow), so callers should fetch this once per operation
     * rather than caching across requests.
     *
     * https://docs.odeal.com/sanalpos/tr/ ("Kimlik Doğrulama" section)
     */
    private function get_access_token(): string
    {
        $client_id = $this->get_setting('odeal_api_key');
        $client_secret = $this->get_setting('odeal_secret_key');

        if (empty($client_id) || empty($client_secret)) {
            throw new RuntimeException('ÖdeAl API key/secret is not configured.');
        }

        $body = http_build_query([
            'clientId' => $client_id,
            'clientSecret' => $client_secret,
            'grantType' => 'client_credentials',
            'scope' => 'vpos',
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->get_auth_url() . '/token',
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $this->log_error('ÖdeAl token request failed: ' . $curl_error);

            throw new RuntimeException('ÖdeAl token request failed: ' . $curl_error);
        }

        $data = json_decode($response, true);

        if ($http_code >= 400 || !is_array($data) || empty($data['accessToken'] ?? $data['access_token'] ?? null)) {
            $this->log_error("ÖdeAl token request returned HTTP {$http_code}: {$response}");

            throw new RuntimeException('ÖdeAl token request did not return an access token.');
        }

        return $data['accessToken'] ?? $data['access_token'];
    }

    public function create_payment_intent(float $amount, string $currency, array $metadata): array
    {
        // TODO: real /init-3d or /init-link call once the exact request schema is confirmed
        // (requires an approved ÖdeAl merchant account - see class docblock). get_access_token()
        // above IS real and will throw if odeal_api_key/odeal_secret_key are missing/invalid.
        $token = $this->get_access_token();

        $intentId = 'ODL_' . uniqid('', true) . '_' . ($metadata['order_id'] ?? '0');
        $checkoutUrl = $this->get_api_url() . '/init-link/' . $intentId;

        return [
            'intent_id' => $intentId,
            'gateway' => 'odeal',
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'checkout_url' => $checkoutUrl,
            'checkout_form' => [
                'type' => 'odeal_hosted',
                'intent_id' => $intentId,
                'action_url' => $checkoutUrl,
            ],
            'raw_response' => json_encode([
                'status' => 'success',
                'intent_id' => $intentId,
                'amount' => $amount,
                'currency' => $currency,
                'access_token_obtained' => !empty($token),
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
        // TODO: real refund/cancel endpoint call once its exact path is confirmed (see docblock).
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
            $this->log_error('ÖdeAl webhook secret is not configured.');
            return false;
        }

        $signature = $headers['X-Odeal-Signature'] 
            ?? ($headers['x-odeal-signature'] 
            ?? ($headers['HTTP_X_ODEAL_SIGNATURE'] ?? null));
        if (empty($signature)) {
            $this->log_error('Missing X-Odeal-Signature header.');
            return false;
        }

        $computed = hash_hmac('sha256', $raw_body, $secret);

        return hash_equals($computed, (string) $signature);
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
