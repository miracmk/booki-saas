<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-3" id="memberships-page">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-light">
      <i class="fas fa-id-card me-2 text-primary"></i>
      <?= vars('page_title') ?>
    </h4>
    <div class="btn-toolbar" role="toolbar">
      <button class="btn btn-outline-secondary me-2" id="add-plan" title="Plan Ekle">
        <i class="fas fa-clipboard-list me-1"></i>
        Plan Ekle
      </button>
      <button class="btn btn-primary" id="add-membership" title="<?= lang('add') ?>">
        <i class="fas fa-plus me-1"></i>
        Üyelik Sat
      </button>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th><?= lang('customer') ?></th>
            <th>Plan</th>
            <th><?= lang('status') ?></th>
            <th>Dönem Sonu</th>
            <th>Kullanılan Seans</th>
            <th><?= lang('actions') ?></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Plan Modal -->
<div class="modal fade" id="plan-modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Yeni Üyelik Planı</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="plan-form">
          <div class="mb-3">
            <label class="form-label">Plan Adı</label>
            <input type="text" class="form-control" name="plan[name]" required />
          </div>
          <div class="mb-3">
            <label class="form-label"><?= lang('service') ?></label>
            <select class="form-select" id="plan-service" name="plan[id_services]" required></select>
          </div>
          <div class="mb-3">
            <label class="form-label">Faturalama Periyodu</label>
            <select class="form-select" name="plan[billing_period]">
              <option value="monthly">Aylık</option>
              <option value="quarterly">3 Aylık</option>
              <option value="yearly">Yıllık</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Fiyat</label>
            <input type="number" step="0.01" class="form-control" name="plan[price]" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Periyot Başına Seans (boş = sınırsız)</label>
            <input type="number" class="form-control" name="plan[sessions_per_period]" />
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('cancel') ?></button>
        <button type="button" class="btn btn-primary" id="save-plan"><?= lang('save') ?></button>
      </div>
    </div>
  </div>
</div>

<!-- Membership (sell) Modal -->
<div class="modal fade" id="membership-modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Üyelik Sat</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="membership-form">
          <div class="mb-3">
            <label class="form-label"><?= lang('customer') ?></label>
            <select class="form-select" id="membership-customer" name="membership[id_users_customer]" required></select>
          </div>
          <div class="mb-3">
            <label class="form-label">Plan</label>
            <select class="form-select" id="membership-plan" name="membership[id_membership_plans]" required></select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('cancel') ?></button>
        <button type="button" class="btn btn-primary" id="save-membership"><?= lang('save') ?></button>
      </div>
    </div>
  </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/pages/memberships.js') ?>"></script>

<?php end_section('scripts'); ?>
