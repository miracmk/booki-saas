<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-3" id="reports-page">
    <h4 class="mb-3 fw-light">Günlük Ciro Raporu</h4>

    <div class="row mb-4">
        <div class="col-12 col-sm-4">
            <label class="form-label" for="report-date">Tarih</label>
            <input type="date" id="report-date" class="form-control">
        </div>
    </div>

    <div id="report-summary" class="mb-4"></div>

    <?php // Salon Flora customization (2026-08-25) - tarih aralıklı, seans bazlı CSV dışa aktarma. ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="fw-light mb-0">Dışa Aktar</h5>
        </div>
        <div class="card-body">
            <p class="form-text text-muted">
                Seçilen tarih aralığındaki her seans için, aşağıda işaretlediğiniz sütunları içeren bir
                CSV dosyası indirir.
            </p>
            <div class="row g-3 align-items-end mb-3">
                <div class="col-12 col-sm-4">
                    <label class="form-label" for="export-start-date">Başlangıç Tarihi</label>
                    <input type="date" id="export-start-date" class="form-control">
                </div>
                <div class="col-12 col-sm-4">
                    <label class="form-label" for="export-end-date">Bitiş Tarihi</label>
                    <input type="date" id="export-end-date" class="form-control">
                </div>
                <div class="col-12 col-sm-4">
                    <button type="button" id="export-csv" class="btn btn-primary w-100">
                        <i class="fas fa-file-csv me-2"></i> CSV İndir
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label mb-0">Sütunlar</label>
                <div>
                    <button type="button" id="export-columns-all" class="btn btn-sm btn-outline-secondary">
                        Tümünü Seç (En Kapsamlı)
                    </button>
                    <button type="button" id="export-columns-none" class="btn btn-sm btn-outline-secondary">
                        Hiçbirini Seçme
                    </button>
                </div>
            </div>

            <?php
            $fieldGroups = [];
            foreach (vars('report_field_catalog') as $key => $field) {
                $fieldGroups[$field['group']][$key] = $field;
            }
            ?>
            <div class="row">
                <?php foreach ($fieldGroups as $groupName => $fields): ?>
                    <div class="col-12 col-sm-6 col-lg-4 mb-3">
                        <strong class="d-block mb-1"><?= e($groupName) ?></strong>
                        <?php foreach ($fields as $key => $field): ?>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input export-column-checkbox"
                                       id="export-col-<?= e($key) ?>" value="<?= e($key) ?>"
                                       <?= !$field['pii'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="export-col-<?= e($key) ?>">
                                    <?= e($field['label']) ?>
                                    <?php if ($field['pii']): ?>
                                        <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65em;">
                                            Kişisel Veri
                                        </span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped" id="report-table">
            <thead>
                <tr>
                    <th>Terapist</th>
                    <th>Seans Sayısı</th>
                    <th>Çalışma Süresi</th>
                    <th>Toplam Ciro</th>
                    <th>Tahsil Edilen</th>
                    <th>Bakiye</th>
                    <th>Bekleyen Tahsilat</th>
                    <th>Fatura</th>
                    <th>Varsayılan Komisyon</th>
                    <th>Terapiste Ödenecek</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <?php // Faz 3.6 - Analytics/BI section with revenue, utilization, retention reports ?>
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="fw-light mb-0">Analitik Raporlar</h5>
        </div>
        <div class="card-body">
            <p class="form-text text-muted mb-3">
                Tarih aralığı üzerinden ciro, kapasite kullanımı ve müşteri kalıcılığı analitikleri.
            </p>

            <div class="row g-3 mb-4 align-items-end">
                <div class="col-12 col-sm-3">
                    <label class="form-label" for="analytics-date-from">Başlangıç Tarihi</label>
                    <input type="date" id="analytics-date-from" class="form-control">
                </div>
                <div class="col-12 col-sm-3">
                    <label class="form-label" for="analytics-date-to">Bitiş Tarihi</label>
                    <input type="date" id="analytics-date-to" class="form-control">
                </div>
                <div class="col-12 col-sm-3">
                    <label class="form-label" for="analytics-group-by">Gruplama</label>
                    <select id="analytics-group-by" class="form-select">
                        <option value="day">Gün</option>
                        <option value="week">Hafta</option>
                        <option value="month">Ay</option>
                    </select>
                </div>
                <div class="col-12 col-sm-3">
                    <button type="button" id="analytics-fetch-btn" class="btn btn-primary w-100">
                        <i class="fas fa-refresh me-2"></i> Getir
                    </button>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-lg-6 mb-3">
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="mb-0">Ciro Raporu</h6>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <pre id="analytics-revenue-result" class="text-monospace" style="font-size: 0.75em; margin: 0;">Yükleniyor...</pre>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6 mb-3">
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="mb-0">Kapasite Kullanımı</h6>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <pre id="analytics-utilization-result" class="text-monospace" style="font-size: 0.75em; margin: 0;">Yükleniyor...</pre>
                        </div>
                    </div>
                </div>

                <?php if (session('role_slug') !== DB_SLUG_PROVIDER): ?>
                    <div class="col-12 col-lg-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6 class="mb-0">Müşteri Kalıcılığı</h6>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <pre id="analytics-retention-result" class="text-monospace" style="font-size: 0.75em; margin: 0;">Yükleniyor...</pre>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/pages/reports.js') ?>"></script>

<script>
    $(document).ready(function() {
        // Set default date range: last 30 days
        const today = new Date();
        const thirtyDaysAgo = new Date(today);
        thirtyDaysAgo.setDate(today.getDate() - 29);

        const formatDate = (date) => date.toISOString().split('T')[0];

        $('#analytics-date-from').val(formatDate(thirtyDaysAgo));
        $('#analytics-date-to').val(formatDate(today));
        $('#analytics-group-by').val('day');

        $('#analytics-fetch-btn').on('click', function() {
            const dateFrom = $('#analytics-date-from').val();
            const dateTo = $('#analytics-date-to').val();
            const groupBy = $('#analytics-group-by').val();

            if (!dateFrom || !dateTo) {
                alert('Lütfen tarih aralığı seçiniz.');
                return;
            }

            const token = $('meta[name="csrf-token"]').attr('content');
            const requests = [
                $.post('<?= site_url("reports/get_revenue_report") ?>', {
                    csrf_token: token,
                    date_from: dateFrom,
                    date_to: dateTo,
                    group_by: groupBy
                }),
                $.post('<?= site_url("reports/get_utilization_report") ?>', {
                    csrf_token: token,
                    date_from: dateFrom,
                    date_to: dateTo,
                    group_by: groupBy
                })
            ];

            // Add retention report request if not a provider
            <?php if (session('role_slug') !== DB_SLUG_PROVIDER): ?>
                requests.push(
                    $.post('<?= site_url("reports/get_retention_report") ?>', {
                        csrf_token: token,
                        date_from: dateFrom,
                        date_to: dateTo,
                        group_by: groupBy,
                        churn_days: 90
                    })
                );
            <?php endif; ?>

            Promise.all(requests.map(req => req.promise ? req.promise() : req))
                .then(([revenueData, utilizationData, retentionData]) => {
                    $('#analytics-revenue-result').text(JSON.stringify(revenueData, null, 2));
                    $('#analytics-utilization-result').text(JSON.stringify(utilizationData, null, 2));
                    <?php if (session('role_slug') !== DB_SLUG_PROVIDER): ?>
                        if (retentionData) {
                            $('#analytics-retention-result').text(JSON.stringify(retentionData, null, 2));
                        }
                    <?php endif; ?>
                })
                .catch((error) => {
                    console.error('Analytics error:', error);
                    const errorMsg = 'Hata: ' + (error.responseJSON?.message || error.statusText || 'Bilinmeyen hata');
                    $('#analytics-revenue-result').text(errorMsg);
                    $('#analytics-utilization-result').text(errorMsg);
                    <?php if (session('role_slug') !== DB_SLUG_PROVIDER): ?>
                        $('#analytics-retention-result').text(errorMsg);
                    <?php endif; ?>
                });
        });
    });
</script>

<?php end_section('scripts'); ?>
