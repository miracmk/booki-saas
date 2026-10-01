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
 * Modern responsive table view, custom fields, branch dropdown, and
 * multi-section editor (Consumables, Add-ons, Follow-up SOAP, Contracts).
 */
App.Pages.Services = (function () {
    const $services = $('#services-page');
    const $id = $('#id');
    const $name = $('#name');
    const $duration = $('#duration');
    const $accessType = $('#access-type');
    const $serviceNature = $('#service-nature');
    const $taxRate = $('#tax-rate');
    const $totalPasses = $('#total-passes');
    const $validHoursStart = $('#valid-hours-start');
    const $validHoursEnd = $('#valid-hours-end');
    const $dailyCapacity = $('#daily-capacity');
    const $passValidityDays = $('#pass-validity-days');
    const $multiPassQuotaType = $('#multi-pass-quota-type');
    const $multiPassQuotaNumber = $('#multi-pass-quota-number');
    const $sectionTimedSettings = $('#section-timed-settings');
    const $packageSessionsContainer = $('#package-sessions-container');
    const $durationContainer = $('#duration-container');
    const $durationLabel = $('#duration-label');
    const $sectionDailyPassSettings = $('#section-daily-pass-settings');
    const $sectionMultiPassSettings = $('#section-multi-pass-settings');
    const $sectionProviders = $('#section-providers');
    const $priceUnitLabel = $('#price-unit-label');
    const $price = $('#price');
    const $currency = $('#currency');
    const $serviceCategoryId = $('#service-category-id');
    const $slotInterval = $('#slot-interval');
    const $attendantsNumber = $('#attendants-number');
    const $isPrivate = $('#is-private');
    const $location = $('#location');
    const $description = $('#description');
    const $color = $('#color');

    let filterResults = [];
    let filterLimit = 1000;
    let serviceFollowUpRules = [];

    /**
     * Helper to show a bootstrap modal
     */
    function showModal(modalId) {
        const el = document.getElementById(modalId);
        if (!el) return;
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        } else if ($.fn.modal) {
            $('#' + modalId).modal('show');
        }
    }

    /**
     * Helper to hide a bootstrap modal
     */
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

    /**
     * HTML escape helper
     */
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /**
     * Switch view to Services Table
     */
    function showTableView() {
        $('#services-table-view').show();
        $('#services-editor-view').hide();
        $('#services-page').removeClass('editing');
    }

    /**
     * Switch view to Service Detail Editor
     */
    function showEditorView(serviceId = null) {
        $('#services-table-view').hide();
        $('#services-editor-view').show();
        $('#services-page').addClass('editing');

        // Select first tab by default
        const tabEl = document.getElementById('tab-btn-general');
        if (tabEl && window.bootstrap && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(tabEl).show();
        } else {
            $('#tab-btn-general').tab('show');
        }

        if (!serviceId) {
            // New Service Mode
            $('#editor-service-title').html('<i class="fas fa-plus-circle text-primary me-2"></i>Yeni Hizmet Tanımla');
            $('#editor-service-subtitle').text('Operasyonel model, süre, sarfiyat reçetesi, ek hizmetler ve sözleşmeleri yapılandırın');
            $('#delete-service').hide();
            resetForm();

            // Defaults
            $serviceNature.val('duration');
            applyServiceNature('duration');
            $taxRate.val('20.00');
            $name.val('');
            $duration.val('60');
            $price.val('0.00');
            $currency.val('₺');
            $serviceCategoryId.val('');
            $location.val('');
            $slotInterval.val('15');
            $attendantsNumber.val('1');
            $totalPasses.val('10');
            $validHoursStart.val('09:00');
            $validHoursEnd.val('18:00');
            $dailyCapacity.val('');
            $passValidityDays.val('30');
            serviceFollowUpRules = [];
            renderFollowUpRules();

            $('#service-addons-table tbody').html('<tr class="text-muted text-center py-4"><td colspan="4">Ek hizmet eklemek için önce hizmeti kaydediniz.</td></tr>');
            $('#service-consumables-table tbody').html('<tr class="text-muted text-center py-4"><td colspan="6">Sarf reçetesi eklemek için önce hizmeti kaydediniz.</td></tr>');
            $('#service-consumables-summary').hide();
            $('#service-contracts-table tbody').html('<tr class="text-muted text-center py-4"><td colspan="5">Sözleşme bağlamak için önce hizmeti kaydediniz.</td></tr>');
            $('#badge-tab-addons').text('0');
            $('#badge-tab-consumables').text('0');
            $('#badge-tab-followup').text('0');
            $('#badge-tab-contracts').text('0');
        } else {
            // Edit Service Mode
            const service = (filterResults || []).find((s) => Number(s.id) === Number(serviceId));
            if (service) {
                $('#editor-service-title').html(`<span class="service-color-pill" style="background-color: ${service.color || '#4338ca'};"></span>${escapeHtml(service.name)}`);
                $('#editor-service-subtitle').text('Hizmet ID: #' + service.id + ' • ' + (service.category_name || 'Kategorisiz'));
                $('#delete-service').show();
                display(service);
            }
        }
    }

    /**
     * Render the Custom Services Table with all requested fields
     */
    function renderServicesTable(services) {
        const $tbody = $('#services-custom-table tbody');
        $tbody.empty();

        if (!services || !services.length) {
            $tbody.html(`
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="fas fa-sparkles fs-1 d-block mb-3 text-secondary opacity-50"></i>
                        <h6 class="fw-bold">Henüz Kayıtlı Hizmet Bulunamadı</h6>
                        <p class="small text-muted mb-3">İşletmenizin ilk bakım veya hizmet paketini tanımlayarak başlayın.</p>
                        <button type="button" class="btn btn-sm btn-primary px-3" id="btn-empty-add-service">
                            <i class="fas fa-plus me-1"></i>Yeni Hizmet Ekle
                        </button>
                    </td>
                </tr>
            `);
            updateKpis([]);
            return;
        }

        updateKpis(services);

        const natureLabels = {
            'duration': '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="fas fa-clock me-1"></i>Süre Bazlı</span>',
            'packaged': '<span class="badge bg-warning-subtle text-dark border border-warning-subtle"><i class="fas fa-cubes me-1"></i>Paket Seans</span>',
            'provider_custom_duration': '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="fas fa-users-gear me-1"></i>Uzman Süresi</span>',
            'daily_pass': '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-ticket me-1"></i>Günlük Pass</span>',
            'multi_pass': '<span class="badge bg-purple-subtle text-purple border border-purple-subtle" style="background-color: #ede9fe; color: #6b21a8;"><i class="fas fa-id-card me-1"></i>Çok Girişli</span>',
        };

        services.forEach((service) => {
            const color = service.color || '#4338ca';
            const nature = service.service_nature || service.access_type || 'duration';
            const natureBadge = natureLabels[nature] || `<span class="badge bg-secondary">${escapeHtml(nature)}</span>`;
            const catBadge = service.category_name 
                ? `<span class="badge bg-light text-secondary border me-1">${escapeHtml(service.category_name)}</span>` 
                : '';
            const priceFmt = `₺${Number(service.price || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            const taxBadge = `<span class="badge bg-light text-muted border" style="font-size:10px;">%${Number(service.tax_rate || 20)} KDV</span>`;

            let durationQuotaText = '';
            if (nature === 'daily_pass') {
                durationQuotaText = `<span class="small fw-semibold text-dark"><i class="fas fa-sun text-warning me-1"></i>${service.valid_hours_start || '09:00'} - ${service.valid_hours_end || '18:00'}</span>`;
            } else if (nature === 'multi_pass') {
                durationQuotaText = `<span class="small fw-semibold text-dark"><i class="fas fa-repeat text-success me-1"></i>${service.total_passes && Number(service.total_passes) > 0 ? service.total_passes + ' Giriş' : 'Sınırsız'}</span><br><small class="text-muted">${service.pass_validity_days || 30} Gün</small>`;
            } else if (nature === 'packaged') {
                durationQuotaText = `<span class="small fw-semibold text-dark"><i class="fas fa-layer-group text-primary me-1"></i>${service.total_passes || 10} Seans</span><br><small class="text-muted">${service.duration || 60} dk/seans</small>`;
            } else {
                durationQuotaText = `<span class="small fw-semibold text-dark"><i class="fas fa-clock text-info me-1"></i>${service.duration || 60} dk</span>`;
            }

            const locationText = service.location && service.location.trim()
                ? `<span class="badge bg-light text-dark border"><i class="fas fa-map-marker-alt text-danger me-1"></i>${escapeHtml(service.location)}</span>`
                : '<span class="badge bg-light text-muted border">Tüm Şubeler</span>';

            const monthlyCount = Number(service.monthly_count || 0);
            const monthlyBadge = `<span class="badge ${monthlyCount > 0 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted border'} px-2 py-1"><i class="fas fa-calendar-check me-1"></i>${monthlyCount} randevu</span>`;

            const providerCount = Number(service.providers_count !== undefined ? service.providers_count : (service.providers ? service.providers.length : 0));
            const providerBadge = `<span class="badge ${providerCount > 0 ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-light text-muted border'} px-2 py-1"><i class="fas fa-user-check me-1"></i>${providerCount} personel</span>`;

            const statusBadge = service.is_private
                ? '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Gizli</span>'
                : '<span class="badge bg-success-subtle text-success border border-success-subtle">Herkese Açık</span>';

            const tr = `
                <tr data-id="${service.id}" class="service-table-row">
                    <td class="ps-3">
                        <div class="d-flex align-items-center">
                            <span class="service-color-pill" style="background-color: ${color};"></span>
                            <div>
                                <span class="fw-bold text-dark fs-6 service-name-link" role="button" data-id="${service.id}">${escapeHtml(service.name)}</span>
                                <div class="mt-1 d-flex align-items-center gap-1 flex-wrap">
                                    ${catBadge}
                                    ${natureBadge}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="fw-bold text-dark fs-6">${priceFmt}</div>
                        <div>${taxBadge}</div>
                    </td>
                    <td>${durationQuotaText}</td>
                    <td>${locationText}</td>
                    <td>${monthlyBadge}</td>
                    <td>${providerBadge}</td>
                    <td>${statusBadge}</td>
                    <td class="text-end pe-3">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-primary btn-edit-service" data-id="${service.id}" title="Düzenle">
                                <i class="fas fa-edit me-1"></i>Düzenle
                            </button>
                            <a href="${App.Utils.Url.siteUrl('?service=' + encodeURIComponent(service.id))}" target="_blank" class="btn btn-outline-secondary" title="Rezervasyon Linki">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger btn-delete-service-direct" data-id="${service.id}" data-name="${escapeHtml(service.name)}" title="Sil">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            $tbody.append(tr);
        });
    }

    /**
     * Update KPI counters at the top of the table view
     */
    function updateKpis(services) {
        const totalServices = services.length;
        let totalMonthlyBookings = 0;
        let totalPriceSum = 0;
        const providerIdsSet = new Set();

        services.forEach((s) => {
            totalMonthlyBookings += Number(s.monthly_count || 0);
            totalPriceSum += Number(s.price || 0);
            if (Array.isArray(s.providers)) {
                s.providers.forEach(p => providerIdsSet.add(p));
            }
        });

        const avgPrice = totalServices > 0 ? (totalPriceSum / totalServices) : 0;

        $('#badge-total-services').text(totalServices + ' Hizmet');
        $('#kpi-total-services').text(totalServices);
        $('#kpi-monthly-bookings').text(totalMonthlyBookings);
        $('#kpi-active-providers').text(providerIdsSet.size);
        $('#kpi-avg-price').text('₺' + avgPrice.toLocaleString('tr-TR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }));
    }

    /**
     * Filter table items client-side
     */
    function applyTableFilters() {
        const searchVal = $('#table-search-input').val().toLowerCase().trim();
        const catVal = $('#table-category-filter').val();
        const natureVal = $('#table-nature-filter').val();
        const branchVal = $('#table-branch-filter').val();

        const filtered = (filterResults || []).filter((s) => {
            if (searchVal) {
                const nameMatch = (s.name || '').toLowerCase().includes(searchVal);
                const descMatch = (s.description || '').toLowerCase().includes(searchVal);
                const catMatch = (s.category_name || '').toLowerCase().includes(searchVal);
                if (!nameMatch && !descMatch && !catMatch) return false;
            }
            if (catVal && String(s.id_service_categories) !== String(catVal)) {
                return false;
            }
            if (natureVal && (s.service_nature || s.access_type || 'duration') !== natureVal) {
                return false;
            }
            if (branchVal && (s.location || '').trim() !== branchVal.trim()) {
                return false;
            }
            return true;
        });

        renderServicesTable(filtered);
    }

    /**
     * Add page event listeners.
     */
    function addEventListeners() {
        // Table search input
        $('#table-search-input').on('input', applyTableFilters);
        $('#table-category-filter, #table-nature-filter, #table-branch-filter').on('change', applyTableFilters);

        // Reset table filters
        $('#btn-reset-table-filters').on('click', () => {
            $('#table-search-input').val('');
            $('#table-category-filter').val('');
            $('#table-nature-filter').val('');
            $('#table-branch-filter').val('');
            renderServicesTable(filterResults);
        });

        // Add service button click
        $(document).on('click', '#btn-create-service, #add-service, #btn-empty-add-service', () => {
            showEditorView(null);
        });

        // Back to table button
        $(document).on('click', '#btn-back-to-table, #cancel-service', () => {
            showTableView();
        });

        // Edit service button click from table row
        $(document).on('click', '.btn-edit-service, .service-name-link', function () {
            const serviceId = $(this).data('id');
            showEditorView(serviceId);
        });

        // Direct delete button on table row
        $(document).on('click', '.btn-delete-service-direct', function () {
            const serviceId = $(this).data('id');
            const serviceName = $(this).data('name') || 'bu hizmeti';
            if (confirm(`"${serviceName}" hizmetini silmek istediğinize emin misiniz?`)) {
                remove(serviceId);
            }
        });

        // Save service button
        $('#save-service').on('click', () => {
            const nature = $serviceNature.val() || 'duration';
            const isFollowUp = Number($('#follow-up-required').prop('checked'));
            const catVal = $serviceCategoryId.val();

            const service = {
                name: $name.val() ? $name.val().trim() : '',
                service_nature: nature,
                access_type: nature,
                tax_rate: ($taxRate.val() !== '' && $taxRate.val() !== null) ? Number($taxRate.val()) : 20.00,
                price: ($price.val() !== '' && $price.val() !== null) ? Number($price.val()) : 0.00,
                currency: $currency.val() || '₺',
                description: $description.val() ? $description.val().trim() : '',
                location: $location.val() ? $location.val().trim() : '',
                color: App.Components.ColorSelection.getColor($color),
                is_private: Number($isPrivate.prop('checked')),
                id_service_categories: (catVal && catVal !== '' && catVal !== 'null') ? Number(catVal) : null,
                follow_up_required: isFollowUp,
                follow_up_category: isFollowUp ? ($('#follow-up-category').val() || 'medical_protocol') : null,
                follow_up_priority: isFollowUp ? ($('#follow-up-priority').val() || 'standard') : 'optional',
                follow_up_delay_override: isFollowUp ? ($('#follow-up-delay-override').val() || '24 hours') : null,
                follow_up_message_override: isFollowUp ? ($('#follow-up-message-override').val() || '') : null,
                crm_follow_up_rules: isFollowUp ? (serviceFollowUpRules || []) : [],
            };

            if (['duration', 'packaged', 'quantity_timed', 'provider_custom_duration'].includes(nature)) {
                service.duration = Number($duration.val() || 60);
                service.slot_interval = Number($slotInterval.val() || 15);
                service.attendants_number = Number($attendantsNumber.val() || 1);
                if (nature === 'packaged' || nature === 'quantity_timed') {
                    service.total_passes = Number($totalPasses.val() || 10);
                    service.pass_validity_days = 90;
                }
            } else if (nature === 'daily_pass') {
                service.duration = 480;
                service.slot_interval = 60;
                service.attendants_number = 1;
                service.valid_hours_start = $validHoursStart.val() || '09:00';
                service.valid_hours_end = $validHoursEnd.val() || '18:00';
                service.daily_capacity = ($dailyCapacity.val() && $dailyCapacity.val() !== '') ? Number($dailyCapacity.val()) : null;
            } else if (nature === 'multi_pass') {
                service.duration = 0;
                service.slot_interval = 15;
                service.attendants_number = 1;
                service.pass_validity_days = Number($passValidityDays.val() || 30);
                if ($multiPassQuotaType.val() === 'fixed') {
                    service.total_passes = Number($multiPassQuotaNumber.val() || 30);
                } else {
                    service.total_passes = 0; // unlimited
                }
            }

            // Providers & per-provider durations
            service.providers = [];
            const providerDurations = {};
            if (['duration', 'packaged', 'quantity_timed', 'provider_custom_duration'].includes(nature)) {
                $('#service-providers .provider-checkbox').each((index, checkboxEl) => {
                    if ($(checkboxEl).prop('checked')) {
                        const pid = $(checkboxEl).attr('data-id');
                        service.providers.push(pid);
                        if (nature === 'provider_custom_duration') {
                            const customDur = Number($(`.provider-duration-input[data-provider-id="${pid}"]`).val());
                            providerDurations[pid] = (customDur && customDur > 0) ? customDur : Number($duration.val() || 60);
                        }
                    }
                });
            }
            if (nature === 'provider_custom_duration') {
                service.provider_durations = providerDurations;
            }

            if ($id.val() !== '') {
                service.id = Number($id.val());
            }

            if (!validate()) {
                return;
            }

            save(service);
        });

        // Delete button inside editor
        $('#delete-service').on('click', () => {
            const serviceId = $id.val();
            if (confirm('Bu hizmet kaydını silmek istediğinize emin misiniz?')) {
                remove(serviceId);
            }
        });

        // Providers selection buttons
        $('#select-all-providers').on('click', () => {
            $('#service-providers input:checkbox').prop('checked', true);
        });

        $('#select-none-providers').on('click', () => {
            $('#service-providers input:checkbox').prop('checked', false);
        });

        // Service nature change
        $serviceNature.on('change', () => {
            applyServiceNature($serviceNature.val());
        });

        // Multi pass quota type change
        $multiPassQuotaType.on('change', function () {
            if ($(this).val() === 'fixed') {
                $multiPassQuotaNumber.show();
            } else {
                $multiPassQuotaNumber.hide();
            }
        });

        // Add-on modal opener
        $('#btn-add-addon-modal').on('click', () => {
            const serviceId = $id.val();
            if (!serviceId) {
                alert('Ek hizmet eklemek için lütfen önce hizmeti kaydediniz.');
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
        $(document).on('click', '.btn-delete-addon', function () {
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
        $('#btn-add-consumable-modal').on('click', () => {
            const serviceId = $id.val();
            if (!serviceId) {
                alert('Sarf reçetesi eklemek için lütfen önce hizmeti kaydediniz.');
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
        $(document).on('click', '.btn-delete-consumable', function () {
            const recId = $(this).data('id');
            const serviceId = $id.val();
            if (!confirm('Bu sarf malzemeyi reçeteden çıkarmak istediğinize emin misiniz?')) return;
            $.post(App.Utils.Url.siteUrl('services/delete_consumable/' + recId), {
                csrf_token: vars('csrf_token'),
            }, () => {
                loadConsumables(serviceId);
                App.Layouts.Backend.displayNotification('Sarf malzeme reçeteden çıkarıldı.');
            });
        });

        // Follow-up required toggle
        $('#follow-up-required').on('change', function () {
            if ($(this).prop('checked')) {
                $('#follow-up-config-body').slideDown(200);
                if (!serviceFollowUpRules || serviceFollowUpRules.length === 0) {
                    syncDefaultFollowUpStep();
                }
            } else {
                $('#follow-up-config-body').slideUp(200);
            }
        });

        // Quick delay buttons
        $(document).on('click', '.btn-quick-delay', function () {
            $('#follow-up-delay-override').val($(this).data('delay'));
        });

        // Variable inserter in follow-up step modal
        $(document).on('click', '.btn-insert-var', function () {
            const varTag = $(this).data('var');
            const $ta = $('#follow-up-message-input');
            const cur = $ta.val();
            $ta.val(cur + (cur.length > 0 && !cur.endsWith(' ') ? ' ' : '') + varTag + ' ').focus();
        });

        // Open follow-up step modal to add a new step
        $('#btn-add-follow-up-modal').on('click', () => {
            $('#modal-follow-up-title').html('<i class="fas fa-plus text-primary me-2"></i>Yeni Takip Adımı Ekle');
            $('#follow-up-rule-index').val('-1');
            $('#follow-up-trigger-select').val('appointment_completed');
            $('#follow-up-delay-select').val('24_hours');
            $('#follow-up-channel-select').val('whatsapp');
            $('#follow-up-action-select').val($('#follow-up-category').val() || 'medical_protocol');
            $('#follow-up-message-input').val($('#follow-up-message-override').val() || '');
            showModal('modal-follow-up-form');
        });

        // Edit follow-up step
        $(document).on('click', '.btn-edit-follow-up', function () {
            const idx = Number($(this).data('index'));
            const rule = serviceFollowUpRules[idx];
            if (!rule) return;
            $('#modal-follow-up-title').html('<i class="fas fa-edit text-primary me-2"></i>Takip Adımını Düzenle (Adım ' + (idx + 1) + ')');
            $('#follow-up-rule-index').val(idx);
            $('#follow-up-trigger-select').val(rule.trigger || 'appointment_completed');
            $('#follow-up-delay-select').val(rule.delay || '24_hours');
            $('#follow-up-channel-select').val(rule.channel || 'whatsapp');
            $('#follow-up-action-select').val(rule.action || 'medical_protocol');
            $('#follow-up-message-input').val(rule.message || '');
            showModal('modal-follow-up-form');
        });

        // Delete follow-up step
        $(document).on('click', '.btn-delete-follow-up', function () {
            const idx = Number($(this).data('index'));
            serviceFollowUpRules.splice(idx, 1);
            renderFollowUpRules();
            App.Layouts.Backend.displayNotification('Takip adımı silindi.');
        });

        // Submit follow-up step form (Add or Edit)
        $('#btn-save-follow-up-submit').on('click', () => {
            const idx = Number($('#follow-up-rule-index').val());
            const newRule = {
                trigger: $('#follow-up-trigger-select').val(),
                delay: $('#follow-up-delay-select').val(),
                channel: $('#follow-up-channel-select').val(),
                action: $('#follow-up-action-select').val(),
                message: $('#follow-up-message-input').val().trim(),
            };

            if (idx >= 0 && idx < serviceFollowUpRules.length) {
                serviceFollowUpRules[idx] = newRule;
                App.Layouts.Backend.displayNotification('Takip adımı güncellendi.');
            } else {
                serviceFollowUpRules.push(newRule);
                App.Layouts.Backend.displayNotification('Yeni takip adımı eklendi.');
            }
            hideModal('modal-follow-up-form');
            renderFollowUpRules();
        });

        // Follow-up template pills
        $(document).on('click', '.btn-template-pill', function () {
            const type = $(this).data('type');
            if (type === 'medication') {
                $('#follow-up-category').val('medical_protocol');
                $('#follow-up-priority').val('critical');
                $('#follow-up-delay-override').val('2 hours');
                $('#follow-up-message-override').val('Sayın {{customer_name}}, {{service_name}} işlemi sonrası doktorunuzun/uzmanınızın reçete ettiği ilaç ve destek ürünlerini saatinde almayı lütfen unutmayınız.');
                serviceFollowUpRules = [
                    {
                        trigger: 'appointment_completed',
                        delay: '2_hours',
                        channel: 'whatsapp',
                        action: 'medical_protocol',
                        message: 'Sayın {{customer_name}}, {{service_name}} işlemi sonrası doktorunuzun/uzmanınızın reçete ettiği ilaç ve destek ürünlerini saatinde almayı lütfen unutmayınız.',
                    },
                    {
                        trigger: 'appointment_completed',
                        delay: '24_hours',
                        channel: 'whatsapp',
                        action: 'medical_protocol',
                        message: 'Merhaba {{customer_name}}, tedavinizin 2. günündesiniz. İlaç kullanımınızı düzenli sürdürüyor musunuz? Danışmak istediğiniz bir konu var mı?',
                    }
                ];
            } else if (type === 'photo') {
                $('#follow-up-category').val('photo_checkin');
                $('#follow-up-priority').val('standard');
                $('#follow-up-delay-override').val('24 hours');
                $('#follow-up-message-override').val('Merhaba {{customer_name}}, {{service_name}} uygulamasının üzerinden 24 saat geçti. Cildinizdeki iyileşme sürecini ve doku durumunu takip edebilmemiz için lütfen işlem bölgesinin güncel bir fotoğrafını bu mesaja yanıt olarak iletir misiniz?');
                serviceFollowUpRules = [
                    {
                        trigger: 'appointment_completed',
                        delay: '24_hours',
                        channel: 'whatsapp',
                        action: 'photo_checkin',
                        message: 'Merhaba {{customer_name}}, {{service_name}} uygulamasının üzerinden 24 saat geçti. Doku iyileşme sürecinizi takip edebilmemiz için işlem bölgesinin fotoğrafını paylaşabilir misiniz?',
                    },
                    {
                        trigger: 'appointment_completed',
                        delay: '3_days',
                        channel: 'whatsapp',
                        action: 'photo_checkin',
                        message: 'Sayın {{customer_name}}, 3. gün cilt analiziniz için lütfen gün ışığında net bir fotoğrafınızı uzmanımızla paylaşınız.',
                    }
                ];
            } else if (type === 'soap') {
                $('#follow-up-category').val('medical_reaction');
                $('#follow-up-priority').val('critical');
                $('#follow-up-delay-override').val('24 hours');
                $('#follow-up-message-override').val('Sayın {{customer_name}}, {{service_name}} tedaviniz sonrasında genel durumunuz nasıl? Herhangi bir ağrı, şişlik veya beklenmeyen bir reaksiyon hissediyor musunuz?');
                serviceFollowUpRules = [
                    {
                        trigger: 'appointment_completed',
                        delay: '2_hours',
                        channel: 'whatsapp',
                        action: 'aftercare_safety',
                        message: 'Sayın {{customer_name}}, {{service_name}} işlemi tamamlandı. İlk 24 saat işlem bölgesine sıcak su temas ettirmemenizi ve önerilen bakım losyonunu uygulamanızı öneririz.',
                    },
                    {
                        trigger: 'appointment_completed',
                        delay: '24_hours',
                        channel: 'whatsapp',
                        action: 'medical_reaction',
                        message: 'Sayın {{customer_name}}, {{service_name}} tedaviniz sonrasında genel durumunuz nasıl? Herhangi bir ağrı, şişlik veya beklenmeyen bir reaksiyon hissediyor musunuz?',
                    }
                ];
            }
            renderFollowUpRules();
            App.Layouts.Backend.displayNotification('Şablon takip adımları ve mesajları yüklendi.');
        });

        // Toggle Contract Link
        $(document).on('change', '.btn-toggle-contract', function () {
            const serviceId = $id.val();
            if (!serviceId) {
                alert('Sözleşme bağlamak için lütfen önce hizmeti kaydedin.');
                $(this).prop('checked', false);
                return;
            }
            const contractId = $(this).data('contract-id');
            const isChecked = $(this).prop('checked');
            const $label = $(this).siblings('label');

            $.ajax({
                url: App.Utils.Url.siteUrl('services/toggle_contract_link'),
                type: 'POST',
                data: {
                    csrf_token: vars('csrf_token'),
                    service_id: Number(serviceId),
                    contract_id: Number(contractId),
                    link: isChecked ? 1 : 0
                },
                success: () => {
                    if (isChecked) {
                        $label.html('<span class="text-success fw-bold">Bağlı (Aktif)</span>');
                        App.Layouts.Backend.displayNotification('Sözleşme bu hizmete bağlandı.');
                    } else {
                        $label.text('Bağlı Değil');
                        App.Layouts.Backend.displayNotification('Sözleşme bağlantısı kaldırıldı.');
                    }
                    const currentBadge = Number($('#badge-tab-contracts').text()) || 0;
                    $('#badge-tab-contracts').text(Math.max(0, currentBadge + (isChecked ? 1 : -1)));
                },
                error: () => {
                    alert('Sözleşme bağlantı durumu güncellenemedi.');
                    $(this).prop('checked', !isChecked);
                }
            });
        });

        // Variable insert buttons in contract modal
        $(document).on('click', '.btn-contract-var', function () {
            const varTag = $(this).data('var');
            const $txt = $('#contract-content-input');
            const curVal = $txt.val();
            $txt.val(curVal + (curVal.length && !curVal.endsWith(' ') ? ' ' : '') + varTag + ' ');
            $txt.focus();
        });

        // Contract preview button with dynamic live placeholder rendering
        $(document).on('click', '.btn-preview-contract', function () {
            const contractId = $(this).data('contract-id');
            const serviceId = $id.val() || 0;
            const fallbackTitle = $(this).data('title') || 'Sözleşme / Onam Formu';
            const fallbackContent = $(this).data('content') || '';

            $('#contract-preview-title').html('<i class="fas fa-file-signature text-primary me-2"></i>' + escapeHtml(fallbackTitle));
            $('#contract-preview-body').html('<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Müşteri canlı verileri derleniyor...</div>');
            $('#contract-preview-raw').text(fallbackContent);
            showModal('modal-contract-preview');

            if (contractId) {
                $.get(App.Utils.Url.siteUrl('services/preview_contract/' + contractId + '/' + serviceId))
                    .done((res) => {
                        if (res && res.success) {
                            $('#contract-preview-body').html(res.rendered_html);
                            $('#contract-preview-raw').text(res.raw_content);
                        }
                    })
                    .fail(() => {
                        $('#contract-preview-body').html(fallbackContent || '<p class="text-muted">Metin içeriği bulunmuyor.</p>');
                    });
            }
        });

        // Add contract modal opener
        $('#btn-add-contract-modal').on('click', () => {
            $('#contract-title-input').val('');
            $('#contract-mandatory-input').prop('checked', true);
            $('#contract-content-input').val('');
            showModal('modal-contract-form');
        });

        // Add contract submit
        $('#btn-save-contract-submit').on('click', () => {
            const serviceId = $id.val();
            const title = $('#contract-title-input').val().trim();
            const content = $('#contract-content-input').val().trim();
            const isMandatory = $('#contract-mandatory-input').prop('checked') ? 1 : 0;

            if (!title) {
                alert('Lütfen sözleşme / onam başlığını girin.');
                return;
            }

            $.ajax({
                url: App.Utils.Url.siteUrl('services/save_contract'),
                type: 'POST',
                data: {
                    csrf_token: vars('csrf_token'),
                    service_id: Number(serviceId || 0),
                    title: title,
                    content_html: content,
                    is_mandatory: isMandatory
                },
                success: () => {
                    hideModal('modal-contract-form');
                    App.Layouts.Backend.displayNotification('Yeni sözleşme şablonu oluşturuldu ve hizmete bağlandı.');
                    if (serviceId) {
                        loadContracts(serviceId);
                    }
                },
                error: (xhr) => {
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Sözleşme kaydedilemedi.';
                    alert(msg);
                }
            });
        });

        // Open Legal Catalog modal
        let loadedCatalogData = [];
        $('#btn-catalog-contract-modal').on('click', () => {
            showModal('modal-catalog-contract');
            loadLegalCatalog();
        });

        function loadLegalCatalog() {
            const $container = $('#catalog-templates-container');
            $container.html('<div class="text-center py-5 text-muted col-12"><i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>Sektörel onam kataloğu yükleniyor...</div>');

            $.get(App.Utils.Url.siteUrl('services/get_legal_catalog'))
                .done((res) => {
                    loadedCatalogData = (res && res.catalog) ? res.catalog : [];
                    renderCatalogCards(loadedCatalogData);
                })
                .fail(() => {
                    $container.html('<div class="alert alert-danger col-12">Katalog yüklenirken bir hata oluştu.</div>');
                });
        }

        function renderCatalogCards(items) {
            const $container = $('#catalog-templates-container');
            $container.empty();

            if (!items.length) {
                $container.html('<div class="text-center py-5 text-muted col-12">Arama kriterlerine uygun şablon bulunamadı.</div>');
                return;
            }

            items.forEach((item) => {
                const card = `
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border shadow-sm p-3 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start gap-1 mb-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">${escapeHtml(item.category)}</span>
                                    <span class="badge ${item.is_mandatory ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary'} small">
                                        ${item.is_mandatory ? 'Zorunlu' : 'İsteğe Bağlı'}
                                    </span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">${escapeHtml(item.title)}</h6>
                                <p class="small text-muted mb-2" style="font-size: 0.8rem; min-height: 40px;">${escapeHtml(item.description || '')}</p>
                                <div class="small text-secondary mb-3" style="font-size: 0.75rem;">
                                    <i class="fas fa-book-medical text-info me-1"></i>Kaynak: <em>${escapeHtml(item.source_reference || 'Resmi Mevzuat')}</em>
                                </div>
                            </div>
                            <div class="d-flex gap-2 pt-2 border-top">
                                <button type="button" class="btn btn-sm btn-outline-secondary w-50 btn-quick-preview-catalog" 
                                        data-title="${escapeHtml(item.title)}" 
                                        data-content="${escapeHtml(item.content_html)}">
                                    <i class="fas fa-eye me-1"></i>İncele
                                </button>
                                <button type="button" class="btn btn-sm btn-success w-50 btn-import-catalog-item" data-code="${escapeHtml(item.code)}">
                                    <i class="fas fa-plus-circle me-1"></i>Hizmete Ekle
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                $container.append(card);
            });
        }

        // Filter catalog cards
        $('#catalog-search-input, #catalog-category-filter').on('input change', function () {
            const q = ($('#catalog-search-input').val() || '').toLowerCase();
            const cat = $('#catalog-category-filter').val();

            const filtered = loadedCatalogData.filter((item) => {
                const matchQ = !q || item.title.toLowerCase().includes(q) || (item.description || '').toLowerCase().includes(q) || (item.keywords || []).some(k => k.toLowerCase().includes(q));
                const matchCat = !cat || item.category === cat;
                return matchQ && matchCat;
            });
            renderCatalogCards(filtered);
        });

        // Quick preview from catalog
        $(document).on('click', '.btn-quick-preview-catalog', function () {
            const title = $(this).data('title');
            const content = $(this).data('content');
            $('#contract-preview-title').html('<i class="fas fa-file-contract text-primary me-2"></i>' + escapeHtml(title));
            $('#contract-preview-body').html(content);
            $('#contract-preview-raw').text(content);
            showModal('modal-contract-preview');
        });

        // Import template from catalog to current service
        $(document).on('click', '.btn-import-catalog-item', function () {
            const code = $(this).data('code');
            const serviceId = $id.val();
            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Ekleniyor...');

            $.ajax({
                url: App.Utils.Url.siteUrl('services/import_catalog_template'),
                type: 'POST',
                data: {
                    csrf_token: vars('csrf_token'),
                    code: code,
                    service_id: Number(serviceId || 0)
                },
                success: () => {
                    hideModal('modal-catalog-contract');
                    App.Layouts.Backend.displayNotification('Şablon başarıyla eklendi ve bu hizmete bağlandı! ✓', 'success');
                    if (serviceId) {
                        loadContracts(serviceId);
                    }
                },
                error: (xhr) => {
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Şablon eklenemedi.';
                    alert(msg);
                },
                complete: () => {
                    $btn.prop('disabled', false).html('<i class="fas fa-plus-circle me-1"></i>Hizmete Ekle');
                }
            });
        });
    }

    /**
     * Save service record to database.
     */
    function save(service) {
        App.Http.Services.save(service).then((response) => {
            App.Layouts.Backend.displayNotification(lang('service_saved') || 'Hizmet başarıyla kaydedildi.');
            filter('', response.id);
            showTableView();
        }).fail((xhr) => {
            const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Hizmet kaydedilirken bir hata oluştu.';
            App.Layouts.Backend.displayNotification(msg, 'danger');
        });
    }

    /**
     * Delete a service record from database.
     */
    function remove(id) {
        App.Http.Services.destroy(id).then(() => {
            App.Layouts.Backend.displayNotification(lang('service_deleted') || 'Hizmet silindi.');
            resetForm();
            filter('');
            showTableView();
        }).fail((xhr) => {
            const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Hizmet silinirken bir hata oluştu.';
            App.Layouts.Backend.displayNotification(msg, 'danger');
        });
    }

    /**
     * Dynamically adjusts form fields and visibility based on selected service nature.
     */
    function applyServiceNature(nature) {
        nature = nature || 'duration';
        $accessType.val(nature);

        switch (nature) {
            case 'duration':
                $sectionTimedSettings.show();
                $packageSessionsContainer.hide();
                $durationContainer.show();
                $durationLabel.text(lang('duration_minutes') || 'Süre (Dakika)');
                $duration.addClass('required').prop('min', vars('event_minimum_duration') || 5);
                $slotInterval.addClass('required');
                $attendantsNumber.addClass('required');
                $sectionDailyPassSettings.hide();
                $sectionMultiPassSettings.hide();
                $sectionProviders.show();
                $priceUnitLabel.text('(Tek Seans Ücreti)');
                $('.provider-custom-duration-container').hide();
                break;

            case 'packaged':
                $sectionTimedSettings.show();
                $packageSessionsContainer.show();
                $totalPasses.addClass('required');
                $durationContainer.show();
                $durationLabel.text('Seans Başı Süre (Dakika)');
                $duration.addClass('required').prop('min', vars('event_minimum_duration') || 5);
                $slotInterval.addClass('required');
                $attendantsNumber.addClass('required');
                $sectionDailyPassSettings.hide();
                $sectionMultiPassSettings.hide();
                $sectionProviders.show();
                $priceUnitLabel.text('(Toplam Paket Satış Fiyatı)');
                $('.provider-custom-duration-container').hide();
                break;

            case 'provider_custom_duration':
                $sectionTimedSettings.show();
                $packageSessionsContainer.hide();
                $durationContainer.show();
                $durationLabel.text('Varsayılan Süre (Dakika)');
                $duration.addClass('required').prop('min', vars('event_minimum_duration') || 5);
                $slotInterval.addClass('required');
                $attendantsNumber.addClass('required');
                $sectionDailyPassSettings.hide();
                $sectionMultiPassSettings.hide();
                $sectionProviders.show();
                $priceUnitLabel.text('(Hizmet Satış Fiyatı)');
                $('.provider-custom-duration-container').show();
                break;

            case 'daily_pass':
                $sectionTimedSettings.hide();
                $sectionDailyPassSettings.show();
                $sectionMultiPassSettings.hide();
                $sectionProviders.hide();
                $duration.removeClass('required');
                $slotInterval.removeClass('required');
                $attendantsNumber.removeClass('required');
                $priceUnitLabel.text('(Günlük Giriş Ücreti)');
                $('.provider-custom-duration-container').hide();
                break;

            case 'multi_pass':
                $sectionTimedSettings.hide();
                $sectionDailyPassSettings.hide();
                $sectionMultiPassSettings.show();
                $sectionProviders.hide();
                $duration.removeClass('required');
                $slotInterval.removeClass('required');
                $attendantsNumber.removeClass('required');
                $priceUnitLabel.text('(Kart / Abonelik Satış Fiyatı)');
                $('.provider-custom-duration-container').hide();
                break;
        }
    }

    /**
     * Check if form is valid
     */
    function validate() {
        $('#services-editor-view .required').removeClass('is-invalid');
        let isValid = true;

        if (!$name.val() || !$name.val().trim()) {
            $name.addClass('is-invalid');
            isValid = false;
        }

        const nature = $serviceNature.val() || 'duration';
        if (['duration', 'packaged', 'quantity_timed', 'provider_custom_duration'].includes(nature)) {
            if (!$duration.val() || Number($duration.val()) < (vars('event_minimum_duration') || 5)) {
                $duration.addClass('is-invalid');
                isValid = false;
            }
        }

        if (nature === 'packaged') {
            if (!$totalPasses.val() || Number($totalPasses.val()) < 1) {
                $totalPasses.addClass('is-invalid');
                isValid = false;
            }
        }

        if (!isValid) {
            App.Layouts.Backend.displayNotification('Lütfen zorunlu alanları eksiksiz doldurunuz.', 'warning');
        }

        return isValid;
    }

    /**
     * Reset service form
     */
    function resetForm() {
        $id.val('');
        $name.val('');
        $duration.val('60');
        $price.val('0.00');
        $currency.val('₺');
        $description.val('');
        $location.val('');
        $slotInterval.val('15');
        $attendantsNumber.val('1');
        $totalPasses.val('10');
        $isPrivate.prop('checked', false);
        $serviceCategoryId.val('');
        $taxRate.val('20.00');
        $validHoursStart.val('09:00');
        $validHoursEnd.val('18:00');
        $dailyCapacity.val('');
        $passValidityDays.val('30');
        $multiPassQuotaType.val('unlimited');
        $multiPassQuotaNumber.hide();
        App.Components.ColorSelection.setColor($color, '#4338ca');
        $('#service-providers input:checkbox').prop('checked', false);
        $('.provider-duration-input').val('60');
        $('#follow-up-required').prop('checked', false);
        $('#follow-up-config-body').hide();
        $('#follow-up-message-override').val('');
        serviceFollowUpRules = [];
        renderFollowUpRules();
        applyServiceNature('duration');
    }

    /**
     * Display a specific service in the editor
     */
    function display(service) {
        if (!service) return;

        $id.val(service.id);
        $name.val(service.name);

        const nature = service.service_nature || service.access_type || 'duration';
        $serviceNature.val(nature);
        applyServiceNature(nature);

        $taxRate.val(service.tax_rate !== null && service.tax_rate !== undefined ? Number(service.tax_rate).toFixed(2) : '20.00');
        $duration.val(service.duration || 60);
        $price.val(Number(service.price || 0).toFixed(2));
        $currency.val(service.currency || '₺');
        $description.val(service.description || '');

        // Location / Branch dropdown binding
        if (service.location) {
            const locVal = service.location.trim();
            if ($location.find('option[value="' + locVal + '"]').length === 0) {
                $location.append(new Option(locVal, locVal));
            }
            $location.val(locVal);
        } else {
            $location.val('');
        }

        $slotInterval.val(service.slot_interval || 15);
        $attendantsNumber.val(service.attendants_number || 1);
        $totalPasses.val(service.total_passes || 10);
        $validHoursStart.val(service.valid_hours_start || '09:00');
        $validHoursEnd.val(service.valid_hours_end || '18:00');
        $dailyCapacity.val(service.daily_capacity || '');
        $passValidityDays.val(service.pass_validity_days || '30');

        if (service.total_passes && Number(service.total_passes) > 0) {
            $multiPassQuotaType.val('fixed');
            $multiPassQuotaNumber.val(service.total_passes).show();
        } else {
            $multiPassQuotaType.val('unlimited');
            $multiPassQuotaNumber.hide();
        }

        $isPrivate.prop('checked', !!Number(service.is_private));
        App.Components.ColorSelection.setColor($color, service.color || '#4338ca');

        const serviceCategoryId = service.id_service_categories !== null ? service.id_service_categories : '';
        $serviceCategoryId.val(serviceCategoryId);

        // Submodules: Addons, Consumables, Follow-Up, Contracts
        loadAddons(service.id);
        loadConsumables(service.id);
        loadContracts(service.id);

        // Follow-up setup
        const isFollowUpReq = !!(Number(service.follow_up_required) || service.followUpRequired);
        $('#follow-up-required').prop('checked', isFollowUpReq);
        if (isFollowUpReq) {
            $('#follow-up-config-body').show();
        } else {
            $('#follow-up-config-body').hide();
        }
        $('#follow-up-category').val(service.follow_up_category || service.followUpCategory || 'medical_protocol');
        $('#follow-up-priority').val(service.follow_up_priority || service.followUpPriority || 'standard');
        $('#follow-up-delay-override').val(service.follow_up_delay_override || service.followUpDelayOverride || '24 hours');
        $('#follow-up-message-override').val(service.follow_up_message_override || service.followUpMessageOverride || '');

        serviceFollowUpRules = [];
        if (Array.isArray(service.crm_follow_up_rules)) {
            serviceFollowUpRules = service.crm_follow_up_rules;
        } else if (typeof service.crm_follow_up_rules === 'string' && service.crm_follow_up_rules.trim()) {
            try {
                serviceFollowUpRules = JSON.parse(service.crm_follow_up_rules) || [];
            } catch (e) {
                serviceFollowUpRules = [];
            }
        }
        renderFollowUpRules();

        // Providers
        $('#service-providers input:checkbox').prop('checked', false);
        if (service.providers && Array.isArray(service.providers)) {
            service.providers.forEach((pid) => {
                $('#service-providers input[data-id="' + pid + '"]').prop('checked', true);
            });
        }

        // Provider custom durations
        let pDurs = {};
        try {
            pDurs = typeof service.provider_durations === 'string'
                ? JSON.parse(service.provider_durations)
                : (service.provider_durations || {});
        } catch (e) {
            pDurs = {};
        }
        $('.provider-duration-input').each(function () {
            const pid = $(this).attr('data-provider-id');
            if (pDurs && pDurs[pid]) {
                $(this).val(pDurs[pid]);
            } else {
                $(this).val(service.duration || 60);
            }
        });
    }

    /**
     * Render Follow-Up Steps Table
     */
    function renderFollowUpRules() {
        const $tbody = $('#service-follow-up-table tbody');
        $tbody.empty();

        if (!serviceFollowUpRules || !serviceFollowUpRules.length) {
            $tbody.html('<tr class="text-muted text-center py-3"><td colspan="5">Kayıtlı takip adımı bulunamadı. "+ Yeni Adım Ekle" butonuna tıklayarak ilk adımı oluşturabilirsiniz.</td></tr>');
            $('#badge-tab-followup').text('0');
            return;
        }

        $('#badge-tab-followup').text(serviceFollowUpRules.length);

        const triggerLabels = {
            'appointment_completed': 'Randevu Tamamlandığında',
            'package_near_expiry': 'Paket Bitimine 1 Seans Kala',
            'service_purchased': 'Hizmet Satın Alındığında',
            'checkin_done': 'Check-in Yapıldığında',
        };

        const delayLabels = {
            'immediate': '⚡ Hemen (0 dk)',
            '2_hours': '⏱️ 2 Saat Sonra',
            '12_hours': '⏱️ 12 Saat Sonra',
            '24_hours': '⏱️ 24 Saat Sonra',
            '48_hours': '⏱️ 48 Saat Sonra',
            '3_days': '📅 3 Gün Sonra',
            '1_week': '📅 1 Hafta Sonra',
            '30_days': '📅 30 Gün Sonra',
        };

        const channelLabels = {
            'whatsapp': '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fab fa-whatsapp me-1"></i>WhatsApp</span>',
            'sms': '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="fas fa-comment-sms me-1"></i>SMS</span>',
            'email': '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="fas fa-envelope me-1"></i>E-Posta</span>',
        };

        const actionLabels = {
            'medical_protocol': '💊 İlaç Kullanımı & Tedavi Protokolü',
            'medical_reaction': '🩺 Klinik SOAP & Reaksiyon Kontrolü',
            'photo_checkin': '📸 Görsel / Fotoğraf Durum Kontrolü',
            'review_nps': '⭐ Memnuniyet & NPS Anketi',
            'renewal_reminder': '🔄 Paket Yenileme & Teklif',
            'tag_vip': '🏷️ "VIP" Etiketi Ekle',
            'aftercare_safety': '🩺 Bakım Sonrası Talimatları',
        };

        serviceFollowUpRules.forEach((rule, idx) => {
            const tr = `
                <tr>
                    <td class="ps-3">
                        <span class="badge bg-light text-secondary border me-1">Adım ${idx + 1}</span>
                        <span class="fw-semibold text-dark">${triggerLabels[rule.trigger] || escapeHtml(rule.trigger)}</span>
                    </td>
                    <td><span class="badge bg-light text-dark border">${delayLabels[rule.delay] || escapeHtml(rule.delay)}</span></td>
                    <td>${channelLabels[rule.channel] || escapeHtml(rule.channel)}</td>
                    <td>
                        <div class="fw-semibold text-dark mb-1">${actionLabels[rule.action] || escapeHtml(rule.action)}</div>
                        <div class="small text-muted font-monospace bg-light p-1 rounded border">${escapeHtml(rule.message || '(Özel mesaj yok)')}</div>
                    </td>
                    <td class="text-end pe-3">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary btn-edit-follow-up" data-index="${idx}" title="Düzenle">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete-follow-up" data-index="${idx}" title="Sil">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            $tbody.append(tr);
        });
    }

    /**
     * Default follow-up steps generator
     */
    function syncDefaultFollowUpStep() {
        const cat = $('#follow-up-category').val() || 'medical_protocol';
        const delay = $('#follow-up-delay-override').val() || '24 hours';
        let delayKey = '24_hours';
        if (delay.includes('2 hours')) delayKey = '2_hours';
        else if (delay.includes('48 hours')) delayKey = '48_hours';
        else if (delay.includes('3 days')) delayKey = '3_days';
        else if (delay.includes('0') || delay.includes('Hemen')) delayKey = 'immediate';

        serviceFollowUpRules = [
            {
                trigger: 'appointment_completed',
                delay: delayKey,
                channel: 'whatsapp',
                action: cat,
                message: $('#follow-up-message-override').val() || 'Merhaba {{customer_name}}, {{service_name}} işleminiz sonrasında durumunuzu takip etmek istedik.',
            }
        ];
        renderFollowUpRules();
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
                    $tbody.html('<tr class="text-muted text-center py-4"><td colspan="4">Kayıtlı ek hizmet bulunamadı.</td></tr>');
                    $('#badge-tab-addons').text('0');
                    return;
                }
                $('#badge-tab-addons').text(addons.length);
                addons.forEach((addon) => {
                    const tr = `
                        <tr>
                            <td class="ps-3 fw-semibold text-dark">${escapeHtml(addon.name)}</td>
                            <td>+${addon.duration_minutes || 0} dk</td>
                            <td class="text-primary fw-bold">+${Number(addon.price || 0).toFixed(2)} ₺</td>
                            <td class="text-end pe-3">
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
                $tbody.html('<tr class="text-danger text-center py-4"><td colspan="4">Ek hizmetler yüklenemedi.</td></tr>');
                $('#badge-tab-addons').text('0');
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
                    $tbody.html('<tr class="text-muted text-center py-4"><td colspan="6">Reçeteye ekli sarf malzeme bulunamadı.</td></tr>');
                    $summary.hide();
                    $('#badge-tab-consumables').text('0');
                    return;
                }

                $('#badge-tab-consumables').text(recipes.length);

                recipes.forEach((rec) => {
                    const unitCost = Number(rec.cost || 0);
                    const totalCost = Number(rec.line_total_cost || (rec.quantity_used * unitCost) || 0);
                    const unit = escapeHtml(rec.product_unit || 'adet');

                    const tr = `
                        <tr>
                            <td class="ps-3 fw-semibold text-dark">${escapeHtml(rec.product_name || 'Ürün #' + rec.id_products)}</td>
                            <td><span class="badge bg-light text-dark border">${rec.quantity_used} ${unit}</span></td>
                            <td class="text-muted">₺${unitCost.toFixed(2)}</td>
                            <td class="fw-semibold text-danger">₺${totalCost.toFixed(2)}</td>
                            <td><span class="badge ${Number(rec.stock_quantity) > 0 ? 'bg-success' : 'bg-danger'}">${rec.stock_quantity || 0} ${unit}</span></td>
                            <td class="text-end pe-3">
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
                $tbody.html('<tr class="text-danger text-center py-4"><td colspan="6">Sarf reçetesi yüklenemedi.</td></tr>');
                $summary.hide();
                $('#badge-tab-consumables').text('0');
            });
    }

    /**
     * Load digital contracts / waivers for the selected service.
     */
    function loadContracts(serviceId) {
        const $tbody = $('#service-contracts-table tbody');
        $tbody.html('<tr class="text-muted text-center py-3"><td colspan="5"><i class="fas fa-spinner fa-spin me-2"></i>Sözleşmeler yükleniyor...</td></tr>');

        $.get(App.Utils.Url.siteUrl('services/get_contracts/' + serviceId))
            .done((res) => {
                $tbody.empty();
                const contracts = (res && res.contracts) ? res.contracts : [];
                let linkedCount = 0;

                if (!contracts.length) {
                    $tbody.html('<tr class="text-muted text-center py-4"><td colspan="5">Sistemde kayıtlı sözleşme veya onam şablonu bulunamadı. "+ Yeni Sözleşme Şablonu Ekle" butonu ile oluşturabilirsiniz.</td></tr>');
                    $('#badge-tab-contracts').text('0');
                    return;
                }

                contracts.forEach((c) => {
                    if (c.is_linked) linkedCount++;
                    const isMandatoryBadge = c.is_mandatory
                        ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fas fa-exclamation-circle me-1"></i>Zorunlu</span>'
                        : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">İsteğe Bağlı</span>';

                    const tr = `
                        <tr>
                            <td class="ps-3">
                                <span class="fw-semibold text-dark">${escapeHtml(c.title)}</span>
                            </td>
                            <td>${isMandatoryBadge}</td>
                            <td>
                                <div class="form-check form-switch m-0 fs-5 d-flex align-items-center">
                                    <input class="form-check-input btn-toggle-contract" type="checkbox" 
                                           data-contract-id="${c.id}" 
                                           id="contract-switch-${c.id}"
                                           ${c.is_linked ? 'checked' : ''}>
                                    <label class="form-check-label small fw-semibold text-muted ms-2" for="contract-switch-${c.id}">
                                        ${c.is_linked ? '<span class="text-success fw-bold">Bağlı (Aktif)</span>' : 'Bağlı Değil'}
                                    </label>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="btn btn-xs btn-outline-info py-1 px-2 btn-preview-contract shadow-sm" 
                                        data-contract-id="${c.id}"
                                        data-title="${escapeHtml(c.title)}" 
                                        data-content="${escapeHtml(c.content_html || '')}">
                                    <i class="fas fa-eye me-1"></i>Metni Gör
                                </button>
                            </td>
                            <td class="text-end pe-3">
                                <span class="badge bg-light text-secondary border">Şablon #${c.id}</span>
                            </td>
                        </tr>
                    `;
                    $tbody.append(tr);
                });

                $('#badge-tab-contracts').text(linkedCount);
            })
            .fail(() => {
                $tbody.html('<tr class="text-danger text-center py-4"><td colspan="5">Sözleşmeler yüklenirken bir hata oluştu.</td></tr>');
                $('#badge-tab-contracts').text('0');
            });
    }

    /**
     * Filters service records depending on a string keyword.
     */
    function filter(keyword = '', selectId = null) {
        App.Http.Services.search(keyword, filterLimit).then((response) => {
            filterResults = response || [];
            renderServicesTable(filterResults);

            if (selectId) {
                showEditorView(selectId);
            }
        });
    }

    /**
     * Update available service categories in dropdowns.
     */
    function updateAvailableServiceCategories() {
        App.Http.ServiceCategories.search('', 999).then((response) => {
            $serviceCategoryId.empty();
            $serviceCategoryId.append(new Option('-- Kategori Seçin --', '')).val('');

            const $tableCatFilter = $('#table-category-filter');
            $tableCatFilter.find('option:not(:first)').remove();

            response.forEach((serviceCategory) => {
                $serviceCategoryId.append(new Option(serviceCategory.name, serviceCategory.id));
                $tableCatFilter.append(new Option(serviceCategory.name, serviceCategory.id));
            });
        });
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        addEventListeners();
        updateAvailableServiceCategories();
        filter('');
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        filter,
        save,
        remove,
        validate,
        resetForm,
        display,
        showTableView,
        showEditorView,
        addEventListeners,
    };
})();
