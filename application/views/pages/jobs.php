<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="wrapper">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col">
        <h1 class="page-title">
          <i class="fas fa-hourglass-start"></i>
          <?= vars('page_title') ?>
        </h1>
      </div>
    </div>

    <!-- Queue Status Summary -->
    <div class="row mb-3">
      <div class="col-md-3">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center">
            <div class="text-muted small">Bekleme</div>
            <div class="h4 mb-0">
              <span id="count-pending">0</span>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center">
            <div class="text-muted small">İşlenirken</div>
            <div class="h4 mb-0">
              <span id="count-reserved">0</span>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center">
            <div class="text-muted small">Tamamlandı</div>
            <div class="h4 mb-0">
              <span id="count-succeeded">0</span>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center">
            <div class="text-muted small">Başarısız</div>
            <div class="h4 mb-0 text-danger">
              <span id="count-failed">0</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Failed Jobs Table -->
    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-light">
            <h5 class="mb-0">Son Başarısız İşler</h5>
          </div>
          <div class="table-responsive">
            <table class="table table-hover table-sm">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Kanal</th>
                  <th>Handler</th>
                  <th>Durum</th>
                  <th>Denemeler</th>
                  <th>Son Hata</th>
                  <th>Correlation ID</th>
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

<script src="<?= asset_url('assets/js/pages/jobs.js') ?>"></script>

<?php end_section('scripts'); ?>
