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
                                    <?php if ($section_key === 'business' && $sub_key === 'hours'): ?>
                                        <?php
                                        $days_map = [
                                            'monday' => lang('monday') ?: 'Pazartesi',
                                            'tuesday' => lang('tuesday') ?: 'Salı',
                                            'wednesday' => lang('wednesday') ?: 'Çarşamba',
                                            'thursday' => lang('thursday') ?: 'Perşembe',
                                            'friday' => lang('friday') ?: 'Cuma',
                                            'saturday' => lang('saturday') ?: 'Cumartesi',
                                            'sunday' => lang('sunday') ?: 'Pazar',
                                        ];
                                        $current_breaks = $working_plan['monday']['breaks'] ?? [];
                                        ?>
                                        <!-- 1. Haftalık Çalışma Saatleri -->
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark"><?= lang('settings_weekly_working_plan') ?></h5>
                                                    <p class="text-muted small mb-0"><?= lang('settings_weekly_working_plan_desc') ?></p>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-apply-global-plan">
                                                        <i class="fas fa-users-cog me-1"></i> <?= lang('settings_apply_plan_to_all_providers') ?>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-success btn-save-section-direct">
                                                        <i class="fas fa-save me-1"></i> <?= lang('save') ?>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body p-4">
                                                <div class="table-responsive">
                                                    <table class="table table-hover align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th style="width: 25%"><?= lang('day') ?: 'Gün' ?></th>
                                                                <th style="width: 25%"><?= lang('status') ?: 'Durum' ?></th>
                                                                <th style="width: 25%"><?= lang('start') ?: 'Başlangıç' ?></th>
                                                                <th style="width: 25%"><?= lang('end') ?: 'Bitiş' ?></th>
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
                                                                            <label class="form-check-label small wp-status-text" for="wp_open_<?= $d_key ?>">
                                                                                <?= $is_d_open ? (lang('open') ?: 'Açık') : (lang('closed') ?: 'Kapalı') ?>
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

                                        <!-- 2. Günlük Molalar -->
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark"><?= lang('settings_breaks_title') ?></h5>
                                                    <p class="text-muted small mb-0"><?= lang('settings_breaks_desc') ?></p>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-break">
                                                    <i class="fas fa-plus me-1"></i> <?= lang('settings_add_break') ?>
                                                </button>
                                            </div>
                                            <div class="card-body p-4">
                                                <div id="breaks-container">
                                                    <?php if (empty($current_breaks)): ?>
                                                        <p class="text-muted small mb-0" id="no-breaks-hint"><?= lang('edit_breaks_hint') ?: 'Tanımlı mola bulunmamaktadır. Eklemek için yukarıdaki butonu kullanın.' ?></p>
                                                    <?php else: ?>
                                                        <?php foreach ($current_breaks as $b_idx => $b_item): ?>
                                                            <div class="d-flex align-items-center gap-2 mb-2 break-row">
                                                                <span class="badge bg-light text-dark border px-2 py-1"><i class="fas fa-coffee me-1"></i> Mola</span>
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

                                        <!-- 3. İşletme Tatilleri & Kapalı Dönemler (Blocked Periods) -->
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark"><?= lang('settings_holidays_and_blocked_title') ?></h5>
                                                    <p class="text-muted small mb-0"><?= lang('settings_holidays_and_blocked_desc') ?></p>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-blocked-period">
                                                    <i class="fas fa-plus me-1"></i> <?= lang('settings_add_holiday_btn') ?>
                                                </button>
                                            </div>
                                            <div class="card-body p-4">
                                                <div id="blocked-periods-table-wrapper">
                                                    <?php if (empty($blocked_periods)): ?>
                                                        <div class="alert alert-light border text-muted small mb-0" id="no-blocked-periods-alert">
                                                            <i class="fas fa-info-circle me-1 text-primary"></i> <?= lang('settings_no_holidays_defined') ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="table-responsive">
                                                            <table class="table table-hover align-middle mb-0" id="table-blocked-periods">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th><?= lang('name') ?: 'Başlık' ?></th>
                                                                        <th><?= lang('start') ?: 'Başlangıç' ?></th>
                                                                        <th><?= lang('end') ?: 'Bitiş' ?></th>
                                                                        <th><?= lang('notes') ?: 'Notlar' ?></th>
                                                                        <th class="text-end"><?= lang('actions') ?: 'İşlem' ?></th>
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
                                                                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-blocked-period" data-id="<?= $bp['id'] ?>" title="<?= lang('delete') ?: 'Sil' ?>">
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

                                        <!-- 4. Personel İstisnaları (Working Plan Exceptions for Staff) -->
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark"><?= lang('settings_staff_exceptions_title') ?></h5>
                                                    <p class="text-muted small mb-0"><?= lang('settings_staff_exceptions_desc') ?></p>
                                                </div>
                                                <div>
                                                    <a href="<?= site_url('providers') ?>" class="btn btn-sm btn-outline-secondary me-2">
                                                        <i class="fas fa-user-clock me-1"></i> <?= lang('settings_go_to_team') ?>
                                                    </a>
                                                    <a href="<?= site_url('calendar') ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-calendar-alt me-1"></i> <?= lang('settings_go_to_calendar') ?>
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="card-body p-4">
                                                <?php if (empty($working_plan_exceptions)): ?>
                                                    <div class="alert alert-light border text-muted small mb-0">
                                                        <i class="fas fa-check-circle text-success me-1"></i> <?= lang('settings_staff_exceptions_empty') ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="table-responsive">
                                                        <table class="table table-hover align-middle mb-0">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th><?= lang('provider') ?: 'Personel' ?></th>
                                                                    <th><?= lang('start_date') ?: 'Başlangıç' ?></th>
                                                                    <th><?= lang('end_date') ?: 'Bitiş' ?></th>
                                                                    <th><?= lang('hours') ?: 'Saatler' ?></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($working_plan_exceptions as $wpe): ?>
                                                                    <?php
                                                                    $prov_name = '-';
                                                                    foreach ($providers as $pr) {
                                                                        if ((int)$pr['id'] === (int)($wpe['id_users_provider'] ?? 0)) {
                                                                            $prov_name = $pr['first_name'] . ' ' . $pr['last_name'];
                                                                            break;
                                                                        }
                                                                    }
                                                                    ?>
                                                                    <tr>
                                                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($prov_name) ?></td>
                                                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($wpe['start_date']) ?></span></td>
                                                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($wpe['end_date']) ?></span></td>
                                                                        <td class="small text-muted">
                                                                            <?= !empty($wpe['start_time']) ? htmlspecialchars($wpe['start_time'] . ' - ' . $wpe['end_time']) : (lang('non_working_day') ?: 'İzinli / Kapalı') ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4 d-flex justify-content-between align-items-center">
                                                <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($sub_title) ?></h5>
                                                <button type="button" class="btn btn-sm btn-success btn-save-section-direct">
                                                    <i class="fas fa-save me-1"></i> <?= lang('save') ?>
                                                </button>
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
                                    <?php endif; ?>
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

