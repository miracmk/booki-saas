<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * SaaS admin panel (reservationadmin.kibusiness.co) - tenant CRUD + plan/license tracking. Runs
 * against the master DB (see EA_Controller::resolve_tenant()'s superadmin host exception). Tenant
 * provisioning here mirrors Console::tenant_create() exactly (same DB-swap dance, same
 * Instance::migrate()/seed() call) - duplicated rather than shared because Console's version is
 * CLI-only (private connect_tenant()/connect_master() helpers, echo-based output) and this is a web
 * JSON endpoint; keep the two in sync if the provisioning steps ever change.
 */
class Superadmin_tenants extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!session('superadmin_id')) {
            redirect('superadmin_auth');
            exit();
        }

        $this->load->library('instance');
    }

    public function index(): void
    {
        method('get');

        $tenants = $this->db->order_by('created_at', 'desc')->get('tenants')->result_array();

        foreach ($tenants as &$tenant) {
            $tenant['appointment_count'] = $this->count_tenant_appointments($tenant);
        }

        unset($tenant);

        html_vars([
            'page_title' => 'Ki Reservation - Kiracılar',
            'csrf_token' => $this->security->get_csrf_hash(),
            'superadmin_username' => session('superadmin_username'),
            'tenants' => $tenants,
        ]);

        $this->load->view('pages/superadmin_tenants');
    }

    public function store(): void
    {
        try {
            method('post');

            check('subdomain', 'string');
            check('custom_domain', 'string|null');
            check('plan', 'string|null');
            check('trial_days', 'numeric|null');

            $subdomain = strtolower(trim((string) request('subdomain')));
            $custom_domain = trim((string) request('custom_domain'));
            $plan = trim((string) request('plan')) ?: null;
            $trial_days = request('trial_days') ? (int) request('trial_days') : null;

            if ($subdomain === '' || !preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $subdomain)) {
                throw new InvalidArgumentException('Geçerli bir subdomain girin (harf/rakam/tire, tek DNS etiketi).');
            }

            if ($this->db->get_where('tenants', ['subdomain' => $subdomain])->num_rows() > 0) {
                throw new InvalidArgumentException('"' . $subdomain . '" subdomain\'i zaten kullanımda.');
            }

            $db_host = $this->db->hostname;
            $db_username = $this->db->username;
            $db_password_plain = $this->db->password;
            $db_name = 'ki_tenant_' . $subdomain;

            $this->db->query(
                'CREATE DATABASE IF NOT EXISTS `' . $db_name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            );

            $pii_enc_key = base64_encode(random_bytes(32));
            $pii_hash_key = base64_encode(random_bytes(32));
            $now = date('Y-m-d H:i:s');

            $this->db->insert('tenants', [
                'subdomain' => $subdomain,
                'custom_domain' => $custom_domain !== '' ? $custom_domain : null,
                'db_host' => $db_host,
                'db_name' => $db_name,
                'db_username' => $db_username,
                'db_password' => tenant_master_encrypt($db_password_plain),
                'pii_enc_key' => tenant_master_encrypt($pii_enc_key),
                'pii_hash_key' => tenant_master_encrypt($pii_hash_key),
                'status' => 'active',
                'plan' => $plan,
                'trial_ends_at' => $trial_days ? date('Y-m-d H:i:s', strtotime("+{$trial_days} days")) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $tenant_id = $this->db->insert_id();

            $this->connect_tenant_db([
                'id' => $tenant_id,
                'subdomain' => $subdomain,
                'db_host' => $db_host,
                'db_username' => $db_username,
                'db_password' => tenant_master_encrypt($db_password_plain),
                'db_name' => $db_name,
                'pii_enc_key' => tenant_master_encrypt($pii_enc_key),
                'pii_hash_key' => tenant_master_encrypt($pii_hash_key),
            ]);

            $this->instance->migrate('fresh');
            $admin_password = $this->instance->seed();

            $this->connect_master_db();

            json_response([
                'success' => true,
                'subdomain' => $subdomain,
                'login_url' => 'https://' . $subdomain . '-reservationapp.kibusiness.co/',
                'admin_password' => $admin_password,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function update_status(): void
    {
        try {
            method('post');

            check('tenant_id', 'numeric');
            check('status', 'string');

            $status = request('status');

            if (!in_array($status, ['active', 'suspended'], true)) {
                throw new InvalidArgumentException('Geçersiz durum.');
            }

            $this->db->update(
                'tenants',
                [
                    'status' => $status,
                    'suspended_at' => $status === 'suspended' ? date('Y-m-d H:i:s') : null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                ['id' => (int) request('tenant_id')],
            );

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function update_plan(): void
    {
        try {
            method('post');

            check('tenant_id', 'numeric');
            check('plan', 'string|null');
            check('license_expires_at', 'string|null');
            check('trial_ends_at', 'string|null');

            $this->db->update(
                'tenants',
                [
                    'plan' => request('plan') ?: null,
                    'license_expires_at' => request('license_expires_at') ?: null,
                    'trial_ends_at' => request('trial_ends_at') ?: null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                ['id' => (int) request('tenant_id')],
            );

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Permanently deletes a tenant's database AND its master row - irreversible. Requires the
     * subdomain to be typed back exactly, matching the confirmation pattern the frontend enforces.
     */
    public function destroy(): void
    {
        try {
            method('post');

            check('tenant_id', 'numeric');
            check('confirm_subdomain', 'string');

            $tenant = $this->db->get_where('tenants', ['id' => (int) request('tenant_id')])->row_array();

            if (!$tenant) {
                throw new InvalidArgumentException('Kiracı bulunamadı.');
            }

            if (request('confirm_subdomain') !== $tenant['subdomain']) {
                throw new InvalidArgumentException('Onay metni subdomain ile eşleşmiyor.');
            }

            $this->db->query('DROP DATABASE IF EXISTS `' . $tenant['db_name'] . '`');
            $this->db->delete('tenants', ['id' => $tenant['id']]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Mirrors Console::connect_tenant() - see that method's docblock for the dbforge/migrations-table
     * caveats this replicates.
     */
    private function connect_tenant_db(array $tenant): void
    {
        $this->load->database(
            [
                'hostname' => $tenant['db_host'],
                'username' => $tenant['db_username'],
                'password' => tenant_master_decrypt($tenant['db_password']),
                'database' => $tenant['db_name'],
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
            'subdomain' => $tenant['subdomain'] ?? '',
            'pii_enc_key' => tenant_master_decrypt($tenant['pii_enc_key']),
            'pii_hash_key' => tenant_master_decrypt($tenant['pii_hash_key']),
        ]);

        $this->load->dbforge();

        if (!$this->db->table_exists('migrations')) {
            $this->dbforge->add_field(['version' => ['type' => 'BIGINT', 'constraint' => 20]]);
            $this->dbforge->create_table('migrations', true);
            $this->db->insert('migrations', ['version' => 0]);
        }
    }

    private function connect_master_db(): void
    {
        $this->load->database('default', false, true);
        $this->load->dbforge();
        tenant_context_clear();
    }

    /**
     * Best-effort appointment count for the tenant list - swallows connection errors (e.g. a tenant
     * whose DB got manually removed) so one broken row doesn't take down the whole dashboard.
     */
    private function count_tenant_appointments(array $tenant): ?int
    {
        try {
            $tenant_db = $this->load->database(
                [
                    'hostname' => $tenant['db_host'],
                    'username' => $tenant['db_username'],
                    'password' => tenant_master_decrypt($tenant['db_password']),
                    'database' => $tenant['db_name'],
                    'dbdriver' => 'mysqli',
                    'dbprefix' => 'ea_',
                    'pconnect' => false,
                    'db_debug' => false,
                    'cache_on' => false,
                    'cachedir' => '',
                    'char_set' => 'utf8mb4',
                    'dbcollat' => 'utf8mb4_unicode_ci',
                    'swap_pre' => '',
                ],
                true,
            );

            $count = (int) $tenant_db->count_all('appointments');
            $tenant_db->close();

            return $count;
        } catch (Throwable $e) {
            return null;
        }
    }
}
