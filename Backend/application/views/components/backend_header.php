<link rel="manifest" href="<?= base_url('manifest.json') ?>">
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js?v=4');
}
</script>
<?php
/**
 * Local variables.
 *
 * @var string $active_menu
 * @var string $company_logo
 */
?>

<?php
$expiry_warning = vars('expiry_warning');
if ($expiry_warning): ?>
    <div class="w-100 text-center py-2 px-3" style="background: #fff4e0; color: #b3720a; font-size: .85rem;">
        <?= e($expiry_warning['label']) ?> bitimine <strong><?= e($expiry_warning['days_left']) ?> gün</strong> kaldı
        (<?= e($expiry_warning['date']) ?>) - devam etmek için lütfen bizimle iletişime geçin.
    </div>
<?php endif; ?>

<?php
$header_company_name = vars('company_name') ?: 'BooKi';
$header_company_logo = base_url('assets/img/logo.png');
$active_menu = $active_menu ?? (vars('active_menu') ?: '');
?>
<!-- Mobile Top Navigation Bar -->
<nav id="header" class="d-md-none navbar navbar-dark bg-primary py-2 px-2">
    <button type="button" class="btn btn-link text-white p-1" data-bs-toggle="offcanvas" data-bs-target="#sidebar"
            aria-controls="sidebar" aria-label="Menüyü aç">
        <i class="fas fa-bars fa-lg"></i>
    </button>
    <span class="text-white fw-bold ms-2 flex-grow-1" style="font-size: 15px;"><?= e($header_company_name) ?></span>
    <a href="<?= site_url('booking') ?>" target="_blank" rel="noopener" class="btn btn-link text-white p-1 me-1" title="Müşteri Randevu Sayfası" aria-label="Randevu Sayfası">
        <i class="fas fa-external-link-alt"></i>
    </a>
    <button type="button" class="btn btn-link text-white p-1 me-1" onclick="openOmnisearch()" aria-label="Arama">
        <i class="fas fa-search"></i>
    </button>
    <?php if (can('view', PRIV_CUSTOMERS)): ?>
        <div class="dropdown">
            <button type="button" class="btn btn-link text-white position-relative p-1 kcc-notif-trigger"
                    data-bs-toggle="dropdown" aria-label="Bildirimler">
                <i class="fas fa-bell"></i>
                <span class="badge rounded-pill bg-danger kcc-notif-badge" style="display: none;">0</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end kcc-notif-panel">
                <div class="kcc-notif-header">Bildirimler</div>
                <div class="kcc-notif-list"></div>
            </div>
        </div>
    <?php endif; ?>
</nav>

