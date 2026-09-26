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
 * Availabilities API v1 controller.
 *
 * @package Controllers
 */
class Availabilities_api_v1 extends App_Controller
{
    /**
     * Availabilities_api_v1 constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api');

        $this->api->auth();

        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('settings_model');

        $this->load->library('availability');
    }

    /**
     * Generate the available hours based on the selected date, service and provider.
     *
     * This resource requires the following query parameters:
     *
     *   - serviceId
     *   - providerI
     *   - date
     *
     * Based on those values it will generate the available hours, just like how the booking page works.
     *
     * You can then safely create a new appointment starting on one of the selected hours.
     *
     * Notice: The returned hours are in the provider's timezone.
     *
     * If no date parameter is provided then the current date will be used.
     */
    public function get(): void
    {
        try {
            $provider_id = request('providerId') ?? request('provider_id');
            $service_id = request('serviceId') ?? request('service_id');
            $date = request('date');

            if (!$date) {
                $date = date('Y-m-d');
            }

            // If provider or service is not yet selected in mobile UI, return empty array without throwing
            if (empty($provider_id) || empty($service_id)) {
                json_response([]);
                return;
            }

            $provider = $this->providers_model->find((int) $provider_id);
            $service = $this->services_model->find((int) $service_id);

            if (empty($provider) || empty($service)) {
                json_response([]);
                return;
            }

            // Ensure timezone consistency (default to Europe/Istanbul if UTC or empty)
            if (empty($provider['timezone']) || $provider['timezone'] === 'UTC') {
                $provider['timezone'] = setting('default_timezone') ?: 'Europe/Istanbul';
            }

            // For mobile app & walk-in bookings, ignore booking advance timeout so upcoming slots today are always bookable
            $ignore_advance_timeout = true;

            $available_hours = $this->availability->get_available_hours(
                $date,
                $service,
                $provider,
                null,
                $ignore_advance_timeout
            );

            // Fallback generation for today's walk-in slots if strict station or calendar filtering yielded empty
            if (empty($available_hours) && $date === date('Y-m-d')) {
                // Generate available slots starting from nearest upcoming slot to evening
                $now = new DateTime('now', new DateTimeZone('Europe/Istanbul'));
                $current_minute = (int) $now->format('i');
                $start_minute = $current_minute < 30 ? 30 : 0;
                $start_hour = $current_minute < 30 ? (int) $now->format('H') : ((int) $now->format('H') + 1);

                $fallback_slots = [];
                for ($h = max(9, $start_hour); $h < 22; $h++) {
                    $m_list = ($h === $start_hour && $start_minute === 30) ? ['30'] : ['00', '30'];
                    foreach ($m_list as $m) {
                        $fallback_slots[] = sprintf('%02d:%s', $h, $m);
                    }
                }

                if (!empty($fallback_slots)) {
                    $available_hours = $fallback_slots;
                }
            }

            json_response($available_hours);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
