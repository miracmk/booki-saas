<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>
<div class="container-fluid backend-page py-3" id="randevuburada-services-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1 fw-bold text-dark d-flex align-items-center">
                <i class="fas fa-tags me-2 text-primary"></i>
                RandevuBurada Hizmetler ve Fiyatlar
            </h4>
            <p class="text-muted small mb-0">İşletmenizin sunduğu hangi hizmetlerin RandevuBurada pazaryerinde listeleneceğini ve pazaryerine özel promosyonel fiyatları belirleyin.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('services') ?>" class="btn btn-outline-secondary rounded-3 px-3 shadow-sm">
                <i class="fas fa-cog me-1"></i> Tüm Hizmetleri Düzenle
            </a>
        </div>
    </div>

    <!-- Alert placeholder -->
    <div id="status-alert" class="d-none alert alert-success alert-dismissible fade show rounded-3" role="alert">
        <span id="status-alert-text"></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-list-check me-2 text-primary"></i>Pazaryeri Hizmet Kataloğu</h6>
            <span class="badge bg-primary bg-opacity-10 text-primary fs-7 rounded-pill">Toplam <?= count($services ?? []) ?> Hizmet</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Hizmet Adı</th>
                        <th>Kategori</th>
                        <th>Süre</th>
                        <th>Standart Fiyat</th>
                        <th>Pazaryeri Özel Fiyatı</th>
                        <th class="text-center">Vitrinde Göster</th>
                        <th class="text-end pe-4">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-box-open fa-2x mb-2 opacity-50"></i>
                                <p class="mb-0">Henüz tanımlı hizmet bulunmuyor.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($services as $svc): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($svc['name'] ?? '') ?></div>
                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;"><?= htmlspecialchars($svc['description'] ?? '') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border"><?= htmlspecialchars($svc['category_name'] ?? 'Genel') ?></span>
                                </td>
                                <td>
                                    <span class="text-muted"><i class="far fa-clock me-1"></i><?= (int)($svc['duration'] ?? 30) ?> dk</span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark"><?= number_format((float)($svc['price'] ?? 0), 2) ?> <?= htmlspecialchars($svc['currency'] ?? '₺') ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success fw-bold p-2" id="promo-display-<?= $svc['id'] ?>">
                                        <?= number_format((float)($svc['promo_price'] ?? $svc['price']), 2) ?> <?= htmlspecialchars($svc['currency'] ?? '₺') ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input" type="checkbox" role="switch" id="switch-svc-<?= $svc['id'] ?>"
                                               <?= !empty($svc['is_marketplace_visible']) ? 'checked' : '' ?>
                                               onchange="toggleServiceVisibility(<?= $svc['id'] ?>, this.checked)">
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-3" 
                                            onclick="openPriceModal(<?= $svc['id'] ?>, '<?= htmlspecialchars(addslashes($svc['name'])) ?>', <?= (float)($svc['promo_price'] ?? $svc['price']) ?>)">
                                        <i class="fas fa-edit me-1"></i> Fiyat Belirle
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

<!-- Fiyat Düzenleme Modalı -->
<div class="modal fade" id="priceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalServiceTitle">Pazaryeri Fiyatı Belirle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="modalServiceId">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">RandevuBurada Özel Vitrin Fiyatı</label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" class="form-control rounded-start-3" id="modalPromoPrice">
                        <span class="input-group-text bg-light rounded-end-3">₺</span>
                    </div>
                    <div class="form-text small">Pazaryeri müşterilerine özel indirimli veya kampanyalı fiyat uygulayabilirsiniz.</div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary rounded-3" onclick="savePromoPrice()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
let priceModal = null;
document.addEventListener('DOMContentLoaded', function() {
    priceModal = new bootstrap.Modal(document.getElementById('priceModal'));
});

function toggleServiceVisibility(serviceId, isVisible) {
    const formData = new FormData();
    formData.append('service_id', serviceId);
    formData.append('is_visible', isVisible ? '1' : '0');
    formData.append('<?= $this->security->get_csrf_token_name() ?>', '<?= $this->security->get_csrf_hash() ?>');

    fetch('<?= site_url('randevuburada/toggle_service') ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        showAlert(data.message || 'Durum güncellendi.');
    })
    .catch(err => {
        alert('İşlem başarısız: ' + err.message);
    });
}

function openPriceModal(id, title, currentPrice) {
    document.getElementById('modalServiceId').value = id;
    document.getElementById('modalServiceTitle').innerText = title + ' - Özel Fiyat';
    document.getElementById('modalPromoPrice').value = currentPrice;
    priceModal.show();
}

function savePromoPrice() {
    const serviceId = document.getElementById('modalServiceId').value;
    const promoPrice = document.getElementById('modalPromoPrice').value;

    const formData = new FormData();
    formData.append('service_id', serviceId);
    formData.append('promo_price', promoPrice);
    formData.append('<?= $this->security->get_csrf_token_name() ?>', '<?= $this->security->get_csrf_hash() ?>');

    fetch('<?= site_url('randevuburada/save_service_price') ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        priceModal.hide();
        document.getElementById('promo-display-' + serviceId).innerText = parseFloat(promoPrice).toFixed(2) + ' ₺';
        showAlert(data.message || 'Fiyat başarıyla kaydedildi.');
    })
    .catch(err => {
        alert('Fiyat güncellenirken hata oluştu: ' + err.message);
    });
}

function showAlert(msg) {
    const alertBox = document.getElementById('status-alert');
    const alertText = document.getElementById('status-alert-text');
    alertText.innerText = msg;
    alertBox.className = 'alert alert-success alert-dismissible fade show rounded-3';
    alertBox.classList.remove('d-none');
}
</script>
<?php end_section('content'); ?>
