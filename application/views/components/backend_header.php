<?php
/**
 * Local variables.
 *
 * @var string $active_menu
 * @var string $company_logo
 */
?>

<?php
// Ki Reservation (2026-08-26) - "N gün kaldı" advance warning, admin role only (see
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

<nav id="header" class="navbar navbar-expand-md navbar-dark bg-primary p-0">
    <?php
    // Salon Flora customization - whitelabeling: fall back to the platform's own name/logo only when
    // the tenant hasn't set their own in General Settings (see EA_Controller::load_common_html_vars()).
    $header_company_name = vars('company_name') ?: 'KI RESERVATION';
    $header_company_logo = vars('company_logo') ?: base_url('assets/img/logo.png');
    ?>
    <div id="header-logo" class="navbar-brand p-1 lh-1">
        <img src="<?= e($header_company_logo) ?>" alt="logo" class="float-start me-2" style="width: 45px; height: 45px;">
        <h6 class="mb-1 mt-1 fw-bold text-white" style="font-size: 15px;"><?= e($header_company_name) ?></h6>
        <small class="d-block text-white-50" style="font-size: 12px;">Online Appointment Scheduler</small>
    </div>

    <button type="button" class="navbar-toggler me-1" data-bs-toggle="collapse" data-bs-target="#header-menu">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div id="header-menu" class="collapse navbar-collapse flex-row-reverse px-2">
        <ul class="navbar-nav">
            <?php $hidden = can('view', PRIV_APPOINTMENTS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_APPOINTMENTS ? 'active' : ''; ?>
            <li class="nav-item text-center <?= $active . $hidden ?>" style="min-width: 100px;">
                <a href="<?= site_url(
                    'calendar' . (vars('calendar_view') === CALENDAR_VIEW_TABLE ? '?view=table' : ''),
                ) ?>"
                   class="nav-link text-white fw-light py-3 px-3"
                   data-tippy-content="<?= lang('manage_appointment_record_hint') ?>">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <?= lang('calendar') ?>
                </a>
            </li>

            <?php $hidden = can('view', PRIV_CUSTOMERS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_CUSTOMERS ? 'active' : ''; ?>
            <li class="nav-item text-center <?= $active . $hidden ?>" style="min-width: 100px;">
                <a href="<?= site_url('customers') ?>" class="nav-link text-white fw-light py-3 px-3"
                   data-tippy-content="<?= lang('manage_customers_hint') ?>">
                    <i class="fas fa-user-friends me-2"></i>
                    <?= lang('customers') ?>
                </a>
            </li>

            <?php $hidden = can('view', PRIV_SERVICES) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_SERVICES ? 'active' : ''; ?>
            <li class="nav-item dropdown text-center <?= $active . $hidden ?>" style="min-width: 100px;">
                <a class="nav-link dropdown-toggle text-white fw-light py-3 px-3" href="#" data-bs-toggle="dropdown"
                   data-tippy-content="<?= lang('manage_services_hint') ?>">
                    <i class="fas fa-business-time me-2"></i>
                    <?= lang('services') ?>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="<?= site_url('services') ?>">
                        <?= lang('services') ?>
                    </a>
                    <a class="dropdown-item" href="<?= site_url('service_categories') ?>">
                        <?= lang('categories') ?>
                    </a>
                </div>
            </li>

            <?php $hidden = can('view', PRIV_STATIONS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_STATIONS ? 'active' : ''; ?>
            <li class="nav-item text-center <?= $active . $hidden ?>" style="min-width: 100px;">
                <a href="<?= site_url('stations') ?>" class="nav-link text-white fw-light py-3 px-3"
                   data-tippy-content="İstasyonları yönet">
                    <i class="fas fa-door-open me-2"></i>
                    İstasyonlar
                </a>
            </li>

            <?php $hidden = can('view', PRIV_WAITLIST) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_WAITLIST ? 'active' : ''; ?>
            <li class="nav-item text-center <?= $active . $hidden ?>" style="min-width: 100px;">
                <a href="<?= site_url('waitlist') ?>" class="nav-link text-white fw-light py-3 px-3"
                   data-tippy-content="Bekleme listesini yönet">
                    <i class="fas fa-hourglass-half me-2"></i>
                    Bekleme Listesi
                </a>
            </li>

            <?php $hidden = can('view', PRIV_MEMBERSHIPS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_MEMBERSHIPS ? 'active' : ''; ?>
            <li class="nav-item text-center <?= $active . $hidden ?>" style="min-width: 100px;">
                <a href="<?= site_url('memberships') ?>" class="nav-link text-white fw-light py-3 px-3"
                   data-tippy-content="Üyelikleri yönet">
                    <i class="fas fa-id-card me-2"></i>
                    Üyelikler
                </a>
            </li>

            <?php $hidden = can('view', PRIV_INVOICES) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_INVOICES ? 'active' : ''; ?>
            <li class="nav-item text-center <?= $active . $hidden ?>" style="min-width: 100px;">
                <a href="<?= site_url('invoices') ?>" class="nav-link text-white fw-light py-3 px-3"
                   data-tippy-content="Faturaları yönet">
                    <i class="fas fa-file-invoice me-2"></i>
                    Faturalar
                </a>
            </li>

            <?php $hidden = can('view', PRIV_POS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_POS ? 'active' : ''; ?>
            <li class="nav-item text-center <?= $active . $hidden ?>" style="min-width: 100px;">
                <a href="<?= site_url('pos') ?>" class="nav-link text-white fw-light py-3 px-3"
                   data-tippy-content="Satış noktası">
                    <i class="fas fa-cash-register me-2"></i>
                    POS
                </a>
            </li>

            <?php $hidden = can('view', PRIV_REPORTS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_REPORTS ? 'active' : ''; ?>
            <li class="nav-item text-center <?= $active . $hidden ?>" style="min-width: 100px;">
                <a href="<?= site_url('reports') ?>" class="nav-link text-white fw-light py-3 px-3"
                   data-tippy-content="Günlük ciro raporu">
                    <i class="fas fa-chart-line me-2"></i>
                    Raporlar
                </a>
            </li>

            <?php $hidden = can('view', PRIV_USERS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_USERS ? 'active' : ''; ?>
            <li class="nav-item dropdown text-center <?= $active . $hidden ?>" style="min-width: 100px;">
                <a class="nav-link dropdown-toggle text-white fw-light py-3 px-3" href="#" data-bs-toggle="dropdown"
                   data-tippy-content="<?= lang('manage_users_hint') ?>">
                    <i class="fas fa-users me-2"></i>
                    <?= lang('users') ?>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="<?= site_url('providers') ?>">
                        <?= lang('providers') ?>
                    </a>
                    <a class="dropdown-item" href="<?= site_url('secretaries') ?>">
                        <?= lang('secretaries') ?>
                    </a>
                    <a class="dropdown-item" href="<?= site_url('admins') ?>">
                        <?= lang('admins') ?>
                    </a>
                </div>
            </li>

            <?php $hidden = can('view', PRIV_SYSTEM_SETTINGS) || can('view', PRIV_USER_SETTINGS) ? '' : 'd-none'; ?>
            <?php $active = $active_menu == PRIV_SYSTEM_SETTINGS ? 'active' : ''; ?>
            <li class="nav-item dropdown text-center <?= $active . $hidden ?>" style="min-width: 100px;">
                <a class="nav-link dropdown-toggle text-white fw-light py-3 px-3" href="#" data-bs-toggle="dropdown"
                   data-tippy-content="<?= lang('settings_hint') ?>">
                    <i class="fas fa-user me-2"></i>
                    <?= e(vars('user_display_name')) ?>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <?php if (can('view', PRIV_SYSTEM_SETTINGS)): ?>
                        <a class="dropdown-item" href="<?= site_url('general_settings') ?>">
                            <i class="fas fa-cogs me-2"></i>
                            <?= lang('settings') ?>
                        </a>
                        <a class="dropdown-item" href="<?= site_url('jobs') ?>">
                            <i class="fas fa-hourglass-start me-2"></i>
                            İş Kuyruğu
                        </a>
                        <a class="dropdown-item" href="<?= site_url('audit_log') ?>">
                            <i class="fas fa-clipboard-list me-2"></i>
                            Denetim Kayıtları
                        </a>
                        <a class="dropdown-item" href="<?= site_url('google_sync_dashboard') ?>">
                            <i class="fab fa-google me-2"></i>
                            Google Takvim Senkron Durumu
                        </a>
                        <a class="dropdown-item" href="<?= site_url('license') ?>">
                            <i class="fas fa-key me-2"></i>
                            Lisans
                        </a>
                    <?php endif; ?>

                    <a class="dropdown-item" href="<?= site_url('account') ?>">
                        <i class="fas fa-user me-2"></i>
                        <?= lang('account') ?>
                    </a>
                    <a class="dropdown-item" href="<?= site_url('about') ?>">
                        <i class="fas fa-info-circle me-2"></i>
                        <?= lang('about') ?>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?= site_url('booking') ?>" target="_blank">
                        <i class="fas fa-external-link me-2"></i>
                        <?= lang('booking') ?>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?= site_url('logout') ?>">
                        <i class="fas fa-sign-out me-2"></i>
                        <?= lang('log_out') ?>
                    </a>
                </div>
            </li>
        </ul>
    </div>
</nav>

<div id="notification" style="display: none;"></div>

<div id="loading" class="position-fixed top-0 start-0 w-100 h-100" style="display: none; z-index: 999999; background: rgba(255, 255, 255, 0.75);">
    <div class="any-element animation is-loading d-block mx-auto">
        &nbsp;
    </div>
</div>
