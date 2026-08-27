<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="whatsapp-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div class="col-sm-9">
            <h4 class="border-bottom py-3 mb-3 fw-light">
                WhatsApp Entegrasyonu
            </h4>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="fw-light mb-0">WhatsApp Business Cloud API Kurulumu</h5>
                </div>
                <div class="card-body">
                    <p class="form-text text-muted">
                        Meta Business Manager'da WhatsApp Business Account (WABA) oluşturup, gerekli kimlik
                        bilgilerini SMS ve WhatsApp Ayarları sayfasında girin. Aşağıda webhook ayarlarını
                        görebilirsiniz.
                    </p>

                    <?php if (vars('whatsapp_configured')): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong>Yapılandırıldı</strong> - WhatsApp iş telefon numarası:
                            <?= e(vars('whatsapp_business_phone_display') ?: 'Tanımlanmamış') ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Yapılandırılmamış</strong> - SMS ve WhatsApp Ayarları sayfasında kimlik
                            bilgilerini girin.
                        </div>
                    <?php endif; ?>

                    <h6 class="mt-4 mb-2">Webhook URL</h6>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" readonly value="<?= e(vars('webhook_url')) ?>">
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(this)">
                            Kopyala
                        </button>
                    </div>
                    <small class="form-text text-muted d-block mb-3">
                        Bu URL'yi Meta Business Manager'da App → Konfigürasyon → Webhooks kısmına girin.
                    </small>

                    <?php if (vars('webhook_verify_token_required')): ?>
                        <div class="alert alert-info">
                            <strong>Webhook Doğrulama Tokeni:</strong> Yapılandırıldı.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <strong>Webhook Doğrulama Tokeni:</strong> SMS ve WhatsApp Ayarları sayfasında
                            ayarlayın.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="fw-light mb-0">Gelen Mesajlar</h5>
                </div>
                <div class="card-body">
                    <?php if (empty(vars('messages'))): ?>
                        <p class="text-muted">Henüz mesaj yok.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                <tr>
                                    <th>Tarih/Saat</th>
                                    <th>Gönderici</th>
                                    <th>WhatsApp ID</th>
                                    <th>Mesaj</th>
                                    <th>Yön</th>
                                    <th>Durum</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach (vars('messages') as $msg): ?>
                                    <tr>
                                        <td><?= e(date('Y-m-d H:i', strtotime($msg['created_at']))) ?></td>
                                        <td>
                                            <?php if ($msg['first_name'] || $msg['last_name']): ?>
                                                <?= e(trim(($msg['first_name'] ?: '') . ' ' . ($msg['last_name'] ?: ''))) ?>
                                            <?php else: ?>
                                                <span class="text-muted">(tanımsız)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?= e(substr($msg['wa_id'], 0, 20)) ?></small>
                                        </td>
                                        <td>
                                            <small><?= e(substr($msg['message'], 0, 50)) ?><?= strlen($msg['message']) > 50 ? '...' : '' ?></small>
                                        </td>
                                        <td>
                                            <?php if ($msg['direction'] === 'in'): ?>
                                                <span class="badge bg-success">Gelen</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">Giden</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($msg['status']): ?>
                                                <small><?= e(ucfirst($msg['status'])) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(button) {
    const input = button.previousElementSibling;
    input.select();
    document.execCommand('copy');
    const originalText = button.textContent;
    button.textContent = 'Kopyalandı!';
    setTimeout(() => {
        button.textContent = originalText;
    }, 2000);
}
</script>

<?php end_section(); ?>
