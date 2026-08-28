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
     * Deliberately excludes appointment-DELETED email: by the time notify_appointment_deleted()
     * runs, the appointment row is already gone (the caller deletes it first), so a queued job
     * holding only an appointment_id could never re-fetch what it needs later - see
     * Notifications::notify_appointment_deleted()'s docblock. That send site stays synchronous,
     * same category of decision as Recovery.php's password-reset email.
     */
    private const HANDLERS = [
        'notifications.send_sms' => [Notifications::class, 'handle_queued_sms'],
        'notifications.send_whatsapp' => [Notifications::class, 'handle_queued_whatsapp'],
        'notifications.send_telegram' => [Notifications::class, 'handle_queued_telegram'],
        'notifications.appointment_saved_email' => [Notifications::class, 'handle_queued_appointment_saved_email'],
        'webhooks.deliver' => [Webhooks_client::class, 'handle_queued_delivery'],
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
