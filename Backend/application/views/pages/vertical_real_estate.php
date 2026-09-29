<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h1 class="h3 fw-bold mb-1">
                <i class="fas fa-building text-warning me-2"></i><?= html_escape($page_title ?? 'Gayrimenkul Portföy & İlan Yönetimi') ?>
            </h1>
            <p class="text-muted mb-0">Satılık ve kiralık gayrimenkul portföyü, mülk detayları, fiyatlandırma ve danışman atamaları.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <button type="button" class="btn btn-warning text-dark fw-bold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-add-listing">
                <i class="fas fa-plus me-1"></i> Yeni Portföy / İlan Ekle
            </button>
        </div>
    </div>

    <!-- Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 text-warning me-3">
                        <i class="fas fa-city fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Toplam Portföy</div>
                        <h4 class="fw-bold mb-0 text-dark"><?= count($listings ?? []) ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success me-3">
                        <i class="fas fa-tag fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Satılık İlanlar</div>
                        <h4 class="fw-bold mb-0 text-success">
                            <?= count(array_filter($listings ?? [], fn($l) => ($l['listing_type'] ?? '') === 'sale')) ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 text-info me-3">
                        <i class="fas fa-key fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Kiralık İlanlar</div>
                        <h4 class="fw-bold mb-0 text-info">
                            <?= count(array_filter($listings ?? [], fn($l) => ($l['listing_type'] ?? '') === 'rent')) ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary me-3">
                        <i class="fas fa-user-tie fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Gayrimenkul Danışmanları</div>
                        <h4 class="fw-bold mb-0 text-dark"><?= count($agents ?? []) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Listings Table Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-home text-warning me-2"></i>Gayrimenkul Portföy Listesi
            </h5>
            <span class="badge bg-light text-dark border"><?= count($listings ?? []) ?> İlan</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="table-listings">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">İlan Kodu</th>
                        <th>İlan Başlığı</th>
                        <th>Tür</th>
                        <th>Mülk Tipi</th>
                        <th>Fiyat</th>
                        <th>Konum</th>
                        <th>m²</th>
                        <th>Sorumlu Danışman</th>
                        <th>Durum</th>
                        <th class="text-end pe-3">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($listings)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="fas fa-home fa-2x mb-2 d-block text-secondary"></i>
                                Henüz kayıtlı gayrimenkul ilanı bulunmuyor. Yeni ilan ekleyerek başlayabilirsiniz.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($listings as $listing): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark">
                                    <span class="badge bg-light text-dark border font-monospace"><?= html_escape($listing['listing_code']) ?></span>
                                </td>
                                <td>
                                    <strong class="text-dark"><?= html_escape($listing['title']) ?></strong>
                                </td>
                                <td>
                                    <?php if (($listing['listing_type'] ?? '') === 'sale'): ?>
                                        <span class="badge bg-success">SATILIK</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-white">KİRALIK</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">
                                        <?= html_escape(ucfirst($listing['property_type'] ?? 'Daire')) ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-dark">
                                    <?= number_format((float)$listing['price'], 0, ',', '.') ?> ₺
                                </td>
                                <td>
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                    <?= html_escape($listing['district']) ?>, <?= html_escape($listing['city']) ?>
                                </td>
                                <td>
                                    <?= (int)($listing['square_meters'] ?? 0) > 0 ? (int)$listing['square_meters'] . ' m²' : '-' ?>
                                </td>
                                <td>
                                    <span class="small text-muted">
                                        <i class="fas fa-user-circle me-1"></i><?= html_escape($listing['agent_name'] ?? 'Genel Portföy') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= ($listing['status'] ?? '') === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= html_escape(strtoupper($listing['status'] ?? 'ACTIVE')) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-view-listing"
                                        data-code="<?= html_escape($listing['listing_code']) ?>"
                                        data-title="<?= html_escape($listing['title']) ?>"
                                        data-type="<?= html_escape($listing['listing_type']) ?>"
                                        data-property="<?= html_escape($listing['property_type']) ?>"
                                        data-price="<?= number_format((float)$listing['price'], 0, ',', '.') ?>"
                                        data-location="<?= html_escape($listing['district'] . ', ' . $listing['city']) ?>"
                                        data-m2="<?= (int)($listing['square_meters'] ?? 0) ?>"
                                        data-agent="<?= html_escape($listing['agent_name'] ?? '-') ?>">
                                        <i class="fas fa-eye"></i> Detay
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

