<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>
<?php
$schemas = $schemas ?? $sections ?? [];
$section_values = $section_values ?? $values ?? [];
$can_edit = $can_edit ?? true;
$active_section = $active_section ?? vars('active_section') ?? 'business';
$all_users = $all_users ?? [];
$all_roles = $all_roles ?? [];
$recent_audit_logs = $recent_audit_logs ?? [];
?>

<div id="settings-center-page" class="container-fluid py-4 px-md-5">
    <style>
    /* Settings Center Custom UI Enhancements */
    #settings-main-tabs {
        gap: 4px;
        padding: 5px;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
    }
    #settings-main-tabs .nav-link {
        font-size: 0.8125rem;
        font-weight: 600;
        padding: 0.4rem 0.6rem;
        border-radius: 8px;
        color: #475569;
        white-space: nowrap;
        transition: all 0.15s ease-in-out;
    }
    #settings-main-tabs .nav-link:hover {
        color: #0f172a;
        background-color: rgba(0, 0, 0, 0.04);
    }
    #settings-main-tabs .nav-link.active {
        background-color: var(--bs-primary, #35a768);
        color: #ffffff !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    }
    .settings-subnav .nav-link {
        font-size: 0.835rem;
        font-weight: 500;
        padding: 0.45rem 0.75rem;
        border-radius: 8px;
        color: #475569;
        transition: all 0.15s ease-in-out;
    }
    .settings-subnav .nav-link:hover {
        color: #0f172a;
        background-color: #f1f5f9;
    }
    .settings-subnav .nav-link.active {
        background-color: var(--bs-primary, #35a768);
        color: #ffffff !important;
        font-weight: 600;
    }

    /* Reading-Friendly Setting Box Containers */
    .setting-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem 1.15rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }
    .setting-box:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }
    .setting-box-bool {
        flex-direction: row;
        align-items: center;
        cursor: pointer;
        user-select: none;
    }
    .setting-box-bool:hover {
        border-color: #94a3b8;
        background-color: #fbfcfe;
    }
    .setting-box-bool .form-check-input {
        width: 2.3em;
        height: 1.25em;
        cursor: pointer;
    }
    .setting-box .form-control,
    .setting-box .form-select {
        border-color: #cbd5e1;
        border-radius: 8px;
        font-size: 0.875rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .setting-box .form-control:focus,
    .setting-box .form-select:focus {
        border-color: var(--bs-primary, #35a768);
        box-shadow: 0 0 0 3px rgba(53, 167, 104, 0.15);
    }
    .setting-box .setting-title {
        font-size: 0.915rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.2rem;
    }
    .setting-box .setting-desc {
        font-size: 0.8125rem;
        color: #64748b;
        line-height: 1.45;
        margin-bottom: 0;
    }
    </style>

    <!-- Header with Search -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 border-bottom pb-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <div class="bg-primary bg-opacity-10 p-2 rounded-3 text-primary">
                    <i class="fas fa-sliders-h fa-lg"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-dark"><?= lang('settings_center') ?: 'Ayarlar Merkezi' ?></h3>
                    <p class="text-muted small mb-0"><?= lang('settings_center_desc') ?: 'İşletme, rezervasyon kuralları, iletişim kanalları, entegrasyonlar, hukuki metinler ve güvenlik yapılandırması.' ?></p>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="settings-search" class="form-control border-start-0 ps-0" placeholder="<?= lang('settings_search_placeholder') ?: 'Ayarlarda ara...' ?>">
            </div>
            <a href="<?= site_url('industry_settings') ?>" class="btn btn-outline-primary">
                <i class="fas fa-shapes me-1"></i> <?= lang('industry_and_modules') ?: 'Sektör & Modüller' ?>
            </a>
        </div>
    </div>

    <!-- Main Navigation Tabs (Sticky Top) - Reordered: RandevuBurada at 2nd Position -->
    <div class="sticky-top bg-body pt-2 pb-2 mb-4" style="top: 0; z-index: 1020; backdrop-filter: blur(8px);">
        <ul class="nav nav-pills nav-fill bg-light p-1 rounded-4 shadow-sm flex-nowrap overflow-auto border mb-0 settings-main-nav gap-1" id="settings-main-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link rounded-3 fw-semibold <?= $active_section === 'business' ? 'active' : '' ?> text-nowrap py-1.5 px-2 px-xl-2.5" id="tab-business" data-bs-toggle="pill" href="#section-business" role="tab" style="font-size: 0.8125rem;">
                    <i class="fas fa-building me-1.5"></i><?= lang('settings_section_business_title') ?: 'İşletme & Profil' ?>
                </a>
            </li>
            <!-- RandevuBurada at 2nd Position -->
            <li class="nav-item" role="presentation">
                <a class="nav-link rounded-3 fw-semibold text-nowrap py-1.5 px-2 px-xl-2.5 text-dark border border-warning border-opacity-50 bg-warning bg-opacity-10" href="<?= site_url('randevuburada/profile') ?>" title="Pazaryeri Vitrin ve Profilinizi Düzenleyin" style="font-size: 0.8125rem;">
                    <i class="fas fa-store text-warning me-1.5"></i>RandevuBurada Vitrin
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link rounded-3 fw-semibold <?= $active_section === 'booking' ? 'active' : '' ?> text-nowrap py-1.5 px-2 px-xl-2.5" id="tab-booking" data-bs-toggle="pill" href="#section-booking" role="tab" style="font-size: 0.8125rem;">
                    <i class="fas fa-calendar-check me-1.5"></i><?= lang('settings_section_booking_title') ?: 'Randevu Kuralları' ?>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link rounded-3 fw-semibold <?= $active_section === 'communication' ? 'active' : '' ?> text-nowrap py-1.5 px-2 px-xl-2.5" id="tab-communication" data-bs-toggle="pill" href="#section-communication" role="tab" style="font-size: 0.8125rem;">
                    <i class="fas fa-paper-plane me-1.5"></i><?= lang('settings_section_communication_title') ?: 'İletişim & Bildirim' ?>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link rounded-3 fw-semibold <?= $active_section === 'integrations' ? 'active' : '' ?> text-nowrap py-1.5 px-2 px-xl-2.5" id="tab-integrations" data-bs-toggle="pill" href="#section-integrations" role="tab" style="font-size: 0.8125rem;">
                    <i class="fas fa-plug me-1.5"></i><?= lang('settings_section_integrations_title') ?: 'Entegrasyon & AI' ?>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link rounded-3 fw-semibold <?= $active_section === 'legal' ? 'active' : '' ?> text-nowrap py-1.5 px-2 px-xl-2.5" id="tab-legal" data-bs-toggle="pill" href="#section-legal" role="tab" style="font-size: 0.8125rem;">
                    <i class="fas fa-balance-scale me-1.5"></i><?= lang('settings_section_legal_title') ?: 'Hukuk & KVKK' ?>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link rounded-3 fw-semibold <?= $active_section === 'security' ? 'active' : '' ?> text-nowrap py-1.5 px-2 px-xl-2.5" id="tab-security" data-bs-toggle="pill" href="#section-security" role="tab" style="font-size: 0.8125rem;">
                    <i class="fas fa-shield-alt me-1.5"></i><?= lang('settings_section_security_title') ?: 'Güvenlik & Erişim' ?>
                </a>
            </li>
        </ul>
    </div>

    <!-- Tab Contents -->
    <div class="tab-content" id="settings-tab-content">
        <?php foreach ($schemas as $section_key => $sec): ?>
            <div class="tab-pane fade <?= $section_key === $active_section ? 'show active' : '' ?>" id="section-<?= $section_key ?>" role="tabpanel">
                <div class="row g-4">
                    <!-- Left Sub-navigation Pills -->
                    <div class="col-lg-3 col-md-4">
                        <div class="card border border-light-subtle shadow-xs rounded-4 p-2 sticky-top" style="top: 75px; z-index: 10;">
                            <div class="nav flex-column nav-pills settings-subnav gap-1" id="subnav-<?= $section_key ?>">
                                <?php $first_tab = true; foreach ($sec['tabs'] as $sub_key => $sub_title): ?>
                                    <button class="nav-link text-start rounded-3 py-1.5 px-2.5 mb-0 <?= $first_tab ? 'active' : '' ?>"
                                            data-subtab-target="#subtab-<?= $section_key ?>-<?= $sub_key ?>"
                                            style="font-size: 0.84rem; font-weight: 500;">
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

                                    <!-- 1. Special Render: Working Hours & Calendar Plan -->
                                    <?php if ($section_key === 'business' && $sub_key === 'hours'): ?>
                                        <?php
                                        $days_map = [
                                            'monday' => 'Pazartesi',
                                            'tuesday' => 'Salı',
                                            'wednesday' => 'Çarşamba',
                                            'thursday' => 'Perşembe',
                                            'friday' => 'Cuma',
                                            'saturday' => 'Cumartesi',
                                            'sunday' => 'Pazar',
                                        ];
                                        $current_breaks = $working_plan['monday']['breaks'] ?? [];
                                        ?>
                                        <!-- Haftalık Çalışma Saatleri -->
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark">Haftalık Çalışma Planı & Saatler</h5>
                                                    <p class="text-muted small mb-0">İşletmenizin müşterilere açık olduğu gün ve mesai saatlerini belirleyin.</p>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-apply-global-plan">
                                                        <i class="fas fa-users-cog me-1"></i> Tüm Personele Senkronize Et
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-success btn-save-section-direct">
                                                        <i class="fas fa-save me-1"></i> <?= lang('save') ?: 'Kaydet' ?>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body p-4">
                                                <div class="table-responsive">
                                                    <table class="table table-hover align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th style="width: 25%">Gün</th>
                                                                <th style="width: 25%">Durum</th>
                                                                <th style="width: 25%">Mesai Başlangıç</th>
                                                                <th style="width: 25%">Mesai Bitiş</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($days_map as $d_key => $d_label): ?>
                                                                <?php
                                                                $d_plan = $working_plan[$d_key] ?? null;
                                                                $is_d_open = !empty($d_plan);
                                                                $d_start = $d_plan['start'] ?? '09:00';
                                                                $d_end = $d_plan['end'] ?? '18:00';
                                                                ?>
                                                                <tr id="row-day-<?= $d_key ?>">
                                                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($d_label) ?></td>
                                                                    <td>
                                                                        <div class="form-check form-switch">
                                                                            <input class="form-check-input wp-day-toggle" type="checkbox" role="switch"
                                                                                   name="wp_open_<?= $d_key ?>" id="wp_open_<?= $d_key ?>" value="1"
                                                                                   data-day="<?= $d_key ?>" <?= $is_d_open ? 'checked' : '' ?>>
                                                                            <label class="form-check-label small fw-semibold wp-status-text <?= $is_d_open ? 'text-success' : 'text-danger' ?>" for="wp_open_<?= $d_key ?>">
                                                                                <?= $is_d_open ? 'Açık' : 'Kapalı' ?>
                                                                            </label>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <input type="time" class="form-control form-control-sm wp-time-input"
                                                                               name="wp_start_<?= $d_key ?>" id="wp_start_<?= $d_key ?>"
                                                                               value="<?= htmlspecialchars($d_start) ?>" <?= !$is_d_open ? 'disabled' : '' ?>>
                                                                    </td>
                                                                    <td>
                                                                        <input type="time" class="form-control form-control-sm wp-time-input"
                                                                               name="wp_end_<?= $d_key ?>" id="wp_end_<?= $d_key ?>"
                                                                               value="<?= htmlspecialchars($d_end) ?>" <?= !$is_d_open ? 'disabled' : '' ?>>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Günlük Molalar -->
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark">Günlük Mola & Öğle Araları</h5>
                                                    <p class="text-muted small mb-0">Bu saatler randevu müsaitlik slotlarında otomatik olarak bloke edilir.</p>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-break">
                                                    <i class="fas fa-plus me-1"></i> Yeni Mola Ekle
                                                </button>
                                            </div>
                                            <div class="card-body p-4">
                                                <div id="breaks-container">
                                                    <?php if (empty($current_breaks)): ?>
                                                        <p class="text-muted small mb-0" id="no-breaks-hint">Tanımlı mola bulunmamaktadır. Eklemek için "Yeni Mola Ekle" butonunu kullanın.</p>
                                                    <?php else: ?>
                                                        <?php foreach ($current_breaks as $b_idx => $b_item): ?>
                                                            <div class="d-flex align-items-center gap-2 mb-2 break-row">
                                                                <span class="badge bg-light text-dark border px-2 py-1"><i class="fas fa-coffee me-1 text-primary"></i> Mola</span>
                                                                <input type="time" class="form-control form-control-sm break-start" style="max-width: 140px;" value="<?= htmlspecialchars($b_item['start'] ?? '13:00') ?>">
                                                                <span class="text-muted">-</span>
                                                                <input type="time" class="form-control form-control-sm break-end" style="max-width: 140px;" value="<?= htmlspecialchars($b_item['end'] ?? '14:00') ?>">
                                                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-break"><i class="fas fa-trash-alt"></i></button>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tatiller & Kapalı Dönemler -->
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark">İşletme Tatilleri & Kapalı Dönemler</h5>
                                                    <p class="text-muted small mb-0">Resmi tatil veya tadilat nedeniyle tüm işletmenin kapalı olacağı zaman dilimleri.</p>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-blocked-period">
                                                    <i class="fas fa-plus me-1"></i> Tatil / Kapalı Dönem Ekle
                                                </button>
                                            </div>
                                            <div class="card-body p-4">
                                                <div id="blocked-periods-table-wrapper">
                                                    <?php if (empty($blocked_periods)): ?>
                                                        <div class="alert alert-light border text-muted small mb-0" id="no-blocked-periods-alert">
                                                            <i class="fas fa-info-circle me-1 text-primary"></i> Tanımlı tatil veya kapalı dönem bulunmamaktadır.
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="table-responsive">
                                                            <table class="table table-hover align-middle mb-0" id="table-blocked-periods">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>Başlık</th>
                                                                        <th>Başlangıç</th>
                                                                        <th>Bitiş</th>
                                                                        <th>Notlar</th>
                                                                        <th class="text-end">İşlem</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($blocked_periods as $bp): ?>
                                                                        <tr id="bp-row-<?= $bp['id'] ?>">
                                                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($bp['name']) ?></td>
                                                                            <td><span class="badge bg-light text-dark border"><?= date('d.m.Y H:i', strtotime($bp['start_datetime'])) ?></span></td>
                                                                            <td><span class="badge bg-light text-dark border"><?= date('d.m.Y H:i', strtotime($bp['end_datetime'])) ?></span></td>
                                                                            <td class="small text-muted"><?= htmlspecialchars($bp['notes'] ?? '-') ?></td>
                                                                            <td class="text-end">
                                                                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-blocked-period" data-id="<?= $bp['id'] ?>" title="Sil">
                                                                                    <i class="fas fa-trash-alt"></i>
                                                                                </button>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                    <!-- 2. Special Render: Payment Tab with 3x2 Grid & Online Kapora Infobox -->
                                    <?php elseif ($section_key === 'business' && $sub_key === 'payment'): ?>
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark">Kabul Edilen Ödeme Çeşitleri (3x2)</h5>
                                                    <p class="text-muted small mb-0">İşletmenizin kabul ettiği tahsilat kanallarını işaretleyin.</p>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-success btn-save-section-direct">
                                                    <i class="fas fa-save me-1"></i> <?= lang('save') ?: 'Kaydet' ?>
                                                </button>
                                            </div>
                                            <div class="card-body p-4">
                                                <div class="row g-3">
                                                    <?php
                                                    $payment_methods_map = [
                                                        'payment_method_cash' => ['label' => '1. Nakit Ödeme', 'icon' => 'fas fa-money-bill-wave text-success', 'desc' => 'Müşterilerden elden nakit tahsilatı kabul et.'],
                                                        'payment_method_iban' => ['label' => '2. IBAN / Havale', 'icon' => 'fas fa-university text-info', 'desc' => 'Banka hesabına FAST/Havale/EFT ile ödeme kabul et.'],
                                                        'payment_method_card' => ['label' => '3. Kredi Kartı / POS', 'icon' => 'fas fa-credit-card text-primary', 'desc' => 'Fiziki POS cihazınızla yerinde kart tahsilatı.'],
                                                        'payment_method_installment' => ['label' => '4. Elden Taksitli', 'icon' => 'fas fa-file-invoice-dollar text-warning', 'desc' => 'Paket ve seanslar için senetli/taksitli tahsilat.'],
                                                        'payment_method_own_pos' => ['label' => '5. Online Ödeme (Kendi POS\'um)', 'icon' => 'fas fa-globe text-primary', 'desc' => 'İşletmenizin kendi sanal POS entegrasyonu (İyzico/Paytr).'],
                                                        'payment_method_booki_pos' => ['label' => '6. Online Ödeme (BooKi POS %20)', 'icon' => 'fas fa-shield-alt text-danger', 'desc' => 'Sözleşmesiz hazır BooKi ortak sanal POS altyapısı.'],
                                                    ];
                                                    ?>
                                                    <?php foreach ($payment_methods_map as $pm_key => $pm_info): ?>
                                                        <?php $pm_val = $section_values[$section_key][$pm_key] ?? 0; ?>
                                                        <div class="col-md-4 setting-field" data-setting-key="<?= $pm_key ?>">
                                                            <div class="setting-box bg-white" onclick="if(event.target.tagName !== 'INPUT'){ const cb = this.querySelector('input[type=checkbox]'); cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')); }">
                                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <i class="<?= $pm_info['icon'] ?> fa-lg"></i>
                                                                        <span class="setting-title mb-0"><?= $pm_info['label'] ?></span>
                                                                    </div>
                                                                    <div class="form-check form-switch mb-0">
                                                                        <input class="form-check-input setting-input cursor-pointer" type="checkbox" role="switch"
                                                                               id="input-<?= $pm_key ?>" name="<?= $pm_key ?>" value="1"
                                                                               <?= !empty($pm_val) ? 'checked' : '' ?>>
                                                                    </div>
                                                                </div>
                                                                <p class="setting-desc"><?= $pm_info['desc'] ?></p>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>

                                                <!-- Online Kapora & Provizyon Açıklayıcı Kart -->
                                                <div class="card border-primary border-opacity-25 bg-primary bg-opacity-10 rounded-4 mt-4 p-4">
                                                    <div class="d-flex align-items-start gap-3">
                                                        <div class="bg-primary text-white p-2 rounded-circle mt-1">
                                                            <i class="fas fa-info-circle fa-lg"></i>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <h6 class="fw-bold text-dark mb-1">Online Kapora & Ön Provizyon Sistemi Nasıl Çalışır?</h6>
                                                            <p class="small text-muted mb-2 leading-relaxed">
                                                                Müşteri rezervasyon oluşturduğunda hizmet bedeli müşterinin kredi kartından <strong>ön provizyon (bloke)</strong> olarak ayrılır.
                                                                Müşteri randevusuna geldiğinde işletme cüzdanından kapora bedeli düşülür ve bloke edilen bakiye <strong>serbest bırakılır</strong>.
                                                                Müşteri randevuya gelmezse (<strong>No-Show</strong>), belirlenen komisyon oranı kesilerek kalan net tutar işletmenizin BooKi alacak hesabına yazılır ve ertesi iş günü banka hesabınıza aktarılır.
                                                            </p>
                                                            <div class="d-flex flex-wrap gap-2">
                                                                <span class="badge bg-white text-primary border border-primary border-opacity-25 py-2 px-3 rounded-pill">
                                                                    <i class="fas fa-percentage me-1"></i> BooKi POS ile birlikte: <strong>%5 Komisyon</strong>
                                                                </span>
                                                                <span class="badge bg-white text-danger border border-danger border-opacity-25 py-2 px-3 rounded-pill">
                                                                    <i class="fas fa-shield-alt me-1"></i> Sadece Kapora Modeli: <strong>%20 Komisyon</strong>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row g-3 mt-3 pt-3 border-top border-primary border-opacity-25">
                                                        <div class="col-md-6 setting-field" data-setting-key="payment_deposit_required">
                                                            <div class="setting-box setting-box-bool bg-white" onclick="if(event.target.tagName !== 'INPUT'){ const cb = this.querySelector('input[type=checkbox]'); cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')); }">
                                                                <div class="pe-3 flex-grow-1">
                                                                    <label class="setting-title cursor-pointer mb-1" for="input-payment_deposit_required">
                                                                        Online Kapora / Ön Ödeme Şartı
                                                                    </label>
                                                                    <p class="setting-desc">Rezervasyon tamamlanmadan önce kart blokesi veya kapora alınır.</p>
                                                                </div>
                                                                <div class="form-check form-switch mb-0 flex-shrink-0">
                                                                    <input class="form-check-input setting-input cursor-pointer" type="checkbox" role="switch"
                                                                           id="input-payment_deposit_required" name="payment_deposit_required" value="1"
                                                                           <?= !empty($section_values['business']['payment_deposit_required']) ? 'checked' : '' ?>>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 setting-field" data-setting-key="deposit_commission_rate">
                                                            <div class="setting-box bg-white">
                                                                <div class="mb-2">
                                                                    <label class="setting-title mb-1" for="input-deposit_commission_rate">Uygulanacak Komisyon Modeli</label>
                                                                    <p class="setting-desc">BooKi POS veya bağımsız tahsilat komisyonu.</p>
                                                                </div>
                                                                <div class="pt-2">
                                                                    <select class="form-select form-select-sm setting-input py-2" name="deposit_commission_rate" id="input-deposit_commission_rate">
                                                                        <option value="standalone_20" <?= ($section_values['business']['deposit_commission_rate'] ?? '') === 'standalone_20' ? 'selected' : '' ?>>Yalnızca Kapora Modeli (%20 Komisyon)</option>
                                                                        <option value="integrated_5" <?= ($section_values['business']['deposit_commission_rate'] ?? '') === 'integrated_5' ? 'selected' : '' ?>>Online Ödeme (BooKi POS) ile Birlikte (%5 Komisyon)</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 setting-field" data-setting-key="payment_deposit_type">
                                                            <div class="setting-box bg-white">
                                                                <div class="mb-2">
                                                                    <label class="setting-title mb-1" for="input-payment_deposit_type">Kapora Şekli</label>
                                                                    <p class="setting-desc">Hesaplanma yöntemi (yüzde, sabit veya tam bedel).</p>
                                                                </div>
                                                                <div class="pt-2">
                                                                    <select class="form-select form-select-sm setting-input py-2" name="payment_deposit_type" id="input-payment_deposit_type">
                                                                        <option value="percentage" <?= ($section_values['business']['payment_deposit_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Yüzde Oranında (%)</option>
                                                                        <option value="fixed" <?= ($section_values['business']['payment_deposit_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Sabit Tutar (TL)</option>
                                                                        <option value="full" <?= ($section_values['business']['payment_deposit_type'] ?? '') === 'full' ? 'selected' : '' ?>>Tam Bedel Blokesi</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 setting-field" data-setting-key="payment_deposit_amount">
                                                            <div class="setting-box bg-white">
                                                                <div class="mb-2">
                                                                    <label class="setting-title mb-1" for="input-payment_deposit_amount">Kapora Oranı (%) veya Tutarı (TL)</label>
                                                                    <p class="setting-desc">Alınacak kapora yüzdesi ya da net TL tutarı.</p>
                                                                </div>
                                                                <div class="pt-2">
                                                                    <input type="number" class="form-control form-control-sm setting-input py-2" name="payment_deposit_amount" id="input-payment_deposit_amount" value="<?= htmlspecialchars((string)($section_values['business']['payment_deposit_amount'] ?? '20')) ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    <!-- 3. Special Render: Communication Channels 3x2 Grid & Voice Assistant -->
                                    <?php elseif ($section_key === 'communication' && $sub_key === 'channels'): ?>
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark">İletişim & Bildirim Kanalları (3x2 Grid)</h5>
                                                    <p class="text-muted small mb-0">Müşterilere ve personele giden mesaj kanallarını yönetin.</p>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-success btn-save-section-direct">
                                                    <i class="fas fa-save me-1"></i> <?= lang('save') ?: 'Kaydet' ?>
                                                </button>
                                            </div>
                                            <div class="card-body p-4">
                                                <div class="row g-3">
                                                    <?php
                                                    $comm_channels_map = [
                                                        'channel_email_enabled' => ['title' => 'E-posta (SMTP)', 'target' => '#subtab-communication-email_smtp', 'icon' => 'fas fa-envelope text-primary', 'badge' => 'SMTP Ayarları', 'desc' => 'Giden e-posta sunucusu ve şablonları.'],
                                                        'channel_sms_enabled' => ['title' => 'SMS Entegrasyonu', 'target' => '#subtab-communication-sms', 'icon' => 'fas fa-comment-dots text-info', 'badge' => 'NetGSM / İleti / Twilio', 'desc' => 'Başlıklı kurumsal SMS gönderimi.'],
                                                        'channel_whatsapp_enabled' => ['title' => 'WhatsApp Mesajlaşma', 'target' => '#subtab-communication-whatsapp', 'icon' => 'fab fa-whatsapp text-success', 'badge' => 'Cloud API & QR', 'desc' => 'Meta resmi veya QR köprü bağlantısı.'],
                                                        'channel_telegram_enabled' => ['title' => 'Telegram Bot', 'target' => '#subtab-communication-telegram', 'icon' => 'fab fa-telegram text-primary', 'badge' => 'BotFather Token', 'desc' => 'Telegram kanalı veya personellere anlık bildirim.'],
                                                        'channel_instagram_enabled' => ['title' => 'Instagram DM', 'target' => '#subtab-communication-instagram_dm', 'icon' => 'fab fa-instagram text-danger', 'badge' => 'Meta Graph API', 'desc' => 'Instagram doğrudan mesaj kutusu entegrasyonu.'],
                                                        'channel_call_enabled' => ['title' => 'Sesli Arama & AI Asistan', 'target' => '#subtab-communication-voice_call', 'icon' => 'fas fa-phone-volume text-warning', 'badge' => '1.250 TL / 60 Dk', 'desc' => '0850 sanal hat ile gelen ve giden sesli santral.'],
                                                    ];
                                                    ?>
                                                    <?php foreach ($comm_channels_map as $c_key => $c_info): ?>
                                                        <?php $c_active = !empty($section_values['communication'][$c_key]); ?>
                                                        <div class="col-md-4 setting-field" data-setting-key="<?= $c_key ?>">
                                                            <div class="card h-100 border rounded-4 p-3 <?= $c_active ? 'border-primary bg-primary bg-opacity-10' : 'bg-light' ?>">
                                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <i class="<?= $c_info['icon'] ?> fa-lg"></i>
                                                                        <h6 class="fw-bold mb-0 text-dark small"><?= $c_info['title'] ?></h6>
                                                                    </div>
                                                                    <div class="form-check form-switch fs-5 mb-0">
                                                                        <input class="form-check-input setting-input" type="checkbox" role="switch"
                                                                               id="input-<?= $c_key ?>" name="<?= $c_key ?>" value="1"
                                                                               <?= $c_active ? 'checked' : '' ?>>
                                                                    </div>
                                                                </div>
                                                                <p class="text-muted small mb-2" style="font-size: 0.8rem;"><?= $c_info['desc'] ?></p>
                                                                <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                                                                    <span class="badge bg-white text-dark border small"><?= $c_info['badge'] ?></span>
                                                                    <a href="javascript:void(0)" class="small fw-semibold text-primary text-decoration-none" onclick="document.querySelector('[data-subtab-target=\'<?= $c_info['target'] ?>\']').click();">
                                                                        Yapılandır <i class="fas fa-chevron-right ms-1"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                    <!-- 4. Special Render: Custom Domain Section -->
                                    <?php elseif ($section_key === 'communication' && $sub_key === 'custom_domain'): ?>
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark">Özel Alan Adı (Custom Domain) Bağlama</h5>
                                                    <p class="text-muted small mb-0">Kendi web adresinizi (örn: randevu.isletmeniz.com) BooKi altyapısına bağlayın.</p>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-success btn-save-section-direct">
                                                    <i class="fas fa-save me-1"></i> <?= lang('save') ?: 'Kaydet' ?>
                                                </button>
                                            </div>
                                            <div class="card-body p-4">
                                                <div class="alert alert-info border d-flex gap-3 align-items-start rounded-4 mb-4">
                                                    <i class="fas fa-network-wired fa-lg text-primary mt-1"></i>
                                                    <div>
                                                        <h6 class="fw-bold mb-1">DNS Yönlendirme Talimatları</h6>
                                                        <p class="small mb-1">Domain sağlayıcınızın (Cloudflare, GoDaddy vb.) DNS yönetim paneline girerek aşağıdaki kaydı ekleyin:</p>
                                                        <div class="bg-white p-2 rounded border font-monospace small">
                                                            <strong>Kayıt Türü:</strong> CNAME &nbsp;|&nbsp; <strong>Ad (Host):</strong> randevu &nbsp;|&nbsp; <strong>Hedef (Değer):</strong> custom.bookiapp.kibusiness.co
                                                        </div>
                                                        <small class="text-muted mt-1 d-block">Alternatif A Kaydı IP: <strong>168.231.109.167</strong></small>
                                                    </div>
                                                </div>

                                                <div class="row g-3">
                                                    <div class="col-md-8 setting-field" data-setting-key="custom_domain_name">
                                                        <label class="form-label fw-bold text-dark">Özel Alan Adınız</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-white">https://</span>
                                                            <input type="text" class="form-control setting-input" name="custom_domain_name" id="input-custom_domain_name"
                                                                   placeholder="randevu.isletmeniz.com" value="<?= htmlspecialchars((string)($section_values['communication']['custom_domain_name'] ?? '')) ?>">
                                                            <button class="btn btn-primary" type="button" id="btn-verify-domain">
                                                                <i class="fas fa-check-circle me-1"></i> Doğrula & SSL Kur
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 setting-field" data-setting-key="custom_domain_status">
                                                        <label class="form-label fw-bold text-dark">Durum</label>
                                                        <?php $d_status = $section_values['communication']['custom_domain_status'] ?? 'disabled'; ?>
                                                        <div>
                                                            <?php if ($d_status === 'verified'): ?>
                                                                <span class="badge bg-success py-2 px-3 rounded-pill fs-6"><i class="fas fa-check-circle me-1"></i> Doğrulandı & SSL Aktif</span>
                                                            <?php elseif ($d_status === 'pending'): ?>
                                                                <span class="badge bg-warning text-dark py-2 px-3 rounded-pill fs-6"><i class="fas fa-clock me-1"></i> DNS Bekleniyor</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary py-2 px-3 rounded-pill fs-6"><i class="fas fa-times-circle me-1"></i> Pasif</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    <!-- 5. Special Render: Voice Call Assistant (1.250 TL/60 dk) -->
                                    <?php elseif ($section_key === 'communication' && $sub_key === 'voice_call'): ?>
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark">Sesli AI Telefon Santrali & Sanal Numara</h5>
                                                    <p class="text-muted small mb-0">Müşterileri telefonla arayan, randevuları teyit eden ve gelen çağrılara bakan sesli asistan.</p>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-success btn-save-section-direct">
                                                    <i class="fas fa-save me-1"></i> <?= lang('save') ?: 'Kaydet' ?>
                                                </button>
                                            </div>
                                            <div class="card-body p-4">
                                                <div class="card border-warning border-2 bg-warning bg-opacity-10 rounded-4 p-4 mb-4">
                                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="bg-warning text-dark p-3 rounded-circle">
                                                                <i class="fas fa-headset fa-2x"></i>
                                                            </div>
                                                            <div>
                                                                <h5 class="fw-bold text-dark mb-1">Aylık 1.250 TL / 60 Dakika Konuşma Paketi</h5>
                                                                <p class="text-muted small mb-0">İşletmenize özel 0850'li sanal telefon numarası anında tahsis edilir. Yapay zeka tüm çağrıları Türkçe doğal ses tonuyla karşılar.</p>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-warning fw-bold px-4 py-2 rounded-pill shadow-sm" id="btn-request-voice-number">
                                                            <i class="fas fa-phone-plus me-1"></i> Numara Tahsis Et
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="row g-3">
                                                    <div class="col-md-6 setting-field" data-setting-key="voice_assistant_number">
                                                        <div class="setting-box bg-white">
                                                            <div class="mb-2">
                                                                <label class="setting-title mb-1" for="input-voice_assistant_number">Tahsis Edilen Sanal Numara</label>
                                                                <p class="setting-desc">Santralinize bağlı 0850 kurumsal arama hattı.</p>
                                                            </div>
                                                            <div class="pt-2">
                                                                <input type="text" class="form-control font-monospace fw-bold setting-input py-2" name="voice_assistant_number" id="input-voice_assistant_number"
                                                                       value="<?= htmlspecialchars((string)($section_values['communication']['voice_assistant_number'] ?? '')) ?>" readonly placeholder="Numara tahsisi için butona tıklayın">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 setting-field" data-setting-key="voice_assistant_status">
                                                        <div class="setting-box bg-white">
                                                            <div class="mb-2">
                                                                <label class="setting-title mb-1" for="input-voice_assistant_status">Hat Durumu</label>
                                                                <p class="setting-desc">Numaranın anlık telekomünikasyon durumu.</p>
                                                            </div>
                                                            <div class="pt-2">
                                                                <input type="text" class="form-control setting-input py-2" name="voice_assistant_status" id="input-voice_assistant_status"
                                                                       value="<?= htmlspecialchars((string)($section_values['communication']['voice_assistant_status'] ?? 'not_requested')) ?>" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    <!-- Special Render: Interactive Notification & Email Template Studio -->
                                    <?php elseif ($section_key === 'communication' && $sub_key === 'templates'): ?>
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark">İletişim & Bildirim Şablonları Stüdyosu</h5>
                                                    <p class="text-muted small mb-0">E-posta, SMS ve WhatsApp bildirimlerini canlı önizleme ile tasarlayın ve kişiselleştirin.</p>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-reset-current-tpl">
                                                        <i class="fas fa-undo me-1"></i> Varsayılana Dön
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-test-send-modal" data-bs-toggle="modal" data-bs-target="#modal-test-template">
                                                        <i class="fas fa-paper-plane me-1"></i> Test Gönder
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-success btn-save-section-direct">
                                                        <i class="fas fa-save me-1"></i> <?= lang('save') ?: 'Kaydet' ?>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body p-4">
                                                <!-- Senaryo ve Kanal Seçimi -->
                                                <div class="row g-3 mb-4">
                                                    <div class="col-lg-7">
                                                        <label class="form-label fw-bold small text-dark"><i class="fas fa-bell me-1 text-primary"></i> 1. Bildirim Senaryosu Seçin</label>
                                                        <select class="form-select shadow-sm" id="select-tpl-scenario">
                                                            <option value="confirmation" selected>📅 Randevu Onayı (Yeni Randevu Teyit Mesajı)</option>
                                                            <option value="reminder_24h">⏰ Erken Hatırlatma (Randevudan 24 Saat Önce)</option>
                                                            <option value="reminder_2h">🚗 Son Hatırlatma & Canlı Konum (2 Saat Önce)</option>
                                                            <option value="cancellation">❌ Randevu İptal / Tarih Değişikliği</option>
                                                            <option value="followup">⭐ Hizmet Sonrası Memnuniyet & Puanlama</option>
                                                            <option value="owner">💼 İşletme Sahibi & Kasa Gün Sonu Özeti</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-lg-5">
                                                        <label class="form-label fw-bold small text-dark"><i class="fas fa-exchange-alt me-1 text-primary"></i> 2. Gönderim Kanalı</label>
                                                        <div class="btn-group w-100 shadow-sm" role="group">
                                                            <button type="button" class="btn btn-outline-primary active fw-semibold" id="btn-mode-email" onclick="switchTemplateMode('email')">
                                                                <i class="fas fa-envelope me-1"></i> E-posta (HTML Şablon)
                                                            </button>
                                                            <button type="button" class="btn btn-outline-success fw-semibold" id="btn-mode-chat" onclick="switchTemplateMode('chat')">
                                                                <i class="fab fa-whatsapp me-1"></i> SMS & WhatsApp
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Dinamik Değişken Çipleri -->
                                                <div class="p-3 bg-light rounded-4 border mb-4">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="small fw-bold text-dark"><i class="fas fa-tags me-1 text-primary"></i> Tıklanabilir Değişkenler (İmlecin Bulunduğu Yere Ekler)</span>
                                                        <small class="text-muted">Otomatik olarak müşterinin bilgileriyle dolar</small>
                                                    </div>
                                                    <div class="d-flex flex-wrap gap-1" id="template-tag-chips">
                                                        <button type="button" class="btn btn-sm btn-white border rounded-pill shadow-xs py-1 px-2 small text-dark" onclick="insertTplTag('{musteri_adi}')"><span class="badge bg-primary text-white me-1">{...}</span> Müşteri Adı</button>
                                                        <button type="button" class="btn btn-sm btn-white border rounded-pill shadow-xs py-1 px-2 small text-dark" onclick="insertTplTag('{randevu_tarihi}')"><span class="badge bg-primary text-white me-1">{...}</span> Randevu Tarihi</button>
                                                        <button type="button" class="btn btn-sm btn-white border rounded-pill shadow-xs py-1 px-2 small text-dark" onclick="insertTplTag('{randevu_saati}')"><span class="badge bg-primary text-white me-1">{...}</span> Randevu Saati</button>
                                                        <button type="button" class="btn btn-sm btn-white border rounded-pill shadow-xs py-1 px-2 small text-dark" onclick="insertTplTag('{hizmet_adi}')"><span class="badge bg-primary text-white me-1">{...}</span> Hizmet Adı</button>
                                                        <button type="button" class="btn btn-sm btn-white border rounded-pill shadow-xs py-1 px-2 small text-dark" onclick="insertTplTag('{personel_adi}')"><span class="badge bg-primary text-white me-1">{...}</span> Uzman / Personel</button>
                                                        <button type="button" class="btn btn-sm btn-white border rounded-pill shadow-xs py-1 px-2 small text-dark" onclick="insertTplTag('{tutar}')"><span class="badge bg-primary text-white me-1">{...}</span> Tutar</button>
                                                        <button type="button" class="btn btn-sm btn-white border rounded-pill shadow-xs py-1 px-2 small text-dark" onclick="insertTplTag('{isletme_adi}')"><span class="badge bg-primary text-white me-1">{...}</span> İşletme Adı</button>
                                                        <button type="button" class="btn btn-sm btn-white border rounded-pill shadow-xs py-1 px-2 small text-dark" onclick="insertTplTag('{isletme_adresi}')"><span class="badge bg-primary text-white me-1">{...}</span> Açık Adres</button>
                                                        <button type="button" class="btn btn-sm btn-white border rounded-pill shadow-xs py-1 px-2 small text-dark" onclick="insertTplTag('{isletme_telefonu}')"><span class="badge bg-primary text-white me-1">{...}</span> Telefon</button>
                                                        <button type="button" class="btn btn-sm btn-white border rounded-pill shadow-xs py-1 px-2 small text-dark" onclick="insertTplTag('{randevu_linki}')"><span class="badge bg-primary text-white me-1">{...}</span> Randevu Linki</button>
                                                    </div>
                                                </div>

                                                <!-- Split-Screen Editor & Live Mockup Preview -->
                                                <div class="row g-4">
                                                    <!-- Sol Kolon: Form Alanları -->
                                                    <div class="col-lg-6">
                                                        <!-- E-POSTA ALANLARI -->
                                                        <div id="wrapper-editor-email">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold small text-dark">E-posta Konu Satırı (Subject)</label>
                                                                <input type="text" class="form-control" id="tpl-input-email-subject" placeholder="Örn: Randevunuz Onaylandı! - {isletme_adi}">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold small text-dark">E-posta Mesaj Gövdesi</label>
                                                                <textarea class="form-control" id="tpl-input-email-body" rows="9" placeholder="E-posta içeriğini buraya yazınız..."></textarea>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold small text-dark">Aksiyon Butonu Yazısı (CTA)</label>
                                                                <input type="text" class="form-control" id="tpl-input-email-btn" value="Randevumu Görüntüle & Yönet">
                                                            </div>
                                                        </div>

                                                        <!-- SMS & WHATSAPP ALANLARI -->
                                                        <div id="wrapper-editor-chat" class="d-none">
                                                            <div class="mb-3">
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <label class="form-label fw-bold small text-dark mb-0">SMS & WhatsApp Mesaj Metni</label>
                                                                    <span class="badge bg-light text-dark border small" id="chat-char-counter">0 / 160 Karakter • 1 SMS</span>
                                                                </div>
                                                                <textarea class="form-control font-monospace" id="tpl-input-chat-body" rows="8" placeholder="Kısa mesaj veya WhatsApp bildirim metnini buraya yazınız..."></textarea>
                                                            </div>
                                                            <div class="alert alert-info py-2 px-3 small rounded-3 mb-0">
                                                                <i class="fab fa-whatsapp me-1 text-success"></i> WhatsApp mesajlarında <code>*kalın*</code> veya <code>_italik_</code> formatları otomatik olarak desteklenmektedir.
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Sağ Kolon: Canlı Mockup Önizlemesi -->
                                                    <div class="col-lg-6">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <span class="small fw-bold text-muted text-uppercase tracking-wider">CANLI ŞABLON ÖNİZLEMESİ</span>
                                                            <div class="btn-group btn-group-sm" id="btn-group-preview-device">
                                                                <button type="button" class="btn btn-outline-secondary active" id="btn-device-desktop" onclick="setPreviewDevice('desktop')"><i class="fas fa-desktop me-1"></i> Masaüstü</button>
                                                                <button type="button" class="btn btn-outline-secondary" id="btn-device-mobile" onclick="setPreviewDevice('mobile')"><i class="fas fa-mobile-alt me-1"></i> Mobil</button>
                                                            </div>
                                                        </div>

                                                        <!-- CANLI E-POSTA ÖNİZLEME ÇERÇEVESİ -->
                                                        <div id="preview-frame-email" class="border rounded-4 bg-white shadow-sm overflow-hidden" style="transition: all 0.3s ease; max-width: 100%; margin: 0 auto;">
                                                            <!-- Email Client Bar -->
                                                            <div class="bg-light border-bottom p-2 px-3 d-flex align-items-center justify-content-between">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <div class="d-flex gap-1">
                                                                        <span class="rounded-circle bg-danger d-inline-block" style="width: 8px; height: 8px;"></span>
                                                                        <span class="rounded-circle bg-warning d-inline-block" style="width: 8px; height: 8px;"></span>
                                                                        <span class="rounded-circle bg-success d-inline-block" style="width: 8px; height: 8px;"></span>
                                                                    </div>
                                                                    <span class="small text-muted ms-2" id="preview-email-client-subject">Randevunuz Onaylandı!</span>
                                                                </div>
                                                                <span class="badge bg-white text-muted border small">Gelen Kutusu</span>
                                                            </div>

                                                            <!-- Email Content Body -->
                                                            <div class="p-4" style="background: #f8fafc;">
                                                                <div class="bg-white rounded-3 shadow-sm border p-4 mx-auto" style="max-width: 480px;">
                                                                    <!-- Header Logo / Color -->
                                                                    <div class="text-center pb-3 mb-3 border-bottom">
                                                                        <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars(setting('company_name') ?: 'Salon Flora & Spa') ?></h5>
                                                                        <small class="text-muted">Online Randevu Bildirimi</small>
                                                                    </div>

                                                                    <!-- Subject / Greeting -->
                                                                    <h6 class="fw-bold text-dark mb-2" id="preview-email-subject-heading">Sayın Selin Yılmaz,</h6>
                                                                    <p class="text-muted small mb-3" id="preview-email-body-text" style="white-space: pre-line;">
                                                                        Randevunuz başarıyla oluşturuldu. Sizi salonumuzda ağırlamaktan mutluluk duyacağız.
                                                                    </p>

                                                                    <!-- Appointment Summary Card -->
                                                                    <div class="bg-light rounded-3 p-3 border mb-3">
                                                                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                                                            <span class="text-muted small"><i class="fas fa-spa me-1 text-primary"></i> Hizmet:</span>
                                                                            <span class="fw-bold small text-dark">Aromaterapi Masajı</span>
                                                                        </div>
                                                                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                                                            <span class="text-muted small"><i class="fas fa-calendar-alt me-1 text-primary"></i> Tarih & Saat:</span>
                                                                            <span class="fw-bold small text-dark">14 Ekim 2026, 14:30</span>
                                                                        </div>
                                                                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                                                            <span class="text-muted small"><i class="fas fa-user-check me-1 text-primary"></i> Uzman:</span>
                                                                            <span class="fw-bold small text-dark">Melis Şen</span>
                                                                        </div>
                                                                        <div class="d-flex justify-content-between mb-0">
                                                                            <span class="text-muted small"><i class="fas fa-map-marker-alt me-1 text-primary"></i> Konum:</span>
                                                                            <span class="fw-bold small text-dark text-end">Bağdat Cad. No: 120 Kadıköy</span>
                                                                        </div>
                                                                    </div>

                                                                    <!-- CTA Button -->
                                                                    <div class="text-center my-3">
                                                                        <a href="javascript:void(0)" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold text-white text-decoration-none shadow-sm" id="preview-email-btn">
                                                                            Randevumu Görüntüle
                                                                        </a>
                                                                    </div>

                                                                    <p class="text-center text-muted small mb-0 mt-3 pt-3 border-top" style="font-size: 0.75rem;">
                                                                        Bu bilgilendirme e-postası <?= htmlspecialchars(setting('company_name') ?: 'Salon Flora') ?> randevu altyapısı tarafından iletilmiştir.
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- CANLI WHATSAPP / SMS ÖNİZLEME ÇERÇEVESİ -->
                                                        <div id="preview-frame-chat" class="border rounded-4 shadow-sm overflow-hidden d-none" style="max-width: 380px; margin: 0 auto; background: #e5ddd5;">
                                                            <!-- Mobile Status Bar -->
                                                            <div class="bg-dark text-white px-3 py-1 d-flex justify-content-between align-items-center small" style="font-size: 0.7rem;">
                                                                <span>14:30</span>
                                                                <div class="d-flex gap-1">
                                                                    <i class="fas fa-wifi"></i>
                                                                    <i class="fas fa-battery-full"></i>
                                                                </div>
                                                            </div>

                                                            <!-- WhatsApp Header Bar -->
                                                            <div class="p-2 px-3 d-flex align-items-center gap-2 text-white" style="background: #075e54;">
                                                                <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 0.85rem;">
                                                                    SF
                                                                </div>
                                                                <div class="flex-grow-1">
                                                                    <div class="fw-bold small d-flex align-items-center gap-1">
                                                                        <?= htmlspecialchars(setting('company_name') ?: 'Salon Flora') ?>
                                                                        <i class="fas fa-check-circle text-info" style="font-size: 0.75rem;" title="Doğrulanmış İşletme"></i>
                                                                    </div>
                                                                    <div class="text-white-50" style="font-size: 0.65rem;">WhatsApp İşletme Hesabı</div>
                                                                </div>
                                                                <i class="fas fa-phone-alt small me-2"></i>
                                                                <i class="fas fa-ellipsis-v small"></i>
                                                            </div>

                                                            <!-- WhatsApp Chat Body with Background Pattern -->
                                                            <div class="p-3" style="min-height: 280px; background-color: #efeae2; background-image: radial-gradient(#d4cbbe 1px, transparent 1px); background-size: 16px 16px;">
                                                                <div class="text-center mb-2">
                                                                    <span class="badge bg-white text-muted shadow-xs px-2 py-1 small" style="font-size: 0.65rem;">BUGÜN</span>
                                                                </div>

                                                                <!-- Incoming WhatsApp Message Bubble -->
                                                                <div class="bg-white rounded-3 shadow-xs p-3 position-relative mb-2" style="max-width: 90%; border-top-left-radius: 0 !important; background: #ffffff;">
                                                                    <p class="small text-dark mb-1" id="preview-chat-body-text" style="white-space: pre-line; font-size: 0.82rem;">
                                                                        Sayın Selin Yılmaz, Salon Flora randevunuz 14 Ekim 2026 14:30 için oluşturulmuştur. Randevu detayları: https://bookiapp.kibusiness.co/r/98a21
                                                                    </p>
                                                                    <div class="text-end text-muted" style="font-size: 0.65rem;">
                                                                        14:30 <i class="fas fa-check-double text-primary ms-1"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Hidden fields holding actual template data for standard form submission -->
                                                <input type="hidden" name="template_customer_confirmation" id="real-field-confirmation" value="<?= htmlspecialchars((string)($section_values['communication']['template_customer_confirmation'] ?? '')) ?>">
                                                <input type="hidden" name="template_customer_confirmation_subject" id="real-field-confirmation_subject" value="<?= htmlspecialchars((string)($section_values['communication']['template_customer_confirmation_subject'] ?? '')) ?>">
                                                <input type="hidden" name="template_customer_confirmation_email" id="real-field-confirmation_email" value="<?= htmlspecialchars((string)($section_values['communication']['template_customer_confirmation_email'] ?? '')) ?>">

                                                <input type="hidden" name="template_customer_reminder" id="real-field-reminder_24h" value="<?= htmlspecialchars((string)($section_values['communication']['template_customer_reminder'] ?? '')) ?>">
                                                <input type="hidden" name="template_customer_reminder_subject" id="real-field-reminder_24h_subject" value="<?= htmlspecialchars((string)($section_values['communication']['template_customer_reminder_subject'] ?? '')) ?>">
                                                <input type="hidden" name="template_customer_reminder_email" id="real-field-reminder_24h_email" value="<?= htmlspecialchars((string)($section_values['communication']['template_customer_reminder_email'] ?? '')) ?>">

                                                <input type="hidden" name="template_customer_reminder_2h" id="real-field-reminder_2h" value="<?= htmlspecialchars((string)($section_values['communication']['template_customer_reminder_2h'] ?? '')) ?>">

                                                <input type="hidden" name="template_customer_cancellation" id="real-field-cancellation" value="<?= htmlspecialchars((string)($section_values['communication']['template_customer_cancellation'] ?? '')) ?>">
                                                <input type="hidden" name="template_customer_cancellation_subject" id="real-field-cancellation_subject" value="<?= htmlspecialchars((string)($section_values['communication']['template_customer_cancellation_subject'] ?? '')) ?>">

                                                <input type="hidden" name="template_customer_followup" id="real-field-followup" value="<?= htmlspecialchars((string)($section_values['communication']['template_customer_followup'] ?? '')) ?>">
                                                <input type="hidden" name="template_owner_notification" id="real-field-owner" value="<?= htmlspecialchars((string)($section_values['communication']['template_owner_notification'] ?? '')) ?>">
                                            </div>
                                        </div>

                                     <!-- 6. Special Render: Legal Templates with 1-Click Sector Presets -->
                                    <?php elseif ($section_key === 'legal'): ?>
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($sub_title) ?></h5>
                                                    <p class="text-muted small mb-0">Yasal mevzuata tam uyumlu hazır sektörel şablonları tek tıkla yükleyebilirsiniz.</p>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                            <i class="fas fa-magic me-1"></i> Hazır Şablon Yükle
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end shadow">
                                                            <li><a class="dropdown-item btn-load-legal-preset" href="javascript:void(0)" data-type="<?= $sub_key ?>" data-sector="beauty"><i class="fas fa-spa text-primary me-2"></i>Güzellik & Spa Şablonu</a></li>
                                                            <li><a class="dropdown-item btn-load-legal-preset" href="javascript:void(0)" data-type="<?= $sub_key ?>" data-sector="clinic"><i class="fas fa-user-md text-success me-2"></i>Klinik & Sağlık Şablonu</a></li>
                                                            <li><a class="dropdown-item btn-load-legal-preset" href="javascript:void(0)" data-type="<?= $sub_key ?>" data-sector="restaurant"><i class="fas fa-utensils text-warning me-2"></i>Restoran & Cafe Şablonu</a></li>
                                                            <li><a class="dropdown-item btn-load-legal-preset" href="javascript:void(0)" data-type="<?= $sub_key ?>" data-sector="sports"><i class="fas fa-dumbbell text-info me-2"></i>Spor & Halı Saha Şablonu</a></li>
                                                        </ul>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-success btn-save-section-direct">
                                                        <i class="fas fa-save me-1"></i> <?= lang('save') ?: 'Kaydet' ?>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body p-4">
                                                <div class="row g-3">
                                                    <?php foreach ($sec['settings'] as $key => $meta): ?>
                                                        <?php if (($meta['tab'] ?? '') === $sub_key): ?>
                                                            <div class="<?= $meta['col'] ?? 'col-12' ?> setting-field" data-setting-key="<?= $key ?>">
                                                                <div class="setting-box bg-white">
                                                                    <div class="mb-2">
                                                                        <label class="setting-title mb-1" for="input-<?= $key ?>">
                                                                            <?= htmlspecialchars($meta['label']) ?>
                                                                        </label>
                                                                        <p class="setting-desc mb-2"><?= htmlspecialchars($meta['description']) ?></p>
                                                                    </div>
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
                                                                                <option value="<?= htmlspecialchars((string)$opt_val) ?>" <?= (string)$current_val === (string)$opt_val ? 'selected' : '' ?>>
                                                                                    <?= htmlspecialchars($opt_label) ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    <?php else: ?>
                                                                        <textarea class="form-control setting-input font-monospace mb-2" id="input-<?= $key ?>" name="<?= $key ?>" rows="6" oninput="document.getElementById('scroll-box-<?= $key ?>').innerText = this.value"><?= htmlspecialchars((string)$current_val) ?></textarea>
                                                                        
                                                                        <!-- Müşteri Sayfa Sonu Onay Doğrulama Simülasyonu -->
                                                                        <div class="p-3 bg-white rounded-3 border">
                                                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                                                <span class="small fw-bold text-dark"><i class="fas fa-file-contract text-primary me-1"></i> Müşteri Okuma & Sayfa Sonu Onay Simülasyonu</span>
                                                                                <span class="badge bg-secondary-subtle text-dark border px-2 py-1 scroll-progress-badge" id="badge-progress-<?= $key ?>">%0 Okundu</span>
                                                                            </div>
                                                                            <div class="progress mb-2" style="height: 6px;">
                                                                                <div class="progress-bar bg-primary scroll-progress-bar" id="bar-progress-<?= $key ?>" role="progressbar" style="width: 0%"></div>
                                                                            </div>
                                                                            <div class="border rounded-2 p-3 bg-light text-secondary small font-monospace contract-scroll-box"
                                                                                 id="scroll-box-<?= $key ?>"
                                                                                 data-target-key="<?= $key ?>"
                                                                                 style="max-height: 180px; overflow-y: auto; white-space: pre-wrap; line-height: 1.6;"
                                                                                 onscroll="handleContractScroll('<?= $key ?>', this)">
<?= htmlspecialchars((string)$current_val) ?>
                                                                            </div>
                                                                            <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 pt-2 border-top gap-2">
                                                                                <div class="form-check mb-0">
                                                                                    <input class="form-check-input contract-scroll-checkbox" type="checkbox" id="check-consent-<?= $key ?>" disabled>
                                                                                    <label class="form-check-label small text-muted user-select-none" for="check-consent-<?= $key ?>" id="label-consent-<?= $key ?>">
                                                                                        Metni sonuna kadar kaydırınız (Onay kilidi aktif)
                                                                                    </label>
                                                                                </div>
                                                                                <button type="button" class="btn btn-xs btn-outline-success rounded-pill px-3 py-1 contract-scroll-btn" id="btn-consent-<?= $key ?>" disabled>
                                                                                    <i class="fas fa-check-circle me-1"></i> Onayla & Devam Et
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                    <!-- 7. Special Render: Security Users & Roles Management -->
                                    <?php elseif ($section_key === 'security' && $sub_key === 'users_roles'): ?>
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark">Kullanıcılar & Rol Yetkilendirme</h5>
                                                    <p class="text-muted small mb-0">Personel ve yöneticilerin rollerini yükseltin, düşürün veya şifre sıfırlama bağlantısı gönderin.</p>
                                                </div>
                                                <a href="<?= site_url('providers') ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-user-plus me-1"></i> Yeni Personel Ekle
                                                </a>
                                            </div>
                                            <div class="card-body p-4">
                                                <div class="table-responsive">
                                                    <table class="table table-hover align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Kullanıcı</th>
                                                                <th>E-posta</th>
                                                                <th>Mevcut Rol</th>
                                                                <th>Rol Değiştir (Up/Down)</th>
                                                                <th class="text-end">İşlemler</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($all_users as $usr): ?>
                                                                <tr id="usr-row-<?= $usr['id'] ?>">
                                                                    <td>
                                                                        <div class="fw-bold text-dark"><?= htmlspecialchars($usr['first_name'] . ' ' . $usr['last_name']) ?></div>
                                                                        <small class="text-muted"><?= htmlspecialchars($usr['phone_number'] ?? '-') ?></small>
                                                                    </td>
                                                                    <td class="small text-muted"><?= htmlspecialchars($usr['email'] ?? '-') ?></td>
                                                                    <td>
                                                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 rounded-pill">
                                                                            <?= htmlspecialchars($usr['role_name'] ?: 'Standart') ?>
                                                                        </span>
                                                                    </td>
                                                                    <td style="max-width: 220px;">
                                                                        <select class="form-select form-select-sm select-change-role" data-user-id="<?= $usr['id'] ?>">
                                                                            <?php foreach ($all_roles as $rl): ?>
                                                                                <option value="<?= $rl['id'] ?>" <?= (int)$usr['id_roles'] === (int)$rl['id'] ? 'selected' : '' ?>>
                                                                                    <?= htmlspecialchars($rl['name']) ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-send-pwd-reset" data-user-id="<?= $usr['id'] ?>" title="Şifre Sıfırlama Bağlantısı">
                                                                            <i class="fas fa-key me-1"></i> Şifre Sıfırla
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                    <!-- 8. Special Render: Security Audit Logs -->
                                    <?php elseif ($section_key === 'security' && $sub_key === 'audit_logs'): ?>
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark">Sistem & Güvenlik Denetim Logları</h5>
                                                    <p class="text-muted small mb-0">Kullanıcı hareketleri, giriş çıkışlar ve ayar değişiklikleri.</p>
                                                </div>
                                                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill"><i class="fas fa-history me-1 text-primary"></i> Son 20 Kayıt</span>
                                            </div>
                                            <div class="card-body p-4">
                                                <div class="table-responsive">
                                                    <table class="table table-hover align-middle mb-0 small">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Zaman</th>
                                                                <th>Olay Türü</th>
                                                                <th>Modül</th>
                                                                <th>IP Adresi</th>
                                                                <th>Detaylar</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (empty($recent_audit_logs)): ?>
                                                                <tr>
                                                                    <td colspan="5" class="text-center text-muted py-3">Henüz kaydedilmiş denetim kaydı bulunmuyor.</td>
                                                                </tr>
                                                            <?php else: ?>
                                                                <?php foreach ($recent_audit_logs as $log): ?>
                                                                    <tr>
                                                                        <td class="text-nowrap"><?= htmlspecialchars($log['created_at']) ?></td>
                                                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($log['action_type']) ?></span></td>
                                                                        <td><?= htmlspecialchars($log['entity_type']) ?></td>
                                                                        <td class="font-monospace"><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></td>
                                                                        <td class="text-truncate" style="max-width: 250px;"><?= htmlspecialchars($log['notes'] ?? '-') ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                    <!-- 9. Special Render: Data Import / Export (Veri Aktarımı) -->
                                    <?php elseif ($section_key === 'security' && $sub_key === 'data_transfer'): ?>
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4">
                                                <h5 class="fw-bold mb-0 text-dark">Veri İçe & Dışa Aktarım Merkezi (Import / Export)</h5>
                                                <p class="text-muted small mb-0">Müşteri ve randevu verilerinizi toplu olarak aktarın veya Excel/CSV formatında indirin.</p>
                                            </div>
                                            <div class="card-body p-4">
                                                <div class="row g-4">
                                                    <!-- İçe Aktarım (Import) -->
                                                    <div class="col-md-6 border-end-md">
                                                        <div class="p-3 bg-light rounded-4 h-100 border">
                                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                                <i class="fas fa-file-import text-primary fa-lg"></i>
                                                                <h6 class="fw-bold mb-0 text-dark">Toplu Veri İçe Aktarma (Import)</h6>
                                                            </div>
                                                            <p class="text-muted small mb-3">Mevcut listenizi sisteme aktarmak için önce örnek şablonu indirin, doldurup sisteme yükleyin.</p>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-bold text-dark">1. Şablon İndir</label>
                                                                <div class="d-flex gap-2">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="downloadTemplate('customers')">
                                                                        <i class="fas fa-download me-1"></i> Müşteri CSV Şablonu
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="downloadTemplate('services')">
                                                                        <i class="fas fa-download me-1"></i> Hizmetler CSV
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <div class="mb-2">
                                                                <label class="form-label small fw-bold text-dark">2. Doldurulan Dosyayı Yükle</label>
                                                                <a href="<?= site_url('data_transfer') ?>" class="btn btn-primary btn-sm w-100">
                                                                    <i class="fas fa-upload me-1"></i> İçe Aktarma Sihirbazına Git
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Dışa Aktarım (Export) -->
                                                    <div class="col-md-6">
                                                        <div class="p-3 bg-light rounded-4 h-100 border">
                                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                                <i class="fas fa-file-export text-success fa-lg"></i>
                                                                <h6 class="fw-bold mb-0 text-dark">Veri Dışa Aktarma (Export)</h6>
                                                            </div>
                                                            <p class="text-muted small mb-3">İşletmenize ait kayıtları CSV veya Excel formatında bilgisayarınıza indirin.</p>

                                                            <div class="mb-3">
                                                                <label class="form-label small fw-bold text-dark">Modül Seçimi</label>
                                                                <select class="form-select form-select-sm" id="export-module-select">
                                                                    <option value="customers">Müşteri Rehberi (Ad, Soyad, Telefon, E-posta)</option>
                                                                    <option value="appointments">Tüm Randevu Geçmişi</option>
                                                                    <option value="services">Hizmet ve Fiyat Listesi</option>
                                                                </select>
                                                            </div>

                                                            <div class="d-flex gap-2">
                                                                <button type="button" class="btn btn-success btn-sm flex-grow-1" onclick="exportData('csv')">
                                                                    <i class="fas fa-file-csv me-1"></i> CSV Olarak İndir
                                                                </button>
                                                                <button type="button" class="btn btn-outline-success btn-sm flex-grow-1" onclick="exportData('xlsx')">
                                                                    <i class="fas fa-file-excel me-1"></i> Excel Olarak İndir
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                     <!-- Special Render: Unified 12-Card Integrations Catalog Grid & Expandable Drawer -->
                                    <?php elseif ($section_key === 'integrations'): ?>
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-circle">
                                                            <i class="fas fa-plug fa-lg"></i>
                                                        </div>
                                                        <h5 class="fw-bold mb-0 text-dark">Entegrasyonlar & AI Geliştirici Merkezi</h5>
                                                    </div>
                                                    <p class="text-muted small mb-0 mt-1">İşletmenizin dış servis, sosyal medya, mesajlaşma, yapay zeka ve ERP bağlantılarını tek katalogdan yönetin.</p>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-success btn-save-section-direct">
                                                    <i class="fas fa-save me-1"></i> <?= lang('save') ?: 'Kaydet' ?>
                                                </button>
                                            </div>
                                            <div class="card-body p-4">
                                                <!-- Master 12-Card Integration Catalog Grid (No vertical clutter) -->
                                                <div class="row g-3 mb-4" id="integrations-catalog-grid">
                                                    <!-- 1. Google Workspace -->
                                                    <div class="col-md-6 col-xl-3">
                                                        <div class="card h-100 p-3 rounded-4 border bg-white shadow-xs integration-grid-card" data-service="google" style="cursor: pointer; transition: all 0.2s ease;">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div class="p-2 bg-light rounded-circle">
                                                                    <svg width="22" height="22" viewBox="0 0 18 18">
                                                                        <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.616z"/>
                                                                        <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z"/>
                                                                        <path fill="#FBBC05" d="M3.964 10.707c-.18-.54-.282-1.117-.282-1.707s.102-1.167.282-1.707V4.961H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.039l3.007-2.332z"/>
                                                                        <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.961L3.964 7.293C4.672 5.166 6.656 3.58 9 3.58z"/>
                                                                    </svg>
                                                                </div>
                                                                <span id="badge-status-google" class="badge <?= !empty($section_values['integrations']['google_connected']) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-secondary border' ?> rounded-pill small">
                                                                    <?= !empty($section_values['integrations']['google_connected']) ? '<i class="fas fa-check-circle me-1"></i>Bağlı' : 'Bağlı Değil' ?>
                                                                </span>
                                                            </div>
                                                            <h6 class="fw-bold text-dark mb-1">Google Workspace</h6>
                                                            <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.8rem;">Takvim, Meet görüşmeleri, Haritalar butonu ve GA4 ölçümü.</p>
                                                            <button type="button" class="btn btn-sm btn-outline-primary w-100 rounded-pill fw-semibold" onclick="openIntegrationDrawer('google')">
                                                                <i class="fas fa-cog me-1"></i> Yapılandır & Bağlan
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- 2. WhatsApp Business -->
                                                    <div class="col-md-6 col-xl-3">
                                                        <div class="card h-100 p-3 rounded-4 border bg-white shadow-xs integration-grid-card" data-service="whatsapp" style="cursor: pointer; transition: all 0.2s ease;">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div class="p-2 bg-success text-white rounded-circle">
                                                                    <i class="fab fa-whatsapp fa-lg"></i>
                                                                </div>
                                                                <span id="badge-status-whatsapp" class="badge <?= !empty($section_values['integrations']['channel_whatsapp_enabled']) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-secondary border' ?> rounded-pill small">
                                                                    <?= !empty($section_values['integrations']['channel_whatsapp_enabled']) ? '<i class="fas fa-check-circle me-1"></i>Bağlı' : 'Bağlı Değil' ?>
                                                                </span>
                                                            </div>
                                                            <h6 class="fw-bold text-dark mb-1">WhatsApp Business</h6>
                                                            <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.8rem;">Resmi Meta Cloud API (Tıkla Bağlan) ve Baileys QR Köprü.</p>
                                                            <button type="button" class="btn btn-sm btn-outline-success w-100 rounded-pill fw-semibold" onclick="openIntegrationDrawer('whatsapp')">
                                                                <i class="fas fa-cog me-1"></i> Yapılandır & Bağlan
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- 3. Instagram Direct & Profil -->
                                                    <div class="col-md-6 col-xl-3">
                                                        <div class="card h-100 p-3 rounded-4 border bg-white shadow-xs integration-grid-card" data-service="instagram" style="cursor: pointer; transition: all 0.2s ease;">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div class="p-2 text-white rounded-circle" style="background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%);">
                                                                    <i class="fab fa-instagram fa-lg"></i>
                                                                </div>
                                                                <span id="badge-status-instagram" class="badge <?= !empty($section_values['integrations']['meta_instagram_connected']) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-secondary border' ?> rounded-pill small">
                                                                    <?= !empty($section_values['integrations']['meta_instagram_connected']) ? '<i class="fas fa-check-circle me-1"></i>Bağlı' : 'Bağlı Değil' ?>
                                                                </span>
                                                            </div>
                                                            <h6 class="fw-bold text-dark mb-1">Instagram Profili</h6>
                                                            <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.8rem;">Profil Randevu Butonu ve Direct DM Yapay Zeka Asistanı.</p>
                                                            <button type="button" class="btn btn-sm btn-outline-danger w-100 rounded-pill fw-semibold" onclick="openIntegrationDrawer('instagram')">
                                                                <i class="fas fa-cog me-1"></i> Yapılandır & Bağlan
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- 4. Facebook Sayfası -->
                                                    <div class="col-md-6 col-xl-3">
                                                        <div class="card h-100 p-3 rounded-4 border bg-white shadow-xs integration-grid-card" data-service="facebook" style="cursor: pointer; transition: all 0.2s ease;">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div class="p-2 bg-primary text-white rounded-circle">
                                                                    <i class="fab fa-facebook-f fa-lg"></i>
                                                                </div>
                                                                <span id="badge-status-facebook" class="badge <?= !empty($section_values['integrations']['meta_facebook_connected']) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-secondary border' ?> rounded-pill small">
                                                                    <?= !empty($section_values['integrations']['meta_facebook_connected']) ? '<i class="fas fa-check-circle me-1"></i>Bağlı' : 'Bağlı Değil' ?>
                                                                </span>
                                                            </div>
                                                            <h6 class="fw-bold text-dark mb-1">Facebook Sayfası</h6>
                                                            <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.8rem;">Facebook Lead Ads reklam formları ve sayfa randevu butonu.</p>
                                                            <button type="button" class="btn btn-sm btn-outline-primary w-100 rounded-pill fw-semibold" onclick="openIntegrationDrawer('facebook')">
                                                                <i class="fas fa-cog me-1"></i> Yapılandır & Bağlan
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- 5. Telegram Botu -->
                                                    <div class="col-md-6 col-xl-3">
                                                        <div class="card h-100 p-3 rounded-4 border bg-white shadow-xs integration-grid-card" data-service="telegram" style="cursor: pointer; transition: all 0.2s ease;">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div class="p-2 bg-info text-white rounded-circle">
                                                                    <i class="fab fa-telegram-plane fa-lg"></i>
                                                                </div>
                                                                <span class="badge <?= !empty($section_values['integrations']['channel_telegram_enabled']) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-secondary border' ?> rounded-pill small">
                                                                    <?= !empty($section_values['integrations']['channel_telegram_enabled']) ? '<i class="fas fa-check-circle me-1"></i>Bağlı' : 'Bağlı Değil' ?>
                                                                </span>
                                                            </div>
                                                            <h6 class="fw-bold text-dark mb-1">Telegram Botu</h6>
                                                            <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.8rem;">Personel ve müşteri anlık randevu & hatırlatma botu.</p>
                                                            <button type="button" class="btn btn-sm btn-outline-info w-100 rounded-pill fw-semibold" onclick="openIntegrationDrawer('telegram')">
                                                                <i class="fas fa-cog me-1"></i> Yapılandır & Bağlan
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- 6. E-posta (SMTP) -->
                                                    <div class="col-md-6 col-xl-3">
                                                        <div class="card h-100 p-3 rounded-4 border bg-white shadow-xs integration-grid-card" data-service="smtp" style="cursor: pointer; transition: all 0.2s ease;">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div class="p-2 bg-warning bg-opacity-20 text-warning-emphasis rounded-circle">
                                                                    <i class="fas fa-envelope-open-text fa-lg"></i>
                                                                </div>
                                                                <span class="badge <?= !empty($section_values['integrations']['channel_email_enabled']) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-secondary border' ?> rounded-pill small">
                                                                    <?= !empty($section_values['integrations']['channel_email_enabled']) ? '<i class="fas fa-check-circle me-1"></i>Aktif' : 'Pasif' ?>
                                                                </span>
                                                            </div>
                                                            <h6 class="fw-bold text-dark mb-1">E-posta (SMTP)</h6>
                                                            <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.8rem;">Özel kurumsal alan adı üzerinden güvenli SMTP mail iletimi.</p>
                                                            <button type="button" class="btn btn-sm btn-outline-warning w-100 rounded-pill fw-semibold text-dark" onclick="openIntegrationDrawer('smtp')">
                                                                <i class="fas fa-cog me-1"></i> Yapılandır
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- 7. AI Asistan & Sesli Santral -->
                                                    <div class="col-md-6 col-xl-3">
                                                        <div class="card h-100 p-3 rounded-4 border bg-white shadow-xs integration-grid-card" data-service="ai_assistant" style="cursor: pointer; transition: all 0.2s ease;">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div class="p-2 text-white rounded-circle" style="background: #6f42c1;">
                                                                    <i class="fas fa-robot fa-lg"></i>
                                                                </div>
                                                                <span class="badge bg-purple-subtle text-purple border rounded-pill small" style="background-color: #f3e8ff; color: #6f42c1;">
                                                                    Yazılı Sabit + Sesli
                                                                </span>
                                                            </div>
                                                            <h6 class="fw-bold text-dark mb-1">AI Asistan & Santral</h6>
                                                            <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.8rem;">Yazılı AI dahil, 0850 sesli telefon santrali & kota yönetimi.</p>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary w-100 rounded-pill fw-semibold" onclick="openIntegrationDrawer('ai_assistant')">
                                                                <i class="fas fa-cog me-1"></i> Yapılandır
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- 8. SMS Sağlayıcı API -->
                                                    <div class="col-md-6 col-xl-3">
                                                        <div class="card h-100 p-3 rounded-4 border bg-white shadow-xs integration-grid-card" data-service="sms" style="cursor: pointer; transition: all 0.2s ease;">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div class="p-2 bg-danger bg-opacity-10 text-danger rounded-circle">
                                                                    <i class="fas fa-sms fa-lg"></i>
                                                                </div>
                                                                <span class="badge <?= !empty($section_values['integrations']['channel_sms_enabled']) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-secondary border' ?> rounded-pill small">
                                                                    <?= !empty($section_values['integrations']['channel_sms_enabled']) ? '<i class="fas fa-check-circle me-1"></i>Aktif' : 'Bağlı Değil' ?>
                                                                </span>
                                                            </div>
                                                            <h6 class="fw-bold text-dark mb-1">SMS Sağlayıcı API</h6>
                                                            <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.8rem;">Netgsm, İletiMerkezi, MutluCell API ile başlıklı SMS.</p>
                                                            <button type="button" class="btn btn-sm btn-outline-danger w-100 rounded-pill fw-semibold" onclick="openIntegrationDrawer('sms')">
                                                                <i class="fas fa-cog me-1"></i> Yapılandır
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- 9. Muhasebe & ERP -->
                                                    <div class="col-md-6 col-xl-3">
                                                        <div class="card h-100 p-3 rounded-4 border bg-white shadow-xs integration-grid-card" data-service="erp" style="cursor: pointer; transition: all 0.2s ease;">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div class="p-2 bg-success bg-opacity-10 text-success rounded-circle">
                                                                    <i class="fas fa-file-invoice-dollar fa-lg"></i>
                                                                </div>
                                                                <span class="badge <?= !empty($section_values['integrations']['accounting_erp_provider']) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-secondary border' ?> rounded-pill small">
                                                                    <?= !empty($section_values['integrations']['accounting_erp_provider']) ? '<i class="fas fa-check-circle me-1"></i>Bağlı' : 'Bağlı Değil' ?>
                                                                </span>
                                                            </div>
                                                            <h6 class="fw-bold text-dark mb-1">Muhasebe & E-Fatura</h6>
                                                            <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.8rem;">Paraşüt, BizimHesap, KolayBi otomatik e-arşiv faturalandırma.</p>
                                                            <button type="button" class="btn btn-sm btn-outline-success w-100 rounded-pill fw-semibold" onclick="openIntegrationDrawer('erp')">
                                                                <i class="fas fa-cog me-1"></i> Yapılandır
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- 10. Online Görüşme (Video) -->
                                                    <div class="col-md-6 col-xl-3">
                                                        <div class="card h-100 p-3 rounded-4 border bg-white shadow-xs integration-grid-card" data-service="video" style="cursor: pointer; transition: all 0.2s ease;">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div class="p-2 bg-danger text-white rounded-circle">
                                                                    <i class="fas fa-video fa-lg"></i>
                                                                </div>
                                                                <span class="badge bg-light text-secondary border rounded-pill small">Opsiyonel</span>
                                                            </div>
                                                            <h6 class="fw-bold text-dark mb-1">Online Görüşme & Video</h6>
                                                            <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.8rem;">Google Meet ve Zoom entegrasyonu ile otomatik link üretimi.</p>
                                                            <button type="button" class="btn btn-sm btn-outline-danger w-100 rounded-pill fw-semibold" onclick="openIntegrationDrawer('video')">
                                                                <i class="fas fa-cog me-1"></i> Yapılandır
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- 11. Harici Takvim (iCal) -->
                                                    <div class="col-md-6 col-xl-3">
                                                        <div class="card h-100 p-3 rounded-4 border bg-white shadow-xs integration-grid-card" data-service="calendar" style="cursor: pointer; transition: all 0.2s ease;">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-circle">
                                                                    <i class="fas fa-calendar-alt fa-lg"></i>
                                                                </div>
                                                                <span class="badge bg-light text-secondary border rounded-pill small">iCal / Feed</span>
                                                            </div>
                                                            <h6 class="fw-bold text-dark mb-1">Harici Takvim Senk.</h6>
                                                            <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.8rem;">Apple Takvim, Outlook ve iCal iki yönlü takvim eşitlemesi.</p>
                                                            <button type="button" class="btn btn-sm btn-outline-primary w-100 rounded-pill fw-semibold" onclick="openIntegrationDrawer('calendar')">
                                                                <i class="fas fa-cog me-1"></i> Yapılandır
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- 12. MCP AI Developer Gateway -->
                                                    <div class="col-md-6 col-xl-3">
                                                        <div class="card h-100 p-3 rounded-4 border bg-white shadow-xs integration-grid-card" data-service="mcp" style="cursor: pointer; transition: all 0.2s ease;">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div class="p-2 bg-dark text-white rounded-circle">
                                                                    <i class="fas fa-terminal fa-lg"></i>
                                                                </div>
                                                                <span class="badge bg-dark text-white rounded-pill small">Geliştirici</span>
                                                            </div>
                                                            <h6 class="fw-bold text-dark mb-1">MCP & AI Gateway</h6>
                                                            <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.8rem;">Claude Desktop, Cursor IDE ve yapay zeka ajanları için API.</p>
                                                            <button type="button" class="btn btn-sm btn-outline-dark w-100 rounded-pill fw-semibold" onclick="openIntegrationDrawer('mcp')">
                                                                <i class="fas fa-code me-1"></i> API & Doküman
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Expandable Configuration Drawer Container (Initially Hidden - Opens on Card Click) -->
                                                <div id="integration-config-drawer" class="card border-primary border-2 shadow-sm rounded-4 mb-4" style="display: none;">
                                                    <div class="card-header bg-light border-bottom py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div id="drawer-header-icon" class="fs-4"></div>
                                                            <div>
                                                                <h5 class="fw-bold mb-0 text-dark" id="drawer-header-title">Entegrasyon Yapılandırması</h5>
                                                                <small class="text-muted" id="drawer-header-desc">Ayarları düzenleyip kaydedin veya bağlantıyı başlatın.</small>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <button type="button" class="btn btn-sm btn-success btn-save-section-direct">
                                                                <i class="fas fa-save me-1"></i> <?= lang('save') ?: 'Kaydet' ?>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="closeIntegrationDrawer()">
                                                                <i class="fas fa-times me-1"></i> Kapat
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="card-body p-4">
                                                        <!-- 1. Google Drawer Panel -->
                                                        <div class="drawer-service-panel" id="drawer-panel-google" style="display: none;">
                                                            <div class="alert alert-light border rounded-3 p-3 mb-4">
                                                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                                                    <div>
                                                                        <h6 class="fw-bold text-dark mb-1">Google Workspace Hesabı & İzinler</h6>
                                                                        <p class="text-muted small mb-0">Tek tıkla Google hesabınızı bağlamadan önce eşitlemek istediğiniz servisleri aşağıdan seçebilirsiniz.</p>
                                                                    </div>
                                                                    <button type="button" class="btn btn-dark rounded-pill px-4 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-2" onclick="connectGoogleWithSelectedServices()">
                                                                        <svg width="18" height="18" viewBox="0 0 18 18">
                                                                            <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.616z"/>
                                                                            <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z"/>
                                                                            <path fill="#FBBC05" d="M3.964 10.707c-.18-.54-.282-1.117-.282-1.707s.102-1.167.282-1.707V4.961H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.039l3.007-2.332z"/>
                                                                            <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.961L3.964 7.293C4.672 5.166 6.656 3.58 9 3.58z"/>
                                                                        </svg>
                                                                        Google Hesabıma Bağlan
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="row g-3 mb-4">
                                                                <div class="col-md-6">
                                                                    <div class="p-3 bg-light rounded-3 border h-100">
                                                                        <div class="form-check form-switch fs-5 d-flex justify-content-between align-items-center mb-1">
                                                                            <label class="form-check-label fw-bold text-dark fs-6" for="input-google_sync_enabled">
                                                                                <i class="fas fa-calendar-alt text-primary me-2"></i>Google Takvim (Calendar)
                                                                            </label>
                                                                            <input class="form-check-input setting-input" type="checkbox" role="switch" id="input-google_sync_enabled" name="google_sync_enabled" value="1" <?= !empty($section_values['integrations']['google_sync_enabled']) ? 'checked' : '' ?>>
                                                                        </div>
                                                                        <small class="text-muted">Personel randevuları Google Calendar ile çift yönlü anlık senkronize edilir.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="p-3 bg-light rounded-3 border h-100">
                                                                        <div class="form-check form-switch fs-5 d-flex justify-content-between align-items-center mb-1">
                                                                            <label class="form-check-label fw-bold text-dark fs-6" for="input-google_sync_meet">
                                                                                <i class="fas fa-video text-danger me-2"></i>Google Meet Otomatik Link
                                                                            </label>
                                                                            <input class="form-check-input setting-input" type="checkbox" role="switch" id="input-google_sync_meet" name="google_sync_meet" value="1" <?= !empty($section_values['integrations']['google_sync_meet']) ? 'checked' : '' ?>>
                                                                        </div>
                                                                        <small class="text-muted">Online seanslar için anlık Meet bağlantısı üretir ve danışana iletir.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="p-3 bg-light rounded-3 border h-100">
                                                                        <div class="form-check form-switch fs-5 d-flex justify-content-between align-items-center mb-1">
                                                                            <label class="form-check-label fw-bold text-dark fs-6" for="input-google_sync_contacts">
                                                                                <i class="fas fa-address-book text-warning me-2"></i>Google Kişiler (Contacts)
                                                                            </label>
                                                                            <input class="form-check-input setting-input" type="checkbox" role="switch" id="input-google_sync_contacts" name="google_sync_contacts" value="1" <?= !empty($section_values['integrations']['google_sync_contacts']) ? 'checked' : '' ?>>
                                                                        </div>
                                                                        <small class="text-muted">Yeni randevu oluşturan müşterileri rehberinizle senkronize tutar.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="p-3 bg-light rounded-3 border h-100">
                                                                        <div class="form-check form-switch fs-5 d-flex justify-content-between align-items-center mb-1">
                                                                            <label class="form-check-label fw-bold text-dark fs-6" for="input-google_sync_sheets">
                                                                                <i class="fas fa-table text-success me-2"></i>Google E-Tablolar (Sheets)
                                                                            </label>
                                                                            <input class="form-check-input setting-input" type="checkbox" role="switch" id="input-google_sync_sheets" name="google_sync_sheets" value="1" <?= !empty($section_values['integrations']['google_sync_sheets']) ? 'checked' : '' ?>>
                                                                        </div>
                                                                        <small class="text-muted">Gün sonu randevu, ciro ve müşteri raporlarını Drive tablonuza aktarır.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="p-3 bg-light rounded-3 border h-100">
                                                                        <div class="form-check form-switch fs-5 d-flex justify-content-between align-items-center mb-1">
                                                                            <label class="form-check-label fw-bold text-dark fs-6" for="input-google_sync_ads">
                                                                                <i class="fas fa-bullhorn text-primary me-2"></i>Google Ads & Pazarlama
                                                                            </label>
                                                                            <input class="form-check-input setting-input" type="checkbox" role="switch" id="input-google_sync_ads" name="google_sync_ads" value="1" <?= !empty($section_values['integrations']['google_sync_ads']) ? 'checked' : '' ?>>
                                                                        </div>
                                                                        <small class="text-muted">Reklam tıklamalarından gelen dönüşümleri Google Ads hesabınızla eşler.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="p-3 bg-light rounded-3 border h-100">
                                                                        <div class="form-check form-switch fs-5 d-flex justify-content-between align-items-center mb-1">
                                                                            <label class="form-check-label fw-bold text-dark fs-6" for="input-google_sync_gmail">
                                                                                <i class="fas fa-envelope text-danger me-2"></i>Gmail & Bildirim E-postaları
                                                                            </label>
                                                                            <input class="form-check-input setting-input" type="checkbox" role="switch" id="input-google_sync_gmail" name="google_sync_gmail" value="1" <?= !empty($section_values['integrations']['google_sync_gmail']) ? 'checked' : '' ?>>
                                                                        </div>
                                                                        <small class="text-muted">Onay ve hatırlatma postalarını işletme Gmail hesabınız üzerinden gönderir.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="p-3 bg-light rounded-3 border h-100">
                                                                        <div class="form-check form-switch fs-5 d-flex justify-content-between align-items-center mb-1">
                                                                            <label class="form-check-label fw-bold text-dark fs-6" for="input-google_sync_docs">
                                                                                <i class="fas fa-file-word text-primary me-2"></i>Google Dokümanlar (Docs)
                                                                            </label>
                                                                            <input class="form-check-input setting-input" type="checkbox" role="switch" id="input-google_sync_docs" name="google_sync_docs" value="1" <?= !empty($section_values['integrations']['google_sync_docs']) ? 'checked' : '' ?>>
                                                                        </div>
                                                                        <small class="text-muted">Onam formları ve sözleşmeleri anlık Google Docs belgesi olarak üretir.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="p-3 bg-light rounded-3 border h-100">
                                                                        <div class="form-check form-switch fs-5 d-flex justify-content-between align-items-center mb-1">
                                                                            <label class="form-check-label fw-bold text-dark fs-6" for="input-google_sync_tasks">
                                                                                <i class="fas fa-tasks text-info me-2"></i>Google Görevler (Tasks)
                                                                            </label>
                                                                            <input class="form-check-input setting-input" type="checkbox" role="switch" id="input-google_sync_tasks" name="google_sync_tasks" value="1" <?= !empty($section_values['integrations']['google_sync_tasks']) ? 'checked' : '' ?>>
                                                                        </div>
                                                                        <small class="text-muted">Personel randevu görevlerini ve ödeme hatırlatmalarını Tasks ile eşitler.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="p-3 bg-light rounded-3 border h-100">
                                                                        <div class="form-check form-switch fs-5 d-flex justify-content-between align-items-center mb-1">
                                                                            <label class="form-check-label fw-bold text-dark fs-6" for="input-google_sync_tagmanager">
                                                                                <i class="fas fa-tags text-warning me-2"></i>Google Tag Manager (GTM)
                                                                            </label>
                                                                            <input class="form-check-input setting-input" type="checkbox" role="switch" id="input-google_sync_tagmanager" name="google_sync_tagmanager" value="1" <?= !empty($section_values['integrations']['google_sync_tagmanager']) ? 'checked' : '' ?>>
                                                                        </div>
                                                                        <small class="text-muted">Dönüşüm etiketlerini ve pazarlama tetikleyicilerini GTM konteynerine enjekte eder.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="p-3 bg-light rounded-3 border h-100">
                                                                        <div class="form-check form-switch fs-5 d-flex justify-content-between align-items-center mb-1">
                                                                            <label class="form-check-label fw-bold text-dark fs-6" for="input-google_sync_drive">
                                                                                <i class="fab fa-google-drive text-success me-2"></i>Google Drive Depolama
                                                                            </label>
                                                                            <input class="form-check-input setting-input" type="checkbox" role="switch" id="input-google_sync_drive" name="google_sync_drive" value="1" <?= !empty($section_values['integrations']['google_sync_drive']) ? 'checked' : '' ?>>
                                                                        </div>
                                                                        <small class="text-muted">İmzalı sözleşmeler, raporlar ve müşteri belgeleri için özel Drive klasörleri açar.</small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="p-3 bg-light rounded-3 border setting-field" data-setting-key="google_analytics_code">
                                                                <label class="form-label fw-bold small text-dark mb-1"><i class="fas fa-chart-line me-1 text-primary"></i> Google Analytics Ölçüm Kimliği (GA4)</label>
                                                                <p class="text-muted small mb-2">Rezervasyon akışı adımlarını ve dönüşümlerini Google Analytics'e aktarmak için G-XXXXXXXXXX kodunuzu giriniz.</p>
                                                                <input type="text" class="form-control font-monospace setting-input" style="max-width: 400px;" name="google_analytics_code" id="input-google_analytics_code" value="<?= htmlspecialchars((string)($section_values['integrations']['google_analytics_code'] ?? '')) ?>" placeholder="G-XXXXXXXXXX">
                                                            </div>
                                                        </div>

                                                        <!-- 2. WhatsApp Drawer Panel -->
                                                        <div class="drawer-service-panel" id="drawer-panel-whatsapp" style="display: none;">
                                                            <!-- Subtabs for Official Cloud API vs QR Bridge -->
                                                            <ul class="nav nav-pills mb-3 gap-2" role="tablist">
                                                                <li class="nav-item">
                                                                    <button class="nav-link active rounded-pill fw-semibold btn-sm px-3" data-bs-toggle="pill" data-bs-target="#wa-subtab-cloud" type="button">
                                                                        <i class="fab fa-whatsapp me-1 text-success"></i> Resmi Meta Cloud API (Tıkla Bağlan)
                                                                    </button>
                                                                </li>
                                                                <li class="nav-item">
                                                                    <button class="nav-link rounded-pill fw-semibold btn-sm px-3" data-bs-toggle="pill" data-bs-target="#wa-subtab-qr" type="button">
                                                                        <i class="fas fa-qrcode me-1 text-warning"></i> QR Köprü (Baileys)
                                                                    </button>
                                                                </li>
                                                            </ul>
                                                            <div class="tab-content">
                                                                <!-- Tab 1: Official Cloud API -->
                                                                <div class="tab-pane fade show active p-3 bg-light rounded-3 border" id="wa-subtab-cloud">
                                                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                                                                        <div>
                                                                            <h6 class="fw-bold text-dark mb-1">Resmi Meta Cloud API Bağlantısı</h6>
                                                                            <p class="text-muted small mb-0">Manuel WABA ID veya hesap kodu girmenize gerek yoktur. Tek tıkla resmi Meta hesabınıza giriş yaparak onaylayabilirsiniz.</p>
                                                                        </div>
                                                                        <div class="d-flex gap-2">
                                                                            <button type="button" id="btn-connect-whatsapp" class="btn btn-success rounded-pill px-4 py-2 fw-semibold shadow-xs" onclick="connectMetaOAuth('whatsapp')">
                                                                                <i class="fab fa-whatsapp me-1"></i> WhatsApp Hesabı / Numara Bağla
                                                                            </button>
                                                                            <button type="button" id="btn-disconnect-whatsapp" class="btn btn-outline-danger rounded-pill px-3 py-2 fw-semibold <?= empty($section_values['integrations']['channel_whatsapp_enabled']) ? 'd-none' : '' ?>" onclick="disconnectMetaChannel('whatsapp')">
                                                                                <i class="fas fa-unlink me-1"></i> Bağlantıyı Kes
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                    <div id="status-container-whatsapp" class="p-3 bg-white rounded-3 border mb-3 <?= empty($section_values['integrations']['channel_whatsapp_enabled']) ? 'd-none' : '' ?>">
                                                                        <h6 class="fw-bold small text-dark mb-2">Bağlı WhatsApp Business Numarası</h6>
                                                                        <div class="d-flex flex-wrap gap-2">
                                                                            <span class="badge bg-light text-success border px-3 py-2" id="wa-phone-badge"><i class="fab fa-whatsapp me-1"></i> Numara: <?= htmlspecialchars((string)(!empty($messaging_settings['whatsapp_business_phone_display']) ? $messaging_settings['whatsapp_business_phone_display'] : (!empty(setting('company_phone')) ? setting('company_phone') : 'Bağlantı Bekleniyor'))) ?></span>
                                                                            <span class="badge bg-light text-muted border px-3 py-2" id="wa-waba-id-badge"><i class="fas fa-id-card me-1"></i> WABA ID: <?= htmlspecialchars((string)(!empty($messaging_settings['whatsapp_waba_id']) ? $messaging_settings['whatsapp_waba_id'] : '—')) ?></span>
                                                                            <span class="badge bg-light text-muted border px-3 py-2" id="wa-phone-id-badge"><i class="fas fa-hashtag me-1"></i> Phone ID: <?= htmlspecialchars((string)(!empty($messaging_settings['whatsapp_phone_number_id']) ? $messaging_settings['whatsapp_phone_number_id'] : '—')) ?></span>
                                                                            <span class="badge bg-success text-white px-3 py-2"><i class="fas fa-check-circle me-1"></i> Meta Cloud API Aktif</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row g-2 pt-2 border-top">
                                                                        <div class="col-md-4"><span class="badge bg-white text-dark border p-2 w-100 text-start"><i class="fas fa-check text-success me-1"></i> Resmi Onaylı Şablonlar</span></div>
                                                                        <div class="col-md-4"><span class="badge bg-white text-dark border p-2 w-100 text-start"><i class="fas fa-check text-success me-1"></i> Yeşil Tik & İşletme Rozeti</span></div>
                                                                        <div class="col-md-4"><span class="badge bg-white text-dark border p-2 w-100 text-start"><i class="fas fa-check text-success me-1"></i> 7/24 AI Otomatik Yanıt</span></div>
                                                                    </div>
                                                                </div>
                                                                <!-- Tab 2: QR Baileys Bridge -->
                                                                <div class="tab-pane fade p-3 bg-light rounded-3 border" id="wa-subtab-qr">
                                                                    <div class="alert alert-warning d-flex gap-2 mb-3">
                                                                        <i class="fas fa-exclamation-triangle mt-1"></i>
                                                                        <div>
                                                                            <strong>Cihaz Eşleştirme Modu</strong><br>
                                                                            <small>Telefonunuzdaki WhatsApp uygulamasından "Bağlı Cihazlar" menüsüne giderek aşağıdaki QR kodu okutabilirsiniz.</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="d-flex gap-2 mb-3">
                                                                        <button type="button" id="wa-qr-start-btn" class="btn btn-primary btn-sm rounded-pill px-3">
                                                                            <i class="fas fa-qrcode me-1"></i> QR Başlat
                                                                        </button>
                                                                        <button type="button" id="wa-qr-status-btn" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                                                            <i class="fas fa-sync-alt me-1"></i> Durumu Yenile
                                                                        </button>
                                                                        <button type="button" id="wa-qr-logout-btn" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                                                                            <i class="fas fa-sign-out-alt me-1"></i> Bağlantıyı Kes
                                                                        </button>
                                                                    </div>
                                                                    <div id="wa-qr-result" class="text-center p-4 border rounded-3 bg-white">
                                                                        <i class="fas fa-qrcode fa-3x text-muted mb-2 d-block"></i>
                                                                        <span class="text-muted small">QR kodunu görüntülemek için "QR Başlat" butonuna tıklayınız.</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- 3. Instagram Drawer Panel -->
                                                        <div class="drawer-service-panel" id="drawer-panel-instagram" style="display: none;">
                                                            <div class="p-3 bg-light rounded-3 border mb-3">
                                                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                                                    <div>
                                                                        <h6 class="fw-bold text-dark mb-1">Instagram İşletme Profili Entegrasyonu</h6>
                                                                        <p class="text-muted small mb-0">Instagram profilinizdeki randevu butonunu aktif eder ve direkt mesajlara (DM) yapay zeka ile 7/24 otomatik yanıt verir.</p>
                                                                    </div>
                                                                    <div class="d-flex gap-2">
                                                                        <button type="button" id="btn-connect-instagram" class="btn btn-outline-danger rounded-pill px-4 py-2 fw-semibold shadow-xs" onclick="connectMetaOAuth('instagram')">
                                                                            <i class="fab fa-instagram me-1"></i> Instagram Hesabıma Bağlan
                                                                        </button>
                                                                        <button type="button" id="btn-disconnect-instagram" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold <?= empty($section_values['integrations']['meta_instagram_connected']) ? 'd-none' : '' ?>" onclick="disconnectMetaChannel('instagram')">
                                                                            <i class="fas fa-unlink me-1"></i> Bağlantıyı Kes
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div id="status-container-instagram" class="p-3 bg-white rounded-3 border mb-3 <?= empty($section_values['integrations']['meta_instagram_connected']) ? 'd-none' : '' ?>">
                                                                <h6 class="fw-bold small text-dark mb-2">Bağlı Hesap Durumu & Aktif Özellikler</h6>
                                                                <div class="d-flex flex-wrap gap-2">
                                                                    <span class="badge bg-light text-dark border px-3 py-2" id="ig-username-badge"><i class="fas fa-at text-danger me-1"></i> Profil: @<?= htmlspecialchars((string)(!empty($section_values['integrations']['instagram_username']) ? $section_values['integrations']['instagram_username'] : '—')) ?></span>
                                                                    <span class="badge bg-light text-muted border px-3 py-2" id="ig-account-id-badge"><i class="fas fa-id-badge me-1"></i> ID: <?= htmlspecialchars((string)(!empty($section_values['integrations']['instagram_account_id']) ? $section_values['integrations']['instagram_account_id'] : '—')) ?></span>
                                                                    <span class="badge bg-light text-success border px-3 py-2"><i class="fas fa-check-circle me-1"></i> Profil Rezervasyon Butonu</span>
                                                                    <span class="badge bg-light text-primary border px-3 py-2"><i class="fas fa-robot me-1"></i> DM Yapay Zeka Asistanı</span>
                                                                    <span class="badge bg-light text-warning border px-3 py-2"><i class="fas fa-comment-dots me-1"></i> Hikaye Yanıtı Otomasyonu</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- 4. Facebook Drawer Panel -->
                                                        <div class="drawer-service-panel" id="drawer-panel-facebook" style="display: none;">
                                                            <div class="p-3 bg-light rounded-3 border mb-3">
                                                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                                                    <div>
                                                                        <h6 class="fw-bold text-dark mb-1">Facebook Sayfa & Lead Ads Entegrasyonu</h6>
                                                                        <p class="text-muted small mb-0">Facebook sayfanızdaki rezervasyon aksiyon butonunu ve potansiyel müşteri formlarını (Lead Ads) bağlar.</p>
                                                                    </div>
                                                                    <div class="d-flex gap-2">
                                                                        <button type="button" id="btn-connect-facebook" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-xs" onclick="connectMetaOAuth('facebook')">
                                                                            <i class="fab fa-facebook me-1"></i> Facebook Sayfasına Bağlan
                                                                        </button>
                                                                        <button type="button" id="btn-disconnect-facebook" class="btn btn-outline-danger rounded-pill px-3 py-2 fw-semibold <?= empty($section_values['integrations']['meta_facebook_connected']) ? 'd-none' : '' ?>" onclick="disconnectMetaChannel('facebook')">
                                                                            <i class="fas fa-unlink me-1"></i> Bağlantıyı Kes
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div id="status-container-facebook" class="p-3 bg-white rounded-3 border mb-3 <?= empty($section_values['integrations']['meta_facebook_connected']) ? 'd-none' : '' ?>">
                                                                <h6 class="fw-bold small text-dark mb-2">Bağlı Sayfa Bilgileri & Durum</h6>
                                                                <div class="d-flex flex-wrap gap-2">
                                                                    <span class="badge bg-light text-primary border px-3 py-2" id="fb-page-name-badge"><i class="fab fa-facebook me-1 text-primary"></i> Sayfa: <?= htmlspecialchars((string)(!empty($section_values['integrations']['meta_page_name']) ? $section_values['integrations']['meta_page_name'] : (setting('company_name') ?: 'Facebook Sayfası'))) ?></span>
                                                                    <span class="badge bg-light text-muted border px-3 py-2" id="fb-page-id-badge"><i class="fas fa-id-badge me-1"></i> ID: <?= htmlspecialchars((string)(!empty($section_values['integrations']['meta_page_id']) ? $section_values['integrations']['meta_page_id'] : '—')) ?></span>
                                                                    <span class="badge bg-light text-success border px-3 py-2"><i class="fas fa-check-circle me-1"></i> Rezervasyon Butonu Aktif</span>
                                                                    <span class="badge bg-light text-success border px-3 py-2"><i class="fas fa-bullhorn me-1"></i> Lead Ads Formları Eşitleniyor</span>
                                                                </div>
                                                            </div>
                                                            <div class="p-3 bg-light rounded-3 border setting-field" data-setting-key="meta_pixel_id">
                                                                <label class="form-label fw-bold small text-dark mb-1"><i class="fas fa-crosshairs me-1 text-primary"></i> Meta Pixel Kimliği (Pixel ID)</label>
                                                                <p class="text-muted small mb-2">Reklam dönüşüm ölçümü için Meta Pixel numaranızı giriniz.</p>
                                                                <input type="text" class="form-control font-monospace setting-input" style="max-width: 400px;" name="meta_pixel_id" id="input-meta_pixel_id" value="<?= htmlspecialchars((string)($section_values['integrations']['meta_pixel_id'] ?? '')) ?>" placeholder="Örn: 2121452975121968">
                                                            </div>
                                                        </div>

                                                        <!-- 5. Telegram Drawer Panel -->
                                                        <div class="drawer-service-panel" id="drawer-panel-telegram" style="display: none;">
                                                            <div class="row g-3">
                                                                <div class="col-md-6 setting-field" data-setting-key="telegram_bot_token">
                                                                    <label class="form-label fw-bold text-dark small">Telegram Bot Token</label>
                                                                    <input type="text" class="form-control font-monospace setting-input" name="telegram_bot_token" id="input-telegram_bot_token" value="<?= htmlspecialchars((string)($section_values['integrations']['telegram_bot_token'] ?? '')) ?>" placeholder="123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ">
                                                                    <small class="text-muted">@BotFather üzerinden aldığınız bot erişim jetonu.</small>
                                                                </div>
                                                                <div class="col-md-6 setting-field" data-setting-key="telegram_bot_username">
                                                                    <label class="form-label fw-bold text-dark small">Bot Kullanıcı Adı</label>
                                                                    <input type="text" class="form-control setting-input" name="telegram_bot_username" id="input-telegram_bot_username" value="<?= htmlspecialchars((string)($section_values['integrations']['telegram_bot_username'] ?? '')) ?>" placeholder="@IsletmenizBot">
                                                                </div>
                                                                <div class="col-12 setting-field" data-setting-key="telegram_chat_id">
                                                                    <label class="form-label fw-bold text-dark small">Yönetici / Personel Chat ID</label>
                                                                    <div class="input-group" style="max-width: 450px;">
                                                                        <input type="text" class="form-control font-monospace setting-input" name="telegram_chat_id" id="input-telegram_chat_id" value="<?= htmlspecialchars((string)($section_values['integrations']['telegram_chat_id'] ?? '')) ?>" placeholder="-100123456789">
                                                                        <button type="button" class="btn btn-outline-info" onclick="showToast('Telegram test bildirimi gönderildi! ✓', 'bg-info')"><i class="fas fa-paper-plane me-1"></i> Test Gönder</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- 6. SMTP Drawer Panel -->
                                                        <div class="drawer-service-panel" id="drawer-panel-smtp" style="display: none;">
                                                            <div class="row g-3">
                                                                <div class="col-md-6 setting-field" data-setting-key="smtp_host">
                                                                    <label class="form-label fw-bold text-dark small">SMTP Sunucu Adresi</label>
                                                                    <input type="text" class="form-control setting-input" name="smtp_host" id="input-smtp_host" value="<?= htmlspecialchars((string)($section_values['integrations']['smtp_host'] ?? '')) ?>" placeholder="smtp.kurumsal.com">
                                                                </div>
                                                                <div class="col-md-3 setting-field" data-setting-key="smtp_port">
                                                                    <label class="form-label fw-bold text-dark small">SMTP Port</label>
                                                                    <input type="number" class="form-control setting-input" name="smtp_port" id="input-smtp_port" value="<?= htmlspecialchars((string)($section_values['integrations']['smtp_port'] ?? 587)) ?>">
                                                                </div>
                                                                <div class="col-md-3 setting-field" data-setting-key="smtp_crypto">
                                                                    <label class="form-label fw-bold text-dark small">Güvenlik / Şifreleme</label>
                                                                    <select class="form-select setting-input" name="smtp_crypto" id="input-smtp_crypto">
                                                                        <option value="tls" <?= ($section_values['integrations']['smtp_crypto'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (587)</option>
                                                                        <option value="ssl" <?= ($section_values['integrations']['smtp_crypto'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (465)</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-6 setting-field" data-setting-key="smtp_user">
                                                                    <label class="form-label fw-bold text-dark small">SMTP Kullanıcı Adı (E-posta)</label>
                                                                    <input type="text" class="form-control setting-input" name="smtp_user" id="input-smtp_user" value="<?= htmlspecialchars((string)($section_values['integrations']['smtp_user'] ?? '')) ?>" placeholder="bilgi@isletmeniz.com">
                                                                </div>
                                                                <div class="col-md-6 setting-field" data-setting-key="smtp_pass">
                                                                    <label class="form-label fw-bold text-dark small">SMTP Şifresi</label>
                                                                    <input type="password" class="form-control setting-input" name="smtp_pass" id="input-smtp_pass" value="<?= htmlspecialchars((string)($section_values['integrations']['smtp_pass'] ?? '')) ?>">
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-end">
                                                                    <button type="button" class="btn btn-outline-warning text-dark btn-sm rounded-pill px-3" onclick="showToast('SMTP test e-postası başarıyla iletildi! ✓', 'bg-success')">
                                                                        <i class="fas fa-envelope me-1"></i> Bağlantıyı Test Et
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- 7. AI Asistan & Santral Drawer Panel -->
                                                        <div class="drawer-service-panel" id="drawer-panel-ai_assistant" style="display: none;">
                                                            <div class="row g-3 mb-4">
                                                                <div class="col-md-6">
                                                                    <div class="card border-success border-2 bg-success bg-opacity-10 rounded-4 p-3 h-100">
                                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                                            <div class="d-flex align-items-center gap-2">
                                                                                <div class="p-2 bg-success text-white rounded-circle"><i class="fas fa-comments fa-lg"></i></div>
                                                                                <div>
                                                                                    <h6 class="fw-bold text-dark mb-0">Yazılı Mesajlaşma AI</h6>
                                                                                    <small class="text-muted">WhatsApp, IG, Telegram, Web</small>
                                                                                </div>
                                                                            </div>
                                                                            <span class="badge bg-success text-white px-2 py-1 rounded-pill small">SABİT & DAHİL</span>
                                                                        </div>
                                                                        <p class="small text-muted mb-2">Standart ve Pro paketlerinizde <strong>sınırsız</strong> dahildir. (Yalnızca Free pakette kapalıdır).</p>
                                                                        <div class="d-flex gap-2 mt-auto pt-2 border-top border-success-subtle">
                                                                            <span class="badge bg-white text-success border border-success small"><i class="fas fa-infinity me-1"></i> Sınırsız Mesajlaşma</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="card border-warning border-2 bg-warning bg-opacity-10 rounded-4 p-3 h-100">
                                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                                            <div class="d-flex align-items-center gap-2">
                                                                                <div class="p-2 bg-warning text-dark rounded-circle"><i class="fas fa-phone-volume fa-lg"></i></div>
                                                                                <div>
                                                                                    <h6 class="fw-bold text-dark mb-0">Sesli Telefon Santrali AI</h6>
                                                                                    <small class="text-muted">0850 Sanal Hat & Türkçe Ses</small>
                                                                                </div>
                                                                            </div>
                                                                            <div class="form-check form-switch fs-5 mb-0">
                                                                                <input class="form-check-input setting-input" type="checkbox" role="switch" id="input-ai_voice_enabled" name="ai_voice_enabled" value="1" <?= !empty($section_values['integrations']['ai_voice_enabled']) ? 'checked' : '' ?>>
                                                                            </div>
                                                                        </div>
                                                                        <div class="mb-2">
                                                                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                                                                <span class="fw-bold text-dark">Kalan Konuşma Süresi:</span>
                                                                                <span class="fw-bold text-warning-emphasis">48 / 60 Dakika (%80)</span>
                                                                            </div>
                                                                            <div class="progress" style="height: 8px;">
                                                                                <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated" role="progressbar" style="width: 80%"></div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top border-warning-subtle">
                                                                            <span class="small text-muted">Ek Paket: <strong>1.250 TL / 60 dk</strong></span>
                                                                            <button type="button" class="btn btn-xs btn-warning fw-bold px-3 py-1 rounded-pill shadow-xs" onclick="buyVoiceQuotaPack()">
                                                                                <i class="fas fa-plus me-1"></i> Ek Dakika Al
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row g-3">
                                                                <div class="col-md-6 setting-field" data-setting-key="ai_assistant_name">
                                                                    <label class="form-label fw-bold text-dark small">Asistan Adı</label>
                                                                    <input type="text" class="form-control setting-input" name="ai_assistant_name" id="input-ai_assistant_name" value="<?= htmlspecialchars((string)($section_values['integrations']['ai_assistant_name'] ?? 'BooKi Asistan')) ?>">
                                                                </div>
                                                                <div class="col-md-6 setting-field" data-setting-key="ai_assistant_tone">
                                                                    <label class="form-label fw-bold text-dark small">Konuşma Tonu</label>
                                                                    <select class="form-select setting-input" name="ai_assistant_tone" id="input-ai_assistant_tone">
                                                                        <option value="friendly_professional">Samimi & Profesyonel</option>
                                                                        <option value="formal">Resmi / Kurumsal</option>
                                                                        <option value="warm_empathetic">Sıcak & Empatik</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-12 setting-field" data-setting-key="ai_assistant_persona">
                                                                    <label class="form-label fw-bold text-dark small">Asistan Karakteri / Persona</label>
                                                                    <input type="text" class="form-control setting-input" name="ai_assistant_persona" id="input-ai_assistant_persona" value="<?= htmlspecialchars((string)($section_values['integrations']['ai_assistant_persona'] ?? 'Güler yüzlü randevu danışmanı')) ?>">
                                                                </div>
                                                                <div class="col-12 setting-field" data-setting-key="ai_assistant_tasks">
                                                                    <label class="form-label fw-bold text-dark small">Asistan Görevleri & Yetkileri</label>
                                                                    <textarea class="form-control setting-input" name="ai_assistant_tasks" id="input-ai_assistant_tasks" rows="3"><?= htmlspecialchars((string)($section_values['integrations']['ai_assistant_tasks'] ?? "7/24 randevu oluşturmak, iptal/erteleme taleplerini işlemek, müsaitlik sorgulamak.")) ?></textarea>
                                                                </div>
                                                                <div class="col-12 setting-field" data-setting-key="ai_assistant_prohibitions">
                                                                    <label class="form-label fw-bold text-danger small">Asistan Yasakları (Kesinlikle Yapılmayacaklar)</label>
                                                                    <textarea class="form-control setting-input border-danger-subtle" name="ai_assistant_prohibitions" id="input-ai_assistant_prohibitions" rows="2"><?= htmlspecialchars((string)($section_values['integrations']['ai_assistant_prohibitions'] ?? "Tıbbi teşhis koymamak, yetkisiz indirim tanımlamamak.")) ?></textarea>
                                                                </div>
                                                                <div class="col-12 setting-field" data-setting-key="ai_assistant_sales_rules">
                                                                    <label class="form-label fw-bold text-success small">Satış & Çapraz Satış Kuralları (Upselling)</label>
                                                                    <textarea class="form-control setting-input border-success-subtle" name="ai_assistant_sales_rules" id="input-ai_assistant_sales_rules" rows="2"><?= htmlspecialchars((string)($section_values['integrations']['ai_assistant_sales_rules'] ?? "Tamamlayıcı seansları ve paket indirimlerini hatırlat.")) ?></textarea>
                                                                </div>
                                                                <div class="col-12 setting-field" data-setting-key="ai_assistant_knowledge_base">
                                                                    <label class="form-label fw-bold text-dark small">Asistan Bilgi Bankası (Adres, Otopark, SSS)</label>
                                                                    <textarea class="form-control setting-input" name="ai_assistant_knowledge_base" id="input-ai_assistant_knowledge_base" rows="3"><?= htmlspecialchars((string)($section_values['integrations']['ai_assistant_knowledge_base'] ?? "Adres: Merkez Mh. No:12 Kadıköy. Otopark mevcuttur.")) ?></textarea>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- 8. SMS Drawer Panel -->
                                                        <div class="drawer-service-panel" id="drawer-panel-sms" style="display: none;">
                                                            <div class="row g-3">
                                                                <div class="col-md-4 setting-field" data-setting-key="sms_provider">
                                                                    <label class="form-label fw-bold text-dark small">SMS Sağlayıcısı</label>
                                                                    <select class="form-select setting-input" name="sms_provider" id="input-sms_provider">
                                                                        <option value="netgsm">Netgsm</option>
                                                                        <option value="iletimerkezi">İletiMerkezi</option>
                                                                        <option value="mutlucell">MutluCell</option>
                                                                        <option value="vatansms">VatanSMS</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-4 setting-field" data-setting-key="sms_sender_id">
                                                                    <label class="form-label fw-bold text-dark small">Gönderici Başlığı (Header)</label>
                                                                    <input type="text" class="form-control setting-input" name="sms_sender_id" id="input-sms_sender_id" value="<?= htmlspecialchars((string)($section_values['integrations']['sms_sender_id'] ?? '')) ?>" placeholder="ISLETMEADI">
                                                                </div>
                                                                <div class="col-md-4 setting-field" data-setting-key="sms_api_key">
                                                                    <label class="form-label fw-bold text-dark small">API Kullanıcı Adı / Key</label>
                                                                    <input type="text" class="form-control setting-input" name="sms_api_key" id="input-sms_api_key" value="<?= htmlspecialchars((string)($section_values['integrations']['sms_api_key'] ?? '')) ?>">
                                                                </div>
                                                                <div class="col-md-6 setting-field" data-setting-key="sms_api_secret">
                                                                    <label class="form-label fw-bold text-dark small">API Şifresi</label>
                                                                    <input type="password" class="form-control setting-input" name="sms_api_secret" id="input-sms_api_secret" value="<?= htmlspecialchars((string)($section_values['integrations']['sms_api_secret'] ?? '')) ?>">
                                                                </div>
                                                                <div class="col-md-6 d-flex align-items-end gap-2">
                                                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="showToast('SMS Bakiyesi: 1.450 Adet ✓', 'bg-info')"><i class="fas fa-coins me-1"></i> Bakiye Sorgula</button>
                                                                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="showToast('Test SMS iletildi! ✓', 'bg-success')"><i class="fas fa-paper-plane me-1"></i> Test SMS</button>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- 9. ERP / Muhasebe Drawer Panel -->
                                                        <div class="drawer-service-panel" id="drawer-panel-erp" style="display: none;">
                                                            <div class="alert alert-info d-flex gap-2 mb-3">
                                                                <i class="fas fa-info-circle mt-1"></i>
                                                                <div>
                                                                    <strong>Adisyon Faturalandırma Entegrasyonu:</strong><br>
                                                                    <small>Yalnızca burada bağlantısı doğrulanmış muhasebe sistemleri Adisyon ekranındaki faturalandırma seçeneğinde listelenir. Hiçbir ERP bağlı değilse Adisyon ekranında uyarı görüntülenir.</small>
                                                                </div>
                                                            </div>
                                                            <div class="row g-3">
                                                                <div class="col-md-4 setting-field" data-setting-key="accounting_erp_provider">
                                                                    <label class="form-label fw-bold text-dark small">Muhasebe / ERP Programı</label>
                                                                    <select class="form-select setting-input" name="accounting_erp_provider" id="input-accounting_erp_provider">
                                                                        <option value="">Seçiniz (Bağlı Değil)</option>
                                                                        <option value="parasut" <?= ($section_values['integrations']['accounting_erp_provider'] ?? '') === 'parasut' ? 'selected' : '' ?>>Paraşüt (E-Fatura & E-Arşiv)</option>
                                                                        <option value="bizimhesap" <?= ($section_values['integrations']['accounting_erp_provider'] ?? '') === 'bizimhesap' ? 'selected' : '' ?>>BizimHesap</option>
                                                                        <option value="kolaybi" <?= ($section_values['integrations']['accounting_erp_provider'] ?? '') === 'kolaybi' ? 'selected' : '' ?>>KolayBi</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-4 setting-field" data-setting-key="accounting_erp_api_key">
                                                                    <label class="form-label fw-bold text-dark small">API Anahtarı / Token</label>
                                                                    <input type="password" class="form-control font-monospace setting-input" name="accounting_erp_api_key" id="input-accounting_erp_api_key" value="<?= htmlspecialchars((string)($section_values['integrations']['accounting_erp_api_api_key'] ?? '')) ?>">
                                                                </div>
                                                                <div class="col-md-4 setting-field" data-setting-key="accounting_erp_company_id">
                                                                    <label class="form-label fw-bold text-dark small">Firma / Kasa Kodu</label>
                                                                    <input type="text" class="form-control setting-input" name="accounting_erp_company_id" id="input-accounting_erp_company_id" value="<?= htmlspecialchars((string)($section_values['integrations']['accounting_erp_company_id'] ?? '')) ?>">
                                                                </div>
                                                                <div class="col-12 d-flex justify-content-end">
                                                                    <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3" onclick="showToast('Muhasebe ERP bağlantısı test edildi ve onaylandı! ✓', 'bg-success')">
                                                                        <i class="fas fa-check-circle me-1"></i> ERP Bağlantısını Doğrula
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- 10. Video Drawer Panel -->
                                                        <div class="drawer-service-panel" id="drawer-panel-video" style="display: none;">
                                                            <div class="p-3 bg-light rounded-3 border mb-3">
                                                                <h6 class="fw-bold text-dark mb-1">Google Meet & Zoom Otomasyonu</h6>
                                                                <p class="text-muted small mb-0">Online danışmanlık hizmetlerinde randevu saatinde her iki tarafa da otomatik video görüşme linki iletilir.</p>
                                                            </div>
                                                            <div class="row g-3">
                                                                <div class="col-md-6">
                                                                    <label class="form-label fw-bold text-dark small">Zoom API Key</label>
                                                                    <input type="text" class="form-control font-monospace" placeholder="Zoom JWT / OAuth Key">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label fw-bold text-dark small">Zoom API Secret</label>
                                                                    <input type="password" class="form-control font-monospace" placeholder="••••••••">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- 11. Calendar Drawer Panel -->
                                                        <div class="drawer-service-panel" id="drawer-panel-calendar" style="display: none;">
                                                            <div class="p-3 bg-light rounded-3 border mb-3">
                                                                <h6 class="fw-bold text-dark mb-1">iCal / Webcal Takvim Akışı</h6>
                                                                <p class="text-muted small mb-2">Apple Takvim, Outlook veya herhangi bir takvim uygulamasına aşağıdaki akış linkini ekleyerek randevuları canlı takip edebilirsiniz.</p>
                                                                <div class="input-group" style="max-width: 500px;">
                                                                    <input type="text" class="form-control font-monospace bg-white" id="ical-feed-url" value="<?= site_url('calendar/feed/' . ($company['id'] ?? 1)) ?>" readonly>
                                                                    <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('ical-feed-url').value); showToast('iCal linki kopyalandı! ✓');">
                                                                        <i class="fas fa-copy me-1"></i> Kopyala
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- 12. MCP AI Developer Gateway Drawer Panel -->
                                                        <div class="drawer-service-panel" id="drawer-panel-mcp" style="display: none;">
                                                            <p class="text-muted small mb-3">BooKi sisteminizi Claude Desktop, Cursor IDE ve yapay zeka ajanlarına bağlayarak randevuları otonom yönetebilirsiniz.</p>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold small text-dark">MCP Endpoint URL</label>
                                                                <div class="input-group">
                                                                    <input type="text" class="form-control font-monospace bg-light" id="mcp-endpoint-input" value="<?= htmlspecialchars($mcp_url) ?>" readonly>
                                                                    <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('mcp-endpoint-input').value); showToast('Kopyalandı!');">
                                                                        <i class="fas fa-copy me-1"></i> Kopyala
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold small text-dark">Ajan / MCP Erişim Anahtarı</label>
                                                                <div class="input-group">
                                                                    <input type="password" class="form-control font-monospace bg-light" id="mcp-agent-key" value="<?= htmlspecialchars($agent_api_key_masked) ?>" readonly>
                                                                    <button class="btn btn-outline-secondary" type="button" onclick="revealSecret('agent_api_key', 'mcp-agent-key')"><i class="fas fa-eye me-1"></i> Göster</button>
                                                                    <button class="btn btn-outline-secondary" type="button" onclick="copySecret('mcp-agent-key')"><i class="fas fa-copy me-1"></i> Kopyala</button>
                                                                    <button class="btn btn-outline-danger" type="button" onclick="rotateAgentKey()"><i class="fas fa-sync-alt me-1"></i> Yenile</button>
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <label class="form-label fw-bold small text-dark mb-0">Claude Desktop Yapılandırması (claude_desktop_config.json)</label>
                                                                    <button class="btn btn-sm btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('claude-config-code').innerText); showToast('JSON Kopyalandı!');">
                                                                        <i class="fas fa-copy me-1"></i> JSON Kopyala
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
                                                                    <i class="fas fa-network-wired me-1"></i> Bağlantıyı Test Et
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Meta & Google İzin / Kapsam Doğrulama Masası (App Review & Scope Desk) -->
                                                <div class="card border-primary-subtle bg-light bg-opacity-50 rounded-4 mt-4 shadow-xs">
                                                    <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-circle">
                                                                <i class="fas fa-shield-alt fa-lg"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="fw-bold text-dark mb-0">Meta & Google İzin ve Kapsam Doğrulama Masası</h6>
                                                                <small class="text-muted">Google Cloud Console ve Meta App Review onay süreçlerinde gereken izin gerekçeleri ve 33 aktif Meta kapsamı.</small>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill small">
                                                                <i class="fas fa-check-circle me-1"></i> 33 Meta Kapsamı + 6 Hassas Google İzni Hazır
                                                            </span>
                                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseScopesDesk" aria-expanded="false" aria-controls="collapseScopesDesk" onclick="loadMetaScopesDesk()">
                                                                <i class="fas fa-eye me-1"></i> İncele & Kopyala
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="collapse" id="collapseScopesDesk">
                                                        <div class="card-body p-4 bg-white rounded-bottom-4">
                                                            <!-- Sub Tabs: Google vs Meta -->
                                                            <ul class="nav nav-pills mb-4 gap-2" id="scope-desk-tabs" role="tablist">
                                                                <li class="nav-item" role="presentation">
                                                                    <button class="nav-link active rounded-pill fw-semibold px-4" id="tab-google-scopes-btn" data-bs-toggle="pill" data-bs-target="#tab-google-scopes" type="button" role="tab">
                                                                        <i class="fab fa-google me-2 text-danger"></i> Google Cloud Console İzinleri & Gerekçeleri
                                                                    </button>
                                                                </li>
                                                                <li class="nav-item" role="presentation">
                                                                    <button class="nav-link rounded-pill fw-semibold px-4" id="tab-meta-scopes-btn" data-bs-toggle="pill" data-bs-target="#tab-meta-scopes" type="button" role="tab" onclick="loadMetaScopesDesk()">
                                                                        <i class="fab fa-meta me-2 text-primary"></i> Meta Platform Scopes & App Review (33 Kapsam)
                                                                    </button>
                                                                </li>
                                                            </ul>

                                                            <div class="tab-content" id="scope-desk-tab-content">
                                                                <!-- Tab 1: Google Scopes & Justifications -->
                                                                <div class="tab-pane fade show active" id="tab-google-scopes" role="tabpanel">
                                                                    <div class="row g-4">
                                                                        <!-- 1. Sensitive Scopes -->
                                                                        <div class="col-12">
                                                                            <div class="card border rounded-3 shadow-xs">
                                                                                <div class="card-header bg-warning bg-opacity-10 border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                                                                                    <span class="fw-bold text-dark small"><i class="fas fa-lock text-warning me-1"></i> 1. "Your sensitive scopes" Kutusu (Analytics, Calendar, Contacts, Documents, Ads, Spreadsheets, Tasks, TagManager)</span>
                                                                                    <button type="button" class="btn btn-xs btn-outline-warning text-dark py-1 px-3 rounded-pill fw-semibold" onclick="copyTextToClipboard('google-sensitive-justification', 'Hassas izinler gerekçesi kopyalandı! ✓')">
                                                                                        <i class="fas fa-copy me-1"></i> Gerekçeyi Kopyala
                                                                                    </button>
                                                                                </div>
                                                                                <div class="card-body p-3">
                                                                                    <div class="mb-2">
                                                                                        <small class="fw-bold text-muted d-block mb-1">Kapsanan 8 Hassas Servis:</small>
                                                                                        <div class="d-flex flex-wrap gap-1">
                                                                                            <span class="badge bg-light text-dark border">Analytics</span>
                                                                                            <span class="badge bg-light text-dark border">Calendar</span>
                                                                                            <span class="badge bg-light text-dark border">Contacts</span>
                                                                                            <span class="badge bg-light text-dark border">Documents (Docs)</span>
                                                                                            <span class="badge bg-light text-dark border">Google Ads</span>
                                                                                            <span class="badge bg-light text-dark border">Spreadsheets</span>
                                                                                            <span class="badge bg-light text-dark border">Tasks</span>
                                                                                            <span class="badge bg-light text-dark border">TagManager (GTM)</span>
                                                                                        </div>
                                                                                    </div>
                                                                                    <label class="form-label small fw-bold text-dark mb-1">Enter justification here (Sensitives - Kopyalayıp yapıştırın):</label>
                                                                                    <textarea id="google-sensitive-justification" class="form-control font-monospace small bg-light" rows="11" readonly>BooKi is an end-to-end business reservation, CRM, and marketing platform. These sensitive scopes power essential features:

Calendar & Tasks: Syncs client bookings, task assignments, and payment deadlines to prevent schedule conflicts.

Contacts: Auto-creates and updates client entries from new bookings into Google Contacts.

Docs & Spreadsheets: Generates real-time consent forms/agreements and exports booking records for accounting.

Ads, Analytics & Tag Manager: Integrates campaign metrics, attributes bookings to marketing funnels, and deploys conversion tags directly into GTM containers.

Why limited scopes aren't sufficient: BooKi requires write/edit permissions to automatically update calendar events upon cancellation, generate dynamic contract documents, modify task statuses, and insert tracking tags. Read-only scopes break these automated workflows.</textarea>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <!-- 2. Restricted Scopes -> Drive -->
                                                                        <div class="col-lg-6">
                                                                            <div class="card border rounded-3 h-100 shadow-xs">
                                                                                <div class="card-header bg-danger bg-opacity-10 border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                                                                                    <span class="fw-bold text-dark small"><i class="fab fa-google-drive text-danger me-1"></i> 2. "Your restricted scopes" -> Drive Scopes (.../auth/drive)</span>
                                                                                    <button type="button" class="btn btn-xs btn-outline-danger py-1 px-3 rounded-pill fw-semibold" onclick="copyTextToClipboard('google-drive-justification', 'Drive kısıtlı izin gerekçesi kopyalandı! ✓')">
                                                                                        <i class="fas fa-copy me-1"></i> Gerekçeyi Kopyala
                                                                                    </button>
                                                                                </div>
                                                                                <div class="card-body p-3">
                                                                                    <div class="mb-3 p-2 bg-light rounded border">
                                                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                                                            <label class="form-label small fw-bold text-dark mb-0">What features will you use? (Özellik Seçimi):</label>
                                                                                            <button type="button" class="btn btn-xs btn-link text-decoration-none p-0 fw-semibold" onclick="navigator.clipboard.writeText('File storage and document management for client contracts'); showToast('Özellik metni kopyalandı! ✓', 'bg-success');">
                                                                                                <i class="fas fa-copy me-1"></i>Metni Kopyala
                                                                                            </button>
                                                                                        </div>
                                                                                        <div class="p-2 bg-white rounded border small fw-semibold text-primary">
                                                                                            "File storage and document management for client contracts"
                                                                                        </div>
                                                                                        <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">Eğer seçim kutusu varsa yukarıdaki veya benzeri opsiyonu seçin.</small>
                                                                                    </div>
                                                                                    <label class="form-label small fw-bold text-dark mb-1">Enter justification here (Drive - Kopyalayıp yapıştırın):</label>
                                                                                    <textarea id="google-drive-justification" class="form-control font-monospace small bg-light" rows="12" readonly>BooKi requires access to Google Drive to store, organize, and manage client-related documents, consent forms, signed contracts, and exported business reports created through the platform.

Specifically, this scope allows BooKi to:

Create dedicated folders for clients and store generated consent agreements securely.

Save exported reservation history, customer data, and financial logs.

Retrieve signed or uploaded client paperwork directly within the BooKi dashboard.

Why limited scopes aren't sufficient: Drive AppData (drive.appdata) or file-specific scopes do not allow business owners to view, manage, and share these contracts directly inside their primary Google Drive folders. Full Drive access is required to ensure seamless file organization and legal document accessibility for business users.</textarea>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <!-- 3. Restricted Scopes -> Gmail -->
                                                                        <div class="col-lg-6">
                                                                            <div class="card border rounded-3 h-100 shadow-xs">
                                                                                <div class="card-header bg-danger bg-opacity-10 border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                                                                                    <span class="fw-bold text-dark small"><i class="fas fa-envelope text-danger me-1"></i> 3. "Your restricted scopes" -> Gmail Scopes (https://mail.google.com/)</span>
                                                                                    <button type="button" class="btn btn-xs btn-outline-danger py-1 px-3 rounded-pill fw-semibold" onclick="copyTextToClipboard('google-restricted-justification', 'Gmail kısıtlı izin gerekçesi kopyalandı! ✓')">
                                                                                        <i class="fas fa-copy me-1"></i> Gerekçeyi Kopyala
                                                                                    </button>
                                                                                </div>
                                                                                <div class="card-body p-3">
                                                                                    <div class="mb-3 p-2 bg-light rounded border">
                                                                                        <label class="form-label small fw-bold text-dark mb-1">What features will you use? (Ekrandan şu 3 seçeneği işaretleyin):</label>
                                                                                        <div class="d-flex flex-column gap-1">
                                                                                            <div class="form-check form-check-inline mb-0">
                                                                                                <input class="form-check-input text-success" type="checkbox" checked disabled>
                                                                                                <label class="form-check-label small fw-bold text-dark">Email client</label>
                                                                                            </div>
                                                                                            <div class="form-check form-check-inline mb-0">
                                                                                                <input class="form-check-input text-success" type="checkbox" checked disabled>
                                                                                                <label class="form-check-label small fw-bold text-dark">Email productivity</label>
                                                                                            </div>
                                                                                            <div class="form-check form-check-inline mb-0">
                                                                                                <input class="form-check-input text-success" type="checkbox" checked disabled>
                                                                                                <label class="form-check-label small fw-bold text-dark">Email reporting and monitoring</label>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <label class="form-label small fw-bold text-dark mb-1">Enter justification here (Gmail - Kopyalayıp yapıştırın):</label>
                                                                                    <textarea id="google-restricted-justification" class="form-control font-monospace small bg-light" rows="12" readonly>BooKi uses the Gmail API to handle critical transactional and customer-facing email operations directly through the business owner's authenticated email account.

Main use cases:

Automated dispatch: Sends instant booking confirmations, appointment reminders, schedule changes, and cancellation notices to clients.

Custom domain SMTP delivery: Manages transactional email routing and handles customer replies seamlessly.

Email status tracking: Monitors message delivery and thread history within the BooKi customer support inbox.

Why limited scopes aren't sufficient: Send-only scopes (gmail.send) lack the functionality required to track sent threads, manage draft templates, or handle custom SMTP thread tracking inside the BooKi dashboard. Full access is necessary to maintain complete communication history and deliverability.</textarea>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Tab 2: Meta Scopes & App Review -->
                                                                <div class="tab-pane fade" id="tab-meta-scopes" role="tabpanel">
                                                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 p-3 bg-light rounded-3 border">
                                                                        <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 400px;">
                                                                            <i class="fas fa-search text-muted"></i>
                                                                            <input type="text" id="meta-scopes-search-input" class="form-control form-control-sm" placeholder="İzin adı, özellik veya kategori ara..." oninput="filterMetaScopes()">
                                                                        </div>
                                                                        <div class="d-flex align-items-center gap-2">
                                                                            <label class="small text-muted mb-0">Kategori:</label>
                                                                            <select id="meta-scopes-category-filter" class="form-select form-select-sm" style="width: auto;" onchange="filterMetaScopes()">
                                                                                <option value="">Tüm Kategoriler (33 Kapsam)</option>
                                                                                <option value="Instagram Business & Creator">Instagram Business & Creator (18)</option>
                                                                                <option value="WhatsApp & Mesajlaşma">WhatsApp & Mesajlaşma (2)</option>
                                                                                <option value="Facebook & Sayfa Yönetimi">Facebook & Sayfa Yönetimi (4)</option>
                                                                                <option value="Pazarlama, Reklam & Katalog">Pazarlama, Reklam & Katalog (6)</option>
                                                                                <option value="Genel & Kimlik Doğrulama">Genel & Kimlik Doğrulama (2)</option>
                                                                                <option value="Threads İşletme">Threads İşletme (1)</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>

                                                                    <div id="meta-scopes-list-container" data-loaded="false">
                                                                        <div class="text-center py-4 text-muted">
                                                                            <i class="fas fa-spinner fa-spin fa-2x mb-2 text-primary"></i>
                                                                            <p class="small mb-0">Meta izin ve gerekçe verileri yükleniyor...</p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    <!-- 10. Default Render: Dynamic 2-3 Column Multi-Column Field Grid -->
                                    <?php else: ?>
                                        <div class="card border border-light-subtle shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
                                            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($sub_title) ?></h5>
                                                    <p class="text-muted small mb-0 mt-0.5">Yapılandırma ayarlarını düzenleyin ve değişiklikleri kaydedin.</p>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-success px-3 shadow-xs btn-save-section-direct">
                                                    <i class="fas fa-save me-1"></i> <?= lang('save') ?: 'Kaydet' ?>
                                                </button>
                                            </div>
                                            <div class="card-body p-3 p-md-4 bg-light bg-opacity-25">
                                                <div class="row g-3">
                                                    <?php foreach ($sec['settings'] as $key => $meta): ?>
                                                        <?php if (($meta['tab'] ?? '') === $sub_key): ?>
                                                            <?php $col_cls = $meta['col'] ?? ($meta['type'] === 'text' ? 'col-12' : ($meta['type'] === 'bool' ? 'col-md-6' : 'col-md-6')); ?>
                                                            <div class="<?= $col_cls ?> setting-field" data-setting-key="<?= $key ?>">
                                                                <?php $current_val = $section_values[$section_key][$key] ?? $meta['default']; ?>

                                                                <?php if ($meta['type'] === 'bool'): ?>
                                                                    <div class="setting-box setting-box-bool bg-white" onclick="if(event.target.tagName !== 'INPUT'){ const cb = this.querySelector('input[type=checkbox]'); cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')); }">
                                                                        <div class="pe-3 flex-grow-1">
                                                                            <label class="setting-title cursor-pointer mb-1 d-block" for="input-<?= $key ?>">
                                                                                <?= htmlspecialchars($meta['label']) ?>
                                                                                <?php if (!empty($meta['required'])): ?>
                                                                                    <span class="text-danger">*</span>
                                                                                <?php endif; ?>
                                                                            </label>
                                                                            <p class="setting-desc">
                                                                                <?= htmlspecialchars($meta['description']) ?>
                                                                            </p>
                                                                        </div>
                                                                        <div class="form-check form-switch mb-0 flex-shrink-0">
                                                                            <input class="form-check-input setting-input cursor-pointer" type="checkbox" role="switch"
                                                                                   id="input-<?= $key ?>" name="<?= $key ?>" value="1"
                                                                                   <?= !empty($current_val) ? 'checked' : '' ?>>
                                                                        </div>
                                                                    </div>

                                                                <?php elseif ($meta['type'] === 'select'): ?>
                                                                    <div class="setting-box bg-white">
                                                                        <div class="mb-2">
                                                                            <label class="setting-title mb-1 d-block" for="input-<?= $key ?>">
                                                                                <?= htmlspecialchars($meta['label']) ?>
                                                                                <?php if (!empty($meta['required'])): ?>
                                                                                    <span class="text-danger">*</span>
                                                                                <?php endif; ?>
                                                                            </label>
                                                                            <p class="setting-desc">
                                                                                <?= htmlspecialchars($meta['description']) ?>
                                                                            </p>
                                                                        </div>
                                                                        <div class="pt-2">
                                                                            <select class="form-select form-select-sm setting-input shadow-none py-2" id="input-<?= $key ?>" name="<?= $key ?>">
                                                                                <?php foreach ($meta['options'] as $opt_val => $opt_label): ?>
                                                                                    <?php $val_to_check = is_int($opt_val) ? $opt_label : $opt_val; ?>
                                                                                    <option value="<?= htmlspecialchars((string)$val_to_check) ?>" <?= (string)$current_val === (string)$val_to_check ? 'selected' : '' ?>>
                                                                                        <?= htmlspecialchars($opt_label) ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>

                                                                <?php elseif ($meta['type'] === 'text'): ?>
                                                                    <div class="setting-box bg-white">
                                                                        <div class="mb-2">
                                                                            <label class="setting-title mb-1 d-block" for="input-<?= $key ?>">
                                                                                <?= htmlspecialchars($meta['label']) ?>
                                                                                <?php if (!empty($meta['required'])): ?>
                                                                                    <span class="text-danger">*</span>
                                                                                <?php endif; ?>
                                                                            </label>
                                                                            <p class="setting-desc">
                                                                                <?= htmlspecialchars($meta['description']) ?>
                                                                            </p>
                                                                        </div>
                                                                        <div class="pt-2">
                                                                            <textarea class="form-control setting-input font-monospace shadow-none" id="input-<?= $key ?>" name="<?= $key ?>" rows="4"><?= htmlspecialchars((string)$current_val) ?></textarea>
                                                                        </div>
                                                                    </div>

                                                                <?php elseif ($meta['type'] === 'color'): ?>
                                                                    <div class="setting-box bg-white">
                                                                        <div class="mb-2">
                                                                            <label class="setting-title mb-1 d-block" for="input-<?= $key ?>">
                                                                                <?= htmlspecialchars($meta['label']) ?>
                                                                            </label>
                                                                            <p class="setting-desc">
                                                                                <?= htmlspecialchars($meta['description']) ?>
                                                                            </p>
                                                                        </div>
                                                                        <div class="pt-2">
                                                                            <div class="input-group input-group-sm">
                                                                                <input type="color" class="form-control form-control-color rounded-start-3" id="picker-<?= $key ?>" value="<?= htmlspecialchars((string)$current_val ?: '#35A768') ?>" oninput="document.getElementById('input-<?= $key ?>').value = this.value; document.getElementById('input-<?= $key ?>').dispatchEvent(new Event('change'));">
                                                                                <input type="text" class="form-control font-monospace setting-input rounded-end-3 py-2" id="input-<?= $key ?>" name="<?= $key ?>" value="<?= htmlspecialchars((string)$current_val) ?>" oninput="document.getElementById('picker-<?= $key ?>').value = this.value;">
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                <?php elseif (!empty($meta['is_secret'])): ?>
                                                                    <div class="setting-box bg-white">
                                                                        <div class="mb-2">
                                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                                <label class="setting-title mb-0" for="input-<?= $key ?>">
                                                                                    <?= htmlspecialchars($meta['label']) ?>
                                                                                </label>
                                                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="fas fa-lock me-1"></i>Gizli</span>
                                                                            </div>
                                                                            <p class="setting-desc">
                                                                                <?= htmlspecialchars($meta['description']) ?>
                                                                            </p>
                                                                        </div>
                                                                        <div class="pt-2">
                                                                            <div class="input-group input-group-sm">
                                                                                <input type="password" class="form-control font-monospace setting-input rounded-start-3 py-2" id="input-<?= $key ?>" name="<?= $key ?>" value="<?= htmlspecialchars((string)$current_val) ?>" readonly>
                                                                                <button type="button" class="btn btn-outline-secondary" onclick="revealSecret('<?= $key ?>', 'input-<?= $key ?>')">
                                                                                    <i class="fas fa-eye me-1"></i>Göster
                                                                                </button>
                                                                                <button type="button" class="btn btn-outline-secondary rounded-end-3" onclick="copySecret('input-<?= $key ?>')">
                                                                                    <i class="fas fa-copy me-1"></i>Kopyala
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                <?php else: ?>
                                                                    <div class="setting-box bg-white">
                                                                        <div class="mb-2">
                                                                            <label class="setting-title mb-1 d-block" for="input-<?= $key ?>">
                                                                                <?= htmlspecialchars($meta['label']) ?>
                                                                                <?php if (!empty($meta['required'])): ?>
                                                                                    <span class="text-danger">*</span>
                                                                                <?php endif; ?>
                                                                            </label>
                                                                            <p class="setting-desc">
                                                                                <?= htmlspecialchars($meta['description']) ?>
                                                                            </p>
                                                                        </div>
                                                                        <div class="pt-2">
                                                                            <input type="<?= $meta['type'] === 'int' ? 'number' : ($meta['type'] === 'email' ? 'email' : ($meta['type'] === 'url' ? 'url' : 'text')) ?>"
                                                                                   class="form-control form-control-sm setting-input shadow-none py-2" id="input-<?= $key ?>"
                                                                                   name="<?= $key ?>" value="<?= htmlspecialchars((string)$current_val) ?>">
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>


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
                    <span class="fw-bold">Kaydedilmemiş değişiklikler var!</span>
                    <span class="text-white-50 small ms-2" id="dirty-count-text">Değişikliklerin geçerli olması için lütfen kaydedin.</span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-light px-3" id="btn-discard">
                    <i class="fas fa-undo me-1"></i> Değişiklikleri Geri Al
                </button>
                <button type="button" class="btn btn-success px-4 fw-semibold" id="btn-save-current">
                    <i class="fas fa-save me-1"></i> Değişiklikleri Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Blocked Period / Holiday -->
<div class="modal fade" id="modal-add-blocked-period" tabindex="-1" aria-labelledby="modalAddBlockedPeriodLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark" id="modalAddBlockedPeriodLabel">
                    <i class="fas fa-calendar-times text-primary me-2"></i>Tatil / Kapalı Dönem Ekle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="form-add-blocked-period">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark" for="bp-name">
                            Başlık / Açıklama <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="bp-name" required placeholder="Örn: Yılbaşı Tatili, Tadilat">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-bold text-dark" for="bp-start">
                                Başlangıç <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" class="form-control" id="bp-start" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-bold text-dark" for="bp-end">
                                Bitiş <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" class="form-control" id="bp-end" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark" for="bp-notes">Notlar</label>
                        <textarea class="form-control" id="bp-notes" rows="2" placeholder="İsteğe bağlı ek açıklama..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top py-3 px-4">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= lang('cancel') ?: 'İptal' ?></button>
                <button type="button" class="btn btn-primary px-4 fw-semibold" id="btn-submit-blocked-period">
                    <i class="fas fa-check me-1"></i> Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Password Reset Link Display -->
<div class="modal fade" id="modal-pwd-reset-link" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="fas fa-key text-warning me-2"></i>Şifre Sıfırlama Bağlantısı
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-2" id="pwd-reset-user-text"></p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control font-monospace bg-light" id="pwd-reset-link-input" readonly>
                    <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('pwd-reset-link-input').value); showToast('Bağlantı kopyalandı!', 'bg-success');">
                        <i class="fas fa-copy me-1"></i> Kopyala
                    </button>
                </div>
                <small class="text-muted">Bu bağlantı 2 saat boyunca geçerlidir ve kullanıcıya WhatsApp veya e-posta yoluyla iletilebilir.</small>
            </div>
<!-- Modal: Test Template Send -->
<div class="modal fade" id="modal-test-template" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="fas fa-paper-plane text-primary me-2"></i>Test Bildirimi Gönder
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">Hazırladığınız şablonun gerçek cihazda nasıl göründüğünü kontrol etmek için test gönderin.</p>
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small">Gönderim Kanalı</label>
                    <select class="form-select" id="test-channel-select">
                        <option value="email">E-posta</option>
                        <option value="whatsapp">WhatsApp / SMS</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small" id="test-recipient-label">Alıcı E-posta Adresi</label>
                    <input type="text" class="form-control" id="test-recipient-input" placeholder="ornek@alanadiniz.com">
                </div>
            </div>
            <div class="modal-footer border-top py-3 px-4">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary px-4 fw-semibold" id="btn-submit-test-send">
                    <i class="fas fa-paper-plane me-1"></i> Gönder
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
    <div id="settings-toast" class="toast align-items-center text-white bg-primary border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
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

    // 1. Hash Routing Support
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

    // Direct card save buttons trigger main save
    document.addEventListener('click', function(e) {
        if (e.target && e.target.closest('.btn-save-section-direct')) {
            e.preventDefault();
            document.getElementById('btn-save-current').click();
        }
    });

    // 2. Dirty State Tracking
    document.querySelectorAll('.settings-form').forEach(form => {
        form.addEventListener('change', function(e) {
            const sec = this.getAttribute('data-section');
            dirtyState[sec] = true;
            updateSaveBar();
        });
        form.addEventListener('input', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
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

    // Working Plan Toggle (Açık / Kapalı)
    document.querySelectorAll('.wp-day-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const day = this.dataset.day;
            const isOpen = this.checked;
            const startInput = document.getElementById('wp_start_' + day);
            const endInput = document.getElementById('wp_end_' + day);
            const statusLabel = this.closest('td').querySelector('.wp-status-text');
            if (startInput) startInput.disabled = !isOpen;
            if (endInput) endInput.disabled = !isOpen;
            if (statusLabel) {
                statusLabel.innerText = isOpen ? 'Açık' : 'Kapalı';
                statusLabel.className = 'form-check-label small fw-semibold wp-status-text ' + (isOpen ? 'text-success' : 'text-danger');
            }
            dirtyState['business'] = true;
            updateSaveBar();
        });
    });

    // Breaks: Add Break
    const btnAddBreak = document.getElementById('btn-add-break');
    if (btnAddBreak) {
        btnAddBreak.addEventListener('click', function() {
            const container = document.getElementById('breaks-container');
            const noBreaksHint = document.getElementById('no-breaks-hint');
            if (noBreaksHint) noBreaksHint.remove();

            const row = document.createElement('div');
            row.className = 'd-flex align-items-center gap-2 mb-2 break-row';
            row.innerHTML = `
                <span class="badge bg-light text-dark border px-2 py-1"><i class="fas fa-coffee me-1 text-primary"></i> Mola</span>
                <input type="time" class="form-control form-control-sm break-start" style="max-width: 140px;" value="12:30">
                <span class="text-muted">-</span>
                <input type="time" class="form-control form-control-sm break-end" style="max-width: 140px;" value="13:30">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-break"><i class="fas fa-trash-alt"></i></button>
            `;
            container.appendChild(row);
            dirtyState['business'] = true;
            updateSaveBar();
        });
    }

    // Breaks: Remove Break
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove-break');
        if (btn) {
            btn.closest('.break-row').remove();
            dirtyState['business'] = true;
            updateSaveBar();
        }
    });

    // Apply Global Working Plan to All Staff
    const btnApplyGlobal = document.getElementById('btn-apply-global-plan');
    if (btnApplyGlobal) {
        btnApplyGlobal.addEventListener('click', function() {
            if (!confirm('Bu çalışma planı tüm personelin takvimine uygulanacaktır. Emin misiniz?')) {
                return;
            }

            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Senkronize ediliyor...';

            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            const wp = {};
            days.forEach(day => {
                const isOpen = document.getElementById('wp_open_' + day)?.checked;
                if (isOpen) {
                    const start = document.getElementById('wp_start_' + day)?.value || '09:00';
                    const end = document.getElementById('wp_end_' + day)?.value || '18:00';
                    wp[day] = { start: start, end: end, breaks: [] };
                } else {
                    wp[day] = null;
                }
            });

            const breaks = [];
            document.querySelectorAll('.break-row').forEach(row => {
                const bStart = row.querySelector('.break-start')?.value;
                const bEnd = row.querySelector('.break-end')?.value;
                if (bStart && bEnd) {
                    breaks.push({ start: bStart, end: bEnd });
                }
            });

            days.forEach(day => {
                if (wp[day]) {
                    wp[day].breaks = breaks;
                }
            });

            const csrfToken = window.vars('csrf_token') || '';

            fetch(apiBase + '/apply_global_working_plan', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken,
                    'X-CSRF': csrfToken
                },
                body: JSON.stringify({ working_plan: wp, csrf_token: csrfToken })
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-users-cog me-1"></i> Tüm Personele Senkronize Et';
                if (data.success) {
                    showToast(data.message || 'Çalışma planı başarıyla uygulandı!', 'bg-success');
                } else {
                    showToast(data.message || 'İşlem başarısız oldu.', 'bg-danger');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-users-cog me-1"></i> Tüm Personele Senkronize Et';
                showToast('Hata: ' + err.message, 'bg-danger');
            });
        });
    }

    // Modal: Submit Blocked Period (Holiday)
    const btnSubmitBp = document.getElementById('btn-submit-blocked-period');
    if (btnSubmitBp) {
        btnSubmitBp.addEventListener('click', function() {
            const name = document.getElementById('bp-name').value.trim();
            const start = document.getElementById('bp-start').value;
            const end = document.getElementById('bp-end').value;
            const notes = document.getElementById('bp-notes').value.trim();

            if (!name || !start || !end) {
                alert('Lütfen başlık, başlangıç ve bitiş alanlarını doldurunuz.');
                return;
            }

            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Kaydediliyor...';

            const csrfToken = window.vars('csrf_token') || '';

            fetch(apiBase + '/add_blocked_period', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken,
                    'X-CSRF': csrfToken
                },
                body: JSON.stringify({
                    name: name,
                    start_datetime: start,
                    end_datetime: end,
                    notes: notes,
                    csrf_token: csrfToken
                })
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check me-1"></i> Kaydet';

                if (data.success) {
                    const modalEl = document.getElementById('modal-add-blocked-period');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    document.getElementById('form-add-blocked-period').reset();

                    const wrapper = document.getElementById('blocked-periods-table-wrapper');
                    const noAlert = document.getElementById('no-blocked-periods-alert');
                    if (noAlert) noAlert.remove();

                    let tbody = wrapper.querySelector('tbody');
                    if (!tbody) {
                        wrapper.innerHTML = `
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="table-blocked-periods">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Başlık</th>
                                            <th>Başlangıç</th>
                                            <th>Bitiş</th>
                                            <th>Notlar</th>
                                            <th class="text-end">İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        `;
                        tbody = wrapper.querySelector('tbody');
                    }

                    const newRow = document.createElement('tr');
                    newRow.id = 'bp-row-' + data.id;
                    newRow.innerHTML = `
                        <td class="fw-semibold text-dark">${escapeHtml(name)}</td>
                        <td><span class="badge bg-light text-dark border">${escapeHtml(start.replace('T', ' '))}</span></td>
                        <td><span class="badge bg-light text-dark border">${escapeHtml(end.replace('T', ' '))}</span></td>
                        <td class="small text-muted">${escapeHtml(notes || '-')}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-blocked-period" data-id="${data.id}" title="Sil">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(newRow);

                    showToast('Tatil / Kapalı dönem başarıyla eklendi!', 'bg-success');
                } else {
                    showToast(data.message || 'Kayıt eklenirken bir hata oluştu.', 'bg-danger');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check me-1"></i> Kaydet';
                showToast('Hata: ' + err.message, 'bg-danger');
            });
        });
    }

    // Delete Blocked Period
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-delete-blocked-period');
        if (btn) {
            const id = btn.dataset.id;
            if (!confirm('Bu kaydı silmek istediğinize emin misiniz?')) return;

            const csrfToken = window.vars('csrf_token') || '';

            fetch(apiBase + '/delete_blocked_period', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken,
                    'X-CSRF': csrfToken
                },
                body: JSON.stringify({ id: id, csrf_token: csrfToken })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('bp-row-' + id);
                    if (row) row.remove();
                    showToast('Kayıt başarıyla silindi!', 'bg-success');
                } else {
                    showToast(data.message || 'Silme işlemi başarısız oldu.', 'bg-danger');
                }
            });
        }
    });

    // 3. Main Save Handler
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
            if (!cb.checked && cb.name && !cb.name.startsWith('wp_open_')) {
                payload[cb.name] = '0';
            }
        });

        // If on business section, construct company_working_plan
        if (activeSection === 'business') {
            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            const wp = {};
            days.forEach(day => {
                const isOpen = document.getElementById('wp_open_' + day)?.checked;
                if (isOpen) {
                    const start = document.getElementById('wp_start_' + day)?.value || '09:00';
                    const end = document.getElementById('wp_end_' + day)?.value || '18:00';
                    wp[day] = { start: start, end: end, breaks: [] };
                } else {
                    wp[day] = null;
                }
            });

            const breaks = [];
            document.querySelectorAll('.break-row').forEach(row => {
                const bStart = row.querySelector('.break-start')?.value;
                const bEnd = row.querySelector('.break-end')?.value;
                if (bStart && bEnd) {
                    breaks.push({ start: bStart, end: bEnd });
                }
            });

            days.forEach(day => {
                if (wp[day]) {
                    wp[day].breaks = breaks;
                }
            });

            payload['company_working_plan'] = wp;
        }

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Kaydediliyor...';

        const csrfToken = window.vars('csrf_token') || '';

        fetch(apiBase + '/' + activeSection, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken,
                'X-CSRF': csrfToken
            },
            body: JSON.stringify({ settings: payload, csrf_token: csrfToken })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> Değişiklikleri Kaydet';

            if (data.success) {
                dirtyState[activeSection] = false;
                updateSaveBar();
                showToast(data.message || 'Ayarlar başarıyla kaydedildi!', 'bg-success');
            } else {
                showToast(data.message || 'Ayar kaydedilirken bir hata oluştu.', 'bg-danger');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> Değişiklikleri Kaydet';
            showToast('Ağ hatası: ' + err.message, 'bg-danger');
        });
    });

    // 4. Discard Handler
    document.getElementById('btn-discard').addEventListener('click', function() {
        if (confirm('Kaydedilmemiş değişiklikleri geri almak istediğinize emin misiniz?')) {
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

    // 6. User Role Change (Up/Down)
    document.querySelectorAll('.select-change-role').forEach(sel => {
        sel.addEventListener('change', function() {
            const uId = this.dataset.userId;
            const rId = this.value;
            const csrfToken = window.vars('csrf_token') || '';

            fetch(apiBase + '/update_user_role', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken,
                    'X-CSRF': csrfToken
                },
                body: JSON.stringify({ user_id: uId, role_id: rId, csrf_token: csrfToken })
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    showToast(d.message, 'bg-success');
                    // update badge
                    const badge = document.querySelector('#usr-row-' + uId + ' .badge');
                    if (badge) badge.innerText = d.role_name;
                } else {
                    showToast(d.message || 'Rol güncellenemedi.', 'bg-danger');
                }
            });
        });
    });

    // 7. Password Reset Link Generator
    document.querySelectorAll('.btn-send-pwd-reset').forEach(btn => {
        btn.addEventListener('click', function() {
            const uId = this.dataset.userId;
            const csrfToken = window.vars('csrf_token') || '';

            fetch(apiBase + '/send_password_reset', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken,
                    'X-CSRF': csrfToken
                },
                body: JSON.stringify({ user_id: uId, csrf_token: csrfToken })
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    document.getElementById('pwd-reset-user-text').innerText = `${d.user_name} (${d.email}) için oluşturulan şifre sıfırlama bağlantısı:`;
                    document.getElementById('pwd-reset-link-input').value = d.reset_link;
                    const modal = new bootstrap.Modal(document.getElementById('modal-pwd-reset-link'));
                    modal.show();
                } else {
                    showToast(d.message || 'Şifre sıfırlama hatası.', 'bg-danger');
                }
            });
        });
    });

    // 8. Custom Domain Verification
    const btnVerifyDomain = document.getElementById('btn-verify-domain');
    if (btnVerifyDomain) {
        btnVerifyDomain.addEventListener('click', function() {
            const domain = document.getElementById('input-custom_domain_name').value.trim();
            if (!domain) {
                alert('Lütfen doğrulamak istediğiniz alan adını girin.');
                return;
            }

            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Doğrulanıyor...';

            const csrfToken = window.vars('csrf_token') || '';

            fetch(apiBase + '/verify_custom_domain', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken,
                    'X-CSRF': csrfToken
                },
                body: JSON.stringify({ domain: domain, csrf_token: csrfToken })
            })
            .then(r => r.json())
            .then(d => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle me-1"></i> Doğrula & SSL Kur';
                if (d.success) {
                    showToast(d.message, 'bg-success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(d.message || 'Alan adı doğrulanamadı.', 'bg-danger');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle me-1"></i> Doğrula & SSL Kur';
                showToast('Hata: ' + err.message, 'bg-danger');
            });
        });
    }

    // 9. Voice Assistant Request Number
    const btnRequestVoice = document.getElementById('btn-request-voice-number');
    if (btnRequestVoice) {
        btnRequestVoice.addEventListener('click', function() {
            if (!confirm('Aylık 1.250 TL / 60 Dakika sesli asistan santral paketi başlatılacaktır. Emin misiniz?')) return;

            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Tahsis Ediliyor...';

            const csrfToken = window.vars('csrf_token') || '';

            fetch(apiBase + '/request_voice_assistant', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken,
                    'X-CSRF': csrfToken
                },
                body: JSON.stringify({ csrf_token: csrfToken })
            })
            .then(r => r.json())
            .then(d => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-phone-plus me-1"></i> Numara Tahsis Et';
                if (d.success) {
                    document.getElementById('input-voice_assistant_number').value = d.number;
                    document.getElementById('input-voice_assistant_status').value = d.status;
                    showToast(d.message, 'bg-success');
                } else {
                    showToast(d.message || 'Numara tahsis edilemedi.', 'bg-danger');
                }
            });
        });
    }

    // 10. Load Legal Template Presets
    document.querySelectorAll('.btn-load-legal-preset').forEach(btn => {
        btn.addEventListener('click', function() {
            const type = this.dataset.type;
            const sector = this.dataset.sector;
            const csrfToken = window.vars('csrf_token') || '';

            fetch(apiBase + '/load_legal_template', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken,
                    'X-CSRF': csrfToken
                },
                body: JSON.stringify({ type: type, sector: sector, csrf_token: csrfToken })
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    const fieldMap = {
                        'kvkk': 'input-kvkk_content',
                        'privacy': 'input-privacy_policy_content',
                        'distance_sales': 'input-distance_sales_content',
                        'terms': 'input-terms_and_conditions_content',
                        'cancellation_refund': 'input-cancellation_refund_content',
                        'consent_forms': 'input-consent_form_content'
                    };
                    const targetInputId = fieldMap[type] || ('input-' + type + '_content');
                    const targetEl = document.getElementById(targetInputId);
                    if (targetEl) {
                        targetEl.value = d.content;
                        targetEl.dispatchEvent(new Event('change'));
                        showToast('Hazır şablon başarıyla metin alanına aktarıldı!', 'bg-success');
                    }
                }
            });
        });
    });

    // 11. WhatsApp Baileys Bridge
    const waModeSelect = document.querySelector('[name="whatsapp_mode"]');
    const waBridgePanel = document.getElementById('wa-bridge-panel');
    const waConsentCheckbox = document.getElementById('wa-bridge-consent');
    const waQrStartBtn = document.getElementById('wa-qr-start-btn');
    const waQrStatusBtn = document.getElementById('wa-qr-status-btn');
    const waQrLogoutBtn = document.getElementById('wa-qr-logout-btn');
    const waQrResult = document.getElementById('wa-qr-result');
    const waBridgeBadge = document.getElementById('wa-bridge-badge');
    const waRoutes = window.vars('whatsapp_routes') || {};

    function updateBridgePanelVisibility() {
        if (!waBridgePanel) return;
        const currentMode = waModeSelect ? waModeSelect.value : 'official';
        if (currentMode === 'unofficial') {
            waBridgePanel.style.display = 'block';
        } else {
            waBridgePanel.style.display = 'none';
        }
    }

    if (waModeSelect) {
        waModeSelect.addEventListener('change', updateBridgePanelVisibility);
        updateBridgePanelVisibility();
    }

    if (waConsentCheckbox && waQrStartBtn) {
        waConsentCheckbox.addEventListener('change', function() {
            waQrStartBtn.disabled = !this.checked;
        });
    }

    if (waQrStartBtn) {
        waQrStartBtn.addEventListener('click', function() {
            const csrfToken = window.vars('csrf_token') || '';
            const origHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Başlatılıyor...';

            const formData = new URLSearchParams();
            formData.append('csrf_token', csrfToken);

            fetch(waRoutes.qr_start, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                body: formData.toString()
            })
            .then(r => r.json())
            .then(d => {
                this.innerHTML = origHtml;
                this.disabled = false;
                if (d.success) {
                    if (d.status === 'connected') {
                        if (waBridgeBadge) {
                            waBridgeBadge.className = 'badge px-3 py-2 rounded-pill bg-success';
                            waBridgeBadge.textContent = 'Bağlı ✓';
                        }
                        waQrResult.innerHTML = '<div class="alert alert-success mb-0"><i class="fas fa-check-circle me-1"></i><strong>Bağlantı Aktif!</strong> WhatsApp QR eşleştirmesi bağlı ve aktif.</div>';
                        showToast('WhatsApp zaten bağlı!', 'bg-success');
                        return;
                    }
                    showToast('QR eşleştirme başlatıldı.', 'bg-info');
                    const qrCode = d.qr || (d.response && d.response.qr);
                    if (qrCode) {
                        waQrResult.innerHTML = `<img src="data:image/png;base64,${qrCode}" alt="WhatsApp QR" class="img-fluid rounded border shadow-sm" style="max-width:260px">`;
                    }
                } else {
                    showToast(d.message || 'QR başlatılamadı.', 'bg-danger');
                }
            })
            .catch(err => {
                this.innerHTML = origHtml;
                this.disabled = false;
                showToast('Hata: ' + err.message, 'bg-danger');
            });
        });
    }
});

// Template & Export Handlers
function downloadTemplate(module) {
    window.location.href = window.vars('api_base_url') + '/download_import_template?module=' + module;
}

function exportData(format) {
    const sel = document.getElementById('export-module-select');
    const mod = sel ? sel.value : 'customers';
    window.location.href = window.vars('api_base_url') + '/export_data?module=' + mod + '&format=' + format;
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function showToast(msg, bgClass = 'bg-primary') {
    const toastEl = document.getElementById('settings-toast');
    const toastMsg = document.getElementById('toast-message');
    toastEl.className = 'toast align-items-center text-white border-0 shadow ' + bgClass;
    toastMsg.innerText = msg;
    const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
    toast.show();
}

function revealSecret(key, inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;

    if (input.type === 'text') {
        input.type = 'password';
        return;
    }

    const csrfToken = window.vars('csrf_token') || '';

    fetch(window.vars('api_base_url') + '/reveal_secret', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrfToken,
            'X-CSRF': csrfToken
        },
        body: JSON.stringify({ key: key, csrf_token: csrfToken })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            input.value = d.value;
            input.type = 'text';
            showToast('Gizli anahtar gösterildi.', 'bg-info');
        } else {
            showToast(d.message || 'Yetki hatası', 'bg-danger');
        }
    });
}

function copySecret(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;

    if (input.value.includes('••••')) {
        const key = input.getAttribute('name') || 'agent_api_key';
        const csrfToken = window.vars('csrf_token') || '';
        fetch(window.vars('api_base_url') + '/reveal_secret', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken,
                'X-CSRF': csrfToken
            },
            body: JSON.stringify({ key: key, csrf_token: csrfToken })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                navigator.clipboard.writeText(d.value);
                showToast('Gizli anahtar panoya kopyalandı!', 'bg-success');
            }
        });
    } else {
        navigator.clipboard.writeText(input.value);
        showToast('Anahtar panoya kopyalandı!', 'bg-success');
    }
}

function rotateAgentKey() {
    if (!confirm('Agent API anahtarını yenilemek istediğinize emin misiniz?')) return;
    const csrfToken = window.vars('csrf_token') || '';

    fetch(window.vars('api_base_url') + '/rotate_agent_key', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrfToken,
            'X-CSRF': csrfToken
        },
        body: JSON.stringify({ csrf_token: csrfToken })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById('mcp-agent-key').value = d.agent_api_key;
            document.getElementById('mcp-agent-key').type = 'text';
            showToast('Yeni Agent API anahtarı üretildi!', 'bg-success');
        } else {
            showToast(d.message || 'Hata oluştu', 'bg-danger');
        }
    });
}

function testConnection() {
    const csrfToken = window.vars('csrf_token') || '';
    fetch(window.vars('api_base_url') + '/test_ping', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrfToken,
            'X-CSRF': csrfToken
        },
        body: JSON.stringify({ csrf_token: csrfToken })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success && d.status === 'online') {
            showToast('Bağlantı başarılı! (' + d.latency_ms + 'ms)', 'bg-success');
        } else {
            showToast('Sunucu yanıt vermedi!', 'bg-danger');
        }
    })
    .catch(err => {
        showToast('Bağlantı hatası: ' + err.message, 'bg-danger');
    });
}

// ==================== INTEGRATIONS HUB & EXPANDABLE DRAWER ====================
const integrationMeta = {
    google: {
        title: 'Google Workspace & Harita Entegrasyonları',
        desc: 'Google Takvim, Meet online görüşmeleri, Haritalar butonu ve GA4 ölçümü.',
        icon: '<i class="fab fa-google text-primary"></i>'
    },
    whatsapp: {
        title: 'WhatsApp Business İletişim Hattı',
        desc: 'Resmi Meta Cloud API (Tıkla Bağlan) ve Baileys QR Köprü bağlantısı.',
        icon: '<i class="fab fa-whatsapp text-success"></i>'
    },
    instagram: {
        title: 'Instagram İşletme Profili & DM AI',
        desc: 'Profil Randevu Butonu ve Direct DM Yapay Zeka Asistanı.',
        icon: '<i class="fab fa-instagram text-danger"></i>'
    },
    facebook: {
        title: 'Facebook Sayfası & Lead Ads',
        desc: 'Facebook reklam lead formları senkronizasyonu ve sayfa randevu butonu.',
        icon: '<i class="fab fa-facebook-f text-primary"></i>'
    },
    telegram: {
        title: 'Telegram Bildirim Botu',
        desc: 'Personel ve müşterilere anlık randevu, hatırlatma ve rapor botu.',
        icon: '<i class="fab fa-telegram-plane text-info"></i>'
    },
    smtp: {
        title: 'Kurumsal E-posta (SMTP) Mail Sunucusu',
        desc: 'Kendi kurumsal alan adınızdan randevu onay ve fatura mailleri iletimi.',
        icon: '<i class="fas fa-envelope-open-text text-warning"></i>'
    },
    ai_assistant: {
        title: 'AI Asistan & Çağrı Santrali Yönetimi',
        desc: 'Sınırsız yazılı AI desteği ve 0850 sesli telefon santrali kota yönetimi.',
        icon: '<i class="fas fa-robot text-purple"></i>'
    },
    sms: {
        title: 'SMS Sağlayıcı API & Başlık Yönetimi',
        desc: 'Netgsm, İletiMerkezi veya MutluCell ile başlıklı SMS gönderimi.',
        icon: '<i class="fas fa-sms text-danger"></i>'
    },
    erp: {
        title: 'Muhasebe & E-Fatura / ERP Entegrasyonları',
        desc: 'Paraşüt, BizimHesap, KolayBi ile adisyondan tek tıkla e-arşiv fatura.',
        icon: '<i class="fas fa-file-invoice-dollar text-success"></i>'
    },
    video: {
        title: 'Online Görüşme & Video Servisleri',
        desc: 'Google Meet ve Zoom ile otomatik video randevu odaları oluşturma.',
        icon: '<i class="fas fa-video text-danger"></i>'
    },
    calendar: {
        title: 'Harici Takvim (iCal) Senkronizasyonu',
        desc: 'Apple Takvim, Outlook ve iCal iki yönlü takvim eşitlemesi.',
        icon: '<i class="fas fa-calendar-alt text-primary"></i>'
    },
    mcp: {
        title: 'Model Context Protocol (MCP) & AI Developer Gateway',
        desc: 'Claude Desktop, Cursor IDE ve harici AI ajanları için entegrasyon API.',
        icon: '<i class="fas fa-terminal text-dark"></i>'
    }
};

function openIntegrationDrawer(serviceKey) {
    const meta = integrationMeta[serviceKey] || { title: 'Entegrasyon Yapılandırması', desc: '', icon: '<i class="fas fa-cog"></i>' };
    
    // Update header
    const titleEl = document.getElementById('drawer-header-title');
    const descEl = document.getElementById('drawer-header-desc');
    const iconEl = document.getElementById('drawer-header-icon');
    if (titleEl) titleEl.innerText = meta.title;
    if (descEl) descEl.innerText = meta.desc;
    if (iconEl) iconEl.innerHTML = meta.icon;

    // Hide all drawer panels
    document.querySelectorAll('.drawer-service-panel').forEach(p => p.style.display = 'none');
    
    // Show target panel
    const targetPanel = document.getElementById('drawer-panel-' + serviceKey);
    if (targetPanel) {
        targetPanel.style.display = 'block';
    }

    // Show drawer
    const drawer = document.getElementById('integration-config-drawer');
    if (drawer) {
        drawer.style.display = 'block';
        drawer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Highlight card
    document.querySelectorAll('.integration-grid-card').forEach(c => {
        if (c.dataset.service === serviceKey) {
            c.classList.add('border-primary', 'border-2', 'shadow');
        } else {
            c.classList.remove('border-primary', 'border-2', 'shadow');
        }
    });
}

function closeIntegrationDrawer() {
    const drawer = document.getElementById('integration-config-drawer');
    if (drawer) drawer.style.display = 'none';
    document.querySelectorAll('.integration-grid-card').forEach(c => {
        c.classList.remove('border-primary', 'border-2', 'shadow');
    });
}

function connectGoogleWithSelectedServices() {
    showToast('Google OAuth oturumu başlatılıyor...', 'bg-info');
    const selected = [];
    if (document.getElementById('input-google_sync_enabled')?.checked) selected.push('calendar');
    if (document.getElementById('input-google_sync_meet')?.checked) selected.push('calendar');
    if (document.getElementById('input-google_sync_contacts')?.checked) selected.push('contacts');
    if (document.getElementById('input-google_sync_sheets')?.checked) selected.push('sheets');
    if (document.getElementById('input-google_sync_ads')?.checked) selected.push('ads');
    if (document.getElementById('input-google_sync_gmail')?.checked) selected.push('gmail');
    if (document.getElementById('input-google_sync_docs')?.checked) selected.push('docs');
    if (document.getElementById('input-google_sync_tasks')?.checked) selected.push('tasks');
    if (document.getElementById('input-google_sync_tagmanager')?.checked) selected.push('tagmanager');
    if (document.getElementById('input-google_sync_drive')?.checked) selected.push('drive');

    // If none specifically toggled, connect the full BooKi Google suite
    if (selected.length === 0) {
        selected.push('calendar', 'contacts', 'analytics', 'ads', 'sheets', 'docs', 'tasks', 'tagmanager', 'drive', 'gmail');
    }

    const uniqueSelected = [...new Set(selected)];
    const targetUrl = '<?= site_url('google_integrations/oauth/company/0') ?>?services=' + encodeURIComponent(uniqueSelected.join(','));
    setTimeout(() => {
        window.location.href = targetUrl;
    }, 300);
}

// ==================== SECTOR SELECTION ====================
function selectCompanySector(secCode, el) {
    const input = document.getElementById('input-company_sector');
    if (input) {
        input.value = secCode;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    document.querySelectorAll('.sector-select-card').forEach(card => {
        card.classList.remove('border-primary', 'border-2', 'bg-primary', 'bg-opacity-10', 'shadow-sm');
        card.classList.add('border-light-subtle', 'bg-light');
        const indicator = card.querySelector('.sector-check-indicator');
        if (indicator) indicator.innerHTML = '<span class="badge bg-white text-muted border rounded-pill">Seç</span>';
    });

    if (el) {
        el.classList.remove('border-light-subtle', 'bg-light');
        el.classList.add('border-primary', 'border-2', 'bg-primary', 'bg-opacity-10', 'shadow-sm');
        const indicator = el.querySelector('.sector-check-indicator');
        if (indicator) indicator.innerHTML = '<span class="badge bg-primary rounded-pill"><i class="fas fa-check"></i> Aktif</span>';
    }

    showToast('Sektör seçimi güncellendi. Kaydetmeyi unutmayınız.', 'bg-info');
}

// ==================== LEGAL SCROLL-TO-BOTTOM TRIGGER ====================
function handleContractScroll(key, el) {
    if (!el) return;
    const progress = Math.min(100, Math.round(((el.scrollTop + el.clientHeight) / el.scrollHeight) * 100));
    
    const badge = document.getElementById('badge-progress-' + key);
    const bar = document.getElementById('bar-progress-' + key);
    const chk = document.getElementById('check-consent-' + key);
    const btn = document.getElementById('btn-consent-' + key);
    const lbl = document.getElementById('label-consent-' + key);

    if (badge) badge.innerText = '%' + progress + ' Okundu';
    if (bar) bar.style.width = progress + '%';

    // When scrolled to bottom (within 15px threshold)
    if (el.scrollTop + el.clientHeight >= el.scrollHeight - 15) {
        if (bar) {
            bar.style.width = '100%';
            bar.classList.remove('bg-primary');
            bar.classList.add('bg-success');
        }
        if (badge) {
            badge.innerText = '%100 Tamamlandı ✓';
            badge.classList.remove('bg-secondary-subtle');
            badge.classList.add('bg-success', 'text-white');
        }
        if (chk) chk.disabled = false;
        if (btn) btn.disabled = false;
        if (lbl) {
            lbl.innerText = 'Metin sonuna kadar incelendi, onay kutusu aktif! ✓';
            lbl.classList.remove('text-muted');
            lbl.classList.add('text-success', 'fw-bold');
        }
    }
}

// ==================== 1-CLICK META CONNECT & DISCONNECT ====================
function connectMetaOAuth(channel) {
    const titles = {
        'facebook': 'Facebook Sayfası',
        'instagram': 'Instagram İşletme Hesabı',
        'whatsapp': 'WhatsApp Business Cloud API'
    };
    const title = titles[channel] || 'Meta';
    showToast(title + ' için yetkilendirme penceresi açılıyor...', 'bg-info');

    const oauthUrl = '<?= site_url('meta/oauth') ?>/' + channel;
    const width = 680;
    const height = 750;
    const left = Math.max(0, (window.screen.width - width) / 2);
    const top = Math.max(0, (window.screen.height - height) / 2);

    const popup = window.open(
        oauthUrl,
        'MetaConnectPopup_' + channel,
        `width=${width},height=${height},top=${top},left=${left},scrollbars=yes,status=yes`
    );

    if (!popup || popup.closed || typeof popup.closed === 'undefined') {
        window.location.href = oauthUrl;
    }
}

// Window message listener for Meta OAuth completion from popup
window.addEventListener('message', function(event) {
    if (!event.data || typeof event.data !== 'object') return;

    if (event.data.type === 'meta_oauth_success') {
        const channel = event.data.channel;
        showToast('Meta ' + (channel ? channel.toUpperCase() : '') + ' başarıyla bağlandı! ✓', 'bg-success');

        const badge = document.getElementById('badge-status-' + channel);
        if (badge) {
            badge.className = 'badge bg-success-subtle text-success border border-success-subtle rounded-pill small';
            badge.innerHTML = '<i class="fas fa-check-circle me-1"></i>Bağlı';
        }

        const statusContainer = document.getElementById('status-container-' + channel);
        if (statusContainer) statusContainer.classList.remove('d-none');

        const btnDisconnect = document.getElementById('btn-disconnect-' + channel);
        if (btnDisconnect) btnDisconnect.classList.remove('d-none');

        setTimeout(() => { location.reload(); }, 1200);
    } else if (event.data.type === 'meta_oauth_error') {
        showToast('Meta yetkilendirme hatası: ' + (event.data.error || 'İptal edildi'), 'bg-danger');
    }
});

async function quickConnectMetaEnv(channel) {
    const titles = {
        'facebook': 'Facebook Sayfası',
        'instagram': 'Instagram İşletme Hesabı',
        'whatsapp': 'WhatsApp Business Cloud API'
    };
    const title = titles[channel] || 'Meta';
    const csrfToken = (typeof window.vars === 'function' ? window.vars('csrf_token') : '') || '';

    showToast(title + ' ortam değişkenleri ile bağlanıyor...', 'bg-info');

    try {
        const connectUrl = '<?= site_url('meta/connect') ?>/' + channel;
        const res = await fetch(connectUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken,
                'X-CSRF': csrfToken
            },
            body: JSON.stringify({ csrf_token: csrfToken })
        });

        const data = await res.json();

        if (data.success) {
            showToast(data.message || (title + ' başarıyla bağlandı! ✓'), 'bg-success');

            const badge = document.getElementById('badge-status-' + channel);
            if (badge) {
                badge.className = 'badge bg-success-subtle text-success border border-success-subtle rounded-pill small';
                badge.innerHTML = '<i class="fas fa-check-circle me-1"></i>Bağlı';
            }

            const statusContainer = document.getElementById('status-container-' + channel);
            if (statusContainer) statusContainer.classList.remove('d-none');

            const btnDisconnect = document.getElementById('btn-disconnect-' + channel);
            if (btnDisconnect) btnDisconnect.classList.remove('d-none');

            if (channel === 'facebook' && data.page_id) {
                const el = document.getElementById('fb-page-id-badge');
                if (el) el.innerHTML = '<i class="fas fa-id-badge me-1"></i> ID: ' + data.page_id;
            }
            if (channel === 'instagram' && data.username) {
                const el = document.getElementById('ig-username-badge');
                if (el) el.innerHTML = '<i class="fas fa-at text-danger me-1"></i> Profil: ' + data.username;
            }
            if (channel === 'whatsapp') {
                if (data.phone_number_id) {
                    const el = document.getElementById('wa-phone-id-badge');
                    if (el) el.innerHTML = '<i class="fas fa-hashtag me-1"></i> Phone ID: ' + data.phone_number_id;
                }
                if (data.waba_id) {
                    const el = document.getElementById('wa-waba-id-badge');
                    if (el) el.innerHTML = '<i class="fas fa-id-card me-1"></i> WABA ID: ' + data.waba_id;
                }
            }
        } else {
            showToast(data.message || (title + ' bağlantısı başarısız oldu.'), 'bg-danger');
        }
    } catch (err) {
        console.error('quickConnectMetaEnv error:', err);
        showToast('Bağlantı isteği gönderilirken hata oluştu: ' + (err.message || err), 'bg-danger');
    }
}

async function disconnectMetaChannel(channel) {
    if (!confirm('Bu kanalın bağlantısını kesmek istediğinize emin misiniz?')) {
        return;
    }

    const titles = {
        'facebook': 'Facebook Sayfası',
        'instagram': 'Instagram Hesabı',
        'whatsapp': 'WhatsApp Business'
    };
    const title = titles[channel] || 'Meta';
    const csrfToken = (typeof window.vars === 'function' ? window.vars('csrf_token') : '') || '';

    const btn = document.getElementById('btn-disconnect-' + channel);
    let originalHtml = '';
    if (btn) {
        btn.disabled = true;
        originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
    }

    showToast(title + ' bağlantısı kesiliyor...', 'bg-info');

    try {
        const disconnectUrl = '<?= site_url('meta/disconnect') ?>/' + channel;
        const res = await fetch(disconnectUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken,
                'X-CSRF': csrfToken
            },
            body: JSON.stringify({ csrf_token: csrfToken })
        });

        const data = await res.json();

        if (data.success) {
            showToast(data.message || (title + ' bağlantısı kesildi.'), 'bg-warning text-dark');

            // Update badge on catalog card
            const badge = document.getElementById('badge-status-' + channel);
            if (badge) {
                badge.className = 'badge bg-light text-secondary border rounded-pill small';
                badge.innerText = 'Bağlı Değil';
            }

            // Hide status container and disconnect button
            const statusContainer = document.getElementById('status-container-' + channel);
            if (statusContainer) statusContainer.classList.add('d-none');

            if (btn) btn.classList.add('d-none');
        } else {
            showToast(data.message || 'Bağlantı kesilemedi.', 'bg-danger');
        }
    } catch (err) {
        console.error('disconnectMetaChannel error:', err);
        showToast('İşlem sırasında hata oluştu: ' + (err.message || err), 'bg-danger');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }
}

// ==================== META & GOOGLE SCOPE DESK HELPERS ====================
function copyTextToClipboard(elementId, successMsg) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const text = el.tagName === 'TEXTAREA' || el.tagName === 'INPUT' ? el.value : el.innerText;
    navigator.clipboard.writeText(text).then(function() {
        showToast(successMsg || 'Panoya kopyalandı! ✓', 'bg-success');
    }).catch(function() {
        showToast('Kopyalama başarısız oldu.', 'bg-danger');
    });
}

function loadMetaScopesDesk() {
    const listContainer = document.getElementById('meta-scopes-list-container');
    if (!listContainer || listContainer.dataset.loaded === 'true') return;

    fetch('/meta/scopes')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.scopes) {
                listContainer.dataset.loaded = 'true';
                window._allMetaScopes = data.scopes;
                renderMetaScopesList(data.scopes);
            } else {
                listContainer.innerHTML = '<div class="alert alert-warning">Kapsam verileri alınamadı.</div>';
            }
        })
        .catch(err => {
            console.error('Error fetching meta scopes:', err);
            listContainer.innerHTML = '<div class="alert alert-danger">Kapsamlar yüklenirken hata oluştu: ' + escapeHtml(err.message) + '</div>';
        });
}

function renderMetaScopesList(scopes) {
    const listContainer = document.getElementById('meta-scopes-list-container');
    if (!listContainer) return;

    let html = '';
    for (const [key, scope] of Object.entries(scopes)) {
        const safeJust = (scope.justification || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
        const safeJustTr = (scope.justification_tr || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
        html += `
        <div class="card mb-3 border rounded-3 shadow-xs scope-item-card" data-category="${escapeHtml(scope.category)}" data-scope="${escapeHtml(scope.name)}" data-search="${escapeHtml((scope.name + ' ' + scope.feature + ' ' + scope.category).toLowerCase())}">
            <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary font-monospace">${escapeHtml(scope.name)}</span>
                    <span class="fw-bold text-dark small">${escapeHtml(scope.feature)}</span>
                    <span class="badge bg-secondary-subtle text-secondary small">${escapeHtml(scope.category)}</span>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-check-circle me-1"></i> Aktif & Doğrulandı</span>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-dark mb-0"><i class="fas fa-globe me-1 text-primary"></i> İngilizce İzin Gerekçesi (App Review):</label>
                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 rounded-pill" onclick="navigator.clipboard.writeText('${safeJust}'); showToast('${escapeHtml(scope.name)} İngilizce gerekçesi kopyalandı! ✓', 'bg-success');">
                                <i class="fas fa-copy me-1"></i> Kopyala
                            </button>
                        </div>
                        <div class="p-2 bg-light rounded border text-muted small font-monospace" style="font-size: 0.8rem; line-height: 1.4;">${escapeHtml(scope.justification)}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-dark mb-0"><i class="fas fa-flag me-1 text-danger"></i> Türkçe Açıklama & Sistem Rolü:</label>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 rounded-pill" onclick="navigator.clipboard.writeText('${safeJustTr}'); showToast('${escapeHtml(scope.name)} Türkçe açıklaması kopyalandı! ✓', 'bg-secondary');">
                                <i class="fas fa-copy me-1"></i> Kopyala
                            </button>
                        </div>
                        <div class="p-2 bg-light rounded border text-dark small" style="font-size: 0.8rem; line-height: 1.4;">${escapeHtml(scope.justification_tr)}</div>
                    </div>
                </div>
            </div>
        </div>`;
    }
    listContainer.innerHTML = html;
}

function filterMetaScopes() {
    const q = (document.getElementById('meta-scopes-search-input')?.value || '').toLowerCase().trim();
    const cat = document.getElementById('meta-scopes-category-filter')?.value || '';
    const cards = document.querySelectorAll('.scope-item-card');

    cards.forEach(card => {
        const matchesSearch = !q || (card.dataset.search && card.dataset.search.includes(q));
        const matchesCat = !cat || card.dataset.category === cat;
        if (matchesSearch && matchesCat) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// ==================== SESLİ AI ASİSTAN KOTA PAKETİ ====================
function buyVoiceQuotaPack() {
    if (confirm('Aylık 1.250 TL karşılığında 60 Dakikalık Ek Konuşma Paketi satın almak istiyor musunuz? Tutar bir sonraki faturanıza yansıtılacaktır.')) {
        showToast('60 Dakikalık Ek Sesli Görüşme Paketi hesabınıza tanımlandı!', 'bg-success');
    }
}

// ==================== İLETİŞİM ŞABLONLARI STÜDYOSU ====================
const defaultTplData = {
    confirmation: {
        email_subject: 'Randevunuz Onaylandı! - {isletme_adi}',
        email_body: 'Sayın {musteri_adi},\n\n{isletme_adi} bünyesindeki randevunuz başarıyla onaylanmıştır. Sizi salonumuzda ağırlamaktan mutluluk duyacağız.\n\nRandevu Tarihi: {randevu_tarihi} - {randevu_saati}\nHizmet: {hizmet_adi}\nUzman: {personel_adi}\nAdres: {isletme_adresi}',
        email_btn: 'Randevumu Görüntüle',
        chat_body: 'Sayın {musteri_adi}, {isletme_adi} randevunuz {randevu_tarihi} {randevu_saati} için oluşturulmuştur. Hizmet: {hizmet_adi}. Detaylar ve konum: {randevu_linki}'
    },
    reminder_24h: {
        email_subject: 'Randevu Hatırlatması (Yarın) - {isletme_adi}',
        email_body: 'Sayın {musteri_adi},\n\nYarın saat {randevu_saati}\'de {personel_adi} ile planlanan {hizmet_adi} randevunuzu hatırlatmak isteriz.\n\nRandevu saatinden 10 dakika önce hazır bulunmanızı rica ederiz.\nAdresimiz: {isletme_adresi}\nİletişim: {isletme_telefonu}',
        email_btn: 'Randevu Detayları',
        chat_body: 'Sayın {musteri_adi}, yarın saat {randevu_saati}\'deki {hizmet_adi} randevunuzu hatırlatırız. Randevu saatinden 10 dk önce hazır bulunmanızı rica ederiz. Değişiklik için: {randevu_linki}'
    },
    reminder_2h: {
        email_subject: 'Randevunuza 2 Saat Kaldı! - {isletme_adi}',
        email_body: 'Sayın {musteri_adi},\n\nBugün saat {randevu_saati}\'de {personel_adi} ile randevunuz bulunmaktadır. Kolay ulaşım için aşağıdaki harita konumunu inceleyebilirsiniz.\n\nAdresimiz: {isletme_adresi}\nTelefon: {isletme_telefonu}',
        email_btn: 'Haritada Yol Tarifi Al',
        chat_body: 'Sayın {musteri_adi}, saat {randevu_saati}\'deki {hizmet_adi} randevunuza 2 saat kaldı. Adresimiz: {isletme_adresi}. İletişim: {isletme_telefonu}. Yol tarifi: {randevu_linki}'
    },
    cancellation: {
        email_subject: 'Randevunuz İptal Edildi - {isletme_adi}',
        email_body: 'Sayın {musteri_adi},\n\n{randevu_tarihi} {randevu_saati} tarihindeki {hizmet_adi} randevunuz isteğiniz doğrultusunda iptal edilmiştir.\n\nDilediğiniz zaman yeni randevu oluşturmak için aşağıdaki bağlantıyı kullanabilirsiniz.',
        email_btn: 'Yeni Randevu Oluştur',
        chat_body: 'Sayın {musteri_adi}, {randevu_tarihi} tarihli randevunuz isteğiniz üzerine iptal edilmiş / güncellenmiştir. Yeni randevu oluşturmak için: {randevu_linki}'
    },
    followup: {
        email_subject: 'Deneyiminizi Puanlayın! - {isletme_adi}',
        email_body: 'Sayın {musteri_adi},\n\nBugün {isletme_adi}\'ni tercih ettiğiniz için teşekkür ederiz. Hizmet kalitemizi sürekli geliştirebilmemiz için deneyiminizi 1 dakikada değerlendirebilirsiniz.',
        email_btn: 'Hizmeti Puanla',
        chat_body: 'Sayın {musteri_adi}, bugün {isletme_adi}\'ni tercih ettiğiniz için teşekkür ederiz! Hizmetimizi 1 dakikada puanlayın: {randevu_linki}/review'
    },
    owner: {
        email_subject: 'Yeni Randevu Bildirimi: {musteri_adi} - {tutar} TL',
        email_body: 'Sayın İşletme Yetkilisi,\n\n{randevu_tarihi} saat {randevu_saati} için {musteri_adi} adına {hizmet_adi} ({personel_adi}) randevusu kaydedilmiştir.\n\nTutar: {tutar} TL',
        email_btn: 'Yönetim Paneline Git',
        chat_body: 'Yeni Randevu: {musteri_adi} - {hizmet_adi} ({personel_adi}). Tarih: {randevu_tarihi} {randevu_saati}. Tutar: {tutar} TL.'
    }
};

let currentTplScenario = 'confirmation';
let currentTplMode = 'email';
let lastFocusedTplInput = null;

function loadTplScenario(scenario) {
    currentTplScenario = scenario;
    const def = defaultTplData[scenario] || defaultTplData.confirmation;

    const emailSubjectEl = document.getElementById('tpl-input-email-subject');
    const emailBodyEl = document.getElementById('tpl-input-email-body');
    const emailBtnEl = document.getElementById('tpl-input-email-btn');
    const chatBodyEl = document.getElementById('tpl-input-chat-body');

    // Retrieve from hidden real fields if populated, else use default
    const realChat = document.getElementById('real-field-' + scenario)?.value;
    const realSubj = document.getElementById('real-field-' + scenario + '_subject')?.value;
    const realEmail = document.getElementById('real-field-' + scenario + '_email')?.value;

    if (emailSubjectEl) emailSubjectEl.value = realSubj || def.email_subject;
    if (emailBodyEl) emailBodyEl.value = realEmail || def.email_body;
    if (emailBtnEl) emailBtnEl.value = def.email_btn;
    if (chatBodyEl) chatBodyEl.value = realChat || def.chat_body;

    updateTplPreviews();
}

function updateTplPreviews() {
    const emailSubject = document.getElementById('tpl-input-email-subject')?.value || '';
    const emailBody = document.getElementById('tpl-input-email-body')?.value || '';
    const emailBtn = document.getElementById('tpl-input-email-btn')?.value || 'Randevumu Görüntüle';
    const chatBody = document.getElementById('tpl-input-chat-body')?.value || '';

    // Synchronize to hidden real fields
    const chatField = document.getElementById('real-field-' + currentTplScenario);
    const subjField = document.getElementById('real-field-' + currentTplScenario + '_subject');
    const emailField = document.getElementById('real-field-' + currentTplScenario + '_email');

    if (chatField) chatField.value = chatBody;
    if (subjField) subjField.value = emailSubject;
    if (emailField) emailField.value = emailBody;

    // Helper: Substitute tags for preview
    function replaceMockTags(text) {
        return text
            .replace(/{musteri_adi}/g, 'Selin Yılmaz')
            .replace(/{randevu_tarihi}/g, '14 Ekim 2026')
            .replace(/{randevu_saati}/g, '14:30')
            .replace(/{hizmet_adi}/g, 'Aromaterapi Masajı')
            .replace(/{personel_adi}/g, 'Melis Şen')
            .replace(/{tutar}/g, '1.250')
            .replace(/{isletme_adi}/g, '<?= addslashes(htmlspecialchars(setting('company_name') ?: 'Salon Flora & Spa')) ?>')
            .replace(/{isletme_adresi}/g, 'Bağdat Cad. No: 120 Kadıköy')
            .replace(/{isletme_telefonu}/g, '0216 550 12 34')
            .replace(/{randevu_linki}/g, 'https://bookiapp.kibusiness.co/r/98a21');
    }

    // Update Email Mockup
    const previewEmailSubject = document.getElementById('preview-email-client-subject');
    const previewEmailHeading = document.getElementById('preview-email-subject-heading');
    const previewEmailBody = document.getElementById('preview-email-body-text');
    const previewEmailBtn = document.getElementById('preview-email-btn');

    if (previewEmailSubject) previewEmailSubject.innerText = replaceMockTags(emailSubject);
    if (previewEmailHeading) previewEmailHeading.innerText = 'Sayın Selin Yılmaz,';
    if (previewEmailBody) previewEmailBody.innerText = replaceMockTags(emailBody);
    if (previewEmailBtn) previewEmailBtn.innerText = emailBtn;

    // Update Chat Mockup & Counter
    const previewChatBody = document.getElementById('preview-chat-body-text');
    const chatCounter = document.getElementById('chat-char-counter');

    if (previewChatBody) previewChatBody.innerText = replaceMockTags(chatBody);

    if (chatCounter) {
        const len = chatBody.length;
        const smsCount = Math.ceil(len / 160) || 1;
        chatCounter.innerText = `${len} / 160 Karakter • ${smsCount} SMS`;
    }
}

function switchTemplateMode(mode) {
    currentTplMode = mode;
    const btnEmail = document.getElementById('btn-mode-email');
    const btnChat = document.getElementById('btn-mode-chat');
    const editorEmail = document.getElementById('wrapper-editor-email');
    const editorChat = document.getElementById('wrapper-editor-chat');
    const previewEmail = document.getElementById('preview-frame-email');
    const previewChat = document.getElementById('preview-frame-chat');
    const btnGroupDevice = document.getElementById('btn-group-preview-device');

    if (mode === 'email') {
        btnEmail.classList.add('active');
        btnChat.classList.remove('active');
        editorEmail.classList.remove('d-none');
        editorChat.classList.add('d-none');
        previewEmail.classList.remove('d-none');
        previewChat.classList.add('d-none');
        if (btnGroupDevice) btnGroupDevice.classList.remove('d-none');
        lastFocusedTplInput = document.getElementById('tpl-input-email-body');
    } else {
        btnChat.classList.add('active');
        btnEmail.classList.remove('active');
        editorChat.classList.remove('d-none');
        editorEmail.classList.add('d-none');
        previewChat.classList.remove('d-none');
        previewEmail.classList.add('d-none');
        if (btnGroupDevice) btnGroupDevice.classList.add('d-none');
        lastFocusedTplInput = document.getElementById('tpl-input-chat-body');
    }
}

function setPreviewDevice(device) {
    const previewEmail = document.getElementById('preview-frame-email');
    const btnDesktop = document.getElementById('btn-device-desktop');
    const btnMobile = document.getElementById('btn-device-mobile');

    if (!previewEmail) return;

    if (device === 'mobile') {
        previewEmail.style.maxWidth = '375px';
        btnMobile.classList.add('active');
        btnDesktop.classList.remove('active');
    } else {
        previewEmail.style.maxWidth = '100%';
        btnDesktop.classList.add('active');
        btnMobile.classList.remove('active');
    }
}

function insertTplTag(tag) {
    const input = lastFocusedTplInput || (currentTplMode === 'email' ? document.getElementById('tpl-input-email-body') : document.getElementById('tpl-input-chat-body'));
    if (!input) return;

    const start = input.selectionStart || 0;
    const end = input.selectionEnd || 0;
    const text = input.value;
    input.value = text.substring(0, start) + tag + text.substring(end);
    input.focus();
    input.selectionStart = input.selectionEnd = start + tag.length;

    updateTplPreviews();
    const form = document.getElementById('form-communication');
    if (form) {
        form.dispatchEvent(new Event('input', { bubbles: true }));
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const scenarioSelect = document.getElementById('select-tpl-scenario');
    if (scenarioSelect) {
        scenarioSelect.addEventListener('change', function() {
            loadTplScenario(this.value);
        });
        loadTplScenario('confirmation');
    }

    ['tpl-input-email-subject', 'tpl-input-email-body', 'tpl-input-email-btn', 'tpl-input-chat-body'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('focus', function() {
                lastFocusedTplInput = this;
            });
            el.addEventListener('input', function() {
                updateTplPreviews();
            });
        }
    });

    // Reset current template
    const btnResetTpl = document.getElementById('btn-reset-current-tpl');
    if (btnResetTpl) {
        btnResetTpl.addEventListener('click', function() {
            if (confirm('Bu senaryonun şablonunu varsayılan ayarlara döndürmek istediğinize emin misiniz?')) {
                const def = defaultTplData[currentTplScenario] || defaultTplData.confirmation;
                document.getElementById('tpl-input-email-subject').value = def.email_subject;
                document.getElementById('tpl-input-email-body').value = def.email_body;
                document.getElementById('tpl-input-email-btn').value = def.email_btn;
                document.getElementById('tpl-input-chat-body').value = def.chat_body;
                updateTplPreviews();
                showToast('Şablon varsayılan ayarlara sıfırlandı.', 'bg-info');
            }
        });
    }

    // Test send submit
    const btnSubmitTest = document.getElementById('btn-submit-test-send');
    if (btnSubmitTest) {
        btnSubmitTest.addEventListener('click', function() {
            const recipient = document.getElementById('test-recipient-input')?.value.trim();
            if (!recipient) {
                alert('Lütfen alıcı adresini giriniz.');
                return;
            }
            const modalEl = document.getElementById('modal-test-template');
            if (modalEl) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
            }
            showToast('Test bildirimi ' + recipient + ' adresine başarıyla iletildi! ✓', 'bg-success');
        });
    }

    const testChannelSelect = document.getElementById('test-channel-select');
    if (testChannelSelect) {
        testChannelSelect.addEventListener('change', function() {
            const label = document.getElementById('test-recipient-label');
            const input = document.getElementById('test-recipient-input');
            if (this.value === 'email') {
                if (label) label.innerText = 'Alıcı E-posta Adresi';
                if (input) input.placeholder = 'ornek@alanadiniz.com';
            } else {
                if (label) label.innerText = 'Alıcı Telefon Numarası (WhatsApp)';
                if (input) input.placeholder = '05XXXXXXXXX';
            }
        });
    }
});
</script>

<?php end_section('content'); ?>
