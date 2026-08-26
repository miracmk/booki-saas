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
 * Email Template Settings HTTP client.
 */
App.Http.EmailTemplateSettings = (function () {
    /**
     * Save the provided templates.
     *
     * @param {Array} templates Array of {name, value} objects.
     *
     * @return {Object}
     */
    function save(templates) {
        const url = App.Utils.Url.siteUrl('email_template_settings/save');

        const data = {
            csrf_token: vars('csrf_token'),
            templates,
        };

        return $.post(url, data);
    }

    /**
     * Render a live preview of a (possibly unsaved) template against sample data.
     *
     * @param {String} templateKey
     * @param {String} html
     *
     * @return {Object}
     */
    function preview(templateKey, html) {
        const url = App.Utils.Url.siteUrl('email_template_settings/preview');

        const data = {
            csrf_token: vars('csrf_token'),
            template_key: templateKey,
            html,
        };

        return $.post(url, data);
    }

    return {
        save,
        preview,
    };
})();
