<!-- Split Payment Modal Component -->
<div class="modal fade" id="splitPaymentModal" tabindex="-1" aria-labelledby="splitPaymentModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-bottom-0">
                <h5 class="modal-title fw-bold" id="splitPaymentModalLabel">
                    <i class="fas fa-cash-register me-2"></i> Tahsilat & Parçalı Ödeme
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-0">
                <!-- Summary Section -->
                <div class="bg-light p-3 border-bottom">
                    <div class="row text-center mb-2">
                        <div class="col-4 border-end">
                            <div class="text-muted small fw-semibold">Toplam Hesap</div>
                            <div class="fs-4 fw-bold text-dark" id="spm-total-amount">₺0.00</div>
                        </div>
                        <div class="col-4 border-end">
                            <div class="text-muted small fw-semibold">Tahsil Edilen</div>
                            <div class="fs-4 fw-bold text-success" id="spm-paid-amount">₺0.00</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small fw-semibold">Kalan Bakiye</div>
                            <div class="fs-4 fw-bold" id="spm-remaining-amount">₺0.00</div>
                        </div>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div id="spm-progress-bar" class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <!-- Input Section -->
                <div class="p-4 border-bottom">
                    <!-- Quick Amounts -->
                    <div class="mb-3 d-flex flex-wrap gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill fw-bold" onclick="SplitPaymentModal.setQuickAmount('all')">Tamamı</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill fw-bold" onclick="SplitPaymentModal.setQuickAmount('half')">1/2</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill fw-bold" onclick="SplitPaymentModal.setQuickAmount('third')">1/3</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill fw-bold" onclick="SplitPaymentModal.setQuickAmount('quarter')">1/4</button>
                        <div class="vr mx-1"></div>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-bold" onclick="SplitPaymentModal.addAmount(50)">₺50</button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-bold" onclick="SplitPaymentModal.addAmount(100)">₺100</button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-bold" onclick="SplitPaymentModal.addAmount(200)">₺200</button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-bold" onclick="SplitPaymentModal.addAmount(500)">₺500</button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-bold" onclick="SplitPaymentModal.addAmount(1000)">₺1000</button>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tahsilat Türü</label>
                            <select class="form-select form-select-lg" id="spm-payment-type" onchange="SplitPaymentModal.onPaymentTypeChange()">
                                <option value="cash">Nakit</option>
                                <option value="credit_card">Kredi/Banka Kartı</option>
                                <option value="transfer">Havale / EFT</option>
                                <option value="discount">İndirim</option>
                                <option value="complimentary">İkram (Complimentary)</option>
                                <option value="coupon">Kupon Kodu</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tutar</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light text-dark fw-bold">₺</span>
                                <input type="number" class="form-control fw-bold" id="spm-amount" step="0.01" min="0" placeholder="0.00" onkeyup="SplitPaymentModal.calculateChange()">
                            </div>
                        </div>

                        <!-- Dynamic Fields -->
                        <div class="col-12" id="spm-dynamic-fields" style="display: none;">
                            <!-- Cash Change -->
                            <div id="spm-field-cash" style="display: none;" class="bg-light p-3 rounded border">
                                <div class="row align-items-center">
                                    <div class="col-6">
                                        <label class="form-label text-muted mb-1">Alınan Para</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₺</span>
                                            <input type="number" class="form-control" id="spm-tendered" step="0.01" onkeyup="SplitPaymentModal.calculateChange()">
                                        </div>
                                    </div>
                                    <div class="col-6 text-end">
                                        <div class="text-muted mb-1">Para Üstü</div>
                                        <div class="fs-4 fw-bold text-primary" id="spm-change-amount">₺0.00</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Discount Fields -->
                            <div id="spm-field-discount" style="display: none;" class="bg-light p-3 rounded border">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted">İndirim Türü</label>
                                        <select class="form-select" id="spm-discount-type" onchange="SplitPaymentModal.calculateDiscount()">
                                            <option value="amount">Tutar (TL)</option>
                                            <option value="percentage">Yüzde (%)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="spm-discount-pct-col" style="display: none;">
                                        <label class="form-label text-muted">İndirim Yüzdesi</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="spm-discount-pct" min="0" max="100" step="1" onkeyup="SplitPaymentModal.calculateDiscount()">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Complimentary Fields -->
                            <div id="spm-field-complimentary" style="display: none;" class="bg-light p-3 rounded border">
                                <label class="form-label text-muted">İkram Nedeni</label>
                                <input type="text" class="form-control" id="spm-complimentary-reason" placeholder="Örn: Şef ikramı, Müşteri şikayeti...">
                            </div>

                            <!-- Coupon Fields -->
                            <div id="spm-field-coupon" style="display: none;" class="bg-light p-3 rounded border">
                                <label class="form-label text-muted">Kupon Kodu</label>
                                <div class="input-group">
                                    <input type="text" class="form-control text-uppercase" id="spm-coupon-code" placeholder="KOD GİRİNİZ">
                                    <!-- In a real app this would verify, for now just UI -->
                                    <button class="btn btn-outline-secondary" type="button"><i class="fas fa-check"></i> Kontrol Et</button>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Not (Opsiyonel)</label>
                            <input type="text" class="form-control" id="spm-note" placeholder="Tahsilat notu ekleyebilirsiniz...">
                        </div>
                    </div>
                    
                    <div class="mt-3 text-end">
                        <button type="button" class="btn btn-primary fw-bold" onclick="SplitPaymentModal.addPaymentLine()">
                            <i class="fas fa-plus-circle me-1"></i> Tahsilat Satırı Ekle
                        </button>
                    </div>
                </div>

                <!-- Payments Table Section -->
                <div class="p-3">
                    <h6 class="fw-bold mb-3"><i class="fas fa-list-ol me-2"></i>Eklenen Tahsilatlar</h6>
                    <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="25%">Tür</th>
                                    <th width="25%">Tutar</th>
                                    <th width="35%">Not</th>
                                    <th width="10%" class="text-end">İşlem</th>
                                </tr>
                            </thead>
                            <tbody id="spm-payments-list">
                                <!-- Populated by JS -->
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Henüz tahsilat eklenmedi.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer bg-light p-3 d-flex justify-content-between border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                <div>
                    <button type="button" class="btn btn-success fw-bold me-2" onclick="SplitPaymentModal.finalizePayment(false)">
                        <i class="fas fa-save me-1"></i> Kısmi Tahsilat Kaydet
                    </button>
                    <button type="button" class="btn btn-dark fw-bold shadow-sm" onclick="SplitPaymentModal.finalizePayment(true)">
                        <i class="fas fa-check-double me-1"></i> Hesabı Kapat & Masayı Boşalt
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