<!-- MODAL: YENİ İLAN EKLE -->
<div class="modal fade" id="modal-add-listing" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus me-2"></i>Yeni Gayrimenkul Portföyü Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form id="form-add-listing">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">İlan Başlığı <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" placeholder="Örn: Caddebostan Sahil Sıfır 3+1 Lüks Daire" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">İlan Türü <span class="text-danger">*</span></label>
                            <select class="form-select" name="listing_type" required>
                                <option value="sale">Satılık</option>
                                <option value="rent">Kiralık</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mülk Tipi <span class="text-danger">*</span></label>
                            <select class="form-select" name="property_type" required>
                                <option value="apartment">Daire / Konut</option>
                                <option value="villa">Villa / Müstakil</option>
                                <option value="office">Ofis / Büro</option>
                                <option value="commercial">Dükkan / Mağaza</option>
                                <option value="land">Arsa / Tarla</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fiyat (₺) <span class="text-danger">*</span></label>
                            <input type="number" step="1" class="form-control" name="price" placeholder="Örn: 7500000" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Net / Brüt Alan (m²)</label>
                            <input type="number" class="form-control" name="square_meters" placeholder="Örn: 145">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">İl <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="city" value="İstanbul" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">İlçe <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="district" placeholder="Örn: Kadıköy" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Sorumlu Gayrimenkul Danışmanı</label>
                            <select class="form-select" name="id_users_agent">
                                <option value="">Danışman Seçiniz (Opsiyonel)...</option>
                                <?php foreach ($agents ?? [] as $ag): ?>
                                    <option value="<?= (int)$ag['id'] ?>">
                                        <?= html_escape($ag['first_name'] . ' ' . $ag['last_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold" id="btn-submit-listing">
                        <i class="fas fa-save me-1"></i> İlanı Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: İLAN DETAYI -->
<div class="modal fade" id="modal-view-listing" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="view-listing-code">Portföy Detayı</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body p-4">
                <h5 class="fw-bold text-primary mb-3" id="view-listing-title">-</h5>
                <div class="row g-3">
                    <div class="col-6">
                        <span class="text-muted small">İlan Türü:</span>
                        <div class="fw-bold" id="view-listing-type">-</div>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small">Mülk Tipi:</span>
                        <div class="fw-bold" id="view-listing-property">-</div>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small">Fiyat:</span>
                        <div class="fw-bold text-success fs-5" id="view-listing-price">-</div>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small">Kullanım Alanı:</span>
                        <div class="fw-bold" id="view-listing-m2">-</div>
                    </div>
                    <div class="col-12">
                        <span class="text-muted small">Konum:</span>
                        <div class="fw-bold" id="view-listing-location">-</div>
                    </div>
                    <div class="col-12">
                        <span class="text-muted small">Sorumlu Danışman:</span>
                        <div class="fw-bold" id="view-listing-agent">-</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Submit Listing
    var formListing = document.getElementById('form-add-listing');
    if (formListing) {
        formListing.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = document.getElementById('btn-submit-listing');
            var originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Kaydediliyor...';

            var fd = new FormData(formListing);
            var payload = {};
            fd.forEach(function(val, key) {
                payload[key] = val;
            });

            fetch('<?= site_url("verticals/save_real_estate_listing") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    alert(data.message || 'İlan portföye başarıyla eklendi.');
                    window.location.reload();
                } else {
                    alert(data.message || 'Kayıt sırasında bir hata oluştu.');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(function(err) {
                alert('Bağlantı hatası: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }

    // View Details
    document.querySelectorAll('.btn-view-listing').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('view-listing-code').textContent = 'İlan Kodu: ' + (this.getAttribute('data-code') || '');
            document.getElementById('view-listing-title').textContent = this.getAttribute('data-title') || '-';
            document.getElementById('view-listing-type').textContent = (this.getAttribute('data-type') || '').toUpperCase();
            document.getElementById('view-listing-property').textContent = (this.getAttribute('data-property') || '').toUpperCase();
            document.getElementById('view-listing-price').textContent = (this.getAttribute('data-price') || '0') + ' ₺';
            document.getElementById('view-listing-m2').textContent = (this.getAttribute('data-m2') || '-') + ' m²';
            document.getElementById('view-listing-location').textContent = this.getAttribute('data-location') || '-';
            document.getElementById('view-listing-agent').textContent = this.getAttribute('data-agent') || '-';

            var modal = new bootstrap.Modal(document.getElementById('modal-view-listing'));
            modal.show();
        });
    });
});
</script>
