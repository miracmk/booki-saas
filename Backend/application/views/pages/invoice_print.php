<?php defined('BASEPATH') or exit('No direct script access allowed');
$customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: 'Bireysel Müşteri';
$customerEmail = $customer['email'] ?? '-';
$customerPhone = $customer['phone_number'] ?? '-';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Fatura #<?= htmlspecialchars($invoice['invoice_number']) ?> - e-Arşiv Fatura</title>
  <style>
    body { font-family: 'Helvetica Neue', Arial, sans-serif; margin: 0; padding: 25px; color: #222; background: #fff; }
    .invoice-box { max-width: 850px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
    .header-table, .items-table, .totals-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .title { font-size: 24px; font-weight: bold; color: #3b82f6; }
    .badge-e-arsiv { display: inline-block; background: #2563eb; color: #fff; font-size: 11px; font-weight: bold; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; margin-top: 5px; }
    .meta-box { background: #f8fafc; padding: 15px; border-radius: 6px; font-size: 13px; line-height: 1.6; }
    .items-table th { background: #f1f5f9; border-bottom: 2px solid #cbd5e1; padding: 10px; text-align: left; font-size: 13px; }
    .items-table td { padding: 10px; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
    .totals-table td { padding: 6px 10px; font-size: 13px; }
    .grand-total { font-size: 16px; font-weight: bold; color: #1e293b; border-top: 2px solid #cbd5e1; }
    .print-btn { background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: bold; margin-bottom: 20px; }
    @media print {
      .no-print { display: none; }
      .invoice-box { border: none; box-shadow: none; padding: 0; }
    }
  </style>
</head>
<body>
  <div class="no-print" style="max-width: 850px; margin: 0 auto 15px auto; text-align: right;">
    <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Faturayı Yazdır / PDF Kaydet</button>
  </div>

  <div class="invoice-box">
    <table class="header-table">
      <tr>
        <td style="vertical-align: top; width: 60%;">
          <div class="title"><?= htmlspecialchars($company_name) ?></div>
          <div class="badge-e-arsiv">e-Arşiv Fatura</div>
          <p style="font-size: 12px; color: #64748b; margin-top: 8px;">
            Hizmet & Rezervasyon Satış Belgesi<br>
            VKN: 1234567890 | Mersis: 012345678900001
          </p>
        </td>
        <td style="vertical-align: top; width: 40%; text-align: right;">
          <div style="font-size: 15px; font-weight: bold;">Fatura No: <?= htmlspecialchars($invoice['invoice_number']) ?></div>
          <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Düzenleme Tarihi: <?= date('d.m.Y H:i', strtotime($invoice['created_at'])) ?></div>
          <?php if (!empty($invoice['erp_invoice_id'])): ?>
            <div style="font-size: 12px; color: #16a34a; margin-top: 4px;">ERP No: <?= htmlspecialchars($invoice['erp_invoice_id']) ?> (<?= htmlspecialchars($invoice['erp_provider']) ?>)</div>
          <?php endif; ?>
        </td>
      </tr>
    </table>

    <div class="meta-box" style="margin-bottom: 25px;">
      <table style="width: 100%;">
        <tr>
          <td style="width: 50%; vertical-align: top;">
            <strong>SAYIN:</strong><br>
            <?= htmlspecialchars($customerName) ?><br>
            E-posta: <?= htmlspecialchars($customerEmail) ?><br>
            Telefon: <?= htmlspecialchars($customerPhone) ?>
          </td>
          <td style="width: 50%; vertical-align: top; text-align: right;">
            <strong>Ödeme Bilgileri:</strong><br>
            Durum: <strong><?= strtoupper($invoice['status']) ?></strong><br>
            Para Birimi: <?= htmlspecialchars($invoice['currency']) ?><br>
            Vade Tarihi: <?= !empty($invoice['due_at']) ? date('d.m.Y', strtotime($invoice['due_at'])) : 'Peşin' ?>
          </td>
        </tr>
      </table>
    </div>

    <table class="items-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Mal / Hizmet Açıklaması</th>
          <th style="text-align: center;">Miktar</th>
          <th style="text-align: right;">Birim Fiyat</th>
          <th style="text-align: right;">KDV</th>
          <th style="text-align: right;">Toplam</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $idx => $item): ?>
          <tr>
            <td><?= $idx + 1 ?></td>
            <td><strong><?= htmlspecialchars($item['description']) ?></strong></td>
            <td style="text-align: center;"><?= number_format((float) $item['quantity'], 0) ?> Adet</td>
            <td style="text-align: right;"><?= number_format((float) $item['unit_price'], 2, ',', '.') ?> <?= $invoice['currency'] ?></td>
            <td style="text-align: right;">%20</td>
            <td style="text-align: right; font-weight: bold;"><?= number_format((float) $item['line_total'], 2, ',', '.') ?> <?= $invoice['currency'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <table class="totals-table" style="width: 45%; margin-left: auto;">
      <tr>
        <td>Ara Toplam:</td>
        <td style="text-align: right;"><?= number_format((float) $invoice['subtotal'], 2, ',', '.') ?> <?= $invoice['currency'] ?></td>
      </tr>
      <tr>
        <td>Hesaplanan KDV (%20):</td>
        <td style="text-align: right;"><?= number_format((float) $invoice['tax_total'], 2, ',', '.') ?> <?= $invoice['currency'] ?></td>
      </tr>
      <tr class="grand-total">
        <td>Genel Toplam:</td>
        <td style="text-align: right; color: #2563eb;"><?= number_format((float) $invoice['total'], 2, ',', '.') ?> <?= $invoice['currency'] ?></td>
      </tr>
    </table>

    <div style="margin-top: 40px; padding-top: 15px; border-top: 1px dashed #cbd5e1; font-size: 11px; color: #94a3b8; text-align: center;">
      Bu belge 213 sayılı V.U.K. hükümlerine göre elektronik ortamda düzenlenmiş ve imzalanmıştır.
    </div>
  </div>
</body>
</html>
