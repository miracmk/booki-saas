<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - QuickBooks Online accounting connector (2026-09-17).
 *
 * OAuth2 (refresh_token grant) + real Invoice API calls.
 * Docs: https://developer.intuit.com/app/developer/qbo/docs/api/accounting/all-entities/invoice
 *       https://developer.intuit.com/app/developer/qbo/docs/develop/authentication-and-authorization
 *
 * Credentials/tokens are platform-level (master_setting(), see Console::erp_config()) - QuickBooks
 * has no concept of per-tenant sub-accounts, one company (realm) per connection.
 * ---------------------------------------------------------------------------- */

require_once __DIR__ . '/Accounting_connector_interface.php';

class Quickbooks_connector implements Accounting_connector_interface
{
    private const TOKEN_URL = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';

    public function connect(array $credentials): void
    {
        // No-op: this connector reads client_id/client_secret/refresh_token/realm_id directly
        // from master_setting() (Console::erp_config) rather than a per-call credentials array -
        // there is exactly one QuickBooks company connected at the platform level.
    }

    public function is_connected(): bool
    {
        $config = $this->config();

        return !empty($config['client_id']) && !empty($config['client_secret'])
            && !empty($config['refresh_token']) && !empty($config['realm_id']);
    }

    public function create_invoice(array $appointment, array $customer): string
    {
        if (!$this->is_connected()) {
            throw new RuntimeException('QuickBooks connector is not configured (client_id/client_secret/refresh_token/realm_id).');
        }

        $config = $this->config();
        $token = $this->access_token($config);

        $customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: 'Guest';

        // A QuickBooks Invoice requires a CustomerRef that already exists in the company - we
        // upsert-by-name via the DisplayName query filter first (QuickBooks has no "find or
        // create" endpoint, so this is search-then-create).
        $customerRef = $this->find_or_create_customer($config, $token, $customerName, $customer['email'] ?? null);

        $payload = [
            'CustomerRef' => ['value' => $customerRef],
            'Line' => [
                [
                    'Amount' => (float) ($appointment['total'] ?? $appointment['price'] ?? 0),
                    'DetailType' => 'SalesItemLineDetail',
                    'Description' => $appointment['description'] ?? 'Randevu / Appointment',
                    'SalesItemLineDetail' => [
                        // "Services" is QuickBooks' default sandbox item - a real company must map
                        // this to an actual Item.Id from their chart of accounts (out of scope here).
                        'ItemRef' => ['value' => (string) ($config['default_item_id'] ?: '1'), 'name' => 'Services'],
                    ],
                ],
            ],
        ];

        $response = $this->api_request($config, $token, 'POST', '/invoice?minorversion=75', $payload);

        $invoiceId = $response['Invoice']['Id'] ?? null;

        if (!$invoiceId) {
            throw new RuntimeException('QuickBooks invoice creation did not return an Id: ' . json_encode($response));
        }

        return (string) $invoiceId;
    }

    public function get_invoice_status(string $external_id): string
    {
        if (!$this->is_connected()) {
            throw new RuntimeException('QuickBooks connector is not configured.');
        }

        $config = $this->config();
        $token = $this->access_token($config);

        $response = $this->api_request($config, $token, 'GET', '/invoice/' . urlencode($external_id) . '?minorversion=75', []);

        $balance = (float) ($response['Invoice']['Balance'] ?? -1);

        if ($balance < 0) {
            return 'unknown';
        }

        return $balance <= 0.0 ? 'paid' : 'sent';
    }

    private function find_or_create_customer(array $config, string $token, string $name, ?string $email): string
    {
        $escapedName = str_replace("'", "\\'", $name);
        $query = "select Id from Customer where DisplayName = '{$escapedName}'";

        $searchResponse = $this->api_request(
            $config,
            $token,
            'GET',
            '/query?query=' . urlencode($query) . '&minorversion=75',
            [],
        );

        $existingId = $searchResponse['QueryResponse']['Customer'][0]['Id'] ?? null;

        if ($existingId) {
            return (string) $existingId;
        }

        $createResponse = $this->api_request($config, $token, 'POST', '/customer?minorversion=75', [
            'DisplayName' => $name,
            'PrimaryEmailAddr' => $email ? ['Address' => $email] : null,
        ]);

        $newId = $createResponse['Customer']['Id'] ?? null;

        if (!$newId) {
            throw new RuntimeException('QuickBooks customer creation did not return an Id.');
        }

        return (string) $newId;
    }

    private function config(): array
    {
        return [
            'client_id' => (string) master_setting('quickbooks_client_id'),
            'client_secret' => (string) master_setting('quickbooks_client_secret'),
            'refresh_token' => (string) master_setting('quickbooks_refresh_token'),
            'realm_id' => (string) master_setting('quickbooks_realm_id'),
            'is_sandbox' => master_setting('quickbooks_is_sandbox') === '1',
            'default_item_id' => (string) master_setting('quickbooks_default_item_id'),
        ];
    }

    private function api_base(array $config): string
    {
        return $config['is_sandbox']
            ? 'https://sandbox-quickbooks.api.intuit.com/v3/company/' . $config['realm_id']
            : 'https://quickbooks.api.intuit.com/v3/company/' . $config['realm_id'];
    }

    /**
     * Refresh-token grant (Basic-auth'd with client_id:client_secret). QuickBooks rotates the
     * refresh_token on every use - the new one is persisted back to master_settings immediately so
     * the next call doesn't use a stale, already-consumed token.
     */
    private function access_token(array $config): string
    {
        $ch = curl_init(self::TOKEN_URL);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode($config['client_id'] . ':' . $config['client_secret']),
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'refresh_token',
                'refresh_token' => $config['refresh_token'],
            ]),
            CURLOPT_TIMEOUT => 30,
        ]);

        $raw = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('QuickBooks token request failed: ' . $error);
        }

        $parsed = json_decode((string) $raw, true);

        if ($http_code >= 400 || empty($parsed['access_token'])) {
            throw new RuntimeException('QuickBooks token response (HTTP ' . $http_code . '): ' . mb_substr((string) $raw, 0, 600));
        }

        if (!empty($parsed['refresh_token'])) {
            master_setting('quickbooks_refresh_token', $parsed['refresh_token']);
        }

        return (string) $parsed['access_token'];
    }

    private function api_request(array $config, string $token, string $method, string $path, array $body): array
    {
        $ch = curl_init($this->api_base($config) . $path);

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ];

        if ($method !== 'GET') {
            $opts[CURLOPT_POSTFIELDS] = json_encode(array_filter($body, fn($v) => $v !== null));
        }

        curl_setopt_array($ch, $opts);

        $raw = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('QuickBooks API request failed: ' . $error);
        }

        $parsed = json_decode((string) $raw, true);

        if ($http_code >= 400 || !is_array($parsed)) {
            throw new RuntimeException(
                'QuickBooks API ' . $method . ' ' . $path . ' (HTTP ' . $http_code . '): ' . mb_substr((string) $raw, 0, 700),
            );
        }

        return $parsed;
    }
}
