<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php
// Ki Reservation (2026-09-11 fix) - vars() does NOT support dot-notation (CI_Config::item() is a
// flat array lookup), so every vars('settings.xxx') call this file used to make was always
// returning null - dropdown "selected" state, saved-value placeholders and checkboxes never
// reflected the real saved settings. Assign once here and read the array directly instead (same
// convention as dashboard.php).
$settings = vars('settings') ?? [];
?>

<div id="messaging-settings-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div class="col-sm-9">
            <h4 class="border-bottom py-3 mb-3 fw-light">
                SMS ve WhatsApp Ayarları
            </h4>

            <!-- Bildirim Motoru varsayılan kanalı -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="fw-light mb-0">Bildirim Motoru</h5>
                </div>
                <div class="card-body">
                    <p class="form-text text-muted">
                        Randevu onayı/iptali gibi otomatik bildirimler e-postaya ek olarak hangi kanaldan da
                        gönderilsin? Seçtiğiniz kanalın kendi ayarları (aşağıda veya WhatsApp/Telegram
                        sayfalarında) yapılandırılmış olmalı, aksi halde sessizce atlanır.
                    </p>
                    <div class="mb-3">
                        <label class="form-label" for="default-notification-channel">Varsayılan Bildirim Kanalı</label>
                        <select id="default-notification-channel" class="form-select">
                            <option value="email" <?= $settings['default_notification_channel'] === 'email' ? 'selected' : '' ?>>Sadece E-posta</option>
                            <option value="sms" <?= $settings['default_notification_channel'] === 'sms' ? 'selected' : '' ?>>E-posta + SMS</option>
                            <option value="whatsapp" <?= $settings['default_notification_channel'] === 'whatsapp' ? 'selected' : '' ?>>E-posta + WhatsApp</option>
                            <option value="telegram" <?= $settings['default_notification_channel'] === 'telegram' ? 'selected' : '' ?>>E-posta + Telegram</option>
                        </select>
                    </div>
                </div>
            </div>

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
                            <option value="none" <?= $settings['sms_gateway'] === 'none' ? 'selected' : '' ?>>Kapalı</option>
                            <option value="netgsm" <?= $settings['sms_gateway'] === 'netgsm' ? 'selected' : '' ?>>Netgsm</option>
                        </select>
                    </div>

                    <div id="netgsm-credentials" style="display: <?= $settings['sms_gateway'] === 'netgsm' ? 'block' : 'none' ?>;">
                        <div class="mb-3">
                            <label class="form-label" for="netgsm-username">Netgsm Kullanıcı Adı</label>
                            <input type="text" id="netgsm-username" class="form-control"
                                   placeholder="<?= $settings['netgsm_username'] ? 'Kayıtlı' : 'kullanici_adi' ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="netgsm-password">Netgsm Şifre</label>
                            <input type="password" id="netgsm-password" class="form-control"
                                   placeholder="<?= $settings['netgsm_password'] ? 'Kayıtlı' : 'sifre' ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="netgsm-header">SMS Başlığı (Gönderici Adı)</label>
                            <input type="text" id="netgsm-header" class="form-control" maxlength="20"
                                   placeholder="<?= $settings['netgsm_header'] ? e($settings['netgsm_header']) : 'SalonFlora' ?>"
                                   value="<?= e($settings['netgsm_header'] ?: '') ?>">
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" id="sms-notifications-enabled" class="form-check-input"
                               <?= $settings['sms_notifications_enabled'] ? 'checked' : '' ?>>
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
                               placeholder="<?= $settings['whatsapp_phone_number_id'] ? 'Kayıtlı' : '102851261234567' ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="whatsapp-access-token">Erişim Tokeni</label>
                        <input type="password" id="whatsapp-access-token" class="form-control"
                               placeholder="<?= $settings['whatsapp_access_token'] ? 'Kayıtlı' : 'EAABs...' ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="whatsapp-waba-id">WABA Kimliği (WhatsApp Business Account ID)</label>
                        <input type="text" id="whatsapp-waba-id" class="form-control"
                               placeholder="<?= $settings['whatsapp_waba_id'] ? 'Kayıtlı' : '123456789012345' ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="whatsapp-webhook-verify-token">Webhook Doğrulama Tokeni</label>
                        <input type="text" id="whatsapp-webhook-verify-token" class="form-control"
                               placeholder="<?= $settings['whatsapp_webhook_verify_token'] ? 'Kayıtlı' : 'my_secure_token_12345' ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="whatsapp-business-phone-display">İş Telefon Numarası (Gösterilen)</label>
                        <input type="text" id="whatsapp-business-phone-display" class="form-control"
                               placeholder="+90 212 XXX XX XX"
                               value="<?= e($settings['whatsapp_business_phone_display'] ?: '') ?>">
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
                               <?= $settings['whatsapp_notifications_enabled'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="whatsapp-notifications-enabled">
                            WhatsApp bildirimleri aktif (randevu güncellemeleri, iptal, vb.)
                        </label>
                    </div>
                </div>
            </div>

            <!-- E-posta (SMTP) Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="fw-light mb-0">E-posta (SMTP) Ayarları</h5>
                </div>
                <div class="card-body">
                    <p class="form-text text-muted">
                        Eğer kendi SMTP sunucunuz varsa aşağıya bilgilerini girin. Boş bırakırsanız
                        e-postalar platformun kendi sunucusundan, altında küçük bir "Ki Reservation ile
                        gönderildi" tanıtım notu ile gönderilir.
                    </p>

                    <div class="mb-3">
                        <label class="form-label" for="smtp-host">SMTP Sunucusu Adresi</label>
                        <input type="text" id="smtp-host" class="form-control"
                               placeholder="smtp.example.com"
                               value="<?= e($settings['smtp_host'] ?: '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="smtp-port">SMTP Portu</label>
                        <input type="number" id="smtp-port" class="form-control"
                               placeholder="587"
                               value="<?= (int) ($settings['smtp_port'] ?: '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="smtp-crypto">Şifreleme Türü</label>
                        <select id="smtp-crypto" class="form-select">
                            <option value="">Seçiniz</option>
                            <option value="tls" <?= $settings['smtp_crypto'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= $settings['smtp_crypto'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                            <option value="none" <?= $settings['smtp_crypto'] === 'none' ? 'selected' : '' ?>>Yok</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="smtp-user">SMTP Kullanıcı Adı</label>
                        <input type="text" id="smtp-user" class="form-control"
                               placeholder="<?= $settings['smtp_user'] ? 'Kayıtlı' : 'kullanici@example.com' ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="smtp-pass">SMTP Şifre</label>
                        <input type="password" id="smtp-pass" class="form-control"
                               placeholder="<?= $settings['smtp_pass'] ? 'Kayıtlı' : 'sifre' ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="smtp-from-name">E-posta Gönderici Adı</label>
                        <input type="text" id="smtp-from-name" class="form-control"
                               placeholder="Örn: Salon Flora Randevu"
                               value="<?= e($settings['smtp_from_name'] ?: '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="smtp-from-address">E-posta Gönderici Adresi</label>
                        <input type="email" id="smtp-from-address" class="form-control"
                               placeholder="no-reply@example.com"
                               value="<?= e($settings['smtp_from_address'] ?: '') ?>">
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
        smtp_host: document.getElementById('smtp-host').value || null,
        smtp_port: document.getElementById('smtp-port').value || null,
        smtp_crypto: document.getElementById('smtp-crypto').value || null,
        smtp_user: document.getElementById('smtp-user').value || null,
        smtp_pass: document.getElementById('smtp-pass').value || null,
        smtp_from_name: document.getElementById('smtp-from-name').value || null,
        smtp_from_address: document.getElementById('smtp-from-address').value || null,
        default_notification_channel: document.getElementById('default-notification-channel').value,
        // Ki Reservation (2026-09-11 fix) - config.php has csrf_protection=true, so a POST without
        // this field was always rejected before reaching the controller (matches the http_client.js
        // pattern used everywhere else in the app, e.g. account_http_client.js).
        csrf_token: '<?= e($this->security->get_csrf_hash()) ?>',
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

<?php end_section('content'); ?>
