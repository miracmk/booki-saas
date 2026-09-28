<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>
<?php
/**
 * @var array $tables
 * @var array $layout_elements
 * @var array $reservations
 * @var array $waitlist
 * @var array $experiences
 * @var array $staff_members
 * @var array $patrons
 */
?>
<div class="container-fluid py-3" id="restaurant-floor-plan-page">
    <!-- Top Action & Navigation Bar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 bg-white p-3 rounded-3 shadow-sm border">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h4 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-layer-group text-primary me-2"></i>Restoran Masa Planı & Canlı Kroki
                </h4>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">2D Kroki Motoru</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                İnteraktif mimari krokiyi yönetin, masaları sürükleyin, müdavimleri ve canlı adisyonları anlık takip edin.
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Mode Switcher -->
            <div class="btn-group" role="group" id="floor-mode-group">
                <input type="radio" class="btn-check" name="floor_mode" id="mode-live" value="live" checked autocomplete="off" onchange="toggleFloorMode('live')">
                <label class="btn btn-outline-primary fw-semibold" for="mode-live">
                    <i class="fas fa-play-circle me-1"></i> Canlı Operasyon
                </label>

                <input type="radio" class="btn-check" name="floor_mode" id="mode-edit" value="edit" autocomplete="off" onchange="toggleFloorMode('edit')">
                <label class="btn btn-outline-secondary fw-semibold" for="mode-edit">
                    <i class="fas fa-pencil-ruler me-1"></i> Kroki Düzenleme
                </label>
            </div>

            <div class="vr mx-1 d-none d-md-block"></div>

            <!-- Quick Auto-Assign Simulation Button -->
            <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#auto-assign-modal" title="Yapay zeka akıllı masa atama simülatörü">
                <i class="fas fa-magic me-1"></i> Akıllı Masa Atama
            </button>

            <!-- Table Pushing / Combining Button -->
            <button class="btn btn-outline-warning text-dark" data-bs-toggle="modal" data-bs-target="#combine-tables-modal" title="Masaları birbirine iterek birleştirin">
                <i class="fas fa-object-group me-1"></i> Masa Birleştir
            </button>

            <!-- Add Architectural Element Button (visible in edit mode) -->
            <button class="btn btn-outline-dark" id="btn-add-layout-element" data-bs-toggle="modal" data-bs-target="#layout-element-modal">
                <i class="fas fa-shapes me-1"></i> Mimari Eleman Ekle
            </button>

            <!-- New Table Modal Button -->
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#new-table-modal">
                <i class="fas fa-plus me-1"></i> Yeni Masa
            </button>

            <!-- Navigation Links -->
            <a href="<?= site_url('restaurant/guest_patronage') ?>" class="btn btn-warning text-dark fw-semibold" title="Müdavim Misafir Zekası">
                <i class="fas fa-crown me-1"></i> Müdavimler
            </a>
            <a href="<?= site_url('restaurant/kroki_booking') ?>" target="_blank" class="btn btn-outline-secondary" title="Müşteri Krokisini Canlı Aç">
                <i class="fas fa-external-link-alt"></i>
            </a>
        </div>
    </div>

    <!-- Section Filter & Live Status Indicators -->
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <!-- Section Filter Tabs -->
            <div class="btn-group btn-group-sm" role="group" id="section-filter-group">
                <button type="button" class="btn btn-dark active" onclick="filterSection('all', this)">
                    <i class="fas fa-th-large me-1"></i> Tüm Bölümler
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterSection('Ana Salon', this)">
                    <i class="fas fa-door-open me-1"></i> Ana Salon
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterSection('Teras', this)">
                    <i class="fas fa-sun me-1"></i> Teras
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterSection('Bahçe', this)">
                    <i class="fas fa-leaf me-1"></i> Bahçe
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterSection('VIP', this)">
                    <i class="fas fa-gem me-1"></i> VIP Loca
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterSection('Bar', this)">
                    <i class="fas fa-cocktail me-1"></i> Bar
                </button>
            </div>

            <!-- Mode Notice Badge -->
            <div id="mode-notice-banner" class="small fw-semibold text-muted d-flex align-items-center gap-2">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                    <i class="fas fa-check-circle me-1"></i>Canlı Masa Operasyonu Aktif
                </span>
                <span class="text-muted small">Masaya tıklayarak sipariş/adisyon veya durum yönetin.</span>
            </div>

            <!-- Status Legend -->
            <div class="d-flex flex-wrap gap-3 small align-items-center">
                <span><span class="badge bg-success rounded-circle p-1 me-1">&nbsp;</span> Boş / Müsait</span>
                <span><span class="badge bg-primary rounded-circle p-1 me-1">&nbsp;</span> Oturuldu</span>
                <span><span class="badge bg-warning rounded-circle p-1 me-1">&nbsp;</span> Hesap İstendi</span>
                <span><span class="badge bg-info rounded-circle p-1 me-1">&nbsp;</span> Rezerve</span>
                <span><span class="badge bg-secondary rounded-circle p-1 me-1">&nbsp;</span> Temizlik</span>
                <span><i class="fas fa-crown text-warning me-1"></i> VIP Müdavim</span>
            </div>
        </div>
    </div>

    <!-- Main Floor Plan Workspace -->
    <div class="row g-3">
        <!-- Floor Canvas Container -->
        <div class="col-12 col-xl-9">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold small text-dark"><i class="fas fa-map-marked-alt text-primary me-2"></i>Mimari Kroki & Masa Yerleşimi</span>
                        <span id="canvas-active-section-badge" class="badge bg-light text-dark border">Tüm Bölümler</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted"><i class="fas fa-arrows-alt me-1"></i>Kroki Modunda masaları ve mimari elemanları sürükleyebilirsiniz.</small>
                    </div>
                </div>

                <!-- 2D Kroki Canvas -->
                <div class="card-body p-0 position-relative" style="min-height: 640px; background-color: #f8fafc; background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 24px 24px; overflow: auto;" id="floor-plan-canvas">

                    <!-- ARCHITECTURAL LAYOUT ELEMENTS -->
                    <?php if (!empty($layout_elements)): ?>
                        <?php foreach ($layout_elements as $elem): ?>
                            <?php
                            $elem_type = $elem['element_type'];
                            $elem_rot = (int) ($elem['rotation'] ?? 0);
                            $elem_w = (int) ($elem['width'] ?: 100);
                            $elem_h = (int) ($elem['height'] ?: 40);
                            $elem_x = (int) ($elem['pos_x'] ?: 0);
                            $elem_y = (int) ($elem['pos_y'] ?: 0);

                            // Unique styling for each architectural type
                            $custom_style = "position: absolute; left: {$elem_x}px; top: {$elem_y}px; width: {$elem_w}px; height: {$elem_h}px; transform: rotate({$elem_rot}deg); transform-origin: center center; z-index: 5;";
                            $class_names = "layout-element user-select-none transition-all rounded d-flex align-items-center justify-content-center text-center p-1";

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
                            } elseif ($elem_type === 'kitchen_pass') {
                                $class_names .= " bg-warning-subtle text-dark border border-warning shadow-sm fw-bold";
                            } elseif ($elem_type === 'plant') {
                                $class_names .= " bg-success-subtle text-success border border-success rounded-circle shadow-sm";
                            } elseif ($elem_type === 'restroom') {
                                $class_names .= " bg-light text-secondary border border-secondary shadow-sm";
                            } else {
                                $class_names .= " bg-secondary-subtle border shadow-sm";
                            }
                            ?>
                            <div class="<?= $class_names ?>"
                                 id="layout-element-<?= $elem['id'] ?>"
                                 data-id="<?= $elem['id'] ?>"
                                 data-type="<?= e($elem_type) ?>"
                                 data-label="<?= e($elem['label']) ?>"
                                 data-section="<?= e($elem['section']) ?>"
                                 data-width="<?= $elem_w ?>"
                                 data-height="<?= $elem_h ?>"
                                 data-rotation="<?= $elem_rot ?>"
                                 style="<?= $custom_style ?>"
                                 onclick="handleLayoutElementClick(<?= $elem['id'] ?>)">
                                <div class="small fw-semibold text-truncate px-1" style="font-size: 11px; pointer-events: none;">
                                    <?php if ($elem_type === 'bar_counter'): ?>
                                        <i class="fas fa-cocktail me-1"></i>
                                    <?php elseif ($elem_type === 'stage'): ?>
                                        <i class="fas fa-music me-1"></i>
                                    <?php elseif ($elem_type === 'window'): ?>
                                        <i class="fas fa-water text-info me-1"></i>
                                    <?php elseif ($elem_type === 'plant'): ?>
                                        <i class="fas fa-seedling text-success"></i>
                                    <?php elseif ($elem_type === 'kitchen_pass'): ?>
                                        <i class="fas fa-concierge-bell me-1"></i>
                                    <?php elseif ($elem_type === 'restroom'): ?>
                                        <i class="fas fa-restroom me-1"></i>
                                    <?php elseif ($elem_type === 'door'): ?>
                                        <i class="fas fa-door-open me-1"></i>
                                    <?php endif; ?>
                                    <?= e($elem['label']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- RESTAURANT TABLES -->
                    <?php foreach ($tables as $t): ?>
                        <?php
                        $status = $t['status'];
                        $bg_class = 'bg-success';
                        $border_color = '#10b981';
                        $status_text = 'Müsait';

                        if ($status === 'seated' || $status === 'dining') {
                            $bg_class = 'bg-primary';
                            $border_color = '#3b82f6';
                            $status_text = 'Oturuldu';
                        } elseif ($status === 'bill_requested') {
                            $bg_class = 'bg-warning text-dark';
                            $border_color = '#f59e0b';
                            $status_text = 'Hesap';
                        } elseif ($status === 'reserved') {
                            $bg_class = 'bg-info text-white';
                            $border_color = '#06b6d4';
                            $status_text = 'Rezerve';
                        } elseif ($status === 'cleaning') {
                            $bg_class = 'bg-secondary';
                            $border_color = '#6b7280';
                            $status_text = 'Temizlik';
                        }

                        $shape = $t['shape'] ?: 'rectangle';
                        $rotation = (int) ($t['rotation'] ?? 0);
                        $is_vip = (int) ($t['is_vip_only'] ?? 0);
                        $min_spend = (float) ($t['min_spend'] ?? 0);
                        $combined_id = $t['combined_with_table_id'] ?? null;

                        // Shape Styling
                        $shape_css = "";
                        if ($shape === 'round') {
                            $shape_css = "border-radius: 50% !important;";
                        } elseif ($shape === 'square') {
                            $shape_css = "border-radius: 12px !important;";
                        } elseif ($shape === 'booth') {
                            // Loca / Booth curved bench styling with velvet headboard effect
                            $shape_css = "border-radius: 18px 18px 8px 8px !important; border-top: 6px solid " . ($is_vip ? '#eab308' : '#6366f1') . " !important;";
                        } elseif ($shape === 'bistro') {
                            $shape_css = "border-radius: 50% !important; border: 3px double {$border_color} !important;";
                        } else {
                            $shape_css = "border-radius: 12px !important;";
                        }

                        // Check if seated customer is a regular / patron
                        $is_patron_seated = !empty($t['customer_tier']) && in_array($t['customer_tier'], ['elite', 'vip_regular', 'regular']);
                        ?>
                        <div class="floor-table position-absolute shadow-sm d-flex flex-column align-items-center justify-content-between p-2 cursor-pointer user-select-none transition-all"
                             id="table-box-<?= $t['id'] ?>"
                             data-id="<?= $t['id'] ?>"
                             data-section="<?= e($t['section']) ?>"
                             data-number="<?= e($t['table_number']) ?>"
                             data-status="<?= e($t['status']) ?>"
                             data-adisyon="<?= $t['current_id_adisyons'] ?: '' ?>"
                             data-capacity="<?= $t['capacity'] ?>"
                             data-shape="<?= e($shape) ?>"
                             data-rotation="<?= $rotation ?>"
                             data-is-vip="<?= $is_vip ?>"
                             data-min-spend="<?= $min_spend ?>"
                             data-combined="<?= $combined_id ?: '' ?>"
                             data-guest-name="<?= e($t['guest_first_name'] ?? '') ?>"
                             data-customer-tier="<?= e($t['customer_tier'] ?? '') ?>"
                             style="left: <?= $t['pos_x'] ?>px; top: <?= $t['pos_y'] ?>px; width: <?= $t['width'] ?: 110 ?>px; height: <?= $t['height'] ?: 85 ?>px; border: 2px solid <?= $border_color ?>; background: #ffffff; transform: rotate(<?= $rotation ?>deg); transform-origin: center center; z-index: 10; <?= $shape_css ?>"
                             onclick="handleTableClick(<?= $t['id'] ?>)">

                            <!-- Top Bar: Table Number, VIP Crown & Capacity -->
                            <div class="d-flex w-100 justify-content-between align-items-center" style="pointer-events: none;">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge <?= $bg_class ?> rounded-pill fw-bold" style="font-size: 11px;">
                                        M<?= e($t['table_number']) ?>
                                    </span>
                                    <?php if ($is_vip): ?>
                                        <i class="fas fa-crown text-warning" title="VIP Masa" style="font-size: 10px;"></i>
                                    <?php endif; ?>
                                    <?php if ($combined_id): ?>
                                        <i class="fas fa-link text-primary" title="Masa <?= e($combined_id) ?> ile Birleşik" style="font-size: 10px;"></i>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted fw-semibold" style="font-size: 10px;">
                                    <i class="fas fa-users"></i> <?= $t['capacity'] ?>
                                </small>
                            </div>

                            <!-- Center: Guest Name or Status Info -->
                            <div class="text-center my-auto w-100" style="pointer-events: none;">
                                <?php if ($status === 'seated' || $status === 'dining'): ?>
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <?php if ($is_patron_seated): ?>
                                            <i class="fas fa-star text-warning" style="font-size: 10px;" title="Müdavim Misafir!"></i>
                                        <?php endif; ?>
                                        <div class="fw-bold text-dark small text-truncate" style="max-width: 80px; font-size: 11px;">
                                            <?= e($t['guest_first_name'] ? $t['guest_first_name'] : 'Masa ' . $t['table_number']) ?>
                                        </div>
                                    </div>
                                    <span class="badge bg-light text-primary border px-1" style="font-size: 10px;">
                                        <?= $t['adisyon_total'] ? number_format($t['adisyon_total'], 2) . ' ₺' : 'Açık' ?>
                                    </span>
                                <?php elseif ($status === 'bill_requested'): ?>
                                    <small class="badge bg-warning text-dark fw-bold px-1" style="font-size: 10px;">Hesap İstendi</small>
                                <?php elseif ($status === 'reserved'): ?>
                                    <small class="text-info fw-bold" style="font-size: 10px;">Rezerve</small>
                                <?php elseif ($status === 'cleaning'): ?>
                                    <small class="text-secondary fw-semibold" style="font-size: 10px;">Temizlik</small>
                                <?php else: ?>
                                    <small class="text-success fw-semibold" style="font-size: 10px;">Müsait</small>
                                    <?php if ($min_spend > 0): ?>
                                        <div class="text-muted" style="font-size: 8px;">Min ₺<?= number_format($min_spend, 0) ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Bottom Bar: Elapsed Time & Shape Indicator -->
                            <div class="d-flex w-100 justify-content-between align-items-center text-muted" style="font-size: 9px; pointer-events: none;">
                                <?php if ($t['minutes_seated'] > 0): ?>
                                    <span class="fw-bold" style="color: <?= $t['aging_color'] ?? '#dc2626' ?>;" 
                                          title="Ortalama: <?= $t['predicted_turnaround_mins'] ?? 80 ?> dk | Tahmini Boşalma: <?= $t['predicted_free_at'] ?? '-' ?> (Kalan: ~<?= $t['predicted_remaining_mins'] ?? 0 ?> dk)">
                                        <i class="far fa-clock"></i> <?= $t['minutes_seated'] ?> dk
                                        <?php if (!empty($t['predicted_free_at'])): ?>
                                            <span class="opacity-75 ms-1" style="font-size: 8px;">(~<?= $t['predicted_free_at'] ?>)</span>
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-truncate" style="max-width: 65px;"><?= e($t['section']) ?></span>
                                <?php endif; ?>

                                <div class="d-flex gap-1 align-items-center">
                                    <?php if ($t['duration_mode'] === 'fixed_duration'): ?>
                                        <span class="badge bg-warning text-dark border px-1" style="font-size: 8px;" title="Süreli Seans (Geri Sayım)">
                                            ⏳ <?= $t['session_remaining_mins'] ?? 0 ?> dk
                                        </span>
                                    <?php elseif ($t['duration_mode'] === 'daily_pass'): ?>
                                        <span class="badge bg-info text-dark border px-1" style="font-size: 8px;" title="Günlük Giriş Pass">
                                            🎟️ Pass
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($shape === 'booth'): ?>
                                        <span class="badge bg-light text-secondary border px-1" style="font-size: 8px;">Loca</span>
                                    <?php elseif ($shape === 'round'): ?>
                                        <span class="badge bg-light text-secondary border px-1" style="font-size: 8px;">Yuvarlak</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right Side: Today's Reservations, Waitlist & Patron Desk -->
        <div class="col-12 col-xl-3">
            <!-- Live Waitlist Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
                    <span class="fw-bold small"><i class="fas fa-hourglass-half text-warning me-1"></i>Bekleme Sırası (Waitlist)</span>
                    <span class="badge bg-warning text-dark"><?= count($waitlist) ?></span>
                </div>
                <div class="card-body p-2" style="max-height: 220px; overflow-y: auto;">
                    <?php if (empty($waitlist)): ?>
                        <div class="text-center py-3 text-muted small">Sırada bekleyen misafir yok.</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush small">
                            <?php foreach ($waitlist as $w): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-1 py-2">
                                    <div>
                                        <div class="fw-bold"><?= e($w['name'] ?? $w['customer_name'] ?? 'Misafir') ?></div>
                                        <small class="text-muted"><?= e($w['phone_number'] ?? '') ?> (<?= $w['party_size'] ?? 2 ?> kişi)</small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="seatFromWaitlist(<?= $w['id'] ?>)">Oturt</button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Today's Reservations Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
                    <span class="fw-bold small"><i class="fas fa-calendar-day text-primary me-1"></i>Bugünün Rezervasyonları</span>
                    <span class="badge bg-primary"><?= count($reservations) ?></span>
                </div>
                <div class="card-body p-2" style="max-height: 250px; overflow-y: auto;">
                    <?php if (empty($reservations)): ?>
                        <div class="text-center py-3 text-muted small">Bugün için rezervasyon yok.</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush small">
                            <?php foreach ($reservations as $r): ?>
                                <li class="list-group-item px-1 py-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold text-dark">
                                            <?= date('H:i', strtotime($r['reservation_datetime'])) ?> - <?= e($r['guest_first_name'] . ' ' . $r['guest_last_name']) ?>
                                        </span>
                                        <span class="badge bg-light text-dark border"><?= $r['party_size'] ?> Kişi</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            Masa: <?= $r['table_number'] ? 'Masa ' . $r['table_number'] : '<span class="text-danger">Atanmadı</span>' ?>
                                        </small>
                                        <?php if ($r['status'] !== 'seated'): ?>
                                            <button class="btn btn-sm btn-success py-0 px-2" onclick="seatReservation(<?= $r['id'] ?>, <?= $r['id_restaurant_tables'] ?: 0 ?>)">
                                                <i class="fas fa-sign-in-alt me-1"></i> Masa Aç
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-success">Oturdu</span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Müdavim Desk / VIP Patrons Quick Card -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
                    <span class="fw-bold small"><i class="fas fa-crown text-warning me-1"></i>Kayıtlı Müdavimler</span>
                    <a href="<?= site_url('restaurant/guest_patronage') ?>" class="small text-decoration-none">Tümü &rarr;</a>
                </div>
                <div class="card-body p-2" style="max-height: 220px; overflow-y: auto;">
                    <?php if (empty($patrons)): ?>
                        <div class="text-center py-3 text-muted small">Henüz müdavim profili oluşturulmamış.</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush small">
                            <?php foreach (array_slice($patrons, 0, 5) as $p): ?>
                                <li class="list-group-item px-1 py-2 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-dark">
                                            <i class="fas fa-star text-warning me-1"></i><?= e($p['customer_name'] ?? 'Müşteri') ?>
                                        </div>
                                        <small class="text-muted">
                                            Favori: <?= e($p['favorite_table_number'] ? 'Masa ' . $p['favorite_table_number'] : ($p['favorite_section'] ?? 'Ana Salon')) ?>
                                            | <?= $p['total_visits'] ?> Ziyaret
                                        </small>
                                    </div>
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle text-uppercase" style="font-size: 10px;">
                                        <?= e($p['patronage_tier']) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: CANLI MASA İŞLEMLERİ (CANLI MOD)                                   -->
<!-- ========================================================================= -->
<div class="modal fade" id="table-action-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-dark" id="table-modal-title">Masa İşlemleri</h5>
                    <small class="text-muted" id="table-modal-subtitle">Canlı Masa Durumu & Adisyon Yönetimi</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="selected-table-id">

                <!-- Table Quick Metadata Strip -->
                <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded-3 mb-3 border">
                    <div class="small">
                        <span class="text-muted">Kapasite:</span> <strong id="modal-table-capacity">-</strong>
                    </div>
                    <div class="small">
                        <span class="text-muted">Şekil:</span> <strong id="modal-table-shape">-</strong>
                    </div>
                    <div class="small" id="modal-table-vip-box">
                        <span class="badge bg-warning text-dark"><i class="fas fa-crown"></i> VIP Masa</span>
                    </div>
                </div>

                <!-- Status Switcher -->
                <div class="mb-3">
                    <label class="form-label small fw-bold">Masa Durumu</label>
                    <select id="modal-table-status" class="form-select form-select-lg">
                        <option value="available">🟢 Boş / Müsait</option>
                        <option value="seated">🔵 Oturuldu (Masa Aç)</option>
                        <option value="dining">🍽️ Yeme-İçme Devam Ediyor</option>
                        <option value="bill_requested">🟡 Hesap İstendi</option>
                        <option value="cleaning">⚪ Temizleniyor</option>
                    </select>
                </div>

                <!-- Server Assignment -->
                <div class="mb-3">
                    <label class="form-label small fw-bold">Sorumlu Garson / Servis Personeli</label>
                    <select id="modal-table-server" class="form-select">
                        <option value="">-- Personel Seçin --</option>
                        <?php foreach ($staff_members as $sm): ?>
                            <option value="<?= $sm['id'] ?>"><?= e($sm['first_name'] . ' ' . $sm['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Active Adisyon Box -->
                <div id="table-adisyon-btn-box" class="p-3 bg-primary-subtle rounded-3 mb-3 border border-primary-subtle d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <span class="fw-bold small text-primary d-block"><i class="fas fa-receipt me-1"></i>Aktif Masa Adisyonu</span>
                            <small class="text-muted" id="modal-adisyon-info">Açık Adisyon</small>
                        </div>
                        <a href="<?= site_url('adisyons') ?>" class="btn btn-sm btn-primary" id="btn-goto-adisyon">
                            <i class="fas fa-external-link-alt me-1"></i> Adisyona Git
                        </a>
                    </div>
                    <hr class="my-2 border-primary-subtle">
                    <!-- Split Payment Button -->
                    <button type="button" class="btn btn-sm btn-success w-100 mb-2 fw-bold shadow-sm" onclick="openFloorPlanSplitPayment()">
                        <i class="fas fa-cash-register me-1"></i> Parçalı Tahsilat Al (Split Payment)
                    </button>
                    <!-- Close & Clean Button -->
                    <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="closeTableAdisyon()">
                        <i class="fas fa-check-double me-1"></i> Masayı Kapat & Temizliğe Al (Puan Yükle)
                    </button>
                </div>

                <!-- Split Combined Table Action -->
                <div id="table-split-btn-box" class="mb-3 d-none">
                    <button type="button" class="btn btn-outline-secondary w-100 btn-sm" onclick="splitCombinedTable()">
                        <i class="fas fa-unlink me-1"></i> Birleştirilmiş Masayı Ayır
                    </button>
                </div>

                <!-- Kroki Properties Link -->
                <div class="text-end">
                    <button type="button" class="btn btn-link btn-sm text-decoration-none text-muted" onclick="openTableKrokiEdit()">
                        <i class="fas fa-cog me-1"></i> Masa Şekil & Kroki Ayarları &rarr;
                    </button>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary" onclick="saveTableModalStatus()">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: MASA KROKİ ÖZELLİKLERİ DÜZENLE (ŞEKİL, ROTATION, VIP, MIN SPEND)    -->
<!-- ========================================================================= -->
<div class="modal fade" id="table-kroki-edit-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="fas fa-ruler-combined text-primary me-2"></i>Masa Kroki & Özellik Düzenleyici</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="table-kroki-form">
                    <input type="hidden" name="id" id="kroki-table-id">

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Masa Numarası</label>
                            <input type="text" name="table_number" id="kroki-table-number" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Bölüm / Salon</label>
                            <select name="section" id="kroki-table-section" class="form-select">
                                <option value="Ana Salon">Ana Salon</option>
                                <option value="Teras">Teras</option>
                                <option value="Bahçe">Bahçe</option>
                                <option value="VIP">VIP</option>
                                <option value="Bar">Bar</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Masa Şekli</label>
                            <select name="shape" id="kroki-table-shape" class="form-select">
                                <option value="rectangle">Dikdörtgen (Geniş)</option>
                                <option value="square">Kare</option>
                                <option value="round">Yuvarlak Masa</option>
                                <option value="booth">Loca / Booth (Banket)</option>
                                <option value="bistro">Bistro / Kokteyl Masası</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Kapasite (Kişi)</label>
                            <input type="number" name="capacity" id="kroki-table-capacity" class="form-control" min="1" max="50">
                        </div>
                    </div>

                    <!-- Rotation Angle Slider -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label small fw-bold mb-0">Döndürme Açısı (Rotation)</label>
                            <span class="badge bg-secondary" id="kroki-rotation-value">0°</span>
                        </div>
                        <input type="range" name="rotation" id="kroki-table-rotation" class="form-range mt-2" min="0" max="360" step="15" value="0" oninput="document.getElementById('kroki-rotation-value').innerText = this.value + '°'">
                        <div class="d-flex justify-content-between text-muted" style="font-size: 10px;">
                            <span>0°</span>
                            <span>90°</span>
                            <span>180°</span>
                            <span>270°</span>
                            <span>360°</span>
                        </div>
                    </div>

                    <!-- VIP & Minimum Spend -->
                    <div class="card p-3 bg-light border mb-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="is_vip_only" id="kroki-table-vip" value="1">
                            <label class="form-check-label small fw-bold text-dark" for="kroki-table-vip">
                                <i class="fas fa-crown text-warning me-1"></i>Sadece VIP Müdavimlere Açık Masa
                            </label>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold text-muted mb-1">Minimum Harcama Tutarı (₺)</label>
                            <input type="number" name="min_spend" id="kroki-table-min-spend" class="form-control form-control-sm" placeholder="Örn: 5000">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="submitTableKrokiProperties()">Kaydet & Güncelle</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: MİMARİ ELEMAN EKLE / DÜZENLE (DUVAR, PENCERE, KAPI, BAR, SAHNE)     -->
<!-- ========================================================================= -->
<div class="modal fade" id="layout-element-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="layout-element-modal-title"><i class="fas fa-shapes text-primary me-2"></i>Mimari Eleman Tanımla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="layout-element-form">
                    <input type="hidden" name="id" id="layout-elem-id">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Mimari Eleman Türü *</label>
                        <select name="element_type" id="layout-elem-type" class="form-select" required onchange="handleElemTypePreset(this.value)">
                            <option value="wall">🧱 Duvar / Ayırıcı Panel</option>
                            <option value="window">🪟 Pencere / Panoramik Cephe</option>
                            <option value="door">🚪 Giriş / Kapı</option>
                            <option value="bar_counter">🍸 Bar Bankosu / Ada Bar</option>
                            <option value="stage">🎵 Akustik Sahne / DJ Kabini</option>
                            <option value="kitchen_pass">🛎️ Mutfak Çıkışı (Kitchen Pass)</option>
                            <option value="pillar">🏛️ Taşıyıcı Kolon</option>
                            <option value="plant">🌿 Botanik Ayırıcı / Saksı</option>
                            <option value="restroom">🚻 Lavabo / WC Koridoru</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Etiket / Açıklama *</label>
                        <input type="text" name="label" id="layout-elem-label" class="form-control" placeholder="Örn: Panoramik Boğaz Manzarası, Ada Bar..." required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Bölüm / Salon</label>
                            <select name="section" id="layout-elem-section" class="form-select">
                                <option value="Ana Salon">Ana Salon</option>
                                <option value="Teras">Teras</option>
                                <option value="Bahçe">Bahçe</option>
                                <option value="VIP">VIP</option>
                                <option value="Bar">Bar</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Döndürme Açısı</label>
                            <input type="number" name="rotation" id="layout-elem-rotation" class="form-control" value="0" min="0" max="360" step="15">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Genişlik (px)</label>
                            <input type="number" name="width" id="layout-elem-width" class="form-control" value="120" min="10">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Yükseklik (px)</label>
                            <input type="number" name="height" id="layout-elem-height" class="form-control" value="40" min="5">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-outline-danger btn-sm d-none" id="btn-delete-layout-elem" onclick="deleteCurrentLayoutElement()">
                    <i class="fas fa-trash me-1"></i> Sil
                </button>
                <div class="d-flex gap-2 ms-auto">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" onclick="submitLayoutElement()">Kaydet</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: MASA BİRLEŞTİRME (TABLE PUSHING)                                   -->
<!-- ========================================================================= -->
<div class="modal fade" id="combine-tables-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="fas fa-object-group text-warning me-2"></i>Masa Birleştirme (Table Pushing)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small">
                    Kalabalık gruplar için iki masayı birleştirin. İkincil masa birincil masaya bağlanacak ve ortak adisyon açılacaktır.
                </p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Ana Masa (Birincil)</label>
                    <select id="combine-master-table" class="form-select">
                        <?php foreach ($tables as $t): ?>
                            <option value="<?= $t['id'] ?>">Masa <?= e($t['table_number']) ?> (<?= e($t['section']) ?> - <?= $t['capacity'] ?> Kişilik)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Eklenen Masa (İkincil Masa)</label>
                    <select id="combine-slave-table" class="form-select">
                        <?php foreach ($tables as $t): ?>
                            <option value="<?= $t['id'] ?>">Masa <?= e($t['table_number']) ?> (<?= e($t['section']) ?> - <?= $t['capacity'] ?> Kişilik)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-warning text-dark fw-bold" onclick="submitCombineTables()">Masaları Birleştir</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: AKILLI MASA ATAMA SİMÜLATÖRÜ (AI SEATING)                          -->
<!-- ========================================================================= -->
<div class="modal fade" id="auto-assign-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="fas fa-robot text-info me-2"></i>Akıllı Masa Atama Motoru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small">
                    Rezervasyon veya kapıdan gelen misafirler için kapasite optimizasyonu, müdavim favori masası ve VIP koruma kurallarına göre en uygun masayı bulun.
                </p>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Kişi Sayısı *</label>
                        <input type="number" id="auto-assign-party-size" class="form-control" value="4" min="1" max="30">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Tercih Edilen Bölüm</label>
                        <select id="auto-assign-section" class="form-select">
                            <option value="">Fark Etmez (En Uygun)</option>
                            <option value="Ana Salon">Ana Salon</option>
                            <option value="Teras">Teras</option>
                            <option value="Bahçe">Bahçe</option>
                            <option value="VIP">VIP</option>
                            <option value="Bar">Bar</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Müdavim Misafir Seçimi (Opsiyonel)</label>
                    <select id="auto-assign-customer" class="form-select">
                        <option value="">-- Genel Misafir (Müdavim Değil) --</option>
                        <?php foreach ($patrons as $p): ?>
                            <option value="<?= $p['id_users_customer'] ?>">
                                ★ <?= e($p['customer_name'] ?? 'Müşteri') ?> (<?= e($p['patronage_tier']) ?> - Fav: <?= e($p['favorite_table_number'] ? 'Masa ' . $p['favorite_table_number'] : $p['favorite_section']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="button" class="btn btn-info text-white w-100 fw-bold py-2 mb-3" onclick="runAutoAssignSimulation()">
                    <i class="fas fa-search me-1"></i> En Uygun Masayı Hesapla
                </button>

                <!-- Recommendation Result Box -->
                <div id="auto-assign-result-box" class="p-3 bg-light rounded-3 border d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0 text-success"><i class="fas fa-check-circle me-1"></i>Önerilen En Uygun Masa:</h6>
                        <span class="badge bg-success" id="auto-res-badge">Masa 1</span>
                    </div>
                    <div class="small text-muted mb-2" id="auto-res-reason">Kapasite tam uyumlu ve müdavim tercihi ile eşleşti.</div>
                    <div class="d-flex justify-content-between small text-muted border-top pt-2">
                        <span>Bölüm: <strong id="auto-res-section">Ana Salon</strong></span>
                        <span>Kapasite: <strong id="auto-res-capacity">4 Kişilik</strong></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: YENİ MASA TANIMLA                                                  -->
<!-- ========================================================================= -->
<div class="modal fade" id="new-table-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle text-primary me-2"></i>Yeni Masa Tanımla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="new-table-form">
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Masa Numarası *</label>
                            <input type="text" name="table_number" class="form-control" placeholder="Örn: 1, 12, T-4" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Bölüm / Salon</label>
                            <select name="section" class="form-select">
                                <option value="Ana Salon">Ana Salon</option>
                                <option value="Teras">Teras</option>
                                <option value="Bahçe">Bahçe</option>
                                <option value="VIP">VIP</option>
                                <option value="Bar">Bar</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Kapasite (Kişi)</label>
                            <input type="number" name="capacity" class="form-control" value="4" min="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Masa Şekli</label>
                            <select name="shape" class="form-select">
                                <option value="rectangle">Dikdörtgen</option>
                                <option value="square">Kare</option>
                                <option value="round">Yuvarlak</option>
                                <option value="booth">Loca / Booth</option>
                                <option value="bistro">Bistro</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_vip_only" id="new-table-vip" value="1">
                        <label class="form-check-label small fw-bold text-dark" for="new-table-vip">
                            <i class="fas fa-crown text-warning me-1"></i>Sadece VIP Müdavimlere Özel Masa
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="submitNewTable()">Masayı Kaydet</button>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script>
let currentFloorMode = 'live'; // 'live' or 'edit'

// Toggle Mode
function toggleFloorMode(mode) {
    currentFloorMode = mode;
    const banner = document.getElementById('mode-notice-banner');
    const canvas = document.getElementById('floor-plan-canvas');

    if (mode === 'edit') {
        banner.innerHTML = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="fas fa-tools me-1"></i>Kroki Düzenleme Modu Aktif</span> <span class="text-muted small">Masaları ve mimari elemanları serbestçe sürükleyebilirsiniz.</span>`;
        canvas.style.cursor = 'grab';
    } else {
        banner.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fas fa-check-circle me-1"></i>Canlı Masa Operasyonu Aktif</span> <span class="text-muted small">Masaya tıklayarak sipariş/adisyon veya durum yönetin.</span>`;
        canvas.style.cursor = 'default';
    }
}

// Drag & Drop for Floor Tables & Layout Elements
document.addEventListener('DOMContentLoaded', function() {
    setupDraggableItems('.floor-table', '<?= site_url('restaurant/save_layout') ?>');
    setupDraggableItems('.layout-element', '<?= site_url('restaurant/save_layout_element') ?>', true);
});

function setupDraggableItems(selector, saveEndpoint, isLayoutElement = false) {
    const items = document.querySelectorAll(selector);
    items.forEach(item => {
        let isDragging = false;
        let startX, startY, initialLeft, initialTop;

        item.addEventListener('mousedown', function(e) {
            if (currentFloorMode !== 'edit') return;
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            initialLeft = parseInt(item.style.left, 10) || 0;
            initialTop = parseInt(item.style.top, 10) || 0;
            item.style.zIndex = 1000;
            e.stopPropagation();
        });

        document.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            item.style.left = Math.max(0, initialLeft + dx) + 'px';
            item.style.top = Math.max(0, initialTop + dy) + 'px';
        });

        document.addEventListener('mouseup', function(e) {
            if (!isDragging) return;
            isDragging = false;
            item.style.zIndex = isLayoutElement ? 5 : 10;

            const itemId = item.dataset.id;
            const posX = parseInt(item.style.left, 10);
            const posY = parseInt(item.style.top, 10);

            const fd = new FormData();
            fd.append('id', itemId);
            fd.append('pos_x', posX);
            fd.append('pos_y', posY);

            fetch(saveEndpoint, { method: 'POST', body: fd });
        });
    });
}

// Section Filter
function filterSection(section, btn) {
    document.querySelectorAll('#section-filter-group .btn').forEach(b => b.classList.remove('active', 'btn-dark'));
    btn.classList.add('active', 'btn-dark');

    document.getElementById('canvas-active-section-badge').innerText = (section === 'all' ? 'Tüm Bölümler' : section);

    const tables = document.querySelectorAll('.floor-table');
    tables.forEach(t => {
        if (section === 'all' || t.dataset.section === section) {
            t.style.display = 'flex';
        } else {
            t.style.display = 'none';
        }
    });

    const elements = document.querySelectorAll('.layout-element');
    elements.forEach(elem => {
        if (section === 'all' || elem.dataset.section === section) {
            elem.style.display = 'flex';
        } else {
            elem.style.display = 'none';
        }
    });
}

// Handle Table Click
function handleTableClick(tableId) {
    const tableEl = document.getElementById('table-box-' + tableId);
    if (!tableEl) return;

    if (currentFloorMode === 'edit') {
        openTableKrokiEditById(tableId);
        return;
    }

    document.getElementById('selected-table-id').value = tableId;
    document.getElementById('table-modal-title').innerText = 'Masa ' + tableEl.dataset.number + ' (' + tableEl.dataset.section + ')';
    document.getElementById('modal-table-capacity').innerText = tableEl.dataset.capacity + ' Kişilik';
    document.getElementById('modal-table-shape').innerText = tableEl.dataset.shape || 'Dikdörtgen';
    document.getElementById('modal-table-status').value = tableEl.dataset.status;

    // VIP badge display
    const vipBox = document.getElementById('modal-table-vip-box');
    if (parseInt(tableEl.dataset.isVip, 10) === 1) {
        vipBox.classList.remove('d-none');
    } else {
        vipBox.classList.add('d-none');
    }

    // Adisyon Box
    const adisyonId = tableEl.dataset.adisyon;
    const adizedBox = document.getElementById('table-adisyon-btn-box');
    if (adisyonId) {
        adizedBox.classList.remove('d-none');
        document.getElementById('modal-adisyon-info').innerText = 'Adisyon #' + adisyonId + ' açık';
        document.getElementById('btn-goto-adisyon').href = '<?= site_url('adisyons') ?>';
    } else {
        adizedBox.classList.add('d-none');
    }

    // Split Combined Box
    const splitBox = document.getElementById('table-split-btn-box');
    if (tableEl.dataset.combined) {
        splitBox.classList.remove('d-none');
    } else {
        splitBox.classList.add('d-none');
    }

    const modal = new bootstrap.Modal(document.getElementById('table-action-modal'));
    modal.show();
}

function openTableKrokiEdit() {
    const tableId = document.getElementById('selected-table-id').value;
    const modalAction = bootstrap.Modal.getInstance(document.getElementById('table-action-modal'));
    if (modalAction) modalAction.hide();
    openTableKrokiEditById(tableId);
}

function openTableKrokiEditById(tableId) {
    const tableEl = document.getElementById('table-box-' + tableId);
    if (!tableEl) return;

    document.getElementById('kroki-table-id').value = tableId;
    document.getElementById('kroki-table-number').value = tableEl.dataset.number;
    document.getElementById('kroki-table-section').value = tableEl.dataset.section;
    document.getElementById('kroki-table-shape').value = tableEl.dataset.shape || 'rectangle';
    document.getElementById('kroki-table-capacity').value = tableEl.dataset.capacity || 4;
    document.getElementById('kroki-table-rotation').value = tableEl.dataset.rotation || 0;
    document.getElementById('kroki-rotation-value').innerText = (tableEl.dataset.rotation || 0) + '°';
    document.getElementById('kroki-table-vip').checked = (parseInt(tableEl.dataset.isVip, 10) === 1);
    document.getElementById('kroki-table-min-spend').value = tableEl.dataset.minSpend || '';

    const modal = new bootstrap.Modal(document.getElementById('table-kroki-edit-modal'));
    modal.show();
}

function submitTableKrokiProperties() {
    const form = document.getElementById('table-kroki-form');
    const fd = new FormData(form);

    fetch('<?= site_url('restaurant/save_table_kroki') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert(data.message || 'Masa güncellenemedi.');
            }
        });
}

function saveTableModalStatus() {
    const tableId = document.getElementById('selected-table-id').value;
    const status = document.getElementById('modal-table-status').value;
    const serverId = document.getElementById('modal-table-server').value;

    const fd = new FormData();
    fd.append('id_tables', tableId);
    fd.append('status', status);
    if (serverId) fd.append('id_users_server', serverId);

    fetch('<?= site_url('restaurant/set_table_status') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert(data.message || 'Durum kaydedilemedi.');
            }
        });
}

