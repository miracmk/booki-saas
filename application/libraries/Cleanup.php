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
 * Cleanup library.
 *
 * Handles the cleanup of expired sessions, old logs, cache files, and customer data based on retention policies.
 *
 * @package Libraries
 */
class Cleanup
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * Cleanup constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('customers_model');
    }

    /**
     * Run all cleanup tasks.
     *
     * @throws Exception
     */
    public function run(): void
    {
        $this->cleanup_sessions();
        $this->cleanup_logs();
        $this->cleanup_cache();
        $this->cleanup_customer_data();
        $this->cleanup_jobs();
        $this->cleanup_data_exports();
    }

    /**
     * Clean up expired session files (older than retention period).
     */
    public function cleanup_sessions(): void
    {
        $session_path = APPPATH . '../storage/sessions';
        $max_age_seconds = STORAGE_RETENTION_DAYS * 86400;

        if (!is_dir($session_path)) {
            response(PHP_EOL . '⇾ Session directory not found.' . PHP_EOL);
            return;
        }

        $deleted_count = 0;
        $cutoff_time = time() - $max_age_seconds;

        foreach (glob($session_path . '/ea_session*') as $file) {
            if (is_file($file) && filemtime($file) < $cutoff_time) {
                if (unlink($file)) {
                    $deleted_count++;
                }
            }
        }

        response(PHP_EOL . "⇾ Session cleanup completed. Deleted {$deleted_count} expired session file(s)." . PHP_EOL);
    }

    /**
     * Clean up old log files (older than retention period).
     */
    public function cleanup_logs(): void
    {
        $log_path = APPPATH . '../storage/logs';

        if (!is_dir($log_path)) {
            response('⇾ Log directory not found.' . PHP_EOL);
            return;
        }

        $deleted_count = 0;
        $cutoff_time = time() - (STORAGE_RETENTION_DAYS * 86400);

        foreach (glob($log_path . '/log-*.php') as $file) {
            if (is_file($file) && filemtime($file) < $cutoff_time) {
                if (unlink($file)) {
                    $deleted_count++;
                }
            }
        }

        response("⇾ Log cleanup completed. Deleted {$deleted_count} old log file(s)." . PHP_EOL);
    }

    /**
     * Clean up old cache files (older than retention period).
     */
    public function cleanup_cache(): void
    {
        $cache_path = APPPATH . '../storage/cache';

        if (!is_dir($cache_path)) {
            response('⇾ Cache directory not found.' . PHP_EOL);
            return;
        }

        $deleted_count = 0;
        $cutoff_time = time() - (STORAGE_RETENTION_DAYS * 86400);

        $files = glob($cache_path . '/*');

        foreach ($files as $file) {
            if (is_file($file) && !in_array(basename($file), ['index.html', '.gitkeep', '.htaccess'])) {
                if (filemtime($file) < $cutoff_time) {
                    if (unlink($file)) {
                        $deleted_count++;
                    }
                }
            }
        }

        response("⇾ Cache cleanup completed. Deleted {$deleted_count} old cache file(s)." . PHP_EOL);
    }

    /**
     * Clean up customer data based on retention policy.
     *
     * @throws Exception
     */
    /**
     * Salon Flora customization (2026-08-24, KVKK data retention) - the stock version of this method
     * called customers_model->delete() on a match, which permanently DELETES the `users` row.
     * appointments.id_users_customer is ON DELETE CASCADE, so that would also destroy the customer's
     * appointment/payment history - which the business needs to keep for accounting (VUK) purposes
     * regardless of how old the customer relationship is. Changed to call the new
     * customers_model->anonymize() instead: the row (and their appointment/revenue history) stays,
     * only the PII is scrubbed. Also added "AND c.anonymized_at IS NULL" so an already-anonymized
     * customer isn't redundantly reprocessed on every nightly run.
     */
    public function cleanup_customer_data(): void
    {
        $data_retention_days = (int) setting('data_retention_days');

        if ($data_retention_days <= 0) {
            response('⇾ Data retention is disabled (set to 0 days).' . PHP_EOL . PHP_EOL);
            return;
        }

        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$data_retention_days} days"));

        $db = $this->CI->db;

        $query = $db->query("
            SELECT DISTINCT c.id
            FROM `" . $db->dbprefix('users') . "` c
            INNER JOIN `" . $db->dbprefix('roles') . "` r ON r.id = c.id_roles AND r.slug = 'customer'
            WHERE c.anonymized_at IS NULL
            AND c.id NOT IN (
                SELECT DISTINCT id_users_customer
                FROM `" . $db->dbprefix('appointments') . "`
                WHERE end_datetime >= ?
            )
            AND c.create_datetime < ?
        ", [$cutoff_date, $cutoff_date]);

        $customers_to_anonymize = $query->result_array();
        $anonymized_count = 0;

        foreach ($customers_to_anonymize as $customer) {
            try {
                $this->CI->customers_model->anonymize((int) $customer['id']);
                $anonymized_count++;

                // BooKi customization - runs in CLI/cron context (no session), so id_users/
                // actor_role are correctly null here - that absence IS the signal that this was the
                // automated retention job, not a human-triggered erasure.
                audit_log('customer.anonymize', 'customer', (int) $customer['id'], [
                    'reason' => 'automated_retention',
                    'data_retention_days' => $data_retention_days,
                ]);

                response('⇾ Anonymized customer ID: ' . $customer['id'] . PHP_EOL);
            } catch (Exception $e) {
                response('⇾ Failed to anonymize customer ID: ' . $customer['id'] . ' - ' . $e->getMessage() . PHP_EOL);
            }
        }

        response(
            PHP_EOL . "⇾ Data retention cleanup completed. Anonymized {$anonymized_count} customer(s)." . PHP_EOL . PHP_EOL,
        );
    }

    /**
     * Clean up old job records from the queue (older than retention period).
     *
     * Deletes completed (succeeded and failed) job records to maintain database size
     * and prevent indefinite accumulation of historical job data.
     *
     * @throws Exception
     */
    public function cleanup_jobs(int $retention_days = 30): void
    {
        $this->CI->load->model('jobs_model');

        $deleted_count = $this->CI->jobs_model->delete_old($retention_days);

        response(
            "⇾ Job queue cleanup completed. Deleted {$deleted_count} old job record(s)." . PHP_EOL,
        );
    }

    /**
     * Faz 30 (KVKK) - expire ready-but-unclaimed data exports and delete their files from disk.
     * Mirrors Customers_model::invalidate_data_exports()'s path-traversal guard and sibling-file
     * cleanup (the three loose-file names in 'files' format, or just the single .zip in 'zip'
     * format) - the difference here is WHY the file goes away (time, not an anonymize() call).
     */
    public function cleanup_data_exports(): void
    {
        $this->CI->load->model('data_requests_model');

        $expirable = $this->CI->data_requests_model->get_expirable(date('Y-m-d H:i:s'));
        $deleted_files = 0;

        $base = realpath(storage_path('exports'));

        foreach ($expirable as $request) {
            if (!empty($request['file_path']) && $base !== false) {
                $abs = realpath(storage_path($request['file_path']));

                if ($abs !== false && strpos($abs, $base . DIRECTORY_SEPARATOR) === 0 && is_file($abs)) {
                    @unlink($abs);
                    $deleted_files++;

                    foreach (['export.json', 'export.html', 'BENIOKU.txt'] as $sibling) {
                        $sibling_path = dirname($abs) . DIRECTORY_SEPARATOR . $sibling;

                        if (is_file($sibling_path)) {
                            @unlink($sibling_path);
                        }
                    }

                    @rmdir(dirname($abs));
                }
            }

            $this->CI->data_requests_model->mark_expired($request['id']);
        }

        response(
            '⇾ Data export cleanup completed. Deleted ' .
                $deleted_files .
                ' file(s), expired ' .
                count($expirable) .
                ' request(s).' .
                PHP_EOL,
        );
    }
}
