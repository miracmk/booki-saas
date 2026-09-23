<?php
/**
 * Local variables.
 *
 * @var bool $display_login_button
 * @var string $legal_notice_url
 * @var string $imprint_url
 */
$hide_brand = plan_allows('white_label') && setting('white_label_enabled') == 1;
$default_name = $hide_brand ? '' : 'BooKi';
$company_name = setting('company_name', $default_name) ?: $default_name;
?>

<footer id="frame-footer" role="contentinfo" class="p-3 text-center border-top">
    <div class="d-flex flex-wrap align-items-center justify-content-center mb-2" style="display:flex !important;gap:1rem;">
        <img src="<?= asset_url('assets/img/iyzico/footer_iyzico_ile_ode_color.svg') ?>" alt="iyzico ile Öde" style="height:28px;width:auto;" loading="lazy">
        <img src="<?= asset_url('assets/img/iyzico/visa.svg') ?>" alt="Visa" style="height:22px;width:auto;" loading="lazy">
        <img src="<?= asset_url('assets/img/iyzico/mastercard.svg') ?>" alt="Mastercard" style="height:24px;width:auto;" loading="lazy">
    </div>
    <small class="d-block d-md-flex align-items-center">
        <span class="footer-powered-by small d-block w-100 w-md-50 text-center text-md-start p-1 pe-md-0">
            &copy; <?= date('Y') ?> <?= e($company_name) ?>

            <?php if (!empty($legal_notice_url)): ?>
                <span>|</span>
                <a href="<?= e($legal_notice_url) ?>" target="_blank"><?= lang('legal_notice') ?></a>
            <?php endif; ?>

            <?php if (!empty($imprint_url)): ?>
                <span>|</span>
                <a href="<?= e($imprint_url) ?>" target="_blank"><?= lang('imprint') ?></a>
            <?php endif; ?>

            <span>|</span>
            <a href="<?= base_url('mesafeli-satis') ?>" target="_blank">Mesafeli Satış Sözleşmesi</a>
            <span>|</span>
            <a href="<?= base_url('teslimat-iade') ?>" target="_blank">Teslimat &amp; İade</a>
        </span>

        <span class="footer-options d-block w-100 w-md-50 text-center text-md-end">
            <span id="select-language" class="badge bg-secondary d-inline-flex align-items-center justify-content-center my-1 my-md-0 px-3 py-2" style="min-width: 100px; min-height: 38px;">
                <i class="fas fa-language me-2"></i>
                <?= ucfirst(config('language')) ?>
            </span>
    
            <?php if ($display_login_button): ?>
                <a class="backend-link badge bg-primary text-decoration-none px-3 py-2 d-inline-flex align-items-center justify-content-center my-1 my-md-0"
                   href="<?= session('user_id') ? site_url('calendar') : site_url('login') ?>"
                   style="min-width: 120px; min-height: 38px;">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    <?= session('user_id') ? lang('backend_section') : lang('login') ?>
                </a>
            <?php endif; ?>
        </span>
    </small>
</footer>
