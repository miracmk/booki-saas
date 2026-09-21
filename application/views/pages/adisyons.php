<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>
<?php
/**
 * @var array $adisyons
 * @var string $status_filter
 * @var string $payment_filter
 * @var int $open_count
 * @var float $unpaid_total
 * @var float $today_revenue
 * @var array $available_services
 * @var array $available_products
 * @var array $customers
 * @var array $staff_members
 * @var array $active_appointments
 * @var array $erp_providers
 * @var string $active_erp_provider
 * @var string|null $open_id
 */
?>
<div class="container-fluid py-3" id="adisyons-page">
    <!-- Header with KPIs & Actions -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-receipt text-primary me-2"></i>Adisyon & Hesap Yönetimi</h4>
            <p class="text-muted small mb-0">Rezervasyon ve hizmet adisyonlarını yönetin, anında tahsilat yapın, tekil veya toplu faturalandırıp ERP'ye aktarın.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" onclick="window.location.reload();">
                <i class="fas fa-sync-alt me-1"></i> Yenile
            </button>
            <button class="btn btn-primary" id="btn-open-new-adisyon-modal" data-bs-toggle="modal" data-bs-target="#new-adisyon-modal">
                <i class="fas fa-plus me-1"></i> Yeni Adisyon Aç
            </button>
        </div>
    </div>

    <!-- Quick Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block">Açık Adisyonlar</span>
                        <h4 class="fw-bold mb-0 text-primary"><?= number_format($open_count ?? 0) ?></h4>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary">
                        <i class="fas fa-folder-open fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block">Tahsil Edilmemiş Tutar</span>
                        <h4 class="fw-bold mb-0 text-danger"><?= number_format($unpaid_total ?? 0, 2) ?> ₺</h4>
                    </div>
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 text-danger">
                        <i class="fas fa-exclamation-circle fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block">Bugünkü Tahsilat</span>
                        <h4 class="fw-bold mb-0 text-success"><?= number_format($today_revenue ?? 0, 2) ?> ₺</h4>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success">
                        <i class="fas fa-cash-register fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block">Hızlı İşlemler</span>
                        <div class="mt-1">
                            <a href="<?= site_url('finance') ?>" class="btn btn-sm btn-outline-dark me-1">Kasa / Finans</a>
                            <a href="<?= site_url('invoices') ?>" class="btn btn-sm btn-outline-primary">Faturalar</a>
                        </div>
                    </div>
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 text-info">
                        <i class="fas fa-bolt fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Selection Floating Bar (Hidden by default) -->
    <div id="bulk-action-bar" class="card border-0 shadow-lg rounded-3 mb-3 bg-dark text-white d-none">
        <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="badge bg-primary fs-6 px-3 py-2 rounded-pill" id="bulk-selected-count">0 Adisyon Seçildi</div>
                <div class="text-light small">
                    Toplam Tutar: <strong id="bulk-selected-total" class="text-warning fs-6">0.00 ₺</strong>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-warning text-dark fw-bold btn-sm px-3" onclick="openBulkInvoiceModal()">
                    <i class="fas fa-file-invoice-dollar me-1"></i> Seçilenleri Faturalandır & ERP'ye Aktar
                </button>
                <button type="button" class="btn btn-outline-light btn-sm" onclick="deselectAllAdisyons()">
                    <i class="fas fa-times me-1"></i> Vazgeç
                </button>
            </div>
        </div>
    </div>

    <!-- Filter & Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <!-- Filter Bar -->
            <div class="p-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3 bg-light bg-opacity-50">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="fw-bold small text-muted"><i class="fas fa-filter me-1"></i>Filtreler:</span>
                    <a href="<?= site_url('adisyons') ?>" class="btn btn-sm <?= ($status_filter === 'all' && $payment_filter === 'all') ? 'btn-dark' : 'btn-outline-secondary' ?>">Tümü</a>
                    <a href="<?= site_url('adisyons?status=open') ?>" class="btn btn-sm <?= $status_filter === 'open' ? 'btn-primary' : 'btn-outline-primary' ?>">Açıklar (<?= $open_count ?? 0 ?>)</a>
                    <a href="<?= site_url('adisyons?payment_status=unpaid') ?>" class="btn btn-sm <?= $payment_filter === 'unpaid' ? 'btn-danger' : 'btn-outline-danger' ?>">Ödenmemiş</a>
                    <a href="<?= site_url('adisyons?payment_status=paid') ?>" class="btn btn-sm <?= $payment_filter === 'paid' ? 'btn-success' : 'btn-outline-success' ?>">Ödenmiş</a>
                </div>
                <div class="input-group input-group-sm" style="max-width: 250px;">
                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                    <input type="text" id="adisyon-search-input" class="form-control" placeholder="Adisyon no veya müşteri ara...">
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="adisyons-table">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th class="ps-3" style="width: 40px;">
                                <input type="checkbox" class="form-check-input" id="select-all-adisyons" title="Tümünü Seç">
                            </th>
                            <th>Adisyon No</th>
                            <th>Müşteri / Masa</th>
                            <th>Sorumlu Personel</th>
                            <th>Durum</th>
                            <th>Ödeme Durumu</th>
                            <th>Fatura Durumu</th>
                            <th>Toplam Tutar</th>
                            <th>Tahsil Edilen</th>
                            <th>Tarih / Saat</th>
                            <th class="text-end pe-3"><?= lang('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($adisyons)): ?>
                            <tr>
                                <td colspan="11" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-receipt fa-3x mb-3 text-secondary opacity-50"></i>
                                        <h6>Henüz gösterilecek adisyon bulunmuyor.</h6>
                                        <p class="small mb-3">Yeni bir randevu tamamlandığında veya manuel adisyon açıldığında burada listelenir.</p>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#new-adisyon-modal">
                                             <i class="fas fa-plus me-1"></i> Yeni Adisyon Aç
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($adisyons as $ad): ?>
                                <tr>
                                    <td class="ps-3">
                                        <input type="checkbox" class="form-check-input adisyon-select-box" 
                                               value="<?= $ad['id'] ?>" 
                                               data-amount="<?= $ad['total_amount'] ?>" 
                                               data-number="<?= e($ad['adisyon_number']) ?>" 
                                               data-customer="<?= e($ad['customer_first_name'] ? ($ad['customer_first_name'] . ' ' . $ad['customer_last_name']) : 'Misafir') ?>" 
                                               data-invoice-status="<?= $ad['invoice_status'] ?>">
                                    </td>
                                    <td class="fw-bold">
                                        <a href="javascript:void(0)" onclick="openAdisyonDrawer(<?= $ad['id'] ?>)" class="text-primary text-decoration-none">
                                            <?= e($ad['adisyon_number']) ?>
                                        </a>
                                        <?php if (!empty($ad['id_appointments'])): ?>
                                            <span class="badge bg-light text-dark border ms-1" title="Randevuya Bağlı"><i class="fas fa-calendar-alt text-primary"></i> #<?= $ad['id_appointments'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($ad['customer_first_name'])): ?>
                                            <div class="fw-semibold"><?= e($ad['customer_first_name'] . ' ' . $ad['customer_last_name']) ?></div>
                                            <small class="text-muted"><?= e($ad['customer_phone'] ?: '') ?></small>
                                        <?php elseif (!empty($ad['table_number'])): ?>
                                            <span class="badge bg-secondary"><i class="fas fa-chair me-1"></i>Masa <?= e($ad['table_number']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">İsimsiz Müşteri</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= !empty($ad['staff_first_name']) ? e($ad['staff_first_name'] . ' ' . $ad['staff_last_name']) : '<span class="text-muted">-</span>' ?>
                                    </td>
                                    <td>
                                        <?php if ($ad['status'] === 'open'): ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1"><i class="fas fa-folder-open me-1"></i>Açık</span>
                                        <?php elseif ($ad['status'] === 'closed'): ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1"><i class="fas fa-check-circle me-1"></i>Kapatıldı</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border px-2 py-1"><?= e($ad['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($ad['payment_status'] === 'paid'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1"><i class="fas fa-check me-1"></i>Ödendi</span>
                                        <?php elseif ($ad['payment_status'] === 'partially_paid'): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1"><i class="fas fa-adjust me-1"></i>Kısmi</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1"><i class="fas fa-times me-1"></i>Ödenmedi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($ad['invoice_status'] === 'invoiced'): ?>
                                            <a href="<?= site_url('invoices') ?>" class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1 text-decoration-none" title="Faturalandırıldı">
                                                <i class="fas fa-file-invoice me-1"></i>Faturalı <?= !empty($ad['id_invoices']) ? '#' . $ad['id_invoices'] : '' ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border px-2 py-1">Faturalanmadı</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold">
                                        <?= number_format($ad['total_amount'], 2) ?> ₺
                                    </td>
                                    <td class="text-success fw-semibold">
                                        <?= number_format($ad['paid_amount'], 2) ?> ₺
                                    </td>
                                    <td class="small text-muted">
                                        <?= date('d.m.Y H:i', strtotime($ad['opened_at'])) ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-primary py-1 px-2" onclick="openAdisyonDrawer(<?= $ad['id'] ?>)">
                                                <i class="fas fa-edit me-1"></i> Detay
                                            </button>
                                            <button type="button" class="btn btn-outline-info py-1 px-2" title="Faturalandır" onclick="openSingleInvoiceModal(<?= $ad['id'] ?>, '<?= e($ad['adisyon_number']) ?>', '<?= e($ad['customer_first_name'] ? ($ad['customer_first_name'] . ' ' . $ad['customer_last_name']) : 'Misafir') ?>', <?= $ad['total_amount'] ?>)">
                                                <i class="fas fa-file-invoice"></i>
                                            </button>
                                        </div>
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

<!-- Modal: New Adisyon (Appointment Fast Link + Manual Selection) -->
<div class="modal fade" id="new-adisyon-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle text-primary me-2"></i>Yeni Adisyon Aç</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Nav tabs -->
                <ul class="nav nav-tabs nav-fill bg-light px-3 pt-2" id="newAdisyonTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="from-appointment-tab" data-bs-toggle="tab" data-bs-target="#from-appointment-pane" type="button" role="tab">
                            <i class="fas fa-calendar-check text-primary me-1"></i> Randevudan / Rezervasyondan Seç
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="manual-adisyon-tab" data-bs-toggle="tab" data-bs-target="#manual-adisyon-pane" type="button" role="tab">
                            <i class="fas fa-user-plus text-success me-1"></i> Doğrudan Manuel Aç
                        </button>
                    </li>
                </ul>

                <div class="tab-content p-4" id="newAdisyonTabContent">
                    <!-- Tab 1: From Appointment -->
                    <div class="tab-pane fade show active" id="from-appointment-pane" role="tabpanel">
                        <p class="text-muted small mb-3">
                            Rezervasyon yapılan randevuyu seçtiğinizde müşteri, hizmet ve sorumlu personel bilgileri adisyona otomatik aktarılır.
                        </p>
                        
                        <?php if (empty($active_appointments)): ?>
                            <div class="alert alert-info py-3 mb-0 text-center">
                                <i class="fas fa-info-circle me-1"></i> Son günlerde kayıtlı açık randevu bulunamadı. Doğrudan manuel adisyon açabilirsiniz.
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Randevu Seçin</label>
                                <select id="appointment-picker-select" class="form-select form-select-lg" onchange="handleAppointmentPickerChange(this)">
                                    <option value="">-- Randevu Listesinden Seçin --</option>
                                    <?php foreach ($active_appointments as $apt): 
                                        $cust_name = trim(($apt['customer_first_name'] ?? '') . ' ' . ($apt['customer_last_name'] ?? '')) ?: 'Misafir';
                                        $prov_name = trim(($apt['provider_first_name'] ?? '') . ' ' . ($apt['provider_last_name'] ?? '')) ?: 'Personel';
                                        $srv_name = $apt['service_name'] ?: 'Hizmet';
                                        $srv_price = (float) ($apt['service_price'] ?? 0);
                                        $apt_time = date('d.m.Y H:i', strtotime($apt['start_datetime']));
                                    ?>
                                        <option value="<?= $apt['id'] ?>" 
                                                data-customer="<?= e($cust_name) ?>" 
                                                data-phone="<?= e($apt['customer_phone'] ?? '') ?>" 
                                                data-service="<?= e($srv_name) ?>" 
                                                data-price="<?= $srv_price ?>" 
                                                data-provider="<?= e($prov_name) ?>" 
                                                data-datetime="<?= $apt_time ?>">
                                            #<?= $apt['id'] ?> - <?= e($cust_name) ?> | <?= e($srv_name) ?> (<?= number_format($srv_price, 2) ?> ₺) - <?= $apt_time ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Preview Card -->
                            <div id="appointment-preview-card" class="card border border-primary bg-primary bg-opacity-10 rounded-3 p-3 d-none">
                                <div class="row align-items-center">
                                    <div class="col-md-7">
                                        <h6 class="fw-bold mb-1 text-primary" id="prev-customer-name">Müşteri Adı</h6>
                                        <div class="small text-muted mb-1"><i class="fas fa-cut me-1"></i>Hizmet: <span id="prev-service-name" class="fw-semibold text-dark">-</span></div>
                                        <div class="small text-muted"><i class="fas fa-user-tie me-1"></i>Personel: <span id="prev-provider-name" class="fw-semibold text-dark">-</span></div>
                                    </div>
                                    <div class="col-md-5 text-md-end mt-2 mt-md-0">
                                        <div class="small text-muted">Hizmet Tutarı</div>
                                        <div class="fs-4 fw-bold text-success" id="prev-service-price">0.00 ₺</div>
                                        <div class="small text-muted" id="prev-datetime">-</div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab 2: Manual Adisyon -->
                    <div class="tab-pane fade" id="manual-adisyon-pane" role="tabpanel">
                        <form id="new-adisyon-form">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Müşteri Seçin (İsteğe Bağlı)</label>
                                <select name="id_users_customer" class="form-select">
                                    <option value="">-- Genel / Misafir Müşteri --</option>
                                    <?php
                                    $cust_list = $customers ?? [];
                                    foreach ($cust_list as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= e($c['first_name'] . ' ' . $c['last_name']) ?> (<?= e($c['phone_number'] ?? '') ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Sorumlu Personel</label>
                                <select name="id_users_staff" class="form-select">
                                    <option value="">-- Personel Seçin --</option>
                                    <?php
                                    $staff_list = $staff_members ?? [];
                                    foreach ($staff_list as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" id="btn-submit-appointment-adisyon" class="btn btn-primary" onclick="submitAppointmentAdisyon()">
                    <i class="fas fa-check me-1"></i> Adisyonu Başlat & Detaya Git
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Invoicing & ERP Dispatch (Single & Bulk) -->
<div class="modal fade" id="invoice-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-invoice text-primary me-2"></i>Adisyonu Faturalandır</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="invoice-summary-box" class="alert alert-light border p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">İşlenecek Adisyon:</span>
                        <strong id="invoice-target-adisyons" class="text-dark">-</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Müşteri / Alıcı:</span>
                        <span id="invoice-target-customer" class="fw-semibold text-dark">-</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <span class="fw-bold">Toplam Fatura Tutarı:</span>
                        <span id="invoice-target-total" class="fw-bold text-primary fs-5">0.00 ₺</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Fatura Türü</label>
                    <select id="invoice-doc-type" class="form-select form-select-sm">
                        <option value="e-arsiv">e-Arşiv Fatura (Bireysel / Nihai Tüketici)</option>
                        <option value="e-fatura">e-Fatura (Kurumsal Vergi Mükellefi)</option>
                        <option value="standard">Standart Satış Faturası / İç Kayıt</option>
                    </select>
                </div>

                <div class="card border-primary border-opacity-25 bg-primary bg-opacity-10 p-3 rounded-3 mb-3">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="invoice-send-erp-toggle" checked onchange="toggleErpProviderSelect(this)">
                        <label class="form-check-label fw-bold text-dark" for="invoice-send-erp-toggle">
                            <i class="fas fa-cloud-upload-alt text-primary me-1"></i> ERP / Muhasebe Sistemine Gönder
                        </label>
                    </div>
                    <p class="small text-muted mb-2">
                        Fatura oluşturulduktan sonra tanımlı muhasebe & ERP sisteminize (Paraşüt, Logo, Mikro vb.) e-fatura/e-arşiv olarak senkronize edilir.
                    </p>
                    <div id="erp-provider-group">
                        <label class="form-label small fw-bold text-muted">ERP Sağlayıcısı</label>
                        <select id="invoice-erp-provider-select" class="form-select form-select-sm">
                            <?php 
                            $providers = $erp_providers ?? [
                                'parasut' => 'Paraşüt (Mikro Grup)',
                                'logo' => 'Logo Yazılım (Logo İşbaşı / ERP)',
                                'mikro' => 'Mikro Yazılım / Run / Jump / Fly',
                                'isbasi' => 'Logo İşbaşı',
                                'quickbooks' => 'QuickBooks Online',
                                'zohobooks' => 'Zoho Books',
                            ];
                            $active_prov = $active_erp_provider ?? 'parasut';
                            foreach ($providers as $key => $title): ?>
                                <option value="<?= $key ?>" <?= $key === $active_prov ? 'selected' : '' ?>>
                                    <?= e($title) ?> <?= $key === $active_prov ? '(Varsayılan Aktif)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" id="btn-confirm-invoicing" class="btn btn-primary px-3" onclick="executeInvoicing()">
                    <i class="fas fa-check-double me-1"></i> Faturalandır & Onayla
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Adisyon Detail & Checkout Offcanvas Drawer -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="adisyon-drawer" style="width: 650px; max-width: 100%;">
    <div class="offcanvas-header border-bottom bg-light">
        <div>
            <h5 class="offcanvas-title fw-bold" id="drawer-adisyon-title">Adisyon Detayı</h5>
            <span class="badge bg-primary" id="drawer-adisyon-badge">Açık</span>
            <span class="badge bg-light text-dark border ms-1" id="drawer-invoice-badge">Faturalanmadı</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0">
        <!-- Customer Context Box -->
        <div class="p-3 border-bottom bg-light bg-opacity-25" id="drawer-customer-context">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-0 fw-bold" id="drawer-customer-name">Misafir Müşteri</h6>
                    <small class="text-muted" id="drawer-customer-phone">-</small>
                </div>
                <div class="text-end">
                    <span class="badge bg-warning text-dark d-none" id="drawer-customer-vip">VIP</span>
                </div>
            </div>
        </div>

        <!-- Add Items Form Section -->
        <div class="p-3 border-bottom">
            <h6 class="fw-bold small mb-2"><i class="fas fa-plus-circle text-primary me-1"></i>Hizmet / Ürün Ekle</h6>
            <div class="row g-2">
                <div class="col-7">
                    <select id="item-selector" class="form-select form-select-sm" onchange="handleItemSelect(this)">
                        <option value="">-- Hizmet veya Ürün Seçin --</option>
                        <optgroup label="Hizmetler">
                            <?php foreach ($available_services as $s): ?>
                                <option value="service-<?= $s['id'] ?>" data-type="service" data-id="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>" data-price="<?= $s['price'] ?>">
                                    <?= e($s['name']) ?> (<?= number_format($s['price'], 2) ?> ₺)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Ürünler">
                            <?php foreach ($available_products as $p): 
                                $prod_price = $p['sale_price'] ?? $p['price'] ?? 0;
                            ?>
                                <option value="product-<?= $p['id'] ?>" data-type="product" data-id="<?= $p['id'] ?>" data-name="<?= e($p['name']) ?>" data-price="<?= $prod_price ?>">
                                    <?= e($p['name']) ?> (<?= number_format($prod_price, 2) ?> ₺ - Stok: <?= $p['stock_quantity'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="col-2">
                    <input type="number" id="item-quantity" class="form-control form-control-sm" value="1" min="1" step="1" placeholder="Adet">
                </div>
                <div class="col-3">
                    <button type="button" class="btn btn-sm btn-primary w-100" onclick="addItemToAdisyon()">
                        <i class="fas fa-plus"></i> Ekle
                    </button>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="flex-grow-1 overflow-auto p-3">
            <h6 class="fw-bold small mb-2 text-muted">Adisyon Kalemleri</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle" id="drawer-items-table">
                    <thead class="table-light small">
                        <tr>
                            <th>Kalem</th>
                            <th class="text-center">Adet</th>
                            <th class="text-end">Birim</th>
                            <th class="text-end">Toplam</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="drawer-items-tbody"></tbody>
                </table>
            </div>
        </div>

        <!-- Summary & Checkout Footer -->
        <div class="p-3 border-top bg-light">
            <div class="d-flex justify-content-between mb-1 small text-muted">
                <span>Ara Toplam:</span>
                <span id="drawer-subtotal">0.00 ₺</span>
            </div>
            <div class="d-flex justify-content-between mb-1 small text-muted">
                <span>KDV:</span>
                <span id="drawer-tax">0.00 ₺</span>
            </div>
            <div class="d-flex justify-content-between mb-2 fw-bold fs-5 text-dark">
                <span>GENEL TOPLAM:</span>
                <span id="drawer-total" class="text-primary">0.00 ₺</span>
            </div>
            <div class="d-flex justify-content-between mb-3 small text-success fw-semibold">
                <span>Tahsil Edilen:</span>
                <span id="drawer-paid">0.00 ₺</span>
            </div>

            <!-- Quick Action Buttons -->
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-success flex-grow-1" onclick="showPaymentModal()">
                    <i class="fas fa-credit-card me-1"></i> Ödeme Al / Tahsilat
                </button>
                <button type="button" class="btn btn-info text-white" onclick="openDrawerInvoiceModal()">
                    <i class="fas fa-file-invoice me-1"></i> Faturalandır
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="printSlipFromDrawer()">
                    <i class="fas fa-print me-1"></i> Fiş Yazdır
                </button>
                <button type="button" class="btn btn-dark" onclick="closeAdisyonFromDrawer()">
                    <i class="fas fa-check-circle me-1"></i> Kapat
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Payment / Checkout -->
<div class="modal fade" id="payment-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-cash-register text-success me-2"></i>Tahsilat Yap</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Ödenecek Tutar (₺)</label>
                    <input type="number" id="payment-amount" class="form-control fs-4 fw-bold text-success" step="0.01">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Ödeme Yöntemi</label>
                    <div class="row g-2">
                        <div class="col-4">
                            <button type="button" class="btn btn-outline-primary w-100 py-2 payment-method-btn active" data-method="cash" onclick="selectPaymentMethod('cash', this)">
                                <i class="fas fa-money-bill-wave d-block mb-1"></i> Nakit
                            </button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn btn-outline-primary w-100 py-2 payment-method-btn" data-method="card" onclick="selectPaymentMethod('card', this)">
                                <i class="fas fa-credit-card d-block mb-1"></i> Kredi Kartı
                            </button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn btn-outline-primary w-100 py-2 payment-method-btn" data-method="bank_transfer" onclick="selectPaymentMethod('bank_transfer', this)">
                                <i class="fas fa-university d-block mb-1"></i> Havale/EFT
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Package / Membership Deduction Section -->
                <div id="package-deduction-group" class="mb-3 d-none">
                    <label class="form-label small fw-bold text-success"><i class="fas fa-box me-1"></i>Aktif Paket ile Seans Düş</label>
                    <select id="payment-package-select" class="form-select form-select-sm"></select>
                </div>
                <div id="membership-deduction-group" class="mb-3 d-none">
                    <label class="form-label small fw-bold text-info"><i class="fas fa-id-card me-1"></i>Aktif Üyelik ile Düş</label>
                    <select id="payment-membership-select" class="form-select form-select-sm"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-success" onclick="submitPayment()">Tahsilatı Tamamla</button>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script>
let currentAdisyonId = null;
let currentAdisyonData = null;
let selectedPaymentMethod = 'cash';
let invoicingAdisyonIds = [];

// Initialize auto-open if passed from backend redirect
document.addEventListener('DOMContentLoaded', function() {
    const autoOpenId = <?= !empty($open_id) ? (int) $open_id : 'null' ?>;
    if (autoOpenId) {
        openAdisyonDrawer(autoOpenId);
    }

    // Bind Select All Checkbox
    const selectAll = document.getElementById('select-all-adisyons');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const boxes = document.querySelectorAll('.adisyon-select-box');
            boxes.forEach(b => b.checked = selectAll.checked);
            updateBulkActionBar();
        });
    }

    // Bind Individual Checkboxes
    document.querySelectorAll('.adisyon-select-box').forEach(b => {
        b.addEventListener('change', updateBulkActionBar);
    });

    // Search filter
    const searchInput = document.getElementById('adisyon-search-input');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const val = this.value.toLowerCase();
            document.querySelectorAll('#adisyons-table tbody tr').forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(val) ? '' : 'none';
            });
        });
    }
});

function updateBulkActionBar() {
    const selectedBoxes = Array.from(document.querySelectorAll('.adisyon-select-box:checked'));
    const bar = document.getElementById('bulk-action-bar');
    if (selectedBoxes.length > 0) {
        bar.classList.remove('d-none');
        document.getElementById('bulk-selected-count').innerText = selectedBoxes.length + ' Adisyon Seçildi';
        
        let total = 0;
        selectedBoxes.forEach(b => {
            total += parseFloat(b.dataset.amount || 0);
        });
        document.getElementById('bulk-selected-total').innerText = total.toFixed(2) + ' ₺';
    } else {
        bar.classList.add('d-none');
        const selectAll = document.getElementById('select-all-adisyons');
        if (selectAll) selectAll.checked = false;
    }
}

function deselectAllAdisyons() {
    document.querySelectorAll('.adisyon-select-box').forEach(b => b.checked = false);
    const selectAll = document.getElementById('select-all-adisyons');
    if (selectAll) selectAll.checked = false;
    updateBulkActionBar();
}

function handleAppointmentPickerChange(select) {
    const opt = select.options[select.selectedIndex];
    const card = document.getElementById('appointment-preview-card');
    if (!opt || !opt.value) {
        card.classList.add('d-none');
        return;
    }

    document.getElementById('prev-customer-name').innerText = opt.dataset.customer || 'Misafir';
    document.getElementById('prev-service-name').innerText = opt.dataset.service || '-';
    document.getElementById('prev-provider-name').innerText = opt.dataset.provider || '-';
    document.getElementById('prev-service-price').innerText = parseFloat(opt.dataset.price || 0).toFixed(2) + ' ₺';
    document.getElementById('prev-datetime').innerText = opt.dataset.datetime || '';
    card.classList.remove('d-none');
}

function submitAppointmentAdisyon() {
    const activeTab = document.querySelector('#newAdisyonTab .nav-link.active');
    if (activeTab && activeTab.id === 'from-appointment-tab') {
        const picker = document.getElementById('appointment-picker-select');
        const aptId = picker ? picker.value : null;
        if (!aptId) {
            alert('Lütfen adisyon oluşturmak için bir randevu seçin.');
            return;
        }
        window.location.href = '<?= site_url('adisyons/create_for_appointment/') ?>' + aptId;
    } else {
        submitNewAdisyon();
    }
}

function submitNewAdisyon() {
    const form = document.getElementById('new-adisyon-form');
    const fd = new FormData(form);

    fetch('<?= site_url('adisyons/create') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const modalEl = document.getElementById('new-adisyon-modal');
                const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                if (modal) modal.hide();
                if (data.adisyon && data.adisyon.id) {
                    openAdisyonDrawer(data.adisyon.id);
                } else {
                    window.location.reload();
                }
            } else {
                alert(data.message || 'Oluşturulamadı.');
            }
        });
}

