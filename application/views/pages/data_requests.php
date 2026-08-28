<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="wrapper">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col">
        <h1 class="page-title">
          <i class="fas fa-shield-alt"></i>
          <?= vars('page_title') ?>
        </h1>
      </div>
    </div>

    <!-- Status Summary -->
    <div class="row mb-3">
      <div class="col-md-2">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center">
            <div class="text-muted small">Bekleyen</div>
            <div class="h4 mb-0"><span id="count-pending">0</span></div>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center">
            <div class="text-muted small">Hazırlanıyor</div>
            <div class="h4 mb-0"><span id="count-processing">0</span></div>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center">
            <div class="text-muted small">Hazır</div>
            <div class="h4 mb-0"><span id="count-ready">0</span></div>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center">
            <div class="text-muted small text-danger">Başarısız</div>
            <div class="h4 mb-0 text-danger"><span id="count-failed">0</span></div>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center">
            <div class="text-muted small">Süresi Doldu</div>
            <div class="h4 mb-0"><span id="count-expired">0</span></div>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center">
            <div class="text-muted small">Tamamlandı</div>
            <div class="h4 mb-0"><span id="count-completed">0</span></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="row mb-3">
      <div class="col-auto">
        <select id="filter-type" class="form-select form-select-sm">
          <option value="">Tüm Türler</option>
          <option value="export">Dışa Aktarma</option>
          <option value="erasure">Silme</option>
        </select>
      </div>
      <div class="col-auto">
        <select id="filter-status" class="form-select form-select-sm">
          <option value="">Tüm Durumlar</option>
          <option value="pending">Bekliyor</option>
          <option value="processing">Hazırlanıyor</option>
          <option value="ready">Hazır</option>
          <option value="failed">Başarısız</option>
          <option value="expired">Süresi Doldu</option>
          <option value="completed">Tamamlandı</option>
        </select>
      </div>
    </div>

    <!-- Requests Table -->
    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-light">
            <h5 class="mb-0">Talepler</h5>
          </div>
          <div class="table-responsive">
            <table class="table table-hover table-sm">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Müşteri</th>
                  <th>Tür</th>
                  <th>Durum</th>
                  <th>Talep Tarihi</th>
                  <th>Not / Hata</th>
                  <th>İşlemler</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/pages/data_requests.js') ?>"></script>

<?php end_section('scripts'); ?>
