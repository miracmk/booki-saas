<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title><?= e($company_name) ?> — Hızlı Giriş Kiosk</title>
    <link rel="stylesheet" href="<?= asset_url('assets/css/general.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('assets/css/backend.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('assets/css/ki-command-center.min.css') ?>">
    <script defer src="<?= asset_url('assets/vendor/@fortawesome-fontawesome-free/fontawesome.min.js') ?>"></script>
    <script defer src="<?= asset_url('assets/vendor/@fortawesome-fontawesome-free/solid.min.js') ?>"></script>
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            color: #ffffff;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .kiosk-card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
        }
        .num-pad-btn {
            height: 75px;
            font-size: 28px;
            font-weight: bold;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            transition: all 0.15s ease;
        }
        .num-pad-btn:active {
            transform: scale(0.96);
            background: rgba(59, 130, 246, 0.5);
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3" id="kiosk-touch-page">
    <div class="container" style="max-width: 580px;">
        <div class="kiosk-card p-4 p-md-5 shadow-lg text-center">
            <div class="mb-4">
                <img src="<?= e($company_logo) ?>" alt="logo" style="max-height: 60px;" class="mb-2">
                <h3 class="fw-bold mb-0"><?= e($company_name) ?></h3>
                <p class="text-white-50 small">Hoş Geldiniz — Lütfen Giriş Yapın</p>
            </div>

            <!-- Display Screen -->
            <div class="mb-4">
                <input type="text" id="kiosk-pass-input" name="kiosk_phone" class="form-control form-control-lg text-center fw-bold fs-2 text-white bg-dark border-secondary py-3" placeholder="05XX XXX XX XX" readonly>
            </div>

            <!-- Feedback Alert -->
            <div id="kiosk-feedback" class="alert d-none mb-4 py-3 fw-semibold"></div>

            <!-- Number Keypad -->
            <div class="row g-2 mb-4">
                <div class="col-4"><button class="btn num-pad-btn keypad-btn w-100" onclick="pressKey('1')">1</button></div>
                <div class="col-4"><button class="btn num-pad-btn keypad-btn w-100" onclick="pressKey('2')">2</button></div>
                <div class="col-4"><button class="btn num-pad-btn keypad-btn w-100" onclick="pressKey('3')">3</button></div>
                <div class="col-4"><button class="btn num-pad-btn keypad-btn w-100" onclick="pressKey('4')">4</button></div>
                <div class="col-4"><button class="btn num-pad-btn keypad-btn w-100" onclick="pressKey('5')">5</button></div>
                <div class="col-4"><button class="btn num-pad-btn keypad-btn w-100" onclick="pressKey('6')">6</button></div>
                <div class="col-4"><button class="btn num-pad-btn keypad-btn w-100" onclick="pressKey('7')">7</button></div>
                <div class="col-4"><button class="btn num-pad-btn keypad-btn w-100" onclick="pressKey('8')">8</button></div>
                <div class="col-4"><button class="btn num-pad-btn keypad-btn w-100" onclick="pressKey('9')">9</button></div>
                <div class="col-4"><button class="btn num-pad-btn keypad-btn w-100 text-danger" onclick="clearKey()"><i class="fas fa-backspace me-1"></i>Sil</button></div>
                <div class="col-4"><button class="btn num-pad-btn keypad-btn w-100" onclick="pressKey('0')">0</button></div>
                <div class="col-4"><button class="btn num-pad-btn keypad-btn w-100 text-success" onclick="submitKioskCheckin()"><i class="fas fa-check"></i></button></div>
            </div>

            <!-- Big Checkin Button -->
            <button class="btn btn-primary btn-lg w-100 py-3 fs-4 fw-bold rounded-4 shadow" onclick="submitKioskCheckin()">
                <i class="fas fa-sign-in-alt me-2"></i> GİRİŞ YAP
            </button>
        </div>
    </div>

    <script>
    let phoneBuffer = '';

    function pressKey(key) {
        if (phoneBuffer.length < 11) {
            phoneBuffer += key;
            updateDisplay();
        }
    }

    function clearKey() {
        phoneBuffer = '';
        updateDisplay();
    }

    function updateDisplay() {
        const el = document.getElementById('kiosk-pass-input') || document.getElementById('kiosk-phone-display');
        if (el) el.value = phoneBuffer;
    }

    function submitKioskCheckin() {
        if (phoneBuffer.length < 10) {
            showFeedback('Lütfen geçerli bir telefon numarası girin.', 'alert-warning');
            return;
        }

        const fd = new FormData();
        fd.append('phone', phoneBuffer);
        fd.append('checkin_method', 'kiosk');

        fetch('<?= site_url('checkin/do_checkin') ?>', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showFeedback(`Hoş geldiniz ${data.customer.first_name}! Girişiniz başarıyla yapıldı.`, 'alert-success');
                    phoneBuffer = '';
                    updateDisplay();
                } else if (data.status === 'already_inside') {
                    showFeedback(data.message, 'alert-info');
                } else {
                    showFeedback(data.message || 'Giriş yapılamadı.', 'alert-danger');
                }
            });
    }

    function showFeedback(msg, cls) {
        const fb = document.getElementById('kiosk-feedback');
        fb.className = 'alert ' + cls + ' mb-4 py-3 fw-semibold';
        fb.innerText = msg;
        fb.classList.remove('d-none');
        setTimeout(() => fb.classList.add('d-none'), 5000);
    }
    </script>
</body>
</html>
