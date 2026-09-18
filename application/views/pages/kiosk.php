<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
    <title><?= e($company_name) ?> — Hızlı Giriş & Çıkış Kiosk</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/themes/default.min.css?prod-20260918-001') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/general.min.css?prod-20260918-001') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/backend.min.css?prod-20260918-001') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/ki-command-center.min.css?prod-20260918-001') ?>">
    <script defer src="<?= base_url('assets/vendor/@fortawesome-fontawesome-free/fontawesome.min.js?prod-20260918-001') ?>"></script>
    <script defer src="<?= base_url('assets/vendor/@fortawesome-fontawesome-free/solid.min.js?prod-20260918-001') ?>"></script>
    <!-- Vendored Offline QR Scanner & Generator -->
    <script src="<?= base_url('assets/vendor/qrcodejs/qrcode.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/html5-qrcode/html5-qrcode.min.js') ?>"></script>
    <style>
        :root {
            --kiosk-bg: #0f172a;
            --kiosk-card-bg: rgba(30, 41, 59, 0.94);
            --kiosk-accent: #3b82f6;
            --kiosk-success: #10b981;
            --kiosk-danger: #ef4444;
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
            background: radial-gradient(circle at 50% 20%, #1e293b 0%, var(--kiosk-bg) 100%);
            color: #f8fafc;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            user-select: none;
            -webkit-user-select: none;
        }
        .kiosk-main-wrapper {
            min-height: calc(100vh - 60px);
            min-height: calc(100dvh - 60px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .kiosk-container {
            width: 100%;
            max-width: 490px;
            margin: 0 auto;
        }
        .kiosk-card {
            background: var(--kiosk-card-bg);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 28px;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
        }
        .kiosk-logo-img {
            max-height: 48px;
            max-width: 180px;
            object-fit: contain;
        }
        /* Mode Switcher Tabs (3 Tabs) */
        .kiosk-nav-pills {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            background: rgba(15, 23, 42, 0.7);
            padding: 6px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .kiosk-tab-btn {
            border: none;
            background: transparent;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 700;
            padding: 10px 6px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }
        .kiosk-tab-btn i {
            font-size: 16px;
        }
        .kiosk-tab-btn.active.tab-keypad {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
        }
        .kiosk-tab-btn.active.tab-camera {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: #fff;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.4);
        }
        .kiosk-tab-btn.active.tab-mobile {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.4);
        }

        /* Display Screen */
        .kiosk-input-display {
            background: rgba(15, 23, 42, 0.8) !important;
            border: 2px solid rgba(255, 255, 255, 0.12) !important;
            color: #ffffff !important;
            font-size: clamp(20px, 5.5vw, 28px) !important;
            font-weight: 700 !important;
            letter-spacing: clamp(1px, 1vw, 3px);
            border-radius: 18px !important;
            padding: 0.65rem 1rem !important;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4);
            transition: border-color 0.2s ease;
        }
        .kiosk-input-display:focus {
            border-color: var(--kiosk-accent) !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25), inset 0 2px 4px rgba(0,0,0,0.4) !important;
        }

        /* 3x4 CSS Grid Keypad */
        .kiosk-keypad-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: clamp(8px, 1.8vw, 12px);
            width: 100%;
        }
        .num-pad-btn {
            height: clamp(48px, 8.5vh, 62px);
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            color: #f8fafc;
            font-size: clamp(20px, 4.5vw, 24px);
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.12s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        }
        .num-pad-btn:active {
            transform: scale(0.94);
            background: rgba(255, 255, 255, 0.18);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        .num-pad-btn.action-clear {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            font-size: clamp(14px, 3.2vw, 17px);
        }
        .num-pad-btn.action-clear:active {
            background: rgba(239, 68, 68, 0.3);
        }
        .num-pad-btn.action-submit {
            background: rgba(16, 185, 129, 0.2);
            border-color: rgba(16, 185, 129, 0.4);
            color: #6ee7b7;
            font-size: clamp(17px, 3.8vw, 20px);
        }
        .num-pad-btn.action-submit:active {
            background: rgba(16, 185, 129, 0.4);
        }

        /* Action Buttons */
        .btn-kiosk-submit {
            height: clamp(50px, 8.5vh, 60px);
            font-size: clamp(16px, 3.8vw, 19px);
            font-weight: 700;
            letter-spacing: 0.5px;
            border-radius: 16px;
            transition: all 0.15s ease;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            border: none !important;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4) !important;
        }
        .btn-kiosk-submit:active {
            transform: scale(0.98);
        }

        /* Top Bar */
        .kiosk-top-bar {
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.25rem;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .kiosk-footer-note {
            padding: 0.6rem 1rem;
            text-align: center;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.4);
        }

        /* Camera Scanner Container */
        #kiosk-qr-reader {
            width: 100% !important;
            border-radius: 20px !important;
            overflow: hidden !important;
            border: 2px solid rgba(139, 92, 246, 0.4) !important;
            background: #000000 !important;
            min-height: 240px;
        }
        #kiosk-qr-reader video {
            border-radius: 18px !important;
            object-fit: cover !important;
        }
        #kiosk-qr-reader__scan_region {
            border-radius: 18px !important;
        }

        /* Touchless QR Display Card */
        .touchless-qr-card {
            background: #ffffff;
            padding: 16px;
            border-radius: 20px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            margin: 0 auto;
        }
        .touchless-qr-card img, .touchless-qr-card canvas {
            display: block !important;
            margin: 0 auto !important;
            border-radius: 12px;
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
                <div class="kiosk-header-area mb-3">
                    <?php if (!empty($company_logo)): ?>
                        <img src="<?= e($company_logo) ?>" alt="logo" class="kiosk-logo-img mb-1">
                    <?php endif; ?>
                    <h3 class="fw-bold mb-0 fs-4"><?= e($company_name) ?></h3>
                    <p class="text-white-50 small mb-0" id="kiosk-subtitle">Telefon numaranızı girerek anında giriş veya çıkış yapın</p>
                </div>

                <!-- Mode Switcher Tabs (3 Tabs) -->
                <div class="kiosk-nav-pills mb-3" id="kiosk-tabs">
                    <button type="button" class="kiosk-tab-btn tab-keypad active" onclick="switchKioskTab('keypad')">
                        <i class="fas fa-keyboard"></i> Telefon Numarası
                    </button>
                    <button type="button" class="kiosk-tab-btn tab-camera" onclick="switchKioskTab('camera')">
                        <i class="fas fa-camera"></i> QR Kod Tara
                    </button>
                    <button type="button" class="kiosk-tab-btn tab-mobile" onclick="switchKioskTab('mobile')">
                        <i class="fas fa-mobile-screen"></i> Temassız QR
                    </button>
                </div>

                <!-- Feedback Alert Box -->
                <div id="kiosk-feedback" class="alert d-none mb-3 py-2 py-sm-3 fw-semibold rounded-3 shadow-sm text-start"></div>

                <!-- SUCCESS QR BADGE (Shown after checkin) -->
                <div id="kiosk-success-badge" class="d-none mb-3 p-3 rounded-3 text-center" style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3);">
                    <div class="text-success fw-bold mb-1 fs-5" id="success-guest-name"></div>
                    <p class="text-white-50 small mb-2">Çıkışta hızlı işlem yapmak için bu QR kodunu veya telefon numaranızı kullanabilirsiniz.</p>
                    <div id="kiosk-checkout-qr-box" class="touchless-qr-card mx-auto"></div>
                </div>

                <!-- TAB 1: KEYPAD INPUT VIEW (Unified Giriş / Çıkış) -->
                <div id="view-keypad-mode">
                    <!-- Display Screen -->
                    <div class="kiosk-input-wrap mb-3">
                        <input type="text" id="kiosk-pass-input" name="kiosk_phone"
                               class="form-control kiosk-input-display text-center"
                               placeholder="05XX XXX XX XX" readonly autocomplete="off">
                    </div>

                    <!-- Touch Number Keypad (3x4 CSS Grid) -->
                    <div class="kiosk-keypad-grid mb-3">
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
                        <button type="button" class="btn num-pad-btn keypad-btn action-submit" onclick="submitKioskAction()"><i class="fas fa-check"></i></button>
                    </div>

                    <!-- Single Unified Action Button -->
                    <button type="button" id="btn-kiosk-submit" class="btn btn-kiosk-submit text-white w-100 shadow" onclick="submitKioskAction()">
                        <i class="fas fa-right-to-bracket me-2"></i> <span id="btn-kiosk-text">GİRİŞ / ÇIKIŞ YAP</span>
                    </button>
                </div>

                <!-- TAB 2: CAMERA QR SCANNER VIEW (Html5Qrcode Engine + Permission Handler) -->
                <div id="view-camera-mode" class="d-none text-center">
                    <div id="kiosk-qr-reader" class="mb-3"></div>

                    <div class="d-flex flex-column gap-2 mb-2">
                        <button type="button" id="btn-start-camera" class="btn btn-primary rounded-pill py-2 fw-bold shadow" onclick="requestAndStartCamera()">
                            <i class="fas fa-video me-2"></i> Kamerayı Başlat (İzin İste)
                        </button>
                        
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="switchCameraFacing()">
                                <i class="fas fa-camera-rotate me-1"></i> Ön/Arka Kamera
                            </button>
                            <!-- Direct Photo / Camera Capture Fallback -->
                            <label class="btn btn-sm btn-success rounded-pill px-3 mb-0 cursor-pointer shadow-sm">
                                <i class="fas fa-camera me-1"></i> Fotoğraftan Tara
                                <input type="file" id="qr-file-input" accept="image/*" capture="environment" class="d-none" onchange="scanQRFromFile(this)">
                            </label>
                        </div>
                    </div>
                    <span class="text-white-50 small d-block"><i class="fas fa-info-circle me-1"></i> Randevu veya Üyelik QR kodunuzu kameraya gösterin</span>
                </div>

                <!-- TAB 3: TOUCHLESS DYNAMIC QR VIEW (For Smartphone Scan) -->
                <div id="view-mobile-mode" class="d-none text-center">
                    <div class="touchless-qr-card mb-3 mx-auto" id="kiosk-touchless-qr-container"></div>
                    <h5 class="fw-bold text-white mb-1">Telefonunuzla Tarayın</h5>
                    <p class="text-white-50 small mb-0 px-2">
                        Kiosk ekranına dokunmadan kendi telefonunuzun kamerasıyla bu QR kodu okutarak giriş ve çıkış işlemlerinizi hemen yapabilirsiniz.
                    </p>
                </div>

            </div>
        </div>
    </main>

    <!-- Kiosk Footer Note -->
    <footer class="kiosk-footer-note">
        BooKi Self-Service Terminal &bull; Numara girdiğinizde içeride değilseniz giriş, içerideyseniz otomatik çıkış yapılır
    </footer>

    <!-- KIOSK APPLICATION LOGIC -->
    <script>
    let activeTab = 'keypad';
    let phoneBuffer = '';
    let feedbackTimeout = null;
    let html5QrCode = null;
    let currentCameraFacing = 'environment';
    let isScannerRunning = false;
    const touchlessUrl = '<?= $touchless_url ?? site_url('checkin/mobile') ?>';

    window.addEventListener('DOMContentLoaded', () => {
        initTouchlessQR();
        updateClock();
    });

    // Initialize Touchless QR Code
    function initTouchlessQR() {
        const container = document.getElementById('kiosk-touchless-qr-container');
        if (container) {
            container.innerHTML = '';
            new QRCode(container, {
                text: touchlessUrl,
                width: 200,
                height: 200,
                colorDark: '#0f172a',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        }
    }

    // Live Clock
    function updateClock() {
        const clockEl = document.getElementById('kiosk-live-clock');
        if (clockEl) {
            const now = new Date();
            clockEl.innerText = now.toLocaleTimeString('tr-TR');
        }
    }
    setInterval(updateClock, 1000);

    // Tab Switcher
    function switchKioskTab(tab) {
        haptic();
        activeTab = tab;

        document.querySelectorAll('.kiosk-tab-btn').forEach(btn => btn.classList.remove('active'));
        const tabBtn = document.querySelector(`.tab-${tab}`);
        if (tabBtn) tabBtn.classList.add('active');

        const keypadView = document.getElementById('view-keypad-mode');
        const cameraView = document.getElementById('view-camera-mode');
        const mobileView = document.getElementById('view-mobile-mode');

        keypadView.classList.add('d-none');
        cameraView.classList.add('d-none');
        mobileView.classList.add('d-none');
        stopCameraScanner();

        if (tab === 'keypad') {
            keypadView.classList.remove('d-none');
            document.getElementById('kiosk-subtitle').innerText = 'Telefon numaranızı girerek anında giriş veya çıkış yapın';
        } else if (tab === 'camera') {
            cameraView.classList.remove('d-none');
            document.getElementById('kiosk-subtitle').innerText = 'Kameraya QR Kodunuzu Gösterin';
            requestAndStartCamera();
        } else if (tab === 'mobile') {
            mobileView.classList.remove('d-none');
            document.getElementById('kiosk-subtitle').innerText = 'Telefonunuzla Tarayın ve Temassız İşlem Yapın';
            initTouchlessQR();
        }
    }

    // Keypad Handlers
    function pressKey(key) {
        haptic();
        if (phoneBuffer.length < 11) {
            phoneBuffer += key;
            updateDisplay();
        }
    }

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
        hideSuccessBadge();
    }

    function updateDisplay() {
        const el = document.getElementById('kiosk-pass-input');
        if (el) el.value = phoneBuffer;
    }

    function haptic() {
        if (window.navigator && window.navigator.vibrate) {
            try { window.navigator.vibrate(14); } catch (e) {}
        }
    }

    // Fullscreen
    function toggleFullscreen() {
        const icon = document.getElementById('fs-icon');
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(() => {});
            if (icon) { icon.classList.remove('fa-expand'); icon.classList.add('fa-compress'); }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen().catch(() => {});
                if (icon) { icon.classList.remove('fa-compress'); icon.classList.add('fa-expand'); }
            }
        }
    }

    // Physical Keyboard listener
    document.addEventListener('keydown', function(e) {
        if (e.key >= '0' && e.key <= '9') {
            pressKey(e.key);
        } else if (e.key === 'Backspace') {
            deleteOneKey();
        } else if (e.key === 'Escape' || e.key === 'Delete') {
            clearKey();
        } else if (e.key === 'Enter') {
            submitKioskAction();
        }
    });

    // Unified Automatic Giriş / Çıkış Action
    function submitKioskAction(identifierVal = null) {
        const identifier = identifierVal || phoneBuffer;

        if (!identifier || identifier.length < 4) {
            showFeedback('Lütfen geçerli bir telefon numarası girin veya QR okutun.', 'alert-warning');
            return;
        }

        const submitBtn = document.getElementById('btn-kiosk-submit');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Kontrol ediliyor...';
        }

        const fd = new FormData();
        fd.append('identifier', identifier);
        fd.append('action', 'auto'); // Auto mode: Checks in if outside, Checks out if inside!
        fd.append('checkin_method', (identifier.includes('APPT') || identifier.includes('MEMB') || activeTab === 'camera') ? 'qr_kiosk' : 'kiosk');

        fetch('<?= site_url('checkin/do_kiosk_action') ?>', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    playSuccessBeep();
                    showFeedback(data.message || 'İşleminiz başarıyla tamamlandı.', 'alert-success');
                    
                    if (data.action === 'checkin' && data.customer) {
                        showSuccessBadge(data.customer, identifier);
                    } else {
                        hideSuccessBadge();
                    }

                    phoneBuffer = '';
                    updateDisplay();
                } else if (data.status === 'already_inside') {
                    showFeedback(data.message || 'Zaten aktif bir giriş kaydınız bulunmaktadır.', 'alert-info');
                } else {
                    showFeedback(data.message || 'İşlem gerçekleştirilemedi. Resepsiyona danışınız.', 'alert-danger');
                }
            })
            .catch(() => {
                showFeedback('Bağlantı hatası oluştu. Lütfen tekrar deneyiniz.', 'alert-danger');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-right-to-bracket me-2"></i> GİRİŞ / ÇIKIŞ YAP';
                }
            });
    }

    function showSuccessBadge(customer, identifier) {
        const badge = document.getElementById('kiosk-success-badge');
        const nameEl = document.getElementById('success-guest-name');
        const qrBox = document.getElementById('kiosk-checkout-qr-box');
        if (badge && nameEl && qrBox) {
            nameEl.innerText = `✓ Hoş Geldiniz, ${customer.first_name || ''} ${customer.last_name || ''}!`;
            qrBox.innerHTML = '';
            new QRCode(qrBox, {
                text: String(identifier),
                width: 130,
                height: 130,
                colorDark: '#0f172a',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
            badge.classList.remove('d-none');
        }
    }

    function hideSuccessBadge() {
        const badge = document.getElementById('kiosk-success-badge');
        if (badge) badge.classList.add('d-none');
    }

    function showFeedback(msg, cls) {
        if (feedbackTimeout) clearTimeout(feedbackTimeout);
        const fb = document.getElementById('kiosk-feedback');
        if (!fb) return;
        fb.className = 'alert ' + cls + ' mb-3 py-2 py-sm-3 fw-semibold rounded-3 shadow-sm text-start';
        fb.innerHTML = msg;
        fb.classList.remove('d-none');
        feedbackTimeout = setTimeout(() => {
            fb.classList.add('d-none');
        }, 7000);
    }

    // Audio Beeper
    function playSuccessBeep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            osc.start();
            osc.stop(ctx.currentTime + 0.12);
        } catch(e) {}
    }

    // CAMERA QR SCANNER (Html5Qrcode Engine with Clear Permission Guides)
    function requestAndStartCamera() {
        if (isScannerRunning) return;

        const startBtn = document.getElementById('btn-start-camera');
        if (startBtn) {
            startBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Kamera İzni Bekleniyor...';
        }

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("kiosk-qr-reader");
        }

        const qrConfig = { fps: 15, qrbox: { width: 220, height: 220 }, aspectRatio: 1.0 };

        html5QrCode.start(
            { facingMode: currentCameraFacing },
            qrConfig,
            (decodedText, decodedResult) => {
                onQrCodeScanned(decodedText);
            },
            (errorMessage) => {
                // scanning frame
            }
        ).then(() => {
            isScannerRunning = true;
            if (startBtn) {
                startBtn.innerHTML = '<i class="fas fa-check me-2 text-success"></i> Kamera Aktif & Tarıyor';
                startBtn.classList.remove('btn-primary');
                startBtn.classList.add('btn-dark');
            }
        }).catch(err => {
            isScannerRunning = false;
            if (startBtn) {
                startBtn.innerHTML = '<i class="fas fa-video me-2"></i> Kamerayı Tekrar Başlat';
                startBtn.classList.remove('btn-dark');
                startBtn.classList.add('btn-primary');
            }

            const errStr = String(err);
            if (errStr.includes('NotAllowedError') || errStr.includes('Permission denied') || errStr.includes('PermissionDismissedError')) {
                showFeedback(`
                    <div class="small">
                        <strong class="text-danger"><i class="fas fa-ban me-1"></i> Kamera İzni Tarayıcıda Engellenmiş:</strong><br>
                        1. Tarayıcınızın adres çubuğundaki (sol üstteki) <strong>Kilit / Site Ayarları</strong> simgesine tıklayın.<br>
                        2. <strong>Kamera İznini 'İzin Ver'</strong> yapıp sayfayı yenileyin.<br>
                        3. Veya hemen aşağıdaki <strong>"Fotoğraftan Tara"</strong> butonuna basarak doğrudan cihaz kamerasını açabilirsiniz.
                    </div>
                `, 'alert-warning');
            } else {
                showFeedback('Kamera başlatılamadı: ' + errStr, 'alert-danger');
            }
        });
    }

    function stopCameraScanner() {
        if (html5QrCode && isScannerRunning) {
            html5QrCode.stop().then(() => {
                isScannerRunning = false;
                const startBtn = document.getElementById('btn-start-camera');
                if (startBtn) {
                    startBtn.innerHTML = '<i class="fas fa-video me-2"></i> Kamerayı Başlat (İzin İste)';
                    startBtn.classList.remove('btn-dark');
                    startBtn.classList.add('btn-primary');
                }
            }).catch(() => {
                isScannerRunning = false;
            });
        }
    }

    function switchCameraFacing() {
        currentCameraFacing = currentCameraFacing === 'environment' ? 'user' : 'environment';
        if (isScannerRunning) {
            stopCameraScanner();
            setTimeout(requestAndStartCamera, 400);
        }
    }

    function onQrCodeScanned(qrText) {
        haptic();
        playSuccessBeep();
        showFeedback(`📷 QR Kod Okundu: <strong>${qrText.substring(0, 28)}...</strong>`, 'alert-info');

        // Automatically Check-in or Check-out
        submitKioskAction(qrText);

        if (html5QrCode && isScannerRunning) {
            try {
                html5QrCode.pause();
                setTimeout(() => {
                    if (html5QrCode && isScannerRunning) {
                        try { html5QrCode.resume(); } catch(e){}
                    }
                }, 3500);
            } catch(e) {}
        }
    }

    // Direct Native Camera Snapshot / File Fallback
    function scanQRFromFile(input) {
        if (!input.files || input.files.length === 0) return;
        const imageFile = input.files[0];

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("kiosk-qr-reader");
        }

        showFeedback('Fotoğraf analiz ediliyor...', 'alert-info');

        html5QrCode.scanFile(imageFile, true)
            .then(decodedText => {
                onQrCodeScanned(decodedText);
            })
            .catch(err => {
                showFeedback('Fotoğrafta geçerli bir QR kod algılanamadı. Lütfen daha net çekiniz.', 'alert-warning');
            });
    }
    </script>
</body>
</html>
