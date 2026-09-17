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
// BooKi (2026-08-26) - "N gün kaldı" advance warning, admin role only (see
// EA_Controller::build_expiry_warning()). The hard cutoff (402, once actually expired) is enforced
// separately in EA_Controller::resolve_tenant() - this is purely the advance notice before that.
$expiry_warning = vars('expiry_warning');
?>
<?php if ($expiry_warning): ?>
    <div class="w-100 text-center py-2 px-3" style="background: #fff4e0; color: #b3720a; font-size: .85rem;">
        <?= e($expiry_warning['label']) ?> bitimine <strong><?= e($expiry_warning['days_left']) ?> gün</strong> kaldı
        (<?= e($expiry_warning['date']) ?>) - devam etmek için lütfen bizimle iletişime geçin.
    </div>
<?php endif; ?>

<?php
// BooKi (2026-09-10) - sidebar navigation redesign. Below the "md" breakpoint (768px),
// Bootstrap's `offcanvas-md` turns #sidebar into a real dismissible offcanvas panel, triggered by
// this thin top bar's hamburger button. At/above "md" it renders as a normal, always-visible,
// fixed-position column (see the CSS block in backend_layout.php) - this thin bar is hidden there
// via `d-md-none`. #header keeps its ID here (not on the sidebar) so calendar_default_view.js's/
// calendar_table_view.js's `$('#header').outerHeight()` height budget still works unmodified: 0 on
// desktop (element hidden), this bar's real height on mobile.
$header_company_name = vars('company_name') ?: 'BooKi';
$header_company_logo = vars('company_logo') ?: base_url('assets/img/logo.png');
?>
<nav id="header" class="d-md-none navbar navbar-dark bg-primary py-2 px-2">
    <button type="button" class="btn btn-link text-white p-1" data-bs-toggle="offcanvas" data-bs-target="#sidebar"
            aria-controls="sidebar" aria-label="Menüyü aç">
        <i class="fas fa-bars fa-lg"></i>
    </button>
    <span class="text-white fw-bold ms-2 flex-grow-1" style="font-size: 15px;"><?= e($header_company_name) ?></span>
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

