<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>
<?php
/**
 * @var array $patrons
 * @var array $tables
 * @var array $staff_members
 * @var string|null $current_tier
 * @var string|null $current_search
 */

// Calculate Summary KPIs
$total_patrons = count($patrons);
$vip_count = 0;
$total_visits_sum = 0;
$total_spend_sum = 0;

foreach ($patrons as $p) {
    if (in_array($p['patronage_tier'] ?? '', ['elite', 'vip_regular'])) {
        $vip_count++;
    }
    $total_visits_sum += (int) ($p['total_visits'] ?? 0);
    $total_spend_sum += (float) ($p['total_spend'] ?? 0);
}
$avg_ticket = $total_visits_sum > 0 ? ($total_spend_sum / $total_visits_sum) : 0;
?>

<div class="container-fluid py-3" id="restaurant-guest-patronage-page">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 bg-white p-3 rounded-3 shadow-sm border">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h4 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-crown text-warning me-2"></i>Masa Müdavimleri & 360° Misafir Zekası
                </h4>
                <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-3 py-1">SevenRooms & Resy Standardı</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Restoranınızın en değerli müdavimlerinin masa alışkanlıklarını, gastronomi tercihlerini, alerjenlerini ve sadakat verilerini tek merkezden yönetin.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="<?= site_url('restaurant') ?>" class="btn btn-outline-primary">
                <i class="fas fa-border-all me-1"></i> Masa Planı
            </a>
            <button class="btn btn-warning text-dark fw-bold" onclick="openNewPatronModal()">
                <i class="fas fa-user-plus me-1"></i> Yeni Müdavim Ekle
            </button>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Toplam Müdavim</div>
                        <h3 class="fw-bold mb-0 text-dark"><?= number_format($total_patrons) ?></h3>
                    </div>
                    <div class="bg-primary-subtle text-primary rounded-circle p-3">
                        <i class="fas fa-users fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-warning">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">VIP & Elite Müdavim</div>
                        <h3 class="fw-bold mb-0 text-warning"><?= number_format($vip_count) ?></h3>
                    </div>
                    <div class="bg-warning-subtle text-warning rounded-circle p-3">
                        <i class="fas fa-crown fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Toplam Müdavim Cirosu</div>
                        <h3 class="fw-bold mb-0 text-success">₺<?= number_format($total_spend_sum, 0, ',', '.') ?></h3>
                    </div>
                    <div class="bg-success-subtle text-success rounded-circle p-3">
                        <i class="fas fa-receipt fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-info">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Ort. Müdavim Hesabı</div>
                        <h3 class="fw-bold mb-0 text-info">₺<?= number_format($avg_ticket, 0, ',', '.') ?></h3>
                    </div>
                    <div class="bg-info-subtle text-info rounded-circle p-3">
                        <i class="fas fa-chart-line fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="get" action="<?= site_url('restaurant/guest_patronage') ?>" class="row g-3 align-items-center">
                <!-- Tier Buttons Filter -->
                <div class="col-12 col-lg-7">
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <a href="<?= site_url('restaurant/guest_patronage') ?>" class="btn <?= empty($current_tier) ? 'btn-dark' : 'btn-outline-secondary' ?>">
                            Tümü (<?= $total_patrons ?>)
                        </a>
                        <a href="<?= site_url('restaurant/guest_patronage?tier=elite') ?>" class="btn <?= $current_tier === 'elite' ? 'btn-dark' : 'btn-outline-secondary' ?>">
                            👑 Elite
                        </a>
                        <a href="<?= site_url('restaurant/guest_patronage?tier=vip_regular') ?>" class="btn <?= $current_tier === 'vip_regular' ? 'btn-dark' : 'btn-outline-secondary' ?>">
                            ⭐ VIP Regular
                        </a>
                        <a href="<?= site_url('restaurant/guest_patronage?tier=regular') ?>" class="btn <?= $current_tier === 'regular' ? 'btn-dark' : 'btn-outline-secondary' ?>">
                            🏅 Müdavim
                        </a>
                        <a href="<?= site_url('restaurant/guest_patronage?tier=first_timer') ?>" class="btn <?= $current_tier === 'first_timer' ? 'btn-dark' : 'btn-outline-secondary' ?>">
                            🌱 İlk Ziyaretçi
                        </a>
                    </div>
                </div>

                <!-- Search Input -->
                <div class="col-12 col-lg-5">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Misafir adı, telefon veya tercih ara..." value="<?= e($current_search ?? '') ?>">
                        <?php if (!empty($current_tier)): ?>
                            <input type="hidden" name="tier" value="<?= e($current_tier) ?>">
                        <?php endif; ?>
                        <button class="btn btn-sm btn-primary" type="submit">
                            <i class="fas fa-search me-1"></i> Filtrele
                        </button>
                        <?php if (!empty($current_search) || !empty($current_tier)): ?>
                            <a href="<?= site_url('restaurant/guest_patronage') ?>" class="btn btn-sm btn-outline-secondary" title="Sıfırla">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Guest Patron Cards Grid -->
    <div class="row g-3">
        <?php if (empty($patrons)): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3 py-5 text-center bg-white">
                    <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                    <h5 class="fw-bold text-dark">Kriterlere Uygun Müdavim Bulunamadı</h5>
                    <p class="text-muted small">Arama filtrenizi temizleyebilir veya yeni bir misafir profili ekleyebilirsiniz.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($patrons as $p): ?>
                <?php
                $tier = $p['patronage_tier'] ?? 'regular';
                $tier_badge = 'bg-primary';
                $tier_icon = 'fa-user-check';
                $tier_label = 'Müdavim';

                if ($tier === 'elite') {
                    $tier_badge = 'bg-dark text-warning border border-warning';
                    $tier_icon = 'fa-crown';
                    $tier_label = 'ELITE MÜDAVİM';
                } elseif ($tier === 'vip_regular') {
                    $tier_badge = 'bg-warning text-dark';
                    $tier_icon = 'fa-star';
                    $tier_label = 'VIP MÜDAVİM';
                } elseif ($tier === 'first_timer') {
                    $tier_badge = 'bg-info text-white';
                    $tier_icon = 'fa-seedling';
                    $tier_label = 'POTANSİYEL';
                }

                $dishes = !empty($p['favorite_dishes_json']) ? (is_array($p['favorite_dishes_json']) ? $p['favorite_dishes_json'] : json_decode($p['favorite_dishes_json'], true)) : [];
                $drinks = !empty($p['favorite_drinks_json']) ? (is_array($p['favorite_drinks_json']) ? $p['favorite_drinks_json'] : json_decode($p['favorite_drinks_json'], true)) : [];
                ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm rounded-3 h-100 bg-white patron-card position-relative overflow-hidden">
                        <!-- Top Accent Banner -->
                        <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle rounded-circle bg-light border d-flex align-items-center justify-content-center text-primary fw-bold" style="width: 44px; height: 44px; font-size: 16px;">
                                    <?= mb_strtoupper(mb_substr($p['customer_name'] ?? 'M', 0, 1)) ?>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark"><?= e($p['customer_name'] ?? 'Misafir') ?></h6>
                                    <small class="text-muted"><i class="fas fa-phone-alt me-1"></i><?= e($p['customer_phone'] ?? '-') ?></small>
                                </div>
                            </div>
                            <span class="badge <?= $tier_badge ?> rounded-pill px-2 py-1 text-uppercase fw-semibold" style="font-size: 10px;">
                                <i class="fas <?= $tier_icon ?> me-1"></i> <?= $tier_label ?>
                            </span>
                        </div>

                        <div class="card-body p-3">
                            <!-- Lifetime Statistics Bar -->
                            <div class="row g-2 text-center mb-3 bg-light p-2 rounded-3">
                                <div class="col-3 border-end">
                                    <div class="small text-muted" style="font-size: 10px;">Ziyaret</div>
                                    <div class="fw-bold text-dark"><?= $p['total_visits'] ?></div>
                                </div>
                                <div class="col-3 border-end">
                                    <div class="small text-muted" style="font-size: 10px;">Ciro</div>
                                    <div class="fw-bold text-success">₺<?= number_format($p['total_spend'], 0) ?></div>
                                </div>
                                <div class="col-3 border-end">
                                    <div class="small text-muted" style="font-size: 10px;">Ort. Hesap</div>
                                    <div class="fw-bold text-dark">₺<?= number_format($p['avg_spend'], 0) ?></div>
                                </div>
                                <div class="col-3">
                                    <div class="small text-muted" style="font-size: 10px;">Grup</div>
                                    <div class="fw-bold text-dark"><?= number_format($p['avg_party_size'], 1) ?> K.</div>
                                </div>
                            </div>

                            <!-- Favorite Table & Seating Preference -->
                            <div class="mb-2 p-2 bg-warning-subtle rounded-3 border border-warning-subtle">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small fw-bold text-dark">
                                        <i class="fas fa-chair text-warning me-1"></i>Favori Masa:
                                    </span>
                                    <span class="badge bg-warning text-dark fw-bold">
                                        <?= $p['favorite_table_number'] ? 'Masa ' . e($p['favorite_table_number']) : 'Esnek' ?>
                                    </span>
                                </div>
                                <div class="small text-muted mt-1" style="font-size: 11px;">
                                    Bölüm: <strong><?= e($p['favorite_section'] ?? 'Ana Salon') ?></strong> | Masa Koruması: <span class="text-success fw-semibold"><i class="fas fa-shield-alt"></i> Otomatik Koruma</span>
                                </div>
                            </div>

                            <!-- Gastronomy & Drinks -->
                            <?php if (!empty($dishes) || !empty($drinks)): ?>
                                <div class="mb-2">
                                    <small class="text-muted fw-bold d-block mb-1" style="font-size: 11px;">Damak Tadı & Favoriler:</small>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php if (is_array($dishes)): ?>
                                            <?php foreach ($dishes as $dish): ?>
                                                <span class="badge bg-light text-dark border" style="font-size: 10px;"><i class="fas fa-utensils text-muted me-1"></i><?= e($dish) ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <?php if (is_array($drinks)): ?>
                                            <?php foreach ($drinks as $drink): ?>
                                                <span class="badge bg-light text-primary border" style="font-size: 10px;"><i class="fas fa-glass-martini-alt text-muted me-1"></i><?= e($drink) ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Allergens & Dietary Warning -->
                            <?php if (!empty($p['allergens']) || !empty($p['dietary_habits'])): ?>
                                <div class="mb-2 p-2 bg-danger-subtle rounded border border-danger-subtle small text-danger" style="font-size: 11px;">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <strong>Diyet & Alerjen:</strong> <?= e(trim($p['dietary_habits'] . ' ' . $p['allergens'])) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Welcome Treat & Service Notes -->
                            <?php if (!empty($p['welcome_treat_pref']) || !empty($p['service_notes'])): ?>
                                <div class="small text-muted bg-light p-2 rounded border" style="font-size: 11px;">
                                    <?php if (!empty($p['welcome_treat_pref'])): ?>
                                        <div class="mb-1 text-primary fw-semibold">
                                            <i class="fas fa-gift text-warning me-1"></i>İkram: <?= e($p['welcome_treat_pref']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($p['service_notes'])): ?>
                                        <div><i class="fas fa-comment-alt text-muted me-1"></i><?= e($p['service_notes']) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Card Footer Actions -->
                        <div class="card-footer bg-white border-top py-2 px-3 d-flex justify-content-between align-items-center">
                            <small class="text-muted" style="font-size: 11px;">
                                Son: <?= $p['last_visit_at'] ? date('d.m.Y', strtotime($p['last_visit_at'])) : 'Yeni' ?>
                            </small>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick='openEditPatronModal(<?= json_encode($p) ?>)'>
                                    <i class="fas fa-pencil-alt me-1"></i> Düzenle
                                </button>
                                <a href="<?= site_url('restaurant/reservations?customer_id=' . $p['id_users_customer']) ?>" class="btn btn-sm btn-outline-primary py-1 px-2">
                                    <i class="fas fa-calendar-plus me-1"></i> Rezervasyon
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: MÜDAVİM PROFİLİ OLUŞTUR / DÜZENLE                                 -->
<!-- ========================================================================= -->
<div class="modal fade" id="patron-profile-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="patron-modal-title">
                    <i class="fas fa-crown text-warning me-2"></i>Müdavim Misafir Zeka Profili
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="patron-profile-form">
                    <input type="hidden" name="id_users_customer" id="patron-customer-id">

                    <!-- Customer Selector / Display -->
                    <div class="mb-3" id="patron-select-group">
                        <label class="form-label small fw-bold">Misafir / Müşteri *</label>
                        <div class="input-group">
                            <input type="text" id="patron-lookup-input" class="form-control" placeholder="Misafir adı veya telefon ile arayın...">
                            <button class="btn btn-outline-primary" type="button" onclick="searchCustomerForPatron()">
                                <i class="fas fa-search me-1"></i> Bul
                            </button>
                        </div>
                        <div id="patron-lookup-results" class="list-group mt-1 d-none" style="max-height: 180px; overflow-y: auto;"></div>
                    </div>

                    <div id="patron-selected-customer-banner" class="alert alert-primary d-flex align-items-center justify-content-between p-2 rounded-3 mb-3 d-none">
                        <div>
                            <strong id="patron-display-name">-</strong>
                            <small class="text-muted d-block" id="patron-display-phone">-</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-link text-decoration-none" onclick="resetCustomerSelection()">Değiştir</button>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Müdavim Seviyesi (Tier)</label>
                            <select name="patronage_tier" id="patron-tier" class="form-select">
                                <option value="first_timer">🌱 İlk Ziyaretçi / Potansiyel</option>
                                <option value="regular">🏅 Standart Müdavim (Regular)</option>
                                <option value="vip_regular">⭐ VIP Regular (Öncelikli Masa)</option>
                                <option value="elite">👑 Elite (En Yüksek Öncelik & VIP Guard)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Favori Masa</label>
                            <select name="favorite_table_id" id="patron-fav-table" class="form-select">
                                <option value="">-- Tercih Yok / Otomatik --</option>
                                <?php foreach ($tables as $t): ?>
                                    <option value="<?= $t['id'] ?>">Masa <?= e($t['table_number']) ?> (<?= e($t['section']) ?> - <?= $t['capacity'] ?> Kişilik)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Favori Bölüm / Ambiyans</label>
                            <select name="favorite_section" id="patron-fav-section" class="form-select">
                                <option value="Ana Salon">Ana Salon</option>
                                <option value="Teras">Teras</option>
                                <option value="Bahçe">Bahçe</option>
                                <option value="VIP">VIP</option>
                                <option value="Bar">Bar</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Favori Garson / Servis Personeli</label>
                            <select name="favorite_server_id" id="patron-fav-server" class="form-select">
                                <option value="">-- Fark Etmez --</option>
                                <?php foreach ($staff_members as $sm): ?>
                                    <option value="<?= $sm['id'] ?>"><?= e($sm['first_name'] . ' ' . $sm['last_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Gastronomy Preferences -->
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Favori Yemekler (Virgülle ayırın)</label>
                            <input type="text" name="favorite_dishes" id="patron-fav-dishes" class="form-control" placeholder="Örn: Levrek Buğulama, Bonfile, Sufle">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Favori İçecekler (Virgülle ayırın)</label>
                            <input type="text" name="favorite_drinks" id="patron-fav-drinks" class="form-control" placeholder="Örn: Maden Suyu (Az buzlu), Beyaz Şarap">
                        </div>
                    </div>

                    <!-- Allergens & Dietary -->
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-danger"><i class="fas fa-exclamation-circle me-1"></i>Alerjen Uyarısı</label>
                            <input type="text" name="allergens" id="patron-allergens" class="form-control" placeholder="Örn: Fıstık, Gluten, Kabuklu Deniz Ürünü...">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Diyet Alışkanlıkları</label>
                            <input type="text" name="dietary_habits" id="patron-dietary" class="form-control" placeholder="Örn: Vejetaryen, Ketojenik, Tuzsuz...">
                        </div>
                    </div>

                    <!-- Welcome Treat & Notes -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-primary"><i class="fas fa-gift me-1"></i>Hoş Geldin İkram Tercihi (VIP Treat)</label>
                        <input type="text" name="welcome_treat_pref" id="patron-welcome-treat" class="form-control" placeholder="Örn: Şefin özel meze tabağı veya Köpüklü Şarap kadehi">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Özel Servis & Ağırlama Notları (Personele Özel)</label>
                        <textarea name="service_notes" id="patron-service-notes" class="form-control" rows="2" placeholder="Örn: Masaya geçer geçmez sıcak havlu ve az buzlu maden suyu rica eder. Doğum günleri 14 Mayıs'tır."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary fw-bold" onclick="submitPatronProfile()">Kaydet & Profili Güncelle</button>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script>
function openNewPatronModal() {
    document.getElementById('patron-modal-title').innerHTML = '<i class="fas fa-crown text-warning me-2"></i>Yeni Müdavim Profili Oluştur';
    document.getElementById('patron-customer-id').value = '';
    document.getElementById('patron-select-group').classList.remove('d-none');
    document.getElementById('patron-selected-customer-banner').classList.add('d-none');
    document.getElementById('patron-profile-form').reset();

    const modal = new bootstrap.Modal(document.getElementById('patron-profile-modal'));
    modal.show();
}

function openEditPatronModal(p) {
    document.getElementById('patron-modal-title').innerHTML = '<i class="fas fa-pencil-alt text-primary me-2"></i>Müdavim Profilini Düzenle: ' + (p.customer_name || 'Misafir');
    document.getElementById('patron-customer-id').value = p.id_users_customer;
    document.getElementById('patron-select-group').classList.add('d-none');

    const banner = document.getElementById('patron-selected-customer-banner');
    banner.classList.remove('d-none');
    document.getElementById('patron-display-name').innerText = p.customer_name;
    document.getElementById('patron-display-phone').innerText = p.customer_phone || '-';

    document.getElementById('patron-tier').value = p.patronage_tier || 'regular';
    document.getElementById('patron-fav-table').value = p.favorite_table_id || '';
    document.getElementById('patron-fav-section').value = p.favorite_section || 'Ana Salon';
    document.getElementById('patron-fav-server').value = p.favorite_server_id || '';

    // Handle JSON or array for dishes & drinks
    let dishes = p.favorite_dishes_json;
    if (typeof dishes === 'string') {
        try { dishes = JSON.parse(dishes); } catch(e) {}
    }
    document.getElementById('patron-fav-dishes').value = Array.isArray(dishes) ? dishes.join(', ') : (dishes || '');

    let drinks = p.favorite_drinks_json;
    if (typeof drinks === 'string') {
        try { drinks = JSON.parse(drinks); } catch(e) {}
    }
    document.getElementById('patron-fav-drinks').value = Array.isArray(drinks) ? drinks.join(', ') : (drinks || '');

    document.getElementById('patron-allergens').value = p.allergens || '';
    document.getElementById('patron-dietary').value = p.dietary_habits || '';
    document.getElementById('patron-welcome-treat').value = p.welcome_treat_pref || '';
    document.getElementById('patron-service-notes').value = p.service_notes || '';

    const modal = new bootstrap.Modal(document.getElementById('patron-profile-modal'));
    modal.show();
}

function searchCustomerForPatron() {
    const q = document.getElementById('patron-lookup-input').value.trim();
    if (!q) {
        alert('Lütfen arama terimi girin.');
        return;
    }

    fetch('<?= site_url('restaurant/api/lookup_patron') ?>?q=' + encodeURIComponent(q))
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('patron-lookup-results');
            container.innerHTML = '';
            if (data.status === 'success' && data.results && data.results.length > 0) {
                container.classList.remove('d-none');
                data.results.forEach(c => {
                    const item = document.createElement('a');
                    item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2';
                    item.href = 'javascript:void(0)';
                    item.innerHTML = `<div><strong>${c.full_name}</strong> <small class="text-muted">(${c.phone_number || 'Tel yok'})</small></div> <span class="badge bg-primary">Seç</span>`;
                    item.onclick = () => selectCustomerForPatron(c);
                    container.appendChild(item);
                });
            } else {
                container.classList.remove('d-none');
                container.innerHTML = '<div class="list-group-item text-muted small">Misafir bulunamadı.</div>';
            }
        });
}

