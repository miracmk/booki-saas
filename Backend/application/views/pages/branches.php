<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php
$branches = vars('branches') ?? [];
$total_branches = count($branches);
$active_branches = 0;
$default_branch_name = '-';

foreach ($branches as $b) {
    if (!empty($b['is_active'])) {
        $active_branches++;
    }
    if (!empty($b['is_default'])) {
        $default_branch_name = $b['name'];
    }
}
?>

<div class="container backend-page py-4" id="branches-page">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="fas fa-building fa-lg"></i>
                </div>
                <h3 class="mb-0 fw-bold"><?= lang('branches') ?: 'Şubeler & Lokasyonlar' ?></h3>
            </div>
            <p class="text-muted small mb-0 ms-md-5">İşletmenizin fiziksel şubelerini, lokasyonlarını ve adres bilgilerini bu alandan yönetebilirsiniz.</p>
        </div>

        <?php if (can('add', PRIV_BRANCHES)): ?>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#branchModal" onclick="openAddBranchModal()">
                <i class="fas fa-plus"></i>
                <span>Yeni Şube Ekle</span>
            </button>
        <?php endif; ?>
    </div>

    <!-- KPI / Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <span class="text-muted small d-block">Toplam Şube</span>
                        <h4 class="mb-0 fw-bold text-dark mt-1"><?= $total_branches ?></h4>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <span class="text-muted small d-block">Aktif Şubeler</span>
                        <h4 class="mb-0 fw-bold text-success mt-1"><?= $active_branches ?></h4>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div class="text-truncate">
                        <span class="text-muted small d-block">Merkez / Varsayılan Şube</span>
                        <h5 class="mb-0 fw-bold text-primary mt-1 text-truncate" title="<?= e($default_branch_name) ?>"><?= e($default_branch_name) ?></h5>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Message Container -->
    <div id="branch-alert-container"></div>

    <!-- Branches List Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold">
                <i class="fas fa-list-ul me-2 text-primary"></i>Kayıtlı Şube Listesi
            </h6>
            <span class="badge bg-light text-secondary border">Toplam <?= $total_branches ?> Kayıt</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="branches-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 25%;">Şube Adı</th>
                        <th style="width: 20%;">Telefon</th>
                        <th style="width: 30%;">Adres</th>
                        <th class="text-center" style="width: 10%;">Durum</th>
                        <th class="text-end pe-3" style="width: 15%;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($branches)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-building fa-2x mb-3 text-secondary opacity-50 d-block"></i>
                                Henüz tanımlanmış bir şube bulunmuyor.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($branches as $branch): ?>
                            <tr id="branch-row-<?= (int)$branch['id'] ?>">
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-sm rounded-2 bg-light border p-2 text-primary d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                            <i class="fas fa-store"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark d-block"><?= e($branch['name']) ?></span>
                                            <?php if (!empty($branch['is_default'])): ?>
                                                <span class="badge bg-warning bg-opacity-25 text-dark border border-warning-subtle" style="font-size: 10px;">
                                                    <i class="fas fa-star me-1 text-warning"></i>Varsayılan Şube
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($branch['phone'])): ?>
                                        <a href="tel:<?= e($branch['phone']) ?>" class="text-decoration-none text-dark small">
                                            <i class="fas fa-phone-alt me-1 text-muted"></i><?= e($branch['phone']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($branch['address'])): ?>
                                        <span class="small text-secondary text-truncate d-inline-block" style="max-width: 280px;" title="<?= e($branch['address']) ?>">
                                            <i class="fas fa-map-marker-alt me-1 text-danger opacity-75"></i><?= e($branch['address']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($branch['is_active'])): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            <i class="fas fa-check-circle me-1"></i>Aktif
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                            <i class="fas fa-times-circle me-1"></i>Pasif
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <?php if (can('edit', PRIV_BRANCHES)): ?>
                                            <button type="button" class="btn btn-outline-secondary" title="Düzenle" 
                                                    onclick='openEditBranchModal(<?= json_encode($branch, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (can('delete', PRIV_BRANCHES)): ?>
                                            <?php if (empty($branch['is_default'])): ?>
                                                <button type="button" class="btn btn-outline-danger" title="Sil" 
                                                        onclick="confirmDeleteBranch(<?= (int)$branch['id'] ?>, '<?= e(addslashes($branch['name'])) ?>')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-outline-secondary opacity-50" title="Varsayılan şube silinemez" disabled>
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            <?php endif; ?>
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

