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
 * Calendar HTTP client.
 *
 * This module implements the calendar related HTTP requests.
 *
 * Old Name: BackendCalendarApi
 */
App.Http.Calendar = (function () {
    /**
     * Save Appointment
     *
     * This method stores the changes of an already registered appointment into the database, via an ajax call.
     *
     * @param {Object} appointment Contain the new appointment data. The ID of the appointment must be already included.
     * The rest values must follow the database structure.
     * @param {Object} [customer] Optional, contains the customer data.
     * @param {Function} [successCallback] Optional, if defined, this function is going to be executed on post success.
     * @param {Function} [errorCallback] Optional, if defined, this function is going to be executed on post failure.
     * @param {Boolean|Object} [notify] Optional, whether to send notifications (defaults to true = notify
     * everyone). Either a single boolean applied to all recipients, or a {customer, provider, admin} object
     * for per-recipient control (see App.Utils.Message.confirmNotifyOptions).
     * @param {Boolean} [forceSave] Optional, whether to force save even if there's a conflict (defaults to false).
     *
     * @return {*|jQuery}
     */
    function saveAppointment(appointment, customer, successCallback, errorCallback, notify = true, forceSave = false) {
        const url = App.Utils.Url.siteUrl('calendar/save_appointment');

        const notifyOptions =
            typeof notify === 'object' && notify !== null
                ? notify
                : {customer: notify, provider: notify, admin: notify};

        const data = {
            csrf_token: vars('csrf_token'),
            appointment_data: appointment,
            notify_customer: notifyOptions.customer ? 1 : 0,
            notify_provider: notifyOptions.provider ? 1 : 0,
            notify_admin: notifyOptions.admin ? 1 : 0,
            force_save: forceSave ? 1 : 0,
        };

        if (customer) {
            data.customer_data = customer;
        }

        return $.post(url, data)
            .done((response) => {
                if (successCallback) {
                    successCallback(response);
                }
            })
            .fail(() => {
                if (errorCallback) {
                    errorCallback();
                }
            });
    }

    /**
     * Remove an appointment.
     *
     * @param {Number} appointmentId
     * @param {String} cancellationReason
     *
     * @return {*|jQuery}
     */
    function deleteAppointment(appointmentId, cancellationReason, notifyUsers = true) {
        const url = App.Utils.Url.siteUrl('calendar/delete_appointment');

        const data = {
            csrf_token: vars('csrf_token'),
            appointment_id: appointmentId,
            cancellation_reason: cancellationReason,
            notify_users: notifyUsers ? 1 : 0,
        };

        return $.post(url, data);
    }

    /**
     * Save unavailability period to database.
     *
     * @param {Object} unavailability Contains the unavailability period data.
     * @param {Function} [successCallback] The ajax success callback function.
     * @param {Function} [errorCallback] The ajax failure callback function.
     *
     * @return {*|jQuery}
     */
    function saveUnavailability(unavailability, successCallback, errorCallback) {
        const url = App.Utils.Url.siteUrl('calendar/save_unavailability');

        const data = {
            csrf_token: vars('csrf_token'),
            unavailability: unavailability,
        };

        return $.post(url, data)
            .done((response) => {
                if (successCallback) {
                    successCallback(response);
                }
            })
            .fail(() => {
                if (errorCallback) {
                    errorCallback();
                }
            });
    }

    /**
     * Remove an unavailability.
     *
     * @param {Number} unavailabilityId
     *
     * @return {*|jQuery}
     */
    function deleteUnavailability(unavailabilityId) {
        const url = App.Utils.Url.siteUrl('calendar/delete_unavailability');

        const data = {
            csrf_token: vars('csrf_token'),
            unavailability_id: unavailabilityId,
        };

        return $.post(url, data);
    }

    /**
     * Save working plan exception of work to database.
     *
     * @param {Date} date Contains the working plan exceptions data.
     * @param {Object} workingPlanException Contains the working plan exceptions data.
     * @param {Number} providerId Contains the working plan exceptions data.
     * @param {Function} successCallback The ajax success callback function.
     * @param {Function} errorCallback The ajax failure callback function.
     * @param {Date} [originalDate] On edit, provide the original date.
     *
     * @return {*|jQuery}
     */
    function saveWorkingPlanException(workingPlanException, providerId, successCallback, errorCallback) {
        const url = App.Utils.Url.siteUrl('calendar/save_working_plan_exception');

        const data = {
            csrf_token: vars('csrf_token'),
            working_plan_exception: workingPlanException,
            provider_id: providerId,
        };

        return $.post(url, data)
            .done((response) => {
                if (successCallback) {
                    successCallback(response);
                }
            })
            .fail(() => {
                if (errorCallback) {
                    errorCallback();
                }
            });
    }

    /**
     * Delete working plan exception
     *
     * @param {Number} exceptionId
     * @param {Number} providerId
     * @param {Function} [successCallback]
     * @param {Function} [errorCallback]
     *
     * @return {*|jQuery}
     */
    function deleteWorkingPlanException(exceptionId, providerId, successCallback, errorCallback) {
        const url = App.Utils.Url.siteUrl('calendar/delete_working_plan_exception');

        const data = {
            csrf_token: vars('csrf_token'),
            exception_id: exceptionId,
            provider_id: providerId,
        };

        return $.post(url, data)
            .done((response) => {
                if (successCallback) {
                    successCallback(response);
                }
            })
            .fail(() => {
                if (errorCallback) {
                    errorCallback();
                }
            });
    }

    /**
     * Get the appointments for the displayed calendar period.
     *
     * @param {Number} recordId Record ID (provider or service).
     * @param {String} filterType The filter type, could be either "provider" or "service".
     * @param {String} startDate Visible start date of the calendar.
     * @param {String} endDate Visible end date of the calendar.
     *
     * @returns {jQuery.jqXHR}
     */
    function getCalendarAppointments(recordId, startDate, endDate, filterType) {
        const url = App.Utils.Url.siteUrl('calendar/get_calendar_appointments');

        const data = {
            csrf_token: vars('csrf_token'),
            record_id: recordId,
            start_date: moment(startDate).format('YYYY-MM-DD'),
            end_date: moment(endDate).format('YYYY-MM-DD'),
            filter_type: filterType,
        };

        return $.post(url, data);
    }

    /**
     * Get the calendar appointments for the table view (different data structure).
     *
     * @param {Date} startDate
     * @param {Date} endDate
     *
     * @return {*|jQuery}
     */
    function getCalendarAppointmentsForTableView(startDate, endDate) {
        const url = App.Utils.Url.siteUrl('calendar/get_calendar_appointments_for_table_view');

        const data = {
            csrf_token: vars('csrf_token'),
            start_date: moment(startDate).format('YYYY-MM-DD'),
            end_date: moment(endDate).format('YYYY-MM-DD'),
        };

        return $.post(url, data);
    }

    /**
     * Save appointment with conflict handling.
     *
     * This method saves an appointment and handles conflict responses by showing a confirmation dialog
     * that allows the user to force save or cancel the operation.
     *
     * @param {Object} appointment The appointment data to save.
     * @param {Object} [customer] Optional customer data.
     * @param {Function} successCallback Callback function to execute on successful save.
     * @param {Function} [errorCallback] Optional callback function to execute on error.
     * @param {Boolean|Object} notify Whether to notify users - see saveAppointment().
     * @param {Function} [revertCallback] Optional callback function to execute when user cancels on conflict.
     */
    function saveAppointmentWithConflictHandling(
        appointment,
        customer,
        successCallback,
        errorCallback,
        notify,
        revertCallback,
    ) {
        const attemptSave = (forceSave = false) => {
            saveAppointment(appointment, customer, null, errorCallback, notify, forceSave).done((response) => {
                if (response.conflict) {
                    // Show conflict confirmation dialog
                    App.Utils.Message.show(
                        lang('appointment_update'),
                        response.message + ' ' + lang('would_you_like_to_proceed'),
                        [
                            {
                                text: lang('cancel'),
                                click: (event, messageModal) => {
                                    messageModal.hide();
                                    if (revertCallback) {
                                        revertCallback();
                                    }
                                },
                            },
                            {
                                text: lang('proceed'),
                                click: (event, messageModal) => {
                                    messageModal.hide();
                                    attemptSave(true);
                                },
                            },
                        ],
                    );
                } else if (response.success) {
                    if (successCallback) {
                        successCallback(response);
                    }
                }
            });
        };

        attemptSave();
    }

    /**
     * Salon Flora customization - mark an appointment's real check-in (seans başlangıcı) time as "now".
     *
     * @param {Number} appointmentId
     *
     * @return {*|jQuery}
     */
    function checkIn(appointmentId) {
        const url = App.Utils.Url.siteUrl('calendar/check_in');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            appointment_id: appointmentId,
        });
    }

    /**
     * Salon Flora customization - manually (re)send an appointment notification without saving any change,
     * from the calendar popover's "Bildirim Gönder" button.
     *
     * @param {Number} appointmentId
     * @param {Boolean|Object} notify Whether to notify users - see saveAppointment().
     *
     * @return {*|jQuery}
     */
    function sendNotification(appointmentId, notify = true) {
        const url = App.Utils.Url.siteUrl('calendar/send_notification');

        const notifyOptions =
            typeof notify === 'object' && notify !== null
                ? notify
                : {customer: notify, provider: notify, admin: notify};

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            appointment_id: appointmentId,
            notify_customer: notifyOptions.customer ? 1 : 0,
            notify_provider: notifyOptions.provider ? 1 : 0,
            notify_admin: notifyOptions.admin ? 1 : 0,
        });
    }

    /**
     * Salon Flora customization - mark an appointment's real check-out (seans bitişi) time as "now".
     *
     * If the real session duration deviates from the service's planned duration beyond the configured
     * thresholds, the backend rejects the request (nothing is written) unless a deviationReason is supplied - the
     * response will have `requires_reason: true` and a `deviation` object describing the mismatch.
     *
     * @param {Number} appointmentId
     * @param {String|null} deviationReason
     *
     * @return {*|jQuery}
     */
    function checkOut(appointmentId, deviationReason = null, earlyExit = null) {
        const url = App.Utils.Url.siteUrl('calendar/check_out');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            appointment_id: appointmentId,
            deviation_reason: deviationReason,
            early_exit_justification: earlyExit?.justification || null,
            early_exit_reason_code: earlyExit?.reasonCode || null,
        });
    }

    /**
     * Salon Flora customization (2026-08-25) - admin/secretary approves (or overturns) a therapist's own
     * pending "haklı erken çıkış" claim, or retroactively (re)classifies an older completed session.
     *
     * @param {Number} appointmentId
     * @param {String} justification 'justified' | 'unjustified'.
     * @param {String} reasonCode One of the keys from vars('early_exit_reason_codes').
     *
     * @return {*|jQuery}
     */
    function reviewEarlyExit(appointmentId, justification, reasonCode) {
        const url = App.Utils.Url.siteUrl('calendar/review_early_exit');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            appointment_id: appointmentId,
            justification,
            reason_code: reasonCode,
        });
    }

    /**
     * Salon Flora customization - manually set (correct/backfill) an appointment's real check-in/check-out
     * timestamps. Only admins/secretaries are authorized server-side - providers get a 403.
     *
     * @param {Number} appointmentId
     * @param {String|null} actualStartDatetime 'YYYY-MM-DD HH:mm:ss' or null.
     * @param {String|null} actualEndDatetime 'YYYY-MM-DD HH:mm:ss' or null.
     *
     * @return {*|jQuery}
     */
    function updateSessionTimes(appointmentId, actualStartDatetime, actualEndDatetime) {
        const url = App.Utils.Url.siteUrl('calendar/update_session_times');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            appointment_id: appointmentId,
            actual_start_datetime: actualStartDatetime,
            actual_end_datetime: actualEndDatetime,
        });
    }

    function clearSessionTimes(appointmentId) {
        const url = App.Utils.Url.siteUrl('calendar/clear_session_times');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            appointment_id: appointmentId,
        });
    }

    /**
     * Salon Flora customization - manually assign (or clear, with stationId = null) the physical station an
     * appointment uses.
     *
     * @param {Number} appointmentId
     * @param {Number|null} stationId
     *
     * @return {*|jQuery}
     */
    function updateStation(appointmentId, stationId) {
        const url = App.Utils.Url.siteUrl('calendar/update_station');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            appointment_id: appointmentId,
            station_id: stationId,
        });
    }

    /**
     * Salon Flora customization (2026-08-25) - quick "add a note" popover action; appends to the
     * appointment's notes rather than replacing them.
     *
     * @param {Number} appointmentId
     * @param {String} note
     *
     * @return {*|jQuery}
     */
    function addNote(appointmentId, note) {
        const url = App.Utils.Url.siteUrl('calendar/add_note');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            appointment_id: appointmentId,
            note,
        });
    }

    /**
     * Salon Flora customization - fetch appointments that are currently checked in but not checked out yet, for
     * the active-sessions widget.
     *
     * @return {*|jQuery}
     */
    function getActiveSessions() {
        const url = App.Utils.Url.siteUrl('calendar/get_active_sessions');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
        });
    }

    /**
     * Salon Flora customization - record payment/collection details for a completed appointment. Only
     * admins/secretaries are authorized server-side; providers get a 403 if this is somehow called for them.
     *
     * @param {Number} appointmentId
     * @param {Object} paymentData {payment_status, payment_method, payment_amount, payment_balance_amount, is_invoiced}
     *
     * @return {*|jQuery}
     */
    function updatePayment(appointmentId, paymentData) {
        const url = App.Utils.Url.siteUrl('calendar/update_payment');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            appointment_id: appointmentId,
            ...paymentData,
        });
    }

    /**
     * Salon Flora customization - which providers can serve a service and whether each is free at the given time.
     * Powers the sequential booking form's provider step.
     *
     * @param {Number} serviceId
     * @param {String} startDatetime 'YYYY-MM-DD HH:mm:ss'
     * @param {String} endDatetime 'YYYY-MM-DD HH:mm:ss'
     * @param {Number|null} appointmentId Exclude this appointment from conflict checks (when editing).
     *
     * @return {*|jQuery}
     */
    function getAvailableProviders(serviceId, startDatetime, endDatetime, appointmentId = null) {
        const url = App.Utils.Url.siteUrl('calendar/get_available_providers');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            service_id: serviceId,
            start_datetime: startDatetime,
            end_datetime: endDatetime,
            appointment_id: appointmentId,
        });
    }

    /**
     * Salon Flora customization - which stations can host a service+provider combination and whether each is free
     * at the given time. Powers the sequential booking form's station step.
     *
     * @param {Number} serviceId
     * @param {Number} providerId
     * @param {String} startDatetime 'YYYY-MM-DD HH:mm:ss'
     * @param {String} endDatetime 'YYYY-MM-DD HH:mm:ss'
     * @param {Number|null} appointmentId Exclude this appointment from conflict checks (when editing).
     *
     * @return {*|jQuery}
     */
    function getAvailableStations(serviceId, providerId, startDatetime, endDatetime, appointmentId = null) {
        const url = App.Utils.Url.siteUrl('calendar/get_available_stations');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            service_id: serviceId,
            provider_id: providerId,
            start_datetime: startDatetime,
            end_datetime: endDatetime,
            appointment_id: appointmentId,
        });
    }

    /**
     * "İlk Müsaitlik" toolbar widget - 2026-09-10.
     *
     * @param {number|null} providerId - A specific provider, or null/undefined for "any provider".
     */
    function getNextAvailability(providerId) {
        const url = App.Utils.Url.siteUrl('calendar/get_next_availability');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            provider_id: providerId || '',
        });
    }

    /**
     * BooKi (2026-09-12) - room/station side of the "İlk Müsaitlik" strip.
     */
    function getRoomAvailability() {
        const url = App.Utils.Url.siteUrl('calendar/get_room_availability');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
        });
    }

    return {
        saveAppointment,
        saveAppointmentWithConflictHandling,
        deleteAppointment,
        saveUnavailability,
        deleteUnavailability,
        saveWorkingPlanException,
        deleteWorkingPlanException,
        getCalendarAppointments,
        getCalendarAppointmentsForTableView,
        checkIn,
        checkOut,
        reviewEarlyExit,
        addNote,
        sendNotification,
        clearSessionTimes,
        updateSessionTimes,
        updateStation,
        getActiveSessions,
        updatePayment,
        getAvailableProviders,
        getAvailableStations,
        getNextAvailability,
        getRoomAvailability,
    };
})();
