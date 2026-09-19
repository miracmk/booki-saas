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
                            <?php if (($msg['role'] ?? '') === 'tool' || empty($msg['content'])) continue; ?>
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
                            <?php 
                                $changes = json_decode((string) $change['changes'], true) ?: []; 
                                $is_appt = ($change['target_table'] === 'appointments');
                                $action = $changes['action'] ?? ($is_appt ? 'create' : 'update');
                            ?>
                            <div class="ai-agent-pending-item border rounded p-3 mb-3 bg-light" data-id="<?= (int) $change['id'] ?>">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <?php if ($is_appt && $action === 'create'): ?>
                                        <span class="badge bg-primary text-white"><i class="fas fa-calendar-plus me-1"></i> Yeni Randevu</span>
                                    <?php elseif ($is_appt && $action === 'cancel'): ?>
                                        <span class="badge bg-danger text-white"><i class="fas fa-calendar-xmark me-1"></i> İptal Talebi</span>
                                    <?php elseif ($is_appt && $action === 'reschedule'): ?>
                                        <span class="badge bg-warning text-dark"><i class="fas fa-calendar-days me-1"></i> Saat Değişikliği</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark"><i class="fas fa-user-pen me-1"></i> Profil Güncelleme</span>
                                    <?php endif; ?>
                                    <small class="text-muted"><?= date('H:i, d.m.Y', strtotime($change['created_at'])) ?></small>
                                </div>

                                <?php if ($is_appt && $action === 'create'): ?>
                                    <div class="small mb-2">
                                        <div><strong>Müşteri:</strong> <?= e($changes['customer_name'] ?? 'Misafir') ?> (<?= e($changes['customer_phone'] ?? '-') ?>)</div>
                                        <div><strong>Hizmet:</strong> <?= e($changes['service_name'] ?? 'Belirtilmedi') ?></div>
                                        <div><strong>Tarih & Saat:</strong> <span class="text-primary fw-bold"><?= e($changes['start_datetime'] ?? '-') ?></span></div>
                                        <?php if (!empty($changes['notes'])): ?>
                                            <div><strong>Not:</strong> <?= e($changes['notes']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($is_appt && $action === 'cancel'): ?>
                                    <div class="small mb-2">
                                        <div><strong>Randevu ID:</strong> #<?= (int) ($change['target_id'] ?: ($changes['appointment_id'] ?? 0)) ?></div>
                                        <div class="text-danger">Müşteri randevunun iptal edilmesini talep ediyor.</div>
                                    </div>
                                <?php elseif ($is_appt && $action === 'reschedule'): ?>
                                    <div class="small mb-2">
                                        <div><strong>Randevu ID:</strong> #<?= (int) ($change['target_id'] ?: ($changes['appointment_id'] ?? 0)) ?></div>
                                        <div><strong>Yeni Tarih:</strong> <span class="text-success fw-bold"><?= e($changes['new_start_datetime'] ?? '-') ?></span></div>
                                    </div>
                                <?php else: ?>
                                    <div class="small text-muted mb-1">Müşteri #<?= (int) $change['target_id'] ?></div>
                                    <ul class="mb-1 ps-3 small">
                                        <?php foreach ($changes as $field => $value): ?>
                                            <?php if ($field === 'action') continue; ?>
                                            <li><strong><?= e($field) ?>:</strong> <?= e((string) $value) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <?php if (!empty($change['reason'])): ?>
                                    <div class="small text-muted fst-italic mb-2 p-1 bg-white rounded border">"<?= e($change['reason']) ?>"</div>
                                <?php endif; ?>

                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-success ai-agent-approve flex-grow-1"><i class="fas fa-check me-1"></i> Onayla & İşle</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger ai-agent-reject"><i class="fas fa-times me-1"></i> Reddet</button>
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
