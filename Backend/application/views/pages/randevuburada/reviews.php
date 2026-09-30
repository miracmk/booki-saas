<?php extend('layouts/backend_layout'); ?>

<?php section('styles'); ?>
<!-- BooKi - the backend layout ships Font Awesome in JS/SVG mode with the "solid" pack only, so
     brand icons ("fab fa-google" / "fab fa-yandex") render as empty boxes. Load the FA webfont
     stylesheet (already used by the other BooKi views and whitelisted in hooks/security_headers.php)
     so the unresolved brand <i> elements left behind by the FA JS get their glyphs. -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* BooKi - scoped polish for the reviews page (page-id scoped, so nothing leaks into other
   backend screens): source cards keep their height, review text wraps instead of stretching. */
#randevuburada-reviews-page .card{border-radius:1rem}
#randevuburada-reviews-page .table>thead>tr>th{font-size:.78rem;text-transform:uppercase;letter-spacing:.02em;color:#64748b;white-space:nowrap}
#randevuburada-reviews-page .table>tbody>tr>td{vertical-align:top}
#randevuburada-reviews-page .rb-review-text{max-width:380px;overflow-wrap:anywhere}
@media(max-width:575.98px){
    #randevuburada-reviews-page .btn-group-sm>.btn{padding:.3rem .45rem}
}
</style>
<?php end_section('styles'); ?>

