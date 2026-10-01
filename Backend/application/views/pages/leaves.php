<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container-fluid backend-page py-4 px-md-5" id="leaves-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-calendar-check text-success me-2"></i> İzin & Tatil Yönetimi (4857 SK)</h2>
            <p class="text-muted mb-0">Yıllık ücretli izin hak edişleri, mazeret/sağlık izinleri, onay akışları ve resmi tatiller.</p>
        </div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalApplyLeave">
            <i class="fas fa-plus me-1"></i> Yeni İzin Girişi Yap
        </button>
    </div>

    <!-- Nav Tabs -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs nav-justified border-bottom-0" role="tablist">
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold <?= $active_tab === 'applications' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-applications">
                        <i class="fas fa-clipboard-list me-2"></i> İzin Başvuruları & Onaylar
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold <?= $active_tab === 'allocations' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-allocations">
                        <i class="fas fa-user-clock me-2"></i> İzin Hak Edişleri & Kıdem (4857 SK)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold <?= $active_tab === 'holidays' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-holidays">
                        <i class="fas fa-umbrella-beach me-2"></i> Resmi Tatil Takvimi
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content">
                <!-- 1. APPLICATIONS TAB -->
                <div class="tab-pane fade <?= $active_tab === 'applications' ? 'show active' : '' ?>" id="tab-applications">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <form method="GET" action="<?= site_url('leaves') ?>" class="d-flex align-items-center gap-2">
                            <input type="hidden" name="tab" value="applications">
                            <label class="fw-semibold text-muted">Durum:</label>
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Tümü</option>
                                <option value="pending" <?= $selected_status === 'pending' ? 'selected' : '' ?>>Beklemede</option>
                                <option value="approved" <?= $selected_status === 'approved' ? 'selected' : '' ?>>Onaylandı</option>
                                <option value="rejected" <?= $selected_status === 'rejected' ? 'selected' : '' ?>>Reddedildi</option>
                            </select>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>Personel</th>
                                    <th>İzin Türü</th>
                                    <th>Tarih Aralığı</th>
                                    <th>Gün</th>
                                    <th>Gerekçe / Açıklama</th>
                                    <th>Durum</th>
                                    <th>Onaylayan</th>
                                    <th class="text-end">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($applications)): ?>
                                    <tr><td colspan="8" class="text-center text-muted py-4">Kayıtlı izin talebi bulunmuyor.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($applications as $app): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= html_escape($app['employee_name']) ?></div>
                                            <small class="text-muted"><?= html_escape($app['department_name'] ?: 'Genel') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge" style="background-color: <?= $app['leave_type_color'] ?>;">
                                                <?= html_escape($app['leave_type_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= date('d.m.Y', strtotime($app['start_date'])) ?> &mdash; <?= date('d.m.Y', strtotime($app['end_date'])) ?>
                                        </td>
                                        <td class="fw-bold"><?= (float) $app['total_days'] ?> Gün</td>
                                        <td><small class="text-muted"><?= html_escape($app['reason'] ?: '—') ?></small></td>
                                        <td>
                                            <?php if ($app['status'] === 'pending'): ?>
                                                <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i> Onay Bekliyor</span>
                                            <?php elseif ($app['status'] === 'approved'): ?>
                                                <span class="badge bg-success"><i class="fas fa-check me-1"></i> Onaylandı</span>
                                            <?php elseif ($app['status'] === 'rejected'): ?>
                                                <span class="badge bg-danger"><i class="fas fa-times me-1"></i> Reddedildi</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= html_escape($app['approver_name'] ?: '—') ?></td>
                                        <td class="text-end">
                                            <?php if ($app['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-success btn-update-leave" data-id="<?= $app['id'] ?>" data-status="approved">
                                                    <i class="fas fa-check"></i> Onayla
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger btn-update-leave" data-id="<?= $app['id'] ?>" data-status="rejected">
                                                    <i class="fas fa-times"></i> Reddet
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

                <!-- 2. ALLOCATIONS TAB -->
                <div class="tab-pane fade <?= $active_tab === 'allocations' ? 'show active' : '' ?>" id="tab-allocations">
                    <h5 class="fw-bold mb-3">4857 Sayılı İş Kanunu Standart Kıdem Cetveli</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card p-3 border rounded-3 bg-light">
                                <h6 class="fw-bold text-dark mb-1">1 - 5 Yıl Kıdem</h6>
                                <div class="fs-4 fw-bold text-primary">14 İş Günü</div>
                                <small class="text-muted">Kanuni asgari yıllık ücretli izin hakkı.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-3 border rounded-3 bg-light">
                                <h6 class="fw-bold text-dark mb-1">5 - 15 Yıl Kıdem</h6>
                                <div class="fs-4 fw-bold text-success">20 İş Günü</div>
                                <small class="text-muted">5 yıldan fazla, 15 yıldan az kıdem.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-3 border rounded-3 bg-light">
                                <h6 class="fw-bold text-dark mb-1">15 Yıl ve Üzeri Kıdem</h6>
                                <div class="fs-4 fw-bold text-warning text-dark">26 İş Günü</div>
                                <small class="text-muted">Kıdemli personel ve 50 yaş üstü hak edişi.</small>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-muted mb-3">Kadro İzin Bakiyeleri (<?= date('Y') ?>)</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>Personel</th>
                                    <th>İşe Giriş</th>
                                    <th>Yıllık Hak Ediş</th>
                                    <th>Kullanılan</th>
                                    <th>Kalan Bakiye</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $emp): ?>
                                <?php
                                $allocs = $this->hr_model->get_leave_allocations((int) $emp['id'], (int) date('Y'));
                                $annual = array_filter($allocs, fn($a) => $a['code'] === 'ANNUAL');
                                $annual = !empty($annual) ? reset($annual) : null;
                                ?>
                                <tr>
                                    <td class="fw-bold"><?= html_escape($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
                                    <td><?= !empty($emp['date_of_joining']) ? date('d.m.Y', strtotime($emp['date_of_joining'])) : '—' ?></td>
                                    <td><?= $annual ? (float) $annual['allocated_days'] : 14 ?> Gün</td>
                                    <td class="text-danger fw-bold"><?= $annual ? (float) $annual['used_days'] : 0 ?> Gün</td>
                                    <td class="text-success fw-bold"><?= $annual ? (float) $annual['remaining_days'] : 14 ?> Gün</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3. HOLIDAYS TAB -->
                <div class="tab-pane fade <?= $active_tab === 'holidays' ? 'show active' : '' ?>" id="tab-holidays">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Resmi ve İdari Tatil Takvimi</h5>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddHoliday">
                            <i class="fas fa-plus me-1"></i> Tatil Ekle
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>Tatil Adı</th>
                                    <th>Başlangıç Tarihi</th>
                                    <th>Bitiş Tarihi</th>
                                    <th>Tekrar Durumu</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($holidays as $h): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><i class="fas fa-calendar-day text-danger me-2"></i> <?= html_escape($h['name']) ?></td>
                                    <td><?= date('d.m.Y', strtotime($h['start_date'])) ?></td>
                                    <td><?= date('d.m.Y', strtotime($h['end_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= $h['is_recurring'] ? 'Her Yıl Tekrarlanır' : 'Tek Seferlik' ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-hol" data-id="<?= $h['id'] ?>">
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

<!-- Modal: Apply Leave -->
<div class="modal fade" id="modalApplyLeave" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formApplyLeave">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Yeni İzin Girişi Yap</h5>
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
                        <label class="form-label fw-semibold">İzin Türü</label>
                        <select class="form-select" name="id_leave_types" required>
                            <?php foreach ($leave_types as $lt): ?>
                                <option value="<?= $lt['id'] ?>"><?= html_escape($lt['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" name="start_date" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Bitiş Tarihi</label>
                            <input type="date" class="form-control" name="end_date" required value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Gerekçe / Açıklama</label>
                        <textarea class="form-control" name="reason" rows="2" placeholder="İzin gerekçesi..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Holiday -->
<div class="modal fade" id="modalAddHoliday" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAddHoliday">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Yeni Tatil Günü Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tatil Adı</label>
                        <input type="text" class="form-control" name="name" required placeholder="örn: Kurban Bayramı 1. Gün">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Başlangıç</label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Bitiş</label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_recurring" value="1" id="chkRec">
                        <label class="form-check-label" for="chkRec">Her yıl aynı tarihte tekrarlansın</label>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Apply Leave
    document.getElementById('formApplyLeave').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('<?= site_url("leaves/apply") ?>', { method: 'POST', body: new FormData(e.target) });
        const json = await res.json();
        if (json.success) location.reload();
        else alert(json.message);
    });

    // Update Leave Status (Approve / Reject)
    document.querySelectorAll('.btn-update-leave').forEach(btn => {
        btn.addEventListener('click', async () => {
            const status = btn.dataset.status;
            let reason = '';
            if (status === 'rejected') {
                reason = prompt('Reddetme gerekçesi giriniz:');
                if (reason === null) return;
            }
            const fd = new FormData();
            fd.append('id', btn.dataset.id);
            fd.append('status', status);
            fd.append('rejection_reason', reason);

            const res = await fetch('<?= site_url("leaves/update_status") ?>', { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success) location.reload();
        });
    });

    // Add Holiday
    document.getElementById('formAddHoliday').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('<?= site_url("leaves/save_holiday") ?>', { method: 'POST', body: new FormData(e.target) });
        const json = await res.json();
        if (json.success) location.reload();
    });

    // Delete Holiday
    document.querySelectorAll('.btn-delete-hol').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (confirm('Bu tatili silmek istediğinize emin misiniz?')) {
                await fetch('<?= site_url("leaves/delete_holiday/") ?>' + btn.dataset.id, { method: 'POST' });
                location.reload();
            }
        });
    });
});
</script>

<?php end_section('content'); ?>
