<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ki Reservation - Admin</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f5f6f8; margin: 0; color: #222; }
        header { background: #1b1f24; color: #fff; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 1.1rem; margin: 0; }
        header a { color: #ccc; text-decoration: none; font-size: .85rem; margin-left: 1rem; }
        main { padding: 1.5rem; max-width: 600px; margin: 0 auto; }
        .card { background: #fff; border-radius: 10px; padding: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        label { display: block; font-size: .85rem; font-weight: 600; margin: 1rem 0 .3rem; }
        input { width: 100%; padding: .55rem .7rem; border: 1px solid #d7d9dd; border-radius: 6px; font-size: .9rem; }
        button { background: #1b1f24; color: #fff; border: none; border-radius: 6px; padding: .6rem 1.2rem; font-weight: 600; cursor: pointer; margin-top: 1.2rem; }
        .hint { color: #666; font-size: .8rem; }
        .msg { font-size: .85rem; margin-top: .8rem; display: none; }
        .msg.ok { color: #1e8a4c; }
        .msg.err { color: #c0392b; }
    </style>
</head>
<body>
    <header>
        <h1>Ki Reservation — SaaS Yönetimi</h1>
        <div>
            <a href="<?= site_url('superadmin_tenants') ?>">Kiracılar</a>
            <a href="<?= site_url('superadmin_auth/logout') ?>">Çıkış</a>
        </div>
    </header>

    <main>
        <div class="card">
            <h2 style="font-size:1.05rem;margin-top:0;">Ki Business Google OAuth</h2>
            <p class="hint">
                Buraya girilen Client ID/Secret, kendi Google Cloud projesi olmayan TÜM kiracılar için
                paylaşılan (ortak) Google Takvim bağlantı kimliği olarak kullanılır. Her yeni kiracının
                kendi callback URL'ini (<code>https://{subdomain}-reservationapp.kibusiness.co/index.php/google/oauth_callback</code>)
                bu OAuth Client'ın Google Cloud Console'daki "Authorized redirect URIs" listesine
                eklemeniz gerekir.
            </p>
            <form id="settings-form">
                <label>Google Client ID</label>
                <input type="text" id="google_client_id" value="<?= e(vars('google_client_id')) ?>">
                <label>
                    Google Client Secret
                    <?= vars('google_client_secret_set') ? '<span class="hint">(zaten kayıtlı - değiştirmek için doldurun)</span>' : '' ?>
                </label>
                <input type="password" id="google_client_secret" placeholder="<?= vars('google_client_secret_set') ? '••••••••' : '' ?>">
                <div class="msg" id="settings-msg"></div>
                <button type="submit">Kaydet</button>
            </form>
        </div>
    </main>

    <script>
        document.getElementById('settings-form').addEventListener('submit', function (event) {
            event.preventDefault();
            const msg = document.getElementById('settings-msg');

            const params = new URLSearchParams({
                csrf_token: '<?= e(vars('csrf_token')) ?>',
                google_client_id: document.getElementById('google_client_id').value,
                google_client_secret: document.getElementById('google_client_secret').value,
            });

            fetch('<?= site_url('superadmin_settings/save') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString(),
            })
                .then((r) => r.json())
                .then((data) => {
                    msg.style.display = 'block';
                    msg.className = 'msg ' + (data.success ? 'ok' : 'err');
                    msg.textContent = data.success ? 'Kaydedildi.' : (data.message || 'Hata oluştu.');
                    if (data.success) {
                        window.location.reload();
                    }
                });
        });
    </script>
</body>
</html>