<!-- Sidebar Navigation (Desktop & Mobile Offcanvas from unified schema) -->
<nav id="sidebar" class="offcanvas-md offcanvas-start bg-primary text-white" tabindex="-1"
     aria-labelledby="sidebar-label">
    <div class="offcanvas-header d-md-none">
        <h6 class="offcanvas-title text-white" id="sidebar-label"><?= e($header_company_name) ?></h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                data-bs-target="#sidebar" aria-label="Kapat"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-0">
        <!-- Logo Header -->
        <div id="header-logo" class="d-none d-md-flex align-items-center p-3">
            <img src="<?= e($header_company_logo) ?>" alt="logo" class="me-2 rounded-2" style="width: 36px; height: 36px; object-fit: contain; background: white; padding: 2px;">
            <div class="flex-grow-1 text-truncate">
                <h6 class="mb-0 fw-bold text-white text-truncate" style="font-size: 14px;"><?= e($header_company_name) ?></h6>
                <small class="d-block text-white-50" style="font-size: 11px;">Business Operating System</small>
            </div>
            <?php if (can('view', PRIV_CUSTOMERS)): ?>
                <div class="dropdown">
                    <button type="button" class="btn btn-link text-white position-relative p-1 kcc-notif-trigger"
                            data-bs-toggle="dropdown" aria-label="Bildirimler">
                        <i class="fas fa-bell"></i>
                        <span class="badge rounded-pill bg-danger kcc-notif-badge" style="display: none;">0</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end kcc-notif-panel">
                        <div class="kcc-notif-header">Bildirimler</div>
                        <div class="kcc-notif-list"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php
        $current_tenant_ctx = function_exists('tenant_context') ? tenant_context() : null;
        $tenant_sub = $current_tenant_ctx['subdomain'] ?? '';
        $mp_url = !empty($tenant_sub)
            ? (function_exists('randevuburada_url') ? randevuburada_url('business/' . rawurlencode($tenant_sub)) : 'https://randevuburada.kibusiness.co/business/' . rawurlencode($tenant_sub))
            : (function_exists('randevuburada_url') ? randevuburada_url() : 'https://randevuburada.kibusiness.co');

        $vert_service = vertical_service();
        $nav_service = navigation_service();
        $nav_groups = $nav_service->forCurrentUser();

        $current_btype = $vert_service->current_business_type();
        $current_bp = $vert_service->get_blueprint($current_btype);
        $current_vert_name = $current_bp['title'] ?? ucfirst($current_btype);
        $demo_roles = $current_bp['demo_roles'] ?? [];

        $current_role_slug = session('role_slug') ?: 'customer';
        $current_job_title = session('job_title') ?: ($current_role_slug === 'admin' ? 'Yönetici' : ucfirst($current_role_slug));
        $current_uri = uri_string();
        ?>

        <!-- Global Search, Context Badges & Role-Aware Quick Actions -->
        <div class="px-3 pb-2 pt-1">
            <!-- Business Type & Role Switcher Badges -->
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge bg-light bg-opacity-25 text-white text-truncate border border-white-50" style="max-width: 140px;" title="<?= e($current_vert_name) ?>">
                    <i class="fas fa-shapes me-1 text-warning"></i><?= e($current_vert_name) ?>
                </span>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-light border-0 py-0 px-2 text-white dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" title="Demo Rol Değiştir" style="background: rgba(255,255,255,0.15); font-size: 11px;">
                        <i class="fas fa-user-circle me-1"></i><?= e($current_job_title) ?>
                    </button>
                    <?php if (!empty($demo_roles)): ?>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                        <li class="dropdown-header small text-uppercase">Demo Rol Değiştir</li>
                        <?php foreach ($demo_roles as $dr): ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-center justify-content-between py-2 <?= $current_role_slug === $dr['slug'] ? 'active fw-bold' : '' ?>" href="<?= site_url('demo/switch_role/' . $dr['slug']) ?>">
                                    <span><i class="fas fa-<?= e($dr['icon'] ?? 'user') ?> me-2 text-secondary"></i><?= e($dr['name']) ?></span>
                                    <?php if ($current_role_slug === $dr['slug']): ?>
                                        <i class="fas fa-check small ms-2 text-primary"></i>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Public Booking & Showcase Links (Open in New Tab) -->
            <div class="d-flex gap-2 mb-2">
                <a href="<?= site_url('booking') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-light bg-opacity-10 text-white border-0 flex-fill py-1 px-2 text-truncate d-flex align-items-center justify-content-center text-decoration-none" style="background: rgba(255,255,255,0.12); font-size: 11px;" title="Müşteri Randevu Sayfası (Yeni Sekme)">
                    <i class="fas fa-external-link-alt text-info me-1"></i> Randevu Al
                </a>
                <?php if (!empty($mp_url)): ?>
                    <a href="<?= e($mp_url) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-light bg-opacity-10 text-white border-0 flex-fill py-1 px-2 text-truncate d-flex align-items-center justify-content-center text-decoration-none" style="background: rgba(255,255,255,0.12); font-size: 11px;" title="RandevuBurada Vitrin Sayfam (Yeni Sekme)">
                        <i class="fas fa-store text-warning me-1"></i> Vitrinim
                    </a>
                <?php endif; ?>
            </div>

            <!-- Command Palette trigger -->
            <button type="button" class="btn btn-light bg-opacity-10 text-white border-0 w-100 text-start d-flex align-items-center justify-content-between py-2 px-2 rounded-3 mb-2" onclick="openOmnisearch()" style="background: rgba(255,255,255,0.12);">
                <span class="small"><i class="fas fa-search me-2 text-white-50"></i>Hızlı Ara...</span>
                <span class="badge bg-dark bg-opacity-50 text-white-50 font-monospace" style="font-size: 10px;">⌘K</span>
            </button>

            <!-- Role-Aware Quick Action Dropdown -->
            <div class="dropdown w-100">
                <button class="btn w-100 fw-bold btn-sm py-2 rounded-3 dropdown-toggle shadow-sm text-white" id="sidebar-quick-action-btn" type="button" data-bs-toggle="dropdown" style="background: var(--bs-primary) !important; background-color: var(--bs-primary) !important; background-image: none !important; border-color: var(--bs-primary) !important;">
                    <i class="fas fa-bolt me-1"></i> Hızlı İşlem
                </button>
                <ul class="dropdown-menu shadow border-0 rounded-3">
                    <?php if (module_enabled('calendar') && can('add', PRIV_APPOINTMENTS)): ?>
                        <li><a class="dropdown-item py-2" href="<?= site_url('calendar') ?>"><i class="fas fa-calendar-plus text-primary me-2"></i>Yeni <?= e(industry_term('appointment_label', 'Randevu')) ?></a></li>
                    <?php endif; ?>
                    <?php if (module_enabled('customers') && can('add', PRIV_CUSTOMERS)): ?>
                        <li><a class="dropdown-item py-2" href="<?= site_url('customers') ?>"><i class="fas fa-user-plus text-success me-2"></i>Yeni <?= e(industry_term('customer_label', 'Müşteri')) ?></a></li>
                    <?php endif; ?>
                    <?php if (module_enabled('restaurant_floor_plan') && can('view', 'restaurant_floor_plan')): ?>
                        <li><a class="dropdown-item py-2" href="<?= site_url('restaurant') ?>"><i class="fas fa-border-all text-danger me-2"></i>Canlı Masa Planı</a></li>
                    <?php endif; ?>
                    <?php if (module_enabled('adisyon') && can('add', 'adisyons')): ?>
                        <li><a class="dropdown-item py-2" href="<?= site_url('adisyons') ?>"><i class="fas fa-receipt text-warning me-2"></i>Yeni Adisyon / Sipariş</a></li>
                    <?php endif; ?>
                    <?php if (module_enabled('checkin') && can('view', 'checkin')): ?>
                        <li><a class="dropdown-item py-2" href="<?= site_url('checkin') ?>"><i class="fas fa-sign-in-alt text-info me-2"></i><?= e(industry_term('customer_label', 'Müşteri')) ?> Girişi (Check-in)</a></li>
                    <?php endif; ?>
                    <?php if (module_enabled('pos') && can('view', PRIV_POS)): ?>
                        <li><a class="dropdown-item py-2" href="<?= site_url('pos') ?>"><i class="fas fa-cash-register text-success me-2"></i>Hızlı Satış (POS)</a></li>
                    <?php endif; ?>
                    <?php if (module_enabled('packages') && can('add', 'packages')): ?>
                        <li><a class="dropdown-item py-2" href="<?= site_url('packages') ?>"><i class="fas fa-box text-secondary me-2"></i>Paket Satışı</a></li>
                    <?php endif; ?>
                    <?php if (module_enabled('memberships') && can('add', PRIV_MEMBERSHIPS)): ?>
                        <li><a class="dropdown-item py-2" href="<?= site_url('memberships') ?>"><i class="fas fa-id-card text-secondary me-2"></i>Üyelik Satışı</a></li>
                    <?php endif; ?>
                    <?php if (module_enabled('expenses') && can('add', 'expenses')): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2" href="<?= site_url('expenses') ?>"><i class="fas fa-file-invoice-dollar text-danger me-2"></i>Gider Ekle</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Grouped Navigation Menu (Schema-Driven, Vertical-First & Role-Aware) -->
        <ul class="sidebar-nav flex-grow-1 px-2 mt-1 list-unstyled" id="sidebar-accordion">
            <?php foreach ($nav_groups as $group_key => $group_data): ?>
                <?php
                $items = $group_data['items'] ?? [];
                if (empty($items)) continue;

                $is_group_active = false;
                foreach ($items as $item) {
                    $item_route = trim($item['route'] ?? '', '/');
                    if (($active_menu === ($item['id'] ?? '')) || ($active_menu === $item_route) || (!empty($item_route) && ($current_uri === $item_route || str_starts_with($current_uri, $item_route . '/')))) {
                        $is_group_active = true;
                        break;
                    }
                    if (!empty($item['children'])) {
                        foreach ($item['children'] as $child) {
                            $child_route = trim($child['route'] ?? '', '/');
                            if (($active_menu === ($child['id'] ?? '')) || ($active_menu === $child_route) || (!empty($child_route) && ($current_uri === $child_route || str_starts_with($current_uri, $child_route . '/')))) {
                                $is_group_active = true;
                                break 2;
                            }
                        }
                    }
                }

                if ($group_key === 'dashboard' && count($items) === 1 && empty($items[0]['children'])):
                    $dash_item = $items[0];
                    $is_dash_active = ($active_menu === 'dashboard' || $current_uri === 'dashboard');
                ?>
                    <li class="nav-item mb-1 <?= $is_dash_active ? 'active' : '' ?>">
                        <a href="<?= site_url($dash_item['route']) ?>" class="nav-link text-white d-flex align-items-center">
                            <i class="<?= e($dash_item['icon']) ?> me-2 text-primary" style="width: 20px;"></i>
                            <span><?= e($dash_item['label']) ?></span>
                            <?php if (!empty($dash_item['badge'])): ?>
                                <span class="badge bg-warning text-dark ms-auto"><?= e($dash_item['badge']) ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item sidebar-group mb-1">
                        <a class="nav-link text-white d-flex justify-content-between align-items-center sidebar-group-toggle <?= $is_group_active ? 'active-parent' : '' ?>"
                           href="#sidebar-menu-<?= e($group_key) ?>" data-bs-toggle="collapse" role="button"
                           aria-expanded="<?= $is_group_active ? 'true' : 'false' ?>" aria-controls="sidebar-menu-<?= e($group_key) ?>">
                            <span class="d-flex align-items-center">
                                <i class="<?= e($group_data['icon']) ?> me-2" style="width: 20px;"></i>
                                <span class="fw-semibold"><?= e($group_data['title']) ?></span>
                            </span>
                            <i class="fas fa-chevron-down small chevron-icon text-white-50"></i>
                        </a>
                        <div class="collapse <?= $is_group_active ? 'show' : '' ?>" id="sidebar-menu-<?= e($group_key) ?>" data-bs-parent="#sidebar-accordion">
                            <ul class="sub-nav-list list-unstyled">
                                <?php foreach ($items as $item): ?>
                                    <?php
                                    $item_route = trim($item['route'] ?? '', '/');
                                    $is_item_active = ($active_menu === ($item['id'] ?? '')) || ($active_menu === $item_route) || (!empty($item_route) && ($current_uri === $item_route || str_starts_with($current_uri, $item_route . '/')));
                                    ?>
                                    <?php if (!empty($item['children'])): ?>
                                        <li class="nav-item <?= $is_item_active ? 'active' : '' ?>">
                                            <?php if (!empty($item['route'])): ?>
                                                <a href="<?= site_url($item['route']) ?>" class="nav-link text-white d-flex align-items-center justify-content-between fw-medium">
                                                    <span><i class="<?= e($item['icon']) ?> me-2"></i><?= e($item['label']) ?></span>
                                                    <?php if (!empty($item['badge'])): ?>
                                                        <span class="badge bg-info ms-auto"><?= e($item['badge']) ?></span>
                                                    <?php endif; ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="nav-link text-white-50 small text-uppercase px-3 pt-2 pb-1"><?= e($item['label']) ?></span>
                                            <?php endif; ?>
                                            <ul class="sub-sub-nav-list list-unstyled ps-3">
                                                <?php foreach ($item['children'] as $child): ?>
                                                    <?php
                                                    $child_route = trim($child['route'] ?? '', '/');
                                                    $is_child_active = ($active_menu === ($child['id'] ?? '')) || ($active_menu === $child_route) || (!empty($child_route) && ($current_uri === $child_route || str_starts_with($current_uri, $child_route . '/')));
                                                    ?>
                                                    <li class="nav-item <?= $is_child_active ? 'active' : '' ?>">
                                                        <a href="<?= site_url($child['route']) ?>" class="nav-link text-white small d-flex align-items-center">
                                                            <i class="<?= e($child['icon']) ?> me-2"></i>
                                                            <span><?= e($child['label']) ?></span>
                                                            <?php if (!empty($child['badge'])): ?>
                                                                <span class="badge bg-warning text-dark ms-auto"><?= e($child['badge']) ?></span>
                                                            <?php endif; ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </li>
                                    <?php else: ?>
                                        <li class="nav-item <?= $is_item_active ? 'active' : '' ?>">
                                            <a href="<?= site_url($item['route']) ?>" class="nav-link text-white d-flex align-items-center">
                                                <i class="<?= e($item['icon']) ?> me-2"></i>
                                                <span><?= e($item['label']) ?></span>
                                                <?php if (!empty($item['badge'])): ?>
                                                    <span class="badge bg-warning text-dark ms-auto"><?= e($item['badge']) ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>

        <!-- User Footer Card - Directly links to Account Settings -->
        <div class="p-2 border-top border-light border-opacity-25 sidebar-footer-account mt-auto">
            <div class="d-flex align-items-center justify-content-between">
                <a href="<?= site_url('account') ?>" class="d-flex align-items-center text-white text-decoration-none p-1 rounded-2 sidebar-user-btn text-truncate flex-grow-1 min-w-0 me-1" title="<?= lang('account') ?: 'Profil & Hesap Ayarları' ?>">
                    <div class="avatar-circle flex-shrink-0 bg-white text-primary d-flex align-items-center justify-content-center fw-bold shadow-sm me-2" style="width: 32px; height: 32px; border-radius: 50%; font-size: 13px;">
                        <?= strtoupper(mb_substr(trim(vars('user_display_name') ?: session('job_title') ?: 'U'), 0, 1, 'UTF-8')) ?>
                    </div>
                    <div class="flex-grow-1 overflow-hidden text-start" style="line-height: 1.2;">
                        <div class="fw-semibold text-truncate small text-white"><?= e(vars('user_display_name') ?: 'Hesabım') ?></div>
                        <div class="text-white-50 text-truncate" style="font-size: 11px;">
                            <?= e(session('job_title') ?: (session('role_slug') === 'admin' ? 'Yönetici' : (session('role_slug') === 'customer' ? 'Müşteri' : 'Kullanıcı'))) ?>
                        </div>
                    </div>
                </a>
                <a href="<?= site_url('logout') ?>" class="btn btn-sm text-white-50 hover-text-danger p-1 flex-shrink-0" title="<?= lang('log_out') ?: 'Çıkış Yap' ?>" style="line-height: 1;">
                    <i class="fas fa-sign-out-alt fa-lg"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Global Command Center / Omnisearch Modal (Cmd+K) -->
