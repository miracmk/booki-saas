<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-3" id="services-page">
    <div class="row" id="services">
        <div id="filter-services" class="filter-records col col-12 mb-4">
            <button id="add-service" class="btn btn-primary add-record-btn mb-4">
                <i class="fas fa-plus-square me-2"></i>
                <?= lang('add') ?>
            </button>

            <form class="mb-4">
                <div class="input-group">
                    <input type="text" class="key form-control" aria-label="keyword">

                    <button class="filter btn btn-outline-secondary" type="submit"
                            data-tippy-content="<?= lang('filter') ?>">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <h4 class="mb-3 fw-light">
                <?= lang('services') ?>
            </h4>

            <div class="results overflow-auto" style="max-height: 650px;">
                <!-- JS -->
            </div>
        </div>

        <div class="record-details column col-12 mb-4">
            <div class="btn-toolbar mb-4">
                <div class="add-edit-delete-group btn-group">
                    <button id="edit-service" class="btn btn-outline-secondary" disabled="disabled">
                        <i class="fas fa-edit me-2"></i>
                        <?= lang('edit') ?>
                    </button>
                </div>

                <div class="save-cancel-group" style="display:none;">
                    <button id="save-service" class="btn btn-primary">
                        <i class="fas fa-check-square me-2"></i>
                        <?= lang('save') ?>
                    </button>
                    <button id="cancel-service" class="btn btn-outline-secondary">
                        <?= lang('cancel') ?>
                    </button>
                    <button id="delete-service" class="btn btn-outline-danger ms-2">
                        <i class="fas fa-trash-alt me-2"></i>
                        <?= lang('delete') ?>
                    </button>
                </div>

            </div>

            <h4 class="mb-3 fw-light">
                <?= lang('details') ?>
            </h4>

            <div class="form-message alert" style="display:none;"></div>

            <input type="hidden" id="id">

            <!-- 1. Zorunlu ve İlk Seçim Alanı: Hizmet Doğası / Tipi -->
            <div class="card border-primary border-opacity-50 bg-primary bg-opacity-10 mb-4 p-3 shadow-sm rounded-3" id="service-nature-card">
                <label class="form-label fw-bold text-dark fs-6 d-flex align-items-center mb-1" for="service-nature">
                    <i class="fas fa-layer-group text-primary me-2"></i>
                    Hizmet Doğası / Tipi <span class="text-danger ms-1">*</span>
                </label>
                <div class="form-text text-muted mb-2 small">
                    Hizmetin operasyonel modelini belirler. Alt form alanları, süre mantığı ve personel atamaları bu seçime göre şekillenir.
                </div>
                <select id="service-nature" class="form-select form-select-lg fw-semibold text-primary border-primary" disabled>
                    <option value="duration">⏱️ Süre Bazlı (Geleneksel Randevu - örn: 60 dk Klasik Cilt Bakımı)</option>
                    <option value="packaged">🔢 Adet Bazlı (Süre Takibi Geçerli / Paket - örn: 10 Seans Lazer Epilasyon)</option>
                    <option value="provider_custom_duration">👥 Adet Bazlı (Süre Özelleştirilebilir / Her Uzmana Özgü Süre - örn: Cilt Bakımı)</option>
                    <option value="daily_pass">🎟️ Günlük Pass (Tesis Kullanımı - örn: Günlük Havuz/Spa Girişi, Co-working)</option>
                    <option value="multi_pass">💳 Çok Girişli Pass (Aylık/Yıllık - örn: 1 Aylık Sınırsız Gym, 50 Girişlik Kort)</option>
                </select>
                <input type="hidden" id="access-type" value="duration">
            </div>

            <!-- Ortak Sabit Alanlar (Tüm Tiplerde Ortak) -->
            <div class="mb-3">
                <label class="form-label" for="name">
                    <?= lang('name') ?>
                    <span class="text-danger" hidden>*</span>
                </label>
                <input id="name" class="form-control required" maxlength="128" placeholder="Örn: Medikal Klasik Cilt Bakımı" disabled>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="service-category-id">
                        <?= lang('category') ?>
                    </label>
                    <select id="service-category-id" class="form-select" disabled></select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="tax-rate">
                        Vergi / KDV Oranı (%)
                    </label>
                    <select id="tax-rate" class="form-select" disabled>
                        <option value="20.00">%20 (Standart Hizmet KDV)</option>
                        <option value="10.00">%10 (İndirimli Hizmet / Sağlık KDV)</option>
                        <option value="1.00">%1 (Temel İhtiyaç)</option>
                        <option value="0.00">%0 (Muaf / İstisna)</option>
                    </select>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-8">
                    <label class="form-label" for="price">
                        <?= lang('price') ?> <span id="price-unit-label">(Toplam Fiyat)</span>
                        <span class="text-danger" hidden>*</span>
                    </label>
                    <input id="price" class="form-control required" placeholder="0.00" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="currency">
                        <?= lang('currency') ?>
                    </label>
                    <input id="currency" class="form-control" maxlength="32" placeholder="₺" disabled>
                </div>
            </div>

            <!-- DİNAMİK BÖLÜM 1: Süre ve Takvim Ayarları (Süre Bazlı & Adet Bazlı Süreli) -->
            <div id="section-timed-settings" class="card border bg-light mb-3 p-3">
                <div class="fw-semibold text-dark mb-2">
                    <i class="fas fa-clock text-info me-2"></i>Randevu Süre & Kapasite Ayarları
                </div>
                <!-- Paket Seans Adedi (Sadece Adet Bazlı Süreli için) -->
                <div class="mb-3" id="package-sessions-container" style="display:none;">
                    <label class="form-label fw-bold" for="total-passes">
                        Toplam Paket / Seans Adedi <span class="text-danger">*</span>
                    </label>
                    <input id="total-passes" class="form-control" type="number" min="1" value="10" placeholder="Örn: 10 Seans" disabled>
                    <div class="form-text small text-muted">Danışanın satın aldığı toplam hak adedi. Her seans randevu takviminde zaman kaplar.</div>
                </div>

                <div class="row g-2">
                    <div class="col-md-4" id="duration-container">
                        <label class="form-label" for="duration">
                            <span id="duration-label"><?= lang('duration_minutes') ?></span>
                            <span class="text-danger" hidden>*</span>
                        </label>
                        <input id="duration" class="form-control required" type="number" min="<?= EVENT_MINIMUM_DURATION ?>" value="60" disabled>
                    </div>

                    <div class="col-md-4" id="slot-interval-container">
                        <label class="form-label" for="slot-interval">
                            <?= lang('slot_interval') ?> (Buffer)
                            <span class="text-danger" hidden>*</span>
                        </label>
                        <input id="slot-interval" class="form-control required" type="number" min="1" value="15" disabled>
                    </div>

                    <div class="col-md-4" id="attendants-number-container">
                        <label class="form-label" for="attendants-number">
                            <?= lang('attendants_number') ?>
                            <span class="text-danger" hidden>*</span>
                        </label>
                        <input id="attendants-number" class="form-control required" type="number" min="1" value="1" disabled>
                    </div>
                </div>
            </div>

            <!-- DİNAMİK BÖLÜM 2: Günlük Pass Ayarları -->
            <div id="section-daily-pass-settings" class="card border border-warning-subtle bg-warning bg-opacity-10 mb-3 p-3" style="display:none;">
                <div class="fw-semibold text-dark mb-2">
                    <i class="fas fa-ticket text-warning me-2"></i>Günlük Pass Giriş Kuralları
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label" for="valid-hours-start">Geçerlilik Başlangıç Saati</label>
                        <input type="time" id="valid-hours-start" class="form-control" value="09:00" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="valid-hours-end">Geçerlilik Bitiş Saati</label>
                        <input type="time" id="valid-hours-end" class="form-control" value="18:00" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="daily-capacity">Günlük Kapasite / Kota (Kişi)</label>
                        <input type="number" id="daily-capacity" class="form-control" min="1" placeholder="Örn: 50" disabled>
                    </div>
                </div>
                <div class="form-text small text-muted">
                    Günlük pass alan müşteriler belirlenen saatler arasında tesisi kullanabilir. Takvimde bireysel personel slotu rezerve edilmez.
                </div>
            </div>

            <!-- DİNAMİK BÖLÜM 3: Çok Girişli Pass Ayarları -->
            <div id="section-multi-pass-settings" class="card border border-success-subtle bg-success bg-opacity-10 mb-3 p-3" style="display:none;">
                <div class="fw-semibold text-dark mb-2">
                    <i class="fas fa-id-card text-success me-2"></i>Abonelik & Kota Kuralları
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label" for="pass-validity-days">Geçerlilik Periyodu</label>
                        <select id="pass-validity-days" class="form-select" disabled>
                            <option value="30">1 Ay (30 Gün)</option>
                            <option value="90">3 Ay (90 Gün)</option>
                            <option value="180">6 Ay (180 Gün)</option>
                            <option value="365">1 Yıl (365 Gün)</option>
                            <option value="14">2 Hafta (14 Gün)</option>
                            <option value="7">1 Hafta (7 Gün)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="multi-pass-quota">Kullanım Hakkı (Giriş Kotası)</label>
                        <div class="input-group">
                            <select id="multi-pass-quota-type" class="form-select" disabled>
                                <option value="unlimited">Sınırsız Giriş</option>
                                <option value="fixed">Belirli Adet Giriş</option>
                            </select>
                            <input type="number" id="multi-pass-quota-number" class="form-control" placeholder="Adet" min="1" value="30" style="display:none;" disabled>
                        </div>
                    </div>
                </div>
                <div class="form-text small text-muted">
                    Geçerlilik periyodu boyunca müşteri tesis turnikesinden veya check-in ekranından kartı okutarak giriş yapar.
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="location">
                    <?= lang('location') ?>
                </label>
                <input id="location" class="form-control" placeholder="Oda / Salon / Şube" disabled>
            </div>

            <div class="mb-3">
                <?php component('color_selection', ['attributes' => 'id="color"']); ?>
            </div>

            <div class="border rounded mb-3 p-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is-private">
                    <label class="form-check-label fw-semibold" for="is-private">
                        <?= lang('hide_from_public') ?>
                    </label>
                </div>
                <div class="form-text text-muted">
                    <small><?= lang('private_hint') ?></small>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="description">
                    <?= lang('description') ?>
                </label>
                <textarea id="description" rows="3" class="form-control" placeholder="Hizmet açıklaması, kapsamı ve danışan bilgilendirme notları..." disabled></textarea>
            </div>

            <!-- DİNAMİK BÖLÜM 4: Hizmet Sağlayanlar (Personel Seçimi) -->
            <div id="section-providers" class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label fw-bold mb-0">
                        <i class="fas fa-user-check text-primary me-1"></i><?= lang('providers') ?>
                    </label>
                    <div class="btn-group btn-group-sm">
                        <button type="button" id="select-all-providers" class="btn btn-outline-secondary" disabled>
                            <?= lang('select_all') ?>
                        </button>
                        <button type="button" id="select-none-providers" class="btn btn-outline-secondary" disabled>
                            <?= lang('select_none') ?>
                        </button>
                    </div>
                </div>
                <div id="service-providers" class="card card-body border mb-3">
                    <?php foreach (vars('providers') as $provider): ?>
                        <div class="form-check d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                            <div>
                                <input class="form-check-input provider-checkbox" type="checkbox"
                                       id="provider-<?= $provider['id'] ?>"
                                       data-id="<?= $provider['id'] ?>" disabled>
                                <label class="form-check-label fw-semibold" for="provider-<?= $provider['id'] ?>">
                                    <?= e($provider['first_name'] . ' ' . $provider['last_name']) ?>
                                </label>
                            </div>
                            <div class="provider-custom-duration-container d-flex align-items-center gap-1" style="display:none;">
                                <span class="small text-muted">Özel Süre:</span>
                                <input type="number" class="form-control form-control-sm provider-duration-input" style="width: 75px;" min="5" step="5" data-provider-id="<?= $provider['id'] ?>" placeholder="60" disabled>
                                <span class="small text-muted">dk</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ALT MODÜLLER (3 Ana Operasyonel Süreç) -->
            <!-- 1. Ek Hizmetler & Opsiyonlar (Add-ons) -->
            <div class="card border mb-3 shadow-sm rounded-3" id="service-addons-card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold text-dark"><i class="fas fa-puzzle-piece text-primary me-2"></i>1. Ek Hizmetler & Opsiyonlar (Add-ons)</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-addon-modal">
                        <i class="fas fa-plus me-1"></i>Ek Hizmet Ekle
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="service-addons-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Ek Hizmet</th>
                                    <th>Ek Süre (dk)</th>
                                    <th>Ek Fiyat</th>
                                    <th class="text-end">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-muted text-center py-3"><td colspan="4">Kayıtlı ek hizmet bulunamadı.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 2. Otomatik Stok Sarfiyat Reçetesi (Recipe) -->
            <div class="card border mb-3 shadow-sm rounded-3" id="service-consumables-card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <div>
                        <span class="fw-semibold text-dark"><i class="fas fa-boxes-stacked text-warning me-2"></i>2. Otomatik Stok Sarfiyat Reçetesi (Recipe)</span>
                        <small class="text-muted d-block" style="font-size:11px;">Randevu tamamlandığında stoktan otomatik düşecek sarf malzemeler</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-warning" id="btn-add-consumable-modal">
                        <i class="fas fa-plus me-1"></i>Sarf Malzeme Ekle
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="service-consumables-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Ürün / Malzeme</th>
                                    <th>Kullanılan Miktar</th>
                                    <th>Birim Maliyet</th>
                                    <th>Toplam Maliyet</th>
                                    <th>Mevcut Stok</th>
                                    <th class="text-end">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-muted text-center py-3"><td colspan="6">Reçeteye ekli sarf malzeme bulunamadı.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light border-top py-2" id="service-consumables-summary" style="display:none;">
                    <div class="row text-center g-2">
                        <div class="col-4">
                            <small class="text-muted d-block" style="font-size:11px;">Toplam Sarf Maliyeti</small>
                            <span class="fw-bold text-danger" id="summary-total-cost">₺0.00</span>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block" style="font-size:11px;">Hizmet Satış Fiyatı</small>
                            <span class="fw-bold text-dark" id="summary-service-price">₺0.00</span>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block" style="font-size:11px;">Tahmini Brüt Kâr (Marj)</small>
                            <span class="fw-bold text-success" id="summary-gross-profit">₺0.00 (%0)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Takip Süreçleri (Klinik SOAP, İlaç Protokolü & Cilt Görsel Takibi - Follow-Up Engine) -->
            <div class="card border mb-4 shadow-sm rounded-3" id="service-follow-up-card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <div>
                        <span class="fw-semibold text-dark"><i class="fas fa-notes-medical text-primary me-2"></i>3. Takip Süreçleri (Klinik SOAP, İlaç & Cilt Görsel Takip Engine)</span>
                        <small class="text-muted d-block" style="font-size:11px;">Hizmet tamamlandığında otomatik tetiklenen klinik SOAP, reçete hatırlatma ve fotoğraf durum kontrolleri</small>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" id="follow-up-required" disabled>
                        <label class="form-check-label fw-bold text-primary small" for="follow-up-required">Takip Aktif</label>
                    </div>
                </div>
                <div class="card-body p-3" id="follow-up-config-body" style="display: none;">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark" for="follow-up-category">
                                <i class="fas fa-stethoscope me-1 text-info"></i>Ana Takip Protokolü / SOAP Türü *
                            </label>
                            <select id="follow-up-category" class="form-select">
                                <option value="medical_reaction">🩺 Klinik SOAP & Reaksiyon/Komplikasyon Kontrolü (Hekim/Klinik)</option>
                                <option value="medical_protocol">💊 İlaç Kullanımı & Tedavi Protokolü ("İlacınızı içmeyi unutmayın")</option>
                                <option value="photo_checkin">📸 Görsel / Fotoğraf Durum Kontrolü ("Cildinizdeki durum nedir? Fotoğraf paylaşınız")</option>
                                <option value="aftercare_safety">🛡️ Lazer / Peeling / Operasyon Sonrası Güvenlik & Bakım Talimatı</option>
                                <option value="diet_form">🥗 Beslenme / Diyet Günlüğü Takip Formu</option>
                                <option value="routine_check">📅 Periyodik Kontrol & Seans Geri Çağırma (Kontrol Randevusu)</option>
                                <option value="retention_marketing">🔄 Paket Seans Tüketim & Yenileme Hatırlatması</option>
                            </select>
                            <div class="form-text small text-muted">Hizmet tamamlandığında danışana uygulanacak birincil klinik veya operasyonel takip.</div>
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
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-dark" for="follow-up-delay-override">
                                <i class="fas fa-hourglass-half me-1 text-secondary"></i>Varsayılan Tetiklenme
                            </label>
                            <input type="text" id="follow-up-delay-override" class="form-control" placeholder="Örn: 24 hours" value="24 hours">
                            <div class="mt-1 d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 btn-quick-delay" data-delay="0 minutes">Hemen</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 btn-quick-delay" data-delay="2 hours">2 Saat</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 btn-quick-delay" data-delay="24 hours">24 Saat</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 btn-quick-delay" data-delay="3 days">3 Gün</button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold small text-dark mb-0" for="follow-up-message-override">
                                <i class="fab fa-whatsapp text-success me-1"></i>Varsayılan Takip Mesajı Şablonu
                            </label>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary btn-xs py-0 btn-template-pill" data-type="medication">
                                    💊 İlaç Şablonu
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-xs py-0 btn-template-pill" data-type="photo">
                                    📸 Cilt Görseli Şablonu
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-xs py-0 btn-template-pill" data-type="soap">
                                    🩺 SOAP Kontrol Şablonu
                                </button>
                            </div>
                        </div>
                        <textarea id="follow-up-message-override" rows="2" class="form-control" placeholder="Örn: Sayın {{customer_name}}, {{service_name}} işlemi sonrası hekiminizin reçete ettiği ilaçları saatinde almayı lütfen unutmayınız..."></textarea>
                    </div>

                    <!-- Takip Adımları & Otomasyon Zaman Çizelgesi -->
                    <div class="card border border-primary border-opacity-25 rounded-3 mb-2">
                        <div class="card-header bg-primary bg-opacity-10 py-2 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold text-primary small"><i class="fas fa-list-ol me-2"></i>Klinik Takip Adımları ve Zaman Çizelgesi</span>
                                <small class="text-muted d-block" style="font-size:11px;">Hizmet sonrası belirlenen saat ve günlerde otomatik iletilecek adımları düzenleyin</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-primary py-1 px-2" id="btn-add-follow-up-modal">
                                    <i class="fas fa-plus me-1"></i>Yeni Adım Ekle
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="service-follow-up-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 25%;">Tetikleyici</th>
                                            <th style="width: 15%;">Zamanlama</th>
                                            <th style="width: 15%;">Kanal</th>
                                            <th style="width: 35%;">Eylem / Mesaj</th>
                                            <th style="width: 10%;" class="text-end">İşlem</th>
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
    </div>
