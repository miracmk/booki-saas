<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>
<?php
$schemas = $schemas ?? $sections ?? [];
$section_values = $section_values ?? $values ?? [];
$can_edit = $can_edit ?? true;
$active_section = $active_section ?? vars('active_section') ?? 'business';
?>

<div id="settings-center-page" class="container-fluid py-4 px-md-5">
    <!-- Header with Search -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 border-bottom pb-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <div class="bg-primary bg-opacity-10 p-2 rounded-3 text-primary">
                    <i class="fas fa-sliders-h fa-lg"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-dark"><?= lang('settings_center') ?></h3>
                    <p class="text-muted small mb-0"><?= lang('settings_center_desc') ?></p>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="settings-search" class="form-control border-start-0 ps-0" placeholder="<?= lang('settings_search_placeholder') ?>">
            </div>
            <a href="<?= site_url('industry_settings') ?>" class="btn btn-outline-primary">
                <i class="fas fa-shapes me-1"></i> <?= lang('industry_and_modules') ?>
            </a>
        </div>
    </div>

    <!-- Main Navigation Tabs -->
    <ul class="nav nav-pills nav-fill bg-light p-2 rounded-4 mb-4 shadow-sm flex-nowrap overflow-auto" id="settings-main-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link rounded-3 fw-semibold <?= $active_section === 'business' ? 'active' : '' ?> text-nowrap py-2 px-3" id="tab-business" data-bs-toggle="pill" href="#section-business" role="tab">
                <i class="fas fa-building me-2"></i><?= lang('settings_section_business_title') ?>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link rounded-3 fw-semibold <?= $active_section === 'booking' ? 'active' : '' ?> text-nowrap py-2 px-3" id="tab-booking" data-bs-toggle="pill" href="#section-booking" role="tab">
                <i class="fas fa-calendar-check me-2"></i><?= lang('settings_section_booking_title') ?>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link rounded-3 fw-semibold <?= $active_section === 'communication' ? 'active' : '' ?> text-nowrap py-2 px-3" id="tab-communication" data-bs-toggle="pill" href="#section-communication" role="tab">
                <i class="fas fa-paper-plane me-2"></i><?= lang('settings_section_communication_title') ?>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link rounded-3 fw-semibold <?= $active_section === 'integrations' ? 'active' : '' ?> text-nowrap py-2 px-3" id="tab-integrations" data-bs-toggle="pill" href="#section-integrations" role="tab">
                <i class="fas fa-plug me-2"></i><?= lang('settings_section_integrations_title') ?>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link rounded-3 fw-semibold <?= $active_section === 'legal' ? 'active' : '' ?> text-nowrap py-2 px-3" id="tab-legal" data-bs-toggle="pill" href="#section-legal" role="tab">
                <i class="fas fa-balance-scale me-2"></i><?= lang('settings_section_legal_title') ?>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link rounded-3 fw-semibold <?= $active_section === 'security' ? 'active' : '' ?> text-nowrap py-2 px-3" id="tab-security" data-bs-toggle="pill" href="#section-security" role="tab">
                <i class="fas fa-shield-alt me-2"></i><?= lang('settings_section_security_title') ?>
            </a>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="settings-tab-content">
        <?php foreach ($schemas as $section_key => $sec): ?>
            <div class="tab-pane fade <?= $section_key === $active_section ? 'show active' : '' ?>" id="section-<?= $section_key ?>" role="tabpanel">
                <div class="row g-4">
                    <!-- Left Sub-navigation Pills -->
                    <div class="col-lg-3 col-md-4">
                        <div class="card border-0 shadow-sm rounded-3 p-2 sticky-top" style="top: 80px; z-index: 10;">
                            <div class="nav flex-column nav-pills" id="subnav-<?= $section_key ?>">
                                <?php $first_tab = true; foreach ($sec['tabs'] as $sub_key => $sub_title): ?>
                                    <button class="nav-link text-start rounded-2 py-2 px-3 mb-1 <?= $first_tab ? 'active' : '' ?>"
                                            data-subtab-target="#subtab-<?= $section_key ?>-<?= $sub_key ?>">
                                        <?= htmlspecialchars($sub_title) ?>
                                    </button>
                                <?php $first_tab = false; endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right Form Cards Container -->
                    <div class="col-lg-9 col-md-8">
                        <form id="form-<?= $section_key ?>" class="settings-form" data-section="<?= $section_key ?>">
                            <?php foreach ($sec['tabs'] as $sub_key => $sub_title): ?>
                                <div class="subtab-pane mb-4" id="subtab-<?= $section_key ?>-<?= $sub_key ?>">
                                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                                        <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4">
                                            <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($sub_title) ?></h5>
                                        </div>
                                        <div class="card-body p-4">
                                            <div class="row g-3">
                                                <?php foreach ($sec['settings'] as $key => $meta): ?>
                                                    <?php if (($meta['tab'] ?? '') === $sub_key): ?>
                                                        <div class="col-12 setting-field" data-setting-key="<?= $key ?>">
                                                            <div class="p-3 bg-light rounded-3 border">
                                                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                                                                    <label class="form-label fw-bold mb-0 text-dark" for="input-<?= $key ?>">
                                                                        <?= htmlspecialchars($meta['label']) ?>
                                                                        <?php if (!empty($meta['required'])): ?>
                                                                            <span class="text-danger">*</span>
                                                                        <?php endif; ?>
                                                                    </label>
                                                                    <?php if (!empty($meta['is_secret'])): ?>
                                                                        <span class="badge bg-secondary"><i class="fas fa-lock me-1"></i><?= lang('settings_secret_key') ?></span>
                                                                    <?php endif; ?>
                                                                </div>

                                                                <p class="text-muted small mb-2"><?= htmlspecialchars($meta['description']) ?></p>

                                                                <?php $current_val = $section_values[$section_key][$key] ?? $meta['default']; ?>

                                                                <?php if ($meta['type'] === 'bool'): ?>
                                                                    <div class="form-check form-switch fs-5">
                                                                        <input class="form-check-input setting-input" type="checkbox" role="switch"
                                                                               id="input-<?= $key ?>" name="<?= $key ?>" value="1"
                                                                               <?= !empty($current_val) ? 'checked' : '' ?>>
                                                                    </div>

                                                                <?php elseif ($meta['type'] === 'select'): ?>
                                                                    <select class="form-select setting-input" id="input-<?= $key ?>" name="<?= $key ?>">
                                                                        <?php foreach ($meta['options'] as $opt_val => $opt_label): ?>
                                                                            <?php $val_to_check = is_int($opt_val) ? $opt_label : $opt_val; ?>
                                                                            <option value="<?= htmlspecialchars($val_to_check) ?>" <?= (string)$current_val === (string)$val_to_check ? 'selected' : '' ?>>
                                                                                <?= htmlspecialchars($opt_label) ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>

                                                                <?php elseif ($meta['type'] === 'text'): ?>
                                                                    <textarea class="form-control setting-input" id="input-<?= $key ?>" name="<?= $key ?>" rows="4"><?= htmlspecialchars((string)$current_val) ?></textarea>

                                                                <?php elseif ($meta['type'] === 'color'): ?>
                                                                    <div class="input-group" style="max-width: 250px;">
                                                                        <input type="color" class="form-control form-control-color" id="picker-<?= $key ?>" value="<?= htmlspecialchars((string)$current_val ?: '#35A768') ?>" oninput="document.getElementById('input-<?= $key ?>').value = this.value; document.getElementById('input-<?= $key ?>').dispatchEvent(new Event('change'));">
                                                                        <input type="text" class="form-control font-monospace setting-input" id="input-<?= $key ?>" name="<?= $key ?>" value="<?= htmlspecialchars((string)$current_val) ?>" oninput="document.getElementById('picker-<?= $key ?>').value = this.value;">
                                                                    </div>

                                                                <?php elseif (!empty($meta['is_secret'])): ?>
                                                                    <div class="input-group">
                                                                        <input type="password" class="form-control font-monospace setting-input" id="input-<?= $key ?>" name="<?= $key ?>" value="<?= htmlspecialchars((string)$current_val) ?>" readonly>
                                                                        <button type="button" class="btn btn-outline-secondary" onclick="revealSecret('<?= $key ?>', 'input-<?= $key ?>')">
                                                                            <i class="fas fa-eye me-1"></i><?= lang('settings_reveal') ?>
                                                                        </button>
                                                                        <button type="button" class="btn btn-outline-secondary" onclick="copySecret('input-<?= $key ?>')">
                                                                            <i class="fas fa-copy me-1"></i><?= lang('settings_copy') ?>
                                                                        </button>
                                                                    </div>

                                                                <?php else: ?>
                                                                    <input type="<?= $meta['type'] === 'int' ? 'number' : 'text' ?>"
                                                                           class="form-control setting-input" id="input-<?= $key ?>"
                                                                           name="<?= $key ?>" value="<?= htmlspecialchars((string)$current_val) ?>">
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Section Specific Interactive Cards -->
                            <?php if ($section_key === 'integrations'): ?>
                                <!-- MCP AI Developer Hub Card -->
                                <div class="card border-primary border-2 shadow-sm rounded-4 mb-4">
                                    <div class="card-header bg-primary text-white py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-robot fa-lg"></i>
                                            <h5 class="fw-bold mb-0"><?= lang('settings_mcp_title') ?></h5>
                                        </div>
                                        <span class="badge bg-white text-primary fw-bold px-3 py-2 rounded-pill"><i class="fas fa-check-circle me-1 text-success"></i><?= lang('active') ?></span>
                                    </div>
                                    <div class="card-body p-4">
                                        <p class="text-muted small">
                                            <?= lang('settings_mcp_desc') ?>
                                        </p>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-dark"><?= lang('settings_mcp_endpoint') ?></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control font-monospace bg-light" id="mcp-endpoint-input" value="<?= htmlspecialchars($mcp_url) ?>" readonly>
                                                <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('mcp-endpoint-input').value); showToast(window.vars('i18n').copied || 'Kopyalandı!');">
                                                    <i class="fas fa-copy me-1"></i><?= lang('settings_copy') ?>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-dark"><?= lang('settings_field_agent_api_key') ?></label>
                                            <div class="input-group">
                                                <input type="password" class="form-control font-monospace bg-light" id="mcp-agent-key" value="<?= htmlspecialchars($agent_api_key_masked) ?>" readonly>
                                                <button class="btn btn-outline-secondary" type="button" onclick="revealSecret('agent_api_key', 'mcp-agent-key')">
                                                    <i class="fas fa-eye me-1"></i><?= lang('settings_reveal') ?>
                                                </button>
                                                <button class="btn btn-outline-secondary" type="button" onclick="copySecret('mcp-agent-key')">
                                                    <i class="fas fa-copy me-1"></i><?= lang('settings_copy') ?>
                                                </button>
                                                <button class="btn btn-outline-danger" type="button" onclick="rotateAgentKey()">
                                                    <i class="fas fa-sync-alt me-1"></i><?= lang('settings_rotate_key') ?>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="form-label fw-bold small text-dark mb-0"><?= lang('settings_mcp_connect_instruction') ?></label>
                                                <button class="btn btn-sm btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('claude-config-code').innerText); showToast(window.vars('i18n').copied || 'JSON Kopyalandı!');">
                                                    <i class="fas fa-copy me-1"></i><?= lang('settings_copy_json') ?>
                                                </button>
                                            </div>
                                            <pre class="bg-dark text-light p-3 rounded-3 small font-monospace mb-0" id="claude-config-code">{
  "mcpServers": {
    "booki": {
      "command": "node",
      "args": ["server.js"],
      "env": {
        "BOOKI_URL": "<?= htmlspecialchars($mcp_url) ?>",
        "BOOKI_KEY": "&lt;AGENT_API_KEY&gt;"
      }
    }
  }
}</pre>
                                        </div>

                                        <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                                            <button type="button" class="btn btn-outline-success" onclick="testConnection()">
                                                <i class="fas fa-network-wired me-1"></i><?= lang('settings_test_connection') ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Sticky Bottom Save Bar -->
    <div id="sticky-save-bar" class="fixed-bottom bg-dark bg-opacity-95 text-white py-3 px-4 shadow-lg border-top border-secondary d-none">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-info-circle text-warning fs-5"></i>
                <div>
                    <span class="fw-bold"><?= lang('settings_unsaved_changes') ?></span>
                    <span class="text-white-50 small ms-2" id="dirty-count-text"><?= lang('settings_unsaved_hint') ?></span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-light px-3" id="btn-discard">
                    <i class="fas fa-undo me-1"></i><?= lang('settings_discard') ?>
                </button>
                <button type="button" class="btn btn-success px-4 fw-semibold" id="btn-save-current">
                    <i class="fas fa-save me-1"></i><?= lang('settings_save_changes') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
    <div id="settings-toast" class="toast align-items-center text-white bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fs-6" id="toast-message"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiBase = window.vars('api_base_url');
    const i18n = window.vars('i18n') || {};
    let activeSection = window.vars('active_section') || 'business';
    let dirtyState = {};

    // 1. Hash Routing Support (#business, #booking, etc.)
    function syncHash() {
        const hash = window.location.hash.replace('#', '');
        if (hash) {
            const parts = hash.split('/');
            const section = parts[0];
            const sub = parts[1];

            const tabLink = document.getElementById('tab-' + section);
            if (tabLink) {
                activeSection = section;
                const bsTab = new bootstrap.Tab(tabLink);
                bsTab.show();

                if (sub) {
                    const subTarget = '#subtab-' + section + '-' + sub;
                    const subBtn = document.querySelector(`[data-subtab-target="${subTarget}"]`);
                    if (subBtn) {
                        subBtn.click();
                    }
                }
            }
        }
    }

    window.addEventListener('hashchange', syncHash);
    syncHash();

    // Main tabs click handler
    document.querySelectorAll('#settings-main-tabs a[data-bs-toggle="pill"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const targetId = e.target.getAttribute('href');
            activeSection = targetId.replace('#section-', '');
            window.location.hash = activeSection;
            updateSaveBar();
        });
    });

    // Sub-nav click handler
    document.querySelectorAll('[data-subtab-target]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetSelector = this.getAttribute('data-subtab-target');
            const parentNav = this.closest('.nav');
            parentNav.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            const targetEl = document.querySelector(targetSelector);
            if (targetEl) {
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // 2. Dirty State Tracking
    document.querySelectorAll('.settings-form').forEach(form => {
        form.addEventListener('change', function(e) {
            const sec = this.getAttribute('data-section');
            dirtyState[sec] = true;
            updateSaveBar();
        });
        form.addEventListener('input', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                const sec = this.getAttribute('data-section');
                dirtyState[sec] = true;
                updateSaveBar();
            }
        });
    });

    function updateSaveBar() {
        const saveBar = document.getElementById('sticky-save-bar');
        if (dirtyState[activeSection]) {
            saveBar.classList.remove('d-none');
        } else {
            saveBar.classList.add('d-none');
        }
    }

    // 3. Save Handler
    document.getElementById('btn-save-current').addEventListener('click', function() {
        const form = document.getElementById('form-' + activeSection);
        if (!form) return;

        const formData = new FormData(form);
        const payload = {};
        for (let [key, val] of formData.entries()) {
            payload[key] = val;
        }

        // Include unchecked checkboxes as 0
        form.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            if (!cb.checked) {
                payload[cb.name] = '0';
            }
        });

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + (i18n.saving || 'Kaydediliyor...');

        fetch(apiBase + '/' + activeSection, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ settings: payload })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> ' + (i18n.save_changes || 'Değişiklikleri Kaydet');

            if (data.success) {
                dirtyState[activeSection] = false;
                updateSaveBar();
                showToast(data.message || i18n.saved_success || 'Ayarlar başarıyla kaydedildi!', 'bg-success');
            } else {
                showToast(data.message || i18n.save_error || 'Ayar kaydedilirken bir hata oluştu.', 'bg-danger');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> ' + (i18n.save_changes || 'Değişiklikleri Kaydet');
            showToast('Ağ hatası oluştu: ' + err.message, 'bg-danger');
        });
    });

    // 4. Discard Handler
    document.getElementById('btn-discard').addEventListener('click', function() {
        if (confirm(i18n.discard_confirm || 'Kaydedilmemiş değişiklikleri geri almak istediğinize emin misiniz?')) {
            location.reload();
        }
    });

    // 5. Search Filter
    document.getElementById('settings-search').addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        document.querySelectorAll('.setting-field').forEach(field => {
            const text = field.innerText.toLowerCase();
            if (query === '' || text.includes(query)) {
                field.style.display = '';
            } else {
                field.style.display = 'none';
            }
        });
    });
});

