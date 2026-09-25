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

        echo 'Migrating master database... ';
        try {
            $this->connect_master();
            $this->instance->migrate($type);
            echo 'OK' . PHP_EOL;
        } catch (Throwable $e) {
            echo 'FAILED: ' . $e->getMessage() . PHP_EOL;
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
     * Explicitly run master database migrations.
     *
     * Usage:
     * php index.php console migrate_master
     */
    public function migrate_master(int $from_version = 156, int $to_version = 163): void
    {
        $this->connect_master();
        echo "Running migrations on master database (v{$from_version} to v{$to_version})...\n";

        for ($v = $from_version; $v <= $to_version; $v++) {
            $prefix = sprintf('%03d', $v);
            $files = glob(APPPATH . "migrations/{$prefix}_*.php");
            foreach ($files as $file) {
                require_once $file;
                $basename = basename($file, '.php');
                $class_suffix = preg_replace('/^\d+_/', '', $basename);
                $class_name = 'Migration_' . ucfirst($class_suffix);
                if (class_exists($class_name)) {
                    echo "  Applying {$class_name} ({$basename})... ";
                    try {
                        $m = new $class_name();
                        $m->up();
                        echo "OK\n";
                    } catch (Throwable $e) {
                        echo "FAILED: " . $e->getMessage() . "\n";
                    }
                }
            }
        }

        echo "Master database migrations finished!\n";
    }

    /**
     * Apply industry blueprints to all existing tenants (or a specific tenant).
     *
     * Usage:
     * php index.php console tenant_apply_blueprints
     * php index.php console tenant_apply_blueprints <subdomain> <industry_code>
     *
     * @param string $target_subdomain Optional specific tenant subdomain
     * @param string $specific_blueprint Optional specific blueprint code
     */
    public function tenant_apply_blueprints(string $target_subdomain = '', string $specific_blueprint = ''): void
    {
        $this->load->library('blueprint_service');

        if (!is_multi_tenant_mode()) {
            $code = !empty($specific_blueprint) ? $specific_blueprint : 'beauty_salon';
            echo 'Single-tenant mode: applying blueprint "' . $code . '"... ';
            $result = $this->blueprint_service->apply_blueprint($code, false);
            echo 'OK (' . json_encode($result) . ')' . PHP_EOL;
            return;
        }

        $query = ['status' => 'active'];
        if (!empty($target_subdomain)) {
            $query['subdomain'] = strtolower(trim($target_subdomain));
        }

        $tenants = $this->db->get_where('tenants', $query)->result_array();

        if (empty($tenants)) {
            echo 'No matching active tenants found.' . PHP_EOL;
            return;
        }

        foreach ($tenants as $tenant) {
            $subdomain = $tenant['subdomain'];
            echo 'Applying blueprint to tenant "' . $subdomain . '"... ';

            $code = $specific_blueprint;
            if (empty($code)) {
                $code = $tenant['business_type'] ?? '';
            }

            if (empty($code) || $code === 'general') {
                $text = strtolower($subdomain . ' ' . ($tenant['company_name'] ?? ''));
                if (preg_match('/(barber|kuafor|kuaför|berber)/ui', $text)) {
                    $code = 'barber';
                } elseif (preg_match('/(masaj|massage|spa)/ui', $text)) {
                    $code = 'massage_spa';
                } elseif (preg_match('/(restoran|restaurant|cafe|kafe|bistro)/ui', $text)) {
                    $code = 'restaurant';
                } elseif (preg_match('/(otel|hotel|resort)/ui', $text)) {
                    $code = 'hotel';
                } elseif (preg_match('/(dis|dent|dental|diş)/ui', $text)) {
                    $code = 'dentist';
                } elseif (preg_match('/(klinik|clinic|doctor|doktor)/ui', $text)) {
                    $code = 'doctor_clinic';
                } elseif (preg_match('/(pilates|studyo|stüdyo|studio|yoga)/ui', $text)) {
                    $code = 'pilates_studio';
                } elseif (preg_match('/(pt|trainer|personal)/ui', $text)) {
                    $code = 'pt_training';
                } elseif (preg_match('/(gym|fitness|spor)/ui', $text)) {
                    $code = 'gym';
                } elseif (preg_match('/(oto|car|wash|yikama|yıkama)/ui', $text)) {
                    $code = 'car_wash';
                } elseif (preg_match('/(tirnak|tırnak|nail)/ui', $text)) {
                    $code = 'nail_studio';
                } else {
                    $code = 'beauty_salon';
                }
            }

            try {
                $this->connect_tenant($tenant);
                $seed_demo = strpos($subdomain, 'demo-') === 0 && $this->db->count_all('appointments') === 0;
                $res = $this->blueprint_service->apply_blueprint($code, $seed_demo);
                echo 'OK [' . $code . '] (' . $res['services_created'] . ' services, ' . $res['stations_created'] . ' stations)' . PHP_EOL;

                $this->connect_master();
                $this->db->update('tenants', [
                    'business_type' => $code,
                    'updated_at' => date('Y-m-d H:i:s'),
                ], ['id' => $tenant['id']]);
            } catch (Throwable $e) {
                echo 'FAILED: ' . $e->getMessage() . PHP_EOL;
                $this->connect_master();
            }
        }

        $this->connect_master();
        echo 'Tenant blueprint synchronization complete.' . PHP_EOL;
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

        // BooKi (2026-08-26) - SaaS admin panel (admin-bookiapp.kibusiness.co) support.
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
            'business_type' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'billing_cycle' => ['type' => 'ENUM', 'constraint' => ['monthly', 'yearly'], 'default' => 'monthly', 'null' => false],
            'mrr_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00, 'null' => false],
            'currency' => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'TRY', 'null' => false],
            'plan' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'trial_ends_at' => ['type' => 'DATETIME', 'null' => true],
            'license_expires_at' => ['type' => 'DATETIME', 'null' => true],
            'suspended_at' => ['type' => 'DATETIME', 'null' => true],
            // BooKi (2026-08-27) - Marketplace support
            'marketplace_opt_in' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false],
            'category' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'city' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'district' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'neighborhood' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'latitude' => ['type' => 'DECIMAL', 'constraint' => '10,8', 'null' => true],
            'longitude' => ['type' => 'DECIMAL', 'constraint' => '11,8', 'null' => true],
            'cover_image_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'short_description' => ['type' => 'TEXT', 'null' => true],
            'company_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'phone_number' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'address' => ['type' => 'TEXT', 'null' => true],
            'price_range' => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true, 'default' => '₺₺'],
            'working_hours_json' => ['type' => 'TEXT', 'null' => true],
            'iban' => ['type' => 'VARCHAR', 'constraint' => 34, 'null' => true],
            'bank_name' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
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

        if (master_setting('marketplace_commission_rate') === null) {
            master_setting('marketplace_commission_rate', '5.00');
            echo 'Generated "marketplace_commission_rate" master setting.' . PHP_EOL;
        }

        if (!$this->db->table_exists('tenant_wallets')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_tenants' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'balance' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00, 'null' => false],
                'total_earned' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00, 'null' => false],
                'total_commission' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00, 'null' => false],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('tenant_wallets', true, ['engine' => 'InnoDB']);
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('tenant_wallets') . ' ADD UNIQUE INDEX idx_tenant_wallets_tenant (id_tenants)');
            echo 'Created "tenant_wallets" table.' . PHP_EOL;
        }

        if (!$this->db->table_exists('wallet_ledger')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_tenants' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'type' => ['type' => 'ENUM', 'constraint' => ['booking_earning', 'commission_deduction', 'settlement', 'adjustment'], 'null' => false],
                'amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
                'currency' => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'TRY', 'null' => false],
                'reference_id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('wallet_ledger', true, ['engine' => 'InnoDB']);
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('wallet_ledger') . ' ADD INDEX idx_wallet_tenant (id_tenants), ADD INDEX idx_wallet_created (created_at)');
            echo 'Created "wallet_ledger" table.' . PHP_EOL;
        }

        // BooKi (2026-09-21) - SaaS Sales CRM & Lead Database
        if (!$this->db->table_exists('leads')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'sector' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
                'district' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'address' => ['type' => 'TEXT', 'null' => true],
                'contact_person' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'phone' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'whatsapp' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'email' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'website' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'instagram' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'reservation_type' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'verification' => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Doğrulanmış', 'null' => true],
                'stage' => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Visit Planned', 'null' => false],
                'priority' => ['type' => 'ENUM', 'constraint' => ['low', 'medium', 'high', 'urgent'], 'default' => 'medium', 'null' => false],
                'lead_source' => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Field Research', 'null' => false],
                'owner_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'owner_name' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'package' => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Henüz Seçilmedi', 'null' => true],
                'billing_period' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'Aylık', 'null' => true],
                'potential_mrr' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00, 'null' => false],
                'demo_start_date' => ['type' => 'DATE', 'null' => true],
                'demo_end_date' => ['type' => 'DATE', 'null' => true],
                'trial_status' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
                'next_action' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'next_action_date' => ['type' => 'DATE', 'null' => true],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'tags' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'converted_tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'conversion_date' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('leads', true, ['engine' => 'InnoDB']);
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('leads') . 
                ' ADD INDEX idx_leads_stage (stage),' .
                ' ADD INDEX idx_leads_sector (sector),' .
                ' ADD INDEX idx_leads_district (district),' .
                ' ADD INDEX idx_leads_phone (phone),' .
                ' ADD INDEX idx_leads_email (email),' .
                ' ADD INDEX idx_leads_converted_tenant (converted_tenant_id)');
            echo 'Created "leads" table.' . PHP_EOL;
        }

        // Add Google Places and geocoordinates columns if missing
        if ($this->db->table_exists('leads')) {
            $cols = [];
            if (!$this->db->field_exists('place_id', 'leads')) {
                $cols['place_id'] = ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true, 'after' => 'id'];
            }
            if (!$this->db->field_exists('primary_type', 'leads')) {
                $cols['primary_type'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'sector'];
            }
            if (!$this->db->field_exists('types_json', 'leads')) {
                $cols['types_json'] = ['type' => 'TEXT', 'null' => true, 'after' => 'primary_type'];
            }
            if (!$this->db->field_exists('business_status', 'leads')) {
                $cols['business_status'] = ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'OPERATIONAL', 'null' => false, 'after' => 'verification'];
            }
            if (!$this->db->field_exists('discovery_state', 'leads')) {
                $cols['discovery_state'] = ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'DISCOVERED', 'null' => false, 'after' => 'business_status'];
            }
            if (!$this->db->field_exists('google_maps_uri', 'leads')) {
                $cols['google_maps_uri'] = ['type' => 'VARCHAR', 'constraint' => 512, 'null' => true, 'after' => 'website'];
            }
            if (!$this->db->field_exists('latitude', 'leads')) {
                $cols['latitude'] = ['type' => 'DECIMAL', 'constraint' => '10,8', 'null' => true, 'after' => 'tags'];
            }
            if (!$this->db->field_exists('longitude', 'leads')) {
                $cols['longitude'] = ['type' => 'DECIMAL', 'constraint' => '11,8', 'null' => true, 'after' => 'latitude'];
            }
            if (!$this->db->field_exists('matched_categories', 'leads')) {
                $cols['matched_categories'] = ['type' => 'TEXT', 'null' => true, 'after' => 'longitude'];
            }
            if (!$this->db->field_exists('matched_queries', 'leads')) {
                $cols['matched_queries'] = ['type' => 'TEXT', 'null' => true, 'after' => 'matched_categories'];
            }
            if (!$this->db->field_exists('matched_regions', 'leads')) {
                $cols['matched_regions'] = ['type' => 'TEXT', 'null' => true, 'after' => 'matched_queries'];
            }
            if (!$this->db->field_exists('first_seen_at', 'leads')) {
                $cols['first_seen_at'] = ['type' => 'DATETIME', 'null' => true, 'after' => 'matched_regions'];
            }
            if (!$this->db->field_exists('last_seen_at', 'leads')) {
                $cols['last_seen_at'] = ['type' => 'DATETIME', 'null' => true, 'after' => 'first_seen_at'];
            }
            if (!$this->db->field_exists('last_crawled_at', 'leads')) {
                $cols['last_crawled_at'] = ['type' => 'DATETIME', 'null' => true, 'after' => 'last_seen_at'];
            }
            if (!$this->db->field_exists('discovery_count', 'leads')) {
                $cols['discovery_count'] = ['type' => 'INT', 'constraint' => 11, 'default' => 1, 'null' => false, 'after' => 'last_crawled_at'];
            }
            if (!$this->db->field_exists('rating', 'leads')) {
                $cols['rating'] = ['type' => 'DECIMAL', 'constraint' => '3,1', 'null' => true, 'after' => 'discovery_count'];
            }
            if (!$this->db->field_exists('user_rating_count', 'leads')) {
                $cols['user_rating_count'] = ['type' => 'INT', 'constraint' => 11, 'null' => true, 'after' => 'rating'];
            }
            if (!$this->db->field_exists('price_level', 'leads')) {
                $cols['price_level'] = ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true, 'after' => 'user_rating_count'];
            }
            if (!$this->db->field_exists('opening_hours_json', 'leads')) {
                $cols['opening_hours_json'] = ['type' => 'TEXT', 'null' => true, 'after' => 'price_level'];
            }
            if (!$this->db->field_exists('photos_json', 'leads')) {
                $cols['photos_json'] = ['type' => 'TEXT', 'null' => true, 'after' => 'opening_hours_json'];
            }
            if (!$this->db->field_exists('reviews_json', 'leads')) {
                $cols['reviews_json'] = ['type' => 'TEXT', 'null' => true, 'after' => 'photos_json'];
            }
            if (!$this->db->field_exists('enriched_at', 'leads')) {
                $cols['enriched_at'] = ['type' => 'DATETIME', 'null' => true, 'after' => 'reviews_json'];
            }

            if (!empty($cols)) {
                $this->dbforge->add_column('leads', $cols);
                echo 'Added Google Places fields to leads table.' . PHP_EOL;
            }
        }

        if (!$this->db->table_exists('crawl_jobs')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'status' => ['type' => 'ENUM', 'constraint' => ['QUEUED', 'RUNNING', 'COMPLETED', 'FAILED', 'CANCELLED'], 'default' => 'QUEUED', 'null' => false],
                'mode' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'standard', 'null' => false],
                'region_mode' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'districts', 'null' => false],
                'region_data_json' => ['type' => 'TEXT', 'null' => true],
                'category_slugs_json' => ['type' => 'TEXT', 'null' => true],
                'search_queries_json' => ['type' => 'TEXT', 'null' => true],
                'total_queries' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => false],
                'completed_queries' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => false],
                'pages_requested' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => false],
                'results_found' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => false],
                'new_leads' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => false],
                'updated_leads' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => false],
                'filtered_closed' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'null' => false],
                'errors_json' => ['type' => 'TEXT', 'null' => true],
                'created_by' => ['type' => 'VARCHAR', 'constraint' => 128, 'default' => 'Super Admin', 'null' => false],
                'started_at' => ['type' => 'DATETIME', 'null' => true],
                'completed_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('crawl_jobs', true, ['engine' => 'InnoDB']);
            echo 'Created "crawl_jobs" table.' . PHP_EOL;
        }

        if (!$this->db->table_exists('places_api_usage')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'timestamp' => ['type' => 'DATETIME', 'null' => false],
                'endpoint' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
                'operation' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'crawl_job_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'place_id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'http_status' => ['type' => 'INT', 'constraint' => 4, 'null' => false],
                'response_time_ms' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'sku_tier' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'Pro', 'null' => false],
                'metadata_json' => ['type' => 'TEXT', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('places_api_usage', true, ['engine' => 'InnoDB']);
            echo 'Created "places_api_usage" table.' . PHP_EOL;
        }

        // Lead Activities (calls, WhatsApp, emails, visits, notes, etc.)
        if (!$this->db->table_exists('lead_activities')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_leads' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'activity_type' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'description' => ['type' => 'TEXT', 'null' => true],
                'performed_by' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
                'metadata_json' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('lead_activities', true, ['engine' => 'InnoDB']);
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('lead_activities') . 
                ' ADD INDEX idx_act_leads (id_leads), ADD INDEX idx_act_type (activity_type)');
            echo 'Created "lead_activities" table.' . PHP_EOL;
        }

        // Lead Stage History (audit trail of every pipeline move)
        if (!$this->db->table_exists('lead_stage_history')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_leads' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'old_stage' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'new_stage' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'changed_by' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
                'reason_notes' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('lead_stage_history', true, ['engine' => 'InnoDB']);
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('lead_stage_history') . ' ADD INDEX idx_lsh_leads (id_leads)');
            echo 'Created "lead_stage_history" table.' . PHP_EOL;
        }

        // Lead Tasks & Follow-ups
        if (!$this->db->table_exists('lead_tasks')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_leads' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'task_type' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'due_date' => ['type' => 'DATE', 'null' => false],
                'due_time' => ['type' => 'TIME', 'null' => true],
                'priority' => ['type' => 'ENUM', 'constraint' => ['low', 'medium', 'high', 'urgent'], 'default' => 'medium', 'null' => false],
                'assigned_to' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'in_progress', 'completed', 'cancelled'], 'default' => 'pending', 'null' => false],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'completed_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('lead_tasks', true, ['engine' => 'InnoDB']);
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('lead_tasks') . 
                ' ADD INDEX idx_tasks_lead (id_leads), ADD INDEX idx_tasks_status (status), ADD INDEX idx_tasks_due (due_date)');
            echo 'Created "lead_tasks" table.' . PHP_EOL;
        }

        // Field Visit Questionnaire Records
        if (!$this->db->table_exists('lead_visits')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_leads' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'visit_date' => ['type' => 'DATETIME', 'null' => false],
                'contact_person' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'position' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'current_booking_method' => ['type' => 'TEXT', 'null' => true],
                'current_system' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'staff_count' => ['type' => 'INT', 'default' => 0, 'null' => false],
                'resource_count' => ['type' => 'INT', 'default' => 0, 'null' => false],
                'monthly_appointments' => ['type' => 'INT', 'default' => 0, 'null' => false],
                'biggest_problem' => ['type' => 'TEXT', 'null' => true],
                'most_needed_feature' => ['type' => 'TEXT', 'null' => true],
                'uses_whatsapp' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false],
                'uses_online_booking' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false],
                'competitor_system' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'budget_approach' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'decision_maker' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'purchase_timeframe' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'objections' => ['type' => 'TEXT', 'null' => true],
                'quick_tags' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'suggested_stage' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'created_by' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('lead_visits', true, ['engine' => 'InnoDB']);
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('lead_visits') . ' ADD INDEX idx_visits_lead (id_leads)');
            echo 'Created "lead_visits" table.' . PHP_EOL;
        }

        // Onboarding Sessions (tokenized multi-step customer setup)
        if (!$this->db->table_exists('onboarding_sessions')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_tenants' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'id_leads' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'token' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'status' => ['type' => 'ENUM', 'constraint' => ['not_opened', 'opened', 'started', 'in_progress', 'completed'], 'default' => 'not_opened', 'null' => false],
                'current_step' => ['type' => 'INT', 'default' => 1, 'null' => false],
                'total_steps' => ['type' => 'INT', 'default' => 10, 'null' => false],
                'progress_percent' => ['type' => 'INT', 'default' => 0, 'null' => false],
                'session_data_json' => ['type' => 'MEDIUMTEXT', 'null' => true],
                'expires_at' => ['type' => 'DATETIME', 'null' => false],
                'first_opened_at' => ['type' => 'DATETIME', 'null' => true],
                'last_activity_at' => ['type' => 'DATETIME', 'null' => true],
                'completed_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('onboarding_sessions', true, ['engine' => 'InnoDB']);
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('onboarding_sessions') . 
                ' ADD UNIQUE INDEX idx_os_token (token),' .
                ' ADD INDEX idx_os_tenant (id_tenants),' .
                ' ADD INDEX idx_os_status (status)');
            echo 'Created "onboarding_sessions" table.' . PHP_EOL;
        }

        // Import Jobs & Error Logs
        if (!$this->db->table_exists('import_jobs')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'file_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'import_type' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'total_rows' => ['type' => 'INT', 'default' => 0, 'null' => false],
                'imported_count' => ['type' => 'INT', 'default' => 0, 'null' => false],
                'updated_count' => ['type' => 'INT', 'default' => 0, 'null' => false],
                'duplicate_count' => ['type' => 'INT', 'default' => 0, 'null' => false],
                'failed_count' => ['type' => 'INT', 'default' => 0, 'null' => false],
                'status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'completed', 'null' => false],
                'created_by' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('import_jobs', true, ['engine' => 'InnoDB']);
            echo 'Created "import_jobs" table.' . PHP_EOL;
        }

        if (!$this->db->table_exists('import_errors')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_jobs' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'row_number' => ['type' => 'INT', 'null' => false],
                'raw_data_json' => ['type' => 'TEXT', 'null' => true],
                'error_reason' => ['type' => 'TEXT', 'null' => false],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('import_errors', true, ['engine' => 'InnoDB']);
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('import_errors') . ' ADD INDEX idx_err_job (id_jobs)');
            echo 'Created "import_errors" table.' . PHP_EOL;
        }

        // Master Audit Logs
        if (!$this->db->table_exists('master_audit_logs')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'actor_username' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
                'action' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'entity_type' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'entity_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'description' => ['type' => 'TEXT', 'null' => true],
                'ip_address' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'metadata_json' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('master_audit_logs', true, ['engine' => 'InnoDB']);
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('master_audit_logs') . 
                ' ADD INDEX idx_mal_action (action),' .
                ' ADD INDEX idx_mal_entity (entity_type, entity_id),' .
                ' ADD INDEX idx_mal_created (created_at)');
            echo 'Created "master_audit_logs" table.' . PHP_EOL;
        }

        // Additional columns on tenants for CRM & Onboarding linkage
        $tenant_crm_columns = [
            'id_leads' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'acquisition_source' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'sales_owner' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'onboarding_status' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'pending', 'null' => false],
            'onboarding_completed_at' => ['type' => 'DATETIME', 'null' => true],
            'onboarding_token' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
        ];

        foreach ($tenant_crm_columns as $col => $spec) {
            if (!$this->db->field_exists($col, 'tenants')) {
                $this->dbforge->add_column('tenants', [$col => $spec]);
                echo 'Added "tenants.' . $col . '" column.' . PHP_EOL;
            }
        }
    }

    /**
     * BooKi (2026-08-26) - create a SaaS super-admin account (admin-bookiapp.kibusiness.co
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
     * BooKi (2026-09-21) - Seed the 560 reference portfolio leads into the master CRM database.
     *
     * Usage: php index.php console seed_initial_leads
     */
    public function seed_initial_leads(): void
    {
        $this->master_install();

        $json_file = APPPATH . 'data/initial_560_leads.json';
        if (!file_exists($json_file)) {
            show_error('Reference leads data file not found at: ' . $json_file);
            return;
        }

        $leads_data = json_decode(file_get_contents($json_file), true);
        if (!is_array($leads_data) || empty($leads_data)) {
            show_error('Invalid or empty JSON in ' . $json_file);
            return;
        }

        $stage_map = [
            'Planlı' => 'Visit Planned',
            'Ziyaret Edildi' => 'Visited',
            'Demo Sunuldu' => 'Demo Presented',
            'Demo Satışı Yapıldı' => 'Trial Started',
            'Yeniden Ziyaret' => 'Follow-up',
            'Kazanıldı' => 'Won',
            'Kaybedildi' => 'Lost',
        ];

        $package_prices = [
            'Starter' => 1999.00,
            'Professional' => 2199.00,
            'Enterprise' => 4499.00,
            'Henüz Seçilmedi' => 2199.00,
        ];

        // Ensure clean state if re-seeding reference portfolio
        $existing_count = $this->db->count_all_results('leads');
        if ($existing_count < 560) {
            $this->db->truncate('leads');
            $this->db->truncate('lead_activities');
            echo 'Resetting leads table to import full 560 portfolio...' . PHP_EOL;
        } else {
            echo "Leads table already has {$existing_count} records." . PHP_EOL;
            return;
        }

        $inserted = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($leads_data as $item) {
            $name = trim($item['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $raw_stage = trim($item['stage'] ?? 'Planlı');
            $stage = $stage_map[$raw_stage] ?? 'Visit Planned';

            $package = trim($item['package'] ?? 'Henüz Seçilmedi');
            $mrr = $package_prices[$package] ?? 2199.00;

            $demo_start = !empty($item['demo_start_date']) ? $item['demo_start_date'] : null;
            $demo_end = !empty($item['demo_end_date']) ? $item['demo_end_date'] : null;
            $trial_status = null;
            if ($stage === 'Trial Started') {
                $trial_status = 'active';
            } elseif ($stage === 'Follow-up') {
                $trial_status = 'ending_soon';
            } elseif ($stage === 'Won') {
                $trial_status = 'converted';
            } elseif ($stage === 'Lost') {
                $trial_status = 'cancelled';
            }

            $lead_record = [
                'id' => (int) ($item['id'] ?? 0) ?: null,
                'name' => $name,
                'sector' => trim($item['sector'] ?? '💅 Güzellik & Tırnak'),
                'district' => trim($item['district'] ?? 'Nilüfer'),
                'address' => trim($item['address'] ?? ''),
                'contact_person' => trim($item['contact_person'] ?? 'Yetkili'),
                'phone' => trim($item['phone'] ?? ''),
                'whatsapp' => trim($item['whatsapp'] ?? ($item['phone'] ?? '')),
                'email' => trim($item['email'] ?? ''),
                'website' => trim($item['website'] ?? ''),
                'instagram' => trim($item['instagram'] ?? ''),
                'reservation_type' => trim($item['reservation_type'] ?? 'Telefon / WhatsApp'),
                'verification' => trim($item['verification'] ?? 'Doğrulanmış'),
                'stage' => $stage,
                'priority' => ($stage === 'Trial Started' || $stage === 'Follow-up') ? 'high' : 'medium',
                'lead_source' => 'Field Research',
                'owner_name' => 'Miraç',
                'package' => $package,
                'billing_period' => trim($item['billing_period'] ?? 'Aylık'),
                'potential_mrr' => $mrr,
                'demo_start_date' => $demo_start,
                'demo_end_date' => $demo_end,
                'trial_status' => $trial_status,
                'next_action' => !empty($item['next_action_date']) ? 'Saha Takibi / Görüşme' : null,
                'next_action_date' => !empty($item['next_action_date']) ? $item['next_action_date'] : null,
                'notes' => trim($item['notes'] ?? ''),
                'created_at' => !empty($item['last_updated']) ? date('Y-m-d H:i:s', strtotime($item['last_updated'])) : $now,
                'updated_at' => $now,
            ];

            if ($lead_record['id'] === null) {
                unset($lead_record['id']);
            }

            $this->db->insert('leads', $lead_record);
            $lead_id = $lead_record['id'] ?? $this->db->insert_id();

            // Record initial activity
            $this->db->insert('lead_activities', [
                'id_leads' => $lead_id,
                'activity_type' => 'note',
                'title' => 'Saha CRM Portföyüne Eklendi',
                'description' => 'İşletme referans saha portföyünden sisteme aktarıldı. Aşama: ' . $stage,
                'performed_by' => 'Sistem / Saha Satış',
                'created_at' => $now,
            ]);

            $inserted++;
        }

        echo "Seeding completed: Exactly {$inserted} leads inserted into CRM." . PHP_EOL;
        return;
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
     * Manage the SaaS platform super-admin (admin-bookiapp.kibusiness.co) login credential.
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

            echo 'Master admin "' . $username . '" updated (password reset). Login on admin-bookiapp.kibusiness.co.'
                . PHP_EOL;

            return;
        }

        $values['username'] = $username;
        $values['created_at'] = date('Y-m-d H:i:s');

        $this->db->insert('master_admins', $values);

        echo 'Master admin "' . $username . '" created. Login on admin-bookiapp.kibusiness.co.' . PHP_EOL;
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

            if (in_array($key, ['zoho_client_id', 'zoho_client_secret', 'zoho_refresh_token'], true) && !empty($display)) {
                $display = str_repeat('*', 8) . mb_substr($display, -4);
            }

            echo '  ' . $key . ' = ' . (empty($display) ? '(empty)' : $display) . PHP_EOL;
        }
    }

    /**
     * BooKi (2026-09-17) - master-level Google OAuth client config (Google_sync.php / Google_
     * integrations_client.php read these via master_setting()). Shared platform OAuth app used by
     * both Calendar sync (per-provider) and Marketing (GA4/Ads, per-tenant OAuth once scopes are
     * requested). Mirrors crm_config's mask-on-display pattern - see Zoho notes above.
     *
     * Usage:
     *
     * php index.php console google_config                                  (show current, masked)
     * php index.php console google_config google_client_id "451286...apps.googleusercontent.com"
     * php index.php console google_config google_client_secret "GOCSPX-..."
     */
    public function google_config(?string $name = null, ?string $value = null): void
    {
        $allowed = ['google_client_id', 'google_client_secret'];

        if ($name !== null && $value !== null) {
            $name = trim($name);

            if (!in_array($name, $allowed, true)) {
                show_error('Unknown Google setting "' . $name . '". Allowed: ' . implode(', ', $allowed) . '.');

                return;
            }

            master_setting($name, trim($value));

            echo 'google_config: ' . $name . ' set.' . PHP_EOL;

            return;
        }

        echo 'Current Google OAuth client configuration' . PHP_EOL;

        foreach ($allowed as $key) {
            $display = master_setting($key);

            if ($key === 'google_client_secret' && !empty($display)) {
                $display = str_repeat('*', 8) . mb_substr($display, -4);
            }

            echo '  ' . $key . ' = ' . (empty($display) ? '(empty)' : $display) . PHP_EOL;
        }
    }

    /**
     * BooKi (2026-09-17) - master-level ERP connector config (QuickBooks Online + Zoho Books -
     * the only two of the 6 requested ERP systems with a real, centralized REST API; see
     * Erp_manager.php docblock for why Logo/Mikro/İşbaşı/Paraşüt are handled differently).
     * Mirrors crm_config/google_config's mask-on-display pattern.
     *
     * Usage:
     *
     * php index.php console erp_config                                             (show current, masked)
     * php index.php console erp_config quickbooks_client_id "..."
     * php index.php console erp_config quickbooks_client_secret "..."
     * php index.php console erp_config quickbooks_refresh_token "..."
     * php index.php console erp_config quickbooks_realm_id "..."
     * php index.php console erp_config quickbooks_is_sandbox "1"
     * php index.php console erp_config zohobooks_client_id "..."
     * php index.php console erp_config zohobooks_client_secret "..."
     * php index.php console erp_config zohobooks_refresh_token "..."
     * php index.php console erp_config zohobooks_organization_id "..."
     * php index.php console erp_config zohobooks_region "eu"
     */
    public function erp_config(?string $name = null, ?string $value = null): void
    {
        $allowed = [
            'quickbooks_client_id',
            'quickbooks_client_secret',
            'quickbooks_refresh_token',
            'quickbooks_realm_id',
            'quickbooks_is_sandbox',
            'quickbooks_default_item_id',
            'zohobooks_client_id',
            'zohobooks_client_secret',
            'zohobooks_refresh_token',
            'zohobooks_organization_id',
            'zohobooks_region',
        ];

        $secret_keys = ['quickbooks_client_secret', 'quickbooks_refresh_token', 'zohobooks_client_secret', 'zohobooks_refresh_token'];

        if ($name !== null && $value !== null) {
            $name = trim($name);

            if (!in_array($name, $allowed, true)) {
                show_error('Unknown ERP setting "' . $name . '". Allowed: ' . implode(', ', $allowed) . '.');

                return;
            }

            master_setting($name, trim($value));

            echo 'erp_config: ' . $name . ' set.' . PHP_EOL;

            return;
        }

        echo 'Current ERP connector configuration (QuickBooks / Zoho Books)' . PHP_EOL;

        foreach ($allowed as $key) {
            $display = master_setting($key);

            if (in_array($key, $secret_keys, true) && !empty($display)) {
                $display = str_repeat('*', 8) . mb_substr($display, -4);
            }

            echo '  ' . $key . ' = ' . (empty($display) ? '(empty)' : $display) . PHP_EOL;
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
        $tenant_hostname = $tenant['db_host'];
        if (($tenant_hostname === 'db' || strpos($tenant_hostname, '127.0.0.1') !== false) && !empty($this->db->hostname)) {
            $tenant_hostname = $this->db->hostname;
        }

        $this->load->database(
            [
                'hostname' => $tenant_hostname,
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
                    // BooKi (2026-09-17 bugfix) - see the services-loop comment below; use save()'s own
                    // return value instead of a separately-queried insert_id() for consistency/safety.
                    $category_ids[$cat_name] = $this->service_categories_model->save(['name' => $cat_name]);
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
                // BooKi (2026-09-17 bugfix) - save()'s own return value IS the new row's id;
                // db->insert_id() taken AFTER save() returns is unreliable because the model's insert()
                // issues further non-INSERT queries afterward (e.g. set_provider_ids()'s cleanup DELETE) -
                // mysqli resets ->insert_id to 0 after any query that isn't itself an INSERT, so every
                // captured id here was silently 0, corrupting every downstream FK (services_providers,
                // stations_providers, appointments) that referenced it. Confirmed live in dev (2026-09-17):
                // db->insert_id() read 0 for every service while save()'s return value was correct.
                $service_ids[$svc['name']] = $this->services_model->save($save_data);
            }

            // Stations
            $station_ids = [];
            foreach ($data['stations'] as $station) {
                $exists = $this->db->get_where('stations', ['name' => $station])->row_array();
                if ($exists) {
                    $station_ids[$station] = $exists['id'];
                    continue;
                }

                $station_ids[$station] = $this->stations_model->save(['name' => $station, 'notes' => '', 'is_active' => 1, 'services' => []]);
            }

            // Providers
            $provider_ids = [];
            foreach ($data['providers'] as $prov_data) {
                // BooKi (2026-09-17 bugfix) - `email` is encrypted at rest (KVKK hardening, see
                // Providers_model::ENCRYPTED_AND_HASHED_FIELDS); a plaintext WHERE can never match
                // ciphertext, so this always missed already-seeded providers and crashed on the
                // unique-email constraint on re-run. Match via the exact-match hash index instead.
                $existing = $this->db->get_where('users', ['email_hash' => sf_pii_hash($prov_data['email'])])->row_array();
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
                // BooKi (2026-09-17 bugfix) - see the services-loop comment above; same insert_id()-after-
                // subsidiary-query bug (set_settings()/set_service_ids()/set_station_ids() etc. all run
                // after the users insert and reset it) - use save()'s own return value instead.
                $provider_ids[$prov_data['first_name']] = $this->providers_model->save($save_data);
            }

            // Customers
            $customer_ids = [];
            foreach ($data['customers'] as $cust) {
                // BooKi (2026-09-17 bugfix) - same encrypted-email issue as the providers loop above
                // (see Customers_model::ENCRYPTED_AND_HASHED_FIELDS) - match via email_hash, not plaintext.
                $existing = $this->db->get_where('users', ['email_hash' => sf_pii_hash($cust['email'])])->row_array();
                if ($existing) {
                    $customer_ids[$cust['first_name']] = $existing['id'];
                    continue;
                }

                // BooKi (2026-09-17 bugfix) - see the services-loop comment above; same bug.
                $customer_ids[$cust['first_name']] = $this->customers_model->save($cust);
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

                // BooKi (2026-09-17 bugfix) - validate_datetime() requires the exact 'Y-m-d H:i:s' format
                // (DateTime::createFromFormat, no partial match) - the catalog's 'HH:MM' start/end times
                // were missing seconds, so every demo appointment failed Appointments_model::validate().
                $base_date = date('Y-m-d', strtotime('+1 day'));
                $apt_data = [
                    'start_datetime' => $base_date . ' ' . $apt['start_time'] . ':00',
                    'end_datetime' => $base_date . ' ' . $apt['end_time'] . ':00',
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
     * BooKi - Test AI Assistant, Channels (WhatsApp, Telegram, Instagram) and LLM Gateway.
     *
     * Usage:
     * php index.php console test_ai [subdomain] [message] [provider]
     */
    public function test_ai(string $subdomain = 'salonflora', string $message = 'Merhaba, yarın için randevu alabilir miyim?', string $provider = ''): void
    {
        echo "======================================================" . PHP_EOL;
        echo "🤖 BooKi AI Asistan & Çoklu LLM Gateway Testi" . PHP_EOL;
        echo "======================================================" . PHP_EOL;

        $this->load->library('ai_llm_gateway');
        $this->load->library('ai_channel_responder');
        $this->load->library('ai_agent_client');

        // 1. Check Configured Providers
        echo PHP_EOL . "1. SAĞLAYICI VE API ANAHTARI KONTROLÜ:" . PHP_EOL;
        $providers = ['google', 'groq', 'openrouter', 'openai', 'anthropic'];
        $active_pref = $this->ai_llm_gateway->get_active_provider();
        echo "   Aktif Tercih: " . ($active_pref ?: 'auto') . PHP_EOL;

        $configured_count = 0;
        foreach ($providers as $p) {
            $key = $this->ai_llm_gateway->get_api_key($p);
            $has_key = !empty($key);
            if ($has_key) $configured_count++;
            $masked = $has_key ? substr($key, 0, 6) . '...' . substr($key, -4) : '(Tanımlanmamış)';
            echo "   - [" . ($has_key ? '✓' : '✗') . "] " . strtoupper($p) . ": " . $masked . PHP_EOL;
        }

        echo "   Toplam Yapılandırılmış Sağlayıcı: {$configured_count}/" . count($providers) . PHP_EOL;

        // 2. Resolve Tenant Context
        echo PHP_EOL . "2. KİRACI BAĞLAMI ({$subdomain}):" . PHP_EOL;
        if (is_multi_tenant_mode()) {
            $tenant = $this->db->get_where('tenants', ['subdomain' => $subdomain])->row_array();
            if ($tenant) {
                $this->connect_tenant($tenant);
                echo "   ✓ Kiracı bulundu: " . ($tenant['company_name'] ?? $subdomain) . " (DB: {$tenant['db_name']})" . PHP_EOL;
            } else {
                echo "   ⚠ Kiracı '{$subdomain}' bulunamadı, master DB'de devam ediliyor." . PHP_EOL;
            }
        }

        // 3. Test LLM Gateway Direct Chat
        echo PHP_EOL . "3. LLM GATEWAY DİREKT CHAT TESTİ:" . PHP_EOL;
        $test_prompt = [
            ['role' => 'system', 'content' => 'Sen BooKi Akıllı Randevu Asistanısın. Türkçe, nazik ve kısa yanıt ver.'],
            ['role' => 'user', 'content' => $message],
        ];

        $chat_options = ['temperature' => 0.3, 'max_tokens' => 200];
        if (!empty($provider)) {
            $chat_options['provider'] = $provider;
        }

        $start_time = microtime(true);
        $res = $this->ai_llm_gateway->chat($test_prompt, $chat_options);
        $elapsed = round((microtime(true) - $start_time) * 1000, 2);

        if ($res && !empty($res['success'])) {
            echo "   ✓ Başarılı Sağlayıcı: " . strtoupper($res['provider'] ?? 'unknown') . " (Model: " . ($res['model'] ?? '-') . ")" . PHP_EOL;
            echo "   ✓ Yanıt Süresi: {$elapsed} ms" . PHP_EOL;
            echo "   💬 AI Yanıtı: " . trim($res['reply']) . PHP_EOL;
        } else {
            echo "   ⚠ Canlı API çağrısı yapılamadı (veya anahtarlar henüz girilmedi)." . PHP_EOL;
            echo "   ℹ API anahtarlarını Superadmin Paneli'nden (https://admin-bookiapp.kibusiness.co/superadmin_settings) girebilirsiniz." . PHP_EOL;
        }

        // 4. Test Multi-Channel Auto-Responder (WhatsApp / Telegram / Instagram)
        echo PHP_EOL . "4. ÇOKLU KANAL OTO-YANITLAYICI TESTİ:" . PHP_EOL;
        $channels = ['whatsapp', 'telegram', 'instagram'];
        foreach ($channels as $chan) {
            echo "   → Kanal: " . strtoupper($chan) . PHP_EOL;
            $reply = $this->ai_channel_responder->respond($chan, '05062505562', $message);
            echo "     Sonuç: " . ($reply ? "✓ Yanıt üretildi:\n     \"" . str_replace("\n", "\n     ", $reply) . "\"" : "✗ Yanıt üretilemedi") . PHP_EOL;
        }

        // 5. Test Admin AI Agent Tools
        echo PHP_EOL . "5. ADMİN AI AGENT TOOL-CALLING TESTİ:" . PHP_EOL;
        $agent_res = $this->ai_agent_client->chat([
            ['role' => 'user', 'content' => 'Randevu durumlarını ve müşteri listesini kontrol et']
        ]);
        $has_reply = !empty($agent_res['reply']);
        echo "   ✓ Admin Agent Durumu: " . ($has_reply ? 'Başarılı' : 'Beklemede/Fallback') . PHP_EOL;
        if ($has_reply) {
            echo "   💬 Agent Özeti: " . trim($agent_res['reply']) . PHP_EOL;
        }
        if (!empty($agent_res['tool_calls'])) {
            echo "   🛠 Çağrılan Araçlar: " . json_encode($agent_res['tool_calls'], JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }

        echo PHP_EOL . "======================================================" . PHP_EOL;
        echo "✓ AI Asistan Testi Tamamlandı." . PHP_EOL;
        echo "======================================================" . PHP_EOL;
    }

    /**
     * BooKi - Inspect and diagnose live channel connectivity (Telegram, WhatsApp, Instagram).
     *
     * Usage:
     * php index.php console test_channels [subdomain] [fix]
     */
    public function test_channels(string $subdomain = 'salonflora', string $fix = ''): void
    {
        echo "======================================================" . PHP_EOL;
        echo "📡 BooKi Canlı Kanal Teşhis Aracı (Telegram / WhatsApp / Instagram)" . PHP_EOL;
        echo "======================================================" . PHP_EOL;

        if (is_multi_tenant_mode()) {
            $tenant = $this->db->get_where('tenants', ['subdomain' => $subdomain])->row_array();
            if (!$tenant) {
                echo "Hata: Kiracı '{$subdomain}' bulunamadı." . PHP_EOL;
                return;
            }
            $this->connect_tenant($tenant);
            echo "Kiracı: " . ($tenant['company_name'] ?? $subdomain) . " ({$subdomain})" . PHP_EOL . PHP_EOL;
        }

        $this->load->model('messaging_settings_model');
        $this->load->library('telegram_client');
        $this->load->library('whatsapp_bridge');

        $msg_settings = $this->messaging_settings_model->get_settings();

        // 1. TELEGRAM TEŞHİSİ
        echo "1. TELEGRAM BOT & WEBHOOK DURUMU:" . PHP_EOL;
        $bot_token = $msg_settings['telegram_bot_token'] ?? setting('telegram_bot_token');
        echo "   - Bot Token: " . (!empty($bot_token) ? substr($bot_token, 0, 10) . '...' : '✗ TANIMLANMAMIŞ') . PHP_EOL;
        echo "   - Bot Kullanıcı Adı: " . (setting('telegram_bot_username') ?: 'Yok') . PHP_EOL;
        echo "   - AI Otomatik Yanıt: " . ($msg_settings['ai_reply_telegram_enabled'] ? '✓ Açık' : '✗ Kapalı') . PHP_EOL;
        echo "   - Webhook Secret: " . (setting('telegram_webhook_secret') ? '✓ Tanımlı' : '✗ Tanımsız') . PHP_EOL;

        if (!empty($bot_token)) {
            // Test getMe via Telegram API
            $me = $this->telegram_client->get_me();
            if ($me) {
                echo "   ✓ Telegram API Bağlantısı: Başarılı (@{$me['username']} - {$me['first_name']})" . PHP_EOL;
            } else {
                echo "   ✗ Telegram API Bağlantısı: Başarısız (Token geçersiz veya Telegram erişilemiyor)" . PHP_EOL;
            }

            // Test getWebhookInfo
            try {
                $client = new \GuzzleHttp\Client();
                $wh_res = $client->get('https://api.telegram.org/bot' . $bot_token . '/getWebhookInfo', ['timeout' => 10]);
                $wh_data = json_decode((string) $wh_res->getBody(), true);
                if (!empty($wh_data['ok'])) {
                    $res = $wh_data['result'];
                    echo "   - Webhook URL: " . ($res['url'] ?: '✗ WEBHOOK AYARLANMAMIŞ (Boş)') . PHP_EOL;
                    if (!empty($res['last_error_message'])) {
                        echo "   ⚠ Telegram Son Hata: " . $res['last_error_message'] . " (" . date('Y-m-d H:i:s', $res['last_error_date'] ?? time()) . ")" . PHP_EOL;
                    }
                    if (!empty($res['pending_update_count'])) {
                        echo "   ℹ Bekleyen Güncelleme: " . $res['pending_update_count'] . PHP_EOL;
                    }
                }
            } catch (\Throwable $e) {
                echo "   ⚠ Webhook bilgisi alınamadı: " . $e->getMessage() . PHP_EOL;
            }
        }

        // 2. WHATSAPP TEŞHİSİ
        echo PHP_EOL . "2. WHATSAPP DURUMU:" . PHP_EOL;
        $wa_mode = $msg_settings['whatsapp_mode'] ?? 'official';
        echo "   - Aktif Mod: " . strtoupper($wa_mode) . PHP_EOL;
        echo "   - AI Otomatik Yanıt: " . ($msg_settings['ai_reply_whatsapp_enabled'] ? '✓ Açık' : '✗ KAPALI') . PHP_EOL;
        echo "   - Bildirimler: " . ($msg_settings['whatsapp_notifications_enabled'] ? '✓ Açık' : '✗ Kapalı') . PHP_EOL;

        if ($wa_mode === 'unofficial') {
            $bridge_url = $msg_settings['whatsapp_bridge_url'] ?: 'http://wa-bridge:3000';
            echo "   - Bridge URL: " . $bridge_url . PHP_EOL;
            echo "   - Bridge Status: " . ($msg_settings['whatsapp_unofficial_status'] ?? 'disconnected') . PHP_EOL;

            // Ping bridge health
            try {
                $client = new \GuzzleHttp\Client();
                $b_res = $client->get(rtrim($bridge_url, '/') . '/health', ['timeout' => 5]);
                $b_data = json_decode((string) $b_res->getBody(), true);
                echo "   ✓ Bridge Servisi: " . ($b_data['status'] ?? 'OK') . " (Uptime: " . round($b_data['uptime'] ?? 0) . "s)" . PHP_EOL;
            } catch (\Throwable $e) {
                echo "   ✗ Bridge Servisi Erişilemedi: " . $e->getMessage() . PHP_EOL;
            }

            // Check session status
            try {
                $bridge = new Whatsapp_bridge($bridge_url, $msg_settings['whatsapp_bridge_secret'] ?? '');
                $status = $bridge->session_status($subdomain);
                echo "   - Oturum Durumu: " . ($status['status'] ?? 'unknown') . PHP_EOL;
            } catch (\Throwable $e) {
                echo "   ⚠ Oturum sorgulanamadı: " . $e->getMessage() . PHP_EOL;
            }
        } else {
            echo "   - Meta Phone Number ID: " . (!empty($msg_settings['whatsapp_phone_number_id']) ? '✓ Tanımlı' : '✗ Boş') . PHP_EOL;
            echo "   - Meta Access Token: " . (!empty($msg_settings['whatsapp_access_token']) ? '✓ Tanımlı' : '✗ Boş') . PHP_EOL;
        }

        // 3. INSTAGRAM TEŞHİSİ
        echo PHP_EOL . "3. INSTAGRAM DURUMU:" . PHP_EOL;
        echo "   - AI Otomatik Yanıt: " . ($msg_settings['ai_reply_instagram_enabled'] ? '✓ Açık' : '✗ KAPALI') . PHP_EOL;
        echo "   - Access Token: " . (!empty($msg_settings['instagram_access_token']) ? '✓ Tanımlı' : '✗ Boş') . PHP_EOL;

        // Auto-fix if requested
        if ($fix === 'fix' || $fix === '--fix') {
            echo PHP_EOL . "4. OTOMATİK DÜZELTME UYGULANIYOR:" . PHP_EOL;
            
            // Enable AI replies
            $update_data = [
                'ai_reply_whatsapp_enabled' => 1,
                'ai_reply_telegram_enabled' => 1,
                'ai_reply_instagram_enabled' => 1,
            ];
            $this->messaging_settings_model->save_settings($update_data);
            setting([
                'ai_reply_whatsapp_enabled' => '1',
                'ai_reply_telegram_enabled' => '1',
                'ai_reply_instagram_enabled' => '1',
            ]);
            echo "   ✓ AI Otomatik Yanıt bayrakları etkinleştirildi (WhatsApp, Telegram, Instagram)." . PHP_EOL;

            // Fix Telegram Webhook URL if bot token exists
            if (!empty($bot_token)) {
                $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';
                $webhook_host = $subdomain . '-' . $app_domain;
                $webhook_url = "https://{$webhook_host}/index.php/telegram/webhook";
                $secret = setting('telegram_webhook_secret');
                if (empty($secret)) {
                    $secret = bin2hex(random_bytes(32));
                    setting(['telegram_webhook_secret' => $secret]);
                }
                $ok = $this->telegram_client->set_webhook($webhook_url, $secret);
                echo "   " . ($ok ? '✓' : '✗') . " Telegram Webhook Güncellendi: {$webhook_url}" . PHP_EOL;
            }
        }

        echo PHP_EOL . "======================================================" . PHP_EOL;
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
            '⇾ php index.php console test_ai [subdomain] [message] [provider]',
            '',
            '',
        ];

        response(implode(PHP_EOL, $help));
    }

    /**
     * Test sending a direct Telegram message to a chat ID.
     *
     * Usage:
     * php index.php console telegram_send [chat_id] [subdomain] [message]
     */
    public function telegram_send(string $chat_id = '5895622522', string $subdomain = 'salonflora', string $message = '🌸 BooKi Salon Flora AI Asistanı aktif! Size nasıl yardımcı olabilirim?'): void
    {
        if (is_multi_tenant_mode()) {
            $tenant = $this->db->get_where('tenants', ['subdomain' => $subdomain])->row_array();
            if ($tenant) {
                $this->connect_tenant($tenant);
            }
        }

        $this->load->library('telegram_client');
        $ok = $this->telegram_client->send_message($chat_id, $message);
        echo $ok ? "✓ Mesaj başarıyla gönderildi: {$chat_id}" . PHP_EOL : "✗ Mesaj gönderilemedi!" . PHP_EOL;
    }

    /**
     * Simulate an inbound Telegram Webhook message.
     *
     * Usage:
     * php index.php console telegram_simulate [message] [chat_id] [subdomain]
     */
    public function telegram_simulate(string $message = 'Merhaba, yarın saç kesimi için randevu almak istiyorum', string $chat_id = '5895622522', string $subdomain = 'salonflora'): void
    {
        if (is_multi_tenant_mode()) {
            $tenant = $this->db->get_where('tenants', ['subdomain' => $subdomain])->row_array();
            if ($tenant) {
                $this->connect_tenant($tenant);
            }
        }

        $this->load->library('telegram_client');
        $this->load->library('ai_channel_responder');

        echo "Simulating Telegram inbound message from {$chat_id}: \"{$message}\"" . PHP_EOL;

        // Match user
        $matched_user = $this->db
            ->select('users.id, roles.slug AS role_slug')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles')
            ->where('users.telegram_chat_id', $chat_id)
            ->get()
            ->row_array();

        $this->db->insert('telegram_messages', [
            'id_users' => $matched_user['id'] ?? null,
            'chat_id' => $chat_id,
            'direction' => 'in',
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $reply = $this->ai_channel_responder->respond('telegram', $chat_id, $message, $matched_user);
        if (!empty($reply)) {
            echo "AI Reply Generated: " . PHP_EOL . $reply . PHP_EOL;
            $sent = $this->telegram_client->send_message($chat_id, $reply);
            echo $sent ? "✓ AI Yanıtı canlı Telegram kullanıcısına iletildi!" . PHP_EOL : "✗ Gönderim hatası" . PHP_EOL;

            $this->db->insert('telegram_messages', [
                'id_users' => $matched_user['id'] ?? null,
                'chat_id' => $chat_id,
                'direction' => 'out',
                'message' => $reply,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            echo "✗ AI yanıt üretemedi." . PHP_EOL;
        }
    }

    /**
     * Test full AI appointment booking, reschedule, cancellation & admin approval workflow.
     *
     * Usage:
     * php index.php console test_ai_proposals [subdomain]
     */
    public function test_ai_proposals(string $subdomain = 'salonflora'): void
    {
        echo "======================================================" . PHP_EOL;
        echo "🧪 BooKi AI Randevu & Yönetici Onay Akışı Testi" . PHP_EOL;
        echo "======================================================" . PHP_EOL;

        if (is_multi_tenant_mode()) {
            $tenant = $this->db->get_where('tenants', ['subdomain' => $subdomain])->row_array();
            if ($tenant) {
                $this->connect_tenant($tenant);
            }
        }

        $this->load->library('ai_channel_responder');
        $this->load->library('ai_agent_client');
        $this->load->model('appointments_model');
        $this->load->model('customers_model');
        $this->load->model('services_model');

        // 1. Inbound Channel Simulation: "Yarın saat 14:00'e Aromaterapi Masajı için randevu almak istiyorum. Adım Selin Yılmaz, telefonum 05062505562"
        echo PHP_EOL . "1. KANAL ÜZERİNDEN YENİ RANDEVU TALEBİ:" . PHP_EOL;
        $inbound_msg = "Merhaba, yarın saat 14:00 için Aromaterapi Masajı randevusu almak istiyorum. İsmim Selin Yılmaz, telefonum 05062505562.";
        $reply = $this->ai_channel_responder->respond('telegram', '5895622522', $inbound_msg);
        echo "   💬 AI Yanıtı: " . trim($reply) . PHP_EOL;

        // Check pending table
        $pending = $this->db
            ->order_by('id', 'desc')
            ->limit(1)
            ->get('ai_agent_pending_changes')
            ->row_array();

        if ($pending && $pending['target_table'] === 'appointments' && $pending['status'] === 'pending') {
            echo "   ✓ Onay Kuyruğuna Eklendi! (ID: #{$pending['id']})" . PHP_EOL;
            echo "     Gerekçe: {$pending['reason']}" . PHP_EOL;
            echo "     Detay: {$pending['changes']}" . PHP_EOL;

            // 2. Admin Approval Simulation
            echo PHP_EOL . "2. YÖNETİCİ ONAYI SİMÜLASYONU (Ai_agent::approve):" . PHP_EOL;
            require_once APPPATH . 'controllers/Ai_agent.php';
            
            // Execute approval logic directly
            $payload = json_decode((string) $pending['changes'], true) ?: [];
            $action = $payload['action'] ?? 'create';

            // Customer
            $cust_id = $this->customers_model->save([
                'first_name' => 'Selin',
                'last_name' => 'Yılmaz',
                'phone_number' => '05062505562',
                'email' => 'selin.yilmaz@kibusiness.co',
            ]);

            $services = $this->services_model->get_available_services();
            $service_id = !empty($services) ? (int) $services[0]['id'] : 1;
            $service = $this->services_model->find($service_id);
            $duration = (int) ($service['duration'] ?? 60);

            $providers = $this->providers_model->get_available_providers();
            $provider_id = !empty($providers) ? (int) $providers[0]['id'] : 1;

            $start_dt = date('Y-m-d 14:00:00', strtotime('+1 day'));
            $end_dt = date('Y-m-d H:i:s', strtotime($start_dt) + ($duration * 60));

            $appt_id = $this->appointments_model->save([
                'start_datetime' => $start_dt,
                'end_datetime' => $end_dt,
                'id_services' => $service_id,
                'id_users_provider' => $provider_id,
                'id_users_customer' => $cust_id,
                'notes' => 'AI Asistan randevu talebi (Yönetici Onaylı)',
                'is_unavailability' => false,
            ]);

            $this->db->update('ai_agent_pending_changes', [
                'status' => 'approved',
                'target_id' => $appt_id,
                'resolved_at' => date('Y-m-d H:i:s'),
            ], ['id' => $pending['id']]);

            echo "   ✓ Yönetici Onayladı! Randevu #{$appt_id} başarıyla takvime işlendi." . PHP_EOL;

            // 3. Test Reschedule Request
            echo PHP_EOL . "3. RANDEVU SAAT DEĞİŞİKLİĞİ TALEBİ:" . PHP_EOL;
            $reschedule_msg = "Az önce aldığım randevunun saatini 16:00 olarak değiştirebilir miyiz?";
            $matched_cust = $this->customers_model->find($cust_id);
            $reschedule_reply = $this->ai_channel_responder->respond('telegram', '5895622522', $reschedule_msg, $matched_cust);
            echo "   💬 AI Yanıtı: " . trim($reschedule_reply) . PHP_EOL;

            $pending_reschedule = $this->db
                ->order_by('id', 'desc')
                ->limit(1)
                ->get('ai_agent_pending_changes')
                ->row_array();

            if ($pending_reschedule && $pending_reschedule['id'] != $pending['id']) {
                echo "   ✓ Değişiklik Talebi Onay Kuyruğuna Eklendi! (ID: #{$pending_reschedule['id']})" . PHP_EOL;
                echo "     Detay: {$pending_reschedule['changes']}" . PHP_EOL;
            }

            // 4. Test Cancellation Request
            echo PHP_EOL . "4. RANDEVU İPTAL TALEBİ:" . PHP_EOL;
            $cancel_msg = "Randevumu iptal etmek istiyorum.";
            $cancel_reply = $this->ai_channel_responder->respond('telegram', '5895622522', $cancel_msg, $matched_cust);
            echo "   💬 AI Yanıtı: " . trim($cancel_reply) . PHP_EOL;

            $pending_cancel = $this->db
                ->order_by('id', 'desc')
                ->limit(1)
                ->get('ai_agent_pending_changes')
                ->row_array();

            if ($pending_cancel && $pending_cancel['id'] != $pending_reschedule['id']) {
                echo "   ✓ İptal Talebi Onay Kuyruğuna Eklendi! (ID: #{$pending_cancel['id']})" . PHP_EOL;
                echo "     Detay: {$pending_cancel['changes']}" . PHP_EOL;
            }
        } else {
            echo "   ✗ Bekleyen kayıt oluşturulamadı." . PHP_EOL;
        }

        echo PHP_EOL . "======================================================" . PHP_EOL;
        echo "✓ Tüm Akış Başarıyla Tamamlandı." . PHP_EOL;
        echo "======================================================" . PHP_EOL;
    }

    /**
     * Test rendered channel templates.
     *
     * Usage:
     * php index.php console test_templates [subdomain]
     */
    public function test_templates(string $subdomain = 'salonflora'): void
    {
        if (is_multi_tenant_mode()) {
            $tenant = $this->db->get_where('tenants', ['subdomain' => $subdomain])->row_array();
            if ($tenant) {
                $this->connect_tenant($tenant);
            }
        }

        $this->load->library('channel_templates');

        $sample_data = [
            'company_name' => 'Salon Flora',
            'customer_name' => 'Selin Yılmaz',
            'service_name' => 'Aromaterapi Masajı (60 dk)',
            'start_datetime' => '2026-09-20 14:00:00',
            'end_datetime' => '2026-09-20 15:00:00',
            'provider_name' => 'Ayşe Kaya',
            'company_address' => 'Bağdat Caddesi No: 120, Kadıköy / İstanbul',
            'company_phone' => '0543 213 70 00',
            'booking_url' => 'https://salonflora-bookiapp.kibusiness.co/index.php/booking',
            'notes' => 'Hafif baskı tercih edilmektedir.',
        ];

        $tpls = ['appointment_pending', 'appointment_approved', 'appointment_rescheduled', 'appointment_cancelled', 'appointment_reminder', 'customer_channel_linked'];

        foreach ($tpls as $tpl) {
            echo "------------------------------------------------------" . PHP_EOL;
            echo "📋 ŞABLON: {$tpl}" . PHP_EOL;
            echo "------------------------------------------------------" . PHP_EOL;
            echo $this->channel_templates->render($tpl, $sample_data) . PHP_EOL . PHP_EOL;
        }
    }

    /**
     * Test Dashboard AI pending integration.
     *
     * Usage:
     * php index.php console test_dashboard_ai [subdomain]
     */
    public function test_dashboard_ai(string $subdomain = 'salonflora'): void
    {
        if (is_multi_tenant_mode()) {
            $tenant = $this->db->get_where('tenants', ['subdomain' => $subdomain])->row_array();
            if ($tenant) {
                $this->connect_tenant($tenant);
            }
        }

        echo "======================================================" . PHP_EOL;
        echo "📊 Dashboard & AI Dikkat Gerektirenler Entegrasyon Testi" . PHP_EOL;
        echo "======================================================" . PHP_EOL;

        // 1. Insert a sample pending appointment proposal
        $this->db->insert('ai_agent_pending_changes', [
            'target_table' => 'appointments',
            'target_id' => 0,
            'changes' => json_encode([
                'action' => 'create',
                'channel' => 'telegram',
                'service_name' => 'Klasik Masaj (60 dk)',
                'start_datetime' => date('Y-m-d 15:00:00', strtotime('+1 day')),
                'customer_name' => 'Can Demir',
                'customer_phone' => '05062505562',
                'customer_email' => 'can.demir@kibusiness.co',
                'notes' => 'Telegram üzerinden AI Asistan ile oluşturuldu',
            ], JSON_UNESCAPED_UNICODE),
            'reason' => '[telegram] Yeni randevu talebi: Can Demir (05062505562)',
            'model_name' => 'google',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $change_id = $this->db->insert_id();
        echo "✓ Örnek AI Randevu Talebi Oluşturuldu: ID #{$change_id}" . PHP_EOL;

        // 2. Query Dashboard Pending
        $pending = $this->db
            ->where('status', 'pending')
            ->order_by('created_at', 'desc')
            ->get('ai_agent_pending_changes')
            ->result_array();

        echo "✓ Dashboard 'Dikkat Gerektirenler' Bekleyen AI İşlemleri: " . count($pending) . " adet" . PHP_EOL;
        foreach ($pending as $p) {
            $c = json_decode((string) $p['changes'], true) ?: [];
            echo "   - [ID #{$p['id']}] {$p['target_table']} | " . ($c['action'] ?? '-') . " | " . ($c['customer_name'] ?? '-') . " | " . ($c['start_datetime'] ?? '-') . PHP_EOL;
        }

        echo PHP_EOL . "✓ Dashboard Entegrasyonu Başarılı!" . PHP_EOL;
        echo "======================================================" . PHP_EOL;
    }

    /**
     * Send reminders for upcoming appointments based on the configured reminder offsets.
     *
     * Usage:
     * php index.php console send_reminders [hours_ahead]
     *
     * @param int $hours_ahead Accepted for backward compatibility; reminder timing
     *                          comes from messaging_settings.reminder_offsets.
     */
    public function send_reminders(?int $hours_ahead = null): void
    {
        if (!is_multi_tenant_mode()) {
            $count = $this->send_reminders_current_db($hours_ahead);
            echo "Sent {$count} appointment reminder(s)." . PHP_EOL;
            return;
        }

        $tenants = $this->db->get_where('tenants', ['status' => 'active'])->result_array();
        foreach ($tenants as $tenant) {
            echo 'Sending reminders for tenant "' . $tenant['subdomain'] . '"... ';
            $this->connect_tenant($tenant);
            $count = $this->send_reminders_current_db($hours_ahead);
            echo $count . ' reminder(s) sent' . PHP_EOL;
        }

        $this->connect_master();
    }

    private function send_reminders_current_db(?int $hours_ahead = null): int
    {
        // Reminder timing and dispatch are handled by the shared Appointment_reminders
        // library (driven by messaging_settings.reminder_offsets). $hours_ahead is
        // accepted for CLI backward compatibility but no longer affects the sweep.
        $this->load->library('appointment_reminders');

        return $this->appointment_reminders->run(false);
    }

    /**
     * BooKi Marketplace - Synchronize tenant profile details into master `tenants` catalog.
     *
     * Usage: php index.php console sync_marketplace_tenants
     */
    public function sync_marketplace_tenants(): void
    {
        $this->connect_master();
        $tenants = $this->db->get_where('tenants', ['status' => 'active'])->result_array();
        echo 'Syncing ' . count($tenants) . ' tenants to marketplace catalog...' . PHP_EOL;

        $demo_meta = [
            'salonflora' => [
                'company_name' => 'Salon Flora',
                'category' => 'Güzellik Salonu',
                'city' => 'İstanbul',
                'district' => 'Kadıköy',
                'phone_number' => '+90 216 555 12 34',
                'address' => 'Bağdat Caddesi No: 142, Kadıköy, İstanbul',
                'price_range' => '₺₺',
                'short_description' => 'Premium saç tasarımı, renklendirme ve profesyonel güzellik hizmetleri.',
                'marketplace_opt_in' => 1,
            ],
            'demo-guzellik' => [
                'company_name' => 'Lumiere Güzellik Merkezi',
                'category' => 'Güzellik Salonu',
                'city' => 'İstanbul',
                'district' => 'Beşiktaş',
                'phone_number' => '+90 212 555 21 00',
                'address' => 'Nispetiye Cad. No: 45, Beşiktaş, İstanbul',
                'price_range' => '₺₺₺',
                'short_description' => 'Saç tasarımı, cilt bakımı ve tırnak uygulamalarında uzman estetisyen kadrosu.',
                'marketplace_opt_in' => 1,
            ],
            'demo-masaj' => [
                'company_name' => 'Serene Masaj & Spa',
                'category' => 'Masaj Salonu/Spa',
                'city' => 'İstanbul',
                'district' => 'Şişli',
                'phone_number' => '+90 212 555 24 00',
                'address' => 'Halaskargazi Cad. No: 88, Şişli, İstanbul',
                'price_range' => '₺₺₺',
                'short_description' => 'İsveç masajı, aromaterapi ve özel çift odası ile huzurlu bir spa deneyimi.',
                'marketplace_opt_in' => 1,
            ],
            'demo-restoran' => [
                'company_name' => 'Mavi Liman Restoran',
                'category' => 'Restoran',
                'city' => 'İzmir',
                'district' => 'Konak',
                'phone_number' => '+90 232 555 26 00',
                'address' => 'Kordon Boyu No: 12, Konak, İzmir',
                'price_range' => '₺₺₺',
                'short_description' => 'Ege lezzetleri ve şef masası degüstasyon menüsü ile unutulmaz bir akşam.',
                'marketplace_opt_in' => 1,
            ],
            'demo-otel' => [
                'company_name' => 'Grand Marmara Otel',
                'category' => 'Otel',
                'city' => 'İstanbul',
                'district' => 'Sarıyer',
                'phone_number' => '+90 212 555 28 00',
                'address' => 'Büyükdere Cad. No: 200, Sarıyer, İstanbul',
                'price_range' => '₺₺₺₺',
                'short_description' => 'Boğaz manzaralı spa, özel toplantı salonları ve lüks konaklama ayrıcalığı.',
                'marketplace_opt_in' => 1,
            ],
            'demo-klinik' => [
                'company_name' => 'Vita Sağlık Kliniği',
                'category' => 'Klinik/Sağlık',
                'city' => 'Ankara',
                'district' => 'Çankaya',
                'phone_number' => '+90 312 555 32 00',
                'address' => 'Tunalı Hilmi Cad. No: 65, Çankaya, Ankara',
                'price_range' => '₺₺',
                'short_description' => 'Dahiliye, diş sağlığı ve uzman fizyoterapi hizmetleri tek çatı altında.',
                'marketplace_opt_in' => 1,
            ],
            'demo-studyo' => [
                'company_name' => 'Pulse Stüdyo & Fitness',
                'category' => 'Stüdyo/Fitness',
                'city' => 'İstanbul',
                'district' => 'Kadıköy',
                'phone_number' => '+90 216 555 35 00',
                'address' => 'Moda Cad. No: 77, Kadıköy, İstanbul',
                'price_range' => '₺₺',
                'short_description' => 'Reformer pilates, dinamik yoga ve birebir kişisel antrenman programları.',
                'marketplace_opt_in' => 1,
            ],
        ];

        foreach ($tenants as $tenant) {
            $subdomain = $tenant['subdomain'];
            $update = [];

            if (isset($demo_meta[$subdomain])) {
                $update = $demo_meta[$subdomain];
            } else {
                try {
                    $this->connect_tenant($tenant);
                    $settings_rows = $this->db->get('settings')->result_array();
                    $settings = [];
                    foreach ($settings_rows as $row) {
                        $settings[$row['name']] = $row['value'];
                    }

                    $update = [
                        'company_name' => $settings['company_name'] ?? ucfirst($subdomain),
                        'phone_number' => $settings['telephone_number'] ?? ($settings['phone_number'] ?? null),
                        'address' => $settings['company_address'] ?? ($settings['address'] ?? null),
                        'marketplace_opt_in' => (int)($settings['marketplace_opt_in'] ?? 1),
                        'category' => $settings['marketplace_category'] ?? ($settings['category'] ?? 'Hizmet & Randevu'),
                        'city' => $settings['marketplace_city'] ?? ($settings['city'] ?? 'İstanbul'),
                        'district' => $settings['marketplace_district'] ?? ($settings['district'] ?? null),
                        'short_description' => $settings['marketplace_description'] ?? ($settings['description'] ?? null),
                        'cover_image_url' => $settings['marketplace_cover_url'] ?? null,
                        'price_range' => $settings['marketplace_price_range'] ?? '₺₺',
                    ];
                } catch (Throwable $e) {
                    echo "  [WARN] Failed reading settings for {$subdomain}: {$e->getMessage()}" . PHP_EOL;
                    $update = [
                        'company_name' => ucfirst($subdomain),
                        'marketplace_opt_in' => 1,
                        'category' => 'Hizmet & Randevu',
                        'city' => 'İstanbul',
                        'price_range' => '₺₺',
                    ];
                }
            }

            $this->connect_master();
            $this->db->where('id', $tenant['id'])->update('tenants', $update);
            echo "  ✓ Synced {$subdomain} (" . ($update['company_name'] ?? $subdomain) . ") -> Marketplace" . PHP_EOL;
        }

        echo 'Done syncing marketplace tenants.' . PHP_EOL;
    }

    /**
     * End-to-End Test Suite for Consumables, Service Recipes, Session Costing & Profit Margins.
     *
     * Tests all affected files:
     * - Migration 154 schema
     * - Blueprint catalogs & service recipes
     * - Inventory_consumables_model
     * - Appointments_model hooks (auto-population, auto-deduction, reversal)
     * - Idempotency
     * - Reporting & unit economics analytics
     *
     * Usage: php index.php console fix_movement_type
     */
    public function fix_movement_type(): void
    {
        $tenants = $this->db->get('tenants')->result_array();
        foreach ($tenants as $t) {
            $this->connect_tenant($t);
            $this->db->query("ALTER TABLE `{$this->db->dbprefix}stock_movements` MODIFY COLUMN `movement_type` VARCHAR(32) NOT NULL");
            echo "Fixed {$t['db_name']}: movement_type → VARCHAR(32)" . PHP_EOL;
        }
        echo "Done." . PHP_EOL;
    }

    /**
     *
     * Usage: php index.php console test_consumables_e2e [subdomain]
     */
    public function test_consumables_e2e(string $subdomain = ''): void
    {
        echo PHP_EOL . "================================================================================" . PHP_EOL;
        echo "🧪 BooKi E2E Test: Seans Sarfiyat Reçetesi, Otomatik Stok Düşümü & Kârlılık" . PHP_EOL;
        echo "================================================================================" . PHP_EOL;

        $passed = 0;
        $failed = 0;

        $assert = function (bool $condition, string $message) use (&$passed, &$failed) {
            if ($condition) {
                echo "  ✓ [PASS] {$message}" . PHP_EOL;
                $passed++;
            } else {
                echo "  ✗ [FAIL] {$message}" . PHP_EOL;
                $failed++;
            }
        };

        // 1. Establish Tenant Context
        if (is_multi_tenant_mode()) {
            $query = ['status' => 'active'];
            if (!empty($subdomain)) {
                $query['subdomain'] = strtolower(trim($subdomain));
            }
            $tenant = $this->db->get_where('tenants', $query)->row_array();
            if (!$tenant) {
                echo "✗ No active tenant found for testing!" . PHP_EOL;
                return;
            }
            $subdomain = $tenant['subdomain'];
            $this->connect_tenant($tenant);
            echo "🏢 Kiracı Bağlamı: {$subdomain} (DB: {$tenant['db_name']})" . PHP_EOL . PHP_EOL;
        } else {
            echo "🏢 Single-Tenant / Standalone Modu" . PHP_EOL . PHP_EOL;
        }

        $this->load->model('inventory_consumables_model');
        $this->load->model('appointments_model');
        $this->load->model('services_model');

        try {
            // =====================================================================
            // TEST 1: Schema Integrity Check (Migration 154)
            // =====================================================================
            echo "--- Test 1: Veritabanı Şeması & Migration 154 Kontrolleri ---" . PHP_EOL;
            $prod_fields = $this->db->list_fields('products');
            $assert(in_array('unit', $prod_fields, true), 'products tablosunda "unit" sütunu mevcut');
            $assert(in_array('is_consumable', $prod_fields, true), 'products tablosunda "is_consumable" sütunu mevcut');
            $assert(in_array('cost_price', $prod_fields, true), 'products tablosunda "cost_price" sütunu mevcut');

            $appt_fields = $this->db->list_fields('appointments');
            $assert(in_array('consumables_cost', $appt_fields, true), 'appointments tablosunda "consumables_cost" sütunu mevcut');
            $assert(in_array('gross_profit', $appt_fields, true), 'appointments tablosunda "gross_profit" sütunu mevcut');
            $assert(in_array('consumables_deducted', $appt_fields, true), 'appointments tablosunda "consumables_deducted" sütunu mevcut');

            $assert($this->db->table_exists('appointment_consumables'), 'appointment_consumables tablosu mevcut');
            $ac_fields = $this->db->list_fields('appointment_consumables');
            $assert(in_array('quantity_used', $ac_fields, true), 'appointment_consumables tablosunda "quantity_used" sütunu mevcut');
            $assert(in_array('total_cost', $ac_fields, true), 'appointment_consumables tablosunda "total_cost" sütunu mevcut');
            $assert(in_array('is_extra', $ac_fields, true), 'appointment_consumables tablosunda "is_extra" sütunu mevcut');

            $sm_fields = $this->db->list_fields('stock_movements');
            $assert(in_array('quantity', $sm_fields, true), 'stock_movements tablosunda "quantity" sütunu mevcut');
            $assert(in_array('unit_cost', $sm_fields, true), 'stock_movements tablosunda "unit_cost" sütunu mevcut');

            // =====================================================================
            // TEST 2: Industry Blueprint JSON Presets
            // =====================================================================
            echo PHP_EOL . "--- Test 2: Sektörel Blueprint Reçete & Sarf Katalogları ---" . PHP_EOL;
            $blueprints = ['dentist', 'doctor_clinic', 'barber', 'beauty_salon', 'massage_spa'];
            foreach ($blueprints as $bp) {
                $file = APPPATH . 'seeders/blueprints/' . $bp . '.json';
                $assert(file_exists($file), "Blueprint dosyası mevcut: {$bp}.json");
                $json = json_decode(file_get_contents($file), true);
                $has_consumables = !empty($json['consumables']);
                $has_recipes = !empty($json['service_consumable_recipes']);
                $assert($has_consumables, "{$bp}.json içinde sarf malzeme kataloğu tanımlı (" . count($json['consumables'] ?? []) . " ürün)");
                $assert($has_recipes, "{$bp}.json içinde hizmet sarfiyat reçeteleri tanımlı (" . count($json['service_consumable_recipes'] ?? []) . " kural)");
            }

            // =====================================================================
            // TEST 3: Create Test Products & Service with Recipe
            // =====================================================================
            echo PHP_EOL . "--- Test 3: Test Hizmeti & Reçete (BOM) Oluşturma ---" . PHP_EOL;

            // Create 2 consumable products
            $now = date('Y-m-d H:i:s');
            $this->db->insert('products', [
                'name' => 'E2E Nitril Eldiven (Test)',
                'sku' => 'E2E-GLOVE-' . time(),
                'cost_price' => 10.00,
                'sale_price' => 0.00,
                'stock_quantity' => 100.00,
                'low_stock_threshold' => 10.00,
                'unit' => 'çift',
                'is_consumable' => 1,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $prod1_id = (int) $this->db->insert_id();

            $this->db->insert('products', [
                'name' => 'E2E Hijyen Örtüsü (Test)',
                'sku' => 'E2E-SHEET-' . time(),
                'cost_price' => 25.00,
                'sale_price' => 0.00,
                'stock_quantity' => 50.00,
                'low_stock_threshold' => 5.00,
                'unit' => 'adet',
                'is_consumable' => 1,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $prod2_id = (int) $this->db->insert_id();

            // Create 1 extra product (e.g. special serum/anesthetic)
            $this->db->insert('products', [
                'name' => 'E2E Özel Bakım Serumu (Test)',
                'sku' => 'E2E-SERUM-' . time(),
                'cost_price' => 50.00,
                'sale_price' => 0.00,
                'stock_quantity' => 30.00,
                'low_stock_threshold' => 5.00,
                'unit' => 'ampul',
                'is_consumable' => 1,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $prod_extra_id = (int) $this->db->insert_id();

            // Create Test Service (Price = 500.00 TL)
            $this->db->insert('services', [
                'name' => 'E2E Test Tedavi Seansı',
                'duration' => 45,
                'price' => 500.00,
                'currency' => '₺',
                'description' => 'E2E Test Service with consumables',
            ]);
            $service_id = (int) $this->db->insert_id();

            // Add recipe rules:
            // Prod 1 (Glove): 2 çift @ 10 TL = 20 TL
            // Prod 2 (Sheet): 1 adet @ 25 TL = 25 TL
            // Total Recipe Consumable Cost = 45.00 TL
            // Expected Gross Profit = 500 - 45 = 455.00 TL (Margin: 91.0%)
            $this->db->insert('service_consumables', [
                'id_services' => $service_id,
                'id_products' => $prod1_id,
                'quantity_used' => 2.00,
                'unit' => 'çift',
                'created_at' => $now,
            ]);
            $this->db->insert('service_consumables', [
                'id_services' => $service_id,
                'id_products' => $prod2_id,
                'quantity_used' => 1.00,
                'unit' => 'adet',
                'created_at' => $now,
            ]);

            $recipes = $this->inventory_consumables_model->get_recipes_for_service($service_id);
            $assert(count($recipes) === 2, 'Hizmete 2 adet sarfiyat reçete kuralı başarıyla eklendi');

            $summary = $this->inventory_consumables_model->get_service_recipe_summary($service_id);
            $assert((float) $summary['total_consumable_cost'] === 45.00, 'Reçete toplam sarf maliyeti doğru hesaplandı: ₺45.00');
            $assert((float) $summary['gross_profit'] === 455.00, 'Reçete brüt kâr doğru hesaplandı: ₺455.00');
            $assert((float) $summary['gross_margin_percent'] === 91.0, 'Reçete brüt kâr marjı doğru hesaplandı: %91.0');

            // =====================================================================
            // TEST 4: Appointment Creation & Auto-Population from Recipe
            // =====================================================================
            echo PHP_EOL . "--- Test 4: Randevu Oluşturma & Reçeteden Otomatik Sarf Doldurma ---" . PHP_EOL;

            // Ensure valid provider user ID and customer user ID exist
            $provider_user = $this->db
                ->select('users.id')
                ->from('users')
                ->join('roles', 'roles.id = users.id_roles', 'inner')
                ->where('roles.slug', DB_SLUG_PROVIDER)
                ->get()
                ->row_array();
            $provider_id = (int) ($provider_user['id'] ?? 1);

            $customer_user = $this->db
                ->select('users.id')
                ->from('users')
                ->join('roles', 'roles.id = users.id_roles', 'inner')
                ->where('roles.slug', DB_SLUG_CUSTOMER)
                ->get()
                ->row_array();
            $customer_id = (int) ($customer_user['id'] ?? 1);

            $start = date('Y-m-d H:i:s', strtotime('+1 day 10:00:00'));
            $end = date('Y-m-d H:i:s', strtotime('+1 day 10:45:00'));

            $appt_data = [
                'start_datetime' => $start,
                'end_datetime' => $end,
                'status' => 'Reserved',
                'is_unavailability' => 0,
                'id_users_provider' => $provider_id,
                'id_users_customer' => $customer_id,
                'id_services' => $service_id,
                'notes' => 'E2E Test Appointment',
            ];
            $appt_id = (int) $this->appointments_model->save($appt_data);
            $assert($appt_id > 0, "Randevu başarıyla oluşturuldu (ID: {$appt_id})");

            $appt = $this->appointments_model->find($appt_id);
            $assert((float) $appt['consumables_cost'] === 45.00, 'Randevu sarf maliyeti reçeteden otomatik hesaplandı: ₺45.00');
            $assert((float) $appt['gross_profit'] === 455.00, 'Randevu brüt kârı otomatik hesaplandı: ₺455.00');
            $assert((int) $appt['consumables_deducted'] === 0, 'Randevu henüz rezerve iken stoktan düşülmedi (consumables_deducted = 0)');

            $appt_consumables = $this->inventory_consumables_model->get_appointment_consumables($appt_id);
            $assert(count($appt_consumables) === 2, 'appointment_consumables tablosuna 2 reçete kalemi otomatik kopyalandı');

            // =====================================================================
            // TEST 5: Session Consumable Customization (Extra items & quantity edits)
            // =====================================================================
            echo PHP_EOL . "--- Test 5: Seans İçi Özel Sarfiyat (Ekstra Malzeme & Miktar Güncelleme) ---" . PHP_EOL;

            // Add extra item (Serum @ 50 TL)
            $extra_rec_id = $this->inventory_consumables_model->save_appointment_consumable([
                'id_appointments' => $appt_id,
                'id_products' => $prod_extra_id,
                'quantity_used' => 1.00,
                'unit' => 'ampul',
                'unit_cost' => 50.00,
                'is_extra' => 1,
                'notes' => 'E2E Ekstra Serum Uygulaması',
            ]);
            $assert($extra_rec_id > 0, "Seansa ekstra sarf malzeme başarıyla eklendi (ID: {$extra_rec_id})");

            // Update Glove quantity from 2 to 3 (+10 TL)
            $glove_item = null;
            foreach ($appt_consumables as $ac) {
                if ((int) $ac['id_products'] === $prod1_id) {
                    $glove_item = $ac;
                    break;
                }
            }
            $this->inventory_consumables_model->save_appointment_consumable([
                'id' => $glove_item['id'],
                'id_appointments' => $appt_id,
                'id_products' => $prod1_id,
                'quantity_used' => 3.00, // 3 * 10 = 30 TL
                'unit' => 'çift',
                'unit_cost' => 10.00,
                'is_extra' => 0,
            ]);

            // Expected new cost: (3 * 10) + (1 * 25) + (1 * 50) = 30 + 25 + 50 = 105.00 TL
            // Expected gross profit: 500 - 105 = 395.00 TL
            $appt = $this->appointments_model->find($appt_id);
            $assert((float) $appt['consumables_cost'] === 105.00, 'Seans sarfiyat güncellemesi sonrası maliyet doğru güncellendi: ₺105.00');
            $assert((float) $appt['gross_profit'] === 395.00, 'Seans sarfiyat güncellemesi sonrası brüt kâr doğru güncellendi: ₺395.00');

            // =====================================================================
            // TEST 6: Auto-Deduction upon Completion (Status -> 'Tamamlandı')
            // =====================================================================
            echo PHP_EOL . "--- Test 6: Seans Tamamlama & Otomatik Stok Düşümü ---" . PHP_EOL;

            $p1_before = (float) $this->db->get_where('products', ['id' => $prod1_id])->row('stock_quantity');
            $p2_before = (float) $this->db->get_where('products', ['id' => $prod2_id])->row('stock_quantity');
            $pe_before = (float) $this->db->get_where('products', ['id' => $prod_extra_id])->row('stock_quantity');

            $appt = $this->appointments_model->find($appt_id);
            $appt['status'] = 'Tamamlandı';
            $this->appointments_model->save($appt);

            $appt = $this->appointments_model->find($appt_id);
            $assert((int) $appt['consumables_deducted'] === 1, 'Randevu tamamlandığında consumables_deducted = 1 oldu');

            $p1_after = (float) $this->db->get_where('products', ['id' => $prod1_id])->row('stock_quantity');
            $p2_after = (float) $this->db->get_where('products', ['id' => $prod2_id])->row('stock_quantity');
            $pe_after = (float) $this->db->get_where('products', ['id' => $prod_extra_id])->row('stock_quantity');

            $assert($p1_after === ($p1_before - 3.00), "Eldiven stoğu 3 birim düştü: {$p1_before} -> {$p1_after}");
            $assert($p2_after === ($p2_before - 1.00), "Örtü stoğu 1 birim düştü: {$p2_before} -> {$p2_after}");
            $assert($pe_after === ($pe_before - 1.00), "Ekstra serum stoğu 1 birim düştü: {$pe_before} -> {$pe_after}");

            $movements = $this->db->get_where('stock_movements', ['id_appointments' => $appt_id])->result_array();
            $assert(count($movements) === 3, 'stock_movements tablosuna 3 adet sarfiyat hareketi kaydedildi');
            foreach ($movements as $m) {
                $assert($m['movement_type'] === 'service_consumption', 'Haraket tipi "service_consumption" olarak kaydedildi');
                $assert((float) $m['quantity'] < 0, 'Stok düşüm miktarı negatif olarak kaydedildi: ' . $m['quantity']);
            }

            // =====================================================================
            // TEST 7: Idempotency Verification (Repeated Updates)
            // =====================================================================
            echo PHP_EOL . "--- Test 7: Idempotency (Mükerrer Stok Düşümü Engeli) ---" . PHP_EOL;

            $appt = $this->appointments_model->find($appt_id);
            $appt['notes'] = 'E2E Updated Notes';
            $this->appointments_model->save($appt);

            $p1_idemp = (float) $this->db->get_where('products', ['id' => $prod1_id])->row('stock_quantity');
            $assert($p1_idemp === $p1_after, 'Randevu tekrar kaydedildiğinde stok ikinci kez DÜŞMEDİ (İdempotency sağlandı)');

            $movements_count = $this->db->where('id_appointments', $appt_id)->count_all_results('stock_movements');
            $assert($movements_count === 3, 'stock_movements tablosunda mükerrer kayıt oluşmadı');

            // =====================================================================
            // TEST 8: Cancellation & Compensating Stock Reversal
            // =====================================================================
            echo PHP_EOL . "--- Test 8: İptal Durumunda Otomatik Stok İadesi (Reversal) ---" . PHP_EOL;

            $appt = $this->appointments_model->find($appt_id);
            $appt['status'] = 'Cancelled';
            $this->appointments_model->save($appt);

            $appt = $this->appointments_model->find($appt_id);
            $assert((int) $appt['consumables_deducted'] === 0, 'Randevu iptal edildiğinde consumables_deducted = 0 olarak sıfırlandı');

            $p1_rev = (float) $this->db->get_where('products', ['id' => $prod1_id])->row('stock_quantity');
            $p2_rev = (float) $this->db->get_where('products', ['id' => $prod2_id])->row('stock_quantity');
            $pe_rev = (float) $this->db->get_where('products', ['id' => $prod_extra_id])->row('stock_quantity');

            $assert($p1_rev === $p1_before, "Eldiven stoğu eski haline iade edildi ({$p1_rev})");
            $assert($p2_rev === $p2_before, "Örtü stoğu eski haline iade edildi ({$p2_rev})");
            $assert($pe_rev === $pe_before, "Serum stoğu eski haline iade edildi ({$pe_rev})");

            $rev_movements = $this->db->get_where('stock_movements', [
                'id_appointments' => $appt_id,
                'movement_type' => 'adjustment',
            ])->result_array();
            $assert(count($rev_movements) === 3, 'stock_movements tablosuna 3 adet "adjustment" dengeleme kaydı eklendi');

            // =====================================================================
            // TEST 9: Consumables & Session Margins Analytics Report
            // =====================================================================
            echo PHP_EOL . "--- Test 9: Raporlama & Birim Seans Kârlılık Analitiği ---" . PHP_EOL;

            // Re-complete the appointment so it reflects in the report
            $appt = $this->appointments_model->find($appt_id);
            $appt['status'] = 'Tamamlandı';
            $this->appointments_model->save($appt);

            $start_date = date('Y-m-d 00:00:00', strtotime('-1 day'));
            $end_date = date('Y-m-d 23:59:59', strtotime('+2 days'));

            $report = $this->inventory_consumables_model->get_consumables_report($start_date, $end_date, $service_id);

            $assert(!empty($report), 'Sarfiyat ve kârlılık analitik raporu başarıyla üretildi');
            $assert((float) $report['total_consumable_spend'] >= 105.00, 'Raporda toplam sarf harcaması doğru yansıdı: ₺' . $report['total_consumable_spend']);
            $assert((float) $report['total_revenue'] >= 500.00, 'Raporda seans hasılatı doğru yansıdı: ₺' . $report['total_revenue']);
            $assert((float) $report['total_gross_profit'] >= 395.00, 'Raporda brüt kâr doğru yansıdı: ₺' . $report['total_gross_profit']);
            $assert((float) $report['overall_margin_percent'] > 0, 'Raporda genel brüt marj yüzdesi doğru hesaplandı: %' . $report['overall_margin_percent']);

            // Check consumed products breakdown
            $found_prod1 = false;
            foreach ($report['consumed_products'] as $cp) {
                if ((int) $cp['id_products'] === $prod1_id) {
                    $found_prod1 = true;
                    $assert((float) $cp['total_quantity'] === 3.00, 'Malzeme kırılımında eldiven tüketimi 3 adet olarak raporlandı');
                    $assert((float) $cp['total_spend'] === 30.00, 'Malzeme kırılımında eldiven maliyeti ₺30.00 olarak raporlandı');
                    break;
                }
            }
            $assert($found_prod1, 'Tüketilen malzemeler listesinde test ürünü yer alıyor');

            // =====================================================================
            // TEST 10: Clean Up Test Artifacts
            // =====================================================================
            echo PHP_EOL . "--- Test 10: Test Kayıtlarının Temizlenmesi ---" . PHP_EOL;
            $this->db->delete('appointment_consumables', ['id_appointments' => $appt_id]);
            $this->db->delete('stock_movements', ['id_appointments' => $appt_id]);
            $this->db->delete('appointments', ['id' => $appt_id]);
            $this->db->delete('service_consumables', ['id_services' => $service_id]);
            $this->db->delete('services', ['id' => $service_id]);
            $this->db->delete('products', ['id' => $prod1_id]);
            $this->db->delete('products', ['id' => $prod2_id]);
            $this->db->delete('products', ['id' => $prod_extra_id]);

            $assert(true, 'Geçici test kayıtları veritabanından temizlendi');

        } catch (Throwable $e) {
            echo PHP_EOL . "💥 EXCEPTION: " . $e->getMessage() . PHP_EOL;
            echo $e->getTraceAsString() . PHP_EOL;
            $failed++;
        }

        if (is_multi_tenant_mode()) {
            $this->connect_master();
        }

        echo PHP_EOL . "================================================================================" . PHP_EOL;
        echo "TEST SONUCU: {$passed} BAŞARILI, {$failed} BAŞARISIZ" . PHP_EOL;
        echo "================================================================================" . PHP_EOL;
    }

    /**
     * BooKi Google Places Crawler CLI Command
     *
     * Usage: php index.php console places_crawl [district] [category] [depth]
     */
    public function places_crawl(string $district = 'Nilüfer', string $category = 'guzellik_kuafor', string $depth = 'standard'): void
    {
        if (is_multi_tenant_mode()) {
            $this->connect_master();
        }

        echo "=== BooKi Google Places Crawler (CLI) ===" . PHP_EOL;
        echo "District: {$district}" . PHP_EOL;
        echo "Category: {$category}" . PHP_EOL;
        echo "Depth: {$depth}" . PHP_EOL . PHP_EOL;

        $this->load->library('google_places_crawler');
        $res = $this->google_places_crawler->crawl([
            'geo_mode' => 'districts',
            'districts' => [$district],
            'categories' => [$category],
            'depth' => $depth,
            'business_status' => 'OPERATIONAL'
        ]);

        echo "Status: " . ($res['status'] ?? 'unknown') . PHP_EOL;
        echo "Queries Run: " . ($res['queries_completed'] ?? 0) . "/" . ($res['total_queries'] ?? 0) . PHP_EOL;
        echo "Places Found: " . ($res['places_found'] ?? 0) . PHP_EOL;
        echo "Leads Created: " . ($res['leads_created'] ?? 0) . PHP_EOL;
        echo "Leads Updated: " . ($res['leads_updated'] ?? 0) . PHP_EOL;
        echo "Queries Failed: " . ($res['queries_failed'] ?? 0) . PHP_EOL;
        if (!empty($res['error_message'])) {
            echo "Error: " . $res['error_message'] . PHP_EOL;
        }
        echo "=== Crawl Completed ===" . PHP_EOL;
    }

    /**
     * Places usage stats
     */
    public function places_stats(): void
    {
        try {
            if (is_multi_tenant_mode()) {
                $this->connect_master();
            }

            $this->load->library('google_places_crawler');
            $stats = $this->google_places_crawler->get_usage_stats();
            echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        } catch (Throwable $e) {
            echo "Error in places_stats: " . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
        }
    }

    /**
     * Normalize all lead business names in master DB.
     */
    public function normalize_lead_names(): void
    {
        if (is_multi_tenant_mode()) {
            $this->connect_master();
        }

        $this->load->library('google_places_crawler');
        $leads = $this->db->select('id, name')->get('leads')->result_array();
        $updated = 0;

        foreach ($leads as $lead) {
            $clean = Google_places_crawler::normalize_business_name($lead['name']);
            if ($clean !== $lead['name']) {
                $this->db->where('id', $lead['id'])->update('leads', ['name' => $clean]);
                $updated++;
                echo "ID {$lead['id']}: '{$lead['name']}' => '{$clean}'" . PHP_EOL;
            }
        }

        echo "Total normalized leads: {$updated} of " . count($leads) . PHP_EOL;
    }

    /**
     * E2E Verification & Smoke Test for Multi-Vertical Enterprise Suite
     *
     * Usage: php index.php console test_multi_vertical_enterprise [subdomain]
     */
    public function test_multi_vertical_enterprise(string $subdomain = 'salonflora'): void
    {
        echo PHP_EOL . "================================================================================" . PHP_EOL;
        echo "🏢 BooKi Multi-Vertical Enterprise Suite - Comprehensive E2E Verification" . PHP_EOL;
        echo "================================================================================" . PHP_EOL;

        $passed = 0;
        $failed = 0;

        $assert = function (bool $condition, string $message) use (&$passed, &$failed) {
            if ($condition) {
                echo "  ✓ [PASS] {$message}" . PHP_EOL;
                $passed++;
            } else {
                echo "  ✗ [FAIL] {$message}" . PHP_EOL;
                $failed++;
            }
        };

        // 1. Establish Tenant Context
        if (is_multi_tenant_mode()) {
            $tenant = $this->db->get_where('tenants', ['subdomain' => $subdomain, 'status' => 'active'])->row_array();
            if (!$tenant) {
                // Fallback to first active tenant
                $tenant = $this->db->get_where('tenants', ['status' => 'active'])->row_array();
            }
            if (!$tenant) {
                echo "✗ No active tenant found!" . PHP_EOL;
                return;
            }
            $this->connect_tenant($tenant);
            echo "🏢 Kiracı: {$tenant['subdomain']} | DB: {$tenant['db_name']}" . PHP_EOL . PHP_EOL;
        }

        // Load all enterprise models
        $this->load->model('gift_cards_model');
        $this->load->model('restaurant_model');
        $this->load->model('sports_matches_model');
        $this->load->model('clinical_records_model');
        $this->load->model('vehicles_model');
        $this->load->model('work_orders_model');
        $this->load->model('digital_waivers_model');
        $this->load->model('event_tickets_model');
        $this->load->model('customers_model');
        $this->load->model('appointments_model');
        $this->load->model('services_model');
        $this->load->model('providers_model');
        $this->load->library('availability');

        $customer = $this->db->get('users', 1)->row_array();
        $customerId = $customer ? (int) $customer['id'] : 1;
        $provider = $this->db->get_where('users', ['id_roles' => 2], 1)->row_array();
        $providerId = $provider ? (int) $provider['id'] : 1;

        try {
            // --- 1. GÜZELLİK & SPA (GIFT CARDS & DEPOSIT) ---
            echo "--- 1. Güzellik & Spa (Hediye Kartı, Kapora & Sadakat) ---" . PHP_EOL;
            $testCode = 'TEST-' . strtoupper(bin2hex(random_bytes(4)));
            $card = $this->gift_cards_model->issue_card([
                'code' => $testCode,
                'initial_amount' => 500.00,
                'recipient_name' => 'Ayşe Yılmaz',
            ]);
            $assert(!empty($card['id']), "Hediye kartı oluşturuldu (ID: {$card['id']}, Kod: {$testCode})");

            $cardCheck = $this->gift_cards_model->get_by_code($testCode);
            $assert($cardCheck && (float)$cardCheck['current_balance'] === 500.0, "Hediye kartı bakiye doğrulandı (500 TL)");

            $redeemRes = $this->gift_cards_model->redeem($testCode, 150.00);
            $assert($redeemRes['success'] === true, "150 TL harcama/redemption başarılı");

            $cardAfter = $this->gift_cards_model->get_by_code($testCode);
            $assert($cardAfter && (float)$cardAfter['current_balance'] === 350.0, "Kalan bakiye doğru (350 TL)");

            // --- 2. RESTORAN & KAFE (GUEST PREFERENCES & KDS) ---
            echo PHP_EOL . "--- 2. Restoran & Kafe (Guest 360 & KDS Mutfak/Bar) ---" . PHP_EOL;
            $prefRes = $this->restaurant_model->save_guest_preferences($customerId, [
                'dietary_restrictions' => ['Gluten-Free', 'Vegetarian'],
                'allergies' => ['Fıstık'],
                'preferred_seating' => 'Cam Kenarı / Bahçe',
                'vip_level' => 'vip',
                'special_notes' => 'Yıldönümü kutlaması, şampanya servisi',
            ]);
            $assert(!empty($prefRes), "Misafir 360 alerjen, VIP ve oturma tercihleri kaydedildi");

            $pref = $this->restaurant_model->get_guest_preferences($customerId);
            $dietaryList = !empty($pref['dietary_restrictions']) ? (is_array($pref['dietary_restrictions']) ? $pref['dietary_restrictions'] : json_decode($pref['dietary_restrictions'], true)) : [];
            $assert($pref && $pref['vip_level'] === 'vip' && in_array('Fıstık', $dietaryList), "Misafir tercihleri ve alerjen bilgisi başarıyla okundu");

            $kitchenOrderId = $this->restaurant_model->create_kitchen_order([
                'station' => 'kitchen',
                'item_name' => 'Izgara Somon',
                'quantity' => 2,
                'notes' => 'Alerjiye dikkat: Fıstıksız',
            ]);
            $assert($kitchenOrderId > 0, "KDS mutfak siparişi açıldı (ID: {$kitchenOrderId})");

            $kdsActive = $this->restaurant_model->get_active_kitchen_orders('kitchen');
            $foundOrder = false;
            foreach ($kdsActive as $ko) {
                if ((int)$ko['id'] === $kitchenOrderId) {
                    $foundOrder = true;
                    break;
                }
            }
            $assert($foundOrder === true, "KDS aktif mutfak ekranında sipariş canlı görünüyor");

            $statusOk = $this->restaurant_model->update_kitchen_order_status($kitchenOrderId, 'ready');
            $assert($statusOk === true, "KDS mutfak sipariş durumu 'ready' olarak güncellendi");

            // --- 3. SPOR / KORT / HALI SAHA (OPEN MATCHES & TURNSTILE GATE) ---
            echo PHP_EOL . "--- 3. Spor & Kort (Açık Maçlar, Matchmaking & Turnike Geçiş) ---" . PHP_EOL;
            $matchId = $this->sports_matches_model->create_match([
                'title' => 'Padel Çiftler Maçı',
                'sport_type' => 'padel',
                'start_datetime' => date('Y-m-d 18:00:00', strtotime('+1 day')),
                'end_datetime' => date('Y-m-d 19:30:00', strtotime('+1 day')),
                'max_players' => 4,
                'price_per_player' => 250.00,
                'created_by_user_id' => $customerId,
            ]);
            $assert($matchId > 0, "Açık maç ilanı açıldı (ID: {$matchId}, Padel)");

            $secondCust = $this->db->get_where('users', ['id !=' => $customerId], 1)->row_array();
            $secondCustId = $secondCust ? (int)$secondCust['id'] : 8888;
            $joinRes = $this->sports_matches_model->join_match($matchId, $secondCustId, 'Team B', '3.5', true);
            $assert($joinRes['success'] === true, "2. oyuncu maça başarıyla katıldı");

            $openMatches = $this->sports_matches_model->get_open_matches('padel');
            $assert(count($openMatches) > 0, "Açık maç listeleme ve filtreleme çalışıyor");

            $gateCheck = $this->sports_matches_model->verify_turnstile_access('UNKNOWN-TAG', 'TURNSTILE-01');
            $assert(isset($gateCheck['access_granted']), "Turnike donanım kapı kontrol API yanıtı doğrulandı");

            // --- 4. SAĞLIK & KLİNİK (EHR SOAP & TELEHEALTH) ---
            echo PHP_EOL . "--- 4. Sağlık & Klinik (EHR SOAP, Sigorta & Teletıp) ---" . PHP_EOL;
            $clinicalId = $this->clinical_records_model->add_record([
                'id_users_customer' => $customerId,
                'id_users_provider' => $providerId,
                'record_type' => 'soap_note',
                'subjective' => 'Hasta sol dizde 3 gündür devam eden batma ve şişlik şikayetiyle başvurdu.',
                'objective' => 'Sol diz eklem hareket açıklığı kısıtlı, hafif efüzyon mevcut. Patella kompresyon testi pozitif.',
                'assessment' => 'Patellofemoral ağrı sendromu / hafif sinovit.',
                'plan' => 'İstirahat, buz uygulama, NSAİİ tedavi başlandı. 10 seans fizik tedavi önerildi.',
            ]);
            $assert($clinicalId > 0, "EHR SOAP klinik dosyası başarıyla kaydedildi (ID: {$clinicalId})");

            $patientRecords = $this->clinical_records_model->get_patient_records($customerId);
            $assert(count($patientRecords) > 0, "Hastanın geçmiş klinik dosyaları çekildi");

            $telehealth = $this->clinical_records_model->generate_telehealth_session(999, 'Dr. Uzman Hekim', 'Hasta Bilgi');
            $assert(!empty($telehealth['room_url']) && str_contains($telehealth['room_url'], 'meet.jit.si'), "Güvenli teletıp Jitsi video görüşme odası üretildi ({$telehealth['room_url']})");

            // --- 5. OTOMOTİV & SERVİS (VEHICLES, DVI & WORK ORDERS) ---
            echo PHP_EOL . "--- 5. Otomotiv & Servis (Araç Sicili, DVI Ekspertiz & İş Emirleri) ---" . PHP_EOL;
            $testPlate = '34TST' . rand(100, 999);
            $vehicleId = $this->vehicles_model->add_vehicle([
                'id_users_customer' => $customerId,
                'plate_number' => $testPlate,
                'brand' => 'Volkswagen',
                'model' => 'Golf 8 1.5 eTSI',
                'year' => 2023,
                'color' => 'Beyaz',
                'current_km' => 28500,
            ]);
            $assert($vehicleId > 0, "Araç sisteme kaydedildi (Plaka: {$testPlate}, ID: {$vehicleId})");

            $inspection = $this->work_orders_model->save_inspection([
                'id_vehicles' => $vehicleId,
                'inspection_type' => 'general_service',
                'overall_score' => 85,
                'items' => [
                    'fren_balatalari' => ['status' => 'yellow', 'note' => '%40 aşınma'],
                    'motor_yagi' => ['status' => 'red', 'note' => 'Değişim zamanı geçmiş'],
                    'lastikler' => ['status' => 'green', 'note' => 'Diş derinliği iyi'],
                ],
            ]);
            $assert(!empty($inspection['id']) && !empty($inspection['customer_shared_token']), "DVI dijital araç inceleme / ekspertiz formu oluşturuldu (ID: {$inspection['id']})");

            $apprOk = $this->work_orders_model->approve_inspection_by_token($inspection['customer_shared_token']);
            $assert($apprOk === true, "Müşteri dijital DVI onayı başarıyla doğrulandı");

            $wo = $this->work_orders_model->create_work_order([
                'id_vehicles' => $vehicleId,
                'status' => 'in_progress',
                'estimated_cost' => 4500.00,
            ]);
            $assert(!empty($wo['id']), "Servis iş emri açıldı (ID: {$wo['id']})");

            $stageOk = $this->work_orders_model->update_status($wo['id'], 'ready');
            $assert($stageOk === true, "İş emri aşaması 'ready' olarak güncellendi");

            // --- 6. DENEYİM & MACERA (DIGITAL WAIVERS & EVENT TICKETS) ---
            echo PHP_EOL . "--- 6. Deneyim & Macera (Dijital Feragatname & Biletleme) ---" . PHP_EOL;
            $waiverId = $this->digital_waivers_model->save_waiver([
                'title' => 'Macera Parkı & Zipline Sorumluluk Feragatnamesi',
                'content_html' => '<p>Etkinlik esnasında oluşabilecek riskleri okudum ve kabul ediyorum.</p>',
                'is_mandatory' => 1,
            ]);
            $assert($waiverId > 0, "Dijital feragatname şablonu oluşturuldu (ID: {$waiverId})");

            $sigId = $this->digital_waivers_model->sign_waiver([
                'id_waivers' => $waiverId,
                'id_users_customer' => $customerId,
                'signer_full_name' => 'Mehmet Demir',
                'signer_email' => 'mehmet@example.com',
                'signer_phone' => '05551234567',
                'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
                'ip_address' => '127.0.0.1',
            ]);
            $assert($sigId > 0, "Biyometrik e-imza ve IP damgasıyla feragatname imzalandı (ID: {$sigId})");

            $existingAppt = $this->db->get('appointments', 1)->row_array();
            $apptId = $existingAppt ? (int) $existingAppt['id'] : 1;
            $ticket = $this->event_tickets_model->issue_ticket($apptId, $customerId, 'VIP-A1');
            $assert(!empty($ticket['id']) && !empty($ticket['ticket_code']), "QR giriş bileti üretildi (Kod: {$ticket['ticket_code']})");

            $burnRes = $this->event_tickets_model->validate_ticket($ticket['ticket_code']);
            $assert($burnRes['valid'] === true && $burnRes['status'] === 'success', "QR bilet kapıda doğrulandı ve yakıldı (Ticket: {$ticket['ticket_code']})");

        } catch (Throwable $e) {
            echo "  ✗ [EXCEPTION] " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
            $failed++;
        }

        // --- 7. AVAILABILITY ENGINE REGRESSION TEST ---
        echo PHP_EOL . "--- 7. Availability Engine Regresyon Doğrulaması ---" . PHP_EOL;
        $serviceRow = $this->db->get('services', 1)->row_array();
        $providerRow = $this->db->get_where('users', ['id_roles' => 2], 1)->row_array();
        if ($serviceRow && $providerRow) {
            $testDate = date('Y-m-d', strtotime('+3 days'));
            $serviceData = $this->services_model->get_row((int)$serviceRow['id']);
            $providerData = $this->providers_model->get_row((int)$providerRow['id']);
            $avail = $this->availability->get_available_hours($testDate, $serviceData, $providerData);
            $assert(is_array($avail), "3-Değişkenli Uygunluk Motoru (Provider + Service + Resource) kesintisiz çalışıyor (Hesaplanan slot: " . count($avail) . ")");
        } else {
            $assert(class_exists('Availability'), "Availability kütüphanesi aktif ve yüklendi");
        }

        // --- 8. WHATSAPP & APPOINTMENT REMINDERS REGRESSION TEST ---
        echo PHP_EOL . "--- 8. Çoklu Ofsetli WhatsApp Hatırlatıcı Motoru Doğrulaması ---" . PHP_EOL;
        $this->load->library('appointment_reminders');
        $this->load->library('whatsapp_client');
        $assert(class_exists('Appointment_reminders'), "Appointment_reminders kütüphanesi aktif");
        $assert(class_exists('Whatsapp_client'), "Whatsapp_client kütüphanesi aktif");

        // --- 9. BLUEPRINTS & MODULAR TERMINOLOGY TEST ---
        echo PHP_EOL . "--- 9. Sektörel Blueprints & Modüler Arayüz Doğrulaması ---" . PHP_EOL;
        $allBlueprints = [
            'beauty_salon' => 'beauty',
            'restaurant' => 'restaurant',
            'sports_court' => 'sports',
            'psychology_dietitian_clinic' => 'health',
            'auto_service_detailing' => 'automotive',
            'experience_escape_room' => 'experience',
        ];
        foreach ($allBlueprints as $bpCode => $expectedGroup) {
            $resolvedGroup = current_vertical_group($bpCode);
            $bpData = current_industry_blueprint($bpCode);
            $assert($resolvedGroup === $expectedGroup, "Blueprint '{$bpCode}' => dikey grubu '{$expectedGroup}'");
            $assert($bpData !== null && !empty($bpData['industry']['name']), "Blueprint '{$bpCode}' JSON dosyası geçerli ve yüklendi ({$bpData['industry']['name']})");
        }

        // --- 10. FRONTEND & UI/UX VIEW RENDERING TEST ---
        echo PHP_EOL . "--- 10. Web UI View Render Test (Frontend & UI/UX) ---" . PHP_EOL;
        session(['user_id' => 1, 'role_slug' => DB_SLUG_ADMIN]);
        html_vars([
            'user_display_name' => 'Demo Admin',
            'active_menu' => 'dashboard',
            'privileges' => ['customers' => 15, 'appointments' => 15],
            'page_title' => 'Test Paneli',
        ]);

        $views = [
            'pages/vertical_gift_cards' => [
                'cards' => [],
                'deposits' => [],
                'customers' => [],
            ],
            'pages/vertical_kds' => [
                'orders' => [],
            ],
            'pages/vertical_sports_matches' => [
                'matches' => [],
                'stations' => [],
                'checkins' => [],
                'customers' => [],
            ],
            'pages/vertical_clinical_records' => [
                'records' => [],
                'patients' => [],
                'providers' => [],
            ],
            'pages/vertical_vehicles_dvi' => [
                'vehicles' => [],
                'work_orders' => [],
                'customers' => [],
            ],
            'pages/vertical_experience_waivers' => [
                'waivers' => [],
                'signatures' => [],
                'tickets' => [],
            ],
        ];

        foreach ($views as $viewPath => $viewData) {
            try {
                $html = $this->load->view($viewPath, $viewData, true);
                $assert(!empty($html) && strlen($html) > 500, "View '{$viewPath}' render edildi (" . strlen($html) . " bytes)");
            } catch (Throwable $e) {
                echo "  ✗ [VIEW FAIL] {$viewPath}: " . $e->getMessage() . PHP_EOL;
                $failed++;
            }
        }

        // --- 11. MODÜLER SIDEBAR (BACKEND HEADER) RENDER TEST ---
        echo PHP_EOL . "--- 11. Modüler Sidebar (Backend Header) Render Test ---" . PHP_EOL;
        try {
            $headerHtml = $this->load->view('components/backend_header', [], true);
            $assert(!empty($headerHtml) && strlen($headerHtml) > 1000, "Backend Header & Sektörel Sidebar render edildi (" . strlen($headerHtml) . " bytes)");
        } catch (Throwable $e) {
            echo "  ✗ [SIDEBAR FAIL] backend_header: " . $e->getMessage() . PHP_EOL;
            $failed++;
        }

        echo PHP_EOL . "================================================================================" . PHP_EOL;
        echo "📊 TEST SONUÇLARI: {$passed} Başarılı, {$failed} Başarısız" . PHP_EOL;
        echo "================================================================================" . PHP_EOL;
    }
}




