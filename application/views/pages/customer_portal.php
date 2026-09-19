<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(setting('company_name') ?: 'BooKi') ?> — Müşteri Portalı</title>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/fontawesome/css/all.min.css') ?>">
    <style>
        :root {
            --primary-color: <?= setting('company_color') ?: '#35A768' ?>;
            --primary-light: #eaf6ef;
        }
        body {
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1e293b;
            padding-bottom: 75px;
        }
        .app-header {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        .hero-appointment-card {
            background: linear-gradient(135deg, <?= setting('company_color') ?: '#35A768' ?> 0%, #1e293b 100%);
            color: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(53, 167, 104, 0.3);
        }
        .portal-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            height: 65px;
            z-index: 1030;
            display: flex;
            align-items: center;
            justify-content: space-around;
        }
        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #64748b;
            text-decoration: none;
            font-size: 11px;
            font-weight: 600;
            flex: 1;
            height: 100%;
            transition: color 0.15s ease;
        }
        .bottom-nav-item.active, .bottom-nav-item:hover {
            color: #2563eb;
        }
        .bottom-nav-item i {
            font-size: 18px;
            margin-bottom: 3px;
        }
        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
        }
        .tab-content-portal {
            display: none;
        }
        .tab-content-portal.active {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <header class="app-header py-3 px-3">
        <div class="container d-flex justify-content-between align-items-center" style="max-width: 680px;">
            <div class="d-flex align-items-center">
                <img src="<?= setting('company_logo') ? e(setting('company_logo')) : base_url('assets/img/logo.png') ?>" alt="logo" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: contain;">
                <span class="fw-bold fs-6"><?= e(setting('company_name') ?: 'BooKi') ?></span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="showQRPassModal()">
                    <i class="fas fa-qrcode me-1"></i> QR Giriş Kartı
                </button>
                <a href="<?= site_url('logout') ?>" class="btn btn-sm btn-link text-muted p-1" title="Çıkış Yap">
                    <i class="fas fa-sign-out-alt fa-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="container py-3" style="max-width: 680px;">
        <!-- TAB 1: HOME -->
        <div id="portal-tab-home" class="tab-content-portal active">
            <!-- Greeting & Quick Stats -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0">Merhaba, <?= e(vars('customer')['first_name'] ?? 'Değerli Müşterimiz') ?> 👋</h5>
                    <small class="text-muted">Randevu ve üyeliklerinizi buradan yönetebilirsiniz.</small>
                </div>
                <div>
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
                        <i class="fas fa-star me-1 text-warning"></i> Gold Üye
                    </span>
                </div>
            </div>

            <!-- Hero: Upcoming Appointment Card -->
            <?php
            $upcoming = vars('upcoming_appointments');
            $next_appt = !empty($upcoming) ? $upcoming[0] : null;
            ?>
            <?php if ($next_appt): ?>
                <div class="hero-appointment-card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-white bg-opacity-25 text-white rounded-pill px-3 py-1">
                            <i class="fas fa-calendar-check me-1"></i> Sonraki Randevunuz
                        </span>
                        <span class="small fw-semibold"><?= e(date('d M Y', strtotime($next_appt['start_datetime']))) ?></span>
                    </div>
                    <h3 class="fw-bold mb-1"><?= e($next_appt['service_name']) ?></h3>
                    <p class="text-white-50 mb-3"><i class="fas fa-user-circle me-1"></i> Uzman: <?= e($next_appt['provider_name']) ?> &bull; <i class="far fa-clock me-1"></i> <?= date('H:i', strtotime($next_appt['start_datetime'])) ?></p>

                    <div class="d-flex flex-wrap gap-2 pt-2 border-top border-white border-opacity-25">
                        <a href="<?= site_url('booking') ?>" class="btn btn-light btn-sm fw-bold px-3 rounded-pill text-primary">
                            <i class="fas fa-redo-alt me-1"></i> Yeniden Planla
                        </a>
                        <a href="https://wa.me/?text=Randevum+hakkında+bilgi+almak+istiyorum" target="_blank" class="btn btn-outline-light btn-sm rounded-pill px-3">
                            <i class="fab fa-whatsapp me-1"></i> WhatsApp
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="portal-card p-4 mb-4 text-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 d-inline-flex mb-3">
                        <i class="fas fa-calendar-plus fa-2x"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Yaklaşan Bir Randevunuz Yok</h6>
                    <p class="text-muted small mb-3">Hemen yeni bir seans veya randevu oluşturun.</p>
                    <a href="<?= site_url('booking') ?>" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="fas fa-plus me-1"></i> Randevu Al
                    </a>
                </div>
            <?php endif; ?>

            <!-- Package Balance Highlights -->
            <div class="portal-card p-3 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><i class="fas fa-box text-success me-2"></i>Aktif Paket Bakiyeniz</h6>
                    <a href="javascript:void(0)" onclick="switchTab('packages')" class="small text-primary text-decoration-none fw-semibold">Tümü</a>
                </div>

                <?php
                $packages = vars('customer_packages');
                ?>
                <?php if (empty($packages)): ?>
                    <p class="text-muted small mb-0 text-center py-2">Kayıtlı aktif paketiniz bulunmuyor.</p>
                <?php else: ?>
                    <?php foreach ($packages as $pkg): ?>
                        <?php
                        $remaining = max(0, (int)$pkg['total_sessions'] - (int)$pkg['used_sessions']);
                        $pct = round(($pkg['used_sessions'] / max(1, $pkg['total_sessions'])) * 100);
                        ?>
                        <div class="mb-3 pb-3 border-bottom last-border-0">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold small text-dark">Hizmet Paketi</span>
                                <span class="badge bg-success bg-opacity-10 text-success fw-bold"><?= $remaining ?> / <?= $pkg['total_sessions'] ?> Seans Kaldı</span>
                            </div>
                            <div class="progress progress-bar-custom bg-light">
                                <div class="progress-bar bg-success" style="width: <?= 100 - $pct ?>%;"></div>
                            </div>
                            <?php if (!empty($pkg['expires_at'])): ?>
                                <small class="text-muted d-block mt-1" style="font-size: 11px;"><i class="far fa-calendar-alt me-1"></i>Geçerlilik: <?= date('d.m.Y', strtotime($pkg['expires_at'])) ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Quick Action Grid -->
            <div class="row g-2 mb-4">
                <div class="col-6">
                    <a href="<?= site_url('booking') ?>" class="portal-card p-3 text-center text-decoration-none d-block h-100">
                        <i class="fas fa-calendar-plus fa-2x text-primary mb-2"></i>
                        <h6 class="fw-bold text-dark mb-0 small">Hızlı Randevu Al</h6>
                    </a>
                </div>
                <div class="col-6">
                    <a href="javascript:void(0)" onclick="showQRPassModal()" class="portal-card p-3 text-center text-decoration-none d-block h-100">
                        <i class="fas fa-id-card fa-2x text-info mb-2"></i>
                        <h6 class="fw-bold text-dark mb-0 small">Dijital Kartım</h6>
                    </a>
                </div>
            </div>
        </div>

        <!-- TAB 2: APPOINTMENTS -->
        <div id="portal-tab-appointments" class="tab-content-portal">
            <h5 class="fw-bold mb-3"><i class="fas fa-calendar-alt text-primary me-2"></i>Randevu Geçmişim</h5>
            
            <div class="portal-card p-3 mb-3">
                <h6 class="fw-bold small text-muted mb-3">Tüm Randevular</h6>
                <?php
                $past = vars('past_appointments');
                $all_appts = array_merge(vars('upcoming_appointments') ?: [], $past ?: []);
                ?>
                <?php if (empty($all_appts)): ?>
                    <p class="text-muted text-center py-4 mb-0">Kayıtlı randevu geçmişiniz bulunmuyor.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($all_appts as $a): ?>
                            <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 fw-bold"><?= e($a['service_name']) ?></h6>
                                    <small class="text-muted"><i class="fas fa-user-circle me-1"></i><?= e($a['provider_name']) ?> &bull; <?= date('d.m.Y H:i', strtotime($a['start_datetime'])) ?></small>
                                </div>
                                <span class="badge bg-light text-dark border"><?= e($a['status'] ?? 'Onaylandı') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB 3: PACKAGES & MEMBERSHIPS -->
        <div id="portal-tab-packages" class="tab-content-portal">
            <h5 class="fw-bold mb-3"><i class="fas fa-box text-success me-2"></i>Paketlerim ve Üyeliklerim</h5>

            <div class="portal-card p-3 mb-4">
                <h6 class="fw-bold small text-muted mb-3">Satın Alınan Paketler</h6>
                <?php if (empty($packages)): ?>
                    <p class="text-muted text-center py-3 mb-0">Aktif bir paketiniz bulunmuyor.</p>
                <?php else: ?>
                    <?php foreach ($packages as $pkg): ?>
                        <div class="p-3 border rounded-3 mb-2 bg-light bg-opacity-25">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold text-dark mb-0">Hizmet Seans Paketi</h6>
                                <span class="badge bg-success"><?= $pkg['status'] ?></span>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mb-2">
                                <span>Kalan: <strong><?= $pkg['total_sessions'] - $pkg['used_sessions'] ?> seans</strong></span>
                                <span>Toplam: <?= $pkg['total_sessions'] ?> seans</span>
                            </div>
                            <div class="progress progress-bar-custom bg-white border">
                                <div class="progress-bar bg-success" style="width: <?= round((($pkg['total_sessions'] - $pkg['used_sessions']) / max(1, $pkg['total_sessions'])) * 100) ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB 4: WALLET & LOYALTY -->
        <div id="portal-tab-wallet" class="tab-content-portal">
            <h5 class="fw-bold mb-3"><i class="fas fa-wallet text-warning me-2"></i>Cüzdan & Sadakat Puanları</h5>

            <div class="portal-card p-4 mb-4 text-center bg-light">
                <small class="text-muted d-block mb-1">Mevcut Sadakat Puanınız</small>
                <h1 class="display-4 fw-bold text-warning text-dark mb-1">1,250</h1>
                <span class="badge bg-warning text-dark px-3 py-1 rounded-pill">Gold Tier Üye</span>
            </div>

            <div class="portal-card p-3">
                <h6 class="fw-bold small text-muted mb-2">Puan Avantajları</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="py-2 border-bottom"><i class="fas fa-check-circle text-success me-2"></i>Her harcamada %5 puan kazanımı</li>
                    <li class="py-2 border-bottom"><i class="fas fa-gift text-primary me-2"></i>Doğum gününde ücretsiz mini bakım</li>
                    <li class="py-2"><i class="fas fa-bolt text-warning me-2"></i>Öncelikli ilk müsaitlik randevu rezervasyonu</li>
                </ul>
            </div>
        </div>

        <!-- TAB 5: PROFILE -->
        <div id="portal-tab-profile" class="tab-content-portal">
            <h5 class="fw-bold mb-3"><i class="fas fa-user-cog text-secondary me-2"></i>Profil Bilgilerim</h5>

            <div class="portal-card p-4">
                <form id="profile-form">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Ad</label>
                            <input type="text" id="first_name" class="form-control" value="<?= e(vars('customer')['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Soyad</label>
                            <input type="text" id="last_name" class="form-control" value="<?= e(vars('customer')['last_name'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">E-posta</label>
                            <input type="email" id="email" class="form-control" value="<?= e(vars('customer')['email'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Telefon</label>
                            <input type="text" id="phone_number" class="form-control" value="<?= e(vars('customer')['phone_number'] ?? '') ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-4 rounded-pill fw-bold py-2">
                        Bilgilerimi Güncelle
                    </button>
                    <div class="alert alert-success mt-3 d-none py-2 text-center small" id="profile-msg">Bilgileriniz kaydedildi!</div>
                </form>
            </div>
        </div>
    </main>

    <!-- Bottom Navigation Bar -->
    <nav class="bottom-nav">
        <a href="javascript:void(0)" class="bottom-nav-item active" onclick="switchTab('home', this)">
            <i class="fas fa-home"></i>
            <span>Ana Sayfa</span>
        </a>
        <a href="javascript:void(0)" class="bottom-nav-item" onclick="switchTab('appointments', this)">
            <i class="fas fa-calendar-alt"></i>
            <span>Randevular</span>
        </a>
        <a href="javascript:void(0)" class="bottom-nav-item" onclick="switchTab('packages', this)">
            <i class="fas fa-box"></i>
            <span>Paketler</span>
        </a>
        <a href="javascript:void(0)" class="bottom-nav-item" onclick="switchTab('wallet', this)">
            <i class="fas fa-wallet"></i>
            <span>Cüzdan</span>
        </a>
        <a href="javascript:void(0)" class="bottom-nav-item" onclick="switchTab('profile', this)">
            <i class="fas fa-user"></i>
            <span>Profil</span>
        </a>
    </nav>

    <!-- Modal: QR Pass Modal -->
    <div class="modal fade" id="qr-pass-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4 rounded-4">
                <h5 class="fw-bold mb-1">Dijital Giriş Kartınız</h5>
                <p class="text-muted small mb-3">Tesise girişte bu QR kodu okutabilirsiniz.</p>

                <div class="p-3 bg-white border rounded-4 d-inline-block mx-auto mb-3 shadow-sm">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= vars('customer')['id'] ?? '1' ?>" alt="QR" style="width: 180px; height: 180px;">
                </div>

                <div class="fw-bold fs-6 mb-1"><?= e(vars('customer')['first_name'] ?? '') ?> <?= e(vars('customer')['last_name'] ?? '') ?></div>
                <small class="text-muted d-block mb-3">Müşteri No: #<?= vars('customer')['id'] ?? '1' ?></small>

                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>

    <script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
    <script>
    function switchTab(tabName, linkEl) {
        document.querySelectorAll('.tab-content-portal').forEach(tab => tab.classList.remove('active'));
        document.getElementById('portal-tab-' + tabName)?.classList.add('active');

        if (linkEl) {
            document.querySelectorAll('.bottom-nav-item').forEach(item => item.classList.remove('active'));
            linkEl.classList.add('active');
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function showQRPassModal() {
        const modal = new bootstrap.Modal(document.getElementById('qr-pass-modal'));
        modal.show();
    }

    document.getElementById('profile-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const msg = document.getElementById('profile-msg');
        msg.classList.remove('d-none');
        setTimeout(() => msg.classList.add('d-none'), 3000);
    });
    </script>
</body>
</html>
