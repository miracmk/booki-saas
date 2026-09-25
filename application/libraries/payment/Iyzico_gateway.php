<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - iyzico payment gateway (2026-08-27, imza şeması düzeltmesi 2026-09-17).
 *
 * Implementation of Payment_gateway_interface for iyzico.
 * API docs: https://docs.iyzico.com/en/getting-started/preliminaries/authentication/hmacsha256-auth
 *           https://docs.iyzico.com/en/payment-methods/checkoutform/cf-implementation/cf-initialize
 *           https://docs.iyzico.com/en/getting-started/preliminaries/api-reference-beta/refund-and-cancel
 *
 * 2026-09-17 düzeltmesi: önceki imza (`base64(hmac_sha256(json_body, secret))`, tek header
 * `X-IYZ-SIGNATURE`) gerçek iyzico "HMACSHA256 Auth" (IYZWSv2) şemasıyla eşleşmiyordu - her
 * istek 401 ile reddedilirdi. Gerçek şema randomKey + uri_path + body'yi imzalar ve ayrı bir
 * `x-iyzi-rnd` header'ı gerektirir (bkz. build_iyzws_v2_auth_headers()). Endpoint path'leri de
 * gerçek dokümantasyona göre düzeltildi (`/v2/...` uydurmaydı).
 *
 * TODO: Webhook "Response Signature Validation" iyzico'da ayrı, farklı bir akış
 * (https://docs.iyzico.com/en/advanced/response-signature-validation) - henüz doğrulanamadı,
 * verify_webhook_signature() hâlâ eski (muhtemelen yanlış) formülü kullanıyor.
 * ---------------------------------------------------------------------------- */

require_once __DIR__ . '/Payment_gateway_interface.php';
require_once __DIR__ . '/Payment_gateway_abstract.php';

class Iyzico_gateway extends Payment_gateway_abstract
{
    /**
     * Get the API base URL (sandbox or production).
     *
     * @return string
     */
    private function get_api_url(): string
    {
        $is_sandbox = (bool) $this->get_setting('is_sandbox');

        return $is_sandbox
            ? 'https://sandbox-api.iyzipay.com'
            : 'https://api.iyzipay.com';
    }

    /**
     * Build the iyzico "HMACSHA256 Auth" (IYZWSv2) headers for a request.
     *
     * https://docs.iyzico.com/en/getting-started/preliminaries/authentication/hmacsha256-auth
     *
     * randomKey + uri_path + request_body are concatenated and HMAC-SHA256'd (hex) with the
     * secret key; the resulting authorizationString is base64-encoded and sent as the
     * `Authorization: IYZWSv2 <...>` header, with the randomKey ALSO sent separately as
     * `x-iyzi-rnd` (iyzico recomputes the signature server-side using that header's value).
     *
     * @param string $uri_path Request path only (e.g. '/payment/iyzipos/checkoutform/initialize/auth/ecom'),
     *                         NOT the full URL.
     */
    private function build_iyzws_v2_auth_headers(string $uri_path, string $json_body, string $api_key, string $secret_key): array
    {
        $random_key = (string) round(microtime(true) * 1000) . bin2hex(random_bytes(8));
        $encrypted_data = hash_hmac('sha256', $random_key . $uri_path . $json_body, $secret_key);
        $authorization_string = "apiKey:{$api_key}&randomKey:{$random_key}&signature:{$encrypted_data}";

        return [
            'Authorization: IYZWSv2 ' . base64_encode($authorization_string),
            'x-iyzi-rnd: ' . $random_key,
        ];
    }

    /**
     * Make a request to the iyzico API.
     *
     * @param string $endpoint API endpoint path (e.g., '/payment/iyzipos/checkoutform/initialize/auth/ecom').
     * @param array $payload Request payload.
     *
     * @return array Parsed JSON response.
     *
     * @throws RuntimeException On HTTP errors or invalid responses.
     */
    private function api_request(string $endpoint, array $payload): array
    {
        $api_key = $this->get_setting('iyzico_api_key');
        $secret_key = $this->get_setting('iyzico_secret_key');

        if (empty($api_key) || empty($secret_key)) {
            throw new RuntimeException('iyzico API key or secret key is not configured.');
        }

        $url = $this->get_api_url() . $endpoint;
        $json_body = json_encode($payload);

        $headers = array_merge(
            ['Content-Type: application/json'],
            $this->build_iyzws_v2_auth_headers($endpoint, $json_body, $api_key, $secret_key),
        );

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $json_body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);

        curl_close($ch);

        if ($curl_error) {
            $this->log_error("HTTP request failed: {$curl_error}");

            throw new RuntimeException('iyzico API request failed: ' . $curl_error);
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            $this->log_error("Invalid JSON response from iyzico: {$response}");

            throw new RuntimeException('iyzico API returned invalid JSON.');
        }

        if ($http_code >= 400) {
            $error_message = $data['errorMessage'] ?? 'Unknown error';
            $this->log_error("iyzico API error ({$http_code}): {$error_message}");

            throw new RuntimeException("iyzico API error: {$error_message}");
        }

        return $data;
    }

    /**
     * Create a payment intent for the given amount.
     *
     * @param float $amount Amount to charge.
     * @param string $currency Currency code (e.g., 'TRY').
     * @param array $metadata Additional metadata (appointment_id, customer_id, etc.).
     *
     * @return array Response with 'intent_id' field.
     */
    public function create_payment_intent(float $amount, string $currency, array $metadata): array
    {
        try {
            // iyzico CheckoutFormInitialize - creates a payment form session
            $payload = [
                'locale' => 'tr',
                'conversationId' => bin2hex(random_bytes(8)),
                'price' => number_format($amount, 2, '.', ''),
                'priceCurrency' => $currency,
                'basketId' => 'basket_' . ($metadata['appointment_id'] ?? uniqid()),
                'paymentGroup' => 'PRODUCT',
                'callbackUrl' => site_url('payment_webhooks/iyzico'),
                'callbackUrl' => site_url('payment/callback/iyzico'),
                'enabledInstallments' => [2, 3, 6, 9],
                'buyer' => [
                    'id' => (string) ($metadata['customer_id'] ?? 'guest'),
                    'name' => $metadata['customer_name'] ?? 'Guest',
                    'surname' => $metadata['customer_surname'] ?? 'Customer',
                    'gsmNumber' => $metadata['customer_phone'] ?? '',
                    'email' => $metadata['customer_email'] ?? '',
                    'identityNumber' => '',
                    'lastLoginDate' => date('Y-m-d H:i:s'),
                    'registrationDate' => date('Y-m-d H:i:s'),
                    'registrationAddress' => $metadata['customer_address'] ?? '',
                    'city' => $metadata['customer_city'] ?? '',
                    'country' => 'Turkey',
                    'zipCode' => $metadata['customer_zip'] ?? '',
                ],
                'billingAddress' => [
                    'contactName' => ($metadata['customer_name'] ?? 'Guest') . ' ' . ($metadata['customer_surname'] ?? 'Customer'),
                    'city' => $metadata['customer_city'] ?? '',
                    'country' => 'Turkey',
                    'address' => $metadata['customer_address'] ?? '',
                    'zipCode' => $metadata['customer_zip'] ?? '',
                ],
                'shippingAddress' => [
                    'contactName' => ($metadata['customer_name'] ?? 'Guest') . ' ' . ($metadata['customer_surname'] ?? 'Customer'),
                    'city' => $metadata['customer_city'] ?? '',
                    'country' => 'Turkey',
                    'address' => $metadata['customer_address'] ?? '',
                    'zipCode' => $metadata['customer_zip'] ?? '',
                ],
                'basketItems' => [
                    [
                        'id' => 'item_' . ($metadata['appointment_id'] ?? 'unknown'),
                        'name' => 'Appointment Deposit',
                        'category1' => 'Services',
                        'itemType' => 'VIRTUAL',
                        'price' => number_format($amount, 2, '.', ''),
                    ],
                ],
            ];

            $response = $this->api_request('/payment/iyzipos/checkoutform/initialize/auth/ecom', $payload);

            if (!isset($response['checkoutFormContent'])) {
                throw new RuntimeException('No checkout form content in iyzico response.');
            }

            return [
                'intent_id' => $response['conversationId'] ?? 'iyzico_' . uniqid(),
                'checkout_form' => $response['checkoutFormContent'] ?? '',
                'raw_response' => json_encode($response),
            ];
        } catch (Throwable $e) {
            $this->log_error('create_payment_intent failed: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Charge/capture a payment intent.
     *
     * @param string $intent_id Payment intent ID.
     * @param array $payload Additional data for charging.
     *
     * @return array Response with 'status' and 'provider_transaction_id' fields.
     */
    public function charge(string $intent_id, array $payload): array
    {
        // iyzico Checkout Form payments are captured by the bank during checkout and reported back through the
        // webhook/redirect flow - there is no server-side "charge" step. Returning a fabricated 'succeeded'
        // response here (as this method previously did) would silently fake a completed payment, so we fail
        // loudly instead. Nothing in the codebase calls charge(); any future caller must reconcile via the
        // iyzico webhook (see parse_webhook_event()).
        throw new RuntimeException(
            'iyzico charge() is not part of the Checkout Form flow - capture the payment via the iyzico webhook instead.',
        );
    }

    /**
     * Refund a previously charged transaction.
     *
     * @param string $provider_transaction_id Transaction ID.
     * @param float|null $amount Partial refund amount; null = full refund.
     *
     * @return array Response with 'status' and 'refund_id' fields.
     */
    public function refund(string $provider_transaction_id, ?float $amount = null): array
    {
        try {
            $payload = [
                'locale' => 'tr',
                'conversationId' => bin2hex(random_bytes(8)),
                'paymentId' => $provider_transaction_id,
            ];

            if ($amount !== null) {
                $payload['price'] = number_format($amount, 2, '.', '');
            }

            $response = $this->api_request('/payment/refund', $payload);

            return [
                'status' => 'succeeded',
                'refund_id' => $response['id'] ?? $provider_transaction_id . '_refund',
                'raw_response' => json_encode($response),
            ];
        } catch (Throwable $e) {
            $this->log_error('refund failed: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Verify webhook signature.
     *
     * @param string $raw_body Raw webhook body.
     * @param array $headers HTTP headers.
     *
     * @return bool True if signature is valid.
     */
    public function verify_webhook_signature(string $raw_body, array $headers): bool
    {
        try {
            // TODO: Verify iyzico webhook signature header name and format
            $secret_key = $this->get_setting('iyzico_secret_key');

            if (empty($secret_key)) {
                $this->log_error('iyzico secret key not configured for webhook verification.');

                return false;
            }

            $received_signature = $headers['X-IYZ-SIGNATURE'] 
                ?? ($headers['x-iyz-signature'] 
                ?? ($headers['HTTP_X_IYZ_SIGNATURE'] ?? ''));

            if (empty($received_signature)) {
                $this->log_error('No X-IYZ-SIGNATURE header in iyzico webhook.');

                return false;
            }

            $expected_signature = base64_encode(
                hash_hmac('sha256', $raw_body, $secret_key, true)
            );

            return hash_equals($expected_signature, $received_signature);
        } catch (Throwable $e) {
            $this->log_error('verify_webhook_signature failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Parse a webhook event.
     *
     * @param string $raw_body Raw webhook body.
     * @param array $headers HTTP headers.
     *
     * @return array Normalized event.
     */
    public function parse_webhook_event(string $raw_body, array $headers): array
    {
        try {
            $event = json_decode($raw_body, true);

            if (!is_array($event)) {
                throw new RuntimeException('Invalid JSON in webhook body.');
            }

            // iyzico Checkout Form notifications carry the status both as 'status' and, on the applied/paid
            // event, as 'paymentStatus'. Accept either spelling so the webhook resolves correctly regardless of
            // which iyzico notification format is delivered.
            $raw_status = strtoupper((string) ($event['status'] ?? $event['paymentStatus'] ?? ''));

            return [
                'type' => $event['eventType'] ?? 'unknown',
                // conversationId is the intent we stored in create_payment_intent() - the webhook handler looks
                // the transaction up by this key first.
                'intent_id' => $event['conversationId'] ?? null,
                'transaction_id' => $event['paymentId'] ?? null,
                'status' => in_array($raw_status, ['SUCCESS', 'PAID', 'PROCESSED', 'APPROVED'], true) ? 'succeeded' : 'failed',
                'amount' => (float) ($event['price'] ?? 0),
                'currency' => $event['currency'] ?? 'TRY',
                'metadata' => $event['metadata'] ?? [],
            ];
        } catch (Throwable $e) {
            $this->log_error('parse_webhook_event failed: ' . $e->getMessage());

            throw $e;
        }
    }
}
