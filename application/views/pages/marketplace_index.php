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
$meta_description = $meta_description ?? 'Şehrinizdeki en iyi kuaför, berber, güzellik merkezi ve klinikleri keşfedin, müşteri yorumlarını inceleyin ve hemen online randevu alın.';
$app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';
$portal_url = 'https://' . $app_domain . '/portal';
$login_url = 'https://' . $app_domain . '/login';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'RandevuBurada — Türkiye\'nin Online Randevu ve Hizmet Pazaryeri | by BooKi'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description ?? 'RandevuBurada; kuaför, berber, güzellik salonu, spa ve kliniklerden 7/24 online randevu alabileceğiniz BooKi hizmet pazaryeridir.'); ?>">
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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46Zmdt9E355iqcxI+BF5w==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Schema.org JSON-LD (ItemList) -->
    <?php if (!empty($json_ld)): ?>
    <script type="application/ld+json">
        <?php echo json_encode($json_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
    </script>
    <?php endif; ?>

    <!-- Schema.org JSON-LD (FAQPage for AI Citability & GEO) -->
    <?php if (!empty($faq_json_ld)): ?>
    <script type="application/ld+json">
        <?php echo json_encode($faq_json_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
    </script>
    <?php endif; ?>

    <!-- Schema.org JSON-LD (BreadcrumbList) -->
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
            --accent: #f59e0b;
            --surface: #ffffff;
            --background: #f8fafc;
            --surface-alt: #f1f5f9;
            --line: #e2e8f0;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --text-light: #94a3b8;
            --star: #f59e0b;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.07);
            --shadow-lg: 0 10px 25px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.04);
            --shadow-xl: 0 20px 35px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
            --font-display: 'Plus Jakarta Sans', sans-serif;
            --font-body: 'Inter', sans-serif;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--font-body);
            background-color: var(--background);
            color: var(--text-dark);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }

        .container { max-width: 1360px; margin: 0 auto; padding: 0 1.5rem; }

        /* Top Navigation Header */
        .navbar {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-bottom: 1px solid var(--line);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 0.75rem 0;
            transition: all 0.2s ease;
        }
        .navbar-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
        }
        .nav-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            font-family: var(--font-display);
            font-size: 1.28rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.025em;
            white-space: nowrap;
            flex-shrink: 0;
            text-decoration: none;
        }
        .nav-brand-badge {
            background: #f0fdfa;
            color: #0f766e;
            border: 1px solid #ccfbf1;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .nav-links-center {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1 1 auto;
            min-width: 0;
            overflow-x: auto;
            white-space: nowrap;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding: 0.2rem 0;
        }
        .nav-links-center::-webkit-scrollbar { display: none; }
        .nav-link-item {
            font-size: 0.88rem;
            font-weight: 600;
            color: #475569;
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            white-space: nowrap;
            flex-shrink: 0;
            transition: all 0.15s ease;
            text-decoration: none;
        }
        .nav-link-item:hover {
            color: var(--primary-dark);
            background-color: var(--surface-alt, #f8fafc);
        }
        .nav-link-item.active {
            color: #0f766e;
            background-color: #f0fdfa;
            font-weight: 700;
        }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-shrink: 0;
            white-space: nowrap;
        }
        .partner-software-link {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: #f0fdfa;
            border: 1px solid #ccfbf1;
            color: #0f766e !important;
            font-size: 0.82rem;
            font-weight: 700;
            padding: 0.38rem 0.75rem;
            border-radius: 9999px;
            white-space: nowrap;
            flex-shrink: 0;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .partner-software-link:hover {
            background: #ccfbf1;
            color: #115e59 !important;
            transform: translateY(-1px);
        }
        .btn-nav-login {
            font-size: 0.88rem;
            font-weight: 600;
            color: #334155;
            padding: 0.45rem 0.75rem;
            border-radius: 8px;
            white-space: nowrap;
            flex-shrink: 0;
            text-decoration: none;
            transition: background-color 0.15s;
        }
        .btn-nav-login:hover { background-color: var(--surface-alt, #f1f5f9); color: #0f172a; }
        .btn-nav-business {
            background-color: #0f766e;
            color: #ffffff !important;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 0.5rem 1.1rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            white-space: nowrap;
            flex-shrink: 0;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 3px 12px rgba(15, 118, 110, 0.22);
        }
        .btn-nav-business:hover {
            background-color: #115e59;
            transform: translateY(-1px);
            box-shadow: 0 5px 16px rgba(15, 118, 110, 0.32);
        }

        /* Hero Storefront Section */
        .hero-storefront {
            background: linear-gradient(135deg, #091e2b 0%, #0f2d3a 50%, #134e4a 100%);
            color: #ffffff;
            padding: 4.5rem 0 3.5rem;
            position: relative;
            overflow: hidden;
        }
        .hero-storefront::before {
            content: "";
            position: absolute;
            top: -30%;
            right: -10%;
            width: 550px;
            height: 550px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.22) 0%, transparent 65%);
            border-radius: 50%;
            pointer-events: none;
        }
        .hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #a7f3d0;
            margin-bottom: 1.25rem;
        }
        .hero-title {
            font-family: var(--font-display);
            font-size: 2.85rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.15;
            margin-bottom: 1rem;
            max-width: 840px;
        }
        .hero-title span {
            color: #34d399;
            background: linear-gradient(120deg, #34d399 0%, #6ee7b7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-subtitle {
            font-size: 1.12rem;
            color: #cbd5e1;
            max-width: 680px;
            margin-bottom: 2.25rem;
            font-weight: 400;
            line-height: 1.55;
        }

        /* Modern 3-Field Search Container */
        .search-container {
            background-color: var(--surface);
            border-radius: 20px;
            padding: 0.65rem;
            box-shadow: var(--shadow-xl);
            max-width: 960px;
            margin-bottom: 2rem;
        }
        .search-form {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .search-field {
            flex: 1 1 240px;
            position: relative;
            display: flex;
            align-items: center;
        }
        .search-field i {
            position: absolute;
            left: 1.1rem;
            color: #94a3b8;
            font-size: 1rem;
        }
        .search-field input, .search-field select {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.75rem;
            border: 1px solid transparent;
            background-color: #f8fafc;
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--text-dark);
            outline: none;
            transition: all 0.2s;
        }
        .search-field input:focus, .search-field select:focus {
            background-color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
        }
        .btn-search-submit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #ffffff;
            font-family: var(--font-display);
            font-size: 0.95rem;
            font-weight: 700;
            padding: 0.88rem 1.85rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
        }
        .btn-search-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        /* Category Chips in Hero */
        .category-scroll {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            scrollbar-width: none;
        }
        .category-scroll::-webkit-scrollbar { display: none; }
        .category-chip {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #ffffff;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.45rem 1rem;
            border-radius: 9999px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            transition: all 0.15s ease;
        }
        .category-chip:hover, .category-chip.active {
            background-color: #ffffff;
            color: #0f172a;
            border-color: #ffffff;
            transform: translateY(-1px);
        }

        /* Trust Ribbon */
        .trust-ribbon {
            background-color: var(--surface);
            border-bottom: 1px solid var(--line);
            padding: 1.25rem 0;
            margin-bottom: 2.5rem;
        }
        .trust-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            align-items: center;
        }
        .trust-item {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }
        .trust-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background-color: var(--primary-light);
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }
        .trust-text h4 { font-size: 0.92rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.1rem; }
        .trust-text p { font-size: 0.78rem; color: var(--text-muted); }

        /* Main Storefront Area */
        .discovery-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .discovery-title {
            font-family: var(--font-display);
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.01em;
        }
        .discovery-count { font-size: 0.9rem; color: var(--text-muted); margin-left: 0.5rem; font-weight: 500; }
        .filter-actions { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
        .btn-geo {
            background-color: var(--surface);
            border: 1px solid var(--line);
            color: var(--text-dark);
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.5rem 0.95rem;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.15s;
        }
        .btn-geo:hover { border-color: var(--primary); color: var(--primary-dark); }
        .sort-select {
            padding: 0.5rem 0.9rem;
            border: 1px solid var(--line);
            border-radius: 10px;
            background-color: var(--surface);
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-dark);
            outline: none;
            cursor: pointer;
        }

        /* Business Cards Grid */
        .businesses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.75rem;
            margin-bottom: 3.5rem;
        }
        .business-card {
            background-color: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--line);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            box-shadow: var(--shadow-sm);
        }
        .business-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: #cbd5e1;
        }
        .card-cover {
            height: 190px;
            width: 100%;
            position: relative;
            background-color: #e2e8f0;
            overflow: hidden;
        }
        .card-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .business-card:hover .card-cover img {
            transform: scale(1.03);
        }
        .card-cover-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: #94a3b8;
            font-size: 3rem;
        }
        .badge-verified {
            position: absolute;
            top: 12px;
            left: 12px;
            background-color: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(4px);
            color: #0284c7;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            box-shadow: var(--shadow-sm);
        }
        .badge-category {
            position: absolute;
            bottom: 12px;
            left: 12px;
            background-color: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(4px);
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.65rem;
            border-radius: 6px;
        }
        .card-body {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        .card-title-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.4rem;
        }
        .card-title {
            font-family: var(--font-display);
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1.3;
        }
        .card-rating-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            background-color: #fef3c7;
            color: #92400e;
            padding: 0.2rem 0.45rem;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .card-location {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .card-description {
            font-size: 0.87rem;
            color: #475569;
            margin-bottom: 1.1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.5;
        }
        .card-footer-action {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 0.9rem;
            border-top: 1px solid var(--line);
        }
        .card-price-hint {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .card-price-hint strong {
            display: block;
            font-size: 0.95rem;
            color: var(--text-dark);
        }
        .btn-card-book {
            background-color: var(--primary);
            color: #ffffff;
            font-family: var(--font-display);
            font-size: 0.88rem;
            font-weight: 700;
            padding: 0.55rem 1.15rem;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.15s ease;
        }
        .btn-card-book:hover {
            background-color: var(--primary-dark);
        }

        /* FAQ Accordion Area */
        .faq-section {
            background-color: var(--surface);
            border-top: 1px solid var(--line);
            padding: 4rem 0;
            margin-top: 2rem;
        }
        .faq-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .faq-header h2 {
            font-family: var(--font-display);
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        .faq-header p {
            color: var(--text-muted);
            font-size: 1rem;
        }
        .faq-grid {
            max-width: 860px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .faq-card {
            border: 1px solid var(--line);
            border-radius: 12px;
            background-color: #fafbfc;
            padding: 1.25rem 1.5rem;
        }
        .faq-question {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.45rem;
        }
        .faq-answer {
            color: #475569;
            font-size: 0.92rem;
            line-height: 1.6;
        }

        /* Footer */
        .site-footer {
            background-color: #0b1320;
            color: #94a3b8;
            padding: 4rem 0 2rem;
            border-top: 1px solid #1e293b;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.2fr;
            gap: 2.5rem;
            margin-bottom: 3rem;
        }
        .footer-brand-title {
            font-family: var(--font-display);
            font-size: 1.4rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .footer-desc {
            font-size: 0.88rem;
            line-height: 1.65;
            color: #64748b;
            max-width: 320px;
            margin-bottom: 1.25rem;
        }
        .footer-col h4 {
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 1.2rem;
            letter-spacing: -0.01em;
        }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 0.65rem; font-size: 0.88rem; }
        .footer-col ul li a { color: #94a3b8; transition: color 0.15s; }
        .footer-col ul li a:hover { color: #34d399; }
        .footer-bottom {
            border-top: 1px solid #1e293b;
            padding-top: 1.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.82rem;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background-color: #0f172a;
            color: #fff;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: var(--shadow-xl);
            font-size: 0.9rem;
            z-index: 99999;
            display: none;
        }
        .toast.show { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 768px) {
            .hero-title { font-size: 2rem; }
            .nav-links-center { display: none; }
            .search-form { flex-direction: column; }
            .btn-search-submit { width: 100%; justify-content: center; }
            .footer-grid { grid-template-columns: 1fr; gap: 2rem; }
            .businesses-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Top Navigation -->
    <header class="navbar">
        <div class="container navbar-content">
            <a href="<?php echo function_exists('randevuburada_url') ? randevuburada_url() : 'https://randevuburada.kibusiness.co'; ?>" class="nav-brand">
                <i class="fas fa-calendar-check" style="color:var(--primary)"></i>
                <span>RandevuBurada</span>
                <span class="nav-brand-badge">by BooKi</span>
            </a>

            <nav class="nav-links-center">
                <a href="<?php echo base_url('marketplace?category=Kuaf%C3%B6r+%26+Sa%C3%A7'); ?>" class="nav-link-item <?php echo strpos($selected_category, 'Kuaf') !== false ? 'active' : ''; ?>">Kuaför & Berber</a>
                <a href="<?php echo base_url('marketplace?category=G%C3%BCzellik+%26+Bak%C4%B1m'); ?>" class="nav-link-item <?php echo strpos($selected_category, 'Güzellik') !== false ? 'active' : ''; ?>">Güzellik & Cilt</a>
                <a href="<?php echo base_url('marketplace?category=T%C4%B1rnak+%26+Estetik'); ?>" class="nav-link-item <?php echo strpos($selected_category, 'Tırnak') !== false ? 'active' : ''; ?>">Tırnak Stüdyosu</a>
                <a href="<?php echo base_url('marketplace?category=Masaj+%26+Terapi'); ?>" class="nav-link-item <?php echo strpos($selected_category, 'Masaj') !== false ? 'active' : ''; ?>">Spa & Masaj</a>
                <a href="<?php echo base_url('marketplace?category=Klinik+%26+Sa%C4%9Fl%C4%B1k'); ?>" class="nav-link-item <?php echo strpos($selected_category, 'Klinik') !== false ? 'active' : ''; ?>">Klinik & Diş</a>
            </nav>

            <div class="nav-actions">
                <a href="https://booki.kibusiness.co" target="_blank" rel="noopener" class="partner-software-link">
                    <i class="fas fa-desktop me-1"></i> BooKi Yazılımı ↗
                </a>
                <a href="<?php echo $login_url; ?>" class="btn-nav-login">
                    <i class="fas fa-user-circle me-1"></i> Giriş Yap
                </a>
                <a href="https://booki.kibusiness.co" target="_blank" rel="noopener" class="btn-nav-business">
                    <i class="fas fa-store me-1"></i> İşletmenizi Ekleyin
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Storefront Search -->
    <section class="hero-storefront">
        <div class="container">
            <div class="hero-kicker">
                <i class="fas fa-bolt"></i> 7/24 Anında Onaylı Randevu Sistemi
            </div>
            <h1 class="hero-title">
                Şehrinizdeki En İyi Uzmanları <span>Keşfedin & Randevu Alın</span>
            </h1>
            <p class="hero-subtitle">
                Kuaför, güzellik salonu, berber, klinik ve spa merkezlerinden sıra beklemeden saniyeler içinde online randevunuzu oluşturun.
            </p>

            <!-- 3-Field Search Form -->
            <div class="search-container">
                <form method="get" action="<?php echo base_url('marketplace'); ?>" class="search-form" id="searchForm">
                    <input type="hidden" name="lat" id="geoLat" value="<?php echo htmlspecialchars($lat); ?>">
                    <input type="hidden" name="lng" id="geoLng" value="<?php echo htmlspecialchars($lng); ?>">

                    <div class="search-field" style="flex: 2 1 260px;">
                        <i class="fas fa-search"></i>
                        <input type="text" name="q" placeholder="Hizmet, salon veya uzman adı..." value="<?php echo htmlspecialchars($selected_q); ?>">
                    </div>

                    <div class="search-field" style="flex: 1 1 180px;">
                        <i class="fas fa-city"></i>
                        <select name="city" id="cityFilter" onchange="this.form.submit()">
                            <option value="">Tüm Şehirler</option>
                            <?php 
                            $popular_cities = ['İstanbul', 'Ankara', 'İzmir', 'Bursa', 'Antalya'];
                            $all_cities = array_unique(array_merge($popular_cities, $cities));
                            foreach ($all_cities as $c): ?>
                                <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $selected_city === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="search-field" style="flex: 1 1 180px;">
                        <i class="fas fa-map-marker-alt"></i>
                        <select name="district" id="districtFilter" onchange="this.form.submit()">
                            <option value="">Tüm İlçeler</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $selected_district === $d ? 'selected' : ''; ?>><?php echo htmlspecialchars($d); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-search-submit">
                        <i class="fas fa-search"></i> Randevu Bul
                    </button>
                </form>
            </div>

            <!-- Category Pills -->
            <div class="category-scroll">
                <a href="<?php echo base_url('marketplace'); ?>" class="category-chip <?php echo empty($selected_category) ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i> Tüm Hizmetler
                </a>
                <?php foreach ($popular_categories as $popCat): ?>
                    <a href="<?php echo base_url('marketplace?category=' . urlencode($popCat['name'])); ?>" 
                       class="category-chip <?php echo $selected_category === $popCat['name'] ? 'active' : ''; ?>">
                        <i class="<?php echo $popCat['icon']; ?>"></i> <?php echo htmlspecialchars($popCat['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Trust Ribbon (Value Prop) -->
    <div class="trust-ribbon">
        <div class="container trust-items">
            <div class="trust-item">
                <div class="trust-icon"><i class="fas fa-check-circle"></i></div>
                <div class="trust-text">
                    <h4>Anında Onaylı Randevu</h4>
                    <p>Telefon trafiği olmadan saniyeler içinde onay</p>
                </div>
            </div>
            <div class="trust-item">
                <div class="trust-icon"><i class="fas fa-shield-alt"></i></div>
                <div class="trust-text">
                    <h4>Pazar Yeri Güvencesi</h4>
                    <p>Güvenli ödeme ve kesintisiz destek</p>
                </div>
            </div>
            <div class="trust-item">
                <div class="trust-icon"><i class="fas fa-star"></i></div>
                <div class="trust-text">
                    <h4>Doğrulanmış Yorumlar</h4>
                    <p>Yalnızca hizmet almış gerçek müşteri puanları</p>
                </div>
            </div>
            <div class="trust-item">
                <div class="trust-icon"><i class="fas fa-clock"></i></div>
                <div class="trust-text">
                    <h4>7/24 Kesintisiz Erişim</h4>
                    <p>Günün her saati dilediğiniz an randevu alın</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Results Section -->
    <main class="container">
        <!-- Results Header & Sort Strip -->
        <div class="discovery-header">
            <div>
                <h2 class="discovery-title" style="display:inline-block;">
                    <?php echo !empty($selected_category) ? htmlspecialchars($selected_category) : 'Öne Çıkan İşletmeler'; ?>
                </h2>
                <span class="discovery-count">(<?php echo (int) $total; ?> işletme bulundu)</span>
            </div>

            <div class="filter-actions">
                <button type="button" class="btn-geo" id="btnGeoLocation">
                    <i class="fas fa-crosshairs text-primary"></i> Yakınımdakiler
                </button>

                <select name="sort" class="sort-select" onchange="applySort(this.value)">
                    <option value="recommended" <?php echo $selected_sort === 'recommended' ? 'selected' : ''; ?>>Akıllı Sıralama (Önerilen)</option>
                    <option value="rating" <?php echo $selected_sort === 'rating' ? 'selected' : ''; ?>>En Yüksek Puanlılar</option>
                    <option value="reviews" <?php echo $selected_sort === 'reviews' ? 'selected' : ''; ?>>En Çok Yorum Alanlar</option>
                    <option value="distance" <?php echo $selected_sort === 'distance' ? 'selected' : ''; ?>>En Yakın Mesafe</option>
                    <option value="newest" <?php echo $selected_sort === 'newest' ? 'selected' : ''; ?>>En Yeni Eklenenler</option>
                </select>

                <?php if (!empty($selected_category) || !empty($selected_city) || !empty($selected_district) || !empty($selected_q) || !empty($lat)): ?>
                    <a href="<?php echo base_url('marketplace'); ?>" style="color:#ef4444; font-size:0.85rem; font-weight:600; margin-left:0.5rem;">
                        <i class="fas fa-times-circle"></i> Filtreleri Temizle
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
                ?>
                    <article class="business-card">
                        <div class="card-cover">
                            <?php if (!empty($tenant['cover_image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($tenant['cover_image_url']); ?>" alt="<?php echo htmlspecialchars($displayName); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="card-cover-placeholder">
                                    <i class="fas fa-spa"></i>
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
                            <div class="card-title-row">
                                <a href="<?php echo $detailUrl; ?>" class="card-title">
                                    <?php echo htmlspecialchars($displayName); ?>
                                </a>
                                <div class="card-rating-badge">
                                    <i class="fas fa-star" style="color:#d97706;"></i>
                                    <span><?php echo ((int)$tenant['review_count'] > 0) ? round((float)$tenant['avg_rating'], 1) : 'Yeni'; ?></span>
                                    <?php if ((int)$tenant['review_count'] > 0): ?>
                                        <small style="color:#78350f;">(<?php echo $tenant['review_count']; ?>)</small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card-location">
                                <i class="fas fa-map-marker-alt" style="color:var(--primary)"></i>
                                <span>
                                    <?php 
                                    $locParts = array_filter([$tenant['district'] ?? null, $tenant['city'] ?? null]);
                                    echo htmlspecialchars(!empty($locParts) ? implode(', ', $locParts) : 'Türkiye');
                                    ?>
                                </span>
                                <?php if (isset($tenant['distance'])): ?>
                                    <span style="color:var(--primary); font-weight:700; margin-left:auto;">
                                        <?php echo number_format((float) $tenant['distance'], 1); ?> km
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($tenant['short_description'])): ?>
                                <p class="card-description">
                                    <?php echo htmlspecialchars($tenant['short_description']); ?>
                                </p>
                            <?php else: ?>
                                <p class="card-description">
                                    Seçkin randevulu hizmetler, profesyonel uzman kadrosu ve anında online onay ile randevu imkanı.
                                </p>
                            <?php endif; ?>

                            <div class="card-footer-action">
                                <div class="card-price-hint">
                                    Fiyat Seviyesi
                                    <strong><?php echo htmlspecialchars($tenant['price_range'] ?? '₺₺'); ?></strong>
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
                <div style="display:flex; justify-content:center; gap:0.5rem; margin:2rem 0 4rem; flex-wrap:wrap;">
                    <?php 
                    $qs = $_GET; 
                    $buildPageUrl = function($p) use ($qs) {
                        $qs['page'] = $p;
                        return '?' . http_build_query($qs);
                    };
                    ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="<?php echo $buildPageUrl($i); ?>" 
                           style="min-width:42px; height:42px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; font-weight:700; font-size:0.9rem; <?php echo $i === $page ? 'background:var(--primary); color:#fff;' : 'background:#fff; border:1px solid var(--line); color:var(--text-dark);'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div style="background:#fff; border-radius:16px; border:1px solid var(--line); padding:4rem 2rem; text-align:center; margin:2rem 0;">
                <div style="width:72px; height:72px; background:var(--primary-light); color:var(--primary); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:2rem; margin-bottom:1.25rem;">
                    <i class="fas fa-search-location"></i>
                </div>
                <h3 style="font-size:1.35rem; font-weight:800; margin-bottom:0.5rem;">Eşleşen İşletme Bulunamadı</h3>
                <p style="color:var(--text-muted); max-width:460px; margin:0 auto 1.5rem;">Arama kriterlerinize uygun işletme bulunamadı. Lütfen filtreleri temizleyip tekrar arayın.</p>
                <a href="<?php echo base_url('marketplace'); ?>" class="btn-nav-business">
                    Tüm İşletmeleri Görüntüle
                </a>
            </div>
        <?php endif; ?>
    </main>

    <!-- FAQ Section (Schema.org FAQPage Compliant) -->
    <section class="faq-section">
        <div class="container">
            <div class="faq-header">
                <h2>Sıkça Sorulan Sorular</h2>
                <p>BooKi Pazar Yeri ile online randevu süreci hakkında merak edilenler</p>
            </div>
            <div class="faq-grid">
                <div class="faq-card">
                    <div class="faq-question">
                        <span>BooKi Pazar Yeri üzerinden randevu almak ücretli mi?</span>
                        <i class="fas fa-chevron-right" style="color:var(--primary); font-size:0.85rem;"></i>
                    </div>
                    <div class="faq-answer">
                        Hayır, BooKi Pazar Yeri üzerinden kuaför, berber, güzellik salonu veya klinik randevusu almak müşteriler için tamamen ücretsizdir. Yalnızca aldığınız hizmetin resmi ücretini ödersiniz.
                    </div>
                </div>
                <div class="faq-card">
                    <div class="faq-question">
                        <span>Pazar yerinden doğrudan randevu nasıl oluşturulur?</span>
                        <i class="fas fa-chevron-right" style="color:var(--primary); font-size:0.85rem;"></i>
                    </div>
                    <div class="faq-answer">
                        İşletme sayfasına giderek almak istediğiniz hizmetin yanındaki "Randevu Al" butonuna tıklayın. Açılan pencerede tercih ettiğiniz personeli, size uygun tarih ve saat dilimini seçip ad-soyad ve telefon bilgilerinizi girerek anında onaylı randevunuzu tamamlayın.
                    </div>
                </div>
                <div class="faq-card">
                    <div class="faq-question">
                        <span>İşletmemi BooKi Pazar Yerine nasıl ekleyebilirim?</span>
                        <i class="fas fa-chevron-right" style="color:var(--primary); font-size:0.85rem;"></i>
                    </div>
                    <div class="faq-answer">
                        BooKi randevu ve salon yönetim yazılımına üye olan tüm işletmeler, panel ayarlarından tek tıkla pazar yeri vitrinine dahil olabilir ve binlerce yeni müşteriye doğrudan erişebilir.
                    </div>
                </div>
                <div class="faq-card">
                    <div class="faq-question">
                        <span>Ödeme süreci ve pazar yeri güvencesi nasıl işler?</span>
                        <i class="fas fa-chevron-right" style="color:var(--primary); font-size:0.85rem;"></i>
                    </div>
                    <div class="faq-answer">
                        Ödemeler 256-bit SSL şifrelemeli güvenli altyapımız ile ister online kartla, ister salonda hizmet esnasında gerçekleştirilir. Tüm randevularda BooKi güvencesi ve SMS onay bilgilendirmesi sunulur.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Professional Storefront Footer (No Developer Links) -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="footer-brand-title" style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem;">
                        <i class="fas fa-calendar-check" style="color:#34d399;"></i>
                        <span>RandevuBurada</span>
                        <span style="font-size:0.7rem; font-weight:700; color:#34d399; background:rgba(16,185,129,0.15); padding:2px 8px; border-radius:12px;">by BooKi</span>
                    </div>
                    <p class="footer-desc" style="font-size:0.92rem; color:#e2e8f0; font-weight:600; margin-bottom:0.4rem;">
                        RandevuBurada, BooKi Hizmet Pazaryeridir.
                    </p>
                    <p class="footer-desc">
                        Randevu ve Salon Yönetim Yazılımı BooKi altyapısıyla güçlendirilmiş; kuaför, berber, güzellik merkezi, klinik ve spa salonlarından 7/24 anında onaylı randevu almanızı sağlayan yeni nesil hizmet pazaryeri.
                    </p>
                    <div style="font-size:0.8rem; color:#64748b; margin-top:0.75rem;">
                        <i class="fas fa-shield-alt text-primary me-1"></i> 256-Bit SSL & KVKK Güvenli Altyapı
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Popüler Hizmetler</h4>
                    <ul>
                        <li><a href="<?php echo base_url('marketplace?category=Kuaf%C3%B6r+%26+Sa%C3%A7'); ?>">Kuaför & Saç Tasarım</a></li>
                        <li><a href="<?php echo base_url('marketplace?category=G%C3%BCzellik+%26+Bak%C4%B1m'); ?>">Cilt Bakımı & Lazer</a></li>
                        <li><a href="<?php echo base_url('marketplace?category=T%C4%B1rnak+%26+Estetik'); ?>">Protez Tırnak & Kalıcı Oje</a></li>
                        <li><a href="<?php echo base_url('marketplace?category=Berber+%26+Erkek'); ?>">Erkek Kuaförü & Berber</a></li>
                        <li><a href="<?php echo base_url('marketplace?category=Masaj+%26+Terapi'); ?>">Spa & Masaj Terapisi</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Şehirler</h4>
                    <ul>
                        <li><a href="<?php echo base_url('marketplace?city=%C4%B0stanbul'); ?>">İstanbul Kuaför Randevu</a></li>
                        <li><a href="<?php echo base_url('marketplace?city=Ankara'); ?>">Ankara Güzellik Salonları</a></li>
                        <li><a href="<?php echo base_url('marketplace?city=%C4%B0zmir'); ?>">İzmir Berber & Kuaför</a></li>
                        <li><a href="<?php echo base_url('marketplace?city=Bursa'); ?>">Bursa Randevulu Hizmetler</a></li>
                        <li><a href="<?php echo base_url('marketplace?city=Antalya'); ?>">Antalya Spa & Masaj</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>BooKi & İşletmeler</h4>
                    <ul>
                        <li><a href="https://booki.kibusiness.co" target="_blank" rel="noopener" style="color:#34d399; font-weight:700;"><i class="fas fa-desktop me-1"></i> BooKi Yazılımı (booki.kibusiness.co)</a></li>
                        <li><a href="https://booki.kibusiness.co/#ozellikler" target="_blank" rel="noopener">Salon Yazılımı Özellikleri</a></li>
                        <li><a href="https://booki.kibusiness.co/#fiyatlandirma" target="_blank" rel="noopener">Paketler & Fiyatlar</a></li>
                        <li><a href="https://booki.kibusiness.co/#pazar-yeri" target="_blank" rel="noopener">Pazaryeri Büyüme Motoru</a></li>
                        <li><a href="<?php echo $login_url; ?>" target="_blank" rel="noopener">İşletme Yönetim Girişi</a></li>
                        <li><a href="https://booki.kibusiness.co/marketplace">booki.kibusiness.co/marketplace</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div>&copy; <?php echo date('Y'); ?> RandevuBurada · <strong>RandevuBurada, BooKi Hizmet Pazaryeridir.</strong> Ki Software (Ki Business Solutions). Tüm hakları saklıdır.</div>
                <div>
                    <a href="<?php echo base_url('privacy'); ?>" style="color:#64748b; margin-right:1.25rem;">Gizlilik / KVKK</a>
                    <a href="<?php echo base_url('terms'); ?>" style="color:#64748b;">Kullanım Koşulları</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <script>
    function showToast(msg, type = 'info') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.style.background = (type === 'error') ? '#ef4444' : '#0f172a';
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3500);
    }

    function applySort(sortVal) {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', sortVal);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }

    document.getElementById('btnGeoLocation')?.addEventListener('click', function() {
        if (!navigator.geolocation) {
            showToast('Tarayıcınız konum servisini desteklemiyor.', 'error');
            return;
        }

        showToast('Konumunuz belirleniyor...', 'info');
        navigator.geolocation.getCurrentPosition(function(pos) {
            document.getElementById('geoLat').value = pos.coords.latitude;
            document.getElementById('geoLng').value = pos.coords.longitude;
            const form = document.getElementById('searchForm');
            let sortInput = form.querySelector('input[name="sort"]');
            if (!sortInput) {
                sortInput = document.createElement('input');
                sortInput.type = 'hidden';
                sortInput.name = 'sort';
                form.appendChild(sortInput);
            }
            sortInput.value = 'distance';
            form.submit();
        }, function(err) {
            showToast('Konum izni alınamadı: ' + err.message, 'error');
        });
    });
    </script>
</body>
</html>
