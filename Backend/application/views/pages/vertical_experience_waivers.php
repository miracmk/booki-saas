<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @var array $waivers
 * @var array $signatures
 * @var array $tickets
 * @var array $customers
 */
$waivers = $waivers ?? [];
$signatures = $signatures ?? [];
$tickets = $tickets ?? [];
$customers = $customers ?? [];
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
                <h1 class="h3 fw-bold mb-1"><i class="fas fa-ticket-alt text-warning me-2"></i>Deneyimler, Dijital Feragatname & Biletleme</h1>
                <p class="text-muted small mb-0">Kaçış oyunları, VR simülasyonları ve atölyeler için zorunlu dijital sözleşme, feragatname imzaları ve QR bilet tarama.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-create-waiver">
                    <i class="fas fa-file-signature me-1"></i> Yeni Feragatname Şablonu
                </button>
            </div>
        </div>

        <div class="row g-4">
            <!-- LEFT: SIGNED WAIVERS & CONTRACTS -->
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-bold mb-0"><i class="fas fa-signature text-primary me-2"></i>İmzalanan Dijital Feragatnameler</h5>
                            <span class="badge bg-secondary"><?= count($signatures) ?> İmzalı Form</span>
                        </div>
                        <button class="btn btn-sm btn-outline-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modal-sign-waiver">
                            <i class="fas fa-pen-nib me-1"></i> Müşteriye İmzalat
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>İmzalayan</th>
                                    <th>Sözleşme / Feragatname</th>
                                    <th>İmza Zamanı</th>
                                    <th>IP & Onay</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($signatures)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Henüz imzalanmış feragatname kaydı yok.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($signatures as $s): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= e($s['signer_full_name'] ?? '') ?></div>
                                                <small class="text-muted"><?= e($s['signer_phone'] ?? ($s['signer_email'] ?? '-')) ?></small>
                                            </td>
                                            <td><span class="badge bg-light text-dark border"><?= e($s['waiver_title'] ?? 'Genel Feragatname') ?></span></td>
                                            <td><small><?= !empty($s['signed_at']) ? date('d.m.Y H:i', strtotime($s['signed_at'])) : '-' ?></small></td>
                                            <td>
                                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Onaylandı</span>
                                                <small class="text-muted d-block"><?= e($s['ip_address'] ?? '') ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- RIGHT: QR TICKET SCANNER & VALIDATOR -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-bold mb-0 text-white"><i class="fas fa-qrcode text-warning me-2"></i>Kapı QR Bilet Doğrulama</h5>
                            <span class="badge bg-warning text-dark">Canlı Giriş</span>
                        </div>
                        <button class="btn btn-sm btn-outline-warning fw-semibold" data-bs-toggle="modal" data-bs-target="#modal-issue-ticket">
                            <i class="fas fa-ticket-alt me-1"></i> Yeni Bilet Kes
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">Katılımcının telefonundaki QR kodu veya TKT kodunu okutarak tek tıkla doğrula ve yak:</p>
                        <form id="form-validate-ticket" class="mb-3">
                            <div class="input-group">
                                <input type="text" id="ticket-code-input" class="form-control font-monospace" placeholder="TKT-XXXX-XXXX" required autocomplete="off">
                                <button class="btn btn-warning fw-bold" type="submit" id="btn-validate-ticket"><i class="fas fa-barcode me-1"></i> Doğrula</button>
                            </div>
                        </form>
                        <div id="ticket-result-box" class="alert d-none py-2 px-3 small"></div>

                        <h6 class="fw-bold mt-4 mb-2"><i class="fas fa-list me-1 text-secondary"></i>Üretilen Etkinlik Biletleri</h6>
                        <div class="list-group list-group-flush small" style="max-height: 280px; overflow-y: auto;">
                            <?php if (empty($tickets)): ?>
                                <div class="text-muted text-center py-3">Henüz bilet üretilmedi.</div>
                            <?php else: ?>
                                <?php foreach ($tickets as $t): ?>
                                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="font-monospace fw-bold me-1 text-primary"><?= e($t['ticket_code'] ?? '') ?></span>
                                            <span><?= e(trim(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? '')) ?: 'Misafir') ?></span>
                                            <?php if (!empty($t['seat_or_slot_label'])): ?>
                                                <span class="badge bg-light text-muted border ms-1"><?= e($t['seat_or_slot_label']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?php if (($t['status'] ?? '') === 'valid'): ?>
                                                <span class="badge bg-success">Geçerli</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?= e(ucfirst($t['status'] ?? 'Kullanıldı')) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 1: YENİ FERAGATNAME ŞABLONU -->
    <div class="modal fade" id="modal-create-waiver" tabindex="-1" aria-labelledby="modal-create-waiver-label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-create-waiver-label"><i class="fas fa-file-contract text-primary me-2"></i>Yeni Dijital Feragatname & Onay Metni</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-create-waiver">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="waiver-title">Sözleşme / Feragatname Başlığı <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="waiver-title" class="form-control" placeholder="Örn: Kaçış Odası Güvenlik Kuralları & Sorumluluk Beyanı" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="waiver-content">Metin İçeriği (HTML / Metin) <span class="text-danger">*</span></label>
                            <textarea name="content_html" id="waiver-content" rows="6" class="form-control" placeholder="Katılımcıların oyuna/etkinliğe başlamadan önce onaylaması gereken kurallar, sağlık uyarıları ve feragat maddeleri..." required></textarea>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_mandatory" value="1" checked id="switch-mandatory">
                            <label class="form-check-label fw-semibold" for="switch-mandatory">Rezervasyondan önce imzalanması zorunludur</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary" id="btn-save-waiver"><i class="fas fa-save me-1"></i> Şablonu Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: YENİ FERAGATNAME İMZALAT -->
    <div class="modal fade" id="modal-sign-waiver" tabindex="-1" aria-labelledby="modal-sign-waiver-label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-sign-waiver-label"><i class="fas fa-signature text-primary me-2"></i>Yeni Feragatname İmzalat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-sign-waiver">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="sign-waiver-template">Feragatname Şablonu <span class="text-danger">*</span></label>
                            <select name="id_waivers" id="sign-waiver-template" class="form-select" required>
                                <option value="">-- Şablon Seçiniz --</option>
                                <?php foreach ($waivers as $w): ?>
                                    <option value="<?= (int) ($w['id'] ?? 0) ?>"><?= e($w['title'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="sign-waiver-fullname">İmzacı Adı Soyadı <span class="text-danger">*</span></label>
                            <input type="text" name="signer_full_name" id="sign-waiver-fullname" class="form-control" placeholder="Örn: Ahmet Yılmaz" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="sign-waiver-phone">Telefon</label>
                                <input type="tel" name="signer_phone" id="sign-waiver-phone" class="form-control" placeholder="05XX XXX XX XX">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="sign-waiver-email">E-posta</label>
                                <input type="email" name="signer_email" id="sign-waiver-email" class="form-control" placeholder="ornek@email.com">
                            </div>
                        </div>
                        <div class="form-check form-switch p-3 bg-light rounded border">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="digital_ack" id="switch-sign-ack" required>
                            <label class="form-check-label fw-semibold text-dark" for="switch-sign-ack">
                                Yukarıdaki güvenlik ve sorumluluk beyanını okudum, riskleri kabul ediyorum.
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary" id="btn-submit-sign"><i class="fas fa-file-signature me-1"></i> İmzayı Kaydet & Onayla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 3: BİLET KES / ÜRET -->
    <div class="modal fade" id="modal-issue-ticket" tabindex="-1" aria-labelledby="modal-issue-ticket-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-issue-ticket-label"><i class="fas fa-ticket-alt text-warning me-2"></i>Bilet Kes / Üret</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-issue-ticket">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="issue-ticket-customer">Danışan / Müşteri <span class="text-danger">*</span></label>
                            <select name="id_users_customer" id="issue-ticket-customer" class="form-select" required>
                                <option value="">-- Müşteri Seçiniz --</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= (int) ($c['id'] ?? 0) ?>">
                                        <?= e(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''))) ?: 'Müşteri #' . (int) ($c['id'] ?? 0) ?>
                                        <?= !empty($c['phone_number']) ? ' (' . e($c['phone_number']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="issue-ticket-appointment">Randevu / Seans <small class="text-muted fw-normal">(İsteğe bağlı)</small></label>
                            <select name="id_appointments" id="issue-ticket-appointment" class="form-select">
                                <option value="">-- Randevu / Seans Seçiniz (İsteğe bağlı) --</option>
                                <?php if (!empty($appointments)): ?>
                                    <?php foreach ($appointments as $a): ?>
                                        <option value="<?= (int) ($a['id'] ?? 0) ?>">
                                            #<?= (int) ($a['id'] ?? 0) ?> - <?= e($a['service_name'] ?? 'Deneyim') ?> (<?= !empty($a['start_datetime']) ? date('d.m.Y H:i', strtotime($a['start_datetime'])) : '-' ?>) - <?= e(trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? ''))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold" for="issue-ticket-type">Bilet Türü <span class="text-danger">*</span></label>
                                <select name="ticket_type" id="issue-ticket-type" class="form-select" required>
                                    <option value="standard">Standart Giriş (standard)</option>
                                    <option value="vip">VIP Deneyim (vip)</option>
                                    <option value="student">Öğrenci / İndirimli (student)</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold" for="issue-ticket-price">Bilet Ücreti (TL)</label>
                                <input type="number" step="0.01" min="0" name="price" id="issue-ticket-price" class="form-control" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-warning fw-bold" id="btn-submit-ticket"><i class="fas fa-qrcode me-1"></i> Bilet Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    const formCreateWaiver = document.getElementById('form-create-waiver');
    if (formCreateWaiver) {
        formCreateWaiver.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btnSave = document.getElementById('btn-save-waiver');
            const originalBtnHtml = btnSave ? btnSave.innerHTML : '';
            if (btnSave) {
                btnSave.disabled = true;
                btnSave.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Kaydediliyor...';
            }

            const fd = new FormData(this);
            const data = Object.fromEntries(fd.entries());
            data.is_mandatory = document.getElementById('switch-mandatory').checked ? 1 : 0;

            const requestHeaders = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };

            try {
                let res;
                try {
                    res = await fetch('<?= site_url('verticals/save_digital_waiver') ?>', {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify(data)
                    });
                    if (!res.ok && (res.status === 404 || res.status === 405)) {
                        throw new Error('Fallback endpoint required');
                    }
                } catch (fallbackErr) {
                    res = await fetch('<?= site_url('api/v1/verticals/experience/waivers') ?>', {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify(data)
                    });
                }

                const json = await res.json().catch(() => ({}));
                if (res.ok && (json.success || json.waiver_id)) {
                    alert('Feragatname şablonu kaydedildi!');
                    window.location.reload();
                } else {
                    alert('Hata: ' + escapeHtml(json.error || json.message || 'İşlem başarısız'));
                }
            } catch (err) {
                alert('Ağ hatası: ' + escapeHtml(err.message || 'Bağlantı kurulamadı.'));
            } finally {
                if (btnSave) {
                    btnSave.disabled = false;
                    btnSave.innerHTML = originalBtnHtml;
                }
            }
        });
    }

    const formSignWaiver = document.getElementById('form-sign-waiver');
    if (formSignWaiver) {
        formSignWaiver.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btnSubmit = document.getElementById('btn-submit-sign');
            const originalBtnHtml = btnSubmit ? btnSubmit.innerHTML : '';
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> İmzalanıyor...';
            }

            const fd = new FormData(this);
            const data = Object.fromEntries(fd.entries());
            data.id_waivers = parseInt(data.id_waivers, 10);
            if (!data.signature_data) {
                data.signature_data = 'DIGITAL_ACK_ACCEPTED_' + new Date().toISOString();
            }

            const requestHeaders = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };

            try {
                let res;
                try {
                    res = await fetch('<?= site_url('verticals/sign_digital_waiver') ?>', {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify(data)
                    });
                    if (!res.ok && (res.status === 404 || res.status === 405)) {
                        throw new Error('Fallback endpoint required');
                    }
                } catch (fallbackErr) {
                    res = await fetch('<?= site_url('api/v1/verticals/experience/waivers/sign') ?>', {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify(data)
                    });
                }

                const json = await res.json().catch(() => ({}));
                if (res.ok && (json.success || json.signature_id)) {
                    alert('Feragatname başarıyla imzalandı!');
                    window.location.reload();
                } else {
                    alert('Hata: ' + escapeHtml(json.error || json.message || 'İşlem başarısız'));
                }
            } catch (err) {
                alert('Ağ hatası: ' + escapeHtml(err.message || 'Bağlantı kurulamadı.'));
            } finally {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalBtnHtml;
                }
            }
        });
    }

    const formIssueTicket = document.getElementById('form-issue-ticket');
    if (formIssueTicket) {
        formIssueTicket.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btnSubmit = document.getElementById('btn-submit-ticket');
            const originalBtnHtml = btnSubmit ? btnSubmit.innerHTML : '';
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Üretiliyor...';
            }

            const fd = new FormData(this);
            const data = Object.fromEntries(fd.entries());
            data.id_users_customer = parseInt(data.id_users_customer, 10);
            data.customer_id = data.id_users_customer;
            if (data.id_appointments) {
                data.id_appointments = parseInt(data.id_appointments, 10);
                data.appointment_id = data.id_appointments;
            } else {
                data.id_appointments = null;
                data.appointment_id = null;
            }
            const ticketType = data.ticket_type || 'standard';
            data.seat_label = ticketType.toUpperCase() + (data.price ? ' (' + data.price + ' TL)' : '');

            const requestHeaders = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };

            try {
                let res;
                try {
                    res = await fetch('<?= site_url('verticals/issue_event_ticket') ?>', {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify(data)
                    });
                    if (!res.ok && (res.status === 404 || res.status === 405)) {
                        throw new Error('Fallback endpoint required');
                    }
                } catch (fallbackErr) {
                    res = await fetch('<?= site_url('api/v1/verticals/experience/tickets') ?>', {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify(data)
                    });
                }

                const json = await res.json().catch(() => ({}));
                if (res.ok && (json.success || json.ticket_code || json.id)) {
                    alert('Bilet oluşturuldu! Kod: ' + escapeHtml(json.ticket_code || (json.ticket && json.ticket.ticket_code) || ''));
                    window.location.reload();
                } else {
                    alert('Hata: ' + escapeHtml(json.error || json.message || 'İşlem başarısız'));
                }
            } catch (err) {
                alert('Ağ hatası: ' + escapeHtml(err.message || 'Bağlantı kurulamadı.'));
            } finally {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalBtnHtml;
                }
            }
        });
    }

    const formValidateTicket = document.getElementById('form-validate-ticket');
    if (formValidateTicket) {
        formValidateTicket.addEventListener('submit', async function(e) {
            e.preventDefault();
            const input = document.getElementById('ticket-code-input');
            const code = input ? input.value.trim() : '';
            const box = document.getElementById('ticket-result-box');
            if (!box) return;

            const btnValidate = document.getElementById('btn-validate-ticket');
            const originalBtnHtml = btnValidate ? btnValidate.innerHTML : '';
            if (btnValidate) {
                btnValidate.disabled = true;
                btnValidate.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Doğrulanıyor...';
            }

            box.className = 'alert alert-info py-2 px-3 small';
            box.textContent = 'Doğrulanıyor...';
            box.classList.remove('d-none');

            const payload = { ticket_code: code, code: code };
            const requestHeaders = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };

            try {
                let res;
                try {
                    res = await fetch('<?= site_url('verticals/validate_event_ticket') ?>', {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify(payload)
                    });
                    if (!res.ok && (res.status === 404 || res.status === 405)) {
                        throw new Error('Fallback endpoint required');
                    }
                } catch (fallbackErr) {
                    res = await fetch('<?= site_url('api/v1/verticals/experience/tickets/validate') ?>', {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify(payload)
                    });
                }

                const json = await res.json().catch(() => ({}));
                if (json.valid) {
                    box.className = 'alert alert-success py-2 px-3 small';
                    const safeMsg = escapeHtml(json.message || 'Bilet başarıyla doğrulandı ve girişe izin verildi.');
                    box.innerHTML = '<strong><i class="fas fa-check-circle me-1"></i>BİLET GEÇERLİ!</strong><br>' + safeMsg;
                    if (input) input.value = '';
                } else {
                    box.className = 'alert alert-danger py-2 px-3 small';
                    const safeErr = escapeHtml(json.message || json.error || 'Geçersiz bilet kodu.');
                    box.innerHTML = '<strong><i class="fas fa-times-circle me-1"></i>GEÇERSİZ BİLET!</strong><br>' + safeErr;
                }
            } catch (err) {
                box.className = 'alert alert-danger py-2 px-3 small';
                box.innerHTML = '<strong><i class="fas fa-exclamation-triangle me-1"></i>Ağ Hatası:</strong> ' + escapeHtml(err.message || 'Bağlantı kurulamadı.');
            } finally {
                if (btnValidate) {
                    btnValidate.disabled = false;
                    btnValidate.innerHTML = originalBtnHtml;
                }
            }
        });
    }
    </script>
</body>
</html>
