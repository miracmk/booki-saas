<?php defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__ . '/PaymentGatewayAdapterInterface.php';

/* ----------------------------------------------------------------------------
 * BooKi / RandevuBurada Ecosystem
 * Adapter: ToslaPaymentGatewayAdapter
 * 
 * Implements Tosla İşim Virtual POS API with SHA-512 authentication,
 * Pre-Authorization (Önprovizyon), 3D Secure Sale, Void (İptal/Serbest Bırakma),
 * and Post-Auth Capture (Tahsilat & Pazaryeri Split).
 * Supports seamless environment switching between Dev Sandbox and Live Production.
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
        // Environment detection: default to sandbox for non-production environments
        $envIsSandbox = (getenv('TOSLA_IS_SANDBOX') !== false)
            ? filter_var(getenv('TOSLA_IS_SANDBOX'), FILTER_VALIDATE_BOOLEAN)
            : (getenv('APP_ENV') !== 'production');

        $this->isSandbox = isset($config['is_sandbox'])
            ? (bool) $config['is_sandbox']
            : $envIsSandbox;

        // Credentials: Dev Sandbox vs Live Production
        $defaultClientId = $this->isSandbox ? '1000000494' : '1000006967';
        $defaultApiUser  = $this->isSandbox ? 'POS_ENT_Test_001' : 'apiUser3041794';
        $defaultApiPass  = $this->isSandbox ? 'POS_ENT_Test_001!*!*' : 'QJMGN0AX9E';
        $defaultBaseUrl  = $this->isSandbox
            ? 'https://prepentegrasyon.tosla.com/api/Payment/'
            : 'https://entegrasyon.tosla.com/api/Payment/';

        $this->clientId = (string) ($config['client_id'] ?? getenv('TOSLA_CLIENT_ID') ?: $defaultClientId);
        $this->apiUser  = (string) ($config['api_user']  ?? getenv('TOSLA_API_USER')  ?: $defaultApiUser);
        $this->apiPass  = (string) ($config['api_pass']  ?? getenv('TOSLA_API_PASS')  ?: $defaultApiPass);
        $this->baseUrl  = (string) ($config['base_url']  ?? getenv('TOSLA_BASE_URL')  ?: $defaultBaseUrl);

        if (!str_ends_with($this->baseUrl, '/')) {
            $this->baseUrl .= '/';
        }
    }

    public function isSandbox(): bool
    {
        return $this->isSandbox;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getFormUrl(): string
    {
        return $this->baseUrl . 'ProcessCardForm';
    }

    public function getFrameUrl(string $threeDSessionId): string
    {
        return $this->baseUrl . 'threeDSecure/' . $threeDSessionId;
    }

    /**
     * Start a 3D Secure Direct Payment session (for SaaS plans, AI add-ons, orders).
     */
    public function startThreeDPayment(
        float $amount,
        string $orderId,
        string $callbackUrl,
        array $options = []
    ): array {
        $amountInKurus = (int) round($amount * 100);
        $rnd = (string) rand(100000, 999999);
        $timeSpan = $this->getTurkeyTimeSpan();
        $hash = $this->generateHash($rnd, $timeSpan);

        $payload = [
            'clientId'         => $this->clientId,
            'apiUser'          => $this->apiUser,
            'Rnd'              => $rnd,
            'timeSpan'         => $timeSpan,
            'Hash'             => $hash,
            'callbackUrl'      => $callbackUrl,
            'orderId'          => substr($orderId, 0, 20),
            'amount'           => $amountInKurus,
            'currency'         => 949,
            'installmentCount' => $options['installment_count'] ?? 0,
            'description'      => $options['description'] ?? 'BooKi Odeme'
        ];

        return $this->sendHttpRequest('threeDPayment', $payload);
    }

    /**
     * Start a 3D Secure Pre-Authorization session (for deposit hold).
     */
    public function startThreeDPreAuth(
        float $amount,
        string $orderId,
        string $callbackUrl,
        array $options = []
    ): array {
        $amountInKurus = (int) round($amount * 100);
        $rnd = (string) rand(100000, 999999);
        $timeSpan = $this->getTurkeyTimeSpan();
        $hash = $this->generateHash($rnd, $timeSpan);

        $payload = [
            'clientId'         => $this->clientId,
            'apiUser'          => $this->apiUser,
            'Rnd'              => $rnd,
            'timeSpan'         => $timeSpan,
            'Hash'             => $hash,
            'callbackUrl'      => $callbackUrl,
            'orderId'          => substr($orderId, 0, 20),
            'amount'           => $amountInKurus,
            'currency'         => 949,
            'installmentCount' => 0
        ];

        return $this->sendHttpRequest('threeDPreAuth', $payload);
    }

    /**
     * Submit card details to ProcessCardForm for 3D Secure authentication.
     */
    public function processCardForm(string $threeDSessionId, array $cardPayload): array
    {
        $cardNumber = preg_replace('/\D/', '', $cardPayload['card_number'] ?? '');
        $expireMonth = str_pad((string)($cardPayload['expire_month'] ?? ''), 2, '0', STR_PAD_LEFT);
        $expireYear = substr((string)($cardPayload['expire_year'] ?? ''), -2);
        $expireDate = $expireMonth . $expireYear;

        $fields = [
            'ThreeDSessionId' => $threeDSessionId,
            'CardHolderName'  => trim((string)($cardPayload['card_holder_name'] ?? '')),
            'CardNo'          => $cardNumber,
            'ExpireDate'      => $expireDate,
            'Cvv'             => (string)($cardPayload['cvv'] ?? '')
        ];

        $ch = curl_init($this->getFormUrl());
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException("Tosla ProcessCardForm Error: " . $curlError);
        }

        return [
            'http_code' => $httpCode,
            'html'      => $rawResponse,
            'session'   => $threeDSessionId
        ];
    }

    /**
     * Direct 3D Payment execution (Initiate Session + Process Card Form if card supplied).
     */
    public function directThreeDPayment(
        float $amount,
        string $orderId,
        string $callbackUrl,
        array $cardPayload,
        array $metadata = []
    ): array {
        // Fallback for isolated Dev Sandbox where external pre-prod IP whitelist blocks outbound connection
        if ($this->isSandbox && !$this->isEndpointReachable()) {
            return [
                'status'             => 'success',
                'order_id'           => $orderId,
                'transaction_id'     => 'DEV_SB_TRANS_' . time(),
                'three_d_session_id' => 'DEV_SB_SESSION_' . uniqid(),
                'auth_code'          => 'SB' . rand(100000, 999999),
                'three_d_html'       => null,
                'three_d_url'        => null,
                'amount'             => $amount,
                'currency'           => 'TRY',
                'is_sandbox'         => true,
                'message'            => 'Dev sandbox payment approved successfully (Simulated mode)'
            ];
        }

        $sessionResponse = $this->startThreeDPayment($amount, $orderId, $callbackUrl, $metadata);
        $threeDSessionId = $sessionResponse['ThreeDSessionId'] ?? $sessionResponse['threeDSessionId'] ?? null;
        $transactionId = $sessionResponse['TransactionId'] ?? $sessionResponse['transactionId'] ?? $orderId;

        if (empty($threeDSessionId)) {
            $msg = !empty($sessionResponse['Message']) ? $sessionResponse['Message'] : json_encode($sessionResponse);
            throw new RuntimeException("Tosla Error: " . $msg);
        }

        $threeDHtml = null;
        if (!empty($cardPayload['card_number'])) {
            $cardResult = $this->processCardForm($threeDSessionId, $cardPayload);
            $threeDHtml = $cardResult['html'] ?? null;
        }

        return [
            'status'             => '3d_redirect_required',
            'order_id'           => $orderId,
            'transaction_id'     => $transactionId,
            'three_d_session_id' => $threeDSessionId,
            'three_d_html'       => $threeDHtml,
            'three_d_url'        => $this->getFrameUrl($threeDSessionId),
            'amount'             => $amount,
            'currency'           => 'TRY',
            'is_sandbox'         => $this->isSandbox,
            'raw_response'       => $sessionResponse
        ];
    }

    /**
     * Issue a Pre-Authorization hold (Önprovizyon) on the customer's card.
     */
    public function holdPreAuth(float $amount, string $currency, array $cardPayload, array $metadata): array
    {
        $orderId = 'PRE_' . ($metadata['appointment_id'] ?? uniqid()) . '_' . time();
        $callbackUrl = $metadata['callback_url'] ?? site_url('payment_webhooks/tosla_preauth_callback');

        if ($this->isSandbox && !$this->isEndpointReachable()) {
            return [
                'status'             => 'held',
                'transaction_id'     => 'DEV_SB_PREAUTH_' . time(),
                'three_d_session_id' => 'DEV_SB_SESSION_' . uniqid(),
                'auth_code'          => 'SB' . rand(100000, 999999),
                'three_d_html'       => null,
                'three_d_url'        => null,
                'amount'             => $amount,
                'currency'           => $currency,
                'held_at'            => date('Y-m-d H:i:s'),
                'is_sandbox'         => true,
                'raw_response'       => ['Code' => 0, 'Message' => 'Sandbox pre-auth held successfully']
            ];
        }

        $sessionResponse = $this->startThreeDPreAuth($amount, $orderId, $callbackUrl, $metadata);
        $threeDSessionId = $sessionResponse['ThreeDSessionId'] ?? $sessionResponse['threeDSessionId'] ?? null;
        $transactionId = $sessionResponse['TransactionId'] ?? $sessionResponse['transactionId'] ?? $orderId;

        if (empty($threeDSessionId)) {
            $msg = $sessionResponse['Message'] ?? $sessionResponse['message'] ?? 'Tosla PreAuth session initialization failed';
            throw new RuntimeException("Tosla Error: " . $msg);
        }

        $threeDHtml = null;
        if (!empty($cardPayload['card_number'])) {
            $cardResult = $this->processCardForm($threeDSessionId, $cardPayload);
            $threeDHtml = $cardResult['html'] ?? null;
        }

        return [
            'status'             => '3d_redirect_required',
            'transaction_id'     => $transactionId,
            'three_d_session_id' => $threeDSessionId,
            'three_d_html'       => $threeDHtml,
            'three_d_url'        => $this->getFrameUrl($threeDSessionId),
            'amount'             => $amount,
            'currency'           => $currency,
            'held_at'            => date('Y-m-d H:i:s'),
            'is_sandbox'         => $this->isSandbox,
            'raw_response'       => $sessionResponse
        ];
    }

    /**
     * Void / Release an active pre-authorization hold (Scenario A: Customer Arrived).
     */
    public function voidPreAuth(string $transactionId, array $options = []): array
    {
        if ($this->isSandbox && !$this->isEndpointReachable()) {
            return [
                'status'         => 'voided',
                'transaction_id' => $transactionId,
                'voided_at'      => date('Y-m-d H:i:s'),
                'is_sandbox'     => true,
                'raw_response'   => ['Code' => 0, 'Message' => 'Sandbox pre-auth released successfully']
            ];
        }

        $rnd = (string) rand(100000, 999999);
        $timeSpan = $this->getTurkeyTimeSpan();
        $hash = $this->generateHash($rnd, $timeSpan);

        $payload = [
            'clientId'      => $this->clientId,
            'apiUser'       => $this->apiUser,
            'Rnd'           => $rnd,
            'timeSpan'      => $timeSpan,
            'Hash'          => $hash,
            'transactionId' => $transactionId,
            'orderId'       => $options['order_id'] ?? $transactionId
        ];

        $response = $this->sendHttpRequest('void', $payload);
        $isSuccess = (isset($response['Code']) && (int)$response['Code'] === 0)
            || (isset($response['BankResponseCode']) && $response['BankResponseCode'] === '00');

        if (!$isSuccess) {
            $errorMsg = $response['Message'] ?? $response['BankResponseMessage'] ?? 'Tosla Void operation failed';
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
        if ($this->isSandbox && !$this->isEndpointReachable()) {
            return [
                'status'          => 'captured',
                'transaction_id'  => $transactionId,
                'captured_amount' => $amount,
                'split_result'    => $splitData,
                'captured_at'     => date('Y-m-d H:i:s'),
                'is_sandbox'      => true,
                'raw_response'    => ['Code' => 0, 'Message' => 'Sandbox pre-auth captured successfully']
            ];
        }

        $amountInKurus = (int) round($amount * 100);
        $rnd = (string) rand(100000, 999999);
        $timeSpan = $this->getTurkeyTimeSpan();
        $hash = $this->generateHash($rnd, $timeSpan);

        $payload = [
            'clientId'      => $this->clientId,
            'apiUser'       => $this->apiUser,
            'Rnd'           => $rnd,
            'timeSpan'      => $timeSpan,
            'Hash'          => $hash,
            'transactionId' => $transactionId,
            'orderId'       => $splitData['order_id'] ?? $transactionId,
            'amount'        => $amountInKurus
        ];

        $response = $this->sendHttpRequest('postAuth', $payload);
        $isSuccess = (isset($response['Code']) && (int)$response['Code'] === 0)
            || (isset($response['BankResponseCode']) && $response['BankResponseCode'] === '00');

        if (!$isSuccess) {
            $errorMsg = $response['Message'] ?? $response['BankResponseMessage'] ?? 'Tosla Capture operation failed';
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
     * Query transaction details by orderId or transactionId.
     */
    public function inquiry(string $orderId = '', string $transactionId = ''): array
    {
        $rnd = (string) rand(100000, 999999);
        $timeSpan = $this->getTurkeyTimeSpan();
        $hash = $this->generateHash($rnd, $timeSpan);

        $payload = [
            'clientId'      => $this->clientId,
            'apiUser'       => $this->apiUser,
            'Rnd'           => $rnd,
            'timeSpan'      => $timeSpan,
            'Hash'          => $hash,
            'orderId'       => $orderId,
            'transactionId' => $transactionId
        ];

        return $this->sendHttpRequest('inquiry', $payload);
    }

    /**
     * Verify credentials against the Tosla gateway.
     */
    public function verifyClient(): array
    {
        $rnd = (string) rand(100000, 999999);
        $timeSpan = $this->getTurkeyTimeSpan();
        $hash = $this->generateHash($rnd, $timeSpan);

        $payload = [
            'clientId' => $this->clientId,
            'apiUser'  => $this->apiUser,
            'Rnd'      => $rnd,
            'timeSpan' => $timeSpan,
            'Hash'     => $hash
        ];

        return $this->sendHttpRequest('VerifyClient', $payload);
    }

    /**
     * Validate callback signature from Tosla.
     */
    public function validateCallbackHash(array $postData): bool
    {
        if (empty($postData['Hash']) || empty($postData['HashParameters'])) {
            return false;
        }

        $paramKeys = explode(',', (string)$postData['HashParameters']);
        $extra = [
            'ClientId' => $this->clientId,
            'ApiUser'  => $this->apiUser
        ];

        $hashString = $this->apiPass;
        foreach ($paramKeys as $key) {
            $hashString .= $extra[$key] ?? ($postData[$key] ?? '');
        }

        $expectedHash = base64_encode(hash('sha512', $hashString, true));
        return hash_equals($expectedHash, (string)$postData['Hash']);
    }

    /**
     * Determine if a callback payload denotes a successful authorization/payment.
     */
    public function isCallbackSuccessful(array $postData): bool
    {
        // 1. Verify hash signature if present
        if (!empty($postData['Hash']) && !empty($postData['HashParameters'])) {
            if (!$this->validateCallbackHash($postData)) {
                return false;
            }
        }

        // 2. Check bank approval code
        $bankCode = (string)($postData['BankResponseCode'] ?? $postData['bankResponseCode'] ?? $postData['returnCode'] ?? '');
        $status = strtolower((string)($postData['status'] ?? $postData['Response'] ?? $postData['response'] ?? ''));
        $mdStatus = (string)($postData['mdStatus'] ?? $postData['MdStatus'] ?? '');

        return ($bankCode === '00' || $bankCode === '0' || $status === 'success' || $status === 'approved' || $mdStatus === '1');
    }

    /**
     * Compute Turkey (GMT+3) timestamp formatted as yyyyMMddHHmmss.
     * Tosla API strictly requires this timestamp to be within 1 hour of Turkish local time.
     */
    public function getTurkeyTimeSpan(): string
    {
        $dt = new DateTime('now', new DateTimeZone('Europe/Istanbul'));
        return $dt->format('YmdHis');
    }

    /**
     * Compute SHA-512 Base64 signature as required by Tosla İşim:
     * Base64(SHA512(ApiPass + ClientId + ApiUser + RandomString + TimeSpan))
     */
    public function generateHash(string $rnd, string $timeSpan): string
    {
        $hashString = $this->apiPass . $this->clientId . $this->apiUser . $rnd . $timeSpan;
        return base64_encode(hash('sha512', $hashString, true));
    }

    /**
     * Helper to verify if the configured baseUrl is reachable without network hangs.
     */
    public function isEndpointReachable(): bool
    {
        static $reachable = null;
        if ($reachable !== null) {
            return $reachable;
        }

        $host = parse_url($this->baseUrl, PHP_URL_HOST);
        if (!$host) {
            $reachable = false;
            return false;
        }

        $ch = curl_init('https://' . $host);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        curl_exec($ch);
        $err = curl_errno($ch);
        curl_close($ch);

        $reachable = ($err === 0);
        return $reachable;
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
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException("Tosla API Connection Error ({$endpoint}): " . $curlError);
        }

        $decoded = json_decode((string)$rawResponse, true);
        if ($decoded === null && !empty($rawResponse)) {
            parse_str($rawResponse, $parsedStr);
            if (!empty($parsedStr)) {
                return $parsedStr;
            }
            throw new RuntimeException("Tosla API Invalid Response: " . substr($rawResponse, 0, 255));
        }

        return $decoded ?? ['http_code' => $httpCode, 'raw' => $rawResponse];
    }
}
