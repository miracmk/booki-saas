<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php
/**
 * @var bool $multi_tenant
 * @var array|null $domain_state
 * @var string $canonical_target
 */
$state = $domain_state ?? [];
$status = $state['custom_domain_status'] ?? 'none';
?>

<div class="container backend-page py-4" id="custom-domain-page"
     data-status="<?= e($status) ?>"
     data-canonical-target="<?= e($canonical_target) ?>"
     data-pending-domain="<?= e($state['custom_domain_pending'] ?? '') ?>"
     data-txt-value="<?= e($state['custom_domain_verification_token'] ? 'ki-verify=' . $state['custom_domain_verification_token'] : '') ?>"
     data-last-error="<?= e($state['custom_domain_last_error'] ?? '') ?>"
     data-active-domain="<?= e($state['custom_domain'] ?? '') ?>"
     data-a-target="<?= e($canonical_ip ?? '') ?>">

    <h4 class="mb-1 fw-light">Özel Alan Adı</h4>
    <p class="text-muted mb-4">Ki Reservation hesabınıza kendi alan adınızdan (örn. rezervasyon.firmaniz.com) erişilmesini sağlayın.</p>

    <?php if (!$multi_tenant): ?>
        <div class="alert alert-info">Bu özellik yalnızca çoklu kiracılı bulut dağıtımında kullanılabilir.</div>
    <?php else: ?>

        <div class="row g-3">
            <div class="col-12 col-lg-7">

                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0">Durum</h6>
                        <span id="cd-status-badge" class="badge"></span>
                    </div>
                    <div class="card-body">
                        <div id="cd-status-body"></div>
                    </div>
                </div>

                <div class="card" id="cd-request-card">
                    <div class="card-header"><h6 class="fw-bold mb-0">Yeni Alan Adı Talep Et</h6></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="cd-domain-input">Alan Adı</label>
                            <input type="text" id="cd-domain-input" class="form-control" placeholder="rezervasyon.firmaniz.com"
                                   value="<?= e($state['custom_domain_pending'] ?? '') ?>">
                            <div class="form-text">www olmadan, tam alan adını girin. Alt alan adı da olabilir (rezervasyon.firmaniz.com gibi).</div>
                        </div>
                        <button type="button" id="cd-request-btn" class="btn btn-primary">
                            <i class="fas fa-globe me-2"></i>Devam Et
                        </button>
                        <div id="cd-request-error" class="alert alert-danger mt-3 d-none"></div>
                    </div>
                </div>

                <div class="card d-none" id="cd-dns-card">
                    <div class="card-header"><h6 class="fw-bold mb-0">DNS Ayarları</h6></div>
                    <div class="card-body">
                        <p class="text-muted small">Alan adınızı sağlayan yerde (DNS sağlayıcınızın panelinde) aşağıdaki <strong>iki kaydı</strong> oluşturun, ardından "Doğrula" butonuna tıklayın. DNS değişikliklerinin yayılması birkaç dakika ile birkaç saat arasında sürebilir.</p>

                        <div class="table-responsive mb-3">
                            <table class="table table-sm">
                                <thead><tr><th>Tür</th><th>Ad / Host</th><th>Değer</th></tr></thead>
                                <tbody>
                                    <tr>
                                        <td><span class="badge bg-secondary">TXT</span></td>
                                        <td><code id="cd-txt-host"></code></td>
                                        <td><code id="cd-txt-value"></code></td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-secondary">CNAME</span></td>
                                        <td><code id="cd-cname-host"></code></td>
                                        <td><code id="cd-cname-target"></code></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-muted small">CNAME kaydı desteklenmiyorsa (bazı kök/apex alan adlarında), bunun yerine bir <strong>A kaydı</strong> ile <code id="cd-a-target"></code> adresine yönlendirebilirsiniz.</p>

                        <button type="button" id="cd-verify-btn" class="btn btn-primary">
                            <i class="fas fa-check-circle me-2"></i>Doğrula
                        </button>
                        <button type="button" id="cd-cancel-btn" class="btn btn-outline-secondary ms-2">Vazgeç</button>
                        <div id="cd-verify-result" class="alert mt-3 d-none"></div>
                    </div>
                </div>

            </div>

            <div class="col-12 col-lg-5">
                <div class="card">
                    <div class="card-header"><h6 class="fw-bold mb-0">Nasıl çalışır?</h6></div>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex gap-3">
                            <span class="badge bg-primary rounded-circle" style="width:24px;height:24px;display:grid;place-items:center;flex:none">1</span>
                            <div><strong class="d-block small">Alan adınızı girin</strong><span class="text-muted small">Kendi domain sağlayıcınızdan sahip olduğunuz bir alan adı veya alt alan adı.</span></div>
                        </div>
                        <div class="list-group-item d-flex gap-3">
                            <span class="badge bg-primary rounded-circle" style="width:24px;height:24px;display:grid;place-items:center;flex:none">2</span>
                            <div><strong class="d-block small">DNS kayıtlarını ekleyin</strong><span class="text-muted small">Size özel bir TXT kaydıyla sahiplik doğrulanır, CNAME/A kaydıyla trafik bize yönlendirilir.</span></div>
                        </div>
                        <div class="list-group-item d-flex gap-3">
                            <span class="badge bg-primary rounded-circle" style="width:24px;height:24px;display:grid;place-items:center;flex:none">3</span>
                            <div><strong class="d-block small">Doğrulayın</strong><span class="text-muted small">"Doğrula" butonuna tıklayın - kayıtları anında kontrol ederiz.</span></div>
                        </div>
                        <div class="list-group-item d-flex gap-3">
                            <span class="badge bg-primary rounded-circle" style="width:24px;height:24px;display:grid;place-items:center;flex:none">4</span>
                            <div><strong class="d-block small">Otomatik etkinleştirme</strong><span class="text-muted small">Doğrulama sonrası SSL sertifikanız otomatik alınır ve alan adınız birkaç dakika içinde aktif olur.</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/pages/custom_domain.min.js') ?>"></script>

<?php end_section('scripts'); ?>