<!-- Modal: Add Blocked Period / Holiday -->
<div class="modal fade" id="modal-add-blocked-period" tabindex="-1" aria-labelledby="modalAddBlockedPeriodLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark" id="modalAddBlockedPeriodLabel">
                    <i class="fas fa-calendar-times text-primary me-2"></i><?= lang('settings_modal_add_holiday_title') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="form-add-blocked-period">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark" for="bp-name">
                            <?= lang('settings_holiday_name') ?> <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="bp-name" required placeholder="<?= lang('settings_holiday_name_placeholder') ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-bold text-dark" for="bp-start">
                                <?= lang('start') ?> <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" class="form-control" id="bp-start" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-bold text-dark" for="bp-end">
                                <?= lang('end') ?> <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" class="form-control" id="bp-end" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark" for="bp-notes">
                            <?= lang('notes') ?>
                        </label>
                        <textarea class="form-control" id="bp-notes" rows="2" placeholder="<?= lang('notes') ?>..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top py-3 px-4">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= lang('cancel') ?></button>
                <button type="button" class="btn btn-primary px-4 fw-semibold" id="btn-submit-blocked-period">
                    <i class="fas fa-check me-1"></i> <?= lang('save') ?>
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

    // Working Plan Toggle (Open / Closed)
    document.querySelectorAll('.wp-day-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const day = this.dataset.day;
            const isOpen = this.checked;
            const startInput = document.getElementById('wp_start_' + day);
            const endInput = document.getElementById('wp_end_' + day);
            const statusLabel = this.closest('td').querySelector('.wp-status-text');
            if (startInput) startInput.disabled = !isOpen;
            if (endInput) endInput.disabled = !isOpen;
            if (statusLabel) statusLabel.innerText = isOpen ? 'Açık' : 'Kapalı';
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
                <span class="badge bg-light text-dark border px-2 py-1"><i class="fas fa-coffee me-1"></i> Mola</span>
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

    // Breaks: Remove Break (delegated)
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
            if (!confirm(i18n.settings_apply_plan_confirm || 'Bu çalışma planı tüm hizmet veren personelin takvimine kopyalanacaktır. Emin misiniz?')) {
                return;
            }

            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Senkronize ediliyor...';

            // Gather current plan
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
                btn.innerHTML = '<i class="fas fa-users-cog me-1"></i> ' + (i18n.settings_apply_plan_to_all_providers || 'Bu Planı Tüm Personele Senkronize Et');
                if (data.success) {
                    showToast(data.message || i18n.plan_applied || 'Çalışma planı başarıyla personele uygulandı!', 'bg-success');
                } else {
                    showToast(data.message || 'İşlem başarısız oldu.', 'bg-danger');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-users-cog me-1"></i> ' + (i18n.settings_apply_plan_to_all_providers || 'Bu Planı Tüm Personele Senkronize Et');
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
                    // Close modal
                    const modalEl = document.getElementById('modal-add-blocked-period');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    document.getElementById('form-add-blocked-period').reset();

                    // Append row to table
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

                    showToast(data.message || i18n.holiday_added || 'Tatil / Kapalı dönem başarıyla eklendi!', 'bg-success');
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

    // Delete Blocked Period (delegated)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-delete-blocked-period');
        if (btn) {
            const id = btn.dataset.id;
            if (!confirm(i18n.delete_confirm || 'Bu kaydı silmek istediğinize emin misiniz?')) {
                return;
            }

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
                    showToast(data.message || i18n.holiday_deleted || 'Kayıt başarıyla silindi!', 'bg-success');
                } else {
                    showToast(data.message || 'Silme işlemi başarısız oldu.', 'bg-danger');
                }
            })
            .catch(err => {
                showToast('Hata: ' + err.message, 'bg-danger');
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
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + (i18n.saving || 'Kaydediliyor...');

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

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

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
            showToast(i18n.rotate_success || 'Yeni Agent API anahtarı başarıyla üretildi!', 'bg-success');
        } else {
            showToast(d.message || 'Hata oluştu', 'bg-danger');
        }
    });
}

// Connectivity test
function testConnection() {
    const i18n = window.vars('i18n') || {};
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
