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

            <!-- ============ Mode selector ============ -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="fw-light mb-0">Gönderim Modu</h5>
                </div>
                <div class="card-body">
                    <p class="form-text text-muted">
                        Ki Reservation tek bir tarayıcıda iki WhatsApp gönderim yöntemi sunar. Değişiklik
                        randevu bildirimlerinin hangi hat üzerinden gideceğini anında değiştirir.
                    </p>

                    <div class="form-check mb-2">
                        <input class="form-check-input wa-mode-radio" type="radio" name="wa_mode" id="wa-mode-official"
                               value="official" <?= vars('whatsapp_mode') === 'official' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="wa-mode-official">
                            <strong>Resmi</strong> - Meta WhatsApp Business Cloud API
                        </label>
                        <small class="form-text text-muted d-block">
                            Marka onaylı, resmi API. Randevu hatırlatmaları gibi bildirimler için
                            onaylı şablonlar gerekir. Meta Business Manager kaydı zorunludur.
                        </small>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input wa-mode-radio" type="radio" name="wa_mode" id="wa-mode-unofficial"
                               value="unofficial" <?= vars('whatsapp_mode') === 'unofficial' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="wa-mode-unofficial">
                            <strong>Resmi olmayan</strong> - QR ile cihaz eşleştirme (köprü)
                        </label>
                        <small class="form-text text-muted d-block">
                            Normal WhatsApp hesabıyla telefon QR kodunu okutarak bağlanır. Meta politikası
                            gereği bilgilendirilmiş onay gerekir.
                        </small>
                    </div>

                    <div id="wa-consent-wrap" class="alert alert-danger <?= vars('whatsapp_mode') === 'unofficial' ? '' : 'd-none' ?>">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="wa-informal-consent"
                                <?= !empty(vars('unofficial_consent_at')) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="wa-informal-consent">
                                WhatsApp kullanım koşullarını bilerek, resmi olmayan yöntemle (QR/cihaz
                                eşleştirme) bağlantı yapmayı kabul ediyorum. Hesabın kısıtlanma riskiyle
                                ilgili bilgilendirildim.
                            </label>
                        </div>
                        <?php if (!empty(vars('unofficial_consent_at'))): ?>
                            <small class="text-muted d-block mt-2">
                                Onay tarihi: <?= e(date('Y-m-d H:i', strtotime(vars('unofficial_consent_at')))) ?>
                            </small>
                        <?php endif; ?>
                    </div>

                    <button type="button" id="wa-save-mode" class="btn btn-primary">
                        <i class="fas fa-save"></i> Modu Kaydet
                    </button>
                </div>
            </div>

            <!-- ============ Official mode wizard ============ -->
            <div id="wa-official-panel" class="card mb-4 <?= vars('whatsapp_mode') === 'official' ? '' : 'd-none' ?>">
                <div class="card-header">
                    <h5 class="fw-light mb-0">Resmi: WhatsApp Business Cloud API</h5>
                </div>
                <div class="card-body">
                    <p class="form-text text-muted">
                        Meta Business Manager'da WhatsApp Business Account (WABA) oluşturup kimlik
                        bilgilerini SMS ve WhatsApp Ayarları sayfasında girin, ardından aşağıdaki
                        adımları tamamlayın.
                    </p>

                    <div id="wa-official-status">
                        <?php if (vars('whatsapp_configured')): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <strong>Yapılandırıldı</strong> - iş telefon numarası:
                                <?= e(vars('whatsapp_business_phone_display') ?: 'Tanımlanmamış') ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Yapılandırılmamış</strong> - SMS ve WhatsApp Ayarları sayfasında
                                Phone Number ID / Access Token girin.
                            </div>
                        <?php endif; ?>
                    </div>

                    <h6 class="mt-3 mb-2">
                        <i class="fas fa-plug"></i> Adım 1 - Bağlantıyı doğrula
                    </h6>
                    <button type="button" id="wa-official-check" class="btn btn-outline-primary mb-2"
                            data-busy-label="Kontrol ediliyor...">
                        <i class="fas fa-sync-alt"></i> Bağlantıyı Doğrula
                    </button>
                    <div id="wa-official-check-result"></div>

                    <h6 class="mt-4 mb-2"><i class="fas fa-link"></i> Adım 2 - Webhook</h6>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" readonly value="<?= e(vars('webhook_url')) ?>">
                        <button class="btn btn-outline-secondary wa-copy" type="button">Kopyala</button>
                    </div>
                    <small class="form-text text-muted d-block">
                        Bu URL'yi Meta Business Manager'da App → Konfigürasyon → Webhooks kısmına girin.
                    </small>

                    <?php if (vars('webhook_verify_token_required')): ?>
                        <div class="alert alert-info mt-2 mb-0">
                            <strong>Webhook Doğrulama Tokeni:</strong> Yapılandırıldı.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-2 mb-0">
                            <strong>Webhook Doğrulama Tokeni:</strong> SMS ve WhatsApp Ayarları sayfasında
                            ayarlayın.
                        </div>
                    <?php endif; ?>

                    <h6 class="mt-4 mb-2"><i class="fas fa-paper-plane"></i> Adım 3 - Test mesajı</h6>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" id="wa-test-phone"
                               placeholder="90XXXXXXXXXX (uluslararası biçim)">
                        <button type="button" id="wa-send-test" class="btn btn-outline-primary"
                                data-busy-label="Gönderiliyor...">
                            <i class="fas fa-paper-plane"></i> Test Gönder
                        </button>
                    </div>
                    <div id="wa-test-result"></div>
                </div>
            </div>

            <!-- ============ Unofficial mode panel ============ -->
            <div id="wa-unofficial-panel" class="card mb-4 <?= vars('whatsapp_mode') === 'unofficial' ? '' : 'd-none' ?>">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-light mb-0">Resmi olmayan: QR / Cihaz Eşleştirme</h5>
                    <span id="wa-unofficial-badge" class="badge
                        <?= vars('unofficial_status') === 'connected' ? 'bg-success' : (vars('unofficial_status') === 'error' ? 'bg-danger' : 'bg-secondary') ?>">
                        <?= e(ucfirst(vars('unofficial_status') ?: 'disconnected')) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Bu yöntem resmi olmayan bir yöntemdir; WhatsApp tarafından desteklenmez ve hesabınızın
                        kısıtlanma riski taşır. Yalnızca bilgilendirilmiş onay ile etkinleştirin.
                    </div>

                    <?php if (empty(vars('unofficial_consent_at'))): ?>
                        <div class="alert alert-danger">
                            Bilgilendirilmiş onay verilmedi. Önce üstteki <strong>Gönderim Modu</strong>
                            kartında onay kutusunu işaretleyip modu kaydedin.
                        </div>
                    <?php endif; ?>

                    <h6 class="mt-3 mb-2"><i class="fas fa-network-wired"></i> Köprü ayarları</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small" for="wa-bridge-url">Köprü REST adresi</label>
                            <input type="text" class="form-control" id="wa-bridge-url"
                                   placeholder="https://wa-bridge.example.com"
                                   value="<?= e(vars('bridge_url') ?: '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small" for="wa-bridge-secret">Paylaşılan gizli anahtar</label>
                            <input type="password" class="form-control" id="wa-bridge-secret"
                                   placeholder="<?= vars('bridge_secret_set') ? '•••••••• (boş bırakılırsa korunur)' : 'Yeni anahtar' ?>">
                        </div>
                    </div>
                    <button type="button" id="wa-save-bridge" class="btn btn-outline-secondary mb-3"
                            data-busy-label="Kaydediliyor...">
                        <i class="fas fa-save"></i> Köprü Ayarını Kaydet
                    </button>

                    <h6 class="mt-3 mb-2"><i class="fas fa-qrcode"></i> Eşleştirme</h6>
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" id="wa-qr-start" class="btn btn-primary" data-busy-label="Başlatılıyor...">
                            <i class="fas fa-qrcode"></i> QR Başlat
                        </button>
                        <button type="button" id="wa-qr-status" class="btn btn-outline-primary" data-busy-label="Yoklanıyor...">
                            <i class="fas fa-sync-alt"></i> Durumu Yenile
                        </button>
                        <button type="button" id="wa-qr-logout" class="btn btn-outline-danger" data-busy-label="Çıkılıyor...">
                            <i class="fas fa-sign-out-alt"></i> Bağlantıyı Kapat
                        </button>
                    </div>

                    <div id="wa-qr-result" class="text-center p-3 border rounded bg-light">
                        <span class="text-muted">QR kodunu görüntülemek için "QR Başlat"a basın.</span>
                    </div>
                </div>
            </div>

            <!-- ============ Messages ============ -->
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

<?php section('scripts'); ?>
<script src="<?= asset_url('assets/js/pages/whatsapp.js') ?>"></script>
<?php end_section('scripts'); ?>

<?php end_section(); ?>