<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="instagram-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div class="col-sm-9">
            <h4 class="border-bottom py-3 mb-3 fw-light">
                <i class="fab fa-instagram me-2 text-danger"></i>
                Instagram Entegrasyonu & AI Yanıtlayıcı
            </h4>

            <div class="alert alert-info border-0 shadow-sm mb-4">
                <i class="fas fa-info-circle me-1"></i>
                <strong>Meta Graph API Bilgisi:</strong> Canlı Instagram Direct mesajlaşması Meta App Review tarafından onaylanmış bir Meta Business App (<code>instagram_manage_messages</code> izni) gerektirir. Geliştirme veya test aşamasında test hesaplarıyla hemen test edilebilir.
            </div>

            <!-- API Configuration Card -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="fw-light mb-0">Meta Graph API Yapılandırması</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="ig-webhook-url">Webhook Adresi (Meta App Dashboard'a yapıştırın)</label>
                        <div class="input-group">
                            <input type="text" id="ig-webhook-url" class="form-control" readonly value="<?= e(vars('webhook_url')) ?>">
                            <button class="btn btn-outline-secondary" type="button" id="copy-webhook-btn">
                                <i class="fas fa-copy me-1"></i> Kopyala
                            </button>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="ig-account-id">Instagram Business / Sayfa ID</label>
                            <input type="text" id="ig-account-id" class="form-control"
                                   placeholder="Örn: 17841400000000000"
                                   value="<?= e(vars('instagram_account_id')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ig-verify-token">Webhook Doğrulama Belirteci (Verify Token)</label>
                            <input type="text" id="ig-verify-token" class="form-control"
                                   placeholder="Meta webhook doğrulama token'ı"
                                   value="<?= vars('webhook_verify_token_set') ? '********' : '' ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="ig-access-token">Page / System User Access Token</label>
                        <input type="password" id="ig-access-token" class="form-control"
                               placeholder="<?= vars('instagram_configured') ? 'Kayıtlı (değiştirmek için yazın)' : 'EAAB...' ?>">
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="ig-notifications-enabled"
                            <?= vars('instagram_notifications_enabled') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="ig-notifications-enabled">
                            Instagram Bildirimleri Açık (Randevu bildirimleri Direct üzerinden gönderilsin)
                        </label>
                    </div>

                    <hr class="my-4">

                    <!-- AI Auto Reply Section -->
                    <h5 class="fw-light mb-2"><i class="fas fa-robot text-primary me-2"></i>AI Asistan Otomatik Yanıtlayıcı</h5>
                    <p class="text-muted small">
                        Müşterileriniz Instagram Direct üzerinden mesaj attığında BooKi AI Asistanı işletme ve randevu bilgileri doğrultusunda otomatik yanıt verir.
                        <strong>Güvenlik Notu:</strong> Müşteri mesajları veritabanında asla doğrudan randevu silme/değiştirme yapamaz; değişiklik talepleri yönetici onay kuyruğuna alınır.
                    </p>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="ai-reply-instagram-enabled"
                            <?= vars('ai_reply_instagram_enabled') ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold" for="ai-reply-instagram-enabled">
                            Instagram Gelen Mesajlara Otomatik AI Yanıtı Ver
                        </label>
                    </div>

                    <button class="btn btn-primary" id="save-instagram-settings">
                        <i class="fas fa-save me-1"></i> Ayarları Kaydet
                    </button>
                </div>
            </div>

            <!-- Message History Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="fw-light mb-0"><i class="fas fa-comments me-2"></i>Mesaj Geçmişi</h5>
                    <span class="badge bg-secondary"><?= count(vars('messages') ?? []) ?> Mesaj</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="ig-messages-table">
                        <thead class="table-light">
                            <tr>
                                <th>Yön</th>
                                <th>Instagram Kullanıcı</th>
                                <th>Müşteri</th>
                                <th>Mesaj</th>
                                <th>Tarih</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $msgs = vars('messages') ?? []; ?>
                            <?php if (empty($msgs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        Henüz bir Instagram mesajı bulunmuyor.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($msgs as $msg): ?>
                                    <tr>
                                        <td>
                                            <?php if ($msg['direction'] === 'in'): ?>
                                                <span class="badge bg-info"><i class="fas fa-arrow-down me-1"></i> Gelen</span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><i class="fas fa-arrow-up me-1"></i> Giden</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><code><?= e($msg['instagram_user_id']) ?></code></td>
                                        <td>
                                            <?php if (!empty($msg['first_name'])): ?>
                                                <?= e($msg['first_name'] . ' ' . $msg['last_name']) ?>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="max-width: 300px;" class="text-truncate"><?= e($msg['message']) ?></td>
                                        <td class="small text-muted"><?= e($msg['created_at']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary ig-reply-btn"
                                                    data-ig-user="<?= e($msg['instagram_user_id']) ?>"
                                                    data-user-id="<?= e($msg['id_users'] ?? '') ?>">
                                                <i class="fas fa-reply me-1"></i> Yanıtla
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reply Modal -->
<div class="modal fade" id="ig-reply-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Instagram Yanıtı Gönder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Alıcı (Instagram ID)</label>
                    <input type="text" class="form-control" id="modal-ig-user-id" readonly>
                    <input type="hidden" id="modal-user-id">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mesaj</label>
                    <textarea class="form-control" id="modal-reply-text" rows="4" placeholder="Mesajınızı yazın..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="modal-send-reply-btn">Gönder</button>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script src="<?= asset_url('assets/js/pages/instagram.js') ?>"></script>
<?php end_section('scripts'); ?>
