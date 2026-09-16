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

if (!defined('CRM_ACTIONS')) {
    define('CRM_ACTIONS', [
        'customer.created',
        'customer.updated',
        'appointment.created',
        'appointment.updated',
        'appointment.cancelled',
    ]);
}

/**
 * BooKi (2026-09-16) - Zoho CRM integration, parts 2/3.
 *
 * Two responsibilities:
 *
 * 1. enqueue() - the SYNC write side. Called ONLY by the booking models (Appointments_model,
 *    Customers_model) right after a customer/appointment row lands. It inserts a tiny PII-free
 *    pointer (action + local ids) into the tenant's `crm_outbox`. Never throws, never touches the
 *    network, and is a no-op on every deployment that has not run migration 138 yet.
 *
 * 2. run() - the SYNC read/delivery side, driven by the `console crm_sync` command. For every
 *    active tenant (or one given tenant) it drains the pending outbox rows, re-joins the local
 *    customer/appointment/service/provider rows, upserts Contacts + Deals into Zoho CRM (OAuth2
 *    refresh-token flow so it works headless in cron/CLI), and records the resulting Zoho record
 *    ids in `crm_id_map` so later updated/cancelled events patch the SAME deal.
 *
 * All Zoho credentials/behaviour live in master_settings (read via master_setting()):
 *   crm_sync_enabled          "1" / "0"
 *   zoho_region               eu (default) | us | in | au | jp | uk | com
 *   zoho_client_id
 *   zoho_client_secret
 *   zoho_refresh_token
 *   zoho_contacts_module      Contacts  (module receiving customers)
 *   zoho_appointments_module  Deals     (module receiving appointments)
 *   zoho_deal_stage_new       Qualification (stage for fresh appointments)
 *   zoho_deal_stage_cancelled Lost          (stage for cancelled appointments)
 *   zoho_contact_lookup_field (optional custom Contact lookup field on the appointments module,
 *                              e.g. "Contact_Name" - links each deal to its contact)
 *
 * Nothing happens until crm_sync_enabled=1 AND the three OAuth credentials are set; until then the
 * outbox simply accumulates and run() reports "not configured".
 *
 * @see migration 138_create_crm_tables.php
 */
