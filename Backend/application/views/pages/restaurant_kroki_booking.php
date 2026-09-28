<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($company_name) ?> | Masa Rezervasyonu & İnteraktif Kroki</title>

    <link rel="stylesheet" href="<?= asset_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0f172a;
            --primary-accent: #2563eb;
            --gold: #d97706;
            --gold-light: #fef3c7;
            --emerald: #059669;
            --surface: #ffffff;
            --bg-canvas: #f8fafc;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
        }

        .font-serif {
            font-family: 'Playfair Display', serif;
        }

        .booking-hero {
            background: linear-gradient(135deg, #090d16 0%, #1e293b 100%);
            color: #ffffff;
            padding: 2.5rem 1rem 3.5rem;
            position: relative;
            overflow: hidden;
        }

        .booking-hero::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 24px;
            background: #f1f5f9;
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
        }

        .step-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.1);
            color: #94a3b8;
            transition: all 0.2s ease;
        }

        .step-pill.active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .step-pill.completed {
            background: #059669;
            color: #ffffff;
        }

        .time-slot-btn {
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #334155;
            font-weight: 600;
            border-radius: 10px;
            padding: 10px 14px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .time-slot-btn:hover {
            border-color: #2563eb;
            color: #2563eb;
            background: #eff6ff;
        }

        .time-slot-btn.active {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
        }

        .party-size-btn {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .party-size-btn:hover {
            border-color: #2563eb;
            color: #2563eb;
        }

        .party-size-btn.active {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.2);
        }

        /* 2D Floor Kroki Canvas */
        #interactive-kroki-canvas {
            min-height: 540px;
            background-color: #f8fafc;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 22px 22px;
            position: relative;
            overflow: auto;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }

        .booking-table {
            position: absolute;
            background: #ffffff;
            border: 2px solid #10b981;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            z-index: 10;
        }

        .booking-table:hover {
            transform: scale(1.05) !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            border-color: #2563eb;
            z-index: 20;
        }

        .booking-table.selected {
            background: #f0fdf4 !important;
            border-color: #059669 !important;
            border-width: 3px !important;
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.2), 0 8px 24px rgba(5, 150, 105, 0.25) !important;
            z-index: 30;
        }

        .booking-table.occupied {
            background: #f1f5f9 !important;
            border-color: #cbd5e1 !important;
            opacity: 0.6;
            cursor: not-allowed;
            filter: grayscale(1);
        }

        .booking-table.occupied:hover {
            transform: none !important;
            box-shadow: none !important;
        }

        .patron-recognition-card {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border: 1px solid #fde68a;
            border-radius: 16px;
        }

        .quick-chip {
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #475569;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .quick-chip:hover, .quick-chip.active {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
        }
    </style>
