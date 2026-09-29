<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @var array $rooms
 * @var array $charges
 * @var array $customers
 * @var array $preferences
 * @var array $stations
 */

$customers = $customers ?? [];
$stations = $stations ?? [];

// Default sample rooms if empty
if (empty($rooms)) {
    $rooms = [
        [
            'id' => 101,
            'name' => 'Oda 101 - Standart Çift Kişilik',
            'room_number' => '101',
            'type' => 'Standart Çift Kişilik',
            'status' => 'clean',
            'floor' => '1. Kat',
            'guest_name' => '',
            'checkout_date' => '',
            'notes' => 'Bahçe manzaralı, çift kişilik geniş yatak.'
        ],
        [
            'id' => 102,
            'name' => 'Oda 102 - Superior King',
            'room_number' => '102',
            'type' => 'Superior King',
            'status' => 'occupied',
            'floor' => '1. Kat',
            'guest_name' => 'Thomas Müller',
            'guest_id' => !empty($customers[0]['id']) ? $customers[0]['id'] : 1,
            'checkout_date' => date('d.m.Y', strtotime('+2 days')) . ' 12:00',
            'notes' => 'Havalimanı VIP transferi talep edildi, late check-out opsiyonlu.'
        ],
        [
            'id' => 103,
            'name' => 'Oda 103 - Bahçe Manzaralı Aile',
            'room_number' => '103',
            'type' => 'Standart Aile',
            'status' => 'dirty',
            'floor' => '1. Kat',
            'guest_name' => 'Mehmet Demir',
            'checkout_date' => date('d.m.Y') . ' 10:30 (Check-out Yapıldı)',
            'notes' => 'Check-out tamamlandı. Çarşaflar ve minibar yenilenecek.'
        ],
        [
            'id' => 201,
            'name' => 'Suit 201 - Jakuzili Deluxe',
            'room_number' => '201',
            'type' => 'Deluxe Teras Suite',
            'status' => 'occupied',
            'floor' => '2. Kat',
            'guest_name' => 'Elif Kaya',
            'guest_id' => !empty($customers[1]['id']) ? $customers[1]['id'] : 2,
            'checkout_date' => date('d.m.Y', strtotime('+1 day')) . ' 11:30',
            'notes' => 'Yıldönümü süslemesi yapıldı. Kuştüyü yastık tercih ediyor.'
        ],
        [
            'id' => 202,
            'name' => 'Suit 202 - Balayı Suiti',
            'room_number' => '202',
            'type' => 'Balayı Suiti',
            'status' => 'clean',
            'floor' => '2. Kat',
            'guest_name' => '',
            'checkout_date' => '',
            'notes' => 'Şömineli, panoramik teraslı, ikram sepeti hazır.'
        ],
        [
            'id' => 301,
            'name' => 'Bungalov 301 - Doğa & Havuz',
            'room_number' => '301',
            'type' => 'Bungalov Doğa',
            'status' => 'occupied',
            'floor' => 'Bahçe / Havuz Başı',
            'guest_name' => 'Caner Yılmaz',
            'guest_id' => !empty($customers[2]['id']) ? $customers[2]['id'] : 3,
            'checkout_date' => date('d.m.Y', strtotime('+3 days')) . ' 12:00',
            'notes' => 'Evcil hayvan ile konaklıyor. Glutensiz kahvaltı istendi.'
        ],
        [
            'id' => 302,
            'name' => 'Bungalov 302 - Ahşap Jakuzili',
            'room_number' => '302',
            'type' => 'Ahşap Jakuzili',
            'status' => 'maintenance',
            'floor' => 'Bahçe / Sessiz Cephe',
            'guest_name' => '',
            'checkout_date' => '',
            'notes' => 'Klima filtre değişimi ve jakuzi bakımı yapılıyor.'
        ],
        [
            'id' => 401,
            'name' => 'Suit 401 - Presidential Kral',
            'room_number' => '401',
            'type' => 'Presidential Kral Dairesi',
            'status' => 'clean',
            'floor' => 'Penthouse Katı',
            'guest_name' => '',
            'checkout_date' => '',
            'notes' => 'Özel sauna, teras havuzu ve şömineli geniş salon.'
        ]
    ];
}

// Default sample charges if empty
if (empty($charges)) {
    $charges = [
        [
            'id' => 1,
            'room_number' => '201',
            'room_name' => 'Suit 201 - Jakuzili Deluxe',
            'guest_name' => 'Elif Kaya',
            'category' => 'Minibar',
            'item_name' => '2x San Pellegrino Su, 1x Toblerone Çikolata',
            'amount' => 350.00,
            'created_at' => date('Y-m-d 09:15:00')
        ],
        [
            'id' => 2,
            'room_number' => '201',
            'room_name' => 'Suit 201 - Jakuzili Deluxe',
            'guest_name' => 'Elif Kaya',
            'category' => 'SPA & Masaj',
            'item_name' => 'Aromaterapi Masajı (50 Dk)',
            'amount' => 1800.00,
            'created_at' => date('Y-m-d 16:30:00', strtotime('-1 day'))
        ],
        [
            'id' => 3,
            'room_number' => '102',
            'room_name' => 'Oda 102 - Superior King',
            'guest_name' => 'Thomas Müller',
            'category' => 'VIP Transfer',
            'item_name' => 'VIP Havalimanı Karşılama (Mercedes Vito)',
            'amount' => 2250.00,
            'created_at' => date('Y-m-d 14:00:00', strtotime('-1 day'))
        ],
        [
            'id' => 4,
            'room_number' => '301',
            'room_name' => 'Bungalov 301 - Doğa & Havuz',
            'guest_name' => 'Caner Yılmaz',
            'category' => 'Oda Servisi',
            'item_name' => 'Gurme Serpme Kahvaltı & Taze Meyve Sepeti',
            'amount' => 750.00,
            'created_at' => date('Y-m-d 10:45:00')
        ],
        [
            'id' => 5,
            'room_number' => '103',
            'room_name' => 'Oda 103 - Bahçe Manzaralı Aile',
            'guest_name' => 'Mehmet Demir',
            'category' => 'Çamaşırhane / Kuru Temizleme',
            'item_name' => 'Express Takım Elbise Ütü & Kuru Temizleme',
            'amount' => 480.00,
            'created_at' => date('Y-m-d 18:20:00', strtotime('-2 days'))
        ]
    ];
}

