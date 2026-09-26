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
                                                <h5 class="fw-bold text-success mb-0">₺<?= number_format($m['price_per_player'], 2) ?> <small class="text-muted fs-6">/ oyuncu</small></h5>
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
                                                <button class="btn btn-sm btn-outline-success btn-join-match" data-id="<?= $m['id'] ?>">
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
                                <button class="btn btn-dark" type="submit"><i class="fas fa-key me-1"></i> Okut</button>
                            </div>
                        </form>
                        <div id="turnstile-result-box" class="alert d-none py-2 px-3 small"></div>

                        <h6 class="fw-bold mt-4 mb-2"><i class="fas fa-history me-1 text-secondary"></i>Son Turnike Geçişleri</h6>
                        <div class="list-group list-group-flush small" style="max-height: 280px; overflow-y: auto;">
                            <?php if (empty($checkins)): ?>
                                <div class="text-muted text-center py-3">Henüz geçiş kaydı yok.</div>
                            <?php else: ?>
                                <?php foreach ($checkins as $ck): ?>
                                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge <?= $ck['status'] === 'completed' ? 'bg-success' : 'bg-danger' ?> me-1">
                                                <?= $ck['status'] === 'completed' ? 'İzin Verildi' : 'Engellendi' ?>
                                            </span>
                                            <span>Müşteri #<?= $ck['id_users_customer'] ?></span>
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
    <div class="modal fade" id="modal-create-match" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trophy text-warning me-2"></i>Yeni Açık Maç Oluştur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-create-match">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Maç Başlığı</label>
                            <input type="text" name="title" class="form-control" placeholder="Örn: Hafta Sonu Padel Karışık Maç" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Spor Türü</label>
                                <select name="sport_type" class="form-select">
                                    <option value="padel">Padel</option>
                                    <option value="tennis">Tenis</option>
                                    <option value="football">Halı Saha (Futbol)</option>
                                    <option value="basketball">Basketbol</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Seviye</label>
                                <select name="level_required" class="form-select">
                                    <option value="all">Her Seviye</option>
                                    <option value="beginner">Başlangıç (1.0 - 2.5)</option>
                                    <option value="intermediate">Orta (3.0 - 4.0)</option>
                                    <option value="advanced">İleri / Pro (4.5+)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Başlangıç Zamanı</label>
                                <input type="datetime-local" name="start_datetime" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Bitiş Zamanı</label>
                                <input type="datetime-local" name="end_datetime" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Kort / Saha</label>
                                <select name="id_stations" class="form-select">
                                    <?php foreach ($stations as $st): ?>
                                        <option value="<?= $st['id'] ?>"><?= e($st['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Kişi Başı Ücret (TL)</label>
                                <input type="number" step="0.01" name="price_per_player" class="form-control" placeholder="250.00">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Maçı Başlat & Aç</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('form-create-match').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const data = Object.fromEntries(fd.entries());
        try {
            const res = await fetch('<?= site_url('api/v1/verticals/sports/matches') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            if (res.ok) {
                alert('Açık maç oluşturuldu!');
                window.location.reload();
            } else {
                alert('Hata: ' + (json.error || 'İşlem başarısız'));
            }
        } catch (err) {
            alert('Ağ hatası: ' + err.message);
        }
    });

    document.getElementById('form-turnstile-test').addEventListener('submit', async function(e) {
        e.preventDefault();
        const token = document.getElementById('turnstile-token-input').value;
        const box = document.getElementById('turnstile-result-box');
        box.className = 'alert alert-info py-2 px-3 small';
        box.innerText = 'Doğrulanıyor...';
        box.classList.remove('d-none');

        try {
            const res = await fetch('<?= site_url('api/v1/verticals/sports/turnstile/verify') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: token })
            });
            const json = await res.json();
            if (json.access_granted) {
                box.className = 'alert alert-success py-2 px-3 small';
                box.innerHTML = '<strong><i class="fas fa-check-circle me-1"></i>GEÇİŞ ONAYLANDI!</strong><br>' + json.reason + ' (Röle: ' + json.relay_trigger + ')';
            } else {
                box.className = 'alert alert-danger py-2 px-3 small';
                box.innerHTML = '<strong><i class="fas fa-times-circle me-1"></i>GEÇİŞ ENGELLENDİ!</strong><br>' + (json.reason || json.error);
            }
        } catch (err) {
            box.className = 'alert alert-danger py-2 px-3 small';
            box.innerText = 'Ağ hatası: ' + err.message;
        }
    });
    </script>
</body>
</html>