function closeTableAdisyon() {
    const tableId = document.getElementById('selected-table-id').value;
    if (!confirm('Adisyon kapatılıp masa temizlik durumuna alınacaktır. Onaylıyor musunuz?')) return;

    fetch('<?= site_url('restaurant/api/close_table') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ table_id: tableId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            alert('Masa kapatıldı ve temizlik moduna alındı.');
            window.location.reload();
        } else {
            alert(data.message || 'İşlem başarısız.');
        }
    });
}

// Layout Element Handle Click
function handleLayoutElementClick(elemId) {
    if (currentFloorMode !== 'edit') return;
    const elem = document.getElementById('layout-element-' + elemId);
    if (!elem) return;

    document.getElementById('layout-elem-id').value = elemId;
    document.getElementById('layout-elem-type').value = elem.dataset.type;
    document.getElementById('layout-elem-label').value = elem.dataset.label;
    document.getElementById('layout-elem-section').value = elem.dataset.section;
    document.getElementById('layout-elem-width').value = elem.dataset.width;
    document.getElementById('layout-elem-height').value = elem.dataset.height;
    document.getElementById('layout-elem-rotation').value = elem.dataset.rotation;
    document.getElementById('layout-element-modal-title').innerText = 'Mimari Elemanı Düzenle';
    document.getElementById('btn-delete-layout-elem').classList.remove('d-none');

    const modal = new bootstrap.Modal(document.getElementById('layout-element-modal'));
    modal.show();
}

