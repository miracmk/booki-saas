<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @var array $vehicles
 * @var array $work_orders
 * @var array $customers
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
                <h1 class="h3 fw-bold mb-1"><i class="fas fa-car text-danger me-2"></i>Oto Servis, Araç Sicili & DVI Ekspertiz</h1>
                <p class="text-muted small mb-0">Müşteri araç kartları (plaka/VIN), DVI dijital ekspertiz checklistleri ve servis iş emri (work order) akışı.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-vehicle">
                    <i class="fas fa-plus me-1"></i> Yeni Araç Tanımla
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
                                                    'inspected' => ['bg-info', 'Ekspertiz Yapıldı'],
                                                    'approved' => ['bg-primary', 'Müşteri Onayladı'],
                                                    'in_progress' => ['bg-warning text-dark', 'İşlemde / Liftte'],
                                                    'parts_waiting' => ['bg-danger', 'Parça Bekleniyor'],
                                                    'ready' => ['bg-success', 'Araç Hazır!'],
                                                    'delivered' => ['bg-dark', 'Teslim Edildi'],
                                                ];
                                                $st = $stMap[$wo['status']] ?? ['bg-secondary', $wo['status']];
                                                ?>
                                                <span class="badge <?= $st[0] ?>"><?= $st[1] ?></span>
                                            </td>
                                            <td>₺<?= number_format($wo['estimated_cost'], 2) ?></td>
                                            <td class="fw-bold text-success">₺<?= number_format($wo['final_cost'] ?: $wo['estimated_cost'], 2) ?></td>
                                            <td><small class="text-muted"><?= date('d.m.Y H:i', strtotime($wo['created_at'])) ?></small></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-warning btn-wo-status" data-id="<?= $wo['id'] ?>" data-status="in_progress">İşleme Al</button>
                                                    <button class="btn btn-outline-success btn-wo-status" data-id="<?= $wo['id'] ?>" data-status="ready">Hazır</button>
                                                    <button class="btn btn-outline-dark btn-wo-status" data-id="<?= $wo['id'] ?>" data-status="delivered">Teslim</button>
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
                                            <td><?= number_format($v['current_km']) ?> km</td>
                                            <td><?= e(trim($v['owner_first_name'] . ' ' . $v['owner_last_name'])) ?></td>
                                            <td><?= e($v['phone_number'] ?: '-') ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary btn-new-dvi" data-id="<?= $v['id'] ?>" data-plate="<?= e($v['plate_number']) ?>">
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

    <!-- MODAL: YENİ ARAÇ -->
    <div class="modal fade" id="modal-add-vehicle" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-car text-danger me-2"></i>Yeni Araç Tanımla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-add-vehicle">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Araç Sahibi (Müşteri)</label>
                            <select name="id_users_customer" class="form-select" required>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e($c['first_name'] . ' ' . $c['last_name']) ?> (<?= e($c['phone_number'] ?: '') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Plaka</label>
                                <input type="text" name="plate_number" class="form-control font-monospace" placeholder="34 ABC 123" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Şasi No (VIN)</label>
                                <input type="text" name="vin" class="form-control font-monospace" placeholder="WBA...">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Marka</label>
                                <input type="text" name="brand" class="form-control" placeholder="BMW, Mercedes, Toyota..." required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Model</label>
                                <input type="text" name="model" class="form-control" placeholder="320i, C200, Corolla..." required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Model Yılı</label>
                                <input type="number" name="year" class="form-control" value="2022">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Güncel Kilometre</label>
                                <input type="number" name="current_km" class="form-control" placeholder="45000">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Aracı Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('form-add-vehicle').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const data = Object.fromEntries(fd.entries());
        try {
            const res = await fetch('<?= site_url('api/v1/verticals/automotive/vehicles') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            if (res.ok) {
                alert('Araç başarıyla kaydedildi!');
                window.location.reload();
            } else {
                alert('Hata: ' + (json.error || 'İşlem başarısız'));
            }
        } catch (err) {
            alert('Ağ hatası: ' + err.message);
        }
    });

    document.querySelectorAll('.btn-wo-status').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            try {
                const res = await fetch('<?= site_url('api/v1/verticals/automotive/work_orders/') ?>' + id + '/status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: status })
                });
                if (res.ok) {
                    window.location.reload();
                } else {
                    alert('Hata oluştu.');
                }
            } catch (err) {
                alert('Ağ hatası: ' + err.message);
            }
        });
    });
    </script>
</body>
</html>
