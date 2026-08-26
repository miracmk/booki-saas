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
 * Legal Settings HTTP client.
 *
 * This module implements the legal settings related HTTP requests.
 */
App.Http.LegalSettings = (function () {
    /**
     * Save legal settings.
     *
     * @param {Object} legalSettings
     *
     * @return {Object}
     */
    function save(legalSettings) {
        const url = App.Utils.Url.siteUrl('legal_settings/save');

        const data = {
            csrf_token: vars('csrf_token'),
            legal_settings: legalSettings,
        };

        return $.post(url, data);
    }

    return {
        save,
    };
})();
