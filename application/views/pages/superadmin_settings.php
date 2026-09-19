<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BooKi - Admin</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f5f6f8; margin: 0; color: #222; }
        header { background: #1b1f24; color: #fff; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 1.1rem; margin: 0; }
        header a { color: #ccc; text-decoration: none; font-size: .85rem; margin-left: 1rem; }
        main { padding: 1.5rem; max-width: 680px; margin: 0 auto; }
        .card { background: #fff; border-radius: 10px; padding: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,.06); margin-bottom: 1.5rem; }
        label { display: block; font-size: .85rem; font-weight: 600; margin: 1rem 0 .3rem; }
        input, select { width: 100%; padding: .55rem .7rem; border: 1px solid #d7d9dd; border-radius: 6px; font-size: .9rem; background: #fff; }
        button { background: #1b1f24; color: #fff; border: none; border-radius: 6px; padding: .6rem 1.2rem; font-weight: 600; cursor: pointer; margin-top: 1.2rem; }
        button:hover { background: #333; }
        .hint { color: #666; font-size: .8rem; line-height: 1.4; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .badge-free { background: #e6f4ea; color: #137333; }
        .msg { font-size: .85rem; margin-top: .8rem; display: none; }
        .msg.ok { color: #1e8a4c; }
        .msg.err { color: #c0392b; }
    </style>
</head>
<body>
    <header>
        <h1>BooKi — SaaS Platform Yönetimi</h1>
        <div>
            <a href="<?= site_url('superadmin_tenants') ?>">Kiracılar</a>
            <a href="<?= site_url('superadmin_auth/logout') ?>">Çıkış</a>
        </div>
    </header>

    <main>
        <!-- AI ASİSTAN & LLM SAĞLAYICILARI KARTI -->
        <div class="card" style="border-top: 4px solid #1a73e8;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <h2 style="font-size:1.15rem;margin-top:0;color:#1a73e8;">🤖 AI Asistan & LLM API Anahtarları</h2>
                <span class="badge badge-free">Free Tier Uyumlu</span>
            </div>
            <p class="hint">
                WhatsApp, Telegram, Instagram ve panel içi AI Asistan yanıtları için ortak LLM sağlayıcı API anahtarları.
                Platform genelinde tanımlanan bu anahtarlar, tüm kiracılarda otomatik fallback sırasıyla kullanılır.
            </p>
            <form id="ai-settings-form">
                <label>Aktif AI Sağlayıcı Tercihi</label>
                <select id="ai_provider">
                    <option value="auto" <?= vars('ai_provider') === 'auto' ? 'selected' : '' ?>>Otomatik Fallback (Önce Google -> Groq -> OpenRouter -> OpenAI -> Anthropic)</option>
                    <option value="google" <?= vars('ai_provider') === 'google' ? 'selected' : '' ?>>Google AI Studio (Gemini - Free Tier)</option>
                    <option value="groq" <?= vars('ai_provider') === 'groq' ? 'selected' : '' ?>>Groq (Llama 3.3 - Free Tier Hızlı)</option>
                    <option value="openrouter" <?= vars('ai_provider') === 'openrouter' ? 'selected' : '' ?>>OpenRouter (Çoklu Model & Free Modeller)</option>
                    <option value="openai" <?= vars('ai_provider') === 'openai' ? 'selected' : '' ?>>OpenAI (GPT-4o Mini / GPT-4o)</option>
                    <option value="anthropic" <?= vars('ai_provider') === 'anthropic' ? 'selected' : '' ?>>Anthropic (Claude 3.5 Sonnet / Haiku)</option>
                </select>

                <hr style="margin:1.2rem 0;border:none;border-top:1px solid #eee;">

                <!-- Google Gemini / AI Studio -->
                <label>
                    Google AI Studio / Gemini API Key
                    <?= vars('google_ai_key_set') ? '<span class="hint" style="color:#137333;">(Kayıtlı ✓)</span>' : '<span class="hint">(aistudio.google.com ücretsiz API)</span>' ?>
                </label>
                <input type="password" id="google_ai_key" placeholder="<?= vars('google_ai_key_set') ? '••••••••' : 'AIzaSy...' ?>">
                <label>Google Model</label>
                <input type="text" id="ai_model_google" placeholder="gemini-1.5-flash" value="<?= e(vars('ai_model_google')) ?>">

                <!-- Groq -->
                <label style="margin-top:1.2rem;">
                    Groq API Key
                    <?= vars('groq_api_key_set') ? '<span class="hint" style="color:#137333;">(Kayıtlı ✓)</span>' : '<span class="hint">(console.groq.com ücretsiz ultra hızlı API)</span>' ?>
                </label>
                <input type="password" id="groq_api_key" placeholder="<?= vars('groq_api_key_set') ? '••••••••' : 'gsk_...' ?>">
                <label>Groq Model</label>
                <input type="text" id="ai_model_groq" placeholder="llama-3.3-70b-versatile" value="<?= e(vars('ai_model_groq')) ?>">

                <!-- OpenRouter -->
                <label style="margin-top:1.2rem;">
                    OpenRouter API Key
                    <?= vars('openrouter_api_key_set') ? '<span class="hint" style="color:#137333;">(Kayıtlı ✓)</span>' : '<span class="hint">(openrouter.ai API Key)</span>' ?>
                </label>
                <input type="password" id="openrouter_api_key" placeholder="<?= vars('openrouter_api_key_set') ? '••••••••' : 'sk-or-v1-...' ?>">
                <label>OpenRouter Model</label>
                <input type="text" id="ai_model_openrouter" placeholder="google/gemini-2.0-flash-exp:free" value="<?= e(vars('ai_model_openrouter')) ?>">

                <!-- OpenAI -->
                <label style="margin-top:1.2rem;">
                    OpenAI API Key
                    <?= vars('openai_api_key_set') ? '<span class="hint" style="color:#137333;">(Kayıtlı ✓)</span>' : '<span class="hint">(platform.openai.com)</span>' ?>
                </label>
                <input type="password" id="openai_api_key" placeholder="<?= vars('openai_api_key_set') ? '••••••••' : 'sk-proj-...' ?>">
                <label>OpenAI Model</label>
                <input type="text" id="ai_model_openai" placeholder="gpt-4o-mini" value="<?= e(vars('ai_model_openai')) ?>">

                <!-- Anthropic -->
                <label style="margin-top:1.2rem;">
                    Anthropic Claude API Key
                    <?= vars('anthropic_api_key_set') ? '<span class="hint" style="color:#137333;">(Kayıtlı ✓)</span>' : '<span class="hint">(console.anthropic.com)</span>' ?>
                </label>
                <input type="password" id="anthropic_api_key" placeholder="<?= vars('anthropic_api_key_set') ? '••••••••' : 'sk-ant-...' ?>">
                <label>Anthropic Model</label>
                <input type="text" id="ai_model_anthropic" placeholder="claude-3-5-haiku-20241022" value="<?= e(vars('ai_model_anthropic')) ?>">

                <div class="msg" id="ai-settings-msg"></div>
                <button type="submit" style="background:#1a73e8;">AI Ayarlarını Kaydet</button>
            </form>
        </div>

        <div class="card">
            <h2 style="font-size:1.05rem;margin-top:0;">Ki Business Google OAuth</h2>
            <p class="hint">
                Buraya girilen Client ID/Secret, kendi Google Cloud projesi olmayan TÜM kiracılar için
                paylaşılan (ortak) Google Takvim bağlantı kimliği olarak kullanılır.
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

        <div class="card">
            <h2 style="font-size:1.05rem;margin-top:0;">Platform SMTP / IMAP</h2>
            <p class="hint">
                Bir kiracı kendi SMTP'sini bağlamadığı sürece randevu/hesap e-postaları buradaki
                platform hesabından gönderilir.
            </p>
            <form id="mail-settings-form">
                <label>SMTP Sunucu</label>
                <input type="text" id="platform_smtp_host" placeholder="smtp.example.com" value="<?= e(vars('platform_smtp_host')) ?>">
                <label>SMTP Port</label>
                <input type="text" id="platform_smtp_port" placeholder="587" value="<?= e(vars('platform_smtp_port')) ?>">
                <label>SMTP Şifreleme</label>
                <input type="text" id="platform_smtp_crypto" placeholder="tls / ssl" value="<?= e(vars('platform_smtp_crypto')) ?>">
                <label>SMTP Kullanıcı Adı</label>
                <input type="text" id="platform_smtp_user" placeholder="kullanici@kibusiness.co" value="<?= e(vars('platform_smtp_user')) ?>">
                <label>
                    SMTP Şifre
                    <?= vars('platform_smtp_pass_set') ? '<span class="hint">(zaten kayıtlı - değiştirmek için doldurun)</span>' : '' ?>
                </label>
                <input type="password" id="platform_smtp_pass" placeholder="<?= vars('platform_smtp_pass_set') ? '••••••••' : '' ?>">
                <label>Gönderen Adı</label>
                <input type="text" id="platform_smtp_from_name" placeholder="BooKi" value="<?= e(vars('platform_smtp_from_name')) ?>">
                <label>Gönderen Adresi</label>
                <input type="text" id="platform_smtp_from_address" placeholder="noreply@kibusiness.co" value="<?= e(vars('platform_smtp_from_address')) ?>">

                <hr style="margin:1.5rem 0;border:none;border-top:1px solid #eee;">

                <label>IMAP Sunucu</label>
                <input type="text" id="platform_imap_host" placeholder="imap.example.com" value="<?= e(vars('platform_imap_host')) ?>">
                <label>IMAP Port</label>
                <input type="text" id="platform_imap_port" placeholder="993" value="<?= e(vars('platform_imap_port')) ?>">
                <label>IMAP Şifreleme</label>
                <input type="text" id="platform_imap_crypto" placeholder="ssl / tls" value="<?= e(vars('platform_imap_crypto')) ?>">
                <label>IMAP Kullanıcı Adı</label>
                <input type="text" id="platform_imap_user" placeholder="kullanici@kibusiness.co" value="<?= e(vars('platform_imap_user')) ?>">
                <label>
                    IMAP Şifre
                    <?= vars('platform_imap_pass_set') ? '<span class="hint">(zaten kayıtlı - değiştirmek için doldurun)</span>' : '' ?>
                </label>
                <input type="password" id="platform_imap_pass" placeholder="<?= vars('platform_imap_pass_set') ? '••••••••' : '' ?>">

                <div class="msg" id="mail-settings-msg"></div>
                <button type="submit">Kaydet</button>
            </form>
        </div>
    </main>

    <script>
        // AI Settings Form
        document.getElementById('ai-settings-form').addEventListener('submit', function (event) {
            event.preventDefault();
            const msg = document.getElementById('ai-settings-msg');

            const fieldIds = [
                'ai_provider',
                'google_ai_key', 'ai_model_google',
                'groq_api_key', 'ai_model_groq',
                'openrouter_api_key', 'ai_model_openrouter',
                'openai_api_key', 'ai_model_openai',
                'anthropic_api_key', 'ai_model_anthropic'
            ];

            const data = { csrf_token: '<?= e(vars('csrf_token')) ?>' };
            fieldIds.forEach((id) => {
                const el = document.getElementById(id);
                if (el) data[id] = el.value;
            });

            fetch('<?= site_url('superadmin_settings/save') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data).toString(),
            })
                .then((r) => r.json())
                .then((data) => {
                    msg.style.display = 'block';
                    msg.className = 'msg ' + (data.success ? 'ok' : 'err');
                    msg.textContent = data.success ? 'AI ayarları başarıyla kaydedildi.' : (data.message || 'Hata oluştu.');
                    if (data.success) {
                        setTimeout(() => window.location.reload(), 1000);
                    }
                });
        });

        // Google OAuth Form
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

        // Mail Settings Form
        document.getElementById('mail-settings-form').addEventListener('submit', function (event) {
            event.preventDefault();
            const msg = document.getElementById('mail-settings-msg');

            const fieldIds = ['platform_smtp_host', 'platform_smtp_port', 'platform_smtp_crypto',
                'platform_smtp_user', 'platform_smtp_pass', 'platform_smtp_from_name',
                'platform_smtp_from_address', 'platform_imap_host', 'platform_imap_port',
                'platform_imap_crypto', 'platform_imap_user', 'platform_imap_pass'];

            const data = { csrf_token: '<?= e(vars('csrf_token')) ?>' };
            fieldIds.forEach((id) => { data[id] = document.getElementById(id).value; });

            fetch('<?= site_url('superadmin_settings/save') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data).toString(),
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
