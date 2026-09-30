<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>BooKi PDKS — Personel Hızlı Giriş & Çıkış Terminali</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/themes/default.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/general.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/backend.min.css') ?>">
    <script defer src="<?= base_url('assets/vendor/@fortawesome-fontawesome-free/fontawesome.min.js') ?>"></script>
    <script defer src="<?= base_url('assets/vendor/@fortawesome-fontawesome-free/solid.min.js') ?>"></script>
    <style>
        :root {
            --kiosk-bg: #0f172a;
            --kiosk-card: #1e293b;
            --kiosk-primary: #3b82f6;
            --kiosk-success: #10b981;
            --kiosk-danger: #ef4444;
        }
        body {
            margin: 0;
            padding: 0;
            background: radial-gradient(circle at 50% 20%, #1e293b 0%, var(--kiosk-bg) 100%);
            color: #f8fafc;
            font-family: system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
        }
        .kiosk-card {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 480px;
            padding: 2.5rem;
            text-align: center;
        }
        .pin-display {
            font-size: 2.5rem;
            letter-spacing: 0.6rem;
            background: #0f172a;
            border: 2px solid rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            padding: 0.8rem;
            margin-bottom: 1.5rem;
            color: #38bdf8;
            font-weight: bold;
            min-height: 75px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .numpad-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 1.5rem;
        }
        .numpad-btn {
            background: #334155;
            color: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            font-size: 1.8rem;
            font-weight: 600;
            padding: 1rem 0;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .numpad-btn:active {
            background: var(--kiosk-primary);
            transform: scale(0.96);
        }
        .punch-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .btn-punch-in {
            background: var(--kiosk-success);
            color: white;
            border: none;
            border-radius: 16px;
            padding: 1.2rem;
            font-size: 1.25rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-punch-out {
            background: var(--kiosk-danger);
            color: white;
            border: none;
            border-radius: 16px;
            padding: 1.2rem;
            font-size: 1.25rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-punch-in:active, .btn-punch-out:active {
            transform: scale(0.97);
        }
        .toast-msg {
            display: none;
            padding: 1rem;
            border-radius: 12px;
            margin-top: 1.2rem;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="kiosk-card">
    <div class="mb-3">
        <h2 class="fw-bold mb-1">🏢 PDKS Personel Terminali</h2>
        <div id="kioskClock" class="fs-4 text-info fw-bold mb-1">--:--:--</div>
        <small class="text-white-50">Lütfen 4-6 haneli Kiosk PIN kodunuzu giriniz</small>
    </div>

    <div class="pin-display" id="pinDisplay">&bull;&bull;&bull;&bull;</div>

    <div class="numpad-grid">
        <button class="numpad-btn" data-key="1">1</button>
        <button class="numpad-btn" data-key="2">2</button>
        <button class="numpad-btn" data-key="3">3</button>
        <button class="numpad-btn" data-key="4">4</button>
        <button class="numpad-btn" data-key="5">5</button>
        <button class="numpad-btn" data-key="6">6</button>
        <button class="numpad-btn" data-key="7">7</button>
        <button class="numpad-btn" data-key="8">8</button>
        <button class="numpad-btn" data-key="9">9</button>
        <button class="numpad-btn text-danger" id="btnClear"><i class="fas fa-trash"></i></button>
        <button class="numpad-btn" data-key="0">0</button>
        <button class="numpad-btn text-warning" id="btnBack"><i class="fas fa-backspace"></i></button>
    </div>

    <div class="punch-actions">
        <button class="btn-punch-in" id="btnIn">
            <i class="fas fa-sign-in-alt me-1"></i> GİRİŞ (IN)
        </button>
        <button class="btn-punch-out" id="btnOut">
            <i class="fas fa-sign-out-alt me-1"></i> ÇIKIŞ (OUT)
        </button>
    </div>

    <div id="toastFeedback" class="toast-msg"></div>
</div>

<script>
let currentPin = '';

function updateDisplay() {
    const disp = document.getElementById('pinDisplay');
    if (!currentPin) {
        disp.innerHTML = '<span style="opacity:0.3">&bull;&bull;&bull;&bull;</span>';
    } else {
        disp.innerText = '&bull;'.repeat(currentPin.length).replace(/&bull;/g, '•');
    }
}

document.querySelectorAll('.numpad-btn[data-key]').forEach(b => {
    b.addEventListener('click', () => {
        if (currentPin.length < 8) {
            currentPin += b.dataset.key;
            updateDisplay();
        }
    });
});

document.getElementById('btnClear').addEventListener('click', () => {
    currentPin = '';
    updateDisplay();
});

document.getElementById('btnBack').addEventListener('click', () => {
    currentPin = currentPin.slice(0, -1);
    updateDisplay();
});

// Clock
setInterval(() => {
    const d = new Date();
    document.getElementById('kioskClock').innerText = d.toTimeString().split(' ')[0];
}, 1000);

async function submitKioskPunch(type) {
    if (!currentPin) {
        showToast('Lütfen önce PIN kodunuzu tuşlayınız.', false);
        return;
    }

    const fd = new FormData();
    fd.append('pin_code', currentPin);
    fd.append('punch_type', type);

    try {
        const res = await fetch('<?= site_url("attendance/kiosk_punch") ?>', { method: 'POST', body: fd });
        const json = await res.json();
        showToast(json.message, json.success);
        if (json.success) {
            currentPin = '';
            updateDisplay();
        }
    } catch(e) {
        showToast('İletişim hatası oluştu.', false);
    }
}

function showToast(msg, ok) {
    const t = document.getElementById('toastFeedback');
    t.style.display = 'block';
    t.innerText = msg;
    t.style.backgroundColor = ok ? 'rgba(16, 185, 129, 0.25)' : 'rgba(239, 68, 68, 0.25)';
    t.style.border = ok ? '1px solid #10b981' : '1px solid #ef4444';
    t.style.color = ok ? '#34d399' : '#f87171';
    setTimeout(() => { t.style.display = 'none'; }, 4000);
}

document.getElementById('btnIn').addEventListener('click', () => submitKioskPunch('IN'));
document.getElementById('btnOut').addEventListener('click', () => submitKioskPunch('OUT'));
</script>

</body>
</html>
