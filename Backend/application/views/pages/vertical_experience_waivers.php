<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @var array $waivers
 * @var array $signatures
 * @var array $tickets
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?php $this->load->view('components/backend_head'); ?>
    <title><?= e(vars('page_title')) ?> - BooKi</title>
</head>
<body class="backend-body">
    <?php $this->load->view('components/backend_header'); ?>

    <div class="container-fluid py-4 px-md-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1"><i class="fas fa-ticket-alt text-warning me-2"></i>Deneyimler, Dijital Feragatname & Biletleme</h1>
                <p class="text-muted small mb-0">Kaçış oyunları, VR simülasyonları ve atölyeler için zorunlu dijital sözleşme, feragatname imzaları ve QR bilet tarama.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-create-waiver">
                    <i class="fas fa-file-signature me-1"></i> Yeni Feragatname Şablonu
                </button>
            </div>
        </div>

        <div class="row g-4">
            <!-- LEFT: SIGNED WAIVERS & CONTRACTS -->
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="fas fa-signature text-primary me-2"></i>İmzalanan Dijital Feragatnameler</h5>
                        <span class="badge bg-secondary"><?= count($signatures) ?> İmzalı Form</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>İmzalayan</th>
                                    <th>Sözleşme / Feragatname</th>
                                    <th>İmza Zamanı</th>
                                    <th>IP & Onay</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($signatures)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Henüz imzalanmış feragatname kaydı yok.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($signatures as $s): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= e($s['signer_full_name']) ?></div>
                                                <small class="text-muted"><?= e($s['signer_phone'] ?: ($s['signer_email'] ?: '-')) ?></small>
                                            </td>
                                            <td><span class="badge bg-light text-dark border"><?= e($s['waiver_title'] ?: 'Genel Feragatname') ?></span></td>
                                            <td><small><?= date('d.m.Y H:i', strtotime($s['signed_at'])) ?></small></td>
                                            <td>
                                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Onaylandı</span>
                                                <small class="text-muted d-block"><?= e($s['ip_address']) ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- RIGHT: QR TICKET SCANNER & VALIDATOR -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="fas fa-qrcode text-warning me-2"></i>Kapı QR Bilet Doğrulama</h5>
                        <span class="badge bg-warning text-dark">Canlı Giriş</span>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">Katılımcının telefonundaki QR kodu veya TKT kodunu okutarak tek tıkla doğrula ve yak:</p>
                        <form id="form-validate-ticket" class="mb-3">
                            <div class="input-group">
                                <input type="text" id="ticket-code-input" class="form-control font-monospace" placeholder="TKT-XXXX-XXXX" required>
                                <button class="btn btn-warning fw-bold" type="submit"><i class="fas fa-barcode me-1"></i> Doğrula</button>
                            </div>
                        </form>
                        <div id="ticket-result-box" class="alert d-none py-2 px-3 small"></div>

                        <h6 class="fw-bold mt-4 mb-2"><i class="fas fa-list me-1 text-secondary"></i>Üretilen Etkinlik Biletleri</h6>
                        <div class="list-group list-group-flush small" style="max-height: 280px; overflow-y: auto;">
                            <?php if (empty($tickets)): ?>
                                <div class="text-muted text-center py-3">Henüz bilet üretilmedi.</div>
                            <?php else: ?>
                                <?php foreach ($tickets as $t): ?>
                                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="font-monospace fw-bold me-1 text-primary"><?= e($t['ticket_code']) ?></span>
                                            <span><?= e(trim($t['first_name'] . ' ' . $t['last_name'])) ?></span>
                                        </div>
                                        <div>
                                            <?php if ($t['status'] === 'valid'): ?>
                                                <span class="badge bg-success">Geçerli</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Kullanıldı</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: YENİ FERAGATNAME -->
    <div class="modal fade" id="modal-create-waiver" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-contract text-primary me-2"></i>Yeni Dijital Feragatname & Onay Metni</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-create-waiver">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Sözleşme / Feragatname Başlığı</label>
                            <input type="text" name="title" class="form-control" placeholder="Örn: Kaçış Odası Güvenlik Kuralları & Sorumluluk Beyanı" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Metin İçeriği (HTML / Metin)</label>
                            <textarea name="content_html" rows="6" class="form-control" placeholder="Katılımcıların oyuna/etkinliğe başlamadan önce onaylaması gereken kurallar, sağlık uyarıları ve feragat maddeleri..." required></textarea>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_mandatory" value="1" checked id="switch-mandatory">
                            <label class="form-check-label fw-semibold" for="switch-mandatory">Rezervasyondan önce imzalanması zorunludur</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Şablonu Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('form-create-waiver').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const data = Object.fromEntries(fd.entries());
        try {
            const res = await fetch('<?= site_url('api/v1/verticals/experience/waivers') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            if (res.ok) {
                alert('Feragatname şablonu kaydedildi!');
                window.location.reload();
            } else {
                alert('Hata: ' + (json.error || 'İşlem başarısız'));
            }
        } catch (err) {
            alert('Ağ hatası: ' + err.message);
        }
    });

    document.getElementById('form-validate-ticket').addEventListener('submit', async function(e) {
        e.preventDefault();
        const code = document.getElementById('ticket-code-input').value;
        const box = document.getElementById('ticket-result-box');
        box.className = 'alert alert-info py-2 px-3 small';
        box.innerText = 'Doğrulanıyor...';
        box.classList.remove('d-none');

        try {
            const res = await fetch('<?= site_url('api/v1/verticals/experience/tickets/validate') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ticket_code: code })
            });
            const json = await res.json();
            if (json.valid) {
                box.className = 'alert alert-success py-2 px-3 small';
                box.innerHTML = '<strong><i class="fas fa-check-circle me-1"></i>BİLET GEÇERLİ!</strong><br>' + json.message;
            } else {
                box.className = 'alert alert-danger py-2 px-3 small';
                box.innerHTML = '<strong><i class="fas fa-times-circle me-1"></i>GEÇERSİZ BİLET!</strong><br>' + (json.message || json.error);
            }
        } catch (err) {
            box.className = 'alert alert-danger py-2 px-3 small';
            box.innerText = 'Ağ hatası: ' + err.message;
        }
    });
    </script>
</body>
</html>
