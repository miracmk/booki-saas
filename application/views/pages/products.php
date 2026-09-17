<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="wrapper">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col">
        <h1 class="page-title">
          <i class="fas fa-cubes"></i>
          <?= vars('page_title') ?>
        </h1>
      </div>
      <div class="col-auto">
        <div class="btn-toolbar" role="toolbar">
          <button class="btn btn-primary" id="add-product" title="<?= lang('add') ?>">
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
                  <th><?= lang('name') ?></th>
                  <th><?= lang('sku') ?></th>
                  <th><?= lang('sale_price') ?></th>
                  <th><?= lang('stock_quantity') ?></th>
                  <th><?= lang('low_stock_threshold') ?></th>
                  <th><?= lang('status') ?></th>
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

<!-- Product Modal -->
<div
  class="modal fade"
  id="product-modal"
  tabindex="-1"
  role="dialog"
  aria-labelledby="product-modal-label"
  aria-hidden="true"
>
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="product-modal-label">
          <?= lang('manage_product') ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="product-form">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="product-name" class="form-label">
                  <?= lang('name') ?> *
                </label>
                <input
                  type="text"
                  class="form-control"
                  id="product-name"
                  name="product[name]"
                  required
                />
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="product-sku" class="form-label">
                  <?= lang('sku') ?>
                </label>
                <input
                  type="text"
                  class="form-control"
                  id="product-sku"
                  name="product[sku]"
                />
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="product-sale-price" class="form-label">
                  <?= lang('sale_price') ?> *
                </label>
                <input
                  type="number"
                  class="form-control"
                  id="product-sale-price"
                  name="product[sale_price]"
                  step="0.01"
                  min="0"
                  required
                />
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="product-cost-price" class="form-label">
                  <?= lang('cost_price') ?>
                </label>
                <input
                  type="number"
                  class="form-control"
                  id="product-cost-price"
                  name="product[cost_price]"
                  step="0.01"
                  min="0"
                />
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="product-stock-quantity" class="form-label">
                  <?= lang('stock_quantity') ?> *
                </label>
                <input
                  type="number"
                  class="form-control"
                  id="product-stock-quantity"
                  name="product[stock_quantity]"
                  min="0"
                  value="0"
                  required
                />
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="product-low-stock-threshold" class="form-label">
                  <?= lang('low_stock_threshold') ?>
                </label>
                <input
                  type="number"
                  class="form-control"
                  id="product-low-stock-threshold"
                  name="product[low_stock_threshold]"
                  min="0"
                  value="5"
                />
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <div class="form-check">
                  <input
                    type="checkbox"
                    class="form-check-input"
                    id="product-is-active"
                    name="product[is_active]"
                    value="1"
                    checked
                  />
                  <label class="form-check-label" for="product-is-active">
                    <?= lang('is_active') ?>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <?= lang('cancel') ?>
        </button>
        <button type="button" class="btn btn-primary" id="save-product">
          <?= lang('save') ?>
        </button>
      </div>
    </div>
  </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/pages/products.js') ?>"></script>

<?php end_section('scripts'); ?>
