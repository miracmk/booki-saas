<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="google-integrations-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div class="col-sm-9">
            <h4 class="border-bottom py-3 mb-3 fw-light">
                Google Entegrasyonları
            </h4>

            <div class="row mb-4">
                <div class="col-sm-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">Google Takvim</h5>
                        </div>
                        <div class="card-body">
                            <small>Terapistlerin randevularını kendi Google Takvim'leriyle senkronize eder.</small>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('google_calendar_settings') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i> <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="fw-light mb-0">Google Analytics</h5>
                        </div>
                        <div class="card-body">
                            <small><?= lang('google_analytics_info') ?></small>
                        </div>
                        <div class="card-footer border-0">
                            <a href="<?= site_url('google_analytics_settings') ?>" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cogs me-2"></i> <?= lang('configure') ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php $companyConnection = vars('company_connection'); ?>

            <?php if (vars('can_manage_company_connection')): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="fw-light mb-0">Şirket Hesabı</h5>
                    </div>
                    <div class="card-body">
                        <p class="form-text text-muted">
                            İşletmenin ortak Google hesabına (Drive/Sheets/Docs gibi paylaşılan servisler
                            için) bağlanın.
                        </p>

                        <?php if ($companyConnection): ?>
                            <p class="mb-2">
                                <i class="fas fa-check-circle text-success me-1"></i>
                                Bağlı: <strong><?= e($companyConnection['google_account_email'] ?: '-') ?></strong>
                                <br>
                                <small class="text-muted">
                                    Aktif servisler:
                                    <?php
                                    $labels = array_map(
                                        fn($key) => Google_integrations_client::SERVICES[$key]['label'] ?? $key,
                                        $companyConnection['enabled_services'],
                                    );
                                    echo e(implode(', ', $labels) ?: '-');
                                    ?>
                                </small>
                            </p>
                        <?php endif; ?>

                        <div class="mb-3">
                            <?php foreach (vars('company_services') as $key => $service): ?>
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" class="form-check-input google-service-checkbox"
                                           id="company-service-<?= e($key) ?>" data-owner-type="company"
                                           data-owner-id="0"
                                           value="<?= e($key) ?>"
                                           <?= in_array($key, $companyConnection['enabled_services'] ?? [], true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="company-service-<?= e($key) ?>">
                                        <?= e($service['label']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="btn btn-primary google-connect-btn"
                                data-owner-type="company" data-owner-id="0">
                            <?= $companyConnection ? 'Yeniden Bağlan / Servisleri Güncelle' : 'Bağlan' ?>
                        </button>
                        <?php if ($companyConnection): ?>
                            <button type="button" class="btn btn-outline-danger google-disconnect-btn"
                                    data-owner-type="company" data-owner-id="0">
                                Bağlantıyı Kes
                            </button>
                        <?php endif; ?>

                        <?php if ($companyConnection && in_array('drive', $companyConnection['enabled_services'], true)): ?>
                            <hr>
                            <h6 class="fw-light">Drive - Hedef Klasör</h6>
                            <?php if (!empty($companyConnection['drive_folder_id'])): ?>
                                <p class="mb-2">
                                    <i class="fas fa-folder text-warning me-1"></i>
                                    <strong><?= e($companyConnection['drive_folder_name']) ?></strong>
                                </p>
                            <?php else: ?>
                                <p class="text-muted mb-2">Henüz bir klasör seçilmedi.</p>
                            <?php endif; ?>
                            <div class="input-group mb-2" style="max-width: 500px;">
                                <input type="text" class="form-control google-target-input"
                                       id="drive-target-value" placeholder="Klasör linki veya ID'si">
                                <button type="button" class="btn btn-outline-primary google-target-btn"
                                        data-target="drive" data-mode="existing"
                                        data-owner-type="company" data-owner-id="0">
                                    Bağla
                                </button>
                                <button type="button" class="btn btn-outline-secondary google-target-btn"
                                        data-target="drive" data-mode="create"
                                        data-owner-type="company" data-owner-id="0">
                                    Yeni Klasör Oluştur
                                </button>
                            </div>
                        <?php endif; ?>

                        <?php if ($companyConnection && in_array('sheets', $companyConnection['enabled_services'], true)): ?>
                            <hr>
                            <h6 class="fw-light">Sheets - Hedef E-Tablo</h6>
                            <?php if (!empty($companyConnection['sheets_spreadsheet_id'])): ?>
                                <p class="mb-2">
                                    <i class="fas fa-table text-success me-1"></i>
                                    <strong><?= e($companyConnection['sheets_spreadsheet_name']) ?></strong>
                                </p>
                            <?php else: ?>
                                <p class="text-muted mb-2">Henüz bir e-tablo seçilmedi.</p>
                            <?php endif; ?>
                            <div class="input-group" style="max-width: 500px;">
                                <input type="text" class="form-control google-target-input"
                                       id="sheets-target-value" placeholder="E-Tablo linki veya ID'si">
                                <button type="button" class="btn btn-outline-primary google-target-btn"
                                        data-target="sheets" data-mode="existing"
                                        data-owner-type="company" data-owner-id="0">
                                    Bağla
                                </button>
                                <button type="button" class="btn btn-outline-secondary google-target-btn"
                                        data-target="sheets" data-mode="create"
                                        data-owner-type="company" data-owner-id="0">
                                    Yeni E-Tablo Oluştur
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="fw-light mb-0">Hizmet Sağlayıcı Hesapları</h5>
                </div>
                <div class="card-body">
                    <p class="form-text text-muted">
                        Her hizmet sağlayıcı kendi Google hesabını (Kişiler/Görevler için) bağlayabilir.
                    </p>

                    <?php foreach (vars('providers') as $provider): ?>
                        <?php $connection = $provider['connection']; ?>
                        <div class="border-bottom py-3">
                            <strong><?= e($provider['name']) ?></strong>
                            <?php if ($connection): ?>
                                <span class="text-success ms-2">
                                    <i class="fas fa-check-circle"></i>
                                    <?= e($connection['google_account_email'] ?: '-') ?>
                                    (<?php
                                    $labels = array_map(
                                        fn($key) => Google_integrations_client::SERVICES[$key]['label'] ?? $key,
                                        $connection['enabled_services'],
                                    );
                                    echo e(implode(', ', $labels));
                                    ?>)
                                </span>
                            <?php else: ?>
                                <span class="text-muted ms-2">Bağlı değil</span>
                            <?php endif; ?>

                            <div class="mt-2">
                                <?php foreach (vars('provider_services') as $key => $service): ?>
                                    <div class="form-check form-check-inline">
                                        <input type="checkbox" class="form-check-input google-service-checkbox"
                                               id="provider-<?= (int) $provider['id'] ?>-service-<?= e($key) ?>"
                                               data-owner-type="provider" data-owner-id="<?= (int) $provider['id'] ?>"
                                               value="<?= e($key) ?>"
                                               <?= in_array($key, $connection['enabled_services'] ?? [], true) ? 'checked' : '' ?>>
                                        <label class="form-check-label"
                                               for="provider-<?= (int) $provider['id'] ?>-service-<?= e($key) ?>">
                                            <?= e($service['label']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-primary google-connect-btn"
                                        data-owner-type="provider" data-owner-id="<?= (int) $provider['id'] ?>">
                                    <?= $connection ? 'Yeniden Bağlan / Servisleri Güncelle' : 'Bağlan' ?>
                                </button>
                                <?php if ($connection): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger google-disconnect-btn"
                                            data-owner-type="provider" data-owner-id="<?= (int) $provider['id'] ?>">
                                        Bağlantıyı Kes
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty(vars('providers'))): ?>
                        <p class="text-muted mb-0">Görüntülenecek hizmet sağlayıcı yok.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (vars('can_manage_company_connection')): ?>
                <div class="card mb-4" id="sheet-syncs-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="fw-light mb-0">Veri Senkronizasyonları (Sheets)</h5>
                        <button type="button" class="btn btn-sm btn-primary" id="new-sheet-sync-btn"
                                <?= empty($companyConnection) || !in_array('sheets', $companyConnection['enabled_services'] ?? [], true) || empty($companyConnection['sheets_spreadsheet_id']) ? 'disabled' : '' ?>>
                            <i class="fas fa-plus me-1"></i> Yeni Senkron Ekle
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="form-text text-muted">
                            Seanslar, Müşteriler ve Hizmet Sağlayanlar için canlı bir Google Sheets aynası
                            kurun - her değişiklik anında ilgili sayfaya işlenir. Bunu bir yedek/kurtarma
                            veritabanı gibi kullanabilirsiniz.
                        </p>

                        <?php if (empty($companyConnection) || !in_array('sheets', $companyConnection['enabled_services'] ?? [], true)): ?>
                            <p class="text-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Önce yukarıdan şirket hesabı için Sheets servisini bağlayın.
                            </p>
                        <?php elseif (empty($companyConnection['sheets_spreadsheet_id'])): ?>
                            <p class="text-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Önce yukarıdan bir hedef E-Tablo seçin veya oluşturun.
                            </p>
                        <?php else: ?>
                            <table class="table table-sm align-middle" id="sheet-syncs-table">
                                <thead>
                                <tr>
                                    <th>Ad</th>
                                    <th>Modül</th>
                                    <th>Sayfa</th>
                                    <th>Alan Sayısı</th>
                                    <th>KVKK/HIPAA</th>
                                    <th>Durum</th>
                                    <th>Son Senkron</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach (vars('sheet_syncs') as $sync): ?>
                                    <tr data-sync-id="<?= (int) $sync['id'] ?>">
                                        <td><?= e($sync['name']) ?></td>
                                        <td><?= e(vars('sheet_modules')[$sync['module']] ?? $sync['module']) ?></td>
                                        <td><?= e($sync['sheet_title']) ?></td>
                                        <td><?= count($sync['field_mappings']) ?></td>
                                        <td>
                                            <?php if ($sync['is_compliant']): ?>
                                                <span class="badge bg-success">Uyumlu</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger" title="PII düz metin olarak yazılıyor">Uyumlu Değil</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $sync['is_active'] ? 'bg-primary' : 'bg-secondary' ?> sync-active-toggle"
                                                  style="cursor: pointer;" data-active="<?= $sync['is_active'] ? '1' : '0' ?>">
                                                <?= $sync['is_active'] ? 'Aktif' : 'Durduruldu' ?>
                                            </span>
                                        </td>
                                        <td><small class="text-muted"><?= $sync['last_synced_at'] ? e($sync['last_synced_at']) : '-' ?></small></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary sync-now-btn">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-sync-btn">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>

                            <?php if (empty(vars('sheet_syncs'))): ?>
                                <p class="text-muted mb-0">Henüz bir senkronizasyon kurulmadı.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<div class="modal fade" id="sheet-sync-wizard-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-light">Yeni Sheets Senkronu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Ad</label>
                    <input type="text" class="form-control" id="wizard-name" placeholder="Örn. Seanslar - Ana Sayfa">
                </div>
                <div class="mb-3">
                    <label class="form-label">Modül</label>
                    <select class="form-select" id="wizard-module">
                        <?php foreach (vars('sheet_modules') as $key => $label): ?>
                            <option value="<?= e($key) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Sayfa (Tab)</label>
                    <select class="form-select" id="wizard-sheet-title"></select>
                    <small class="form-text text-muted">Bağlı e-tablonuzdaki sayfalar (tablar) listelenir.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Başlık Satırı</label>
                    <input type="number" class="form-control" id="wizard-header-row" value="1" min="1" style="max-width: 120px;">
                    <small class="form-text text-muted" id="wizard-header-hint"></small>
                </div>
                <hr>
                <h6 class="fw-light">Sütun Eşleme - hangi bilgi hangi sütuna gidecek</h6>
                <p class="form-text text-muted">
                    Seçtiğiniz alanlar, seçim sırasına göre A'dan başlayarak sütunlara yazılır ve başlık
                    satırına otomatik etiket eklenir. Sayfada zaten bir başlık satırı varsa, tanınan
                    alanlar otomatik işaretlenir.
                </p>
                <div class="mb-2">
                    <button type="button" class="btn btn-sm btn-link p-0" id="wizard-select-all">Tümünü Seç</button>
                    <button type="button" class="btn btn-sm btn-link p-0 ms-2" id="wizard-select-none">Hiçbirini Seçme</button>
                </div>
                <div id="wizard-field-list" class="row" style="max-height: 260px; overflow-y: auto;"></div>
                <hr>
                <div class="mb-3">
                    <label class="form-label">
                        KVKK/HIPAA Kişisel Veri (PII) Politikası
                    </label>
                    <select class="form-select" id="wizard-pii-mode">
                        <option value="exclude">Hariç Tut (önerilen - tam uyumlu)</option>
                        <option value="encrypted">Şifreli Yaz (uyumlu, sadece uygulama ile okunabilir)</option>
                        <option value="plaintext">Düz Metin Yaz (UYUMLU DEĞİL - KVKK/HIPAA ihlali riski)</option>
                    </select>
                    <small class="form-text" id="wizard-pii-warning"></small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Yazma Modu</label>
                    <select class="form-select" id="wizard-write-mode">
                        <option value="upsert">Güncelle (aynı kayıt aynı satırda kalır - önerilen)</option>
                        <option value="append">Sadece Ekle (her değişiklik yeni satır)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="wizard-save-btn">Kaydet ve Senkronize Et</button>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script>
    window.ki_sheet_field_catalogs = <?= json_encode(vars('sheet_field_catalogs'), JSON_UNESCAPED_UNICODE) ?>;
    window.ki_sheets_spreadsheet_id = <?= json_encode($companyConnection['sheets_spreadsheet_id'] ?? null) ?>;
</script>
<script src="<?= asset_url('assets/js/pages/google_integrations.js') ?>"></script>

<?php end_section('scripts'); ?>
