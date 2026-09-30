<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container-fluid backend-page py-3 px-md-4" style="max-width: 1400px;" id="marketing-page">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0 fw-bold text-dark">
        <i class="fas fa-bullhorn me-2 text-primary"></i>
        Pazarlama & Reklam Merkezi
      </h4>
      <div class="text-muted small mt-1">Google Ads, Meta Ads (Instagram/Facebook), Dönüşüm Takibi ve Müşteri Havuzu</div>
    </div>
    <div class="btn-toolbar gap-2" role="toolbar">
      <?php if (vars('initials')['can_add']): ?>
        <button class="btn btn-outline-secondary btn-sm" id="btn-assign-customer-segment" title="Müşteriyi Özel Segmente Taşı">
          <i class="fas fa-user-tag me-1"></i>
          Segmente Müşteri Ekle
        </button>
        <button class="btn btn-primary" id="btn-open-new-campaign" title="Yeni reklam kampanyası oluştur">
          <i class="fas fa-plus-circle me-1"></i>
          Yeni Kampanya Oluştur
        </button>
      <?php endif; ?>
    </div>
  </div>

  <?php 
    $g_conn = vars('google_connected');
    $m_conn = vars('meta_connected');
    if (!$g_conn || !$m_conn): 
  ?>
  <div class="alert alert-light border border-primary-subtle d-flex align-items-center justify-content-between p-3 mb-3 rounded-3 shadow-sm" role="alert" style="background: #f8fafc;">
    <div class="d-flex align-items-center gap-3">
      <div style="font-size:24px; color:#3b82f6; line-height:1;">
        <i class="fas fa-info-circle"></i>
      </div>
      <div>
        <h6 class="mb-1 fw-bold text-dark" style="font-size:14px;">
          Google & Meta Reklam ve İletişim Entegrasyonları
        </h6>
        <p class="mb-0 text-muted" style="font-size:12.5px;">
          Google Ads, Meta Ads (Instagram, Facebook), Threads ve WhatsApp Cloud API entegrasyon ayarları <strong>Ayarlar &gt; Entegrasyonlar</strong> sekmesine taşınmıştır. Tek tıkla hesaplarınızı bağlayabilirsiniz.
        </p>
      </div>
    </div>
    <div class="ms-3">
      <a href="<?= site_url('settings?tab=integrations#social-settings') ?>" class="btn btn-outline-primary btn-sm fw-bold px-3 py-2 shadow-sm text-nowrap" style="border-radius:8px;">
        <i class="fas fa-plug me-1"></i> Entegrasyonlar Sayfasına Git
      </a>
    </div>
  </div>
  <?php endif; ?>

  <!-- Main Navigation Tabs -->
  <ul class="nav nav-tabs mb-3" id="marketing-tabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active fw-semibold" id="campaigns-tab" data-bs-toggle="tab" data-bs-target="#campaigns-pane" type="button" role="tab">
        <i class="fas fa-ad me-1 text-primary"></i>
        Kampanyalar (Google & Meta)
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link fw-semibold" id="attributions-tab" data-bs-toggle="tab" data-bs-target="#attributions-pane" type="button" role="tab">
        <i class="fas fa-chart-line me-1 text-success"></i>
        Dönüşümler (Leads & Satışlar)
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link fw-semibold" id="landing-pages-tab" data-bs-toggle="tab" data-bs-target="#landing-pages-pane" type="button" role="tab">
        <i class="fas fa-laptop-code me-1 text-info"></i>
        Açılış Sayfaları (Landing Pages)
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link fw-semibold" id="segments-tab" data-bs-toggle="tab" data-bs-target="#segments-pane" type="button" role="tab">
        <i class="fas fa-users-cog me-1 text-secondary"></i>
        Segmentasyon (Müşteri Havuzu)
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link fw-semibold" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews-pane" type="button" role="tab">
        <i class="fas fa-star me-1 text-warning"></i>
        Müşteri Değerlendirmeleri
      </button>
    </li>
  </ul>

  <div class="tab-content" id="marketing-tab-content">
    
    <!-- 1. CAMPAIGNS PANE (PRIMARY DEFAULT) -->
    <div class="tab-pane fade show active" id="campaigns-pane" role="tabpanel">
      <!-- KPI Metric Cards -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-md-4 col-xl">
          <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted small fw-bold text-uppercase">Toplam Harcama</span>
              <span class="badge bg-danger-subtle text-danger"><i class="fas fa-coins"></i></span>
            </div>
            <div class="fs-4 fw-bold text-dark mt-2" id="kpi-total-spend">0,00 ₺</div>
            <div class="small text-muted mt-1"><i class="fab fa-google text-danger me-1"></i> Google + <i class="fab fa-meta text-primary me-1"></i> Meta</div>
          </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
          <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted small fw-bold text-uppercase">Gösterimler</span>
              <span class="badge bg-primary-subtle text-primary"><i class="fas fa-eye"></i></span>
            </div>
            <div class="fs-4 fw-bold text-primary mt-2" id="kpi-total-impressions">0</div>
            <div class="small text-muted mt-1">Reklam Görüntülenme</div>
          </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
          <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted small fw-bold text-uppercase">Tıklama & TBM</span>
              <span class="badge bg-info-subtle text-info"><i class="fas fa-mouse-pointer"></i></span>
            </div>
            <div class="fs-4 fw-bold text-info mt-2" id="kpi-total-clicks">0</div>
            <div class="small text-muted mt-1">Ort. TBM: <strong id="kpi-avg-cpc">0,00 ₺</strong></div>
          </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
          <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted small fw-bold text-uppercase">Dönüşümler (Lead)</span>
              <span class="badge bg-success-subtle text-success"><i class="fas fa-check-circle"></i></span>
            </div>
            <div class="fs-4 fw-bold text-success mt-2" id="kpi-total-conversions">0</div>
            <div class="small text-muted mt-1">Randevu & Form Başarısı</div>
          </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
          <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted small fw-bold text-uppercase">Ortalama ROAS</span>
              <span class="badge bg-warning-subtle text-warning"><i class="fas fa-chart-line"></i></span>
            </div>
            <div class="fs-4 fw-bold text-dark mt-2" id="kpi-avg-roas">0.00x</div>
            <div class="small text-success mt-1"><i class="fas fa-arrow-trend-up me-1"></i> Reklam Getirisi</div>
          </div>
        </div>
      </div>

      <!-- Performance Visualizer & Filter Bar -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
          <div class="row align-items-center g-3">
            <div class="col-md-6">
              <div class="d-flex align-items-center gap-2">
                <span class="fw-bold small text-muted text-uppercase me-2">Platform Filtresi:</span>
                <div class="btn-group btn-group-sm" role="group">
                  <button type="button" class="btn btn-outline-secondary active campaign-filter-btn" data-filter="all">Tümü</button>
                  <button type="button" class="btn btn-outline-danger campaign-filter-btn" data-filter="google_ads"><i class="fab fa-google me-1"></i> Google Ads</button>
                  <button type="button" class="btn btn-outline-primary campaign-filter-btn" data-filter="meta_ads"><i class="fab fa-meta me-1"></i> Meta Ads</button>
                  <button type="button" class="btn btn-outline-dark campaign-filter-btn" data-filter="broadcast"><i class="fas fa-envelope me-1"></i> Yayın / İletişim</button>
                </div>
              </div>
            </div>
            <div class="col-md-6 text-md-end">
              <div class="d-inline-flex align-items-center gap-3 small text-muted">
                <div><span class="badge rounded-circle p-1 bg-danger me-1">&nbsp;</span> Google Ads: <strong id="bar-google-spend">0 ₺</strong></div>
                <div><span class="badge rounded-circle p-1 bg-primary me-1">&nbsp;</span> Meta Ads: <strong id="bar-meta-spend">0 ₺</strong></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Campaigns Table -->
      <div class="card border-0 shadow-sm">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="campaigns-table">
            <thead class="table-light">
              <tr>
                <th style="min-width: 220px;">Kampanya & Platform</th>
                <th>Tür / Reklam Grubu</th>
                <th class="text-end">Günlük Bütçe</th>
                <th class="text-center">Gösterim</th>
                <th class="text-center">Tıklama</th>
                <th class="text-center">Ort. TBM</th>
                <th class="text-end">Harcama</th>
                <th class="text-center">Dönüşüm</th>
                <th class="text-center">ROAS</th>
                <th class="text-center">Durum</th>
                <th class="text-end" style="min-width: 140px;"><?= lang('actions') ?></th>
              </tr>
            </thead>
            <tbody>
              <!-- Rendered via JS -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 2. ATTRIBUTIONS PANE (DÖNÜŞÜMLER) -->
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
            <div class="text-muted small text-uppercase fw-bold">Atfedilen Toplam Gelir</div>
            <div class="fs-3 fw-bold text-primary mt-1" id="attr-stat-revenue">0,00 ₺</div>
            <div class="small text-muted mt-1"><i class="fas fa-wallet me-1"></i> Tamamlanan Satış Değeri</div>
          </div>
        </div>
      </div>

      <!-- Attributions Table Card -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0 fw-bold">
              <i class="fas fa-user-secret me-2 text-primary"></i>
              Google & Meta Reklam Ziyaretçileri, Tıklama Kaynakları ve Lead Dönüşümleri
            </h6>
            <small class="text-muted">Gelen müşterilerin hangi reklam kampanyasından, anahtar kelimeden veya reklam setinden geldiği takip edilir.</small>
          </div>
          <button class="btn btn-sm btn-outline-secondary" id="refresh-attributions-btn">
            <i class="fas fa-sync-alt me-1"></i> Listeyi Yenile
          </button>
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0" id="attributions-table">
            <thead class="table-light">
              <tr>
                <th>Kaynak & Platform</th>
                <th>Tıklama Zamanı</th>
                <th>Müşteri / Lead Bilgisi</th>
                <th>Heatmap & Etkileşim</th>
                <th>Kampanya / UTM / Keyword</th>
                <th class="text-center">Dönüşüm & Gelir</th>
                <th class="text-end"><?= lang('actions') ?></th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 3. LANDING PAGES PANE (AÇILIŞ SAYFALARI) -->
    <div class="tab-pane fade" id="landing-pages-pane" role="tabpanel">
      <!-- Cookie Consent Banner Notice -->
      <div class="card border-0 shadow-sm mb-3 bg-light-subtle">
        <div class="card-body p-3 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-3">
            <div class="fs-3 text-warning"><i class="fas fa-cookie-bite"></i></div>
            <div>
              <div class="fw-bold">KVKK & Çerez Onay Bildirimi (Cookie Consent) Entegre</div>
              <div class="small text-muted">Açılış sayfalarında ve online rezervasyonda ziyaretçilere otomatik çerez onay bildirimi sunulur, pikseller onay sonrasında tetiklenir.</div>
            </div>
          </div>
          <div>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-semibold">
              <i class="fas fa-shield-alt me-1"></i> KVKK & GDPR Uyumlu Aktif
            </span>
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0 fw-bold">
              <i class="fas fa-laptop-code me-2 text-info"></i>
              Kampanyaya Özel Açılış Sayfaları (Landing Pages)
            </h6>
            <small class="text-muted">Özel URL uzantıları (slug), Meta Pixel, Google Tag Manager ve UTM parametreleri ile yüksek dönüşüm sağlayan sayfalar.</small>
          </div>
          <?php if (vars('initials')['can_add']): ?>
            <button class="btn btn-sm btn-primary" id="add-landing-page">
              <i class="fas fa-plus me-1"></i> Yeni Açılış Sayfası Ekle
            </button>
          <?php endif; ?>
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0 align-middle" id="landing-pages-table">
            <thead class="table-light">
              <tr>
                <th>Sayfa Başlığı</th>
                <th>Slug (URL)</th>
                <th>Bağlı Hizmet</th>
                <th class="text-center">Ziyaret</th>
                <th class="text-center">Dönüşüm</th>
                <th class="text-center">Dönüşüm Oranı</th>
                <th class="text-center">Durum</th>
                <th class="text-end"><?= lang('actions') ?></th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 4. SEGMENTS PANE (SEGMENTASYON & MÜŞTERİ HAVUZU) -->
    <div class="tab-pane fade" id="segments-pane" role="tabpanel">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h6 class="mb-0 fw-bold">Müşteri Segmentleri & Özel Hedefleme Havuzları</h6>
          <small class="text-muted">Müşterilerinizi kural bazlı (VIP, Pasif, Doğum Günü) veya özel listelere ayırarak reklam ve duyurularınızda hedef kitle olarak kullanın.</small>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary btn-sm" id="refresh-all-segments">
            <i class="fas fa-sync-alt me-1"></i> Segmentleri Yenile
          </button>
          <button class="btn btn-primary btn-sm" id="add-segment">
            <i class="fas fa-plus me-1"></i> Yeni Segment
          </button>
        </div>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="segments-table">
            <thead class="table-light">
              <tr>
                <th>Segment Adı</th>
                <th>Tür</th>
                <th>Kural / Koşul</th>
                <th class="text-center">Müşteri Sayısı</th>
                <th class="text-center">Durum</th>
                <th class="text-end"><?= lang('actions') ?></th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 5. REVIEWS PANE (MÜŞTERİ DEĞERLENDİRMELERİ & RANDEVUBURADA) -->
    <div class="tab-pane fade" id="reviews-pane" role="tabpanel">
      <div class="alert alert-info border-0 shadow-sm d-flex align-items-center justify-content-between p-3 mb-3 rounded-3" style="background:#eff6ff;">
        <div class="d-flex align-items-center gap-3">
          <div style="font-size:24px; color:#2563eb; line-height:1;">
            <i class="fas fa-store"></i>
          </div>
          <div>
            <h6 class="mb-1 fw-bold text-dark" style="font-size:14px;">
              RandevuBurada Vitrin Yorum Entegrasyonu
            </h6>
            <p class="mb-0 text-muted" style="font-size:12.5px;">
              İşletmenize gelen gerçek müşteri değerlendirmelerini onaylayabilir ve tercihinize göre <strong>RandevuBurada Vitrin Profilinizde</strong> yayınlanmasını sağlayabilirsiniz.
            </p>
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold">
            <i class="fas fa-comments text-primary me-2"></i>
            Müşteri Yorumları & Değerlendirmeler
          </h6>
          <span class="badge bg-secondary-subtle text-secondary px-3 py-1">Toplam: <?= count($reviews ?? []) ?> Değerlendirme</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="reviews-table">
            <thead class="table-light">
              <tr>
                <th>Müşteri</th>
                <th>Puan</th>
                <th>Yorum Metni</th>
                <th>Hizmet / Uzman</th>
                <th>Tarih</th>
                <th class="text-center">RandevuBurada'da Yayınla</th>
                <th class="text-center">Durum</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($reviews)): ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">Henüz müşteri değerlendirmesi bulunmamaktadır.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($reviews as $rev): ?>
                  <tr>
                    <td class="fw-semibold">
                      <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary fw-bold" style="width:34px; height:34px; font-size:12px;">
                          <?= mb_substr($rev['customer_name_display'] ?? 'M', 0, 1) ?>
                        </div>
                        <div>
                          <div><?= e($rev['customer_name_display'] ?? 'Anonim') ?></div>
                          <small class="text-muted">Randevu #<?= $rev['appointment_id'] ?></small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="text-warning">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                          <i class="<?= $i <= (int)($rev['rating'] ?? 0) ? 'fas' : 'far' ?> fa-star"></i>
                        <?php endfor; ?>
                        <span class="ms-1 fw-bold text-dark"><?= (int)($rev['rating'] ?? 0) ?>/5</span>
                      </div>
                    </td>
                    <td style="max-width: 300px;">
                      <div class="text-truncate" title="<?= e($rev['comment'] ?? 'Puanlama yapıldı, yorum yazılmadı.') ?>">
                        <?= e($rev['comment'] ?? 'Puanlama yapıldı, yorum yazılmadı.') ?>
                      </div>
                    </td>
                    <td class="small">
                      <div><strong class="text-dark"><?= e($rev['service_name'] ?? 'Hizmet') ?></strong></div>
                      <span class="text-muted"><?= e($rev['provider_name_display'] ?? '') ?></span>
                    </td>
                    <td class="small text-muted">
                      <?= !empty($rev['created_at']) ? date('d.m.Y H:i', strtotime($rev['created_at'])) : '-' ?>
                    </td>
                    <td class="text-center">
                      <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input review-toggle-randevuburada" type="checkbox" role="switch" data-review-id="<?= $rev['id'] ?>" <?= !empty($rev['publish_to_randevuburada']) ? 'checked' : '' ?>>
                      </div>
                    </td>
                    <td class="text-center">
                      <?php if ($rev['status'] === 'published'): ?>
                        <span class="badge bg-success">Onaylandı</span>
                      <?php elseif ($rev['status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark">Beklemede</span>
                      <?php else: ?>
                        <span class="badge bg-secondary"><?= e($rev['status']) ?></span>
                      <?php endif; ?>
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

<!-- ========================================== -->
<!-- MODAL: YENİ REKLAM KAMPANYASI (GOOGLE & META) -->
<!-- ========================================== -->
<div class="modal fade" id="new-campaign-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header border-bottom bg-light py-3">
        <h5 class="modal-title fw-bold text-dark" id="new-campaign-modal-title">
          <i class="fas fa-bullhorn text-primary me-2"></i>
          Yeni Reklam Kampanyası Oluştur
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <!-- Two distinct tabs for Google Ads & Meta Ads -->
        <ul class="nav nav-tabs nav-fill bg-light px-3 pt-2 border-bottom" id="campaign-platform-tabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold py-3" id="tab-btn-google-ads" data-bs-toggle="tab" data-bs-target="#tab-pane-google-ads" type="button" role="tab">
              <i class="fab fa-google text-danger fs-5 me-2"></i>
              Google Ads Kampanyası
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold py-3" id="tab-btn-meta-ads" data-bs-toggle="tab" data-bs-target="#tab-pane-meta-ads" type="button" role="tab">
              <i class="fab fa-meta text-primary fs-5 me-2"></i>
              Meta Ads (Instagram & Facebook)
            </button>
          </li>
        </ul>

        <div class="tab-content p-4" id="campaign-platform-content">
          <!-- 1. GOOGLE ADS TAB -->
          <div class="tab-pane fade show active" id="tab-pane-google-ads" role="tabpanel">
            <form id="google-ads-form">
              <div class="row g-3">
                <div class="col-md-7">
                  <label class="form-label fw-bold">Kampanya Adı <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="g-campaign-name" placeholder="Örn: Google - Arama Ağı Cilt Bakımı & Lazer" required>
                </div>
                <div class="col-md-5">
                  <label class="form-label fw-bold">Kampanya Türü</label>
                  <select class="form-select" id="g-campaign-type">
                    <option value="search">Arama Ağı (Search Ads)</option>
                    <option value="display">Görüntülü Reklam Ağı (Display)</option>
                    <option value="pmax">Performance Max (Maksimum Performans)</option>
                    <option value="video">YouTube Video Kampanyası</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold">Reklam Grubu Adı</label>
                  <input type="text" class="form-control" id="g-ad-group-name" placeholder="Örn: Cilt Bakımı Randevu">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">Teklif Stratejisi</label>
                  <select class="form-select" id="g-bidding-strategy">
                    <option value="maximize_conversions">Dönüşümleri En Üst Düzeye Çıkar (Maximize Conversions)</option>
                    <option value="maximize_clicks">Tıklamaları En Üst Düzeye Çıkar (Maximize Clicks)</option>
                    <option value="target_cpa">Hedef EBM / CPA (Dönüşüm Başına Maliyet)</option>
                  </select>
                </div>

                <div class="col-12">
                  <label class="form-label fw-bold">Hedef Anahtar Kelimeler (Keywords)</label>
                  <textarea class="form-control font-monospace small" id="g-keywords" rows="3" placeholder="Örn:&#10;kuaför randevu&#10;&quot;en iyi cilt bakımı&quot;&#10;[lazer epilasyon istanbul]"></textarea>
                  <div class="form-text">Geniş eşleme için düz metin, sıralı eşleme için tırnak ("kelime"), tam eşleme için köşeli parantez ([kelime]) kullanın. Her satıra bir anahtar kelime yazın.</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold">Reklam Başlığı 1 <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="g-headline-1" placeholder="Örn: Profesyonel Cilt & Saç Bakımı" required maxlength="30">
                  <div class="form-text">Maks 30 karakter</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">Reklam Başlığı 2</label>
                  <input type="text" class="form-control" id="g-headline-2" placeholder="Örn: Şimdi Online Randevu Alın" maxlength="30">
                  <div class="form-text">Maks 30 karakter</div>
                </div>

                <div class="col-12">
                  <label class="form-label fw-bold">Reklam Açıklaması <span class="text-danger">*</span></label>
                  <textarea class="form-control" id="g-description" rows="2" placeholder="Uzman kadromuz ile güzelliğinizi ve sağlığınızı ön planda tutun. İlk randevunuza özel indirim fırsatı." maxlength="90" required></textarea>
                  <div class="form-text">Maks 90 karakter</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold">Günlük Bütçe (₺) <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <input type="number" step="10" min="50" class="form-control" id="g-budget" value="250" required>
                    <span class="input-group-text">₺ / gün</span>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">Nihai Hedef URL</label>
                  <input type="text" class="form-control" id="g-target-url" placeholder="https://isletmeniz.com/ veya /p/landing-sayfasi">
                </div>
              </div>
            </form>
          </div>

          <!-- 2. META ADS TAB -->
          <div class="tab-pane fade" id="tab-pane-meta-ads" role="tabpanel">
            <form id="meta-ads-form">
              <div class="row g-3">
                <div class="col-md-7">
                  <label class="form-label fw-bold">Kampanya Adı <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="m-campaign-name" placeholder="Örn: Meta - Instagram Reels & Hikayeler Bahar İndirimi" required>
                </div>
                <div class="col-md-5">
                  <label class="form-label fw-bold">Kampanya Amacı (Objective)</label>
                  <select class="form-select" id="m-objective">
                    <option value="leads">Potansiyel Müşteriler (Leads & Randevu)</option>
                    <option value="traffic">Trafik (Web Sitesi Ziyaretleri)</option>
                    <option value="sales">Satışlar (Dönüşümler)</option>
                    <option value="engagement">Etkileşim (Mesaj & Beğeni)</option>
                    <option value="awareness">Bilinirlik (Erişim & Gösterim)</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold">Reklam Seti Adı</label>
                  <input type="text" class="form-control" id="m-adset-name" placeholder="Örn: Kadınlar 20-45 İstanbul">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">Reklam Yerleşimleri (Placements)</label>
                  <select class="form-select" id="m-placements">
                    <option value="advantage_plus">Advantage+ Yerleşimler (Önerilen - Otomatik)</option>
                    <option value="instagram_reels_stories">Instagram Reels & Hikayeler</option>
                    <option value="instagram_feed">Sadece Instagram Akışı</option>
                    <option value="facebook_feed">Sadece Facebook Akışı</option>
                  </select>
                </div>

                <div class="col-12">
                  <label class="form-label fw-bold">Hedef Kitle (Demografi, Lokasyon & İlgi Alanları)</label>
                  <input type="text" class="form-control" id="m-audience" placeholder="Örn: Kadınlar 20-50, Güzellik, Saç Modelleri, Cilt Sağlığı (İstanbul +20km)">
                  <div class="form-text">Meta Marketing API kitle parametreleri ile doğrudan hedeflenir.</div>
                </div>

                <div class="col-12">
                  <label class="form-label fw-bold">Reklam Ana Metni (Primary Text) <span class="text-danger">*</span></label>
                  <textarea class="form-control" id="m-primary-text" rows="3" placeholder="Kendinizi şımartmanın tam zamanı! Şimdi randevunuzu oluşturun, ilk seansınıza özel %20 indirim kazanın." required></textarea>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold">Reklam Başlığı <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="m-headline" placeholder="Örn: İlk Randevunuza Özel %20 İndirim" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">Eylem Çağrısı (CTA Butonu)</label>
                  <select class="form-select" id="m-cta">
                    <option value="BOOK_NOW">Randevu Al (Book Now)</option>
                    <option value="APPLY_NOW">Şimdi Başvur</option>
                    <option value="CONTACT_US">Bize Ulaşın</option>
                    <option value="LEARN_MORE">Daha Fazla Bilgi Al</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold">Günlük Bütçe (₺) <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <input type="number" step="10" min="50" class="form-control" id="m-budget" value="200" required>
                    <span class="input-group-text">₺ / gün</span>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">Açılış / Randevu Sayfası URL</label>
                  <input type="text" class="form-control" id="m-target-url" placeholder="https://isletmeniz.com/ veya /p/kampanya">
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light border-top d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-primary" id="btn-save-campaign-draft">
            <i class="fas fa-file-alt me-1"></i> Taslak Olarak Kaydet
          </button>
          <button type="button" class="btn btn-success" id="btn-publish-campaign">
            <i class="fas fa-paper-plane me-1"></i> Yayınla ve Başlat
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- MODAL: KAMPANYA METRİKLERİNİ DÜZENLE -->
<!-- ========================================== -->
<div class="modal fade" id="campaign-metrics-modal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold">
          <i class="fas fa-sliders-h text-primary me-2"></i>
          Kampanya Metriklerini Düzenle
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit-metric-campaign-id">
        <div class="mb-3">
          <label class="form-label fw-bold">Kampanya Adı</label>
          <input type="text" class="form-control bg-light" id="edit-metric-campaign-name" readonly>
        </div>
        <div class="row g-2 mb-2">
          <div class="col-6">
            <label class="form-label fw-bold">Gösterim (Impressions)</label>
            <input type="number" class="form-control" id="edit-metric-impressions">
          </div>
          <div class="col-6">
            <label class="form-label fw-bold">Tıklama (Clicks)</label>
            <input type="number" class="form-control" id="edit-metric-clicks">
          </div>
        </div>
        <div class="row g-2 mb-2">
          <div class="col-6">
            <label class="form-label fw-bold">Harcama (Spend ₺)</label>
            <input type="number" step="0.01" class="form-control" id="edit-metric-spend">
          </div>
          <div class="col-6">
            <label class="form-label fw-bold">Dönüşüm (Leads / Satış)</label>
            <input type="number" class="form-control" id="edit-metric-conversions">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">ROAS (Reklam Getirisi Katsayısı)</label>
          <input type="number" step="0.01" class="form-control" id="edit-metric-roas" placeholder="Örn: 3.85">
        </div>
      </div>
      <div class="modal-footer border-top">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
        <button type="button" class="btn btn-primary" id="btn-save-metrics">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- MODAL: SEGMENET'E MÜŞTERİ TAŞI / EKLE -->
<!-- ========================================== -->
<div class="modal fade" id="assign-customer-segment-modal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold">
          <i class="fas fa-user-plus text-primary me-2"></i>
          Müşteriyi Özel Segmente Ekle
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted mb-3">Mevcut bir müşteriyi seçerek potansiyel müşteri havuzunuzdaki özel bir segmente atayabilirsiniz.</p>
        <div class="mb-3">
          <label class="form-label fw-bold">Müşteri Seçin <span class="text-danger">*</span></label>
          <select class="form-select" id="assign-customer-id">
            <option value="">-- Müşteri Seçin --</option>
            <?php foreach ($customers ?? [] as $c): ?>
              <option value="<?= $c['id'] ?>">
                <?= e(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')) ?> 
                <?= !empty($c['phone_number']) ? ' (' . e($c['phone_number']) . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Hedef Segment <span class="text-danger">*</span></label>
          <select class="form-select" id="assign-segment-id">
            <option value="">-- Segment Seçin --</option>
            <?php foreach ($segments ?? [] as $s): ?>
              <option value="<?= $s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['type']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer border-top">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btn-confirm-assign-customer">Segmente Ekle</button>
      </div>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- MODAL: SEGMENT OLUŞTUR / DÜZENLE -->
<!-- ========================================== -->
<div class="modal fade" id="segment-modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="segment-modal-title">Yeni Segment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-bold">Ad</label>
          <input type="text" class="form-control" id="segment-name" placeholder="Örn: VIP Müşteriler">
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Tür</label>
          <select class="form-select" id="segment-type">
            <option value="vip">VIP (asgari randevu sayısı)</option>
            <option value="inactive">Pasif (uzun süredir randevu yok)</option>
            <option value="birthday">Doğum günü yaklaşanlar</option>
            <option value="all">Tüm müşteriler</option>
            <option value="custom">Özel liste</option>
          </select>
        </div>
        <div id="segment-rule-vip" class="mb-3 d-none">
          <label class="form-label fw-bold">Asgari randevu sayısı</label>
          <input type="number" class="form-control" id="rule-min-appointments" min="1" value="5">
        </div>
        <div id="segment-rule-inactive" class="mb-3 d-none">
          <label class="form-label fw-bold">Pasif gün sayısı</label>
          <input type="number" class="form-control" id="rule-inactive-days" min="1" value="60">
        </div>
        <div id="segment-rule-birthday" class="mb-3 d-none">
          <label class="form-label fw-bold">Kutlama öncesi gün</label>
          <input type="number" class="form-control" id="rule-days-ahead" min="0" value="14">
        </div>
        <div id="segment-rule-custom" class="mb-3 d-none">
          <label class="form-label fw-bold">Müşteri ID'leri (virgülle ayırın)</label>
          <textarea class="form-control" id="rule-customer-ids" rows="3" placeholder="1, 2, 3"></textarea>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="segment-enabled" checked>
          <label class="form-check-label fw-bold" for="segment-enabled">Aktif</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('cancel') ?></button>
        <button type="button" class="btn btn-primary" id="save-segment">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- MODAL: AÇILIŞ SAYFASI (LANDING PAGE) -->
<!-- ========================================== -->
<div class="modal fade" id="landing-page-modal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="landing-page-modal-title">Yeni Açılış Sayfası</h5>
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
          <textarea class="form-control" id="landing-content" rows="4" placeholder="Kampanyanızın avantajlarını, kullanılan ürünleri ve randevu detaylarını açıklayın..."></textarea>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold small">Sayfaya Özel Meta Pixel ID</label>
            <input type="text" class="form-control form-control-sm" id="landing-meta-pixel" placeholder="Örn: 1234567890">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold small">Sayfaya Özel GTM ID</label>
            <input type="text" class="form-control form-control-sm" id="landing-gtm-id" placeholder="Örn: GTM-XXXXXX">
          </div>
        </div>
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" id="landing-cookie-consent" checked>
          <label class="form-check-label fw-bold" for="landing-cookie-consent">KVKK & Çerez Onay Çubuğunu Göster</label>
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

<!-- ========================================== -->
<!-- MODAL: ATTRIBUTION DETAY & HEATMAP -->
<!-- ========================================== -->
<div class="modal fade" id="attribution-modal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="fas fa-mouse me-2 text-primary"></i> Reklam Ziyaretçi & Heatmap İncelemesi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="attribution-modal-body"></div>
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
    reviews: <?= json_encode($reviews ?? []) ?>,
    customers: <?= json_encode($customers ?? []) ?>,
    integrations: <?= json_encode($integrations ?? []) ?>,
    initials: <?= json_encode(script_vars('initials')) ?>
  });
</script>
<script src="<?= asset_url('assets/js/pages/marketing.js') ?>"></script>

<?php end_section('scripts'); ?>