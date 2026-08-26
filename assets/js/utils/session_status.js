/* ----------------------------------------------------------------------------
 * Salon Flora customization.
 *
 * Pure session-status calculations shared by the calendar views, the event
 * popover, the appointment modal and the active-sessions widget. Every
 * "is this session running / overdue / how many minutes left" decision in
 * the app should go through compute() so the rules only live in one place.
 *
 * Crucial rule: the expected end of a session is always measured from the
 * REAL check-in time (actual_start_datetime), never from the originally
 * booked start_datetime. A session that started late is not expected to
 * end at the originally booked end_datetime.
 * ---------------------------------------------------------------------------- */
App.Utils.SessionStatus = (function () {
    const moment = window.moment;

    // A session that started late is measured from the real check-in time, so a client whose clock is off would
    // otherwise show wrong countdowns/badges. serverOffsetMs corrects for that: it's added to Date.now() everywhere
    // below instead of trusting the browser clock directly.
    let serverOffsetMs = 0;

    let thresholds = {
        tolerance_minutes: 8, // Salon Flora customization (2026-08-25) - see business_settings.php
        warning_minutes: 10,
        late_start_grace_minutes: 10,
        duration_baseline: 'check_in', // 'check_in' | 'booked_start'
    };

    /**
     * Record the server clock so subsequent compute() calls can correct for local clock drift.
     *
     * @param {String} serverTime Server datetime string (Y-m-d H:i:s), as returned by the backend.
     */
    function syncServerTime(serverTime) {
        if (!serverTime) {
            return;
        }

        serverOffsetMs = moment(serverTime, 'YYYY-MM-DD HH:mm:ss').valueOf() - Date.now();
    }

    /**
     * Override the configured deviation/warning thresholds (normally passed once from vars('session_thresholds')).
     *
     * @param {Object} nextThresholds
     */
    function setThresholds(nextThresholds) {
        if (nextThresholds) {
            thresholds = {...thresholds, ...nextThresholds};
        }
    }

    /**
     * @returns {Date} The current time, corrected for server/client clock drift.
     */
    function now() {
        return new Date(Date.now() + serverOffsetMs);
    }

    /**
     * Compute the current session status of an appointment.
     *
     * @param {Object} appointment Appointment data (must include actual_start_datetime, actual_end_datetime,
     *   start_datetime and a nested service.duration in minutes).
     *
     * @returns {Object} {
     *   status: 'pending' | 'late_start' | 'running' | 'ending_soon' | 'overdue' | 'done',
     *   expectedEnd: Date|null,
     *   remainingMinutes: number|null,  // positive while running, negative once overdue
     *   actualMinutes: number|null,     // only set once the session is done
     * }
     */
    function compute(appointment) {
        // Salon Flora customization - a booking-time custom_duration_minutes override (e.g. staff extending a
        // session) must move the expected end/countdown too, exactly like it already does in effectivePricing().
        const duration = Number(appointment?.custom_duration_minutes) || Number(appointment?.service?.duration) || 0;

        if (appointment.actual_end_datetime) {
            const start = moment(appointment.actual_start_datetime, 'YYYY-MM-DD HH:mm:ss');
            const end = moment(appointment.actual_end_datetime, 'YYYY-MM-DD HH:mm:ss');

            return {
                status: 'done',
                expectedEnd: null,
                remainingMinutes: null,
                actualMinutes: end.diff(start, 'minutes'),
            };
        }

        if (!appointment.actual_start_datetime) {
            const bookedStart = moment(appointment.start_datetime, 'YYYY-MM-DD HH:mm:ss');
            const minutesPastBookedStart = moment(now()).diff(bookedStart, 'minutes');

            return {
                status: minutesPastBookedStart > thresholds.late_start_grace_minutes ? 'late_start' : 'pending',
                expectedEnd: null,
                remainingMinutes: null,
                actualMinutes: null,
            };
        }

        const checkIn = moment(appointment.actual_start_datetime, 'YYYY-MM-DD HH:mm:ss');
        const expectedEnd = checkIn.clone().add(duration, 'minutes');
        const remainingMinutes = expectedEnd.diff(moment(now()), 'minutes');

        let status = 'running';

        if (remainingMinutes < 0) {
            status = 'overdue';
        } else if (remainingMinutes <= thresholds.warning_minutes) {
            status = 'ending_soon';
        }

        return {
            status,
            expectedEnd: expectedEnd.toDate(),
            remainingMinutes,
            actualMinutes: null,
        };
    }

    /**
     * Salon Flora customization (2026-08-25) - the timestamp a session's expected duration is measured
     * from, per the 'session_duration_baseline' setting: either the real check-in time (a late start
     * must still run its full planned length) or the originally booked start time (planned end is fixed
     * regardless of when check-in actually happened). Mirrors the backend exactly - see
     * Appointments_model::compute_effective_billing().
     *
     * @param {Object} appointment
     *
     * @returns {moment.Moment}
     */
    function durationBaseline(appointment) {
        return thresholds.duration_baseline === 'booked_start'
            ? moment(appointment.start_datetime, 'YYYY-MM-DD HH:mm:ss')
            : moment(appointment.actual_start_datetime, 'YYYY-MM-DD HH:mm:ss');
    }

    /**
     * Compute what deviation (if any) a check-out right now would produce, mirroring the backend's check_out()
     * logic exactly - used to decide client-side whether to prompt for a reason before even calling the server.
     *
     * @param {Object} appointment
     *
     * @returns {Object|null} {type: 'early'|'late', expectedMinutes, actualMinutes, deltaMinutes} or null if the
     *   duration is within tolerance ("Normal", no prompt needed).
     */
    function computeDeviation(appointment) {
        const expectedMinutes = Number(appointment?.custom_duration_minutes) || Number(appointment?.service?.duration) || 0;
        const actualMinutes = moment(now()).diff(durationBaseline(appointment), 'minutes');
        const deltaMinutes = actualMinutes - expectedMinutes;
        const tolerance = thresholds.tolerance_minutes;

        let type = null;

        if (deltaMinutes < -tolerance) {
            type = 'early';
        } else if (deltaMinutes > tolerance) {
            type = 'late';
        }

        if (!type) {
            return null;
        }

        return {type, expectedMinutes, actualMinutes, deltaMinutes};
    }

    /**
     * Salon Flora customization (2026-08-25) - the amount actually owed for this session, mirroring the
     * backend's Appointments_model::compute_effective_billing() exactly:
     *
     * - Real duration within +/- tolerance_minutes of the planned duration: bill the full planned
     *   duration ("Normal").
     * - Ran over by more than tolerance: bill the real (longer) duration.
     * - Left more than tolerance early: bill the full planned duration if justified AND approved
     *   (early_exit_justification === 'justified' && early_exit_approved_by), otherwise the real
     *   (shorter) duration.
     *
     * A price_override on the appointment always wins; a custom_duration_minutes is used as the planned
     * duration in place of the service's own when there is no real check-in/check-out yet.
     *
     * @param {Object} appointment Must include a nested service.duration/price, and may include
     *   actual_start_datetime/actual_end_datetime, custom_duration_minutes, price_override,
     *   early_exit_justification, early_exit_approved_by.
     *
     * @returns {{minutes: number, price: number, hourlyRate: number}}
     */
    function effectivePricing(appointment) {
        const service = appointment?.service || {};
        const serviceDuration = Number(service.duration) || 0;
        const servicePrice = Number(service.price) || 0;
        const hourlyRate = serviceDuration > 0 ? (servicePrice / serviceDuration) * 60 : servicePrice;
        const expectedMinutes = Number(appointment.custom_duration_minutes) || serviceDuration;

        let minutes;

        if (appointment.actual_start_datetime && appointment.actual_end_datetime) {
            const end = moment(appointment.actual_end_datetime, 'YYYY-MM-DD HH:mm:ss');
            const rawMinutes = end.diff(durationBaseline(appointment), 'minutes');
            const delta = rawMinutes - expectedMinutes;
            const tolerance = thresholds.tolerance_minutes;

            if (Math.abs(delta) <= tolerance) {
                minutes = expectedMinutes;
            } else if (delta > tolerance) {
                minutes = rawMinutes;
            } else {
                const justified = appointment.early_exit_justification === 'justified' && Boolean(appointment.early_exit_approved_by);
                minutes = justified ? expectedMinutes : rawMinutes;
            }
        } else {
            minutes = expectedMinutes;
        }

        const price =
            appointment.price_override !== null && appointment.price_override !== undefined && appointment.price_override !== ''
                ? Number(appointment.price_override)
                : Math.round(hourlyRate * (minutes / 60) * 100) / 100;

        return {minutes, price, hourlyRate};
    }

    /**
     * @param {Object} appointment
     *
     * @returns {String[]} FullCalendar event className(s) reflecting the current session status.
     */
    function eventClassNames(appointment) {
        const {status} = compute(appointment);

        const classes = ['sf-session-' + status.replace(/_/g, '-')];

        if (isPaymentMissing(appointment)) {
            classes.push('sf-payment-missing');
        }

        // Salon Flora customization - marks an appointment admin/secretary force-saved despite a busy
        // therapist and/or station conflict (see Calendar.php::save_appointment(), conflict_override
        // column) - a persistent visual flag until someone resolves or reschedules it.
        if (appointment.conflict_override) {
            classes.push('sf-conflict-override');
        }

        return classes;
    }

    /**
     * Salon Flora customization - "eksik tahsilat" kalıcı görsel uyarısı: the session is over but payment hasn't
     * actually been collected. "Tahsilat yapılmadı / eksik" only closes the mandatory dialog so staff aren't
     * re-prompted every time - it's an acknowledgement, not a resolution, so it must keep showing this warning
     * (with whatever balance was recorded) exactly like the untouched 'pending' state. Only 'collected' clears it.
     * Shown as a persistent red marker on the calendar/popover/widget until someone actually collects payment.
     *
     * @param {Object} appointment
     *
     * @returns {Boolean}
     */
    function isPaymentMissing(appointment) {
        return Boolean(appointment.actual_end_datetime) && appointment.payment_status !== 'collected';
    }

    /**
     * Salon Flora customization - the time range to actually DISPLAY on the calendar for this appointment. Once a
     * session has a real check-in, the calendar block reflects the ACTUAL start/end instead of the originally
     * booked one - this is purely a display concern: start_datetime/end_datetime in the database are never
     * touched here, so the originally booked time stays intact and visible in the popover/modal ("Randevu
     * Saati") alongside the real check-in/check-out ("Seans Takibi"). Ends at the real check-out if there is one,
     * otherwise at the expected end (check-in + service duration) so an in-progress session's block still has a
     * sensible length, otherwise at the originally booked end for a session that hasn't started yet.
     *
     * @param {Object} appointment
     *
     * @returns {{start: Date, end: Date}}
     */
    function displayedTimeRange(appointment) {
        if (!appointment.actual_start_datetime) {
            return {
                start: moment(appointment.start_datetime, 'YYYY-MM-DD HH:mm:ss').toDate(),
                end: moment(appointment.end_datetime, 'YYYY-MM-DD HH:mm:ss').toDate(),
            };
        }

        const start = moment(appointment.actual_start_datetime, 'YYYY-MM-DD HH:mm:ss');

        if (appointment.actual_end_datetime) {
            return {start: start.toDate(), end: moment(appointment.actual_end_datetime, 'YYYY-MM-DD HH:mm:ss').toDate()};
        }

        const duration = Number(appointment?.custom_duration_minutes) || Number(appointment?.service?.duration) || 0;

        return {start: start.toDate(), end: start.clone().add(duration, 'minutes').toDate()};
    }

    /**
     * @param {String} status One of compute()'s status values.
     *
     * @returns {String} Human-readable Turkish label.
     */
    function statusLabel(status) {
        const labels = {
            pending: 'Başlamadı',
            late_start: 'Randevu saati geçti',
            running: 'Devam ediyor',
            ending_soon: 'Bitişe az kaldı',
            overdue: 'Süresi doldu',
            done: 'Bitti',
        };

        return labels[status] || status;
    }

    /**
     * @param {String} status
     *
     * @returns {String} Bootstrap color keyword (for badge/border classes).
     */
    function statusColor(status) {
        const colors = {
            pending: 'secondary',
            late_start: 'warning',
            running: 'success',
            ending_soon: 'warning',
            overdue: 'danger',
            done: 'secondary',
        };

        return colors[status] || 'secondary';
    }

    return {
        syncServerTime,
        setThresholds,
        now,
        compute,
        computeDeviation,
        effectivePricing,
        eventClassNames,
        statusLabel,
        statusColor,
        isPaymentMissing,
        displayedTimeRange,
    };
})();
