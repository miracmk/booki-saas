<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>
<?php
/**
 * @var string $date
 * @var string|null $status
 * @var array $reservations
 * @var array $tables
 * @var array $experiences
 */
?>
<div class="container-fluid py-3" id="restaurant-reservations-page">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-calendar-alt text-primary me-2"></i>Restoran Rezervasyonları</h4>
            <p class="text-muted small mb-0">Tüm masa rezervasyonlarını yönetin, deneyimleri (tasting, chef's table) ve özel istekleri takip edin.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('restaurant') ?>" class="btn btn-outline-dark">
                <i class="fas fa-map me-1"></i> Masa Planına Dön
            </a>
            <button class="btn btn-primary" id="btn-new-res-modal" data-bs-toggle="modal" data-bs-target="#new-reservation-modal">
                <i class="fas fa-plus me-1"></i> Yeni Rezervasyon
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-sm-4 col-md-3">
                    <label class="form-label small fw-bold">Tarih</label>
                    <input type="date" name="date" class="form-control" value="<?= e($date) ?>">
                </div>
                <div class="col-12 col-sm-4 col-md-3">
                    <label class="form-label small fw-bold">Durum</label>
                    <select name="status" class="form-select">
                        <option value="">-- Tümü --</option>
                        <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>Onaylandı</option>
                        <option value="seated" <?= $status === 'seated' ? 'selected' : '' ?>>Oturuldu</option>
                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Tamamlandı</option>
                        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>İptal</option>
                    </select>
                </div>
                <div class="col-12 col-sm-4 col-md-2">
                    <button type="submit" class="btn btn-dark w-100"><i class="fas fa-search me-1"></i> Filtrele</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reservations Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="restaurant-res-table">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th class="ps-3">Saat</th>
                            <th>Misafir Adı</th>
                            <th>Kişi</th>
                            <th>Masa</th>
                            <th>Deneyim / Paket</th>
                            <th>Özel İstek / Alerji</th>
                            <th><?= lang('status') ?></th>
                            <th class="text-end pe-3"><?= lang('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservations)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-calendar-times fa-3x mb-3 text-secondary opacity-50"></i>
                                    <h6>Bu tarih için kayıtlı rezervasyon bulunamadı.</h6>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reservations as $r): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-primary">
                                        <?= date('H:i', strtotime($r['reservation_datetime'])) ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= e($r['guest_first_name'] . ' ' . $r['guest_last_name']) ?>
                                            <?php if ($r['is_vip']): ?>
                                                <span class="badge bg-warning text-dark ms-1">VIP</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?= e($r['guest_phone'] ?: '') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= $r['party_size'] ?> Kişi</span>
                                    </td>
                                    <td>
                                        <?= $r['table_number'] ? '<span class="badge bg-secondary">Masa ' . e($r['table_number']) . '</span>' : '<span class="text-muted">Atanmadı</span>' ?>
                                    </td>
                                    <td>
                                        <?= !empty($r['experience_title']) ? '<span class="badge bg-info text-white">' . e($r['experience_title']) . '</span>' : '<span class="text-muted">Standart</span>' ?>
                                    </td>
                                    <td class="small">
                                        <?php if (!empty($r['special_occasion'])): ?>
                                            <span class="badge bg-light text-dark border me-1"><i class="fas fa-gift text-danger me-1"></i><?= e($r['special_occasion']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($r['allergies'])): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><i class="fas fa-exclamation-triangle me-1"></i><?= e($r['allergies']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['status'] === 'seated'): ?>
                                            <span class="badge bg-success">Oturuldu</span>
                                        <?php elseif ($r['status'] === 'confirmed'): ?>
                                            <span class="badge bg-primary">Onaylandı</span>
                                        <?php elseif ($r['status'] === 'cancelled'): ?>
                                            <span class="badge bg-danger">İptal</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark"><?= e($r['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <?php if ($r['status'] !== 'seated'): ?>
                                            <button class="btn btn-sm btn-success py-1 px-2" onclick="seatReservationDirect(<?= $r['id'] ?>, <?= $r['id_restaurant_tables'] ?: 0 ?>)">
                                                <i class="fas fa-sign-in-alt me-1"></i> Masaya Oturt
                                            </button>
                                        <?php endif; ?>
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

<!-- Modal: New Reservation -->
<div class="modal fade" id="new-reservation-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-calendar-plus text-primary me-2"></i>Yeni Restoran Rezervasyonu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="new-res-form">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Müşteri Seçin *</label>
                        <select name="id_users_customer" class="form-select select2" required>
                            <option value="">-- Müşteri Seçin --</option>
                            <?php
                            $cust_list = $customers ?? [];
                            foreach ($cust_list as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['first_name'] . ' ' . $c['last_name']) ?> (<?= e($c['phone_number'] ?? '') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label small fw-bold">Tarih & Saat *</label>
                            <input type="datetime-local" name="reservation_datetime" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold">Kişi Sayısı</label>
                            <input type="number" name="party_size" class="form-control" value="2" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tercih Edilen Masa (İsteğe Bağlı)</label>
                        <select name="id_restaurant_tables" class="form-select">
                            <option value="">-- Otomatik / Girişte Belirle --</option>
                            <?php foreach ($tables as $t): ?>
                                <option value="<?= $t['id'] ?>">Masa <?= e($t['table_number']) ?> (<?= e($t['section']) ?> - <?= $t['capacity'] ?> Kişilik)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Özel Gün</label>
                            <select name="special_occasion" class="form-select">
                                <option value="">-- Yok --</option>
                                <option value="Doğum Günü">Doğum Günü</option>
                                <option value="Yıl Dönümü">Yıl Dönümü</option>
                                <option value="İş Yemeği">İş Yemeği</option>
                                <option value="Romantik Akşam">Romantik Akşam</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Alerjiler</label>
                            <input type="text" name="allergies" class="form-control" placeholder="Örn: Gluten, Fıstık">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="submitReservation()">Rezervasyonu Oluştur</button>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script>
function submitReservation() {
    const form = document.getElementById('new-res-form');
    const fd = new FormData(form);

    fetch('<?= site_url('restaurant/save_reservation') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert(data.message || 'Hata oluştu.');
            }
        });
}

function seatReservationDirect(resId, tableId) {
    const fd = new FormData();
    fd.append('id_reservations', resId);
    if (tableId) fd.append('id_tables', tableId);

    fetch('<?= site_url('restaurant/seat') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = '<?= site_url('restaurant') ?>';
            } else {
                alert(data.message || 'Hata oluştu.');
            }
        });
}
</script>
<?php end_section('scripts'); ?>
