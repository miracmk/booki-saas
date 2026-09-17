<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container-fluid backend-page py-3 px-md-4" style="max-width: 1400px;" id="marketing-page">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-light">
      <i class="fas fa-bullhorn me-2 text-primary"></i>
      <?= vars('page_title') ?>
    </h4>
    <div class="btn-toolbar" role="toolbar">
      <?php if (vars('initials')['can_add']): ?>
        <button class="btn btn-outline-secondary me-2" id="refresh-all-segments" title="Tüm segment boyutlarını yeniden hesapla">
          <i class="fas fa-sync-alt me-1"></i>
          Segmentleri Güncelle
        </button>
        <button class="btn btn-primary me-2" id="add-segment" title="Yeni segment oluştur">
          <i class="fas fa-plus me-1"></i>
          Yeni Segment
        </button>
        <button class="btn btn-success" id="add-campaign" title="Yeni kampanya oluştur">
          <i class="fas fa-paper-plane me-1"></i>
          Yeni Kampanya
        </button>
      <?php endif; ?>
    </div>
  </div>

  <ul class="nav nav-tabs mb-3" id="marketing-tabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="segments-tab" data-bs-toggle="tab" data-bs-target="#segments-pane" type="button" role="tab">
        <i class="fas fa-users me-1"></i>
        Segmentler
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="campaigns-tab" data-bs-toggle="tab" data-bs-target="#campaigns-pane" type="button" role="tab">
        <i class="fas fa-paper-plane me-1"></i>
        Kampanyalar
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="integrations-tab" data-bs-toggle="tab" data-bs-target="#integrations-pane" type="button" role="tab">
        <i class="fab fa-google me-1 text-danger"></i><i class="fab fa-meta me-1 text-primary"></i>
        Google & Meta Entegrasyonları
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="attributions-tab" data-bs-toggle="tab" data-bs-target="#attributions-pane" type="button" role="tab">
        <i class="fas fa-chart-line me-1 text-success"></i>
        Dönüşüm & Reklam Atıfları
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="landing-pages-tab" data-bs-toggle="tab" data-bs-target="#landing-pages-pane" type="button" role="tab">
        <i class="fas fa-laptop-code me-1 text-info"></i>
        Açılış Sayfaları (Landing Pages)
      </button>
    </li>
  </ul>

  <div class="tab-content" id="marketing-tab-content">
    <!-- 1. Segments Pane -->
    <div class="tab-pane fade show active" id="segments-pane" role="tabpanel">
      <div class="card border-0 shadow-sm">
        <div class="table-responsive">
          <table class="table table-hover mb-0" id="segments-table">
            <thead>
              <tr>
                <th>Ad</th>
                <th>Tür</th>
                <th>Kural</th>
                <th class="text-center">Üye Sayısı</th>
                <th class="text-center">Durum</th>
                <th><?= lang('actions') ?></th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 2. Campaigns Pane -->
    <div class="tab-pane fade" id="campaigns-pane" role="tabpanel">
      <div class="card border-0 shadow-sm">
        <div class="table-responsive">
          <table class="table table-hover mb-0" id="campaigns-table">
            <thead>
              <tr>
                <th>Ad</th>
                <th>Segment / Tür</th>
                <th>Kanal</th>
                <th class="text-center">Bütçe</th>
                <th class="text-center">Alıcı</th>
                <th class="text-center">Gönderildi</th>
                <th class="text-center">Başarısız</th>
                <th><?= lang('status') ?></th>
                <th><?= lang('actions') ?></th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 3. Google & Meta Integrations Pane -->
    <div class="tab-pane fade" id="integrations-pane" role="tabpanel">
      <div class="row">
        <!-- Google Suite -->
        <div class="col-lg-6 mb-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
              <h5 class="card-title mb-0 d-flex align-items-center">
                <i class="fab fa-google text-danger fs-4 me-2"></i>
                Google Pazarlama & Analitik Paketi
              </h5>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label fw-bold">Google Ads (Dönüşüm / Müşteri ID)</label>
                <input type="text" class="form-control" id="int-google-ads-id" placeholder="Örn: AW-123456789 veya 123-456-7890">
                <div class="form-text">Google Ads dönüşümlerini ve randevu değerlerini otomatik raporlar.</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Google Analytics 4 (GA4 Ölçüm Kimliği)</label>
                <input type="text" class="form-control" id="int-google-analytics-id" placeholder="Örn: G-XXXXXXXXXX">
                <div class="form-text">Tüm rezervasyon adımlarını ve sayfa akışlarını GA4 ile izler.</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Google Tag Manager (GTM Container ID)</label>
                <input type="text" class="form-control" id="int-gtm-container-id" placeholder="Örn: GTM-XXXXXXX">
                <div class="form-text">Özel etiket ve tetikleyiciler için GTM kapsayıcısı enjekte edilir.</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Google Search Console Doğrulama Kodu</label>
                <input type="text" class="form-control" id="int-google-search-console-token" placeholder="Örn: google-site-verification token veya meta içeriği">
                <div class="form-text">Arama motoru dizinleme ve organik arama analitiği için site mülkiyeti doğrular.</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Google Trends Takip Anahtar Kelimeleri</label>
                <input type="text" class="form-control" id="int-google-trends-keywords" placeholder="Örn: kuaför, cilt bakımı, masaj, rezervasyon">
                <div class="form-text">Sektörünüzde arama trendlerini ve popülerliği takip eder (virgülle ayırın).</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Google Business Profile (İşletme Profili) ID</label>
                <input type="text" class="form-control" id="int-google-business-profile-id" placeholder="Örn: locations/123456789">
                <div class="form-text">Harita ve Google İşletme profilinizden gelen randevu trafiklerini eşler.</div>
              </div>

              <!-- Trends Live Insights Box -->
              <div class="p-3 bg-light rounded border mt-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-bold small text-uppercase text-muted"><i class="fas fa-chart-line me-1"></i> Canlı Arama Trendi Özeti</span>
                  <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" id="refresh-trends-btn">Yenile</button>
                </div>
                <div id="trends-preview" class="small">
                  <div class="text-muted">Trend verileri yükleniyor...</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Meta Suite -->
        <div class="col-lg-6 mb-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
              <h5 class="card-title mb-0 d-flex align-items-center">
                <i class="fab fa-meta text-primary fs-4 me-2"></i>
                Meta (Facebook & Instagram) Paketi
              </h5>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label fw-bold">Meta Pixel Kimliği (Pixel ID)</label>
                <input type="text" class="form-control" id="int-meta-pixel-id" placeholder="Örn: 1234567890123456">
                <div class="form-text">Rezervasyon sayfalarında PageView ve Schedule olaylarını otomatik tetikler.</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Meta Dönüşümler API (CAPI) Erişim Jetonu</label>
                <textarea class="form-control" id="int-meta-capi-token" rows="2" placeholder="EAA..."></textarea>
                <div class="form-text">iOS 14+ ve reklam engelleyicileri aşarak sunucu taraflı güvenli dönüşüm gönderir.</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Meta Reklam Hesabı ID (Ad Account ID)</label>
                <input type="text" class="form-control" id="int-meta-ad-account-id" placeholder="Örn: act_123456789">
                <div class="form-text">Kampanya bütçesi ve tıklama atıflarını bağlar.</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Meta Sayfa / Instagram İşletme ID</label>
                <input type="text" class="form-control" id="int-meta-page-id" placeholder="Örn: 987654321">
                <div class="form-text">İşletme sayfası ve Instagram profil senkronizasyonu.</div>
              </div>
              <div class="mb-4">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="int-meta-status-sync-enabled">
                  <label class="form-check-label fw-bold" for="int-meta-status-sync-enabled">
                    Otomatik Durum & Promosyon Gönderi Senkronizasyonu
                  </label>
                </div>
                <div class="form-text">Yeni kampanyaları ve açılan randevu boşluklarını otomatik olarak Meta hikaye/durumunda paylaşır.</div>
              </div>

              <!-- Meta Status Sync Box -->
              <div class="p-3 bg-light rounded border">
                <h6 class="fw-bold mb-2"><i class="fab fa-instagram text-danger me-1"></i> Meta Hikaye / Durum Senkronizasyonu</h6>
                <p class="small text-muted mb-2">Profilinizde hemen paylaşılacak promosyon veya randevu duyurusu metnini test edin:</p>
                <div class="input-group mb-2">
                  <input type="text" class="form-control form-control-sm" id="meta-status-text" value="Bu haftaya özel seçili hizmetlerimizde %20 indirim! Hemen randevu alın.">
                  <button class="btn btn-sm btn-outline-primary" type="button" id="sync-meta-status-btn">
                    <i class="fas fa-paper-plane me-1"></i> Şimdi Yayınla
                  </button>
                </div>
                <div id="meta-status-result" class="small text-success d-none"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Save Integrations Action Bar -->
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">
          <span class="text-muted small">
            <i class="fas fa-shield-alt text-success me-1"></i>
            Tüm entegrasyon anahtarları kiracı veritabanında güvenli şekilde saklanır.
          </span>
          <button type="button" class="btn btn-primary px-4" id="save-integrations">
            <i class="fas fa-save me-1"></i>
            Entegrasyonları Kaydet
          </button>
        </div>
      </div>
    </div>

    <!-- 4. Attribution & Customer Extraction Pane -->
    <div class="tab-pane fade" id="attributions-pane" role="tabpanel">
      <!-- KPI Overview Cards -->
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small text-uppercase fw-bold">Toplam Reklam Tıklaması</div>
            <div class="fs-3 fw-bold text-dark mt-1" id="attr-stat-total-clicks">0</div>
            <div class="small text-primary mt-1"><i class="fas fa-mouse-pointer me-1"></i> gclid, fbclid & UTM</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small text-uppercase fw-bold">Kimliği Çıkarılan Lead</div>
            <div class="fs-3 fw-bold text-info mt-1" id="attr-stat-leads">0</div>
            <div class="small text-info mt-1"><i class="fas fa-fingerprint me-1"></i> Form & Zaman Damgası Eşleşmesi</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small text-uppercase fw-bold">Tamamlanan Randevu</div>
            <div class="fs-3 fw-bold text-success mt-1" id="attr-stat-conversions">0</div>
            <div class="small text-success mt-1"><i class="fas fa-check-circle me-1"></i> Reklam Dönüşüm Başarısı</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small text-uppercase fw-bold">Atfedilen Gelir</div>
            <div class="fs-3 fw-bold text-primary mt-1" id="attr-stat-revenue">0,00 ₺</div>
            <div class="small text-muted mt-1"><i class="fas fa-wallet me-1"></i> Satış Değeri</div>
          </div>
        </div>
      </div>

      <!-- Attributions Table Card -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold">
            <i class="fas fa-user-secret me-2 text-primary"></i>
            Reklam Ziyaretçileri, Tıklama Saatleri & Çıkarılan Müşteri Kimlikleri
          </h6>
          <button class="btn btn-sm btn-outline-secondary" id="refresh-attributions-btn">
            <i class="fas fa-sync-alt me-1"></i> Listeyi Yenile
          </button>
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0" id="attributions-table">
            <thead>
              <tr>
                <th>Kaynak / Reklam</th>
                <th>Tıklama Saati</th>
                <th>Çıkarılan Müşteri Kimliği</th>
                <th>Heatmap & Etkileşim</th>
                <th>Kampanya / UTM</th>
                <th class="text-center">Dönüşüm & Gelir</th>
                <th><?= lang('actions') ?></th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 5. Landing Pages Pane -->
    <div class="tab-pane fade" id="landing-pages-pane" role="tabpanel">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold">
            <i class="fas fa-laptop-code me-2 text-info"></i>
            Özel Kampanya Açılış Sayfaları (Landing Pages)
          </h6>
          <?php if (vars('initials')['can_add']): ?>
            <button class="btn btn-sm btn-primary" id="add-landing-page">
              <i class="fas fa-plus me-1"></i> Yeni Açılış Sayfası Ekle
            </button>
          <?php endif; ?>
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0" id="landing-pages-table">
            <thead>
              <tr>
                <th>Başlık</th>
                <th>Slug (URL)</th>
                <th>Bağlı Hizmet</th>
                <th class="text-center">Ziyaret</th>
                <th class="text-center">Dönüşüm</th>
                <th class="text-center">Dönüşüm Oranı</th>
                <th class="text-center">Durum</th>
                <th><?= lang('actions') ?></th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Segment Modal (Preserved) -->