function handleElemTypePreset(type) {
    const labelInput = document.getElementById('layout-elem-label');
    const wInput = document.getElementById('layout-elem-width');
    const hInput = document.getElementById('layout-elem-height');

    if (type === 'wall') {
        if (!labelInput.value) labelInput.value = 'Ayırıcı Duvar';
        wInput.value = 160; hInput.value = 20;
    } else if (type === 'window') {
        if (!labelInput.value) labelInput.value = 'Panoramik Cam Cephe';
        wInput.value = 180; hInput.value = 15;
    } else if (type === 'bar_counter') {
        if (!labelInput.value) labelInput.value = 'Kokteyl & İçecek Barı';
        wInput.value = 220; hInput.value = 60;
    } else if (type === 'stage') {
        if (!labelInput.value) labelInput.value = 'Canlı Müzik Sahnesi';
        wInput.value = 180; hInput.value = 100;
    }
}

function submitLayoutElement() {
    const form = document.getElementById('layout-element-form');
    const fd = new FormData(form);

    fetch('<?= site_url('restaurant/save_layout_element') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert(data.message || 'Eleman kaydedilemedi.');
            }
        });
}

function deleteCurrentLayoutElement() {
    const id = document.getElementById('layout-elem-id').value;
    if (!id || !confirm('Bu mimari elemanı silmek istediğinizden emin misiniz?')) return;

    const fd = new FormData();
    fd.append('id', id);

    fetch('<?= site_url('restaurant/delete_layout_element') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert('Silinemedi.');
            }
        });
}

