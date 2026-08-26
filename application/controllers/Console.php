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
        $this->load->dbforge(); // Ki Reservation (2026-08-26) - used by master_install()

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
     * Use this method to install Ki Reservation directly from the terminal.
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
     * Use this method to upgrade an Ki Reservation instance to the latest database state.
     *
     * Notice:
     *
     * Do not use this method to install the app as it will not seed the database with the initial entries (admin,
     * provider, service, settings etc.).
     *
     * Ki Reservation (2026-08-26) - multi-tenant aware: if the connected 'default' DB is a master DB
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
     * Ki Reservation (2026-08-26) - create the master DB schema (`tenants`, `tenant_migration_log`).
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

        // Ki Reservation (2026-08-26) - SaaS admin panel (reservationadmin.kibusiness.co) support.
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
        ];

        foreach ($tenant_columns as $column => $spec) {
            if (!$this->db->field_exists($column, 'tenants')) {
                $this->dbforge->add_column('tenants', [$column => $spec]);
                echo 'Added "tenants.' . $column . '" column.' . PHP_EOL;
            }
        }
    }

    /**
     * Ki Reservation (2026-08-26) - create a SaaS super-admin account (reservationadmin.kibusiness.co
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
     * Ki Reservation (2026-08-26) - provision a brand-new tenant: creates its database, generates
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
     * Ki Reservation (2026-08-26) - one-off, re-runnable COPY (never a move) of Salon Flora's live
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

        $category_id_map = [];

        foreach ($old_categories as $category) {
            $category_id_map[$category['id']] = $this->service_categories_model->save([
                'name' => $category['name'],
                'description' => $category['description'],
            ]);
        }

        $service_id_map = [];

        foreach ($old_services as $service) {
            $service_id_map[$service['id']] = $this->services_model->save([
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
            $station_id_map[$station['id']] = $this->stations_model->save([
                'name' => $station['name'],
                'notes' => $station['notes'],
                'is_active' => $station['is_active'],
                'services' => [], // fail-open in the source too (empty stations_services there)
            ]);
        }

        $provider_id_map = [];

        foreach ($old_providers as $provider) {
            $new_service_ids = array_map(
                fn($id) => $service_id_map[$id] ?? null,
                $provider_service_ids[$provider['id']] ?? [],
            );

            $new_station_ids = array_map(
                fn($id) => $station_id_map[$id] ?? null,
                $provider_station_ids[$provider['id']] ?? [],
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
                        // Ki Reservation (2026-08-26) - the old bcrypt hash cannot be carried over as-is:
                        // Providers_model::insert() always re-hashes whatever is in 'password' as if it
                        // were plaintext. A migrated provider gets this fixed temporary password instead
                        // (reported to the user, must be changed on first login) - their username stays
                        // the same as on the live system.
                        'username' => $provider['username'],
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
     * Ki Reservation (2026-08-26) - point (or clear) a tenant's custom domain in the master DB.
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
     * Ki Reservation (2026-08-26) - swap $this->db to a tenant's own database AND set
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

        // Ki Reservation (2026-08-26) - CRITICAL: dbforge is bound to whatever $this->db WAS at the
        // moment it was (lazily) loaded and does NOT follow later $this->db swaps on its own (unlike
        // models/migrations, which resolve $this->db dynamically via CI_Model/CI_Migration's __get
        // magic method proxying to get_instance()->db on every access). Without this explicit
        // reload, a migration's CREATE TABLE could silently run against the PREVIOUS tenant's
        // database instead of the one just connected to.
        $this->load->dbforge();

        // Ki Reservation (2026-08-26) - CI_Migration normally auto-creates its "migrations" tracking
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
     * Ki Reservation (2026-08-26) - reconnect $this->db to the master DB ('default' connection group)
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
     * Use this method to back up your Ki Reservation data.
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
     * Use this method in a cronjob to automatically sync events between Ki Reservation and Google Calendar.
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
        // Ki Reservation (2026-08-26) - multi-tenant aware, same pattern as migrate(): iterate every
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
     * Ki Reservation (2026-08-26) - the original sync() body, run against whatever $this->db
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
        // Ki Reservation (2026-08-26) - multi-tenant aware, same pattern as sync()/migrate().
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
            'Ki Reservation ' . config('version'),
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
            '',
            '',
        ];

        response(implode(PHP_EOL, $help));
    }
}
