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
 * BooKi encrypt.
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
class App_Encrypt extends CI_Encrypt
{
    //
}

if (!class_exists('EA_Encrypt', false)) {
    class_alias(App_Encrypt::class, 'EA_Encrypt');
}
