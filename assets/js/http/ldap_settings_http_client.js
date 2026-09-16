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
 * LDAP Settings HTTP client.
 *
 * This module implements the LDAP settings related HTTP requests.
 */
App.Http.LdapSettings = (function () {
    /**
     * Save LDAP settings.
     *
     * @param {Object} ldapSettings
     *
     * @return {Object}
     */
    function save(ldapSettings) {
        const url = App.Utils.Url.siteUrl('ldap_settings/save');

        const data = {
            csrf_token: vars('csrf_token'),
            ldap_settings: ldapSettings,
        };

        return $.post(url, data);
    }

    /**
     * Search LDAP server.
     *
     * @param {String} keyword
     *
     * @return {Object}
     */
    function search(keyword) {
        const url = App.Utils.Url.siteUrl('ldap_settings/search');

        const data = {
            csrf_token: vars('csrf_token'),
            keyword,
        };

        return $.post(url, data);
    }

    return {
        save,
        search,
    };
})();
