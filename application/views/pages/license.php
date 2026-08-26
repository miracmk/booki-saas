<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php
$license_status = vars('license_status') ?? [];
$license_key = (string) (vars('license_key') ?? '');

$state = $license_status['state'] ?? 'missing';
$claims = $license_status['claims'] ?? null;

$state_labels = [
    'valid' => ['Geçerli', 'success'],
    'grace' => ['Ek Süre (Ödeme Bekleniyor)', 'warning'],
    'expired' => ['Süresi Doldu', 'danger'],
    'missing' => ['Lisans Yok', 'secondary'],
    'invalid' => ['Geçersiz', 'danger'],
];
[$state_label, $state_color] = $state_labels[$state] ?? ['Bilinmiyor', 'secondary'];
?>

<div class="container backend-page py-3" id="license-page" style="max-width: 700px;">
    <h4 class="mb-3 fw-light">Lisans</h4>

    <div class="card mb-4">
        <div class="card-body">
            <p class="mb-2">
                <span class="badge bg-<?= $state_color ?>"><?= e($state_label) ?></span>
            </p>
            <p class="mb-0"><?= e($license_status['message'] ?? '') ?></p>

            <?php if ($claims): ?>
                <hr>
                <dl class="row mb-0 small">
                    <dt class="col-4">Lisans sahibi</dt>
                    <dd class="col-8"><?= e($claims['sub'] ?? '-') ?></dd>
                    <dt class="col-4">Plan</dt>
                    <dd class="col-8"><?= e($claims['plan'] ?? '-') ?></dd>
                    <dt class="col-4">Geçerlilik bitişi</dt>
                    <dd class="col-8"><?= isset($claims['exp']) ? e(date('Y-m-d H:i', (int) $claims['exp'])) : '-' ?></dd>
                    <?php if (isset($license_status['days_remaining'])): ?>
                        <dt class="col-4">Kalan/geçen gün</dt>
                        <dd class="col-8"><?= (int) $license_status['days_remaining'] ?></dd>
                    <?php endif; ?>
                </dl>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Lisans Anahtarını Güncelle</h6>
            <p class="text-muted small">Ki Software tarafından size iletilen lisans anahtarını buraya yapıştırın.</p>
            <textarea id="license-key-input" class="form-control mb-3" rows="4" placeholder="eyJ..."><?= e($license_key) ?></textarea>
            <button type="button" id="save-license-key" class="btn btn-primary">Kaydet</button>
            <span id="license-save-message" class="ms-2 text-success" style="display:none;">Kaydedildi.</span>
        </div>
    </div>
</div>

<script>
    document.getElementById('save-license-key').addEventListener('click', function () {
        const key = document.getElementById('license-key-input').value.trim();
        const csrfToken = (typeof vars === 'function') ? vars('csrf_token') : null;

        fetch('<?= site_url('license/save') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({license_key: key, csrf_token: csrfToken || ''}),
        })
            .then((r) => r.json())
            .then((r) => {
                if (r.success) {
                    document.getElementById('license-save-message').style.display = 'inline';
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    alert(r.message || 'Kaydedilemedi.');
                }
            })
            .catch(() => alert('Kaydedilemedi.'));
    });
</script>

<?php end_section('content'); ?>
