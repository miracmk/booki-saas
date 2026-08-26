<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="email-template-settings-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div class="col-sm-9">
            <div class="d-flex justify-content-between align-items-center border-bottom mb-4 py-2">
                <h4 class="mb-0 fw-light"><?= lang('email_templates') ?></h4>

                <button type="button" id="save-email-templates" class="btn btn-primary">
                    <i class="fas fa-check-square me-2"></i>
                    <?= lang('save') ?>
                </button>
            </div>

            <ul class="nav nav-tabs" id="email-template-base-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-base-template="appointment_saved" type="button">
                        <?= lang('email_template_appointment_saved') ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-base-template="appointment_deleted" type="button">
                        <?= lang('email_template_appointment_deleted') ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-base-template="account_recovery" type="button">
                        <?= lang('email_template_account_recovery') ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-base-template="password_reset" type="button">
                        <?= lang('email_template_password_reset') ?>
                    </button>
                </li>
            </ul>

            <div class="border border-top-0 rounded-bottom p-3">
                <ul class="nav nav-pills mb-3" id="email-template-role-tabs">
                    <li class="nav-item">
                        <button class="nav-link active" data-role="customer" type="button"><?= lang('email_template_role_customer') ?></button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-role="admin" type="button"><?= lang('email_template_role_admin') ?></button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-role="secretary" type="button"><?= lang('email_template_role_secretary') ?></button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-role="provider" type="button"><?= lang('email_template_role_provider') ?></button>
                    </li>
                </ul>

                <div class="d-flex flex-wrap align-items-center gap-2 mb-2" id="email-template-toolbar">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary" data-cmd="bold" title="<?= lang('bold') ?>"><i class="fas fa-bold"></i></button>
                        <button type="button" class="btn btn-outline-secondary" data-cmd="italic" title="<?= lang('italic') ?>"><i class="fas fa-italic"></i></button>
                        <button type="button" class="btn btn-outline-secondary" data-cmd="underline" title="<?= lang('underline') ?>"><i class="fas fa-underline"></i></button>
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary" data-cmd="justifyLeft" title="<?= lang('align_left') ?>"><i class="fas fa-align-left"></i></button>
                        <button type="button" class="btn btn-outline-secondary" data-cmd="justifyCenter" title="<?= lang('align_center') ?>"><i class="fas fa-align-center"></i></button>
                        <button type="button" class="btn btn-outline-secondary" data-cmd="justifyRight" title="<?= lang('align_right') ?>"><i class="fas fa-align-right"></i></button>
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary" data-cmd="insertUnorderedList" title="<?= lang('bulleted_list') ?>"><i class="fas fa-list-ul"></i></button>
                        <button type="button" class="btn btn-outline-secondary" data-cmd="insertOrderedList" title="<?= lang('numbered_list') ?>"><i class="fas fa-list-ol"></i></button>
                        <button type="button" class="btn btn-outline-secondary" id="email-template-insert-link" title="<?= lang('link') ?>"><i class="fas fa-link"></i></button>
                        <button type="button" class="btn btn-outline-secondary" data-cmd="removeFormat" title="<?= lang('remove_format') ?>"><i class="fas fa-eraser"></i></button>
                    </div>
                    <div class="btn-group btn-group-sm ms-auto" role="group">
                        <button type="button" class="btn btn-outline-secondary active" id="email-template-view-visual"><?= lang('visual_view') ?></button>
                        <button type="button" class="btn btn-outline-secondary" id="email-template-view-source"><?= lang('source_view') ?></button>
                    </div>
                </div>

                <p class="text-muted small mb-2">
                    <?= lang('email_template_editable_hint') ?>
                </p>

                <iframe id="email-template-editor-frame" title="editor" sandbox="allow-same-origin"
                        style="width: 100%; min-height: 480px; border: 1px solid #ddd; background: #fff;"></iframe>

                <textarea id="email-template-source" class="d-none form-control" rows="20"
                          style="font-family: monospace; font-size: 13px;"></textarea>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <button type="button" id="reset-current-template" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-undo me-2"></i>
                        <?= lang('reset_to_default') ?>
                    </button>

                    <button type="button" id="preview-current-template" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-eye me-2"></i>
                        <?= lang('preview') ?>
                    </button>
                </div>

                <hr>

                <div id="email-template-placeholders">
                    <p class="text-muted small mb-2">
                        <?= lang('email_template_placeholders_hint') ?>
                    </p>

                    <div class="placeholder-group" data-base-template="appointment_saved">
                        <code>{{subject}}</code> <code>{{message}}</code> <code>{{service_name}}</code>
                        <code>{{service_description}}</code> <code>{{provider_name}}</code>
                        <code>{{appointment_start}}</code> <code>{{appointment_end}}</code>
                        <code>{{appointment_timezone}}</code> <code>{{appointment_status}}</code>
                        <code>{{appointment_location}}</code> <code>{{appointment_meeting_link}}</code>
                        <code>{{appointment_notes}}</code> <code>{{customer_name}}</code>
                        <code>{{customer_email}}</code> <code>{{customer_phone}}</code>
                        <code>{{customer_address}}</code> <code>{{appointment_link}}</code>
                        <code>{{company_name}}</code> <code>{{company_link}}</code>
                    </div>

                    <div class="placeholder-group d-none" data-base-template="appointment_deleted">
                        <code>{{service_name}}</code> <code>{{service_description}}</code>
                        <code>{{provider_name}}</code> <code>{{appointment_start}}</code>
                        <code>{{appointment_end}}</code> <code>{{appointment_timezone}}</code>
                        <code>{{appointment_status}}</code> <code>{{appointment_location}}</code>
                        <code>{{appointment_meeting_link}}</code> <code>{{appointment_notes}}</code>
                        <code>{{customer_name}}</code> <code>{{customer_email}}</code>
                        <code>{{customer_phone}}</code> <code>{{customer_address}}</code>
                        <code>{{cancellation_reason}}</code> <code>{{company_name}}</code>
                        <code>{{company_link}}</code>
                    </div>

                    <div class="placeholder-group d-none" data-base-template="account_recovery">
                        <code>{{subject}}</code> <code>{{message}}</code> <code>{{company_name}}</code>
                        <code>{{company_link}}</code>
                    </div>

                    <div class="placeholder-group d-none" data-base-template="password_reset">
                        <code>{{subject}}</code> <code>{{message}}</code> <code>{{reset_link}}</code>
                        <code>{{company_name}}</code> <code>{{company_link}}</code>
                    </div>

                    <p class="text-muted small mt-2 mb-2" id="email-template-role-hint">
                        <?= lang('email_template_role_hint') ?>
                    </p>

                    <p class="text-muted small mb-0">
                        <?= lang('email_template_conditional_hint') ?>
                        <code>{{#if customer_email}}...{{/if}}</code>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="email-template-preview-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang('preview') ?></h5>
                <div class="btn-group ms-3" role="group">
                    <button type="button" class="btn btn-outline-secondary btn-sm active" data-preview-width="580">
                        <i class="fas fa-desktop"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-preview-width="375">
                        <i class="fas fa-mobile-alt"></i>
                    </button>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light d-flex justify-content-center">
                <iframe id="email-template-preview-frame" title="preview" sandbox=""
                        style="width: 580px; max-width: 100%; height: 70vh; border: 1px solid #ddd; background: #fff;"></iframe>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/http/email_template_settings_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/email_template_settings.js') ?>"></script>

<?php end_section('scripts'); ?>