// Default sample preferences if empty
if (empty($preferences)) {
    $preferences = [
        [
            'id' => 1,
            'guest_name' => 'Elif Kaya',
            'vip_level' => 'VIP',
            'pillow_choice' => 'Kuştüyü Yastık',
            'floor_preference' => 'Yüksek Kat / Sessiz Cephe',
            'dietary_allergies' => 'Laktozsuz, Çilek Alerjisi',
            'special_notes' => 'Yıldönümü konaklaması; taze beyaz güller ve şampanya ikramı.'
        ],
        [
            'id' => 2,
            'guest_name' => 'Thomas Müller',
            'vip_level' => 'VVIP',
            'pillow_choice' => 'Ortopedik Yastık',
            'floor_preference' => 'Asansöre Yakın, Sigarasız Kat',
            'dietary_allergies' => 'Glutensiz Ekmek',
            'special_notes' => 'Sabah 07:00 express kahvaltı ve Financial Times gazetesi.'
        ],
        [
            'id' => 3,
            'guest_name' => 'Caner Yılmaz',
            'vip_level' => 'Standart',
            'pillow_choice' => 'Sentetik Antialerjik',
            'floor_preference' => 'Bahçe / Havuz Manzaralı',
            'dietary_allergies' => 'Fıstık & Kabuklu Kuruyemiş Alerjisi',
            'special_notes' => 'Küçük ırk köpek ile seyahat ediyor; mama kabı hazırlandı.'
        ]
    ];
}

// Calculate summary stats
$total_rooms = count($rooms);
$clean_rooms = 0;
$occupied_rooms = 0;
$dirty_rooms = 0;
$maintenance_rooms = 0;

foreach ($rooms as $rm) {
    $st = strtolower(trim($rm['status'] ?? 'clean'));
    if ($st === 'occupied') {
        $occupied_rooms++;
    } elseif ($st === 'dirty' || $st === 'cleaning') {
        $dirty_rooms++;
    } elseif ($st === 'maintenance') {
        $maintenance_rooms++;
    } else {
        $clean_rooms++;
    }
}

