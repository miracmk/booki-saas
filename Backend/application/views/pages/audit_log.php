<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php
$entries = vars('entries') ?? [];
$total = (int) (vars('total') ?? 0);
$page = (int) (vars('page') ?? 1);
$page_size = (int) (vars('page_size') ?? 100);
$action_filter = (string) (vars('action_filter') ?? '');
$from_date = (string) (vars('from_date') ?? '');
$to_date = (string) (vars('to_date') ?? '');
?>

<div class="container-fluid py-3 px-4" id="audit-log-page">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0 fw-bold text-dark">
                <i class="fas fa-shield-alt me-2 text-primary"></i>Denetim Kayıtları
            </h1>
            <p class="text-muted small mb-0">
                Giriş/çıkış denemeleri, veri saklama, anonimleştirme ve hassas kayıt değişiklikleri izlenir.
            </p>
        </div>
        <span class="badge bg-white text-dark border shadow-sm px-3 py-2 fs-7">
            Toplam <strong><?= number_format($total) ?></strong> kayıt
        </span>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold text-muted" for="filter-action">İşlem (kısmi eşleşme)</label>
                    <input type="text" id="filter-action" name="action" class="form-control"
                           placeholder="örn. customer.anonymize" value="<?= e($action_filter) ?>">
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-semibold text-muted" for="filter-from">Başlangıç Tarihi</label>
                    <input type="date" id="filter-from" name="from" class="form-control" value="<?= e($from_date) ?>">
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-semibold text-muted" for="filter-to">Bitiş Tarihi</label>
                    <input type="date" id="filter-to" name="to" class="form-control" value="<?= e($to_date) ?>">
                </div>
                <div class="col-12 col-md-2 col-lg-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Filtrele
                    </button>
                </div>
                <?php if ($action_filter !== '' || $from_date !== '' || $to_date !== ''): ?>
                <div class="col-12 col-md-2 col-lg-2">
                    <a href="<?= site_url('audit_log') ?>" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-undo me-1"></i> Sıfırla
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 text-nowrap" style="width: 170px;">Tarih / Saat</th>
                        <th class="text-nowrap" style="width: 180px;">İşlem</th>
                        <th class="text-nowrap" style="width: 180px;">Aktör</th>
                        <th class="text-nowrap text-center" style="width: 90px;">Rol</th>
                        <th class="text-nowrap" style="width: 170px;">Hedef</th>
                        <th class="text-nowrap" style="width: 130px;">IP Adresi</th>
                        <th class="pe-3">Detaylar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entries)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-search fa-3x text-secondary opacity-50 mb-3 d-block"></i>
                                <span>Kriterlere uygun denetim kaydı bulunamadı.</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($entries as $entry): ?>
                            <?php
                            $action = (string) ($entry['action'] ?? '');
                            $badge_class = 'bg-secondary-subtle text-secondary border';
                            if (str_contains($action, 'create') || str_contains($action, 'sell') || str_contains($action, 'join')) {
                                $badge_class = 'bg-primary-subtle text-primary border border-primary-subtle';
                            } elseif (str_contains($action, 'success') || str_contains($action, 'approve')) {
                                $badge_class = 'bg-success-subtle text-success border border-success-subtle';
                            } elseif (str_contains($action, 'delete') || str_contains($action, 'failed') || str_contains($action, 'cancel') || str_contains($action, 'void')) {
                                $badge_class = 'bg-danger-subtle text-danger border border-danger-subtle';
                            } elseif (str_contains($action, 'update') || str_contains($action, 'renew')) {
                                $badge_class = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                            }
                            ?>
                            <tr>
                                <td class="ps-3 text-nowrap">
                                    <span class="fw-semibold text-dark"><?= date('d.m.Y', strtotime($entry['created_at'])) ?></span>
                                    <span class="text-muted small ms-1"><?= date('H:i:s', strtotime($entry['created_at'])) ?></span>
                                </td>
                                <td class="text-nowrap">
                                    <span class="badge <?= $badge_class ?> font-monospace"><?= e($action) ?></span>
                                </td>
                                <td class="text-nowrap">
                                    <?= $entry['actor_label'] ? '<i class="fas fa-user-circle me-1 text-muted"></i>' . e($entry['actor_label']) : '<span class="badge bg-light text-muted border">sistem</span>' ?>
                                </td>
                                <td class="text-center text-nowrap">
                                    <span class="badge bg-light text-dark border"><?= e($entry['actor_role'] ?? '-') ?></span>
                                </td>
                                <td class="text-nowrap">
                                    <?php if ($entry['entity_type']): ?>
                                        <span class="badge bg-light text-dark border">
                                            <?= e(str_replace('_', ' ', $entry['entity_type'])) ?> #<?= (int) $entry['entity_id'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap">
                                    <code class="text-muted"><?= e($entry['ip_address'] ?? '-') ?></code>
                                </td>
                                <td class="pe-3">
                                    <?php if (!empty($entry['details']) && is_array($entry['details'])): ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($entry['details'] as $k => $v): ?>
                                                <?php
                                                $display_val = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v;
                                                $full_val = $display_val;
                                                if (mb_strlen($display_val) > 28) {
                                                    $display_val = mb_substr($display_val, 0, 28) . '…';
                                                }
                                                ?>
                                                <span class="badge bg-light text-dark border fw-normal py-1" title="<?= e($full_val) ?>">
                                                    <span class="text-muted"><?= e($k) ?>:</span> <?= e($display_val) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php elseif (!empty($entry['details'])): ?>
                                        <small class="text-muted"><?= e((string) $entry['details']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php $total_pages = $page_size > 0 ? (int) ceil($total / $page_size) : 0; ?>
    <?php if ($total_pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link"
                           href="?page=<?= $p ?>&action=<?= urlencode($action_filter) ?>&from=<?= urlencode($from_date) ?>&to=<?= urlencode($to_date) ?>">
                            <?= $p ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php end_section('content'); ?>
