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
 * Google Analytics Settings HTTP client.
 *
 * This module implements the Google Analytics settings related HTTP requests.
 */
App.Http.GoogleAnalyticsSettings = (function () {
    /**
     * Save Google Analytics settings.
     *
     * @param {Object} googleAnalyticsSettings
     *
     * @return {Object}
     */
    function save(googleAnalyticsSettings) {
        const url = App.Utils.Url.siteUrl('google_analytics_settings/save');

        const data = {
            csrf_token: vars('csrf_token'),
            google_analytics_settings: googleAnalyticsSettings,
        };

        return $.post(url, data);
    }

    return {
        save,
    };
})();
