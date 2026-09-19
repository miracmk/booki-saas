<?php defined('BASEPATH') or exit('No direct script access allowed');

extract(html_vars());

$display_name = $display_name ?? ($tenant['company_name'] ?? $tenant['subdomain']);
$canonical_url = $canonical_url ?? base_url('marketplace/business/' . urlencode($tenant['subdomain']));
$meta_description = $meta_description ?? ($display_name . ' için sunulan hizmetleri inceleyin, müşteri yorumlarını okuyun ve online randevu oluşturun.');
$reviews = $reviews ?? [];
$services = $services ?? [];
$rating_dist = $rating_dist ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$total_reviews = (int) ($tenant['review_count'] ?? count($reviews));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? ($display_name . ' — Online Randevu & Değerlendirmeler | BooKi')); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="business.business">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($tenant['cover_image_url'] ?: base_url('assets/img/social-card.png')); ?>">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($tenant['cover_image_url'] ?: base_url('assets/img/social-card.png')); ?>">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46Zmdt9E355iqcxI+BF5w==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Schema.org JSON-LD (GEO: Generative Engine Optimization) -->
    <?php if (!empty($json_ld)): ?>
    <script type="application/ld+json">
        <?php echo json_encode($json_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
    </script>
    <?php endif; ?>

    <style>
        :root {
            --primary: #35A768;
            --primary-dark: #2a8653;
            --primary-light: #eaf6ef;
            --surface: #ffffff;
            --background: #f8fafc;
            --line: #e2e8f0;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --star: #f59e0b;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 14px rgba(0, 0, 0, 0.07);
            --shadow-lg: 0 12px 28px rgba(0, 0, 0, 0.09);
            --font: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --radius-md: 12px;
            --radius-lg: 18px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--font); background-color: var(--background); color: var(--text-dark); line-height: 1.5; -webkit-font-smoothing: antialiased; }
        a { text-decoration: none; color: inherit; }

        .container { max-width: 1140px; margin: 0 auto; padding: 0 1.25rem; }

        /* Top Navbar */
        .navbar {
            background-color: var(--surface);
            border-bottom: 1px solid var(--line);
            padding: 0.85rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar-content { display: flex; align-items: center; justify-content: space-between; }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 600;
            transition: color 0.15s;
        }
        .back-link:hover { color: var(--primary); }

        /* Profile Header Hero */
        .profile-hero {
            background-color: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--line);
            box-shadow: var(--shadow-sm);
            margin: 2rem 0;
            overflow: hidden;
        }
        .profile-cover {
            width: 100%;
            height: 260px;
            background-color: #e2e8f0;
            position: relative;
            overflow: hidden;
        }
        .profile-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-cover-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: #94a3b8;
            font-size: 4rem;
        }
        .profile-header-body {
            padding: 1.75rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        .profile-main-info { flex: 1 1 500px; }
        .profile-title {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
        }
        .badge-verified {
            font-size: 0.75rem;
            font-weight: 700;
            background-color: #e0f2fe;
            color: #0284c7;
            padding: 0.3rem 0.65rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .profile-meta-tags {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        .meta-tag { display: inline-flex; align-items: center; gap: 0.4rem; }
        .profile-description {
            font-size: 0.95rem;
            color: #475569;
            line-height: 1.6;
            margin-bottom: 1.25rem;
            max-width: 720px;
        }

        .profile-action-box {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            align-items: flex-end;
        }
        .btn-book-large {
            background-color: var(--primary);
            color: #ffffff;
            font-size: 1.05rem;
            font-weight: 700;
            padding: 0.85rem 2.25rem;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            box-shadow: 0 4px 16px rgba(53, 167, 104, 0.3);
            transition: all 0.2s;
        }
        .btn-book-large:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(53, 167, 104, 0.4);
        }
        .btn-directions {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            padding: 0.4rem 0.85rem;
            border: 1px solid var(--line);
            border-radius: 8px;
            transition: all 0.15s;
        }
        .btn-directions:hover { color: var(--primary); border-color: var(--primary); }

        /* Two-Column Layout */
        .layout-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        /* Section Cards */
        .section-card {
            background-color: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--line);
            box-shadow: var(--shadow-sm);
            padding: 1.75rem;
            margin-bottom: 2rem;
        }
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid var(--line);
        }
        .section-title {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Services List */
        .services-list { display: flex; flex-direction: column; gap: 0.85rem; }
        .service-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.15rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--line);
            background-color: #fafbfc;
            transition: all 0.15s;
        }
        .service-row:hover {
            border-color: #cbd5e1;
            background-color: #ffffff;
            box-shadow: var(--shadow-sm);
        }
        .service-info h4 { font-size: 1rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.2rem; }
        .service-info p { font-size: 0.85rem; color: var(--text-muted); }
        .service-badge-duration {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.3rem;
        }
        .service-action { display: flex; align-items: center; gap: 1.25rem; }
        .service-price { font-size: 1.15rem; font-weight: 800; color: var(--text-dark); white-space: nowrap; }
        .btn-service-select {
            background-color: var(--primary-light);
            color: var(--primary);
            font-size: 0.85rem;
            font-weight: 700;
            padding: 0.45rem 0.95rem;
            border-radius: 8px;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .btn-service-select:hover { background-color: var(--primary); color: #fff; }

        /* Rating Breakdown Box */
        .rating-overview {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            padding: 1.25rem;
            background-color: #fafbfc;
            border-radius: var(--radius-md);
            border: 1px solid var(--line);
        }
        .rating-big-score { text-align: center; }
        .rating-number { font-size: 3rem; font-weight: 800; color: var(--text-dark); line-height: 1; margin-bottom: 0.35rem; }
        .rating-stars-gold { color: var(--star); font-size: 1.15rem; }
        .rating-total-count { font-size: 0.82rem; color: var(--text-muted); margin-top: 0.3rem; }

        .rating-bars { flex: 1; display: flex; flex-direction: column; gap: 0.4rem; }
        .bar-row { display: flex; align-items: center; gap: 0.65rem; font-size: 0.82rem; color: var(--text-muted); }
        .bar-track { flex: 1; height: 8px; background-color: #e2e8f0; border-radius: 4px; overflow: hidden; }
        .bar-fill { height: 100%; background-color: var(--star); border-radius: 4px; }

        /* Reviews List */
        .reviews-list { display: flex; flex-direction: column; gap: 1rem; }
        .review-card {
            padding: 1.25rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--line);
            background-color: #ffffff;
        }
        .review-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem; }
        .review-author { font-weight: 700; color: var(--text-dark); font-size: 0.95rem; display: flex; align-items: center; gap: 0.4rem; }
        .review-date { font-size: 0.8rem; color: var(--text-muted); }
        .review-stars { color: var(--star); font-size: 0.88rem; margin-bottom: 0.5rem; }
        .review-body { font-size: 0.9rem; color: #475569; line-height: 1.5; }

        /* Sidebar Widgets */
        .sidebar-widget {
            background-color: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--line);
            box-shadow: var(--shadow-sm);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .widget-title { font-size: 1.05rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-dark); }
        .info-row { display: flex; align-items: flex-start; gap: 0.75rem; margin-bottom: 0.9rem; font-size: 0.88rem; color: #475569; }
        .info-row i { color: var(--primary); margin-top: 3px; font-size: 1rem; }

        /* Sticky Mobile Action Bar */
        .mobile-booking-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #ffffff;
            border-top: 1px solid var(--line);
            padding: 0.75rem 1.25rem;
            box-shadow: 0 -4px 16px rgba(0,0,0,0.08);
            display: none;
            align-items: center;
            justify-content: space-between;
            z-index: 999;
        }
        .mobile-price-preview .lbl { font-size: 0.75rem; color: var(--text-muted); }
        .mobile-price-preview .val { font-size: 1.1rem; font-weight: 800; color: var(--primary); }

        @media (max-width: 900px) {
            .layout-grid { grid-template-columns: 1fr; }
            .profile-action-box { align-items: flex-start; width: 100%; }
            .btn-book-large { width: 100%; justify-content: center; }
            .mobile-booking-bar { display: flex; }
            body { padding-bottom: 70px; }
        }
    </style>
</head>
<body>

    <!-- Top Navbar -->
    <header class="navbar">
        <div class="container navbar-content">
            <a href="<?php echo base_url('marketplace'); ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Marketplace'e Dön
            </a>
            <a href="<?php echo base_url('marketplace'); ?>" style="font-weight: 800; color: var(--primary); font-size: 1.1rem;">
                BooKi
            </a>
        </div>
    </header>

    <main class="container">
        <!-- Business Profile Hero -->
        <article class="profile-hero">
            <div class="profile-cover">
                <?php if (!empty($tenant['cover_image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($tenant['cover_image_url']); ?>" alt="<?php echo htmlspecialchars($display_name); ?>">
                <?php else: ?>
                    <div class="profile-cover-placeholder">
                        <i class="fas fa-spa"></i>
                    </div>
                <?php endif; ?>
            </div>

            <div class="profile-header-body">
                <div class="profile-main-info">
                    <h1 class="profile-title">
                        <?php echo htmlspecialchars($display_name); ?>
                        <span class="badge-verified"><i class="fas fa-check-circle"></i> Doğrulanmış İşletme</span>
                    </h1>

                    <div class="profile-meta-tags">
                        <?php if (!empty($tenant['category'])): ?>
                            <span class="meta-tag"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($tenant['category']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($tenant['city']) || !empty($tenant['district'])): ?>
                            <span class="meta-tag"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(implode(', ', array_filter([$tenant['district'] ?? null, $tenant['city'] ?? null]))); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($tenant['price_range'])): ?>
                            <span class="meta-tag"><i class="fas fa-coins"></i> Fiyat Aralığı: <?php echo htmlspecialchars($tenant['price_range']); ?></span>
                        <?php endif; ?>
                        <?php if ($total_reviews > 0): ?>
                            <span class="meta-tag" style="color:var(--star); font-weight:700;">
                                <i class="fas fa-star"></i> <?php echo round((float)$tenant['avg_rating'], 1); ?> (<?php echo $total_reviews; ?> değerlendirme)
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($tenant['short_description'])): ?>
                        <p class="profile-description">
                            <?php echo htmlspecialchars($tenant['short_description']); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="profile-action-box">
                    <a href="<?php echo htmlspecialchars($booking_url); ?>" class="btn-book-large" target="_blank" rel="noopener">
                        <i class="fas fa-calendar-check"></i> Hemen Randevu Al
                    </a>
                    <?php 
                    $mapQuery = urlencode(($tenant['address'] ?? '') . ' ' . ($tenant['district'] ?? '') . ' ' . ($tenant['city'] ?? ''));
                    if (!empty($tenant['latitude']) && !empty($tenant['longitude'])) {
                        $mapQuery = $tenant['latitude'] . ',' . $tenant['longitude'];
                    }
                    ?>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $mapQuery; ?>" target="_blank" rel="noopener" class="btn-directions">
                        <i class="fas fa-directions"></i> Haritada Gör & Yol Tarifi
                    </a>
                </div>
            </div>
        </article>

        <!-- Two Column Content -->
        <div class="layout-grid">
            <!-- Left Column: Services & Reviews -->
            <div class="main-content-col">

                <!-- Services Menu Section -->
                <section class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-concierge-bell text-primary"></i> Hizmetler & Fiyat Listesi
                        </h2>
                        <span style="font-size:0.85rem; color:var(--text-muted); font-weight:600;">
                            <?php echo count($services); ?> Hizmet Mevcut
                        </span>
                    </div>

                    <?php if (!empty($services)): ?>
                        <div class="services-list">
                            <?php foreach ($services as $srv): ?>
                                <div class="service-row">
                                    <div class="service-info">
                                        <h4><?php echo htmlspecialchars($srv['name']); ?></h4>
                                        <?php if (!empty($srv['description'])): ?>
                                            <p><?php echo htmlspecialchars($srv['description']); ?></p>
                                        <?php endif; ?>
                                        <div class="service-badge-duration">
                                            <i class="far fa-clock"></i> <?php echo (int) $srv['duration']; ?> dakika
                                            <?php if (!empty($srv['category_name'])): ?>
                                                &nbsp;·&nbsp; <span><?php echo htmlspecialchars($srv['category_name']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="service-action">
                                        <div class="service-price">
                                            <?php echo number_format((float) $srv['price'], 0, ',', '.'); ?> <?php echo htmlspecialchars($srv['currency'] ?? 'TL'); ?>
                                        </div>
                                        <a href="<?php echo htmlspecialchars($booking_url); ?>" target="_blank" rel="noopener" class="btn-service-select">
                                            Seç <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color:var(--text-muted); text-align:center; padding:1.5rem;">İşletmenin sunduğu tüm hizmetleri randevu sihirbazında görebilirsiniz.</p>
                        <div style="text-align:center;">
                            <a href="<?php echo htmlspecialchars($booking_url); ?>" target="_blank" rel="noopener" class="btn-book-large" style="font-size:0.9rem; padding:0.6rem 1.5rem;">
                                Tüm Hizmetleri Gör & Randevu Al
                            </a>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Reviews Section -->
                <section class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-comments text-primary"></i> Müşteri Değerlendirmeleri
                        </h2>
                        <?php if ($total_reviews > 0): ?>
                            <span style="font-weight:700; color:var(--text-dark);">
                                <?php echo $total_reviews; ?> Onaylı Yorum
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($total_reviews > 0): ?>
                        <!-- Rating Overview Breakdown -->
                        <div class="rating-overview">
                            <div class="rating-big-score">
                                <div class="rating-number"><?php echo round((float)$tenant['avg_rating'], 1); ?></div>
                                <div class="rating-stars-gold">
                                    <?php 
                                    $fullStars = floor((float)$tenant['avg_rating']);
                                    for ($i = 0; $i < 5; $i++) {
                                        echo $i < $fullStars ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                </div>
                                <div class="rating-total-count"><?php echo $total_reviews; ?> değerlendirme</div>
                            </div>

                            <div class="rating-bars">
                                <?php for ($stars = 5; $stars >= 1; $stars--): 
                                    $cnt = $rating_dist[$stars] ?? 0;
                                    $pct = $total_reviews > 0 ? round(($cnt / $total_reviews) * 100) : 0;
                                ?>
                                    <div class="bar-row">
                                        <span style="width: 45px;"><?php echo $stars; ?> Yıldız</span>
                                        <div class="bar-track">
                                            <div class="bar-fill" style="width: <?php echo $pct; ?>%;"></div>
                                        </div>
                                        <span style="width: 35px; text-align: right;"><?php echo $cnt; ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <!-- Reviews List -->
                        <div class="reviews-list">
                            <?php foreach ($reviews as $rev): ?>
                                <div class="review-card">
                                    <div class="review-header">
                                        <div class="review-author">
                                            <i class="fas fa-user-circle text-muted" style="font-size:1.2rem;"></i>
                                            <span><?php echo htmlspecialchars($rev['customer_name']); ?></span>
                                            <span style="font-size:0.75rem; color:#10b981; font-weight:600;"><i class="fas fa-check-circle"></i> Onaylı Müşteri</span>
                                        </div>
                                        <span class="review-date">
                                            <?php echo date('d.m.Y', strtotime($rev['created_at'])); ?>
                                        </span>
                                    </div>

                                    <div class="review-stars">
                                        <?php for ($s = 0; $s < (int)$rev['rating']; $s++): ?>
                                            <i class="fas fa-star"></i>
                                        <?php endfor; ?>
                                    </div>

                                    <?php if (!empty($rev['comment'])): ?>
                                        <p class="review-body">
                                            <?php echo nl2br(htmlspecialchars($rev['comment'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>
                        <div style="text-align:center; padding: 2rem; color:var(--text-muted);">
                            <i class="far fa-comment-dots" style="font-size:2.5rem; margin-bottom:0.75rem; color:#cbd5e1;"></i>
                            <p>Bu işletme için henüz onaylanmış bir müşteri yorumu bulunmuyor.</p>
                            <small>Siz de randevunuzu tamamladıktan sonra deneyiminizi paylaşabilirsiniz.</small>
                        </div>
                    <?php endif; ?>
                </section>

            </div>

            <!-- Right Column: Sidebar Info & Location -->
            <aside class="sidebar-col">
                <div class="sidebar-widget">
                    <h3 class="widget-title"><i class="fas fa-info-circle text-primary me-1"></i> İletişim & Lokasyon</h3>

                    <?php if (!empty($tenant['address'])): ?>
                        <div class="info-row">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong>Adres:</strong><br>
                                <?php echo nl2br(htmlspecialchars($tenant['address'])); ?>
                                <?php if (!empty($tenant['city']) || !empty($tenant['district'])): ?>
                                    <br><?php echo htmlspecialchars(implode(', ', array_filter([$tenant['district'] ?? null, $tenant['city'] ?? null]))); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($tenant['phone_number'])): ?>
                        <div class="info-row">
                            <i class="fas fa-phone-alt"></i>
                            <div>
                                <strong>Telefon:</strong><br>
                                <a href="tel:<?php echo htmlspecialchars($tenant['phone_number']); ?>" style="color:var(--primary); font-weight:600;">
                                    <?php echo htmlspecialchars($tenant['phone_number']); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="info-row">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <strong>Güvenli Rezervasyon:</strong><br>
                            Randevunuz işletmeye doğrudan iletilir, anında onaylanır.
                        </div>
                    </div>

                    <div style="margin-top:1.5rem;">
                        <a href="<?php echo htmlspecialchars($booking_url); ?>" target="_blank" rel="noopener" class="btn-book-large" style="width:100%; justify-content:center; font-size:0.95rem;">
                            Randevu Oluştur
                        </a>
                    </div>
                </div>

                <div class="sidebar-widget" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color:#fff;">
                    <h3 class="widget-title" style="color:#fff;"><i class="fas fa-qrcode text-primary me-1"></i> Kolay Karşılama</h3>
                    <p style="font-size:0.85rem; color:#94a3b8; line-height:1.5; margin-bottom:1rem;">
                        Randevu aldığınızda size özel QR kodunuz oluşturulur. İşletmeye ulaştığınızda kiosk ekranına QR kodunuzu göstererek saniyeler içinde giriş yapabilirsiniz.
                    </p>
                    <span style="font-size:0.8rem; color:#38bdf8;"><i class="fas fa-bolt"></i> Sıra beklemeden hızlı check-in</span>
                </div>
            </aside>
        </div>
    </main>

    <!-- Sticky Mobile Action Bar -->
    <div class="mobile-booking-bar">
        <div class="mobile-price-preview">
            <div class="lbl"><?php echo htmlspecialchars($display_name); ?></div>
            <div class="val"><?php echo $total_reviews > 0 ? '★ ' . round((float)$tenant['avg_rating'], 1) : 'Doğrulanmış'; ?></div>
        </div>
        <a href="<?php echo htmlspecialchars($booking_url); ?>" target="_blank" rel="noopener" class="btn-book-large" style="padding:0.6rem 1.4rem; font-size:0.9rem;">
            Randevu Al
        </a>
    </div>

</body>
</html>
