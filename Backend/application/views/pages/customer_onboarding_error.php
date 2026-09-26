<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BooKi - Onboarding</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', -apple-system, sans-serif; }
        body { background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 40px; max-width: 520px; text-align: center; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
        .icon-box { width: 64px; height: 64px; background: #fee2e2; color: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 20px; }
        h1 { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 12px; }
        p { font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 24px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; background: #2563eb; color: white; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; }
        .btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-box">⚠️</div>
        <h1><?= htmlspecialchars($title) ?></h1>
        <p><?= htmlspecialchars($message) ?></p>
        <a href="https://kibusiness.co" class="btn">BooKi Ana Sayfasına Git</a>
    </div>
</body>
</html>

