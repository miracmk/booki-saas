/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */
App.Pages.ChannelTemplateSettings = (function () {
    // Keep in sync with Channel_template_settings::TEMPLATE_KEYS (PHP).
    const TEMPLATE_KEYS = [
        'appointment_pending',
        'appointment_approved',
        'appointment_rescheduled',
        'appointment_cancelled',
        'appointment_reminder',
        'customer_channel_linked',
    ];

    const $saveButton = $('#save-channel-templates');
    const $resetButton = $('#reset-current-template');
    const $previewButton = $('#preview-current-template');
    const $templateButtons = $('#channel-template-list .nav-link');
    const $contentTextarea = $('#channel-template-content');
    const $previewBody = $('#channel-template-preview-body');

    let templates = {}; // template key -> { value, default }
    let contents = {}; // template key -> current editor content (source of truth between switches)
    let activeKey = 'appointment_pending';
    let previewModal = null;

    /**
     * Setting name for a template key (must match Channel_template_settings::TEMPLATE_KEYS).
     *
     * @param {String} templateKey
     *
     * @return {String}
     */
    function settingName(templateKey) {
        return 'channel_template_' + templateKey;
    }

    /**
     * Persist whatever is currently in the editor into the contents map.
     *
     * @param {String} templateKey
     */
    function captureActiveEditor(templateKey) {
        contents[templateKey] = $contentTextarea.val();
    }

    /**
     * Load the currently active template's content into the editor and highlight its tab.
     */
    function renderActive() {
        $templateButtons.removeClass('active');
        $templateButtons.filter('[data-template-key="' + activeKey + '"]').addClass('active');

        $contentTextarea.val(contents[activeKey] || '');
    }

    /**
     * Switch the active template tab, capturing the outgoing tab's edits first.
     *
     * @param {String} templateKey
     */
    function activateTemplate(templateKey) {
        captureActiveEditor(activeKey);
        activeKey = templateKey;
        renderActive();
    }

    /**
     * Reset the currently active template's editor content back to the shipped default.
     */
    function onResetClick() {
        if (!(activeKey in defaults())) {
            return;
        }

        if (!confirm(lang('email_template_reset_confirm'))) {
            return;
        }

        contents[activeKey] = defaults()[activeKey];
        renderActive();
    }

    /**
     * Lazily built map of template key -> built-in default body.
     *
     * @return {Object}
     */
    function defaults() {
        return Object.keys(templates).reduce((acc, key) => {
            acc[key] = templates[key].default || '';
            return acc;
        }, {});
    }

    /**
     * Ask the backend to render the currently active (possibly unsaved) template against sample
     * channel data, then show it inside the preview modal.
     */
    function onPreviewClick() {
        captureActiveEditor(activeKey);

        App.Http.ChannelTemplateSettings.preview(activeKey, contents[activeKey]).done((response) => {
            $previewBody.text(response.text || '');

            if (!previewModal) {
                previewModal = new bootstrap.Modal('#channel-template-preview-modal');
            }

            previewModal.show();
        });
    }

    /**
     * Capture every template's current content (including whichever tab is on-screen right now)
     * and persist it.
     */
    function onSaveClick() {
        captureActiveEditor(activeKey);

        const payload = TEMPLATE_KEYS.map((templateKey) => ({
            name: settingName(templateKey),
            value: contents[templateKey] || '',
        }));

        App.Http.ChannelTemplateSettings.save(payload).done(() => {
            App.Layouts.Backend.displayNotification(lang('settings_saved'));
        });
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        templates = vars('templates') || {};

        TEMPLATE_KEYS.forEach((templateKey) => {
            const template = templates[templateKey] || { value: '', default: '' };

            contents[templateKey] = template.value || template.default || '';
        });

        renderActive();

        $templateButtons.on('click', (event) => activateTemplate($(event.currentTarget).data('template-key')));
        $resetButton.on('click', onResetClick);
        $previewButton.on('click', onPreviewClick);
        $saveButton.on('click', onSaveClick);
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();