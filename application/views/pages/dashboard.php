<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php
/**
 * @var array $summary
 * @var string $today_label
 */
$summary = vars('summary');
$status_badge = [
    'Confirmed' => 'kcc-status-green',
    'Booked' => 'kcc-status-green',
    'Tamamlandı' => 'kcc-status-blue',
    'Bekliyor' => 'kcc-status-amber',
];
?>

<div class="container-fluid backend-page py-4" id="dashboard-page">

    <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1 fw-bold">Günaydın, <?= e(vars('user_display_name')) ?></h1>
            <p class="text-muted mb-0">Bugün <?= e(setting('company_name') ?: 'işletmeniz') ?> için olan bitene genel bakış.</p>
        </div>
        <div class="text-muted small"><?= e($today_label ?? vars('today_label')) ?></div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold">İlk Müsaitlik</span>
            </div>
            <div id="next-availability-strip">
                <span class="text-muted small">Yükleniyor...</span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card kcc-kpi h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start text-muted small fw-semibold">
                        <span>Bugünkü Randevular</span>
                        <span class="kcc-kpi-icon kcc-kpi-icon-blue"><i class="fas fa-calendar-alt"></i></span>
                    </div>
                    <div class="kcc-kpi-value"><?= (int) $summary['appointment_count'] ?></div>
                    <span class="kcc-trend neutral">bugün için planlanan</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kcc-kpi h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start text-muted small fw-semibold">
                        <span>Bugünkü Gelir</span>
                        <span class="kcc-kpi-icon kcc-kpi-icon-amber">₺</span>
                    </div>
                    <div class="kcc-kpi-value">₺<?= number_format((float) $summary['revenue_collected'], 0, ',', '.') ?></div>
                    <span class="kcc-trend <?= $summary['revenue_pending'] > 0 ? 'warn' : 'neutral' ?>">
                        ₺<?= number_format((float) $summary['revenue_pending'], 0, ',', '.') ?> bekliyor
                    </span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kcc-kpi h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start text-muted small fw-semibold">
                        <span>Aktif Seanslar</span>
                        <span class="kcc-kpi-icon kcc-kpi-icon-coral"><i class="fas fa-stopwatch"></i></span>
                    </div>
                    <div class="kcc-kpi-value" id="dash-active-count"><?= (int) $summary['active_sessions_count'] ?></div>
                    <span class="kcc-trend neutral" id="dash-active-sub">canlı</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kcc-kpi h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start text-muted small fw-semibold">
                        <span>Doluluk</span>
                        <span class="kcc-kpi-icon kcc-kpi-icon-violet">%</span>
                    </div>
                    <div class="kcc-kpi-value"><?= $summary['occupancy_pct'] !== null ? $summary['occupancy_pct'] . '%' : '—' ?></div>
                    <span class="kcc-trend neutral">bugün</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="h6 mb-0 fw-bold">Bugünün Akışı</h3>
                    <span class="text-muted small"><?= (int) $summary['appointment_count'] ?> randevu · <?= (int) $summary['waiting_count'] ?> bekliyor</span>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($summary['appointments'])): ?>
                        <div class="list-group-item text-muted small py-4 text-center">Bugün için planlanmış randevu yok.</div>
                    <?php endif; ?>
                    <?php foreach ($summary['appointments'] as $appt): ?>
                        <a href="<?= site_url('calendar') ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                            <div class="fw-bold text-muted small" style="width:48px"><?= e($appt['start_time']) ?></div>
                            <div class="flex-grow-1">
                                <div class="fw-bold"><?= e($appt['customer_name']) ?></div>
                                <div class="text-muted small"><?= e($appt['service_name']) ?> · <?= e($appt['provider_name']) ?> · <?= e($appt['station_name']) ?></div>
                            </div>
                            <span class="badge <?= $status_badge[$appt['status']] ?? 'kcc-status-gray' ?>"><?= e($appt['status']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer bg-transparent border-0 pb-3">
                    <a href="<?= site_url('calendar') ?>" class="btn btn-outline-primary w-100">Takvimi Görüntüle →</a>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="h6 mb-0 fw-bold">Canlı Seanslar</h3>
                    <span class="text-muted small" id="dash-live-count-label"><?= (int) $summary['active_sessions_count'] ?> aktif</span>
                </div>
                <div id="dash-live-sessions" class="list-group list-group-flush">
                    <div class="list-group-item text-muted small py-4 text-center">Yükleniyor…</div>
                </div>
                <div class="card-footer bg-transparent border-0 pb-3">
                    <a href="<?= site_url('calendar') ?>" class="btn btn-primary w-100">Aktif Seansları Yönet →</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="h6 mb-0 fw-bold">Dikkat Gerektirenler</h3>
                </div>
                <div id="dash-attention" class="list-group list-group-flush">
                    <?php if ($summary['payment_missing_count'] > 0): ?>
                        <a href="<?= site_url('calendar') ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                            <i class="fas fa-circle text-danger" style="font-size:8px"></i>
                            <div class="flex-grow-1">
                                <div class="fw-bold small"><?= (int) $summary['payment_missing_count'] ?> ödeme eksik</div>
                                <div class="text-muted small">Bugün tamamlanan seanslardan</div>
                            </div>
                            <span class="text-primary small fw-bold">Görüntüle →</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($summary['waiting_count'] > 0): ?>
                        <a href="<?= site_url('waitlist') ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                            <i class="fas fa-circle text-warning" style="font-size:8px"></i>
                            <div class="flex-grow-1">
                                <div class="fw-bold small"><?= (int) $summary['waiting_count'] ?> bekleme talebi</div>
                                <div class="text-muted small">Dolu saatler için müşteri bekliyor</div>
                            </div>
                            <span class="text-primary small fw-bold">Görüntüle →</span>
                        </a>
                    <?php endif; ?>
                    <div id="dash-attention-overdue"></div>
                    <?php if ($summary['payment_missing_count'] === 0 && $summary['waiting_count'] === 0): ?>
                        <div class="list-group-item text-muted small py-4 text-center" id="dash-attention-empty">Şu an dikkat gerektiren bir konu yok.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <strong>Hızlı İşlemler</strong>
                <div class="text-muted small">En sık kullandığınız operasyonlara tek tıkla erişin.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= site_url('calendar') ?>" class="btn btn-primary btn-sm">+ Yeni Randevu</a>
                <a href="<?= site_url('customers') ?>" class="btn btn-outline-secondary btn-sm">+ Müşteri</a>
                <a href="<?= site_url('calendar') ?>" class="btn btn-outline-secondary btn-sm">₺ Ödeme Al</a>
                <a href="<?= site_url('waitlist') ?>" class="btn btn-outline-secondary btn-sm">+ Bekleme Talebi</a>
            </div>
        </div>
    </div>

</div>

<style>
    .kcc-status-green { background: #eef7f3; color: #29936f; }
    .kcc-status-amber { background: #fff6e5; color: #c68a28; }
    .kcc-status-blue { background: #eef3fb; color: #4c78b8; }
    .kcc-status-gray { background: #eef0ef; color: #78827f; }
</style>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<!-- Needed by the global next_availability_widget.js for the "İlk Müsaitlik" strip above -
     calendar.php loads this too, but dashboard.php previously didn't since it has no calendar
     of its own. -->
<script src="<?= asset_url('assets/js/http/calendar_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/dashboard.min.js') ?>"></script>

<?php end_section('scripts'); ?>
