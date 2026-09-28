<?php defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__ . '/PaymentGatewayAdapterInterface.php';

/* ----------------------------------------------------------------------------
 * BooKi / RandevuBurada Ecosystem
 * Adapter: ToslaPaymentGatewayAdapter
 * 
 * Implements Tosla İşim Virtual POS API with SHA-512 authentication,
 * Pre-Authorization (Önprovizyon), Void (İptal/Serbest Bırakma),
 * and Post-Auth Capture (Tahsilat & Pazaryeri Split).
 * ---------------------------------------------------------------------------- */

class ToslaPaymentGatewayAdapter implements PaymentGatewayAdapterInterface
{
    private string $clientId;
    private string $apiUser;
    private string $apiPass;
    private bool $isSandbox;
    private string $baseUrl;

    public function __construct(array $config = [])
    {
        // Default to provided live/test merchant credentials
        $this->clientId  = (string) ($config['client_id'] ?? '1000006967');
        $this->apiUser   = (string) ($config['api_user'] ?? 'apiUser3041794');
        $this->apiPass   = (string) ($config['api_pass'] ?? 'QJMGN0AX9E');
        $this->isSandbox = (bool)   ($config['is_sandbox'] ?? false);

        $this->baseUrl = $this->isSandbox
            ? 'https://prepentegrasyon.tosla.com/api/Payment/'
            : 'https://entegrasyon.tosla.com/api/Payment/';
    }

    /**
     * Issue a Pre-Authorization hold (Önprovizyon) on the customer's card.
     */
    public function holdPreAuth(float $amount, string $currency, array $cardPayload, array $metadata): array
    {
        $orderId = 'PRE_' . ($metadata['appointment_id'] ?? uniqid()) . '_' . time();
        $rnd = bin2hex(random_bytes(8));
        $timeSpan = date('YmdHis');
        $hash = $this->generateHash($rnd, $timeSpan);

        $payload = [
            'clientId'       => $this->clientId,
            'apiUser'        => $this->apiUser,
            'rnd'            => $rnd,
            'timeSpan'       => $timeSpan,
            'hash'           => $hash,
            'orderId'        => $orderId,
            'amount'         => number_format($amount, 2, '.', ''),
            'currency'       => $currency === 'TRY' ? '949' : $currency,
            'cardHolderName' => $cardPayload['card_holder_name'] ?? '',
            'cardNumber'     => preg_replace('/\D/', '', $cardPayload['card_number'] ?? ''),
            'expireMonth'    => str_pad((string)($cardPayload['expire_month'] ?? ''), 2, '0', STR_PAD_LEFT),
            'expireYear'     => (string)($cardPayload['expire_year'] ?? ''),
            'cvv'            => (string)($cardPayload['cvv'] ?? ''),
            'callBackUrl'    => $metadata['callback_url'] ?? site_url('payment_webhooks/tosla_preauth_callback'),
            'installment'    => '1',
            'extraData'      => json_encode($metadata)
        ];

        $endpoint = 'ThreeDPreAuth'; // Or direct PreAuth if non-3D
        $response = $this->sendHttpRequest($endpoint, $payload);

        // Verification of response
        $isSuccess = isset($response['returnCode']) && $response['returnCode'] === '00';
        $transactionId = $response['transactionId'] ?? $response['authCode'] ?? $orderId;

        if (!$isSuccess && !isset($response['threeDSessionId'])) {
            $errorMsg = $response['returnMessage'] ?? 'Tosla PreAuth failed';
            throw new RuntimeException("Tosla PreAuth Error: " . $errorMsg);
        }

        return [
            'status'         => $isSuccess ? 'held' : '3d_redirect_required',
            'transaction_id' => $transactionId,
            'auth_code'      => $response['authCode'] ?? null,
            'three_d_html'   => $response['threeDHtml'] ?? null,
            'three_d_url'    => $response['threeDUrl'] ?? null,
            'amount'         => $amount,
            'currency'       => $currency,
            'held_at'        => date('Y-m-d H:i:s'),
            'raw_response'   => $response
        ];
    }

