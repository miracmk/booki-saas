<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * BooKi - Professional Kitchen Display System (KDS)
 * Multi-Station Routing (Kitchen, Bar, Grill, Dessert) with Audio Alerts & Elapsed Timers
 *
 * @var string $station
 * @var array $orders
 */
$user_display_name = vars('user_display_name') ?? 'Mutfak Ekibi';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>KDS — Mutfak & Bar Ekranı - BooKi</title>
    <link rel="stylesheet" type="text/css" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/general.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/backend.min.css') ?>">
    <script src="<?= base_url('assets/vendor/@fortawesome-fontawesome-free/fontawesome.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/@fortawesome-fontawesome-free/solid.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/bootstrap/bootstrap.min.js') ?>"></script>
    <style>
        :root {
            --kds-bg: #090d16;
            --kds-header: #0f172a;
            --kds-card: #131c2e;
            --kds-card-border: #1e293b;
            --kds-new: #ef4444;
            --kds-prep: #f59e0b;
            --kds-ready: #10b981;
            --kds-served: #3b82f6;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--kds-bg);
            color: #f8fafc;
            min-height: 100vh;
            user-select: none;
        }
        .mono-num {
            font-family: 'JetBrains Mono', monospace;
        }
        /* TOP NAV */
        .kds-navbar {
            background-color: var(--kds-header);
            border-bottom: 1px solid #1e293b;
        }
        .kds-nav-btn {
            font-size: 0.85rem;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 9999px;
            transition: all 0.15s;
        }
        /* STATION TABS */
        .btn-station-tab {
            font-weight: 700;
            padding: 8px 18px;
            border-radius: 9999px;
            font-size: 0.9rem;
            border: 1px solid #334155;
            color: #94a3b8;
            background: #1e293b;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-station-tab.active, .btn-station-tab:hover {
            color: #ffffff;
            background: #ea580c;
            border-color: #ea580c;
            box-shadow: 0 0 15px rgba(234, 88, 12, 0.4);
        }
        /* TICKET CARD */
        .kds-card {
            background-color: var(--kds-card);
            border-radius: 18px;
            border: 2px solid var(--kds-card-border);
            transition: transform 0.15s, border-color 0.2s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .kds-card-new {
            border-color: var(--kds-new) !important;
            box-shadow: 0 0 18px rgba(239, 68, 68, 0.25);
        }
        .kds-card-preparing {
            border-color: var(--kds-prep) !important;
            box-shadow: 0 0 18px rgba(245, 158, 11, 0.25);
        }
        .kds-card-ready {
            border-color: var(--kds-ready) !important;
            box-shadow: 0 0 18px rgba(16, 185, 129, 0.25);
        }
        /* TIMERS */
        .timer-badge-normal {
            background: #10b981;
            color: #ffffff;
        }
        .timer-badge-warning {
            background: #f59e0b;
            color: #000000;
        }
        .timer-badge-critical {
            background: #ef4444;
            color: #ffffff;
            animation: pulse-danger 1s infinite alternate;
        }
        @keyframes pulse-danger {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0.85; transform: scale(1.04); }
        }
        /* BUMP BUTTONS */
        .bump-btn {
            font-size: 1.05rem;
            padding: 12px 14px;
            font-weight: 800;
            border-radius: 12px;
            letter-spacing: 0.5px;
            transition: transform 0.1s;
        }
        .bump-btn:active {
            transform: scale(0.97);
        }
        /* TOAST */
        #kds-toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
        }
    </style>
