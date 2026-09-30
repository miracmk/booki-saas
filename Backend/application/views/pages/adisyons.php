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
                                <tr id="adisyon-row-<?= $ad['id'] ?>">
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
                                    <td class="adisyon-status-cell">
                                        <?php if ($ad['status'] === 'open'): ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1"><i class="fas fa-folder-open me-1"></i>Açık</span>
                                        <?php elseif ($ad['status'] === 'closed'): ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1"><i class="fas fa-check-circle me-1"></i>Kapatıldı</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border px-2 py-1"><?= e($ad['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="adisyon-payment-cell">
                                        <?php if ($ad['payment_status'] === 'paid'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1"><i class="fas fa-check me-1"></i>Ödendi</span>
                                        <?php elseif ($ad['payment_status'] === 'partially_paid'): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1"><i class="fas fa-adjust me-1"></i>Kısmi</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1"><i class="fas fa-times me-1"></i>Ödenmedi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="adisyon-invoice-cell">
                                        <?php if ($ad['invoice_status'] === 'invoiced'): ?>
                                            <a href="<?= site_url('invoices') ?>" class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1 text-decoration-none" title="Faturalandırıldı">
                                                <i class="fas fa-file-invoice me-1"></i>Faturalı <?= !empty($ad['id_invoices']) ? '#' . $ad['id_invoices'] : '' ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border px-2 py-1">Faturalanmadı</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold adisyon-total-cell">
                                        <?= number_format($ad['total_amount'], 2) ?> ₺
                                    </td>
                                    <td class="text-success fw-semibold adisyon-paid-cell">
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

                <?php if (empty($connected_erp_providers)): ?>
                    <div class="alert alert-warning border border-warning border-opacity-25 rounded-3 p-3 mb-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                <span class="fw-bold text-dark">Bağlı Muhasebe / ERP Yazılımı Bulunmuyor</span>
                                <div class="small text-muted mt-1">Faturalarınızın e-Fatura / e-Arşiv olarak muhasebe sisteminize otomatik düşmesi için bir muhasebe yazılımı bağlayın.</div>
                            </div>
                            <a href="<?= site_url('settings?tab=integrations#erp-settings') ?>" target="_blank" class="btn btn-sm btn-warning text-dark fw-bold text-nowrap">
                                <i class="fas fa-plug me-1"></i> Muhasebe Yazılımı Bağla
                            </a>
                        </div>
                    </div>
                    <input type="hidden" id="invoice-send-erp-toggle" value="0">
                <?php else: ?>
                    <div class="card border-primary border-opacity-25 bg-primary bg-opacity-10 p-3 rounded-3 mb-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="invoice-send-erp-toggle" checked onchange="toggleErpProviderSelect(this)">
                            <label class="form-check-label fw-bold text-dark" for="invoice-send-erp-toggle">
                                <i class="fas fa-cloud-upload-alt text-primary me-1"></i> ERP / Muhasebe Sistemine Gönder
                            </label>
                        </div>
                        <p class="small text-muted mb-2">
                            Fatura oluşturulduktan sonra bağlı muhasebe & ERP sisteminize (Paraşüt, Logo, Mikro vb.) e-fatura/e-arşiv olarak senkronize edilir.
                        </p>
                        <div id="erp-provider-group">
                            <label class="form-label small fw-bold text-muted">Bağlı ERP Sağlayıcısı</label>
                            <select id="invoice-erp-provider-select" class="form-select form-select-sm">
                                <?php foreach ($connected_erp_providers as $key => $title): ?>
                                    <option value="<?= $key ?>" <?= $key === $active_erp_provider ? 'selected' : '' ?>>
                                        <?= e($title) ?> <?= $key === $active_erp_provider ? '(Aktif)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
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
        <div class="d-flex align-items-center flex-wrap gap-1">
            <h5 class="offcanvas-title fw-bold me-2" id="drawer-adisyon-title">Adisyon Detayı</h5>
            <span class="badge bg-primary" id="drawer-adisyon-badge">Açık</span>
            <span class="badge bg-light text-dark border ms-1" id="drawer-invoice-badge">Faturalanmadı</span>
            <button type="button" class="btn btn-sm btn-outline-success d-none ms-2" id="drawer-btn-reopen" onclick="reopenAdisyonFromDrawer()">
                <i class="fas fa-lock-open me-1"></i> Yeniden Aç
            </button>
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
        <div class="p-3 border-bottom bg-light bg-opacity-25">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold small mb-0"><i class="fas fa-plus-circle text-primary me-1"></i>Hizmet / Ürün Ekle</h6>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 active" id="btn-tab-catalog" onclick="switchItemAddMode('catalog')">
                        <i class="fas fa-list me-1"></i> Katalogdan
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" id="btn-tab-custom" onclick="switchItemAddMode('custom')">
                        <i class="fas fa-pen me-1"></i> Özel Kalem
                    </button>
                </div>
            </div>

            <!-- Mode 1: Catalog Selection -->
            <div id="item-add-catalog-form">
                <div class="row g-2 mb-2">
                    <div class="col-12">
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
                </div>
                <div class="row g-2">
                    <div class="col-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">₺</span>
                            <input type="number" id="item-unit-price" class="form-control" placeholder="Fiyat" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Ad</span>
                            <input type="number" id="item-quantity" class="form-control" value="1" min="1" step="1">
                        </div>
                    </div>
                    <div class="col-4">
                        <button type="button" class="btn btn-sm btn-primary w-100" onclick="addItemToAdisyon()">
                            <i class="fas fa-plus me-1"></i> Ekle
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mode 2: Custom Quick Item -->
            <div id="item-add-custom-form" class="d-none">
                <div class="row g-2 mb-2">
                    <div class="col-8">
                        <input type="text" id="custom-item-name" class="form-control form-control-sm" placeholder="Kalem Adı (Örn: Saç Bakım Serumu, Ekstra İşlem...)">
                    </div>
                    <div class="col-4">
                        <select id="custom-item-staff" class="form-select form-select-sm">
                            <option value="">Personel (İsteğe Bağlı)</option>
                            <?php foreach ($staff_members as $sm): ?>
                                <option value="<?= $sm['id'] ?>"><?= e($sm['first_name'] . ' ' . $sm['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">₺</span>
                            <input type="number" id="custom-item-price" class="form-control" placeholder="Birim Fiyat" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Ad</span>
                            <input type="number" id="custom-item-qty" class="form-control" value="1" min="1" step="1">
                        </div>
                    </div>
                    <div class="col-4">
                        <button type="button" class="btn btn-sm btn-success w-100" onclick="addCustomItemToAdisyon()">
                            <i class="fas fa-check me-1"></i> Ekle
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="flex-grow-1 overflow-auto p-3">
            <!-- Dynamic Drawer Alert Container -->
            <div id="drawer-alert-box" class="d-none alert py-2 px-3 small rounded-3 mb-3"></div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold small mb-0 text-muted">Adisyon Kalemleri</h6>
                <small class="text-muted" id="drawer-items-count">0 Kalem</small>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle" id="drawer-items-table">
                    <thead class="table-light small">
                        <tr>
                            <th>Kalem</th>
                            <th class="text-center" style="width: 100px;">Adet</th>
                            <th class="text-end" style="width: 90px;">Birim</th>
                            <th class="text-end" style="width: 100px;">Toplam</th>
                            <th class="text-end" style="width: 70px;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody id="drawer-items-tbody"></tbody>
                </table>
            </div>

            <!-- Drawer Payments Section -->
            <div class="mt-3 pt-3 border-top" id="drawer-payments-section">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold small mb-0 text-muted"><i class="fas fa-receipt me-1 text-success"></i>Yapılan Tahsilatlar</h6>
                    <span id="drawer-payments-count" class="badge bg-light text-dark border">0 İşlem</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0" id="drawer-payments-table">
                        <thead class="table-light small">
                            <tr>
                                <th>Yöntem</th>
                                <th>Tarih</th>
                                <th class="text-end">Tutar</th>
                                <th class="text-end" style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="drawer-payments-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Summary & Checkout Footer -->
        <div class="p-3 border-top bg-light">
            <div class="d-flex justify-content-between mb-1 small text-muted">
                <span>Ara Toplam:</span>
                <span id="drawer-subtotal">0.00 ₺</span>
            </div>
            <div class="d-flex justify-content-between mb-1 small text-muted">
                <span>KDV (%20):</span>
                <span id="drawer-tax">0.00 ₺</span>
            </div>
            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom fw-bold fs-5 text-dark">
                <span>GENEL TOPLAM:</span>
                <span id="drawer-total" class="text-primary">0.00 ₺</span>
            </div>
            <div class="d-flex justify-content-between mb-1 small text-success fw-semibold">
                <span>Tahsil Edilen:</span>
                <span id="drawer-paid">0.00 ₺</span>
            </div>
            <div class="d-flex justify-content-between mb-3 fw-bold fs-5" id="drawer-remaining-row">
                <span id="drawer-remaining-label" class="text-danger"><i class="fas fa-hourglass-half me-1"></i>KALAN TUTAR:</span>
                <span id="drawer-remaining" class="text-danger">0.00 ₺</span>
            </div>

            <!-- Quick Action Buttons -->
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-success flex-grow-1" id="drawer-btn-payment" onclick="showPaymentModal()">
                    <i class="fas fa-credit-card me-1"></i> Ödeme Al / Tahsilat
                </button>
                <button type="button" class="btn btn-info text-white" onclick="openDrawerInvoiceModal()">
                    <i class="fas fa-file-invoice me-1"></i> Faturalandır
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-print me-1"></i> Fiş Yazdır
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item small" href="javascript:void(0)" onclick="printSlipFromDrawer('80mm')"><i class="fas fa-file-invoice me-2 text-primary"></i>80mm Standart Fiş</a></li>
                        <li><a class="dropdown-item small" href="javascript:void(0)" onclick="printSlipFromDrawer('58mm')"><i class="fas fa-receipt me-2 text-success"></i>58mm Termal Fiş</a></li>
                        <li><a class="dropdown-item small" href="javascript:void(0)" onclick="printSlipFromDrawer('custom')"><i class="fas fa-sliders-h me-2 text-muted"></i>Özel Boyut (Genişlik Seç)</a></li>
                    </ul>
                </div>
                <button type="button" class="btn btn-dark d-none" id="drawer-btn-close-adisyon" onclick="closeAdisyonFromDrawer()">
                    <i class="fas fa-check-circle me-1"></i> Kapat
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Item -->
<div class="modal fade" id="edit-item-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow border-0">
            <div class="modal-header py-2 bg-light">
                <h6 class="modal-title fw-bold"><i class="fas fa-edit text-primary me-1"></i>Kalemi Düzenle</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <input type="hidden" id="edit-item-id">
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Kalem / Ürün Adı</label>
                    <input type="text" id="edit-item-name" class="form-control form-control-sm">
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-muted mb-1">Miktar / Adet</label>
                        <input type="number" id="edit-item-qty" class="form-control form-control-sm" min="0.01" step="1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-muted mb-1">Birim Fiyat (₺)</label>
                        <input type="number" id="edit-item-price" class="form-control form-control-sm" min="0" step="0.01">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-muted mb-1">İndirim Tutarı (₺)</label>
                    <input type="number" id="edit-item-discount" class="form-control form-control-sm" min="0" step="0.01" value="0">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Sorumlu Personel</label>
                    <select id="edit-item-staff" class="form-select form-select-sm">
                        <option value="">-- Personel Seçin --</option>
                        <?php foreach ($staff_members as $sm): ?>
                            <option value="<?= $sm['id'] ?>"><?= e($sm['first_name'] . ' ' . $sm['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary btn-sm fw-bold px-3" onclick="saveItemEdit()">
                    <i class="fas fa-check me-1"></i> Güncelle
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Payment / Checkout -->
<div class="modal fade" id="payment-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title fw-bold"><i class="fas fa-cash-register text-success me-2"></i>Tahsilat Yap</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <!-- Adisyon Balance Summary Card -->
                <div class="card bg-light border-0 p-3 rounded-3 mb-3">
                    <div class="d-flex justify-content-between text-muted small mb-1">
                        <span>Adisyon Toplamı:</span>
                        <strong id="modal-ad-total" class="text-dark">0.00 ₺</strong>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mb-1">
                        <span>Önceki Tahsilatlar:</span>
                        <strong id="modal-ad-paid" class="text-success">0.00 ₺</strong>
                    </div>
                    <div class="d-flex justify-content-between fs-6 fw-bold border-top pt-1 mt-1 text-danger">
                        <span>Kalan Ödenecek Bakiye:</span>
                        <span id="modal-ad-remaining">0.00 ₺</span>
                    </div>
                </div>

                <!-- Payment Amount Input & Quick Percentage Buttons -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold mb-0">Alınacak Tahsilat Tutarı (₺)</label>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setPaymentAmount('full')">Tüm Kalan</button>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setPaymentAmount('half')">%50 (Yarım)</button>
                        </div>
                    </div>
                    <input type="number" id="payment-amount" class="form-control fs-4 fw-bold text-success" step="0.01" oninput="updatePaymentRemainingPreview()">
                    <div class="d-flex justify-content-between small text-muted mt-1">
                        <span>Bu tahsilat sonrası kalan:</span>
                        <strong id="payment-after-remaining" class="text-muted">0.00 ₺</strong>
                    </div>
                </div>

                <!-- Payment Method Selector -->
                <div class="mb-3">
                    <label class="form-label small fw-bold">Ödeme Yöntemi</label>
                    <div class="row g-2" id="payment-method-selector">
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
                        <div class="col-4">
                            <button type="button" class="btn btn-outline-primary w-100 py-2 payment-method-btn" data-method="package" onclick="selectPaymentMethod('package', this)">
                                <i class="fas fa-box d-block mb-1"></i> Paketten Düş
                            </button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn btn-outline-primary w-100 py-2 payment-method-btn" data-method="membership" onclick="selectPaymentMethod('membership', this)">
                                <i class="fas fa-id-card d-block mb-1"></i> Üyelik
                            </button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn btn-outline-primary w-100 py-2 payment-method-btn" data-method="gift_card" onclick="selectPaymentMethod('gift_card', this)">
                                <i class="fas fa-gift d-block mb-1"></i> Hediye Kartı
                            </button>
                        </div>
                    </div>
                </div>

                <!-- IBAN & Bank Transfer Section -->
                <div id="iban-bank-transfer-group" class="card bg-info bg-opacity-10 border-info border-opacity-25 p-3 rounded-3 mb-3 d-none">
                    <label class="form-label small fw-bold text-dark mb-1">
                        <i class="fas fa-university text-info me-1"></i>Hesap Seçimi (Havale / FAST / EFT)
                    </label>
                    <p class="small text-muted mb-2" style="font-size:11px;">Müşterinin ödeme göndereceği banka hesabını seçin; IBAN ve Alıcı Adı ekranda gösterilir.</p>
                    
                    <select id="payment-bank-account-select" class="form-select form-select-sm mb-2" onchange="onBankAccountSelected(this)">
                        <?php if (!empty($bank_accounts)): ?>
                            <option value="">-- Banka Hesabı Seçin --</option>
                            <?php foreach ($bank_accounts as $ba): ?>
                                <option value="<?= $ba['id'] ?>" data-bank="<?= e($ba['bank_name']) ?>" data-receiver="<?= e($ba['account_name']) ?>" data-iban="<?= e($ba['iban']) ?>">
                                    <?= e($ba['bank_name']) ?> - <?= e($ba['iban']) ?> (<?= e($ba['account_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">-- Tanımlı Banka Hesabı Bulunamadı --</option>
                        <?php endif; ?>
                    </select>

                    <div id="no-bank-account-alert" class="alert alert-warning py-2 px-3 small mb-2 d-none">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>Havale / EFT tahsilatı için banka hesabı tanımlı değil.</strong>
                        <button type="button" class="btn btn-sm btn-outline-warning ms-1 py-0" onclick="openQuickBankAccountModal()">
                            <i class="fas fa-plus me-1"></i> Hemen Ekle
                        </button>
                    </div>

                    <div class="p-3 bg-white rounded-3 border shadow-sm d-none" id="iban-details-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary px-2 py-1" id="iban-display-bank">-</span>
                            <span class="badge bg-light text-dark border small">FAST / Havale / EFT</span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small d-block" style="font-size:11px;">Alıcı Adı Soyadı / Unvan:</span>
                            <div class="fw-bold text-dark fs-6" id="iban-display-receiver">-</div>
                        </div>
                        <div>
                            <span class="text-muted small d-block" style="font-size:11px;">IBAN Numarası:</span>
                            <div class="font-monospace fw-bold text-primary p-2 bg-light rounded border my-1" id="iban-display-iban" style="font-size:14px; letter-spacing:1px; word-break:break-all;">-</div>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1" onclick="copyIbanToClipboard()">
                                <i class="fas fa-copy me-1"></i> IBAN'ı Kopyala
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary flex-grow-1" onclick="copyReceiverToClipboard()">
                                <i class="fas fa-user me-1"></i> Alıcıyı Kopyala
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ÖKC / Fiziki POS Entegrasyon Bölümü -->
                <div id="pos-okc-group" class="card bg-primary bg-opacity-10 border-primary border-opacity-25 p-3 rounded-3 mb-3 d-none">
                    <!-- If no ÖKC is connected -->
                    <div id="pos-okc-disconnected-alert" class="alert alert-warning py-2 px-3 small mb-2 d-none">
                        <i class="fas fa-exclamation-triangle me-1"></i><strong>Bağlı ÖKC Cihazı Bulunmuyor:</strong> Sistemde tanımlı veya bağlı bir ÖKC / Fiziki POS cihazı bulunamadı. Kredi kartı tahsilatını cihaz entegrasyonu olmadan kaydedebilirsiniz.
                    </div>

                    <!-- If ÖKC is connected -->
                    <div id="pos-okc-connected-section">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="payment-send-pos" onchange="togglePosButtonText(this.checked)">
                            <label class="form-check-label fw-bold text-dark small" for="payment-send-pos">
                                <i class="fas fa-cash-register text-primary me-1"></i> POS'a Gönder (ÖKC Destekli - Otomatik Mali Fiş Bas)
                            </label>
                        </div>
                        <div class="small text-muted mb-2">
                            ÖKC / Fiziki POS cihazına anlık tahsilat düşer ve mali fiş cihazdan basılır.
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">POS / ÖKC Terminali</label>
                                <select id="payment-pos-terminal-select" class="form-select form-select-sm">
                                    <?php if (!empty($okc_terminals)): ?>
                                        <?php foreach ($okc_terminals as $t): ?>
                                            <option value="<?= $t['id'] ?>"><?= e($t['name']) ?> (<?= e($t['terminal_id']) ?>)</option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="none">Tanımlı ÖKC Terminali Yok</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">KDV Departmanı</label>
                                <select id="payment-okc-vat-select" class="form-select form-select-sm">
                                    <option value="20">%20 Hizmet Departmanı</option>
                                    <option value="10">%10 Bakım / Sağlık</option>
                                    <option value="1">%1 Temel</option>
                                </select>
                            </div>
                        </div>
                        <div id="no-okc-terminal-alert" class="alert alert-warning py-2 px-3 small mb-0 mt-2 d-none">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <strong>Tanımlı ÖKC / POS terminali yok.</strong>
                            <button type="button" class="btn btn-sm btn-outline-warning ms-1 py-0" onclick="openQuickBankAccountModal('pos')">
                                <i class="fas fa-plus me-1"></i> Hemen Ekle
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Package / Membership / Gift Card Deduction Section -->
                <div id="package-deduction-group" class="mb-3 d-none">
                    <label class="form-label small fw-bold text-success"><i class="fas fa-box me-1"></i>Müşteri Paketinden 1 Seans Düş</label>
                    <select id="payment-package-select" class="form-select form-select-sm"></select>
                </div>
                <div id="membership-deduction-group" class="mb-3 d-none">
                    <label class="form-label small fw-bold text-info"><i class="fas fa-id-card me-1"></i>Aktif Üyelik ile Düş</label>
                    <select id="payment-membership-select" class="form-select form-select-sm"></select>
                </div>
                <div id="gift-card-deduction-group" class="mb-3 d-none">
                    <label class="form-label small fw-bold text-danger"><i class="fas fa-gift me-1"></i>Hediye Kartı Kodu</label>
                    <input type="text" id="payment-gift-card-code" class="form-control form-control-sm text-uppercase font-monospace" placeholder="Örn: GIFT-XXXXXX">
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-success btn-sm fw-bold px-3" id="btn-submit-payment" onclick="submitPayment()">Tahsilatı Tamamla</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Quick Add Bank Account (from payment modal) -->
<div class="modal fade" id="quick-bank-account-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow border-0">
            <div class="modal-header py-2 bg-light">
                <h6 class="modal-title fw-bold" id="quick-ba-modal-title"><i class="fas fa-university text-primary me-1"></i>Hızlı Banka Hesabı Ekle</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="alert alert-info py-2 px-3 small">
                    <i class="fas fa-info-circle me-1"></i>
                    Kaydettikten sonra kayıt otomatik seçilecek ve <strong>IBAN / terminal bilgileri ekranda gösterilecektir</strong>.
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Tür</label>
                    <select id="quick-ba-type" class="form-select form-select-sm" onchange="toggleQuickBankAccountType(this.value)">
                        <option value="bank">Banka Vadesiz Hesabı (IBAN / Havale)</option>
                        <option value="pos">Fiziki POS Terminali / ÖKC</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Banka / Kurum Adı <span class="text-danger">*</span></label>
                    <input type="text" id="quick-ba-bank-name" class="form-control form-control-sm" placeholder="Örn: Ziraat Bankası">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Alıcı Adı / Cihaz Tanımı <span class="text-danger">*</span></label>
                    <input type="text" id="quick-ba-account-name" class="form-control form-control-sm" placeholder="Örn: XYZ Güzellik Salonu">
                </div>
                <div class="mb-2" id="quick-ba-iban-group">
                    <label class="form-label small fw-semibold text-muted mb-1">IBAN <span class="text-danger">*</span></label>
                    <input type="text" id="quick-ba-iban" class="form-control form-control-sm font-monospace text-uppercase" placeholder="TR00 0000 0000 0000 0000 0000 00" maxlength="34">
                </div>
                <div class="row g-2 mb-2 d-none" id="quick-ba-pos-group">
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-muted mb-1">Terminal ID <span class="text-danger">*</span></label>
                        <input type="text" id="quick-ba-pos-terminal" class="form-control form-control-sm" placeholder="Örn: 20230001">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-muted mb-1">Sağlayıcı</label>
                        <input type="text" id="quick-ba-pos-provider" class="form-control form-control-sm" placeholder="Örn: Garanti, ÖdeAl">
                    </div>
                </div>
                <div class="form-check" id="quick-ba-default-group">
                    <input class="form-check-input" type="checkbox" id="quick-ba-default" value="1">
                    <label class="form-check-label small" for="quick-ba-default">Varsayılan IBAN olarak ata</label>
                </div>
                <div class="form-check d-none" id="quick-ba-default-pos-group">
                    <input class="form-check-input" type="checkbox" id="quick-ba-default-pos" value="1">
                    <label class="form-check-label small" for="quick-ba-default-pos">Varsayılan POS olarak ata</label>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary btn-sm fw-bold" id="btn-save-quick-bank-account" onclick="saveQuickBankAccount()">Kaydet ve Seç</button>
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
                window.currentAdisyon = data.adisyon;
                renderAdisyonDrawer(data);
                const drawerEl = document.getElementById('adisyon-drawer');
                const bsDrawer = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);
                bsDrawer.show();
            }
        });
}

function showDrawerAlert(type, message) {
    const box = document.getElementById('drawer-alert-box');
    if (!box) return;
    box.className = `alert alert-${type} py-2 px-3 small rounded-3 mb-3 d-flex align-items-center justify-content-between`;
    box.innerHTML = `<div><i class="fas ${type === 'success' ? 'fa-check-circle' : (type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle')} me-2"></i>${message}</div><button type="button" class="btn-close btn-close-sm" onclick="this.parentElement.classList.add('d-none')"></button>`;
    box.classList.remove('d-none');
    setTimeout(() => {
        if (box) box.classList.add('d-none');
    }, 4500);
}

function switchItemAddMode(mode) {
    const catBtn = document.getElementById('btn-tab-catalog');
    const cusBtn = document.getElementById('btn-tab-custom');
    const catForm = document.getElementById('item-add-catalog-form');
    const cusForm = document.getElementById('item-add-custom-form');
    if (mode === 'catalog') {
        if (catBtn) {
            catBtn.classList.add('active', 'btn-outline-primary');
            catBtn.classList.remove('btn-outline-secondary');
        }
        if (cusBtn) {
            cusBtn.classList.remove('active', 'btn-outline-primary');
            cusBtn.classList.add('btn-outline-secondary');
        }
        if (catForm) catForm.classList.remove('d-none');
        if (cusForm) cusForm.classList.add('d-none');
    } else {
        if (cusBtn) {
            cusBtn.classList.add('active', 'btn-outline-primary');
            cusBtn.classList.remove('btn-outline-secondary');
        }
        if (catBtn) {
            catBtn.classList.remove('active', 'btn-outline-primary');
            catBtn.classList.add('btn-outline-secondary');
        }
        if (cusForm) cusForm.classList.remove('d-none');
        if (catForm) catForm.classList.add('d-none');
        const nameInput = document.getElementById('custom-item-name');
        if (nameInput) setTimeout(() => nameInput.focus(), 100);
    }
}

function handleItemSelect(select) {
    const opt = select.options[select.selectedIndex];
    const priceInput = document.getElementById('item-unit-price');
    if (!opt || !opt.value) {
        if (priceInput) priceInput.value = '';
        return;
    }
    if (priceInput && opt.dataset.price !== undefined) {
        priceInput.value = parseFloat(opt.dataset.price || 0).toFixed(2);
    }
}

function renderAdisyonDrawer(data) {
    const ad = data.adisyon;
    const isOpen = ad.status === 'open';
    document.getElementById('drawer-adisyon-title').innerText = 'Adisyon #' + ad.adisyon_number;
    document.getElementById('drawer-adisyon-badge').innerText = isOpen ? 'Açık' : 'Kapatıldı';
    document.getElementById('drawer-adisyon-badge').className = 'badge ' + (isOpen ? 'bg-primary' : 'bg-secondary');

    const reopenBtn = document.getElementById('drawer-btn-reopen');
    if (reopenBtn) reopenBtn.classList.toggle('d-none', isOpen);

    const closeBtn = document.getElementById('drawer-btn-close-adisyon');
    if (closeBtn) closeBtn.classList.toggle('d-none', !isOpen);

    // Kapatılmış adisyonda tahsilat alınamaz; önce yeniden açılmalı.
    const paymentBtn = document.getElementById('drawer-btn-payment');
    if (paymentBtn) {
        paymentBtn.disabled = !isOpen;
        paymentBtn.title = isOpen ? '' : 'Kapatılmış adisyonda tahsilat alabilmek için önce adisyonu yeniden açın.';
    }

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

    const items = ad.items || [];
    const itemsCountEl = document.getElementById('drawer-items-count');
    if (itemsCountEl) itemsCountEl.innerText = items.length + ' Kalem';

    // Render items
    const tbody = document.getElementById('drawer-items-tbody');
    tbody.innerHTML = '';
    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted small py-3">Adisyonda henüz kalem bulunmuyor.</td></tr>';
    } else {
        items.forEach(it => {
            const tr = document.createElement('tr');
            const qty = parseFloat(it.quantity);
            const unitPrice = parseFloat(it.unit_price);
            const total = parseFloat(it.total_amount);
            const discount = parseFloat(it.discount_amount || 0);

            tr.innerHTML = `
                <td>
                    <span class="fw-semibold text-dark">${it.name}</span>
                    ${it.staff_first_name ? `<br><small class="text-muted"><i class="fas fa-user-circle me-1"></i>${it.staff_first_name} ${it.staff_last_name || ''}</small>` : ''}
                    ${discount > 0 ? `<br><small class="text-danger"><i class="fas fa-tag me-1"></i>-${discount.toFixed(2)} ₺ İndirim</small>` : ''}
                </td>
                <td class="text-center text-nowrap">
                    ${ad.status === 'open' ? `
                        <div class="btn-group btn-group-sm align-items-center" role="group">
                            <button type="button" class="btn btn-outline-secondary py-0 px-1" style="font-size:11px;" onclick="quickChangeItemQty(${it.id}, -1)" title="1 Azalt">-</button>
                            <span class="fw-bold px-2 small">${qty}</span>
                            <button type="button" class="btn btn-outline-secondary py-0 px-1" style="font-size:11px;" onclick="quickChangeItemQty(${it.id}, 1)" title="1 Artır">+</button>
                        </div>
                    ` : `<span class="fw-bold">${qty}</span>`}
                </td>
                <td class="text-end small">${unitPrice.toFixed(2)} ₺</td>
                <td class="text-end fw-bold text-dark">${total.toFixed(2)} ₺</td>
                <td class="text-end text-nowrap">
                    ${ad.status === 'open' ? `
                        <button type="button" class="btn btn-sm btn-link text-primary p-0 me-2" onclick="openEditItemModal(${it.id})" title="Kalemi Düzenle">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removeItemFromAdisyon(${it.id})" title="Kalemi Sil">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    ` : '<span class="text-muted small">-</span>'}
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Render payments
    const payTbody = document.getElementById('drawer-payments-tbody');
    const payCount = document.getElementById('drawer-payments-count');
    if (payTbody) {
        payTbody.innerHTML = '';
        const payments = ad.payments || [];
        if (payCount) payCount.innerText = payments.length + ' İşlem';

        if (payments.length === 0) {
            payTbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted small py-2">Henüz tahsilat yapılmadı.</td></tr>';
        } else {
            payments.forEach(p => {
                const tr = document.createElement('tr');
                const methodBadge = {
                    'cash': '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Nakit</span>',
                    'card': '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Kredi Kartı</span>',
                    'bank_transfer': '<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">Havale/EFT</span>',
                    'package': '<span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25">Paketten Düş</span>',
                    'membership': '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Üyelik</span>',
                    'gift_card': '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Hediye Kartı</span>',
                }[p.payment_method] || `<span class="badge bg-light text-dark">${p.payment_method}</span>`;

                tr.innerHTML = `
                    <td>
                        ${methodBadge}
                        ${p.notes ? `<div class="small text-muted mt-1" style="font-size:10px; line-height:1.2;">${p.notes}</div>` : ''}
                    </td>
                    <td class="small text-muted">${p.created_at ? p.created_at.substring(5, 16) : '-'}</td>
                    <td class="text-end fw-bold text-success">${parseFloat(p.amount).toFixed(2)} ₺</td>
                    <td class="text-end">
                        ${ad.status === 'open' ? `<button class="btn btn-sm btn-link text-danger p-0" onclick="removePaymentFromAdisyon(${p.id})" title="Tahsilatı Sil / İade"><i class="fas fa-trash-alt"></i></button>` : ''}
                    </td>
                `;
                payTbody.appendChild(tr);
            });
        }
    }

    const total = parseFloat(ad.total_amount || 0);
    const paid = parseFloat(ad.paid_amount || 0);
    const remaining = Math.max(0, total - paid);

    document.getElementById('drawer-subtotal').innerText = parseFloat(ad.subtotal || 0).toFixed(2) + ' ₺';
    document.getElementById('drawer-tax').innerText = parseFloat(ad.tax_amount || 0).toFixed(2) + ' ₺';
    document.getElementById('drawer-total').innerText = total.toFixed(2) + ' ₺';
    document.getElementById('drawer-paid').innerText = paid.toFixed(2) + ' ₺';

    const remEl = document.getElementById('drawer-remaining');
    const remLabel = document.getElementById('drawer-remaining-label');
    const payBtn = document.getElementById('drawer-btn-payment');

    if (remEl) {
        remEl.innerText = remaining.toFixed(2) + ' ₺';
        if (remaining <= 0.001 && total > 0) {
            remEl.className = 'text-success';
            if (remLabel) {
                remLabel.className = 'text-success';
                remLabel.innerHTML = '<i class="fas fa-check-circle me-1"></i>BAKİYE KAPANDI:';
            }
            if (payBtn) payBtn.innerHTML = '<i class="fas fa-plus me-1"></i> Ek Tahsilat Al';
        } else {
            remEl.className = 'text-danger';
            if (remLabel) {
                remLabel.className = 'text-danger';
                remLabel.innerHTML = '<i class="fas fa-hourglass-half me-1"></i>KALAN TUTAR:';
            }
            if (payBtn) payBtn.innerHTML = `<i class="fas fa-credit-card me-1"></i> Ödeme Al (${remaining.toFixed(2)} ₺)`;
        }
    }

    // Synchronize background table row in real time
    updateAdisyonRow(ad);
}

function updateAdisyonRow(ad) {
    if (!ad || !ad.id) return;
    const row = document.getElementById('adisyon-row-' + ad.id);
    if (!row) return;

    const totalEl = row.querySelector('.adisyon-total-cell');
    if (totalEl) totalEl.innerText = parseFloat(ad.total_amount).toFixed(2) + ' ₺';

    const paidEl = row.querySelector('.adisyon-paid-cell');
    if (paidEl) paidEl.innerText = parseFloat(ad.paid_amount).toFixed(2) + ' ₺';

    const statusCell = row.querySelector('.adisyon-status-cell');
    if (statusCell) {
        if (ad.status === 'open') {
            statusCell.innerHTML = '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1"><i class="fas fa-folder-open me-1"></i>Açık</span>';
        } else {
            statusCell.innerHTML = '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1"><i class="fas fa-check-circle me-1"></i>Kapatıldı</span>';
        }
    }

    const payCell = row.querySelector('.adisyon-payment-cell');
    if (payCell) {
        if (ad.payment_status === 'paid') {
            payCell.innerHTML = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1"><i class="fas fa-check me-1"></i>Ödendi</span>';
        } else if (ad.payment_status === 'partially_paid') {
            payCell.innerHTML = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1"><i class="fas fa-adjust me-1"></i>Kısmi</span>';
        } else {
            payCell.innerHTML = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1"><i class="fas fa-times me-1"></i>Ödenmedi</span>';
        }
    }
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
    const customPriceVal = document.getElementById('item-unit-price').value;
    const price = (customPriceVal !== '' && !isNaN(customPriceVal)) ? parseFloat(customPriceVal) : parseFloat(opt.dataset.price || 0);
    const qty = parseFloat(document.getElementById('item-quantity').value) || 1;

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
                select.value = '';
                document.getElementById('item-unit-price').value = '';
                document.getElementById('item-quantity').value = 1;
                if (data.adisyon) {
                    currentAdisyonData.adisyon = data.adisyon;
                    window.currentAdisyon = data.adisyon;
                    renderAdisyonDrawer(currentAdisyonData);
                    updateAdisyonRow(data.adisyon);
                } else {
                    openAdisyonDrawer(currentAdisyonId);
                }
                showDrawerAlert('success', `"${name}" adisyona eklendi.`);
            } else {
                alert(data.message || 'Kalem eklenemedi.');
            }
        })
        .catch(err => alert('Ağ hatası: ' + err.message));
}

function addCustomItemToAdisyon() {
    if (!currentAdisyonId) return;
    const name = (document.getElementById('custom-item-name').value || '').trim();
    const priceVal = document.getElementById('custom-item-price').value;
    const price = parseFloat(priceVal);
    const qty = parseFloat(document.getElementById('custom-item-qty').value) || 1;
    const staffId = document.getElementById('custom-item-staff').value;

    if (!name) {
        alert('Lütfen özel kalem adını giriniz.');
        document.getElementById('custom-item-name').focus();
        return;
    }
    if (isNaN(price) || price < 0) {
        alert('Lütfen geçerli bir birim fiyat giriniz.');
        document.getElementById('custom-item-price').focus();
        return;
    }

    const fd = new FormData();
    fd.append('id_adisyons', currentAdisyonId);
    fd.append('item_type', 'product');
    fd.append('name', name);
    fd.append('unit_price', price);
    fd.append('quantity', qty);
    if (staffId) fd.append('id_users_staff', staffId);

    fetch('<?= site_url('adisyons/add_item') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('custom-item-name').value = '';
                document.getElementById('custom-item-price').value = '';
                document.getElementById('custom-item-qty').value = 1;
                document.getElementById('custom-item-staff').value = '';
                if (data.adisyon) {
                    currentAdisyonData.adisyon = data.adisyon;
                    window.currentAdisyon = data.adisyon;
                    renderAdisyonDrawer(currentAdisyonData);
                    updateAdisyonRow(data.adisyon);
                } else {
                    openAdisyonDrawer(currentAdisyonId);
                }
                showDrawerAlert('success', `Özel kalem "${name}" adisyona eklendi.`);
            } else {
                alert(data.message || 'Özel kalem eklenemedi.');
            }
        })
        .catch(err => alert('Ağ hatası: ' + err.message));
}

function quickChangeItemQty(itemId, delta) {
    if (!currentAdisyonData || !currentAdisyonData.adisyon) return;
    const items = currentAdisyonData.adisyon.items || [];
    const item = items.find(it => parseInt(it.id) === parseInt(itemId));
    if (!item) return;

    const newQty = parseFloat(item.quantity) + delta;
    if (newQty <= 0) {
        removeItemFromAdisyon(itemId);
        return;
    }

    updateItemApi(itemId, { quantity: newQty });
}

function openEditItemModal(itemId) {
    if (!currentAdisyonData || !currentAdisyonData.adisyon) return;
    const items = currentAdisyonData.adisyon.items || [];
    const item = items.find(it => parseInt(it.id) === parseInt(itemId));
    if (!item) return;

    document.getElementById('edit-item-id').value = item.id;
    document.getElementById('edit-item-name').value = item.name;
    document.getElementById('edit-item-qty').value = parseFloat(item.quantity);
    document.getElementById('edit-item-price').value = parseFloat(item.unit_price).toFixed(2);
    document.getElementById('edit-item-discount').value = parseFloat(item.discount_amount || 0).toFixed(2);
    document.getElementById('edit-item-staff').value = item.id_users_staff || '';

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('edit-item-modal'));
    modal.show();
}

function saveItemEdit() {
    const itemId = document.getElementById('edit-item-id').value;
    const name = (document.getElementById('edit-item-name').value || '').trim();
    const qty = parseFloat(document.getElementById('edit-item-qty').value);
    const price = parseFloat(document.getElementById('edit-item-price').value);
    const discount = parseFloat(document.getElementById('edit-item-discount').value) || 0;
    const staffId = document.getElementById('edit-item-staff').value;

    if (!name) {
        alert('Kalem adı boş bırakılamaz.');
        return;
    }
    if (isNaN(qty) || qty <= 0) {
        alert('Geçerli bir adet giriniz.');
        return;
    }
    if (isNaN(price) || price < 0) {
        alert('Geçerli bir birim fiyat giriniz.');
        return;
    }

    const payload = {
        id: itemId,
        name: name,
        quantity: qty,
        unit_price: price,
        discount_amount: discount,
        id_users_staff: staffId || null
    };

    updateItemApi(itemId, payload, () => {
        const modalEl = document.getElementById('edit-item-modal');
        const inst = bootstrap.Modal.getInstance(modalEl);
        if (inst) inst.hide();
    });
}

function updateItemApi(itemId, payload, callback) {
    const body = Object.assign({}, payload || {});
    if (!body.id && !body.item_id) {
        body.id = itemId;
    }

    const request = fetch('<?= site_url('adisyons/update_item') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    })
    .then(async res => {
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('Sunucu yanıtı okunamadı (HTTP ' + res.status + ').');
        }
        if (data.status !== 'success') {
            throw new Error(data.message || ('Güncellenemedi (HTTP ' + res.status + ').'));
        }
        return data;
    })
    .then(data => {
        if (data.adisyon) {
            currentAdisyonData.adisyon = data.adisyon;
            window.currentAdisyon = data.adisyon;
            renderAdisyonDrawer(currentAdisyonData);
            updateAdisyonRow(data.adisyon);
        } else {
            openAdisyonDrawer(currentAdisyonId);
        }
        showDrawerAlert('success', 'Kalem güncellendi.');
        if (typeof callback === 'function') callback();
    })
    .catch(err => showDrawerAlert('danger', err.message || 'Kalem güncellenemedi.'));

    return request;
}

function removeItemFromAdisyon(itemId) {
    if (!confirm('Bu kalemi adisyondan çıkarmak istediğinize emin misiniz?')) return;
    fetch('<?= site_url('adisyons/remove_item/') ?>' + itemId)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.adisyon) {
                    currentAdisyonData.adisyon = data.adisyon;
                    window.currentAdisyon = data.adisyon;
                    renderAdisyonDrawer(currentAdisyonData);
                    updateAdisyonRow(data.adisyon);
                } else {
                    openAdisyonDrawer(currentAdisyonId);
                }
                showDrawerAlert('success', 'Kalem adisyondan silindi.');
            } else {
                alert(data.message || 'Hata oluştu.');
            }
        })
        .catch(err => alert('Ağ hatası: ' + err.message));
}