<div class="modal fade" id="omnisearch-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom bg-light p-3">
                <div class="input-group input-group-lg border-0">
                    <span class="input-group-text bg-transparent border-0 text-muted ps-2"><i class="fas fa-search fa-lg"></i></span>
                    <input type="text" id="omnisearch-input" class="form-control bg-transparent border-0 fs-5" placeholder="Müşteri, randevu, adisyon, fatura, ürün veya masa arayın..." autocomplete="off">
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" style="max-height: 480px; overflow-y: auto;">
                <div id="omnisearch-empty" class="text-center py-5 text-muted">
                    <i class="fas fa-search fa-3x mb-3 text-secondary opacity-50"></i>
                    <p class="mb-0">Aramak istediğiniz terimi yazın (Örn: Ayşe, AD-2026, Masa 4)...</p>
                </div>
                <div id="omnisearch-results" class="list-group list-group-flush"></div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 justify-content-between small text-muted">
                <span><kbd>ESC</kbd> kapatır &bull; <kbd>↵</kbd> seçer</span>
                <span class="fw-semibold">BooKi Command Center</span>
            </div>
        </div>
    </div>
</div>

<div id="notification" style="display: none;"></div>

<div id="loading" class="position-fixed top-0 start-0 w-100 h-100" style="display: none; z-index: 999999; background: rgba(255, 255, 255, 0.75);">
    <div class="any-element animation is-loading d-block mx-auto">
        &nbsp;
    </div>
