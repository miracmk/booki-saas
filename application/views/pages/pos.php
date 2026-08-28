<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="wrapper">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col">
        <h1 class="page-title">
          <i class="fas fa-cash-register"></i>
          <?= vars('page_title') ?>
        </h1>
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
                  <th><?= lang('id') ?></th>
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
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/pages/pos.js') ?>"></script>

<?php end_section('scripts'); ?>
