<?php defined('BASEPATH') or exit('No direct script access allowed');

// Standalone public marketplace discovery portal.
extract(html_vars());

$selected_q = $selected_q ?? '';
$selected_category = $selected_category ?? '';
$selected_city = $selected_city ?? '';
$selected_district = $selected_district ?? '';
$selected_sort = $selected_sort ?? 'recommended';
$lat = $lat ?? '';
$lng = $lng ?? '';
$canonical_url = $canonical_url ?? base_url('marketplace');
$meta_description = $meta_description ?? 'Şehrinizdeki en iyi kuaför, güzellik merkezi ve klinikleri keşfedin, müşteri yorumlarını inceleyin ve hemen online randevu alın.';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'BooKi — Hizmet & Randevu Pazar Yeri'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta property="og:image" content="<?php echo base_url('assets/img/social-card.png'); ?>">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="twitter:image" content="<?php echo base_url('assets/img/social-card.png'); ?>">

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

        .container { max-width: 1240px; margin: 0 auto; padding: 0 1.25rem; }

        /* Top Navigation */
        .navbar {
            background-color: var(--surface);
            border-bottom: 1px solid var(--line);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 0.85rem 0;
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95);
        }
        .navbar-content { display: flex; align-items: center; justify-content: space-between; }
        .nav-logo { display: flex; align-items: center; gap: 0.65rem; font-size: 1.25rem; font-weight: 800; color: var(--primary); }
        .nav-logo img { height: 32px; width: auto; }
        .nav-actions { display: flex; align-items: center; gap: 0.75rem; }
        .btn-nav-link { font-size: 0.9rem; font-weight: 600; color: var(--text-muted); padding: 0.5rem 0.85rem; border-radius: 8px; transition: all 0.15s; }
        .btn-nav-link:hover { color: var(--text-dark); background-color: #f1f5f9; }
        .btn-nav-cta { background-color: var(--primary); color: #ffffff !important; font-size: 0.88rem; font-weight: 600; padding: 0.55rem 1.15rem; border-radius: 8px; transition: all 0.2s; box-shadow: 0 2px 8px rgba(53, 167, 104, 0.25); }
        .btn-nav-cta:hover { background-color: var(--primary-dark); transform: translateY(-1px); }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #ffffff;
            padding: 3.5rem 0 3rem;
            position: relative;
            overflow: hidden;
        }
        .hero::after {
            content: "";
            position: absolute;
            top: -50%;
            right: -20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(53, 167, 104, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .hero-title { font-size: 2.35rem; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 0.75rem; line-height: 1.2; text-align: center; }
        .hero-title span { color: var(--primary); }
        .hero-subtitle { font-size: 1.05rem; color: #94a3b8; text-align: center; max-width: 650px; margin: 0 auto 2rem; font-weight: 400; }

        /* Main Search Bar in Hero */
        .hero-search-box {
            background-color: var(--surface);
            border-radius: 16px;
            padding: 0.6rem;
            box-shadow: var(--shadow-lg);
            max-width: 860px;
            margin: 0 auto;
        }
        .hero-search-form { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .search-input-group {
            flex: 1 1 240px;
            position: relative;
            display: flex;
            align-items: center;
        }
        .search-input-group i {
            position: absolute;
            left: 1rem;
            color: #94a3b8;
            font-size: 0.95rem;
        }
        .search-input-group input,
        .search-input-group select {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.6rem;
            border: 1px solid var(--line);
            border-radius: 10px;
            font-size: 0.92rem;
            font-family: inherit;
            color: var(--text-dark);
            background-color: #ffffff;
            min-height: 48px;
            outline: none;
            transition: border-color 0.15s;
        }
        .search-input-group input:focus,
        .search-input-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(53, 167, 104, 0.15);
        }
        .btn-search {
            padding: 0.75rem 1.6rem;
            background-color: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        .btn-search:hover { background-color: var(--primary-dark); transform: translateY(-1px); }

        /* Category Pills */
        .category-pills {
            display: flex;
            gap: 0.6rem;
            overflow-x: auto;
            padding: 1.5rem 0 0.5rem;
            scrollbar-width: none;
            justify-content: center;
            flex-wrap: wrap;
        }
        .category-pills::-webkit-scrollbar { display: none; }
        .category-pill {
            background-color: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #e2e8f0;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.45rem 0.95rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .category-pill:hover,
        .category-pill.active {
            background-color: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            transform: translateY(-1px);
        }

        /* Filter & Sorting Bar */
        .discovery-bar {
            background-color: var(--surface);
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            margin: 2rem 0 1.5rem;
            border: 1px solid var(--line);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .discovery-count { font-size: 0.92rem; font-weight: 600; color: var(--text-dark); }
        .discovery-count span { color: var(--primary); font-weight: 800; }
        .discovery-controls { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
        .control-select {
            padding: 0.45rem 0.8rem;
            border: 1px solid var(--line);
            border-radius: 8px;
            font-size: 0.85rem;
            color: var(--text-dark);
            background-color: #fff;
            min-height: 40px;
            outline: none;
            cursor: pointer;
        }
        .btn-geo {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.85rem;
            border: 1px solid var(--line);
            background-color: #ffffff;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-dark);
            cursor: pointer;
            min-height: 40px;
            transition: all 0.15s;
        }
        .btn-geo:hover { border-color: var(--primary); color: var(--primary); }

        /* Business Cards Grid */
        .businesses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        .business-card {
            background-color: var(--surface);
            border-radius: var(--radius-lg);
            overflow: hidden;
            border: 1px solid var(--line);
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .business-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: #cbd5e1;
        }
        .card-cover {
            width: 100%;
            height: 180px;
            background-color: #e2e8f0;
            position: relative;
            overflow: hidden;
        }
        .card-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .business-card:hover .card-cover img { transform: scale(1.04); }
        .card-cover-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #94a3b8;
            font-size: 2.75rem;
        }
        .badge-verified {
            position: absolute;
            top: 0.85rem;
            left: 0.85rem;
            background-color: rgba(15, 23, 42, 0.85);
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.3rem 0.65rem;
            border-radius: 50px;
            backdrop-filter: blur(4px);
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .badge-verified i { color: #38bdf8; }
        .badge-category {
            position: absolute;
            bottom: 0.85rem;
            left: 0.85rem;
            background-color: rgba(255, 255, 255, 0.95);
            color: var(--text-dark);
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.65rem;
            border-radius: 6px;
            box-shadow: var(--shadow-sm);
        }

        .card-body {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        .card-title-link {
            font-size: 1.18rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.4rem;
            letter-spacing: -0.01em;
            display: block;
            line-height: 1.3;
        }
        .card-title-link:hover { color: var(--primary); }

        .card-location {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .card-description {
            font-size: 0.88rem;
            color: #475569;
            margin-bottom: 1rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex-grow: 1;
        }

        .card-footer-metrics {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 0.85rem;
            border-top: 1px solid var(--line);
            margin-top: auto;
        }
        .card-rating {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.88rem;
        }
        .card-rating .stars { color: var(--star); font-size: 0.95rem; }
        .card-rating .score { font-weight: 700; color: var(--text-dark); }
        .card-rating .count { color: var(--text-muted); font-size: 0.8rem; }
        .btn-card-book {
            background-color: var(--primary);
            color: #ffffff;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 0.45rem 1rem;
            border-radius: 8px;
            transition: all 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .btn-card-book:hover { background-color: var(--primary-dark); }

        /* Empty State */
        .empty-state {
            background-color: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--line);
            padding: 3.5rem 2rem;
            text-align: center;
            margin: 2rem 0;
            box-shadow: var(--shadow-sm);
        }
        .empty-state-icon {
            width: 72px;
            height: 72px;
            background-color: var(--primary-light);
            color: var(--primary);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.85rem;
            margin-bottom: 1.25rem;
        }
        .empty-state h3 { font-size: 1.3rem; font-weight: 700; margin-bottom: 0.5rem; }
        .empty-state p { color: var(--text-muted); font-size: 0.95rem; max-width: 480px; margin: 0 auto 1.5rem; }

        /* Pagination */
        .pagination { display: flex; justify-content: center; gap: 0.45rem; margin: 2rem 0 3.5rem; flex-wrap: wrap; }
        .page-link {
            min-width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            border-radius: 8px;
            background-color: var(--surface);
            color: var(--text-dark);
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.15s;
        }
        .page-link:hover { border-color: var(--primary); color: var(--primary); }
        .page-link.active { background-color: var(--primary); border-color: var(--primary); color: #fff; }
        .page-link.disabled { opacity: 0.45; pointer-events: none; }

        /* Toast Container */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            background-color: #1e293b;
            color: #fff;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInRight 0.3s ease;
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Footer */
        .footer {
            background-color: #0f172a;
            color: #94a3b8;
            padding: 3rem 0 2rem;
            border-top: 1px solid #1e293b;
            margin-top: 4rem;
        }
        .footer-content { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 2rem; margin-bottom: 2.5rem; }
        .footer-col h4 { color: #ffffff; font-size: 1rem; font-weight: 700; margin-bottom: 1rem; }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 0.5rem; font-size: 0.88rem; }
        .footer-col ul li a:hover { color: #ffffff; }
        .footer-bottom { border-top: 1px solid #1e293b; padding-top: 1.5rem; text-align: center; font-size: 0.82rem; }

        @media (max-width: 768px) {
            .hero-title { font-size: 1.85rem; }
            .hero-search-form { flex-direction: column; }
            .discovery-bar { flex-direction: column; align-items: flex-start; }
            .discovery-controls { width: 100%; }
            .control-select, .btn-geo { width: 100%; }
        }
    </style>
</head>
<body>

    <!-- Header Navbar -->
    <header class="navbar">
        <div class="container navbar-content">
            <a href="<?php echo base_url('marketplace'); ?>" class="nav-logo">
                <i class="fas fa-calendar-check"></i>
                <span>BooKi<span style="color:#0f172a">Market</span></span>
            </a>
            <div class="nav-actions">
                <a href="<?php echo base_url('sitemap.xml'); ?>" class="btn-nav-link" target="_blank">Sitemap</a>
                <a href="<?php echo base_url('login'); ?>" class="btn-nav-link">Giriş Yap</a>
                <a href="<?php echo base_url('portal'); ?>" class="btn-nav-cta">İşletmenizi Ekleyin</a>
            </div>
        </div>
    </header>

    <!-- Hero Search Section -->
    <section class="hero">
        <div class="container">
            <h1 class="hero-title">Şehrinizdeki En İyi Uzmanları <span>Keşfedin</span></h1>
            <p class="hero-subtitle">Kuaför, güzellik salonu, spa, klinik ve özel uzmanlardan 7/24 anında randevu alın.</p>

            <!-- Search Form -->
            <div class="hero-search-box">
                <form method="get" action="<?php echo base_url('marketplace'); ?>" class="hero-search-form" id="searchForm">
                    <input type="hidden" name="lat" id="geoLat" value="<?php echo htmlspecialchars($lat); ?>">
                    <input type="hidden" name="lng" id="geoLng" value="<?php echo htmlspecialchars($lng); ?>">

                    <div class="search-input-group">
                        <i class="fas fa-search"></i>
                        <input type="text" name="q" placeholder="İşletme, hizmet veya anahtar kelime..." value="<?php echo htmlspecialchars($selected_q); ?>">
                    </div>

                    <div class="search-input-group" style="flex: 0 1 180px;">
                        <i class="fas fa-city"></i>
                        <select name="city" id="cityFilter" onchange="this.form.submit()">
                            <option value="">Tüm Şehirler</option>
                            <?php foreach ($cities as $c): ?>
                                <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $selected_city === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="search-input-group" style="flex: 0 1 180px;">
                        <i class="fas fa-map-marker-alt"></i>
                        <select name="district" id="districtFilter" onchange="this.form.submit()">
                            <option value="">Tüm İlçeler</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $selected_district === $d ? 'selected' : ''; ?>><?php echo htmlspecialchars($d); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i> Randevu Bul
                    </button>
                </form>
            </div>

            <!-- Popular Category Pills -->
            <div class="category-pills">
                <a href="<?php echo base_url('marketplace'); ?>" class="category-pill <?php echo empty($selected_category) ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i> Tümü
                </a>
                <?php foreach ($popular_categories as $popCat): ?>
                    <a href="<?php echo base_url('marketplace?category=' . urlencode($popCat['name'])); ?>" 
                       class="category-pill <?php echo $selected_category === $popCat['name'] ? 'active' : ''; ?>">
                        <i class="<?php echo $popCat['icon']; ?>"></i> <?php echo htmlspecialchars($popCat['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Main Results Section -->
    <main class="container">
        <!-- Controls & Filter Strip -->
        <div class="discovery-bar">
            <div class="discovery-count">
                Toplam <span><?php echo (int) $total; ?></span> işletme listeleniyor
                <?php if (!empty($selected_category) || !empty($selected_city) || !empty($selected_q)): ?>
                    <small style="color:var(--text-muted); margin-left:8px;">(Filtrelendi)</small>
                <?php endif; ?>
            </div>

            <div class="discovery-controls">
                <button type="button" class="btn-geo" id="btnGeoLocation">
                    <i class="fas fa-crosshairs text-primary"></i> Yakınımdakiler
                </button>

                <select name="sort" class="control-select" onchange="applySort(this.value)">
                    <option value="recommended" <?php echo $selected_sort === 'recommended' ? 'selected' : ''; ?>>Önerilen (Akıllı Sıralama)</option>
                    <option value="rating" <?php echo $selected_sort === 'rating' ? 'selected' : ''; ?>>En Yüksek Puan</option>
                    <option value="reviews" <?php echo $selected_sort === 'reviews' ? 'selected' : ''; ?>>En Çok Değerlendirilen</option>
                    <option value="distance" <?php echo $selected_sort === 'distance' ? 'selected' : ''; ?>>En Yakın Mesafe</option>
                    <option value="newest" <?php echo $selected_sort === 'newest' ? 'selected' : ''; ?>>En Yeni İşletmeler</option>
                </select>

                <?php if (!empty($selected_category) || !empty($selected_city) || !empty($selected_district) || !empty($selected_q) || !empty($lat)): ?>
                    <a href="<?php echo base_url('marketplace'); ?>" class="btn-nav-link" style="color:#ef4444; font-size:0.85rem;">
                        <i class="fas fa-times-circle"></i> Filtreleri Sıfırla
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Businesses Grid -->
        <?php if (!empty($tenants)): ?>
            <div class="businesses-grid">
                <?php foreach ($tenants as $tenant): 
                    $displayName = !empty($tenant['company_name']) ? $tenant['company_name'] : $tenant['subdomain'];
                    $detailUrl = base_url('marketplace/business/' . urlencode($tenant['subdomain']));
                    $appDomain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';
                    $bookUrl = !empty($tenant['custom_domain']) ? 'https://' . $tenant['custom_domain'] . '/booking?ref=marketplace' : 'https://' . $tenant['subdomain'] . '-' . $appDomain . '/booking?ref=marketplace';
                ?>
                    <article class="business-card">
                        <div class="card-cover">
                            <?php if (!empty($tenant['cover_image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($tenant['cover_image_url']); ?>" alt="<?php echo htmlspecialchars($displayName); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="card-cover-placeholder">
                                    <i class="fas fa-store"></i>
                                </div>
                            <?php endif; ?>

                            <div class="badge-verified">
                                <i class="fas fa-check-circle"></i> Doğrulanmış
                            </div>

                            <?php if (!empty($tenant['category'])): ?>
                                <span class="badge-category"><?php echo htmlspecialchars($tenant['category']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body">
                            <a href="<?php echo $detailUrl; ?>" class="card-title-link">
                                <?php echo htmlspecialchars($displayName); ?>
                            </a>

                            <div class="card-location">
                                <i class="fas fa-map-marker-alt text-muted"></i>
                                <span>
                                    <?php 
                                    $locParts = array_filter([$tenant['district'] ?? null, $tenant['city'] ?? null]);
                                    echo htmlspecialchars(!empty($locParts) ? implode(', ', $locParts) : 'Türkiye');
                                    ?>
                                </span>
                                <?php if (isset($tenant['distance'])): ?>
                                    <span style="color:var(--primary); font-weight:600; margin-left:auto;">
                                        <?php echo number_format((float) $tenant['distance'], 1); ?> km
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($tenant['short_description'])): ?>
                                <p class="card-description">
                                    <?php echo htmlspecialchars($tenant['short_description']); ?>
                                </p>
                            <?php endif; ?>

                            <div class="card-footer-metrics">
                                <div class="card-rating">
                                    <?php if ((int)$tenant['review_count'] > 0): ?>
                                        <span class="stars"><i class="fas fa-star"></i></span>
                                        <span class="score"><?php echo round((float)$tenant['avg_rating'], 1); ?></span>
                                        <span class="count">(<?php echo $tenant['review_count']; ?>)</span>
                                    <?php else: ?>
                                        <span class="count" style="color:#94a3b8;">Henüz yorum yok</span>
                                    <?php endif; ?>
                                </div>

                                <a href="<?php echo $detailUrl; ?>" class="btn-card-book">
                                    Randevu Al <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php 
                    $qs = $_GET; 
                    $buildPageUrl = function($p) use ($qs) {
                        $qs['page'] = $p;
                        return '?' . http_build_query($qs);
                    };
                    ?>
                    <?php if ($page > 1): ?>
                        <a href="<?php echo $buildPageUrl(1); ?>" class="page-link" title="İlk Sayfa">«</a>
                        <a href="<?php echo $buildPageUrl($page - 1); ?>" class="page-link" title="Önceki">‹</a>
                    <?php else: ?>
                        <span class="page-link disabled">«</span>
                        <span class="page-link disabled">‹</span>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="<?php echo $buildPageUrl($i); ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo $buildPageUrl($page + 1); ?>" class="page-link" title="Sonraki">›</a>
                        <a href="<?php echo $buildPageUrl($total_pages); ?>" class="page-link" title="Son Sayfa">»</a>
                    <?php else: ?>
                        <span class="page-link disabled">›</span>
                        <span class="page-link disabled">»</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Rich Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-search-location"></i>
                </div>
                <h3>Eşleşen İşletme Bulunamadı</h3>
                <p>Arama kriterlerinize uygun işletme bulunamadı. Lütfen filtreleri gevşetip tekrar deneyin veya farklı bir konum seçin.</p>
                <a href="<?php echo base_url('marketplace'); ?>" class="btn-nav-cta">
                    <i class="fas fa-undo me-1"></i> Tüm İşletmeleri Göster
                </a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col" style="max-width: 340px;">
                    <div class="nav-logo" style="margin-bottom: 1rem; color: #fff;">
                        <i class="fas fa-calendar-check text-primary"></i>
                        <span>BooKi</span>
                    </div>
                    <p style="font-size: 0.88rem; line-height: 1.6;">BooKi, randevu ile çalışan seçkin işletmeleri müşterilerle buluşturan yeni nesil SaaS rezervasyon platformudur.</p>
                </div>
                <div class="footer-col">
                    <h4>Keşfet</h4>
                    <ul>
                        <li><a href="<?php echo base_url('marketplace'); ?>">Tüm İşletmeler</a></li>
                        <li><a href="<?php echo base_url('marketplace?category=Kuaf%C3%B6r+%26+Sa%C3%A7'); ?>">Kuaförler</a></li>
                        <li><a href="<?php echo base_url('marketplace?category=G%C3%BCzellik+%26+Bak%C4%B1m'); ?>">Güzellik Merkezleri</a></li>
                        <li><a href="<?php echo base_url('marketplace?category=Spa+%26+Masaj'); ?>">Spa & Masaj</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>İşletmeler İçin</h4>
                    <ul>
                        <li><a href="<?php echo base_url('portal'); ?>">İşletmenizi Kaydedin</a></li>
                        <li><a href="<?php echo base_url('login'); ?>">Yönetim Paneli Girişi</a></li>
                        <li><a href="<?php echo base_url('docs'); ?>">Yardım & Dokümantasyon</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Standartlar & SEO</h4>
                    <ul>
                        <li><a href="<?php echo base_url('sitemap.xml'); ?>" target="_blank">XML Sitemap</a></li>
                        <li><a href="<?php echo base_url('robots.txt'); ?>" target="_blank">Robots.txt</a></li>
                        <li><a href="<?php echo base_url('llms.txt'); ?>" target="_blank">LLMs.txt (Yapay Zeka)</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; <?php echo date('Y'); ?> BooKi Ecosystem. Tüm hakları saklıdır.
            </div>
        </div>
    </footer>

    <script>
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.innerHTML = `<i class="fas fa-info-circle"></i> <span>${message}</span>`;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            toast.style.transition = 'all 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    function applySort(sortVal) {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', sortVal);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }

    document.getElementById('btnGeoLocation')?.addEventListener('click', function() {
        if (!navigator.geolocation) {
            showToast('Tarayıcınız konum özelliğini desteklemiyor.', 'error');
            return;
        }

        showToast('Konumunuz alınıyor...', 'info');
        navigator.geolocation.getCurrentPosition(function(pos) {
            document.getElementById('geoLat').value = pos.coords.latitude;
            document.getElementById('geoLng').value = pos.coords.longitude;
            const form = document.getElementById('searchForm');
            const sortInput = document.createElement('input');
            sortInput.type = 'hidden';
            sortInput.name = 'sort';
            sortInput.value = 'distance';
            form.appendChild(sortInput);
            form.submit();
        }, function(err) {
            showToast('Konum izni alınamadı: ' + err.message, 'error');
        });
    });
    </script>
</body>
</html>
