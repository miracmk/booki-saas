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
 * Onboarding page.
 *
 * New tenant admin's first-login company profile wizard.
 */
App.Pages.Onboarding = (function () {
    const $form = $('#onboarding-form');
    const $submit = $('#onboarding-submit');
    const $alert = $('.alert');

    function onSubmit(event) {
        event.preventDefault();

        const closedDays = $('.closed-day:checked')
            .map(function () {
                return $(this).val();
            })
            .get();

        const data = {
            csrf_token: vars('csrf_token'),
            company_name: $('#company_name').val(),
            business_type: $('#business_type').val(),
            company_address: $('#company_address').val(),
            company_phone: $('#company_phone').val(),
            working_start: $('#working_start').val(),
            working_end: $('#working_end').val(),
            closed_days: closedDays,
            social_instagram: $('#social_instagram').val(),
            social_telegram: $('#social_telegram').val(),
            social_facebook: $('#social_facebook').val(),
            social_website: $('#social_website').val(),
        };

        $alert.addClass('d-none');
        $submit.prop('disabled', true);

        $.post(App.Utils.Url.siteUrl('onboarding/save'), data)
            .done((response) => {
                if (response.success) {
                    window.location.href = App.Utils.Url.siteUrl('calendar');
                } else {
                    $alert.text(response.message || 'Error').removeClass('d-none').addClass('alert-danger');
                    $submit.prop('disabled', false);
                }
            })
            .fail((xhr) => {
                const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error';
                $alert.text(message).removeClass('d-none').addClass('alert-danger');
                $submit.prop('disabled', false);
            });
    }

    $form.on('submit', onSubmit);

    return {};
})();
