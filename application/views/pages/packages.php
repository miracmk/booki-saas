<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="wrapper">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col">
        <h1 class="page-title">
          <i class="fas fa-boxes"></i>
          <?= vars('page_title') ?>
        </h1>
      </div>
      <div class="col-auto">
        <div class="btn-toolbar" role="toolbar">
          <button class="btn btn-primary" id="add-package" title="<?= lang('add') ?>">
            <i class="fas fa-plus"></i>
            <?= lang('add') ?>
          </button>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <input
              type="text"
              class="keyword form-control"
              placeholder="<?= lang('search') ?>..."
              title="<?= lang('search') ?>"
            />
          </div>

          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>#</th>
                  <th><?= lang('customer') ?></th>
                  <th><?= lang('service') ?></th>
                  <th><?= lang('total_sessions') ?></th>
                  <th><?= lang('used_sessions') ?></th>
                  <th><?= lang('status') ?></th>
                  <th><?= lang('expires') ?></th>
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
                <input
                  type="number"
                  class="form-control"
                  id="package-total-sessions"
                  name="package[total_sessions]"
                  min="1"
                  required
                />
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="package-unit-price" class="form-label">
                  <?= lang('unit_price') ?>
                </label>
                <input
                  type="number"
                  class="form-control"
                  id="package-unit-price"
                  name="package[unit_price]"
                  step="0.01"
                  min="0"
                />
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
