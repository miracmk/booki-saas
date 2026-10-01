<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title ?? 'Dijital Onam & Hizmet Sözleşmesi') ?> - BooKi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; }
        .consent-card { max-width: 800px; margin: 30px auto; background: #fff; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden; }
        .consent-header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; padding: 28px; }
        .legal-content-box { max-height: 380px; overflow-y: auto; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; font-size: 0.95rem; line-height: 1.6; }
        .signature-pad { border: 2px dashed #cbd5e1; border-radius: 8px; background: #fff; touch-action: none; cursor: crosshair; }
    </style>
</head>
<body>

<div class="container pb-5">
    <div class="consent-card">
        <div class="consent-header text-center">
            <h4 class="fw-bold mb-1"><i class="fas fa-file-signature text-warning me-2"></i><?= e($tenant_name ?? 'Merkez') ?></h4>
            <div class="text-white-50 small">Elektronik Bilgilendirilmiş Onam ve Hizmet Sözleşmesi</div>
        </div>

        <div class="p-4 p-md-5">
            <?php if (!empty($already_signed)): ?>
                <div class="text-center py-4">
                    <div class="text-success mb-3"><i class="fas fa-check-circle fa-4x"></i></div>
                    <h4 class="fw-bold text-dark">Onam Formunuz Başarıyla Onaylanmıştır</h4>
                    <p class="text-muted mb-4">Bu hizmet için gerekli dijital bilgilendirme ve onam kaydınız sistemimize güvenle kaydedilmiştir.</p>
                    <div class="alert alert-light border small text-muted d-inline-block text-start">
                        <div><strong>İmzacı:</strong> <?= e($signer_name ?? '-') ?></div>
                        <div><strong>İmza Zamanı:</strong> <?= e($signed_at ?? '-') ?></div>
                        <div><strong>Hizmet:</strong> <?= e($service_name ?? '-') ?></div>
                    </div>
                </div>
            <?php elseif (empty($consents)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-info-circle fa-3x mb-3 text-secondary"></i>
                    <h5>Bu işlem için imzalanması gereken ek onam formu bulunmuyor.</h5>
                </div>
            <?php else: ?>
                <div class="mb-4">
                    <div class="row g-2 mb-3 small text-muted bg-light p-3 rounded border">
                        <div class="col-sm-6"><strong>Danışan:</strong> <?= e($customer_name) ?></div>
                        <div class="col-sm-6"><strong>Hizmet:</strong> <span class="badge bg-primary"><?= e($service_name) ?></span></div>
                        <div class="col-sm-6"><strong>Randevu Tarihi:</strong> <?= e($appointment_date) ?></div>
                        <div class="col-sm-6"><strong>Uzman:</strong> <?= e($provider_name) ?></div>
                    </div>

                    <?php foreach ($consents as $idx => $c): ?>
                        <div class="consent-block mb-4 p-3 border rounded shadow-sm">
                            <h5 class="fw-bold text-dark mb-2">
                                <i class="fas fa-file-contract text-primary me-2"></i><?= e($c['title']) ?>
                                <?php if ($c['is_mandatory']): ?>
                                    <span class="badge bg-danger small ms-2">Zorunlu Onam</span>
                                <?php endif; ?>
                            </h5>
                            
                            <div class="legal-content-box mb-3">
                                <?= $c['compiled_html'] ?>
                            </div>

                            <div class="form-check p-2 bg-light rounded border mb-2">
                                <input class="form-check-input consent-cb me-2" type="checkbox" id="cb-<?= $c['id'] ?>" data-id="<?= $c['id'] ?>" <?= $c['is_mandatory'] ? 'data-mandatory="1"' : '' ?>>
                                <label class="form-check-label fw-semibold small text-dark" for="cb-<?= $c['id'] ?>">
                                    Yukarıdaki metni okudum, anladım, olası sonuçlar hakkında bilgilendirildim ve onaylıyorum.
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="mt-4 p-3 bg-light rounded border">
                        <label class="form-label fw-bold d-flex justify-content-between align-items-center mb-1">
                            <span><i class="fas fa-signature text-danger me-1"></i>Biyometrik / Dijital İmzanız:</span>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" id="btn-clear-sig">
                                <i class="fas fa-eraser me-1"></i>Temizle
                            </button>
                        </label>
                        <div class="signature-pad mb-2">
                            <canvas id="client-signature-canvas" width="700" height="150" style="width: 100%; height: 150px; display: block;"></canvas>
                        </div>
                        <div class="small text-muted mb-3">Parmağınız veya dokunmatik kaleminizle kutu içerisine imzanızı atınız.</div>

                        <button type="button" class="btn btn-success btn-lg w-100 fw-bold shadow-sm" id="btn-submit-consent-client">
                            <i class="fas fa-check-circle me-2"></i>Onam Formunu İmzala & Gönder
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const canvas = document.getElementById('client-signature-canvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        canvas.width = canvas.parentElement.clientWidth || 700;
        canvas.height = 150;
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#0f172a';

        let drawing = false;
        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return { x: clientX - rect.left, y: clientY - rect.top };
        }

        canvas.addEventListener('mousedown', (e) => { drawing = true; ctx.beginPath(); const p = getPos(e); ctx.moveTo(p.x, p.y); });
        canvas.addEventListener('mousemove', (e) => { if (!drawing) return; const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); });
        window.addEventListener('mouseup', () => { drawing = false; });

        canvas.addEventListener('touchstart', (e) => { e.preventDefault(); drawing = true; ctx.beginPath(); const p = getPos(e); ctx.moveTo(p.x, p.y); });
        canvas.addEventListener('touchmove', (e) => { if (!drawing) return; e.preventDefault(); const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); });
        canvas.addEventListener('touchend', () => { drawing = false; });

        document.getElementById('btn-clear-sig')?.addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        });

        document.getElementById('btn-submit-consent-client')?.addEventListener('click', function () {
            let valid = true;
            document.querySelectorAll('.consent-cb[data-mandatory="1"]').forEach((cb) => {
                if (!cb.checked) { valid = false; cb.classList.add('is-invalid'); }
                else { cb.classList.remove('is-invalid'); }
            });

            if (!valid) {
                alert('Lütfen zorunlu bilgilendirme ve onam maddelerini onaylayınız.');
                return;
            }

            const sigData = canvas.toDataURL('image/png');
            const $btn = this;
            $btn.disabled = true;
            $btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Kaydediliyor...';

            fetch('<?= site_url('consents/submit_signature/' . ($appointment_hash ?? '')) ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ signature_data: sigData })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Onam kaydedilemedi.');
                    $btn.disabled = false;
                    $btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Onam Formunu İmzala & Gönder';
                }
            })
            .catch(() => {
                alert('Bağlantı hatası.');
                $btn.disabled = false;
                $btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Onam Formunu İmzala & Gönder';
            });
        });
    }
</script>

</body>
</html>
