<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="integrations-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div id="integrations" class="col-sm-9">
            <h4 class="border-bottom py-3 mb-3 fw-light">
                <?= lang('integrations') ?>
            </h4>

            <p class="form-text text-muted mb-4">
                <?= lang('integrations_info') ?>
            </p>

            <div class="row">
                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                <?= lang('webhooks') ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    <?= lang('webhooks_info') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('webhooks') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <?php // Salon Flora customization - unified "Google Entegrasyonları" hub (Calendar, Analytics,
                // and Contacts/Drive/Sheets/Docs/Tasks connections) replaces the separate Google Analytics /
                // Google Calendar cards that used to be scattered here. ?>
                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                Google Entegrasyonları
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    Google Takvim, Google Analytics ve Kişiler/Drive/E-Tablolar/Dokümanlar/
                                    Görevler için şirket veya kişisel hesap bağlantıları.
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('google_integrations') ?>"
                               class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                <?= lang('matomo_analytics') ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    <?= lang('matomo_analytics_info') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('matomo_analytics_settings') ?>"
                               class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                <?= lang('api') ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    <?= lang('api_info') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('api_settings') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                <?= lang('ldap') ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    <?= lang('ldap_info') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('ldap_settings') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                <?= lang('jitsi') ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    <?= lang('jitsi_info') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('jitsi_settings') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                <?= lang('altcha') ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    <?= lang('altcha_info') ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('altcha_settings') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <?php // Salon Flora customization - native Telegram integration. ?>
                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                Telegram
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    Randevu bildirimlerini personele (ve isteğe bağlı müşterilere) Telegram
                                    üzerinden de gönderin; müşterilerin bota yazdığı mesajları görüp buradan
                                    yanıtlayın.
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('telegram') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <?php // BooKi (2026-09-11 fix) - iyzico/PayTR/Stripe credentials, same
                // "built but unlinked" bug as the two cards below. ?>
                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                Ödeme
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    iyzico, PayTR ve Stripe ödeme sağlayıcı kimlik bilgilerinizi buradan
                                    yönetin.
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('payment_settings') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <?php // BooKi (2026-09-11 fix) - was built (Whatsapp.php + Messaging_settings.php,
                // dual-mode: resmi Meta Cloud API + QR bridge) but never linked from anywhere in the UI. ?>
                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                WhatsApp
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    Randevu bildirimlerini WhatsApp üzerinden gönderin - resmi Meta Business Cloud
                                    API veya QR kod ile cihaz eşleştirme (bilgilendirilmiş onay gerektirir).
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('whatsapp') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <?php // BooKi (2026-09-11 fix) - SMS (Netgsm) + SMTP credentials, same "built but
                // unlinked" bug as the WhatsApp card above. ?>
                <div class="col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">
                                SMS ve E-posta (SMTP)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 integration-info">
                                <small>
                                    Netgsm üzerinden SMS bildirimleri ve kendi SMTP sunucunuzla e-posta gönderimi
                                    için kimlik bilgilerini yapılandırın.
                                </small>
                            </div>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('messaging_settings') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i>
                                <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

