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
 * Salon Flora customization - Telegram integration settings page (2026-08-25).
 */
(function () {
    $('#save-telegram-settings').on('click', function () {
        const url = App.Utils.Url.siteUrl('telegram/save_settings');

        $.post(url, {
            csrf_token: vars('csrf_token'),
            bot_token: $('#bot-token').val(),
            notifications_enabled: $('#notifications-enabled').is(':checked') ? 1 : 0,
        })
            .done((response) => {
                App.Layouts.Backend.displayNotification('Telegram ayarları kaydedildi.');
                if (response.bot_username) {
                    location.reload();
                }
            })
            .fail((jqXHR) => {
                App.Layouts.Backend.displayNotification(jqXHR.responseJSON?.message || 'Kaydedilemedi.');
            });
    });

    $('#setup-webhook').on('click', function () {
        const url = App.Utils.Url.siteUrl('telegram/setup_webhook');

        $.post(url, {csrf_token: vars('csrf_token')})
            .done(() => {
                App.Layouts.Backend.displayNotification('Webhook kuruldu.');
                location.reload();
            })
            .fail((jqXHR) => {
                App.Layouts.Backend.displayNotification(jqXHR.responseJSON?.message || 'Webhook kurulamadı.');
            });
    });

    $(document).on('click', '.generate-link-btn', function () {
        const userId = $(this).data('user-id');
        const url = App.Utils.Url.siteUrl('telegram/generate_link');

        $.post(url, {csrf_token: vars('csrf_token'), user_id: userId})
            .done((response) => {
                App.Utils.Message.show(
                    'Bağlantı Linki',
                    '<p>Bu linki personele iletin, bota "Başlat"a bastıklarında Telegram hesapları bağlanır:</p>' +
                        '<input type="text" class="form-control" readonly value="' +
                        response.link +
                        '" onclick="this.select()">',
                );
            })
            .fail((jqXHR) => {
                App.Layouts.Backend.displayNotification(jqXHR.responseJSON?.message || 'Link oluşturulamadı.');
            });
    });

    $(document).on('click', '.reply-btn', function () {
        const $button = $(this);
        const $input = $button.closest('.input-group').find('.reply-input');
        const message = $input.val().trim();

        if (!message) {
            return;
        }

        const url = App.Utils.Url.siteUrl('telegram/reply');

        $.post(url, {
            csrf_token: vars('csrf_token'),
            chat_id: $button.data('chat-id'),
            user_id: $button.data('user-id') || null,
            message,
        })
            .done(() => {
                App.Layouts.Backend.displayNotification('Mesaj gönderildi.');
                $input.val('');
            })
            .fail((jqXHR) => {
                App.Layouts.Backend.displayNotification(jqXHR.responseJSON?.message || 'Gönderilemedi.');
            });
    });
})();
