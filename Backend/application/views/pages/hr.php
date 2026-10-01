<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container-fluid backend-page py-4 px-md-5" id="hr-page">
    <!-- Header & KPIs -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-users-cog text-primary me-2"></i> İnsan Kaynakları & Personel Yönetimi</h2>
            <p class="text-muted mb-0">Kurumsal kadro, özlük dosyaları, organizasyon şeması, zimmet ve işe alım merkezi.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('attendance') ?>" class="btn btn-outline-primary"><i class="fas fa-clock me-1"></i> PDKS & Vardiya</a>
            <a href="<?= site_url('leaves') ?>" class="btn btn-outline-success"><i class="fas fa-calendar-check me-1"></i> İzin Yönetimi</a>
            <a href="<?= site_url('payroll') ?>" class="btn btn-outline-warning"><i class="fas fa-money-check-alt me-1"></i> Bordro & Maaş</a>
            <a href="<?= site_url('attendance/kiosk') ?>" target="_blank" class="btn btn-dark"><i class="fas fa-tablet-alt me-1"></i> Kiosk Modu</a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card shadow-sm border-0 rounded-3 p-3 bg-white border-start border-primary border-4">
                <span class="text-muted small fw-semibold">Toplam Kadro</span>
                <h3 class="fw-bold mb-0 text-primary mt-1"><?= $kpis['total_employees'] ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card shadow-sm border-0 rounded-3 p-3 bg-white border-start border-success border-4">
                <span class="text-muted small fw-semibold">Bugün Mesaide</span>
                <h3 class="fw-bold mb-0 text-success mt-1"><?= $kpis['today_present'] ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card shadow-sm border-0 rounded-3 p-3 bg-white border-start border-info border-4">
                <span class="text-muted small fw-semibold">Departman</span>
                <h3 class="fw-bold mb-0 text-info mt-1"><?= $kpis['departments_count'] ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card shadow-sm border-0 rounded-3 p-3 bg-white border-start border-warning border-4">
                <span class="text-muted small fw-semibold">Bekleyen İzinler</span>
                <h3 class="fw-bold mb-0 text-warning mt-1"><?= $kpis['pending_leaves'] ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card shadow-sm border-0 rounded-3 p-3 bg-white border-start border-danger border-4">
                <span class="text-muted small fw-semibold">Avans Talepleri</span>
                <h3 class="fw-bold mb-0 text-danger mt-1"><?= $kpis['pending_advances'] ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card shadow-sm border-0 rounded-3 p-3 bg-white border-start border-secondary border-4">
                <span class="text-muted small fw-semibold">Açık Pozisyonlar</span>
                <h3 class="fw-bold mb-0 text-secondary mt-1"><?= $kpis['open_jobs'] ?></h3>
            </div>
        </div>
    </div>

    <!-- Alert Bar if any milestones -->
    <?php if (!empty($alerts['expiring_documents']) || !empty($alerts['birthdays']) || !empty($alerts['probations_ending'])): ?>
    <div class="alert alert-info border-0 shadow-sm d-flex flex-wrap align-items-center justify-content-between p-3 mb-4 rounded-3 gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-bell fa-lg text-primary"></i>
            <div>
                <strong>İK Bildirimleri:</strong>
                <?php if (!empty($alerts['expiring_documents'])): ?>
                    <span class="badge bg-danger ms-2"><?= count($alerts['expiring_documents']) ?> Süresi Dolan Evrak</span>
                <?php endif; ?>
                <?php if (!empty($alerts['probations_ending'])): ?>
                    <span class="badge bg-warning text-dark ms-2"><?= count($alerts['probations_ending']) ?> Deneme Süresi Biten</span>
                <?php endif; ?>
                <?php if (!empty($alerts['birthdays'])): ?>
                    <span class="badge bg-success ms-2"><?= count($alerts['birthdays']) ?> Yaklaşan Doğum Günü</span>
                <?php endif; ?>
            </div>
        </div>
        <span class="small text-muted">Özlük ve uyumluluk kontrolleri günceldir.</span>
    </div>
    <?php endif; ?>

    <!-- Nav Tabs -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs nav-justified border-bottom-0" id="hrTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold <?= $active_tab === 'employees' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-employees">
                        <i class="fas fa-id-badge me-2"></i> Personel Kadrosu & Özlük
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold <?= $active_tab === 'departments' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-departments">
                        <i class="fas fa-sitemap me-2"></i> Departmanlar & Unvanlar
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold <?= $active_tab === 'orgchart' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-orgchart">
                        <i class="fas fa-project-diagram me-2"></i> Organizasyon Şeması
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold <?= $active_tab === 'documents' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-documents">
                        <i class="fas fa-folder-open me-2"></i> Dijital Evrak Kasası
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold <?= $active_tab === 'assets' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-assets">
                        <i class="fas fa-laptop me-2"></i> Zimmet Takibi
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-semibold <?= $active_tab === 'recruitment' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-recruitment">
                        <i class="fas fa-user-plus me-2"></i> İşe Alım (ATS)
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content">
                <!-- 1. EMPLOYEES TAB -->
                <div class="tab-pane fade <?= $active_tab === 'employees' ? 'show active' : '' ?>" id="tab-employees">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h5 class="fw-bold mb-0">Aktif Kadro (<?= count($employees) ?> Personel)</h5>
                        <div class="d-flex gap-2">
                            <a href="<?= site_url('providers') ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i> Yeni Personel Ekle
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>Personel</th>
                                    <th>İş Unvanı & Rol</th>
                                    <th>Departman</th>
                                    <th>İletişim</th>
                                    <th>İşe Giriş</th>
                                    <th>Kiosk PIN</th>
                                    <th class="text-end">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                                <?= strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= html_escape($emp['first_name'] . ' ' . $emp['last_name']) ?></div>
                                                <small class="text-muted">TCKN: <?= html_escape($emp['tckn_passport'] ?: '—') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= html_escape($emp['job_title'] ?: ($emp['designation_title'] ?: 'Uzman')) ?></span>
                                        <div class="small text-muted"><?= html_escape($emp['role_slug'] ?: $emp['role_name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info fw-semibold"><?= html_escape($emp['department_name'] ?: 'Genel') ?></span>
                                    </td>
                                    <td>
                                        <div><i class="fas fa-envelope text-muted me-1"></i> <?= html_escape($emp['email']) ?></div>
                                        <small class="text-muted"><i class="fas fa-phone text-muted me-1"></i> <?= html_escape($emp['phone_number'] ?: '—') ?></small>
                                    </td>
                                    <td>
                                        <div><?= !empty($emp['date_of_joining']) ? date('d.m.Y', strtotime($emp['date_of_joining'])) : '—' ?></div>
                                        <small class="text-muted"><?= $emp['employment_type'] === 'full_time' ? 'Tam Zamanlı' : 'Yarı Zamanlı' ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= html_escape($emp['pin_code'] ?: 'Tanımsız') ?></span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary btn-edit-employee" data-user-id="<?= $emp['id'] ?>">
                                            <i class="fas fa-edit me-1"></i> Özlük Düzenle
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. DEPARTMENTS & DESIGNATIONS TAB -->
                <div class="tab-pane fade <?= $active_tab === 'departments' ? 'show active' : '' ?>" id="tab-departments">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0"><i class="fas fa-building text-primary me-2"></i> Departmanlar</h5>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddDepartment">
                                    <i class="fas fa-plus me-1"></i> Departman Ekle
                                </button>
                            </div>
                            <div class="list-group shadow-sm border-0">
                                <?php foreach ($departments as $dept): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center p-3 border mb-2 rounded-3">
                                    <div>
                                        <h6 class="fw-bold mb-1"><?= html_escape($dept['name']) ?> <small class="text-muted">(<?= html_escape($dept['code']) ?>)</small></h6>
                                        <small class="text-muted"><i class="fas fa-users me-1"></i> <?= $dept['employee_count'] ?> Çalışan</small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-danger btn-delete-dept" data-id="<?= $dept['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0"><i class="fas fa-user-tag text-info me-2"></i> Standart Unvanlar</h5>
                                <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalAddDesignation">
                                    <i class="fas fa-plus me-1"></i> Unvan Ekle
                                </button>
                            </div>
                            <div class="list-group shadow-sm border-0">
                                <?php foreach ($designations as $desig): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center p-3 border mb-2 rounded-3">
                                    <div>
                                        <h6 class="fw-bold mb-0"><?= html_escape($desig['title']) ?></h6>
                                        <small class="text-muted"><?= html_escape($desig['code']) ?> &bull; <?= $desig['employee_count'] ?> Personel</small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger btn-delete-desig" data-id="<?= $desig['id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. ORG CHART TAB -->
                <div class="tab-pane fade <?= $active_tab === 'orgchart' ? 'show active' : '' ?>" id="tab-orgchart">
                    <h5 class="fw-bold mb-3"><i class="fas fa-sitemap text-primary me-2"></i> Şirket Hiyerarşisi & Yönetim Ağacı</h5>
                    <div class="p-4 bg-light rounded-4 border">
                        <div class="org-tree-container">
                            <?php
                            function render_org_node($node) {
                                echo '<div class="org-node card shadow-sm p-3 mb-3 border-0 rounded-3 bg-white" style="display: inline-block; min-width: 240px; margin-right: 15px;">';
                                echo '<div class="fw-bold text-primary">' . html_escape($node['name']) . '</div>';
                                echo '<div class="small fw-semibold text-dark">' . html_escape($node['title']) . '</div>';
                                echo '<small class="text-muted"><i class="fas fa-building me-1"></i>' . html_escape($node['department']) . '</small>';
                                if (!empty($node['children'])) {
                                    echo '<div class="ms-4 mt-3 ps-3 border-start border-2 border-primary">';
                                    foreach ($node['children'] as $child) {
                                        render_org_node($child);
                                    }
                                    echo '</div>';
                                }
                                echo '</div>';
                            }
                            if (!empty($org_tree)) {
                                foreach ($org_tree as $root) {
                                    render_org_node($root);
                                }
                            } else {
                                echo '<p class="text-muted mb-0">Henüz hiyerarşik ast-üst ataması yapılmamış. Personel özlük kartlarından "Rapor Ettiği Yönetici" alanını belirleyerek şemayı oluşturabilirsiniz.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- 4. DOCUMENTS TAB -->
                <div class="tab-pane fade <?= $active_tab === 'documents' ? 'show active' : '' ?>" id="tab-documents">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Özlük Belgeleri & Dosya Kasası (<?= count($documents) ?> Evrak)</h5>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalUploadDocument">
                            <i class="fas fa-upload me-1"></i> Evrak Yükle
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>Personel</th>
                                    <th>Belge Türü</th>
                                    <th>Başlık</th>
                                    <th>Geçerlilik Tarihi</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td class="fw-bold"><?= html_escape($doc['employee_name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= html_escape($doc['document_type']) ?></span></td>
                                    <td><?= html_escape($doc['title']) ?></td>
                                    <td><?= !empty($doc['expiry_date']) ? date('d.m.Y', strtotime($doc['expiry_date'])) : 'Süresiz' ?></td>
                                    <td>
                                        <span class="badge bg-success">Geçerli</span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url($doc['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i> İncele</a>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-doc" data-id="<?= $doc['id'] ?>"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 5. ASSETS TAB -->
                <div class="tab-pane fade <?= $active_tab === 'assets' ? 'show active' : '' ?>" id="tab-assets">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Zimmet & Varlık Kataloğu</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddAsset">
                                <i class="fas fa-plus me-1"></i> Yeni Varlık Ekle
                            </button>
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalAssignAsset">
                                <i class="fas fa-hand-holding me-1"></i> Personele Zimmetle
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th>Varlık Adı & Kod</th>
                                    <th>Kategori</th>
                                    <th>Seri No</th>
                                    <th>Durum</th>
                                    <th>Zimmetli Personel</th>
                                    <th class="text-end">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assets as $ast): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= html_escape($ast['asset_name']) ?></div>
                                        <small class="text-muted"><?= html_escape($ast['asset_code']) ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= html_escape($ast['category']) ?></span></td>
                                    <td><?= html_escape($ast['serial_number'] ?: '—') ?></td>
                                    <td>
                                        <?php if ($ast['status'] === 'assigned'): ?>
                                            <span class="badge bg-warning text-dark"><i class="fas fa-user-check me-1"></i> Zimmette</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i> Boşta (Müsait)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($ast['current_assignment'])): ?>
                                            <strong><?= html_escape($ast['current_assignment']['assigned_to_name']) ?></strong>
                                            <div class="small text-muted">Tarih: <?= date('d.m.Y', strtotime($ast['current_assignment']['assigned_date'])) ?></div>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if (!empty($ast['current_assignment'])): ?>
                                            <button class="btn btn-sm btn-outline-warning btn-return-asset" data-id="<?= $ast['current_assignment']['id'] ?>">
                                                <i class="fas fa-undo me-1"></i> İade Al
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-asset" data-id="<?= $ast['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 6. RECRUITMENT TAB -->
                <div class="tab-pane fade <?= $active_tab === 'recruitment' ? 'show active' : '' ?>" id="tab-recruitment">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Açık Pozisyonlar & Aday Havuzu (ATS)</h5>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddJob">
                            <i class="fas fa-plus me-1"></i> Yeni Pozisyon Aç
                        </button>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-5">
                            <h6 class="fw-bold text-muted mb-3">Açık İlanlar</h6>
                            <?php foreach ($job_openings as $job): ?>
                            <div class="card p-3 mb-2 border rounded-3 shadow-sm">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="fw-bold mb-1 text-primary"><?= html_escape($job['title']) ?></h6>
                                    <span class="badge bg-success">Açık</span>
                                </div>
                                <small class="text-muted mb-2"><?= html_escape($job['department_name'] ?: 'Genel') ?> &bull; <?= $job['applicants_count'] ?> Aday</small>
                                <p class="small text-muted mb-0"><?= html_escape(substr($job['job_description'] ?? '', 0, 100)) ?>...</p>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="col-md-7">
                            <h6 class="fw-bold text-muted mb-3">Son Aday Başvuruları</h6>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle border">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Aday</th>
                                            <th>Pozisyon</th>
                                            <th>Durum</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applicants as $app): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= html_escape($app['full_name']) ?></div>
                                                <small class="text-muted"><?= html_escape($app['phone'] ?: $app['email']) ?></small>
                                            </td>
                                            <td><?= html_escape($app['job_title']) ?></td>
                                            <td>
                                                <select class="form-select form-select-sm select-applicant-status" data-id="<?= $app['id'] ?>">
                                                    <option value="new" <?= $app['status'] === 'new' ? 'selected' : '' ?>>Yeni Başvuru</option>
                                                    <option value="screening" <?= $app['status'] === 'screening' ? 'selected' : '' ?>>Ön Eleme</option>
                                                    <option value="interview" <?= $app['status'] === 'interview' ? 'selected' : '' ?>>Mülakat</option>
                                                    <option value="offered" <?= $app['status'] === 'offered' ? 'selected' : '' ?>>Teklif Yapıldı</option>
                                                    <option value="hired" <?= $app['status'] === 'hired' ? 'selected' : '' ?>>İşe Alındı</option>
                                                    <option value="rejected" <?= $app['status'] === 'rejected' ? 'selected' : '' ?>>Reddedildi</option>
                                                </select>
                                            </td>
                                            <td>
                                                <a href="<?= site_url('providers') ?>" class="btn btn-sm btn-outline-success" title="Personel Olarak Kaydet">
                                                    <i class="fas fa-user-check"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Employee Profile -->
<div class="modal fade" id="modalEditEmployee" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formEditEmployee">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-edit text-primary me-2"></i> Personel Özlük Bilgilerini Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_users" id="emp_id_users">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">TCKN / Pasaport No</label>
                            <input type="text" class="form-control" name="tckn_passport" id="emp_tckn_passport">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">İş Unvanı (Kartvizit)</label>
                            <input type="text" class="form-control" name="job_title" id="emp_job_title">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Departman</label>
                            <select class="form-select" name="id_departments" id="emp_id_departments">
                                <option value="">Seçiniz...</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= html_escape($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Standart Unvan</label>
                            <select class="form-select" name="id_designations" id="emp_id_designations">
                                <option value="">Seçiniz...</option>
                                <?php foreach ($designations as $des): ?>
                                    <option value="<?= $des['id'] ?>"><?= html_escape($des['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Rapor Ettiği Yönetici</label>
                            <select class="form-select" name="reports_to_user_id" id="emp_reports_to_user_id">
                                <option value="">Seçiniz (Bağımsız / En Üst Yönetici)...</option>
                                <?php foreach ($employees as $e): ?>
                                    <option value="<?= $e['id'] ?>"><?= html_escape($e['first_name'] . ' ' . $e['last_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">İşe Giriş Tarihi</label>
                            <input type="date" class="form-control" name="date_of_joining" id="emp_date_of_joining">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kiosk / Terminal PIN Kodu (4-6 Hane)</label>
                            <input type="text" class="form-control" name="pin_code" id="emp_pin_code" maxlength="8">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Maaş IBAN</label>
                            <input type="text" class="form-control" name="iban" id="emp_iban" placeholder="TR...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Acil Durum Kişisi</label>
                            <input type="text" class="form-control" name="emergency_contact_name" id="emp_emergency_contact_name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Acil Durum Telefonu</label>
                            <input type="text" class="form-control" name="emergency_contact_phone" id="emp_emergency_contact_phone">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Department -->
<div class="modal fade" id="modalAddDepartment" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAddDept">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Yeni Departman Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Departman Adı</label>
                        <input type="text" class="form-control" name="name" required placeholder="örn: Mutfak & Bar">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Departman Kodu</label>
                        <input type="text" class="form-control" name="code" placeholder="örn: KITCHEN">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Designation -->
<div class="modal fade" id="modalAddDesignation" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAddDesig">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Yeni Unvan Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Unvan Adı</label>
                        <input type="text" class="form-control" name="title" required placeholder="örn: Baş Terapist">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Unvan Kodu</label>
                        <input type="text" class="form-control" name="code" placeholder="örn: LEAD_THERAPIST">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Upload Document -->
<div class="modal fade" id="modalUploadDocument" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formUploadDoc" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Yeni Özlük Belgesi Yükle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Personel</label>
                        <select class="form-select" name="id_users" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach ($employees as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= html_escape($e['first_name'] . ' ' . $e['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Belge Türü</label>
                        <select class="form-select" name="document_type">
                            <option value="contract">İş Sözleşmesi</option>
                            <option value="id_copy">Kimlik Fotokopisi</option>
                            <option value="criminal_record">Adli Sicil Kaydı</option>
                            <option value="health_cert">Sağlık Raporu / Hijyen Belgesi</option>
                            <option value="diploma">Diploma / Sertifika</option>
                            <option value="other">Diğer Evrak</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Belge Başlığı</label>
                        <input type="text" class="form-control" name="title" required placeholder="örn: 2026 İş Sözleşmesi">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Geçerlilik Tarihi (Opsiyonel)</label>
                        <input type="date" class="form-control" name="expiry_date">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Dosya Seç (PDF / Resim)</label>
                        <input type="file" class="form-control" name="file" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Yükle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Asset -->
<div class="modal fade" id="modalAddAsset" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAddAsset">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Yeni Varlık / Zimmet Eşyası Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Varlık Adı</label>
                        <input type="text" class="form-control" name="asset_name" required placeholder="örn: MacBook Pro 14">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Varlık Kodu</label>
                        <input type="text" class="form-control" name="asset_code" placeholder="örn: LAP-01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori</label>
                        <select class="form-select" name="category">
                            <option value="laptop">Bilgisayar & Laptop</option>
                            <option value="pos">POS Terminali</option>
                            <option value="phone">Şirket Telefonu</option>
                            <option value="vehicle">Şirket Aracı / Taşıt</option>
                            <option value="tool">Mesleki Ekipman / Cihaz</option>
                            <option value="uniform">Üniforma & Kıyafet</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Seri Numarası</label>
                        <input type="text" class="form-control" name="serial_number">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Assign Asset -->
<div class="modal fade" id="modalAssignAsset" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAssignAsset">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Personele Zimmet Teslimi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Varlık</label>
                        <select class="form-select" name="id_assets" required>
                            <option value="">Boştaki Varlığı Seçiniz...</option>
                            <?php foreach ($assets as $a): ?>
                                <?php if ($a['status'] === 'available'): ?>
                                    <option value="<?= $a['id'] ?>"><?= html_escape($a['asset_name'] . ' (' . $a['asset_code'] . ')') ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Teslim Edilecek Personel</label>
                        <select class="form-select" name="id_users" required>
                            <option value="">Personel Seçiniz...</option>
                            <?php foreach ($employees as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= html_escape($e['first_name'] . ' ' . $e['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Teslim Durumu / Şerh</label>
                        <input type="text" class="form-control" name="condition_on_assignment" value="Sorunsuz, eksiksiz ve çalışır durumda teslim edildi.">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">Zimmeti Onayla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit employee
    document.querySelectorAll('.btn-edit-employee').forEach(btn => {
        btn.addEventListener('click', async () => {
            const uid = btn.dataset.userId;
            const res = await fetch('<?= site_url("hr/employee_detail/") ?>' + uid);
            const json = await res.json();
            if (json.success) {
                const emp = json.employee;
                document.getElementById('emp_id_users').value = emp.id;
                document.getElementById('emp_tckn_passport').value = emp.tckn_passport || '';
                document.getElementById('emp_job_title').value = emp.job_title || '';
                document.getElementById('emp_id_departments').value = emp.id_departments || '';
                document.getElementById('emp_id_designations').value = emp.id_designations || '';
                document.getElementById('emp_reports_to_user_id').value = emp.reports_to_user_id || '';
                document.getElementById('emp_date_of_joining').value = emp.date_of_joining || '';
                document.getElementById('emp_pin_code').value = emp.pin_code || '';
                document.getElementById('emp_iban').value = emp.iban || '';
                document.getElementById('emp_emergency_contact_name').value = emp.emergency_contact_name || '';
                document.getElementById('emp_emergency_contact_phone').value = emp.emergency_contact_phone || '';
                new bootstrap.Modal(document.getElementById('modalEditEmployee')).show();
            }
        });
    });

    document.getElementById('formEditEmployee').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const res = await fetch('<?= site_url("hr/save_employee") ?>', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            location.reload();
        } else {
            alert(json.message);
        }
    });

    // Department Add
    document.getElementById('formAddDept').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('<?= site_url("hr/save_department") ?>', { method: 'POST', body: new FormData(e.target) });
        const json = await res.json();
        if (json.success) location.reload();
    });

    // Delete Department
    document.querySelectorAll('.btn-delete-dept').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (confirm('Bu departmanı silmek istediğinize emin misiniz?')) {
                await fetch('<?= site_url("hr/delete_department/") ?>' + btn.dataset.id, { method: 'POST' });
                location.reload();
            }
        });
    });

    // Designation Add
    document.getElementById('formAddDesig').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('<?= site_url("hr/save_designation") ?>', { method: 'POST', body: new FormData(e.target) });
        const json = await res.json();
        if (json.success) location.reload();
    });

    // Delete Designation
    document.querySelectorAll('.btn-delete-desig').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (confirm('Bu unvanı silmek istediğinize emin misiniz?')) {
                await fetch('<?= site_url("hr/delete_designation/") ?>' + btn.dataset.id, { method: 'POST' });
                location.reload();
            }
        });
    });

    // Upload Document
    document.getElementById('formUploadDoc').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('<?= site_url("hr/upload_document") ?>', { method: 'POST', body: new FormData(e.target) });
        const json = await res.json();
        if (json.success) location.reload();
        else alert(json.message);
    });

    // Delete Document
    document.querySelectorAll('.btn-delete-doc').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (confirm('Bu evrakı silmek istediğinize emin misiniz?')) {
                await fetch('<?= site_url("hr/delete_document/") ?>' + btn.dataset.id, { method: 'POST' });
                location.reload();
            }
        });
    });

    // Add Asset
    document.getElementById('formAddAsset').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('<?= site_url("hr/save_asset") ?>', { method: 'POST', body: new FormData(e.target) });
        const json = await res.json();
        if (json.success) location.reload();
    });

    // Assign Asset
    document.getElementById('formAssignAsset').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('<?= site_url("hr/assign_asset") ?>', { method: 'POST', body: new FormData(e.target) });
        const json = await res.json();
        if (json.success) location.reload();
        else alert(json.message);
    });

    // Return Asset
    document.querySelectorAll('.btn-return-asset').forEach(btn => {
        btn.addEventListener('click', async () => {
            const cond = prompt('Zimmet iade durum notu giriniz:', 'Eksiksiz ve hasarsız teslim alındı.');
            if (cond !== null) {
                const fd = new FormData();
                fd.append('id', btn.dataset.id);
                fd.append('condition_on_return', cond);
                await fetch('<?= site_url("hr/return_asset") ?>', { method: 'POST', body: fd });
                location.reload();
            }
        });
    });

    // Delete Asset
    document.querySelectorAll('.btn-delete-asset').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (confirm('Bu varlığı silmek istediğinize emin misiniz?')) {
                await fetch('<?= site_url("hr/delete_asset/") ?>' + btn.dataset.id, { method: 'POST' });
                location.reload();
            }
        });
    });

    // Applicant Status change
    document.querySelectorAll('.select-applicant-status').forEach(sel => {
        sel.addEventListener('change', async () => {
            const fd = new FormData();
            fd.append('id', sel.dataset.id);
            fd.append('status', sel.value);
            await fetch('<?= site_url("hr/update_applicant_status") ?>', { method: 'POST', body: fd });
        });
    });
});
</script>

<?php end_section('content'); ?>
