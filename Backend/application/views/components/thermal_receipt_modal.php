<!-- Thermal Receipt Print Modal (80mm & 58mm ESC-POS Ready) -->
<div class="modal fade" id="thermalReceiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title fw-bold">
                    <i class="fas fa-print me-2 text-warning"></i>Adisyon & Termal Fiş Önizleme
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-3 bg-light d-flex justify-content-center">
                <!-- RECEIPT CONTAINER (80mm Width) -->
                <div id="receipt-paper" class="bg-white p-4 shadow-sm border" style="width: 320px; font-family: 'JetBrains Mono', 'Courier New', Courier, monospace; font-size: 13px; color: #000; line-height: 1.4;">
                    <div class="text-center mb-3">
                        <h5 class="fw-bold m-0 text-uppercase" id="rcpt-venue-name">RESTORAN & BİSTRO</h5>
                        <small class="text-muted d-block" id="rcpt-venue-subtitle">Müşteri Hesap Fişi</small>
                        <div class="border-top border-bottom border-dark border-dashed my-2 py-1 small">
                            <div class="d-flex justify-content-between">
                                <span>MASA: <strong id="rcpt-table-num">Masa 1</strong></span>
                                <span id="rcpt-date">28.09.2026 15:00</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted" style="font-size: 11px;">
                                <span>GARSON: <span id="rcpt-waiter-name">Garson</span></span>
                                <span>FİŞ NO: #<span id="rcpt-id">1001</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- ORDER ITEMS TABLE -->
                    <table class="w-100 mb-2" style="font-size: 12px; border-collapse: collapse;">
                        <thead>
                            <tr class="border-bottom border-dark">
                                <th class="text-start py-1">ÜRÜN</th>
                                <th class="text-center py-1">AD</th>
                                <th class="text-end py-1">TUTAR</th>
                            </tr>
                        </thead>
                        <tbody id="rcpt-items-body">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>

                    <div class="border-top border-dark border-dashed pt-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <span>ARA TOPLAM:</span>
                            <span id="rcpt-subtotal">0.00 ₺</span>
                        </div>
                        <div class="d-flex justify-content-between text-danger" id="rcpt-discount-row" style="display:none !important;">
                            <span>İNDİRİM / KUPON:</span>
                            <span id="rcpt-discount">-0.00 ₺</span>
                        </div>
                        <div class="d-flex justify-content-between text-success" id="rcpt-complimentary-row" style="display:none !important;">
                            <span>ŞEF İKRAMI:</span>
                            <span id="rcpt-complimentary">-0.00 ₺</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold fs-6 border-top border-dark pt-1 mt-1">
                            <span>GENEL TOPLAM:</span>
                            <span id="rcpt-total">0.00 ₺</span>
                        </div>
                    </div>

                    <!-- PAYMENTS BREAKDOWN -->
                    <div class="border-top border-bottom border-dark border-dashed py-1 mb-2 small" id="rcpt-payments-box">
                        <div class="fw-bold mb-1" style="font-size: 11px;">TAHSİLAT DÖKÜMÜ:</div>
                        <div id="rcpt-payments-lines">
                            <!-- Populated via JS -->
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="text-center pt-2 text-muted" style="font-size: 11px;">
                        <p class="m-0">Bizi Tercih Ettiğiniz İçin</p>
                        <p class="fw-bold m-0">TEŞEKKÜR EDERİZ!</p>
                        <p class="m-0" style="font-size: 10px;">Mali değeri yoktur / Bilgi fişidir.</p>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary btn-sm fw-bold px-3 shadow" onclick="ThermalReceipt.print()">
                    <i class="fas fa-print me-1"></i> 80mm Yazıcıya Gönder
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #receipt-paper, #receipt-paper * {
        visibility: visible;
    }
    #receipt-paper {
        position: absolute;
        left: 0;
        top: 0;
        width: 80mm !important;
        padding: 4mm !important;
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<script>
const ThermalReceipt = {
    open(data) {
        document.getElementById('rcpt-venue-name').innerText = data.venueName || 'BooKi Restoran';
        document.getElementById('rcpt-table-num').innerText = data.tableNumber ? 'Masa ' + data.tableNumber : 'Hızlı Satış';
        document.getElementById('rcpt-date').innerText = data.date || new Date().toLocaleString('tr-TR');
        document.getElementById('rcpt-waiter-name').innerText = data.waiterName || 'Kasiyer';
        document.getElementById('rcpt-id').innerText = data.adisyonId || Math.floor(1000 + Math.random() * 9000);

        const tbody = document.getElementById('rcpt-items-body');
        tbody.innerHTML = '';
        (data.items || []).forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-start py-1">${item.name || item.item_name}</td>
                <td class="text-center py-1">${item.quantity || item.qty}</td>
                <td class="text-end py-1 font-monospace">${parseFloat(item.price || item.total_price).toFixed(2)} ₺</td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('rcpt-subtotal').innerText = (data.subtotal || data.total || 0).toFixed(2) + ' ₺';
        document.getElementById('rcpt-total').innerText = (data.total || 0).toFixed(2) + ' ₺';

        // Payments
        const payBox = document.getElementById('rcpt-payments-lines');
        payBox.innerHTML = '';
        if (data.payments && data.payments.length > 0) {
            data.payments.forEach(p => {
                const line = document.createElement('div');
                line.className = 'd-flex justify-content-between';
                const typeLabels = { cash: 'Nakit', card: 'Kredi Kartı', transfer: 'Havale/EFT', discount: 'İndirim', complimentary: 'İkram' };
                line.innerHTML = `<span>${typeLabels[p.payment_type] || p.payment_type}:</span><span>${parseFloat(p.amount).toFixed(2)} ₺</span>`;
                payBox.appendChild(line);
            });
            document.getElementById('rcpt-payments-box').style.display = 'block';
        } else {
            document.getElementById('rcpt-payments-box').style.display = 'none';
        }

        new bootstrap.Modal(document.getElementById('thermalReceiptModal')).show();
    },
    print() {
        window.print();
    }
};
</script>
