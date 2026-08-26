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

<div class="container backend-page py-3" id="audit-log-page">
    <h4 class="mb-3 fw-light">Denetim Kayıtları</h4>
    <p class="text-muted small mb-4">
        Giriş/çıkış denemeleri, müşteri/terapist anonimleştirme veya silme işlemleri ve ödeme kayıt
        değişiklikleri burada izlenir. Otomatik (gece 03:30) veri saklama işlemleri de "system" olarak
        (boş aktör alanıyla) görünür.
    </p>

    <form method="get" class="row g-2 mb-4">
        <div class="col-12 col-sm-3">
            <label class="form-label" for="filter-action">İşlem (kısmi eşleşme)</label>
            <input type="text" id="filter-action" name="action" class="form-control"
                   placeholder="örn. customer.anonymize" value="<?= e($action_filter) ?>">
        </div>
        <div class="col-12 col-sm-3">
            <label class="form-label" for="filter-from">Başlangıç</label>
            <input type="date" id="filter-from" name="from" class="form-control" value="<?= e($from_date) ?>">
        </div>
        <div class="col-12 col-sm-3">
            <label class="form-label" for="filter-to">Bitiş</label>
            <input type="date" id="filter-to" name="to" class="form-control" value="<?= e($to_date) ?>">
        </div>
        <div class="col-12 col-sm-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Filtrele</button>
        </div>
    </form>

    <p class="text-muted small">Toplam <?= $total ?> kayıt.</p>

    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>Tarih/Saat</th>
                    <th>İşlem</th>
                    <th>Aktör</th>
                    <th>Rol</th>
                    <th>Hedef</th>
                    <th>IP</th>
                    <th>Detay</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Kayıt bulunamadı.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><?= e($entry['created_at']) ?></td>
                            <td><code><?= e($entry['action']) ?></code></td>
                            <td><?= $entry['actor_label'] ? e($entry['actor_label']) : '<span class="text-muted">(system)</span>' ?></td>
                            <td><?= e($entry['actor_role'] ?? '-') ?></td>
                            <td>
                                <?php if ($entry['entity_type']): ?>
                                    <?= e($entry['entity_type']) ?> #<?= (int) $entry['entity_id'] ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= e($entry['ip_address'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($entry['details'])): ?>
                                    <small class="text-muted"><?= e(json_encode($entry['details'], JSON_UNESCAPED_UNICODE)) ?></small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $total_pages = $page_size > 0 ? (int) ceil($total / $page_size) : 0; ?>
    <?php if ($total_pages > 1): ?>
        <nav>
            <ul class="pagination">
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