function removePaymentFromAdisyon(paymentId) {
    if (!confirm('Bu tahsilat kaydını silmek ve adisyon bakiyesini geri almak istediğinize emin misiniz?')) return;
    fetch('<?= site_url('adisyons/remove_payment/') ?>' + paymentId)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.adisyon) {
                    currentAdisyonData.adisyon = data.adisyon;
                    window.currentAdisyon = data.adisyon;
                    renderAdisyonDrawer(currentAdisyonData);
                    updateAdisyonRow(data.adisyon);
                } else {
                    openAdisyonDrawer(currentAdisyonId);
                }
                showDrawerAlert('success', 'Tahsilat kaydı silindi.');
            } else {
                alert(data.message || 'Tahsilat silinemedi.');
            }
        })
        .catch(err => alert('İşlem başarısız: ' + err.message));
}

function setIbanDetailsCardVisible(visible) {
    const card = document.getElementById('iban-details-card');
    if (card) card.classList.toggle('d-none', !visible);
    const alertEl = document.getElementById('no-bank-account-alert');
    if (alertEl) alertEl.classList.toggle('d-none', visible);
}

function onBankAccountSelected(select) {
    if (!select) return;

    const card = document.getElementById('iban-details-card');
    const alertEl = document.getElementById('no-bank-account-alert');
    const opt = select.options[select.selectedIndex];
    const selectedId = (opt && opt.value) ? String(opt.value) : '';

    // Banka hesabı tanımlı mı? Boş değerli seçenekler gerçek hesap sayılmaz.
    let hasAccounts = false;
    for (let i = 0; i < select.options.length; i++) {
        if (select.options[i].value) { hasAccounts = true; break; }
    }

    if (!opt || !selectedId) {
        if (card) card.classList.add('d-none');
        if (alertEl) alertEl.classList.toggle('d-none', hasAccounts);
        return;
    }

    if (alertEl) alertEl.classList.add('d-none');
    if (card) card.classList.remove('d-none');

    const bank = opt.dataset.bank || opt.text;
    const receiver = opt.dataset.receiver || '';
    const iban = opt.dataset.iban || '';

    const bankEl = document.getElementById('iban-display-bank');
    const recEl = document.getElementById('iban-display-receiver');
    const ibanEl = document.getElementById('iban-display-iban');

    if (bankEl) bankEl.innerText = bank || '-';
    if (recEl) recEl.innerText = receiver || '-';
    if (ibanEl) ibanEl.innerText = iban || '-';
}

