<?php defined('BASEPATH') or exit('No direct script access allowed');

extract(html_vars());

$display_name = $display_name ?? 'İşletme';
$canonical_url = $canonical_url ?? randevuburada_url();
$meta_description = $meta_description ?? ($display_name . ' için çalışma saatleri, adres, fotoğraflar ve randevu talebi.');
$photos = $photos ?? [];
$opening_hours = $opening_hours ?? null;
$google_reviews = $google_reviews ?? [];
$lead = $lead ?? [];
$is_claimed = $is_claimed ?? false;
$claim_url = $claim_url ?? '';
$claim_token = $claim_token ?? '';
$onboarding_url = $onboarding_url ?? '';
$is_claim_page = !empty($claim_token) && !$is_claimed;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <title><?php echo htmlspecialchars($page_title ?? ($display_name . ' — Randevu & İletişim | RandevuBurada')); ?></title>
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo base_url('assets/img/randevuburada-favicon-32.png'); ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo base_url('assets/img/randevuburada-favicon-192.png'); ?>">
    <link rel="shortcut icon" href="<?php echo base_url('assets/img/randevuburada-favicon.ico'); ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="business.business">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title ?? $display_name); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <?php if (!empty($photos[0])): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($photos[0]); ?>">
    <?php endif; ?>

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($page_title ?? $display_name); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta_description); ?>">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46Zmdt9E355iqcxI+BF5w==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Schema.org JSON-LD -->
    <?php if (!empty($json_ld)): ?>
    <script type="application/ld+json">
        <?php echo json_encode($json_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
    </script>
    <?php endif; ?>

    <?php if (!empty($breadcrumb_json_ld)): ?>
    <script type="application/ld+json">
        <?php echo json_encode($breadcrumb_json_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
    </script>
    <?php endif; ?>

    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --primary-light: #ecfdf5;
            --secondary: #0ea5e9;
            --surface: #ffffff;
            --background: #f8fafc;
            --line: #e2e8f0;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --star: #f59e0b;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 14px rgba(0, 0, 0, 0.07);
            --shadow-lg: 0 12px 28px rgba(0, 0, 0, 0.09);
            --font-display: 'Plus Jakarta Sans', sans-serif;
            --font-body: 'Inter', sans-serif;
            --radius-md: 12px;
            --radius-lg: 18px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--font-body); background-color: var(--background); color: var(--text-dark); line-height: 1.5; -webkit-font-smoothing: antialiased; }
        a { text-decoration: none; color: inherit; }

        .container { max-width: 1160px; margin: 0 auto; padding: 0 1.25rem; }

        /* Top Navbar */
        .navbar { background: var(--surface); border-bottom: 1px solid var(--line); padding: 0.85rem 0; position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow-sm); }
        .navbar .container { display: flex; align-items: center; justify-content: space-between; }
        .nav-brand { font-family: var(--font-display); font-weight: 800; font-size: 1.3rem; color: var(--primary); display: flex; align-items: center; gap: 0.4rem; }
        .nav-brand span { color: var(--text-dark); }
        .nav-back { display: flex; align-items: center; gap: 0.5rem; color: var(--text-muted); font-size: 0.875rem; }
        .nav-back:hover { color: var(--primary); }

        /* Claim Banner */
        .claim-banner { background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%); padding: 0.9rem 1.5rem; text-align: center; color: #fff; font-size: 0.95rem; font-weight: 600; }
        .claim-banner a { color: #fff; text-decoration: underline; font-weight: 700; margin-left: 0.5rem; }
        .claim-banner a:hover { opacity: 0.9; }

        /* Photo Slider */
        .photo-slider { width: 100%; overflow-x: auto; scroll-snap-type: x mandatory; display: flex; gap: 0; background: #1e293b; border-radius: 0 0 var(--radius-lg) var(--radius-lg); }
        .photo-slider::-webkit-scrollbar { display: none; }
        .photo-slide { min-width: 100%; max-width: 100%; scroll-snap-align: start; }
        .photo-slide img { width: 100%; height: 340px; object-fit: cover; display: block; }
        .photo-placeholder { width: 100%; height: 340px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%); border-radius: 0 0 var(--radius-lg) var(--radius-lg); }
        .photo-placeholder i { font-size: 4rem; color: #94a3b8; }
        .slider-nav { display: flex; justify-content: center; gap: 0.5rem; padding: 0.75rem 0; }
        .slider-dot { width: 8px; height: 8px; border-radius: 50%; background: #cbd5e1; cursor: pointer; transition: background 0.2s; }
        .slider-dot.active { background: var(--primary); width: 22px; border-radius: 4px; }

        /* Profile Header */
        .profile-header { padding: 1.5rem 0; }
        .profile-name { font-family: var(--font-display); font-size: 1.75rem; font-weight: 800; margin-bottom: 0.25rem; }
        .profile-category { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem; }
        .profile-category i { color: var(--primary); margin-right: 0.3rem; }
        .profile-location { color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.75rem; }
        .profile-location i { color: var(--secondary); }

        /* Rating */
        .rating-badge { display: inline-flex; align-items: center; gap: 0.35rem; background: #fef3c7; padding: 0.3rem 0.7rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600; color: #92400e; }
        .rating-stars { display: inline-flex; gap: 1px; }
        .rating-stars i { color: var(--star); font-size: 0.8rem; }

        /* Info Cards */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1.5rem 0; }
        @media (max-width: 640px) { .info-grid { grid-template-columns: 1fr; } }
        .info-card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-md); padding: 1rem 1.25rem; }
        .info-card h3 { font-family: var(--font-display); font-size: 0.9rem; font-weight: 700; margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.4rem; }
        .info-card h3 i { color: var(--primary); }
        .info-card p, .info-card li { font-size: 0.85rem; color: var(--text-muted); }
        .info-card ul { list-style: none; }
        .info-card ul li { padding: 0.2rem 0; display: flex; justify-content: space-between; }
        .info-card ul li .day { font-weight: 500; color: var(--text-dark); }

        /* External Links */
        .external-links { display: flex; flex-wrap: wrap; gap: 0.6rem; margin: 1rem 0 1.5rem; }
        .ext-link { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border: 1px solid var(--line); border-radius: 999px; font-size: 0.82rem; font-weight: 500; color: var(--text-dark); transition: all 0.2s; }
        .ext-link:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
        .ext-link i { font-size: 0.9rem; }
        .ext-link.instagram i { color: #E4405F; }
        .ext-link.whatsapp i { color: #25D366; }
        .ext-link.website i { color: var(--secondary); }
        .ext-link.maps i { color: #4285F4; }

        /* Reviews Section */
        .reviews-section { margin: 1.5rem 0; }
        .reviews-section h2 { font-family: var(--font-display); font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; }
        .review-card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-md); padding: 1rem 1.25rem; margin-bottom: 0.75rem; }
        .review-author { font-weight: 600; font-size: 0.9rem; margin-bottom: 0.2rem; }
        .review-rating { display: flex; gap: 2px; margin-bottom: 0.4rem; }
        .review-rating i { color: var(--star); font-size: 0.75rem; }
        .review-text { font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; }
        .review-date { font-size: 0.75rem; color: #94a3b8; margin-top: 0.3rem; }

        /* CTA Button */
        .cta-section { position: fixed; bottom: 0; left: 0; right: 0; background: var(--surface); border-top: 1px solid var(--line); padding: 0.85rem 1.25rem; z-index: 100; box-shadow: 0 -4px 16px rgba(0,0,0,0.06); }
        .cta-btn { display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; max-width: 480px; margin: 0 auto; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: #fff; font-family: var(--font-display); font-weight: 700; font-size: 1rem; padding: 0.9rem 1.5rem; border: none; border-radius: 999px; cursor: pointer; transition: transform 0.15s, box-shadow 0.15s; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3); }
        .cta-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(16, 185, 129, 0.35); }
        body { padding-bottom: 85px; }

        /* Contact Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: var(--surface); border-radius: var(--radius-lg); width: 90%; max-width: 420px; padding: 2rem; box-shadow: var(--shadow-xl); position: relative; }
        .modal-close { position: absolute; top: 0.75rem; right: 1rem; background: none; border: none; font-size: 1.5rem; color: var(--text-muted); cursor: pointer; }
        .modal-title { font-family: var(--font-display); font-size: 1.2rem; font-weight: 700; margin-bottom: 0.3rem; }
        .modal-subtitle { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 0.3rem; }
        .form-group input { width: 100%; padding: 0.7rem 0.9rem; border: 1px solid var(--line); border-radius: 10px; font-size: 0.9rem; font-family: var(--font-body); outline: none; transition: border-color 0.2s; }
        .form-group input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(16,185,129,0.1); }
        .submit-btn { width: 100%; padding: 0.8rem; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: #fff; border: none; border-radius: 999px; font-family: var(--font-display); font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: transform 0.15s; }
        .submit-btn:hover { transform: translateY(-1px); }
        .submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }

        /* Success message */
        .success-msg { text-align: center; padding: 1.5rem 0; }
        .success-msg i { font-size: 2.5rem; color: var(--primary); margin-bottom: 0.75rem; display: block; }
        .success-msg h3 { font-family: var(--font-display); font-size: 1.1rem; font-weight: 700; margin-bottom: 0.3rem; }
        .success-msg p { font-size: 0.85rem; color: var(--text-muted); }

        /* Claim Page Hero */
        .claim-hero { text-align: center; padding: 3rem 1.5rem; background: linear-gradient(135deg, var(--primary-light) 0%, #dbeafe 100%); border-radius: var(--radius-lg); margin: 1.5rem 0; }
        .claim-hero h1 { font-family: var(--font-display); font-size: 1.6rem; font-weight: 800; color: var(--text-dark); margin-bottom: 0.5rem; }
        .claim-hero p { font-size: 0.95rem; color: var(--text-muted); max-width: 480px; margin: 0 auto 1.5rem; }
        .claim-hero .claim-start-btn { display: inline-flex; align-items: center; gap: 0.5rem; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: #fff; padding: 0.9rem 2rem; border-radius: 999px; font-family: var(--font-display); font-weight: 700; font-size: 1rem; box-shadow: 0 6px 20px rgba(16,185,129,0.3); transition: transform 0.15s; }
        .claim-hero .claim-start-btn:hover { transform: translateY(-2px); }

        .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 2rem 0; }
        @media (max-width: 640px) { .features-grid { grid-template-columns: 1fr; } }
        .feature-card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-md); padding: 1.25rem; text-align: center; }
        .feature-card i { font-size: 1.5rem; color: var(--primary); margin-bottom: 0.5rem; display: block; }
        .feature-card h3 { font-family: var(--font-display); font-size: 0.9rem; font-weight: 700; margin-bottom: 0.3rem; }
        .feature-card p { font-size: 0.8rem; color: var(--text-muted); }

        /* Footer */
        .page-footer { background: var(--surface); border-top: 1px solid var(--line); padding: 1.5rem 0; text-align: center; color: var(--text-muted); font-size: 0.8rem; margin-top: 2rem; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="container">
        <a href="<?php echo randevuburada_url(); ?>" class="nav-brand" style="display:flex; align-items:center; gap:0.5rem; text-decoration:none;">
            <img src="<?php echo base_url('assets/img/randevuburada-logo.png'); ?>" alt="RandevuBurada" style="height:36px; width:auto; display:block;">
        </a>
        <a href="<?php echo randevuburada_url(); ?>" class="nav-back">
            <i class="fa-solid fa-arrow-left"></i> Tüm İşletmeler
        </a>
    </div>
</nav>

<?php if (!$is_claimed && !$is_claim_page): ?>
<!-- Claim Banner for unclaimed profiles -->
<div class="claim-banner">
    <i class="fa-solid fa-store"></i>
    İşletme Sahibi misiniz?
    <a href="<?php echo htmlspecialchars($claim_url); ?>">Profilinizi Sahiplenin →</a>
</div>
<?php endif; ?>

<?php if ($is_claim_page): ?>
<!-- ============= CLAIM PAGE ============= -->
<div class="container">
    <div class="claim-hero">
        <h1><i class="fa-solid fa-shield-check" style="color: var(--primary);"></i> <?php echo htmlspecialchars($display_name); ?></h1>
        <p>Profilinizi sahiplenerek, RandevuBurada'da müşterilerinizle doğrudan bağlantı kurun. Randevu yönetimi, müşteri portföyü ve online rezervasyon — hepsi tek platformda.</p>
        <a href="<?php echo htmlspecialchars($onboarding_url); ?>" class="claim-start-btn">
            <i class="fa-solid fa-rocket"></i> Profilimi Sahiplen
        </a>
    </div>

    <div class="features-grid">
        <div class="feature-card">
            <i class="fa-solid fa-calendar-days"></i>
            <h3>Online Randevu Takvimi</h3>
            <p>Müşterileriniz 7/24 randevu alabilir. Çakışma önleyici 3 katmanlı sistem.</p>
        </div>
        <div class="feature-card">
            <i class="fa-solid fa-users"></i>
            <h3>Müşteri Portföyü</h3>
            <p>Müşteri geçmişi, tercihler ve otomatik hatırlatmalar.</p>
        </div>
        <div class="feature-card">
            <i class="fa-solid fa-chart-line"></i>
            <h3>Profesyonel Profil</h3>
            <p>Hizmetlerinizi, fotoğraflarınızı ve yorumlarınızı sergileyin.</p>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ============= BUSINESS PROFILE PAGE ============= -->

<!-- Photo Slider -->
<?php if (!empty($photos)): ?>
<div class="photo-slider" id="photoSlider">
    <?php foreach ($photos as $i => $photo_url): ?>
    <div class="photo-slide">
        <img src="<?php echo htmlspecialchars($photo_url); ?>" alt="<?php echo htmlspecialchars($display_name); ?> - Fotoğraf <?php echo $i + 1; ?>" loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>">
    </div>
    <?php endforeach; ?>
</div>
<?php if (count($photos) > 1): ?>
<div class="slider-nav">
    <?php foreach ($photos as $i => $_): ?>
    <div class="slider-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-slide="<?php echo $i; ?>"></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php else: ?>
<div class="photo-placeholder">
    <i class="fa-solid fa-store"></i>
</div>
<?php endif; ?>

<div class="container">
    <!-- Profile Header -->
    <div class="profile-header">
        <h1 class="profile-name"><?php echo htmlspecialchars($display_name); ?></h1>

        <?php if (!empty($lead['sector'])): ?>
        <div class="profile-category">
            <i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($lead['sector']); ?>
        </div>
        <?php endif; ?>

        <?php
        $location_parts = array_filter([
            $lead['neighborhood'] ?? '',
            $lead['district'] ?? '',
            $lead['city'] ?? '',
        ]);
        ?>
        <?php if (!empty($location_parts)): ?>
        <div class="profile-location">
            <i class="fa-solid fa-location-dot"></i>
            <?php echo htmlspecialchars(implode(', ', $location_parts)); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($lead['rating']) && (float) $lead['rating'] > 0): ?>
        <div class="rating-badge">
            <div class="rating-stars">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                    <?php if ($s <= round((float) $lead['rating'])): ?>
                        <i class="fa-solid fa-star"></i>
                    <?php else: ?>
                        <i class="fa-regular fa-star"></i>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            <?php echo number_format((float) $lead['rating'], 1); ?>
            <?php if (!empty($lead['user_rating_count'])): ?>
                <span style="font-weight:400; color: #b45309;">(<?php echo (int) $lead['user_rating_count']; ?> yorum)</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- External Links -->
    <div class="external-links">
        <?php if (!empty($lead['google_maps_uri'])): ?>
        <a href="<?php echo htmlspecialchars($lead['google_maps_uri']); ?>" target="_blank" rel="noopener" class="ext-link maps">
            <i class="fa-solid fa-map-location-dot"></i> Google Haritalar'da Aç
        </a>
        <?php endif; ?>

        <?php if (!empty($lead['instagram_url'])): ?>
        <a href="<?php echo htmlspecialchars($lead['instagram_url']); ?>" target="_blank" rel="noopener" class="ext-link instagram">
            <i class="fa-brands fa-instagram"></i> Instagram
        </a>
        <?php endif; ?>

        <?php
        $wa_num = $lead['clean_whatsapp'] ?? ($lead['whatsapp_number'] ?? '');
        if ($wa_num !== ''):
        ?>
        <a href="https://wa.me/<?php echo urlencode($wa_num); ?>" target="_blank" rel="noopener" class="ext-link whatsapp">
            <i class="fa-brands fa-whatsapp"></i> WhatsApp
        </a>
        <?php endif; ?>

        <?php if (!empty($lead['website_url'])): ?>
        <a href="<?php echo htmlspecialchars($lead['website_url']); ?>" target="_blank" rel="noopener" class="ext-link website">
            <i class="fa-solid fa-globe"></i> Web Sitesi
        </a>
        <?php endif; ?>
    </div>

    <!-- Info Grid -->
    <div class="info-grid">
        <!-- Address Card -->
        <?php if (!empty($lead['address'])): ?>
        <div class="info-card">
            <h3><i class="fa-solid fa-location-dot"></i> Adres</h3>
            <p><?php echo htmlspecialchars($lead['address']); ?></p>
        </div>
        <?php endif; ?>

        <!-- Phone Card -->
        <?php if (!empty($lead['phone'])): ?>
        <div class="info-card">
            <h3><i class="fa-solid fa-phone"></i> Telefon</h3>
            <p><a href="tel:<?php echo htmlspecialchars($lead['phone']); ?>"><?php echo htmlspecialchars($lead['phone']); ?></a></p>
        </div>
        <?php endif; ?>

        <!-- Opening Hours Card -->
        <?php if ($opening_hours && !empty($opening_hours['weekdayDescriptions'])): ?>
        <div class="info-card" style="grid-column: span 2;">
            <h3><i class="fa-solid fa-clock"></i> Çalışma Saatleri</h3>
            <ul>
                <?php foreach ($opening_hours['weekdayDescriptions'] as $day_desc): ?>
                <li>
                    <?php
                    $parts = explode(':', $day_desc, 2);
                    ?>
                    <span class="day"><?php echo htmlspecialchars(trim($parts[0])); ?></span>
                    <span><?php echo htmlspecialchars(trim($parts[1] ?? '')); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <!-- Google Reviews -->
    <?php if (!empty($google_reviews)): ?>
    <div class="reviews-section">
        <h2><i class="fa-solid fa-star" style="color: var(--star);"></i> Müşteri Yorumları</h2>
        <?php foreach (array_slice($google_reviews, 0, 5) as $review): ?>
        <div class="review-card">
            <div class="review-author">
                <?php echo htmlspecialchars($review['authorAttribution']['displayName'] ?? 'Müşteri'); ?>
            </div>
            <div class="review-rating">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                    <?php if ($s <= (int) ($review['rating'] ?? 5)): ?>
                        <i class="fa-solid fa-star"></i>
                    <?php else: ?>
                        <i class="fa-regular fa-star"></i>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            <?php if (!empty($review['text']['text'])): ?>
            <div class="review-text"><?php echo htmlspecialchars($review['text']['text']); ?></div>
            <?php endif; ?>
            <?php if (!empty($review['relativePublishTimeDescription'])): ?>
            <div class="review-date"><?php echo htmlspecialchars($review['relativePublishTimeDescription']); ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Map Embed (if coordinates available) -->
    <?php if (!empty($lead['latitude']) && !empty($lead['longitude'])): ?>
    <div class="info-card" style="margin-bottom: 1.5rem;">
        <h3><i class="fa-solid fa-map"></i> Konum</h3>
        <div style="width: 100%; height: 250px; border-radius: 10px; overflow: hidden; margin-top: 0.5rem;">
            <iframe
                width="100%"
                height="100%"
                style="border:0;"
                loading="lazy"
                allowfullscreen
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps?q=<?php echo urlencode($lead['latitude'] . ',' . $lead['longitude']); ?>&output=embed">
            </iframe>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- CTA Bottom Bar (Unclaimed only) -->
<?php if (!$is_claimed): ?>
<div class="cta-section">
    <button class="cta-btn" id="openContactModal">
        <i class="fa-solid fa-calendar-plus"></i> Randevu / Bilgi Talep Et
    </button>
</div>

<!-- Contact Request Modal -->
<div class="modal-overlay" id="contactModal">
    <div class="modal-box">
        <button class="modal-close" id="closeModal">&times;</button>

        <div id="contactForm">
            <div class="modal-title"><?php echo htmlspecialchars($display_name); ?></div>
            <div class="modal-subtitle">Randevu veya bilgi talebi gönderin. İşletme en kısa sürede sizinle iletişime geçecektir.</div>

            <div class="form-group">
                <label for="customerName">Adınız Soyadınız</label>
                <input type="text" id="customerName" placeholder="Örn: Ayşe Yılmaz" required>
            </div>
            <div class="form-group">
                <label for="customerPhone">Telefon Numaranız</label>
                <input type="tel" id="customerPhone" placeholder="05xx xxx xx xx" required>
            </div>

            <button class="submit-btn" id="submitContact">
                <i class="fa-solid fa-paper-plane"></i> Gönder
            </button>
        </div>

        <div id="contactSuccess" class="success-msg" style="display: none;">
            <i class="fa-solid fa-circle-check"></i>
            <h3>Talebiniz İletildi!</h3>
            <p>İşletme en kısa sürede sizinle iletişime geçecektir.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endif; /* end if not claim page */ ?>

<!-- Footer -->
<footer class="page-footer">
    <div class="container">
        &copy; <?php echo date('Y'); ?> RandevuBurada — <a href="<?php echo randevuburada_url(); ?>" style="color: var(--primary);">BooKi</a> tarafından desteklenmektedir.
    </div>
</footer>

<script>
// Photo slider navigation
(function() {
    const slider = document.getElementById('photoSlider');
    const dots = document.querySelectorAll('.slider-dot');
    if (!slider || dots.length === 0) return;

    function updateDots(activeIdx) {
        dots.forEach((d, i) => d.classList.toggle('active', i === activeIdx));
    }

    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => {
            slider.scrollTo({ left: slider.clientWidth * i, behavior: 'smooth' });
            updateDots(i);
        });
    });

    slider.addEventListener('scroll', () => {
        const idx = Math.round(slider.scrollLeft / slider.clientWidth);
        updateDots(idx);
    });
})();

// Contact modal
(function() {
    const openBtn = document.getElementById('openContactModal');
    const closeBtn = document.getElementById('closeModal');
    const modal = document.getElementById('contactModal');
    const submitBtn = document.getElementById('submitContact');

    if (!openBtn || !modal) return;

    openBtn.addEventListener('click', () => modal.classList.add('active'));
    closeBtn.addEventListener('click', () => modal.classList.remove('active'));
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('active'); });

    submitBtn.addEventListener('click', async () => {
        const name = document.getElementById('customerName').value.trim();
        const phone = document.getElementById('customerPhone').value.trim();

        if (!name || !phone) {
            alert('Lütfen adınızı ve telefon numaranızı girin.');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Gönderiliyor...';

        try {
            const resp = await fetch('<?php echo randevuburada_url('isletme/contact'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    lead_id: '<?php echo (int) ($lead['id'] ?? 0); ?>',
                    customer_name: name,
                    customer_phone: phone,
                }),
            });
            const data = await resp.json();

            document.getElementById('contactForm').style.display = 'none';
            document.getElementById('contactSuccess').style.display = 'block';
        } catch (err) {
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Gönder';
        }
    });
})();
</script>

</body>
</html>

