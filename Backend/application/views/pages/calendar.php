<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container-fluid backend-page pt-2 pb-0" id="calendar-page">
    <!-- Live Availability Strip Container (Terapistler & Odalar) -->
    <div id="next-availability-container" class="mb-3">
        <!-- Next availability widget mounts here dynamically -->
    </div>

    <!-- Calendar Controls & Actions Toolbar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 p-3 bg-white rounded-3 shadow-sm border" id="calendar-toolbar">
        <div id="calendar-filter" class="d-flex align-items-center gap-2">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted" style="border-radius: 8px 0 0 8px;">
                    <i class="fas fa-filter text-primary"></i>
                </span>
                <select id="select-filter-item"
                        class="form-select border-start-0 ps-1"
                        style="min-width: 240px; border-radius: 0 8px 8px 0; font-weight: 500;"
                        data-tippy-content="<?= lang('select_filter_item_hint') ?>"
                        aria-label="Filter">
                    <!-- JS -->
                </select>
            </div>
        </div>

        <div id="calendar-actions" class="d-flex flex-wrap align-items-center gap-2">
            <?php if (vars('calendar_view') === CALENDAR_VIEW_DEFAULT): ?>
                <button
                    id="enable-sync"
                    class="btn btn-outline-secondary btn-sm px-3"
                    data-tippy-content="<?= lang('enable_appointment_sync_hint') ?>"
                    hidden>
                    <i class="fas fa-rotate me-1"></i>
                    <?= lang('enable_sync') ?>
                </button>

                <div class="btn-group" id="sync-button-group" hidden>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="trigger-sync"
                            data-tippy-content="<?= lang('trigger_sync_hint') ?>">
                        <i class="fas fa-rotate me-1"></i>
                        <?= lang('synchronize') ?>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle dropdown-toggle-split"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li>
                            <a class="dropdown-item py-2" href="#" id="disable-sync">
                                <i class="fas fa-ban text-danger me-2"></i><?= lang('disable_sync') ?>
                            </a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>

            <button id="reload-appointments" class="btn btn-outline-secondary btn-sm px-3 py-2 text-dark shadow-sm d-inline-flex align-items-center"
                    data-tippy-content="<?= lang('reload_appointments_hint') ?>">
                <i class="fas fa-sync-alt text-secondary"></i>
            </button>

            <?php if (vars('calendar_view') === CALENDAR_VIEW_DEFAULT): ?>
                <a class="btn btn-outline-secondary btn-sm px-3 py-2 text-dark shadow-sm d-inline-flex align-items-center gap-1" href="<?= site_url('calendar?view=table') ?>"
                   data-tippy-content="<?= lang('table') ?>">
                    <i class="fas fa-table-columns text-secondary me-1"></i>
                    <span class="d-none d-md-inline small fw-semibold"><?= lang('table') ?></span>
                </a>
            <?php endif; ?>

            <?php if (vars('calendar_view') === CALENDAR_VIEW_TABLE): ?>
                <a class="btn btn-outline-secondary btn-sm px-3 py-2 text-dark shadow-sm d-inline-flex align-items-center gap-1" href="<?= site_url('calendar?view=default') ?>"
                   data-tippy-content="<?= lang('default') ?>">
                    <i class="fas fa-calendar-alt text-secondary me-1"></i>
                    <span class="d-none d-md-inline small fw-semibold"><?= lang('default') ?></span>
                </a>
            <?php endif; ?>

            <?php if (can('add', PRIV_APPOINTMENTS)): ?>
                <div class="dropdown d-inline-block">
                    <button class="btn btn-primary btn-sm px-3 py-2 fw-semibold shadow-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-plus me-1"></i> <?= lang('appointment') ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li>
                            <a class="dropdown-item py-2" href="#" id="insert-appointment">
                                <i class="fas fa-calendar-plus text-primary me-2"></i><?= lang('appointment') ?>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="#" id="insert-unavailability">
                                <i class="fas fa-coffee text-warning me-2"></i><?= lang('unavailability') ?>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="#"
                               id="insert-working-plan-exception" <?= session('role_slug') !== DB_SLUG_ADMIN ? 'hidden' : '' ?>>
                                <i class="fas fa-calendar-times text-danger me-2"></i><?= lang('working_plan_exception') ?>
                            </a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="calendar-shell">
        <div id="calendar">
            <!-- Dynamically Generated Content -->
        </div>
    </div>
</div>

<!-- Page Components -->

<?php component('appointments_modal', [
    'available_services' => vars('available_services'),
    'appointment_status_options' => vars('appointment_status_options'),
    'timezones' => vars('timezones'),
    'require_first_name' => vars('require_first_name'),
    'require_last_name' => vars('require_last_name'),
    'require_email' => vars('require_email'),
    'require_phone_number' => vars('require_phone_number'),
    'require_address' => vars('require_address'),
    'require_city' => vars('require_city'),
    'require_zip_code' => vars('require_zip_code'),
    'require_notes' => vars('require_notes'),
]); ?>

<?php component('unavailabilities_modal', [
    'timezones' => vars('timezones'),
    'timezone' => vars('timezone'),
]); ?>

<?php component('working_plan_exceptions_modal'); ?>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/vendor/fullcalendar/index.global.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/fullcalendar-moment/index.global.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/jquery-jeditable/jquery.jeditable.min.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/ui.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/calendar_default_view.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/calendar_table_view.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/calendar_event_popover.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/calendar_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/customers_http_client.js') ?>"></script>
<?php if (vars('calendar_view') === CALENDAR_VIEW_DEFAULT): ?>
    <script src="<?= asset_url('assets/js/utils/calendar_sync.js') ?>"></script>
    <script src="<?= asset_url('assets/js/http/google_http_client.js') ?>"></script>
    <script src="<?= asset_url('assets/js/http/caldav_http_client.js') ?>"></script>
<?php endif; ?>
<script src="<?= asset_url('assets/js/pages/calendar.js') ?>"></script>

<?php end_section('scripts'); ?>

