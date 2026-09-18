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
 * Calendar event popover utility.
 *
 * This module implements the functionality of calendar event popovers,
 * providing shared UI builders for appointment, unavailability, working plan
 * exception, and blocked period popovers.
 */
App.Utils.CalendarEventPopover = (function () {
    const moment = window.moment;

    // Icon Rendering Functions

    /**
     * Render a map icon that links to Google maps.
     *
     * @param {Object} user - Should have the address, city, etc properties.
     * @returns {string|null} The rendered HTML or null if no address data.
     */
    function renderMapIcon(user) {
        const data = [user.address, user.city, user.state, user.zip_code].filter(Boolean);
        if (!data.length) {
            return null;
        }
        return $('<div/>', {
            html: [
                $('<a/>', {
                    href: 'https://google.com/maps/place/' + data.join(','),
                    target: '_blank',
                    html: [$('<span/>', {class: 'fas fa-map-marker-alt'})],
                }),
            ],
        }).html();
    }

    /**
     * Render a mail icon.
     *
     * @param {string} email - Email address.
     * @returns {string|null} The rendered HTML or null if no email.
     */
    function renderMailIcon(email) {
        if (!email) {
            return null;
        }
        return $('<div/>', {
            html: [
                $('<a/>', {
                    href: 'mailto:' + email,
                    target: '_blank',
                    html: [$('<span/>', {class: 'fas fa-envelope'})],
                }),
            ],
        }).html();
    }

    /**
     * Render a phone icon.
     *
     * @param {string} phone - Phone number.
     * @returns {string|null} The rendered HTML or null if no phone.
     */
    function renderPhoneIcon(phone) {
        if (!phone) {
            return null;
        }
        return $('<div/>', {
            html: [
                $('<a/>', {
                    href: 'tel:' + phone,
                    target: '_blank',
                    html: [$('<span/>', {class: 'fas fa-phone-alt'})],
                }),
            ],
        }).html();
    }

    /**
     * Render custom content into the popover of events.
     *
     * @param {Object} info - The info object as passed from FullCalendar.
     * @returns {Object|string|null} Return HTML string, a jQuery selector or null for nothing.
     */
    function renderCustomContent(info) {
        return null; // Default behavior - can be overridden
    }

    // Helper Functions

    /**
     * Format a datetime string for display.
     *
     * @param {Date|string} datetime - The datetime to format.
     * @returns {string} Formatted datetime string.
     */
    function formatDateTime(datetime) {
        return App.Utils.Date.format(
            moment(datetime).format('YYYY-MM-DD HH:mm:ss'),
            vars('date_format'),
            vars('time_format'),
            true,
        );
    }

    /**
     * Get truncated notes for popover display.
     *
     * @param {Object} event - Calendar event object.
     * @returns {string} Notes text (truncated to 100 chars) or '-'.
     */
    function getEventNotes(event) {
        const notes = event.extendedProps?.data?.notes;
        if (!notes) {
            return '-';
        }

        return notes.length > 100 ? notes.substring(0, 100) + '...' : notes;
    }

    // Popover UI Element Builders

    /**
     * Create a popover button element.
     *
     * @param {string} className - CSS class names.
     * @param {string} iconClass - Font Awesome icon class.
     * @param {string} labelKey - Language key for button text.
     * @returns {jQuery} Button element.
     */
    function createPopoverButton(className, iconClass, labelKey) {
        return $('<button/>', {
            class: className,
            html: [$('<i/>', {class: iconClass + ' me-2'}), $('<span/>', {text: lang(labelKey)})],
        });
    }

    /**
     * Create the standard popover action buttons.
     *
     * @param {string} displayEdit - CSS class to show/hide edit button.
     * @param {string} displayDelete - CSS class to show/hide delete button.
     * @returns {jQuery} Button container element.
     */
    function createPopoverButtons(displayEdit, displayDelete) {
        return $('<div/>', {
            class: 'd-flex justify-content-center flex-wrap gap-2',
            html: [
                createPopoverButton('close-popover btn btn-outline-secondary', 'fas fa-ban', 'close'),
                createPopoverButton(
                    'delete-popover btn btn-outline-secondary ' + displayDelete,
                    'fas fa-trash-alt',
                    'delete',
                ),
                createPopoverButton('edit-popover btn btn-primary ' + displayEdit, 'fas fa-edit', 'edit'),
            ],
        });
    }

    /**
     * Salon Flora customization - same as createPopoverButtons(), plus a "Bildirim Gönder" button that lets
     * staff manually (re)send an appointment notification (customer / provider / admin ticks) without opening
     * the edit modal. Appointment-only - gated behind the same displayEdit permission as the edit button.
     *
     * @param {string} appointmentId - The appointment's id (data-appointment-id for the click handler).
     * @param {string} displayEdit - CSS class to show/hide edit and notify buttons.
     * @param {string} displayDelete - CSS class to show/hide delete button.
     * @returns {jQuery} Button container element.
     */
    function createAppointmentPopoverButtons(appointmentId, displayEdit, displayDelete) {
        return $('<div/>', {
            class: 'd-flex justify-content-center flex-wrap gap-2',
            html: [
                createPopoverButton('close-popover btn btn-outline-secondary', 'fas fa-ban', 'close'),
                createPopoverButton(
                    'delete-popover btn btn-outline-secondary ' + displayDelete,
                    'fas fa-trash-alt',
                    'delete',
                ),
                createPopoverButton(
                    'notify-popover btn btn-outline-secondary ' + displayEdit,
                    'fas fa-bell',
                    'send_notification',
                ).attr('data-appointment-id', appointmentId),
                createPopoverButton(
                    'add-note-popover btn btn-outline-secondary ' + displayEdit,
                    'fas fa-sticky-note',
                    'add_note',
                ).attr('data-appointment-id', appointmentId),
                $('<a/>', {
                    class: 'btn btn-outline-success btn-sm ' + displayEdit,
                    href: App.Utils.Url.siteUrl('adisyons/create_for_appointment/' + appointmentId),
                    html: [$('<i class="fas fa-receipt me-1"></i>'), $('<span>Adisyon / Kasa</span>')],
                }),
                createPopoverButton('edit-popover btn btn-primary ' + displayEdit, 'fas fa-edit', 'edit'),
            ],
        });
    }

    /**
     * Create a labeled text row for popover content.
     *
     * @param {string} labelKey - Language key for label.
     * @param {string} text - Text content.
     * @returns {Array<jQuery>} Array of jQuery elements.
     */
    function createPopoverRow(labelKey, text) {
        return [
            $('<strong/>', {class: 'd-inline-block me-2', text: lang(labelKey)}),
            $('<span/>', {text: text}),
            $('<br/>'),
        ];
    }

    // Popover Content Builders

    /**
     * Build popover content for unavailability events.
     *
     * @param {Object} info - FullCalendar event info.
     * @param {string} displayEdit - CSS class for edit visibility.
     * @param {string} displayDelete - CSS class for delete visibility.
     * @returns {jQuery} Popover content element.
     */
    function buildUnavailabilityPopover(info, displayEdit, displayDelete) {
        const data = info.event.extendedProps.data;
        const provider = data.provider;
        let startDateTime = info.event.start;
        let endDateTime = info.event.end || info.event.start;

        if (data.start_datetime) {
            startDateTime = new Date(data.start_datetime);
            endDateTime = new Date(data.end_datetime);
        }
        return $('<div/>', {
            html: [
                ...createPopoverRow('provider', provider.first_name + ' ' + provider.last_name),
                ...createPopoverRow('start', formatDateTime(startDateTime)),
                ...createPopoverRow('end', formatDateTime(endDateTime)),
                ...createPopoverRow('notes', getEventNotes(info.event)),
                renderCustomContent(info),
                $('<hr/>'),
                createPopoverButtons(displayEdit, displayDelete),
            ],
        });
    }

    /**
     * Build popover content for working plan exception events.
     *
     * @param {Object} info - FullCalendar event info.
     * @param {string} displayEdit - CSS class for edit visibility.
     * @param {string} displayDelete - CSS class for delete visibility.
     * @returns {jQuery} Popover content element.
     */
    function buildWorkingPlanExceptionPopover(info, displayEdit, displayDelete) {
        const data = info.event.extendedProps.data;
        const date = moment(info.event.start).format('YYYY-MM-DD');
        const workingPlanException = data.workingPlanException;
        const provider = data.provider;
        const startTime = workingPlanException?.startTime;
        const endTime = workingPlanException?.endTime;

        const formatTimeOrDash = function (time) {
            if (!time) {
                return '-';
            }
            return App.Utils.Date.format(date + ' ' + time, vars('date_format'), vars('time_format'), true);
        };

        const isNonWorking = !startTime;

        return $('<div/>', {
            html: [
                ...createPopoverRow('provider', provider.first_name + ' ' + provider.last_name),
                ...createPopoverRow('start', formatTimeOrDash(startTime)),
                ...createPopoverRow('end', formatTimeOrDash(endTime)),
                ...createPopoverRow('timezone', startTime ? vars('timezones')[provider.timezone] : '-'),
                isNonWorking ? $('<p/>', {class: 'mt-2 mb-0 text-muted', text: lang('make_non_working_day')}) : null,
                renderCustomContent(info),
                $('<hr/>'),
                createPopoverButtons(displayEdit, displayDelete),
            ],
        });
    }

    /**
     * Salon Flora customization - build the session-tracking block: a status badge (with remaining/overrun
     * minutes) plus a quick check-in/check-out button, so staff can operate on a session directly from the
     * popover without opening the full edit modal. No button is shown once the session is done (use the modal's
     * "Sıfırla" for corrections), or when the viewer lacks edit permission.
     *
     * @param {Object} data - Appointment data (info.event.extendedProps.data).
     * @param {string} displayEdit - Same flag buildAppointmentPopover() receives; '' if editable, 'd-none' if not.
     * @returns {jQuery} Session block element.
     */
    function buildSessionBlock(data, displayEdit) {
        const {status, remainingMinutes, actualMinutes} = App.Utils.SessionStatus.compute(data);
        const label = App.Utils.SessionStatus.statusLabel(status);
        const color = App.Utils.SessionStatus.statusColor(status);

        let detail = '';

        if (status === 'running' || status === 'ending_soon') {
            detail = ' · Kalan ' + remainingMinutes + ' dk';
        } else if (status === 'overdue') {
            detail = ' · ' + Math.abs(remainingMinutes) + ' dk aştı';
        } else if (status === 'done' && actualMinutes !== null) {
            detail = ' · Fiili ' + actualMinutes + ' dk';
        }

        const canEdit = displayEdit !== 'd-none';

        let button = null;

        if (canEdit && (status === 'pending' || status === 'late_start')) {
            button = $('<button/>', {
                type: 'button',
                class: 'salonflora-checkin-popover btn btn-outline-success btn-sm ms-2',
                'data-appointment-id': data.id,
                text: 'Seansı Başlat',
            });
        } else if (canEdit && (status === 'running' || status === 'ending_soon' || status === 'overdue')) {
            button = $('<button/>', {
                type: 'button',
                class: 'salonflora-checkout-popover btn btn-outline-secondary btn-sm ms-2',
                'data-appointment-id': data.id,
                text: 'Seansı Bitir',
            });
        }

        // Salon Flora customization - persistent "eksik tahsilat" warning, only meaningful (and only ever shown)
        // to admins/secretaries - providers never see or touch payment data.
        const paymentMissing = canEdit && vars('can_manage_payment') && App.Utils.SessionStatus.isPaymentMissing(data);

        const paymentBadge = paymentMissing
            ? $('<span/>', {class: 'badge bg-danger ms-1', text: 'TAHSİLAT EKSİK'})
            : null;

        const collectButton = paymentMissing
            ? $('<button/>', {
                  type: 'button',
                  class: 'salonflora-collect-popover btn btn-outline-danger btn-sm ms-2',
                  'data-appointment-id': data.id,
                  text: 'Tahsilat Al',
              })
            : null;

        return $('<div/>', {
            class: 'mb-2',
            html: [
                $('<span/>', {class: 'badge bg-' + color, text: label}),
                detail ? $('<small/>', {class: 'text-muted ms-1', text: detail}) : null,
                button,
                paymentBadge,
                collectButton,
            ],
        });
    }

    /**
     * Salon Flora customization (2026-08-25) - shows the recorded early-exit classification (if any) and,
     * for admins/secretaries, a control to approve/overturn it - covers both a therapist's own pending
     * "haklı" claim (early_exit_approved_by still null) and retroactively (re)classifying an older
     * completed session that predates this feature (has actual times but no classification yet).
     * Provider-only viewers see the read-only status, never the review control.
     *
     * @param {Object} data - Appointment data (info.event.extendedProps.data).
     * @returns {jQuery|null} Element, or null if there's nothing to show (session not finished yet).
     */
    function buildEarlyExitReviewBlock(data) {
        if (!data.actual_end_datetime) {
            return null;
        }

        const canReview = Boolean(vars('can_manage_payment'));
        const reasonCodes = vars('early_exit_reason_codes') || {};

        const statusRow = data.early_exit_justification
            ? $('<div/>', {
                  class: 'mb-1',
                  html: [
                      $('<span/>', {
                          class: 'badge ' + (data.early_exit_justification === 'justified' ? 'bg-success' : 'bg-danger'),
                          text: data.early_exit_justification === 'justified' ? 'Haklı Erken Çıkış' : 'Haksız Erken Çıkış',
                      }),
                      $('<small/>', {
                          class: 'text-muted ms-1',
                          text:
                              (reasonCodes[data.early_exit_reason_code] || data.early_exit_reason_code || '') +
                              (data.early_exit_approved_by ? '' : ' · Onay bekliyor'),
                      }),
                  ],
              })
            : null;

        if (!canReview) {
            return statusRow;
        }

        const $select = $('<select/>', {class: 'form-select form-select-sm d-inline-block w-auto me-1'});
        $('<option/>', {value: '', text: 'Sebep...'}).appendTo($select);
        Object.keys(reasonCodes).forEach((code) => {
            $('<option/>', {
                value: code,
                text: reasonCodes[code],
                selected: code === data.early_exit_reason_code,
            }).appendTo($select);
        });

        const $justifiedBtn = $('<button/>', {
            type: 'button',
            class: 'btn btn-outline-success btn-sm me-1',
            text: 'Haklı',
        });
        const $unjustifiedBtn = $('<button/>', {
            type: 'button',
            class: 'btn btn-outline-danger btn-sm',
            text: 'Haksız',
        });

        const submitReview = (justification) => {
            const reasonCode = $select.val();

            if (!reasonCode) {
                $select.addClass('is-invalid').trigger('focus');
                return;
            }

            App.Http.Calendar.reviewEarlyExit(data.id, justification, reasonCode).done(() => {
                App.Layouts.Backend.displayNotification('Sınıflandırma kaydedildi.');
                $('.close-popover').trigger('click');
                reloadAppointmentsTrigger();
            });
        };

        $justifiedBtn.on('click', () => submitReview('justified'));
        $unjustifiedBtn.on('click', () => submitReview('unjustified'));

        return $('<div/>', {
            class: 'mb-2',
            html: [statusRow, $('<div/>', {class: 'mt-1', html: [$select, $justifiedBtn, $unjustifiedBtn]})],
        });
    }

    /**
     * Salon Flora customization - reloads the calendar's appointment list, used after an in-popover action
     * (like an early-exit review) changes data the calendar is already displaying.
     */
    function reloadAppointmentsTrigger() {
        $('#reload-appointments').trigger('click');
    }

    /**
     * Build popover content for appointment events.
     *
     * @param {Object} info - FullCalendar event info.
     * @param {string} displayEdit - CSS class for edit visibility.
     * @param {string} displayDelete - CSS class for delete visibility.
     * @returns {jQuery} Popover content element.
     */
    function buildAppointmentPopover(info, displayEdit, displayDelete) {
        const data = info.event.extendedProps.data;
        const customer = data.customer;
        const provider = data.provider;
        const customerName = [customer.first_name, customer.last_name].filter(Boolean).join(' ') || '-';
        const station = data.id_stations
            ? (vars('stations') || []).find((s) => Number(s.value) === Number(data.id_stations))
            : null;
        const meetingLinkElements = data.meeting_link
            ? [
                  $('<strong/>', {class: 'd-inline-block me-2', text: lang('meeting_link')}),
                  $('<a/>', {href: data.meeting_link, target: '_blank', text: data.meeting_link}),
                  $('<br/>'),
              ]
            : [];
        // Salon Flora customization - the calendar block itself moves to show the REAL check-in/check-out time
        // once there is one (see displayedTimeRange() in session_status.js - info.event.start/end reflect that),
        // so the originally booked time is shown here separately ("Randevu Saati") from data.start_datetime/
        // end_datetime directly (never changed by check-in/out), with the real times added as their own rows
        // whenever they differ from the booking.
        const bookingStart = formatDateTime(data.start_datetime);
        const bookingEnd = formatDateTime(data.end_datetime);

        const realTimeRows = [];

        if (data.actual_start_datetime) {
            realTimeRows.push(...createPopoverRow('real_start', formatDateTime(data.actual_start_datetime)));
        }

        if (data.actual_end_datetime) {
            realTimeRows.push(...createPopoverRow('real_end', formatDateTime(data.actual_end_datetime)));
        }

        // Salon Flora customization - conflict_override warning banner (see Calendar.php::save_appointment(),
        // sf-conflict-override class on the calendar event itself).
        const conflictBanner = data.conflict_override
            ? [
                  $('<div/>', {
                      class: 'alert alert-warning py-2 px-2 mb-2',
                      html: [
                          $('<i/>', {class: 'fas fa-exclamation-triangle me-1'}),
                          $('<span/>', {
                              text:
                                  (data.conflict_override.includes('provider') && data.conflict_override.includes('station')
                                      ? 'Terapist ve oda çakışmasına rağmen kaydedildi'
                                      : data.conflict_override.includes('provider')
                                        ? 'Terapist çakışmasına rağmen kaydedildi'
                                        : 'Oda çakışmasına rağmen kaydedildi') +
                                  (data.conflict_override_at ? ' (' + formatDateTime(data.conflict_override_at) + ')' : ''),
                          }),
                      ],
                  }),
              ]
            : [];

        return $('<div/>', {
            html: [
                ...conflictBanner,
                ...createPopoverRow('start', bookingStart),
                ...createPopoverRow('end', bookingEnd),
                ...realTimeRows,
                ...createPopoverRow('timezone', vars('timezones')[provider.timezone]),
                ...createPopoverRow('status', data.status || '-'),
                buildSessionBlock(data, displayEdit),
                buildEarlyExitReviewBlock(data),
                ...createPopoverRow('station', station ? station.label : 'Atanmamış'),
                ...createPopoverRow('service', data.service.name),
                $('<strong/>', {class: 'd-inline-block me-2', text: lang('provider')}),
                renderMapIcon(provider),
                $('<span/>', {text: provider.first_name + ' ' + provider.last_name}),
                $('<br/>'),
                $('<strong/>', {class: 'd-inline-block me-2', text: lang('customer')}),
                renderMapIcon(customer),
                $('<span/>', {class: 'd-inline-block fw-semibold', text: customerName}),
                customer.id ? $('<button/>', {
                    type: 'button',
                    class: 'btn btn-link btn-sm p-0 ms-2 text-decoration-none',
                    title: 'Müşteri 360°',
                    click: () => {
                        if (window.openCustomer360) {
                            window.openCustomer360(customer.id);
                        }
                    },
                    html: [$('<i class="fas fa-id-card text-primary me-1"></i>'), $('<small class="text-primary fw-bold">360°</small>')],
                }) : null,
                $('<br/>'),
                $('<strong/>', {class: 'd-inline-block me-2', text: lang('email')}),
                renderMailIcon(customer.email),
                $('<span/>', {class: 'd-inline-block', text: customer.email || '-'}),
                $('<br/>'),
                $('<strong/>', {class: 'd-inline-block me-2', text: lang('phone')}),
                renderPhoneIcon(customer.phone_number),
                $('<span/>', {class: 'd-inline-block', text: customer.phone_number || '-'}),
                $('<br/>'),
                ...meetingLinkElements,
                ...createPopoverRow('notes', getEventNotes(info.event)),
                renderCustomContent(info),
                $('<hr/>'),
                createAppointmentPopoverButtons(data.id, displayEdit, displayDelete),
            ],
        });
    }

    /**
     * Build popover content for blocked period events.
     *
     * @param {Object} info - FullCalendar event info.
     * @returns {jQuery} Popover content element.
     */
    function buildBlockedPeriodPopover(info) {
        const data = info.event.extendedProps.data;

        return $('<div/>', {
            html: [
                ...createPopoverRow('name', data.name || '-'),
                ...createPopoverRow('start', formatDateTime(info.event.start)),
                ...createPopoverRow('end', formatDateTime(info.event.end)),
                ...createPopoverRow('notes', data.notes || '-'),
                $('<hr/>'),
                $('<div/>', {
                    class: 'd-flex justify-content-center',
                    html: [createPopoverButton('close-popover btn btn-outline-secondary', 'fas fa-ban', 'close')],
                }),
            ],
        });
    }

    // Public API

    return {
        renderPhoneIcon,
        renderMapIcon,
        renderMailIcon,
        renderCustomContent,
        formatDateTime,
        getEventNotes,
        createPopoverButton,
        createPopoverButtons,
        createPopoverRow,
        buildUnavailabilityPopover,
        buildWorkingPlanExceptionPopover,
        buildAppointmentPopover,
        buildBlockedPeriodPopover,
    };
})();
