<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * BooKi - Professional Waitress / Server Handheld Terminal
 * Mobile & Tablet Floor View, Real-Time Table Calls, Fast Order Entry & Kitchen Dispatch
 *
 * @var array $tables
 * @var array $categories
 * @var array $menu_items
 * @var array $active_calls
 */
$user_display_name = vars('user_display_name') ?? 'Garson';
$sections = array_values(array_unique(array_filter(array_column($tables, 'section'))));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Garson El Terminali - BooKi</title>
    <link rel="stylesheet" type="text/css" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/general.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/backend.min.css') ?>">
    <script src="<?= base_url('assets/vendor/@fortawesome-fontawesome-free/fontawesome.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/@fortawesome-fontawesome-free/solid.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/bootstrap/bootstrap.min.js') ?>"></script>
    <style>
        :root {
            --waiter-bg: #090e17;
            --waiter-surface: #131c2d;
            --waiter-border: #233149;
            --waiter-avail: #059669;
            --waiter-dining: #2563eb;
            --waiter-bill: #d97706;
            --waiter-called: #dc2626;
            --waiter-clean: #475569;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--waiter-bg);
            color: #f8fafc;
            padding-bottom: 70px;
            user-select: none;
            min-height: 100vh;
        }
        .mono-num {
            font-family: 'JetBrains Mono', monospace;
        }
        /* HEADER */
        .waiter-navbar {
            background-color: #0f172a;
            border-bottom: 1px solid #1e293b;
        }
        .waiter-nav-btn {
            font-size: 0.85rem;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 9999px;
            transition: all 0.15s;
        }
        /* CALL BANNER */
        .call-banner {
            border-left: 6px solid #ef4444;
            background: #1e293b;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.25);
            animation: pulse-call 1.5s infinite alternate;
        }
        @keyframes pulse-call {
            from { border-left-color: #ef4444; }
            to { border-left-color: #f59e0b; }
        }
        /* SECTION FILTER */
        .btn-sec-pill {
            font-size: 0.85rem;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 9999px;
            border: 1px solid #334155;
            background: #131c2d;
            color: #94a3b8;
            white-space: nowrap;
            transition: all 0.15s;
        }
        .btn-sec-pill.active, .btn-sec-pill:hover {
            background: #f59e0b;
            border-color: #f59e0b;
            color: #0f172a;
            box-shadow: 0 0 12px rgba(245, 158, 11, 0.4);
        }
        /* TABLE CARD */
        .waiter-card {
            border-radius: 18px;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            border: 2px solid transparent;
            min-height: 125px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .waiter-card:active {
            transform: scale(0.96);
        }
        .tbl-available {
            background-color: #064e3b;
            border-color: #059669;
        }
        .tbl-dining {
            background-color: #1e3a8a;
            border-color: #3b82f6;
        }
        .tbl-bill {
            background-color: #78350f;
            border-color: #f59e0b;
            animation: pulse-bill 1.2s infinite alternate;
        }
        .tbl-called {
            background-color: #7f1d1d;
            border-color: #ef4444;
            animation: pulse-bill 0.8s infinite alternate;
        }
        .tbl-cleaning {
            background-color: #1e293b;
            border-color: #475569;
        }
        .tbl-reserved {
            background-color: #2e1065;
            border-color: #8b5cf6;
        }
        @keyframes pulse-bill {
            from { box-shadow: 0 0 5px rgba(239, 68, 68, 0.4); }
            to { box-shadow: 0 0 20px rgba(239, 68, 68, 0.9); }
        }
        /* MODAL */
        .modal-dark .modal-content {
            background-color: #131c2d;
            color: #ffffff;
            border-radius: 20px;
            border: 1px solid #233149;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        /* TOAST */
        #waiter-toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
        }
    </style>
</head>
<body>

    <!-- TOP HEADER -->
    <nav class="waiter-navbar px-3 py-2 text-white shadow-sm mb-3">
        <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap gap-2">
            
            <div class="d-flex align-items-center gap-2">
                <a href="<?= site_url('restaurant') ?>" class="btn btn-outline-light btn-sm waiter-nav-btn">
                    <i class="fas fa-th-large me-1"></i> Masa Planı
                </a>
                <a href="<?= site_url('restaurant/register_screen') ?>" class="btn btn-outline-warning btn-sm waiter-nav-btn">
                    <i class="fas fa-cash-register me-1"></i> Kasa
                </a>
                <a href="<?= site_url('restaurant/kitchen_screen') ?>" class="btn btn-outline-danger btn-sm waiter-nav-btn">
                    <i class="fas fa-fire me-1"></i> Mutfak
                </a>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-warning btn-sm rounded-circle p-2" style="width: 38px; height: 38px;" onclick="testSound()" title="Zil Test">
                    <i class="fas fa-bell"></i>
                </button>
                <div class="badge bg-dark border border-secondary py-2 px-3 rounded-pill mono-num fs-6">
                    <i class="fas fa-clock text-warning me-1"></i><span id="clock-text">--:--</span>
                </div>
                <span class="badge bg-secondary rounded-pill py-2 px-3 small d-none d-sm-inline-block">
                    <i class="fas fa-user-circle me-1"></i><?= e($user_display_name) ?>
                </span>
            </div>

        </div>
    </nav>

    <div class="container-fluid px-3 px-md-4">

        <!-- ACTIVE WAITER CALLS BANNER -->
        <div id="waiter-calls-container" class="mb-3">
            <?php foreach ($active_calls as $c): ?>
                <div class="card call-banner p-3 mb-2 rounded-4 shadow-sm" id="call-box-<?= $c['id_restaurant_tables'] ?>">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <div class="fw-bold text-danger fs-5">
                                <i class="fas fa-exclamation-triangle fa-spin me-2 text-warning"></i>
                                <span>Masa <?= e($c['table_number']) ?></span>
                                <span class="badge bg-danger ms-2"><?= $c['call_type'] === 'bill' ? 'Hesap İstendi' : 'Garson Çağrısı' ?></span>
                            </div>
                            <div class="small text-light mt-1"><?= e($c['note'] ?: 'Misafir masaya servis talep ediyor.') ?></div>
                        </div>
                        <button class="btn btn-success btn-sm rounded-pill px-3 py-2 fw-bold shadow" onclick="resolveCall(<?= $c['id_restaurant_tables'] ?>)">
                            <i class="fas fa-check me-1"></i> Gittim / Tamamlandı
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- SECTION FILTER PILLS -->
        <div class="d-flex gap-2 mb-3 overflow-x-auto pb-1" id="section-bar">
            <button class="btn-sec-pill active" onclick="filterWaiterTables('all', this)">
                <i class="fas fa-layer-group me-1"></i> Tüm Masalar
            </button>
            <?php foreach ($sections as $sec): ?>
                <button class="btn-sec-pill" onclick="filterWaiterTables('<?= e($sec) ?>', this)">
                    <?= e($sec) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- TABLES GRID -->
        <div class="row g-2" id="waiter-tables-grid">
            <?php foreach ($tables as $t): ?>
                <?php
                $is_called = ($t['waiter_call_status'] ?? 'none') !== 'none';
                $cls = 'tbl-' . $t['status'];
                if ($is_called) {
                    $cls = $t['waiter_call_status'] === 'bill_requested' ? 'tbl-bill' : 'tbl-called';
                }
                ?>
                <div class="col-6 col-md-4 col-lg-3 waiter-tbl-col" data-section="<?= e($t['section']) ?>">
                    <div class="waiter-card <?= $cls ?> p-3 h-100 d-flex flex-column justify-content-between shadow-sm"
                         onclick="openTableActions(<?= $t['id'] ?>, '<?= e($t['table_number']) ?>', '<?= e($t['status']) ?>')">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-dark fs-6 fw-bolder">Masa <?= e($t['table_number']) ?></span>
                                <small class="text-white-50 fw-semibold" style="font-size: 11px;"><?= e($t['section']) ?></small>
                            </div>
                            <?php if ($is_called): ?>
                                <span class="badge bg-danger w-100 py-1 mb-1 fw-bold">
                                    <i class="fas fa-bell fa-bounce me-1"></i>ÇALIYOR!
                                </span>
                            <?php elseif ($t['status'] === 'seated' || $t['status'] === 'dining'): ?>
                                <span class="badge bg-primary text-white w-100 py-1 mb-1 fw-bold">
                                    <i class="fas fa-utensils me-1"></i>Dolu (<?= $t['minutes_seated'] ?> dk)
                                </span>
                            <?php elseif ($t['status'] === 'available'): ?>
                                <span class="badge bg-success text-white w-100 py-1 mb-1 fw-bold">
                                    <i class="fas fa-check-circle me-1"></i>Boş
                                </span>
                            <?php elseif ($t['status'] === 'cleaning'): ?>
                                <span class="badge bg-secondary text-white w-100 py-1 mb-1 fw-bold">
                                    <i class="fas fa-broom me-1"></i>Temizlik
                                </span>
                            <?php elseif ($t['status'] === 'reserved'): ?>
                                <span class="badge text-white w-100 py-1 mb-1 fw-bold" style="background-color: #8b5cf6;">
                                    <i class="fas fa-calendar-check me-1"></i>Rezerve
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-white border-opacity-15 small">
                            <span class="text-white-50"><i class="fas fa-user-friends me-1"></i><?= $t['capacity'] ?> Kişi</span>
                            <strong class="text-warning mono-num fs-6"><?= !empty($t['adisyon_total']) ? number_format((float) $t['adisyon_total'], 2) . ' ₺' : '-' ?></strong>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- TABLE ACTIONS MODAL -->
    <div class="modal fade modal-dark" id="tableActionsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-3">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-white" id="action-modal-table-title">Masa --</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="d-grid gap-2">
                        <button class="btn btn-warning py-3 fw-bold rounded-4 shadow-sm" onclick="openOrderEntry()">
                            <i class="fas fa-plus-circle me-2"></i>Sipariş Gir & Mutfağa İlet
                        </button>
                        <button class="btn btn-success py-3 fw-bold rounded-4 shadow-sm" onclick="openWaitressSplitPayment()">
                            <i class="fas fa-cash-register me-2"></i>Masada Tahsilat Al (Parçalı)
                        </button>
                        <button class="btn btn-outline-danger py-2 rounded-4" onclick="callBillForTable()">
                            <i class="fas fa-receipt me-2"></i>Hesap İsteği Bildir
                        </button>
                        <button class="btn btn-outline-light py-2 rounded-4" onclick="openTransferModal()">
                            <i class="fas fa-exchange-alt me-2"></i>Masayı Başka Masaya Taşı
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ORDER ENTRY MODAL -->
    <div class="modal fade modal-dark" id="orderEntryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content p-3">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title fw-bold text-white" id="order-entry-title">Masa Sipariş Girişi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control bg-dark text-white border-secondary rounded-end-pill py-2" 
                               id="waiter-search-food" placeholder="Menüde hızlı ara..." onkeyup="filterWaiterFood()">
                    </div>
                    
                    <div class="row g-2 overflow-y-auto mb-3" style="max-height: 320px;" id="waiter-food-grid">
                        <?php foreach ($menu_items as $mi): ?>
                            <div class="col-6 col-md-4 waiter-food-item" data-name="<?= strtolower(e($mi['name'])) ?>">
                                <div class="card bg-dark border-secondary p-3 rounded-3 h-100 cursor-pointer d-flex flex-column justify-content-between shadow-sm"
                                     style="cursor: pointer;"
                                     onclick="addWaiterItem(<?= $mi['id'] ?>, '<?= htmlspecialchars(addslashes($mi['name']), ENT_QUOTES, 'UTF-8') ?>', <?= (float) $mi['price'] ?>, '<?= $mi['station'] ?>')">
                                    <div class="fw-bold small text-light"><?= e($mi['name']) ?></div>
                                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-secondary">
                                        <span class="text-warning fw-bold mono-num"><?= number_format((float) $mi['price'], 2) ?> ₺</span>
                                        <span class="badge bg-secondary" style="font-size: 10px;"><?= strtoupper($mi['station']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- TEMP CART SUMMARY -->
                    <div class="p-3 bg-dark bg-opacity-75 rounded-4 border border-secondary mb-3">
                        <h6 class="fw-bold text-warning mb-2"><i class="fas fa-shopping-basket me-1"></i>Eklenecek Ürünler:</h6>
                        <div id="waiter-temp-cart" class="small mb-2 text-white-50">Henüz ürün seçilmedi.</div>
                        <div class="d-flex justify-content-between fw-bold text-light border-top border-secondary pt-2">
                            <span>Ara Toplam:</span>
                            <span id="waiter-temp-total" class="text-warning mono-num fs-5">0.00 ₺</span>
                        </div>
                    </div>

                    <button class="btn btn-success w-100 py-3 rounded-pill fw-bold fs-6 shadow" id="btn-waiter-send" onclick="sendWaiterOrderToKitchen()">
                        <i class="fas fa-paper-plane me-2"></i>Mutfağa & Bara Gönder
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLE TRANSFER MODAL -->
    <div class="modal fade modal-dark" id="transferModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-3">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-white">Masa Aktarımı / Taşıma</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <label class="form-label small text-secondary">Hedef Masayı Seçin:</label>
                    <select class="form-select bg-dark text-white border-secondary rounded-3 mb-3 py-2" id="target-table-select">
                        <?php foreach ($tables as $t): ?>
                            <?php if ($t['status'] === 'available'): ?>
                                <option value="<?= $t['id'] ?>">Masa <?= e($t['table_number']) ?> (<?= e($t['section']) ?> - Boş)</option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow" onclick="confirmTableTransfer()">
                        Masayı Aktar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TOAST -->
    <div id="waiter-toast-container"></div>

    <script>
        let selectedTableId = null;
        let selectedTableNumber = null;
        let waiterCart = [];
        let audioCtx = null;

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        setInterval(() => {
            document.getElementById('clock-text').innerText = new Date().toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
        }, 1000);

        function showWaiterToast(message, type = 'info') {
            const container = document.getElementById('waiter-toast-container');
            const toastEl = document.createElement('div');
            toastEl.className = `alert alert-${type} shadow-lg rounded-4 py-2 px-3 mb-2 d-flex align-items-center gap-2 fade show`;
            toastEl.style.minWidth = '260px';
            toastEl.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
                <div class="small fw-bold">${escapeHtml(message)}</div>
            `;
            container.appendChild(toastEl);
            setTimeout(() => {
                toastEl.classList.remove('show');
                setTimeout(() => toastEl.remove(), 250);
            }, 3000);
        }

        function testSound() {
            try {
                if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                if (audioCtx.state === 'suspended') audioCtx.resume();
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(659.25, audioCtx.currentTime);
                gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.3);
                showWaiterToast('Ses bildirimi aktif!', 'success');
            } catch (e) {}
        }

        function filterWaiterTables(section, btn) {
            document.querySelectorAll('#section-bar button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            document.querySelectorAll('.waiter-tbl-col').forEach(c => {
                const s = c.getAttribute('data-section');
                c.style.display = (section === 'all' || s === section) ? 'block' : 'none';
            });
        }

        function openTableActions(id, number, status) {
            selectedTableId = id;
            selectedTableNumber = number;
            document.getElementById('action-modal-table-title').innerText = `Masa ${number}`;
            new bootstrap.Modal(document.getElementById('tableActionsModal')).show();
        }

        async function resolveCall(tableId) {
            try {
                const res = await fetch('<?= site_url('restaurant/api/resolve_call') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ table_id: tableId })
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.status === 'success') {
                    const box = document.getElementById(`call-box-${tableId}`);
                    if (box) box.remove();
                    showWaiterToast('Çağrı tamamlandı olarak kapatıldı.', 'success');
                    setTimeout(() => window.location.reload(), 500);
                }
            } catch (e) {
                showWaiterToast('Hata: ' + e.message, 'danger');
            }
        }

        function openOrderEntry() {
            bootstrap.Modal.getInstance(document.getElementById('tableActionsModal')).hide();
            document.getElementById('order-entry-title').innerText = `Masa ${selectedTableNumber} Sipariş Girişi`;
            waiterCart = [];
            renderWaiterCart();
            new bootstrap.Modal(document.getElementById('orderEntryModal')).show();
        }

        function filterWaiterFood() {
            const q = document.getElementById('waiter-search-food').value.toLowerCase().trim();
            document.querySelectorAll('.waiter-food-item').forEach(c => {
                const name = c.getAttribute('data-name');
                c.style.display = (!q || name.includes(q)) ? 'block' : 'none';
            });
        }

        function addWaiterItem(id, name, price, station) {
            const existing = waiterCart.find(i => i.id === id);
            if (existing) {
                existing.quantity++;
            } else {
                waiterCart.push({ id, name, price, station, quantity: 1 });
            }
            renderWaiterCart();
            showWaiterToast(`${name} eklendi`, 'info');
        }

        function renderWaiterCart() {
            const container = document.getElementById('waiter-temp-cart');
            if (waiterCart.length === 0) {
                container.innerHTML = 'Henüz ürün seçilmedi.';
                document.getElementById('waiter-temp-total').innerText = '0.00 ₺';
                return;
            }

            container.innerHTML = waiterCart.map((item, idx) => `
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-secondary text-white">
                    <span><strong>${item.quantity}x</strong> ${escapeHtml(item.name)}</span>
                    <div class="d-flex align-items-center gap-2">
                        <strong class="text-warning mono-num">${(item.quantity * item.price).toFixed(2)} ₺</strong>
                        <button class="btn btn-sm btn-link text-danger p-0" onclick="removeWaiterItem(${idx})"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            `).join('');

            const total = waiterCart.reduce((sum, item) => sum + (item.quantity * item.price), 0);
            document.getElementById('waiter-temp-total').innerText = total.toFixed(2) + ' ₺';
        }

        function removeWaiterItem(idx) {
            waiterCart.splice(idx, 1);
            renderWaiterCart();
        }

        async function sendWaiterOrderToKitchen() {
            if (waiterCart.length === 0) {
                showWaiterToast('Lütfen en az bir ürün seçin.', 'warning');
                return;
            }

            const btn = document.getElementById('btn-waiter-send');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Gönderiliyor...';

            try {
                const res = await fetch('<?= site_url('restaurant/api/self_order') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        table_id: selectedTableId,
                        items: waiterCart
                    })
                });
                if (!res.ok) {
                    showWaiterToast('Sipariş iletilirken sunucu hatası (' + res.status + ')', 'danger');
                    return;
                }
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    showWaiterToast('Sipariş başarıyla mutfağa iletildi!', 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    showWaiterToast('Hata: ' + (data.message || 'Sipariş gönderilemedi.'), 'danger');
                }
            } catch (err) {
                showWaiterToast('Ağ hatası: ' + err.message, 'danger');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Mutfağa & Bara Gönder';
            }
        }

        async function callBillForTable() {
            try {
                const res = await fetch('<?= site_url('restaurant/api/call_waiter') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ table_id: selectedTableId, call_type: 'bill', note: 'Garson Tarafından Hesap İstendi' })
                });
                if (!res.ok) return;
                const data = await res.json();
                showWaiterToast(data.message || 'Hesap talebi kaydedildi.', 'success');
                setTimeout(() => window.location.reload(), 600);
            } catch (e) {
                showWaiterToast('Hata: ' + e.message, 'danger');
            }
        }

        function openTransferModal() {
            bootstrap.Modal.getInstance(document.getElementById('tableActionsModal')).hide();
            new bootstrap.Modal(document.getElementById('transferModal')).show();
        }

        async function confirmTableTransfer() {
            const targetId = document.getElementById('target-table-select').value;
            if (!targetId) return;

            try {
                const res = await fetch('<?= site_url('restaurant/api/transfer_table') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        source_table_id: selectedTableId,
                        target_table_id: targetId,
                        from_table_id: selectedTableId,
                        to_table_id: targetId
                    })
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.status === 'success') {
                    showWaiterToast('Masa başarıyla aktarıldı.', 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    showWaiterToast(data.message || 'Masa aktarılamadı.', 'danger');
                }
            } catch (e) {
                showWaiterToast('Hata: ' + e.message, 'danger');
            }
        }

        // Masada Tahsilat Al (Parçalı Ödeme)
        async function openWaitressSplitPayment() {
            bootstrap.Modal.getInstance(document.getElementById('tableActionsModal')).hide();
            try {
                const res = await fetch(`<?= site_url('restaurant/api/table_experience?table_id=') ?>${selectedTableId}`);
                const data = await res.json();
                if (data.status === 'success' && data.data && data.data.current_id_adisyons) {
                    const adisyonId = data.data.current_id_adisyons;
                    const total = parseFloat(data.data.adisyon_total) || 0;
                    SplitPaymentModal.open('adisyon', adisyonId, total, []);
                    SplitPaymentModal.onFinalized(function() {
                        showWaiterToast('Tahsilat başarıyla kaydedildi!', 'success');
                        setTimeout(() => window.location.reload(), 800);
                    });
                } else {
                    showWaiterToast('Bu masada açık bir adisyon bulunamadı.', 'warning');
                }
            } catch (err) {
                showWaiterToast('Hata: ' + err.message, 'danger');
            }
        }
    </script>

    <!-- Load Split Payment Modal Component -->
    <?php $this->load->view('components/split_payment_modal'); ?>
</body>
</html>
