<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#35495E">
    <title><?= e(vars('page_title')) ?> - BooKi</title>

    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/ki-command-center.min.css') ?>">

    <style>
        body {
            background-color: #f8fafc;
            color: #1e293b;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .onboarding-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            color: #ffffff;
            border-radius: 1.25rem;
            padding: 2.5rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
        .industry-card {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .industry-card:hover {
            transform: translateY(-4px);
            border-color: #3b82f6;
            box-shadow: 0 12px 24px -8px rgba(59, 130, 246, 0.25);
        }
        .industry-card.selected {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }
        .industry-icon {
            font-size: 2.5rem;
            line-height: 1;
            margin-bottom: 0.75rem;
        }
        .service-type-badge {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.35rem 0.65rem;
            border-radius: 9999px;
        }
        .type-duration { background: #e0f2fe; color: #0369a1; }
        .type-station { background: #fef3c7; color: #b45309; }
        .type-procedure { background: #fce7f3; color: #be185d; }
        .type-hybrid { background: #ede9fe; color: #6d28d9; }

        .preview-pane {
            background: #ffffff;
            border-radius: 1.25rem;
            border: 1px solid #e2e8f0;
            padding: 1.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 2rem;
        }
        .tag-pill {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-right: 0.35rem;
            margin-bottom: 0.35rem;
        }
        .step-progress {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .step-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: #94a3b8;
        }
        .step-item.active {
            color: #2563eb;
        }
        .step-number {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background: #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }
        .step-item.active .step-number {
            background: #2563eb;
            color: #ffffff;
        }
        .step-line {
            width: 3rem;
            height: 2px;
            background: #e2e8f0;
        }
    </style>
</head>
<body class="p-3 p-md-4">
    <?php $this->load->view('components/backend_header'); ?>

    <div class="container-xl mt-3 mb-5">
        <!-- Top Hero Section -->
        <div class="onboarding-hero d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill">SaaS Onboarding & Blueprint Engine</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">11 Hazır Sektör Paketi</span>
                </div>
                <h1 class="h2 fw-bold text-white mb-2">İşletmenizin Sektörünü Seçin</h1>
                <p class="text-white-50 mb-0 max-w-2xl">
                    Seçtiğiniz sektöre göre hazır hizmetler, istasyonlar, terminoloji (Hasta/Danışan/Üye) ve özel modüller 30 saniyede otomatik kurulur.
                </p>
            </div>
            <div class="text-md-end">
                <span class="text-white-50 small d-block mb-1">Mevcut Sektör:</span>
                <span class="badge bg-secondary px-3 py-2 fs-6 fw-semibold text-uppercase"><?= e($current_industry) ?></span>
            </div>
        </div>

        <!-- Wizard Steps -->
        <div class="step-progress">
            <div class="step-item active">
                <div class="step-number">1</div>
                <span>Sektör Seçimi</span>
            </div>
            <div class="step-line"></div>
            <div class="step-item active">
                <div class="step-number">2</div>
                <span>Önizleme & Yapılandırma</span>
            </div>
            <div class="step-line"></div>
            <div class="step-item">
                <div class="step-number">3</div>
                <span>Tek Tıkla Kurulum</span>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Industry Grid (11 Sectors) -->
            <div class="col-lg-7 col-xl-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 fw-bold mb-0">Desteklenen Sektörler (<?= count($blueprints) ?>)</h3>
                    <span class="text-muted small">Her paket 10 müşteri, 3-4 personel ve 15-20 randevu demosu içerir</span>
                </div>

                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" id="industry-cards-container">
                    <?php foreach ($blueprints as $bp): ?>
                        <?php
                            $type_class = 'type-duration';
                            $type_label = 'Süre Bazlı';
                            if ($bp['service_type'] === 'station') {
                                $type_class = 'type-station';
                                $type_label = 'İstasyon Bazlı';
                            } elseif ($bp['service_type'] === 'procedure') {
                                $type_class = 'type-procedure';
                                $type_label = 'İşlem / Eklenti';
                            } elseif ($bp['service_type'] === 'hybrid') {
                                $type_class = 'type-hybrid';
                                $type_label = 'Hibrit Model';
                            }
                        ?>
                        <div class="col">
                            <div class="industry-card <?= $bp['code'] === 'beauty_salon' ? 'selected' : '' ?>" 
                                 data-code="<?= e($bp['code']) ?>" 
                                 onclick="selectIndustry('<?= e($bp['code']) ?>')">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="industry-icon"><?= $bp['icon'] ?></div>
                                        <span class="service-type-badge <?= $type_class ?>"><?= $type_label ?></span>
                                    </div>
                                    <h4 class="h6 fw-bold mb-1 text-dark"><?= e($bp['name']) ?></h4>
                                    <p class="text-muted small mb-3" style="min-height: 40px; font-size: 0.825rem; line-height: 1.4;">
                                        <?= e(character_limiter($bp['description'], 90)) ?>
                                    </p>
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-slate-100 text-muted small" style="font-size: 0.75rem;">
                                        <span><i class="fas fa-list me-1"></i><?= $bp['service_count'] ?> Hizmet</span>
                                        <span><i class="fas fa-door-open me-1"></i><?= $bp['station_count'] ?> İstasyon</span>
                                        <span><i class="fas fa-users me-1"></i><?= $bp['provider_count'] ?> Uzman</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Column: Live Blueprint Preview & Setup Pane -->
            <div class="col-lg-5 col-xl-4">
                <div class="preview-pane">
                    <div id="preview-loading" class="text-center py-5" style="display: none;">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2 small">Şablon detayları yükleniyor...</p>
                    </div>

                    <div id="preview-content">
                        <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                            <span class="fs-1" id="pv-icon">✨</span>
                            <div>
                                <h3 class="h5 fw-bold mb-0 text-dark" id="pv-name">Güzellik Salonu</h3>
                                <span class="service-type-badge type-hybrid" id="pv-type-badge">Hibrit Model</span>
                            </div>
                        </div>

                        <p class="text-muted small mb-3" id="pv-desc">
                            Cilt bakımı, lazer epilasyon, kalıcı makyaj ve bölgesel incelme için komple yönetim paketi.
                        </p>

                        <!-- Terminology mapping -->
                        <div class="mb-3">
                            <label class="form-label text-uppercase text-muted fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Terminoloji Uyarlaması</label>
                            <div class="bg-light p-2 rounded-3 small">
                                <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle">
                                    <span class="text-muted">Müşteri:</span>
                                    <strong class="text-primary" id="term-customer">Danışan</strong>
                                </div>
                                <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle">
                                    <span class="text-muted">Personel:</span>
                                    <strong class="text-primary" id="term-provider">Uzman Estetisyen</strong>
                                </div>
                                <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle">
                                    <span class="text-muted">Randevu:</span>
                                    <strong class="text-primary" id="term-appointment">Seans / Randevu</strong>
                                </div>
                                <div class="d-flex justify-content-between py-1">
                                    <span class="text-muted">İstasyon:</span>
                                    <strong class="text-primary" id="term-station">Kabin / Cihaz</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Active Modules -->
                        <div class="mb-3">
                            <label class="form-label text-uppercase text-muted fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Aktifleşecek Modüller</label>
                            <div id="pv-modules" class="d-flex flex-wrap"></div>
                        </div>

                        <!-- Included Services Preview -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label text-uppercase text-muted fw-bold mb-0" style="font-size: 0.7rem; letter-spacing: 0.05em;">Örnek Hizmetler</label>
                                <span class="badge bg-secondary-subtle text-secondary" id="pv-services-count">10 Hizmet</span>
                            </div>
                            <ul class="list-group list-group-flush small" id="pv-services-list" style="max-height: 160px; overflow-y: auto;"></ul>
                        </div>

                        <!-- Configuration Form -->
                        <form id="blueprint-apply-form" class="mt-4 pt-3 border-top">
                            <input type="hidden" name="industry_code" id="form-industry-code" value="beauty_salon">
                            
                            <div class="mb-3">
                                <label for="form-company-name" class="form-label small fw-semibold">İşletme Adınız</label>
                                <input type="text" class="form-control form-control-sm rounded-3" id="form-company-name" name="company_name" value="<?= e($company_name) ?>" placeholder="Örn: Flora Güzellik & Bakım">
                            </div>

                            <div class="form-check form-switch mb-4 p-2 bg-light rounded-3 d-flex align-items-center justify-content-between">
                                <label class="form-check-label small fw-semibold ms-2 mb-0" for="form-seed-demo">
                                    <i class="fas fa-magic text-warning me-1"></i> Demo Verisi Yükle
                                    <small class="d-block text-muted fw-normal" style="font-size: 0.72rem;">10 müşteri, 3-4 personel ve 15-20 randevu</small>
                                </label>
                                <input class="form-check-input ms-0" type="checkbox" role="switch" id="form-seed-demo" name="seed_demo" value="1" checked>
                            </div>

                            <button type="button" class="btn btn-primary w-100 py-2 rounded-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" id="btn-apply-blueprint" onclick="submitBlueprint()">
                                <i class="fas fa-rocket"></i>
                                <span>Şablonu Kur ve Başlat</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= asset_url('assets/vendor/jquery/jquery.min.js') ?>"></script>
    <script src="<?= asset_url('assets/vendor/@popperjs-core/popper.min.js') ?>"></script>
    <script src="<?= asset_url('assets/vendor/bootstrap/bootstrap.min.js') ?>"></script>
    <script src="<?= asset_url('assets/vendor/@fortawesome-fontawesome-free/fontawesome.min.js') ?>"></script>
    <script src="<?= asset_url('assets/vendor/@fortawesome-fontawesome-free/solid.min.js') ?>"></script>
    <script>
        const baseUrl = '<?= site_url() ?>';
        let currentSelectedCode = 'beauty_salon';

        $(document).ready(function() {
            selectIndustry('beauty_salon');
        });

        function selectIndustry(code) {
            currentSelectedCode = code;
            $('#form-industry-code').val(code);

            $('.industry-card').removeClass('selected');
            $(`.industry-card[data-code="${code}"]`).addClass('selected');

            $('#preview-loading').show();
            $('#preview-content').hide();

            $.getJSON(`${baseUrl}onboarding/get_blueprint_details?code=${code}`, function(data) {
                renderPreview(data);
                $('#preview-loading').hide();
                $('#preview-content').fadeIn(150);
            }).fail(function() {
                $('#preview-loading').hide();
                $('#preview-content').show();
                alert('Şablon detayları yüklenemedi.');
            });
        }

        function renderPreview(data) {
            $('#pv-icon').text(data.industry.icon || '🏢');
            $('#pv-name').text(data.industry.name);
            $('#pv-desc').text(data.industry.description);

            // Badge
            let typeBadge = 'Süre Bazlı';
            let badgeClass = 'type-duration';
            if (data.industry.service_type === 'station') {
                typeBadge = 'İstasyon Bazlı';
                badgeClass = 'type-station';
            } else if (data.industry.service_type === 'procedure') {
                typeBadge = 'İşlem / Eklenti';
                badgeClass = 'type-procedure';
            } else if (data.industry.service_type === 'hybrid') {
                typeBadge = 'Hibrit Model';
                badgeClass = 'type-hybrid';
            }
            $('#pv-type-badge').text(typeBadge).attr('class', `service-type-badge ${badgeClass}`);

            // Terminology
            if (data.terminology) {
                $('#term-customer').text(data.terminology.customer_label || 'Müşteri');
                $('#term-provider').text(data.terminology.provider_label || 'Personel');
                $('#term-appointment').text(data.terminology.appointment_label || 'Randevu');
                $('#term-station').text(data.terminology.station_label || 'İstasyon');
            }

            // Modules
            const modulesContainer = $('#pv-modules');
            modulesContainer.empty();
            if (data.enabled_modules) {
                const moduleLabels = {
                    'appointments': 'Randevular',
                    'calendar': 'Akıllı Takvim',
                    'stations': 'İstasyon & Oda',
                    'packages': 'Paket Seanslar',
                    'memberships': 'Üyelikler',
                    'adisyon': 'Adisyon & Sipariş',
                    'finance': 'Kasa & Finans',
                    'expenses': 'Gider Takibi',
                    'checkin': 'Check-In / Out',
                    'inventory': 'Stok & Sarf',
                    'reviews': 'Müşteri Yorumları',
                    'marketing': 'Pazarlama & SMS',
                    'loyalty': 'Sadakat Puanı',
                    'staff_commissions': 'Personel Primi',
                    'invoices': 'e-Fatura',
                    'restaurant_floor_plan': 'Masa Planı'
                };
                data.enabled_modules.forEach(m => {
                    const label = moduleLabels[m] || m;
                    modulesContainer.append(`<span class="tag-pill"><i class="fas fa-check text-success me-1"></i>${label}</span>`);
                });
            }

            // Services
            const servicesList = $('#pv-services-list');
            servicesList.empty();
            if (data.services) {
                $('#pv-services-count').text(`${data.services.length} Hizmet`);
                data.services.forEach(s => {
                    servicesList.append(`
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1 bg-transparent border-light-subtle">
                            <span class="text-truncate" style="max-width: 200px;"><i class="fas fa-circle me-1" style="color: ${s.color || '#3b82f6'}; font-size: 8px;"></i> ${s.name}</span>
                            <span class="fw-semibold text-dark">${s.price} ₺ <span class="text-muted fw-normal">(${s.duration} dk)</span></span>
                        </li>
                    `);
                });
            }
        }

        function submitBlueprint() {
            const btn = $('#btn-apply-blueprint');
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span> Kurulum Yapılıyor...');

            const formData = $('#blueprint-apply-form').serialize();

            $.post(`${baseUrl}onboarding/apply`, formData, function(response) {
                if (response.success) {
                    btn.removeClass('btn-primary').addClass('btn-success').html('<i class="fas fa-check me-2"></i> Başarıyla Kuruldu! Yönlendiriliyor...');
                    setTimeout(function() {
                        window.location.href = response.redirect_url || `${baseUrl}calendar`;
                    }, 1200);
                } else {
                    btn.prop('disabled', false).html(originalHtml);
                    alert(response.message || 'Kurulum sırasında bir hata oluştu.');
                }
            }, 'json').fail(function(xhr) {
                btn.prop('disabled', false).html(originalHtml);
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Kurulum başarısız oldu.';
                alert(msg);
            });
        }
    </script>
</body>
</html>
