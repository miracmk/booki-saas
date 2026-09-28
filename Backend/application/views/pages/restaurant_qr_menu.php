<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * BooKi - Digital QR Menu & Self-Order Portal
 * Mobile-First, Real-Time Table Ordering with Loyalty & Membership Perks
 *
 * @var array|null $table
 * @var string|null $table_token
 * @var array $categories
 * @var array $items
 * @var array|null $customer
 * @var array|null $membership
 * @var array|null $table_experience
 * @var string $company_name
 * @var array|null $qr_settings
 */

$brand_primary = !empty($qr_settings['primary_color']) ? $qr_settings['primary_color'] : '#f97316';
$brand_accent = !empty($qr_settings['accent_color']) ? $qr_settings['accent_color'] : '#0f172a';
$font_family = !empty($qr_settings['font_family']) ? $qr_settings['font_family'] : 'Poppins';
$hero_title = !empty($qr_settings['hero_title']) ? $qr_settings['hero_title'] : $company_name;
$hero_subtitle = !empty($qr_settings['hero_subtitle']) ? $qr_settings['hero_subtitle'] : 'Lezzetli anlar ve seçkin lezzetler sizi bekliyor.';
$hero_banner = !empty($qr_settings['hero_banner_url']) ? $qr_settings['hero_banner_url'] : '';
$allow_self_order = isset($qr_settings['allow_self_order']) ? (bool) $qr_settings['allow_self_order'] : true;
$enable_waiter_call = isset($qr_settings['enable_waiter_call']) ? (bool) $qr_settings['enable_waiter_call'] : true;
$enable_bill_request = isset($qr_settings['enable_bill_request']) ? (bool) $qr_settings['enable_bill_request'] : true;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= e($hero_title) ?> — QR Menü & Masadan Sipariş</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=<?= urlencode($font_family) ?>:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --brand-primary: <?= $brand_primary ?>;
            --brand-primary-dark: <?= $brand_primary ?>;
            --brand-accent: <?= $brand_accent ?>;
            --brand-bg: #f8fafc;
            --brand-surface: #ffffff;
            --brand-text: #1e293b;
            --brand-muted: #64748b;
            --radius-md: 14px;
            --radius-lg: 20px;
        }
        body {
            font-family: '<?= $font_family ?>', sans-serif;
            background-color: var(--brand-bg);
            color: var(--brand-text);
            padding-bottom: 90px;
            -webkit-tap-highlight-color: transparent;
        }
        .header-banner {
            background: linear-gradient(135deg, <?= $brand_accent ?> 0%, #1e293b 100%);
            color: #ffffff;
            border-bottom-left-radius: 28px;
            border-bottom-right-radius: 28px;
            padding: 24px 20px 20px 20px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15);
        }
        .category-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--brand-text);
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
        }
        .category-pill.active, .category-pill:hover {
            background: var(--brand-primary);
            color: #ffffff;
            border-color: var(--brand-primary);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.25);
        }
        .item-card {
            background: var(--brand-surface);
            border-radius: var(--radius-md);
            border: 1px solid #f1f5f9;
            overflow: hidden;
            transition: all 0.25s ease;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        .item-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
            border-color: #fed7aa;
        }
        .item-img-container {
            width: 110px;
            height: 110px;
            min-width: 110px;
            border-radius: 12px;
            overflow: hidden;
            background: #e2e8f0;
            position: relative;
        }
        .item-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .badge-dietary {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 3px 8px;
            border-radius: 6px;
        }
        .badge-chef { background-color: #fef3c7; color: #b45309; }
        .badge-vegan { background-color: #dcfce7; color: #15803d; }
        .badge-veg { background-color: #dcfce7; color: #166534; }
        .badge-gluten { background-color: #e0f2fe; color: #0369a1; }
        .badge-spicy { background-color: #fee2e2; color: #b91c1c; }

        .sticky-cart-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-top: 1px solid #e2e8f0;
            padding: 12px 20px;
            z-index: 1050;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
        }
        .modal-bottom-sheet .modal-dialog {
            margin: 0;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            max-width: 520px;
            margin-left: auto;
            margin-right: auto;
        }
        .modal-bottom-sheet .modal-content {
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
            border: none;
            box-shadow: 0 -10px 40px rgba(0,0,0,0.2);
            max-height: 85vh;
            overflow-y: auto;
        }
        .floating-action-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            transition: transform 0.15s;
        }
        .floating-action-btn:active {
            transform: scale(0.92);
        }
    </style>
</head>
<body>

    <!-- TOP HEADER -->
    <header class="header-banner">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <span class="badge bg-warning text-dark fw-bold px-2 py-1 mb-1">
                    <i class="fas fa-utensils me-1"></i> Dijital Menü & Sipariş
                </span>
                <h1 class="h4 fw-bolder mb-0"><?= e($company_name) ?></h1>
                <p class="small text-white-50 mb-0">Gurme lezzetler masanıza servis edilir</p>
            </div>
            <div class="text-end">
                <?php if ($table): ?>
                    <div class="badge bg-primary fs-6 px-3 py-2 shadow-sm rounded-pill">
                        <i class="fas fa-chair me-1"></i> Masa <?= e($table['table_number']) ?>
                    </div>
                    <div class="small text-white-50 mt-1"><?= e($table['section']) ?></div>
                <?php else: ?>
                    <div class="badge bg-secondary fs-6 px-3 py-2 rounded-pill">
                        <i class="fas fa-store me-1"></i> Genel Menü
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- QUICK TABLE ACTIONS -->
        <?php if ($table): ?>
            <div class="d-flex gap-2 mt-3 pt-2 border-top border-secondary border-opacity-25 flex-wrap">
                <button class="btn btn-sm btn-outline-warning rounded-pill px-3" onclick="triggerCallWaiter('waiter')">
                    <i class="fas fa-bell me-1"></i> Garson Çağır
                </button>
                <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="triggerCallWaiter('bill')">
                    <i class="fas fa-receipt me-1"></i> Hesap İste
                </button>
                <?php if (!empty($table_experience['adisyon'])): ?>
                    <a href="<?= site_url('restaurant/customer_screen/' . e($table['qr_token'] ?: $table['id'])) ?>" class="btn btn-sm btn-light text-dark rounded-pill px-3 fw-bold ms-auto">
                        <i class="fas fa-clock me-1 text-primary"></i> Canlı Takip & Adisyon
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- LOYALTY & MEMBERSHIP STATUS BANNER -->
        <div class="card bg-dark bg-opacity-50 border-0 rounded-4 p-3 mt-3 text-white">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-warning text-dark rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fas fa-gem fs-5"></i>
                    </div>
                    <div>
                        <?php if ($customer): ?>
                            <div class="fw-bold fs-6">Merhaba, <?= e($customer['first_name']) ?>!</div>
                            <div class="small text-warning">
                                <i class="fas fa-coins me-1"></i><?= (int) ($customer['loyalty_balance'] ?? 0) ?> Sadakat Puanı
                                <?php if ($membership): ?>
                                    &bull; <span class="badge bg-success"><?= e($membership['plan_name'] ?? 'VIP Üye') ?> (%<?= (float) $membership['discount_percent'] ?> İndirim)</span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="fw-bold fs-6">Sadakat Kulübü & Üyelik İndirimleri</div>
                            <div class="small text-white-50">Siparişinizde %5 Nakit Puan kazanın! Telefonunuzla anında katılın.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- MAIN MENU CONTENT -->
    <main class="container py-3">

        <!-- CATEGORIES SCROLLER -->
        <div class="d-flex gap-2 overflow-x-auto pb-2 mb-3 no-scrollbar" style="scroll-snap-type: x mandatory;">
            <a href="#cat-all" class="category-pill active" onclick="filterCategory(0, this, event)">
                <i class="fas fa-th-large"></i> Tümü
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="#cat-<?= $cat['id'] ?>" class="category-pill" onclick="filterCategory(<?= $cat['id'] ?>, this, event)">
                    <i class="fas <?= e($cat['icon'] ?: 'fa-utensils') ?>"></i> <?= e($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- SEARCH & DIETARY FILTER -->
        <div class="row g-2 mb-4">
            <div class="col-12 col-md-8">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 rounded-start-pill ps-3 text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control bg-white border-start-0 rounded-end-pill ps-1" id="menu-search-input" placeholder="Menüde yemek, içecek veya tatlı arayın..." onkeyup="filterItems()">
                </div>
            </div>
            <div class="col-12 col-md-4">
                <select class="form-select rounded-pill" id="dietary-filter" onchange="filterItems()">
                    <option value="">Beslenme Tercihi: Tümü</option>
                    <option value="chef_special">🌟 Şefin Spesiyali</option>
                    <option value="vegan">🌱 Vegan</option>
                    <option value="vegetarian">🥗 Vejetaryen</option>
                    <option value="gluten_free">🌾 Glutensiz</option>
                </select>
            </div>
        </div>

        <!-- ITEMS GRID -->
        <div class="row g-3" id="menu-items-grid">
            <?php foreach ($items as $item): 
                if (isset($item['is_qr_visible']) && (int) $item['is_qr_visible'] === 0) continue;
            ?>
                <div class="col-12 col-md-6 menu-item-card-wrapper" 
                     data-category="<?= (int) $item['id_categories'] ?>"
                     data-name="<?= strtolower(e($item['name'])) ?>"
                     data-desc="<?= strtolower(e($item['description'] ?? '')) ?>"
                     data-dietary="<?= e($item['dietary_badges'] ?? '') ?>"
                     data-allergens="<?= e($item['allergens'] ?? '') ?>"
                     data-id="<?= (int) $item['id'] ?>">
                    
                    <div class="item-card p-3 h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex gap-3">
                            <!-- Image -->
                            <?php if (!empty($item['image_url'])): ?>
                                <div class="item-img-container shadow-sm">
                                    <img src="<?= e($item['image_url']) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
                                    <?php if ($item['calories']): ?>
                                        <span class="position-absolute bottom-0 start-0 m-1 badge bg-dark bg-opacity-75 text-white" style="font-size: 10px;">
                                            <?= $item['calories'] ?> kcal
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Info -->
                            <div class="flex-grow-1">
                                <div class="d-flex gap-1 flex-wrap mb-1">
                                    <?php if (!empty($item['badge_text'])): ?>
                                        <span class="badge bg-warning text-dark fw-bold" style="font-size: 10px;">
                                            <?= e($item['badge_text']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (str_contains($item['dietary_badges'] ?? '', 'chef_special')): ?>
                                        <span class="badge badge-dietary badge-chef"><i class="fas fa-crown me-1"></i>Şefin Seçimi</span>
                                    <?php endif; ?>
                                    <?php if (str_contains($item['dietary_badges'] ?? '', 'vegan')): ?>
                                        <span class="badge badge-dietary badge-vegan">Vegan</span>
                                    <?php elseif (str_contains($item['dietary_badges'] ?? '', 'vegetarian')): ?>
                                        <span class="badge badge-dietary badge-veg">Vejetaryen</span>
                                    <?php endif; ?>
                                    <?php if (str_contains($item['dietary_badges'] ?? '', 'gluten_free')): ?>
                                        <span class="badge badge-dietary badge-gluten">Glutensiz</span>
                                    <?php endif; ?>
                                </div>

                                <h3 class="h6 fw-bold mb-1 text-dark"><?= e($item['name']) ?></h3>
                                <p class="small text-muted mb-2 line-clamp-2" style="font-size: 0.82rem; line-height: 1.35;">
                                    <?= e($item['description']) ?>
                                </p>

                                <?php if (!empty($item['allergens'])): ?>
                                    <div class="small text-muted fst-italic mb-1" style="font-size: 11px;">
                                        <i class="fas fa-info-circle text-secondary me-1"></i>Alerjen: <?= e($item['allergens']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Footer Price & Add Button -->
                        <div class="d-flex align-items-center justify-content-between mt-3 pt-2 border-top">
                            <div>
                                <span class="h5 fw-bolder text-primary mb-0"><?= number_format((float) $item['price'], 2) ?> ₺</span>
                                <small class="text-muted d-block" style="font-size: 10px;"><i class="fas fa-stopwatch me-1"></i><?= $item['prep_time_minutes'] ?> dk</small>
                            </div>
                            <button class="btn btn-primary rounded-pill px-3 fw-bold btn-add-item" 
                                    onclick='openItemModal(<?= json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                <i class="fas fa-plus me-1"></i> Ekle
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </main>

    <!-- STICKY CART BAR -->
    <div class="sticky-cart-bar" id="sticky-cart-bar" style="display: none;">
        <div class="container d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="position-relative">
                    <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-shopping-bag fs-5"></i>
                    </div>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cart-count-badge">0</span>
                </div>
                <div>
                    <div class="small text-muted">Sipariş Toplamı</div>
                    <div class="fw-bolder fs-5 text-dark" id="cart-total-text">0.00 ₺</div>
                </div>
            </div>
            <button class="btn btn-primary btn-lg rounded-pill px-4 fw-bold shadow" onclick="openCartModal()">
                Sepeti Gör <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </div>
    </div>

    <!-- ITEM DETAIL & OPTIONS MODAL (BOTTOM SHEET) -->
    <div class="modal fade modal-bottom-sheet" id="itemDetailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-1 px-4">
                    <div id="modal-item-img-wrap" class="text-center mb-3" style="display: none;">
                        <img id="modal-item-img" src="" class="img-fluid rounded-4 shadow-sm" style="max-height: 180px; width: 100%; object-fit: cover;">
                    </div>
                    <h3 class="h5 fw-bold text-dark mb-1" id="modal-item-title"></h3>
                    <p class="text-muted small mb-2" id="modal-item-desc"></p>
                    <div class="h4 fw-bold text-primary mb-3" id="modal-item-price"></div>

                    <!-- DYNAMIC OPTIONS CONTAINER -->
                    <div id="modal-options-container" class="mb-3"></div>

                    <!-- SPECIAL NOTE -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary"><i class="fas fa-pen me-1"></i>Şefe / Mutfağa Özel Not</label>
                        <input type="text" class="form-control rounded-3 text-sm" id="modal-item-note" placeholder="Örn: Soğansız olsun, az tuzlu, ekstra peçete...">
                    </div>

                    <!-- QUANTITY SELECTOR -->
                    <div class="d-flex align-items-center justify-content-between bg-light p-2 rounded-4 mb-3">
                        <span class="fw-bold small ps-2">Adet Seçimi</span>
                        <div class="d-flex align-items-center gap-3">
                            <button class="btn btn-outline-secondary btn-sm rounded-circle" style="width: 34px; height: 34px;" onclick="changeModalQty(-1)"><i class="fas fa-minus"></i></button>
                            <span class="fw-bold fs-5" id="modal-item-qty">1</span>
                            <button class="btn btn-primary btn-sm rounded-circle" style="width: 34px; height: 34px;" onclick="changeModalQty(1)"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 py-3 rounded-pill fw-bold fs-6 shadow mb-2" onclick="confirmAddToCart()">
                        Sepete Ekle &bull; <span id="modal-item-total-btn">0.00 ₺</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- CART & CHECKOUT MODAL (OFFCANVAS / BOTTOM SHEET) -->
    <div class="modal fade modal-bottom-sheet" id="cartModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-shopping-bag text-primary me-2"></i>Masa Siparişiniz
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- ITEMS LIST -->
                    <div id="cart-items-list" class="mb-3"></div>

                    <!-- LOYALTY & PHONE INPUT -->
                    <div class="card bg-light border-0 rounded-4 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold small text-dark"><i class="fas fa-gem text-warning me-1"></i>Sadakat Puanı & Kampanya</span>
                            <span class="badge bg-warning text-dark">+%5 Puan</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-7">
                                <input type="tel" class="form-control form-control-sm rounded-3" id="order-phone" 
                                       placeholder="Telefon No (Puan için)" 
                                       value="<?= e($customer['phone_number'] ?? '') ?>">
                            </div>
                            <div class="col-5">
                                <input type="text" class="form-control form-control-sm rounded-3" id="order-name" 
                                       placeholder="İsim" 
                                       value="<?= e(trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''))) ?>">
                            </div>
                        </div>
                        <?php if ($membership): ?>
                            <div class="alert alert-success py-1 px-2 mt-2 mb-0 small rounded-3">
                                <i class="fas fa-check-circle me-1"></i><strong><?= e($membership['plan_name']) ?>:</strong> Siparişinize %<?= (float) $membership['discount_percent'] ?> indirim uygulanacaktır!
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- SUMMARY -->
                    <div class="bg-white border rounded-4 p-3 mb-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Ara Toplam</span>
                            <span id="cart-summary-subtotal">0.00 ₺</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>KDV (%10 Dahil)</span>
                            <span id="cart-summary-tax">0.00 ₺</span>
                        </div>
                        <?php if ($membership && (float) $membership['discount_percent'] > 0): ?>
                            <div class="d-flex justify-content-between small text-success fw-bold mb-1">
                                <span>Üyelik İndirimi (%<?= (float) $membership['discount_percent'] ?>)</span>
                                <span id="cart-summary-discount">-0.00 ₺</span>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between fs-5 fw-bolder text-dark pt-2 border-top">
                            <span>Toplam Tutar</span>
                            <span id="cart-summary-total" class="text-primary">0.00 ₺</span>
                        </div>
                    </div>

                    <!-- SUBMIT ORDER -->
                    <button class="btn btn-primary w-100 py-3 rounded-pill fw-bold fs-6 shadow mb-2" id="btn-submit-order" onclick="submitOrder()">
                        <i class="fas fa-paper-plane me-2"></i>Siparişi Mutfağa İlet
                    </button>
                    <small class="text-muted d-block text-center" style="font-size: 11px;">
                        <i class="fas fa-check me-1"></i>Siparişiniz doğrudan şefin ekranına ve bara anlık düşecektir.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- ORDER SUCCESS MODAL -->
    <div class="modal fade" id="orderSuccessModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 p-4 text-center">
                <div class="mb-3">
                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 72px; height: 72px;">
                        <i class="fas fa-check fa-2x"></i>
                    </div>
                </div>
                <h4 class="fw-bolder mb-1">Siparişiniz Alındı!</h4>
                <p class="text-muted small mb-3">Mutfak ve bar ekibimiz hazırlıklara başladı. Siparişinizin durumunu canlı izleyebilirsiniz.</p>
                
                <div class="card bg-light border-0 rounded-4 p-3 mb-3 text-start small">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Adisyon No:</span>
                        <strong id="success-adisyon-no">-</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Masa:</span>
                        <strong id="success-table-no"><?= e($table['table_number'] ?? '1') ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Kazanılan Sadakat Puanı:</span>
                        <strong class="text-warning" id="success-points">+0 Puan</strong>
                    </div>
                </div>

                <a href="<?= site_url('restaurant/customer_screen/' . e($table['qr_token'] ?: ($table['id'] ?? '1'))) ?>" class="btn btn-primary w-100 py-3 rounded-pill fw-bold mb-2">
                    <i class="fas fa-stream me-2"></i>Canlı Sipariş Takip Ekranına Git
                </a>
                <button type="button" class="btn btn-outline-secondary w-100 rounded-pill" data-bs-dismiss="modal">
                    Menüyü İncelemeye Devam Et
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const tableToken = <?= json_encode($table['qr_token'] ?? $table['id'] ?? '1') ?>;
        const membershipDiscountRate = <?= (float) ($membership['discount_percent'] ?? 0) ?>;

        let cart = [];
        let currentModalItem = null;
        let currentModalQty = 1;

        // Filter Categories
        function filterCategory(catId, element, e) {
            if (e) e.preventDefault();
            document.querySelectorAll('.category-pill').forEach(el => el.classList.remove('active'));
            element.classList.add('active');

            const wrappers = document.querySelectorAll('.menu-item-card-wrapper');
            wrappers.forEach(w => {
                if (catId === 0 || parseInt(w.getAttribute('data-category')) === catId) {
                    w.style.display = 'block';
                } else {
                    w.style.display = 'none';
                }
            });
        }

        // Search & Dietary filter
        function filterItems() {
            const query = document.getElementById('menu-search-input').value.toLowerCase().trim();
            const dietary = document.getElementById('dietary-filter').value;
            const wrappers = document.querySelectorAll('.menu-item-card-wrapper');

            wrappers.forEach(w => {
                const name = w.getAttribute('data-name');
                const desc = w.getAttribute('data-desc');
                const diet = w.getAttribute('data-dietary');

                const matchesQuery = !query || name.includes(query) || desc.includes(query);
                const matchesDietary = !dietary || diet.includes(dietary);

                if (matchesQuery && matchesDietary) {
                    w.style.display = 'block';
                } else {
                    w.style.display = 'none';
                }
            });
        }

        // Open item modal for options
        function openItemModal(item) {
            currentModalItem = item;
            currentModalQty = 1;

            document.getElementById('modal-item-title').innerText = item.name;
            document.getElementById('modal-item-desc').innerText = item.description || '';
            document.getElementById('modal-item-price').innerText = parseFloat(item.price).toFixed(2) + ' ₺';
            document.getElementById('modal-item-qty').innerText = '1';
            document.getElementById('modal-item-note').value = '';
            document.getElementById('modal-item-total-btn').innerText = parseFloat(item.price).toFixed(2) + ' ₺';

            if (item.image_url) {
                document.getElementById('modal-item-img').src = item.image_url;
                document.getElementById('modal-item-img-wrap').style.display = 'block';
            } else {
                document.getElementById('modal-item-img-wrap').style.display = 'none';
            }

            // Options container
            const optContainer = document.getElementById('modal-options-container');
            optContainer.innerHTML = '';

            if (item.options && typeof item.options === 'object') {
                for (const [groupName, choices] of Object.entries(item.options)) {
                    if (Array.isArray(choices) && choices.length > 0) {
                        const grpDiv = document.createElement('div');
                        grpDiv.className = 'mb-2';
                        grpDiv.innerHTML = `<label class="form-label small fw-bold mb-1 text-dark">${groupName}</label>`;
                        
                        const select = document.createElement('select');
                        select.className = 'form-select form-select-sm rounded-3 modal-opt-select';
                        select.setAttribute('data-group', groupName);
                        choices.forEach(ch => {
                            const opt = document.createElement('option');
                            opt.value = ch;
                            opt.innerText = ch;
                            select.appendChild(opt);
                        });
                        grpDiv.appendChild(select);
                        optContainer.appendChild(grpDiv);
                    }
                }
            }

            const modal = new bootstrap.Modal(document.getElementById('itemDetailModal'));
            modal.show();
        }

        function changeModalQty(delta) {
            currentModalQty = Math.max(1, currentModalQty + delta);
            document.getElementById('modal-item-qty').innerText = currentModalQty;
            const total = (parseFloat(currentModalItem.price) * currentModalQty).toFixed(2);
            document.getElementById('modal-item-total-btn').innerText = total + ' ₺';
        }

        function confirmAddToCart() {
            if (!currentModalItem) return;

            // Collect selected options
            const selectedOptions = {};
            document.querySelectorAll('.modal-opt-select').forEach(sel => {
                selectedOptions[sel.getAttribute('data-group')] = sel.value;
            });

            const note = document.getElementById('modal-item-note').value.trim();

            cart.push({
                id: currentModalItem.id,
                name: currentModalItem.name,
                price: parseFloat(currentModalItem.price),
                station: currentModalItem.station || 'kitchen',
                quantity: currentModalQty,
                selected_options: selectedOptions,
                notes: note
            });

            bootstrap.Modal.getInstance(document.getElementById('itemDetailModal')).hide();
            updateCartUI();
        }

        function updateCartUI() {
            const count = cart.reduce((acc, item) => acc + item.quantity, 0);
            const total = cart.reduce((acc, item) => acc + (item.price * item.quantity), 0);

            document.getElementById('cart-count-badge').innerText = count;
            document.getElementById('cart-total-text').innerText = total.toFixed(2) + ' ₺';

            const bar = document.getElementById('sticky-cart-bar');
            bar.style.display = count > 0 ? 'block' : 'none';
        }

        function openCartModal() {
            const container = document.getElementById('cart-items-list');
            container.innerHTML = '';

            if (cart.length === 0) {
                container.innerHTML = '<p class="text-center text-muted py-3">Sepetiniz boş.</p>';
            } else {
                cart.forEach((item, index) => {
                    let optStr = '';
                    if (item.selected_options && Object.keys(item.selected_options).length > 0) {
                        optStr = Object.entries(item.selected_options).map(([k,v]) => `${k}: ${v}`).join(', ');
                    }

                    const row = document.createElement('div');
                    row.className = 'd-flex align-items-center justify-content-between py-2 border-bottom';
                    row.innerHTML = `
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark">${item.name}</div>
                            ${optStr ? `<div class="text-muted" style="font-size: 11px;">${optStr}</div>` : ''}
                            ${item.notes ? `<div class="text-secondary fst-italic" style="font-size: 11px;"><i class="fas fa-pen me-1"></i>${item.notes}</div>` : ''}
                            <div class="small text-primary fw-bold">${(item.price * item.quantity).toFixed(2)} ₺ (${item.quantity}x ${item.price.toFixed(2)} ₺)</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-danger rounded-circle" style="width: 28px; height: 28px;" onclick="removeCartItem(${index})">
                                <i class="fas fa-trash-alt" style="font-size: 11px;"></i>
                            </button>
                        </div>
                    `;
                    container.appendChild(row);
                });
            }

            const subtotal = cart.reduce((acc, item) => acc + (item.price * item.quantity), 0);
            const discount = (subtotal * (membershipDiscountRate / 100));
            const finalTotal = Math.max(0, subtotal - discount);

            document.getElementById('cart-summary-subtotal').innerText = subtotal.toFixed(2) + ' ₺';
            document.getElementById('cart-summary-tax').innerText = (finalTotal * 0.10).toFixed(2) + ' ₺';
            if (document.getElementById('cart-summary-discount')) {
                document.getElementById('cart-summary-discount').innerText = '-' + discount.toFixed(2) + ' ₺';
            }
            document.getElementById('cart-summary-total').innerText = finalTotal.toFixed(2) + ' ₺';

            const modal = new bootstrap.Modal(document.getElementById('cartModal'));
            modal.show();
        }

        function removeCartItem(index) {
            cart.splice(index, 1);
            updateCartUI();
            openCartModal();
        }

        // Submit order via AJAX
        async function submitOrder() {
            if (cart.length === 0) return;

            const btn = document.getElementById('btn-submit-order');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sipariş İletiliyor...';

            const payload = {
                table_token: tableToken,
                items: cart,
                customer_phone: document.getElementById('order-phone').value.trim(),
                customer_name: document.getElementById('order-name').value.trim(),
            };

            try {
                const res = await fetch('<?= site_url('restaurant/api/self_order') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.status === 'success') {
                    // Reset cart
                    cart = [];
                    updateCartUI();

                    bootstrap.Modal.getInstance(document.getElementById('cartModal')).hide();

                    document.getElementById('success-adisyon-no').innerText = data.adisyon_number || '-';
                    document.getElementById('success-points').innerText = '+' + (data.potential_loyalty_points || 0) + ' Puan';

                    const successModal = new bootstrap.Modal(document.getElementById('orderSuccessModal'));
                    successModal.show();
                } else {
                    alert('Hata: ' + (data.message || 'Sipariş gönderilemedi.'));
                }
            } catch (err) {
                alert('Ağ hatası: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Siparişi Mutfağa İlet';
            }
        }

        // Call waiter or request bill
        async function triggerCallWaiter(type) {
            const note = type === 'bill' ? prompt('Hesap ödeme tercihiniz (Nakit / Kredi Kartı / Sadakat Puanı):', 'Kredi Kartı') : prompt('Garsona iletmek istediğiniz özel bir istek var mı (Örn: Peçete, su)?', '');
            if (note === null) return;

            try {
                const res = await fetch('<?= site_url('restaurant/api/call_waiter') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        table_token: tableToken,
                        call_type: type,
                        note: note
                    })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    alert(data.message || 'Talebiniz personele iletildi.');
                } else {
                    alert('Hata: ' + (data.message || 'İşlem başarısız.'));
                }
            } catch (err) {
                alert('Bağlantı hatası: ' + err.message);
            }
        }
    </script>
</body>
</html>
