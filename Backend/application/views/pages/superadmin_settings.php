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
        main { padding: 1.5rem; max-width: 980px; margin: 0 auto; }
        .card { background: #fff; border-radius: 10px; padding: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,.06); margin-bottom: 1.5rem; }
        label { display: block; font-size: .85rem; font-weight: 600; margin: 1rem 0 .3rem; }
        input, select, textarea { width: 100%; padding: .55rem .7rem; border: 1px solid #d7d9dd; border-radius: 6px; font-size: .9rem; background: #fff; box-sizing: border-box; }
        button { background: #1b1f24; color: #fff; border: none; border-radius: 6px; padding: .6rem 1.2rem; font-weight: 600; cursor: pointer; margin-top: 1.2rem; }
        button:hover { background: #333; }
        .hint { color: #666; font-size: .8rem; line-height: 1.4; }
        .badge { display: inline-block; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .badge-free { background: #e6f4ea; color: #137333; }
        .badge-hybrid { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-legacy { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-healthy { background: #dcfce7; color: #15803d; }
        .badge-degraded { background: #fef9c3; color: #854d0e; }
        .badge-cooldown { background: #fee2e2; color: #b91c1c; }
        .provider-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .provider-box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; background: #fafafa; }
        .provider-box h3 { margin: 0 0 .5rem; font-size: .95rem; display: flex; justify-content: space-between; align-items: center; }
        .env-code { font-family: monospace; font-size: .75rem; background: #f1f5f9; padding: 2px 4px; border-radius: 3px; color: #475569; }
        .toggle-group { display: flex; gap: 1rem; margin: .8rem 0; flex-wrap: wrap; }
        .toggle-card { flex: 1; min-width: 260px; border: 2px solid #e2e8f0; border-radius: 8px; padding: 1rem; cursor: pointer; transition: all .2s; }
        .toggle-card.active { border-color: #2563eb; background: #f0f7ff; }
        .metrics-table { width: 100%; border-collapse: collapse; margin-top: .8rem; font-size: .85rem; }
        .metrics-table th, .metrics-table td { padding: 8px 10px; border: 1px solid #e2e8f0; text-align: left; }
        .metrics-table th { background: #f8fafc; font-weight: 600; color: #475569; }
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
        <!-- UNIFIED AI & LLM HUB (HİBRİT MODEL ROUTER & ENTEGRASYONLAR) -->
        <div class="card" style="border-top: 4px solid #2563eb;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                <div>
                    <h2 style="font-size:1.25rem;margin:0;color:#1e293b;">🤖 Merkezi AI & LLM Yönetim Hub</h2>
                    <span class="hint">Eşit Sağlayıcılar: OpenAI, Anthropic, Google | Dinamik 12 Kriter Hibrit Model Router</span>
                </div>
                <div>
                    <?php if (vars('ai_engine_version') === 'hybrid'): ?>
                        <span class="badge badge-hybrid">⚡ Yeni Hibrit Router Aktif</span>
                    <?php else: ?>
                        <span class="badge badge-legacy">🔄 Eski AI Asistan Sistemi Aktif</span>
                    <?php endif; ?>
                    <span class="badge badge-free">🌿 Ücretsiz Yapı Korundu</span>
                </div>
            </div>

            <!-- Free Tier Architecture Status Notice -->
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:.85rem;margin-top:1rem;font-size:.85rem;line-height:1.5;color:#166534;">
                <strong>🌿 Mevcut Ücretsiz Yapı Kurgusu Devam Ediyor:</strong>
                Platform genelinde Google AI Studio (1500 RPD), Groq ve OpenRouter ücretsiz kotaları korunmuştur.
                Hibrit Model Router'ın 9. kriteri ("Kalan Ücretsiz Kota"), ücretsiz kotalar bitene kadar sıfır maliyetli modelleri önceliklendirerek işletme maliyetini minimumda tutar.
            </div>

            <form id="ai-settings-form">
                <!-- 1. AI ENGINE MODE SELECTION (ESKİ vs YENİ TOGGLE) -->
                <label style="margin-top:1.2rem;font-size:.95rem;">1. AI Asistan Motoru Seçimi (Geçiş Toggle'ı)</label>
                <div class="toggle-group">
                    <label class="toggle-card <?= vars('ai_engine_version') !== 'legacy' ? 'active' : '' ?>" style="margin:0;">
                        <input type="radio" name="ai_engine_version" value="hybrid" <?= vars('ai_engine_version') !== 'legacy' ? 'checked' : '' ?> style="width:auto;margin-right:6px;" onchange="updateEngineCards()">
                        <strong>⚡ Yeni Nesil Hibrit Model Router (Önerilen)</strong>
                        <p class="hint" style="margin:6px 0 0;">
                            <strong>Eşit Birinci Sınıf Sağlayıcılar:</strong> OpenAI, Anthropic Claude ve Google Gemini birbirine eşit tutulur.
                            Her istekte 12 kritere göre (Görev tipi, model yeteneği, tool calling desteği, structured output, token limiti, anlık sağlık, gecikme, hata oranı, kalan ücretsiz kota, API maliyeti, kiracı politikası ve başarı oranı) en uygun model dinamik seçilir. Hata durumunda aynı router devreye girerek sonraki en iyi modele geçer.
                        </p>
                    </label>

                    <label class="toggle-card <?= vars('ai_engine_version') === 'legacy' ? 'active' : '' ?>" style="margin:0;">
                        <input type="radio" name="ai_engine_version" value="legacy" <?= vars('ai_engine_version') === 'legacy' ? 'checked' : '' ?> style="width:auto;margin-right:6px;" onchange="updateEngineCards()">
                        <strong>🔄 Eski AI Asistan Sistemi (Geçici Fallback)</strong>
                        <p class="hint" style="margin:6px 0 0;">
                            Geriye dönük uyumluluk için geçici statik sıralı fallback zinciri (Google -> Groq -> OpenRouter -> OpenAI -> Anthropic).
                        </p>
                    </label>
                </div>

                <label style="margin-top:1rem;">Sağlayıcı Tercihi (İsteğe Bağlı Sabitleme)</label>
                <select id="ai_provider">
                    <option value="auto" <?= vars('ai_provider') === 'auto' ? 'selected' : '' ?>>Otomatik / Dinamik Karar (Router 12 kritere göre en uygun modeli seçer)</option>
                    <option value="google" <?= vars('ai_provider') === 'google' ? 'selected' : '' ?>>Google AI Studio / Gemini Tercih Et</option>
                    <option value="openai" <?= vars('ai_provider') === 'openai' ? 'selected' : '' ?>>OpenAI (GPT-4o Mini / GPT-4o) Tercih Et</option>
                    <option value="anthropic" <?= vars('ai_provider') === 'anthropic' ? 'selected' : '' ?>>Anthropic Claude 3.5 Tercih Et</option>
                    <option value="groq" <?= vars('ai_provider') === 'groq' ? 'selected' : '' ?>>Groq (Ultra Hızlı / Llama 3.3) Tercih Et</option>
                    <option value="openrouter" <?= vars('ai_provider') === 'openrouter' ? 'selected' : '' ?>>OpenRouter (Çoklu Model) Tercih Et</option>
                </select>

                <hr style="margin:1.5rem 0;border:none;border-top:1px solid #e2e8f0;">

                <!-- 2. TÜM AI SAĞLAYICILARI VE ENV ENTEGRASYONLARI (TEK SAYFADA) -->
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <label style="margin:0;font-size:.95rem;">2. Birinci Sınıf Eşit AI Sağlayıcıları & API Anahtarları</label>
                    <span class="hint">Tüm ENV değişkenleri doğrudan buradan yönetilebilir</span>
                </div>

                <div class="provider-grid">
                    <!-- Google Gemini -->
                    <div class="provider-box">
                        <h3>
                            <span>Google AI Studio / Gemini</span>
                            <span class="badge badge-free">Free 1500 RPD</span>
                        </h3>
                        <div class="hint" style="margin-bottom:8px;">
                            <span class="env-code">GEMINI_API_KEY</span> / <span class="env-code">GOOGLE_AI_KEY</span>
                        </div>
                        <label style="margin-top:.4rem;">
                            API Key <?= vars('google_ai_key_set') ? '<span class="hint" style="color:#137333;">(Kayıtlı ✓)</span>' : '<span class="hint">(aistudio.google.com)</span>' ?>
                        </label>
                        <input type="password" id="google_ai_key" placeholder="<?= vars('google_ai_key_set') ? '••••••••' : 'AIzaSy...' ?>">
                        <label>Model (<span class="env-code">GEMINI_MODEL</span>)</label>
                        <input type="text" id="ai_model_google" placeholder="gemini-3.8-flash" value="<?= e(vars('ai_model_google')) ?>">
                        <div class="hint" style="margin-top:6px;font-size:0.75rem;">
                            ✓ Tool Calling &nbsp;|&nbsp; ✓ Structured Output &nbsp;|&nbsp; 1M+ Context
                        </div>
                    </div>

                    <!-- OpenAI -->
                    <div class="provider-box">
                        <h3>
                            <span>OpenAI</span>
                            <span class="badge" style="background:#e0f2fe;color:#0369a1;">Eşit 1. Sınıf</span>
                        </h3>
                        <div class="hint" style="margin-bottom:8px;">
                            <span class="env-code">OPENAI_API_KEY</span> / <span class="env-code">OPENAI_MODEL</span>
                        </div>
                        <label style="margin-top:.4rem;">
                            API Key <?= vars('openai_api_key_set') ? '<span class="hint" style="color:#137333;">(Kayıtlı ✓)</span>' : '<span class="hint">(platform.openai.com)</span>' ?>
                        </label>
                        <input type="password" id="openai_api_key" placeholder="<?= vars('openai_api_key_set') ? '••••••••' : 'sk-proj-...' ?>">
                        <label>Model (<span class="env-code">OPENAI_MODEL</span>)</label>
                        <input type="text" id="ai_model_openai" placeholder="gpt-4o-mini" value="<?= e(vars('ai_model_openai')) ?>">
                        <div class="hint" style="margin-top:6px;font-size:0.75rem;">
                            ✓ Function Calling &nbsp;|&nbsp; ✓ JSON Mode &nbsp;|&nbsp; 128k Context
                        </div>
                    </div>

                    <!-- Anthropic Claude -->
                    <div class="provider-box">
                        <h3>
                            <span>Anthropic Claude</span>
                            <span class="badge" style="background:#fce7f3;color:#9d174d;">Eşit 1. Sınıf</span>
                        </h3>
                        <div class="hint" style="margin-bottom:8px;">
                            <span class="env-code">ANTHROPIC_API_KEY</span> / <span class="env-code">ANTHROPIC_MODEL</span>
                        </div>
                        <label style="margin-top:.4rem;">
                            API Key <?= vars('anthropic_api_key_set') ? '<span class="hint" style="color:#137333;">(Kayıtlı ✓)</span>' : '<span class="hint">(console.anthropic.com)</span>' ?>
                        </label>
                        <input type="password" id="anthropic_api_key" placeholder="<?= vars('anthropic_api_key_set') ? '••••••••' : 'sk-ant-...' ?>">
                        <label>Model (<span class="env-code">ANTHROPIC_MODEL</span>)</label>
                        <input type="text" id="ai_model_anthropic" placeholder="claude-3-5-haiku-20241022" value="<?= e(vars('ai_model_anthropic')) ?>">
                        <div class="hint" style="margin-top:6px;font-size:0.75rem;">
                            ✓ Native Tool Use &nbsp;|&nbsp; ✓ JSON Schema &nbsp;|&nbsp; 200k Context
                        </div>
                    </div>

                    <!-- Groq -->
                    <div class="provider-box">
                        <h3>
                            <span>Groq (Ultra-Hızlı)</span>
                            <span class="badge badge-free">Free Tier</span>
                        </h3>
                        <div class="hint" style="margin-bottom:8px;">
                            <span class="env-code">GROQ_API_KEY</span> / <span class="env-code">GROQ_MODEL</span>
                        </div>
                        <label style="margin-top:.4rem;">
                            API Key <?= vars('groq_api_key_set') ? '<span class="hint" style="color:#137333;">(Kayıtlı ✓)</span>' : '<span class="hint">(console.groq.com)</span>' ?>
                        </label>
                        <input type="password" id="groq_api_key" placeholder="<?= vars('groq_api_key_set') ? '••••••••' : 'gsk_...' ?>">
                        <label>Model</label>
                        <input type="text" id="ai_model_groq" placeholder="llama-3.3-70b-versatile" value="<?= e(vars('ai_model_groq')) ?>">
                        <div class="hint" style="margin-top:6px;font-size:0.75rem;">
                            ✓ ~220ms Gecikme &nbsp;|&nbsp; ✓ Llama 3.3 70B
                        </div>
                    </div>

                    <!-- OpenRouter -->
                    <div class="provider-box">
                        <h3>
                            <span>OpenRouter</span>
                            <span class="badge" style="background:#f1f5f9;color:#334155;">Açık Kaynak</span>
                        </h3>
                        <div class="hint" style="margin-bottom:8px;">
                            <span class="env-code">OPENROUTER_API_KEY</span>
                        </div>
                        <label style="margin-top:.4rem;">
                            API Key <?= vars('openrouter_api_key_set') ? '<span class="hint" style="color:#137333;">(Kayıtlı ✓)</span>' : '<span class="hint">(openrouter.ai)</span>' ?>
                        </label>
                        <input type="password" id="openrouter_api_key" placeholder="<?= vars('openrouter_api_key_set') ? '••••••••' : 'sk-or-v1-...' ?>">
                        <label>Model</label>
                        <input type="text" id="ai_model_openrouter" placeholder="qwen/qwen-2.5-72b-instruct" value="<?= e(vars('ai_model_openrouter')) ?>">
                        <div class="hint" style="margin-top:6px;font-size:0.75rem;">
                            ✓ 100+ Model Desteği &nbsp;|&nbsp; Free modeller
                        </div>
                    </div>
                </div>

                <hr style="margin:1.5rem 0;border:none;border-top:1px solid #e2e8f0;">

                <!-- 3. EK AI SES VE KANAL ENTEGRASYONLARI -->
                <label style="font-size:.95rem;">3. Entegre Sesli Asistan, WhatsApp Bridge & MCP Ajan Entegrasyonları</label>
                <div class="provider-grid">
                    <!-- ElevenLabs AI Voice -->
                    <div class="provider-box">
                        <h3>
                            <span>ElevenLabs AI Voice</span>
                            <span class="badge" style="background:#ede9fe;color:#6d28d9;">Sesli Asistan</span>
                        </h3>
                        <div class="hint"><span class="env-code">ELEVENLABS_API_KEY</span></div>
                        <label style="margin-top:.4rem;">
                            API Key <?= vars('elevenlabs_api_key_set') ? '<span class="hint" style="color:#137333;">(Kayıtlı ✓)</span>' : '' ?>
                        </label>
                        <input type="password" id="elevenlabs_api_key" placeholder="<?= vars('elevenlabs_api_key_set') ? '••••••••' : 'xi-api-key...' ?>">
                        <label>Agent ID</label>
                        <input type="text" id="elevenlabs_agent_id" value="<?= e(vars('elevenlabs_agent_id')) ?>" placeholder="agent_...">
                        <label>Voice ID</label>
                        <input type="text" id="elevenlabs_voice_id" value="<?= e(vars('elevenlabs_voice_id')) ?>" placeholder="21m00Tcm4TlvDq8ikWAM">
                        <label>Model ID</label>
                        <input type="text" id="elevenlabs_model_id" value="<?= e(vars('elevenlabs_model_id')) ?>" placeholder="eleven_multilingual_v2">
                    </div>

                    <!-- WhatsApp AI Bridge -->
                    <div class="provider-box">
                        <h3>
                            <span>WhatsApp AI Bridge</span>
                            <span class="badge" style="background:#dcfce7;color:#15803d;">Köprü Servisi</span>
                        </h3>
                        <div class="hint"><span class="env-code">WA_BRIDGE_URL</span></div>
                        <label style="margin-top:.4rem;">Bridge Service URL</label>
                        <input type="text" id="wa_bridge_url" value="<?= e(vars('wa_bridge_url')) ?>" placeholder="http://ki-wa-bridge:3000">
                        <label>Bridge Secret Key <?= vars('wa_bridge_secret_set') ? '<span class="hint" style="color:#137333;">(Kayıtlı ✓)</span>' : '' ?></label>
                        <input type="password" id="wa_bridge_secret" placeholder="<?= vars('wa_bridge_secret_set') ? '••••••••' : 'Gizli anahtar...' ?>">
                    </div>

                    <!-- BooKi MCP Server -->
                    <div class="provider-box">
                        <h3>
                            <span>BooKi MCP Sunucusu</span>
                            <span class="badge" style="background:#e0e7ff;color:#4338ca;">Model Context Protocol</span>
                        </h3>
                        <label style="margin-top:.4rem;">Public Streamable Endpoint</label>
                        <input type="text" id="mcp_url_input" value="<?= e(vars('mcp_server_url')) ?>" readonly style="background:#f8fafc;font-family:monospace;font-size:0.8rem;">
                        <label>Internal Container URL</label>
                        <input type="text" value="<?= e(vars('mcp_internal_url')) ?>" readonly style="background:#f8fafc;font-family:monospace;font-size:0.8rem;">
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('mcp_url_input').value); alert('Kopyalandı!');" style="margin-top:.5rem;padding:.4rem .8rem;font-size:.8rem;background:#475569;">
                            📋 MCP URL Kopyala
                        </button>
                    </div>
                </div>

                <div class="msg" id="ai-settings-msg"></div>
                <div style="display:flex;gap:10px;align-items:center;margin-top:1.2rem;">
                    <button type="submit" style="background:#2563eb;margin-top:0;">💾 Tüm AI & Entegrasyon Ayarlarını Kaydet</button>
                </div>
            </form>

            <hr style="margin:1.8rem 0;border:none;border-top:1px solid #e2e8f0;">

            <!-- 4. CANLI MODEL METRİKLERİ VE SAĞLIK TABLOSU -->
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                <label style="margin:0;font-size:.95rem;">4. Canlı Model Sağlık, Gecikme & Başarı Oranları (Router Metrikleri)</label>
                <button type="button" id="btn-reset-ai-metrics" style="margin-top:0;padding:.35rem .75rem;font-size:.8rem;background:#64748b;">
                    🔄 Metrikleri Sıfırla
                </button>
            </div>
            <p class="hint" style="margin-top:4px;">
                Router, her modelin son isteklerdeki gecikme (EMA), hata oranı ve circuit breaker durumunu izler.
            </p>

            <?php $metrics = vars('ai_router_metrics')['models'] ?? []; ?>
            <div style="overflow-x:auto;">
                <table class="metrics-table">
                    <thead>
                        <tr>
                            <th>Model</th>
                            <th>Sağlayıcı</th>
                            <th>Yetenek</th>
                            <th>Araç Desteği</th>
                            <th>Durum</th>
                            <th>Gecikme</th>
                            <th>Başarı %</th>
                            <th>Kalan Ücretsiz Kota</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($metrics)): ?>
                            <tr><td colspan="8" style="text-align:center;color:#64748b;">Kayıtlı metrik henüz yok. İlk AI isteğinde otomatik dolacaktır.</td></tr>
                        <?php else: ?>
                            <?php foreach ($metrics as $m): ?>
                                <tr>
                                    <td><strong><?= e($m['display_name']) ?></strong></td>
                                    <td><span class="badge" style="background:#f1f5f9;color:#334155;"><?= strtoupper(e($m['provider'])) ?></span></td>
                                    <td><?= (int)$m['capability'] ?> / 100</td>
                                    <td><?= !empty($m['tool_support']) ? '<span style="color:#16a34a;">✓ Destekli</span>' : '<span style="color:#94a3b8;">-</span>' ?></td>
                                    <td>
                                        <span class="badge badge-<?= e($m['state']) ?>">
                                            <?= e(ucfirst($m['state'])) ?>
                                        </span>
                                    </td>
                                    <td><?= number_format($m['latency_ms'], 0) ?> ms</td>
                                    <td><?= number_format($m['success_rate'], 1) ?>%</td>
                                    <td>
                                        <?php if (!empty($m['has_free_tier'])): ?>
                                            <span style="color:#15803d;font-weight:600;"><?= (int)$m['remaining_free_quota'] ?> istek/gün</span>
                                        <?php else: ?>
                                            <span style="color:#64748b;">Ücretli / Kredi</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <hr style="margin:1.8rem 0;border:none;border-top:1px solid #e2e8f0;">

            <!-- 5. İNTERAKTİF HİBRİT ROUTER TEST & SİMÜLASYON PANELİ -->
            <label style="font-size:.95rem;margin-top:0;">5. İnteraktif Hibrit Model Router Karar Simülasyonu & Canlı Test</label>
            <p class="hint" style="margin-top:4px;">
                Belirli bir görev türü ve kullanıcı mesajı için 12 kriterlik algoritmanın hangi modeli neden seçtiğini canlı olarak test edin.
            </p>

            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;">
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:10px;">
                    <div>
                        <label style="margin-top:0;">Görev Türü (Task Type)</label>
                        <select id="sim_task_type">
                            <option value="appointment_booking">Randevu Alma & Takvim (Tool Calling & Function Execution)</option>
                            <option value="fast_response">Hızlı Yanıt & Karşılama (Düşük Gecikme / WhatsApp)</option>
                            <option value="complex_reasoning">Karmaşık Muhakeme (Şikayet / İtiraz / Kural Değerlendirme)</option>
                            <option value="summary">Konuşma Özeti (Uzun Bağlam / Memory)</option>
                            <option value="chat">Genel Sohbet (Dengeli Karar)</option>
                        </select>
                    </div>
                    <div>
                        <label style="margin-top:0;">Test Mesajı / Prompt</label>
                        <input type="text" id="sim_prompt" value="Merhaba, yarın saat 14:00 için saç kesimi randevusu alabilir miyim?">
                    </div>
                </div>

                <div style="margin-top:10px;display:flex;align-items:center;gap:12px;">
                    <label style="margin:0;display:inline-flex;align-items:center;gap:6px;font-weight:normal;cursor:pointer;">
                        <input type="checkbox" id="sim_execute" checked style="width:auto;">
                        <span>Gerçek LLM API Çağrısını da Çalıştır (Canlı Yanıt Al)</span>
                    </label>
                    <button type="button" id="btn-test-router" style="margin-top:0;background:#0f172a;padding:.5rem 1rem;">
                        🚀 Dinamik Model Seçimini Test Et
                    </button>
                </div>

                <div id="router-test-result" style="display:none;margin-top:1rem;background:#fff;border:1px solid #cbd5e1;border-radius:6px;padding:1rem;"></div>
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
        function updateEngineCards() {
            const selected = document.querySelector('input[name="ai_engine_version"]:checked')?.value || 'hybrid';
            document.querySelectorAll('.toggle-card').forEach(card => {
                const radio = card.querySelector('input[type="radio"]');
                if (radio && radio.value === selected) {
                    card.classList.add('active');
                } else {
                    card.classList.remove('active');
                }
            });
        }

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
                'anthropic_api_key', 'ai_model_anthropic',
                'elevenlabs_api_key', 'elevenlabs_agent_id', 'elevenlabs_voice_id', 'elevenlabs_model_id',
                'wa_bridge_url', 'wa_bridge_secret'
            ];

            const data = {
                csrf_token: '<?= e(vars('csrf_token')) ?>',
                ai_engine_version: document.querySelector('input[name="ai_engine_version"]:checked')?.value || 'hybrid'
            };

            fieldIds.forEach((id) => {
                const el = document.getElementById(id);
                if (el && el.value !== '') data[id] = el.value;
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
                    msg.textContent = data.success ? 'Tüm AI & Entegrasyon ayarları başarıyla kaydedildi.' : (data.message || 'Hata oluştu.');
                    if (data.success) {
                        setTimeout(() => window.location.reload(), 1000);
                    }
                })
                .catch((e) => {
                    msg.style.display = 'block';
                    msg.className = 'msg err';
                    msg.textContent = 'Bağlantı hatası: ' + e.message;
                });
        });

        // Reset AI Metrics Cache
        const btnResetMetrics = document.getElementById('btn-reset-ai-metrics');
        if (btnResetMetrics) {
            btnResetMetrics.addEventListener('click', function () {
                if (!confirm('Tüm modellerin anlık gecikme, hata ve sağlık istatistikleri sıfırlanacaktır. Emin misiniz?')) {
                    return;
                }
                const btn = this;
                btn.disabled = true;
                fetch('<?= site_url('superadmin_settings/reset_ai_metrics') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ csrf_token: '<?= e(vars('csrf_token')) ?>' }).toString(),
                })
                    .then(r => r.json())
                    .then(data => {
                        alert(data.message || 'Metrikler sıfırlandı.');
                        window.location.reload();
                    })
                    .catch(e => {
                        alert('Hata: ' + e.message);
                        btn.disabled = false;
                    });
            });
        }

        // Interactive AI Hybrid Router Test & Simulation
        const btnTestRouter = document.getElementById('btn-test-router');
        if (btnTestRouter) {
            btnTestRouter.addEventListener('click', function () {
                const taskType = document.getElementById('sim_task_type').value;
                const prompt = document.getElementById('sim_prompt').value;
                const execute = document.getElementById('sim_execute').checked ? '1' : '0';
                const resultBox = document.getElementById('router-test-result');

                btnTestRouter.disabled = true;
                btnTestRouter.textContent = '⏳ Analiz Ediliyor...';
                resultBox.style.display = 'block';
                resultBox.innerHTML = '<div style="color:#64748b;font-size:0.9rem;">12 kriter dinamik hesaplanıyor ve modeller puanlanıyor...</div>';

                const params = new URLSearchParams({
                    csrf_token: '<?= e(vars('csrf_token')) ?>',
                    task_type: taskType,
                    prompt: prompt,
                    execute: execute
                });

                fetch('<?= site_url('superadmin_settings/test_ai_router') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                })
                    .then(r => r.json())
                    .then(data => {
                        btnTestRouter.disabled = false;
                        btnTestRouter.textContent = '🚀 Dinamik Model Seçimini Test Et';

                        if (!data.success) {
                            resultBox.innerHTML = `<div style="color:#c0392b;">Hata: ${data.message || 'Router çalıştırılamadı'}</div>`;
                            return;
                        }

                        const sel = data.selected_model;
                        if (!sel) {
                            resultBox.innerHTML = '<div style="color:#c0392b;">Hiçbir uygun model bulunamadı. Lütfen API anahtarlarını kontrol edin.</div>';
                            return;
                        }

                        let html = `
                            <div style="border-bottom:1px solid #e2e8f0;padding-bottom:10px;margin-bottom:10px;">
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <h4 style="margin:0;font-size:1rem;color:#1e293b;">
                                        🏆 Seçilen Model: <strong style="color:#2563eb;">${sel.display_name}</strong>
                                    </h4>
                                    <span class="badge" style="background:#dcfce7;color:#15803d;font-size:12px;">Puan: ${sel.total_score} / 100</span>
                                </div>
                                <div style="font-size:0.8rem;color:#64748b;margin-top:4px;">
                                    Sağlayıcı: <strong>${sel.provider.toUpperCase()}</strong> | Model: <code>${sel.model}</code> | Durum: <strong>${sel.health.state}</strong>
                                </div>
                            </div>
                        `;

                        // 12 criteria breakdown table
                        if (sel.breakdown) {
                            html += `
                                <div style="font-size:0.82rem;font-weight:600;color:#475569;margin-bottom:4px;">12 Kriter Puanlama Dökümü:</div>
                                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:6px;font-size:0.78rem;background:#f8fafc;padding:8px;border-radius:6px;border:1px solid #e2e8f0;">
                                    <div>🎯 Görev Uyumu: <strong>${sel.breakdown.task_type_fit || 0}</strong></div>
                                    <div>🧠 Model Yeteneği: <strong>${sel.breakdown.model_capability || 0}</strong></div>
                                    <div>🛠️ Tool Desteği: <strong>${sel.breakdown.tool_support || 0}</strong></div>
                                    <div>📋 Structured Output: <strong>${sel.breakdown.structured_output || 0}</strong></div>
                                    <div>📏 Bağlam/Token: <strong>${sel.breakdown.context_fit || 0}</strong></div>
                                    <div>🩺 Sağlayıcı Sağlığı: <strong>${sel.breakdown.provider_health || 0}</strong></div>
                                    <div>⚡ Anlık Gecikme: <strong>${sel.breakdown.current_latency || 0}</strong></div>
                                    <div>🛡️ Hata Oranı: <strong>${sel.breakdown.error_rate || 0}</strong></div>
                                    <div>🌿 Kalan Free Kota: <strong>${sel.breakdown.remaining_free_quota || 0}</strong></div>
                                    <div>💰 API Maliyeti: <strong>${sel.breakdown.paid_api_cost || 0}</strong></div>
                                    <div>🏢 Kiracı Politikası: <strong>${sel.breakdown.tenant_ai_policy || 0}</strong></div>
                                    <div>📊 Geçmiş Başarı: <strong>${sel.breakdown.historical_success_rate || 0}</strong></div>
                                </div>
                            `;
                        }

                        // Execution output
                        if (data.execution) {
                            const ex = data.execution;
                            html += `
                                <div style="margin-top:12px;padding:10px;background:${ex.success ? '#f0fdf4' : '#fef2f2'};border:1px solid ${ex.success ? '#bbf7d0' : '#fecaca'};border-radius:6px;">
                                    <div style="display:flex;justify-content:space-between;font-size:0.82rem;font-weight:600;color:${ex.success ? '#166534' : '#991b1b'};">
                                        <span>Canlı LLM Çağrı Sonucu (${ex.latency_ms} ms):</span>
                                        <span>Motor: ${ex.engine}</span>
                                    </div>
                                    <div style="margin-top:6px;font-size:0.88rem;color:#1e293b;white-space:pre-wrap;background:#fff;padding:8px;border-radius:4px;border:1px solid #e2e8f0;">${escapeHtml(ex.reply)}</div>
                                </div>
                            `;
                        }

                        resultBox.innerHTML = html;
                    })
                    .catch(e => {
                        btnTestRouter.disabled = false;
                        btnTestRouter.textContent = '🚀 Dinamik Model Seçimini Test Et';
                        resultBox.innerHTML = `<div style="color:#c0392b;">Bağlantı hatası: ${e.message}</div>`;
                    });
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

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
