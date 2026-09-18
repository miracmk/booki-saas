<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>
<?php
/**
 * @var array $tables
 * @var array $reservations
 * @var array $waitlist
 * @var array $experiences
 * @var array $staff_members
 */
?>
<div class="container-fluid py-3" id="restaurant-floor-plan-page">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-utensils text-primary me-2"></i>Restoran Masa Planı & Canlı Operasyon</h4>
            <p class="text-muted small mb-0">Masaları canlı izleyin, sürükleyerek yerleşim düzenini ayarlayın, misafirleri oturtun ve adisyon açın.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('restaurant/reservations') ?>" class="btn btn-outline-dark">
                <i class="fas fa-calendar-alt me-1"></i> Rezervasyonlar
            </a>
            <a href="<?= site_url('adisyons') ?>" class="btn btn-outline-primary">
                <i class="fas fa-receipt me-1"></i> Adisyonlar
            </a>
            <button class="btn btn-primary" id="btn-new-table-modal" data-bs-toggle="modal" data-bs-target="#new-table-modal">
                <i class="fas fa-plus me-1"></i> Yeni Masa Ekle
            </button>
        </div>
    </div>

    <!-- Status Legend & Section Filter -->
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <!-- Section Filter -->
            <div class="btn-group btn-group-sm" role="group" id="section-filter-group">
                <button type="button" class="btn btn-dark active" onclick="filterSection('all', this)">Tüm Bölümler</button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterSection('Ana Salon', this)">Ana Salon</button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterSection('Teras', this)">Teras</button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterSection('Bahçe', this)">Bahçe</button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterSection('VIP', this)">VIP</button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterSection('Bar', this)">Bar</button>
            </div>

            <!-- Status Legend -->
            <div class="d-flex flex-wrap gap-3 small align-items-center">
                <span><span class="badge bg-success me-1">&nbsp;</span> Boş / Müsait</span>
                <span><span class="badge bg-primary me-1">&nbsp;</span> Oturuldu / Yeme-İçme</span>
                <span><span class="badge bg-warning text-dark me-1">&nbsp;</span> Hesap İstendi</span>
                <span><span class="badge bg-info text-white me-1">&nbsp;</span> Rezerve</span>
                <span><span class="badge bg-secondary me-1">&nbsp;</span> Temizlik</span>
            </div>
        </div>
    </div>

    <!-- Main Floor Plan Workspace -->
    <div class="row g-3">
        <!-- Floor Canvas Container -->
        <div class="col-12 col-xl-9">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
                    <span class="fw-bold small text-muted"><i class="fas fa-map me-1"></i>Yerleşim Planı (Sürükle & Bırak)</span>
                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Masaya tıklayarak adisyon ve rezervasyon işlemlerini yapabilirsiniz.</small>
                </div>
                <div class="card-body p-0 position-relative" style="min-height: 580px; background: #f8fafc; background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 20px 20px;" id="floor-plan-canvas">
                    <?php foreach ($tables as $t): ?>
                        <?php
                        $bg_class = 'bg-success';
                        $border_color = '#10b981';
                        if ($t['status'] === 'seated' || $t['status'] === 'dining') {
                            $bg_class = 'bg-primary';
                            $border_color = '#3b82f6';
                        } elseif ($t['status'] === 'bill_requested') {
                            $bg_class = 'bg-warning text-dark';
                            $border_color = '#f59e0b';
                        } elseif ($t['status'] === 'reserved') {
                            $bg_class = 'bg-info text-white';
                            $border_color = '#06b6d4';
                        } elseif ($t['status'] === 'cleaning') {
                            $bg_class = 'bg-secondary';
                            $border_color = '#6b7280';
                        }
                        ?>
                        <div class="floor-table position-absolute rounded-3 shadow-sm d-flex flex-column align-items-center justify-content-between p-2 cursor-pointer user-select-none transition-all"
                             id="table-box-<?= $t['id'] ?>"
                             data-id="<?= $t['id'] ?>"
                             data-section="<?= e($t['section']) ?>"
                             data-number="<?= e($t['table_number']) ?>"
                             data-status="<?= e($t['status']) ?>"
                             data-adisyon="<?= $t['current_id_adisyons'] ?: '' ?>"
                             data-capacity="<?= $t['capacity'] ?>"
                             style="left: <?= $t['pos_x'] ?>px; top: <?= $t['pos_y'] ?>px; width: <?= $t['width'] ?: 110 ?>px; height: <?= $t['height'] ?: 85 ?>px; border: 2px solid <?= $border_color ?>; background: #ffffff; cursor: move;"
                             onclick="handleTableClick(<?= $t['id'] ?>)">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <span class="badge <?= $bg_class ?> rounded-pill small" style="font-size: 11px;">
                                    Masa <?= e($t['table_number']) ?>
                                </span>
                                <small class="text-muted" style="font-size: 10px;"><i class="fas fa-users"></i> <?= $t['capacity'] ?></small>
                            </div>

                            <div class="text-center my-auto">
                                <?php if ($t['status'] === 'seated' || $t['status'] === 'dining'): ?>
                                    <div class="fw-bold text-dark small text-truncate" style="max-width: 90px;">
                                        <?= e($t['guest_first_name'] ? $t['guest_first_name'] : 'Masa ' . $t['table_number']) ?>
                                    </div>
                                    <span class="badge bg-light text-primary border" style="font-size: 10px;">
                                        <?= $t['adisyon_total'] ? number_format($t['adisyon_total'], 2) . ' ₺' : 'Açık' ?>
                                    </span>
                                <?php elseif ($t['status'] === 'reserved'): ?>
                                    <small class="text-info fw-semibold" style="font-size: 10px;">Rezerve</small>
                                <?php elseif ($t['status'] === 'cleaning'): ?>
                                    <small class="text-secondary fw-semibold" style="font-size: 10px;">Temizleniyor</small>
                                <?php else: ?>
                                    <small class="text-success fw-semibold" style="font-size: 10px;">Müsait</small>
                                <?php endif; ?>
                            </div>

                            <?php if ($t['minutes_seated'] > 0): ?>
                                <small class="text-muted" style="font-size: 9px;"><i class="far fa-clock"></i> <?= $t['minutes_seated'] ?> dk</small>
                            <?php else: ?>
                                <small class="text-muted text-truncate" style="font-size: 9px;"><?= e($t['section']) ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right Side: Today's Reservations & Live Waitlist Queue -->
        <div class="col-12 col-xl-3">
            <!-- Live Waitlist Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
                    <span class="fw-bold small"><i class="fas fa-hourglass-half text-warning me-1"></i>Bekleme Sırası</span>
                    <span class="badge bg-warning text-dark"><?= count($waitlist) ?></span>
                </div>
                <div class="card-body p-2" style="max-height: 250px; overflow-y: auto;">
                    <?php if (empty($waitlist)): ?>
                        <div class="text-center py-3 text-muted small">Sırada bekleyen misafir yok.</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush small">
                            <?php foreach ($waitlist as $w): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-1 py-2">
                                    <div>
                                        <div class="fw-bold"><?= e($w['name'] ?? $w['customer_name'] ?? 'Misafir') ?></div>
                                        <small class="text-muted"><?= e($w['phone_number'] ?? '') ?> (<?= $w['party_size'] ?? 2 ?> kişi)</small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="seatFromWaitlist(<?= $w['id'] ?>)">Oturt</button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Today's Reservations Card -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
                    <span class="fw-bold small"><i class="fas fa-calendar-day text-primary me-1"></i>Bugünün Rezervasyonları</span>
                    <span class="badge bg-primary"><?= count($reservations) ?></span>
                </div>
                <div class="card-body p-2" style="max-height: 300px; overflow-y: auto;">
                    <?php if (empty($reservations)): ?>
                        <div class="text-center py-3 text-muted small">Bugün için rezervasyon yok.</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush small">
                            <?php foreach ($reservations as $r): ?>
                                <li class="list-group-item px-1 py-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold text-dark"><?= date('H:i', strtotime($r['reservation_datetime'])) ?> - <?= e($r['guest_first_name'] . ' ' . $r['guest_last_name']) ?></span>
                                        <span class="badge bg-light text-dark border"><?= $r['party_size'] ?> Kişi</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Masa: <?= $r['table_number'] ? 'Masa ' . $r['table_number'] : 'Atanmadı' ?></small>
                                        <?php if ($r['status'] !== 'seated'): ?>
                                            <button class="btn btn-sm btn-success py-0 px-2" onclick="seatReservation(<?= $r['id'] ?>, <?= $r['id_restaurant_tables'] ?: 0 ?>)">Masa Aç</button>
                                        <?php else: ?>
                                            <span class="badge bg-success">Oturdu</span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Table Action / Seating / Status -->
