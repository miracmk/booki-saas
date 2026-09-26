<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kurulum Tamamlandı - BooKi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', -apple-system, sans-serif; }
        body { background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 48px 40px; max-width: 560px; text-align: center; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
        .icon-box { width: 72px; height: 72px; background: #dcfce7; color: #16a34a; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 36px; margin: 0 auto 24px; }
        h1 { font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 12px; }
        p { font-size: 15px; color: #64748b; line-height: 1.6; margin-bottom: 28px; }
        .info-pill { background: #f1f5f9; padding: 12px 16px; border-radius: 10px; font-size: 13px; color: #334155; margin-bottom: 28px; text-align: left; }
        .info-pill strong { color: #0f172a; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #2563eb; color: white; padding: 14px 28px; border-radius: 10px; font-size: 15px; font-weight: 700; text-decoration: none; width: 100%; box-shadow: 0 4px 12px rgba(37,99,235,0.25); }
        .btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-box">🎉</div>
        <h1>Tebrikler! Kurulum Tamamlandı</h1>
        <p>İşletmenizin BooKi rezervasyon ve yönetim platformu başarıyla kuruldu ve aktif hale getirildi. Artık randevularınızı almaya hazırsınız!</p>
        
        <div class="info-pill">
            <div>🏢 <strong>İşletme:</strong> <?= htmlspecialchars($tenant['company_name'] ?: $tenant['subdomain']) ?></div>
            <div style="margin-top:4px;">🌐 <strong>Yönetim Paneli Adresiniz:</strong> <a href="<?= htmlspecialchars($portal_url) ?>" target="_blank" style="color:#2563eb;"><?= htmlspecialchars($tenant['subdomain']) ?>.bookiapp.kibusiness.co</a></div>
        </div>

        <a href="<?= htmlspecialchars($portal_url) ?>" class="btn">Yönetim Paneline Giriş Yap →</a>
    </div>
</body>
</html>