</head>
<body class="d-flex flex-column">

    <!-- TOP HEADER -->
    <nav class="kds-navbar px-3 py-2 text-white shadow-sm">
        <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap gap-2">
            
            <div class="d-flex align-items-center gap-3">
                <a href="<?= site_url('restaurant') ?>" class="btn btn-outline-light btn-sm kds-nav-btn">
                    <i class="fas fa-th-large me-1"></i> Masa Planı
                </a>
                <a href="<?= site_url('restaurant/register_screen') ?>" class="btn btn-outline-warning btn-sm kds-nav-btn">
                    <i class="fas fa-cash-register me-1"></i> Kasa Terminali
                </a>
                <a href="<?= site_url('restaurant/waitress_screen') ?>" class="btn btn-outline-info btn-sm kds-nav-btn">
                    <i class="fas fa-tablet-alt me-1"></i> Garson Terminali
                </a>
                <div class="ms-2 d-none d-lg-flex align-items-center gap-2 border-start border-secondary ps-3">
                    <span class="badge bg-danger text-white px-2 py-1 fw-bold fs-6">
                        <i class="fas fa-fire me-1"></i> KDS Mutfak
                    </span>
                    <span class="text-secondary small">Canlı İstasyon Yönetimi</span>
                </div>
            </div>

            <!-- RIGHT CONTROLS -->
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-outline-light btn-sm kds-nav-btn d-none d-md-inline-block" onclick="toggleFullScreen()">
                    <i class="fas fa-expand me-1"></i> Tam Ekran
                </button>
                <button class="btn btn-outline-warning btn-sm kds-nav-btn" onclick="enableAudioAndTest()">
                    <i class="fas fa-volume-up me-1"></i> Ses Testi
                </button>
                <div class="form-check form-switch text-light small d-flex align-items-center gap-2 m-0">
                    <input class="form-check-input" type="checkbox" id="auto-sound-toggle" checked>
                    <label class="form-check-label fw-semibold" for="auto-sound-toggle">Zil Sesi</label>
                </div>
                <button class="btn btn-outline-info btn-sm kds-nav-btn position-relative" data-bs-toggle="offcanvas" data-bs-target="#kdsIntercomDrawer" onclick="loadIntercomChats()">
                    <i class="fas fa-comments me-1"></i> Telsiz & İletişim
                    <span class="badge bg-danger rounded-pill ms-1" id="kds-unread-badge" style="display:none">0</span>
                </button>
                <div class="badge bg-dark border border-secondary py-2 px-3 rounded-pill mono-num fs-6">
                    <i class="fas fa-clock text-warning me-1"></i><span id="kds-clock">--:--:--</span>
                </div>
            </div>

        </div>
    </nav>

    <!-- CONTENT WRAPPER -->
    <div class="container-fluid p-3 p-md-4 flex-grow-1">

        <!-- STATION TABS BAR -->
        <div class="d-flex gap-2 mb-4 overflow-x-auto pb-2" id="station-tabs-container">
            <button class="btn-station-tab <?= ($station === 'all' || empty($station)) ? 'active' : '' ?>" onclick="switchStation('all', this)">
                <i class="fas fa-layer-group"></i> Tüm İstasyonlar (<span id="count-all"><?= count($orders) ?></span>)
            </button>
            <button class="btn-station-tab <?= ($station === 'hot_sauce' || $station === 'kitchen') ? 'active' : '' ?>" onclick="switchStation('hot_sauce', this)">
                <i class="fas fa-fire text-danger"></i> Sıcak & Sos
            </button>
            <button class="btn-station-tab <?= ($station === 'grill_meat' || $station === 'grill') ? 'active' : '' ?>" onclick="switchStation('grill_meat', this)">
                <i class="fas fa-drumstick-bite text-warning"></i> Izgara & Et
            </button>
            <button class="btn-station-tab <?= ($station === 'cold_pantry') ? 'active' : '' ?>" onclick="switchStation('cold_pantry', this)">
                <i class="fas fa-leaf text-success"></i> Soğuk Meze
            </button>
            <button class="btn-station-tab <?= ($station === 'bakery_dough') ? 'active' : '' ?>" onclick="switchStation('bakery_dough', this)">
                <i class="fas fa-pizza-slice text-warning"></i> Hamur & Fırın
            </button>
            <button class="btn-station-tab <?= ($station === 'pastry_dessert' || $station === 'dessert') ? 'active' : '' ?>" onclick="switchStation('pastry_dessert', this)">
                <i class="fas fa-birthday-cake text-info"></i> Tatlı & Pastane
            </button>
            <button class="btn-station-tab <?= ($station === 'bar' || $station === 'bartender') ? 'active' : '' ?>" onclick="switchStation('bar', this)">
                <i class="fas fa-cocktail text-primary"></i> Bar & İçecek
            </button>
        </div>

        <!-- TICKETS GRID -->
        <div class="row g-3" id="kds-tickets-grid">
            <?php if (empty($orders)): ?>
                <div class="col-12 text-center py-5 text-secondary" id="empty-tickets-msg">
                    <div class="mb-3 text-success opacity-75">
                        <i class="fas fa-check-circle fa-4x"></i>
                    </div>
                    <h4 class="fw-bold text-light">Mutfak veya Barda Bekleyen Sipariş Yok</h4>
                    <p class="small text-secondary">Masalardan veya garson el terminalinden yeni sipariş açıldığında burada anlık sesli olarak belirecektir.</p>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                    <?php
                    $ord_time = strtotime($o['ordered_at']);
                    $elapsed_min = max(0, round((time() - $ord_time) / 60));
                    $timer_class = $elapsed_min >= 20 ? 'timer-badge-critical' : ($elapsed_min >= 10 ? 'timer-badge-warning' : 'timer-badge-normal');
                    $card_state_class = 'kds-card-' . e($o['status']);
                    ?>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 ticket-card-col" data-station="<?= e($o['station']) ?>" data-id="<?= $o['id'] ?>">
                        <div class="kds-card <?= $card_state_class ?> p-3 h-100 d-flex flex-column justify-content-between">
                            <div>
                                <!-- TICKET HEADER -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-danger fs-5 px-3 py-1 fw-bolder rounded-pill">
                                        Masa: <?= e($o['table_number'] ?: 'Paket') ?>
                                    </span>
                                    <span class="badge <?= $timer_class ?> fs-6 px-2 py-1 mono-num rounded-pill">
                                        <i class="fas fa-stopwatch me-1"></i><?= $elapsed_min ?> dk
                                    </span>
                                </div>

                                <!-- STATION & ORDER TIME -->
                                <div class="d-flex justify-content-between small text-secondary mb-2">
                                    <span class="badge bg-dark border border-secondary text-uppercase"><?= e($o['station']) ?></span>
                                    <span class="mono-num text-light small"><i class="fas fa-clock me-1"></i><?= date('H:i', $ord_time) ?></span>
                                </div>

                                <!-- ITEM DETAILS -->
                                <div class="mt-2 mb-3">
                                    <div class="h4 fw-bolder text-warning mb-1">
                                        <span class="text-white"><?= (int) $o['quantity'] ?>x</span> <?= e($o['item_name']) ?>
                                    </div>
                                    <?php if (!empty($o['notes'])): ?>
                                        <div class="alert alert-warning text-dark py-1 px-2 my-2 small fw-bold rounded-3">
                                            <i class="fas fa-sticky-note me-1"></i><?= e($o['notes']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- BUMP ACTION BUTTON -->
                            <div class="pt-2 border-top border-secondary border-opacity-50">
                                <?php if ($o['status'] === 'new'): ?>
                                    <button class="btn btn-warning w-100 bump-btn shadow-sm" onclick="bumpStatus(<?= $o['id'] ?>, 'preparing')">
                                        <i class="fas fa-utensils me-2"></i>Hazırla
                                    </button>
                                <?php elseif ($o['status'] === 'preparing'): ?>
                                    <button class="btn btn-success w-100 bump-btn shadow-sm" onclick="bumpStatus(<?= $o['id'] ?>, 'ready')">
                                        <i class="fas fa-bell me-2"></i>Hazır! (Çan Çal)
                                    </button>
                                <?php elseif ($o['status'] === 'ready'): ?>
                                    <button class="btn btn-info w-100 bump-btn text-dark shadow-sm" onclick="bumpStatus(<?= $o['id'] ?>, 'served')">
                                        <i class="fas fa-check-double me-2"></i>Servis Edildi
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- TOAST -->
    <div id="kds-toast-container"></div>

    <script>
        let currentStation = <?= json_encode($station) ?>;
        let knownTicketIds = new Set(<?= json_encode(array_map(fn($o) => (int)$o['id'], $orders)) ?>);
        let audioCtx = null;

        // Live Clock
        setInterval(() => {
            const now = new Date();
            document.getElementById('kds-clock').innerText = now.toLocaleTimeString('tr-TR');
        }, 1000);

        // Web Audio Synthesizer Chime
        function playChime(freq = 587.33, duration = 0.4) {
            try {
                if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                if (audioCtx.state === 'suspended') audioCtx.resume();

                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();

                osc.type = 'triangle';
                osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(880, audioCtx.currentTime + duration);

                gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + duration);

                osc.connect(gain);
                gain.connect(audioCtx.destination);

                osc.start();
                osc.stop(audioCtx.currentTime + duration);
            } catch (e) {
                // Audio context block
            }
        }

        function enableAudioAndTest() {
            playChime(523.25, 0.5);
            setTimeout(() => playChime(659.25, 0.5), 150);
            setTimeout(() => playChime(783.99, 0.6), 300);
            showKdsToast('Ses bildirimi aktif ve test edildi!', 'success');
        }

        function showKdsToast(message, type = 'info') {
            const container = document.getElementById('kds-toast-container');
            const toastEl = document.createElement('div');
            toastEl.className = `alert alert-${type} shadow-lg rounded-4 py-2 px-3 mb-2 d-flex align-items-center gap-2 fade show`;
            toastEl.style.minWidth = '260px';
            toastEl.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
                <div class="small fw-bold">${message}</div>
            `;
            container.appendChild(toastEl);
            setTimeout(() => {
                toastEl.classList.remove('show');
                setTimeout(() => toastEl.remove(), 250);
            }, 3000);
        }

        function switchStation(st, btn) {
            currentStation = st;
            document.querySelectorAll('.btn-station-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            filterGrid();
        }

        function filterGrid() {
            const cards = document.querySelectorAll('.ticket-card-col');
            let visibleCount = 0;
            cards.forEach(c => {
                const st = c.getAttribute('data-station');
                if (currentStation === 'all' || st === currentStation) {
                    c.style.display = 'block';
                    visibleCount++;
                } else {
                    c.style.display = 'none';
                }
            });
            const emptyMsg = document.getElementById('empty-tickets-msg');
            if (emptyMsg) {
                emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        }

        // Bump Ticket Status
        async function bumpStatus(orderId, nextStatus) {
            try {
                const res = await fetch('<?= site_url('restaurant/api/kds_update') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, status: nextStatus })
                });
                if (!res.ok) {
                    showKdsToast('Durum güncellenirken sunucu hatası (' + res.status + ')', 'danger');
                    return;
                }
                const data = await res.json();
                if (data.status === 'success') {
                    if (nextStatus === 'ready') {
                        // Play double bell chime
                        playChime(880, 0.4);
                        setTimeout(() => playChime(1046.5, 0.4), 200);
                    }
                    pollKds(); // Refresh immediately
                } else {
                    showKdsToast('Hata: ' + (data.message || 'Durum güncellenemedi.'), 'danger');
                }
            } catch (err) {
                showKdsToast('Ağ hatası: ' + err.message, 'danger');
            }
        }

        // Live Polling every 4 seconds
        async function pollKds() {
            try {
                const res = await fetch(`<?= site_url('restaurant/api/kds_orders') ?>?station=${encodeURIComponent(currentStation)}`);
                if (!res.ok) return;
                const data = await res.json();

                if (data.status === 'success') {
                    const newIds = new Set();
                    let hasNewTicket = false;

                    data.orders.forEach(o => {
                        const id = parseInt(o.id);
                        newIds.add(id);
                        if (!knownTicketIds.has(id)) {
                            hasNewTicket = true;
                        }
                    });

                    // Sound alert on new ticket
                    if (hasNewTicket && document.getElementById('auto-sound-toggle').checked) {
                        playChime(523.25, 0.5);
                        setTimeout(() => playChime(659.25, 0.5), 180);
                    }

                    knownTicketIds = newIds;
                    renderTickets(data.orders);
                    
                    const countBadge = document.getElementById('count-all');
                    if (countBadge) countBadge.innerText = data.orders.length;
                }
            } catch (e) {
                // Silently handle
            }
        }

        function renderTickets(orders) {
            const container = document.getElementById('kds-tickets-grid');
            if (!container) return;

            if (orders.length === 0) {
                container.innerHTML = `
                    <div class="col-12 text-center py-5 text-secondary" id="empty-tickets-msg">
                        <i class="fas fa-check-circle fa-4x mb-3 text-success opacity-75"></i>
                        <h4 class="fw-bold text-light">Mutfak veya Barda Bekleyen Sipariş Yok</h4>
                        <p class="small text-secondary">Masalardan veya garson terminalinden yeni sipariş açıldığında burada anlık sesli belirecektir.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = orders.map(o => {
                const elapsed = o.elapsed_minutes || 0;
                const timerClass = o.urgency === 'critical' ? 'timer-badge-critical' : (o.urgency === 'warning' ? 'timer-badge-warning' : 'timer-badge-normal');
                const cardStateClass = 'kds-card-' + o.status;

                let btnHtml = '';
                if (o.status === 'new') {
                    btnHtml = `<button class="btn btn-warning w-100 bump-btn shadow-sm" onclick="bumpStatus(${o.id}, 'preparing')"><i class="fas fa-utensils me-2"></i>Hazırla</button>`;
                } else if (o.status === 'preparing') {
                    btnHtml = `<button class="btn btn-success w-100 bump-btn shadow-sm" onclick="bumpStatus(${o.id}, 'ready')"><i class="fas fa-bell me-2"></i>Hazır! (Çan Çal)</button>`;
                } else if (o.status === 'ready') {
                    btnHtml = `<button class="btn btn-info w-100 bump-btn text-dark shadow-sm" onclick="bumpStatus(${o.id}, 'served')"><i class="fas fa-check-double me-2"></i>Servis Edildi</button>`;
                }

                return `
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 ticket-card-col" data-station="${o.station}" data-id="${o.id}">
                        <div class="kds-card ${cardStateClass} p-3 h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-danger fs-5 px-3 py-1 fw-bolder rounded-pill">
                                        Masa: ${o.table_number || 'Paket'}
                                    </span>
                                    <span class="badge ${timerClass} fs-6 px-2 py-1 mono-num rounded-pill">
                                        <i class="fas fa-stopwatch me-1"></i>${elapsed} dk
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between small text-secondary mb-2">
                                    <span class="badge bg-dark border border-secondary text-uppercase">${o.station}</span>
                                    <span class="mono-num text-light small"><i class="fas fa-clock me-1"></i>${o.ordered_at ? o.ordered_at.substring(11, 16) : ''}</span>
                                </div>
                                <div class="mt-2 mb-3">
                                    <div class="h4 fw-bolder text-warning mb-1">
                                        <span class="text-white">${o.quantity}x</span> ${o.item_name}
                                    </div>
                                    ${o.notes ? `<div class="alert alert-warning text-dark py-1 px-2 my-2 small fw-bold rounded-3"><i class="fas fa-sticky-note me-1"></i>${o.notes}</div>` : ''}
                                </div>
                            </div>
                            <div class="pt-2 border-top border-secondary border-opacity-50">
                                ${btnHtml}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Fullscreen Toggle
        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }

        setInterval(pollKds, 4000);

        // =========================================================================
        // LIVE INTERCOM / CHAT (NutrixPOS Kitchen.vue Style)
        // =========================================================================
        async function loadIntercomChats() {
            try {
                const res = await fetch('<?= site_url('restaurant/api/kitchen_chats') ?>');
                const data = await res.json();
                if (data.status === 'success') {
                    renderIntercomStream(data.data || []);
                }
            } catch (err) {
                console.error('Chat error:', err);
            }
        }

        function renderIntercomStream(chats) {
            const stream = document.getElementById('kds-chat-stream');
            if (!stream) return;
            if (chats.length === 0) {
                stream.innerHTML = '<div class="text-center text-secondary py-4 small">Henüz telsiz mesajı yok.</div>';
                return;
            }

            const urgencyBadges = {
                normal: '<span class="badge bg-secondary">Bilgi</span>',
                urgent: '<span class="badge bg-danger">⚠️ Acil</span>',
                ready: '<span class="badge bg-success">🔔 Hazır</span>',
                stock_out: '<span class="badge bg-warning text-dark">🚫 Tükendi</span>'
            };

            stream.innerHTML = chats.map(c => `
                <div class="p-2 mb-2 rounded border border-secondary border-opacity-50" style="background: rgba(255,255,255,0.03);">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="small text-info"><i class="fas fa-user-circle me-1"></i>${c.sender_name}</strong>
                        <div>
                            ${urgencyBadges[c.urgency] || ''}
                            <span class="text-secondary small mono-num ms-1" style="font-size:10px;">${c.created_at ? c.created_at.substring(11,16) : ''}</span>
                        </div>
                    </div>
                    <div class="text-light small">${c.message}</div>
                </div>
            `).join('');
            stream.scrollTop = stream.scrollHeight;
        }

        async function sendQuickIntercom(message, urgency = 'normal', tableId = null) {
            await postIntercomMessage(message, urgency, tableId);
        }

        async function sendCustomIntercom() {
            const input = document.getElementById('kds-chat-input');
            const message = (input.value || '').trim();
            if (!message) return;
            input.value = '';
            await postIntercomMessage(message, 'normal', null);
        }

        async function postIntercomMessage(message, urgency, tableId) {
            try {
                const res = await fetch('<?= site_url('restaurant/api/kitchen_chats') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        sender_name: 'Mutfak',
                        sender_role: 'kitchen',
                        target_role: 'waiters',
                        message: message,
                        urgency: urgency,
                        table_id: tableId
                    })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showKdsToast('Telsiz mesajı iletildi: ' + message, 'success');
                    loadIntercomChats();
                }
            } catch (err) {
                showKdsToast('Telsiz hatası: ' + err.message, 'danger');
            }
        }

        setInterval(loadIntercomChats, 5000);
    </script>

    <!-- INTERCOM DRAWER (NutrixPOS Kitchen.vue Style) -->
    <div class="offcanvas offcanvas-end text-bg-dark border-start border-secondary" tabindex="-1" id="kdsIntercomDrawer" style="width: 380px;">
        <div class="offcanvas-header border-bottom border-secondary">
            <h5 class="offcanvas-title fw-bold">
                <i class="fas fa-broadcast-tower text-warning me-2"></i>Mutfak & Bar Telsizi
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column p-3">
            <!-- QUICK CHIP TEMPLATES -->
            <div class="mb-3">
                <small class="text-white-50 fw-bold d-block mb-1">HIZLI ÇAĞRI VE BİLDİRİMLER:</small>
                <div class="d-flex flex-wrap gap-1">
                    <button class="btn btn-sm btn-outline-success rounded-pill" onclick="sendQuickIntercom('Masa 1 Hazır, Servise Alınız!', 'ready', 1)">M1 Hazır</button>
                    <button class="btn btn-sm btn-outline-success rounded-pill" onclick="sendQuickIntercom('Masa 2 Hazır, Servise Alınız!', 'ready', 2)">M2 Hazır</button>
                    <button class="btn btn-sm btn-outline-success rounded-pill" onclick="sendQuickIntercom('Masa 3 Hazır, Servise Alınız!', 'ready', 3)">M3 Hazır</button>
                    <button class="btn btn-sm btn-outline-success rounded-pill" onclick="sendQuickIntercom('Masa 4 Hazır, Servise Alınız!', 'ready', 4)">M4 Hazır</button>
                    <button class="btn btn-sm btn-outline-danger rounded-pill" onclick="sendQuickIntercom('⚠️ Acil Garson Masaya!', 'urgent')">⚠️ Acil Garson</button>
                    <button class="btn btn-sm btn-outline-warning rounded-pill" onclick="sendQuickIntercom('🚫 Günün Balığı Tükendi!', 'stock_out')">🚫 Stok Bitti</button>
                    <button class="btn btn-sm btn-outline-info rounded-pill" onclick="sendQuickIntercom('🧊 Bara Acil Buz Lazım', 'normal')">🧊 Buz Takviyesi</button>
                </div>
            </div>

            <!-- CHAT MESSAGES STREAM -->
            <div class="flex-grow-1 overflow-auto p-2 rounded bg-black bg-opacity-50 border border-secondary mb-3" id="kds-chat-stream" style="max-height: calc(100vh - 280px);">
                <div class="text-center text-secondary py-4 small">Yükleniyor...</div>
            </div>

            <!-- CHAT INPUT -->
            <div class="input-group">
                <input type="text" id="kds-chat-input" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Telsiz mesajı yazın..." onkeyup="if(event.key==='Enter') sendCustomIntercom()">
                <button class="btn btn-warning btn-sm fw-bold" onclick="sendCustomIntercom()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</body>
</html>
