<?php extend('layouts/account_layout'); ?>

<?php section('content'); ?>

<div class="text-center mb-4">
    <h4 class="text-primary fw-semibold mb-1"><?= lang('onboarding_title') ?></h4>
    <p class="small mb-0"><?= lang('onboarding_intro') ?></p>
</div>

<div class="alert d-none"></div>

<form id="onboarding-form">
    <div class="mb-3">
        <label for="company_name" class="form-label fw-medium"><?= lang('company_name') ?></label>
        <input type="text" id="company_name" name="company_name" class="form-control"
               value="<?= e(vars('company_name')) ?>" required>
    </div>

    <div class="mb-3">
        <label for="business_type" class="form-label fw-medium"><?= lang('onboarding_business_type') ?></label>
        <input type="text" id="business_type" name="business_type" class="form-control"
               placeholder="<?= lang('onboarding_business_type_hint') ?>"
               value="<?= e(vars('business_type')) ?>">
    </div>

    <div class="mb-3">
        <label for="company_address" class="form-label fw-medium"><?= lang('onboarding_address') ?></label>
        <input type="text" id="company_address" name="company_address" class="form-control"
               value="<?= e(vars('company_address')) ?>">
    </div>

    <div class="mb-3">
        <label for="company_phone" class="form-label fw-medium"><?= lang('onboarding_phone') ?></label>
        <input type="text" id="company_phone" name="company_phone" class="form-control"
               value="<?= e(vars('company_phone')) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label fw-medium"><?= lang('onboarding_working_hours') ?></label>
        <div class="d-flex gap-2 align-items-center mb-2">
            <input type="time" id="working_start" name="working_start" class="form-control" value="09:00">
            <span>-</span>
            <input type="time" id="working_end" name="working_end" class="form-control" value="18:00">
        </div>
        <label class="form-label small text-muted"><?= lang('onboarding_closed_days') ?></label>
        <div class="d-flex flex-wrap gap-3" id="closed-days">
            <?php
            $day_labels = [
                'monday' => lang('monday'),
                'tuesday' => lang('tuesday'),
                'wednesday' => lang('wednesday'),
                'thursday' => lang('thursday'),
                'friday' => lang('friday'),
                'saturday' => lang('saturday'),
                'sunday' => lang('sunday'),
            ];
            ?>
            <?php foreach ($day_labels as $day => $label): ?>
                <div class="form-check">
                    <input class="form-check-input closed-day" type="checkbox" value="<?= e($day) ?>"
                           id="closed-<?= e($day) ?>" <?= $day === 'sunday' ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="closed-<?= e($day) ?>"><?= e($label) ?></label>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label fw-medium"><?= lang('onboarding_social') ?></label>
        <div class="input-group mb-2">
            <span class="input-group-text"><i class="fab fa-instagram"></i></span>
            <input type="text" id="social_instagram" name="social_instagram" class="form-control"
                   placeholder="Instagram" value="<?= e(vars('social_instagram')) ?>">
        </div>
        <div class="input-group mb-2">
            <span class="input-group-text"><i class="fab fa-telegram"></i></span>
            <input type="text" id="social_telegram" name="social_telegram" class="form-control"
                   placeholder="Telegram" value="<?= e(vars('social_telegram')) ?>">
        </div>
        <div class="input-group mb-2">
            <span class="input-group-text"><i class="fab fa-facebook"></i></span>
            <input type="text" id="social_facebook" name="social_facebook" class="form-control"
                   placeholder="Facebook" value="<?= e(vars('social_facebook')) ?>">
        </div>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-globe"></i></span>
            <input type="text" id="social_website" name="social_website" class="form-control"
                   placeholder="Website" value="<?= e(vars('social_website')) ?>">
        </div>
    </div>

    <p class="small text-muted"><?= lang('onboarding_skip_note') ?></p>

    <div class="d-grid gap-2 mb-3">
        <button type="submit" id="onboarding-submit" class="btn btn-primary">
            <?= lang('onboarding_finish') ?>
        </button>
    </div>
</form>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script src="<?= asset_url('assets/js/pages/onboarding.js') ?>"></script>
<?php end_section('scripts'); ?>
