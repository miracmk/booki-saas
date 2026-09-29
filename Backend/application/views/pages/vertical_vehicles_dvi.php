<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @var array $vehicles
 * @var array $work_orders
 * @var array $customers
 * @var array|null $technicians
 */
$technicians = $technicians ?? [];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?php $this->load->view('components/backend_head'); ?>
    <title><?= e(vars('page_title')) ?> - BooKi</title>
</head>
<body class="backend-body">
    <?php $this->load->view('components/backend_header'); ?>

    <div class="container-fluid py-4 px-md-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1"><i class="fas fa-car text-danger me-2"></i>Oto Servis, Araç Sicili & DVI Ekspertiz</h1>
                <p class="text-muted small mb-0">Müşteri araç kartları (plaka/VIN), DVI dijital ekspertiz checklistleri ve servis iş emri (work order) akışı.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modal-add-work-order">
                    <i class="fas fa-plus-circle me-1"></i> Yeni İş Emri Başlat
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-vehicle">
                    <i class="fas fa-car me-1"></i> Yeni Araç Tanımla
                </button>
            </div>
        </div>

        <ul class="nav nav-pills mb-4" id="auto-tabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="tab-wo-btn" data-bs-toggle="pill" data-bs-target="#tab-wo">
                    <i class="fas fa-tools me-1"></i> İş Emirleri / Work Orders (<?= count($work_orders) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="tab-veh-btn" data-bs-toggle="pill" data-bs-target="#tab-veh">
                    <i class="fas fa-car-side me-1"></i> Kayıtlı Müşteri Araçları (<?= count($vehicles) ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="auto-tabs-content">
            <!-- TAB 1: WORK ORDERS -->
            <div class="tab-pane fade show active" id="tab-wo">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="fw-bold mb-0 text-secondary"><i class="fas fa-clipboard-list text-primary me-2"></i>Aktif Servis İş Emirleri</h5>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-add-work-order">
                        <i class="fas fa-plus-circle me-1"></i> Yeni İş Emri Başlat
                    </button>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>İş Emri No</th>
                                    <th>Araç & Plaka</th>
                                    <th>Aşama / Durum</th>
                                    <th>Tahmini Maliyet</th>
                                    <th>Kesin Tutar</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>Aşama Güncelle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($work_orders)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Kayıtlı servis iş emri bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($work_orders as $wo): ?>
                                        <tr>
                                            <td class="fw-bold font-monospace text-primary"><?= e($wo['work_order_number']) ?></td>
                                            <td>
                                                <span class="badge bg-dark font-monospace me-1"><?= e($wo['plate_number']) ?></span>
                                                <span class="fw-semibold"><?= e($wo['brand'] . ' ' . $wo['model']) ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $stMap = [
                                                    'created' => ['bg-secondary', 'Kayıt Açıldı'],
                                                    'inspected' => ['bg-info text-white', 'Ekspertiz Yapıldı'],
                                                    'estimate_pending' => ['bg-secondary text-white', 'Fiyat Bekleniyor'],
                                                    'approved' => ['bg-primary', 'Müşteri Onayladı'],
                                                    'in_progress' => ['bg-warning text-dark', 'İşlemde / Liftte'],
                                                    'parts_waiting' => ['bg-danger', 'Parça Bekleniyor'],
                                                    'quality_check' => ['bg-info text-dark', 'Son Kontrol'],
                                                    'ready' => ['bg-success', 'Araç Hazır!'],
                                                    'delivered' => ['bg-dark', 'Teslim Edildi'],
                                                ];
                                                $st = $stMap[$wo['status']] ?? ['bg-secondary', $wo['status']];
                                                ?>
                                                <span class="badge <?= $st[0] ?>"><?= $st[1] ?></span>
                                            </td>
                                            <td>₺<?= number_format((float) ($wo['estimated_cost'] ?? 0), 2) ?></td>
                                            <td class="fw-bold text-success">₺<?= number_format((float) ($wo['final_cost'] ?: $wo['estimated_cost']), 2) ?></td>
                                            <td><small class="text-muted"><?= date('d.m.Y H:i', strtotime($wo['created_at'])) ?></small></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group" aria-label="Aşama Güncelle">
                                                    <button type="button" class="btn btn-outline-info btn-wo-status <?= $wo['status'] === 'inspected' ? 'active' : '' ?>" data-id="<?= $wo['id'] ?>" data-status="inspected" title="Ekspertiz Yapıldı (inspected)">
                                                        <i class="fas fa-clipboard-check me-1"></i>Ekspertiz
                                                    </button>
                                                    <button type="button" class="btn btn-outline-warning btn-wo-status <?= $wo['status'] === 'in_progress' ? 'active' : '' ?>" data-id="<?= $wo['id'] ?>" data-status="in_progress" title="İşleme Al / Lift (in_progress)">
                                                        <i class="fas fa-wrench me-1"></i>İşlemde
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger btn-wo-status <?= $wo['status'] === 'parts_waiting' ? 'active' : '' ?>" data-id="<?= $wo['id'] ?>" data-status="parts_waiting" title="Parça Bekleniyor (parts_waiting)">
                                                        <i class="fas fa-box me-1"></i>Parça
                                                    </button>
                                                    <button type="button" class="btn btn-outline-success btn-wo-status <?= $wo['status'] === 'ready' ? 'active' : '' ?>" data-id="<?= $wo['id'] ?>" data-status="ready" title="Araç Hazır (ready)">
                                                        <i class="fas fa-check-circle me-1"></i>Hazır
                                                    </button>
                                                    <button type="button" class="btn btn-outline-dark btn-wo-status <?= $wo['status'] === 'delivered' ? 'active' : '' ?>" data-id="<?= $wo['id'] ?>" data-status="delivered" title="Teslim Edildi (delivered)">
                                                        <i class="fas fa-flag-checkered me-1"></i>Teslim
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: VEHICLES -->
            <div class="tab-pane fade" id="tab-veh">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Plaka</th>
                                    <th>Araç Marka & Model</th>
                                    <th>Yıl</th>
                                    <th>Mevcut KM</th>
                                    <th>Araç Sahibi</th>
                                    <th>Telefon</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($vehicles)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Kayıtlı müşteri aracı bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($vehicles as $v): ?>
                                        <tr>
                                            <td><span class="badge bg-dark font-monospace fs-6 px-2 py-1"><?= e($v['plate_number']) ?></span></td>
                                            <td class="fw-bold"><?= e($v['brand'] . ' ' . $v['model']) ?></td>
                                            <td><?= e($v['year'] ?: '-') ?></td>
                                            <td><?= number_format((int) ($v['current_km'] ?? 0)) ?> km</td>
                                            <td><?= e(trim(($v['owner_first_name'] ?? '') . ' ' . ($v['owner_last_name'] ?? ''))) ?></td>
                                            <td><?= e($v['phone_number'] ?: '-') ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-new-dvi"
                                                    data-id="<?= $v['id'] ?>"
                                                    data-plate="<?= e($v['plate_number']) ?>"
                                                    data-brand="<?= e($v['brand']) ?>"
                                                    data-model="<?= e($v['model']) ?>"
                                                    data-brand-model="<?= e($v['brand'] . ' ' . $v['model']) ?>">
                                                    <i class="fas fa-clipboard-check me-1"></i> DVI Ekspertiz Yap
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: YENİ İŞ EMRİ BAŞLAT -->
    <div class="modal fade" id="modal-add-work-order" tabindex="-1" aria-labelledby="modal-add-work-order-title" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-add-work-order-title">
                        <i class="fas fa-tools text-danger me-2"></i>Yeni İş Emri Başlat
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-add-wo">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="wo-vehicle-id">Araç / Plaka <span class="text-danger">*</span></label>
                            <select name="id_vehicles" id="wo-vehicle-id" class="form-select" required>
                                <option value="">-- Servise Giren Aracı Seçiniz --</option>
                                <?php foreach ($vehicles as $v): ?>
                                    <option value="<?= $v['id'] ?>">
                                        <?= e($v['plate_number']) ?> &mdash; <?= e($v['brand'] . ' ' . $v['model']) ?> (<?= e(trim(($v['owner_first_name'] ?? '') . ' ' . ($v['owner_last_name'] ?? ''))) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="wo-technician-id">Sorumlu Teknisyen / Usta</label>
                            <select name="id_users_technician" id="wo-technician-id" class="form-select">
                                <option value="">-- Teknisyen Seçiniz (İsteğe Bağlı) --</option>
                                <?php foreach ($technicians as $t): ?>
                                    <option value="<?= $t['id'] ?>">
                                        <?= e(trim($t['first_name'] . ' ' . $t['last_name'])) ?> <?= !empty($t['phone_number']) ? '(' . e($t['phone_number']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="wo-customer-complaint">Şikayet / Müşteri Talebi</label>
                            <textarea name="customer_complaint" id="wo-customer-complaint" class="form-control" rows="3" placeholder="Örn: 60.000 km periyodik bakım, ön takımdan gelen ses, fren balata değişimi..."></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="wo-estimated-cost">Tahmini Maliyet (TL)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₺</span>
                                    <input type="number" step="0.01" min="0" name="estimated_cost" id="wo-estimated-cost" class="form-control" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="wo-delivery-datetime">Tahmini Teslim Tarihi</label>
                                <input type="datetime-local" name="delivery_datetime" id="wo-delivery-datetime" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-success" id="btn-submit-wo">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="wo-spinner" role="status" aria-hidden="true"></span>
                            <i class="fas fa-play me-1" id="wo-icon"></i> İş Emrini Başlat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: DVI EKSPERTİZ YAP -->
    <div class="modal fade" id="modal-new-dvi" tabindex="-1" aria-labelledby="modal-new-dvi-title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <div>
                        <h5 class="modal-title mb-0" id="modal-new-dvi-title">
                            <i class="fas fa-clipboard-check text-danger me-2"></i>DVI Dijital Ekspertiz
                            <span id="dvi-modal-plate" class="badge bg-dark font-monospace ms-2"></span>
                            <span id="dvi-modal-vehicle" class="text-secondary small ms-2 fw-semibold"></span>
                        </h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-save-dvi">
                    <input type="hidden" name="id_vehicles" id="dvi-vehicle-id" value="">
                    <div class="modal-body">
                        <!-- Share Link / Success Notification Alert Box -->
                        <div id="dvi-share-result" class="alert alert-success d-none mb-4 shadow-sm" role="alert">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-check-circle fs-4 me-2"></i>
                                <strong class="fs-6">Ekspertiz Başarıyla Kaydedildi & Paylaşım Linki Oluşturuldu!</strong>
                            </div>
                            <p class="small mb-2 text-muted">Müşteriniz bu bağlantı üzerinden DVI ekspertiz raporunu görüntüleyebilir ve onaylayabilir:</p>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace form-control-sm" id="dvi-share-link" readonly>
                                <button class="btn btn-sm btn-outline-success" type="button" id="dvi-copy-link-btn">
                                    <i class="fas fa-copy me-1"></i> Linki Kopyala
                                </button>
                                <a href="#" target="_blank" class="btn btn-sm btn-primary" id="dvi-open-link">
                                    <i class="fas fa-external-link-alt me-1"></i> Raporu Aç
                                </a>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold" for="dvi-inspection-type">
                                    <i class="fas fa-list-alt text-primary me-1"></i> Muayene / Ekspertiz Tipi
                                </label>
                                <select name="inspection_type" id="dvi-inspection-type" class="form-select" required>
                                    <option value="general_service" selected>Genel Servis Muayenesi (General Service)</option>
                                    <option value="periodic_maintenance">Periyodik Bakım Muayenesi (Periodic Maintenance)</option>
                                    <option value="pre_sale_expertiz">Alım &amp; Satım Ekspertiz Raporu (Pre-Sale Expertise)</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-semibold mb-0" for="dvi-overall-score">
                                        <i class="fas fa-chart-line text-success me-1"></i> Ekspertiz Puanı
                                    </label>
                                    <span class="badge bg-primary fs-6 px-3 py-1"><span id="dvi-score-display">85</span> / 100</span>
                                </div>
                                <input type="range" class="form-range mt-2" min="0" max="100" value="85" id="dvi-overall-score" name="overall_score">
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3 text-secondary border-bottom pb-2">
                            <i class="fas fa-tasks text-danger me-2"></i>DVI Kontrol Noktaları Checklist'i
                        </h6>

                        <div class="table-responsive mb-3">
                            <table class="table table-bordered align-middle table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 32%;">Kontrol Noktası</th>
                                        <th style="width: 38%;" class="text-center">Durum Seçimi</th>
                                        <th style="width: 30%;">Teknisyen Açıklaması / Not</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- 1. Motor ve Yağ Durumu -->
                                    <tr>
                                        <td class="fw-semibold"><i class="fas fa-oil-can text-danger me-2"></i>Motor ve Yağ Durumu</td>
                                        <td>
                                            <div class="btn-group btn-group-sm w-100" role="group">
                                                <input type="radio" class="btn-check" name="item_motor_yagi_status" id="dvi_my_g" value="green" checked>
                                                <label class="btn btn-outline-success" for="dvi_my_g"><i class="fas fa-check"></i> Yeşil / İyi</label>
                                                <input type="radio" class="btn-check" name="item_motor_yagi_status" id="dvi_my_y" value="yellow">
                                                <label class="btn btn-outline-warning" for="dvi_my_y"><i class="fas fa-exclamation-triangle"></i> Sarı / Dikkat</label>
                                                <input type="radio" class="btn-check" name="item_motor_yagi_status" id="dvi_my_r" value="red">
                                                <label class="btn btn-outline-danger" for="dvi_my_r"><i class="fas fa-times-circle"></i> Kırmızı / Acil</label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="item_motor_yagi_note" class="form-control form-control-sm" placeholder="Motor yağı seviyesi, kaçak vb.">
                                        </td>
                                    </tr>
                                    <!-- 2. Fren Balataları ve Diskler -->
                                    <tr>
                                        <td class="fw-semibold"><i class="fas fa-compact-disc text-secondary me-2"></i>Fren Balataları ve Diskler</td>
                                        <td>
                                            <div class="btn-group btn-group-sm w-100" role="group">
                                                <input type="radio" class="btn-check" name="item_fren_balatalari_status" id="dvi_fb_g" value="green" checked>
                                                <label class="btn btn-outline-success" for="dvi_fb_g"><i class="fas fa-check"></i> Yeşil / İyi</label>
                                                <input type="radio" class="btn-check" name="item_fren_balatalari_status" id="dvi_fb_y" value="yellow">
                                                <label class="btn btn-outline-warning" for="dvi_fb_y"><i class="fas fa-exclamation-triangle"></i> Sarı / Dikkat</label>
                                                <input type="radio" class="btn-check" name="item_fren_balatalari_status" id="dvi_fb_r" value="red">
                                                <label class="btn btn-outline-danger" for="dvi_fb_r"><i class="fas fa-times-circle"></i> Kırmızı / Acil</label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="item_fren_balatalari_note" class="form-control form-control-sm" placeholder="Ön/arka balata kalınlığı, disk durumu">
                                        </td>
                                    </tr>
                                    <!-- 3. Lastik Diş Derinliği & Basınç -->
                                    <tr>
                                        <td class="fw-semibold"><i class="fas fa-circle-notch text-dark me-2"></i>Lastik Diş Derinliği &amp; Basınç</td>
                                        <td>
                                            <div class="btn-group btn-group-sm w-100" role="group">
                                                <input type="radio" class="btn-check" name="item_lastikler_status" id="dvi_lt_g" value="green" checked>
                                                <label class="btn btn-outline-success" for="dvi_lt_g"><i class="fas fa-check"></i> Yeşil / İyi</label>
                                                <input type="radio" class="btn-check" name="item_lastikler_status" id="dvi_lt_y" value="yellow">
                                                <label class="btn btn-outline-warning" for="dvi_lt_y"><i class="fas fa-exclamation-triangle"></i> Sarı / Dikkat</label>
                                                <input type="radio" class="btn-check" name="item_lastikler_status" id="dvi_lt_r" value="red">
                                                <label class="btn btn-outline-danger" for="dvi_lt_r"><i class="fas fa-times-circle"></i> Kırmızı / Acil</label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="item_lastikler_note" class="form-control form-control-sm" placeholder="Diş derinliği mm, DOT yılı, basınç">
                                        </td>
                                    </tr>
                                    <!-- 4. Akü ve Elektrik Sistemi -->
                                    <tr>
                                        <td class="fw-semibold"><i class="fas fa-car-battery text-warning me-2"></i>Akü ve Elektrik Sistemi</td>
                                        <td>
                                            <div class="btn-group btn-group-sm w-100" role="group">
                                                <input type="radio" class="btn-check" name="item_aku_elektrik_status" id="dvi_ae_g" value="green" checked>
                                                <label class="btn btn-outline-success" for="dvi_ae_g"><i class="fas fa-check"></i> Yeşil / İyi</label>
                                                <input type="radio" class="btn-check" name="item_aku_elektrik_status" id="dvi_ae_y" value="yellow">
                                                <label class="btn btn-outline-warning" for="dvi_ae_y"><i class="fas fa-exclamation-triangle"></i> Sarı / Dikkat</label>
                                                <input type="radio" class="btn-check" name="item_aku_elektrik_status" id="dvi_ae_r" value="red">
                                                <label class="btn btn-outline-danger" for="dvi_ae_r"><i class="fas fa-times-circle"></i> Kırmızı / Acil</label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="item_aku_elektrik_note" class="form-control form-control-sm" placeholder="Akü voltajı (CCA / V), aydınlatma, sigorta">
                                        </td>
                                    </tr>
                                    <!-- 5. Süspansiyon & Alt Takım -->
                                    <tr>
                                        <td class="fw-semibold"><i class="fas fa-wrench text-info me-2"></i>Süspansiyon &amp; Alt Takım</td>
                                        <td>
                                            <div class="btn-group btn-group-sm w-100" role="group">
                                                <input type="radio" class="btn-check" name="item_suspansiyon_alt_takim_status" id="dvi_st_g" value="green" checked>
                                                <label class="btn btn-outline-success" for="dvi_st_g"><i class="fas fa-check"></i> Yeşil / İyi</label>
                                                <input type="radio" class="btn-check" name="item_suspansiyon_alt_takim_status" id="dvi_st_y" value="yellow">
                                                <label class="btn btn-outline-warning" for="dvi_st_y"><i class="fas fa-exclamation-triangle"></i> Sarı / Dikkat</label>
                                                <input type="radio" class="btn-check" name="item_suspansiyon_alt_takim_status" id="dvi_st_r" value="red">
                                                <label class="btn btn-outline-danger" for="dvi_st_r"><i class="fas fa-times-circle"></i> Kırmızı / Acil</label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="item_suspansiyon_alt_takim_note" class="form-control form-control-sm" placeholder="Amortisörler, rot/rotil, burçlar, salıncak">
                                        </td>
                                    </tr>
                                    <!-- 6. Kaporta & Boya Durumu -->
                                    <tr>
                                        <td class="fw-semibold"><i class="fas fa-car-crash text-primary me-2"></i>Kaporta &amp; Boya Durumu</td>
                                        <td>
                                            <div class="btn-group btn-group-sm w-100" role="group">
                                                <input type="radio" class="btn-check" name="item_kaporta_boya_status" id="dvi_kb_g" value="green" checked>
                                                <label class="btn btn-outline-success" for="dvi_kb_g"><i class="fas fa-check"></i> Yeşil / İyi</label>
                                                <input type="radio" class="btn-check" name="item_kaporta_boya_status" id="dvi_kb_y" value="yellow">
                                                <label class="btn btn-outline-warning" for="dvi_kb_y"><i class="fas fa-exclamation-triangle"></i> Sarı / Dikkat</label>
                                                <input type="radio" class="btn-check" name="item_kaporta_boya_status" id="dvi_kb_r" value="red">
                                                <label class="btn btn-outline-danger" for="dvi_kb_r"><i class="fas fa-times-circle"></i> Kırmızı / Acil</label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="item_kaporta_boya_note" class="form-control form-control-sm" placeholder="Çizik, göçük, lokal boya veya değişen parça">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="dvi-general-notes">
                                <i class="fas fa-comment-alt text-muted me-1"></i> Genel Servis &amp; Ekspertiz Değerlendirme Notu
                            </label>
                            <textarea name="general_notes" id="dvi-general-notes" class="form-control" rows="2" placeholder="Müşteriye iletilecek genel servis tavsiyeleri ve ekspertiz özeti..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="button" class="btn btn-outline-info d-none" id="dvi-reload-btn">
                            <i class="fas fa-sync-alt me-1"></i> Listeyi Güncelle
                        </button>
                        <button type="submit" class="btn btn-primary" id="btn-submit-dvi">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="dvi-spinner" role="status" aria-hidden="true"></span>
                            <i class="fas fa-share-alt me-1" id="dvi-icon"></i> Ekspertizi Kaydet &amp; Paylaşım Linki Üret
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: YENİ ARAÇ -->
    <div class="modal fade" id="modal-add-vehicle" tabindex="-1" aria-labelledby="modal-add-vehicle-title" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-add-vehicle-title"><i class="fas fa-car text-danger me-2"></i>Yeni Araç Tanımla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-add-vehicle">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="veh-customer-id">Araç Sahibi (Müşteri)</label>
                            <select name="id_users_customer" id="veh-customer-id" class="form-select" required>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e($c['first_name'] . ' ' . $c['last_name']) ?> (<?= e($c['phone_number'] ?: '') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="veh-plate-number">Plaka</label>
                                <input type="text" name="plate_number" id="veh-plate-number" class="form-control font-monospace" placeholder="34 ABC 123" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="veh-vin">Şasi No (VIN)</label>
                                <input type="text" name="vin" id="veh-vin" class="form-control font-monospace" placeholder="WBA...">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="veh-brand">Marka</label>
                                <input type="text" name="brand" id="veh-brand" class="form-control" placeholder="BMW, Mercedes, Toyota..." required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="veh-model">Model</label>
                                <input type="text" name="model" id="veh-model" class="form-control" placeholder="320i, C200, Corolla..." required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="veh-year">Model Yılı</label>
                                <input type="number" name="year" id="veh-year" class="form-control" value="2022">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="veh-current-km">Güncel Kilometre</label>
                                <input type="number" name="current_km" id="veh-current-km" class="form-control" placeholder="45000">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary" id="btn-submit-vehicle">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="veh-spinner" role="status" aria-hidden="true"></span>
                            <i class="fas fa-check me-1" id="veh-icon"></i> Aracı Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    document.addEventListener('DOMContentLoaded', function() {
        // ---------------------------------------------------------------------
        // 1. DVI EKSPERTİZ MODAL & SUBMISSION
        // ---------------------------------------------------------------------
        const scoreSlider = document.getElementById('dvi-overall-score');
        const scoreDisplay = document.getElementById('dvi-score-display');
        if (scoreSlider && scoreDisplay) {
            scoreSlider.addEventListener('input', function() {
                scoreDisplay.textContent = escapeHtml(this.value);
            });
        }

        const dviReloadBtn = document.getElementById('dvi-reload-btn');
        if (dviReloadBtn) {
            dviReloadBtn.addEventListener('click', function() {
                window.location.reload();
            });
        }

        const copyLinkBtn = document.getElementById('dvi-copy-link-btn');
        if (copyLinkBtn) {
            copyLinkBtn.addEventListener('click', function() {
                const linkInput = document.getElementById('dvi-share-link');
                if (linkInput && linkInput.value) {
                    linkInput.select();
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(linkInput.value).then(() => {
                            const originalHtml = copyLinkBtn.innerHTML;
                            copyLinkBtn.innerHTML = '<i class="fas fa-check text-success me-1"></i> Kopyalandı!';
                            setTimeout(() => { copyLinkBtn.innerHTML = originalHtml; }, 2000);
                        }).catch(() => {
                            document.execCommand('copy');
                            alert('Link kopyalandı: ' + escapeHtml(linkInput.value));
                        });
                    } else {
                        document.execCommand('copy');
                        alert('Link kopyalandı: ' + escapeHtml(linkInput.value));
                    }
                }
            });
        }

        // Open DVI Modal button click
        document.querySelectorAll('.btn-new-dvi').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const vehicleId = this.getAttribute('data-id');
                const plate = this.getAttribute('data-plate') || '';
                const brandModel = this.getAttribute('data-brand-model') || '';

                const idInput = document.getElementById('dvi-vehicle-id');
                if (idInput) idInput.value = vehicleId;

                const plateEl = document.getElementById('dvi-modal-plate');
                if (plateEl) plateEl.textContent = plate;

                const vehicleEl = document.getElementById('dvi-modal-vehicle');
                if (vehicleEl) vehicleEl.textContent = brandModel;

                // Hide previous result section
                const shareResult = document.getElementById('dvi-share-result');
                if (shareResult) shareResult.classList.add('d-none');

                const reloadBtn = document.getElementById('dvi-reload-btn');
                if (reloadBtn) reloadBtn.classList.add('d-none');

                const submitBtn = document.getElementById('btn-submit-dvi');
                const spinner = document.getElementById('dvi-spinner');
                if (submitBtn) submitBtn.disabled = false;
                if (spinner) spinner.classList.add('d-none');

                const modalEl = document.getElementById('modal-new-dvi');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            });
        });

        // Form Submit: Save DVI Inspection
        const formSaveDvi = document.getElementById('form-save-dvi');
        if (formSaveDvi) {
            formSaveDvi.addEventListener('submit', async function(e) {
                e.preventDefault();
                const submitBtn = document.getElementById('btn-submit-dvi');
                const spinner = document.getElementById('dvi-spinner');
                const vehicleId = document.getElementById('dvi-vehicle-id')?.value;

                if (!vehicleId) {
                    alert('Lütfen bir araç seçiniz.');
                    return;
                }

                if (submitBtn) submitBtn.disabled = true;
                if (spinner) spinner.classList.remove('d-none');

                const inspectionType = document.getElementById('dvi-inspection-type')?.value || 'general_service';
                const overallScore = parseInt(document.getElementById('dvi-overall-score')?.value || '85', 10);
                const generalNotes = document.getElementById('dvi-general-notes')?.value || '';

                const checklistKeys = [
                    'motor_yagi',
                    'fren_balatalari',
                    'lastikler',
                    'aku_elektrik',
                    'suspansiyon_alt_takim',
                    'kaporta_boya'
                ];

                const items = {};
                checklistKeys.forEach(key => {
                    const checkedRadio = document.querySelector('input[name="item_' + key + '_status"]:checked');
                    const status = checkedRadio ? checkedRadio.value : 'green';
                    const noteInput = document.querySelector('input[name="item_' + key + '_note"]');
                    const note = noteInput ? noteInput.value.trim() : '';
                    items[key] = {
                        status: status,
                        note: note
                    };
                });

                const payload = {
                    id_vehicles: parseInt(vehicleId, 10),
                    inspection_type: inspectionType,
                    overall_score: overallScore,
                    general_notes: generalNotes,
                    items: items
                };

                try {
                    let res = await fetch('<?= site_url('verticals/save_vehicle_inspection') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    if (!res.ok && res.status === 404) {
                        res = await fetch('<?= site_url('api/v1/verticals/automotive/inspections') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        });
                    }

                    const json = await res.json().catch(() => ({}));
                    if (res.ok) {
                        const token = json.token || json.customer_shared_token || (json.inspection && json.inspection.customer_shared_token) || '';
                        const reportUrl = json.report_url || ('<?= site_url('api/v1/verticals/automotive/inspections/') ?>' + encodeURIComponent(token));

                        const shareResult = document.getElementById('dvi-share-result');
                        const linkInput = document.getElementById('dvi-share-link');
                        const openLinkBtn = document.getElementById('dvi-open-link');
                        const reloadBtn = document.getElementById('dvi-reload-btn');

                        if (linkInput) linkInput.value = reportUrl;
                        if (openLinkBtn) openLinkBtn.href = reportUrl;
                        if (shareResult) {
                            shareResult.classList.remove('d-none');
                            shareResult.scrollIntoView({ behavior: 'smooth' });
                        }
                        if (reloadBtn) reloadBtn.classList.remove('d-none');

                        alert('DVI Ekspertiz başarıyla kaydedildi! Müşteri paylaşım linki oluşturuldu.');
                    } else {
                        alert('Hata: ' + escapeHtml(json.message || json.error || 'Ekspertiz kaydedilemedi.'));
                    }
                } catch (err) {
                    alert('Ağ hatası: ' + escapeHtml(err.message));
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                    if (spinner) spinner.classList.add('d-none');
                }
            });
        }

        // ---------------------------------------------------------------------
        // 2. YENİ İŞ EMRİ BAŞLAT MODAL & SUBMISSION
        // ---------------------------------------------------------------------
        const formAddWo = document.getElementById('form-add-wo');
        if (formAddWo) {
            formAddWo.addEventListener('submit', async function(e) {
                e.preventDefault();
                const submitBtn = document.getElementById('btn-submit-wo');
                const spinner = document.getElementById('wo-spinner');

                const vehicleId = document.getElementById('wo-vehicle-id')?.value;
                if (!vehicleId) {
                    alert('Lütfen bir araç seçiniz.');
                    return;
                }

                if (submitBtn) submitBtn.disabled = true;
                if (spinner) spinner.classList.remove('d-none');

                const techId = document.getElementById('wo-technician-id')?.value;
                const complaint = (document.getElementById('wo-customer-complaint')?.value || '').trim();
                const estimatedCost = parseFloat(document.getElementById('wo-estimated-cost')?.value || '0');
                const deliveryDatetime = document.getElementById('wo-delivery-datetime')?.value || null;

                const payload = {
                    id_vehicles: parseInt(vehicleId, 10),
                    id_users_technician: techId ? parseInt(techId, 10) : null,
                    customer_complaint: complaint,
                    labor_items: complaint ? [{ description: complaint }] : [],
                    estimated_cost: isNaN(estimatedCost) ? 0.00 : estimatedCost,
                    delivery_datetime: deliveryDatetime,
                    status: 'created'
                };

                try {
                    let res = await fetch('<?= site_url('verticals/create_work_order') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    if (!res.ok && res.status === 404) {
                        res = await fetch('<?= site_url('api/v1/verticals/automotive/work_orders') ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        });
                    }

                    const json = await res.json().catch(() => ({}));
                    if (res.ok) {
                        alert('İş emri başarıyla başlatıldı!');
                        const modalEl = document.getElementById('modal-add-work-order');
                        if (modalEl && typeof bootstrap !== 'undefined') {
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        }
                        window.location.reload();
                    } else {
                        alert('Hata: ' + escapeHtml(json.message || json.error || 'İş emri oluşturulamadı.'));
                    }
                } catch (err) {
                    alert('Ağ hatası: ' + escapeHtml(err.message));
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                    if (spinner) spinner.classList.add('d-none');
                }
            });
        }

        // ---------------------------------------------------------------------
        // 3. İŞ EMRİ AŞAMA / DURUM GÜNCELLEME (.btn-wo-status)
        // ---------------------------------------------------------------------
        document.querySelectorAll('.btn-wo-status').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const status = this.getAttribute('data-status');
                if (!id || !status) return;

                const originalHtml = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

                try {
                    let res = await fetch('<?= site_url('verticals/update_work_order_status') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id: parseInt(id, 10),
                            work_order_id: parseInt(id, 10),
                            status: status
                        })
                    });

                    if (!res.ok && res.status === 404) {
                        res = await fetch('<?= site_url('api/v1/verticals/automotive/work_orders/') ?>' + encodeURIComponent(id) + '/status', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ status: status })
                        });
                    }

                    const json = await res.json().catch(() => ({}));
                    if (res.ok) {
                        window.location.reload();
                    } else {
                        alert('Hata: ' + escapeHtml(json.message || json.error || 'Aşama güncellenemedi.'));
                        this.disabled = false;
                        this.innerHTML = originalHtml;
                    }
                } catch (err) {
                    alert('Ağ hatası: ' + escapeHtml(err.message));
                    this.disabled = false;
                    this.innerHTML = originalHtml;
                }
            });
        });

        // ---------------------------------------------------------------------
        // 4. YENİ ARAÇ TANIMLAMA (#form-add-vehicle)
        // ---------------------------------------------------------------------
        const formAddVehicle = document.getElementById('form-add-vehicle');
        if (formAddVehicle) {
            formAddVehicle.addEventListener('submit', async function(e) {
                e.preventDefault();
                const submitBtn = document.getElementById('btn-submit-vehicle');
                const spinner = document.getElementById('veh-spinner');
                if (submitBtn) submitBtn.disabled = true;
                if (spinner) spinner.classList.remove('d-none');

                const fd = new FormData(this);
                const data = Object.fromEntries(fd.entries());
                try {
                    const res = await fetch('<?= site_url('api/v1/verticals/automotive/vehicles') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    const json = await res.json().catch(() => ({}));
                    if (res.ok) {
                        alert('Araç başarıyla kaydedildi!');
                        window.location.reload();
                    } else {
                        alert('Hata: ' + escapeHtml(json.error || json.message || 'İşlem başarısız'));
                    }
                } catch (err) {
                    alert('Ağ hatası: ' + escapeHtml(err.message));
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                    if (spinner) spinner.classList.add('d-none');
                }
            });
        }
    });
    </script>
</body>
</html>
