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

/**
 * Salon Flora customization - "Google Entegrasyonları" (2026-08-25): a general-purpose Google OAuth
 * connection manager, separate from the existing Calendar-specific sync (Google.php/Google_sync.php -
 * untouched, still the only thing driving Calendar sync). Lets the company (owner_type='company') or an
 * individual provider (owner_type='provider') connect their own Google account for one or more of
 * Contacts/Drive/Sheets/Docs/Tasks (see SERVICES below).
 *
 * This library only manages the CONNECTION (OAuth consent, token storage/refresh) - it does not yet do
 * anything with the connected services (no Contacts sync, no Sheets export, etc.); those are separate,
 * later features built on top of an established connection.
 *
 * @package Libraries
 */
class Google_integrations_client
{
    /**
     * Registry of the services this hub can connect. 'owner_types' lists who is allowed to connect that
     * service - Drive/Sheets/Docs are company-only (a single shared business Drive/Sheets/Docs account
     * makes far more sense than one per therapist); Contacts/Tasks make sense for either.
     */
    public const SERVICES = [
        'contacts' => [
            'label' => 'Kişiler (Contacts)',
            'scope' => 'https://www.googleapis.com/auth/contacts',
            'owner_types' => ['company', 'provider'],
        ],
        'drive' => [
            'label' => 'Drive',
            'scope' => 'https://www.googleapis.com/auth/drive.file',
            'owner_types' => ['company'],
        ],
        'sheets' => [
            'label' => 'E-Tablolar (Sheets)',
            'scope' => 'https://www.googleapis.com/auth/spreadsheets',
            'owner_types' => ['company'],
        ],
        'docs' => [
            'label' => 'Dokümanlar (Docs)',
            'scope' => 'https://www.googleapis.com/auth/documents',
            'owner_types' => ['company'],
        ],
        'tasks' => [
            'label' => 'Görevler (Tasks)',
            'scope' => 'https://www.googleapis.com/auth/tasks',
            'owner_types' => ['company', 'provider'],
        ],
    ];

    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * @var Google_Client
     */
    protected Google_Client $client;

    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('settings_model');
    }

    /**
     * Services a given owner type is allowed to connect.
     *
     * @param string $owner_type 'company' | 'provider'.
     *
     * @return array<string, array> Subset of SERVICES.
     */
    public static function services_for_owner_type(string $owner_type): array
    {
        return array_filter(self::SERVICES, fn($service) => in_array($owner_type, $service['owner_types'], true));
    }

    /**
     * @return string
     */
    protected function get_client_id(): string
    {
        return setting('google_client_id') ?: (config('google_client_id') ?: '');
    }

    /**
     * @return string
     */
    protected function get_client_secret(): string
    {
        return setting('google_client_secret') ?: (config('google_client_secret') ?: '');
    }

    /**
     * Initialize a Google_Client requesting exactly the scopes for the given service keys. A fresh
     * instance every call - never reused across requests for different services/owners.
     *
     * @param string[] $service_keys Keys from SERVICES.
     */
    protected function initialize_client(array $service_keys): void
    {
        // Salon Flora customization - TLS certificate validation left ON (Guzzle default) - see the
        // matching comment in Google_sync.php::initialize_clients() for why 'verify' => false is wrong.
        $http = new GuzzleHttp\Client();

        $this->client = new Google_Client();
        $this->client->setHttpClient($http);
        $this->client->setApplicationName('BooKi');
        $this->client->setClientId($this->get_client_id());
        $this->client->setClientSecret($this->get_client_secret());
        $this->client->setRedirectUri(site_url('google_integrations/oauth_callback'));
        $this->client->setPrompt('consent');
        $this->client->setAccessType('offline');

        foreach ($service_keys as $service_key) {
            if (isset(self::SERVICES[$service_key])) {
                $this->client->addScope([self::SERVICES[$service_key]['scope']]);
            }
        }

        // Always request 'openid email' too - the ID token that comes back with the access token then
        // carries the connected account's email as a verifiable JWT claim (see authenticate()), with no
        // extra API call or extra vendored service needed just to look up "whose account is this".
        $this->client->addScope(['openid', 'email']);
    }

    /**
     * Build the Google consent URL for the given service selection.
     *
     * @param string[] $service_keys
     * @param string $state CSRF state token.
     *
     * @return string
     */
    public function get_auth_url(array $service_keys, string $state): string
    {
        $this->initialize_client($service_keys);

        $this->client->setState($state);

        return $this->client->createAuthUrl() . '&max_auth_age=0';
    }

    /**
     * Exchange an OAuth code for tokens, and fetch the connected account's email.
     *
     * @param string $code
     * @param string[] $service_keys Same selection the auth URL was built with (needed again because
     *   fetchAccessTokenWithAuthCode() requires a client configured with the exact same scopes).
     *
     * @return array{token: array, email: string|null, granted_scopes: string[]}
     *
     * @throws RuntimeException
     */
    public function authenticate(string $code, array $service_keys): array
    {
        $this->initialize_client($service_keys);

        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new RuntimeException(
                'Google Authentication Error (' . $token['error'] . '): ' . ($token['error_description'] ?? ''),
            );
        }

        $email = null;

        try {
            if (!empty($token['id_token'])) {
                $claims = $this->client->verifyIdToken($token['id_token']);
                $email = is_array($claims) ? ($claims['email'] ?? null) : null;
            }
        } catch (Throwable $e) {
            log_message('error', 'Google_integrations_client - could not verify id_token: ' . $e->getMessage());
        }

        return [
            'token' => $token,
            'email' => $email,
            'granted_scopes' => is_string($token['scope'] ?? null) ? explode(' ', $token['scope']) : [],
        ];
    }

    /**
     * Persist (insert or update) a connection row. Tokens are PII-grade secrets and go through the same
     * sf_pii_encrypt() pipeline used for customer/provider contact fields.
     *
     * @param string $owner_type 'company' | 'provider'.
     * @param int $owner_id 0 for company, the provider's user id otherwise.
     * @param array $auth_result Result of authenticate().
     * @param string[] $service_keys The services this connection is enabled for.
     */
    public function save_connection(string $owner_type, int $owner_id, array $auth_result, array $service_keys): void
    {
        $token = $auth_result['token'];

        $now = date('Y-m-d H:i:s');

        $data = [
            'owner_type' => $owner_type,
            'owner_id' => $owner_id,
            'google_account_email' => $auth_result['email'],
            'access_token' => sf_pii_encrypt($token['access_token'] ?? null),
            'granted_scopes' => json_encode($auth_result['granted_scopes'], JSON_UNESCAPED_SLASHES),
            'enabled_services' => json_encode(array_values($service_keys)),
            'token_expires_at' => isset($token['expires_in'])
                ? date('Y-m-d H:i:s', time() + (int) $token['expires_in'])
                : null,
            'updated_at' => $now,
        ];

        // A refresh token is only returned on the FIRST consent (or when prompt=consent forces a new
        // one, which we always do) - but guard anyway so a re-auth glitch never wipes out a working one.
        if (!empty($token['refresh_token'])) {
            $data['refresh_token'] = sf_pii_encrypt($token['refresh_token']);
        }

        $existing = $this->CI->db
            ->get_where('google_connections', ['owner_type' => $owner_type, 'owner_id' => $owner_id])
            ->row_array();

        if ($existing) {
            $this->CI->db->update('google_connections', $data, ['id' => $existing['id']]);
        } else {
            $data['created_at'] = $now;
            $this->CI->db->insert('google_connections', $data);
        }
    }

    /**
     * @param string $owner_type
     * @param int $owner_id
     *
     * @return array|null Decrypted connection row, or null if not connected.
     */
    public function get_connection(string $owner_type, int $owner_id): ?array
    {
        $row = $this->CI->db
            ->get_where('google_connections', ['owner_type' => $owner_type, 'owner_id' => $owner_id])
            ->row_array();

        if (!$row) {
            return null;
        }

        $row['access_token'] = sf_pii_is_encrypted($row['access_token']) ? sf_pii_decrypt($row['access_token']) : $row['access_token'];
        $row['refresh_token'] = sf_pii_is_encrypted($row['refresh_token']) ? sf_pii_decrypt($row['refresh_token']) : $row['refresh_token'];
        $row['enabled_services'] = json_decode((string) $row['enabled_services'], true) ?: [];
        $row['granted_scopes'] = json_decode((string) $row['granted_scopes'], true) ?: [];

        return $row;
    }

    /**
     * Salon Flora customization (2026-08-25) - authenticate $this->client/$this->service against a
     * stored connection's refresh token, for Drive/Sheets target selection (create/validate). Call
     * before create_drive_folder()/create_spreadsheet()/get_drive_service()/get_sheets_service().
     *
     * @param array $connection Result of get_connection().
     */
    public function authenticate_connection(array $connection): void
    {
        $this->initialize_client($connection['enabled_services']);

        $this->client->refreshToken($connection['refresh_token']);
    }

    /**
     * @return Google_Service_Drive Must call authenticate_connection() first.
     */
    public function get_drive_service(): Google_Service_Drive
    {
        return new Google_Service_Drive($this->client);
    }

    /**
     * @return Google_Service_Sheets Must call authenticate_connection() first.
     */
    public function get_sheets_service(): Google_Service_Sheets
    {
        return new Google_Service_Sheets($this->client);
    }

    /**
     * Create a new Drive folder under the connection's account and return {id, name}.
     *
     * @param string $name
     *
     * @return array{id: string, name: string}
     */
    public function create_drive_folder(string $name): array
    {
        $folder = new Google_Service_Drive_DriveFile();
        $folder->setName($name);
        $folder->setMimeType('application/vnd.google-apps.folder');

        $created = $this->get_drive_service()->files->create($folder, ['fields' => 'id, name']);

        return ['id' => $created->getId(), 'name' => $created->getName()];
    }

    /**
     * Validate that a Drive folder id exists and is reachable with the current connection, returning its
     * display name.
     *
     * @param string $folder_id
     *
     * @return string The folder's name.
     *
     * @throws RuntimeException If the folder can't be found/accessed.
     */
    public function get_drive_folder_name(string $folder_id): string
    {
        try {
            $file = $this->get_drive_service()->files->get($folder_id, ['fields' => 'id, name, mimeType']);
        } catch (Throwable $e) {
            throw new RuntimeException('Klasör bulunamadı veya erişim izni yok: ' . $e->getMessage());
        }

        if ($file->getMimeType() !== 'application/vnd.google-apps.folder') {
            throw new RuntimeException('Bu ID bir klasöre değil, başka bir dosyaya ait.');
        }

        return $file->getName();
    }

    /**
     * Create a new spreadsheet under the connection's account and return {id, name}.
     *
     * @param string $name
     *
     * @return array{id: string, name: string}
     */
    public function create_spreadsheet(string $name): array
    {
        $properties = new Google_Service_Sheets_SpreadsheetProperties();
        $properties->setTitle($name);

        $spreadsheet = new Google_Service_Sheets_Spreadsheet();
        $spreadsheet->setProperties($properties);

        $created = $this->get_sheets_service()->spreadsheets->create($spreadsheet, ['fields' => 'spreadsheetId, properties']);

        return ['id' => $created->getSpreadsheetId(), 'name' => $created->getProperties()->getTitle()];
    }

    /**
     * Validate that a spreadsheet id exists and is reachable, returning its title.
     *
     * @param string $spreadsheet_id
     *
     * @return string The spreadsheet's title.
     *
     * @throws RuntimeException If the spreadsheet can't be found/accessed.
     */
    public function get_spreadsheet_name(string $spreadsheet_id): string
    {
        try {
            $spreadsheet = $this->get_sheets_service()->spreadsheets->get($spreadsheet_id, ['fields' => 'properties']);
        } catch (Throwable $e) {
            throw new RuntimeException('E-Tablo bulunamadı veya erişim izni yok: ' . $e->getMessage());
        }

        return $spreadsheet->getProperties()->getTitle();
    }

    /**
     * Extract a Drive folder id from either a raw id or a folder URL
     * (drive.google.com/drive/folders/<id>).
     *
     * @param string $value
     *
     * @return string
     */
    public static function extract_drive_folder_id(string $value): string
    {
        if (preg_match('#/folders/([a-zA-Z0-9_-]+)#', $value, $matches)) {
            return $matches[1];
        }

        return trim($value);
    }

    /**
     * Extract a spreadsheet id from either a raw id or a spreadsheet URL
     * (docs.google.com/spreadsheets/d/<id>/...).
     *
     * @param string $value
     *
     * @return string
     */
    public static function extract_spreadsheet_id(string $value): string
    {
        if (preg_match('#/spreadsheets/d/([a-zA-Z0-9_-]+)#', $value, $matches)) {
            return $matches[1];
        }

        return trim($value);
    }

    /**
     * Persist the Drive folder target for a connection.
     */
    public function set_drive_target(string $owner_type, int $owner_id, string $folder_id, string $folder_name): void
    {
        $this->CI->db->update(
            'google_connections',
            ['drive_folder_id' => $folder_id, 'drive_folder_name' => $folder_name, 'updated_at' => date('Y-m-d H:i:s')],
            ['owner_type' => $owner_type, 'owner_id' => $owner_id],
        );
    }

    /**
     * Persist the Sheets spreadsheet target for a connection.
     */
    public function set_sheets_target(string $owner_type, int $owner_id, string $spreadsheet_id, string $spreadsheet_name): void
    {
        $this->CI->db->update(
            'google_connections',
            [
                'sheets_spreadsheet_id' => $spreadsheet_id,
                'sheets_spreadsheet_name' => $spreadsheet_name,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            ['owner_type' => $owner_type, 'owner_id' => $owner_id],
        );
    }

    /**
     * All connections (company + every provider that has one), decrypted.
     *
     * @return array<int, array>
     */
    public function get_all_connections(): array
    {
        $rows = $this->CI->db->get('google_connections')->result_array();

        return array_map(function ($row) {
            $row['access_token'] = sf_pii_is_encrypted($row['access_token']) ? sf_pii_decrypt($row['access_token']) : $row['access_token'];
            $row['refresh_token'] = sf_pii_is_encrypted($row['refresh_token']) ? sf_pii_decrypt($row['refresh_token']) : $row['refresh_token'];
            $row['enabled_services'] = json_decode((string) $row['enabled_services'], true) ?: [];
            $row['granted_scopes'] = json_decode((string) $row['granted_scopes'], true) ?: [];

            return $row;
        }, $rows);
    }

    /**
     * @param string $owner_type
     * @param int $owner_id
     */
    public function disconnect(string $owner_type, int $owner_id): void
    {
        $this->CI->db->delete('google_connections', ['owner_type' => $owner_type, 'owner_id' => $owner_id]);
    }
}
