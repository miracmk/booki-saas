<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="api-settings-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div id="api-settings" class="col-sm-9">
            <form>
                <fieldset>
                    <div class="d-flex justify-content-between align-items-center border-bottom mb-4 py-2">
                        <h4 class="mb-0 fw-light">
                            <?= lang('api') ?>
                        </h4>

                        <div>
                            <a href="<?= site_url('integrations') ?>" class="btn btn-outline-primary me-2">
                                <i class="fas fa-chevron-left me-2"></i>
                                <?= lang('back') ?>
                            </a>

                            <?php if (can('edit', PRIV_SYSTEM_SETTINGS)): ?>
                                <button type="button" id="save-settings" class="btn btn-primary">
                                    <i class="fas fa-check-square me-2"></i>
                                    <?= lang('save') ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label" for="api-token">
                                    <?= lang('api_token') ?>
                                </label>
                                <input id="api-token" class="form-control" data-field="api_token">
                                <div class="form-text text-muted">
                                    <small>
                                        <?= lang('api_token_hint') ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MCP (Model Context Protocol) AI Connection Card -->
                    <div class="card mt-4 border-primary shadow-sm" id="mcp">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-semibold text-primary">
                                <i class="fas fa-robot me-2"></i>Model Context Protocol (MCP) AI Entegrasyonu
                            </h5>
                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Sunucu Aktif</span>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">
                                BooKi MCP Sunucusu; Claude Desktop, Cursor IDE, ElevenLabs sesli asistanları ve harici AI ajanlarınızın
                                randevu sisteminizi güvenle yönetmesini sağlar. Ajanlar hizmetleri listeleyebilir, anlık müsaitlik
                                sorgulayabilir ve doğrudan randevu oluşturup iptal edebilir.
                            </p>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">MCP Sunucu Bağlantı Ucu (SSE / Streamable HTTP)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace" id="mcp-endpoint-input" value="<?= e(vars('mcp_url')) ?>" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('mcp-endpoint-input').value); alert('Bağlantı adresi kopyalandı!');">
                                        <i class="fas fa-copy me-1"></i>Kopyala
                                    </button>
                                </div>
                                <div class="form-text text-muted">
                                    Kiracıya özel MCP ucu. Harici istemciler bu adresi doğrudan kullanabilir.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Ajan / MCP Erişim Anahtarı (Agent API Key)</label>
                                <div class="input-group">
                                    <input type="password" class="form-control font-monospace" id="mcp-token-input" value="<?= e(vars('agent_api_key')) ?>" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="toggleTokenVisibility()">
                                        <i class="fas fa-eye" id="mcp-token-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('mcp-token-input').value); alert('Erişim anahtarı kopyalandı!');">
                                        <i class="fas fa-copy me-1"></i>Kopyala
                                    </button>
                                    <?php if (can('edit', PRIV_SYSTEM_SETTINGS)): ?>
                                    <button class="btn btn-outline-danger" type="button" onclick="rotateAgentKey()">
                                        <i class="fas fa-sync-alt me-1"></i>Yeni Anahtar Üret
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="form-text text-muted">
                                    Yetkilendirme için <code>Authorization: Bearer &lt;bu_anahtar&gt;</code> başlığı kullanılır.
                                </div>
                            </div>

                            <hr class="my-3">

                            <h6 class="fw-bold mb-2">İstemci Yapılandırma Formatı (Claude Desktop / Cursor)</h6>
                            <div class="bg-dark text-white p-3 rounded font-monospace small position-relative" style="background:#1e1e1e !important;">
                                <button class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2" onclick="navigator.clipboard.writeText(document.getElementById('claude-config-code').innerText); alert('Yapılandırma JSON kopyalandı!');">
                                    <i class="fas fa-copy me-1"></i>Kopyala
                                </button>
                                <pre class="mb-0 text-white" id="claude-config-code" style="white-space: pre-wrap; margin: 0;">{
  "mcpServers": {
    "booki": {
      "url": "<?= e(vars('mcp_url')) ?>",
      "headers": {
        "Authorization": "Bearer <?= e(vars('agent_api_key')) ?>",
        "X-Tenant": "<?= e(vars('tenant_subdomain')) ?>"
      }
    }
  }
}</pre>
                            </div>
                            <div class="mt-2 text-muted small">
                                <strong>Kullanılabilir MCP Araçları:</strong> <code>business</code>, <code>services</code>, <code>providers</code>, <code>availability</code>, <code>create_appointment</code>, <code>cancel_appointment</code>, <code>reschedule_appointment</code>, <code>customer_lookup</code>, <code>customer_appointments</code>, <code>stations</code>, <code>marketing_campaigns</code>
                            </div>
                        </div>
                    </div>

                </fieldset>
            </form>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/http/api_settings_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/api_settings.js') ?>"></script>
<script>
function toggleTokenVisibility() {
    var inp = document.getElementById('mcp-token-input');
    var eye = document.getElementById('mcp-token-eye');
    if (inp.type === 'password') {
        inp.type = 'text';
        eye.className = 'fas fa-eye-slash';
    } else {
        inp.type = 'password';
        eye.className = 'fas fa-eye';
    }
}

function rotateAgentKey() {
    if (!confirm('Ajan API anahtarını yenilemek istediğinizden emin misiniz? Eski anahtarı kullanan AI ajanları erişimini kaybeder.')) {
        return;
    }
    fetch('<?= site_url('api_settings/generate_agent_key') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.agent_api_key) {
            document.getElementById('mcp-token-input').value = data.agent_api_key;
            var sub = '<?= e(vars('tenant_subdomain')) ?>';
            var url = '<?= e(vars('mcp_url')) ?>';
            var json = JSON.stringify({
                mcpServers: {
                    booki: {
                        url: url,
                        headers: {
                            Authorization: 'Bearer ' + data.agent_api_key,
                            'X-Tenant': sub
                        }
                    }
                }
            }, null, 2);
            document.getElementById('claude-config-code').innerText = json;
            alert('Yeni Ajan API anahtarı başarıyla oluşturuldu ve kaydedildi.');
        } else {
            alert('Anahtar oluşturulurken hata meydana geldi.');
        }
    })
    .catch(function(err) {
        alert('Bağlantı hatası: ' + err);
    });
}
</script>

<?php end_section('scripts'); ?>