// Table Combining (Table Pushing)
function submitCombineTables() {
    const masterId = document.getElementById('combine-master-table').value;
    const slaveId = document.getElementById('combine-slave-table').value;

    if (masterId === slaveId) {
        alert('Aynı masayı kendisiyle birleştiremezsiniz.');
        return;
    }

    const fd = new FormData();
    fd.append('master_id', masterId);
    fd.append('slave_id', slaveId);

    fetch('<?= site_url('restaurant/combine_tables') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || 'Masalar birleştirilemedi.');
            }
        });
}

function splitCombinedTable() {
    const tableId = document.getElementById('selected-table-id').value;
    if (!confirm('Bu masa birleştirmesini ayırmak istediğinizden emin misiniz?')) return;

    const fd = new FormData();
    fd.append('table_id', tableId);

    fetch('<?= site_url('restaurant/split_tables') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || 'Ayrılamadı.');
            }
        });
}

// AI Auto-Assign Simulation
function runAutoAssignSimulation() {
    const partySize = document.getElementById('auto-assign-party-size').value;
    const section = document.getElementById('auto-assign-section').value;
    const customerId = document.getElementById('auto-assign-customer').value;

    const params = new URLSearchParams({
        party_size: partySize,
        section: section,
        customer_id: customerId
    });

    fetch('<?= site_url('restaurant/api/auto_assign') ?>?' + params.toString())
        .then(res => res.json())
        .then(data => {
            const resultBox = document.getElementById('auto-assign-result-box');
            if (data.status === 'success' && data.table) {
                resultBox.classList.remove('d-none');
                document.getElementById('auto-res-badge').innerText = 'Masa ' + data.table.table_number;
                document.getElementById('auto-res-section').innerText = data.table.section;
                document.getElementById('auto-res-capacity').innerText = data.table.capacity + ' Kişilik';

                let reason = 'Kapasiteye tam uyumlu masa bulundu.';
                if (data.is_patron_favorite) {
                    reason = '★ Müdavim misafirin favori masası tespit edildi ve koruma kuralıyla otomatik atandı!';
                }
                document.getElementById('auto-res-reason').innerText = reason;
            } else {
                alert(data.message || 'Uygun masa bulunamadı.');
                resultBox.classList.add('d-none');
            }
        })
        .catch(err => alert('Hesaplama sırasında hata oluştu.'));
}

