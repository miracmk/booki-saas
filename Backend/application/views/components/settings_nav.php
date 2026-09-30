<div class="settings-nav-sidebar pb-4">
    <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom">
        <h6 class="fw-bold mb-0 text-dark small text-uppercase tracking-wider">
            <i class="fas fa-sliders-h text-primary me-2"></i><?= lang('settings_center') ?>
        </h6>
    </div>

    <ul id="settings-nav" class="nav flex-column gap-1">
        <li class="nav-item">
            <a class="nav-link px-2.5 py-1.5 rounded-3 fw-semibold text-primary d-flex align-items-center gap-2 <?= uri_string() === 'settings' ? 'active bg-primary bg-opacity-10' : '' ?>" href="<?= site_url('settings') ?>" style="font-size: 0.84rem;">
                <i class="fas fa-sliders-h fa-fw text-primary"></i>
                <span><?= lang('settings_center') ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link px-2.5 py-1.5 rounded-3 text-secondary d-flex align-items-center gap-2" href="<?= site_url('industry_settings') ?>" style="font-size: 0.84rem;">
                <i class="fas fa-shapes fa-fw text-warning"></i>
                <span><?= lang('industry_and_modules') ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link px-2.5 py-1.5 rounded-3 text-secondary d-flex align-items-center gap-2" href="<?= site_url('randevuburada/profile') ?>" style="font-size: 0.84rem;">
                <i class="fas fa-store fa-fw text-warning"></i>
                <span>RandevuBurada Vitrin</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link px-2.5 py-1.5 rounded-3 text-secondary d-flex align-items-center gap-2" href="<?= site_url('settings#business') ?>" style="font-size: 0.84rem;">
                <i class="fas fa-building fa-fw"></i>
                <span><?= lang('settings_section_business_title') ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link px-2.5 py-1.5 rounded-3 text-secondary d-flex align-items-center gap-2" href="<?= site_url('settings#booking') ?>" style="font-size: 0.84rem;">
                <i class="fas fa-calendar-check fa-fw"></i>
                <span><?= lang('settings_section_booking_title') ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link px-2.5 py-1.5 rounded-3 text-secondary d-flex align-items-center gap-2" href="<?= site_url('settings#communication') ?>" style="font-size: 0.84rem;">
                <i class="fas fa-paper-plane fa-fw"></i>
                <span><?= lang('settings_section_communication_title') ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link px-2.5 py-1.5 rounded-3 text-secondary d-flex align-items-center gap-2" href="<?= site_url('follow_up') ?>" style="font-size: 0.84rem;">
                <i class="fas fa-route fa-fw text-success"></i>
                <span>Takip Motoru</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link px-2.5 py-1.5 rounded-3 text-secondary d-flex align-items-center gap-2" href="<?= site_url('settings#integrations') ?>" style="font-size: 0.84rem;">
                <i class="fas fa-plug fa-fw"></i>
                <span><?= lang('settings_section_integrations_title') ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link px-2.5 py-1.5 rounded-3 text-secondary d-flex align-items-center gap-2" href="<?= site_url('settings#legal') ?>" style="font-size: 0.84rem;">
                <i class="fas fa-balance-scale fa-fw"></i>
                <span><?= lang('settings_section_legal_title') ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link px-2.5 py-1.5 rounded-3 text-secondary d-flex align-items-center gap-2" href="<?= site_url('settings#security') ?>" style="font-size: 0.84rem;">
                <i class="fas fa-shield-alt fa-fw"></i>
                <span><?= lang('settings_section_security_title') ?></span>
            </a>
        </li>

        <?php if (session('role_slug') === DB_SLUG_ADMIN): ?>
            <li class="nav-item">
                <a class="nav-link px-2.5 py-1.5 rounded-3 text-secondary d-flex align-items-center gap-2" href="<?= site_url('email_template_settings') ?>" style="font-size: 0.84rem;">
                    <i class="fas fa-envelope-open-text fa-fw"></i>
                    <span><?= lang('email_templates') ?></span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link px-2.5 py-1.5 rounded-3 text-secondary d-flex align-items-center gap-2" href="<?= site_url('channel_template_settings') ?>" style="font-size: 0.84rem;">
                    <i class="fas fa-comments fa-fw"></i>
                    <span><?= lang('channel_templates') ?></span>
                </a>
            </li>
        <?php endif; ?>

        <li class="nav-item">
            <a class="nav-link px-2.5 py-1.5 rounded-3 text-secondary d-flex align-items-center gap-2" href="<?= site_url('data_transfer') ?>" style="font-size: 0.84rem;">
                <i class="fas fa-file-export fa-fw"></i>
                <span><?= lang('data_transfer') ?></span>
            </a>
        </li>
    </ul>
</div>