const SplitPaymentModal = {
    modalInstance: null,
    entityType: 'adisyon',
    entityId: null,
    totalAmount: 0,
    paidAmount: 0,
    payments: [],
    finalizedCallback: null,
    paymentTypeLabels: {
        'cash': 'Nakit',
        'credit_card': 'Kredi Kartı',
        'card': 'Kredi Kartı',
        'transfer': 'Havale / EFT',
        'discount': 'İndirim',
        'complimentary': 'İkram',
        'coupon': 'Kupon'
    },

    init: function() {
        if (!this.modalInstance) {
            const modalEl = document.getElementById('splitPaymentModal');
            if (modalEl) {
                this.modalInstance = new bootstrap.Modal(modalEl);
                modalEl.addEventListener('hidden.bs.modal', () => {
                    this.resetForm();
                });
            }
        }
    },

    open: function(entityType, entityId, totalAmount, existingPayments = []) {
        this.init();
        this.entityType = entityType;
        this.entityId = entityId;
        this.totalAmount = parseFloat(totalAmount) || 0;
        
        this.resetForm();
        this.loadPayments();
        
        this.modalInstance.show();
    },

    close: function() {
        if (this.modalInstance) {
            this.modalInstance.hide();
        }
    },

    onFinalized: function(callback) {
        this.finalizedCallback = callback;
    },

    resetForm: function() {
        document.getElementById('spm-payment-type').value = 'cash';
        document.getElementById('spm-amount').value = '';
        document.getElementById('spm-note').value = '';
        
        document.getElementById('spm-tendered').value = '';
        document.getElementById('spm-change-amount').innerText = '₺0.00';
        
        document.getElementById('spm-discount-type').value = 'amount';
        document.getElementById('spm-discount-pct').value = '';
        
        document.getElementById('spm-complimentary-reason').value = '';
        document.getElementById('spm-coupon-code').value = '';
        
        this.onPaymentTypeChange();
        this.updateSummary();
    },

    onPaymentTypeChange: function() {
        const type = document.getElementById('spm-payment-type').value;
        const dynamicFields = document.getElementById('spm-dynamic-fields');
        const fields = ['cash', 'discount', 'complimentary', 'coupon'];
        
        fields.forEach(f => {
            document.getElementById('spm-field-' + f).style.display = 'none';
        });

        if (fields.includes(type)) {
            dynamicFields.style.display = 'block';
            document.getElementById('spm-field-' + type).style.display = 'block';
            
            if (type === 'cash') {
                const remaining = this.getRemaining();
                if (remaining > 0) {
                    document.getElementById('spm-amount').value = remaining.toFixed(2);
                }
            }
        } else {
            dynamicFields.style.display = 'none';
        }
        this.calculateChange();
    },

    setQuickAmount: function(fraction) {
        const remaining = this.getRemaining();
        if (remaining <= 0) return;
        
        let val = 0;
        if (fraction === 'all') val = remaining;
        else if (fraction === 'half') val = remaining / 2;
        else if (fraction === 'third') val = remaining / 3;
        else if (fraction === 'quarter') val = remaining / 4;
        
        document.getElementById('spm-amount').value = val.toFixed(2);
        this.calculateChange();
    },

    addAmount: function(add) {
        const input = document.getElementById('spm-amount');
        const current = parseFloat(input.value) || 0;
        input.value = (current + add).toFixed(2);
        this.calculateChange();
    },

    getRemaining: function() {
        return Math.max(0, this.totalAmount - this.paidAmount);
    },

    updateSummary: function() {
        document.getElementById('spm-total-amount').innerText = '₺' + this.totalAmount.toFixed(2);
        document.getElementById('spm-paid-amount').innerText = '₺' + this.paidAmount.toFixed(2);
        
        const remaining = this.getRemaining();
        const remEl = document.getElementById('spm-remaining-amount');
        remEl.innerText = '₺' + remaining.toFixed(2);
        
        if (remaining <= 0 && this.totalAmount > 0) {
            remEl.classList.remove('text-danger', 'text-dark');
            remEl.classList.add('text-success');
        } else if (remaining > 0 && remaining < this.totalAmount) {
            remEl.classList.remove('text-success', 'text-dark');
            remEl.classList.add('text-danger');
        } else {
            remEl.classList.remove('text-success', 'text-danger');
            remEl.classList.add('text-dark');
        }

        const pct = this.totalAmount > 0 ? Math.min(100, (this.paidAmount / this.totalAmount) * 100) : 0;
        const bar = document.getElementById('spm-progress-bar');
        bar.style.width = pct + '%';
        bar.setAttribute('aria-valuenow', pct);
        
        if (pct === 100) {
            bar.classList.remove('bg-warning');
            bar.classList.add('bg-success');
        } else {
            bar.classList.remove('bg-success');
            bar.classList.add('bg-warning');
        }
    },

    calculateChange: function() {
        const type = document.getElementById('spm-payment-type').value;
        if (type !== 'cash') return;
        
        const amount = parseFloat(document.getElementById('spm-amount').value) || 0;
        const tendered = parseFloat(document.getElementById('spm-tendered').value) || 0;
        
        const change = Math.max(0, tendered - amount);
        document.getElementById('spm-change-amount').innerText = '₺' + change.toFixed(2);
    },

    calculateDiscount: function() {
        const type = document.getElementById('spm-payment-type').value;
        if (type !== 'discount') return;
        
        const dType = document.getElementById('spm-discount-type').value;
        const pctCol = document.getElementById('spm-discount-pct-col');
        const amountInput = document.getElementById('spm-amount');
        
        if (dType === 'percentage') {
            pctCol.style.display = 'block';
            const pct = parseFloat(document.getElementById('spm-discount-pct').value) || 0;
            const remaining = this.getRemaining();
            amountInput.value = ((remaining * pct) / 100).toFixed(2);
            amountInput.readOnly = true;
        } else {
            pctCol.style.display = 'none';
            amountInput.readOnly = false;
        }
    },

    loadPayments: async function() {
        if (!this.entityId) return;
        
        try {
            const res = await fetch(`<?= site_url('adisyons/split_payments') ?>/${this.entityId}`);
            if (res.ok) {
                const data = await res.json();
                if (data.status === 'success') {
                    this.payments = data.payments || [];
                    this.renderPaymentsTable();
                }
            }
        } catch (e) {
            console.error('Error loading payments:', e);
        }
    },

    renderPaymentsTable: function() {
        const tbody = document.getElementById('spm-payments-list');
        tbody.innerHTML = '';
        
        this.paidAmount = 0;
        
        if (this.payments.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">Henüz tahsilat eklenmedi.</td></tr>`;
            this.updateSummary();
            return;
        }

        this.payments.forEach((p, index) => {
            const amount = parseFloat(p.amount);
            this.paidAmount += amount;
            
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-muted fw-bold">${index + 1}</td>
                <td><span class="badge bg-secondary">${this.paymentTypeLabels[p.payment_type] || p.payment_type}</span></td>
                <td class="fw-bold text-success">₺${amount.toFixed(2)}</td>
                <td class="small text-muted text-truncate" style="max-width: 150px;">${escapeHtml(p.note || '-')}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="SplitPaymentModal.removePaymentLine(${p.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        
        this.updateSummary();
    },

    addPaymentLine: async function() {
        const amount = parseFloat(document.getElementById('spm-amount').value);
        if (!amount || amount <= 0) {
            alert('Lütfen geçerli bir tutar girin.');
            return;
        }

        const type = document.getElementById('spm-payment-type').value;
        let note = document.getElementById('spm-note').value;
        
        // Append context to note
        if (type === 'complimentary') {
            const reason = document.getElementById('spm-complimentary-reason').value;
            note = reason ? `İkram: ${reason}` : note;
        } else if (type === 'coupon') {
            const code = document.getElementById('spm-coupon-code').value;
            note = code ? `Kupon: ${code}` : note;
        }

        const formData = new FormData();
        formData.append('csrf_token', '<?= vars('csrf_token') ?>');
        formData.append('entity_id', this.entityId);
        formData.append('adisyon_id', this.entityId);
        formData.append('entity_type', this.entityType);
        formData.append('amount', amount);
        formData.append('payment_type', type);
        formData.append('notes', note);
        formData.append('note', note);

        try {
            const res = await fetch(`<?= site_url('adisyons/add_split_payment') ?>`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.status === 'success') {
                this.resetForm();
                this.loadPayments(); // Reload to get IDs
            } else {
                alert(data.message || 'Tahsilat eklenirken hata oluştu.');
            }
        } catch (e) {
            console.error('Add payment error:', e);
            alert('Bir ağ hatası oluştu.');
        }
    },

    removePaymentLine: async function(id) {
        if (!confirm('Bu tahsilatı silmek istediğinize emin misiniz?')) return;
        
        const formData = new FormData();
        formData.append('csrf_token', '<?= vars('csrf_token') ?>');

        try {
            const res = await fetch(`<?= site_url('adisyons/remove_split_payment') ?>/${id}`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.status === 'success') {
                this.loadPayments();
            } else {
                alert(data.message || 'Silme işlemi başarısız.');
            }
        } catch (e) {
            console.error('Remove payment error:', e);
        }
    },

    finalizePayment: async function(closeTable) {
        if (this.payments.length === 0) {
            alert('Lütfen önce tahsilat satırı ekleyin.');
            return;
        }
        
        if (closeTable && this.getRemaining() > 0) {
            if (!confirm(`Hala ₺${this.getRemaining().toFixed(2)} bakiye görünüyor. Yine de hesabı kapatmak istiyor musunuz?`)) {
                return;
            }
        }

        const formData = new FormData();
        formData.append('csrf_token', '<?= vars('csrf_token') ?>');
        formData.append('close_table', closeTable ? '1' : '0');

        try {
            const res = await fetch(`<?= site_url('adisyons/finalize_split_payment') ?>/${this.entityId}`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.status === 'success') {
                this.close();
                if (this.finalizedCallback) {
                    this.finalizedCallback(data);
                } else {
                    if (typeof showPosToast === 'function') {
                        showPosToast('Tahsilat başarıyla tamamlandı.', 'success');
                    } else {
                        alert('Tahsilat başarıyla tamamlandı.');
                    }
                    if (closeTable && typeof closeActiveTable === 'function') {
                        closeActiveTable();
                    } else {
                        window.location.reload();
                    }
                }
            } else {
                alert(data.message || 'İşlem tamamlanamadı.');
            }
        } catch (e) {
            console.error('Finalize error:', e);
            alert('Bir ağ hatası oluştu.');
        }
    }
};
</script>
