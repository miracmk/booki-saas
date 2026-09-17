<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-3" id="invoices-page">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-light">
      <i class="fas fa-file-invoice me-2 text-primary"></i>
      <?= vars('page_title') ?>
    </h4>
    <div class="btn-toolbar" role="toolbar">
      <button class="btn btn-primary" id="add-invoice" title="<?= lang('add') ?>">
        <i class="fas fa-plus me-1"></i>
        Yeni Fatura
      </button>
    </div>
  </div>

    <?php // 2026-09-12 - Muhasebe yazılımlarına (Logo/Mikro/Netsis/Zirve/İşBaşı/ETA vb.) elle/dosyayla
          // içe aktarılabilecek evrensel bir CSV; canlı REST API'si olan sistemler (Paraşüt/KolayBi)
          // için gerçek push entegrasyonu ayrı bir sonraki adım (API anahtarı/sandbox erişimi gerekir). ?>
    <div class="row mb-3">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center" style="cursor: pointer;"
               data-bs-toggle="collapse" data-bs-target="#invoice-export-collapse" role="button">
            <h6 class="fw-light mb-0"><i class="fas fa-file-export me-2"></i>Muhasebe Dışa Aktar</h6>
            <i class="fas fa-chevron-down"></i>
          </div>
          <div class="collapse" id="invoice-export-collapse">
            <div class="card-body">
              <p class="form-text text-muted">
                Seçilen tarih aralığındaki kesilmiş (taslak/iptal hariç) faturaları, muhasebe
                yazılımınıza (Logo, Mikro, Netsis, Zirve, İşBaşı, ETA, Paraşüt, KolayBi vb.) içe
                aktarabileceğiniz genel bir CSV formatında indirir.
              </p>
              <div class="row g-3 align-items-end">
                <div class="col-12 col-sm-4">
                  <label class="form-label" for="invoice-export-start">Başlangıç Tarihi</label>
                  <input type="date" id="invoice-export-start" class="form-control">
                </div>
                <div class="col-12 col-sm-4">
                  <label class="form-label" for="invoice-export-end">Bitiş Tarihi</label>
                  <input type="date" id="invoice-export-end" class="form-control">
                </div>
                <div class="col-12 col-sm-4">
                  <button type="button" id="invoice-export-csv" class="btn btn-primary w-100">
                    <i class="fas fa-file-csv me-2"></i> CSV İndir
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th>Fatura No</th>
                  <th><?= lang('customer') ?></th>
                  <th><?= lang('status') ?></th>
                  <th>ERP Durumu</th>
                  <th>Tutar</th>
                  <th>Oluşturulma</th>
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

<!-- Invoice Creation Modal -->
<div class="modal fade" id="invoice-modal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-file-invoice me-2 text-primary"></i>Yeni Fatura Oluştur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-bold"><?= lang('customer') ?></label>
          <select class="form-select" id="invoice-customer"></select>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Randevu Kalemleri</label>
          <div id="billable-items-container" class="border rounded p-2 bg-light">
            <p class="text-muted mb-0">Önce müşteri seçin.</p>
          </div>
        </div>

        <div class="card border-dashed p-3 mb-3 bg-light">
          <label class="form-label fw-bold text-primary mb-2"><i class="fas fa-plus-circle me-1"></i>Doğrudan / Manuel Kalem Ekle</label>
          <div class="row g-2 align-items-end">
            <div class="col-md-5">
              <label class="form-label small">Kalem Açıklaması</label>
              <input type="text" class="form-control form-control-sm" id="custom-item-desc" placeholder="Örn: Özel Bakım veya Ürün">
            </div>
            <div class="col-md-2">
              <label class="form-label small">Adet</label>
              <input type="number" class="form-control form-control-sm" id="custom-item-qty" value="1" min="1">
            </div>
            <div class="col-md-3">
              <label class="form-label small">Birim Fiyat (TL)</label>
              <input type="number" class="form-control form-control-sm" id="custom-item-price" placeholder="0.00" step="0.01">
            </div>
            <div class="col-md-2">
              <button type="button" class="btn btn-sm btn-outline-primary w-100" id="btn-add-custom-item">
                <i class="fas fa-plus"></i> Ekle
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('cancel') ?></button>
        <button type="button" class="btn btn-primary" id="save-invoice">Fatura Oluştur</button>
      </div>
    </div>
  </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/pages/invoices.js') ?>"></script>

<?php end_section('scripts'); ?>
