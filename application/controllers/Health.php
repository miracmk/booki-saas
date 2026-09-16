<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Health check controller (Faz 32+33, 2026-08-28).
 *
 * Provides unauthenticated health monitoring endpoints for infrastructure
 * observability. Both endpoints return 200 with status data on success or
 * 503 only if the master DB is unreachable.
 * ---------------------------------------------------------------------------- */

class Health extends EA_Controller
{
    /**
     * Health controller constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('jobs_model');
    }

    /**
     * Lightweight health check endpoint (no DB access).
     *
     * Always returns 200 with basic uptime status. This is useful for
     * load balancers and container orchestration health probes that just
     * need to know if the application is running.
     *
     * @return void
     */
    public function index(): void
    {
        try {
            json_response([
                'status' => 'ok',
                'ts' => date('c'),
                'version' => config('version') ?? 'unknown',
            ], 200);
        } catch (Throwable $e) {
            json_response([
                'status' => 'error',
                'error' => 'Health endpoint failed unexpectedly.',
            ], 500);
        }
    }

    /**
     * Deep health check endpoint (token-gated, includes DB checks and queue monitoring).
     *
     * Requires an X-Health-Token header matching the 'health_token' setting for
     * constant-time token comparison. Returns a detailed status including:
     * - Master DB reachability
     * - Active tenant count (multi-tenant mode only)
     * - Per-tenant checks: migration drift, queue depth, failed jobs, pending age
     * - Storage/logs writability
     *
     * Returns 200 with degraded/error status in the JSON body if checks fail,
     * or 503 only if the master DB itself is unreachable.
     *
     * @return void
     */
    public function deep(): void
    {
        try {
            // Verify health token (constant-time comparison). MUST use master_setting(), not
            // setting() - this endpoint runs against the master DB connection (it checks master
            // reachability + iterates every tenant), which has no `settings` table at all. See
            // Console::master_install() where this master-level token is generated, and migration
            // 124's docblock for why a per-tenant setting can't work here (a real bug caught by an
            // actual HTTP request in isolated Docker testing, not just code review).
            $token = request('token') ?? $_SERVER['HTTP_X_HEALTH_TOKEN'] ?? '';
            $expected_token = master_setting('health_token');

            if (!$expected_token || !hash_equals($token, $expected_token)) {
                json_response([
                    'status' => 'forbidden',
                    'error' => 'Invalid or missing health token.',
                ], 403);
                return;
            }

            // Start building the response.
            $response = [
                'status' => 'ok',
                'ts' => date('c'),
                'checks' => [],
            ];

            // Check master DB reachability.
            try {
                $this->db->query('SELECT 1');
                $response['checks']['master_db'] = ['status' => 'ok'];
            } catch (Throwable $e) {
                $response['status'] = 'error';
                $response['checks']['master_db'] = [
                    'status' => 'error',
                    'error' => 'Master database unreachable.',
                ];

                // Master DB unreachable = 503
                json_response($response, 503);
                return;
            }

            // Check active tenant count (multi-tenant mode only).
            if (is_multi_tenant_mode()) {
                try {
                    $tenant_count = $this->db->get_where('tenants', ['status' => 'active'])->num_rows();
                    $response['checks']['active_tenants'] = [
                        'status' => 'ok',
                        'count' => $tenant_count,
                    ];
                } catch (Throwable $e) {
                    $response['status'] = 'degraded';
                    $response['checks']['active_tenants'] = [
                        'status' => 'error',
                        'error' => 'Could not retrieve tenant count.',
                    ];
                }

                // Per-tenant deep checks.
                $response['checks']['tenants'] = [];

                try {
                    $tenants = $this->db->get_where('tenants', ['status' => 'active'])->result_array();

                    foreach ($tenants as $tenant) {
                        $tenant_check = $this->check_tenant_deep($tenant);
                        $response['checks']['tenants'][$tenant['subdomain']] = $tenant_check;

                        if ($tenant_check['status'] !== 'ok') {
                            $response['status'] = 'degraded';
                        }
                    }
                } catch (Throwable $e) {
                    $response['status'] = 'degraded';
                    $response['checks']['tenants_enumeration'] = [
                        'status' => 'error',
                        'error' => 'Could not enumerate tenants.',
                    ];
                }
            }

            // Check storage/logs writability.
            try {
                $log_path = config('log_path');

                if (!is_writable($log_path)) {
                    $response['status'] = 'degraded';
                    $response['checks']['logs_writable'] = [
                        'status' => 'error',
                        'error' => 'Log directory is not writable.',
                    ];
                } else {
                    $response['checks']['logs_writable'] = ['status' => 'ok'];
                }
            } catch (Throwable $e) {
                $response['status'] = 'degraded';
                $response['checks']['logs_writable'] = [
                    'status' => 'error',
                    'error' => 'Could not check log directory.',
                ];
            }

            // Never 503 here - master DB unreachability (the only 503 case) already returned above.
            json_response($response, 200);
        } catch (Throwable $e) {
            log_message('error', 'Health::deep() failed unexpectedly: ' . $e->getMessage());
            json_response([
                'status' => 'error',
                'ts' => date('c'),
                'error' => 'Health check failed unexpectedly.',
            ], 503);
        }
    }

