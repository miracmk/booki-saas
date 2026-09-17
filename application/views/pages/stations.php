<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-3" id="stations-page">
    <div class="row" id="stations">
        <div id="filter-stations" class="filter-records col col-12 mb-4">
            <button id="add-station" class="btn btn-primary add-record-btn mb-4">
                <i class="fas fa-plus-square me-2"></i>
                <?= lang('add') ?>
            </button>

            <form class="mb-4">
                <div class="input-group">
                    <input type="text" class="key form-control" aria-label="keyword">

                    <button class="filter btn btn-outline-secondary" type="submit"
                            data-tippy-content="<?= lang('filter') ?>">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <h4 class="mb-3 fw-light"><?= lang('stations_heading') ?></h4>

            <div class="results overflow-auto" style="max-height: 650px;">
                <!-- JS -->
            </div>
        </div>

        <div class="record-details column col-12 mb-4">
            <div class="btn-toolbar mb-4">
                <div class="add-edit-delete-group btn-group">
                    <button id="edit-station" class="btn btn-outline-secondary" disabled="disabled">
                        <i class="fas fa-edit me-2"></i>
                        <?= lang('edit') ?>
                    </button>
                </div>

                <div class="save-cancel-group" style="display:none;">
                    <button id="save-station" class="btn btn-primary">
                        <i class="fas fa-check-square me-2"></i>
                        <?= lang('save') ?>
                    </button>
                    <button id="cancel-station" class="btn btn-outline-secondary">
                        <?= lang('cancel') ?>
                    </button>
                    <button id="delete-station" class="btn btn-outline-danger ms-2">
                        <i class="fas fa-trash-alt me-2"></i>
                        <?= lang('delete') ?>
                    </button>
                </div>
            </div>

            <h4 class="mb-3 fw-light">
                <?= lang('details') ?>
            </h4>

            <div class="form-message alert" style="display:none;"></div>

            <input type="hidden" id="id">

            <div class="mb-3">
                <label class="form-label" for="name">
                    Ad
                    <span class="text-danger" hidden>*</span>
                </label>
                <input id="name" class="form-control required" maxlength="256" disabled
                       placeholder="<?= lang('stations_name_placeholder') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label" for="notes">
                    Not
                </label>
                <textarea id="notes" rows="3" class="form-control" disabled></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label" for="display-order">
                    İlk Müsaitlik Sırası
                </label>
                <input id="display-order" type="number" step="1" class="form-control" style="max-width: 140px;" disabled>
                <div class="form-text text-muted">
                    <small>
                        Küçük sayı önce gösterilir (İlk Müsaitlik şeridinde ve takvim oda listesinde).
                        Aynı sayıya sahip odalar isme göre sıralanır.
                    </small>
                </div>
            </div>

            <div class="border rounded mb-3 p-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is-active">

                    <label class="form-check-label" for="is-active">
                        Aktif
                    </label>
                </div>

                <div class="form-text text-muted">
                    <small>
                        <?= lang('stations_inactive_hint') ?>
                    </small>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    Bu istasyonda verilebilen hizmetler
                </label>

                <div class="border rounded p-3">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="station-no-restriction" disabled checked>
                        <label class="form-check-label" for="station-no-restriction">
                            Tüm hizmetler (kısıtlama yok)
                        </label>
                    </div>

                    <div id="station-services" class="ps-2 border-top pt-2">
                        <!-- JS -->
                    </div>
                </div>

                <div class="form-text text-muted">
                    <small>
                        Hiçbir hizmet seçilmezse bu istasyon tüm hizmetler için kullanılabilir. Belirli hizmetler
                        seçilirse, bu istasyon SADECE o hizmetler için randevu formunda önerilir.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/http/stations_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/stations.js') ?>"></script>

<?php end_section('scripts'); ?>
