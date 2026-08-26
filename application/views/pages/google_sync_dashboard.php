<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php $rows = vars('rows') ?? []; ?>

<div class="container backend-page py-3" id="google-sync-dashboard-page">
    <h4 class="mb-3 fw-light">Google Takvim Senkron Durumu</h4>
    <p class="text-muted small mb-4">
        Google Takvim senkronu açık her sağlayıcı için: anlık push-bildirim kanalının durumu (varsa
        süresi), en son senkron denemesi (tam tarama veya webhook tetiklemeli) ve varsa en son hata.
        Kanal yoksa veya süresi dolmuşsa, bir sonraki <code>console sync</code> çalışmasında otomatik
        yenilenir.
    </p>

    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>Sağlayıcı</th>
                    <th>Takvim</th>
                    <th>Push Kanalı</th>
                    <th>Kanal Süresi</th>
                    <th>Son Senkron</th>
                    <th>Tetikleyici</th>
                    <th>Durum</th>
                    <th>Olay Sayısı</th>
                    <th>Son Hata</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            Google Takvim senkronu açık bir sağlayıcı yok.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= e($row['provider_name']) ?></td>
                            <td><small class="text-muted"><?= e((string) ($row['calendar_id'] ?? '-')) ?></small></td>
                            <td>
                                <?php if ($row['channel_active']): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Yok</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e((string) ($row['channel_expiration'] ?? '-')) ?></td>
                            <td><?= e((string) ($row['last_synced_at'] ?? '-')) ?></td>
                            <td><?= e((string) ($row['last_trigger'] ?? '-')) ?></td>
                            <td>
                                <?php if ($row['last_status'] === 'success'): ?>
                                    <span class="badge bg-success">Başarılı</span>
                                <?php elseif ($row['last_status'] === 'failed'): ?>
                                    <span class="badge bg-danger">Başarısız</span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e((string) ($row['last_event_count'] ?? '-')) ?></td>
                            <td>
                                <?php if (!empty($row['last_error'])): ?>
                                    <small class="text-danger" title="<?= e((string) $row['last_error_at']) ?>">
                                        <?= e($row['last_error']) ?>
                                    </small>
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

<?php end_section('content'); ?>
