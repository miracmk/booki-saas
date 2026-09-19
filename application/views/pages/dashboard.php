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
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h1 class="h3 mb-0 fw-bold">Günaydın, <?= e(vars('user_display_name')) ?></h1>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-3 py-1 rounded-pill">
                    <span class="me-1"><?= $summary['industry_info']['icon'] ?? '✨' ?></span>
                    <?= e($summary['industry_config']['badge'] ?? ($summary['industry_info']['name'] ?? 'İşletme')) ?>
                </span>
            </div>
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
                        <span><?= e($summary['industry_config']['kpi_1_title'] ?? 'Bugünkü Randevular') ?></span>
                        <span class="kcc-kpi-icon kcc-kpi-icon-blue"><i class="fas fa-calendar-alt"></i></span>
                    </div>
                    <div class="kcc-kpi-value"><?= (int) $summary['appointment_count'] ?></div>
                    <span class="kcc-trend neutral"><?= e($summary['industry_config']['kpi_1_sub'] ?? 'bugün için planlanan') ?></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kcc-kpi h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start text-muted small fw-semibold">
                        <span><?= e($summary['industry_config']['kpi_2_title'] ?? 'Bugünkü Gelir') ?></span>
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
                        <span><?= e($summary['industry_config']['kpi_3_title'] ?? 'Aktif Seanslar') ?></span>
                        <span class="kcc-kpi-icon kcc-kpi-icon-coral"><i class="fas fa-stopwatch"></i></span>
                    </div>
                    <div class="kcc-kpi-value" id="dash-active-count">
                        <?php if ($summary['industry_code'] === 'restaurant' && !empty($summary['open_adisyons_count'])): ?>
                            <?= (int) $summary['open_adisyons_count'] ?>
                        <?php else: ?>
                            <?= (int) $summary['active_sessions_count'] ?>
                        <?php endif; ?>
                    </div>
                    <span class="kcc-trend neutral" id="dash-active-sub"><?= e($summary['industry_config']['kpi_3_sub'] ?? 'canlı') ?></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kcc-kpi h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start text-muted small fw-semibold">
                        <span><?= e($summary['industry_config']['kpi_4_title'] ?? 'Doluluk') ?></span>
                        <span class="kcc-kpi-icon kcc-kpi-icon-violet">%</span>
                    </div>
                    <div class="kcc-kpi-value">
                        <?php if ($summary['industry_code'] === 'restaurant' && !empty($summary['total_tables_count'])): ?>
                            <?= round(($summary['occupied_tables_count'] / max($summary['total_tables_count'], 1)) * 100) ?>%
                        <?php else: ?>
                            <?= $summary['occupancy_pct'] !== null ? $summary['occupancy_pct'] . '%' : '—' ?>
                        <?php endif; ?>
                    </div>
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
                    <span class="text-muted small"><?= (int) $summary['appointment_count'] ?> <?= e(strtolower($summary['terminology']['appointment_label'] ?? 'randevu')) ?> · <?= (int) $summary['waiting_count'] ?> bekliyor</span>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($summary['appointments'])): ?>
                        <div class="list-group-item text-muted small py-4 text-center">Bugün için planlanmış <?= e(strtolower($summary['terminology']['appointment_label'] ?? 'randevu')) ?> yok.</div>
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
                    <?php if (!empty($summary['ai_pending_items'])): ?>
                        <div class="list-group-item bg-light-subtle py-2 d-flex justify-content-between align-items-center">
                            <span class="small fw-bold text-primary"><i class="fas fa-robot me-1"></i> AI Asistan — Onay Bekleyen İşlemler (<span id="dash-ai-count"><?= (int) $summary['ai_pending_count'] ?></span>)</span>
                            <a href="<?= site_url('ai_agent') ?>" class="small text-decoration-none">Tümünü Gör →</a>
                        </div>
                        <?php foreach ($summary['ai_pending_items'] as $item): ?>
                            <?php 
                                $changes = json_decode((string) $item['changes'], true) ?: []; 
                                $is_appt = ($item['target_table'] === 'appointments');
                                $action = $changes['action'] ?? ($is_appt ? 'create' : 'update');
                            ?>
                            <div class="list-group-item py-3 dash-ai-item" id="dash-ai-item-<?= (int) $item['id'] ?>" data-id="<?= (int) $item['id'] ?>">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($is_appt && $action === 'create'): ?>
                                            <span class="badge bg-primary text-white"><i class="fas fa-calendar-plus me-1"></i> Yeni Randevu</span>
                                        <?php elseif ($is_appt && $action === 'cancel'): ?>
                                            <span class="badge bg-danger text-white"><i class="fas fa-calendar-xmark me-1"></i> İptal Talebi</span>
                                        <?php elseif ($is_appt && $action === 'reschedule'): ?>
                                            <span class="badge bg-warning text-dark"><i class="fas fa-calendar-days me-1"></i> Saat Değişikliği</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark"><i class="fas fa-user-pen me-1"></i> Profil Güncelleme</span>
                                        <?php endif; ?>
                                        <strong class="small"><?= e($changes['customer_name'] ?? 'Müşteri #' . (int) $item['target_id']) ?></strong>
                                        <?php if (!empty($changes['customer_phone'])): ?>
                                            <span class="text-muted small">(<?= e($changes['customer_phone']) ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted"><?= date('H:i, d.m.Y', strtotime($item['created_at'])) ?></small>
                                </div>

                                <div class="small mb-2 ps-1">
                                    <?php if ($is_appt && $action === 'create'): ?>
                                        <div><strong>Hizmet:</strong> <?= e($changes['service_name'] ?? 'Belirtilmedi') ?> · <strong>Tarih:</strong> <span class="text-primary fw-bold"><?= e($changes['start_datetime'] ?? '-') ?></span></div>
                                    <?php elseif ($is_appt && $action === 'reschedule'): ?>
                                        <div><strong>Randevu #<?= (int) ($item['target_id'] ?: ($changes['appointment_id'] ?? 0)) ?></strong> · <strong>Yeni Tarih:</strong> <span class="text-success fw-bold"><?= e($changes['new_start_datetime'] ?? '-') ?></span></div>
                                    <?php elseif ($is_appt && $action === 'cancel'): ?>
                                        <div class="text-danger">Randevu #<?= (int) ($item['target_id'] ?: ($changes['appointment_id'] ?? 0)) ?> için iptal talebi</div>
                                    <?php endif; ?>
                                    <?php if (!empty($item['reason'])): ?>
                                        <div class="text-muted fst-italic mt-1">"<?= e($item['reason']) ?>"</div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-success dash-ai-approve" data-id="<?= (int) $item['id'] ?>" data-busy-label="İşleniyor...">
                                        <i class="fas fa-check me-1"></i> Onayla & Takvime İşle
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger dash-ai-reject" data-id="<?= (int) $item['id'] ?>" data-busy-label="...">
                                        <i class="fas fa-times me-1"></i> Reddet
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

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
                    <?php if ($summary['payment_missing_count'] === 0 && $summary['waiting_count'] === 0 && empty($summary['ai_pending_count'])): ?>
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
                <div class="text-muted small">Sektörünüze özel sık kullandığınız operasyonlara tek tıkla erişin.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <?php if (!empty($summary['industry_config']['quick_actions'])): ?>
                    <?php foreach ($summary['industry_config']['quick_actions'] as $qa): ?>
                        <a href="<?= e($qa['url']) ?>" class="btn <?= e($qa['class']) ?> btn-sm"><?= e($qa['label']) ?></a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <a href="<?= site_url('calendar') ?>" class="btn btn-primary btn-sm">+ Yeni Randevu</a>
                    <a href="<?= site_url('customers') ?>" class="btn btn-outline-secondary btn-sm">+ Müşteri</a>
                    <a href="<?= site_url('calendar') ?>" class="btn btn-outline-secondary btn-sm">₺ Ödeme Al</a>
                    <a href="<?= site_url('waitlist') ?>" class="btn btn-outline-secondary btn-sm">+ Bekleme Talebi</a>
                <?php endif; ?>
                <a href="<?= site_url('industry_settings') ?>" class="btn btn-outline-primary btn-sm" title="Sektörü ve bağlı modülleri yönet">
                    <i class="fas fa-shapes me-1"></i>Sektör & Modüller
                </a>
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
