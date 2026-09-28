<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>
<style>
    .theme-preset-card {
        cursor: pointer;
        border: 2px solid transparent;
        border-radius: 12px;
        transition: all 0.25s ease;
        position: relative;
        overflow: hidden;
    }
    .theme-preset-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }
    .theme-preset-card.active {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
    }
    .theme-swatch {
        height: 48px;
        border-radius: 8px 8px 0 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .phone-mockup-frame {
        width: 320px;
        height: 600px;
        border: 10px solid #1e293b;
        border-radius: 36px;
        background: #0f172a;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        margin: 0 auto;
    }
    .phone-notch {
        width: 120px;
        height: 18px;
        background: #1e293b;
        border-radius: 0 0 12px 12px;
        margin: 0 auto;
    }
    .phone-screen {
        flex: 1;
        overflow-y: auto;
        padding: 14px;
        color: #fff;
        font-family: inherit;
    }
    .badge-station {
        font-size: 0.75rem;
        padding: 4px 8px;
        border-radius: 6px;
    }
</style>

<div class="container-fluid backend-page px-4 py-4 flex-grow-1">
        <!-- Top Title & Actions Bar -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?= site_url('restaurant') ?>">Restoran</a></li>
                        <li class="breadcrumb-item active" aria-current="page">QR Menü & Tasarım Stüdyosu</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-dark mb-0">
                    <i class="fas fa-qrcode text-warning me-2"></i>QR Menü & Marka Tasarım Stüdyosu
                </h1>
                <p class="text-muted small mb-0">
                    Müşterilerin masadan eriştiği dijital menünün tasarımını, renklerini, self-order kurallarını ve ürün vitrinini canlı yönetin.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= site_url('restaurant/menu?table=' . (!empty($tables[0]['table_number']) ? $tables[0]['table_number'] : '1')) ?>" target="_blank" class="btn btn-outline-secondary">
                    <i class="fas fa-external-link-alt me-1"></i>Canlı Menüyü Aç
                </a>
                <a href="<?= site_url('restaurant/print_qr_stands') ?>" target="_blank" class="btn btn-outline-primary">
                    <i class="fas fa-print me-1"></i>Masa Standlarını Yazdır
                </a>
                <button type="button" id="btn-save-qr-settings" class="btn btn-primary px-4 shadow-sm">
                    <i class="fas fa-save me-1"></i>Ayarları Kaydet
                </button>
            </div>
        </div>

        <!-- Main Content with Tabs -->
        <div class="row g-4">
            <!-- Left Side: Customization Forms -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-3 px-4">
                        <ul class="nav nav-pills card-header-pills" id="qrStudioTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-semibold" id="tab-theme-btn" data-bs-toggle="pill" data-bs-target="#tab-theme" type="button" role="tab">
                                    <i class="fas fa-paint-brush me-1 text-warning"></i>Marka & Tema Tasarımı
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold" id="tab-order-btn" data-bs-toggle="pill" data-bs-target="#tab-order" type="button" role="tab">
                                    <i class="fas fa-sliders-h me-1 text-primary"></i>Sipariş & Süre Kuralları
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold" id="tab-items-btn" data-bs-toggle="pill" data-bs-target="#tab-items" type="button" role="tab">
                                    <i class="fas fa-utensils me-1 text-success"></i>Ürün Görünürlüğü & İstasyonlar
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-4">
                        <form id="qr-settings-form">
                            <input type="hidden" name="csrf_token" value="<?= e(vars('csrf_token')) ?>">

                            <div class="tab-content" id="qrStudioTabsContent">
                                <!-- TAB 1: TEMA VE MARKA TASARIMI -->
                                <div class="tab-pane fade show active" id="tab-theme" role="tabpanel">
                                    <h5 class="fw-bold mb-3 text-dark">Hazır Tema Şablonu Seçin</h5>
                                    <div class="row g-3 mb-4">
                                        <!-- Preset 1 -->
                                        <div class="col-md-3 col-6">
                                            <div class="card theme-preset-card <?= ($qr_settings['theme_preset'] === 'dark_gold') ? 'active' : '' ?>" data-preset="dark_gold" data-primary="#D97706" data-accent="#F59E0B" data-bg="dark">
                                                <div class="theme-swatch" style="background: linear-gradient(135deg, #111827 50%, #D97706 100%); color: #F59E0B;">
                                                    <i class="fas fa-crown"></i>
                                                </div>
                                                <div class="p-2 text-center bg-white">
                                                    <span class="fw-bold d-block small">Lüks Dark Gold</span>
                                                    <span class="text-muted" style="font-size: 0.7rem;">Gece / Fine Dining</span>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Preset 2 -->
                                        <div class="col-md-3 col-6">
                                            <div class="card theme-preset-card <?= ($qr_settings['theme_preset'] === 'warm_terracotta') ? 'active' : '' ?>" data-preset="warm_terracotta" data-primary="#EA580C" data-accent="#FB923C" data-bg="warm">
                                                <div class="theme-swatch" style="background: linear-gradient(135deg, #451A03 50%, #EA580C 100%); color: #FDBA74;">
                                                    <i class="fas fa-coffee"></i>
                                                </div>
                                                <div class="p-2 text-center bg-white">
                                                    <span class="fw-bold d-block small">Bistro Terracotta</span>
                                                    <span class="text-muted" style="font-size: 0.7rem;">Kafe & Ahşap Bistro</span>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Preset 3 -->
                                        <div class="col-md-3 col-6">
                                            <div class="card theme-preset-card <?= ($qr_settings['theme_preset'] === 'pure_white') ? 'active' : '' ?>" data-preset="pure_white" data-primary="#059669" data-accent="#10B981" data-bg="light">
                                                <div class="theme-swatch" style="background: linear-gradient(135deg, #F8FAFC 50%, #059669 100%); color: #065F46;">
                                                    <i class="fas fa-sun"></i>
                                                </div>
                                                <div class="p-2 text-center bg-white">
                                                    <span class="fw-bold d-block small">Modern Minimalist</span>
                                                    <span class="text-muted" style="font-size: 0.7rem;">Aydınlık & Zümrüt</span>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Preset 4 -->
                                        <div class="col-md-3 col-6">
                                            <div class="card theme-preset-card <?= ($qr_settings['theme_preset'] === 'neon_night') ? 'active' : '' ?>" data-preset="neon_night" data-primary="#06B6D4" data-accent="#3B82F6" data-bg="neon">
                                                <div class="theme-swatch" style="background: linear-gradient(135deg, #0F172A 50%, #06B6D4 100%); color: #38BDF8;">
                                                    <i class="fas fa-cocktail"></i>
                                                </div>
                                                <div class="p-2 text-center bg-white">
                                                    <span class="fw-bold d-block small">Bar Neon Night</span>
                                                    <span class="text-muted" style="font-size: 0.7rem;">Kokteyl & Lounge Bar</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="theme_preset" id="input_theme_preset" value="<?= e($qr_settings['theme_preset']) ?>">

                                    <hr class="my-4">

                                    <h5 class="fw-bold mb-3 text-dark">Özel Renkler ve Tipografi</h5>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold small">Ana Marka Rengi</label>
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="primary_color_picker" value="<?= e($qr_settings['primary_color']) ?>">
                                                <input type="text" class="form-control" name="primary_color" id="primary_color_text" value="<?= e($qr_settings['primary_color']) ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold small">Vurgu & İkincil Renk</label>
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color" id="accent_color_picker" value="<?= e($qr_settings['accent_color']) ?>">
                                                <input type="text" class="form-control" name="accent_color" id="accent_color_text" value="<?= e($qr_settings['accent_color']) ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold small">Yazı Tipi (Font)</label>
                                            <select class="form-select" name="font_family" id="input_font_family">
                                                <option value="Poppins" <?= ($qr_settings['font_family'] === 'Poppins') ? 'selected' : '' ?>>Poppins (Modern & Dengeli)</option>
                                                <option value="Inter" <?= ($qr_settings['font_family'] === 'Inter') ? 'selected' : '' ?>>Inter (Clean Tech)</option>
                                                <option value="Playfair Display" <?= ($qr_settings['font_family'] === 'Playfair Display') ? 'selected' : '' ?>>Playfair Display (Gourmet Lüks Serif)</option>
                                                <option value="Montserrat" <?= ($qr_settings['font_family'] === 'Montserrat') ? 'selected' : '' ?>>Montserrat (Şık & Geometrik)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <h5 class="fw-bold mb-3 text-dark">Karşılama Metinleri & Banner Görseli</h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Karşılama Başlığı</label>
                                            <input type="text" class="form-control" name="hero_title" id="input_hero_title" value="<?= e($qr_settings['hero_title']) ?>" placeholder="Örn: Mavi Liman Restoran">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Hero / Kapak Görseli URL</label>
                                            <input type="url" class="form-control" name="hero_banner_url" id="input_hero_banner_url" value="<?= e($qr_settings['hero_banner_url'] ?? '') ?>" placeholder="https://.../restaurant_hero.jpg">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold small">Karşılama Alt Metni</label>
                                            <textarea class="form-control" name="hero_subtitle" id="input_hero_subtitle" rows="2" placeholder="Örn: Doğal taş fırından lezzetler ve imza kokteyller..."><?= e($qr_settings['hero_subtitle'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- TAB 2: SIPARIS VE SURE KURALLARI -->
                                <div class="tab-pane fade" id="tab-order" role="tabpanel">
                                    <h5 class="fw-bold mb-3 text-dark">Masadan Sipariş (Self-Order) ve Onay Akışı</h5>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <div class="form-check form-switch p-3 bg-light rounded-3">
                                                <input class="form-check-input ms-0 me-2" type="checkbox" name="allow_self_order" value="1" id="allow_self_order" <?= !empty($qr_settings['allow_self_order']) ? 'checked' : '' ?>>
                                                <label class="form-check-label fw-bold" for="allow_self_order">
                                                    Masadan Doğrudan Sipariş Açık
                                                </label>
                                                <div class="text-muted small">Müşteri QR menüyü okuttuğunda sepetine ürün ekleyip sipariş gönderebilir.</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Sipariş Yönlendirme Modu</label>
                                            <select class="form-select" name="order_approval_mode">
                                                <option value="direct" <?= ($qr_settings['order_approval_mode'] === 'direct') ? 'selected' : '' ?>>
                                                    🚀 Doğrudan KDS'ye (Mutfağa & Bara) İlet
                                                </option>
                                                <option value="waiter_approval" <?= ($qr_settings['order_approval_mode'] === 'waiter_approval') ? 'selected' : '' ?>>
                                                    🛡️ Önce Garson Ekranına Düşsün (Garson Onaylı)
                                                </option>
                                            </select>
                                            <div class="form-text small">Doğrudan modda sipariş verildiği an ilgili mutfak veya bar istasyonunun ekranına düşer.</div>
                                        </div>
                                    </div>

                                    <h5 class="fw-bold mb-3 text-dark">Masa Çağrı & Hızlı Hizmet Butonları</h5>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-4">
                                            <div class="form-check form-switch p-3 bg-light rounded-3">
                                                <input class="form-check-input ms-0 me-2" type="checkbox" name="enable_waiter_call" value="1" id="enable_waiter_call" <?= !empty($qr_settings['enable_waiter_call']) ? 'checked' : '' ?>>
                                                <label class="form-check-label fw-bold small" for="enable_waiter_call">
                                                    🔔 Garson / Su Çağır
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch p-3 bg-light rounded-3">
                                                <input class="form-check-input ms-0 me-2" type="checkbox" name="enable_bill_request" value="1" id="enable_bill_request" <?= !empty($qr_settings['enable_bill_request']) ? 'checked' : '' ?>>
                                                <label class="form-check-label fw-bold small" for="enable_bill_request">
                                                    🧾 Hesap / Adisyon İste
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch p-3 bg-light rounded-3">
                                                <input class="form-check-input ms-0 me-2" type="checkbox" name="enable_tipping" value="1" id="enable_tipping" <?= !empty($qr_settings['enable_tipping']) ? 'checked' : '' ?>>
                                                <label class="form-check-label fw-bold small" for="enable_tipping">
                                                    💝 Bahşiş (Tip) Seçeneği
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold small">Bahşiş Oranları (Yüzde)</label>
                                            <input type="text" class="form-control" name="tip_options" value="<?= e($qr_settings['tip_options']) ?>" placeholder="Örn: 5,10,15,20">
                                            <div class="form-text small">Virgülle ayırarak yüzdeleri belirtebilirsiniz.</div>
                                        </div>
                                    </div>

                                    <h5 class="fw-bold mb-3 text-dark">Geliş - Kalkış ve Oturum Süre Modeli (SambaPOS-3 Mantığı)</h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small">Mekan Oturum Süre Modu</label>
                                            <select class="form-select" name="venue_duration_mode" id="venue_duration_mode">
                                                <option value="open_ended" <?= ($qr_settings['venue_duration_mode'] === 'open_ended') ? 'selected' : '' ?>>
                                                    ☕ Süresiz / Açık Adisyon (Restoran, Kafe, Bar, Ören Yeri)
                                                </option>
                                                <option value="fixed_duration" <?= ($qr_settings['venue_duration_mode'] === 'fixed_duration') ? 'selected' : '' ?>>
                                                    ⏳ Süreli Seans (Hamam, VIP Loca, Private Spa, Bilardo)
                                                </option>
                                                <option value="daily_pass" <?= ($qr_settings['venue_duration_mode'] === 'daily_pass') ? 'selected' : '' ?>>
                                                    🎟️ Günlük Giriş / Günlük Pass (Genel Hamam, Plaj, Havuz)
                                                </option>
                                            </select>
                                            <div class="form-text small">
                                                Süresiz açık adisyonda müşteriye kalkış saati sorulmaz; geçen süre sayacı işler.
                                            </div>
                                        </div>
                                        <div class="col-md-6" id="session_duration_col" style="<?= ($qr_settings['venue_duration_mode'] === 'open_ended') ? 'opacity: 0.5;' : '' ?>">
                                            <label class="form-label fw-semibold small">Varsayılan Oturum Süresi (Dakika)</label>
                                            <input type="number" class="form-control" name="default_seat_duration_minutes" value="<?= (int) $qr_settings['default_seat_duration_minutes'] ?>" min="15" max="480">
                                            <div class="form-text small">Süreli seanslarda geriye sayım sayacı çalışır (örn: 90 dk).</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- TAB 3: URUN GOZTERIMI VE ISTASYONLAR -->
                                <div class="tab-pane fade" id="tab-items" role="tabpanel">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="fw-bold mb-0 text-dark">Ürün QR Görünürlüğü ve KDS İstasyon Eşleştirmesi</h5>
                                        <input type="text" id="filter-menu-items" class="form-control form-control-sm w-auto" placeholder="Ürün ara...">
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle" id="menu-items-table">
                                            <thead class="table-light small">
                                                <tr>
                                                    <th>Ürün</th>
                                                    <th>Kategori</th>
                                                    <th>Fiyat (₺)</th>
                                                    <th>KDS İstasyonu (Mutfak / Bar)</th>
                                                    <th>Rozet / Etiket</th>
                                                    <th class="text-center">QR'da Göster</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($menu_items as $item): ?>
                                                <tr data-item-id="<?= $item['id'] ?>" class="item-row">
                                                    <td>
                                                        <span class="fw-bold text-dark item-name"><?= e($item['name']) ?></span>
                                                    </td>
                                                    <td class="small text-muted"><?= e($item['category_name'] ?? 'Genel') ?></td>
                                                    <td style="width: 110px;">
                                                        <input type="number" step="0.5" class="form-control form-control-sm quick-price" value="<?= (float) $item['price'] ?>">
                                                    </td>
                                                    <td style="min-width: 220px;">
                                                        <select class="form-select form-select-sm quick-station">
                                                            <?php foreach ($stations as $scode => $sinfo): ?>
                                                            <option value="<?= $scode ?>" <?= ($item['station'] === $scode) ? 'selected' : '' ?>>
                                                                [<?= $sinfo['group'] ?>] <?= e($sinfo['name']) ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>
                                                    <td style="width: 170px;">
                                                        <select class="form-select form-select-sm quick-badge">
                                                            <option value="" <?= empty($item['badge_text']) ? 'selected' : '' ?>>- Rozet Yok -</option>
                                                            <option value="Şefin İmzası ⭐" <?= (($item['badge_text'] ?? '') === 'Şefin İmzası ⭐') ? 'selected' : '' ?>>Şefin İmzası ⭐</option>
                                                            <option value="En Çok Satan 🔥" <?= (($item['badge_text'] ?? '') === 'En Çok Satan 🔥') ? 'selected' : '' ?>>En Çok Satan 🔥</option>
                                                            <option value="Acılı 🌶️" <?= (($item['badge_text'] ?? '') === 'Acılı 🌶️') ? 'selected' : '' ?>>Acılı 🌶️</option>
                                                            <option value="Glutensiz 🌾" <?= (($item['badge_text'] ?? '') === 'Glutensiz 🌾') ? 'selected' : '' ?>>Glutensiz 🌾</option>
                                                            <option value="Vegan 🌱" <?= (($item['badge_text'] ?? '') === 'Vegan 🌱') ? 'selected' : '' ?>>Vegan 🌱</option>
                                                        </select>
                                                    </td>
                                                    <td class="text-center" style="width: 110px;">
                                                        <div class="form-check form-switch d-inline-block">
                                                            <input class="form-check-input quick-qr-visible" type="checkbox" value="1" <?= (!isset($item['is_qr_visible']) || $item['is_qr_visible'] == 1) ? 'checked' : '' ?>>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Side: Live Interactive Phone Mockup -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                    <h6 class="fw-bold text-dark mb-1">
                        <i class="fas fa-mobile-alt me-1 text-primary"></i>Canlı Mobil QR Menü Önizlemesi
                    </h6>
                    <p class="text-muted small mb-3">Tasarım değişiklikleri anında buraya yansır.</p>

                    <div class="phone-mockup-frame" id="mockup-frame">
                        <div class="phone-notch"></div>
                        <div class="phone-screen" id="mockup-screen">
                            <div class="p-3 text-center rounded-3 mb-3" id="mockup-hero" style="background: <?= e($qr_settings['primary_color']) ?>;">
                                <h6 class="fw-bold mb-1" id="mockup-title"><?= e($qr_settings['hero_title']) ?></h6>
                                <p class="small mb-0 opacity-75" id="mockup-subtitle"><?= e($qr_settings['hero_subtitle']) ?></p>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                <span class="badge bg-secondary">Masa 4</span>
                                <span class="small opacity-75" id="mockup-action-btn" style="color: <?= e($qr_settings['accent_color']) ?>;">
                                    <i class="fas fa-concierge-bell me-1"></i>Garson Çağır
                                </span>
                            </div>

                            <div class="text-start">
                                <div class="p-2 mb-2 rounded-3 bg-dark border border-secondary border-opacity-25">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold small">Bonfile Izgara</span>
                                        <span class="fw-bold small" style="color: <?= e($qr_settings['accent_color']) ?>;">450 ₺</span>
                                    </div>
                                    <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">Şefin İmzası ⭐</span>
                                    <div class="text-muted" style="font-size: 0.65rem;">Közlenmiş domates ve taze kekikli patates ile</div>
                                </div>
                                <div class="p-2 mb-2 rounded-3 bg-dark border border-secondary border-opacity-25">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold small">İmza Kokteyl Passion</span>
                                        <span class="fw-bold small" style="color: <?= e($qr_settings['accent_color']) ?>;">260 ₺</span>
                                    </div>
                                    <span class="badge bg-info text-dark" style="font-size: 0.65rem;">Bar Spesiyali</span>
                                    <div class="text-muted" style="font-size: 0.65rem;">Passion fruit püre, taze nane ve lime</div>
                                </div>
                                <div class="p-2 mb-2 rounded-3 bg-dark border border-secondary border-opacity-25">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold small">Fıstıklı Sıcak Sufle</span>
                                        <span class="fw-bold small" style="color: <?= e($qr_settings['accent_color']) ?>;">180 ₺</span>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.65rem;">Belçika çikolatası ve vanilyalı dondurma</div>
                                </div>
                            </div>

                            <div class="mt-auto pt-3">
                                <button type="button" class="btn btn-sm w-100 fw-bold" id="mockup-cart-btn" style="background: <?= e($qr_settings['accent_color']) ?>; color: #000;">
                                    <i class="fas fa-shopping-bag me-1"></i>Sepeti Görüntüle (2 Ürün)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;

        // Presets click handling
        document.querySelectorAll('.theme-preset-card').forEach(card => {
            card.addEventListener('click', function () {
                document.querySelectorAll('.theme-preset-card').forEach(c => c.classList.remove('active'));
                this.classList.add('active');

                const preset = this.dataset.preset;
                const primary = this.dataset.primary;
                const accent = this.dataset.accent;

                document.getElementById('input_theme_preset').value = preset;
                document.getElementById('primary_color_text').value = primary;
                document.getElementById('primary_color_picker').value = primary;
                document.getElementById('accent_color_text').value = accent;
                document.getElementById('accent_color_picker').value = accent;

                updateMockup();
            });
        });

        // Color sync
        document.getElementById('primary_color_picker').addEventListener('input', function() {
            document.getElementById('primary_color_text').value = this.value;
            updateMockup();
        });
        document.getElementById('accent_color_picker').addEventListener('input', function() {
            document.getElementById('accent_color_text').value = this.value;
            updateMockup();
        });
        document.getElementById('input_hero_title').addEventListener('input', updateMockup);
        document.getElementById('input_hero_subtitle').addEventListener('input', updateMockup);

        function updateMockup() {
            const primary = document.getElementById('primary_color_text').value;
            const accent = document.getElementById('accent_color_text').value;
            const title = document.getElementById('input_hero_title').value || 'Hoş Geldiniz';
            const subtitle = document.getElementById('input_hero_subtitle').value || '';

            document.getElementById('mockup-hero').style.background = primary;
            document.getElementById('mockup-title').textContent = title;
            document.getElementById('mockup-subtitle').textContent = subtitle;
            document.getElementById('mockup-cart-btn').style.background = accent;
            document.getElementById('mockup-action-btn').style.color = accent;
        }

        // Save QR Settings Form
        document.getElementById('btn-save-qr-settings').addEventListener('click', function () {
            const form = document.getElementById('qr-settings-form');
            const formData = new FormData(form);

            fetch('<?= site_url('restaurant/api_save_qr_settings') ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('QR Menü ayarları başarıyla kaydedildi!');
                } else {
                    alert('Hata: ' + (data.message || 'Kaydedilemedi.'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Ağ hatası oluştu.');
            });
        });

        // Quick update menu item fields (QR visible, station, badge, price)
        document.querySelectorAll('#menu-items-table tbody tr').forEach(row => {
            const itemId = row.dataset.itemId;
            const qrToggle = row.querySelector('.quick-qr-visible');
            const stationSelect = row.querySelector('.quick-station');
            const badgeSelect = row.querySelector('.quick-badge');
            const priceInput = row.querySelector('.quick-price');

            function triggerQuickUpdate() {
                const payload = {
                    id: itemId,
                    csrf_token: csrfToken,
                    is_qr_visible: qrToggle.checked ? 1 : 0,
                    station: stationSelect.value,
                    badge_text: badgeSelect.value,
                    price: parseFloat(priceInput.value) || 0
                };

                fetch('<?= site_url('restaurant/api_update_menu_item_quick') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        row.classList.add('table-success');
                        setTimeout(() => row.classList.remove('table-success'), 600);
                    }
                });
            }

            qrToggle.addEventListener('change', triggerQuickUpdate);
            stationSelect.addEventListener('change', triggerQuickUpdate);
            badgeSelect.addEventListener('change', triggerQuickUpdate);
            priceInput.addEventListener('change', triggerQuickUpdate);
        });

        // Filter menu items table
        document.getElementById('filter-menu-items').addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            document.querySelectorAll('#menu-items-table tbody tr').forEach(row => {
                const text = row.querySelector('.item-name').textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });

        // Duration mode toggle
        document.getElementById('venue_duration_mode').addEventListener('change', function () {
            const col = document.getElementById('session_duration_col');
            col.style.opacity = (this.value === 'open_ended') ? '0.5' : '1';
        });
    });
    </script>
</div>
<?php end_section('content'); ?>
