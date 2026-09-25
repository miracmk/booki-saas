<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @var array $orders
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?php $this->load->view('components/backend_head'); ?>
    <title>KDS — Mutfak & Bar Ekranı - BooKi</title>
    <style>
        .kds-card {
            border-radius: 12px;
            transition: transform 0.15s ease-in-out;
        }
        .kds-card:hover {
            transform: translateY(-2px);
        }
        .kds-status-new { border-left: 6px solid #dc3545; }
        .kds-status-preparing { border-left: 6px solid #ffc107; }
        .kds-status-ready { border-left: 6px solid #198754; }
    </style>
</head>
<body class="bg-dark text-white">
    <div class="container-fluid py-3 px-md-4">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary pb-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <a href="<?= site_url('restaurant') ?>" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Masa Planı
                </a>
                <h1 class="h4 fw-bold mb-0 text-warning">
                    <i class="fas fa-fire me-2"></i>KDS — Canlı Mutfak & Bar Sipariş Ekranı
                </h1>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary fs-6"><i class="fas fa-clock me-1"></i><span id="live-clock">--:--:--</span></span>
                <button class="btn btn-outline-warning btn-sm" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt me-1"></i> Yenile
                </button>
            </div>
        </div>

        <!-- STATION TABS -->
        <div class="d-flex gap-2 mb-4">
            <button class="btn btn-warning fw-semibold btn-station-filter active" data-station="all">Tüm İstasyonlar</button>
            <button class="btn btn-outline-light btn-station-filter" data-station="kitchen">Mutfak</button>
            <button class="btn btn-outline-light btn-station-filter" data-station="bar">Bar / İçecek</button>
            <button class="btn btn-outline-light btn-station-filter" data-station="grill">Izgara / Ocak</button>
        </div>

        <!-- ORDERS GRID -->
        <div class="row g-3" id="orders-container">
            <?php if (empty($orders)): ?>
                <div class="col-12 text-center py-5 text-muted">
                    <i class="fas fa-check-circle fa-4x mb-3 text-secondary"></i>
                    <h4>Şu anda bekleyen veya hazırlanan mutfak siparişi yok.</h4>
                    <p class="small">Yeni masa siparişleri açıldığında burada anlık olarak görünecektir.</p>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                    <div class="col-md-4 col-lg-3 order-card-wrapper" data-station="<?= e($o['station']) ?>">
                        <div class="card bg-secondary text-white shadow kds-card kds-status-<?= e($o['status']) ?> h-100">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-danger fs-6">Masa: <?= e($o['table_number'] ?: 'Paket') ?></span>
                                        <span class="badge bg-dark"><?= strtoupper(e($o['station'])) ?></span>
                                    </div>
                                    <h4 class="fw-bold text-warning mb-1">
                                        <?= e($o['quantity']) ?>x <?= e($o['item_name']) ?>
                                    </h4>
                                    <?php if (!empty($o['notes'])): ?>
                                        <div class="alert alert-warning py-1 px-2 mb-2 small text-dark">
                                            <i class="fas fa-sticky-note me-1"></i><?= e($o['notes']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <small class="text-white-50 d-block">
                                        <i class="fas fa-stopwatch me-1"></i>Sipariş: <?= date('H:i', strtotime($o['ordered_at'])) ?>
                                    </small>
                                </div>
                                <div class="mt-3 pt-2 border-top border-dark d-flex gap-2">
                                    <?php if ($o['status'] === 'new'): ?>
                                        <button class="btn btn-warning w-100 fw-bold btn-set-status" data-id="<?= $o['id'] ?>" data-status="preparing">
                                            <i class="fas fa-utensils me-1"></i> Hazırla
                                        </button>
                                    <?php elseif ($o['status'] === 'preparing'): ?>
                                        <button class="btn btn-success w-100 fw-bold btn-set-status" data-id="<?= $o['id'] ?>" data-status="ready">
                                            <i class="fas fa-bell me-1"></i> Hazır!
                                        </button>
                                    <?php elseif ($o['status'] === 'ready'): ?>
                                        <button class="btn btn-info w-100 fw-bold text-dark btn-set-status" data-id="<?= $o['id'] ?>" data-status="served">
                                            <i class="fas fa-check me-1"></i> Servis Edildi
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    setInterval(() => {
        const d = new Date();
        document.getElementById('live-clock').innerText = d.toLocaleTimeString('tr-TR');
    }, 1000);

    // Filter stations
    document.querySelectorAll('.btn-station-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.btn-station-filter').forEach(b => b.classList.remove('active', 'btn-warning'));
            document.querySelectorAll('.btn-station-filter').forEach(b => b.classList.add('btn-outline-light'));
            this.classList.remove('btn-outline-light');
            this.classList.add('active', 'btn-warning');

            const target = this.getAttribute('data-station');
            document.querySelectorAll('.order-card-wrapper').forEach(card => {
                if (target === 'all' || card.getAttribute('data-station') === target) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Update status
    document.querySelectorAll('.btn-set-status').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            try {
                const res = await fetch('<?= site_url('api/v1/verticals/restaurant/kds/update_status') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: id, status: status })
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

    // Auto refresh every 15s
    setTimeout(() => { window.location.reload(); }, 15000);
    </script>
</body>
</html>
