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
 * Strings utility.
 *
 * This module implements the functionality of strings.
 */
window.App.Utils.String = (function () {
    /**
     * Upper case the first letter of the provided string.
     *
     * Old Name: GeneralFunctions.upperCaseFirstLetter
     *
     * @param {String} value
     *
     * @returns {string}
     */
    function upperCaseFirstLetter(value) {
        return value.charAt(0).toUpperCase() + value.slice(1);
    }

    /**
     * Escape HTML content with the use of jQuery.
     *
     * Old Name: GeneralFunctions.escapeHtml
     *
     * @param {String} content
     *
     * @return {String}
     */
    function escapeHtml(content) {
        return $('<div/>').text(content).html();
    }

    return {
        upperCaseFirstLetter,
        escapeHtml,
    };
})();
