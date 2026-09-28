<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @var array $projects
 * @var array $timesheets
 * @var array $clients
 * @var array $consultants
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
                <h1 class="h3 fw-bold mb-1">
                    <i class="fas fa-project-diagram text-primary me-2"></i>Danışmanlık & Stratejik Proje Yönetimi
                </h1>
                <p class="text-muted small mb-0">Proje evreleri, teslimat kilometre taşları (milestones), faturalandırılabilir efor dökümü ve müşteri onay portalları.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-add-timesheet">
                    <i class="fas fa-clock me-1"></i> Efor / Saat Kaydet
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-project">
                    <i class="fas fa-folder-plus me-1"></i> Yeni Proje Başlat
                </button>
            </div>
        </div>

        <!-- STATS -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <span class="text-muted small d-block mb-1">Aktif Danışmanlık Projeleri</span>
                        <h3 class="fw-bold mb-0 text-dark"><?= count($projects) ?></h3>
                        <small class="text-primary"><i class="fas fa-tasks me-1"></i>Yönetilen Portföy</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <span class="text-muted small d-block mb-1">Girilen Efor Kaydı</span>
                        <h3 class="fw-bold mb-0 text-success"><?= count($timesheets) ?></h3>
                        <small class="text-muted"><i class="fas fa-hourglass-half me-1"></i>Danışman Çalışma Saatleri</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <span class="text-muted small d-block mb-1">Danışan Müşteriler</span>
                        <h3 class="fw-bold mb-0 text-info"><?= count($clients) ?></h3>
                        <small class="text-info"><i class="fas fa-building me-1"></i>Şirket ve Kurumlar</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABS: PROJELER / EFOR & TIMESHEETS -->
        <ul class="nav nav-tabs border-bottom mb-3" role="tablist">
            <li class="nav-item">
                <a class="nav-link active fw-bold" data-bs-toggle="tab" href="#tab-projects">
                    <i class="fas fa-briefcase me-2"></i>Projeler & Fazlar (<?= count($projects) ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-bold" data-bs-toggle="tab" href="#tab-timesheets">
                    <i class="fas fa-calendar-check me-2"></i>Timesheets & Efor Dökümleri (<?= count($timesheets) ?>)
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- TAB 1: PROJELER -->
            <div class="tab-pane fade show active" id="tab-projects">
                <div class="row g-4">
                    <?php if (empty($projects)): ?>
                        <div class="col-12 text-center py-5 text-muted">
                            <i class="fas fa-project-diagram fs-1 d-block mb-3 text-secondary"></i>
                            Kayıtlı danışmanlık projesi bulunmuyor. Yeni proje başlatmak için yukarıdaki butonu kullanın.
                        </div>
                    <?php else: ?>
                        <?php foreach ($projects as $p): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge bg-light text-primary border font-monospace"><?= e($p['project_code']) ?></span>
                                            <span class="badge bg-success text-uppercase"><?= e($p['status']) ?></span>
                                        </div>
                                        <h5 class="fw-bold mb-1"><?= e($p['title']) ?></h5>
                                        <p class="text-muted small mb-3"><?= e(mb_strimwidth($p['scope'] ?? 'Kapsam belirtilmedi', 0, 90, '...')) ?></p>

                                        <div class="small mb-2">
                                            <strong>Danışan:</strong> <?= e(trim($p['client_first_name'] . ' ' . $p['client_last_name'])) ?>
                                        </div>
                                        <div class="small mb-3">
                                            <strong>Lider Danışman:</strong> <?= e(trim($p['consultant_first_name'] . ' ' . $p['consultant_last_name'])) ?>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                            <div>
                                                <small class="text-muted d-block">Bütçe</small>
                                                <span class="fw-bold text-dark">₺<?= number_format((float) $p['total_budget'], 2) ?></span>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary" onclick="addMilestone(<?= $p['id'] ?>, '<?= e($p['title']) ?>')">
                                                <i class="fas fa-flag-checkered me-1"></i> Kilometre Taşı Ekle
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TAB 2: TIMESHEETS -->
            <div class="tab-pane fade" id="tab-timesheets">
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tarih</th>
                                    <th>Proje Kodu & Başlık</th>
                                    <th>Danışman</th>
                                    <th>Açıklama / İş Kalemi</th>
                                    <th>Harcanan Süre</th>
                                    <th>Faturalandırılabilir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($timesheets)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Kayıtlı efor/zaman dökümü bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($timesheets as $t): ?>
                                        <tr>
                                            <td><?= date('d.m.Y', strtotime($t['log_date'])) ?></td>
                                            <td>
                                                <span class="badge bg-light text-dark border font-monospace"><?= e($t['project_code']) ?></span>
                                                <span class="fw-semibold"><?= e($t['project_title']) ?></span>
                                            </td>
                                            <td><?= e(trim($t['consultant_first_name'] . ' ' . $t['consultant_last_name'])) ?></td>
                                            <td><?= e($t['work_summary']) ?></td>
                                            <td class="fw-bold"><?= $t['hours_spent'] ?> saat</td>
                                            <td>
                                                <?= $t['is_billable'] ? '<span class="badge bg-success">Evet (Billable)</span>' : '<span class="badge bg-secondary">İç Toplantı (Non-Billable)</span>' ?>
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

    <!-- MODAL: YENİ PROJE -->
    <div class="modal fade" id="modal-add-project" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-folder-plus text-primary me-2"></i>Yeni Danışmanlık Projesi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-add-project" onsubmit="submitProject(event)">
                    <div class="modal-body row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Proje Adı *</label>
                            <input type="text" name="title" class="form-control" placeholder="Örn: 2026 Q3 Dijitalleşme & ERP Dönüşüm Stratejisi" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Proje Kodu</label>
                            <input type="text" name="project_code" class="form-control" placeholder="Otomatik Üretilir">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Danışan Müşteri / Şirket *</label>
                            <select name="id_users_client" class="form-select" required>
                                <option value="">Müşteri Seçiniz...</option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e(trim($c['first_name'] . ' ' . $c['last_name'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Lider Danışman *</label>
                            <select name="id_users_lead_consultant" class="form-select" required>
                                <option value="">Danışman Seçiniz...</option>
                                <?php foreach ($consultants as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= e(trim($u['first_name'] . ' ' . $u['last_name'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Toplam Bütçe (TL)</label>
                            <input type="number" step="0.01" name="total_budget" class="form-control" placeholder="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Başlangıç Tarihi</label>
                            <input type="date" name="start_date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Hedef Bitiş Tarihi</label>
                            <input type="date" name="target_end_date" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Proje Kapsamı & Beklenen Çıktılar</label>
                            <textarea name="scope" class="form-control" rows="3" placeholder="Fazlar, teslim edilecek raporlar, toplantı periyotları..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Projeyi Başlat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: YENİ EFOR GİRİŞİ -->
    <div class="modal fade" id="modal-add-timesheet" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-clock text-primary me-2"></i>Efor & Zaman Kaydı Gir</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-add-timesheet" onsubmit="submitTimesheet(event)">
                    <div class="modal-body row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">İlgili Proje *</label>
                            <select name="id_projects" class="form-select" required>
                                <option value="">Proje Seçiniz...</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= e($p['project_code']) ?> - <?= e($p['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Çalışma Tarihi *</label>
                            <input type="date" name="log_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Süre (Saat) *</label>
                            <input type="number" step="0.25" name="hours_spent" class="form-control" value="1.0" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_billable" value="1" id="isBillableSwitch" checked>
                                <label class="form-check-label small fw-bold" for="isBillableSwitch">Müşteriye Faturalandırılabilir (Billable)</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Yapılan Çalışmanın Özeti *</label>
                            <textarea name="work_summary" class="form-control" rows="3" placeholder="Piyasa araştırması yapıldı, yönetim sunumu hazırlandı..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Eforu Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        async function submitProject(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const json = Object.fromEntries(formData.entries());

            const res = await fetch('<?= site_url('verticals/save_consulting_project') ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': '<?= $this->security->get_csrf_hash() ?>'},
                body: JSON.stringify(json)
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            }
        }

        async function submitTimesheet(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const json = Object.fromEntries(formData.entries());
            json.is_billable = document.getElementById('isBillableSwitch').checked ? 1 : 0;

            const res = await fetch('<?= site_url('verticals/save_consulting_timesheet') ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': '<?= $this->security->get_csrf_hash() ?>'},
                body: JSON.stringify(json)
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            }
        }

        async function addMilestone(projectId, projectTitle) {
            const title = prompt(`"${projectTitle}" için yeni Kilometre Taşı / Faz Başlığı giriniz:`);
            if (!title) return;
            const dueDate = prompt('Hedef Teslim Tarihi (YYYY-AA-GG):', '<?= date('Y-m-d', strtotime('+30 days')) ?>');
            if (!dueDate) return;

            const res = await fetch('<?= site_url('verticals/save_consulting_milestone') ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': '<?= $this->security->get_csrf_hash() ?>'},
                body: JSON.stringify({
                    id_projects: projectId,
                    title: title,
                    due_date: dueDate
                })
            });
            const data = await res.json();
            if (data.success) {
                alert('Kilometre taşı projeye başarıyla eklendi.');
                location.reload();
            }
        }
    </script>
</body>
</html>
