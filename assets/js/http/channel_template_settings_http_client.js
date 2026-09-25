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
 * Channel Template Settings HTTP client.
 */
App.Http.ChannelTemplateSettings = (function () {
    /**
     * Save the provided channel templates.
     *
     * @param {Array} templates Array of {name, value} objects.
     *
     * @return {Object}
     */
    function save(templates) {
        const url = App.Utils.Url.siteUrl('channel_template_settings/save');

        const data = {
            csrf_token: vars('csrf_token'),
            templates,
        };

        return $.post(url, data);
    }

    /**
     * Render a live preview of a (possibly unsaved) template against sample channel data.
     *
     * @param {String} templateKey
     * @param {String} value
     *
     * @return {Object}
     */
    function preview(templateKey, value) {
        const url = App.Utils.Url.siteUrl('channel_template_settings/preview');

        const data = {
            csrf_token: vars('csrf_token'),
            template_key: templateKey,
            value,
        };

        return $.post(url, data);
    }

    return {
        save,
        preview,
    };
})();