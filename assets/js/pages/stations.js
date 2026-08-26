/* ----------------------------------------------------------------------------
 * Salon Flora customization - Stations page.
 * ---------------------------------------------------------------------------- */

App.Pages.Stations = (function () {
    const $stations = $('#stations');
    const $id = $('#id');
    const $name = $('#name');
    const $notes = $('#notes');
    const $isActive = $('#is-active');
    const $filterStations = $('#filter-stations');
    const $stationServices = $('#station-services');
    const $stationNoRestriction = $('#station-no-restriction');
    let filterResults = {};
    let filterLimit = 20;

    /**
     * Salon Flora customization - label a service with its duration, so duration variants of the same service
     * (e.g. "Klasik Masaj" 60/90/120 dk) are distinguishable in the checkbox list.
     *
     * @param {Object} service
     *
     * @return {String}
     */
    function serviceLabel(service) {
        return service.duration ? `${service.name} — ${service.duration} dk` : service.name;
    }

    /**
     * Salon Flora customization - build the "which services can this station host" checkbox list, grouped by
     * service category so it reads as "pick a category" even though the underlying data (and the fail-open rule)
     * is per-service - see Stations_model::get_station_ids_for_service() for why.
     */
    function renderServiceCheckboxes() {
        $stationServices.empty();

        const services = vars('services') || [];
        const categories = vars('service_categories') || [];

        const categoryGroups = categories.map((category) => ({
            category,
            services: services.filter((service) => Number(service.id_service_categories) === Number(category.id)),
        }));

        const uncategorized = services.filter(
            (service) => !categories.some((category) => Number(category.id) === Number(service.id_service_categories)),
        );

        if (uncategorized.length) {
            categoryGroups.push({category: {id: null, name: 'Kategorisiz'}, services: uncategorized});
        }

        categoryGroups.forEach((group) => {
            if (!group.services.length) {
                return;
            }

            const $groupHeader = $('<div/>', {
                class: 'd-flex justify-content-between align-items-center mt-2 mb-1',
                html: [
                    $('<strong/>', {class: 'small', text: group.category.name}),
                    $('<div/>', {
                        class: 'btn-group btn-group-sm',
                        html: [
                            $('<button/>', {
                                type: 'button',
                                class: 'btn btn-outline-secondary btn-sm select-all-category',
                                'data-category-id': group.category.id,
                                text: 'Tümünü seç',
                            }),
                            $('<button/>', {
                                type: 'button',
                                class: 'btn btn-outline-secondary btn-sm select-none-category',
                                'data-category-id': group.category.id,
                                text: 'Temizle',
                            }),
                        ],
                    }),
                ],
            });

            $stationServices.append($groupHeader);

            group.services.forEach((service) => {
                const checkboxId = `station-service-${service.id}`;

                $('<div/>', {
                    class: 'form-check',
                    html: [
                        $('<input/>', {
                            id: checkboxId,
                            class: 'form-check-input station-service-checkbox',
                            type: 'checkbox',
                            'data-id': service.id,
                            'data-category-id': group.category.id,
                            prop: {disabled: true},
                        }),
                        $('<label/>', {class: 'form-check-label', text: serviceLabel(service), for: checkboxId}),
                    ],
                }).appendTo($stationServices);
            });
        });
    }

    function addEventListeners() {
        $stations.on('submit', '#filter-stations form', (event) => {
            event.preventDefault();
            const key = $filterStations.find('.key').val();
            $filterStations.find('.selected').removeClass('selected');
            App.Pages.Stations.resetForm();
            App.Pages.Stations.filter(key);
        });

        $stations.on('click', '.station-row', (event) => {
            if ($filterStations.find('.filter').prop('disabled')) {
                return;
            }

            const stationId = $(event.currentTarget).attr('data-id');

            const station = filterResults.find((filterResult) => Number(filterResult.id) === Number(stationId));

            App.Pages.Stations.display(station);
            $filterStations.find('.selected').removeClass('selected');
            $(event.currentTarget).addClass('selected');
            $('#edit-station, #delete-station').prop('disabled', false);

            $('#stations-page').addClass('editing');
            $stations.find('.add-edit-delete-group').hide();
            $stations.find('.save-cancel-group').show();
            $stations.find('#delete-station').show();
            $stations.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $stations.find('.record-details .form-label span').prop('hidden', false);
            $filterStations.find('button').prop('disabled', true);
            $stationNoRestriction.trigger('change');
        });

        $stations.on('click', '#add-station', () => {
            App.Pages.Stations.resetForm();
            $('#stations-page').addClass('editing');
            $stations.find('.add-edit-delete-group').hide();
            $stations.find('.save-cancel-group').show();
            $stations.find('#delete-station').hide();
            $stations.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $stations.find('.record-details .form-label span').prop('hidden', false);
            $filterStations.find('button').prop('disabled', true);

            $isActive.prop('checked', true);
            $stationNoRestriction.prop('checked', true).trigger('change');
        });

        $stations.on('click', '#cancel-station', () => {
            const id = $id.val();

            App.Pages.Stations.resetForm();
            $('#stations-page').removeClass('editing');

            if (id !== '') {
                App.Pages.Stations.select(id, true);
            }
        });

        $stations.on('click', '#save-station', () => {
            const services = $stationNoRestriction.prop('checked')
                ? []
                : $stationServices
                      .find('input:checkbox:checked')
                      .map((index, checkboxEl) => Number($(checkboxEl).data('id')))
                      .get();

            const station = {
                name: $name.val(),
                notes: $notes.val(),
                is_active: Number($isActive.prop('checked')),
                services,
            };

            if ($id.val() !== '') {
                station.id = $id.val();
            }

            if (!App.Pages.Stations.validate()) {
                return;
            }

            App.Pages.Stations.save(station);
        });

        $stations.on('click', '#edit-station', () => {
            $('#stations-page').addClass('editing');
            $stations.find('.add-edit-delete-group').hide();
            $stations.find('.save-cancel-group').show();
            $stations.find('.record-details').find('input, select, textarea').prop('disabled', false);
            $stations.find('.record-details .form-label span').prop('hidden', false);
            $filterStations.find('button').prop('disabled', true);
            $stationNoRestriction.trigger('change');
        });

        $stations.on('click', '#delete-station', () => {
            const stationId = $id.val();
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
                        App.Pages.Stations.remove(stationId);
                        messageModal.hide();
                    },
                },
            ];

            App.Utils.Message.show('İstasyonu Sil', lang('delete_record_prompt'), buttons);
        });

        // Salon Flora customization - service-restriction checkbox list.
        $stations.on('change', '#station-no-restriction', (event) => {
            const noRestriction = $(event.currentTarget).is(':checked');
            $stationServices.find('input:checkbox').prop('checked', false);
            $stationServices.find('input:checkbox, button').prop('disabled', $stationNoRestriction.prop('disabled') || noRestriction);
        });

        $stations.on('change', '.station-service-checkbox', () => {
            if ($stationServices.find('input:checkbox:checked').length) {
                $stationNoRestriction.prop('checked', false);
            }
        });

        $stations.on('click', '.select-all-category', (event) => {
            const categoryId = $(event.currentTarget).data('category-id');
            $stationServices
                .find(`input:checkbox[data-category-id="${categoryId}"]`)
                .prop('checked', true)
                .trigger('change');
        });

        $stations.on('click', '.select-none-category', (event) => {
            const categoryId = $(event.currentTarget).data('category-id');
            $stationServices.find(`input:checkbox[data-category-id="${categoryId}"]`).prop('checked', false);
        });
    }

    function save(station) {
        App.Http.Stations.save(station).then((response) => {
            App.Layouts.Backend.displayNotification('İstasyon kaydedildi.');
            App.Pages.Stations.resetForm();
            $('#stations-page').removeClass('editing');
            $filterStations.find('.key').val('');
            App.Pages.Stations.filter('', response.id, true);
        });
    }

    function remove(id) {
        App.Http.Stations.destroy(id).then(() => {
            App.Layouts.Backend.displayNotification('İstasyon silindi.');
            App.Pages.Stations.resetForm();
            $('#stations-page').removeClass('editing');
            App.Pages.Stations.filter($filterStations.find('.key').val());
        });
    }

    function validate() {
        $stations.find('.is-invalid').removeClass('is-invalid');
        $stations.find('.form-message').removeClass('alert-danger').hide();

        try {
            let missingRequired = false;

            $stations.find('.required').each((index, requiredField) => {
                if (!$(requiredField).val()) {
                    $(requiredField).addClass('is-invalid');
                    missingRequired = true;
                }
            });

            if (missingRequired) {
                throw new Error(lang('fields_are_required'));
            }

            return true;
        } catch (error) {
            $stations.find('.form-message').addClass('alert-danger').text(error.message).show();
            return false;
        }
    }

    function resetForm() {
        $filterStations.find('.selected').removeClass('selected');
        $filterStations.find('button').prop('disabled', false);

        $stations.find('.record-details').find('input, select, textarea').val('').prop('disabled', true);
        $stations.find('.record-details .form-label span').prop('hidden', true);
        $stations.find('.record-details #is-active').prop('checked', false);
        $stationServices.find('input:checkbox').prop('checked', false);
        $stationNoRestriction.prop('checked', true);

        $stations.find('.add-edit-delete-group').show();
        $stations.find('.save-cancel-group').hide();
        $('#edit-station, #delete-station').prop('disabled', true);

        $stations.find('.record-details .is-invalid').removeClass('is-invalid');
        $stations.find('.record-details .form-message').hide();
    }

    function display(station) {
        $id.val(station.id);
        $name.val(station.name);
        $notes.val(station.notes);
        $isActive.prop('checked', !!station.is_active);
        App.Http.Stations.find(station.id).done((response) => {
            const count = (response.provider_ids || []).length;
            $stations.find('.record-details h4').find('.provider-count-badge').remove();
            $stations.find('.record-details h4').append(
                $('<span/>', {class: 'provider-count-badge badge bg-secondary ms-2', text: count + ' terapist atanmış'}),
            );

            // Salon Flora customization - reflect the station's service restriction (empty = unrestricted /
            // fail-open, see Stations_model::get_station_ids_for_service()).
            const serviceIds = (response.services || []).map(Number);

            $stationServices.find('input:checkbox').prop('checked', false);
            serviceIds.forEach((serviceId) => {
                $stationServices.find(`input:checkbox[data-id="${serviceId}"]`).prop('checked', true);
            });

            $stationNoRestriction.prop('checked', serviceIds.length === 0);
            $stationServices
                .find('input:checkbox, button')
                .prop('disabled', $stationNoRestriction.prop('disabled') || serviceIds.length === 0);
        });
    }

    function filter(keyword, selectId = null, show = false) {
        App.Http.Stations.search(keyword, filterLimit).then((response) => {
            filterResults = response;

            $filterStations.find('.results').empty();

            response.forEach((station) => {
                $filterStations.find('.results').append(App.Pages.Stations.getFilterHtml(station)).append($('<hr/>'));
            });

            if (response.length === 0) {
                $filterStations.find('.results').append(
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
                        App.Pages.Stations.filter(keyword, selectId, show);
                    },
                }).appendTo('#filter-stations .results');
            }

            if (selectId) {
                App.Pages.Stations.select(selectId, show);
            }
        });
    }

    function getFilterHtml(station) {
        return $('<div/>', {
            'class': 'station-row entry',
            'data-id': station.id,
            'html': [
                $('<strong/>', {
                    'text': station.name,
                }),
                $('<br/>'),
                $('<small/>', {
                    'class': 'text-muted',
                    'text': station.is_active ? 'Aktif' : 'Pasif',
                }),
                $('<br/>'),
            ],
        });
    }

    function select(id, show = false) {
        $filterStations.find('.selected').removeClass('selected');

        $filterStations.find('.station-row[data-id="' + id + '"]').addClass('selected');

        if (show) {
            const station = filterResults.find((filterResult) => Number(filterResult.id) === Number(id));

            App.Pages.Stations.display(station);

            $('#edit-station, #delete-station').prop('disabled', false);
        }
    }

    function initialize() {
        renderServiceCheckboxes();
        App.Pages.Stations.resetForm();
        App.Pages.Stations.filter('');
        App.Pages.Stations.addEventListeners();
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
