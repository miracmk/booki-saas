<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="wrapper">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col">
        <h1 class="page-title">
          <i class="fas fa-file-invoice"></i>
          <?= vars('page_title') ?>
        </h1>
      </div>
      <div class="col-auto">
        <div class="btn-toolbar" role="toolbar">
          <button class="btn btn-primary" id="add-invoice" title="<?= lang('add') ?>">
            <i class="fas fa-plus"></i>
            Yeni Fatura
          </button>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Fatura No</th>
                  <th><?= lang('customer') ?></th>
                  <th><?= lang('status') ?></th>
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
</div>

<!-- Invoice Creation Modal -->
<div class="modal fade" id="invoice-modal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Yeni Fatura</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label"><?= lang('customer') ?></label>
          <select class="form-select" id="invoice-customer"></select>
        </div>
        <div id="billable-items-container">
          <p class="text-muted">Önce müşteri seçin.</p>
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
