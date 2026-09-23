<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>İşletme Girişi — BooKi</title>
    <link rel="icon" type="image/png" href="<?= asset_url('img/logo-16x16.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #0d9488;
            --primary-dark: #0f766e;
            --primary-light: #f0fdfa;
            --navy: #0f172a;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --bg: #f8fafc;
        }
        body {
            font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: radial-gradient(circle at top right, #e0f2fe 0%, #f8fafc 45%, #f1f5f9 100%);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
        }
        .portal-wrapper {
            width: 100%;
            max-width: 440px;
        }
        .portal-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.08), 0 20px 25px -5px rgba(15, 23, 42, 0.04);
            border: 1px solid var(--border);
            padding: 2.5rem 2.2rem;
            position: relative;
            overflow: hidden;
        }
        .portal-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #0d9488, #3b82f6);
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--navy);
            color: #ffffff;
            font-weight: 800;
            font-size: 0.95rem;
            letter-spacing: 0.04em;
            padding: 0.35rem 0.85rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        .title {
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }
        .subtitle {
            color: var(--text-muted);
            font-size: 0.88rem;
            line-height: 1.5;
            margin-bottom: 1.8rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        label {
            display: block;
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 0.45rem;
        }
        .input-box {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-icon {
            position: absolute;
            left: 1rem;
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
        }
        input[type="text"] {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.6rem;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--navy);
            background: #f8fafc;
            transition: all 0.2s ease;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.12);
        }
        .url-preview {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.78rem;
            color: var(--text-muted);
            word-break: break-all;
            background: #f1f5f9;
            padding: 0.4rem 0.65rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .url-preview strong {
            color: var(--primary-dark);
            font-weight: 700;
        }
        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);
            margin-top: 1rem;
        }
        .btn-submit:hover {
            opacity: 0.95;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(13, 148, 136, 0.35);
        }
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .msg {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            display: none;
            line-height: 1.4;
        }
        .msg.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .msg.info {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .portal-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.82rem;
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .portal-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        .portal-footer a:hover {
            text-decoration: underline;
        }
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="portal-wrapper">
        <div class="portal-card">
            <div class="brand-badge">
                <i class="fas fa-calendar-check"></i> BOO·KI
            </div>
            
            <h1 class="title">İşletme Girişi</h1>
            <p class="subtitle">İşletme kullanıcı adınızı (veya e-postanızı) girerek size özel yönetim paneli giriş ekranına yönlenin.</p>
            
            <div class="msg error" id="msg"></div>
            
            <form id="portal-form" autocomplete="on">
                <div class="form-group">
                    <label for="identifier">İşletme Kullanıcı Adı (Subdomain)</label>
                    <div class="input-box">
                        <i class="fas fa-store input-icon"></i>
                        <input type="text" id="identifier" name="identifier" 
                               placeholder="ornek-isletme" 
                               required autofocus 
                               spellcheck="false" 
                               autocapitalize="none">
                    </div>
                    <span class="url-preview" id="url-preview">
                        Giriş Adresi: <strong id="preview-url">https://...-bookiapp.kibusiness.co/login</strong>
                    </span>
                </div>
                
                <button type="submit" id="submit-btn" class="btn-submit">
                    <span>Giriş Ekranına Git</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="portal-footer">
                <div class="footer-links">
                    <a href="https://booki.kibusiness.co"><i class="fas fa-home me-1"></i> Ana Sayfa (booki.kibusiness.co)</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const appDomain = '<?= getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co' ?>';
        const input = document.getElementById('identifier');
        const previewEl = document.getElementById('preview-url');
        const msg = document.getElementById('msg');
        const btn = document.getElementById('submit-btn');

        function sanitizeSlug(raw) {
            let val = (raw || '').trim().toLowerCase();
            val = val.replace(/^https?:\/\//i, '');
            val = val.replace(/\/.*$/, '');
            val = val.replace(/:\d+$/, '');
            const pattern = appDomain.replace('.', '\\.');
            val = val.replace(new RegExp('[-.]' + pattern + '$', 'i'), '');
            val = val.replace(/^@/, '');
            return val;
        }

        input.addEventListener('input', function () {
            const raw = this.value.trim();
            const slug = sanitizeSlug(raw);
            if (slug && !slug.includes('@') && !slug.includes('.')) {
                previewEl.textContent = 'https://' + slug + '-' + appDomain + '/login';
            } else if (raw) {
                previewEl.textContent = 'İşletme taranıyor...';
            } else {
                previewEl.textContent = 'https://...-' + appDomain + '/login';
            }
        });

        document.getElementById('portal-form').addEventListener('submit', function (event) {
            event.preventDefault();

            const rawVal = input.value.trim();
            if (!rawVal) return;

            msg.style.display = 'none';
            btn.disabled = true;
            btn.innerHTML = '<span>Yönlendiriliyor...</span> <i class="fas fa-spinner fa-spin"></i>';

            fetch('<?= site_url('portal/find_tenant') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'identifier=' + encodeURIComponent(rawVal) +
                    '&csrf_token=' + encodeURIComponent('<?= e(vars('csrf_token')) ?>'),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data && data.success && data.login_url) {
                        window.location.href = data.login_url;
                        return;
                    }

                    // Fallback direct redirection if valid slug format entered
                    const cleanSlug = sanitizeSlug(rawVal);
                    if (cleanSlug && /^[a-z0-9-]+$/.test(cleanSlug)) {
                        window.location.href = 'https://' + cleanSlug + '-' + appDomain + '/login';
                        return;
                    }

                    msg.textContent = (data && data.message) || 'İşletme bulunamadı. Lütfen işletme adınızı kontrol edin.';
                    msg.style.display = 'block';
                    btn.disabled = false;
                    btn.innerHTML = '<span>Giriş Ekranına Git</span> <i class="fas fa-arrow-right"></i>';
                })
                .catch(() => {
                    const cleanSlug = sanitizeSlug(rawVal);
                    if (cleanSlug && /^[a-z0-9-]+$/.test(cleanSlug)) {
                        window.location.href = 'https://' + cleanSlug + '-' + appDomain + '/login';
                        return;
                    }

                    msg.textContent = 'Bağlantı hatası oluştu. Lütfen tekrar deneyin.';
                    msg.style.display = 'block';
                    btn.disabled = false;
                    btn.innerHTML = '<span>Giriş Ekranına Git</span> <i class="fas fa-arrow-right"></i>';
                });
        });
    </script>
</body>
</html>
