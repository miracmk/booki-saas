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
 * Assets URL helper function.
 *
 * This function will create an asset file URL that includes a cache busting parameter in order
 * to invalidate the browser cache in case of an update.
 *
 * @param string $uri Relative URI (just like the one used in the base_url helper).
 * @param string|null $protocol Valid URI protocol.
 *
 * @return string Returns the final asset URL.
 */
function asset_url(string $uri = '', ?string $protocol = null): string
{
    $debug = config('debug');

    $cache_busting_token = '?' . config('cache_busting_token');

    if (str_contains(basename($uri), '.js') && !str_contains(basename($uri), '.min.js') && !$debug) {
        $min_uri = str_replace('.js', '.min.js', $uri);
        $min_file = FCPATH . ltrim($min_uri, '/');
        $orig_file = FCPATH . ltrim($uri, '/');
        if (file_exists($min_file) && (!file_exists($orig_file) || filemtime($min_file) >= filemtime($orig_file))) {
            $uri = $min_uri;
        }
    }

    if (str_contains(basename($uri), '.css') && !str_contains(basename($uri), '.min.css') && !$debug) {
        $min_uri = str_replace('.css', '.min.css', $uri);
        $min_file = FCPATH . ltrim($min_uri, '/');
        $orig_file = FCPATH . ltrim($uri, '/');
        if (file_exists($min_file) && (!file_exists($orig_file) || filemtime($min_file) >= filemtime($orig_file))) {
            $uri = $min_uri;
        }
    }

    return base_url($uri . $cache_busting_token, $protocol);
}
