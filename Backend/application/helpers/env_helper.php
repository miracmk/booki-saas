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

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * Example:
     *
     * $debug = env('debug', FALSE);
     *
     * @param string $key Environment key.
     * @param mixed|null $default Default value in case the requested variable has no value.
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    function env(string $key, mixed $default = null): mixed
    {
        if (empty($key)) {
            throw new InvalidArgumentException('The $key argument cannot be empty.');
        }

        return $_ENV[$key] ?? $default;
    }
}
