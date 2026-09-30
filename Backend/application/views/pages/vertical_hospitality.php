<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * World-Class Hospitality & Hotel Property Management System (PMS) View.
 *
 * Inspired by QloApps, hotel-mgmt-system, HotinGo, Django HMS, and HotelDruid.
 *
 * @var array $rooms
 * @var array $room_types
 * @var array $rate_plans
 * @var array $tape_chart
 * @var array $housekeeping_board
 * @var array $maintenance_tickets
 * @var array $kbs_declarations
 * @var array $night_audits
 * @var array $kpi_stats
 * @var array $folios
 * @var array $charges
 * @var array $customers
 * @var array $preferences
 * @var array $stations
 */

$customers = $customers ?? [];
$stations = $stations ?? [];
$room_types = $room_types ?? [];
$rate_plans = $rate_plans ?? [];
$tape_chart = $tape_chart ?? ['calendar_days' => [], 'room_rows' => []];
$housekeeping_board = $housekeeping_board ?? [];
$maintenance_tickets = $maintenance_tickets ?? [];
$kbs_declarations = $kbs_declarations ?? [];
$night_audits = $night_audits ?? [];
$folios = $folios ?? [];
$charges = $charges ?? [];
$preferences = $preferences ?? [];

// Default sample rooms if empty
if (empty($rooms)) {
    $rooms = [
        [
            'id' => 101,
            'name' => 'Oda 101 - Standart Çift Kişilik',
            'room_number' => '101',
            'type' => 'Standart Çift Kişilik',
            'base_price' => 2200.00,
            'status' => 'clean',
            'floor' => '1. Kat',
            'guest_name' => '',
            'checkout_date' => '',
            'door_lock_code' => '482910',
            'notes' => 'Bahçe manzaralı, çift kişilik geniş yatak.'
        ],
        [
            'id' => 102,
            'name' => 'Oda 102 - Superior King',
            'room_number' => '102',
            'type' => 'Superior King',
            'base_price' => 3800.00,
            'status' => 'occupied',
            'floor' => '1. Kat',
            'guest_name' => 'Thomas Müller',
            'guest_id' => !empty($customers[0]['id']) ? $customers[0]['id'] : 1,
            'checkout_date' => date('d.m.Y', strtotime('+2 days')) . ' 12:00',
            'door_lock_code' => '918234',
            'notes' => 'Havalimanı VIP transferi talep edildi, late check-out opsiyonlu.'
        ],
        [
            'id' => 103,
            'name' => 'Oda 103 - Bahçe Manzaralı Aile',
            'room_number' => '103',
            'type' => 'Standart Aile',
            'base_price' => 2800.00,
            'status' => 'dirty',
            'floor' => '1. Kat',
            'guest_name' => 'Mehmet Demir',
            'checkout_date' => date('d.m.Y') . ' 10:30 (Check-out Yapıldı)',
            'door_lock_code' => '716253',
            'notes' => 'Check-out tamamlandı. Çarşaflar ve minibar yenilenecek.'
        ],
        [
            'id' => 201,
            'name' => 'Suit 201 - Jakuzili Deluxe',
            'room_number' => '201',
            'type' => 'Deluxe Teras Suite',
            'base_price' => 4500.00,
            'status' => 'occupied',
            'floor' => '2. Kat',
            'guest_name' => 'Elif Kaya',
            'guest_id' => !empty($customers[1]['id']) ? $customers[1]['id'] : 2,
            'checkout_date' => date('d.m.Y', strtotime('+1 day')) . ' 11:30',
            'door_lock_code' => '837192',
            'notes' => 'Yıldönümü süslemesi yapıldı. Kuştüyü yastık tercih ediyor.'
        ],
        [
            'id' => 202,
            'name' => 'Suit 202 - Balayı Suiti',
            'room_number' => '202',
            'type' => 'Balayı Suiti',
            'base_price' => 5200.00,
            'status' => 'clean',
            'floor' => '2. Kat',
            'guest_name' => '',
            'checkout_date' => '',
            'door_lock_code' => '625140',
            'notes' => 'Şömineli, panoramik teraslı, ikram sepeti hazır.'
        ],
        [
            'id' => 301,
            'name' => 'Bungalov 301 - Doğa & Havuz',
            'room_number' => '301',
            'type' => 'Bungalov Doğa',
            'base_price' => 4800.00,
            'status' => 'occupied',
            'floor' => 'Bahçe / Havuz Başı',
            'guest_name' => 'Caner Yılmaz',
            'guest_id' => !empty($customers[2]['id']) ? $customers[2]['id'] : 3,
            'checkout_date' => date('d.m.Y', strtotime('+3 days')) . ' 12:00',
            'door_lock_code' => '514230',
            'notes' => 'Evcil hayvan ile konaklıyor. Glutensiz kahvaltı istendi.'
        ],
        [
            'id' => 302,
            'name' => 'Bungalov 302 - Ahşap Jakuzili',
            'room_number' => '302',
            'type' => 'Ahşap Jakuzili',
            'base_price' => 4800.00,
            'status' => 'maintenance',
            'floor' => 'Bahçe / Sessiz Cephe',
            'guest_name' => '',
            'checkout_date' => '',
            'door_lock_code' => '994821',
            'notes' => 'Klima filtre değişimi ve jakuzi bakımı yapılıyor.'
        ],
        [
            'id' => 401,
            'name' => 'Suit 401 - Presidential Kral',
            'room_number' => '401',
            'type' => 'Presidential Kral Dairesi',
            'base_price' => 8900.00,
            'status' => 'clean',
            'floor' => '4. Kat Teras',
            'guest_name' => '',
            'checkout_date' => '',
            'door_lock_code' => '102938',
            'notes' => 'Panoramik deniz manzaralı, VIP havalimanı transferi dahil.'
        ],
    ];
}

// KPI Statistics
$total_rooms = count($rooms);
$clean_rooms = 0;
$occupied_rooms = 0;
$dirty_rooms = 0;
$maintenance_rooms = 0;
$total_room_revenue = 0.00;

foreach ($rooms as $r) {
    $st = strtolower(trim($r['status'] ?? 'clean'));
    if ($st === 'clean' || $st === 'inspected' || $st === 'available') {
        $clean_rooms++;
    } elseif ($st === 'occupied') {
        $occupied_rooms++;
        $total_room_revenue += (float) ($r['base_price'] ?? 2000.00);
    } elseif ($st === 'dirty' || $st === 'cleaning') {
        $dirty_rooms++;
    } elseif ($st === 'maintenance') {
        $maintenance_rooms++;
    }
}

$saleable_rooms = max(1, $total_rooms - $maintenance_rooms);
$occupancy_rate = round(($occupied_rooms / $saleable_rooms) * 100, 1);
$adr = $occupied_rooms > 0 ? round($total_room_revenue / $occupied_rooms, 2) : 0.00;
$revpar = $total_rooms > 0 ? round($total_room_revenue / $total_rooms, 2) : 0.00;

$total_folio_amount = 0.00;
foreach ($charges as $c) {
    $total_folio_amount += (float) ($c['amount'] ?? 0.00);
}
?>

