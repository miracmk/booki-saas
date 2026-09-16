<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <title>BooKi - Admin</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #1b1f24;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 24px rgba(0,0,0,.25);
            padding: 2.5rem;
            width: 100%;
            max-width: 380px;
        }
        h1 { font-size: 1.35rem; margin: 0 0 .3rem; color: #222; }
        p.hint { color: #666; font-size: .85rem; margin: 0 0 1.5rem; }
        label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: .4rem; color: #333; }
        input { width: 100%; padding: .6rem .75rem; border: 1px solid #d7d9dd; border-radius: 8px; font-size: 1rem; margin-bottom: 1rem; }
        button { width: 100%; padding: .7rem; background: #1b1f24; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; }
        button:disabled { opacity: .6; cursor: default; }
        .msg { font-size: .85rem; margin-bottom: 1rem; display: none; color: #c0392b; }
    </style>
</head>
<body>
    <div class="card">
        <h1>BooKi</h1>
        <p class="hint">SaaS Yönetim Paneli</p>
        <div class="msg" id="msg"></div>
        <form id="superadmin-login-form">
            <label for="username">Kullanıcı adı</label>
            <input type="text" id="username" required autofocus>
            <label for="password">Şifre</label>
            <input type="password" id="password" required>
            <button type="submit" id="submit-btn">Giriş Yap</button>
        </form>
    </div>

    <script>
        document.getElementById('superadmin-login-form').addEventListener('submit', function (event) {
            event.preventDefault();

            const msg = document.getElementById('msg');
            const btn = document.getElementById('submit-btn');

            msg.style.display = 'none';
            btn.disabled = true;

            fetch('<?= site_url('superadmin_auth/validate') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'username=' + encodeURIComponent(document.getElementById('username').value) +
                    '&password=' + encodeURIComponent(document.getElementById('password').value) +
                    '&csrf_token=' + encodeURIComponent('<?= e(vars('csrf_token')) ?>'),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        window.location.href = '<?= site_url('superadmin_tenants') ?>';
                        return;
                    }

                    msg.textContent = (data && data.message) || 'Giriş başarısız.';
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
