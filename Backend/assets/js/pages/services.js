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
 * Services page.
 *
 * This module implements the functionality of the services page.
 */
App.Pages.Services = (function () {
    const $services = $('#services');
    const $id = $('#id');
    const $name = $('#name');
    const $duration = $('#duration');
    const $price = $('#price');
    const $currency = $('#currency');
    const $serviceCategoryId = $('#service-category-id');
    const $slotInterval = $('#slot-interval');
    const $attendantsNumber = $('#attendants-number');
    const $isPrivate = $('#is-private');
    const $location = $('#location');
    const $description = $('#description');
    const $filterServices = $('#filter-services');
    const $color = $('#color');
    let filterResults = {};
    let filterLimit = 20;

    /**
     * Add page event listeners.
     */
    function addEventListeners() {
        /**
         * Event: Filter Services Form "Submit"
         *
         * @param {jQuery.Event} event
         */
        $services.on('submit', '#filter-services form', (event) => {
            event.preventDefault();
            const key = $filterServices.find('.key').val();
            $filterServices.find('.selected').removeClass('selected');
            App.Pages.Services.resetForm();
            App.Pages.Services.filter(key);
        });

        /**
         * Event: Filter Service Row "Click"
         *
         * Display the selected service data to the user.
         */
        $services.on('click', '.service-row', (event) => {
            if ($filterServices.find('.filter').prop('disabled')) {
                $filterServices.find('.results').css('color', '#AAA');
                return; // exit because we are on edit mode
            }

            const serviceId = $(event.currentTarget).attr('data-id');

            const service = filterResults.find((filterResult) => Number(filterResult.id) === Number(serviceId));

            // Add dedicated provider link.
            const dedicatedUrl = App.Utils.Url.siteUrl('?service=' + encodeURIComponent(service.id));

            const $link = $('<a/>', {
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

            $services.find('.record-details h4').find('a').remove().end().append($link);
            new bootstrap.Tooltip($link[0]);

            App.Pages.Services.display(service);
            $filterServices.find('.selected').removeClass('selected');
            $(event.currentTarget).addClass('selected');
            $('#edit-service, #delete-service').prop('disabled', false);

            // Automatically enter edit mode
            $('#services-page').addClass('editing');
            $services.find('.add-edit-delete-group').hide();
            $services.find('.save-cancel-group').show();
            $services.find('#delete-service').show(); // Show delete button when editing
            $services.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $services.find('.record-details .form-label span').prop('hidden', false);
            $filterServices.find('button').prop('disabled', true);
            $filterServices.find('.results').css('color', '#AAA');
            App.Components.ColorSelection.enable($color);
            $('#service-providers input:checkbox').prop('disabled', false);
            $('#select-all-providers, #select-none-providers').prop('disabled', false);
        });

        /**
         * Event: Add New Service Button "Click"
         */
        $services.on('click', '#add-service', () => {
            App.Pages.Services.resetForm();
            $('#services-page').addClass('editing');
            $services.find('.add-edit-delete-group').hide();
            $services.find('.save-cancel-group').show();
            $services.find('#delete-service').hide(); // Hide delete button when adding
            $services.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $services.find('.record-details .form-label span').prop('hidden', false);
            $filterServices.find('button').prop('disabled', true);
            $filterServices.find('.results').css('color', '#AAA');
            App.Components.ColorSelection.enable($color);
            $('#service-providers input:checkbox').prop('disabled', false);
            $('#select-all-providers, #select-none-providers').prop('disabled', false);

            // Default values
            $name.val('Service');
            $duration.val('30');
            $price.val('0');
            $currency.val('');
            $serviceCategoryId.val('');
            $slotInterval.val('15');
            $attendantsNumber.val('1');
        });

        /**
         * Event: Cancel Service Button "Click"
         *
         * Cancel add or edit of a service record.
         */
        $services.on('click', '#cancel-service', () => {
            const id = $id.val();

            App.Pages.Services.resetForm();
            $('#services-page').removeClass('editing');

            if (id !== '') {
                App.Pages.Services.select(id, true);
            }
        });

        /**
         * Event: Save Service Button "Click"
         */
        $services.on('click', '#save-service', () => {
            const service = {
                name: $name.val(),
                duration: $duration.val(),
                price: $price.val(),
                currency: $currency.val(),
                description: $description.val(),
                location: $location.val(),
                color: App.Components.ColorSelection.getColor($color),
                slot_interval: $slotInterval.val(),
                attendants_number: $attendantsNumber.val(),
                is_private: Number($isPrivate.prop('checked')),
                id_service_categories: $serviceCategoryId.val() || undefined,
            };

            // Include service providers.
            service.providers = [];
            $('#service-providers input:checkbox').each((index, checkboxEl) => {
                if ($(checkboxEl).prop('checked')) {
                    service.providers.push($(checkboxEl).attr('data-id'));
                }
            });

            if ($id.val() !== '') {
                service.id = $id.val();
            }

            if (!App.Pages.Services.validate()) {
                return;
            }

            App.Pages.Services.save(service);
        });

        /**
         * Event: Edit Service Button "Click"
         */
        $services.on('click', '#edit-service', () => {
            $('#services-page').addClass('editing');
            $services.find('.add-edit-delete-group').hide();
            $services.find('.save-cancel-group').show();
            $services.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $services.find('.record-details .form-label span').prop('hidden', false);
            $filterServices.find('button').prop('disabled', true);
            $filterServices.find('.results').css('color', '#AAA');
            App.Components.ColorSelection.enable($color);
            $('#service-providers input:checkbox').prop('disabled', false);
            $('#select-all-providers, #select-none-providers').prop('disabled', false);
        });

        /**
         * Event: Delete Service Button "Click"
         */
        $services.on('click', '#delete-service', () => {
            const serviceId = $id.val();
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
                        App.Pages.Services.remove(serviceId);
                        messageModal.hide();
                    },
                },
            ];

            App.Utils.Message.show(lang('delete_service'), lang('delete_record_prompt'), buttons);
        });

        /**
         * Event: Select All Providers Button "Click"
         */
        $services.on('click', '#select-all-providers', () => {
            $('#service-providers input:checkbox').prop('checked', true);
        });

        /**
         * Event: Select None Providers Button "Click"
         */
        $services.on('click', '#select-none-providers', () => {
            $('#service-providers input:checkbox').prop('checked', false);
        });

        function showModal(modalId) {
            const el = document.getElementById(modalId);
            if (!el) return;
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(el).show();
            } else if ($.fn.modal) {
                $('#' + modalId).modal('show');
            }
        }

        function hideModal(modalId) {
            const el = document.getElementById(modalId);
            if (!el) return;
            if (window.bootstrap && bootstrap.Modal) {
                const inst = bootstrap.Modal.getInstance(el) || bootstrap.Modal.getOrCreateInstance(el);
                if (inst) inst.hide();
            } else if ($.fn.modal) {
                $('#' + modalId).modal('hide');
            }
        }

        // Add-on modal opener
        $services.on('click', '#btn-add-addon-modal', () => {
            const serviceId = $id.val();
            if (!serviceId) {
                App.Layouts.Backend.displayNotification('Ek hizmet eklemek için önce hizmeti kaydediniz.', 'warning');
                return;
            }
            $('#addon-name-input').val('');
            $('#addon-duration-input').val('15');
            $('#addon-price-input').val('0.00');
            $('#addon-desc-input').val('');
            showModal('modal-addon-form');
        });

        // Add-on submit
        $('#btn-save-addon-submit').on('click', () => {
            const serviceId = $id.val();
            const name = $('#addon-name-input').val().trim();
            if (!serviceId || !name) {
                alert('Lütfen ek hizmet adını girin.');
                return;
            }
            const data = {
                csrf_token: vars('csrf_token'),
                id_services: Number(serviceId),
                name: name,
                duration_minutes: Number($('#addon-duration-input').val() || 0),
                price: Number($('#addon-price-input').val() || 0),
                description: $('#addon-desc-input').val().trim(),
            };
            $.ajax({
                url: App.Utils.Url.siteUrl('services/save_addon'),
                type: 'POST',
                data: data,
                success: () => {
                    hideModal('modal-addon-form');
                    loadAddons(serviceId);
                    App.Layouts.Backend.displayNotification('Ek hizmet kaydedildi.');
                },
                error: (xhr) => {
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Ek hizmet kaydedilemedi.';
                    alert(msg);
                },
            });
        });

        // Add-on delete
        $services.on('click', '.btn-delete-addon', function () {
            const addonId = $(this).data('id');
            const serviceId = $id.val();
            if (!confirm('Bu ek hizmeti silmek istediğinize emin misiniz?')) return;
            $.post(App.Utils.Url.siteUrl('services/delete_addon/' + addonId), {
                csrf_token: vars('csrf_token'),
            }, () => {
                loadAddons(serviceId);
                App.Layouts.Backend.displayNotification('Ek hizmet silindi.');
            }).fail((xhr) => {
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Ek hizmet silinemedi.';
                alert(msg);
            });
        });

        // Consumable modal opener
        $services.on('click', '#btn-add-consumable-modal', () => {
            const serviceId = $id.val();
            if (!serviceId) {
                App.Layouts.Backend.displayNotification('Sarf reçetesi eklemek için önce hizmeti kaydediniz.', 'warning');
                return;
            }
            $('#consumable-product-select').val('');
            $('#consumable-qty-input').val('1.00');
            $('#consumable-unit-display').val('adet');
            showModal('modal-consumable-form');
        });

        $('#consumable-product-select').on('change', function () {
            const unit = $(this).find('option:selected').data('unit') || 'adet';
            $('#consumable-unit-display').val(unit);
        });

        // Consumable submit
        $('#btn-save-consumable-submit').on('click', () => {
            const serviceId = $id.val();
            const productId = $('#consumable-product-select').val();
            const qty = Number($('#consumable-qty-input').val());
            if (!serviceId || !productId || qty <= 0) {
                alert('Lütfen ürün ve geçerli bir miktar seçin.');
                return;
            }
            const data = {
                csrf_token: vars('csrf_token'),
                id_services: Number(serviceId),
                id_products: Number(productId),
                quantity_used: qty,
            };
            $.ajax({
                url: App.Utils.Url.siteUrl('services/save_consumable'),
                type: 'POST',
                data: data,
                success: () => {
                    hideModal('modal-consumable-form');
                    loadConsumables(serviceId);
                    App.Layouts.Backend.displayNotification('Sarf malzeme reçeteye eklendi.');
                },
                error: (xhr) => {
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Sarf malzeme kaydedilemedi.';
                    alert(msg);
                },
            });
        });

        // Consumable delete
        $services.on('click', '.btn-delete-consumable', function () {
            const recId = $(this).data('id');
            const serviceId = $id.val();
            if (!confirm('Bu sarf malzemeyi reçeteden çıkarmak istediğinize emin misiniz?')) return;
            $.post(App.Utils.Url.siteUrl('services/delete_consumable/' + recId), {
                csrf_token: vars('csrf_token'),
            }, () => {
                loadConsumables(serviceId);
                App.Layouts.Backend.displayNotification('Sarf malzeme reçeteden çıkarıldı.');
            }).fail((xhr) => {
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Sarf malzeme reçeteden çıkarılamadı.';
                alert(msg);
            });
        });
    }

    /**
     * Save service record to database.
     *
     * @param {Object} service Contains the service record data. If an 'id' value is provided
     * then the update operation is going to be executed.
     */
    function save(service) {
        App.Http.Services.save(service).then((response) => {
            App.Layouts.Backend.displayNotification(lang('service_saved'));
            App.Pages.Services.resetForm();
            $('#services-page').removeClass('editing');
            $filterServices.find('.key').val('');
            App.Pages.Services.filter('', response.id, true);
        });
    }

    /**
     * Delete a service record from database.
     *
     * @param {Number} id Record ID to be deleted.
     */
    function remove(id) {
        App.Http.Services.destroy(id).then(() => {
            App.Layouts.Backend.displayNotification(lang('service_deleted'));
            App.Pages.Services.resetForm();
            $('#services-page').removeClass('editing');
            App.Pages.Services.filter($filterServices.find('.key').val());
        });
    }

    /**
     * Validates a service record.
     *
     * @return {Boolean} Returns the validation result.
     */
    function validate() {
        $services.find('.is-invalid').removeClass('is-invalid');
        $services.find('.form-message').removeClass('alert-danger').hide();

        try {
            // Validate required fields.
            let missingRequired = false;

            $services.find('.required').each((index, requiredField) => {
                if (!$(requiredField).val()) {
                    $(requiredField).addClass('is-invalid');
                    missingRequired = true;
                }
            });

            if (missingRequired) {
                throw new Error(lang('fields_are_required'));
            }

            // Validate the duration.
            if (Number($duration.val()) < vars('event_minimum_duration')) {
                $duration.addClass('is-invalid');
                throw new Error(lang('invalid_duration'));
            }

            return true;
        } catch (error) {
            $services.find('.form-message').addClass('alert-danger').text(error.message).show();
            return false;
        }
    }

    /**
     * Resets the service tab form back to its initial state.
     */
    function resetForm() {
        $filterServices.find('.selected').removeClass('selected');
        $filterServices.find('button').prop('disabled', false);
        $filterServices.find('.results').css('color', '');

        $services.find('.record-details').find('input, select, textarea').val('').prop('disabled', true);
        $services.find('.record-details .form-label span').prop('hidden', true);
        $services.find('.record-details #is-private').prop('checked', false);
        $services.find('.record-details h4 a').remove();

        $services.find('.add-edit-delete-group').show();
        $services.find('.save-cancel-group').hide();
        $('#edit-service, #delete-service').prop('disabled', true);

        $services.find('.record-details .is-invalid').removeClass('is-invalid');
        $services.find('.record-details .form-message').hide();

        // Reset providers checkboxes
        $('#service-providers input:checkbox').prop('disabled', true).prop('checked', false);
        $('#select-all-providers, #select-none-providers').prop('disabled', true);
        $('#service-providers a').remove();

        // Reset Addons & Consumables
        $('#service-addons-table tbody').html('<tr class="text-muted text-center py-3"><td colspan="4">Ek hizmet bulunamadı.</td></tr>');
        $('#service-consumables-table tbody').html('<tr class="text-muted text-center py-3"><td colspan="6">Reçeteye ekli sarf malzeme bulunamadı.</td></tr>');
        $('#service-consumables-summary').hide();

        App.Components.ColorSelection.disable($color);
    }

    /**
     * Display a service record into the service form.
     *
     * @param {Object} service Contains the service record data.
     */
    function display(service) {
        if (!service) return;
        $id.val(service.id);
        $name.val(service.name);
        $duration.val(service.duration);
        $price.val(service.price);
        $currency.val(service.currency);
        $description.val(service.description);
        $location.val(service.location);
        $slotInterval.val(service.slot_interval);
        $attendantsNumber.val(service.attendants_number);
        $isPrivate.prop('checked', service.is_private);
        App.Components.ColorSelection.setColor($color, service.color);

        const serviceCategoryId = service.id_service_categories !== null ? service.id_service_categories : '';
        $serviceCategoryId.val(serviceCategoryId);

        // Load Addons & Consumables for this service
        loadAddons(service.id);
        loadConsumables(service.id);

        // Display providers
        $('#service-providers a').remove();
        $('#service-providers input:checkbox').prop('checked', false);

        if (service.providers) {
            service.providers.forEach((serviceProviderId) => {
                const $checkbox = $('#service-providers input[data-id="' + serviceProviderId + '"]');

                if (!$checkbox.length) {
                    return;
                }

                $checkbox.prop('checked', true);

                // Add dedicated service-provider link.
                const dedicatedUrl = App.Utils.Url.siteUrl(
                    '?service=' + encodeURIComponent(service.id) + '&provider=' + encodeURIComponent(serviceProviderId),
                );

                const $link = $('<a/>', {
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

                $checkbox.parent().append($link);
                new bootstrap.Tooltip($link[0]);
            });
        }
    }

    /**
     * Load add-ons for the selected service.
     */
    function loadAddons(serviceId) {
        const $tbody = $('#service-addons-table tbody');
        $tbody.html('<tr class="text-muted text-center py-2"><td colspan="4"><i class="fas fa-spinner fa-spin me-2"></i>Yükleniyor...</td></tr>');

        $.get(App.Utils.Url.siteUrl('services/get_addons/' + serviceId))
            .done((addons) => {
                $tbody.empty();
                if (!addons || !addons.length) {
                    $tbody.html('<tr class="text-muted text-center py-2"><td colspan="4">Kayıtlı ek hizmet bulunamadı.</td></tr>');
                    return;
                }
                addons.forEach((addon) => {
                    const tr = `
                        <tr>
                            <td class="fw-semibold text-dark">${escapeHtml(addon.name)}</td>
                            <td>+${addon.duration_minutes || 0} dk</td>
                            <td class="text-primary fw-bold">+${Number(addon.price || 0).toFixed(2)} ₺</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-addon" data-id="${addon.id}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    $tbody.append(tr);
                });
            })
            .fail(() => {
                $tbody.html('<tr class="text-danger text-center py-2"><td colspan="4">Ek hizmetler yüklenemedi.</td></tr>');
            });
    }

    /**
     * Load consumables recipes for the selected service.
     */
    function loadConsumables(serviceId) {
        const $tbody = $('#service-consumables-table tbody');
        const $summary = $('#service-consumables-summary');
        $tbody.html('<tr class="text-muted text-center py-2"><td colspan="6"><i class="fas fa-spinner fa-spin me-2"></i>Yükleniyor...</td></tr>');

        $.get(App.Utils.Url.siteUrl('services/get_consumables/' + serviceId))
            .done((response) => {
                $tbody.empty();
                const recipes = Array.isArray(response) ? response : (response.recipes || []);
                const summary = response && response.summary ? response.summary : null;

                if (!recipes || !recipes.length) {
                    $tbody.html('<tr class="text-muted text-center py-2"><td colspan="6">Reçeteye ekli sarf malzeme bulunamadı.</td></tr>');
                    $summary.hide();
                    return;
                }
                recipes.forEach((rec) => {
                    const unitCost = Number(rec.cost || 0);
                    const totalCost = Number(rec.line_total_cost || (rec.quantity_used * unitCost) || 0);
                    const unit = escapeHtml(rec.product_unit || 'adet');

                    const tr = `
                        <tr>
                            <td class="fw-semibold text-dark">${escapeHtml(rec.product_name || 'Ürün #' + rec.id_products)}</td>
                            <td><span class="badge bg-light text-dark border">${rec.quantity_used} ${unit}</span></td>
                            <td class="text-muted">₺${unitCost.toFixed(2)}</td>
                            <td class="fw-semibold text-danger">₺${totalCost.toFixed(2)}</td>
                            <td><span class="badge ${Number(rec.stock_quantity) > 0 ? 'bg-success' : 'bg-danger'}">${rec.stock_quantity || 0} ${unit}</span></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-consumable" data-id="${rec.id}" title="Reçeteden Çıkar">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    $tbody.append(tr);
                });

                if (summary && summary.total_consumable_cost !== undefined) {
                    $('#summary-total-cost').text('₺' + Number(summary.total_consumable_cost || 0).toFixed(2));
                    $('#summary-service-price').text('₺' + Number(summary.service_price || 0).toFixed(2));
                    const profit = Number(summary.gross_profit || 0).toFixed(2);
                    const margin = Number(summary.gross_margin_percent || 0).toFixed(1);
                    $('#summary-gross-profit').text(`₺${profit} (%${margin})`);
                    $summary.show();
                } else {
                    $summary.hide();
                }
            })
            .fail(() => {
                $tbody.html('<tr class="text-danger text-center py-2"><td colspan="6">Sarf reçetesi yüklenemedi.</td></tr>');
                $summary.hide();
            });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /**
     * Filters service records depending on a string keyword.
     *
     * @param {String} keyword This is used to filter the service records of the database.
     * @param {Number} selectId Optional, if set then after the filter operation the record with this
     * ID will be selected (but not displayed).
     * @param {Boolean} show Optional (false), if true then the selected record will be displayed on the form.
     */
    function filter(keyword, selectId = null, show = false) {
        App.Http.Services.search(keyword, filterLimit).then((response) => {
            filterResults = response;

            $filterServices.find('.results').empty();

            response.forEach((service) => {
                $filterServices.find('.results').append(App.Pages.Services.getFilterHtml(service)).append($('<hr/>'));
            });

            if (response.length === 0) {
                $filterServices.find('.results').append(
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
                        App.Pages.Services.filter(keyword, selectId, show);
                    },
                }).appendTo('#filter-services .results');
            }

            if (selectId) {
                App.Pages.Services.select(selectId, show);
            }
        });
    }

    /**
     * Get Filter HTML
     *
     * Get a service row HTML code that is going to be displayed on the filter results list.
     *
     * @param {Object} service Contains the service record data.
     *
     * @return {String} The HTML code that represents the record on the filter results list.
     */
    function getFilterHtml(service) {
        const name = service.name;

        const info = service.duration + ' min - ' + service.price + ' ' + service.currency;

        return $('<div/>', {
            'class': 'service-row entry',
            'data-id': service.id,
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
     * Select a specific record from the current filter results. If the service id does not exist
     * in the list then no record will be selected.
     *
     * @param {Number} id The record id to be selected from the filter results.
     * @param {Boolean} show Optional (false), if true then the method will display the record on the form.
     */
    function select(id, show = false) {
        $filterServices.find('.selected').removeClass('selected');

        $filterServices.find('.service-row[data-id="' + id + '"]').addClass('selected');

        if (show) {
            const service = filterResults.find((filterResult) => Number(filterResult.id) === Number(id));

            App.Pages.Services.display(service);

            $('#edit-service, #delete-service').prop('disabled', false);
        }
    }

    /**
     * Update the service-category list box.
     *
     * Use this method every time a change is made to the service categories db table.
     */
    function updateAvailableServiceCategories() {
        App.Http.ServiceCategories.search('', 999).then((response) => {
            $serviceCategoryId.empty();

            $serviceCategoryId.append(new Option('', '')).val('');

            response.forEach((serviceCategory) => {
                $serviceCategoryId.append(new Option(serviceCategory.name, serviceCategory.id));
            });
        });
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        App.Pages.Services.resetForm();
        App.Pages.Services.filter('');
        App.Pages.Services.addEventListeners();
        updateAvailableServiceCategories();
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        filter,
        save,
        remove,
        validate,
        getFilterHtml,
        resetForm,
        display,
        select,
        addEventListeners,
    };
})();
