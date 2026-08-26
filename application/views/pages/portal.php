<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <title>Ki Reservation</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #f5f6f8;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 16px rgba(0,0,0,.08);
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
        }
        h1 { font-size: 1.35rem; margin: 0 0 .5rem; color: #222; }
        p.hint { color: #666; font-size: .9rem; margin: 0 0 1.5rem; }
        label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: .4rem; color: #333; }
        input[type=text] {
            width: 100%;
            padding: .6rem .75rem;
            border: 1px solid #d7d9dd;
            border-radius: 8px;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        button {
            width: 100%;
            padding: .7rem;
            background: #35A768;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        button:disabled { opacity: .6; cursor: default; }
        .msg { font-size: .85rem; margin-bottom: 1rem; display: none; }
        .msg.error { color: #c0392b; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Ki Reservation</h1>
        <p class="hint">Firma kullanıcı adınızı veya e-postanızı girin, sizi doğru firmaya yönlendirelim.</p>
        <div class="msg error" id="msg"></div>
        <form id="portal-form">
            <label for="identifier">Kullanıcı adı / e-posta</label>
            <input type="text" id="identifier" name="identifier" required autofocus>
            <button type="submit" id="submit-btn">Devam Et</button>
        </form>
    </div>

    <script>
        document.getElementById('portal-form').addEventListener('submit', function (event) {
            event.preventDefault();

            const identifier = document.getElementById('identifier').value.trim();
            const msg = document.getElementById('msg');
            const btn = document.getElementById('submit-btn');

            if (!identifier) {
                return;
            }

            msg.style.display = 'none';
            btn.disabled = true;

            fetch('<?= site_url('portal/find_tenant') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'identifier=' + encodeURIComponent(identifier) +
                    '&csrf_token=' + encodeURIComponent('<?= e(vars('csrf_token')) ?>'),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success && data.login_url) {
                        window.location.href = data.login_url;
                        return;
                    }

                    msg.textContent = (data && data.message) || 'Hesap bulunamadı.';
                    msg.style.display = 'block';
                    btn.disabled = false;
                })
                .catch(() => {
                    msg.textContent = 'Bir hata oluştu, lütfen tekrar deneyin.';
                    msg.style.display = 'block';
                    btn.disabled = false;
                });
        });
    </script>
</body>
</html>
