<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>
<div class="container-fluid backend-page py-3" id="randevuburada-reservations-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1 fw-bold text-dark d-flex align-items-center">
                <i class="fas fa-calendar-check me-2 text-primary"></i>
                RandevuBurada Rezervasyonları
            </h4>
            <p class="text-muted small mb-0">RandevuBurada pazaryeri üzerinden işletmenize yönlendirilen müşteri rezervasyonlarını ve randevu taleplerini takip edin.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('calendar') ?>" class="btn btn-outline-secondary rounded-3 px-3 shadow-sm">
                <i class="fas fa-calendar-alt me-1"></i> Genel Takvime Git
            </a>
        </div>
    </div>

    <!-- KPI Kartları -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">Toplam Pazaryeri Talebi</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1"><?= (int)($stats['total'] ?? 0) ?></h3>
                    </div>
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3 fs-4">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">Onaylanan Rezervasyonlar</span>
                        <h3 class="mb-0 fw-bold text-success mt-1"><?= (int)($stats['confirmed'] ?? 0) ?></h3>
                    </div>
                    <div class="rounded-3 bg-success bg-opacity-10 text-success p-3 fs-4">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">Bekleyen Talepler</span>
                        <h3 class="mb-0 fw-bold text-warning mt-1"><?= (int)($stats['pending'] ?? 0) ?></h3>
                    </div>
                    <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-3 fs-4">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">Pazaryeri Ciro Hacmi</span>
                        <h3 class="mb-0 fw-bold text-info mt-1"><?= number_format((float)($stats['total_volume'] ?? 0), 2) ?> ₺</h3>
                    </div>
                    <div class="rounded-3 bg-info bg-opacity-10 text-info p-3 fs-4">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert placeholder -->
    <div id="status-alert" class="d-none alert alert-success alert-dismissible fade show rounded-3" role="alert">
        <span id="status-alert-text"></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>

    <!-- Rezervasyon Tablosu -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-list me-2 text-primary"></i>Rezervasyon Listesi</h6>
            <div class="btn-group btn-group-sm rounded-3 shadow-none">
                <button type="button" class="btn btn-outline-secondary active" onclick="filterReservations('all', this)">Tümü</button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterReservations('confirmed', this)">Onaylananlar</button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterReservations('pending', this)">Bekleyenler</button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterReservations('cancelled', this)">İptaller</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="reservationsTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Müşteri</th>
                        <th>Hizmet</th>
                        <th>Tarih & Saat</th>
                        <th>Uzman / Personel</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th class="text-end pe-4">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($appointments)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-calendar-times fa-2x mb-2 opacity-50"></i>
                                <p class="mb-0">Henüz RandevuBurada pazaryeri rezervasyonu bulunmuyor.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($appointments as $apt): ?>
                            <?php 
                            $status = strtolower($apt['status'] ?? 'reserved');
                            $customer_name = trim(($apt['customer_first_name'] ?? '') . ' ' . ($apt['customer_last_name'] ?? ''));
                            $customer_phone = $apt['customer_phone'] ?? '';
                            $clean_phone = preg_replace('/[^0-9]/', '', $customer_phone);
                            ?>
                            <tr class="res-row" data-status="<?= htmlspecialchars($status) ?>" id="row-apt-<?= $apt['id'] ?>">
                                <td class="ps-4">
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($customer_name ?: 'Müşteri') ?></div>
                                    <small class="text-muted"><i class="fas fa-phone me-1"></i><?= htmlspecialchars($customer_phone ?: 'Telefon yok') ?></small>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark"><?= htmlspecialchars($apt['service_name'] ?? 'Hizmet') ?></div>
                                    <small class="text-muted"><i class="far fa-clock me-1"></i><?= (int)($apt['service_duration'] ?? 30) ?> dk</small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars(date('d.m.Y', strtotime($apt['start_datetime']))) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars(date('H:i', strtotime($apt['start_datetime']))) ?> - <?= htmlspecialchars(date('H:i', strtotime($apt['end_datetime']))) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">
                                        <i class="fas fa-user-tie me-1"></i><?= htmlspecialchars(trim(($apt['provider_first_name'] ?? '') . ' ' . ($apt['provider_last_name'] ?? '')) ?: 'Herhangi Biri') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark"><?= number_format((float)($apt['service_price'] ?? 0), 2) ?> ₺</span>
                                </td>
                                <td>
                                    <?php if ($status === 'confirmed'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success" id="badge-apt-<?= $apt['id'] ?>"><i class="fas fa-check-circle me-1"></i> Onaylandı</span>
                                    <?php elseif ($status === 'cancelled'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger" id="badge-apt-<?= $apt['id'] ?>"><i class="fas fa-times-circle me-1"></i> İptal Edildi</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-dark" id="badge-apt-<?= $apt['id'] ?>"><i class="fas fa-clock me-1"></i> Beklemede</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <?php if (!empty($clean_phone)): ?>
                                            <a href="https://wa.me/<?= $clean_phone ?>?text=<?= rawurlencode('Merhaba ' . $customer_name . ', RandevuBurada üzerinden yaptığınız ' . date('d.m.Y H:i', strtotime($apt['start_datetime'])) . ' tarihli randevunuz hakkında iletişime geçiyoruz.') ?>" target="_blank" rel="noopener" class="btn btn-outline-success" title="WhatsApp Mesaj Gönder">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-outline-primary" title="Onayla" onclick="updateAppointmentStatus(<?= $apt['id'] ?>, 'confirmed')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" title="İptal Et" onclick="updateAppointmentStatus(<?= $apt['id'] ?>, 'cancelled')">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function updateAppointmentStatus(appointmentId, status) {
    const formData = new FormData();
    formData.append('appointment_id', appointmentId);
    formData.append('status', status);
    formData.append('<?= $this->security->get_csrf_token_name() ?>', '<?= $this->security->get_csrf_hash() ?>');

    fetch('<?= site_url('randevuburada/update_reservation_status') ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        const row = document.getElementById('row-apt-' + appointmentId);
        const badge = document.getElementById('badge-apt-' + appointmentId);
        row.dataset.status = status;
        if (status === 'confirmed') {
            badge.className = 'badge bg-success bg-opacity-10 text-success';
            badge.innerHTML = '<i class="fas fa-check-circle me-1"></i> Onaylandı';
        } else if (status === 'cancelled') {
            badge.className = 'badge bg-danger bg-opacity-10 text-danger';
            badge.innerHTML = '<i class="fas fa-times-circle me-1"></i> İptal Edildi';
        }
        showAlert(data.message || 'Rezervasyon güncellendi.');
    })
    .catch(err => {
        alert('Hata oluştu: ' + err.message);
    });
}

function filterReservations(status, btn) {
    document.querySelectorAll('#randevuburada-reservations-page .btn-group .btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const rows = document.querySelectorAll('.res-row');
    rows.forEach(r => {
        if (status === 'all' || r.dataset.status === status || (status === 'pending' && r.dataset.status === 'reserved')) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

function showAlert(msg) {
    const alertBox = document.getElementById('status-alert');
    const alertText = document.getElementById('status-alert-text');
    alertText.innerText = msg;
    alertBox.className = 'alert alert-success alert-dismissible fade show rounded-3';
    alertBox.classList.remove('d-none');
}
</script>
<?php end_section('content'); ?>
