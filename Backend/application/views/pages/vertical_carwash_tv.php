<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @var array $queue
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Canlı Peron & Yıkama Takip Ekranı - BooKi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #0b0f19;
            color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            overflow-x: hidden;
        }
        .tv-header {
            background: linear-gradient(90deg, #1e293b 0%, #0f172a 100%);
            border-bottom: 2px solid #3b82f6;
        }
        .plate-badge {
            background-color: #f8fafc;
            color: #0f172a;
            font-family: 'Courier New', Courier, monospace;
            font-weight: 800;
            font-size: 1.6rem;
            letter-spacing: 2px;
            padding: 6px 16px;
            border-radius: 8px;
            border: 2px solid #334155;
            display: inline-block;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }
        .status-card {
            border-radius: 12px;
            background-color: #1e293b;
            border: 1px solid #334155;
            transition: transform 0.2s ease;
        }
        .status-ready {
            border: 2px solid #10b981;
            background: linear-gradient(180deg, rgba(16, 185, 129, 0.15) 0%, #1e293b 100%);
        }
        .status-washing {
            border: 2px solid #3b82f6;
            background: linear-gradient(180deg, rgba(59, 130, 246, 0.15) 0%, #1e293b 100%);
        }
        .status-detailing {
            border: 2px solid #f59e0b;
            background: linear-gradient(180deg, rgba(245, 158, 11, 0.15) 0%, #1e293b 100%);
        }
        .live-pulse {
            animation: pulse-animation 1.5s infinite;
        }
        @keyframes pulse-animation {
            0% { opacity: 1; }
            50% { opacity: 0.4; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- TOP TV BAR -->
    <div class="tv-header px-4 py-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <span class="fs-2">🚗</span>
            <div>
                <h2 class="h4 fw-bold mb-0 text-white">Canlı Araç Yıkama & Detailing Peron Panosu</h2>
                <small class="text-white-50"><i class="fas fa-circle text-success me-1 live-pulse"></i> Canlı İstasyon Durumu & Bekleme Süreleri</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-4">
            <div class="text-end">
                <div class="fs-4 fw-bold font-monospace" id="clock-display">00:00:00</div>
                <small class="text-white-50"><?= date('d.m.Y') ?></small>
            </div>
            <a href="<?= site_url('verticals/automotive') ?>" class="btn btn-outline-secondary btn-sm text-white-50">
                <i class="fas fa-times me-1"></i> Çıkış
            </a>
        </div>
    </div>

    <!-- MAIN QUEUE GRID -->
    <div class="container-fluid py-4 px-4">
        <div class="row g-4" id="queue-cards-container">
            <?php if (empty($queue)): ?>
                <div class="col-12 text-center py-5 text-white-50">
                    <i class="fas fa-car fs-1 d-block mb-3 text-secondary"></i>
                    <h3>Şu anda peronlarda aktif araç bulunmuyor.</h3>
                    <p>Yeni araç girişi olduğunda ekran otomatik olarak güncellenecektir.</p>
                </div>
            <?php else: ?>
                <?php foreach ($queue as $q): ?>
                    <?php 
                        $statusClass = 'status-washing';
                        $statusText = 'Yıkamada';
                        $statusIcon = 'fas fa-shower text-primary';
                        if ($q['queue_status'] === 'ready') {
                            $statusClass = 'status-ready';
                            $statusText = 'TESLİME HAZIR';
                            $statusIcon = 'fas fa-check-circle text-success';
                        } elseif ($q['queue_status'] === 'detailing') {
                            $statusClass = 'status-detailing';
                            $statusText = 'Detaylı Kurulama & Ozon';
                            $statusIcon = 'fas fa-spray-can text-warning';
                        } elseif ($q['queue_status'] === 'waiting') {
                            $statusClass = 'status-card';
                            $statusText = 'Sırada Bekliyor';
                            $statusIcon = 'fas fa-clock text-secondary';
                        }
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card status-card <?= $statusClass ?> p-3 shadow-lg">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-dark border text-light px-3 py-2 fs-6">
                                    <i class="fas fa-warehouse me-1 text-primary"></i> <?= e($q['bay_name']) ?>
                                </span>
                                <span class="fw-bold fs-6">
                                    <i class="<?= $statusIcon ?> me-1"></i> <?= $statusText ?>
                                </span>
                            </div>

                            <div class="text-center my-2">
                                <div class="plate-badge"><?= e($q['plate_number']) ?></div>
                                <div class="mt-2 text-white-50 small">
                                    <?= e($q['brand']) ?> <?= e($q['model']) ?> (<?= e(strtoupper($q['vehicle_segment'] ?? 'SEDAN')) ?>)
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-3 mt-3 border-top border-secondary">
                                <div class="small">
                                    <span class="text-white-50 d-block">Uygulama</span>
                                    <strong class="text-light"><?= e($q['service_name'] ?: 'Standart Yıkama') ?></strong>
                                </div>
                                <div class="text-end">
                                    <span class="text-white-50 d-block small">Tahmini Teslim</span>
                                    <span class="fw-bold fs-5 text-warning">
                                        <?= !empty($q['estimated_ready_at']) ? date('H:i', strtotime($q['estimated_ready_at'])) : '-' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // LIVE CLOCK
        setInterval(() => {
            const now = new Date();
            document.getElementById('clock-display').innerText = now.toLocaleTimeString('tr-TR');
        }, 1000);

        // AUTO REFRESH QUEUE EVERY 15 SECONDS
        setTimeout(() => {
            location.reload();
        }, 15000);
    </script>
</body>
</html>
