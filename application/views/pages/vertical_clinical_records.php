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
</head>
<body class="backend-body">
    <?php $this->load->view('components/backend_header'); ?>

    <div class="container-fluid py-4 px-md-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1"><i class="fas fa-stethoscope text-primary me-2"></i>Klinik Kayıtları, SOAP Notları & Telehealth</h1>
                <p class="text-muted small mb-0">EHR klinik charting (Subjective, Objective, Assessment, Plan), anamnez formları ve şifreli online seans odaları.</p>
            </div>
            <div class="d-flex gap-2">
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
                            <label class="form-label fw-bold text-danger">A — Assessment (Teşhis, Klinik Değerlendirme & Tanı)</label>
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
