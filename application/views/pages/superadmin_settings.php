<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
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

        <!-- BOOTSTRAP MCP (MODEL CONTEXT PROTOCOL) AI SUNUCUSU KARTI -->
        <div class="card" style="border-top: 4px solid #10b981;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <h2 style="font-size:1.15rem;margin-top:0;color:#10b981;">🔌 BooKi MCP (Model Context Protocol) AI Sunucusu</h2>
                <span class="badge" style="background:#d1fae5;color:#065f46;">Aktif / HTTP Streamable</span>
            </div>
            <p class="hint">
                Claude Desktop, Cursor, Windsurf, ElevenLabs AI Voice Agent ve harici ajanların tüm platform kiracılarına (tenant) güvenle bağlanmasını sağlayan merkezi Model Context Protocol sunucusu.
            </p>

            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;margin-top:1rem;">
                <label style="margin-top:0;">Merkezi MCP Sunucu Ucu (Public HTTP Streamable)</label>
                <div style="display:flex;gap:8px;margin-top:4px;">
                    <input type="text" id="mcp_url_input" value="<?= e(vars('mcp_server_url')) ?>" readonly style="background:#fff;font-family:monospace;font-size:0.85rem;">
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('mcp_url_input').value); alert('Kopyalandı!');" style="margin-top:0;padding:.5rem 1rem;white-space:nowrap;background:#10b981;">Kopyala</button>
                </div>

                <label style="margin-top:0.8rem;">İç Docker Ağı Bağlantı Ucu (Internal Service)</label>
                <input type="text" value="<?= e(vars('mcp_internal_url')) ?>" readonly style="background:#f1f5f9;font-family:monospace;font-size:0.85rem;color:#64748b;">

                <label style="margin-top:0.8rem;">Dinamik Kiracı Yönlendirme Formatı</label>
                <p class="hint" style="margin:4px 0 8px;">
                    Harici istemciler herhangi bir kiracıya şu şekillerde bağlanabilir:
                </p>
                <div style="background:#1e293b;color:#f8fafc;padding:.75rem;border-radius:6px;font-family:monospace;font-size:0.8rem;line-height:1.6;">
                    • <strong>URL ile:</strong> <code><?= e(vars('mcp_server_url')) ?>?tenant={subdomain}</code><br>
                    • <strong>Header ile:</strong> <code>X-Tenant: {subdomain}</code><br>
                    • <strong>Yetkilendirme:</strong> <code>Authorization: Bearer {tenant_agent_api_key}</code>
                </div>
            </div>

            <div style="margin-top:1rem;display:flex;gap:10px;">
                <a href="<?= e(vars('mcp_server_url')) ?>" target="_blank" style="display:inline-block;background:#0f172a;color:#fff;text-decoration:none;padding:.5rem 1rem;border-radius:6px;font-size:.85rem;font-weight:600;">
                    🔍 MCP Sağlık Kontrolü (JSON Test)
                </a>
                <a href="<?= site_url('superadmin_tenants') ?>" style="display:inline-block;background:#e2e8f0;color:#1e293b;text-decoration:none;padding:.5rem 1rem;border-radius:6px;font-size:.85rem;font-weight:600;">
                    👥 Kiracı Bazlı MCP Kodlarını Gör
                </a>
            </div>
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

        <!-- META PLATFORM ENTEGRASYONU (WHATSAPP, INSTAGRAM, ADS, FACEBOOK) -->
        <div class="card" style="border-top: 4px solid #1877f2;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <h2 style="font-size:1.05rem;margin-top:0;color:#1877f2;">Meta Platform Entegrasyonu (WhatsApp, Instagram, Ads, Facebook)</h2>
                <span class="badge" style="background:#e7f3ff;color:#1877f2;">Merkezi SaaS</span>
            </div>
            <p class="hint">
                Tüm kiracıların (tenant'ların) WhatsApp, Instagram, Lead Ads ve Meta Business hesaplarını tek bir Meta App üzerinden bağlaması için ortak platform kimlikleri.
            </p>
            <form id="meta-settings-form">
                <label>Meta App ID</label>
                <input type="text" id="meta_app_id" placeholder="123456789012345" value="<?= e(vars('meta_app_id')) ?>">
                <label>
                    Meta App Secret
                    <?= vars('meta_app_secret_set') ? '<span class="hint" style="color:#137333;">(Kayıtlı ✓ - değiştirmek için doldurun)</span>' : '<span class="hint">(developers.facebook.com App Secret)</span>' ?>
                </label>
                <input type="password" id="meta_app_secret" placeholder="<?= vars('meta_app_secret_set') ? '••••••••' : '' ?>">
                <label>
                    Meta Webhook Verify Token (Ortak Doğrulama Anahtarı)
                </label>
                <input type="text" id="meta_webhook_verify_token" value="<?= e(vars('meta_webhook_verify_token')) ?>" placeholder="bookiapp_meta_webhook_secret_2026">
                <span class="hint" style="display:block;margin-top:4px;">Meta Developer Webhook paneline yapıştıracağınız Verify Token. Varsayılan: <code>bookiapp_meta_webhook_secret_2026</code></span>
                <div class="msg" id="meta-settings-msg"></div>
                <button type="submit" style="background:#1877f2;">Meta Ayarlarını Kaydet</button>
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

        <!-- BOO-KI PAZAR YERİ VE SEKTÖREL KOMİSYON YÖNETİMİ -->
        <div class="card" style="border-top: 4px solid #35A768;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <h2 style="font-size:1.15rem;margin-top:0;color:#35A768;">🏪 BooKi Pazar Yeri & Sektörel Komisyon Yönetimi</h2>
                <span class="badge" style="background:#eaf6ef;color:#2a8653;">Marketplace Storefront</span>
            </div>
            <p class="hint">
                Pazar yeri üzerinden alınan doğrudan rezervasyonlarda platform tarafından tahsil edilen ödemelerden kesilecek sektörel komisyon oranlarını yönetin.
                İşletme hak edişleri (Fiyat - Komisyon) günlük periyotlarla mutabakat altına alınır.
            </p>

            <!-- Wallet Aggregate Metrics Bar -->
            <?php $ws = vars('wallet_stats') ?? []; ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(130px, 1fr));gap:10px;margin:1rem 0;background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #e2e8f0;">
                <div>
                    <div style="font-size:0.75rem;color:#64748b;font-weight:600;">TOPLAM HACİM</div>
                    <div style="font-size:1.1rem;font-weight:700;color:#0f172a;">₺<?= number_format($ws['total_earned'] ?? 0, 2) ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem;color:#64748b;font-weight:600;">KESİLEN KOMİSYON</div>
                    <div style="font-size:1.1rem;font-weight:700;color:#35A768;">₺<?= number_format($ws['total_commission'] ?? 0, 2) ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem;color:#64748b;font-weight:600;">BEKLEYEN HAKEDİŞ</div>
                    <div style="font-size:1.1rem;font-weight:700;color:#d97706;">₺<?= number_format($ws['total_balance'] ?? 0, 2) ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem;color:#64748b;font-weight:600;">AKTİF CÜZDAN</div>
                    <div style="font-size:1.1rem;font-weight:700;color:#475569;"><?= (int)($ws['active_wallets'] ?? 0) ?> İşletme</div>
                </div>
            </div>

            <!-- Settlement Action -->
            <div style="display:flex;align-items:center;justify-content:space-between;background:#fffbeb;padding:10px 14px;border-radius:6px;border:1px solid #fef3c7;margin-bottom:1.5rem;">
                <span style="font-size:0.83rem;color:#92400e;">
                    <strong>Günlük Hak Ediş:</strong> Pozitif bakiyesi olan işletmelerin hakediş transfer emrini işletin.
                </span>
                <button type="button" id="btn-process-settlements" style="margin-top:0;background:#d97706;padding:6px 12px;font-size:0.82rem;">
                    ⚡ Hakedişleri İşlet
                </button>
            </div>
            <div class="msg" id="settlement-msg"></div>

            <hr style="margin:1.2rem 0;border:none;border-top:1px solid #eee;">

            <!-- Commission Form -->
            <form id="commission-settings-form">
                <label style="font-size:0.95rem;color:#0f172a;">Genel Varsayılan Komisyon Oranı (%)</label>
                <input type="number" step="0.01" min="0" max="100" id="marketplace_commission_rate" value="<?= e(vars('marketplace_commission_rate')) ?>" style="font-weight:700;font-size:1rem;">
                <span class="hint">Özel oran belirlenmemiş sektörler ve genel işletmeler için uygulanacak komisyon.</span>

                <label style="margin-top:1.5rem;font-size:0.95rem;color:#0f172a;">Sektör Bazlı Komisyon Oranları (%)</label>
                <?php 
                $sec_rates = vars('sector_commission_rates') ?? []; 
                $sectors_info = [
                    'barber' => ['name' => '💇‍♂️ Berber & Erkek Kuaförü', 'desc' => 'Saç kesimi, sakal, bakım'],
                    'beauty_salon' => ['name' => '💅 Güzellik Salonu & Kuaför', 'desc' => 'Bayan kuaförü, cilt bakımı, makyaj'],
                    'nail_studio' => ['name' => '💅 Tırnak & Nail Art Stüdyosu', 'desc' => 'Protez tırnak, kalıcı oje, manikür'],
                    'massage_spa' => ['name' => '💆‍♀️ Spa, Masaj & Hamam', 'desc' => 'Aromaterapi, medikal masaj, spa'],
                    'dentist' => ['name' => '🦷 Diş Kliniği & Hekimi', 'desc' => 'Diş muayenesi, estetik diş, temizlik'],
                    'doctor_clinic' => ['name' => '🩺 Doktor & Özel Klinik', 'desc' => 'Uzman doktor muayeneleri, klinik'],
                    'pilates_studio' => ['name' => '🧘‍♀️ Pilates & Yoga Stüdyosu', 'desc' => 'Reformer pilates, grup yoga'],
                    'gym' => ['name' => '🏋️‍♂️ Spor Salonu & Fitness', 'desc' => 'Spor salonu seansları, fitness'],
                    'pt_training' => ['name' => '🏃‍♂️ Personal Trainer (PT)', 'desc' => 'Birebir özel antrenörlük'],
                    'car_wash' => ['name' => '🚗 Oto Yıkama & Detailing', 'desc' => 'İç-dış yıkama, seramik kaplama'],
                    'restaurant' => ['name' => '🍽️ Restoran & Masa Rezervasyonu', 'desc' => 'Restoran masaları, şef tadımları'],
                    'hotel' => ['name' => '🏨 Otel & Konaklama', 'desc' => 'Otel oda ve suit rezervasyonları'],
                ];
                ?>

                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:12px;margin-top:10px;">
                    <?php foreach ($sectors_info as $code => $info): 
                        $current_rate = $sec_rates[$code] ?? 5.00;
                    ?>
                        <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:10px 12px;border-radius:8px;">
                            <div style="font-weight:600;font-size:0.85rem;margin-bottom:2px;"><?= $info['name'] ?></div>
                            <div style="font-size:0.75rem;color:#64748b;margin-bottom:6px;"><?= $info['desc'] ?></div>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <input type="number" step="0.01" min="0" max="100" class="sector-rate-input" data-sector="<?= $code ?>" value="<?= number_format((float)$current_rate, 2, '.', '') ?>" style="padding:4px 8px;font-weight:600;">
                                <span style="font-weight:600;color:#64748b;font-size:0.85rem;">%</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="msg" id="commission-settings-msg"></div>
                <button type="submit" style="background:#35A768;">Komisyon Oranlarını Kaydet</button>
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

        // Meta Platform Settings Form
        document.getElementById('meta-settings-form').addEventListener('submit', function (event) {
            event.preventDefault();
            const msg = document.getElementById('meta-settings-msg');

            const params = new URLSearchParams({
                csrf_token: '<?= e(vars('csrf_token')) ?>',
                meta_app_id: document.getElementById('meta_app_id').value,
                meta_app_secret: document.getElementById('meta_app_secret').value,
                meta_webhook_verify_token: document.getElementById('meta_webhook_verify_token').value,
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
                    msg.textContent = data.success ? 'Meta ayarları başarıyla kaydedildi.' : (data.message || 'Hata oluştu.');
                    if (data.success) {
                        setTimeout(() => window.location.reload(), 800);
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

        // Commission Settings Form
        document.getElementById('commission-settings-form').addEventListener('submit', function (event) {
            event.preventDefault();
            const msg = document.getElementById('commission-settings-msg');
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            const generalRate = document.getElementById('marketplace_commission_rate').value;
            const sectorRates = {};
            document.querySelectorAll('.sector-rate-input').forEach(input => {
                const sec = input.getAttribute('data-sector');
                sectorRates[sec] = input.value;
            });

            const bodyParams = new URLSearchParams();
            bodyParams.append('csrf_token', '<?= e(vars('csrf_token')) ?>');
            bodyParams.append('marketplace_commission_rate', generalRate);
            for (const [sCode, sRate] of Object.entries(sectorRates)) {
                bodyParams.append(`sector_rates[${sCode}]`, sRate);
            }

            fetch('<?= site_url('superadmin_settings/save') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: bodyParams.toString(),
            })
                .then(r => r.json())
                .then(data => {
                    submitBtn.disabled = false;
                    msg.style.display = 'block';
                    msg.className = 'msg ' + (data.success ? 'ok' : 'err');
                    msg.textContent = data.success ? 'Komisyon oranları başarıyla güncellendi.' : (data.message || 'Hata oluştu.');
                    if (data.success) {
                        setTimeout(() => window.location.reload(), 1200);
                    }
                })
                .catch(err => {
                    submitBtn.disabled = false;
                    msg.style.display = 'block';
                    msg.className = 'msg err';
                    msg.textContent = 'Bağlantı hatası: ' + err.message;
                });
        });

        // Process Settlements Button
        document.getElementById('btn-process-settlements').addEventListener('click', function () {
            if (!confirm('Tüm kiracı cüzdanlarındaki pozitif bakiyeler için günlük hakediş transfer emri işletilecek. Devam edilsin mi?')) {
                return;
            }

            const btn = this;
            const msg = document.getElementById('settlement-msg');
            btn.disabled = true;
            btn.textContent = 'İşleniyor...';

            const bodyParams = new URLSearchParams();
            bodyParams.append('csrf_token', '<?= e(vars('csrf_token')) ?>');

            fetch('<?= site_url('superadmin_settings/process_daily_settlements') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: bodyParams.toString(),
            })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.textContent = '⚡ Hakedişleri İşlet';
                    msg.style.display = 'block';
                    msg.className = 'msg ' + (data.success ? 'ok' : 'err');
                    msg.textContent = data.message || (data.success ? 'Hakedişler başarıyla işlendi.' : 'Hata oluştu.');
                    if (data.success) {
                        setTimeout(() => window.location.reload(), 1500);
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.textContent = '⚡ Hakedişleri İşlet';
                    msg.style.display = 'block';
                    msg.className = 'msg err';
                    msg.textContent = 'Hata: ' + err.message;
                });
        });
    </script>
</body>
</html>
