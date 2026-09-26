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
 * BooKi log.
 *
 * @property App_Benchmark $benchmark
 * @property App_Cache $cache
 * @property App_Calendar $calendar
 * @property App_Config $config
 * @property App_DB_forge $dbforge
 * @property App_DB_query_builder $db
 * @property App_DB_utility $dbutil
 * @property App_Email $email
 * @property App_Encrypt $encrypt
 * @property App_Encryption $encryption
 * @property App_Exceptions $exceptions
 * @property App_Hooks $hooks
 * @property App_Input $input
 * @property App_Lang $lang
 * @property App_Loader $load
 * @property App_Log $log
 * @property App_Migration $migration
 * @property App_Output $output
 * @property App_Profiler $profiler
 * @property App_Router $router
 * @property App_Security $security
 * @property App_Session $session
 * @property App_Upload $upload
 * @property App_URI $uri
 */
class App_Log extends CI_Log
{
    /**
     * Override write_log to emit structured JSON logs.
     *
     * The JSON format is designed for modern log aggregation systems and includes:
     * - Correlation ID (for distributed request tracing)
     * - Tenant context (for multi-tenant deployments)
     * - User ID (when a session is active)
     * - URI and IP (request metadata)
     * - Process ID (for worker process identification)
     *
     * Falls back to stock plain-text logging if anything throws, so logging is never
     * a new source of fatal errors.
     *
     * @param string $level Log level (debug, info, warn, error, etc.)
     * @param string $msg Log message
     * @return bool True on success, false otherwise.
     */
    public function write_log($level, $msg)
    {
        // No type declarations here, deliberately - the parent CI_Log::write_log($level, $msg)
        // (system/core/Log.php) declares neither parameter nor return types. PHP's method
        // override compatibility rules do not allow a child to add types the parent lacks;
        // doing so is a FATAL "Declaration must be compatible" error that takes down the whole
        // app the moment anything tries to log (confirmed - this exact mistake was caught by a
        // real `console master_install` run, not just php -l, which does not catch this class
        // of error since it's a runtime/inheritance-compatibility check, not a syntax error).
        // BooKi (Dalga 2) - IMPORTANT: stock CI_Log::write_log() (system/core/Log.php)
        // gates on $this->_levels[$level], and that array's keys are UPPERCASE
        // ('ERROR'=>1,'DEBUG'=>2,'INFO'=>3,'ALL'=>4) - but log_message() (system/core/Common.php)
        // never uppercases $level, and every call site in this codebase (129+) passes lowercase
        // ('error', 'warning', 'notice', etc). The isset() check on the uppercase-keyed array
        // therefore ALWAYS fails for every real call site, so write_log() returns false before
        // writing anything - stock logging in this app is a near-total no-op today (confirmed:
        // this is exactly the "Undefined array key" warning observed during earlier testing).
        // This override fixes that (case-insensitive, sensible level names) rather than
        // replicating the broken gate - going from "silently drops almost everything" to
        // "actually logs" is the whole point of this faz, not "logging more than before".
        if ($this->_threshold === 0) {
            return false;
        }

        $level_int = $this->_level_order($level);

        if ($level_int > $this->_threshold) {
            return false;
        }

        try {
            // Build the structured record.
            $record = [
                'ts' => date('c'), // ISO8601
                'level' => strtoupper($level),
                'msg' => $msg,
                'tenant' => null,
                'correlation_id' => null,
                'user_id' => null,
                'uri' => $_SERVER['REQUEST_URI'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'pid' => getmypid(),
            ];

            // Extract tenant context (guard against not being loaded yet).
            if (function_exists('tenant_context')) {
                $tenant = tenant_context();
                if ($tenant !== null && isset($tenant['subdomain'])) {
                    $record['tenant'] = $tenant['subdomain'];
                }
            }

            // Extract correlation ID (guard against not being loaded yet).
            if (function_exists('correlation_id')) {
                $record['correlation_id'] = correlation_id();
            }

            // Extract user ID from session if available (guard defensively).
            try {
                if (function_exists('session') && session('user_id')) {
                    $record['user_id'] = (int) session('user_id');
                }
            } catch (Throwable) {
                // No session or session error - leave user_id null.
            }

            // Write the JSON-encoded record to the log file (same path as stock CI3 logs).
            $filepath = $this->_log_path . 'log-' . date('Y-m-d') . '.php';

            // Check if this is a fresh file - if so, prepend the <?php exit; guard line.
            $is_new_file = !file_exists($filepath);

            $handle = fopen($filepath, 'a');

            if ($handle === false) {
                return false;
            }

            // Stock CI3 guard line - must be exactly this (preventing direct web access).
            if ($is_new_file) {
                fwrite($handle, "<?php exit;\n");
            }

            // Write one JSON line per log entry.
            $json = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            fwrite($handle, $json . "\n");
            fclose($handle);

            return true;
        } catch (Throwable) {
            // Logging failed - fall back to stock plain-text behavior.
            // This ensures a logging error is never a new source of fatal errors.
            return parent::write_log($level, $msg);
        }
    }

    /**
     * Helper to map a level name to its numeric order for threshold comparison.
     * Mirrors the stock CI_Log's _level_order logic.
     *
     * @param string $level
     * @return int
     */
    private function _level_order(string $level): int
    {
        // Mirrors stock CI_Log's numeric order (error=1 is the strictest/most-important tier,
        // matching this codebase's config.php log_threshold=1 "errors only" setting) but
        // case-insensitive and covering every level name actually used across log_message()
        // call sites in this codebase (not just CI3's stock debug/info/all).
        $level_order = [
            'error' => 1,
            'warning' => 2,
            'notice' => 3,
            'info' => 3,
            'debug' => 4,
            'all' => 4,
        ];

        // Unknown level names fail open toward "always log" (order 1) rather than being
        // silently dropped - an unrecognized level is more likely a typo worth surfacing
        // than noise worth suppressing.
        return $level_order[strtolower($level)] ?? 1;
    }
}

if (!class_exists('App_Log', false)) {
    class_alias(App_Log::class, 'App_Log');
}
