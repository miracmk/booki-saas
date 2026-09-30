<?php extend('layouts/backend_layout'); ?>

<?php section('styles'); ?>
<style>
#randevuburada-profile-page {
    --rb-brand: <?= htmlspecialchars($settings['company_color'] ?? '#35A768') ?>;
}
#randevuburada-profile-page .rb-card {
    border: 0;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
    border-radius: 1rem;
    background: #ffffff;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
#randevuburada-profile-page .rb-card-header {
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    padding: 1rem 1.35rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
#randevuburada-profile-page .rb-card-header h6 {
    margin: 0;
    font-weight: 700;
    font-size: 0.95rem;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
#randevuburada-profile-page .rb-card-body {
    padding: 1.4rem;
}
#randevuburada-profile-page .form-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.35rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
#randevuburada-profile-page .form-control,
#randevuburada-profile-page .form-select {
    border-radius: 0.7rem;
    border: 1px solid #e2e8f0;
    font-size: 0.88rem;
    padding: 0.58rem 0.85rem;
    color: #0f172a;
    background-color: #fff;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
#randevuburada-profile-page .form-control:focus,
#randevuburada-profile-page .form-select:focus {
    border-color: #35A768;
    box-shadow: 0 0 0 3px rgba(53, 167, 104, 0.15);
}
#randevuburada-profile-page .sticky-sidebar {
    position: sticky;
    top: 1.5rem;
}
/* Live Preview Card */
#randevuburada-profile-page .preview-card {
    border: 0;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
    border-radius: 1.25rem;
    background: #fff;
    overflow: hidden;
}
#randevuburada-profile-page .preview-cover {
    height: 120px;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    position: relative;
    background: linear-gradient(135deg, var(--rb-brand) 0%, #0f172a 100%);
    transition: background 0.3s ease;
}
#randevuburada-profile-page .preview-cover-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.65) 100%);
}
#randevuburada-profile-page .preview-avatar {
    width: 60px;
    height: 60px;
    min-width: 60px;
    border-radius: 50%;
    background: #ffffff;
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 800;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    border: 3px solid #ffffff;
    position: relative;
    top: 25px;
    z-index: 2;
    transition: all 0.2s ease;
}
#randevuburada-profile-page .rb-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.88rem;
    font-weight: 700;
    padding: 0.5rem 1.15rem;
    border-radius: 999px;
    transition: all 0.2s ease;
}
#randevuburada-profile-page .rb-pill.on {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
#randevuburada-profile-page .rb-pill.off {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}
.pulse-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    background: #22c55e;
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
    animation: pulse 1.8s infinite;
}
@keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}
@media (max-width: 991px) {
    #randevuburada-profile-page .sticky-sidebar { position: static; }
}
</style>
<?php end_section('styles'); ?>

