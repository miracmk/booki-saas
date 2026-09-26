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
 * BooKi input.
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
 *
 * @property string $raw_input_stream
 */
class App_Input extends CI_Input
{
    /**
     * Fetch an item from JSON data.
     *
     * @param string|null $index Index for item to be fetched from the JSON payload.
     * @param bool|false $xss_clean Whether to apply XSS filtering
     *
     * @return mixed
     */
    public function json(?string $index = null, bool $xss_clean = true): mixed
    {
        /** @var App_Controller $CI */
        $CI = &get_instance();

        if (strpos((string) $CI->input->get_request_header('Content-Type'), 'application/json') === false) {
            return null;
        }

        $input_stream = $CI->input->raw_input_stream;

        if (empty($input_stream)) {
            return null;
        }

        $payload = json_decode($input_stream, true);

        if ($payload === null && json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        if ($xss_clean && is_array($payload)) {
            $payload = $this->xss_clean_recursive($payload, $CI->security);
        }

        if (empty($index)) {
            return $payload;
        }

        return $payload[$index] ?? null;
    }

    /**
     * Recursively apply XSS cleaning to an array.
     *
     * @param array $data The data to clean.
     * @param CI_Security $security The security instance.
     *
     * @return array The cleaned data.
     */
    private function xss_clean_recursive(array $data, CI_Security $security): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->xss_clean_recursive($value, $security);
            } elseif (is_string($value)) {
                $data[$key] = $security->xss_clean($value);
            }
            // Non-string, non-array values are left as-is (integers, booleans, etc.)
        }

        return $data;
    }
}

if (!class_exists('App_Input', false)) {
    class_alias(App_Input::class, 'App_Input');
}