function openSingleInvoiceModal(adisyonId, adisyonNumber, customerName, totalAmount) {
    invoicingAdisyonIds = [adisyonId];
    document.getElementById('invoice-target-adisyons').innerText = '#' + adisyonNumber;
    document.getElementById('invoice-target-customer').innerText = customerName || 'Misafir';
    document.getElementById('invoice-target-total').innerText = parseFloat(totalAmount).toFixed(2) + ' ₺';

    const modal = new bootstrap.Modal(document.getElementById('invoice-modal'));
    modal.show();
}

function openDrawerInvoiceModal() {
    if (!currentAdisyonData) return;
    const ad = currentAdisyonData.adisyon;
    const custName = ad.customer_first_name ? (ad.customer_first_name + ' ' + (ad.customer_last_name || '')) : 'Misafir';
    openSingleInvoiceModal(ad.id, ad.adisyon_number, custName, ad.total_amount);
}

function openBulkInvoiceModal() {
    const selectedBoxes = Array.from(document.querySelectorAll('.adisyon-select-box:checked'));
    if (selectedBoxes.length === 0) {
        alert('Lütfen faturalandırmak için en az bir adisyon seçin.');
        return;
    }

    invoicingAdisyonIds = selectedBoxes.map(b => parseInt(b.value));
    const numbers = selectedBoxes.map(b => '#' + b.dataset.number).join(', ');
    let total = 0;
    selectedBoxes.forEach(b => total += parseFloat(b.dataset.amount || 0));

    document.getElementById('invoice-target-adisyons').innerText = selectedBoxes.length + ' Adet (' + (numbers.length > 50 ? numbers.substring(0, 50) + '...' : numbers) + ')';
    document.getElementById('invoice-target-customer').innerText = 'Toplu Adisyon Seçimi (' + selectedBoxes.length + ' Müşteri)';
    document.getElementById('invoice-target-total').innerText = total.toFixed(2) + ' ₺';

    const modal = new bootstrap.Modal(document.getElementById('invoice-modal'));
    modal.show();
}

