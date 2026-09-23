<?php defined('BASEPATH') or exit('No direct script access allowed');

extract(html_vars());

$display_name = $display_name ?? ($tenant['company_name'] ?? $tenant['subdomain']);
$canonical_url = $canonical_url ?? base_url('marketplace/business/' . urlencode($tenant['subdomain']));
$meta_description = $meta_description ?? ($display_name . ' için sunulan hizmetleri inceleyin, müşteri yorumlarını okuyun ve online randevu oluşturun.');
$reviews = $reviews ?? [];
$services = $services ?? [];
$rating_dist = $rating_dist ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$total_reviews = (int) ($tenant['review_count'] ?? count($reviews));
$app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';
$portal_url = 'https://' . $app_domain . '/portal';
$login_url = $portal_url;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? ($display_name . ' — Online Randevu & Hizmetler | RandevuBurada by BooKi')); ?></title>
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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46Zmdt9E355iqcxI+BF5w==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Schema.org JSON-LD (LocalBusiness & GEO) -->
    <?php if (!empty($json_ld)): ?>
    <script type="application/ld+json">
        <?php echo json_encode($json_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
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
            --shadow-xl: 0 20px 40px rgba(0, 0, 0, 0.12);
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
        .navbar {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-bottom: 1px solid var(--line);
            padding: 0.75rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #475569;
            font-size: 0.88rem;
            font-weight: 600;
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.15s ease;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .back-link:hover {
            color: #0f766e;
            background-color: #f0fdfa;
        }
        .nav-brand {
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 1.15rem;
            color: var(--text-dark);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
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

        /* Profile Hero Card */
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
            font-family: var(--font-display);
            font-size: 2.15rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.65rem;
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
            gap: 1.25rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        .meta-tag { display: inline-flex; align-items: center; gap: 0.45rem; }
        .profile-description {
            font-size: 0.95rem;
            color: #475569;
            line-height: 1.6;
            margin-bottom: 1rem;
            max-width: 720px;
        }

        .profile-action-box {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            align-items: flex-end;
        }
        .btn-book-hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #ffffff;
            font-family: var(--font-display);
            font-size: 1.05rem;
            font-weight: 700;
            padding: 0.85rem 2.25rem;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-book-hero:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 22px rgba(16, 185, 129, 0.4);
        }

        /* Layout Grid */
        .layout-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        /* Section Card */
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
            font-family: var(--font-display);
            font-size: 1.35rem;
            font-weight: 700;
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
            padding: 1.1rem 1.25rem;
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
        .service-info h4 { font-size: 1.02rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.25rem; }
        .service-info p { font-size: 0.85rem; color: var(--text-muted); }
        .service-duration-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.35rem;
        }
        .service-action { display: flex; align-items: center; gap: 1.25rem; }
        .service-price { font-size: 1.2rem; font-weight: 800; color: var(--text-dark); white-space: nowrap; }
        .btn-select-service {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            font-family: var(--font-display);
            font-size: 0.88rem;
            font-weight: 700;
            padding: 0.5rem 1.15rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .btn-select-service:hover {
            background-color: var(--primary);
            color: #ffffff;
        }

        /* Sidebar Info Widget */
        .sidebar-widget {
            background-color: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--line);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }
        .sidebar-widget h3 {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-dark);
        }
        .hours-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            padding: 0.4rem 0;
            border-bottom: 1px dashed var(--line);
        }

        /* Reviews List */
        .review-card {
            border-bottom: 1px solid var(--line);
            padding: 1rem 0;
        }
        .review-card:last-child { border-bottom: none; }
        .review-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.4rem;
        }
        .review-author { font-weight: 700; font-size: 0.92rem; }
        .review-date { font-size: 0.8rem; color: var(--text-muted); }
        .review-comment { font-size: 0.9rem; color: #334155; line-height: 1.55; }

        /* MODAL / DRAWER FOR IN-MARKETPLACE BOOKING */
        .booking-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            z-index: 99999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .booking-modal-backdrop.active { display: flex; }
        .booking-modal {
            background-color: var(--surface);
            border-radius: 20px;
            width: 100%;
            max-width: 580px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
            display: flex;
            flex-direction: column;
            animation: modalPop 0.25s ease-out;
        }
        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.96) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #fafbfc;
            border-radius: 20px 20px 0 0;
        }
        .modal-title { font-family: var(--font-display); font-size: 1.2rem; font-weight: 800; color: var(--text-dark); }
        .btn-modal-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.25rem;
        }
        .modal-body { padding: 1.5rem; }

        /* Step Indicators */
        .booking-steps {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.75rem;
            position: relative;
        }
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            z-index: 2;
        }
        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--surface-alt);
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--line);
        }
        .step-item.active .step-circle {
            background-color: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }
        .step-item.completed .step-circle {
            background-color: #d1fae5;
            color: var(--primary-dark);
            border-color: var(--primary);
        }
        .step-label { font-size: 0.72rem; font-weight: 600; color: var(--text-muted); }
        .step-item.active .step-label { color: var(--primary-dark); font-weight: 700; }

        /* Form Inputs */
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-size: 0.88rem; font-weight: 600; color: var(--text-dark); margin-bottom: 0.4rem; }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--line);
            border-radius: 10px;
            font-size: 0.92rem;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
        }

        /* Slot Chips */
        .slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 8px;
            max-height: 180px;
            overflow-y: auto;
            margin-top: 0.5rem;
            padding: 4px;
        }
        .slot-chip {
            background-color: #f8fafc;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 8px 4px;
            text-align: center;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.15s;
        }
        .slot-chip:hover { border-color: var(--primary); color: var(--primary-dark); }
        .slot-chip.selected {
            background-color: var(--primary);
            border-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
        }

        /* Payment Radio Options */
        .payment-option-card {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 0.9rem 1.15rem;
            display: flex;
            align-items: center;
            gap: 0.85rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            background-color: #fafbfc;
            transition: all 0.15s;
        }
        .payment-option-card:hover { border-color: #cbd5e1; }
        .payment-option-card.active {
            border-color: var(--primary);
            background-color: var(--primary-light);
        }

        .btn-modal-action {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 700;
            width: 100%;
            padding: 0.9rem;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            margin-top: 1.25rem;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.25);
            transition: all 0.15s;
        }
        .btn-modal-action:hover {
            box-shadow: 0 6px 18px rgba(16, 185, 129, 0.35);
        }

        /* Success Screen */
        .success-box {
            text-align: center;
            padding: 2rem 1rem;
        }
        .success-icon {
            width: 72px;
            height: 72px;
            background-color: var(--primary-light);
            color: var(--primary);
            font-size: 2.25rem;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
        }
        .ref-badge {
            background-color: #f1f5f9;
            padding: 0.4rem 1rem;
            border-radius: 8px;
            font-family: monospace;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: var(--text-dark);
            display: inline-block;
            margin: 0.75rem 0 1.25rem;
        }

        /* Footer */
        .footer {
            background-color: #0b1320;
            color: #94a3b8;
            padding: 3.5rem 0 2rem;
            border-top: 1px solid #1e293b;
        }
        .footer-content { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 2rem; margin-bottom: 2.5rem; }
        .footer-bottom { border-top: 1px solid #1e293b; padding-top: 1.5rem; text-align: center; font-size: 0.82rem; }

        @media (max-width: 768px) {
            .layout-grid { grid-template-columns: 1fr; }
            .profile-header-body { flex-direction: column; }
            .profile-action-box { width: 100%; align-items: stretch; }
            .btn-book-hero { width: 100%; justify-content: center; }
        }

        /* Tenant Login Modal */
        .tenant-login-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .tenant-login-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
        }
        .tenant-login-dialog {
            position: relative;
            background: #ffffff;
            border-radius: 20px;
            width: 100%;
            max-width: 440px;
            padding: 2.2rem 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            z-index: 1;
            text-align: left;
            animation: modalFadeIn 0.2s ease-out;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(12px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .tenant-login-close {
            position: absolute;
            top: 1rem;
            right: 1.2rem;
            background: none;
            border: none;
            font-size: 1.7rem;
            color: #94a3b8;
            cursor: pointer;
            line-height: 1;
        }
        .tenant-login-close:hover { color: #0f172a; }
        .tenant-modal-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            font-weight: 700;
            background: #ccfbf1;
            color: #0f766e;
            padding: 0.3rem 0.75rem;
            border-radius: 999px;
            margin-bottom: 0.8rem;
        }
        .tenant-modal-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.4rem;
        }
        .tenant-modal-desc {
            font-size: 0.88rem;
            color: #64748b;
            margin-bottom: 1.4rem;
            line-height: 1.45;
        }
        .tenant-input-group label {
            display: block;
            font-size: 0.83rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.4rem;
        }
        .tenant-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .tenant-input-wrap i {
            position: absolute;
            left: 0.9rem;
            color: #94a3b8;
            font-size: 0.95rem;
        }
        .tenant-input-wrap input {
            width: 100%;
            padding: 0.7rem 0.9rem 0.7rem 2.4rem;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .tenant-input-wrap input:focus {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15);
        }
        .tenant-modal-preview {
            margin-top: 0.5rem;
            font-size: 0.78rem;
            color: #64748b;
            background: #f8fafc;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            word-break: break-all;
        }
        .tenant-modal-preview strong {
            color: #0f766e;
        }
        .tenant-modal-btn {
            width: 100%;
            margin-top: 1.1rem;
            padding: 0.8rem;
            background: #0f766e;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: background 0.15s, transform 0.15s;
        }
        .tenant-modal-btn:hover {
            background: #115e59;
            transform: translateY(-1px);
        }
        .tenant-modal-footer {
            margin-top: 1.2rem;
            text-align: center;
            font-size: 0.82rem;
        }
        .tenant-modal-portal-link {
            color: #0f766e;
            font-weight: 600;
            text-decoration: none;
        }
        .tenant-modal-portal-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <!-- Top Navigation Header -->
    <header class="navbar">
        <div class="container navbar-content">
            <a href="<?php echo function_exists('randevuburada_url') ? randevuburada_url() : 'https://randevuburada.kibusiness.co'; ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> RandevuBurada
            </a>
            <a href="<?php echo function_exists('randevuburada_url') ? randevuburada_url() : 'https://randevuburada.kibusiness.co'; ?>" class="nav-brand">
                <i class="fas fa-calendar-check" style="color:var(--primary)"></i>
                <span>RandevuBurada</span>
                <span class="nav-brand-badge">by BooKi</span>
            </a>
            <div style="display:flex; align-items:center; gap:0.75rem; white-space:nowrap; flex-shrink:0;">
                <a href="https://booki.kibusiness.co" target="_blank" rel="noopener" class="partner-software-link">
                    <i class="fas fa-desktop me-1"></i> BooKi Yazılımı ↗
                </a>
                <a href="<?php echo $portal_url; ?>" onclick="openTenantLoginModal(event)" style="font-size:0.85rem; font-weight:600; color:var(--text-muted); text-decoration:none; padding:0.4rem 0.75rem;">
                    <i class="fas fa-user-circle me-1"></i> İşletme Girişi
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Profile Header Hero -->
        <div class="profile-hero">
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
                            <span class="meta-tag"><i class="fas fa-tag" style="color:var(--primary)"></i> <?php echo htmlspecialchars($tenant['category']); ?></span>
                        <?php endif; ?>

                        <span class="meta-tag">
                            <i class="fas fa-star" style="color:var(--star)"></i>
                            <strong><?php echo ((int)$tenant['review_count'] > 0) ? round((float)$tenant['avg_rating'], 1) : '5.0'; ?></strong>
                            (<?php echo (int) $tenant['review_count']; ?> değerlendirme)
                        </span>

                        <span class="meta-tag">
                            <i class="fas fa-money-bill-wave text-muted"></i> Fiyat: <?php echo htmlspecialchars($tenant['price_range'] ?? '₺₺'); ?>
                        </span>

                        <?php if (!empty($tenant['city']) || !empty($tenant['district'])): ?>
                            <span class="meta-tag">
                                <i class="fas fa-map-marker-alt text-muted"></i>
                                <?php echo htmlspecialchars(implode(', ', array_filter([$tenant['district'] ?? null, $tenant['city'] ?? null]))); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($tenant['short_description'])): ?>
                        <p class="profile-description"><?php echo htmlspecialchars($tenant['short_description']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="profile-action-box">
                    <button type="button" class="btn-book-hero" onclick="startDirectBooking()">
                        <i class="fas fa-calendar-plus"></i> Hemen Randevu Al
                    </button>
                    <span style="font-size:0.8rem; color:#64748b;">
                        <i class="fas fa-shield-alt text-primary"></i> Pazar Yeri Güvenceli Rezervasyon
                    </span>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="layout-grid">
            <!-- Left Column: Services & Reviews -->
            <div>
                <!-- Services Section -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-list-ul" style="color:var(--primary)"></i> Hizmetler & Fiyat Listesi
                        </h2>
                        <span style="font-size:0.85rem; color:var(--text-muted);">
                            <?php echo count($services); ?> hizmet mevcut
                        </span>
                    </div>

                    <?php if (!empty($services)): ?>
                        <div class="services-list">
                            <?php foreach ($services as $srv): 
                                $priceFormatted = number_format((float)($srv['price'] ?? 0), 2, ',', '.') . ' ' . ($srv['currency'] ?? '₺');
                            ?>
                                <div class="service-row">
                                    <div class="service-info">
                                        <h4><?php echo htmlspecialchars($srv['name']); ?></h4>
                                        <?php if (!empty($srv['description'])): ?>
                                            <p><?php echo htmlspecialchars($srv['description']); ?></p>
                                        <?php endif; ?>
                                        <span class="service-duration-badge">
                                            <i class="far fa-clock"></i> <?php echo (int)$srv['duration']; ?> dakika
                                        </span>
                                    </div>
                                    <div class="service-action">
                                        <div class="service-price"><?php echo $priceFormatted; ?></div>
                                        <button type="button" class="btn-select-service" 
                                                onclick="selectServiceAndBook(<?php echo (int)$srv['id']; ?>, '<?php echo addslashes(htmlspecialchars($srv['name'])); ?>', <?php echo (int)$srv['duration']; ?>, <?php echo (float)$srv['price']; ?>, '<?php echo htmlspecialchars($srv['currency'] ?? 'TRY'); ?>')">
                                            Randevu Al
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color:var(--text-muted); text-align:center; padding:2rem 0;">Bu işletme için listelenmiş aktif hizmet bulunmamaktadır.</p>
                    <?php endif; ?>
                </div>

                <!-- Reviews Section -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-comment-dots" style="color:var(--star)"></i> Müşteri Değerlendirmeleri
                        </h2>
                        <span style="font-size:0.85rem; color:var(--text-muted);"><?php echo count($reviews); ?> Yorum</span>
                    </div>

                    <?php if (!empty($reviews)): ?>
                        <div>
                            <?php foreach ($reviews as $rev): ?>
                                <div class="review-card">
                                    <div class="review-meta">
                                        <span class="review-author"><?php echo htmlspecialchars($rev['customer_name']); ?></span>
                                        <span class="review-date"><?php echo date('d.m.Y', strtotime($rev['created_at'])); ?></span>
                                    </div>
                                    <div style="color:var(--star); font-size:0.85rem; margin-bottom:0.35rem;">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo ($i <= (int)$rev['rating']) ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <?php if (!empty($rev['comment'])): ?>
                                        <p class="review-comment"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color:var(--text-muted); text-align:center; padding:1.5rem 0;">Henüz değerlendirme yapılmamış. İlk randevunuzu alarak deneyiminizi paylaşabilirsiniz.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Sidebar Business Info -->
            <div>
                <div class="sidebar-widget">
                    <h3>İşletme Bilgileri</h3>
                    <div style="font-size:0.9rem; margin-bottom:1rem;">
                        <div style="margin-bottom:0.6rem;">
                            <strong style="display:block; font-size:0.78rem; color:var(--text-muted);">ADRES</strong>
                            <span><?php echo htmlspecialchars($tenant['address'] ?? ($tenant['district'] . ', ' . $tenant['city'])); ?></span>
                        </div>
                        <?php if (!empty($tenant['phone_number'])): ?>
                            <div style="margin-bottom:0.6rem;">
                                <strong style="display:block; font-size:0.78rem; color:var(--text-muted);">TELEFON</strong>
                                <span><?php echo htmlspecialchars($tenant['phone_number']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($tenant['latitude']) && !empty($tenant['longitude'])): ?>
                        <a href="https://maps.google.com/?q=<?php echo urlencode($tenant['latitude'] . ',' . $tenant['longitude']); ?>" target="_blank" 
                           style="display:inline-flex; align-items:center; gap:0.4rem; font-size:0.85rem; font-weight:700; color:var(--primary-dark);">
                            <i class="fas fa-external-link-alt"></i> Haritada Göster
                        </a>
                    <?php endif; ?>
                </div>

                <div class="sidebar-widget">
                    <h3>Çalışma Saatleri</h3>
                    <div class="hours-row"><span>Pazartesi</span><strong>09:00 - 19:00</strong></div>
                    <div class="hours-row"><span>Salı</span><strong>09:00 - 19:00</strong></div>
                    <div class="hours-row"><span>Çarşamba</span><strong>09:00 - 19:00</strong></div>
                    <div class="hours-row"><span>Perşembe</span><strong>09:00 - 19:00</strong></div>
                    <div class="hours-row"><span>Cuma</span><strong>09:00 - 19:00</strong></div>
                    <div class="hours-row"><span>Cumartesi</span><strong>09:00 - 19:00</strong></div>
                    <div class="hours-row" style="border-bottom:none;"><span>Pazar</span><strong style="color:#ef4444;">Kapalı</strong></div>
                </div>

                <div class="sidebar-widget" style="background:#ecfdf5; border-color:#a7f3d0;">
                    <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                        <i class="fas fa-shield-alt" style="font-size:1.5rem; color:var(--primary-dark); margin-top:0.2rem;"></i>
                        <div>
                            <h4 style="font-size:0.95rem; font-weight:800; color:#065f46; margin-bottom:0.2rem;">BooKi Pazar Yeri Güvencesi</h4>
                            <p style="font-size:0.8rem; color:#047857; line-height:1.5;">
                                Randevunuz doğrudan işletme takvimine işlenir, anında onaylanır ve rezervasyon detayları telefonunuza iletilir.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- IN-MARKETPLACE DIRECT BOOKING MODAL -->
    <div class="booking-modal-backdrop" id="bookingModalBackdrop">
        <div class="booking-modal" id="bookingModal">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle">Online Randevu Oluştur</div>
                <button type="button" class="btn-modal-close" onclick="closeBookingModal()">&times;</button>
            </div>

            <div class="modal-body">
                <!-- Step Indicators -->
                <div class="booking-steps">
                    <div class="step-item active" id="stepIndicator1">
                        <div class="step-circle">1</div>
                        <span class="step-label">Tarih & Saat</span>
                    </div>
                    <div class="step-item" id="stepIndicator2">
                        <div class="step-circle">2</div>
                        <span class="step-label">Bilgileriniz</span>
                    </div>
                    <div class="step-item" id="stepIndicator3">
                        <div class="step-circle">3</div>
                        <span class="step-label">Ödeme & Onay</span>
                    </div>
                </div>

                <!-- STEP 1: Service, Provider, Date & Slot Selection -->
                <div id="bookingStep1">
                    <div style="background:#f8fafc; border:1px solid var(--line); border-radius:12px; padding:12px; margin-bottom:1.25rem; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600;">SEÇİLEN HİZMET</div>
                            <div style="font-weight:700; font-size:1rem;" id="selectedServiceName">-</div>
                            <div style="font-size:0.8rem; color:var(--text-muted);" id="selectedServiceDuration">-</div>
                        </div>
                        <div style="font-size:1.2rem; font-weight:800; color:var(--primary-dark);" id="selectedServicePrice">-</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Uzman / Personel Tercihi</label>
                        <select class="form-control" id="bookingProviderSelect">
                            <option value="any">Fark Etmez / İlk Müsait Uzman</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Randevu Tarihi</label>
                        <input type="date" class="form-control" id="bookingDateInput" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" onchange="loadAvailableSlots()">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Müsait Saat Dilimleri</label>
                        <div id="slotsLoading" style="display:none; color:var(--text-muted); font-size:0.85rem; padding:1rem 0; text-align:center;">
                            <i class="fas fa-spinner fa-spin me-1"></i> Müsait saatler kontrol ediliyor...
                        </div>
                        <div class="slots-grid" id="slotsContainer">
                            <!-- Dynamic Slot Chips -->
                        </div>
                        <div id="slotsEmptyMsg" style="display:none; color:#ef4444; font-size:0.85rem; margin-top:0.5rem;">
                            Seçilen tarihte müsait saat dilimi bulunamadı. Lütfen başka bir gün seçin.
                        </div>
                    </div>

                    <button type="button" class="btn-modal-action" id="btnToStep2" onclick="goToStep2()" disabled>
                        İlerle: Müşteri Bilgileri <i class="fas fa-arrow-right me-1"></i>
                    </button>
                </div>

                <!-- STEP 2: Customer Contact Details -->
                <div id="bookingStep2" style="display:none;">
                    <div class="form-group">
                        <label class="form-label">Adınız *</label>
                        <input type="text" class="form-control" id="custFirstName" placeholder="Örn: Ayşe">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Soyadınız *</label>
                        <input type="text" class="form-control" id="custLastName" placeholder="Örn: Yılmaz">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cep Telefonu (SMS Onayı İçin) *</label>
                        <input type="tel" class="form-control" id="custPhone" placeholder="05XX XXX XX XX">
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-posta Adresi (İsteğe Bağlı)</label>
                        <input type="email" class="form-control" id="custEmail" placeholder="ayse@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Randevu Notu / Özel İstek (İsteğe Bağlı)</label>
                        <textarea class="form-control" id="custNotes" rows="2" placeholder="Varsa işletmeye iletmek istediğiniz özel detaylar..."></textarea>
                    </div>

                    <div style="display:flex; gap:10px;">
                        <button type="button" class="form-control" style="width:35%;" onclick="goToStep1()">Geri</button>
                        <button type="button" class="btn-modal-action" style="margin-top:0; width:65%;" onclick="goToStep3()">
                            İlerle: Ödeme Tercihi <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 3: Payment Choice & Final Confirmation -->
                <div id="bookingStep3" style="display:none;">
                    <div style="background:#f8fafc; border:1px solid var(--line); border-radius:12px; padding:1.25rem; margin-bottom:1.5rem;">
                        <h4 style="font-size:0.92rem; font-weight:800; margin-bottom:0.75rem; color:var(--text-dark);">REZERVASYON ÖZETİ</h4>
                        <div style="display:flex; justify-content:space-between; font-size:0.88rem; margin-bottom:0.35rem;">
                            <span style="color:var(--text-muted);">İşletme:</span>
                            <strong><?php echo htmlspecialchars($display_name); ?></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:0.88rem; margin-bottom:0.35rem;">
                            <span style="color:var(--text-muted);">Hizmet:</span>
                            <strong id="summaryServiceName">-</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:0.88rem; margin-bottom:0.35rem;">
                            <span style="color:var(--text-muted);">Tarih & Saat:</span>
                            <strong id="summaryDateTime">-</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:1.1rem; font-weight:800; margin-top:0.75rem; padding-top:0.75rem; border-top:1px dashed var(--line);">
                            <span>Ödenecek Tutar:</span>
                            <span style="color:var(--primary-dark);" id="summaryPrice">-</span>
                        </div>
                    </div>

                    <label class="form-label" style="margin-bottom:0.6rem;">Ödeme Yöntemi Tercihi</label>
                    <div class="payment-option-card active" id="payOptOnline" onclick="selectPaymentMethod('online')">
                        <input type="radio" name="payMethod" value="online" checked style="accent-color:var(--primary);">
                        <div>
                            <div style="font-weight:700; font-size:0.92rem;">🔒 Güvenli Online Ödeme (Platform Tahsilatı)</div>
                            <div style="font-size:0.78rem; color:var(--text-muted);">Tutar BooKi havuzunda güvende tutulur, randevu tamamlandığında işletmeye aktarılır.</div>
                        </div>
                    </div>

                    <div class="payment-option-card" id="payOptSalon" onclick="selectPaymentMethod('salon')">
                        <input type="radio" name="payMethod" value="salon" style="accent-color:var(--primary);">
                        <div>
                            <div style="font-weight:700; font-size:0.92rem;">🏢 Salonda / Kapıda Ödeme</div>
                            <div style="font-size:0.78rem; color:var(--text-muted);">Hizmet sonrasında doğrudan salonda nakit veya kartla ödeyin.</div>
                        </div>
                    </div>

                    <div id="bookingSubmitError" style="display:none; color:#ef4444; font-size:0.85rem; margin:0.75rem 0;"></div>

                    <div style="display:flex; gap:10px; margin-top:1.25rem;">
                        <button type="button" class="form-control" style="width:35%;" onclick="goToStep2()">Geri</button>
                        <button type="button" class="btn-modal-action" id="btnSubmitBooking" style="margin-top:0; width:65%;" onclick="submitBooking()">
                            Randevuyu Tamamla & Onayla
                        </button>
                    </div>
                </div>

                <!-- STEP 4: Success Confirmation Screen -->
                <div id="bookingStepSuccess" style="display:none;">
                    <div class="success-box">
                        <div class="success-icon"><i class="fas fa-check"></i></div>
                        <h3 style="font-family:var(--font-display); font-size:1.4rem; font-weight:800; margin-bottom:0.4rem; color:var(--text-dark);">
                            Rezervasyonunuz Başarıyla Onaylandı!
                        </h3>
                        <p style="color:var(--text-muted); font-size:0.9rem;">
                            Randevu detaylarınız işletmeye iletildi ve onaylandı.
                        </p>
                        
                        <div>
                            <span class="ref-badge" id="successRefCode">BK-000000</span>
                        </div>

                        <div style="background:#f8fafc; border:1px solid var(--line); border-radius:12px; padding:1rem; text-align:left; font-size:0.88rem; margin-bottom:1.5rem;">
                            <div style="margin-bottom:0.4rem;"><strong>İşletme:</strong> <?php echo htmlspecialchars($display_name); ?></div>
                            <div style="margin-bottom:0.4rem;"><strong>Hizmet:</strong> <span id="successService">-</span></div>
                            <div style="margin-bottom:0.4rem;"><strong>Tarih & Saat:</strong> <span id="successDateTime">-</span></div>
                            <div><strong>Adres:</strong> <?php echo htmlspecialchars($tenant['address'] ?? ($tenant['district'] . ', ' . $tenant['city'])); ?></div>
                        </div>

                        <button type="button" class="btn-modal-action" onclick="closeBookingModal(); window.location.reload();">
                            Tamamlandı
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div style="max-width:380px;">
                    <div style="font-family:var(--font-display); font-size:1.35rem; font-weight:800; color:#fff; margin-bottom:0.6rem;">
                        <i class="fas fa-calendar-check" style="color:var(--primary);"></i> RandevuBurada
                        <span style="font-size:0.7rem; font-weight:700; color:#34d399; background:rgba(16,185,129,0.15); padding:2px 8px; border-radius:12px; margin-left:6px;">by BooKi</span>
                    </div>
                    <p style="font-size:0.92rem; line-height:1.6; color:#e2e8f0; font-weight:600; margin-bottom:0.4rem;">
                        RandevuBurada, BooKi Hizmet Pazaryeridir.
                    </p>
                    <p style="font-size:0.86rem; line-height:1.6; color:#94a3b8;">
                        Seçkin salonlardan ve uzmanlardan 7/24 anında onaylı randevu almanızı sağlayan yeni nesil hizmet pazaryeri.
                    </p>
                </div>
                <div>
                    <h4 style="color:#fff; font-size:0.95rem; margin-bottom:1rem;">BooKi & İşletmeler</h4>
                    <ul style="list-style:none; font-size:0.88rem;">
                        <li style="margin-bottom:0.5rem;"><a href="https://booki.kibusiness.co" target="_blank" rel="noopener" style="color:#34d399; font-weight:700;"><i class="fas fa-desktop me-1"></i> BooKi Yazılımı (booki.kibusiness.co)</a></li>
                        <li style="margin-bottom:0.5rem;"><a href="https://booki.kibusiness.co/#ozellikler" target="_blank" rel="noopener">Salon Yazılımı Özellikleri</a></li>
                        <li style="margin-bottom:0.5rem;"><a href="https://booki.kibusiness.co/#fiyatlandirma" target="_blank" rel="noopener">Fiyatlar & Paketler</a></li>
                        <li style="margin-bottom:0.5rem;"><a href="<?php echo $portal_url; ?>" onclick="openTenantLoginModal(event)">İşletme Girişi</a></li>
                        <li><a href="https://booki.kibusiness.co/marketplace">booki.kibusiness.co/marketplace</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="color:#fff; font-size:0.95rem; margin-bottom:1rem;">Yasal</h4>
                    <ul style="list-style:none; font-size:0.88rem;">
                        <li style="margin-bottom:0.5rem;"><a href="<?php echo base_url('privacy'); ?>">Gizlilik & KVKK</a></li>
                        <li><a href="<?php echo base_url('terms'); ?>">Kullanım Koşulları</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; <?php echo date('Y'); ?> RandevuBurada · <strong>RandevuBurada, BooKi Hizmet Pazaryeridir.</strong> Ki Software (Ki Business Solutions). Tüm hakları saklıdır.
            </div>
        </div>
    </footer>

    <script>
    const currentSubdomain = '<?php echo addslashes($tenant['subdomain']); ?>';
    let currentService = null;
    let selectedSlotTime = null;
    let selectedPayment = 'online';

    function startDirectBooking() {
        <?php if (!empty($services)): ?>
            const firstSrv = <?php echo json_encode($services[0]); ?>;
            selectServiceAndBook(firstSrv.id, firstSrv.name, firstSrv.duration, firstSrv.price, firstSrv.currency || 'TRY');
        <?php else: ?>
            alert('İşletmeye ait aktif hizmet bulunamadı.');
        <?php endif; ?>
    }

    function selectServiceAndBook(id, name, duration, price, currency) {
        currentService = { id, name, duration, price, currency };
        selectedSlotTime = null;

        document.getElementById('selectedServiceName').textContent = name;
        document.getElementById('selectedServiceDuration').textContent = duration + ' dakika';
        document.getElementById('selectedServicePrice').textContent = Number(price).toLocaleString('tr-TR', { minimumFractionDigits: 2 }) + ' ' + (currency || '₺');

        document.getElementById('bookingModalBackdrop').classList.add('active');
        goToStep1();
        loadAvailableSlots();
    }

    function closeBookingModal() {
        document.getElementById('bookingModalBackdrop').classList.remove('active');
    }

    function goToStep1() {
        document.getElementById('bookingStep1').style.display = 'block';
        document.getElementById('bookingStep2').style.display = 'none';
        document.getElementById('bookingStep3').style.display = 'none';
        document.getElementById('bookingStepSuccess').style.display = 'none';

        document.getElementById('stepIndicator1').className = 'step-item active';
        document.getElementById('stepIndicator2').className = 'step-item';
        document.getElementById('stepIndicator3').className = 'step-item';
    }

    function goToStep2() {
        if (!selectedSlotTime) {
            alert('Lütfen randevu için bir saat dilimi seçin.');
            return;
        }

        document.getElementById('bookingStep1').style.display = 'none';
        document.getElementById('bookingStep2').style.display = 'block';
        document.getElementById('bookingStep3').style.display = 'none';

        document.getElementById('stepIndicator1').className = 'step-item completed';
        document.getElementById('stepIndicator2').className = 'step-item active';
        document.getElementById('stepIndicator3').className = 'step-item';
    }

    function goToStep3() {
        const fName = document.getElementById('custFirstName').value.trim();
        const lName = document.getElementById('custLastName').value.trim();
        const phone = document.getElementById('custPhone').value.trim();

        if (!fName || !lName || !phone) {
            alert('Lütfen ad, soyad ve telefon numaranızı eksiksiz girin.');
            return;
        }

        document.getElementById('summaryServiceName').textContent = currentService.name;
        document.getElementById('summaryDateTime').textContent = document.getElementById('bookingDateInput').value + ' ' + selectedSlotTime;
        document.getElementById('summaryPrice').textContent = Number(currentService.price).toLocaleString('tr-TR', { minimumFractionDigits: 2 }) + ' ' + (currentService.currency || '₺');

        document.getElementById('bookingStep1').style.display = 'none';
        document.getElementById('bookingStep2').style.display = 'none';
        document.getElementById('bookingStep3').style.display = 'block';

        document.getElementById('stepIndicator1').className = 'step-item completed';
        document.getElementById('stepIndicator2').className = 'step-item completed';
        document.getElementById('stepIndicator3').className = 'step-item active';
    }

    function selectPaymentMethod(m) {
        selectedPayment = m;
        document.getElementById('payOptOnline').classList.toggle('active', m === 'online');
        document.getElementById('payOptSalon').classList.toggle('active', m === 'salon');
        document.querySelector('input[name="payMethod"][value="' + m + '"]').checked = true;
    }

    function loadAvailableSlots() {
        if (!currentService) return;

        const dateVal = document.getElementById('bookingDateInput').value;
        const slotsContainer = document.getElementById('slotsContainer');
        const loading = document.getElementById('slotsLoading');
        const emptyMsg = document.getElementById('slotsEmptyMsg');
        const btnNext = document.getElementById('btnToStep2');

        slotsContainer.innerHTML = '';
        loading.style.display = 'block';
        emptyMsg.style.display = 'none';
        btnNext.disabled = true;
        selectedSlotTime = null;

        fetch('<?php echo base_url('marketplace/get_slots/'); ?>' + currentSubdomain + '?service_id=' + currentService.id + '&date=' + dateVal)
            .then(r => r.json())
            .then(data => {
                loading.style.display = 'none';
                if (!data.success || !data.slots || data.slots.length === 0) {
                    emptyMsg.style.display = 'block';
                    return;
                }

                // Populate Providers if present
                const pSelect = document.getElementById('bookingProviderSelect');
                if (data.providers && data.providers.length > 0 && pSelect.options.length <= 1) {
                    data.providers.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.id;
                        opt.textContent = p.first_name + ' ' + p.last_name;
                        pSelect.appendChild(opt);
                    });
                }

                data.slots.forEach(slot => {
                    const chip = document.createElement('div');
                    chip.className = 'slot-chip';
                    chip.textContent = slot;
                    chip.onclick = function() {
                        document.querySelectorAll('.slot-chip').forEach(c => c.classList.remove('selected'));
                        chip.classList.add('selected');
                        selectedSlotTime = slot;
                        btnNext.disabled = false;
                    };
                    slotsContainer.appendChild(chip);
                });
            })
            .catch(err => {
                loading.style.display = 'none';
                emptyMsg.style.display = 'block';
                emptyMsg.textContent = 'Müsaitlik kontrol edilirken hata oluştu: ' + err.message;
            });
    }

    function submitBooking() {
        const btn = document.getElementById('btnSubmitBooking');
        const errDiv = document.getElementById('bookingSubmitError');
        errDiv.style.display = 'none';
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Rezervasyon Tamamlanıyor...';

        const payload = new URLSearchParams({
            service_id: currentService.id,
            date: document.getElementById('bookingDateInput').value,
            time: selectedSlotTime,
            provider_id: document.getElementById('bookingProviderSelect').value,
            first_name: document.getElementById('custFirstName').value.trim(),
            last_name: document.getElementById('custLastName').value.trim(),
            phone_number: document.getElementById('custPhone').value.trim(),
            email: document.getElementById('custEmail').value.trim(),
            notes: document.getElementById('custNotes').value.trim(),
            payment_method: selectedPayment,
        });

        fetch('<?php echo base_url('marketplace/create_booking/'); ?>' + currentSubdomain, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: payload.toString()
        })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false;
                btn.textContent = 'Randevuyu Tamamla & Onayla';

                if (!data.success) {
                    errDiv.style.display = 'block';
                    errDiv.textContent = data.message || 'Rezervasyon oluşturulamadı. Lütfen tekrar deneyin.';
                    return;
                }

                // Show Success Screen
                document.getElementById('bookingStep3').style.display = 'none';
                document.getElementById('bookingStepSuccess').style.display = 'block';
                document.getElementById('modalTitle').textContent = 'Randevu Onayı';
                document.getElementById('successRefCode').textContent = data.booking_ref || 'BK-SUCCESS';
                document.getElementById('successService').textContent = data.service_name;
                document.getElementById('successDateTime').textContent = data.date_formatted + ' ' + data.time;
            })
            .catch(err => {
                btn.disabled = false;
                btn.textContent = 'Randevuyu Tamamla & Onayla';
                errDiv.style.display = 'block';
                errDiv.textContent = 'Bağlantı hatası: ' + err.message;
            });
    }

    const tenantAppDomain = '<?php echo $app_domain; ?>';

    function sanitizeSlug(raw) {
        let val = (raw || '').trim().toLowerCase();
        val = val.replace(/^https?:\/\//i, '');
        val = val.replace(/\/.*$/, '');
        val = val.replace(/:\d+$/, '');
        const pattern = tenantAppDomain.replace('.', '\\.');
        val = val.replace(new RegExp('[-.]' + pattern + '$', 'i'), '');
        val = val.replace(/^@/, '');
        return val;
    }

    function openTenantLoginModal(e) {
        if (e) e.preventDefault();
        const modal = document.getElementById('tenantLoginModal');
        if (modal) {
            modal.style.display = 'flex';
            const input = document.getElementById('tenantModalInput');
            if (input) {
                input.focus();
                updateTenantPreview();
            }
        }
    }

    function closeTenantLoginModal() {
        const modal = document.getElementById('tenantLoginModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function updateTenantPreview() {
        const input = document.getElementById('tenantModalInput');
        const preview = document.getElementById('tenantModalPreviewUrl');
        if (!input || !preview) return;
        const slug = sanitizeSlug(input.value);
        if (slug && !slug.includes('@') && !slug.includes('.')) {
            preview.textContent = 'https://' + slug + '-' + tenantAppDomain + '/login';
        } else if (input.value.trim()) {
            preview.textContent = 'İşletme aranıyor...';
        } else {
            preview.textContent = 'https://...-' + tenantAppDomain + '/login';
        }
    }

    document.getElementById('tenantModalInput')?.addEventListener('input', updateTenantPreview);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeTenantLoginModal();
    });

    function handleTenantModalSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('tenantModalInput');
        const btn = document.getElementById('tenantModalBtn');
        const rawVal = (input ? input.value : '').trim();
        if (!rawVal) return;

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span>Yönlendiriliyor...</span> <i class="fas fa-spinner fa-spin"></i>';
        }

        const slug = sanitizeSlug(rawVal);
        if (slug && /^[a-z0-9-]+$/.test(slug)) {
            window.location.href = 'https://' + slug + '-' + tenantAppDomain + '/login';
        } else {
            window.location.href = '<?php echo $portal_url; ?>';
        }
    }
    </script>

    <!-- Tenant Login Modal -->
    <div id="tenantLoginModal" class="tenant-login-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="tenantModalTitle">
        <div class="tenant-login-backdrop" onclick="closeTenantLoginModal()"></div>
        <div class="tenant-login-dialog">
            <button type="button" class="tenant-login-close" onclick="closeTenantLoginModal()" aria-label="Kapat">&times;</button>
            <div class="tenant-modal-badge"><i class="fas fa-store"></i> BooKi İşletme Girişi</div>
            <h3 id="tenantModalTitle" class="tenant-modal-title">Yönetim Panelinize Giriş Yapın</h3>
            <p class="tenant-modal-desc">Superadmin'de belirlenen işletme kullanıcı adınızı (subdomain) girerek yönetim panelinize doğrudan ulaşın.</p>
            
            <form id="tenantModalForm" onsubmit="handleTenantModalSubmit(event)">
                <div class="tenant-input-group">
                    <label for="tenantModalInput">İşletme Kullanıcı Adı (Subdomain)</label>
                    <div class="tenant-input-wrap">
                        <i class="fas fa-store"></i>
                        <input type="text" id="tenantModalInput" placeholder="isletme-kullanici-adi" autocomplete="off" spellcheck="false" required>
                    </div>
                    <div class="tenant-modal-preview">
                        Adres: <strong id="tenantModalPreviewUrl">https://...-bookiapp.kibusiness.co/login</strong>
                    </div>
                </div>
                
                <button type="submit" id="tenantModalBtn" class="tenant-modal-btn">
                    <span>Giriş Ekranına Git</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="tenant-modal-footer">
                <a href="<?php echo $portal_url; ?>" class="tenant-modal-portal-link">
                    <i class="fas fa-search"></i> İşletme adınızı hatırlamıyor musunuz? E-posta ile bulun
                </a>
            </div>
        </div>
    </div>
</body>
</html>
