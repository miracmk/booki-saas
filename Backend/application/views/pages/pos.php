<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container-fluid backend-page py-3 px-md-4" style="max-width: 1400px;" id="pos-page">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-light">
      <i class="fas fa-cash-register me-2 text-primary"></i>
      <?= vars('page_title') ?>
    </h4>
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <?php if ((vars('active_gateway') ?? '') === 'iyzico'): ?>
        <img src="<?= asset_url('assets/img/iyzico/iyzico_ile_ode.svg') ?>" alt="iyzico ile Öde" style="height:42px;width:auto;" loading="lazy">
      <?php endif; ?>
      <span class="badge bg-light text-dark border py-2 px-3">
        <i class="fas fa-credit-card me-1 text-primary"></i>
        Aktif Sanal POS: <strong class="text-primary"><?= htmlspecialchars(vars('active_gateway_name') ?? 'Tanımsız') ?></strong>
      </span>
    </div>
  </div>

    <div class="row">
      <div class="col-md-5">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <h5>Yeni Satış</h5>
            <div class="mb-3">
              <label class="form-label"><?= lang('customer') ?> (opsiyonel)</label>
              <select class="form-select" id="pos-customer"></select>
            </div>
            <div class="mb-3">
              <label class="form-label">Ürün</label>
              <select class="form-select" id="pos-product"></select>
            </div>
            <div class="mb-3">
              <label class="form-label">Miktar</label>
              <input type="number" class="form-control" id="pos-quantity" value="1" min="1" />
            </div>
            <button class="btn btn-outline-primary" id="add-item">Sepete Ekle</button>

            <hr />

            <table class="table table-sm">
              <tbody id="pos-basket"></tbody>
            </table>
            <div class="text-end">
              <strong>Toplam: <span id="pos-total">0.00</span></strong>
            </div>

            <button class="btn btn-primary w-100 mt-3" id="create-order">Sipariş Oluştur</button>
          </div>
        </div>
      </div>

      <div class="col-md-7">
        <div class="card border-0 shadow-sm">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>#</th>
                  <th><?= lang('customer') ?></th>
                  <th><?= lang('status') ?></th>
                  <th>Tutar</th>
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

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/pages/pos.js') ?>"></script>

<?php end_section('scripts'); ?>