function copyIbanToClipboard() {
    const ibanEl = document.getElementById('iban-display-iban');
    if (!ibanEl) return;
    const text = ibanEl.innerText.replace(/\s+/g, ' ').trim();
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            const btn = document.querySelector('button[onclick="copyIbanToClipboard()"]');
            if (btn) {
                const orig = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check me-1 text-success"></i> Kopyalandı!';
                setTimeout(() => btn.innerHTML = orig, 1800);
            }
        }).catch(() => {
            prompt('IBAN Kopyala (Ctrl+C):', text);
        });
    } else {
        prompt('IBAN Kopyala (Ctrl+C):', text);
    }
}

function copyReceiverToClipboard() {
    const recEl = document.getElementById('iban-display-receiver');
    if (!recEl) return;
    const text = recEl.innerText.trim();
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            const btn = document.querySelector('button[onclick="copyReceiverToClipboard()"]');
            if (btn) {
                const orig = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check me-1 text-success"></i> Kopyalandı!';
                setTimeout(() => btn.innerHTML = orig, 1800);
            }
        }).catch(() => {
            prompt('Alıcı Adı Kopyala (Ctrl+C):', text);
        });
    } else {
        prompt('Alıcı Adı Kopyala (Ctrl+C):', text);
    }
}

function remainingForCurrentAdisyon() {
    const ad = (currentAdisyonData && currentAdisyonData.adisyon) ? currentAdisyonData.adisyon : (window.currentAdisyon || null);
    if (!ad) return 0;
    return Math.max(0, parseFloat(ad.total_amount || 0) - parseFloat(ad.paid_amount || 0));
}

