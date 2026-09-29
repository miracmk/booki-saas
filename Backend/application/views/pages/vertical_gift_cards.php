<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @var array $cards
 * @var array $deposits
 * @var array $customers
 */
extend('layouts/backend_layout');
section('content');
?>

<div class="container backend-page py-3" id="gift-cards-page">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="fas fa-gift text-danger me-2"></i>Hediye Kartları & Kapora Yönetimi</h1>
            <p class="text-muted small mb-0">Hediye kartı bakiyeleri, harcama geçmişi ve randevu kapora/depozito güvencesi.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-issue-gift-card">
                <i class="fas fa-plus me-1"></i> Yeni Hediye Kartı Tanımla
            </button>
        </div>
    </div>

        <!-- TENANT KAPORA SİSTEMİ KONTROL PANELİ (TOGGLE) -->
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-lg-7">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-primary-subtle text-primary p-2 rounded-circle me-3">
                                <i class="fas fa-shield-alt fa-lg"></i>
                            </span>
                            <div>
                                <h5 class="fw-bold mb-0">Online Randevu Kapora (Ön Ödeme) Güvencesi</h5>
                                <p class="text-muted small mb-0">Müşterilerden randevu alırken kapora talep ederek randevuya gelmeme (no-show) oranlarını sıfırlayın.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                        <div class="form-check form-switch d-inline-flex align-items-center gap-2 fs-5">
                            <label class="form-check-label fw-bold user-select-none" for="toggle-require-deposit">
                                <span id="deposit-status-text" class="<?= !empty($payment_settings['require_deposit']) ? 'text-success' : 'text-muted' ?>">
                                    <?= !empty($payment_settings['require_deposit']) ? 'Kapora Aktif' : 'Kapora Kapalı' ?>
                                </span>
                            </label>
                            <input class="form-check-input" type="checkbox" role="switch" id="toggle-require-deposit"
                                   style="width: 2.75rem; height: 1.5rem; cursor: pointer;"
                                   <?= !empty($payment_settings['require_deposit']) ? 'checked' : '' ?>>
                        </div>
                    </div>
                </div>

                <div id="deposit-config-panel" class="mt-3 pt-3 border-top" style="<?= empty($payment_settings['require_deposit']) ? 'display:none;' : '' ?>">
                    <form id="form-quick-deposit-settings" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Kapora Hesaplama Türü</label>
                            <select class="form-select form-select-sm" id="deposit-type-select">
                                <option value="fixed" <?= ($payment_settings['deposit_type'] ?? 'fixed') === 'fixed' ? 'selected' : '' ?>>Sabit Tutar (₺)</option>
                                <option value="percentage" <?= ($payment_settings['deposit_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Hizmet Fiyatının Yüzdesi (%)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary" id="deposit-value-label">
                                <?= ($payment_settings['deposit_type'] ?? 'fixed') === 'percentage' ? 'Kapora Oranı (%)' : 'Kapora Tutarı (₺)' ?>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" id="deposit-value-prefix"><?= ($payment_settings['deposit_type'] ?? 'fixed') === 'percentage' ? '%' : '₺' ?></span>
                                <input type="number" step="0.01" min="1" class="form-control" id="deposit-value-input"
                                       value="<?= (float)($payment_settings['deposit_value'] ?? 100.00) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary flex-grow-1" id="btn-save-deposit-settings">
                                <i class="fas fa-check me-1"></i> Ayarları Kaydet
                            </button>
                            <a href="<?= site_url('payment_settings') ?>" class="btn btn-sm btn-outline-secondary" title="Ödeme Ağ Geçidi Ayarları">
                                <i class="fas fa-cog"></i>
                            </a>
                        </div>
                    </form>
                    <div class="mt-2 text-muted small d-flex align-items-center">
                        <i class="fas fa-info-circle me-1 text-primary"></i>
                        <span>Aktif ödeme ağ geçidi: <strong><?= e(strtoupper($payment_settings['active_gateway'] ?? 'none')) ?></strong>. Kapora tutarı rezervasyon sırasında müşteriden online tahsil edilir.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABS -->
        <ul class="nav nav-pills mb-4" id="gift-card-tabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="tab-giftcards-btn" data-bs-toggle="pill" data-bs-target="#tab-giftcards">
                    <i class="fas fa-credit-card me-1"></i> Hediye Kartları (<?= count($cards) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="tab-deposits-btn" data-bs-toggle="pill" data-bs-target="#tab-deposits">
                    <i class="fas fa-shield-alt me-1"></i> Kapora & Depozito Güvencesi (<?= count($deposits) ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="gift-card-tabs-content">
            <!-- TAB 1: GIFT CARDS -->
            <div class="tab-pane fade show active" id="tab-giftcards">
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kart Kodu</th>
                                    <th>Müşteri / Alıcı</th>
                                    <th>Başlangıç Tutarı</th>
                                    <th>Kalan Bakiye</th>
                                    <th>Durum</th>
                                    <th>Geçerlilik</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cards)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Kayıtlı hediye kartı bulunamadı.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($cards as $c): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-dark font-monospace fs-6 px-2 py-1"><?= e($c['code']) ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= e($c['recipient_name'] ?: 'İsimsiz Kart') ?></div>
                                            <small class="text-muted"><?= e($c['recipient_phone'] ?: ($c['recipient_email'] ?: '-')) ?></small>
                                        </td>
                                        <td>₺<?= number_format($c['initial_amount'], 2) ?></td>
                                        <td class="fw-bold text-success">₺<?= number_format($c['current_balance'], 2) ?></td>
                                        <td>
                                            <?php if ($c['status'] === 'active'): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php elseif ($c['status'] === 'redeemed'): ?>
                                                <span class="badge bg-secondary">Tükendi</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark"><?= e(ucfirst($c['status'])) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $c['expires_at'] ? date('d.m.Y', strtotime($c['expires_at'])) : '<span class="text-muted">Süresiz</span>' ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary btn-redeem-card" data-id="<?= $c['id'] ?>" data-code="<?= e($c['code']) ?>" data-balance="<?= $c['current_balance'] ?>">
                                                <i class="fas fa-hand-holding-usd me-1"></i> Bakiye Harca
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

            <!-- TAB 2: DEPOSITS -->
            <div class="tab-pane fade" id="tab-deposits">
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Randevu ID</th>
                                    <th>Müşteri</th>
                                    <th>Hizmet</th>
                                    <th>Randevu Zamanı</th>
                                    <th>Kapora Tutarı</th>
                                    <th>Kapora Durumu</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($deposits)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Kayıtlı kapora işlemi bulunamadı.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($deposits as $d): ?>
                                    <tr>
                                        <td>#<?= $d['id'] ?></td>
                                        <td class="fw-semibold"><?= e(trim($d['first_name'] . ' ' . $d['last_name'])) ?></td>
                                        <td><?= e($d['service_name']) ?></td>
                                        <td><?= date('d.m.Y H:i', strtotime($d['start_datetime'])) ?></td>
                                        <td class="fw-bold text-primary">₺<?= number_format($d['deposit_amount'], 2) ?></td>
                                        <td>
                                            <?php
                                            $stMap = [
                                                'paid' => ['bg-success', 'Ödendi'],
                                                'pending' => ['bg-warning text-dark', 'Bekliyor'],
                                                'refunded' => ['bg-info', 'İade Edildi'],
                                                'forfeited' => ['bg-danger', 'No-Show / Kesildi'],
                                            ];
                                            $st = $stMap[$d['deposit_status']] ?? ['bg-secondary', $d['deposit_status']];
                                            ?>
                                            <span class="badge <?= $st[0] ?>"><?= $st[1] ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-success btn-update-deposit" data-id="<?= $d['id'] ?>" data-status="paid">Ödendi</button>
                                                <button class="btn btn-outline-danger btn-update-deposit" data-id="<?= $d['id'] ?>" data-status="forfeited">Kes (No-Show)</button>
                                                <button class="btn btn-outline-info btn-update-deposit" data-id="<?= $d['id'] ?>" data-status="refunded">İade Et</button>
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
        </div>
    </div>

    <!-- MODAL: YENİ HEDİYE KARTI -->
    <div class="modal fade" id="modal-issue-gift-card" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-gift text-danger me-2"></i>Yeni Hediye Kartı Oluştur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-issue-card">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Alıcı Adı Soyadı</label>
                            <input type="text" name="recipient_name" class="form-control" placeholder="Örn: Ayşe Demir" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Kart Bakiyesi (TL)</label>
                                <input type="number" step="0.01" name="initial_amount" class="form-control" placeholder="500.00" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Son Kullanma Tarihi</label>
                                <input type="date" name="expires_at" class="form-control" value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Alıcı Telefon Numarası</label>
                            <input type="tel" name="recipient_phone" class="form-control" placeholder="05XXXXXXXXX">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Kartı Tanımla & Üret</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: HEDİYE KARTI BAKİYE HARCAMA -->
    <div class="modal fade" id="redeemGiftCardModal" tabindex="-1" aria-labelledby="redeemGiftCardModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="redeemGiftCardModalLabel">
                        <i class="fas fa-hand-holding-usd text-primary me-2"></i>Hediye Kartı Bakiye Harca
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-redeem-gift-card">
                    <input type="hidden" id="redeem-card-id" name="card_id">
                    <input type="hidden" id="redeem-card-code" name="code">
                    <div class="modal-body">
                        <div class="bg-light p-3 rounded mb-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">Kart Kodu:</span>
                                <span class="badge bg-dark font-monospace fs-6 px-2 py-1" id="redeem-display-code">-</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Mevcut Bakiye:</span>
                                <span class="fw-bold text-success fs-5" id="redeem-display-balance">₺0.00</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="redeem-amount" class="form-label fw-semibold">
                                Harcanacak Tutar (₺) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">₺</span>
                                <input type="number" id="redeem-amount" name="amount" class="form-control" step="0.01" min="1" placeholder="0.00" required>
                            </div>
                            <div class="form-text text-muted" id="redeem-max-hint">Maksimum harcanabilir: ₺0.00</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="redeem-adisyon-id" class="form-label fw-semibold">Adisyon ID <small class="text-muted">(İsteğe bağlı)</small></label>
                                <input type="number" id="redeem-adisyon-id" name="adisyon_id" class="form-control" placeholder="Örn: 1042">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="redeem-notes" class="form-label fw-semibold">Not / Açıklama <small class="text-muted">(İsteğe bağlı)</small></label>
                                <input type="text" id="redeem-notes" name="notes" class="form-control" placeholder="Örn: Kuaför hizmeti">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary" id="btn-submit-redeem">
                            <i class="fas fa-check me-1"></i> Harcamayı Onayla
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script>
document.getElementById('form-issue-card').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const data = Object.fromEntries(fd.entries());
        try {
            const res = await fetch('<?= site_url('api/v1/verticals/gift_cards/issue') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            if (res.ok) {
                alert('Hediye kartı başarıyla üretildi: ' + json.code);
                window.location.reload();
            } else {
                alert('Hata: ' + (json.error || 'İşlem başarısız'));
            }
        } catch (err) {
            alert('Ağ hatası: ' + err.message);
        }
    });

    document.querySelectorAll('.btn-update-deposit').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            if (!confirm('Kapora durumunu güncellemek istiyor musunuz?')) return;
            try {
                const res = await fetch('<?= site_url('api/v1/verticals/deposits/update') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ appointment_id: id, status: status })
                });
                if (res.ok) {
                    window.location.reload();
                } else {
                    alert('Hata oluştu.');
                }
            } catch (err) {
                alert('Ağ hatası: ' + err.message);
            }
        });
    });

    // Kapora Toggle & Ayarları Yönetimi
    const toggleDeposit = document.getElementById('toggle-require-deposit');
    const depositConfigPanel = document.getElementById('deposit-config-panel');
    const depositStatusText = document.getElementById('deposit-status-text');
    const depositTypeSelect = document.getElementById('deposit-type-select');
    const depositValueLabel = document.getElementById('deposit-value-label');
    const depositValuePrefix = document.getElementById('deposit-value-prefix');
    const depositValueInput = document.getElementById('deposit-value-input');
    const formQuickDeposit = document.getElementById('form-quick-deposit-settings');

    if (depositTypeSelect) {
        depositTypeSelect.addEventListener('change', function() {
            if (this.value === 'percentage') {
                depositValueLabel.textContent = 'Kapora Oranı (%)';
                depositValuePrefix.textContent = '%';
                if (!depositValueInput.value || Number(depositValueInput.value) > 100) {
                    depositValueInput.value = '25';
                }
            } else {
                depositValueLabel.textContent = 'Kapora Tutarı (₺)';
                depositValuePrefix.textContent = '₺';
                if (!depositValueInput.value || Number(depositValueInput.value) < 10) {
                    depositValueInput.value = '100.00';
                }
            }
        });
    }

    async function sendDepositSettings(enabled) {
        const formData = new FormData();
        formData.append('csrf_token', (typeof vars === 'function' ? vars('csrf_token') : '') || '');
        formData.append('require_deposit', enabled ? '1' : '0');
        formData.append('deposit_type', depositTypeSelect.value);
        formData.append('deposit_value', depositValueInput.value);

        try {
            const res = await fetch('<?= site_url('verticals/save_deposit_settings') ?>', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (res.ok && data.success) {
                if (window.App && App.Layouts && App.Layouts.Backend) {
                    App.Layouts.Backend.displayNotification(data.message);
                } else {
                    alert(data.message);
                }
            } else {
                alert('Hata: ' + (data.message || 'Kapora ayarları kaydedilemedi.'));
            }
        } catch (err) {
            alert('Ağ hatası: ' + err.message);
        }
    }

    if (toggleDeposit) {
        toggleDeposit.addEventListener('change', async function() {
            const isEnabled = this.checked;
            if (isEnabled) {
                depositStatusText.textContent = 'Kapora Aktif';
                depositStatusText.className = 'text-success';
                $(depositConfigPanel).slideDown(200);
            } else {
                depositStatusText.textContent = 'Kapora Kapalı';
                depositStatusText.className = 'text-muted';
                $(depositConfigPanel).slideUp(200);
            }
            await sendDepositSettings(isEnabled);
        });
    }

    if (formQuickDeposit) {
        formQuickDeposit.addEventListener('submit', async function(e) {
            e.preventDefault();
            await sendDepositSettings(toggleDeposit.checked);
        });
    }

    // Gift Card Redemption Modal & Handler
    let activeRedeemRow = null;

    $(document).on('click', '.btn-redeem-card', function(e) {
        e.preventDefault();
        const btn = $(this);
        const cardId = btn.data('id') || btn.attr('data-id');
        const code = btn.data('code') || btn.attr('data-code');
        const balance = parseFloat(btn.data('balance') || btn.attr('data-balance') || 0);

        activeRedeemRow = btn.closest('tr');

        $('#redeem-card-id').val(cardId || '');
        $('#redeem-card-code').val(code || '');
        $('#redeem-display-code').text(code || '-');
        $('#redeem-display-balance').text('₺' + balance.toFixed(2));
        $('#redeem-max-hint').text('Maksimum harcanabilir: ₺' + balance.toFixed(2));

        const amountInput = $('#redeem-amount');
        amountInput.attr('max', balance);
        amountInput.val('');
        $('#redeem-adisyon-id').val('');
        $('#redeem-notes').val('');

        const modalEl = document.getElementById('redeemGiftCardModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    });

    $('#form-redeem-gift-card').on('submit', async function(e) {
        e.preventDefault();
        const btnSubmit = $('#btn-submit-redeem');
        const cardId = $('#redeem-card-id').val();
        const code = $('#redeem-card-code').val();
        const amount = parseFloat($('#redeem-amount').val());
        const balance = parseFloat($('#redeem-amount').attr('max') || 0);
        const adisyonId = $('#redeem-adisyon-id').val();
        const notes = $('#redeem-notes').val();

        if (isNaN(amount) || amount <= 0) {
            alert('Lütfen geçerli bir harcama tutarı giriniz.');
            return;
        }

        if (amount > balance) {
            alert('Harcama tutarı mevcut bakiyeden (₺' + balance.toFixed(2) + ') fazla olamaz.');
            return;
        }

        btnSubmit.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> İşleniyor...');

        try {
            const payload = {
                card_id: cardId ? parseInt(cardId, 10) : undefined,
                code: code,
                amount: amount,
                notes: notes || undefined,
                adisyon_id: adisyonId ? parseInt(adisyonId, 10) : undefined
            };

            const res = await fetch('<?= site_url('api/v1/verticals/gift_cards/redeem') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (res.ok && data.success) {
                const modalEl = document.getElementById('redeemGiftCardModal');
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }

                if (window.App && App.Layouts && App.Layouts.Backend) {
                    App.Layouts.Backend.displayNotification(data.message || 'Bakiye başarıyla harcandı.');
                } else {
                    alert(data.message || 'Bakiye başarıyla harcandı.');
                }

                if (activeRedeemRow && typeof data.remaining_balance !== 'undefined') {
                    const newBalance = parseFloat(data.remaining_balance);
                    activeRedeemRow.find('td:nth-child(4)').text('₺' + newBalance.toFixed(2));
                    const redeemBtn = activeRedeemRow.find('.btn-redeem-card');
                    redeemBtn.data('balance', newBalance).attr('data-balance', newBalance);

                    if (newBalance <= 0.001) {
                        activeRedeemRow.find('td:nth-child(5)').html('<span class="badge bg-secondary">Tükendi</span>');
                        redeemBtn.prop('disabled', true).addClass('disabled');
                    }
                } else {
                    setTimeout(() => window.location.reload(), 1000);
                }
            } else {
                alert('Hata: ' + (data.message || data.error || 'Harcama işlemi gerçekleştirilemedi.'));
            }
        } catch (err) {
            alert('Ağ hatası: ' + err.message);
        } finally {
            btnSubmit.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Harcamayı Onayla');
        }
    });
    </script>
<?php end_section('scripts'); ?>
