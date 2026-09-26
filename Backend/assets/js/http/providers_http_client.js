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
 * Providers HTTP client.
 *
 * This module implements the providers related HTTP requests.
 */
App.Http.Providers = (function () {
    /**
     * Save (create or update) a provider.
     *
     * @param {Object} provider
     *
     * @return {Object}
     */
    function save(provider) {
        return provider.id ? update(provider) : store(provider);
    }

    /**
     * Create a provider.
     *
     * @param {Object} provider
     *
     * @return {Object}
     */
    function store(provider) {
        const url = App.Utils.Url.siteUrl('providers/store');

        const data = {
            csrf_token: vars('csrf_token'),
            provider: provider,
        };

        return $.post(url, data);
    }

    /**
     * Update a provider.
     *
     * @param {Object} provider
     *
     * @return {Object}
     */
    function update(provider) {
        const url = App.Utils.Url.siteUrl('providers/update');

        const data = {
            csrf_token: vars('csrf_token'),
            provider: provider,
        };

        return $.post(url, data);
    }

    /**
     * Delete a provider.
     *
     * @param {Number} providerId
     *
     * @return {Object}
     */
    function destroy(providerId) {
        const url = App.Utils.Url.siteUrl('providers/destroy');

        const data = {
            csrf_token: vars('csrf_token'),
            provider_id: providerId,
        };

        return $.post(url, data);
    }

    /**
     * Search providers by keyword.
     *
     * @param {String} keyword
     * @param {Number} [limit]
     * @param {Number} [offset]
     * @param {String} [orderBy]
     *
     * @return {Object}
     */
    function search(keyword, limit = null, offset = null, orderBy = null) {
        const url = App.Utils.Url.siteUrl('providers/search');

        const data = {
            csrf_token: vars('csrf_token'),
            keyword,
            limit,
            offset,
            order_by: orderBy || undefined,
        };

        return $.post(url, data);
    }

    /**
     * Find a provider.
     *
     * @param {Number} providerId
     *
     * @return {Object}
     */
    function find(providerId) {
        const url = App.Utils.Url.siteUrl('providers/find');

        const data = {
            csrf_token: vars('csrf_token'),
            provider_id: providerId,
        };

        return $.post(url, data);
    }

    /**
     * BooKi (2026-08-26) - find or create a skill by name.
     *
     * @param {String} name
     *
     * @return {Object}
     */
    function createSkill(name) {
        const url = App.Utils.Url.siteUrl('providers/create_skill');

        const data = {
            csrf_token: vars('csrf_token'),
            name,
        };

        return $.post(url, data);
    }

    return {
        save,
        store,
        update,
        destroy,
        search,
        find,
        createSkill,
    };
})();
