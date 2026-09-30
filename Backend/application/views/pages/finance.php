<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>
<?php
/**
 * @var float $today_collections
 * @var float $month_collections
 * @var float $unpaid_receivables
 * @var float $month_expenses
 * @var float $net_cashflow
 * @var array $active_register
 * @var array $bank_accounts
 * @var array $recent_payments
 * @var array $expenses
 * @var array $expense_categories
 * @var array $staff_commissions
 */
?>
<div class="container-fluid py-3" id="finance-dashboard-page">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-wallet text-primary me-2"></i>Finans Merkezi & Kasa Yönetimi</h4>
            <p class="text-muted small mb-0">Tahsilatlar, açık alacaklar, giderler, kasa gün sonu kapanışı ve personel hakedişlerini tek ekrandan yönetin.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('expenses') ?>" class="btn btn-outline-danger">
                <i class="fas fa-receipt me-1"></i> Gider Ekle
            </a>
            <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#close-register-modal">
                <i class="fas fa-lock me-1"></i> Gün Sonu / Kasa Kapat
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#payout-modal">
                <i class="fas fa-hand-holding-usd me-1"></i> Personel Ödemesi Yap
            </button>
        </div>
    </div>

    <!-- Executive KPI Row -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3" id="kpi-total-income">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <span class="text-muted small d-block mb-1">Bu Ayki Toplam Tahsilat</span>
                    <h3 class="fw-bold text-success mb-0"><?= number_format($month_collections, 2) ?> ₺</h3>
                    <small class="text-muted">Bugün: <?= number_format($today_collections, 2) ?> ₺</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <span class="text-muted small d-block mb-1">Açık / Bekleyen Alacaklar</span>
                    <h3 class="fw-bold text-danger mb-0"><?= number_format($unpaid_receivables, 2) ?> ₺</h3>
                    <small class="text-muted">Ödenmemiş adisyon ve faturalar</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3" id="kpi-total-expense">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <span class="text-muted small d-block mb-1">Bu Ayki Giderler</span>
                    <h3 class="fw-bold text-warning text-dark mb-0"><?= number_format($month_expenses, 2) ?> ₺</h3>
                    <small class="text-muted">Kira, maaş, sarf ve faturalar</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3" id="kpi-net-cash">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <span class="text-muted small d-block mb-1">Net Nakit Akışı (Bu Ay)</span>
                    <h3 class="fw-bold <?= $net_cashflow >= 0 ? 'text-primary' : 'text-danger' ?> mb-0"><?= number_format($net_cashflow, 2) ?> ₺</h3>
                    <small class="text-muted">Tahsilat - Gider</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Cash Register Box & Bank Accounts -->
    <div class="row g-3 mb-4">
        <!-- Cash Register Card -->
        <div class="col-12 col-lg-6" id="cash-registers-table">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-cash-register text-primary me-2"></i>Aktif Kasa (<?= e($active_register['register_name']) ?>)</h6>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1">Açık</span>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <span class="text-muted small d-block">Mevcut Kasa Bakiyesi</span>
                            <h2 class="fw-bold text-dark mb-0"><?= number_format($active_register['current_balance'], 2) ?> ₺</h2>
                        </div>
                        <div class="text-end">
                            <span class="text-muted small d-block">Açılış Bakiyesi</span>
                            <span class="fw-semibold text-secondary"><?= number_format($active_register['opening_balance'], 2) ?> ₺</span>
                        </div>
                    </div>
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="p-2 bg-light rounded-2">
                                <span class="text-muted small d-block">Toplam Nakit Girişi</span>
                                <span class="fw-bold text-success">+<?= number_format($active_register['total_cash_in'], 2) ?> ₺</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 bg-light rounded-2">
                                <span class="text-muted small d-block">Toplam Nakit Çıkışı</span>
                                <span class="fw-bold text-danger">-<?= number_format($active_register['total_cash_out'], 2) ?> ₺</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bank Accounts Card -->
        <div class="col-12 col-lg-6" id="bank-accounts-table">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-university text-primary me-2"></i>Banka Hesapları & POS Terminalleri</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="openBankAccountModal()">
                        <i class="fas fa-plus me-1"></i> Hesap / POS Bağla
                    </button>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($bank_accounts)): ?>
                        <div class="text-center py-4 text-muted small">
                            <i class="fas fa-wallet fa-2x mb-2 text-muted opacity-50 d-block"></i>
                            Henüz tanımlı banka hesabı veya POS terminali bulunmuyor.
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($bank_accounts as $b): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-2 py-3">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h6 class="mb-0 fw-bold text-dark"><?= e($b['bank_name']) ?> - <?= e($b['account_name']) ?></h6>
                                            <?php if (!empty($b['is_default_iban'])): ?>
                                                <span class="badge bg-primary bg-opacity-10 text-primary small border border-primary border-opacity-25" title="Müşteri Havale / EFT ödemeleri bu hesaba yönlendirilir">
                                                    <i class="fas fa-exchange-alt me-1"></i>Havale/EFT
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($b['is_default_pos'])): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success small border border-success border-opacity-25" title="Fiziki POS çekimleri bu hesaba aktarılır">
                                                    <i class="fas fa-credit-card me-1"></i>POS Hesabı
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($b['is_default_payout'])): ?>
                                                <span class="badge bg-warning bg-opacity-10 text-dark small border border-warning border-opacity-25" title="BooKi Online Kapora & Tahsilat Hakedişleri bu hesaba aktarılır">
                                                    <i class="fas fa-hand-holding-usd me-1"></i>BooKi Hakediş
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small text-muted font-monospace">
                                            <?php if (!empty($b['iban'])): ?>
                                                <i class="fas fa-hashtag me-1"></i><?= e($b['iban']) ?>
                                            <?php endif; ?>
                                            <?php if (!empty($b['pos_terminal_id'])): ?>
                                                <span class="ms-2"><i class="fas fa-cash-register me-1"></i>Terminal: <?= e($b['pos_terminal_id']) ?> (<?= e($b['pos_provider'] ?: 'ÖKC') ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-end d-flex align-items-center gap-2">
                                        <span class="fw-bold text-primary fs-6"><?= number_format($b['balance'], 2) ?> <?= e($b['currency'] ?? 'TRY') ?></span>
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2" onclick="deleteBankAccount(<?= $b['id'] ?>)" title="Hesabı Kaldır">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payments & Expenses Tabs -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs m-0 px-3" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active py-3 fw-bold" data-bs-toggle="tab" data-bs-target="#tab-payments">
                        <i class="fas fa-money-check-alt me-1 text-success"></i> Son Tahsilatlar (<?= count($recent_payments) ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-bold" data-bs-toggle="tab" data-bs-target="#tab-expenses">
                        <i class="fas fa-receipt me-1 text-danger"></i> Son Giderler (<?= count($expenses) ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-bold" data-bs-toggle="tab" data-bs-target="#tab-commissions">
                        <i class="fas fa-percentage me-1 text-primary"></i> Personel Prim / Hakedişleri
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-0 tab-content">
            <!-- Payments Tab -->
            <div class="tab-pane fade show active" id="tab-payments">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th class="ps-3">Tarih</th>
                                <th>Adisyon No</th>
                                <th><?= lang('customer') ?></th>
                                <th>Ödeme Yöntemi</th>
                                <th class="text-end pe-3">Tutar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_payments as $p): ?>
                                <tr>
                                    <td class="ps-3 text-muted small"><?= date('d.m.Y H:i', strtotime($p['created_at'])) ?></td>
                                    <td class="fw-semibold text-primary">#<?= e($p['adisyon_number'] ?: $p['id_adisyons']) ?></td>
                                    <td><?= e($p['customer_first_name'] . ' ' . $p['customer_last_name']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= strtoupper(e($p['payment_method'])) ?></span></td>
                                    <td class="text-end pe-3 fw-bold text-success">+<?= number_format($p['amount'], 2) ?> ₺</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Expenses Tab -->
            <div class="tab-pane fade" id="tab-expenses">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th class="ps-3">Tarih</th>
                                <th>Kategori</th>
                                <th>Gider Başlığı / Tedarikçi</th>
                                <th>Yöntem</th>
                                <th class="text-end pe-3">Tutar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expenses as $ex): ?>
                                <tr>
                                    <td class="ps-3 text-muted small"><?= date('d.m.Y', strtotime($ex['expense_date'])) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= ucfirst(e($ex['category'])) ?></span></td>
                                    <td class="fw-semibold"><?= e($ex['title']) ?> <?= $ex['supplier_name'] ? '<small class="text-muted">(' . e($ex['supplier_name']) . ')</small>' : '' ?></td>
                                    <td><span class="badge bg-secondary"><?= strtoupper(e($ex['payment_method'])) ?></span></td>
                                    <td class="text-end pe-3 fw-bold text-danger">-<?= number_format($ex['amount'], 2) ?> ₺</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Commissions Tab -->
            <div class="tab-pane fade" id="tab-commissions">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th class="ps-3"><?= lang('provider') ?></th>
                                <th>Satış Adedi</th>
                                <th>Toplam Satış</th>
                                <th>Hakedilen Prim</th>
                                <th>Bahşiş</th>
                                <th class="text-end pe-3"><?= lang('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff_commissions as $sc): ?>
                                <tr>
                                    <td class="ps-3 fw-semibold"><?= e($sc['first_name'] . ' ' . $sc['last_name']) ?></td>
                                    <td><?= $sc['sales_count'] ?> adet</td>
                                    <td class="fw-bold"><?= number_format($sc['total_sales'], 2) ?> ₺</td>
                                    <td class="text-primary fw-bold"><?= number_format($sc['total_commission'], 2) ?> ₺</td>
                                    <td class="text-success fw-bold"><?= number_format($sc['total_tips'], 2) ?> ₺</td>
                                    <td class="text-end pe-3">
                                        <button class="btn btn-sm btn-outline-primary" onclick="openPayoutModal(<?= $sc['id_users_staff'] ?>, '<?= e($sc['first_name'] . ' ' . $sc['last_name']) ?>')">
                                            Ödeme Hazırla
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

<!-- Modal: Close Register / Gün Sonu -->
<div class="modal fade" id="close-register-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-lock text-dark me-2"></i>Kasa Gün Sonu Kapanışı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Kasada sayılan fiziki nakit tutarını girin. Sistem beklenen bakiye ile karşılaştırarak mutabakat oluşturacaktır.</p>
                <input type="hidden" id="modal-register-id" value="<?= $active_register['id'] ?>">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Kasada Sayılan Gerçek Nakit (₺) *</label>
                    <input type="number" id="modal-actual-cash" class="form-control form-control-lg fw-bold fs-3 text-primary" step="0.01" value="<?= $active_register['current_balance'] ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Kapanış Notu / Açıklama</label>
                    <textarea id="modal-closing-notes" class="form-control" rows="2" placeholder="Örn: Gün sonu sayımı tam, 100 ₺ bozuk para bırakıldı."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-dark" onclick="submitCloseRegister()">Kapanışı Onayla ve Kapat</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Staff Payout -->
<div class="modal fade" id="payout-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-hand-holding-usd text-primary me-2"></i>Personel Maaş & Prim Ödemesi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="payout-form">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Personel Seçin</label>
                        <select name="id_users_staff" id="payout-staff-select" class="form-select" required>
                            <?php foreach ($staff_commissions as $sc): ?>
                                <option value="<?= $sc['id_users_staff'] ?>"><?= e($sc['first_name'] . ' ' . $sc['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Dönem Başlangıç</label>
                            <input type="date" name="period_start" class="form-control" value="<?= date('Y-m-01') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Dönem Bitiş</label>
                            <input type="date" name="period_end" class="form-control" value="<?= date('Y-m-t') ?>" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Taban Maaş (₺)</label>
                            <input type="number" name="base_salary" class="form-control" value="0.00" step="0.01">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Avans Kesintisi (₺)</label>
                            <input type="number" name="advances" class="form-control" value="0.00" step="0.01">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="submitPayout()">Ödemeyi Tamamla</button>
            </div>
        </div>
    </div>
<!-- Modal: Bank Account & POS Terminal -->
<div class="modal fade" id="bank-account-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark"><i class="fas fa-university text-primary me-2"></i>Banka Hesabı & POS Terminali Bağla</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="bank-account-form">
                    <input type="hidden" name="id" id="bank-account-id" value="">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Hesap / Entegrasyon Türü</label>
                        <select name="account_type" id="bank-account-type" class="form-select rounded-3" onchange="togglePosFields(this.value)">
                            <option value="bank">Banka Vadesiz Hesabı (IBAN / Havale)</option>
                            <option value="pos">Fiziki POS Terminali / ÖKC</option>
                            <option value="payout">BooKi Tahsilat & Kapora Hakediş Hesabı</option>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Banka / Kurum Adı</label>
                            <input type="text" name="bank_name" id="bank-name" class="form-control rounded-3" placeholder="Örn: Garanti BBVA" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Hesap / Cihaz Tanımı</label>
                            <input type="text" name="account_name" id="account-name" class="form-control rounded-3" placeholder="Örn: Ana Ticari / Kasa POS" required>
                        </div>
                    </div>

                    <div class="mb-3" id="group-iban">
                        <label class="form-label small fw-semibold text-muted">IBAN Numarası</label>
                        <input type="text" name="iban" id="bank-iban" class="form-control rounded-3 font-monospace" placeholder="TR00 0000 0000 0000 0000 0000 00">
                    </div>

                    <div class="row g-3 mb-3" id="group-pos" style="display: none;">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">POS Terminal / ÖKC Seri No</label>
                            <input type="text" name="pos_terminal_id" id="pos-terminal-id" class="form-control rounded-3 font-monospace" placeholder="Örn: TR88291039">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">POS Sağlayıcı / Marka</label>
                            <input type="text" name="pos_provider" id="pos-provider" class="form-control rounded-3" placeholder="Örn: Ingenico ÖKC / Garanti">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Varsayılan Kullanım Alanları</label>
                        <div class="card bg-light border-0 p-3 rounded-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_default_iban" id="check-def-iban" value="1">
                                <label class="form-check-label small fw-semibold" for="check-def-iban">Havale / EFT Ödemelerinde Müşteriye Sunulacak Ana Hesap</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_default_pos" id="check-def-pos" value="1">
                                <label class="form-check-label small fw-semibold" for="check-def-pos">Kredi Kartı / Fiziki POS Tahsilatlarının Bağlandığı Hesap</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_default_payout" id="check-def-payout" value="1">
                                <label class="form-check-label small fw-semibold" for="check-def-payout">BooKi Online Ödeme & Kapora Hakedişlerinin Aktarılacağı Hesap</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top py-3">
                <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary rounded-3 px-4" onclick="submitBankAccount()">
                    <i class="fas fa-save me-1"></i> Kaydet & Bağla
                </button>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script>
let bankAccountModal = null;
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('bank-account-modal');
    if (el) bankAccountModal = new bootstrap.Modal(el);
});

function openBankAccountModal() {
    document.getElementById('bank-account-form').reset();
    document.getElementById('bank-account-id').value = '';
    togglePosFields('bank');
    if (bankAccountModal) bankAccountModal.show();
}

function togglePosFields(type) {
    const posGroup = document.getElementById('group-pos');
    if (posGroup) {
        posGroup.style.display = (type === 'pos') ? 'flex' : 'none';
    }
}

function submitBankAccount() {
    const form = document.getElementById('bank-account-form');
    const fd = new FormData(form);

    fetch('<?= site_url('finance/save_bank_account') ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Hesap başarıyla kaydedildi.');
                window.location.reload();
            } else {
                alert(data.message || 'Kayıt başarısız.');
            }
        })
        .catch(err => alert('İşlem başarısız: ' + err.message));
}