// Toast notification helper
function showToast(msg, bgClass = 'bg-primary') {
    const toastEl = document.getElementById('settings-toast');
    const toastMsg = document.getElementById('toast-message');
    toastEl.className = 'toast align-items-center text-white border-0 ' + bgClass;
    toastMsg.innerText = msg;
    const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
    toast.show();
}

// Reveal secret via AJAX
function revealSecret(key, inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;

    if (input.type === 'text') {
        input.type = 'password';
        return;
    }

    const i18n = window.vars('i18n') || {};

    fetch(window.vars('api_base_url') + '/reveal_secret', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ key: key })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            input.value = d.value;
            input.type = 'text';
            showToast(i18n.secret_revealed || 'Gizli anahtar gösterildi. Sayfadan ayrıldığınızda tekrar gizlenecektir.', 'bg-info');
        } else {
            showToast(d.message || i18n.view_only_notice || 'Yetki hatası', 'bg-danger');
        }
    });
}

// Copy secret
function copySecret(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const i18n = window.vars('i18n') || {};

    if (input.value.includes('••••')) {
        const key = input.getAttribute('name') || 'agent_api_key';
        fetch(window.vars('api_base_url') + '/reveal_secret', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ key: key })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                navigator.clipboard.writeText(d.value);
                showToast(i18n.copied_to_clipboard || 'Gizli anahtar panoya kopyalandı!', 'bg-success');
            }
        });
    } else {
        navigator.clipboard.writeText(input.value);
        showToast(i18n.copied_to_clipboard || 'Anahtar panoya kopyalandı!', 'bg-success');
    }
}

