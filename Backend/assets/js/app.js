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
 * App global namespace object.
 *
 * This script should be loaded before the other modules in order to define the global application namespace.
 */
window.App = (function () {
    function onAjaxError(event, jqXHR, textStatus, errorThrown) {
        console.error('Unexpected HTTP Error: ', jqXHR, textStatus, errorThrown);

        let response;

        try {
            response = JSON.parse(jqXHR.responseText); // JSON response
        } catch (error) {
            response = {message: jqXHR.responseText}; // String response
        }

        if (!response || !response.message) {
            return;
        }

        if (App.Utils.Message) {
            App.Utils.Message.show('BooKi', lang('unexpected_issues_message'));

            $('<div/>', {
                'class': 'card',
                'html': [
                    $('<div/>', {
                        'class': 'card-body overflow-auto',
                        'html': response.message,
                    }),
                ],
            }).appendTo('#message-modal .modal-body');
        }
    }

    $(document).ajaxError(onAjaxError);

    $.ajaxSetup({
        beforeSend: function (xhr, settings) {
            if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !settings.crossDomain) {
                const token = (typeof window.vars === 'function' ? window.vars('csrf_token') : null)
                    || (window.App && window.App.Security ? window.App.Security.csrfToken : '');
                if (token) {
                    xhr.setRequestHeader('X-CSRF-Token', token);
                    xhr.setRequestHeader('X-CSRF', token);
                }
            }
        }
    });

    $(function () {
        if (window.moment) {
            window.moment.locale(vars('language_code'));
        }
    });

    const Utils = {
        ajaxUrl: function (uri) {
            if (window.App && window.App.Utils && window.App.Utils.Url && typeof window.App.Utils.Url.siteUrl === 'function') {
                return window.App.Utils.Url.siteUrl(uri);
            }
            const baseUrl = typeof window.vars === 'function' ? window.vars('base_url') : '';
            const indexPage = typeof window.vars === 'function' ? window.vars('index_page') : '';
            return `${baseUrl}${indexPage ? '/' + indexPage : ''}/${uri}`;
        },
        message: function (message, type = 'info') {
            if (window.App && window.App.Layouts && window.App.Layouts.Backend && typeof window.App.Layouts.Backend.displayNotification === 'function') {
                window.App.Layouts.Backend.displayNotification(message);
            } else if (window.App && window.App.Utils && window.App.Utils.Message && typeof window.App.Utils.Message.show === 'function') {
                window.App.Utils.Message.show('BooKi', message);
            } else {
                console.log(`[${type}] ${message}`);
            }
        },
        ajaxErrorMsg: function (jqXHR) {
            let msg = 'Bir hata oluştu.';
            try {
                const res = typeof jqXHR === 'string' ? JSON.parse(jqXHR) : (jqXHR && jqXHR.responseJSON ? jqXHR.responseJSON : JSON.parse((jqXHR && jqXHR.responseText) || '{}'));
                if (res.message) msg = res.message;
                else if (res.error) msg = res.error;
            } catch (e) {
                if (jqXHR && jqXHR.statusText) msg = jqXHR.statusText;
            }
            if (window.App && window.App.Utils && typeof window.App.Utils.message === 'function') {
                window.App.Utils.message(msg, 'error');
            }
        },
    };

    const Security = {
        get csrfToken() {
            return typeof window.vars === 'function' ? (window.vars('csrf_token') || '') : '';
        },
    };

    const Lang = new Proxy({}, {
        get: function (target, prop) {
            if (typeof prop === 'string') {
                return typeof window.lang === 'function' ? window.lang(prop) : prop;
            }
            return target[prop];
        },
    });

    return {
        Components: {},
        Http: {},
        Layouts: {},
        Pages: {},
        Utils,
        Security,
        Lang,
    };
})();

