<!doctype html>
<html lang="<?= config('language_code') ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <meta name="theme-color" content="#35A768">
    <meta name="google" content="notranslate">

    <?php slot('meta'); ?>

    <title><?= vars('page_title') ?? lang('backend_section') ?></title>

    <link rel="icon" type="image/x-icon" href="<?= asset_url('assets/img/favicon.ico') ?>">
    <link rel="icon" sizes="192x192" href="<?= asset_url('assets/img/logo.png') ?>">

    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/vendor/trumbowyg/trumbowyg.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/vendor/select2/select2.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/vendor/flatpickr/flatpickr.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/vendor/flatpickr/material_green.min.css') ?>">
    <link rel="stylesheet" type="text/css"
          href="<?= asset_url('assets/css/themes/' . setting('theme', 'default') . '.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/general.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/backend.css') ?>">

    <?php component('company_color_style', ['company_color' => setting('company_color')]); ?>

    <?php slot('styles'); ?>
</head>
<body class="d-flex flex-column h-100">

<?php component('backend_header', ['active_menu' => vars('active_menu')]); ?>

<main class="flex-shrink-0" id="main-content">

    <?php
    // Ki Reservation customization (2026-08-24) - license status banner. Admin-only (a provider/
    // secretary has no ability to act on a licensing issue), and deliberately non-blocking - see
    // Licensing.php's docblock for why this never prevents the page itself from loading/working.
    if (session('role_slug') === DB_SLUG_ADMIN) {
        try {
            $CI = &get_instance();
            $CI->load->library('licensing');
            $license_status = $CI->licensing->status();

            if (in_array($license_status['state'], ['missing', 'grace', 'expired', 'invalid'], true)) {
                $banner_class = $license_status['state'] === 'expired' ? 'alert-danger' : 'alert-warning';
                ?>
                <div class="alert <?= $banner_class ?> mb-0 rounded-0 text-center py-2">
                    <strong>Lisans:</strong> <?= e($license_status['message']) ?>
                    <a href="<?= site_url('license') ?>" class="alert-link">Lisans durumunu görüntüle</a>
                </div>
                <?php
            }
        } catch (Throwable $e) {
            log_message('error', 'License banner check failed: ' . $e->getMessage());
        }
    }
    ?>

    <?php slot('content'); ?>

</main>

<?php component('backend_footer', ['user_display_name' => vars('user_display_name')]); ?>

<script src="<?= asset_url('assets/vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/@popperjs-core/popper.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/bootstrap/bootstrap.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/moment/moment.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/moment-timezone/moment-timezone-with-data.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/@fortawesome-fontawesome-free/fontawesome.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/@fortawesome-fontawesome-free/solid.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/tippy.js/tippy-bundle.umd.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/trumbowyg/trumbowyg.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/select2/select2.min.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/flatpickr/flatpickr.min.js') ?>"></script>

<script src="<?= asset_url('assets/js/app.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/date.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/file.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/http.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/lang.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/message.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/string.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/url.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/validation.js') ?>"></script>
<script src="<?= asset_url('assets/js/layouts/backend_layout.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/localization_http_client.js') ?>"></script>

<!-- Salon Flora customization - session tracking (check-in/out countdown, deviation dialog). Loaded here rather
     than in the (stock, un-overridden) calendar page view so we don't have to override that file too. -->
<script src="<?= asset_url('assets/js/utils/session_status.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/session_actions.js') ?>"></script>
<script src="<?= asset_url('assets/js/components/active_sessions_widget.js') ?>"></script>
<script src="<?= asset_url('assets/js/components/next_availability_widget.js') ?>"></script>

