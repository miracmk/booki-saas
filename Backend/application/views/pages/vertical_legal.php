<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h1 class="h3 fw-bold mb-1">
                <i class="fas fa-gavel text-primary me-2"></i><?= html_escape($page_title ?? 'Hukuk Bürosu & Dava Yönetimi') ?>
            </h1>
            <p class="text-muted mb-0">Müvekkil dava dosyaları, esas numaraları, duruşma takvimi ve adli süreç takibi.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <button type="button" class="btn btn-primary px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-add-case">
                <i class="fas fa-plus me-1"></i> Yeni Dava / Dosya Aç
            </button>
        </div>
    </div>

    <!-- Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary me-3">
                        <i class="fas fa-folder-open fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Aktif Dava Dosyaları</div>
                        <h4 class="fw-bold mb-0 text-dark">
                            <?= count(array_filter($cases ?? [], fn($c) => ($c['status'] ?? '') === 'open')) ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 text-warning me-3">
                        <i class="fas fa-calendar-alt fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Yaklaşan Duruşmalar</div>
                        <h4 class="fw-bold mb-0 text-dark">
                            <?= count(array_filter($cases ?? [], fn($c) => !empty($c['hearing_datetime']) && strtotime($c['hearing_datetime']) >= time())) ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success me-3">
                        <i class="fas fa-users fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Kayıtlı Müvekkiller</div>
                        <h4 class="fw-bold mb-0 text-dark"><?= count($clients ?? []) ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-secondary bg-opacity-10 p-3 text-secondary me-3">
                        <i class="fas fa-archive fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Karara Bağlanan / Kapalı</div>
                        <h4 class="fw-bold mb-0 text-dark">
                            <?= count(array_filter($cases ?? [], fn($c) => in_array($c['status'] ?? '', ['closed', 'appeal']))) ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cases Table Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-balance-scale text-primary me-2"></i>Dava Dosyaları Listesi
            </h5>
            <span class="badge bg-light text-dark border"><?= count($cases ?? []) ?> Toplam Dosya</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="table-cases">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Esas / Dosya No</th>
                        <th>Mahkeme</th>
                        <th>Müvekkil</th>
                        <th>Karşı Taraf</th>
                        <th>Dava Türü</th>
                        <th>Duruşma Tarihi</th>
                        <th>Durum</th>
                        <th class="text-end pe-3">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cases)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-folder-open fa-2x mb-2 d-block text-secondary"></i>
                                Henüz kayıtlı dava dosyası bulunmuyor. Yeni dava ekleyerek başlayabilirsiniz.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cases as $case): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-primary">
                                    <?= html_escape($case['case_number']) ?>
                                </td>
                                <td><?= html_escape($case['court_name']) ?></td>
                                <td>
                                    <strong><?= html_escape($case['client_name'] ?? 'Müvekkil #' . $case['id_users_client']) ?></strong>
                                    <?php if (!empty($case['client_phone'])): ?>
                                        <div class="small text-muted"><i class="fas fa-phone-alt me-1"></i><?= html_escape($case['client_phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= html_escape($case['opposing_party'] ?: '-') ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= html_escape(ucfirst($case['case_type'] ?? 'Hukuk')) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($case['hearing_datetime'])): ?>
                                        <?php $ts = strtotime($case['hearing_datetime']); ?>
                                        <span class="badge <?= $ts < time() ? 'bg-secondary' : 'bg-warning text-dark' ?>">
                                            <i class="far fa-clock me-1"></i><?= date('d.m.Y H:i', $ts) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Belirlenmedi</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusBadges = [
                                        'open' => 'bg-success',
                                        'closed' => 'bg-secondary',
                                        'appeal' => 'bg-info',
                                    ];
                                    $badge = $statusBadges[$case['status']] ?? 'bg-primary';
                                    ?>
                                    <span class="badge <?= $badge ?>">
                                        <?= html_escape(strtoupper($case['status'])) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-view-case"
                                        data-id="<?= (int)$case['id'] ?>"
                                        data-case-number="<?= html_escape($case['case_number']) ?>"
                                        data-court="<?= html_escape($case['court_name']) ?>"
                                        data-client="<?= html_escape($case['client_name'] ?? '') ?>"
                                        data-opposing="<?= html_escape($case['opposing_party'] ?? '') ?>"
                                        data-type="<?= html_escape($case['case_type'] ?? '') ?>"
                                        data-subject="<?= html_escape($case['case_subject'] ?? '') ?>"
                                        data-hearing="<?= html_escape($case['hearing_datetime'] ?? '') ?>"
                                        data-status="<?= html_escape($case['status'] ?? '') ?>">
                                        <i class="fas fa-eye"></i> Detay
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

<!-- MODAL: YENİ DAVA EKLE -->
<div class="modal fade" id="modal-add-case" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus me-2"></i>Yeni Dava / Dosya Kaydı</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form id="form-add-case">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Müvekkil <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_users_client" required>
                                <option value="">Müvekkil Seçiniz...</option>
                                <?php foreach ($clients ?? [] as $cl): ?>
                                    <option value="<?= (int)$cl['id'] ?>">
                                        <?= html_escape($cl['first_name'] . ' ' . $cl['last_name']) ?> (<?= html_escape($cl['phone_number']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Esas / Dosya No <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="case_number" placeholder="Örn: 2026/142 Esas" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mahkeme / Merci <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="court_name" placeholder="Örn: İstanbul 4. Asliye Ticaret Mahkemesi" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Karşı Taraf (Davalı / Davacı)</label>
                            <input type="text" class="form-control" name="opposing_party" placeholder="Örn: ABC Lojistik A.Ş.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Dava Türü</label>
                            <select class="form-select" name="case_type">
                                <option value="civil">Hukuk / Tazminat</option>
                                <option value="commercial">Ticaret Hukuku</option>
                                <option value="labor">İş Hukuku</option>
                                <option value="execution">İcra & İflas</option>
                                <option value="family">Aile & Boşanma</option>
                                <option value="criminal">Ceza Hukuku</option>
                                <option value="administrative">İdare & Vergi</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">İlk Duruşma Tarihi & Saati</label>
                            <input type="datetime-local" class="form-control" name="hearing_datetime">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Dava Konusu & Talep Özeti <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="case_subject" rows="3" placeholder="Davanın konusu, harca esas değer ve talepler..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-case">
                        <i class="fas fa-save me-1"></i> Dosyayı Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: DAVA DETAYI -->
<div class="modal fade" id="modal-view-case" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="view-case-title">Dava Dosyası Detayı</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="text-muted small">Mahkeme:</div>
                        <div class="fw-bold fs-6" id="view-court">-</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Müvekkil:</div>
                        <div class="fw-bold fs-6 text-primary" id="view-client">-</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Karşı Taraf:</div>
                        <div class="fw-bold" id="view-opposing">-</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Dava Türü:</div>
                        <div class="fw-bold" id="view-type">-</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Duruşma Tarihi:</div>
                        <div class="fw-bold text-danger" id="view-hearing">-</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Durum:</div>
                        <div class="fw-bold" id="view-status">-</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Dava Konusu ve Notlar:</div>
                        <div class="p-3 bg-light rounded mt-1" id="view-subject" style="white-space: pre-wrap;">-</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Yazdır
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function escapeHtml(text) {
        if (!text) return '';
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // New Case Form Submit
    var formAddCase = document.getElementById('form-add-case');
    if (formAddCase) {
        formAddCase.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = document.getElementById('btn-submit-case');
            var originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Kaydediliyor...';

            var fd = new FormData(formAddCase);
            var payload = {};
            fd.forEach(function(value, key) {
                payload[key] = value;
            });

            fetch('<?= site_url("verticals/save_legal_case") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    alert(data.message || 'Dava dosyası başarıyla kaydedildi.');
                    window.location.reload();
                } else {
                    alert(data.message || 'Kayıt sırasında bir hata oluştu.');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(function(err) {
                alert('Bağlantı hatası: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }

    // View Case Details Modal
    document.querySelectorAll('.btn-view-case').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var caseNumber = this.getAttribute('data-case-number') || '';
            document.getElementById('view-case-title').textContent = 'Dosya No: ' + caseNumber;
            document.getElementById('view-court').textContent = this.getAttribute('data-court') || '-';
            document.getElementById('view-client').textContent = this.getAttribute('data-client') || '-';
            document.getElementById('view-opposing').textContent = this.getAttribute('data-opposing') || '-';
            document.getElementById('view-type').textContent = this.getAttribute('data-type') || '-';
            document.getElementById('view-hearing').textContent = this.getAttribute('data-hearing') || 'Belirlenmedi';
            document.getElementById('view-status').textContent = (this.getAttribute('data-status') || '').toUpperCase();
            document.getElementById('view-subject').textContent = this.getAttribute('data-subject') || '-';

            var modal = new bootstrap.Modal(document.getElementById('modal-view-case'));
            modal.show();
        });
    });
});
</script>
