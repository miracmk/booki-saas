<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - payment gateway abstract base class (2026-08-27).
 *
 * Common functionality for all gateway implementations.
 * ---------------------------------------------------------------------------- */

abstract class Payment_gateway_abstract implements Payment_gateway_interface
{
    /**
     * Gateway settings (from payment_settings table row).
     *
     * @var array
     */
    protected array $settings;

    /**
     * CodeIgniter instance (for logging, etc.).
     *
     * @var CI_Controller
     */
    protected CI_Controller $CI;

    /**
     * Constructor - initialize with settings array.
     *
     * @param array $settings Payment settings row (includes active_gateway, API keys, etc.).
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->CI = &get_instance();
    }

    /**
     * Log an error message to CodeIgniter's error log.
     *
     * @param string $message Error message.
     */
    protected function log_error(string $message): void
    {
        log_message('error', static::class . ' - ' . $message);
    }

    /**
     * Log an info message to CodeIgniter's debug log.
     *
     * @param string $message Info message.
     */
    protected function log_info(string $message): void
    {
        log_message('debug', static::class . ' - ' . $message);
    }

    /**
     * Get a setting value (with optional encryption-aware decryption for sensitive fields).
     *
     * @param string $key Setting key.
     *
     * @return mixed Setting value, or null if not found.
     */
    protected function get_setting(string $key): mixed
    {
        return $this->settings[$key] ?? null;
    }
}
