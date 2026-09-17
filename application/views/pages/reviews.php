<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-3" id="reviews-page">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-light">
      <i class="fas fa-star me-2 text-primary"></i>
      <?= vars('page_title') ?>
    </h4>
  </div>

    <ul class="nav nav-tabs mb-3" id="reviews-tabs" role="tablist">
      <?php foreach (['requested' => 'Talep Edilen', 'pending' => 'Bekleyen', 'published' => 'Yayınlanan', 'rejected' => 'Reddedilen'] as $key => $label): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?= $key === 'pending' ? 'active' : '' ?>" id="<?= $key ?>-tab" data-bs-toggle="tab" data-bs-target="#<?= $key ?>-pane" type="button" role="tab">
            <?= $label ?>
            <span class="badge bg-secondary count-badge" id="count-<?= $key ?>">0</span>
          </button>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="tab-content" id="reviews-tab-content">
      <?php foreach (['requested' => 'requested', 'pending' => 'pending', 'published' => 'published', 'rejected' => 'rejected'] as $key => $status): ?>
        <div class="tab-pane fade <?= $key === 'pending' ? 'show active' : '' ?>" id="<?= $key ?>-pane" role="tabpanel">
          <div class="card border-0 shadow-sm">
            <div class="table-responsive">
              <table class="table table-hover" id="reviews-table-<?= $key ?>">
                <thead>
                  <tr>
                    <th>Müşteri</th>
                    <th>Puan</th>
                    <th>Yorum</th>
                    <th>Gönderim</th>
                    <th><?= lang('actions') ?></th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="alert alert-info mt-3 mb-0">
      <i class="fas fa-info-circle"></i>
      Yorumlar, tamamlanan randevulardan sonra müşteriye SMS/WhatsApp ile gönderilen tek kullanımlık bağlantı üzerinden gelir. Yayınlanan yorumlar marketplace profilinizde görünür.
    </div>
  </div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script>
  window.scriptVars = Object.assign({}, window.scriptVars || {}, {
    reviews: <?= json_encode($reviews ?? []) ?>,
    counts: <?= json_encode(script_vars('counts')) ?>,
    routes: <?= json_encode(script_vars('routes')) ?>,
    initials: <?= json_encode(script_vars('initials')) ?>
  });
</script>
<script src="<?= asset_url('assets/js/pages/reviews.js') ?>"></script>

<?php end_section('scripts'); ?>