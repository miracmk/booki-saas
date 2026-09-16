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
 * Jitsi Settings HTTP client.
 *
 * This module implements the Jitsi settings related HTTP requests.
 */
App.Http.JitsiSettings = (function () {
    /**
     * Save Jitsi settings.
     *
     * @param {Array} jitsiSettings
     *
     * @return {Object}
     */
    function save(jitsiSettings) {
        const url = App.Utils.Url.siteUrl('jitsi_settings/save');

        const data = {
            csrf_token: vars('csrf_token'),
            jitsi_settings: jitsiSettings,
        };

        return $.post(url, data);
    }

    return {
        save,
    };
})();
