<?php
/**
 * Local variables.
 *
 * @var string $user_display_name
 */
$hide_brand = plan_allows('white_label') && setting('white_label_enabled') == 1;
?>
<footer id="footer" role="contentinfo" class="d-flex flex-column flex-sm-row justify-content-between align-items-center py-2 px-3 px-lg-4 mt-auto bg-body border-top text-muted" style="font-size: 12px; min-height: 42px;">
    <div class="d-flex align-items-center gap-2 mb-1 mb-sm-0">
        <span>&copy; <?= date('Y') ?> <strong class="text-body"><?= e(setting('company_name', 'BooKi') ?: 'BooKi') ?></strong></span>
        <span class="d-none d-md-inline opacity-50">&bull;</span>
        <span class="d-none d-md-inline"><?= lang('all_rights_reserved') ?: 'Tüm hakları saklıdır.' ?></span>
    </div>

    <div class="d-flex align-items-center gap-3">
        <span id="select-language" class="badge bg-body-secondary text-body border px-2 py-1 user-select-none" style="cursor: pointer;" title="Dil Değiştir">
            <i class="fas fa-globe me-1 text-primary"></i> <?= ucfirst(config('language')) ?>
        </span>

        <?php if (!$hide_brand): ?>
        <span class="small" style="font-size: 11px;">
            Powered by <a href="https://kisoftware.com" target="_blank" rel="noopener" class="text-muted text-decoration-none fw-semibold">BooKi</a>
        </span>
        <?php endif; ?>

        <!-- Hidden anchor for JS compatibility (Account.js update) -->
        <span id="footer-user-display-name" class="d-none"><?= lang('hello') . ', ' . e($user_display_name) ?>!</span>
    </div>
</footer>
