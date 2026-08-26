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
 * Salon Flora customization - "Google Entegrasyonları" page (2026-08-25). Mirrors the popup + postMessage
 * OAuth pattern already used by the existing Google Calendar sync (assets/js/utils/calendar_sync.js).
 */
(function () {
    $(document).on('click', '.google-connect-btn', function () {
        const ownerType = $(this).data('owner-type');
        const ownerId = $(this).data('owner-id');

        const services = $(
            '.google-service-checkbox[data-owner-type="' + ownerType + '"][data-owner-id="' + ownerId + '"]:checked',
        )
            .map((index, checkbox) => $(checkbox).val())
            .get();

        if (!services.length) {
            App.Layouts.Backend.displayNotification('Önce en az bir servis seçin.');
            return;
        }

        const authUrl = App.Utils.Url.siteUrl(
            'google_integrations/oauth/' + ownerType + '/' + ownerId + '?services=' + services.join(','),
        );

        window.open(authUrl, 'Ki Reservation', 'width=800, height=600');

        const onMessage = (event) => {
            if (event.origin !== window.location.origin || event.data !== 'google_integrations_oauth_success') {
                return;
            }

            window.removeEventListener('message', onMessage);
            location.reload();
        };

        window.addEventListener('message', onMessage);
    });

    $(document).on('click', '.google-target-btn', function () {
        const $button = $(this);
        const target = $button.data('target'); // 'drive' | 'sheets'
        const mode = $button.data('mode'); // 'existing' | 'create'
        const ownerType = $button.data('owner-type');
        const ownerId = $button.data('owner-id');

        const $input = $('#' + target + '-target-value');
        const value = $input.val().trim();

        if (mode === 'existing' && !value) {
            App.Layouts.Backend.displayNotification('Önce link veya ID girin.');
            return;
        }

        $button.prop('disabled', true);

        $.post(App.Utils.Url.siteUrl('google_integrations/set_' + target + '_target'), {
            csrf_token: vars('csrf_token'),
            owner_type: ownerType,
            owner_id: ownerId,
            mode,
            value,
        })
            .done((response) => {
                if (!response.success) {
                    App.Layouts.Backend.displayNotification(response.message || 'İşlem başarısız.');
                    $button.prop('disabled', false);
                    return;
                }

                App.Layouts.Backend.displayNotification('Hedef güncellendi.');
                location.reload();
            })
            .fail((jqXHR) => {
                App.Layouts.Backend.displayNotification(jqXHR.responseJSON?.message || 'İşlem başarısız.');
                $button.prop('disabled', false);
            });
    });

    $(document).on('click', '.google-disconnect-btn', function () {
        const ownerType = $(this).data('owner-type');
        const ownerId = $(this).data('owner-id');

        App.Utils.Message.show(lang('delete'), 'Bu Google bağlantısını kesmek istediğinize emin misiniz?', [
            {
                text: lang('cancel'),
                click: (event, messageModal) => messageModal.hide(),
            },
            {
                text: lang('delete'),
                click: (event, messageModal) => {
                    messageModal.hide();

                    $.post(App.Utils.Url.siteUrl('google_integrations/disconnect'), {
                        csrf_token: vars('csrf_token'),
                        owner_type: ownerType,
                        owner_id: ownerId,
                    }).done(() => {
                        App.Layouts.Backend.displayNotification('Bağlantı kesildi.');
                        location.reload();
                    });
                },
            },
        ]);
    });

    /**
     * Salon Flora customization (2026-08-25) - "Veri Senkronizasyonları" wizard: pick a module, pick the
     * spreadsheet tab, choose which fields go to which columns (in the order chosen), pick a KVKK/HIPAA
     * posture, save. Field catalogs come pre-rendered from the backend (window.ki_sheet_field_catalogs) so
     * this stays a single JS file with no extra endpoint just to describe "what fields exist".
     */
    const catalogs = window.ki_sheet_field_catalogs || {};
    const spreadsheetId = window.ki_sheets_spreadsheet_id;

    function renderFieldList(moduleKey) {
        const catalog = catalogs[moduleKey] || {};
        const groups = {};

        Object.keys(catalog).forEach((key) => {
            const field = catalog[key];
            groups[field.group] = groups[field.group] || [];
            groups[field.group].push({ key, ...field });
        });

        let html = '';

        Object.keys(groups).forEach((group) => {
            html += '<div class="col-12 mb-2"><strong>' + group + '</strong></div>';
            groups[group].forEach((field) => {
                html +=
                    '<div class="col-sm-6">' +
                    '<div class="form-check">' +
                    '<input type="checkbox" class="form-check-input wizard-field-checkbox" ' +
                    'id="wizard-field-' +
                    field.key +
                    '" value="' +
                    field.key +
                    '" data-pii="' +
                    (field.pii ? '1' : '0') +
                    '">' +
                    '<label class="form-check-label" for="wizard-field-' +
                    field.key +
                    '">' +
                    field.label +
                    (field.pii ? ' <span class="badge bg-warning text-dark">Kişisel Veri</span>' : '') +
                    '</label>' +
                    '</div>' +
                    '</div>';
            });
        });

        $('#wizard-field-list').html(html);
    }

    function updatePiiWarning() {
        const mode = $('#wizard-pii-mode').val();
        const hasPii = $('.wizard-field-checkbox:checked[data-pii="1"]').length > 0;
        const $warning = $('#wizard-pii-warning');

        if (!hasPii) {
            $warning.removeClass('text-danger text-success').text('');
            return;
        }

        if (mode === 'plaintext') {
            $warning.removeClass('text-success').addClass('text-danger').text('Bu ayar KVKK/HIPAA uyumlu değildir.');
        } else {
            $warning.removeClass('text-danger').addClass('text-success').text('Seçilen kişisel veri alanları korunacak.');
        }
    }

    function loadSheetTabs() {
        const $select = $('#wizard-sheet-title');
        $select.html('<option>Yükleniyor...</option>');

        $.get(App.Utils.Url.siteUrl('google_integrations/sheet_tabs'), { spreadsheet_id: spreadsheetId })
            .done((response) => {
                if (!response.success) {
                    $select.html('<option>Sayfa bulunamadı</option>');
                    return;
                }

                $select.html(
                    response.tabs
                        .map((tab) => '<option value="' + tab.title + '">' + tab.title + '</option>')
                        .join(''),
                );

                // Ki Reservation (2026-08-26) - prefill from whichever tab ends up selected by default.
                prefillFromExistingHeader();
            })
            .fail(() => $select.html('<option>Sayfa listesi alınamadı</option>'));
    }

    /**
     * Ki Reservation (2026-08-26) - fetch the selected tab's existing header row (Google_integrations::
     * sheet_header()) and, for any cell whose text exactly matches a known field's label
     * (case-insensitive), pre-check that field's checkbox. Gives the admin a head start when reusing
     * a spreadsheet that already has a header row from a previous export/manual setup, without
     * guessing at column order server-side. Silently does nothing when the tab is empty/new or no
     * cell matches a known field - never unchecks anything already chosen by the admin.
     */
    function prefillFromExistingHeader() {
        const sheetTitle = $('#wizard-sheet-title').val();
        const headerRow = $('#wizard-header-row').val();
        const $hint = $('#wizard-header-hint');

        if (!sheetTitle || !spreadsheetId) {
            return;
        }

        $.get(App.Utils.Url.siteUrl('google_integrations/sheet_header'), {
            spreadsheet_id: spreadsheetId,
            sheet_title: sheetTitle,
            header_row: headerRow,
        })
            .done((response) => {
                if (!response.success || !response.headers || !response.headers.length) {
                    $hint.text('');
                    return;
                }

                const moduleKey = $('#wizard-module').val();
                const catalog = catalogs[moduleKey] || {};
                let matchedCount = 0;

                response.headers.forEach((headerText) => {
                    const normalized = String(headerText).trim().toLowerCase();

                    if (!normalized) {
                        return;
                    }

                    const matchingKey = Object.keys(catalog).find(
                        (key) => catalog[key].label.trim().toLowerCase() === normalized,
                    );

                    if (matchingKey) {
                        $('#wizard-field-' + matchingKey).prop('checked', true);
                        matchedCount++;
                    }
                });

                updatePiiWarning();

                $hint.text(
                    matchedCount
                        ? 'Mevcut başlık satırından ' + matchedCount + ' alan otomatik eşleştirildi.'
                        : 'Mevcut başlık satırı bulundu ama bilinen alanlarla eşleşmedi - alanları elle seçin.',
                );
            })
            .fail(() => $hint.text(''));
    }

    $(document).on('change', '#wizard-sheet-title, #wizard-header-row', prefillFromExistingHeader);

    $(document).on('click', '#new-sheet-sync-btn', function () {
        $('#wizard-name').val('');
        $('#wizard-header-row').val(1);
        $('#wizard-pii-mode').val('exclude');
        $('#wizard-write-mode').val('upsert');
        $('#wizard-header-hint').text('');
        renderFieldList($('#wizard-module').val());
        loadSheetTabs();
        updatePiiWarning();

        const modal = new bootstrap.Modal(document.getElementById('sheet-sync-wizard-modal'));
        modal.show();
    });

    $(document).on('change', '#wizard-module', function () {
        renderFieldList($(this).val());
        updatePiiWarning();
    });

    $(document).on('change', '#wizard-pii-mode', updatePiiWarning);
    $(document).on('change', '.wizard-field-checkbox', updatePiiWarning);

    $(document).on('click', '#wizard-select-all', function () {
        $('.wizard-field-checkbox').prop('checked', true);
        updatePiiWarning();
    });

    $(document).on('click', '#wizard-select-none', function () {
        $('.wizard-field-checkbox').prop('checked', false);
        updatePiiWarning();
    });

    $(document).on('click', '#wizard-save-btn', function () {
        const fieldKeys = $('.wizard-field-checkbox:checked')
            .map((index, checkbox) => $(checkbox).val())
            .get();

        if (!fieldKeys.length) {
            App.Layouts.Backend.displayNotification('En az bir alan seçmelisiniz.');
            return;
        }

        const name = $('#wizard-name').val().trim();

        if (!name) {
            App.Layouts.Backend.displayNotification('Bir ad girin.');
            return;
        }

        const $button = $(this);
        $button.prop('disabled', true);

        $.post(App.Utils.Url.siteUrl('google_integrations/create_sync'), {
            csrf_token: vars('csrf_token'),
            module: $('#wizard-module').val(),
            name,
            spreadsheet_id: spreadsheetId,
            sheet_title: $('#wizard-sheet-title').val(),
            header_row: $('#wizard-header-row').val(),
            pii_mode: $('#wizard-pii-mode').val(),
            write_mode: $('#wizard-write-mode').val(),
            field_keys: fieldKeys,
        })
            .done((response) => {
                if (!response.success) {
                    App.Layouts.Backend.displayNotification(response.message || 'İşlem başarısız.');
                    $button.prop('disabled', false);
                    return;
                }

                App.Layouts.Backend.displayNotification('Senkronizasyon oluşturuldu ve ' + response.backfill.synced + ' kayıt işlendi.');
                location.reload();
            })
            .fail((jqXHR) => {
                App.Layouts.Backend.displayNotification(jqXHR.responseJSON?.message || 'İşlem başarısız.');
                $button.prop('disabled', false);
            });
    });

    $(document).on('click', '.sync-now-btn', function () {
        const syncId = $(this).closest('tr').data('sync-id');
        const $button = $(this);
        $button.prop('disabled', true);

        $.post(App.Utils.Url.siteUrl('google_integrations/sync_now'), {
            csrf_token: vars('csrf_token'),
            id: syncId,
        })
            .done((response) => {
                if (!response.success) {
                    App.Layouts.Backend.displayNotification(response.message || 'İşlem başarısız.');
                    $button.prop('disabled', false);
                    return;
                }

                App.Layouts.Backend.displayNotification(response.synced + ' kayıt senkronize edildi.');
                location.reload();
            })
            .fail((jqXHR) => {
                App.Layouts.Backend.displayNotification(jqXHR.responseJSON?.message || 'İşlem başarısız.');
                $button.prop('disabled', false);
            });
    });

    $(document).on('click', '.sync-active-toggle', function () {
        const $badge = $(this);
        const syncId = $badge.closest('tr').data('sync-id');
        const newActive = $badge.data('active') ? 0 : 1;

        $.post(App.Utils.Url.siteUrl('google_integrations/update_sync'), {
            csrf_token: vars('csrf_token'),
            id: syncId,
            is_active: newActive,
        }).done((response) => {
            if (!response.success) {
                App.Layouts.Backend.displayNotification(response.message || 'İşlem başarısız.');
                return;
            }

            location.reload();
        });
    });

    $(document).on('click', '.delete-sync-btn', function () {
        const syncId = $(this).closest('tr').data('sync-id');

        App.Utils.Message.show(lang('delete'), 'Bu senkronizasyonu silmek istediğinize emin misiniz?', [
            {
                text: lang('cancel'),
                click: (event, messageModal) => messageModal.hide(),
            },
            {
                text: lang('delete'),
                click: (event, messageModal) => {
                    messageModal.hide();

                    $.post(App.Utils.Url.siteUrl('google_integrations/delete_sync'), {
                        csrf_token: vars('csrf_token'),
                        id: syncId,
                    }).done(() => {
                        App.Layouts.Backend.displayNotification('Senkronizasyon silindi.');
                        location.reload();
                    });
                },
            },
        ]);
    });
})();
