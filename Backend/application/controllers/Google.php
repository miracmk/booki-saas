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
 * Google controller.
 *
 * Handles the Google Calendar synchronization related operations.
 *
 * @package Controllers
 */
class Google extends App_Controller
{
    /**
     * Google constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('google_sync');

        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('roles_model');
    }

    /**
     * Complete synchronization of appointments between Google Calendar and BooKi.
     *
     * This method will completely sync the appointments of a provider with his Google Calendar account. The sync period
     * needs to be relatively small, because a lot of API calls might be necessary and this will lead to consuming the
     * Google limit for the Calendar API usage.
     */
    public static function sync(?string $provider_id = null): void
    {
        try {
            /** @var App_Controller $CI */
            $CI = get_instance();

            $CI->load->library('google_sync');

            // Load the libraries as this method is called statically from the CLI command

            $CI->load->model('appointments_model');
            $CI->load->model('unavailabilities_model');
            $CI->load->model('providers_model');
            $CI->load->model('services_model');
            $CI->load->model('customers_model');
            $CI->load->model('settings_model');

            $user_id = session('user_id');

            if (!$user_id && !is_cli()) {
                return;
            }

            if (!$provider_id) {
                throw new InvalidArgumentException('No provider ID provided.');
            }

            $provider = $CI->providers_model->find($provider_id);

            // Check whether the selected provider has the Google Sync enabled.
            $google_sync = $CI->providers_model->get_setting($provider['id'], 'google_sync');

            if (!$google_sync) {
                return; // The selected provider does not have the Google Sync enabled.
            }

            $google_token = json_decode($provider['settings']['google_token'], true);

            $CI->google_sync->refresh_token($google_token['refresh_token']);

            // Fetch provider's appointments that belong to the sync time period.
            $sync_past_days = $provider['settings']['sync_past_days'];

            $sync_future_days = $provider['settings']['sync_future_days'];

            $start = strtotime('-' . $sync_past_days . ' days', strtotime(date('Y-m-d')));

            $end = strtotime('+' . $sync_future_days . ' days', strtotime(date('Y-m-d')));

            $where = [
                'start_datetime >=' => date('Y-m-d H:i:s', $start),
                'end_datetime <=' => date('Y-m-d H:i:s', $end),
                'id_users_provider' => $provider['id'],
            ];

            $appointments = $CI->appointments_model->get($where);

            $unavailabilities = $CI->unavailabilities_model->get($where);

            $local_events = [...$appointments, ...$unavailabilities];

            $company_color = setting('company_color');

            $settings = [
                'company_name' => setting('company_name'),
                'company_link' => setting('company_link'),
                'company_email' => setting('company_email'),
                'company_color' =>
                    !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
            ];

            $provider_timezone = new DateTimeZone($provider['timezone']);

            // Pre-fetch Google events in the sync window so we can detect duplicates and
            // re-link local records to existing Google events when the user disables and
            // re-enables synchronization on the same calendar. Without this, every local
            // event would be pushed again and produce visible duplicates.
            try {
                $existing_google_events = $CI->google_sync->get_sync_events(
                    $provider['settings']['google_calendar'],
                    $start,
                    $end,
                );
            } catch (Throwable $prefetch_error) {
                log_message(
                    'error',
                    'Google::sync - failed to prefetch existing calendar events for provider ID '
                    . $provider['id'] . ': ' . $prefetch_error->getMessage()
                    . '. Duplicate detection disabled for this run.',
                );

                $existing_google_events = null;
            }

            $extract_google_event_range = function ($google_event) use ($provider_timezone): ?array {
                if ($google_event->getStart() === null || $google_event->getEnd() === null) {
                    return null;
                }

                $is_all_day = $google_event->getStart()->getDateTime() === null;

                if ($is_all_day) {
                    $g_start = new DateTime($google_event->getStart()->getDate() . ' 00:00:00', $provider_timezone);
                    $g_end = new DateTime($google_event->getEnd()->getDate() . ' 00:00:00', $provider_timezone);
                    $g_end->modify('-1 minute');
                } else {
                    $g_start = new DateTime($google_event->getStart()->getDateTime());
                    $g_start->setTimezone($provider_timezone);
                    $g_end = new DateTime($google_event->getEnd()->getDateTime());
                    $g_end->setTimezone($provider_timezone);
                }

                return [$g_start->getTimestamp(), $g_end->getTimestamp()];
            };

            // Sync each appointment with Google Calendar by following the project's sync protocol (see documentation).
            foreach ($local_events as $local_event) {
                if (!$local_event['is_unavailability']) {
                    $service = $CI->services_model->find($local_event['id_services']);
                    $customer = $CI->customers_model->find($local_event['id_users_customer']);
                    $events_model = $CI->appointments_model;
                } else {
                    $service = null;
                    $customer = null;
                    $events_model = $CI->unavailabilities_model;
                }

                // If current appointment not synced yet, add to Google Calendar.
                if (!$local_event['id_google_calendar']) {
                    // Before creating a new Google event, try to match an existing one in the
                    // calendar by start/end (and, for unavailabilities, the synthetic
                    // "Unavailable" summary). When the user disables and re-enables sync on
                    // the same calendar, the local id_google_calendar is wiped but the events
                    // still exist remotely, and re-pushing them would create duplicates.
                    $matched_google_event = null;

                    if ($existing_google_events !== null) {
                        $local_start_ts = (new DateTime($local_event['start_datetime'], $provider_timezone))
                            ->getTimestamp();
                        $local_end_ts = (new DateTime($local_event['end_datetime'], $provider_timezone))
                            ->getTimestamp();

                        foreach ($existing_google_events->getItems() as $candidate) {
                            if ($candidate->getStatus() === 'cancelled') {
                                continue;
                            }

                            $candidate_range = $extract_google_event_range($candidate);

                            if ($candidate_range === null) {
                                continue;
                            }

                            if (
                                $candidate_range[0] !== $local_start_ts ||
                                $candidate_range[1] !== $local_end_ts
                            ) {
                                continue;
                            }

                            // For unavailabilities require the synthetic "Unavailable" summary
                            // to avoid hijacking unrelated Google events that just happen to
                            // overlap the same time window.
                            if ($local_event['is_unavailability']) {
                                $candidate_summary = trim((string) $candidate->getSummary());

                                if (strcasecmp($candidate_summary, 'Unavailable') !== 0) {
                                    continue;
                                }
                            }

                            $matched_google_event = $candidate;
                            break;
                        }
                    }

                    if ($matched_google_event !== null) {
                        $local_event = $events_model->find($local_event['id']);
                        $local_event['id_google_calendar'] = $matched_google_event->getId();
                        $events_model->save($local_event);
                        continue;
                    }

                    if (!$local_event['is_unavailability']) {
                        $google_event = $CI->google_sync->add_appointment(
                            $local_event,
                            $provider,
                            $service,
                            $customer,
                            $settings,
                        );
                    } else {
                        $google_event = $CI->google_sync->add_unavailability($provider, $local_event);
                    }

                    $local_event = $events_model->find($local_event['id']);

                    $local_event['id_google_calendar'] = $google_event->getId();

                    $events_model->save($local_event); // Save the Google Calendar ID.

                    continue;
                }

                // Appointment is synced with Google Calendar.

                try {
                    $google_event = $CI->google_sync->get_event($provider, $local_event['id_google_calendar']);

                    if ($google_event->getStatus() == 'cancelled') {
                        throw new Exception('Event is cancelled, remove the record from BooKi.');
                    }

                    // If Google Calendar event is different from BooKi appointment then update BooKi record.
                    // Both sides must be evaluated in the provider's timezone to get consistent timestamps.
                    // Local datetimes are stored as timezone-naive strings in the provider's timezone, so
                    // wrap them with the provider timezone before calling getTimestamp().
                    $local_event_start = (new DateTime($local_event['start_datetime'], $provider_timezone))->getTimestamp();
                    $local_event_end = (new DateTime($local_event['end_datetime'], $provider_timezone))->getTimestamp();

                    $is_google_all_day = $google_event->getStart()->getDateTime() === null;

                    if ($is_google_all_day) {
                        // All-day events carry only a date string (no time, no timezone offset).
                        // Interpret them as midnight in the provider's timezone so the stored
                        // datetimes stay consistent and is_all_day_event() keeps returning true.
                        $google_event_start = new DateTime(
                            $google_event->getStart()->getDate() . ' 00:00:00',
                            $provider_timezone,
                        );
                        $google_event_end = new DateTime(
                            $google_event->getEnd()->getDate() . ' 00:00:00',
                            $provider_timezone,
                        );
                        $google_event_end->modify('-1 minute'); // Exclusive end → 23:59:00 of the last actual day
                    } else {
                        // Timed events carry RFC3339 strings with an embedded timezone offset.
                        // Create without a timezone so the offset in the string is honoured, then
                        // convert to the provider's timezone for local storage.
                        $google_event_start = new DateTime($google_event->getStart()->getDateTime());
                        $google_event_start->setTimezone($provider_timezone);
                        $google_event_end = new DateTime($google_event->getEnd()->getDateTime());
                        $google_event_end->setTimezone($provider_timezone);
                    }

                    if ($local_event['is_unavailability']) {
                        $google_event_summary = $google_event->getSummary();
                        // Skip the synthetic "Unavailable" summary that EA itself sets when
                        // pushing unavailabilities to Google so it doesn't get duplicated
                        // back into the local notes/description.
                        $google_event_notes = strcasecmp(trim((string) $google_event_summary), 'Unavailable') === 0
                            ? (string) $google_event->getDescription()
                            : trim($google_event_summary . ' ' . $google_event->getDescription());
                    } else {
                        $google_event_notes = $google_event->getDescription();
                    }

                    $is_different =
                        $local_event_start !== $google_event_start->getTimestamp() ||
                        $local_event_end !== $google_event_end->getTimestamp() ||
                        $local_event['notes'] !== $google_event_notes;

                    // Salon Flora customization (2026-08-25) - ASYMMETRIC two-way sync, matching what
                    // Calendly/Cal.com do (see project research notes): BooKi is always the
                    // source of truth for a real APPOINTMENT (a customer booking) - if it diverges from
                    // Google, we push our values back to Google rather than letting a remote edit
                    // silently rewrite the booking. Unavailabilities are the opposite on purpose: they
                    // exist specifically to mirror a provider's OWN personal Google Calendar events into
                    // BooKi, so Google stays authoritative for those.
                    if ($is_different) {
                        if ($local_event['is_unavailability']) {
                            $local_event['start_datetime'] = $google_event_start->format('Y-m-d H:i:s');
                            $local_event['end_datetime'] = $google_event_end->format('Y-m-d H:i:s');
                            $local_event['notes'] = $google_event_notes;
                            $events_model->save($local_event);
                        } else {
                            $CI->google_sync->update_appointment($local_event, $provider, $service, $customer, $settings);
                        }
                    }
                } catch (Throwable) {
                    if ($local_event['is_unavailability']) {
                        // The provider's personal Google event was removed - the mirrored local
                        // unavailability has no reason to exist anymore.
                        $events_model->delete($local_event['id']);
                    } else {
                        // Salon Flora customization (2026-08-25) - a real appointment must NEVER be
                        // silently deleted just because its Google event disappeared (accidental
                        // deletion, a therapist clearing their calendar, etc.). BooKi stays the
                        // source of truth: keep the appointment and RE-CREATE the Google event instead.
                        try {
                            $recreated_event = $CI->google_sync->add_appointment(
                                $local_event,
                                $provider,
                                $service,
                                $customer,
                                $settings,
                            );

                            $local_event = $events_model->find($local_event['id']);
                            $local_event['id_google_calendar'] = $recreated_event->getId();
                            $events_model->save($local_event);
                        } catch (Throwable $recreate_error) {
                            log_message(
                                'error',
                                'Google::sync - could not recreate appointment (' .
                                    $local_event['id'] .
                                    ') on Google after it went missing: ' .
                                    $recreate_error->getMessage(),
                            );
                        }
                    }
                }
            }

            // Add Google Calendar events that do not exist in BooKi.
            $google_calendar = $provider['settings']['google_calendar'];

            try {
                $google_events = $CI->google_sync->get_sync_events($google_calendar, $start, $end);
            } catch (Throwable $e) {
                if ($e->getCode() === 404) {
                    log_message('error', 'Google - Remote Calendar not found for provider ID: ' . $provider_id);

                    return; // The remote calendar was not found.
                } else {
                    throw $e;
                }
            }

            foreach ($google_events->getItems() as $google_event) {
                if ($google_event->getStatus() === 'cancelled') {
                    continue;
                }

                if ($google_event->getStart() === null || $google_event->getEnd() === null) {
                    continue;
                }

                $is_google_all_day = $google_event->getStart()->getDateTime() === null;

                if ($is_google_all_day) {
                    // All-day event: map to 00:00:00 → 23:59:00 of the actual day(s).
                    // Google's end date is exclusive (e.g. a single all-day on the 27th has end.date = '28th').
                    $google_event_start = new DateTime(
                        $google_event->getStart()->getDate() . ' 00:00:00',
                        $provider_timezone,
                    );
                    $google_event_end = new DateTime(
                        $google_event->getEnd()->getDate() . ' 00:00:00',
                        $provider_timezone,
                    );
                    $google_event_end->modify('-1 minute'); // Exclusive end → 23:59:00 of last actual day
                } else {
                    if ($google_event->getStart()->getDateTime() === $google_event->getEnd()->getDateTime()) {
                        continue; // Zero-duration timed event, skip
                    }

                    $google_event_start = new DateTime($google_event->getStart()->getDateTime());
                    $google_event_start->setTimezone($provider_timezone);
                    $google_event_end = new DateTime($google_event->getEnd()->getDateTime());
                    $google_event_end->setTimezone($provider_timezone);
                }

                $appointment_results = $CI->appointments_model->get([
                    'id_google_calendar' => $google_event->getId(),
                    'id_users_provider' => $provider_id,
                ]);

                if (!empty($appointment_results)) {
                    continue;
                }

                $unavailability_results = $CI->unavailabilities_model->get([
                    'id_google_calendar' => $google_event->getId(),
                    'id_users_provider' => $provider_id,
                ]);

                if (!empty($unavailability_results)) {
                    continue;
                }

                // Skip the synthetic "Unavailable" summary that EA itself sets when
                // pushing unavailabilities to Google so it doesn't get duplicated into
                // the local notes/description.
                $google_event_summary = $google_event->getSummary();
                $google_event_notes =
                    strcasecmp(trim((string) $google_event_summary), 'Unavailable') === 0
                        ? (string) $google_event->getDescription()
                        : trim($google_event_summary . ' ' . $google_event->getDescription());

                // Record doesn't exist in the BooKi, so add the event now.
                $local_event = [
                    'start_datetime' => $google_event_start->format('Y-m-d H:i:s'),
                    'end_datetime' => $google_event_end->format('Y-m-d H:i:s'),
                    'is_unavailability' => true,
                    'location' => $google_event->getLocation(),
                    'notes' => $google_event_notes,
                    'id_users_provider' => $provider_id,
                    'id_google_calendar' => $google_event->getId(),
                    'id_users_customer' => null,
                    'id_services' => null,
                ];

                $CI->unavailabilities_model->save($local_event);
            }

            if ($CI->db->table_exists('google_calendar_sync_log')) {
                $CI->db->insert('google_calendar_sync_log', [
                    'id_users_provider' => $provider_id,
                    'trigger' => 'cron_full_scan',
                    'status' => 'success',
                    'event_count' => count($local_events) + count($google_events->getItems()),
                    'synced_at' => date('Y-m-d H:i:s'),
                ]);
            }

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            log_message(
                'error',
                'Google - Sync completed with an error (provider ID "' . $provider_id . '"): ' . $e->getMessage(),
            );

            if ($CI->db->table_exists('google_calendar_sync_log')) {
                $CI->db->insert('google_calendar_sync_log', [
                    'id_users_provider' => (int) $provider_id,
                    'trigger' => 'cron_full_scan',
                    'status' => 'failed',
                    'event_count' => 0,
                    'error_message' => $e->getMessage(),
                    'synced_at' => date('Y-m-d H:i:s'),
                ]);
            }

            if ($e->getCode() === 401) {
                json_response(
                    [
                        'success' => false,
                        'message' => lang('invalid_credentials_provided'),
                    ],
                    401,
                );

                return;
            }

            json_exception($e);
        }
    }

