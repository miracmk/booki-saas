<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-3" id="providers-page">
    <div class="row" id="providers">
        <div id="filter-providers" class="filter-records column col-12 mb-4">
            <button id="add-provider" class="btn btn-primary add-record-btn mb-4">
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

            <h4 class="mb-3 fw-light">
                <?= lang('providers') ?>
            </h4>

            <div class="results overflow-auto" style="max-height: 650px;">
                <!-- JS -->
            </div>
        </div>

        <div class="record-details column col-12 mb-4">
            <div class="float-md-start mb-4 me-4">
                <div class="add-edit-delete-group btn-group">
                    <button id="edit-provider" class="btn btn-outline-secondary" disabled="disabled">
                        <i class="fas fa-edit me-2"></i>
                        <?= lang('edit') ?>
                    </button>
                </div>

                <div class="save-cancel-group" style="display:none;">
                    <button id="save-provider" class="btn btn-primary">
                        <i class="fas fa-check-square me-2"></i>
                        <?= lang('save') ?>
                    </button>
                    <button id="cancel-provider" class="btn btn-outline-secondary">
                        <?= lang('cancel') ?>
                    </button>
                    <button id="delete-provider" class="btn btn-outline-danger ms-2">
                        <i class="fas fa-trash-alt me-2"></i>
                        <?= lang('delete') ?>
                    </button>
                    <button id="anonymize-provider" class="btn btn-outline-danger ms-2"
                            title="Randevu/komisyon geçmişini SİLMEDEN terapistin kimliğini belirleyen tüm bilgilerini (ad-soyad, telefon, e-posta, adres, notlar) kalıcı olarak kaldırır. Geri alınamaz.">
                        <i class="fas fa-user-slash me-2"></i>
                        KVKK - Unutulma Hakkı
                    </button>
                </div>

            </div>

            <ul class="nav nav-pills switch-view">
                <li class="nav-item">
                    <a class="nav-link active" href="#details" data-bs-toggle="tab">
                        <?= lang('details') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#working-plan" data-bs-toggle="tab">
                        <?= lang('working_plan') ?>
                    </a>
                </li>
            </ul>

            <?php
