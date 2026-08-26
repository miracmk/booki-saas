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
 * Providers page.
 *
 * This module implements the functionality of the providers page.
 */
App.Pages.Providers = (function () {
    const $providers = $('#providers');
    const $id = $('#id');
    const $firstName = $('#first-name');
    const $lastName = $('#last-name');
    const $email = $('#email');
    const $mobileNumber = $('#mobile-number');
    const $phoneNumber = $('#phone-number');
    const $address = $('#address');
    const $city = $('#city');
    const $state = $('#state');
    const $zipCode = $('#zip-code');
    const $isPrivate = $('#is-private');
    const $notes = $('#notes');
    const $language = $('#language');
    const $timezone = $('#timezone');
    const $ldapDn = $('#ldap-dn');
    const $username = $('#username');
    const $password = $('#password');
    const $passwordConfirmation = $('#password-confirm');
    const $notifications = $('#notifications');
    const $calendarView = $('#calendar-view');
    const $commissionType = $('#commission-type'); // Salon Flora customization
    const $commissionValue = $('#commission-value'); // Salon Flora customization
    const $commissionOvertimeBonus = $('#commission-overtime-bonus'); // Salon Flora customization
    const $filterProviders = $('#filter-providers');
    let filterResults = {};
    let filterLimit = 20;
    let workingPlanManager;

    /**
     * Add the page event listeners.
     */
    function addEventListeners() {
        /**
         * Event: Filter Providers Form "Submit"
         *
         * Filter the provider records with the given key string.
         *
         * @param {jQuery.Event} event
         */
        $providers.on('submit', '#filter-providers form', (event) => {
            event.preventDefault();
            const key = $('#filter-providers .key').val();
            $('.selected').removeClass('selected');
            App.Pages.Providers.resetForm();
            App.Pages.Providers.filter(key);
        });

        /**
         * Event: Filter Provider Row "Click"
         *
         * Display the selected provider data to the user.
         */
        $providers.on('click', '.provider-row', (event) => {
            if ($filterProviders.find('.filter').prop('disabled')) {
                $filterProviders.find('.results').css('color', '#AAA');
                return; // Exit because we are currently on edit mode.
            }

            const providerId = $(event.currentTarget).attr('data-id');
            const provider = filterResults.find((filterResult) => Number(filterResult.id) === Number(providerId));

            App.Pages.Providers.display(provider);
            $filterProviders.find('.selected').removeClass('selected');
            $(event.currentTarget).addClass('selected');
            $('#edit-provider, #delete-provider, #anonymize-provider').prop('disabled', false);

            // Automatically enter edit mode
            $('#providers-page').addClass('editing');
            $providers.find('.add-edit-delete-group').hide();
            $providers.find('.save-cancel-group').show();
            $providers.find('#delete-provider, #anonymize-provider').show(); // Show delete/anonymize buttons when editing
            $filterProviders.find('button').prop('disabled', true);
            $filterProviders.find('.results').css('color', '#AAA');
            $providers.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $providers.find('.record-details .form-label span').prop('hidden', false);
            $('#password, #password-confirm').removeClass('required');
            $('#provider-services input:checkbox').prop('disabled', false);
            $('#select-all-services, #select-none-services').prop('disabled', false);
            $('#provider-stations input:checkbox').prop('disabled', false); // Salon Flora customization
            $('#select-all-stations, #select-none-stations').prop('disabled', false); // Salon Flora customization
            $('#provider-skills input:checkbox').prop('disabled', false); // Ki Reservation (2026-08-26)
            $('#select-all-skills, #select-none-skills, #new-skill-name, #add-skill-button').prop('disabled', false); // Ki Reservation (2026-08-26)
            $('#provider-service-commissions select, #provider-service-commissions input').prop('disabled', false); // Salon Flora customization
            $providers
                .find(
                    '.add-break, .edit-break, .delete-break, .add-working-plan-exception, .edit-working-plan-exception, .delete-working-plan-exception, #reset-working-plan',
                )
                .prop('disabled', false);
            $('#providers input:checkbox').prop('disabled', false);
            workingPlanManager.timepickers(false);
        });

        /**
         * Event: Add New Provider Button "Click"
         */
        $providers.on('click', '#add-provider', () => {
            App.Pages.Providers.resetForm();
            $('#providers-page').addClass('editing');
            $filterProviders.find('button').prop('disabled', true);
            $filterProviders.find('.results').css('color', '#AAA');
            $providers.find('.add-edit-delete-group').hide();
            $providers.find('.save-cancel-group').show();
            $providers.find('#delete-provider, #anonymize-provider').hide(); // Hide delete/anonymize buttons when adding
            $providers.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $providers.find('.record-details .form-label span').prop('hidden', false);
            $('#password, #password-confirm').addClass('required');
            $providers
                .find(
                    '.add-break, .edit-break, .delete-break, .add-working-plan-exception, .edit-working-plan-exception, .delete-working-plan-exception, #reset-working-plan',
                )
                .prop('disabled', false);
            $('#provider-services input:checkbox').prop('disabled', false);
            $('#select-all-services, #select-none-services').prop('disabled', false);
            $('#provider-stations input:checkbox').prop('disabled', false); // Salon Flora customization
            $('#select-all-stations, #select-none-stations').prop('disabled', false); // Salon Flora customization
            $('#provider-skills input:checkbox').prop('disabled', false); // Ki Reservation (2026-08-26)
            $('#select-all-skills, #select-none-skills, #new-skill-name, #add-skill-button').prop('disabled', false); // Ki Reservation (2026-08-26)
            $('#provider-service-commissions select, #provider-service-commissions input').prop('disabled', false); // Salon Flora customization

            // Apply default working plan
            const companyWorkingPlan = JSON.parse(vars('company_working_plan'));
            workingPlanManager.setup(companyWorkingPlan);
            workingPlanManager.timepickers(false);
        });

        /**
         * Event: Edit Provider Button "Click"
         */
        $providers.on('click', '#edit-provider', () => {
            $('#providers-page').addClass('editing');
            $providers.find('.add-edit-delete-group').hide();
            $providers.find('.save-cancel-group').show();
            $filterProviders.find('button').prop('disabled', true);
            $filterProviders.find('.results').css('color', '#AAA');
            $providers.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $providers.find('.record-details .form-label span').prop('hidden', false);
            $('#password, #password-confirm').removeClass('required');
            $('#provider-services input:checkbox').prop('disabled', false);
            $('#select-all-services, #select-none-services').prop('disabled', false);
            $('#provider-stations input:checkbox').prop('disabled', false); // Salon Flora customization
            $('#select-all-stations, #select-none-stations').prop('disabled', false); // Salon Flora customization
            $('#provider-skills input:checkbox').prop('disabled', false); // Ki Reservation (2026-08-26)
            $('#select-all-skills, #select-none-skills, #new-skill-name, #add-skill-button').prop('disabled', false); // Ki Reservation (2026-08-26)
            $('#provider-service-commissions select, #provider-service-commissions input').prop('disabled', false); // Salon Flora customization
            $providers
                .find(
                    '.add-break, .edit-break, .delete-break, .add-working-plan-exception, .edit-working-plan-exception, .delete-working-plan-exception, #reset-working-plan',
                )
                .prop('disabled', false);
            $('#providers input:checkbox').prop('disabled', false);
            workingPlanManager.timepickers(false);
        });

        /**
         * Event: Delete Provider Button "Click"
         */
        $providers.on('click', '#delete-provider', () => {
            const providerId = $id.val();

            const buttons = [
                {
                    text: lang('cancel'),
                    click: (event, messageModal) => {
                        messageModal.hide();
                    },
                },
                {
                    text: lang('delete'),
                    click: (event, messageModal) => {
                        App.Pages.Providers.remove(providerId);
                        messageModal.hide();
                    },
                },
            ];

            App.Utils.Message.show(lang('delete_provider'), lang('delete_record_prompt'), buttons);
        });

        /**
         * Salon Flora customization (2026-08-24, KVKK) - Event: "KVKK - Unutulma Hakkı" Button "Click"
         */
        $providers.on('click', '#anonymize-provider', () => {
            const providerId = $id.val();

            const buttons = [
                {
                    text: lang('cancel'),
                    click: (event, messageModal) => {
                        messageModal.hide();
                    },
                },
                {
                    text: 'Anonimleştir',
                    click: (event, messageModal) => {
                        App.Pages.Providers.anonymizeProvider(providerId);
                        messageModal.hide();
                    },
                },
            ];

            App.Utils.Message.show(
                'Terapisti Anonimleştir (KVKK - Unutulma Hakkı)',
                'Bu terapistin ad, telefon, e-posta, adres ve not bilgileri KALICI olarak silinecek. ' +
                    'Randevu ve komisyon geçmişi (muhasebe kayıtları için) korunacak, ancak artık bu terapiste ait olduğu görünmeyecek. Bu işlem GERİ ALINAMAZ. Devam edilsin mi?',
                buttons,
            );
        });

        /**
         * Event: Save Provider Button "Click"
         */
        $providers.on('click', '#save-provider', () => {
            const workingPlan = workingPlanManager.get();

            if (workingPlan === null) {
                return;
            }

            const provider = {
                first_name: $firstName.val(),
                last_name: $lastName.val(),
                email: $email.val(),
                mobile_number: $mobileNumber.val(),
                phone_number: $phoneNumber.val(),
                address: $address.val(),
                city: $city.val(),
                state: $state.val(),
                zip_code: $zipCode.val(),
                is_private: Number($isPrivate.prop('checked')),
                notes: $notes.val(),
                language: $language.val(),
                timezone: $timezone.val(),
                ldap_dn: $ldapDn.val(),
                commission_type: $commissionType.val(), // Salon Flora customization
                commission_value: $commissionValue.val() || 0, // Salon Flora customization
                commission_overtime_bonus: $commissionOvertimeBonus.val() || 0, // Salon Flora customization
                settings: {
                    username: $username.val(),
                    working_plan: JSON.stringify(workingPlan),
                    working_plan_exceptions: JSON.stringify(workingPlanManager.getWorkingPlanExceptions()),
                    notifications: Number($notifications.prop('checked')),
                    calendar_view: $calendarView.val(),
                },
            };

            // Include provider services.
            provider.services = [];
            $('#provider-services input:checkbox').each((index, checkboxEl) => {
                if ($(checkboxEl).prop('checked')) {
                    provider.services.push($(checkboxEl).attr('data-id'));
                }
            });

            // Salon Flora customization: include provider stations (a provider may be assigned to more than one).
            provider.stations = [];
            $('#provider-stations input:checkbox').each((index, checkboxEl) => {
                if ($(checkboxEl).prop('checked')) {
                    provider.stations.push($(checkboxEl).attr('data-id'));
                }
            });

            // Salon Flora customization: when enabled, the stations selected above narrow (rather than being
            // ignored in favor of) the assigned service's own station list.
            provider.station_restriction_enabled = $('#station-restriction-enabled').prop('checked');

            // Ki Reservation (2026-08-26): include provider skills.
            provider.skills = [];
            $('#provider-skills input:checkbox').each((index, checkboxEl) => {
                if ($(checkboxEl).prop('checked')) {
                    provider.skills.push($(checkboxEl).attr('data-id'));
                }
            });

            // Salon Flora customization: include per-service commission overrides (only rows where a type was
            // actually chosen - an empty type means "use the default commission above").
            provider.service_commissions = [];
            $('#provider-service-commissions .service-commission-row').each((index, rowEl) => {
                const $row = $(rowEl);
                const commissionType = $row.find('.commission-type').val();

                if (!commissionType) {
                    return;
                }

                provider.service_commissions.push({
                    id_services: $row.attr('data-id-services'),
                    commission_type: commissionType,
                    commission_value: $row.find('.commission-value').val() || 0,
                });
            });

            // Include password if changed.
            if ($password.val() !== '') {
                provider.settings.password = $password.val();
            }

            // Include id if changed.
            if ($id.val() !== '') {
                provider.id = $id.val();
            }

            if (!App.Pages.Providers.validate()) {
                return;
            }

            App.Pages.Providers.save(provider);
        });

        /**
         * Event: Cancel Provider Button "Click"
         *
         * Cancel add or edit of an provider record.
         */
        $providers.on('click', '#cancel-provider', () => {
            const id = $('#filter-providers .selected').attr('data-id');
            App.Pages.Providers.resetForm();
            $('#providers-page').removeClass('editing');
            if (id) {
                App.Pages.Providers.select(id, true);
            }
        });

        /**
         * Event: Reset Working Plan Button "Click".
         */
        $providers.on('click', '#reset-working-plan', () => {
            $('.breaks tbody').empty();
            $('.working-plan-exceptions tbody').empty();
            $('.work-start, .work-end').val('');
            const companyWorkingPlan = JSON.parse(vars('company_working_plan'));
            workingPlanManager.setup(companyWorkingPlan);
            workingPlanManager.timepickers(false);
        });

        /**
         * Event: Select All Services Button "Click"
         */
        $providers.on('click', '#select-all-services', () => {
            $('#provider-services input:checkbox').prop('checked', true);
            App.Pages.Providers.renderServiceCommissions(); // Salon Flora customization
        });

        /**
         * Event: Select None Services Button "Click"
         */
        $providers.on('click', '#select-none-services', () => {
            $('#provider-services input:checkbox').prop('checked', false);
            App.Pages.Providers.renderServiceCommissions(); // Salon Flora customization
        });

        /**
         * Salon Flora customization - Event: Select All/None Stations Button "Click"
         */
        $providers.on('click', '#select-all-stations', () => {
            $('#provider-stations input:checkbox').prop('checked', true);
        });

        $providers.on('click', '#select-none-stations', () => {
            $('#provider-stations input:checkbox').prop('checked', false);
        });

        /**
         * Ki Reservation (2026-08-26) - Event: Select All/None Skills Button "Click"
         */
        $providers.on('click', '#select-all-skills', () => {
            $('#provider-skills input:checkbox').prop('checked', true);
        });

        $providers.on('click', '#select-none-skills', () => {
            $('#provider-skills input:checkbox').prop('checked', false);
        });

        /**
         * Ki Reservation (2026-08-26) - Event: Add New Skill Button "Click"
         *
         * Finds or creates the skill by name on the server, then appends (or checks, if it already
         * existed) its checkbox in the #provider-skills list, so it can be assigned without a
         * dedicated catalog management page.
         */
        $providers.on('click', '#add-skill-button', () => {
            const $input = $('#new-skill-name');
            const name = $input.val().trim();

            if (!name) {
                return;
            }

            App.Http.Providers.createSkill(name)
                .done((response) => {
                    if (!response.success) {
                        App.Utils.Message.show(lang('unexpected_issues'), response.message, 'error');
                        return;
                    }

                    const existingCheckbox = $(`#provider-skills input[data-id="${response.id}"]`);

                    if (existingCheckbox.length) {
                        existingCheckbox.prop('checked', true);
                    } else {
                        App.Pages.Providers.appendSkillCheckbox({ value: response.id, label: response.name }, true);
                    }

                    $input.val('');
                })
                .fail(() => {
                    App.Utils.Message.show(lang('unexpected_issues'), lang('service_communication_error'), 'error');
                });
        });
    }

    /**
     * Save provider record to database.
     *
     * @param {Object} provider Contains the provider record data. If an 'id' value is provided
     * then the update operation is going to be executed.
     */
    function save(provider) {
        App.Http.Providers.save(provider).then((response) => {
            App.Layouts.Backend.displayNotification(lang('provider_saved'));
            App.Pages.Providers.resetForm();
            $('#providers-page').removeClass('editing');
            $('#filter-providers .key').val('');
            App.Pages.Providers.filter('', response.id, true);
        });
    }

    /**
     * Delete a provider record from database.
     *
     * @param {Number} id Record id to be deleted.
     */
    function remove(id) {
        App.Http.Providers.destroy(id).then(() => {
            App.Layouts.Backend.displayNotification(lang('provider_deleted'));
            App.Pages.Providers.resetForm();
            $('#providers-page').removeClass('editing');
            App.Pages.Providers.filter($('#filter-providers .key').val());
        });
    }

    /**
     * Salon Flora customization (2026-08-24, KVKK data retention) - anonymize an (ex-)provider's PII
     * in place, keeping the record and its appointment/commission history intact for accounting
     * purposes. See Providers_model::anonymize() for the full rationale.
     *
     * @param {Number} id Record id to be anonymized.
     */
    function anonymizeProvider(id) {
        const url = App.Utils.Url.siteUrl('providers/anonymize');

        $.post(url, {csrf_token: vars('csrf_token'), provider_id: id}).done(() => {
            App.Layouts.Backend.displayNotification('Terapist kişisel verileri anonimleştirildi.');
            App.Pages.Providers.resetForm();
            $('#providers-page').removeClass('editing');
            App.Pages.Providers.filter($('#filter-providers .key').val());
        });
    }

    /**
     * Validates a provider record.
     *
     * @return {Boolean} Returns the validation result.
     */
    function validate() {
        $providers.find('.is-invalid').removeClass('is-invalid');
        $providers.find('.form-message').removeClass('alert-danger').hide();

        try {
            // Validate required fields.
            let missingRequired = false;

            $providers.find('.required').each((index, requiredFieldEl) => {
                if (!$(requiredFieldEl).val()) {
                    $(requiredFieldEl).addClass('is-invalid');
                    missingRequired = true;
                }
            });

            if (missingRequired) {
                throw new Error(lang('fields_are_required'));
            }

            // Validate passwords.
            if ($password.val() !== $passwordConfirmation.val()) {
                $('#password, #password-confirm').addClass('is-invalid');
                throw new Error(lang('passwords_mismatch'));
            }

            if ($password.val().length < vars('min_password_length') && $password.val() !== '') {
                $('#password, #password-confirm').addClass('is-invalid');
                throw new Error(lang('password_length_notice').replace('$number', vars('min_password_length')));
            }

            // Validate user email.
            if (!App.Utils.Validation.email($email.val())) {
                $email.addClass('is-invalid');
                throw new Error(lang('invalid_email'));
            }

            // Validate phone number.
            const phoneNumber = $phoneNumber.val();

            if (phoneNumber && !App.Utils.Validation.phone(phoneNumber)) {
                $phoneNumber.addClass('is-invalid');
                throw new Error(lang('invalid_phone'));
            }

            // Validate mobile number.
            const mobileNumber = $mobileNumber.val();

            if (mobileNumber && !App.Utils.Validation.phone(mobileNumber)) {
                $mobileNumber.addClass('is-invalid');
                throw new Error(lang('invalid_phone'));
            }

            // Check if username exists
            if ($username.attr('already-exists') === 'true') {
                $username.addClass('is-invalid');
                throw new Error(lang('username_already_exists'));
            }

            return true;
        } catch (error) {
            $('#providers .form-message').addClass('alert-danger').text(error.message).show();
            return false;
        }
    }

    /**
     * Resets the provider tab form back to its initial state.
     */
    function resetForm() {
        $filterProviders.find('.selected').removeClass('selected');
        $filterProviders.find('button').prop('disabled', false);
        $filterProviders.find('.results').css('color', '');

        $providers.find('.add-edit-delete-group').show();
        $providers.find('.save-cancel-group').hide();
        $providers.find('.record-details h4 a').remove();
        $providers.find('.record-details').find('input, select, textarea').val('').prop('disabled', true);
        $providers.find('.record-details .form-label span').prop('hidden', true);
        $providers.find('.record-details #calendar-view').val('default');
        $providers.find('.record-details #language').val(vars('default_language'));
        $providers.find('.record-details #timezone').val(vars('default_timezone'));
        $providers.find('.record-details #commission-type').val('percentage'); // Salon Flora customization
        $providers.find('.record-details #is-private').prop('checked', false);
        $providers.find('.record-details #notifications').prop('checked', true);
        $providers.find('.add-break, .add-working-plan-exception, #reset-working-plan').prop('disabled', true);

        workingPlanManager.timepickers(true);
        $providers.find('#providers .working-plan input:checkbox').prop('disabled', true);
        $('.breaks').find('.edit-break, .delete-break').prop('disabled', true);
        $('.working-plan-exceptions')
            .find('.edit-working-plan-exception, .delete-working-plan-exception')
            .prop('disabled', true);

        $providers.find('.record-details .is-invalid').removeClass('is-invalid');
        $providers.find('.record-details .form-message').hide();

        $('#edit-provider, #delete-provider, #anonymize-provider').prop('disabled', true);
        $('#provider-services input:checkbox').prop('disabled', true).prop('checked', false);
        $('#select-all-services, #select-none-services').prop('disabled', true);
        $('#provider-services a').remove();
        // Salon Flora customization
        $('#provider-stations input:checkbox').prop('disabled', true).prop('checked', false);
        $('#select-all-stations, #select-none-stations').prop('disabled', true);
        $('#station-restriction-enabled').prop('disabled', true).prop('checked', false);
        // Ki Reservation (2026-08-26)
        $('#provider-skills input:checkbox').prop('disabled', true).prop('checked', false);
        $('#select-all-skills, #select-none-skills, #new-skill-name, #add-skill-button').prop('disabled', true);
        App.Pages.Providers.renderServiceCommissions();
        $('#providers .working-plan tbody').empty();
        $('#providers .breaks tbody').empty();
        $('#providers .working-plan-exceptions tbody').empty();
    }

    /**
     * Display a provider record into the provider form.
     *
     * @param {Object} provider Contains the provider record data.
     */
    function display(provider) {
        $id.val(provider.id);
        $firstName.val(provider.first_name);
        $lastName.val(provider.last_name);
        $email.val(provider.email);
        $mobileNumber.val(provider.mobile_number);
        $phoneNumber.val(provider.phone_number);
        $address.val(provider.address);
        $city.val(provider.city);
        $state.val(provider.state);
        $zipCode.val(provider.zip_code);
        $isPrivate.prop('checked', provider.is_private);
        $notes.val(provider.notes);
        $language.val(provider.language);
        $timezone.val(provider.timezone);
        $ldapDn.val(provider.ldap_dn);
        $commissionType.val(provider.commission_type || 'percentage'); // Salon Flora customization
        $commissionValue.val(provider.commission_value || 0); // Salon Flora customization
        $commissionOvertimeBonus.val(provider.commission_overtime_bonus || 0); // Salon Flora customization

        $username.val(provider.settings.username);
        $calendarView.val(provider.settings.calendar_view);
        $notifications.prop('checked', Boolean(Number(provider.settings.notifications)));

        // Add dedicated provider link.
        let dedicatedUrl = App.Utils.Url.siteUrl('?provider=' + encodeURIComponent(provider.id));
        let $link = $('<a/>', {
            'href': dedicatedUrl,
            'target': '_blank',
            'data-bs-toggle': 'tooltip',
            'title': lang('booking_link'),
            'aria-label': lang('booking_link'),
            'html': [
                $('<i/>', {
                    'class': 'fas fa-link',
                }),
            ],
        });

        $providers.find('.details-view h4').find('a').remove().end().append($link);
        new bootstrap.Tooltip($link[0]);

        $('#provider-services a').remove();
        $('#provider-services input:checkbox').prop('checked', false);

        provider.services.forEach((providerServiceId) => {
            const $checkbox = $('#provider-services input[data-id="' + providerServiceId + '"]');

            if (!$checkbox.length) {
                return;
            }

            $checkbox.prop('checked', true);

            // Add dedicated service-provider link.
            dedicatedUrl = App.Utils.Url.siteUrl(
                '?provider=' + encodeURIComponent(provider.id) + '&service=' + encodeURIComponent(providerServiceId),
            );

            $link = $('<a/>', {
                'href': dedicatedUrl,
                'target': '_blank',
                'class': 'ms-2',
                'data-bs-toggle': 'tooltip',
                'title': lang('booking_link'),
                'aria-label': lang('booking_link'),
                'html': [
                    $('<i/>', {
                        'class': 'fas fa-link',
                    }),
                ],
            });

            $checkbox.parent().append($link);
            new bootstrap.Tooltip($link[0]);
        });

        // Salon Flora customization: a provider may be assigned to more than one station.
        $('#provider-stations input:checkbox').prop('checked', false);
        (provider.stations || []).forEach((providerStationId) => {
            $('#provider-stations input[data-id="' + providerStationId + '"]').prop('checked', true);
        });
        $('#station-restriction-enabled').prop('checked', Number(provider.station_restriction_enabled) === 1);

        // Ki Reservation (2026-08-26): a provider may have more than one skill.
        $('#provider-skills input:checkbox').prop('checked', false);
        (provider.skills || []).forEach((providerSkillId) => {
            $('#provider-skills input[data-id="' + providerSkillId + '"]').prop('checked', true);
        });

        // Salon Flora customization: rebuild the per-service commission rows for the currently assigned services,
        // pre-filling any existing overrides.
        App.Pages.Providers.renderServiceCommissions(provider);

        // Display working plan
        const workingPlan = JSON.parse(provider.settings.working_plan);
        workingPlanManager.setup(workingPlan);
        $('.working-plan').find('input').prop('disabled', true);
        $('.breaks').find('.edit-break, .delete-break').prop('disabled', true);
        $providers.find('.working-plan-exceptions tbody').empty();
        const workingPlanExceptions = JSON.parse(provider.settings.working_plan_exceptions);
        workingPlanManager.setupWorkingPlanExceptions(workingPlanExceptions);
        $('.working-plan-exceptions')
            .find('.edit-working-plan-exception, .delete-working-plan-exception')
            .prop('disabled', true);
        $providers.find('.working-plan input:checkbox').prop('disabled', true);
    }

    /**
     * Filters provider records depending a string keyword.
     *
     * @param {string} keyword This is used to filter the provider records of the database.
     * @param {numeric} selectId Optional, if set, when the function is complete a result row can be set as selected.
     * @param {bool} show Optional (false), if true the selected record will be also displayed.
     */
    function filter(keyword, selectId = null, show = false) {
        App.Http.Providers.search(keyword, filterLimit).then((response) => {
            filterResults = response;

            $filterProviders.find('.results').empty();
            response.forEach((provider) => {
                $('#filter-providers .results').append(App.Pages.Providers.getFilterHtml(provider)).append($('<hr/>'));
            });

            if (!response.length) {
                $filterProviders.find('.results').append(
                    $('<em/>', {
                        'text': lang('no_records_found'),
                    }),
                );
            } else if (response.length === filterLimit) {
                $('<button/>', {
                    'type': 'button',
                    'class': 'btn btn-outline-secondary w-100 load-more text-center',
                    'text': lang('load_more'),
                    'click': () => {
                        filterLimit += 20;
                        App.Pages.Providers.filter(keyword, selectId, show);
                    },
                }).appendTo('#filter-providers .results');
            }

            if (selectId) {
                App.Pages.Providers.select(selectId, show);
            }
        });
    }

    /**
     * Get an provider row html code that is going to be displayed on the filter results list.
     *
     * @param {Object} provider Contains the provider record data.
     *
     * @return {String} The html code that represents the record on the filter results list.
     */
    function getFilterHtml(provider) {
        const name = provider.first_name + ' ' + provider.last_name;

        let info = provider.email;

        info = provider.mobile_number ? info + ', ' + provider.mobile_number : info;

        info = provider.phone_number ? info + ', ' + provider.phone_number : info;

        return $('<div/>', {
            'class': 'provider-row entry',
            'data-id': provider.id,
            'html': [
                $('<strong/>', {
                    'text': name,
                }),
                $('<br/>'),
                $('<small/>', {
                    'class': 'text-muted',
                    'text': info,
                }),
                $('<br/>'),
            ],
        });
    }

    /**
     * Select and display a providers filter result on the form.
     *
     * @param {Number} id Record id to be selected.
     * @param {Boolean} show Optional (false), if true the record will be displayed on the form.
     */
    function select(id, show = false) {
        // Select record in filter results.
        $filterProviders.find('.provider-row[data-id="' + id + '"]').addClass('selected');

        // Display record in form (if display = true).
        if (show) {
            const provider = filterResults.find((filterResult) => Number(filterResult.id) === Number(id));

            App.Pages.Providers.display(provider);

            $('#edit-provider, #delete-provider, #anonymize-provider').prop('disabled', false);
        }
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        workingPlanManager = new App.Utils.WorkingPlan();
        workingPlanManager.addEventListeners();

        App.Pages.Providers.resetForm();
        App.Pages.Providers.filter('');
        App.Pages.Providers.addEventListeners();

        vars('services').forEach((service) => {
            const checkboxId = `provider-service-${service.id}`;

            $('<div/>', {
                'class': 'checkbox',
                'html': [
                    $('<div/>', {
                        'class': 'checkbox form-check',
                        'html': [
                            $('<input/>', {
                                'id': checkboxId,
                                'class': 'form-check-input',
                                'type': 'checkbox',
                                'data-id': service.id,
                                'prop': {
                                    'disabled': true,
                                },
                            }),
                            $('<label/>', {
                                'class': 'form-check-label',
                                'text': App.Pages.Providers.serviceLabel(service),
                                'for': checkboxId,
                            }),
                        ],
                    }),
                ],
            }).appendTo('#provider-services');
        });

        // Salon Flora customization: render the station checkboxes (a provider may be assigned to more than one).
        (vars('stations') || []).forEach((station) => {
            const checkboxId = `provider-station-${station.value}`;

            $('<div/>', {
                'class': 'checkbox form-check',
                'html': [
                    $('<input/>', {
                        'id': checkboxId,
                        'class': 'form-check-input',
                        'type': 'checkbox',
                        'data-id': station.value,
                        'prop': {
                            'disabled': true,
                        },
                    }),
                    $('<label/>', {
                        'class': 'form-check-label',
                        'text': station.label,
                        'for': checkboxId,
                    }),
                ],
            }).appendTo('#provider-stations');
        });

        // Ki Reservation (2026-08-26): render the skill checkboxes.
        (vars('skills') || []).forEach((skill) => {
            App.Pages.Providers.appendSkillCheckbox(skill, false);
        });

        // Salon Flora customization: whenever the assigned services change, rebuild the commission override rows
        // so they always match the currently checked services.
        $providers.on('change', '#provider-services input:checkbox', () => {
            App.Pages.Providers.renderServiceCommissions();
        });
    }

    /**
     * Ki Reservation (2026-08-26) - append one skill checkbox to #provider-skills. Used both for the
     * initial render (from vars('skills')) and when an admin adds a brand-new skill inline (see the
     * '#add-skill-button' click handler).
     *
     * @param {Object} skill {value, label}.
     * @param {Boolean} checked Whether the checkbox starts checked (true right after an admin adds it).
     */
    function appendSkillCheckbox(skill, checked) {
        const checkboxId = `provider-skill-${skill.value}`;

        $('<div/>', {
            'class': 'checkbox form-check',
            'html': [
                $('<input/>', {
                    'id': checkboxId,
                    'class': 'form-check-input',
                    'type': 'checkbox',
                    'data-id': skill.value,
                    'prop': {
                        'disabled': false,
                        'checked': checked,
                    },
                }),
                $('<label/>', {
                    'class': 'form-check-label',
                    'text': skill.label,
                    'for': checkboxId,
                }),
            ],
        }).appendTo('#provider-skills');
    }

    /**
     * Salon Flora customization - label a service with its duration, so duration variants of the same service
     * (e.g. "Klasik Masaj" 60/90/120 dk) are distinguishable in checkboxes and commission rows.
     *
     * @param {Object} service Service data (id, name, duration, ...).
     *
     * @return {String} Returns the label text.
     */
    function serviceLabel(service) {
        return service.duration ? `${service.name} — ${service.duration} dk` : service.name;
    }

    /**
     * Salon Flora customization - rebuild the per-service commission override rows to match the currently checked
     * service checkboxes, preserving values already entered and pre-filling from the provider's saved overrides.
     *
     * @param {Object|null} provider Optional provider data (used right after display() to pre-fill saved
     * overrides); omit to just resync the rows with the checked services while editing.
     */
    function renderServiceCommissions(provider = null) {
        const $container = $('#provider-service-commissions');

        // Preserve whatever the admin already typed before rebuilding.
        const existingValues = {};
        $container.find('.service-commission-row').each((index, rowEl) => {
            const $row = $(rowEl);
            existingValues[$row.attr('data-id-services')] = {
                commission_type: $row.find('.commission-type').val(),
                commission_value: $row.find('.commission-value').val(),
            };
        });

        const savedOverrides = {};
        (provider ? provider.service_commissions || [] : []).forEach((override) => {
            savedOverrides[override.id_services] = override;
        });

        const disabled = $('#provider-services input:checkbox').first().prop('disabled');

        $container.empty();

        $('#provider-services input:checkbox:checked').each((index, checkboxEl) => {
            const serviceId = $(checkboxEl).attr('data-id');
            const service = vars('services').find((s) => Number(s.id) === Number(serviceId));

            if (!service) {
                return;
            }

            const saved = existingValues[serviceId] || savedOverrides[serviceId] || {};

            const $typeSelect = $('<select/>', {
                'class': 'form-select form-select-sm commission-type',
                'style': 'max-width:150px;',
                'prop': {'disabled': disabled},
                'html': [
                    $('<option/>', {value: '', text: 'Varsayılan'}),
                    $('<option/>', {value: 'percentage', text: 'Yüzde (%)'}),
                    $('<option/>', {value: 'fixed', text: 'Sabit Tutar (TL)'}),
                    $('<option/>', {value: 'hourly', text: 'Saatlik (TL/saat)'}),
                ],
            }).val(saved.commission_type || '');

            const $valueInput = $('<input/>', {
                'type': 'number',
                'step': '0.01',
                'min': '0',
                'class': 'form-control form-control-sm commission-value',
                'style': 'max-width:120px;',
                'placeholder': '0.00',
                'prop': {'disabled': disabled},
                'val': saved.commission_value || '',
            });

            $('<div/>', {
                'class': 'service-commission-row d-flex align-items-center gap-2 mb-2',
                'data-id-services': serviceId,
                'html': [
                    $('<div/>', {'class': 'flex-grow-1 small', 'text': App.Pages.Providers.serviceLabel(service)}),
                    $typeSelect,
                    $valueInput,
                ],
            }).appendTo($container);
        });

        if (!$container.children().length) {
            $container.append(
                $('<div/>', {'class': 'text-muted small', 'text': 'Önce yukarıdan hizmet seçin.'}),
            );
        }
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        filter,
        save,
        remove,
        anonymizeProvider,
        validate,
        getFilterHtml,
        resetForm,
        display,
        select,
        addEventListeners,
        serviceLabel,
        renderServiceCommissions,
        appendSkillCheckbox,
    };
})();
