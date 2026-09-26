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
 * Jitsi Client Library
 *
 * Generates random Jitsi meeting links for appointments.
 *
 * @package Libraries
 */
class Jitsi_client
{
    /**
     * Generate a random Jitsi meeting link.
     *
     * @return string The generated Jitsi meeting URL.
     *
     * @throws \Random\RandomException
     */
    public function generate_link(): string
    {
        // Generate a random room name (alphanumeric, 16 characters)
        $room = bin2hex(random_bytes(8));

        // You can customize the domain if you use a self-hosted Jitsi server
        $domain = 'https://meet.jit.si/';

        return $domain . $room;
    }
}
