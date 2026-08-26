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
 * Appointments HTTP client.
 *
 * This module implements the appointments related HTTP requests.
 */
App.Http.Appointments = (function () {
    /**
     * Save (create or update) an appointment.
     *
     * @param {Object} appointment
     *
     * @return {Object}
     */
    function save(appointment) {
        return appointment.id ? update(appointment) : store(appointment);
    }

    /**
     * Create an appointment.
     *
     * @param {Object} appointment
     *
     * @return {Object}
     */
    function store(appointment) {
        const url = App.Utils.Url.siteUrl('appointments/store');

        const data = {
            csrf_token: vars('csrf_token'),
            appointment: appointment,
        };

        return $.post(url, data);
    }

    /**
     * Update an appointment.
     *
     * @param {Object} appointment
     *
     * @return {Object}
     */
    function update(appointment) {
        const url = App.Utils.Url.siteUrl('appointments/update');

        const data = {
            csrf_token: vars('csrf_token'),
            appointment: appointment,
        };

        return $.post(url, data);
    }

    /**
     * Delete an appointment.
     *
     * @param {Number} appointmentId
     *
     * @return {Object}
     */
    function destroy(appointmentId) {
        const url = App.Utils.Url.siteUrl('appointments/destroy');

        const data = {
            csrf_token: vars('csrf_token'),
            appointment_id: appointmentId,
        };

        return $.post(url, data);
    }

    /**
     * Search appointments by keyword.
     *
     * @param {String} keyword
     * @param {Number} [limit]
     * @param {Number} [offset]
     * @param {String} [orderBy]
     *
     * @return {Object}
     */
    function search(keyword, limit = null, offset = null, orderBy = null) {
        const url = App.Utils.Url.siteUrl('appointments/search');

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
     * Find an appointment.
     *
     * @param {Number} appointmentId
     *
     * @return {Object}
     */
    function find(appointmentId) {
        const url = App.Utils.Url.siteUrl('appointments/find');

        const data = {
            csrf_token: vars('csrf_token'),
            appointment_id: appointmentId,
        };

        return $.post(url, data);
    }

    /**
     * Ki Reservation (2026-08-26) - "İlk Müsaitlik": find the first upcoming date/hour slots for a
     * service, each already matched to a specific free provider + station.
     *
     * @param {Number|String} serviceId
     * @param {Number} [limit]
     *
     * @return {Object}
     */
    function firstAvailability(serviceId, limit) {
        const url = App.Utils.Url.siteUrl('appointments/first_availability');

        const data = {
            csrf_token: vars('csrf_token'),
            service_id: serviceId,
            limit: limit || 3,
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
        firstAvailability,
    };
})();