function updatePaymentRemainingPreview() {
    const amountInput = document.getElementById('payment-amount');
    const remainingTotal = parseFloat(amountInput?.dataset.remaining || 0);
    const toPay = parseFloat(amountInput?.value || 0);
    const after = Math.max(0, remainingTotal - toPay);
    const afterEl = document.getElementById('payment-after-remaining');
    if (afterEl) {
        afterEl.innerText = after.toFixed(2) + ' ₺';
        if (after <= 0.001) {
            afterEl.className = 'text-success fw-bold';
        } else {
            afterEl.className = 'text-danger fw-bold';
        }
    }
}

function setPaymentAmount(type) {
    const remaining = parseFloat(document.getElementById('payment-amount').dataset.remaining || 0);
    const amountInput = document.getElementById('payment-amount');
    if (type === 'full') {
        amountInput.value = remaining.toFixed(2);
    } else if (type === 'half') {
        amountInput.value = (remaining / 2).toFixed(2);
    }
    updatePaymentRemainingPreview();
}

function togglePosButtonText(sendPos) {
    const btn = document.getElementById('btn-submit-payment');
    if (btn) {
        btn.innerHTML = sendPos ? '<i class="fas fa-cash-register me-1"></i> Tahsilatı Tamamla & POS\'a Gönder (ÖKC)' : 'Tahsilatı Tamamla';
    }
}

