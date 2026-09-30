<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container-fluid backend-page py-4 px-md-5" id="attendance-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-clock text-primary me-2"></i> PDKS, Vardiya & Puantaj Takibi</h2>
            <p class="text-muted mb-0">Canlı giriş-çıkış logları, esnek vardiya çizelgeleme ve aylık resmi puantaj cetveli.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalManualPunch">
                <i class="fas fa-fingerprint me-1"></i> Manuel Punch / Giriş-Çıkış
            </button>
            <a href="<?= site_url('attendance/kiosk') ?>" target="_blank" class="btn btn-dark">
                <i class="fas fa-tablet-alt me-1"></i> Kiosk / Tablet Terminali
            </a>
        </div>
    </div>

    <!-- Nav Tabs -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs nav-justified border-bottom-0" role="tablist">
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold <?= $active_tab === 'daily' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-daily">
                        <i class="fas fa-calendar-day me-2"></i> Günlük Puantaj & Canlı Takip
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold <?= $active_tab === 'monthly' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-monthly">
                        <i class="fas fa-table me-2"></i> Aylık Puantaj Cetveli (Bordro Öncesi)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold <?= $active_tab === 'shifts' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-shifts">
                        <i class="fas fa-business-time me-2"></i> Vardiyalar & Çizelgeleme
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content">
                <!-- 1. DAILY ATTENDANCE TAB -->
                <div class="tab-pane fade <?= $active_tab === 'daily' ? 'show active' : '' ?>" id="tab-daily">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <form method="GET" action="<?= site_url('attendance') ?>" class="d-flex align-items-center gap-2">
                            <input type="hidden" name="tab" value="daily">
                            <label class="fw-semibold text-muted">Tarih:</label>
                            <input type="date" name="date" class="form-control form-control-sm" value="<?= $selected_date ?>" onchange="this.form.submit()">
                        </form>
                        <div class="small text-muted">
                            <span class="badge bg-success me-1">&bull;</span> Zamanında
                            <span class="badge bg-warning text-dark mx-1">&bull;</span> Geç Kaldı
                            <span class="badge bg-info text-dark mx-1">&bull;</span> Yarım Gün
                            <span class="badge bg-danger ms-1">&bull;</span> Devamsız
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>Personel</th>
                                    <th>Vardiya</th>
                                    <th>Giriş Saati</th>
                                    <th>Çıkış Saati</th>
                                    <th>Çalışılan Süre</th>
                                    <th>Geç Kalma</th>
                                    <th>Fazla Mesai</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($daily_attendance)): ?>
                                    <tr><td colspan="8" class="text-center text-muted py-4">Seçilen tarihte henüz giriş/çıkış kaydı bulunmuyor.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($daily_attendance as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= html_escape($row['employee_name']) ?></div>
                                            <small class="text-muted"><?= html_escape($row['department_name'] ?: 'Genel') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= html_escape($row['shift_name'] ?: 'Serbest / Atanmamış') ?></span>
                                        </td>
                                        <td class="fw-semibold text-success">
                                            <?= $row['in_time'] ? date('H:i', strtotime($row['in_time'])) : '—' ?>
                                        </td>
                                        <td class="fw-semibold text-danger">
                                            <?= $row['out_time'] ? date('H:i', strtotime($row['out_time'])) : '<span class="badge bg-primary">Mesaide</span>' ?>
                                        </td>
                                        <td>
                                            <?= round($row['total_worked_minutes'] / 60, 1) ?> Saat
                                        </td>
                                        <td>
                                            <?php if ($row['late_minutes'] > 0): ?>
                                                <span class="badge bg-warning text-dark">+<?= $row['late_minutes'] ?> dk</span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['overtime_minutes'] > 0): ?>
                                                <span class="badge bg-success">+<?= $row['overtime_minutes'] ?> dk</span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] === 'present'): ?>
                                                <span class="badge bg-success">Mevcut</span>
                                            <?php elseif ($row['status'] === 'half_day'): ?>
                                                <span class="badge bg-info text-dark">Yarım Gün</span>
                                            <?php elseif ($row['status'] === 'on_leave'): ?>
                                                <span class="badge bg-secondary">İzinli</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Gelmedi</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. MONTHLY SUMMARY TAB -->
                <div class="tab-pane fade <?= $active_tab === 'monthly' ? 'show active' : '' ?>" id="tab-monthly">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <form method="GET" action="<?= site_url('attendance') ?>" class="d-flex align-items-center gap-2">
                            <input type="hidden" name="tab" value="monthly">
                            <label class="fw-semibold text-muted">Dönem:</label>
                            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $selected_month === $m ? 'selected' : '' ?>><?= sprintf('%02d', $m) ?>. Ay</option>
                                <?php endfor; ?>
                            </select>
                            <input type="number" name="year" class="form-control form-control-sm" value="<?= $selected_year ?>" style="width: 90px;" onchange="this.form.submit()">
                        </form>
                        <a href="<?= site_url('payroll') ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-calculator me-1"></i> Bu Puantajla Bordro Hesapla
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>Personel</th>
                                    <th>Çalışılan Gün</th>
                                    <th>Yarım Gün</th>
                                    <th>İzinli Gün</th>
                                    <th>Devamsız Gün</th>
                                    <th>Toplam Geç Kalma</th>
                                    <th>Toplam Fazla Mesai</th>
                                    <th>Toplam Fiili Çalışma</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($monthly_summary)): ?>
                                    <tr><td colspan="8" class="text-center text-muted py-4">Bu ay için henüz puantaj kaydı bulunmuyor.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($monthly_summary as $sum): ?>
                                    <tr>
                                        <td class="fw-bold"><?= html_escape($sum['employee_name']) ?></td>
                                        <td class="text-success fw-bold"><?= $sum['present_days'] ?> Gün</td>
                                        <td><?= $sum['half_days'] ?></td>
                                        <td class="text-primary"><?= $sum['leave_days'] ?> Gün</td>
                                        <td class="text-danger fw-bold"><?= $sum['absent_days'] ?> Gün</td>
                                        <td><?= $sum['total_late_minutes'] ?> dk</td>
                                        <td class="text-success fw-bold">+<?= round($sum['total_overtime_minutes'] / 60, 1) ?> Saat</td>
                                        <td class="fw-bold"><?= round($sum['total_worked_minutes'] / 60, 1) ?> Saat</td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3. SHIFTS TAB -->
                <div class="tab-pane fade <?= $active_tab === 'shifts' ? 'show active' : '' ?>" id="tab-shifts">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Vardiya Tanımları</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddShift">
                                <i class="fas fa-plus me-1"></i> Yeni Vardiya Ekle
                            </button>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAssignShift">
                                <i class="fas fa-user-clock me-1"></i> Personele Vardiya Ata
                            </button>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <?php foreach ($shifts as $sh): ?>
                        <div class="col-md-4">
                            <div class="card p-3 border rounded-3 shadow-sm" style="border-left: 5px solid <?= $sh['color'] ?> !important;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold mb-0"><?= html_escape($sh['name']) ?></h6>
                                    <span class="badge" style="background-color: <?= $sh['color'] ?>;"><?= html_escape($sh['code']) ?></span>
                                </div>
                                <div class="fs-5 fw-bold text-dark mb-1">
                                    <?= date('H:i', strtotime($sh['start_time'])) ?> - <?= date('H:i', strtotime($sh['end_time'])) ?>
                                </div>
                                <div class="small text-muted">
                                    Mola: <?= $sh['break_duration_minutes'] ?> dk &bull; Tolerans: <?= $sh['late_grace_minutes'] ?> dk
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <h6 class="fw-bold text-muted mb-3">Aktif Vardiya Çizelgesi</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>Personel</th>
                                    <th>Atanan Vardiya</th>
                                    <th>Saatler</th>
                                    <th>Başlangıç Tarihi</th>
                                    <th>Bitiş Tarihi</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $asg): ?>
                                <tr>
                                    <td class="fw-bold"><?= html_escape($asg['employee_name']) ?></td>
                                    <td>
                                        <span class="badge" style="background-color: <?= $asg['color'] ?>;"><?= html_escape($asg['shift_name']) ?></span>
                                    </td>
                                    <td><?= date('H:i', strtotime($asg['start_time'])) ?> - <?= date('H:i', strtotime($asg['end_time'])) ?></td>
                                    <td><?= date('d.m.Y', strtotime($asg['start_date'])) ?></td>
                                    <td><?= !empty($asg['end_date']) ? date('d.m.Y', strtotime($asg['end_date'])) : 'Süresiz' ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-asg" data-id="<?= $asg['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Manual Punch -->
<div class="modal fade" id="modalManualPunch" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formManualPunch">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Manuel Giriş/Çıkış Kaydı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Personel</label>
                        <select class="form-select" name="id_users" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach ($employees as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= html_escape($e['first_name'] . ' ' . $e['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">İşlem Türü</label>
                        <select class="form-select" name="punch_type">
                            <option value="IN">GİRİŞ (Punch IN)</option>
                            <option value="OUT">ÇIKIŞ (Punch OUT)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Shift -->
<div class="modal fade" id="modalAddShift" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAddShift">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Yeni Vardiya Tanımla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Vardiya Adı</label>
                        <input type="text" class="form-control" name="name" required placeholder="örn: Sabah Vardiyası">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Vardiya Kodu</label>
                        <input type="text" class="form-control" name="code" placeholder="örn: SABAH">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Başlangıç Saati</label>
                            <input type="time" class="form-control" name="start_time" required value="09:00">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Bitiş Saati</label>
                            <input type="time" class="form-control" name="end_time" required value="18:00">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Mola (Dakika)</label>
                            <input type="number" class="form-control" name="break_duration_minutes" value="60">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Renk Etiketi</label>
                            <input type="color" class="form-control form-control-color w-100" name="color" value="#10b981">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Assign Shift -->
<div class="modal fade" id="modalAssignShift" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAssignShift">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Personele Vardiya Ata</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Personel</label>
                        <select class="form-select" name="id_users" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach ($employees as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= html_escape($e['first_name'] . ' ' . $e['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Vardiya</label>
                        <select class="form-select" name="id_shifts" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach ($shifts as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= html_escape($s['name']) ?> (<?= substr($s['start_time'], 0, 5) ?> - <?= substr($s['end_time'], 0, 5) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Başlangıç Tarihi</label>
                        <input type="date" class="form-control" name="start_date" required value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Atamayı Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manual Punch
    document.getElementById('formManualPunch').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('<?= site_url("attendance/punch") ?>', { method: 'POST', body: new FormData(e.target) });
        const json = await res.json();
        if (json.success) location.reload();
        else alert(json.message);
    });

    // Add Shift
    document.getElementById('formAddShift').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('<?= site_url("attendance/save_shift") ?>', { method: 'POST', body: new FormData(e.target) });
        const json = await res.json();
        if (json.success) location.reload();
    });

    // Assign Shift
    document.getElementById('formAssignShift').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('<?= site_url("attendance/save_assignment") ?>', { method: 'POST', body: new FormData(e.target) });
        const json = await res.json();
        if (json.success) location.reload();
    });

    // Delete Assignment
    document.querySelectorAll('.btn-delete-asg').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (confirm('Vardiya atamasını silmek istediğinize emin misiniz?')) {
                await fetch('<?= site_url("attendance/delete_assignment/") ?>' + btn.dataset.id, { method: 'POST' });
                location.reload();
            }
        });
    });
});
</script>

<?php end_section('content'); ?>
