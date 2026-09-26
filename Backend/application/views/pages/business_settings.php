<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="business-logic-page" class="container backend-page py-3">
    <div id="business-logic">
        <div class="row">
            <div class="col-sm-3">
                <?php component('settings_nav'); ?>
            </div>
            <div class="col-sm-9">
                <form>
                    <fieldset>
                        <div class="d-flex justify-content-between align-items-center border-bottom mb-4 py-2">
                            <h4 class="mb-0 fw-light">
                                <?= lang('business_logic') ?>
                            </h4>

                            <?php if (can('edit', PRIV_SYSTEM_SETTINGS)): ?>
                                <button type="button" id="save-settings" class="btn btn-primary">
                                    <i class="fas fa-check-square me-2"></i>
                                    <?= lang('save') ?>
                                </button>
                            <?php endif; ?>
                        </div>

                        <h5 class="mb-3 fw-light"><?= lang('working_plan') ?></h5>

                        <p class="form-text text-muted mb-4">
                            <?= lang('edit_working_plan_hint') ?>
                        </p>

                        <div class="table-responsive">
                            <table class="working-plan table table-striped">
                                <thead>
                                <tr>
                                    <th><?= lang('day') ?></th>
                                    <th><?= lang('start') ?></th>
                                    <th><?= lang('end') ?></th>
                                </tr>
                                </thead>
                                <tbody><!-- Dynamic Content --></tbody>
                            </table>
                        </div>

                        <div class="text-end mb-5">
                            <button class="btn btn-outline-secondary" id="apply-global-working-plan" type="button">
                                <i class="fas fa-check"></i>
                                <?= lang('apply_to_all_providers') ?>
                            </button>
                        </div>

                        <h5 class="mb-3 fw-light"><?= lang('breaks') ?></h5>

                        <p class="form-text text-muted">
                            <?= lang('edit_breaks_hint') ?>
                        </p>

                        <div class="mt-2">
                            <button type="button" class="add-break btn btn-primary">
                                <i class="fas fa-plus-square me-2"></i>
                                <?= lang('add_break') ?>
                            </button>
                        </div>

                        <br>

                        <div class="table-responsive">
                            <table class="breaks table table-striped mb-5">
                                <thead>
                                <tr>
                                    <th><?= lang('day') ?></th>
                                    <th><?= lang('start') ?></th>
                                    <th><?= lang('end') ?></th>
                                    <th><?= lang('actions') ?></th>
                                </tr>
                                </thead>
                                <tbody><!-- Dynamic Content --></tbody>
                            </table>
                        </div>

                        <?php if (can('view', PRIV_BLOCKED_PERIODS)): ?>
                            <h5 class="mb-3 fw-light"><?= lang('blocked_periods') ?></h5>

                            <p class="form-text text-muted">
                                <?= lang('blocked_periods_hint') ?>
                            </p>

                            <div class="mb-5">
                                <a href="<?= site_url('blocked_periods') ?>" class="btn btn-primary">
                                    <i class="fas fa-cogs me-2"></i>
                                    <?= lang('configure') ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <h5 class="mb-3 fw-light"><?= lang(
                            'allow_rescheduling_cancellation_before',
                        ) ?></h5>

                        <div class="mb-5">
                            <label for="book-advance-timeout" class="form-label">
                                <?= lang('timeout_minutes') ?>
                            </label>
                            <input id="book-advance-timeout" data-field="book_advance_timeout" class="form-control"
                                   type="number" min="15">
                            <div class="form-text text-muted">
                                <small>
                                    <?= lang('book_advance_timeout_hint') ?>
                                </small>
                            </div>
                        </div>

                        <h5 class="mb-3 fw-light"><?= lang('future_booking_limit') ?></h5>

                        <div class="mb-5">
                            <label for="future-booking-limit" class="form-label">
                                <?= lang('limit_days') ?>
                            </label>
                            <input id="future-booking-limit" data-field="future_booking_limit" class="form-control"
                                   type="number" min="15">
                            <div class="form-text text-muted">
                                <small>
                                    <?= lang('future_booking_limit_hint') ?>
                                </small>
                            </div>
                        </div>

                        <?php // Salon Flora customization - session duration/commission calculation rules (2026-08-25). ?>
                        <h5 class="mb-3 fw-light">Seans Süresi ve Sapma Kuralları</h5>

                        <div class="mb-3">
                            <label for="session-deviation-tolerance-minutes" class="form-label">
                                Tolerans (dakika)
                            </label>
                            <input id="session-deviation-tolerance-minutes"
                                   data-field="session_deviation_tolerance_minutes" class="form-control"
                                   type="number" min="0" max="60">
                            <div class="form-text text-muted">
                                <small>
                                    Gerçek seans süresi planlanan süreden bu kadar dakika (artı veya eksi) sapıyorsa
                                    "normal" sayılır ve tam planlanan süre üzerinden faturalanır/komisyonlanır. Bu
                                    aralığın dışına çıkan bir erken çıkış "Haklı/Haksız" sınıflandırması ister.
                                </small>
                            </div>
                        </div>

                        <div class="mb-5">
                            <label for="session-duration-baseline" class="form-label">
                                Süre Hesaplama Başlangıcı
                            </label>
                            <select id="session-duration-baseline" data-field="session_duration_baseline"
                                    class="form-select">
                                <option value="check_in">Gerçek check-in saati</option>
                                <option value="booked_start">Randevu (booking) saati</option>
                            </select>
                            <div class="form-text text-muted">
                                <small>
                                    Terapist geç check-in yaparsa, planlanan seans süresi nereden itibaren
                                    sayılsın? "Gerçek check-in saati" seçiliyse, geç başlayan bir seans de check-out
                                    saatine kadar tam süresini doldurmalıdır (örn. 18:06 check-in, 60 dk hizmet →
                                    beklenen bitiş 19:06). "Randevu saati" seçiliyse beklenen bitiş her zaman
                                    orijinal randevu saatine göre sabittir (örn. 19:00).
                                </small>
                            </div>
                        </div>

                        <h5 class="mb-3 fw-light">AI Assistant (Beta)</h5>

                        <div class="mb-5">
                            <div class="form-check">
                                <input id="ai-assistant-enabled" type="checkbox" data-field="ai_assistant_enabled"
                                       class="form-check-input">
                                <label for="ai-assistant-enabled" class="form-check-label">
                                    Enable AI Assistant
                                </label>
                            </div>
                            <div class="form-text text-muted">
                                <small>
                                    Enable the AI Assistant widget on the booking page (voice-to-text transcription, coming soon).
                                </small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-start align-items-center mb-3">
                            <h5 class="mb-0 me-3 fw-light">
                                <?= lang('appointment_status_options') ?>
                            </h5>
                        </div>

                        <p class="form-text text-muted mb-4">
                            <?= lang('appointment_status_options_info') ?>
                        </p>

                        <?php component('appointment_status_options', [
                            'attributes' => 'id="appointment-status-options"',
                        ]); ?>

                    </fieldset>
                </form>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/vendor/jquery-jeditable/jquery.jeditable.min.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/ui.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/working_plan.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/business_settings_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/business_settings.js') ?>"></script>

<?php end_section('scripts'); ?>

