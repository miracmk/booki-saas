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
 * Matomo Analytics Settings HTTP client.
 *
 * This module implements the Matomo Analytics settings related HTTP requests.
 */
App.Http.MatomoAnalyticsSettings = (function () {
    /**
     * Save Matomo Analytics settings.
     *
     * @param {Object} matomoAnalyticsSettings
     *
     * @return {Object}
     */
    function save(matomoAnalyticsSettings) {
        const url = App.Utils.Url.siteUrl('matomo_analytics_settings/save');

        const data = {
            csrf_token: vars('csrf_token'),
            matomo_analytics_settings: matomoAnalyticsSettings,
        };

        return $.post(url, data);
    }

    return {
        save,
    };
})();
