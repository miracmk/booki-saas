/**
 * Instagram integration and message handling JavaScript.
 */

'use strict';

App.Pages.Instagram = (function () {
    const $saveBtn = $('#save-instagram-settings');
    const $copyBtn = $('#copy-webhook-btn');
    const $replyModal = $('#ig-reply-modal');
    const $sendReplyBtn = $('#modal-send-reply-btn');

    function init() {
        addEventListeners();
    }

    function addEventListeners() {
        $copyBtn.on('click', onCopyWebhookClick);
        $saveBtn.on('click', onSaveSettingsClick);
        $('body').on('click', '.ig-reply-btn', onReplyClick);
        $sendReplyBtn.on('click', onSendReplyClick);
    }

    function onCopyWebhookClick() {
        const url = $('#ig-webhook-url').val();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(() => {
                App.Utils.message('Webhook adresi panoya kopyalandı.', 'success');
            });
        } else {
            $('#ig-webhook-url').select();
            document.execCommand('copy');
            App.Utils.message('Webhook adresi kopyalandı.', 'success');
        }
    }

    function onSaveSettingsClick() {
        $saveBtn.prop('disabled', true);

        const data = {
            instagram_account_id: $('#ig-account-id').val(),
            instagram_access_token: $('#ig-access-token').val(),
            instagram_webhook_verify_token: $('#ig-verify-token').val(),
            instagram_notifications_enabled: $('#ig-notifications-enabled').is(':checked'),
            ai_reply_instagram_enabled: $('#ai-reply-instagram-enabled').is(':checked'),
        };

        const routes = (window.scriptVars && window.scriptVars.routes) || {};
        const url = routes.save_settings || App.Utils.ajaxUrl('instagram/save_settings');

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: data,
            headers: { 'X-CSRF-Token': App.Security.csrfToken },
        })
            .done(function (response) {
                if (response.success) {
                    App.Utils.message('Instagram ayarları başarıyla kaydedildi.', 'success');
                } else {
                    App.Utils.message('Ayarlar kaydedilemedi.', 'error');
                }
            })
            .fail(function (jqxhr) {
                App.Utils.ajaxErrorMsg(jqxhr);
            })
            .always(function () {
                $saveBtn.prop('disabled', false);
            });
    }

    function onReplyClick(event) {
        const igUser = $(event.currentTarget).data('ig-user');
        const userId = $(event.currentTarget).data('user-id');

        $('#modal-ig-user-id').val(igUser);
        $('#modal-user-id').val(userId || '');
        $('#modal-reply-text').val('');

        $replyModal.modal('show');
    }

    function onSendReplyClick() {
        const igUser = $('#modal-ig-user-id').val();
        const userId = $('#modal-user-id').val();
        const message = $('#modal-reply-text').val().trim();

        if (!message) {
            App.Utils.message('Lütfen bir yanıt mesajı yazın.', 'error');
            return;
        }

        $sendReplyBtn.prop('disabled', true);

        const routes = (window.scriptVars && window.scriptVars.routes) || {};
        const url = routes.reply || App.Utils.ajaxUrl('instagram/reply');

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: {
                instagram_user_id: igUser,
                user_id: userId || null,
                message: message,
            },
            headers: { 'X-CSRF-Token': App.Security.csrfToken },
        })
            .done(function (response) {
                if (response.success) {
                    $replyModal.modal('hide');
                    App.Utils.message('Yanıt gönderildi.', 'success');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    App.Utils.message('Mesaj gönderilemedi.', 'error');
                }
            })
            .fail(function (jqxhr) {
                App.Utils.ajaxErrorMsg(jqxhr);
            })
            .always(function () {
                $sendReplyBtn.prop('disabled', false);
            });
    }

    document.addEventListener('DOMContentLoaded', init);

    return {};
})();