</div>

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
                    <label class="form-label">Ek Hizmet Adı *</label>
                    <input type="text" class="form-control" id="addon-name-input" placeholder="Örn: Saç Bakım Maskesi, Masaj Yağı Aromaterapi">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Ek Süre (Dakika)</label>
                        <input type="number" class="form-control" id="addon-duration-input" value="15" min="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Ek Fiyat (₺)</label>
                        <input type="number" step="0.01" class="form-control" id="addon-price-input" value="0.00">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Açıklama</label>
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
                    <label class="form-label">Ürün / Stok Kalemi *</label>
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
                        <label class="form-label">Kullanılan Miktar *</label>
                        <input type="number" step="0.01" class="form-control" id="consumable-qty-input" value="1.00">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Birim</label>
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

<!-- Modal: Yeni / Düzenle Takip Kuralı & Adımı (CRM & Follow-up) -->
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
                            <option value="immediate">Hemen (0 dk)</option>
                            <option value="2_hours">2 Saat Sonra</option>
                            <option value="12_hours">12 Saat Sonra</option>
                            <option value="24_hours" selected>24 Saat Sonra</option>
                            <option value="48_hours">48 Saat Sonra</option>
                            <option value="3_days">3 Gün Sonra</option>
                            <option value="1_week">1 Hafta Sonra</option>
                            <option value="30_days">30 Gün Sonra</option>
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
                        <option value="medical_protocol">💊 İlaç Kullanımı & Tedavi Protokolü Hatırlatması</option>
                        <option value="medical_reaction">🩺 Klinik SOAP & Reaksiyon/Ağrı Kontrolü</option>
                        <option value="photo_checkin">📸 Görsel / Fotoğraf Durum Kontrolü (Fotoğraf Talebi)</option>
                        <option value="aftercare_safety">🛡️ Bakım Sonrası Talimatları & Güvenlik</option>
                        <option value="review_nps">⭐ Memnuniyet & NPS Anketi</option>
                        <option value="renewal_reminder">🔄 Paket Yenileme & Özel Teklif</option>
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

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/http/services_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/service_categories_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/services.js') ?>"></script>

<?php end_section('scripts'); ?>
