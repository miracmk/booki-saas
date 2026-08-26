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
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/pages/reports.js') ?>"></script>

<?php end_section('scripts'); ?>
