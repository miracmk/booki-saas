/* ----------------------------------------------------------------------------
 * Ki Reservation - Online Appointment Scheduler
 *
 * WhatsApp settings page (Dalga 3 / Faz 3.5 dual-mode panel).
 * ---------------------------------------------------------------------------- */

App.Pages.Whatsapp = (function () {
    const $officialPanel = $('#wa-official-panel');
    const $unofficialPanel = $('#wa-unofficial-panel');
    const $consentWrap = $('#wa-consent-wrap');
    const $informalConsent = $('#wa-informal-consent');
    const $unofficialBadge = $('#wa-unofficial-badge');

    const ROUTES = vars('routes');

    /**
     * Perform an action request and resolve with the parsed JSON.
     * Appends the CSRF token exactly like the other admin pages.
     * Returns a jQuery Deferred (so callers use .done/.fail like the other
     * App.Http clients) while actually riding on fetch().
     */
    function post(action, data) {
        const dfd = $.Deferred();
        const params = new URLSearchParams(Object.assign({ csrf_token: vars('csrf_token') }, data || {}));
        fetch(action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: params.toString(),
        })
            .then((response) => response.json())
            .then((json) => dfd.resolve(json))
            .catch((error) => dfd.reject(error));
        return dfd.promise();
    }

    function get(action) {
        const dfd = $.Deferred();
        fetch(action, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((response) => response.json())
            .then((json) => dfd.resolve(json))
            .catch((error) => dfd.reject(error));
        return dfd.promise();
    }

    function isBusy($button) {
        return $button.data('wa-busy') === true;
    }

    function setBusy($button, busy) {
        if (busy) {
            $button.data('wa-busy', true).prop('disabled', true);
            const label = $button.data('busy-label');
            if (label) {
                $button.data('wa-original-label', $button.html());
                $button.html(label);
            }
        } else {
            $button.data('wa-busy', false).prop('disabled', false);
            const original = $button.data('wa-original-label');
            if (original) {
                $button.html(original);
            }
        }
    }

    function setBadge(status) {
        const label = String(status || 'disconnected');
        $unofficialBadge
            .removeClass('bg-success bg-danger bg-secondary')
            .addClass(label === 'connected' ? 'bg-success' : (label === 'error' ? 'bg-danger' : 'bg-secondary'))
            .text(label.charAt(0).toUpperCase() + label.slice(1));
    }

    function updateModeUi(mode) {
        const unofficial = String(mode) === 'unofficial';
        $officialPanel.toggleClass('d-none', unofficial);
        $unofficialPanel.toggleClass('d-none', !unofficial);
        $consentWrap.toggleClass('d-none', !unofficial);

        if (unofficial) {
            $consentWrap.removeClass('alert-danger').addClass('alert-danger');
        }
    }

    // ---------------- Copy buttons (delegated, incl. the old inline one) ------------

    $(document).on('click', '.wa-copy', function () {
        const $input = $(this).prev();
        $input.trigger('focus').select();
        document.execCommand('copy');
        const original = $(this).text();
        $(this).text('Kopyalandı!');
        setTimeout(() => $(this).text(original), 2000);
    });

    // ---------------- Mode selector ----------------

    $('.wa-mode-radio').on('change', function () {
        updateModeUi(this.value);
    });

    function onSaveModeClick() {
        const $button = $('#wa-save-mode');
        if (isBusy($button)) {
            return;
        }

        const mode = $('.wa-mode-radio:checked').val();

        if (mode === 'unofficial' && !$informalConsent.prop('checked')) {
            App.Layouts.Backend.displayNotification('Resmi olmayan mod için bilgilendirilmiş onay gerekli.');
            return;
        }

        setBusy($button, true);
        post(ROUTES.save_mode, { mode: mode, consent: $informalConsent.prop('checked') ? 1 : 0 })
            .done((response) => {
                setBusy($button, false);
                if (response.success) {
                    App.Layouts.Backend.displayNotification('Mod kaydedildi.');
                    updateModeUi(mode);
                } else {
                    App.Layouts.Backend.displayNotification(response.message || 'Mod kaydedilemedi.');
                }
            })
            .fail(() => {
                setBusy($button, false);
                App.Layouts.Backend.displayNotification('İstek başarısız oldu.');
            });
    }

    // ---------------- Official mode: check connection + test send ----------------

    function renderAlert($container, type, html) {
        $container.html('<div class="alert alert-' + type + ' mb-0">' + html + '</div>');
    }

    function escapeHtml(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function onOfficialCheckClick() {
        const $button = $('#wa-official-check');
        if (isBusy($button)) {
            return;
        }

        setBusy($button, true);
        get(ROUTES.check_connection)
            .done((response) => {
                setBusy($button, false);
                const $result = $('#wa-official-check-result');

                if (response.success && response.account) {
                    const account = response.account;
                    const fields = [
                        ['İş telefonu', account.display_phone_number],
                        ['Onaylı ad', account.verified_name],
                        ['Kalite puanı', account.quality_rating],
                        ['Doğrulama', account.code_verification_status],
                        ['Telefon ID', account.id],
                    ].filter((row) => row[1] != null && row[1] !== '');
                    renderAlert($result, 'success',
                        '<strong>Bağlantı doğrulandı.</strong><ul class="mb-0 mt-1">' +
                        fields.map((row) => '<li><strong>' + escapeHtml(row[0]) + ':</strong> ' + escapeHtml(row[1]) + '</li>').join('') +
                        '</ul>');
                } else {
                    renderAlert($result, 'danger',
                        '<strong>Bağlantı doğrulanamadı.</strong> ' + escapeHtml(response.message || 'Meta hesabına ulaşılamadı.'));
                }
            })
            .fail(() => {
                setBusy($button, false);
                renderAlert($('#wa-official-check-result'), 'danger', '<strong>İstek başarısız oldu.</strong>');
            });
    }

    function onSendTestClick() {
        const $button = $('#wa-send-test');
        if (isBusy($button)) {
            return;
        }

        const toPhone = $('#wa-test-phone').val().trim();
        if (!toPhone) {
            App.Layouts.Backend.displayNotification('Telefon numarası girin.');
            return;
        }

        setBusy($button, true);
        post(ROUTES.send_test, { to_phone: toPhone })
            .done((response) => {
                setBusy($button, false);
                renderAlert($('#wa-test-result'), response.success ? 'success' : 'danger',
                    response.success
                        ? '<strong>Test mesajı gönderildi.</strong>'
                        : '<strong>Gönderilemedi:</strong> ' + escapeHtml(response.message || 'bilinmeyen hata'));
            })
            .fail(() => {
                setBusy($button, false);
                renderAlert($('#wa-test-result'), 'danger', '<strong>İstek başarısız oldu.</strong>');
            });
    }

    // ---------------- Unofficial mode: bridge config + QR pairing ----------------

    function onSaveBridgeClick() {
        const $button = $('#wa-save-bridge');
        if (isBusy($button)) {
            return;
        }

        const url = $('#wa-bridge-url').val().trim();
        const secret = $('#wa-bridge-secret').val().trim();

        if (!url && !secret) {
            App.Layouts.Backend.displayNotification('Adres veya anahtar girin.');
            return;
        }

        setBusy($button, true);
        post(ROUTES.save_bridge, { bridge_url: url, bridge_secret: secret })
            .done((response) => {
                setBusy($button, false);
                if (response.success) {
                    App.Layouts.Backend.displayNotification('Köprü ayarı kaydedildi.');
                    $('#wa-bridge-secret').val('');
                } else {
                    App.Layouts.Backend.displayNotification(response.message || 'Kaydedilemedi.');
                }
            })
            .fail(() => {
                setBusy($button, false);
                App.Layouts.Backend.displayNotification('İstek başarısız oldu.');
            });
    }

    let qrPollTimer = null;

    function renderQr($container, response) {
        if (response.qr == null || response.qr === '') {
            $container.html('<span class="text-muted">QR kodu henüz hazır değil.</span>');
            return;
        }

        const qr = String(response.qr);
        const $box = $('<div/>');

        if (qr.indexOf('data:image') === 0) {
            $box.html('<img src="' + qr + '" alt="WhatsApp QR" class="img-fluid" style="max-width:280px">');
        } else if (qr.indexOf('{') !== 0 && !qr.startsWith('http')) {
            $box.html('<img src="data:image/png;base64,' + qr + '" alt="WhatsApp QR" class="img-fluid" style="max-width:280px">');
        } else {
            $box.html('<span class="text-muted">QR çıktısı desteklenmiyor.</span>');
        }

        $container.html($box);
    }

    function pollQrStatus(attempts) {
        if (attempts <= 0) {
            setBadge('connecting');
            $('#wa-qr-result').html('<span class="text-danger">QR süresi doldu; tekrar başlatın.</span>');
            qrPollTimer = null;
            return;
        }

        get(ROUTES.qr_status).done((response) => {
            if (response.success) {
                setBadge(response.status);

                if (response.status === 'connecting') {
                    renderQr($('#wa-qr-result'), response);
                    qrPollTimer = setTimeout(() => pollQrStatus(attempts - 1), 4000);
                    return;
                }

                if (response.status === 'connected') {
                    $('#wa-qr-result').html(
                        '<div class="alert alert-success mb-0">' +
                        '<strong>Bağlantı kuruldu.</strong> Resmi olmayan WhatsApp gönderimi hazır.</div>');
                } else if (response.status === 'error') {
                    $('#wa-qr-result').html('<div class="alert alert-danger mb-0"><strong>Bağlantı hatası.</strong></div>');
                } else {
                    $('#wa-qr-result').html('<span class="text-muted">Bağlantı kurulmadı.</span>');
                }
            }
        }).fail(() => {
            setBadge('disconnected');
            $('#wa-qr-result').html('<span class="text-danger">Köprüye ulaşılamadı.</span>');
        }).always(() => {
            qrPollTimer = null;
        });
    }

    function onQrStartClick() {
        const $button = $('#wa-qr-start');
        if (isBusy($button)) {
            return;
        }

        if (!vars('bridge_secret_set') && !$('#wa-bridge-secret').val().trim()) {
            App.Layouts.Backend.displayNotification('Önce köprü ayarını kaydedin.');
            return;
        }

        if (qrPollTimer) {
            clearTimeout(qrPollTimer);
            qrPollTimer = null;
        }

        setBadge('connecting');
        $('#wa-qr-result').html('<span class="text-muted">Eşleştirme başlatılıyor...</span>');
        setBusy($button, true);

        post(ROUTES.qr_start, {})
            .done((response) => {
                setBusy($button, false);
                if (response.success) {
                    App.Layouts.Backend.displayNotification('Eşleştirme başlatıldı.');
                    pollQrStatus(15);
                } else {
                    setBadge('error');
                    $('#wa-qr-result').html('<div class="alert alert-danger mb-0">' +
                        escapeHtml(response.message || 'Eşleştirme başlatılamadı.') + '</div>');
                }
            })
            .fail(() => {
                setBusy($button, false);
                setBadge('error');
                $('#wa-qr-result').html('<div class="alert alert-danger mb-0">Köprüye ulaşılamadı.</div>');
            });
    }

    function onQrStatusClick() {
        const $button = $('#wa-qr-status');
        if (isBusy($button)) {
            return;
        }

        if (qrPollTimer) {
            clearTimeout(qrPollTimer);
            qrPollTimer = null;
        }

        setBusy($button, true);
        get(ROUTES.qr_status)
            .done((response) => {
                setBusy($button, false);
                if (!response.success) {
                    setBadge('disconnected');
                    $('#wa-qr-result').html('<div class="alert alert-danger mb-0">' +
                        escapeHtml(response.message || 'Köprüye ulaşılamadı.') + '</div>');
                    return;
                }

                setBadge(response.status);

                if (response.status === 'connected') {
                    $('#wa-qr-result').html(
                        '<div class="alert alert-success mb-0"><strong>Bağlantı kuruldu.</strong></div>');
                } else if (response.status === 'error') {
                    $('#wa-qr-result').html('<div class="alert alert-danger mb-0"><strong>Bağlantı hatası.</strong></div>');
                } else if (response.status === 'connecting') {
                    renderQr($('#wa-qr-result'), response);
                } else {
                    $('#wa-qr-result').html('<span class="text-muted">Bağlantı kurulmadı.</span>');
                }
            })
            .fail(() => {
                setBusy($button, false);
                setBadge('disconnected');
                $('#wa-qr-result').html('<span class="text-danger">Köprüye ulaşılamadı.</span>');
            });
    }

    function onQrLogoutClick() {
        const $button = $('#wa-qr-logout');
        if (isBusy($button)) {
            return;
        }

        if (qrPollTimer) {
            clearTimeout(qrPollTimer);
            qrPollTimer = null;
        }

        setBusy($button, true);
        post(ROUTES.qr_logout, {})
            .done((response) => {
                setBusy($button, false);
                if (response.success) {
                    setBadge('disconnected');
                    $('#wa-qr-result').html('<span class="text-muted">Bağlantı kapatıldı.</span>');
                }
            })
            .fail(() => {
                setBusy($button, false);
                App.Layouts.Backend.displayNotification('İstek başarısız oldu.');
            });
    }

    // ---------------- Initialize ----------------

    function initialize() {
        // Wire events (delegated so radios keep working after re-render).
        $('#wa-save-mode').on('click', onSaveModeClick);
        $('#wa-official-check').on('click', onOfficialCheckClick);
        $('#wa-send-test').on('click', onSendTestClick);
        $('#wa-save-bridge').on('click', onSaveBridgeClick);
        $('#wa-qr-start').on('click', onQrStartClick);
        $('#wa-qr-status').on('click', onQrStatusClick);
        $('#wa-qr-logout').on('click', onQrLogoutClick);

        updateModeUi(vars('mode'));
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();