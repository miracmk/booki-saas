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

if (!function_exists('lang')) {
    /**
     * Lang
     *
     * Fetches a language variable and optionally outputs a form label
     *
     * @param string $line The language line.
     * @param string $for The "for" value (id of the form element).
     * @param array $attributes Any additional HTML attributes.
     *
     * @return string
     */
    function lang(string $line, string $for = '', array $attributes = []): string
    {
        /** @var EA_Controller $CI */
        $CI = get_instance();

        $result = $CI->lang->line($line);

        if ($for !== '') {
            $result = '<label for="' . $for . '"' . _stringify_attributes($attributes) . '>' . $result . '</label>';
        }

        return $result ?: $line;
    }
}
