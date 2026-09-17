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

use Jsvrcek\ICS\Exception\CalendarEventException;

require_once __DIR__ . '/Google.php';
require_once __DIR__ . '/Caldav.php';

/**
 * Console controller.
 *
 * Handles all the Console related operations.
 */
class Console extends EA_Controller
{
    /**
     * Console constructor.
     */
    public function __construct()
    {
        if (!is_cli()) {
            exit('No direct script access allowed');
        }

        parent::__construct();

        $this->load->dbutil();
        $this->load->dbforge(); // BooKi (2026-08-26) - used by master_install()

        $this->load->library('instance');
        $this->load->library('cleanup');

        $this->load->model('admins_model');
        $this->load->model('customers_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('settings_model');
        $this->load->model('stations_model');
        $this->load->model('service_categories_model');
        $this->load->model('appointments_model');
    }

    /**
     * Perform a console installation.
     *
     * Use this method to install BooKi directly from the terminal.
     *
     * Usage:
     *
     * php index.php console install
     *
     * @throws Exception
     */
    public function install(): void
    {
        $this->instance->migrate('fresh');

        $password = $this->instance->seed();

        response(
            PHP_EOL . '⇾ Installation completed, login with "administrator" / "' . $password . '".' . PHP_EOL . PHP_EOL,
        );
    }

    /**
     * Migrate the database to the latest state.
     *
     * Use this method to upgrade an BooKi instance to the latest database state.
     *
     * Notice:
     *
     * Do not use this method to install the app as it will not seed the database with the initial entries (admin,
     * provider, service, settings etc.).
     *
     * BooKi (2026-08-26) - multi-tenant aware: if the connected 'default' DB is a master DB
     * (has a `tenants` table, see is_multi_tenant_mode()), migrates EVERY active tenant's own database
     * in turn instead of the 'default' connection itself - "every code update auto-applies to every
     * tenant" (see project plan). A single tenant failing is logged to `tenant_migration_log` and does
     * NOT stop the others. Single-tenant/standalone deployments (e.g. Salon Flora) are completely
     * unaffected - is_multi_tenant_mode() is false there, so this falls through to the exact original
     * behavior.
     *
     * Usage:
     *
     * php index.php console migrate
     *
     * php index.php console migrate fresh
     *
     * @param string $type
     */
    public function migrate(string $type = ''): void
    {
        if (!is_multi_tenant_mode()) {
            $this->instance->migrate($type);

            return;
        }

        $tenants = $this->db->get_where('tenants', ['status' => 'active'])->result_array();

        foreach ($tenants as $tenant) {
            echo 'Migrating tenant "' . $tenant['subdomain'] . '"... ';

            try {
                $this->connect_tenant($tenant);
                $this->instance->migrate($type);

                echo 'OK' . PHP_EOL;

                $this->connect_master();
                $this->db->insert('tenant_migration_log', [
                    'id_tenants' => $tenant['id'],
                    'status' => 'success',
                    'migrated_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (Throwable $e) {
                echo 'FAILED: ' . $e->getMessage() . PHP_EOL;

                $this->connect_master();
                $this->db->insert('tenant_migration_log', [
                    'id_tenants' => $tenant['id'],
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'migrated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->connect_master();
    }

    /**
     * BooKi (2026-08-26) - create the master DB schema (`tenants`, `tenant_migration_log`).
     * Run this ONCE, against a deployment whose 'default' connection points at the intended master
     * DB, before provisioning any tenant with tenant_create(). Separate from the regular
     * application/migrations/ sequence (those apply to TENANT databases) - the master schema is tiny
     * and versioned independently.
     *
     * Usage:
     *
     * php index.php console master_install
     */
    public function master_install(): void
    {
        if (!$this->db->table_exists('tenants')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'subdomain' => ['type' => 'VARCHAR', 'constraint' => 63, 'null' => false],
                'custom_domain' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'db_host' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'db_name' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'db_username' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'db_password' => ['type' => 'TEXT', 'null' => false],
                'pii_enc_key' => ['type' => 'TEXT', 'null' => false],
                'pii_hash_key' => ['type' => 'TEXT', 'null' => false],
                'deployment_type' => [
                    'type' => 'ENUM',
                    'constraint' => ['cloud', 'self_hosted'],
                    'null' => false,
                    'default' => 'cloud',
                ],
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['active', 'suspended'],
                    'null' => false,
                    'default' => 'active',
                ],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
                'updated_at' => ['type' => 'DATETIME', 'null' => false],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('tenants', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('tenants') .
                    ' ADD UNIQUE INDEX idx_tenants_subdomain (subdomain),' .
                    ' ADD UNIQUE INDEX idx_tenants_custom_domain (custom_domain)',
            );

            echo 'Created "tenants" table.' . PHP_EOL;
        } else {
            echo '"tenants" table already exists, skipped.' . PHP_EOL;
        }

        if (!$this->db->table_exists('tenant_migration_log')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_tenants' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'status' => ['type' => 'ENUM', 'constraint' => ['success', 'failed'], 'null' => false],
                'error_message' => ['type' => 'TEXT', 'null' => true],
                'migrated_at' => ['type' => 'DATETIME', 'null' => false],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('tenant_migration_log', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('tenant_migration_log') .
                    ' ADD INDEX idx_tml_tenant (id_tenants)',
            );

            echo 'Created "tenant_migration_log" table.' . PHP_EOL;
        } else {
            echo '"tenant_migration_log" table already exists, skipped.' . PHP_EOL;
        }

        // BooKi (2026-08-26) - SaaS admin panel (reservationadmin.kibusiness.co) support.
        // master_admins is a credential store entirely separate from any tenant's own users - Ki
        // Software's own staff, not tied to a tenant, never resolved via EA_Controller::resolve_tenant().
        if (!$this->db->table_exists('master_admins')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'username' => ['type' => 'VARCHAR', 'constraint' => 256, 'null' => false],
                'email' => ['type' => 'VARCHAR', 'constraint' => 256, 'null' => false],
                'password' => ['type' => 'VARCHAR', 'constraint' => 512, 'null' => false],
                'salt' => ['type' => 'VARCHAR', 'constraint' => 512, 'null' => false],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('master_admins', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('master_admins') .
                    ' ADD UNIQUE INDEX idx_master_admins_username (username)',
            );

            echo 'Created "master_admins" table.' . PHP_EOL;
        } else {
            echo '"master_admins" table already exists, skipped.' . PHP_EOL;
        }

        // Plan/license tracking columns on `tenants` - added individually (idempotent) so re-running
        // master_install() on an already-installed master DB upgrades it in place.
        $tenant_columns = [
            'plan' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'trial_ends_at' => ['type' => 'DATETIME', 'null' => true],
            'license_expires_at' => ['type' => 'DATETIME', 'null' => true],
            'suspended_at' => ['type' => 'DATETIME', 'null' => true],
            // BooKi (2026-08-27) - Marketplace support
            'marketplace_opt_in' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false],
            'category' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'city' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'cover_image_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'short_description' => ['type' => 'TEXT', 'null' => true],
            // BooKi (2026-09-10) - tenant self-service custom domain (Custom_domain.php
            // controller). 'custom_domain' (above) is the LIVE, routed domain - untouched here until
            // the host-side domain-worker.sh actually provisions it. 'custom_domain_pending' is what
            // the tenant just requested, tracked separately so a bad/incomplete request never clobbers
            // an already-working domain.
            'custom_domain_pending' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'custom_domain_status' => [
                'type' => 'ENUM',
                'constraint' => ['none', 'pending_dns', 'dns_verified', 'provisioning', 'active', 'failed'],
                'null' => false,
                'default' => 'none',
            ],
            'custom_domain_verification_token' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'custom_domain_requested_at' => ['type' => 'DATETIME', 'null' => true],
            'custom_domain_verified_at' => ['type' => 'DATETIME', 'null' => true],
            'custom_domain_active_at' => ['type' => 'DATETIME', 'null' => true],
            'custom_domain_last_error' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ];

        foreach ($tenant_columns as $column => $spec) {
            if (!$this->db->field_exists($column, 'tenants')) {
                $this->dbforge->add_column('tenants', [$column => $spec]);
                echo 'Added "tenants.' . $column . '" column.' . PHP_EOL;
            }
        }

        // BooKi (2026-08-27) - Marketplace reviews table (master DB, aggregates ratings from all tenants)
        if (!$this->db->table_exists('reviews')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_tenants' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'customer_name' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
                'customer_phone_hash' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'rating' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false],
                'comment' => ['type' => 'TEXT', 'null' => true],
                'source_appointment_hash' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['pending', 'published', 'rejected'],
                    'default' => 'pending',
                    'null' => false,
                ],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('reviews', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('reviews') .
                    ' ADD INDEX idx_reviews_tenant (id_tenants),' .
                    ' ADD UNIQUE INDEX idx_reviews_source_hash (source_appointment_hash)',
            );

            echo 'Created "reviews" table.' . PHP_EOL;
        } else {
            echo '"reviews" table already exists, skipped.' . PHP_EOL;
        }

        // BooKi (2026-08-26) - "Ki Business Google OAuth": a platform-wide key/value settings
        // table (superadmin-editable) so tenants can connect Google Calendar using Ki Software's own
        // shared OAuth Client instead of each needing their own Google Cloud project - see
        // master_setting() (tenant_helper.php) and Google_sync::get_client_id()/get_client_secret().
        if (!$this->db->table_exists('master_settings')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
                'value' => ['type' => 'TEXT', 'null' => true],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('master_settings', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('master_settings') . ' ADD UNIQUE INDEX idx_master_settings_name (name)',
            );

            echo 'Created "master_settings" table.' . PHP_EOL;
        } else {
            echo '"master_settings" table already exists, skipped.' . PHP_EOL;
        }

        // BooKi (Dalga 2, 2026-08-28) - health_token gates GET /health/deep (see
        // Health.php). It must be a MASTER-level secret, not a per-tenant setting: that endpoint
        // checks master DB reachability and iterates every tenant, so $this->db is the master
        // connection at the point the token is checked - a tenant `settings` row would never be
        // reachable from there. Idempotent: only generated once, existing token is never rotated
        // by re-running master_install().
        if (master_setting('health_token') === null) {
            master_setting('health_token', bin2hex(random_bytes(32)));
            echo 'Generated "health_token" master setting.' . PHP_EOL;
        }
    }

    /**
     * BooKi (2026-08-26) - create a SaaS super-admin account (reservationadmin.kibusiness.co
     * login) in the master DB. Requires master_install() to have been run first.
     *
     * Usage: php index.php console superadmin_create <username> <email> <password>
     */
    public function superadmin_create(string $username = '', string $email = '', string $password = ''): void
    {
        if (!$this->db->table_exists('master_admins')) {
            show_error('The "master_admins" table does not exist - run "console master_install" first.');

            return;
        }

        if ($username === '' || $email === '' || $password === '') {
            show_error('Usage: console superadmin_create <username> <email> <password>');

            return;
        }

        if ($this->db->get_where('master_admins', ['username' => $username])->num_rows() > 0) {
            show_error('A super-admin with username "' . $username . '" already exists.');

            return;
        }

        $salt = generate_salt();

        $this->db->insert('master_admins', [
            'username' => $username,
            'email' => $email,
            'password' => hash_password($salt, $password),
            'salt' => $salt,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        echo 'Super-admin "' . $username . '" created.' . PHP_EOL;
    }

    /**
     * BooKi (2026-08-26) - provision a brand-new tenant: creates its database, generates
     * fresh (tenant-specific, never reused across tenants) PII encryption keys, runs the full
     * migration set against it, seeds the default admin/service/provider, and registers it in the
     * master `tenants` table. Requires master_install() to have been run first.
     *
     * The new tenant's database lives on the SAME MySQL server as the master DB (db_host taken from
     * the master connection's own config) - this deployment doesn't (yet) support spreading tenants
     * across multiple physical DB servers, though the schema (db_host stored per-tenant) allows it
     * later without a migration.
     *
     * Usage:
     *
     * php index.php console tenant_create acme
     *
     * php index.php console tenant_create acme acme.example.com
     *
     * @param string $subdomain Must be a valid single DNS label (letters, digits, hyphens).
     * @param string $custom_domain Optional - a fully-qualified custom domain this tenant is also reachable at.
     *
     * @throws Exception
     */
    public function tenant_create(string $subdomain = '', string $custom_domain = ''): void
    {
        if (!is_multi_tenant_mode()) {
            show_error(
                'The connected "default" database has no "tenants" table - run "console master_install" first.',
            );

            return;
        }

        $subdomain = strtolower(trim($subdomain));

        if ($subdomain === '' || !preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $subdomain)) {
            show_error('Provide a valid subdomain (a single DNS label) as the first argument.');

            return;
        }

        if ($this->db->get_where('tenants', ['subdomain' => $subdomain])->num_rows() > 0) {
            show_error('A tenant with subdomain "' . $subdomain . '" already exists.');

            return;
        }

        // Keep the master connection's own hostname/user/pass - the new tenant DB lives on the same server.
        $db_host = $this->db->hostname;
        $db_username = $this->db->username;
        $db_password_plain = $this->db->password;
        $db_name = 'ki_tenant_' . $subdomain;

        // Create the tenant's database on the same MySQL server as the master DB.
        $this->db->query('CREATE DATABASE IF NOT EXISTS `' . $db_name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

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
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $tenant_id = $this->db->insert_id();

        echo 'Registered tenant "' . $subdomain . '" (id ' . $tenant_id . '), database "' . $db_name . '".' . PHP_EOL;

        // Switch to the new tenant's database and set up its schema/default data, reusing the exact
        // same install path a standalone deployment goes through.
        $this->connect_tenant([
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

        $password = $this->instance->seed();

        $this->connect_master();

        echo '⇾ Tenant "' .
            $subdomain .
            '" installed, login with "administrator" / "' .
            $password .
            '".' .
            PHP_EOL;
    }

    /**
     * BooKi (2026-08-26) - one-off, re-runnable COPY (never a move) of Salon Flora's live
     * standalone data (services, stations, providers, customers, appointments) into its own
     * `salonflora` tenant in this multi-tenant instance. The live system
     * (rezervasyon.salonflora.tr / salonflora-ea-app + salonflora-ea-db) is ONLY ever read from - not
     * one row there is modified or deleted - and stays fully operational throughout and after this.
     *
     * Customers are deduplicated before import: the live DB has ~3868 raw customer rows but the
     * business has ~400-something real customers (front-desk created a fresh row nearly every visit
     * instead of reusing an existing one). Dedup key: phone_number_hash (exact match - same phone =
     * same person) first, then, for the handful with no phone at all, an exact normalized first+last
     * name match. Common Turkish honorifics accidentally saved as part of a name ("Bey", "Hanım", ...)
     * are stripped during normalization so they never end up looking like a real surname - important
     * for any future CRM/Meta/Google Ads/Composio integration keyed off clean names.
     *
     * Usage:
     *   php index.php console migrate_salonflora_live_data dry      # report only, no writes anywhere
     *   php index.php console migrate_salonflora_live_data commit    # actually import into the tenant
     *
     * Requires: ki-reservation-app container connected to the salonflora_salonflora-net Docker
     * network (`docker network connect salonflora_salonflora-net ki-reservation-app`), so it can reach
     * the live `easyappointments-db` host - this is NOT part of the container's own compose file
     * (deliberately - the connection is only needed for this one-off script, not ongoing operation).
     */
    public function migrate_salonflora_live_data(string $mode = 'dry'): void
    {
        if (!is_multi_tenant_mode()) {
            show_error('The connected "default" database has no "tenants" table.');

            return;
        }

        $tenant = $this->db->get_where('tenants', ['subdomain' => 'salonflora'])->row_array();

        if (!$tenant) {
            show_error('No "salonflora" tenant found - run "console tenant_create salonflora" first.');

            return;
        }

        $commit = $mode === 'commit';

        echo ($commit ? '=== COMMIT MODE - will write to the salonflora tenant ===' : '=== DRY RUN - no writes, report only ===') .
            PHP_EOL . PHP_EOL;

        // --- Phase 1: read everything from the LIVE Salon Flora DB, decrypted with ITS OWN keys. ---
        // tenant_context() must be null here so sf_pii_*() fall back to the env-var keys below (the
        // live system's actual SF_PII_ENC_KEY/SF_PII_HASH_KEY, copied from its docker-compose.yml).
        tenant_context_clear();
        putenv('SF_PII_ENC_KEY=wMiZvcN66xk8GA505Jk5dWpbRWifFTSRQf6WBRdyLFE=');
        putenv('SF_PII_HASH_KEY=KO8PdSdJE9poZMKVY0/+k+it8Z/fwLnvK8ch6wISvJk=');

        $old_db = $this->load->database(
            [
                'hostname' => 'easyappointments-db',
                'username' => 'easyappointments',
                'password' => 'NZEJtaocUqaLqQeH45jibYGt',
                'database' => 'easyappointments',
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
            true,
        );

        $old_categories = $old_db->get('service_categories')->result_array();
        $old_services = $old_db->get('services')->result_array();
        $old_stations = $old_db->get('stations')->result_array();

        // NOTE: fetch role IDs into plain variables FIRST - calling $old_db->get_where(...) again
        // while another query is still being built on that SAME connection object resets the pending
        // select()/from()/join() state (CI3's query builder keeps that state on the connection, not
        // per-call), silently corrupting the outer query.
        $provider_role_id = $old_db->get_where('roles', ['slug' => 'provider'])->row_array()['id'];
        $customer_role_id = $old_db->get_where('roles', ['slug' => 'customer'])->row_array()['id'];

        $old_providers = $old_db
            ->select('users.*, user_settings.username, user_settings.working_plan, user_settings.calendar_view, user_settings.notifications')
            ->from('users')
            ->join('user_settings', 'user_settings.id_users = users.id', 'inner')
            ->where('users.id_roles', $provider_role_id)
            ->get()
            ->result_array();

        // Decrypt NOW, while tenant_context() is still null and the OLD env-var keys are active -
        // connect_tenant() below switches tenant_context() to the NEW tenant's key, after which
        // sf_pii_decrypt() on this OLD ciphertext would silently fail (wrong key) and return null.
        foreach ($old_providers as &$provider) {
            foreach (['email', 'phone_number', 'address', 'state', 'zip_code', 'notes'] as $field) {
                $provider[$field] = sf_pii_decrypt($provider[$field]);
            }
        }

        unset($provider);

        $skipped_providers = $old_db
            ->select('id, first_name, last_name')
            ->from('users')
            ->where('id_roles', $provider_role_id)
            ->where_not_in('id', array_column($old_providers, 'id') ?: [0])
            ->get()
            ->result_array();

        $provider_service_ids = [];

        foreach ($old_db->get('services_providers')->result_array() as $row) {
            $provider_service_ids[$row['id_users']][] = (int) $row['id_services'];
        }

        $provider_station_ids = [];

        foreach ($old_db->get('stations_providers')->result_array() as $row) {
            $provider_station_ids[$row['id_users']][] = (int) $row['id_stations'];
        }

        $old_customers = $old_db
            ->select(
                'id, first_name, last_name, email, email_hash, phone_number, phone_number_hash, address, ' .
                    'city, state, zip_code, notes, social_links, timezone, language, create_datetime',
            )
            ->from('users')
            ->where('id_roles', $customer_role_id)
            ->order_by('create_datetime', 'asc')
            ->order_by('id', 'asc')
            ->get()
            ->result_array();

        // Decrypt now - see the identical comment above the provider loop for why this must happen
        // before connect_tenant() switches tenant_context() to the new tenant's key. phone_number_hash
        // (used for dedup grouping below) is left untouched - it's a hash, not ciphertext, and matching
        // it doesn't require decryption at all.
        foreach ($old_customers as &$customer) {
            foreach (['email', 'phone_number', 'address', 'state', 'zip_code', 'notes', 'social_links'] as $field) {
                $customer[$field] = sf_pii_decrypt($customer[$field]);
            }
        }

        unset($customer);

        $old_appointments = $old_db->get('appointments')->result_array();

        // --- Dedup customers: phone_number_hash groups first, then exact normalized-name groups for
        // the handful with no phone at all. ---
        $honorifics = ['bey', 'hanım', 'hanim', 'hoca', 'usta', 'abi', 'abla', 'beyefendi', 'hanımefendi', 'hanimefendi'];

        $normalize_name = function (string $first, string $last) use ($honorifics): array {
            $strip = function (string $value) use ($honorifics): array {
                $tokens = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);

                return array_values(
                    array_filter($tokens, fn($t) => !in_array(mb_strtolower($t, 'UTF-8'), $honorifics, true)),
                );
            };

            return ['first_name' => implode(' ', $strip($first)), 'last_name' => implode(' ', $strip($last))];
        };

        $phone_groups = [];
        $no_phone = [];

        foreach ($old_customers as $row) {
            if (!empty($row['phone_number_hash'])) {
                $phone_groups[$row['phone_number_hash']][] = $row;
            } else {
                $no_phone[] = $row;
            }
        }

        $name_groups = [];

        foreach ($no_phone as $row) {
            $norm = $normalize_name((string) $row['first_name'], (string) $row['last_name']);
            $key = mb_strtolower(trim($norm['first_name'] . '|' . $norm['last_name']), 'UTF-8');
            $name_groups[$key][] = $row;
        }

        $customer_groups = array_merge(array_values($phone_groups), array_values($name_groups));

        echo 'Kaynak (canlı Salon Flora): ' .
            count($old_categories) . ' kategori, ' .
            count($old_services) . ' hizmet, ' .
            count($old_stations) . ' istasyon, ' .
            count($old_providers) . ' sağlayıcı (kullanılabilir), ' .
            count($skipped_providers) . ' sağlayıcı atlanacak (kullanıcı adı/ayar eksik), ' .
            count($old_customers) . ' ham müşteri kaydı, ' .
            count($customer_groups) . ' tekilleştirilmiş müşteri, ' .
            count($old_appointments) . ' randevu.' . PHP_EOL;

        foreach ($skipped_providers as $sp) {
            echo '  Atlanan sağlayıcı: "' . $sp['first_name'] . ' ' . $sp['last_name'] . '" (id ' . $sp['id'] . ', ayar/kullanıcı adı yok)' . PHP_EOL;
        }

        if (!$commit) {
            echo PHP_EOL . 'Dry run tamamlandı - hiçbir yere yazılmadı. Gerçek aktarım için "commit" parametresiyle çalıştırın.' . PHP_EOL;

            return;
        }

        // --- Phase 2: switch to the salonflora TENANT's own DB - tenant_context() now points at ITS
        // OWN pii_enc_key/pii_hash_key, so every sf_pii_encrypt() call from here on re-encrypts with
        // the NEW tenant's key, never the old one. ---
        $this->connect_tenant($tenant);

        // The tenant's public-booking-form field requirements (require_email/require_phone_number/
        // require_last_name, all '1' by default) don't apply to historical data being imported -
        // plenty of real customers only ever gave a phone OR only a first name (which is the whole
        // point of the name normalization here - a single-name customer should stay that way, not be
        // forced into a fabricated last name). Relax them for the duration of the import, then restore
        // whatever they were set to before.
        $original_require_email = setting('require_email');
        $original_require_phone_number = setting('require_phone_number');
        $original_require_last_name = setting('require_last_name');

        setting(['require_email' => '0', 'require_phone_number' => '0', 'require_last_name' => '0']);

        // BooKi (2026-09-12) - IDEMPOTENCY ADDED: this script's first run already imported
        // 516 customers / 174 appointments into the (no longer empty) salonflora tenant - confirmed via
        // direct row counts before making this change. Re-running the original insert-only logic would
        // have duplicated every category/service/station/provider/customer/appointment a second time.
        // Every stage below now looks for an existing match FIRST and reuses its id; only genuinely new
        // rows (created on the live system since the last run) get inserted. Safe to run again in the
        // future for the same reason.
        $category_id_map = [];

        foreach ($old_categories as $category) {
            $existing = $this->db->get_where('service_categories', ['name' => $category['name']])->row_array();

            $category_id_map[$category['id']] = $existing
                ? (int) $existing['id']
                : $this->service_categories_model->save([
                    'name' => $category['name'],
                    'description' => $category['description'],
                ]);
        }

        $service_id_map = [];

        foreach ($old_services as $service) {
            $existing = $this->db->get_where('services', ['name' => $service['name']])->row_array();

            $service_id_map[$service['id']] = $existing
                ? (int) $existing['id']
                : $this->services_model->save([
                    'name' => $service['name'],
                    'duration' => $service['duration'],
                    'price' => $service['price'],
                    'currency' => $service['currency'],
                    'description' => $service['description'],
                    'slot_interval' => $service['slot_interval'],
                    'color' => $service['color'],
                    'location' => $service['location'],
                    'attendants_number' => $service['attendants_number'],
                    'is_private' => $service['is_private'],
                    'id_service_categories' => $category_id_map[$service['id_service_categories']] ?? null,
                ]);
        }

        $station_id_map = [];

        foreach ($old_stations as $station) {
            $existing = $this->db->get_where('stations', ['name' => $station['name']])->row_array();

            $station_id_map[$station['id']] = $existing
                ? (int) $existing['id']
                : $this->stations_model->save([
                    'name' => $station['name'],
                    'notes' => $station['notes'],
                    'is_active' => $station['is_active'],
                    'services' => [], // fail-open in the source too (empty stations_services there)
                ]);
        }

        $provider_id_map = [];
        $provider_role_id_new = $this->db->get_where('roles', ['slug' => DB_SLUG_PROVIDER])->row_array()['id'];

        $build_unique_provider_username = function (string $base_username, string $first_name, ?string $last_name): string {
            $base = trim((string) $base_username);

            if ($base === '') {
                $base = preg_replace('/[^a-zA-Z0-9._-]+/', '', strtolower(trim($first_name . ' ' . ($last_name ?? ''))));
                $base = trim((string) $base, '.-_');
            }

            if ($base === '') {
                $base = 'provider';
            }

            $candidate = $base;
            $suffix = 2;

            while ($this->db->get_where('user_settings', ['username' => $candidate])->num_rows() > 0) {
                $candidate = $base . $suffix;
                $suffix++;
            }

            return $candidate;
        };

        foreach ($old_providers as $provider) {
            $new_service_ids = array_map(
                fn($id) => $service_id_map[$id] ?? null,
                $provider_service_ids[$provider['id']] ?? [],
            );

            $new_station_ids = array_map(
                fn($id) => $station_id_map[$id] ?? null,
                $provider_station_ids[$provider['id']] ?? [],
            );

            // Match by FIRST NAME ONLY (not last name) - the live system has the same real person
            // duplicated under slightly different last names/honorifics (e.g. "Aybeniz H" here vs.
            // "Aybeniz Hanım" as the skipped duplicate above, vs. "Aybeniz Erdogan" already in the
            // tenant from an earlier run) - user confirmed these are all one person. First-name-only
            // matching is safe here because the salon has a handful of staff, each with a distinct
            // first name.
            $existing_provider = $this->db
                ->select('users.id, users.last_name')
                ->from('users')
                ->where('users.id_roles', $provider_role_id_new)
                ->where('users.first_name', $provider['first_name'])
                ->get()
                ->row_array();

            if ($existing_provider) {
                $provider_id_map[$provider['id']] = (int) $existing_provider['id'];

                // User-requested cleanup: normalize the display name to the same "İlk Ad İlk Harf."
                // convention as the other providers (İlayda İ., Aslı O., ...), once.
                if (mb_strtolower(trim((string) $existing_provider['last_name']), 'UTF-8') !== 'e.'
                    && mb_strtolower((string) $provider['first_name'], 'UTF-8') === 'aybeniz') {
                    $this->db->update('users', ['last_name' => 'E.'], ['id' => $existing_provider['id']]);
                }

                continue;
            }

            $unique_username = $build_unique_provider_username(
                (string) ($provider['username'] ?? ''),
                (string) ($provider['first_name'] ?? ''),
                $provider['last_name'] ?? null,
            );

            try {
                $provider_id_map[$provider['id']] = $this->providers_model->save([
                    'first_name' => $provider['first_name'],
                    'last_name' => $provider['last_name'],
                    'email' => $provider['email'],
                    'mobile_number' => $provider['mobile_number'],
                    'phone_number' => $provider['phone_number'],
                    'address' => $provider['address'],
                    'city' => $provider['city'],
                    'state' => $provider['state'],
                    'zip_code' => $provider['zip_code'],
                    'notes' => $provider['notes'],
                    'timezone' => $provider['timezone'] ?: 'UTC',
                    'language' => $provider['language'] ?: 'turkish',
                    'services' => array_values(array_filter($new_service_ids)),
                    'stations' => array_values(array_filter($new_station_ids)),
                    'settings' => [
                        // BooKi (2026-08-26) - the old bcrypt hash cannot be carried over as-is:
                        // Providers_model::insert() always re-hashes whatever is in 'password' as if it
                        // were plaintext. A migrated provider gets this fixed temporary password instead
                        // (reported to the user, must be changed on first login). When the original live
                        // username is already taken in the tenant, we keep the original name as a base and
                        // append a numeric suffix so repeated imports remain safe.
                        'username' => $unique_username,
                        'password' => 'DegistirBu2026!',
                        'working_plan' => $provider['working_plan'],
                        'calendar_view' => $provider['calendar_view'] ?: 'default',
                        'notifications' => (bool) $provider['notifications'],
                    ],
                ]);
            } catch (Throwable $e) {
                echo '  Sağlayıcı atlandı (eski id ' . $provider['id'] . '): ' . $e->getMessage() . PHP_EOL;
            }
        }

        $customer_id_map = []; // old customer id (ANY duplicate) -> new canonical customer id

        $imported_customers = 0;

        foreach ($customer_groups as $group) {
            // Prefer the most recently created duplicate as the base record (most likely to reflect
            // the latest/most complete info a staff member entered), but fill in any field it's
            // missing from an older duplicate in the same group.
            $base = end($group);
            reset($group);

            $pick = function (string $field) use ($group, $base) {
                if (!empty($base[$field])) {
                    return $base[$field];
                }

                foreach ($group as $row) {
                    if (!empty($row[$field])) {
                        return $row[$field];
                    }
                }

                return null;
            };

            $norm = $normalize_name((string) $base['first_name'], (string) $pick('last_name'));

            // Idempotency: this customer may already exist from an earlier run of this same script.
            // phone_number_hash/email_hash in the OLD dump were computed with the LIVE system's own
            // pii_hash_key - not comparable to this tenant's hashes - but we already have the
            // DECRYPTED plaintext (from phase 1, before connect_tenant() switched keys), so
            // sf_pii_hash() here recomputes it correctly under the NEW tenant's key for a real lookup.
            $existing_customer_id = null;
            $pick_phone = $pick('phone_number');
            $pick_email = $pick('email');

            if (!empty($pick_phone)) {
                $match = $this->db
                    ->select('users.id')
                    ->from('users')
                    ->join('roles', 'roles.id = users.id_roles', 'inner')
                    ->where('roles.slug', DB_SLUG_CUSTOMER)
                    ->where('users.phone_number_hash', sf_pii_hash($pick_phone))
                    ->get()
                    ->row_array();
                $existing_customer_id = $match['id'] ?? null;
            }

            if (!$existing_customer_id && !empty($pick_email)) {
                $match = $this->db
                    ->select('users.id')
                    ->from('users')
                    ->join('roles', 'roles.id = users.id_roles', 'inner')
                    ->where('roles.slug', DB_SLUG_CUSTOMER)
                    ->where('users.email_hash', sf_pii_hash($pick_email))
                    ->get()
                    ->row_array();
                $existing_customer_id = $match['id'] ?? null;
            }

            if (!$existing_customer_id && $norm['first_name'] !== '') {
                $match = $this->db
                    ->select('users.id')
                    ->from('users')
                    ->join('roles', 'roles.id = users.id_roles', 'inner')
                    ->where('roles.slug', DB_SLUG_CUSTOMER)
                    ->where('users.first_name', $norm['first_name'])
                    ->where('users.last_name', $norm['last_name'])
                    ->get()
                    ->row_array();
                $existing_customer_id = $match['id'] ?? null;
            }

            if ($existing_customer_id) {
                $imported_customers++; // counted as "handled", not a fresh insert
                foreach ($group as $row) {
                    $customer_id_map[$row['id']] = (int) $existing_customer_id;
                }
                continue;
            }

            try {
                $new_id = $this->customers_model->save([
                    'first_name' => $norm['first_name'] !== '' ? $norm['first_name'] : $base['first_name'],
                    'last_name' => $norm['last_name'],
                    'email' => $pick('email'),
                    'phone_number' => $pick('phone_number'),
                    'address' => $pick('address'),
                    'city' => $pick('city'),
                    'state' => $pick('state'),
                    'zip_code' => $pick('zip_code'),
                    'notes' => $pick('notes'),
                    'social_links' => $pick('social_links'),
                    'timezone' => $pick('timezone') ?: 'UTC',
                    'language' => $pick('language') ?: 'turkish',
                ]);
            } catch (Throwable $e) {
                echo '  Müşteri atlandı (eski id ' . $base['id'] . '): ' . $e->getMessage() . PHP_EOL;
                continue;
            }

            $imported_customers++;

            foreach ($group as $row) {
                $customer_id_map[$row['id']] = $new_id;
            }
        }

        $imported_appointments = 0;
        $skipped_appointments = 0;

        foreach ($old_appointments as $appointment) {
            $new_provider_id = $provider_id_map[$appointment['id_users_provider']] ?? null;
            $new_customer_id = $appointment['is_unavailability']
                ? null
                : ($customer_id_map[$appointment['id_users_customer']] ?? null);
            $new_service_id = $service_id_map[$appointment['id_services']] ?? null;
            $new_station_id = $appointment['id_stations'] ? $station_id_map[$appointment['id_stations']] ?? null : null;

            if (!$new_provider_id || (!$appointment['is_unavailability'] && (!$new_customer_id || !$new_service_id))) {
                $skipped_appointments++;
                continue;
            }

            // Idempotency: same provider + same start time already imported = same appointment.
            $already_exists = $this->db
                ->where('id_users_provider', $new_provider_id)
                ->where('start_datetime', $appointment['start_datetime'])
                ->where('is_unavailability', $appointment['is_unavailability'])
                ->get('appointments')
                ->num_rows() > 0;

            if ($already_exists) {
                continue;
            }

            try {
                $this->appointments_model->save([
                    'start_datetime' => $appointment['start_datetime'],
                    'end_datetime' => $appointment['end_datetime'],
                    'actual_start_datetime' => $appointment['actual_start_datetime'],
                    'actual_end_datetime' => $appointment['actual_end_datetime'],
                    'is_unavailability' => $appointment['is_unavailability'],
                    'id_users_provider' => $new_provider_id,
                    'id_users_customer' => $new_customer_id,
                    'id_services' => $new_service_id ?: $service_id_map[array_key_first($service_id_map)],
                    'id_stations' => $new_station_id,
                    'station_assigned_manually' => $appointment['station_assigned_manually'],
                    'location' => $appointment['location'],
                    'notes' => $appointment['notes'],
                    'color' => $appointment['color'],
                    'status' => $appointment['status'],
                    'payment_status' => $appointment['payment_status'],
                    'payment_method' => $appointment['payment_method'],
                    'payment_amount' => $appointment['payment_amount'],
                    'payment_balance_amount' => $appointment['payment_balance_amount'],
                    'is_invoiced' => $appointment['is_invoiced'],
                ]);
            } catch (Throwable $e) {
                echo '  Randevu atlandı (eski id ' . $appointment['id'] . '): ' . $e->getMessage() . PHP_EOL;
                $skipped_appointments++;
                continue;
            }

            $imported_appointments++;
        }

        setting([
            'require_email' => $original_require_email,
            'require_phone_number' => $original_require_phone_number,
            'require_last_name' => $original_require_last_name,
        ]);

        $this->connect_master();

        echo PHP_EOL . '=== TAMAMLANDI ===' . PHP_EOL;
        echo count($category_id_map) . ' kategori, ' . count($service_id_map) . ' hizmet, ' .
            count($station_id_map) . ' istasyon, ' . count($provider_id_map) . ' sağlayıcı aktarıldı.' . PHP_EOL;
        echo $imported_customers . ' benzersiz müşteri aktarıldı (' . count($customer_id_map) . ' eski kayıt bunlara eşlendi).' . PHP_EOL;
        echo $imported_appointments . ' randevu aktarıldı, ' . $skipped_appointments . ' randevu eşleme eksikliği nedeniyle atlandı.' . PHP_EOL;
        echo PHP_EOL . 'Aktarılan sağlayıcıların GEÇİCİ şifresi: "DegistirBu2026!" - ilk girişte değiştirmeleri istenmeli.' . PHP_EOL;
    }

    /**
     * BooKi (2026-08-26) - point (or clear) a tenant's custom domain in the master DB.
     * Purely a DB update - actually provisioning the domain (DNS check, Let's Encrypt HTTP-01
     * certificate, nginx server block) is the host-side script's job
     * (/opt/apps/ki-rezervasyon/scripts/add-custom-domain.sh), which calls this as its last step.
     * EA_Controller::resolve_tenant() already reads `custom_domain` on every request - nothing else
     * needs to change once this is set.
     *
     * Usage: php index.php console tenant_set_custom_domain <subdomain> <custom_domain|-->
     * (pass "-" as custom_domain to clear it back to null.)
     */
    public function tenant_set_custom_domain(string $subdomain = '', string $custom_domain = ''): void
    {
        if (!is_multi_tenant_mode()) {
            show_error(
                'The connected "default" database has no "tenants" table - run "console master_install" first.',
            );

            return;
        }

        $subdomain = strtolower(trim($subdomain));
        $tenant = $this->db->get_where('tenants', ['subdomain' => $subdomain])->row_array();

        if (!$tenant) {
            show_error('No tenant with subdomain "' . $subdomain . '" was found.');

            return;
        }

        $custom_domain = trim($custom_domain);
        $value = $custom_domain === '' || $custom_domain === '-' ? null : strtolower($custom_domain);

        $this->db->update(
            'tenants',
            ['custom_domain' => $value, 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $tenant['id']],
        );

        echo 'Tenant "' . $subdomain . '" custom_domain set to: ' . ($value ?? '(none)') . PHP_EOL;
    }

    /**
     * Generate (or set) the per-tenant `agent_api_key` setting consumed by the Agent API
     * (application/controllers/Agent_api.php). The MCP server and any other agent client must
     * present this value as a Bearer token; tenants without a key respond 503 to agent calls.
     *
     * Passing an explicit value stores it verbatim (rotate by passing a new one). When $value is
     * empty a fresh 64-hex key is generated and printed.
     *
     * Usage:
     *
     * php index.php console agent_key <subdomain> [value]
     *
     * php index.php console agent_key salonflora
     */
    public function agent_key(string $subdomain = '', string $value = ''): void
    {
        $value = trim($value);

        if (!is_multi_tenant_mode()) {
            if ($value === '') {
                $value = bin2hex(random_bytes(32));
            }

            setting(['agent_api_key' => $value]);

            echo 'agent_api_key set to: ' . $value . PHP_EOL;

            return;
        }

        $subdomain = strtolower(trim($subdomain));

        $tenant = $this->db->get_where('tenants', ['subdomain' => $subdomain])->row_array();

        if (!$tenant) {
            show_error('No tenant with subdomain "' . $subdomain . '" was found.');

            return;
        }

        $this->connect_tenant($tenant);

        if ($value === '') {
            $value = bin2hex(random_bytes(32));
        }

        setting(['agent_api_key' => $value]);

        $this->connect_master();

        echo 'agent_api_key for tenant "' . $subdomain . '" set to: ' . $value . PHP_EOL;
    }

    /**
     * Add/reset the login credential of an Administrator user (username login, not email).
     *
     * Mirrors agent_key()'s tenant resolution so it works in both single-tenant and multi-tenant
     * mode. Uses the production Admins_model::save() path (PII encryption, bcrypt password hashing
     * via user_settings, username/email uniqueness) rather than raw SQL.
     *
     * php index.php console admin_add <subdomain> <username> <password> [first_name] [last_name] [email]
     *
     * php index.php console admin_add salonflora donkimonki "5562BooKi.." Donki Monki donkimonki@salonflora.tr
     */
    public function admin_add(
        string $subdomain,
        string $username,
        string $password,
        string $first_name = '',
        string $last_name = '',
        string $email = '',
    ): void {
        $username = trim($username);
        $password = $password;
        $first_name = trim($first_name) !== '' ? trim($first_name) : $username;
        $email = trim($email) !== '' ? trim($email) : $username . '@salonflora.tr';

        if ($username === '') {
            show_error('Username cannot be empty.');

            return;
        }

        if (!is_multi_tenant_mode()) {
            $this->create_admin_user($username, $password, $first_name, $last_name, $email);

            echo 'Admin "' . $username . '" is ready. Login with username + password.' . PHP_EOL;

            return;
        }

        $subdomain = strtolower(trim($subdomain));

        $tenant = $this->db->get_where('tenants', ['subdomain' => $subdomain])->row_array();

        if (!$tenant) {
            show_error('No tenant with subdomain "' . $subdomain . '" was found.');

            return;
        }

        $this->connect_tenant($tenant);

        $created = $this->create_admin_user($username, $password, $first_name, $last_name, $email);

        $this->connect_master();

        echo 'Admin "' . $username . '" for tenant "' . $subdomain . '" is '
            . ($created ? 'ready' : 'already set, credentials reset') . '. Login with username + password.' . PHP_EOL;
    }

    private function create_admin_user(
        string $username,
        string $password,
        string $first_name,
        string $last_name,
        string $email,
    ): bool {
        $this->load->model('admins_model');

        $existing = $this->db
            ->from('users')
            ->join('user_settings', 'user_settings.id_users = users.id', 'inner')
            ->where(['username' => $username])
            ->get()
            ->row_array();

        // Upsert: keep any existing admin user while forcing the requested username/password.
        $admin = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'settings' => [
                'username' => $username,
                'password' => $password,
            ],
        ];

        if (!empty($existing)) {
            $admin['id'] = (int) $existing['id'];
        }

        $this->admins_model->save($admin);

        return empty($existing);
    }

    /**
     * Manage the SaaS platform super-admin (reservationadmin.kibusiness.co) login credential.
     *
     * This writes to the MASTER database's `ea_master_admins` table (Ki Software staff, separate
     * from any tenant's users - see Superadmin_auth.php). It intentionally never touches tenant
     * databases and never calls connect_tenant(): in the console `$this->db` is already the master
     * connection.
     *
     * php index.php console admin_master <username> <password> [email]
     *
     * php index.php console admin_master donkimonki "5562BooKi.."
     */
    public function admin_master(string $username, string $password, string $email = ''): void
    {
        $username = trim($username);
        $email = trim($email) !== '' ? trim($email) : $username . '@kibusiness.co';

        if ($username === '' || $password === '') {
            show_error('Username and password are required.');

            return;
        }

        $existing = $this->db
            ->get_where('master_admins', ['username' => $username])
            ->row_array();

        $salt = generate_salt();
        $hash = hash_password($salt, $password);

        $values = [
            'email' => $email,
            'password' => $hash,
            'salt' => $salt,
        ];

        if (!empty($existing)) {
            $this->db->where('username', $username)->update('master_admins', $values);

            echo 'Master admin "' . $username . '" updated (password reset). Login on reservationadmin.kibusiness.co.'
                . PHP_EOL;

            return;
        }

        $values['username'] = $username;
        $values['created_at'] = date('Y-m-d H:i:s');

        $this->db->insert('master_admins', $values);

        echo 'Master admin "' . $username . '" created. Login on reservationadmin.kibusiness.co.' . PHP_EOL;
    }

    /**
     * BooKi (2026-09-16) - configure the platform-wide Zoho CRM integration. Values are
     * stored in master_settings (NOT per-tenant), so one configuration covers every tenant.
     *
     * Usage:
     *
     * php index.php console crm_config crm_sync_enabled 1
     * php index.php console crm_config zoho_region eu
     * php index.php console crm_config zoho_client_id ...
     * php index.php console crm_config zoho_client_secret ...
     * php index.php console crm_config zoho_refresh_token ...
     * php index.php console crm_config zoho_contacts_module Contacts
     * php index.php console crm_config zoho_appointments_module Deals
     * php index.php console crm_config zoho_deal_stage_new Qualification
     * php index.php console crm_config zoho_deal_stage_cancelled Lost
     * php index.php console crm_config zoho_contact_lookup_field Contact_Name
     *
     * Run without arguments to print the current configuration (values masked where secret).
     */
    public function crm_config(?string $name = null, ?string $value = null): void
    {
        $allowed = [
            'crm_sync_enabled',
            'zoho_region',
            'zoho_client_id',
            'zoho_client_secret',
            'zoho_refresh_token',
            'zoho_contacts_module',
            'zoho_appointments_module',
            'zoho_deal_stage_new',
            'zoho_deal_stage_cancelled',
            'zoho_contact_lookup_field',
        ];

        if ($name !== null && $value !== null) {
            $name = trim($name);

            if (!in_array($name, $allowed, true)) {
                show_error('Unknown CRM setting "' . $name . '". Allowed: ' . implode(', ', $allowed) . '.');

                return;
            }

            master_setting($name, trim($value));

            echo 'crm_config: ' . $name . ' = "' . trim($value) . '"' . PHP_EOL;

            return;
        }

        echo 'Current Zoho CRM configuration' . PHP_EOL;

        foreach ($allowed as $key) {
            $display = master_setting($key);

            if (in_array($key, ['zoho_client_id', 'zoho_client_secret', 'zoho_refresh_token'], true) && $display !== '') {
                $display = str_repeat('*', 8) . mb_substr($display, -4);
            }

            echo '  ' . $key . ' = ' . ($display === '' ? '(empty)' : $display) . PHP_EOL;
        }
    }

    /**
     * BooKi (2026-09-16) - drain the Zoho CRM outbox of every active tenant (or of a single
     * tenant). Pushes only pending customer/appointment events; idempotent afterwards ('sent' rows are
     * never re-pushed). Safe to run from a cron.
     *
     * Usage:
     *
     * php index.php console crm_sync                 (all tenants)
     * php index.php console crm_sync salonflora      (a single tenant)
     * php index.php console crm_sync --dry-run       (build + print payloads, never contacts Zoho)
     * php index.php console crm_sync salonflora --dry-run
     */
    public function crm_sync(string $arg1 = '', string $arg2 = ''): void
    {
        $subdomain = null;
        $dry_run = false;

        foreach ([$arg1, $arg2] as $arg) {
            if ($arg === '--dry-run') {
                $dry_run = true;
            } elseif ($arg !== '') {
                $subdomain = $arg;
            }
        }

        $this->load->library('crm_sync');

        if ($dry_run) {
            echo '=== DRY RUN - no records will be written to Zoho ===' . PHP_EOL;
        }

        $report = $this->crm_sync->run($subdomain, $dry_run);

        if (empty($report['configured'])) {
            echo $report['message'] . PHP_EOL;

            return;
        }

        if (empty($report['tenants'])) {
            echo 'No active tenants to process.' . PHP_EOL;

            return;
        }

        $totals = ['processed' => 0, 'sent' => 0, 'failed' => 0];

        foreach ($report['tenants'] as $subdomain_name => $tenant_report) {
            echo 'Tenant "' . $subdomain_name . '": '
                . $tenant_report['processed'] . ' processed, '
                . $tenant_report['sent'] . ' sent, '
                . $tenant_report['failed'] . ' failed'
                . PHP_EOL;

            foreach ($tenant_report['errors'] as $error) {
                echo '  ! ' . $error . PHP_EOL;
            }

            $totals['processed'] += $tenant_report['processed'];
            $totals['sent'] += $tenant_report['sent'];
            $totals['failed'] += $tenant_report['failed'];
        }

        echo 'Total: ' . $totals['processed'] . ' processed, '
            . $totals['sent'] . ' sent, '
            . $totals['failed'] . ' failed'
            . PHP_EOL;
    }

    /**
     * whose requested domain has passed the in-app DNS ownership check (custom_domain_status =
     * 'dns_verified') and is waiting for the actual Let's Encrypt cert + nginx server block. A
     * host-side worker (scripts/domain-worker.sh, run on a cron) polls this, then for each row runs
     * the EXISTING scripts/add-custom-domain.sh (unchanged - still the only thing that ever touches
     * certbot/nginx, and still only ever runs on the host, never inside this container) and finally
     * reports back via domain_provision_mark() below. One line of JSON per tenant so the worker can
     * parse it with `jq` without a real API.
     *
     * 2026-09-10 fix - this call now CLAIMS each row it hands out: the row is flipped from
     * 'dns_verified' to 'provisioning' by a single conditional UPDATE, and is only echoed when that
     * UPDATE actually won (affected_rows() === 1). The worker cron runs every 5 minutes but
     * add-custom-domain.sh can legitimately run longer than that (certbot rate limits, slow DNS), so
     * without the claim a second tick would hand the SAME row out again and two concurrent
     * `certbot certonly` runs plus two concurrent `docker cp` writes to the same NPM proxy-host conf
     * would race. Because the claim lives on the row (not in a file lock in the worker), a manual
     * worker/script re-run is protected too.
     *
     * A row whose worker died mid-run (host reboot, OOM) would otherwise sit in 'provisioning'
     * forever, so anything stuck there for more than STALE_PROVISIONING_MINUTES is reverted to
     * 'dns_verified' at the top of this call and simply gets picked up again on this same tick.
     *
     * Usage: php index.php console domain_requests_pending
     */
    public function domain_requests_pending(): void
    {
        if (!is_multi_tenant_mode()) {
            return;
        }

        $stale_provisioning_minutes = 30;

        // 2026-09-10 bugfix - these were raw $this->db->query() calls with a bare "tenants" table
        // name. CI's dbprefix ('ea_' here) is only ever applied by the QUERY BUILDER (->from(),
        // ->where(), ->table(), etc.) - a raw SQL string is never touched, so "UPDATE tenants ..."
        // failed with "Table 'ki_reservation_master.tenants' doesn't exist" on every call (the real
        // table is ea_tenants). $this->db->dbprefix('tenants') resolves it explicitly, the same
        // pattern already used a few lines up in master_install() for its ALTER TABLE calls.
        $this->db->query(
            'UPDATE ' . $this->db->dbprefix('tenants') . ' SET custom_domain_status = ?, updated_at = ? ' .
            'WHERE custom_domain_status = ? AND updated_at < ?',
            [
                'dns_verified',
                date('Y-m-d H:i:s'),
                'provisioning',
                date('Y-m-d H:i:s', time() - ($stale_provisioning_minutes * 60)),
            ],
        );

        $rows = $this->db
            ->select('id, subdomain, custom_domain_pending')
            ->from('tenants')
            ->where('custom_domain_status', 'dns_verified')
            ->get()
            ->result_array();

        foreach ($rows as $row) {
            // Atomic claim: single conditional UPDATE, so only one caller can ever move a given row
            // out of 'dns_verified'. If we lost the race, another worker already owns this domain.
            $this->db->query(
                'UPDATE ' . $this->db->dbprefix('tenants') . ' SET custom_domain_status = ?, updated_at = ? ' .
                'WHERE id = ? AND custom_domain_status = ?',
                ['provisioning', date('Y-m-d H:i:s'), (int) $row['id'], 'dns_verified'],
            );

            if ($this->db->affected_rows() !== 1) {
                continue;
            }

            echo json_encode([
                'subdomain' => $row['subdomain'],
                'custom_domain' => $row['custom_domain_pending'],
            ]) . PHP_EOL;
        }
    }

    /**
     * BooKi (2026-09-10) - tenant self-service custom domain, part 3/3: the host-side worker
     * calls this once it has finished (or failed) provisioning one tenant's pending domain from
     * domain_requests_pending() above. On success, add-custom-domain.sh has ALREADY pointed
     * `custom_domain` itself at the new value (its last step is tenant_set_custom_domain, unchanged) -
     * this call only updates the self-service status/timestamp bookkeeping that Custom_domain.php's
     * UI polls, and clears the now-redundant `custom_domain_pending`.
     *
     * Usage: php index.php console domain_provision_mark <subdomain> <active|failed> [error_message]
     */
    public function domain_provision_mark(string $subdomain = '', string $status = '', string $error_message = ''): void
    {
        if (!is_multi_tenant_mode()) {
            return;
        }

        if (!in_array($status, ['active', 'failed'], true)) {
            show_error('Status must be "active" or "failed".');

            return;
        }

        $subdomain = strtolower(trim($subdomain));
        $tenant = $this->db->get_where('tenants', ['subdomain' => $subdomain])->row_array();

        if (!$tenant) {
            show_error('No tenant with subdomain "' . $subdomain . '" was found.');

            return;
        }

        $update = [
            'custom_domain_status' => $status,
            'custom_domain_last_error' => $status === 'failed' ? mb_substr($error_message, 0, 255) : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($status === 'active') {
            $update['custom_domain_pending'] = null;
            $update['custom_domain_active_at'] = date('Y-m-d H:i:s');
        }

        $this->db->update('tenants', $update, ['id' => $tenant['id']]);

        echo 'Tenant "' . $subdomain . '" custom_domain_status set to: ' . $status . PHP_EOL;
    }

    /**
     * BooKi (Dalga 3 / Faz 3.1) - list the Communication Hub rules of every active tenant
     * (or a single tenant when a subdomain is given). Each row drives one event x recipient x channel
     * send - see Communication_hub::publish().
     *
     * Usage: php index.php console communication_rules [subdomain]
     */
    public function communication_rules(string $subdomain = ''): void
    {
        $tenants = $this->console_rule_tenants($subdomain);

        foreach ($tenants as $tenant) {
            if ($tenant !== null) {
                $this->connect_tenant($tenant);
            }

            echo PHP_EOL . '=== ' . ($tenant ? 'Tenant "' . $tenant['subdomain'] . '"' : 'Standalone DB') . ' ===' . PHP_EOL;

            $rules = $this->db
                ->order_by('event', 'asc')
                ->order_by('recipient', 'asc')
                ->get('communication_rules')
                ->result_array();

            if ($rules === []) {
                echo '  (kural yok)' . PHP_EOL;
                continue;
            }

            foreach ($rules as $rule) {
                echo sprintf(
                    "  [%s] %-22s -> %-9s <= %-28s %s%s",
                    (int) $rule['enabled'] === 1 ? 'ON ' : 'OFF',
                    $rule['event'],
                    $rule['recipient'],
                    $rule['channel'],
                    $rule['subject'] !== '' && $rule['subject'] !== null ? '| ' . $rule['subject'] : '',
                    PHP_EOL,
                );
            }
        }

        if (is_multi_tenant_mode()) {
            $this->connect_master();
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.1) - set the channels (and enable/disable) of one
     * event x recipient Communication Hub rule. Creates the row if it does not exist yet.
     *
     * Usage: php index.php console communication_rule_set <event> <recipient> <channel> [enabled] [subdomain]
     *
     * Example: php index.php console communication_rule_set appointment_completed customer email,sms 1
     */
    public function communication_rule_set(
        string $event = '',
        string $recipient = '',
        string $channel = '',
        string $enabled = '',
        string $subdomain = '',
    ): void {
        $event = strtolower(trim($event));
        $recipient = strtolower(trim($recipient));

        if (!in_array($event, ['appointment_created', 'appointment_completed', 'appointment_cancelled'], true)) {
            show_error('Unknown event "' . $event . '". Valid: appointment_created|appointment_completed|appointment_cancelled.');

            return;
        }

        if (!in_array($recipient, ['customer', 'provider', 'admin', 'secretary'], true)) {
            show_error('Unknown recipient "' . $recipient . '". Valid: customer|provider|admin|secretary.');

            return;
        }

        $channels = array_values(array_filter(array_map('trim', explode(',', $channel))));

        foreach ($channels as $single_channel) {
            if (!in_array($single_channel, ['email', 'sms', 'whatsapp', 'telegram'], true)) {
                show_error('Unknown channel "' . $single_channel . '". Valid: email|sms|whatsapp|telegram (comma-separated).');

                return;
            }
        }

        if ($channels === []) {
            show_error('At least one channel is required (comma-separated).');

            return;
        }

        $enabled_flag = $enabled === '' ? 1 : (int) $enabled;

        if (!in_array($enabled_flag, [0, 1], true)) {
            show_error('Enabled must be 0 or 1.');

            return;
        }

        $tenants = $this->console_rule_tenants($subdomain);

        foreach ($tenants as $tenant) {
            if ($tenant !== null) {
                $this->connect_tenant($tenant);
            }

            $existing = $this->db->get_where(
                'communication_rules',
                ['event' => $event, 'recipient' => $recipient],
            )->row_array();

            if ($existing) {
                $this->db->set('channel', implode(',', $channels));
                $this->db->set('enabled', $enabled_flag);
                $this->db->set('updated_at', date('Y-m-d H:i:s'));
                $this->db->where('event', $event);
                $this->db->where('recipient', $recipient);
                $this->db->update('communication_rules');

                echo 'Updated ' . $event . '/' . $recipient . ' @ ' .
                    ($tenant ? $tenant['subdomain'] : 'standalone') . PHP_EOL;
            } else {
                $this->db->insert('communication_rules', [
                    'event' => $event,
                    'recipient' => $recipient,
                    'channel' => implode(',', $channels),
                    'enabled' => $enabled_flag,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                echo 'Inserted ' . $event . '/' . $recipient . ' @ ' .
                    ($tenant ? $tenant['subdomain'] : 'standalone') . PHP_EOL;
            }

            echo '  channels: ' . implode(',', $channels) . PHP_EOL;
            echo '  enabled: ' . $enabled_flag . PHP_EOL;
        }

        if (is_multi_tenant_mode()) {
            $this->connect_master();
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.1) - set a rule's email subject and message body template
     * ({{placeholder}} syntax, e.g. {service_name} / {customer_name} / {start_datetime} / {reason}).
     * Pass "-" to clear either field back to null (falls back to the built-in default at send time).
     *
     * Usage: php index.php console communication_rule_template <event> <recipient> <subject> <message> [subdomain]
     */
    public function communication_rule_template(
        string $event = '',
        string $recipient = '',
        string $subject = '',
        string $message = '',
        string $subdomain = '',
    ): void {
        $event = strtolower(trim($event));
        $recipient = strtolower(trim($recipient));

        if (!in_array($event, ['appointment_created', 'appointment_completed', 'appointment_cancelled'], true)) {
            show_error('Unknown event "' . $event . '". Valid: appointment_created|appointment_completed|appointment_cancelled.');

            return;
        }

        if (!in_array($recipient, ['customer', 'provider', 'admin', 'secretary'], true)) {
            show_error('Unknown recipient "' . $recipient . '". Valid: customer|provider|admin|secretary.');

            return;
        }

        $subject_value = trim($subject);
        $message_value = trim($message);

        if ($subject_value === '-') {
            $subject_value = null;
        }

        if ($message_value === '-') {
            $message_value = null;
        }

        $tenants = $this->console_rule_tenants($subdomain);

        foreach ($tenants as $tenant) {
            if ($tenant !== null) {
                $this->connect_tenant($tenant);
            }

            $existing = $this->db->get_where(
                'communication_rules',
                ['event' => $event, 'recipient' => $recipient],
            )->row_array();

            if (!$existing) {
                $this->db->insert('communication_rules', [
                    'event' => $event,
                    'recipient' => $recipient,
                    'channel' => 'email',
                    'subject' => $subject_value ?? null,
                    'message' => $message_value ?? null,
                    'enabled' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                echo 'INSERTED ' . $event . '/' . $recipient . ' (disabled) @ ' .
                    ($tenant ? $tenant['subdomain'] : 'standalone') . PHP_EOL;
            } else {
                $this->db->set('subject', $subject_value);
                $this->db->set('message', $message_value);
                $this->db->set('updated_at', date('Y-m-d H:i:s'));
                $this->db->where('event', $event);
                $this->db->where('recipient', $recipient);
                $this->db->update('communication_rules');

                echo 'UPDATED template for ' . $event . '/' . $recipient . ' @ ' .
                    ($tenant ? $tenant['subdomain'] : 'standalone') . PHP_EOL;
            }

            echo '  subject: ' . ($subject_value ?? '(default)') . PHP_EOL;
            echo '  message: ' . ($message_value ?? '(default)') . PHP_EOL;
        }

        if (is_multi_tenant_mode()) {
            $this->connect_master();
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.2) - list the Automation Engine rules of every active tenant
     * (or a single tenant when a subdomain is given). Rules are seeded disabled; enable them with
     * automation_rule_toggle.
     *
     * Usage: php index.php console automation_rules [subdomain]
     */
    public function automation_rules(string $subdomain = ''): void
    {
        $tenants = $this->console_rule_tenants($subdomain);

        foreach ($tenants as $tenant) {
            if ($tenant !== null) {
                $this->connect_tenant($tenant);
            }

            echo PHP_EOL . '=== ' . ($tenant ? 'Tenant "' . $tenant['subdomain'] . '"' : 'Standalone DB') . ' ===' . PHP_EOL;

            $rules = $this->db
                ->order_by('id', 'asc')
                ->get('automation_rules')
                ->result_array();

            if ($rules === []) {
                echo '  (kural yok)' . PHP_EOL;
                continue;
            }

            foreach ($rules as $rule) {
                echo sprintf(
                    "  [%s] #%d %s\n       event: %s\n       conditions: %s\n       actions: %s\n",
                    (int) $rule['enabled'] === 1 ? 'ON ' : 'OFF',
                    (int) $rule['id'],
                    $rule['name'],
                    $rule['event'],
                    trim((string) $rule['conditions']) === '' ? '(her zaman)' : $rule['conditions'],
                    $rule['actions'],
                );
            }
        }

        if (is_multi_tenant_mode()) {
            $this->connect_master();
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.2) - toggle one Automation Engine rule on/off per tenant.
     *
     * Usage: php index.php console automation_rule_toggle <id> <0|1> [subdomain]
     *
     * Example: php index.php console automation_rule_toggle 1 1
     */
    public function automation_rule_toggle(
        string $id = '',
        string $enabled = '',
        string $subdomain = '',
    ): void {
        $rule_id = (int) $id;
        $flag = (int) $enabled;

        if ($rule_id <= 0) {
            show_error('A positive rule id is required.');

            return;
        }

        if (!in_array($flag, [0, 1], true)) {
            show_error('Enabled must be 0 or 1.');

            return;
        }

        $tenants = $this->console_rule_tenants($subdomain);

        foreach ($tenants as $tenant) {
            if ($tenant !== null) {
                $this->connect_tenant($tenant);
            }

            $rule = $this->db->get_where('automation_rules', ['id' => $rule_id])->row_array();

            if (!$rule) {
                echo 'Rule #' . $rule_id . ' not found @ ' .
                    ($tenant ? $tenant['subdomain'] : 'standalone') . PHP_EOL;
                continue;
            }

            $this->db->update('automation_rules', [
                'enabled' => $flag,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $rule_id]);

            echo 'Rule #' . $rule_id . ' "' . $rule['name'] . '" -> ' .
                ($flag ? 'ON' : 'OFF') . ' @ ' .
                ($tenant ? $tenant['subdomain'] : 'standalone') . PHP_EOL;
        }

        if (is_multi_tenant_mode()) {
            $this->connect_master();
        }
    }

    /**
     * Resolve the tenant list a communication-rule command should operate on. In multi-tenant mode
     * either the single tenant named by $subdomain (all if empty - like migrate()); in standalone
     * mode a single "null" pseudo-tenant meaning "run against the connected DB". The caller is
     * responsible for connect_tenant()/connect_master() around each entry.
     */
    private function console_rule_tenants(string $subdomain): array
    {
        if (!is_multi_tenant_mode()) {
            return [null];
        }

        if ($subdomain !== '') {
            $tenant = $this->db->get_where('tenants', ['subdomain' => strtolower(trim($subdomain))])->row_array();

            if (!$tenant) {
                show_error('No tenant with subdomain "' . $subdomain . '" was found.');

                return [];
            }

            return [$tenant];
        }

        return $this->db->get_where('tenants', ['status' => 'active'])->result_array();
    }

    /**
     * BooKi (2026-08-26) - swap $this->db to a tenant's own database AND set
     * tenant_context() so salonflora_crypto_helper.php uses this tenant's own PII keys (mirrors what
     * EA_Controller::resolve_tenant() does for web requests). $tenant must have
     * db_host/db_username/db_password/pii_enc_key/pii_hash_key (all tenant_master_encrypt()-ed) and
     * db_name - accepts either a full row from the `tenants` table or the minimal array tenant_create()
     * builds right after inserting one.
     */
    private function connect_tenant(array $tenant): void
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

        // BooKi (2026-08-26) - CRITICAL: dbforge is bound to whatever $this->db WAS at the
        // moment it was (lazily) loaded and does NOT follow later $this->db swaps on its own (unlike
        // models/migrations, which resolve $this->db dynamically via CI_Model/CI_Migration's __get
        // magic method proxying to get_instance()->db on every access). Without this explicit
        // reload, a migration's CREATE TABLE could silently run against the PREVIOUS tenant's
        // database instead of the one just connected to.
        $this->load->dbforge();

        // BooKi (2026-08-26) - CI_Migration normally auto-creates its "migrations" tracking
        // table, but only in its own constructor, which runs ONCE per process (the Loader caches
        // library instances) - for the FIRST tenant this Console process ever connects to, not every
        // one of them. Every subsequent tenant needs it created here instead, if missing.
        if (!$this->db->table_exists('migrations')) {
            $this->dbforge->add_field(['version' => ['type' => 'BIGINT', 'constraint' => 20]]);
            $this->dbforge->create_table('migrations', true);
            $this->db->insert('migrations', ['version' => 0]);
        }
    }

    /**
     * BooKi (2026-08-26) - reconnect $this->db to the master DB ('default' connection group)
     * after connect_tenant() swapped it away, so the caller can go back to querying `tenants`.
     */
    private function connect_master(): void
    {
        $this->load->database('default', false, true);
        $this->load->dbforge(); // see the comment in connect_tenant()
        tenant_context_clear(); // undo the tenant_context() connect_tenant() set
    }

    /**
     * Seed the database with test data.
     *
     * Use this method to add test data to your database
     *
     * Usage:
     *
     * php index.php console seed
     * @throws Exception
     */
    public function seed(): void
    {
        $this->instance->seed();
    }

    /**
     * Create a database backup file.
     *
     * Use this method to back up your BooKi data.
     *
     * Usage:
     *
     * php index.php console backup
     *
     * php index.php console backup /path/to/backup/folder
     *
     * @throws Exception
     */
    public function backup(): void
    {
        $this->instance->backup($GLOBALS['argv'][3] ?? null);
    }

    /**
     * Trigger the synchronization of all provider calendars with Google Calendar.
     *
     * Use this method in a cronjob to automatically sync events between BooKi and Google Calendar.
     *
     * Notice:
     *
     * Google syncing must first be enabled for each individual provider from inside the backend calendar page.
     *
     * Usage:
     *
     * php index.php console sync
     *
     * @throws CalendarEventException
     * @throws Exception
     * @throws Throwable
     */
    public function sync(): void
    {
        // BooKi (2026-08-26) - multi-tenant aware, same pattern as migrate(): iterate every
        // active tenant's own database instead of running once against 'default' (the master DB has
        // no `providers`/`users` tables at all). Single-tenant/standalone deployments unaffected.
        if (!is_multi_tenant_mode()) {
            $this->sync_current_db();

            return;
        }

        $tenants = $this->db->get_where('tenants', ['status' => 'active'])->result_array();

        foreach ($tenants as $tenant) {
            $this->connect_tenant($tenant);
            $this->sync_current_db();
        }

        $this->connect_master();
    }

    /**
     * BooKi (2026-08-26) - the original sync() body, run against whatever $this->db
     * currently points to (a single tenant, or the standalone DB).
     */
    private function sync_current_db(): void
    {
        $providers = $this->providers_model->get();

        foreach ($providers as $provider) {
            if (filter_var($provider['settings']['google_sync'], FILTER_VALIDATE_BOOLEAN)) {
                Google::sync((string) $provider['id']);

                // Registers a push-notification channel on first run, renews it once it's within
                // 24h of expiring, and does nothing otherwise - see Google::ensure_watch_channel().
                // This full-scan cron stays the safety net; the channel just shortens the latency
                // between a real change and it being reflected (see Google::webhook()).
                Google::ensure_watch_channel($provider);
            }

            if (filter_var($provider['settings']['caldav_sync'], FILTER_VALIDATE_BOOLEAN)) {
                Caldav::sync((string) $provider['id']);
            }
        }
    }

    /**
     * Clean up old customer data based on data retention settings.
     *
     * Use this method in a cronjob to automatically delete customer data older than the configured retention period.
     *
     * Usage:
     *
     * php index.php console cleanup
     *
     * @throws Exception
     */
    public function cleanup(): void
    {
        // BooKi (2026-08-26) - multi-tenant aware, same pattern as sync()/migrate().
        if (!is_multi_tenant_mode()) {
            $this->cleanup->run();

            return;
        }

        $tenants = $this->db->get_where('tenants', ['status' => 'active'])->result_array();

        foreach ($tenants as $tenant) {
            $this->connect_tenant($tenant);
            $this->cleanup->run();
        }

        $this->connect_master();
    }

    /**
     * BooKi (2026-08-28) - process queued background jobs from the unified job queue.
     *
     * Reserves up to $limit pending jobs for this worker, executes them via Job_dispatcher,
     * marks successes/failures, and releases stale reservations (from crashed workers).
     * Implements a per-minute wall-clock cap (50 seconds) to prevent long-running tasks
     * from blocking the next cron invocation.
     *
     * Multi-tenant aware: iterates every active tenant's database, processing their
     * jobs independently. Single-tenant deployments are unaffected.
     *
     * Usage:
     *
     * php index.php console process_jobs
     * php index.php console process_jobs default 50
     * php index.php console process_jobs default 100
     *
     * @param string $queue The queue name to process (default: 'default').
     * @param int $limit Maximum jobs to process per invocation (default: 50).
     * @throws Throwable
     */
    public function process_jobs(string $queue = 'default', int $limit = 50): void
    {
        // BooKi (2026-08-28) - multi-tenant aware, same pattern as sync()/cleanup()/migrate().
        if (!is_multi_tenant_mode()) {
            $this->process_jobs_current_db($queue, (int) $limit);

            return;
        }

        $tenants = $this->db->get_where('tenants', ['status' => 'active'])->result_array();
        $start_time = microtime(true);

        foreach ($tenants as $tenant) {
            echo 'Processing jobs for tenant "' . $tenant['subdomain'] . '"... ';

            $this->connect_tenant($tenant);
            $processed = $this->process_jobs_current_db($queue, (int) $limit);
            echo $processed . ' job(s) processed' . PHP_EOL;

            // Wall-clock cap: stop processing tenants after 50 seconds to avoid running
            // longer than a typical minute cron slot.
            if (microtime(true) - $start_time > 50) {
                echo 'Wall-clock cap reached (50s), stopping to avoid blocking next cron slot.' . PHP_EOL;
                break;
            }
        }

        $this->connect_master();
    }

    /**
     * BooKi (2026-08-28) - process jobs for the currently-connected database
     * (single tenant or standalone). Called per-tenant by process_jobs(), or directly
     * in single-tenant mode.
     *
     * @param string $queue The queue name to process.
     * @param int $limit Maximum jobs to reserve and process.
     * @return int Number of jobs processed (succeeded + failed).
     */
    private function process_jobs_current_db(string $queue, int $limit): int
    {
        $this->load->library('queue');
        $this->load->library('job_dispatcher');
        $this->load->model('jobs_model');

        // Release any reservations held by crashed workers (stale > 10 minutes).
        $this->queue->release_stale_reservations();

        // Reserve up to $limit pending jobs for this worker.
        $worker_id = gethostname() . ':' . getmypid();
        $jobs = $this->queue->reserve($limit, $worker_id, $queue);

        $processed = 0;

        foreach ($jobs as $job) {
            try {
                $this->job_dispatcher->dispatch($job);
                $this->queue->mark_succeeded($job['id']);
                $processed++;
            } catch (Throwable $e) {
                $this->queue->mark_failed($job['id'], $e);
                log_message('error', 'process_jobs - job ' . $job['id'] . ' failed: ' . $e->getMessage());
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Faz 30 (KVKK) - safety-net batch processor for pending data export requests. Every export
     * already goes through the normal job queue (Customer_portal::request_export() pushes
     * 'data_requests.export', drained by process_jobs()) - this exists only to catch exports created
     * while the queue was disabled (Customer_portal falls back to synchronous build in that case, so
     * in practice this should rarely find work) or a request that was queued but whose job row was
     * lost. Reuses Data_export::handle_queued_export() directly rather than duplicating its logic.
     *
     * Usage:
     *
     * php index.php console process_data_requests
     * php index.php console process_data_requests 50
     *
     * @param int $limit Maximum pending export requests to process per invocation.
     * @throws Throwable
     */
    public function process_data_requests(int $limit = 25): void
    {
        if (!is_multi_tenant_mode()) {
            $this->process_data_requests_current_db($limit);

            return;
        }

        $tenants = $this->db->get_where('tenants', ['status' => 'active'])->result_array();
        $start_time = microtime(true);

        foreach ($tenants as $tenant) {
            echo 'Processing data export requests for tenant "' . $tenant['subdomain'] . '"... ';

            $this->connect_tenant($tenant);
            $processed = $this->process_data_requests_current_db($limit);
            echo $processed . ' request(s) processed' . PHP_EOL;

            if (microtime(true) - $start_time > 50) {
                echo 'Wall-clock cap reached (50s), stopping to avoid blocking next cron slot.' . PHP_EOL;
                break;
            }
        }

        $this->connect_master();
    }

    /**
     * Faz 30 (KVKK) - process pending export requests for the currently-connected database.
     * Called per-tenant by process_data_requests(), or directly in single-tenant mode.
     *
     * @param int $limit Maximum pending export requests to reserve and process.
     * @return int Number of requests processed.
     */
    private function process_data_requests_current_db(int $limit): int
    {
        $this->load->model('data_requests_model');
        $this->load->library('data_export');

        $pending = $this->data_requests_model->get_pending_exports($limit);

        foreach ($pending as $request) {
            $this->data_export->handle_queued_export($this, ['request_id' => $request['id']]);
        }

        return count($pending);
    }

    /**
     * BooKi (Dalga 3 / Faz 3.3) - list marketing segments.
     *
     * Usage: php index.php console marketing_segments [subdomain]
     */
    public function marketing_segments(string $subdomain = ''): void
    {
        $tenants = $this->console_rule_tenants($subdomain);

        foreach ($tenants as $tenant) {
            if ($tenant !== null) {
                $this->connect_tenant($tenant);
            }

            echo PHP_EOL . '=== ' . ($tenant ? 'Tenant "' . $tenant['subdomain'] . '"' : 'Standalone DB') . ' ===' . PHP_EOL;

            if (!$this->db->table_exists('marketing_segments')) {
                echo '  (marketing_segments tablosu yok — migrate gerekli)' . PHP_EOL;
                continue;
            }

            $segments = $this->db->order_by('id', 'asc')->get('marketing_segments')->result_array();

            if ($segments === []) {
                echo '  (segment yok)' . PHP_EOL;
                continue;
            }

            foreach ($segments as $seg) {
                echo sprintf(
                    "  [%s] #%d %s\n       tür: %s | üye: %s | son hesaplama: %s\n",
                    (int) $seg['enabled'] === 1 ? 'ON ' : 'OFF',
                    (int) $seg['id'],
                    $seg['name'],
                    $seg['type'],
                    (int) $seg['member_count'],
                    $seg['last_calculated'] ? $seg['last_calculated'] : 'hiç hesaplanmadı',
                );
            }
        }

        if (is_multi_tenant_mode()) {
            $this->connect_master();
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.3) - refresh segment member counts.
     *
     * Usage: php index.php console marketing_refresh [subdomain]
     */
    public function marketing_refresh(string $subdomain = ''): void
    {
        $tenants = $this->console_rule_tenants($subdomain);

        foreach ($tenants as $tenant) {
            if ($tenant !== null) {
                $this->connect_tenant($tenant);
            }

            echo PHP_EOL . '=== ' . ($tenant ? 'Tenant "' . $tenant['subdomain'] . '"' : 'Standalone DB') . ' ===' . PHP_EOL;

            if (!$this->db->table_exists('marketing_segments')) {
                echo '  (marketing_segments tablosu yok)' . PHP_EOL;
                continue;
            }

            $this->load->model('segments_model');
            $this->segments_model->refresh_all_counts();

            $segments = $this->db->order_by('id', 'asc')->get('marketing_segments')->result_array();

            foreach ($segments as $seg) {
                echo sprintf("  #%d %s → %d üye\n", (int) $seg['id'], $seg['name'], (int) $seg['member_count']);
            }
        }

        if (is_multi_tenant_mode()) {
            $this->connect_master();
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.3) - list marketing campaigns.
     *
     * Usage: php index.php console marketing_campaigns [subdomain]
     */
    public function marketing_campaigns(string $subdomain = ''): void
    {
        $tenants = $this->console_rule_tenants($subdomain);

        foreach ($tenants as $tenant) {
            if ($tenant !== null) {
                $this->connect_tenant($tenant);
            }

            echo PHP_EOL . '=== ' . ($tenant ? 'Tenant "' . $tenant['subdomain'] . '"' : 'Standalone DB') . ' ===' . PHP_EOL;

            if (!$this->db->table_exists('marketing_campaigns')) {
                echo '  (marketing_campaigns tablosu yok — migrate gerekli)' . PHP_EOL;
                continue;
            }

            $campaigns = $this->db->order_by('id', 'asc')->get('marketing_campaigns')->result_array();

            if ($campaigns === []) {
                echo '  (kampanya yok)' . PHP_EOL;
                continue;
            }

            foreach ($campaigns as $camp) {
                echo sprintf(
                    "  #%d %s\n       kanal: %s | durum: %s | gönderilen: %d/%d\n",
                    (int) $camp['id'],
                    $camp['name'],
                    $camp['channel'],
                    $camp['status'],
                    (int) $camp['sent_count'],
                    (int) $camp['total_recipients'],
                );
            }
        }

        if (is_multi_tenant_mode()) {
            $this->connect_master();
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.4) - issue a review request for one
     * completed appointment per tenant, as if the automation rule had just fired.
     *
     * Usage: php index.php console review_issue <appointment_id> [subdomain]
     *
     * The command hands the Automation Engine a synthetic enabled rule carrying
     * only the review_request action, so the review link + message go out
     * immediately instead of waiting for the 2-hour automation window.
     */
    public function review_issue(
        string $appointment_id = '',
        string $subdomain = '',
    ): void {
        $appointment_id = (int) $appointment_id;

        if ($appointment_id <= 0) {
            show_error('A positive appointment id is required.');
        }

        $tenants = $this->console_rule_tenants($subdomain);

        foreach ($tenants as $tenant) {
            if ($tenant !== null) {
                $this->connect_tenant($tenant);
            }

            $appointment = $this->appointments_model->find($appointment_id);

            if (!$appointment) {
                echo 'Appointment #' . $appointment_id . ' not found @ ' .
                    ($tenant ? $tenant['subdomain'] : 'standalone') . PHP_EOL;
                continue;
            }

            $ctx = [
                'appointment' => $appointment,
                'service' => $this->services_model->find($appointment['id_services']),
                'provider' => $this->providers_model->find($appointment['id_users_provider']),
                'customer' => $this->customers_model->find($appointment['id_users_customer']),
                'settings' => [
                    'company_name' => setting('company_name'),
                    'company_link' => setting('company_link'),
                    'company_email' => setting('company_email'),
                ],
            ];

            $this->load->library('automation_engine');

            $this->automation_engine->run([
                'id' => 0,
                'name' => 'Console review_issue',
                'event' => 'appointment_completed',
                'conditions' => '',
                'enabled' => 1,
                'actions' => json_encode([
                    ['type' => 'review_request', 'recipient' => 'customer',
                     'channels' => 'sms,whatsapp', 'subject' => '', 'text' => ''],
                ]),
            ], 'appointment_completed', $ctx);

            echo 'Review request issued for appointment #' . $appointment_id . ' @ ' .
                ($tenant ? $tenant['subdomain'] : 'standalone') . PHP_EOL;
        }

        if (is_multi_tenant_mode()) {
            $this->connect_master();
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.4) - list tenant review requests.
     *
     * Usage: php index.php console reviews list [status] [subdomain]
     *
     * Status filter: requested|pending|published|rejected (empty = all).
     */
    public function reviews(
        string $command = '',
        string $status = '',
        string $subdomain = '',
    ): void {
        if ($command !== 'list') {
            show_error('Unknown reviews subcommand "' . $command . '" - use: reviews list [status] [subdomain]');
        }

        $tenants = $this->console_rule_tenants($subdomain);

        foreach ($tenants as $tenant) {
            if ($tenant !== null) {
                $this->connect_tenant($tenant);
            }

            $this->load->model('reviews_model');

            $reviews = $this->reviews_model->get($status !== '' ? $status : null);

            echo PHP_EOL . ($tenant ? $tenant['subdomain'] : 'standalone') .
                ' - review requests: ' . count($reviews) . PHP_EOL;

            foreach ($reviews as $review) {
                echo sprintf(
                    "  #%d [%s] rating=%s token=%s appt=%s customer=%s\n",
                    (int) $review['id'],
                    $review['status'],
                    $review['rating'] !== null ? $review['rating'] : '-',
                    (string) $review['token'],
                    (int) $review['appointment_id'],
                    (string) ($review['customer_name'] ?? '-'),
                );
            }
        }

        if (is_multi_tenant_mode()) {
            $this->connect_master();
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.4) - moderate one tenant review and mirror
     * the decision to the master DB (marketplace visibility).
     *
     * Usage: php index.php console review_status <review_id> <published|rejected> [subdomain]
     */
    public function review_status(
        string $review_id = '',
        string $status = '',
        string $subdomain = '',
    ): void {
        $review_id = (int) $review_id;

        $this->load->model('reviews_model');

        if ($review_id <= 0 || !in_array($status, [
            Reviews_model::STATUS_PUBLISHED,
            Reviews_model::STATUS_REJECTED,
        ], true)) {
            show_error('Usage: review_status <id> <published|rejected> [subdomain]');
        }

        $tenants = $this->console_rule_tenants($subdomain);

        foreach ($tenants as $tenant) {
            if ($tenant !== null) {
                $this->connect_tenant($tenant);
            }

            $this->load->model('reviews_model');
            $this->load->library('review_service');

            $review = $this->reviews_model->moderate($review_id, $status, 0);

            $this->review_service->mirror_to_master($review, $status);

            echo 'Review #' . $review_id . ' -> ' . $status . ' @ ' .
                ($tenant ? $tenant['subdomain'] : 'standalone') . PHP_EOL;
        }

        if (is_multi_tenant_mode()) {
            $this->connect_master();
        }
    }

    /**
     * No-op test infrastructure bootstrap command.
     *
     * This command is used only by TenantTestCase to bootstrap the CI3 framework during test runs.
     * It is not intended for end users and should not be listed in help() output.
     */
    public function noop(): void
    {
        echo 'noop' . PHP_EOL;
    }

    /**
     * Seed demo tenants with sample data.
     *
     * BooKi (Dalga 5) - populate demo/test tenants (demo-guzellik, demo-masaj, etc.)
     * with vertical-specific services, staff, stations, and sample appointments.
     * Supports dry-run mode to preview changes before committing.
     *
     * Usage:
     *   php index.php console demo_seed hoteltest dry
     *   php index.php console demo_seed hoteltest commit
     *   php index.php console demo_seed all dry
     *   php index.php console demo_seed all commit
     *
     * Security:
     *   - Allowlist-only (PRIV_BRANCHES pattern): if subdomain is not in demo_seed_data(),
     *     the command fails immediately with no writes.
     *   - Salonflora specifically blocked (fail-closed).
     *
     * @param string $subdomain Tenant subdomain ('all' for all demo tenants) or empty for dry-run
     * @param string $mode      'dry' (preview) or 'commit' (write to DB)
     */
    public function demo_seed(string $subdomain = '', string $mode = 'dry'): void
    {
        if (!is_multi_tenant_mode()) {
            show_error('demo_seed is only available in multi-tenant mode.');
        }

        if (empty($subdomain)) {
            $subdomain = 'all';
        }

        $commit = $mode === 'commit';

        // BooKi (2026-09-17 fix) - demo_seed_data() reads setting('company_working_plan'),
        // which lives in the per-tenant ea_settings table. The master DB has NO ea_settings,
        // so building the catalog before the loop (master connection active) died with a
        // silent DB error and demo_seed never worked anywhere. Build it inside the loop,
        // right after connect_tenant(), instead.
        $targets = $subdomain === 'all' ? $this->demo_tenant_keys() : [$subdomain];

        foreach ($targets as $target) {
            if (!in_array($target, $this->demo_tenant_keys(), true)) {
                echo '⚠ Tenant "' . $target . '" not in demo catalog, skipping.' . PHP_EOL;
                continue;
            }

            // BooKi (2026-09-16 bugfix) - each loop iteration must start from the master DB, since
            // the previous iteration's connect_tenant() left $this->db pointed at that tenant's own
            // database (which has no "tenants" table) - without this, every target after the first
            // in a "demo_seed all" run fails this lookup silently.
            $this->connect_master();

            $tenant = $this->db->get_where('tenants', ['subdomain' => $target])->row_array();
            if (!$tenant) {
                echo '⚠ Tenant "' . $target . '" does not exist. Run: php index.php console tenant_create ' . $target . PHP_EOL;
                continue;
            }

            $this->connect_tenant($tenant);

        $this->load->model('service_categories_model');
            $this->load->model('services_model');
            $this->load->model('stations_model');
            $this->load->model('providers_model');
            $this->load->model('customers_model');
            $this->load->model('appointments_model');
            $this->load->model('roles_model');

            $catalog = $this->demo_seed_data(); // tenant connection active - ea_settings exists here
            $data = $catalog[$target] ?? null;

            if ($data === null) {
                echo '⚠ Tenant "' . $target . '" not in demo catalog, skipping.' . PHP_EOL;
                continue;
            }

            if (!$commit) {
                echo '📋 DRY-RUN: ' . $target . PHP_EOL;
                echo '   - Company: ' . $data['company_name'] . PHP_EOL;
                echo '   - Categories: ' . count($data['categories']) . PHP_EOL;
                echo '   - Services: ' . count($data['services']) . PHP_EOL;
                echo '   - Stations: ' . count($data['stations']) . PHP_EOL;
                echo '   - Providers: ' . count($data['providers']) . PHP_EOL;
                echo '   - Customers: ' . count($data['customers']) . PHP_EOL;
                echo '   - Appointments: ' . count($data['appointments']) . PHP_EOL;
                continue;
            }

            echo '💾 COMMIT: ' . $target . PHP_EOL;

            // Settings
            setting([
                'company_name' => $data['company_name'],
                'business_type' => $data['business_type'],
                'company_email' => 'demo-' . $target . '@kibusiness.co',
                'company_phone' => '+90 555 000 00 10',
                'company_address' => 'Demo Address, Türkiye',
                'onboarding_completed' => '1',
            ]);

            // Categories
            $category_ids = [];
            foreach ($data['categories'] as $cat_name) {
                $exists = $this->db->get_where('service_categories', ['name' => $cat_name])->row_array();
                if ($exists) {
                    $category_ids[$cat_name] = $exists['id'];
                } else {
                    $this->service_categories_model->save(['name' => $cat_name]);
                    $id = $this->db->insert_id();
                    $category_ids[$cat_name] = $id;
                }
            }

            // Services
            $service_ids = [];
            foreach ($data['services'] as $svc) {
                $existing = $this->db->get_where('services', ['name' => $svc['name']])->row_array();
                if ($existing) {
                    $service_ids[$svc['name']] = $existing['id'];
                    continue;
                }

                $save_data = [
                    'name' => $svc['name'],
                    'duration' => $svc['duration'],
                    'price' => $svc['price'],
                    'currency' => 'TRY',
                    'slot_interval' => 15,
                    'attendants_number' => $svc['attendants_number'] ?? 1,
                    'id_service_categories' => $category_ids[$svc['category']] ?? 1,
                    'description' => '',
                ];
                $this->services_model->save($save_data);
                $id = $this->db->insert_id();
                $service_ids[$svc['name']] = $id;
            }

            // Stations
            $station_ids = [];
            foreach ($data['stations'] as $station) {
                $exists = $this->db->get_where('stations', ['name' => $station])->row_array();
                if ($exists) {
                    $station_ids[$station] = $exists['id'];
                    continue;
                }

                $this->stations_model->save(['name' => $station, 'notes' => '', 'is_active' => 1, 'services' => []]);
                $id = $this->db->insert_id();
                $station_ids[$station] = $id;
            }

            // Providers
            $provider_ids = [];
            foreach ($data['providers'] as $prov_data) {
                $existing = $this->db->get_where('users', ['email' => $prov_data['email']])->row_array();
                if ($existing) {
                    $provider_ids[$prov_data['first_name']] = $existing['id'];
                    continue;
                }

                $prov_services = [];
                foreach ($prov_data['services'] as $svc_name) {
                    if (isset($service_ids[$svc_name])) {
                        $prov_services[] = $service_ids[$svc_name];
                    }
                }

                $prov_stations = [];
                foreach ($prov_data['stations'] as $sta_name) {
                    if (isset($station_ids[$sta_name])) {
                        $prov_stations[] = $station_ids[$sta_name];
                    }
                }

                $base_username = strtolower(str_replace(' ', '', trim((string) ($prov_data['first_name'] ?? ''))));
                if ($base_username === '') {
                    $base_username = 'provider';
                }

                $username = $base_username;
                $username_suffix = 2;
                while ($this->db->get_where('user_settings', ['username' => $username])->num_rows() > 0) {
                    $username = $base_username . (string) $username_suffix;
                    $username_suffix++;
                }

                $pwd = bin2hex(random_bytes(12));
                $save_data = [
                    'first_name' => $prov_data['first_name'],
                    'last_name' => $prov_data['last_name'],
                    'email' => $prov_data['email'],
                    'phone_number' => $prov_data['phone_number'],
                    'services' => $prov_services,
                    'stations' => $prov_stations,
                    'settings' => [
                        'username' => $username,
                        'password' => $pwd,
                        'working_plan' => setting('company_working_plan'),
                        'working_plan_exceptions' => '{}',
                        'notifications' => false,
                        'google_sync' => false,
                        'sync_past_days' => 30,
                        'sync_future_days' => 90,
                        'calendar_view' => 0,
                    ],
                ];
                $this->providers_model->save($save_data);
                $id = $this->db->insert_id();
                $provider_ids[$prov_data['first_name']] = $id;
            }

            // Customers
            $customer_ids = [];
            foreach ($data['customers'] as $cust) {
                $existing = $this->db->get_where('customers', ['email' => $cust['email']])->row_array();
                if ($existing) {
                    $customer_ids[$cust['first_name']] = $existing['id'];
                    continue;
                }

                $this->customers_model->save($cust);
                $id = $this->db->insert_id();
                $customer_ids[$cust['first_name']] = $id;
            }

            // Appointments (relative dates: +1 day forward)
            foreach ($data['appointments'] as $apt) {
                $prov_id = $provider_ids[$apt['provider']] ?? null;
                $cust_id = $customer_ids[$apt['customer']] ?? null;
                $svc_id = $service_ids[$apt['service']] ?? null;
                $sta_id = $station_ids[$apt['station']] ?? null;

                if (!$prov_id || !$cust_id || !$svc_id || !$sta_id) {
                    continue; // Skip if any reference is missing
                }

                $base_date = date('Y-m-d', strtotime('+1 day'));
                $apt_data = [
                    'start_datetime' => $base_date . ' ' . $apt['start_time'],
                    'end_datetime' => $base_date . ' ' . $apt['end_time'],
                    'is_unavailability' => 0,
                    'notes' => 'Demo appointment',
                    'id_users_provider' => $prov_id,
                    'id_users_customer' => $cust_id,
                    'id_services' => $svc_id,
                    'id_stations' => $sta_id,
                ];
                $this->appointments_model->save($apt_data);
            }

            echo '✓ Seeded: ' . $target . PHP_EOL;
        }

        if (is_multi_tenant_mode()) {
            $this->connect_master();
        }
    }

    /**
     * Allowlisted demo tenant subdomains. Kept as a separate static list (fail-closed
     * allowlist, salonflora deliberately NOT included) because the full catalog data
     * can only be built with an active tenant DB connection - see demo_seed().
     *
     * @return array Subdomain keys, identical to the keys of demo_seed_data().
     */
    private function demo_tenant_keys(): array
    {
        return ['demo-guzellik', 'demo-masaj', 'demo-restoran', 'demo-otel', 'demo-klinik', 'demo-studyo'];
    }

    /**
     * Return demo catalog data for all vertical-specific demo tenants.
     *
     * This is a static, allowlist-only definition. Any subdomain not in this
     * array is rejected at seed-time.
     *
     * @return array Keyed by subdomain (demo-guzellik, demo-masaj, ...), value is seed data
     */
    private function demo_seed_data(): array
    {
        $working_plan = setting('company_working_plan') ?? json_encode([
            'monday' => ['start' => '09:00', 'end' => '18:00', 'breaks' => []],
            'tuesday' => ['start' => '09:00', 'end' => '18:00', 'breaks' => []],
            'wednesday' => ['start' => '09:00', 'end' => '18:00', 'breaks' => []],
            'thursday' => ['start' => '09:00', 'end' => '18:00', 'breaks' => []],
            'friday' => ['start' => '09:00', 'end' => '18:00', 'breaks' => []],
            'saturday' => ['start' => '09:00', 'end' => '15:00', 'breaks' => []],
            'sunday' => [],
        ]);

        return [
            'demo-guzellik' => [
                'company_name' => 'Lumiere Güzellik Merkezi',
                'business_type' => 'Güzellik Salonu',
                'categories' => ['Saç', 'Cilt Bakımı', 'Tırnak'],
                'services' => [
                    ['name' => 'Saç Kesimi & Fön', 'duration' => 60, 'price' => 750, 'category' => 'Saç', 'attendants_number' => 1],
                    ['name' => 'Saç Boyası (Tek Renk)', 'duration' => 120, 'price' => 2400, 'category' => 'Saç', 'attendants_number' => 1],
                    ['name' => 'Keratin Bakım', 'duration' => 150, 'price' => 3800, 'category' => 'Saç', 'attendants_number' => 1],
                    ['name' => 'Klasik Cilt Bakımı', 'duration' => 60, 'price' => 1200, 'category' => 'Cilt Bakımı', 'attendants_number' => 1],
                    ['name' => 'Kalıcı Oje (Manikür)', 'duration' => 45, 'price' => 600, 'category' => 'Tırnak', 'attendants_number' => 1],
                ],
                'stations' => ['Koltuk 1', 'Koltuk 2', 'Bakım Odası'],
                'providers' => [
                    ['first_name' => 'Elif', 'last_name' => 'Yıldırım', 'email' => 'elif@demo-guzellik.kibusiness.co', 'phone_number' => '+90 555 000 00 21', 'services' => ['Saç Kesimi & Fön', 'Saç Boyası (Tek Renk)'], 'stations' => ['Koltuk 1', 'Koltuk 2']],
                    ['first_name' => 'Merve', 'last_name' => 'Aksoy', 'email' => 'merve@demo-guzellik.kibusiness.co', 'phone_number' => '+90 555 000 00 22', 'services' => ['Keratin Bakım', 'Klasik Cilt Bakımı'], 'stations' => ['Bakım Odası']],
                    ['first_name' => 'Zeynep', 'last_name' => 'Korkmaz', 'email' => 'zeynep@demo-guzellik.kibusiness.co', 'phone_number' => '+90 555 000 00 23', 'services' => ['Kalıcı Oje (Manikür)'], 'stations' => ['Koltuk 1']],
                ],
                'customers' => [
                    ['first_name' => 'Ayşe', 'last_name' => 'Demo', 'email' => 'demo-musteri1@demo-guzellik.kibusiness.co', 'phone_number' => '+90 555 000 00 30'],
                    ['first_name' => 'Mehmet', 'last_name' => 'Demo', 'email' => 'demo-musteri2@demo-guzellik.kibusiness.co', 'phone_number' => '+90 555 000 00 31'],
                ],
                'appointments' => [
                    ['provider' => 'Elif', 'customer' => 'Ayşe', 'service' => 'Saç Kesimi & Fön', 'station' => 'Koltuk 1', 'start_time' => '09:00', 'end_time' => '10:00'],
                    ['provider' => 'Merve', 'customer' => 'Mehmet', 'service' => 'Klasik Cilt Bakımı', 'station' => 'Bakım Odası', 'start_time' => '14:00', 'end_time' => '15:00'],
                ],
            ],
            'demo-masaj' => [
                'company_name' => 'Serene Masaj & Spa',
                'business_type' => 'Masaj Salonu/Spa',
                'categories' => ['Masaj', 'Spa Ritüelleri'],
                'services' => [
                    ['name' => 'İsveç Masajı', 'duration' => 60, 'price' => 1500, 'category' => 'Masaj', 'attendants_number' => 1],
                    ['name' => 'Derin Doku Masajı', 'duration' => 90, 'price' => 2200, 'category' => 'Masaj', 'attendants_number' => 1],
                    ['name' => 'Aromaterapi Masajı', 'duration' => 75, 'price' => 1900, 'category' => 'Masaj', 'attendants_number' => 1],
                    ['name' => 'Sıcak Taş Terapisi', 'duration' => 90, 'price' => 2500, 'category' => 'Spa Ritüelleri', 'attendants_number' => 1],
                    ['name' => 'Çift Masajı', 'duration' => 60, 'price' => 2800, 'category' => 'Spa Ritüelleri', 'attendants_number' => 2],
                ],
                'stations' => ['Masaj Odası 1', 'Masaj Odası 2', 'Çift Odası'],
                'providers' => [
                    ['first_name' => 'Deniz', 'last_name' => 'Arslan', 'email' => 'deniz@demo-masaj.kibusiness.co', 'phone_number' => '+90 555 000 00 24', 'services' => ['İsveç Masajı', 'Derin Doku Masajı'], 'stations' => ['Masaj Odası 1']],
                    ['first_name' => 'Burak', 'last_name' => 'Şentürk', 'email' => 'burak@demo-masaj.kibusiness.co', 'phone_number' => '+90 555 000 00 25', 'services' => ['Aromaterapi Masajı', 'Sıcak Taş Terapisi', 'Çift Masajı'], 'stations' => ['Masaj Odası 2', 'Çift Odası']],
                ],
                'customers' => [
                    ['first_name' => 'Ayşe', 'last_name' => 'Demo', 'email' => 'demo-musteri1@demo-masaj.kibusiness.co', 'phone_number' => '+90 555 000 00 30'],
                    ['first_name' => 'Mehmet', 'last_name' => 'Demo', 'email' => 'demo-musteri2@demo-masaj.kibusiness.co', 'phone_number' => '+90 555 000 00 31'],
                ],
                'appointments' => [
                    ['provider' => 'Deniz', 'customer' => 'Ayşe', 'service' => 'İsveç Masajı', 'station' => 'Masaj Odası 1', 'start_time' => '10:00', 'end_time' => '11:00'],
                    ['provider' => 'Burak', 'customer' => 'Mehmet', 'service' => 'Sıcak Taş Terapisi', 'station' => 'Masaj Odası 2', 'start_time' => '15:00', 'end_time' => '16:30'],
                ],
            ],
            'demo-restoran' => [
                'company_name' => 'Mavi Liman Restoran',
                'business_type' => 'Restoran',
                'categories' => ['Masa Rezervasyonu', 'Özel Etkinlik'],
                'services' => [
                    ['name' => 'Akşam Yemeği—2 Kişilik Masa', 'duration' => 120, 'price' => 0, 'category' => 'Masa Rezervasyonu', 'attendants_number' => 1],
                    ['name' => 'Akşam Yemeği—4 Kişilik Masa', 'duration' => 120, 'price' => 0, 'category' => 'Masa Rezervasyonu', 'attendants_number' => 1],
                    ['name' => 'Öğle Menüsü—2 Kişilik', 'duration' => 90, 'price' => 0, 'category' => 'Masa Rezervasyonu', 'attendants_number' => 1],
                    ['name' => 'Şef Masası Degüstasyon', 'duration' => 180, 'price' => 3500, 'category' => 'Özel Etkinlik', 'attendants_number' => 6],
                    ['name' => 'Özel Etkinlik/Grup Rezervasyonu', 'duration' => 240, 'price' => 0, 'category' => 'Özel Etkinlik', 'attendants_number' => 1],
                ],
                'stations' => ['Masa 4 (Pencere Kenarı)', 'Masa 9 (Bahçe)', 'Masa 12 (Şef Masası)'],
                'providers' => [
                    ['first_name' => 'Ahmet', 'last_name' => 'Duran', 'email' => 'ahmet@demo-restoran.kibusiness.co', 'phone_number' => '+90 555 000 00 26', 'services' => ['Akşam Yemeği—2 Kişilik Masa', 'Akşam Yemeği—4 Kişilik Masa'], 'stations' => ['Masa 4 (Pencere Kenarı)', 'Masa 9 (Bahçe)']],
                    ['first_name' => 'Selin', 'last_name' => 'Bozkurt', 'email' => 'selin@demo-restoran.kibusiness.co', 'phone_number' => '+90 555 000 00 27', 'services' => ['Şef Masası Degüstasyon', 'Özel Etkinlik/Grup Rezervasyonu'], 'stations' => ['Masa 12 (Şef Masası)']],
                ],
                'customers' => [
                    ['first_name' => 'Ayşe', 'last_name' => 'Demo', 'email' => 'demo-musteri1@demo-restoran.kibusiness.co', 'phone_number' => '+90 555 000 00 30'],
                    ['first_name' => 'Mehmet', 'last_name' => 'Demo', 'email' => 'demo-musteri2@demo-restoran.kibusiness.co', 'phone_number' => '+90 555 000 00 31'],
                ],
                'appointments' => [
                    ['provider' => 'Ahmet', 'customer' => 'Ayşe', 'service' => 'Akşam Yemeği—2 Kişilik Masa', 'station' => 'Masa 4 (Pencere Kenarı)', 'start_time' => '19:00', 'end_time' => '21:00'],
                    ['provider' => 'Selin', 'customer' => 'Mehmet', 'service' => 'Şef Masası Degüstasyon', 'station' => 'Masa 12 (Şef Masası)', 'start_time' => '20:00', 'end_time' => '23:00'],
                ],
            ],
            'demo-otel' => [
                'company_name' => 'Grand Marmara Otel',
                'business_type' => 'Otel',
                'categories' => ['Spa & Wellness', 'Toplantı & Etkinlik', 'Transfer'],
                'services' => [
                    ['name' => 'Spa Günü Paketi', 'duration' => 180, 'price' => 3200, 'category' => 'Spa & Wellness', 'attendants_number' => 1],
                    ['name' => 'Hamam & Kese Ritüeli', 'duration' => 60, 'price' => 1400, 'category' => 'Spa & Wellness', 'attendants_number' => 1],
                    ['name' => 'Toplantı Salonu—Yarım Gün', 'duration' => 240, 'price' => 6500, 'category' => 'Toplantı & Etkinlik', 'attendants_number' => 1],
                    ['name' => 'Toplantı Salonu—Saatlik', 'duration' => 60, 'price' => 2000, 'category' => 'Toplantı & Etkinlik', 'attendants_number' => 1],
                    ['name' => 'Havalimanı Transferi', 'duration' => 90, 'price' => 1800, 'category' => 'Transfer', 'attendants_number' => 1],
                ],
                'stations' => ['Spa Suiti A', 'Toplantı Salonu "Boğaz"', 'Hamam'],
                'providers' => [
                    ['first_name' => 'Canan', 'last_name' => 'Erdoğan', 'email' => 'canan@demo-otel.kibusiness.co', 'phone_number' => '+90 555 000 00 28', 'services' => ['Spa Günü Paketi', 'Hamam & Kese Ritüeli'], 'stations' => ['Spa Suiti A', 'Hamam']],
                    ['first_name' => 'Kerem', 'last_name' => 'Yalçın', 'email' => 'kerem@demo-otel.kibusiness.co', 'phone_number' => '+90 555 000 00 29', 'services' => ['Toplantı Salonu—Yarım Gün', 'Toplantı Salonu—Saatlik', 'Havalimanı Transferi'], 'stations' => ['Toplantı Salonu "Boğaz"']],
                ],
                'customers' => [
                    ['first_name' => 'Ayşe', 'last_name' => 'Demo', 'email' => 'demo-musteri1@demo-otel.kibusiness.co', 'phone_number' => '+90 555 000 00 30'],
                    ['first_name' => 'Mehmet', 'last_name' => 'Demo', 'email' => 'demo-musteri2@demo-otel.kibusiness.co', 'phone_number' => '+90 555 000 00 31'],
                ],
                'appointments' => [
                    ['provider' => 'Canan', 'customer' => 'Ayşe', 'service' => 'Spa Günü Paketi', 'station' => 'Spa Suiti A', 'start_time' => '10:00', 'end_time' => '13:00'],
                    ['provider' => 'Kerem', 'customer' => 'Mehmet', 'service' => 'Toplantı Salonu—Yarım Gün', 'station' => 'Toplantı Salonu "Boğaz"', 'start_time' => '14:00', 'end_time' => '18:00'],
                ],
            ],
            'demo-klinik' => [
                'company_name' => 'Vita Sağlık Kliniği',
                'business_type' => 'Klinik/Sağlık',
                'categories' => ['Poliklinik', 'Diş', 'Fizik Tedavi'],
                'services' => [
                    ['name' => 'Dahiliye Muayenesi', 'duration' => 30, 'price' => 1500, 'category' => 'Poliklinik', 'attendants_number' => 1],
                    ['name' => 'Diş Kontrolü & Temizliği', 'duration' => 45, 'price' => 2000, 'category' => 'Diş', 'attendants_number' => 1],
                    ['name' => 'Fizik Tedavi Seansı', 'duration' => 45, 'price' => 1200, 'category' => 'Fizik Tedavi', 'attendants_number' => 1],
                    ['name' => 'Beslenme & Diyet Danışmanlığı', 'duration' => 60, 'price' => 1000, 'category' => 'Poliklinik', 'attendants_number' => 1],
                    ['name' => 'Kontrol Muayenesi', 'duration' => 20, 'price' => 750, 'category' => 'Poliklinik', 'attendants_number' => 1],
                ],
                'stations' => ['Muayene Odası 1', 'Diş Ünitesi', 'Tedavi Odası'],
                'providers' => [
                    ['first_name' => 'Ayşe', 'last_name' => 'Demir', 'email' => 'ayse@demo-klinik.kibusiness.co', 'phone_number' => '+90 555 000 00 32', 'services' => ['Dahiliye Muayenesi', 'Beslenme & Diyet Danışmanlığı'], 'stations' => ['Muayene Odası 1']],
                    ['first_name' => 'Mert', 'last_name' => 'Kaya', 'email' => 'mert@demo-klinik.kibusiness.co', 'phone_number' => '+90 555 000 00 33', 'services' => ['Diş Kontrolü & Temizliği'], 'stations' => ['Diş Ünitesi']],
                    ['first_name' => 'Gizem', 'last_name' => 'Uçar', 'email' => 'gizem@demo-klinik.kibusiness.co', 'phone_number' => '+90 555 000 00 34', 'services' => ['Fizik Tedavi Seansı'], 'stations' => ['Tedavi Odası']],
                ],
                'customers' => [
                    ['first_name' => 'Ayşe', 'last_name' => 'Demo', 'email' => 'demo-musteri1@demo-klinik.kibusiness.co', 'phone_number' => '+90 555 000 00 30'],
                    ['first_name' => 'Mehmet', 'last_name' => 'Demo', 'email' => 'demo-musteri2@demo-klinik.kibusiness.co', 'phone_number' => '+90 555 000 00 31'],
                ],
                'appointments' => [
                    ['provider' => 'Ayşe', 'customer' => 'Ayşe', 'service' => 'Dahiliye Muayenesi', 'station' => 'Muayene Odası 1', 'start_time' => '09:30', 'end_time' => '10:00'],
                    ['provider' => 'Mert', 'customer' => 'Mehmet', 'service' => 'Diş Kontrolü & Temizliği', 'station' => 'Diş Ünitesi', 'start_time' => '11:00', 'end_time' => '11:45'],
                ],
            ],
            'demo-studyo' => [
                'company_name' => 'Pulse Stüdyo & Fitness',
                'business_type' => 'Stüdyo/Fitness',
                'categories' => ['Grup Dersleri', 'Kişisel Antrenman'],
                'services' => [
                    ['name' => 'Reformer Pilates', 'duration' => 50, 'price' => 900, 'category' => 'Grup Dersleri', 'attendants_number' => 6],
                    ['name' => 'Yoga Akışı', 'duration' => 60, 'price' => 600, 'category' => 'Grup Dersleri', 'attendants_number' => 10],
                    ['name' => 'Kişisel Antrenman', 'duration' => 60, 'price' => 1800, 'category' => 'Kişisel Antrenman', 'attendants_number' => 1],
                    ['name' => 'Fonksiyonel HIIT', 'duration' => 45, 'price' => 700, 'category' => 'Grup Dersleri', 'attendants_number' => 8],
                    ['name' => 'Vücut Analizi & Program Çıkarma', 'duration' => 30, 'price' => 500, 'category' => 'Kişisel Antrenman', 'attendants_number' => 1],
                ],
                'stations' => ['Stüdyo A (Reformer)', 'Stüdyo B (Grup)', 'Serbest Ağırlık Alanı'],
                'providers' => [
                    ['first_name' => 'Cem', 'last_name' => 'Özkan', 'email' => 'cem@demo-studyo.kibusiness.co', 'phone_number' => '+90 555 000 00 35', 'services' => ['Reformer Pilates', 'Kişisel Antrenman'], 'stations' => ['Stüdyo A (Reformer)']],
                    ['first_name' => 'Nazlı', 'last_name' => 'Türkmen', 'email' => 'nazli@demo-studyo.kibusiness.co', 'phone_number' => '+90 555 000 00 36', 'services' => ['Yoga Akışı', 'Fonksiyonel HIIT'], 'stations' => ['Stüdyo B (Grup)']],
                ],
                'customers' => [
                    ['first_name' => 'Ayşe', 'last_name' => 'Demo', 'email' => 'demo-musteri1@demo-studyo.kibusiness.co', 'phone_number' => '+90 555 000 00 30'],
                    ['first_name' => 'Mehmet', 'last_name' => 'Demo', 'email' => 'demo-musteri2@demo-studyo.kibusiness.co', 'phone_number' => '+90 555 000 00 31'],
                ],
                'appointments' => [
                    ['provider' => 'Cem', 'customer' => 'Ayşe', 'service' => 'Kişisel Antrenman', 'station' => 'Stüdyo A (Reformer)', 'start_time' => '07:00', 'end_time' => '08:00'],
                    ['provider' => 'Nazlı', 'customer' => 'Mehmet', 'service' => 'Yoga Akışı', 'station' => 'Stüdyo B (Grup)', 'start_time' => '18:00', 'end_time' => '19:00'],
                ],
            ],
        ];
    }

    /**
     * Show help information about the console capabilities.
     *
     * Use this method to see the available commands.
     *
     * Usage:
     *
     * php index.php console help
     */
    public function help(): void
    {
        $help = [
            '',
            'BooKi ' . config('version'),
            '',
            'Usage:',
            '',
            '⇾ php index.php console [command] [arguments]',
            '',
            'Commands:',
            '',
            '⇾ php index.php console migrate',
            '⇾ php index.php console migrate fresh',
            '⇾ php index.php console migrate up',
            '⇾ php index.php console migrate down',
            '⇾ php index.php console seed',
            '⇾ php index.php console install',
            '⇾ php index.php console backup',
            '⇾ php index.php console sync',
            '⇾ php index.php console cleanup    (cleans sessions, logs, cache, and customer data)',
            '⇾ php index.php console process_jobs [queue] [limit]',
            '⇾ php index.php console process_data_requests [limit]',
            '⇾ php index.php console marketing_segments [subdomain]',
            '⇾ php index.php console marketing_refresh [subdomain]',
            '⇾ php index.php console marketing_campaigns [subdomain]',
            '⇾ php index.php console review_issue <appointment_id> [subdomain]',
            '⇾ php index.php console reviews list [status] [subdomain]',
            '⇾ php index.php console review_status <review_id> <published|rejected> [subdomain]',
            '⇾ php index.php console demo_seed [subdomain|all] [dry|commit]',
            '',
            '',
        ];

        response(implode(PHP_EOL, $help));
    }
}