<!-- Add / Edit Branch Modal -->
<div class="modal fade" id="branchModal" tabindex="-1" aria-labelledby="branchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="branchModalLabel">
                    <i class="fas fa-building me-2 text-primary"></i><span>Şube Ekle</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form id="branchForm" onsubmit="submitBranchForm(event)">
                <div class="modal-body p-4">
                    <input type="hidden" id="branch-id" name="id" value="">

                    <div class="mb-3">
                        <label for="branch-name" class="form-label fw-semibold small">Şube Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="branch-name" name="name" placeholder="Örn. Kadıköy Şubesi, Merkez Ofis" required maxlength="190">
                    </div>

                    <div class="mb-3">
                        <label for="branch-phone" class="form-label fw-semibold small">Telefon Numarası</label>
                        <input type="tel" class="form-control" id="branch-phone" name="phone" placeholder="Örn. 0216 123 45 67" maxlength="32">
                    </div>

                    <div class="mb-3">
                        <label for="branch-address" class="form-label fw-semibold small">Adres Bilgisi</label>
                        <textarea class="form-control" id="branch-address" name="address" rows="3" placeholder="Şubenin açık adresi..." maxlength="255"></textarea>
                    </div>

                    <div class="row g-3 pt-2">
                        <div class="col-sm-6">
                            <div class="card bg-light border p-2 rounded-2">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="branch-is-active" name="is_active" value="1" checked>
                                    <label class="form-check-label fw-semibold small" for="branch-is-active">Aktif Şube</label>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">Müşteriler randevu alabilir.</small>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="card bg-light border p-2 rounded-2">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="branch-is-default" name="is_default" value="1">
                                    <label class="form-check-label fw-semibold small" for="branch-is-default">Varsayılan Şube</label>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">İşletmenin ana merkezidir.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary px-4" id="btn-save-branch">
                        <i class="fas fa-save me-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteBranchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-body p-4 text-center">
                <div class="text-danger mb-3">
                    <i class="fas fa-exclamation-triangle fa-3x"></i>
                </div>
                <h5 class="fw-bold mb-2">Şubeyi Sil</h5>
                <p class="text-muted small mb-4">
                    "<strong id="delete-branch-name"></strong>" şubesini silmek istediğinize emin misiniz? Bu işlem geri alınamaz.
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="button" class="btn btn-danger flex-fill" id="btn-confirm-delete" onclick="executeDeleteBranch()">Sil</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let deletingBranchId = null;

function getCsrfToken() {
    return (typeof window.vars === 'function' ? window.vars('csrf_token') : null)
        || (window.App && window.App.Security ? window.App.Security.csrfToken : '')
        || '';
}

function showBranchAlert(type, message) {
    const container = document.getElementById('branch-alert-container');
    if (!container) return;
    container.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
}

function openAddBranchModal() {
    document.getElementById('branchModalLabel').innerHTML = '<i class="fas fa-building me-2 text-primary"></i><span>Yeni Şube Ekle</span>';
    document.getElementById('branch-id').value = '';
    document.getElementById('branch-name').value = '';
    document.getElementById('branch-phone').value = '';
    document.getElementById('branch-address').value = '';
    document.getElementById('branch-is-active').checked = true;
    document.getElementById('branch-is-default').checked = false;
}

function openEditBranchModal(branch) {
    document.getElementById('branchModalLabel').innerHTML = '<i class="fas fa-edit me-2 text-primary"></i><span>Şube Düzenle</span>';
    document.getElementById('branch-id').value = branch.id || '';
    document.getElementById('branch-name').value = branch.name || '';
    document.getElementById('branch-phone').value = branch.phone || '';
    document.getElementById('branch-address').value = branch.address || '';
    document.getElementById('branch-is-active').checked = !!parseInt(branch.is_active);
    document.getElementById('branch-is-default').checked = !!parseInt(branch.is_default);

    const modalEl = document.getElementById('branchModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function submitBranchForm(event) {
    event.preventDefault();
    const btn = document.getElementById('btn-save-branch');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Kaydediliyor...';

    const branchId = document.getElementById('branch-id').value;
    const branchData = {
        name: document.getElementById('branch-name').value.trim(),
        phone: document.getElementById('branch-phone').value.trim(),
        address: document.getElementById('branch-address').value.trim(),
        is_active: document.getElementById('branch-is-active').checked ? 1 : 0,
        is_default: document.getElementById('branch-is-default').checked ? 1 : 0
    };

    if (branchId) {
        branchData.id = parseInt(branchId);
    }

    const endpoint = branchId ? '<?= site_url("branches/update") ?>' : '<?= site_url("branches/store") ?>';

    $.ajax({
        url: endpoint,
        type: 'POST',
        data: {
            csrf_token: getCsrfToken(),
            branch: branchData
        },
        dataType: 'json',
        success: function(response) {
            btn.disabled = false;
            btn.innerHTML = originalText;
            const modalEl = document.getElementById('branchModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            showBranchAlert('success', branchId ? 'Şube başarıyla güncellendi.' : 'Yeni şube başarıyla eklendi.');
            setTimeout(() => { window.location.reload(); }, 600);
        },
        error: function(xhr) {
            btn.disabled = false;
            btn.innerHTML = originalText;
            let errMsg = 'İşlem sırasında bir hata oluştu.';
            try {
                const res = JSON.parse(xhr.responseText);
                if (res.message) errMsg = res.message;
            } catch(e) {}
            alert('Hata: ' + errMsg);
        }
    });
}

function confirmDeleteBranch(branchId, branchName) {
    deletingBranchId = branchId;
    document.getElementById('delete-branch-name').textContent = branchName;
    const modalEl = document.getElementById('deleteBranchModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function executeDeleteBranch() {
    if (!deletingBranchId) return;

    const btn = document.getElementById('btn-confirm-delete');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    $.ajax({
        url: '<?= site_url("branches/destroy") ?>',
        type: 'POST',
        data: {
            csrf_token: getCsrfToken(),
            branch_id: deletingBranchId
        },
        dataType: 'json',
        success: function(response) {
            const modalEl = document.getElementById('deleteBranchModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            showBranchAlert('success', 'Şube başarıyla silindi.');
            setTimeout(() => { window.location.reload(); }, 600);
        },
        error: function(xhr) {
            btn.disabled = false;
            btn.innerHTML = 'Sil';
            let errMsg = 'Şube silinemedi.';
            try {
                const res = JSON.parse(xhr.responseText);
                if (res.message) errMsg = res.message;
            } catch(e) {}
            alert('Hata: ' + errMsg);
        }
    });
}
</script>

<?php end_section('content'); ?>
