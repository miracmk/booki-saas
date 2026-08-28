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
 * Login page.
 *
 * This module implements the functionality of the login page.
 */
App.Pages.Login = (function () {
    const $loginForm = $('#login-form');
    const $username = $('#username');
    const $password = $('#password');
    const $captchaText = $('.captcha-text');
    const $captchaTitle = $('.captcha-title');
    const $captchaHint = $('#captcha-hint');
    const $altchaPayload = $('#altcha-payload');
    const $altchaHint = $('#altcha-hint');

    /**
     * Refresh the captcha image.
     */
    function refreshCaptcha() {
        $('.captcha-image').attr('src', App.Utils.Url.siteUrl('captcha?' + Date.now()));
    }

    /**
     * Login Button "Click"
     *
     * Make an ajax call to the server and check whether the user's credentials are right.
     *
     * If yes then redirect him to his desired page, otherwise display a message.
     */
    function onLoginFormSubmit(event) {
        event.preventDefault();

        const username = $username.val();
        const password = $password.val();

        if (!username || !password) {
            return;
        }

        if ($captchaText.length > 0) {
            $captchaText.removeClass('is-invalid');
            if ($captchaText.val() === '') {
                $captchaText.addClass('is-invalid');
                return;
            }
        }
        
        if ($altchaPayload.length > 0 && $altchaPayload.val() === '') {
            $altchaHint.text(lang('altcha_verification_failed')).fadeTo(400, 1);
            
            setTimeout(() => {
                $altchaHint.fadeTo(400, 0);
            }, 3000);
            
            return;
        }

        const captcha = $captchaText.length > 0 ? $captchaText.val() : null;
        const altchaPayloadValue = $altchaPayload.length > 0 ? $altchaPayload.val() : null;

        const $alert = $('.alert');

        $alert.addClass('d-none');

        App.Http.Login.validate(username, password, captcha, altchaPayloadValue).done((response) => {
            if (response.captcha_verification === false) {
                $captchaHint.text(lang('captcha_is_wrong')).fadeTo(400, 1);

                setTimeout(() => {
                    $captchaHint.fadeTo(400, 0);
                }, 3000);

                refreshCaptcha();

                $captchaText.addClass('is-invalid');

                return;
            }
            
            if (response.altcha_verification === false) {
                $altchaHint.text(lang('altcha_verification_failed')).fadeTo(400, 1);

                setTimeout(() => {
                    $altchaHint.fadeTo(400, 0);
                }, 3000);

                // Reset ALTCHA widget
                if (App.Utils.Altcha) {
                    App.Utils.Altcha.reset('altcha-widget');
                }

                return;
            }

            // Check if TOTP is required BEFORE checking success (since requires_totp response has success:true)
            if (response.requires_totp) {
                // Show TOTP verification form
                $('#login-form').addClass('d-none');
                $('#totp-form').removeClass('d-none');
                $('#pending-token').val(response.pending_token);
                $('#totp-code').focus();
                return;
            }

            if (response.success) {
                window.location.href = response.redirect_url || vars('dest_url');
            } else {
                $alert.text(lang('login_failed'));
                $alert.removeClass('d-none alert-danger alert-success').addClass('alert-danger');
                refreshCaptcha();
            }
        });
    }
    
    /**
     * TOTP Form Submit Handler
     */
    function onTotpFormSubmit(event) {
        event.preventDefault();

        const pendingToken = $('#pending-token').val();
        const totpCode = $('#totp-code').val();

        if (!pendingToken || !totpCode) {
            return;
        }

        const $alert = $('.alert');
        $alert.addClass('d-none');

        App.Http.Login.verifyTotp(pendingToken, totpCode).done((response) => {
            if (response.success) {
                window.location.href = response.redirect_url || vars('dest_url');
            } else {
                $alert.text(response.error || lang('invalid_totp_code'));
                $alert.removeClass('d-none alert-danger alert-success').addClass('alert-danger');
                $('#totp-code').val('');
                $('#totp-code').focus();
            }
        });
    }

    /**
     * Go back to login form from TOTP form
     */
    function onTotpBackClick() {
        $('#totp-form').addClass('d-none');
        $('#login-form').removeClass('d-none');
        $('#username').focus();
    }

    /**
     * Initialize ALTCHA widget if present.
     */
    function initializeAltcha() {
        if ($('#altcha-widget').length && App.Utils.Altcha) {
            App.Utils.Altcha.initialize('altcha-widget');
        }
    }

    $loginForm.on('submit', onLoginFormSubmit);
    $('#totp-form').on('submit', onTotpFormSubmit);
    $('#totp-back').on('click', onTotpBackClick);

    $captchaTitle.on('click', 'button', refreshCaptcha);

    // Initialize ALTCHA
    initializeAltcha();

    return {};
})();
