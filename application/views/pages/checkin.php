<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>
<?php
/**
 * @var array $occupancy
 * @var array $history
 * @var array $customers
 */
?>
<div class="container-fluid py-3" id="checkin-dashboard-page">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-sign-in-alt text-primary me-2"></i>Giriş / Çıkış & Tesis Doluluk Yönetimi</h4>
            <p class="text-muted small mb-0">QR kod, telefon veya isim ile hızlı giriş-çıkış yapın, üyelik haklarını ve canlı tesis doluluğunu kontrol edin.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('checkin/kiosk') ?>" target="_blank" class="btn btn-dark" id="btn-kiosk-mode">
                <i class="fas fa-tablet-alt me-1"></i> Kiosk Modunu Aç (Tablet)
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#checkin-modal">
                <i class="fas fa-plus me-1"></i> Hızlı Giriş Yap
            </button>
        </div>
    </div>

    <!-- Occupancy Meter & Quick Search Row -->
    <div class="row g-3 mb-4">
        <!-- Live Occupancy Gauge -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold text-dark"><i class="fas fa-users text-primary me-2"></i>Canlı Tesis Doluluğu</span>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1">Canlı</span>
                    </div>

                    <div class="text-center my-3">
                        <h1 class="display-3 fw-bold text-primary mb-0"><?= $occupancy['current_count'] ?></h1>
                        <span class="text-muted">İçerideki Kişi / Kapasite: <strong><?= $occupancy['max_capacity'] ?></strong></span>
                    </div>

                    <div>
                        <div class="progress mb-2" style="height: 12px;" id="occupancy-bar">
                            <div class="progress-bar <?= $occupancy['occupancy_rate'] > 85 ? 'bg-danger' : ($occupancy['occupancy_rate'] > 60 ? 'bg-warning' : 'bg-success') ?>"
                                 role="progressbar"
                                 style="width: <?= $occupancy['occupancy_rate'] ?>%;"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Doluluk Oranı: %<?= $occupancy['occupancy_rate'] ?></span>
                            <span>Kalan Kontenjan: <?= max(0, $occupancy['max_capacity'] - $occupancy['current_count']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Check-in Search Box -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-bolt text-warning me-2"></i>Anında Giriş / Çıkış Ara</h6>
                </div>
                <div class="card-body p-4">
                    <form id="quick-checkin-form" onsubmit="event.preventDefault(); submitQuickCheckin();">
                        <div class="input-group input-group-lg mb-3">
                            <span class="input-group-text bg-light"><i class="fas fa-qrcode"></i></span>
                            <input type="text" id="checkin-quick-input" class="form-control" placeholder="Telefon numarası, QR kod veya Müşteri No..." autofocus autocomplete="off">
                            <button class="btn btn-primary px-4 fw-bold" type="submit">
                                <i class="fas fa-sign-in-alt me-1"></i> GİRİŞ YAP
                            </button>
                        </div>
                        <div id="checkin-feedback" class="alert d-none py-2 mb-0"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Guests Inside Table -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center border-bottom">
            <h6 class="mb-0 fw-bold"><i class="fas fa-user-check text-success me-2"></i>Şu An İçeride Olan Misafirler (<?= count($occupancy['active_guests']) ?>)</h6>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.location.reload();">
                <i class="fas fa-sync-alt"></i> Yenile
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th class="ps-3"><?= lang('customer') ?></th>
                            <th><?= lang('phone') ?></th>
                            <th>Üyelik / Paket</th>
                            <th>Giriş Saati</th>
                            <th>İçerideki Süre</th>
                            <th>Yöntem</th>
                            <th class="text-end pe-3"><?= lang('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($occupancy['active_guests'])): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Şu an içeride misafir bulunmuyor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($occupancy['active_guests'] as $g): ?>
                                <?php
                                $mins = max(1, round((time() - strtotime($g['entry_timestamp'])) / 60));
                                ?>
                                <tr>
                                    <td class="ps-3 fw-bold">
                                        <?= e($g['customer_first_name'] . ' ' . $g['customer_last_name']) ?>
                                    </td>
                                    <td><?= e($g['customer_phone'] ?: '-') ?></td>
                                    <td>
                                        <?= !empty($g['membership_plan_name']) ? '<span class="badge bg-info text-white">' . e($g['membership_plan_name']) . '</span>' : '<span class="text-muted">Standart</span>' ?>
                                    </td>
                                    <td class="text-primary fw-semibold"><?= date('H:i', strtotime($g['entry_timestamp'])) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= $mins ?> dakika</span></td>
                                    <td><span class="badge bg-secondary"><?= strtoupper(e($g['checkin_method'])) ?></span></td>
                                    <td class="text-end pe-3">
                                        <button class="btn btn-sm btn-outline-danger py-1 px-3" onclick="doCheckout(<?= $g['id'] ?>)">
                                            <i class="fas fa-sign-out-alt me-1"></i> Çıkış Ver
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Manual Checkin -->
<div class="modal fade" id="checkin-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus text-primary me-2"></i>Müşteri Girişi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="manual-checkin-form">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Müşteri Seçin *</label>
                        <select name="id_users_customer" class="form-select" required>
                            <option value="">-- Müşteri Seçin --</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['first_name'] . ' ' . $c['last_name']) ?> (<?= e($c['phone_number']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Giriş Yöntemi</label>
                        <select name="checkin_method" class="form-select">
                            <option value="manual">Manuel Resepsiyon</option>
                            <option value="qr">QR Kod Tarama</option>
                            <option value="phone">Telefon Numarası</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="submitManualCheckin()">Girişi Kaydet</button>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script>
function submitQuickCheckin() {
    const inputEl = document.getElementById('checkin-quick-input') || document.getElementById('quick-input');
    const input = inputEl ? inputEl.value.trim() : '';
    if (!input) return;

    const fd = new FormData();
    if (input.match(/^\d+$/) && input.length >= 7) {
        fd.append('phone', input);
    } else {
        fd.append('qr_token', input);
    }
    fd.append('checkin_method', 'phone');

    fetch('<?= site_url('checkin/do_checkin') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            const fb = document.getElementById('checkin-feedback');
            fb.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning');

            if (data.status === 'success') {
                fb.classList.add('alert-success');
                fb.innerHTML = `<strong>Başarılı!</strong> ${data.customer.first_name} ${data.customer.last_name} girişi yapıldı (Saat: ${data.entry_time}).`;
                if (inputEl) inputEl.value = '';
                setTimeout(() => window.location.reload(), 1500);
            } else if (data.status === 'already_inside') {
                fb.classList.add('alert-warning');
                fb.innerHTML = `<strong>Bilgi:</strong> ${data.message}`;
            } else {
                fb.classList.add('alert-danger');
                fb.innerHTML = `<strong>Hata:</strong> ${data.message || 'Giriş yapılamadı.'}`;
            }
        });
}

function doCheckout(checkinId) {
    const fd = new FormData();
    fd.append('checkin_id', checkinId);

    fetch('<?= site_url('checkin/do_checkout') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert(data.message || 'Çıkış verilemedi.');
            }
        });
}

function submitManualCheckin() {
    const form = document.getElementById('manual-checkin-form');
    const fd = new FormData(form);

    fetch('<?= site_url('checkin/do_checkin') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert(data.message || 'Giriş yapılamadı.');
            }
        });
}
</script>
<?php end_section('scripts'); ?>
