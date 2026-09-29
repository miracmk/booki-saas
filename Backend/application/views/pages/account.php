<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="account-page" class="container backend-page py-3">
    <div id="account">
        <div class="row">
            <div class="<?= vars('is_customer') ? 'col-lg-10 offset-lg-1' : 'col-lg-8 offset-lg-2' ?>">
                <?php if (vars('is_customer')): ?>
                    <div class="alert alert-light border border-info border-opacity-25 d-flex align-items-center mb-4 p-3 shadow-sm rounded-3">
                        <div class="me-3 text-info">
                            <i class="fas fa-id-card-alt fa-2x"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading mb-1 text-dark fw-bold">
                                Müşteri Kartı ve Profil Özeti 
                                <span class="badge bg-secondary ms-2 fw-normal fs-6">Salt Okunur</span>
                            </h6>
                            <div class="text-muted small">
                                Profil bilgilerinizi ve işlem geçmişinizi bu sayfadan inceleyebilirsiniz. Bilgilerinizi güncellemek veya randevu değişikliği yapmak için lütfen işletme yetkilisi ile iletişime geçiniz.
                            </div>
                        </div>
                    </div>

                    <?php if (!empty(vars('customer_card'))): ?>
                        <?php $customer_card = vars('customer_card'); ?>
                        <?php if (!empty($customer_card['tags'])): ?>
                            <div class="mb-3 d-flex flex-wrap gap-2">
                                <?php foreach ($customer_card['tags'] as $tag): ?>
                                    <span class="badge <?= $tag['class'] ?> px-2 py-1"><?= html_escape($tag['label']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php $metrics = $customer_card['metrics'] ?? []; ?>
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6 col-md-3">
                                <div class="card h-100 border shadow-sm">
                                    <div class="card-body p-3 text-center">
                                        <div class="text-muted small text-uppercase fw-semibold mb-1">Toplam Randevu</div>
                                        <div class="fs-4 fw-bold text-primary"><?= (int)($metrics['total_appointments'] ?? 0) ?></div>
                                        <div class="text-success small"><i class="fas fa-check-circle me-1"></i><?= (int)($metrics['completed_appointments'] ?? 0) ?> Tamamlandı</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <div class="card h-100 border shadow-sm">
                                    <div class="card-body p-3 text-center">
                                        <div class="text-muted small text-uppercase fw-semibold mb-1">İptal / Gelmedi</div>
                                        <div class="fs-4 fw-bold text-secondary"><?= (int)($metrics['cancellations'] ?? 0) ?> / <?= (int)($metrics['no_shows'] ?? 0) ?></div>
                                        <div class="text-muted small">İptal ve Gelmedi</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <div class="card h-100 border shadow-sm">
                                    <div class="card-body p-3 text-center">
                                        <div class="text-muted small text-uppercase fw-semibold mb-1">Paket / Üyelik</div>
                                        <div class="fs-4 fw-bold text-info"><?= (int)($metrics['active_packages_count'] ?? 0) ?> / <?= (int)($metrics['active_memberships_count'] ?? 0) ?></div>
                                        <div class="text-muted small">Aktif Paket & Üyelik</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <div class="card h-100 border shadow-sm">
                                    <div class="card-body p-3 text-center">
                                        <div class="text-muted small text-uppercase fw-semibold mb-1">Son Ziyaret</div>
                                        <div class="fs-6 fw-bold text-dark mt-1">
                                            <?= !empty($metrics['last_visit']) ? date('d.m.Y H:i', strtotime($metrics['last_visit'])) : '-' ?>
                                        </div>
                                        <?php if (!empty($metrics['next_appointment'])): ?>
                                            <div class="text-primary small mt-1" title="<?= html_escape($metrics['next_appointment']['service_name'] ?? '') ?>">
                                                <i class="fas fa-clock me-1"></i><?= date('d.m.Y H:i', strtotime($metrics['next_appointment']['start_datetime'])) ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-muted small mt-1">Gelecek randevu yok</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($customer_card['timeline'])): ?>
                            <div class="card border shadow-sm mb-4">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                    <h6 class="mb-0 fw-bold"><i class="fas fa-history me-2 text-primary"></i>İşlem ve Randevu Geçmişi</h6>
                                    <span class="badge bg-secondary"><?= count($customer_card['timeline']) ?> Kayıt</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                        <table class="table table-hover table-striped mb-0 align-middle">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th class="ps-3">Tarih</th>
                                                    <th>İşlem / Hizmet</th>
                                                    <th>Detay</th>
                                                    <th>Durum</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($customer_card['timeline'] as $item): ?>
                                                    <tr>
                                                        <td class="ps-3 text-nowrap small text-muted">
                                                            <i class="fas <?= !empty($item['icon']) ? html_escape($item['icon']) : 'fa-circle' ?> me-1 text-<?= html_escape($item['badge'] ?? 'primary') ?>"></i>
                                                            <?= !empty($item['date']) ? date('d.m.Y H:i', strtotime($item['date'])) : '-' ?>
                                                        </td>
                                                        <td class="fw-semibold text-dark small">
                                                            <?= html_escape($item['title'] ?? '') ?>
                                                        </td>
                                                        <td class="small text-muted">
                                                            <?= html_escape($item['subtitle'] ?? '') ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?= html_escape($item['badge'] ?? 'secondary') ?>">
                                                                <?= html_escape(ucfirst($item['status'] ?? '')) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>

                <form>
                    <fieldset>
                        <div class="d-flex justify-content-between align-items-center border-bottom mb-4 py-2">
                            <h4 class="mb-0 fw-light">
                                <?= vars('is_customer') ? 'Kişisel Hesap ve İletişim Bilgileri' : lang('account') ?>
                            </h4>

                            <?php if (vars('can_edit')): ?>
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

                        <?php if (!vars('is_customer')): ?>
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
                        <?php endif; ?>
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