<nav id="sidebar" class="offcanvas-md offcanvas-start bg-primary text-white" tabindex="-1"
     aria-labelledby="sidebar-label">
    <div class="offcanvas-header d-md-none">
        <h6 class="offcanvas-title text-white" id="sidebar-label"><?= e($header_company_name) ?></h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                data-bs-target="#sidebar" aria-label="Kapat"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-0">
        <div id="header-logo" class="d-none d-md-flex align-items-center p-3">
            <img src="<?= e($header_company_logo) ?>" alt="logo" class="me-2" style="width: 40px; height: 40px;">
            <div class="flex-grow-1">
                <h6 class="mb-0 fw-bold text-white" style="font-size: 14px;"><?= e($header_company_name) ?></h6>
                <small class="d-block text-white-50" style="font-size: 11px;">Online Appointment Scheduler</small>
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

        <ul class="nav flex-column flex-grow-1 overflow-auto px-2 sidebar-nav">
            <?php $hidden = can('view', PRIV_APPOINTMENTS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == 'dashboard' ? 'active' : ''; ?>
            <li class="nav-item <?= $active ?> <?= $hidden ?>">
                <a href="<?= site_url('dashboard') ?>" class="nav-link text-white" data-tippy-content="Genel bakış">
                    <i class="fas fa-gauge-high me-2"></i>
                    Dashboard
                </a>
            </li>

            <?php $has_plan = plan_allows(PRIV_AI_AGENT); ?>
            <?php $hidden = can('view', PRIV_AI_AGENT) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_AI_AGENT ? 'active' : ''; ?>
            <li class="nav-item <?= $active ?> <?= $hidden ?>">
                <a <?php if ($has_plan): ?>href="<?= site_url('ai_agent') ?>"<?php else: ?>href="#" data-locked="true" data-feature="<?php echo PRIV_AI_AGENT; ?>" data-tier="Elite"<?php endif; ?> class="nav-link text-white" data-tippy-content="AI Asistan">
                    <i class="fas fa-robot me-2"></i>
                    AI Asistan
                <?php if (!$has_plan): ?><i class="fas fa-lock ms-auto text-warning ms-2" title="Elite Plan"></i><?php endif; ?></a>
            </li>

            <?php $hidden = can('view', PRIV_APPOINTMENTS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_APPOINTMENTS ? 'active' : ''; ?>
            <li class="nav-item <?= $active ?> <?= $hidden ?>">
                <a href="<?= site_url(
                    'calendar' . (vars('calendar_view') === CALENDAR_VIEW_TABLE ? '?view=table' : ''),
                ) ?>"
                   class="nav-link text-white"
                   data-tippy-content="<?= lang('manage_appointment_record_hint') ?>">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <?= lang('calendar') ?>
                </a>
            </li>

            <?php $hidden = can('view', PRIV_CUSTOMERS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_CUSTOMERS ? 'active' : ''; ?>
            <li class="nav-item <?= $active ?> <?= $hidden ?>">
                <a href="<?= site_url('customers') ?>" class="nav-link text-white"
                   data-tippy-content="<?= lang('manage_customers_hint') ?>">
                    <i class="fas fa-user-friends me-2"></i>
                    <?= lang('customers') ?>
                </a>
            </li>

            <?php $hidden = can('view', PRIV_SERVICES) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_SERVICES ? 'active' : ''; ?>
            <li class="nav-item <?= $active ?> <?= $hidden ?>">
                <a class="nav-link text-white d-flex justify-content-between align-items-center" href="#"
                   data-bs-toggle="collapse" data-bs-target="#sidebar-services-collapse"
                   data-tippy-content="<?= lang('manage_services_hint') ?>">
                    <span><i class="fas fa-business-time me-2"></i><?= lang('services') ?></span>
                    <i class="fas fa-chevron-down small"></i>
                </a>
                <div class="collapse <?= $active ? 'show' : '' ?>" id="sidebar-services-collapse">
                    <ul class="nav flex-column ps-4">
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="<?= site_url('services') ?>">
                                <?= lang('services') ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="<?= site_url('service_categories') ?>">
                                <?= lang('categories') ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <?php $hidden = can('view', PRIV_STATIONS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_STATIONS ? 'active' : ''; ?>
            <li class="nav-item <?= $active ?> <?= $hidden ?>">
                <a href="<?= site_url('stations') ?>" class="nav-link text-white"
                   data-tippy-content="İstasyonları yönet">
                    <i class="fas fa-door-open me-2"></i>
                    İstasyonlar
                </a>
            </li>

            <?php $has_plan = plan_allows(PRIV_WAITLIST); ?>
            <?php $hidden = can('view', PRIV_WAITLIST) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_WAITLIST ? 'active' : ''; ?>
            <li class="nav-item <?= $active ?> <?= $hidden ?>">
                <a <?php if ($has_plan): ?>href="<?= site_url('waitlist') ?>"<?php else: ?>href="#" data-locked="true" data-feature="<?php echo PRIV_WAITLIST; ?>" data-tier="Basic"<?php endif; ?> class="nav-link text-white"
                   data-tippy-content="Bekleme listesini yönet">
                    <i class="fas fa-hourglass-half me-2"></i>
                    Bekleme Listesi
                <?php if (!$has_plan): ?><i class="fas fa-lock ms-auto text-warning ms-2" title="Basic Plan"></i><?php endif; ?></a>
            </li>

            <?php $has_plan = plan_allows(PRIV_MEMBERSHIPS); ?>
            <?php $hidden = can('view', PRIV_MEMBERSHIPS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_MEMBERSHIPS ? 'active' : ''; ?>
            <li class="nav-item <?= $active ?> <?= $hidden ?>">
                <a <?php if ($has_plan): ?>href="<?= site_url('memberships') ?>"<?php else: ?>href="#" data-locked="true" data-feature="<?php echo PRIV_MEMBERSHIPS; ?>" data-tier="Premium"<?php endif; ?> class="nav-link text-white"
                   data-tippy-content="Üyelikleri yönet">
                    <i class="fas fa-id-card me-2"></i>
                    Üyelikler
                <?php if (!$has_plan): ?><i class="fas fa-lock ms-auto text-warning ms-2" title="Premium Plan"></i><?php endif; ?></a>
            </li>

            <?php $hidden = can('view', PRIV_CUSTOMERS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == 'data_requests' ? 'active' : ''; ?>
            <li class="nav-item <?= $active ?> <?= $hidden ?>">
                <a href="<?= site_url('data_requests') ?>" class="nav-link text-white"
                   data-tippy-content="KVKK veri talepleri">
                    <i class="fas fa-shield-alt me-2"></i>
                    Veri Talepleri
                </a>
            </li>

            <?php $has_plan = plan_allows(PRIV_INVOICES); ?>
            <?php $hidden = can('view', PRIV_INVOICES) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_INVOICES ? 'active' : ''; ?>
            <li class="nav-item <?= $active ?> <?= $hidden ?>">
                <a <?php if ($has_plan): ?>href="<?= site_url('invoices') ?>"<?php else: ?>href="#" data-locked="true" data-feature="<?php echo PRIV_INVOICES; ?>" data-tier="Premium"<?php endif; ?> class="nav-link text-white"
                   data-tippy-content="Faturaları yönet">
                    <i class="fas fa-file-invoice me-2"></i>
                    Faturalar
                <?php if (!$has_plan): ?><i class="fas fa-lock ms-auto text-warning ms-2" title="Premium Plan"></i><?php endif; ?></a>
            </li>

            <?php $has_plan = plan_allows(PRIV_POS); ?>
            <?php $hidden = can('view', PRIV_POS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_POS ? 'active' : ''; ?>
            <li class="nav-item <?= $active ?> <?= $hidden ?>">
                <a <?php if ($has_plan): ?>href="<?= site_url('pos') ?>"<?php else: ?>href="#" data-locked="true" data-feature="<?php echo PRIV_POS; ?>" data-tier="Premium"<?php endif; ?> class="nav-link text-white"
                   data-tippy-content="Satış noktası">
                    <i class="fas fa-cash-register me-2"></i>
                    POS
                <?php if (!$has_plan): ?><i class="fas fa-lock ms-auto text-warning ms-2" title="Premium Plan"></i><?php endif; ?></a>
            </li>

            <?php $has_plan = plan_allows(PRIV_REPORTS); ?>
            <?php $hidden = can('view', PRIV_REPORTS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_REPORTS ? 'active' : ''; ?>
            <li class="nav-item <?= $active ?> <?= $hidden ?>">
                <a <?php if ($has_plan): ?>href="<?= site_url('reports') ?>"<?php else: ?>href="#" data-locked="true" data-feature="<?php echo PRIV_REPORTS; ?>" data-tier="Basic"<?php endif; ?> class="nav-link text-white"
                   data-tippy-content="Günlük ciro raporu">
                    <i class="fas fa-chart-line me-2"></i>
                    Raporlar
                <?php if (!$has_plan): ?><i class="fas fa-lock ms-auto text-warning ms-2" title="Basic Plan"></i><?php endif; ?></a>
            </li>

            <?php $has_plan = plan_allows(PRIV_MARKETING); ?>
            <?php $hidden = can('view', PRIV_MARKETING) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_MARKETING ? 'active' : ''; ?>
            <li class="nav-item <?= $active ?> <?= $hidden ?>">
                <a <?php if ($has_plan): ?>href="<?= site_url('marketing') ?>"<?php else: ?>href="#" data-locked="true" data-feature="<?php echo PRIV_MARKETING; ?>" data-tier="Premium"<?php endif; ?> class="nav-link text-white"
                   data-tippy-content="Pazarlama kampanyaları">
                    <i class="fas fa-bullhorn me-2"></i>
                    Pazarlama
                <?php if (!$has_plan): ?><i class="fas fa-lock ms-auto text-warning ms-2" title="Premium Plan"></i><?php endif; ?></a>
            </li>

            <?php $has_plan = plan_allows(PRIV_REVIEWS); ?>
            <?php $hidden = can('view', PRIV_REVIEWS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_REVIEWS ? 'active' : ''; ?>
            <li class="nav-item <?= $active ?> <?= $hidden ?>">
                <a <?php if ($has_plan): ?>href="<?= site_url('reviews') ?>"<?php else: ?>href="#" data-locked="true" data-feature="<?php echo PRIV_REVIEWS; ?>" data-tier="Premium"<?php endif; ?> class="nav-link text-white"
                   data-tippy-content="Müşteri yorumları">
                    <i class="fas fa-star me-2"></i>
                    Yorumlar
                <?php if (!$has_plan): ?><i class="fas fa-lock ms-auto text-warning ms-2" title="Premium Plan"></i><?php endif; ?></a>
            </li>

            <?php $hidden = can('view', PRIV_USERS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_USERS ? 'active' : ''; ?>
            <li class="nav-item <?= $active ?> <?= $hidden ?>">
                <a class="nav-link text-white d-flex justify-content-between align-items-center" href="#"
                   data-bs-toggle="collapse" data-bs-target="#sidebar-users-collapse"
                   data-tippy-content="<?= lang('manage_users_hint') ?>">
                    <span><i class="fas fa-users me-2"></i><?= lang('users') ?></span>
                    <i class="fas fa-chevron-down small"></i>
                </a>
                <div class="collapse <?= $active ? 'show' : '' ?>" id="sidebar-users-collapse">
                    <ul class="nav flex-column ps-4">
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="<?= site_url('providers') ?>">
                                <?= lang('providers') ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="<?= site_url('secretaries') ?>">
                                <?= lang('secretaries') ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="<?= site_url('admins') ?>">
                                <?= lang('admins') ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>

        <div class="border-top border-light border-opacity-25 p-2">
            <?php $hidden = can('view', PRIV_SYSTEM_SETTINGS) || can('view', PRIV_USER_SETTINGS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_SYSTEM_SETTINGS ? 'active' : ''; ?>
            <ul class="nav flex-column <?= $hidden ?>">
                <li class="nav-item <?= $active ?>">
                    <a class="nav-link text-white d-flex justify-content-between align-items-center" href="#"
                       data-bs-toggle="collapse" data-bs-target="#sidebar-account-collapse"
                       data-tippy-content="<?= lang('settings_hint') ?>">
                        <span><i class="fas fa-user me-2"></i><?= e(vars('user_display_name')) ?></span>
                        <i class="fas fa-chevron-down small"></i>
                    </a>
                    <div class="collapse" id="sidebar-account-collapse">
                        <ul class="nav flex-column ps-4">
                            <?php if (can('view', PRIV_SYSTEM_SETTINGS)): ?>
                                <li class="nav-item">
                                    <a class="nav-link text-white-50" href="<?= site_url('general_settings') ?>">
                                        <i class="fas fa-cogs me-2"></i>
                                        <?= lang('settings') ?>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-white-50" href="<?= site_url('jobs') ?>">
                                        <i class="fas fa-hourglass-start me-2"></i>
                                        İş Kuyruğu
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-white-50" href="<?= site_url('audit_log') ?>">
                                        <i class="fas fa-clipboard-list me-2"></i>
                                        Denetim Kayıtları
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-white-50" href="<?= site_url('google_sync_dashboard') ?>">
                                        <i class="fab fa-google me-2"></i>
                                        Google Takvim Senkron Durumu
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-white-50" href="<?= site_url('custom_domain') ?>">
                                        <i class="fas fa-globe me-2"></i>
                                        Özel Alan Adı
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li class="nav-item">
                                <a class="nav-link text-white-50 kcc-theme-trigger" href="#">
                                    <i class="fas fa-palette me-2"></i>
                                    Renk Teması
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white-50" href="<?= site_url('account') ?>">
                                    <i class="fas fa-user me-2"></i>
                                    <?= lang('account') ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white-50" href="<?= site_url('about') ?>">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <?= lang('about') ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white-50" href="<?= site_url('booking') ?>" target="_blank">
                                    <i class="fas fa-external-link me-2"></i>
                                    <?= lang('booking') ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white-50" href="<?= site_url('logout') ?>">
                                    <i class="fas fa-sign-out me-2"></i>
                                    <?= lang('log_out') ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div id="notification" style="display: none;"></div>

<div id="loading" class="position-fixed top-0 start-0 w-100 h-100" style="display: none; z-index: 999999; background: rgba(255, 255, 255, 0.75);">
    <div class="any-element animation is-loading d-block mx-auto">
        &nbsp;
    </div>
</div>

<!-- Upgrade Plan Modal -->
<div class="modal fade" id="upgrade-plan-modal" tabindex="-1" aria-labelledby="upgradePlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="upgradePlanModalLabel">
                    <i class="fas fa-lock text-warning me-2"></i> <span id="upgrade-feature-name">Özellik Kilitli</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="mb-4">
                    <i class="fas fa-gem fa-4x text-primary"></i>
                </div>
                <h5>Paketinizi Yükseltin</h5>
                <p class="text-muted mt-3">
                    Bu özelliği kullanabilmek için planınızı 
                    <strong class="text-dark" id="upgrade-tier-name">Premium</strong> veya daha üst bir pakete yükseltmeniz gerekmektedir.
                </p>
                <p class="text-muted">
                    Lütfen yöneticiniz veya <?php echo (plan_allows('white_label') && setting('white_label_enabled') == 1) ? 'destek ekibi' : 'BooKi destek ekibi'; ?> ile iletişime geçin.
                </p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <a href="https://kisoftware.com/contact" target="_blank" class="btn btn-primary">İletişime Geçin</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-locked="true"]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // prevent collapse toggles if any
            
            var feature = this.getAttribute('data-feature') || 'Bu Özellik';
            var tier = this.getAttribute('data-tier') || 'Üst';
            
            // Re-map constants if possible, or just capitalize
            var featureNameMap = {
                'reports': 'Raporlar',
                'waitlist': 'Bekleme Listesi',
                'webhooks': 'Webhooks',
                'products': 'Ürün & Stok',
                'marketing': 'Pazarlama',
                'invoices': 'Faturalama',
                'pos': 'Kasa & POS',
                'reviews': 'Değerlendirmeler',
                'memberships': 'Üyelikler',
                'packages': 'Paket Seanslar',
                'branches': 'Şubeler',
                'ai_agent': 'AI Asistan'
            };
            
            var displayFeature = featureNameMap[feature] || feature.replace('PRIV_', '').replace('_', ' ');
            
            document.getElementById('upgrade-feature-name').innerText = displayFeature + ' Kilitli';
            document.getElementById('upgrade-tier-name').innerText = tier;
            
            var upgradeModal = new bootstrap.Modal(document.getElementById('upgrade-plan-modal'));
            upgradeModal.show();
        });
    });
});
</script>