    /**
     * Void / Release an active pre-authorization hold (Scenario A: Customer Arrived).
     */
    public function voidPreAuth(string $transactionId, array $options = []): array
    {
        $rnd = bin2hex(random_bytes(8));
        $timeSpan = date('YmdHis');
        $hash = $this->generateHash($rnd, $timeSpan);

        $payload = [
            'clientId'      => $this->clientId,
            'apiUser'       => $this->apiUser,
            'rnd'           => $rnd,
            'timeSpan'      => $timeSpan,
            'hash'          => $hash,
            'transactionId' => $transactionId,
            'voidReason'    => $options['reason'] ?? 'Customer checked in at venue - TBK 178 hold released'
        ];

        $response = $this->sendHttpRequest('Void', $payload);

        $isSuccess = (isset($response['returnCode']) && $response['returnCode'] === '00')
            || (isset($response['status']) && strtolower($response['status']) === 'success');

        if (!$isSuccess) {
            $errorMsg = $response['returnMessage'] ?? 'Tosla Void operation failed';
            throw new RuntimeException("Tosla VoidPreAuth Error: " . $errorMsg);
        }

        return [
            'status'         => 'voided',
            'transaction_id' => $transactionId,
            'voided_at'      => date('Y-m-d H:i:s'),
            'raw_response'   => $response
        ];
    }

    /**
     * Capture pre-authorized deposit with split marketplace payout (Scenario B: No-Show).
     */
    public function capturePreAuth(string $transactionId, float $amount, array $splitData = []): array
    {
        $rnd = bin2hex(random_bytes(8));
        $timeSpan = date('YmdHis');
        $hash = $this->generateHash($rnd, $timeSpan);

        $payload = [
            'clientId'      => $this->clientId,
            'apiUser'       => $this->apiUser,
            'rnd'           => $rnd,
            'timeSpan'      => $timeSpan,
            'hash'          => $hash,
            'transactionId' => $transactionId,
            'amount'        => number_format($amount, 2, '.', '')
        ];

        // If gateway supports sub-merchant marketplace split payloads
        if (!empty($splitData['sub_merchant_id'])) {
            $payload['subMerchantId'] = $splitData['sub_merchant_id'];
            $payload['subMerchantAmount'] = number_format($splitData['merchant_amount'] ?? 0, 2, '.', '');
            $payload['commissionAmount'] = number_format($splitData['platform_amount'] ?? 0, 2, '.', '');
        }

        $response = $this->sendHttpRequest('PostAuth', $payload);

        $isSuccess = (isset($response['returnCode']) && $response['returnCode'] === '00')
            || (isset($response['status']) && strtolower($response['status']) === 'success');

        if (!$isSuccess) {
            $errorMsg = $response['returnMessage'] ?? 'Tosla Capture operation failed';
            throw new RuntimeException("Tosla CapturePreAuth Error: " . $errorMsg);
        }

        return [
            'status'          => 'captured',
            'transaction_id'  => $transactionId,
            'captured_amount' => $amount,
            'split_result'    => $splitData,
            'captured_at'     => date('Y-m-d H:i:s'),
            'raw_response'    => $response
        ];
    }

    /**
     * Compute SHA-512 Base64 signature as required by Tosla İşim:
     * Base64(SHA512(ApiPass + ClientId + ApiUser + RandomString + TimeSpan))
     */
    private function generateHash(string $rnd, string $timeSpan): string
    {
        $hashString = $this->apiPass . $this->clientId . $this->apiUser . $rnd . $timeSpan;
        return base64_encode(hash('sha512', $hashString, true));
    }

    /**
     * Perform HTTP POST request to Tosla İşim API.
     */
    private function sendHttpRequest(string $endpoint, array $data): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        $jsonData = json_encode($data);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($jsonData)
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException("Tosla API Connection Error: " . $curlError);
        }

        $decoded = json_decode((string)$rawResponse, true);
        if ($decoded === null && !empty($rawResponse)) {
            // Some endpoints might return URL-encoded or XML; decode if JSON fails
            parse_str($rawResponse, $parsedStr);
            if (!empty($parsedStr)) {
                return $parsedStr;
            }
            throw new RuntimeException("Tosla API Invalid Response: " . substr($rawResponse, 0, 255));
        }

        return $decoded ?? ['http_code' => $httpCode, 'raw' => $rawResponse];
    }
}
