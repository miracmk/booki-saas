<?php
/**
 * Local variables.
 *
 * @var array $available_services
 * @var array $appointment_status_options
 * @var array $timezones
 * @var array $require_first_name
 * @var array $require_last_name
 * @var array $require_email
 * @var array $require_phone_number
 * @var array $require_address
 * @var array $require_city
 * @var array $require_zip_code
 * @var array $require_notes
 */
?>
<div id="appointments-modal" class="modal fade">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-lg-down modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><?= lang('edit_appointment_title') ?></h3>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="modal-message alert d-none"></div>

                <form>
                    <fieldset>
                        <h5 class="mb-3 fw-light"><?= lang('appointment_details_title') ?></h5>

                        <input id="appointment-id" type="hidden">

                        <div class="row">
                            <div class="col-12">
                                <fieldset class="sf-step mb-3" data-step="1">
                                    <div class="mb-3">
                                        <label for="start-datetime" class="form-label">
                                            <span class="badge bg-secondary sf-step-badge">1</span>
                                            <?= lang('start_date_time') ?>
                                        </label>
                                        <input id="start-datetime" class="required form-control">
                                    </div>

                                    <div class="mb-3">
                                        <label for="end-datetime" class="form-label"><?= lang(
                                            'end_date_time',
                                        ) ?></label>
                                        <input id="end-datetime" class="required form-control">
                                    </div>
                                </fieldset>

                                <fieldset class="sf-step mb-3" data-step="2">
                                    <div class="mb-3">
                                        <label for="select-service" class="form-label">
                                            <span class="badge bg-secondary sf-step-badge">2</span>
                                            <?= lang('service') ?>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select id="select-service" class="required form-select">
                                            <?php
                                            // Group services by category, only if there is at least one service
                                            // with a parent category.
                                            $has_category = false;

                                            foreach ($available_services as $service) {
                                                if (!empty($service['service_category_id'])) {
                                                    $has_category = true;
                                                    break;
                                                }
                                            }

                                            if ($has_category) {
                                                $grouped_services = [];

                                                foreach ($available_services as $service) {
                                                    if (!empty($service['service_category_id'])) {
                                                        if (!isset($grouped_services[$service['service_category_name']])) {
                                                            $grouped_services[$service['service_category_name']] = [];
                                                        }

                                                        $grouped_services[$service['service_category_name']][] = $service;
                                                    }
                                                }

                                                // We need the uncategorized services at the end of the list, so we will use
                                                // another iteration only for the uncategorized services.
                                                $grouped_services['uncategorized'] = [];

                                                foreach ($available_services as $service) {
                                                    if ($service['service_category_id'] == null) {
                                                        $grouped_services['uncategorized'][] = $service;
                                                    }
                                                }

                                                foreach ($grouped_services as $key => $group) {
                                                    $group_label =
                                                        $key !== 'uncategorized'
                                                            ? e($group[0]['service_category_name'])
                                                            : 'Uncategorized';

                                                    if (count($group) > 0) {
                                                        echo '<optgroup label="' . $group_label . '">';

                                                        foreach ($group as $service) {
                                                            echo '<option value="' .
                                                                $service['id'] .
                                                                '">' .
                                                                e($service['name']) .
                                                                '</option>';
                                                        }

                                                        echo '</optgroup>';
                                                    }
                                                }
                                            } else {
                                                foreach ($available_services as $service) {
                                                    echo '<option value="' .
                                                        $service['id'] .
                                                        '">' .
                                                        e($service['name']) .
                                                        '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <button type="button" id="salonflora-first-availability-btn"
                                                class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-bolt me-1"></i>
                                            İlk Müsaitlik
                                        </button>
                                        <div id="salonflora-first-availability-results" class="mt-2 d-none"></div>
                                    </div>

                                    <div class="mb-3 row g-2">
                                        <div class="col-6">
                                            <label for="salonflora-custom-duration" class="form-label">
                                                Özel Süre (dk)
                                            </label>
                                            <input type="number" id="salonflora-custom-duration" min="1"
                                                   class="form-control form-control-sm"
                                                   placeholder="Hizmetin süresi">
                                        </div>
                                        <div class="col-6">
                                            <label for="salonflora-price-override" class="form-label">
                                                Sabit Fiyat (TL)
                                            </label>
                                            <input type="number" id="salonflora-price-override" min="0" step="0.01"
                                                   class="form-control form-control-sm"
                                                   placeholder="Otomatik hesapla">
                                        </div>
                                        <div class="form-text text-muted salonflora-price-preview"></div>
                                    </div>
                                </fieldset>

                                <fieldset class="sf-step mb-3" data-step="3">
                                    <div class="mb-3">
                                        <label for="select-provider" class="form-label">
                                            <span class="badge bg-secondary sf-step-badge">3</span>
                                            <?= lang('provider') ?>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select id="select-provider" class="required form-select"></select>
                                        <div class="form-text text-muted sf-provider-hint"></div>
                                    </div>
                                </fieldset>

                                <fieldset class="sf-step mb-3" data-step="4">
                                    <div class="mb-3 salonflora-station-panel">
                                        <label for="salonflora-station-select" class="form-label">
                                            <span class="badge bg-secondary sf-step-badge">4</span>
                                            İstasyon
                                            <small class="text-muted salonflora-station-mode"></small>
                                        </label>
                                        <select id="salonflora-station-select" class="form-select form-select-sm"></select>
                                        <div class="form-text text-muted sf-station-hint"></div>
                                    </div>
                                </fieldset>

                                <div class="mb-3 salonflora-checkinout-panel d-none">
                                    <label class="form-label">
                                        Seans Takibi
                                    </label>
                                    <div class="border rounded p-2">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small>
                                                Başlangıç:
                                                <span class="salonflora-actual-start">-</span>
                                            </small>
                                            <div>
                                                <button type="button" id="salonflora-edit-session-start"
                                                        class="btn btn-outline-primary btn-sm d-none">
                                                    Düzenle
                                                </button>
                                                <button type="button" id="salonflora-check-in"
                                                        class="btn btn-outline-success btn-sm">
                                                    Seansı Başlat
                                                </button>
                                            </div>
                                        </div>
                                        <div class="d-none mb-2 salonflora-edit-session-start-row">
                                            <input type="datetime-local" id="salonflora-session-start-input"
                                                   class="form-control form-control-sm d-inline-block w-auto">
                                            <button type="button" id="salonflora-save-session-start"
                                                    class="btn btn-primary btn-sm">Kaydet</button>
                                            <button type="button" id="salonflora-cancel-session-start"
                                                    class="btn btn-outline-secondary btn-sm">İptal</button>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small>
                                                Bitiş:
                                                <span class="salonflora-actual-end">-</span>
                                            </small>
                                            <div>
                                                <button type="button" id="salonflora-edit-session-end"
                                                        class="btn btn-outline-primary btn-sm d-none">
                                                    Düzenle
                                                </button>
                                                <button type="button" id="salonflora-check-out"
                                                        class="btn btn-outline-secondary btn-sm">
                                                    Seansı Bitir
                                                </button>
                                            </div>
                                        </div>
                                        <div class="d-none mt-2 salonflora-edit-session-end-row">
                                            <input type="datetime-local" id="salonflora-session-end-input"
                                                   class="form-control form-control-sm d-inline-block w-auto">
                                            <button type="button" id="salonflora-save-session-end"
                                                    class="btn btn-primary btn-sm">Kaydet</button>
                                            <button type="button" id="salonflora-cancel-session-end"
                                                    class="btn btn-outline-secondary btn-sm">İptal</button>
                                        </div>
                                        <button type="button" id="salonflora-clear-session" class="btn btn-outline-danger btn-sm mt-2">Sıfırla</button>
                                        <div class="small text-muted mt-2 d-none salonflora-session-deviation"></div>
                                    </div>
                                </div>

                                <div class="mb-3 salonflora-payment-panel d-none">
                                    <label class="form-label">
                                        Tahsilat
                                    </label>
                                    <div class="border rounded p-2">
                                        <div class="small mb-2">
                                            <strong>Durum:</strong>
                                            <span class="salonflora-payment-summary">-</span>
                                        </div>
                                        <button type="button" id="salonflora-edit-payment" class="btn btn-outline-primary btn-sm">
                                            Tahsilat Bilgisini Düzenle
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <?php // 2026-09-12 - kullanıcı isteğiyle saat dilimi bilgisi gizlendi (görsel
                                      // gürültü, personel için gereksiz). JS'in `.provider-timezone` metnini
                                      // set etmesi zararsız olduğu için eleman DOM'da kalıyor, sadece d-none. ?>
                                <div class="mb-3 d-none">
                                    <label class="form-label">
                                        <?= lang('timezone') ?>
                                    </label>

                                    <div
                                        class="border rounded d-flex justify-content-between align-items-center bg-light timezone-info">
                                        <div class="border-end w-50 p-1 text-center">
                                            <small>
                                                <?= lang('provider') ?>:
                                                <span class="provider-timezone">
                                                    -
                                                </span>
                                            </small>
                                        </div>
                                        <div class="w-50 p-1 text-center">
                                            <small>
                                                <?= lang('current_user') ?>:
                                                <span>
                                                    <?= $timezones[session('timezone', 'UTC')] ?>
                                                </span>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="appointment-notes" class="form-label">
                                        <?= lang('notes') ?>
                                        <?php if ($require_notes): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </label>
                                    <textarea id="appointment-notes" class="<?= $require_notes
                                        ? 'required'
                                        : '' ?> form-control" rows="3"></textarea>
                                </div>

                                <div class="accordion" id="sf-other-settings-accordion">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#sf-other-settings-body">
                                                Diğer Ayarlar
                                            </button>
                                        </h2>
                                        <div id="sf-other-settings-body" class="accordion-collapse collapse">
                                            <div class="accordion-body">
                                                <div class="mb-3">
                                                    <?php component('color_selection', [
                                                        'attributes' => 'id="appointment-color"',
                                                    ]); ?>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="appointment-location" class="form-label">
                                                        <?= lang('location') ?>
                                                    </label>
                                                    <input id="appointment-location" class="form-control">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="appointment-meeting-link" class="form-label">
                                                        <?= lang('meeting_link') ?>
                                                    </label>
                                                    <input id="appointment-meeting-link" class="form-control"
                                                           placeholder="https://">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="appointment-status" class="form-label">
                                                        <?= lang('status') ?>
                                                    </label>
                                                    <select id="appointment-status" class="form-select">
                                                        <?php foreach (
                                                            $appointment_status_options
                                                            as $appointment_status_option
                                                        ): ?>
                                                            <option value="<?= e($appointment_status_option) ?>">
                                                                <?= e($appointment_status_option) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <br>

                    <fieldset class="sf-step" data-step="5">
                        <h5 class="mb-3 fw-light">
                            <span class="badge bg-secondary sf-step-badge">5</span>
                            <?= lang('customer_details_title') ?>
                            <button id="new-customer" class="btn btn-outline-secondary btn-sm" type="button"
                                    data-tippy-content="<?= lang('clear_fields_add_existing_customer_hint') ?>">
                                <i class="fas fa-plus-square me-2"></i>
                                <?= lang('new') ?>
                            </button>
                            <button id="select-customer" class="btn btn-outline-secondary btn-sm" type="button"
                                    data-tippy-content="<?= lang('pick_existing_customer_hint') ?>">
                                <i class="fas fa-hand-pointer me-2"></i>
                                <span>
                                    <?= lang('select') ?>
                                </span>
                            </button>

                            <input id="filter-existing-customers"
                                   placeholder="<?= lang('type_to_filter_customers') ?>"
                                   style="display: none;" class="input-sm form-control">
                        </h5>

                        <div id="existing-customers-list" style="display: none;"></div>

                        <input id="customer-id" type="hidden">

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="first-name" class="form-label">
                                        <?= lang('first_name') ?>
                                        <?php if ($require_first_name): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </label>
                                    <input type="text" id="first-name"
                                           class="<?= $require_first_name ? 'required' : '' ?> form-control"
                                           maxlength="100"/>
                                </div>

                                <div class="mb-3">
                                    <label for="last-name" class="form-label">
                                        <?= lang('last_name') ?>
                                        <?php if ($require_last_name): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </label>
                                    <input type="text" id="last-name"
                                           class="<?= $require_last_name ? 'required' : '' ?> form-control"
                                           maxlength="120"/>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <?= lang('email') ?>
                                        <?php if ($require_email): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </label>
                                    <input type="text" id="email"
                                           class="<?= $require_email ? 'required' : '' ?> form-control"
                                           maxlength="120"/>
                                </div>

                                <div class="mb-3">
                                    <label for="phone-number" class="form-label">
                                        <?= lang('phone_number') ?>
                                        <?php if ($require_phone_number): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </label>
                                    <input type="text" id="phone-number" maxlength="60"
                                           class="<?= $require_phone_number ? 'required' : '' ?> form-control"/>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="language">
                                        <?= lang('language') ?>
                                        <span class="text-danger" hidden>*</span>
                                    </label>
                                    <select id="language" class="form-select required">
                                        <?php foreach (vars('available_languages') as $available_language): ?>
                                            <option value="<?= $available_language ?>">
                                                <?= ucfirst($available_language) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <?php component('custom_fields'); ?>

                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="address" class="form-label">
                                        <?= lang('address') ?>
                                        <?php if ($require_address): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </label>
                                    <input type="text" id="address"
                                           class="<?= $require_address ? 'required' : '' ?> form-control"
                                           maxlength="120"/>
                                </div>

                                <div class="mb-3">
                                    <label for="city" class="form-label">
                                        İlçe
                                        <?php if ($require_city): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </label>
                                    <select id="city" class="<?= $require_city ? 'required' : '' ?> form-select">
                                        <option value="">-</option>
                                        <?php
                                        // Salon Flora customization - see SALONFLORA_BURSA_DISTRICTS (constants.php).
                                        foreach (SALONFLORA_BURSA_DISTRICTS as $district) {
                                            echo '<option value="' . e($district) . '">' . e($district) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="state" class="form-label">
                                        Semt / Mahalle
                                    </label>
                                    <input type="text" id="state" class="form-control" maxlength="120"/>
                                </div>

                                <div class="mb-3">
                                    <label for="zip-code" class="form-label">
                                        <?= lang('zip_code') ?>
                                        <?php if ($require_zip_code): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </label>
                                    <input type="text" id="zip-code"
                                           class="<?= $require_zip_code ? 'required' : '' ?> form-control"
                                           maxlength="120"/>
                                </div>

                                <?php // 2026-09-12 - kullanıcı isteğiyle gizlendi; `required` de kaldırıldı
                                      // (görünmeyen bir alanı zorunlu tutmak formu kilitler), select DOM'da
                                      // kalıyor (JS'in varsayılan değer ataması varsa bozulmasın diye). ?>
                                <div class="mb-3 d-none">
                                    <label class="form-label" for="timezone">
                                        <?= lang('timezone') ?>
                                        <span class="text-danger" hidden>*</span>
                                    </label>
                                    <?php component('timezone_dropdown', [
                                        'attributes' => 'id="timezone" class="form-select"',
                                        'grouped_timezones' => vars('grouped_timezones'),
                                    ]); ?>
                                </div>

                                <div class="mb-3">
                                    <label for="customer-notes" class="form-label">
                                        <?= lang('notes') ?>
                                    </label>
                                    <textarea id="customer-notes" rows="3" class="form-control"></textarea>
                                </div>

                            </div>
                        </div>
                    </fieldset>

                </form>
            </div>

            <div class="modal-footer">

                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <?= lang('cancel') ?>
                </button>
                <button id="save-appointment" class="btn btn-primary">
                    <i class="fas fa-check-square me-2"></i>
                    <?= lang('save') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/components/appointments_modal.js') ?>"></script>

<?php end_section('scripts'); ?>
