<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(setting('company_name') ?: 'BooKi') ?> - Randevularım</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f5f6f8; margin: 0; color: #222; }
        header { background: #35A768; color: #fff; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 1.05rem; margin: 0; }
        header a { color: #eafff0; text-decoration: none; font-size: .85rem; }
        main { padding: 1.5rem; max-width: 760px; margin: 0 auto; }
        .card { background: #fff; border-radius: 10px; padding: 1.2rem 1.4rem; margin-bottom: 1.2rem; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        .card h2 { font-size: .95rem; margin: 0 0 .8rem; color: #444; }
        .appt { border-bottom: 1px solid #eee; padding: .6rem 0; font-size: .88rem; }
        .appt:last-child { border-bottom: none; }
        .appt .when { font-weight: 600; }
        .appt .meta { color: #666; font-size: .8rem; }
        .empty { color: #999; font-size: .85rem; }
        label { display: block; font-size: .8rem; font-weight: 600; margin: .6rem 0 .3rem; }
        input { width: 100%; padding: .5rem .6rem; border: 1px solid #d7d9dd; border-radius: 6px; font-size: .88rem; }
        button { background: #35A768; color: #fff; border: none; border-radius: 6px; padding: .6rem 1rem; font-weight: 600; cursor: pointer; margin-top: .8rem; }
        .msg { font-size: .82rem; margin-top: .5rem; display: none; }
        .msg.ok { color: #1e8a4c; }
        .msg.err { color: #c0392b; }
    </style>
</head>
<body>
    <header>
        <h1><?= e(setting('company_name') ?: 'BooKi') ?></h1>
        <a href="<?= site_url('logout') ?>">Çıkış</a>
    </header>

    <main>
        <div class="card">
            <h2>Yaklaşan Randevularım</h2>
            <?php if (empty(vars('upcoming_appointments'))): ?>
                <div class="empty">Yaklaşan bir randevunuz yok.</div>
            <?php else: ?>
                <?php foreach (vars('upcoming_appointments') as $a): ?>
                    <div class="appt">
                        <div class="when"><?= e(date('d.m.Y H:i', strtotime($a['start_datetime']))) ?></div>
                        <div class="meta"><?= e($a['service_name']) ?> — <?= e($a['provider_name']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Kalan Seanslarım</h2>
            <?php if (empty(vars('customer_packages'))): ?>
                <div class="empty">Hiçbir paketiniz yok.</div>
            <?php else: ?>
                <?php foreach (vars('customer_packages') as $pkg): ?>
                    <div class="appt">
                        <div class="when">
                            Hizmet: <?= e($pkg['id_services']) ?> — Kalan: <strong><?= e($pkg['total_sessions'] - $pkg['used_sessions']) ?>/<?= e($pkg['total_sessions']) ?></strong>
                        </div>
                        <div class="meta">
                            Durum: <?= e($pkg['status']) ?>
                            <?php if ($pkg['expires_at']): ?>
                                — Sona Eriş: <?= e(date('d.m.Y', strtotime($pkg['expires_at']))) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Geçmiş Randevularım</h2>
            <?php if (empty(vars('past_appointments'))): ?>
                <div class="empty">Geçmiş randevunuz yok.</div>
            <?php else: ?>
                <?php foreach (vars('past_appointments') as $a): ?>
                    <div class="appt">
                        <div class="when"><?= e(date('d.m.Y H:i', strtotime($a['start_datetime']))) ?></div>
                        <div class="meta"><?= e($a['service_name']) ?> — <?= e($a['provider_name']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Bilgilerim</h2>
            <form id="profile-form">
                <label>Ad</label>
                <input type="text" id="first_name" value="<?= e(vars('customer')['first_name'] ?? '') ?>" required>
                <label>Soyad</label>
                <input type="text" id="last_name" value="<?= e(vars('customer')['last_name'] ?? '') ?>">
                <label>E-posta</label>
                <input type="email" id="email" value="<?= e(vars('customer')['email'] ?? '') ?>">
                <label>Telefon</label>
                <input type="text" id="phone_number" value="<?= e(vars('customer')['phone_number'] ?? '') ?>">
                <label>Adres</label>
                <input type="text" id="address" value="<?= e(vars('customer')['address'] ?? '') ?>">
                <label>Şehir</label>
                <input type="text" id="city" value="<?= e(vars('customer')['city'] ?? '') ?>">
                <div class="msg" id="profile-msg"></div>
                <button type="submit">Kaydet</button>
            </form>
        </div>

        <div class="card">
            <h2>Kişisel Verilerim (KVKK)</h2>
            <p class="meta" style="margin: 0 0 .8rem;">
                Verilerinizin bir kopyasını indirebilir veya hesabınızın anonimleştirilmesini talep edebilirsiniz.
            </p>
            <button type="button" id="kvkk-export-btn">Verilerimi Dışa Aktar</button>
            <button type="button" id="kvkk-erasure-btn" style="background:#c0392b; margin-left:.5rem;">Hesabımı Sil</button>
            <div class="msg" id="kvkk-msg"></div>
            <div id="kvkk-requests" style="margin-top: 1rem;"></div>
        </div>

        <div class="card">
            <h2>Şifre Değiştir</h2>
            <form id="password-form">
                <label>Mevcut Şifre</label>
                <input type="password" id="current_password" required>
                <label>Yeni Şifre (en az 8 karakter)</label>
                <input type="password" id="new_password" minlength="8" required>
                <div class="msg" id="password-msg"></div>
                <button type="submit">Şifreyi Değiştir</button>
            </form>
        </div>
    </main>

    <script>
        document.getElementById('profile-form').addEventListener('submit', function (event) {
            event.preventDefault();
            const msg = document.getElementById('profile-msg');
            msg.style.display = 'none';

            const params = new URLSearchParams({
                csrf_token: '<?= e(vars('csrf_token')) ?>',
                first_name: document.getElementById('first_name').value,
                last_name: document.getElementById('last_name').value,
                email: document.getElementById('email').value,
                phone_number: document.getElementById('phone_number').value,
                address: document.getElementById('address').value,
                city: document.getElementById('city').value,
            });

            fetch('<?= site_url('customer_portal/update_profile') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString(),
            })
                .then((r) => r.json())
                .then((data) => {
                    msg.className = 'msg ' + (data.success ? 'ok' : 'err');
                    msg.textContent = data.success ? 'Kaydedildi.' : (data.message || 'Hata oluştu.');
                    msg.style.display = 'block';
                });
        });

        document.getElementById('password-form').addEventListener('submit', function (event) {
            event.preventDefault();
            const msg = document.getElementById('password-msg');
            msg.style.display = 'none';

            const params = new URLSearchParams({
                csrf_token: '<?= e(vars('csrf_token')) ?>',
                current_password: document.getElementById('current_password').value,
                new_password: document.getElementById('new_password').value,
            });

            fetch('<?= site_url('customer_portal/change_password') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString(),
            })
                .then((r) => r.json())
                .then((data) => {
                    msg.className = 'msg ' + (data.success ? 'ok' : 'err');
                    msg.textContent = data.success ? 'Şifreniz değiştirildi.' : (data.message || 'Hata oluştu.');
                    msg.style.display = 'block';
                    if (data.success) {
                        document.getElementById('password-form').reset();
                    }
                });
        });
        const kvkkMsg = document.getElementById('kvkk-msg');
        const kvkkRequestsEl = document.getElementById('kvkk-requests');
        const kvkkStatusLabels = {
            pending: 'Bekliyor',
            processing: 'Hazırlanıyor',
            ready: 'Hazır',
            failed: 'Başarısız',
            expired: 'Süresi Doldu',
            completed: 'Tamamlandı',
        };

        function kvkkShowMessage(ok, text) {
            kvkkMsg.className = 'msg ' + (ok ? 'ok' : 'err');
            kvkkMsg.textContent = text;
            kvkkMsg.style.display = 'block';
        }

        function loadKvkkRequests() {
            fetch('<?= site_url('customer_portal/data_requests') ?>')
                .then((r) => r.json())
                .then((data) => {
                    if (!data.success) {
                        return;
                    }

                    if (!data.requests.length) {
                        kvkkRequestsEl.innerHTML = '<div class="empty">Henüz bir talebiniz yok.</div>';
                        return;
                    }

                    kvkkRequestsEl.innerHTML = data.requests.map(function (req) {
                        const typeLabel = req.request_type === 'export' ? 'Dışa Aktarma' : 'Silme';
                        const statusLabel = kvkkStatusLabels[req.status] || req.status;
                        let resendButton = '';

                        if (req.request_type === 'export' && req.status === 'ready') {
                            resendButton = '<button type="button" class="kvkk-resend-btn" data-id="' + req.id + '" style="margin-top:.4rem;">Bağlantıyı Yeniden Gönder</button>';
                        }

                        return '<div class="appt"><div class="when">' + typeLabel + ' — ' + statusLabel + '</div>' +
                            '<div class="meta">' + req.created_at + '</div>' + resendButton + '</div>';
                    }).join('');

                    kvkkRequestsEl.querySelectorAll('.kvkk-resend-btn').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            kvkkResendLink(btn.getAttribute('data-id'));
                        });
                    });
                });
        }

        function kvkkResendLink(requestId) {
            const params = new URLSearchParams({
                csrf_token: '<?= e(vars('csrf_token')) ?>',
                request_id: requestId,
            });

            fetch('<?= site_url('customer_portal/request_download_link') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString(),
            })
                .then((r) => r.json())
                .then((data) => {
                    kvkkShowMessage(data.success, data.success ? 'İndirme bağlantısı e-posta adresinize gönderildi.' : (data.message || 'Hata oluştu.'));
                });
        }

        document.getElementById('kvkk-export-btn').addEventListener('click', function () {
            fetch('<?= site_url('customer_portal/request_export') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ csrf_token: '<?= e(vars('csrf_token')) ?>' }).toString(),
            })
                .then((r) => r.json())
                .then((data) => {
                    kvkkShowMessage(data.success, data.success ? 'Talebiniz alındı. Hazır olduğunda e-posta ile bilgilendirileceksiniz.' : (data.message || 'Hata oluştu.'));
                    if (data.success) {
                        loadKvkkRequests();
                    }
                });
        });

        document.getElementById('kvkk-erasure-btn').addEventListener('click', function () {
            if (!confirm('Hesabınızın anonimleştirilmesini talep etmek istediğinizden emin misiniz? Bu işlem geri alınamaz ve işletme tarafından onaylanması gerekir.')) {
                return;
            }

            fetch('<?= site_url('customer_portal/request_erasure') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ csrf_token: '<?= e(vars('csrf_token')) ?>' }).toString(),
            })
                .then((r) => r.json())
                .then((data) => {
                    kvkkShowMessage(data.success, data.success ? 'Silme talebiniz alındı ve incelenecektir.' : (data.message || 'Hata oluştu.'));
                    if (data.success) {
                        loadKvkkRequests();
                    }
                });
        });

        loadKvkkRequests();
    </script>
</body>
</html>
