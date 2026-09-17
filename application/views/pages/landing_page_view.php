<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page['title']) ?> - <?= e($company_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php component('google_analytics_script', [
        'google_analytics_code' => $google_analytics_code,
        'meta_pixel_id' => $meta_pixel_id,
    ]); ?>
    <style>
        body {
            background-color: #f8fafc;
            color: #1e293b;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .hero-section {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: #ffffff;
            padding: 4rem 1rem;
            text-align: center;
        }
        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .hero-headline {
            font-size: 1.25rem;
            font-weight: 300;
            max-width: 700px;
            margin: 0 auto 2rem auto;
            opacity: 0.9;
        }
        .content-card {
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
            padding: 2.5rem;
            margin-top: -3rem;
        }
        .service-highlight {
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            background-color: #f8fafc;
            margin-bottom: 2rem;
        }
        .cta-button {
            padding: 1rem 2.5rem;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 50rem;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);
            transition: all 0.2s ease-in-out;
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6);
        }
    </style>
</head>
<body>

<header class="hero-section">
    <div class="container">
        <span class="badge bg-white text-primary mb-3 px-3 py-2 text-uppercase fw-bold"><?= e($company_name) ?></span>
        <h1 class="hero-title"><?= e($page['title']) ?></h1>
        <?php if (!empty($page['headline'])): ?>
            <p class="hero-headline"><?= e($page['headline']) ?></p>
        <?php endif; ?>
    </div>
</header>

<main class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="content-card">
                <?php if (!empty($page['content'])): ?>
                    <div class="page-content mb-4 fs-5 text-secondary">
                        <?= nl2br(e($page['content'])) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($page['service_name'])): ?>
                    <div class="service-highlight">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="mb-0 fw-bold text-dark"><?= e($page['service_name']) ?></h4>
                            <span class="badge bg-success fs-6 px-3 py-2">
                                <?= number_format((float) ($page['service_price'] ?? 0), 2, ',', '.') ?> <?= e($currency ?? '₺') ?>
                            </span>
                        </div>
                        <?php if (!empty($page['service_duration'])): ?>
                            <div class="text-muted small mb-2">
                                <i class="far fa-clock me-1"></i> <?= (int) $page['service_duration'] ?> dakika
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($page['service_description'])): ?>
                            <p class="text-secondary small mb-0"><?= e($page['service_description']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <a href="<?= e($booking_url) ?>" class="btn btn-primary cta-button text-white" id="landing-cta-btn">
                        <i class="fas fa-calendar-check me-2"></i>
                        <?= e($page['cta_text'] ?: 'Hemen Randevu Al') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="text-center py-4 text-muted small">
    <p class="mb-0">&copy; <?= date('Y') ?> <?= e($company_name) ?> - Tüm Hakları Saklıdır.</p>
</footer>

</body>
</html>