<div class="modal fade" id="segment-modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="segment-modal-title">Yeni Segment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Ad</label>
          <input type="text" class="form-control" id="segment-name" placeholder="Örn: VIP Müşteriler">
        </div>
        <div class="mb-3">
          <label class="form-label">Tür</label>
          <select class="form-select" id="segment-type">
            <option value="vip">VIP (asgari randevu sayısı)</option>
            <option value="inactive">Pasif (uzun süredir randevu yok)</option>
            <option value="birthday">Doğum günü yaklaşanlar</option>
            <option value="all">Tüm müşteriler</option>
            <option value="custom">Özel liste</option>
          </select>
        </div>
        <div id="segment-rule-vip" class="mb-3 d-none">
          <label class="form-label">Asgari randevu sayısı</label>
          <input type="number" class="form-control" id="rule-min-appointments" min="1" value="5">
        </div>
        <div id="segment-rule-inactive" class="mb-3 d-none">
          <label class="form-label">Pasif gün sayısı</label>
          <input type="number" class="form-control" id="rule-inactive-days" min="1" value="60">
        </div>
        <div id="segment-rule-birthday" class="mb-3 d-none">
          <label class="form-label">Kutlama öncesi gün</label>
          <input type="number" class="form-control" id="rule-days-ahead" min="0" value="14">
          <div class="form-text">Doğum tarihi, müşteri kaydının «Özel Alan 1» sütununda tutulur.</div>
        </div>
        <div id="segment-rule-custom" class="mb-3 d-none">
          <label class="form-label">Müşteri ID'leri (virgülle ayırın)</label>
          <textarea class="form-control" id="rule-customer-ids" rows="3" placeholder="1, 2, 3"></textarea>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="segment-enabled" checked>
          <label class="form-check-label" for="segment-enabled">Aktif</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('cancel') ?></button>
        <button type="button" class="btn btn-primary" id="save-segment">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Campaign Modal (Expanded) -->