$occupancy_rate = $total_rooms > 0 ? round(($occupied_rooms / $total_rooms) * 100) : 0;
$total_folio_amount = 0;
foreach ($charges as $c) {
    $total_folio_amount += (float) ($c['amount'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?php $this->load->view('components/backend_head'); ?>
    <title><?= e(vars('page_title')) ?> - BooKi</title>
    <style>
        .room-card {
            transition: all 0.25s ease-in-out;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
        }
        .room-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08) !important;
        }
        .room-card.status-clean {
            border-top: 5px solid #198754;
        }
        .room-card.status-occupied {
            border-top: 5px solid #dc3545;
        }
        .room-card.status-dirty,
        .room-card.status-cleaning {
            border-top: 5px solid #ffc107;
        }
        .room-card.status-maintenance {
            border-top: 5px solid #6c757d;
        }

        .category-badge-minibar { background-color: #6366f1; color: #fff; }
        .category-badge-spa { background-color: #06b6d4; color: #fff; }
        .category-badge-transfer { background-color: #3b82f6; color: #fff; }
        .category-badge-roomservice { background-color: #f59e0b; color: #fff; }
        .category-badge-laundry { background-color: #8b5cf6; color: #fff; }
        .category-badge-other { background-color: #64748b; color: #fff; }

        .stat-icon-bubble {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .btn-set-room-status {
            font-size: 0.72rem;
            padding: 0.25rem 0.4rem;
            font-weight: 500;
        }

        .guest-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e0e7ff;
            color: #4338ca;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
        }
    </style>
</head>
<body class="backend-body">
    <?php $this->load->view('components/backend_header'); ?>

    <div class="container-fluid py-4 px-md-4">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1"><i class="fas fa-hotel text-primary me-2"></i>Otel & Konaklama - Oda Yönetimi, Housekeeping & Folyo</h1>
                <p class="text-muted small mb-0">Oda durum panosu, kat hizmetleri, minibar/ekstra harcama folyoları ve misafir 360 konaklama tercihleri.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modal-guest-pref" id="btn-guest-pref">
                    <i class="fas fa-concierge-bell me-1"></i> Misafir Tercihleri
                </button>
                <button class="btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modal-add-charge" id="btn-add-charge">
                    <i class="fas fa-file-invoice-dollar me-1"></i> Ekstra Harcama / Folyo Ekle
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
                            <small class="text-muted">Kapasite</small>
                        </div>
                        <div class="stat-icon-bubble bg-light text-primary">
                            <i class="fas fa-door-closed"></i>
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
                            <i class="fas fa-bed"></i>
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
                            <small class="text-danger">%<?= $occupancy_rate ?> Doluluk</small>
                        </div>
                        <div class="stat-icon-bubble bg-danger-subtle text-danger">
                            <i class="fas fa-user-check"></i>
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
                            <small class="text-warning">Housekeeping</small>
                        </div>
                        <div class="stat-icon-bubble bg-warning-subtle text-warning">
                            <i class="fas fa-broom"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold">Bakımda / Arıza</div>
                            <h3 class="fw-bold mb-0 text-secondary" id="stat-maintenance-rooms"><?= $maintenance_rooms ?></h3>
                            <small class="text-secondary">Teknik Servis</small>
                        </div>
                        <div class="stat-icon-bubble bg-secondary-subtle text-secondary">
                            <i class="fas fa-tools"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-2">
                <div class="card shadow-sm border-0 h-100 bg-primary text-white">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-white-50 small fw-semibold">Folyo Ekstralar</div>
                            <h4 class="fw-bold mb-0 text-white" id="stat-folio-total">₺<?= number_format($total_folio_amount, 2) ?></h4>
                            <small class="text-white-50"><?= count($charges) ?> Kalem Harcama</small>
                        </div>
                        <div class="stat-icon-bubble bg-white bg-opacity-25 text-white">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROOM & SUITE STATUS MATRIX (Oda Durum Panosu & Kat Hizmetleri) -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="fw-bold mb-0"><i class="fas fa-th-large text-primary me-2"></i>Oda Durum Panosu & Kat Hizmetleri Matrisi</h5>
                    <span class="badge bg-secondary" id="filtered-room-count"><?= $total_rooms ?> Oda</span>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- FILTER BUTTONS -->
                    <div class="btn-group btn-group-sm" role="group" id="room-filter-group">
                        <button type="button" class="btn btn-outline-primary active btn-room-filter" data-filter="all">Tümü (<?= $total_rooms ?>)</button>
                        <button type="button" class="btn btn-outline-success btn-room-filter" data-filter="clean">Temiz & Müsait (<?= $clean_rooms ?>)</button>
                        <button type="button" class="btn btn-outline-danger btn-room-filter" data-filter="occupied">Dolu (<?= $occupied_rooms ?>)</button>
                        <button type="button" class="btn btn-outline-warning btn-room-filter" data-filter="dirty">Temizlik Bekliyor (<?= $dirty_rooms ?>)</button>
                        <button type="button" class="btn btn-outline-secondary btn-room-filter" data-filter="maintenance">Bakımda (<?= $maintenance_rooms ?>)</button>
                    </div>
                    <!-- SEARCH INPUT -->
                    <div class="input-group input-group-sm" style="max-width: 200px;">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="room-search-input" class="form-control border-start-0" placeholder="Oda / misafir ara...">
                    </div>
                </div>
            </div>
            <div class="card-body bg-light bg-opacity-50 p-3">
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-3" id="room-cards-container">
                    <?php foreach ($rooms as $r):
                        $st = strtolower(trim($r['status'] ?? 'clean'));
                        if ($st === 'available') $st = 'clean';
                        $statusClass = 'status-' . $st;
                    ?>
                        <div class="col room-card-item"
                             data-room-id="<?= e($r['id']) ?>"
                             data-status="<?= e($st) ?>"
                             data-search="<?= e(strtolower(($r['name'] ?? '') . ' ' . ($r['type'] ?? '') . ' ' . ($r['guest_name'] ?? '') . ' ' . ($r['floor'] ?? ''))) ?>">
                            <div class="card shadow-sm border-0 h-100 room-card <?= $statusClass ?>" id="room-card-<?= e($r['id']) ?>">
                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div>
                                        <!-- ROOM HEADER -->
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="fw-bold mb-0 text-dark room-title"><?= e($r['name']) ?></h6>
                                                <small class="text-muted"><i class="fas fa-layer-group me-1"></i><?= e($r['floor'] ?? 'Ana Bina') ?></small>
                                            </div>
                                            <!-- STATUS BADGE -->
                                            <div class="room-status-badge-container">
                                                <?php if ($st === 'clean'): ?>
                                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Temiz & Müsait</span>
                                                <?php elseif ($st === 'occupied'): ?>
                                                    <span class="badge bg-danger"><i class="fas fa-user-check me-1"></i>Dolu / Misafir Konaklıyor</span>
                                                <?php elseif ($st === 'dirty' || $st === 'cleaning'): ?>
                                                    <span class="badge bg-warning text-dark"><i class="fas fa-broom me-1"></i>Temizlik Bekliyor / Kirli</span>
                                                <?php elseif ($st === 'maintenance'): ?>
                                                    <span class="badge bg-secondary"><i class="fas fa-tools me-1"></i>Bakımda / Arızalı</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- ROOM DETAILS / GUEST INFO -->
                                        <div class="room-guest-container mb-3">
                                            <?php if ($st === 'occupied' && !empty($r['guest_name'])): ?>
                                                <div class="p-2 rounded bg-light border border-danger-subtle">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <span class="guest-avatar"><?= strtoupper(mb_substr($r['guest_name'], 0, 1)) ?></span>
                                                        <div>
                                                            <div class="fw-bold small text-dark guest-name-display"><?= e($r['guest_name']) ?></div>
                                                            <small class="text-danger d-block">
                                                                <i class="far fa-calendar-alt me-1"></i>Çıkış: <?= e($r['checkout_date'] ?: 'Belirtilmedi') ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($r['notes'])): ?>
                                                        <small class="text-muted d-block fst-italic"><i class="fas fa-info-circle me-1"></i><?= e($r['notes']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php elseif ($st === 'dirty' || $st === 'cleaning'): ?>
                                                <div class="p-2 rounded bg-light border border-warning-subtle">
                                                    <div class="small text-warning-emphasis fw-semibold">
                                                        <i class="fas fa-sparkles me-1 text-warning"></i>Housekeeping Sırasında
                                                    </div>
                                                    <small class="text-muted d-block"><?= !empty($r['notes']) ? e($r['notes']) : 'Oda boşaltıldı, temizlik ve çarşaf değişimi bekliyor.' ?></small>
                                                </div>
                                            <?php elseif ($st === 'maintenance'): ?>
                                                <div class="p-2 rounded bg-light border border-secondary-subtle">
                                                    <div class="small text-secondary fw-semibold">
                                                        <i class="fas fa-wrench me-1"></i>Teknik Servis Müdahalesi
                                                    </div>
                                                    <small class="text-muted d-block"><?= !empty($r['notes']) ? e($r['notes']) : 'Arıza bildirimi yapıldı. Oda satışa kapalı.' ?></small>
                                                </div>
                                            <?php else: ?>
                                                <div class="p-2 rounded bg-light border border-success-subtle">
                                                    <div class="small text-success fw-semibold">
                                                        <i class="fas fa-check-circle me-1"></i>Oda Hazır
                                                    </div>
                                                    <small class="text-muted d-block"><?= !empty($r['notes']) ? e($r['notes']) : 'Tüm kontroller yapıldı, yeni misafir kabulüne hazır.' ?></small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- QUICK ACTION BUTTONS -->
                                    <div class="border-top pt-2">
                                        <div class="text-muted small mb-1 fw-semibold" style="font-size: 0.72rem;">Hızlı Durum Değiştir:</div>
                                        <div class="btn-group btn-group-sm w-100" role="group">
                                            <button type="button" class="btn btn-outline-success btn-set-room-status"
                                                    data-room-id="<?= e($r['id']) ?>"
                                                    data-room-name="<?= e($r['name']) ?>"
                                                    data-status="clean"
                                                    title="Temizlendi olarak işaretle">
                                                <i class="fas fa-check"></i> Temizlendi
                                            </button>
                                            <button type="button" class="btn btn-outline-warning btn-set-room-status"
                                                    data-room-id="<?= e($r['id']) ?>"
                                                    data-room-name="<?= e($r['name']) ?>"
                                                    data-status="cleaning"
                                                    title="Temizlik yapılıyor olarak işaretle">
                                                <i class="fas fa-broom"></i> Temizlik
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-set-room-status"
                                                    data-room-id="<?= e($r['id']) ?>"
                                                    data-room-name="<?= e($r['name']) ?>"
                                                    data-status="maintenance"
                                                    title="Bakıma al">
                                                <i class="fas fa-tools"></i> Bakım
                                            </button>
                                            <button type="button" class="btn btn-outline-primary btn-set-room-status"
                                                    data-room-id="<?= e($r['id']) ?>"
                                                    data-room-name="<?= e($r['name']) ?>"
                                                    data-status="available"
                                                    title="Boşa çıkar / Check-out">
                                                <i class="fas fa-door-open"></i> Boşa Çıkar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- FOLIO & EXTRA CHARGES (Oda Hesabı & Minibar Harcamaları) -->
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-bold mb-0"><i class="fas fa-file-invoice-dollar text-primary me-2"></i>Oda Hesabı & Minibar Harcamaları (Folyo)</h5>
                            <span class="badge bg-primary" id="folio-count-badge"><?= count($charges) ?> Kayıt</span>
                        </div>
                        <button class="btn btn-sm btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modal-add-charge">
                            <i class="fas fa-plus me-1"></i> Yeni Folyo Harcaması Ekle
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="table-charges">
                            <thead class="table-light">
                                <tr>
                                    <th>Oda No</th>
                                    <th>Misafir</th>
                                    <th>Harcama Kalemi</th>
                                    <th>Kategori</th>
                                    <th class="text-end">Tutar (₺)</th>
                                    <th class="text-end">Tarih</th>
                                </tr>
                            </thead>
                            <tbody id="charges-tbody">
                                <?php if (empty($charges)): ?>
                                    <tr id="empty-charges-row">
                                        <td colspan="6" class="text-center py-4 text-muted">Henüz kaydedilmiş folyo ekstra harcaması bulunmuyor.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($charges as $c):
                                        $cat = trim($c['category'] ?? 'Diğer');
                                        $badgeClass = 'category-badge-other';
                                        if (stripos($cat, 'minibar') !== false) {
                                            $badgeClass = 'category-badge-minibar';
                                        } elseif (stripos($cat, 'spa') !== false || stripos($cat, 'masaj') !== false) {
                                            $badgeClass = 'category-badge-spa';
                                        } elseif (stripos($cat, 'transfer') !== false) {
                                            $badgeClass = 'category-badge-transfer';
                                        } elseif (stripos($cat, 'oda servisi') !== false) {
                                            $badgeClass = 'category-badge-roomservice';
                                        } elseif (stripos($cat, 'çamaşır') !== false || stripos($cat, 'temizleme') !== false) {
                                            $badgeClass = 'category-badge-laundry';
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-dark fw-bold"><?= e($c['room_number'] ?? ($c['room_name'] ?? 'Oda')) ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark"><?= e($c['guest_name'] ?? 'Misafir') ?></div>
                                            </td>
                                            <td>
                                                <div class="small text-dark fw-medium"><?= e($c['item_name'] ?? '-') ?></div>
                                            </td>
                                            <td>
                                                <span class="badge <?= $badgeClass ?> small"><?= e($cat) ?></span>
                                            </td>
                                            <td class="text-end fw-bold text-success">
                                                ₺<?= number_format((float) ($c['amount'] ?? 0), 2) ?>
                                            </td>
                                            <td class="text-end text-muted small">
                                                <?= !empty($c['created_at']) ? date('d.m.Y H:i', strtotime($c['created_at'])) : '-' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- GUEST PREFERENCES 360 (Misafir Konaklama & Servis Tercihleri) -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-bold mb-0"><i class="fas fa-user-tag text-info me-2"></i>Misafir Konaklama Tercihleri</h5>
                            <span class="badge bg-info text-dark" id="guest-pref-badge"><?= count($preferences) ?> Misafir 360</span>
                        </div>
                        <button class="btn btn-sm btn-outline-info fw-semibold" data-bs-toggle="modal" data-bs-target="#modal-guest-pref">
                            <i class="fas fa-edit me-1"></i> Tercih Kaydet
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush" id="preferences-list-container">
                            <?php if (empty($preferences)): ?>
                                <div class="text-center py-4 text-muted" id="empty-pref-msg">
                                    <i class="fas fa-concierge-bell fa-2x mb-2 text-secondary opacity-50"></i>
                                    <p class="small mb-0">Henüz özel misafir tercihi kaydedilmedi.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($preferences as $p):
                                    $vip = strtoupper($p['vip_level'] ?? 'STANDART');
                                    $vipBadge = 'bg-secondary';
                                    if ($vip === 'VIP') $vipBadge = 'bg-warning text-dark';
                                    if ($vip === 'VVIP') $vipBadge = 'bg-danger';
                                ?>
                                    <div class="list-group-item p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="guest-avatar"><?= strtoupper(mb_substr($p['guest_name'] ?? 'M', 0, 1)) ?></span>
                                                <div>
                                                    <h6 class="fw-bold mb-0 text-dark"><?= e($p['guest_name'] ?? 'Misafir') ?></h6>
                                                    <small class="text-muted"><?= e($p['floor_preference'] ?? 'Kat Tercihi Belirtilmedi') ?></small>
                                                </div>
                                            </div>
                                            <span class="badge <?= $vipBadge ?> fw-semibold"><?= e($vip) ?></span>
                                        </div>
                                        <div class="small bg-light p-2 rounded">
                                            <div class="mb-1">
                                                <strong class="text-secondary"><i class="fas fa-cloud me-1"></i>Yastık:</strong>
                                                <span class="text-dark fw-medium"><?= e($p['pillow_choice'] ?? 'Standart') ?></span>
                                            </div>
                                            <div class="mb-1">
                                                <strong class="text-secondary"><i class="fas fa-utensils me-1"></i>Diyet / Alerjen:</strong>
                                                <span class="badge bg-warning-subtle text-warning-emphasis"><?= e($p['dietary_allergies'] ?? 'Yok') ?></span>
                                            </div>
                                            <?php if (!empty($p['special_notes'])): ?>
                                                <div class="text-muted fst-italic pt-1 border-top mt-1" style="font-size: 0.8rem;">
                                                    <i class="fas fa-quote-left me-1 text-primary"></i><?= e($p['special_notes']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: EKSTRA HARCAMA EKLE (#modal-add-charge) -->
    <div class="modal fade" id="modal-add-charge" tabindex="-1" aria-labelledby="modal-add-charge-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modal-add-charge-label">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Ekstra Harcama / Folyo Ekle
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-add-charge">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <!-- ROOM SELECT -->
                            <div class="col-md-6">
                                <label for="charge-room-id" class="form-label small fw-bold">Oda Seçimi <span class="text-danger">*</span></label>
                                <select class="form-select" id="charge-room-id" name="station_id" required>
                                    <option value="" disabled selected>-- Oda Seçiniz --</option>
                                    <?php foreach ($rooms as $r): ?>
                                        <option value="<?= e($r['id']) ?>" data-room-name="<?= e($r['name']) ?>" data-room-number="<?= e($r['room_number'] ?? $r['id']) ?>" data-guest="<?= e($r['guest_name'] ?? '') ?>" data-guest-id="<?= e($r['guest_id'] ?? '') ?>">
                                            <?= e($r['name']) ?> <?= !empty($r['guest_name']) ? '(' . e($r['guest_name']) . ')' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- GUEST SELECT -->
                            <div class="col-md-6">
                                <label for="charge-guest-id" class="form-label small fw-bold">Misafir <span class="text-danger">*</span></label>
                                <select class="form-select" id="charge-guest-id" name="guest_id" required>
                                    <option value="" disabled selected>-- Misafir Seçiniz --</option>
                                    <?php if (!empty($customers)): ?>
                                        <?php foreach ($customers as $c): ?>
                                            <option value="<?= e($c['id']) ?>" data-name="<?= e(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''))) ?>">
                                                <?= e(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''))) ?> (<?= e($c['phone_number'] ?? ($c['email'] ?? '')) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="1" data-name="Thomas Müller">Thomas Müller</option>
                                        <option value="2" data-name="Elif Kaya">Elif Kaya</option>
                                        <option value="3" data-name="Caner Yılmaz">Caner Yılmaz</option>
                                        <option value="4" data-name="Mehmet Demir">Mehmet Demir</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- CATEGORY SELECT -->
                            <div class="col-md-6">
                                <label for="charge-category" class="form-label small fw-bold">Harcama Kategorisi <span class="text-danger">*</span></label>
                                <select class="form-select" id="charge-category" name="category" required>
                                    <option value="Minibar" selected>Minibar</option>
                                    <option value="SPA & Masaj">SPA & Masaj</option>
                                    <option value="VIP Transfer">VIP Transfer</option>
                                    <option value="Oda Servisi">Oda Servisi</option>
                                    <option value="Çamaşırhane / Kuru Temizleme">Çamaşırhane / Kuru Temizleme</option>
                                    <option value="Diğer">Diğer</option>
                                </select>
                            </div>

                            <!-- AMOUNT INPUT -->
                            <div class="col-md-6">
                                <label for="charge-amount" class="form-label small fw-bold">Tutar (₺) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₺</span>
                                    <input type="number" step="0.01" min="0.01" class="form-control" id="charge-amount" name="amount" placeholder="0.00" required>
                                </div>
                            </div>

                            <!-- ITEM DESCRIPTION INPUT -->
                            <div class="col-12">
                                <label for="charge-item-name" class="form-label small fw-bold">Harcama Kalemi Açıklaması <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="charge-item-name" name="item_name" placeholder="Örn: 2x Su, 1x Çikolata, 1x Kırmızı Şarap" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary fw-semibold" id="btn-submit-charge">
                            <i class="fas fa-save me-1"></i> Folyoya Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: MİSAFİR TERCİHLERİ (#modal-guest-pref) -->
    <div class="modal fade" id="modal-guest-pref" tabindex="-1" aria-labelledby="modal-guest-pref-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="modal-guest-pref-label">
                        <i class="fas fa-concierge-bell text-warning me-2"></i>Misafir 360 Konaklama Tercihleri
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-guest-pref">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <!-- GUEST SELECT -->
                            <div class="col-12">
                                <label for="pref-customer-id" class="form-label small fw-bold">Misafir Seçimi <span class="text-danger">*</span></label>
                                <select class="form-select" id="pref-customer-id" name="customer_id" required>
                                    <option value="" disabled selected>-- Misafir Seçiniz --</option>
                                    <?php if (!empty($customers)): ?>
                                        <?php foreach ($customers as $c): ?>
                                            <option value="<?= e($c['id']) ?>" data-name="<?= e(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''))) ?>">
                                                <?= e(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''))) ?> (<?= e($c['phone_number'] ?? ($c['email'] ?? '')) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="1" data-name="Thomas Müller">Thomas Müller</option>
                                        <option value="2" data-name="Elif Kaya">Elif Kaya</option>
                                        <option value="3" data-name="Caner Yılmaz">Caner Yılmaz</option>
                                        <option value="4" data-name="Mehmet Demir">Mehmet Demir</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- PILLOW CHOICE -->
                            <div class="col-md-6">
                                <label for="pref-pillow-choice" class="form-label small fw-bold">Yastık Tercihi <span class="text-danger">*</span></label>
                                <select class="form-select" id="pref-pillow-choice" name="pillow_choice" required>
                                    <option value="Ortopedik" selected>Ortopedik Yastık</option>
                                    <option value="Kuştüyü">Kuştüyü Yastık</option>
                                    <option value="Sentetik">Sentetik / Antialerjik Yastık</option>
                                    <option value="Bambu Visco">Bambu Visco Yastık</option>
                                </select>
                            </div>

                            <!-- FLOOR PREFERENCE -->
                            <div class="col-md-6">
                                <label for="pref-floor-preference" class="form-label small fw-bold">Kat & Cephe Tercihi <span class="text-danger">*</span></label>
                                <select class="form-select" id="pref-floor-preference" name="floor_preference" required>
                                    <option value="Yüksek Kat" selected>Yüksek Kat</option>
                                    <option value="Sigarasız Kat">Sigarasız Kat</option>
                                    <option value="Asansöre Yakın">Asansöre Yakın</option>
                                    <option value="Sessiz Cephe">Sessiz Cephe / Bahçe Manzaralı</option>
                                    <option value="Deniz / Havuz Manzaralı">Deniz / Havuz Manzaralı</option>
                                </select>
                            </div>

                            <!-- DIETARY / ALLERGIES -->
                            <div class="col-12">
                                <label for="pref-dietary-allergies" class="form-label small fw-bold">Diyet & Alerjenler</label>
                                <input type="text" class="form-control" id="pref-dietary-allergies" name="dietary_allergies" placeholder="Örn: Glutensiz, Vegan, Fıstık Alerjisi, Laktozsuz...">
                                <small class="text-muted">Alerjenler mutfak ve oda servisi ekibine otomatik iletilir.</small>
                            </div>

                            <!-- SPECIAL NOTES -->
                            <div class="col-12">
                                <label for="pref-special-notes" class="form-label small fw-bold">Özel Notlar & İstekler</label>
                                <textarea class="form-control" id="pref-special-notes" name="special_notes" rows="3" placeholder="Örn: Late check-out talebi, gazete tercihi, ekstra yorgan vb."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-dark fw-semibold" id="btn-submit-guest-pref">
                            <i class="fas fa-check-circle me-1 text-success"></i> Tercihleri Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
        <div id="hospitality-toast" class="toast align-items-center text-white border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="hospitality-toast-body">
                    İşlem başarıyla tamamlandı.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Kapat"></button>
            </div>
        </div>
    </div>

    <script>
    /**
     * Strict XSS Protection Helper
     */
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Show Toast Notification
     */
    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('hospitality-toast');
        const toastBody = document.getElementById('hospitality-toast-body');
        if (!toastEl || !toastBody) return;

        toastEl.className = 'toast align-items-center text-white border-0 shadow bg-' + (type === 'error' ? 'danger' : type);
        toastBody.innerHTML = escapeHtml(message);

        const bsToast = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4000 });
        bsToast.show();
    }

    /**
     * Update room counters
     */
    function updateRoomCounters() {
        const allCards = document.querySelectorAll('.room-card-item');
        let total = allCards.length;
        let clean = 0;
        let occupied = 0;
        let dirty = 0;
        let maintenance = 0;

        allCards.forEach(card => {
            const st = card.dataset.status;
            if (st === 'occupied') occupied++;
            else if (st === 'dirty' || st === 'cleaning') dirty++;
            else if (st === 'maintenance') maintenance++;
            else clean++;
        });

        const statTotal = document.getElementById('stat-total-rooms');
        const statClean = document.getElementById('stat-clean-rooms');
        const statOcc = document.getElementById('stat-occupied-rooms');
        const statDirty = document.getElementById('stat-dirty-rooms');
        const statMaint = document.getElementById('stat-maintenance-rooms');

        if (statTotal) statTotal.textContent = total;
        if (statClean) statClean.textContent = clean;
        if (statOcc) statOcc.textContent = occupied;
        if (statDirty) statDirty.textContent = dirty;
        if (statMaint) statMaint.textContent = maintenance;
    }

    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. ROOM MATRIX FILTER TABS & SEARCH ---
        const filterButtons = document.querySelectorAll('.btn-room-filter');
        const searchInput = document.getElementById('room-search-input');
        const roomItems = document.querySelectorAll('.room-card-item');
        const filteredCountBadge = document.getElementById('filtered-room-count');

        let currentFilter = 'all';
        let searchQuery = '';

        function applyRoomFilters() {
            let visibleCount = 0;
            roomItems.forEach(item => {
                const itemStatus = item.dataset.status;
                const itemSearch = item.dataset.search || '';

                const matchesStatus = (currentFilter === 'all') ||
                    (currentFilter === 'clean' && (itemStatus === 'clean' || itemStatus === 'available')) ||
                    (currentFilter === 'occupied' && itemStatus === 'occupied') ||
                    (currentFilter === 'dirty' && (itemStatus === 'dirty' || itemStatus === 'cleaning')) ||
                    (currentFilter === 'maintenance' && itemStatus === 'maintenance');

                const matchesQuery = !searchQuery || itemSearch.includes(searchQuery);

                if (matchesStatus && matchesQuery) {
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
            btn.addEventListener('click', function() {
                filterButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentFilter = this.dataset.filter || 'all';
                applyRoomFilters();
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                searchQuery = this.value.trim().toLowerCase();
                applyRoomFilters();
            });
        }

        // --- 2. ROOM STATUS QUICK ACTIONS (.btn-set-room-status) ---
        const statusButtons = document.querySelectorAll('.btn-set-room-status');
        statusButtons.forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                const roomId = this.dataset.roomId;
                const roomName = this.dataset.roomName || ('Oda #' + roomId);
                const targetStatus = this.dataset.status;

                const originalBtnHtml = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                try {
                    const payload = {
                        room_id: roomId,
                        station_id: roomId,
                        status: targetStatus
                    };

                    const response = await fetch('<?= site_url('verticals/update_room_status') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload)
                    });

                    const json = await response.json().catch(() => ({}));

                    if (response.ok && (json.success || json.status)) {
                        // Dynamically update UI card
                        const cardItem = document.querySelector(`.room-card-item[data-room-id="${roomId}"]`);
                        if (cardItem) {
                            const newStatus = targetStatus === 'available' ? 'clean' : targetStatus;
                            cardItem.dataset.status = newStatus;

                            const roomCard = cardItem.querySelector('.room-card');
                            if (roomCard) {
                                roomCard.className = `card shadow-sm border-0 h-100 room-card status-${newStatus}`;
                            }

                            const badgeContainer = cardItem.querySelector('.room-status-badge-container');
                            if (badgeContainer) {
                                if (newStatus === 'clean') {
                                    badgeContainer.innerHTML = '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Temiz & Müsait</span>';
                                } else if (newStatus === 'occupied') {
                                    badgeContainer.innerHTML = '<span class="badge bg-danger"><i class="fas fa-user-check me-1"></i>Dolu / Misafir Konaklıyor</span>';
                                } else if (newStatus === 'dirty' || newStatus === 'cleaning') {
                                    badgeContainer.innerHTML = '<span class="badge bg-warning text-dark"><i class="fas fa-broom me-1"></i>Temizlik Bekliyor / Kirli</span>';
                                } else if (newStatus === 'maintenance') {
                                    badgeContainer.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-tools me-1"></i>Bakımda / Arızalı</span>';
                                }
                            }

                            // Update guest info box if emptied/vacated
                            const guestBox = cardItem.querySelector('.room-guest-container');
                            if (guestBox && (newStatus === 'clean' || newStatus === 'available')) {
                                guestBox.innerHTML = '<div class="p-2 rounded bg-light border border-success-subtle">' +
                                    '<div class="small text-success fw-semibold"><i class="fas fa-check-circle me-1"></i>Oda Hazır</div>' +
                                    '<small class="text-muted d-block">Tüm kontroller yapıldı, yeni misafir kabulüne hazır.</small>' +
                                    '</div>';
                            } else if (guestBox && (newStatus === 'dirty' || newStatus === 'cleaning')) {
                                guestBox.innerHTML = '<div class="p-2 rounded bg-light border border-warning-subtle">' +
                                    '<div class="small text-warning-emphasis fw-semibold"><i class="fas fa-broom me-1 text-warning"></i>Housekeeping Sırasında</div>' +
                                    '<small class="text-muted d-block">Kat hizmetleri temizlik sırasına alındı.</small>' +
                                    '</div>';
                            } else if (guestBox && newStatus === 'maintenance') {
                                guestBox.innerHTML = '<div class="p-2 rounded bg-light border border-secondary-subtle">' +
                                    '<div class="small text-secondary fw-semibold"><i class="fas fa-wrench me-1"></i>Teknik Servis Müdahalesi</div>' +
                                    '<small class="text-muted d-block">Teknik servis incelemesinde. Oda satışa kapalı.</small>' +
                                    '</div>';
                            }
                        }

                        updateRoomCounters();
                        applyRoomFilters();
                        showToast(`${escapeHtml(roomName)} durumu başarıyla güncellendi: ${escapeHtml(targetStatus)}`, 'success');
                    } else {
                        showToast('Hata: ' + escapeHtml(json.message || 'Durum güncellenemedi.'), 'error');
                    }
                } catch (err) {
                    showToast('Ağ hatası: ' + escapeHtml(err.message || 'Bağlantı kurulamadı.'), 'error');
                } finally {
                    this.disabled = false;
                    this.innerHTML = originalBtnHtml;
                }
            });
        });

        // --- 3. AUTO FILL GUEST WHEN ROOM SELECTED IN CHARGE MODAL ---
        const chargeRoomSelect = document.getElementById('charge-room-id');
        const chargeGuestSelect = document.getElementById('charge-guest-id');
        if (chargeRoomSelect && chargeGuestSelect) {
            chargeRoomSelect.addEventListener('change', function() {
                const selectedOpt = this.options[this.selectedIndex];
                const guestId = selectedOpt.dataset.guestId;
                if (guestId) {
                    chargeGuestSelect.value = guestId;
                }
            });
        }

        // --- 4. SUBMIT FORM: ADD EXTRA HARCAMA / FOLYO (#form-add-charge) ---
        const formAddCharge = document.getElementById('form-add-charge');
        if (formAddCharge) {
            formAddCharge.addEventListener('submit', async function(e) {
                e.preventDefault();
                const btnSubmit = document.getElementById('btn-submit-charge');
                const originalBtnHtml = btnSubmit ? btnSubmit.innerHTML : '';
                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Kaydediliyor...';
                }

                const fd = new FormData(this);
                const data = Object.fromEntries(fd.entries());
                data.amount = parseFloat(data.amount) || 0;
                data.station_id = parseInt(data.station_id, 10);
                data.room_id = data.station_id;
                data.guest_id = parseInt(data.guest_id, 10);

                const selectedRoomOpt = chargeRoomSelect ? chargeRoomSelect.options[chargeRoomSelect.selectedIndex] : null;
                const roomNumber = selectedRoomOpt ? (selectedRoomOpt.dataset.roomNumber || selectedRoomOpt.dataset.roomName || 'Oda') : 'Oda';
                const selectedGuestOpt = chargeGuestSelect ? chargeGuestSelect.options[chargeGuestSelect.selectedIndex] : null;
                const guestName = selectedGuestOpt ? (selectedGuestOpt.dataset.name || selectedGuestOpt.text) : 'Misafir';

                try {
                    const response = await fetch('<?= site_url('verticals/add_room_charge') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(data)
                    });

                    const json = await response.json().catch(() => ({}));

                    if (response.ok && (json.success || json.charge_id || json.id)) {
                        // Append dynamically to charges table
                        const tbody = document.getElementById('charges-tbody');
                        const emptyRow = document.getElementById('empty-charges-row');
                        if (emptyRow) emptyRow.remove();

                        let badgeClass = 'category-badge-other';
                        const cat = data.category || 'Diğer';
                        if (cat.toLowerCase().includes('minibar')) badgeClass = 'category-badge-minibar';
                        else if (cat.toLowerCase().includes('spa')) badgeClass = 'category-badge-spa';
                        else if (cat.toLowerCase().includes('transfer')) badgeClass = 'category-badge-transfer';
                        else if (cat.toLowerCase().includes('servis')) badgeClass = 'category-badge-roomservice';
                        else if (cat.toLowerCase().includes('çamaşır')) badgeClass = 'category-badge-laundry';

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td><span class="badge bg-dark fw-bold">${escapeHtml(roomNumber)}</span></td>
                            <td><div class="fw-semibold text-dark">${escapeHtml(guestName)}</div></td>
                            <td><div class="small text-dark fw-medium">${escapeHtml(data.item_name)}</div></td>
                            <td><span class="badge ${badgeClass} small">${escapeHtml(cat)}</span></td>
                            <td class="text-end fw-bold text-success">₺${data.amount.toFixed(2)}</td>
                            <td class="text-end text-muted small">Az önce</td>
                        `;
                        if (tbody) tbody.prepend(tr);

                        // Update badges
                        const countBadge = document.getElementById('folio-count-badge');
                        if (countBadge) {
                            const curCount = parseInt(countBadge.textContent, 10) || 0;
                            countBadge.textContent = (curCount + 1) + ' Kayıt';
                        }

                        // Close modal
                        const modalEl = document.getElementById('modal-add-charge');
                        if (modalEl) {
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        }
                        formAddCharge.reset();

                        showToast('Ekstra harcama folyoya başarıyla kaydedildi!', 'success');
                    } else {
                        showToast('Hata: ' + escapeHtml(json.message || 'Harcama kaydedilemedi.'), 'error');
                    }
                } catch (err) {
                    showToast('Ağ hatası: ' + escapeHtml(err.message || 'Bağlantı kurulamadı.'), 'error');
                } finally {
                    if (btnSubmit) {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = originalBtnHtml;
                    }
                }
            });
        }

        // --- 5. SUBMIT FORM: SAVE GUEST PREFERENCES (#form-guest-pref) ---
        const formGuestPref = document.getElementById('form-guest-pref');
        if (formGuestPref) {
            formGuestPref.addEventListener('submit', async function(e) {
                e.preventDefault();
                const btnSubmit = document.getElementById('btn-submit-guest-pref');
                const originalBtnHtml = btnSubmit ? btnSubmit.innerHTML : '';
                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Kaydediliyor...';
                }

                const fd = new FormData(this);
                const data = Object.fromEntries(fd.entries());
                data.customer_id = parseInt(data.customer_id, 10);
                data.id_users_customer = data.customer_id;

                const guestSelect = document.getElementById('pref-customer-id');
                const selectedOpt = guestSelect ? guestSelect.options[guestSelect.selectedIndex] : null;
                const guestName = selectedOpt ? (selectedOpt.dataset.name || selectedOpt.text) : 'Misafir';

                try {
                    const response = await fetch('<?= site_url('verticals/save_guest_preferences') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(data)
                    });

                    const json = await response.json().catch(() => ({}));

                    if (response.ok && (json.success || json.id || json.preferences)) {
                        // Append dynamically to preferences list
                        const prefContainer = document.getElementById('preferences-list-container');
                        const emptyMsg = document.getElementById('empty-pref-msg');
                        if (emptyMsg) emptyMsg.remove();

                        const firstChar = guestName ? guestName.charAt(0).toUpperCase() : 'M';
                        const div = document.createElement('div');
                        div.className = 'list-group-item p-3 border-start border-4 border-info';
                        div.innerHTML = `
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="guest-avatar">${escapeHtml(firstChar)}</span>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">${escapeHtml(guestName)}</h6>
                                        <small class="text-muted">${escapeHtml(data.floor_preference || 'Kat Tercihi Belirtilmedi')}</small>
                                    </div>
                                </div>
                                <span class="badge bg-warning text-dark fw-semibold">VIP</span>
                            </div>
                            <div class="small bg-light p-2 rounded">
                                <div class="mb-1">
                                    <strong class="text-secondary"><i class="fas fa-cloud me-1"></i>Yastık:</strong>
                                    <span class="text-dark fw-medium">${escapeHtml(data.pillow_choice || 'Standart')}</span>
                                </div>
                                <div class="mb-1">
                                    <strong class="text-secondary"><i class="fas fa-utensils me-1"></i>Diyet / Alerjen:</strong>
                                    <span class="badge bg-warning-subtle text-warning-emphasis">${escapeHtml(data.dietary_allergies || 'Yok')}</span>
                                </div>
                                ${data.special_notes ? `
                                    <div class="text-muted fst-italic pt-1 border-top mt-1" style="font-size: 0.8rem;">
                                        <i class="fas fa-quote-left me-1 text-primary"></i>${escapeHtml(data.special_notes)}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                        if (prefContainer) prefContainer.prepend(div);

                        // Close modal
                        const modalEl = document.getElementById('modal-guest-pref');
                        if (modalEl) {
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        }
                        formGuestPref.reset();

                        showToast(`${escapeHtml(guestName)} konaklama tercihleri kaydedildi!`, 'success');
                    } else {
                        showToast('Hata: ' + escapeHtml(json.message || 'Tercihler kaydedilemedi.'), 'error');
                    }
                } catch (err) {
                    showToast('Ağ hatası: ' + escapeHtml(err.message || 'Bağlantı kurulamadı.'), 'error');
                } finally {
                    if (btnSubmit) {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = originalBtnHtml;
                    }
                }
            });
        }
    });
    </script>
</body>
</html>
