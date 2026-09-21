<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="general-settings-page" class="container backend-page py-3">
    <div id="general-settings">
        <div class="row">
            <div class="col-sm-3">
                <?php component('settings_nav'); ?>
            </div>
            <div class="col-sm-9">
                <form>
                    <fieldset>
                        <div class="d-flex justify-content-between align-items-center border-bottom mb-4 py-2">
                            <h4 class="mb-0 fw-light">
                                <?= lang('general_settings') ?>
                            </h4>

                            <?php if (can('edit', PRIV_SYSTEM_SETTINGS)): ?>
                                <button type="button" id="save-settings" class="btn btn-primary">
                                    <i class="fas fa-check-square me-2"></i>
                                    <?= lang('save') ?>
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Sektörel Blueprint Bilgi & Ayar Kartı -->
                        <?php $ind_info = current_industry_info(); ?>
                        <div class="card mb-4 border-0 shadow-sm bg-light-subtle">
                            <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex align-items-center">
                                    <div class="fs-1 me-3 p-2 bg-white rounded-3 shadow-xs text-center" style="width: 54px; height: 54px; line-height: 38px;">
                                        <?= $ind_info['icon'] ?>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <h6 class="mb-0 fw-bold"><?= e($ind_info['name']) ?></h6>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 11px;">Aktif Sektör</span>
                                        </div>
                                        <small class="text-muted d-block"><?= e($ind_info['description']) ?></small>
                                    </div>
                                </div>
                                <div>
                                    <a href="<?= site_url('industry_settings') ?>" class="btn btn-outline-primary btn-sm px-3 shadow-xs">
                                        <i class="fas fa-shapes me-1"></i> Sektör & Modülleri Değiştir →
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-5">
                            <div class="col-12">
                                <h5 class="mb-3 fw-light"><?= lang('company') ?></h5>

                                <div class="mb-3">
                                    <label class="form-label" for="company-name">
                                        <?= lang('company_name') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input id="company-name" data-field="company_name" class="required form-control">
                                    <div class="form-text text-muted">
                                        <small>
                                            <?= lang('company_name_hint') ?>
                                        </small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="company-email">
                                        <?= lang('company_email') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input id="company-email" data-field="company_email" class="required form-control">
                                    <div class="form-text text-muted">
                                        <small>
                                            <?= lang('company_email_hint') ?>
                                        </small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="company-link">
                                        <?= lang('company_link') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input id="company-link" data-field="company_link" class="required form-control">
                                    <div class="form-text text-muted">
                                        <small>
                                            <?= lang('company_link_hint') ?>
                                        </small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="company-logo">
                                        <?= lang('company_logo') ?>
                                    </label>
                                    <input type="file" id="company-logo" data-field="company_logo" class="form-control"
                                           accept="image/*">
                                    <div class="form-text text-muted">
                                        <small>
                                            <?= lang('company_logo_hint') ?>
                                            <?php if (!plan_allows('white_label')): ?>
                                                <br><span class="text-warning"><i class="fas fa-info-circle me-1"></i>Özel logonuzun randevu ve giriş ekranlarında görünmesi için <strong>White-Label (Elite Plan)</strong> gereklidir.</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>

                                    <div class="d-flex justify-content-center">
                                        <img src="#" alt="Company Logo Preview" id="company-logo-preview"
                                             class="img-thumbnail my-3" hidden>
                                    </div>

                                    <div class="d-flex justify-content-center">
                                        <button type="button" class="btn btn-danger btn-sm mb-3"
                                                id="remove-company-logo" hidden>
                                            <i class="fas fa-trash me-2"></i>
                                            <?= lang('remove') ?>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="company-color">
                                        <?= lang('company_color') ?>
                                    </label>

                                    <input type="color" id="company-color" data-field="company_color"
                                           class="form-control">

                                    <div class="form-text text-muted">
                                        <small>
                                            <?= lang('company_color_hint') ?>
                                        </small>
                                    </div>

                                    <div class="d-flex justify-content-center">
                                        <button type="button" class="btn btn-danger btn-sm my-3"
                                                id="reset-company-color" hidden>
                                            <i class="fas fa-undo-alt me-2"></i>
                                            <?= lang('reset') ?>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="theme">
                                        <?= lang('theme') ?>
                                    </label>

                                    <select id="theme" data-field="theme" class="form-select">
                                        <?php foreach (vars('available_themes') as $available_theme): ?>
                                            <option value="<?= $available_theme ?>">
                                                <?= ucfirst($available_theme) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <div class="form-text text-muted">
                                        <small>
                                            <?= lang('company_color_hint') ?>
                                        </small>
                                    </div>
                                </div>


                                <?php if (plan_allows("white_label")): ?>

                                <div class="mb-3 form-check">

                                    <input type="checkbox" id="white-label-enabled" data-field="white_label_enabled" class="form-check-input" value="1">

                                    <label class="form-check-label" for="white-label-enabled">

                                        White-Label Marka Gizleme Aktif

                                    </label>

                                    <div class="form-text text-muted">

                                        <small>Alt kısımdaki "Powered by BooKi" ve lisans yazılarını gizler.</small>

                                    </div>

                                </div>

                                <?php else: ?>

                                <div class="mb-3 form-check">

                                    <input type="checkbox" disabled class="form-check-input">

                                    <label class="form-check-label text-muted">

                                        White-Label Marka Gizleme <span class="badge bg-warning ms-1"><i class="fas fa-lock"></i> Elite Plan</span>

                                    </label>

                                    <div class="form-text text-muted">

                                        <small>Markamızı gizlemek için planınızı yükseltin.</small>

                                    </div>

                                </div>

                                <?php endif; ?>


                            </div>
                        </div>

                        <div class="row mb-5">
                            <div class="col-12">
                                <h5 class="mb-3 fw-light"><?= lang('localization') ?></h5>

                                <div class="mb-3">
                                    <label class="form-label" for="date-format">
                                        <?= lang('date_format') ?>
                                    </label>
                                    <select class="form-select" id="date-format" data-field="date_format">
                                        <option value="DMY">DMY</option>
                                        <option value="MDY">MDY</option>
                                        <option value="YMD">YMD</option>
                                    </select>
                                    <div class="form-text text-muted">
                                        <small>
                                            <?= lang('date_format_hint') ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="time-format">
                                        <?= lang('time_format') ?>
                                    </label>
                                    <select class="form-select" id="time-format" data-field="time_format">
                                        <option value="<?= TIME_FORMAT_REGULAR ?>">H:MM AM/PM</option>
                                        <option value="<?= TIME_FORMAT_MILITARY ?>">HH:MM</option>
                                    </select>
                                    <div class="form-text text-muted">
                                        <small>
                                            <?= lang('time_format_hint') ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="first-weekday">
                                        <?= lang('first_weekday') ?>
                                    </label>
                                    <select class="form-select" id="first-weekday" data-field="first_weekday">
                                        <option value="sunday"><?= lang('sunday') ?></option>
                                        <option value="monday"><?= lang('monday') ?></option>
                                        <option value="tuesday"><?= lang('tuesday') ?></option>
                                        <option value="wednesday"><?= lang('wednesday') ?></option>
                                        <option value="thursday"><?= lang('thursday') ?></option>
                                        <option value="friday"><?= lang('friday') ?></option>
                                        <option value="saturday"><?= lang('saturday') ?></option>
                                    </select>
                                    <div class="form-text text-muted">
                                        <small>
                                            <?= lang('first_weekday_hint') ?>
                                        </small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="default-language">
                                        <?= lang('default_language') ?>
                                        <span class="text-danger" hidden>*</span>
                                    </label>
                                    <select id="default-language" class="form-select required"
                                            data-field="default_language">
                                        <?php foreach (vars('available_languages') as $available_language): ?>
                                            <option value="<?= $available_language ?>">
                                                <?= ucfirst($available_language) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text text-muted">
                                        <small>
                                            <?= lang('default_language_hint') ?>
                                        </small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="default-timezone">
                                        <?= lang('default_timezone') ?>
                                        <span class="text-danger" hidden>*</span>
                                    </label>
                                    <?php component('timezone_dropdown', [
                                        'attributes' =>
                                            'id="default-timezone" data-field="default_timezone" class="form-select required"',
                                        'grouped_timezones' => vars('grouped_timezones'),
                                    ]); ?>
                                </div>
                                <div class="form-text text-muted">
                                    <small>
                                        <?= lang('default_timezone_hint') ?>
                                    </small>
                                </div>

                            </div>
                        </div>

                        <!-- Marketplace & Keşif Profili (BooKi Marketplace) -->
                        <div class="row mb-5">
                            <div class="col-12">
                                <div class="card border-primary border-opacity-25 bg-light p-4 rounded-3">
                                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                        <h5 class="mb-0 fw-bold text-dark">
                                            <i class="fas fa-store text-primary me-2"></i>BooKi Pazar Yeri & Keşif Profili
                                        </h5>
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="<?= site_url('marketplace') ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt me-1"></i>Pazar Yerinde Görüntüle</a>
                                            <span class="badge bg-primary">SEO & GEO Entegre</span>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-4">
                                        İşletmenizin BooKi Pazar Yeri'nde (Marketplace) listelenmesini sağlayarak Google ve Yapay Zeka (Gemini, ChatGPT, Perplexity) aramalarında yeni müşteriler kazanın.
                                    </p>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="marketplace-opt-in" data-field="marketplace_opt_in">
                                        <label class="form-check-label fw-semibold" for="marketplace-opt-in">
                                            İşletmemi BooKi Pazar Yerinde Yayınla & Keşfe Aç
                                        </label>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="marketplace-category">Pazar Yeri Kategorisi</label>
                                            <select id="marketplace-category" class="form-select" data-field="marketplace_category">
                                                <option value="">Seçiniz</option>
                                                <option value="Kuaför & Saç">Kuaför & Saç Tasarım</option>
                                                <option value="Güzellik & Bakım">Güzellik Salonu & Cilt Bakımı</option>
                                                <option value="Spa & Masaj">Spa, Hamam & Masaj</option>
                                                <option value="Klinik & Sağlık">Klinik, Diş & Sağlık</option>
                                                <option value="Tırnak & Estetik">Tırnak & Kalıcı Makyaj</option>
                                                <option value="Fitness & Spor">Fitness, Pilates & PT</option>
                                                <option value="Diyetisyen">Diyetisyen & Beslenme</option>
                                                <option value="Pet Kuaför">Pet Kuaför & Bakım</option>
                                                <option value="Dövme & Piercing">Dövme & Piercing</option>
                                                <option value="Diğer Hizmetler">Diğer Hizmetler</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label" for="marketplace-price-range">Fiyat Segmenti</label>
                                            <select id="marketplace-price-range" class="form-select" data-field="marketplace_price_range">
                                                <option value="₺">₺ (Ekonomik / Uygun)</option>
                                                <option value="₺₺" selected>₺₺ (Standart / Ortalama)</option>
                                                <option value="₺₺₺">₺₺₺ (Premium / Seçkin)</option>
                                                <option value="₺₺₺₺">₺₺₺₺ (Lüks / VIP)</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label" for="marketplace-city">Şehir</label>
                                            <input type="text" id="marketplace-city" class="form-control" data-field="marketplace_city" placeholder="Örn: İstanbul, Ankara, İzmir">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label" for="marketplace-district">İlçe / Bölge</label>
                                            <input type="text" id="marketplace-district" class="form-control" data-field="marketplace_district" placeholder="Örn: Kadıköy, Beşiktaş, Çankaya">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label" for="marketplace-cover-image">Kapak Görseli URL'si</label>
                                            <input type="url" id="marketplace-cover-image" class="form-control" data-field="marketplace_cover_image_url" placeholder="https://example.com/salon-foto.jpg">
                                            <small class="text-muted">Pazar yeri kartlarında ve işletme profilinde görünecek yüksek kaliteli fotoğraf bağlantısı.</small>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label" for="marketplace-short-desc">Kısa Tanıtım Yazısı (SEO & AI İçin)</label>
                                            <textarea id="marketplace-short-desc" class="form-control" data-field="marketplace_short_description" rows="3" placeholder="İşletmenizin sunduğu özel deneyimi ve uzmanlık alanlarını kısaca açıklayın..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </fieldset>
                </form>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/http/general_settings_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/general_settings.js') ?>"></script>

<?php end_section('scripts'); ?>