<style>
    /* Ki Reservation (2026-09-10) - sidebar navigation. Mobile-first: #sidebar is a Bootstrap
       offcanvas (off-screen drawer) by default; the media query below turns it into a normal,
       always-visible, fixed-position left column at/above the "md" breakpoint (768px), matching
       Bootstrap's own `offcanvas-md` behavior contract. */
    #sidebar .nav-link { padding: .55rem .75rem; border-radius: 6px; font-weight: 300; }
    #sidebar .nav-link:hover { background: rgba(255, 255, 255, 0.08); }
    #sidebar .nav-item.active > .nav-link { background: rgba(255, 255, 255, 0.16); font-weight: 600; }
    #sidebar .nav-link .fa-chevron-down { transition: transform .2s ease; }
    #sidebar .nav-link[aria-expanded="true"] .fa-chevron-down { transform: rotate(180deg); }

    /* Ki Reservation (2026-09-10) - Bootstrap's own `.offcanvas-md` breakpoint rules force
       .offcanvas-body to `flex-grow:0; overflow-y:visible` at >=768px (it assumes a "static, just
       render inline" mode, not a persistent full-height column) - that broke both "push the account
       block to the bottom" and "only the middle nav list scrolls". These overrides apply at every
       width (not only >=768px) so the same single-scroll-container behavior is consistent in the
       mobile offcanvas drawer too. */
    #sidebar,
    #sidebar .offcanvas-body {
        display: flex !important;
        flex-direction: column !important;
    }
    #sidebar .offcanvas-body {
        flex-grow: 1 !important;
        overflow-y: hidden !important;
        padding: 0 !important;
    }
    #sidebar .sidebar-nav {
        flex: 1 1 auto;
        min-height: 0; /* let the flex child actually shrink so overflow-y:auto can kick in */
        overflow-y: auto;
    }
    @media (min-width: 768px) {
        #sidebar {
            width: 230px !important;
            position: fixed !important;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100;
        }
        #main-content, #footer { margin-left: 230px; }
    }

    /* Salon Flora customization - live session status indicators on calendar appointment events. */
    .fc-event.sf-session-running {
        border-left: 4px solid #28a745 !important;
    }
    .fc-event.sf-session-ending-soon {
        border-left: 4px solid #fd7e14 !important;
        animation: sf-session-pulse 1.6s ease-in-out infinite;
    }
    .fc-event.sf-session-overdue {
        border-left: 4px solid #dc3545 !important;
        background-color: rgba(220, 53, 69, 0.18) !important;
    }
    .fc-event.sf-session-late-start {
        border-left: 4px dashed #6c757d !important;
    }
    .fc-event.sf-session-done {
        opacity: 0.6;
    }
    /* Salon Flora customization - persistent "eksik tahsilat" warning: the session is over but payment was never
       recorded. Stacks on top of whichever sf-session-* class also applies. */
    .fc-event.sf-payment-missing {
        box-shadow: inset 4px 0 0 #dc3545, 0 0 0 2px #dc3545 !important;
    }
    .fc-event.sf-payment-missing .fc-event-title::after {
        content: " \20BA!";
        color: #dc3545;
        font-weight: 700;
    }
    /* Salon Flora customization - admin/secretary force-saved this appointment despite a busy therapist
       and/or station (see Calendar.php::save_appointment(), conflict_override column). Stacks on top of
       whichever sf-session-* class also applies. */
    .fc-event.sf-conflict-override {
        background-image: repeating-linear-gradient(
            45deg,
            rgba(253, 126, 20, 0.35),
            rgba(253, 126, 20, 0.35) 6px,
            transparent 6px,
            transparent 12px
        ) !important;
    }
    .fc-event.sf-conflict-override .fc-event-title::before {
        content: "\26A0 ";
    }
    @keyframes sf-session-pulse {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(253, 126, 20, 0.4);
        }
        50% {
            box-shadow: 0 0 0 4px rgba(253, 126, 20, 0);
        }
    }

    /* Salon Flora customization - sequential booking form step locking (appointments_modal.php). A locked step
       stays visible (so staff see what's coming) but is dimmed and inert until the previous step is complete. */
    .sf-step.sf-step-locked {
        opacity: 0.45;
        pointer-events: none;
    }
    .sf-step-badge {
        font-weight: 400;
    }
    .sf-step:not(.sf-step-locked) .sf-step-badge {
        background-color: #198754 !important;
    }
</style>

<?php
// Salon Flora customization - session-tracking script vars (payment dialog gating, deviation
// thresholds) used to be set only inside Calendar::index(), so any backend page other than the
// calendar had them undefined - active_sessions_widget.js/session_status.js still run there
// (backend_footer loads them on every page) and silently skipped the mandatory payment dialog on
// check-out. Provide the same defaults here, on every backend page, without clobbering Calendar's
// own (identical) values - script_vars() merges by key, so this only fills in what's missing.
if (!array_key_exists('can_manage_payment', script_vars())) {
    script_vars([
        'session_thresholds' => [
            'tolerance_minutes' => (int) setting('session_deviation_tolerance_minutes', SESSION_DEVIATION_TOLERANCE_MINUTES_DEFAULT),
            'duration_baseline' => setting('session_duration_baseline', 'check_in'),
            'warning_minutes' => SESSION_WARNING_THRESHOLD_MINUTES,
            'late_start_grace_minutes' => SESSION_LATE_START_GRACE_MINUTES,
            'end_prompt_snooze_minutes' => SESSION_END_PROMPT_SNOOZE_MINUTES,
        ],
        'early_exit_reason_codes' => EARLY_EXIT_REASON_CODES,
        'payment_methods' => [
            ['value' => 'iban', 'label' => 'IBAN'],
            ['value' => 'physical_pos', 'label' => 'Fiziki POS'],
            ['value' => 'virtual_pos', 'label' => 'Sanal POS'],
            ['value' => 'cash', 'label' => 'Nakit'],
        ],
        'can_manage_payment' => session('role_slug') !== DB_SLUG_PROVIDER,
    ]);
}
?>
<?php component('js_vars_script'); ?>
<?php component('js_lang_script'); ?>

<?php slot('scripts'); ?>

</body>
</html>
