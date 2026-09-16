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
 * Salon Flora customization (2026-08-25) - real-time, multi-module Google Sheets sync engine. Every
 * create/update/delete of an appointment/customer/provider calls sync_record(), which pushes the change
 * to every active google_sheet_syncs pipe configured for that module, instantly (no queue/cron - this
 * runs inline in the request that made the change, per the user's explicit "anında sheets'e işlensin").
 *
 * A pipe is "one module -> one sheet tab", with a user-chosen field->column mapping (field_mappings) and
 * a KVKK/HIPAA posture (pii_mode - see migration 099). Row identity across syncs is tracked in
 * google_sheet_sync_log (record_id -> row_number), so repeat writes update the same row (write_mode
 * 'upsert') instead of appending duplicates, and unchanged rows (same row_hash) are skipped entirely to
 * stay well under Sheets' write-rate limits.
 *
 * Failures for one pipe (bad token, sheet deleted, rate limit) are caught and logged to
 * google_sheet_syncs.last_error - they never interrupt the appointment/customer/provider save that
 * triggered them, and never affect any other pipe.
 *
 * @package Libraries
 */
class Google_sheets_writer
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->library('google_integrations_client');
        $this->CI->load->model('customers_model');
        $this->CI->load->model('providers_model');
        $this->CI->load->model('appointments_model');

        require_once APPPATH . 'libraries/Google_sheets_fields.php';
    }

    /**
     * Entry point - called from Calendar.php/Customers.php/Providers.php right after a save or delete.
     * Never throws - a broken Sheets pipe must not break the save it's reacting to.
     *
     * @param string $module 'appointments' | 'customers' | 'providers'.
     * @param int $record_id
     * @param string $operation 'upsert' | 'delete'.
     */
    public function sync_record(string $module, int $record_id, string $operation): void
    {
        try {
            $syncs = $this->CI->db
                ->get_where('google_sheet_syncs', ['module' => $module, 'is_active' => true])
                ->result_array();
        } catch (Throwable $e) {
            log_message('error', 'Google_sheets_writer - could not load syncs: ' . $e->getMessage());

            return;
        }

        foreach ($syncs as $sync) {
            try {
                $this->run_sync($sync, $record_id, $operation);
            } catch (Throwable $e) {
                log_message(
                    'error',
                    'Google_sheets_writer - sync #' . $sync['id'] . ' (' . $module . ') failed: ' . $e->getMessage(),
                );

                $this->CI->db->update(
                    'google_sheet_syncs',
                    ['last_error' => substr($e->getMessage(), 0, 65000), 'updated_at' => date('Y-m-d H:i:s')],
                    ['id' => $sync['id']],
                );
            }
        }
    }

    /**
     * @param array $sync A google_sheet_syncs row.
     * @param int $record_id
     * @param string $operation 'upsert' | 'delete'.
     *
     * @throws RuntimeException
     */
    protected function run_sync(array $sync, int $record_id, string $operation): void
    {
        $mappings = $this->CI->db
            ->get_where('google_sheet_field_mappings', ['id_google_sheet_syncs' => $sync['id']])
            ->result_array();

        usort($mappings, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        if (empty($mappings)) {
            return;
        }

        $connection = $this->find_connection_for_sync($sync);

        if (!$connection) {
            throw new RuntimeException('Bağlı Google hesabı bulunamadı.');
        }

        $this->CI->google_integrations_client->authenticate_connection($connection);

        $service = $this->CI->google_integrations_client->get_sheets_service();

        $log = $this->CI->db
            ->get_where('google_sheet_sync_log', ['id_google_sheet_syncs' => $sync['id'], 'record_id' => $record_id])
            ->row_array();

        if ($operation === 'delete') {
            $this->write_delete($service, $sync, $log, $record_id);

            return;
        }

        $values = $this->fetch_record_values($sync['module'], $record_id);

        if ($values === null) {
            // Record vanished between the trigger and this write (race with a fast subsequent delete) -
            // nothing to sync.
            return;
        }

        $row = $this->build_row($values, $mappings, $sync['pii_mode']);

        $row_hash = hash('sha256', json_encode($row, JSON_UNESCAPED_UNICODE));

        if ($log && $log['row_hash'] === $row_hash && $log['status'] === 'synced') {
            // Nothing actually changed since the last sync - skip the write entirely.
            return;
        }

        if ($log && $log['row_number'] && $sync['write_mode'] === 'upsert') {
            $this->write_row_at(
                $service,
                $sync['spreadsheet_id'],
                $sync['sheet_title'],
                (int) $log['row_number'],
                $mappings,
                $row,
            );
            $row_number = (int) $log['row_number'];
        } else {
            $row_number = $this->append_row($service, $sync, $mappings, $row);
        }

        $this->update_log($sync['id'], $record_id, $row_number, $row_hash, 'synced', null);

        $this->CI->db->update(
            'google_sheet_syncs',
            ['last_synced_at' => date('Y-m-d H:i:s'), 'last_error' => null, 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $sync['id']],
        );
    }

    /**
     * @param array $sync
     *
     * @return array|null Decrypted google_connections row, or null.
     */
    protected function find_connection_for_sync(array $sync): ?array
    {
        $connection = $this->CI->db->get_where('google_connections', ['id' => $sync['id_google_connections']])->row_array();

        if (!$connection) {
            return null;
        }

        $connection['access_token'] = sf_pii_is_encrypted($connection['access_token'])
            ? sf_pii_decrypt($connection['access_token'])
            : $connection['access_token'];
        $connection['refresh_token'] = sf_pii_is_encrypted($connection['refresh_token'])
            ? sf_pii_decrypt($connection['refresh_token'])
            : $connection['refresh_token'];
        $connection['enabled_services'] = json_decode((string) $connection['enabled_services'], true) ?: [];

        return $connection;
    }

    /**
     * Fetch a single record's field_key => value map (same field keys as Google_sheets_fields::catalog())
     * for one module.
     *
     * @param string $module
     * @param int $record_id
     *
     * @return array<string, mixed>|null Null if the record no longer exists.
     */
    protected function fetch_record_values(string $module, int $record_id): ?array
    {
        return match ($module) {
            'appointments' => $this->fetch_appointment_values($record_id),
            'customers' => $this->fetch_customer_values($record_id),
            'providers' => $this->fetch_provider_values($record_id),
            default => null,
        };
    }

    /**
     * Mirrors the field set Reports::export_csv() builds for one appointment (see field keys in
     * Google_sheets_fields::catalog('appointments')).
     */
    protected function fetch_appointment_values(int $appointment_id): ?array
    {
        $row = $this->CI->db
            ->select(
                'appointments.*, providers.first_name AS provider_first_name, providers.last_name AS provider_last_name, providers.email AS provider_email, providers.commission_type AS provider_commission_type, providers.commission_value AS provider_commission_value, providers.commission_overtime_bonus, customers.first_name AS customer_first_name, customers.last_name AS customer_last_name, customers.id AS customer_id, services.name AS service_name, services.price AS service_price, services.duration AS service_duration, stations.name AS station_name, psc.commission_type AS override_commission_type, psc.commission_value AS override_commission_value',
            )
            ->from('appointments')
            ->join('users AS providers', 'providers.id = appointments.id_users_provider', 'inner')
            ->join('users AS customers', 'customers.id = appointments.id_users_customer', 'left')
            ->join('services', 'services.id = appointments.id_services', 'inner')
            ->join('stations', 'stations.id = appointments.id_stations', 'left')
            ->join(
                'provider_service_commissions AS psc',
                'psc.id_users = providers.id AND psc.id_services = services.id',
                'left',
            )
            ->where('appointments.id', $appointment_id)
            ->get()
            ->row_array();

        if (!$row) {
            return null;
        }

        $billing = $this->CI->appointments_model->compute_effective_billing(
            ['price' => $row['service_price'], 'duration' => $row['service_duration']],
            $row,
        );

        $has_override = $row['override_commission_type'] !== null;
        $commission_type = $has_override ? $row['override_commission_type'] : ($row['provider_commission_type'] ?: 'percentage');
        $commission_value = (float) ($has_override ? $row['override_commission_value'] : $row['provider_commission_value']);

        if ($commission_type === 'fixed') {
            $payout = $commission_value;
        } elseif ($commission_type === 'hourly') {
            $payout = $commission_value * ($billing['minutes'] / 60);
            if ($billing['minutes'] > 60) {
                $payout += (float) $row['commission_overtime_bonus'];
            }
        } else {
            $payout = $billing['price'] * ($commission_value / 100);
        }

        $customer_name = trim(($row['customer_first_name'] ?? '') . ' ' . ($row['customer_last_name'] ?? '')) ?: '-';

        $customer_pii = $row['customer_id'] ? $this->CI->customers_model->find((int) $row['customer_id']) : null;

        $money = fn($value) => $value !== null ? number_format((float) $value, 2, ',', '') : '-';
        $dt = fn($value) => $value ? (new DateTime($value))->format('d.m.Y H:i') : '-';

        $start = new DateTime($row['start_datetime']);
        $end = new DateTime($row['end_datetime']);

        return [
            'appointment_id' => $row['id'],
            'created_at' => $dt($row['create_datetime']),
            'date' => $start->format('d.m.Y'),
            'time' => $start->format('H:i'),
            'end_time' => $end->format('H:i'),
            'actual_start' => $dt($row['actual_start_datetime']),
            'actual_end' => $dt($row['actual_end_datetime']),
            'status' => $row['status'] ?: '-',
            'notes' => $row['notes'] ?: '-',
            'location' => $row['location'] ?: '-',
            'customer_name' => $customer_name,
            'customer_phone' => $customer_pii['phone_number'] ?? '-',
            'customer_email' => $customer_pii['email'] ?? '-',
            'customer_address' => $customer_pii['address'] ?? '-',
            'provider_name' => trim($row['provider_first_name'] . ' ' . $row['provider_last_name']),
            'provider_email' => $row['provider_email'] ?: '-',
            'service_name' => $row['service_name'],
            'service_list_price' => $money($row['service_price']),
            'service_planned_minutes' => $row['service_duration'],
            'effective_minutes' => $billing['minutes'],
            'effective_price' => $money($billing['price']),
            'hourly_rate' => $money($billing['hourly_rate']),
            'payout' => $money($payout),
            'station_name' => $row['station_name'] ?: 'Atanmamış',
            'payment_status' => $row['payment_status'] ?: '-',
            'payment_method' => $row['payment_method'] ?: '-',
            'payment_amount' => $money($row['payment_amount']),
            'payment_balance' => $money($row['payment_balance_amount']),
            'is_invoiced' => $row['is_invoiced'] ? 'Evet' : 'Hayır',
            'deviation_type' => $row['session_deviation_type'] ?: '-',
            'deviation_minutes' => $row['session_deviation_minutes'] ?? '-',
            'early_exit_justification' => $row['early_exit_justification'] === 'justified' ? 'Haklı' : ($row['early_exit_justification'] === 'unjustified' ? 'Haksız' : '-'),
            'early_exit_reason' => $row['early_exit_reason_code'] ? (EARLY_EXIT_REASON_CODES[$row['early_exit_reason_code']] ?? $row['early_exit_reason_code']) : '-',
            'conflict_override' => $row['conflict_override'] ?: '-',
        ];
    }

    protected function fetch_customer_values(int $customer_id): ?array
    {
        try {
            $customer = $this->CI->customers_model->find($customer_id);
        } catch (Throwable $e) {
            return null;
        }

        $social_links = json_decode((string) ($customer['social_links'] ?? ''), true) ?: [];

        return [
            'id' => $customer['id'],
            'first_name' => $customer['first_name'] ?: '-',
            'last_name' => $customer['last_name'] ?: '-',
            'phone_number' => $customer['phone_number'] ?: '-',
            'email' => $customer['email'] ?: '-',
            'address' => $customer['address'] ?: '-',
            'city' => $customer['city'] ?: '-',
            'state' => $customer['state'] ?: '-',
            'whatsapp' => $social_links['whatsapp'] ?? '-',
            'telegram' => $social_links['telegram'] ?? '-',
            'instagram' => $social_links['instagram'] ?? '-',
            'last_contact_channel' => $customer['last_contact_channel'] ?: '-',
            'notes' => $customer['notes'] ?: '-',
            'created_at' => $customer['create_datetime'] ? (new DateTime($customer['create_datetime']))->format('d.m.Y H:i') : '-',
        ];
    }

    protected function fetch_provider_values(int $provider_id): ?array
    {
        try {
            $provider = $this->CI->providers_model->find($provider_id);
        } catch (Throwable $e) {
            return null;
        }

        return [
            'id' => $provider['id'],
            'first_name' => $provider['first_name'] ?: '-',
            'last_name' => $provider['last_name'] ?: '-',
            'phone_number' => $provider['phone_number'] ?: '-',
            'email' => $provider['email'] ?: '-',
            'commission_type' => $provider['commission_type'] ?: '-',
            'commission_value' => $provider['commission_value'] ?? '-',
            'commission_overtime_bonus' => $provider['commission_overtime_bonus'] ?? '-',
            'telegram_linked' => !empty($provider['telegram_chat_id']) ? 'Evet' : 'Hayır',
            'created_at' => $provider['create_datetime'] ? (new DateTime($provider['create_datetime']))->format('d.m.Y H:i') : '-',
        ];
    }

    /**
     * Turn a fetched values map into the ordered list of cell values for one row, honoring pii_mode per
     * field (exclude/encrypted/plaintext - see migration 099 for the KVKK/HIPAA rationale).
     *
     * @param array<string, mixed> $values
     * @param array $mappings Ordered google_sheet_field_mappings rows.
     * @param string $pii_mode 'exclude' | 'encrypted' | 'plaintext'.
     *
     * @return array<int, string> One cell value per mapping, in mapping order.
     */
    protected function build_row(array $values, array $mappings, string $pii_mode): array
    {
        $catalog = Google_sheets_fields::catalog($this->module_of_mappings($mappings));

        $row = [];

        foreach ($mappings as $mapping) {
            $key = $mapping['field_key'];
            $field_meta = $catalog[$key] ?? ['pii' => false];
            $value = $values[$key] ?? '';

            if ($field_meta['pii']) {
                if ($pii_mode === 'exclude') {
                    $value = '';
                } elseif ($pii_mode === 'encrypted') {
                    $value = $value !== '' && $value !== '-' ? sf_pii_encrypt((string) $value) : $value;
                }
                // 'plaintext' - leave $value as-is.
            }

            $row[] = (string) $value;
        }

        return $row;
    }

    /**
     * The module a mapping set belongs to is implied by the sync it came from - callers already know it,
     * but build_row() only receives mappings, so infer it here rather than plumb an extra parameter
     * through every call site. Any field_key from any module works for catalog lookup purposes since keys
     * don't collide meaningfully across modules for the pii flag we need.
     */
    protected function module_of_mappings(array $mappings): string
    {
        foreach (Google_sheets_fields::MODULES as $module => $label) {
            $catalog = Google_sheets_fields::catalog($module);
            if (isset($catalog[$mappings[0]['field_key']])) {
                return $module;
            }
        }

        return 'appointments';
    }

    /**
     * Append a new row at the bottom of the sheet and return its 1-based row number.
     */
    protected function append_row(Google_Service_Sheets $service, array $sync, array $mappings, array $row): int
    {
        $range = $sync['sheet_title'] . '!A:A';

        // Find the next empty row by asking Sheets for the current row count under this sync's header,
        // rather than trusting our own log (which could be stale if someone edited the sheet by hand).
        $existing = $service->spreadsheets_values->get($sync['spreadsheet_id'], $range);
        $next_row = max((int) $sync['header_row'] + 1, count($existing->getValues() ?? []) + 1);

        $this->write_row_at($service, $sync['spreadsheet_id'], $sync['sheet_title'], $next_row, $mappings, $row);

        return $next_row;
    }

    /**
     * Write $row's cells at a specific 1-based row number, each cell placed under its mapped column
     * letter (so mappings don't need to be contiguous columns).
     */
    protected function write_row_at(
        Google_Service_Sheets $service,
        string $spreadsheet_id,
        string $sheet_title,
        int $row_number,
        array $mappings,
        array $row,
    ): void {
        $data = [];

        foreach ($mappings as $index => $mapping) {
            $range = $sheet_title . '!' . $mapping['column_letter'] . $row_number;

            $value_range = new Google_Service_Sheets_ValueRange();
            $value_range->setRange($range);
            $value_range->setValues([[$row[$index] ?? '']]);

            $data[] = $value_range;
        }

        $body = new Google_Service_Sheets_BatchUpdateValuesRequest();
        $body->setValueInputOption('RAW');
        $body->setData($data);

        $this->retry_with_backoff(fn() => $service->spreadsheets_values->batchUpdate($spreadsheet_id, $body));
    }

    /**
     * A deleted record's sheet row is NOT removed (that would shift every row below it and break the
     * dedup log's row_number tracking) - it's marked "[SİLİNDİ]" in its first mapped column instead, so
     * the disaster-recovery mirror still shows the record existed and when it was removed.
     */
    protected function write_delete(Google_Service_Sheets $service, array $sync, ?array $log, int $record_id): void
    {
        if (!$log || !$log['row_number']) {
            // Never synced in the first place - nothing to mark.
            return;
        }

        $mappings = $this->CI->db
            ->get_where('google_sheet_field_mappings', ['id_google_sheet_syncs' => $sync['id']])
            ->result_array();

        if (empty($mappings)) {
            return;
        }

        usort($mappings, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        $first_column = $mappings[0]['column_letter'];
        $range = $sync['sheet_title'] . '!' . $first_column . $log['row_number'];

        $value_range = new Google_Service_Sheets_ValueRange();
        $value_range->setRange($range);
        $value_range->setValues([['[SİLİNDİ]']]);

        $this->retry_with_backoff(
            fn() => $service->spreadsheets_values->update($sync['spreadsheet_id'], $range, $value_range, ['valueInputOption' => 'RAW']),
        );

        $this->update_log($sync['id'], $record_id, (int) $log['row_number'], $log['row_hash'] ?? '', 'deleted', null);
    }

    protected function update_log(
        int $sync_id,
        int $record_id,
        int $row_number,
        string $row_hash,
        string $status,
        ?string $error_message,
    ): void {
        $existing = $this->CI->db
            ->get_where('google_sheet_sync_log', ['id_google_sheet_syncs' => $sync_id, 'record_id' => $record_id])
            ->row_array();

        $data = [
            'row_number' => $row_number,
            'row_hash' => $row_hash,
            'status' => $status,
            'error_message' => $error_message,
            'synced_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->CI->db->update('google_sheet_sync_log', $data, ['id' => $existing['id']]);
        } else {
            $data['id_google_sheet_syncs'] = $sync_id;
            $data['record_id'] = $record_id;
            $this->CI->db->insert('google_sheet_sync_log', $data);
        }
    }

    /**
     * Exponential backoff for Sheets API 429 (rate limit) / 403 (quota) responses - a burst of several
     * appointment saves in a row (e.g. a bulk import) must not fail outright just because Sheets
     * momentarily throttles.
     */
    protected function retry_with_backoff(callable $call, int $max_attempts = 4): mixed
    {
        $attempt = 0;

        while (true) {
            try {
                return $call();
            } catch (Google_Service_Exception $e) {
                $attempt++;

                if ($attempt >= $max_attempts || !in_array($e->getCode(), [429, 403, 500, 503], true)) {
                    throw $e;
                }

                // 429 is a PER-MINUTE quota (see Google_sheets_writer::backfill() docblock) - a
                // sub-second backoff never actually clears it, so wait several seconds instead of the
                // short exponential delay used for transient 5xx errors.
                usleep($e->getCode() === 429 ? (int) (5000000 * $attempt) : (int) (200000 * 2 ** $attempt));
            }
        }
    }

    /**
     * Read a spreadsheet's tabs (title + sheetId), for the mapping wizard's "hangi sayfa" step.
     *
     * @param array $connection Result of Google_integrations_client::get_connection().
     * @param string $spreadsheet_id
     *
     * @return array<int, array{title: string, sheetId: int}>
     */
    public function list_sheet_tabs(array $connection, string $spreadsheet_id): array
    {
        $this->CI->google_integrations_client->authenticate_connection($connection);

        $service = $this->CI->google_integrations_client->get_sheets_service();

        $spreadsheet = $service->spreadsheets->get($spreadsheet_id);

        return array_map(
            fn($sheet) => [
                'title' => $sheet->getProperties()->getTitle(),
                'sheetId' => $sheet->getProperties()->getSheetId(),
            ],
            $spreadsheet->getSheets(),
        );
    }

    /**
     * Read a sheet tab's existing header row (if any), for the mapping wizard's "hangi sütuna hangi bilgi
     * gelecek" step - lets the UI pre-fill/detect an already-existing header rather than starting blank.
     *
     * @return array<string, string> column_letter => header text.
     */
    public function read_header_row(array $connection, string $spreadsheet_id, string $sheet_title, int $header_row): array
    {
        $this->CI->google_integrations_client->authenticate_connection($connection);

        $service = $this->CI->google_integrations_client->get_sheets_service();

        $range = $sheet_title . '!' . $header_row . ':' . $header_row;

        $result = $service->spreadsheets_values->get($spreadsheet_id, $range);
        $values = $result->getValues();
        $headers = $values[0] ?? [];

        $out = [];

        foreach ($headers as $index => $text) {
            if ($text === '') {
                continue;
            }

            $out[$this->column_letter($index)] = $text;
        }

        return $out;
    }

    /**
     * Write the header row for a newly configured sync, and return the mappings written (so the caller
     * can persist them).
     *
     * @param array<int, array{field_key: string, column_letter: string}> $field_columns Ordered.
     */
    public function write_header_row(
        array $connection,
        string $spreadsheet_id,
        string $sheet_title,
        int $header_row,
        array $field_columns,
        array $catalog,
    ): void {
        $this->CI->google_integrations_client->authenticate_connection($connection);

        $service = $this->CI->google_integrations_client->get_sheets_service();

        $data = [];

        foreach ($field_columns as $field_column) {
            $label = $catalog[$field_column['field_key']]['label'] ?? $field_column['field_key'];
            $range = $sheet_title . '!' . $field_column['column_letter'] . $header_row;

            $value_range = new Google_Service_Sheets_ValueRange();
            $value_range->setRange($range);
            $value_range->setValues([[$label]]);

            $data[] = $value_range;
        }

        $body = new Google_Service_Sheets_BatchUpdateValuesRequest();
        $body->setValueInputOption('RAW');
        $body->setData($data);

        $this->retry_with_backoff(fn() => $service->spreadsheets_values->batchUpdate($spreadsheet_id, $body));
    }

    /**
     * Zero-based column index -> spreadsheet column letter (0 => A, 25 => Z, 26 => AA, ...).
     */
    public static function column_letter(int $index): string
    {
        $letter = '';

        $index++;

        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }

    /**
     * Manually re-sync every existing record of a module through one sync pipe - used by the "Şimdi
     * Senkronize Et" button after a sync is first configured (so a freshly connected sheet isn't empty
     * until the next organic change), and by the disaster-recovery "backfill" action.
     *
     * @param int $sync_id
     *
     * @return array{synced: int, failed: int}
     */
    public function backfill(int $sync_id): array
    {
        $sync = $this->CI->db->get_where('google_sheet_syncs', ['id' => $sync_id])->row_array();

        if (!$sync) {
            throw new InvalidArgumentException('Senkronizasyon bulunamadı.');
        }

        if (!in_array($sync['module'], ['appointments', 'customers', 'providers'], true)) {
            throw new InvalidArgumentException('Geçersiz modül.');
        }

        $mappings = $this->CI->db
            ->get_where('google_sheet_field_mappings', ['id_google_sheet_syncs' => $sync['id']])
            ->result_array();

        usort($mappings, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        if (empty($mappings)) {
            return ['synced' => 0, 'failed' => 0];
        }

        if ($sync['module'] === 'appointments') {
            $ids = $this->CI->db->select('id')->from('appointments')->where('is_unavailability', false)->get()->result_array();
        } else {
            $role_slug = $sync['module'] === 'customers' ? DB_SLUG_CUSTOMER : DB_SLUG_PROVIDER;
            $this->CI->db->select('users.id')->from('users')->join('roles', 'roles.id = users.id_roles')->where('roles.slug', $role_slug);
            $ids = $this->CI->db->get()->result_array();
        }

        // Salon Flora customization (2026-08-25) - build every row's values in memory first (pure DB
        // reads, no Sheets API calls, no rate limit exposure), THEN issue ONE values.get() to find the
        // next empty row and a handful of chunked batchUpdate() calls to write them all - a naive "one
        // API call per record" backfill of even a moderate appointment history blows through Sheets'
        // 60-writes/minute/user quota (confirmed live: a 64-appointment backfill hit 429
        // RESOURCE_EXHAUSTED after ~60 individual row writes).
        $connection = $this->find_connection_for_sync($sync);

        if (!$connection) {
            throw new RuntimeException('Bağlı Google hesabı bulunamadı.');
        }

        $this->CI->google_integrations_client->authenticate_connection($connection);

        $service = $this->CI->google_integrations_client->get_sheets_service();

        $existing_logs = $this->CI->db
            ->get_where('google_sheet_sync_log', ['id_google_sheet_syncs' => $sync['id']])
            ->result_array();

        $log_by_record = [];
        foreach ($existing_logs as $log) {
            $log_by_record[(int) $log['record_id']] = $log;
        }

        $range = $sync['sheet_title'] . '!A:A';
        $existing = $service->spreadsheets_values->get($sync['spreadsheet_id'], $range);
        $next_row = max((int) $sync['header_row'] + 1, count($existing->getValues() ?? []) + 1);

        $failed = 0;
        $pending_log_updates = [];
        $value_ranges = [];

        foreach ($ids as $id_row) {
            $record_id = (int) $id_row['id'];

            try {
                $values = $this->fetch_record_values($sync['module'], $record_id);

                if ($values === null) {
                    continue;
                }

                $row = $this->build_row($values, $mappings, $sync['pii_mode']);
                $row_hash = hash('sha256', json_encode($row, JSON_UNESCAPED_UNICODE));

                $existing_log = $log_by_record[$record_id] ?? null;

                if ($existing_log && $existing_log['row_hash'] === $row_hash && $existing_log['status'] === 'synced') {
                    continue;
                }

                if ($existing_log && $existing_log['row_number'] && $sync['write_mode'] === 'upsert') {
                    $row_number = (int) $existing_log['row_number'];
                } else {
                    $row_number = $next_row;
                    $next_row++;
                }

                foreach ($mappings as $index => $mapping) {
                    $value_range = new Google_Service_Sheets_ValueRange();
                    $value_range->setRange($sync['sheet_title'] . '!' . $mapping['column_letter'] . $row_number);
                    $value_range->setValues([[$row[$index] ?? '']]);
                    $value_ranges[] = $value_range;
                }

                $pending_log_updates[] = [
                    'record_id' => $record_id,
                    'row_number' => $row_number,
                    'row_hash' => $row_hash,
                ];
            } catch (Throwable $e) {
                $failed++;
                log_message('error', 'Google_sheets_writer::backfill - record ' . $record_id . ' failed: ' . $e->getMessage());
            }
        }

        // Sheets caps a single batchUpdate at 10,000,000 cells - chunk conservatively by request COUNT
        // (not cells) to also stay well clear of the per-minute write-request quota that bit us above;
        // each chunk is one write request regardless of how many cells it carries.
        foreach (array_chunk($value_ranges, 2000) as $chunk) {
            $body = new Google_Service_Sheets_BatchUpdateValuesRequest();
            $body->setValueInputOption('RAW');
            $body->setData($chunk);

            $this->retry_with_backoff(fn() => $service->spreadsheets_values->batchUpdate($sync['spreadsheet_id'], $body));
        }

        foreach ($pending_log_updates as $update) {
            $this->update_log($sync['id'], $update['record_id'], $update['row_number'], $update['row_hash'], 'synced', null);
        }

        $this->CI->db->update(
            'google_sheet_syncs',
            ['last_synced_at' => date('Y-m-d H:i:s'), 'last_error' => null, 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $sync['id']],
        );

        return ['synced' => count($pending_log_updates), 'failed' => $failed];
    }
}
