<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @var array $records
 * @var array $vitals
 * @var array $prescriptions
 * @var array $allergies
 * @var array $lab_orders
 * @var array $patients
 * @var array $providers
 */
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
                <h1 class="h3 fw-bold mb-1">
                    <i class="fas fa-stethoscope text-primary me-2"></i>Klinik EMR, Hayati Bulgular & Reçete (Clinical Suite)
                </h1>
                <p class="text-muted small mb-0">Elektronik Sağlık Kaydı (EHR), SOAP notları, tansiyon/nabız/BMI takibi, alerji rozetleri ve laboratuvar istemleri (OpenMRS / Bahmni standardı).</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modal-add-allergy">
                    <i class="fas fa-shield-virus me-1"></i> Alerji Kaydet
                </button>
                <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modal-add-vitals">
                    <i class="fas fa-heartbeat me-1"></i> Vital Ölçümü Gir
                </button>
                <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#modal-add-prescription">
                    <i class="fas fa-prescription me-1"></i> Reçete Yaz
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-soap">
                    <i class="fas fa-notes-medical me-1"></i> SOAP Vizit Notu Ekle
                </button>
            </div>
        </div>

        <!-- STATS -->
        <div class="row g-3 mb-4">
            <div class="col-sm-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <span class="text-muted small d-block mb-1">Klinik Vizit Notları</span>
                        <h3 class="fw-bold mb-0 text-primary"><?= count($records) ?></h3>
                        <small class="text-muted"><i class="fas fa-file-medical me-1"></i>SOAP Kayıtları</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <span class="text-muted small d-block mb-1">Vital Ölçümleri</span>
                        <h3 class="fw-bold mb-0 text-success"><?= count($vitals) ?></h3>
                        <small class="text-success"><i class="fas fa-heartbeat me-1"></i>Tansiyon & Nabız</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <span class="text-muted small d-block mb-1">Kayıtlı Alerjiler</span>
                        <h3 class="fw-bold mb-0 text-danger"><?= count($allergies) ?></h3>
                        <small class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Hasta Güvenliği</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <span class="text-muted small d-block mb-1">Reçeteler & İlaçlar</span>
                        <h3 class="fw-bold mb-0 text-info"><?= count($prescriptions) ?></h3>
                        <small class="text-info"><i class="fas fa-pills me-1"></i>Tedavi Geçmişi</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABS -->
        <ul class="nav nav-tabs border-bottom mb-3" role="tablist">
            <li class="nav-item">
                <a class="nav-link active fw-bold" data-bs-toggle="tab" href="#tab-soap">
                    <i class="fas fa-file-medical-alt me-2"></i>SOAP Vizit Notları (<?= count($records) ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-bold" data-bs-toggle="tab" href="#tab-vitals">
                    <i class="fas fa-heartbeat me-2"></i>Hayati Bulgular (Vitals & BMI) (<?= count($vitals) ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-bold" data-bs-toggle="tab" href="#tab-prescriptions">
                    <i class="fas fa-prescription-bottle-alt me-2"></i>Reçeteler & İlaçlar (<?= count($prescriptions) ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-bold" data-bs-toggle="tab" href="#tab-allergies">
                    <i class="fas fa-allergies me-2"></i>Alerji Rozetleri (<?= count($allergies) ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-bold" data-bs-toggle="tab" href="#tab-labs">
                    <i class="fas fa-vial me-2"></i>Laboratuvar & Tetkik (<?= count($lab_orders) ?>)
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- TAB 1: SOAP NOTLARI -->
            <div class="tab-pane fade show active" id="tab-soap">
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tarih</th>
                                    <th>Danışan / Hasta</th>
                                    <th>Hekim / Uzman</th>
                                    <th>Kayıt Türü</th>
                                    <th>Şikayet (S) & Bulgular (O)</th>
                                    <th>Teşhis (A) & Tedavi Planı (P)</th>
                                    <th>Gizlilik</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($records)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">Kayıtlı klinik charting veya SOAP notu bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($records as $r): ?>
                                        <tr>
                                            <td><small class="text-muted"><?= date('d.m.Y H:i', strtotime($r['created_at'])) ?></small></td>
                                            <td class="fw-bold"><?= e(trim($r['patient_first_name'] . ' ' . $r['patient_last_name'])) ?></td>
                                            <td><?= e(trim($r['doc_first_name'] . ' ' . $r['doc_last_name'])) ?></td>
                                            <td><span class="badge bg-primary"><?= strtoupper(e($r['record_type'])) ?></span></td>
                                            <td>
                                                <div class="small"><strong>S:</strong> <?= e(mb_strimwidth($r['subjective'] ?? '-', 0, 40, '...')) ?></div>
                                                <div class="small text-muted"><strong>O:</strong> <?= e(mb_strimwidth($r['objective'] ?? '-', 0, 40, '...')) ?></div>
                                            </td>
                                            <td>
                                                <div class="small text-danger"><strong>A:</strong> <?= e(mb_strimwidth($r['assessment'] ?? '-', 0, 40, '...')) ?></div>
                                                <div class="small text-success"><strong>P:</strong> <?= e(mb_strimwidth($r['plan'] ?? '-', 0, 40, '...')) ?></div>
                                            </td>
                                            <td>
                                                <?php if ($r['is_confidential']): ?>
                                                    <span class="badge bg-danger"><i class="fas fa-lock me-1"></i>Gizli Dosya</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark border">Normal</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($r['id_appointments'])): ?>
                                                    <button class="btn btn-sm btn-outline-success btn-telehealth" data-id="<?= $r['id_appointments'] ?>">
                                                        <i class="fas fa-video me-1"></i> Telehealth
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted small">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: HAYATİ BULGULAR (VITALS) -->
            <div class="tab-pane fade" id="tab-vitals">
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ölçüm Tarihi</th>
                                    <th>Hasta</th>
                                    <th>Tansiyon (mmHg)</th>
                                    <th>Nabız (bpm)</th>
                                    <th>Ateş (°C)</th>
                                    <th>Kilo & Boy</th>
                                    <th>VKİ (BMI)</th>
                                    <th>SpO2 & Şeker</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($vitals)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">Kayıtlı vital bulgu bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($vitals as $v): ?>
                                        <tr>
                                            <td><?= date('d.m.Y H:i', strtotime($v['recorded_at'])) ?></td>
                                            <td class="fw-bold"><?= e(trim($v['first_name'] . ' ' . $v['last_name'])) ?></td>
                                            <td>
                                                <?php if ($v['systolic_bp'] && $v['diastolic_bp']): ?>
                                                    <span class="badge bg-light text-dark border fs-6"><?= $v['systolic_bp'] ?> / <?= $v['diastolic_bp'] ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="text-danger fw-bold"><i class="fas fa-heartbeat me-1"></i><?= $v['pulse_rate'] ?: '-' ?></span></td>
                                            <td><?= $v['temperature_c'] ? $v['temperature_c'] . ' °C' : '-' ?></td>
                                            <td>
                                                <?= $v['weight_kg'] ? $v['weight_kg'] . ' kg' : '-' ?> / 
                                                <?= $v['height_cm'] ? $v['height_cm'] . ' cm' : '-' ?>
                                            </td>
                                            <td>
                                                <?php if ($v['bmi']): ?>
                                                    <span class="badge bg-info text-dark"><?= $v['bmi'] ?> kg/m²</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="d-block">SpO2: <strong><?= $v['spo2_percent'] ? $v['spo2_percent'] . '%' : '-' ?></strong></small>
                                                <small class="text-muted">Şeker: <?= $v['blood_glucose'] ? $v['blood_glucose'] . ' mg/dL' : '-' ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: REÇETELER -->
            <div class="tab-pane fade" id="tab-prescriptions">
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tarih</th>
                                    <th>Hasta</th>
                                    <th>Hekim</th>
                                    <th>İlaç Adı</th>
                                    <th>Dozaj</th>
                                    <th>Kullanım Şekli</th>
                                    <th>Süre</th>
                                    <th>Talimatlar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($prescriptions)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">Kayıtlı reçete bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($prescriptions as $p): ?>
                                        <tr>
                                            <td><?= date('d.m.Y H:i', strtotime($p['prescribed_at'])) ?></td>
                                            <td class="fw-bold"><?= e(trim($p['patient_first_name'] . ' ' . $p['patient_last_name'])) ?></td>
                                            <td><?= e(trim($p['doc_first_name'] . ' ' . $p['doc_last_name'])) ?></td>
                                            <td class="fw-bold text-primary"><i class="fas fa-pills me-1"></i><?= e($p['medication_name']) ?></td>
                                            <td><?= e($p['dosage']) ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= e($p['frequency']) ?></span></td>
                                            <td><?= $p['duration_days'] ?> gün</td>
                                            <td><small class="text-muted"><?= e($p['instructions'] ?: '-') ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 4: ALERJİLER -->
            <div class="tab-pane fade" id="tab-allergies">
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Hasta</th>
                                    <th>Alerjen Madde / İlaç</th>
                                    <th>Şiddet Derecesi</th>
                                    <th>Reaksiyon Belirtileri & Notlar</th>
                                    <th>Tespit Tarihi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($allergies)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Kayıtlı alerji bildirimi bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($allergies as $a): ?>
                                        <tr>
                                            <td class="fw-bold"><?= e(trim($a['first_name'] . ' ' . $a['last_name'])) ?></td>
                                            <td class="fw-bold text-danger"><i class="fas fa-exclamation-triangle me-1"></i><?= e($a['allergen']) ?></td>
                                            <td>
                                                <?php if ($a['severity'] === 'severe'): ?>
                                                    <span class="badge bg-danger">Yüksek (Anafilaksi Riski)</span>
                                                <?php elseif ($a['severity'] === 'moderate'): ?>
                                                    <span class="badge bg-warning text-dark">Orta Şiddetli</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Hafif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= e($a['reaction_notes'] ?: '-') ?></td>
                                            <td><?= $a['identified_at'] ? date('d.m.Y', strtotime($a['identified_at'])) : '-' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 5: LABORATUVAR & TETKİK -->
            <div class="tab-pane fade" id="tab-labs">
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tarih</th>
                                    <th>Hasta</th>
                                    <th>Tetkik / Tahlil Adı</th>
                                    <th>Kategori</th>
                                    <th>Durum</th>
                                    <th>Sonuç Özeti</th>
                                    <th>Referans Aralığı</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($lab_orders)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Kayıtlı laboratuvar istemi bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($lab_orders as $l): ?>
                                        <tr>
                                            <td><?= date('d.m.Y H:i', strtotime($l['created_at'])) ?></td>
                                            <td class="fw-bold"><?= e(trim($l['first_name'] . ' ' . $l['last_name'])) ?></td>
                                            <td class="fw-bold"><i class="fas fa-vial me-1 text-info"></i><?= e($l['test_name']) ?></td>
                                            <td><span class="badge bg-light text-dark border text-uppercase"><?= e($l['category']) ?></span></td>
                                            <td><span class="badge bg-info text-uppercase"><?= e($l['status']) ?></span></td>
                                            <td>
                                                <?php if ($l['is_abnormal']): ?>
                                                    <span class="text-danger fw-bold"><i class="fas fa-exclamation-circle me-1"></i><?= e($l['result_summary']) ?></span>
                                                <?php else: ?>
                                                    <?= e($l['result_summary'] ?: '-') ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><small class="text-muted"><?= e($l['normal_range'] ?: '-') ?></small></td>
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

    <!-- MODAL: YENİ VİTAL BULGU -->
    <div class="modal fade" id="modal-add-vitals" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-heartbeat text-success me-2"></i>Vital Bulgu Ölçüm Girişi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form onsubmit="submitVitals(event)">
                    <div class="modal-body row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Hasta / Danışan *</label>
                            <select name="id_users_patient" class="form-select" required>
                                <option value="">Hasta Seçiniz...</option>
                                <?php foreach ($patients as $pt): ?>
                                    <option value="<?= $pt['id'] ?>"><?= e(trim($pt['first_name'] . ' ' . $pt['last_name'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Sistolik (Büyük) Tansiyon</label>
                            <input type="number" name="systolic_bp" class="form-control" placeholder="120">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Diyastolik (Küçük) Tansiyon</label>
                            <input type="number" name="diastolic_bp" class="form-control" placeholder="80">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Nabız (bpm)</label>
                            <input type="number" name="pulse_rate" class="form-control" placeholder="72">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Vücut Sıcaklığı (°C)</label>
                            <input type="number" step="0.1" name="temperature_c" class="form-control" placeholder="36.5">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Kilo (kg)</label>
                            <input type="number" step="0.1" name="weight_kg" class="form-control" placeholder="70.5">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Boy (cm)</label>
                            <input type="number" step="0.1" name="height_cm" class="form-control" placeholder="175">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Oksijen Doygunluğu (SpO2 %)</label>
                            <input type="number" name="spo2_percent" class="form-control" placeholder="98">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Açlık Kan Şekeri (mg/dL)</label>
                            <input type="number" step="0.1" name="blood_glucose" class="form-control" placeholder="95">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> Ölçümleri Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: YENİ REÇETE -->
    <div class="modal fade" id="modal-add-prescription" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-prescription text-info me-2"></i>Yeni Reçete Yaz</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form onsubmit="submitPrescription(event)">
                    <div class="modal-body row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Hasta *</label>
                            <select name="id_users_patient" class="form-select" required>
                                <option value="">Hasta Seçiniz...</option>
                                <?php foreach ($patients as $pt): ?>
                                    <option value="<?= $pt['id'] ?>"><?= e(trim($pt['first_name'] . ' ' . $pt['last_name'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-8">
                            <label class="form-label small fw-bold">İlaç Adı *</label>
                            <input type="text" name="medication_name" class="form-control" placeholder="Örn: Parol 500mg Tablet" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold">Dozaj</label>
                            <input type="text" name="dosage" class="form-control" value="500 mg">
                        </div>
                        <div class="col-8">
                            <label class="form-label small fw-bold">Kullanım Sıklığı</label>
                            <input type="text" name="frequency" class="form-control" value="Günde 2 defa (Tok)">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold">Tedavi Süresi (Gün)</label>
                            <input type="number" name="duration_days" class="form-control" value="7">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Kullanım Talimatı & Uyarılar</label>
                            <textarea name="instructions" class="form-control" rows="2" placeholder="Bol su ile içiniz, alkolle almayınız..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="submit" class="btn btn-info text-white"><i class="fas fa-print me-1"></i> Reçeteyi Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: YENİ ALERJİ KAYDI -->
    <div class="modal fade" id="modal-add-allergy" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-allergies me-2"></i>Alerji Uyarısı Ekle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form onsubmit="submitAllergy(event)">
                    <div class="modal-body row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Hasta *</label>
                            <select name="id_users_patient" class="form-select" required>
                                <option value="">Hasta Seçiniz...</option>
                                <?php foreach ($patients as $pt): ?>
                                    <option value="<?= $pt['id'] ?>"><?= e(trim($pt['first_name'] . ' ' . $pt['last_name'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-8">
                            <label class="form-label small fw-bold">Alerjen Madde / İlaç *</label>
                            <input type="text" name="allergen" class="form-control" placeholder="Örn: Penisilin, Fıstık, Arı Sütü" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold">Şiddet</label>
                            <select name="severity" class="form-select">
                                <option value="mild">Hafif</option>
                                <option value="moderate" selected>Orta</option>
                                <option value="severe">Şiddetli (Anafilaksi)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Reaksiyon Belirtileri</label>
                            <input type="text" name="reaction_notes" class="form-control" placeholder="Ciltte döküntü, nefes darlığı, kaşıntı...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="submit" class="btn btn-danger"><i class="fas fa-check me-1"></i> Alerjiyi Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: YENİ SOAP NOTU -->
    <div class="modal fade" id="modal-add-soap" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-notes-medical text-primary me-2"></i>Yeni Klinik SOAP Notu Girişi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-add-soap">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Danışan / Hasta</label>
                                <select name="id_users_customer" class="form-select" required>
                                    <?php foreach ($patients as $pt): ?>
                                        <option value="<?= $pt['id'] ?>"><?= e($pt['first_name'] . ' ' . $pt['last_name']) ?> (<?= e($pt['phone_number'] ?: '') ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Hekim / Terapist / Uzman</label>
                                <select name="id_users_provider" class="form-select" required>
                                    <?php foreach ($providers as $pr): ?>
                                        <option value="<?= $pr['id'] ?>"><?= e($pr['first_name'] . ' ' . $pr['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-primary">S — Subjective (Anamnez / Danışanın Şikayet ve İfadeleri)</label>
                            <textarea name="subjective" rows="2" class="form-control" placeholder="Danışanın belirttiği semptomlar, başlangıç tarihi ve genel şikayetler..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary">O — Objective (Fizik Muayene, Vital Bulgular & Testler)</label>
                            <textarea name="objective" rows="2" class="form-control" placeholder="Tansiyon, nabız, palpasyon bulguları, laboratuvar test değerleri..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-danger">A — Assessment (Teşhis, Klinik Değerlendirme & Tanı / ICD-10)</label>
                            <textarea name="assessment" rows="2" class="form-control" placeholder="Klinik tanı, ICD-10 kodu veya psikolojik değerlendirme..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-success">P — Plan (Tedavi Planı, Reçete, Seans Hedefleri & Egzersizler)</label>
                            <textarea name="plan" rows="2" class="form-control" placeholder="Uygulanacak tedavi adımları, ev ödevleri, sonraki randevu tarihi..."></textarea>
                        </div>

                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_confidential" value="1" checked id="switch-confidential">
                            <label class="form-check-label fw-semibold" for="switch-confidential">Bu dosya gizli klinik kayıt statüsündedir (KVKK / Hasta Mahremiyeti)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> SOAP Notunu Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    async function submitVitals(e) {
        e.preventDefault();
        const json = Object.fromEntries(new FormData(e.target).entries());
        const res = await fetch('<?= site_url('verticals/save_patient_vitals') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': '<?= $this->security->get_csrf_hash() ?>'},
            body: JSON.stringify(json)
        });
        const data = await res.json();
        if (data.success) location.reload();
    }

    async function submitPrescription(e) {
        e.preventDefault();
        const json = Object.fromEntries(new FormData(e.target).entries());
        const res = await fetch('<?= site_url('verticals/save_patient_prescription') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': '<?= $this->security->get_csrf_hash() ?>'},
            body: JSON.stringify(json)
        });
        const data = await res.json();
        if (data.success) location.reload();
    }

    async function submitAllergy(e) {
        e.preventDefault();
        const json = Object.fromEntries(new FormData(e.target).entries());
        const res = await fetch('<?= site_url('verticals/save_patient_allergy') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': '<?= $this->security->get_csrf_hash() ?>'},
            body: JSON.stringify(json)
        });
        const data = await res.json();
        if (data.success) location.reload();
    }

    document.getElementById('form-add-soap').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const data = Object.fromEntries(fd.entries());
        try {
            const res = await fetch('<?= site_url('api/v1/verticals/clinic/records') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            if (res.ok) {
                alert('Klinik dosya başarıyla kaydedildi!');
                window.location.reload();
            } else {
                alert('Hata: ' + (json.error || 'İşlem başarısız'));
            }
        } catch (err) {
            alert('Ağ hatası: ' + err.message);
        }
    });

    document.querySelectorAll('.btn-telehealth').forEach(btn => {
        btn.addEventListener('click', async function() {
            const apptId = this.getAttribute('data-id');
            try {
                const res = await fetch('<?= site_url('api/v1/verticals/clinic/telehealth/') ?>' + apptId);
                const json = await res.json();
                if (json.telehealth_url) {
                    window.open(json.telehealth_url, '_blank');
                } else {
                    alert('Görüşme linki oluşturulamadı.');
                }
            } catch (err) {
                alert('Ağ hatası: ' + err.message);
            }
        });
    });
    </script>
</body>
</html>
