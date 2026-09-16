<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

require_once __DIR__ . '/Accounting_connector_interface.php';

/**
 * Parasut (Muhasebe Yönetim Sistemi) accounting connector.
 *
 * Implements OAuth2 integration with Paraşüt's API for automatic invoice creation.
 * Token management and encryption are handled by Accounting_settings_model.
 *
 * @package Libraries
 */
class Parasut_connector implements Accounting_connector_interface
{
    /**
     * Paraşüt OAuth2 token endpoint.
     *
     * @var string
     */
    private string $token_endpoint = 'https://api.parasut.com/oauth/token';

    /**
     * Paraşüt API base URL.
     *
     * @var string
     */
    private string $api_base = 'https://api.parasut.com/v4';

    /**
     * Current stored connection data (from the database).
     *
     * @var array|null
     */
    private ?array $connection_data = null;

    /**
     * CodeIgniter instance.
     *
     * @var object
     */
    private object $CI;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('accounting_settings_model');
    }

    /**
     * Establish a connection using OAuth2 credentials.
     *
     * Accepts an authorization_code (from OAuth2 callback) and exchanges it for access/refresh tokens.
     * Alternatively, accepts stored refresh_token for re-authentication.
     *
     * @param array $credentials Array with either 'authorization_code' or 'refresh_token'.
     *
     * @throws RuntimeException If token exchange fails.
     */
    public function connect(array $credentials): void
    {
        try {
            // Determine which grant type to use
            if (!empty($credentials['authorization_code'])) {
                $grant_data = [
                    'grant_type' => 'authorization_code',
                    'code' => $credentials['authorization_code'],
                    'redirect_uri' => site_url('accounting_settings/callback'),
                ];
            } elseif (!empty($credentials['refresh_token'])) {
                $grant_data = [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $credentials['refresh_token'],
                ];
            } else {
                throw new RuntimeException('Either authorization_code or refresh_token must be provided.');
            }

            // Add Paraşüt client credentials (from env)
            $grant_data['client_id'] = getenv('PARASUT_CLIENT_ID');
            $grant_data['client_secret'] = getenv('PARASUT_CLIENT_SECRET');

            if (empty($grant_data['client_id']) || empty($grant_data['client_secret'])) {
                throw new RuntimeException('PARASUT_CLIENT_ID and PARASUT_CLIENT_SECRET environment variables not set.');
            }

            // Exchange for tokens
            $token_response = $this->exchange_tokens($grant_data);

            // Save to database (encrypted)
            $this->CI->accounting_settings_model->save_connection([
                'provider' => 'parasut',
                'access_token' => $token_response['access_token'] ?? null,
                'refresh_token' => $token_response['refresh_token'] ?? null,
                'company_id' => $credentials['company_id'] ?? null,
                'expires_at' => !empty($token_response['expires_in'])
                    ? date('Y-m-d H:i:s', time() + $token_response['expires_in'])
                    : null,
            ]);

            $this->connection_data = null; // Invalidate cache
        } catch (Throwable $e) {
            log_message('error', 'Parasut_connector::connect() failed: ' . $e->getMessage());
            throw new RuntimeException('Failed to connect to Paraşüt: ' . $e->getMessage());
        }
    }

    /**
     * Check if a valid connection is established.
     *
     * @return bool True if access token is stored and hasn't expired.
     */
    public function is_connected(): bool
    {
        $conn = $this->get_connection();

        if (!$conn || empty($conn['access_token'])) {
            return false;
        }

        // If expires_at is set and in the past, consider it disconnected
        if (!empty($conn['expires_at']) && strtotime($conn['expires_at']) <= time()) {
            return false;
        }

        return true;
    }

    /**
     * Create an invoice in Paraşüt for an appointment.
     *
     * @param array $appointment Appointment data (id, start_datetime, end_datetime, id_services, etc.).
     * @param array $customer Customer data (id, first_name, last_name, email, phone_number, etc.).
     *
     * @return string External invoice ID (not yet implemented - returns placeholder).
     *
     * @throws RuntimeException Always for now - feature is stub/placeholder.
     */
    public function create_invoice(array $appointment, array $customer): string
    {
        throw new RuntimeException(
            'Invoice creation via Paraşüt connector is not yet implemented. '
            . 'This skeleton establishes only the connection layer. '
            . 'Future PR will implement full invoice API integration.',
        );

        // STUB: The following is pseudocode for a future implementation:
        // if (!$this->is_connected()) {
        //     throw new RuntimeException('Paraşüt connector is not connected.');
        // }
        //
        // $invoice_data = [
        //     'invoice' => [
        //         'invoice_type' => 'invoice',
        //         'issue_date' => date('Y-m-d', strtotime($appointment['start_datetime'])),
        //         'contact_id' => ... (lookup or create customer in Paraşüt),
        //         'line_items' => [...],
        //     ],
        // ];
        //
        // $response = $this->api_request('POST', '/invoices', $invoice_data);
        // return $response['data']['id'] ?? null;
    }

    /**
     * Get the status of a previously created invoice.
     *
     * @param string $external_id Paraşüt invoice ID.
     *
     * @return string Invoice status (placeholder - not implemented).
     *
     * @throws RuntimeException If connector is not connected or if lookup fails.
     */
    public function get_invoice_status(string $external_id): string
    {
        if (!$this->is_connected()) {
            throw new RuntimeException('Paraşüt connector is not connected.');
        }

        // STUB: Pseudocode for a future implementation:
        // $response = $this->api_request('GET', '/invoices/' . urlencode($external_id));
        // return $response['data']['status'] ?? 'unknown';

        return 'unknown'; // Placeholder
    }

    /**
     * Exchange OAuth2 grant for access/refresh tokens.
     *
     * @param array $grant_data Grant parameters (grant_type, code/refresh_token, client_id, client_secret).
     *
     * @return array Token response with 'access_token', 'refresh_token' (if available), 'expires_in'.
     *
     * @throws RuntimeException If the token endpoint returns an error.
     */
    private function exchange_tokens(array $grant_data): array
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->token_endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($grant_data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);

        curl_close($curl);

        if ($curl_error) {
            throw new RuntimeException('cURL error: ' . $curl_error);
        }

        if ($http_code !== 200) {
            throw new RuntimeException(
                'Paraşüt token endpoint returned HTTP ' . $http_code . ': ' . substr($response, 0, 500),
            );
        }

        $decoded = json_decode($response, true);

        if (empty($decoded['access_token'])) {
            throw new RuntimeException('No access_token in response: ' . substr($response, 0, 500));
        }

        return $decoded;
    }

    /**
     * Get the stored connection data from the database.
     *
     * @return array|null Connection record with decrypted tokens, or null if no connection exists.
     */
    private function get_connection(): ?array
    {
        if ($this->connection_data === null) {
            $this->connection_data = $this->CI->accounting_settings_model->get_connection() ?? false;
        }

        return $this->connection_data === false ? null : $this->connection_data;
    }
}
