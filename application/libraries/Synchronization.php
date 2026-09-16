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
 * Synchronization library.
 *
 * Handles external calendar synchronization functionality.
 *
 * @package Libraries
 */
class Synchronization
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * Synchronization constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('providers_model');
        $this->CI->load->model('appointments_model');

        $this->CI->load->library('google_sync');
        $this->CI->load->library('caldav_sync');
    }

    /**
     * Synchronize changes made to the appointment with external calendars.
     *
     * @param array $appointment Appointment record.
     * @param array $service Service record.
     * @param array $provider Provider record.
     * @param array $customer Customer record.
     * @param array $settings Required settings for the notification content.
     */
    public function sync_appointment_saved(
        array $appointment,
        array $service,
        array $provider,
        array $customer,
        array $settings,
    ): void {
        try {
            // Google

            if ($provider['settings']['google_sync']) {
                if (empty($provider['settings']['google_token'])) {
                    throw new RuntimeException('No google token available for the provider: ' . $provider['id']);
                }

                $google_token = json_decode($provider['settings']['google_token'], true);

                $this->CI->google_sync->refresh_token($google_token['refresh_token']);

                if (empty($appointment['id_google_calendar'])) {
                    $google_event = $this->CI->google_sync->add_appointment(
                        $appointment,
                        $provider,
                        $service,
                        $customer,
                        $settings,
                    );

                    $appointment['id_google_calendar'] = $google_event->getId();

                    $this->CI->appointments_model->save($appointment);
                } else {
                    $this->CI->google_sync->update_appointment($appointment, $provider, $service, $customer, $settings);
                }
            }

            // CalDAV

            if ($provider['settings']['caldav_sync']) {
                $appointment['id_caldav_calendar'] = $this->CI->caldav_sync->save_appointment(
                    $appointment,
                    $service,
                    $provider,
                    $customer,
                );

                $this->CI->appointments_model->save($appointment);
            }
        } catch (Throwable $e) {
            log_message(
                'error',
                'Synchronization - Could not sync confirmation details of appointment (' .
                    ($appointment['id'] ?? '-') .
                    ') : ' .
                    $e->getMessage(),
            );

            log_message('error', $e->getTraceAsString());
        }
    }

    /**
     * Synchronize removal of an appointment with external calendars.
     *
     * @param array $appointment Appointment record.
     * @param array $provider Provider record.
     */
    public function sync_appointment_deleted(array $appointment, array $provider): void
    {
        try {
            // Google

            if ($provider['settings']['google_sync'] && !empty($appointment['id_google_calendar'])) {
                if (empty($provider['settings']['google_token'])) {
                    throw new RuntimeException('No google token available for the provider: ' . $provider['id']);
                }

                $google_token = json_decode($provider['settings']['google_token'], true);

                $this->CI->google_sync->refresh_token($google_token['refresh_token']);

                $this->CI->google_sync->delete_appointment($provider, $appointment['id_google_calendar']);
            }

            // CalDAV

            if ($provider['settings']['caldav_sync'] && !empty($appointment['id_caldav_calendar'])) {
                $this->CI->caldav_sync->delete_event($provider, $appointment['id_caldav_calendar']);
            }
        } catch (Throwable $e) {
            log_message(
                'error',
                'Synchronization - Could not sync cancellation details of appointment (' .
                    ($appointment['id'] ?? '-') .
                    ') : ' .
                    $e->getMessage(),
            );
            log_message('error', $e->getTraceAsString());
        }
    }

    /**
     * Synchronize changes made to the unavailability with external calendars.
     *
     * @param array $unavailability Unavailability record.
     * @param array $provider Provider record.
     */
    public function sync_unavailability_saved(array $unavailability, array $provider): void
    {
        try {
            // Google

            if ($provider['settings']['google_sync']) {
                if (empty($provider['settings']['google_token'])) {
                    throw new RuntimeException('No google token available for the provider: ' . $provider['id']);
                }

                $google_token = json_decode($provider['settings']['google_token'], true);

                $this->CI->google_sync->refresh_token($google_token['refresh_token']);

                if (empty($unavailability['id_google_calendar'])) {
                    $google_event = $this->CI->google_sync->add_unavailability($provider, $unavailability);

                    $unavailability['id_google_calendar'] = $google_event->getId();

                    $this->CI->unavailabilities_model->save($unavailability);
                } else {
                    $this->CI->google_sync->update_unavailability($provider, $unavailability);
                }
            }

            // CalDAV

            if ($provider['settings']['caldav_sync']) {
                $unavailability['id_caldav_calendar'] = $this->CI->caldav_sync->save_unavailability(
                    $unavailability,
                    $provider,
                );

                $this->CI->unavailabilities_model->save($unavailability);
            }
        } catch (Throwable $e) {
            log_message(
                'error',
                'Synchronization - Could not sync cancellation details of unavailability (' .
                    ($appointment['id'] ?? '-') .
                    ') : ' .
                    $e->getMessage(),
            );
            log_message('error', $e->getTraceAsString());
        }
    }

    /**
     * Synchronize removal of an unavailability with external calendars.
     *
     * @param array $unavailability Unavailability record.
     * @param array $provider Provider record.
     */
    public function sync_unavailability_deleted(array $unavailability, array $provider): void
    {
        try {
            // Google

            if ($provider['settings']['google_sync'] && !empty($unavailability['id_google_calendar'])) {
                if (empty($provider['settings']['google_token'])) {
                    throw new RuntimeException('No google token available for the provider: ' . $provider['id']);
                }

                $google_token = json_decode($provider['settings']['google_token'], true);

                $this->CI->google_sync->refresh_token($google_token['refresh_token']);

                $this->CI->google_sync->delete_unavailability($provider, $unavailability['id_google_calendar']);
            }

            // CalDAV

            if ($provider['settings']['caldav_sync'] && !empty($unavailability['id_caldav_calendar'])) {
                $this->CI->caldav_sync->delete_event($provider, $unavailability['id_caldav_calendar']);
            }
        } catch (Throwable $e) {
            log_message(
                'error',
                'Synchronization - Could not sync cancellation details of unavailability (' .
                    ($appointment['id'] ?? '-') .
                    ') : ' .
                    $e->getMessage(),
            );
            log_message('error', $e->getTraceAsString());
        }
    }

    /**
     * Make sure a synced appointment is removed from Google/CalDAV Calendar, if its provider is changed.
     *
     * @throws Exception
     */
    /**
     * @param int $appointment_id
     * @param int $new_provider_id The provider this appointment is being saved with NOW (from the
     *   incoming save request) - the DB row (fetched below) still holds the OLD provider until the
     *   caller's save() runs afterward, so this can't be read from the DB; it must be passed in.
     *
     * Salon Flora bugfix (2026-08-25) - this used to compare $existing_appointment['id_users_provider']
     * against itself (both sides were the same DB read), so the condition was always false and a
     * provider reassignment NEVER removed the old Google/CalDAV event - it silently stayed on the
     * PREVIOUS therapist's calendar forever, and because id_google_calendar/id_caldav_calendar stayed
     * set, sync_appointment_saved() then tried to UPDATE that stale (wrong-owner) event instead of
     * creating a fresh one on the new provider's calendar. Now: compare against the real new provider id,
     * and clear the sync ids after removal so the subsequent save creates a proper new event.
     */
    public function remove_appointment_on_provider_change($appointment_id, int $new_provider_id): void
    {
        $existing_appointment = $this->CI->appointments_model->find($appointment_id);

        $existing_google_id = $existing_appointment['id_google_calendar'];
        $existing_caldav_id = $existing_appointment['id_caldav_calendar'];

        $existing_provider_id = (int) $existing_appointment['id_users_provider'];

        if (($existing_google_id || $existing_caldav_id) && $existing_provider_id !== $new_provider_id) {
            $existing_provider = $this->CI->providers_model->find($existing_provider_id);

            if ($existing_provider['settings']['google_sync'] || $existing_provider['settings']['caldav_sync']) {
                $this->sync_appointment_deleted($existing_appointment, $existing_provider);
            }

            $this->CI->db->update(
                'appointments',
                ['id_google_calendar' => null, 'id_caldav_calendar' => null],
                ['id' => $appointment_id],
            );
        }
    }
}
