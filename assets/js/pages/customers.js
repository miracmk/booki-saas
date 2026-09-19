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
 * Customers page.
 *
 * This module implements the functionality of the customers page.
 */
App.Pages.Customers = (function () {
    const $customers = $('#customers');
    const $filterCustomers = $('#filter-customers');
    const $id = $('#customer-id');
    const $firstName = $('#first-name');
    const $lastName = $('#last-name');
    const $email = $('#email');
    const $phoneNumber = $('#phone-number');
    const $address = $('#address');
    const $city = $('#city');
    const $state = $('#state');
    const $zipCode = $('#zip-code');
    const $timezone = $('#timezone');
    const $language = $('#language');
    const $ldapDn = $('#ldap-dn');
    const $customField1 = $('#custom-field-1');
    const $customField2 = $('#custom-field-2');
    const $customField3 = $('#custom-field-3');
    const $customField4 = $('#custom-field-4');
    const $customField5 = $('#custom-field-5');
    const $notes = $('#notes');
    const $formMessage = $('#form-message');
    const $customerAppointments = $('#customer-appointments');

    // Salon Flora customization - CRM customer card.
    const $socialWhatsapp = $('#social-whatsapp');
    const $socialTelegram = $('#social-telegram');
    const $socialInstagram = $('#social-instagram');
    const $lastContactChannel = $('#last-contact-channel');
    const $notificationPreferenceMode = $('#notification-preference-mode');
    const $customNotificationChannels = $('#custom-notification-channels');
    const notificationChannelInputs = {
        email: $('#notify-email'),
        sms: $('#notify-sms'),
        call: $('#notify-call'),
        whatsapp: $('#notify-whatsapp'),
        telegram: $('#notify-telegram'),
        instagram: $('#notify-instagram'),
    };
    const $customerInsights = $('#customer-insights');

    const moment = window.moment;

    let filterResults = {};
    let filterLimit = 20;

    /**
     * Add the page event listeners.
     */
    function addEventListeners() {
        /**
         * Event: Filter Customers Form "Submit"
         *
         * @param {jQuery.Event} event
         */
        $customers.on('submit', '#filter-customers form', (event) => {
            event.preventDefault();
            const key = $filterCustomers.find('.key').val();
            $filterCustomers.find('.selected').removeClass('selected');
            filterLimit = 20;
            App.Pages.Customers.resetForm();
            App.Pages.Customers.filter(key);
        });

        /**
         * Event: Filter Entry "Click"
         *
         * Display the customer data of the selected row.
         *
         * @param {jQuery.Event} event
         */
        $customers.on('click', '.customer-row', (event) => {
            if ($filterCustomers.find('.filter').prop('disabled')) {
                return; // Do nothing when user edits a customer record.
            }

            const customerId = $(event.currentTarget).attr('data-id');
            const customer = filterResults.find((filterResult) => Number(filterResult.id) === Number(customerId));

            App.Pages.Customers.display(customer);
            $('#filter-customers .selected').removeClass('selected');
            $(event.currentTarget).addClass('selected');
            $('#edit-customer, #delete-customer, #anonymize-customer').prop('disabled', false);

            // Automatically enter edit mode
            $('#customers-page').addClass('editing');
            $customers.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $customers.find('.record-details .form-label span').prop('hidden', false);
            $customers.find('#add-edit-delete-group').hide();
            $customers.find('#save-cancel-group').show();
            $customers.find('#delete-customer, #anonymize-customer').show(); // Show delete/anonymize buttons when editing
            $filterCustomers.find('button').prop('disabled', true);
            $filterCustomers.find('.results').css('color', '#AAA');
        });

        /**
         * Event: Add Customer Button "Click"
         */
        $customers.on('click', '#add-customer', () => {
            App.Pages.Customers.resetForm();
            $('#customers-page').addClass('editing');
            $customers.find('#add-edit-delete-group').hide();
            $customers.find('#save-cancel-group').show();
            $customers.find('#delete-customer, #anonymize-customer').hide(); // Hide delete/anonymize buttons when adding
            $customers.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $customers.find('.record-details .form-label span').prop('hidden', false);
            $filterCustomers.find('button').prop('disabled', true);
            $filterCustomers.find('.results').css('color', '#AAA');
        });

        /**
         * Event: Edit Customer Button "Click"
         */
        $customers.on('click', '#edit-customer', () => {
            $('#customers-page').addClass('editing');
            $customers.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $customers.find('.record-details .form-label span').prop('hidden', false);
            $customers.find('#add-edit-delete-group').hide();
            $customers.find('#save-cancel-group').show();
            $filterCustomers.find('button').prop('disabled', true);
            $filterCustomers.find('.results').css('color', '#AAA');
        });

        $customers.on('change', '#notification-preference-mode', () => {
            $customNotificationChannels.toggle($notificationPreferenceMode.val() === 'custom');
        });

        /**
         * Event: Cancel Customer Add/Edit Operation Button "Click"
         */
        $customers.on('click', '#cancel-customer', () => {
            const id = $id.val();

            App.Pages.Customers.resetForm();
            $('#customers-page').removeClass('editing');

            if (id) {
                select(id, true);
            }
        });

        /**
         * Event: Save Add/Edit Customer Operation "Click"
         */
        $customers.on('click', '#save-customer', () => {
            const customer = {
                first_name: $firstName.val(),
                last_name: $lastName.val(),
                email: $email.val(),
                phone_number: $phoneNumber.val(),
                address: $address.val(),
                city: $city.val(),
                state: $state.val(),
                zip_code: $zipCode.val(),
                notes: $notes.val(),
                // Salon Flora customization - CRM customer card.
                social_links: JSON.stringify({
                    whatsapp: $socialWhatsapp.val() || null,
                    telegram: $socialTelegram.val() || null,
                    instagram: $socialInstagram.val() || null,
                }),
                last_contact_channel: $lastContactChannel.val() || null,
                notification_preferences: {
                    mode: $notificationPreferenceMode.val() || 'default',
                    email_enabled: notificationChannelInputs.email.prop('checked'),
                    sms_enabled: notificationChannelInputs.sms.prop('checked'),
                    call_enabled: notificationChannelInputs.call.prop('checked'),
                    whatsapp_enabled: notificationChannelInputs.whatsapp.prop('checked'),
                    telegram_enabled: notificationChannelInputs.telegram.prop('checked'),
                    instagram_enabled: notificationChannelInputs.instagram.prop('checked'),
                },
                timezone: $timezone.val(),
                language: $language.val() || 'english',
                custom_field_1: $customField1.val(),
                custom_field_2: $customField2.val(),
                custom_field_3: $customField3.val(),
                custom_field_4: $customField4.val(),
                custom_field_5: $customField5.val(),
                ldap_dn: $ldapDn.val(),
            };

            if ($id.val()) {
                customer.id = $id.val();
            }

            if (!App.Pages.Customers.validate()) {
                return;
            }

            App.Pages.Customers.save(customer);
        });

        /**
         * Event: Delete Customer Button "Click"
         */
        $customers.on('click', '#delete-customer', () => {
            const customerId = $id.val();
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
                        App.Pages.Customers.remove(customerId);
                        messageModal.hide();
                    },
                },
            ];

            App.Utils.Message.show(lang('delete_customer'), lang('delete_record_prompt'), buttons);
        });

        /**
         * Salon Flora customization (2026-08-24, KVKK) - Event: "KVKK - Unutulma Hakkı" Button "Click"
         */
        $customers.on('click', '#anonymize-customer', () => {
            const customerId = $id.val();
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
                        App.Pages.Customers.anonymizeCustomer(customerId);
                        messageModal.hide();
                    },
                },
            ];

            App.Utils.Message.show(
                'Müşteriyi Anonimleştir (KVKK - Unutulma Hakkı)',
                'Bu müşterinin ad, telefon, e-posta, adres ve not bilgileri KALICI olarak silinecek. ' +
                    'Randevu ve ödeme geçmişi (muhasebe kayıtları için) korunacak, ancak artık bu müşteriye ait olduğu görünmeyecek. Bu işlem GERİ ALINAMAZ. Devam edilsin mi?',
                buttons,
            );
        });
    }

    /**
     * Save a customer record to the database (via ajax post).
     *
     * @param {Object} customer Contains the customer data.
     */
    function save(customer) {
        App.Http.Customers.save(customer).then((response) => {
            App.Layouts.Backend.displayNotification(lang('customer_saved'));
            App.Pages.Customers.resetForm();
            $('#customers-page').removeClass('editing');
            $('#filter-customers .key').val('');
            App.Pages.Customers.filter('', response.id, true);
        });
    }

    /**
     * Delete a customer record from database.
     *
     * @param {Number} id Record id to be deleted.
     */
    function remove(id) {
        App.Http.Customers.destroy(id).then(() => {
            App.Layouts.Backend.displayNotification(lang('customer_deleted'));
            App.Pages.Customers.resetForm();
            $('#customers-page').removeClass('editing');
            App.Pages.Customers.filter($('#filter-customers .key').val());
        });
    }

    /**
     * Salon Flora customization (2026-08-24, KVKK data retention) - anonymize a customer's PII in
     * place (name/phone/email/address/notes), keeping the record and its appointment history intact
     * for accounting purposes. See Customers_model::anonymize() for the full rationale.
     *
     * @param {Number} id Record id to be anonymized.
     */
    function anonymizeCustomer(id) {
        const url = App.Utils.Url.siteUrl('customers/anonymize');

        $.post(url, {csrf_token: vars('csrf_token'), customer_id: id}).done(() => {
            App.Layouts.Backend.displayNotification('Müşteri kişisel verileri anonimleştirildi.');
            App.Pages.Customers.resetForm();
            $('#customers-page').removeClass('editing');
            App.Pages.Customers.filter($('#filter-customers .key').val());
        });
    }

    /**
     * Validate customer data before save (insert or update).
     */
    function validate() {
        $formMessage.removeClass('alert-danger').hide();
        $('.is-invalid').removeClass('is-invalid');

        try {
            // Validate required fields.
            let missingRequired = false;

            $('.required').each((index, requiredField) => {
                if ($(requiredField).val() === '') {
                    $(requiredField).addClass('is-invalid');
                    missingRequired = true;
                }
            });

            if (missingRequired) {
                throw new Error(lang('fields_are_required'));
            }

            // Validate email address.
            const email = $email.val();

            if (email && !App.Utils.Validation.email(email)) {
                $email.addClass('is-invalid');
                throw new Error(lang('invalid_email'));
            }

            // Validate phone number.
            const phoneNumber = $phoneNumber.val();

            if (phoneNumber && !App.Utils.Validation.phone(phoneNumber)) {
                $phoneNumber.addClass('is-invalid');
                throw new Error(lang('invalid_phone'));
            }

            return true;
        } catch (error) {
            $formMessage.addClass('alert-danger').text(error.message).show();
            return false;
        }
    }

    /**
     * Bring the customer form back to its initial state.
     */
    function resetForm() {
        $customers.find('.record-details').find('input, select, textarea').val('').prop('disabled', true);
        Object.values(notificationChannelInputs).forEach(($input) => $input.prop('checked', false));
        $notificationPreferenceMode.val('default');
        $customNotificationChannels.hide();
        $customers.find('.record-details .form-label span').prop('hidden', true);
        $customers.find('.record-details #timezone').val(vars('default_timezone'));
        $customers.find('.record-details #language').val(vars('default_language'));

        $customerAppointments.empty();
        $customerInsights.empty();

        $customers.find('#edit-customer, #delete-customer, #anonymize-customer').prop('disabled', true);
        $customers.find('#add-edit-delete-group').show();
        $customers.find('#save-cancel-group').hide();

        $customers.find('.record-details .is-invalid').removeClass('is-invalid');
        $customers.find('.record-details #form-message').hide();

        $filterCustomers.find('button').prop('disabled', false);
        $filterCustomers.find('.selected').removeClass('selected');
        $filterCustomers.find('.results').css('color', '');
    }

    /**
     * Salon Flora customization - CRM customer card: compute and render behavioral insights purely from
     * the customer's own appointment history (already embedded in customer.appointments, each pre-loaded
     * with .service/.provider - see Customers::find()). No extra request needed - "en son iletişime
     * geçtiği kanal" style automated tracking isn't computed here since nothing in the system logs actual
     * messages yet (see last-contact-channel field, set manually for now).
     *
     * @param {Object} customer - Contains customer.appointments.
     */
    function renderInsights(customer) {
        $customerInsights.empty();

        const appointments = (customer.appointments || []).filter((appointment) => !Number(appointment.is_unavailability));

        if (!appointments.length) {
            $('<p/>', {'class': 'text-muted mb-0', 'text': 'Henüz randevu geçmişi yok.'}).appendTo($customerInsights);
            return;
        }

        const countBy = (list, keyFn) => {
            const counts = new Map();
            list.forEach((item) => {
                const key = keyFn(item);
                if (key === null || key === undefined) {
                    return;
                }
                counts.set(key, (counts.get(key) || 0) + 1);
            });
            return counts;
        };

        const topEntry = (counts) => {
            let best = null;
            counts.forEach((count, key) => {
                if (!best || count > best.count) {
                    best = {key, count};
                }
            });
            return best;
        };

        const providerName = (appointment) =>
            appointment.provider ? `${appointment.provider.first_name} ${appointment.provider.last_name}` : null;

        const DAY_NAMES = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];

        const timeBucket = (hour) => {
            if (hour < 12) {
                return 'Sabah (00:00-12:00)';
            }
            if (hour < 17) {
                return 'Öğleden Sonra (12:00-17:00)';
            }
            return 'Akşam (17:00-24:00)';
        };

        const favoriteService = topEntry(countBy(appointments, (a) => a.service?.name));
        const favoriteProvider = topEntry(countBy(appointments, providerName));
        const favoriteDay = topEntry(countBy(appointments, (a) => DAY_NAMES[new Date(a.start_datetime).getDay()]));
        const favoriteTime = topEntry(countBy(appointments, (a) => timeBucket(new Date(a.start_datetime).getHours())));

        const lastSessions = [...appointments]
            .sort((a, b) => new Date(b.start_datetime) - new Date(a.start_datetime))
            .slice(0, 3);

        const row = (label, value) => [
            $('<strong/>', {'class': 'd-inline-block me-2', 'text': label}),
            $('<span/>', {'text': value}),
            $('<br/>'),
        ];

        $customerInsights.append(
            ...row('Toplam Seans', String(appointments.length)),
            ...row('En Çok Tercih Ettiği Hizmet', favoriteService ? `${favoriteService.key} (${favoriteService.count})` : '-'),
            ...row('En Çok Gittiği Terapist', favoriteProvider ? `${favoriteProvider.key} (${favoriteProvider.count})` : '-'),
            ...row('Tercih Ettiği Gün', favoriteDay ? `${favoriteDay.key} (${favoriteDay.count})` : '-'),
            ...row('Tercih Ettiği Saat Dilimi', favoriteTime ? `${favoriteTime.key} (${favoriteTime.count})` : '-'),
        );

        // BooKi (2026-08-26) - a best-effort SIGNAL, not an authoritative recommendation: match
        // the customer's favorite service against provider skill tags via simple case-insensitive
        // substring matching (there is no service<->skill foreign key). Only shown when it actually
        // finds something, and skipped entirely when the customer's favorite provider already has the
        // matching skill (nothing new to suggest).
        if (favoriteService) {
            const serviceName = favoriteService.key.toLowerCase();
            const providersWithSkills = vars('providers_with_skills') || [];

            const matches = providersWithSkills.filter((provider) =>
                (provider.skills || []).some(
                    (skill) =>
                        serviceName.includes(skill.toLowerCase()) || skill.toLowerCase().includes(serviceName),
                ),
            );

            const newSuggestions = matches
                .map((provider) => provider.name)
                .filter((name) => name !== (favoriteProvider ? favoriteProvider.key : null));

            if (newSuggestions.length) {
                row('Yeteneğe Göre Önerilen Terapist', newSuggestions.join(', ')).forEach((el) =>
                    $customerInsights.append(el),
                );
            }
        }

        $('<hr/>').appendTo($customerInsights);
        $('<strong/>', {'class': 'd-block mb-2', 'text': 'Son 3 Seans'}).appendTo($customerInsights);

        lastSessions.forEach((appointment) => {
            const date = App.Utils.Date.format(
                moment(appointment.start_datetime).toDate(),
                vars('date_format'),
                vars('time_format'),
                true,
            );

            $('<div/>', {
                'class': 'mb-2',
                'html': [
                    $('<span/>', {'text': date}),
                    $('<br/>'),
                    $('<small/>', {
                        'class': 'text-muted',
                        'text':
                            (appointment.service?.name || '-') +
                            ' · ' +
                            (providerName(appointment) || '-'),
                    }),
                ],
            }).appendTo($customerInsights);
        });
    }

    /**
     * Display a customer record into the form.
     *
     * @param {Object} customer Contains the customer record data.
     */
    function display(customer) {
        $id.val(customer.id);
        $firstName.val(customer.first_name);
        $lastName.val(customer.last_name);
        $email.val(customer.email);
        $phoneNumber.val(customer.phone_number);
        $address.val(customer.address);
        $city.val(customer.city);
        $state.val(customer.state);
        $zipCode.val(customer.zip_code);
        $notes.val(customer.notes);
        $timezone.val(customer.timezone);
        $language.val(customer.language || 'english');
        $ldapDn.val(customer.ldap_dn);
        $customField1.val(customer.custom_field_1);
        $customField2.val(customer.custom_field_2);
        $customField3.val(customer.custom_field_3);
        $customField4.val(customer.custom_field_4);
        $customField5.val(customer.custom_field_5);

        // Salon Flora customization - CRM customer card.
        let socialLinks = {};

        try {
            socialLinks = customer.social_links ? JSON.parse(customer.social_links) : {};
        } catch (error) {
            socialLinks = {};
        }

        $socialWhatsapp.val(socialLinks.whatsapp || '');
        $socialTelegram.val(socialLinks.telegram || '');
        $socialInstagram.val(socialLinks.instagram || '');
        $lastContactChannel.val(customer.last_contact_channel || '');

        const notificationPreferences = customer.notification_preferences || {};
        $notificationPreferenceMode.val(notificationPreferences.mode || 'default');
        Object.entries(notificationChannelInputs).forEach(([channel, $input]) => {
            $input.prop('checked', Boolean(notificationPreferences[`${channel}_enabled`]));
        });
        $customNotificationChannels.toggle($notificationPreferenceMode.val() === 'custom');

        renderInsights(customer);

        $customerAppointments.empty();

        if (!customer.appointments.length) {
            $('<p/>', {
                'text': lang('no_records_found'),
            }).appendTo($customerAppointments);
        }

        customer.appointments.forEach((appointment) => {
            if (
                vars('role_slug') === App.Layouts.Backend.DB_SLUG_PROVIDER &&
                parseInt(appointment.id_users_provider) !== vars('user_id')
            ) {
                return;
            }

            if (
                vars('role_slug') === App.Layouts.Backend.DB_SLUG_SECRETARY &&
                vars('secretary_providers').indexOf(appointment.id_users_provider) === -1
            ) {
                return;
            }

            const start = App.Utils.Date.format(
                moment(appointment.start_datetime).toDate(),
                vars('date_format'),
                vars('time_format'),
                true,
            );

            const end = App.Utils.Date.format(
                moment(appointment.end_datetime).toDate(),
                vars('date_format'),
                vars('time_format'),
                true,
            );

            $('<div/>', {
                'class': 'appointment-row',
                'data-id': appointment.id,
                'html': [
                    // Service - Provider

                    $('<a/>', {
                        'href': App.Utils.Url.siteUrl(`calendar/reschedule/${appointment.hash}`),
                        'html': [
                            $('<i/>', {
                                'class': 'fas fa-edit me-1',
                            }),
                            $('<strong/>', {
                                'text':
                                    appointment.service.name +
                                    ' - ' +
                                    appointment.provider.first_name +
                                    ' ' +
                                    appointment.provider.last_name,
                            }),
                            $('<br/>'),
                        ],
                    }),

                    // Start

                    $('<small/>', {
                        'text': start,
                    }),
                    $('<br/>'),

                    // End

                    $('<small/>', {
                        'text': end,
                    }),
                    $('<br/>'),

                    // Timezone

                    $('<small/>', {
                        'text': vars('timezones')[appointment.provider.timezone],
                    }),
                ],
            }).appendTo('#customer-appointments');
        });
    }

    /**
     * Filter customer records.
     *
     * @param {String} keyword This keyword string is used to filter the customer records.
     * @param {Number} selectId Optional, if set then after the filter operation the record with the given
     * ID will be selected (but not displayed).
     * @param {Boolean} show Optional (false), if true then the selected record will be displayed on the form.
     */
    function filter(keyword, selectId = null, show = false) {
        $filterCustomers.find('.results').html(
            '<div class="text-center py-4 text-muted">' +
            '<div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>' +
            '<span>' + (lang('loading') || 'Yükleniyor...') + '</span>' +
            '</div>'
        );

        App.Http.Customers.search(keyword, filterLimit).then((response) => {
            filterResults = response;

            $filterCustomers.find('.results').empty();

            response.forEach((customer) => {
                $('#filter-customers .results').append(App.Pages.Customers.getFilterHtml(customer)).append($('<hr/>'));
            });

            if (!response.length) {
                if (keyword) {
                    $filterCustomers.find('.results').append(
                        $('<div/>', {
                            'class': 'text-center py-4 text-muted',
                            'html': [
                                $('<i/>', {'class': 'fas fa-search fa-2x opacity-50 mb-2 d-block'}),
                                $('<p/>', {'class': 'mb-2 small', 'text': lang('no_records_found') || 'Aramanızla eşleşen müşteri bulunamadı.'}),
                                $('<button/>', {
                                    'type': 'button',
                                    'class': 'btn btn-sm btn-outline-secondary',
                                    'html': '<i class="fas fa-times me-1"></i>Filtreyi Temizle',
                                    'click': () => {
                                        $filterCustomers.find('.key').val('');
                                        App.Pages.Customers.filter('');
                                    }
                                })
                            ]
                        })
                    );
                } else {
                    $filterCustomers.find('.results').append(
                        $('<div/>', {
                            'class': 'text-center py-4 text-muted',
                            'html': [
                                $('<i/>', {'class': 'fas fa-users fa-3x text-primary opacity-50 mb-3 d-block'}),
                                $('<h6/>', {'class': 'fw-semibold text-dark mb-1', 'text': 'Henüz kayıtlı müşteri yok'}),
                                $('<p/>', {'class': 'small text-muted mb-3', 'text': 'Yeni randevu oluşturulduğunda veya müşteri eklediğinizde burada listelenir.'}),
                                $('<button/>', {
                                    'type': 'button',
                                    'class': 'btn btn-sm btn-primary',
                                    'html': '<i class="fas fa-user-plus me-1"></i>Yeni Müşteri Ekle',
                                    'click': () => {
                                        $('#add-customer').trigger('click');
                                    }
                                })
                            ]
                        })
                    );
                }
            } else if (response.length === filterLimit) {
                $('<button/>', {
                    'type': 'button',
                    'class': 'btn btn-outline-secondary w-100 load-more text-center',
                    'text': lang('load_more'),
                    'click': () => {
                        filterLimit += 20;
                        App.Pages.Customers.filter(keyword, selectId, show);
                    },
                }).appendTo('#filter-customers .results');
            }

            if (selectId) {
                App.Pages.Customers.select(selectId, show);
            }
        }).catch((err) => {
            $filterCustomers.find('.results').html(
                $('<div/>', {
                    'class': 'text-center py-4 text-danger',
                    'html': [
                        $('<i/>', {'class': 'fas fa-exclamation-triangle fa-2x mb-2 d-block'}),
                        $('<p/>', {'class': 'small mb-2', 'text': 'Müşteriler yüklenirken bir hata oluştu.'}),
                        $('<button/>', {
                            'type': 'button',
                            'class': 'btn btn-sm btn-outline-danger',
                            'html': '<i class="fas fa-sync-alt me-1"></i>Tekrar Dene',
                            'click': () => App.Pages.Customers.filter(keyword, selectId, show)
                        })
                    ]
                })
            );
        });
    }

    /**
     * Get the filter results row HTML code.
     *
     * @param {Object} customer Contains the customer data.
     *
     * @return {String} Returns the record HTML code.
     */
    function getFilterHtml(customer) {
        const name = (customer.first_name || '[No First Name]') + ' ' + (customer.last_name || '[No Last Name]');

        let info = customer.email || '[No Email]';

        info = customer.phone_number ? info + ', ' + customer.phone_number : info;

        return $('<div/>', {
            'class': 'customer-row entry',
            'data-id': customer.id,
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
     * Select a specific record from the current filter results.
     *
     * If the customer id does not exist in the list then no record will be selected.
     *
     * @param {Number} id The record id to be selected from the filter results.
     * @param {Boolean} show Optional (false), if true then the method will display the record on the form.
     */
    function select(id, show = false) {
        $('#filter-customers .selected').removeClass('selected');

        $('#filter-customers .entry[data-id="' + id + '"]').addClass('selected');

        if (show) {
            const customer = filterResults.find((filterResult) => Number(filterResult.id) === Number(id));

            App.Pages.Customers.display(customer);

            $('#edit-customer, #delete-customer, #anonymize-customer').prop('disabled', false);
        }
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        App.Pages.Customers.resetForm();
        App.Pages.Customers.addEventListeners();
        App.Pages.Customers.filter('');
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        filter,
        save,
        remove,
        anonymizeCustomer,
        validate,
        getFilterHtml,
        resetForm,
        display,
        select,
        addEventListeners,
    };
})();