function selectPaymentMethod(method, btn) {
    selectedPaymentMethod = method;
    document.querySelectorAll('.payment-method-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    // IBAN Bank Transfer group
    const ibanGroup = document.getElementById('iban-bank-transfer-group');
    if (ibanGroup) {
        if (method === 'bank_transfer') {
            ibanGroup.classList.remove('d-none');
            const sel = document.getElementById('payment-bank-account-select');
            if (sel) onBankAccountSelected(sel);
        } else {
            ibanGroup.classList.add('d-none');
        }
    }

    // POS / OKC section
    const posGroup = document.getElementById('pos-okc-group');
    const isOkc = !!(currentAdisyonData && (currentAdisyonData.is_okc_connected || (currentAdisyonData.okc_terminals && currentAdisyonData.okc_terminals.length > 0)));
    const okcAlert = document.getElementById('no-okc-terminal-alert');
    if (posGroup) {
        if (method === 'card') {
            posGroup.classList.remove('d-none');
            const sendPos = isOkc && document.getElementById('payment-send-pos')?.checked;
            togglePosButtonText(sendPos);
            if (okcAlert) okcAlert.classList.toggle('d-none', !!isOkc);
        } else {
            posGroup.classList.add('d-none');
            if (okcAlert) okcAlert.classList.add('d-none');
            togglePosButtonText(false);
        }
    }

    const giftCardGroup = document.getElementById('gift-card-deduction-group');
    if (giftCardGroup) {
        if (method === 'gift_card') {
            giftCardGroup.classList.remove('d-none');
            const codeInput = document.getElementById('payment-gift-card-code');
            if (codeInput) setTimeout(() => codeInput.focus(), 150);
        } else {
            giftCardGroup.classList.add('d-none');
        }
    }

    const pkgGroup = document.getElementById('package-deduction-group');
    if (method === 'package' && pkgGroup) {
        pkgGroup.classList.remove('d-none');
    } else if (pkgGroup) {
        pkgGroup.classList.add('d-none');
    }

    const membGroup = document.getElementById('membership-deduction-group');
    if (method === 'membership' && membGroup) {
        membGroup.classList.remove('d-none');
    } else if (membGroup) {
        membGroup.classList.add('d-none');
    }
}

async function showPaymentModal(adisyonId) {
    if (adisyonId && (!currentAdisyonData || currentAdisyonId !== adisyonId)) {
        currentAdisyonId = adisyonId;
        try {
            const res = await fetch('<?= site_url('adisyons/get_details/') ?>' + adisyonId);
            const data = await res.json();
            if (data.status === 'success') {
                currentAdisyonData = data;
                window.currentAdisyon = data.adisyon;
                renderAdisyonDrawer(data);
            }
        } catch(e) {}
    }

    const currentAdisyon = (currentAdisyonData && currentAdisyonData.adisyon) ? currentAdisyonData.adisyon : (window.currentAdisyon || currentAdisyonData);
    if (!currentAdisyonData && !currentAdisyon) return;

    const ad = currentAdisyon;
    const total = parseFloat(ad.total_amount || 0);
    const paid = parseFloat(ad.paid_amount || 0);
    const remaining = Math.max(0, total - paid);

    // Kapatılmış adisyonda tahsilat alınamaz (backend de bunu engellemiyor).
    if (ad.status && ad.status !== 'open') {
        showDrawerAlert('warning', 'Kapatılmış adisyonda tahsilat alınamaz. Önce adisyonu yeniden açın.');
        return;
    }

    // Update modal balance card
    document.getElementById('modal-ad-total').innerText = total.toFixed(2) + ' ₺';
    document.getElementById('modal-ad-paid').innerText = paid.toFixed(2) + ' ₺';
    document.getElementById('modal-ad-remaining').innerText = remaining.toFixed(2) + ' ₺';

    const amountInput = document.getElementById('payment-amount');
    amountInput.value = remaining.toFixed(2);
    amountInput.dataset.remaining = remaining.toFixed(2);
    updatePaymentRemainingPreview();

    // Setup Bank Accounts Dropdown
    const bankAccounts = (currentAdisyonData && currentAdisyonData.bank_accounts) || [];
    const bankSelect = document.getElementById('payment-bank-account-select');
    if (bankSelect) {
        if (bankAccounts.length > 0) {
            const esc = v => String(v === null || v === undefined ? '' : v)
                .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                .replace(/</g, '&lt;').replace(/>/g, '&gt;');
            bankSelect.innerHTML = '<option value="">-- Banka Hesabı Seçin --</option>' + bankAccounts.map(ba => `
                <option value="${esc(ba.id)}" data-bank="${esc(ba.bank_name)}" data-receiver="${esc(ba.account_name)}" data-iban="${esc(ba.iban)}">
                    ${esc(ba.bank_name)} - ${esc(ba.iban)} (${esc(ba.account_name)})
                </option>
            `).join('');
        } else {
            bankSelect.innerHTML = '<option value="">-- Tanımlı Banka Hesabı Bulunamadı --</option>';
        }
        onBankAccountSelected(bankSelect);
    }

    // Setup ÖKC section based on is_okc_connected
    const isOkc = !!(currentAdisyonData && (currentAdisyonData.is_okc_connected || (currentAdisyonData.okc_terminals && currentAdisyonData.okc_terminals.length > 0)));
    const okcAlert = document.getElementById('pos-okc-disconnected-alert');
    const okcSection = document.getElementById('pos-okc-connected-section');
    const sendPosCheck = document.getElementById('payment-send-pos');
    const termSelect = document.getElementById('payment-pos-terminal-select');

    if (isOkc) {
        if (okcAlert) okcAlert.classList.add('d-none');
        if (okcSection) okcSection.classList.remove('d-none');
        if (sendPosCheck) {
            sendPosCheck.disabled = false;
            sendPosCheck.checked = true;
        }
        if (termSelect && currentAdisyonData.okc_terminals) {
            termSelect.innerHTML = currentAdisyonData.okc_terminals.map(t => `<option value="${t.id}">${t.name} (${t.terminal_id})</option>`).join('');
        }
    } else {
        if (okcAlert) okcAlert.classList.remove('d-none');
        if (okcSection) okcSection.classList.add('d-none');
        if (sendPosCheck) {
            sendPosCheck.disabled = true;
            sendPosCheck.checked = false;
        }
    }

    // Packages setup
    const pkgSelect = document.getElementById('payment-package-select');
    const pkgGroup = document.getElementById('package-deduction-group');
    const packages = (currentAdisyonData && currentAdisyonData.customer_packages) || (currentAdisyon && currentAdisyon.customer_packages) || [];
    if (packages && packages.length > 0) {
        pkgSelect.innerHTML = '<option value="">-- Paket Seçin --</option>' + 
            packages.map(p => `<option value="${p.id}">${p.service_name || p.name || 'Paket'} (${(p.total_sessions || 0) - (p.used_sessions || 0)} seans kaldı)</option>`).join('');
        pkgGroup.classList.remove('d-none');
    } else {
        pkgSelect.innerHTML = '<option value="">-- Tanımlı Aktif Paket Bulunamadı --</option>';
        pkgGroup.classList.add('d-none');
    }

    // Memberships setup
    const membSelect = document.getElementById('payment-membership-select');
    const membGroup = document.getElementById('membership-deduction-group');
    let memberships = (currentAdisyonData && currentAdisyonData.customer_memberships) || 
                      (currentAdisyon && currentAdisyon.customer_memberships) || [];

    if ((!memberships || memberships.length === 0) && ad.id_users_customer) {
        try {
            const mRes = await fetch('<?= site_url('memberships/get_customer_memberships/') ?>' + ad.id_users_customer);
            if (mRes.ok) {
                const mData = await mRes.json();
                if (Array.isArray(mData)) memberships = mData;
                else if (mData && mData.memberships) memberships = mData.memberships;
            }
        } catch(e) {}
    }

    if (memberships && memberships.length > 0) {
        membSelect.innerHTML = '<option value="">-- Üyelik Seçin --</option>' +
            memberships.map(m => {
                const title = m.package_name || m.title || m.plan_name || 'Üyelik';
                const remainingCredits = (m.remaining_credits !== undefined && m.remaining_credits !== null)
                    ? m.remaining_credits
                    : ((m.remaining_amount !== undefined && m.remaining_amount !== null) ? m.remaining_amount : 0);
                return `<option value="${m.id}">${title} (Kalan: ${remainingCredits})</option>`;
            }).join('');
        membGroup.classList.remove('d-none');
    } else {
        membSelect.innerHTML = '<option value="">-- Aktif Üyelik Bulunamadı --</option>';
        membGroup.classList.add('d-none');
    }

    // Reset default payment method to cash
    const cashBtn = document.querySelector('.payment-method-btn[data-method="cash"]');
    selectPaymentMethod('cash', cashBtn);

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('payment-modal'));
    modal.show();
}

