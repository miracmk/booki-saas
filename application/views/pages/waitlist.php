<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="wrapper">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col">
        <h1 class="page-title">
          <i class="fas fa-hourglass-half"></i>
          <?= $page_title ?>
        </h1>
      </div>
      <div class="col-auto">
        <div class="btn-toolbar" role="toolbar">
          <button class="btn btn-primary" id="add-entry" title="<?= lang('add') ?>">
            <i class="fas fa-plus"></i>
            <?= lang('add') ?>
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
                  <th><?= lang('id') ?></th>
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

<script src="<?= asset('js/pages/waitlist.js') ?>"></script>
