<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * Availability library.
 *
 * Handles availability related functionality.
 *
 * @package Libraries
 */
class Availability
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * Availability constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('admins_model');
        $this->CI->load->model('appointments_model');
        $this->CI->load->model('providers_model');
        $this->CI->load->model('secretaries_model');
        $this->CI->load->model('secretaries_model');
        $this->CI->load->model('settings_model');
        $this->CI->load->model('unavailabilities_model');
        $this->CI->load->model('blocked_periods_model');
        $this->CI->load->model('working_plan_exceptions_model');
        $this->CI->load->model('stations_model'); // Salon Flora customization

        $this->CI->load->library('ics_file');
    }

    /**
     * Ki Reservation (2026-08-26) - "İlk Müsaitlik" (first availability): scan forward from today
     * across every provider assigned to the service, and return the first N distinct date/hour slots
     * found, each already matched to the specific free provider + station (room) that would be used -
     * reuses get_available_hours() (which already excludes fully-booked stations) plus
     * Stations_model::find_free_station() to resolve WHICH station is free for that exact slot.
     *
     * @param array $service Service data.
     * @param array $providers Candidate providers (must already be filtered to ones assigned to the service).
     * @param int $limit How many slots to return.
     * @param int $max_days How many days forward to scan before giving up.
     * @param bool $one_per_provider Ki Reservation (2026-09-12) - when true, stop after the FIRST (earliest)
     *   hour found for each provider instead of collecting every open hour that provider has today. Without
     *   this, a single early provider with many open hours can fill the entire $limit budget by itself,
     *   starving every other provider of a slot - exactly the bug behind Calendar::get_next_availability()'s
     *   per-provider "İlk Müsaitlik" strip reporting "no availability" for almost everyone. Appointments.php's
     *   ::first_availability() (the "suggest 3 upcoming slots" wizard helper) intentionally wants the globally
     *   earliest N slots regardless of provider, so it keeps the old (false) behavior.
     *
     * @return array List of ['date' => 'Y-m-d', 'hour' => 'H:i', 'provider_id' => int, 'provider_name' =>
     *   string, 'station_id' => int|null, 'station_name' => string|null], earliest first.
     *
     * @throws Exception
     */
    public function find_first_available_slots(
        array $service,
        array $providers,
        int $limit = 3,
        int $max_days = 60,
        bool $one_per_provider = false,
    ): array {
        $slots = [];
        $duration = new DateInterval('PT' . (int) $service['duration'] . 'M');
        $providers_with_slot = [];

        for ($day_offset = 0; $day_offset < $max_days && count($slots) < $limit; $day_offset++) {
            $date = (new DateTime('today'))->modify("+{$day_offset} day")->format('Y-m-d');

            foreach ($providers as $provider) {
                if (count($slots) >= $limit) {
                    break;
                }

                if ($one_per_provider && isset($providers_with_slot[$provider['id']])) {
                    continue;
                }

                // ignore_advance_timeout=true: every current caller of find_first_available_slots()
                // (Calendar::get_next_availability(), Appointments::first_availability()) is a
                // staff-only tool gated behind PRIV_APPOINTMENTS, never the public booking widget - see
                // get_available_hours() docblock note above for why that distinction matters here.
                $available_hours = $this->get_available_hours($date, $service, $provider, null, true);

                foreach ($available_hours as $hour) {
                    if (count($slots) >= $limit) {
                        break;
                    }

                    $station_id = null;
                    $station_name = null;

                    $station_ids = $provider['stations'] ?? [];

                    if (!empty($station_ids)) {
                        $slot_start = new DateTime($date . ' ' . $hour);
                        $slot_end = (clone $slot_start)->add($duration);

                        $station_id = $this->CI->stations_model->find_free_station(
                            $station_ids,
                            $slot_start->format('Y-m-d H:i:s'),
                            $slot_end->format('Y-m-d H:i:s'),
                        );

                        if ($station_id !== null) {
                            $station_name = $this->CI->stations_model->find($station_id)['name'] ?? null;
                        }
                    }

                    $slots[] = [
                        'date' => $date,
                        'hour' => $hour,
                        'provider_id' => (int) $provider['id'],
                        'provider_name' => trim($provider['first_name'] . ' ' . $provider['last_name']),
                        'station_id' => $station_id,
                        'station_name' => $station_name,
                    ];

                    // Only stop scanning this provider once we've found an hour that's actually
                    // seatable (a free station, or the provider needs none) - an hour with no free
                    // station is unusable and the caller filters it back out, so stopping here would
                    // wrongly report "no availability" even though a later hour that same day works.
                    if ($one_per_provider && ($station_id !== null || empty($station_ids))) {
                        $providers_with_slot[$provider['id']] = true;
                        break;
                    }
                }
            }
        }

        return $slots;
    }

    /**
     * Get the available hours of a provider.
     *
     * @param string $date Selected date (Y-m-d).
     * @param array $service Service data.
     * @param array $provider Provider data.
     * @param int|null $exclude_appointment_id Exclude an appointment from the availability generation.
     *
     * @return array
     *
     * @throws Exception
     */
    public function get_available_hours(
        string $date,
        array $service,
        array $provider,
        ?int $exclude_appointment_id = null,
        bool $ignore_advance_timeout = false,
    ): array {
        if ($this->CI->blocked_periods_model->is_entire_date_blocked($date)) {
            return [];
        }

        if ($service['attendants_number'] > 1) {
            $available_hours = $this->consider_multiple_attendants($date, $service, $provider, $exclude_appointment_id);
        } else {
            $available_periods = $this->get_available_periods($date, $provider, $exclude_appointment_id);

            $available_hours = $this->generate_available_hours($date, $service, $available_periods);
        }

        // Ki Reservation (2026-09-12) - $ignore_advance_timeout=true skips the "book_advance_timeout"
        // buffer below. That setting exists to stop ONLINE customers self-booking a slot starting too
        // soon for staff to prepare (public Booking.php always passes false, unchanged) - it should NOT
        // also delay the internal "İlk Müsaitlik" staff view of who's free right now for a walk-in
        // (find_first_available_slots() passes true). Bug report: at 15:48 with book_advance_timeout=30,
        // a provider free from 16:00 was reported as first-available at 16:30 (16:00/16:15 both fell
        // inside the +30min threshold) even though reception could seat a walk-in there immediately.
        if (!$ignore_advance_timeout) {
            $available_hours = $this->consider_book_advance_timeout($date, $available_hours, $provider);
        }

        $available_hours = $this->consider_future_booking_limit($date, $available_hours, $provider);

        // Salon Flora customization: filter out hours where none of the provider's assigned stations are free.
        return $this->consider_station_availability($date, $service, $provider, $available_hours, $exclude_appointment_id);
    }

    /**
     * Salon Flora customization - remove hours where none of the provider's assigned stations are free.
     *
     * A provider may be assigned to more than one physical station (e.g. can work in either "Masaj Odası 1" or
     * "Masaj Odası 2"). The provider remains bookable during a given hour as long as AT LEAST ONE of their
     * assigned stations is not occupied by another provider's appointment during that time - the provider is only
     * blocked when EVERY one of their stations is simultaneously taken. Providers with no assigned stations are
     * unaffected (returned as-is).
     *
     * @param string $date Selected date (Y-m-d).
     * @param array $service Service data.
     * @param array $provider Provider data.
     * @param array $available_hours Candidate available hours (H:i strings).
     * @param int|null $exclude_appointment_id Exclude an appointment from the conflict check.
     *
     * @return array Returns the filtered available hours.
     *
     * @throws Exception
     */
    protected function consider_station_availability(
        string $date,
        array $service,
        array $provider,
        array $available_hours,
        ?int $exclude_appointment_id = null,
    ): array {
        $station_ids = $provider['stations'] ?? [];

        if (empty($station_ids)) {
            return $available_hours;
        }

        $duration = new DateInterval('PT' . (int) $service['duration'] . 'M');

        return array_values(
            array_filter($available_hours, function (string $hour) use ($date, $duration, $station_ids, $provider, $exclude_appointment_id) {
                $slot_start = new DateTime($date . ' ' . $hour);
                $slot_end = (clone $slot_start)->add($duration);

                $free_station_id = $this->CI->stations_model->find_free_station(
                    $station_ids,
                    $slot_start->format('Y-m-d H:i:s'),
                    $slot_end->format('Y-m-d H:i:s'),
                    $exclude_appointment_id,
                );

                return $free_station_id !== null;
            }),
        );
    }

    /**
     * Get multiple attendants hours.
     *
     * This method will add the additional appointment hours whenever a service accepts multiple attendants.
     *
     * @param string $date Selected date (Y-m-d).
     * @param array $service Service data.
     * @param array $provider Provider data.
     * @param int|null $exclude_appointment_id Exclude an appointment from the availability generation.
     *
     * @return array Returns the available hours array.
     *
     * @throws Exception
     */
    protected function consider_multiple_attendants(
        string $date,
        array $service,
        array $provider,
        ?int $exclude_appointment_id = null,
    ): array {
        $unavailability_events = $this->CI->unavailabilities_model->get([
            'is_unavailability' => true,
            'DATE(start_datetime) <=' => $date,
            'DATE(end_datetime) >=' => $date,
            'id_users_provider' => $provider['id'],
        ]);

        $working_plan = json_decode($provider['settings']['working_plan'], true);

        $working_plan_exceptions = $this->CI->working_plan_exceptions_model->get_by_provider($provider['id']);

        $working_day = strtolower(date('l', strtotime($date)));

        $date_working_plan = $working_plan[$working_day] ?? null;

        // Search if the $date is a custom availability period added outside the normal working plan.
        if (array_key_exists($date, $working_plan_exceptions)) {
            $date_working_plan = $working_plan_exceptions[$date];
        }

        if (!$date_working_plan) {
            return [];
        }

        $periods = [
            [
                'start' => new DateTime($date . ' ' . $date_working_plan['start']),
                'end' => new DateTime($date . ' ' . $date_working_plan['end']),
            ],
        ];

        $blocked_periods = $this->CI->blocked_periods_model->get_for_period($date, $date);

        $periods = $this->remove_breaks($date, $periods, $date_working_plan['breaks']);
        $periods = $this->remove_unavailability_events($periods, $unavailability_events);
        $periods = $this->remove_unavailability_events($periods, $blocked_periods);

        $hours = [];

        $interval_value = !empty($service['slot_interval']) ? $service['slot_interval'] : 15;
        $interval = new DateInterval('PT' . (int) $interval_value . 'M');
        $duration = new DateInterval('PT' . (int) $service['duration'] . 'M');

        foreach ($periods as $period) {
            $slot_start = clone $period['start'];
            $slot_end = clone $slot_start;
            $slot_end->add($duration);

            while ($slot_end <= $period['end']) {
                // Salon Flora customization: station availability is now checked once, uniformly, at the end of
                // get_available_hours() via consider_station_availability() - see there for why (a provider can be
                // assigned to more than one station, and is bookable as long as at least one of them is free).

                // Make sure there is no other service appointment for this time slot.
                $other_service_attendants_number = $this->CI->appointments_model->get_other_service_attendants_number(
                    $slot_start,
                    $slot_end,
                    $service['id'],
                    $provider['id'],
                    $exclude_appointment_id,
                );

                if ($other_service_attendants_number > 0) {
                    $slot_start->add($interval);
                    $slot_end->add($interval);
                    continue;
                }

                // Check reserved attendants for this time slot and see if current attendants fit.
                $appointment_attendants_number = $this->CI->appointments_model->get_attendants_number_for_period(
                    $slot_start,
                    $slot_end,
                    $service['id'],
                    $provider['id'],
                    $exclude_appointment_id,
                );

                if ($appointment_attendants_number < $service['attendants_number']) {
                    $hours[] = $slot_start->format('H:i');
                }

                $slot_start->add($interval);
                $slot_end->add($interval);
            }
        }

        return $hours;
    }

    /**
     * Remove breaks from available time periods.
     *
     * @param string $date Selected date (Y-m-d).
     * @param array $periods Empty periods.
     * @param array $breaks Array of breaks.
     *
     * @return array Returns the available time periods without the breaks.
     *
     * @throws Exception
     */
    public function remove_breaks(string $date, array $periods, array $breaks): array
    {
        if (!$breaks) {
            return $periods;
        }

        foreach ($breaks as $break) {
            $break_start = new DateTime($date . ' ' . $break['start']);

            $break_end = new DateTime($date . ' ' . $break['end']);

            foreach ($periods as &$period) {
                $period_start = $period['start'];

                $period_end = $period['end'];

                if ($break_start <= $period_start && $break_end >= $period_start && $break_end <= $period_end) {
                    // left
                    $period['start'] = $break_end;
                    continue;
                }

                if (
                    $break_start >= $period_start &&
                    $break_start <= $period_end &&
                    $break_end >= $period_start &&
                    $break_end <= $period_end
                ) {
                    // middle
                    $period['end'] = $break_start;
                    $periods[] = [
                        'start' => $break_end,
                        'end' => $period_end,
                    ];
                    continue;
                }

                if ($break_start >= $period_start && $break_start <= $period_end && $break_end >= $period_end) {
                    // right
                    $period['end'] = $break_start;
                    continue;
                }

                if ($break_start <= $period_start && $break_end >= $period_end) {
                    // break contains period
                    $period['start'] = $break_end;
                }
            }
        }

        return $periods;
    }

    /**
     * Remove the unavailability entries from the available time periods of the selected date.
     *
     * @param array $periods Available time periods.
     * @param array $unavailability_events Unavailability events of the current date.
     *
     * @return array Returns the available time periods without the unavailability events.
     *
     * @throws Exception
     */
    public function remove_unavailability_events(array $periods, array $unavailability_events): array
    {
        foreach ($unavailability_events as $unavailability_event) {
            $unavailability_start = new DateTime($unavailability_event['start_datetime']);

            $unavailability_end = new DateTime($unavailability_event['end_datetime']);

            foreach ($periods as &$period) {
                $period_start = $period['start'];

                $period_end = $period['end'];

                if (
                    $unavailability_start <= $period_start &&
                    $unavailability_end >= $period_start &&
                    $unavailability_end <= $period_end
                ) {
                    // Left
                    $period['start'] = $unavailability_end;
                    continue;
                }

                if (
                    $unavailability_start >= $period_start &&
                    $unavailability_start <= $period_end &&
                    $unavailability_end >= $period_start &&
                    $unavailability_end <= $period_end
                ) {
                    // Middle
                    $period['end'] = $unavailability_start;
                    $periods[] = [
                        'start' => $unavailability_end,
                        'end' => $period_end,
                    ];
                    continue;
                }

                if (
                    $unavailability_start >= $period_start &&
                    $unavailability_start <= $period_end &&
                    $unavailability_end >= $period_end
                ) {
                    // Right
                    $period['end'] = $unavailability_start;
                    continue;
                }

                if ($unavailability_start <= $period_start && $unavailability_end >= $period_end) {
                    // Unavailability contains period
                    $period['start'] = $unavailability_end;
                }
            }
        }

        return $periods;
    }

    /**
     * Get an array containing the free time periods (start - end) of a selected date.
     *
     * This method is very important because there are many cases where the system needs to know when a provider is
     * available for an appointment. It will return an array that belongs to the selected date and contains values that
     * have the start and the end time of an available time period.
     *
     * @param string $date Selected date (Y-m-d).
     * @param array $provider Provider data.
     * @param int|null $exclude_appointment_id Exclude an appointment from the availability generation.
     *
     * @return array Returns an array with the available time periods of the provider.
     *
     * @throws Exception
     */
    public function get_available_periods(string $date, array $provider, ?int $exclude_appointment_id = null): array
    {
        // Get the service, provider's working plan and provider appointments.
        $working_plan = json_decode($provider['settings']['working_plan'], true);

        // Get the provider's working plan exceptions from the new table.
        $working_plan_exceptions = $this->CI->working_plan_exceptions_model->get_by_provider($provider['id']);

        // Validate and sanitize inputs before building query
        $provider_id = (int) $provider['id'];

        // Validate date format to prevent SQL injection
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new InvalidArgumentException('Invalid date format provided.');
        }

        // Build query using array-based where clause for safety
        $where = [
            'id_users_provider' => $provider_id,
            'DATE(start_datetime) <=' => $date,
            'DATE(end_datetime) >=' => $date,
        ];

        // Sometimes it might be necessary to exclude an appointment from the calculation (e.g. when editing an
        // existing appointment).
        if ($exclude_appointment_id) {
            $where['id !='] = (int) $exclude_appointment_id;
        }

        // Salon Flora customization: station availability is now checked once, uniformly, at the end of
        // get_available_hours() via consider_station_availability() - see there for why (a provider can be
        // assigned to more than one station, and is bookable as long as at least one of them is free).

        $appointments = array_values(
            array_merge(
                $this->CI->appointments_model->get($where),
                $this->CI->unavailabilities_model->get($where),
                $this->CI->blocked_periods_model->get_for_period($date, $date),
            ),
        );

        // Find the empty spaces on the plan. The first split between the plan is due to a break (if any). After that
        // every reserved appointment is considered to be a taken space in the plan.
        $working_day = strtolower(date('l', strtotime($date)));

        $date_working_plan = $working_plan[$working_day] ?? null;

        // Search if the $date is a custom availability period added outside the normal working plan.
        if (array_key_exists($date, $working_plan_exceptions)) {
            $date_working_plan = $working_plan_exceptions[$date];
        }

        if (!$date_working_plan) {
            return [];
        }

        $periods = [];

        if (isset($date_working_plan['breaks'])) {
            $periods[] = [
                'start' => $date_working_plan['start'],
                'end' => $date_working_plan['end'],
            ];

            $day_start = new DateTime($date_working_plan['start']);
            $day_end = new DateTime($date_working_plan['end']);

            // Split the working plan to available time periods that do not contain the breaks in them.
            foreach ($date_working_plan['breaks'] as $break) {
                $break_start = new DateTime($break['start']);
                $break_end = new DateTime($break['end']);

                if ($break_start < $day_start) {
                    $break_start = $day_start;
                }

                if ($break_end > $day_end) {
                    $break_end = $day_end;
                }

                if ($break_start >= $break_end) {
                    continue;
                }

                foreach ($periods as $key => $period) {
                    $period_start = new DateTime($period['start']);
                    $period_end = new DateTime($period['end']);

                    $remove_current_period = false;

                    if ($break_start > $period_start && $break_start < $period_end && $break_end > $period_start) {
                        $periods[] = [
                            'start' => $period_start->format('H:i'),
                            'end' => $break_start->format('H:i'),
                        ];

                        $remove_current_period = true;
                    }

                    if ($break_start < $period_end && $break_end > $period_start && $break_end < $period_end) {
                        $periods[] = [
                            'start' => $break_end->format('H:i'),
                            'end' => $period_end->format('H:i'),
                        ];

                        $remove_current_period = true;
                    }

                    if ($break_start == $period_start && $break_end == $period_end) {
                        $remove_current_period = true;
                    }

                    if ($remove_current_period) {
                        unset($periods[$key]);
                    }
                }
            }
        }

        // Break the empty periods with the reserved appointments.
        foreach ($appointments as $appointment) {
            foreach ($periods as $index => &$period) {
                $appointment_start = new DateTime($appointment['start_datetime']);
                $appointment_end = new DateTime($appointment['end_datetime']);

                if ($appointment_start >= $appointment_end) {
                    continue;
                }

                $period_start = new DateTime($date . ' ' . $period['start']);
                $period_end = new DateTime($date . ' ' . $period['end']);

                if (
                    $appointment_start <= $period_start &&
                    $appointment_end <= $period_end &&
                    $appointment_end <= $period_start
                ) {
                    // The appointment does not belong in this time period, so we  will not change anything.
                    continue;
                } else {
                    if (
                        $appointment_start <= $period_start &&
                        $appointment_end <= $period_end &&
                        $appointment_end >= $period_start
                    ) {
                        // The appointment starts before the period and finishes somewhere inside. We will need to break
                        // this period and leave the available part.
                        $period['start'] = $appointment_end->format('H:i');
                    } else {
                        if ($appointment_start >= $period_start && $appointment_end < $period_end) {
                            // The appointment is inside the time period, so we will split the period into two new
                            // others.
                            unset($periods[$index]);

                            $periods[] = [
                                'start' => $period_start->format('H:i'),
                                'end' => $appointment_start->format('H:i'),
                            ];

                            $periods[] = [
                                'start' => $appointment_end->format('H:i'),
                                'end' => $period_end->format('H:i'),
                            ];
                        } elseif ($appointment_start == $period_start && $appointment_end == $period_end) {
                            unset($periods[$index]); // The whole period is blocked so remove it from the available periods array.
                        } else {
                            if (
                                $appointment_start >= $period_start &&
                                $appointment_end >= $period_start &&
                                $appointment_start <= $period_end
                            ) {
                                // The appointment starts in the period and finishes out of it. We will need to remove
                                // the time that is taken from the appointment.
                                $period['end'] = $appointment_start->format('H:i');
                            } else {
                                if (
                                    $appointment_start >= $period_start &&
                                    $appointment_end >= $period_end &&
                                    $appointment_start >= $period_end
                                ) {
                                    // The appointment does not belong in the period so do not change anything.
                                    continue;
                                } else {
                                    if (
                                        $appointment_start <= $period_start &&
                                        $appointment_end >= $period_end &&
                                        $appointment_start <= $period_end
                                    ) {
                                        // The appointment is bigger than the period, so this period needs to be removed.
                                        unset($periods[$index]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return array_values($periods);
    }

    /**
     * Calculate the available appointment hours.
     *
     * Calculate the available appointment hours for the given date. The empty spaces are broken down to 15 min and if
     * the service fit in each quarter then a new available hour is added to the "$available_hours" array.
     *
     * @param string $date Selected date (Y-m-d).
     * @param array $service Service data.
     * @param array $empty_periods Empty periods array.
     *
     * @return array Returns an array with the available hours for the appointment.
     *
     * @throws Exception
     */
    protected function generate_available_hours(string $date, array $service, array $empty_periods): array
    {
        $available_hours = [];

        foreach ($empty_periods as $period) {
            $start_hour = new DateTime($date . ' ' . $period['start']);

            $end_hour = new DateTime($date . ' ' . $period['end']);

            $interval = !empty($service['slot_interval']) ? (int) $service['slot_interval'] : 15;

            $current_hour = $start_hour;

            $diff = $current_hour->diff($end_hour);

            while ($diff->h * 60 + $diff->i >= (int) $service['duration'] && $diff->invert === 0) {
                $available_hours[] = $current_hour->format('H:i');

                $current_hour->add(new DateInterval('PT' . $interval . 'M'));

                $diff = $current_hour->diff($end_hour);
            }
        }

        return $available_hours;
    }

    /**
     * Consider the book advance timeout and remove available hours that have passed the threshold.
     *
     * If the selected date is today, remove past hours. It is important  include the timeout before booking
     * that is set in the back-office the system. Normally we might want the customer to book an appointment
     * that is at least half or one hour from now. The setting is stored in minutes.
     *
     * @param string $date The selected date.
     * @param array $available_hours Already generated available hours.
     * @param array $provider Provider information.
     *
     * @return array Returns the updated available hours.
     *
     * @throws Exception
     */
    protected function consider_book_advance_timeout(string $date, array $available_hours, array $provider): array
    {
        $provider_timezone = new DateTimeZone($provider['timezone']);

        $book_advance_timeout = setting('book_advance_timeout', 0);
        $book_advance_timeout = is_numeric($book_advance_timeout) ? max(0, (int) $book_advance_timeout) : 0;

        $threshold = new DateTime('now', $provider_timezone);

        $threshold->modify('+' . $book_advance_timeout . ' minutes');

        foreach ($available_hours as $index => $value) {
            $available_hour = new DateTime($date . ' ' . $value, $provider_timezone);

            if ($available_hour->getTimestamp() <= $threshold->getTimestamp()) {
                unset($available_hours[$index]);
            }
        }

        $available_hours = array_values($available_hours);

        sort($available_hours, SORT_STRING);

        return array_values($available_hours);
    }

    /**
     * Remove times if succeed the future booking limit.
     *
     * @param string $selected_date
     * @param array $available_hours
     * @param array $provider
     *
     * @return array
     *
     * @throws Exception
     */
    protected function consider_future_booking_limit(
        string $selected_date,
        array $available_hours,
        array $provider,
    ): array {
        $provider_timezone = new DateTimeZone($provider['timezone']);

        $future_booking_limit = setting('future_booking_limit', 90); // in days
        $future_booking_limit = is_numeric($future_booking_limit) ? max(0, (int) $future_booking_limit) : 90;

        $threshold = new DateTime('now', $provider_timezone);

        $threshold->modify('+' . $future_booking_limit . ' days');

        $selected_date_time = new DateTime($selected_date);

        if ($threshold < $selected_date_time) {
            return [];
        }

        return $threshold > $selected_date_time ? $available_hours : [];
    }
}
