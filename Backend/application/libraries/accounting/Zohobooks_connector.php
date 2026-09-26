<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Zoho Books accounting connector (2026-09-17).
 *
 * OAuth2 (refresh_token grant) + real Invoice API calls.
 * Docs: https://www.zoho.com/books/api/v3/
 *
 * Reuses the exact OAuth refresh-token pattern already proven working in Crm_sync.php (Zoho
 * CRM), but Zoho Books is a SEPARATE Zoho product/app registration from Zoho CRM, so it gets its
 * own master_setting() keys (zohobooks_*) rather than reusing zoho_client_id/secret - a tenant
 * may want CRM sync and Books invoicing connected to different Zoho accounts.
 * ---------------------------------------------------------------------------- */

require_once __DIR__ . '/Accounting_connector_interface.php';

class Zohobooks_connector implements Accounting_connector_interface
{
    private const ACCOUNT_HOSTS = [
        'us' => 'https://accounts.zoho.com',
        'com' => 'https://accounts.zoho.com',
        'in' => 'https://accounts.zoho.in',
        'au' => 'https://accounts.zoho.com.au',
        'eu' => 'https://accounts.zoho.eu',
        'uk' => 'https://accounts.zoho.eu',
        'jp' => 'https://accounts.zoho.jp',
    ];

    private const API_HOSTS = [
        'us' => 'https://www.zohoapis.com',
        'com' => 'https://www.zohoapis.com',
        'in' => 'https://www.zohoapis.in',
        'au' => 'https://www.zohoapis.com.au',
        'eu' => 'https://www.zohoapis.eu',
        'uk' => 'https://www.zohoapis.eu',
        'jp' => 'https://www.zohoapis.jp',
    ];

    public function connect(array $credentials): void
    {
        // No-op: this connector reads client_id/client_secret/refresh_token/organization_id
        // directly from master_setting() (Console::erp_config) - platform-level, single
        // connected Zoho Books organization.
    }

    public function is_connected(): bool
    {
        $config = $this->config();

        return !empty($config['client_id']) && !empty($config['client_secret'])
            && !empty($config['refresh_token']) && !empty($config['organization_id']);
    }

    public function create_invoice(array $appointment, array $customer): string
    {
        if (!$this->is_connected()) {
            throw new RuntimeException('Zoho Books connector is not configured (client_id/client_secret/refresh_token/organization_id).');
        }

        $config = $this->config();
        $token = $this->access_token($config);

        $customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: 'Guest';
        $contactId = $this->find_or_create_contact($config, $token, $customerName, $customer['email'] ?? null);

        $payload = [
            'customer_id' => $contactId,
            'line_items' => [
                [
                    'name' => $appointment['description'] ?? 'Randevu / Appointment',
                    'rate' => (float) ($appointment['total'] ?? $appointment['price'] ?? 0),
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->api_request($config, $token, 'POST', '/invoices', $payload);

        $invoiceId = $response['invoice']['invoice_id'] ?? null;

        if (!$invoiceId) {
            throw new RuntimeException('Zoho Books invoice creation did not return an invoice_id: ' . json_encode($response));
        }

        return (string) $invoiceId;
    }

    public function get_invoice_status(string $external_id): string
    {
        if (!$this->is_connected()) {
            throw new RuntimeException('Zoho Books connector is not configured.');
        }

        $config = $this->config();
        $token = $this->access_token($config);

        $response = $this->api_request($config, $token, 'GET', '/invoices/' . urlencode($external_id), []);

        // Zoho Books invoice.status is one of: draft, sent, viewed, overdue, paid, void, unpaid, partially_paid.
        return (string) ($response['invoice']['status'] ?? 'unknown');
    }

    private function find_or_create_contact(array $config, string $token, string $name, ?string $email): string
    {
        $searchResponse = $this->api_request(
            $config,
            $token,
            'GET',
            '/contacts?contact_name=' . urlencode($name),
            [],
        );

        $existingId = $searchResponse['contacts'][0]['contact_id'] ?? null;

        if ($existingId) {
            return (string) $existingId;
        }

        $createResponse = $this->api_request($config, $token, 'POST', '/contacts', [
            'contact_name' => $name,
            'email' => $email,
        ]);

        $newId = $createResponse['contact']['contact_id'] ?? null;

        if (!$newId) {
            throw new RuntimeException('Zoho Books contact creation did not return a contact_id.');
        }

        return (string) $newId;
    }

    private function config(): array
    {
        return [
            'region' => master_setting('zohobooks_region') ?: 'eu',
            'client_id' => (string) master_setting('zohobooks_client_id'),
            'client_secret' => (string) master_setting('zohobooks_client_secret'),
            'refresh_token' => (string) master_setting('zohobooks_refresh_token'),
            'organization_id' => (string) master_setting('zohobooks_organization_id'),
        ];
    }

    private function access_token(array $config): string
    {
        $host = self::ACCOUNT_HOSTS[$config['region']] ?? self::ACCOUNT_HOSTS['eu'];

        $ch = curl_init($host . '/oauth/v2/token');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'refresh_token',
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'refresh_token' => $config['refresh_token'],
            ]),
            CURLOPT_TIMEOUT => 30,
        ]);

        $raw = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('Zoho Books token request failed: ' . $error);
        }

        $parsed = json_decode((string) $raw, true);

        if (empty($parsed['access_token'])) {
            throw new RuntimeException('Zoho Books token response (HTTP ' . $http_code . '): ' . mb_substr((string) $raw, 0, 600));
        }

        return (string) $parsed['access_token'];
    }

    private function api_request(array $config, string $token, string $method, string $path, array $body): array
    {
        $host = self::API_HOSTS[$config['region']] ?? self::API_HOSTS['eu'];
        $url = $host . '/books/v3' . $path . (str_contains($path, '?') ? '&' : '?') . 'organization_id=' . $config['organization_id'];

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Zoho-oauthtoken ' . $token,
                'Content-Type: application/json;charset=UTF-8',
            ],
            CURLOPT_TIMEOUT => 30,
        ];

        if ($method !== 'GET') {
            $opts[CURLOPT_POSTFIELDS] = json_encode(array_filter($body, fn($v) => $v !== null));
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, $opts);

        $raw = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('Zoho Books API request failed: ' . $error);
        }

        $parsed = json_decode((string) $raw, true);

        if ($http_code >= 400 || !is_array($parsed)) {
            throw new RuntimeException(
                'Zoho Books API ' . $method . ' ' . $path . ' (HTTP ' . $http_code . '): ' . mb_substr((string) $raw, 0, 700),
            );
        }

        return $parsed;
    }
}
