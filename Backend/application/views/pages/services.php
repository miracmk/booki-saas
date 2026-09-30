<?php extend('layouts/backend_layout'); ?>

<?php section('styles'); ?>
<style>
/* Genişlikleri Sayfaya Tam Sığdırma & Ferah Alan */
#services-page,
#services-page .container-fluid,
#services-page .record-details,
#services-page .filter-records,
#services-page #services {
    max-width: 100% !important;
    width: 100% !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
}

.services-kpi-card {
    transition: transform .15s ease, box-shadow .15s ease;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.08);
}
.services-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
}

.service-color-pill {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
    box-shadow: 0 0 0 2px #fff, 0 0 0 3px rgba(0,0,0,0.15);
}

.nav-service-sections .nav-link {
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    padding: 10px 18px;
    color: #4b5563;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    transition: all .2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}
.nav-service-sections .nav-link:hover {
    background: #f1f5f9;
    color: #1e293b;
}
.nav-service-sections .nav-link.active {
    background: #4338ca;
    color: #ffffff;
    border-color: #4338ca;
    box-shadow: 0 4px 12px rgba(67, 56, 202, 0.25);
}
.nav-service-sections .nav-link.active .badge {
    background: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
}

.table-custom-services tbody tr {
    transition: background-color .15s ease;
}
.table-custom-services tbody tr:hover {
    background-color: rgba(67, 56, 202, 0.04);
}

.service-section-box {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
    margin-bottom: 24px;
}
</style>
<?php end_section('styles'); ?>

<?php section('content'); ?>

<div class="container-fluid px-3 px-md-4 backend-page py-3" id="services-page">

    <!-- ========================================== -->
    <!-- GÖRÜNÜM 1: GENİŞ ÖZEL HİZMET TABLOSU       -->
    <!-- ========================================== -->
    <div id="services-table-view">
        <!-- Başlık & Üst İşlem Butonları -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1 d-flex align-items-center">
                    <i class="fas fa-sparkles text-primary me-2"></i>Hizmetler & Bakım Kataloğu
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 ms-3" id="badge-total-services">0 Hizmet</span>
                </h4>
                <p class="text-muted small mb-0">İşletmenizde sunulan tüm bakım, seans, paket ve üyelik modellerini tek ekrandan yönetin.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-primary px-3 py-2 fw-semibold shadow-sm" id="btn-create-service">
                    <i class="fas fa-plus-circle me-2"></i>Yeni Hizmet Ekle
                </button>
            </div>
        </div>

        <!-- KPI Özet İstatistik Kartları -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card services-kpi-card bg-white p-3 border-0 shadow-sm">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-3 bg-primary bg-opacity-10 text-primary me-3">
                            <i class="fas fa-spa fs-4"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Toplam Hizmet</small>
                            <h4 class="fw-bold mb-0 text-dark" id="kpi-total-services">0</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card services-kpi-card bg-white p-3 border-0 shadow-sm">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-3 bg-success bg-opacity-10 text-success me-3">
                            <i class="fas fa-calendar-check fs-4"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Bu Ayki Randevular</small>
                            <h4 class="fw-bold mb-0 text-success" id="kpi-monthly-bookings">0</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card services-kpi-card bg-white p-3 border-0 shadow-sm">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-3 bg-info bg-opacity-10 text-info me-3">
                            <i class="fas fa-user-check fs-4"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Aktif Personel Eşleşmesi</small>
                            <h4 class="fw-bold mb-0 text-dark" id="kpi-active-providers">0</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card services-kpi-card bg-white p-3 border-0 shadow-sm">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 p-3 bg-warning bg-opacity-10 text-warning me-3">
                            <i class="fas fa-tags fs-4"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Ortalama Ücret</small>
                            <h4 class="fw-bold mb-0 text-dark" id="kpi-avg-price">₺0</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtre ve Arama Çubuğu -->
        <div class="card border-0 shadow-sm mb-4 rounded-3">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 ps-0" id="table-search-input" placeholder="Hizmet adı veya açıklama ile ara...">
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <select class="form-select" id="table-category-filter">
                            <option value="">Tüm Kategoriler</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <select class="form-select" id="table-nature-filter">
                            <option value="">Tüm Hizmet Çeşitleri</option>
                            <option value="duration">⏱️ Süre Bazlı</option>
                            <option value="packaged">🔢 Adet / Paket Seans</option>
                            <option value="provider_custom_duration">👥 Uzmana Özgü Süre</option>
                            <option value="daily_pass">🎟️ Günlük Pass</option>
                            <option value="multi_pass">💳 Çok Girişli Pass</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select class="form-select" id="table-branch-filter">
                            <option value="">Tüm Şubeler / Konum</option>
                            <?php foreach (vars('branches') ?? [] as $b): ?>
                                <option value="<?= e($b['name']) ?>"><?= e($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-1 text-end">
                        <button type="button" class="btn btn-outline-secondary w-100" id="btn-reset-table-filters" title="Filtreleri Sıfırla">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Özel Hizmet Veri Tablosu (Custom Table) -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-custom-services align-middle mb-0" id="services-custom-table">
                        <thead class="table-light text-secondary small text-uppercase">
                            <tr>
                                <th style="width: 25%;" class="ps-3">Hizmet Adı & Çeşidi</th>
                                <th style="width: 14%;">Ücreti</th>
                                <th style="width: 12%;">Süre / Kota</th>
                                <th style="width: 12%;">Konum (Şube)</th>
                                <th style="width: 12%;">Bu Ay Alınma</th>
                                <th style="width: 11%;">Tanımlı Personel</th>
                                <th style="width: 7%;">Durum</th>
                                <th style="width: 7%;" class="text-end pe-3">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- JavaScript ile dinamik render edilir -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- GÖRÜNÜM 2: TAM GENİŞLİK HİZMET EDİTÖRÜ     -->
    <!-- ========================================== -->
    <div id="services-editor-view" style="display: none;">
        <!-- Üst Başlık & Kaydet / İptal Aksiyon Barı -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 sticky-top bg-white" style="top: 10px; z-index: 1020;">
            <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" id="btn-back-to-table">
                        <i class="fas fa-arrow-left me-1"></i>Hizmet Tablosuna Dön
                    </button>
                    <div>
                        <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2" id="editor-service-title">
                            Yeni Hizmet Tanımla
                        </h5>
                        <small class="text-muted" id="editor-service-subtitle">Operasyonel model, sarfiyat reçetesi, ek hizmetler, takip sistemi ve sözleşmeler</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button id="cancel-service" type="button" class="btn btn-outline-secondary px-3">
                        <i class="fas fa-times me-1"></i>Vazgeç
                    </button>
                    <button id="delete-service" type="button" class="btn btn-outline-danger px-3" style="display:none;">
                        <i class="fas fa-trash-alt me-1"></i>Hizmeti Sil
                    </button>
                    <button id="save-service" type="button" class="btn btn-primary px-4 fw-semibold shadow-sm">
                        <i class="fas fa-check-circle me-1"></i>Hizmeti Kaydet
                    </button>
                </div>
            </div>
        </div>

        <div class="form-message alert" style="display:none;"></div>
        <input type="hidden" id="id">

        <!-- Alt Bölümler Sekme Menüsü (5 Ana Bölüm) -->
        <ul class="nav nav-pills nav-service-sections mb-4 flex-wrap gap-2" id="service-section-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-btn-general" data-bs-toggle="pill" data-bs-target="#tab-pane-general" type="button" role="tab">
                    <i class="fas fa-sliders-h text-primary"></i>1. Genel & Operasyonel Model
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-btn-consumables" data-bs-toggle="pill" data-bs-target="#tab-pane-consumables" type="button" role="tab">
                    <i class="fas fa-boxes-stacked text-warning"></i>2. Otomatik Sarfiyat Reçetesi
                    <span class="badge bg-secondary rounded-pill" id="badge-tab-consumables">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-btn-addons" data-bs-toggle="pill" data-bs-target="#tab-pane-addons" type="button" role="tab">
                    <i class="fas fa-puzzle-piece text-info"></i>3. Ek Hizmetler (Add-ons)
                    <span class="badge bg-secondary rounded-pill" id="badge-tab-addons">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-btn-followup" data-bs-toggle="pill" data-bs-target="#tab-pane-followup" type="button" role="tab">
                    <i class="fas fa-notes-medical text-success"></i>4. Takip Sistemi & SOAP
                    <span class="badge bg-secondary rounded-pill" id="badge-tab-followup">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-btn-contracts" data-bs-toggle="pill" data-bs-target="#tab-pane-contracts" type="button" role="tab">
                    <i class="fas fa-file-signature text-danger"></i>5. İlgili Sözleşmeler & Onam
                    <span class="badge bg-secondary rounded-pill" id="badge-tab-contracts">0</span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="service-section-tabs-content">

            <!-- ======================================================== -->
            <!-- ALT BÖLÜM 1: GENEL & OPERASYONEL MODEL (DOĞRU SIRALAMA) -->
            <!-- ======================================================== -->
            <div class="tab-pane fade show active" id="tab-pane-general" role="tabpanel">
                <div class="service-section-box p-4">

                    <!-- 1.1 Zorunlu ve İlk Seçim: Hizmet Doğası / Tipi -->
                    <div class="card border-primary border-opacity-50 bg-primary bg-opacity-10 mb-4 p-3 shadow-sm rounded-3" id="service-nature-card">
                        <label class="form-label fw-bold text-dark fs-6 d-flex align-items-center mb-1" for="service-nature">
                            <i class="fas fa-layer-group text-primary me-2"></i>
                            Hizmet Doğası / Operasyonel Model <span class="text-danger ms-1">*</span>
                        </label>
                        <div class="form-text text-muted mb-2 small">
                            Hizmetin randevu ve salon operasyonundaki işleyiş modelini belirler. Alt form alanları, süre kurgusu ve personel atamaları bu seçime göre otomatik şekillenir.
                        </div>
                        <select id="service-nature" class="form-select form-select-lg fw-semibold text-primary border-primary">
                            <option value="duration">⏱️ Süre Bazlı (Geleneksel Randevu - örn: 60 dk Klasik Cilt Bakımı)</option>
                            <option value="packaged">🔢 Adet Bazlı (Süre Takibi Geçerli / Paket - örn: 10 Seans Lazer Epilasyon)</option>
                            <option value="provider_custom_duration">👥 Adet Bazlı (Süre Özelleştirilebilir / Her Uzmana Özgü Süre - örn: Cilt Bakımı)</option>
                            <option value="daily_pass">🎟️ Günlük Pass (Tesis Kullanımı - örn: Günlük Havuz/Spa Girişi, Co-working)</option>
                            <option value="multi_pass">💳 Çok Girişli Pass (Aylık/Yıllık - örn: 1 Aylık Sınırsız Gym, 50 Girişlik Kort)</option>
                        </select>
                        <input type="hidden" id="access-type" value="duration">
                    </div>

                    <!-- 1.2 Temel Bilgiler: Adı, Kategori, Konum (Dropdown -> Branches), Renk, Gizlilik -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold" for="name">
                                <?= lang('name') ?> <span class="text-danger">*</span>
                            </label>
                            <input id="name" class="form-control form-control-lg required" maxlength="128" placeholder="Örn: Medikal Klasik Cilt Bakımı">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="service-category-id">
                                <?= lang('category') ?>
                            </label>
                            <select id="service-category-id" class="form-select form-select-lg"></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" for="location">
                                <i class="fas fa-map-marker-alt text-primary me-1"></i>Konum / Şube (Branch)
                            </label>
                            <!-- KONUM ARTIK DROPDOWN VE BRANCHES TABLOSUNA BAĞLI -->
                            <select id="location" class="form-select form-select-lg">
                                <option value="">-- Tüm Şubeler (Genel) --</option>
                                <?php foreach (vars('branches') ?? [] as $b): ?>
                                    <option value="<?= e($b['name']) ?>"><?= e($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4 align-items-center">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-1" for="color">
                                <i class="fas fa-palette text-secondary me-1"></i><?= lang('color') ?> (Takvim Rengi)
                            </label>
                            <?php component('color_selection', ['attributes' => 'id="color"']); ?>
                        </div>
                        <div class="col-md-6 pt-md-3">
                            <div class="border rounded-3 p-3 bg-light d-flex align-items-center justify-content-between">
                                <div>
                                    <label class="form-check-label fw-bold small text-dark mb-0" for="is-private">
                                        <?= lang('hide_from_public') ?> (Özel / Gizli Hizmet)
                                    </label>
                                    <div class="text-muted" style="font-size: 11px;">Açık olduğunda online rezervasyon sayfasında listelenmez, sadece dahili oluşturulabilir.</div>
                                </div>
                                <div class="form-check form-switch m-0 fs-5">
                                    <input class="form-check-input" type="checkbox" id="is-private">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 1.3 Fiyatlandırma & KDV -->
                    <div class="card border bg-light mb-4 p-3 rounded-3">
                        <div class="fw-bold text-dark mb-2">
                            <i class="fas fa-tag text-success me-2"></i>Fiyatlandırma & Vergi
                        </div>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold" for="price">
                                    <?= lang('price') ?> <span id="price-unit-label" class="text-muted small">(Toplam Fiyat)</span>
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input id="price" class="form-control required" placeholder="0.00">
                                    <input id="currency" class="form-control" style="max-width: 80px;" maxlength="32" placeholder="₺" value="₺">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" for="tax-rate">
                                    Vergi / KDV Oranı (%)
                                </label>
                                <select id="tax-rate" class="form-select">
                                    <option value="20.00">%20 (Standart Hizmet KDV)</option>
                                    <option value="10.00">%10 (İndirimli Hizmet / Sağlık KDV)</option>
                                    <option value="1.00">%1 (Temel İhtiyaç)</option>
                                    <option value="0.00">%0 (Muaf / İstisna)</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="small text-muted pb-2">
                                    <i class="fas fa-info-circle me-1"></i>Adisyon ve e-arşiv faturalarında bu KDV oranı geçerli olur.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 1.4 Süre, Seans & Kapasite Kuralları (Hizmet Çeşidine Göre Dinamik Gösterim) -->
                    <!-- SÜRE & RANDEVU TAKVİM AYARLARI (Süre Bazlı & Adet Bazlı Süreli) -->
                    <div id="section-timed-settings" class="card border border-info-subtle bg-info bg-opacity-10 mb-4 p-3 rounded-3">
                        <div class="fw-bold text-dark mb-2">
                            <i class="fas fa-clock text-info me-2"></i>Randevu Süre & Kapasite Ayarları
                        </div>
                        <!-- Paket Seans Adedi (Sadece Adet Bazlı Süreli için) -->
                        <div class="mb-3" id="package-sessions-container" style="display:none;">
                            <label class="form-label fw-bold" for="total-passes">
                                Toplam Paket / Seans Adedi <span class="text-danger">*</span>
                            </label>
                            <input id="total-passes" class="form-control" type="number" min="1" value="10" placeholder="Örn: 10 Seans">
                            <div class="form-text small text-muted">Danışanın satın aldığı toplam seans hakkı. Her seans randevu takviminde zaman kaplar.</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4" id="duration-container">
                                <label class="form-label fw-semibold" for="duration">
                                    <span id="duration-label"><?= lang('duration_minutes') ?></span>
                                    <span class="text-danger">*</span>
                                </label>
                                <input id="duration" class="form-control required" type="number" min="<?= EVENT_MINIMUM_DURATION ?>" value="60">
                            </div>
                            <div class="col-md-4" id="slot-interval-container">
                                <label class="form-label fw-semibold" for="slot-interval">
                                    <?= lang('slot_interval') ?> (Buffer / Aralık dk)
                                    <span class="text-danger">*</span>
                                </label>
                                <input id="slot-interval" class="form-control required" type="number" min="1" value="15">
                            </div>
                            <div class="col-md-4" id="attendants-number-container">
                                <label class="form-label fw-semibold" for="attendants-number">
                                    <?= lang('attendants_number') ?> (Eşzamanlı Kapasite)
                                    <span class="text-danger">*</span>
                                </label>
                                <input id="attendants-number" class="form-control required" type="number" min="1" value="1">
                            </div>
                        </div>
                    </div>

                    <!-- GÜNLÜK PASS AYARLARI -->
                    <div id="section-daily-pass-settings" class="card border border-warning-subtle bg-warning bg-opacity-10 mb-4 p-3 rounded-3" style="display:none;">
                        <div class="fw-bold text-dark mb-2">
                            <i class="fas fa-ticket text-warning me-2"></i>Günlük Pass Giriş Kuralları
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" for="valid-hours-start">Geçerlilik Başlangıç Saati</label>
                                <input type="time" id="valid-hours-start" class="form-control" value="09:00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" for="valid-hours-end">Geçerlilik Bitiş Saati</label>
                                <input type="time" id="valid-hours-end" class="form-control" value="18:00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" for="daily-capacity">Günlük Kapasite / Kota (Kişi)</label>
                                <input type="number" id="daily-capacity" class="form-control" min="1" placeholder="Örn: 50">
                            </div>
                        </div>
                        <div class="form-text small text-muted">
                            Günlük pass alan müşteriler belirlenen saat aralığında tesisi kullanabilir. Takvimde bireysel personel slotu bloke edilmez.
                        </div>
                    </div>

                    <!-- ÇOK GİRİŞLİ PASS (ABONELİK) AYARLARI -->
                    <div id="section-multi-pass-settings" class="card border border-success-subtle bg-success bg-opacity-10 mb-4 p-3 rounded-3" style="display:none;">
                        <div class="fw-bold text-dark mb-2">
                            <i class="fas fa-id-card text-success me-2"></i>Abonelik & Kota Kuralları
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="pass-validity-days">Geçerlilik Periyodu</label>
                                <select id="pass-validity-days" class="form-select">
                                    <option value="30">1 Ay (30 Gün)</option>
                                    <option value="90">3 Ay (90 Gün)</option>
                                    <option value="180">6 Ay (180 Gün)</option>
                                    <option value="365">1 Yıl (365 Gün)</option>
                                    <option value="14">2 Hafta (14 Gün)</option>
                                    <option value="7">1 Hafta (7 Gün)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="multi-pass-quota">Kullanım Hakkı (Giriş Kotası)</label>
                                <div class="input-group">
                                    <select id="multi-pass-quota-type" class="form-select">
                                        <option value="unlimited">Sınırsız Giriş</option>
                                        <option value="fixed">Belirli Adet Giriş</option>
                                    </select>
                                    <input type="number" id="multi-pass-quota-number" class="form-control" placeholder="Adet" min="1" value="30" style="display:none;">
                                </div>
                            </div>
                        </div>
                        <div class="form-text small text-muted">
                            Geçerlilik süresi boyunca müşteri tesis turnikesinden kart okutarak veya check-in ekranından giriş sağlar.
                        </div>
                    </div>

                    <!-- 1.5 Açıklama & Notlar -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="description">
                            <?= lang('description') ?> & Danışan Bilgilendirme Notları
                        </label>
                        <textarea id="description" rows="3" class="form-control" placeholder="Hizmet açıklaması, kapsamı, danışanın işlem öncesi bilmesi gerekenler..."></textarea>
                    </div>

                    <!-- 1.6 Hizmet Sağlayan Personeller -->
                    <div id="section-providers" class="mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold mb-0 text-dark">
                                <i class="fas fa-user-check text-primary me-2"></i><?= lang('providers') ?> (Bu Hizmeti Veren Personeller)
                            </label>
                            <div class="btn-group btn-group-sm">
                                <button type="button" id="select-all-providers" class="btn btn-outline-secondary">
                                    <?= lang('select_all') ?>
                                </button>
                                <button type="button" id="select-none-providers" class="btn btn-outline-secondary">
                                    <?= lang('select_none') ?>
                                </button>
                            </div>
                        </div>
                        <div id="service-providers" class="card card-body border p-3 rounded-3">
                            <div class="row g-2">
                                <?php foreach (vars('providers') as $provider): ?>
                                    <div class="col-md-6">
                                        <div class="form-check d-flex justify-content-between align-items-center p-2 rounded border bg-light">
                                            <div class="d-flex align-items-center">
                                                <input class="form-check-input provider-checkbox me-2" type="checkbox"
                                                       id="provider-<?= $provider['id'] ?>"
                                                       data-id="<?= $provider['id'] ?>">
                                                <label class="form-check-label fw-semibold" for="provider-<?= $provider['id'] ?>">
                                                    <?= e($provider['first_name'] . ' ' . $provider['last_name']) ?>
                                                </label>
                                            </div>
                                            <div class="provider-custom-duration-container d-flex align-items-center gap-1" style="display:none;">
                                                <span class="small text-muted" style="font-size:11px;">Özel Süre:</span>
                                                <input type="number" class="form-control form-control-sm provider-duration-input" style="width: 70px;" min="5" step="5" data-provider-id="<?= $provider['id'] ?>" placeholder="60">
                                                <span class="small text-muted" style="font-size:11px;">dk</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ======================================================== -->
            <!-- ALT BÖLÜM 2: OTOMATİK SARFİYAT REÇETESİ (RECIPE)        -->
            <!-- ======================================================== -->
            <div class="tab-pane fade" id="tab-pane-consumables" role="tabpanel">
                <div class="service-section-box p-4" id="service-consumables-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">
                                <i class="fas fa-boxes-stacked text-warning me-2"></i>Otomatik Stok Sarfiyat Reçetesi (Recipe)
                            </h5>
                            <small class="text-muted">Bu hizmet tamamlandığında stoktan otomatik düşecek sarf malzemeleri ve birim maliyetlerini tanımlayın.</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-warning px-3 fw-semibold" id="btn-add-consumable-modal">
                            <i class="fas fa-plus me-1"></i>Sarf Malzeme Ekle
                        </button>
                    </div>

                    <div class="table-responsive rounded border mb-3">
                        <table class="table table-hover align-middle mb-0" id="service-consumables-table">
                            <thead class="table-light text-secondary small text-uppercase">
                                <tr>
                                    <th>Ürün / Sarf Malzeme</th>
                                    <th>Kullanılan Miktar</th>
                                    <th>Birim Maliyet</th>
                                    <th>Toplam Maliyet</th>
                                    <th>Mevcut Stok</th>
                                    <th class="text-end pe-3">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-muted text-center py-4"><td colspan="6">Reçeteye ekli sarf malzeme bulunamadı.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="card bg-light border-0 rounded-3 p-3" id="service-consumables-summary" style="display:none;">
                        <div class="row text-center g-3">
                            <div class="col-4">
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Toplam Sarf Maliyeti</small>
                                <span class="fw-bold text-danger fs-5" id="summary-total-cost">₺0.00</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Hizmet Satış Fiyatı</small>
                                <span class="fw-bold text-dark fs-5" id="summary-service-price">₺0.00</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Tahmini Brüt Kâr & Marj</small>
                                <span class="fw-bold text-success fs-5" id="summary-gross-profit">₺0.00 (%0)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- ALT BÖLÜM 3: EK HİZMETLER (ADD-ONS)                     -->
            <!-- ======================================================== -->
            <div class="tab-pane fade" id="tab-pane-addons" role="tabpanel">
                <div class="service-section-box p-4" id="service-addons-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">
                                <i class="fas fa-puzzle-piece text-info me-2"></i>Ek Hizmetler & Opsiyonlar (Add-ons)
                            </h5>
                            <small class="text-muted">Danışanın randevu oluştururken veya işlem sırasında ekleyebileceği opsiyonel ek hizmetler.</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary px-3 fw-semibold" id="btn-add-addon-modal">
                            <i class="fas fa-plus me-1"></i>Ek Hizmet Ekle
                        </button>
                    </div>

                    <div class="table-responsive rounded border mb-3">
                        <table class="table table-hover align-middle mb-0" id="service-addons-table">
                            <thead class="table-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-3">Ek Hizmet Adı</th>
                                    <th>Ek Süre (dk)</th>
                                    <th>Ek Fiyat (₺)</th>
                                    <th class="text-end pe-3">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-muted text-center py-4"><td colspan="4">Kayıtlı ek hizmet bulunamadı.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- ALT BÖLÜM 4: TAKİP SİSTEMİ & KLİNİK SOAP (FOLLOW-UP)    -->
            <!-- ======================================================== -->
            <div class="tab-pane fade" id="tab-pane-followup" role="tabpanel">
                <div class="service-section-box p-4" id="service-follow-up-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">
                                <i class="fas fa-notes-medical text-success me-2"></i>Takip Süreçleri & Klinik SOAP Engine
                            </h5>
                            <small class="text-muted">Hizmet tamamlandıktan sonra danışana otomatik iletilecek bakım sonrası talimatları, ilaç protokolü ve görsel kontroller.</small>
                        </div>
                        <div class="form-check form-switch m-0 fs-5 d-flex align-items-center gap-2">
                            <label class="form-check-label fw-bold text-primary small mb-0" for="follow-up-required">Takip Aktif</label>
                            <input class="form-check-input" type="checkbox" id="follow-up-required">
                        </div>
                    </div>

                    <div id="follow-up-config-body" style="display: none;">
                        <div class="row g-3 mb-3">
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-dark" for="follow-up-category">
                                    <i class="fas fa-stethoscope me-1 text-info"></i>Takip Protokolü / SOAP Türü *
                                </label>
                                <select id="follow-up-category" class="form-select">
                                    <option value="medical_reaction">🩺 Klinik SOAP & Reaksiyon/Komplikasyon Kontrolü</option>
                                    <option value="medical_protocol">💊 İlaç Kullanımı & Tedavi Protokolü</option>
                                    <option value="photo_checkin">📸 Görsel / Fotoğraf Durum Kontrolü</option>
                                    <option value="aftercare_safety">🛡️ Lazer / Peeling / Operasyon Sonrası Bakım</option>
                                    <option value="diet_form">🥗 Beslenme / Diyet Günlüğü Takip Formu</option>
                                    <option value="routine_check">📅 Periyodik Kontrol & Geri Çağırma (Kontrol Randevusu)</option>
                                    <option value="retention_marketing">🔄 Paket Seans Tüketim & Yenileme</option>
                                </select>
                                <div class="form-text small text-muted">Hizmet tamamlandığında danışana uygulanacak takip protokolü.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-dark" for="follow-up-priority">
                                    <i class="fas fa-shield-alt me-1 text-warning"></i>Öncelik Derecesi
                                </label>
                                <select id="follow-up-priority" class="form-select">
                                    <option value="critical">🚨 Kritik (Tıbbi / Opt-out Bypass)</option>
                                    <option value="standard" selected>⭐ Standart (Önemli Takip)</option>
                                    <option value="optional">ℹ️ İsteğe Bağlı</option>
                                </select>
                                <div class="form-text small text-muted">Kritik takipler hastanın tıbbi güvenliği için iletilir.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-dark" for="follow-up-delay-override">
                                    <i class="fas fa-hourglass-half me-1 text-secondary"></i>Tetiklenme Zamanı
                                </label>
                                <input type="text" id="follow-up-delay-override" class="form-control" placeholder="Örn: 24 hours" value="24 hours">
                                <div class="mt-1 d-flex flex-wrap gap-1">
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 btn-quick-delay" data-delay="0 minutes">Hemen</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 btn-quick-delay" data-delay="2 hours">2 Saat</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 btn-quick-delay" data-delay="24 hours">24 Saat</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 btn-quick-delay" data-delay="48 hours">48 Saat</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 btn-quick-delay" data-delay="3 days">3 Gün</button>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold small text-dark mb-0" for="follow-up-message-override">
                                    <i class="fab fa-whatsapp text-success me-1"></i>WhatsApp / SMS Takip Mesajı Şablonu
                                </label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary btn-xs py-0 btn-template-pill" data-type="medication">
                                        💊 İlaç Şablonu
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-xs py-0 btn-template-pill" data-type="photo">
                                        📸 Cilt Görseli
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-xs py-0 btn-template-pill" data-type="soap">
                                        🩺 SOAP Kontrol
                                    </button>
                                </div>
                            </div>
                            <textarea id="follow-up-message-override" rows="3" class="form-control" placeholder="Örn: Sayın {{customer_name}}, {{service_name}} işlemi sonrası uzmanımızın talimatlarına uymayı lütfen unutmayınız..."></textarea>
                            <div class="form-text small text-muted d-flex justify-content-between align-items-center mt-1">
                                <span>Dinamik Değişkenler: <code>{{customer_name}}</code>, <code>{{service_name}}</code>, <code>{{provider_name}}</code>, <code>{{booking_url}}</code></span>
                                <span class="badge bg-light text-secondary border">Follow-Up Engine Entegre</span>
                            </div>
                        </div>

                        <!-- Takip Adımları & Otomasyon Zaman Çizelgesi -->
                        <div class="card border border-primary border-opacity-25 rounded-3 mb-2">
                            <div class="card-header bg-primary bg-opacity-10 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold text-primary small"><i class="fas fa-list-ol me-2"></i>Klinik Takip Adımları ve Zaman Çizelgesi</span>
                                    <small class="text-muted d-block" style="font-size:11px;">Hizmet sonrası belirlenen saat ve günlerde otomatik iletilecek adımları düzenleyin</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary py-1 px-3" id="btn-add-follow-up-modal">
                                    <i class="fas fa-plus me-1"></i>Yeni Adım Ekle
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="service-follow-up-table">
                                        <thead class="table-light text-secondary small text-uppercase">
                                            <tr>
                                                <th style="width: 25%;" class="ps-3">Tetikleyici</th>
                                                <th style="width: 15%;">Zamanlama</th>
                                                <th style="width: 15%;">Kanal</th>
                                                <th style="width: 35%;">Eylem / Mesaj</th>
                                                <th style="width: 10%;" class="text-end pe-3">İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="text-muted text-center py-3"><td colspan="5">Kayıtlı takip adımı bulunamadı.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- ALT BÖLÜM 5: İLGİLİ SÖZLEŞMELER & ONAM FORMLARI (WAIVERS) -->
            <!-- ======================================================== -->
            <div class="tab-pane fade" id="tab-pane-contracts" role="tabpanel">
                <div class="service-section-box p-4" id="service-contracts-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">
                                <i class="fas fa-file-signature text-danger me-2"></i>İlgili Sözleşmeler & Dijital Onam Formları
                            </h5>
                            <small class="text-muted">Bu hizmet alındığında danışanın dijital veya fiziksel onaylaması gereken feragatname, KVKK ve uygulama sözleşmeleri.</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger px-3 fw-semibold" id="btn-add-contract-modal">
                            <i class="fas fa-plus me-1"></i>Yeni Sözleşme Şablonu Ekle
                        </button>
                    </div>

                    <div class="table-responsive rounded border mb-3">
                        <table class="table table-hover align-middle mb-0" id="service-contracts-table">
                            <thead class="table-light text-secondary small text-uppercase">
                                <tr>
                                    <th style="width: 35%;" class="ps-3">Sözleşme / Onam Metni</th>
                                    <th style="width: 15%;">Zorunluluk</th>
                                    <th style="width: 25%;">Bu Hizmete Bağla</th>
                                    <th style="width: 15%;">Metin Önizleme</th>
                                    <th style="width: 10%;" class="text-end pe-3">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-muted text-center py-4"><td colspan="5"><i class="fas fa-spinner fa-spin me-2"></i>Sözleşmeler yükleniyor...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- ======================================================== -->
<!-- MODALLAR                                                -->
<!-- ======================================================== -->

<!-- Modal: Ek Hizmet Ekle -->
<div class="modal fade" id="modal-addon-form" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-puzzle-piece text-primary me-2"></i>Ek Hizmet Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Ek Hizmet Adı *</label>
                    <input type="text" class="form-control" id="addon-name-input" placeholder="Örn: Saç Bakım Maskesi, Masaj Yağı Aromaterapi">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Ek Süre (Dakika)</label>
                        <input type="number" class="form-control" id="addon-duration-input" value="15" min="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Ek Fiyat (₺)</label>
                        <input type="number" step="0.01" class="form-control" id="addon-price-input" value="0.00">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Açıklama</label>
                    <textarea class="form-control" id="addon-desc-input" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="btn-save-addon-submit">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Sarf Malzeme Ekle -->
<div class="modal fade" id="modal-consumable-form" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-boxes-stacked text-warning me-2"></i>Sarf Malzeme Reçetesi Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Ürün / Stok Kalemi *</label>
                    <select class="form-select" id="consumable-product-select">
                        <option value="">-- Ürün Seçin --</option>
                        <?php foreach (vars('products') ?? [] as $prod): ?>
                            <option value="<?= $prod['id'] ?>" data-stock="<?= $prod['stock_quantity'] ?? 0 ?>" data-unit="<?= e($prod['unit'] ?? 'adet') ?>" data-cost="<?= (float) ($prod['cost_price'] ?? 0) ?>">
                                <?= e($prod['name']) ?> (Stok: <?= $prod['stock_quantity'] ?? 0 ?> <?= e($prod['unit'] ?? 'adet') ?> - Maliyet: ₺<?= number_format((float)($prod['cost_price'] ?? 0), 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Kullanılan Miktar *</label>
                        <input type="number" step="0.01" class="form-control" id="consumable-qty-input" value="1.00">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Birim</label>
                        <input type="text" class="form-control" id="consumable-unit-display" value="adet" readonly>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-warning" id="btn-save-consumable-submit">Reçeteye Ekle</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Takip Kuralı / Adımı Ekle & Düzenle (Temizlenmiş) -->
<div class="modal fade" id="modal-follow-up-form" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-follow-up-title"><i class="fas fa-notes-medical text-primary me-2"></i>Takip Adımı Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="follow-up-rule-index" value="-1">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tetikleyici Olay *</label>
                    <select class="form-select" id="follow-up-trigger-select">
                        <option value="appointment_completed">Randevu / İşlem Tamamlandığında</option>
                        <option value="package_near_expiry">Paket Bitimine 1 Seans Kala</option>
                        <option value="service_purchased">Hizmet / Paket Satın Alındığında</option>
                        <option value="checkin_done">Giriş / Check-in Yapıldığında</option>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Zamanlama / Gecikme *</label>
                        <select class="form-select" id="follow-up-delay-select">
                            <option value="immediate">⚡ Hemen (0 dk)</option>
                            <option value="2_hours">⏱️ 2 Saat Sonra</option>
                            <option value="12_hours">⏱️ 12 Saat Sonra</option>
                            <option value="24_hours" selected>⏱️ 24 Saat Sonra</option>
                            <option value="48_hours">⏱️ 48 Saat Sonra</option>
                            <option value="3_days">📅 3 Gün Sonra</option>
                            <option value="1_week">📅 1 Hafta Sonra</option>
                            <option value="30_days">📅 30 Gün Sonra</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">İletişim Kanalı *</label>
                        <select class="form-select" id="follow-up-channel-select">
                            <option value="whatsapp">💬 WhatsApp</option>
                            <option value="sms">📱 SMS</option>
                            <option value="email">✉️ E-Posta</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Otomasyon Eylemi / Protokol Türü *</label>
                    <select class="form-select" id="follow-up-action-select">
                        <option value="review_nps">⭐ Memnuniyet & NPS Anketi Gönder</option>
                        <option value="renewal_reminder">🔄 Paket Yenileme & Özel Teklif Gönder</option>
                        <option value="medical_protocol">💊 İlaç Kullanımı & Tedavi Protokolü Hatırlatması</option>
                        <option value="medical_reaction">🩺 Klinik SOAP & Reaksiyon/Ağrı Kontrolü</option>
                        <option value="photo_checkin">📸 Görsel / Fotoğraf Durum Kontrolü (Fotoğraf Talebi)</option>
                        <option value="aftercare_safety">🛡️ Bakım Sonrası Talimatları & Güvenlik</option>
                        <option value="tag_vip">🏷️ Müşteriye "VIP" Etiketi Ekle</option>
                    </select>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold mb-0">Mesaj Metni / Talimat *</label>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 btn-insert-var" data-var="{{customer_name}}">+ Danışan</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 btn-insert-var" data-var="{{service_name}}">+ Hizmet</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 btn-insert-var" data-var="{{provider_name}}">+ Uzman</button>
                        </div>
                    </div>
                    <textarea class="form-control" id="follow-up-message-input" rows="3" placeholder="Örn: Merhaba Sayın {{customer_name}}, {{service_name}} işlemi sonrasında..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="btn-save-follow-up-submit">
                    <i class="fas fa-check me-1"></i>Adımı Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Yeni Sözleşme / Onam Formu Şablonu Ekle -->
<div class="modal fade" id="modal-contract-form" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-contract text-danger me-2"></i>Yeni Sözleşme / Onam Şablonu Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sözleşme / Onam Başlığı *</label>
                    <input type="text" class="form-control" id="contract-title-input" placeholder="Örn: Lazer Epilasyon Bilgilendirilmiş Onam ve Hizmet Sözleşmesi">
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="contract-mandatory-input" checked>
                        <label class="form-check-label fw-semibold" for="contract-mandatory-input">Zorunlu İmzalanması Gereken Sözleşme</label>
                    </div>
                    <div class="form-text small text-muted">Danışanın randevu öncesinde onaylaması zorunlu tutulur.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sözleşme / Onam Metni *</label>
                    <textarea class="form-control" id="contract-content-input" rows="5" placeholder="İşbu sözleşme kapsamında danışan işlem şartlarını, olası geçici reaksiyonları ve bakım kurallarını kabul eder..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-danger" id="btn-save-contract-submit">Şablonu Oluştur & Hizmete Bağla</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Sözleşme Metni Önizleme -->
<div class="modal fade" id="modal-contract-preview" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contract-preview-title"><i class="fas fa-file-alt text-primary me-2"></i>Sözleşme Önizleme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 bg-light rounded border font-monospace small" id="contract-preview-body" style="white-space: pre-wrap; max-height: 450px; overflow-y: auto;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/http/services_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/service_categories_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/services.js') ?>"></script>

<?php end_section('scripts'); ?>
