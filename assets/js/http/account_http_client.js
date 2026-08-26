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
 * Account HTTP client.
 *
 * This module implements the account related HTTP requests.
 */
App.Http.Account = (function () {
    /**
     * Save account.
     *
     * @param {Object} account
     *
     * @return {Object}
     */
    function save(account) {
        const url = App.Utils.Url.siteUrl('account/save');

        const data = {
            csrf_token: vars('csrf_token'),
            account,
        };

        return $.post(url, data);
    }

    /**
     * Validate username.
     *
     * @param {Number} userId
     * @param {String} username
     *
     * @return {Object}
     */
    function validateUsername(userId, username) {
        const url = App.Utils.Url.siteUrl('account/validate_username');

        const data = {
            csrf_token: vars('csrf_token'),
            user_id: userId,
            username,
        };

        return $.post(url, data);
    }

    return {
        save,
        validateUsername,
    };
})();