function normalizeIban(value) {
    return String(value || '').replace(/[^0-9a-zA-Z]/g, '').toUpperCase();
}

function isValidIban(iban) {
    const value = normalizeIban(iban);
    if (value.length < 15 || value.length > 34) return false;
    if (!/^TR/i.test(value) && value.length !== 34 && value.length !== 26) return false;
    if (!/^[A-Z]{2}[0-9]{2}/.test(value)) return false;

    const rearranged = value.slice(4) + value.slice(0, 4);
    const expanded = rearranged.replace(/[A-Z]/g, ch => String(ch.charCodeAt(0) - 55));
    let remainder = 0;
    for (let i = 0; i < expanded.length; i++) {
        remainder = (remainder * 10 + parseInt(expanded[i], 10)) % 97;
    }
    return remainder === 1;
}

function toggleQuickBankAccountType(type) {
    const isPos = type === 'pos';
    const ibanGroup = document.getElementById('quick-ba-iban-group');
    const posGroup = document.getElementById('quick-ba-pos-group');
    const defIban = document.getElementById('quick-ba-default-group');
    const defPos = document.getElementById('quick-ba-default-pos-group');
    if (ibanGroup) ibanGroup.classList.toggle('d-none', isPos);
    if (posGroup) posGroup.classList.toggle('d-none', !isPos);
    if (defIban) defIban.classList.toggle('d-none', isPos);
    if (defPos) defPos.classList.toggle('d-none', !isPos);
    const title = document.getElementById('quick-ba-modal-title');
    if (title) {
        title.innerHTML = isPos
            ? '<i class="fas fa-credit-card text-primary me-1"></i>Hızlı ÖKC / POS Terminali Ekle'
            : '<i class="fas fa-university text-primary me-1"></i>Hızlı Banka Hesabı Ekle';
    }
}

