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
 * ALTCHA Settings HTTP client.
 *
 * This module implements the ALTCHA settings related HTTP requests.
 */
App.Http.AltchaSettings = (function () {
    /**
     * Save ALTCHA settings.
     *
     * @param {Array} altchaSettings
     *
     * @return {Object}
     */
    function save(altchaSettings) {
        const url = App.Utils.Url.siteUrl('altcha_settings/save');

        const data = {
            csrf_token: vars('csrf_token'),
            altcha_settings: altchaSettings,
        };

        return $.post(url, data);
    }

    /**
     * Generate a new HMAC key.
     *
     * @return {Object}
     */
    function generateKey() {
        const url = App.Utils.Url.siteUrl('altcha_settings/generate_key');

        const data = {
            csrf_token: vars('csrf_token'),
        };

        return $.post(url, data);
    }

    return {
        save,
        generateKey,
    };
})();
