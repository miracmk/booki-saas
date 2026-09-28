<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="follow-up-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div class="col-sm-9">
            <div class="d-flex justify-content-between align-items-center border-bottom py-3 mb-3">
                <div>
                    <h4 class="fw-light mb-0">Hizmet Sonrası Takip Motoru</h4>
                    <small class="text-muted">
                        Sektör Ailesi: <span class="badge bg-primary text-uppercase"><?= e(vars('industry_family')) ?></span> | 
                        Blueprint: <span class="badge bg-secondary text-uppercase"><?= e(vars('blueprint_type')) ?></span>
                    </small>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createRuleModal">
                    <i class="fas fa-plus me-1"></i> Yeni Kural Tanımla
                </button>
            </div>

            <!-- ============ Stats Counters ============ -->
            <?php $stats = vars('stats') ?: []; ?>
            <div class="row g-2 mb-4">
                <div class="col-md-2 col-4">
                    <div class="card p-2 text-center bg-light border-0 shadow-sm">
                        <small class="text-muted d-block">Planlanan</small>
                        <h4 class="mb-0 text-primary fw-bold"><?= (int)($stats['scheduled'] ?? 0) ?></h4>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="card p-2 text-center bg-light border-0 shadow-sm">
                        <small class="text-muted d-block">İletilen</small>
                        <h4 class="mb-0 text-success fw-bold"><?= (int)($stats['sent'] ?? 0) ?></h4>
                    </div>
                </div>
                <div class="col-md-3 col-4">
                    <div class="card p-2 text-center bg-light border-0 shadow-sm">
                        <small class="text-muted d-block">Rebook Önleme</small>
                        <h4 class="mb-0 text-info fw-bold"><?= (int)($stats['cancelled_rebooked'] ?? 0) ?></h4>
                        <small class="text-muted" style="font-size: 10px;">Anti-Spam</small>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card p-2 text-center bg-light border-0 shadow-sm">
                        <small class="text-muted d-block">Opt-Out</small>
                        <h4 class="mb-0 text-secondary fw-bold"><?= (int)($stats['cancelled_opt_out'] ?? 0) ?></h4>
                        <small class="text-muted" style="font-size: 10px;">RED/DUR</small>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card p-2 text-center bg-light border-0 shadow-sm">
                        <small class="text-muted d-block">Müşteri Yanıtları</small>
                        <h4 class="mb-0 text-warning fw-bold"><?= (int)($stats['responses'] ?? 0) ?></h4>
                        <small class="text-muted" style="font-size: 10px;">NPS & Reaksiyon</small>
                    </div>
                </div>
            </div>

            <!-- ============ Business Logic Rules Banner ============ -->
            <div class="card mb-4 border-info">
                <div class="card-body bg-light-info py-2 px-3">
                    <div class="row align-items-center">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <strong><i class="fas fa-shield-alt text-primary me-1"></i> Anti-Spam (Rebook)</strong>
                            <p class="mb-0 small text-muted">Geleceğe aktif randevusu olan müşteriye randevu yenileme mesajı gitmez.</p>
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <strong><i class="fas fa-moon text-indigo me-1"></i> Sessiz Saatler</strong>
                            <p class="mb-0 small text-muted">21:00 - 09:00 arası takip durur, ertesi sabah saat 09:30'a ötelenir.</p>
                        </div>
                        <div class="col-md-4">
                            <strong><i class="fas fa-user-slash text-danger me-1"></i> KVKK Opt-Out</strong>
                            <p class="mb-0 small text-muted">Müşteri RED/DUR yazarsa tıbbi reaksiyon hariç bildirimler kesilir.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ Follow-Up Rules List ============ -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="fw-light mb-0">Aktif Takip Kuralları</h5>
                    <span class="badge bg-secondary"><?= count(vars('rules') ?: []) ?> Kural</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kural Tipi</th>
                                <th>Gecikme (Zamanlama)</th>
                                <th>WhatsApp Şablonu</th>
                                <th>Durum</th>
                                <th class="text-end">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ((vars('rules') ?: []) as $rule): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">
                                            <?php
                                                $type_labels = [
                                                    'reaction_check' => '<i class="fas fa-heartbeat text-danger me-1"></i> Akut Reaksiyon Kontrolü',
                                                    'aftercare' => '<i class="fas fa-hand-holding-medical text-primary me-1"></i> Aftercare (Bakım Kılavuzu)',
                                                    'retention_rebook' => '<i class="fas fa-sync text-success me-1"></i> Akıllı Randevu Yenileme',
                                                    'review_request' => '<i class="fas fa-star text-warning me-1"></i> NPS & Değerlendirme',
                                                    'asset_delivery' => '<i class="fas fa-file-invoice text-info me-1"></i> Dijital Belge / Teslim',
                                                    'diet_form' => '<i class="fas fa-clipboard-list text-purple me-1"></i> Form & Beslenme Günlüğü',
                                                    'routine_check' => '<i class="fas fa-calendar-check text-secondary me-1"></i> Rutin Kontrol Çağrısı',
                                                ];
                                                echo $type_labels[$rule['rule_type']] ?? e($rule['rule_type']);
                                            ?>
                                        </div>
                                        <small class="text-muted"><?= e($rule['blueprint_type']) ?> | <?= e($rule['industry_family']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="far fa-clock me-1 text-primary"></i> <?= e($rule['trigger_delay_interval']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code><?= e($rule['whatsapp_template_name']) ?></code>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input rule-toggle" type="checkbox" role="switch"
                                                   data-rule-id="<?= e($rule['id']) ?>" <?= !empty($rule['is_active']) ? 'checked' : '' ?>>
                                            <label class="form-check-label small text-muted"><?= !empty($rule['is_active']) ? 'Açık' : 'Kapalı' ?></label>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary edit-rule-btn"
                                                data-rule='<?= json_encode($rule) ?>'>
                                            <i class="fas fa-edit"></i> Düzenle
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty(vars('rules'))): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        Bu sektör için tanımlanmış takip kuralı bulunamadı.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ============ Recent Dispatches Log ============ -->
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="fw-light mb-0">Son Takip Görevleri & Yanıtlar</h5>
                    <span class="text-muted small">Son 30 Görev</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th>ID / Randevu</th>
                                <th>Kural & Şablon</th>
                                <th>Planlanan / Gönderilen</th>
                                <th>Durum</th>
                                <th>Müşteri Yanıtı / NPS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ((vars('recent_dispatches') ?: []) as $disp): ?>
                                <tr>
                                    <td>
                                        <strong>#<?= e($disp['booking_id']) ?></strong><br>
                                        <small class="text-muted">Müşteri #<?= e($disp['customer_id']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= e($disp['rule_type'] ?? 'takip') ?></span><br>
                                        <small class="text-muted"><?= e($disp['whatsapp_template_name'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <div><i class="far fa-calendar-alt me-1 text-muted"></i><?= e($disp['scheduled_for']) ?></div>
                                        <?php if (!empty($disp['sent_at'])): ?>
                                            <small class="text-success"><i class="fas fa-check me-1"></i><?= e($disp['sent_at']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                            $status_badges = [
                                                'SCHEDULED' => '<span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i>Bekliyor</span>',
                                                'SENT' => '<span class="badge bg-success"><i class="fas fa-check-double me-1"></i>İletildi</span>',
                                                'CANCELLED_REBOOKED' => '<span class="badge bg-info"><i class="fas fa-calendar-check me-1"></i>Rebook Edildi (İptal)</span>',
                                                'CANCELLED_OPT_OUT' => '<span class="badge bg-secondary"><i class="fas fa-ban me-1"></i>Opt-Out (RED)</span>',
                                                'FAILED' => '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Hata</span>',
                                            ];
                                            echo $status_badges[$disp['status']] ?? ('<span class="badge bg-light text-dark">' . e($disp['status']) . '</span>');
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($disp['response_decoded'])): ?>
                                            <?php $resp = $disp['response_decoded']; ?>
                                            <?php if (isset($resp['nps_score'])): ?>
                                                <span class="badge bg-warning text-dark">
                                                    ⭐ <?= (int)$resp['nps_score'] ?>/5 Puan
                                                </span>
                                            <?php elseif (isset($resp['choice'])): ?>
                                                <span class="badge <?= $resp['choice'] == 2 ? 'bg-danger' : 'bg-success' ?>">
                                                    <?= $resp['choice'] == 2 ? '🚨 Acil Hekim Danışması' : '✅ İyiyim (Sorun Yok)' ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark"><?= e(json_encode($resp)) ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty(vars('recent_dispatches'))): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        Henüz yürütülmüş bir takip görevi bulunmuyor.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ============ Edit Rule Modal ============ -->
<div class="modal fade" id="editRuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kuralı Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <form id="editRuleForm">
                    <input type="hidden" id="edit_rule_id" name="rule_id">
                    <div class="mb-3">
                        <label class="form-label">Tetikleme Gecikmesi (Interval)</label>
                        <input type="text" class="form-control" id="edit_trigger_delay_interval" name="trigger_delay_interval"
                               placeholder="ör: 24 hours, 21 days, 45 minutes">
                        <small class="form-text text-muted">Örnek: <code>15 minutes</code>, <code>2 hours</code>, <code>24 hours</code>, <code>21 days</code></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">WhatsApp Şablon Adı</label>
                        <input type="text" class="form-control" id="edit_whatsapp_template_name" name="whatsapp_template_name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Özel Mesaj Metni (Opsiyonel)</label>
                        <textarea class="form-control" id="edit_message_copy" name="message_copy" rows="3"
                                  placeholder="Varsayılan şablon metnini değiştirmek isterseniz girin..."></textarea>
                        <small class="form-text text-muted">Değişkenler: <code>{{customer_name}}</code>, <code>{{service_name}}</code>, <code>{{provider_name}}</code>, <code>{{booking_url}}</code></small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="saveRuleBtn">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- ============ Create Rule Modal ============ -->
<div class="modal fade" id="createRuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Takip Kuralı Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <form id="createRuleForm">
                    <div class="mb-3">
                        <label class="form-label">Kural Tipi</label>
                        <select class="form-select" id="create_rule_type" name="rule_type">
                            <option value="review_request">NPS & Değerlendirme İsteği</option>
                            <option value="aftercare">Aftercare (Bakım Kılavuzu)</option>
                            <option value="retention_rebook">Akıllı Randevu Yenileme (Retention)</option>
                            <option value="reaction_check">Akut Reaksiyon Kontrolü (Medikal)</option>
                            <option value="asset_delivery">Dijital Varlık Teslimi (Rapor/Fotoğraf)</option>
                            <option value="diet_form">Uyum & Form Takibi</option>
                            <option value="routine_check">Rutin Kontrol Çağrısı (6 Ay)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gecikme Süresi (Interval)</label>
                        <input type="text" class="form-control" id="create_trigger_delay_interval" name="trigger_delay_interval"
                               placeholder="ör: 2 hours, 24 hours, 21 days">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">WhatsApp Şablon Adı</label>
                        <input type="text" class="form-control" id="create_whatsapp_template_name" name="whatsapp_template_name"
                               placeholder="ör: custom_follow_up_msg">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Özel Mesaj Metni</label>
                        <textarea class="form-control" id="create_message_copy" name="message_copy" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="submitCreateRuleBtn">Oluştur</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const routes = window.vars ? window.vars.routes : {};

    // Toggle active state
    document.querySelectorAll('.rule-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const ruleId = this.dataset.ruleId;
            const isActive = this.checked ? 1 : 0;
            const label = this.nextElementSibling;

            fetch(routes.toggle_rule, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ rule_id: ruleId, is_active: isActive })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    label.textContent = isActive ? 'Açık' : 'Kapalı';
                } else {
                    alert(data.message || 'Hata oluştu');
                }
            })
            .catch(err => {
                console.error(err);
                alert('İşlem sırasında bir hata oluştu.');
            });
        });
    });

    // Open Edit Modal
    const editModalEl = document.getElementById('editRuleModal');
    const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;

    document.querySelectorAll('.edit-rule-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const rule = JSON.parse(this.dataset.rule);
            document.getElementById('edit_rule_id').value = rule.id;
            document.getElementById('edit_trigger_delay_interval').value = rule.trigger_delay_interval;
            document.getElementById('edit_whatsapp_template_name').value = rule.whatsapp_template_name;

            let schema = rule.dynamic_payload_schema_decoded || {};
            document.getElementById('edit_message_copy').value = schema.mesaj || schema.soru || '';

            editModal.show();
        });
    });

    // Save edited rule
    document.getElementById('saveRuleBtn').addEventListener('click', function() {
        const ruleId = document.getElementById('edit_rule_id').value;
        const interval = document.getElementById('edit_trigger_delay_interval').value;
        const template = document.getElementById('edit_whatsapp_template_name').value;
        const copy = document.getElementById('edit_message_copy').value;

        fetch(routes.save_rule, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                rule_id: ruleId,
                trigger_delay_interval: interval,
                whatsapp_template_name: template,
                message_copy: copy
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Kayıt başarısız');
            }
        });
    });

    // Submit Create Custom Rule
    document.getElementById('submitCreateRuleBtn').addEventListener('click', function() {
        const ruleType = document.getElementById('create_rule_type').value;
        const interval = document.getElementById('create_trigger_delay_interval').value;
        const template = document.getElementById('create_whatsapp_template_name').value;
        const copy = document.getElementById('create_message_copy').value;

        if (!interval || !template) {
            alert('Lütfen gecikme süresi ve şablon adını doldurun.');
            return;
        }

        fetch(routes.create_rule, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                rule_type: ruleType,
                trigger_delay_interval: interval,
                whatsapp_template_name: template,
                message_copy: copy
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Oluşturma başarısız');
            }
        });
    });
});
</script>

<?php end_section('content'); ?>
