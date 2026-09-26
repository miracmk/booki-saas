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
 * BooKi loader.
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
class App_Loader extends CI_Loader
{
    /**
     * Override the original view loader method so that layouts are also supported.
     *
     * @param string $view View filename.
     * @param array $vars An associative array of data to be extracted for use in the view.
     * @param bool $return Whether to return the view output or leave it to the Output class.
     *
     * @return object|string
     */
    public function view($view, $vars = [], $return = false)
    {
        $layout = config('layout');

        $is_layout_page = empty($layout); // This is a layout page if "layout" was undefined before the page got rendered.

        $result = $this->_ci_load([
            '_ci_view' => $view,
            '_ci_vars' => $this->_ci_prepare_view_vars($vars),
            '_ci_return' => $return,
        ]);

        $layout = config('layout');

        if ($layout && $is_layout_page) {
            $result = $this->_ci_load([
                '_ci_view' => $layout['filename'],
                '_ci_vars' => $this->_ci_prepare_view_vars($vars),
                '_ci_return' => $return,
            ]);
        }

        return $result;
    }
}

if (!class_exists('App_Loader', false)) {
    class_alias(App_Loader::class, 'App_Loader');
}
