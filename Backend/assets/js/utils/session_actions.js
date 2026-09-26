/* ----------------------------------------------------------------------------
 * Salon Flora customization.
 *
 * Shared check-in / check-out action handlers, used by both calendar views
 * (default + table), the event popover and the appointment modal, so the
 * deviation-reason dialog behaves identically no matter where check-out was
 * triggered from.
 * ---------------------------------------------------------------------------- */
App.Utils.SessionActions = (function () {
    /**
     * Start a session (check-in).
     *
     * @param {Number} appointmentId
     * @param {Object} callbacks {onUpdated(appointment), onError()}
     */
    function checkIn(appointmentId, callbacks = {}) {
        return App.Http.Calendar.checkIn(appointmentId)
            .done((response) => {
                if (!response.success) {
                    App.Layouts.Backend.displayNotification(response.message || 'İşlem başarısız.');
                    callbacks.onError?.();
                    return;
                }

                App.Layouts.Backend.displayNotification('Seans başlatıldı.');
                callbacks.onUpdated?.(response.appointment);
            })
            .fail(() => {
                App.Layouts.Backend.displayNotification('Seans başlatılamadı.');
                callbacks.onError?.();
            });
    }

    /**
     * Salon Flora customization - has payment already been recorded for this appointment (either collected or
     * explicitly marked as not collected)? "pending" is the only "not yet decided" state.
     *
     * @param {Object} appointment
     *
     * @returns {Boolean}
     */
    function hasPayment(appointment) {
        return Boolean(appointment?.payment_status) && appointment.payment_status !== 'pending';
    }

    /**
     * Finish a session (check-out). If the real duration deviates from the service's planned duration beyond the
     * configured thresholds, the server rejects the request and this shows a dialog asking staff for a reason,
     * then resubmits with it.
     *
     * @param {Number} appointmentId
     * @param {Object} callbacks {onUpdated(appointment), onError()}
     */
    function checkOut(appointmentId, callbacks = {}) {
        performCheckOut(appointmentId, null, null, callbacks);
    }

    /**
     * Salon Flora customization - let staff record payment at any point (typically at check-in, before the
     * session even starts) rather than only at the mandatory check-out prompt. Not mandatory here - a "Sonra"
     * (later) option is offered, since forcing this the moment a customer walks in creates exactly the kind of
     * "answer whatever to make it go away" fatigue that produces bad data.
     *
     * @param {Number} appointmentId
     * @param {Object} appointment Current appointment data (for the default amount / existing payment display).
     * @param {Object} callbacks {onUpdated(appointment), onError()}
     */
    function collectPayment(appointmentId, appointment, callbacks = {}) {
        showPaymentDialog(appointmentId, appointment, callbacks, {mandatory: false});
    }

    function performCheckOut(appointmentId, reason, earlyExit, callbacks) {
        App.Http.Calendar.checkOut(appointmentId, reason, earlyExit)
            .done((response) => {
                if (response.success) {
                    App.Layouts.Backend.displayNotification(
                        reason ? 'Seans kaydedildi.' : 'Seans tamamlandı.',
                    );

                    // Salon Flora customization - a session isn't fully "done" from a bookkeeping standpoint
                    // until payment is recorded (or explicitly marked as not collected). Only admins/secretaries
                    // are ever asked - providers never see or touch payment data. If payment was already
                    // recorded earlier (e.g. at check-in, via collectPayment()), don't ask a second time.
                    if (vars('can_manage_payment') && !hasPayment(response.appointment)) {
                        showPaymentDialog(appointmentId, response.appointment, callbacks, {mandatory: true});
                    } else {
                        callbacks.onUpdated?.(response.appointment);
                    }

                    return;
                }

                if (response.requires_reason) {
                    showDeviationDialog(appointmentId, response.deviation, callbacks);
                    return;
                }

                App.Layouts.Backend.displayNotification(response.message || 'İşlem başarısız.');
                callbacks.onError?.();
            })
            .fail(() => {
                App.Layouts.Backend.displayNotification('Seans bitirilemedi.');
                callbacks.onError?.();
            });
    }

    /**
     * Salon Flora customization - mandatory payment-collection dialog shown right after check-out (to
     * admins/secretaries only). Staff must record either that payment was collected (method + amount +
     * invoiced flag) or explicitly acknowledge it wasn't (with an optional remaining balance) - the dialog has no
     * close button and can't be dismissed with Escape or a backdrop click, so a session never silently ends up
     * with unknown payment status.
     */
    function showPaymentDialog(appointmentId, appointment, callbacks, {mandatory = true} = {}) {
        // Salon Flora customization - prefill with the EFFECTIVE price (real check-in/check-out duration, rounded
        // down to the nearest half hour, times the service's hourly rate - or a price_override if one was set at
        // booking) rather than the flat service.price, so what staff see here matches what should actually be
        // collected.
        const defaultAmount = App.Utils.SessionStatus.effectivePricing(appointment).price || '';
        const paymentMethods = vars('payment_methods') || [];

        const buttons = [];

        if (!mandatory) {
            // Salon Flora customization - "collect now" (e.g. at check-in) is optional: staff can defer to the
            // mandatory check-out prompt instead of being forced through this every time.
            buttons.push({
                text: 'Sonra',
                className: 'btn btn-outline-secondary',
                click: (event, modal) => modal.hide(),
            });
        }

        buttons.push(
            {
                text: 'Kaydet',
                click: (event, modal) => {
                    const collected = $('input[name="sf-payment-collected"]:checked').val() === 'yes';

                    if (collected) {
                        const method = $('#sf-payment-method').val();

                        if (!method) {
                            $('#sf-payment-method').addClass('is-invalid');
                            return;
                        }

                        const amount = $('#sf-payment-amount').val();
                        const invoiced = $('#sf-payment-invoiced').is(':checked');

                        modal.hide();
                        submitPayment(
                            appointmentId,
                            {
                                payment_status: 'collected',
                                payment_method: method,
                                payment_amount: amount !== '' ? amount : null,
                                payment_balance_amount: null,
                                is_invoiced: invoiced,
                            },
                            callbacks,
                        );
                        return;
                    }

                    const balance = $('#sf-payment-balance').val();

                    modal.hide();
                    submitPayment(
                        appointmentId,
                        {
                            payment_status: 'not_collected',
                            payment_method: null,
                            payment_amount: null,
                            payment_balance_amount: balance !== '' ? balance : null,
                            is_invoiced: false,
                        },
                        callbacks,
                    );
                },
            },
        );

        // isDismissible = false: no close (X) button, Escape does nothing, backdrop click does nothing - this
        // dialog only closes via the "Kaydet" button above.
        // isDismissible follows `mandatory`: the check-out prompt can't be dismissed except via its own buttons;
        // the optional check-in-time prompt can be closed normally (X / Escape / backdrop), on top of its "Sonra"
        // button.
        //
        // BUG FIX: App.Utils.Message.show() silently no-ops (`if (!title || !message) return null;`) when message
        // is an empty string - '' is falsy in JS. Passing '' here meant the dialog NEVER opened (the modal's
        // '#message-modal .modal-body' selector below still found the PREVIOUS modal instance left over in the
        // DOM and appended the payment form fields into it, but nothing ever called .show() on it) - this was
        // reported as "tahsilat al yapamıyorum". A non-empty placeholder is filled in immediately after by the
        // radio buttons appended below.
        App.Utils.Message.show('Tahsilat Bilgisi', 'Lütfen tahsilat durumunu belirtin:', buttons, !mandatory);

        const $body = $('#message-modal .modal-body');

        $('<div/>', {
            class: 'form-check mb-2',
            html: [
                $('<input/>', {
                    type: 'radio',
                    class: 'form-check-input',
                    name: 'sf-payment-collected',
                    id: 'sf-payment-collected-yes',
                    value: 'yes',
                    checked: true,
                }),
                $('<label/>', {class: 'form-check-label', for: 'sf-payment-collected-yes', text: 'Tahsilat yapıldı'}),
            ],
        }).appendTo($body);

        $('<div/>', {
            class: 'form-check mb-3',
            html: [
                $('<input/>', {
                    type: 'radio',
                    class: 'form-check-input',
                    name: 'sf-payment-collected',
                    id: 'sf-payment-collected-no',
                    value: 'no',
                }),
                $('<label/>', {
                    class: 'form-check-label',
                    for: 'sf-payment-collected-no',
                    text: 'Tahsilat yapılmadı / eksik',
                }),
            ],
        }).appendTo($body);

        const $collectedFields = $('<div/>', {id: 'sf-payment-collected-fields'}).appendTo($body);

        $('<label/>', {class: 'form-label', for: 'sf-payment-method', text: 'Ödeme Yöntemi'}).appendTo(
            $collectedFields,
        );

        const $methodSelect = $('<select/>', {id: 'sf-payment-method', class: 'form-select mb-2'})
            .append($('<option/>', {value: '', text: 'Seçiniz...'}))
            .append(paymentMethods.map((method) => $('<option/>', {value: method.value, text: method.label})))
            .appendTo($collectedFields);

        $methodSelect.on('change', () => $methodSelect.removeClass('is-invalid'));

        $('<label/>', {class: 'form-label', for: 'sf-payment-amount', text: 'Tutar (TRY)'}).appendTo(
            $collectedFields,
        );

        $('<input/>', {
            type: 'number',
            step: '0.01',
            min: '0',
            id: 'sf-payment-amount',
            class: 'form-control mb-2',
            value: defaultAmount,
        }).appendTo($collectedFields);

        $('<div/>', {
            class: 'form-check',
            html: [
                $('<input/>', {type: 'checkbox', class: 'form-check-input', id: 'sf-payment-invoiced'}),
                $('<label/>', {class: 'form-check-label', for: 'sf-payment-invoiced', text: 'Faturalandırıldı'}),
            ],
        }).appendTo($collectedFields);

        const $notCollectedFields = $('<div/>', {id: 'sf-payment-not-collected-fields', class: 'd-none'}).appendTo(
            $body,
        );

        $('<div/>', {
            class: 'alert alert-warning',
            text: 'Dikkat: bu seans için tahsilat yapılmadı olarak kaydedilecek. Müşteride bakiye kalmış olabilir, mutlaka takip edin.',
        }).appendTo($notCollectedFields);

        $('<label/>', {class: 'form-label', for: 'sf-payment-balance', text: 'Kalan Bakiye (TRY, varsa)'}).appendTo(
            $notCollectedFields,
        );

        $('<input/>', {
            type: 'number',
            step: '0.01',
            min: '0',
            id: 'sf-payment-balance',
            class: 'form-control',
        }).appendTo($notCollectedFields);

        $body.find('input[name="sf-payment-collected"]').on('change', () => {
            const collected = $('input[name="sf-payment-collected"]:checked').val() === 'yes';
            $collectedFields.toggleClass('d-none', !collected);
            $notCollectedFields.toggleClass('d-none', collected);
        });
    }

    /**
     * @param {Number} appointmentId
     * @param {Object} paymentData {payment_status, payment_method, payment_amount, payment_balance_amount, is_invoiced}
     * @param {Object} callbacks
     */
    function submitPayment(appointmentId, paymentData, callbacks) {
        App.Http.Calendar.updatePayment(appointmentId, paymentData)
            .done((response) => {
                if (!response.success) {
                    App.Layouts.Backend.displayNotification(response.message || 'Tahsilat bilgisi kaydedilemedi.');
                    callbacks.onError?.();
                    return;
                }

                App.Layouts.Backend.displayNotification(
                    paymentData.payment_status === 'collected'
                        ? 'Tahsilat kaydedildi.'
                        : 'Tahsilat "yapılmadı" olarak işaretlendi.',
                );
                callbacks.onUpdated?.(response.appointment);
            })
            .fail(() => {
                App.Layouts.Backend.displayNotification('Tahsilat bilgisi kaydedilemedi.');
                callbacks.onError?.();
            });
    }

    /**
     * Show the "why did this session run short/long" dialog. Staff must pick a preset reason or write their own -
     * cancelling leaves the session open (no actual_end_datetime is written) so it keeps showing as overdue on the
     * calendar until someone actually finishes it with a reason.
     */
    function showDeviationDialog(appointmentId, deviation, callbacks) {
        const isEarly = deviation.type === 'early';

        const diffLabel = isEarly
            ? Math.abs(deviation.delta_minutes) + ' dk KISA'
            : deviation.delta_minutes + ' dk UZUN';

        const header =
            'Planlanan: ' +
            deviation.expected_minutes +
            ' dk &nbsp;·&nbsp; Gerçekleşen: ' +
            deviation.actual_minutes +
            ' dk &nbsp;·&nbsp; (' +
            diffLabel +
            ')';

        if (isEarly) {
            showEarlyExitDialog(appointmentId, deviation, header, callbacks);
            return;
        }

        // "late" (ran over) - unchanged free-text-reason flow.
        const reasonPresets = ['Müşteri geç geldi', 'Ek uygulama yapıldı', 'Teknik/ekipman sorunu', 'Diğer'];

        const buttons = [
            {
                text: lang('cancel'),
                click: (event, messageModal) => messageModal.hide(),
            },
            {
                text: 'Kaydet ve Bitir',
                click: (event, messageModal) => {
                    const reason = ($('#session-deviation-reason').val() || '').trim();

                    if (!reason) {
                        $('#session-deviation-reason').addClass('is-invalid').trigger('focus');
                        return;
                    }

                    messageModal.hide();
                    performCheckOut(appointmentId, reason, null, callbacks);
                },
            },
        ];

        App.Utils.Message.show('Seans Süresi Sapması', header, buttons);

        const $body = $('#message-modal .modal-body');

        const $chips = $('<div/>', {class: 'd-flex flex-wrap gap-2 mt-3 mb-2'});

        reasonPresets.forEach((preset) => {
            $('<button/>', {
                type: 'button',
                class: 'btn btn-outline-secondary btn-sm',
                text: preset,
                click() {
                    $('#session-deviation-reason')
                        .val(preset === 'Diğer' ? '' : preset)
                        .removeClass('is-invalid')
                        .trigger('focus');
                },
            }).appendTo($chips);
        });

        $chips.appendTo($body);

        $('<textarea/>', {
            id: 'session-deviation-reason',
            class: 'form-control w-100 mt-2',
            rows: '2',
            placeholder: 'Sebep yazın...',
        }).appendTo($body);
    }

    /**
     * Salon Flora customization (2026-08-25) - the therapist left more than the tolerance window early.
     * Staff must pick a fixed reason code AND classify it as "Haklı" (justified - bills the full planned
     * duration once an admin/secretary approves it) or "Haksız" (unjustified - bills the real, shorter
     * duration). A therapist classifying their own session is recorded but stays unapproved (pending)
     * until an admin/secretary reviews it - see Calendar.php::check_out()/review_early_exit().
     */
    function showEarlyExitDialog(appointmentId, deviation, header, callbacks) {
        const reasonCodes = deviation.reason_codes || vars('early_exit_reason_codes') || {};

        const buttons = [
            {
                text: lang('cancel'),
                click: (event, messageModal) => messageModal.hide(),
            },
            {
                text: 'Kaydet ve Bitir',
                click: (event, messageModal) => {
                    const justification = $('input[name="sf-early-exit-justification"]:checked').val();
                    const reasonCode = $('#early-exit-reason-code').val();

                    if (!justification) {
                        App.Layouts.Backend.displayNotification('Haklı mı haksız mı olduğunu seçin.');
                        return;
                    }

                    if (!reasonCode) {
                        $('#early-exit-reason-code').addClass('is-invalid').trigger('focus');
                        return;
                    }

                    messageModal.hide();
                    performCheckOut(appointmentId, null, {justification, reasonCode}, callbacks);
                },
            },
        ];

        App.Utils.Message.show('Erken Çıkış', header, buttons);

        const $body = $('#message-modal .modal-body');

        $('<div/>', {
            class: 'mt-3 mb-2',
            html: [
                $('<label/>', {class: 'form-label d-block', text: 'Bu erken çıkış:'}),
                $('<div/>', {
                    class: 'btn-group w-100',
                    role: 'group',
                    html: [
                        $('<input/>', {
                            type: 'radio',
                            class: 'btn-check',
                            name: 'sf-early-exit-justification',
                            id: 'sf-early-exit-justified',
                            value: 'justified',
                        }),
                        $('<label/>', {
                            class: 'btn btn-outline-success',
                            for: 'sf-early-exit-justified',
                            text: 'Haklı',
                        }),
                        $('<input/>', {
                            type: 'radio',
                            class: 'btn-check',
                            name: 'sf-early-exit-justification',
                            id: 'sf-early-exit-unjustified',
                            value: 'unjustified',
                        }),
                        $('<label/>', {
                            class: 'btn btn-outline-danger',
                            for: 'sf-early-exit-unjustified',
                            text: 'Haksız',
                        }),
                    ],
                }),
            ],
        }).appendTo($body);

        const $select = $('<select/>', {
            id: 'early-exit-reason-code',
            class: 'form-select mt-2',
        }).appendTo($body);

        $('<option/>', {value: '', text: 'Sebep seçin...'}).appendTo($select);

        Object.keys(reasonCodes).forEach((code) => {
            $('<option/>', {value: code, text: reasonCodes[code]}).appendTo($select);
        });

        $select.on('change', () => $select.removeClass('is-invalid'));
    }

    return {
        checkIn,
        checkOut,
        showPaymentDialog,
        hasPayment,
        collectPayment,
    };
})();