</div>

<script>
// Global Command Palette Shortcut Listener (Cmd+K / Ctrl+K)
document.addEventListener('keydown', function(e) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        openOmnisearch();
    }
});

function openOmnisearch() {
    const modalEl = document.getElementById('omnisearch-modal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    setTimeout(() => document.getElementById('omnisearch-input').focus(), 300);
}

document.getElementById('omnisearch-input')?.addEventListener('input', function() {
    const q = this.value.trim();
    const resultsBox = document.getElementById('omnisearch-results');
    const emptyBox = document.getElementById('omnisearch-empty');

    if (q.length < 2) {
        resultsBox.innerHTML = '';
        emptyBox.classList.remove('d-none');
        return;
    }

    fetch('<?= site_url('search/global_query?q=') ?>' + encodeURIComponent(q))
        .then(res => res.json())
        .then(data => {
            resultsBox.innerHTML = '';
            if (data.results && data.results.length > 0) {
                emptyBox.classList.add('d-none');
                data.results.forEach(r => {
                    const a = document.createElement('a');
                    a.href = r.url;
                    a.className = 'list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3';
                    a.innerHTML = `
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-${r.badge} bg-opacity-10 text-${r.badge} p-2 me-3" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas ${r.icon}"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark">${r.title}</h6>
                                <small class="text-muted">${r.subtitle}</small>
                            </div>
                        </div>
                        <span class="badge bg-light text-dark border">${r.category}</span>
                    `;
                    resultsBox.appendChild(a);
                });
            } else {
                emptyBox.classList.remove('d-none');
                emptyBox.innerHTML = '<p class="py-4 text-muted mb-0">Eşleşen sonuç bulunamadı.</p>';
            }
        });
});
</script>
