<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="ai-agent-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-light mb-0">AI Asistan</h5>
                    <button type="button" id="ai-agent-reset" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-rotate-left me-1"></i> Sohbeti sıfırla
                    </button>
                </div>
                <div class="card-body">
                    <p class="form-text text-muted">
                        Müşteri arama, randevu geçmişi gibi küçük sorular sorabilirsiniz. Bir kaydı
                        güncellemek isterseniz asistan doğrudan değiştirmez - öneriyi sağdaki
                        "Onay Bekleyenler" listesine ekler, siz onaylayana kadar hiçbir şey değişmez.
                    </p>

                    <div id="ai-agent-log" class="mb-3" style="max-height: 420px; overflow-y: auto;">
                        <?php foreach (vars('history') ?? [] as $msg): ?>
                            <div class="ai-agent-msg ai-agent-msg-<?= e($msg['role']) ?> mb-2">
                                <strong><?= $msg['role'] === 'user' ? 'Siz' : 'Asistan' ?>:</strong>
                                <span><?= nl2br(e($msg['content'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty(vars('history'))): ?>
                            <span class="text-muted">Henüz bir mesaj yok. Örnek: "Ahmet Yılmaz'ı ara" veya "12 numaralı müşterinin randevularını göster".</span>
                        <?php endif; ?>
                    </div>

                    <form id="ai-agent-form" class="d-flex gap-2">
                        <input type="text" id="ai-agent-input" class="form-control" placeholder="Bir şey sorun..." autocomplete="off">
                        <button type="submit" class="btn btn-primary" data-busy-label="Düşünüyor...">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="fw-light mb-0">Onay Bekleyenler</h6>
                </div>
                <div class="card-body">
                    <div id="ai-agent-pending-list">
                        <?php if (empty(vars('pending'))): ?>
                            <span class="text-muted">Bekleyen değişiklik yok.</span>
                        <?php endif; ?>
                        <?php foreach (vars('pending') ?? [] as $change): ?>
                            <?php $changes = json_decode((string) $change['changes'], true) ?: []; ?>
                            <div class="ai-agent-pending-item border rounded p-2 mb-2" data-id="<?= (int) $change['id'] ?>">
                                <div class="small text-muted mb-1">Müşteri #<?= (int) $change['target_id'] ?></div>
                                <ul class="mb-1 ps-3">
                                    <?php foreach ($changes as $field => $value): ?>
                                        <li><strong><?= e($field) ?>:</strong> <?= e((string) $value) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if (!empty($change['reason'])): ?>
                                    <div class="small text-muted fst-italic mb-2">"<?= e($change['reason']) ?>"</div>
                                <?php endif; ?>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-success ai-agent-approve">Onayla</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger ai-agent-reject">Reddet</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php section('scripts'); ?>
<script src="<?= asset_url('assets/js/pages/ai_agent.js') ?>"></script>
<?php end_section('scripts'); ?>

<?php end_section('content'); ?>
