<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="messaging-settings-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div class="col-sm-9">
            <h4 class="border-bottom py-3 mb-3 fw-light">
                SMS ve WhatsApp Ayarları
            </h4>

            <!-- SMS (Netgsm) Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="fw-light mb-0">SMS Bildirimleri (Netgsm)</h5>
                </div>
                <div class="card-body">
                    <p class="form-text text-muted">
                        Randevu bildirimleri ve müşteri mesajları için SMS göndermek üzere Netgsm
                        açacağınız. Henüz açmadıysanız bu bölümü boş bırakabilirsiniz.
                    </p>

                    <div class="mb-3">
                        <label class="form-label" for="sms-gateway">SMS Sağlayıcısı</label>
                        <select id="sms-gateway" class="form-select">
                            <option value="none" <?= vars('settings.sms_gateway') === 'none' ? 'selected' : '' ?>>Kapalı</option>
                            <option value="netgsm" <?= vars('settings.sms_gateway') === 'netgsm' ? 'selected' : '' ?>>Netgsm</option>
                        </select>
                    </div>

                    <div id="netgsm-credentials" style="display: <?= vars('settings.sms_gateway') === 'netgsm' ? 'block' : 'none' ?>;">
                        <div class="mb-3">
                            <label class="form-label" for="netgsm-username">Netgsm Kullanıcı Adı</label>
                            <input type="text" id="netgsm-username" class="form-control"
                                   placeholder="<?= vars('settings.netgsm_username') ? 'Kayıtlı' : 'kullanici_adi' ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="netgsm-password">Netgsm Şifre</label>
                            <input type="password" id="netgsm-password" class="form-control"
                                   placeholder="<?= vars('settings.netgsm_password') ? 'Kayıtlı' : 'sifre' ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="netgsm-header">SMS Başlığı (Gönderici Adı)</label>
                            <input type="text" id="netgsm-header" class="form-control" maxlength="20"
                                   placeholder="<?= vars('settings.netgsm_header') ? e(vars('settings.netgsm_header')) : 'SalonFlora' ?>"
                                   value="<?= e(vars('settings.netgsm_header') ?: '') ?>">
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" id="sms-notifications-enabled" class="form-check-input"
                               <?= vars('settings.sms_notifications_enabled') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="sms-notifications-enabled">
                            SMS bildirimleri aktif (randevu güncellemeleri, iptal, vb.)
                        </label>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="fw-light mb-0">WhatsApp Business Cloud API</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Kurulum gerekli:</strong> Meta Business hesabınızdan aldığınız telefon numarası
                        kimliği, erişim tokeni ve diğer bilgileri buraya girin. Henüz girilmediyse WhatsApp
                        bildirimleri gönderilemez.
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="whatsapp-phone-number-id">Telefon Numarası Kimliği</label>
                        <input type="text" id="whatsapp-phone-number-id" class="form-control"
                               placeholder="<?= vars('settings.whatsapp_phone_number_id') ? 'Kayıtlı' : '102851261234567' ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="whatsapp-access-token">Erişim Tokeni</label>
                        <input type="password" id="whatsapp-access-token" class="form-control"
                               placeholder="<?= vars('settings.whatsapp_access_token') ? 'Kayıtlı' : 'EAABs...' ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="whatsapp-waba-id">WABA Kimliği (WhatsApp Business Account ID)</label>
                        <input type="text" id="whatsapp-waba-id" class="form-control"
                               placeholder="<?= vars('settings.whatsapp_waba_id') ? 'Kayıtlı' : '123456789012345' ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="whatsapp-webhook-verify-token">Webhook Doğrulama Tokeni</label>
                        <input type="text" id="whatsapp-webhook-verify-token" class="form-control"
                               placeholder="<?= vars('settings.whatsapp_webhook_verify_token') ? 'Kayıtlı' : 'my_secure_token_12345' ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="whatsapp-business-phone-display">İş Telefon Numarası (Gösterilen)</label>
                        <input type="text" id="whatsapp-business-phone-display" class="form-control"
                               placeholder="+90 212 XXX XX XX"
                               value="<?= e(vars('settings.whatsapp_business_phone_display') ?: '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Webhook URL</label>
                        <div class="input-group">
                            <input type="text" class="form-control" readonly value="<?= e(vars('webhook_url')) ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(this)">
                                Kopyala
                            </button>
                        </div>
                        <small class="form-text text-muted mt-2">
                            Bu URL'yi Meta Business Manager'da webhook ayarlarına girin.
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" id="whatsapp-notifications-enabled" class="form-check-input"
                               <?= vars('settings.whatsapp_notifications_enabled') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="whatsapp-notifications-enabled">
                            WhatsApp bildirimleri aktif (randevu güncellemeleri, iptal, vb.)
                        </label>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="mb-4">
                <button id="save-messaging-settings" class="btn btn-primary">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle Netgsm credentials visibility based on SMS gateway selection
document.getElementById('sms-gateway').addEventListener('change', function() {
    const netgsmSection = document.getElementById('netgsm-credentials');
    netgsmSection.style.display = this.value === 'netgsm' ? 'block' : 'none';
});

// Copy to clipboard utility
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

// Save settings
document.getElementById('save-messaging-settings').addEventListener('click', function() {
    const data = {
        sms_gateway: document.getElementById('sms-gateway').value,
        netgsm_username: document.getElementById('netgsm-username').value || null,
        netgsm_password: document.getElementById('netgsm-password').value || null,
        netgsm_header: document.getElementById('netgsm-header').value || null,
        sms_notifications_enabled: document.getElementById('sms-notifications-enabled').checked,
        whatsapp_phone_number_id: document.getElementById('whatsapp-phone-number-id').value || null,
        whatsapp_access_token: document.getElementById('whatsapp-access-token').value || null,
        whatsapp_waba_id: document.getElementById('whatsapp-waba-id').value || null,
        whatsapp_webhook_verify_token: document.getElementById('whatsapp-webhook-verify-token').value || null,
        whatsapp_business_phone_display: document.getElementById('whatsapp-business-phone-display').value || null,
        whatsapp_notifications_enabled: document.getElementById('whatsapp-notifications-enabled').checked,
    };

    $.post('<?= base_url('messaging_settings/save_settings') ?>', data, function(response) {
        if (response.success) {
            alert('Ayarlar kaydedildi.');
            location.reload();
        }
    }).fail(function(xhr) {
        alert('Hata: ' + (xhr.responseJSON?.error || 'Bilinmeyen hata'));
    });
});
</script>

<?php end_section(); ?>
