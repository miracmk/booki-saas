<?php extend('layouts/message_layout'); ?>

<?php section('content'); ?>

<div class="d-flex align-items-center justify-content-center">
    <div class="text-center py-4 px-3 w-100">
        <?php if (vars('payment_status') === 'succeeded'): ?>
            <div class="d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mx-auto mb-4" style="width: 90px; height: 90px;">
                <i class="fas fa-check-circle fa-3x text-success"></i>
            </div>
            <h3 class="text-success fw-bold mb-3">Ödeme Başarıyla Tamamlandı</h3>
            <p class="fs-5 text-muted mb-2">
                İşleminiz onaylandı ve randevu kaydınız güncellendi.
            </p>
        <?php else: ?>
            <div class="d-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10 mx-auto mb-4" style="width: 90px; height: 90px;">
                <i class="fas fa-times-circle fa-3x text-danger"></i>
            </div>
            <h3 class="text-danger fw-bold mb-3">Ödeme Gerçekleştirilemedi</h3>
            <p class="fs-5 text-muted mb-2">
                <?= e(vars('payment_error_message') ?: 'Ödeme sağlayıcısı veya banka tarafından işlem reddedildi.') ?>
            </p>
        <?php endif; ?>

        <?php if (vars('transaction_ref')): ?>
            <div class="my-3 p-3 bg-light rounded text-start" style="font-size: 0.9rem;">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">İşlem / Referans No:</span>
                    <strong><?= e(vars('transaction_ref')) ?></strong>
                </div>
                <?php if (vars('amount')): ?>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Tutar:</span>
                        <strong><?= number_format((float)vars('amount'), 2, ',', '.') ?> <?= e(vars('currency') ?: 'TRY') ?></strong>
                    </div>
                <?php endif; ?>
                <?php if (vars('gateway')): ?>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Ödeme Yöntemi:</span>
                        <span class="badge bg-secondary text-uppercase"><?= e(vars('gateway')) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center mt-4">
            <?php if (vars('redirect_url')): ?>
                <a href="<?= e(vars('redirect_url')) ?>" class="btn btn-primary px-4 py-2">
                    <i class="fas fa-arrow-right me-2"></i> Devam Et
                </a>
            <?php else: ?>
                <a href="<?= site_url() ?>" class="btn btn-primary px-4 py-2">
                    <i class="fas fa-home me-2"></i> Ana Sayfaya Dön
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php end_section('content'); ?>