// This form message is outside the details view, so that it can be
// visible when the user has working plan view active.
?>

            <div class="form-message alert mt-4" style="display:none;"></div>

            <div class="tab-content">
                <div class="details-view tab-pane fade show active clearfix" id="details">
                    <h4 class="mb-3 fw-light">
                        <?= lang('details') ?>
                    </h4>

                    <input type="hidden" id="id" class="record-id">

                    <div class="row">
                        <div class="details col-12 col-lg-6">
                            <div class="mb-3">
                                <label class="form-label" for="first-name">
                                    <?= lang('first_name') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input id="first-name" class="form-control required" maxlength="256" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="last-name">
                                    <?= lang('last_name') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input id="last-name" class="form-control required" maxlength="512" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="email">
                                    <?= lang('email') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input id="email" class="form-control required" max="512" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="phone-number">
                                    <?= lang('phone_number') ?>
                                </label>
                                <input id="phone-number" class="form-control" max="128" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="mobile-number">
                                    <?= lang('mobile_number') ?>

                                </label>
                                <input id="mobile-number" class="form-control" maxlength="128" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="address">
                                    <?= lang('address') ?>
                                </label>
                                <input id="address" class="form-control" maxlength="256" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="city">
                                    <?= lang('city') ?>

                                </label>
                                <input id="city" class="form-control" maxlength="256" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="state">
                                    <?= lang('state') ?>
                                </label>
                                <input id="state" class="form-control" maxlength="256" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="zip-code">
                                    <?= lang('zip_code') ?>

                                </label>
                                <input id="zip-code" class="form-control" maxlength="64" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="notes">
                                    <?= lang('notes') ?>
                                </label>
                                <textarea id="notes" class="form-control" rows="3" disabled></textarea>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">
                                        İstasyonlar
                                    </label>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" id="select-all-stations" class="btn btn-outline-secondary" disabled>
                                            <?= lang('select_all') ?>
                                        </button>
                                        <button type="button" id="select-none-stations" class="btn btn-outline-secondary" disabled>
                                            <?= lang('select_none') ?>
                                        </button>
                                    </div>
                                </div>

                                <div id="provider-stations" class="card card-body border">
                                    <!-- JS -->
                                </div>

                                <div class="form-text text-muted">
                                    <small>
                                        Bu terapistin çalışabildiği istasyon(lar). Birden fazla seçilebilir - terapist
                                        atanmış istasyonlarından en az biri boşsa müsait görünür. Aynı istasyona
                                        atanmış başka bir terapistin randevusu, o istasyonu o saat için kapatır.
                                    </small>
                                </div>

                                <div class="form-check mt-2">
                                    <input type="checkbox" id="station-restriction-enabled" class="form-check-input" disabled>
                                    <label class="form-check-label" for="station-restriction-enabled">
                                        Sadece yukarıda seçilen istasyonlarla sınırla
                                    </label>
                                    <div class="form-text text-muted">
                                        <small>
                                            Kapalıysa (varsayılan) bu terapist, üstteki seçime bakılmaksızın, çalıştığı
                                            hizmetin AÇIK olduğu her istasyonda randevu alabilir. Açılırsa, üstte
                                            işaretlenmemiş istasyonlar bu terapist için hiç önerilmez.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">
                                        Yetenekler
                                    </label>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" id="select-all-skills" class="btn btn-outline-secondary" disabled>
                                            <?= lang('select_all') ?>
                                        </button>
                                        <button type="button" id="select-none-skills" class="btn btn-outline-secondary" disabled>
                                            <?= lang('select_none') ?>
                                        </button>
                                    </div>
                                </div>

                                <div id="provider-skills" class="card card-body border">
                                    <!-- JS -->
                                </div>

                                <div class="input-group input-group-sm mt-2">
                                    <input type="text" id="new-skill-name" class="form-control"
                                           placeholder="Yeni yetenek adı (örn. Aromaterapi)" disabled>
                                    <button type="button" id="add-skill-button" class="btn btn-outline-primary" disabled>
                                        Ekle
                                    </button>
                                </div>

                                <div class="form-text text-muted">
                                    <small>
                                        Bu terapistin uzmanlık alanları. Müşteri kartında talep edilen hizmete uygun
                                        yeteneği olan terapistler öneri olarak öne çıkarılır.
                                    </small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Varsayılan Komisyon
                                </label>
                                <div class="input-group">
                                    <select id="commission-type" class="form-select" disabled style="max-width: 160px;">
                                        <option value="percentage">Yüzde (%)</option>
                                        <option value="fixed">Sabit Tutar (TL)</option>
                                        <option value="hourly">Saatlik (TL/saat)</option>
                                    </select>
                                    <input id="commission-value" type="number" step="0.01" min="0"
                                           class="form-control" disabled>
                                </div>
                                <div class="form-text text-muted">
                                    <small>
                                        Aşağıda hizmet bazında özel bir komisyon girilmemiş hizmetler için kullanılan
                                        genel oran/tutar. Yüzde seçilirse, o seansın hesaplanan ücretinin belirtilen
                                        yüzdesi terapiste ödenir. Sabit tutar seçilirse, tamamlanan her seans için bu
                                        miktar ödenir. Saatlik seçilirse, seansın gerçek (check-in/check-out) süresi
                                        üzerinden bu tutar × saat olarak ödenir.
                                    </small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="commission-overtime-bonus" class="form-label">
                                    1 Saat Üzeri Ek Sabit Komisyon (TL)
                                </label>
                                <input id="commission-overtime-bonus" type="number" step="0.01" min="0"
                                       class="form-control" style="max-width: 160px;" disabled>
                                <div class="form-text text-muted">
                                    <small>
                                        Sadece "Saatlik" komisyon tipinde uygulanır: seansın gerçek (check-in/
                                        check-out) süresi 1 saati geçerse, saatlik komisyona ek olarak bu sabit
                                        tutar bir kere eklenir (kaç saat geçtiği fark etmez).
                                    </small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label mb-2">
                                    Hizmet Bazlı Komisyon
                                </label>

                                <div id="provider-service-commissions">
                                    <!-- JS -->
                                </div>

                                <div class="form-text text-muted">
                                    <small>
                                        Atanmış her hizmet için isteğe bağlı özel komisyon. Boş bırakılırsa yukarıdaki
                                        varsayılan komisyon kullanılır. Aynı hizmetin farklı süre varyantları
                                        (60/90/120 dk) ayrı satırlar olarak listelenir, her biri için farklı tutar
                                        girilebilir.
                                    </small>
                                </div>
                            </div>

                        </div>
                        <div class="settings col-12 col-lg-6">
                            <div class="mb-3">
                                <label class="form-label" for="username">
                                    <?= lang('username') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input id="username" class="form-control required" maxlength="256" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="password">
                                    <?= lang('password') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input type="password" id="password" class="form-control required"
                                       maxlength="512" autocomplete="new-password" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="password-confirm">
                                    <?= lang('retype_password') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <input type="password" id="password-confirm"
                                       class="form-control required" maxlength="512"
                                       autocomplete="new-password" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="calendar-view">
                                    <?= lang('calendar') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <select id="calendar-view" class="form-select required" disabled>
                                    <option value="default"><?= lang('default') ?></option>
                                    <option value="table"><?= lang('table') ?></option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="language">
                                    <?= lang('language') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <select id="language" class="form-select required" disabled>
                                    <?php foreach (vars('available_languages') as $available_language): ?>
                                        <option value="<?= $available_language ?>">
                                            <?= ucfirst($available_language) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="timezone">
                                    <?= lang('timezone') ?>
                                    <span class="text-danger" hidden>*</span>
                                </label>
                                <?php component('timezone_dropdown', [
                                    'attributes' => 'id="timezone" class="form-select required" disabled',
                                    'grouped_timezones' => vars('grouped_timezones'),
                                ]); ?>
                            </div>

                            <?php if (setting('ldap_is_active')): ?>
                                <div class="mb-3">
                                    <label for="ldap-dn" class="form-label">
                                        <?= lang('ldap_dn') ?>
                                    </label>
                                    <input type="text" id="ldap-dn" class="form-control" maxlength="100" disabled/>
                                </div>
                            <?php endif; ?>

                            <div>
                                <label class="form-label mb-3">
                                    <?= lang('options') ?>
                                </label>
                            </div>

                            <div class="border rounded mb-3 p-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is-private">
                                    <label class="form-check-label" for="is-private">
                                        <?= lang('hide_from_public') ?>
                                    </label>
                                </div>

                                <div class="form-text text-muted mb-3">
                                    <small>
                                        <?= lang('private_hint') ?>
                                    </small>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notifications" disabled>
                                    <label class="form-check-label" for="notifications">
                                        <?= lang('receive_notifications') ?>
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label mb-0">
                                    <?= lang('services') ?>
                                </label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" id="select-all-services" class="btn btn-outline-secondary" disabled>
                                        <?= lang('select_all') ?>
                                    </button>
                                    <button type="button" id="select-none-services" class="btn btn-outline-secondary" disabled>
                                        <?= lang('select_none') ?>
                                    </button>
                                </div>
                            </div>

                            <div id="provider-services" class="card card-body border">
                                <!-- JS -->
                            </div>

                        </div>
                    </div>
                </div>

                <div class="working-plan-view tab-pane fade clearfix" id="working-plan">
                    <h4 class="mb-3 fw-light">
                        <?= lang('working_plan') ?>
                    </h4>

                    <button id="reset-working-plan" class="btn btn-primary"
                            data-tippy-content="<?= lang('reset_working_plan') ?>">
                        <i class="fas fa-undo-alt me-2"></i>
                        <?= lang('reset_plan') ?></button>
                    <div class="table-responsive">
                        <table class="working-plan table table-striped mt-2">
                            <thead>
                            <tr>
                                <th><?= lang('day') ?></th>
                                <th><?= lang('start') ?></th>
                                <th><?= lang('end') ?></th>
                            </tr>
                            </thead>
                            <tbody><!-- Dynamic Content --></tbody>
                        </table>
                    </div>

                    <br>

                    <h4 class="mb-3 fw-light">
                        <?= lang('breaks') ?>
                    </h4>

                    <p>
                        <?= lang('add_breaks_during_each_day') ?>
                    </p>

                    <div>
                        <button type="button" class="add-break btn btn-primary">
                            <i class="fas fa-plus-square me-2"></i>
                            <?= lang('add_break') ?>
                        </button>
                    </div>

                    <br>

                    <div class="table-responsive">
                        <table class="breaks table table-striped">
                            <thead>
                            <tr>
                                <th><?= lang('day') ?></th>
                                <th><?= lang('start') ?></th>
                                <th><?= lang('end') ?></th>
                                <th><?= lang('actions') ?></th>
                            </tr>
                            </thead>
                            <tbody><!-- Dynamic Content --></tbody>
                        </table>
                    </div>

                    <br>

                    <h4 class="mb-3 fw-light">
                        <?= lang('working_plan_exceptions') ?>
                    </h4>

                    <p>
                        <?= lang('add_working_plan_exceptions_during_each_day') ?>
                    </p>

                    <div>
                        <button type="button" class="add-working-plan-exception btn btn-primary me-2">
                            <i class="fas fa-plus-square me-2"></i>
                            <?= lang('add_working_plan_exception') ?>
                        </button>
                    </div>

                    <br>

                    <div class="table-responsive">
                        <table class="working-plan-exceptions table table-striped">
                            <thead>
                            <tr>
                                <th><?= lang('date') ?></th>
                                <th><?= lang('start') ?></th>
                                <th><?= lang('end') ?></th>
                                <th><?= lang('actions') ?></th>
                            </tr>
                            </thead>
                            <tbody><!-- Dynamic Content --></tbody>
                        </table>
                    </div>

                    <?php component('working_plan_exceptions_modal'); ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/vendor/jquery-jeditable/jquery.jeditable.min.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/ui.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/working_plan.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/account_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/providers_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/providers.js') ?>"></script>

<?php end_section('scripts'); ?>