function toggleErpProviderSelect(toggle) {
    const group = document.getElementById('erp-provider-group');
    if (group) {
        group.style.display = toggle.checked ? 'block' : 'none';
    }
}

function executeInvoicing() {
    if (!invoicingAdisyonIds || invoicingAdisyonIds.length === 0) {
        alert('Faturalandırılacak adisyon bulunamadı.');
        return;
    }

    const sendToErp = document.getElementById('invoice-send-erp-toggle').checked;
    const erpProvider = document.getElementById('invoice-erp-provider-select').value;
    const btn = document.getElementById('btn-confirm-invoicing');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Faturalandırılıyor...';
    btn.disabled = true;

    if (invoicingAdisyonIds.length === 1) {
        // Single invoicing
        const adId = invoicingAdisyonIds[0];
        const fd = new FormData();
        fd.append('send_to_erp', sendToErp ? '1' : '0');
        fd.append('erp_provider', erpProvider);

        fetch('<?= site_url('adisyons/create_invoice/') ?>' + adId, { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                if (data.status === 'success') {
                    bootstrap.Modal.getInstance(document.getElementById('invoice-modal')).hide();
                    alert(data.message || 'Fatura başarıyla oluşturuldu!');
                    window.location.reload();
                } else {
                    alert(data.message || 'Fatura oluşturulamadı.');
                }
            })
            .catch(err => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('İşlem sırasında hata oluştu.');
            });
    } else {
        // Bulk invoicing
        const fd = new FormData();
        invoicingAdisyonIds.forEach(id => fd.append('adisyon_ids[]', id));
        fd.append('send_to_erp', sendToErp ? '1' : '0');
        fd.append('erp_provider', erpProvider);

        fetch('<?= site_url('adisyons/bulk_create_invoices') ?>', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                if (data.status === 'success') {
                    bootstrap.Modal.getInstance(document.getElementById('invoice-modal')).hide();
                    alert(data.message || 'Seçilen adisyonlar başarıyla faturalandırıldı!');
                    window.location.reload();
                } else {
                    alert(data.message || 'Toplu faturalandırma sırasında hata oluştu.');
                }
            })
            .catch(err => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('İşlem sırasında hata oluştu.');
            });
    }
}

