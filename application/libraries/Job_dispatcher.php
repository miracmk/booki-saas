<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Job Dispatcher (Faz 32+33, 2026-08-28).
 *
 * Dispatches queued jobs to their registered handlers.
 *
 * CRITICAL SECURITY CONSTRAINT: Handlers are NEVER derived from user input,
 * database values, or any external source. They are resolved ONLY via a hardcoded
 * whitelist in self::HANDLERS, eliminating any possibility of arbitrary code
 * execution (RCE). The 'handler' column in the jobs table is just a key into
 * this whitelist - never a direct callable. Any attempt to dispatch an unknown
 * handler raises an exception immediately.
 *
 * During Faz 33, handlers will be populated and wired into the existing
 * notification send paths. For now, the HANDLERS array is empty - the dispatch
 * mechanism itself is complete and ready.
 * ---------------------------------------------------------------------------- */

class Job_dispatcher
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * Hardcoded whitelist of available job handlers.
     *
     * Each entry maps a handler key to [ClassName::class, 'method_name'].
     * Only handlers listed here may be dispatched - anything else raises an exception.
     *
     * Example (populated in Faz 33):
     * [
     *     'notifications.send_sms' => [Notifications::class, 'handle_queued_sms'],
     *     'notifications.send_email' => [Notifications::class, 'handle_queued_email'],
     *     'webhook.retry' => [Webhooks::class, 'handle_queued_retry'],
     * ]
     */
    private const HANDLERS = [
        // Populated in Faz 33 when synchronous send paths are migrated to queued jobs.
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Dispatch a job to its registered handler.
     *
     * Looks up the job's handler in self::HANDLERS and invokes the mapped
     * [class, method] with ($this->CI, $payload) as arguments.
     *
     * @param array $job The job record from the database, including 'handler' and 'payload'.
     * @return void
     * @throws RuntimeException if the handler is not found in the whitelist.
     */
    public function dispatch(array $job): void
    {
        $handler_key = $job['handler'];

        if (!isset(self::HANDLERS[$handler_key])) {
            throw new RuntimeException('Unknown job handler: ' . $handler_key);
        }

        [$class_name, $method_name] = self::HANDLERS[$handler_key];

        // Extract the library name from the class name (last component, lowercased)
        $class_name_parts = explode('\\', $class_name);
        $library_name = strtolower(end($class_name_parts));

        // Load the library/class if not already loaded
        if (!class_exists($class_name)) {
            $this->CI->load->library($library_name);
        }

        $payload = json_decode($job['payload'], true);

        // Ensure the class/library instance is available for calling the method
        if (!isset($this->CI->$library_name)) {
            // If still not loaded, try to instantiate directly
            $this->CI->$library_name = new $class_name();
        }

        // Invoke the handler method: handler($this->CI, $payload)
        call_user_func([$this->CI->$library_name, $method_name], $this->CI, $payload);
    }
}
