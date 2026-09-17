<h4 class=" py-3 mb-3 fw-light">
    <?= lang('settings') ?>
</h4>

<ul id="settings-nav" class="nav flex-column">
    <li class="nav-item mb-3">
        <a class="nav-link px-0 py-2" href="<?= site_url('general_settings') ?>">
            <?= lang('general_settings') ?>
        </a>
    </li>

    <li class="nav-item mb-3">
        <a class="nav-link px-0 py-2" href="<?= site_url('booking_settings') ?>">
            <?= lang('booking_settings') ?>
        </a>
    </li>

    <li class="nav-item mb-3">
        <a class="nav-link px-0 py-2" href="<?= site_url('business_settings') ?>">
            <?= lang('business_logic') ?>
        </a>
    </li>

    <li class="nav-item mb-3">
        <a class="nav-link px-0 py-2" href="<?= site_url('legal_settings') ?>">
            <?= lang('legal_contents') ?>
        </a>
    </li>

    <li class="nav-item mb-3">
        <a class="nav-link px-0 py-2" href="<?= site_url('messaging_settings') ?>">
            Bildirim Ayarları
        </a>
    </li>

    <?php // Salon Flora customization - "Şablonlar" (email templates) is an admin-only owner tool. ?>
    <?php if (session('role_slug') === DB_SLUG_ADMIN): ?>
        <li class="nav-item mb-3">
            <a class="nav-link px-0 py-2" href="<?= site_url('email_template_settings') ?>">
                <?= lang('email_templates') ?>
            </a>
        </li>
    <?php endif; ?>

    <li class="nav-item mb-3">
        <a class="nav-link px-0 py-2" href="<?= site_url('integrations') ?>">
            <?= lang('integrations') ?>
        </a>
    </li>

    <?php // BooKi (2026-08-26) - içe/dışa aktarma sihirbazı. ?>
    <li class="nav-item mb-3">
        <a class="nav-link px-0 py-2" href="<?= site_url('data_transfer') ?>">
            Veriler
        </a>
    </li>
</ul>