// Rotate agent API key
function rotateAgentKey() {
    const i18n = window.vars('i18n') || {};
    if (!confirm(i18n.rotate_confirm || 'Agent API anahtarını yenilemek istediğinize emin misiniz? Eski anahtarı kullanan tüm AI asistanlar ve MCP bağlantıları kesilecektir!')) {
        return;
    }

    fetch(window.vars('api_base_url') + '/rotate_agent_key', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById('mcp-agent-key').value = d.agent_api_key;
            document.getElementById('mcp-agent-key').type = 'text';
            showToast(i18n.rotate_success || 'Yeni Agent API anahtarı başarıyla üretildi!', 'bg-success');
        } else {
            showToast(d.message || 'Hata oluştu', 'bg-danger');
        }
    });
}

// Connectivity test
function testConnection() {
    const i18n = window.vars('i18n') || {};
    fetch(window.vars('api_base_url') + '/test_ping', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(d => {
        if (d.success && d.status === 'online') {
            showToast((i18n.connection_successful || 'Bağlantı başarılı!') + ` (${d.latency_ms}ms)`, 'bg-success');
        } else {
            showToast(i18n.connection_failed || 'Sunucu yanıt vermedi!', 'bg-danger');
        }
    })
    .catch(err => {
        showToast('Bağlantı hatası: ' + err.message, 'bg-danger');
    });
}
</script>

<?php end_section('content'); ?>
