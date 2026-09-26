<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-3" id="reviews-page">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-light">
      <i class="fas fa-star me-2 text-primary"></i>
      <?= vars('page_title') ?>
    </h4>
  </div>

  <div class="row g-2 mb-3 align-items-center">
    <div class="col-md-4 col-sm-6">
      <label class="form-label small text-muted mb-1">Uzman / Sağlayıcı Filtresi</label>
      <select id="review-filter-provider" class="form-select form-select-sm">
        <option value="">Tüm Uzmanlar</option>
        <?php foreach (($providers ?? []) as $prov): ?>
          <option value="<?= $prov['id'] ?>"><?= htmlspecialchars(trim($prov['first_name'] . ' ' . $prov['last_name'])) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4 col-sm-6">
      <label class="form-label small text-muted mb-1">Oda / İstasyon Filtresi</label>
      <select id="review-filter-station" class="form-select form-select-sm">
        <option value="">Tüm Odalar / İstasyonlar</option>
        <?php foreach (($stations ?? []) as $st): ?>
          <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
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
            <table class="table table-hover align-middle" id="reviews-table-<?= $key ?>">
              <thead>
                <tr>
                  <th>Kim (Müşteri)</th>
                  <th>Kime (Uzman)</th>
                  <th>Oda / İstasyon</th>
                  <th>Genel Puan</th>
                  <th>Oda Değerlendirmesi</th>
                  <th>Yorum</th>
                  <th>Tarih</th>
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