function deleteBankAccount(id) {
    if (!confirm('Bu hesabı silmek istediğinize emin misiniz?')) return;
    fetch('<?= site_url('finance/delete_bank_account/') ?>' + id)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Hesap silindi.');
                window.location.reload();
            }
        });
}

function submitCloseRegister() {
    const regId = document.getElementById('modal-register-id').value;
    const actualCash = document.getElementById('modal-actual-cash').value;
    const notes = document.getElementById('modal-closing-notes').value;

    const fd = new FormData();
    fd.append('id_cash_registers', regId);
    fd.append('actual_cash', actualCash);
    fd.append('closing_notes', notes);

    fetch('<?= site_url('finance/close_register') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Kasa başarıyla kapatıldı! Fark: ' + data.data.difference + ' ₺');
                window.location.reload();
            } else {
                alert(data.message || 'Kasa kapatılamadı.');
            }
        });
}

function openPayoutModal(staffId, staffName) {
    document.getElementById('payout-staff-select').value = staffId;
    const modal = new bootstrap.Modal(document.getElementById('payout-modal'));
    modal.show();
}

function submitPayout() {
    const form = document.getElementById('payout-form');
    const fd = new FormData(form);

    fetch('<?= site_url('finance/generate_payout') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Personel hakediş ödemesi başarıyla kaydedildi!');
                window.location.reload();
            } else {
                alert(data.message || 'Ödeme oluşturulamadı.');
            }
        });
}
</script>
<?php end_section('scripts'); ?>