<?php section('content'); ?>
<div class="container-fluid backend-page py-3 px-md-4" style="max-width: 1440px;" id="randevuburada-profile-page">
    
    <!-- Üst Başlık & Hızlı Aksiyon Barı -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 small fw-semibold">
                    <i class="fas fa-sync-alt fa-spin me-1 text-primary" style="animation-duration: 4s;"></i> Canlı İşletme Profili Senkronizasyonu
                </span>
                <?php if (!empty($tenant_sub)): ?>
                    <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small">
                        <i class="fas fa-globe me-1"></i><?= e($tenant_sub) ?>.kibusiness.co
                    </span>
                <?php endif; ?>
            </div>
            <h4 class="mb-1 fw-bold text-dark d-flex align-items-center">
                <i class="fas fa-store me-2 text-primary"></i>
                RandevuBurada Vitrin & İşletme Profil Yönetimi
            </h4>
            <p class="text-muted small mb-0">
                Pazaryeri vitrininizde müşterilerin gördüğü profil, harita, rezervasyon kuralları ve marka detaylarını yönetin. Değişiklikler anında İşletme Profilinizle (`ea_settings`) ve Pazaryeri kataloğuyla senkronize olur.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= e($mp_url ?? 'https://randevuburada.kibusiness.co') ?>" target="_blank" rel="noopener" class="btn btn-outline-primary rounded-3 px-3 shadow-sm d-inline-flex align-items-center">
                <i class="fas fa-external-link-alt me-2"></i> Canlı Vitrini Görüntüle
            </a>
            <button type="button" class="btn btn-primary rounded-3 px-4 shadow-sm fw-semibold d-inline-flex align-items-center" id="rb-save-btn">
                <i class="fas fa-save me-2" id="rb-save-icon"></i>
                <span id="rb-save-text">Değişiklikleri Kaydet</span>
            </button>
        </div>
    </div>

    <!-- Bildirim / Durum Kutusu -->
    <div id="status-alert" class="d-none alert alert-success alert-dismissible fade show rounded-3 shadow-xs mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle me-2 fs-5"></i>
            <div>
                <strong id="status-alert-title">Başarılı!</strong>
                <span id="status-alert-text" class="ms-1">RandevuBurada vitrin profili ve İşletme Profili güncellendi.</span>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>

    <div class="row g-4">
        <!-- SOL SÜTUN: Form Kartları -->
        <div class="col-lg-8">
            <form id="profile-form">
                <!-- 1. KART: İşletme Kimliği & Marka -->
                <div class="card rb-card mb-4">
                    <div class="rb-card-header">
                        <h6><i class="fas fa-building text-primary"></i> İşletme Kimliği & Marka Bilgileri</h6>
                        <span class="badge bg-light text-muted fw-normal" style="font-size: 11px;">Temel Ayarlar</span>
                    </div>
                    <div class="rb-card-body">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label" for="f-company-name">İşletme Adı (Vitrinde ve Fişlerde Görünen) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="f-company-name" name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>" placeholder="Örn: Salon Bella Güzellik & Bakım" required>
                                <div class="form-text small">Müşterilerin RandevuBurada ve randevu sayfalarında gördüğü resmi işletme adı.</div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="f-category">Sektörel Kategori <span class="text-danger">*</span></label>
                                <select class="form-select" id="f-category" name="randevuburada_category">
                                    <?php
                                    $categories = [
                                        'Kuaför & Güzellik',
                                        'Güzellik & Bakım',
                                        'Berber & Erkek Bakımı',
                                        'Spa & Masaj',
                                        'Tırnak & Nail Art',
                                        'Estetik & Klinik',
                                        'Restoran & Gastronomi',
                                        'Kafe & Bistro',
                                        'Spor & Fitness',
                                        'Tenis & Kort',
                                        'Sağlık & Diş',
                                        'Veteriner & Pet',
                                        'Otomotiv & Servis',
                                        'Dövme & Piercing',
                                        'Eğlence & Aktivite',
                                        'Hizmet & Randevu',
                                        'Genel / Diğer'
                                    ];
                                    $current_cat = $settings['randevuburada_category'] ?? '';
                                    if (!in_array($current_cat, $categories) && !empty($current_cat)) {
                                        array_unshift($categories, $current_cat);
                                    }
                                    foreach ($categories as $cat):
                                    ?>
                                        <option value="<?= htmlspecialchars($cat) ?>" <?= ($current_cat === $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="f-price-range">Fiyat Skalası / Segment</label>
                                <select class="form-select" id="f-price-range" name="randevuburada_price_range">
                                    <option value="₺" <?= ($settings['randevuburada_price_range'] ?? '') === '₺' ? 'selected' : '' ?>>₺ (Ekonomik / Uygun)</option>
                                    <option value="₺₺" <?= ($settings['randevuburada_price_range'] ?? '₺₺') === '₺₺' ? 'selected' : '' ?>>₺₺ (Standart / Ortalama)</option>
                                    <option value="₺₺₺" <?= ($settings['randevuburada_price_range'] ?? '') === '₺₺₺' ? 'selected' : '' ?>>₺₺₺ (Seçkin / Premium)</option>
                                    <option value="₺₺₺₺" <?= ($settings['randevuburada_price_range'] ?? '') === '₺₺₺₺' ? 'selected' : '' ?>>₺₺₺₺ (Lüks / VIP)</option>
                                </select>
                                <div class="form-text small">Pazaryeri filtrelerinde arama kriteridir.</div>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label" for="f-company-link">Web Sitesi Bağlantısı</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fas fa-link"></i></span>
                                    <input type="url" class="form-control" id="f-company-link" name="company_link" value="<?= htmlspecialchars($settings['company_link'] ?? '') ?>" placeholder="https://isletmeniz.com">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="f-company-color">Marka / Tema Rengi</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" class="form-control form-control-color p-1 rounded-3" id="f-company-color" name="company_color" value="<?= htmlspecialchars($settings['company_color'] ?? '#35A768') ?>" title="Marka Renginizi Seçin" style="width: 48px; height: 38px;">
                                    <span id="color-hex-label" class="small font-monospace text-muted"><?= htmlspecialchars($settings['company_color'] ?? '#35A768') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. KART: İletişim, Konum & Adres (Harita & Arama) -->
                <div class="card rb-card mb-4">
                    <div class="rb-card-header">
                        <h6><i class="fas fa-map-marked-alt text-primary"></i> İletişim & Lokasyon Bilgileri</h6>
                        <span class="badge bg-light text-muted fw-normal" style="font-size: 11px;">Harita & Keşfet</span>
                    </div>
                    <div class="rb-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="f-phone">İletişim Telefon Numarası <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fas fa-phone"></i></span>
                                    <input type="tel" class="form-control" id="f-phone" name="company_phone" value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>" placeholder="+90 555 123 45 67">
                                </div>
                                <div class="form-text small">Müşterilerin doğrudan arayabileceği veya WhatsApp üzerinden ulaşabileceği numara.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="f-email">İletişim E-posta Adresi</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="f-email" name="company_email" value="<?= htmlspecialchars($settings['company_email'] ?? '') ?>" placeholder="info@isletmeniz.com">
                                </div>
                                <div class="form-text small">Rezervasyon bildirimleri ve müşteri yazışmaları için.</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="f-city">Şehir (İl) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="f-city" name="randevuburada_city" list="cities-list" value="<?= htmlspecialchars($settings['randevuburada_city'] ?? '') ?>" placeholder="Örn: İstanbul">
                                <datalist id="cities-list">
                                    <option value="İstanbul">
                                    <option value="Ankara">
                                    <option value="İzmir">
                                    <option value="Bursa">
                                    <option value="Antalya">
                                    <option value="Adana">
                                    <option value="Eskişehir">
                                    <option value="Gaziantep">
                                    <option value="Konya">
                                    <option value="Kocaeli">
                                    <option value="Mersin">
                                    <option value="Kayseri">
                                    <option value="Muğla">
                                    <option value="Samsun">
                                    <option value="Trabzon">
                                </datalist>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="f-district">İlçe / Bölge <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="f-district" name="randevuburada_district" value="<?= htmlspecialchars($settings['randevuburada_district'] ?? '') ?>" placeholder="Örn: Kadıköy / Nilüfer">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="f-neighborhood">Mahalle / Semt</label>
                                <input type="text" class="form-control" id="f-neighborhood" name="randevuburada_neighborhood" value="<?= htmlspecialchars($settings['randevuburada_neighborhood'] ?? '') ?>" placeholder="Örn: Caferağa Mah. / Ataevler">
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="f-address">Açık Adres / Konum Tarifi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="f-address" name="company_address" value="<?= htmlspecialchars($settings['company_address'] ?? '') ?>" placeholder="Örn: Bağdat Caddesi No: 124/A Kadıköy / İstanbul">
                                <div class="form-text small">Müşterilerin navigasyonla işletmenize gelebilmesi için açık adres.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. KART: Tanıtım, Görseller & SEO -->
                <div class="card rb-card mb-4">
                    <div class="rb-card-header">
                        <h6><i class="fas fa-images text-primary"></i> Tanıtım, Vitrin Görseli & SEO</h6>
                        <span class="badge bg-light text-muted fw-normal" style="font-size: 11px;">Müşteri İlgisi</span>
                    </div>
                    <div class="rb-card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="f-cover-image">Vitrin & Kapak Görseli URL'si</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fas fa-camera"></i></span>
                                    <input type="url" class="form-control" id="f-cover-image" name="randevuburada_cover_image" value="<?= htmlspecialchars($settings['randevuburada_cover_image'] ?? '') ?>" placeholder="https://images.unsplash.com/... veya https://isletmeniz.com/foto.jpg">
                                </div>
                                <div class="form-text small">RandevuBurada pazar yeri vitrin kartında ve sayfa başlığında arka plan olarak görünür.</div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0" for="f-description">İşletme Tanıtım Yazısı (Bio & SEO)</label>
                                    <span class="small text-muted" id="desc-char-count">0 / 500 karakter</span>
                                </div>
                                <textarea class="form-control" id="f-description" name="company_description" rows="4" maxlength="600" placeholder="Müşterilerinize işletmenizin sunduğu özel deneyimi, uzmanlık alanlarınızı ve sunduğunuz ayrıcalıkları anlatan çekici bir tanıtım yazısı..."><?= htmlspecialchars($settings['company_description'] ?? '') ?></textarea>
                                <div class="form-text small">RandevuBurada arama sonuçlarında ve yapay zeka randevu asistanında öne çıkarılır.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="f-tags">Arama Etiketleri & Anahtar Kelimeler</label>
                                <input type="text" class="form-control" id="f-tags" name="randevuburada_tags" value="<?= htmlspecialchars($settings['randevuburada_tags'] ?? '') ?>" placeholder="Örn: saç renklendirme, keratin bakım, manikür, lazer epilasyon">
                                <div class="form-text small">RandevuBurada arama çubuğunda işletmenizin kolay bulunmasını sağlayacak virgülle ayrılmış anahtar sözcükler.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. KART: Rezervasyon Kuralları -->
                <div class="card rb-card mb-4">
                    <div class="rb-card-header">
                        <h6><i class="fas fa-clock text-primary"></i> Pazaryeri Rezervasyon Kuralları</h6>
                        <span class="badge bg-light text-muted fw-normal" style="font-size: 11px;">Rezervasyon Yönetimi</span>
                    </div>
                    <div class="rb-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="f-min-hours">En Erken Randevu Alma Süresi (Saat)</label>
                                <input type="number" class="form-control" id="f-min-hours" name="randevuburada_min_notice_hours" min="0" max="72" value="<?= htmlspecialchars($settings['randevuburada_min_notice_hours'] ?? '2') ?>">
                                <div class="form-text small">Müşteriler randevu saatinden en az kaç saat öncesine kadar randevu oluşturabilir? (Örn: 2 saat)</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Otomatik Rezervasyon Onayı</label>
                                <div class="p-2 border rounded-3 bg-light-subtle d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="fw-semibold small text-dark">Anında Rezervasyon Onayı</div>
                                        <div class="text-muted" style="font-size: 11px;">Gelen randevular yönetici onayı beklemeden onaylansın.</div>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="instantBookingSwitch" name="randevuburada_instant_booking" value="1" <?= ($settings['randevuburada_instant_booking'] ?? '1') === '1' ? 'checked' : '' ?> style="cursor: pointer; transform: scale(1.15);">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- SAĞ SÜTUN: Vitrin Durumu & Canlı Önizleme -->
        <div class="col-lg-4">
            <div class="sticky-sidebar">
                <!-- Vitrin Durumu Kartı -->
                <div class="card rb-card mb-4">
                    <div class="rb-card-header">
                        <h6><i class="fas fa-toggle-on text-success"></i> Pazaryeri Vitrin Durumu</h6>
                    </div>
                    <div class="rb-card-body text-center">
                        <div class="mb-3">
                            <span id="vitrin-pill" class="rb-pill <?= ($settings['randevuburada_active'] ?? '1') === '1' ? 'on' : 'off' ?>">
                                <span class="pulse-dot <?= ($settings['randevuburada_active'] ?? '1') === '1' ? '' : 'd-none' ?>" id="vitrin-pulse"></span>
                                <i class="fas <?= ($settings['randevuburada_active'] ?? '1') === '1' ? 'fa-check-circle' : 'fa-times-circle' ?> me-1" id="vitrin-pill-icon"></i>
                                <span id="vitrin-pill-text"><?= ($settings['randevuburada_active'] ?? '1') === '1' ? 'Vitrin Aktif & Yayında' : 'Vitrin Kapalı (Gizli)' ?></span>
                            </span>
                        </div>
                        <p class="text-muted small mb-3">
                            Bu anahtar açık olduğunda işletmeniz RandevuBurada dizininde listelenir ve müşteriler arama yaparak profilinize ulaşabilir.
                        </p>
                        <div class="form-check form-switch d-inline-flex align-items-center gap-2 text-start p-0">
                            <input class="form-check-input ms-0 me-2" type="checkbox" id="activeSwitch" name="randevuburada_active" value="1" <?= ($settings['randevuburada_active'] ?? '1') === '1' ? 'checked' : '' ?> style="cursor: pointer; transform: scale(1.25);">
                            <label class="form-check-label fw-bold text-dark" for="activeSwitch" style="cursor: pointer;">
                                Pazaryerinde Yayında Tut
                            </label>
                        </div>

                        <hr class="my-3 text-muted opacity-25">

                        <div class="text-start">
                            <div class="small fw-semibold text-muted mb-1 text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Doğrudan Vitrin Bağlantınız</div>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control font-monospace" id="mp-url-input" value="<?= e($mp_url ?? '') ?>" readonly style="font-size: 11px; background: #f8fafc;">
                                <button type="button" class="btn btn-outline-secondary" id="copy-url-btn" title="Bağlantıyı Kopyala">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Canlı Vitrin Kartı Önizleme -->
                <div class="card preview-card mb-4 shadow-sm">
                    <div class="preview-cover d-flex align-items-end p-3" id="preview-cover-bg" style="<?= !empty($settings['randevuburada_cover_image']) ? 'background-image: url(\'' . htmlspecialchars($settings['randevuburada_cover_image']) . '\');' : '' ?>">
                        <div class="preview-cover-overlay"></div>
                        <div class="preview-avatar" id="preview-avatar" style="border-color: <?= htmlspecialchars($settings['company_color'] ?? '#35A768') ?>;">
                            <?= e(function_exists('mb_substr') ? mb_substr($settings['company_name'] ?: 'B', 0, 1, 'UTF-8') : substr($settings['company_name'] ?: 'B', 0, 1)) ?>
                        </div>
                    </div>

                    <div class="card-body p-3 pt-4">
                        <div class="d-flex justify-content-between align-items-start mb-2 mt-2">
                            <div>
                                <h6 class="fw-bold mb-1 text-dark" id="preview-company-name">
                                    <?= htmlspecialchars($settings['company_name'] ?: 'İşletme Adınız') ?>
                                </h6>
                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" id="preview-category" style="font-size: 11px;">
                                        <?= htmlspecialchars($settings['randevuburada_category'] ?: 'Kuaför & Güzellik') ?>
                                    </span>
                                    <span class="badge bg-light text-dark border" id="preview-price-range" style="font-size: 11px;">
                                        <?= htmlspecialchars($settings['randevuburada_price_range'] ?? '₺₺') ?>
                                    </span>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" id="preview-instant" style="font-size: 11px;">
                                        <i class="fas fa-bolt me-1"></i> Anında Onay
                                    </span>
                                </div>
                            </div>
                        </div>

                        <p class="small text-muted mb-3 text-truncate-2" id="preview-bio" style="font-size: 12px; min-height: 36px;">
                            <?= htmlspecialchars($settings['company_description'] ?: 'Müşterilerinize işletmenizi anlatan kısa ve çekici bir tanıtım yazısı...') ?>
                        </p>

                        <div class="small text-muted mb-1 d-flex align-items-center" style="font-size: 12px;">
                            <i class="fas fa-map-marker-alt me-2 text-danger" style="width: 14px;"></i>
                            <span id="preview-location" class="text-truncate">
                                <?php
                                $loc_parts = array_filter([$settings['randevuburada_district'] ?? '', $settings['randevuburada_city'] ?? '']);
                                echo htmlspecialchars(!empty($loc_parts) ? implode(', ', $loc_parts) : ($settings['company_address'] ?: 'Lokasyon belirtilmedi'));
                                ?>
                            </span>
                        </div>

                        <div class="small text-muted mb-3 d-flex align-items-center" style="font-size: 12px;">
                            <i class="fas fa-phone me-2 text-success" style="width: 14px;"></i>
                            <span id="preview-phone" class="text-truncate"><?= htmlspecialchars($settings['company_phone'] ?: 'Telefon belirtilmedi') ?></span>
                        </div>

                        <div class="border-top pt-2">
                            <a href="<?= e($mp_url ?? 'https://randevuburada.kibusiness.co') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary w-100 rounded-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-external-link-alt me-2"></i> Vitrini Müşteri Gözüyle İncele
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Bilgi Kutusu: İki Yönlü Eşitleme -->
                <div class="card border-0 rounded-4 shadow-xs" style="background: #f8fafc; border: 1px dashed #cbd5e1 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-shield-alt text-primary"></i>
                            <span class="fw-bold small text-dark">Tam Entegre Veri Akışı</span>
                        </div>
                        <ul class="list-unstyled mb-0 small text-muted" style="font-size: 11.5px; line-height: 1.6;">
                            <li><i class="fas fa-check text-success me-1"></i> <strong>İşletme Profili:</strong> Bilgiler BooKi sistem ayarlarına (`ea_settings`) kaydedilir.</li>
                            <li><i class="fas fa-check text-success me-1"></i> <strong>RandevuBurada Kataloğu:</strong> Pazaryeri sunucularındaki kaydınız (`ea_tenants`) anında güncellenir.</li>
                            <li><i class="fas fa-check text-success me-1"></i> <strong>Online Randevu:</strong> Müşterilerinizin randevu adımı bu bilgilerle güncellenir.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script>
(function() {
    'use strict';

    /* BooKi - Safe CSRF Handling */
    const RB_CSRF_NAME = <?= json_encode($csrf_name ?? 'csrf_token') ?>;
    let currentCsrfHash = <?= json_encode((string) ($csrf_hash ?? vars('csrf_token'))) ?>;

    function getCsrfToken() {
        if (typeof window.vars === 'function' && window.vars('csrf_token')) {
            return window.vars('csrf_token');
        }
        return currentCsrfHash;
    }

    function initProfileBindings() {
        const nameInput = document.getElementById('f-company-name');
        const categorySelect = document.getElementById('f-category');
        const priceRangeSelect = document.getElementById('f-price-range');
        const phoneInput = document.getElementById('f-phone');
        const emailInput = document.getElementById('f-email');
        const cityInput = document.getElementById('f-city');
        const districtInput = document.getElementById('f-district');
        const addressInput = document.getElementById('f-address');
        const coverInput = document.getElementById('f-cover-image');
        const descInput = document.getElementById('f-description');
        const colorInput = document.getElementById('f-company-color');
        const activeSwitch = document.getElementById('activeSwitch');
        const instantSwitch = document.getElementById('instantBookingSwitch');

        // Preview elements
        const previewName = document.getElementById('preview-company-name');
        const previewAvatar = document.getElementById('preview-avatar');
        const previewCat = document.getElementById('preview-category');
        const previewPrice = document.getElementById('preview-price-range');
        const previewLocation = document.getElementById('preview-location');
        const previewPhone = document.getElementById('preview-phone');
        const previewBio = document.getElementById('preview-bio');
        const previewCover = document.getElementById('preview-cover-bg');
        const previewInstant = document.getElementById('preview-instant');
        const colorHexLabel = document.getElementById('color-hex-label');
        const descCharCount = document.getElementById('desc-char-count');
        const vitrinPill = document.getElementById('vitrin-pill');
        const vitrinPillText = document.getElementById('vitrin-pill-text');
        const vitrinPillIcon = document.getElementById('vitrin-pill-icon');
        const vitrinPulse = document.getElementById('vitrin-pulse');

        // Character counter
        function updateCharCount() {
            if (descInput && descCharCount) {
                const len = descInput.value.length;
                descCharCount.textContent = len + ' / 500 karakter';
            }
        }
        if (descInput) {
            descInput.addEventListener('input', function() {
                updateCharCount();
                if (previewBio) {
                    previewBio.textContent = this.value.trim() || 'Müşterilerinize işletmenizi anlatan kısa ve çekici bir tanıtım yazısı...';
                }
            });
            updateCharCount();
        }

        // Live Name & Avatar
        if (nameInput) {
            nameInput.addEventListener('input', function() {
                const val = this.value.trim() || 'İşletme Adınız';
                if (previewName) previewName.textContent = val;
                if (previewAvatar) previewAvatar.textContent = val.charAt(0).toUpperCase();
            });
        }

        // Live Category
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                if (previewCat) previewCat.textContent = this.value || 'Kuaför & Güzellik';
            });
        }

        // Live Price Range
        if (priceRangeSelect) {
            priceRangeSelect.addEventListener('change', function() {
                if (previewPrice) previewPrice.textContent = this.value || '₺₺';
            });
        }

        // Live Location
        function updateLocationPreview() {
            const city = cityInput ? cityInput.value.trim() : '';
            const dist = districtInput ? districtInput.value.trim() : '';
            const addr = addressInput ? addressInput.value.trim() : '';

            let str = '';
            if (dist && city) {
                str = dist + ', ' + city;
            } else if (city) {
                str = city;
            } else if (addr) {
                str = addr;
            } else {
                str = 'Lokasyon belirtilmedi';
            }
            if (previewLocation) previewLocation.textContent = str;
        }
        if (cityInput) cityInput.addEventListener('input', updateLocationPreview);
        if (districtInput) districtInput.addEventListener('input', updateLocationPreview);
        if (addressInput) addressInput.addEventListener('input', updateLocationPreview);

        // Live Phone
        if (phoneInput) {
            phoneInput.addEventListener('input', function() {
                if (previewPhone) previewPhone.textContent = this.value.trim() || 'Telefon belirtilmedi';
            });
        }

        // Live Cover Image
        if (coverInput) {
            coverInput.addEventListener('input', function() {
                const url = this.value.trim();
                if (previewCover) {
                    if (url) {
                        previewCover.style.backgroundImage = 'url("' + url + '")';
                    } else {
                        previewCover.style.backgroundImage = 'none';
                    }
                }
            });
        }

        // Live Brand Color
        if (colorInput) {
            colorInput.addEventListener('input', function() {
                const col = this.value;
                if (colorHexLabel) colorHexLabel.textContent = col;
                if (previewAvatar) previewAvatar.style.borderColor = col;
                const pageRoot = document.getElementById('randevuburada-profile-page');
                if (pageRoot) pageRoot.style.setProperty('--rb-brand', col);
            });
        }

        // Live Instant Booking
        if (instantSwitch) {
            instantSwitch.addEventListener('change', function() {
                if (previewInstant) {
                    if (this.checked) {
                        previewInstant.classList.remove('d-none');
                    } else {
                        previewInstant.classList.add('d-none');
                    }
                }
            });
        }

        // Live Vitrin Status Switch
        if (activeSwitch) {
            activeSwitch.addEventListener('change', function() {
                const isOn = this.checked;
                if (vitrinPill) {
                    vitrinPill.className = 'rb-pill ' + (isOn ? 'on' : 'off');
                }
                if (vitrinPillIcon) {
                    vitrinPillIcon.className = 'fas ' + (isOn ? 'fa-check-circle' : 'fa-times-circle') + ' me-1';
                }
                if (vitrinPillText) {
                    vitrinPillText.textContent = isOn ? 'Vitrin Aktif & Yayında' : 'Vitrin Kapalı (Gizli)';
                }
                if (vitrinPulse) {
                    if (isOn) vitrinPulse.classList.remove('d-none');
                    else vitrinPulse.classList.add('d-none');
                }
            });
        }

        // Copy URL button
        const copyBtn = document.getElementById('copy-url-btn');
        const urlInput = document.getElementById('mp-url-input');
        if (copyBtn && urlInput) {
            copyBtn.addEventListener('click', function() {
                navigator.clipboard.writeText(urlInput.value).then(function() {
                    const origHtml = copyBtn.innerHTML;
                    copyBtn.innerHTML = '<i class="fas fa-check text-success"></i>';
                    setTimeout(function() { copyBtn.innerHTML = origHtml; }, 2000);
                }).catch(function() {
                    urlInput.select();
                    document.execCommand('copy');
                });
            });
        }
    }

    // Save profile async
    function saveProfile() {
        const saveBtn = document.getElementById('rb-save-btn');
        const saveIcon = document.getElementById('rb-save-icon');
        const saveText = document.getElementById('rb-save-text');
        const alertBox = document.getElementById('status-alert');
        const alertTitle = document.getElementById('status-alert-title');
        const alertText = document.getElementById('status-alert-text');

        const form = document.getElementById('profile-form');
        const formData = form ? new FormData(form) : new FormData();

        // Include switches and rules
        const activeSwitch = document.getElementById('activeSwitch');
        formData.set('randevuburada_active', (activeSwitch && activeSwitch.checked) ? '1' : '0');

        const instantSwitch = document.getElementById('instantBookingSwitch');
        formData.set('randevuburada_instant_booking', (instantSwitch && instantSwitch.checked) ? '1' : '0');

        const minHours = document.getElementById('f-min-hours');
        if (minHours) {
            formData.set('randevuburada_min_notice_hours', minHours.value);
        }

        formData.append(RB_CSRF_NAME, getCsrfToken());

        // Button loading state
        if (saveBtn) saveBtn.disabled = true;
        if (saveIcon) saveIcon.className = 'fas fa-spinner fa-spin me-2';
        if (saveText) saveText.textContent = 'Kaydediliyor...';

        fetch('<?= site_url('randevuburada/save_profile') ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(res) {
            return res.json();
        })
        .then(function(data) {
            if (data.csrf_hash) {
                currentCsrfHash = data.csrf_hash;
            }
            if (saveBtn) saveBtn.disabled = false;
            if (saveIcon) saveIcon.className = 'fas fa-check me-2';
            if (saveText) saveText.textContent = 'Kaydedildi!';

            if (alertBox) {
                alertBox.className = 'alert alert-success alert-dismissible fade show rounded-3 shadow-xs mb-4';
                if (alertTitle) alertTitle.textContent = 'Başarılı!';
                if (alertText) alertText.textContent = data.message || 'Tüm vitrin ve işletme profili ayarları başarıyla eşitlendi.';
                alertBox.classList.remove('d-none');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            setTimeout(function() {
                if (saveIcon) saveIcon.className = 'fas fa-save me-2';
                if (saveText) saveText.textContent = 'Değişiklikleri Kaydet';
            }, 3000);
        })
        .catch(function(err) {
            if (saveBtn) saveBtn.disabled = false;
            if (saveIcon) saveIcon.className = 'fas fa-exclamation-triangle me-2';
            if (saveText) saveText.textContent = 'Tekrar Dene';

            if (alertBox) {
                alertBox.className = 'alert alert-danger alert-dismissible fade show rounded-3 shadow-xs mb-4';
                if (alertTitle) alertTitle.textContent = 'Hata!';
                if (alertText) alertText.textContent = 'Profil kaydedilirken bir hata oluştu: ' + (err.message || 'Bilinmeyen hata');
                alertBox.classList.remove('d-none');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initProfileBindings();
            const btn = document.getElementById('rb-save-btn');
            if (btn) btn.addEventListener('click', saveProfile);
        });
    } else {
        initProfileBindings();
        const btn = document.getElementById('rb-save-btn');
        if (btn) btn.addEventListener('click', saveProfile);
    }
})();
</script>
<?php end_section('scripts'); ?>