function openQuickBankAccountModal(type) {
    const typeSelect = document.getElementById('quick-ba-type');
    if (typeSelect) typeSelect.value = (type === 'pos') ? 'pos' : 'bank';
    toggleQuickBankAccountType(typeSelect ? typeSelect.value : 'bank');

    const fields = ['quick-ba-bank-name', 'quick-ba-account-name', 'quick-ba-iban', 'quick-ba-pos-terminal', 'quick-ba-pos-provider'];
    fields.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    const defIban = document.getElementById('quick-ba-default');
    const defPos = document.getElementById('quick-ba-default-pos');
    if (defIban) defIban.checked = false;
    if (defPos) defPos.checked = false;

    const quickModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('quick-bank-account-modal'));
    quickModal.show();
    const first = document.getElementById('quick-ba-bank-name');
    if (first) setTimeout(() => first.focus(), 250);
}

function saveQuickBankAccount() {
    const accountType = document.getElementById('quick-ba-type')?.value === 'pos' ? 'pos' : 'bank';
    const bankName = (document.getElementById('quick-ba-bank-name')?.value || '').trim();
    const accountName = (document.getElementById('quick-ba-account-name')?.value || '').trim();
    const ibanRaw = document.getElementById('quick-ba-iban')?.value || '';
    const posTerminalId = (document.getElementById('quick-ba-pos-terminal')?.value || '').trim();
    const posProvider = (document.getElementById('quick-ba-pos-provider')?.value || '').trim();
    const iban = normalizeIban(ibanRaw);

    if (!bankName || !accountName) {
        alert('Kurum/cihaz adı ve hesap tanımı zorunludur.');
        return;
    }

    if (accountType === 'bank') {
        if (!iban) {
            alert('IBAN alanı zorunludur.');
            return;
        }
        if (!isValidIban(iban)) {
            alert('IBAN geçersiz görünüyor (mod-97 kontrolü başarısız). Lütfen bankadan aldığınız IBANı kontrol edin.');
            return;
        }
    } else if (!posTerminalId) {
        alert('ÖKC / POS terminal ID alanı zorunludur.');
        return;
    }

    const saveBtn = document.getElementById('btn-save-quick-bank-account');
    const origText = saveBtn ? saveBtn.innerHTML : '';
    if (saveBtn) {
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Kaydediliyor...';
        saveBtn.disabled = true;
    }

    const fd = new FormData();
    fd.append('id', 0);
    fd.append('bank_name', bankName);
    fd.append('account_name', accountName);
    fd.append('account_type', accountType);
    fd.append('currency', 'TRY');
    if (accountType === 'bank') {
        fd.append('iban', iban);
        fd.append('is_default_iban', document.getElementById('quick-ba-default')?.checked ? 1 : 0);
    } else {
        fd.append('iban', '');
        fd.append('pos_terminal_id', posTerminalId);
        fd.append('pos_provider', posProvider);
        fd.append('is_default_pos', document.getElementById('quick-ba-default-pos')?.checked ? 1 : 0);
    }

    fetch('<?= site_url('finance/save_bank_account') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (!data || !data.success) {
                throw new Error((data && data.message) || 'Hesap kaydedilemedi.');
            }
            return fetch('<?= site_url('adisyons/get_details/') ?>' + currentAdisyonId, { method: 'GET' })
                .then(r => r.json());
        })
        .then(details => {
            if (!details || details.status !== 'success') {
                throw new Error('Adisyon yenilenemedi.');
            }

            currentAdisyonData = details;
            window.currentAdisyonData = details;
            window.currentAdisyon = details.adisyon;

            const okcSelect = document.getElementById('payment-pos-terminal-select');
            if (okcSelect) {
                const terminals = details.okc_terminals || [];
                const escT = v => String(v === null || v === undefined ? '' : v)
                    .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;').replace(/>/g, '&gt;');
                okcSelect.innerHTML = terminals.length > 0
                    ? terminals.map(t => `<option value="${escT(t.id)}">${escT(t.name)} (${escT(t.terminal_id)})</option>`).join('')
                    : '<option value="none">Tanımlı ÖKC Terminali Yok</option>';
            }

            const bankSelect = document.getElementById('payment-bank-account-select');
            if (bankSelect && accountType === 'bank') {
                const bankAccounts = details.bank_accounts || [];
                const esc = v => String(v === null || v === undefined ? '' : v)
                    .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;').replace(/>/g, '&gt;');
                bankSelect.innerHTML = bankAccounts.length > 0
                    ? '<option value="">-- Banka Hesabı Seçin --</option>' + bankAccounts.map(ba => `
                        <option value="${esc(ba.id)}" data-bank="${esc(ba.bank_name)}" data-receiver="${esc(ba.account_name)}" data-iban="${esc(ba.iban)}">
                            ${esc(ba.bank_name)} - ${esc(ba.iban)} (${esc(ba.account_name)})
                        </option>
                    `).join('')
                    : '<option value="">-- Tanımlı Banka Hesabı Bulunamadı --</option>';

                // Yeni hesabı otomatik seç
                let target = bankAccounts.find(ba => normalizeIban(ba.iban) === iban);
                if (!target) {
                    target = bankAccounts.find(ba => parseInt(ba.is_default_iban, 10) === 1);
                }
                if (target) bankSelect.value = String(target.id);
                onBankAccountSelected(bankSelect);
            }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('quick-bank-account-modal')).hide();
            if (typeof showDrawerAlert === 'function') {
                showDrawerAlert('success', accountType === 'bank' ? 'Banka hesabı kaydedildi ve seçildi.' : 'ÖKC / POS terminali kaydedildi.');
            }
        })
        .catch(err => alert(err.message || 'Kayıt tamamlanamadı.'))
        .finally(() => {
            if (saveBtn) {
                saveBtn.innerHTML = origText;
                saveBtn.disabled = false;
            }
        });
}

