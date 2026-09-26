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
    <div class="card mt-4 shadow-sm border-0">
        <div class="row mb-4">
            <div class="col-12 col-sm-4">
                <h4 class="mb-0 fw-light">
                    <i class="fas fa-chart-line me-2 text-primary"></i>
                    Command Center Analytics
                </h4>
                <p class="form-text text-muted mb-4">
                    Tarih aralığı üzerinden ciro, personel doluluğu ve müşteri kalıcılığı metriklerini izleyin.
                </p>
            </div>
        </div>
        <div class="p-3 bg-light rounded-3 mb-4 d-flex flex-wrap align-items-center gap-3">
                <div class="flex-grow-1" style="min-width: 150px;">
                    <label class="form-label small fw-bold text-muted text-uppercase" for="analytics-date-from">Başlangıç</label>
                    <input type="date" id="analytics-date-from" class="form-control form-control-sm border-0 shadow-sm">
                </div>
                <div class="flex-grow-1" style="min-width: 150px;">
                    <label class="form-label small fw-bold text-muted text-uppercase" for="analytics-date-to">Bitiş</label>
                    <input type="date" id="analytics-date-to" class="form-control form-control-sm border-0 shadow-sm">
                </div>
                <div class="flex-grow-1" style="min-width: 150px;">
                    <label class="form-label small fw-bold text-muted text-uppercase" for="analytics-group-by">Gruplama</label>
                    <select id="analytics-group-by" class="form-select form-select-sm border-0 shadow-sm">
                        <option value="day">Günlük</option>
                        <option value="week">Haftalık</option>
                        <option value="month">Aylık</option>
                    </select>
                </div>
                <div class="mt-4">
                    <button type="button" id="analytics-fetch-btn" class="btn btn-primary btn-sm shadow-sm px-4 rounded-pill">
                        <i class="fas fa-sync-alt me-2"></i> Analiz Et
                    </button>
                </div>
            </div>

            <div id="analytics-error" class="alert alert-danger d-none rounded-3 border-0 shadow-sm"></div>

            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom-0">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-wallet text-success me-2"></i> Ciro Analizi</h6>
                        </div>
                        <div class="card-body pt-0">
                            <div id="analytics-revenue-kpis" class="row g-3 mb-4"></div>
                            <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                <table class="table table-borderless table-hover mb-0 align-middle">
                                    <thead class="table-light"><tr><th class="rounded-start">Dönem</th><th>Seans</th><th>Brüt</th><th class="rounded-end">Net</th></tr></thead>
                                    <tbody id="analytics-revenue-trend">
                                        <tr><td colspan="4" class="text-muted text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Veriler hazırlanıyor...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom-0">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-user-clock text-warning me-2"></i> Kapasite Kullanımı</h6>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                                <table class="table table-borderless table-hover mb-0 align-middle">
                                    <thead class="table-light"><tr><th class="rounded-start">Personel</th><th>Dolu/Müsait</th><th class="rounded-end">Doluluk Oranı</th></tr></thead>
                                    <tbody id="analytics-utilization-table">
                                        <tr><td colspan="3" class="text-muted text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Veriler hazırlanıyor...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (session('role_slug') !== DB_SLUG_PROVIDER): ?>
                    <div class="col-12 col-lg-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-bottom-0">
                                <h6 class="mb-0 fw-bold"><i class="fas fa-users text-info me-2"></i> Müşteri Kalıcılığı (Retention)</h6>
                            </div>
                            <div class="card-body pt-0">
                                <div id="analytics-retention-kpis" class="row g-3 mb-4"></div>
                                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                                    <table class="table table-borderless table-hover mb-0 align-middle">
                                        <thead class="table-light"><tr><th class="rounded-start">Dönem</th><th>Yeni Müşteri</th><th>Geri Dönen</th><th class="rounded-end">Kalıcılık Oranı</th></tr></thead>
                                        <tbody id="analytics-retention-table">
                                            <tr><td colspan="4" class="text-muted text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Veriler hazırlanıyor...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="col-12 col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-boxes-stacked text-warning me-2"></i> Sarf Malzeme & Seans Kârlılık Analizi</h6>
                            <span class="badge bg-light text-muted border">Birim Seans Reçete & Stok Sarfiyatı</span>
                        </div>
                        <div class="card-body pt-0">
                            <div id="analytics-consumables-kpis" class="row g-3 mb-4"></div>
                            <div class="row g-3">
                                <div class="col-12 col-lg-6">
                                    <h6 class="fw-semibold text-secondary small text-uppercase mb-2">
                                        <i class="fas fa-layer-group me-1"></i> En Çok Tüketilen Sarf Malzemeleri
                                    </h6>
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-borderless table-hover mb-0 align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="rounded-start">Malzeme / Ürün</th>
                                                    <th>Toplam Miktar</th>
                                                    <th>Seans</th>
                                                    <th class="rounded-end text-end">Toplam Harcama</th>
                                                </tr>
                                            </thead>
                                            <tbody id="analytics-consumables-table">
                                                <tr><td colspan="4" class="text-muted text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Veriler hazırlanıyor...</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <h6 class="fw-semibold text-secondary small text-uppercase mb-2">
                                        <i class="fas fa-chart-pie me-1"></i> Hizmet Bazında Seans Kârlılığı
                                    </h6>
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-borderless table-hover mb-0 align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="rounded-start">Hizmet Adı</th>
                                                    <th>Seans</th>
                                                    <th>Ciro</th>
                                                    <th>Sarf Maliyeti</th>
                                                    <th>Brüt Kâr</th>
                                                    <th class="rounded-end text-end">Kâr Marjı</th>
                                                </tr>
                                            </thead>
                                            <tbody id="analytics-services-profit-table">
                                                <tr><td colspan="6" class="text-muted text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Veriler hazırlanıyor...</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
        // rest of the app's script_vars-based pages use (see App_Controller::load_common_script_vars()).
        const token = <?= json_encode(vars('csrf_token')) ?>;

        function formatMoney(n) {
            return '<span class="badge bg-light text-dark border fs-6 fw-normal py-2 px-3 shadow-sm">₺' + Number(n || 0).toLocaleString('tr-TR', {maximumFractionDigits: 0}) + '</span>';
        }

        function kpi(label, value) {
            return '<div class="col-6 col-md-3"><div class="p-3 bg-white border-0 shadow-sm rounded-3 text-center h-100 d-flex flex-column justify-content-center">' +
                '<div class="small text-muted text-uppercase fw-bold mb-2">' + label + '</div>' +
                '<div class="fs-4 fw-bolder text-dark">' + value + '</div></div></div>';
        }

        function getProgressBar(pct) {
            if (pct === null || pct === undefined || isNaN(pct)) return '-';
            let bgClass = 'bg-success';
            if (pct < 30) bgClass = 'bg-primary';
            else if (pct < 60) bgClass = 'bg-warning';
            
            return '<div class="d-flex align-items-center"><div class="progress flex-grow-1" style="height: 8px;"><div class="progress-bar ' + bgClass + '" role="progressbar" style="width: ' + pct + '%" aria-valuenow="' + pct + '" aria-valuemin="0" aria-valuemax="100"></div></div><span class="ms-2 small fw-bold">' + pct + '%</span></div>';
        }

        function renderRevenue(data) {
            const t = data.totals || {};
            $('#analytics-revenue-kpis').html(
                kpi('Toplam Ciro', formatMoney(t.gross)) +
                kpi('Net', formatMoney(t.net)) +
                kpi('Seans', '<span class="badge bg-primary rounded-pill">' + (t.session_count || 0) + '</span>') +
                kpi('Ort. Fiş', formatMoney(t.avg_ticket))
            );

            const rows = (data.trend || []).map((r) =>
                '<tr><td class="text-nowrap fw-medium">' + r.key + '</td><td><span class="badge bg-light text-secondary border">' + r.session_count + '</span></td>' +
                '<td>' + formatMoney(r.gross) + '</td><td>' + formatMoney(r.net) + '</td></tr>'
            ).join('');
            $('#analytics-revenue-trend').html(rows || '<tr><td colspan="4" class="text-muted text-center py-4">Veri yok.</td></tr>');
        }

        function renderUtilization(data) {
            const rows = (data.providers || []).map((p) =>
                '<tr><td class="fw-medium">' + $('<div>').text(p.provider_name).html() + '</td>' +
                '<td><span class="text-success fw-bold">' + p.booked_minutes + '</span> / <span class="text-muted">' + p.available_minutes + '</span> dk</td>' +
                '<td>' + getProgressBar(p.utilization_pct) + '</td></tr>'
            ).join('');
            $('#analytics-utilization-table').html(rows || '<tr><td colspan="3" class="text-muted text-center py-4">Veri yok.</td></tr>');
        }

        function renderRetention(data) {
            const c = data.churn || {};
            $('#analytics-retention-kpis').html(
                kpi('Kaybedilen Müşteri', '<span class="text-danger fw-bold">' + (c.churned || 0) + '</span>') +
                kpi('Kayıp Oranı', '<span class="badge bg-danger rounded-pill">' + (c.pct || 0) + '%</span>') +
                kpi('Toplam Müşteri', '<span class="text-dark fw-bold">' + (c.total || 0) + '</span>') +
                kpi('Eşik (gün)', '<span class="text-muted">' + (c.days || 90) + '</span>')
            );

            const rows = (data.months || []).map((m) =>
                '<tr><td class="fw-medium">' + m.month + '</td>' +
                '<td><span class="badge bg-info text-white rounded-pill px-3">' + m.new + '</span></td>' +
                '<td><span class="badge bg-success text-white rounded-pill px-3">' + m.returning + '</span></td>' +
                '<td><div class="d-flex align-items-center"><div class="progress flex-grow-1" style="height: 6px;"><div class="progress-bar bg-info" style="width: ' + m.repeat_rate + '%"></div></div><span class="ms-2 small fw-bold">' + m.repeat_rate + '%</span></div></td></tr>'
            ).join('');
            $('#analytics-retention-table').html(rows || '<tr><td colspan="4" class="text-muted text-center py-4">Veri yok.</td></tr>');
        }

        function renderConsumablesReport(data) {
            if (!data) return;
            const spend = Number(data.total_consumable_spend || 0);
            const revenue = Number(data.total_revenue || 0);
            const profit = Number(data.total_gross_profit || 0);
            const margin = Number(data.overall_margin_percent || 0);

            $('#analytics-consumables-kpis').html(
                kpi('Toplam Sarf Gideri', '<span class="text-danger">₺' + spend.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span>') +
                kpi('Seans Hasılatı', '₺' + revenue.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2})) +
                kpi('Toplam Brüt Kâr', '<span class="text-success">₺' + profit.toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span>') +
                kpi('Ort. Brüt Marj', '<span class="badge bg-success rounded-pill px-3 fs-6">%' + margin + '</span>')
            );

            const prodRows = (data.consumed_products || []).map((p) =>
                '<tr>' +
                '<td class="fw-medium text-dark">' + $('<div>').text(p.product_name || 'Ürün #' + p.id_products).html() + '</td>' +
                '<td><span class="badge bg-light text-dark border">' + p.total_quantity + ' ' + (p.unit || 'adet') + '</span></td>' +
                '<td><span class="badge bg-light text-secondary border">' + p.session_count + ' seans</span></td>' +
                '<td class="text-end fw-semibold text-danger">₺' + Number(p.total_spend || 0).toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                '</tr>'
            ).join('');
            $('#analytics-consumables-table').html(prodRows || '<tr><td colspan="4" class="text-muted text-center py-4">Bu dönemde sarfiyat kaydı yok.</td></tr>');

            const svsRows = (data.service_profitability || []).map((s) =>
                '<tr>' +
                '<td class="fw-medium text-dark">' + $('<div>').text(s.service_name).html() + '</td>' +
                '<td><span class="badge bg-primary rounded-pill">' + s.total_appointments + '</span></td>' +
                '<td>₺' + Number(s.total_revenue || 0).toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                '<td class="text-danger">₺' + Number(s.total_consumables_cost || 0).toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                '<td class="fw-semibold text-success">₺' + Number(s.total_gross_profit || 0).toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                '<td class="text-end">' + getProgressBar(s.margin_percent) + '</td>' +
                '</tr>'
            ).join('');
            $('#analytics-services-profit-table').html(svsRows || '<tr><td colspan="6" class="text-muted text-center py-4">Bu dönemde seans verisi yok.</td></tr>');
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
                }),
                $.post('<?= site_url("reports/get_consumables_report") ?>', {
                    csrf_token: token,
                    date_from: dateFrom,
                    date_to: dateTo
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
                .then(([revenueData, utilizationData, consumablesData, retentionData]) => {
                    renderRevenue(revenueData);
                    renderUtilization(utilizationData);
                    renderConsumablesReport(consumablesData);
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

