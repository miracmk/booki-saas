<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="account-page" class="container backend-page py-3">
    <div id="account">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <form>
                    <fieldset>
                        <div class="d-flex justify-content-between align-items-center border-bottom mb-4 py-2">
                            <h4 class="mb-0 fw-light">
                                <?= lang('account') ?>
                            </h4>

                            <?php if (can('edit', PRIV_USER_SETTINGS)): ?>
                                <button type="button" id="save-settings" class="btn btn-primary">
                                    <i class="fas fa-check-square me-2"></i>
                                    <?= lang('save') ?>
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <input type="hidden" id="user-id">

                                <div class="mb-3">
                                    <label class="form-label" for="first-name">
                                        <?= lang('first_name') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input id="first-name" class="form-control required">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="last-name">
                                        <?= lang('last_name') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input id="last-name" class="form-control required">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="email">
                                        <?= lang('email') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input id="email" class="form-control required">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="phone-number">
                                        <?= lang('phone_number') ?>
                                    </label>
                                    <input id="phone-number" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="mobile-number">
                                        <?= lang('mobile_number') ?>
                                    </label>
                                    <input id="mobile-number" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="address">
                                        <?= lang('address') ?>
                                    </label>
                                    <input id="address" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="city">
                                        <?= lang('city') ?>
                                    </label>
                                    <input id="city" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="state">
                                        <?= lang('state') ?>
                                    </label>
                                    <input id="state" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="zip-code">
                                        <?= lang('zip_code') ?>
                                    </label>
                                    <input id="zip-code" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="notes">
                                        <?= lang('notes') ?>
                                    </label>
                                    <textarea id="notes" class="form-control" rows="3"></textarea>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label" for="username">
                                        <?= lang('username') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input id="username" class="form-control required">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="password">
                                        <?= lang('password') ?>
                                    </label>
                                    <input type="password" id="password" class="form-control"
                                           autocomplete="new-password">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="retype-password">
                                        <?= lang('retype_password') ?>
                                    </label>
                                    <input type="password" id="retype-password" class="form-control"
                                           autocomplete="new-password">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="calendar-view"><?= lang('calendar') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select id="calendar-view" class="form-select required">
                                        <option value="default"><?= lang('default') ?></option>
                                        <option value="table"><?= lang('table') ?></option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="language">
                                        <?= lang('language') ?>
                                        <span class="text-danger" hidden>*</span>
                                    </label>
                                    <select id="language" class="form-select required">
                                        <?php foreach (vars('available_languages') as $available_language): ?>
                                            <option value="<?= $available_language ?>">
                                                <?= ucfirst($available_language) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="timezone">
                                        <?= lang('timezone') ?>
                                    </label>
                                    <?php component('timezone_dropdown', [
                                        'attributes' => 'id="timezone" class="form-select required"',
                                        'grouped_timezones' => vars('grouped_timezones'),
                                    ]); ?>
                                </div>

                                <div>
                                    <label class="form-label mb-3">
                                        <?= lang('options') ?>
                                    </label>
                                </div>

                                <div class="border rounded mb-3 p-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" id="notifications" type="checkbox">
                                        <label class="form-check-label" for="notifications">
                                            <?= lang('receive_notifications') ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Two-Factor Authentication Section -->
                        <div class="border-top pt-4 mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-shield-alt text-primary me-2"></i>
                                    <?= lang('two_factor_authentication') ?>
                                </h5>
                                <span id="totp-status" class="badge bg-secondary">
                                    <?= lang('disabled') ?>
                                </span>
                            </div>

                            <p class="small text-muted mb-3">
                                <?= lang('totp_help_text') ?>
                            </p>

                            <div id="totp-disabled-content">
                                <button type="button" id="totp-setup-btn" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-lock me-1"></i>
                                    <?= lang('setup_two_factor') ?>
                                </button>
                            </div>

                            <div id="totp-enabled-content" class="d-none">
                                <div class="alert alert-info small mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <?= lang('totp_enabled_message') ?>
                                </div>

                                <div class="btn-group" role="group">
                                    <button type="button" id="totp-regenerate-btn" class="btn btn-outline-warning btn-sm">
                                        <i class="fas fa-sync-alt me-1"></i>
                                        <?= lang('regenerate_backup_codes') ?>
                                    </button>
                                    <button type="button" id="totp-disable-btn" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-trash me-1"></i>
                                        <?= lang('disable_two_factor') ?>
                                    </button>
                                </div>
                            </div>

                            <!-- TOTP Setup Modal -->
                            <div id="totp-setup-modal" class="modal fade" tabindex="-1">
                                <div class="modal-dialog modal-sm">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h6 class="modal-title">
                                                <?= lang('setup_authenticator') ?>
                                            </h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div id="totp-qr-section" class="mb-3">
                                                <p class="small text-muted mb-2">
                                                    <?= lang('scan_with_authenticator') ?>
                                                </p>
                                                <div id="totp-qr-code" class="text-center"></div>

                                                <div class="mt-3">
                                                    <p class="small text-muted mb-2">
                                                        <?= lang('or_enter_manually') ?>
                                                    </p>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" id="totp-secret-display" class="form-control" readonly>
                                                        <button class="btn btn-outline-secondary" type="button" id="totp-copy-secret">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div id="totp-verify-section">
                                                <label class="form-label small fw-medium">
                                                    <?= lang('enter_verification_code') ?>
                                                </label>
                                                <input type="text" id="totp-verify-code" class="form-control form-control-sm text-center" placeholder="000000" inputmode="numeric">
                                                <small class="text-muted d-block mt-2">
                                                    <?= lang('verification_code_hint') ?>
                                                </small>
                                            </div>

                                            <div id="totp-error-message" class="alert alert-danger small mt-2 d-none"></div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                                                <?= lang('cancel') ?>
                                            </button>
                                            <button type="button" id="totp-confirm-btn" class="btn btn-primary btn-sm">
                                                <?= lang('enable') ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Backup Codes Modal -->
                            <div id="backup-codes-modal" class="modal fade" tabindex="-1">
                                <div class="modal-dialog modal-sm">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h6 class="modal-title">
                                                <?= lang('backup_codes') ?>
                                            </h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body">
                                            <p class="small text-danger fw-medium mb-3">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                <?= lang('backup_codes_shown_once') ?>
                                            </p>

                                            <div id="backup-codes-list" class="bg-light p-3 rounded small font-monospace mb-3" style="max-height: 200px; overflow-y: auto;">
                                            </div>

                                            <button type="button" id="backup-codes-copy-btn" class="btn btn-sm btn-outline-secondary w-100">
                                                <i class="fas fa-copy me-1"></i>
                                                <?= lang('copy_codes') ?>
                                            </button>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">
                                                <?= lang('close') ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Disable TOTP Modal -->
                            <div id="totp-disable-modal" class="modal fade" tabindex="-1">
                                <div class="modal-dialog modal-sm">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h6 class="modal-title">
                                                <?= lang('disable_two_factor') ?>
                                            </h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body">
                                            <p class="small mb-3">
                                                <?= lang('disable_totp_warning') ?>
                                            </p>

                                            <label class="form-label small fw-medium">
                                                <?= lang('confirm_password') ?>
                                            </label>
                                            <input type="password" id="totp-disable-password" class="form-control form-control-sm" autocomplete="current-password">
                                            <small class="text-muted d-block mt-2">
                                                <?= lang('password_required_for_security') ?>
                                            </small>

                                            <div id="totp-disable-error" class="alert alert-danger small mt-2 d-none"></div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                                                <?= lang('cancel') ?>
                                            </button>
                                            <button type="button" id="totp-confirm-disable-btn" class="btn btn-danger btn-sm">
                                                <?= lang('disable') ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Regenerate Backup Codes Modal -->
                            <div id="regenerate-backup-codes-modal" class="modal fade" tabindex="-1">
                                <div class="modal-dialog modal-sm">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h6 class="modal-title">
                                                <?= lang('regenerate_backup_codes') ?>
                                            </h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body">
                                            <p class="small mb-3">
                                                <?= lang('regenerate_codes_help') ?>
                                            </p>

                                            <label class="form-label small fw-medium">
                                                <?= lang('enter_verification_code') ?>
                                            </label>
                                            <input type="text" id="regenerate-totp-code" class="form-control form-control-sm text-center" placeholder="000000" inputmode="numeric">

                                            <div id="regenerate-error" class="alert alert-danger small mt-2 d-none"></div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                                                <?= lang('cancel') ?>
                                            </button>
                                            <button type="button" id="regenerate-confirm-btn" class="btn btn-primary btn-sm">
                                                <?= lang('regenerate') ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>

    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/http/account_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/account.js') ?>"></script>

<?php end_section('scripts'); ?>