function submitPayment() {
    const amount = document.getElementById('payment-amount').value;
    const pkgId = $('#payment-package-select').val() || document.getElementById('payment-package-select')?.value;
    const membId = $('#payment-membership-select').val() || document.getElementById('payment-membership-select')?.value;
    const giftCardCode = (document.getElementById('payment-gift-card-code')?.value || '').trim();
    const isOkc = !!(currentAdisyonData && (currentAdisyonData.is_okc_connected || (currentAdisyonData.okc_terminals && currentAdisyonData.okc_terminals.length > 0)));
    const sendPos = (selectedPaymentMethod === 'card' && isOkc && document.getElementById('payment-send-pos')?.checked) ? '1' : '0';
    const posTerminalId = document.getElementById('payment-pos-terminal-select')?.value;
    const bankAccountId = document.getElementById('payment-bank-account-select')?.value;

    if (!amount || parseFloat(amount) <= 0) {
        alert('Lütfen geçerli bir tahsilat tutarı giriniz.');
        return;
    }

    if (parseFloat(amount) > remainingForCurrentAdisyon() + 0.001) {
        alert('Tahsilat tutarı kalan bakiyeden (' + remainingForCurrentAdisyon().toFixed(2) + ' ₺) fazla olamaz.');
        return;
    }

    if (selectedPaymentMethod === 'bank_transfer') {
        const bankSelect = document.getElementById('payment-bank-account-select');
        if (!bankSelect || !bankSelect.value) {
            alert('Havale / EFT tahsilatı için önce bir banka hesabı seçin.');
            selectPaymentMethod('bank_transfer', document.querySelector('.payment-method-btn[data-method="bank_transfer"]'));
            return;
        }
    }

    const submitBtn = document.getElementById('btn-submit-payment');
    const origText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> İşleniyor...';
    submitBtn.disabled = true;

    const fd = new FormData();
    fd.append('id_adisyons', currentAdisyonId);
    fd.append('amount', amount);
    fd.append('payment_method', selectedPaymentMethod);
    fd.append('send_to_pos', sendPos);
    if (posTerminalId) fd.append('pos_terminal_id', posTerminalId);
    if (selectedPaymentMethod === 'bank_transfer' && bankAccountId) {
        fd.append('bank_account_id', bankAccountId);
    }

    if (selectedPaymentMethod === 'membership' || selectedPaymentMethod === 'package') {
        if (membId) fd.append('id_customer_memberships', membId);
        if (pkgId) fd.append('id_customer_packages', pkgId);
    } else {
        if (pkgId) fd.append('id_customer_packages', pkgId);
        if (membId) fd.append('id_customer_memberships', membId);
    }

    if (selectedPaymentMethod === 'gift_card') {
        if (!giftCardCode) {
            alert('Lütfen hediye kartı kodunu giriniz.');
            submitBtn.innerHTML = origText;
            submitBtn.disabled = false;
            return;
        }
        fd.append('gift_card_code', giftCardCode.toUpperCase());
    }

    fetch('<?= site_url('adisyons/pay') ?>', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            submitBtn.innerHTML = origText;
            submitBtn.disabled = false;
            if (data.status === 'success') {
                const modalEl = document.getElementById('payment-modal');
                if (modalEl) {
                    const inst = bootstrap.Modal.getInstance(modalEl);
                    if (inst) inst.hide();
                }

                if (data.adisyon) {
                    currentAdisyonData.adisyon = data.adisyon;
                    window.currentAdisyon = data.adisyon;
                    renderAdisyonDrawer(currentAdisyonData);
                    updateAdisyonRow(data.adisyon);
                } else {
                    openAdisyonDrawer(currentAdisyonId);
                }

                const rem = data.remaining_amount !== undefined
                    ? parseFloat(data.remaining_amount)
                    : (data.adisyon ? Math.max(0, parseFloat(data.adisyon.total_amount) - parseFloat(data.adisyon.paid_amount)) : 0);

                // Modal bakiye alanlarını ve dataset'i anında tazele ki kısmi ödeme
                // sonrası tekrar açıldığında güncel kalan değer görünsün.
                const paidEl = document.getElementById('modal-ad-paid');
                const remEl = document.getElementById('modal-ad-remaining');
                const amountEl = document.getElementById('payment-amount');
                if (data.adisyon) {
                    if (paidEl) paidEl.innerText = parseFloat(data.adisyon.paid_amount || 0).toFixed(2) + ' ₺';
                    if (remEl) remEl.innerText = rem.toFixed(2) + ' ₺';
                    if (amountEl) {
                        amountEl.dataset.remaining = rem.toFixed(2);
                        amountEl.value = rem > 0 ? rem.toFixed(2) : '0.00';
                    }
                    updatePaymentRemainingPreview();
                }

                if (rem > 0) {
                    showDrawerAlert('success', `Kısmi tahsilat (${parseFloat(amount).toFixed(2)} ₺) kaydedildi. Kalan Bakiye: ${rem.toFixed(2)} ₺`);
                } else {
                    showDrawerAlert('success', `Tahsilat (${parseFloat(amount).toFixed(2)} ₺) tamamlandı. Adisyon bakiyesi kapandı.`);
                }

                if (data.okc_warning) {
                    alert('⚠️ ÖKC Uyarısı:\n' + data.okc_warning);
                } else if (data.okc_receipt) {
                    alert('✓ Ödeme Başarılı!\n✓ ÖKC POS Cihazına Fiş Düştü!\nFiş No: ' + data.okc_receipt.receipt_no + '\nZ No: ' + data.okc_receipt.z_no);
                }
            } else {
                alert(data.message || 'Ödeme alınamadı.');
            }
        })
        .catch(err => {
            submitBtn.innerHTML = origText;
            submitBtn.disabled = false;
            alert('Ağ hatası: ' + err.message);
        });
}

function closeAdisyonFromDrawer() {
    if (!currentAdisyonId) return;
    if (!confirm('Adisyonu kapatmak istediğinize emin misiniz? Stok ve sarf malzeme düşümleri gerçekleşecektir.')) return;
    fetch('<?= site_url('adisyons/close/') ?>' + currentAdisyonId)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.adisyon) {
                    currentAdisyonData.adisyon = data.adisyon;
                    window.currentAdisyon = data.adisyon;
                    renderAdisyonDrawer(currentAdisyonData);
                    updateAdisyonRow(data.adisyon);
                } else {
                    openAdisyonDrawer(currentAdisyonId);
                }
                showDrawerAlert('success', 'Adisyon başarıyla kapatıldı.');
            } else {
                showDrawerAlert('danger', data.message || 'Kapatılamadı.');
            }
        })
        .catch(err => showDrawerAlert('danger', 'Ağ hatası: ' + err.message));
}

function reopenAdisyonFromDrawer() {
    if (!currentAdisyonId) return;
    if (!confirm('Kapatılmış adisyonu yeniden açmak istediğinize emin misiniz? Adisyon tekrar düzenlenebilir ve tahsilat alınabilir hale gelir.')) return;
    fetch('<?= site_url('adisyons/reopen/') ?>' + currentAdisyonId)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.adisyon) {
                    currentAdisyonData.adisyon = data.adisyon;
                    window.currentAdisyon = data.adisyon;
                    renderAdisyonDrawer(currentAdisyonData);
                    updateAdisyonRow(data.adisyon);
                } else {
                    openAdisyonDrawer(currentAdisyonId);
                }
                showDrawerAlert('success', 'Adisyon yeniden açıldı.');
            } else {
                showDrawerAlert('danger', data.message || 'Yeniden açılamadı.');
            }
        })
        .catch(err => showDrawerAlert('danger', 'Ağ hatası: ' + err.message));
}

function printSlipFromDrawer(format = '80mm') {
    if (!currentAdisyonId) return;
    let url = '<?= site_url('adisyons/print_slip/') ?>' + currentAdisyonId + '?format=' + format;
    if (format === 'custom') {
        const customW = prompt('Özel termal rulo genişliği (mm cinsinden, örn: 58, 70, 80):', '70');
        if (customW) url += '&width=' + encodeURIComponent(customW);
    }
    window.open(url, '_blank');
}
</script>
<?php end_section('scripts'); ?>
