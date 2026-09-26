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
 * BooKi security.
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
class App_Security extends CI_Security
{
    /**
     * CSRF Verify
     *
     * @return    CI_Security
     */
    public function csrf_verify()
    {
        // If it's not a POST request we will set the CSRF cookie
        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
            return $this->csrf_set_cookie();
        }

        // Check if URI has been whitelisted from CSRF checks
        if ($exclude_uris = config_item('csrf_exclude_uris')) {
            $uri = load_class('URI', 'core');
            foreach ($exclude_uris as $excluded) {
                if (preg_match('#^' . $excluded . '$#i' . (UTF8_ENABLED ? 'u' : ''), $uri->uri_string())) {
                    return $this;
                }
            }
        }

        // Check CSRF token validity, but don't error on mismatch just yet - we'll want to regenerate
        $csrf_token = $_POST[$this->_csrf_token_name]
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN']
            ?? ($_SERVER['HTTP_X_CSRF'] ?? null));

        if (empty($csrf_token) && isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $json = json_decode($input, true);
            if (is_array($json) && !empty($json[$this->_csrf_token_name])) {
                $csrf_token = $json[$this->_csrf_token_name];
            }
        }

        $valid =
            isset($csrf_token, $_COOKIE[$this->_csrf_cookie_name]) &&
            is_string($csrf_token) &&
            is_string($_COOKIE[$this->_csrf_cookie_name]) &&
            hash_equals($csrf_token, $_COOKIE[$this->_csrf_cookie_name]);

        // We kill this since we're done, and we don't want to pollute the _POST array
        unset($_POST[$this->_csrf_token_name]);

        // Regenerate on every submission?
        if (config_item('csrf_regenerate')) {
            // Nothing should last forever
            unset($_COOKIE[$this->_csrf_cookie_name]);
            $this->_csrf_hash = null;
        }

        $this->_csrf_set_hash();
        $this->csrf_set_cookie();

        if ($valid !== true) {
            $this->csrf_show_error();
        }

        log_message('info', 'CSRF token verified');
        return $this;
    }
}

if (!class_exists('App_Security', false)) {
    class_alias(App_Security::class, 'App_Security');
}
