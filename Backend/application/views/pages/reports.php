<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container-fluid backend-page py-3 px-md-4" style="max-width: 1400px;" id="reports-page">
  <!-- Header Bar -->
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
      <h4 class="mb-0 fw-bold text-dark">
        <i class="fas fa-chart-pie me-2 text-primary"></i>
        Raporlar & Analitik Merkezi
      </h4>
      <div class="text-muted small mt-1">Ciro, finansal özetler, personel hakedişleri ve tek tıkla indirilebilir şablonlar</div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="<?= site_url('reports/download_template?template=gun_sonu') ?>" class="btn btn-success btn-sm shadow-sm" id="btn-quick-gun-sonu">
        <i class="fas fa-bolt me-1"></i> Gün Sonu Raporu İndir (Bugün)
      </a>
      <a href="<?= site_url('reports/download_template?template=aylik_muhasebe') ?>" class="btn btn-outline-primary btn-sm shadow-sm">
        <i class="fas fa-file-invoice-dollar me-1"></i> Aylık Muhasebe Özeti İndir
      </a>
    </div>
  </div>

  <!-- SECTION 1: HAZIR RAPOR ŞABLONLARI (1-TIKLA İNDİR) -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h6 class="mb-0 fw-bold text-dark">
            <i class="fas fa-file-download text-primary me-2"></i>
            Hazır Rapor Şablonları (1-Tıkla İndir)
          </h6>
          <small class="text-muted">Muhasebe ve işletme yönetimi için önceden filtrelenmiş standart formatlar</small>
        </div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">CSV / Excel Uyumlu (UTF-8 BOM)</span>
      </div>
    </div>
    <div class="card-body p-3">
      <div class="row g-3">
        <!-- Şablon 1: Gün Sonu -->
        <div class="col-md-6 col-lg-4">
          <div class="p-3 border rounded-3 bg-light-subtle h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold text-dark"><i class="fas fa-calendar-day text-success me-2"></i> Gün Sonu Raporu</span>
                <span class="badge bg-success">Bugün</span>
              </div>
              <p class="small text-muted mb-3">Bugün gerçekleşen tüm randevular, adisyonlar, tahsil edilen ve kalan bakiyeler ile fatura durumları.</p>
            </div>
            <a href="<?= site_url('reports/download_template?template=gun_sonu') ?>" class="btn btn-outline-success btn-sm w-100 fw-semibold">
              <i class="fas fa-download me-1"></i> Şablonu İndir (CSV)
            </a>
          </div>
        </div>

        <!-- Şablon 2: Aylık Muhasebe -->
        <div class="col-md-6 col-lg-4">
          <div class="p-3 border rounded-3 bg-light-subtle h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold text-dark"><i class="fas fa-file-invoice text-primary me-2"></i> Aylık Muhasebe Özeti</span>
                <span class="badge bg-primary">Bu Ay</span>
              </div>
              <p class="small text-muted mb-3">Mali müşavir ve ERP entegrasyonu için aylık ciro, faturalar, KDV matrahları ve tahsilat türleri dökümü.</p>
            </div>
            <a href="<?= site_url('reports/download_template?template=aylik_muhasebe') ?>" class="btn btn-outline-primary btn-sm w-100 fw-semibold">
              <i class="fas fa-download me-1"></i> Şablonu İndir (CSV)
            </a>
          </div>
        </div>

        <!-- Şablon 3: Personel Prim & Hakediş -->
        <div class="col-md-6 col-lg-4">
          <div class="p-3 border rounded-3 bg-light-subtle h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold text-dark"><i class="fas fa-user-check text-warning me-2"></i> Personel Prim & Hakediş</span>
                <span class="badge bg-warning text-dark">Hakediş</span>
              </div>
              <p class="small text-muted mb-3">Uzman bazlı tamamlanan seanslar, net çalışma süreleri, komisyon oranları ve haklı/haksız hakediş dağılımı.</p>
            </div>
            <a href="<?= site_url('reports/download_template?template=personel_prim') ?>" class="btn btn-outline-warning text-dark btn-sm w-100 fw-semibold">
              <i class="fas fa-download me-1"></i> Şablonu İndir (CSV)
            </a>
          </div>
        </div>

        <!-- Şablon 4: Hizmet Karlılık -->
        <div class="col-md-6 col-lg-4">
          <div class="p-3 border rounded-3 bg-light-subtle h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold text-dark"><i class="fas fa-spa text-info me-2"></i> Hizmet Karlılık & Popülerlik</span>
                <span class="badge bg-info">Son 30 Gün</span>
              </div>
              <p class="small text-muted mb-3">Hangi hizmetin kaç kez alındığı, toplam çalışma süresi ve işletme cirosuna net katkısı.</p>
            </div>
            <a href="<?= site_url('reports/download_template?template=hizmet_karlilik') ?>" class="btn btn-outline-info btn-sm w-100 fw-semibold">
              <i class="fas fa-download me-1"></i> Şablonu İndir (CSV)
            </a>
          </div>
        </div>

        <!-- Şablon 5: Ödeme & Tahsilat Kanalları -->
        <div class="col-md-6 col-lg-4">
          <div class="p-3 border rounded-3 bg-light-subtle h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold text-dark"><i class="fas fa-credit-card text-secondary me-2"></i> Ödeme & Tahsilat Kanalları</span>
                <span class="badge bg-secondary">Ödeme Kırılımı</span>
              </div>
              <p class="small text-muted mb-3">Nakit, ÖKC POS, Havale/EFT ve Online Kapora tahsilatlarının ayrıntılı dökümü ve mutabakat listesi.</p>
            </div>
            <a href="<?= site_url('reports/download_template?template=odeme_tahsilat') ?>" class="btn btn-outline-secondary btn-sm w-100 fw-semibold">
              <i class="fas fa-download me-1"></i> Şablonu İndir (CSV)
            </a>
          </div>
        </div>

        <!-- Şablon 6: Hızlı Özel Rapor -->
        <div class="col-md-6 col-lg-4">
          <div class="p-3 border rounded-3 bg-light-subtle h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold text-dark"><i class="fas fa-sliders-h text-dark me-2"></i> Özel Sütunlu Rapor Oluştur</span>
                <span class="badge bg-dark">Gelişmiş</span>
              </div>
              <p class="small text-muted mb-3">Tarih aralığı ve dilediğiniz veri sütunlarını (PII dahil) seçerek kendi özel raporunuzu derleyin.</p>
            </div>
            <button type="button" class="btn btn-outline-dark btn-sm w-100 fw-semibold" onclick="$('#headingCustomExport button').click(); document.getElementById('headingCustomExport').scrollIntoView({behavior: 'smooth'});">
              <i class="fas fa-cogs me-1"></i> Sihirbaza Git
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- SECTION 2: AKORDEON & TABLO DÜZENİ -->
  <div class="accordion shadow-sm" id="reportsAccordion">

    <!-- AKORDEON 1: GÜNLÜK CİRO & KASA ÖZETİ (DEFAULT OPEN) -->
    <div class="accordion-item border-0 border-bottom">
      <h2 class="accordion-header" id="headingDailyRevenue">
        <button class="accordion-button fw-bold text-dark bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDailyRevenue" aria-expanded="true" aria-controls="collapseDailyRevenue">
          <i class="fas fa-cash-register text-success me-2 fs-5"></i>
          1. Günlük Ciro & Kasa Özeti Tablosu
        </button>
      </h2>
      <div id="collapseDailyRevenue" class="accordion-collapse collapse show" aria-labelledby="headingDailyRevenue" data-bs-parent="#reportsAccordion">
        <div class="accordion-body p-4">
          <div class="row align-items-center g-3 mb-4">
            <div class="col-12 col-sm-4 col-md-3">
              <label class="form-label fw-bold small text-uppercase text-muted" for="report-date">Rapor Tarihi</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                <input type="date" id="report-date" class="form-control" value="<?= date('Y-m-d') ?>">
              </div>
            </div>
            <div class="col-12 col-sm-8 col-md-9 pt-sm-4">
              <div id="report-summary"></div>
            </div>
          </div>

          <div class="table-responsive border rounded-3">
            <table class="table table-hover align-middle mb-0" id="report-table">
              <thead class="table-light">
                <tr>
                  <th>Uzman / Personel</th>
                  <th class="text-center">Seans Adedi</th>
                  <th class="text-center">Çalışma Süresi</th>
                  <th class="text-end">Toplam Ciro</th>
                  <th class="text-end">Tahsil Edilen</th>
                  <th class="text-end">Kalan Bakiye</th>
                  <th class="text-center">Bekleyen Adisyon</th>
                  <th class="text-center">Fatura Durumu</th>
                  <th>Komisyon Oranı</th>
                  <th class="text-end">Personele Hakediş</th>
                </tr>
              </thead>
              <tbody>
                <!-- Populated via reports.js -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- AKORDEON 2: DÖNEMSEL FİNANS & CİRO ANALİZİ (ANALYTICS) -->
    <div class="accordion-item border-0 border-bottom">
      <h2 class="accordion-header" id="headingAnalytics">
        <button class="accordion-button collapsed fw-bold text-dark bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAnalytics" aria-expanded="false" aria-controls="collapseAnalytics">
          <i class="fas fa-chart-line text-primary me-2 fs-5"></i>
          2. Dönemsel Finans & Ciro Trend Analizi
        </button>
      </h2>
      <div id="collapseAnalytics" class="accordion-collapse collapse" aria-labelledby="headingAnalytics" data-bs-parent="#reportsAccordion">
        <div class="accordion-body p-4">
          <div class="p-3 bg-light rounded-3 mb-4 d-flex flex-wrap align-items-center gap-3">
            <div class="flex-grow-1" style="min-width: 150px;">
              <label class="form-label small fw-bold text-muted text-uppercase" for="analytics-date-from">Başlangıç</label>
              <input type="date" id="analytics-date-from" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('-29 days')) ?>">
            </div>
            <div class="flex-grow-1" style="min-width: 150px;">
              <label class="form-label small fw-bold text-muted text-uppercase" for="analytics-date-to">Bitiş</label>
              <input type="date" id="analytics-date-to" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="flex-grow-1" style="min-width: 150px;">
              <label class="form-label small fw-bold text-muted text-uppercase" for="analytics-group-by">Gruplama</label>
              <select id="analytics-group-by" class="form-select form-select-sm">
                <option value="day">Günlük</option>
                <option value="week">Haftalık</option>
                <option value="month">Aylık</option>
              </select>
            </div>
            <div class="mt-sm-4">
              <button type="button" id="analytics-fetch-btn" class="btn btn-primary btn-sm px-4 shadow-sm">
                <i class="fas fa-sync-alt me-1"></i> Analiz Et
              </button>
            </div>
          </div>

          <div id="analytics-error" class="alert alert-danger d-none rounded-3 border-0 shadow-sm"></div>

          <div class="row g-4">
            <div class="col-12 col-lg-6">
              <div class="card h-100 border shadow-sm">
                <div class="card-header bg-white border-bottom">
                  <h6 class="mb-0 fw-bold"><i class="fas fa-wallet text-success me-2"></i> Ciro Dağılım Tablosu</h6>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" id="analytics-revenue-table">
                      <thead class="table-light">
                        <tr>
                          <th>Dönem</th>
                          <th class="text-center">Randevu</th>
                          <th class="text-end">Ciro</th>
                          <th class="text-end">Hakediş</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr><td colspan="4" class="text-center text-muted py-3">Verileri görmek için "Analiz Et" butonuna basınız.</td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12 col-lg-6">
              <div class="card h-100 border shadow-sm">
                <div class="card-header bg-white border-bottom">
                  <h6 class="mb-0 fw-bold"><i class="fas fa-user-clock text-info me-2"></i> Personel Doluluk Tablosu</h6>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" id="analytics-utilization-table">
                      <thead class="table-light">
                        <tr>
                          <th>Personel</th>
                          <th class="text-center">Randevu Süresi</th>
                          <th class="text-center">Müsait Süre</th>
                          <th class="text-center">Doluluk %</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr><td colspan="4" class="text-center text-muted py-3">Verileri görmek için "Analiz Et" butonuna basınız.</td></tr>
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

    <!-- AKORDEON 3: ÖZELLEŞTİRİLMİŞ DIŞA AKTARMA SİHİRBAZI -->
    <div class="accordion-item border-0">
      <h2 class="accordion-header" id="headingCustomExport">
        <button class="accordion-button collapsed fw-bold text-dark bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCustomExport" aria-expanded="false" aria-controls="collapseCustomExport">
          <i class="fas fa-cogs text-secondary me-2 fs-5"></i>
          3. Özel Filtreli ve Sütun Seçimli Rapor Oluşturucu
        </button>
      </h2>
      <div id="collapseCustomExport" class="accordion-collapse collapse" aria-labelledby="headingCustomExport" data-bs-parent="#reportsAccordion">
        <div class="accordion-body p-4">
          <p class="text-muted small mb-3">
            Belirlediğiniz tarih aralığı için dilediğiniz alanları seçerek tek bir Excel/CSV dosyası oluşturabilirsiniz.
          </p>

          <div class="row g-3 align-items-end mb-4">
            <div class="col-12 col-sm-4">
              <label class="form-label fw-bold small" for="export-start-date">Başlangıç Tarihi</label>
              <input type="date" id="export-start-date" class="form-control" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-12 col-sm-4">
              <label class="form-label fw-bold small" for="export-end-date">Bitiş Tarihi</label>
              <input type="date" id="export-end-date" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-12 col-sm-4">
              <button type="button" id="export-csv" class="btn btn-primary w-100 fw-semibold">
                <i class="fas fa-file-csv me-2"></i> Seçili Sütunlarla CSV İndir
              </button>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <span class="fw-bold small text-uppercase text-muted">Rapora Dahil Edilecek Sütunlar</span>
            <div class="btn-group btn-group-sm">
              <button type="button" id="export-columns-all" class="btn btn-outline-secondary">Tümünü Seç</button>
              <button type="button" id="export-columns-none" class="btn btn-outline-secondary">Hiçbirini Seçme</button>
            </div>
          </div>

          <?php
            $fieldGroups = [];
            foreach (vars('report_field_catalog') as $key => $field) {
              $fieldGroups[$field['group']][$key] = $field;
            }
          ?>
          <div class="row g-3">
            <?php foreach ($fieldGroups as $groupName => $fields): ?>
              <div class="col-12 col-sm-6 col-lg-4 mb-2">
                <div class="p-3 border rounded-3 bg-light-subtle h-100">
                  <strong class="d-block mb-2 text-dark border-bottom pb-1"><?= e($groupName) ?></strong>
                  <?php foreach ($fields as $key => $field): ?>
                    <div class="form-check mb-1">
                      <input type="checkbox" class="form-check-input export-column-checkbox"
                             id="export-col-<?= e($key) ?>" value="<?= e($key) ?>"
                             <?= !$field['pii'] ? 'checked' : '' ?>>
                      <label class="form-check-label small" for="export-col-<?= e($key) ?>">
                        <?= e($field['label']) ?>
                        <?php if ($field['pii']): ?>
                          <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65em;">KVKK / PII</span>
                        <?php endif; ?>
                      </label>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script>
  window.scriptVars = Object.assign({}, window.scriptVars || {}, {
    report_field_catalog: <?= json_encode(script_vars('report_field_catalog') ?? []) ?>
  });
</script>
<script src="<?= asset_url('assets/js/pages/reports.js') ?>"></script>

<?php end_section('scripts'); ?>