class Crm_sync
{
    protected CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Queue a CRM event for the current tenant. Idempotent, PII-free and fail-safe: a missing
     * outbox table (migration not yet applied) or any DB hiccup silently disables the queue, it
     * must never break a booking.
     */
    public function enqueue(string $action, ?int $customer_id = null, ?int $appointment_id = null): void
    {
        // Gate behind the platform-wide master flag: a single-tenant deployment (no master_settings
        // table at all) or a master that has not activated CRM sync never accumulates an outbox the
        // `console crm_sync` worker would not drain. Read once per process, not per booking.
        static $enabled = null;

        if ($enabled === null) {
            $enabled = master_setting('crm_sync_enabled') === '1';
        }

        if (!$enabled || !in_array($action, CRM_ACTIONS, true) || !$this->outbox_available()) {
            return;
        }

        try {
            $this->CI->db->insert('crm_outbox', [
                'action' => $action,
                'customer_id' => $customer_id !== null ? (int) $customer_id : null,
                'appointment_id' => $appointment_id !== null ? (int) $appointment_id : null,
                'status' => 'pending',
                'attempts' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Crm_sync - enqueue failed: ' . $e->getMessage());
        }
    }

    protected function outbox_available(): bool
    {
        static $available = null;

        if ($available === null) {
            try {
                $available = (bool) $this->CI->db->table_exists('crm_outbox');
            } catch (Throwable $e) {
                $available = false;
            }
        }

        return $available;
    }

    /**
     * Drain pending CRM events. Returns a per-tenant report.
     */
    public function run(?string $subdomain = null, bool $dry_run = false): array
    {
        $config = $this->zoho_config();

        if (!$config['enabled']) {
            return [
                'configured' => false,
                'tenants' => [],
                'message' => 'CRM sync disengaged: set crm_sync_enabled=1 on the master to activate.',
            ];
        }

        if (
            $config['client_id'] === '' ||
            $config['client_secret'] === '' ||
            $config['refresh_token'] === ''
        ) {
            return [
                'configured' => false,
                'tenants' => [],
                'message' => 'Zoho credentials are missing '
                    . '(zoho_client_id / zoho_client_secret / zoho_refresh_token).',
            ];
        }

        $report = ['configured' => true, 'tenants' => []];

        foreach ($this->load_tenants($subdomain) as $tenant) {
            $report['tenants'][$tenant['subdomain']] = $this->process_tenant($tenant, $config, $dry_run);
        }

        return $report;
    }

    protected function load_tenants(?string $subdomain): array
    {
        $master = $this->CI->load->database('default', true);

        if (!$master->table_exists('tenants')) {
            return [];
        }

        if ($subdomain !== null && $subdomain !== '') {
            $row = $master->get_where('tenants', ['subdomain' => $subdomain, 'status' => 'active'])->row_array();

            return $row ? [$row] : [];
        }

        return $master->get_where('tenants', ['status' => 'active'])->result_array();
    }

    protected function process_tenant(array $tenant, array $config, bool $dry_run): array
    {
        $subdomain = $tenant['subdomain'];
        $report = ['processed' => 0, 'sent' => 0, 'failed' => 0, 'errors' => []];

        try {
            $this->connect_tenant_db($tenant);

            $rows = $this->CI->db
                ->where('status', 'pending')
                ->get('crm_outbox')
                ->result_array();

            if (empty($rows)) {
                return $report;
            }

            $token = $dry_run ? '' : $this->access_token($config);

            foreach ($rows as $row) {
                $report['processed']++;

                try {
                    $this->process_row($row, $config, $token, $dry_run);
                    $report['sent']++;

                    // A dry-run only builds/prints payloads - it must NOT consume the queue.
                    if (!$dry_run) {
                        $this->CI->db->where('id', (int) $row['id'])->update('crm_outbox', [
                            'status' => 'sent',
                            'error' => null,
                            'synced_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                } catch (Throwable $e) {
                    $report['failed']++;
                    $report['errors'][] = 'outbox#' . $row['id'] . ' (' . $row['action'] . '): ' . $e->getMessage();

                    $attempts = (int) ($row['attempts'] ?? 0) + 1;

                    // Dry-runs may hit code bugs before the real integration - failing them should not
                    // burn the row's real attempt budget either.
                    if (!$dry_run) {
                        $this->CI->db->where('id', (int) $row['id'])->update('crm_outbox', [
                            'attempts' => $attempts,
                            'error' => mb_substr($e->getMessage(), 0, 1000),
                            'status' => $attempts >= 5 ? 'failed' : 'pending',
                        ]);
                    }
                }
            }
        } catch (Throwable $e) {
            $report['failed']++;
            $report['errors'][] = 'tenant: ' . $e->getMessage();
        }

        return $report;
    }

    protected function process_row(array $row, array $config, string $token, bool $dry_run): void
    {
        switch ($row['action']) {
            case 'customer.created':
            case 'customer.updated':
                $this->sync_customer((int) $row['customer_id'], $config, $token, $dry_run);

                break;

            case 'appointment.created':
                $this->sync_appointment((int) $row['appointment_id'], $config, $token, $dry_run, false);

                break;

            case 'appointment.updated':
                $this->sync_appointment((int) $row['appointment_id'], $config, $token, $dry_run, true);

                break;

            case 'appointment.cancelled':
                $this->cancel_appointment((int) $row['appointment_id'], $config, $token, $dry_run);

                break;
        }
    }

    protected function sync_customer(int $customer_id, array $config, string $token, bool $dry_run): string
    {
        $customer = $this->CI->db->get_where('users', ['id' => $customer_id])->row_array();

        if (empty($customer)) {
            return '';
        }

        // First/last name are stored plaintext (searchable); email/phone are SFENC1-encrypted.
        $record = [];

        if (!empty($customer['first_name'])) {
            $record['First_Name'] = $customer['first_name'];
        }

        if (!empty($customer['last_name'])) {
            $record['Last_Name'] = $customer['last_name'];
        }

        if (sf_pii_is_encrypted($customer['email'] ?? null)) {
            $email = sf_pii_decrypt($customer['email']);

            if ($email) {
                $record['Email'] = $email;
            }
        } elseif (!empty($customer['email'])) {
            $record['Email'] = $customer['email'];
        }

        if (sf_pii_is_encrypted($customer['phone_number'] ?? null)) {
            $phone = sf_pii_decrypt($customer['phone_number']);

            if ($phone) {
                $record['Phone'] = $phone;
            }
        } elseif (!empty($customer['phone_number'])) {
            $record['Phone'] = $customer['phone_number'];
        }

        if (empty($record)) {
            return '';
        }

        $module = $config['contacts_module'];

        if ($dry_run) {
            echo '  [dry-run] ' . $module . ' upsert: ' . json_encode($record, JSON_UNESCAPED_UNICODE) . PHP_EOL;

            return '';
        }

        $body = ['data' => [$record], 'trigger' => []];

        if (!empty($record['Email'])) {
            $body['duplicate_check_fields'] = ['Email'];
        }

        $response = $this->zoho_request('POST', $this->api_url($config, "crm/v8/$module/upsert"), $body, $token);

        return (string) ($response['id'] ?? '');
    }

    protected function sync_appointment(int $appointment_id, array $config, string $token, bool $dry_run, bool $is_update): void
    {
        $appointment = $this->CI->db->get_where('appointments', ['id' => $appointment_id])->row_array();

        if (empty($appointment)) {
            return;
        }

        $service = $this->CI->db->get_where('services', ['id' => $appointment['id_services']])->row_array();
        $provider = $this->CI->db->get_where('users', ['id' => $appointment['id_users_provider']])->row_array();

        $customer_name = $this->user_name($appointment['id_users_customer']);
        $service_name = $service['name'] ?? '';
        $provider_name = $provider['first_name'] . ' ' . $provider['last_name'];

        $module = $config['appointments_module'];

        $deal = [];

        if ($customer_name !== '') {
            $deal['Deal_Name'] = trim($customer_name . ($service_name !== '' ? " - $service_name" : ''));
        } elseif ($service_name !== '') {
            $deal['Deal_Name'] = $service_name;
        }

        $price = (float) ($service['price'] ?? 0);

        if ($price > 0) {
            $deal['Amount'] = $price;
        }

        if (!empty($appointment['end_datetime'])) {
            $deal['Closing_Date'] = date('Y-m-d', strtotime((string) $appointment['end_datetime']));
        }

        $deal['Stage'] = $config['deal_stage_new'];

        $lines = [];

        if (!empty($appointment['start_datetime'])) {
            $lines[] = 'Randevu: ' . $appointment['start_datetime'];
        }

        if ($provider_name !== '') {
            $lines[] = 'Personel: ' . $provider_name;
        }

        if (!empty($appointment['location'])) {
            $lines[] = 'Konum: ' . $appointment['location'];
        }

        if (!empty($appointment['notes'])) {
            $lines[] = 'Notlar: ' . $appointment['notes'];
        }

        if (!empty($appointment['hash'])) {
            $lines[] = 'Yönetim: ' . $this->manage_url($appointment['hash']);
        }

        if (!empty($lines)) {
            $deal['Description'] = implode(PHP_EOL, $lines);
        }

        if (empty($deal['Deal_Name']) && empty($deal['Amount']) && empty($deal['Description'])) {
            return;
        }

        $existing_id = $this->mapped_zoho_id('appointment', $appointment_id, $module);

        if ($existing_id === '') {
            if (!$is_update && !empty($config['contact_lookup_field'])) {
                $contact_zoho_id = $this->sync_customer((int) $appointment['id_users_customer'], $config, $token, $dry_run);

                if ($contact_zoho_id !== '') {
                    $deal[$config['contact_lookup_field']] = $contact_zoho_id;
                }
            }

            if ($dry_run) {
                echo '  [dry-run] ' . $module . ' create: '
                    . json_encode($deal, JSON_UNESCAPED_UNICODE) . PHP_EOL;

                return;
            }

            $response = $this->zoho_request(
                'POST',
                $this->api_url($config, "crm/v8/$module"),
                ['data' => [$deal], 'trigger' => []],
                $token,
            );

            $zoho_id = (string) ($response['id'] ?? '');

            if ($zoho_id !== '') {
                $this->set_mapped_zoho_id('appointment', $appointment_id, $module, $zoho_id);
            }

            return;
        }

        if ($dry_run) {
            echo '  [dry-run] ' . $module . ' update #' . $existing_id . ': '
                . json_encode($deal, JSON_UNESCAPED_UNICODE) . PHP_EOL;

            return;
        }

        $deal['id'] = $existing_id;

        $this->zoho_request(
            'PUT',
            $this->api_url($config, "crm/v8/$module"),
            ['data' => [$deal], 'trigger' => []],
            $token,
        );
    }

    protected function cancel_appointment(int $appointment_id, array $config, string $token, bool $dry_run): void
    {
        $module = $config['appointments_module'];
        $existing_id = $this->mapped_zoho_id('appointment', $appointment_id, $module);

        if ($existing_id === '') {
            return;
        }

        $update = ['id' => $existing_id, 'Stage' => $config['deal_stage_cancelled']];

        if ($dry_run) {
            echo '  [dry-run] ' . $module . ' cancel/update #' . $existing_id . ': '
                . json_encode($update, JSON_UNESCAPED_UNICODE) . PHP_EOL;

            return;
        }

        $this->zoho_request(
            'PUT',
            $this->api_url($config, "crm/v8/$module"),
            ['data' => [$update], 'trigger' => []],
            $token,
        );
    }

    protected function mapped_zoho_id(string $local_type, int $local_id, string $module): string
    {
        $row = $this->CI->db
            ->get_where('crm_id_map', ['local_type' => $local_type, 'local_id' => $local_id, 'zoho_module' => $module])
            ->row_array();

        return (string) ($row['zoho_id'] ?? '');
    }

    protected function set_mapped_zoho_id(string $local_type, int $local_id, string $module, string $zoho_id): void
    {
        $exists = $this->CI->db
            ->where(['local_type' => $local_type, 'local_id' => $local_id, 'zoho_module' => $module])
            ->get('crm_id_map')
            ->num_rows();

        if ($exists > 0) {
            $this->CI->db
                ->where(['local_type' => $local_type, 'local_id' => $local_id, 'zoho_module' => $module])
                ->update('crm_id_map', ['zoho_id' => $zoho_id, 'synced_at' => date('Y-m-d H:i:s')]);

            return;
        }

        $this->CI->db->insert('crm_id_map', [
            'local_type' => $local_type,
            'local_id' => $local_id,
            'zoho_module' => $module,
            'zoho_id' => $zoho_id,
            'synced_at' => date('Y-m-d H:i:s'),
        ]);
    }

    protected function user_name(int $user_id): string
    {
        $user = $this->CI->db->get_where('users', ['id' => $user_id])->row_array();

        if (empty($user)) {
            return '';
        }

        return trim(trim((string) $user['first_name']) . ' ' . trim((string) $user['last_name']));
    }

    protected function manage_url(string $hash): string
    {
        if (function_exists('base_url')) {
            return base_url('booking/reschedule/' . $hash);
        }

        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        return $proto . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/booking/reschedule/' . $hash;
    }

    protected function zoho_config(): array
    {
        return [
            'enabled' => master_setting('crm_sync_enabled') === '1',
            'region' => master_setting('zoho_region') ?: 'eu',
            'client_id' => (string) master_setting('zoho_client_id'),
            'client_secret' => (string) master_setting('zoho_client_secret'),
            'refresh_token' => (string) master_setting('zoho_refresh_token'),
            'contacts_module' => master_setting('zoho_contacts_module') ?: 'Contacts',
            'appointments_module' => master_setting('zoho_appointments_module') ?: 'Deals',
            'deal_stage_new' => master_setting('zoho_deal_stage_new') ?: 'Qualification',
            'deal_stage_cancelled' => master_setting('zoho_deal_stage_cancelled') ?: 'Lost',
            'contact_lookup_field' => (string) master_setting('zoho_contact_lookup_field'),
        ];
    }

    protected function access_token(array $config): string
    {
        $host = $this->account_host($config['region']);

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
            throw new RuntimeException('Zoho token request failed: ' . $error);
        }

        $parsed = json_decode((string) $raw, true);

        if (empty($parsed['access_token'])) {
            throw new RuntimeException(
                'Zoho token response (HTTP ' . $http_code . '): ' . mb_substr((string) $raw, 0, 600),
            );
        }

        return (string) $parsed['access_token'];
    }

    protected function zoho_request(string $method, string $url, array $body, string $token): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Zoho-oauthtoken ' . $token,
                'Content-Type: application/json;charset=UTF-8',
            ],
            CURLOPT_POSTFIELDS => (string) json_encode($body),
            CURLOPT_TIMEOUT => 45,
        ]);

        $raw = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('Zoho CRM request failed: ' . $error);
        }

