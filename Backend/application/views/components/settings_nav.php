<h4 class="py-3 mb-3 fw-light">
    <?= lang('settings_center') ?>
</h4>

<ul id="settings-nav" class="nav flex-column">
    <li class="nav-item mb-2">
        <a class="nav-link px-0 py-2 fw-semibold text-primary" href="<?= site_url('settings') ?>">
            <i class="fas fa-sliders-h me-2"></i><?= lang('settings_center') ?>
        </a>
    </li>

    <li class="nav-item mb-2">
        <a class="nav-link px-0 py-2 text-dark" href="<?= site_url('industry_settings') ?>">
            <i class="fas fa-shapes me-2 text-warning"></i><?= lang('industry_and_modules') ?>
        </a>
    </li>

    <li class="nav-item mb-2">
        <a class="nav-link px-0 py-2 text-secondary" href="<?= site_url('settings#business') ?>">
            <i class="fas fa-building me-2"></i><?= lang('settings_section_business_title') ?>
        </a>
    </li>

    <li class="nav-item mb-2">
        <a class="nav-link px-0 py-2 text-secondary" href="<?= site_url('settings#booking') ?>">
            <i class="fas fa-calendar-check me-2"></i><?= lang('settings_section_booking_title') ?>
        </a>
    </li>

    <li class="nav-item mb-2">
        <a class="nav-link px-0 py-2 text-secondary" href="<?= site_url('settings#communication') ?>">
            <i class="fas fa-paper-plane me-2"></i><?= lang('settings_section_communication_title') ?>
        </a>
    </li>

    <li class="nav-item mb-2">
        <a class="nav-link px-0 py-2 text-secondary" href="<?= site_url('settings#integrations') ?>">
            <i class="fas fa-plug me-2"></i><?= lang('settings_section_integrations_title') ?>
        </a>
    </li>

    <li class="nav-item mb-2">
        <a class="nav-link px-0 py-2 text-secondary" href="<?= site_url('settings#legal') ?>">
            <i class="fas fa-balance-scale me-2"></i><?= lang('settings_section_legal_title') ?>
        </a>
    </li>

    <li class="nav-item mb-2">
        <a class="nav-link px-0 py-2 text-secondary" href="<?= site_url('settings#security') ?>">
            <i class="fas fa-shield-alt me-2"></i><?= lang('settings_section_security_title') ?>
        </a>
    </li>

    <?php if (session('role_slug') === DB_SLUG_ADMIN): ?>
        <li class="nav-item mb-2">
            <a class="nav-link px-0 py-2 text-secondary" href="<?= site_url('email_template_settings') ?>">
                <i class="fas fa-envelope-open-text me-2"></i><?= lang('email_templates') ?>
            </a>
        </li>

        <li class="nav-item mb-2">
            <a class="nav-link px-0 py-2 text-secondary" href="<?= site_url('channel_template_settings') ?>">
                <i class="fas fa-comments me-2"></i><?= lang('channel_templates') ?>
            </a>
        </li>
    <?php endif; ?>

    <li class="nav-item mb-2">
        <a class="nav-link px-0 py-2 text-secondary" href="<?= site_url('data_transfer') ?>">
            <i class="fas fa-file-export me-2"></i><?= lang('data_transfer') ?>
        </a>
    </li>
</ul>
