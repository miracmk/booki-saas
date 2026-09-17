<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container-fluid backend-page py-3 px-md-4" style="max-width: 1400px;" id="marketing-page">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-light">
      <i class="fas fa-bullhorn me-2 text-primary"></i>
      <?= vars('page_title') ?>
    </h4>
    <div class="btn-toolbar" role="toolbar">
      <?php if (vars('initials')['can_add']): ?>
        <button class="btn btn-outline-secondary me-2" id="refresh-all-segments" title="Tüm segment boyutlarını yeniden hesapla">
          <i class="fas fa-sync-alt me-1"></i>
          Segmentleri Güncelle
        </button>
        <button class="btn btn-primary me-2" id="add-segment" title="Yeni segment oluştur">
          <i class="fas fa-plus me-1"></i>
          Yeni Segment
        </button>
        <button class="btn btn-success" id="add-campaign" title="Yeni kampanya oluştur">
          <i class="fas fa-paper-plane me-1"></i>
          Yeni Kampanya
        </button>
      <?php endif; ?>
    </div>
  </div>

    <ul class="nav nav-tabs mb-3" id="marketing-tabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="segments-tab" data-bs-toggle="tab" data-bs-target="#segments-pane" type="button" role="tab">
          <i class="fas fa-users"></i>
          Segmentler
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="campaigns-tab" data-bs-toggle="tab" data-bs-target="#campaigns-pane" type="button" role="tab">
          <i class="fas fa-paper-plane"></i>
          Kampanyalar
        </button>
      </li>
    </ul>

    <div class="tab-content" id="marketing-tab-content">
      <div class="tab-pane fade show active" id="segments-pane" role="tabpanel">
        <div class="card border-0 shadow-sm">
          <div class="table-responsive">
            <table class="table table-hover" id="segments-table">
              <thead>
                <tr>
                  <th>Ad</th>
                  <th>Tür</th>
                  <th>Kural</th>
                  <th class="text-center">Üye Sayısı</th>
                  <th class="text-center">Durum</th>
                  <th><?= lang('actions') ?></th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="tab-pane fade" id="campaigns-pane" role="tabpanel">
        <div class="card border-0 shadow-sm">
          <div class="table-responsive">
            <table class="table table-hover" id="campaigns-table">
              <thead>
                <tr>
                  <th>Ad</th>
                  <th>Segment</th>
                  <th>Kanal</th>
                  <th class="text-center">Alıcı</th>
                  <th class="text-center">Gönderildi</th>
                  <th class="text-center">Başarısız</th>
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

<!-- Segment Modal -->
<div class="modal fade" id="segment-modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="segment-modal-title">Yeni Segment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Ad</label>
          <input type="text" class="form-control" id="segment-name" placeholder="Örn: VIP Müşteriler">
        </div>
        <div class="mb-3">
          <label class="form-label">Tür</label>
          <select class="form-select" id="segment-type">
            <option value="vip">VIP (asgari randevu sayısı)</option>
            <option value="inactive">Pasif (uzun süredir randevu yok)</option>
            <option value="birthday">Doğum günü yaklaşanlar</option>
            <option value="all">Tüm müşteriler</option>
            <option value="custom">Özel liste</option>
          </select>
        </div>
        <div id="segment-rule-vip" class="mb-3 d-none">
          <label class="form-label">Asgari randevu sayısı</label>
          <input type="number" class="form-control" id="rule-min-appointments" min="1" value="5">
        </div>
        <div id="segment-rule-inactive" class="mb-3 d-none">
          <label class="form-label">Pasif gün sayısı</label>
          <input type="number" class="form-control" id="rule-inactive-days" min="1" value="60">
        </div>
        <div id="segment-rule-birthday" class="mb-3 d-none">
          <label class="form-label">Kutlama öncesi gün</label>
          <input type="number" class="form-control" id="rule-days-ahead" min="0" value="14">
          <div class="form-text">Doğum tarihi, müşteri kaydının «Özel Alan 1» sütununda tutulur.</div>
        </div>
        <div id="segment-rule-custom" class="mb-3 d-none">
          <label class="form-label">Müşteri ID'leri (virgülle ayırın)</label>
          <textarea class="form-control" id="rule-customer-ids" rows="3" placeholder="1, 2, 3"></textarea>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="segment-enabled" checked>
          <label class="form-check-label" for="segment-enabled">Aktif</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('cancel') ?></button>
        <button type="button" class="btn btn-primary" id="save-segment">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Campaign Modal -->
<div class="modal fade" id="campaign-modal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="campaign-modal-title">Yeni Kampanya</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Ad</label>
            <input type="text" class="form-control" id="campaign-name" placeholder="Örn: Yılbaşı İndirimi">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Hedef Segment</label>
            <select class="form-select" id="campaign-segment"></select>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Kanal</label>
            <select class="form-select" id="campaign-channel">
              <option value="email">E-posta</option>
              <option value="sms">SMS</option>
              <option value="whatsapp">WhatsApp</option>
              <option value="telegram">Telegram</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Konu (sadece e-posta)</label>
            <input type="text" class="form-control" id="campaign-subject" placeholder="Örn: Özel fırsat">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Mesaj</label>
          <textarea class="form-control" id="campaign-message" rows="6" placeholder="Merge alanları: {{customer_name}}, {{company_name}}"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('cancel') ?></button>
        <button type="button" class="btn btn-primary" id="save-campaign">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Send Modal -->
<div class="modal fade" id="send-modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Kampanya Gönderimi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning mb-0" id="send-alert">Bu işlem geri alınamaz. Alıcı listesi kampanyanın hedef segmentine göre hazırlanacak ve mesajlar seçilen kanaldan gönderilecektir.</div>
        <div class="progress mt-3 d-none" id="send-progress-container">
          <div class="progress-bar" id="send-progress-bar" style="width: 0%">0%</div>
        </div>
        <p class="mt-2 mb-0 text-muted" id="send-status">Gönderim başlatılıyor...</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('cancel') ?></button>
        <button type="button" class="btn btn-success" id="confirm-send">Gönderimi Başlat</button>
      </div>
    </div>
  </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script>
  window.scriptVars = Object.assign({}, window.scriptVars || {}, {
    segments: <?= json_encode($segments ?? []) ?>,
    campaigns: <?= json_encode($campaigns ?? []) ?>,
    initials: <?= json_encode(script_vars('initials')) ?>
  });
  var EWA = {
    segment_selected_id: <?= json_encode((int) (isset($segments[0]) ? $segments[0]['id'] : 0)) ?>,
    segments_map: <?= json_encode(array_combine(
        array_map(static fn($s) => (string) $s['id'], $segments ?? []),
        $segments ?? []
    )) ?>,
    campaigns: <?= json_encode($campaigns ?? []) ?>,
    prime_data: <?= json_encode(array_filter(array_column($segments ?? [], 'rules'))) ?>,
    initials: <?= json_encode(script_vars('initials')) ?>
  };
</script>
<script src="<?= asset_url('assets/js/pages/marketing.js') ?>"></script>

<?php end_section('scripts'); ?>