function seatReservation(resId, tableId) {
    const fd = new FormData();
    fd.append('id_reservations', resId);
    if (tableId) fd.append('id_tables', tableId);

    fetch('<?= site_url('restaurant/seat') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert(data.message || 'Hata oluştu.');
            }
        });
}

function submitNewTable() {
    const form = document.getElementById('new-table-form');
    const fd = new FormData(form);

    fetch('<?= site_url('restaurant/save_table') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert(data.message || 'Masa eklenemedi.');
            }
        });
}

// Floor Plan Parçalı Tahsilat
function openFloorPlanSplitPayment() {
    const tableId = currentSelectedTableId;
    if (!tableId) return;

    const modalEl = document.getElementById('tableDetailModal');
    if (modalEl) {
        bootstrap.Modal.getInstance(modalEl)?.hide();
    }

    fetch(`<?= site_url('restaurant/api/table_experience?table_id=') ?>${tableId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.data && data.data.current_id_adisyons) {
                const adisyonId = data.data.current_id_adisyons;
                const total = parseFloat(data.data.adisyon_total) || 0;
                SplitPaymentModal.open('adisyon', adisyonId, total, []);
                SplitPaymentModal.onFinalized(function() {
                    alert('Tahsilat başarıyla tamamlandı!');
                    window.location.reload();
                });
            } else {
                alert('Bu masada açık bir adisyon bulunamadı.');
            }
        })
        .catch(err => alert('Hata: ' + err.message));
}
</script>

<!-- Load Split Payment Modal Component -->
<?php $this->load->view('components/split_payment_modal'); ?>
<?php end_section('scripts'); ?>
