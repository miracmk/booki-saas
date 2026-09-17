<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-3" id="waitlist-page">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-light">
      <i class="fas fa-hourglass-half me-2 text-primary"></i>
      <?= vars('page_title') ?>
    </h4>
    <div class="btn-toolbar" role="toolbar">
      <button class="btn btn-primary" id="add-entry" title="<?= lang('add') ?>">
        <i class="fas fa-plus me-1"></i>
        <?= lang('add') ?>
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
            <th><?= lang('service') ?></th>
            <th><?= lang('status') ?></th>
            <th><?= lang('date') ?></th>
            <th><?= lang('actions') ?></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Waitlist Entry Modal -->
<div class="modal fade" id="entry-modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?= lang('add') ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="entry-form">
          <div class="mb-3">
            <label class="form-label"><?= lang('customer') ?></label>
            <select class="form-select" id="entry-customer" name="entry[id_users_customer]" required></select>
          </div>
          <div class="mb-3">
            <label class="form-label"><?= lang('service') ?></label>
            <select class="form-select" id="entry-service" name="entry[id_services]" required></select>
          </div>
          <div class="mb-3">
            <label class="form-label"><?= lang('provider') ?></label>
            <select class="form-select" id="entry-provider" name="entry[id_users_provider]"></select>
          </div>
          <div class="mb-3">
            <label class="form-label"><?= lang('date') ?></label>
            <input type="date" class="form-control" name="entry[requested_date]" />
          </div>
          <div class="mb-3">
            <label class="form-label">Bildirim Kanalı</label>
            <select class="form-select" name="entry[notify_channel]">
              <option value="both">SMS + WhatsApp</option>
              <option value="sms">SMS</option>
              <option value="whatsapp">WhatsApp</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('cancel') ?></button>
        <button type="button" class="btn btn-primary" id="save-entry"><?= lang('save') ?></button>
      </div>
    </div>
  </div>
</div>

<!-- Proactive Notification Modal -->
<div class="modal fade" id="notify-modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-paper-plane me-2 text-primary"></i>Müşteriyi Önden Bilgilendir</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="notify-entry-id">
        <p class="text-muted small mb-3">Müşteriye boşalan veya açılan randevu aralığı hakkında önceden SMS/WhatsApp bilgilendirmesi gönderin.</p>
        <div class="mb-3">
          <label class="form-label fw-bold">Müşteri</label>
          <div id="notify-customer-display" class="form-control-plaintext text-primary fw-semibold"></div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Talep Edilen Hizmet</label>
          <div id="notify-service-display" class="form-control-plaintext"></div>
        </div>
        <div class="mb-3">
          <label class="form-label">Açılan Randevu Tarihi ve Saati</label>
          <input type="datetime-local" class="form-control" id="notify-slot-datetime">
        </div>
        <div class="mb-3">
          <label class="form-label">Gönderim Kanalı</label>
          <select class="form-select" id="notify-channel">
            <option value="both">WhatsApp + SMS</option>
            <option value="whatsapp">Sadece WhatsApp</option>
            <option value="sms">Sadece SMS</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="confirm-notify-btn">
          <i class="fas fa-paper-plane me-1"></i> Bildirimi Gönder
        </button>
      </div>
    </div>
  </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/pages/waitlist.js') ?>"></script>

<?php end_section('scripts'); ?>
