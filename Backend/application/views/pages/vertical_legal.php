<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @var array $matters
 * @var array $hearings
 * @var array $time_entries
 * @var array $clients
 * @var array $attorneys
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <?php $this->load->view('components/backend_head'); ?>
    <title><?= e(vars('page_title')) ?> - BooKi</title>
    <style>
        .timer-display {
            font-family: 'Courier New', Courier, monospace;
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: 2px;
        }
        .conflict-hit-card {
            border-left: 4px solid #dc3545;
            background-color: #fff8f8;
        }
    </style>
</head>
<body class="backend-body">
    <?php $this->load->view('components/backend_header'); ?>

    <div class="container-fluid py-4 px-md-4">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1">
                    <i class="fas fa-balance-scale text-primary me-2"></i>Hukuk Bürosu & Dava Yönetimi (Legal Suite)
                </h1>
                <p class="text-muted small mb-0">Dava ve dosya takibi, mahkeme duruşma ajandası, kronometreli saatlik faturalama ve çıkar çatışması kontrolü.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modal-conflict-check">
                    <i class="fas fa-shield-alt me-1"></i> Çıkar Çatışması Sorgula
                </button>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-add-hearing">
                    <i class="fas fa-calendar-plus me-1"></i> Duruşma Ekle
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-matter">
                    <i class="fas fa-folder-plus me-1"></i> Yeni Dava / Dosya Aç
                </button>
            </div>
        </div>

        <!-- TOP STATS & LIVE BILLABLE TIMER WIDGET -->
        <div class="row g-3 mb-4">
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <span class="text-muted small d-block mb-1">Toplam Dava Dosyası</span>
                                <h3 class="fw-bold mb-0 text-dark"><?= count($matters) ?></h3>
                                <small class="text-primary"><i class="fas fa-folder-open me-1"></i>Derdest & Arşiv</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <span class="text-muted small d-block mb-1">Yaklaşan Duruşmalar</span>
                                <h3 class="fw-bold mb-0 text-warning"><?= count($hearings) ?></h3>
                                <small class="text-muted"><i class="fas fa-gavel me-1"></i>Mahkeme Takvimi</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <span class="text-muted small d-block mb-1">Faturalanacak Süre</span>
                                <h3 class="fw-bold mb-0 text-success"><?= count($time_entries) ?> <span class="fs-6 fw-normal text-muted">kayıt</span></h3>
                                <small class="text-success"><i class="fas fa-stopwatch me-1"></i>Saatlik Hakediş</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LIVE BILLABLE STOPWATCH TIMER (CLIO STANDARD) -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 bg-dark text-white h-100">
                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-danger text-uppercase px-2 py-1"><i class="fas fa-circle me-1 animate-pulse"></i> Canlı Dava Kronometresi</span>
                            <small class="text-white-50">Saatlik Zaman Takibi</small>
                        </div>
                        <div class="text-center my-1">
                            <div class="timer-display text-warning" id="live-timer-text">00:00:00</div>
                        </div>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm bg-secondary text-white border-0" id="timer-matter-select">
                                <option value="">İlgili Dava Dosyasını Seçin...</option>
                                <?php foreach ($matters as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= e($m['matter_number']) ?> - <?= e($m['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-sm btn-success px-3" id="btn-timer-start" onclick="toggleTimer()">
                                <i class="fas fa-play" id="timer-icon"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-light" onclick="saveTimerEntry()" title="Süreyi Kaydet">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABS: DOSYALAR / DURUŞMALAR / SÜRE KAYITLARI -->
        <ul class="nav nav-tabs border-bottom mb-3" role="tablist">
            <li class="nav-item">
                <a class="nav-link active fw-bold" data-bs-toggle="tab" href="#tab-matters">
                    <i class="fas fa-briefcase me-2"></i>Dava Dosyaları (<?= count($matters) ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-bold" data-bs-toggle="tab" href="#tab-hearings">
                    <i class="fas fa-gavel me-2"></i>Duruşma Ajandası & Yasal Süreler (<?= count($hearings) ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-bold" data-bs-toggle="tab" href="#tab-timesheets">
                    <i class="fas fa-clock me-2"></i>Saatlik Çalışma & Efor Dökümü (<?= count($time_entries) ?>)
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- TAB 1: DAVA DOSYALARI -->
            <div class="tab-pane fade show active" id="tab-matters">
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Dosya No</th>
                                    <th>Dava Başlığı & Mahkeme</th>
                                    <th>Müvekkil</th>
                                    <th>Sorumlu Avukat</th>
                                    <th>Tür & Aşama</th>
                                    <th>Dava Değeri</th>
                                    <th>Karşı Taraf</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($matters)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="fas fa-balance-scale fs-1 d-block mb-3 text-secondary"></i>
                                            Henüz kayıtlı dava dosyası bulunmuyor. Yeni dosya eklemek için yukarıdaki butonu kullanın.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($matters as $m): ?>
                                        <tr>
                                            <td class="fw-bold text-primary font-monospace"><?= e($m['matter_number']) ?></td>
                                            <td>
                                                <div class="fw-bold"><?= e($m['title']) ?></div>
                                                <small class="text-muted"><?= e($m['court_name'] ?: 'Mahkeme Belirtilmedi') ?> | <strong>Esas:</strong> <?= e($m['case_number'] ?: '-') ?></small>
                                            </td>
                                            <td>
                                                <div class="fw-semibold"><?= e(trim($m['client_first_name'] . ' ' . $m['client_last_name'])) ?></div>
                                                <small class="text-muted"><?= e($m['client_phone']) ?></small>
                                            </td>
                                            <td><?= e(trim($m['attorney_first_name'] . ' ' . $m['attorney_last_name'])) ?></td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= strtoupper(e($m['case_type'])) ?></span>
                                                <span class="badge bg-primary text-uppercase"><?= e($m['case_status']) ?></span>
                                            </td>
                                            <td class="fw-bold text-success">₺<?= number_format((float) $m['claim_amount'], 2) ?></td>
                                            <td>
                                                <div class="small"><?= e($m['opposing_party'] ?: '-') ?></div>
                                                <small class="text-muted">Vekil: <?= e($m['opposing_counsel'] ?: '-') ?></small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteMatter(<?= $m['id'] ?>)">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: DURUŞMALAR -->
            <div class="tab-pane fade" id="tab-hearings">
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Duruşma Tarihi & Saati</th>
                                    <th>Dava / Dosya</th>
                                    <th>Mahkeme / Salon</th>
                                    <th>Duruşma Özeti & Zabıt Notları</th>
                                    <th>Son Yasal Tebligat Süresi</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($hearings)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Kayıtlı duruşma randevusu bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($hearings as $h): ?>
                                        <tr>
                                            <td class="fw-bold text-danger">
                                                <i class="fas fa-calendar-day me-1"></i><?= date('d.m.Y H:i', strtotime($h['hearing_datetime'])) ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= e($h['matter_title']) ?></div>
                                                <small class="text-muted"><?= e($h['matter_number']) ?></small>
                                            </td>
                                            <td><?= e($h['court_name']) ?> <br><small class="text-muted"><?= e($h['court_room'] ?: 'Salon Belirtilmedi') ?></small></td>
                                            <td><?= e($h['hearing_summary'] ?: '-') ?></td>
                                            <td>
                                                <?php if ($h['next_deadline_date']): ?>
                                                    <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i><?= date('d.m.Y', strtotime($h['next_deadline_date'])) ?></span>
                                                    <small class="d-block text-muted"><?= e($h['deadline_description']) ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-info text-uppercase"><?= e($h['status']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: SAATLİK ÇALIŞMA KAYITLARI -->
            <div class="tab-pane fade" id="tab-timesheets">
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tarih</th>
                                    <th>Dosya No & Başlık</th>
                                    <th>Avukat</th>
                                    <th>Yapılan Çalışma / Açıklama</th>
                                    <th>Süre</th>
                                    <th>Saatlik Ücret</th>
                                    <th>Toplam Tutar</th>
                                    <th>Faturalandırılabilir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($time_entries)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">Henüz zaman kaydı girilmedi. Canlı kronometre ile çalışmaya başlayabilirsiniz.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($time_entries as $t): ?>
                                        <tr>
                                            <td><?= date('d.m.Y H:i', strtotime($t['created_at'])) ?></td>
                                            <td><strong><?= e($t['matter_number']) ?></strong> - <?= e($t['matter_title']) ?></td>
                                            <td><?= e(trim($t['attorney_first_name'] . ' ' . $t['attorney_last_name'])) ?></td>
                                            <td><?= e($t['work_description']) ?></td>
                                            <td class="fw-bold"><?= $t['duration_minutes'] ?> dk</td>
                                            <td>₺<?= number_format((float) $t['hourly_rate'], 2) ?>/saat</td>
                                            <td class="fw-bold text-success">₺<?= number_format((float) $t['total_amount'], 2) ?></td>
                                            <td>
                                                <?= $t['is_billable'] ? '<span class="badge bg-success">Evet</span>' : '<span class="badge bg-secondary">Hayır</span>' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: YENİ DAVA DOSYASI -->
    <div class="modal fade" id="modal-add-matter" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-folder-plus text-primary me-2"></i>Yeni Dava / Dosya Kaydı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-add-matter" onsubmit="submitMatter(event)">
                    <div class="modal-body row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Dava / Dosya Başlığı *</label>
                            <input type="text" name="title" class="form-control" placeholder="Örn: X A.Ş. Sözleşme İhlali ve Alacak Davası" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Dosya Referans Kodu</label>
                            <input type="text" name="matter_number" class="form-control" placeholder="Otomatik Üretilir">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Müvekkil *</label>
                            <select name="id_users_client" class="form-select" required>
                                <option value="">Müvekkil Seçiniz...</option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e(trim($c['first_name'] . ' ' . $c['last_name'])) ?> (<?= e($c['phone_number']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Sorumlu Avukat *</label>
                            <select name="id_users_attorney" class="form-select" required>
                                <option value="">Avukat Seçiniz...</option>
                                <?php foreach ($attorneys as $a): ?>
                                    <option value="<?= $a['id'] ?>"><?= e(trim($a['first_name'] . ' ' . $a['last_name'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Mahkeme Adı</label>
                            <input type="text" name="court_name" class="form-control" placeholder="Örn: İstanbul 14. Asliye Ticaret Mahkemesi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Esas / Dosya Numarası</label>
                            <input type="text" name="case_number" class="form-control" placeholder="Örn: 2026/412 E.">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Dava Türü</label>
                            <select name="case_type" class="form-select">
                                <option value="civil">Hukuk / Ticaret</option>
                                <option value="criminal">Ceza</option>
                                <option value="enforcement">İcra / İflas</option>
                                <option value="labor">İş Hukuku</option>
                                <option value="administrative">İdare & Vergi</option>
                                <option value="family">Aile / Boşanma</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Dava Değeri (TL)</label>
                            <input type="number" step="0.01" name="claim_amount" class="form-control" placeholder="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Saatlik Ücret (TL/Saat)</label>
                            <input type="number" step="0.01" name="hourly_rate" class="form-control" value="3500.00">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Karşı Taraf (Davalı / Davacı)</label>
                            <input type="text" name="opposing_party" class="form-control" placeholder="Hasım kişi veya şirket adı">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Karşı Taraf Vekili (Avukat)</label>
                            <input type="text" name="opposing_counsel" class="form-control" placeholder="Karşı vekil adı soyadı">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Dava Konusu & Notlar</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Özet uyuşmazlık konusu, tensip notları..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Dosyayı Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: ÇIKAR ÇATIŞMASI KONTROLÜ (CLIO BENCHMARK) -->
    <div class="modal fade" id="modal-conflict-check" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-shield-alt me-2"></i>Çıkar Çatışması Sorgulama (Conflict of Interest Check)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Yeni müvekkil kabul etmeden önce, karşı taraf veya ilişkili kişilerin geçmişte temsil edilip edilmediğini veya hasım olarak yer alıp almadığını tüm arşivde tarayın.</p>
                    <div class="input-group mb-3">
                        <input type="text" id="conflict-keyword" class="form-control form-control-lg" placeholder="Şahıs Adı, Şirket Unvanı veya TC/Vergi No girin...">
                        <button class="btn btn-danger px-4" onclick="runConflictCheck()">
                            <i class="fas fa-search me-1"></i> Taramayı Başlat
                        </button>
                    </div>

                    <div id="conflict-results-container" class="d-none">
                        <h6 class="fw-bold mb-3" id="conflict-status-text"></h6>
                        <div id="conflict-hits-list"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: YENİ DURUŞMA -->
    <div class="modal fade" id="modal-add-hearing" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-calendar-plus text-primary me-2"></i>Duruşma Randevusu Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-add-hearing" onsubmit="submitHearing(event)">
                    <div class="modal-body row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">İlgili Dava Dosyası *</label>
                            <select name="id_legal_matters" class="form-select" required>
                                <option value="">Dava Seçiniz...</option>
                                <?php foreach ($matters as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= e($m['matter_number']) ?> - <?= e($m['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-bold">Duruşma Tarih & Saati *</label>
                            <input type="datetime-local" name="hearing_datetime" class="form-control" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Duruşma Salon No</label>
                            <input type="text" name="court_room" class="form-control" placeholder="Örn: 2. Kat No: 4">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Duruşma Notu / Gündemi</label>
                            <textarea name="hearing_summary" class="form-control" rows="2" placeholder="Tanık dinletilmesi, bilirkişi raporuna itiraz..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Hak Düşürücü Son Gün</label>
                            <input type="date" name="next_deadline_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Süre Açıklaması</label>
                            <input type="text" name="deadline_description" class="form-control" placeholder="Cevap dilekçesi, itiraz">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Duruşmayı Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // LIVE STOPWATCH TIMER
        let timerSeconds = 0;
        let timerInterval = null;
        let isRunning = false;

        function toggleTimer() {
            if (!isRunning) {
                const matterId = document.getElementById('timer-matter-select').value;
                if (!matterId) {
                    alert('Lütfen kronometreyi başlatmadan önce ilgili dava dosyasını seçiniz.');
                    return;
                }
                isRunning = true;
                document.getElementById('timer-icon').className = 'fas fa-pause';
                document.getElementById('btn-timer-start').className = 'btn btn-sm btn-warning px-3';
                timerInterval = setInterval(() => {
                    timerSeconds++;
                    const hrs = String(Math.floor(timerSeconds / 3600)).padStart(2, '0');
                    const mins = String(Math.floor((timerSeconds % 3600) / 60)).padStart(2, '0');
                    const secs = String(timerSeconds % 60).padStart(2, '0');
                    document.getElementById('live-timer-text').innerText = `${hrs}:${mins}:${secs}`;
                }, 1000);
            } else {
                isRunning = false;
                clearInterval(timerInterval);
                document.getElementById('timer-icon').className = 'fas fa-play';
                document.getElementById('btn-timer-start').className = 'btn btn-sm btn-success px-3';
            }
        }

        async function saveTimerEntry() {
            if (timerSeconds < 60) {
                alert('Kaydetmek için en az 1 dakika çalışılmış olması gerekir.');
                return;
            }
            const matterId = document.getElementById('timer-matter-select').value;
            const desc = prompt('Yapılan çalışmayı özetleyiniz (Dilekçe yazımı, dosya inceleme vb.):');
            if (!desc) return;

            const minutes = Math.ceil(timerSeconds / 60);
            try {
                const res = await fetch('<?= site_url('verticals/save_legal_time_entry') ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-Token': '<?= $this->security->get_csrf_hash() ?>'},
                    body: JSON.stringify({
                        id_legal_matters: matterId,
                        work_description: desc,
                        duration_minutes: minutes,
                        hourly_rate: 3500
                    })
                });
                const data = await res.json();
                if (data.success) {
                    alert(`Çalışma kaydedildi: ${minutes} dakika dosya hakedişine eklendi.`);
                    location.reload();
                }
            } catch (e) {
                alert('Kayıt sırasında hata oluştu.');
            }
        }

        async function runConflictCheck() {
            const keyword = document.getElementById('conflict-keyword').value.trim();
            if (keyword.length < 3) {
                alert('Lütfen aramak için en az 3 karakter giriniz.');
                return;
            }

            const res = await fetch(`<?= site_url('verticals/check_legal_conflict') ?>?keyword=${encodeURIComponent(keyword)}`);
            const data = await res.json();
            const container = document.getElementById('conflict-results-container');
            const statusText = document.getElementById('conflict-status-text');
            const hitsList = document.getElementById('conflict-hits-list');

            container.classList.remove('d-none');
            hitsList.innerHTML = '';

            if (data.total_conflicts === 0) {
                statusText.className = 'fw-bold text-success';
                statusText.innerHTML = '<i class="fas fa-check-circle me-1"></i> Çıkar Çatışması Bulunmadı: İlgili isim arşivde hasım veya taraf olarak eşleşmedi.';
            } else {
                statusText.className = 'fw-bold text-danger';
                statusText.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i> DİKKAT: ${data.total_conflicts} Adet Olası Çatışma Eşleşmesi Bulundu!`;

                data.matters.forEach(m => {
                    hitsList.innerHTML += `
                        <div class="card p-3 mb-2 conflict-hit-card">
                            <div class="fw-bold text-danger"><i class="fas fa-balance-scale me-1"></i> Dava Eşleşmesi: ${m.matter_number} - ${m.title}</div>
                            <div class="small"><strong>Müvekkil:</strong> ${m.first_name} ${m.last_name} | <strong>Hasım Taraf:</strong> ${m.opposing_party} | <strong>Karşı Vekil:</strong> ${m.opposing_counsel}</div>
                        </div>
                    `;
                });
            }
        }

        async function submitMatter(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const json = Object.fromEntries(formData.entries());

            const res = await fetch('<?= site_url('verticals/save_legal_matter') ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': '<?= $this->security->get_csrf_hash() ?>'},
                body: JSON.stringify(json)
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Kayıt sırasında hata oluştu.');
            }
        }

        async function submitHearing(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const json = Object.fromEntries(formData.entries());

            const res = await fetch('<?= site_url('verticals/save_legal_hearing') ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': '<?= $this->security->get_csrf_hash() ?>'},
                body: JSON.stringify(json)
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            }
        }

        async function deleteMatter(id) {
            if (!confirm('Bu dava dosyasını silmek istediğinizden emin misiniz?')) return;
            const res = await fetch('<?= site_url('verticals/delete_legal_matter') ?>/' + id, {method: 'POST'});
            location.reload();
        }
    </script>
</body>
</html>
