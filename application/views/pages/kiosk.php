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
        /* Mode Switcher Tabs */
        .kiosk-nav-pills {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            background: rgba(15, 23, 42, 0.7);
            padding: 6px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .kiosk-tab-btn {
            border: none;
            background: transparent;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 700;
            padding: 8px 4px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }
        .kiosk-tab-btn i {
            font-size: 15px;
        }
        .kiosk-tab-btn.active.tab-in {
            background: var(--kiosk-success);
            color: #fff;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
        }
        .kiosk-tab-btn.active.tab-out {
            background: var(--kiosk-danger);
            color: #fff;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35);
        }
        .kiosk-tab-btn.active.tab-camera {
            background: #8b5cf6;
            color: #fff;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.35);
        }
        .kiosk-tab-btn.active.tab-mobile {
            background: var(--kiosk-accent);
            color: #fff;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
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
            height: clamp(48px, 8vh, 58px);
            font-size: clamp(16px, 3.6vw, 18px);
            font-weight: 700;
            letter-spacing: 0.5px;
            border-radius: 16px;
            transition: all 0.15s ease;
        }
        .btn-kiosk-in {
            background: var(--kiosk-success) !important;
            border-color: var(--kiosk-success) !important;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4) !important;
        }
        .btn-kiosk-in:active {
            transform: scale(0.98);
        }
        .btn-kiosk-out {
            background: var(--kiosk-danger) !important;
            border-color: var(--kiosk-danger) !important;
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4) !important;
        }
        .btn-kiosk-out:active {
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
            min-height: 260px;
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

        /* Prompt Box */
        .kiosk-inline-checkout-prompt {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 14px;
            padding: 12px;
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
                    <p class="text-white-50 small mb-0" id="kiosk-subtitle">Self-Service Giriş & Çıkış Terminali</p>
                </div>

                <!-- Mode Switcher Tabs -->
                <div class="kiosk-nav-pills mb-3" id="kiosk-tabs">
                    <button type="button" class="kiosk-tab-btn tab-in active" onclick="switchKioskTab('checkin')">
                        <i class="fas fa-sign-in-alt"></i> Giriş Yap
                    </button>
                    <button type="button" class="kiosk-tab-btn tab-out" onclick="switchKioskTab('checkout')">
                        <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                    </button>
                    <button type="button" class="kiosk-tab-btn tab-camera" onclick="switchKioskTab('camera')">
                        <i class="fas fa-camera"></i> QR Tara
                    </button>
                    <button type="button" class="kiosk-tab-btn tab-mobile" onclick="switchKioskTab('mobile')">
                        <i class="fas fa-qrcode"></i> Temassız
                    </button>
                </div>

                <!-- Feedback Alert Box -->
                <div id="kiosk-feedback" class="alert d-none mb-3 py-2 py-sm-3 fw-semibold rounded-3 shadow-sm text-start"></div>

                <!-- Inline Action Box (for when customer is already inside or quick checkout prompt) -->
                <div id="kiosk-prompt-box" class="kiosk-inline-checkout-prompt d-none mb-3 text-center">
                    <p class="text-white small mb-2" id="kiosk-prompt-text"></p>
                    <button type="button" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold" id="kiosk-prompt-btn" onclick="executePromptAction()">
                        <i class="fas fa-sign-out-alt me-1"></i> Şimdi Çıkış Yap
                    </button>
                </div>

                <!-- SUCCESS QR BADGE (Shown after checkin to photograph or use for checkout) -->
                <div id="kiosk-success-badge" class="d-none mb-3 p-3 rounded-3 text-center" style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3);">
                    <div class="text-success fw-bold mb-1 fs-5" id="success-guest-name"></div>
                    <p class="text-white-50 small mb-2">Çıkışta hızlı işlem yapmak için telefon numaranızı veya QR kodunuzu kullanabilirsiniz.</p>
                    <div id="kiosk-checkout-qr-box" class="touchless-qr-card mx-auto"></div>
                </div>

                <!-- TAB 1 & 2: KEYPAD INPUT VIEW (For Check-in & Check-out) -->
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

                    <!-- Big Action Button -->
                    <button type="button" id="btn-kiosk-submit" class="btn btn-kiosk-submit btn-kiosk-in w-100 shadow" onclick="submitKioskAction()">
                        <i class="fas fa-sign-in-alt me-2"></i> <span id="btn-kiosk-text">GİRİŞ YAP</span>
                    </button>
                </div>

                <!-- TAB 3: CAMERA QR SCANNER VIEW (Html5Qrcode Powered) -->
                <div id="view-camera-mode" class="d-none text-center">
                    <div id="kiosk-qr-reader" class="mb-3"></div>

                    <div class="d-flex flex-column gap-2 mb-2">
                        <button type="button" id="btn-start-camera" class="btn btn-primary rounded-pill py-2 fw-bold" onclick="requestAndStartCamera()">
                            <i class="fas fa-video me-2"></i> Kamerayı Başlat (İzin İste)
                        </button>
                        
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="switchCameraFacing()">
                                <i class="fas fa-camera-rotate me-1"></i> Ön/Arka Kamera
                            </button>
                            <!-- File / Native Camera capture fallback -->
                            <label class="btn btn-sm btn-outline-info rounded-pill px-3 mb-0 cursor-pointer">
                                <i class="fas fa-image me-1"></i> Fotoğraftan Tara
                                <input type="file" id="qr-file-input" accept="image/*" capture="environment" class="d-none" onchange="scanQRFromFile(this)">
                            </label>
                        </div>
                    </div>
                    <span class="text-white-50 small d-block"><i class="fas fa-info-circle me-1"></i> Randevu veya Üyelik QR kodunuzu kameraya gösterin</span>
                </div>

                <!-- TAB 4: TOUCHLESS DYNAMIC QR VIEW (For Smartphone Scan) -->
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
        BooKi Self-Service Terminal &bull; Dokunmatik ekran, kamera veya telefonunuzla temassız işlem yapabilirsiniz
    </footer>

    <!-- KIOSK APPLICATION LOGIC -->
    <script>
    let activeTab = 'checkin';
    let phoneBuffer = '';
    let feedbackTimeout = null;
    let html5QrCode = null;
    let currentCameraFacing = 'environment';
    let isScannerRunning = false;
    let promptCustomerId = null;
    const touchlessUrl = '<?= $touchless_url ?? site_url('checkin/mobile') ?>';

    window.addEventListener('DOMContentLoaded', () => {
        initTouchlessQR();
        updateClock();
    });

    // Initialize Touchless QR Code with standard QRCode.js
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
        hidePromptBox();

        // Update Tab Pill UI
        document.querySelectorAll('.kiosk-tab-btn').forEach(btn => btn.classList.remove('active'));
        const tabBtn = document.querySelector(`.tab-${tab === 'checkin' ? 'in' : (tab === 'checkout' ? 'out' : tab)}`);
        if (tabBtn) tabBtn.classList.add('active');

        // View Containers
        const keypadView = document.getElementById('view-keypad-mode');
        const cameraView = document.getElementById('view-camera-mode');
        const mobileView = document.getElementById('view-mobile-mode');
        const submitBtn = document.getElementById('btn-kiosk-submit');
        const submitText = document.getElementById('btn-kiosk-text');

        keypadView.classList.add('d-none');
        cameraView.classList.add('d-none');
        mobileView.classList.add('d-none');
        stopCameraScanner();

        if (tab === 'checkin') {
            keypadView.classList.remove('d-none');
            submitBtn.className = 'btn btn-kiosk-submit btn-kiosk-in w-100 shadow';
            submitText.innerText = 'GİRİŞ YAP';
            document.getElementById('kiosk-subtitle').innerText = 'Telefon Numaranızı Girerek Giriş Yapın';
        } else if (tab === 'checkout') {
            keypadView.classList.remove('d-none');
            submitBtn.className = 'btn btn-kiosk-submit btn-kiosk-out w-100 shadow';
            submitText.innerText = 'ÇIKIŞ YAP';
            document.getElementById('kiosk-subtitle').innerText = 'Telefon Numaranızı Girerek Çıkış Yapın';
        } else if (tab === 'camera') {
            cameraView.classList.remove('d-none');
            document.getElementById('kiosk-subtitle').innerText = 'Kameraya QR Kodunuzu Gösterin';
            // Trigger camera request
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
        hidePromptBox();
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

    // Execute Action (Giriş / Çıkış)
    function submitKioskAction(targetAction = null, identifierVal = null) {
        const action = targetAction || activeTab;
        const identifier = identifierVal || phoneBuffer;

        if (!identifier || identifier.length < 4) {
            showFeedback('Lütfen geçerli bir telefon numarası girin veya QR okutun.', 'alert-warning');
            return;
        }

        const submitBtn = document.getElementById('btn-kiosk-submit');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> İşleniyor...';
        }

        const fd = new FormData();
        fd.append('identifier', identifier);
        fd.append('action', action);
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
                    hidePromptBox();
                } else if (data.status === 'already_inside') {
                    showFeedback(data.message || 'Zaten aktif bir giriş kaydınız bulunmaktadır.', 'alert-info');
                    promptCustomerId = data.customer ? data.customer.id : identifier;
                    showPromptBox(`Sayın ${data.customer ? data.customer.first_name : ''}, şu an içeride görünüyorsunuz. Çıkış yapmak ister misiniz?`, 'checkout');
                } else if (data.status === 'not_inside') {
                    showFeedback(data.message || 'Aktif bir giriş kaydınız bulunmamaktadır.', 'alert-warning');
                    hidePromptBox();
                } else {
                    showFeedback(data.message || 'İşlem gerçekleştirilemedi. Resepsiyona danışınız.', 'alert-danger');
                    hidePromptBox();
                }
            })
            .catch(() => {
                showFeedback('Bağlantı hatası oluştu. Lütfen tekrar deneyiniz.', 'alert-danger');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = activeTab === 'checkin' ? '<i class="fas fa-sign-in-alt me-2"></i> GİRİŞ YAP' : '<i class="fas fa-sign-out-alt me-2"></i> ÇIKIŞ YAP';
                }
            });
    }

    function showPromptBox(text, actionType) {
        const box = document.getElementById('kiosk-prompt-box');
        const txt = document.getElementById('kiosk-prompt-text');
        if (box && txt) {
            txt.innerText = text;
            box.classList.remove('d-none');
        }
    }

    function hidePromptBox() {
        const box = document.getElementById('kiosk-prompt-box');
        if (box) box.classList.add('d-none');
    }

    function executePromptAction() {
        if (promptCustomerId) {
            submitKioskAction('checkout', String(promptCustomerId));
        }
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

    // CAMERA QR SCANNER (Html5Qrcode Engine)
    function requestAndStartCamera() {
        if (isScannerRunning) return;

        const startBtn = document.getElementById('btn-start-camera');
        if (startBtn) {
            startBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Kamera İzni Bekleniyor...';
        }

        // Check Secure Context
        if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
            showFeedback('⚠️ <strong>Uyarı:</strong> Tarayıcı güvenlik politikası gereği kamera erişimi için HTTPS bağlantısı gerekmektedir (Şu an HTTP üzerindesiniz). Kamerayı doğrudan açmak için "Fotoğraftan Tara" butonunu da kullanabilirsiniz.', 'alert-warning');
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
                // scanning frame error (ignore frame misses)
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
            showFeedback('Kamera başlatılamadı: ' + (err || 'Lütfen tarayıcı izinlerinden kameraya izin veriniz.'), 'alert-danger');
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

        // Process Kiosk Action (auto mode or active tab)
        submitKioskAction('auto', qrText);

        // Pause scanner briefly to avoid multiple instant triggers
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

    // Native file input / device camera snapshot fallback
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
                showFeedback('Fotoğrafta geçerli bir QR kod bulunamadı. Lütfen daha net çekiniz.', 'alert-warning');
            });
    }
    </script>
</body>
</html>