</head>
<body>

    <!-- Hero Header -->
    <header class="booking-hero">
        <div class="container text-center">
            <span class="badge bg-white-subtle text-white border border-white-subtle rounded-pill px-3 py-1 mb-2">
                <i class="fas fa-utensils me-1"></i> Masa Rezervasyonu & İnteraktif Kroki
            </span>
            <h1 class="display-6 font-serif fw-bold mb-1"><?= e($company_name) ?></h1>
            <p class="text-white-50 small mb-4">Masanızı mimari kroki üzerinden dilediğiniz gibi seçin veya akıllı sistemimize bırakın.</p>

            <!-- Steps Nav -->
            <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
                <div class="step-pill active" id="step-pill-1"><i class="fas fa-calendar me-1"></i> 1. Tarih & Kişi</div>
                <i class="fas fa-chevron-right text-white-50 small"></i>
                <div class="step-pill" id="step-pill-2"><i class="fas fa-chair me-1"></i> 2. Masa Seçimi</div>
                <i class="fas fa-chevron-right text-white-50 small"></i>
                <div class="step-pill" id="step-pill-3"><i class="fas fa-check-circle me-1"></i> 3. Onay & Bilgiler</div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="container pb-5" style="margin-top: -10px;">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-11">

                <!-- STEP 1: DATE, TIME & PARTY SIZE -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white" id="step-1-card">
                    <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-calendar-alt text-primary me-2"></i>1. Ne Zaman ve Kaç Kişi Geleceksiniz?</h5>

                    <div class="row g-4">
                        <!-- Date Picker -->
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-bold text-muted">Rezervasyon Tarihi</label>
                            <input type="date" id="booking-date" class="form-control form-control-lg rounded-3" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" onchange="updateDateTimeSummary()">
                        </div>

                        <!-- Party Size -->
                        <div class="col-12 col-md-8">
                            <label class="form-label small fw-bold text-muted">Kişi Sayısı</label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <button type="button" class="party-size-btn <?= $i === 2 ? 'active' : '' ?>" onclick="selectPartySize(<?= $i ?>, this)">
                                        <?= $i ?>
                                    </button>
                                <?php endfor; ?>
                                <button type="button" class="party-size-btn" onclick="selectPartySize(10, this)">10</button>
                                <button type="button" class="party-size-btn" onclick="selectPartySize(12, this)">12+</button>
                            </div>
                            <input type="hidden" id="booking-party-size" value="2">
                        </div>
                    </div>

                    <!-- Time Slots -->
                    <div class="mt-4">
                        <label class="form-label small fw-bold text-muted">Uygun Saatler</label>
                        <div class="d-flex flex-wrap gap-2" id="time-slots-container">
                            <?php
                            $times = ['12:30', '13:00', '13:30', '18:00', '18:30', '19:00', '19:30', '20:00', '20:30', '21:00', '21:30'];
                            foreach ($times as $idx => $tm):
                            ?>
                                <button type="button" class="time-slot-btn <?= $tm === '19:30' ? 'active' : '' ?>" onclick="selectTimeSlot('<?= $tm ?>', this)">
                                    <i class="far fa-clock me-1"></i><?= $tm ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="booking-time" value="19:30">
                    </div>

                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold" onclick="goToStep(2)">
                            Masa Seçimine Geç <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 2: INTERACTIVE KROKI OR SMART AUTO SEATING -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white d-none" id="step-2-card">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 border-bottom pb-3">
                        <div>
                            <h5 class="fw-bold mb-1 text-dark"><i class="fas fa-map-marker-alt text-primary me-2"></i>2. Masanızı Belirleyin</h5>
                            <p class="text-muted small mb-0">İster interaktif plandan yerinizi seçin, ister sistemimize en ideal masayı buldurun.</p>
                        </div>

                        <!-- Mode Switcher -->
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-primary fw-semibold" id="btn-mode-picker" onclick="switchSeatingMode('picker')">
                                <i class="fas fa-hand-pointer me-1"></i> Krokiden Seç
                            </button>
                            <button type="button" class="btn btn-outline-primary fw-semibold" id="btn-mode-smart" onclick="switchSeatingMode('smart')">
                                <i class="fas fa-magic me-1"></i> Otomatik Ata (Akıllı AI)
                            </button>
                        </div>
                    </div>

                    <!-- Smart Auto Seating Banner (When Smart Mode is chosen) -->
                    <div id="smart-seating-banner" class="alert alert-primary rounded-3 p-3 mb-3 d-none">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-1"><i class="fas fa-robot text-primary me-2"></i>Yapay Zeka Akıllı Masa Atama</h6>
                                <p class="small text-muted mb-0" id="smart-recommendation-text">Kişi sayınıza ve rezervasyon saatine göre en keyifli masa analiz ediliyor...</p>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm fw-bold px-3 py-2 rounded-pill" onclick="triggerSmartAssignment()">
                                <i class="fas fa-bolt me-1"></i> En Uygun Masayı Bul
                            </button>
                        </div>
                    </div>

                    <!-- Section Filter Toolbar -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 bg-light p-2 rounded-3 border">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-dark active" onclick="filterKrokiSection('all', this)">Tüm Bölümler</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="filterKrokiSection('Ana Salon', this)">Ana Salon</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="filterKrokiSection('Teras', this)">Teras (Boğaz/Manzara)</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="filterKrokiSection('Bahçe', this)">Bahçe</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="filterKrokiSection('VIP', this)">VIP Loca</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="filterKrokiSection('Bar', this)">Bar</button>
                        </div>

                        <!-- Legend -->
                        <div class="d-flex gap-3 small align-items-center text-muted">
                            <span><span class="badge bg-success rounded-circle p-1 me-1">&nbsp;</span> Müsait Masa</span>
                            <span><span class="badge bg-secondary rounded-circle p-1 me-1">&nbsp;</span> Dolu / Rezerve</span>
                            <span><i class="fas fa-crown text-warning me-1"></i> VIP Masa</span>
                        </div>
                    </div>

                    <!-- 2D Kroki Floor Plan View -->
                    <div id="interactive-kroki-canvas">
                        <!-- Architectural Layout Elements -->
                        <?php if (!empty($layout_elements)): ?>
                            <?php foreach ($layout_elements as $elem): ?>
                                <?php
                                $elem_type = $elem['element_type'];
                                $elem_rot = (int) ($elem['rotation'] ?? 0);
                                $elem_w = (int) ($elem['width'] ?: 100);
                                $elem_h = (int) ($elem['height'] ?: 40);
                                $elem_x = (int) ($elem['pos_x'] ?: 0);
                                $elem_y = (int) ($elem['pos_y'] ?: 0);

                                $custom_style = "position: absolute; left: {$elem_x}px; top: {$elem_y}px; width: {$elem_w}px; height: {$elem_h}px; transform: rotate({$elem_rot}deg); transform-origin: center center; z-index: 5; pointer-events: none;";
                                $class_names = "kroki-arch-element rounded d-flex align-items-center justify-content-center text-center p-1 user-select-none";

                                if ($elem_type === 'wall') {
                                    $class_names .= " bg-dark text-white border border-secondary shadow-sm";
                                } elseif ($elem_type === 'window') {
                                    $class_names .= " border border-2 border-info shadow-sm";
                                    $custom_style .= " background: rgba(56, 189, 248, 0.15); backdrop-filter: blur(2px); border-style: dashed !important;";
                                } elseif ($elem_type === 'door') {
                                    $class_names .= " bg-light border border-2 border-dark text-dark fw-bold shadow-sm";
                                } elseif ($elem_type === 'bar_counter') {
                                    $class_names .= " text-white shadow fw-bold";
                                    $custom_style .= " background: linear-gradient(135deg, #78350f, #b45309); border: 2px solid #d97706; border-radius: 12px;";
                                } elseif ($elem_type === 'stage') {
                                    $class_names .= " text-white shadow fw-bold";
                                    $custom_style .= " background: linear-gradient(135deg, #1e293b, #334155); border: 2px dashed #94a3b8; border-radius: 10px;";
                                } elseif ($elem_type === 'plant') {
                                    $class_names .= " bg-success-subtle text-success border border-success rounded-circle shadow-sm";
                                } else {
                                    $class_names .= " bg-light text-muted border shadow-sm";
                                }
                                ?>
                                <div class="<?= $class_names ?>" data-section="<?= e($elem['section']) ?>" style="<?= $custom_style ?>">
                                    <div class="small fw-semibold text-truncate px-1" style="font-size: 11px;">
                                        <?php if ($elem_type === 'bar_counter'): ?>
                                            <i class="fas fa-cocktail me-1"></i>
                                        <?php elseif ($elem_type === 'stage'): ?>
                                            <i class="fas fa-music me-1"></i>
                                        <?php elseif ($elem_type === 'window'): ?>
                                            <i class="fas fa-water text-info me-1"></i>
                                        <?php elseif ($elem_type === 'plant'): ?>
                                            <i class="fas fa-seedling text-success me-1"></i>
                                        <?php endif; ?>
                                        <?= e($elem['label']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Tables -->
                        <?php foreach ($tables as $t): ?>
                            <?php
                            $is_occupied = in_array($t['status'], ['seated', 'dining', 'bill_requested']);
                            $shape = $t['shape'] ?: 'rectangle';
                            $rotation = (int) ($t['rotation'] ?? 0);
                            $is_vip = (int) ($t['is_vip_only'] ?? 0);
                            $min_spend = (float) ($t['min_spend'] ?? 0);

                            $shape_css = "";
                            if ($shape === 'round') {
                                $shape_css = "border-radius: 50% !important;";
                            } elseif ($shape === 'booth') {
                                $shape_css = "border-radius: 18px 18px 8px 8px !important; border-top: 6px solid " . ($is_vip ? '#eab308' : '#6366f1') . " !important;";
                            } elseif ($shape === 'bistro') {
                                $shape_css = "border-radius: 50% !important;";
                            } else {
                                $shape_css = "border-radius: 12px !important;";
                            }
                            ?>
                            <div class="booking-table <?= $is_occupied ? 'occupied' : '' ?>"
                                 id="kroki-table-<?= $t['id'] ?>"
                                 data-id="<?= $t['id'] ?>"
                                 data-number="<?= e($t['table_number']) ?>"
                                 data-section="<?= e($t['section']) ?>"
                                 data-capacity="<?= $t['capacity'] ?>"
                                 data-occupied="<?= $is_occupied ? 1 : 0 ?>"
                                 data-vip="<?= $is_vip ?>"
                                 data-min-spend="<?= $min_spend ?>"
                                 style="left: <?= $t['pos_x'] ?>px; top: <?= $t['pos_y'] ?>px; width: <?= $t['width'] ?: 110 ?>px; height: <?= $t['height'] ?: 85 ?>px; transform: rotate(<?= $rotation ?>deg); transform-origin: center center; <?= $shape_css ?>"
                                 onclick="selectTableOnKroki(<?= $t['id'] ?>)">

                                <div class="d-flex w-100 justify-content-between align-items-center" style="pointer-events: none;">
                                    <span class="badge <?= $is_occupied ? 'bg-secondary' : 'bg-dark' ?> rounded-pill" style="font-size: 10px;">
                                        M<?= e($t['table_number']) ?>
                                    </span>
                                    <?php if ($is_vip): ?>
                                        <i class="fas fa-crown text-warning" title="VIP Loca" style="font-size: 10px;"></i>
                                    <?php endif; ?>
                                </div>

                                <div class="text-center my-auto" style="pointer-events: none;">
                                    <?php if ($is_occupied): ?>
                                        <small class="text-muted fw-bold" style="font-size: 10px;">Dolu</small>
                                    <?php else: ?>
                                        <div class="small fw-bold text-success" style="font-size: 11px;">Müsait</div>
                                        <small class="text-muted" style="font-size: 9px;"><i class="fas fa-users"></i> <?= $t['capacity'] ?> Kişi</small>
                                    <?php endif; ?>
                                </div>

                                <div class="w-100 text-center" style="font-size: 8px; pointer-events: none;">
                                    <span class="text-muted text-truncate d-block"><?= e($t['section']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Selected Table Banner -->
                    <div id="selected-table-banner" class="alert alert-success d-flex flex-wrap justify-content-between align-items-center mt-3 p-3 rounded-3 d-none">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success text-white rounded-circle p-2 px-3 fw-bold fs-5" id="banner-table-badge">
                                M1
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-success" id="banner-table-title">Masa 1 Seçildi</h6>
                                <small class="text-muted" id="banner-table-details">Ana Salon • 4 Kişilik Kapasite</small>
                            </div>
                        </div>
                        <input type="hidden" id="selected-table-id" value="">
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" onclick="goToStep(1)">
                            <i class="fas fa-arrow-left me-2"></i> Geri
                        </button>
                        <button type="button" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold" id="btn-to-step-3" onclick="goToStep(3)">
                            İletişim Bilgilerine Geç <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 3: GUEST INFORMATION & PATRON INTELLIGENCE RECOGNITION -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white d-none" id="step-3-card">
                    <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-user-check text-primary me-2"></i>3. Misafir Bilgileri & Özel İstekler</h5>

                    <!-- Müdavim Recognition Banner (Dynamically shown on phone lookup) -->
                    <div id="patron-recognized-card" class="patron-recognition-card p-3 mb-4 d-none">
                        <div class="d-flex align-items-start gap-3">
                            <div class="bg-warning text-dark rounded-circle p-3 d-flex align-items-center justify-content-center shadow-sm">
                                <i class="fas fa-crown fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark" id="patron-welcome-title">Hoş Geldiniz Sayın Müdavimimiz!</h6>
                                <p class="small text-muted mb-2" id="patron-welcome-text">
                                    Restoranımızın değerli müdavimi olarak sizi tekrar ağırlamaktan mutluluk duyuyoruz.
                                </p>
                                <div class="badge bg-warning text-dark border border-warning-subtle" id="patron-treat-badge">
                                    <i class="fas fa-gift me-1"></i> İkramınız Hazır
                                </div>
                            </div>
                        </div>
                    </div>

                    <form id="kroki-booking-form">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold">Telefon Numarası *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-phone"></i></span>
                                    <input type="tel" name="phone_number" id="guest-phone" class="form-control" placeholder="05XXXXXXXXX" required onblur="checkPatronRecognition(this.value)">
                                </div>
                                <small class="text-muted" style="font-size: 11px;">Müdavim misafirlerimiz otomatik tanınır ve favori masa önceliği atanır.</small>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold">E-posta Adresi</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" id="guest-email" class="form-control" placeholder="ad@ornek.com">
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold">Adınız *</label>
                                <input type="text" name="first_name" id="guest-first-name" class="form-control" placeholder="Adınız" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold">Soyadınız *</label>
                                <input type="text" name="last_name" id="guest-last-name" class="form-control" placeholder="Soyadınız" required>
                            </div>
                        </div>

                        <!-- Special Request Quick Chips -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Özel Tercihler & Talepler</label>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="quick-chip" onclick="toggleQuickChip(this, 'Pencere Kenarı')">🪟 Pencere Kenarı</span>
                                <span class="quick-chip" onclick="toggleQuickChip(this, 'Yıldönümü / Kutlama')">🎉 Kutlama / Yıldönümü</span>
                                <span class="quick-chip" onclick="toggleQuickChip(this, 'Sakin Masa')">🤫 Sakin & Sessiz Masa</span>
                                <span class="quick-chip" onclick="toggleQuickChip(this, 'Mama Sandalyesi')">👶 Mama Sandalyesi</span>
                                <span class="quick-chip" onclick="toggleQuickChip(this, 'Gluten Hassasiyeti')">🌾 Glutensiz</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Şefe veya Servis Ekibine Özel Not</label>
                            <textarea name="notes" id="guest-notes" class="form-control" rows="2" placeholder="Özel kutlama, alerjen veya masa konumu ile ilgili taleplerinizi buraya yazabilirsiniz..."></textarea>
                        </div>

                        <!-- Summary Card Before Submission -->
                        <div class="p-3 bg-light rounded-3 border mb-4">
                            <div class="row g-2 align-items-center small">
                                <div class="col-6 col-md-3">
                                    <span class="text-muted d-block">Tarih:</span>
                                    <strong id="summary-date">-</strong>
                                </div>
                                <div class="col-6 col-md-3">
                                    <span class="text-muted d-block">Saat:</span>
                                    <strong id="summary-time">-</strong>
                                </div>
                                <div class="col-6 col-md-3">
                                    <span class="text-muted d-block">Misafir:</span>
                                    <strong id="summary-party">-</strong>
                                </div>
                                <div class="col-6 col-md-3">
                                    <span class="text-muted d-block">Seçilen Masa:</span>
                                    <strong class="text-success" id="summary-table">-</strong>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-4" onclick="goToStep(2)">
                                <i class="fas fa-arrow-left me-2"></i> Masayı Değiştir
                            </button>
                            <button type="button" class="btn btn-success btn-lg rounded-pill px-5 fw-bold shadow" onclick="submitReservation()">
                                <i class="fas fa-check-circle me-2"></i> Rezervasyonu Onayla
                            </button>
                        </div>
                    </form>
                </div>

                <!-- SUCCESS CONFIRMATION MODAL / SCREEN -->
                <div class="card border-0 shadow-lg rounded-4 p-5 text-center bg-white d-none" id="booking-success-card">
                    <div class="mb-3">
                        <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="fas fa-check fa-3x"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold font-serif text-dark mb-1">Rezervasyonunuz Alındı!</h3>
                    <p class="text-muted small mb-4" id="success-welcome-msg">Sizi restoranımızda ağırlamaktan onur duyacağız.</p>

                    <div class="card bg-light border p-3 rounded-3 max-w-md mx-auto mb-4 text-start" style="max-width: 480px; margin: 0 auto;">
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <span class="text-muted small">Rezervasyon No:</span>
                            <strong class="text-dark" id="success-res-id">#RES-001</strong>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <span class="text-muted small">Tarih & Saat:</span>
                            <strong class="text-dark" id="success-res-datetime">-</strong>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <span class="text-muted small">Kişi Sayısı:</span>
                            <strong class="text-dark" id="success-res-party">-</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Masa & Bölüm:</span>
                            <strong class="text-success" id="success-res-table">-</strong>
                        </div>
                    </div>

                    <p class="text-muted small mb-3">Detaylar SMS ve E-posta olarak tarafınıza iletilmiştir.</p>
                    <a href="<?= site_url('restaurant/kroki_booking') ?>" class="btn btn-outline-dark rounded-pill px-4">
                        Yeni Rezervasyon Yap
                    </a>
                </div>

            </div>
        </div>
    </main>

    <script src="<?= asset_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
    <script>
    let selectedTableId = null;
    let selectedTableNumber = null;
    let selectedTableSection = null;
    let isPatronPriority = false;

    function goToStep(step) {
        if (step === 2) {
            updateDateTimeSummary();
        } else if (step === 3) {
            if (!selectedTableId) {
                alert('Lütfen bir masa seçin veya "Otomatik Ata" modunu kullanın.');
                return;
            }
            updateFinalSummary();
        }

        document.getElementById('step-1-card').classList.add('d-none');
        document.getElementById('step-2-card').classList.add('d-none');
        document.getElementById('step-3-card').classList.add('d-none');

        document.getElementById('step-pill-1').classList.remove('active', 'completed');
        document.getElementById('step-pill-2').classList.remove('active', 'completed');
        document.getElementById('step-pill-3').classList.remove('active', 'completed');

        if (step === 1) {
            document.getElementById('step-1-card').classList.remove('d-none');
            document.getElementById('step-pill-1').classList.add('active');
        } else if (step === 2) {
            document.getElementById('step-2-card').classList.remove('d-none');
            document.getElementById('step-pill-1').classList.add('completed');
            document.getElementById('step-pill-2').classList.add('active');
        } else if (step === 3) {
            document.getElementById('step-3-card').classList.remove('d-none');
            document.getElementById('step-pill-1').classList.add('completed');
            document.getElementById('step-pill-2').classList.add('completed');
            document.getElementById('step-pill-3').classList.add('active');
        }
        window.scrollTo({ top: 120, behavior: 'smooth' });
    }

    function selectPartySize(size, btn) {
        document.querySelectorAll('.party-size-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('booking-party-size').value = size;
        updateDateTimeSummary();
    }

    function selectTimeSlot(time, btn) {
        document.querySelectorAll('.time-slot-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('booking-time').value = time;
        updateDateTimeSummary();
    }

    function updateDateTimeSummary() {
        const dt = document.getElementById('booking-date').value;
        const tm = document.getElementById('booking-time').value;
        const ps = document.getElementById('booking-party-size').value;
        document.getElementById('summary-date').innerText = dt;
        document.getElementById('summary-time').innerText = tm;
        document.getElementById('summary-party').innerText = ps + ' Kişi';
    }

    function updateFinalSummary() {
        updateDateTimeSummary();
        document.getElementById('summary-table').innerText = (selectedTableNumber ? 'Masa ' + selectedTableNumber + ' (' + selectedTableSection + ')' : 'Otomatik Atama');
    }

    // Kroki Seating Mode
    function switchSeatingMode(mode) {
        const btnPicker = document.getElementById('btn-mode-picker');
        const btnSmart = document.getElementById('btn-mode-smart');
        const banner = document.getElementById('smart-seating-banner');

        if (mode === 'smart') {
            btnSmart.classList.add('btn-primary');
            btnSmart.classList.remove('btn-outline-primary');
            btnPicker.classList.remove('btn-primary');
            btnPicker.classList.add('btn-outline-primary');
            banner.classList.remove('d-none');
            triggerSmartAssignment();
        } else {
            btnPicker.classList.add('btn-primary');
            btnPicker.classList.remove('btn-outline-primary');
            btnSmart.classList.remove('btn-primary');
            btnSmart.classList.add('btn-outline-primary');
            banner.classList.add('d-none');
        }
    }

    function filterKrokiSection(section, btn) {
        document.querySelectorAll('#step-2-card .btn-group .btn').forEach(b => b.classList.remove('active', 'btn-dark'));
        btn.classList.add('active', 'btn-dark');

        document.querySelectorAll('.booking-table').forEach(t => {
            if (section === 'all' || t.dataset.section === section) {
                t.style.display = 'flex';
            } else {
                t.style.display = 'none';
            }
        });

        document.querySelectorAll('.kroki-arch-element').forEach(el => {
            if (section === 'all' || el.dataset.section === section) {
                el.style.display = 'flex';
            } else {
                el.style.display = 'none';
            }
        });
    }

    function selectTableOnKroki(tableId) {
        const tableEl = document.getElementById('kroki-table-' + tableId);
        if (!tableEl || parseInt(tableEl.dataset.occupied, 10) === 1) return;

        document.querySelectorAll('.booking-table').forEach(t => t.classList.remove('selected'));
        tableEl.classList.add('selected');

        selectedTableId = tableId;
        selectedTableNumber = tableEl.dataset.number;
        selectedTableSection = tableEl.dataset.section;

        document.getElementById('selected-table-id').value = tableId;
        document.getElementById('banner-table-badge').innerText = 'M' + selectedTableNumber;
        document.getElementById('banner-table-title').innerText = 'Masa ' + selectedTableNumber + ' Seçildi';
        document.getElementById('banner-table-details').innerText = selectedTableSection + ' • ' + tableEl.dataset.capacity + ' Kişilik';
        document.getElementById('selected-table-banner').classList.remove('d-none');
    }

    function triggerSmartAssignment() {
        const partySize = document.getElementById('booking-party-size').value;
        const dt = document.getElementById('booking-date').value + ' ' + document.getElementById('booking-time').value;

        fetch('<?= site_url('restaurant/api/auto_assign') ?>?party_size=' + partySize + '&datetime=' + encodeURIComponent(dt))
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.table) {
                    selectTableOnKroki(data.table.id);
                    document.getElementById('smart-recommendation-text').innerHTML = `
                        <strong>Masa ${data.table.table_number}</strong> (${data.table.section} - ${data.table.capacity} Kişilik) sizin için tahsis edildi!
                        ${data.is_patron_favorite ? '<span class="badge bg-warning text-dark ms-2">★ Müdavim Tercihi</span>' : ''}
                    `;
                } else {
                    document.getElementById('smart-recommendation-text').innerText = 'Seçilen saat ve kişi sayısına uygun masa bulunamadı. Lütfen saati değiştirin.';
                }
            });
    }

    // Phone Patron Lookup
    function checkPatronRecognition(phone) {
        phone = phone.trim();
        if (phone.length < 7) return;

        fetch('<?= site_url('restaurant/api/lookup_patron') ?>?q=' + encodeURIComponent(phone))
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.match) {
                    const c = data.match;
                    const p = c.patron_profile;

                    document.getElementById('guest-first-name').value = c.first_name || '';
                    document.getElementById('guest-last-name').value = c.last_name || '';
                    if (c.email) document.getElementById('guest-email').value = c.email;

                    if (p) {
                        isPatronPriority = true;
                        const card = document.getElementById('patron-recognized-card');
                        card.classList.remove('d-none');
                        document.getElementById('patron-welcome-title').innerText = 'Hoş Geldiniz, ' + c.full_name + '!';

                        let note = `Restoranımızın değerli müdavimi (${p.patronage_tier}) olarak ${p.total_visits} kez bizi ziyaret ettiniz. `;
                        if (p.favorite_table_id) {
                            note += `Favori masanız öncelikli olarak kontrol edildi.`;
                            // auto select favorite table if available
                            selectTableOnKroki(p.favorite_table_id);
                        }
                        document.getElementById('patron-welcome-text').innerText = note;

                        if (p.welcome_treat_pref) {
                            document.getElementById('patron-treat-badge').innerHTML = '<i class="fas fa-gift me-1"></i> İkramınız: ' + p.welcome_treat_pref;
                        }
                    }
                }
            });
    }

    function toggleQuickChip(chip, text) {
        chip.classList.toggle('active');
        const notes = document.getElementById('guest-notes');
        let current = notes.value.trim();

        if (chip.classList.contains('active')) {
            notes.value = current ? current + ', ' + text : text;
        } else {
            notes.value = current.replace(text, '').replace(', ,', ',').replace(/^,\s*/, '').replace(/,\s*$/, '');
        }
    }

    function submitReservation() {
        const firstName = document.getElementById('guest-first-name').value.trim();
        const lastName = document.getElementById('guest-last-name').value.trim();
        const phone = document.getElementById('guest-phone').value.trim();
        const email = document.getElementById('guest-email').value.trim();

        if (!firstName || !lastName || !phone) {
            alert('Lütfen ad, soyad ve telefon bilgilerinizi eksiksiz doldurun.');
            return;
        }

        const date = document.getElementById('booking-date').value;
        const time = document.getElementById('booking-time').value;
        const partySize = document.getElementById('booking-party-size').value;
        const notes = document.getElementById('guest-notes').value;

        const payload = {
            id_restaurant_tables: selectedTableId,
            guest_first_name: firstName,
            guest_last_name: lastName,
            guest_phone: phone,
            guest_email: email,
            reservation_datetime: date + ' ' + time + ':00',
            party_size: partySize,
            special_requests: notes,
            notes: notes,
            is_customer_selected_table: 1,
            is_patron_priority: isPatronPriority ? 1 : 0
        };

        const fd = new FormData();
        Object.keys(payload).forEach(k => fd.append(k, payload[k]));

        fetch('<?= site_url('restaurant/save_reservation') ?>', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' || data.id) {
                    document.getElementById('step-3-card').classList.add('d-none');
                    document.getElementById('booking-success-card').classList.remove('d-none');

                    document.getElementById('success-res-id').innerText = '#RES-' + (data.id || Math.floor(1000 + Math.random() * 9000));
                    document.getElementById('success-res-datetime').innerText = date + ' ' + time;
                    document.getElementById('success-res-party').innerText = partySize + ' Kişi';
                    document.getElementById('success-res-table').innerText = 'Masa ' + selectedTableNumber + ' (' + selectedTableSection + ')';
                    window.scrollTo({ top: 50, behavior: 'smooth' });
                } else {
                    alert(data.message || 'Rezervasyon kaydedilemedi. Lütfen tekrar deneyin.');
                }
            })
            .catch(err => {
                alert('Rezervasyon sırasında bağlantı hatası oluştu.');
            });
    }
    </script>
</body>
</html>
