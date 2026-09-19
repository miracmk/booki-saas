<link rel="manifest" href="<?= base_url('manifest.json') ?>">
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js');
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
$header_company_logo = vars('company_logo') ?: base_url('assets/img/logo.png');
?>
<!-- Mobile Top Navigation Bar -->
<nav id="header" class="d-md-none navbar navbar-dark bg-primary py-2 px-2">
    <button type="button" class="btn btn-link text-white p-1" data-bs-toggle="offcanvas" data-bs-target="#sidebar"
            aria-controls="sidebar" aria-label="Menüyü aç">
        <i class="fas fa-bars fa-lg"></i>
    </button>
    <span class="text-white fw-bold ms-2 flex-grow-1" style="font-size: 15px;"><?= e($header_company_name) ?></span>
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

<!-- Sidebar Navigation -->
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

        <!-- Global Search / Command Center Trigger & Quick Action -->
        <div class="px-3 pb-2 pt-1">
            <button type="button" class="btn btn-light bg-opacity-10 text-white border-0 w-100 text-start d-flex align-items-center justify-content-between py-2 px-2 rounded-3 mb-2" onclick="openOmnisearch()" style="background: rgba(255,255,255,0.12);">
                <span class="small"><i class="fas fa-search me-2 text-white-50"></i>Hızlı Ara...</span>
                <span class="badge bg-dark bg-opacity-50 text-white-50 font-monospace" style="font-size: 10px;">⌘K</span>
            </button>
            <div class="dropdown w-100">
                <button class="btn btn-warning w-100 fw-bold btn-sm py-2 rounded-3 dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-plus-circle me-1"></i> Hızlı İşlem
                </button>
                <ul class="dropdown-menu shadow border-0 rounded-3">
                    <li><a class="dropdown-item py-2" href="<?= site_url('calendar') ?>"><i class="fas fa-calendar-plus text-primary me-2"></i>Yeni Randevu</a></li>
                    <li><a class="dropdown-item py-2" href="<?= site_url('customers') ?>"><i class="fas fa-user-plus text-success me-2"></i>Yeni Müşteri</a></li>
                    <li><a class="dropdown-item py-2" href="<?= site_url('adisyons') ?>"><i class="fas fa-receipt text-warning me-2"></i>Yeni Adisyon / Sipariş</a></li>
                    <li><a class="dropdown-item py-2" href="<?= site_url('checkin') ?>"><i class="fas fa-sign-in-alt text-info me-2"></i>Müşteri Girişi (Check-in)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2" href="<?= site_url('packages') ?>"><i class="fas fa-box text-secondary me-2"></i>Paket Satışı</a></li>
                    <li><a class="dropdown-item py-2" href="<?= site_url('memberships') ?>"><i class="fas fa-id-card text-secondary me-2"></i>Üyelik Satışı</a></li>
                    <li><a class="dropdown-item py-2" href="<?= site_url('expenses') ?>"><i class="fas fa-file-invoice-dollar text-danger me-2"></i>Gider Ekle</a></li>
                </ul>
            </div>
        </div>

        <!-- Grouped Navigation Menu (Collapsible Accordion & Smooth Scroll) -->
        <ul class="nav flex-column flex-grow-1 px-2 sidebar-nav mt-1">
            <!-- Dashboard -->
            <li class="nav-item mb-1 <?= $active_menu == 'dashboard' ? 'active' : '' ?>">
                <a href="<?= site_url('dashboard') ?>" class="nav-link text-white d-flex align-items-center">
                    <i class="fas fa-gauge-high me-2 text-primary" style="width: 20px;"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- GROUP 1: OPERASYON -->
            <?php
            $is_operations_active = in_array($active_menu, [PRIV_APPOINTMENTS, 'checkin', PRIV_WAITLIST, PRIV_STATIONS, PRIV_SERVICES]);
            ?>
            <li class="nav-item sidebar-group mb-1">
                <a class="nav-link text-white d-flex justify-content-between align-items-center sidebar-group-toggle <?= $is_operations_active ? 'active-parent' : '' ?>"
                   href="#sidebar-menu-operations" data-bs-toggle="collapse" role="button"
                   aria-expanded="<?= $is_operations_active ? 'true' : 'false' ?>" aria-controls="sidebar-menu-operations">
                    <span class="d-flex align-items-center">
                        <i class="fas fa-calendar-check me-2 text-info" style="width: 20px;"></i>
                        <span class="fw-semibold">Operasyon</span>
                    </span>
                    <i class="fas fa-chevron-down small chevron-icon text-white-50"></i>
                </a>
                <div class="collapse <?= $is_operations_active ? 'show' : '' ?>" id="sidebar-menu-operations">
                    <ul class="nav flex-column sub-nav-list">
                        <li class="nav-item <?= $active_menu == PRIV_APPOINTMENTS ? 'active' : '' ?>">
                            <a href="<?= site_url('calendar' . (vars('calendar_view') === CALENDAR_VIEW_TABLE ? '?view=table' : '')) ?>" class="nav-link text-white">
                                <i class="fas fa-calendar-alt me-2"></i>
                                <?= lang('calendar') ?>
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu == 'checkin' ? 'active' : '' ?>">
                            <a href="<?= site_url('checkin') ?>" class="nav-link text-white">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Giriş / Kiosk
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu == PRIV_WAITLIST ? 'active' : '' ?>">
                            <a href="<?= site_url('waitlist') ?>" class="nav-link text-white">
                                <i class="fas fa-hourglass-half me-2"></i>
                                Bekleme Listesi
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu == PRIV_STATIONS ? 'active' : '' ?>">
                            <a href="<?= site_url('stations') ?>" class="nav-link text-white">
                                <i class="fas fa-door-open me-2"></i>
                                İstasyonlar & Odalar
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu == PRIV_SERVICES ? 'active' : '' ?>">
                            <a href="<?= site_url('services') ?>" class="nav-link text-white">
                                <i class="fas fa-business-time me-2"></i>
                                Hizmetler & Menü
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- GROUP 2: MÜŞTERİLER & CRM -->
            <?php
            $is_customers_active = in_array($active_menu, [PRIV_CUSTOMERS, 'packages', PRIV_MEMBERSHIPS]);
            ?>
            <li class="nav-item sidebar-group mb-1">
                <a class="nav-link text-white d-flex justify-content-between align-items-center sidebar-group-toggle <?= $is_customers_active ? 'active-parent' : '' ?>"
                   href="#sidebar-menu-customers" data-bs-toggle="collapse" role="button"
                   aria-expanded="<?= $is_customers_active ? 'true' : 'false' ?>" aria-controls="sidebar-menu-customers">
                    <span class="d-flex align-items-center">
                        <i class="fas fa-user-friends me-2 text-success" style="width: 20px;"></i>
                        <span class="fw-semibold">Müşteriler & CRM</span>
                    </span>
                    <i class="fas fa-chevron-down small chevron-icon text-white-50"></i>
                </a>
                <div class="collapse <?= $is_customers_active ? 'show' : '' ?>" id="sidebar-menu-customers">
                    <ul class="nav flex-column sub-nav-list">
                        <li class="nav-item <?= $active_menu == PRIV_CUSTOMERS ? 'active' : '' ?>">
                            <a href="<?= site_url('customers') ?>" class="nav-link text-white">
                                <i class="fas fa-users me-2"></i>
                                <?= lang('customers') ?>
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu == 'packages' ? 'active' : '' ?>">
                            <a href="<?= site_url('packages') ?>" class="nav-link text-white">
                                <i class="fas fa-box me-2"></i>
                                Paket Seanslar
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu == PRIV_MEMBERSHIPS ? 'active' : '' ?>">
                            <a href="<?= site_url('memberships') ?>" class="nav-link text-white">
                                <i class="fas fa-id-card me-2"></i>
                                Üyelikler & Planlar
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- GROUP 3: SATIŞ & FİNANS -->
            <?php
            $is_finance_active = in_array($active_menu, ['adisyons', 'finance', PRIV_POS, PRIV_INVOICES, 'expenses']);
            ?>
            <li class="nav-item sidebar-group mb-1">
                <a class="nav-link text-white d-flex justify-content-between align-items-center sidebar-group-toggle <?= $is_finance_active ? 'active-parent' : '' ?>"
                   href="#sidebar-menu-finance" data-bs-toggle="collapse" role="button"
                   aria-expanded="<?= $is_finance_active ? 'true' : 'false' ?>" aria-controls="sidebar-menu-finance">
                    <span class="d-flex align-items-center">
                        <i class="fas fa-wallet me-2 text-warning" style="width: 20px;"></i>
                        <span class="fw-semibold">Satış & Finans</span>
                    </span>
                    <i class="fas fa-chevron-down small chevron-icon text-white-50"></i>
                </a>
                <div class="collapse <?= $is_finance_active ? 'show' : '' ?>" id="sidebar-menu-finance">
                    <ul class="nav flex-column sub-nav-list">
                        <li class="nav-item <?= $active_menu == 'adisyons' ? 'active' : '' ?>">
                            <a href="<?= site_url('adisyons') ?>" class="nav-link text-white">
                                <i class="fas fa-receipt me-2"></i>
                                Adisyonlar
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu == 'finance' ? 'active' : '' ?>">
                            <a href="<?= site_url('finance') ?>" class="nav-link text-white">
                                <i class="fas fa-chart-line me-2"></i>
                                Finans & Kasa
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu == PRIV_POS ? 'active' : '' ?>">
                            <a href="<?= site_url('pos') ?>" class="nav-link text-white">
                                <i class="fas fa-cash-register me-2"></i>
                                Hızlı Satış (POS)
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu == PRIV_INVOICES ? 'active' : '' ?>">
                            <a href="<?= site_url('invoices') ?>" class="nav-link text-white">
                                <i class="fas fa-file-invoice-dollar me-2"></i>
                                Faturalar & e-Fatura
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu == 'expenses' ? 'active' : '' ?>">
                            <a href="<?= site_url('expenses') ?>" class="nav-link text-white">
                                <i class="fas fa-money-bill-wave me-2"></i>
                                Gider Yönetimi
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- GROUP 4: RESTORAN MODÜLÜ -->
            <?php
            $is_restaurant_active = in_array($active_menu, ['restaurant_floor_plan', 'restaurant_reservations']);
            ?>
            <li class="nav-item sidebar-group mb-1">
                <a class="nav-link text-white d-flex justify-content-between align-items-center sidebar-group-toggle <?= $is_restaurant_active ? 'active-parent' : '' ?>"
                   href="#sidebar-menu-restaurant" data-bs-toggle="collapse" role="button"
                   aria-expanded="<?= $is_restaurant_active ? 'true' : 'false' ?>" aria-controls="sidebar-menu-restaurant">
                    <span class="d-flex align-items-center">
                        <i class="fas fa-utensils me-2 text-danger" style="width: 20px;"></i>
                        <span class="fw-semibold">Restoran Modülü</span>
                    </span>
                    <i class="fas fa-chevron-down small chevron-icon text-white-50"></i>
                </a>
                <div class="collapse <?= $is_restaurant_active ? 'show' : '' ?>" id="sidebar-menu-restaurant">
                    <ul class="nav flex-column sub-nav-list">
                        <li class="nav-item <?= $active_menu == 'restaurant_floor_plan' ? 'active' : '' ?>">
                            <a href="<?= site_url('restaurant') ?>" class="nav-link text-white">
                                <i class="fas fa-border-all me-2"></i>
                                Masa Planı
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu == 'restaurant_reservations' ? 'active' : '' ?>">
                            <a href="<?= site_url('restaurant/reservations') ?>" class="nav-link text-white">
                                <i class="fas fa-calendar-check me-2"></i>
                                Rezervasyonlar
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- GROUP 5: ENVANTER & ÜRÜNLER -->
            <li class="nav-item mb-1 <?= $active_menu == 'products' ? 'active' : '' ?>">
                <a href="<?= site_url('products') ?>" class="nav-link text-white d-flex align-items-center">
                    <i class="fas fa-boxes me-2 text-warning" style="width: 20px;"></i>
                    <span>Ürünler & Stok</span>
                </a>
            </li>

            <!-- GROUP 6: EKİP & PERSONEL -->
            <li class="nav-item mb-1 <?= $active_menu == PRIV_USERS ? 'active' : '' ?>">
                <a href="<?= site_url('providers') ?>" class="nav-link text-white d-flex align-items-center">
                    <i class="fas fa-user-tie me-2 text-primary" style="width: 20px;"></i>
                    <span>Personel & Uzmanlar</span>
                </a>
            </li>

            <!-- GROUP 7: PAZARLAMA & İTİBAR -->
            <?php
            $is_marketing_active = in_array($active_menu, [PRIV_MARKETING, PRIV_REVIEWS]);
            ?>
            <li class="nav-item sidebar-group mb-1">
                <a class="nav-link text-white d-flex justify-content-between align-items-center sidebar-group-toggle <?= $is_marketing_active ? 'active-parent' : '' ?>"
                   href="#sidebar-menu-marketing" data-bs-toggle="collapse" role="button"
                   aria-expanded="<?= $is_marketing_active ? 'true' : 'false' ?>" aria-controls="sidebar-menu-marketing">
                    <span class="d-flex align-items-center">
                        <i class="fas fa-bullhorn me-2 text-info" style="width: 20px;"></i>
                        <span class="fw-semibold">Pazarlama & İtibar</span>
                    </span>
                    <i class="fas fa-chevron-down small chevron-icon text-white-50"></i>
                </a>
                <div class="collapse <?= $is_marketing_active ? 'show' : '' ?>" id="sidebar-menu-marketing">
                    <ul class="nav flex-column sub-nav-list">
                        <li class="nav-item <?= $active_menu == PRIV_MARKETING ? 'active' : '' ?>">
                            <a href="<?= site_url('marketing') ?>" class="nav-link text-white">
                                <i class="fas fa-paper-plane me-2"></i>
                                Kampanyalar
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu == PRIV_REVIEWS ? 'active' : '' ?>">
                            <a href="<?= site_url('reviews') ?>" class="nav-link text-white">
                                <i class="fas fa-star me-2"></i>
                                Müşteri Yorumları
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- GROUP 8: RAPORLAR & AI -->
            <?php
            $is_reports_active = in_array($active_menu, [PRIV_REPORTS, PRIV_AI_AGENT]);
            ?>
            <li class="nav-item sidebar-group mb-1">
                <a class="nav-link text-white d-flex justify-content-between align-items-center sidebar-group-toggle <?= $is_reports_active ? 'active-parent' : '' ?>"
                   href="#sidebar-menu-reports" data-bs-toggle="collapse" role="button"
                   aria-expanded="<?= $is_reports_active ? 'true' : 'false' ?>" aria-controls="sidebar-menu-reports">
                    <span class="d-flex align-items-center">
                        <i class="fas fa-chart-pie me-2 text-success" style="width: 20px;"></i>
                        <span class="fw-semibold">Raporlama & AI</span>
                    </span>
                    <i class="fas fa-chevron-down small chevron-icon text-white-50"></i>
                </a>
                <div class="collapse <?= $is_reports_active ? 'show' : '' ?>" id="sidebar-menu-reports">
                    <ul class="nav flex-column sub-nav-list">
                        <li class="nav-item <?= $active_menu == PRIV_REPORTS ? 'active' : '' ?>">
                            <a href="<?= site_url('reports') ?>" class="nav-link text-white">
                                <i class="fas fa-chart-bar me-2"></i>
                                İşletme Raporları
                            </a>
                        </li>
                        <li class="nav-item <?= $active_menu == PRIV_AI_AGENT ? 'active' : '' ?>">
                            <a href="<?= site_url('ai_agent') ?>" class="nav-link text-white">
                                <i class="fas fa-robot me-2"></i>
                                AI Asistan
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>

        <!-- User & Settings Footer -->
        <div class="border-top border-light border-opacity-25 p-2">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link text-white d-flex justify-content-between align-items-center" href="#"
                       data-bs-toggle="collapse" data-bs-target="#sidebar-account-collapse">
                        <span><i class="fas fa-user me-2"></i><?= e(vars('user_display_name')) ?></span>
                        <i class="fas fa-chevron-down small"></i>
                    </a>
                    <div class="collapse" id="sidebar-account-collapse">
                        <ul class="nav flex-column ps-4">
                            <?php if (can('view', PRIV_SYSTEM_SETTINGS)): ?>
                                <li class="nav-item"><a class="nav-link text-white-50" href="<?= site_url('onboarding') ?>"><i class="fas fa-magic me-2"></i>Sektör Şablonları & Sihirbaz</a></li>
                                <li class="nav-item"><a class="nav-link text-white-50" href="<?= site_url('general_settings') ?>"><i class="fas fa-cogs me-2"></i><?= lang('settings') ?></a></li>
                                <li class="nav-item"><a class="nav-link text-white-50" href="<?= site_url('audit_log') ?>"><i class="fas fa-clipboard-list me-2"></i>Denetim Kayıtları</a></li>
                                <li class="nav-item"><a class="nav-link text-white-50" href="<?= site_url('data_requests') ?>"><i class="fas fa-shield-alt me-2"></i>Veri Talepleri (KVKK)</a></li>
                            <?php endif; ?>
                            <li class="nav-item"><a class="nav-link text-white-50" href="<?= site_url('account') ?>"><i class="fas fa-user me-2"></i><?= lang('account') ?></a></li>
                            <li class="nav-item"><a class="nav-link text-white-50" href="<?= site_url('booking') ?>" target="_blank"><i class="fas fa-external-link me-2"></i>Müşteri Randevu Sayfası</a></li>
                            <li class="nav-item"><a class="nav-link text-white-50" href="<?= site_url('logout') ?>"><i class="fas fa-sign-out me-2"></i><?= lang('log_out') ?></a></li>
                        </ul>
                    </div>
                </li>
            </ul>
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
