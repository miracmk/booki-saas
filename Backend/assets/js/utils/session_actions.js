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

                App.Layouts.Backend.displayNotification('Randevu başlatıldı. Adisyon ekranına yönlendiriliyorsunuz...');
                callbacks.onUpdated?.(response.appointment);
                const targetUrl = (vars('site_url') || '') + '/adisyons?appointment_id=' + appointmentId;
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 400);
            })
            .fail(() => {
                App.Layouts.Backend.displayNotification('Randevu başlatılamadı.');
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
     * Finish an appointment (check-out).
     *
     * @param {Number} appointmentId
     * @param {Object} callbacks {onUpdated(appointment), onError()}
     */
    function checkOut(appointmentId, callbacks = {}) {
        performCheckOut(appointmentId, null, null, callbacks);
    }

    /**
     * Let staff record payment. Payment is always collected on the Adisyon screen (each appointment has its
     * own Adisyon), so this navigates there instead of showing the legacy appointment-level dialog. The
     * Adisyon page's index() resolves the appointment to an existing (or new) Adisyon and auto-opens its
     * drawer, where "Tahsilat Al" records the payment. Shared by the appointment modal's "Tahsilat Bilgisini
     * Düzenle" and the active-sessions widget's "Tahsilat Al".
     *
     * @param {Number} appointmentId
     * @param {Object} appointment Current appointment data (unused; kept for signature compatibility).
     * @param {Object} callbacks {onUpdated(appointment), onError()} (unused; kept for signature compatibility).
     */
    function collectPayment(appointmentId, appointment, callbacks = {}) {
        if (!appointmentId) {
            callbacks.onError?.();
            return;
        }

        App.Layouts.Backend.displayNotification('Adisyon ekranına yönlendiriliyorsunuz...');
        const targetUrl = (vars('site_url') || '') + '/adisyons?appointment_id=' + appointmentId + '&action=pay';
        setTimeout(() => {
            window.location.href = targetUrl;
        }, 300);
    }

    /**
     * Legacy shim: redirects to unified Adisyon payment collection.
     */
    function showPaymentDialog(appointmentId, appointment, callbacks) {
        collectPayment(appointmentId, appointment, callbacks);
    }

    function performCheckOut(appointmentId, reason, earlyExit, callbacks) {
        App.Http.Calendar.checkOut(appointmentId, reason, earlyExit)
            .done((response) => {
                if (response.success) {
                    App.Layouts.Backend.displayNotification(
                        reason ? 'Randevu kaydedildi. Adisyona yönlendiriliyorsunuz...' : 'Randevu tamamlandı. Adisyona yönlendiriliyorsunuz...',
                    );

                    callbacks.onUpdated?.(response.appointment);

                    const targetUrl = (vars('site_url') || '') + '/adisyons?appointment_id=' + appointmentId + '&action=pay';
                    setTimeout(() => {
                        window.location.href = targetUrl;
                    }, 400);

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
                App.Layouts.Backend.displayNotification('Randevu tamamlanamadı.');
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
                        App.Layouts.Backend.displayNotification('Lütfen erken çıkış kusur / haklılık durumunu seçin.');
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

        App.Utils.Message.show('Erken Çıkış Değerlendirmesi', header, buttons);

        const $body = $('#message-modal .modal-body');

        $('<div/>', {
            class: 'mt-3 mb-2',
            html: [
                $('<label/>', {class: 'form-label d-block fw-bold text-dark mb-2', text: 'Erken Çıkış Kusur / Sorumluluk Tespiti:'}),
                $('<div/>', {
                    class: 'd-flex flex-column gap-2 w-100',
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
                            class: 'btn btn-outline-success text-start py-2 px-3',
                            for: 'sf-early-exit-justified',
                            html: '<i class="fas fa-check-circle me-2"></i><strong>Personel Haklı</strong> <span class="d-block small text-muted">Müşteri kaynaklı / erken ayrıldı — Personel tam hakedişini alır.</span>',
                        }),
                        $('<input/>', {
                            type: 'radio',
                            class: 'btn-check',
                            name: 'sf-early-exit-justification',
                            id: 'sf-early-exit-unjustified',
                            value: 'unjustified',
                        }),
                        $('<label/>', {
                            class: 'btn btn-outline-danger text-start py-2 px-3',
                            for: 'sf-early-exit-unjustified',
                            html: '<i class="fas fa-times-circle me-2"></i><strong>Müşteri Haklı</strong> <span class="d-block small text-muted">Personel kusurlu / eksik hizmet — Personel hakedişi kesilir.</span>',
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
