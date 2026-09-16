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
 * Google Calendar Settings HTTP client.
 *
 * This module implements the Google Calendar Settings related HTTP requests.
 */
App.Http.GoogleCalendarSettings = (function () {
    /**
     * Save Google Calendar settings.
     *
     * @param {Array} googleCalendarSettings
     *
     * @return {*|jQuery}
     */
    function save(googleCalendarSettings) {
        const url = App.Utils.Url.siteUrl('google_calendar_settings/save');

        const data = {
            csrf_token: vars('csrf_token'),
            google_calendar_settings: googleCalendarSettings,
        };

        return $.post(url, data);
    }

    return {
        save,
    };
})();
