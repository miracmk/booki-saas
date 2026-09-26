<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="channel-template-settings-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div class="col-sm-9">
            <div class="d-flex justify-content-between align-items-center border-bottom mb-4 py-2">
                <h4 class="mb-0 fw-light">Kanal Mesaj Şablonları</h4>

                <button type="button" id="save-channel-templates" class="btn btn-primary">
                    <i class="fas fa-check-square me-2"></i>
                    <?= lang('save') ?>
                </button>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <ul class="nav nav-pills flex-column" id="channel-template-list">
                        <li class="nav-item mb-1">
                            <button class="nav-link active w-100 text-start" data-template-key="appointment_pending" type="button">
                                Randevu Talebi
                            </button>
                        </li>
                        <li class="nav-item mb-1">
                            <button class="nav-link w-100 text-start" data-template-key="appointment_approved" type="button">
                                Randevu Onayı
                            </button>
                        </li>
                        <li class="nav-item mb-1">
                            <button class="nav-link w-100 text-start" data-template-key="appointment_rescheduled" type="button">
                                Randevu Güncelleme
                            </button>
                        </li>
                        <li class="nav-item mb-1">
                            <button class="nav-link w-100 text-start" data-template-key="appointment_cancelled" type="button">
                                Randevu İptali
                            </button>
                        </li>
                        <li class="nav-item mb-1">
                            <button class="nav-link w-100 text-start" data-template-key="appointment_reminder" type="button">
                                Randevu Hatırlatması
                            </button>
                        </li>
                        <li class="nav-item mb-1">
                            <button class="nav-link w-100 text-start" data-template-key="customer_channel_linked" type="button">
                                Kanal Eşleştirme
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="col-md-8">
                    <div class="border rounded p-3">
                        <p class="text-muted small mb-2">
                            Şablonlarda dinamik alanları şu yer tutucularla kullanabilirsiniz:
                        </p>

                        <p class="text-muted small mb-2" id="channel-template-placeholders">
                            <code>{company_name}</code> <code>{customer_name}</code>
                            <code>{service_name}</code> <code>{start_datetime}</code>
                            <code>{end_datetime}</code> <code>{end_time_clause}</code>
                            <code>{provider_name}</code> <code>{company_address}</code>
                            <code>{address_clause}</code> <code>{company_phone}</code>
                            <code>{phone_clause}</code> <code>{booking_url}</code>
                            <code>{notes}</code>
                        </p>

                        <textarea id="channel-template-content" class="form-control" rows="18"
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="channel-template-preview-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang('preview') ?></h5>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <pre id="channel-template-preview-body" class="rounded bg-white border p-3 mb-0"
                     style="white-space: pre-wrap; font-family: inherit; font-size: 14px;"></pre>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/http/channel_template_settings_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/channel_template_settings.js') ?>"></script>

<?php end_section('scripts'); ?>