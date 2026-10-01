<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container-fluid backend-page py-4 px-md-5" id="ess-page">
    <!-- Employee Greeting Banner -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4 bg-primary text-white p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar bg-white text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3" style="width: 60px; height: 60px;">
                    <?= strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)) ?>
                </div>
                <div>
                    <h3 class="fw-bold mb-1">Hoş Geldiniz, <?= html_escape($employee['first_name'] . ' ' . $employee['last_name']) ?>!</h3>
                    <p class="mb-0 text-white-50">
                        <i class="fas fa-id-badge me-1"></i> <?= html_escape($employee['job_title'] ?: ($employee['designation_title'] ?: 'Personel')) ?>
                        &bull; <i class="fas fa-building me-1"></i> <?= html_escape($employee['department_name'] ?: 'Genel Kadro') ?>
                    </p>
                </div>
            </div>
            <!-- Live Punch Widget -->
            <div class="card bg-white text-dark p-3 rounded-3 shadow-sm border-0" style="min-width: 280px;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold text-muted"><i class="fas fa-clock text-primary me-1"></i> PDKS Canlı Saat</span>
                    <span id="liveClock" class="fw-bold fs-6 text-dark"><?= date('H:i:s') ?></span>
                </div>
                <div class="d-flex gap-2">
                    <button id="btnPunchIn" class="btn btn-success w-50 fw-bold">
                        <i class="fas fa-sign-in-alt me-1"></i> Giriş Yap
                    </button>
                    <button id="btnPunchOut" class="btn btn-danger w-50 fw-bold">
                        <i class="fas fa-sign-out-alt me-1"></i> Çıkış Yap
                    </button>
                </div>
                <div id="punchStatusText" class="small text-center text-muted mt-2">
                    Bugünkü Durum: <strong><?= !empty($today_attendance['in_time']) ? 'Giriş: ' . substr($today_attendance['in_time'], 0, 5) : 'Henüz Giriş Yapılmadı' ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 rounded-3 p-3 bg-white border-start border-success border-4">
                <span class="text-muted small fw-semibold">Kalan Yıllık İzin</span>
                <?php
                $annual = array_filter($leave_allocations, fn($a) => $a['code'] === 'ANNUAL');
                $annual = !empty($annual) ? reset($annual) : null;
                ?>
                <h3 class="fw-bold mb-0 text-success mt-1"><?= $annual ? (float) $annual['remaining_days'] : 14 ?> Gün</h3>
                <small class="text-muted">4857 SK Yıllık İzin</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 rounded-3 p-3 bg-white border-start border-primary border-4">
                <span class="text-muted small fw-semibold">Bu Ayki BooKi Primim</span>
                <?php
                $total_comm = array_sum(array_column($commissions, 'commission_amount'));
                ?>
                <h3 class="fw-bold mb-0 text-primary mt-1">₺<?= number_format($total_comm, 2) ?></h3>
                <small class="text-muted"><?= count($commissions) ?> Hizmet/Ürün Satışı</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 rounded-3 p-3 bg-white border-start border-info border-4">
                <span class="text-muted small fw-semibold">Güncel Vardiyam</span>
                <h4 class="fw-bold mb-0 text-info mt-1">
                    <?= !empty($shifts) ? substr($shifts[0]['start_time'], 0, 5) . ' - ' . substr($shifts[0]['end_time'], 0, 5) : '09:00 - 18:00' ?>
                </h4>
                <small class="text-muted"><?= !empty($shifts) ? html_escape($shifts[0]['shift_name']) : 'Standart Mesai' ?></small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 rounded-3 p-3 bg-white border-start border-warning border-4">
                <span class="text-muted small fw-semibold">Zimmetli Ekipman</span>
                <h3 class="fw-bold mb-0 text-warning mt-1"><?= count($assets) ?></h3>
                <small class="text-muted">Adıma Kayıtlı Varlık</small>
            </div>
        </div>
    </div>

    <!-- Quick Action Bar -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <button class="btn btn-outline-success shadow-sm" data-bs-toggle="modal" data-bs-target="#modalEssLeave">
            <i class="fas fa-calendar-plus me-1"></i> İzin Talep Et
        </button>
        <button class="btn btn-outline-warning text-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#modalEssAdvance">
            <i class="fas fa-hand-holding-usd me-1"></i> Avans Talep Et
        </button>
        <button class="btn btn-outline-info text-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#modalEssExpense">
            <i class="fas fa-receipt me-1"></i> Fiş & Masraf Bildir
        </button>
    </div>

    <!-- Nav Tabs -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs nav-justified border-bottom-0" role="tablist">
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold active" data-bs-toggle="tab" data-bs-target="#tab-my-leaves">
                        <i class="fas fa-clipboard-list me-2"></i> İzin Taleplerim
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-my-commissions">
                        <i class="fas fa-coins me-2"></i> Prim & Hakediş Dökümüm
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-my-slips">
                        <i class="fas fa-file-invoice-dollar me-2"></i> Maaş Bordrolarım
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-my-assets">
                        <i class="fas fa-laptop me-2"></i> Zimmetlerim
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content">
                <!-- 1. MY LEAVES -->
                <div class="tab-pane fade show active" id="tab-my-leaves">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>İzin Türü</th>
                                    <th>Tarih Aralığı</th>
                                    <th>Gün</th>
                                    <th>Açıklama</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leave_applications)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">Henüz kayıtlı bir izin başvurunuz bulunmuyor.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($leave_applications as $app): ?>
                                    <tr>
                                        <td>
                                            <span class="badge" style="background-color: <?= $app['leave_type_color'] ?>;">
                                                <?= html_escape($app['leave_type_name']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d.m.Y', strtotime($app['start_date'])) ?> &mdash; <?= date('d.m.Y', strtotime($app['end_date'])) ?></td>
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
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. MY COMMISSIONS -->
                <div class="tab-pane fade" id="tab-my-commissions">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>Tarih</th>
                                    <th>Adisyon No</th>
                                    <th>İşlem / Ürün</th>
                                    <th>Tutar</th>
                                    <th>Prim Oranı</th>
                                    <th>Kazanılan Prim</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($commissions)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">Bu ay için henüz hak edilmiş prim kaydı bulunmuyor.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($commissions as $comm): ?>
                                    <tr>
                                        <td><?= date('d.m.Y H:i', strtotime($comm['created_at'])) ?></td>
                                        <td>#<?= $comm['id_adisyons'] ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= html_escape($comm['item_name'] ?? 'Hizmet') ?></span></td>
                                        <td>₺<?= number_format($comm['item_price'] ?? 0, 2) ?></td>
                                        <td>%<?= number_format($comm['commission_rate'] ?? 0, 1) ?></td>
                                        <td class="fw-bold text-success">+₺<?= number_format($comm['commission_amount'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3. MY PAYROLL SLIPS -->
                <div class="tab-pane fade" id="tab-my-slips">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>Dönem</th>
                                    <th>Taban Maaş</th>
                                    <th>Primler</th>
                                    <th>Fazla Mesai</th>
                                    <th>Kesintiler (SGK/Vergi/Avans)</th>
                                    <th>Net Ödenen</th>
                                    <th class="text-end">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payroll_slips)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">Henüz yayınlanmış maaş pusulanız bulunmuyor.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($payroll_slips as $slip): ?>
                                    <tr>
                                        <td class="fw-bold"><?= sprintf('%02d/%d', $slip['month'], $slip['year']) ?></td>
                                        <td>₺<?= number_format($slip['base_salary'], 2) ?></td>
                                        <td class="text-success">+₺<?= number_format($slip['commission_amount'], 2) ?></td>
                                        <td class="text-primary">+₺<?= number_format($slip['overtime_amount'], 2) ?></td>
                                        <td class="text-danger">-₺<?= number_format($slip['sgk_deduction'] + $slip['tax_deduction'] + $slip['advance_deduction'], 2) ?></td>
                                        <td class="fs-6 fw-bold text-success">₺<?= number_format($slip['net_pay'], 2) ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-dark" onclick="window.print()">
                                                <i class="fas fa-print me-1"></i> Pusulayı Gör
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 4. MY ASSETS -->
                <div class="tab-pane fade" id="tab-my-assets">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>Varlık Adı</th>
                                    <th>Kod</th>
                                    <th>Kategori</th>
                                    <th>Seri Numarası</th>
                                    <th>Teslim Tarihi</th>
                                    <th>Teslim Notu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($assets)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">Üzerinize zimmetlenmiş aktif varlık bulunmuyor.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($assets as $ast): ?>
                                    <tr>
                                        <td class="fw-bold"><?= html_escape($ast['asset_name']) ?></td>
                                        <td><span class="badge bg-secondary"><?= html_escape($ast['asset_code']) ?></span></td>
                                        <td><?= html_escape($ast['asset_category']) ?></td>
                                        <td><?= html_escape($ast['serial_number'] ?: '—') ?></td>
                                        <td><?= date('d.m.Y', strtotime($ast['assigned_date'])) ?></td>
                                        <td><small class="text-muted"><?= html_escape($ast['condition_on_assignment']) ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: ESS Apply Leave -->
<div class="modal fade" id="modalEssLeave" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEssLeave">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">İzin Başvurusu Yap</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
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
                        <label class="form-label fw-semibold">İzin Nedeni / Açıklama</label>
                        <textarea class="form-control" name="reason" rows="3" required placeholder="Gerekçenizi yazınız..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">Başvuruyu Gönder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: ESS Advance Request -->
<div class="modal fade" id="modalEssAdvance" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEssAdvance">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Maaş Avansı Talep Et</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Talep Edilen Tutar (₺)</label>
                        <input type="number" step="0.01" class="form-control" name="requested_amount" required placeholder="örn: 5000.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Gerekçe</label>
                        <textarea class="form-control" name="reason" rows="3" required placeholder="Avans gerekçenizi yazınız..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold">Talebi İlet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: ESS Expense Submit -->
<div class="modal fade" id="modalEssExpense" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEssExpense" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">İş Masrafı & Fiş Bildir</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori</label>
                        <select class="form-select" name="category">
                            <option value="travel">Yol & Ulaşım / Yakıt</option>
                            <option value="meal">İş Yemeği</option>
                            <option value="supplies">Sarf Malzeme / Kırtasiye</option>
                            <option value="other">Diğer Gider</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tutar (₺)</label>
                        <input type="number" step="0.01" class="form-control" name="amount" required placeholder="örn: 450.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fiş / Fatura Fotoğrafı</label>
                        <input type="file" class="form-control" name="receipt" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Açıklama</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Masraf detayını yazınız..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Gönder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Live Clock
    setInterval(() => {
        const now = new Date();
        document.getElementById('liveClock').innerText = now.toTimeString().split(' ')[0];
    }, 1000);

    // Punch IN / OUT
    async function doPunch(type) {
        let lat = null, lng = null;
        if (navigator.geolocation) {
            try {
                const pos = await new Promise((res, rej) => navigator.geolocation.getCurrentPosition(res, rej, { timeout: 3000 }));
                lat = pos.coords.latitude;
                lng = pos.coords.longitude;
            } catch(e) {}
        }
        const fd = new FormData();
        fd.append('punch_type', type);
        if (lat) fd.append('latitude', lat);
        if (lng) fd.append('longitude', lng);

        const res = await fetch('<?= site_url("ess/punch") ?>', { method: 'POST', body: fd });
        const json = await res.json();
        alert(json.message);
        location.reload();
    }

    document.getElementById('btnPunchIn').addEventListener('click', () => doPunch('IN'));
    document.getElementById('btnPunchOut').addEventListener('click', () => doPunch('OUT'));

    // ESS Leave Submit
    document.getElementById('formEssLeave').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('<?= site_url("ess/apply_leave") ?>', { method: 'POST', body: new FormData(e.target) });
        const json = await res.json();
        alert(json.message);
        if (json.success) location.reload();
    });

    // ESS Advance Submit
    document.getElementById('formEssAdvance').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('<?= site_url("ess/request_advance") ?>', { method: 'POST', body: new FormData(e.target) });
        const json = await res.json();
        alert(json.message);
        if (json.success) location.reload();
    });

    // ESS Expense Submit
    document.getElementById('formEssExpense').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('<?= site_url("ess/submit_expense") ?>', { method: 'POST', body: new FormData(e.target) });
        const json = await res.json();
        alert(json.message);
        if (json.success) location.reload();
    });
});
</script>

<?php end_section('content'); ?>
