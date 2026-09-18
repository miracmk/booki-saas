<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
    <title><?= e($company_name) ?> — Hızlı Giriş Kiosk</title>
    <link rel="stylesheet" href="<?= asset_url('assets/css/themes/' . setting('theme', 'default') . '.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('assets/css/general.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('assets/css/backend.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('assets/css/ki-command-center.min.css') ?>">
    <script defer src="<?= asset_url('assets/vendor/@fortawesome-fontawesome-free/fontawesome.min.js') ?>"></script>
    <script defer src="<?= asset_url('assets/vendor/@fortawesome-fontawesome-free/solid.min.js') ?>"></script>
    <style>
        :root {
            --kiosk-bg: #0f172a;
            --kiosk-card-bg: rgba(30, 41, 59, 0.94);
            --kiosk-accent: #3b82f6;
        }
        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            overflow-x: hidden;
            background: radial-gradient(circle at 50% 20%, #1e293b 0%, #0f172a 100%);
            color: #ffffff;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        #kiosk-touch-page {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .kiosk-top-bar {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.25rem;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            z-index: 10;
        }
        .kiosk-main-wrapper {
            flex: 1 1 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            width: 100%;
        }
        .kiosk-container {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
        }
        .kiosk-card {
            background: var(--kiosk-card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }
        .kiosk-logo-img {
            max-height: clamp(38px, 6vh, 56px);
            max-width: 160px;
            object-fit: contain;
        }
        .kiosk-input-display {
            width: 100% !important;
            height: clamp(50px, 7.5vh, 64px) !important;
            font-size: clamp(22px, 4vw, 30px) !important;
            letter-spacing: 2px !important;
            font-weight: 700 !important;
            background: rgba(15, 23, 42, 0.9) !important;
            color: #38bdf8 !important;
            border: 1.5px solid rgba(56, 189, 248, 0.3) !important;
            border-radius: 16px !important;
            box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.5) !important;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .kiosk-input-display:focus {
            border-color: #38bdf8 !important;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.25) !important;
        }
        .kiosk-keypad-grid {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 10px !important;
            width: 100% !important;
        }
        .num-pad-btn {
            height: clamp(48px, 7vh, 66px) !important;
            font-size: clamp(20px, 3.5vh, 26px) !important;
            font-weight: 700 !important;
            border-radius: 14px !important;
            background: rgba(255, 255, 255, 0.08) !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            color: #ffffff !important;
            transition: all 0.12s cubic-bezier(0.4, 0, 0.2, 1) !important;
            touch-action: manipulation !important;
            user-select: none !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            padding: 0 !important;
        }
        .num-pad-btn:hover {
            background: rgba(255, 255, 255, 0.15) !important;
            color: #ffffff !important;
            border-color: rgba(255, 255, 255, 0.25) !important;
        }
        .num-pad-btn:active {
            transform: scale(0.94) !important;
            background: rgba(56, 189, 248, 0.4) !important;
            color: #ffffff !important;
        }
        .num-pad-btn.action-clear {
            color: #f87171 !important;
            background: rgba(239, 68, 68, 0.12) !important;
            border-color: rgba(239, 68, 68, 0.25) !important;
            font-size: clamp(15px, 2.5vh, 19px) !important;
        }
        .num-pad-btn.action-clear:active {
            background: rgba(239, 68, 68, 0.35) !important;
        }
        .num-pad-btn.action-submit {
            color: #4ade80 !important;
            background: rgba(34, 197, 94, 0.15) !important;
            border-color: rgba(34, 197, 94, 0.3) !important;
        }
        .num-pad-btn.action-submit:active {
            background: rgba(34, 197, 94, 0.4) !important;
        }
        .btn-kiosk-submit {
            height: clamp(50px, 7.5vh, 64px) !important;
            font-size: clamp(17px, 2.5vh, 21px) !important;
            font-weight: 700 !important;
            letter-spacing: 0.5px !important;
            border-radius: 16px !important;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
            border: none !important;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.4) !important;
            transition: all 0.15s ease !important;
            color: #ffffff !important;
        }
        .btn-kiosk-submit:hover {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            box-shadow: 0 10px 28px rgba(37, 99, 235, 0.55) !important;
            color: #ffffff !important;
        }
        .btn-kiosk-submit:active {
            transform: scale(0.98) !important;
        }
        .kiosk-footer-note {
            padding: 0.5rem 1rem;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.4);
            text-align: center;
        }
        @media (orientation: landscape) and (max-height: 520px) {
            .kiosk-top-bar { padding: 0.35rem 1rem; }
            .kiosk-card { padding: 0.75rem 1.25rem !important; border-radius: 18px; }
            .kiosk-header-area { margin-bottom: 0.35rem !important; }
            .kiosk-header-area h3 { font-size: 1.1rem !important; }
            .kiosk-header-area p { display: none; }
            .kiosk-input-wrap { margin-bottom: 0.35rem !important; }
            .num-pad-btn { height: 38px !important; font-size: 17px !important; }
            .btn-kiosk-submit { height: 42px !important; font-size: 15px !important; }
        }
    </style>
