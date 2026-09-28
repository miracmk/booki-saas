<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * BooKi - Customer Screen & Live Dining Portal
 * Real-Time Order Tracking, Digital Bill, Loyalty Wallet & Interactive Table Service
 *
 * @var array $table
 * @var string|null $table_token
 * @var array|null $experience
 * @var string $company_name
 */
$adisyon = $experience['adisyon'] ?? null;
$kitchen_orders = $experience['kitchen_orders'] ?? [];
$customer = $experience['customer'] ?? null;
$membership = $experience['membership'] ?? null;
$pending_call = $experience['pending_call'] ?? null;
$minutes_dining = $experience['minutes_dining'] ?? 0;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= e($company_name) ?> — Masa <?= e($table['table_number'] ?? '1') ?> Canlı Sipariş & Hesap</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #f97316;
            --brand-primary-dark: #ea580c;
            --brand-dark: #0f172a;
            --brand-bg: #f8fafc;
            --brand-card: #ffffff;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--brand-bg);
            color: #1e293b;
            padding-bottom: 95px;
        }
        .live-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            border-bottom-left-radius: 28px;
            border-bottom-right-radius: 28px;
            padding: 24px 20px 20px 20px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15);
        }
        .stepper-item {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }
        .stepper-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            color: #64748b;
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 6px;
            z-index: 2;
            transition: all 0.3s;
        }
        .stepper-circle.active {
            background: var(--brand-primary);
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.25);
            animation: pulse-ring 2s infinite;
        }
        .stepper-circle.completed {
            background: #10b981;
            color: #ffffff;
        }
        .stepper-line {
            position: absolute;
            top: 19px;
            left: 50%;
            width: 100%;
            height: 3px;
            background: #e2e8f0;
            z-index: 1;
        }
        .stepper-line.completed {
            background: #10b981;
        }
        .stepper-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-align: center;
        }
        .stepper-label.active {
            color: var(--brand-primary);
            font-weight: 700;
        }
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(249, 115, 22, 0); }
            100% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0); }
        }
        .status-badge-new { background-color: #f1f5f9; color: #475569; }
        .status-badge-preparing { background-color: #fef3c7; color: #b45309; }
        .status-badge-ready { background-color: #dcfce7; color: #15803d; }
        .status-badge-served { background-color: #e0f2fe; color: #0369a1; }

        .floating-footer-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-top: 1px solid #e2e8f0;
            padding: 10px 16px;
            z-index: 1050;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>
<body>

    <!-- TOP HEADER -->
    <header class="live-header">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <a href="<?= site_url('restaurant/menu/' . e($table['qr_token'] ?: ($table['id'] ?? '1'))) ?>" class="text-white text-decoration-none small">
                    <i class="fas fa-arrow-left me-1"></i> Menüye Dön
                </a>
                <h1 class="h4 fw-bold mb-0 mt-1"><?= e($company_name) ?></h1>
            </div>
            <div class="text-end">
                <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill shadow-sm">
                    Masa <?= e($table['table_number'] ?? '1') ?>
                </span>
                <div class="small text-white-50 mt-1">
                    <i class="fas fa-stopwatch me-1"></i><?= $minutes_dining ?> dakikadır masadasınız
                </div>
            </div>
        </div>

        <!-- PENDING WAITER CALL ALERT IF ANY -->
        <div id="waiter-call-alert" class="alert alert-warning py-2 px-3 mt-3 mb-0 rounded-4 d-flex align-items-center justify-content-between <?= $pending_call ? '' : 'd-none' ?>">
            <div class="d-flex align-items-center gap-2 small">
                <i class="fas fa-bell fa-spin text-dark"></i>
                <div>
                    <strong>Talebiniz İletildi:</strong> 
                    <span id="call-note-text"><?= e($pending_call['note'] ?? 'Garsonunuz masanıza yönlendirildi.') ?></span>
                </div>
            </div>
            <span class="badge bg-dark">Bekleniyor</span>
        </div>
    </header>

    <main class="container py-3">

        <!-- OVERALL PROGRESS TRACKER (STEPPER) -->
        <div class="card border-0 rounded-4 shadow-sm p-3 mb-3 bg-white">
            <h6 class="fw-bold mb-3 text-dark d-flex align-items-center justify-content-between">
                <span><i class="fas fa-fire text-warning me-2"></i>Mutfak & Servis Durumu</span>
                <span class="badge bg-success bg-opacity-10 text-success fw-bold" id="live-refresh-badge">
                    <i class="fas fa-circle text-success me-1" style="font-size: 8px;"></i> Canlı
                </span>
            </h6>

            <?php
            // Determine active step
            $has_served = false;
            $has_ready = false;
            $has_prep = false;
            $has_new = false;

            foreach ($kitchen_orders as $ko) {
                if ($ko['status'] === 'served') $has_served = true;
                if ($ko['status'] === 'ready') $has_ready = true;
                if ($ko['status'] === 'preparing') $has_prep = true;
                if ($ko['status'] === 'new') $has_new = true;
            }

            $step = 1;
            if ($has_ready) $step = 3;
            elseif ($has_prep) $step = 2;
            elseif ($has_served && !$has_new && !$has_prep && !$has_ready) $step = 4;
            ?>

            <div class="d-flex justify-content-between position-relative py-2">
                <!-- STEP 1 -->
                <div class="stepper-item">
                    <div class="stepper-circle <?= $step >= 1 ? ($step === 1 ? 'active' : 'completed') : '' ?>">
                        <i class="fas <?= $step > 1 ? 'fa-check' : 'fa-receipt' ?>"></i>
                    </div>
                    <span class="stepper-label <?= $step === 1 ? 'active' : '' ?>">Alındı</span>
                    <div class="stepper-line <?= $step > 1 ? 'completed' : '' ?>"></div>
                </div>

                <!-- STEP 2 -->
                <div class="stepper-item">
                    <div class="stepper-circle <?= $step >= 2 ? ($step === 2 ? 'active' : 'completed') : '' ?>">
                        <i class="fas <?= $step > 2 ? 'fa-check' : 'fa-utensils' ?>"></i>
                    </div>
                    <span class="stepper-label <?= $step === 2 ? 'active' : '' ?>">Hazırlanıyor</span>
                    <div class="stepper-line <?= $step > 2 ? 'completed' : '' ?>"></div>
                </div>

                <!-- STEP 3 -->
                <div class="stepper-item">
                    <div class="stepper-circle <?= $step >= 3 ? ($step === 3 ? 'active' : 'completed') : '' ?>">
                        <i class="fas <?= $step > 3 ? 'fa-check' : 'fa-bell' ?>"></i>
                    </div>
                    <span class="stepper-label <?= $step === 3 ? 'active' : '' ?>">Masaya Geliyor</span>
                    <div class="stepper-line <?= $step > 3 ? 'completed' : '' ?>"></div>
                </div>

                <!-- STEP 4 -->
                <div class="stepper-item">
                    <div class="stepper-circle <?= $step === 4 ? 'completed' : '' ?>">
                        <i class="fas fa-smile"></i>
                    </div>
                    <span class="stepper-label <?= $step === 4 ? 'active' : '' ?>">Servis Edildi</span>
                </div>
            </div>
        </div>

        <!-- ACTIVE KITCHEN DISPATCHED ITEMS -->
        <div class="card border-0 rounded-4 shadow-sm p-3 mb-3 bg-white">
            <h6 class="fw-bold mb-2 text-dark"><i class="fas fa-list-check text-primary me-2"></i>Masa Sipariş Kalemleri</h6>
            <div id="kitchen-orders-list">
                <?php if (empty($kitchen_orders)): ?>
                    <p class="text-muted small mb-0 py-2">Henüz mutfağa iletilen bir sipariş bulunmuyor.</p>
                <?php else: ?>
                    <?php foreach ($kitchen_orders as $ko): ?>
                        <?php
                        $badge_class = 'status-badge-' . e($ko['status']);
                        $status_label = [
                            'new' => 'Alındı',
                            'preparing' => 'Hazırlanıyor',
                            'ready' => 'Hazır / Masaya Geliyor',
                            'served' => 'Servis Edildi',
                            'cancelled' => 'İptal',
                        ][$ko['status']] ?? $ko['status'];
                        ?>
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                            <div>
                                <div class="fw-bold text-dark">
                                    <span class="badge bg-secondary me-1"><?= (int) $ko['quantity'] ?>x</span>
                                    <?= e($ko['item_name']) ?>
                                </div>
                                <?php if (!empty($ko['notes'])): ?>
                                    <small class="text-muted fst-italic"><i class="fas fa-pen me-1"></i><?= e($ko['notes']) ?></small>
                                <?php endif; ?>
                            </div>
                            <span class="badge <?= $badge_class ?> px-2 py-1 rounded-pill small">
                                <?= $status_label ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- LOYALTY POINTS & MEMBERSHIP CARD -->
        <div class="card border-0 rounded-4 shadow-sm p-3 mb-3 bg-gradient text-white" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small text-white-50"><i class="fas fa-gem text-warning me-1"></i>Sadakat Cüzdanı</span>
                <?php if ($membership): ?>
                    <span class="badge bg-success"><?= e($membership['plan_name']) ?></span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">Ücretsiz Katılım</span>
                <?php endif; ?>
            </div>
            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <div class="h3 fw-bolder text-warning mb-0" id="loyalty-balance-text">
                        <?= (int) ($customer['loyalty_balance'] ?? 0) ?> <span class="fs-6 text-white-50">Puan</span>
                    </div>
                    <small class="text-white-50">
                        Bu yemekten kazanılacak: <strong class="text-warning">+<?= (int) ($adisyon['loyalty_points_earned'] ?? 0) ?> Puan</strong>
                    </small>
                </div>
                <?php if (!empty($customer) && (int) ($customer['loyalty_balance'] ?? 0) >= 100 && $adisyon && (float) $adisyon['total_amount'] > 0): ?>
                    <button class="btn btn-warning btn-sm rounded-pill fw-bold" onclick="redeemPointsModal()">
                        <i class="fas fa-gift me-1"></i> Puan Kullan
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- DIGITAL ITEMIZED ADISYON BILL -->
        <?php if ($adisyon): ?>
            <div class="card border-0 rounded-4 shadow-sm p-3 mb-3 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-receipt text-primary me-2"></i>Dijital Adisyon</h6>
                        <small class="text-muted">No: <?= e($adisyon['adisyon_number']) ?></small>
                    </div>
                    <span class="badge <?= $adisyon['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                        <?= $adisyon['payment_status'] === 'paid' ? 'Ödendi' : 'Açık / Ödenmedi' ?>
                    </span>
                </div>

                <!-- ITEMS LIST -->
                <div class="mb-3">
                    <?php foreach ($adisyon['items'] as $item): ?>
                        <div class="d-flex justify-content-between py-1 small">
                            <span><?= (float) $item['quantity'] ?>x <?= e($item['name']) ?></span>
                            <span class="fw-semibold"><?= number_format((float) $item['total_amount'], 2) ?> ₺</span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- TOTALS -->
                <div class="border-top pt-2 small text-muted">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Ara Toplam</span>
                        <span><?= number_format((float) $adisyon['subtotal'], 2) ?> ₺</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>KDV</span>
                        <span><?= number_format((float) $adisyon['tax_amount'], 2) ?> ₺</span>
                    </div>
                    <?php if ((float) $adisyon['membership_discount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-1 text-success fw-semibold">
                            <span>Üyelik İndirimi</span>
                            <span>-<?= number_format((float) $adisyon['membership_discount'], 2) ?> ₺</span>
                        </div>
                    <?php endif; ?>
                    <?php if ((float) $adisyon['loyalty_discount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-1 text-warning fw-semibold">
                            <span>Sadakat Puanı İndirimi</span>
                            <span>-<?= number_format((float) $adisyon['loyalty_discount'], 2) ?> ₺</span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between fs-5 fw-bolder text-dark pt-2 border-top">
                        <span>Ödenecek Toplam</span>
                        <span class="text-primary"><?= number_format((float) $adisyon['total_amount'], 2) ?> ₺</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- DINING FEEDBACK & RATING WIDGET -->
        <div class="card border-0 rounded-4 shadow-sm p-3 mb-4 bg-white text-center">
            <h6 class="fw-bold mb-1 text-dark">Deneyiminizi Değerlendirin</h6>
            <p class="small text-muted mb-2">Yemek ve servis kalitemiz nasıldı?</p>
            <div class="d-flex justify-content-center gap-2 mb-2" id="star-rating-container">
                <i class="fas fa-star fs-3 text-warning cursor-pointer" onclick="setRating(1)"></i>
                <i class="fas fa-star fs-3 text-warning cursor-pointer" onclick="setRating(2)"></i>
                <i class="fas fa-star fs-3 text-warning cursor-pointer" onclick="setRating(3)"></i>
                <i class="fas fa-star fs-3 text-warning cursor-pointer" onclick="setRating(4)"></i>
                <i class="fas fa-star fs-3 text-warning cursor-pointer" onclick="setRating(5)"></i>
            </div>
            <button class="btn btn-sm btn-outline-dark rounded-pill px-3 mx-auto" onclick="submitFeedback()">
                <i class="fas fa-comment-dots me-1"></i> Görüşünüzü İletin
            </button>
        </div>

    </main>

    <!-- FLOATING ACTION NAVIGATION -->
    <nav class="floating-footer-nav">
        <div class="container d-flex justify-content-between align-items-center gap-2">
            <button class="btn btn-outline-dark rounded-pill flex-grow-1 py-2 fw-semibold" onclick="triggerCall('waiter')">
                <i class="fas fa-bell text-warning me-1"></i> Garson Çağır
            </button>
            <button class="btn btn-outline-info rounded-pill flex-grow-1 py-2 fw-semibold" onclick="triggerCall('bill')">
                <i class="fas fa-receipt text-primary me-1"></i> Hesap İste
            </button>
            <a href="<?= site_url('restaurant/menu/' . e($table['qr_token'] ?: ($table['id'] ?? '1'))) ?>" class="btn btn-primary rounded-pill flex-grow-1 py-2 fw-bold">
                <i class="fas fa-plus me-1"></i> Sipariş Ekle
            </a>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const tableToken = <?= json_encode($table['qr_token'] ?? $table['id'] ?? '1') ?>;

        // Auto polling every 5 seconds for live status
        setInterval(async () => {
            try {
                const res = await fetch(`<?= site_url('restaurant/api/table_experience') ?>?table_token=${encodeURIComponent(tableToken)}`);
                const data = await res.json();
                if (data.status === 'success' && data.data) {
                    const d = data.data;
                    if (d.pending_call) {
                        document.getElementById('waiter-call-alert').classList.remove('d-none');
                        document.getElementById('call-note-text').innerText = d.pending_call.note || 'Garson masanıza yönlendirildi.';
                    } else {
                        document.getElementById('waiter-call-alert').classList.add('d-none');
                    }
                }
            } catch (e) {
                // Silently handle
            }
        }, 5000);

        async function triggerCall(type) {
            const note = type === 'bill' ? prompt('Ödeme yöntemi tercihiniz (Nakit / Kredi Kartı / Sadakat Puanı):', 'Kredi Kartı') : prompt('Garsona iletmek istediğiniz özel bir istek var mı?', '');
            if (note === null) return;

            try {
                const res = await fetch('<?= site_url('restaurant/api/call_waiter') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        table_token: tableToken,
                        call_type: type,
                        note: note
                    })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    alert(data.message || 'Talebiniz personele iletildi.');
                    document.getElementById('waiter-call-alert').classList.remove('d-none');
                    document.getElementById('call-note-text').innerText = note || 'Garson masanıza yönlendirildi.';
                } else {
                    alert('Hata: ' + (data.message || 'İşlem başarısız.'));
                }
            } catch (err) {
                alert('Bağlantı hatası: ' + err.message);
            }
        }

        async function redeemPointsModal() {
            const adisyonId = <?= (int) ($adisyon['id'] ?? 0) ?>;
            if (!adisyonId) return;

            const pts = prompt('Kaç sadakat puanı kullanmak istiyorsunuz? (100 Puan = 10 TL):', '100');
            if (!pts || isNaN(pts) || parseInt(pts) <= 0) return;

            try {
                const res = await fetch('<?= site_url('restaurant/api/redeem_loyalty') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        adisyon_id: adisyonId,
                        points: parseInt(pts)
                    })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    alert(`${data.points_redeemed} puan kullanıldı! Hesabınıza ${data.discount_applied} ₺ indirim uygulandı.`);
                    window.location.reload();
                } else {
                    alert('Hata: ' + (data.message || 'Puan kullanılamadı.'));
                }
            } catch (err) {
                alert('Ağ hatası: ' + err.message);
            }
        }

        let selectedRating = 5;
        function setRating(r) {
            selectedRating = r;
            const stars = document.querySelectorAll('#star-rating-container i');
            stars.forEach((s, idx) => {
                if (idx < r) {
                    s.classList.remove('far');
                    s.classList.add('fas');
                } else {
                    s.classList.remove('fas');
                    s.classList.add('far');
                }
            });
        }

        function submitFeedback() {
            const comment = prompt('Eklemek istediğiniz bir yorumunuz var mı?', '');
            if (comment !== null) {
                alert('Geri bildiriminiz için teşekkür ederiz! Şefimize ve ekibimize iletilmiştir.');
            }
        }
    </script>
</body>
</html>
