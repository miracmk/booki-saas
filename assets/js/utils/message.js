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
 * Messages utility.
 *
 * This module implements the functionality of messages.
 */
window.App.Utils.Message = (function () {
    let messageModal = null;

    /**
     * Show a message box to the user.
     *
     * This functions displays a message box in the admin array. It is useful when user
     * decisions or verifications are needed.
     *
     * @param {String} title The title of the message box.
     * @param {String} message The message of the dialog.
     * @param {Array} [buttons] Contains the dialog buttons along with their functions.
     * @param {Boolean} [isDismissible] If true, the button will show the close X in the header and close with the press of the Escape button.
     *
     * @return {jQuery|null} Return the #message-modal selector or null if the arguments are invalid.
     */
    function show(title, message, buttons = null, isDismissible = true) {
        if (!title || !message) {
            return null;
        }

        if (!buttons) {
            buttons = [
                {
                    text: lang('close'),
                    className: 'btn btn-outline-primary',
                    click: function (event, messageModal) {
                        messageModal.hide();
                    },
                },
            ];
        }

        if (messageModal?.dispose && messageModal?.hide && messageModal?._element) {
            messageModal.hide();
            messageModal.dispose();
            messageModal = undefined;
        }

        $('#message-modal').remove();

        const $messageModal = $(`
            <div class="modal" id="message-modal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                ${title}
                            </h5>
                            ${
                                isDismissible
                                    ? '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>'
                                    : ''
                            }
                        </div>
                        <div class="modal-body">
                            ${message}
                        </div>
                        <div class="modal-footer">
                            <!-- * -->
                        </div>
                    </div>
                </div>
            </div>
        `).appendTo('body');

        buttons.forEach((button) => {
            if (!button) {
                return;
            }

            if (!button.className) {
                button.className = 'btn btn-outline-primary';
            }

            const $button = $(`
                <button type="button" class="${button.className}">
                    ${button.text}
                </button>
            `).appendTo($messageModal.find('.modal-footer'));

            if (button.click) {
                $button.on('click', (event) => button.click(event, messageModal));
            }
        });

        messageModal = new bootstrap.Modal('#message-modal', {
            keyboard: isDismissible,
            backdrop: 'static',
        });

        $messageModal.on('shown.bs.modal', () => {
            $messageModal
                .find('.modal-footer button:last')
                .removeClass('btn-outline-primary')
                .addClass('btn-primary')
                .focus();
        });

        messageModal.show();

        $messageModal.css('z-index', '99999').next().css('z-index', '9999');

        return $messageModal;
    }

    /**
     * Salon Flora customization - show a confirmation dialog with three independent notification
     * ticks (customer / provider / admin), instead of a plain yes/no choice. Lets staff decide exactly
     * who should receive the appointment notification email before the action proceeds.
     *
     * Dismissing the dialog (X, Escape, backdrop) does nothing further, same as ignoring the old
     * yes/no dialog did - the caller only runs once the "Kaydet" button is pressed.
     *
     * @param {String} title The title of the dialog.
     * @param {String} question The question/explanation shown above the ticks.
     * @param {Function} onConfirm Called with {customer, provider, admin} booleans once confirmed.
     */
    function confirmNotifyOptions(title, question, onConfirm) {
        show(title, `
            <p>${question}</p>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="notify-option-customer" checked>
                <label class="form-check-label" for="notify-option-customer">${lang('notify_customer')}</label>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="notify-option-provider" checked>
                <label class="form-check-label" for="notify-option-provider">${lang('notify_provider')}</label>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="notify-option-admin" checked>
                <label class="form-check-label" for="notify-option-admin">${lang('notify_admin')}</label>
            </div>
        `, [
            {
                text: lang('save'),
                click: (event, messageModal) => {
                    const notify = {
                        customer: $('#notify-option-customer').is(':checked'),
                        provider: $('#notify-option-provider').is(':checked'),
                        admin: $('#notify-option-admin').is(':checked'),
                    };
                    messageModal.hide();
                    onConfirm(notify);
                },
            },
        ]);
    }

    function toast(message, type = 'success', duration = 3500) {
        if (window.App && window.App.Utils && window.App.Utils.Toast) {
            window.App.Utils.Toast.show(message, type, duration);
        } else {
            show(type.toUpperCase(), message);
        }
    }

    return {
        show,
        confirmNotifyOptions,
        toast,
        success: (msg, dur) => toast(msg, 'success', dur),
        error: (msg, dur) => toast(msg, 'error', dur),
        info: (msg, dur) => toast(msg, 'info', dur),
    };
})();
