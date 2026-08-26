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
 * Password Reset HTTP client.
 *
 * This module implements the password reset related HTTP requests.
 */
App.Http.PasswordReset = (function () {
    /**
     * Complete the password reset.
     *
     * @param {String} token
     * @param {String} password
     * @param {String} passwordConfirm
     * @param {String} captcha
     * @param {String} altchaPayload
     *
     * @return {Object}
     */
    function complete(token, password, passwordConfirm, captcha, altchaPayload) {
        const url = App.Utils.Url.siteUrl('recovery/complete');

        const data = {
            csrf_token: vars('csrf_token'),
            token,
            password,
            password_confirm: passwordConfirm,
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
        complete,
    };
})();
