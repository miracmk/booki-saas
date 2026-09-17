/* ----------------------------------------------------------------------------
 * Salon Flora customization.
 *
 * "Aktif Seanslar" widget: a button injected into the calendar toolbar with a
 * live count badge, opening an offcanvas panel listing every appointment
 * that is currently checked in but not checked out - regardless of which
 * provider the calendar itself is currently filtered to. This is deliberate:
 * a provider whose session is overdue must stay visible even if the calendar
 * view is scoped to someone else.
 * ---------------------------------------------------------------------------- */
App.Components.ActiveSessionsWidget = (function () {
    const POLL_INTERVAL_MS = 60000;

    let $button;
    let $badge;
    let $offcanvasBody;

    // Salon Flora customization - "did the session finish?" prompt state. Tracked here (not in session_status.js)
    // because it's about WHEN to ask, not about the session's actual status.
    const lastPromptedAt = {};
    let promptOpenForAppointmentId = null;

    /**
     * Inject the toggle button + badge into the calendar toolbar, and the (initially empty) offcanvas panel into
     * the document body.
     */
    function buildDom() {
        $button = $('<button/>', {
            type: 'button',
            class: 'btn btn-outline-light btn-sm ms-2',
            html: [
                $('<i/>', {class: 'fas fa-stopwatch me-1'}),
                'Aktif Seanslar ',
                $('<span/>', {class: 'badge bg-secondary salonflora-active-sessions-badge', text: '0'}),
            ],
        });

        $badge = $button.find('.salonflora-active-sessions-badge');

        $button.on('click', () => {
            offcanvas().show();
            poll();
        });

        $('#calendar-actions').append($button);

        const $offcanvas = $(
            '<div class="offcanvas offcanvas-end" tabindex="-1" id="salonflora-active-sessions-offcanvas">' +
                '<div class="offcanvas-header">' +
                '<h5 class="offcanvas-title">Aktif Seanslar</h5>' +
                '<button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>' +
                '</div>' +
                '<div class="offcanvas-body"></div>' +
                '</div>',
        ).appendTo('body');

        $offcanvasBody = $offcanvas.find('.offcanvas-body');
    }

    /**
     * @returns {Object} The Bootstrap Offcanvas instance (created lazily on first use).
     */
    function offcanvas() {
        return bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('salonflora-active-sessions-offcanvas'));
    }

    /**
     * Fetch the current active sessions and re-render the badge + (if open) the panel list.
     */
    function poll() {
        if (!App.Http || !App.Http.Calendar || typeof App.Http.Calendar.getActiveSessions !== 'function') {
            return;
        }

        App.Http.Calendar.getActiveSessions().done((response) => {
            if (!response.success) {
                return;
            }

            App.Utils.SessionStatus.syncServerTime(response.server_time);

            render(response.appointments, response.unpaid_appointments || []);
            maybePromptSessionEnd(response.appointments);
        });
    }

    /**
     * Salon Flora customization - "Seans süresi doldu, çıktı mı?" prompt. Runs on EVERY page (not just the
     * calendar), since staff can be anywhere when a session's planned duration runs out. Only one prompt is shown
     * at a time; if staff dismiss it with "Hayır, henüz çıkmadı" the same appointment is re-asked after
     * SESSION_END_PROMPT_SNOOZE_MINUTES, not on every poll.
     *
     * @param {Array} appointments
     */
    function maybePromptSessionEnd(appointments) {
        if (promptOpenForAppointmentId) {
            return;
        }

        // Don't stack this on top of another mandatory dialog already on screen (e.g. the deviation-reason or
        // payment dialog from a check-out that's still in progress).
        if ($('#message-modal').hasClass('show')) {
            return;
        }

        const snoozeMs = (vars('session_thresholds')?.end_prompt_snooze_minutes || 5) * 60000;
        const now = Date.now();

        const candidate = appointments.find((appointment) => {
            if (App.Utils.SessionStatus.compute(appointment).status !== 'overdue') {
                return false;
            }

            const last = lastPromptedAt[appointment.id];

            return !last || now - last >= snoozeMs;
        });

        if (!candidate) {
            return;
        }

        showSessionEndPrompt(candidate);
    }

    /**
     * @param {Object} appointment
     */
    function showSessionEndPrompt(appointment) {
        promptOpenForAppointmentId = appointment.id;
        lastPromptedAt[appointment.id] = Date.now();

        const customerName = [appointment.customer?.first_name, appointment.customer?.last_name]
            .filter(Boolean)
            .join(' ');

        const buttons = [
            {
                text: 'Hayır, henüz çıkmadı',
                className: 'btn btn-outline-secondary',
                click: (event, modal) => {
                    modal.hide();
                    promptOpenForAppointmentId = null;
                },
            },
            {
                text: 'Evet, çıktı',
                className: 'btn btn-primary',
                click: (event, modal) => {
                    modal.hide();
                    promptOpenForAppointmentId = null;

                    App.Utils.SessionActions.checkOut(appointment.id, {
                        onUpdated: poll,
                        onError: poll,
                    });
                },
            },
        ];

        // isDismissible = false: staff must pick one of the two buttons above - no silently dismissing this and
        // forgetting about it.
        App.Utils.Message.show(
            'Seans Süresi Doldu',
            (customerName || 'Bu randevu') + ' için planlanan seans süresi doldu. Seanstan çıktı mı?',
            buttons,
            false,
        );
    }

    /**
     * @param {Array} appointments Currently in-session (checked in, not checked out) appointments.
     * @param {Array} unpaidAppointments Salon Flora customization - completed today but payment not actually
     *   collected yet (payment_status is 'pending' or 'not_collected'). This is what makes "eksik tahsilat" a
     *   KALICI (persistent) warning rather than something that disappears the moment a session ends - a session
     *   that finishes here stays visible in this list until someone actually collects payment for it ("Tahsilat
     *   yapılmadı" only closes the dialog, it doesn't resolve the warning), no matter how much later that happens.
     */
    function render(appointments, unpaidAppointments = []) {
        // Salon Flora customization - no-op on pages other than the calendar, where the widget's DOM (button +
        // badge + offcanvas) was never built.
        if (!$badge) {
            return;
        }

        const overdueCount = appointments.filter(
            (appointment) => App.Utils.SessionStatus.compute(appointment).status === 'overdue',
        ).length;

        const badgeText =
            appointments.length +
            (overdueCount ? ' · ' + overdueCount + ' dolan' : '') +
            (unpaidAppointments.length ? ' · ' + unpaidAppointments.length + ' tahsilat eksik' : '');

        $badge
            .text(badgeText)
            .removeClass('bg-secondary bg-danger')
            .addClass(overdueCount || unpaidAppointments.length ? 'bg-danger' : 'bg-secondary');

        if (!$offcanvasBody.is(':visible') && !$('#salonflora-active-sessions-offcanvas').hasClass('show')) {
            return;
        }

        $offcanvasBody.empty();

        if (unpaidAppointments.length) {
            $('<h6/>', {
                class: 'text-danger mb-2',
                html: [
                    $('<i/>', {class: 'fas fa-exclamation-triangle me-1'}),
                    document.createTextNode('Tahsilatı Eksik Seanslar (' + unpaidAppointments.length + ')'),
                ],
            }).appendTo($offcanvasBody);

            unpaidAppointments.forEach((appointment) => {
                const customerName = [appointment.customer?.first_name, appointment.customer?.last_name]
                    .filter(Boolean)
                    .join(' ');
                const providerName = [appointment.provider?.first_name, appointment.provider?.last_name]
                    .filter(Boolean)
                    .join(' ');

                $('<div/>', {
                    class: 'border border-danger rounded p-2 mb-2',
                    html: [
                        $('<div/>', {
                            html: [
                                $('<strong/>', {text: customerName || '-'}),
                                $('<br/>'),
                                $('<small/>', {class: 'text-muted', text: providerName}),
                            ],
                        }),
                        $('<button/>', {
                            type: 'button',
                            class: 'btn btn-outline-danger btn-sm mt-2 salonflora-collect-unpaid',
                            'data-appointment-id': appointment.id,
                            text: 'Tahsilat Al',
                        }).data('appointment', appointment),
                    ],
                }).appendTo($offcanvasBody);
            });

            $('<hr/>').appendTo($offcanvasBody);
        }

        if (!appointments.length) {
            if (!unpaidAppointments.length) {
                $offcanvasBody.html('<p class="text-muted">Şu anda devam eden bir seans yok.</p>');
            }
            return;
        }

        appointments.forEach((appointment) => {
            const {status, remainingMinutes} = App.Utils.SessionStatus.compute(appointment);
            const label = App.Utils.SessionStatus.statusLabel(status);
            const color = App.Utils.SessionStatus.statusColor(status);

            const customerName = [appointment.customer?.first_name, appointment.customer?.last_name]
                .filter(Boolean)
                .join(' ');
            const providerName = [appointment.provider?.first_name, appointment.provider?.last_name]
                .filter(Boolean)
                .join(' ');

            let detail = '';

            if (status === 'running' || status === 'ending_soon') {
                detail = 'Kalan ' + remainingMinutes + ' dk';
            } else if (status === 'overdue') {
                detail = Math.abs(remainingMinutes) + ' dk aştı';
            }

            const $item = $('<div/>', {class: 'border rounded p-2 mb-2'});

            $('<div/>', {class: 'd-flex justify-content-between align-items-start'}).append(
                $('<div/>', {
                    html: [
                        $('<strong/>', {text: customerName || '-'}),
                        $('<br/>'),
                        $('<small/>', {class: 'text-muted', text: providerName}),
                        appointment.station ? $('<br/>') : null,
                        appointment.station
                            ? $('<small/>', {class: 'text-muted', text: appointment.station.name})
                            : null,
                    ],
                }),
                $('<span/>', {class: 'badge bg-' + color, text: label}),
            ).appendTo($item);

            if (detail) {
                $('<div/>', {class: 'small text-muted mt-1', text: detail}).appendTo($item);
            }

            if (status !== 'done') {
                $('<button/>', {
                    type: 'button',
                    class: 'btn btn-outline-secondary btn-sm mt-2 salonflora-checkout-popover',
                    'data-appointment-id': appointment.id,
                    text: 'Seansı Bitir',
                }).appendTo($item);
            }

            $item.appendTo($offcanvasBody);
        });
    }

    /**
     * Initialize the widget. The polling (badge count + the "did the session finish?" prompt) runs on EVERY
     * backend page, since staff can be anywhere when a session's planned duration runs out. The widget's own
     * button/offcanvas DOM is only injected on the calendar page (there's no #calendar-actions toolbar
     * elsewhere to attach it to) - render() already no-ops gracefully when $offcanvasBody doesn't exist.
     */
    function initialize() {
        if ($('#calendar-page').length) {
            buildDom();

            // Reuse the same "Seansı Bitir" delegate behavior as the popover (same class name, same handler
            // contract), but re-poll the widget afterwards instead of touching a FullCalendar event.
            $('body').on('click', '#salonflora-active-sessions-offcanvas .salonflora-checkout-popover', (event) => {
                const appointmentId = $(event.currentTarget).data('appointment-id');
                $(event.currentTarget).prop('disabled', true);
                App.Utils.SessionActions.checkOut(appointmentId, {
                    onUpdated: poll,
                    onError: () => $(event.currentTarget).prop('disabled', false),
                });
            });

            $('body').on('click', '#salonflora-active-sessions-offcanvas .salonflora-collect-unpaid', (event) => {
                const appointmentId = $(event.currentTarget).data('appointment-id');
                const appointment = $(event.currentTarget).data('appointment') || {id: appointmentId};
                $(event.currentTarget).prop('disabled', true);
                App.Utils.SessionActions.collectPayment(appointmentId, appointment, {
                    onUpdated: poll,
                    onError: () => $(event.currentTarget).prop('disabled', false),
                });
            });
        }

        poll();
        setInterval(poll, POLL_INTERVAL_MS);
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
