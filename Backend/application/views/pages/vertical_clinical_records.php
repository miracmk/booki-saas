<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @var array $records
 * @var array $patients
 * @var array $providers
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?php $this->load->view('components/backend_head'); ?>
    <title><?= e(vars('page_title')) ?> - BooKi</title>
    <style>
        .soap-badge {
            display: inline-block;
            width: 24px;
            height: 24px;
            line-height: 24px;
            text-align: center;
            border-radius: 50%;
            font-weight: 700;
            font-size: 12px;
        }
        .soap-badge-s { background-color: #cfe2ff; color: #084298; }
        .soap-badge-o { background-color: #e2e3e5; color: #41464b; }
        .soap-badge-a { background-color: #f8d7da; color: #842029; }
        .soap-badge-p { background-color: #d1e7dd; color: #0f5132; }

        .soap-block {
            border-radius: 8px;
            border-left: 4px solid;
            padding: 12px 16px;
            background-color: #fdfdfd;
        }
        .soap-block-s { border-left-color: #0d6efd; background-color: #f8faff; }
        .soap-block-o { border-left-color: #6c757d; background-color: #fbfbfb; }
        .soap-block-a { border-left-color: #dc3545; background-color: #fff9f9; }
        .soap-block-p { border-left-color: #198754; background-color: #f8fff9; }

        @media print {
            body * {
                visibility: hidden !important;
            }
            #modal-view-soap,
            #modal-view-soap .modal-dialog,
            #modal-view-soap .modal-content,
            #print-soap-area,
            #print-soap-area * {
                visibility: visible !important;
            }
            #modal-view-soap {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }
            #modal-view-soap .modal-dialog {
                max-width: 100% !important;
                margin: 0 !important;
            }
            #modal-view-soap .modal-content {
                border: none !important;
                box-shadow: none !important;
            }
            .modal-backdrop,
            .no-print,
            .btn-close,
            #modal-view-soap .modal-footer {
                display: none !important;
            }
        }
    </style>
</head>
<body class="backend-body">
    <?php $this->load->view('components/backend_header'); ?>

    <div class="container-fluid py-4 px-md-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1"><i class="fas fa-stethoscope text-primary me-2"></i>Klinik Kayıtları, SOAP Notları & Telehealth</h1>
                <p class="text-muted small mb-0">EHR klinik charting (Subjective, Objective, Assessment, Plan), anamnez formları, hasta sigorta/SGK poliçeleri ve şifreli online seans odaları.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#modal-patient-insurance" id="btn-top-insurance">
                    <i class="fas fa-id-card me-1"></i> Hasta Sigorta / Poliçe
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-soap">
                    <i class="fas fa-notes-medical me-1"></i> Yeni Klinik SOAP Notu Ekle
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="fas fa-file-medical-alt text-info me-2"></i>Klinik Muayene & Danışan Takip Dosyaları</h5>
                <span class="badge bg-secondary"><?= count($records) ?> Dosya Kaydı</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="table-clinical-records">
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
                                    <td><span class="badge bg-primary"><?= strtoupper(e($r['record_type'] ?? 'SOAP')) ?></span></td>
                                    <td>
                                        <div class="small"><strong>S:</strong> <?= e(mb_strimwidth($r['subjective'] ?? '-', 0, 40, '...')) ?></div>
                                        <div class="small text-muted"><strong>O:</strong> <?= e(mb_strimwidth($r['objective'] ?? '-', 0, 40, '...')) ?></div>
                                    </td>
                                    <td>
                                        <div class="small text-danger"><strong>A:</strong> <?= e(mb_strimwidth($r['assessment'] ?? '-', 0, 40, '...')) ?></div>
                                        <div class="small text-success"><strong>P:</strong> <?= e(mb_strimwidth($r['plan'] ?? '-', 0, 40, '...')) ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($r['is_confidential'])): ?>
                                            <span class="badge bg-danger"><i class="fas fa-lock me-1"></i>Gizli Dosya</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap align-items-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-view-soap"
                                                title="Detaylı SOAP Charting Görüntüle"
                                                data-record="<?= htmlspecialchars(json_encode([
                                                    'id' => (int) $r['id'],
                                                    'date' => date('d.m.Y H:i', strtotime($r['created_at'])),
                                                    'patient_id' => (int) $r['id_users_customer'],
                                                    'patient_name' => trim($r['patient_first_name'] . ' ' . $r['patient_last_name']),
                                                    'doctor_name' => trim($r['doc_first_name'] . ' ' . $r['doc_last_name']),
                                                    'record_type' => $r['record_type'] ?? 'soap_note',
                                                    'id_appointments' => !empty($r['id_appointments']) ? (int) $r['id_appointments'] : null,
                                                    'is_confidential' => !empty($r['is_confidential']) ? 1 : 0,
                                                    'subjective' => $r['subjective'] ?? '',
                                                    'objective' => $r['objective'] ?? '',
                                                    'assessment' => $r['assessment'] ?? '',
                                                    'plan' => $r['plan'] ?? ''
                                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fas fa-eye me-1"></i> Detay
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-info btn-row-insurance"
                                                title="Hasta Sigorta / SGK Poliçesi"
                                                data-patient-id="<?= (int) $r['id_users_customer'] ?>"
                                                data-patient-name="<?= e(trim($r['patient_first_name'] . ' ' . $r['patient_last_name'])) ?>">
                                                <i class="fas fa-id-card"></i>
                                            </button>
                                            <?php if (!empty($r['id_appointments'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success btn-telehealth" data-id="<?= (int) $r['id_appointments'] ?>" title="Online Seans Odası Başlat">
                                                    <i class="fas fa-video me-1"></i> Telehealth
                                                </button>
                                            <?php endif; ?>
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

    <!-- MODAL 1: YENİ SOAP NOTU GİRİŞİ (#modal-add-soap) -->
    <div class="modal fade" id="modal-add-soap" tabindex="-1" aria-labelledby="modalAddSoapLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddSoapLabel"><i class="fas fa-notes-medical text-primary me-2"></i>Yeni Klinik SOAP Notu Girişi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-add-soap">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Danışan / Hasta <span class="text-danger">*</span></label>
                                <select name="id_users_customer" class="form-select" required>
                                    <option value="">Danışan seçiniz...</option>
                                    <?php foreach ($patients as $pt): ?>
                                        <option value="<?= (int) $pt['id'] ?>"><?= e($pt['first_name'] . ' ' . $pt['last_name']) ?> (<?= e($pt['phone_number'] ?: 'Tel yok') ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Hekim / Terapist / Uzman <span class="text-danger">*</span></label>
                                <select name="id_users_provider" class="form-select" required>
                                    <option value="">Uzman seçiniz...</option>
                                    <?php foreach ($providers as $pr): ?>
                                        <option value="<?= (int) $pr['id'] ?>"><?= e($pr['first_name'] . ' ' . $pr['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Dosya / Kayıt Türü</label>
                                <select name="record_type" class="form-select">
                                    <option value="soap_note" selected>Klinik SOAP Notu</option>
                                    <option value="anamnesis">Anamnez / İlk Değerlendirme</option>
                                    <option value="prescription">Reçete & İlaç Listesi</option>
                                    <option value="lab_result">Laboratuvar & Tetkik Raporu</option>
                                    <option value="diet_plan">Beslenme & Diyet Planı</option>
                                    <option value="vet_record">Veteriner Muayene Kaydı</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Randevu / Seans İlişkilendir (Opsiyonel)</label>
                                <input type="number" name="id_appointments" class="form-control" placeholder="Randevu No (Örn: 1042)" min="1">
                                <div class="form-text">SOAP notu belirli bir randevuya aitse seans numarasını yazabilirsiniz.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-primary">S — Subjective (Anamnez / Danışanın Şikayet ve İfadeleri)</label>
                            <textarea name="subjective" rows="3" class="form-control" placeholder="Danışanın belirttiği semptomlar, başlangıç tarihi, şikayetler, alerjiler ve geçmiş medikal öykü..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary">O — Objective (Fizik Muayene, Vital Bulgular & Testler)</label>
                            <textarea name="objective" rows="3" class="form-control" placeholder="Tansiyon, nabız, palpasyon bulguları, laboratuvar test değerleri, görüntüleme bulguları..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-danger">A — Assessment (Teşhis, Klinik Değerlendirme & Tanı)</label>
                            <textarea name="assessment" rows="3" class="form-control" placeholder="Klinik tanı, ICD-10 kodu, diferansiyel tanı veya psikolojik durum değerlendirmesi..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-success">P — Plan (Tedavi Planı, Reçete, Seans Hedefleri & Egzersizler)</label>
                            <textarea name="plan" rows="3" class="form-control" placeholder="Uygulanacak tedavi adımları, ev ödevleri, reçete edilen ilaçlar, sonraki kontrol tarihi..."></textarea>
                        </div>

                        <div class="form-check form-switch mt-3 p-3 bg-light rounded border">
                            <input class="form-check-input" type="checkbox" name="is_confidential" value="1" checked id="switch-confidential">
                            <label class="form-check-label fw-semibold" for="switch-confidential">
                                <i class="fas fa-lock text-danger me-1"></i> Bu dosya gizli klinik kayıt statüsündedir (KVKK / Hasta Mahremiyeti)
                            </label>
                            <div class="small text-muted">İşaretlendiğinde yalnızca yetkili hekim ve klinik yöneticileri bu içeriğe erişebilir.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary" id="btn-save-soap">
                            <i class="fas fa-save me-1"></i> SOAP Notunu Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: KLİNİK DOSYA DETAYI & CHARTING (#modal-view-soap) -->
    <div class="modal fade" id="modal-view-soap" tabindex="-1" aria-labelledby="modalViewSoapLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalViewSoapLabel">
                        <i class="fas fa-file-medical text-primary me-2"></i>Klinik Dosya Detayı & Charting
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body p-4" id="print-soap-area">
                    <!-- Üst Bilgi Kartı -->
                    <div class="card border mb-3">
                        <div class="card-body p-3 bg-white">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <h5 class="fw-bold text-dark mb-1" id="view-patient-name">-</h5>
                                    <div class="text-muted small">
                                        <span><i class="fas fa-user-md me-1 text-primary"></i>Hekim/Uzman: <strong id="view-doctor-name">-</strong></span>
                                        <span class="ms-3"><i class="far fa-calendar-alt me-1 text-secondary"></i>Tarih: <span id="view-date">-</span></span>
                                    </div>
                                    <div class="text-muted small mt-1" id="view-appointment-row" style="display:none;">
                                        <i class="fas fa-calendar-check me-1 text-info"></i>İlişkili Randevu / Seans No: <strong id="view-appointment-id">-</strong>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div id="view-type-badge" class="mb-1"><span class="badge bg-primary">SOAP_NOTE</span></div>
                                    <div id="view-confidential-badge"><span class="badge bg-danger"><i class="fas fa-lock me-1"></i>Gizli Dosya</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SOAP Bölümleri -->
                    <div class="d-flex flex-column gap-3">
                        <!-- S -->
                        <div class="soap-block soap-block-s">
                            <div class="d-flex align-items-center mb-2">
                                <span class="soap-badge soap-badge-s me-2">S</span>
                                <h6 class="fw-bold text-primary mb-0">Subjective (Anamnez / Danışanın Şikayet & İfadeleri)</h6>
                            </div>
                            <div class="text-secondary small" id="view-subjective" style="white-space: pre-wrap; word-break: break-word;">-</div>
                        </div>

                        <!-- O -->
                        <div class="soap-block soap-block-o">
                            <div class="d-flex align-items-center mb-2">
                                <span class="soap-badge soap-badge-o me-2">O</span>
                                <h6 class="fw-bold text-secondary mb-0">Objective (Fizik Muayene, Bulgular, Tetkikler & Vital Bulgular)</h6>
                            </div>
                            <div class="text-secondary small" id="view-objective" style="white-space: pre-wrap; word-break: break-word;">-</div>
                        </div>

                        <!-- A -->
                        <div class="soap-block soap-block-a">
                            <div class="d-flex align-items-center mb-2">
                                <span class="soap-badge soap-badge-a me-2">A</span>
                                <h6 class="fw-bold text-danger mb-0">Assessment (Teşhis, Tanı & Klinik Değerlendirme)</h6>
                            </div>
                            <div class="text-secondary small" id="view-assessment" style="white-space: pre-wrap; word-break: break-word;">-</div>
                        </div>

                        <!-- P -->
                        <div class="soap-block soap-block-p">
                            <div class="d-flex align-items-center mb-2">
                                <span class="soap-badge soap-badge-p me-2">P</span>
                                <h6 class="fw-bold text-success mb-0">Plan (Tedavi Planı, Reçete, Ev Ödevleri & Takip)</h6>
                            </div>
                            <div class="text-secondary small" id="view-plan" style="white-space: pre-wrap; word-break: break-word;">-</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" id="btn-print-soap">
                        <i class="fas fa-print me-1"></i> Yazdır / PDF
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 3: HASTA SİGORTA / SGK / POLİÇE (#modal-patient-insurance) -->
    <div class="modal fade" id="modal-patient-insurance" tabindex="-1" aria-labelledby="modalPatientInsuranceLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPatientInsuranceLabel"><i class="fas fa-id-card text-info me-2"></i>Hasta Sigorta & Poliçe Bilgileri</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-patient-insurance">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Danışan / Hasta <span class="text-danger">*</span></label>
                            <select name="id_users_customer" id="insurance-patient-select" class="form-select" required>
                                <option value="">Hasta seçiniz...</option>
                                <?php foreach ($patients as $pt): ?>
                                    <option value="<?= (int) $pt['id'] ?>"><?= e($pt['first_name'] . ' ' . $pt['last_name']) ?> (<?= e($pt['phone_number'] ?: 'Tel yok') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text" id="insurance-loading-hint" style="display:none;">
                                <span class="spinner-border spinner-border-sm me-1"></span> Mevcut sigorta kaydı kontrol ediliyor...
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Sigorta Kurumu / Sağlayıcı <span class="text-danger">*</span></label>
                            <input type="text" name="provider_name" id="insurance-provider-name" class="form-control" placeholder="Örn: SGK, Allianz, Acıbadem Sigorta, AXA" required list="insurance-providers-list">
                            <datalist id="insurance-providers-list">
                                <option value="SGK (Genel Sağlık Sigortası)">
                                <option value="Allianz Sigorta">
                                <option value="Acıbadem Sigorta / Bupa">
                                <option value="Anadolu Sigorta">
                                <option value="AXA Sigorta">
                                <option value="Mapfre Sigorta">
                                <option value="Aksigorta">
                                <option value="Türkiye Sigorta">
                                <option value="Özel Sağlık Sigortası">
                            </datalist>
                        </div>

                        <div class="row">
                            <div class="col-md-7 mb-3">
                                <label class="form-label fw-semibold">Poliçe / Dosya No <span class="text-danger">*</span></label>
                                <input type="text" name="policy_number" id="insurance-policy-number" class="form-control" placeholder="Poliçe veya provizyon no" required>
                            </div>
                            <div class="col-md-5 mb-3">
                                <label class="form-label fw-semibold">Karşılama (%)</label>
                                <input type="number" name="coverage_ratio" id="insurance-coverage-ratio" class="form-control" min="0" max="100" value="100" placeholder="100">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Geçerlilik Bitiş Tarihi</label>
                            <input type="date" name="valid_until" id="insurance-valid-until" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Poliçe / Provizyon Notları</label>
                            <textarea name="notes" id="insurance-notes" rows="2" class="form-control" placeholder="Muafiyetler, anlaşmalı kurum provizyon şartları, seans limitleri..."></textarea>
                        </div>

                        <div class="alert alert-info py-2 px-3 small mb-0">
                            <i class="fas fa-info-circle me-1"></i> Bu bilgiler klinik faturalandırma ve provizyon işlemlerinde otomatik uygulanır.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-info text-white" id="btn-save-insurance">
                            <i class="fas fa-save me-1"></i> Poliçeyi Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Strict HTML Sanitization helper
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function nl2br(str) {
        if (!str || str.trim() === '') return '<span class="text-muted fst-italic">Kayıt girilmemiş</span>';
        return escapeHtml(str).replace(/\r\n|\r|\n/g, '<br>');
    }

    // 1. SOAP NOTU EKLEME FORMU SUBMISSION
    const formAddSoap = document.getElementById('form-add-soap');
    if (formAddSoap) {
        formAddSoap.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btnSave = document.getElementById('btn-save-soap');
            const originalBtnHtml = btnSave ? btnSave.innerHTML : '';
            if (btnSave) {
                btnSave.disabled = true;
                btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Kaydediliyor...';
            }

            const fd = new FormData(this);
            const data = Object.fromEntries(fd.entries());

            // Explicitly set is_confidential: 1 if switch is checked, 0 otherwise
            const switchEl = document.getElementById('switch-confidential');
            data.is_confidential = (switchEl && switchEl.checked) ? 1 : 0;

            // Handle optional appointment id
            if (data.id_appointments && String(data.id_appointments).trim() !== '') {
                data.id_appointments = parseInt(data.id_appointments, 10);
            } else {
                delete data.id_appointments;
            }

            try {
                let res;
                let requestHeaders = {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                };

                try {
                    res = await fetch('<?= site_url('verticals/add_clinical_record') ?>', {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify(data)
                    });
                    if (!res.ok && res.status === 404) {
                        throw new Error('Fallback endpoint required');
                    }
                } catch (fallbackErr) {
                    res = await fetch('<?= site_url('api/v1/verticals/clinic/records') ?>', {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify(data)
                    });
                }

                const json = await res.json().catch(() => ({}));
                if (res.ok && (json.success || json.record_id)) {
                    alert('Klinik dosya ve SOAP notu başarıyla kaydedildi!');
                    window.location.reload();
                } else {
                    alert('Hata: ' + (json.error || json.message || 'İşlem gerçekleştirilemedi. Lütfen alanları kontrol ediniz.'));
                }
            } catch (err) {
                alert('Ağ hatası: ' + err.message);
            } finally {
                if (btnSave) {
                    btnSave.disabled = false;
                    btnSave.innerHTML = originalBtnHtml;
                }
            }
        });
    }

    // 2. KLİNİK DOSYA DETAYI & CHARTING GÖRÜNTÜLE (#modal-view-soap)
    const modalViewSoapEl = document.getElementById('modal-view-soap');
    const modalViewSoap = modalViewSoapEl ? new bootstrap.Modal(modalViewSoapEl) : null;

    document.querySelectorAll('.btn-view-soap').forEach(btn => {
        btn.addEventListener('click', function() {
            const rawData = this.getAttribute('data-record');
            if (!rawData) return;

            try {
                const r = JSON.parse(rawData);

                const patientNameEl = document.getElementById('view-patient-name');
                const doctorNameEl = document.getElementById('view-doctor-name');
                const dateEl = document.getElementById('view-date');
                const apptRowEl = document.getElementById('view-appointment-row');
                const apptIdEl = document.getElementById('view-appointment-id');
                const typeBadgeEl = document.getElementById('view-type-badge');
                const confBadgeEl = document.getElementById('view-confidential-badge');

                const subjEl = document.getElementById('view-subjective');
                const objEl = document.getElementById('view-objective');
                const assessEl = document.getElementById('view-assessment');
                const planEl = document.getElementById('view-plan');

                if (patientNameEl) patientNameEl.textContent = r.patient_name || 'Danışan Belirtilmemiş';
                if (doctorNameEl) doctorNameEl.textContent = r.doctor_name || 'Hekim Belirtilmemiş';
                if (dateEl) dateEl.textContent = r.date || '-';

                if (apptRowEl && apptIdEl) {
                    if (r.id_appointments) {
                        apptIdEl.textContent = '#' + r.id_appointments;
                        apptRowEl.style.display = 'block';
                    } else {
                        apptRowEl.style.display = 'none';
                    }
                }

                if (typeBadgeEl) {
                    const typeSafe = escapeHtml((r.record_type || 'SOAP').toUpperCase());
                    typeBadgeEl.innerHTML = '<span class="badge bg-primary">' + typeSafe + '</span>';
                }

                if (confBadgeEl) {
                    if (r.is_confidential) {
                        confBadgeEl.innerHTML = '<span class="badge bg-danger"><i class="fas fa-lock me-1"></i>Gizli Dosya</span>';
                    } else {
                        confBadgeEl.innerHTML = '<span class="badge bg-light text-dark border">Normal</span>';
                    }
                }

                if (subjEl) subjEl.innerHTML = nl2br(r.subjective);
                if (objEl) objEl.innerHTML = nl2br(r.objective);
                if (assessEl) assessEl.innerHTML = nl2br(r.assessment);
                if (planEl) planEl.innerHTML = nl2br(r.plan);

                if (modalViewSoap) {
                    modalViewSoap.show();
                }
            } catch (err) {
                console.error('Kayıt çözümleme hatası:', err);
            }
        });
    });

    // 2.1 YAZDIR / PDF BUTONU
    const btnPrintSoap = document.getElementById('btn-print-soap');
    if (btnPrintSoap) {
        btnPrintSoap.addEventListener('click', function() {
            window.print();
        });
    }

    // 3. HASTA SİGORTA / SGK / POLİÇE MODALİ
    const modalInsuranceEl = document.getElementById('modal-patient-insurance');
    const modalInsurance = modalInsuranceEl ? new bootstrap.Modal(modalInsuranceEl) : null;
    const insuranceSelect = document.getElementById('insurance-patient-select');
    const formInsurance = document.getElementById('form-patient-insurance');

    async function loadPatientInsurance(patientId) {
        if (!patientId) return;
        const hint = document.getElementById('insurance-loading-hint');
        if (hint) hint.style.display = 'block';

        try {
            const res = await fetch('<?= site_url('api/v1/verticals/clinic/patient_history/') ?>' + encodeURIComponent(patientId), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (res.ok) {
                const data = await res.json();
                const ins = data.insurance;
                if (ins) {
                    const provEl = document.getElementById('insurance-provider-name');
                    const polEl = document.getElementById('insurance-policy-number');
                    const covEl = document.getElementById('insurance-coverage-ratio');
                    const validEl = document.getElementById('insurance-valid-until');
                    const notesEl = document.getElementById('insurance-notes');

                    if (provEl) provEl.value = ins.provider_name || '';
                    if (polEl) polEl.value = ins.policy_number || '';
                    if (covEl) covEl.value = ins.coverage_ratio !== undefined ? ins.coverage_ratio : 100;
                    if (validEl) validEl.value = ins.valid_until ? ins.valid_until.substring(0, 10) : '';
                    if (notesEl) notesEl.value = ins.notes || '';
                } else {
                    // Reset fields for new entry
                    const polEl = document.getElementById('insurance-policy-number');
                    const notesEl = document.getElementById('insurance-notes');
                    if (polEl) polEl.value = '';
                    if (notesEl) notesEl.value = '';
                }
            }
        } catch (err) {
            console.warn('Sigorta bilgisi yüklenemedi:', err);
        } finally {
            if (hint) hint.style.display = 'none';
        }
    }

    if (insuranceSelect) {
        insuranceSelect.addEventListener('change', function() {
            loadPatientInsurance(this.value);
        });
    }

    document.querySelectorAll('.btn-row-insurance').forEach(btn => {
        btn.addEventListener('click', function() {
            const patientId = this.getAttribute('data-patient-id');
            if (insuranceSelect && patientId) {
                insuranceSelect.value = patientId;
                loadPatientInsurance(patientId);
            }
            if (modalInsurance) {
                modalInsurance.show();
            }
        });
    });

    if (formInsurance) {
        formInsurance.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btnSave = document.getElementById('btn-save-insurance');
            const originalBtnHtml = btnSave ? btnSave.innerHTML : '';
            if (btnSave) {
                btnSave.disabled = true;
                btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Kaydediliyor...';
            }

            const fd = new FormData(this);
            const data = Object.fromEntries(fd.entries());
            const customerId = parseInt(data.id_users_customer, 10);
            if (!customerId) {
                alert('Lütfen bir hasta seçiniz.');
                if (btnSave) {
                    btnSave.disabled = false;
                    btnSave.innerHTML = originalBtnHtml;
                }
                return;
            }

            data.coverage_ratio = data.coverage_ratio ? parseInt(data.coverage_ratio, 10) : 100;

            const requestHeaders = {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };

            try {
                let res;
                try {
                    res = await fetch('<?= site_url('verticals/save_patient_insurance') ?>', {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify(data)
                    });
                    if (!res.ok && res.status === 404) {
                        throw new Error('Fallback endpoint required');
                    }
                } catch (fallbackErr) {
                    res = await fetch('<?= site_url('api/v1/verticals/clinic/insurance/') ?>' + encodeURIComponent(customerId), {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify(data)
                    });
                }

                const json = await res.json().catch(() => ({}));
                if (res.ok && (json.success || json.insurance_id)) {
                    alert('Hasta sigorta & SGK poliçe bilgisi başarıyla güncellendi!');
                    if (modalInsurance) {
                        modalInsurance.hide();
                    }
                } else {
                    alert('Hata: ' + (json.error || json.message || 'Sigorta kaydı güncellenemedi.'));
                }
            } catch (err) {
                alert('Ağ hatası: ' + err.message);
            } finally {
                if (btnSave) {
                    btnSave.disabled = false;
                    btnSave.innerHTML = originalBtnHtml;
                }
            }
        });
    }

    // 4. TELEHEALTH ENTEGRASYONU (Spinner + Error Handling + New Tab)
    document.querySelectorAll('.btn-telehealth').forEach(btn => {
        btn.addEventListener('click', async function() {
            const apptId = this.getAttribute('data-id');
            if (!apptId) return;

            const originalBtnHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Bağlanıyor...';

            try {
                const res = await fetch('<?= site_url('api/v1/verticals/clinic/telehealth/') ?>' + encodeURIComponent(apptId), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json().catch(() => ({}));

                if (res.ok && json.telehealth_url) {
                    window.open(json.telehealth_url, '_blank');
                } else {
                    const errMsg = json.error || json.message || 'Görüşme linki oluşturulamadı veya randevu bulunamadı.';
                    alert('Telehealth Hatası: ' + errMsg);
                }
            } catch (err) {
                alert('Telehealth bağlantı hatası: ' + err.message);
            } finally {
                this.disabled = false;
                this.innerHTML = originalBtnHtml;
            }
        });
    });
    </script>
</body>
</html>
