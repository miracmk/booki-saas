/* ----------------------------------------------------------------------------
 * BooKi - AI Asistan page (Dalga 4, 2026-09-12).
 * ---------------------------------------------------------------------------- */

App.Pages.AiAgent = (function () {
    const ROUTES = vars('routes');

    /**
     * fetch() helper with the CSRF token + correct Content-Type. NOTE: whatsapp.js
     * originally shipped this same helper WITHOUT the Content-Type header, which
     * made every POST on that page fail CSRF validation (fetch defaults a plain-
     * string body to text/plain, so PHP never populates $_POST). Fixed here from
     * the start - see that page's fix commit for the full root-cause writeup.
     */
    function post(action, data) {
        const dfd = $.Deferred();
        const params = new URLSearchParams(Object.assign({ csrf_token: vars('csrf_token') }, data || {}));
        fetch(action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params.toString(),
        })
            .then((response) => response.json())
            .then((json) => dfd.resolve(json))
            .catch((error) => dfd.reject(error));
        return dfd.promise();
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function appendMessage(role, text) {
        const $log = $('#ai-agent-log');
        $log.find('.text-muted').remove();
        $log.append(
            '<div class="ai-agent-msg ai-agent-msg-' + role + ' mb-2">' +
                '<strong>' + (role === 'user' ? 'Siz' : 'Asistan') + ':</strong> ' +
                '<span>' + escapeHtml(text).replace(/\n/g, '<br>') + '</span>' +
            '</div>',
        );
        $log.scrollTop($log.prop('scrollHeight'));
    }

    function onSubmit(event) {
        event.preventDefault();

        const $input = $('#ai-agent-input');
        const message = $input.val().trim();

        if (!message) {
            return;
        }

        const $button = $('#ai-agent-form button[type="submit"]');
        $button.prop('disabled', true);
        $input.prop('disabled', true);

        appendMessage('user', message);
        $input.val('');

        post(ROUTES.chat, { message: message })
            .done((response) => {
                if (response.success) {
                    appendMessage('assistant', response.reply);
                } else {
                    appendMessage('assistant', response.message || 'Bir hata oluştu.');
                }
                refreshPending();
            })
            .fail(() => {
                appendMessage('assistant', 'İstek başarısız oldu, lütfen tekrar deneyin.');
            })
            .always(() => {
                $button.prop('disabled', false);
                $input.prop('disabled', false).trigger('focus');
            });
    }

    function onReset() {
        post(ROUTES.reset, {}).done(() => {
            $('#ai-agent-log').html(
                '<span class="text-muted">Henüz bir mesaj yok.</span>',
            );
        });
    }

    function renderPending(items) {
        const $list = $('#ai-agent-pending-list');

        if (!items.length) {
            $list.html('<span class="text-muted">Bekleyen değişiklik yok.</span>');
            return;
        }

        $list.empty();

        items.forEach((change) => {
            let changes = {};
            try {
                changes = JSON.parse(change.changes || '{}');
            } catch (e) {
                changes = {};
            }

            const rows = Object.keys(changes)
                .map((field) => '<li><strong>' + escapeHtml(field) + ':</strong> ' + escapeHtml(changes[field]) + '</li>')
                .join('');

            const reason = change.reason
                ? '<div class="small text-muted fst-italic mb-2">"' + escapeHtml(change.reason) + '"</div>'
                : '';

            $list.append(
                '<div class="ai-agent-pending-item border rounded p-2 mb-2" data-id="' + change.id + '">' +
                    '<div class="small text-muted mb-1">Müşteri #' + escapeHtml(change.target_id) + '</div>' +
                    '<ul class="mb-1 ps-3">' + rows + '</ul>' +
                    reason +
                    '<div class="d-flex gap-2">' +
                        '<button type="button" class="btn btn-sm btn-success ai-agent-approve">Onayla</button>' +
                        '<button type="button" class="btn btn-sm btn-outline-danger ai-agent-reject">Reddet</button>' +
                    '</div>' +
                '</div>',
            );
        });
    }

    function refreshPending() {
        fetch(ROUTES.pending, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => r.json())
            .then((json) => {
                if (json.success) {
                    renderPending(json.pending);
                }
            })
            .catch(() => {});
    }

    function onResolveClick(action) {
        return function () {
            const $item = $(this).closest('.ai-agent-pending-item');
            const id = $item.data('id');

            post(action, { id: id }).done((response) => {
                if (response.success) {
                    $item.remove();
                    if (!$('#ai-agent-pending-list .ai-agent-pending-item').length) {
                        $('#ai-agent-pending-list').html('<span class="text-muted">Bekleyen değişiklik yok.</span>');
                    }
                } else {
                    App.Layouts.Backend.displayNotification(response.message || 'İşlem başarısız.');
                }
            });
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        $('#ai-agent-form').on('submit', onSubmit);
        $('#ai-agent-reset').on('click', onReset);
        $(document).on('click', '.ai-agent-approve', onResolveClick(ROUTES.approve));
        $(document).on('click', '.ai-agent-reject', onResolveClick(ROUTES.reject));

        const $log = $('#ai-agent-log');
        $log.scrollTop($log.prop('scrollHeight'));
    });

    return {};
})();