<div class="modal fade" id="campaign-modal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="campaign-modal-title">Yeni Kampanya</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Ad</label>
            <input type="text" class="form-control" id="campaign-name" placeholder="Örn: Yılbaşı İndirimi">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Kampanya Türü</label>
            <select class="form-select" id="campaign-type">
              <option value="broadcast">Müşteri Segmenti Yayını</option>
              <option value="google_ads">Google Ads Reklam Kampanyası</option>
              <option value="meta_ads">Meta (Facebook/Instagram) Reklam Kampanyası</option>
            </select>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3" id="campaign-segment-group">
            <label class="form-label">Hedef Segment</label>
            <select class="form-select" id="campaign-segment"></select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Kanal</label>
            <select class="form-select" id="campaign-channel">
              <option value="email">E-posta</option>
              <option value="sms">SMS</option>
              <option value="whatsapp">WhatsApp</option>
              <option value="telegram">Telegram</option>
              <option value="google_ads">Google Ads</option>
              <option value="meta_ads">Meta Ads</option>
            </select>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Bütçe (₺)</label>
            <input type="number" step="0.01" class="form-control" id="campaign-budget" placeholder="Örn: 1500.00">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Hedef / Açılış Sayfası URL</label>
            <input type="text" class="form-control" id="campaign-target-url" placeholder="Örn: /p/sonbahar-bakimi veya tam link">
          </div>
        </div>
        <div class="mb-3" id="campaign-subject-group">
          <label class="form-label">Konu (sadece e-posta)</label>
          <input type="text" class="form-control" id="campaign-subject" placeholder="Örn: Özel fırsat">
        </div>
        <div class="mb-3">
          <label class="form-label">Mesaj / Reklam Metni</label>
          <textarea class="form-control" id="campaign-message" rows="5" placeholder="Merge alanları: {{customer_name}}, {{company_name}}"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('cancel') ?></button>
        <button type="button" class="btn btn-primary" id="save-campaign">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Send Modal (Preserved) -->