<div class="modal fade" id="table-action-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="table-modal-title">Masa İşlemleri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="selected-table-id">
                
                <!-- Status Switcher -->
                <div class="mb-3">
                    <label class="form-label small fw-bold">Masa Durumu</label>
                    <select id="modal-table-status" class="form-select">
                        <option value="available">Boş / Müsait</option>
                        <option value="seated">Oturuldu (Masa Aç)</option>
                        <option value="dining">Yeme-İçme Devam Ediyor</option>
                        <option value="bill_requested">Hesap İstendi</option>
                        <option value="cleaning">Temizleniyor</option>
                    </select>
                </div>

                <!-- Server Assignment -->
                <div class="mb-3">
                    <label class="form-label small fw-bold">Sorumlu Garson / Personel</label>
                    <select id="modal-table-server" class="form-select">
                        <option value="">-- Personel Seçin --</option>
                        <?php foreach ($staff_members as $sm): ?>
                            <option value="<?= $sm['id'] ?>"><?= e($sm['first_name'] . ' ' . $sm['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Quick Adisyon Action -->
                <div id="table-adisyon-btn-box" class="p-3 bg-light rounded-3 mb-3 d-none">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold small d-block">Aktif Masa Adisyonu</span>
                            <small class="text-muted" id="modal-adisyon-info">-</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" id="btn-goto-adisyon">
                            <i class="fas fa-receipt me-1"></i> Adisyona Git / Sipariş
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary" onclick="saveTableModalStatus()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: New Table -->
<div class="modal fade" id="new-table-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle text-primary me-2"></i>Yeni Masa Tanımla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="new-table-form">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Masa Numarası *</label>
                        <input type="text" name="table_number" class="form-control" placeholder="Örn: 1, 12, T-4" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Bölüm / Salon</label>
                        <select name="section" class="form-select">
                            <option value="Ana Salon">Ana Salon</option>
                            <option value="Teras">Teras</option>
                            <option value="Bahçe">Bahçe</option>
                            <option value="VIP">VIP</option>
                            <option value="Bar">Bar</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kapasite (Kişi)</label>
                        <input type="number" name="capacity" class="form-control" value="4" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Masa Şekli</label>
                        <select name="shape" class="form-select">
                            <option value="rectangle">Dikdörtgen</option>
                            <option value="square">Kare</option>
                            <option value="round">Yuvarlak</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="submitNewTable()">Masayı Kaydet</button>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script>
// Drag and Drop Table Positions
document.addEventListener('DOMContentLoaded', function() {
    const tables = document.querySelectorAll('.floor-table');
    tables.forEach(table => {
        let isDragging = false;
        let startX, startY, initialLeft, initialTop;

        table.addEventListener('mousedown', function(e) {
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            initialLeft = parseInt(table.style.left, 10) || 0;
            initialTop = parseInt(table.style.top, 10) || 0;
            table.style.zIndex = 1000;
        });

        document.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            table.style.left = Math.max(0, initialLeft + dx) + 'px';
            table.style.top = Math.max(0, initialTop + dy) + 'px';
        });

        document.addEventListener('mouseup', function(e) {
            if (!isDragging) return;
            isDragging = false;
            table.style.zIndex = '';
            
            // Save coordinates via AJAX
            const tableId = table.dataset.id;
            const posX = parseInt(table.style.left, 10);
            const posY = parseInt(table.style.top, 10);

            const fd = new FormData();
            fd.append('id', tableId);
            fd.append('pos_x', posX);
            fd.append('pos_y', posY);
            fetch('<?= site_url('restaurant/save_layout') ?>', { method: 'POST', body: fd });
        });
    });
});

