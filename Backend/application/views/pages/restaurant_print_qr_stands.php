<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * BooKi - Printable Table QR Stands & Tents
 * Print-ready stylish table standees with direct QR links to menu & self-order
 *
 * @var array $tables
 * @var string $company_name
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Masa QR Kodları Yazdır — <?= e($company_name) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
        }
        .qr-card {
            background: #ffffff;
            border-radius: 24px;
            border: 2px dashed #cbd5e1;
            padding: 32px 24px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            page-break-inside: avoid;
            margin-bottom: 24px;
        }
        .qr-frame {
            background: #f8fafc;
            padding: 16px;
            border-radius: 20px;
            display: inline-block;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff;
            }
            .qr-card {
                box-shadow: none;
                border: 2px solid #000000;
            }
        }
    </style>
</head>
<body class="p-4">

    <!-- NO-PRINT TOOLBAR -->
    <div class="no-print d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded-4 shadow-sm border">
        <div>
            <h5 class="fw-bold mb-0"><i class="fas fa-qrcode text-primary me-2"></i>Masa QR Kodları & Masa Kartları</h5>
            <small class="text-muted">Masalara koyabileceğiniz yüksek çözünürlüklü dijital menü ve sipariş QR kartları.</small>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('restaurant') ?>" class="btn btn-outline-dark">
                <i class="fas fa-arrow-left me-1"></i> Masa Planı
            </a>
            <button class="btn btn-primary fw-bold px-4" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Yazdır / PDF Kaydet
            </button>
        </div>
    </div>

    <!-- QR STAND CARDS CONTAINER -->
    <div class="container">
        <div class="row g-4">
            <?php foreach ($tables as $t): ?>
                <?php
                $menu_url = site_url('restaurant/menu/' . ($t['qr_token'] ?: $t['id']));
                $qr_api_url = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($menu_url);
                ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="qr-card">
                        <div class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-2">
                            <?= e($t['section']) ?>
                        </div>
                        <h2 class="display-6 fw-bolder mb-1">MASA <?= e($t['table_number']) ?></h2>
                        <p class="text-muted small mb-3"><?= e($company_name) ?></p>

                        <div class="qr-frame mb-3">
                            <img src="<?= $qr_api_url ?>" alt="QR Kod Masa <?= e($t['table_number']) ?>" width="180" height="180">
                        </div>

                        <h6 class="fw-bold text-dark mb-1">
                            <i class="fas fa-mobile-alt text-primary me-1"></i> Kameranızla Okutun
                        </h6>
                        <p class="small text-muted mb-0" style="font-size: 11px;">
                            Temassız Dijital Menü &bull; Masadan Sipariş &bull; Sadakat Puanı & Garson Çağır
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</body>
</html>
