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
 * Appointments modal component.
 *
 * This module implements the appointments modal functionality.
 *
 * Old Name: BackendCalendarAppointmentsModal
 */
App.Components.AppointmentsModal = (function () {
    const $appointmentsModal = $('#appointments-modal');
    const $startDatetime = $('#start-datetime');
    const $endDatetime = $('#end-datetime');
    const $filterExistingCustomers = $('#filter-existing-customers');
    const $customerId = $('#customer-id');
    const $firstName = $('#first-name');
    const $lastName = $('#last-name');
    const $email = $('#email');
    const $phoneNumber = $('#phone-number');
    const $address = $('#address');
    const $city = $('#city');
    const $state = $('#state');
    const $zipCode = $('#zip-code');
    const $language = $('#language');
    const $timezone = $('#timezone');
    const $customerNotes = $('#customer-notes');
    const $selectCustomer = $('#select-customer');
    const $saveAppointment = $('#save-appointment');
    const $appointmentId = $('#appointment-id');
    const $appointmentLocation = $('#appointment-location');
    const $appointmentMeetingLink = $('#appointment-meeting-link');
    const $appointmentStatus = $('#appointment-status');
    const $appointmentColor = $('#appointment-color');
    const $appointmentNotes = $('#appointment-notes');
    const $reloadAppointments = $('#reload-appointments');
    const $selectFilterItem = $('#select-filter-item');
    const $selectService = $('#select-service');
    const $selectProvider = $('#select-provider');
    const $insertAppointment = $('#insert-appointment');
    const $existingCustomersList = $('#existing-customers-list');
    const $newCustomer = $('#new-customer');
    const $customField1 = $('#custom-field-1');
    const $customField2 = $('#custom-field-2');
    const $customField3 = $('#custom-field-3');
    const $customField4 = $('#custom-field-4');
    const $customField5 = $('#custom-field-5');

    // Salon Flora customization - station selection + check-in/check-out.
    const $stationPanel = $('.salonflora-station-panel');
    const $stationSelect = $('#salonflora-station-select');
    const $firstAvailabilityBtn = $('#salonflora-first-availability-btn');
    const $firstAvailabilityResults = $('#salonflora-first-availability-results');
    const $stationMode = $('.salonflora-station-mode');
    const $checkInOutPanel = $('.salonflora-checkinout-panel');
    const $actualStart = $('.salonflora-actual-start');
    const $actualEnd = $('.salonflora-actual-end');
    const $checkInButton = $('#salonflora-check-in');
    const $checkOutButton = $('#salonflora-check-out');
    const $clearSessionButton = $('#salonflora-clear-session');
    const $sessionDeviation = $('.salonflora-session-deviation');
    const $paymentPanel = $('.salonflora-payment-panel');
    const $paymentSummary = $('.salonflora-payment-summary');
    const $editPaymentButton = $('#salonflora-edit-payment');
    const $customDuration = $('#salonflora-custom-duration');
    const $priceOverride = $('#salonflora-price-override');
    const $pricePreview = $('.salonflora-price-preview');
    const $editSessionStartButton = $('#salonflora-edit-session-start');
    const $editSessionEndButton = $('#salonflora-edit-session-end');
    const $sessionStartEditRow = $('.salonflora-edit-session-start-row');
    const $sessionEndEditRow = $('.salonflora-edit-session-end-row');
    const $sessionStartInput = $('#salonflora-session-start-input');
    const $sessionEndInput = $('#salonflora-session-end-input');
    const $saveSessionStartButton = $('#salonflora-save-session-start');
    const $saveSessionEndButton = $('#salonflora-save-session-end');
    const $cancelSessionStartButton = $('#salonflora-cancel-session-start');
    const $cancelSessionEndButton = $('#salonflora-cancel-session-end');

    const moment = window.moment;

    // Salon Flora customization - the full data object of the appointment currently open in the modal (as last
    // passed to displaySessionTracking()), used by the payment/session-time-edit actions which need fields
    // (actual_start_datetime, actual_end_datetime, custom_duration_minutes, price_override, service) beyond what's
    // reflected in the form inputs.
    let currentAppointmentData = null;

    // Salon Flora bugfix - updateStationOptions() fires an AJAX request on every service/provider/time
    // change with no cancellation of the previous in-flight one; a fast sequence of changes could let an
    // older response overwrite the dropdown after a newer request had already started. Each call captures
    // the current token; a response is only applied if the token still matches the latest call.
    let stationRequestToken = 0;

    /**
     * Salon Flora customization - the sequential booking form's step lock: 1 saat → 2 hizmet → 3 hizmet sağlayıcı
     * → 4 istasyon → 5 müşteri. Each step is disabled/dimmed (.sf-step-locked) until the previous one has a
     * value. Editing an EXISTING appointment unlocks everything at once (all the values already exist and are
     * valid - there's nothing sequential to enforce). New appointments start locked down to step 1.
     */
    const stepController = (function () {
        const $steps = $('.sf-step');

        function lockFrom(step) {
            $steps.each((index, el) => {
                const $el = $(el);
                const stepNumber = Number($el.data('step'));
                $el.toggleClass('sf-step-locked', stepNumber >= step);
            });
        }

        function unlockAll() {
            $steps.removeClass('sf-step-locked');
        }

        function reset() {
            lockFrom(2);
        }

        return {lockFrom, unlockAll, reset};
    })();

    /**
     * Update the displayed timezone.
     */
    function updateTimezone() {
        const providerId = $selectProvider.val();

        const provider = vars('available_providers').find(
            (availableProvider) => Number(availableProvider.id) === Number(providerId),
        );

        if (provider && provider.timezone) {
            $('.provider-timezone').text(vars('timezones')[provider.timezone]);
        }
    }

    /**
     * Salon Flora customization - fill the station dropdown with the stations available for the currently
     * selected service (narrowed to the provider's own assigned stations only if that provider has
     * station_restriction_enabled set - see Stations_model::get_candidate_station_ids()), each marked
     * free/occupied for the currently selected time range. Used both when editing an existing appointment
     * and when booking a new one, so staff can choose the station up front rather than only after the
     * appointment already exists.
     *
     * @param {Number|String|null} keepStationId - Station id to keep selected if it's still a valid option
     *   (falls back to "— Atanmamış —" otherwise).
     */
    function updateStationOptions(keepStationId = null) {
        // Salon Flora customization - stations are now tied to SERVICES, not to providers (see
        // Stations_model::get_candidate_station_ids()), so the client no longer has enough information (which
        // stations a service is restricted to, and which are occupied at this specific time) to build this list
        // itself - it must ask the server. Requires service + provider + a valid time range to all be selected;
        // until then the panel stays disabled with an explanatory placeholder (this is step 4 of the sequential
        // booking flow, so that's the normal state until steps 1-3 are done).
        const serviceId = $selectService.val();
        const providerId = $selectProvider.val();

        let startDateTimeObject;
        let endDateTimeObject;

        try {
            startDateTimeObject = App.Utils.UI.getDateTimePickerValue($startDatetime);
            endDateTimeObject = App.Utils.UI.getDateTimePickerValue($endDatetime);
        } catch (e) {
            startDateTimeObject = null;
        }

        if (!serviceId || !providerId || !startDateTimeObject || !endDateTimeObject) {
            $stationSelect
                .empty()
                .append($('<option/>', {value: '', text: 'Önce saat, hizmet ve hizmet sağlayıcı seçin'}))
                .prop('disabled', true);
            $('.sf-station-hint').text('');
            return;
        }

        const startDatetime = moment(startDateTimeObject).format('YYYY-MM-DD HH:mm:ss');
        const endDatetime = moment(endDateTimeObject).format('YYYY-MM-DD HH:mm:ss');
        const appointmentId = $appointmentId.val() || null;

        $stationSelect.prop('disabled', true).empty().append($('<option/>', {value: '', text: 'Yükleniyor...'}));

        const requestToken = ++stationRequestToken;

        App.Http.Calendar.getAvailableStations(serviceId, providerId, startDatetime, endDatetime, appointmentId)
            .done((response) => {
                if (requestToken !== stationRequestToken) {
                    // A newer call has since been made - this response is stale, ignore it.
                    return;
                }

                if (!response.success) {
                    return;
                }

                const stations = response.stations || [];

                $stationSelect
                    .empty()
                    .append($('<option/>', {value: '', text: '— Atanmamış —'}))
                    .append(
                        stations.map((station) =>
                            $('<option/>', {
                                value: station.value,
                                text: station.is_free ? station.label : station.label + ' (dolu)',
                                disabled: !station.is_free,
                            }),
                        ),
                    );

                const keepValid =
                    keepStationId && stations.some((station) => Number(station.value) === Number(keepStationId));

                $stationSelect.val(keepValid ? keepStationId : '').prop('disabled', stations.length === 0);

                if (!stations.length) {
                    $stationSelect.empty().append(
                        $('<option/>', {value: '', text: 'Bu hizmet+sağlayıcı için uygun istasyon yok'}),
                    );
                }

                const freeCount = stations.filter((station) => station.is_free).length;

                $('.sf-station-hint').text(
                    stations.length ? `Bu saatte ${freeCount}/${stations.length} istasyon müsait.` : '',
                );

                // Salon Flora customization - BUG FIX: step 5 (müşteri) was only unlocked by the station
                // select's OWN 'change' event, which never fires if staff leave it on its default value (e.g.
                // "— Atanmamış —" for automatic assignment) without touching it - a station choice (even "no
                // station") completes step 4 regardless of whether the dropdown was touched, so unlock here too,
                // once the options have actually loaded.
                stepController.lockFrom(6);
            })
            .fail(() => {
                if (requestToken !== stationRequestToken) {
                    return;
                }

                $stationSelect
                    .empty()
                    .append($('<option/>', {value: '', text: 'İstasyon listesi yüklenemedi'}))
                    .prop('disabled', false);
            });
    }

    /**
     * Add the component event listeners.
     */
    function addEventListeners() {
        /**
         * Salon Flora customization - Event: "Seansı Başlat" (check-in) button click. Delegates to
         * App.Utils.SessionActions so behavior is identical to the popover's quick check-in button.
         */
        $checkInButton.on('click', () => {
            const appointmentId = $appointmentId.val();

            if (!appointmentId) {
                return;
            }

            $checkInButton.prop('disabled', true);

            App.Utils.SessionActions.checkIn(appointmentId, {
                onUpdated: (appointment) => displaySessionTracking(appointment),
                onError: () => $checkInButton.prop('disabled', false),
            });
        });

        /**
         * Salon Flora customization - Event: "Seansı Bitir" (check-out) button click. Delegates to
         * App.Utils.SessionActions, which shows the deviation-reason dialog when needed - identical behavior to
         * the popover's quick check-out button.
         */
        $checkOutButton.on('click', () => {
            const appointmentId = $appointmentId.val();

            if (!appointmentId) {
                return;
            }

            $checkOutButton.prop('disabled', true);

            App.Utils.SessionActions.checkOut(appointmentId, {
                onUpdated: (appointment) => displaySessionTracking(appointment),
                onError: () => $checkOutButton.prop('disabled', false),
            });
        });

        /**
         * Salon Flora customization - Event: "Sıfırla" (clear session times) button click.
         */
        $clearSessionButton.on('click', () => {
            const appointmentId = $appointmentId.val();
            if (!appointmentId) {
                return;
            }
            App.Http.Calendar.clearSessionTimes(appointmentId).done((response) => {
                App.Layouts.Backend.displayNotification('Seans bilgileri sıfırlandı.');
                displaySessionTracking(response.appointment);
            });
        });

        /**
         * Salon Flora customization - Event: "Tahsilat Bilgisini Düzenle" button click. Lets admins/secretaries
         * correct or fill in payment details after the fact (e.g. a provider checked the session out, leaving
         * payment_status "pending", and now someone at reception is recording the actual payment).
         */
        $editPaymentButton.on('click', () => {
            const appointmentId = $appointmentId.val();

            if (!appointmentId || !currentAppointmentData) {
                return;
            }

            App.Utils.SessionActions.collectPayment(appointmentId, currentAppointmentData, {
                onUpdated: (updatedAppointment) => displaySessionTracking(updatedAppointment),
            });
        });

        /**
         * Salon Flora customization - Events: "Özel Süre" / "Sabit Fiyat" inputs and the service dropdown all
         * affect the effective price preview.
         */
        $customDuration.on('input', updatePricePreview);
        $priceOverride.on('input', updatePricePreview);
        $selectService.on('change', updatePricePreview);

        /**
         * Salon Flora customization - Events: "Düzenle" (edit check-in/check-out time) buttons. Only shown to
         * admins/secretaries (see displaySessionTracking()). Opens an inline datetime-local input prefilled with
         * the current value (or now, if never set), so staff can backfill a missed check-in/check-out or correct
         * a mistaken one.
         */
        $editSessionStartButton.on('click', () => {
            const current = currentAppointmentData?.actual_start_datetime
                ? moment(currentAppointmentData.actual_start_datetime)
                : moment();
            $sessionStartInput.val(current.format('YYYY-MM-DDTHH:mm'));
            $sessionStartEditRow.removeClass('d-none');
        });

        $editSessionEndButton.on('click', () => {
            const current = currentAppointmentData?.actual_end_datetime
                ? moment(currentAppointmentData.actual_end_datetime)
                : moment();
            $sessionEndInput.val(current.format('YYYY-MM-DDTHH:mm'));
            $sessionEndEditRow.removeClass('d-none');
        });

        $cancelSessionStartButton.on('click', () => $sessionStartEditRow.addClass('d-none'));
        $cancelSessionEndButton.on('click', () => $sessionEndEditRow.addClass('d-none'));

        $saveSessionStartButton.on('click', () => {
            const appointmentId = $appointmentId.val();
            const value = $sessionStartInput.val();

            if (!appointmentId || !value) {
                return;
            }

            const actualStart = moment(value).format('YYYY-MM-DD HH:mm:ss');
            const actualEnd = currentAppointmentData?.actual_end_datetime || null;

            App.Http.Calendar.updateSessionTimes(appointmentId, actualStart, actualEnd)
                .done((response) => {
                    if (!response.success) {
                        App.Layouts.Backend.displayNotification(response.message || 'İşlem başarısız.');
                        return;
                    }
                    App.Layouts.Backend.displayNotification('Başlangıç saati güncellendi.');
                    $sessionStartEditRow.addClass('d-none');
                    displaySessionTracking(response.appointment);
                })
                .fail((jqXHR) => {
                    App.Layouts.Backend.displayNotification(jqXHR.responseJSON?.message || 'Güncellenemedi.');
                });
        });

        $saveSessionEndButton.on('click', () => {
            const appointmentId = $appointmentId.val();
            const value = $sessionEndInput.val();

            if (!appointmentId || !value) {
                return;
            }

            const actualStart = currentAppointmentData?.actual_start_datetime || null;
            const actualEnd = moment(value).format('YYYY-MM-DD HH:mm:ss');

            App.Http.Calendar.updateSessionTimes(appointmentId, actualStart, actualEnd)
                .done((response) => {
                    if (!response.success) {
                        App.Layouts.Backend.displayNotification(response.message || 'İşlem başarısız.');
                        return;
                    }
                    App.Layouts.Backend.displayNotification('Bitiş saati güncellendi.');
                    $sessionEndEditRow.addClass('d-none');
                    displaySessionTracking(response.appointment);
                })
                .fail((jqXHR) => {
                    App.Layouts.Backend.displayNotification(jqXHR.responseJSON?.message || 'Güncellenemedi.');
                });
        });

        /**
         * Salon Flora customization - Event: station dropdown change. Saves immediately (rather than waiting for
         * the main "Kaydet" button) so staff see the conflict error right away if the chosen station turns out to
         * be occupied.
         */
        $stationSelect.on('change', () => {
            // Salon Flora customization - sequential booking form: a station choice (even "— Atanmamış —",
            // value "") completes the sequence - unlock step 5 (müşteri). This runs for both new and existing
            // appointments; only the live update_station call below is existing-appointment-only.
            stepController.lockFrom(6);

            const appointmentId = $appointmentId.val();

            if (!appointmentId) {
                return;
            }

            const stationId = $stationSelect.val() || null;

            $stationSelect.prop('disabled', true);

            App.Http.Calendar.updateStation(appointmentId, stationId)
                .done((response) => {
                    if (!response.success) {
                        App.Layouts.Backend.displayNotification(response.message || 'İşlem başarısız.');
                        return;
                    }

                    App.Layouts.Backend.displayNotification('İstasyon güncellendi.');
                    displaySessionTracking(response.appointment);
                })
                .fail((jqXHR) => {
                    const message = jqXHR.responseJSON?.message || 'İstasyon güncellenemedi.';
                    App.Layouts.Backend.displayNotification(message);
                })
                .always(() => {
                    $stationSelect.prop('disabled', false);
                });
        });

        /**
         * Event: Manage Appointments Dialog Save Button "Click"
         *
         * Stores the appointment changes or inserts a new appointment depending on the dialog mode.
         */
        $saveAppointment.on('click', () => {
            // Before doing anything the appointment data need to be validated.
            if (!App.Components.AppointmentsModal.validateAppointmentForm()) {
                return;
            }

            // ID must exist on the object in order for the model to update the record and not to perform
            // an insert operation.

            const startDateTimeObject = App.Utils.UI.getDateTimePickerValue($startDatetime);
            const startDatetime = moment(startDateTimeObject).format('YYYY-MM-DD HH:mm:ss');

            const endDateTimeObject = App.Utils.UI.getDateTimePickerValue($endDatetime);
            const endDatetime = moment(endDateTimeObject).format('YYYY-MM-DD HH:mm:ss');

            const appointment = {
                id_services: $selectService.val(),
                id_users_provider: $selectProvider.val(),
                start_datetime: startDatetime,
                end_datetime: endDatetime,
                location: $appointmentLocation.val(),
                meeting_link: $appointmentMeetingLink.val(),
                color: App.Components.ColorSelection.getColor($appointmentColor),
                status: $appointmentStatus.val(),
                notes: $appointmentNotes.val(),
                is_unavailability: Number(false),
                // Salon Flora customization - booking-time pricing overrides (both optional).
                custom_duration_minutes: $customDuration.val() !== '' ? $customDuration.val() : null,
                price_override: $priceOverride.val() !== '' ? $priceOverride.val() : null,
            };

            if ($appointmentId.val() !== '') {
                // Set the id value, only if we are editing an appointment.
                appointment.id = $appointmentId.val();
            } else {
                // Salon Flora customization - only send the station choice for a brand new appointment. An
                // existing appointment's station is already saved live via the dropdown's change handler
                // (updateStation) - sending it again here would either needlessly re-validate it or, if staff
                // temporarily cleared the dropdown without meaning to unassign it, wrongly fall back to automatic
                // assignment.
                appointment.id_stations = $stationSelect.val() || null;
            }

            const customer = {
                first_name: $firstName.val(),
                last_name: $lastName.val(),
                email: $email.val(),
                phone_number: $phoneNumber.val(),
                address: $address.val(),
                city: $city.val(),
                state: $state.val(),
                zip_code: $zipCode.val(),
                language: $language.val(),
                timezone: $timezone.val(),
                notes: $customerNotes.val(),
                custom_field_1: $customField1.val(),
                custom_field_2: $customField2.val(),
                custom_field_3: $customField3.val(),
                custom_field_4: $customField4.val(),
                custom_field_5: $customField5.val(),
            };

            if ($customerId.val() !== '') {
                // Set the id value, only if we are editing an appointment.
                customer.id = $customerId.val();
                appointment.id_users_customer = customer.id;
            }

            // Define success callback.
            const successCallback = () => {
                // Display success message to the user.
                App.Layouts.Backend.displayNotification(lang('appointment_saved'));

                // Close the modal dialog and refresh the calendar appointments.
                $appointmentsModal.find('.alert').addClass('d-none');
                $appointmentsModal.modal('hide');
                $reloadAppointments.trigger('click');
            };

            // Define error callback.
            const errorCallback = () => {
                $appointmentsModal.find('.modal-message').text(lang('service_communication_error'));
                $appointmentsModal.find('.modal-message').addClass('alert-danger').removeClass('d-none');
                $appointmentsModal.find('.modal-body').scrollTop(0);
            };

            // Check if this is an update (appointment has an ID)
            const isUpdate = Boolean(appointment.id);

            if (isUpdate) {
                // Salon Flora customization - let staff pick exactly who gets notified (customer /
                // provider / admin), instead of a single all-or-nothing yes/no choice.
                App.Utils.Message.confirmNotifyOptions(
                    lang('appointment_update'),
                    lang('notify_users_on_update_question'),
                    (notify) => {
                        App.Http.Calendar.saveAppointmentWithConflictHandling(
                            appointment,
                            customer,
                            successCallback,
                            errorCallback,
                            notify,
                        );
                    },
                );
            } else {
                // New appointment - let staff pick exactly who gets notified.
                App.Utils.Message.confirmNotifyOptions(
                    lang('new_appointment_title'),
                    lang('notify_users_on_create_question'),
                    (notify) => {
                        App.Http.Calendar.saveAppointmentWithConflictHandling(
                            appointment,
                            customer,
                            successCallback,
                            errorCallback,
                            notify,
                        );
                    },
                );
            }
        });

        /**
         * Event: Insert Appointment Button "Click"
         *
         * When the user presses this button, the manage appointment dialog opens and lets the user create a new
         * appointment.
         */
        $insertAppointment.on('click', () => {
            $('.popover').remove();

            App.Components.AppointmentsModal.resetModal();

            // Set the selected filter item and find the next appointment time as the default modal values.
            if ($selectFilterItem.find('option:selected').attr('type') === 'provider') {
                const providerId = $('#select-filter-item').val();

                const providers = vars('available_providers').filter(
                    (provider) => Number(provider.id) === Number(providerId),
                );

                if (providers.length) {
                    $selectService.val(providers[0].services[0]).trigger('change');
                    $selectProvider.val(providerId);
                }
            } else if ($selectFilterItem.find('option:selected').attr('type') === 'service') {
                // Salon Flora customization - BUG FIX: selecting the option alone doesn't fire 'change', so the
                // provider list box below stayed populated with resetModal()'s default service's providers
                // instead of this (filtered) service's providers.
                $selectService
                    .find('option[value="' + $selectFilterItem.val() + '"]')
                    .prop('selected', true)
                    .end()
                    .trigger('change');
            } else {
                $selectService.find('option:first').prop('selected', true).trigger('change');
            }

            $selectProvider.trigger('change');

            const serviceId = $selectService.val();

            const service = vars('available_services').find(
                (availableService) => Number(availableService.id) === Number(serviceId),
            );

            const duration = service ? service.duration : 60;

            const startMoment = moment();

            const currentMin = parseInt(startMoment.format('mm'));

            if (currentMin > 0 && currentMin < 15) {
                startMoment.set({minutes: 15});
            } else if (currentMin > 15 && currentMin < 30) {
                startMoment.set({minutes: 30});
            } else if (currentMin > 30 && currentMin < 45) {
                startMoment.set({minutes: 45});
            } else {
                startMoment.add(1, 'hour').set({minutes: 0});
            }

            App.Utils.UI.setDateTimePickerValue($startDatetime, startMoment.toDate());
            App.Utils.UI.setDateTimePickerValue($endDatetime, startMoment.add(duration, 'minutes').toDate());

            // Display modal form.
            $appointmentsModal.find('.modal-header h3').text(lang('new_appointment_title'));

            $appointmentsModal.modal('show');
        });

        /**
         * Event: Pick Existing Customer Button "Click"
         *
         * @param {jQuery.Event} event
         */
        $selectCustomer.on('click', (event) => {
            if (!$existingCustomersList.is(':visible')) {
                $(event.currentTarget).find('span').text(lang('hide'));
                $existingCustomersList.empty();
                $existingCustomersList.slideDown('slow');
                $filterExistingCustomers.fadeIn('slow').val('');
                vars('customers').forEach((customer) => {
                    $('<div/>', {
                        'data-id': customer.id,
                        'text':
                            (customer.first_name || '[No First Name]') + ' ' + (customer.last_name || '[No Last Name]'),
                    }).appendTo($existingCustomersList);
                });
            } else {
                $existingCustomersList.slideUp('slow');
                $filterExistingCustomers.fadeOut('slow');
                $(event.currentTarget).find('span').text(lang('select'));
            }
        });

        /**
         * Event: Select Existing Customer From List "Click"
         *
         * @param {jQuery.Event}
         */
        $appointmentsModal.on('click', '#existing-customers-list div', (event) => {
            const customerId = $(event.target).attr('data-id');

            const customer = vars('customers').find((customer) => Number(customer.id) === Number(customerId));

            if (customer) {
                $customerId.val(customer.id);
                $firstName.val(customer.first_name);
                $lastName.val(customer.last_name);
                $email.val(customer.email);
                $phoneNumber.val(customer.phone_number);
                $address.val(customer.address);
                $city.val(customer.city);
                $zipCode.val(customer.zip_code);
                $language.val(customer.language);
                $timezone.val(customer.timezone);
                $customerNotes.val(customer.notes);
                $customField1.val(customer.custom_field_1);
                $customField2.val(customer.custom_field_2);
                $customField3.val(customer.custom_field_3);
                $customField4.val(customer.custom_field_4);
                $customField5.val(customer.custom_field_5);
                displayCustomerContext(customer);
            }

            $selectCustomer.trigger('click'); // Hide the list.
            updateLiveSummary();
        });

        let filterExistingCustomersTimeout = null;

        /**
         * Event: Filter Existing Customers "Change"
         *
         * @param {jQuery.Event}
         */
        $filterExistingCustomers.on('keyup', (event) => {
            if (filterExistingCustomersTimeout) {
                clearTimeout(filterExistingCustomersTimeout);
            }

            const keyword = $(event.target).val().toLowerCase();

            filterExistingCustomersTimeout = setTimeout(() => {
                $('#loading').css('visibility', 'hidden');

                App.Http.Customers.search(keyword, 50)
                    .done((response) => {
                        $existingCustomersList.empty();

                        response.forEach((customer) => {
                            $('<div/>', {
                                'data-id': customer.id,
                                'text':
                                    (customer.first_name || '[No First Name]') +
                                    ' ' +
                                    (customer.last_name || '[No Last Name]'),
                            }).appendTo($existingCustomersList);

                            // Verify if this customer is on the old customer list.
                            const result = vars('customers').filter((existingCustomer) => {
                                return Number(existingCustomer.id) === Number(customer.id);
                            });

                            // Add it to the customer list.
                            if (!result.length) {
                                vars('customers').push(customer);
                            }
                        });
                    })
                    .fail(() => {
                        // If there is any error on the request, search by the local client database.
                        $existingCustomersList.empty();

                        vars('customers').forEach((customer) => {
                            if (
                                customer.first_name.toLowerCase().indexOf(keyword) !== -1 ||
                                customer.last_name.toLowerCase().indexOf(keyword) !== -1 ||
                                customer.email.toLowerCase().indexOf(keyword) !== -1 ||
                                customer.phone_number.toLowerCase().indexOf(keyword) !== -1 ||
                                customer.address.toLowerCase().indexOf(keyword) !== -1 ||
                                customer.city.toLowerCase().indexOf(keyword) !== -1 ||
                                customer.zip_code.toLowerCase().indexOf(keyword) !== -1 ||
                                customer.notes.toLowerCase().indexOf(keyword) !== -1
                            ) {
                                $('<div/>', {
                                    'data-id': customer.id,
                                    'text':
                                        (customer.first_name || '[No First Name]') +
                                        ' ' +
                                        (customer.last_name || '[No Last Name]'),
                                }).appendTo($existingCustomersList);
                            }
                        });
                    })
                    .always(() => {
                        $('#loading').css('visibility', '');
                    });
            }, 1000);
        });

        /**
         * Event: Selected Service "Change"
         *
         * When the user clicks on a service, its available providers should become visible. We also need to
         * update the start and end time of the appointment.
         */
        $selectService.on('change', () => {
            const serviceId = $selectService.val();

            const providerId = $selectProvider.val();

            $selectProvider.empty();

            // Automatically update the service duration.
            const service = vars('available_services').find((availableService) => {
                return Number(availableService.id) === Number(serviceId);
            });

            if (service?.color) {
                App.Components.ColorSelection.setColor($appointmentColor, service.color);
            }

            const duration = service ? service.duration : 60;

            const startDateTimeObject = App.Utils.UI.getDateTimePickerValue($startDatetime);
            const endDateTimeObject = new Date(startDateTimeObject.getTime() + duration * 60000);
            App.Utils.UI.setDateTimePickerValue($endDatetime, endDateTimeObject);

            // Update the providers select box.

            vars('available_providers').forEach((provider) => {
                provider.services.forEach((providerServiceId) => {
                    if (
                        vars('role_slug') === App.Layouts.Backend.DB_SLUG_PROVIDER &&
                        Number(provider.id) !== vars('user_id')
                    ) {
                        return; // continue
                    }

                    if (
                        vars('role_slug') === App.Layouts.Backend.DB_SLUG_SECRETARY &&
                        vars('secretary_providers').indexOf(Number(provider.id)) === -1
                    ) {
                        return; // continue
                    }

                    // If the current provider is able to provide the selected service, add him to the list box.
                    if (Number(providerServiceId) === Number(serviceId)) {
                        $selectProvider.append(new Option(provider.first_name + ' ' + provider.last_name, provider.id));
                    }
                });

                if ($selectProvider.find(`option[value="${providerId}"]`).length) {
                    $selectProvider.val(providerId);
                }
            });

            // Salon Flora customization - sequential booking form: a service is now picked, so unlock step 3
            // (hizmet sağlayıcı). Steps 4 (istasyon) and 5 (müşteri) reset back to locked - the provider list
            // just changed, so any previously-selected provider/station may no longer be valid.
            stepController.lockFrom(4);
        });

        /**
         * Event: Provider "Change"
         */
        $selectProvider.on('change', () => {
            updateTimezone();

            // Salon Flora customization - refresh the station options for the newly selected provider. Only
            // meaningful once an appointment is being booked/edited (the panel is shown either way, filtered to
            // an empty state if the provider has no stations).
            updateStationOptions();

            // Salon Flora customization - sequential booking form: a provider is now picked, so unlock step 4
            // (istasyon). Step 5 (müşteri) only unlocks once a station is actually chosen (see the station
            // select's own 'change' handler).
            if ($selectProvider.val()) {
                stepController.lockFrom(5);
            }
        });

        /**
         * Event: Enter New Customer Button "Click"
         */
        $newCustomer.on('click', () => {
            $customerId.val('');
            $firstName.val('');
            $lastName.val('');
            $email.val('');
            $phoneNumber.val('');
            $address.val('');
            $city.val('');
            $zipCode.val('');
            $language.val(vars('default_language'));
            $timezone.val(vars('default_timezone'));
            $customerNotes.val('');
            $customField1.val('');
            $customField2.val('');
            $customField3.val('');
            $customField4.val('');
            $customField5.val('');
        });

        /**
         * Event: "İlk Müsaitlik" Button "Click"
         *
         * BooKi (2026-08-26) - find the first 3 upcoming slots for the selected service
         * (each already matched to a specific free provider + station) and let staff pick one.
         */
        $firstAvailabilityBtn.on('click', () => {
            const serviceId = $selectService.val();

            if (!serviceId) {
                $firstAvailabilityResults
                    .removeClass('d-none')
                    .html('<div class="text-danger small">Önce bir hizmet seçin.</div>');
                return;
            }

            $firstAvailabilityBtn.prop('disabled', true);
            $firstAvailabilityResults.removeClass('d-none').html('<div class="text-muted small">Aranıyor…</div>');

            App.Http.Appointments.firstAvailability(serviceId, 3)
                .done((slots) => {
                    $firstAvailabilityBtn.prop('disabled', false);

                    if (!slots || !slots.length) {
                        $firstAvailabilityResults.html(
                            '<div class="text-muted small">Uygun bir zaman bulunamadı.</div>',
                        );
                        return;
                    }

                    const $list = $('<div class="d-flex flex-column gap-1"></div>');

                    slots.forEach((slot) => {
                        const label =
                            moment(`${slot.date} ${slot.hour}`, 'YYYY-MM-DD HH:mm').format('DD.MM.YYYY HH:mm') +
                            ' — ' +
                            slot.provider_name +
                            (slot.station_name ? ' — ' + slot.station_name : '');

                        const $option = $('<button type="button" class="btn btn-outline-secondary btn-sm text-start"></button>').text(
                            label,
                        );

                        $option.on('click', () => {
                            applyFirstAvailabilitySlot(slot);
                            $firstAvailabilityResults.addClass('d-none').empty();
                        });

                        $list.append($option);
                    });

                    $firstAvailabilityResults.empty().append($list);
                })
                .fail(() => {
                    $firstAvailabilityBtn.prop('disabled', false);
                    $firstAvailabilityResults.html(
                        '<div class="text-danger small">İlk müsaitlik aranırken bir hata oluştu.</div>',
                    );
                });
        });
    }

    /**
     * BooKi (2026-08-26) - apply a slot picked from the "İlk Müsaitlik" results: sets the
     * date/time, triggers the service→provider cascade, then the provider→station cascade (reusing
     * the exact same change handlers a manual selection would trigger), pre-selecting the station
     * that was already matched free for this slot.
     *
     * @param {Object} slot {date, hour, provider_id, station_id, ...} - see Availability::find_first_available_slots().
     */
    function applyFirstAvailabilitySlot(slot) {
        const startDateTimeObject = moment(`${slot.date} ${slot.hour}`, 'YYYY-MM-DD HH:mm').toDate();

        App.Utils.UI.setDateTimePickerValue($startDatetime, startDateTimeObject);

        $selectService.trigger('change');

        $selectProvider.val(slot.provider_id).trigger('change');

        if (slot.station_id) {
            updateStationOptions(slot.station_id);
        }
    }

    /**
     * Reset Appointment Dialog
     *
     * This method resets the manage appointment dialog modal to its initial state. After that you can make
     * any modification might be necessary in order to bring the dialog to the desired state.
     */
    function resetModal() {
        // Salon Flora customization - the station picker is shown for both new and existing appointments (staff
        // can plan which room/station a session will use at booking time); updateStationOptions() below fills it
        // in once the provider list is ready. The check-in/out panel stays hidden for a brand new, unsaved
        // appointment - it has no ID yet, so there's nothing for the check-in endpoint to act on.
        // Salon Flora customization - a brand new appointment starts locked down to step 1 (saat); every other
        // step opens up as staff fill in the previous one. displaySessionTracking() unlocks everything again for
        // an existing appointment being edited (see below).
        stepController.reset();

        $checkInOutPanel.addClass('d-none');
        $stationMode.text('');

        $actualStart.text('-');
        $actualEnd.text('-');
        $checkInButton.prop('disabled', false);
        $checkOutButton.prop('disabled', false);
        $sessionDeviation.addClass('d-none').text('');
        $paymentPanel.addClass('d-none');

        // Salon Flora customization - clear the previous appointment's cached data + pricing preview/edit rows.
        currentAppointmentData = null;
        $pricePreview.text('');
        $editSessionStartButton.addClass('d-none');
        $editSessionEndButton.addClass('d-none');
        $sessionStartEditRow.addClass('d-none');
        $sessionEndEditRow.addClass('d-none');

        // Empty form fields.
        $appointmentsModal.find('input, textarea').val('');
        $appointmentsModal.find('.modal-message').addClass('.d-none');
        $appointmentsModal.find('.is-invalid').removeClass('is-invalid');

        const defaultStatusValue = $appointmentStatus.find('option:first').val();
        $appointmentStatus.val(defaultStatusValue);

        $language.val(vars('default_language'));
        $timezone.val(vars('default_timezone'));

        // Reset color.
        $appointmentColor.find('.color-selection-option:first').trigger('click');

        // Prepare service and provider select boxes.
        //
        // Salon Flora customization - BUG FIX: this used to be `$selectService.val($selectService.eq(0).attr('value'))`.
        // `.attr('value')` on a <select> element (not an <option>) returns undefined, and jQuery's `.val(undefined)`
        // is a no-op getter call - it never actually selected the first option's value. Depending on prior state
        // this could leave $selectService.val() returning null, which made every `Number(x) === Number(null)`
        // comparison below silently fail, emptying the provider list box AND (via updateStationOptions(), which
        // reads $selectProvider.val()) the station dropdown - this was the "istasyon çıkmıyor" bug reported for
        // new appointments.
        //
        // IMPORTANT: only SELECT the option here - do NOT `.trigger('change')` yet. The service 'change' handler
        // reads App.Utils.UI.getDateTimePickerValue($startDatetime), which throws if flatpickr hasn't been
        // initialized on that field yet (it hasn't, at this point in resetModal() - see below). Triggering
        // 'change' here silently broke the ENTIRE resetModal() call (the exception aborted the function before
        // it ever reached `.modal('show')`), which was reported as "randevu kaydedemiyorum" - the modal never
        // opened at all. The trigger is done further down, after both datetimepickers are initialized.
        $selectService.find('option:first').prop('selected', true);

        // Close existing customers-filter frame.
        $existingCustomersList.slideUp('slow');
        $filterExistingCustomers.fadeOut('slow');
        $selectCustomer.find('span').text(lang('select'));

        // Setup start and datetimepickers.
        // Get the selected service duration. It will be needed in order to calculate the appointment end datetime.
        const serviceId = $selectService.val();

        const service = vars('available_services').forEach((service) => Number(service.id) === Number(serviceId));

        const duration = service ? service.duration : 0;

        const startDatetime = new Date();
        const endDatetime = moment().add(duration, 'minutes').toDate();

        App.Utils.UI.initializeDateTimePicker($startDatetime, {
            onClose: () => {
                // Salon Flora customization - BUG FIX: this used to unconditionally overwrite #end-datetime with
                // start + service.duration EVERY time the start picker closed - even just opening and closing it
                // without changing the value. For an EXISTING appointment, that silently wiped out any manually
                // extended/shortened end time (booking-time duration override) the moment staff so much as
                // touched the start field again. Only auto-follow the service's default duration for a brand new,
                // unsaved appointment, where there's no manual end time yet to protect.
                if (!$appointmentId.val()) {
                    const serviceId = $selectService.val();

                    // Automatically update the #end-datetime DateTimePicker based on service duration.
                    const service = vars('available_services').find(
                        (availableService) => Number(availableService.id) === Number(serviceId),
                    );

                    const startDateTimeObject = App.Utils.UI.getDateTimePickerValue($startDatetime);
                    const endDateTimeObject = new Date(startDateTimeObject.getTime() + service.duration * 60000);
                    App.Utils.UI.setDateTimePickerValue($endDatetime, endDateTimeObject);
                }

                // Salon Flora customization - sequential booking form: a time is now picked, unlock step 2
                // (hizmet). The time may have just changed on an appointment that already had a provider/station
                // chosen, so re-check station availability for the new time too.
                stepController.lockFrom(3);
                updateStationOptions($stationSelect.val());
            },
        });

        App.Utils.UI.setDateTimePickerValue($startDatetime, startDatetime);

        App.Utils.UI.initializeDateTimePicker($endDatetime);
        App.Utils.UI.setDateTimePickerValue($endDatetime, endDatetime);
        $appointmentsModal.find('.modal-message').removeClass('alert-danger').text('').addClass('d-none');

        // Salon Flora customization - NOW it's safe to fire the service 'change' handler (both datetimepickers
        // are initialized above) - this re-populates the provider list box from the selected service.
        $selectService.trigger('change');

        // Salon Flora customization - now that the provider list box has its options (and a default selection),
        // fill the station dropdown for that provider.
        updateStationOptions();

        $('#customer-context-banner').addClass('d-none');
        $('#addons-checkbox-list').empty();
        $('#service-addons-selector').addClass('d-none');
        updateLiveSummary();

        // Salon Flora customization - BUG FIX: $selectService.trigger('change') above re-populates the provider
        // list and, via its own 'change' handler, calls stepController.lockFrom(4) - which UNLOCKS steps 2/3
        // (hizmet/sağlayıcı) even though staff haven't picked a time yet for a brand new appointment. Re-lock
        // back down to step 1 here, now that all of resetModal()'s side effects have run.
        stepController.reset();
    }

    /**
     * Validate the manage appointment dialog data.
     *
     * Validation checks need to run every time the data are going to be saved.
     *
     * @return {Boolean} Returns the validation result.
     */
    function validateAppointmentForm() {
        // Reset previous validation css formatting.
        $appointmentsModal.find('.is-invalid').removeClass('is-invalid');
        $appointmentsModal.find('.modal-message').addClass('d-none');

        try {
            // Check required fields.
            let missingRequiredField = false;

            $appointmentsModal.find('.required').each((index, requiredField) => {
                if ($(requiredField).val() === '' || $(requiredField).val() === null) {
                    $(requiredField).addClass('is-invalid');
                    missingRequiredField = true;
                }
            });

            if (missingRequiredField) {
                throw new Error(lang('fields_are_required'));
            }

            // Check email address.
            if (
                $appointmentsModal.find('#email').val() &&
                !App.Utils.Validation.email($appointmentsModal.find('#email').val())
            ) {
                $appointmentsModal.find('#email').addClass('is-invalid');
                throw new Error(lang('invalid_email'));
            }

            // Check appointment start and end time.
            const startDateTimeObject = App.Utils.UI.getDateTimePickerValue($startDatetime);
            const endDateTimeObject = App.Utils.UI.getDateTimePickerValue($endDatetime);

            if (startDateTimeObject > endDateTimeObject) {
                $startDatetime.addClass('is-invalid');
                $endDatetime.addClass('is-invalid');
                throw new Error(lang('start_date_before_end_error'));
            }

            return true;
        } catch (error) {
            $appointmentsModal
                .find('.modal-message')
                .addClass('alert-danger')
                .text(error.message)
                .removeClass('d-none');
            return false;
        }
    }

    /**
     * Salon Flora customization - show the assigned station and the check-in/check-out controls for an existing
     * appointment. Called by the calendar view after it populates the modal with an appointment being edited.
     * New (unsaved) appointments never call this, so the panels stay hidden (see resetModal()) until the
     * appointment actually exists.
     *
     * @param {Object} appointment Appointment data object (including id_users_provider, actual_start_datetime,
     * actual_end_datetime).
     */
    function displaySessionTracking(appointment) {
        currentAppointmentData = appointment;

        // Salon Flora customization - an existing appointment already has valid time/service/provider/station
        // values, so there's nothing sequential left to enforce - unlock every step.
        stepController.unlockAll();

        // Salon Flora customization - restore this appointment's booking-time overrides (if any) and show the
        // effective (real duration, hourly-rate-based) price preview.
        $customDuration.val(appointment.custom_duration_minutes ?? '');
        $priceOverride.val(appointment.price_override ?? '');
        updatePricePreview();

        // Salon Flora customization - only admins/secretaries may correct check-in/check-out times after the
        // fact (same permission as payment management) - a provider can check a session in/out but never
        // backdate/edit those timestamps.
        const canEditSessionTimes = Boolean(vars('can_manage_payment'));
        $editSessionStartButton.toggleClass('d-none', !canEditSessionTimes);
        $editSessionEndButton.toggleClass('d-none', !canEditSessionTimes);
        $sessionStartEditRow.addClass('d-none');
        $sessionEndEditRow.addClass('d-none');

        // Salon Flora customization: the station select is editable (manual override), not just a display label -
        // a provider can be assigned to more than one station, so this must be set explicitly per appointment
        // rather than assumed from the provider record. updateStationOptions() rebuilds the option list scoped to
        // this appointment's provider and keeps the current station selected if still valid.
        updateStationOptions(appointment.id_stations || null);
        // Note: the API can return this as the string "0" (truthy in JS), so it must be coerced to a Number.
        $stationMode.text(Number(appointment.station_assigned_manually) ? 'Manuel atandı' : 'Otomatik atandı');

        $checkInOutPanel.removeClass('d-none');

        if (appointment.actual_start_datetime) {
            $actualStart.text(moment(appointment.actual_start_datetime).format('DD/MM/YYYY HH:mm'));
            $checkInButton.prop('disabled', true);
        } else {
            $actualStart.text('-');
            $checkInButton.prop('disabled', false);
        }

        if (appointment.actual_end_datetime) {
            $actualEnd.text(moment(appointment.actual_end_datetime).format('DD/MM/YYYY HH:mm'));
            $checkOutButton.prop('disabled', true);
        } else {
            $actualEnd.text('-');
            $checkOutButton.prop('disabled', false);
        }

        if (appointment.session_deviation_type) {
            const diffLabel =
                appointment.session_deviation_type === 'early'
                    ? Math.abs(appointment.session_deviation_minutes) + ' dk kısa'
                    : appointment.session_deviation_minutes + ' dk uzun';

            $sessionDeviation
                .text('Sapma: ' + diffLabel + ' — ' + (appointment.session_deviation_reason || '-'))
                .removeClass('d-none');
        } else {
            $sessionDeviation.addClass('d-none').text('');
        }

        // Salon Flora customization - payment panel: only admins/secretaries can see or touch this. Visible from
        // check-in onwards (payment can be collected any time from then on, not just after check-out) - there's
        // simply nothing meaningful to show before a session has even started.
        const canSeePayment = vars('can_manage_payment') && appointment.actual_start_datetime;

        $paymentPanel.toggleClass('d-none', !canSeePayment);

        if (canSeePayment) {
            $paymentSummary.text(paymentSummaryText(appointment));
            $editPaymentButton.text(
                App.Utils.SessionActions.hasPayment(appointment) ? 'Tahsilat Bilgisini Düzenle' : 'Tahsilat Al',
            );
        }

        if (appointment.customer) {
            displayCustomerContext(appointment.customer);
        }
        updateLiveSummary();
    }

    /**
     * Salon Flora customization - recompute and show the effective price preview (hizmetin saatlik ücretine göre,
     * gerçek/özel süre veya sabit fiyat override'ı dikkate alınarak) below the duration/price override inputs, so
     * staff see immediately what booking-time values they're setting.
     */
    function updatePricePreview() {
        const serviceId = $selectService.val();
        const service = (vars('available_services') || []).find(
            (candidate) => Number(candidate.id) === Number(serviceId),
        );

        if (!service) {
            $pricePreview.text('');
            return;
        }

        const appointment = {
            service,
            actual_start_datetime: currentAppointmentData?.actual_start_datetime || null,
            actual_end_datetime: currentAppointmentData?.actual_end_datetime || null,
            custom_duration_minutes: $customDuration.val() || null,
            price_override: $priceOverride.val() || null,
        };

        const {minutes, price} = App.Utils.SessionStatus.effectivePricing(appointment);

        $pricePreview.text('Hesaplanan ücret (' + minutes + ' dk): ' + price.toFixed(2) + ' TRY');
    }

    /**
     * @param {Object} appointment
     * @returns {String} Human-readable payment status summary for the payment panel.
     */
    function paymentSummaryText(appointment) {
        if (appointment.payment_status === 'collected') {
            const method = (vars('payment_methods') || []).find(
                (m) => m.value === appointment.payment_method,
            );

            const amount =
                appointment.payment_amount !== null && appointment.payment_amount !== undefined
                    ? Number(appointment.payment_amount).toFixed(2) + ' TRY'
                    : '-';

            return (
                'Tahsil edildi — ' +
                (method?.label || appointment.payment_method || '-') +
                ' — ' +
                amount +
                (Number(appointment.is_invoiced) ? ' (Faturalı)' : ' (Faturasız)')
            );
        }

        if (appointment.payment_status === 'not_collected') {
            const balance =
                appointment.payment_balance_amount !== null
                    ? ' — Bakiye: ' + Number(appointment.payment_balance_amount).toFixed(2) + ' TRY'
                    : '';

            return 'Tahsilat yapılmadı' + balance;
        }

        return 'Henüz girilmedi';
    }

    /**
     * Update the live summary sidebar panel dynamically.
     */
    function updateLiveSummary() {
        // 1. Customer
        const firstName = $firstName.val() || '';
        const lastName = $lastName.val() || '';
        const customerName = (firstName + ' ' + lastName).trim() || 'Seçilmedi';
        $('#summary-customer-name').text(customerName);
        $('#summary-customer-phone').text($phoneNumber.val() || '-');

        // 2. Service & Addons
        const serviceId = $selectService.val();
        const service = (vars('available_services') || []).find((s) => Number(s.id) === Number(serviceId));
        const serviceName = service ? service.name : '-';
        let baseDuration = service ? Number(service.duration) : 0;
        if ($customDuration.val()) {
            baseDuration = Number($customDuration.val());
        }

        let basePrice = service ? Number(service.price) : 0;
        if ($priceOverride.val() !== '' && $priceOverride.val() !== null) {
            basePrice = Number($priceOverride.val());
        }

        let totalAddonsDuration = 0;
        let totalAddonsPrice = 0;
        const selectedAddonNames = [];

        $('.addon-checkbox:checked').each(function () {
            const addonName = $(this).data('name');
            const addonDur = Number($(this).data('duration') || 0);
            const addonPrice = Number($(this).data('price') || 0);
            selectedAddonNames.push(addonName);
            totalAddonsDuration += addonDur;
            totalAddonsPrice += addonPrice;
        });

        $('#summary-service-name').text(serviceName);
        if (selectedAddonNames.length) {
            $('#summary-addons-list').html(selectedAddonNames.map(n => '<span class="badge bg-white text-dark border me-1">+' + escapeHtml(n) + '</span>').join(' '));
        } else {
            $('#summary-addons-list').text('Ek hizmet seçilmedi');
        }

        // 3. Timing
        const totalDuration = baseDuration + totalAddonsDuration;
        $('#summary-duration-breakdown').text(totalDuration + ' dakika (' + baseDuration + ' dk temel' + (totalAddonsDuration > 0 ? ' + ' + totalAddonsDuration + ' dk ek' : '') + ')');
        
        let startStr = '-';
        try {
            const startObj = App.Utils.UI.getDateTimePickerValue($startDatetime);
            const endObj = App.Utils.UI.getDateTimePickerValue($endDatetime);
            if (startObj && endObj) {
                startStr = moment(startObj).format('DD MMM YYYY, HH:mm') + ' - ' + moment(endObj).format('HH:mm');
            }
        } catch(e) {}
        $('#summary-datetime').text(startStr);

        // 4. Provider & Station
        const providerId = $selectProvider.val();
        const provider = (vars('available_providers') || []).find((p) => Number(p.id) === Number(providerId));
        const providerName = provider ? provider.first_name + ' ' + provider.last_name : 'Seçilmedi';
        const stationText = $stationSelect.find('option:selected').text() || 'Atanmadı';
        $('#summary-provider-station').text(providerName + ' · ' + stationText);

        // 5. Pricing
        const totalPrice = basePrice + totalAddonsPrice;
        $('#summary-base-price').text(basePrice.toFixed(2) + ' ₺');
        $('#summary-addons-price').text('+' + totalAddonsPrice.toFixed(2) + ' ₺');
        $('#summary-total-price').text(totalPrice.toFixed(2) + ' ₺');

        // 6. Fast actions & badge
        if ($appointmentId.val()) {
            $('#summary-status-badge').text('Randevu #' + $appointmentId.val()).removeClass('bg-primary').addClass('bg-dark');
            $('#summary-fast-actions').removeClass('d-none');
        } else {
            $('#summary-status-badge').text('Yeni Randevu').removeClass('bg-dark').addClass('bg-primary');
            $('#summary-fast-actions').addClass('d-none');
        }
    }

    /**
     * Load add-ons for the selected service into the booking form.
     */
    function loadServiceAddons(serviceId) {
        const $container = $('#service-addons-selector');
        const $list = $('#addons-checkbox-list');
        $list.empty();

        if (!serviceId) {
            $container.addClass('d-none');
            return;
        }

        $.get(App.Utils.Url.siteUrl('services/get_addons/' + serviceId))
            .done((addons) => {
                if (!addons || !addons.length) {
                    $container.addClass('d-none');
                    return;
                }
                $container.removeClass('d-none');
                addons.forEach((addon) => {
                    const label = `${escapeHtml(addon.name)} (+${addon.duration_minutes} dk, +${Number(addon.price).toFixed(2)} ₺)`;
                    const checkboxHtml = `
                        <div class="form-check form-check-inline bg-white px-2 py-1 border rounded me-2 mb-2">
                            <input class="form-check-input addon-checkbox" type="checkbox" id="addon-check-${addon.id}" 
                                   data-id="${addon.id}" data-name="${escapeHtml(addon.name)}" 
                                   data-duration="${addon.duration_minutes}" data-price="${addon.price}">
                            <label class="form-check-label small fw-semibold" for="addon-check-${addon.id}">
                                ${label}
                            </label>
                        </div>
                    `;
                    $list.append(checkboxHtml);
                });
                updateLiveSummary();
            })
            .fail(() => {
                $container.addClass('d-none');
            });
    }

    /**
     * Display Customer Context banner with 360 preview and VIP status.
     */
    function displayCustomerContext(customer) {
        const $banner = $('#customer-context-banner');
        if (!customer || !customer.id) {
            $banner.addClass('d-none');
            return;
        }

        const fullName = [(customer.first_name || ''), (customer.last_name || '')].join(' ').trim() || 'Müşteri #' + customer.id;
        $('#ctx-cust-avatar').text(fullName.charAt(0).toUpperCase());
        $('#ctx-cust-name').text(fullName);
        $('#ctx-cust-phone').text(customer.phone_number || '-');
        $('#ctx-cust-email').text(customer.email || '-');

        $('#btn-open-ctx-360').off('click').on('click', () => {
            if (window.openCustomer360) {
                window.openCustomer360(customer.id);
            }
        });

        // Check customer packages & VIP from 360 endpoint
        $.get(App.Utils.Url.siteUrl('customers/get_360/' + customer.id))
            .done((res) => {
                if (res && res.success) {
                    const packages = res.packages || [];
                    const activePkg = packages.find(p => Number(p.remaining_sessions) > 0);
                    if (activePkg) {
                        $('#ctx-cust-pkg-badge').text('Paket: ' + activePkg.package_name + ' (' + activePkg.remaining_sessions + ' Seans)').removeClass('d-none');
                    } else {
                        $('#ctx-cust-pkg-badge').addClass('d-none');
                    }

                    const metrics = res.metrics || {};
                    if (Number(metrics.completed_appointments) >= 5 || Number(metrics.total_spend) > 3000) {
                        $('#ctx-cust-vip-badge').removeClass('d-none');
                    } else {
                        $('#ctx-cust-vip-badge').addClass('d-none');
                    }
                }
            });

        $banner.removeClass('d-none');
        updateLiveSummary();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /**
     * Salon Flora customization - providers must only ever see a customer's first name, never surname, email,
     * phone or address.
     */
    function applyCustomerPrivacyRestrictions() {
        if (vars('role_slug') !== App.Layouts.Backend.DB_SLUG_PROVIDER) {
            return;
        }

        ['#last-name', '#email', '#phone-number', '#address', '#city', '#state', '#zip-code'].forEach((selector) => {
            $(selector)
                .closest('.mb-3')
                .addClass('d-none')
                .find(selector)
                .removeClass('required')
                .prop('disabled', true);
        });

        $('#select-customer, #filter-existing-customers').addClass('d-none');
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        addEventListeners();
        applyCustomerPrivacyRestrictions();

        // Bind live summary updates to inputs
        $appointmentsModal.on('input change', 'input, select, textarea', () => {
            updateLiveSummary();
        });

        $appointmentsModal.on('change', '.addon-checkbox', function () {
            // Recompute end datetime based on added addon duration
            const serviceId = $selectService.val();
            const service = (vars('available_services') || []).find(s => Number(s.id) === Number(serviceId));
            let totalMins = service ? Number(service.duration) : 30;
            if ($customDuration.val()) {
                totalMins = Number($customDuration.val());
            }

            $('.addon-checkbox:checked').each(function () {
                totalMins += Number($(this).data('duration') || 0);
            });

            try {
                const startObj = App.Utils.UI.getDateTimePickerValue($startDatetime);
                if (startObj) {
                    const newEnd = new Date(startObj.getTime() + totalMins * 60000);
                    App.Utils.UI.setDateTimePickerValue($endDatetime, newEnd);
                }
            } catch(e) {}

            updateLiveSummary();
        });

        // Fast open adisyon button
        $('#btn-fast-open-adisyon').on('click', () => {
            const aptId = $appointmentId.val();
            if (aptId) {
                window.location.href = App.Utils.Url.siteUrl('adisyons/create_for_appointment/' + aptId);
            }
        });

        $selectService.on('change', function () {
            loadServiceAddons($(this).val());
            updateLiveSummary();
        });
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        resetModal,
        validateAppointmentForm,
        displaySessionTracking,
        updateLiveSummary,
        displayCustomerContext,
    };
})();
