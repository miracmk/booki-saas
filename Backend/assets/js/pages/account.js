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
 * Account page.
 *
 * This module implements the functionality of the account page.
 */
App.Pages.Account = (function () {
    const $userId = $('#user-id');
    const $firstName = $('#first-name');
    const $lastName = $('#last-name');
    const $email = $('#email');
    const $mobileNumber = $('#mobile-number');
    const $phoneNumber = $('#phone-number');
    const $address = $('#address');
    const $city = $('#city');
    const $state = $('#state');
    const $zipCode = $('#zip-code');
    const $notes = $('#notes');
    const $language = $('#language');
    const $timezone = $('#timezone');
    const $username = $('#username');
    const $password = $('#password');
    const $retypePassword = $('#retype-password');
    const $calendarView = $('#calendar-view');
    const notifications = $('#notifications');
    const $saveSettings = $('#save-settings');
    const $footerUserDisplayName = $('#footer-user-display-name');

    /**
     * Check if the form has invalid values.
     *
     * @return {Boolean}
     */
    function isInvalid() {
        try {
            $('#account .is-invalid').removeClass('is-invalid');

            // Validate required fields.

            let missingRequiredFields = false;

            $('#account .required').each((index, requiredField) => {
                const $requiredField = $(requiredField);

                if (!$requiredField.val()) {
                    $requiredField.addClass('is-invalid');
                    missingRequiredFields = true;
                }
            });

            if (missingRequiredFields) {
                throw new Error(lang('fields_are_required'));
            }

            // Validate passwords (if values provided).

            if ($password.val() && $password.val() !== $retypePassword.val()) {
                $password.addClass('is-invalid');
                $retypePassword.addClass('is-invalid');
                throw new Error(lang('passwords_mismatch'));
            }

            // Validate user email.

            const emailValue = $email.val();

            if (!App.Utils.Validation.email(emailValue)) {
                $email.addClass('is-invalid');
                throw new Error(lang('invalid_email'));
            }

            if ($username.hasClass('is-invalid')) {
                throw new Error(lang('username_already_exists'));
            }

            return false;
        } catch (error) {
            App.Layouts.Backend.displayNotification(error.message);
            return true;
        }
    }

    /**
     * Apply the account values to the form.
     *
     * @param {Object} account
     */
    function deserialize(account) {
        $userId.val(account.id);
        $firstName.val(account.first_name);
        $lastName.val(account.last_name);
        $email.val(account.email);
        $mobileNumber.val(account.mobile_number);
        $phoneNumber.val(account.phone_number);
        $address.val(account.address);
        $city.val(account.city);
        $state.val(account.state);
        $zipCode.val(account.zip_code);
        $notes.val(account.notes);
        $language.val(account.language);
        $timezone.val(account.timezone);
        $username.val(account.settings.username);
        $password.val('');
        $retypePassword.val('');
        $calendarView.val(account.settings.calendar_view);
        notifications.prop('checked', Boolean(Number(account.settings.notifications)));
    }

    /**
     * Get the account information from the form.
     *
     * @return {Object}
     */
    function serialize() {
        return {
            id: $userId.val(),
            first_name: $firstName.val(),
            last_name: $lastName.val(),
            email: $email.val(),
            mobile_number: $mobileNumber.val(),
            phone_number: $phoneNumber.val(),
            address: $address.val(),
            city: $city.val(),
            state: $state.val(),
            zip_code: $zipCode.val(),
            notes: $notes.val(),
            language: $language.val(),
            timezone: $timezone.val(),
            settings: {
                username: $username.val(),
                password: $password.val() || undefined,
                calendar_view: $calendarView.val(),
                notifications: Number(notifications.prop('checked')),
            },
        };
    }

    /**
     * Save the account information.
     */
    function onSaveSettingsClick() {
        if (isInvalid()) {
            App.Layouts.Backend.displayNotification(lang('user_settings_are_invalid'));

            return;
        }

        const account = serialize();

        App.Http.Account.save(account).done(() => {
            App.Layouts.Backend.displayNotification(lang('settings_saved'));

            $footerUserDisplayName.text('Hello, ' + $firstName.val() + ' ' + $lastName.val() + '!');
        });
    }

    /**
     * Make sure the username is unique.
     */
    function onUsernameChange() {
        const username = $username.val();

        App.Http.Account.validateUsername(vars('user_id'), username).done((response) => {
            const isValid = response.is_valid;
            $username.toggleClass('is-invalid', !isValid);
            if (!isValid) {
                App.Layouts.Backend.displayNotification(lang('username_already_exists'));
            }
        });
    }

    /**
     * Load TOTP status and update UI.
     */
    function loadTotpStatus() {
        const userId = vars('user_id');
        const user = vars('account');

        // Check if user has TOTP enabled from user_settings
        // For now, we'll load this from an API call or assume not enabled initially
        // This would typically come from the user_settings model

        // For this implementation, we'll add a way to check status via the API
        // For now, assume disabled unless we fetch it
        updateTotpUI(false);
    }

    /**
     * Update TOTP UI based on enabled state.
     */
    function updateTotpUI(isEnabled) {
        const $status = $('#totp-status');
        const $disabledContent = $('#totp-disabled-content');
        const $enabledContent = $('#totp-enabled-content');

        if (isEnabled) {
            $status.removeClass('bg-secondary').addClass('bg-success').text(lang('enabled'));
            $disabledContent.addClass('d-none');
            $enabledContent.removeClass('d-none');
        } else {
            $status.removeClass('bg-success').addClass('bg-secondary').text(lang('disabled'));
            $disabledContent.removeClass('d-none');
            $enabledContent.addClass('d-none');
        }
    }

    /**
     * Handle TOTP Setup button click.
     */
    function onTotpSetupClick() {
        const $modal = $('#totp-setup-modal');
        const modal = new bootstrap.Modal($modal[0]);

        // Generate a new TOTP secret
        App.Http.Account.totpSetup().done((response) => {
            $('#totp-secret-display').val(response.secret);

            // Display QR code
            const qrContainer = document.getElementById('totp-qr-code');
            qrContainer.innerHTML = '<img src="' + response.otpauth_uri + '" alt="QR Code" style="max-width: 200px;">';

            // Show the modal
            modal.show();
        }).fail(() => {
            App.Layouts.Backend.displayNotification(lang('failed_to_setup_totp'));
        });
    }

    /**
     * Handle copy secret button click.
     */
    function onCopySecretClick() {
        const $secret = $('#totp-secret-display');
        const secret = $secret.val();

        if (secret && navigator.clipboard) {
            navigator.clipboard.writeText(secret).then(() => {
                const $btn = $('#totp-copy-secret');
                const originalHtml = $btn.html();
                $btn.html('<i class="fas fa-check"></i>');

                setTimeout(() => {
                    $btn.html(originalHtml);
                }, 2000);
            });
        }
    }

    /**
     * Handle TOTP verification during setup.
     */
    function onTotpConfirmClick() {
        const code = $('#totp-verify-code').val();

        if (!code) {
            $('#totp-error-message').text(lang('code_required')).removeClass('d-none');
            return;
        }

        App.Http.Account.totpEnable(code).done((response) => {
            if (response.success) {
                // Show backup codes modal
                showBackupCodesModal(response.backup_codes);

                // Close setup modal
                bootstrap.Modal.getInstance(document.getElementById('totp-setup-modal')).hide();

                // Update UI
                updateTotpUI(true);

                // Show notification
                App.Layouts.Backend.displayNotification(lang('totp_enabled_successfully'));
            }
        }).fail((response) => {
            const error = response.responseJSON?.error || lang('invalid_totp_code');
            $('#totp-error-message').text(error).removeClass('d-none');
        });
    }

    /**
     * Show backup codes modal.
     */
    function showBackupCodesModal(backupCodes) {
        const $codesList = $('#backup-codes-list');
        $codesList.html('');

        backupCodes.forEach((code) => {
            const $codeDiv = $('<div class="mb-1"></div>').text(code);
            $codesList.append($codeDiv);
        });

        // Store codes for copy functionality
        $('#backup-codes-list').data('codes', backupCodes);

        const modal = new bootstrap.Modal(document.getElementById('backup-codes-modal'));
        modal.show();
    }

    /**
     * Copy backup codes to clipboard.
     */
    function onBackupCodesCopyClick() {
        const backupCodes = $('#backup-codes-list').data('codes');
        const codesText = backupCodes.join('\n');

        if (navigator.clipboard) {
            navigator.clipboard.writeText(codesText).then(() => {
                const $btn = $('#backup-codes-copy-btn');
                const originalHtml = $btn.html();
                $btn.html('<i class="fas fa-check me-1"></i>' + lang('copied'));

                setTimeout(() => {
                    $btn.html(originalHtml);
                }, 2000);
            });
        }
    }

    /**
     * Handle TOTP Disable button click.
     */
    function onTotpDisableClick() {
        $('#totp-disable-password').val('');
        $('#totp-disable-error').addClass('d-none');

        const modal = new bootstrap.Modal(document.getElementById('totp-disable-modal'));
        modal.show();
    }

    /**
     * Confirm TOTP disable.
     */
    function onTotpConfirmDisableClick() {
        const password = $('#totp-disable-password').val();

        if (!password) {
            $('#totp-disable-error').text(lang('password_required')).removeClass('d-none');
            return;
        }

        App.Http.Account.totpDisable(password).done((response) => {
            if (response.success) {
                bootstrap.Modal.getInstance(document.getElementById('totp-disable-modal')).hide();
                updateTotpUI(false);
                App.Layouts.Backend.displayNotification(lang('totp_disabled_successfully'));
            }
        }).fail((response) => {
            const error = response.responseJSON?.error || lang('failed_to_disable_totp');
            $('#totp-disable-error').text(error).removeClass('d-none');
        });
    }

    /**
     * Handle Regenerate Backup Codes button click.
     */
    function onTotpRegenerateClick() {
        $('#regenerate-totp-code').val('');
        $('#regenerate-error').addClass('d-none');

        const modal = new bootstrap.Modal(document.getElementById('regenerate-backup-codes-modal'));
        modal.show();
    }

    /**
     * Confirm regenerate backup codes.
     */
    function onRegenerateConfirmClick() {
        const code = $('#regenerate-totp-code').val();

        if (!code) {
            $('#regenerate-error').text(lang('code_required')).removeClass('d-none');
            return;
        }

        App.Http.Account.totpRegenerateBackupCodes(code).done((response) => {
            if (response.success) {
                bootstrap.Modal.getInstance(document.getElementById('regenerate-backup-codes-modal')).hide();
                showBackupCodesModal(response.backup_codes);
                App.Layouts.Backend.displayNotification(lang('backup_codes_regenerated'));
            }
        }).fail((response) => {
            const error = response.responseJSON?.error || lang('invalid_totp_code');
            $('#regenerate-error').text(error).removeClass('d-none');
        });
    }

    /**
     * Initialize the page.
     */
    function initialize() {
        const account = vars('account');

        deserialize(account);

        $saveSettings.on('click', onSaveSettingsClick);

        $username.on('change', onUsernameChange);

        // Initialize TOTP
        loadTotpStatus();

        // TOTP event listeners
        $('#totp-setup-btn').on('click', onTotpSetupClick);
        $('#totp-copy-secret').on('click', onCopySecretClick);
        $('#totp-confirm-btn').on('click', onTotpConfirmClick);
        $('#backup-codes-copy-btn').on('click', onBackupCodesCopyClick);
        $('#totp-disable-btn').on('click', onTotpDisableClick);
        $('#totp-confirm-disable-btn').on('click', onTotpConfirmDisableClick);
        $('#totp-regenerate-btn').on('click', onTotpRegenerateClick);
        $('#regenerate-confirm-btn').on('click', onRegenerateConfirmClick);
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