    /**
     * Register (or renew) a push-notification watch channel for a provider's Google Calendar.
     *
     * Called by Console::sync() on every cron run for providers with Google Sync enabled, when no
     * channel is on file yet or the existing one expires within 24 hours. Stops the old channel
     * (if any) before registering the new one so Google does not keep two live channels for the
     * same calendar.
     *
     * @throws Throwable
     */
    public static function ensure_watch_channel(array $provider): void
    {
        /** @var App_Controller $CI */
        $CI = get_instance();

        $CI->load->library('google_sync');

        $calendar_id = $provider['settings']['google_calendar'] ?? null;

        if (empty($calendar_id)) {
            return;
        }

        $google_token = json_decode($provider['settings']['google_token'], true);

        if (empty($google_token['refresh_token'])) {
            return;
        }

        $existing = $CI->db
            ->get_where('google_calendar_watch_channels', [
                'id_users_provider' => $provider['id'],
                'calendar_id' => $calendar_id,
            ])
            ->row_array();

        $needs_renewal =
            !$existing || strtotime($existing['expiration']) - time() < 24 * 60 * 60; // renew within 24h of expiry

        if (!$needs_renewal) {
            return;
        }

        try {
            $CI->google_sync->refresh_token($google_token['refresh_token']);

            if ($existing) {
                try {
                    $CI->google_sync->stop_watch($existing['channel_id'], $existing['resource_id']);
                } catch (Throwable $e) {
                    // Non-fatal - the old channel simply expires on its own if Google can't stop it now.
                    log_message('error', 'Google::ensure_watch_channel - could not stop old channel: ' . $e->getMessage());
                }
            }

            $channel = $CI->google_sync->register_watch($calendar_id);

            $sync_token = $existing['sync_token'] ?? null;

            if (empty($sync_token)) {
                $sync_token = $CI->google_sync->get_initial_sync_token($calendar_id);
            }

            $data = [
                'id_users_provider' => $provider['id'],
                'calendar_id' => $calendar_id,
                'channel_id' => $channel['channel_id'],
                'resource_id' => $channel['resource_id'],
                'channel_token' => $channel['channel_token'],
                'expiration' => $channel['expiration']->format('Y-m-d H:i:s'),
                'sync_token' => $sync_token,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $CI->db->update('google_calendar_watch_channels', $data, ['id' => $existing['id']]);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $CI->db->insert('google_calendar_watch_channels', $data);
            }

            $CI->db->insert('google_calendar_sync_log', [
                'id_users_provider' => $provider['id'],
                'trigger' => 'channel_renewal',
                'status' => 'success',
                'event_count' => 0,
                'synced_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Google::ensure_watch_channel - failed for provider ' . $provider['id'] . ': ' . $e->getMessage());

            $CI->db->insert('google_calendar_sync_log', [
                'id_users_provider' => $provider['id'],
                'trigger' => 'channel_renewal',
                'status' => 'failed',
                'event_count' => 0,
                'error_message' => $e->getMessage(),
                'synced_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Public webhook endpoint - Google POSTs here whenever a watched calendar changes.
     *
     * No admin session exists for this request (Google calls it directly), so authenticity is
     * verified via the channel_token that was registered with the channel (see
     * Google_sync::register_watch()) and echoed back in the X-Goog-Channel-Token header on every
     * notification. Always responds 200 so Google does not retry-storm us on errors we've already
     * logged; a failed incremental sync is caught on the next `console sync` cron's full-window scan.
     */
    public static function webhook(): void
    {
        /** @var App_Controller $CI */
        $CI = get_instance();

        $resource_state = $CI->input->get_request_header('X-Goog-Resource-State');
        $channel_id = $CI->input->get_request_header('X-Goog-Channel-ID');
        $channel_token = $CI->input->get_request_header('X-Goog-Channel-Token');

        // The "sync" state is Google's initial confirmation right after events.watch() succeeds -
        // there is nothing to pull yet.
        if (empty($channel_id) || $resource_state === 'sync') {
            http_response_code(200);
            return;
        }

        $channel = $CI->db->get_where('google_calendar_watch_channels', ['channel_id' => $channel_id])->row_array();

        if (!$channel || !hash_equals($channel['channel_token'], (string) $channel_token)) {
            log_message('error', 'Google::webhook - unknown channel or token mismatch for channel_id: ' . $channel_id);
            http_response_code(200);
            return;
        }

        $CI->load->library('google_sync');
        $CI->load->model('appointments_model');
        $CI->load->model('unavailabilities_model');
        $CI->load->model('providers_model');
        $CI->load->model('services_model');
        $CI->load->model('customers_model');
        $CI->load->model('settings_model');

        $provider = $CI->providers_model->find($channel['id_users_provider']);

        try {
            $google_token = json_decode($provider['settings']['google_token'], true);
            $CI->google_sync->refresh_token($google_token['refresh_token']);

            try {
                $result = $CI->google_sync->get_incremental_events($channel['calendar_id'], $channel['sync_token']);
            } catch (\Google\Service\Exception $e) {
                if ($e->getCode() !== 410) {
                    throw $e;
                }

                // Token invalidated by Google - fall back to a full window resync (reuses all the
                // dedup/re-link/asymmetric-direction logic already battle-tested there), then
                // bootstrap a fresh sync token so future webhook calls can go incremental again.
                self::sync((string) $provider['id']);

                $fresh_token = $CI->google_sync->get_initial_sync_token($channel['calendar_id']);

                $CI->db->update(
                    'google_calendar_watch_channels',
                    ['sync_token' => $fresh_token, 'updated_at' => date('Y-m-d H:i:s')],
                    ['id' => $channel['id']],
                );

                $CI->db->insert('google_calendar_sync_log', [
                    'id_users_provider' => $provider['id'],
                    'trigger' => 'webhook_incremental',
                    'status' => 'success',
                    'event_count' => 0,
                    'error_message' => 'sync_token expired (410) - fell back to full resync',
                    'synced_at' => date('Y-m-d H:i:s'),
                ]);

                http_response_code(200);
                return;
            }

            $provider_timezone = new DateTimeZone($provider['timezone']);

            $applied = 0;

            foreach ($result['items'] as $google_event) {
                $appointment_match = $CI->appointments_model->get([
                    'id_google_calendar' => $google_event->getId(),
                    'id_users_provider' => $provider['id'],
                ]);

                $unavailability_match = $CI->unavailabilities_model->get([
                    'id_google_calendar' => $google_event->getId(),
                    'id_users_provider' => $provider['id'],
                ]);

                $is_cancelled = $google_event->getStatus() === 'cancelled';

                if (!empty($unavailability_match)) {
                    $local_event = $unavailability_match[0];

                    if ($is_cancelled) {
                        // Mirrors the full-sync behaviour: the provider's own Google event is gone,
                        // so the local mirror has no reason to exist.
                        $CI->unavailabilities_model->delete($local_event['id']);
                    } elseif ($google_event->getStart() && $google_event->getEnd()) {
                        $is_all_day = $google_event->getStart()->getDateTime() === null;

                        if ($is_all_day) {
                            $start = new DateTime($google_event->getStart()->getDate() . ' 00:00:00', $provider_timezone);
                            $end = new DateTime($google_event->getEnd()->getDate() . ' 00:00:00', $provider_timezone);
                            $end->modify('-1 minute');
                        } else {
                            $start = new DateTime($google_event->getStart()->getDateTime());
                            $start->setTimezone($provider_timezone);
                            $end = new DateTime($google_event->getEnd()->getDateTime());
                            $end->setTimezone($provider_timezone);
                        }

                        $local_event['start_datetime'] = $start->format('Y-m-d H:i:s');
                        $local_event['end_datetime'] = $end->format('Y-m-d H:i:s');
                        $local_event['notes'] = (string) $google_event->getDescription();
                        $CI->unavailabilities_model->save($local_event);
                    }

                    $applied++;
                    continue;
                }

                if (!empty($appointment_match)) {
                    // BooKi stays the source of truth for real bookings (asymmetric sync
                    // policy, see Google::sync()). A cancelled/edited appointment event on the Google
                    // side is left for the next full `console sync` cron, which already knows how to
                    // recreate/repush it correctly - avoids duplicating that logic here.
                    continue;
                }

                if ($is_cancelled) {
                    continue; // Nothing local references this event - ignore the deletion.
                }

                if (!$google_event->getStart() || !$google_event->getEnd()) {
                    continue;
                }

                $is_all_day = $google_event->getStart()->getDateTime() === null;

                if ($is_all_day) {
                    $start = new DateTime($google_event->getStart()->getDate() . ' 00:00:00', $provider_timezone);
                    $end = new DateTime($google_event->getEnd()->getDate() . ' 00:00:00', $provider_timezone);
                    $end->modify('-1 minute');
                } else {
                    if ($google_event->getStart()->getDateTime() === $google_event->getEnd()->getDateTime()) {
                        continue;
                    }

                    $start = new DateTime($google_event->getStart()->getDateTime());
                    $start->setTimezone($provider_timezone);
                    $end = new DateTime($google_event->getEnd()->getDateTime());
                    $end->setTimezone($provider_timezone);
                }

                $summary = (string) $google_event->getSummary();
                $notes =
                    strcasecmp(trim($summary), 'Unavailable') === 0
                        ? (string) $google_event->getDescription()
                        : trim($summary . ' ' . $google_event->getDescription());

                $CI->unavailabilities_model->save([
                    'start_datetime' => $start->format('Y-m-d H:i:s'),
                    'end_datetime' => $end->format('Y-m-d H:i:s'),
                    'is_unavailability' => true,
                    'location' => $google_event->getLocation(),
                    'notes' => $notes,
                    'id_users_provider' => $provider['id'],
                    'id_google_calendar' => $google_event->getId(),
                    'id_users_customer' => null,
                    'id_services' => null,
                ]);

                $applied++;
            }

            $CI->db->update(
                'google_calendar_watch_channels',
                ['sync_token' => $result['next_sync_token'], 'updated_at' => date('Y-m-d H:i:s')],
                ['id' => $channel['id']],
            );

            $CI->db->insert('google_calendar_sync_log', [
                'id_users_provider' => $provider['id'],
                'trigger' => 'webhook_incremental',
                'status' => 'success',
                'event_count' => $applied,
                'synced_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Google::webhook - failed for provider ' . $provider['id'] . ': ' . $e->getMessage());

            $CI->db->insert('google_calendar_sync_log', [
                'id_users_provider' => $provider['id'],
                'trigger' => 'webhook_incremental',
                'status' => 'failed',
                'event_count' => 0,
                'error_message' => $e->getMessage(),
                'synced_at' => date('Y-m-d H:i:s'),
            ]);
        }

        http_response_code(200);
    }

    /**
     * Authorize Google Calendar API usage for a specific provider.
     *
     * Since it is required to follow the web application flow, in order to retrieve a refresh token from the Google API
     * service, this method is going to authorize the given provider.
     *
     * @param string $provider_id The provider id, for whom the sync authorization is made.
     */
    public function oauth(string $provider_id): void
    {
        $user_id = session('user_id');

        if (!$user_id) {
            show_error('Forbidden', 403);
        }

        // Validate provider_id is a positive integer
        $provider_id = filter_var($provider_id, FILTER_VALIDATE_INT);
        if ($provider_id === false || $provider_id <= 0) {
            show_error('Invalid provider ID', 400);
        }

        if (cannot('edit', PRIV_USERS) && (int) $user_id !== (int) $provider_id) {
            show_error('Forbidden', 403);
        }

        require_plan_feature('google_calendar');

        // Generate and store OAuth state parameter to prevent CSRF
        $csrf_token = bin2hex(random_bytes(32));

        // In multi-tenant mode, package signed state with tenant origin for central relay
        $oauth_state = build_google_oauth_state($csrf_token, 'google/oauth_callback');

        // Store the provider id and state for use on the callback function.
        session([
            'oauth_provider_id' => $provider_id,
            'oauth_state' => $csrf_token,
        ]);

        // Redirect browser to google user content page.
        header('Location: ' . $this->google_sync->get_auth_url($oauth_state));
    }

    /**
     * BooKi (2026-09-19) - Central OAuth relay for multi-tenant SaaS.
     * Google redirects to the platform's central callback (https://bookiapp.kibusiness.co/google/oauth_callback).
     * This method verifies the signed state, checks that the destination host is a legitimate active tenant,
     * and bounces the browser to the tenant's own origin with the auth code intact.
     */
    private function relay_to_tenant_callback(): void
    {
        $state_raw = (string) request('state');
        if (empty($state_raw)) {
            show_error('Geçersiz veya eksik OAuth state parametresi.', 400);
            return;
        }

        $payload = verify_google_oauth_state($state_raw);
        if ($payload === null) {
            show_error('OAuth güvenlik doğrulaması başarısız oldu (imza geçersiz veya süre doldu).', 403);
            return;
        }

        $target_host = strtolower(trim((string) ($payload['host'] ?? '')));
        $target_route = ltrim((string) ($payload['target'] ?? 'google/oauth_callback'), '/');
        $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';

        // Validate destination host against active tenants in master DB to prevent open redirect
        $master_db = $this->load->database('default', true);
        $is_valid = false;

        if ($target_host !== '' && $target_host !== $app_domain) {
            $app_domain_pattern = preg_quote($app_domain, '/');
            if (preg_match('/^([a-z0-9-]+)[-\.]' . $app_domain_pattern . '$/', $target_host, $matches)) {
                $subdomain = $matches[1];
                $tenant = $master_db->get_where('tenants', ['subdomain' => $subdomain, 'status' => 'active'])->row_array();
                if ($tenant) {
                    $is_valid = true;
                }
            } else {
                $tenant = $master_db->get_where('tenants', ['custom_domain' => $target_host, 'status' => 'active'])->row_array();
                if ($tenant) {
                    $is_valid = true;
                }
            }
        }

        if (!$is_valid) {
            show_error('Geçersiz veya aktif olmayan kiracı hedefi.', 400);
            return;
        }

        $query_params = [];
        if (request('code') !== null) {
            $query_params['code'] = request('code');
        }
        if (request('state') !== null) {
            $query_params['state'] = request('state');
        }
        if (request('error') !== null) {
            $query_params['error'] = request('error');
        }

        $dest_url = 'https://' . $target_host . '/' . $target_route . (!empty($query_params) ? '?' . http_build_query($query_params) : '');
        header('Location: ' . $dest_url);
        exit();
    }

    /**
     * Callback method for the Google Calendar API authorization process.
     *
     * Once the user grants consent with his Google Calendar data usage, the Google OAuth service will redirect him back
     * in this page. Here we are going to store the refresh token, because this is what will be used to generate access
     * tokens in the future.
     *
     * IMPORTANT: Because it is necessary to authorize the application using the web server flow (see official
     * documentation of OAuth), every BooKi installation should use its own calendar api key. So in every
     * api console account, the "http://path-to-BooKi/google/oauth_callback" should be included in an
     * allowed redirect URL.
     *
     * @throws Exception
     */
    public function oauth_callback(): void
    {
        $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';
        $current_host = preg_replace('/:\d+$/', '', strtolower((string) ($_SERVER['HTTP_HOST'] ?? '')));

        // Central relay: when the callback lands on the bare platform app domain,
        // unpack the cryptographic state, verify the tenant, and bounce the browser to the tenant's own origin.
        if ($current_host === $app_domain && is_multi_tenant_mode()) {
            $this->relay_to_tenant_callback();
            return;
        }

        if (!session('user_id')) {
            abort(403, 'Forbidden');
        }

        // Verify OAuth state to prevent CSRF attacks. If state is absent (e.g. a stale redirect
        // from before CSRF protection was added) or mismatched, abort gracefully.
        $returned_state = (string) request('state');
        $stored_state = session('oauth_state');

        $csrf_to_verify = $returned_state;
        $unpacked = verify_google_oauth_state($returned_state);
        if ($unpacked !== null && !empty($unpacked['csrf'])) {
            $csrf_to_verify = $unpacked['csrf'];
        }

        if (empty($csrf_to_verify) || empty($stored_state) || !hash_equals($stored_state, $csrf_to_verify)) {
            session(['oauth_state' => null]);
            show_error('Security validation failed. Please try the Google Calendar sync again.', 403);

            return;
        }

        // Clear the state after verification
        session(['oauth_state' => null]);

        $code = request('code');

        if (empty($code)) {
            response('Code authorization failed.');

            return;
        }

        $token = $this->google_sync->authenticate($code);

        if (empty($token)) {
            response('Token authorization failed.');

            return;
        }

        // Store the token into the database for future reference.
        $oauth_provider_id = filter_var(session('oauth_provider_id'), FILTER_VALIDATE_INT);
        $user_id = (int) session('user_id');

        if ($oauth_provider_id && $oauth_provider_id > 0) {
            if (cannot('edit', PRIV_USERS) && $user_id !== (int) $oauth_provider_id) {
                show_error('Forbidden', 403);

                return;
            }

            $provider = $this->providers_model->find($oauth_provider_id);

            // Salon Flora customization (2026-08-25) - each provider gets their OWN dedicated Google
            // Calendar (created here, once) instead of syncing into their personal "primary" calendar -
            // their salon appointments stay separate from their personal events. Idempotent: a
            // reconnect (existing setting isn't 'primary'/empty) keeps reusing the calendar already
            // created, so re-authenticating never spawns duplicates.
            $existing_calendar_id = $this->providers_model->get_setting($oauth_provider_id, 'google_calendar');

            if (empty($existing_calendar_id) || $existing_calendar_id === 'primary') {
                $this->google_sync->refresh_token($token['refresh_token']);

                $calendar_name = trim(
                    (setting('company_name') ?: 'BooKi') . ' - ' . $provider['first_name'] . ' ' . $provider['last_name'],
                );

                try {
                    $google_calendar_id = $this->google_sync->create_calendar($calendar_name, $provider['timezone'] ?? null);
                } catch (Throwable $e) {
                    log_message('error', 'Google oauth_callback - could not create dedicated calendar: ' . $e->getMessage());
                    $google_calendar_id = 'primary';
                }
            } else {
                $google_calendar_id = $existing_calendar_id;
            }

            $this->providers_model->set_setting($oauth_provider_id, 'google_sync', true);
            $this->providers_model->set_setting($oauth_provider_id, 'google_token', json_encode($token));
            $this->providers_model->set_setting($oauth_provider_id, 'google_calendar', $google_calendar_id);
            session(['oauth_provider_id' => null]);

            $this->notify_provider_google_sync_enabled($provider);

            // Notify the opener that OAuth completed successfully, then close this popup. Using
            // postMessage ensures the parent only reacts AFTER the server has saved the token,
            // avoiding the race condition that arises when polling window.document.URL.
            echo '<script>window.opener && window.opener.postMessage("oauth_success", window.location.origin); window.close();</script>';
        } else {
            response('Sync provider id not specified.');
        }
    }

    /**
     * Salon Flora customization (2026-08-25) - let the provider know their Google Calendar sync is now
     * active, over whichever channels are available (email always; native Telegram too if they've linked
     * their account - see Telegram.php). Best-effort: a failure here must never break the OAuth flow the
     * user is actively waiting on.
     *
     * @param array $provider
     */
    private function notify_provider_google_sync_enabled(array $provider): void
    {
        try {
            if (!empty($provider['email'])) {
                $this->load->library('email');

                $this->email->from(setting('company_email') ?: setting('company_name'), setting('company_name'));
                $this->email->to($provider['email']);
                $this->email->subject('Google Takvim senkronizasyonu aktif edildi');
                $this->email->message(
                    'Merhaba ' .
                        $provider['first_name'] .
                        ',<br><br>Randevularınız artık kendi Google Takvim hesabınızdaki özel bir takvime otomatik olarak ekleniyor.',
                );
                $this->email->send();
            }
        } catch (Throwable $e) {
            log_message('error', 'notify_provider_google_sync_enabled - email failed: ' . $e->getMessage());
        }

        try {
            if (!empty($provider['telegram_chat_id'])) {
                $this->load->library('telegram_client');
                $this->telegram_client->send_message(
                    $provider['telegram_chat_id'],
                    '✅ Google Takvim senkronizasyonu aktif edildi. Randevularınız artık kendi Google Takvim\'inizde de görünecek.',
                );
            }
        } catch (Throwable $e) {
            log_message('error', 'notify_provider_google_sync_enabled - telegram failed: ' . $e->getMessage());
        }
    }

    /**
     * This method will return a list of the available Google Calendars.
     *
     * The user will need to select a specific calendar from this list to sync his appointments with. Google access must
     * be already granted for the specific provider.
     */
    public function get_google_calendars(): void
    {
        try {
            method('post');

            check('provider_id', 'numeric');

            $provider_id = (int) request('provider_id');

            if (empty($provider_id)) {
                throw new Exception('Provider id is required in order to fetch the google calendars.');
            }

            // Check if selected provider has sync enabled.
            $google_sync = $this->providers_model->get_setting($provider_id, 'google_sync');

            if (!$google_sync) {
                json_response([
                    'success' => false,
                ]);

                return;
            }

            $google_token = json_decode($this->providers_model->get_setting($provider_id, 'google_token'), true);

            $this->google_sync->refresh_token($google_token['refresh_token']);

            $calendars = $this->google_sync->get_google_calendars();

            json_response($calendars);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Select a specific google calendar for a provider.
     *
     * All the appointments will be synced with this particular calendar.
     */
    public function select_google_calendar(): void
    {
        try {
            method('post');

            check('provider_id', 'numeric');
            check('calendar_id', 'string');

            $provider_id = request('provider_id');

            $user_id = session('user_id');

            if (cannot('edit', PRIV_USERS) && (int) $user_id !== (int) $provider_id) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            $calendar_id = request('calendar_id');

            $this->providers_model->set_setting($provider_id, 'google_calendar', $calendar_id);

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Disable a providers sync setting.
     *
     * This method deletes the "google_sync" and "google_token" settings from the database.
     *
     * After that the provider's appointments will be no longer synced with Google Calendar.
     */
    public function disable_provider_sync(): void
    {
        try {
            method('post');

            check('provider_id', 'numeric');

            $provider_id = request('provider_id');

            if (!$provider_id) {
                throw new Exception('Provider id not specified.');
            }

            $user_id = session('user_id');

            if (cannot('edit', PRIV_USERS) && (int) $user_id !== (int) $provider_id) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            $watch_channel = $this->db
                ->get_where('google_calendar_watch_channels', ['id_users_provider' => $provider_id])
                ->row_array();

            if ($watch_channel) {
                try {
                    $this->google_sync->refresh_token(
                        json_decode($this->providers_model->find($provider_id)['settings']['google_token'], true)[
                            'refresh_token'
                        ],
                    );
                    $this->google_sync->stop_watch($watch_channel['channel_id'], $watch_channel['resource_id']);
                } catch (Throwable $e) {
                    // Non-fatal - the channel simply expires on Google's side if we can't stop it now.
                    log_message('error', 'Google::disable_provider_sync - could not stop watch channel: ' . $e->getMessage());
                }

                $this->db->delete('google_calendar_watch_channels', ['id' => $watch_channel['id']]);
            }

            $this->providers_model->set_setting($provider_id, 'google_sync', false);

            $this->providers_model->set_setting($provider_id, 'google_token');

            $this->appointments_model->clear_google_sync_ids($provider_id);

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
