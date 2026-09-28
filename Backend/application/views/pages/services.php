<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-3" id="services-page">
    <div class="row" id="services">
        <div id="filter-services" class="filter-records col col-12 mb-4">
            <button id="add-service" class="btn btn-primary add-record-btn mb-4">
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
                <?= lang('services') ?>
            </h4>

            <div class="results overflow-auto" style="max-height: 650px;">
                <!-- JS -->
            </div>
        </div>

        <div class="record-details column col-12 mb-4">
            <div class="btn-toolbar mb-4">
                <div class="add-edit-delete-group btn-group">
                    <button id="edit-service" class="btn btn-outline-secondary" disabled="disabled">
                        <i class="fas fa-edit me-2"></i>
                        <?= lang('edit') ?>
                    </button>
                </div>

                <div class="save-cancel-group" style="display:none;">
                    <button id="save-service" class="btn btn-primary">
                        <i class="fas fa-check-square me-2"></i>
                        <?= lang('save') ?>
                    </button>
                    <button id="cancel-service" class="btn btn-outline-secondary">
                        <?= lang('cancel') ?>
                    </button>
                    <button id="delete-service" class="btn btn-outline-danger ms-2">
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
                    <?= lang('name') ?>
                    <span class="text-danger" hidden>*</span>
                </label>
                <input id="name" class="form-control required" maxlength="128" disabled>
            </div>

            <div class="mb-3">
                <label class="form-label" for="duration">
                    <?= lang('duration_minutes') ?>
                    <span class="text-danger" hidden>*</span>
                </label>
                <input id="duration" class="form-control required" type="number" min="<?= EVENT_MINIMUM_DURATION ?>"
                       disabled>
            </div>

            <div class="mb-3">
                <label class="form-label" for="access-type">
                    Hizmet Süre & Geçiş Modeli (Erişim Tipi)
                </label>
                <select id="access-type" class="form-select" disabled>
                    <option value="duration">⏱️ Süreli Seans (Standart Randevu Süresi - örn: 60/90 dk)</option>
                    <option value="open_ended">☕ Süresiz / Açık Adisyon (Restoran, Kafe, Bar, Ören Yeri)</option>
                    <option value="daily_pass">🎟️ Günlük Giriş / Günlük Pass (Genel Hamam, Plaj, Açık Havuz)</option>
                    <option value="multi_pass">🔢 Çok Girişli Paket / Seanslı Kart (Örn: 10 Girişlik Hamam/Spa Kartı)</option>
                </select>
                <div class="form-text small text-muted">
                    İşletme modelinize göre müşteriye çıkış saati sormadan açık adisyon, günlük pass veya çok girişli paket uygulayabilirsiniz.
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="price">
                    <?= lang('price') ?>
                    <span class="text-danger" hidden>*</span>
                </label>
                <input id="price" class="form-control required" disabled>
            </div>

            <div class="mb-3">
                <label class="form-label" for="currency">
                    <?= lang('currency') ?>

                </label>
                <input id="currency" class="form-control" maxlength="32" disabled>
            </div>

            <div class="mb-3">
                <label class="form-label" for="service-category-id">
                    <?= lang('category') ?>
                </label>
                <select id="service-category-id" class="form-select" disabled></select>
            </div>

            <div class="mb-3">
                <label class="form-label" for="slot-interval">
                    <?= lang('slot_interval') ?>
                    <span class="text-danger" hidden>*</span>
                </label>
                <input id="slot-interval" class="form-control required" type="number" min="1" disabled>
            </div>

            <div class="mb-3">
                <label class="form-label" for="attendants-number">
                    <?= lang('attendants_number') ?>
                    <span class="text-danger" hidden>*</span>
                </label>
                <input id="attendants-number" class="form-control required" type="number" min="1" disabled>
            </div>

            <div class="mb-3">
                <label class="form-label" for="location">
                    <?= lang('location') ?>
                </label>
                <input id="location" class="form-control" disabled>
            </div>

            <div class="mb-3">
                <?php component('color_selection', ['attributes' => 'id="color"']); ?>
            </div>

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

                <div class="form-text text-muted">
                    <small>
                        <?= lang('private_hint') ?>
                    </small>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="description">
                    <?= lang('description') ?>
                </label>
                <textarea id="description" rows="4" class="form-control" disabled></textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="form-label mb-0">
                    <?= lang('providers') ?>
                </label>
                <div class="btn-group btn-group-sm">
                    <button type="button" id="select-all-providers" class="btn btn-outline-secondary" disabled>
                        <?= lang('select_all') ?>
                    </button>
                    <button type="button" id="select-none-providers" class="btn btn-outline-secondary" disabled>
                        <?= lang('select_none') ?>
                    </button>
                </div>
            </div>

            <div id="service-providers" class="card card-body border mb-3">
                <?php foreach (vars('providers') as $provider): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               id="provider-<?= $provider['id'] ?>"
                               data-id="<?= $provider['id'] ?>" disabled>
                        <label class="form-check-label" for="provider-<?= $provider['id'] ?>">
                            <?= e($provider['first_name'] . ' ' . $provider['last_name']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- World-Class SaaS: Ek Hizmetler (Add-ons) -->
            <div class="card border mb-3" id="service-addons-card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold text-dark"><i class="fas fa-puzzle-piece text-primary me-2"></i>Ek Hizmetler & Opsiyonlar (Add-ons)</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-addon-modal">
                        <i class="fas fa-plus me-1"></i>Ek Hizmet Ekle
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="service-addons-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Ek Hizmet</th>
                                    <th>Ek Süre (dk)</th>
                                    <th>Ek Fiyat</th>
                                    <th class="text-end">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-muted text-center py-3"><td colspan="4">Kayıtlı ek hizmet bulunamadı.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- World-Class SaaS: Otomatik Stok Sarfiyat Reçetesi -->
            <div class="card border mb-3" id="service-consumables-card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <div>
                        <span class="fw-semibold text-dark"><i class="fas fa-boxes-stacked text-warning me-2"></i>Otomatik Stok Sarfiyat Reçetesi (Recipe)</span>
                        <small class="text-muted d-block" style="font-size:11px;">Randevu tamamlandığında stoktan otomatik düşecek sarf malzemeler</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-warning" id="btn-add-consumable-modal">
                        <i class="fas fa-plus me-1"></i>Sarf Malzeme Ekle
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="service-consumables-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Ürün / Malzeme</th>
                                    <th>Kullanılan Miktar</th>
                                    <th>Birim Maliyet</th>
                                    <th>Toplam Maliyet</th>
                                    <th>Mevcut Stok</th>
                                    <th class="text-end">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-muted text-center py-3"><td colspan="6">Reçeteye ekli sarf malzeme bulunamadı.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light border-top py-2" id="service-consumables-summary" style="display:none;">
                    <div class="row text-center g-2">
                        <div class="col-4">
                            <small class="text-muted d-block" style="font-size:11px;">Toplam Sarf Maliyeti</small>
                            <span class="fw-bold text-danger" id="summary-total-cost">₺0.00</span>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block" style="font-size:11px;">Hizmet Satış Fiyatı</small>
                            <span class="fw-bold text-dark" id="summary-service-price">₺0.00</span>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block" style="font-size:11px;">Tahmini Brüt Kâr (Marj)</small>
                            <span class="fw-bold text-success" id="summary-gross-profit">₺0.00 (%0)</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Ek Hizmet Ekle -->
<div class="modal fade" id="modal-addon-form" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-puzzle-piece text-primary me-2"></i>Ek Hizmet Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Ek Hizmet Adı *</label>
                    <input type="text" class="form-control" id="addon-name-input" placeholder="Örn: Saç Bakım Maskesi, Masaj Yağı Aromaterapi">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Ek Süre (Dakika)</label>
                        <input type="number" class="form-control" id="addon-duration-input" value="15" min="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Ek Fiyat (₺)</label>
                        <input type="number" step="0.01" class="form-control" id="addon-price-input" value="0.00">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Açıklama</label>
                    <textarea class="form-control" id="addon-desc-input" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="btn-save-addon-submit">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Sarf Malzeme Ekle -->
<div class="modal fade" id="modal-consumable-form" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-boxes-stacked text-warning me-2"></i>Sarf Malzeme Reçetesi Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Ürün / Stok Kalemi *</label>
                    <select class="form-select" id="consumable-product-select">
                        <option value="">-- Ürün Seçin --</option>
                        <?php foreach (vars('products') ?? [] as $prod): ?>
                            <option value="<?= $prod['id'] ?>" data-stock="<?= $prod['stock_quantity'] ?? 0 ?>" data-unit="<?= e($prod['unit'] ?? 'adet') ?>" data-cost="<?= (float) ($prod['cost_price'] ?? 0) ?>">
                                <?= e($prod['name']) ?> (Stok: <?= $prod['stock_quantity'] ?? 0 ?> <?= e($prod['unit'] ?? 'adet') ?> - Maliyet: ₺<?= number_format((float)($prod['cost_price'] ?? 0), 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Kullanılan Miktar *</label>
                        <input type="number" step="0.01" class="form-control" id="consumable-qty-input" value="1.00">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Birim</label>
                        <input type="text" class="form-control" id="consumable-unit-display" value="adet" readonly>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-warning" id="btn-save-consumable-submit">Reçeteye Ekle</button>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/http/services_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/service_categories_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/services.js') ?>"></script>

<?php end_section('scripts'); ?>
