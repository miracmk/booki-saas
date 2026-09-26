<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-3" id="customers-page">
    <div class="row" id="customers">
        <div id="filter-customers" class="filter-records col col-12 mb-4">
            <?php if (
                can('add', PRIV_CUSTOMERS) &&
                (!setting('limit_customer_access') || vars('role_slug') === DB_SLUG_ADMIN)
            ): ?>
                <button id="add-customer" class="btn btn-primary add-record-btn mb-4">
                    <i class="fas fa-plus-square me-2"></i>
                    <?= lang('add') ?>
                </button>
            <?php endif; ?>

            <form class="mb-4">
                <div class="input-group mb-3">
                    <input type="text" class="key form-control" aria-label="keyword">

                    <button class="filter btn btn-outline-secondary" type="submit"
                            data-tippy-content="<?= lang('filter') ?>">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <h4 class="mb-3 fw-light">
                <?= lang('customers') ?>
            </h4>

            <div class="results overflow-auto" style="max-height: 650px;">
                <!-- JS -->
            </div>
        </div>

        <div class="record-details col-12 mb-4">
            <div class="btn-toolbar mb-4">
                <div id="add-edit-delete-group" class="btn-group">

                    <?php if (can('edit', PRIV_CUSTOMERS)): ?>
                        <button id="edit-customer" class="btn btn-outline-secondary" disabled="disabled">
                            <i class="fas fa-edit me-2"></i>
                            <?= lang('edit') ?>
                        </button>
                    <?php endif; ?>
                    <button id="btn-customer-360" class="btn btn-primary" disabled="disabled" onclick="openCustomer360Drawer()">
                        <i class="fas fa-history me-1"></i> 360° Zaman Tüneli & CRM
                    </button>
                </div>

                <div id="save-cancel-group" style="display:none;">
                    <button id="save-customer" class="btn btn-primary">
                        <i class="fas fa-check-square me-2"></i>
                        <?= lang('save') ?>
                    </button>
                    <button id="cancel-customer" class="btn btn-outline-secondary">
                        <?= lang('cancel') ?>
                    </button>
                    <?php if (can('delete', PRIV_CUSTOMERS)): ?>
                        <button id="delete-customer" class="btn btn-outline-danger ms-2">
                            <i class="fas fa-trash-alt me-2"></i>
                            <?= lang('delete') ?>
                        </button>
                        <button id="anonymize-customer" class="btn btn-outline-danger ms-2"
                                title="Randevu/ödeme geçmişini SİLMEDEN müşterinin kimliğini belirleyen tüm bilgilerini (ad-soyad, telefon, e-posta, adres, notlar) kalıcı olarak kaldırır. Geri alınamaz.">
                            <i class="fas fa-user-slash me-2"></i>
                            KVKK - Unutulma Hakkı
                        </button>
                    <?php endif; ?>
                </div>

            </div>

            <input id="customer-id" type="hidden">

            <div class="row">
                <div class="col-12 col-lg-6" style="margin-left: 0;">
                    <h4 class="mb-3 fw-light">
                        <?= lang('details') ?>
                    </h4>

                    <div id="form-message" class="alert" style="display:none;"></div>

                    <div class="mb-3">
                        <label for="first-name" class="form-label">
                            <?= lang('first_name') ?>
                            <?php if (vars('require_first_name')): ?>
                                <span class="text-danger" hidden>*</span>
                            <?php endif; ?>
                        </label>
                        <input type="text" id="first-name"
                               class="<?= vars('require_first_name') ? 'required' : '' ?> form-control" maxlength="100"
                               disabled/>
                    </div>

                    <div class="mb-3">
                        <label for="last-name" class="form-label">
                            <?= lang('last_name') ?>
                            <?php if (vars('require_last_name')): ?>
                                <span class="text-danger" hidden>*</span>
                            <?php endif; ?>
                        </label>
                        <input type="text" id="last-name"
                               class="<?= vars('require_last_name') ? 'required' : '' ?> form-control" maxlength="120"
                               disabled/>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <?= lang('email') ?>
                            <?php if (vars('require_email')): ?>
                                <span class="text-danger" hidden>*</span>
                            <?php endif; ?>
                        </label>
                        <input type="text" id="email"
                               class="<?= vars('require_email') ? 'required' : '' ?> form-control" maxlength="120"
                               disabled/>
                    </div>

                    <div class="mb-3">
                        <label for="phone-number" class="form-label">
                            <?= lang('phone_number') ?>
                            <?php if (vars('require_phone_number')): ?>
                                <span class="text-danger" hidden>*</span>
                            <?php endif; ?>
                        </label>
                        <input type="text" id="phone-number" maxlength="60"
                               class="<?= vars('require_phone_number') ? 'required' : '' ?> form-control" disabled/>
                    </div>

                    <?php // Salon Flora customization - CRM customer card: contact channel links + last-contact label. ?>
                    <div class="mb-3">
                        <label class="form-label d-block">İletişim Kanalları</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text" style="min-width: 4.5rem;">WhatsApp</span>
                            <input type="text" id="social-whatsapp" class="form-control"
                                   placeholder="Telefon no. veya wa.me linki" disabled/>
                        </div>
                        <div class="input-group mb-2">
                            <span class="input-group-text" style="min-width: 4.5rem;">Telegram</span>
                            <input type="text" id="social-telegram" class="form-control"
                                   placeholder="@kullaniciadi veya t.me linki" disabled/>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text" style="min-width: 4.5rem;">Instagram</span>
                            <input type="text" id="social-instagram" class="form-control"
                                   placeholder="@kullaniciadi veya instagram.com linki" disabled/>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="last-contact-channel">Son İletişim Kanalı</label>
                        <select id="last-contact-channel" class="form-select" disabled>
                            <option value="">-</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="telegram">Telegram</option>
                            <option value="instagram">Instagram</option>
                            <option value="phone">Telefon</option>
                            <option value="email">E-posta</option>
                            <option value="in_person">Yüz yüze / Salonda</option>
                        </select>
                        <div class="form-text">
                            Şimdilik personel tarafından elle işaretlenir; mesajlaşma entegrasyonları
                            devreye girince otomatik güncellenecek.
                        </div>
                    </div>

                    <div class="mb-4" id="customer-notification-preferences">
                        <label class="form-label d-block">Bildirim Tercihleri</label>
                        <select id="notification-preference-mode" class="form-select mb-2" disabled>
                            <option value="default">Varsayılan bildirim ayarlarını kullan</option>
                            <option value="custom">Bu müşteriye özel kanalları kullan</option>
                        </select>
                        <div id="custom-notification-channels" class="border rounded p-3">
                            <div class="form-check mb-2">
                                <input type="checkbox" id="notify-email" class="form-check-input" disabled>
                                <label class="form-check-label" for="notify-email">E-posta</label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" id="notify-sms" class="form-check-input" disabled>
                                <label class="form-check-label" for="notify-sms">SMS</label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" id="notify-call" class="form-check-input" disabled>
                                <label class="form-check-label" for="notify-call">Arama</label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" id="notify-whatsapp" class="form-check-input" disabled>
                                <label class="form-check-label" for="notify-whatsapp">WhatsApp</label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" id="notify-telegram" class="form-check-input" disabled>
                                <label class="form-check-label" for="notify-telegram">Telegram</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" id="notify-instagram" class="form-check-input" disabled>
                                <label class="form-check-label" for="notify-instagram">Instagram</label>
                            </div>
                        </div>
                        <div class="form-text">Özel seçim, Ayarlar &gt; Bildirim Ayarları içindeki pasif kanalları kullanmaz.</div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">
                            <?= lang('address') ?>
                            <?php if (vars('require_address')): ?>
                                <span class="text-danger" hidden>*</span>
                            <?php endif; ?>
                        </label>
                        <input type="text" id="address"
                               class="<?= vars('require_address') ? 'required' : '' ?> form-control"
                               maxlength="120" disabled/>
                    </div>

                    <div class="mb-3">
                        <label for="city" class="form-label">
                            İlçe
                            <?php if (vars('require_city')): ?>
                                <span class="text-danger" hidden>*</span>
                            <?php endif; ?>
                        </label>
                        <select id="city" class="<?= vars('require_city') ? 'required' : '' ?> form-select" disabled>
                            <option value="">-</option>
                            <?php
                            // Salon Flora customization - same fixed Bursa district list as the public booking
                            // form (booking_info_step.php) - kept consistent so backend-entered customers use the
                            // same values.
                            foreach (SALONFLORA_BURSA_DISTRICTS as $district) {
                                echo '<option value="' . e($district) . '">' . e($district) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="state">
                            Semt / Mahalle
                        </label>
                        <input type="text" id="state" class="form-control" maxlength="120" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="zip-code" class="form-label">
                            <?= lang('zip_code') ?>
                            <?php if (vars('require_zip_code')): ?>
                                <span class="text-danger" hidden>*</span>
                            <?php endif; ?>
                        </label>
                        <input type="text" id="zip-code"
                               class="<?= vars('require_zip_code') ? 'required' : '' ?> form-control"
                               maxlength="120" disabled/>
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

                    <?php component('custom_fields', [
                        'disabled' => true,
                    ]); ?>

                    <div class="mb-3">
                        <label class="form-label" for="notes">
                            <?= lang('notes') ?>
                        </label>
                        <textarea id="notes" rows="4" class="form-control" disabled></textarea>
                    </div>

                </div>

                <div class="col-12 col-lg-6">
                    <?php // Salon Flora customization - CRM customer card: computed insights (favorite service/
                    // therapist, booking-time preferences, last 3 sessions), derived client-side from the
                    // customer's own appointment history - see renderInsights() in customers.js. ?>
                    <h4 class="mb-3 fw-light">
                        Müşteri İçgörüleri
                    </h4>

                    <div id="customer-insights" class="card border p-3 mb-4 w-100"></div>

                    <h4 class="mb-3 fw-light">
                        <?= lang('appointments') ?>
                    </h4>

                    <div id="customer-appointments" class="card border p-3 overflow-auto mb-4 w-100" style="min-height: 400px; max-height: 800px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Customer 360 Offcanvas Drawer -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="customer-360-drawer" style="width: 650px; max-width: 100%;">
    <div class="offcanvas-header border-bottom bg-light">
        <div>
            <h5 class="offcanvas-title fw-bold" id="c360-name">Müşteri 360° Görünümü</h5>
            <div id="c360-tags" class="mt-1"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0 d-flex flex-column">
        <!-- Customer KPI Metrics -->
        <div class="p-3 border-bottom bg-light bg-opacity-50">
            <div class="row g-2 text-center">
                <div class="col-4">
                    <div class="p-2 bg-white rounded-3 shadow-sm border">
                        <small class="text-muted d-block">Toplam Harcama</small>
                        <span class="fw-bold text-success fs-6" id="c360-total-spent">0.00 ₺</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 bg-white rounded-3 shadow-sm border">
                        <small class="text-muted d-block">Tamamlanan Randevu</small>
                        <span class="fw-bold text-primary fs-6" id="c360-completed-count">0</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 bg-white rounded-3 shadow-sm border">
                        <small class="text-muted d-block">İptal / No-Show</small>
                        <span class="fw-bold text-danger fs-6" id="c360-noshow-count">0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline Section -->
        <div class="flex-grow-1 overflow-auto p-3">
            <h6 class="fw-bold small text-muted mb-3"><i class="fas fa-stream text-primary me-2"></i>Tüm İşlem & Etkileşim Geçmişi</h6>
            <div class="timeline" id="c360-timeline-container"></div>
        </div>
    </div>
</div>

<script>
function openCustomer360Drawer() {
    const customerId = document.getElementById('customer-id').value;
    if (!customerId) return;

    fetch('<?= site_url('customers/get_360?customer_id=') ?>' + customerId)
        .then(res => res.json())
        .then(data => {
            const c = data.customer;
            document.getElementById('c360-name').innerText = c.first_name + ' ' + (c.last_name || '');
            
            // Render tags
            const tagsBox = document.getElementById('c360-tags');
            tagsBox.innerHTML = (data.tags || []).map(t => `<span class="badge ${t.class} me-1">${t.label}</span>`).join('');

            // Metrics
            document.getElementById('c360-total-spent').innerText = data.metrics.total_spent.toFixed(2) + ' ₺';
            document.getElementById('c360-completed-count').innerText = data.metrics.completed_appointments;
            document.getElementById('c360-noshow-count').innerText = data.metrics.no_shows;

            // Render Timeline
            const container = document.getElementById('c360-timeline-container');
            container.innerHTML = '';
            if (!data.timeline || data.timeline.length === 0) {
                container.innerHTML = '<div class="text-muted text-center py-4">Henüz işlem geçmişi bulunmuyor.</div>';
            } else {
                data.timeline.forEach(t => {
                    const item = document.createElement('div');
                    item.className = 'd-flex gap-3 mb-3 pb-3 border-bottom';
                    item.innerHTML = `
                        <div class="rounded-circle bg-${t.badge} bg-opacity-10 text-${t.badge} p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; flex-shrink: 0;">
                            <i class="fas ${t.icon}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold small text-dark">${t.title}</span>
                                <small class="text-muted">${t.date}</small>
                            </div>
                            <small class="text-muted d-block">${t.subtitle}</small>
                            ${t.amount > 0 ? `<span class="badge bg-light text-success border mt-1">${parseFloat(t.amount).toFixed(2)} ₺</span>` : ''}
                        </div>
                    `;
                    container.appendChild(item);
                });
            }

            const drawerEl = document.getElementById('customer-360-drawer');
            const bsDrawer = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);
            bsDrawer.show();
        });
}

// Enable 360 button when a customer is selected
document.addEventListener('DOMContentLoaded', function() {
    const observer = new MutationObserver(function() {
        const cid = document.getElementById('customer-id')?.value;
        const btn = document.getElementById('btn-customer-360');
        if (btn) {
            if (cid && cid !== '') {
                btn.removeAttribute('disabled');
            } else {
                btn.setAttribute('disabled', 'disabled');
            }
        }
    });
    const cidInput = document.getElementById('customer-id');
    if (cidInput) {
        observer.observe(cidInput, { attributes: true, attributeFilter: ['value'] });
    }
});
</script>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/http/customers_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/customers.js') ?>"></script>

<?php end_section('scripts'); ?>
