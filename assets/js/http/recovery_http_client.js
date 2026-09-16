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
 * Recovery HTTP client.
 *
 * This module implements the account recovery related HTTP requests.
 */
App.Http.Recovery = (function () {
    /**
     * Perform an account recovery.
     *
     * @param {String} username
     * @param {String} email
     * @param {String} captcha
     * @param {String} altchaPayload
     *
     * @return {Object}
     */
    function perform(username, email, captcha, altchaPayload) {
        const url = App.Utils.Url.siteUrl('recovery/perform');

        const data = {
            csrf_token: vars('csrf_token'),
            username,
            email,
        };

        if (captcha) {
            data.captcha = captcha;
        }
        
        if (altchaPayload) {
            data.altcha_payload = altchaPayload;
        }

        return $.post(url, data);
    }

    return {
        perform,
    };
})();
