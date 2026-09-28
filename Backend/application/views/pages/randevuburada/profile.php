<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>
<div class="container-fluid backend-page py-3" id="randevuburada-profile-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1 fw-bold text-dark d-flex align-items-center">
                <i class="fas fa-store me-2 text-primary"></i>
                RandevuBurada Vitrin & Profil Yönetimi
            </h4>
            <p class="text-muted small mb-0">RandevuBurada pazaryerinde müşterilerinizin göreceği profil ve vitrin bilgilerinizi buradan yönetin.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= e($mp_url) ?>" target="_blank" rel="noopener" class="btn btn-outline-primary rounded-3 px-3 shadow-sm d-flex align-items-center">
                <i class="fas fa-external-link-alt me-2"></i> Canlı Vitrini Görüntüle
            </a>
            <button type="button" class="btn btn-primary rounded-3 px-3 shadow-sm" onclick="saveStorefrontProfile()">
                <i class="fas fa-save me-1"></i> Değişiklikleri Kaydet
            </button>
        </div>
    </div>

    <!-- Alert placeholder -->
    <div id="status-alert" class="d-none alert alert-success alert-dismissible fade show rounded-3" role="alert">
        <span id="status-alert-text"></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>

    <div class="row g-4">
        <!-- Sol Sütun: Profil Formu -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-id-card me-2 text-primary"></i>Pazaryeri Genel Bilgileri</h6>
                </div>
                <div class="card-body p-4">
                    <form id="profile-form">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">İşletme Adı (Vitrinde Görünen)</label>
                                <input type="text" class="form-control rounded-3" name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Sektörel Kategori</label>
                                <select class="form-select rounded-3" name="randevuburada_category">
                                    <option value="Restoran & Gastronomi" <?= ($settings['randevuburada_category'] ?? '') === 'Restoran & Gastronomi' ? 'selected' : '' ?>>Restoran & Gastronomi</option>
                                    <option value="Kuaför & Güzellik" <?= ($settings['randevuburada_category'] ?? '') === 'Kuaför & Güzellik' ? 'selected' : '' ?>>Kuaför & Güzellik</option>
                                    <option value="Sağlık & Klinik" <?= ($settings['randevuburada_category'] ?? '') === 'Sağlık & Klinik' ? 'selected' : '' ?>>Sağlık & Klinik</option>
                                    <option value="Spor & Fitness" <?= ($settings['randevuburada_category'] ?? '') === 'Spor & Fitness' ? 'selected' : '' ?>>Spor & Fitness</option>
                                    <option value="Otomotiv & Servis" <?= ($settings['randevuburada_category'] ?? '') === 'Otomotiv & Servis' ? 'selected' : '' ?>>Otomotiv & Servis</option>
                                    <option value="Eğlence & Aktivite" <?= ($settings['randevuburada_category'] ?? '') === 'Eğlence & Aktivite' ? 'selected' : '' ?>>Eğlence & Aktivite</option>
                                    <option value="Genel" <?= ($settings['randevuburada_category'] ?? '') === 'Genel' ? 'selected' : '' ?>>Diğer / Genel Hizmetler</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">İletişim Telefon Numarası</label>
                                <input type="tel" class="form-control rounded-3" name="company_phone" value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">İletişim E-posta Adresi</label>
                                <input type="email" class="form-control rounded-3" name="company_email" value="<?= htmlspecialchars($settings['company_email'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-muted">Açık Adres / Konum</label>
                                <input type="text" class="form-control rounded-3" name="company_address" value="<?= htmlspecialchars($settings['company_address'] ?? '') ?>" placeholder="Örn: Barbaros Mah. Atatürk Cad. No:12 Kadıköy / İstanbul">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-muted">İşletme Tanıtım Yazısı (Bio)</label>
                                <textarea class="form-control rounded-3" name="company_description" rows="4" placeholder="Müşterilerinize işletmenizi anlatan kısa ve çekici bir tanıtım yazısı..."><?= htmlspecialchars($settings['company_description'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-muted">Arama Etiketleri & Anahtar Kelimeler</label>
                                <input type="text" class="form-control rounded-3" name="randevuburada_tags" value="<?= htmlspecialchars($settings['randevuburada_tags'] ?? '') ?>" placeholder="Örn: saç kesimi, manikür, lazer, akşam yemeği">
                                <div class="form-text small">RandevuBurada arama çubuğunda işletmenizin kolay bulunmasını sağlayacak virgülle ayrılmış kelimeler.</div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-sliders-h me-2 text-primary"></i>Pazaryeri Rezervasyon Kuralları</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">En Erken Rezervasyon Süresi (Saat)</label>
                            <input type="number" form="profile-form" class="form-control rounded-3" name="randevuburada_min_notice_hours" min="0" max="72" value="<?= htmlspecialchars($settings['randevuburada_min_notice_hours'] ?? '2') ?>">
                            <div class="form-text small">Müşteri en az kaç saat öncesinden randevu alabilir?</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Anında Rezervasyon Onayı</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" form="profile-form" type="checkbox" name="randevuburada_instant_booking" id="instantBookingSwitch" value="1" <?= ($settings['randevuburada_instant_booking'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="instantBookingSwitch">Gelen randevuları otomatik onayla</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sağ Sütun: Vitrin Durumu & Önizleme -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-toggle-on me-2 text-success"></i>Vitrin Durumu</h6>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="mb-3">
                        <span class="badge bg-success bg-opacity-10 text-success fs-6 py-2 px-3 rounded-pill">
                            <i class="fas fa-check-circle me-1"></i> Vitrin Aktif
                        </span>
                    </div>
                    <p class="text-muted small mb-4">RandevuBurada pazaryerinde işletme profiliniz ziyaretçilere açık ve listelenmektedir.</p>
                    <div class="form-check form-switch d-inline-block text-start">
                        <input class="form-check-input" form="profile-form" type="checkbox" name="randevuburada_active" id="activeSwitch" value="1" <?= ($settings['randevuburada_active'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold text-dark" for="activeSwitch">Vitrini Açık Tut</label>
                    </div>
                </div>
            </div>

            <!-- Canlı Vitrin Kartı Önizleme -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="bg-primary text-white p-4 position-relative" style="background: linear-gradient(135deg, var(--bs-primary) 0%, #1e293b 100%);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center shadow-sm" style="width: 52px; height: 52px; font-size: 20px; font-weight: bold;">
                            <?= mb_substr($settings['company_name'] ?: 'İ', 0, 1) ?>
                        </div>
                        <div>
                            <h6 class="mb-0 text-white fw-bold"><?= htmlspecialchars($settings['company_name'] ?: 'İşletme Adınız') ?></h6>
                            <span class="badge bg-light bg-opacity-25 text-white small"><?= htmlspecialchars($settings['randevuburada_category'] ?: 'Kategori') ?></span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="small text-muted mb-2"><i class="fas fa-map-marker-alt me-2 text-danger"></i><?= htmlspecialchars($settings['company_address'] ?: 'Adres belirtilmedi') ?></div>
                    <div class="small text-muted mb-3"><i class="fas fa-phone me-2 text-success"></i><?= htmlspecialchars($settings['company_phone'] ?: 'Telefon belirtilmedi') ?></div>
                    <div class="border-top pt-2">
                        <a href="<?= e($mp_url) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary w-100 rounded-3">
                            <i class="fas fa-eye me-1"></i> Vitrin Canlı Sayfasına Git
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function saveStorefrontProfile() {
    const form = document.getElementById('profile-form');
    const formData = new FormData(form);
    
    // Checkbox handles
    if (!form.querySelector('input[name="randevuburada_active"]').checked) {
        formData.set('randevuburada_active', '0');
    }
    if (!form.querySelector('input[name="randevuburada_instant_booking"]').checked) {
        formData.set('randevuburada_instant_booking', '0');
    }

    fetch('<?= site_url('randevuburada/save_profile') ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        const alertBox = document.getElementById('status-alert');
        const alertText = document.getElementById('status-alert-text');
        alertText.innerText = data.message || 'Başarıyla kaydedildi.';
        alertBox.className = 'alert alert-success alert-dismissible fade show rounded-3';
        alertBox.classList.remove('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    })
    .catch(err => {
        alert('Kaydedilirken bir hata oluştu: ' + err.message);
    });
}
</script>
<?php end_section('content'); ?>