function selectCustomerForPatron(c) {
    document.getElementById('patron-customer-id').value = c.id;
    document.getElementById('patron-lookup-results').classList.add('d-none');
    document.getElementById('patron-select-group').classList.add('d-none');

    const banner = document.getElementById('patron-selected-customer-banner');
    banner.classList.remove('d-none');
    document.getElementById('patron-display-name').innerText = c.full_name;
    document.getElementById('patron-display-phone').innerText = c.phone_number || c.email || '-';

    // If customer already has dining profile, prefill
    if (c.patron_profile) {
        openEditPatronModal({
            ...c.patron_profile,
            id_users_customer: c.id,
            customer_name: c.full_name,
            customer_phone: c.phone_number
        });
    }
}

function resetCustomerSelection() {
    document.getElementById('patron-customer-id').value = '';
    document.getElementById('patron-select-group').classList.remove('d-none');
    document.getElementById('patron-selected-customer-banner').classList.add('d-none');
}

function submitPatronProfile() {
    const customerId = document.getElementById('patron-customer-id').value;
    if (!customerId) {
        alert('Lütfen bir misafir seçin.');
        return;
    }

    const dishesVal = document.getElementById('patron-fav-dishes').value;
    const dishesArr = dishesVal.split(',').map(s => s.trim()).filter(Boolean);

    const drinksVal = document.getElementById('patron-fav-drinks').value;
    const drinksArr = drinksVal.split(',').map(s => s.trim()).filter(Boolean);

    const payload = {
        id_users_customer: customerId,
        patronage_tier: document.getElementById('patron-tier').value,
        favorite_table_id: document.getElementById('patron-fav-table').value || null,
        favorite_section: document.getElementById('patron-fav-section').value,
        favorite_server_id: document.getElementById('patron-fav-server').value || null,
        favorite_dishes_json: JSON.stringify(dishesArr),
        favorite_drinks_json: JSON.stringify(drinksArr),
        allergens: document.getElementById('patron-allergens').value,
        dietary_habits: document.getElementById('patron-dietary').value,
        welcome_treat_pref: document.getElementById('patron-welcome-treat').value,
        service_notes: document.getElementById('patron-service-notes').value,
    };

    fetch('<?= site_url('restaurant/api/save_patron_profile') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Müdavim profili başarıyla kaydedildi.');
            window.location.reload();
        } else {
            alert(data.message || 'Kayıt sırasında hata oluştu.');
        }
    });
}
</script>
<?php end_section('scripts'); ?>
