<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container-fluid py-3 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h4 mb-0 fw-bold text-dark">
        <i class="fas fa-boxes me-2 text-primary"></i><?= vars('page_title') ?>
      </h1>
      <p class="text-muted small mb-0">Müşterilerinize tanımlanan çoklu seans paketlerini ve kalan hakları takip edin.</p>
    </div>
    <div>
      <button class="btn btn-primary" id="add-package" title="<?= lang('add') ?>">
        <i class="fas fa-plus me-1"></i> <?= lang('add') ?>
      </button>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <div class="row align-items-center g-2">
        <div class="col-md-4 col-sm-6">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted">
              <i class="fas fa-search"></i>
            </span>
            <input
              type="text"
              class="keyword form-control border-start-0 bg-light"
              placeholder="<?= lang('search') ?>..."
              title="<?= lang('search') ?>"
            />
          </div>
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3" style="width: 70px;">#</th>
            <th><?= lang('customer') ?></th>
            <th><?= lang('service') ?></th>
            <th class="text-center" style="width: 140px;"><?= lang('total_sessions') ?></th>
            <th class="text-center" style="width: 140px;"><?= lang('used_sessions') ?></th>
            <th class="text-center" style="width: 130px;"><?= lang('status') ?></th>
            <th style="width: 150px;"><?= lang('expires') ?></th>
            <th class="text-end pe-3" style="width: 120px;"><?= lang('actions') ?></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Package Modal -->
<div
  class="modal fade"
  id="package-modal"
  tabindex="-1"
  role="dialog"
  aria-labelledby="package-modal-label"
  aria-hidden="true"
>
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="package-modal-label">
          <?= lang('manage_package') ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="package-form">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="package-customer" class="form-label">
                  <?= lang('customer') ?> *
                </label>
                <select class="form-select" id="package-customer" name="package[id_users_customer]" required>
                  <option value=""><?= lang('select') ?></option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="package-service" class="form-label">
                  <?= lang('service') ?> *
                </label>
                <select class="form-select" id="package-service" name="package[id_services]" required>
                  <option value=""><?= lang('select') ?></option>
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="package-total-sessions" class="form-label">
                  <?= lang('total_sessions') ?> *
                </label>
                <div class="input-group">
                  <input
                    type="number"
                    class="form-control"
                    id="package-total-sessions"
                    name="package[total_sessions]"
                    min="1"
                    required
                  />
                  <span class="input-group-text">Seans</span>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="package-unit-price" class="form-label">
                  <?= lang('unit_price') ?>
                </label>
                <div class="input-group">
                  <input
                    type="number"
                    class="form-control"
                    id="package-unit-price"
                    name="package[unit_price]"
                    step="0.01"
                    min="0"
                  />
                  <span class="input-group-text">₺</span>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="package-expires-at" class="form-label">
                  <?= lang('expires_at') ?>
                </label>
                <input
                  type="datetime-local"
                  class="form-control"
                  id="package-expires-at"
                  name="package[expires_at]"
                />
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="package-status" class="form-label">
                  <?= lang('status') ?> *
                </label>
                <select class="form-select" id="package-status" name="package[status]" required>
                  <option value="active"><?= lang('active') ?></option>
                  <option value="exhausted"><?= lang('exhausted') ?></option>
                  <option value="cancelled"><?= lang('cancelled') ?></option>
                </select>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <?= lang('cancel') ?>
        </button>
        <button type="button" class="btn btn-primary" id="save-package">
          <?= lang('save') ?>
        </button>
      </div>
    </div>
  </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/pages/packages.js') ?>"></script>

<?php end_section('scripts'); ?>
