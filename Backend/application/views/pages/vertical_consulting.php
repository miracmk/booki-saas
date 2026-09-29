<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h1 class="h3 fw-bold mb-1">
                <i class="fas fa-briefcase text-info me-2"></i><?= html_escape($page_title ?? 'Danışmanlık & Zaman Takibi (Billable Hours)') ?>
            </h1>
            <p class="text-muted mb-0">Müşteri danışmanlık projeleri, saatlik efor kayıtları ve faturalandırılabilir çalışma saatleri.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <button type="button" class="btn btn-info text-white px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-add-time-log">
                <i class="fas fa-stopwatch me-1"></i> Yeni Zaman Kaydı Ekle
            </button>
        </div>
    </div>

    <!-- Metric Cards -->
    <?php
    $totalMinutes = 0;
    $totalFee = 0;
    foreach ($time_logs ?? [] as $tl) {
        $totalMinutes += (int)($tl['duration_minutes'] ?? 0);
        $totalFee += (float)($tl['total_fee'] ?? 0);
    }
    $totalHours = round($totalMinutes / 60, 1);
    ?>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 text-info me-3">
                        <i class="fas fa-clock fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Toplam Efor</div>
                        <h4 class="fw-bold mb-0 text-dark"><?= $totalHours ?> Saat</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success me-3">
                        <i class="fas fa-coins fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Faturalandırılabilir Tutar</div>
                        <h4 class="fw-bold mb-0 text-success"><?= number_format($totalFee, 2, ',', '.') ?> ₺</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary me-3">
                        <i class="fas fa-users fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Kayıtlı Danışanlar</div>
                        <h4 class="fw-bold mb-0 text-dark"><?= count($clients ?? []) ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 text-warning me-3">
                        <i class="fas fa-user-tie fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Danışman / Uzmanlar</div>
                        <h4 class="fw-bold mb-0 text-dark"><?= count($consultants ?? []) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Timesheet Table Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-list text-info me-2"></i>Danışmanlık Efor ve Zaman Kayıtları
            </h5>
            <span class="badge bg-light text-dark border"><?= count($time_logs ?? []) ?> Toplam Kayıt</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="table-time-logs">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Tarih</th>
                        <th>Danışan / Firma</th>
                        <th>Danışman</th>
                        <th>Proje / Görev</th>
                        <th>Süre</th>
                        <th>Saatlik Ücret</th>
                        <th>Toplam Tutar</th>
                        <th>Faturalanabilir</th>
                        <th>Durum</th>
                        <th class="text-end pe-3">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($time_logs)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="fas fa-history fa-2x mb-2 d-block text-secondary"></i>
                                Henüz kayıtlı danışmanlık süresi bulunmuyor. Yeni zaman kaydı ekleyerek başlayabilirsiniz.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($time_logs as $log): ?>
                            <tr>
                                <td class="ps-3 small text-muted">
                                    <?= date('d.m.Y H:i', strtotime($log['created_at'])) ?>
                                </td>
                                <td>
                                    <strong><?= html_escape($log['client_name'] ?? 'Müşteri #' . $log['id_users_client']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="fas fa-user me-1"></i><?= html_escape($log['consultant_name'] ?? 'Danışman #' . $log['id_users_consultant']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= html_escape($log['project_name']) ?></div>
                                    <div class="small text-muted text-truncate" style="max-width: 250px;">
                                        <?= html_escape($log['work_description'] ?: '-') ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= (int)$log['duration_minutes'] ?> dk (<?= round($log['duration_minutes'] / 60, 1) ?> sa)
                                    </span>
                                </td>
                                <td><?= number_format((float)$log['hourly_rate'], 2, ',', '.') ?> ₺</td>
                                <td class="fw-bold text-success">
                                    <?= number_format((float)$log['total_fee'], 2, ',', '.') ?> ₺
                                </td>
                                <td>
                                    <?php if (!empty($log['is_billable'])): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Evet</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Hayır</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info text-white">
                                        <?= html_escape(strtoupper($log['status'] ?? 'LOGGED')) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-view-log"
                                        data-project="<?= html_escape($log['project_name']) ?>"
                                        data-client="<?= html_escape($log['client_name'] ?? '') ?>"
                                        data-consultant="<?= html_escape($log['consultant_name'] ?? '') ?>"
                                        data-duration="<?= (int)$log['duration_minutes'] ?>"
                                        data-fee="<?= number_format((float)$log['total_fee'], 2, ',', '.') ?>"
                                        data-description="<?= html_escape($log['work_description'] ?? '') ?>">
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

<!-- MODAL: YENİ ZAMAN KAYDI EKLE -->
<div class="modal fade" id="modal-add-time-log" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-stopwatch me-2"></i>Yeni Zaman Kaydı (Timesheet)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form id="form-add-time-log">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Danışan / Firma <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_users_client" required>
                                <option value="">Müşteri Seçiniz...</option>
                                <?php foreach ($clients ?? [] as $cl): ?>
                                    <option value="<?= (int)$cl['id'] ?>">
                                        <?= html_escape($cl['first_name'] . ' ' . $cl['last_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Danışman / Uzman <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_users_consultant" required>
                                <option value="">Danışman Seçiniz...</option>
                                <?php foreach ($consultants ?? [] as $cn): ?>
                                    <option value="<?= (int)$cn['id'] ?>">
                                        <?= html_escape($cn['first_name'] . ' ' . $cn['last_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Proje / Hizmet Başlığı <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="project_name" placeholder="Örn: Finansal Dönüşüm Danışmanlığı" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Süre (Dakika) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="input-duration" name="duration_minutes" min="1" max="1440" value="60" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Saatlik Ücret (₺)</label>
                            <input type="number" step="0.01" class="form-control" id="input-hourly-rate" name="hourly_rate" value="1500.00">
                        </div>
                        <div class="col-12">
                            <div class="p-3 bg-light rounded d-flex justify-content-between align-items-center">
                                <span class="text-muted fw-semibold">Hesaplanan Toplam Tutar:</span>
                                <span class="h5 fw-bold text-success mb-0" id="display-total-fee">1.500,00 ₺</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Yapılan Çalışma / Notlar <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="work_description" rows="3" placeholder="Görüşme tutanağı, teslim edilen analizler, alınan kararlar..." required></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="switch-billable" name="is_billable" value="1" checked>
                                <label class="form-check-label fw-semibold" for="switch-billable">Faturalandırılabilir Çalışma (Müşteriye yansıtılacak)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-info text-white" id="btn-submit-time-log">
                        <i class="fas fa-save me-1"></i> Eforu Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: ZAMAN DETAYI -->
<div class="modal fade" id="modal-view-log" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="view-log-project">Efor Detayı</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <span class="text-muted small">Danışan:</span>
                    <strong class="d-block" id="view-log-client">-</strong>
                </div>
                <div class="mb-3">
                    <span class="text-muted small">Danışman:</span>
                    <strong class="d-block" id="view-log-consultant">-</strong>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <span class="text-muted small">Süre:</span>
                        <div class="fw-bold" id="view-log-duration">-</div>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small">Toplam Tutar:</span>
                        <div class="fw-bold text-success" id="view-log-fee">-</div>
                    </div>
                </div>
                <div class="mb-2">
                    <span class="text-muted small">Yapılan İş Açıklaması:</span>
                    <div class="p-3 bg-light rounded mt-1" id="view-log-description" style="white-space: pre-wrap;">-</div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dynamic Fee Calculation
    var inputDuration = document.getElementById('input-duration');
    var inputHourlyRate = document.getElementById('input-hourly-rate');
    var displayTotalFee = document.getElementById('display-total-fee');

    function calculateTotal() {
        var duration = parseFloat(inputDuration.value) || 0;
        var rate = parseFloat(inputHourlyRate.value) || 0;
        var total = (duration / 60) * rate;
        displayTotalFee.textContent = total.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺';
    }

    if (inputDuration && inputHourlyRate) {
        inputDuration.addEventListener('input', calculateTotal);
        inputHourlyRate.addEventListener('input', calculateTotal);
    }

    // Submit Time Log
    var formTimeLog = document.getElementById('form-add-time-log');
    if (formTimeLog) {
        formTimeLog.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = document.getElementById('btn-submit-time-log');
            var originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Kaydediliyor...';

            var fd = new FormData(formTimeLog);
            var payload = {};
            fd.forEach(function(val, key) {
                payload[key] = val;
            });
            payload.is_billable = document.getElementById('switch-billable').checked ? 1 : 0;

            fetch('<?= site_url("verticals/save_consulting_time_log") ?>', {
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
                    alert(data.message || 'Zaman kaydı başarıyla kaydedildi.');
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

    // View Log Details
    document.querySelectorAll('.btn-view-log').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('view-log-project').textContent = this.getAttribute('data-project') || '-';
            document.getElementById('view-log-client').textContent = this.getAttribute('data-client') || '-';
            document.getElementById('view-log-consultant').textContent = this.getAttribute('data-consultant') || '-';
            document.getElementById('view-log-duration').textContent = this.getAttribute('data-duration') + ' dakika';
            document.getElementById('view-log-fee').textContent = this.getAttribute('data-fee') + ' ₺';
            document.getElementById('view-log-description').textContent = this.getAttribute('data-description') || '-';

            var modal = new bootstrap.Modal(document.getElementById('modal-view-log'));
            modal.show();
        });
    });
});
</script>
