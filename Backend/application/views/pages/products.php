<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container-fluid py-3 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h4 mb-0 fw-bold text-dark">
        <i class="fas fa-cubes me-2 text-primary"></i><?= vars('page_title') ?>
      </h1>
      <p class="text-muted small mb-0">Ürün envanterini, perakende satış fiyatlarını ve sarfiyat stoklarını yönetin.</p>
    </div>
    <div>
      <button class="btn btn-primary" id="add-product" title="<?= lang('add') ?>">
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
            <th><?= lang('name') ?></th>
            <th style="width: 170px;"><?= lang('sku') ?></th>
            <th class="text-end" style="width: 140px;"><?= lang('sale_price') ?></th>
            <th class="text-center" style="width: 140px;"><?= lang('stock_quantity') ?></th>
            <th class="text-center" style="width: 150px;"><?= lang('low_stock_threshold') ?></th>
            <th class="text-center" style="width: 130px;"><?= lang('status') ?></th>
            <th class="text-end pe-3" style="width: 120px;"><?= lang('actions') ?></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
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
                <div class="input-group">
                  <input
                    type="number"
                    class="form-control"
                    id="product-sale-price"
                    name="product[sale_price]"
                    step="0.01"
                    min="0"
                    required
                  />
                  <span class="input-group-text">₺</span>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="product-cost-price" class="form-label">
                  <?= lang('cost_price') ?>
                </label>
                <div class="input-group">
                  <input
                    type="number"
                    class="form-control"
                    id="product-cost-price"
                    name="product[cost_price]"
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
                <label for="product-stock-quantity" class="form-label">
                  <?= lang('stock_quantity') ?> *
                </label>
                <div class="input-group">
                  <input
                    type="number"
                    class="form-control"
                    id="product-stock-quantity"
                    name="product[stock_quantity]"
                    min="0"
                    value="0"
                    required
                  />
                  <span class="input-group-text">Adet</span>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="product-low-stock-threshold" class="form-label">
                  <?= lang('low_stock_threshold') ?>
                </label>
                <div class="input-group">
                  <input
                    type="number"
                    class="form-control"
                    id="product-low-stock-threshold"
                    name="product[low_stock_threshold]"
                    min="0"
                    value="5"
                  />
                  <span class="input-group-text">Adet</span>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <div class="form-check form-switch mt-2">
                <input
                  type="checkbox"
                  class="form-check-input"
                  id="product-is-active"
                  name="product[is_active]"
                  value="1"
                  checked
                  role="switch"
                />
                <label class="form-check-label fw-semibold" for="product-is-active">
                  <?= lang('is_active') ?> (Satış ve Sarfiyata Açık)
                </label>
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
