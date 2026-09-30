<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container-fluid backend-page py-4 px-md-5" id="payroll-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-money-check-alt text-warning text-dark me-2"></i> Bordro, Maaş & Hakediş Yönetimi</h2>
            <p class="text-muted mb-0">Taban maaş, BooKi servis/ürün primleri, fazla mesai, yasal kesintiler ve avans/masraf mahsupları.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-warning text-dark fw-semibold" data-bs-toggle="modal" data-bs-target="#modalGeneratePayroll">
                <i class="fas fa-calculator me-1"></i> Yeni Bordro Dönemi Hesapla
            </button>
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAddStructure">
                <i class="fas fa-cog me-1"></i> Maaş Yapısı Tanımla
            </button>
        </div>
    </div>

    <!-- Nav Tabs -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs nav-justified border-bottom-0" role="tablist">
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold <?= $active_tab === 'payrolls' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-payrolls">
                        <i class="fas fa-file-invoice-dollar me-2"></i> Bordro Pusulaları (Salary Slips)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold <?= $active_tab === 'advances' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-advances">
                        <i class="fas fa-hand-holding-usd me-2"></i> Personel Avans Talepleri
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold <?= $active_tab === 'expenses' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-expenses">
                        <i class="fas fa-receipt me-2"></i> Masraf & Harcama Bildirimleri
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content">
                <!-- 1. PAYROLLS & SLIPS TAB -->
                <div class="tab-pane fade <?= $active_tab === 'payrolls' ? 'show active' : '' ?>" id="tab-payrolls">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <form method="GET" action="<?= site_url('payroll') ?>" class="d-flex align-items-center gap-2">
                            <input type="hidden" name="tab" value="payrolls">
                            <label class="fw-semibold text-muted">Dönem Seçimi:</label>
                            <select name="payroll_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <?php foreach ($payrolls as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $selected_payroll_id === (int) $p['id'] ? 'selected' : '' ?>>
                                        <?= sprintf('%02d/%d', $p['month'], $p['year']) ?> - (Brüt: ₺<?= number_format($p['total_gross'], 2) ?> | Net: ₺<?= number_format($p['total_net'], 2) ?>) - [<?= strtoupper($p['status']) ?>]
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>Personel</th>
                                    <th>Departman & Unvan</th>
                                    <th>Taban Maaş</th>
                                    <th>BooKi Prim / Komisyon</th>
                                    <th>Fazla Mesai</th>
                                    <th>Brüt Hakediş</th>
                                    <th>SGK & Vergi</th>
                                    <th>Avans Mahsubu</th>
                                    <th>Ödenecek Net Maaş</th>
                                    <th class="text-end">Pusula</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($slips)): ?>
                                    <tr><td colspan="10" class="text-center text-muted py-4">Bu dönem için hesaplanmış bordro fişi bulunmuyor. Üst kısımdan "Yeni Bordro Dönemi Hesapla" butonunu kullanabilirsiniz.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($slips as $slip): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= html_escape($slip['employee_name']) ?></div>
                                            <small class="text-muted"><?= html_escape($slip['iban'] ?: 'IBAN Tanımsız') ?></small>
                                        </td>
                                        <td>
                                            <div><?= html_escape($slip['department_name'] ?: 'Genel') ?></div>
                                            <small class="text-muted"><?= html_escape($slip['job_title'] ?: 'Uzman') ?></small>
                                        </td>
                                        <td>₺<?= number_format($slip['base_salary'], 2) ?></td>
                                        <td class="text-success fw-bold">+₺<?= number_format($slip['commission_amount'], 2) ?></td>
                                        <td class="text-primary">+₺<?= number_format($slip['overtime_amount'], 2) ?></td>
                                        <td class="fw-bold">₺<?= number_format($slip['gross_pay'], 2) ?></td>
                                        <td class="text-danger">-₺<?= number_format($slip['sgk_deduction'] + $slip['tax_deduction'], 2) ?></td>
                                        <td class="text-danger">-₺<?= number_format($slip['advance_deduction'], 2) ?></td>
                                        <td class="fs-6 fw-bold text-success">₺<?= number_format($slip['net_pay'], 2) ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-dark" onclick="window.print()">
                                                <i class="fas fa-print me-1"></i> Yazdır
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. ADVANCES TAB -->
                <div class="tab-pane fade <?= $active_tab === 'advances' ? 'show active' : '' ?>" id="tab-advances">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Personel Avans & Borç Talepleri</h5>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>Personel</th>
                                    <th>Talep Tarihi</th>
                                    <th>Tutar</th>
                                    <th>Gerekçe</th>
                                    <th>Durum</th>
                                    <th>Onaylayan</th>
                                    <th class="text-end">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($advances)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">Kayıtlı avans talebi bulunmuyor.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($advances as $adv): ?>
                                    <tr>
                                        <td class="fw-bold"><?= html_escape($adv['employee_name']) ?></td>
                                        <td><?= date('d.m.Y H:i', strtotime($adv['created_at'])) ?></td>
                                        <td class="fs-6 fw-bold text-dark">₺<?= number_format($adv['requested_amount'], 2) ?></td>
                                        <td><small class="text-muted"><?= html_escape($adv['reason'] ?: '—') ?></small></td>
                                        <td>
                                            <?php if ($adv['status'] === 'pending'): ?>
                                                <span class="badge bg-warning text-dark">Onay Bekliyor</span>
                                            <?php elseif ($adv['status'] === 'approved'): ?>
                                                <span class="badge bg-success">Onaylandı &bull; Bordrodan Düşülecek</span>
                                            <?php elseif ($adv['status'] === 'rejected'): ?>
                                                <span class="badge bg-danger">Reddedildi</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= html_escape($adv['approver_name'] ?: '—') ?></td>
                                        <td class="text-end">
                                            <?php if ($adv['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-success btn-update-adv" data-id="<?= $adv['id'] ?>" data-status="approved">
                                                    <i class="fas fa-check"></i> Onayla
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger btn-update-adv" data-id="<?= $adv['id'] ?>" data-status="rejected">
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

                <!-- 3. EXPENSES TAB -->
                <div class="tab-pane fade <?= $active_tab === 'expenses' ? 'show active' : '' ?>" id="tab-expenses">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Personel Masraf & Harcama Bildirimleri</h5>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>Personel</th>
                                    <th>Masraf Tarihi</th>
                                    <th>Kategori</th>
                                    <th>Tutar</th>
                                    <th>Fiş / Fatura</th>
                                    <th>Durum</th>
                                    <th class="text-end">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($expenses)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">Kayıtlı masraf bildirimi bulunmuyor.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($expenses as $exp): ?>
                                    <tr>
                                        <td class="fw-bold"><?= html_escape($exp['employee_name']) ?></td>
                                        <td><?= date('d.m.Y', strtotime($exp['claim_date'])) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= html_escape($exp['category']) ?></span></td>
                                        <td class="fs-6 fw-bold text-dark">₺<?= number_format($exp['amount'], 2) ?></td>
                                        <td>
                                            <?php if (!empty($exp['receipt_file_path'])): ?>
                                                <a href="<?= base_url($exp['receipt_file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-receipt me-1"></i> Fişi Gör
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($exp['status'] === 'pending'): ?>
                                                <span class="badge bg-warning text-dark">Onay Bekliyor</span>
                                            <?php elseif ($exp['status'] === 'approved'): ?>
                                                <span class="badge bg-success">Onaylandı</span>
                                            <?php elseif ($exp['status'] === 'reimbursed'): ?>
                                                <span class="badge bg-info text-dark">Geri Ödendi</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Reddedildi</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($exp['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-success btn-update-exp" data-id="<?= $exp['id'] ?>" data-status="approved">
                                                    <i class="fas fa-check"></i> Onayla
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger btn-update-exp" data-id="<?= $exp['id'] ?>" data-status="rejected">
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
            </div>
        </div>
    </div>
</div>

<!-- Modal: Generate Payroll -->
<div class="modal fade" id="modalGeneratePayroll" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formGenPayroll">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Aylık Bordro Hesapla & Oluştur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Ay</label>
                            <select name="month" class="form-select">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= (int) date('m') === $m ? 'selected' : '' ?>><?= sprintf('%02d', $m) ?>. Ay</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Yıl</label>
                            <input type="number" name="year" class="form-control" value="<?= date('Y') ?>">
                        </div>
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="fas fa-info-circle me-1"></i> Bu işlem, seçilen dönemdeki tüm personellerin taban maaşlarını, BooKi randevu ve adisyon primlerini, puantajdan gelen fazla mesaileri ve avans mahsuplarını otomatik toplayarak bordro fişlerini oluşturacaktır.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold">Hesaplamayı Başlat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Structure -->
<div class="modal fade" id="modalAddStructure" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formSaveStructure">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Personel Maaş Yapısı Tanımla</h5>
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
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label fw-semibold">Taban Aylık Net / Brüt Maaş</label>
                            <input type="number" step="0.01" class="form-control" name="base_salary" required placeholder="örn: 35000.00">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">Para Birimi</label>
                            <select class="form-select" name="currency">
                                <option value="TRY">TRY (₺)</option>
                                <option value="USD">USD ($)</option>
                                <option value="EUR">EUR (€)</option>
                            </select>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Generate Payroll
    document.getElementById('formGenPayroll').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('<?= site_url("payroll/generate") ?>', { method: 'POST', body: new FormData(e.target) });
        const json = await res.json();
        if (json.success) {
            alert(json.message);
            location.reload();
        } else {
            alert(json.message);
        }
    });

    // Save Structure
    document.getElementById('formSaveStructure').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('<?= site_url("payroll/save_structure") ?>', { method: 'POST', body: new FormData(e.target) });
        const json = await res.json();
        if (json.success) location.reload();
        else alert(json.message);
    });

    // Update Advance
    document.querySelectorAll('.btn-update-adv').forEach(btn => {
        btn.addEventListener('click', async () => {
            const fd = new FormData();
            fd.append('id', btn.dataset.id);
            fd.append('status', btn.dataset.status);
            await fetch('<?= site_url("payroll/update_advance_status") ?>', { method: 'POST', body: fd });
            location.reload();
        });
    });

    // Update Expense
    document.querySelectorAll('.btn-update-exp').forEach(btn => {
        btn.addEventListener('click', async () => {
            const fd = new FormData();
            fd.append('id', btn.dataset.id);
            fd.append('status', btn.dataset.status);
            await fetch('<?= site_url("payroll/update_expense_status") ?>', { method: 'POST', body: fd });
            location.reload();
        });
    });
});
</script>

<?php end_section('content'); ?>
