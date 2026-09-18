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
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-lg-down modal-xl">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light border-bottom py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-2 text-primary">
                        <i class="fas fa-calendar-check fs-5"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold mb-0 text-dark"><?= lang('edit_appointment_title') ?></h4>
                        <small class="text-muted" id="appointment-modal-subtitle">Hızlı rezervasyon ve operasyonel randevu yönetimi</small>
                    </div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="modal-message alert d-none mb-3"></div>

                <div class="row g-4">
                    <!-- LEFT COLUMN: Booking Step Form -->
                    <div class="col-lg-8 border-end pe-lg-4">
                        <form id="appointment-main-form">
                            <!-- Customer Context Badge Banner -->
                            <div id="customer-context-banner" class="card border border-primary border-opacity-25 bg-primary bg-opacity-10 mb-4 p-3 rounded-3 d-none">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px; font-size: 18px;" id="ctx-cust-avatar">
                                            A
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <h6 class="fw-bold mb-0 text-dark" id="ctx-cust-name">-</h6>
                                                <span class="badge bg-warning text-dark border border-warning" id="ctx-cust-vip-badge">VIP Müşteri</span>
                                                <span class="badge bg-success" id="ctx-cust-pkg-badge">Paket: 4 Seans Kalan</span>
                                            </div>
                                            <div class="small text-muted mt-1" id="ctx-cust-details">
                                                <i class="fas fa-phone me-1"></i><span id="ctx-cust-phone">-</span> · 
                                                <i class="fas fa-envelope me-1 ms-2"></i><span id="ctx-cust-email">-</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-open-ctx-360">
                                            <i class="fas fa-id-card me-1"></i>360° Profil
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <fieldset>
                                <input id="appointment-id" type="hidden">

                                <div class="row">
                                    <div class="col-12">
                                        <!-- Step 1: Zaman Seçimi -->
                                        <fieldset class="sf-step mb-4" data-step="1">
                                            <label class="form-label fw-bold text-dark mb-2">
                                                <span class="badge bg-primary rounded-circle me-1">1</span>
                                                Tarih & Saat Aralığı
                                            </label>
                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i class="fas fa-calendar-alt text-muted"></i></span>
                                                        <input id="start-datetime" class="required form-control" placeholder="<?= lang('start_date_time') ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i class="fas fa-clock text-muted"></i></span>
                                                        <input id="end-datetime" class="required form-control" placeholder="<?= lang('end_date_time') ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </fieldset>

                                        <!-- Step 2: Hizmet & Ek Hizmetler (Add-ons) -->
                                        <fieldset class="sf-step mb-4" data-step="2">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label for="select-service" class="form-label fw-bold text-dark mb-0">
                                                    <span class="badge bg-primary rounded-circle me-1">2</span>
                                                    <?= lang('service') ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <button type="button" id="salonflora-first-availability-btn" class="btn btn-outline-primary btn-sm py-1">
                                                    <i class="fas fa-bolt text-warning me-1"></i>İlk Müsaitlik Bul
                                                </button>
                                            </div>
                                            <select id="select-service" class="required form-select mb-2">
                                                <?php
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
                                                    $grouped_services['uncategorized'] = [];
                                                    foreach ($available_services as $service) {
                                                        if ($service['service_category_id'] == null) {
                                                            $grouped_services['uncategorized'][] = $service;
                                                        }
                                                    }

                                                    foreach ($grouped_services as $key => $group) {
                                                        $group_label = $key !== 'uncategorized' ? e($group[0]['service_category_name']) : 'Genel';
                                                        if (count($group) > 0) {
                                                            echo '<optgroup label="' . $group_label . '">';
                                                            foreach ($group as $service) {
                                                                echo '<option value="' . $service['id'] . '">' . e($service['name']) . ' (' . $service['duration'] . ' dk - ' . $service['price'] . ' ₺)</option>';
                                                            }
                                                            echo '</optgroup>';
                                                        }
                                                    }
                                                } else {
                                                    foreach ($available_services as $service) {
                                                        echo '<option value="' . $service['id'] . '">' . e($service['name']) . ' (' . $service['duration'] . ' dk - ' . $service['price'] . ' ₺)</option>';
                                                    }
                                                }
                                                ?>
                                            </select>

                                            <div id="salonflora-first-availability-results" class="mt-2 d-none"></div>

                                            <!-- Dynamic Service Add-ons Container -->
                                            <div id="service-addons-selector" class="border rounded p-3 bg-light bg-opacity-50 mt-2 mb-2 d-none">
                                                <label class="form-label small fw-bold text-secondary mb-2">
                                                    <i class="fas fa-puzzle-piece text-primary me-1"></i>Ek Hizmet & Opsiyon Seçimi (Add-ons)
                                                </label>
                                                <div id="addons-checkbox-list" class="d-flex flex-wrap gap-2">
                                                    <!-- Dynamic Add-on checkboxes -->
                                                </div>
                                            </div>

                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <label for="salonflora-custom-duration" class="form-label small text-muted">
                                                        Özel Süre (dk)
                                                    </label>
                                                    <input type="number" id="salonflora-custom-duration" min="1"
                                                           class="form-control form-control-sm"
                                                           placeholder="Standart süre">
                                                </div>
                                                <div class="col-6">
                                                    <label for="salonflora-price-override" class="form-label small text-muted">
                                                        Sabit Fiyat (TL)
                                                    </label>
                                                    <input type="number" id="salonflora-price-override" min="0" step="0.01"
                                                           class="form-control form-control-sm"
                                                           placeholder="Otomatik fiyat">
                                                </div>
                                                <div class="form-text text-muted salonflora-price-preview"></div>
                                            </div>
                                        </fieldset>

                                        <!-- Step 3: Uzman / Personel -->
                                        <fieldset class="sf-step mb-4" data-step="3">
                                            <label for="select-provider" class="form-label fw-bold text-dark">
                                                <span class="badge bg-primary rounded-circle me-1">3</span>
                                                <?= lang('provider') ?>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select id="select-provider" class="required form-select"></select>
                                            <div class="form-text text-muted sf-provider-hint"></div>
                                        </fieldset>

                                        <!-- Step 4: İstasyon / Oda -->
                                        <fieldset class="sf-step mb-4" data-step="4">
                                            <div class="salonflora-station-panel">
                                                <label for="salonflora-station-select" class="form-label fw-bold text-dark">
                                                    <span class="badge bg-primary rounded-circle me-1">4</span>
                                                    İstasyon / Oda
                                                    <small class="text-muted salonflora-station-mode fw-normal"></small>
                                                </label>
                                                <select id="salonflora-station-select" class="form-select"></select>
                                                <div class="form-text text-muted sf-station-hint"></div>
                                            </div>
                                        </fieldset>

                                        <!-- Session Tracking Panel (When editing existing) -->
                                        <div class="mb-4 salonflora-checkinout-panel d-none">
                                            <label class="form-label fw-bold text-dark">
                                                <i class="fas fa-user-clock text-info me-1"></i>Seans Takibi & Check-in
                                            </label>
                                            <div class="border rounded p-3 bg-light">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div>
                                                        <small class="text-muted">Fiili Başlangıç:</small>
                                                        <strong class="salonflora-actual-start ms-1">-</strong>
                                                    </div>
                                                    <div>
                                                        <button type="button" id="salonflora-edit-session-start" class="btn btn-outline-primary btn-sm d-none">Düzenle</button>
                                                        <button type="button" id="salonflora-check-in" class="btn btn-success btn-sm"><i class="fas fa-play me-1"></i>Seansı Başlat</button>
                                                    </div>
                                                </div>
                                                <div class="d-none mb-2 salonflora-edit-session-start-row">
                                                    <input type="datetime-local" id="salonflora-session-start-input" class="form-control form-control-sm d-inline-block w-auto">
                                                    <button type="button" id="salonflora-save-session-start" class="btn btn-primary btn-sm">Kaydet</button>
                                                    <button type="button" id="salonflora-cancel-session-start" class="btn btn-outline-secondary btn-sm">İptal</button>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <small class="text-muted">Fiili Bitiş:</small>
                                                        <strong class="salonflora-actual-end ms-1">-</strong>
                                                    </div>
                                                    <div>
                                                        <button type="button" id="salonflora-edit-session-end" class="btn btn-outline-primary btn-sm d-none">Düzenle</button>
                                                        <button type="button" id="salonflora-check-out" class="btn btn-outline-secondary btn-sm"><i class="fas fa-stop me-1"></i>Seansı Bitir</button>
                                                    </div>
                                                </div>
                                                <div class="d-none mt-2 salonflora-edit-session-end-row">
                                                    <input type="datetime-local" id="salonflora-session-end-input" class="form-control form-control-sm d-inline-block w-auto">
                                                    <button type="button" id="salonflora-save-session-end" class="btn btn-primary btn-sm">Kaydet</button>
                                                    <button type="button" id="salonflora-cancel-session-end" class="btn btn-outline-secondary btn-sm">İptal</button>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                                    <button type="button" id="salonflora-clear-session" class="btn btn-link text-danger btn-sm p-0">Sıfırla</button>
                                                    <div class="small text-muted salonflora-session-deviation"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tahsilat Paneli -->
                                        <div class="mb-4 salonflora-payment-panel d-none">
                                            <label class="form-label fw-bold text-dark">
                                                <i class="fas fa-cash-register text-success me-1"></i>Tahsilat Durumu
                                            </label>
                                            <div class="border rounded p-3 bg-light d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="salonflora-payment-summary fw-semibold text-dark">-</span>
                                                </div>
                                                <button type="button" id="salonflora-edit-payment" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-receipt me-1"></i>Tahsilat Bilgisini Düzenle
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="appointment-notes" class="form-label fw-bold text-dark">
                                                <?= lang('notes') ?>
                                                <?php if ($require_notes): ?>
                                                    <span class="text-danger">*</span>
                                                <?php endif; ?>
                                            </label>
                                            <textarea id="appointment-notes" class="<?= $require_notes ? 'required' : '' ?> form-control" rows="2" placeholder="Randevu özel notları..."></textarea>
                                        </div>

                                        <div class="accordion mb-4" id="sf-other-settings-accordion">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#sf-other-settings-body">
                                                        <i class="fas fa-sliders-h text-muted me-2"></i>Ek Ayarlar & Durum
                                                    </button>
                                                </h2>
                                                <div id="sf-other-settings-body" class="accordion-collapse collapse">
                                                    <div class="accordion-body">
                                                        <div class="mb-3">
                                                            <?php component('color_selection', ['attributes' => 'id="appointment-color"']); ?>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="appointment-location" class="form-label"><?= lang('location') ?></label>
                                                            <input id="appointment-location" class="form-control">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="appointment-meeting-link" class="form-label"><?= lang('meeting_link') ?></label>
                                                            <input id="appointment-meeting-link" class="form-control" placeholder="https://">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="appointment-status" class="form-label"><?= lang('status') ?></label>
                                                            <select id="appointment-status" class="form-select">
                                                                <?php foreach ($appointment_status_options as $appointment_status_option): ?>
                                                                    <option value="<?= e($appointment_status_option) ?>"><?= e($appointment_status_option) ?></option>
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

                            <!-- Step 5: Müşteri Bilgileri -->
                            <fieldset class="sf-step" data-step="5">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0 fw-bold text-dark">
                                        <span class="badge bg-primary rounded-circle me-1">5</span>
                                        <?= lang('customer_details_title') ?>
                                    </h5>
                                    <div class="btn-group btn-group-sm">
                                        <button id="new-customer" class="btn btn-outline-secondary" type="button">
                                            <i class="fas fa-plus me-1"></i><?= lang('new') ?>
                                        </button>
                                        <button id="select-customer" class="btn btn-outline-primary" type="button">
                                            <i class="fas fa-search me-1"></i><span><?= lang('select') ?></span>
                                        </button>
                                    </div>
                                </div>

                                <input id="filter-existing-customers" placeholder="<?= lang('type_to_filter_customers') ?>" style="display: none;" class="form-control mb-3">
                                <div id="existing-customers-list" style="display: none;" class="mb-3"></div>

                                <input id="customer-id" type="hidden">

                                <div class="row g-2">
                                    <div class="col-md-6 mb-2">
                                        <label for="first-name" class="form-label small text-muted">
                                            <?= lang('first_name') ?><?= $require_first_name ? ' <span class="text-danger">*</span>' : '' ?>
                                        </label>
                                        <input type="text" id="first-name" class="<?= $require_first_name ? 'required' : '' ?> form-control" maxlength="100"/>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label for="last-name" class="form-label small text-muted">
                                            <?= lang('last_name') ?><?= $require_last_name ? ' <span class="text-danger">*</span>' : '' ?>
                                        </label>
                                        <input type="text" id="last-name" class="<?= $require_last_name ? 'required' : '' ?> form-control" maxlength="120"/>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label for="phone-number" class="form-label small text-muted">
                                            <?= lang('phone_number') ?><?= $require_phone_number ? ' <span class="text-danger">*</span>' : '' ?>
                                        </label>
                                        <input type="text" id="phone-number" maxlength="60" class="<?= $require_phone_number ? 'required' : '' ?> form-control"/>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label for="email" class="form-label small text-muted">
                                            <?= lang('email') ?><?= $require_email ? ' <span class="text-danger">*</span>' : '' ?>
                                        </label>
                                        <input type="text" id="email" class="<?= $require_email ? 'required' : '' ?> form-control" maxlength="120"/>
                                    </div>
                                    <div class="col-12 mb-2 d-none">
                                        <select id="language" class="form-select required">
                                            <?php foreach (vars('available_languages') as $available_language): ?>
                                                <option value="<?= $available_language ?>"><?= ucfirst($available_language) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" id="address" class="form-control" maxlength="120"/>
                                        <select id="city" class="form-select"><option value="">-</option></select>
                                        <input type="text" id="state" class="form-control" maxlength="120"/>
                                        <input type="text" id="zip-code" class="form-control" maxlength="120"/>
                                        <textarea id="customer-notes" rows="2" class="form-control"></textarea>
                                    </div>
                                </div>
                            </fieldset>
                        </form>
                    </div>

                    <!-- RIGHT COLUMN: Live Summary & Fast Operational Panel -->
                    <div class="col-lg-4 ps-lg-4">
                        <div class="card border rounded-3 p-3 bg-light shadow-sm sticky-top" style="top: 15px;">
                            <div class="d-flex justify-content-between align-items-center pb-3 mb-3 border-bottom">
                                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-clipboard-list text-primary me-2"></i>Randevu Özeti</h6>
                                <span class="badge bg-primary" id="summary-status-badge">Yeni Randevu</span>
                            </div>

                            <!-- Customer Summary -->
                            <div class="mb-3">
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Müşteri</small>
                                <div class="fw-bold text-dark fs-6 mt-1" id="summary-customer-name">Seçilmedi</div>
                                <div class="small text-muted" id="summary-customer-phone">-</div>
                            </div>

                            <!-- Timing Summary -->
                            <div class="mb-3">
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Zaman & Süre</small>
                                <div class="fw-semibold text-dark mt-1" id="summary-datetime">-</div>
                                <div class="small text-muted" id="summary-duration-breakdown">0 dakika</div>
                            </div>

                            <!-- Service & Addons Breakdown -->
                            <div class="mb-3">
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Hizmet & Ekler</small>
                                <div class="fw-semibold text-dark mt-1" id="summary-service-name">-</div>
                                <div class="small text-muted" id="summary-addons-list">Ek hizmet seçilmedi</div>
                            </div>

                            <!-- Provider & Station -->
                            <div class="mb-3">
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Uzman & İstasyon</small>
                                <div class="fw-semibold text-dark mt-1" id="summary-provider-station">-</div>
                            </div>

                            <!-- Price Breakdown Card -->
                            <div class="card border-0 bg-white p-3 rounded-3 shadow-none border mb-3">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span>Hizmet Ücreti</span>
                                    <span id="summary-base-price">0.00 ₺</span>
                                </div>
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span>Ek Hizmetler</span>
                                    <span id="summary-addons-price">+0.00 ₺</span>
                                </div>
                                <div class="d-flex justify-content-between small text-success mb-2 d-none" id="summary-discount-row">
                                    <span>Paket / Üyelik İndirimi</span>
                                    <span id="summary-discount-price">-0.00 ₺</span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold text-dark pt-2 border-top fs-6">
                                    <span>Toplam Tutar</span>
                                    <span class="text-primary" id="summary-total-price">0.00 ₺</span>
                                </div>
                            </div>

                            <!-- Fast Actions for Existing Appointment -->
                            <div id="summary-fast-actions" class="d-none">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2" id="btn-fast-open-adisyon">
                                    <i class="fas fa-cash-register me-1"></i>Adisyona Git / Tahsilat Al
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light border-top py-3">
                <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                    <?= lang('cancel') ?>
                </button>
                <button id="save-appointment" class="btn btn-primary px-4 fw-semibold">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= lang('save') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/http/appointments_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/components/appointments_modal.js') ?>"></script>

<?php end_section('scripts'); ?>
