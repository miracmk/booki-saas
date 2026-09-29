<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @var array $sessions
 * @var array $students
 * @var array $attendance
 * @var array $evaluations
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
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1"><i class="fas fa-graduation-cap text-success me-2"></i>Eğitim, Atölye & Kurs Yönetimi</h1>
                <p class="text-muted small mb-0">Sınıf ve atölye takvimi, canlı öğrenci yoklaması, otomatik seans düşümü ve gelişim karnesi.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modal-student-grade">
                    <i class="fas fa-star me-1"></i> Not / Gelişim Girişi
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-take-attendance">
                    <i class="fas fa-clipboard-check me-1"></i> Hızlı Yoklama Al
                </button>
            </div>
        </div>

        <!-- STATS CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-4 border-primary">
                    <small class="text-muted fw-semibold">Toplam Ders / Oturum</small>
                    <h3 class="fw-bold mb-0 text-primary"><?= count($sessions) ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-4 border-success">
                    <small class="text-muted fw-semibold">Kayıtlı Öğrenci Sayısı</small>
                    <h3 class="fw-bold mb-0 text-success"><?= count($students) ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-4 border-info">
                    <small class="text-muted fw-semibold">Alınan Yoklama Kaydı</small>
                    <h3 class="fw-bold mb-0 text-info"><?= count($attendance) ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 border-start border-4 border-warning">
                    <small class="text-muted fw-semibold">Öğrenci Değerlendirmeleri</small>
                    <h3 class="fw-bold mb-0 text-warning"><?= count($evaluations) ?></h3>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- LEFT: SESSIONS & LIVE ATTENDANCE -->
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="fas fa-chalkboard-teacher text-primary me-2"></i>Aktif Ders ve Atölye Oturumları</h5>
                        <span class="badge bg-primary"><?= count($sessions) ?> Oturum</span>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (empty($sessions)): ?>
                            <div class="text-center py-4 text-muted">Planlanmış aktif ders veya atölye oturumu bulunmuyor.</div>
                        <?php else: ?>
                            <?php foreach ($sessions as $s): ?>
                                <div class="list-group-item p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="fw-bold mb-1"><?= e($s['service_name'] ?: 'Ders / Atölye') ?></h6>
                                            <span class="badge bg-light text-dark border me-1"><i class="fas fa-user-tie me-1"></i><?= e($s['provider_name'] ?: 'Eğitmen') ?></span>
                                            <span class="badge bg-secondary me-1"><i class="fas fa-door-open me-1"></i><?= e($s['station_name'] ?: 'Sınıf / Atölye') ?></span>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-info text-dark"><?= date('d.m.Y H:i', strtotime($s['start_datetime'])) ?></span>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted"><i class="fas fa-clock me-1"></i><?= round((strtotime($s['end_datetime']) - strtotime($s['start_datetime'])) / 60) ?> dk</small>
                                        <button class="btn btn-sm btn-outline-primary btn-open-session-attendance" data-session-id="<?= $s['id'] ?>" data-session-title="<?= e($s['service_name']) ?>">
                                            <i class="fas fa-check-double me-1"></i> Yoklama Gir
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RECENT ATTENDANCE TABLE -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="fas fa-history text-secondary me-2"></i>Son Yoklama Hareketleri</h5>
                        <span class="badge bg-secondary"><?= count($attendance) ?> Kayıt</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Öğrenci</th>
                                    <th>Ders</th>
                                    <th>Durum</th>
                                    <th>Not</th>
                                    <th>Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($attendance)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-3 text-muted">Henüz yoklama hareketi kaydedilmedi.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($attendance as $att): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= e($att['student_name']) ?></td>
                                            <td><?= e($att['service_name'] ?: ('Oturum #' . $att['id_appointments'])) ?></td>
                                            <td>
                                                <?php if ($att['status'] === 'present'): ?>
                                                    <span class="badge bg-success">Katıldı</span>
                                                <?php elseif ($att['status'] === 'absent'): ?>
                                                    <span class="badge bg-danger">Gelmedi</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">İzinli</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-muted"><?= e($att['notes'] ?: '-') ?></td>
                                            <td class="text-muted"><?= date('d.m.Y H:i', strtotime($att['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- RIGHT: STUDENT PROGRESS & EVALUATIONS -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="fas fa-medal text-warning me-2"></i>Öğrenci Gelişim & Karne</h5>
                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modal-student-grade">
                            <i class="fas fa-plus me-1"></i> Yeni Değerlendirme
                        </button>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (empty($evaluations)): ?>
                            <div class="text-center py-4 text-muted">Kayıtlı öğrenci değerlendirmesi bulunamadı.</div>
                        <?php else: ?>
                            <?php foreach ($evaluations as $ev): ?>
                                <div class="list-group-item p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div>
                                            <h6 class="fw-bold mb-0"><?= e($ev['student_name']) ?></h6>
                                            <small class="text-primary fw-semibold"><?= e($ev['subject']) ?></small>
                                        </div>
                                        <div>
                                            <span class="badge bg-warning text-dark fs-6"><?= e($ev['grade_score']) ?> Puan</span>
                                        </div>
                                    </div>
                                    <p class="small text-muted mb-1"><?= e($ev['feedback_notes'] ?: 'Geri bildirim belirtilmedi.') ?></p>
                                    <small class="text-muted d-block text-end"><?= date('d.m.Y H:i', strtotime($ev['created_at'])) ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: YOKLAMA AL -->
    <div class="modal fade" id="modal-take-attendance" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-clipboard-check text-primary me-2"></i>Canlı Sınıf Yoklaması Al</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-save-attendance">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Ders / Atölye Oturumu</label>
                            <select name="session_id" id="attendance-session-select" class="form-select" required>
                                <option value="">Ders Seçiniz...</option>
                                <?php foreach ($sessions as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= e($s['service_name']) ?> - <?= e($s['provider_name']) ?> (<?= date('d.m.Y H:i', strtotime($s['start_datetime'])) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-2 fw-semibold">Öğrenci Katılım Durumları:</div>
                        <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Öğrenci</th>
                                        <th class="text-center">Katıldı</th>
                                        <th class="text-center">Gelmedi</th>
                                        <th class="text-center">İzinli</th>
                                        <th>Not</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $st): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= e($st['first_name'] . ' ' . $st['last_name']) ?></td>
                                            <td class="text-center">
                                                <input type="radio" class="btn-check" name="status_<?= $st['id'] ?>" id="st_<?= $st['id'] ?>_p" value="present" checked autocomplete="off">
                                                <label class="btn btn-outline-success btn-sm px-2 py-0" for="st_<?= $st['id'] ?>_p"><i class="fas fa-check"></i></label>
                                            </td>
                                            <td class="text-center">
                                                <input type="radio" class="btn-check" name="status_<?= $st['id'] ?>" id="st_<?= $st['id'] ?>_a" value="absent" autocomplete="off">
                                                <label class="btn btn-outline-danger btn-sm px-2 py-0" for="st_<?= $st['id'] ?>_a"><i class="fas fa-times"></i></label>
                                            </td>
                                            <td class="text-center">
                                                <input type="radio" class="btn-check" name="status_<?= $st['id'] ?>" id="st_<?= $st['id'] ?>_e" value="excused" autocomplete="off">
                                                <label class="btn btn-outline-warning btn-sm px-2 py-0" for="st_<?= $st['id'] ?>_e"><i class="fas fa-clock"></i></label>
                                            </td>
                                            <td>
                                                <input type="text" name="notes_<?= $st['id'] ?>" class="form-control form-control-sm" placeholder="Opsiyonel not...">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" id="btn-submit-attendance" class="btn btn-primary"><i class="fas fa-save me-1"></i> Yoklamayı Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: ÖĞRENCİ DEĞERLENDİRME -->
    <div class="modal fade" id="modal-student-grade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-star text-warning me-2"></i>Öğrenci Not / Gelişim Girişi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-save-grade">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Öğrenci</label>
                            <select name="student_id" class="form-select" required>
                                <option value="">Öğrenci Seçiniz...</option>
                                <?php foreach ($students as $st): ?>
                                    <option value="<?= $st['id'] ?>"><?= e($st['first_name'] . ' ' . $st['last_name']) ?> (<?= e($st['phone_number'] ?: '') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Ders / Atölye</label>
                            <select name="session_id" class="form-select">
                                <option value="">Bağımsız / Genel Değerlendirme</option>
                                <?php foreach ($sessions as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= e($s['service_name']) ?> (<?= date('d.m.Y H:i', strtotime($s['start_datetime'])) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label fw-semibold">Ders Konusu / Ödev Başlığı</label>
                                <input type="text" name="subject" class="form-control" placeholder="Örn: 3. Hafta Armoni & Ritim Egzersizi" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Puan / Not (0-100)</label>
                                <input type="number" name="grade_score" class="form-control" min="0" max="100" placeholder="95" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Eğitmen Geri Bildirimi & Notlar</label>
                            <textarea name="feedback_notes" rows="3" class="form-control" placeholder="Öğrencinin performansı, geliştiği alanlar ve ev çalışması..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" id="btn-submit-grade" class="btn btn-success"><i class="fas fa-check me-1"></i> Değerlendirmeyi Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Quick open attendance for specific session
    document.querySelectorAll('.btn-open-session-attendance').forEach(btn => {
        btn.addEventListener('click', function() {
            const sid = this.getAttribute('data-session-id');
            const selectEl = document.getElementById('attendance-session-select');
            if (selectEl) {
                selectEl.value = sid;
            }
            const modalEl = document.getElementById('modal-take-attendance');
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
        });
    });

    // Form save attendance
    document.getElementById('form-save-attendance').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-submit-attendance');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Kaydediliyor...';

        const sessionId = document.getElementById('attendance-session-select').value;
        const records = [];

        <?php foreach ($students as $st): ?>
        {
            const stId = <?= (int) $st['id'] ?>;
            const statusEl = document.querySelector('input[name="status_' + stId + '"]:checked');
            const noteEl = document.querySelector('input[name="notes_' + stId + '"]');
            if (statusEl) {
                records.push({
                    student_id: stId,
                    status: statusEl.value,
                    notes: noteEl ? noteEl.value : ''
                });
            }
        }
        <?php endforeach; ?>

        try {
            const res = await fetch('<?= site_url('verticals/save_attendance') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    records: records
                })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                alert(data.message || 'Yoklama başarıyla kaydedildi!');
                window.location.reload();
            } else {
                alert('Hata: ' + (data.message || data.error || 'İşlem başarısız.'));
            }
        } catch (err) {
            alert('Ağ hatası: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> Yoklamayı Kaydet';
        }
    });

    // Form save grade
    document.getElementById('form-save-grade').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-submit-grade');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Kaydediliyor...';

        const fd = new FormData(this);
        const payload = Object.fromEntries(fd.entries());

        try {
            const res = await fetch('<?= site_url('verticals/save_student_grade') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (res.ok && data.success) {
                alert(data.message || 'Değerlendirme kaydedildi!');
                window.location.reload();
            } else {
                alert('Hata: ' + (data.message || data.error || 'İşlem başarısız.'));
            }
        } catch (err) {
            alert('Ağ hatası: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check me-1"></i> Değerlendirmeyi Kaydet';
        }
    });
    </script>
</body>
</html>