<?php section('content'); ?>
<div class="container-fluid backend-page py-3 px-md-4" style="max-width: 1400px;" id="randevuburada-reviews-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1 fw-bold text-dark d-flex align-items-center">
                <i class="fas fa-comments me-2 text-primary"></i>
                RandevuBurada Yorum & Kaynak Yönetimi
            </h4>
            <p class="text-muted small mb-0">RandevuBurada vitrininizde gösterilen müşteri yorumlarını yönetin, Google ve Yandex gibi dış platform yorum entegrasyonlarını açıp kapatın.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary rounded-3 px-3 shadow-sm" onclick="saveSourceSettings()">
                <i class="fas fa-save me-1"></i> Kaynak Ayarlarını Kaydet
            </button>
        </div>
    </div>

    <!-- Alert placeholder -->
    <div id="status-alert" class="d-none alert alert-success alert-dismissible fade show rounded-3" role="alert">
        <span id="status-alert-text"></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>

    <!-- Kaynak Yönetim Kartları -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 bg-danger bg-opacity-10 text-danger p-2 fs-5">
                            <i class="fab fa-google"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">Google Haritalar</h6>
                            <span class="text-muted small">Google İşletme Profili</span>
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="sourceGoogle" <?= !empty($sources['google']) ? 'checked' : '' ?>>
                    </div>
                </div>
                <p class="text-muted small mb-0">Google Haritalar ve İşletme Profilinizdeki doğrulanmış yorumları vitrinize çeker ve sergiler.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-2 fs-5">
                            <i class="fab fa-yandex"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">Yandex Haritalar</h6>
                            <span class="text-muted small">Yandex Business</span>
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="sourceYandex" <?= !empty($sources['yandex']) ? 'checked' : '' ?>>
                    </div>
                </div>
                <p class="text-muted small mb-0">Yandex Haritalar üzerindeki puan ve müşteri geri bildirimlerini profilinizde gösterir.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-2 fs-5">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">RandevuBurada Yorumları</h6>
                            <span class="text-muted small">Doğrulanmış Rezervasyonlar</span>
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="sourceDirect" <?= !empty($sources['randevuburada']) ? 'checked' : '' ?>>
                    </div>
                </div>
                <p class="text-muted small mb-0">Hizmet alan gerçek müşterilerin randevu sonrası bıraktığı doğrudan platform yorumları.</p>
            </div>
        </div>
    </div>

    <!-- Yorum Moderasyon Tablosu -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-star-half-alt me-2 text-warning"></i>Vitrin Yorum Moderasyonu</h6>
                <span class="badge bg-secondary bg-opacity-10 text-secondary small fw-semibold rounded-pill"><?= count($reviews ?? []) ?> Yorum</span>
            </div>
            <div class="btn-group btn-group-sm rounded-3 shadow-none">
                <button type="button" class="btn btn-outline-secondary active" onclick="filterReviews('all', this)">Tümü</button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterReviews('randevuburada', this)">RandevuBurada</button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterReviews('google', this)">Google</button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterReviews('yandex', this)">Yandex</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="reviewsTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Müşteri & Puan</th>
                        <th>Kaynak</th>
                        <th>Yorum & Değerlendirme</th>
                        <th>Tarih</th>
                        <th>Durum</th>
                        <th class="text-end pe-4">İşlem / Moderasyon</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-comment-slash fa-2x mb-2 opacity-50"></i>
                                <p class="mb-0">Henüz yayınlanmış veya bekleyen yorum bulunmuyor.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $rev): ?>
                            <tr class="review-row" data-source="<?= htmlspecialchars(strtolower($rev['source'] ?? 'randevuburada')) ?>" id="row-rev-<?= $rev['id'] ?>">
                                <td class="ps-4">
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars(trim(($rev['customer_first_name'] ?? 'Müşteri') . ' ' . ($rev['customer_last_name'] ?? ''))) ?></div>
                                    <div class="text-warning small">
                                        <?php $rating = (int)($rev['rating'] ?? 5); ?>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="<?= $i <= $rating ? 'fas' : 'far' ?> fa-star"></i>
                                        <?php endfor; ?>
                                        <span class="ms-1 fw-bold text-dark"><?= $rating ?>.0</span>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $src = strtolower($rev['source'] ?? 'randevuburada');
                                    if ($src === 'google'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle"><i class="fab fa-google me-1"></i> Google</span>
                                    <?php elseif ($src === 'yandex'): ?>
                                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning-subtle"><i class="fab fa-yandex me-1"></i> Yandex</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle"><i class="fas fa-store me-1"></i> RandevuBurada</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <p class="mb-0 text-dark small rb-review-text"><?= nl2br(htmlspecialchars($rev['comment'] ?? 'Hizmetten son derece memnun kaldım, teşekkürler.')) ?></p>
                                </td>
                                <td>
                                    <small class="text-muted"><?= htmlspecialchars(substr($rev['created_at'] ?? date('Y-m-d'), 0, 10)) ?></small>
                                </td>
                                <td>
                                    <?php 
                                    $st = strtolower($rev['status'] ?? 'published');
                                    if ($st === 'published'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success" id="badge-status-<?= $rev['id'] ?>"><i class="fas fa-check-circle me-1"></i> Vitrinde Yayında</span>
                                    <?php elseif ($st === 'rejected'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger" id="badge-status-<?= $rev['id'] ?>"><i class="fas fa-ban me-1"></i> Gizlendi / Reddedildi</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-dark" id="badge-status-<?= $rev['id'] ?>"><i class="fas fa-clock me-1"></i> Onay Bekliyor</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-success" title="Kabul Et & Vitrinde Göster" onclick="moderateReview(<?= $rev['id'] ?>, 'publish')">
                                            <i class="fas fa-check me-1"></i> Kabul Et
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" title="Reddet & Vitrinden Gizle" onclick="moderateReview(<?= $rev['id'] ?>, 'reject')">
                                            <i class="fas fa-eye-slash me-1"></i> Gizle / Reddet
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script>
/* BooKi - CSRF: the controller passes csrf_name / csrf_hash to the view (views cannot reach
   $this->security in this CI build - $this inside a view is the loader). window.vars('csrf_token')
   is the app-wide fallback (App_Controller::load_common_script_vars()). These POST endpoints are
   CSRF-protected by CI's Security library, so every fetch below must carry the token. */
const RB_CSRF_NAME = <?= json_encode($csrf_name ?? 'csrf_token') ?>;
const RB_CSRF_TOKEN = <?= json_encode((string) ($csrf_hash ?? vars('csrf_token'))) ?>;

function rbCsrfToken() {
    return (typeof window.vars === 'function' && window.vars('csrf_token')) || RB_CSRF_TOKEN;
}

function saveSourceSettings() {
    const formData = new FormData();
    formData.append('google', document.getElementById('sourceGoogle').checked ? '1' : '0');
    formData.append('yandex', document.getElementById('sourceYandex').checked ? '1' : '0');
    formData.append('randevuburada', document.getElementById('sourceDirect').checked ? '1' : '0');
    formData.append(RB_CSRF_NAME, rbCsrfToken());

    fetch('<?= site_url('randevuburada/save_sources') ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        showAlert(data.message || 'Yorum kaynakları kaydedildi.');
    })
    .catch(err => {
        alert('Kaydedilirken hata oluştu: ' + err.message);
    });
}

function moderateReview(reviewId, action) {
    const url = action === 'publish' ? '<?= site_url('randevuburada/publish_review') ?>' : '<?= site_url('randevuburada/reject_review') ?>';
    const formData = new FormData();
    formData.append('review_id', reviewId);
    formData.append(RB_CSRF_NAME, rbCsrfToken());

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        const badge = document.getElementById('badge-status-' + reviewId);
        if (action === 'publish') {
            badge.className = 'badge bg-success bg-opacity-10 text-success';
            badge.innerHTML = '<i class="fas fa-check-circle me-1"></i> Vitrinde Yayında';
        } else {
            badge.className = 'badge bg-danger bg-opacity-10 text-danger';
            badge.innerHTML = '<i class="fas fa-ban me-1"></i> Gizlendi / Reddedildi';
        }
        showAlert(data.message || 'Yorum güncellendi.');
    })
    .catch(err => {
        alert('İşlem sırasında hata oluştu: ' + err.message);
    });
}

function filterReviews(source, btn) {
    document.querySelectorAll('#randevuburada-reviews-page .btn-group .btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const rows = document.querySelectorAll('.review-row');
    rows.forEach(r => {
        if (source === 'all' || r.dataset.source === source) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
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
<?php end_section('scripts'); ?>
