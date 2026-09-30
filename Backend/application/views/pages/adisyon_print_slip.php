<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Adisyon & Termal Fiş Yazdırma Sayfası (80mm, 58mm & Özel Boyut)
 * ESC/POS Termal ve Standart Yazıcı Uyumlu
 * 
 * @var array $adisyon
 * @var string $company_name
 * @var string|null $company_phone
 * @var string|null $company_address
 * @var string $format
 * @var string $width
 */
$format = $format ?? '80mm';
$width_num = (int) ($width ?? ($format === '58mm' ? 58 : 80));
if ($width_num <= 0) $width_num = 80;
$paper_width_px = $width_num === 58 ? '260px' : ($width_num === 80 ? '340px' : ($width_num * 4.2) . 'px');
$customer_name = !empty($adisyon['customer_first_name']) 
    ? trim($adisyon['customer_first_name'] . ' ' . ($adisyon['customer_last_name'] ?? ''))
    : (!empty($adisyon['table_number']) ? 'Masa ' . $adisyon['table_number'] : 'Misafir Müşteri');
$staff_name = !empty($adisyon['staff_first_name']) 
    ? trim($adisyon['staff_first_name'] . ' ' . ($adisyon['staff_last_name'] ?? ''))
    : 'Kasiyer';
$remaining = max(0.00, round((float)($adisyon['total_amount'] ?? 0) - (float)($adisyon['paid_amount'] ?? 0), 2));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiş #<?= e($adisyon['adisyon_number'] ?? $adisyon['id']) ?> - <?= e($company_name) ?></title>
    <link href="<?= base_url('assets/vendor/fontawesome/css/all.min.css') ?>" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace, 'DejaVu Sans Mono', monospace;
            background-color: #f1f3f5;
            color: #000;
            font-size: <?= $width_num === 58 ? '11px' : '12px' ?>;
            line-height: 1.35;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 10px;
        }
        .no-print-bar {
            width: 100%;
            max-width: 480px;
            background: #212529;
            color: #fff;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .no-print-bar .btn {
            background: #0d6efd;
            color: #fff;
            border: none;
            padding: 6px 14px;
            font-size: 13px;
            font-family: sans-serif;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
        }
        .no-print-bar .btn:hover {
            background: #0b5ed7;
        }
        .no-print-bar .btn-secondary {
            background: #6c757d;
        }
        .no-print-bar .btn-secondary:hover {
            background: #5c636a;
        }
        .format-select {
            padding: 5px 8px;
            border-radius: 4px;
            border: 1px solid #495057;
            background: #343a40;
            color: #fff;
            font-size: 12px;
            font-family: sans-serif;
        }
        /* RECEIPT PAPER */
        .receipt-paper {
            width: <?= $paper_width_px ?>;
            background: #fff;
            padding: <?= $width_num === 58 ? '12px 10px' : '18px 14px' ?>;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #dee2e6;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .company-title {
            font-size: <?= $width_num === 58 ? '14px' : '16px' ?>;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .subtitle {
            font-size: 10px;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .company-info {
            font-size: 10px;
            color: #555;
            margin-bottom: 6px;
            line-height: 1.25;
        }
        .dashed-line {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .solid-line {
            border-top: 1px solid #000;
            margin: 8px 0;
        }
        .double-line {
            border-top: 2px dashed #000;
            margin: 8px 0;
        }
        .meta-table {
            width: 100%;
            font-size: <?= $width_num === 58 ? '10px' : '11px' ?>;
            margin-bottom: 6px;
        }
        .meta-table td {
            padding: 1px 0;
            vertical-align: top;
        }
        .meta-table td.right {
            text-align: right;
        }
        /* ITEMS TABLE */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
            font-size: <?= $width_num === 58 ? '10.5px' : '11.5px' ?>;
        }
        .items-table th {
            border-bottom: 1px solid #000;
            padding: 4px 0;
            text-align: left;
            font-weight: bold;
        }
        .items-table th.center { text-align: center; }
        .items-table th.right { text-align: right; }
        .items-table td {
            padding: 4px 0;
            vertical-align: top;
        }
        .items-table td.center { text-align: center; }
        .items-table td.right { text-align: right; font-weight: 600; }
        .item-staff {
            font-size: 9.5px;
            color: #555;
            display: block;
        }
        /* TOTALS */
        .totals-table {
            width: 100%;
            font-size: <?= $width_num === 58 ? '11px' : '12px' ?>;
            margin: 6px 0;
        }
        .totals-table td {
            padding: 2px 0;
        }
        .totals-table td.right {
            text-align: right;
            font-weight: bold;
        }
        .grand-total {
            font-size: <?= $width_num === 58 ? '13px' : '15px' ?>;
            font-weight: bold;
        }
        /* PAYMENTS */
        .payments-box {
            margin: 6px 0;
            font-size: <?= $width_num === 58 ? '10px' : '11px' ?>;
        }
        .payments-header {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }
        .footer {
            text-align: center;
            margin-top: 12px;
            font-size: 10px;
            color: #495057;
            line-height: 1.35;
        }
        .footer-highlight {
            font-weight: bold;
            color: #000;
            margin: 4px 0;
        }
        .barcode-box {
            text-align: center;
            margin: 10px 0 4px 0;
            font-family: monospace;
            font-size: 11px;
            letter-spacing: 2px;
        }

        /* PRINT STYLES */
        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .receipt-paper {
                border: none !important;
                box-shadow: none !important;
                padding: 4mm !important;
                width: 100% !important;
                max-width: <?= $width_num ?>mm !important;
            }
            @page {
                size: <?= $width_num ?>mm auto;
                margin: 0mm;
            }
        }
    </style>
</head>
<body>

    <!-- On-screen Action Toolbar (Excluded during print) -->
    <div class="no-print-bar">
        <div style="display:flex; align-items:center; gap:8px;">
            <i class="fas fa-print fa-lg text-primary"></i>
            <span style="font-size:13px; font-weight:600;">Fiş Yazdırma</span>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <select class="format-select" onchange="changeFormat(this.value)">
                <option value="80mm" <?= $format === '80mm' ? 'selected' : '' ?>>80mm Termal</option>
                <option value="58mm" <?= $format === '58mm' ? 'selected' : '' ?>>58mm Termal</option>
            </select>
            <button class="btn btn-secondary" onclick="window.close()"><i class="fas fa-times"></i> Kapat</button>
            <button class="btn" onclick="window.print()"><i class="fas fa-print"></i> Yazdır</button>
        </div>
    </div>

    <!-- ESC/POS Thermal Receipt Paper -->
    <div class="receipt-paper" id="receipt-paper">
        <div class="header">
            <div class="company-title"><?= e($company_name) ?></div>
            <div class="subtitle">BİLGİ FİŞİ / ADİSYON ÇIKTISI</div>
            <?php if (!empty($company_address)): ?>
                <div class="company-info"><?= nl2br(e($company_address)) ?></div>
            <?php endif; ?>
            <?php if (!empty($company_phone)): ?>
                <div class="company-info">Tel: <?= e($company_phone) ?></div>
            <?php endif; ?>
        </div>

        <div class="dashed-line"></div>

        <table class="meta-table">
            <tr>
                <td><strong>Adisyon:</strong></td>
                <td class="right font-monospace">#<?= e($adisyon['adisyon_number'] ?? $adisyon['id']) ?></td>
            </tr>
            <tr>
                <td><strong>Tarih:</strong></td>
                <td class="right"><?= date('d.m.Y H:i', strtotime($adisyon['opened_at'] ?? $adisyon['created_at'] ?? 'now')) ?></td>
            </tr>
            <tr>
                <td><strong>Müşteri:</strong></td>
                <td class="right"><?= e($customer_name) ?></td>
            </tr>
            <tr>
                <td><strong>Personel:</strong></td>
                <td class="right"><?= e($staff_name) ?></td>
            </tr>
            <?php if (!empty($adisyon['table_number'])): ?>
                <tr>
                    <td><strong>Masa:</strong></td>
                    <td class="right">Masa <?= e($adisyon['table_number']) ?> <?= !empty($adisyon['table_section']) ? '(' . e($adisyon['table_section']) . ')' : '' ?></td>
                </tr>
            <?php endif; ?>
        </table>

        <div class="dashed-line"></div>

        <!-- ITEMS -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">KALEM</th>
                    <th class="center" style="width: 15%;">AD</th>
                    <th class="right" style="width: 35%;">TUTAR</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($adisyon['items'])): ?>
                    <tr>
                        <td colspan="3" class="center" style="padding: 10px 0; color: #777;">Kalem bulunamadı.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($adisyon['items'] as $item): ?>
                        <tr>
                            <td>
                                <div><?= e($item['name']) ?></div>
                                <?php if (!empty($item['staff_first_name'])): ?>
                                    <span class="item-staff"><?= e($item['staff_first_name'] . ' ' . ($item['staff_last_name'] ?? '')) ?></span>
                                <?php endif; ?>
                                <?php if ((float)($item['discount_amount'] ?? 0) > 0): ?>
                                    <span class="item-staff" style="color: #d9534f;">-<?= number_format((float)$item['discount_amount'], 2) ?> ₺ İndirim</span>
                                <?php endif; ?>
                            </td>
                            <td class="center"><?= (float) $item['quantity'] ?></td>
                            <td class="right"><?= number_format((float) $item['total_amount'], 2) ?> ₺</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="dashed-line"></div>

        <!-- TOTALS -->
        <table class="totals-table">
            <tr>
                <td>ARA TOPLAM:</td>
                <td class="right"><?= number_format((float) ($adisyon['subtotal'] ?? 0), 2) ?> ₺</td>
            </tr>
            <tr>
                <td>KDV (%20):</td>
                <td class="right"><?= number_format((float) ($adisyon['tax_amount'] ?? 0), 2) ?> ₺</td>
            </tr>
            <?php if ((float) ($adisyon['discount_amount'] ?? 0) > 0): ?>
                <tr style="color: #b02a37;">
                    <td>İNDİRİM:</td>
                    <td class="right">-<?= number_format((float) $adisyon['discount_amount'], 2) ?> ₺</td>
                </tr>
            <?php endif; ?>
            <?php if ((float) ($adisyon['tip_amount'] ?? 0) > 0): ?>
                <tr>
                    <td>BAHŞİŞ:</td>
                    <td class="right"><?= number_format((float) $adisyon['tip_amount'], 2) ?> ₺</td>
                </tr>
            <?php endif; ?>
            <tr class="grand-total">
                <td style="padding-top: 4px;">GENEL TOPLAM:</td>
                <td class="right" style="padding-top: 4px;"><?= number_format((float) ($adisyon['total_amount'] ?? 0), 2) ?> ₺</td>
            </tr>
        </table>

        <!-- PAYMENTS -->
        <?php if (!empty($adisyon['payments'])): ?>
            <div class="dashed-line"></div>
            <div class="payments-box">
                <div class="payments-header">TAHSİLAT DÖKÜMÜ:</div>
                <?php foreach ($adisyon['payments'] as $pay): 
                    $method_label = [
                        'cash' => 'Nakit',
                        'card' => 'Kredi Kartı',
                        'bank_transfer' => 'Havale / EFT (IBAN)',
                        'package' => 'Paket Seansından',
                        'membership' => 'Üyelikten',
                        'gift_card' => 'Hediye Kartı',
                    ][$pay['payment_method']] ?? ucfirst($pay['payment_method']);
                ?>
                    <div class="payment-row">
                        <span>
                            <?= e($method_label) ?>
                            <?php if (!empty($pay['notes'])): ?>
                                <small style="display:block; font-size:9px; color:#555;"><?= e($pay['notes']) ?></small>
                            <?php endif; ?>
                        </span>
                        <span style="font-weight:bold;"><?= number_format((float) $pay['amount'], 2) ?> ₺</span>
                    </div>
                <?php endforeach; ?>

                <div class="dashed-line" style="margin: 4px 0;"></div>
                <div class="payment-row" style="font-weight: bold;">
                    <span>TOPLAM TAHSİLAT:</span>
                    <span><?= number_format((float) ($adisyon['paid_amount'] ?? 0), 2) ?> ₺</span>
                </div>
                <div class="payment-row" style="font-weight: bold; color: <?= $remaining > 0 ? '#b02a37' : '#198754' ?>;">
                    <span>KALAN BAKİYE:</span>
                    <span><?= number_format($remaining, 2) ?> ₺</span>
                </div>
            </div>
        <?php else: ?>
            <div class="dashed-line"></div>
            <div class="payments-box">
                <div class="payment-row" style="font-weight:bold; color:#b02a37;">
                    <span>ÖDEME DURUMU:</span>
                    <span>ÖDENMEDİ (<?= number_format((float)($adisyon['total_amount'] ?? 0), 2) ?> ₺)</span>
                </div>
            </div>
        <?php endif; ?>

        <div class="double-line"></div>

        <div class="barcode-box">
            *<?= e($adisyon['adisyon_number'] ?? $adisyon['id']) ?>*
        </div>

        <div class="footer">
            <div class="footer-highlight">Bizi Tercih Ettiğiniz İçin Teşekkür Ederiz!</div>
            <div>Bu belge bilgi fişidir, mali değeri yoktur.</div>
            <div>Mali e-Arşiv / e-Fatura talepleriniz için kasaya başvurunuz.</div>
        </div>
    </div>

    <script>
        function changeFormat(fmt) {
            const url = new URL(window.location.href);
            url.searchParams.set('format', fmt);
            window.location.href = url.toString();
        }
        // Auto prompt print on open if print param is present or standard open
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('autoprint') === '1') {
                setTimeout(() => window.print(), 300);
            }
        });
    </script>
</body>
</html>
