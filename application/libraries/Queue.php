<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Queue Library (Faz 32+33, 2026-08-28).
 *
 * Unified job queue management: pushing jobs, reserving them for processing,
 * and marking completion/failure with exponential backoff retry logic.
 *
 * CRITICAL SAFETY NOTE: push() is wrapped in try/catch internally and NEVER
 * throws. Every job queue operation that might fail (database connectivity,
 * constraint violations, etc) is logged and returns a safe default (null for
 * push, empty array for reserve, etc). Call sites depend on this - they fall
 * through to synchronous execution if the queue push fails, ensuring no
 * request goes unanswered even if the queue is completely broken.
 * ---------------------------------------------------------------------------- */

class Queue
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('jobs_model');
    }

    /**
     * Check if the job queue is enabled in settings.
     *
     * @return bool True if queue_enabled setting is '1', false otherwise.
     */
    public function enabled(): bool
    {
        return setting('queue_enabled') === '1';
    }

    /**
     * Push a job onto the queue.
     *
     * This method is wrapped in try/catch internally. If the database insert fails
     * for any reason, the error is logged and null is returned - NEVER an exception.
     * Call sites must handle the null return value by falling back to synchronous
     * execution of the task.
     *
     * @param string $channel The channel type: 'email', 'sms', 'whatsapp', 'telegram', 'webhook', 'export'.
     * @param string $handler A key from Job_dispatcher::HANDLERS. Must be hardcoded in the dispatcher,
     *                        never derived from user input.
     * @param array $payload JSON-serializable array containing only IDs and non-PII values.
     *                       NEVER include phone numbers, email addresses, encrypted passwords,
     *                       or any other PII - workers re-resolve these at execution time.
     * @param array $opts Optional configuration:
     *                   'queue' => string (default: 'default') - queue name
     *                   'reference_type' => string - type of the referenced entity (e.g., 'webhook')
     *                   'reference_id' => int - ID of the referenced entity
     *                   'correlation_id' => string - UUID/correlation ID for tracing related jobs
     *                   'delay_seconds' => int - number of seconds to delay before making available
     *
     * @return int|null The new job ID, or null if insertion failed (logged internally).
     */
    public function push(string $channel, string $handler, array $payload, array $opts = []): ?int
    {
        try {
            $queue = $opts['queue'] ?? 'default';
            $reference_type = $opts['reference_type'] ?? null;
            $reference_id = $opts['reference_id'] ?? null;
            $correlation_id = $opts['correlation_id'] ?? null;
            $delay_seconds = (int) ($opts['delay_seconds'] ?? 0);

            $available_at = new DateTime('now', new DateTimeZone('UTC'));

            if ($delay_seconds > 0) {
                $available_at->modify('+' . $delay_seconds . ' seconds');
            }

            $job = [
                'queue' => $queue,
                'channel' => $channel,
                'handler' => $handler,
                'payload' => json_encode($payload),
                'status' => 'pending',
                'attempts' => 0,
                'max_attempts' => (int) setting('queue_max_attempts') ?: 3,
                'available_at' => $available_at->format('Y-m-d H:i:s'),
                'reference_type' => $reference_type,
                'reference_id' => $reference_id,
                'correlation_id' => $correlation_id,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            return $this->CI->jobs_model->insert($job);
        } catch (Throwable $e) {
            log_message('error', 'Queue::push() failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Atomically reserve up to $limit pending jobs for this worker.
     *
     * Fetches jobs whose available_at <= now(), marks them as 'reserved' with the
     * current timestamp and worker ID, and returns the full job records. This is
     * done atomically in a single UPDATE...LIMIT statement followed by a SELECT,
     * preventing race conditions between multiple concurrent workers.
     *
     * @param int $limit Maximum number of jobs to reserve in this call.
     * @param string $worker_id A unique identifier for this worker (e.g., hostname:pid).
     * @param string $queue The queue name to process (default: 'default').
     *
     * @return array Array of job records, each with 'id', 'handler', 'payload', etc.
     *               Returns empty array if no pending jobs are available.
     */
    public function reserve(int $limit, string $worker_id, string $queue = 'default'): array
    {
        try {
            return $this->CI->jobs_model->reserve_batch($limit, $worker_id, $queue);
        } catch (Throwable $e) {
            log_message('error', 'Queue::reserve() failed: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Mark a job as succeeded and set its completed_at timestamp.
     *
     * @param int $job_id The job ID to mark as succeeded.
     * @return void
     */
    public function mark_succeeded(int $job_id): void
    {
        try {
            $this->CI->jobs_model->mark_status($job_id, 'succeeded', [
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Queue::mark_succeeded() failed: ' . $e->getMessage());
        }
    }

    /**
     * Mark a job as failed and schedule a retry with exponential backoff.
     *
     * Increments the attempts counter. If attempts >= max_attempts, the job status
     * is set to 'failed' permanently. Otherwise, the job is returned to 'pending'
     * with available_at set to now() + (60 * 2^attempts) seconds, implementing
     * exponential backoff.
     *
     * @param int $job_id The job ID to mark as failed.
     * @param Throwable $e The exception that caused the failure - message is stored in last_error.
     * @return void
     */
    public function mark_failed(int $job_id, Throwable $e): void
    {
        try {
            $job = $this->CI->jobs_model->find($job_id);

            if (!$job) {
                return;
            }

            $attempts = (int) $job['attempts'] + 1;
            $max_attempts = (int) $job['max_attempts'];
            $now = new DateTime('now', new DateTimeZone('UTC'));

            if ($attempts >= $max_attempts) {
                // Final failure - give up
                $this->CI->jobs_model->mark_status($job_id, 'failed', [
                    'attempts' => $attempts,
                    'last_error' => $e->getMessage(),
                    'completed_at' => $now->format('Y-m-d H:i:s'),
                ]);
            } else {
                // Retry with exponential backoff: 60 * 2^attempts seconds
                $backoff_seconds = 60 * (2 ** ($attempts - 1));
                $next_available = clone $now;
                $next_available->modify('+' . $backoff_seconds . ' seconds');

                $this->CI->jobs_model->mark_status($job_id, 'pending', [
                    'attempts' => $attempts,
                    'last_error' => $e->getMessage(),
                    'available_at' => $next_available->format('Y-m-d H:i:s'),
                    'reserved_at' => null,
                    'reserved_by' => null,
                ]);
            }
        } catch (Throwable $e) {
            log_message('error', 'Queue::mark_failed() failed: ' . $e->getMessage());
        }
    }

    /**
     * Release reservations that have been stale for longer than $stale_minutes.
     *
     * This safety mechanism prevents dead workers from blocking jobs forever.
     * If a worker crashes or loses connectivity while a job is reserved,
     * this method will release it back to 'pending' after the timeout.
     *
     * @param int $stale_minutes Number of minutes a reservation must be stale to release (default: 10).
     * @return int The number of jobs released.
     */
    public function release_stale_reservations(int $stale_minutes = 10): int
    {
        try {
            return $this->CI->jobs_model->release_stale($stale_minutes);
        } catch (Throwable $e) {
            log_message('error', 'Queue::release_stale_reservations() failed: ' . $e->getMessage());

            return 0;
        }
    }
}
