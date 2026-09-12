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
    <?php // 2026-09-12 - açılır/kapanır (collapsible) yapıldı + tek tık "Gün Sonu Raporu" eklendi. ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center" style="cursor: pointer;"
             data-bs-toggle="collapse" data-bs-target="#export-collapse" role="button">
            <h5 class="fw-light mb-0">Dışa Aktar</h5>
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="collapse" id="export-collapse">
            <div class="card-body">
                <p class="form-text text-muted">
                    Seçilen tarih aralığındaki her seans için, aşağıda işaretlediğiniz sütunları içeren bir
                    CSV dosyası indirir (Excel'de doğrudan açılır).
                </p>

                <button type="button" id="export-end-of-day" class="btn btn-outline-primary mb-3">
                    <i class="fas fa-bolt me-2"></i> Gün Sonu Raporu (bugün, tüm sütunlar)
                </button>

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

            <div id="analytics-error" class="alert alert-danger d-none"></div>

            <div class="row">
                <div class="col-12 col-lg-6 mb-3">
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="mb-0">Ciro Raporu</h6>
                        </div>
                        <div class="card-body">
                            <div id="analytics-revenue-kpis" class="row g-2 mb-3"></div>
                            <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                <table class="table table-sm table-striped mb-0">
                                    <thead><tr><th>Tarih</th><th>Seans</th><th>Ciro</th><th>Net</th></tr></thead>
                                    <tbody id="analytics-revenue-trend">
                                        <tr><td colspan="4" class="text-muted">Yükleniyor...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6 mb-3">
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="mb-0">Kapasite Kullanımı</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                                <table class="table table-sm table-striped mb-0">
                                    <thead><tr><th>Terapist</th><th>Dolu (dk)</th><th>Müsait (dk)</th><th>Doluluk</th></tr></thead>
                                    <tbody id="analytics-utilization-table">
                                        <tr><td colspan="4" class="text-muted">Yükleniyor...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (session('role_slug') !== DB_SLUG_PROVIDER): ?>
                    <div class="col-12 col-lg-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6 class="mb-0">Müşteri Kalıcılığı</h6>
                            </div>
                            <div class="card-body">
                                <div id="analytics-retention-kpis" class="row g-2 mb-3"></div>
                                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                                    <table class="table table-sm table-striped mb-0">
                                        <thead><tr><th>Ay</th><th>Yeni</th><th>Geri Dönen</th><th>Tekrar Oranı</th></tr></thead>
                                        <tbody id="analytics-retention-table">
                                            <tr><td colspan="4" class="text-muted">Yükleniyor...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
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
        // 2026-09-12 bugfix: this used to read `$('meta[name="csrf-token"]').attr('content')` - that
        // meta tag never existed anywhere in backend_layout.php, so `token` was always the literal
        // string "undefined", every POST below failed CI3's CSRF check (403), and every analytics
        // card showed the caught error message - this is the concrete reason the user reported
        // "Analitik raporlar çalışmıyor". Fixed by using the same `vars('csrf_token')` PHP helper the
        // rest of the app's script_vars-based pages use (see EA_Controller::load_common_script_vars()).
        const token = <?= json_encode(vars('csrf_token')) ?>;

        function formatMoney(n) {
            return '₺' + Number(n || 0).toLocaleString('tr-TR', {maximumFractionDigits: 0});
        }

        function kpi(label, value) {
            return '<div class="col-6 col-md-3"><div class="p-2 border rounded text-center">' +
                '<div class="small text-muted">' + label + '</div>' +
                '<div class="fw-bold">' + value + '</div></div></div>';
        }

        function renderRevenue(data) {
            const t = data.totals || {};
            $('#analytics-revenue-kpis').html(
                kpi('Toplam Ciro', formatMoney(t.gross)) +
                kpi('Net', formatMoney(t.net)) +
                kpi('Seans', t.session_count || 0) +
                kpi('Ort. Fiş', formatMoney(t.avg_ticket))
            );

            const rows = (data.trend || []).map((r) =>
                '<tr><td>' + r.key + '</td><td>' + r.session_count + '</td>' +
                '<td>' + formatMoney(r.gross) + '</td><td>' + formatMoney(r.net) + '</td></tr>'
            ).join('');
            $('#analytics-revenue-trend').html(rows || '<tr><td colspan="4" class="text-muted">Veri yok.</td></tr>');
        }

        function renderUtilization(data) {
            const rows = (data.providers || []).map((p) =>
                '<tr><td>' + p.provider_name + '</td><td>' + p.booked_minutes + '</td>' +
                '<td>' + p.available_minutes + '</td>' +
                '<td>' + (p.utilization_pct !== null ? p.utilization_pct + '%' : '-') + '</td></tr>'
            ).join('');
            $('#analytics-utilization-table').html(rows || '<tr><td colspan="4" class="text-muted">Veri yok.</td></tr>');
        }

        function renderRetention(data) {
            const c = data.churn || {};
            $('#analytics-retention-kpis').html(
                kpi('Kaybedilen Müşteri', c.churned || 0) +
                kpi('Kayıp Oranı', (c.pct || 0) + '%') +
                kpi('Toplam Müşteri', c.total || 0) +
                kpi('Eşik (gün)', c.days || 90)
            );

            const rows = (data.months || []).map((m) =>
                '<tr><td>' + m.month + '</td><td>' + m.new + '</td><td>' + m.returning + '</td>' +
                '<td>' + m.repeat_rate + '%</td></tr>'
            ).join('');
            $('#analytics-retention-table').html(rows || '<tr><td colspan="4" class="text-muted">Veri yok.</td></tr>');
        }

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

            $('#analytics-error').addClass('d-none').text('');

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
                    renderRevenue(revenueData);
                    renderUtilization(utilizationData);
                    <?php if (session('role_slug') !== DB_SLUG_PROVIDER): ?>
                        if (retentionData) {
                            renderRetention(retentionData);
                        }
                    <?php endif; ?>
                })
                .catch((error) => {
                    console.error('Analytics error:', error);
                    const errorMsg = 'Hata: ' + (error.responseJSON?.message || error.statusText || 'Bilinmeyen hata');
                    $('#analytics-error').removeClass('d-none').text(errorMsg);
                });
        });

        $('#analytics-fetch-btn').trigger('click');
    });
</script>

<?php end_section('scripts'); ?>
