<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Yetkisiz Erişim | BooKi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            max-width: 600px;
            width: 100%;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body>
<div class="container p-4">
    <div class="card error-card mx-auto border-0 bg-white">
        <div class="card-body p-5 text-center">
            <div class="mb-4">
                <span class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle p-4">
                    <i class="fas fa-lock fa-3x"></i>
                </span>
            </div>

            <h2 class="fw-bold text-dark mb-2">403 - Yetkisiz Erişim</h2>
            <p class="text-muted mb-4">
                <?= !empty($message) ? $message : 'Bu sayfayı görüntülemek veya bu işlemi gerçekleştirmek için gerekli yetkiye sahip değilsiniz.' ?>
            </p>

            <?php
            $ci = &get_instance();
            $user_id = (int) session('user_id');
            $first_route = 'account';
            if ($ci && isset($ci->permission_service)) {
                $first_route = $ci->permission_service->first_accessible_route($user_id);
            }
            ?>

            <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
                <a href="<?= site_url($first_route) ?>" class="btn btn-primary px-4 py-2">
                    <i class="fas fa-home me-2"></i>Bana Uygun Sayfaya Git
                </a>
                <a href="<?= site_url('logout') ?>" class="btn btn-outline-danger px-4 py-2">
                    <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
                </a>
            </div>

            <?php
            $tenant = function_exists('tenant_context') ? tenant_context() : [];
            $subdomain = $tenant['subdomain'] ?? '';
            $is_demo = function_exists('is_demo_environment') ? is_demo_environment() : ((defined('ENVIRONMENT') && ENVIRONMENT === 'demo') || str_starts_with($subdomain, 'demo-'));
            ?>

            <?php if ($is_demo): ?>
                <div class="border-top pt-4 mt-3 text-start">
                    <h6 class="fw-bold text-secondary mb-2 small text-uppercase tracking-wider">
                        <i class="fas fa-user-tag me-1"></i>Demo Rol Değiştirici
                    </h6>
                    <p class="small text-muted mb-3">Farklı bir role geçerek sistemi test edebilirsiniz:</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= site_url('demo/switch_role/owner') ?>" class="btn btn-sm btn-outline-primary">
                            İşletme Sahibi (Owner)
                        </a>
                        <a href="<?= site_url('demo/switch_role/manager') ?>" class="btn btn-sm btn-outline-secondary">
                            Yönetici (Manager)
                        </a>
                        <a href="<?= site_url('demo/switch_role/reception') ?>" class="btn btn-sm btn-outline-secondary">
                            Resepsiyon
                        </a>
                        <a href="<?= site_url('demo/switch_role/cashier') ?>" class="btn btn-sm btn-outline-secondary">
                            Kasa
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
