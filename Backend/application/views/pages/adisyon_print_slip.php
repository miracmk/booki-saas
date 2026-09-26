<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Adisyon Fişi — #<?= e($adisyon['adisyon_number']) ?></title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        body {
            width: 78mm;
            margin: 0 auto;
            padding: 8px 4px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 10px; text-align: center;">
        <button onclick="window.print()" style="padding: 6px 12px; font-weight: bold;">YAZDIR</button>
    </div>

    <div class="text-center bold" style="font-size: 15px;"><?= e($company_name) ?></div>
    <div class="text-center" style="font-size: 11px;">HESAP & ADİSYON FİŞİ</div>
    <div class="divider"></div>

    <div class="row"><span>Adisyon No:</span><span class="bold"><?= e($adisyon['adisyon_number']) ?></span></div>
    <div class="row"><span>Tarih:</span><span><?= date('d.m.Y H:i', strtotime($adisyon['opened_at'])) ?></span></div>
    <?php if (!empty($adisyon['customer_first_name'])): ?>
        <div class="row"><span>Müşteri:</span><span><?= e($adisyon['customer_first_name'] . ' ' . $adisyon['customer_last_name']) ?></span></div>
    <?php elseif (!empty($adisyon['table_number'])): ?>
        <div class="row"><span>Masa:</span><span class="bold">Masa <?= e($adisyon['table_number']) ?></span></div>
    <?php endif; ?>
    <?php if (!empty($adisyon['staff_first_name'])): ?>
        <div class="row"><span>Personel:</span><span><?= e($adisyon['staff_first_name']) ?></span></div>
    <?php endif; ?>

    <div class="divider"></div>
    <div class="bold row">
        <span style="flex: 2;">Kalem</span>
        <span style="flex: 1; text-align: center;">Ad.</span>
        <span style="flex: 1;" class="text-right">Tutar</span>
    </div>
    <div class="divider"></div>

    <?php foreach ($adisyon['items'] as $it): ?>
        <div class="row">
            <span style="flex: 2;"><?= e($it['name']) ?></span>
            <span style="flex: 1; text-align: center;"><?= (float) $it['quantity'] ?></span>
            <span style="flex: 1;" class="text-right"><?= number_format($it['total_amount'], 2) ?></span>
        </div>
    <?php endforeach; ?>

    <div class="divider"></div>
    <div class="row"><span>Ara Toplam:</span><span class="text-right"><?= number_format($adisyon['subtotal'], 2) ?> ₺</span></div>
    <div class="row"><span>KDV:</span><span class="text-right"><?= number_format($adisyon['tax_amount'], 2) ?> ₺</span></div>
    <div class="row bold" style="font-size: 14px;"><span>TOPLAM:</span><span class="text-right"><?= number_format($adisyon['total_amount'], 2) ?> ₺</span></div>
    <div class="row"><span>Tahsil Edilen:</span><span class="text-right"><?= number_format($adisyon['paid_amount'], 2) ?> ₺</span></div>
    <div class="divider"></div>

    <div class="text-center" style="font-size: 10px; margin-top: 10px;">
        Bizi tercih ettiğiniz için teşekkür ederiz!
    </div>
</body>
</html>
