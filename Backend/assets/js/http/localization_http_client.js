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
 * Localization HTTP client.
 *
 * This module implements the account related HTTP requests.
 */
App.Http.Localization = (function () {
    /**
     * Change language.
     *
     * @param {String} language
     */
    function changeLanguage(language) {
        const url = App.Utils.Url.siteUrl('localization/change_language');

        const data = {
            csrf_token: vars('csrf_token'),
            language,
        };

        return $.post(url, data);
    }

    return {
        changeLanguage,
    };
})();