    /**
     * Perform deep checks for a single tenant database.
     *
     * Checks:
     * - Migration state (compare latest version in migrations table vs files)
     * - Queue depth (count by status)
     * - Failed jobs in last hour
     * - Oldest pending job age
     *
     * @param array $tenant Tenant row from the master DB.
     * @return array Status of tenant's checks.
     */
    private function check_tenant_deep(array $tenant): array
    {
        $result = [
            'status' => 'ok',
            'checks' => [],
        ];

        try {
            // Connect to the tenant's database.
            $tenant_db = $this->load->database(
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
                true, // return connection, don't replace $this->db
                true, // query_builder=true - REQUIRED, this method chains ->where()/->count_all_results()
                // below (Loader::database()'s 3rd param is $query_builder, not a "silent failure" flag -
                // passing false here would strip CI_DB_query_builder and fatal on the first ->where() call).
            );

            // Check migration state.
            try {
                if ($tenant_db->table_exists('migrations')) {
                    $latest_version = $tenant_db->query(
                        'SELECT MAX(version) as latest FROM ' . $tenant_db->dbprefix('migrations'),
                    )->row_array();
                    $latest_version = (int) ($latest_version['latest'] ?? 0);

                    // Get the highest-numbered migration file in application/migrations/.
                    $migration_files = glob(APPPATH . 'migrations/*.php');
                    $highest_file_version = 0;

                    if (is_array($migration_files)) {
                        foreach ($migration_files as $file) {
                            preg_match('/(\d+)_/', basename($file), $matches);
                            if (isset($matches[1])) {
                                $highest_file_version = max($highest_file_version, (int) $matches[1]);
                            }
                        }
                    }

                    if ($latest_version < $highest_file_version) {
                        $result['status'] = 'degraded';
                        $result['checks']['migrations'] = [
                            'status' => 'warning',
                            'current' => $latest_version,
                            'available' => $highest_file_version,
                        ];
                    } else {
                        $result['checks']['migrations'] = [
                            'status' => 'ok',
                            'version' => $latest_version,
                        ];
                    }
                } else {
                    $result['status'] = 'degraded';
                    $result['checks']['migrations'] = [
                        'status' => 'error',
                        'error' => 'Migrations table not found.',
                    ];
                }
            } catch (Throwable $e) {
                $result['status'] = 'degraded';
                $result['checks']['migrations'] = [
                    'status' => 'error',
                    'error' => 'Could not check migration state.',
                ];
            }

            // Check queue depth.
            try {
                if ($tenant_db->table_exists('jobs')) {
                    $counts = $this->get_queue_counts($tenant_db);
                    $result['checks']['queue'] = [
                        'status' => 'ok',
                        'pending' => $counts['pending'],
                        'reserved' => $counts['reserved'],
                        'succeeded' => $counts['succeeded'],
                        'failed' => $counts['failed'],
                    ];
                }
            } catch (Throwable $e) {
                $result['status'] = 'degraded';
                $result['checks']['queue'] = [
                    'status' => 'error',
                    'error' => 'Could not check queue depth.',
                ];
            }

            // Check failed jobs in the last hour.
            try {
                if ($tenant_db->table_exists('jobs')) {
                    $cutoff = new DateTime('now', new DateTimeZone('UTC'));
                    $cutoff->modify('-60 minutes');

                    $failed_count = $tenant_db
                        ->where('status', 'failed')
                        ->where('completed_at >=', $cutoff->format('Y-m-d H:i:s'))
                        ->count_all_results('jobs');

                    if ($failed_count > 0) {
                        $result['status'] = 'degraded';
                    }

                    $result['checks']['recent_failures'] = [
                        'status' => $failed_count > 0 ? 'warning' : 'ok',
                        'count_last_hour' => $failed_count,
                    ];
                }
            } catch (Throwable $e) {
                $result['status'] = 'degraded';
                $result['checks']['recent_failures'] = [
                    'status' => 'error',
                    'error' => 'Could not check recent failures.',
                ];
            }

            // Check oldest pending job age.
            try {
                if ($tenant_db->table_exists('jobs')) {
                    $age_result = $tenant_db->query(
                        'SELECT TIMESTAMPDIFF(SECOND, MIN(available_at), NOW()) as age ' .
                        'FROM ' . $tenant_db->dbprefix('jobs') . ' ' .
                        'WHERE status = ?',
                        ['pending'],
                    )->row_array();

                    if ($age_result && $age_result['age'] !== null) {
                        $age_seconds = (int) $age_result['age'];
                        $status = $age_seconds > 3600 ? 'warning' : 'ok'; // warn if > 1 hour

                        if ($status === 'warning') {
                            $result['status'] = 'degraded';
                        }

                        $result['checks']['oldest_pending_age'] = [
                            'status' => $status,
                            'age_seconds' => $age_seconds,
                        ];
                    } else {
                        $result['checks']['oldest_pending_age'] = [
                            'status' => 'ok',
                            'age_seconds' => null,
                        ];
                    }
                }
            } catch (Throwable $e) {
                $result['status'] = 'degraded';
                $result['checks']['oldest_pending_age'] = [
                    'status' => 'error',
                    'error' => 'Could not check job age.',
                ];
            }
        } catch (Throwable $e) {
            $result['status'] = 'error';
            $result['error'] = 'Could not connect to tenant database.';
        }

        return $result;
    }

    /**
     * Get queue job counts by status for a specific database connection.
     *
     * @param CI_DB_query_builder $db Database connection to query.
     * @return array Count of jobs by status.
     */
    private function get_queue_counts(CI_DB_query_builder $db): array
    {
        $result = $db->query(
            'SELECT status, COUNT(*) as count FROM ' . $db->dbprefix('jobs') . ' GROUP BY status',
        )->result_array();

        $counts = ['pending' => 0, 'reserved' => 0, 'succeeded' => 0, 'failed' => 0];

        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }

        return $counts;
    }
}
