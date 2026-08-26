/* ----------------------------------------------------------------------------
 * Ki Reservation - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */
App.Pages.EmailTemplateSettings = (function () {
    // Keep in sync with Email_template_settings::TEMPLATE_KEYS (PHP).
    const ROLE_SPLIT_BASE_TEMPLATES = ['appointment_saved', 'appointment_deleted'];
    const FLAT_TEMPLATE_KEYS = [
        'appointment_saved_customer',
        'appointment_saved_admin',
        'appointment_saved_secretary',
        'appointment_saved_provider',
        'appointment_deleted_customer',
        'appointment_deleted_admin',
        'appointment_deleted_secretary',
        'appointment_deleted_provider',
        'account_recovery',
        'password_reset',
    ];

    const $saveButton = $('#save-email-templates');
    const $resetButton = $('#reset-current-template');
    const $previewButton = $('#preview-current-template');
    const $baseTabs = $('#email-template-base-tabs .nav-link');
    const $roleTabsWrapper = $('#email-template-role-tabs');
    const $roleTabs = $roleTabsWrapper.find('.nav-link');
    const $roleHint = $('#email-template-role-hint');
    const $previewFrame = $('#email-template-preview-frame');
    const $editorFrame = $('#email-template-editor-frame');
    const $sourceTextarea = $('#email-template-source');
    const $viewVisualButton = $('#email-template-view-visual');
    const $viewSourceButton = $('#email-template-view-source');
    const $insertLinkButton = $('#email-template-insert-link');

    let defaults = {};
    let templateHtml = {}; // flat key -> current HTML string (source of truth between tab switches)
    let activeBaseTemplate = 'appointment_saved';
    let activeRole = 'customer';
    let sourceViewActive = false;
    let previewModal = null;

    // Salon Flora bugfix (2026-08-24): the iframe's srcdoc load is asynchronous (loadIntoIframe
    // returns before the browser has actually parsed the document), and merely LOOKING at the
    // visual editor - switching to the "Kod" tab, clicking "Önizleme" - used to re-serialize the
    // iframe's DOM via outerHTML even when the admin never touched anything. Because the HTML5
    // parser silently reshapes invalid table markup on load (see Email_messages.php docblock), that
    // read-only round-trip was enough to corrupt the template on save. frameReady guards against
    // reading a still-loading iframe; frameDirty guards against reading one the admin never
    // actually edited - only a real edit (typing, toolbar command, link insert) marks it dirty and
    // makes captureActiveEditor() write the iframe's content back into templateHtml.
    let frameReady = false;
    let frameDirty = false;

    /**
     * True when the given base template is split by recipient role.
     *
     * @param {String} baseTemplate
     *
     * @return {Boolean}
     */
    function isRoleSplit(baseTemplate) {
        return ROLE_SPLIT_BASE_TEMPLATES.includes(baseTemplate);
    }

    /**
     * The flat template key for the currently active (base template, role) selection - must match
     * Email_template_settings::TEMPLATE_KEYS / parse_template_key() naming on the PHP side.
     *
     * @return {String}
     */
    function activeTemplateKey() {
        return isRoleSplit(activeBaseTemplate) ? activeBaseTemplate + '_' + activeRole : activeBaseTemplate;
    }

    /**
     * Setting name for a flat template key (must match Email_messages::TEMPLATE_SETTING_KEYS).
     *
     * @param {String} templateKey
     *
     * @return {String}
     */
    function settingName(templateKey) {
        return 'email_template_' + templateKey;
    }

    /**
     * Turn on rich-text editing inside the (already-loaded) editor iframe document.
     */
    function enableDesignMode() {
        const frameDocument = $editorFrame[0].contentDocument;

        if (frameDocument) {
            frameDocument.designMode = 'on';
            frameDocument.addEventListener('input', () => {
                frameDirty = true;
            });
        }

        frameReady = true;
    }

    /**
     * Read the full HTML document currently sitting inside the editor iframe, as a string.
     *
     * @return {String}
     */
    function captureIframeHtml() {
        const frameDocument = $editorFrame[0].contentDocument;

        if (!frameDocument || !frameDocument.documentElement) {
            return '';
        }

        return '<!doctype html>\n' + frameDocument.documentElement.outerHTML;
    }

    /**
     * Push a template's HTML into the editor iframe and (re)enable editing once it has loaded.
     *
     * @param {String} html
     */
    function loadIntoIframe(html) {
        const $frame = $editorFrame;

        // A load may already be in flight for a previous template - mark not-ready/not-dirty
        // immediately so a capture that races with it (see captureActiveEditor()) can't attribute
        // its content to the wrong templateKey or persist a stale/partial read.
        frameReady = false;
        frameDirty = false;

        $frame.off('load.emailTemplateEditor').on('load.emailTemplateEditor', enableDesignMode);

        $frame.attr('srcdoc', html);
    }

    /**
     * Capture whatever is currently in the visible editor (iframe or source textarea) into
     * templateHtml[templateKey], WITHOUT switching views.
     *
     * Visual (iframe) capture only happens when the admin has actually made an edit since the
     * iframe was loaded (frameDirty) - see the frameReady/frameDirty declaration above for why.
     * Merely displaying/previewing an unmodified template never rewrites templateHtml, so an
     * unrelated browser-parser reshaping of the markup can't silently corrupt it.
     *
     * @param {String} templateKey
     */
    function captureActiveEditor(templateKey) {
        if (sourceViewActive) {
            templateHtml[templateKey] = $sourceTextarea.val();
            return;
        }

        if (!frameReady || !frameDirty) {
            return;
        }

        const captured = captureIframeHtml();

        if (captured !== '') {
            templateHtml[templateKey] = captured;
        }
    }

    /**
     * Render the currently active templateHtml entry into whichever view (iframe or source) is
     * visible right now.
     */
    function renderActiveEditor() {
        const html = templateHtml[activeTemplateKey()] || '';

        if (sourceViewActive) {
            $sourceTextarea.val(html);
        } else {
            loadIntoIframe(html);
        }
    }

    /**
     * Re-render which base/role tabs are active, which placeholder list and role hint show, and
     * load the newly-active template's content into the editor.
     */
    function render() {
        $baseTabs.removeClass('active');
        $baseTabs.filter('[data-base-template="' + activeBaseTemplate + '"]').addClass('active');

        const roleSplit = isRoleSplit(activeBaseTemplate);
        $roleTabsWrapper.toggleClass('d-none', !roleSplit);
        $roleHint.toggleClass('d-none', !roleSplit);

        $roleTabs.removeClass('active');
        $roleTabs.filter('[data-role="' + activeRole + '"]').addClass('active');

        $('.placeholder-group').addClass('d-none');
        $('.placeholder-group[data-base-template="' + activeBaseTemplate + '"]').removeClass('d-none');

        renderActiveEditor();
    }

    /**
     * Switch the active base template tab, capturing the outgoing tab's edits first. Resets the
     * role tab to "customer" for role-split templates, so switching groups never leaves a stale
     * role selected.
     *
     * @param {String} baseTemplate
     */
    function activateBaseTemplate(baseTemplate) {
        captureActiveEditor(activeTemplateKey());
        activeBaseTemplate = baseTemplate;
        activeRole = 'customer';
        render();
    }

    /**
     * Switch the active role tab within the current base template, capturing the outgoing tab's
     * edits first.
     *
     * @param {String} role
     */
    function activateRole(role) {
        captureActiveEditor(activeTemplateKey());
        activeRole = role;
        render();
    }

    /**
     * Toggle between the visual (iframe) and source (textarea) editors, carrying the current
     * content over to whichever view is being switched to.
     */
    function toggleSourceView() {
        captureActiveEditor(activeTemplateKey());
        sourceViewActive = !sourceViewActive;

        $editorFrame.toggleClass('d-none', sourceViewActive);
        $sourceTextarea.toggleClass('d-none', !sourceViewActive);
        $viewVisualButton.toggleClass('active', !sourceViewActive);
        $viewSourceButton.toggleClass('active', sourceViewActive);
        $('#email-template-toolbar [data-cmd], #email-template-insert-link').prop('disabled', sourceViewActive);

        renderActiveEditor();
    }

    /**
     * Run a document.execCommand against the editor iframe's document (bold/italic/align/etc).
     *
     * @param {Event} event
     */
    function onToolbarCommandClick(event) {
        if (sourceViewActive) {
            return;
        }

        const frameDocument = $editorFrame[0].contentDocument;

        if (!frameDocument) {
            return;
        }

        $editorFrame[0].contentWindow.focus();
        frameDocument.execCommand($(event.currentTarget).data('cmd'), false, null);
        frameDirty = true;
    }

    /**
     * Prompt for a URL and wrap the current iframe selection in a link.
     */
    function onInsertLinkClick() {
        if (sourceViewActive) {
            return;
        }

        const frameDocument = $editorFrame[0].contentDocument;

        if (!frameDocument) {
            return;
        }

        const url = prompt(lang('insert_link_prompt'));

        if (!url) {
            return;
        }

        $editorFrame[0].contentWindow.focus();
        frameDocument.execCommand('createLink', false, url);
        frameDirty = true;
    }

    /**
     * Reset the currently active template's editor content back to the shipped default.
     */
    function onResetClick() {
        const templateKey = activeTemplateKey();

        if (!(templateKey in defaults)) {
            return;
        }

        if (!confirm(lang('email_template_reset_confirm'))) {
            return;
        }

        templateHtml[templateKey] = defaults[templateKey];
        renderActiveEditor();
    }

    /**
     * Ask the backend to render the currently active (possibly unsaved) template against sample
     * data, then show it inside a sandboxed (script-disabled) preview iframe.
     */
    function onPreviewClick() {
        const templateKey = activeTemplateKey();

        captureActiveEditor(templateKey);

        App.Http.EmailTemplateSettings.preview(templateKey, templateHtml[templateKey]).done((response) => {
            $previewFrame.attr('srcdoc', response.html);

            if (!previewModal) {
                previewModal = new bootstrap.Modal('#email-template-preview-modal');
            }

            previewModal.show();
        });
    }

    /**
     * Toggle the preview iframe between desktop and mobile widths.
     *
     * @param {Event} event
     */
    function onPreviewWidthClick(event) {
        const $button = $(event.currentTarget);

        $('#email-template-preview-modal [data-preview-width]').removeClass('active');
        $button.addClass('active');

        $previewFrame.css('width', $button.data('preview-width') + 'px');
    }

    /**
     * Capture every template's current content (including whichever tab is on-screen right now)
     * and persist it.
     */
    function onSaveClick() {
        captureActiveEditor(activeTemplateKey());

        const payload = FLAT_TEMPLATE_KEYS.map((templateKey) => ({
            name: settingName(templateKey),
            value: templateHtml[templateKey] || '',
        }));

        App.Http.EmailTemplateSettings.save(payload).done(() => {
            App.Layouts.Backend.displayNotification(lang('settings_saved'));
        });
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        const templates = vars('templates') || {};

        FLAT_TEMPLATE_KEYS.forEach((templateKey) => {
            const template = templates[templateKey] || { value: '', default: '' };

            defaults[templateKey] = template.default || '';
            templateHtml[templateKey] = template.value || template.default || '';
        });

        render();

        $baseTabs.on('click', (event) => activateBaseTemplate($(event.currentTarget).data('base-template')));
        $roleTabs.on('click', (event) => activateRole($(event.currentTarget).data('role')));
        $resetButton.on('click', onResetClick);
        $previewButton.on('click', onPreviewClick);
        $saveButton.on('click', onSaveClick);
        $viewVisualButton.on('click', () => sourceViewActive && toggleSourceView());
        $viewSourceButton.on('click', () => !sourceViewActive && toggleSourceView());
        $('#email-template-toolbar [data-cmd]').on('click', onToolbarCommandClick);
        $insertLinkButton.on('click', onInsertLinkClick);
        $('#email-template-preview-modal [data-preview-width]').on('click', onPreviewWidthClick);
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