<div class="modal fade" id="send-modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Kampanya Gönderimi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning mb-0" id="send-alert">Bu işlem geri alınamaz. Alıcı listesi kampanyanın hedef segmentine göre hazırlanacak ve mesajlar seçilen kanaldan gönderilecektir.</div>
        <div class="progress mt-3 d-none" id="send-progress-container">
          <div class="progress-bar" id="send-progress-bar" style="width: 0%">0%</div>
        </div>
        <p class="mt-2 mb-0 text-muted" id="send-status">Gönderim başlatılıyor...</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('cancel') ?></button>
        <button type="button" class="btn btn-success" id="confirm-send">Gönderimi Başlat</button>
      </div>
    </div>
  </div>
</div>

<!-- Landing Page Modal -->
<div class="modal fade" id="landing-page-modal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="landing-page-modal-title">Yeni Açılış Sayfası</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-7 mb-3">
            <label class="form-label fw-bold">Sayfa Başlığı</label>
            <input type="text" class="form-control" id="landing-title" placeholder="Örn: Özel Cilt Yenileme Kampanyası">
          </div>
          <div class="col-md-5 mb-3">
            <label class="form-label fw-bold">Slug (URL Uzantısı)</label>
            <div class="input-group">
              <span class="input-group-text small">/p/</span>
              <input type="text" class="form-control" id="landing-slug" placeholder="cilt-yenileme">
            </div>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Alt Başlık / Slogan (Headline)</label>
          <input type="text" class="form-control" id="landing-headline" placeholder="Örn: Doğal ışıltınızı yeniden kazanın - sınırlı kontenjan!">
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Öne Çıkarılan Hizmet</label>
            <select class="form-select" id="landing-service">
              <option value="">Hizmet seçin (isteğe bağlı)</option>
              <?php foreach ($services ?? [] as $srv): ?>
                <option value="<?= $srv['id'] ?>"><?= e($srv['name']) ?> (<?= number_format((float) ($srv['price'] ?? 0), 2, ',', '.') ?> ₺)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">CTA Buton Metni</label>
            <input type="text" class="form-control" id="landing-cta-text" value="Hemen Randevu Al">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Sayfa İçeriği & Kampanya Detayları</label>
          <textarea class="form-control" id="landing-content" rows="5" placeholder="Kampanyanızın avantajlarını, kullanılan ürünleri ve randevu detaylarını açıklayın..."></textarea>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="landing-is-active" checked>
          <label class="form-check-label fw-bold" for="landing-is-active">Sayfayı Yayına Al (Aktif)</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('cancel') ?></button>
        <button type="button" class="btn btn-primary" id="save-landing-page">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Attribution Heatmap Detail Modal -->
