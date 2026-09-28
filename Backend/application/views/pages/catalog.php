<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>
<?php
/**
 * Unified Catalog View (Vertical-First UI Abstraction).
 *
 * @var string $family
 * @var string $business_type
 * @var array $terms
 * @var array $sections
 * @var int $total_services
 * @var int $total_categories
 * @var int $total_products
 * @var int $total_packages
 * @var int $total_memberships
 */
?>
<div class="container-fluid px-3 px-md-4 py-4 flex-grow-1">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 pb-2 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= site_url('dashboard') ?>" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($terms['catalog'] ?? 'Katalog') ?></li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-1 text-dark d-flex align-items-center">
                <span class="me-2"><?= e(current_industry_info()['icon'] ?? '📁') ?></span>
                <?= e($terms['catalog'] ?? 'Katalog Yönetimi') ?>
            </h3>
            <p class="text-muted small mb-0">İşletmenizin sunduğu <?= mb_strtolower(e($terms['service'] ?? 'hizmet')) ?>, ürün, paket ve üyelik modellerini tek ekrandan yönetin.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <?php if (can('add', 'services')): ?>
                <a href="<?= site_url('services') ?>" class="btn btn-primary btn-sm rounded-3 shadow-sm px-3 py-2 fw-semibold">
                    <i class="fas fa-plus me-1"></i> Yeni <?= e($terms['service'] ?? 'Hizmet') ?>
                </a>
            <?php endif; ?>
            <?php if (module_enabled('inventory') && can('add', 'products')): ?>
                <a href="<?= site_url('products') ?>" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fw-semibold">
                    <i class="fas fa-box me-1"></i> Yeni <?= e($terms['product'] ?? 'Ürün') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($total_services)): ?>
        <?= function_exists('render_empty_state') ? render_empty_state('services', 'Henüz katalogda ' . mb_strtolower(e($terms['service'] ?? 'hizmet')) . ' bulunmuyor.') : '' ?>
    <?php else: ?>
        <!-- Catalog Overview Summary Grid -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;"><?= e($terms['service'] ?? 'Hizmet') ?></small>
                            <h4 class="fw-bold mb-0 text-primary mt-1"><?= (int) ($total_services ?? 0) ?></h4>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3">
                            <i class="fas fa-concierge-bell fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Kategoriler</small>
                            <h4 class="fw-bold mb-0 text-success mt-1"><?= (int) ($total_categories ?? 0) ?></h4>
                        </div>
                        <div class="rounded-circle bg-success bg-opacity-10 text-success p-3">
                            <i class="fas fa-folder fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;"><?= e($terms['product'] ?? 'Ürün') ?></small>
                            <h4 class="fw-bold mb-0 text-warning mt-1"><?= (int) ($total_products ?? 0) ?></h4>
                        </div>
                        <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-3">
                            <i class="fas fa-boxes fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;"><?= e($terms['package'] ?? 'Paket') ?> & Üyelik</small>
                            <h4 class="fw-bold mb-0 text-info mt-1"><?= (int) (($total_packages ?? 0) + ($total_memberships ?? 0)) ?></h4>
                        </div>
                        <div class="rounded-circle bg-info bg-opacity-10 text-info p-3">
                            <i class="fas fa-tags fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vertical Sections -->
        <?php if (!empty($sections)): ?>
            <div class="row g-4">
                <?php foreach ($sections as $section): ?>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white d-flex flex-column transition-hover">
                            <div class="card-body p-4 flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="rounded-3 bg-light p-3 text-primary">
                                        <i class="<?= e($section['icon']) ?> fa-2x"></i>
                                    </div>
                                    <span class="badge rounded-pill bg-light text-dark border px-3 py-2 fw-semibold fs-6">
                                        <?= (int) $section['count'] ?> kayıt
                                    </span>
                                </div>
                                <h5 class="fw-bold text-dark mb-2"><?= e($section['title']) ?></h5>
                                <p class="text-muted small mb-0"><?= e($section['description']) ?></p>
                            </div>
                            <div class="card-footer bg-transparent border-0 px-4 pb-4 pt-0">
                                <a href="<?= e($section['url']) ?>" class="btn btn-outline-primary w-100 rounded-3 py-2 fw-semibold d-flex align-items-center justify-content-center">
                                    <span><?= e($section['btn_text']) ?></span>
                                    <i class="fas fa-arrow-right ms-2 small"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php end_section(); ?>
