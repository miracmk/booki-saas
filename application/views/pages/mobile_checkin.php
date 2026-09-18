<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
    <title><?= e($company_name) ?> — Temassız Hızlı Giriş & Çıkış</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/themes/default.min.css?prod-20260918-001') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/general.min.css?prod-20260918-001') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/backend.min.css?prod-20260918-001') ?>">
    <script defer src="<?= base_url('assets/vendor/@fortawesome-fontawesome-free/fontawesome.min.js?prod-20260918-001') ?>"></script>
    <script defer src="<?= base_url('assets/vendor/@fortawesome-fontawesome-free/solid.min.js?prod-20260918-001') ?>"></script>
    <style>
        :root {
            --mobile-bg: #0f172a;
            --mobile-card-bg: rgba(30, 41, 59, 0.95);
            --mobile-accent: #3b82f6;
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body {
            margin: 0; padding: 0; width: 100%; min-height: 100vh; min-height: 100dvh;
            background: radial-gradient(circle at 50% 20%, #1e293b 0%, var(--mobile-bg) 100%);
            color: #f8fafc; font-family: system-ui, -apple-system, sans-serif;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
        }
        .mobile-card {
            background: var(--mobile-card-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            backdrop-filter: blur(12px);
            width: 100%; max-width: 440px;
        }
        .mobile-input {
            background: rgba(15, 23, 42, 0.8) !important;
            border: 2px solid rgba(255, 255, 255, 0.15) !important;
            color: #fff !important; font-size: 20px !important; letter-spacing: 1px;
            border-radius: 16px;
        }
        .mobile-input:focus {
            border-color: var(--mobile-accent) !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25) !important;
        }
        .btn-action-submit {
            height: 52px; font-size: 17px; font-weight: 700; border-radius: 14px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            border: none !important;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4) !important;
        }
    </style>
</head>
<body class="p-3">
    <div class="mobile-card p-4 shadow-lg text-center my-auto">
        <!-- Logo & Header -->
        <div class="mb-4">
            <?php if (!empty($company_logo)): ?>
                <img src="<?= e($company_logo) ?>" alt="logo" style="max-height: 52px; object-fit: contain;" class="mb-2">
            <?php endif; ?>
            <h4 class="fw-bold mb-1"><?= e($company_name) ?></h4>
            <p class="text-white-50 small mb-0">Temassız Hızlı Giriş & Çıkış</p>
        </div>

        <!-- Unified Form -->
        <form id="mobile-checkin-form" onsubmit="event.preventDefault(); submitMobileAction();">
            <div class="mb-3 text-start">
                <label class="form-label text-white-50 small fw-semibold" id="input-label">Telefon Numaranız</label>
                <input type="tel" id="mobile-identifier" class="form-control mobile-input text-center py-2"
                       placeholder="05XX XXX XX XX" autofocus required autocomplete="tel">
                <span class="text-white-50 small mt-1 d-block" style="font-size: 11px;">
                    İçeride değilseniz girişiniz, içerideyseniz otomatik çıkışınız yapılır.
                </span>
            </div>

            <!-- Feedback Alert -->
            <div id="mobile-feedback" class="alert d-none mb-3 py-2 fw-semibold rounded-3 shadow-sm text-start"></div>

            <button type="submit" id="btn-mobile-submit" class="btn btn-action-submit text-white w-100 shadow">
                <i class="fas fa-right-to-bracket me-2"></i> <span id="submit-text">GİRİŞ / ÇIKIŞ YAP</span>
            </button>
        </form>

        <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 text-white-50 small">
            BooKi Temassız Ziyaretçi Sistemi &bull; Salon Flora
        </div>
    </div>

    <script>
    function submitMobileAction() {
        const input = document.getElementById('mobile-identifier');
        const val = (input ? input.value : '').trim();
        if (!val) return;

        const btn = document.getElementById('btn-mobile-submit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Kontrol ediliyor...';

        const fd = new FormData();
        fd.append('identifier', val);
        fd.append('action', 'auto');
        fd.append('checkin_method', 'mobile_qr');

        fetch('<?= site_url('checkin/do_kiosk_action') ?>', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showFeedback(data.message || 'İşleminiz başarıyla tamamlandı.', 'alert-success');
                    input.value = '';
                } else if (data.status === 'already_inside') {
                    showFeedback(data.message || 'Zaten aktif bir girişiniz bulunuyor.', 'alert-info');
                } else {
                    showFeedback(data.message || 'İşlem gerçekleştirilemedi. Resepsiyona danışınız.', 'alert-danger');
                }
            })
            .catch(() => {
                showFeedback('Bağlantı hatası oluştu. Lütfen tekrar deneyiniz.', 'alert-danger');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-right-to-bracket me-2"></i> GİRİŞ / ÇIKIŞ YAP';
            });
    }

    function showFeedback(msg, cls) {
        const fb = document.getElementById('mobile-feedback');
        if (!fb) return;
        fb.className = 'alert ' + cls + ' mb-3 py-2 fw-semibold rounded-3 shadow-sm text-start';
        fb.innerHTML = msg;
        fb.classList.remove('d-none');
    }
    </script>
</body>
</html>