function filterSection(section, btn) {
    document.querySelectorAll('#section-filter-group .btn').forEach(b => b.classList.remove('active', 'btn-dark'));
    btn.classList.add('active', 'btn-dark');

    const tables = document.querySelectorAll('.floor-table');
    tables.forEach(t => {
        if (section === 'all' || t.dataset.section === section) {
            t.style.display = 'flex';
        } else {
            t.style.display = 'none';
        }
    });
}

function handleTableClick(tableId) {
    const tableEl = document.getElementById('table-box-canvas') || document.getElementById('table-box-' + tableId);
    if (!tableEl) return;

    document.getElementById('selected-table-id').value = tableId;
    document.getElementById('table-modal-title').innerText = 'Masa ' + tableEl.dataset.number + ' (' + tableEl.dataset.section + ')';
    document.getElementById('modal-table-status').value = tableEl.dataset.status;

    const adisyonId = tableEl.dataset.adisyon;
    const adizedBox = document.getElementById('table-adisyon-btn-box');
    if (adisyonId) {
        adizedBox.classList.remove('d-none');
        document.getElementById('btn-goto-adisyon').onclick = () => window.location.href = '<?= site_url('adisyons') ?>';
    } else {
        adizedBox.classList.add('d-none');
    }

    const modal = new bootstrap.Modal(document.getElementById('table-action-modal'));
    modal.show();
}

function saveTableModalStatus() {
    const tableId = document.getElementById('selected-table-id').value;
    const status = document.getElementById('modal-table-status').value;
    const serverId = document.getElementById('modal-table-server').value;

    const fd = new FormData();
    fd.append('id_tables', tableId);
    fd.append('status', status);
    if (serverId) fd.append('id_users_server', serverId);

    fetch('<?= site_url('restaurant/set_table_status') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            }
        });
}

function seatReservation(resId, tableId) {
    const fd = new FormData();
    fd.append('id_reservations', resId);
    if (tableId) fd.append('id_tables', tableId);

    fetch('<?= site_url('restaurant/seat') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert(data.message || 'Hata oluştu.');
            }
        });
}

function submitNewTable() {
    const form = document.getElementById('new-table-form');
    const fd = new FormData(form);

    fetch('<?= site_url('restaurant/save_table') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert(data.message || 'Masa eklenemedi.');
            }
        });
}
</script>
<?php end_section('scripts'); ?>
