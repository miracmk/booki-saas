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
 * API Settings HTTP client.
 *
 * This module implements the API settings related HTTP requests.
 */
App.Http.ApiSettings = (function () {
    /**
     * Save API settings.
     *
     * @param {Object} apiSettings
     *
     * @return {Object}
     */
    function save(apiSettings) {
        const url = App.Utils.Url.siteUrl('api_settings/save');

        const data = {
            csrf_token: vars('csrf_token'),
            api_settings: apiSettings,
        };

        return $.post(url, data);
    }

    return {
        save,
    };
})();
