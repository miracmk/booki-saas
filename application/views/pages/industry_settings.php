<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="industry-settings-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div class="col-sm-9">
            <div class="d-flex justify-content-between align-items-center border-bottom mb-4 py-2">
                <div>
                    <h4 class="mb-0 fw-light">
                        <i class="fas fa-shapes text-primary me-2"></i>Sektör & Modül Yapılandırması
                    </h4>
                    <small class="text-muted">İşletmenizin sektörünü belirleyin; alan adları, bağlı modüller ve yönetim dashboard'u anında sektörünüze özelleşsin.</small>
                </div>

                <?php if (can('edit', PRIV_SYSTEM_SETTINGS)): ?>
                    <button type="button" id="btn-save-industry" class="btn btn-primary shadow-sm px-4">
                        <i class="fas fa-check-circle me-2"></i>Sektörü Uygula & Kaydet
                    </button>
                <?php endif; ?>
            </div>

            <div class="alert alert-info d-flex align-items-center mb-4 border-0 shadow-sm" role="alert">
                <i class="fas fa-info-circle fa-2x me-3 text-info"></i>
                <div class="small">
                    <strong>Bilgi:</strong> Sektör değişikliği yaptığınızda mevcut randevularınız ve müşteri kayıtlarınız <strong>kesinlikle silinmez</strong>. Sistemdeki terminoloji (Örn. Müşteri yerine Hasta veya Misafir), sol menüdeki modüller ve ana Dashboard görünümü anında seçilen sektöre göre biçimlenir.
                </div>
            </div>

            <!-- SECTOR SELECTOR GRID -->
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-light py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-th-large me-2 text-primary"></i>1. Sektörünüzü Seçin</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3" id="blueprint-cards-container">
                        <?php foreach ($all_blueprints as $bp): ?>
                            <?php 
                                $is_active = ($bp['code'] === $active_industry); 
                            ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 industry-card p-3 border <?= $is_active ? 'border-primary bg-primary bg-opacity-10 shadow-sm' : 'border-light-subtle' ?>" 
                                     data-code="<?= e($bp['code']) ?>"
                                     style="cursor: pointer; transition: all 0.2s ease-in-out;">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="fs-2"><?= $bp['icon'] ?></span>
                                        <?php if ($is_active): ?>
                                            <span class="badge bg-primary px-2 py-1 active-indicator"><i class="fas fa-check me-1"></i>Şu An Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border px-2 py-1 select-badge">Seç</span>
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="fw-bold mb-1 text-truncate"><?= e($bp['name']) ?></h6>
                                    <p class="small text-muted mb-2 line-clamp-2" style="font-size: 12px; min-height: 36px;">
                                        <?= e($bp['description']) ?>
                                    </p>
                                    <div class="d-flex flex-wrap gap-1 mt-auto">
                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 10px;">
                                            <?= (int) $bp['service_count'] ?> Hizmet
                                        </span>
                                        <span class="badge bg-info-subtle text-info" style="font-size: 10px;">
                                            <?= (int) $bp['station_count'] ?> İstasyon
                                        </span>
                                        <span class="badge bg-dark-subtle text-dark" style="font-size: 10px;">
                                            <?= count($bp['enabled_modules']) ?> Modül
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="selected_industry_code" name="industry_code" value="<?= e($active_industry) ?>">
                </div>
            </div>

            <!-- SECTOR TERMINOLOGY CUSTOMIZATION -->
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-spell-check me-2 text-primary"></i>2. Sektörel Alan İsimleri (Terminoloji)</h6>
                    <small class="text-muted">Seçilen sektöre göre otomatik dolar, dilerseniz kendinize göre özelleştirebilirsiniz.</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" for="term_customer">
                                <i class="fas fa-user text-primary me-1"></i>Müşteri Yerine Kullanılacak:
                            </label>
                            <input type="text" class="form-control" id="term_customer" name="terminology[customer_label]" 
                                   value="<?= e($current_terminology['customer_label'] ?? 'Müşteri') ?>" placeholder="Örn: Hasta, Misafir, Üye">
                            <div class="form-text" style="font-size: 11px;">Müşteri listesi, arama ve formlarda görünür.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" for="term_provider">
                                <i class="fas fa-user-tie text-success me-1"></i>Personel / Uzman Yerine:
                            </label>
                            <input type="text" class="form-control" id="term_provider" name="terminology[provider_label]" 
                                   value="<?= e($current_terminology['provider_label'] ?? 'Personel / Uzman') ?>" placeholder="Örn: Hekim, Garson, Antrenör">
                            <div class="form-text" style="font-size: 11px;">Takvim sütunlarında ve personel sayfalarında görünür.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" for="term_service">
                                <i class="fas fa-concierge-bell text-warning me-1"></i>Hizmet Yerine Kullanılacak:
                            </label>
                            <input type="text" class="form-control" id="term_service" name="terminology[service_label]" 
                                   value="<?= e($current_terminology['service_label'] ?? 'Hizmet') ?>" placeholder="Örn: Tedavi, Menü, Ders, Bakım">
                            <div class="form-text" style="font-size: 11px;">Hizmet listesi ve randevu detaylarında yer alır.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="term_station">
                                <i class="fas fa-door-open text-info me-1"></i>İstasyon / Oda Yerine Kullanılacak:
                            </label>
                            <input type="text" class="form-control" id="term_station" name="terminology[station_label]" 
                                   value="<?= e($current_terminology['station_label'] ?? 'İstasyon / Oda') ?>" placeholder="Örn: Diş Üniti, Masa, Stüdyo, Peron, Koltuk">
                            <div class="form-text" style="font-size: 11px;">Fiziksel alan, masa veya ünit isimlerini temsil eder.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="term_appointment">
                                <i class="fas fa-calendar-check text-danger me-1"></i>Randevu Yerine Kullanılacak:
                            </label>
                            <input type="text" class="form-control" id="term_appointment" name="terminology[appointment_label]" 
                                   value="<?= e($current_terminology['appointment_label'] ?? 'Randevu') ?>" placeholder="Örn: Muayene, Masa Rezervasyonu, Seans">
                            <div class="form-text" style="font-size: 11px;">Takvim etkinlikleri ve bildirimlerde kullanılır.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CONNECTED MODULES (FEATURE TOGGLES) -->
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-puzzle-piece me-2 text-primary"></i>3. Bağlı Modüller & Menü Görünürlüğü</h6>
                    <small class="text-muted">Açık olan modüller sol menüde ve hızlı işlemlerde aktif olur.</small>
                </div>
                <div class="card-body">
                    <div class="row g-3" id="modules-toggles-container">
                        <?php foreach ($available_modules as $mod_key => $mod_data): ?>
                            <?php 
                                $is_checked = !empty($current_features[$mod_key]);
                            ?>
                            <div class="col-md-6">
                                <div class="p-2 border rounded-3 d-flex align-items-center justify-content-between bg-light-subtle h-100">
                                    <div class="d-flex align-items-center me-2">
                                        <div class="p-2 rounded-2 bg-white text-primary shadow-xs me-3 text-center" style="width: 38px; height: 38px;">
                                            <i class="<?= e($mod_data['icon']) ?>"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold small"><?= e($mod_data['name']) ?></div>
                                            <div class="text-muted" style="font-size: 11px;"><?= e($mod_data['desc']) ?></div>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input module-checkbox" type="checkbox" role="switch" 
                                               id="mod_<?= e($mod_key) ?>" name="modules[<?= e($mod_key) ?>]" value="1"
                                               <?= $is_checked ? 'checked' : '' ?>
                                               <?= $mod_data['core'] ? 'disabled checked' : '' ?>>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- OPTIONAL TEMPLATE IMPORT -->
            <div class="card mb-4 shadow-sm border-0 border-start border-warning border-4">
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="import_templates" name="import_templates" value="1">
                        <label class="form-check-label fw-semibold" for="import_templates">
                            Seçilen sektörün örnek kategorilerini ve hizmet paketlerini de hesabıma ekle
                        </label>
                        <div class="text-muted small">
                            İşaretlerseniz, seçilen sektörün hazır hizmet ve kategori listesi mevcut hizmetlerinize eklenecektir (Mevcut hizmetleriniz silinmez).
                        </div>
                    </div>
                </div>
            </div>

            <!-- SAVE BUTTON BOTTOM -->
            <?php if (can('edit', PRIV_SYSTEM_SETTINGS)): ?>
                <div class="text-end mb-5">
                    <button type="button" id="btn-save-industry-bottom" class="btn btn-primary btn-lg shadow px-5">
                        <i class="fas fa-save me-2"></i>Sektör Ayarlarını Kaydet & Uygula
                    </button>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<style>
    .industry-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .shadow-xs {
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
</style>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script>
$(function() {
    var csrfToken = '<?= $this->security->get_csrf_hash() ?>';

    // Industry card selection handler
    $('.industry-card').on('click', function() {
        var card = $(this);
        var code = card.data('code');

        $('.industry-card').removeClass('border-primary bg-primary bg-opacity-10 shadow-sm').addClass('border-light-subtle');
        $('.industry-card .active-indicator').replaceWith('<span class="badge bg-light text-secondary border px-2 py-1 select-badge">Seç</span>');

        card.removeClass('border-light-subtle').addClass('border-primary bg-primary bg-opacity-10 shadow-sm');
        card.find('.select-badge').replaceWith('<span class="badge bg-primary px-2 py-1 active-indicator"><i class="fas fa-check me-1"></i>Seçildi</span>');

        $('#selected_industry_code').val(code);

        // Fetch blueprint defaults for live preview
        $.get('<?= site_url("industry_settings/get_blueprint_defaults") ?>', { code: code }, function(res) {
            if (res && res.success) {
                // Update terminology fields
                if (res.terminology) {
                    if (res.terminology.customer_label) $('#term_customer').val(res.terminology.customer_label);
                    if (res.terminology.provider_label) $('#term_provider').val(res.terminology.provider_label);
                    if (res.terminology.service_label) $('#term_service').val(res.terminology.service_label);
                    if (res.terminology.station_label) $('#term_station').val(res.terminology.station_label);
                    if (res.terminology.appointment_label) $('#term_appointment').val(res.terminology.appointment_label);
                }

                // Update module checkboxes
                if (res.enabled_modules && Array.isArray(res.enabled_modules)) {
                    $('.module-checkbox:not(:disabled)').prop('checked', false);
                    res.enabled_modules.forEach(function(mod) {
                        $('#mod_' + mod).prop('checked', true);
                    });
                }
            }
        });
    });

    // Save action handler
    function saveIndustrySettings() {
        var btn = $('#btn-save-industry, #btn-save-industry-bottom');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Kaydediliyor...');

        var postData = {
            industry_code: $('#selected_industry_code').val(),
            terminology: {
                customer_label: $('#term_customer').val(),
                provider_label: $('#term_provider').val(),
                service_label: $('#term_service').val(),
                station_label: $('#term_station').val(),
                appointment_label: $('#term_appointment').val()
            },
            modules: {},
            import_templates: $('#import_templates').is(':checked') ? 1 : 0,
            csrf_token: csrfToken
        };

        $('.module-checkbox').each(function() {
            var modKey = $(this).attr('id').replace('mod_', '');
            if ($(this).is(':checked')) {
                postData.modules[modKey] = 1;
            }
        });

        $.ajax({
            url: '<?= site_url("industry_settings/save") ?>',
            type: 'POST',
            data: postData,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    if (window.App && window.App.Utils && window.App.Utils.Toast) {
                        App.Utils.Toast.show(res.message, 'success');
                    } else {
                        alert(res.message);
                    }
                    setTimeout(function() {
                        window.location.href = '<?= site_url("dashboard") ?>';
                    }, 1000);
                } else {
                    alert(res.message || 'Bir hata oluştu.');
                    btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Sektör Ayarlarını Kaydet & Uygula');
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Kayıt sırasında bir hata oluştu.';
                alert(msg);
                btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Sektör Ayarlarını Kaydet & Uygula');
            }
        });
    }

    $('#btn-save-industry, #btn-save-industry-bottom').on('click', saveIndustrySettings);
});
</script>
<?php end_section('scripts'); ?>