function openAdisyonDrawer(id) {
    currentAdisyonId = id;
    fetch('<?= site_url('adisyons/get_details/') ?>' + id)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                currentAdisyonData = data;
                renderAdisyonDrawer(data);
                const drawerEl = document.getElementById('adisyon-drawer');
                const bsDrawer = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);
                bsDrawer.show();
            }
        });
}

function renderAdisyonDrawer(data) {
    const ad = data.adisyon;
    document.getElementById('drawer-adisyon-title').innerText = 'Adisyon #' + ad.adisyon_number;
    document.getElementById('drawer-adisyon-badge').innerText = ad.status === 'open' ? 'Açık' : 'Kapatıldı';
    document.getElementById('drawer-adisyon-badge').className = 'badge ' + (ad.status === 'open' ? 'bg-primary' : 'bg-secondary');

    const invBadge = document.getElementById('drawer-invoice-badge');
    if (ad.invoice_status === 'invoiced') {
        invBadge.className = 'badge bg-info text-white ms-1';
        invBadge.innerText = 'Faturalı ' + (ad.id_invoices ? '#' + ad.id_invoices : '');
    } else {
        invBadge.className = 'badge bg-light text-muted border ms-1';
        invBadge.innerText = 'Faturalanmadı';
    }

    if (ad.customer_first_name) {
        document.getElementById('drawer-customer-name').innerText = ad.customer_first_name + ' ' + (ad.customer_last_name || '');
        document.getElementById('drawer-customer-phone').innerText = ad.customer_phone || ad.customer_email || '';
    } else if (ad.table_number) {
        document.getElementById('drawer-customer-name').innerText = 'Masa ' + ad.table_number + ' (' + (ad.table_section || '') + ')';
        document.getElementById('drawer-customer-phone').innerText = 'Masa Adisyonu';
    } else {
        document.getElementById('drawer-customer-name').innerText = 'Genel Misafir';
        document.getElementById('drawer-customer-phone').innerText = '-';
    }

    // Render items
    const tbody = document.getElementById('drawer-items-tbody');
    tbody.innerHTML = '';
    (ad.items || []).forEach(it => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <span class="fw-semibold">${it.name}</span>
                ${it.staff_first_name ? `<br><small class="text-muted">${it.staff_first_name} ${it.staff_last_name || ''}</small>` : ''}
            </td>
            <td class="text-center">${parseFloat(it.quantity)}</td>
            <td class="text-end">${parseFloat(it.unit_price).toFixed(2)} ₺</td>
            <td class="text-end fw-bold">${parseFloat(it.total_amount).toFixed(2)} ₺</td>
            <td class="text-end">
                ${ad.status === 'open' ? `<button class="btn btn-sm btn-link text-danger p-0" onclick="removeItemFromAdisyon(${it.id})"><i class="fas fa-trash-alt"></i></button>` : ''}
            </td>
        `;
        tbody.appendChild(tr);
    });

    document.getElementById('drawer-subtotal').innerText = parseFloat(ad.subtotal).toFixed(2) + ' ₺';
    document.getElementById('drawer-tax').innerText = parseFloat(ad.tax_amount).toFixed(2) + ' ₺';
    document.getElementById('drawer-total').innerText = parseFloat(ad.total_amount).toFixed(2) + ' ₺';
    document.getElementById('drawer-paid').innerText = parseFloat(ad.paid_amount).toFixed(2) + ' ₺';
}

function handleItemSelect(select) {
    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.dataset.price) return;
}

function addItemToAdisyon() {
    if (!currentAdisyonId) return;
    const select = document.getElementById('item-selector');
    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) {
        alert('Lütfen eklenecek hizmet veya ürün seçin.');
        return;
    }

    const type = opt.dataset.type;
    const id = opt.dataset.id;
    const name = opt.dataset.name;
    const price = opt.dataset.price;
    const qty = document.getElementById('item-quantity').value || 1;

    const fd = new FormData();
    fd.append('id_adisyons', currentAdisyonId);
    fd.append('item_type', type);
    if (type === 'service') fd.append('id_services', id);
    if (type === 'product') fd.append('id_products', id);
    fd.append('name', name);
    fd.append('unit_price', price);
    fd.append('quantity', qty);

    fetch('<?= site_url('adisyons/add_item') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                openAdisyonDrawer(currentAdisyonId);
            } else {
                alert(data.message || 'Hata oluştu.');
            }
        });
}

function removeItemFromAdisyon(itemId) {
    if (!confirm('Bu kalemi adisyondan çıkarmak istediğinize emin misiniz?')) return;
    fetch('<?= site_url('adisyons/remove_item/') ?>' + itemId)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                openAdisyonDrawer(currentAdisyonId);
            } else {
                alert(data.message || 'Hata oluştu.');
            }
        });
}

function showPaymentModal() {
    if (!currentAdisyonData) return;
    const ad = currentAdisyonData.adisyon;
    const remaining = Math.max(0, parseFloat(ad.total_amount) - parseFloat(ad.paid_amount));
    document.getElementById('payment-amount').value = remaining.toFixed(2);

    // Packages setup
    const pkgSelect = document.getElementById('payment-package-select');
    const pkgGroup = document.getElementById('package-deduction-group');
    if (currentAdisyonData.customer_packages && currentAdisyonData.customer_packages.length > 0) {
        pkgSelect.innerHTML = '<option value="">-- Paket Seçin --</option>' + 
            currentAdisyonData.customer_packages.map(p => `<option value="${p.id}">${p.service_name} (${p.total_sessions - p.used_sessions} seans kaldı)</option>`).join('');
        pkgGroup.classList.remove('d-none');
    } else {
        pkgGroup.classList.add('d-none');
    }

    const modal = new bootstrap.Modal(document.getElementById('payment-modal'));
    modal.show();
}

function selectPaymentMethod(method, btn) {
    selectedPaymentMethod = method;
    document.querySelectorAll('.payment-method-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

function submitPayment() {
    const amount = document.getElementById('payment-amount').value;
    const pkgId = document.getElementById('payment-package-select').value;

    const fd = new FormData();
    fd.append('id_adisyons', currentAdisyonId);
    fd.append('amount', amount);
    fd.append('payment_method', selectedPaymentMethod);
    if (pkgId) fd.append('id_customer_packages', pkgId);

    fetch('<?= site_url('adisyons/pay') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                bootstrap.Modal.getInstance(document.getElementById('payment-modal')).hide();
                openAdisyonDrawer(currentAdisyonId);
            } else {
                alert(data.message || 'Ödeme alınamadı.');
            }
        });
}

function closeAdisyonFromDrawer() {
    if (!confirm('Adisyonu kapatmak istediğinize emin misiniz? Stok ve sarf malzeme düşümleri gerçekleşecektir.')) return;
    fetch('<?= site_url('adisyons/close/') ?>' + currentAdisyonId)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                openAdisyonDrawer(currentAdisyonId);
            } else {
                alert(data.message || 'Kapatılamadı.');
            }
        });
}

function printSlipFromDrawer() {
    window.open('<?= site_url('adisyons/print_slip/') ?>' + currentAdisyonId, '_blank');
}
</script>
<?php end_section('scripts'); ?>