</head>
<body id="kiosk-touch-page">
    <!-- Kiosk Top Navigation Bar -->
    <header class="kiosk-top-bar">
        <a href="<?= site_url('checkin') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3 py-1" style="font-size: 12px; border-color: rgba(255,255,255,0.2);">
            <i class="fas fa-arrow-left me-1"></i> Panele Dön
        </a>
        <div class="d-flex align-items-center gap-3">
            <div id="kiosk-live-clock" class="text-white-50 fw-semibold font-monospace small d-none d-sm-block">
                --:--:--
            </div>
            <button type="button" class="btn btn-sm btn-link text-white-50 p-1 text-decoration-none" onclick="toggleFullscreen()" title="Tam Ekran Modu">
                <i class="fas fa-expand fa-lg" id="fs-icon"></i>
            </button>
        </div>
    </header>

    <!-- Main Interactive Area -->
    <main class="kiosk-main-wrapper">
        <div class="kiosk-container">
            <div class="kiosk-card p-3 p-sm-4 shadow-lg text-center">
                <!-- Branding Header -->
                <div class="kiosk-header-area mb-3 mb-sm-4">
                    <?php if (!empty($company_logo)): ?>
                        <img src="<?= e($company_logo) ?>" alt="logo" class="kiosk-logo-img mb-2">
                    <?php endif; ?>
                    <h3 class="fw-bold mb-1 fs-4 fs-sm-3"><?= e($company_name) ?></h3>
                    <p class="text-white-50 small mb-0">Hızlı Giriş Terminali &bull; Telefon Numaranızı Girin</p>
                </div>

                <!-- Display Screen -->
                <div class="kiosk-input-wrap mb-3 mb-sm-4">
                    <input type="text" id="kiosk-pass-input" name="kiosk_phone"
                           class="form-control kiosk-input-display text-center"
                           placeholder="05XX XXX XX XX" readonly autocomplete="off">
                </div>

                <!-- Feedback Alert Box -->
                <div id="kiosk-feedback" class="alert d-none mb-3 py-2 py-sm-3 fw-semibold rounded-3 shadow-sm"></div>

                <!-- Touch Number Keypad (3x4 CSS Grid) -->
                <div class="kiosk-keypad-grid mb-3 mb-sm-4">
                    <button type="button" class="btn num-pad-btn keypad-btn" onclick="pressKey('1')">1</button>
                    <button type="button" class="btn num-pad-btn keypad-btn" onclick="pressKey('2')">2</button>
                    <button type="button" class="btn num-pad-btn keypad-btn" onclick="pressKey('3')">3</button>
                    <button type="button" class="btn num-pad-btn keypad-btn" onclick="pressKey('4')">4</button>
                    <button type="button" class="btn num-pad-btn keypad-btn" onclick="pressKey('5')">5</button>
                    <button type="button" class="btn num-pad-btn keypad-btn" onclick="pressKey('6')">6</button>
                    <button type="button" class="btn num-pad-btn keypad-btn" onclick="pressKey('7')">7</button>
                    <button type="button" class="btn num-pad-btn keypad-btn" onclick="pressKey('8')">8</button>
                    <button type="button" class="btn num-pad-btn keypad-btn" onclick="pressKey('9')">9</button>
                    <button type="button" class="btn num-pad-btn keypad-btn action-clear" onclick="clearKey()"><i class="fas fa-backspace me-1"></i>Sil</button>
                    <button type="button" class="btn num-pad-btn keypad-btn" onclick="pressKey('0')">0</button>
                    <button type="button" class="btn num-pad-btn keypad-btn action-submit" onclick="submitKioskCheckin()"><i class="fas fa-check"></i></button>
                </div>

                <!-- Big Checkin Action Button -->
                <button type="button" id="btn-kiosk-submit" class="btn btn-primary btn-kiosk-submit w-100 shadow" onclick="submitKioskCheckin()">
                    <i class="fas fa-sign-in-alt me-2"></i> GİRİŞ YAP
                </button>
            </div>
        </div>
    </main>

    <!-- Kiosk Footer Note -->
    <footer class="kiosk-footer-note">
        BooKi Self-Service Kiosk &bull; Dokunmatik veya klavye ile işlem yapabilirsiniz
    </footer>

    <script>
    let phoneBuffer = '';
    let feedbackTimeout = null;

    // Live Clock updater
    function updateClock() {
        const clockEl = document.getElementById('kiosk-live-clock');
        if (clockEl) {
            const now = new Date();
            clockEl.innerText = now.toLocaleTimeString('tr-TR');
        }
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Keypad press handler
    function pressKey(key) {
        haptic();
        if (phoneBuffer.length < 11) {
            phoneBuffer += key;
            updateDisplay();
        }
    }

    // Delete single or clear all
    function deleteOneKey() {
        haptic();
        if (phoneBuffer.length > 0) {
            phoneBuffer = phoneBuffer.slice(0, -1);
            updateDisplay();
        }
    }

    function clearKey() {
        haptic();
        phoneBuffer = '';
        updateDisplay();
    }

    function updateDisplay() {
        const el = document.getElementById('kiosk-pass-input') || document.getElementById('kiosk-phone-display');
        if (el) {
            el.value = phoneBuffer;
        }
    }

    // Haptic feedback for touch screens
    function haptic() {
        if (window.navigator && window.navigator.vibrate) {
            try { window.navigator.vibrate(12); } catch (e) {}
        }
    }

    // Fullscreen toggle for front-desk tablets
    function toggleFullscreen() {
        const icon = document.getElementById('fs-icon');
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(() => {});
            if (icon) {
                icon.classList.remove('fa-expand');
                icon.classList.add('fa-compress');
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen().catch(() => {});
                if (icon) {
                    icon.classList.remove('fa-compress');
                    icon.classList.add('fa-expand');
                }
            }
        }
    }

    // Physical Keyboard & Barcode/RFID Reader Listener
    document.addEventListener('keydown', function(e) {
        if (e.key >= '0' && e.key <= '9') {
            pressKey(e.key);
        } else if (e.key === 'Backspace') {
            deleteOneKey();
        } else if (e.key === 'Escape' || e.key === 'Delete') {
            clearKey();
        } else if (e.key === 'Enter') {
            submitKioskCheckin();
        }
    });

    // Check-in submission
    function submitKioskCheckin() {
        if (phoneBuffer.length < 10) {
            showFeedback('Lütfen geçerli bir telefon numarası girin.', 'alert-warning');
            return;
        }

        const submitBtn = document.getElementById('btn-kiosk-submit');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Kontrol ediliyor...';
        }

        const fd = new FormData();
        fd.append('phone', phoneBuffer);
        fd.append('checkin_method', 'kiosk');

        fetch('<?= site_url('checkin/do_checkin') ?>', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const name = (data.customer && data.customer.first_name) ? data.customer.first_name : 'Değerli Müşterimiz';
                    showFeedback(`✓ Hoş geldiniz ${name}! Girişiniz başarıyla yapıldı.`, 'alert-success');
                    phoneBuffer = '';
                    updateDisplay();
                } else if (data.status === 'already_inside') {
                    showFeedback(data.message || 'Zaten aktif bir giriş kaydınız bulunmaktadır.', 'alert-info');
                } else {
                    showFeedback(data.message || 'Giriş yapılamadı. Lütfen resepsiyona danışınız.', 'alert-danger');
                }
            })
            .catch(() => {
                showFeedback('Bağlantı hatası oluştu. Lütfen tekrar deneyin.', 'alert-danger');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i> GİRİŞ YAP';
                }
            });
    }

    function showFeedback(msg, cls) {
        if (feedbackTimeout) clearTimeout(feedbackTimeout);
        const fb = document.getElementById('kiosk-feedback');
        if (!fb) return;
        fb.className = 'alert ' + cls + ' mb-3 py-2 py-sm-3 fw-semibold rounded-3 shadow-sm';
        fb.innerText = msg;
        fb.classList.remove('d-none');
        feedbackTimeout = setTimeout(() => {
            fb.classList.add('d-none');
        }, 5000);
    }
    </script>
</body>
</html>
