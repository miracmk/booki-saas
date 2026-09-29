<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oto Yıkama & Detailing - Sıram Nerede? TV Ekranı</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta http-equiv="refresh" content="30">
    <style>
        body { background-color: #0f172a; color: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .tv-header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-bottom: 2px solid #334155; }
        .queue-card { border-radius: 12px; transition: all 0.3s; }
        .plate-badge { font-size: 1.6rem; letter-spacing: 2px; font-weight: 800; font-family: monospace; background: #000; color: #fff; border: 2px solid #cbd5e1; border-radius: 6px; padding: 4px 14px; }
        .plate-tr { background-color: #1d4ed8; color: #fff; font-size: 0.9rem; padding: 2px 6px; border-radius: 3px; margin-right: 6px; }
    </style>
</head>
<body class="p-3">
    <div class="tv-header p-3 rounded-3 mb-4 d-flex justify-content-between align-items-center shadow">
        <div>
            <h1 class="h3 fw-bold mb-0 text-white"><i class="fas fa-car-wash text-info me-2"></i>Oto Yıkama & Detailing - Canlı Durum Panosu</h1>
            <span class="text-secondary small">Araç yıkama, bakım ve teslimat canlı takip ekranı</span>
        </div>
        <div class="text-end">
            <span class="badge bg-danger pulse me-2"><i class="fas fa-broadcast-tower me-1"></i>CANLI YAYIN</span>
            <span class="h4 fw-bold text-info" id="live-clock">--:--:--</span>
        </div>
    </div>

    <div class="row g-4">
        <!-- YIKAMADA / İŞLEMDE (IN PROGRESS) -->
        <div class="col-md-6">
            <div class="card bg-dark border-primary shadow-lg h-100">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 fw-bold"><i class="fas fa-soap me-2"></i>Yıkamada / İşlemde Olanlar</h4>
                    <span class="badge bg-light text-primary fs-6"><?= count($in_progress ?? []) ?> Araç</span>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($in_progress)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-car fa-3x mb-3 text-secondary"></i>
                            <h5>Şu anda işlemde olan araç bulunmuyor.</h5>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($in_progress as $car): ?>
                                <div class="queue-card p-3 bg-secondary bg-opacity-25 border border-secondary rounded-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="d-inline-flex align-items-center plate-badge mb-2">
                                            <span class="plate-tr">TR</span><?= html_escape($car['plate_number'] ?? '34 ABC 123') ?>
                                        </div>
                                        <div class="text-white-50"><?= html_escape($car['service_name'] ?? 'İç-Dış Detaylı Yıkama') ?></div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-warning text-dark fs-6 px-3 py-2"><i class="fas fa-spinner fa-spin me-1"></i>Yıkanıyor</span>
                                        <div class="small text-muted mt-1">Peron: <?= html_escape($car['station_name'] ?? 'Peron 1') ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- HAZIR / TESLİME HAZIR (READY) -->
        <div class="col-md-6">
            <div class="card bg-dark border-success shadow-lg h-100">
                <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 fw-bold"><i class="fas fa-check-circle me-2"></i>Hazır / Teslim Bekleyenler</h4>
                    <span class="badge bg-light text-success fs-6"><?= count($ready ?? []) ?> Araç</span>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($ready)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-clipboard-check fa-3x mb-3 text-secondary"></i>
                            <h5>Teslim edilmeyi bekleyen hazır araç bulunmuyor.</h5>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($ready as $car): ?>
                                <div class="queue-card p-3 bg-success bg-opacity-10 border border-success rounded-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="d-inline-flex align-items-center plate-badge mb-2">
                                            <span class="plate-tr">TR</span><?= html_escape($car['plate_number'] ?? '34 XYZ 789') ?>
                                        </div>
                                        <div class="text-white-50"><?= html_escape($car['brand'] ?? '') ?> <?= html_escape($car['model'] ?? '') ?></div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success fs-6 px-3 py-2"><i class="fas fa-key me-1"></i>ARACINIZ HAZIR</span>
                                        <div class="small text-white-50 mt-1">Lütfen Danışmaya Başvurunuz</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateClock() {
            var now = new Date();
            var h = String(now.getHours()).padStart(2, '0');
            var m = String(now.getMinutes()).padStart(2, '0');
            var s = String(now.getSeconds()).padStart(2, '0');
            var clockEl = document.getElementById('live-clock');
            if (clockEl) clockEl.textContent = h + ':' + m + ':' + s;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>