<div class="modal fade" id="attribution-modal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-mouse me-2 text-primary"></i> Reklam Ziyaretçi & Heatmap İncelemesi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="attribution-modal-body">
        <!-- populated via js -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
      </div>
    </div>
  </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script>
  window.scriptVars = Object.assign({}, window.scriptVars || {}, {
    segments: <?= json_encode($segments ?? []) ?>,
    campaigns: <?= json_encode($campaigns ?? []) ?>,
    landing_pages: <?= json_encode($landing_pages ?? []) ?>,
    attributions: <?= json_encode($attributions ?? []) ?>,
    services: <?= json_encode($services ?? []) ?>,
    integrations: <?= json_encode($integrations ?? []) ?>,
    initials: <?= json_encode(script_vars('initials')) ?>
  });
  var EWA = {
    segment_selected_id: <?= json_encode((int) (isset($segments[0]) ? $segments[0]['id'] : 0)) ?>,
    segments_map: <?= json_encode(array_combine(
        array_map(static fn($s) => (string) $s['id'], $segments ?? []),
        $segments ?? []
    )) ?>,
    campaigns: <?= json_encode($campaigns ?? []) ?>,
    prime_data: <?= json_encode(array_filter(array_column($segments ?? [], 'rules'))) ?>,
    initials: <?= json_encode(script_vars('initials')) ?>
  };
</script>
<script src="<?= asset_url('assets/js/pages/marketing.js') ?>"></script>

<?php end_section('scripts'); ?>