        $parsed = json_decode((string) $raw, true);

        $record = $parsed['data'][0] ?? [];

        if ($http_code >= 400 || ($record['status'] ?? '') === 'error') {
            throw new RuntimeException(
                'Zoho CRM ' . $method . ' ' . $url . ' (HTTP ' . $http_code . '): '
                . mb_substr((string) $raw, 0, 700),
            );
        }

        return [
            'id' => $record['details']['id'] ?? null,
            'code' => $record['code'] ?? '',
            'raw' => $parsed,
        ];
    }

    protected function api_url(array $config, string $path): string
    {
        return $this->api_host($config['region']) . '/' . $path;
    }

    protected function account_host(string $region): string
    {
        $hosts = [
            'us' => 'https://accounts.zoho.com',
            'com' => 'https://accounts.zoho.com',
            'in' => 'https://accounts.zoho.in',
            'au' => 'https://accounts.zoho.com.au',
            'eu' => 'https://accounts.zoho.eu',
            'uk' => 'https://accounts.zoho.eu',
            'jp' => 'https://accounts.zoho.jp',
        ];

        return $hosts[$region] ?? 'https://accounts.zoho.com';
    }

    protected function api_host(string $region): string
    {
        $hosts = [
            'us' => 'https://www.zohoapis.com',
            'com' => 'https://www.zohoapis.com',
            'in' => 'https://www.zohoapis.in',
            'au' => 'https://www.zohoapis.com.au',
            'eu' => 'https://www.zohoapis.eu',
            'uk' => 'https://www.zohoapis.eu',
            'jp' => 'https://www.zohoapis.jp',
        ];

        return $hosts[$region] ?? 'https://www.zohoapis.com';
    }

    /**
     * Connect to a tenant's own database (mirror of Console::connect_tenant()). Uses the
     * non-replacing load->database(...) form so the worker's master/default connection is untouched.
     */
    protected function connect_tenant_db(array $tenant): void
    {
        $this->CI->load->database(
            [
                'hostname' => $tenant['db_host'] ?? '',
                'username' => $tenant['db_username'] ?? '',
                'password' => tenant_master_decrypt((string) ($tenant['db_password'] ?? '')),
                'database' => $tenant['db_name'] ?? '',
                'dbdriver' => 'mysqli',
                'dbprefix' => 'ea_',
                'pconnect' => false,
                'db_debug' => true,
                'cache_on' => false,
                'cachedir' => '',
                'char_set' => 'utf8mb4',
                'dbcollat' => 'utf8mb4_unicode_ci',
                'swap_pre' => '',
            ],
            false,
            true,
        );

        tenant_context([
            'id' => (int) ($tenant['id'] ?? 0),
            'subdomain' => (string) ($tenant['subdomain'] ?? ''),
            'pii_enc_key' => tenant_master_decrypt((string) ($tenant['pii_enc_key'] ?? '')),
            'pii_hash_key' => tenant_master_decrypt((string) ($tenant['pii_hash_key'] ?? '')),
        ]);
    }
}