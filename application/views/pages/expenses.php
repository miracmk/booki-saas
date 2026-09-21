<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>
<?php
/**
 * @var array $expenses
 * @var array $summary
 * @var string|null $category_filter
 */
?>
<div class="container-fluid py-3" id="expenses-page">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-file-invoice-dollar text-danger me-2"></i>Gider Yönetimi & Harcamalar</h4>
            <p class="text-muted small mb-0">İşletme giderlerini, kira, fatura, personel ve sarf malzemesi harcamalarını kaydedip takip edin.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('finance') ?>" class="btn btn-outline-dark">
                <i class="fas fa-wallet me-1"></i> Finans Merkezine Dön
            </a>
            <button class="btn btn-danger" id="btn-new-expense" data-bs-toggle="modal" data-bs-target="#modal-expense-form">
                <i class="fas fa-plus me-1"></i> Yeni Gider Ekle
            </button>
        </div>
    </div>

    <!-- Category Summary Cards -->
    <div class="row g-3 mb-4">
        <?php foreach ($summary as $s): ?>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body p-3">
                        <span class="text-muted small d-block mb-1 text-truncate"><?= ucfirst(e($s['category'])) ?></span>
                        <h5 class="fw-bold text-dark mb-0"><?= number_format($s['total_amount'], 2) ?> ₺</h5>
                        <small class="text-muted"><?= $s['count'] ?> adet</small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Expenses Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="expenses-table">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th class="ps-3">Tarih</th>
                            <th>Kategori</th>
                            <th>Gider Başlığı</th>
                            <th>Tedarikçi / Firma</th>
                            <th>Ödeme Şekli</th>
                            <th><?= lang('status') ?></th>
                            <th class="text-end">Tutar</th>
                            <th class="text-end pe-3"><?= lang('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">Kayıtlı gider bulunamadı.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expenses as $e): ?>
                                <tr>
                                    <td class="ps-3 small text-muted"><?= date('d.m.Y', strtotime($e['expense_date'])) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= ucfirst(e($e['category'])) ?></span></td>
                                    <td class="fw-semibold"><?= e($e['title']) ?></td>
                                    <td><?= e($e['supplier_name'] ?: '-') ?></td>
                                    <td><span class="badge bg-secondary"><?= strtoupper(e($e['payment_method'])) ?></span></td>
                                    <td>
                                        <?php if ($e['status'] === 'paid'): ?>
                                            <span class="badge bg-success">Ödendi</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Beklemede</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold text-danger">-<?= number_format($e['amount'], 2) ?> ₺</td>
                                    <td class="text-end pe-3">
                                        <button class="btn btn-sm btn-link text-danger p-0" onclick="deleteExpense(<?= $e['id'] ?>)"><i class="fas fa-trash-alt"></i></button>
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

<!-- Modal: New Expense -->
<div class="modal fade" id="modal-expense-form" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-receipt text-danger me-2"></i>Yeni Gider Kaydı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="new-expense-form">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Gider Başlığı / Açıklama *</label>
                        <input type="text" name="title" class="form-control" placeholder="Örn: Eylül Ayı Dükkan Kirası" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Kategori</label>
                            <select name="category" class="form-select">
                                <option value="rent">Kira</option>
                                <option value="utilities">Elektrik / Su / Doğalgaz</option>
                                <option value="supplies">Sarf Malzeme</option>
                                <option value="inventory">Ürün Tedariği</option>
                                <option value="marketing">Pazarlama & Reklam</option>
                                <option value="salaries">Personel / Maaş</option>
                                <option value="software">Yazılım & Abonelik</option>
                                <option value="taxes">Vergi & Muhasebe</option>
                                <option value="maintenance">Bakım & Onarım</option>
                                <option value="other">Diğer</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Tutar (₺) *</label>
                            <input type="number" name="amount" class="form-control" step="0.01" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Tedarikçi / Alıcı Firma</label>
                            <input type="text" name="supplier_name" class="form-control" placeholder="Örn: X Kozmetik A.Ş.">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Gider Tarihi</label>
                            <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Ödeme Yöntemi</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">Nakit (Kasa Çıkışı)</option>
                            <option value="bank_transfer">Banka Havalesi</option>
                            <option value="card">Kredi Kartı</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-danger" onclick="submitExpense()">Gideri Kaydet</button>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script>
function submitExpense() {
    const form = document.getElementById('new-expense-form');
    const fd = new FormData(form);

    fetch('<?= site_url('expenses/save') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert(data.message || 'Gider kaydedilemedi.');
            }
        });
}

function deleteExpense(id) {
    if (!confirm('Bu gider kaydını silmek istediğinize emin misiniz?')) return;
    fetch('<?= site_url('expenses/delete/') ?>' + id, { method: 'POST' })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert(data.message || 'Gider silinemedi.');
            }
        });
}
</script>
<?php end_section('scripts'); ?>
