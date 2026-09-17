<?php
/**
 * Local variables.
 *
 * @var string $user_display_name
 */
?>
<div id="footer" class="d-lg-flex justify-content-lg-start align-items-lg-center p-2 text-center text-lg-left mt-auto bg-body border-top" style="font-size: 11px;">
    <div class="mb-3 me-lg-5 mb-lg-0">
        &copy; <?= date('Y') ?> <?= e(setting('company_name', 'BooKi') ?: 'BooKi') ?>
    </div>

    <div class="mb-3 me-lg-5 mb-lg-0">
        <?= lang('licensed_under') ?>
        <a href="https://github.com/miracmk/ki-reservation/blob/main/LICENSE" target="_blank">
            Ki Software License
        </a>
    </div>

    <div class="mb-3 me-lg-5 mb-lg-0">
        <span id="select-language" class="badge bg-dark">
            <i class="fas fa-language me-2"></i>
        	<?= ucfirst(config('language')) ?>
        </span>
    </div>

    <div class="mb-3 me-lg-5 mb-lg-0">
        <a href="<?= site_url('appointments') ?>">
            <?= lang('go_to_booking_page') ?>
        </a>
    </div>

    <div class="ms-lg-auto">
        <strong id="footer-user-display-name">
            <?= lang('hello') . ', ' . e($user_display_name) ?>!
        </strong>
    </div>

    <div class="text-muted small">
        <a href="https://kisoftware.com" target="_blank" class="text-muted">Powered by BooKi (Ki Software License)</a>
    </div>
</div>


