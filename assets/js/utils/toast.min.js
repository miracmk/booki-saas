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
 * Toast notification utility for non-blocking feedback.
 * Replaces intrusive modal popups for routine success/info/warning messages.
 */
window.App = window.App || {};
window.App.Utils = window.App.Utils || {};

window.App.Utils.Toast = (function () {
    let $container = null;

    function getContainer() {
        if (!$container || !$('#ki-toast-container').length) {
            $container = $('<div id="ki-toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 99999; pointer-events: none;"></div>').appendTo('body');
        }
        return $container;
    }

    /**
     * Show a toast message.
     *
     * @param {string} message The text or HTML message.
     * @param {'success'|'error'|'warning'|'info'} [type='success'] Notification type.
     * @param {number} [duration=3500] Auto-dismiss duration in ms (0 for persistent).
     */
    function show(message, type = 'success', duration = 3500) {
        if (!message) return;

        const iconMap = {
            success: 'fa-check-circle text-success',
            error: 'fa-exclamation-circle text-danger',
            warning: 'fa-exclamation-triangle text-warning',
            info: 'fa-info-circle text-primary',
        };

        const borderMap = {
            success: 'border-success',
            error: 'border-danger',
            warning: 'border-warning',
            info: 'border-primary',
        };

        const icon = iconMap[type] || iconMap.info;
        const border = borderMap[type] || borderMap.info;

        const $toast = $(`
            <div class="toast align-items-center bg-white text-dark border-0 border-start border-4 ${border} shadow-lg mb-2" role="alert" aria-live="assertive" aria-atomic="true" style="pointer-events: auto; min-width: 300px; border-radius: 10px;">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-2 py-3 px-3">
                        <i class="fas ${icon} fs-5 flex-shrink-0"></i>
                        <div class="flex-grow-1 small fw-semibold" style="line-height: 1.4;">${message}</div>
                    </div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Kapat"></button>
                </div>
            </div>
        `);

        getContainer().append($toast);

        if (window.bootstrap && bootstrap.Toast) {
            const bsToast = new bootstrap.Toast($toast[0], {
                delay: duration,
                autohide: duration > 0,
            });
            bsToast.show();
            $toast.on('hidden.bs.toast', function () {
                $toast.remove();
            });
        } else {
            // Fallback if bootstrap Toast JS isn't loaded
            $toast.fadeIn(200);
            if (duration > 0) {
                setTimeout(() => {
                    $toast.fadeOut(300, () => $toast.remove());
                }, duration);
            }
            $toast.find('.btn-close').on('click', () => $toast.remove());
        }
    }

    return {
        show: show,
        success: (msg, dur) => show(msg, 'success', dur),
        error: (msg, dur) => show(msg, 'error', dur),
        warning: (msg, dur) => show(msg, 'warning', dur),
        info: (msg, dur) => show(msg, 'info', dur),
    };
})();

