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
 * Salon Flora customization - "Google Entegrasyonları" (2026-08-25): connect the company's own Google
 * account, or an individual provider's, to Contacts/Drive/Sheets/Docs/Tasks. Separate from the existing
 * Google Calendar sync (Google.php) - that keeps working exactly as before, untouched.
 *
 * @package Controllers
 */
class Google_integrations extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('google_integrations_client');
        $this->load->library('google_sheets_writer');
        $this->load->model('providers_model');
    }

    /**
     * Only the company connection may own Sheets syncs today (Drive/Sheets are company-only services -
     * see Google_integrations_client::SERVICES) - kept as a helper in case that ever changes.
     */
    private function require_sheets_sync_permissions(): void
    {
        if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }
    }

    /**
     * @param string $owner_type
     * @param int $owner_id
     *
     * @throws RuntimeException If the current user isn't allowed to manage this connection.
     */
    private function check_owner_permissions(string $owner_type, int $owner_id): void
    {
        if ($owner_type === 'company') {
            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            return;
        }

        // owner_type === 'provider' - same rule as the existing Calendar OAuth (Google.php::oauth()): an
        // admin/secretary with PRIV_USERS edit rights may manage any provider's connection, a provider
        // may only manage their own.
        $user_id = (int) session('user_id');

        if (cannot('edit', PRIV_USERS) && $user_id !== $owner_id) {
            throw new RuntimeException('You do not have the required permissions for this task.');
        }
    }

    /**
     * Render the Google Entegrasyonları page.
     */
    public function index(): void
    {
        method('get');

        if (cannot('view', PRIV_SYSTEM_SETTINGS) && cannot('view', PRIV_USERS)) {
            abort(403, 'Forbidden');
        }

        $connections = $this->google_integrations_client->get_all_connections();

        $company_connection = null;
        $provider_connections = [];

        foreach ($connections as $connection) {
            if ($connection['owner_type'] === 'company') {
                $company_connection = $connection;
            } else {
                $provider_connections[(int) $connection['owner_id']] = $connection;
            }
        }

        $role_slug = session('role_slug');
        $user_id = (int) session('user_id');

        // A provider only ever manages their own connection; admin/secretary see every provider.
        $providers = $role_slug === DB_SLUG_PROVIDER
            ? array_filter($this->providers_model->get(), fn($provider) => (int) $provider['id'] === $user_id)
            : $this->providers_model->get();

        // Salon Flora customization (2026-08-25) - "Veri Senkronizasyonları" (Sheets sync) section data.
        // Company-only for now (Sheets is a company-only service - Google_integrations_client::SERVICES).
        $sheet_syncs = [];

        if ($company_connection) {
            $syncs = $this->db->get_where('google_sheet_syncs', ['id_google_connections' => $company_connection['id']])->result_array();

            foreach ($syncs as &$sync) {
                $sync['field_mappings'] = $this->db
                    ->get_where('google_sheet_field_mappings', ['id_google_sheet_syncs' => $sync['id']])
                    ->result_array();

                usort($sync['field_mappings'], fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

                // Salon Flora customization - KVKK/HIPAA compliance badge (per explicit user instruction):
                // a pipe is "compliant" only if it never leaves a PII field in the clear - i.e. pii_mode is
                // 'exclude' or 'encrypted', never 'plaintext'.
                $sync['is_compliant'] = $sync['pii_mode'] !== 'plaintext';
            }

            $sheet_syncs = $syncs;
        }

        require_once APPPATH . 'libraries/Google_sheets_fields.php';

        html_vars([
            'page_title' => 'Google Entegrasyonları',
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'can_manage_company_connection' => can('edit', PRIV_SYSTEM_SETTINGS),
            'company_services' => Google_integrations_client::services_for_owner_type('company'),
            'provider_services' => Google_integrations_client::services_for_owner_type('provider'),
            'company_connection' => $company_connection,
            'providers' => array_map(
                fn($provider) => [
                    'id' => $provider['id'],
                    'name' => trim($provider['first_name'] . ' ' . $provider['last_name']),
                    'connection' => $provider_connections[(int) $provider['id']] ?? null,
                ],
                $providers,
            ),
            'sheet_modules' => Google_sheets_fields::MODULES,
            'sheet_field_catalogs' => array_combine(
                array_keys(Google_sheets_fields::MODULES),
                array_map(fn($module) => Google_sheets_fields::catalog($module), array_keys(Google_sheets_fields::MODULES)),
            ),
            'sheet_syncs' => $sheet_syncs,
        ]);

        $this->load->view('pages/google_integrations');
    }

    /**
     * Redirect to Google's consent screen for the given owner + service selection. Opened in a popup
     * window by the frontend (window.open), mirroring the existing Calendar OAuth flow exactly.
     *
     * @param string $owner_type 'company' | 'provider'.
     * @param string $owner_id '0' for company, the provider's user id otherwise.
     */
    public function oauth(string $owner_type, string $owner_id): void
    {
        if (!session('user_id')) {
            show_error('Forbidden', 403);
        }

        if (!in_array($owner_type, ['company', 'provider'], true)) {
            show_error('Invalid owner type', 400);
        }

        $owner_id = filter_var($owner_id, FILTER_VALIDATE_INT);

        if ($owner_id === false || $owner_id < 0) {
            show_error('Invalid owner id', 400);
        }

        try {
            $this->check_owner_permissions($owner_type, $owner_id);
        } catch (Throwable $e) {
            show_error($e->getMessage(), 403);
        }

        $requested_services = array_filter(explode(',', (string) request('services', '')));

        $allowed_services = array_keys(Google_integrations_client::services_for_owner_type($owner_type));

        $service_keys = array_values(array_intersect($requested_services, $allowed_services));

        if (empty($service_keys)) {
            show_error('En az bir servis seçmelisiniz.', 400);
        }

        $csrf_token = bin2hex(random_bytes(32));
        $oauth_state = build_google_oauth_state($csrf_token, 'google_integrations/oauth_callback');

        session([
            'google_integrations_owner_type' => $owner_type,
            'google_integrations_owner_id' => $owner_id,
            'google_integrations_services' => $service_keys,
            'google_integrations_oauth_state' => $csrf_token,
        ]);

        header('Location: ' . $this->google_integrations_client->get_auth_url($service_keys, $oauth_state));
    }

    /**
     * OAuth callback - exchanges the code, stores the connection, and closes the popup window.
     */
    public function oauth_callback(): void
    {
        if (!session('user_id')) {
            abort(403, 'Forbidden');
        }

        $returned_state = (string) request('state');
        $stored_state = session('google_integrations_oauth_state');

        $csrf_to_verify = $returned_state;
        $unpacked = verify_google_oauth_state($returned_state);
        if ($unpacked !== null && !empty($unpacked['csrf'])) {
            $csrf_to_verify = $unpacked['csrf'];
        }

        if (empty($csrf_to_verify) || empty($stored_state) || !hash_equals($stored_state, $csrf_to_verify)) {
            session(['google_integrations_oauth_state' => null]);
            show_error('Security validation failed. Please try connecting Google again.', 403);

            return;
        }

        session(['google_integrations_oauth_state' => null]);

        $code = request('code');

        if (empty($code)) {
            response('Code authorization failed.');

            return;
        }

        $owner_type = session('google_integrations_owner_type');
        $owner_id = session('google_integrations_owner_id');
        $service_keys = session('google_integrations_services');

        if (empty($owner_type) || $owner_id === null || empty($service_keys)) {
            response('Missing connection context.');

            return;
        }

        try {
            $this->check_owner_permissions($owner_type, (int) $owner_id);
        } catch (Throwable $e) {
            show_error($e->getMessage(), 403);

            return;
        }

        try {
            $result = $this->google_integrations_client->authenticate($code, $service_keys);
        } catch (Throwable $e) {
            response('Token authorization failed: ' . $e->getMessage());

            return;
        }

        $this->google_integrations_client->save_connection($owner_type, (int) $owner_id, $result, $service_keys);

        session([
            'google_integrations_owner_type' => null,
            'google_integrations_owner_id' => null,
            'google_integrations_services' => null,
        ]);

        echo '<script>window.opener && window.opener.postMessage("google_integrations_oauth_success", window.location.origin); window.close();</script>';
    }

    /**
     * Remove a connection.
     */
    public function disconnect(): void
    {
        try {
            method('post');

            check('owner_type', 'string');
            check('owner_id', 'numeric');

            $owner_type = (string) request('owner_type');
            $owner_id = (int) request('owner_id');

            $this->check_owner_permissions($owner_type, $owner_id);

            $this->google_integrations_client->disconnect($owner_type, $owner_id);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Salon Flora customization (2026-08-25) - choose WHERE the Drive service actually writes: an
     * existing folder (pasted URL/id) or a brand new one created on the spot.
     */
    public function set_drive_target(): void
    {
        try {
            method('post');

            check('owner_type', 'string');
            check('owner_id', 'numeric');
            check('mode', 'string');
            check('value', 'string|null');

            $owner_type = (string) request('owner_type');
            $owner_id = (int) request('owner_id');
            $mode = (string) request('mode');

            $this->check_owner_permissions($owner_type, $owner_id);

            $connection = $this->google_integrations_client->get_connection($owner_type, $owner_id);

            if (!$connection || !in_array('drive', $connection['enabled_services'], true)) {
                throw new InvalidArgumentException('Önce Drive servisini bağlayın.');
            }

            $this->google_integrations_client->authenticate_connection($connection);

            if ($mode === 'create') {
                $name = trim((string) request('value')) ?: 'BooKi';
                $result = $this->google_integrations_client->create_drive_folder($name);
            } elseif ($mode === 'existing') {
                $folder_id = Google_integrations_client::extract_drive_folder_id((string) request('value', ''));

                if (empty($folder_id)) {
                    throw new InvalidArgumentException('Klasör ID veya linki girin.');
                }

                $result = ['id' => $folder_id, 'name' => $this->google_integrations_client->get_drive_folder_name($folder_id)];
            } else {
                throw new InvalidArgumentException('Geçersiz mod.');
            }

            $this->google_integrations_client->set_drive_target($owner_type, $owner_id, $result['id'], $result['name']);

            json_response(['success' => true, 'folder' => $result]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Salon Flora customization (2026-08-25) - choose WHERE the Sheets service actually writes: an
     * existing spreadsheet (pasted URL/id) or a brand new one created on the spot.
     */
    public function set_sheets_target(): void
    {
        try {
            method('post');

            check('owner_type', 'string');
            check('owner_id', 'numeric');
            check('mode', 'string');
            check('value', 'string|null');

            $owner_type = (string) request('owner_type');
            $owner_id = (int) request('owner_id');
            $mode = (string) request('mode');

            $this->check_owner_permissions($owner_type, $owner_id);

            $connection = $this->google_integrations_client->get_connection($owner_type, $owner_id);

            if (!$connection || !in_array('sheets', $connection['enabled_services'], true)) {
                throw new InvalidArgumentException('Önce Sheets servisini bağlayın.');
            }

            $this->google_integrations_client->authenticate_connection($connection);

            if ($mode === 'create') {
                $name = trim((string) request('value')) ?: 'BooKi';
                $result = $this->google_integrations_client->create_spreadsheet($name);
            } elseif ($mode === 'existing') {
                $spreadsheet_id = Google_integrations_client::extract_spreadsheet_id((string) request('value', ''));

                if (empty($spreadsheet_id)) {
                    throw new InvalidArgumentException('E-Tablo ID veya linki girin.');
                }

                $result = [
                    'id' => $spreadsheet_id,
                    'name' => $this->google_integrations_client->get_spreadsheet_name($spreadsheet_id),
                ];
            } else {
                throw new InvalidArgumentException('Geçersiz mod.');
            }

            $this->google_integrations_client->set_sheets_target($owner_type, $owner_id, $result['id'], $result['name']);

            json_response(['success' => true, 'spreadsheet' => $result]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * The company's connection row, or throws - every Sheets sync endpoint below needs it.
     *
     * @throws InvalidArgumentException
     */
    private function company_connection(): array
    {
        $connection = $this->google_integrations_client->get_connection('company', 0);

        if (!$connection || !in_array('sheets', $connection['enabled_services'], true)) {
            throw new InvalidArgumentException('Önce şirket hesabı için Sheets servisini bağlayın.');
        }

        return $connection;
    }

    /**
     * List a connected spreadsheet's tabs - step 1 of the sync wizard ("hangi sayfa").
     */
    public function sheet_tabs(): void
    {
        try {
            method('get');

            $this->require_sheets_sync_permissions();

            check('spreadsheet_id', 'string');

            $connection = $this->company_connection();

            $tabs = $this->google_sheets_writer->list_sheet_tabs($connection, (string) request('spreadsheet_id'));

            json_response(['success' => true, 'tabs' => $tabs]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Read a tab's existing header row - step 2 of the sync wizard ("hangi sütuna hangi bilgi gelecek"),
     * lets the UI pre-fill a mapping if the sheet already has headers.
     */
    public function sheet_header(): void
    {
        try {
            method('get');

            $this->require_sheets_sync_permissions();

            check('spreadsheet_id', 'string');
            check('sheet_title', 'string');
            check('header_row', 'numeric|null');

            $connection = $this->company_connection();

            $headers = $this->google_sheets_writer->read_header_row(
                $connection,
                (string) request('spreadsheet_id'),
                (string) request('sheet_title'),
                (int) request('header_row', 1),
            );

            json_response(['success' => true, 'headers' => $headers]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * List every configured sync for a module (used by the wizard to warn about re-using a tab).
     */
    public function list_syncs(): void
    {
        try {
            method('get');

            $this->require_sheets_sync_permissions();

            $syncs = $this->db->get('google_sheet_syncs')->result_array();

            json_response(['success' => true, 'syncs' => $syncs]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Create a new module -> sheet-tab sync pipe, with its field->column mapping. Writes the header row
     * immediately so the sheet is self-explanatory the moment it's configured, then does an initial
     * backfill of every existing record in that module (so the sheet isn't empty until the next change).
     */
    public function create_sync(): void
    {
        try {
            method('post');

            $this->require_sheets_sync_permissions();

            check('module', 'string');
            check('name', 'string');
            check('spreadsheet_id', 'string');
            check('sheet_title', 'string');
            check('sheet_gid', 'numeric|null');
            check('header_row', 'numeric|null');
            check('pii_mode', 'string|null');
            check('dedup_column', 'string|null');
            check('write_mode', 'string|null');
            check('field_keys', 'array');

            require_once APPPATH . 'libraries/Google_sheets_fields.php';

            $module = (string) request('module');

            if (!array_key_exists($module, Google_sheets_fields::MODULES)) {
                throw new InvalidArgumentException('Geçersiz modül.');
            }

            $catalog = Google_sheets_fields::catalog($module);

            $field_keys = array_values(array_intersect((array) request('field_keys'), array_keys($catalog)));

            if (empty($field_keys)) {
                throw new InvalidArgumentException('En az bir alan seçmelisiniz.');
            }

            $pii_mode = (string) request('pii_mode', 'exclude');

            if (!in_array($pii_mode, ['exclude', 'encrypted', 'plaintext'], true)) {
                throw new InvalidArgumentException('Geçersiz KVKK/HIPAA modu.');
            }

            $write_mode = (string) request('write_mode', 'upsert');

            if (!in_array($write_mode, ['append', 'upsert'], true)) {
                throw new InvalidArgumentException('Geçersiz yazma modu.');
            }

            $connection = $this->company_connection();

            $header_row = (int) request('header_row', 1);
            $sheet_title = (string) request('sheet_title');

            $now = date('Y-m-d H:i:s');

            $this->db->insert('google_sheet_syncs', [
                'id_google_connections' => $connection['id'],
                'module' => $module,
                'name' => (string) request('name'),
                'spreadsheet_id' => (string) request('spreadsheet_id'),
                'sheet_title' => $sheet_title,
                'sheet_gid' => request('sheet_gid') !== null ? (int) request('sheet_gid') : null,
                'header_row' => $header_row,
                'pii_mode' => $pii_mode,
                'dedup_column' => request('dedup_column') ?: null,
                'write_mode' => $write_mode,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sync_id = (int) $this->db->insert_id();

            $field_columns = [];

            foreach (array_values($field_keys) as $index => $field_key) {
                $column_letter = Google_sheets_writer::column_letter($index);

                $this->db->insert('google_sheet_field_mappings', [
                    'id_google_sheet_syncs' => $sync_id,
                    'field_key' => $field_key,
                    'column_letter' => $column_letter,
                    'column_header' => $catalog[$field_key]['label'],
                    'sort_order' => $index,
                ]);

                $field_columns[] = ['field_key' => $field_key, 'column_letter' => $column_letter];
            }

            $this->google_sheets_writer->write_header_row(
                $connection,
                (string) request('spreadsheet_id'),
                $sheet_title,
                $header_row,
                $field_columns,
                $catalog,
            );

            $backfill = $this->google_sheets_writer->backfill($sync_id);

            json_response(['success' => true, 'id' => $sync_id, 'backfill' => $backfill]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Toggle a sync on/off, or change its KVKK/HIPAA posture / write mode - without touching the field
     * mapping (use delete_sync + create_sync to remap columns).
     */
    public function update_sync(): void
    {
        try {
            method('post');

            $this->require_sheets_sync_permissions();

            check('id', 'numeric');
            check('is_active', 'numeric|null');
            check('pii_mode', 'string|null');
            check('write_mode', 'string|null');

            $sync = $this->db->get_where('google_sheet_syncs', ['id' => (int) request('id')])->row_array();

            if (!$sync) {
                throw new InvalidArgumentException('Senkronizasyon bulunamadı.');
            }

            $data = ['updated_at' => date('Y-m-d H:i:s')];

            if (request('is_active') !== null) {
                $data['is_active'] = (bool) (int) request('is_active');
            }

            if (request('pii_mode') !== null) {
                if (!in_array(request('pii_mode'), ['exclude', 'encrypted', 'plaintext'], true)) {
                    throw new InvalidArgumentException('Geçersiz KVKK/HIPAA modu.');
                }

                $data['pii_mode'] = request('pii_mode');
            }

            if (request('write_mode') !== null) {
                if (!in_array(request('write_mode'), ['append', 'upsert'], true)) {
                    throw new InvalidArgumentException('Geçersiz yazma modu.');
                }

                $data['write_mode'] = request('write_mode');
            }

            $this->db->update('google_sheet_syncs', $data, ['id' => $sync['id']]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Remove a sync pipe entirely (mappings + log cascade-cleaned manually - no FK constraints on these
     * tables, see migration 099).
     */
    public function delete_sync(): void
    {
        try {
            method('post');

            $this->require_sheets_sync_permissions();

            check('id', 'numeric');

            $sync_id = (int) request('id');

            $this->db->delete('google_sheet_field_mappings', ['id_google_sheet_syncs' => $sync_id]);
            $this->db->delete('google_sheet_sync_log', ['id_google_sheet_syncs' => $sync_id]);
            $this->db->delete('google_sheet_syncs', ['id' => $sync_id]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * "Şimdi Senkronize Et" - manually push every current record of the sync's module through it.
     */
    public function sync_now(): void
    {
        try {
            method('post');

            $this->require_sheets_sync_permissions();

            check('id', 'numeric');

            $result = $this->google_sheets_writer->backfill((int) request('id'));

            $this->db->update(
                'google_sheet_syncs',
                ['last_synced_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
                ['id' => (int) request('id')],
            );

            json_response(['success' => true] + $result);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