<div class="hospitality-vertical-wrapper bg-light min-vh-100">
    <div class="container-fluid py-4 px-md-4">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary px-3 py-2 fs-6 rounded-pill shadow-sm"><i class="fas fa-hotel me-1"></i> BooKi PMS Enterprise</span>
                    <span class="badge bg-dark bg-opacity-75 text-white px-2 py-1 small"><i class="fas fa-shield-alt text-success me-1"></i> KBS & %2 Konaklama Vergisi Uyumlu</span>
                </div>
                <h1 class="h3 fw-bold mt-2 mb-1 text-dark">Otel, Resort & Butik Konaklama Yönetim Sistemi</h1>
                <p class="text-muted small mb-0">Ön büro, 14-30 günlük Gantt Tape Chart, hızlı check-in/out, folyo muhasebesi, kat hizmetleri ve gece denetimi (Night Audit).</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-warning text-dark fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-apply-template">
                    <i class="fas fa-magic me-1"></i> Tesis & Oda Şablonu Uygula
                </button>
                <button class="btn btn-outline-dark fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-kbs-export">
                    <i class="fas fa-file-export text-danger me-1"></i> KBS Bildirimi
                </button>
                <button class="btn btn-outline-primary fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-night-audit">
                    <i class="fas fa-moon text-warning me-1"></i> Gece Denetimi (Night Audit)
                </button>
                <button class="btn btn-success fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-express-checkin">
                    <i class="fas fa-key me-1"></i> Hızlı Check-In
                </button>
                <button class="btn btn-primary fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-add-charge">
                    <i class="fas fa-file-invoice-dollar me-1"></i> Folyo Harcaması Ekle
                </button>
            </div>
        </div>

        <!-- STATS / KPI CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-2">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold">Toplam Oda</div>
                            <h3 class="fw-bold mb-0 text-dark" id="stat-total-rooms"><?= $total_rooms ?></h3>
                            <small class="text-muted"><?= $total_rooms * 2 ?> Yatak Kapasite</small>
                        </div>
                        <div class="stat-icon-bubble bg-light text-primary">
                            <i class="fas fa-door-closed fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold">Temiz & Müsait</div>
                            <h3 class="fw-bold mb-0 text-success" id="stat-clean-rooms"><?= $clean_rooms ?></h3>
                            <small class="text-success"><i class="fas fa-check-circle me-1"></i>Satışa Hazır</small>
                        </div>
                        <div class="stat-icon-bubble bg-success-subtle text-success">
                            <i class="fas fa-bed fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold">Dolu Odalar</div>
                            <h3 class="fw-bold mb-0 text-danger" id="stat-occupied-rooms"><?= $occupied_rooms ?></h3>
                            <small class="text-danger fw-bold">%<?= $occupancy_rate ?> Doluluk</small>
                        </div>
                        <div class="stat-icon-bubble bg-danger-subtle text-danger">
                            <i class="fas fa-user-check fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold">Temizlik Bekliyor</div>
                            <h3 class="fw-bold mb-0 text-warning" id="stat-dirty-rooms"><?= $dirty_rooms ?></h3>
                            <small class="text-warning">Kat Hizmetleri</small>
                        </div>
                        <div class="stat-icon-bubble bg-warning-subtle text-warning">
                            <i class="fas fa-broom fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold">ADR / RevPAR</div>
                            <h4 class="fw-bold mb-0 text-info">₺<?= number_format($adr, 0) ?></h4>
                            <small class="text-muted">RevPAR: ₺<?= number_format($revpar, 0) ?></small>
                        </div>
                        <div class="stat-icon-bubble bg-info-subtle text-info">
                            <i class="fas fa-chart-line fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-2">
                <div class="card shadow-sm border-0 h-100 bg-primary text-white">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-white-50 small fw-semibold">Açık Folyo Bakiyesi</div>
                            <h4 class="fw-bold mb-0 text-white" id="stat-folio-total">₺<?= number_format($total_folio_amount, 2) ?></h4>
                            <small class="text-white-50"><?= count($charges) ?> Kalem Ekstra</small>
                        </div>
                        <div class="stat-icon-bubble bg-white bg-opacity-25 text-white">
                            <i class="fas fa-receipt fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN PMS NAVIGATION TABS -->
        <ul class="nav nav-pills mb-3 bg-white p-2 rounded-3 shadow-sm border flex-wrap" id="pmsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold" id="tab-matrix-btn" data-bs-toggle="pill" data-bs-target="#tab-matrix" type="button" role="tab">
                    <i class="fas fa-layer-group me-2 text-primary"></i>Oda & Kat Şablonu (Blueprint)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-tape-btn" data-bs-toggle="pill" data-bs-target="#tab-tape" type="button" role="tab">
                    <i class="fas fa-stream me-2 text-primary"></i>Gantt Tape Chart (14 Gün)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-folios-btn" data-bs-toggle="pill" data-bs-target="#tab-folios" type="button" role="tab">
                    <i class="fas fa-file-invoice-dollar me-2 text-success"></i>Folyo & Minibar
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-hk-btn" data-bs-toggle="pill" data-bs-target="#tab-hk" type="button" role="tab">
                    <i class="fas fa-broom me-2 text-warning"></i>Kat Hizmetleri (HK)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-maint-btn" data-bs-toggle="pill" data-bs-target="#tab-maint" type="button" role="tab">
                    <i class="fas fa-tools me-2 text-danger"></i>Teknik Servis & Arıza
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-rates-btn" data-bs-toggle="pill" data-bs-target="#tab-rates" type="button" role="tab">
                    <i class="fas fa-tags me-2 text-info"></i>Oda Tipleri & OTA iCal
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-kbs-btn" data-bs-toggle="pill" data-bs-target="#tab-kbs" type="button" role="tab">
                    <i class="fas fa-id-card me-2 text-secondary"></i>KBS Kimlik Bildirimi
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-audits-btn" data-bs-toggle="pill" data-bs-target="#tab-audits" type="button" role="tab">
                    <i class="fas fa-history me-2 text-dark"></i>Night Audit Raporları
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pmsTabsContent">
            <!-- 1. ROOM STATUS & BLUEPRINT MATRIX TAB -->
            <div class="tab-pane fade show active" id="tab-matrix" role="tabpanel">
                <?php
                    // Group rooms by floor / zone
                    $floor_groups = [];
                    foreach ($rooms as $r) {
                        $fl = !empty($r['floor']) ? $r['floor'] : 'Ana Bina (Zemin Kat)';
                        $floor_groups[$fl][] = $r;
                    }
                ?>
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-bold mb-0"><i class="fas fa-layer-group text-primary me-2"></i>Oda & Kat Şablonu Panosu (Mimari Yerleşim)</h5>
                            <span class="badge bg-secondary" id="filtered-room-count"><?= $total_rooms ?> Oda</span>
                            <span class="badge bg-light text-dark border"><?= count($floor_groups) ?> Bölge / Kat</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <!-- VIEW MODE TOGGLE (FLOORS BLUEPRINT vs COMPACT GRID) -->
                            <div class="btn-group btn-group-sm" role="group" id="blueprint-view-toggle">
                                <button type="button" class="btn btn-primary active" id="btn-mode-floors" title="Kat ve Bloklara Göre Mimari Şablon Görünümü">
                                    <i class="fas fa-building me-1"></i>Kat & Blok Şablonu
                                </button>
                                <button type="button" class="btn btn-outline-primary" id="btn-mode-grid" title="Tüm Odalar Kompakt Izgara">
                                    <i class="fas fa-th me-1"></i>Kompakt Izgara
                                </button>
                            </div>

                            <div class="btn-group btn-group-sm" role="group" id="room-filter-group">
                                <button type="button" class="btn btn-outline-primary active btn-room-filter" data-filter="all">Tümü (<?= $total_rooms ?>)</button>
                                <button type="button" class="btn btn-outline-success btn-room-filter" data-filter="clean">Temiz & Müsait (<?= $clean_rooms ?>)</button>
                                <button type="button" class="btn btn-outline-danger btn-room-filter" data-filter="occupied">Dolu (<?= $occupied_rooms ?>)</button>
                                <button type="button" class="btn btn-outline-warning btn-room-filter" data-filter="dirty">Temizlik (<?= $dirty_rooms ?>)</button>
                                <button type="button" class="btn btn-outline-secondary btn-room-filter" data-filter="maintenance">Bakımda (<?= $maintenance_rooms ?>)</button>
                            </div>
                            <div class="input-group input-group-sm" style="max-width: 190px;">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="room-search-input" class="form-control border-start-0" placeholder="Oda / misafir ara...">
                            </div>
                        </div>
                    </div>

                    <div class="card-body bg-light bg-opacity-50 p-3">
                        <!-- 1.1 KAT & BLOK ŞABLONU GÖRÜNÜMÜ (FLOOR & ZONE BLUEPRINT VIEW) -->
                        <div id="floors-view-container">
                            <?php foreach ($floor_groups as $floor_name => $f_rooms):
                                $f_total = count($f_rooms);
                                $f_clean = 0; $f_occupied = 0; $f_dirty = 0; $f_maint = 0;
                                foreach ($f_rooms as $fr) {
                                    $fst = strtolower(trim($fr['status'] ?? 'clean'));
                                    if ($fst === 'clean' || $fst === 'inspected' || $fst === 'available') $f_clean++;
                                    elseif ($fst === 'occupied') $f_occupied++;
                                    elseif ($fst === 'dirty' || $fst === 'cleaning') $f_dirty++;
                                    elseif ($fst === 'maintenance') $f_maint++;
                                }
                                $f_occ_pct = $f_total > 0 ? round(($f_occupied / $f_total) * 100) : 0;
                            ?>
                                <div class="floor-zone-block bg-white p-3 rounded-3 shadow-sm border mb-4">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fs-5 fw-bold text-dark">
                                                <i class="fas fa-hotel text-primary me-2"></i><?= htmlspecialchars($floor_name) ?>
                                            </span>
                                            <span class="badge bg-secondary"><?= $f_total ?> Oda</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="d-flex gap-2 small">
                                                <span class="text-success"><i class="fas fa-check-circle me-1"></i><?= $f_clean ?> Müsait</span>
                                                <span class="text-danger fw-semibold"><i class="fas fa-user-check me-1"></i><?= $f_occupied ?> Dolu</span>
                                                <?php if ($f_dirty > 0): ?>
                                                    <span class="text-warning"><i class="fas fa-broom me-1"></i><?= $f_dirty ?> Kirli</span>
                                                <?php endif; ?>
                                                <?php if ($f_maint > 0): ?>
                                                    <span class="text-secondary"><i class="fas fa-tools me-1"></i><?= $f_maint ?> Bakım</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="d-flex align-items-center gap-2" style="min-width: 140px;">
                                                <div class="progress flex-grow-1" style="height: 7px;">
                                                    <div class="progress-bar <?= $f_occ_pct > 75 ? 'bg-danger' : ($f_occ_pct > 40 ? 'bg-primary' : 'bg-success') ?>" role="progressbar" style="width: <?= $f_occ_pct ?>%"></div>
                                                </div>
                                                <span class="small fw-bold text-muted">%<?= $f_occ_pct ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-3">
                                        <?php foreach ($f_rooms as $r):
                                            $st = strtolower(trim($r['status'] ?? 'clean'));
                                            if ($st === 'available') $st = 'clean';
                                            $statusClass = 'status-' . $st;
                                            $amenities = $r['amenities_list'] ?? ['wifi', 'ac'];
                                        ?>
                                            <div class="col room-card-item"
                                                 data-room-id="<?= $r['id'] ?>"
                                                 data-status="<?= $st ?>"
                                                 data-room-name="<?= htmlspecialchars($r['name']) ?>"
                                                 data-guest-name="<?= htmlspecialchars($r['guest_name'] ?? '') ?>"
                                                 data-room-number="<?= htmlspecialchars($r['room_number'] ?? '') ?>"
                                                 data-room-type="<?= htmlspecialchars($r['type'] ?? 'Standart') ?>"
                                                 data-floor="<?= htmlspecialchars($r['floor'] ?? $floor_name) ?>"
                                                 data-price="<?= $r['base_price'] ?? 2200 ?>"
                                                 data-sqm="<?= $r['room_size_sqm'] ?? 28 ?>"
                                                 data-bed="<?= htmlspecialchars($r['bed_type'] ?? '1 King Çift Kişilik Yatak') ?>"
                                                 data-pin="<?= htmlspecialchars($r['door_lock_code'] ?? '') ?>">
                                                <div class="card h-100 shadow-sm border-0 room-card <?= $statusClass ?> position-relative">
                                                    <div class="room-status-indicator <?= $st ?>"></div>
                                                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                                                        <div>
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div>
                                                                    <h6 class="fw-bold mb-0 text-dark room-number-label">
                                                                        <a href="javascript:void(0)" class="text-dark text-decoration-none btn-room-blueprint-trigger" data-room-id="<?= $r['id'] ?>" title="Oda Şablon Detayını İncele">
                                                                            <?= htmlspecialchars($r['name']) ?> <i class="fas fa-external-link-alt text-primary small ms-1" style="font-size: 0.7rem;"></i>
                                                                        </a>
                                                                    </h6>
                                                                    <span class="text-muted small"><?= htmlspecialchars($r['type'] ?? 'Standart') ?></span>
                                                                </div>
                                                                <span class="badge room-badge badge-<?= $st ?> text-capitalize px-2 py-1" id="badge-room-<?= $r['id'] ?>">
                                                                    <?php
                                                                        switch ($st) {
                                                                            case 'clean': echo '<i class="fas fa-check-circle me-1"></i>Temiz'; break;
                                                                            case 'inspected': echo '<i class="fas fa-clipboard-check me-1"></i>Onaylı'; break;
                                                                            case 'occupied': echo '<i class="fas fa-user me-1"></i>Dolu'; break;
                                                                            case 'dirty': echo '<i class="fas fa-broom me-1"></i>Kirli'; break;
                                                                            case 'cleaning': echo '<i class="fas fa-spinner fa-spin me-1"></i>Temizleniyor'; break;
                                                                            case 'maintenance': echo '<i class="fas fa-tools me-1"></i>Bakımda'; break;
                                                                            case 'do_not_disturb': echo '<i class="fas fa-ban me-1"></i>DND'; break;
                                                                            default: echo htmlspecialchars($st);
                                                                        }
                                                                    ?>
                                                                </span>
                                                            </div>

                                                            <!-- BLUEPRINT SPECS STRIP (m², Bed, Amenities) -->
                                                            <div class="blueprint-specs-strip d-flex gap-1 flex-wrap mb-2">
                                                                <span class="badge bg-light text-dark border small" title="Oda Büyüklüğü">
                                                                    <i class="fas fa-vector-square text-primary me-1"></i><?= $r['room_size_sqm'] ?? 28 ?> m²
                                                                </span>
                                                                <span class="badge bg-light text-dark border small text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($r['bed_type'] ?? '1 Çift Kişilik') ?>">
                                                                    <i class="fas fa-bed text-info me-1"></i><?= htmlspecialchars($r['bed_type'] ?? '1 Çift Kişilik') ?>
                                                                </span>
                                                            </div>

                                                            <!-- AMENITIES PILLS -->
                                                            <div class="blueprint-amenities d-flex gap-1 flex-wrap mb-2">
                                                                <?php if (in_array('jacuzzi', $amenities) || in_array('infinity_jacuzzi', $amenities)): ?>
                                                                    <span class="badge bg-info-subtle text-info border border-info-subtle small"><i class="fas fa-hot-tub me-1"></i>Jakuzi</span>
                                                                <?php endif; ?>
                                                                <?php if (in_array('fireplace', $amenities)): ?>
                                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle small"><i class="fas fa-fire me-1"></i>Şömine</span>
                                                                <?php endif; ?>
                                                                <?php if (in_array('private_pool', $amenities) || in_array('pool_access', $amenities)): ?>
                                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle small"><i class="fas fa-swimming-pool me-1"></i>Havuz</span>
                                                                <?php endif; ?>
                                                                <?php if (in_array('kitchen', $amenities) || in_array('full_kitchen', $amenities)): ?>
                                                                    <span class="badge bg-success-subtle text-success border border-success-subtle small"><i class="fas fa-utensils me-1"></i>Mutfak</span>
                                                                <?php endif; ?>
                                                                <?php if (in_array('sea_view', $amenities)): ?>
                                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle small"><i class="fas fa-water me-1"></i>Deniz</span>
                                                                <?php endif; ?>
                                                                <?php if (in_array('balcony', $amenities) || in_array('terrace', $amenities)): ?>
                                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle small"><i class="fas fa-wind me-1"></i>Teras/Balkon</span>
                                                                <?php endif; ?>
                                                            </div>

                                                            <div class="room-details-box bg-white p-2 rounded border mb-2 small">
                                                                <div class="d-flex justify-content-between mb-1">
                                                                    <span class="text-muted"><i class="fas fa-tag me-1"></i>Gecelik:</span>
                                                                    <span class="fw-bold text-primary">₺<?= number_format($r['base_price'] ?? 2200, 2) ?></span>
                                                                </div>
                                                                <?php if (!empty($r['door_lock_code'])): ?>
                                                                <div class="d-flex justify-content-between mb-1">
                                                                    <span class="text-muted"><i class="fas fa-key me-1"></i>Akıllı Kapı PIN:</span>
                                                                    <span class="badge bg-dark font-monospace"><?= htmlspecialchars($r['door_lock_code']) ?></span>
                                                                </div>
                                                                <?php endif; ?>
                                                                <?php if ($st === 'occupied' && !empty($r['guest_name'])): ?>
                                                                    <div class="mt-2 pt-2 border-top">
                                                                        <div class="d-flex align-items-center gap-1 text-danger fw-semibold">
                                                                            <i class="fas fa-user-circle"></i>
                                                                            <span class="text-truncate"><?= htmlspecialchars($r['guest_name']) ?></span>
                                                                        </div>
                                                                        <?php if (!empty($r['checkout_date'])): ?>
                                                                            <div class="text-muted small mt-1">
                                                                                <i class="fas fa-sign-out-alt me-1"></i>Çıkış: <?= htmlspecialchars($r['checkout_date']) ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($r['folio_balance']) && $r['folio_balance'] > 0): ?>
                                                                            <div class="text-primary small fw-bold mt-1">
                                                                                <i class="fas fa-receipt me-1"></i>Folyo: ₺<?= number_format($r['folio_balance'], 2) ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>

                                                        <!-- CARD ACTION FOOTER -->
                                                        <div class="d-flex gap-1 justify-content-between align-items-center mt-2 pt-2 border-top">
                                                            <div class="dropdown">
                                                                <button class="btn btn-xs btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                                    Durum
                                                                </button>
                                                                <ul class="dropdown-menu shadow">
                                                                    <li><a class="dropdown-item btn-quick-status text-success" href="#" data-room-id="<?= $r['id'] ?>" data-status="clean"><i class="fas fa-check-circle me-2"></i>Temiz (Satışa Aç)</a></li>
                                                                    <li><a class="dropdown-item btn-quick-status text-primary" href="#" data-room-id="<?= $r['id'] ?>" data-status="inspected"><i class="fas fa-clipboard-check me-2"></i>Kontrol Edildi (Onaylı)</a></li>
                                                                    <li><a class="dropdown-item btn-quick-status text-warning" href="#" data-room-id="<?= $r['id'] ?>" data-status="dirty"><i class="fas fa-broom me-2"></i>Kirli (Temizlik Bekliyor)</a></li>
                                                                    <li><a class="dropdown-item btn-quick-status text-secondary" href="#" data-room-id="<?= $r['id'] ?>" data-status="cleaning"><i class="fas fa-spray-can me-2"></i>Temizleniyor</a></li>
                                                                    <li><a class="dropdown-item btn-quick-status text-danger" href="#" data-room-id="<?= $r['id'] ?>" data-status="maintenance"><i class="fas fa-tools me-2"></i>Bakıma Al (Bloke Et)</a></li>
                                                                </ul>
                                                            </div>

                                                            <div class="d-flex gap-1">
                                                                <button class="btn btn-xs btn-outline-info btn-room-blueprint-trigger"
                                                                        data-room-id="<?= $r['id'] ?>"
                                                                        title="Oda Şablon Detayı">
                                                                    <i class="fas fa-info-circle"></i>
                                                                </button>
                                                                <?php if ($st === 'occupied'): ?>
                                                                    <button class="btn btn-sm btn-outline-danger btn-checkout-trigger"
                                                                            data-room-id="<?= $r['id'] ?>"
                                                                            data-room-name="<?= htmlspecialchars($r['name']) ?>"
                                                                            data-guest-name="<?= htmlspecialchars($r['guest_name'] ?? '') ?>"
                                                                            title="Check-Out Yap & Folyoyu Kapat">
                                                                        <i class="fas fa-sign-out-alt"></i> Çıkış
                                                                    </button>
                                                                    <button class="btn btn-sm btn-outline-primary btn-add-charge-trigger"
                                                                            data-room-id="<?= $r['id'] ?>"
                                                                            data-room-name="<?= htmlspecialchars($r['name']) ?>"
                                                                            data-guest-name="<?= htmlspecialchars($r['guest_name'] ?? '') ?>"
                                                                            data-guest-id="<?= $r['guest_id'] ?? '' ?>"
                                                                            title="Ekstra Folyo Harcaması Ekle">
                                                                        <i class="fas fa-plus"></i> Folyo
                                                                    </button>
                                                                <?php else: ?>
                                                                    <button class="btn btn-sm btn-success btn-checkin-trigger"
                                                                            data-room-id="<?= $r['id'] ?>"
                                                                            data-room-name="<?= htmlspecialchars($r['name']) ?>"
                                                                            data-room-price="<?= $r['base_price'] ?? 2200 ?>"
                                                                            title="Odaya Hızlı Check-In Yap">
                                                                        <i class="fas fa-key"></i> Giriş
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- 1.2 KOMPAKT IZGARA GÖRÜNÜMÜ (COMPACT ALL-ROOMS GRID VIEW - HIDDEN BY DEFAULT) -->
                        <div id="room-cards-container" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-3 d-none">
                            <?php foreach ($rooms as $r):
                                $st = strtolower(trim($r['status'] ?? 'clean'));
                                if ($st === 'available') $st = 'clean';
                                $statusClass = 'status-' . $st;
                                $amenities = $r['amenities_list'] ?? ['wifi', 'ac'];
                            ?>
                                <div class="col room-card-item"
                                     data-room-id="<?= $r['id'] ?>"
                                     data-status="<?= $st ?>"
                                     data-room-name="<?= htmlspecialchars($r['name']) ?>"
                                     data-guest-name="<?= htmlspecialchars($r['guest_name'] ?? '') ?>"
                                     data-room-number="<?= htmlspecialchars($r['room_number'] ?? '') ?>"
                                     data-room-type="<?= htmlspecialchars($r['type'] ?? 'Standart') ?>"
                                     data-floor="<?= htmlspecialchars($r['floor'] ?? 'Ana Bina') ?>"
                                     data-price="<?= $r['base_price'] ?? 2200 ?>"
                                     data-sqm="<?= $r['room_size_sqm'] ?? 28 ?>"
                                     data-bed="<?= htmlspecialchars($r['bed_type'] ?? '1 King Çift Kişilik Yatak') ?>"
                                     data-pin="<?= htmlspecialchars($r['door_lock_code'] ?? '') ?>">
                                    <div class="card h-100 shadow-sm border-0 room-card <?= $statusClass ?> position-relative">
                                        <div class="room-status-indicator <?= $st ?>"></div>
                                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h6 class="fw-bold mb-0 text-dark room-number-label">
                                                            <?= htmlspecialchars($r['name']) ?>
                                                        </h6>
                                                        <span class="text-muted small"><?= htmlspecialchars($r['type'] ?? 'Standart') ?> (<?= htmlspecialchars($r['floor'] ?? 'Ana Bina') ?>)</span>
                                                    </div>
                                                    <span class="badge room-badge badge-<?= $st ?> text-capitalize px-2 py-1">
                                                        <?= htmlspecialchars($st) ?>
                                                    </span>
                                                </div>

                                                <div class="room-details-box bg-white p-2 rounded border mb-2 small">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-muted"><i class="fas fa-vector-square me-1"></i>Şablon:</span>
                                                        <span class="fw-semibold text-dark"><?= $r['room_size_sqm'] ?? 28 ?> m² | <?= htmlspecialchars($r['bed_type'] ?? '1 Çift Kişilik') ?></span>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-muted"><i class="fas fa-tag me-1"></i>Gecelik:</span>
                                                        <span class="fw-bold text-primary">₺<?= number_format($r['base_price'] ?? 2200, 2) ?></span>
                                                    </div>
                                                    <?php if (!empty($r['door_lock_code'])): ?>
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-muted"><i class="fas fa-key me-1"></i>Kapı PIN:</span>
                                                        <span class="badge bg-dark font-monospace"><?= htmlspecialchars($r['door_lock_code']) ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                    <?php if ($st === 'occupied' && !empty($r['guest_name'])): ?>
                                                        <div class="mt-2 pt-2 border-top">
                                                            <div class="text-danger fw-semibold text-truncate">
                                                                <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($r['guest_name']) ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="d-flex gap-1 justify-content-between align-items-center mt-2 pt-2 border-top">
                                                <button class="btn btn-xs btn-outline-info btn-room-blueprint-trigger" data-room-id="<?= $r['id'] ?>">
                                                    <i class="fas fa-info-circle me-1"></i>Şablon
                                                </button>
                                                <?php if ($st === 'occupied'): ?>
                                                    <button class="btn btn-sm btn-outline-danger btn-checkout-trigger" data-room-id="<?= $r['id'] ?>" data-room-name="<?= htmlspecialchars($r['name']) ?>" data-guest-name="<?= htmlspecialchars($r['guest_name'] ?? '') ?>">Çıkış</button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-success btn-checkin-trigger" data-room-id="<?= $r['id'] ?>" data-room-name="<?= htmlspecialchars($r['name']) ?>" data-room-price="<?= $r['base_price'] ?? 2200 ?>">Giriş</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. GANTT TAPE CHART TAB (HotelDruid & QloApps Benchmark) -->
            <div class="tab-pane fade" id="tab-tape" role="tabpanel">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-0"><i class="fas fa-stream text-primary me-2"></i>14 Günlük Gantt Tape Chart (Oda & Doluluk Matrisi)</h5>
                            <small class="text-muted">Tüm odaların gün gün doluluk durumları, misafir isimleri ve pansiyon türleri.</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success bg-opacity-25 text-success border border-success"><i class="fas fa-circle me-1"></i>Check-in Yapıldı</span>
                            <span class="badge bg-primary bg-opacity-25 text-primary border border-primary"><i class="fas fa-circle me-1"></i>Rezervasyon</span>
                            <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary"><i class="fas fa-circle me-1"></i>Tamamlandı</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 550px;">
                            <table class="table table-bordered table-sm align-middle mb-0 tape-chart-table">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="min-width: 160px; z-index: 5;" class="bg-light">Oda / Birim</th>
                                        <?php foreach ($tape_chart['calendar_days'] as $cd): ?>
                                            <th class="text-center <?= $cd['is_weekend'] ? 'bg-warning bg-opacity-10' : '' ?> <?= $cd['is_today'] ? 'border-primary border-2 bg-primary bg-opacity-10' : '' ?>" style="min-width: 75px;">
                                                <div class="small fw-bold <?= $cd['is_today'] ? 'text-primary' : '' ?>"><?= $cd['day_name'] ?></div>
                                                <div class="fs-6 fw-bold"><?= $cd['day_num'] ?></div>
                                                <div class="badge bg-secondary bg-opacity-75 font-monospace" style="font-size: 0.65rem;">%<?= $cd['occupancy_pct'] ?></div>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($tape_chart['room_rows'])): ?>
                                        <?php foreach ($tape_chart['room_rows'] as $rr): ?>
                                            <tr>
                                                <td class="fw-bold text-dark bg-white">
                                                    <i class="fas fa-door-closed text-muted me-1"></i> <?= htmlspecialchars($rr['name']) ?>
                                                    <div class="small text-muted font-monospace"><?= htmlspecialchars($rr['floor'] ?? '') ?></div>
                                                </td>
                                                <?php
                                                    $daysCount = count($tape_chart['calendar_days']);
                                                    for ($col = 0; $col < $daysCount; $col++):
                                                        $matchingBlock = null;
                                                        foreach ($rr['blocks'] as $b) {
                                                            if ($col >= $b['start_col'] && $col < ($b['start_col'] + $b['span_cols'])) {
                                                                $matchingBlock = $b;
                                                                break;
                                                            }
                                                        }
                                                ?>
                                                    <td class="text-center p-1 <?= !empty($matchingBlock) ? 'bg-primary bg-opacity-10' : '' ?>">
                                                        <?php if ($matchingBlock && $col === $matchingBlock['start_col']): ?>
                                                            <div class="p-1 rounded text-white shadow-sm text-truncate small"
                                                                 style="background-color: <?= $matchingBlock['color'] ?>; font-size: 0.72rem;"
                                                                 title="<?= htmlspecialchars($matchingBlock['guest_name']) ?> (<?= $matchingBlock['board_type'] ?>)">
                                                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($matchingBlock['guest_name']) ?>
                                                            </div>
                                                        <?php elseif ($matchingBlock): ?>
                                                            <div class="w-100 rounded" style="height: 6px; background-color: <?= $matchingBlock['color'] ?>; opacity: 0.7;"></div>
                                                        <?php else: ?>
                                                            <span class="text-muted" style="font-size: 0.7rem;">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endfor; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="15" class="text-center py-4 text-muted">Tape chart verisi oluşturuluyor...</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. FOLIO & EXTRA BILLING TAB -->
            <div class="tab-pane fade" id="tab-folios" role="tabpanel">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-bold mb-0"><i class="fas fa-file-invoice-dollar text-primary me-2"></i>Oda Hesabı, Minibar & Ekstra Folyo Harcamaları</h5>
                            <span class="badge bg-primary"><?= count($charges) ?> Harcama</span>
                        </div>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-charge">
                            <i class="fas fa-plus me-1"></i> Yeni Folyo Harcaması Ekle
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="table-charges">
                                <thead class="table-light">
                                    <tr>
                                        <th>Oda No / Adı</th>
                                        <th>Misafir Adı</th>
                                        <th>Kategori</th>
                                        <th>Harcama Kalemi</th>
                                        <th>Tutar</th>
                                        <th>%2 Konaklama Vergisi</th>
                                        <th>Tarih</th>
                                        <th class="text-end">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($charges)): ?>
                                        <?php foreach ($charges as $c):
                                            $amt = (float) $c['amount'];
                                            $isRoomNight = str_contains(strtolower($c['category']), 'room') || str_contains(strtolower($c['category']), 'oda');
                                            $tax2 = $isRoomNight ? round($amt * 0.02, 2) : 0.00;
                                        ?>
                                            <tr>
                                                <td class="fw-semibold text-dark">
                                                    <i class="fas fa-door-closed text-primary me-1"></i>
                                                    <?= htmlspecialchars($c['room_name'] ?? ('Oda #' . ($c['room_number'] ?? ''))) ?>
                                                </td>
                                                <td>
                                                    <i class="fas fa-user-circle text-muted me-1"></i>
                                                    <?= htmlspecialchars($c['guest_name'] ?? 'Misafir') ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary bg-opacity-25 text-dark text-uppercase font-monospace" style="font-size: 0.75rem;">
                                                        <?= htmlspecialchars($c['category'] ?? 'Ekstra') ?>
                                                    </span>
                                                </td>
                                                <td class="fw-medium text-dark"><?= htmlspecialchars($c['item_name'] ?? 'Ekstra Harcama') ?></td>
                                                <td class="fw-bold text-primary">₺<?= number_format($amt, 2) ?></td>
                                                <td class="text-muted small">
                                                    <?= $tax2 > 0 ? '₺' . number_format($tax2, 2) : '-' ?>
                                                </td>
                                                <td class="text-muted small"><?= date('d.m.Y H:i', strtotime($c['created_at'] ?? 'now')) ?></td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-dark" onclick="window.print();" title="Folyo Yazdır">
                                                        <i class="fas fa-print"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">Henüz kayıtlı folyo harcaması bulunmuyor.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. HOUSEKEEPING (KAT HİZMETLERİ) TAB -->
            <div class="tab-pane fade" id="tab-hk" role="tabpanel">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-0"><i class="fas fa-broom text-warning me-2"></i>Kat Hizmetleri & Housekeeping Görev Panosu</h5>
                            <small class="text-muted">Çıkış temizlikleri, günlük oda bakımı ve minibar yenileme iş emirleri.</small>
                        </div>
                        <button class="btn btn-sm btn-warning text-dark fw-semibold" data-bs-toggle="modal" data-bs-target="#modal-create-hk-task">
                            <i class="fas fa-plus me-1"></i> Yeni Temizlik Görevi Ata
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Oda</th>
                                        <th>Görev Tipi</th>
                                        <th>Öncelik</th>
                                        <th>Atanan Görevli</th>
                                        <th>Durum</th>
                                        <th>Oluşturulma</th>
                                        <th class="text-end">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($housekeeping_board)): ?>
                                        <?php foreach ($housekeeping_board as $hk): ?>
                                            <tr>
                                                <td class="fw-bold text-dark"><i class="fas fa-bed text-muted me-1"></i><?= htmlspecialchars($hk['room_name'] ?? ('Oda #' . $hk['room_station_id'])) ?></td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">
                                                        <?php
                                                            switch ($hk['task_type']) {
                                                                case 'departure_clean': echo 'Çıkış Temizliği'; break;
                                                                case 'stayover_clean': echo 'Günlük Temizlik'; break;
                                                                case 'deep_clean': echo 'Derin Temizlik'; break;
                                                                case 'inspection': echo 'Oda Denetimi'; break;
                                                                case 'minibar_refill': echo 'Minibar Yenileme'; break;
                                                                default: echo htmlspecialchars($hk['task_type']);
                                                            }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $hk['priority'] === 'urgent_arrival' ? 'bg-danger' : ($hk['priority'] === 'high' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                                        <?= $hk['priority'] === 'urgent_arrival' ? 'ACİL GİRİŞ' : strtoupper($hk['priority']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars(trim(($hk['staff_first_name'] ?? '') . ' ' . ($hk['staff_last_name'] ?? '')) ?: 'Atama Bekliyor') ?></td>
                                                <td>
                                                    <span class="badge <?= $hk['status'] === 'completed' ? 'bg-success' : ($hk['status'] === 'in_progress' ? 'bg-info' : 'bg-warning text-dark') ?>">
                                                        <?= $hk['status'] === 'completed' ? 'Tamamlandı' : ($hk['status'] === 'in_progress' ? 'Sürüyor' : 'Bekliyor') ?>
                                                    </span>
                                                </td>
                                                <td class="small text-muted"><?= date('d.m.Y H:i', strtotime($hk['created_at'])) ?></td>
                                                <td class="text-end">
                                                    <?php if ($hk['status'] !== 'completed' && $hk['status'] !== 'inspected'): ?>
                                                        <button class="btn btn-sm btn-outline-success btn-update-hk-task" data-task-id="<?= $hk['id'] ?>" data-status="completed">
                                                            <i class="fas fa-check"></i> Tamamla
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-success small fw-semibold"><i class="fas fa-check-double me-1"></i>Onaylandı</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">Aktif bekleyen kat hizmetleri görevi bulunmuyor. Tüm odalar temiz!</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. MAINTENANCE & ROOM FAULTS TAB -->
            <div class="tab-pane fade" id="tab-maint" role="tabpanel">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-0"><i class="fas fa-tools text-danger me-2"></i>Teknik Servis & Oda Arıza Biletleri</h5>
                            <small class="text-muted">Klima, elektrik, tesisat ve kapı kilidi arıza takip sistemi.</small>
                        </div>
                        <button class="btn btn-sm btn-danger fw-semibold" data-bs-toggle="modal" data-bs-target="#modal-create-maintenance">
                            <i class="fas fa-plus me-1"></i> Yeni Arıza Kaydı Aç
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Oda</th>
                                        <th>Kategori</th>
                                        <th>Başlık / Sorun</th>
                                        <th>Öncelik</th>
                                        <th>Durum</th>
                                        <th>Oluşturulma</th>
                                        <th class="text-end">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($maintenance_tickets)): ?>
                                        <?php foreach ($maintenance_tickets as $mt): ?>
                                            <tr>
                                                <td class="fw-bold text-dark"><i class="fas fa-door-closed text-muted me-1"></i><?= htmlspecialchars($mt['room_name'] ?? ('Oda #' . $mt['room_station_id'])) ?></td>
                                                <td><span class="badge bg-secondary bg-opacity-25 text-dark"><?= htmlspecialchars($mt['issue_category']) ?></span></td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($mt['title']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($mt['description'] ?? '') ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $mt['priority'] === 'critical' ? 'bg-danger' : ($mt['priority'] === 'high' ? 'bg-warning text-dark' : 'bg-info') ?>">
                                                        <?= strtoupper($mt['priority']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $mt['status'] === 'resolved' ? 'bg-success' : 'bg-danger' ?>">
                                                        <?= $mt['status'] === 'resolved' ? 'Çözüldü' : 'Açık' ?>
                                                    </span>
                                                </td>
                                                <td class="small text-muted"><?= date('d.m.Y H:i', strtotime($mt['created_at'])) ?></td>
                                                <td class="text-end">
                                                    <?php if ($mt['status'] !== 'resolved'): ?>
                                                        <button class="btn btn-sm btn-outline-success btn-resolve-maint" data-ticket-id="<?= $mt['id'] ?>">
                                                            <i class="fas fa-wrench me-1"></i> Onarıldı
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-success small fw-semibold"><i class="fas fa-check-circle me-1"></i>Onarıldı</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">Aktif teknik servis arıza kaydı bulunmuyor.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. ROOM TYPES & OTA ICAL SYNC TAB -->
            <div class="tab-pane fade" id="tab-rates" role="tabpanel">
                <div class="row g-3 mb-4">
                    <div class="col-lg-6">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0"><i class="fas fa-layer-group text-primary me-2"></i>Oda Tipleri & Fiyatlandırma</h5>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-room-type">
                                    <i class="fas fa-plus me-1"></i> Yeni Oda Tipi
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Kod</th>
                                                <th>Oda Tipi Adı</th>
                                                <th>Kapasite</th>
                                                <th>Gecelik Taban</th>
                                                <th>Pansiyon</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($room_types as $rt): ?>
                                                <tr>
                                                    <td class="font-monospace fw-bold text-primary"><?= htmlspecialchars($rt['code']) ?></td>
                                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($rt['name']) ?></td>
                                                    <td><i class="fas fa-users me-1 text-muted"></i><?= $rt['max_capacity'] ?> Kişilik</td>
                                                    <td class="fw-bold text-success">₺<?= number_format($rt['base_price_per_night'], 2) ?></td>
                                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($rt['rate_plan_default']) ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0"><i class="fas fa-calendar-alt text-info me-2"></i>OTA & 2-Way iCal Senkronizasyonu (Airbnb, Booking)</h5>
                                <span class="badge bg-info">Çift Rezervasyon Önleme</span>
                            </div>
                            <div class="card-body p-3">
                                <p class="text-muted small mb-3">Odalarınızı Airbnb, Booking.com, VRBO ve Google Rentals ile çift yönlü iCalendar URL beslemesiyle senkronize edin.</p>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($rooms, 0, 5) as $r): ?>
                                        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <div>
                                                <div class="fw-bold text-dark"><i class="fas fa-door-closed text-primary me-1"></i><?= htmlspecialchars($r['name']) ?></div>
                                                <small class="text-muted font-monospace"><?= base_url('verticals/hospitality_ical_export/' . $r['id']) ?></small>
                                            </div>
                                            <div class="d-flex gap-1">
                                                <a href="<?= base_url('verticals/hospitality_ical_export/' . $r['id']) ?>" target="_blank" class="btn btn-xs btn-outline-primary" title="iCal Dışa Aktar">
                                                    <i class="fas fa-download"></i> .ics
                                                </a>
                                                <button class="btn btn-xs btn-outline-success btn-sync-ical" data-room-id="<?= $r['id'] ?>" title="Şimdi Senkronize Et">
                                                    <i class="fas fa-sync"></i> Eşle
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7. KBS (KİMLİK BİLDİRİM SİSTEMİ) TAB -->
            <div class="tab-pane fade" id="tab-kbs" role="tabpanel">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-0"><i class="fas fa-id-card text-danger me-2"></i>KBS (Kimlik Bildirim Sistemi - 1774 Sayılı Kanun)</h5>
                            <small class="text-muted">Emniyet Genel Müdürlüğü (AKBS) ve Jandarma resmi konaklayan bildirim kayıtları.</small>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?= base_url('verticals/hospitality_kbs_export?format=json') ?>" class="btn btn-sm btn-outline-dark">
                                <i class="fas fa-file-code me-1"></i> JSON İndir
                            </a>
                            <a href="<?= base_url('verticals/hospitality_kbs_export?format=xml') ?>" class="btn btn-sm btn-danger">
                                <i class="fas fa-file-excel me-1"></i> Resmi XML İndir
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>TCKN / Pasaport No</th>
                                        <th>Ad Soyad</th>
                                        <th>Baba / Anne Adı</th>
                                        <th>Uyruk / Cinsiyet</th>
                                        <th>Oda No</th>
                                        <th>Giriş Tarihi</th>
                                        <th>KBS Durumu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($kbs_declarations)): ?>
                                        <?php foreach ($kbs_declarations as $kbs): ?>
                                            <tr>
                                                <td class="font-monospace fw-bold text-dark"><?= htmlspecialchars($kbs['national_id_or_passport']) ?></td>
                                                <td class="fw-semibold text-primary"><?= htmlspecialchars($kbs['first_name'] . ' ' . $kbs['last_name']) ?></td>
                                                <td class="small text-muted"><?= htmlspecialchars(($kbs['father_name'] ?? '-') . ' / ' . ($kbs['mother_name'] ?? '-')) ?></td>
                                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($kbs['nationality_code']) ?> (<?= $kbs['gender'] ?>)</span></td>
                                                <td class="fw-bold"><?= htmlspecialchars($kbs['room_name'] ?? ('Oda #' . $kbs['room_station_id'])) ?></td>
                                                <td class="small text-muted"><?= date('d.m.Y H:i', strtotime($kbs['checkin_datetime'])) ?></td>
                                                <td><span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Bildirildi</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">Kayıtlı KBS bildirimi bulunmuyor. Yeni check-in yapıldığında otomatik işlenir.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 8. NIGHT AUDIT (GÜN SONU DEVRİ) TAB -->
            <div class="tab-pane fade" id="tab-audits" role="tabpanel">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-0"><i class="fas fa-moon text-warning me-2"></i>Gece Denetimi (Night Audit) Gün Sonu Arşivi</h5>
                            <small class="text-muted">Otomatik gece oda ücreti tahakkuku, doluluk %, ADR, RevPAR ve %2 Konaklama Vergisi mutabakatları.</small>
                        </div>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-night-audit">
                            <i class="fas fa-play me-1"></i> Şimdi Gece Denetimini Çalıştır
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Denetim Tarihi</th>
                                        <th>Dolu / Toplam</th>
                                        <th>Doluluk %</th>
                                        <th>ADR (Ortalama Fiyat)</th>
                                        <th>RevPAR</th>
                                        <th>Oda Cirosu</th>
                                        <th>%2 Konaklama Vergisi</th>
                                        <th>Genel Toplam</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($night_audits)): ?>
                                        <?php foreach ($night_audits as $na): ?>
                                            <tr>
                                                <td class="fw-bold text-dark font-monospace"><?= date('d.m.Y', strtotime($na['audit_date'])) ?></td>
                                                <td><?= $na['occupied_rooms'] ?> / <?= $na['total_rooms'] ?></td>
                                                <td><span class="badge bg-info text-dark">%<?= $na['occupancy_rate'] ?></span></td>
                                                <td class="fw-semibold">₺<?= number_format($na['adr'], 2) ?></td>
                                                <td class="fw-semibold text-primary">₺<?= number_format($na['revpar'], 2) ?></td>
                                                <td class="fw-bold text-dark">₺<?= number_format($na['total_room_revenue'], 2) ?></td>
                                                <td class="text-danger fw-semibold">₺<?= number_format($na['accommodation_tax_total'], 2) ?></td>
                                                <td class="fw-bold text-success">₺<?= number_format($na['grand_total'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">Henüz gerçekleştirilmiş gece denetimi (Night Audit) kaydı bulunmuyor.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 1: HIZLI CHECK-IN & KBS GİRİŞ KAYDI -->
    <div class="modal fade" id="modal-express-checkin" tabindex="-1" aria-labelledby="modal-checkin-label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold" id="modal-checkin-label">
                        <i class="fas fa-key me-2"></i>Otel Hızlı Check-In & KBS Kimlik Bildirimi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-express-checkin">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Oda Seçimi *</label>
                                <select class="form-select" name="room_id" id="checkin-room-select" required>
                                    <option value="">Oda seçin...</option>
                                    <?php foreach ($rooms as $r): ?>
                                        <option value="<?= $r['id'] ?>" data-price="<?= $r['base_price'] ?? 2200 ?>">
                                            <?= htmlspecialchars($r['name']) ?> (<?= htmlspecialchars($r['type'] ?? '') ?>) - ₺<?= number_format($r['base_price'] ?? 2200, 0) ?>/gece
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Pansiyon Türü</label>
                                <select class="form-select" name="board_type">
                                    <option value="BB" selected>Oda & Kahvaltı (BB)</option>
                                    <option value="RO">Sadece Oda (RO)</option>
                                    <option value="HB">Yarım Pansiyon (HB)</option>
                                    <option value="FB">Tam Pansiyon (FB)</option>
                                    <option value="AI">Her Şey Dahil (AI)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Misafir Adı *</label>
                                <input type="text" class="form-control" name="guest_first_name" required placeholder="Ad">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Misafir Soyadı *</label>
                                <input type="text" class="form-control" name="guest_last_name" required placeholder="Soyad">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">TCKN / Pasaport No (KBS) *</label>
                                <input type="text" class="form-control font-monospace" name="kbs_id_number" required placeholder="TC Kimlik veya Pasaport No">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Telefon</label>
                                <input type="tel" class="form-control" name="guest_phone" placeholder="05XXXXXXXXX">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Baba Adı</label>
                                <input type="text" class="form-control" name="father_name" placeholder="Baba Adı">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Doğum Tarihi</label>
                                <input type="date" class="form-control" name="birth_date">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Araç Plakası</label>
                                <input type="text" class="form-control text-uppercase" name="vehicle_plate" placeholder="34 ABC 123">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-success fw-bold" id="btn-submit-checkin">
                            <i class="fas fa-check-circle me-1"></i> Check-In Yap & Odayı Aç
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: HIZLI CHECK-OUT & FOLYO HESABI KAPATMA -->
    <div class="modal fade" id="modal-express-checkout" tabindex="-1" aria-labelledby="modal-checkout-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white py-3">
                    <h5 class="modal-title fw-bold" id="modal-checkout-label">
                        <i class="fas fa-sign-out-alt me-2"></i>Oda Check-Out & Folyo Tahsilatı
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-express-checkout">
                    <input type="hidden" name="room_id" id="checkout-room-id">
                    <div class="modal-body p-4">
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="fas fa-info-circle me-1"></i> Check-out işlemi tamamlandığında oda otomatik olarak <strong>Kirli (Dirty)</strong> durumuna alınır ve Kat Hizmetlerine çıkış temizliği iş emri açılır.
                        </div>
                        <h5 class="fw-bold text-dark mb-1" id="checkout-room-name-display">-</h5>
                        <p class="text-muted small mb-3" id="checkout-guest-name-display">-</p>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Tahsilat Yöntemi</label>
                            <select class="form-select" name="payment_method">
                                <option value="credit_card" selected>Kredi Kartı / POS</option>
                                <option value="cash">Nakit</option>
                                <option value="bank_transfer">Havale / EFT</option>
                                <option value="room_bill">Firma Faturası / Acente</option>
                            </select>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="checkout-close-folio" name="pay_balance" value="1" checked>
                            <label class="form-check-label fw-semibold" for="checkout-close-folio">Kalan bakiye tahsil edildi ve folyo kapatılsın</label>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="submit" class="btn btn-danger fw-bold" id="btn-submit-checkout">
                            <i class="fas fa-check me-1"></i> Check-Out'u Tamamla
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 3: FOLYO HARCAMASI EKLE -->
    <div class="modal fade" id="modal-add-charge" tabindex="-1" aria-labelledby="modal-add-charge-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold" id="modal-add-charge-label">
                        <i class="fas fa-plus-circle me-2"></i>Folyoya Ekstra Harcama Ekle
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-add-charge">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Oda Seçimi *</label>
                            <select class="form-select" id="charge-room-id" name="room_id" required>
                                <option value="">Oda seçin...</option>
                                <?php foreach ($rooms as $r): ?>
                                    <option value="<?= $r['id'] ?>">
                                        <?= htmlspecialchars($r['name']) ?> <?= !empty($r['guest_name']) ? '(' . htmlspecialchars($r['guest_name']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Harcama Kategorisi *</label>
                            <select class="form-select" id="charge-category" name="category" required>
                                <option value="minibar" selected>Minibar İçecek / Atıştırmalık</option>
                                <option value="restaurant">Restoran & Oda Servisi (Adisyon)</option>
                                <option value="spa_wellness">SPA & Masaj Hizmeti</option>
                                <option value="transfer">Havalimanı VIP Transferi</option>
                                <option value="laundry">Çamaşırhane / Kuru Temizleme</option>
                                <option value="extra_bed">Ekstra Yatak / Bebek Beşiği</option>
                                <option value="extra">Diğer Ekstra Harcamalar</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Harcama Kalemi Açıklaması *</label>
                            <input type="text" class="form-control" id="charge-item-name" name="item_name" required placeholder="Örn: 2x Bira, 1x Cips veya VIP Transfer">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <label class="form-label fw-bold small text-muted">Tutar (₺) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">₺</span>
                                    <input type="number" step="0.01" min="0.01" class="form-control" id="charge-amount" name="amount" required placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-4">
                                <label class="form-label fw-bold small text-muted">Adet</label>
                                <input type="number" step="1" min="1" class="form-control" name="quantity" value="1">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary fw-bold" id="btn-submit-charge">
                            <i class="fas fa-save me-1"></i> Folyoya Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 4: GECE DENETİMİ (NIGHT AUDIT) ÇALIŞTIR -->
    <div class="modal fade" id="modal-night-audit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-moon text-warning me-2"></i>Gece Denetimi & Gün Sonu Devri (Night Audit)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-night-audit">
                    <div class="modal-body p-4">
                        <div class="alert alert-warning small mb-3">
                            <i class="fas fa-exclamation-triangle me-1"></i> Gece Denetimi, tüm dolu odalara günlük konaklama ücretini otomatik tahakkuk ettirir, %2 Konaklama Vergisini hesaplar ve günün ADR/RevPAR verilerini dondurur.
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Denetim Tarihi</label>
                            <input type="date" class="form-control font-monospace" name="audit_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="post-room-charges" name="post_room_charges" value="1" checked>
                            <label class="form-check-label fw-semibold" for="post-room-charges">Dolu odaların gece ücreti otomatik folyoya eklensin</label>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="submit" class="btn btn-primary fw-bold" id="btn-submit-night-audit">
                            <i class="fas fa-play me-1"></i> Denetimi Başlat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 5: YENİ KAT HİZMETLERİ İŞ EMRİ -->
    <div class="modal fade" id="modal-create-hk-task" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark py-3">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-broom me-2"></i>Kat Hizmetleri İş Emri Oluştur
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-create-hk-task">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Oda *</label>
                            <select class="form-select" name="room_id" required>
                                <?php foreach ($rooms as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Görev Tipi</label>
                            <select class="form-select" name="task_type">
                                <option value="stayover_clean">Günlük Misafir Oda Temizliği</option>
                                <option value="departure_clean">Çıkış Temizliği & Çarşaf Değişimi</option>
                                <option value="deep_clean">Detaylı / Derin Temizlik</option>
                                <option value="inspection">Oda Giriş Öncesi Denetim</option>
                                <option value="minibar_refill">Minibar İkram Yenileme</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Öncelik</label>
                            <select class="form-select" name="priority">
                                <option value="normal">Normal</option>
                                <option value="high">Yüksek</option>
                                <option value="urgent_arrival">Acil (Giriş Bekleyen Misafir)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Özel Notlar</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Örn: Bebek beşiği konulacak, ekstra havlu bırakılacak."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-warning text-dark fw-bold">
                            <i class="fas fa-check me-1"></i> Görevi Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 6: TEKNİK SERVİS ARIZA BİLETİ AÇ -->
    <div class="modal fade" id="modal-create-maintenance" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white py-3">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-tools me-2"></i>Teknik Servis & Oda Arıza Bilet Aç
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-create-maintenance">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Arızalı Oda *</label>
                            <select class="form-select" name="room_id" required>
                                <?php foreach ($rooms as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Arıza Kategorisi</label>
                            <select class="form-select" name="issue_category">
                                <option value="hvac_ac">Klima & Isıtma / Havalandırma</option>
                                <option value="plumbing">Sıhhi Tesisat / Jakuzi / Duş</option>
                                <option value="electrical">Elektrik / Aydınlatma / Priz</option>
                                <option value="keycard_lock">Kapı Kilidi / Akıllı Kilit</option>
                                <option value="wifi_tv">Wi-Fi & Televizyon / Uydu</option>
                                <option value="furniture">Mobilya & Ahşap Aksam</option>
                                <option value="other">Diğer Teknik Sorunlar</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Arıza Başlığı *</label>
                            <input type="text" class="form-control" name="title" required placeholder="Örn: Klima su akıtıyor veya Jakuzi çalışmıyor">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Açıklama</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Arızanın detaylarını belirtin..."></textarea>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="block-room" name="block_room" value="1" checked>
                            <label class="form-check-label fw-semibold text-danger" for="block-room">Odayı bakıma al (Satışa ve rezervasyona bloke et)</label>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-danger fw-bold">
                            <i class="fas fa-save me-1"></i> Arıza Kaydını Aç
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 8. MODAL: PRESET PROPERTY TEMPLATES (TESİS & ODA ŞABLONU UYGULA) -->
    <div class="modal fade" id="modal-apply-template" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning bg-opacity-25 py-3">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fas fa-magic text-warning me-2"></i>Hazır Tesis & Mimari Yerleşim Şablonu Uygula
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-3">
                        Tesisinizin mimari konseptine uygun hazır oda ve kat şablonunu seçerek saniyeler içinde tüm oda tiplerini, katları ve donanımları otomatik oluşturabilirsiniz.
                    </p>
                    <div class="row g-3">
                        <!-- Template 1: Boutique Hotel -->
                        <div class="col-md-4">
                            <div class="card h-100 border shadow-sm p-3 position-relative">
                                <span class="badge bg-primary position-absolute top-0 end-0 m-2">Popüler</span>
                                <div class="text-center my-2 text-primary fs-1">
                                    <i class="fas fa-hotel"></i>
                                </div>
                                <h6 class="fw-bold text-dark text-center mb-1">Lüks Butik Otel</h6>
                                <p class="text-muted small text-center mb-3">3 Katlı mimari plan: Bahçe odaları, deluxe deniz terasları ve balayı penthouseları.</p>
                                <ul class="list-unstyled small text-muted mb-3">
                                    <li><i class="fas fa-check text-success me-1"></i> 3 Farklı Oda Tipi Şablonu</li>
                                    <li><i class="fas fa-check text-success me-1"></i> 6 Hazır Oda (101-301)</li>
                                    <li><i class="fas fa-check text-success me-1"></i> Jakuzi & Balkon Donanımları</li>
                                </ul>
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 fw-bold btn-select-template" data-template-key="boutique_hotel">
                                    Bu Şablonu Uygula
                                </button>
                            </div>
                        </div>

                        <!-- Template 2: Bungalow Resort -->
                        <div class="col-md-4">
                            <div class="card h-100 border shadow-sm p-3 position-relative">
                                <span class="badge bg-success position-absolute top-0 end-0 m-2">Doğa & Glamping</span>
                                <div class="text-center my-2 text-success fs-1">
                                    <i class="fas fa-campground"></i>
                                </div>
                                <h6 class="fw-bold text-dark text-center mb-1">Doğa Bungalov & Köyü</h6>
                                <p class="text-muted small text-center mb-3">Havuz başı jakuzili ahşap bungalovlar, şömineli orman taş villaları ve glamping kubbe çadırları.</p>
                                <ul class="list-unstyled small text-muted mb-3">
                                    <li><i class="fas fa-check text-success me-1"></i> 3 Doğa Konsept Şablonu</li>
                                    <li><i class="fas fa-check text-success me-1"></i> 7 Hazır Birim (Bungalov & Villa)</li>
                                    <li><i class="fas fa-check text-success me-1"></i> Özel Havuz, Jakuzi & Şömine</li>
                                </ul>
                                <button type="button" class="btn btn-outline-success btn-sm w-100 fw-bold btn-select-template" data-template-key="bungalow_resort">
                                    Bu Şablonu Uygula
                                </button>
                            </div>
                        </div>

                        <!-- Template 3: Apart Pension -->
                        <div class="col-md-4">
                            <div class="card h-100 border shadow-sm p-3 position-relative">
                                <span class="badge bg-info text-dark position-absolute top-0 end-0 m-2">Aile & Apart</span>
                                <div class="text-center my-2 text-info fs-1">
                                    <i class="fas fa-door-open"></i>
                                </div>
                                <h6 class="fw-bold text-dark text-center mb-1">Apart Otel & Pansiyon</h6>
                                <p class="text-muted small text-center mb-3">Tam donanımlı mutfaklı 1+1 aile daireleri, stüdyolar ve ekonomik çift kişilik odalar.</p>
                                <ul class="list-unstyled small text-muted mb-3">
                                    <li><i class="fas fa-check text-success me-1"></i> 3 Daire Tipi Şablonu</li>
                                    <li><i class="fas fa-check text-success me-1"></i> 6 Hazır Daire / Oda</li>
                                    <li><i class="fas fa-check text-success me-1"></i> Mutfak & Çamaşır Makinesi</li>
                                </ul>
                                <button type="button" class="btn btn-outline-info btn-sm w-100 fw-bold btn-select-template" data-template-key="apart_pension">
                                    Bu Şablonu Uygula
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 9. MODAL: ROOM BLUEPRINT & SPEC DETAILS (ODA ŞABLON VE TEKNİK MİMARİ DETAY KARTI) -->
    <div class="modal fade" id="modal-room-blueprint-detail" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold" id="blueprint-modal-room-name">
                        <i class="fas fa-layer-group me-2"></i>Oda Mimari Şablon Kartı
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <div class="p-3 bg-light rounded-3 border mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-bold">Oda Tipi Şablonu:</span>
                                    <span class="badge bg-primary fs-6" id="blueprint-modal-type">Standart</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-bold">Kat / Mimari Bölge:</span>
                                    <span class="fw-semibold text-dark" id="blueprint-modal-floor">1. Kat</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-bold">Net Metrekare:</span>
                                    <span class="fw-bold text-dark" id="blueprint-modal-sqm">28 m²</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small fw-bold">Yatak Düzeni:</span>
                                    <span class="fw-semibold text-dark" id="blueprint-modal-bed">1 King Yatak</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small fw-bold">Taban Gecelik Fiyat:</span>
                                    <span class="fw-bold text-success fs-5" id="blueprint-modal-price">₺2.200</span>
                                </div>
                            </div>

                            <div class="card border mb-3">
                                <div class="card-header bg-white py-2 fw-semibold small text-muted">
                                    <i class="fas fa-concierge-bell text-primary me-1"></i> Oda Donanımları & Olanaklar
                                </div>
                                <div class="card-body p-3">
                                    <div class="d-flex gap-2 flex-wrap" id="blueprint-modal-amenities">
                                        <span class="badge bg-light text-dark border p-2"><i class="fas fa-wifi text-primary me-1"></i> Yüksek Hızlı Wi-Fi</span>
                                        <span class="badge bg-light text-dark border p-2"><i class="fas fa-snowflake text-info me-1"></i> Sessiz Klima</span>
                                        <span class="badge bg-light text-dark border p-2"><i class="fas fa-tv text-secondary me-1"></i> Smart LED TV</span>
                                        <span class="badge bg-light text-dark border p-2"><i class="fas fa-lock text-dark me-1"></i> Dijital Kasa</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="card border mb-3 text-center p-3 bg-dark text-white">
                                <div class="small text-white-50 text-uppercase fw-bold mb-1">Dijital Akıllı Kapı PIN Kodu</div>
                                <div class="display-6 fw-bold font-monospace text-warning mb-1" id="blueprint-modal-pin">
                                    ------
                                </div>
                                <div class="small text-white-50">Misafir girişiyle birlikte anlık üretilir</div>
                            </div>

                            <div class="card border mb-3 p-3 bg-light">
                                <div class="small fw-bold text-muted mb-2"><i class="fas fa-user-circle text-danger me-1"></i> Mevcut Misafir Durumu</div>
                                <div id="blueprint-modal-guest-info" class="small">
                                    <span class="text-success"><i class="fas fa-check-circle me-1"></i>Oda boş, yeni misafir girişine hazır.</span>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-success fw-bold" id="blueprint-modal-action-btn">
                                    <i class="fas fa-key me-1"></i> Odaya Hızlı Giriş Yap
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Kapat</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
        <div id="hospitality-toast" class="toast align-items-center text-white border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="hospitality-toast-body">
                    İşlem gerçekleştirildi.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Kapat"></button>
            </div>
        </div>
    </div>
</div>

<style>
/* World-Class Modern Hotel PMS Stylesheet */
.hospitality-vertical-wrapper {
    background-color: #f8fafc;
    font-family: inherit;
}
.stat-icon-bubble {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.room-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-radius: 12px;
    overflow: hidden;
}
.room-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08) !important;
}
.room-status-indicator {
    height: 5px;
    width: 100%;
}
.room-status-indicator.clean, .room-status-indicator.inspected { background-color: #10b981; }
.room-status-indicator.occupied { background-color: #ef4444; }
.room-status-indicator.dirty, .room-status-indicator.cleaning { background-color: #f59e0b; }
.room-status-indicator.maintenance { background-color: #64748b; }
.room-status-indicator.do_not_disturb { background-color: #8b5cf6; }

.badge-clean, .badge-inspected { background-color: #d1fae5; color: #065f46; }
.badge-occupied { background-color: #fee2e2; color: #991b1b; }
.badge-dirty, .badge-cleaning { background-color: #fef3c7; color: #92400e; }
.badge-maintenance { background-color: #e2e8f0; color: #334155; }
.badge-do_not_disturb { background-color: #ede9fe; color: #5b21b6; }

.tape-chart-table th, .tape-chart-table td {
    vertical-align: middle;
}
.btn-xs {
    padding: 0.15rem 0.4rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toastEl = document.getElementById('hospitality-toast');
    const toastBody = document.getElementById('hospitality-toast-body');
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });

    function showNotification(msg, isSuccess = true) {
        toastEl.className = 'toast align-items-center text-white border-0 shadow ' + (isSuccess ? 'bg-success' : 'bg-danger');
        toastBody.textContent = msg;
        toast.show();
    }

    // ROOM FILTERING
    const filterButtons = document.querySelectorAll('.btn-room-filter');
    const roomItems = document.querySelectorAll('.room-card-item');
    const searchInput = document.getElementById('room-search-input');
    const filteredCountBadge = document.getElementById('filtered-room-count');

    function applyFilters() {
        const activeBtn = document.querySelector('.btn-room-filter.active');
        const filterVal = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';
        const searchVal = (searchInput ? searchInput.value : '').toLowerCase().trim();

        let visibleCount = 0;

        roomItems.forEach(item => {
            const st = item.getAttribute('data-status');
            const roomName = (item.getAttribute('data-room-name') || '').toLowerCase();
            const guestName = (item.getAttribute('data-guest-name') || '').toLowerCase();
            const roomNum = (item.getAttribute('data-room-number') || '').toLowerCase();

            const matchesStatus = (filterVal === 'all' || st === filterVal);
            const matchesSearch = (searchVal === '' || roomName.includes(searchVal) || guestName.includes(searchVal) || roomNum.includes(searchVal));

            if (matchesStatus && matchesSearch) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        if (filteredCountBadge) {
            filteredCountBadge.textContent = visibleCount + ' Oda';
        }
    }

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            filterButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    // QUICK STATUS UPDATE
    document.querySelectorAll('.btn-quick-status').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const roomId = this.getAttribute('data-room-id');
            const newStatus = this.getAttribute('data-status');

            fetch('<?= base_url("verticals/update_room_status") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ room_id: roomId, status: newStatus })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Oda durumu güncellendi.');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showNotification(data.message || 'Hata oluştu.', false);
                }
            })
            .catch(err => showNotification('Bağlantı hatası: ' + err.message, false));
        });
    });

    // EXPRESS CHECK-IN TRIGGER
    document.querySelectorAll('.btn-checkin-trigger').forEach(btn => {
        btn.addEventListener('click', function () {
            const roomId = this.getAttribute('data-room-id');
            const roomSelect = document.getElementById('checkin-room-select');
            if (roomSelect) {
                roomSelect.value = roomId;
            }
            const modal = new bootstrap.Modal(document.getElementById('modal-express-checkin'));
            modal.show();
        });
    });

    // SUBMIT EXPRESS CHECK-IN
    const checkinForm = document.getElementById('form-express-checkin');
    if (checkinForm) {
        checkinForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(checkinForm);
            const payload = Object.fromEntries(formData.entries());

            fetch('<?= base_url("verticals/hospitality_checkin") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showNotification('Check-in başarılı! Akıllı Kapı PIN: ' + (data.door_pin || 'Oluşturuldu'));
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showNotification(data.message || 'Check-in başarısız oldu.', false);
                }
            })
            .catch(err => showNotification('Bağlantı hatası: ' + err.message, false));
        });
    }

    // CHECK-OUT TRIGGER
    document.querySelectorAll('.btn-checkout-trigger').forEach(btn => {
        btn.addEventListener('click', function () {
            const roomId = this.getAttribute('data-room-id');
            const roomName = this.getAttribute('data-room-name');
            const guestName = this.getAttribute('data-guest-name');

            document.getElementById('checkout-room-id').value = roomId;
            document.getElementById('checkout-room-name-display').textContent = roomName;
            document.getElementById('checkout-guest-name-display').textContent = 'Misafir: ' + (guestName || 'Bilinmiyor');

            const modal = new bootstrap.Modal(document.getElementById('modal-express-checkout'));
            modal.show();
        });
    });

    // SUBMIT CHECK-OUT
    const checkoutForm = document.getElementById('form-express-checkout');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(checkoutForm);
            const payload = Object.fromEntries(formData.entries());

            fetch('<?= base_url("verticals/hospitality_checkout") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Check-out başarıyla yapıldı.');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message || 'Check-out başarısız oldu.', false);
                }
            })
            .catch(err => showNotification('Bağlantı hatası: ' + err.message, false));
        });
    }

    // ADD CHARGE MODAL TRIGGER
    document.querySelectorAll('.btn-add-charge-trigger').forEach(btn => {
        btn.addEventListener('click', function () {
            const roomId = this.getAttribute('data-room-id');
            const roomSelect = document.getElementById('charge-room-id');
            if (roomSelect) {
                roomSelect.value = roomId;
            }
            const modal = new bootstrap.Modal(document.getElementById('modal-add-charge'));
            modal.show();
        });
    });

    // SUBMIT ADD CHARGE
    const chargeForm = document.getElementById('form-add-charge');
    if (chargeForm) {
        chargeForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(chargeForm);
            const payload = Object.fromEntries(formData.entries());

            fetch('<?= base_url("verticals/add_room_charge") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Harcama folyoya kaydedildi.');
                    setTimeout(() => location.reload(), 900);
                } else {
                    showNotification(data.message || 'Harcama eklenemedi.', false);
                }
            })
            .catch(err => showNotification('Bağlantı hatası: ' + err.message, false));
        });
    }

    // SUBMIT NIGHT AUDIT
    const auditForm = document.getElementById('form-night-audit');
    if (auditForm) {
        auditForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(auditForm);
            const payload = Object.fromEntries(formData.entries());

            fetch('<?= base_url("verticals/hospitality_run_night_audit") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showNotification('Gece Denetimi tamamlandı! ADR: ₺' + (data.audit?.adr || '0') + ', RevPAR: ₺' + (data.audit?.revpar || '0'));
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showNotification(data.message || 'Denetim çalıştırılamadı.', false);
                }
            })
            .catch(err => showNotification('Bağlantı hatası: ' + err.message, false));
        });
    }

    // HOUSEKEEPING TASK COMPLETE
    document.querySelectorAll('.btn-update-hk-task').forEach(btn => {
        btn.addEventListener('click', function () {
            const taskId = this.getAttribute('data-task-id');
            const status = this.getAttribute('data-status');

            fetch('<?= base_url("verticals/hospitality_housekeeping_task_update") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ task_id: taskId, status: status })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showNotification('Kat hizmetleri görevi tamamlandı. Oda satışa hazır!');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showNotification(data.message || 'Hata oluştu.', false);
                }
            })
            .catch(err => showNotification('Hata: ' + err.message, false));
        });
    });

    // RESOLVE MAINTENANCE
    document.querySelectorAll('.btn-resolve-maint').forEach(btn => {
        btn.addEventListener('click', function () {
            const ticketId = this.getAttribute('data-ticket-id');

            fetch('<?= base_url("verticals/hospitality_resolve_maintenance") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ticket_id: ticketId, resolution_notes: 'Teknik servis tarafından giderildi.' })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showNotification('Arıza kaydı çözüldü! Oda temizliğe yönlendirildi.');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showNotification(data.message || 'Hata oluştu.', false);
                }
            })
            .catch(err => showNotification('Hata: ' + err.message, false));
        });
    });

    // ICAL SYNC
    document.querySelectorAll('.btn-sync-ical').forEach(btn => {
        btn.addEventListener('click', function () {
            const roomId = this.getAttribute('data-room-id');
            showNotification('OTA iCal senkronizasyonu başlatıldı...');

            fetch('<?= base_url("verticals/hospitality_ical_sync") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ room_id: roomId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'OTA senkronizasyonu tamamlandı.');
                } else {
                    showNotification(data.message || data.error || 'iCal adresi kontrol edilmeli.', false);
                }
            })
            .catch(err => showNotification('Bağlantı hatası: ' + err.message, false));
        });
    });

    // BLUEPRINT VIEW SWITCHER (Kat & Blok Şablonu vs Kompakt Izgara)
    const btnModeFloors = document.getElementById('btn-mode-floors');
    const btnModeGrid = document.getElementById('btn-mode-grid');
    const floorsContainer = document.getElementById('floors-view-container');
    const gridContainer = document.getElementById('room-cards-container');

    if (btnModeFloors && btnModeGrid && floorsContainer && gridContainer) {
        btnModeFloors.addEventListener('click', function () {
            btnModeFloors.classList.add('active', 'btn-primary');
            btnModeFloors.classList.remove('btn-outline-primary');
            btnModeGrid.classList.remove('active', 'btn-primary');
            btnModeGrid.classList.add('btn-outline-primary');

            floorsContainer.classList.remove('d-none');
            gridContainer.classList.add('d-none');
        });

        btnModeGrid.addEventListener('click', function () {
            btnModeGrid.classList.add('active', 'btn-primary');
            btnModeGrid.classList.remove('btn-outline-primary');
            btnModeFloors.classList.remove('active', 'btn-primary');
            btnModeFloors.classList.add('btn-outline-primary');

            gridContainer.classList.remove('d-none');
            floorsContainer.classList.add('d-none');
        });
    }

    // PRESET PROPERTY TEMPLATE SELECTION
    document.querySelectorAll('.btn-select-template').forEach(btn => {
        btn.addEventListener('click', function () {
            const tplKey = this.getAttribute('data-template-key');
            if (!confirm('Bu tesis şablonunu uygulamak istediğinizden emin misiniz? Şablondaki oda tipleri ve odalar sisteme eklenecektir.')) {
                return;
            }

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Uygulanıyor...';

            fetch('<?= base_url("verticals/hospitality_apply_property_template") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ template_key: tplKey })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Tesis şablonu başarıyla uygulandı!');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showNotification(data.message || 'Şablon uygulanamadı.', false);
                    this.disabled = false;
                    this.textContent = 'Bu Şablonu Uygula';
                }
            })
            .catch(err => {
                showNotification('Bağlantı hatası: ' + err.message, false);
                this.disabled = false;
                this.textContent = 'Bu Şablonu Uygula';
            });
        });
    });

    // ROOM BLUEPRINT DETAIL MODAL
    document.querySelectorAll('.btn-room-blueprint-trigger').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const card = this.closest('.room-card-item');
            if (!card) return;

            const roomId = card.getAttribute('data-room-id');
            const roomName = card.getAttribute('data-room-name');
            const roomType = card.getAttribute('data-room-type');
            const floor = card.getAttribute('data-floor');
            const price = card.getAttribute('data-price');
            const sqm = card.getAttribute('data-sqm');
            const bed = card.getAttribute('data-bed');
            const pin = card.getAttribute('data-pin');
            const guestName = card.getAttribute('data-guest-name');
            const status = card.getAttribute('data-status');

            document.getElementById('blueprint-modal-room-name').innerHTML = '<i class="fas fa-layer-group me-2"></i>' + roomName + ' (Şablon Kartı)';
            document.getElementById('blueprint-modal-type').textContent = roomType || 'Standart';
            document.getElementById('blueprint-modal-floor').textContent = floor || 'Ana Bina';
            document.getElementById('blueprint-modal-sqm').textContent = (sqm || '28') + ' m²';
            document.getElementById('blueprint-modal-bed').textContent = bed || '1 King Çift Kişilik Yatak';
            document.getElementById('blueprint-modal-price').textContent = '₺' + Number(price || 2200).toLocaleString('tr-TR', { minimumFractionDigits: 2 });
            document.getElementById('blueprint-modal-pin').textContent = pin || '------';

            const guestInfoBox = document.getElementById('blueprint-modal-guest-info');
            const actionBtn = document.getElementById('blueprint-modal-action-btn');

            if (status === 'occupied' && guestName) {
                guestInfoBox.innerHTML = '<div class="text-danger fw-bold"><i class="fas fa-user-circle me-1"></i>' + guestName + '</div><div class="text-muted small mt-1">Oda şu anda kullanımda.</div>';
                actionBtn.className = 'btn btn-danger fw-bold';
                actionBtn.innerHTML = '<i class="fas fa-sign-out-alt me-1"></i> Check-Out Yap';
                actionBtn.onclick = function() {
                    const bpModal = bootstrap.Modal.getInstance(document.getElementById('modal-room-blueprint-detail'));
                    if (bpModal) bpModal.hide();
                    document.getElementById('checkout-room-id').value = roomId;
                    document.getElementById('checkout-room-name-display').textContent = roomName;
                    document.getElementById('checkout-guest-name-display').textContent = 'Misafir: ' + guestName;
                    new bootstrap.Modal(document.getElementById('modal-express-checkout')).show();
                };
            } else {
                guestInfoBox.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Oda temiz ve yeni misafir girişine hazır.</span>';
                actionBtn.className = 'btn btn-success fw-bold';
                actionBtn.innerHTML = '<i class="fas fa-key me-1"></i> Odaya Hızlı Giriş Yap';
                actionBtn.onclick = function() {
                    const bpModal = bootstrap.Modal.getInstance(document.getElementById('modal-room-blueprint-detail'));
                    if (bpModal) bpModal.hide();
                    const roomSelect = document.getElementById('checkin-room-select');
                    if (roomSelect) roomSelect.value = roomId;
                    new bootstrap.Modal(document.getElementById('modal-express-checkin')).show();
                };
            }

            const modal = new bootstrap.Modal(document.getElementById('modal-room-blueprint-detail'));
            modal.show();
        });
    });
});
</script>
