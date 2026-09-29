<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @var array $matches
 * @var array $stations
 * @var array $checkins
 * @var array $customers
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?php $this->load->view('components/backend_head'); ?>
    <title><?= e(vars('page_title')) ?> - BooKi</title>
    <style>
    @keyframes turnstileBadgeIn {
        0% {
            opacity: 0;
            transform: translateY(-8px) scale(0.97);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    .turnstile-animated {
        animation: turnstileBadgeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    .turnstile-pulse-success {
        box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.35);
        animation: turnstileBadgeIn 0.35s ease-out, turnstileGlowGreen 2.2s infinite;
    }
    .turnstile-pulse-danger {
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.35);
        animation: turnstileBadgeIn 0.35s ease-out, turnstileGlowRed 2.2s infinite;
    }
    @keyframes turnstileGlowGreen {
        0% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(25, 135, 84, 0); }
        100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
    }
    @keyframes turnstileGlowRed {
        0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }
    </style>
</head>
<body class="backend-body">
    <?php $this->load->view('components/backend_header'); ?>

    <div class="container-fluid py-4 px-md-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1"><i class="fas fa-table-tennis text-success me-2"></i>Kortlar, Açık Maçlar & Turnike Geçiş</h1>
                <p class="text-muted small mb-0">Padel, tenis ve halı saha açık maçları, oyuncu eşleştirme (matchmaking) ve donanım turnike erişim kontrolü.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-create-match">
                    <i class="fas fa-plus me-1"></i> Yeni Açık Maç Oluştur
                </button>
            </div>
        </div>

        <div class="row g-4">
            <!-- LEFT: OPEN MATCHES & MATCHMAKING -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="fas fa-users text-primary me-2"></i>Aktif Açık Maçlar (Matchmaking)</h5>
                        <span class="badge bg-success"><?= count($matches) ?> Maç Açık</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($matches)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-calendar-times fa-3x mb-2 text-secondary"></i>
                                <p>Şu anda planlanmış açık maç bulunmuyor.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($matches as $m): ?>
                                    <div class="list-group-item p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h5 class="fw-bold mb-1"><?= e($m['title']) ?></h5>
                                                <span class="badge bg-dark me-1"><?= strtoupper(e($m['sport_type'])) ?></span>
                                                <span class="badge bg-secondary me-1">Kort: <?= e($m['court_name'] ?: 'Belirlenmedi') ?></span>
                                                <span class="badge bg-info text-dark">Seviye: <?= e($m['level_required']) ?></span>
                                            </div>
                                            <div class="text-end">
                                                <h5 class="fw-bold text-success mb-0">₺<?= number_format((float) $m['price_per_player'], 2) ?> <small class="text-muted fs-6">/ oyuncu</small></h5>
                                                <small class="text-muted"><?= date('d.m.Y H:i', strtotime($m['start_datetime'])) ?></small>
                                            </div>
                                        </div>

                                        <!-- CAPACITY PROGRESS -->
                                        <?php
                                        $cur = (int) $m['current_players'];
                                        $max = (int) $m['max_players'];
                                        $pct = round(($cur / max(1, $max)) * 100);
                                        ?>
                                        <div class="d-flex justify-content-between small text-muted mb-1">
                                            <span>Katılımcılar (<?= $cur ?> / <?= $max ?>)</span>
                                            <span>%<?= $pct ?> Dolu</span>
                                        </div>
                                        <div class="progress mb-3" style="height: 8px;">
                                            <div class="progress-bar bg-success" style="width: <?= $pct ?>%;"></div>
                                        </div>

                                        <!-- PARTICIPANTS LIST -->
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <div class="d-flex gap-1 flex-wrap">
                                                <?php foreach ($m['participants'] as $p): ?>
                                                    <span class="badge bg-light text-dark border">
                                                        <i class="fas fa-user me-1 text-primary"></i><?= e($p['first_name'] . ' ' . $p['last_name']) ?> (<?= e($p['team'] ?: 'Takımsız') ?>)
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php if ($cur < $max): ?>
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-success btn-join-match"
                                                    data-id="<?= (int) $m['id'] ?>"
                                                    data-title="<?= e($m['title']) ?>"
                                                    data-sport="<?= e($m['sport_type']) ?>"
                                                    data-price="<?= number_format((float) $m['price_per_player'], 2) ?>"
                                                    data-cur="<?= $cur ?>"
                                                    data-max="<?= $max ?>">
                                                    <i class="fas fa-user-plus me-1"></i> Oyuncu Ekle
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT: LIVE TURNSTILE / ACCESS MONITOR -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="fas fa-door-closed text-warning me-2"></i>Turnike Erişim Simülatörü</h5>
                        <span class="badge bg-warning text-dark">Röle Kontrol</span>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">Donanım turnikelerinden veya QR okuyucudan gelen token kodunu anında doğrulayıp röle tetikleyin:</p>
                        <form id="form-turnstile-test" class="mb-3">
                            <div class="input-group">
                                <input type="text" id="turnstile-token-input" class="form-control" placeholder="Token veya CUST:1 / MEMB:1" required>
                                <button class="btn btn-dark" type="submit" id="btn-turnstile-submit"><i class="fas fa-key me-1"></i> Okut</button>
                            </div>
                        </form>
                        <div id="turnstile-result-box" class="alert d-none py-2 px-3 small"></div>

                        <h6 class="fw-bold mt-4 mb-2"><i class="fas fa-history me-1 text-secondary"></i>Son Turnike Geçişleri</h6>
                        <div id="turnstile-checkins-list" class="list-group list-group-flush small" style="max-height: 280px; overflow-y: auto;">
                            <?php if (empty($checkins)): ?>
                                <div class="text-muted text-center py-3" id="turnstile-empty-hint">Henüz geçiş kaydı yok.</div>
                            <?php else: ?>
                                <?php foreach ($checkins as $ck): ?>
                                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge <?= $ck['status'] === 'completed' ? 'bg-success' : 'bg-danger' ?> me-1">
                                                <?= $ck['status'] === 'completed' ? 'İzin Verildi' : 'Engellendi' ?>
                                            </span>
                                            <span>Müşteri #<?= (int) $ck['id_users_customer'] ?></span>
                                        </div>
                                        <span class="text-muted"><?= date('H:i:s', strtotime($ck['entry_timestamp'] ?: $ck['created_at'])) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: YENİ AÇIK MAÇ -->
    <div class="modal fade" id="modal-create-match" tabindex="-1" aria-labelledby="modalCreateMatchLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCreateMatchLabel"><i class="fas fa-trophy text-warning me-2"></i>Yeni Açık Maç Oluştur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-create-match">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="create-match-title">Maç Başlığı</label>
                            <input type="text" id="create-match-title" name="title" class="form-control" placeholder="Örn: Hafta Sonu Padel Karışık Maç" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="create-match-sport-type">Spor Türü</label>
                                <select name="sport_type" id="create-match-sport-type" class="form-select">
                                    <option value="padel">Padel</option>
                                    <option value="tennis">Tenis</option>
                                    <option value="football">Halı Saha (Futbol)</option>
                                    <option value="basketball">Basketbol</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="create-match-level">Seviye</label>
                                <select name="level_required" id="create-match-level" class="form-select">
                                    <option value="all">Her Seviye</option>
                                    <option value="beginner">Başlangıç (1.0 - 2.5)</option>
                                    <option value="intermediate">Orta (3.0 - 4.0)</option>
                                    <option value="advanced">İleri / Pro (4.5+)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="create-match-start">Başlangıç Zamanı</label>
                                <input type="datetime-local" id="create-match-start" name="start_datetime" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="create-match-end">Bitiş Zamanı</label>
                                <input type="datetime-local" id="create-match-end" name="end_datetime" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="create-match-court">Kort / Saha</label>
                                <select name="id_stations" id="create-match-court" class="form-select">
                                    <?php foreach ($stations as $st): ?>
                                        <option value="<?= (int) $st['id'] ?>"><?= e($st['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="create-match-max-players">Kişi Sayısı / Kapasite</label>
                                <input type="number" id="create-match-max-players" name="max_players" class="form-control" min="2" max="50" value="4" required>
                                <div class="form-text small" id="create-match-capacity-hint"><i class="fas fa-info-circle me-1"></i>Padel için önerilen: 4 oyuncu (2v2)</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold" for="create-match-price">Kişi Başı Ücret (TL)</label>
                                <input type="number" step="0.01" id="create-match-price" name="price_per_player" class="form-control" placeholder="250.00" value="0.00">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary" id="btn-create-match-submit"><i class="fas fa-check me-1"></i> Maçı Başlat & Aç</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: MAÇA OYUNCU EKLE / KATIL -->
    <div class="modal fade" id="modal-join-match" tabindex="-1" aria-labelledby="modalJoinMatchLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalJoinMatchLabel"><i class="fas fa-user-plus text-success me-2"></i>Maça Oyuncu Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <form id="form-join-match">
                    <input type="hidden" name="match_id" id="join-match-id" value="">
                    <div class="modal-body">
                        <!-- MATCH INFO SUMMARY BANNER -->
                        <div class="alert alert-light border mb-3 p-3">
                            <div class="fw-bold mb-1 fs-6" id="join-match-title-display">-</div>
                            <div class="d-flex justify-content-between align-items-center small text-muted">
                                <div>
                                    <span class="badge bg-dark me-1" id="join-match-sport-display">-</span>
                                    <span class="badge bg-secondary" id="join-match-capacity-display">-</span>
                                </div>
                                <div class="fw-bold text-success fs-6" id="join-match-fee-display">₺0.00</div>
                            </div>
                        </div>

                        <!-- SELECT CUSTOMER / PLAYER -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="join-customer-select">Danışan / Müşteri / Oyuncu <span class="text-danger">*</span></label>
                            <select name="id_users_customer" id="join-customer-select" class="form-select" required>
                                <option value="">Oyuncu seçiniz...</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>">
                                        <?= e($c['first_name'] . ' ' . $c['last_name']) ?> <?= !empty($c['phone_number']) ? '(' . e($c['phone_number']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <!-- SELECT TEAM -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="join-team-select">Takım</label>
                                <select name="team" id="join-team-select" class="form-select">
                                    <option value="A Takımı">A Takımı</option>
                                    <option value="B Takımı">B Takımı</option>
                                    <option value="Ev Sahibi">Ev Sahibi</option>
                                    <option value="Deplasman">Deplasman</option>
                                    <option value="Takımsız">Takımsız / Bireysel</option>
                                </select>
                            </div>

                            <!-- SELECT SKILL LEVEL -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="join-skill-level-select">Seviye</label>
                                <select name="skill_level" id="join-skill-level-select" class="form-select">
                                    <option value="beginner">Başlangıç (Beginner)</option>
                                    <option value="intermediate" selected>Orta (Intermediate)</option>
                                    <option value="advanced">İleri / Pro (Advanced)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-success" id="btn-submit-join">
                            <i class="fas fa-check me-1"></i> Maça Katıl / Ekle
                        </button>
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

    // 1. DYNAMIC CAPACITY SUGGESTIONS FOR SPORT TYPES
    const sportTypeSelect = document.getElementById('create-match-sport-type');
    const maxPlayersInput = document.getElementById('create-match-max-players');
    const capacityHint = document.getElementById('create-match-capacity-hint');

    const sportCapacitySuggestions = {
        padel: { default: 4, hint: 'Padel için önerilen: 4 oyuncu (2v2)' },
        tennis: { default: 4, hint: 'Tenis için önerilen: 2 (Tekler) veya 4 oyuncu (Çiftler)' },
        football: { default: 14, hint: 'Halı Saha için önerilen: 14 oyuncu (7v7)' },
        basketball: { default: 10, hint: 'Basketbol için önerilen: 10 oyuncu (5v5) veya 6 (3v3)' }
    };

    function updateCapacitySuggestion() {
        if (!sportTypeSelect || !maxPlayersInput) return;
        const sport = sportTypeSelect.value;
        const cfg = sportCapacitySuggestions[sport] || { default: 4, hint: 'Önerilen: 4 oyuncu' };
        maxPlayersInput.value = cfg.default;
        if (capacityHint) {
            capacityHint.innerHTML = '<i class="fas fa-info-circle me-1"></i>' + escapeHtml(cfg.hint);
        }
    }

    if (sportTypeSelect) {
        sportTypeSelect.addEventListener('change', updateCapacitySuggestion);
    }

    // 2. CREATE MATCH FORM SUBMISSION
    const formCreateMatch = document.getElementById('form-create-match');
    if (formCreateMatch) {
        formCreateMatch.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btnSubmit = document.getElementById('btn-create-match-submit') || this.querySelector('button[type="submit"]');
            const originalBtnHtml = btnSubmit ? btnSubmit.innerHTML : '';
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Oluşturuluyor...';
            }

            const fd = new FormData(this);
            const data = Object.fromEntries(fd.entries());
            if (data.max_players) {
                data.max_players = parseInt(data.max_players, 10);
            }
            if (data.id_stations) {
                data.id_stations = parseInt(data.id_stations, 10);
            }
            if (data.price_per_player) {
                data.price_per_player = parseFloat(data.price_per_player);
            }

            const requestHeaders = {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };

            try {
                let res;
                try {
                    res = await fetch('<?= site_url('verticals/create_sports_match') ?>', {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify(data)
                    });
                    if (!res.ok && res.status === 404) {
                        throw new Error('Fallback endpoint required');
                    }
                } catch (fallbackErr) {
                    res = await fetch('<?= site_url('api/v1/verticals/sports/matches') ?>', {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify(data)
                    });
                }

                const json = await res.json().catch(() => ({}));
                if (res.ok && (json.success || json.match_id)) {
                    alert('Açık maç başarıyla oluşturuldu!');
                    window.location.reload();
                } else {
                    alert('Hata: ' + (json.error || json.message || 'Açık maç oluşturulamadı.'));
                }
            } catch (err) {
                alert('Ağ hatası: ' + (err.message || 'Bağlantı hatası.'));
            } finally {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalBtnHtml;
                }
            }
        });
    }

    // 3. JOIN MATCH MODAL HANDLING & CLICK WIRING
    const modalJoinEl = document.getElementById('modal-join-match');
    const modalJoin = modalJoinEl && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(modalJoinEl) : null;

    document.querySelectorAll('.btn-join-match').forEach(btn => {
        btn.addEventListener('click', function() {
            const matchId = this.getAttribute('data-id');
            const title = this.getAttribute('data-title') || 'Açık Maç';
            const sport = this.getAttribute('data-sport') || 'Spor';
            const price = this.getAttribute('data-price') || '0.00';
            const cur = this.getAttribute('data-cur') || '0';
            const max = this.getAttribute('data-max') || '4';

            const idInput = document.getElementById('join-match-id');
            const titleDisplay = document.getElementById('join-match-title-display');
            const sportDisplay = document.getElementById('join-match-sport-display');
            const capacityDisplay = document.getElementById('join-match-capacity-display');
            const feeDisplay = document.getElementById('join-match-fee-display');

            if (idInput) idInput.value = matchId;
            if (titleDisplay) titleDisplay.textContent = title;
            if (sportDisplay) sportDisplay.textContent = sport.toUpperCase();
            if (capacityDisplay) capacityDisplay.textContent = 'Kapasite: ' + cur + ' / ' + max;
            if (feeDisplay) feeDisplay.textContent = '₺' + price + ' / oyuncu';

            const customerSelect = document.getElementById('join-customer-select');
            if (customerSelect) customerSelect.value = '';

            if (modalJoin) {
                modalJoin.show();
            }
        });
    });

    // 4. JOIN MATCH FORM SUBMISSION
    const formJoin = document.getElementById('form-join-match');
    if (formJoin) {
        formJoin.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btnSubmit = document.getElementById('btn-submit-join');
            const originalBtnHtml = btnSubmit ? btnSubmit.innerHTML : '';
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Ekleniyor...';
            }

            const fd = new FormData(this);
            const data = Object.fromEntries(fd.entries());
            const matchId = parseInt(data.match_id, 10);
            const customerId = parseInt(data.id_users_customer, 10);

            if (!matchId || !customerId) {
                alert('Lütfen geçerli bir maç ve oyuncu seçiniz.');
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalBtnHtml;
                }
                return;
            }

            data.match_id = matchId;
            data.customer_id = customerId;
            data.id_users_customer = customerId;

            const requestHeaders = {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };

            try {
                let res;
                try {
                    res = await fetch('<?= site_url('verticals/join_sports_match') ?>', {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify(data)
                    });
                    if (!res.ok && res.status === 404) {
                        throw new Error('Fallback endpoint required');
                    }
                } catch (fallbackErr) {
                    res = await fetch('<?= site_url('api/v1/verticals/sports/matches/') ?>' + encodeURIComponent(matchId) + '/join', {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify(data)
                    });
                }

                const json = await res.json().catch(() => ({}));
                if (res.ok && json.success) {
                    alert(json.message || 'Oyuncu maça başarıyla katıldı!');
                    if (modalJoin) {
                        modalJoin.hide();
                    }
                    window.location.reload();
                } else {
                    alert('Hata: ' + (json.message || json.error || 'Maça katılım gerçekleştirilemedi.'));
                }
            } catch (err) {
                alert('Ağ hatası: ' + (err.message || 'Bağlantı hatası.'));
            } finally {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalBtnHtml;
                }
            }
        });
    }

    // 5. TURNSTILE CHECKIN PREPEND HELPER
    function prependTurnstileCheckin(granted, token) {
        const list = document.getElementById('turnstile-checkins-list');
        if (!list) return;

        const emptyHint = document.getElementById('turnstile-empty-hint');
        if (emptyHint) emptyHint.remove();

        const now = new Date();
        const timeStr = String(now.getHours()).padStart(2, '0') + ':' +
                        String(now.getMinutes()).padStart(2, '0') + ':' +
                        String(now.getSeconds()).padStart(2, '0');

        const item = document.createElement('div');
        item.className = 'list-group-item px-0 py-2 d-flex justify-content-between align-items-center turnstile-animated';

        const safeBadgeClass = granted ? 'bg-success' : 'bg-danger';
        const safeBadgeText = granted ? 'İzin Verildi' : 'Engellendi';
        const safeToken = escapeHtml(token);

        item.innerHTML = `
            <div>
                <span class="badge ${safeBadgeClass} me-1">${safeBadgeText}</span>
                <span>${safeToken}</span>
            </div>
            <span class="text-muted">${timeStr}</span>
        `;

        list.insertBefore(item, list.firstChild);
    }

    // 6. TURNSTILE TEST SUBMISSION & XSS HARDENING
    const formTurnstile = document.getElementById('form-turnstile-test');
    if (formTurnstile) {
        formTurnstile.addEventListener('submit', async function(e) {
            e.preventDefault();
            const tokenInput = document.getElementById('turnstile-token-input');
            const token = tokenInput ? tokenInput.value.trim() : '';
            const box = document.getElementById('turnstile-result-box');
            const btnVerify = document.getElementById('btn-turnstile-submit');
            const originalBtnHtml = btnVerify ? btnVerify.innerHTML : '';

            if (!token) return;

            if (btnVerify) {
                btnVerify.disabled = true;
                btnVerify.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Okunuyor...';
            }

            box.className = 'alert alert-info py-2 px-3 small turnstile-animated';
            box.innerHTML = '<div class="d-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <span>Yetki doğrulanıyor ve röle kontrol ediliyor...</span></div>';
            box.classList.remove('d-none');

            const requestHeaders = {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };

            try {
                let res;
                try {
                    res = await fetch('<?= site_url('verticals/verify_turnstile') ?>', {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify({ token: token, access_token: token })
                    });
                    if (!res.ok && res.status === 404) {
                        throw new Error('Fallback endpoint required');
                    }
                } catch (fallbackErr) {
                    res = await fetch('<?= site_url('api/v1/verticals/sports/turnstile/verify') ?>', {
                        method: 'POST',
                        headers: requestHeaders,
                        body: JSON.stringify({ token: token, access_token: token })
                    });
                }

                const json = await res.json().catch(() => ({}));
                if (json.access_granted) {
                    const safeReason = escapeHtml(String(json.reason || 'Geçerli rezervasyon veya üyelik doğrulandı.'));
                    const safeRelay = escapeHtml(String(json.relay_trigger !== undefined ? json.relay_trigger : '1'));

                    box.className = 'alert alert-success py-3 px-3 small turnstile-pulse-success';
                    box.innerHTML = `
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <span class="badge bg-success py-2 px-3 fs-6 shadow-sm"><i class="fas fa-check-circle me-1"></i>GEÇİŞ İZNİ VERİLDİ</span>
                            <span class="badge bg-dark text-warning py-2 px-2"><i class="fas fa-bolt me-1"></i>Röle Tetiklendi (Port: ${safeRelay})</span>
                        </div>
                        <div class="text-success-emphasis fw-medium"><i class="fas fa-info-circle me-1"></i>${safeReason}</div>
                    `;

                    prependTurnstileCheckin(true, token);
                } else {
                    const safeReason = escapeHtml(String(json.reason || json.error || 'Yetkisiz erişim veya geçersiz kart tokenı.'));

                    box.className = 'alert alert-danger py-3 px-3 small turnstile-pulse-danger';
                    box.innerHTML = `
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <span class="badge bg-danger py-2 px-3 fs-6 shadow-sm"><i class="fas fa-times-circle me-1"></i>GEÇİŞ ENGELLENDİ</span>
                            <span class="badge bg-secondary text-white py-2 px-2"><i class="fas fa-lock me-1"></i>Turnike Kilitli</span>
                        </div>
                        <div class="text-danger fw-medium"><i class="fas fa-exclamation-circle me-1"></i>${safeReason}</div>
                    `;

                    prependTurnstileCheckin(false, token);
                }
            } catch (err) {
                const safeErr = escapeHtml(String(err && err.message ? err.message : 'Bağlantı hatası.'));
                box.className = 'alert alert-danger py-2 px-3 small turnstile-animated';
                box.innerHTML = `<strong><i class="fas fa-exclamation-triangle me-1"></i>Ağ Hatası:</strong> ${safeErr}`;
            } finally {
                if (btnVerify) {
                    btnVerify.disabled = false;
                    btnVerify.innerHTML = originalBtnHtml;
                }
            }
        });
    }
    </script>
</body>
</html>
