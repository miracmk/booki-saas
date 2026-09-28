<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * BooKi - Professional Restaurant Cashier & Register Terminal (POS)
 * Interactive Floor Selector, Order Entry, Split Bill, Loyalty & Membership Deductions, Slip Printing
 *
 * @var array $tables
 * @var array $categories
 * @var array $menu_items
 * @var array $customers
 */
$user_display_name = vars('user_display_name') ?? 'Kasiyer';
$dining_count = count(array_filter($tables, fn($t) => in_array($t['status'], ['dining', 'seated'])));
$bill_count = count(array_filter($tables, fn($t) => $t['status'] === 'bill_requested'));
$available_count = count(array_filter($tables, fn($t) => $t['status'] === 'available'));
$total_tables = count($tables);
$sections = array_values(array_unique(array_filter(array_column($tables, 'section'))));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Kasa & POS Satış Terminali - BooKi</title>
    <link rel="stylesheet" type="text/css" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/general.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/backend.min.css') ?>">
    <script src="<?= base_url('assets/vendor/@fortawesome-fontawesome-free/fontawesome.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/@fortawesome-fontawesome-free/solid.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/bootstrap/bootstrap.min.js') ?>"></script>
    <style>
        :root {
            --pos-bg: #f1f5f9;
            --pos-header-bg: #0f172a;
            --pos-panel-bg: #ffffff;
            --pos-border: #e2e8f0;
            --pos-primary: #2563eb;
            --pos-success: #10b981;
            --pos-warning: #f59e0b;
            --pos-danger: #ef4444;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--pos-bg);
            color: #1e293b;
            min-height: 100vh;
            user-select: none;
        }
        .mono-num {
            font-family: 'JetBrains Mono', monospace;
        }
        /* NAVBAR */
        .pos-navbar {
            background-color: var(--pos-header-bg);
            border-bottom: 1px solid #1e293b;
        }
        .pos-nav-btn {
            font-size: 0.85rem;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 9999px;
            transition: all 0.15s ease;
        }
        /* STATS PILLS */
        .stat-pill {
            background: #ffffff;
            border: 1px solid var(--pos-border);
            border-radius: 12px;
            padding: 8px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        /* TABLE CARD */
        .pos-table-card {
            background: #ffffff;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.18s ease;
            border: 2px solid transparent;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
            position: relative;
            overflow: hidden;
        }
        .pos-table-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0,0,0,0.08);
        }
        .pos-table-card.active {
            border-color: var(--pos-primary) !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25) !important;
        }
        .pos-table-available {
            border-color: #bbf7d0;
            background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 50%);
        }
        .pos-table-dining {
            border-color: #bfdbfe;
            background: linear-gradient(180deg, #eff6ff 0%, #ffffff 50%);
        }
        .pos-table-bill {
            border-color: #fde68a;
            background: linear-gradient(180deg, #fffbeb 0%, #ffffff 50%);
            animation: pulse-border 1.5s infinite;
        }
        .pos-table-cleaning {
            border-color: #e2e8f0;
            background: #f8fafc;
        }
        .pos-table-reserved {
            border-color: #c4b5fd;
            background: linear-gradient(180deg, #f5f3ff 0%, #ffffff 50%);
        }
        @keyframes pulse-border {
            0% { border-color: #f59e0b; box-shadow: 0 0 0 1px #f59e0b; }
            50% { border-color: #ef4444; box-shadow: 0 0 12px rgba(239, 68, 68, 0.4); }
            100% { border-color: #f59e0b; box-shadow: 0 0 0 1px #f59e0b; }
        }
        /* SECTION FILTER BUTTONS */
        .btn-section-filter {
            font-size: 0.85rem;
            font-weight: 700;
            border-radius: 9999px;
            padding: 6px 16px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            transition: all 0.15s;
        }
        .btn-section-filter.active, .btn-section-filter:hover {
            background: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
        }
        /* ADISYON PANEL */
        .adisyon-box {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            border: 1px solid var(--pos-border);
        }
        .order-row {
            transition: background 0.15s;
            border-radius: 10px;
        }
        .order-row:hover {
            background: #f8fafc;
        }
        /* PAY BUTTONS */
        .btn-pay {
            font-size: 1rem;
            font-weight: 700;
            padding: 14px;
            border-radius: 14px;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-pay:active {
            transform: scale(0.98);
        }
        /* TOAST */
        #pos-toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
        }
    </style>
</head>
<body>

    <!-- TOP NAVIGATION BAR -->
    <nav class="pos-navbar px-3 py-2 text-white shadow-sm">
        <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap gap-2">
            
            <div class="d-flex align-items-center gap-2">
                <a href="<?= site_url('restaurant') ?>" class="btn btn-outline-light btn-sm pos-nav-btn">
                    <i class="fas fa-th-large me-1"></i> Masa Planı
                </a>
                <a href="<?= site_url('dashboard') ?>" class="btn btn-outline-light btn-sm pos-nav-btn text-muted">
                    <i class="fas fa-arrow-left me-1"></i> Ana Panel
                </a>
                <div class="ms-2 d-none d-md-flex align-items-center gap-2 border-start border-secondary ps-3">
                    <span class="badge bg-warning text-dark px-2 py-1 fw-bold fs-6">
                        <i class="fas fa-cash-register me-1"></i> Kasa Terminali
                    </span>
                    <span class="text-light small opacity-75">BooKi POS Operating System</span>
                </div>
            </div>

            <!-- STATION LINKS -->
            <div class="d-flex align-items-center gap-2">
                <a href="<?= site_url('restaurant/kitchen_screen') ?>" class="btn btn-outline-danger btn-sm pos-nav-btn" target="_blank">
                    <i class="fas fa-fire me-1"></i> Mutfak & KDS
                </a>
                <a href="<?= site_url('restaurant/waitress_screen') ?>" class="btn btn-outline-info btn-sm pos-nav-btn" target="_blank">
                    <i class="fas fa-tablet-alt me-1"></i> Garson Terminali
                </a>
                <button type="button" class="btn btn-outline-light btn-sm pos-nav-btn d-none d-lg-inline-block" onclick="toggleFullScreen()">
                    <i class="fas fa-expand me-1"></i> Tam Ekran
                </button>
                <div class="badge bg-dark border border-secondary py-2 px-3 rounded-pill mono-num fs-6">
                    <i class="fas fa-clock text-warning me-1"></i><span id="pos-clock">--:--:--</span>
                </div>
                <span class="badge bg-secondary rounded-pill py-2 px-3 small d-none d-sm-inline-block">
                    <i class="fas fa-user-circle me-1"></i><?= e($user_display_name) ?>
                </span>
            </div>

        </div>
    </nav>

    <!-- MAIN INTERACTION AREA -->
    <div class="container-fluid py-3 px-3 px-xl-4">

        <!-- STATS & FILTER BAR -->
        <div class="row g-2 align-items-center mb-3">
            
            <!-- SUMMARY STATS -->
            <div class="col-12 col-xl-7">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="stat-pill">
                        <div class="badge bg-dark rounded-circle p-2"><i class="fas fa-utensils"></i></div>
                        <div>
                            <div class="text-muted" style="font-size: 11px;">Toplam Masa</div>
                            <div class="fw-bold mono-num fs-6"><?= $total_tables ?> Masa</div>
                        </div>
                    </div>
                    <div class="stat-pill border-primary bg-primary bg-opacity-10">
                        <div class="badge bg-primary rounded-circle p-2"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="text-primary fw-bold" style="font-size: 11px;">Dolu / Yeme-İçme</div>
                            <div class="fw-bold mono-num text-primary fs-6"><?= $dining_count ?> Masa</div>
                        </div>
                    </div>
                    <div class="stat-pill border-warning bg-warning bg-opacity-10">
                        <div class="badge bg-warning text-dark rounded-circle p-2"><i class="fas fa-receipt"></i></div>
                        <div>
                            <div class="text-warning-emphasis fw-bold" style="font-size: 11px;">Hesap İsteyen</div>
                            <div class="fw-bold mono-num text-dark fs-6"><?= $bill_count ?> Masa</div>
                        </div>
                    </div>
                    <div class="stat-pill border-success bg-success bg-opacity-10">
                        <div class="badge bg-success rounded-circle p-2"><i class="fas fa-check"></i></div>
                        <div>
                            <div class="text-success fw-bold" style="font-size: 11px;">Boş / Müsait</div>
                            <div class="fw-bold mono-num text-success fs-6"><?= $available_count ?> Masa</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEARCH & SECTION PILLS -->
            <div class="col-12 col-xl-5 text-xl-end">
                <div class="d-flex align-items-center justify-content-xl-end gap-2 flex-wrap">
                    <div class="input-group input-group-sm" style="max-width: 220px;">
                        <span class="input-group-text bg-white border-end-0 rounded-start-pill"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 rounded-end-pill" id="pos-table-search" placeholder="Masa Ara (örn: 4)" onkeyup="filterTablesBySearch()">
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-dark rounded-pill px-3 active" onclick="filterTablesByStatus('all', this)">Tümü</button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 ms-1" onclick="filterTablesByStatus('dining', this)">Dolu</button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 ms-1" onclick="filterTablesByStatus('bill_requested', this)">Hesap</button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 ms-1" onclick="filterTablesByStatus('available', this)">Boş</button>
                    </div>
                </div>
            </div>

        </div>

        <div class="row g-3">
            
            <!-- LEFT COLUMN: TABLE GRID & SECTION TABS -->
            <div class="col-12 col-lg-7 col-xl-8">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                    
                    <!-- SECTION SELECTOR TABS -->
                    <div class="card-header bg-white py-3 px-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 overflow-x-auto pb-1" id="section-filter-bar">
                            <button class="btn-section-filter active" onclick="filterTablesBySection('all', this)">
                                <i class="fas fa-layer-group me-1"></i> Tüm Bölümler
                            </button>
                            <?php foreach ($sections as $sec): ?>
                                <button class="btn-section-filter" onclick="filterTablesBySection('<?= e($sec) ?>', this)">
                                    <?= e($sec) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- TABLES GRID -->
                    <div class="card-body p-3 overflow-y-auto" style="max-height: calc(100vh - 230px);" id="tables-container">
                        <div class="row g-3" id="tables-grid">
                            <?php foreach ($tables as $t): ?>
                                <?php
                                $card_class = 'pos-table-' . $t['status'];
                                $has_adisyon = !empty($t['current_id_adisyons']);
                                $is_bill = $t['status'] === 'bill_requested';
                                $is_dining = in_array($t['status'], ['seated', 'dining']);
                                ?>
                                <div class="col-6 col-sm-6 col-md-4 col-xl-3 table-card-item" 
                                     data-status="<?= e($t['status']) ?>" 
                                     data-section="<?= e($t['section']) ?>" 
                                     data-number="<?= e($t['table_number']) ?>" 
                                     data-id="<?= $t['id'] ?>">
                                    <div class="pos-table-card <?= $card_class ?> p-3 h-100 d-flex flex-column justify-content-between" 
                                         onclick="selectPosTable(<?= $t['id'] ?>)">
                                        
                                        <div>
                                            <!-- TOP BADGE -->
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-dark fw-bold fs-6 px-2 py-1 rounded-3">
                                                    Masa <?= e($t['table_number']) ?>
                                                </span>
                                                <small class="text-muted fw-semibold" style="font-size: 11px;">
                                                    <i class="fas fa-map-marker-alt me-1 text-secondary"></i><?= e($t['section']) ?>
                                                </small>
                                            </div>

                                            <!-- STATUS BADGE -->
                                            <?php if ($is_bill): ?>
                                                <div class="badge bg-warning text-dark w-100 py-1 mb-2 fw-bold text-truncate">
                                                    <i class="fas fa-bell me-1"></i>HESAP İSTENDİ
                                                </div>
                                            <?php elseif ($is_dining): ?>
                                                <div class="badge bg-primary text-white w-100 py-1 mb-2 fw-bold text-truncate">
                                                    <i class="fas fa-utensils me-1"></i>Yemekte (<?= $t['minutes_seated'] ?> dk)
                                                </div>
                                            <?php elseif ($t['status'] === 'available'): ?>
                                                <div class="badge bg-success text-white w-100 py-1 mb-2 fw-bold text-truncate">
                                                    <i class="fas fa-check-circle me-1"></i>Boş & Müsait
                                                </div>
                                            <?php elseif ($t['status'] === 'cleaning'): ?>
                                                <div class="badge bg-secondary text-white w-100 py-1 mb-2 fw-bold text-truncate">
                                                    <i class="fas fa-broom me-1"></i>Temizlik
                                                </div>
                                            <?php elseif ($t['status'] === 'reserved'): ?>
                                                <div class="badge text-white w-100 py-1 mb-2 fw-bold text-truncate" style="background-color: #8b5cf6;">
                                                    <i class="fas fa-calendar-check me-1"></i>Rezerve
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- FOOTER ROW (CAPACITY & PRICE) -->
                                        <div class="mt-2 pt-2 border-top border-dark border-opacity-10 d-flex justify-content-between align-items-center">
                                            <small class="text-muted fw-semibold">
                                                <i class="fas fa-user-friends me-1"></i><?= $t['capacity'] ?> Kişi
                                            </small>
                                            <?php if ($has_adisyon): ?>
                                                <strong class="text-primary fs-6 mono-num">
                                                    <?= number_format((float) ($t['adisyon_total'] ?? 0), 2) ?> ₺
                                                </strong>
                                            <?php else: ?>
                                                <span class="text-muted small"><i class="fas fa-plus-circle text-success"></i> Aç</span>
                                            <?php endif; ?>
                                        </div>

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </div>

            <!-- RIGHT COLUMN: ACTIVE ADISYON, CUSTOMER, LOYALTY & PAYMENT REGISTER -->
            <div class="col-12 col-lg-5 col-xl-4">
                <div class="adisyon-box h-100 d-flex flex-column justify-content-between" id="pos-register-panel">
                    
                    <!-- EMPTY STANDBY STATE -->
                    <div id="no-table-selected" class="p-5 text-center my-auto">
                        <div class="mb-3 text-secondary opacity-50">
                            <i class="fas fa-cash-register fa-4x"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">İşlem Yapmak İçin Bir Masa Seçin</h5>
                        <p class="text-muted small px-3">
                            Soldaki salondan bir masaya tıkladığınızda; adisyon detayları, sipariş ekleme, sadakat indirimi ve ödeme seçenekleri burada anında açılacaktır.
                        </p>
                        <div class="mt-4 pt-3 border-top">
                            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill small">
                                <i class="fas fa-bolt text-warning me-1"></i> Hızlı ve Güvenli Kasa Satışı
                            </span>
                        </div>
                    </div>

                    <!-- ACTIVE TABLE REGISTER INTERFACE -->
                    <div id="active-table-interface" style="display: none;" class="p-3 d-flex flex-column h-100">
                        
                        <!-- HEADER WITH ACTIONS -->
                        <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                            <div>
                                <span class="badge bg-primary fs-5 px-3 py-1 rounded-pill" id="selected-table-badge">Masa --</span>
                                <small class="text-muted ms-2 fw-semibold" id="selected-table-section">Ana Salon</small>
                            </div>
                            <div class="d-flex gap-1">
                                <button class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-sm" onclick="openQuickAddModal()">
                                    <i class="fas fa-plus me-1"></i> Ürün Ekle
                                </button>
                                <button class="btn btn-outline-dark btn-sm rounded-pill px-2" id="btn-print-slip" onclick="printAdisyonSlip()" title="Fiş Yazdır">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </div>

                        <!-- CUSTOMER & LOYALTY CARD -->
                        <div class="card bg-light border-0 rounded-4 p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold small text-dark"><i class="fas fa-gem text-warning me-1"></i>Müşteri & Sadakat Arama</span>
                                <span class="badge bg-warning text-dark fw-bold" id="customer-points-badge" style="display: none;">0 Puan</span>
                            </div>
                            <div class="input-group input-group-sm">
                                <input type="tel" class="form-control rounded-start-pill" id="pos-customer-phone" placeholder="Telefon (örn: 0555...)">
                                <button class="btn btn-dark rounded-end-pill px-3" type="button" onclick="lookupCustomerPhone()">
                                    <i class="fas fa-search me-1"></i> Bul
                                </button>
                            </div>
                            
                            <!-- CUSTOMER INFO ROW (ON MATCH) -->
                            <div id="customer-info-box" style="display: none;" class="small mt-2 p-2 bg-white rounded-3 border">
                                <div class="d-flex justify-content-between align-items-center fw-bold">
                                    <span id="cust-name-text">Müşteri Adı</span>
                                    <span class="text-primary mono-num" id="cust-phone-text">0555...</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center text-muted mt-1" style="font-size: 11px;">
                                    <span>Bakiye: <strong class="text-warning mono-num" id="cust-balance-text">0 Puan</strong></span>
                                    <span id="cust-membership-badge" class="badge bg-success">VIP</span>
                                </div>
                                <div class="mt-2 pt-1 border-top">
                                    <button class="btn btn-warning btn-sm w-100 py-1 fw-bold text-dark rounded-pill" onclick="applyLoyaltyPointsPrompt()">
                                        <i class="fas fa-gift me-1"></i> Puan İndirimi Kullan
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ADISYON ITEMS CONTAINER -->
                        <div class="card border rounded-4 p-2 mb-2 bg-light flex-grow-1 overflow-y-auto" style="min-height: 180px; max-height: 280px;" id="adisyon-items-container">
                            <!-- Items injected dynamically -->
                        </div>

                        <!-- TOTALS CALCULATION BOX -->
                        <div class="p-3 bg-light rounded-4 mb-2 border">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Ara Toplam:</span>
                                <span class="mono-num fw-semibold" id="adisyon-subtotal-val">0.00 ₺</span>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>KDV (%10):</span>
                                <span class="mono-num fw-semibold" id="adisyon-tax-val">0.00 ₺</span>
                            </div>
                            <div class="d-flex justify-content-between small text-success fw-bold mb-1" id="membership-disc-row" style="display: none;">
                                <span><i class="fas fa-tag me-1"></i>Üyelik İndirimi:</span>
                                <span class="mono-num" id="adisyon-membership-disc-val">-0.00 ₺</span>
                            </div>
                            <div class="d-flex justify-content-between small text-warning-emphasis fw-bold mb-1" id="loyalty-disc-row" style="display: none;">
                                <span><i class="fas fa-gem me-1"></i>Sadakat İndirimi:</span>
                                <span class="mono-num" id="adisyon-loyalty-disc-val">-0.00 ₺</span>
                            </div>
                            <div class="d-flex justify-content-between fs-4 fw-bolder text-dark pt-2 border-top">
                                <span>Ödenecek Tutar:</span>
                                <span class="text-primary mono-num" id="adisyon-total-val">0.00 ₺</span>
                            </div>
                        </div>

                        <!-- TOUCH PAYMENT BUTTONS -->
                        <div class="row g-2 mb-2">
                            <div class="col-12">
                                <button class="btn btn-lg w-100 shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; font-weight: 700; padding: 16px; border-radius: 14px; font-size: 1.1rem;" onclick="openSplitPayment()">
                                    <i class="fas fa-cash-register me-2"></i> Tahsilat Al & Parçalı Ödeme
                                </button>
                            </div>
                        </div>

                        <!-- CLOSE TABLE ACTION -->
                        <button class="btn btn-dark w-100 py-2 rounded-pill fw-bold" onclick="closeActiveTable()">
                            <i class="fas fa-check-circle me-1 text-success"></i> Masayı Kapat & Temizliğe Al
                        </button>

                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- QUICK ADD ITEM MODAL -->
    <div class="modal fade" id="quickAddModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom py-3 px-4">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fas fa-utensils text-primary me-2"></i>Masaya Ürün Ekle
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 rounded-end-pill py-2" id="quick-item-search" placeholder="Hızlı ürün ara (örn: pizza, kahve, kola)..." onkeyup="filterQuickItems()">
                    </div>
                    
                    <div class="row g-2 overflow-y-auto" style="max-height: 420px;" id="quick-items-list">
                        <?php foreach ($menu_items as $mi): ?>
                            <div class="col-6 col-md-4 quick-item-col" data-name="<?= strtolower(e($mi['name'])) ?>">
                                <div class="card p-3 border rounded-3 h-100 d-flex flex-column justify-content-between shadow-sm cursor-pointer hover-card" 
                                     style="cursor: pointer;"
                                     onclick="addItemToAdisyon(<?= $mi['id'] ?>, '<?= addslashes($mi['name']) ?>', <?= (float) $mi['price'] ?>, '<?= $mi['station'] ?>')">
                                    <div class="fw-bold text-dark"><?= e($mi['name']) ?></div>
                                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                        <span class="text-primary fw-bolder fs-6 mono-num"><?= number_format((float) $mi['price'], 2) ?> ₺</span>
                                        <span class="badge bg-secondary text-uppercase small" style="font-size: 10px;"><?= strtoupper($mi['station']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TOAST CONTAINER -->
    <div id="pos-toast-container"></div>

    <script>
        let currentTableId = null;
        let currentAdisyonId = null;
        let currentCustomerId = null;
        let currentTableData = null;

        // Clock
        setInterval(() => {
            const now = new Date();
            document.getElementById('pos-clock').innerText = now.toLocaleTimeString('tr-TR');
        }, 1000);

        // Toast Helper
        function showPosToast(message, type = 'info') {
            const container = document.getElementById('pos-toast-container');
            const toastEl = document.createElement('div');
            toastEl.className = `alert alert-${type} shadow-lg rounded-4 py-2 px-3 mb-2 d-flex align-items-center gap-2 fade show`;
            toastEl.style.minWidth = '280px';
            toastEl.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : (type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle')}"></i>
                <div class="small fw-bold">${message}</div>
            `;
            container.appendChild(toastEl);
            setTimeout(() => {
                toastEl.classList.remove('show');
                setTimeout(() => toastEl.remove(), 250);
            }, 3000);
        }

        // Filter Tables by Status
        function filterTablesByStatus(status, btn) {
            document.querySelectorAll('#pos-table-search, .btn-group button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            document.querySelectorAll('.table-card-item').forEach(c => {
                const st = c.getAttribute('data-status');
                if (status === 'all' || st === status) {
                    c.style.display = 'block';
                } else {
                    c.style.display = 'none';
                }
            });
        }

        // Filter Tables by Section
        function filterTablesBySection(section, btn) {
            document.querySelectorAll('#section-filter-bar button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            document.querySelectorAll('.table-card-item').forEach(c => {
                const sec = c.getAttribute('data-section');
                if (section === 'all' || sec === section) {
                    c.style.display = 'block';
                } else {
                    c.style.display = 'none';
                }
            });
        }

        // Filter Tables by Number Search
        function filterTablesBySearch() {
            const q = document.getElementById('pos-table-search').value.toLowerCase().trim();
            document.querySelectorAll('.table-card-item').forEach(c => {
                const num = (c.getAttribute('data-number') || '').toLowerCase();
                c.style.display = (!q || num.includes(q)) ? 'block' : 'none';
            });
        }

        // Select Table & Load Adisyon
        async function selectPosTable(tableId) {
            currentTableId = tableId;
            document.querySelectorAll('.pos-table-card').forEach(c => c.classList.remove('active'));
            const card = document.querySelector(`.table-card-item[data-id="${tableId}"] .pos-table-card`);
            if (card) card.classList.add('active');

            try {
                const res = await fetch(`<?= site_url('restaurant/api/table_experience') ?>?table_id=${tableId}`);
                if (!res.ok) {
                    showPosToast('Masa bilgisi alınırken sunucu hatası (' + res.status + ')', 'danger');
                    return;
                }
                const data = await res.json();

                if (data.status === 'success' && data.data) {
                    currentTableData = data.data;
                    renderRegisterPanel(data.data);
                } else {
                    showPosToast(data.message || 'Masa bilgisi alınamadı.', 'warning');
                }
            } catch (err) {
                console.error(err);
                showPosToast('Ağ hatası: ' + err.message, 'danger');
            }
        }

        // Render Register Panel
        function renderRegisterPanel(data) {
            document.getElementById('no-table-selected').style.display = 'none';
            document.getElementById('active-table-interface').style.display = 'flex';

            document.getElementById('selected-table-badge').innerText = `Masa ${data.table.table_number}`;
            document.getElementById('selected-table-section').innerText = data.table.section || 'Salon';

            const adisyon = data.adisyon;
            const itemsContainer = document.getElementById('adisyon-items-container');
            itemsContainer.innerHTML = '';

            // Handle Customer
            if (data.customer) {
                currentCustomerId = data.customer.id;
                document.getElementById('cust-name-text').innerText = `${data.customer.first_name || ''} ${data.customer.last_name || ''}`.trim() || 'Kayıtlı Müşteri';
                document.getElementById('cust-phone-text').innerText = data.customer.phone_number || '';
                document.getElementById('cust-balance-text').innerText = `${data.customer.loyalty_points || 0} Puan`;
                document.getElementById('customer-points-badge').innerText = `${data.customer.loyalty_points || 0} Puan`;
                document.getElementById('customer-points-badge').style.display = 'inline-block';
                document.getElementById('customer-info-box').style.display = 'block';
            } else {
                currentCustomerId = null;
                document.getElementById('customer-points-badge').style.display = 'none';
                document.getElementById('customer-info-box').style.display = 'none';
            }

            if (adisyon && adisyon.items && adisyon.items.length > 0) {
                currentAdisyonId = adisyon.id;

                adisyon.items.forEach(item => {
                    const row = document.createElement('div');
                    row.className = 'd-flex justify-content-between align-items-center py-2 px-2 border-bottom order-row';
                    row.innerHTML = `
                        <div>
                            <span class="badge bg-dark rounded-pill px-2 py-1 me-1 mono-num">${parseInt(item.quantity)}x</span> 
                            <strong class="text-dark">${item.name}</strong>
                            ${item.notes ? `<div class="text-muted fst-italic" style="font-size: 11px;"><i class="fas fa-sticky-note me-1"></i>${item.notes}</div>` : ''}
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold mono-num text-dark">${parseFloat(item.total_amount).toFixed(2)} ₺</span>
                            <button class="btn btn-sm btn-outline-danger border-0 p-1" onclick="removeItemFromAdisyon(${item.id})" title="Sil">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    `;
                    itemsContainer.appendChild(row);
                });

                document.getElementById('adisyon-subtotal-val').innerText = parseFloat(adisyon.subtotal).toFixed(2) + ' ₺';
                document.getElementById('adisyon-tax-val').innerText = parseFloat(adisyon.tax_amount).toFixed(2) + ' ₺';

                if (parseFloat(adisyon.membership_discount) > 0) {
                    document.getElementById('membership-disc-row').style.display = 'flex';
                    document.getElementById('adisyon-membership-disc-val').innerText = '-' + parseFloat(adisyon.membership_discount).toFixed(2) + ' ₺';
                } else {
                    document.getElementById('membership-disc-row').style.display = 'none';
                }

                if (parseFloat(adisyon.loyalty_discount) > 0) {
                    document.getElementById('loyalty-disc-row').style.display = 'flex';
                    document.getElementById('adisyon-loyalty-disc-val').innerText = '-' + parseFloat(adisyon.loyalty_discount).toFixed(2) + ' ₺';
                } else {
                    document.getElementById('loyalty-disc-row').style.display = 'none';
                }

                document.getElementById('adisyon-total-val').innerText = parseFloat(adisyon.total_amount).toFixed(2) + ' ₺';
            } else {
                currentAdisyonId = null;
                itemsContainer.innerHTML = '<div class="text-center text-muted small my-auto py-4"><i class="fas fa-utensils fa-2x mb-2 text-secondary opacity-50"></i><p class="mb-0">Masada açık sipariş yok.<br><strong>+ Ürün Ekle</strong> butonundan sipariş girebilirsiniz.</p></div>';
                document.getElementById('adisyon-subtotal-val').innerText = '0.00 ₺';
                document.getElementById('adisyon-tax-val').innerText = '0.00 ₺';
                document.getElementById('membership-disc-row').style.display = 'none';
                document.getElementById('loyalty-disc-row').style.display = 'none';
                document.getElementById('adisyon-total-val').innerText = '0.00 ₺';
            }
        }

        // Customer Lookup
        async function lookupCustomerPhone() {
            const phone = document.getElementById('pos-customer-phone').value.trim();
            if (!phone) return;

            try {
                const res = await fetch(`<?= site_url('restaurant/api/customer_lookup') ?>?phone=${encodeURIComponent(phone)}`);
                const data = await res.json();

                if (data.status === 'success' && data.customer) {
                    const c = data.customer;
                    currentCustomerId = c.id;
                    document.getElementById('cust-name-text').innerText = c.name;
                    document.getElementById('cust-phone-text').innerText = c.phone;
                    document.getElementById('cust-balance-text').innerText = `${c.loyalty_points} Puan (${(c.loyalty_points / 10).toFixed(2)} ₺)`;

                    if (c.membership) {
                        document.getElementById('cust-membership-badge').style.display = 'inline-block';
                        document.getElementById('cust-membership-badge').innerText = `${c.membership.plan_name} (%${c.membership.discount_percent})`;
                    } else {
                        document.getElementById('cust-membership-badge').style.display = 'none';
                    }

                    document.getElementById('customer-info-box').style.display = 'block';
                    showPosToast('Müşteri başarıyla eşleştirildi!', 'success');
                } else {
                    showPosToast('Müşteri bulunamadı.', 'warning');
                }
            } catch (err) {
                showPosToast('Ağ hatası: ' + err.message, 'danger');
            }
        }

        // Apply Loyalty Points
        async function applyLoyaltyPointsPrompt() {
            if (!currentAdisyonId) {
                showPosToast('Lütfen önce masaya bir ürün ekleyin.', 'warning');
                return;
            }

            const pts = prompt('Kaç puan indirim olarak düşülsün? (100 Puan = 10 TL):', '100');
            if (!pts || isNaN(pts) || parseInt(pts) <= 0) return;

            try {
                const res = await fetch('<?= site_url('restaurant/api/redeem_loyalty') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ adisyon_id: currentAdisyonId, points: parseInt(pts), customer_id: currentCustomerId })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showPosToast(`${data.points_redeemed} puan düşüldü! ${data.discount_applied} ₺ indirim uygulandı.`, 'success');
                    selectPosTable(currentTableId);
                } else {
                    showPosToast('Hata: ' + (data.message || 'Puan düşülemedi.'), 'danger');
                }
            } catch (err) {
                showPosToast('Ağ hatası: ' + err.message, 'danger');
            }
        }

        // Open Quick Add Modal
        function openQuickAddModal() {
            if (!currentTableId) {
                showPosToast('Lütfen önce bir masa seçin.', 'warning');
                return;
            }
            const modal = new bootstrap.Modal(document.getElementById('quickAddModal'));
            modal.show();
        }

        // Filter Quick Items
        function filterQuickItems() {
            const q = document.getElementById('quick-item-search').value.toLowerCase().trim();
            document.querySelectorAll('.quick-item-col').forEach(c => {
                const name = c.getAttribute('data-name');
                c.style.display = (!q || name.includes(q)) ? 'block' : 'none';
            });
        }

        // Add Item to Adisyon
        async function addItemToAdisyon(itemId, itemName, itemPrice, station) {
            if (!currentTableId) return;

            try {
                const res = await fetch('<?= site_url('restaurant/api/self_order') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        table_id: currentTableId,
                        items: [{
                            id: itemId,
                            name: itemName,
                            price: itemPrice,
                            quantity: 1,
                            station: station
                        }]
                    })
                });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('quickAddModal')).hide();
                    showPosToast(`${itemName} eklendi!`, 'success');
                    selectPosTable(currentTableId);
                } else {
                    showPosToast('Hata: ' + (data.message || 'Ürün eklenemedi.'), 'danger');
                }
            } catch (err) {
                showPosToast('Ağ hatası: ' + err.message, 'danger');
            }
        }

        // Remove Item
        async function removeItemFromAdisyon(itemId) {
            if (!confirm('Bu ürünü adisyondan silmek istediğinize emin misiniz?')) return;
            try {
                const res = await fetch(`<?= site_url('adisyons/remove_item/') ?>${itemId}`, { method: 'POST' });
                if (res.ok) {
                    showPosToast('Ürün adisyondan kaldırıldı.', 'info');
                    selectPosTable(currentTableId);
                }
            } catch (e) {
                showPosToast('Hata: ' + e.message, 'danger');
            }
        }

        // Record Payment
        async function recordPayment(method) {
            if (!currentAdisyonId) {
                showPosToast('Ödeme yapılacak açık bir adisyon yok.', 'warning');
                return;
            }

            const totalPayable = parseFloat(document.getElementById('adisyon-total-val').innerText) || 0;
            const amount = prompt(`Tahsil edilecek tutar (${method.toUpperCase()}):`, totalPayable.toFixed(2));
            if (!amount || isNaN(amount) || parseFloat(amount) <= 0) return;

            try {
                const formData = new FormData();
                formData.append('id_adisyons', currentAdisyonId);
                formData.append('amount', parseFloat(amount));
                formData.append('payment_method', method);

                const res = await fetch('<?= site_url('adisyons/pay') ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.status === 'success') {
                    showPosToast('Ödeme başarıyla kaydedildi!', 'success');
                    selectPosTable(currentTableId);
                } else {
                    showPosToast('Ödeme hatası: ' + (data.message || 'İşlem başarısız.'), 'danger');
                }
            } catch (err) {
                showPosToast('Ağ hatası: ' + err.message, 'danger');
            }
        }

        // Close Table
        async function closeActiveTable() {
            if (!currentTableId) return;
            if (!confirm('Masayı kapatmak ve temizliğe almak istediğinize emin misiniz?')) return;

            try {
                const res = await fetch('<?= site_url('restaurant/api/close_table') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ table_id: currentTableId })
                });
                const data = await res.json();

                if (data.status === 'success') {
                    showPosToast(data.message || 'Masa başarıyla kapatıldı.', 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    showPosToast('Hata: ' + (data.message || 'Masa kapatılamadı.'), 'danger');
                }
            } catch (err) {
                showPosToast('Ağ hatası: ' + err.message, 'danger');
            }
        }

        // Open Split Payment Modal
        function openSplitPayment() {
            if (!currentAdisyonId) {
                showPosToast('Ödeme yapılacak açık bir adisyon yok.', 'warning');
                return;
            }
            // Parse total ignoring formatting, adisyon-total-val innerText might have ' ₺'
            let rawTotal = document.getElementById('adisyon-total-val').innerText;
            rawTotal = rawTotal.replace(/[^0-9.-]+/g,"");
            const totalPayable = parseFloat(rawTotal) || 0;
            
            SplitPaymentModal.open('adisyon', currentAdisyonId, totalPayable, []);
            SplitPaymentModal.onFinalized(function(data) {
                showPosToast('Tahsilat başarıyla tamamlandı.', 'success');
                selectPosTable(currentTableId); // Refresh UI
            });
        }

        // Print Slip (80mm & 58mm ESC-POS Ready)
        function printAdisyonSlip() {
            if (!currentAdisyonId) {
                showPosToast('Yazdırılacak adisyon yok.', 'warning');
                return;
            }

            const items = [];
            document.querySelectorAll('#adisyon-items-list tr').forEach(tr => {
                const name = tr.querySelector('.item-name')?.innerText || tr.cells[0]?.innerText;
                const qty = parseInt(tr.querySelector('.item-qty')?.innerText || tr.cells[1]?.innerText) || 1;
                const price = parseFloat(tr.querySelector('.item-price')?.innerText?.replace(/[^0-9.-]+/g, "") || tr.cells[2]?.innerText?.replace(/[^0-9.-]+/g, "")) || 0;
                if (name && qty) {
                    items.push({ name: name.trim(), qty: qty, price: price });
                }
            });

            const rawTotal = document.getElementById('adisyon-total-val').innerText.replace(/[^0-9.-]+/g, "");
            const total = parseFloat(rawTotal) || 0;
            const tableNum = document.getElementById('pos-active-table-title')?.innerText?.replace('Masa ', '') || currentTableId;

            ThermalReceipt.open({
                venueName: 'BooKi Restoran',
                tableNumber: tableNum,
                adisyonId: currentAdisyonId,
                total: total,
                subtotal: total,
                items: items
            });
        }

        // Fullscreen Toggle
        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }
    </script>
    
    <!-- Load Split Payment Modal Component -->
    <?php $this->load->view('components/split_payment_modal'); ?>
    <!-- Load Thermal Receipt Modal Component -->
    <?php $this->load->view('components/thermal_receipt_modal'); ?>
</body>
</html>
