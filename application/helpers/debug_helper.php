<?php use JetBrains\PhpStorm\NoReturn;

defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

if (!function_exists('dd')) {
    /**
     * Output the provided variables with "var_dump" and stop the execution.
     *
     * Example:
     *
     * dd($appointment, $service, $provider, $customer);
     *
     * @param mixed ...$vars
     */
    #[NoReturn]
    function dd(...$vars): void
    {
        echo is_cli() ? PHP_EOL : '<pre>';
        var_dump($vars);
        echo is_cli() ? PHP_EOL : '</pre>';

        exit(1);
    }
}
