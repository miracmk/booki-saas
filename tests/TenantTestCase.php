<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use RuntimeException;

/**
 * Base test case for integration tests requiring a live tenant database connection.
 *
 * This class bootstraps the CodeIgniter3 framework once per test run (static, shared across all test
 * methods) and provides helpers to connect to tenant-specific databases, mirroring the behavior of
 * Console::connect_tenant() and EA_Controller::resolve_tenant().
 *
 * IMPORTANT: MySQL DDL (CREATE/ALTER/DROP) is NOT transactional — any test that runs a migration
 * must NOT rely on the trans_begin/trans_rollback wrapper in setUp/tearDown and must clean up manually.
 */
abstract class TenantTestCase extends BaseTestCase
{
    /**
     * Shared CodeIgniter instance, booted once per test run.
     */
    protected static ?object $ci = null;

    /**
     * Guard to ensure the CI boot happens exactly once per process.
     */
    private static bool $ci_booted = false;

    /**
     * Bootstrap the CodeIgniter3 application instance if not already done.
     *
     * This method is called automatically by setUp() and should not be called directly.
     * The CI instance is cached in a static property and reused across all test methods.
     */
    protected static function boot_ci(): void
    {
        if (self::$ci_booted) {
            return;
        }

        self::$ci_booted = true;

        // Set up CLI argv to route to the noop console command
        $_SERVER['argv'] = ['index.php', 'console', 'noop'];
        $_SERVER['REQUEST_METHOD'] = null;

        // Buffer output to suppress any accidental echo from the CI bootstrap
        ob_start();

        try {
            putenv('APP_ENV=testing');
            $_SERVER['APP_ENV'] = 'testing';

            // Load the CI3 front controller, which boots the framework. index.php guards its own
            // BASEPATH define() against this exact case (see the comment there) - tests/bootstrap.php
            // may already have defined it for a unit test that ran first in the same process.
            require_once __DIR__ . '/../index.php';

            self::$ci = get_instance();
        } finally {
            ob_end_clean();
        }

        if (!self::$ci) {
            throw new RuntimeException('Failed to bootstrap CodeIgniter instance.');
        }

        // Restore PHPUnit's error and exception handlers that CI3 overrode during boot
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * Get the shared CodeIgniter instance.
     *
     * @return object The CI_Controller singleton instance.
     */
    protected static function ci(): object
    {
        if (!self::$ci) {
            self::boot_ci();
        }

        return self::$ci;
    }

    /**
     * Get the database instance from the current CI context.
     *
     * @return object The CI database instance.
     */
    protected static function db(): object
    {
        return self::ci()->db;
    }

    /**
     * Check if the environment is running in multi-tenant mode.
     *
     * Multi-tenant mode is active when the master 'default' database has a 'tenants' table.
     * Single-tenant/standalone deployments (e.g., Salon Flora) do not have this table and should
     * skip tests that depend on multi-tenancy.
     *
     * @return bool True if multi-tenant mode is enabled, false otherwise.
     */
    protected static function is_multi_tenant(): bool
    {
        return self::db()->table_exists('tenants');
    }

    /**
     * Mark the current test as skipped if not running in multi-tenant mode.
     *
     * Call this in setUp() or at the start of a test method if it depends on multi-tenancy.
     */
    protected static function require_multi_tenant(): void
    {
        if (!self::is_multi_tenant()) {
            static::markTestSkipped('Not running against a multi-tenant environment.');
        }
    }

    /**
     * Connect to a tenant's database and set up the tenant context.
     *
     * This method replicates the logic from Console::connect_tenant() and EA_Controller::resolve_tenant().
     * It swaps $this->db to the tenant's own database and sets tenant_context() so encryption helpers
     * use the tenant's own PII keys.
     *
     * @param array $tenant Must contain: db_host, db_username, db_password (encrypted), db_name,
     *                      pii_enc_key (encrypted), pii_hash_key (encrypted).
     *
     * @throws RuntimeException If the tenant data is malformed or database connection fails.
     */
    protected static function connect_tenant(array $tenant): void
    {
        if (empty($tenant['db_host']) || empty($tenant['db_username']) || empty($tenant['db_name'])) {
            throw new RuntimeException('Tenant data is missing required fields: db_host, db_username, or db_name.');
        }

        if (empty($tenant['db_password']) || empty($tenant['pii_enc_key']) || empty($tenant['pii_hash_key'])) {
            throw new RuntimeException('Tenant data is missing encrypted fields: db_password, pii_enc_key, or pii_hash_key.');
        }

        $ci = self::ci();

        $tenant_db_config = [
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
        ];

        $ci->load->database($tenant_db_config, false, true);

        tenant_context([
            'id' => (int) ($tenant['id'] ?? 0),
            'subdomain' => $tenant['subdomain'] ?? '',
            'pii_enc_key' => tenant_master_decrypt($tenant['pii_enc_key']),
            'pii_hash_key' => tenant_master_decrypt($tenant['pii_hash_key']),
        ]);

        // CRITICAL: dbforge is bound to whatever $this->db WAS at the moment it was loaded and does
        // NOT follow later $this->db swaps on its own. Without this reload, a migration's CREATE TABLE
        // could run against the PREVIOUS tenant's database instead of the one just connected to.
        $ci->load->dbforge();

        // CI_Migration normally auto-creates its "migrations" tracking table in its constructor
        // (which runs ONCE per process due to the Loader's caching), but only for the FIRST tenant
        // this process ever connects to. Every subsequent tenant needs it created here.
        if (!self::db()->table_exists('migrations')) {
            $ci->dbforge->add_field(['version' => ['type' => 'BIGINT', 'constraint' => 20]]);
            $ci->dbforge->create_table('migrations', true);
            self::db()->insert('migrations', ['version' => 0]);
        }
    }

    /**
     * Set up the test: boot CI if needed and begin a database transaction.
     *
     * All database changes made during the test are rolled back at tearDown(), providing test isolation.
     * This does NOT work for DDL (CREATE/ALTER/DROP table) statements — tests that run migrations
     * must clean up manually and should be marked with a skip guard in require_multi_tenant().
     */
    protected function setUp(): void
    {
        parent::setUp();
        self::boot_ci();
        self::db()->trans_begin();
    }

    /**
     * Reconnect to the master DB ('default' connection group) and reset tenant context.
     */
    protected static function connect_master(): void
    {
        $ci = self::ci();
        $ci->load->database('default', false, true);
        $ci->load->dbforge();
        if (function_exists('tenant_context_clear')) {
            tenant_context_clear();
        }
    }

    /**
     * Tear down the test: roll back any database changes made during the test.
     */
    protected function tearDown(): void
    {
        self::db()->trans_rollback();

        self::connect_master();

        parent::tearDown();
    }
}
